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

// Función para convertir nombre de tarifa a nombre de columna
function obtenerNombreColumnaTarifa($nombreTarifa)
{
    // Convertir a minúsculas
    $nombre = strtolower($nombreTarifa);
    // Reemplazar espacios y caracteres especiales
    $nombre = preg_replace('/[^a-zA-Z0-9]/', '', $nombre);
    // Añadir prefijo precio_
    return 'precio_' . $nombre;
}

// Función para actualizar precios de productos según la tarifa
function actualizarPreciosProductosPorTarifa($conexion, $idTarifa, $columnName, $descuentoPorcentaje, $sobreescribirManuales = false)
{
    // Obtener todos los productos activos
    $stmt = $conexion->query("SELECT id, precio FROM productos WHERE activo = 1");
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Actualizar el precio para cada producto en la columna de productos
    foreach ($productos as $producto) {
        $idProducto = $producto['id'];
        $precioBase = floatval($producto['precio']);
        $nuevoPrecio = round($precioBase * (1 - $descuentoPorcentaje / 100), 2);

        // Actualizar el precio en la columna de tarifa
        $upsertStmt = $conexion->prepare("UPDATE productos SET `$columnName` = :precio WHERE id = :id");
        $upsertStmt->execute([
            ':precio' => $nuevoPrecio,
            ':id' => $idProducto
        ]);

        // Guardar en historial de precios
        try {
            $pdoHistorial = new PDO(RUTA, USUARIO, PASS);
            $stmtHistorial = $pdoHistorial->prepare("INSERT INTO productos_historial_precios (id_producto, precio, id_tarifa, usuario_id) VALUES (:id_producto, :precio, :id_tarifa, :usuario_id)");
            $adminId = $_SESSION['id'] ?? null;
            $stmtHistorial->execute([
                ':id_producto' => $idProducto,
                ':precio' => $nuevoPrecio,
                ':id_tarifa' => $idTarifa,
                ':usuario_id' => $adminId
            ]);
        } catch (Exception $e) {
            // Si falla el historial, continuamos
        }
    }
}

// Función para añadir columna de tarifa a la tabla productos
function anadirColumnaTarifaProductos($conexion, $idTarifa, $nombreTarifa, $descuentoPorcentaje)
{
    $columnName = obtenerNombreColumnaTarifa($nombreTarifa);

    // Añadir la columna a la tabla productos
    $alterStmt = $conexion->prepare("ALTER TABLE productos ADD COLUMN `$columnName` DECIMAL(10, 2) NULL AFTER activo");
    $alterStmt->execute();

    // Poblar la columna con los precios calculados
    actualizarPreciosProductosPorTarifa($conexion, $idTarifa, $columnName, $descuentoPorcentaje);
}

// Función para eliminar columna de tarifa de la tabla productos
function eliminarColumnaTarifaProductos($conexion, $nombreTarifa)
{
    $columnName = obtenerNombreColumnaTarifa($nombreTarifa);

    // Eliminar la columna de la tabla productos
    $alterStmt = $conexion->prepare("ALTER TABLE productos DROP COLUMN `$columnName`");
    $alterStmt->execute();
}

// Función para detectar si hay precios manuales en una tarifa (precios diferentes a los calculados)
function detectarPreciosManuales($conexion, $idTarifa, $nombreTarifa)
{
    $columnName = obtenerNombreColumnaTarifa($nombreTarifa);

    // Obtener el descuento de la tarifa
    $stmtTarifa = $conexion->prepare("SELECT descuento_porcentaje FROM tarifas_prefijadas WHERE id = :id");
    $stmtTarifa->bindParam(':id', $idTarifa, PDO::PARAM_INT);
    $stmtTarifa->execute();
    $tarifa = $stmtTarifa->fetch(PDO::FETCH_ASSOC);

    if (!$tarifa)
        return [];

    $descuento = floatval($tarifa['descuento_porcentaje']);

    // Obtener productos que tienen precio en esa columna
    $sql = "
        SELECT p.id, p.nombre, p.precio, p.$columnName as precio_tarifa
        FROM productos p
        WHERE p.$columnName IS NOT NULL
    ";

    $stmt = $conexion->query($sql);
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $manuales = [];
    foreach ($productos as $prod) {
        $precioBase = floatval($prod['precio']);
        $precioTarifa = floatval($prod['precio_tarifa']);
        $precioCalculado = round($precioBase * (1 - $descuento / 100), 2);

        // Si el precio almacenado es diferente del calculado, es manual
        if (abs($precioTarifa - $precioCalculado) > 0.01) {
            $manuales[] = [
                'id' => $prod['id'],
                'nombre' => $prod['nombre'],
                'precio' => $precioTarifa
            ];
        }
    }

    return $manuales;
}

header('Content-Type: application/json; charset=utf-8');

try {
    // Obtener todas las tarifas prefijadas
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_GET['id']) && !isset($_GET['eliminar']) && !isset($_GET['detectarManuales'])) {
        $conexion = ConexionDB::getInstancia()->getConexion();
        $stmt = $conexion->query("SELECT * FROM tarifas_prefijadas WHERE activo = 1 ORDER BY orden");
        $tarifas = $stmt->fetchAll();
        echo json_encode($tarifas);
        exit;
    }

    // Detectar precios manuales para una tarifa (comparando precios almacenados vs calculados)
    if (isset($_GET['detectarManuales'])) {
        $idTarifa = intval($_GET['detectarManuales']);
        $conexion = ConexionDB::getInstancia()->getConexion();

        // Primero obtener el nombre de la tarifa
        $stmtTarifa = $conexion->prepare("SELECT nombre FROM tarifas_prefijadas WHERE id = :id");
        $stmtTarifa->bindParam(':id', $idTarifa, PDO::PARAM_INT);
        $stmtTarifa->execute();
        $tarifa = $stmtTarifa->fetch(PDO::FETCH_ASSOC);

        if ($tarifa) {
            // Usar la nueva función para detectar precios manuales
            $manuales = detectarPreciosManuales($conexion, $idTarifa, $tarifa['nombre']);
            echo json_encode(['ok' => true, 'manuales' => $manuales]);
        } else {
            echo json_encode(['ok' => true, 'manuales' => []]);
        }
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

    // Actualizar un precio individual de un producto en una tarifa
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizarPrecioIndividual'])) {
        $idTarifa = intval($_POST['idTarifa']);
        $idProducto = intval($_POST['idProducto']);
        $precio = floatval($_POST['precio']);
        $esManual = isset($_POST['esManual']) ? (intval($_POST['esManual']) === 1 ? 1 : 0) : 1;

        if ($idTarifa <= 0 || $idProducto <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'ID de tarifa o producto inválido']);
            exit;
        }

        $conexion = ConexionDB::getInstancia()->getConexion();

        // Obtener el nombre de la tarifa para actualizar la columna
        $stmtTarifa = $conexion->prepare("SELECT nombre FROM tarifas_prefijadas WHERE id = :id");
        $stmtTarifa->bindParam(':id', $idTarifa, PDO::PARAM_INT);
        $stmtTarifa->execute();
        $tarifa = $stmtTarifa->fetch(PDO::FETCH_ASSOC);

        if ($tarifa) {
            $columnName = obtenerNombreColumnaTarifa($tarifa['nombre']);

            // Obtener el nombre del producto y el precio anterior
            $stmtProducto = $conexion->prepare("SELECT nombre, `$columnName` as precio_anterior FROM productos WHERE id = :id");
            $stmtProducto->bindParam(':id', $idProducto, PDO::PARAM_INT);
            $stmtProducto->execute();
            $producto = $stmtProducto->fetch(PDO::FETCH_ASSOC);
            $precioAnterior = $producto ? floatval($producto['precio_anterior']) : 0;
            $nombreProducto = $producto ? $producto['nombre'] : 'Producto Desconocido';

            // Actualizar el precio
            $stmt = $conexion->prepare("UPDATE productos SET `$columnName` = :precio WHERE id = :id");
            $stmt->bindParam(':precio', $precio);
            $stmt->bindParam(':id', $idProducto, PDO::PARAM_INT);
            $stmt->execute();

            // Guardar en historial de precios
            try {
                $pdoHistorial = new PDO(RUTA, USUARIO, PASS);
                $adminId = $_SESSION['id'] ?? null;
                $stmtHistorial = $pdoHistorial->prepare("INSERT INTO productos_historial_precios (id_producto, precio, id_tarifa, usuario_id) VALUES (:id_producto, :precio, :id_tarifa, :usuario_id)");
                $stmtHistorial->execute([
                    ':id_producto' => $idProducto,
                    ':precio' => $precio,
                    ':id_tarifa' => $idTarifa,
                    ':usuario_id' => $adminId
                ]);
            } catch (Exception $e) {
                // Si falla el historial, continuamos
            }

            // Registrar el log del cambio de precio
            try {
                $pdoLog = new PDO(RUTA, USUARIO, PASS);
                $pdoLog->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                $adminId = $_SESSION['id'] ?? null;
                $adminNombre = $_SESSION['nombre'] ?? 'Admin';

                $descripcionLog = "Precio de tarifa '$tarifa[nombre]' actualizado para producto '$nombreProducto'";
                $detallesLog = json_encode([
                    'producto_id' => $idProducto,
                    'producto_nombre' => $nombreProducto,
                    'tarifa_id' => $idTarifa,
                    'tarifa_nombre' => $tarifa['nombre'],
                    'precio_anterior' => $precioAnterior,
                    'precio_nuevo' => $precio
                ]);

                $stmtLog = $pdoLog->prepare("INSERT INTO logs_sistema (tipo, usuario_id, usuario_nombre, descripcion, detalles) VALUES ('modificacion_precio_tarifa', :usuario_id, :usuario_nombre, :descripcion, :detalles)");
                $stmtLog->execute([
                    ':usuario_id' => $adminId,
                    ':usuario_nombre' => $adminNombre,
                    ':descripcion' => $descripcionLog,
                    ':detalles' => $detallesLog
                ]);
            } catch (Exception $e) {
                // Si falla el log, continuamos sin interrumpir
            }

            echo json_encode(['ok' => true]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Tarifa no encontrada']);
        }
        exit;
    }

    // Crear nueva tarifa
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['editar']) && !isset($_POST['actualizarPrecioIndividual'])) {
        $nombre = $_POST['nombre'] ?? '';
        $descripcion = $_POST['descripcion'] ?? '';
        $descuento_porcentaje = (float) ($_POST['descuento_porcentaje'] ?? 0);
        $requiere_cliente = isset($_POST['requiere_cliente']) && $_POST['requiere_cliente'] === '1' ? 1 : 0;
        $orden = $_POST['orden'] ?? 0;

        if (empty($nombre)) {
            http_response_code(400);
            echo json_encode(['error' => 'El nombre es obligatorio']);
            exit;
        }

        // Validar que el descuento no sea negativo
        if ($descuento_porcentaje < 0) {
            http_response_code(400);
            echo json_encode(['error' => 'El descuento no puede ser negativo']);
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
            $idTarifa = $conexion->lastInsertId();
            // Añadir columna de tarifa a la tabla productos y populate los precios
            anadirColumnaTarifaProductos($conexion, $idTarifa, $nombre, $descuento_porcentaje);

            echo json_encode(['ok' => true, 'id' => $idTarifa]);
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
        $descuento_porcentaje = (float) ($_POST['descuento_porcentaje'] ?? 0);
        $requiere_cliente = isset($_POST['requiere_cliente']) && $_POST['requiere_cliente'] === '1' ? 1 : 0;
        $orden = $_POST['orden'] ?? 0;

        if (empty($nombre)) {
            http_response_code(400);
            echo json_encode(['error' => 'El nombre es obligatorio']);
            exit;
        }

        // Validar que el descuento no sea negativo
        if ($descuento_porcentaje < 0) {
            http_response_code(400);
            echo json_encode(['error' => 'El descuento no puede ser negativo']);
            exit;
        }

        $conexion = ConexionDB::getInstancia()->getConexion();

        // Obtener el nombre actual de la tarifa antes de actualizar
        $checkStmt = $conexion->prepare("SELECT nombre FROM tarifas_prefijadas WHERE id = :id");
        $checkStmt->bindParam(':id', $id, PDO::PARAM_INT);
        $checkStmt->execute();
        $tarifaActual = $checkStmt->fetch(PDO::FETCH_ASSOC);
        $nombreAnterior = $tarifaActual['nombre'] ?? '';

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
            // Si el nombre cambió, renombrar la columna
            if ($nombreAnterior && $nombre !== $nombreAnterior) {
                $columnNameAnterior = obtenerNombreColumnaTarifa($nombreAnterior);
                $columnNameNuevo = obtenerNombreColumnaTarifa($nombre);

                // Renombrar la columna
                $renameStmt = $conexion->prepare("ALTER TABLE productos CHANGE COLUMN `$columnNameAnterior` `$columnNameNuevo` DECIMAL(10, 2) NULL");
                $renameStmt->execute();
            }

            // Verificar si se deben recalcular los precios o mantener los existentes
            $sobreescribir = isset($_POST['sobreescribirManuales']) && $_POST['sobreescribirManuales'] === '1';

            // Actualizar precios en la columna solo si se eligió sobreescribir
            if ($sobreescribir) {
                $columnName = obtenerNombreColumnaTarifa($nombre);
                actualizarPreciosProductosPorTarifa($conexion, $id, $columnName, $descuento_porcentaje);
            }

            echo json_encode(['ok' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error al actualizar la tarifa']);
        }
        exit;
    }

    // Eliminar tarifa (borrado físico)
    if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && isset($_GET['eliminar'])) {
        $id = intval($_GET['eliminar']);

        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'ID de tarifa inválido']);
            exit;
        }

        try {
            $conexion = ConexionDB::getInstancia()->getConexion();

            // Verificar que existe la tarifa
            $checkStmt = $conexion->prepare("SELECT id, nombre FROM tarifas_prefijadas WHERE id = :id");
            $checkStmt->bindParam(':id', $id, PDO::PARAM_INT);
            $checkStmt->execute();
            $tarifa = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if (!$tarifa) {
                http_response_code(404);
                echo json_encode(['error' => 'Tarifa no encontrada']);
                exit;
            }

            // Primero eliminar la columna de tarifa de la tabla productos usando el nombre
            eliminarColumnaTarifaProductos($conexion, $tarifa['nombre']);

            // Borrar físicamente la tarifa
            $stmt = $conexion->prepare("DELETE FROM tarifas_prefijadas WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $result = $stmt->execute();

            $rowCount = $stmt->rowCount();

            if ($result && $rowCount > 0) {
                echo json_encode(['ok' => true, 'message' => 'Tarifa eliminada']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'No se pudo eliminar la tarifa']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Error al eliminar la tarifa: ' . $e->getMessage()]);
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