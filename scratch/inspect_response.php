<?php
require_once(__DIR__ . '/../config/confDB.php');
require_once(__DIR__ . '/../core/conexionDB.php');

try {
    $pdo = ConexionDB::getInstancia()->getConexion();
    $stmt = $pdo->query("SELECT id, respuesta_xml FROM verifactu_cola_envios WHERE estado = 'enviado' ORDER BY id DESC LIMIT 1");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        echo "ID: " . $row['id'] . "\n";
        echo "Response: " . $row['respuesta_xml'] . "\n";
    } else {
        echo "No records found.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
