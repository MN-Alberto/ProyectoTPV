<?php
require_once(__DIR__ . '/config/confDB.php');
require_once(__DIR__ . '/core/conexionDB.php');

try {
    $conexion = ConexionDB::getInstancia()->getConexion();

    $sql1 = "CREATE TABLE IF NOT EXISTS cambios_tarifas_batch (
        id INT AUTO_INCREMENT PRIMARY KEY,
        usuario_id INT,
        fecha_programada DATETIME NOT NULL,
        estado ENUM('pendiente', 'aplicado', 'cancelado') DEFAULT 'pendiente',
        fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        fecha_aplicacion DATETIME DEFAULT NULL,
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $sql2 = "CREATE TABLE IF NOT EXISTS ajustes_tarifas_detalle (
        id INT AUTO_INCREMENT PRIMARY KEY,
        batch_id INT NOT NULL,
        producto_id INT NOT NULL,
        tarifa_id INT NOT NULL,
        precio_anterior DECIMAL(10, 4) NOT NULL DEFAULT 0,
        precio_nuevo DECIMAL(10, 4) NOT NULL,
        FOREIGN KEY (batch_id) REFERENCES cambios_tarifas_batch(id) ON DELETE CASCADE,
        FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE,
        FOREIGN KEY (tarifa_id) REFERENCES tarifas_prefijadas(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $conexion->exec($sql1);
    echo "Tabla cambios_tarifas_batch creada o ya existente.\n";

    $conexion->exec($sql2);
    echo "Tabla ajustes_tarifas_detalle creada o ya existente.\n";

}
catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
