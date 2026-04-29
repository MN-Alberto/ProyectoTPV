<?php
require_once(__DIR__ . '/../config/confDB.php');
require_once(__DIR__ . '/../core/conexionDB.php');

try {
    $pdo = ConexionDB::getInstancia()->getConexion();
    echo "SEARCHING T531 and T530:\n";
    $stmt = $pdo->query("SELECT id, num_documento, hash, hash_previo FROM verifactu_cola_envios WHERE num_documento IN ('T530', 'T531') ORDER BY id DESC");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
    
    // Also check in tickets table (since T is for ticket)
    $stmt2 = $pdo->query("SELECT id, hash, hash_previo FROM tickets ORDER BY id DESC LIMIT 5");
    print_r($stmt2->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
