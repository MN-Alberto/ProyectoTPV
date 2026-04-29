<?php
require_once(__DIR__ . '/../config/confDB.php');
require_once(__DIR__ . '/../core/conexionDB.php');

try {
    $pdo = ConexionDB::getInstancia()->getConexion();
    $stmt = $pdo->query("SELECT * FROM facturas LIMIT 1");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    print_r(array_keys($row));
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
