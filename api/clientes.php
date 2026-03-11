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

    /**
     * Valida un DNI español (8 dígitos + 1 letra).
     * La letra se calcula dividiendo el número entre 23 y cogiendo el resto.
     * @param string $dni El DNI a validar
     * @return bool True si el DNI es válido
     */
    function validarDNI($dni)
    {
        // Verificar formato: 8 dígitos + 1 letra
        if (!preg_match('/^\\d{8}[A-Z]$/', strtoupper($dni))) {
            return false;
        }

        // Extraer números y letra
        $dni = strtoupper($dni);
        $numeros = intval(substr($dni, 0, 8));
        $letra = substr($dni, 8, 1);

        // Calcular la letra correcta según el algoritmo español
        $letras = 'TRWAGMYFPDXBNJZSQVHLCKE';
        $letraCorrecta = $letras[$numeros % 23];

        return $letra === $letraCorrecta;
    }

    // Manejar POST para crear nuevo cliente
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $dni = strtoupper(trim($_POST['dni'] ?? ''));
        $nombre = $_POST['nombre'] ?? '';
        $apellidos = $_POST['apellidos'] ?? '';
        $fecha_alta = $_POST['fecha_alta'] ?? date('Y-m-d');

        if (empty($dni) || empty($nombre) || empty($apellidos)) {
            http_response_code(400);
            echo json_encode(['error' => 'DNI, nombre y apellidos son obligatorios']);
            exit;
        }

        // Validar formato DNI (8 dígitos + letra válida)
        if (!validarDNI($dni)) {
            http_response_code(400);
            echo json_encode(['error' => 'El DNI debe tener 8 dígitos seguidos de una letra válida (ejemplo: 12345678A)']);
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
        $dni = strtoupper(trim($_PUT['dni'] ?? ''));
        $nombre = $_PUT['nombre'] ?? '';
        $apellidos = $_PUT['apellidos'] ?? '';
        $fecha_alta = $_PUT['fecha_alta'] ?? '';

        if (empty($id) || empty($dni) || empty($nombre) || empty($apellidos)) {
            http_response_code(400);
            echo json_encode(['error' => 'Todos los campos son obligatorios']);
            exit;
        }

        // Validar formato DNI (8 dígitos + letra válida)
        if (!validarDNI($dni)) {
            http_response_code(400);
            echo json_encode(['error' => 'El DNI debe tener 8 dígitos seguidos de una letra válida (ejemplo: 12345678A)']);
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

    // Manejar GET para obtener las compras de un cliente por DNI (debe estar ANTES de la verificación por DNI)
    if (isset($_GET['compras']) && isset($_GET['dni'])) {
        $dni = $_GET['dni'];

        // Primero verificar que el cliente existe
        $stmt = $pdo->prepare("SELECT id FROM clientes WHERE dni = ? AND activo = 1");
        $stmt->execute([$dni]);
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$cliente) {
            http_response_code(404);
            echo json_encode(['error' => 'Cliente no encontrado']);
            exit;
        }

        // Obtener las ventas del cliente
        $stmt = $pdo->prepare("
            SELECT v.*, u.nombre as usuario_nombre 
            FROM ventas v 
            LEFT JOIN usuarios u ON v.idUsuario = u.id 
            WHERE v.cliente_dni = ? AND v.estado = 'completada' 
            ORDER BY v.fecha DESC
        ");
        $stmt->execute([$dni]);
        $ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Para cada venta, obtener las líneas de productos
        foreach ($ventas as &$venta) {
            $stmtLineas = $pdo->prepare("
                SELECT lv.*, p.nombre as producto_nombre, p.imagen as producto_imagen
                FROM lineasVenta lv
                JOIN productos p ON lv.idProducto = p.id
                WHERE lv.idVenta = ?
            ");
            $stmtLineas->execute([$venta['id']]);
            $lineas = $stmtLineas->fetchAll(PDO::FETCH_ASSOC);

            // Calcular precio con IVA para cada línea
            foreach ($lineas as &$linea) {
                $iva = isset($linea['iva']) ? floatval($linea['iva']) : 21;
                $precioBase = floatval($linea['precioUnitario']);
                $precioConIva = $precioBase * (1 + $iva / 100);
                $linea['precioUnitarioConIva'] = round($precioConIva, 2);
                $linea['subtotalConIva'] = round($precioConIva * $linea['cantidad'], 2);
            }

            $venta['lineas'] = $lineas;
        }

        echo json_encode($ventas);
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