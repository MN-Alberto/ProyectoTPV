<?php
require_once(__DIR__ . '/../config/confDB.php');
require_once(__DIR__ . '/../core/conexionDB.php');

try {
    $pdo = ConexionDB::getInstancia()->getConexion();
    $stmt = $pdo->query("SELECT * FROM verifactu_cola_envios LIMIT 1");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode(array_keys($row), JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
