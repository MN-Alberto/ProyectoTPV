<?php
require_once(__DIR__ . '/../config/confDB.php');

try {
    $pdo = new PDO(RUTA, USUARIO, PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Añadiendo índices para mejorar rendimiento de estadísticas...\n";

    // Índices para lineasVenta
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_lineas_producto ON lineasVenta(idProducto)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_lineas_venta ON lineasVenta(idVenta)");

    // Índices para tickets y facturas (sobre el campo fecha)
    // Nota: Si son vistas, hay que indexar las tablas base.
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_tickets_fecha ON tickets(fecha)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_facturas_fecha ON facturas(fecha)");

    echo "¡Índices creados con éxito!";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
