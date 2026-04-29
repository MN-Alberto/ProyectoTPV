<?php
require_once(__DIR__ . '/../config/confDB.php');
require_once(__DIR__ . '/../core/conexionDB.php');

try {
    $pdo = ConexionDB::getInstancia()->getConexion();
    $pdo->exec("ALTER TABLE verifactu_cola_envios ADD COLUMN respuesta_xml LONGTEXT NULL AFTER xml_contenido");
    echo "Column 'respuesta_xml' added successfully.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
