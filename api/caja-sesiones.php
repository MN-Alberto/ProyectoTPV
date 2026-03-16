<?php
/**
 * API para gestionar las sesiones de caja.
 * 
 * @author Alberto Méndez
 * @version 1.0 (04/03/2026)
 */

session_start();
require_once(__DIR__ . '/../config/confDB.php');
require_once(__DIR__ . '/../model/Caja.php');

header('Content-Type: application/json; charset=utf-8');

// Verificar si el usuario es administrador
if (!isset($_SESSION['rolUsuario']) || $_SESSION['rolUsuario'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Acceso denegado']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

// GET: Obtener sesiones o datos de arqueo
if ($method === 'GET') {
    // Verificar si es una solicitud de arqueo
    if (isset($_GET['arqueo']) && isset($_GET['idSesion'])) {
        obtenerDatosArqueo($_GET['idSesion']);
        exit;
    }

    // Verificar si es una solicitud de último arqueo
    if (isset($_GET['ultimoArqueo']) && isset($_GET['idSesion'])) {
        obtenerUltimoArqueo($_GET['idSesion']);
        exit;
    }

    $orden = $_GET['orden'] ?? 'fecha_desc';

    // Validar orden
    $ordenesValidos = ['fecha_desc', 'fecha_asc'];
    if (!in_array($orden, $ordenesValidos)) {
        $orden = 'fecha_desc';
    }

    try {
        $pdo = new PDO(RUTA, USUARIO, PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Verificar si la tabla existe
        $tablaExiste = $pdo->query("SHOW TABLES LIKE 'caja_sesiones'")->rowCount() > 0;
        if (!$tablaExiste) {
            echo json_encode([]);
            exit;
        }

        // Ordenar por fecha
        $orderBy = 'cs.fechaApertura DESC';
        if ($orden === 'fecha_asc') {
            $orderBy = 'cs.fechaApertura ASC';
        }

        // Filtro por fecha
        $condiciones = [];
        if (isset($_GET['filtroFecha'])) {
            switch ($_GET['filtroFecha']) {
                case 'hoy':
                    $condiciones[] = "DATE(cs.fechaApertura) = CURDATE()";
                    break;
                case '7dias':
                    $condiciones[] = "cs.fechaApertura >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                    break;
                case '30dias':
                    $condiciones[] = "cs.fechaApertura >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                    break;
            }
        }

        $sql = "
            SELECT cs.id, cs.idUsuario, cs.fechaApertura, cs.fechaCierre, 
                   cs.importeInicial, cs.importeActual, cs.cambio, cs.estado,
                   u.nombre as usuario_nombre,
                   COALESCE((SELECT SUM(importe) FROM retiros WHERE idCajaSesion = cs.id), 0) as total_retiros,
                   COALESCE((SELECT SUM(importeTotal) FROM devoluciones WHERE idSesionCaja = cs.id OR (idSesionCaja IS NULL AND fecha >= cs.fechaApertura AND (cs.fechaCierre IS NULL OR fecha <= cs.fechaCierre))), 0) as total_devoluciones,
                   COALESCE((SELECT SUM(lv.cantidad) FROM ventas v 
                             JOIN lineasVenta lv ON v.id = lv.idVenta 
                             WHERE v.idSesionCaja = cs.id OR (v.idSesionCaja IS NULL AND v.fecha >= cs.fechaApertura AND (cs.fechaCierre IS NULL OR v.fecha <= cs.fechaCierre))), 0) as total_productos,
                   COALESCE((SELECT COUNT(*) FROM ventas v 
                             WHERE v.idSesionCaja = cs.id OR (v.idSesionCaja IS NULL AND v.fecha >= cs.fechaApertura AND (cs.fechaCierre IS NULL OR v.fecha <= cs.fechaCierre))), 0) as total_ventas,
                   (SELECT diferencia FROM arqueos_caja WHERE idCajaSesion = cs.id AND tipoArqueo = 'cierre' ORDER BY fechaArqueo DESC LIMIT 1) as diferencia,
                   (SELECT efectivoContado FROM arqueos_caja WHERE idCajaSesion = cs.id AND tipoArqueo = 'cierre' ORDER BY fechaArqueo DESC LIMIT 1) as efectivoContado
            FROM caja_sesiones cs
            LEFT JOIN usuarios u ON cs.idUsuario = u.id
        ";

        if (!empty($condiciones)) {
            $sql .= " WHERE " . implode(" AND ", $condiciones) . " ";
        }

        $sql .= " ORDER BY $orderBy ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $sesiones = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($sesiones);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'Error al obtener sesiones de caja']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'Error al obtener sesiones de caja']);
    }
    exit;
}

// POST: Registrar arqueo
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (isset($input['accion']) && $input['accion'] === 'registrarArqueo') {
        registrarArqueo($input);
        exit;
    }
}

http_response_code(405);
echo json_encode(['ok' => false, 'error' => 'Método no permitido']);

// ======================== FUNCIONES ========================

function obtenerDatosArqueo($idSesion)
{
    try {
        $caja = new Caja();
        $caja->setId($idSesion);
        $datos = $caja->getDatosArqueo();
        echo json_encode(['ok' => true, 'datos' => $datos]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'Error al obtener datos del arqueo']);
    }
}

function obtenerUltimoArqueo($idSesion)
{
    try {
        $caja = new Caja();
        $caja->setId($idSesion);
        $arqueo = $caja->getUltimoArqueo();
        echo json_encode(['ok' => true, 'arqueo' => $arqueo]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'Error al obtener el último arqueo']);
    }
}

function registrarArqueo($input)
{
    $idSesion = $input['idSesion'] ?? null;
    $efectivoContado = $input['efectivoContado'] ?? 0;
    $detalleConteo = $input['detalleConteo'] ?? null;
    $observaciones = $input['observaciones'] ?? null;
    $tipoArqueo = $input['tipoArqueo'] ?? 'cierre';
    $idUsuario = $_SESSION['idUsuario'] ?? null;

    if (!$idSesion || !$idUsuario) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Faltan datos requeridos']);
        return;
    }

    try {
        $caja = new Caja();
        $caja->setId($idSesion);

        // Convertir detalleConteo a JSON si es un array
        $detalleJson = null;
        if (is_array($detalleConteo)) {
            $detalleJson = json_encode($detalleConteo, JSON_UNESCAPED_UNICODE);
        }

        $resultado = $caja->registrarArqueo(
            $idUsuario,
            (float) $efectivoContado,
            $detalleJson,
            $observaciones,
            $tipoArqueo
        );

        if ($resultado) {
            echo json_encode(['ok' => true, 'mensaje' => 'Arqueo registrado correctamente']);
        } else {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => 'Error al registrar el arqueo']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'Error al registrar el arqueo: ' . $e->getMessage()]);
    }
}
