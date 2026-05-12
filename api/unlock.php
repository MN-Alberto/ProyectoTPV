<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once(__DIR__ . '/../config/confDB.php');
require_once(__DIR__ . '/../model/Usuario.php');

if (!isset($_SESSION['idUsuario'])) {
    echo json_encode(['success' => false, 'message' => 'No hay sesión activa']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$password = $data['password'] ?? '';

if (empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Contraseña vacía']);
    exit;
}

$nombre = $_SESSION['nombreUsuario'] ?? '';
$usuario = Usuario::login($nombre, $password);

if ($usuario) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Contraseña incorrecta']);
}
?>
