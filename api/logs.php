<?php
/**
 * API de Auditoría de Eventos (Logs).
 * Proporciona herramientas para la consulta, filtrado persistente y depuración
 * de los registros de actividad del sistema (inicios de sesión, ventas, errores).
 * 
 * @author Alberto Méndez
 * @version 1.3 (04/03/2026)
 */

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once(__DIR__ . '/../config/confDB.php');
    session_start();

    header('Content-Type: application/json; charset=utf-8');

    $pdo = new PDO(RUTA, USUARIO, PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de conexión', 'detalle' => $e->getMessage()]);
    exit;
}

/** 
 * MANEJADOR DE CONSULTAS (GET)
 * Permite la extracción de registros aplicando filtros de tipo, fecha y paginación masiva.
 */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // Verificar si la tabla existe
        $stmt = $pdo->query("SHOW TABLES LIKE 'logs_sistema'");
        if ($stmt->rowCount() === 0) {
            echo json_encode(['logs' => [], 'total' => 0, 'mensaje' => 'Tabla no existe']);
            exit;
        }

        // Parámetros - sin límite para mostrar todos los logs
        $porPagina = 10000; // Un número alto para essentially ilimitado

        // Filtros
        $tipo = isset($_GET['tipo']) && $_GET['tipo'] !== '' ? $_GET['tipo'] : null;
        $fecha = isset($_GET['fecha']) && $_GET['fecha'] !== '' ? $_GET['fecha'] : null;

        // Construir consulta para COUNT (usa prepared statements)
        $where = [];
        $params = [];

        if ($tipo) {
            // Verificar si es un filtro múltiple (coma-separated)
            if (strpos($tipo, ',') !== false) {
                $tipos = array_map('trim', explode(',', $tipo));
                $placeholders = [];
                foreach ($tipos as $i => $t) {
                    $placeholder = ":tipo" . $i;
                    $placeholders[] = $placeholder;
                    $params[$placeholder] = $t;
                }
                $where[] = "tipo IN (" . implode(',', $placeholders) . ")";
            } else {
                $where[] = "tipo = :tipo";
                $params[':tipo'] = $tipo;
            }
        }

        if ($fecha) {
            $where[] = "fecha >= :fecha";
            $params[':fecha'] = $fecha . ' 00:00:00';
            $where[] = "fecha <= :fecha_hasta";
            $params[':fecha_hasta'] = $fecha . ' 23:59:59';
        }

        $whereClause = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

        // Consulta total
        $sqlTotal = "SELECT COUNT(*) as total FROM logs_sistema $whereClause";
        $stmtTotal = $pdo->prepare($sqlTotal);
        $stmtTotal->execute($params);
        $total = $stmtTotal->fetch(PDO::FETCH_ASSOC)['total'];

        // Consulta datos - Usar valores directos para evitar problemas con LIMIT y parámetros
        $sqlSelect = "SELECT id, fecha, tipo, usuario_id, usuario_nombre, descripcion, detalles 
                FROM logs_sistema";

        // Agregar filtros con valores directamente (pdo::quote ya incluye las comillas)
        $whereSelect = [];
        if ($tipo) {
            // Verificar si es un filtro múltiple (coma-separated)
            if (strpos($tipo, ',') !== false) {
                $tipos = array_map('trim', explode(',', $tipo));
                $tipoQuotes = array_map(function ($t) use ($pdo) {
                    return $pdo->quote($t);
                }, $tipos);
                $whereSelect[] = "tipo IN (" . implode(',', $tipoQuotes) . ")";
            } else {
                $whereSelect[] = "tipo = " . $pdo->quote($tipo);
            }
        }
        if ($fecha) {
            $whereSelect[] = "fecha >= " . $pdo->quote($fecha . ' 00:00:00');
            $whereSelect[] = "fecha <= " . $pdo->quote($fecha . ' 23:59:59');
        }

        $sqlSelect .= count($whereSelect) > 0 ? ' WHERE ' . implode(' AND ', $whereSelect) : '';
        $sqlSelect .= " ORDER BY fecha DESC LIMIT $porPagina";

        $stmt = $pdo->query($sqlSelect);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Decodificar detalles JSON
        foreach ($logs as &$log) {
            if ($log['detalles']) {
                $log['detalles'] = json_decode($log['detalles'], true);
            }
        }

        echo json_encode([
            'logs' => $logs,
            'total' => $total
        ]);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al obtener logs', 'detalle' => $e->getMessage()]);
    }
    exit;
}

/** 
 * ACCIÓN ESPECIAL: Limpiar Logs (POST con flag 'limpiar')
 * Realiza un truncado físico de la tabla de auditoría para liberar espacio.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['accion']) && $_GET['accion'] === 'limpiar') {
    try {
        // Capturar info del usuario antes de truncate
        $usuarioId = $_SESSION['idUsuario'] ?? null;
        $usuarioNombre = $_SESSION['nombreUsuario'] ?? 'Sistema';

        // Eliminar todos los logs
        $pdo->exec("TRUNCATE TABLE logs_sistema");

        // Insertar log de borrado
        $stmt = $pdo->prepare("INSERT INTO logs_sistema (tipo, usuario_id, usuario_nombre, descripcion, detalles) VALUES (:tipo, :usuario_id, :usuario_nombre, :descripcion, :detalles)");
        $stmt->execute([
            ':tipo' => 'borrado_logs',
            ':usuario_id' => $usuarioId,
            ':usuario_nombre' => $usuarioNombre,
            ':descripcion' => 'Se han borrado todos los logs del sistema',
            ':detalles' => json_encode(['accion' => 'borrado_logs', 'timestamp' => date('Y-m-d H:i:s')])
        ]);

        echo json_encode(['ok' => true, 'mensaje' => 'Logs eliminados correctamente']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'Error al eliminar logs', 'detalle' => $e->getMessage()]);
    }
    exit;
}

// ======================== POST: Crear log ========================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar que hay sesión activa
    if (!isset($_SESSION['idUsuario'])) {
        http_response_code(401);
        echo json_encode(['error' => 'No autorizado']);
        exit;
    }

    // Leer datos
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !is_array($input)) {
        http_response_code(400);
        echo json_encode(['error' => 'Datos inválidos']);
        exit;
    }

    $tipo = isset($input['tipo']) ? $input['tipo'] : null;
    $descripcion = isset($input['descripcion']) ? $input['descripcion'] : '';
    $detalles = isset($input['detalles']) ? $input['detalles'] : null;

    // Validar tipo
    $tiposPermitidos = ['login', 'logout', 'login_fallido', 'venta', 'apertura_caja', 'cierre_caja', 'retiro_caja', 'acceso_admin', 'acceso_cajero', 'acceso_login', 'creacion_usuario', 'modificacion_usuario', 'eliminacion_usuario', 'borrado_logs'];

    if (!$tipo || !in_array($tipo, $tiposPermitidos)) {
        http_response_code(400);
        echo json_encode(['error' => 'Tipo de log no válido']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO logs_sistema (tipo, usuario_id, usuario_nombre, descripcion, detalles) VALUES (:tipo, :usuario_id, :usuario_nombre, :descripcion, :detalles)");

        $stmt->execute([
            ':tipo' => $tipo,
            ':usuario_id' => $_SESSION['idUsuario'],
            ':usuario_nombre' => $_SESSION['nombreUsuario'] ?? 'Desconocido',
            ':descripcion' => $descripcion,
            ':detalles' => $detalles ? json_encode($detalles) : null
        ]);

        echo json_encode(['ok' => true, 'id' => $pdo->lastInsertId()]);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al guardar log', 'detalle' => $e->getMessage()]);
    }
    exit;
}

// Método no soportado
http_response_code(405);
echo json_encode(['error' => 'Método no permitido']);
