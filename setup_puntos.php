<?php
/**
 * Script para configurar el sistema de puntos
 * Crea la tabla de historial de puntos y añade la columna puntos a clientes si no existe
 */
require_once(__DIR__ . '/config/confDB.php');
require_once(__DIR__ . '/core/conexionDB.php');

try {
    $conexion = ConexionDB::getInstancia()->getConexion();

    // 1. Añadir columna puntos a clientes si no existe
    try {
        $conexion->exec("ALTER TABLE clientes ADD COLUMN puntos INT NOT NULL DEFAULT 0 AFTER compras_realizadas");
        echo "Columna 'puntos' añadida a la tabla clientes.\n";
    }
    catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "La columna 'puntos' ya existe en clientes.\n";
        }
        else {
            throw $e;
        }
    }

    // 2. Crear tabla de historial de puntos
    $sqlHistorial = "CREATE TABLE IF NOT EXISTS puntos_historial (
        id INT AUTO_INCREMENT PRIMARY KEY,
        cliente_dni VARCHAR(20) NOT NULL,
        venta_id INT DEFAULT NULL,
        puntos_ganados INT DEFAULT 0,
        puntos_canjeados INT DEFAULT 0,
        descuento_euros DECIMAL(10,2) DEFAULT 0,
        fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
        usuario_id INT DEFAULT NULL,
        observacion VARCHAR(255) DEFAULT NULL,
        INDEX idx_cliente_dni (cliente_dni),
        INDEX idx_venta_id (venta_id),
        INDEX idx_fecha (fecha)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $conexion->exec($sqlHistorial);
    echo "Tabla 'puntos_historial' creada o ya existente.\n";

    echo "\n✓ Sistema de puntos configurado correctamente.\n";
}
catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
