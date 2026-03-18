<?php
require_once(__DIR__ . '/../config/confDB.php');

try {
    $pdo = new PDO(RUTA, USUARIO, PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Añadiendo columnas a caja_sesiones...\n";

    $queries = [
        "ALTER TABLE caja_sesiones ADD COLUMN interrupcion_tipo VARCHAR(20) DEFAULT NULL",
        "ALTER TABLE caja_sesiones ADD COLUMN interrupcion_usuario_id INT DEFAULT NULL",
        "ALTER TABLE caja_sesiones ADD COLUMN interrupcion_usuario_nombre VARCHAR(100) DEFAULT NULL",
        "ALTER TABLE caja_sesiones ADD COLUMN interrupcion_fecha DATETIME DEFAULT NULL"
    ];

    foreach ($queries as $sql) {
        try {
            $pdo->exec($sql);
            echo "Ejecutada: $sql\n";
        } catch (Exception $e) {
            echo "Error (posiblemente ya existe): " . $e->getMessage() . "\n";
        }
    }

    echo "Migración completada.\n";

} catch (Exception $e) {
    echo "Error de conexión: " . $e->getMessage() . "\n";
    exit(1);
}
