<?php
require_once(__DIR__ . '/../config/confDB.php');
require_once(__DIR__ . '/../core/conexionDB.php');

try {
    $pdo = ConexionDB::getInstancia()->getConexion();
    $stmt = $pdo->prepare("SELECT xml_contenido FROM verifactu_cola_envios WHERE num_documento = 'T533' ORDER BY id DESC LIMIT 1");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        echo $row['xml_contenido'];
    } else {
        echo "NOT FOUND";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
