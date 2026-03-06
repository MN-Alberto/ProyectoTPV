<?php
/**
 * API para gestionar las sesiones de caja.
 * 
 * @author Alberto Méndez
 * @version 1.0 (04/03/2026)
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

if ($method === 'GET') {
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

        $sql = "
            SELECT cs.id, cs.idUsuario, cs.fechaApertura, cs.fechaCierre, 
                   cs.importeInicial, cs.importeActual, cs.cambio, cs.estado,
                   u.nombre as usuario_nombre,
                   COALESCE((SELECT SUM(importe) FROM retiros WHERE idCajaSesion = cs.id), 0) as total_retiros,
                   COALESCE((SELECT SUM(importeTotal) FROM devoluciones WHERE idSesionCaja = cs.id), 0) as total_devoluciones,
                   COALESCE((SELECT SUM(lv.cantidad) FROM ventas v 
                             JOIN lineasVenta lv ON v.id = lv.idVenta 
                             WHERE v.fecha >= cs.fechaApertura 
                             AND (cs.fechaCierre IS NULL OR v.fecha <= cs.fechaCierre)), 0) as total_productos,
                   COALESCE((SELECT COUNT(*) FROM ventas v 
                             WHERE v.fecha >= cs.fechaApertura 
                             AND (cs.fechaCierre IS NULL OR v.fecha <= cs.fechaCierre)), 0) as total_ventas
            FROM caja_sesiones cs
            LEFT JOIN usuarios u ON cs.idUsuario = u.id
            ORDER BY $orderBy
        ";

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

http_response_code(405);
echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
