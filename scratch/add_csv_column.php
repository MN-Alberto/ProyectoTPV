<?php
require_once(__DIR__ . '/../config/confDB.php');
require_once(__DIR__ . '/../core/conexionDB.php');

try {
    $pdo = ConexionDB::getInstancia()->getConexion();
    $pdo->exec("ALTER TABLE verifactu_cola_envios ADD COLUMN csv_aeat VARCHAR(100) NULL AFTER respuesta_xml");
    echo "Column 'csv_aeat' added successfully.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
