<?php
require_once(__DIR__ . '/../config/confDB.php');
require_once(__DIR__ . '/../core/conexionDB.php');

try {
    $pdo = ConexionDB::getInstancia()->getConexion();
    echo "FACTURAS:\n";
    $stmt = $pdo->query("SELECT serie, numero, hash FROM facturas ORDER BY id DESC LIMIT 3");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
    
    echo "TICKETS:\n";
    $stmt2 = $pdo->query("SELECT id, hash FROM tickets ORDER BY id DESC LIMIT 3");
    print_r($stmt2->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
