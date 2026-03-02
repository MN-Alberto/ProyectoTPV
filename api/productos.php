<?php
/**
 * API para gestionar los productos 
 * @author Alberto Méndez
 * @version 1.2 (02/03/2026)
 */

// Requerimos los archivos necesarios
require_once(__DIR__ . '/../config/confDB.php');
require_once(__DIR__ . '/../model/Producto.php');
require_once(__DIR__ . '/../model/Categoria.php');

// Indicamos al navegador que es un tipo JSON
header('Content-Type: application/json; charset=utf-8');

// Inicializamos el objeto Producto
$producto = new Producto();

// ── ELIMINAR PRODUCTO (DELETE) ────────────────────────────────────────────
// Si el método es DELETE
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    // Parseamos los parámetros
    parse_str(file_get_contents('php://input'), $params);
    // Obtenemos el id del producto a eliminar
    $id = isset($_GET['eliminar']) ? (int) $_GET['eliminar'] : 0;

    // Si el id es válido y se puede eliminar
    if ($id > 0 && $producto->eliminar($id)) {
        // Devolvemos un código 200 (OK)
        http_response_code(200);
        echo json_encode(['ok' => true]);
    } else {
        // Devolvemos un código 400 (BAD REQUEST)
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'No se pudo eliminar el producto.']);
    }
    exit();
}

// ── POST (actualizar) ──────────────────────────────────────────────────────
// Si el método es POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtenemos los parámetros del formulario
    $id = (int) $_POST['id'];
    $nombre = trim($_POST['nombre']);
    $precio = (float) $_POST['precio'];
    $stock = (int) $_POST['stock'];
    $activo = (int) $_POST['activo'];

    // Buscamos el producto por id
    $producto = Producto::buscarPorId($id);

    // Si no existe el producto
    if (!$producto) {
        // Devolvemos un código 404 (NOT FOUND)
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'Producto no encontrado.']);
        exit();
    }

    // Actualizamos los campos del producto
    $producto->setNombre($nombre);
    $producto->setPrecio($precio);
    $producto->setStock($stock);
    $producto->setActivo((int) $_POST['activo']);

    // Subida de imagen (opcional)
    // Verifica si existe un archivo enviado con el nombre 'imagen'
    // y que no haya ocurrido ningún error durante la subida
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        // Obtenemos la extensión del archivo
        $ext = pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);
        // Generamos un nombre único para el archivo
        $nombreFile = 'prod_' . $id . '_' . time() . '.' . $ext;
        // Obtenemos la ruta de destino
        $destino = __DIR__ . '/../webroot/img/' . $nombreFile;
        // Movemos el archivo a la ruta de destino
        if (move_uploaded_file($_FILES['imagen']['tmp_name'], $destino)) {
            // Actualizamos la imagen del producto
            $producto->setImagen('webroot/img/' . $nombreFile);
        }
    }

    // Actualizamos el producto
    if ($producto->actualizar()) {
        // Devolvemos un código 200 (OK)
        http_response_code(200);
        echo json_encode(['ok' => true]);
    } else {
        // Devolvemos un código 400 (BAD REQUEST)
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'No se pudo actualizar el producto.']);
    }
    exit();
}

// ── FILTROS GET ───────────────────────────────────────────────────────────

// Si se busca un producto
if (isset($_GET['buscarProducto']) && !empty(trim($_GET['buscarProducto']))) {
    // Obtenemos la búsqueda
    $busqueda = trim($_GET['buscarProducto']);

    // Si se busca un producto por nombre y categoría
    if (isset($_GET['idCategoria']) && !empty($_GET['idCategoria']) && $_GET['idCategoria'] !== 'todas') {
        // Buscamos el producto por nombre y categoría
        $productos = Producto::buscarPorNombreYCategoria($busqueda, (int) $_GET['idCategoria']);
    } else {
        // Buscamos el producto por nombre
        $productos = Producto::buscarPorNombre($busqueda);
    }
    // Si se busca un producto por categoría
} elseif (isset($_GET['idCategoria']) && !empty($_GET['idCategoria']) && $_GET['idCategoria'] !== 'todas') {
    // Obtenemos los productos por categoría
    $productos = Producto::obtenerPorCategoria((int) $_GET['idCategoria']);
    // Si no se busca nada
} else {
    // Si se busca un producto por admin
    if (isset($_GET['admin'])) {
        // Obtenemos todos los productos incluyendo los inactivos
        $productos = Producto::obtenerTodosAdmin();
    } else {
        // Obtenemos todos los productos
        $productos = Producto::obtenerTodos();
    }
}

// ── FORMATEO RESPUESTA ────────────────────────────────────────────────────
// Creamos un array para mapear el id de la categoría a su nombre
$mapCategorias = [];
foreach (Categoria::obtenerTodas() as $cat) {
    $mapCategorias[$cat->getId()] = $cat->getNombre();
}

// Creamos un array para almacenar el resultado
$resultado = [];
// Recorremos los productos
foreach ($productos as $prod) {
    // Obtenemos el id de la categoría
    $idCat = (int) $prod->getIdCategoria();
    // Añadimos el producto al resultado
    $resultado[] = [
        'id' => $prod->getId(),
        'nombre' => $prod->getNombre(),
        'precio' => (float) $prod->getPrecio(),
        'stock' => (int) $prod->getStock(),
        'idCategoria' => $idCat,
        'categoria' => $mapCategorias[$idCat] ?? '—',   // ← nombre legible
        'activo' => (int) $prod->getActivo(),         // ← 1 = activo, 0 = inactivo
        'imagen' => $prod->getImagen()
    ];
}

// Devolvemos el resultado en formato JSON
echo json_encode($resultado);
?>