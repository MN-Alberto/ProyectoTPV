<?php
require_once(__DIR__ . '/../config/confDB.php');
require_once(__DIR__ . '/../core/conexionDB.php');

try {
    $pdo = ConexionDB::getInstancia()->getConexion();
    echo "SEARCHING HASH 8B3E...\n";
    $stmt = $pdo->query("SELECT id, hash FROM tickets WHERE hash LIKE '8B3E%'");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
    
    $stmt2 = $pdo->query("SELECT id, hash FROM facturas WHERE hash LIKE '8B3E%'");
    print_r($stmt2->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
