<?php
require_once(__DIR__ . '/../config/confDB.php');
require_once(__DIR__ . '/../model/Producto.php');
require_once(__DIR__ . '/../model/Categoria.php');

header('Content-Type: application/json; charset=utf-8');

$producto = new Producto();

// ── ELIMINAR PRODUCTO (DELETE) ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    parse_str(file_get_contents('php://input'), $params);
    $id = isset($_GET['eliminar']) ? (int) $_GET['eliminar'] : 0;

    if ($id > 0 && $producto->eliminar($id)) {
        echo json_encode(['ok' => true]);
    } else {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'No se pudo eliminar el producto.']);
    }
    exit();
}

// ── POST (actualizar) ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) $_POST['id'];
    $nombre = trim($_POST['nombre']);
    $precio = (float) $_POST['precio'];
    $stock = (int) $_POST['stock'];
    $activo = (int) $_POST['activo'];

    // Cargar el producto existente para no perder descripción e idCategoria
    $producto = Producto::buscarPorId($id);

    if (!$producto) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'Producto no encontrado.']);
        exit();
    }

    // Actualizar solo los campos que llegan del formulario
    $producto->setNombre($nombre);
    $producto->setPrecio($precio);
    $producto->setStock($stock);
    $producto->setActivo((int) $_POST['activo']);

    // Subida de imagen (opcional)
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);
        $nombreFile = 'prod_' . $id . '_' . time() . '.' . $ext;
        $destino = __DIR__ . '/../webroot/img/' . $nombreFile;
        if (move_uploaded_file($_FILES['imagen']['tmp_name'], $destino)) {
            $producto->setImagen('webroot/img/' . $nombreFile);
        }
    }

    if ($producto->actualizar()) {
        echo json_encode(['ok' => true]);
    } else {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'No se pudo actualizar el producto.']);
    }
    exit();
}

// ── FILTROS GET ───────────────────────────────────────────────────────────
if (isset($_GET['buscarProducto']) && !empty(trim($_GET['buscarProducto']))) {
    $busqueda = trim($_GET['buscarProducto']);

    if (isset($_GET['idCategoria']) && !empty($_GET['idCategoria']) && $_GET['idCategoria'] !== 'todas') {
        $productos = Producto::buscarPorNombreYCategoria($busqueda, (int) $_GET['idCategoria']);
    } else {
        $productos = Producto::buscarPorNombre($busqueda);
    }

} elseif (isset($_GET['idCategoria']) && !empty($_GET['idCategoria']) && $_GET['idCategoria'] !== 'todas') {
    $productos = Producto::obtenerPorCategoria((int) $_GET['idCategoria']);

} else {
    if (isset($_GET['admin'])) {
        $productos = Producto::obtenerTodosAdmin();
    } else {
        $productos = Producto::obtenerTodos();
    }
}

// ── FORMATEO RESPUESTA ────────────────────────────────────────────────────
// Cargamos las categorías una sola vez para mapear id → nombre
$mapCategorias = [];
foreach (Categoria::obtenerTodas() as $cat) {
    $mapCategorias[$cat->getId()] = $cat->getNombre();
}

$resultado = [];
foreach ($productos as $prod) {
    $idCat = (int) $prod->getIdCategoria();
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

echo json_encode($resultado);
?>