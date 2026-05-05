<?php
/**
 * API de Auditoría de Retiros de Efectivo.
 * 
 * API exclusiva para administradores que proporciona acceso al historial
 * de extracciones de efectivo realizadas desde caja. Esta API es SOLO LECTURA,
 * no permite crear ni modificar retiros, solamente consultarlos.
 * 
 * ✅ Características:
 *  - Control de acceso estricto solo para administradores
 *  - Filtros predefinidos por rango de fecha
 *  - Ordenación configurable por fecha e importe
 *  - Información contextual completa con usuario y sesión de caja
 *  - Modo debug para diagnóstico
 *  - Verificación automática de existencia de la tabla
 * 
 * @author Alberto Méndez
 * @version 1.1 (Comentarios añadidos)
 * @since 1.0 (04/03/2026)
 */

session_start();
require_once(__DIR__ . '/../config/confDB.php');

header('Content-Type: application/json; charset=utf-8');

// Verificar si el usuario es administrador
if (!isset($_SESSION['rolUsuario']) || $_SESSION['rolUsuario'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Acceso denegado']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

/** 
 * MANEJADOR DE CONSULTAS (GET)
 * Recupera el listado de retiros aplicando filtros de tiempo y ordenación.
 */
if ($method === 'GET') {
    $orden = $_GET['orden'] ?? 'fecha_desc';

    /**
     * 🛡️ WHITELIST PARA ORDENACIÓN
     * 
     * Se usa una lista blanca de valores permitidos para la ordenación.
     * Esta es una medida de seguridad contra SQL Injection ya que el parámetro
     * `orden` proviene directamente del usuario y no se puede parametrizar en la consulta.
     * 
     * Cualquier valor no reconocido se sustituye por el valor por defecto `fecha_desc`.
     * NUNCA se concatena directamente entrada del usuario en la sentencia SQL.
     */
    $ordenesValidos = [
        'fecha_desc' => 'r.fecha DESC',
        'fecha_asc' => 'r.fecha ASC',
        'importe_desc' => 'r.importe DESC',
        'importe_asc' => 'r.importe ASC'
    ];

    if (!isset($ordenesValidos[$orden])) {
        $orden = 'fecha_desc';
    }
    $orderBy = $ordenesValidos[$orden];

    try {
        $pdo = new PDO(RUTA, USUARIO, PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        /**
         * 🧱 VERIFICACIÓN DE EXISTENCIA DE TABLA
         * 
         * Se comprueba que la tabla exista antes de realizar la consulta.
         * Esto es necesario porque la tabla `retiros` se crea dinámicamente
         * cuando se registra el primer retiro, no durante la instalación inicial.
         * 
         * Si no existe la tabla se devuelve un array vacío sin generar errores.
         */
        $tablaExiste = $pdo->query("SHOW TABLES LIKE 'retiros'")->rowCount() > 0;
        if (!$tablaExiste) {
            echo json_encode([]);
            exit;
        }

        // Filtro por fecha
        $condiciones = [];
        $parametros = [];
        if (isset($_GET['filtroFecha'])) {
            switch ($_GET['filtroFecha']) {
                case 'hoy':
                    $condiciones[] = "DATE(r.fecha) = CURDATE()";
                    break;
                case '7dias':
                    $condiciones[] = "DATE(r.fecha) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
                    break;
                case '30dias':
                    $condiciones[] = "DATE(r.fecha) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
                    break;
            }
        }

        $sql = "
            SELECT r.id, r.idCajaSesion, r.idUsuario, r.importe, r.motivo, r.fecha,
                   u.nombre as usuario_nombre,
                   cs.fechaApertura as caja_fecha_apertura
            FROM retiros r
            LEFT JOIN usuarios u ON r.idUsuario = u.id
            LEFT JOIN caja_sesiones cs ON r.idCajaSesion = cs.id
        ";

        if (!empty($condiciones)) {
            $sql .= " WHERE " . implode(" AND ", $condiciones) . " ";
        }

        $sql .= " ORDER BY $orderBy ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $retiros = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (isset($_GET['debug'])) {
            $totalEnTabla = $pdo->query("SELECT COUNT(*) FROM retiros")->fetchColumn();
            echo json_encode(['retiros' => $retiros, 'debug' => ['total_tabla' => $totalEnTabla, 'filtros' => $condiciones]]);
        } else {
            echo json_encode($retiros);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'Error al obtener retiros: ' . $e->getMessage()]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'Error al obtener retiros: ' . $e->getMessage()]);
    }
    exit;
}

http_response_code(405);
echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
