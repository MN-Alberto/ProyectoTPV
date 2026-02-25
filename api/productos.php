<?php
/*
 * Autor: Alberto Méndez
 * Fecha de actualización: 25/02/2026
 *
 * API REST para obtener productos en formato JSON.
 * Soporta filtrado por categoría y búsqueda por nombre.
 */

require_once(__DIR__ . '/../config/confDB.php');
require_once(__DIR__ . '/../model/Producto.php');
require_once(__DIR__ . '/../model/Categoria.php');

header('Content-Type: application/json; charset=utf-8');

// Determinar qué productos devolver.
if (isset($_GET['buscarProducto']) && !empty(trim($_GET['buscarProducto']))) {
    // Búsqueda por nombre.
    $productos = Producto::buscarPorNombre(trim($_GET['buscarProducto']));
} elseif (isset($_GET['idCategoria']) && !empty($_GET['idCategoria'])) {
    // Filtrar por categoría.
    $productos = Producto::obtenerPorCategoria((int) $_GET['idCategoria']);
} else {
    // Todos los productos.
    $productos = Producto::obtenerTodos();
}

// Convertir a array asociativo para JSON.
$resultado = [];
foreach ($productos as $prod) {
    $resultado[] = [
        'id' => $prod->getId(),
        'nombre' => $prod->getNombre(),
        'precio' => (float) $prod->getPrecio(),
        'stock' => (int) $prod->getStock(),
        'idCategoria' => (int) $prod->getIdCategoria()
    ];
}

echo json_encode($resultado);
?>