<?php
/**
 * API de Gestión de Backups.
 * Permite la creación manual, listado y eliminación de copias de seguridad.
 *
 * @author Alberto Méndez
 * @version 1.0 (2026)
 */

// Error logging for debugging - session_start must be first
error_reporting(E_ALL);
ini_set('display_errors', 0); // Set to 0 in production
ini_set('log_errors', 1);
ini_set('memory_limit', '1024M'); // Allow more memory for backup operations

session_start();

error_log("backups.php called - Method: " . $_SERVER['REQUEST_METHOD']);
error_log("Session idUsuario after start: " . (isset($_SESSION['idUsuario']) ? $_SESSION['idUsuario'] : 'NOT SET'));
error_log("Session rolUsuario after start: " . (isset($_SESSION['rolUsuario']) ? $_SESSION['rolUsuario'] : 'NOT SET'));

require_once(__DIR__ . '/../config/confDB.php');
require_once(__DIR__ . '/../core/BackupManager.php');

// Solo administradores pueden gestionar backups
// Bypass for debugging - if session exists but role is wrong, log it
if (!isset($_SESSION['idUsuario'])) {
    error_log("Access denied - No idUsuario in session");
    http_response_code(200); // Return 200 to avoid browser 403 error
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => 'Sesión no válida. Por favor, inicia sesión novamente.', 'code' => 'NO_SESSION']);
    exit;
}

if (!isset($_SESSION['rolUsuario']) || $_SESSION['rolUsuario'] !== 'admin') {
    error_log("User logged in but rol is: " . ($_SESSION['rolUsuario'] ?? 'NOT SET'));
    http_response_code(200); // Return 200 to avoid browser 403 error
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => 'No tienes permisos de administrador para acceder a backups.', 'code' => 'NOT_ADMIN']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

// Enable output buffering and error capture for debugging
ob_start();

set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    error_log("PHP Error [$errno]: $errstr in $errfile on line $errline");
    return true;
});

register_shutdown_function(function () {
    $error = error_get_last();
    if ($error !== null && ($error['type'] & E_ERROR)) {
        error_log("Fatal Error: " . $error['message'] . " in " . $error['file'] . " on line " . $error['line']);
    }
});

// Clear any output buffers
while (ob_get_level()) {
    ob_end_clean();
}
ob_start();

try {
    $pdo = new PDO(RUTA, USUARIO, PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Debug endpoint - remove in production
    if (isset($_GET['debug'])) {
        echo json_encode([
            'session_id' => session_id(),
            'idUsuario' => isset($_SESSION['idUsuario']) ? $_SESSION['idUsuario'] : null,
            'rolUsuario' => isset($_SESSION['rolUsuario']) ? $_SESSION['rolUsuario'] : null,
            'nombreUsuario' => isset($_SESSION['nombreUsuario']) ? $_SESSION['nombreUsuario'] : null
        ]);
        exit;
    }

    // Endpoint para obtener el número de filas de una tabla
    if (isset($_GET['getRowCount']) && isset($_GET['tabla'])) {
        $tabla = $_GET['tabla'];
        $stmt = $pdo->query("SELECT COUNT(*) FROM $tabla");
        $count = $stmt->fetchColumn();
        echo json_encode(['ok' => true, 'total' => $count]);
        exit;
    }

    // Endpoint para obtener el progreso del backup (para tablas grandes)
    if (isset($_GET['getBackupProgress'])) {
        $tabla = $_GET['tabla'] ?? 'general';
        $progressFile = sys_get_temp_dir() . '/backup_progress_' . $tabla . '.json';

        if (file_exists($progressFile)) {
            $data = json_decode(file_get_contents($progressFile), true);
            echo json_encode([
                'ok' => true,
                'progreso' => $data['actual'] ?? 0,
                'total' => $data['total'] ?? 0,
                'porcentaje' => $data['total'] > 0 ? round(($data['actual'] / $data['total']) * 100) : 0
            ]);
        }
        else {
            echo json_encode(['ok' => true, 'progreso' => 0, 'total' => 0, 'porcentaje' => 0]);
        }
        exit;
    }
    // Endpoint para cancelar un backup en progreso
    if (isset($_GET['cancelBackup'])) {
        session_write_close();
        $tabla = $_GET['tabla'] ?? 'general';

        // Usar archivo simple de cancelación
        $cancelFile = sys_get_temp_dir() . '/backup_cancel_' . $tabla . '.txt';

        error_log("Cancel requested for tabla: " . $tabla);
        error_log("Cancel file path: " . $cancelFile);

        // Crear archivo de cancelación
        $result = file_put_contents($cancelFile, 'cancel');

        error_log("Cancel file write result: " . ($result !== false ? 'success' : 'failed'));

        echo json_encode(['ok' => true, 'message' => 'Backup cancelado']);
        exit;
    }

    $manager = new BackupManager($pdo);

    $metodo = $_SERVER['REQUEST_METHOD'];

    if ($metodo === 'GET') {
        if (isset($_GET['accion']) && $_GET['accion'] === 'descargar' && isset($_GET['archivo'])) {
            $archivo = basename($_GET['archivo']);
            $ruta = __DIR__ . '/../backups/' . $archivo;
            if (file_exists($ruta)) {
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . $archivo . '"');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($ruta));
                readfile($ruta);
                exit;
            }
            else {
                http_response_code(404);
                echo json_encode(['error' => 'Archivo no encontrado']);
            }
        }
        else {
            // Listado de backups
            $backups = $manager->listarBackups();
            echo json_encode(['ok' => true, 'backups' => $backups]);
        }
    }
    elseif ($metodo === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $accion = isset($input['accion']) ? $input['accion'] : '';

        if ($accion === 'crear') {
            error_log("Creating backup...");
            $resultado = $manager->crearBackupCompleto();
            error_log("Backup result: " . json_encode($resultado));

            // Ensure clean output
            while (ob_get_level()) {
                ob_end_clean();
            }
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($resultado);
            exit;
        }
        elseif ($accion === 'crear_tabla') {
            // Backup de tabla específica (para tablas grandes)
            if (!isset($input['tabla'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Falta el nombre de la tabla']);
                exit;
            }
            $tabla = $input['tabla'];
            session_write_close();
            $resultado = $manager->crearBackupTabla($tabla);
            echo json_encode($resultado);
            exit;
        }
        elseif ($accion === 'rotar') {
            $eliminados = $manager->rotarBackups(30);
            echo json_encode(['ok' => true, 'eliminados' => $eliminados]);
        }
        elseif ($accion === 'restaurar' && isset($input['archivo'])) {
            $archivo = basename($input['archivo']);
            // Check if it's a .sql file (table backup) or .enc file (full backup)
            if (preg_match('/\.sql$/', $archivo)) {
                $resultado = $manager->restaurarTabla($archivo);
            }
            else {
                $resultado = $manager->restaurarBackup($archivo);
            }
            echo json_encode($resultado);
        }
        else {
            http_response_code(400);
            echo json_encode(['error' => 'Acción no válida']);
        }
    }
    elseif ($metodo === 'DELETE') {
        if (isset($_GET['archivo'])) {
            $archivo = basename($_GET['archivo']);
            $ruta = __DIR__ . '/../backups/' . $archivo;
            if (file_exists($ruta)) {
                unlink($ruta);
                echo json_encode(['ok' => true, 'mensaje' => 'Backup eliminado']);
            }
            else {
                http_response_code(404);
                echo json_encode(['error' => 'Archivo no encontrado']);
            }
        }
        else {
            http_response_code(400);
            echo json_encode(['error' => 'Falta el nombre del archivo']);
        }
    }
}
catch (Exception $e) {
    error_log("Backup Exception: " . $e->getMessage());
    error_log("Backup Exception Trace: " . $e->getTraceAsString());

    // Ensure clean output
    while (ob_get_level()) {
        ob_end_clean();
    }

    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'Error en el sistema de backup',
        'detalle' => $e->getMessage()
    ]);
}
