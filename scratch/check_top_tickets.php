<?php
require_once(__DIR__ . '/../config/confDB.php');
require_once(__DIR__ . '/../core/conexionDB.php');

try {
    $pdo = ConexionDB::getInstancia()->getConexion();
    echo "TOP TICKETS:\n";
    $stmt = $pdo->query("SELECT id, hash, hash_previo FROM tickets ORDER BY id DESC LIMIT 5");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
