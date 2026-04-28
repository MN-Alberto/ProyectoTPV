<?php
/**
 * API Cola de Envíos Verifactu.
 * Versión Final Garantizada (Enlace via ventas_ids)
 */

session_start();
require_once(__DIR__ . '/../config/confDB.php');
require_once(__DIR__ . '/../core/conexionDB.php');
require_once(__DIR__ . '/../core/Verifactu.php');

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['rolUsuario']) || $_SESSION['rolUsuario'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso denegado.']);
    exit;
}

try {
    $pdo = ConexionDB::getInstancia()->getConexion();

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {

        if (isset($_GET['pendientes'])) {
            $page = max(1, (int)($_GET['page'] ?? 1));
            $limit = min(100, max(1, (int)($_GET['limit'] ?? 20)));
            $offset = ($page - 1) * $limit;
            $filtroEstado = $_GET['estado'] ?? null;

            $where = "1=1";
            if ($filtroEstado && in_array($filtroEstado, ['pendiente','error_temporal','error_permanente','enviado','descartado'])) {
                $where .= " AND estado = :estado";
            }

            $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM verifactu_cola_envios WHERE $where");
            if ($filtroEstado && in_array($filtroEstado, ['pendiente','error_temporal','error_permanente','enviado','descartado'])) {
                $stmtCount->bindValue(':estado', $filtroEstado);
            }
            $stmtCount->execute();
            $total = (int)$stmtCount->fetchColumn();

            $stmt = $pdo->prepare("SELECT * FROM verifactu_cola_envios WHERE $where ORDER BY fecha_creacion DESC LIMIT :limit OFFSET :offset");
            if ($filtroEstado && in_array($filtroEstado, ['pendiente','error_temporal','error_permanente','enviado','descartado'])) {
                $stmt->bindValue(':estado', $filtroEstado);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $envios = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Recuperar Serie+Numero desde ventas_ids
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
            $page = max(1, (int)($_GET['page'] ?? 1));
            $limit = min(100, max(10, (int)($_GET['limit'] ?? 30)));
            $offset = ($page - 1) * $limit;
            $stmtCount = $pdo->query("SELECT COUNT(*) FROM verifactu_eventos");
            $total = (int)$stmtCount->fetchColumn();

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
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';
        if ($action === 'procesarCola') {
            echo json_encode(['ok' => true, 'resumen' => Verifactu::procesarColaPendientes()]);
        } elseif ($action === 'reenviar') {
            $pdo->prepare("UPDATE verifactu_cola_envios SET estado = 'pendiente', proximo_reintento = NOW() WHERE id = ?")->execute([$input['id']]);
            echo json_encode(['ok' => true, 'resumen' => Verifactu::procesarColaPendientes()]);
        } elseif ($action === 'subsanar') {
            $idDoc = (int)($input['id_documento'] ?? 0);
            $tabla = $input['tabla'] ?? 'tickets';
            $result = Verifactu::subsanarDocumento($idDoc, $tabla);
            echo json_encode($result);
        } elseif ($action === 'editarDocumento') {
            $idDoc = (int)($input['id_documento'] ?? 0);
            $tabla = $input['tabla'] ?? 'tickets';
            $nif = $input['nif'] ?? '';
            $nombre = $input['nombre'] ?? '';
            $direccion = $input['direccion'] ?? '';
            if (!in_array($tabla, ['tickets', 'facturas'])) { echo json_encode(['error' => 'Tabla no válida.']); exit; }
            $stmt = $pdo->prepare("UPDATE {$tabla} SET cliente_dni = ?, cliente_nombre = ?, cliente_direccion = ? WHERE id = ?");
            $stmt->execute([$nif, $nombre, $direccion, $idDoc]);
            Verifactu::registrarEvento('edicion_manual', $idDoc, $tabla, "Datos de cliente actualizados manualmente para subsanación.");
            echo json_encode(['ok' => true]);
        } elseif ($action === 'descartarError') {
            $id = (int)($input['id'] ?? 0);
            $pdo->prepare("DELETE FROM verifactu_cola_envios WHERE id = ?")->execute([$id]);
            echo json_encode(['ok' => true]);
        }
        exit;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'ERROR: ' . $e->getMessage()]);
    exit;
}
