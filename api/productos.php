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
    $busqueda = trim($_GET['buscarProducto']);
    if (isset($_GET['idCategoria']) && !empty($_GET['idCategoria']) && $_GET['idCategoria'] !== 'todas') {
        // Búsqueda por nombre y categoría específica
        $productos = Producto::buscarPorNombreYCategoria($busqueda, (int) $_GET['idCategoria']);
    } else {
        // Búsqueda por nombre en todas las categorías
        $productos = Producto::buscarPorNombre($busqueda);
    }
} elseif (isset($_GET['idCategoria']) && !empty($_GET['idCategoria']) && $_GET['idCategoria'] !== 'todas') {
    // Filtrar por categoría sin búsqueda de nombre.
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
        'idCategoria' => (int) $prod->getIdCategoria(),
        'imagen' => $prod->getImagen()
    ];
}

echo json_encode($resultado);
?>