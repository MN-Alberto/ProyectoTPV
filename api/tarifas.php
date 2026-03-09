<?php
/**
 * API para gestionar las tarifas prefijadas
 * @author Alberto Méndez
 * @version 1.0 (09/03/2026)
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once(__DIR__ . '/../config/confDB.php');
require_once(__DIR__ . '/../core/conexionDB.php');

header('Content-Type: application/json; charset=utf-8');

try {
    // Obtener todas las tarifas prefijadas
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_GET['id']) && !isset($_GET['eliminar'])) {
        $conexion = ConexionDB::getInstancia()->getConexion();
        $stmt = $conexion->query("SELECT * FROM tarifas_prefijadas WHERE activo = 1 ORDER BY orden");
        $tarifas = $stmt->fetchAll();
        echo json_encode($tarifas);
        exit;
    }

    // Obtener una tarifa específica
    if (isset($_GET['id'])) {
        $conexion = ConexionDB::getInstancia()->getConexion();
        $stmt = $conexion->prepare("SELECT * FROM tarifas_prefijadas WHERE id = :id");
        $stmt->bindParam(':id', $_GET['id'], PDO::PARAM_INT);
        $stmt->execute();
        $tarifa = $stmt->fetch();

        if ($tarifa) {
            echo json_encode($tarifa);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Tarifa no encontrada']);
        }
        exit;
    }

    // Crear nueva tarifa
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['editar'])) {
        $nombre = $_POST['nombre'] ?? '';
        $descripcion = $_POST['descripcion'] ?? '';
        $descuento_porcentaje = $_POST['descuento_porcentaje'] ?? 0;
        $requiere_cliente = isset($_POST['requiere_cliente']) && $_POST['requiere_cliente'] === '1' ? 1 : 0;
        $orden = $_POST['orden'] ?? 0;

        if (empty($nombre)) {
            http_response_code(400);
            echo json_encode(['error' => 'El nombre es obligatorio']);
            exit;
        }

        $conexion = ConexionDB::getInstancia()->getConexion();
        $stmt = $conexion->prepare(
            "INSERT INTO tarifas_prefijadas (nombre, descripcion, descuento_porcentaje, requiere_cliente, orden) 
             VALUES (:nombre, :descripcion, :descuento_porcentaje, :requiere_cliente, :orden)"
        );
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':descripcion', $descripcion);
        $stmt->bindParam(':descuento_porcentaje', $descuento_porcentaje);
        $stmt->bindParam(':requiere_cliente', $requiere_cliente, PDO::PARAM_BOOL);
        $stmt->bindParam(':orden', $orden, PDO::PARAM_INT);

        if ($stmt->execute()) {
            echo json_encode(['ok' => true, 'id' => $conexion->lastInsertId()]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error al guardar la tarifa']);
        }
        exit;
    }

    // Editar tarifa existente
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar'])) {
        $id = $_POST['editar'];
        $nombre = $_POST['nombre'] ?? '';
        $descripcion = $_POST['descripcion'] ?? '';
        $descuento_porcentaje = $_POST['descuento_porcentaje'] ?? 0;
        $requiere_cliente = isset($_POST['requiere_cliente']) && $_POST['requiere_cliente'] === '1' ? 1 : 0;
        $orden = $_POST['orden'] ?? 0;

        if (empty($nombre)) {
            http_response_code(400);
            echo json_encode(['error' => 'El nombre es obligatorio']);
            exit;
        }

        $conexion = ConexionDB::getInstancia()->getConexion();

        // Verificar si existe otra tarifa con el mismo nombre (excluyendo la actual)
        $stmt = $conexion->prepare("SELECT id FROM tarifas_prefijadas WHERE nombre = :nombre AND id != :id");
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['error' => 'Ya existe una tarifa con ese nombre']);
            exit;
        }

        $stmt = $conexion->prepare(
            "UPDATE tarifas_prefijadas 
             SET nombre = :nombre, descripcion = :descripcion, descuento_porcentaje = :descuento_porcentaje, 
                 requiere_cliente = :requiere_cliente, orden = :orden 
             WHERE id = :id"
        );
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':descripcion', $descripcion);
        $stmt->bindParam(':descuento_porcentaje', $descuento_porcentaje);
        $stmt->bindParam(':requiere_cliente', $requiere_cliente, PDO::PARAM_BOOL);
        $stmt->bindParam(':orden', $orden, PDO::PARAM_INT);

        if ($stmt->execute()) {
            echo json_encode(['ok' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error al actualizar la tarifa']);
        }
        exit;
    }

    // Eliminar tarifa (marcar como inactiva)
    if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && isset($_GET['eliminar'])) {
        $id = $_GET['eliminar'];

        $conexion = ConexionDB::getInstancia()->getConexion();
        $stmt = $conexion->prepare("UPDATE tarifas_prefijadas SET activo = 0 WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            echo json_encode(['ok' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error al eliminar la tarifa']);
        }
        exit;
    }

    // Si ninguna condición coincide
    http_response_code(400);
    echo json_encode(['error' => 'Solicitud no válida']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>