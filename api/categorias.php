<?php
/**
 * API para gestionar las categorías
 * @author Alberto Méndez
 * @version 1.0 (02/03/2026)
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once(__DIR__ . '/../config/confDB.php');
require_once(__DIR__ . '/../model/Categoria.php');

header('Content-Type: application/json; charset=utf-8');

try {
    $categorias = Categoria::obtenerTodas();

    $resultado = [];
    foreach ($categorias as $cat) {
        $resultado[] = [
            'id' => $cat->getId(),
            'nombre' => $cat->getNombre()
        ];
    }

    echo json_encode($resultado);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>