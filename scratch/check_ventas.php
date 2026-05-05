<?php
require_once(__DIR__ . '/../config/confDB.php');

try {
    $conexion = ConexionDB::getInstancia()->getConexion();
    
    // Check if view 'ventas' exists and its structure
    echo "--- ESTRUCTURA DE LA VISTA 'ventas' ---\n";
    $stmt = $conexion->query("DESCRIBE ventas");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
    }
    
    // Check latest sales in 'ventas' view
    echo "\n--- ÚLTIMAS 5 VENTAS EN LA VISTA 'ventas' ---\n";
    $stmt = $conexion->query("SELECT id, fecha, total, serie, numero FROM ventas ORDER BY fecha DESC LIMIT 5");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
    }
    
    // Check if there is an open session
    echo "\n--- SESIÓN DE CAJA ABIERTA ---\n";
    $stmt = $conexion->query("SELECT id, fechaApertura FROM caja_sesiones WHERE estado = 'abierta' ORDER BY id DESC LIMIT 1");
    $caja = $stmt->fetch(PDO::FETCH_ASSOC);
    print_r($caja);
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
