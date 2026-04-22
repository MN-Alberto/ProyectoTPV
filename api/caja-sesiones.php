<?php
/**
 * API de Gestión de Sesiones de Caja y Arqueos.
 * Permite la administración integral de los turnos de trabajo, incluyendo la apertura,
 * el seguimiento de movimientos de efectivo y la realización de arqueos de cierre.
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

/** 
 * MANEJADOR DE SOLICITUDES GET
 * Recupera listados de sesiones, filtros históricos o cálculos de arqueo específicos.
 */
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
                   COALESCE(v.qty, 0) as totalVentas,
                   COALESCE(p.qty, 0) as totalProductos,
                   COALESCE(r.total, 0) as totalRetiros,
                   COALESCE(d.total, 0) as totalDevoluciones,
                   COALESCE(ac.diferencia, 0) as desajuste,
                   COALESCE(ac.efectivoContado, 0) as efectivoContado,
                   COALESCE(u2.nombre, '') as usuario_cierre_nombre
            FROM caja_sesiones cs
            LEFT JOIN usuarios u ON cs.idUsuario = u.id
            LEFT JOIN (
                SELECT idSesionCaja, SUM(qty) as qty FROM (
                    SELECT idSesionCaja, COUNT(*) as qty FROM tickets WHERE idSesionCaja IS NOT NULL GROUP BY idSesionCaja
                    UNION ALL
                    SELECT idSesionCaja, COUNT(*) as qty FROM facturas WHERE idSesionCaja IS NOT NULL GROUP BY idSesionCaja
                ) vsub GROUP BY idSesionCaja
            ) v ON v.idSesionCaja = cs.id
            LEFT JOIN (
                SELECT idSesionCaja, SUM(qty) as qty FROM (
                    SELECT t.idSesionCaja, SUM(lv.cantidad) as qty FROM lineasVenta lv JOIN tickets t ON lv.idVenta = t.id WHERE t.idSesionCaja IS NOT NULL GROUP BY t.idSesionCaja
                    UNION ALL
                    SELECT f.idSesionCaja, SUM(lv.cantidad) as qty FROM lineasVenta lv JOIN facturas f ON lv.idVenta = f.id WHERE f.idSesionCaja IS NOT NULL GROUP BY f.idSesionCaja
                ) psub GROUP BY idSesionCaja
            ) p ON p.idSesionCaja = cs.id
            LEFT JOIN (SELECT idCajaSesion, SUM(importe) as total FROM retiros GROUP BY idCajaSesion) r ON r.idCajaSesion = cs.id
            LEFT JOIN (SELECT idSesionCaja, SUM(importeTotal) as total FROM devoluciones GROUP BY idSesionCaja) d ON d.idSesionCaja = cs.id
            LEFT JOIN (
                SELECT idCajaSesion, diferencia, efectivoContado, idUsuario 
                FROM arqueos_caja 
                WHERE tipoArqueo = 'cierre' 
                GROUP BY idCajaSesion 
                ORDER BY fechaArqueo DESC
            ) ac ON ac.idCajaSesion = cs.id
            LEFT JOIN usuarios u2 ON ac.idUsuario = u2.id
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

/** 
 * MANEJADOR DE SOLICITUDES POST
 * Procesa el registro persistente de nuevos arqueos y cierres de sesión.
 */
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

/**
 * Recupera el balance teórico de la caja en un momento dado.
 * Calcula lo que "debería haber" basándose en ventas, retiros y fondo inicial.
 * 
 * @param int $idSesion Identificador de la sesión a analizar.
 * @return void Envía una respuesta JSON con el desglose de importes.
 */
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

/**
 * Procesa y almacena el conteo físico de billetes y monedas realizado por un empleado.
 * 
 * @param array $input Datos de la solicitud (idSesion, efectivoContado, detalleConteo, etc.).
 * @return void Envía confirmación o error en formato JSON.
 */
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
