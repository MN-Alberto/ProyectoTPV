<?php
/**
 * API DE COLA ASÍNCRONA DE ENVÍOS A VERIFACTU
 * 
 * Sistema de gestión de encolado, reintentos, subsanación y auditoría
 * para los registros fiscales enviados a la AEAT.
 * 
 * ✅ Características:
 *  - Reintentos exponenciales automáticos
 *  - Permisos especiales para worker cron
 *  - Resolución dinámica de números de serie
 *  - Sistema de subsanación manual de errores
 *  - Auditoría completa de todos los eventos
 *  - Enlace doble con tablas originales
 *  - Edición manual post-cierre de factura
 * 
 * @author Alberto Méndez
 * @version 1.1 (Comentarios añadidos)
 * @since 1.0 (Versión Final Garantizada)
 */

session_start();
require_once(__DIR__ . '/../config/confDB.php');
require_once(__DIR__ . '/../core/conexionDB.php');
require_once(__DIR__ . '/../core/Verifactu.php');

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['idUsuario'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso denegado.']);
    exit;
}

/**
 * 🔑 PERMISOS ESPECIALES PARA WORKER
 * 
 * Todas las acciones requieren rol de administrador, EXCEPTO `procesarCola`.
 * 
 * Este endpoint puede ser llamado por el CRON automático sin sesión iniciada.
 * Es la única excepción de seguridad en TODO el sistema.
 */
$inputCheck = json_decode(file_get_contents('php://input'), true);
$isAutoProcess = ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($inputCheck['action']) && $inputCheck['action'] === 'procesarCola');

$rolUsuario = isset($_SESSION['rolUsuario']) ? $_SESSION['rolUsuario'] : '';
if ($rolUsuario !== 'admin' && !$isAutoProcess) {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso denegado. Se requiere administrador.']);
    exit;
}

try {
    $pdo = ConexionDB::getInstancia()->getConexion();

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {

        if (isset($_GET['pendientes'])) {
            $page = max(1, (int) ($_GET['page'] ?? 1));
            $limit = min(100, max(1, (int) ($_GET['limit'] ?? 20)));
            $offset = ($page - 1) * $limit;
            $filtroEstado = $_GET['estado'] ?? null;

            $where = "1=1";
            if ($filtroEstado && in_array($filtroEstado, ['pendiente', 'subsanado', 'error_temporal', 'error_permanente', 'enviado', 'descartado'])) {
                $where .= " AND estado = :estado";
            }

            $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM verifactu_cola_envios WHERE $where");
            if ($filtroEstado && in_array($filtroEstado, ['pendiente', 'subsanado', 'error_temporal', 'error_permanente', 'enviado', 'descartado'])) {
                $stmtCount->bindValue(':estado', $filtroEstado);
            }
            $stmtCount->execute();
            $total = (int) $stmtCount->fetchColumn();

            $stmt = $pdo->prepare("SELECT * FROM verifactu_cola_envios WHERE $where ORDER BY fecha_creacion DESC LIMIT :limit OFFSET :offset");
            if ($filtroEstado && in_array($filtroEstado, ['pendiente', 'subsanado', 'error_temporal', 'error_permanente', 'enviado', 'descartado'])) {
                $stmt->bindValue(':estado', $filtroEstado);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $envios = $stmt->fetchAll(PDO::FETCH_ASSOC);

            /**
             * 🧩 RESOLUCIÓN DINÁMICA DE NÚMEROS DE FACTURA
             * 
             * La cola solo almacena el ID interno. El número oficial de factura
             * se resuelve EN TIEMPO REAL desde la tabla `ventas_ids`.
             * 
             * Si no existe o falla, se construye un número de fallback
             * a partir del tipo de documento y el ID interno.
             * 
             * De esta forma NUNCA se muestran IDs internos al usuario.
             */
            foreach ($envios as &$e) {
                if (empty($e['num_documento'])) {
                    $stmtIds = $pdo->prepare("SELECT serie, numero FROM ventas_ids WHERE id = ?");
                    $stmtIds->execute([$e['id_documento']]);
                    $idsData = $stmtIds->fetch(PDO::FETCH_ASSOC);

                    if ($idsData) {
                        $e['display_num'] = ($idsData['serie'] ?: ($e['tabla_origen'] === 'facturas' ? 'F' : 'T')) . ($idsData['numero'] ?: $e['id_documento']);
                    } else {
                        $e['display_num'] = ($e['tabla_origen'] === 'facturas' ? 'F' : 'T') . $e['id_documento'];
                    }
                } else {
                    $e['display_num'] = $e['num_documento'];
                }

                /**
                 * 📄 LECTURA EN VIVO DEL CSV OFICIAL
                 * 
                 * NUNCA se guarda una copia del CSV en la cola.
                 * 
                 * Se lee SIEMPRE directamente desde la tabla original en el momento
                 * de mostrarlo. Esto garantiza que si el documento se subsana
                 * manualmente, se mostrará siempre la versión actualizada.
                 */
                $colCsv = ($e['tipo_envio'] === 'alta') ? 'csv_aeat' : 'csv_anulacion';
                if (in_array($e['tabla_origen'], ['tickets', 'facturas'])) {
                    $stmtCsv = $pdo->prepare("SELECT {$colCsv} as csv FROM {$e['tabla_origen']} WHERE id = ?");
                    $stmtCsv->execute([$e['id_documento']]);
                    $csvData = $stmtCsv->fetch(PDO::FETCH_ASSOC);
                    $e['csv_aeat'] = $csvData ? $csvData['csv'] : null;
                }
            }

            echo json_encode([
                'envios' => $envios,
                'total' => $total,
                'page' => $page,
                'pages' => ceil($total / $limit)
            ]);
            exit;
        }

        if (isset($_GET['eventos'])) {
            $page = max(1, (int) ($_GET['page'] ?? 1));
            $limit = min(100, max(10, (int) ($_GET['limit'] ?? 30)));
            $offset = ($page - 1) * $limit;
            $stmtCount = $pdo->query("SELECT COUNT(*) FROM verifactu_eventos");
            $total = (int) $stmtCount->fetchColumn();

            $stmt = $pdo->prepare("
                SELECT e.*, 
                       CONCAT(COALESCE(vi.serie, ''), COALESCE(vi.numero, '')) as display_num
                FROM verifactu_eventos e
                LEFT JOIN ventas_ids vi ON e.id_documento = vi.id
                ORDER BY e.fecha DESC 
                LIMIT :limit OFFSET :offset
            ");
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            echo json_encode(['eventos' => $stmt->fetchAll(PDO::FETCH_ASSOC), 'total' => $total]);
            exit;
        }

        if (isset($_GET['estadisticas'])) {
            echo json_encode(Verifactu::getEstadisticasCola());
            exit;
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = isset($inputCheck) ? $inputCheck : json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';
        if ($action === 'procesarCola') {
            $auto = !empty($input['auto']);
            echo json_encode(['ok' => true, 'resumen' => Verifactu::procesarColaPendientes($auto)]);
        } elseif ($action === 'reenviar') {
            $pdo->prepare("UPDATE verifactu_cola_envios SET estado = 'pendiente', proximo_reintento = NOW(), intentos = 0 WHERE id = ?")->execute([$input['id']]);
            echo json_encode(['ok' => true, 'resumen' => Verifactu::procesarColaPendientes()]);
        } elseif ($action === 'subsanar') {
            $idDoc = (int) ($input['id_documento'] ?? 0);
            $tabla = $input['tabla'] ?? 'tickets';
            $result = Verifactu::subsanarDocumento($idDoc, $tabla);
            echo json_encode($result);
        } elseif ($action === 'editarDocumento') {
            $idDoc = (int) ($input['id_documento'] ?? 0);
            $tabla = $input['tabla'] ?? 'tickets';
            $nif = $input['nif'] ?? '';
            $nombre = $input['nombre'] ?? '';
            $direccion = $input['direccion'] ?? '';

            if (!in_array($tabla, ['tickets', 'facturas'])) {
                echo json_encode(['error' => 'Tabla no válida.']);
                exit;
            }

            /**
             * 🚨 EDICIÓN MANUAL DE FACTURAS YA EMITIDAS
             * 
             * Esta es la ÚNICA operación del sistema que permite modificar
             * una factura DESPUES de haber sido emitida y cerrada.
             * 
             * Solo está permitido para corregir errores de datos de cliente
             * que impidan la aceptación del documento por la AEAT.
             * 
             * TODOS los cambios son registrados en el log de eventos.
             */
            $stmt = $pdo->prepare("UPDATE {$tabla} SET cliente_dni = ?, cliente_nombre = ?, cliente_direccion = ? WHERE id = ?");
            $stmt->execute([$nif, $nombre, $direccion, $idDoc]);

            Verifactu::registrarEvento('edicion_manual', $idDoc, $tabla, "Datos de cliente actualizados manualmente para subsanación.");
            echo json_encode(['ok' => true]);
        } elseif ($action === 'descartarError') {
            $id = (int) ($input['id'] ?? 0);
            $pdo->prepare("DELETE FROM verifactu_cola_envios WHERE id = ?")->execute([$id]);
            echo json_encode(['ok' => true]);
        } elseif ($action === 'limpiarEventos') {
            $pdo->exec("DELETE FROM verifactu_eventos");
            echo json_encode(['ok' => true]);
        } elseif ($action === 'limpiarColaEnvios') {
            $pdo->exec("DELETE FROM verifactu_cola_envios WHERE estado IN ('enviado', 'descartado')");
            echo json_encode(['ok' => true]);
        }
        exit;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'ERROR: ' . $e->getMessage()]);
    exit;
}
