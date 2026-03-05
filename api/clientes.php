<?php
/**
 * API para gestionar los clientes habituales
 * @author Alberto Méndez
 * @version 1.0 (05/03/2026)
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json; charset=utf-8');

try {
    // Conexión a la base de datos
    $pdo = new PDO('mysql:host=localhost;dbname=ProyectoTPV', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET NAMES utf8mb4");

    // Manejar POST para crear nuevo cliente
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $dni = $_POST['dni'] ?? '';
        $nombre = $_POST['nombre'] ?? '';
        $apellidos = $_POST['apellidos'] ?? '';
        $fecha_alta = $_POST['fecha_alta'] ?? date('Y-m-d');

        if (empty($dni) || empty($nombre) || empty($apellidos)) {
            http_response_code(400);
            echo json_encode(['error' => 'DNI, nombre y apellidos son obligatorios']);
            exit;
        }

        // Verificar si el DNI ya existe
        $stmt = $pdo->prepare("SELECT id FROM clientes WHERE dni = ? AND activo = 1");
        $stmt->execute([$dni]);
        if ($stmt->fetch()) {
            http_response_code(409);
            echo json_encode(['error' => 'Ya existe un cliente con este DNI']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO clientes (dni, nombre, apellidos, fecha_alta, productos_comprados, compras_realizadas, activo) VALUES (?, ?, ?, ?, 0, 0, 1)");

        if ($stmt->execute([$dni, $nombre, $apellidos, $fecha_alta])) {
            echo json_encode(['ok' => true, 'id' => $pdo->lastInsertId()]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error al guardar el cliente']);
        }
        exit;
    }

    // Manejar PUT para actualizar cliente
    if ($_SERVER['REQUEST_METHOD'] === 'PUT' && isset($_GET['actualizar'])) {
        parse_str(file_get_contents("php://input"), $_PUT);

        $id = $_PUT['id'] ?? '';
        $dni = $_PUT['dni'] ?? '';
        $nombre = $_PUT['nombre'] ?? '';
        $apellidos = $_PUT['apellidos'] ?? '';
        $fecha_alta = $_PUT['fecha_alta'] ?? '';

        if (empty($id) || empty($dni) || empty($nombre) || empty($apellidos)) {
            http_response_code(400);
            echo json_encode(['error' => 'Todos los campos son obligatorios']);
            exit;
        }

        // Verificar si el DNI ya existe en otro cliente
        $stmt = $pdo->prepare("SELECT id FROM clientes WHERE dni = ? AND id != ? AND activo = 1");
        $stmt->execute([$dni, $id]);
        if ($stmt->fetch()) {
            http_response_code(409);
            echo json_encode(['error' => 'Ya existe otro cliente con este DNI']);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE clientes SET dni = ?, nombre = ?, apellidos = ?, fecha_alta = ? WHERE id = ?");

        if ($stmt->execute([$dni, $nombre, $apellidos, $fecha_alta, $id])) {
            echo json_encode(['ok' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error al actualizar el cliente']);
        }
        exit;
    }

    // Manejar DELETE para eliminar cliente (baja física)
    if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && isset($_GET['eliminar'])) {
        $id = $_GET['eliminar'];

        $stmt = $pdo->prepare("DELETE FROM clientes WHERE id = ?");

        if ($stmt->execute([$id])) {
            echo json_encode(['ok' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error al eliminar el cliente']);
        }
        exit;
    }

    // Manejar PUT para reactivar cliente
    if ($_SERVER['REQUEST_METHOD'] === 'PUT' && isset($_GET['reactivar'])) {
        $id = $_GET['reactivar'];

        $stmt = $pdo->prepare("UPDATE clientes SET activo = 1 WHERE id = ?");

        if ($stmt->execute([$id])) {
            echo json_encode(['ok' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error al activar el cliente']);
        }
        exit;
    }

    // Manejar GET para obtener cliente por DNI (para buscar clientes)
    if (isset($_GET['dni'])) {
        $dni = $_GET['dni'];

        $stmt = $pdo->prepare("SELECT * FROM clientes WHERE dni = ? AND activo = 1");
        $stmt->execute([$dni]);
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($cliente) {
            echo json_encode($cliente);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Cliente no encontrado']);
        }
        exit;
    }

    // Verificar si se pide un cliente específico
    if (isset($_GET['id'])) {
        $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ? AND activo = 1");
        $stmt->execute([$_GET['id']]);
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($cliente) {
            echo json_encode($cliente);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Cliente no encontrado']);
        }
        exit;
    }

    // Obtener todos los clientes activos
    $stmt = $pdo->query("SELECT * FROM clientes WHERE activo = 1 ORDER BY fecha_creacion DESC");
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($clientes);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>