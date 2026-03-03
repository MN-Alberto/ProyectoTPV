<?php
/**
 * Script de prueba para verificar el proceso de devolución múltiple con ticket.
 */
require_once(__DIR__ . '/../config/confDB.php');
require_once(__DIR__ . '/../model/Venta.php');
require_once(__DIR__ . '/../model/LineaVenta.php');
require_once(__DIR__ . '/../model/Producto.php');
require_once(__DIR__ . '/../model/Devolucion.php');

echo "--- INICIO VERIFICACIÓN DE DEVOLUCIONES ---\n";

// 1. Buscar una venta real para probar o crear una ficticia
$conexion = ConexionDB::getInstancia()->getConexion();
$venta = $conexion->query("SELECT * FROM ventas ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);

if (!$venta) {
    die("No hay ventas en la base de datos para probar.\n");
}

$idVenta = $venta['id'];
echo "Probando con Venta ID: $idVenta\n";

// 2. Obtener líneas de esa venta
$lineas = LineaVenta::obtenerDetalleParaDevolucion($idVenta);
if (empty($lineas)) {
    die("La venta $idVenta no tiene líneas de detalle.\n");
}

echo "Líneas encontradas: " . count($lineas) . "\n";
foreach ($lineas as $l) {
    echo "- Producto: {$l['producto_nombre']} (ID: {$l['idProducto']}), Comprado: {$l['cantidad']}, Ya devuelto: {$l['cantidad_devuelta']}\n";
}

// 3. Simular una devolución de la primera línea (si hay margen)
$linea = $lineas[0];
$disponible = $linea['cantidad'] - $linea['cantidad_devuelta'];

if ($disponible <= 0) {
    echo "No hay cantidad disponible para devolver en la primera línea. Prueba con otra venta.\n";
} else {
    echo "Intentando devolver 1 unidad de {$linea['producto_nombre']}...\n";

    // Guardar stock inicial
    $producto = Producto::buscarPorId($linea['idProducto']);
    $stockInicial = $producto->getStock();

    // Crear devolución
    $dev = new Devolucion();
    $dev->setIdVenta($idVenta);
    $dev->setIdProducto($linea['idProducto']);
    $dev->setCantidad(1);
    $dev->setImporteTotal($linea['precioUnitario']);
    $dev->setIdUsuario(1); // Asumiendo que el ID 1 existe (admin)
    $dev->setMetodoPago('Efectivo');

    if ($dev->insertar()) {
        echo "OK: Devolución insertada con ID: " . $dev->getId() . "\n";

        // Actualizar stock
        $producto->actualizarStock(1);
        $stockFinal = Producto::buscarPorId($linea['idProducto'])->getStock();

        if ($stockFinal == $stockInicial + 1) {
            echo "OK: Stock actualizado correctamente ($stockInicial -> $stockFinal)\n";
        } else {
            echo "ERROR: Fallo en actualización de stock ($stockInicial -> $stockFinal)\n";
        }

        // Verificar que ahora aparece en el detalle
        $lineasNuevas = LineaVenta::obtenerDetalleParaDevolucion($idVenta);
        foreach ($lineasNuevas as $ln) {
            if ($ln['idProducto'] == $linea['idProducto']) {
                echo "OK: Cantidad devuelta actualizada en vista de detalle: {$ln['cantidad_devuelta']}\n";
            }
        }
    } else {
        echo "ERROR: No se pudo insertar la devolución.\n";
    }
}

echo "--- FIN VERIFICACIÓN ---\n";
