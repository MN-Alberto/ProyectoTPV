<?php
/**
 * API para gestionar las ventas 
 * @author Alberto Méndez
 * @version 1.2 (02/03/2026)
 */


// Requerimos los archivos necesarios
require_once(__DIR__ . '/../config/confDB.php');
require_once(__DIR__ . '/../model/Venta.php');

// Establecemos el tipo de contenido de la respuesta, en este caso JSON
header('Content-Type: application/json; charset=utf-8');

// Si se solicita el historial de ventas de la sesión de caja actual
if (isset($_GET['historialCaja'])) {
    try {
        $conexion = ConexionDB::getInstancia()->getConexion();

        // Obtener la sesión de caja activa
        $stmtCaja = $conexion->prepare("SELECT id, fechaApertura FROM caja_sesiones WHERE estado = 'abierta' ORDER BY id DESC LIMIT 1");
        $stmtCaja->execute();
        $caja = $stmtCaja->fetch(PDO::FETCH_ASSOC);

        if (!$caja) {
            // No hay caja abierta
            echo json_encode(['error' => 'No hay sesión de caja abierta']);
            exit();
        }

        $idCaja = $caja['id'];
        $fechaApertura = $caja['fechaApertura'];

        // Obtener las ventas desde la apertura de la caja
        $stmt = $conexion->prepare("
            SELECT v.id, v.fecha, v.total, v.metodoPago as forma_pago, v.tipoDocumento, u.nombre as usuario_nombre,
            (SELECT SUM(lv.cantidad) FROM lineasVenta lv WHERE lv.idVenta = v.id) as cantidad_productos
            FROM ventas v
            LEFT JOIN usuarios u ON v.idUsuario = u.id
            WHERE v.fecha >= ?
            ORDER BY v.fecha DESC
        ");
        $stmt->execute([$fechaApertura]);
        $ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($ventas);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit();
}

// Si se solicita los detalles de una venta específica
if (isset($_GET['detalleVenta'])) {
    try {
        $idVenta = (int) $_GET['detalleVenta'];
        $conexion = ConexionDB::getInstancia()->getConexion();

        // Obtener datos de la venta
        $stmt = $conexion->prepare("
            SELECT v.*, u.nombre as usuario_nombre
            FROM ventas v
            LEFT JOIN usuarios u ON v.idUsuario = u.id
            WHERE v.id = ?
        ");
        $stmt->execute([$idVenta]);
        $venta = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$venta) {
            echo json_encode(['error' => 'Venta no encontrada']);
            exit();
        }

        // Obtener las líneas de venta
        $stmtLineas = $conexion->prepare("
            SELECT lv.*, p.nombre as producto_nombre, i.porcentaje as iva_producto
            FROM lineasVenta lv
            LEFT JOIN productos p ON lv.idProducto = p.id
            LEFT JOIN iva i ON p.idIva = i.id
            WHERE lv.idVenta = ?
        ");
        $stmtLineas->execute([$idVenta]);
        $lineas = $stmtLineas->fetchAll(PDO::FETCH_ASSOC);

        // Añadir campo iva a cada línea (priorizar lv.iva si existe, si no usar iva del producto)
        foreach ($lineas as &$linea) {
            if (!isset($linea['iva']) || $linea['iva'] === null) {
                $linea['iva'] = $linea['iva_producto'] ?? 21;
            }
        }

        // Obtener datos de descuento de la venta (si existen)
        $stmtDescuento = $conexion->prepare("SHOW COLUMNS FROM ventas LIKE 'descuento%'");
        $stmtDescuento->execute();
        $camposDescuento = $stmtDescuento->fetchAll(PDO::FETCH_COLUMN);

        $descuentosVenta = [];
        if (!empty($camposDescuento)) {
            $stmtDesc = $conexion->prepare("SELECT * FROM ventas WHERE id = ?");
            $stmtDesc->execute([$idVenta]);
            $ventaDesc = $stmtDesc->fetch(PDO::FETCH_ASSOC);
            if ($ventaDesc) {
                foreach ($camposDescuento as $campo) {
                    if (isset($ventaDesc[$campo]) && $ventaDesc[$campo] !== '' && $ventaDesc[$campo] !== 'ninguno') {
                        $descuentosVenta[$campo] = $ventaDesc[$campo];
                    }
                }
            }
        }

        echo json_encode([
            'venta' => $venta,
            'lineas' => $lineas,
            'descuentos' => $descuentosVenta
        ]);
        exit();
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
        exit();
    }
}

// Si se solicita verificar una venta para devolución (incluye productos y cantidades ya devueltas)
if (isset($_GET['checkVentaDevolucion'])) {
    try {
        require_once(__DIR__ . '/../model/LineaVenta.php');
        $idVenta = (int) $_GET['checkVentaDevolucion'];

        $conexion = ConexionDB::getInstancia()->getConexion();
        // Obtener datos básicos de la venta
        $stmt = $conexion->prepare("SELECT * FROM ventas WHERE id = ?");
        $stmt->execute([$idVenta]);
        $venta = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$venta) {
            echo json_encode(['error' => 'Ticket no encontrado']);
            exit();
        }

        // Obtener líneas con cantidades devueltas calculadas
        $lineas = LineaVenta::obtenerDetalleParaDevolucion($idVenta);

        echo json_encode([
            'venta' => $venta,
            'lineas' => $lineas
        ]);
        exit();
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
        exit();
    }
}

// Si se solicita todas las ventas (para el panel de admin)
if (isset($_GET['todas']) || isset($_GET['limpiarVentas'])) {
    // Endpoint para limpiar todas las ventas
    if (isset($_GET['limpiarVentas'])) {
        try {
            $conexion = ConexionDB::getInstancia()->getConexion();

            // Iniciar transacción
            $conexion->beginTransaction();

            // Primero eliminar las líneas de venta
            $stmtLineas = $conexion->prepare("DELETE FROM lineasVenta");
            $stmtLineas->execute();

            // Luego eliminar las ventas
            $stmtVentas = $conexion->prepare("DELETE FROM ventas");
            $stmtVentas->execute();

            // Confirmar transacción
            $conexion->commit();

            echo json_encode(['success' => true, 'message' => 'Todas las ventas han sido eliminadas']);
        } catch (Exception $e) {
            $conexion->rollBack();
            http_response_code(500);
            echo json_encode(['error' => 'Error al eliminar ventas: ' . $e->getMessage()]);
        }
        exit();
    }

    // Resto del código para obtener ventas...
    try {
        $conexion = ConexionDB::getInstancia()->getConexion();

        // Construir condiciones de filtro
        $condiciones = [];
        $parametros = [];

        // Filtro por método de pago
        if (isset($_GET['metodoPago']) && $_GET['metodoPago'] !== '' && $_GET['metodoPago'] !== 'todos') {
            $condiciones[] = "v.metodoPago = ?";
            $parametros[] = $_GET['metodoPago'];
        }

        // Filtro por tipo de documento
        if (isset($_GET['tipoDocumento']) && $_GET['tipoDocumento'] !== '' && $_GET['tipoDocumento'] !== 'todos') {
            $condiciones[] = "v.tipoDocumento = ?";
            $parametros[] = $_GET['tipoDocumento'];
        }

        // Filtro por fecha
        if (isset($_GET['filtroFecha'])) {
            $filtro = $_GET['filtroFecha'];
            $hoy = date('Y-m-d');

            switch ($filtro) {
                case 'hoy':
                    $condiciones[] = "DATE(v.fecha) = ?";
                    $parametros[] = $hoy;
                    break;
                case '7dias':
                    $condiciones[] = "v.fecha >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                    break;
                case '30dias':
                    $condiciones[] = "v.fecha >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                    break;
                // 'todos' no añade condición
            }
        }

        // Construir consulta
        $sql = "
            SELECT v.id, v.fecha, v.total, v.metodoPago as forma_pago, v.tipoDocumento, u.nombre as usuario_nombre,
            t.nombre as tarifa_nombre,
            (SELECT SUM(lv.cantidad) FROM lineasVenta lv WHERE lv.idVenta = v.id) as cantidad_productos
            FROM ventas v
            LEFT JOIN usuarios u ON v.idUsuario = u.id
            LEFT JOIN tarifas_prefijadas t ON v.idTarifa = t.id
        ";
        if (!empty($condiciones)) {
            $sql .= " WHERE " . implode(" AND ", $condiciones);
        }

        // Ordenar por
        $orden = "v.fecha DESC"; // por defecto
        if (isset($_GET['orden'])) {
            switch ($_GET['orden']) {
                case 'importe_asc':
                    $orden = "v.total ASC";
                    break;
                case 'importe_desc':
                    $orden = "v.total DESC";
                    break;
                case 'cantidad_asc':
                    $orden = "cantidad_productos ASC";
                    break;
                case 'cantidad_desc':
                    $orden = "cantidad_productos DESC";
                    break;
                case 'fecha_asc':
                    $orden = "v.fecha ASC";
                    break;
                case 'fecha_desc':
                    $orden = "v.fecha DESC";
                    break;
                case 'id_asc':
                    $orden = "v.id ASC";
                    break;
                case 'id_desc':
                    $orden = "v.id DESC";
                    break;
            }
        }

        $sql .= " ORDER BY " . $orden . " LIMIT 100";

        $stmt = $conexion->prepare($sql);
        $stmt->execute($parametros);
        $ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($ventas);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit();
}

// Ventas de los últimos 7 días agrupadas por día
// Obtenemos la conexión a la base de datos
$conexion = ConexionDB::getInstancia()->getConexion();
// Preparamos la consulta para obtener las ventas de los últimos 7 días agrupadas por día
$stmt = $conexion->prepare("
    SELECT 
        DATE(fecha) as dia,
        SUM(total) as total,
        COUNT(*) as pedidos
    FROM ventas
    WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY DATE(fecha)
    ORDER BY dia ASC
");
// Ejecutamos la consulta
$stmt->execute();
// Obtenemos los resultados en forma de array asociativo
$filas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Rellenamos los días vacíos para que siempre salgan 7 días
$resultado = [];
for ($i = 6; $i >= 0; $i--) {
    $dia = date('Y-m-d', strtotime("-$i days"));
    $resultado[$dia] = ['dia' => $dia, 'total' => 0, 'pedidos' => 0];
}
foreach ($filas as $fila) {
    $resultado[$fila['dia']] = $fila;
}

// Mostramos los resultados en formato JSON
echo json_encode(array_values($resultado));
?>