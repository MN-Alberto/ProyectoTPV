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
require_once(__DIR__ . '/../model/Iva.php');

// Indicamos al navegador que es un tipo JSON
header('Content-Type: application/json; charset=utf-8');

try {
    // PREVISUALIZAR CAMBIO DE IVA
    if (isset($_GET['previsualizarIVA'])) {
        $nuevoIdIva = intval($_GET['previsualizarIVA']);

        // Verificar que el tipo de IVA existe
        $nuevoIva = Iva::buscarPorId($nuevoIdIva);
        if (!$nuevoIva) {
            http_response_code(400);
            echo json_encode(['error' => 'Tipo de IVA inválido']);
            exit();
        }

        $conexion = ConexionDB::getInstancia()->getConexion();
        $stmt = $conexion->query("SELECT p.id, p.nombre, p.precio, p.idIva, i.porcentaje as ivaPorcentaje, i.nombre as ivaNombre FROM productos p LEFT JOIN iva i ON p.idIva = i.id ORDER BY p.nombre ASC LIMIT 100");
        $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $resultado = [];
        foreach ($productos as $prod) {
            $resultado[] = [
                'id' => $prod['id'],
                'nombre' => $prod['nombre'],
                'precio' => floatval($prod['precio']),
                'iva_actual' => floatval($prod['ivaPorcentaje']),
                'iva_nuevo' => floatval($nuevoIva->getPorcentaje())
            ];
        }

        echo json_encode(['ok' => true, 'productos' => $resultado]);
        exit();
    }

    // PREVISUALIZAR AJUSTE DE PRECIOS
    if (isset($_GET['previsualizarAjuste'])) {
        $porcentaje = floatval($_GET['previsualizarAjuste']);

        $conexion = ConexionDB::getInstancia()->getConexion();
        $stmt = $conexion->query("SELECT p.id, p.nombre, p.precio, p.idIva, i.porcentaje as ivaPorcentaje FROM productos p LEFT JOIN iva i ON p.idIva = i.id ORDER BY p.nombre ASC LIMIT 100");
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
        $nuevoIdIva = intval($_GET['cambiarIVA']);
        $excluidos = [];

        // Verificar que el tipo de IVA existe
        $nuevoIva = Iva::buscarPorId($nuevoIdIva);
        if (!$nuevoIva) {
            http_response_code(400);
            echo json_encode(['error' => 'Tipo de IVA inválido']);
            exit();
        }

        $conexion = ConexionDB::getInstancia()->getConexion();

        // Obtener el IVA anterior (el más común en los productos)
        $stmtAnterior = $conexion->query("SELECT i.porcentaje as iva_actual FROM productos p LEFT JOIN iva i ON p.idIva = i.id GROUP BY p.idIva ORDER BY COUNT(*) DESC LIMIT 1");
        $ivaAnterior = $stmtAnterior->fetch(PDO::FETCH_ASSOC);
        $ivaAnteriorValor = $ivaAnterior ? floatval($ivaAnterior['iva_actual']) : null;

        // Obtener productos excluidos si se proporcionan
        if (isset($_GET['excluidos']) && !empty($_GET['excluidos'])) {
            $excluidos = array_map('intval', explode(',', $_GET['excluidos']));
        }

        // Actualizar idIva excluyendo productos si hay
        if (count($excluidos) > 0) {
            $placeholders = implode(',', array_fill(0, count($excluidos), '?'));
            $stmt = $conexion->prepare("UPDATE productos SET idIva = ? WHERE id NOT IN ($placeholders)");
            $params = array_merge([$nuevoIdIva], $excluidos);
            $stmt->execute($params);
        } else {
            $stmt = $conexion->prepare("UPDATE productos SET idIva = ?");
            $stmt->execute([$nuevoIdIva]);
        }
        $actualizados = $stmt->rowCount();

        // Registrar log de cambio de IVA (si falla, no afecta al resultado)
        $logExito = false;
        try {
            $stmtLog = $conexion->prepare("INSERT INTO logs_sistema (tipo, usuario_id, usuario_nombre, descripcion, detalles) VALUES (:tipo, :usuario_id, :usuario_nombre, :descripcion, :detalles)");
            $stmtLog->execute([
                ':tipo' => 'cambio_iva',
                ':usuario_id' => $_SESSION['idUsuario'] ?? null,
                ':usuario_nombre' => $_SESSION['nombreUsuario'] ?? 'Desconocido',
                ':descripcion' => 'Cambio de IVA de ' . $ivaAnteriorValor . '% a ' . $nuevoIva->getPorcentaje() . '%',
                ':detalles' => json_encode([
                    'iva_anterior' => $ivaAnteriorValor,
                    'iva_nuevo' => floatval($nuevoIva->getPorcentaje()),
                    'iva_nombre' => $nuevoIva->getNombre(),
                    'productos_actualizados' => intval($actualizados),
                    'productos_excluidos' => $excluidos
                ], JSON_UNESCAPED_UNICODE)
            ]);
            $logExito = true;
        } catch (Exception $e) {
            // Silenciar errores de logging pero continuar
            error_log('Error al registrar log de cambio IVA: ' . $e->getMessage());
        }

        echo json_encode(['ok' => true, 'actualizados' => $actualizados, 'log' => $logExito]);
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

        // Verificar si la columna precio_cliente existe
        $columnExists = false;
        try {
            $stmtCheck = $conexion->query("SHOW COLUMNS FROM productos LIKE 'precio_cliente'");
            $columnExists = $stmtCheck->rowCount() > 0;
        } catch (Exception $e) {
            $columnExists = false;
        }

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

            // Actualizar también el precio de la tarifa Cliente
            if ($columnExists) {
                $update = $conexion->prepare("UPDATE productos SET precio = :precio, precio_cliente = :precio WHERE id = :id");
                $update->bindParam(':precio', $nuevoPrecio);
                $update->bindParam(':id', $prod['id'], PDO::PARAM_INT);
            } else {
                $update = $conexion->prepare("UPDATE productos SET precio = :precio WHERE id = :id");
                $update->bindParam(':precio', $nuevoPrecio);
                $update->bindParam(':id', $prod['id'], PDO::PARAM_INT);
            }
            $update->execute();
            $actualizados++;
        }

        echo json_encode(['ok' => true, 'actualizados' => $actualizados]);
        exit();
    }

    // Verificar si el usuario tiene permiso para crear productos
    if (isset($_GET['historialPrecios'])) {
        $idProducto = intval($_GET['historialPrecios']);

        if ($idProducto <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'ID de producto inválido']);
            exit;
        }

        $conexion = ConexionDB::getInstancia()->getConexion();

        // Verificar si la tabla de historial existe
        try {
            $stmtCheck = $conexion->query("SHOW TABLES LIKE 'productos_historial_precios'");
            if ($stmtCheck->rowCount() == 0) {
                echo json_encode([]);
                exit;
            }
        } catch (Exception $e) {
            echo json_encode([]);
            exit;
        }

        // Obtener el historial de precios
        $stmt = $conexion->prepare("
            SELECT h.id, h.precio, h.fecha_cambio, h.id_tarifa, t.nombre as tarifa_nombre, u.nombre as usuario_nombre
            FROM productos_historial_precios h
            LEFT JOIN tarifas_prefijadas t ON h.id_tarifa = t.id
            LEFT JOIN usuarios u ON h.usuario_id = u.id
            WHERE h.id_producto = :id_producto
            ORDER BY h.fecha_cambio DESC
        ");
        $stmt->bindParam(':id_producto', $idProducto, PDO::PARAM_INT);
        $stmt->execute();

        $historial = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $historial[] = [
                'precio' => floatval($row['precio']),
                'valido_desde' => $row['fecha_cambio'],
                'tarifa' => $row['tarifa_nombre'] ?? 'Precio Base',
                'usuario' => $row['usuario_nombre'] ?? 'Sistema'
            ];
        }

        echo json_encode($historial);
        exit;
    }

    // OBTENER LISTA DE PRODUCTOS PARA DROPdown (para historial de precios)
    if (isset($_GET['listaProductos'])) {
        $conexion = ConexionDB::getInstancia()->getConexion();
        $stmt = $conexion->query("SELECT id, nombre, precio FROM productos WHERE activo = 1 ORDER BY nombre ASC");
        $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $resultado = [];
        foreach ($productos as $prod) {
            $resultado[] = [
                'id' => $prod['id'],
                'nombre' => $prod['nombre'],
                'precio' => floatval($prod['precio'])
            ];
        }

        echo json_encode($resultado);
        exit;
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
        $idIva = (int) ($_POST['idIva'] ?? 1);

        // Validar que los campos obligatorios no estén vacíos
        if (empty($nombre) || empty($categoria)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'El nombre y la categoría son obligatorios.']);
            exit();
        }

        // Validar que precio y stock no sean negativos
        if ($precio < 0) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'El precio no puede ser negativo.']);
            exit();
        }

        if ($stock < 0) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'El stock no puede ser negativo.']);
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
            $producto->setIdIva($idIva);

            // Buscar la categoría para obtener su ID y actualizar
            $categorias = Categoria::obtenerTodas();
            $idCategoria = null;
            foreach ($categorias as $cat) {
                if ($cat->getNombre() === $categoria) {
                    $idCategoria = $cat->getId();
                    break;
                }
            }
            if ($idCategoria !== null) {
                $producto->setIdCategoria($idCategoria);
            }
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
            $producto->setIdIva($idIva);
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
            // Obtenemos los datos antiguos del producto antes de actualizar
            $productoAnterior = null;
            try {
                $pdoAux = new PDO(RUTA, USUARIO, PASS);
                $stmtAux = $pdoAux->prepare("SELECT p.nombre, p.descripcion, p.precio, p.stock, p.idCategoria, p.imagen, p.activo, p.idIva, i.porcentaje as ivaPorcentaje, i.nombre as ivaNombre FROM productos p LEFT JOIN iva i ON p.idIva = i.id WHERE p.id = :id");
                $stmtAux->execute([':id' => $id]);
                $productoAnterior = $stmtAux->fetch(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                // Si falla, continuamos sin datos anteriores
            }

            // Actualizamos el producto existente
            if ($producto->actualizar()) {
                // Guardar en historial de precios si el precio cambió
                if ($productoAnterior && isset($productoAnterior['precio'])) {
                    $precioAnterior = floatval($productoAnterior['precio']);
                    $precioNuevo = floatval($producto->getPrecio());

                    if (round($precioAnterior, 2) !== round($precioNuevo, 2)) {
                        try {
                            $pdoHistorial = new PDO(RUTA, USUARIO, PASS);
                            $adminId = $_SESSION['id'] ?? null;
                            $stmtHistorial = $pdoHistorial->prepare("INSERT INTO productos_historial_precios (id_producto, precio, id_tarifa, usuario_id) VALUES (:id_producto, :precio, :id_tarifa, :usuario_id)");
                            $stmtHistorial->execute([
                                ':id_producto' => $id,
                                ':precio' => $precioNuevo,
                                ':id_tarifa' => null, // Precio base
                                ':usuario_id' => $adminId
                            ]);
                        } catch (Exception $e) {
                            // Si falla el historial, continuamos
                        }
                    }
                }

                // Obtener el nombre del nuevo IVA después de actualizar
                try {
                    $pdoIva = new PDO(RUTA, USUARIO, PASS);
                    $stmtIva = $pdoIva->prepare("SELECT nombre, porcentaje FROM iva WHERE id = :id");
                    $stmtIva->execute([':id' => $producto->getIdIva()]);
                    $ivaData = $stmtIva->fetch(PDO::FETCH_ASSOC);
                    if ($ivaData) {
                        $producto->setIvaNombre($ivaData['nombre'] . ' (' . $ivaData['porcentaje'] . '%)');
                    }
                } catch (Exception $e) {
                    // Si falla, mantenemos el valor anterior
                }

                // Registrar log de modificación de producto
                try {
                    $pdoLog = new PDO(RUTA, USUARIO, PASS);
                    $pdoLog->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    $adminId = $_SESSION['id'] ?? null;
                    $adminNombre = $_SESSION['nombre'] ?? 'Admin';

                    // Comparamos los datos para encontrar qué campos cambiaron
                    $cambios = [];

                    // Obtener categorías para convertir IDs a nombres
                    $categorias = Categoria::obtenerTodas();
                    $mapCategorias = [];
                    foreach ($categorias as $cat) {
                        $mapCategorias[$cat->getId()] = $cat->getNombre();
                    }

                    if ($productoAnterior) {
                        $campos = ['nombre' => 'Nombre', 'descripcion' => 'Descripción', 'precio' => 'Precio', 'stock' => 'Stock', 'idCategoria' => 'Categoría', 'imagen' => 'Imagen', 'activo' => 'Activo', 'idIva' => 'IVA'];
                        foreach ($campos as $campo => $label) {
                            $valorAnterior = $productoAnterior[$campo] ?? '';
                            $valorNuevo = null;
                            switch ($campo) {
                                case 'nombre':
                                    $valorNuevo = $producto->getNombre();
                                    break;
                                case 'descripcion':
                                    $valorNuevo = $producto->getDescripcion();
                                    break;
                                case 'precio':
                                    $valorNuevo = $producto->getPrecio();
                                    break;
                                case 'stock':
                                    $valorNuevo = $producto->getStock();
                                    break;
                                case 'idCategoria':
                                    // Convertir IDs de categoría a nombres
                                    $valorAnterior = $mapCategorias[$valorAnterior] ?? $valorAnterior;
                                    $valorNuevo = $mapCategorias[$producto->getIdCategoria()] ?? $producto->getIdCategoria();
                                    break;
                                case 'imagen':
                                    $valorNuevo = $producto->getImagen();
                                    break;
                                case 'activo':
                                    $valorNuevo = $producto->getActivo();
                                    break;
                                case 'idIva':
                                    // Convertir IDs de IVA a nombres
                                    $valorAnterior = $productoAnterior['ivaNombre'] ?? $valorAnterior;
                                    $valorNuevo = $producto->getIvaNombre() ?? $producto->getIdIva();
                                    break;
                            }
                            // Comparar valores (convertir a string para comparación)
                            // Para precios, usar comparación numérica para evitar problemas de flotantes
                            $sonIguales = false;
                            if ($campo === 'precio') {
                                // Comparar como números con 2 decimales
                                $sonIguales = round(floatval($valorAnterior), 2) === round(floatval($valorNuevo), 2);
                            } else {
                                $sonIguales = strval($valorAnterior) === strval($valorNuevo);
                            }

                            if (!$sonIguales) {
                                $cambios[] = [
                                    'campo' => $label,
                                    'anterior' => $valorAnterior,
                                    'nuevo' => $valorNuevo
                                ];
                            }
                        }
                    }

                    $detalles = count($cambios) > 0 ? json_encode($cambios, JSON_UNESCAPED_UNICODE) : null;

                    $stmtLog = $pdoLog->prepare("INSERT INTO logs_sistema (tipo, usuario_id, usuario_nombre, descripcion, detalles) VALUES ('modificacion_producto', :usuario_id, :usuario_nombre, :descripcion, :detalles)");
                    $stmtLog->execute([
                        ':usuario_id' => $adminId,
                        ':usuario_nombre' => $adminNombre,
                        ':descripcion' => 'Producto modificado: ' . $nombre,
                        ':detalles' => $detalles
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

    // Obtener las tarifas existentes para saber qué columnas de precio leer
    $tarifas = [];
    $columnasPrecios = [];
    try {
        $conexion = ConexionDB::getInstancia()->getConexion();
        $stmtTarifas = $conexion->query("SELECT id, nombre FROM tarifas_prefijadas WHERE activo = 1");
        while ($row = $stmtTarifas->fetch(PDO::FETCH_ASSOC)) {
            $tarifas[] = $row;
            // Generar nombre de columna: precio_ + nombre sin espacios
            $columna = 'precio_' . preg_replace('/[^a-zA-Z0-9]/', '', strtolower($row['nombre']));
            $columnasPrecios[$row['id']] = $columna;
        }
    } catch (Exception $e) {
        // Si falla, continuamos sin tarifas
    }

    // Construir consulta SQL para obtener los precios de las columnas de tarifas
    $sqlPrecios = "SELECT id";
    foreach ($columnasPrecios as $col) {
        $sqlPrecios .= ", `$col`";
    }
    $sqlPrecios .= " FROM productos";

    $preciosTarifas = [];
    try {
        $stmtPrecios = $conexion->query($sqlPrecios);
        while ($row = $stmtPrecios->fetch(PDO::FETCH_ASSOC)) {
            $idProd = $row['id'];
            $preciosTarifas[$idProd] = [];
            foreach ($columnasPrecios as $idTarifa => $col) {
                if (isset($row[$col]) && $row[$col] !== null) {
                    $preciosTarifas[$idProd][$idTarifa] = [
                        'precio' => (float) $row[$col],
                        'es_manual' => 0 // Los precios de columnas se consideran calculados, no manuales
                    ];
                }
            }
        }
    } catch (Exception $e) {
        // Si falla, continuamos con array vacío
    }

    // Recorremos los productos
    foreach ($productos as $prod) {
        // Obtenemos el id de la categoría
        $idCat = (int) $prod->getIdCategoria();
        $idProd = $prod->getId();

        // Añadimos el producto al resultado
        $resultado[] = [
            'id' => $idProd,
            'nombre' => $prod->getNombre(),
            'precio' => (float) $prod->getPrecio(),
            'stock' => (int) $prod->getStock(),
            'idCategoria' => $idCat,
            'categoria' => $mapCategorias[$idCat] ?? '—',   // ← nombre legible
            'activo' => (int) $prod->getActivo(),         // ← 1 = activo, 0 = inactivo
            'imagen' => $prod->getImagen(),
            'idIva' => (int) $prod->getIdIva(),
            'iva' => (float) $prod->getIvaPorcentaje(),   // ← porcentaje numérico para cálculos
            'ivaNombre' => $prod->getIvaNombre(),          // ← nombre legible del tipo de IVA
            'preciosTarifas' => $preciosTarifas[$idProd] ?? [] // ← Precios específicos por tarifa
        ];
    }

    // Devolvemos el resultado en formato JSON
    echo json_encode($resultado);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>