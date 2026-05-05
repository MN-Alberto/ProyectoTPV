<?php
require_once(__DIR__ . '/../config/confDB.php');

try {
    $dsn = RUTA;
    $pdo = new PDO($dsn, USUARIO, PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "--- lineasVenta ---\n";
    $stmt = $pdo->query("SHOW CREATE TABLE lineasVenta");
    echo $stmt->fetchColumn(1) . "\n\n";

    echo "--- ventas ---\n";
    $stmt = $pdo->query("SHOW CREATE TABLE ventas");
    echo $stmt->fetchColumn(1) . "\n\n";

    echo "--- tickets ---\n";
    $stmt = $pdo->query("SHOW CREATE TABLE tickets");
    echo $stmt->fetchColumn(1) . "\n\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
