<?php
/**
 * API para gestionar los productos 
 * @author Alberto Méndez
 * @version 1.3 (03/03/2026)
 */

// Iniciamos la sesión
session_start();

// Requerimos los archivos necesarios
require_once(__DIR__ . '/../config/confDB.php');
require_once(__DIR__ . '/../model/Producto.php');
require_once(__DIR__ . '/../model/Categoria.php');

// Indicamos al navegador que es un tipo JSON
header('Content-Type: application/json; charset=utf-8');

try {
    // PREVISUALIZAR CAMBIO DE IVA
    if (isset($_GET['previsualizarIVA'])) {
        $nuevoIVA = floatval($_GET['previsualizarIVA']);

        if ($nuevoIVA < 0 || $nuevoIVA > 100) {
            http_response_code(400);
            echo json_encode(['error' => 'IVA inválido']);
            exit();
        }

        $conexion = ConexionDB::getInstancia()->getConexion();
        $stmt = $conexion->query("SELECT id, nombre, precio, iva FROM productos ORDER BY nombre ASC LIMIT 100");
        $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $resultado = [];
        foreach ($productos as $prod) {
            $resultado[] = [
                'id' => $prod['id'],
                'nombre' => $prod['nombre'],
                'precio' => floatval($prod['precio']),
                'iva_actual' => intval($prod['iva']),
                'iva_nuevo' => $nuevoIVA
            ];
        }

        echo json_encode(['ok' => true, 'productos' => $resultado]);
        exit();
    }

    // PREVISUALIZAR AJUSTE DE PRECIOS
    if (isset($_GET['previsualizarAjuste'])) {
        $porcentaje = floatval($_GET['previsualizarAjuste']);

        $conexion = ConexionDB::getInstancia()->getConexion();
        $stmt = $conexion->query("SELECT id, nombre, precio, iva FROM productos ORDER BY nombre ASC LIMIT 100");
        $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $resultado = [];
        foreach ($productos as $prod) {
            $precioActual = floatval($prod['precio']);
            $nuevoPrecio = round($precioActual * (1 + ($porcentaje / 100)), 2);
            $resultado[] = [
                'id' => $prod['id'],
                'nombre' => $prod['nombre'],
                'precio_actual' => $precioActual,
                'precio_nuevo' => $nuevoPrecio,
                'diferencia' => round($nuevoPrecio - $precioActual, 2)
            ];
        }

        echo json_encode(['ok' => true, 'productos' => $resultado]);
        exit();
    }

    // CAMBIAR IVA A TODOS LOS PRODUCTOS
    if (isset($_GET['cambiarIVA'])) {
        $nuevoIVA = floatval($_GET['cambiarIVA']);
        $excluidos = [];

        if ($nuevoIVA < 0 || $nuevoIVA > 100) {
            http_response_code(400);
            echo json_encode(['error' => 'IVA inválido']);
            exit();
        }

        $conexion = ConexionDB::getInstancia()->getConexion();

        // Obtener productos excluidos si se proporcionan
        if (isset($_GET['excluidos']) && !empty($_GET['excluidos'])) {
            $excluidos = array_map('intval', explode(',', $_GET['excluidos']));
        }

        // Actualizar IVA excluyendo productos si hay
        if (count($excluidos) > 0) {
            $placeholders = implode(',', array_fill(0, count($excluidos), '?'));
            $stmt = $conexion->prepare("UPDATE productos SET iva = :iva WHERE id NOT IN ($placeholders)");
            $params = array_merge([$nuevoIVA], $excluidos);
            $stmt->execute($params);
        } else {
            $stmt = $conexion->prepare("UPDATE productos SET iva = :iva");
            $stmt->bindParam(':iva', $nuevoIVA, PDO::PARAM_INT);
            $stmt->execute();
        }
        $actualizados = $stmt->rowCount();

        echo json_encode(['ok' => true, 'actualizados' => $actualizados]);
        exit();
    }

    // AJUSTAR PRECIOS DE TODOS LOS PRODUCTOS
    if (isset($_GET['ajustePrecios'])) {
        $porcentaje = floatval($_GET['ajustePrecios']);
        $excluidos = [];

        if (isset($_GET['excluidos']) && !empty($_GET['excluidos'])) {
            $excluidos = array_map('intval', explode(',', $_GET['excluidos']));
        }

        $conexion = ConexionDB::getInstancia()->getConexion();

        if (count($excluidos) > 0) {
            $placeholders = implode(',', array_fill(0, count($excluidos), '?'));
            $stmt = $conexion->prepare("SELECT id, precio FROM productos WHERE id NOT IN ($placeholders)");
            $stmt->execute($excluidos);
        } else {
            $stmt = $conexion->query("SELECT id, precio FROM productos");
        }
        $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $actualizados = 0;
        foreach ($productos as $prod) {
            $nuevoPrecio = $prod['precio'] * (1 + ($porcentaje / 100));
            $nuevoPrecio = round($nuevoPrecio, 2);

            $update = $conexion->prepare("UPDATE productos SET precio = :precio WHERE id = :id");
            $update->bindParam(':precio', $nuevoPrecio);
            $update->bindParam(':id', $prod['id'], PDO::PARAM_INT);
            $update->execute();
            $actualizados++;
        }

        echo json_encode(['ok' => true, 'actualizados' => $actualizados]);
        exit();
    }

    // Verificar si el usuario tiene permiso para crear productos
    if (isset($_GET['checkPermisoCrear'])) {
        $permisos = $_SESSION['permisosUsuario'] ?? '';
        $tienePermiso = ($permisos !== null && $permisos !== '' && strpos($permisos, 'crear_productos') !== false);
        echo json_encode(['tienePermiso' => $tienePermiso]);
        exit();
    }

    // Inicializamos el objeto Producto
    $producto = new Producto();

    // ── ELIMINAR PRODUCTO (DELETE) ────────────────────────────────────────────
    // Si el método es DELETE
    if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        // Obtenemos el id del producto a eliminar
        $id = isset($_GET['eliminar']) ? (int) $_GET['eliminar'] : 0;

        // Si el id es válido
        if ($id > 0) {
            // Buscar el producto por ID
            $productoAEliminar = Producto::buscarPorId($id);
            if ($productoAEliminar && $productoAEliminar->eliminar()) {
                // Registrar log de eliminación de producto
                try {
                    $pdoLog = new PDO(RUTA, USUARIO, PASS);
                    $pdoLog->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    $adminId = $_SESSION['id'] ?? null;
                    $adminNombre = $_SESSION['nombre'] ?? 'Admin';
                    $productoNombre = $productoAEliminar->getNombre() ?? 'ID: ' . $id;

                    $stmtLog = $pdoLog->prepare("INSERT INTO logs_sistema (tipo, usuario_id, usuario_nombre, descripcion) VALUES ('eliminacion_producto', :usuario_id, :usuario_nombre, :descripcion)");
                    $stmtLog->execute([
                        ':usuario_id' => $adminId,
                        ':usuario_nombre' => $adminNombre,
                        ':descripcion' => 'Producto eliminado: ' . $productoNombre
                    ]);
                } catch (Exception $e) {
                    // Silenciar errores de logging
                }

                // Devolvemos un código 200 (OK)
                http_response_code(200);
                echo json_encode(['ok' => true]);
            } else {
                // Devolvemos un código 400 (BAD REQUEST)
                http_response_code(400);
                echo json_encode(['ok' => false, 'error' => 'No se pudo eliminar el producto.']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'ID de producto inválido.']);
        }
        exit();
    }

    // ── POST (crear o actualizar) ──────────────────────────────────────────────────────
    // Si el método es POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Obtenemos los parámetros del formulario
        $id = (int) ($_POST['id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $precio = (float) ($_POST['precio'] ?? 0);
        $stock = (int) ($_POST['stock'] ?? 0);
        $activo = (int) ($_POST['activo'] ?? 1);
        $categoria = trim($_POST['categoria'] ?? '');
        $iva = (int) ($_POST['iva'] ?? 21);

        // Validar que los campos obligatorios no estén vacíos
        if (empty($nombre) || empty($categoria)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'El nombre y la categoría son obligatorios.']);
            exit();
        }

        // Si hay ID, es una actualización; si no hay ID, es una creación
        if ($id > 0) {
            // Buscamos el producto por id para actualizar
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
            $producto->setActivo($activo);
            $producto->setIva($iva);
        } else {
            // Es un nuevo producto - buscar la categoría para obtener su ID
            $categorias = Categoria::obtenerTodas();
            $idCategoria = null;
            foreach ($categorias as $cat) {
                if ($cat->getNombre() === $categoria) {
                    $idCategoria = $cat->getId();
                    break;
                }
            }

            if ($idCategoria === null) {
                http_response_code(400);
                echo json_encode(['ok' => false, 'error' => 'Categoría no encontrada.']);
                exit();
            }

            // Crear nuevo producto
            $producto = new Producto();
            $producto->setNombre($nombre);
            $producto->setPrecio($precio);
            $producto->setStock($stock);
            $producto->setActivo($activo);
            $producto->setIdCategoria($idCategoria);
            $producto->setImagen('webroot/img/logo.PNG');
            $producto->setIva($iva);
        }

        // Subida de imagen (opcional)
        // Verifica si existe un archivo enviado con el nombre 'imagen'
        // y que no haya ocurrido ningún error durante la subida
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            // Obtenemos la extensión del archivo
            $ext = pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);
            // Generamos un nombre único para el archivo
            $nombreFile = 'prod_' . ($id > 0 ? $id : time()) . '_' . time() . '.' . $ext;
            // Obtenemos la ruta de destino
            $destino = __DIR__ . '/../webroot/img/' . $nombreFile;
            // Movemos el archivo a la ruta de destino
            if (move_uploaded_file($_FILES['imagen']['tmp_name'], $destino)) {
                // Actualizamos la imagen del producto
                $producto->setImagen('webroot/img/' . $nombreFile);
            }
        }

        // Guardamos el producto (insertar o actualizar)
        if ($id > 0) {
            // Actualizamos el producto existente
            if ($producto->actualizar()) {
                // Registrar log de modificación de producto
                try {
                    $pdoLog = new PDO(RUTA, USUARIO, PASS);
                    $pdoLog->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    $adminId = $_SESSION['id'] ?? null;
                    $adminNombre = $_SESSION['nombre'] ?? 'Admin';

                    $stmtLog = $pdoLog->prepare("INSERT INTO logs_sistema (tipo, usuario_id, usuario_nombre, descripcion) VALUES ('modificacion_producto', :usuario_id, :usuario_nombre, :descripcion)");
                    $stmtLog->execute([
                        ':usuario_id' => $adminId,
                        ':usuario_nombre' => $adminNombre,
                        ':descripcion' => 'Producto modificado: ' . $nombre
                    ]);
                } catch (Exception $e) {
                    // Silenciar errores de logging
                }

                http_response_code(200);
                echo json_encode(['ok' => true]);
            } else {
                http_response_code(400);
                echo json_encode(['ok' => false, 'error' => 'No se pudo actualizar el producto.']);
            }
        } else {
            // Insertamos el nuevo producto
            if ($producto->insertar()) {
                // Registrar log de creación de producto
                try {
                    $pdoLog = new PDO(RUTA, USUARIO, PASS);
                    $pdoLog->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    $adminId = $_SESSION['id'] ?? null;
                    $adminNombre = $_SESSION['nombre'] ?? 'Admin';

                    $stmtLog = $pdoLog->prepare("INSERT INTO logs_sistema (tipo, usuario_id, usuario_nombre, descripcion) VALUES ('creacion_producto', :usuario_id, :usuario_nombre, :descripcion)");
                    $stmtLog->execute([
                        ':usuario_id' => $adminId,
                        ':usuario_nombre' => $adminNombre,
                        ':descripcion' => 'Producto creado: ' . $nombre
                    ]);
                } catch (Exception $e) {
                    // Silenciar errores de logging
                }

                http_response_code(201);
                echo json_encode(['ok' => true]);
            } else {
                http_response_code(400);
                echo json_encode(['ok' => false, 'error' => 'No se pudo crear el producto.']);
            }
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
            'imagen' => $prod->getImagen(),
            'iva' => (int) $prod->getIva()
        ];
    }

    // Devolvemos el resultado en formato JSON
    echo json_encode($resultado);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>