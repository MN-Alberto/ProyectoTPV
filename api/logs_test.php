<?php
/**
 * API para gestionar los logs del sistema.
 * 
 * GET  → Devuelve los logs con paginación y filtros
 * POST → Crea un nuevo log (solo usuarios autenticados)
 * 
 * @author Alberto Méndez
 * @version 1.1
 */

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Starting API...<br>";

try {
    require_once(__DIR__ . '/../config/confDB.php');
    echo "Config loaded<br>";

    session_start();
    echo "Session started<br>";

    header('Content-Type: application/json; charset=utf-8');

    $pdo = new PDO(RUTA, USUARIO, PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Database connected<br>";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
    echo "Stack: " . $e->getTraceAsString() . "<br>";
    exit;
}

// Verificar si la tabla existe
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'logs_sistema'");
    if ($stmt->rowCount() === 0) {
        echo json_encode(['logs' => [], 'total' => 0, 'mensaje' => 'Tabla no existe']);
        exit;
    }
    echo "Table exists<br>";
} catch (Exception $e) {
    echo "Error checking table: " . $e->getMessage() . "<br>";
    exit;
}

echo "API Ready";
