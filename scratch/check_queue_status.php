<?php
require_once(__DIR__ . '/../config/confDB.php');
require_once(__DIR__ . '/../core/conexionDB.php');

try {
    $pdo = ConexionDB::getInstancia()->getConexion();
    echo "QUEUE STATUS COUNT:\n";
    $stmt = $pdo->query("SELECT estado, COUNT(*) as total FROM verifactu_cola_envios GROUP BY estado");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
    
    echo "STUCK RECORDS (Enviando > 5m):\n";
    $stmt2 = $pdo->query("SELECT id, num_documento, fecha_ultimo_intento FROM verifactu_cola_envios WHERE estado = 'enviando' AND fecha_ultimo_intento < DATE_SUB(NOW(), INTERVAL 5 MINUTE)");
    print_r($stmt2->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
