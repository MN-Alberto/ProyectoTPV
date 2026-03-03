<?php
require_once(__DIR__ . '/../config/confDB.php');
require_once(__DIR__ . '/../core/conexionDB.php');

try {
    $conexion = ConexionDB::getInstancia()->getConexion();

    // Añadir columna idVenta
    $sql1 = "ALTER TABLE devoluciones ADD COLUMN idVenta INT NULL AFTER idUsuario";
    try {
        $conexion->exec($sql1);
        echo "Columna idVenta añadida.\n";
    } catch (PDOException $e) {
        echo "Aviso (Columna): " . $e->getMessage() . "\n";
    }

    // Añadir clave foránea
    $sql2 = "ALTER TABLE devoluciones 
             ADD CONSTRAINT fk_devolucion_venta 
             FOREIGN KEY (idVenta) REFERENCES ventas(id) 
             ON DELETE SET NULL";
    try {
        $conexion->exec($sql2);
        echo "Clave foránea fk_devolucion_venta añadida.\n";
    } catch (PDOException $e) {
        echo "Aviso (FK): " . $e->getMessage() . "\n";
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
