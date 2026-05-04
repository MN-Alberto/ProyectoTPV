<?php
/**
 * API de Gestión de Backups.
 * Permite la creación manual, listado y eliminación de copias de seguridad.
 *
 * @author Alberto Méndez
 * @version 1.0 (2026)
 */

/**
 * CONFIGURACIÓN INICIAL DEL ENTORNO
 * 
 * Se establecen parámetros específicos para operaciones de backup
 * ya que estas requieren mas recursos que una petición normal
 */
error_reporting(E_ALL);
ini_set('display_errors', 0); // No mostrar errores al cliente en producción
ini_set('log_errors', 1);     // Registrar todos los errores en archivo de log
ini_set('memory_limit', '1024M'); // Aumentar limite de memoria para generar backups grandes

// Iniciar sesión ANTES de cualquier salida, requisito de PHP
session_start();

/**
 * REGISTRO DE TRAZAS PARA DEPURACIÓN
 * 
 * Se registra toda llamada a esta API para seguimiento
 * y detección de problemas de acceso
 */
error_log("backups.php called - Method: " . $_SERVER['REQUEST_METHOD']);
error_log("Session idUsuario after start: " . (isset($_SESSION['idUsuario']) ? $_SESSION['idUsuario'] : 'NOT SET'));
error_log("Session rolUsuario after start: " . (isset($_SESSION['rolUsuario']) ? $_SESSION['rolUsuario'] : 'NOT SET'));

/**
 * CARGA DE DEPENDENCIAS
 * 
 * - Configuración de base de datos
 * - Clase principal que implementa toda la lógica de backups
 */
require_once(__DIR__ . '/../config/confDB.php');
require_once(__DIR__ . '/../core/BackupManager.php');

/**
 * CONTROL DE ACCESO Y PERMISOS
 * 
 * SOLAMENTE los usuarios con rol ADMINISTRADOR pueden acceder
 * a todas las funcionalidades de gestión de backups.
 * 
 * NOTA: Se devuelve codigo HTTP 200 en lugar de 403 para evitar
 * que los navegadores intercepten el error y no muestren el mensaje
 * de error personalizado al usuario final.
 */
if (!isset($_SESSION['idUsuario'])) {
    error_log("Access denied - No idUsuario in session");
    http_response_code(200);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => 'Sesión no válida. Por favor, inicia sesión novamente.', 'code' => 'NO_SESSION']);
    exit;
}

if (!isset($_SESSION['rolUsuario']) || $_SESSION['rolUsuario'] !== 'admin') {
    error_log("User logged in but rol is: " . ($_SESSION['rolUsuario'] ?? 'NOT SET'));
    http_response_code(200);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => 'No tienes permisos de administrador para acceder a backups.', 'code' => 'NOT_ADMIN']);
    exit;
}

// Establecer tipo de respuesta estandar para toda la API
header('Content-Type: application/json; charset=utf-8');

/**
 * SISTEMA DE CAPTURA DE ERRORES GLOBAL
 * 
 * Se implementa un sistema de captura de TODOS los errores
 * incluso errores fatales que normalmente detendrían la ejecución
 * sin dejar rastro. Todos quedan registrados en el log del sistema.
 */
ob_start();

// Capturar errores normales, warnings y notices
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    error_log("PHP Error [$errno]: $errstr in $errfile on line $errline");
    return true; // No dejar que PHP maneje el error
});

// Capturar errores fatales que matan la ejecución
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error !== null && ($error['type'] & E_ERROR)) {
        error_log("Fatal Error: " . $error['message'] . " in " . $error['file'] . " on line " . $error['line']);
    }
});

/**
 * LIMPIEZA DE BUFFERS DE SALIDA
 * 
 * Se eliminan TODOS los buffers existentes para asegurar
 * que no quede salida basura antes de enviar la respuesta JSON.
 * Esto evita respuestas corruptas por warnings o ecos accidentales.
 */
while (ob_get_level()) {
    ob_end_clean();
}
ob_start();

try {
    // Inicializar conexión a base de datos en modo excepciones
    $pdo = new PDO(RUTA, USUARIO, PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // ==============================================
    // ENDPOINTS DE LA API
    // ==============================================

    /**
     * ENDPOINT: Debug de sesión
     * 
     * @param debug Parametro GET para activar
     * @return Datos actuales de la sesión del usuario
     */
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

    /**
     * ENDPOINT: Contar filas de una tabla
     * 
     * Utilizado para calcular el progreso antes de iniciar un backup
     * de tablas muy grandes con muchos registros.
     * 
     * @param getRowCount Activar endpoint
     * @param tabla Nombre de la tabla a consultar
     * @return Numero total de registros
     */
    if (isset($_GET['getRowCount']) && isset($_GET['tabla'])) {
        $tabla = $_GET['tabla'];
        $stmt = $pdo->query("SELECT COUNT(*) FROM $tabla");
        $count = $stmt->fetchColumn();
        echo json_encode(['ok' => true, 'total' => $count]);
        exit;
    }

    /**
     * ENDPOINT: Obtener progreso de backup en tiempo real
     * 
     * Funciona mediante un archivo temporal que actualiza el proceso
     * de backup cada cierto numero de registros. El frontend consulta
     * este endpoint periodicamente para mostrar la barra de progreso.
     * 
     * @param getBackupProgress Activar endpoint
     * @param tabla Nombre de la tabla (opcional)
     * @return Progreso actual, total y porcentaje completado
     */
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
        } else {
            echo json_encode(['ok' => true, 'progreso' => 0, 'total' => 0, 'porcentaje' => 0]);
        }
        exit;
    }
    /**
     * ENDPOINT: Cancelar backup en ejecución
     * 
     * Funciona creando un archivo señal. El proceso de backup
     * comprueba periodicamente la existencia de este archivo
     * y se detiene de forma segura si existe.
     * 
     * @param cancelBackup Activar endpoint
     * @param tabla Nombre de la tabla (opcional)
     * @return Confirmación de solicitud enviada
     */
    if (isset($_GET['cancelBackup'])) {
        session_write_close(); // Liberar bloqueo de sesión
        $tabla = $_GET['tabla'] ?? 'general';

        $cancelFile = sys_get_temp_dir() . '/backup_cancel_' . $tabla . '.txt';

        error_log("Cancel requested for tabla: " . $tabla);
        error_log("Cancel file path: " . $cancelFile);

        // El simple hecho de crear este archivo cancela el backup
        $result = file_put_contents($cancelFile, 'cancel');

        error_log("Cancel file write result: " . ($result !== false ? 'success' : 'failed'));

        echo json_encode(['ok' => true, 'message' => 'Backup cancelado']);
        exit;
    }

    // Instanciar gestor de backups con la conexión activa
    $manager = new BackupManager($pdo);

    // Obtener metodo HTTP para enrutar la petición
    $metodo = $_SERVER['REQUEST_METHOD'];

    /**
     * METODO GET: Lectura y descarga
     */
    if ($metodo === 'GET') {
        /**
         * ENDPOINT: Descargar archivo de backup
         * 
         * Se usa basename() para evitar path traversal attacks
         * que permitirían descargar cualquier archivo del servidor.
         * 
         * @param accion=descargar
         * @param archivo Nombre del archivo en la carpeta backups
         * @return Archivo para descargar o error 404
         */
        if (isset($_GET['accion']) && $_GET['accion'] === 'descargar' && isset($_GET['archivo'])) {
            $archivo = basename($_GET['archivo']); // Seguridad: solo nombre de archivo
            $ruta = __DIR__ . '/../backups/' . $archivo;

            if (file_exists($ruta)) {
                // Cabeceras estandar para descarga de archivos
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . $archivo . '"');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($ruta));

                readfile($ruta); // Enviar archivo directamente al cliente
                exit;
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Archivo no encontrado']);
            }
        } else {
            /**
             * ENDPOINT: Listar todos los backups existentes
             * 
             * Comportamiento por defecto del metodo GET sin parametros.
             * Devuelve array con información de fecha, tamaño y nombre
             * de cada archivo de backup almacenado en el sistema.
             */
            $backups = $manager->listarBackups();
            echo json_encode(['ok' => true, 'backups' => $backups]);
        }
    }

    /**
     * METODO POST: Acciones de escritura
     * 
     * Todas las operaciones que modifican el sistema:
     * crear backups, restaurar, rotar, etc.
     */ elseif ($metodo === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $accion = isset($input['accion']) ? $input['accion'] : '';

        /**
         * ACCION: Crear backup COMPLETO de toda la base de datos
         * 
         * Genera una copia de seguridad cifrada de todas las tablas.
         * Esta operacion puede tardar varios segundos/minutos segun tamaño.
         */
        if ($accion === 'crear') {
            error_log("Creating backup...");
            $resultado = $manager->crearBackupCompleto();
            error_log("Backup result: " . json_encode($resultado));

            // Limpiar absolutamente toda salida antes de responder
            while (ob_get_level()) {
                ob_end_clean();
            }
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($resultado);
            exit;
        }
        /**
         * ACCION: Crear backup de una SOLA tabla
         * 
         * Para tablas muy grandes (ventas, lineas) se permite hacer
         * backup individual. Se cierra la sesion antes de empezar
         * para no bloquear otras peticiones del usuario mientras dura.
         */ elseif ($accion === 'crear_tabla') {
            if (!isset($input['tabla'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Falta el nombre de la tabla']);
                exit;
            }
            $tabla = $input['tabla'];
            session_write_close(); // Liberar sesion para no bloquear
            $resultado = $manager->crearBackupTabla($tabla);
            echo json_encode($resultado);
            exit;
        }
        /**
         * ACCION: Rotar backups antiguos
         * 
         * Elimina automaticamente todos los backups con mas de N dias.
         * Por defecto se mantienen los ultimos 30 dias.
         * Devuelve el numero de archivos que se eliminaron.
         */ elseif ($accion === 'rotar') {
            $eliminados = $manager->rotarBackups(30);
            echo json_encode(['ok' => true, 'eliminados' => $eliminados]);
        }
        /**
         * ACCION: Restaurar backup
         * 
         * Detecta automaticamente que tipo de backup es:
         * - .sql = backup de tabla individual
         * - .enc = backup completo cifrado
         * Y ejecuta la restauracion correspondiente.
         */ elseif ($accion === 'restaurar' && isset($input['archivo'])) {
            $archivo = basename($input['archivo']);

            // Detectar tipo de backup por extension del archivo
            if (preg_match('/\.sql$/', $archivo)) {
                $resultado = $manager->restaurarTabla($archivo);
            } else {
                $resultado = $manager->restaurarBackup($archivo);
            }
            echo json_encode($resultado);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Acción no válida']);
        }
    }
    /**
     * METODO DELETE: Eliminar backups
     */ elseif ($metodo === 'DELETE') {
        if (isset($_GET['archivo'])) {
            $archivo = basename($_GET['archivo']);
            $ruta = __DIR__ . '/../backups/' . $archivo;

            if (file_exists($ruta)) {
                unlink($ruta);
                echo json_encode(['ok' => true, 'mensaje' => 'Backup eliminado']);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Archivo no encontrado']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Falta el nombre del archivo']);
        }
    }
}
/**
 * MANEJO GLOBAL DE EXCEPCIONES
 * 
 * Cualquier error no capturado durante toda la ejecucion
 * llegara aqui, se registra en log, se limpia toda salida
 * y se devuelve un error JSON estandar al cliente.
 */ catch (Exception $e) {
    error_log("Backup Exception: " . $e->getMessage());
    error_log("Backup Exception Trace: " . $e->getTraceAsString());

    // Limpiar cualquier salida parcial que pueda existir
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
