<?php
/**
 * API para gestionar los retiros de caja.
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
    $ordenesValidos = ['fecha_desc', 'fecha_asc', 'importe_desc', 'importe_asc'];
    if (!in_array($orden, $ordenesValidos)) {
        $orden = 'fecha_desc';
    }

    try {
        $pdo = new PDO(RUTA, USUARIO, PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Verificar si la tabla existe
        $tablaExiste = $pdo->query("SHOW TABLES LIKE 'retiros'")->rowCount() > 0;
        if (!$tablaExiste) {
            // La tabla no existe, retornar array vacío
            echo json_encode([]);
            exit;
        }

        // Ordenar por fecha
        $orderBy = 'r.fecha DESC';
        if ($orden === 'fecha_asc') {
            $orderBy = 'r.fecha ASC';
        } elseif ($orden === 'importe_desc') {
            $orderBy = 'r.importe DESC';
        } elseif ($orden === 'importe_asc') {
            $orderBy = 'r.importe ASC';
        }

        $sql = "
            SELECT r.id, r.idCajaSesion, r.idUsuario, r.importe, r.motivo, r.fecha,
                   u.nombre as usuario_nombre,
                   cs.fechaApertura as caja_fecha_apertura
            FROM retiros r
            LEFT JOIN usuarios u ON r.idUsuario = u.id
            LEFT JOIN caja_sesiones cs ON r.idCajaSesion = cs.id
            ORDER BY $orderBy
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $retiros = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($retiros);
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
