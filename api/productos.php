<?php
/**
 * TPV Bazar - API REST de Productos
 * 
 * Este script actúa como un endpoint que devuelve la lista de productos en formato JSON.
 * Permite filtrar los resultados mediante parámetros GET para:
 * - Obtener todos los productos.
 * - Filtrar por una categoría específica.
 * - Buscar productos por nombre.
 * - Combinar búsqueda por nombre y filtro de categoría.
 * 
 * @author Alberto Méndez
 * @version 1.2 (26/02/2026)
 */

// Carga de configuración de base de datos y modelos necesarios
require_once(__DIR__ . '/../config/confDB.php');
require_once(__DIR__ . '/../model/Producto.php');
require_once(__DIR__ . '/../model/Categoria.php');

// Establecemos la cabecera para que el navegador/cliente trate la respuesta como JSON en UTF-8
header('Content-Type: application/json; charset=utf-8');

/**
 * ────────────────────────────────────────────────────────────────────────────
 * 1. PROCESAMIENTO DE FILTROS Y BÚSQUEDA
 * ────────────────────────────────────────────────────────────────────────────
 * Se evalúan los parámetros recibidos vía URL (?idCategoria=X&buscarProducto=Y)
 */

if (isset($_GET['buscarProducto']) && !empty(trim($_GET['buscarProducto']))) {
    // Si hay una cadena de búsqueda, limpiamos espacios en blanco
    $busqueda = trim($_GET['buscarProducto']);

    if (isset($_GET['idCategoria']) && !empty($_GET['idCategoria']) && $_GET['idCategoria'] !== 'todas') {
        // CASO A: Búsqueda por nombre dentro de una categoría específica
        $productos = Producto::buscarPorNombreYCategoria($busqueda, (int) $_GET['idCategoria']);
    } else {
        // CASO B: Búsqueda por nombre en todo el catálogo (todas las categorías)
        $productos = Producto::buscarPorNombre($busqueda);
    }

} elseif (isset($_GET['idCategoria']) && !empty($_GET['idCategoria']) && $_GET['idCategoria'] !== 'todas') {
    // CASO C: Listado completo de una categoría específica (sin búsqueda por nombre)
    $productos = Producto::obtenerPorCategoria((int) $_GET['idCategoria']);

} else {
    // CASO D: Sin filtros ni búsquedas, devolvemos el catálogo completo
    $productos = Producto::obtenerTodos();
}

/**
 * ────────────────────────────────────────────────────────────────────────────
 * 2. FORMATEO DE LA RESPUESTA JSON
 * ────────────────────────────────────────────────────────────────────────────
 * Recorremos la lista de objetos 'Producto' para convertirlos en un array 
 * asociativo plano, asegurando tipos de datos correctos para el frontend.
 */
$resultado = [];
foreach ($productos as $prod) {
    $resultado[] = [
        'id' => $prod->getId(),
        'nombre' => $prod->getNombre(),
        'precio' => (float) $prod->getPrecio(),      // Forzamos float para cálculos en JS
        'stock' => (int) $prod->getStock(),        // Forzamos int para validaciones de inventario
        'idCategoria' => (int) $prod->getIdCategoria(),
        'imagen' => $prod->getImagen()              // Ruta de la imagen del producto
    ];
}

// Codificamos el array resultante en una cadena JSON y la imprimimos
echo json_encode($resultado);
?>