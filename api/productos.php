<?php
/**
 * API de Gestión de Productos.
 * Proporciona endpoints para la consulta, filtrado, previsualización de cambios masivos
 * y persistencia de artículos en el Inventario del TPV.
 * 
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
    /** 
     * PREVISUALIZAR CAMBIO DE IVA
     * Calcula cómo afectaría un cambio en el tipo de impuesto a una muestra de productos.
     * @param int $_GET['previsualizarIVA'] ID del nuevo tipo de IVA.
     */
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
        $stmt = $conexion->query("SELECT p.id, p.nombre, p.precio, p.idIva, p.decimales, i.porcentaje as ivaPorcentaje, i.nombre as ivaNombre FROM productos p LEFT JOIN iva i ON p.idIva = i.id ORDER BY p.nombre ASC LIMIT 100");
        $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $resultado = [];
        foreach ($productos as $prod) {
            $resultado[] = [
                'id' => $prod['id'],
                'nombre' => $prod['nombre'],
                'precio' => floatval($prod['precio']),
                'iva_actual' => floatval($prod['ivaPorcentaje']),
                'iva_nuevo' => floatval($nuevoIva->getPorcentaje()),
                'decimales' => intval($prod['decimales'] ?? 2)
            ];
        }

        echo json_encode(['ok' => true, 'productos' => $resultado]);
        exit();
    }

    /** 
     * PREVISUALIZAR AJUSTE DE PRECIOS
     * Simula una subida o bajada porcentual de precios base en el catálogo.
     * @param float $_GET['previsualizarAjuste'] Porcentaje de variación (ej: 10 para +10%).
     */
    if (isset($_GET['previsualizarAjuste'])) {
        $porcentaje = floatval($_GET['previsualizarAjuste']);

        $conexion = ConexionDB::getInstancia()->getConexion();
        $stmt = $conexion->query("SELECT p.id, p.nombre, p.precio, p.idIva, p.decimales, i.porcentaje as ivaPorcentaje FROM productos p LEFT JOIN iva i ON p.idIva = i.id ORDER BY p.nombre ASC LIMIT 100");
        $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $resultado = [];
        foreach ($productos as $prod) {
            $precioActual = floatval($prod['precio']);
            $prec = intval($prod['decimales'] ?? 2);
            $nuevoPrecio = round($precioActual * (1 + ($porcentaje / 100)), $prec);
            $resultado[] = [
                'id' => $prod['id'],
                'nombre' => $prod['nombre'],
                'precio_actual' => $precioActual,
                'precio_nuevo' => $nuevoPrecio,
                'diferencia' => round($nuevoPrecio - $precioActual, $prec),
                'decimales' => $prec
            ];
        }

        echo json_encode(['ok' => true, 'productos' => $resultado]);
        exit();
    }

    /** 
     * CAMBIAR IVA A TODOS LOS PRODUCTOS
     * Aplica de forma masiva un nuevo tipo de IVA a los productos del sistema.
     * @param int $_GET['cambiarIVA'] ID del nuevo tipo de IVA a establecer.
     * @param string $_GET['excluidos'] IDs de productos separados por coma que no deben actualizarse.
     */
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

    // PROGRAMAR CAMBIO DE IVA PARA FECHA FUTURA
    if (isset($_GET['accion']) && $_GET['accion'] === 'programar_cambio_iva') {
        $ivaId = intval($_POST['iva_id']);
        $fechaProgramada = $_POST['fecha_programada'];
        $productosExcluidos = $_POST['productos_excluidos'] ?? '';

        if (!$ivaId || !$fechaProgramada) {
            echo json_encode(['error' => 'Faltan datos requeridos']);
            exit();
        }

        // Verificar que el tipo de IVA existe
        $nuevoIva = Iva::buscarPorId($ivaId);
        if (!$nuevoIva) {
            echo json_encode(['error' => 'Tipo de IVA inválido']);
            exit();
        }

        $conexion = ConexionDB::getInstancia()->getConexion();

        // Crear tabla si no existe
        $conexion->exec("
            CREATE TABLE IF NOT EXISTS cambios_iva_programados (
                id INT AUTO_INCREMENT PRIMARY KEY,
                iva_id INT NOT NULL,
                fecha_programada DATETIME NOT NULL,
                productos_excluidos TEXT,
                usuario_id INT,
                usuario_nombre VARCHAR(100),
                estado ENUM('pendiente', 'aplicado', 'cancelado') DEFAULT 'pendiente',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Insertar el cambio programado
        $stmt = $conexion->prepare("INSERT INTO cambios_iva_programados (iva_id, fecha_programada, productos_excluidos, usuario_id, usuario_nombre) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $ivaId,
            $fechaProgramada,
            $productosExcluidos,
            $_SESSION['idUsuario'] ?? null,
            $_SESSION['nombreUsuario'] ?? 'Desconocido'
        ]);

        $fecha = new DateTime($fechaProgramada);
        echo json_encode([
            'ok' => true,
            'fecha_formateada' => $fecha->format('d/m/Y H:i')
        ]);
        exit();
    }

    // APLICAR CAMBIOS DE IVA PROGRAMADOS (se llama al cargar la página)
    if (isset($_GET['accion']) && $_GET['accion'] === 'aplicar_cambios_iva_programados') {
        $conexion = ConexionDB::getInstancia()->getConexion();

        // Verificar si la tabla existe
        $tables = $conexion->query("SHOW TABLES LIKE 'cambios_iva_programados'")->fetchAll();
        if (empty($tables)) {
            echo json_encode(['ok' => true, 'aplicados' => 0]);
            exit();
        }

        // Obtener cambios pendientes que ya deberían estar aplicados
        $stmt = $conexion->query("
            SELECT * FROM cambios_iva_programados 
            WHERE estado = 'pendiente' AND fecha_programada <= NOW()
        ");
        $cambios = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $aplicados = 0;
        $nuevosIVA = [];

        foreach ($cambios as $cambio) {
            $ivaId = $cambio['iva_id'];
            $excluidos = $cambio['productos_excluidos'] ? array_map('intval', explode(',', $cambio['productos_excluidos'])) : [];

            // Obtener el nuevo IVA
            $stmtIva = $conexion->prepare("SELECT porcentaje FROM iva WHERE id = ?");
            $stmtIva->execute([$ivaId]);
            $ivaNuevo = $stmtIva->fetch(PDO::FETCH_ASSOC);
            $nuevoPorcentaje = $ivaNuevo ? intval($ivaNuevo['porcentaje']) : 21;

            // Obtener productos que se van a actualizar (para devolver sus nuevos IVA)
            if (count($excluidos) > 0) {
                $placeholders = implode(',', array_fill(0, count($excluidos), '?'));
                $stmtProductos = $conexion->prepare("SELECT id FROM productos WHERE id NOT IN ($placeholders)");
                $stmtProductos->execute($excluidos);
            } else {
                $stmtProductos = $conexion->query("SELECT id FROM productos");
            }
            $productosActualizados = $stmtProductos->fetchAll(PDO::FETCH_ASSOC);

            // Guardar los nuevos IVA de cada producto
            foreach ($productosActualizados as $prod) {
                $nuevosIVA[$prod['id']] = $nuevoPorcentaje;
            }

            // Aplicar el cambio de IVA
            if (count($excluidos) > 0) {
                $placeholders = implode(',', array_fill(0, count($excluidos), '?'));
                $stmtUpdate = $conexion->prepare("UPDATE productos SET idIva = ? WHERE id NOT IN ($placeholders)");
                $params = array_merge([$ivaId], $excluidos);
                $stmtUpdate->execute($params);
            } else {
                $stmtUpdate = $conexion->prepare("UPDATE productos SET idIva = ?");
                $stmtUpdate->execute([$ivaId]);
            }

            // Marcar como aplicado
            $stmtMarca = $conexion->prepare("UPDATE cambios_iva_programados SET estado = 'aplicado' WHERE id = ?");
            $stmtMarca->execute([$cambio['id']]);

            $aplicados++;
        }

        echo json_encode(['ok' => true, 'aplicados' => $aplicados, 'nuevosIVA' => $nuevosIVA]);
        exit();
    }

    // OBTENER TODOS LOS CAMBIOS DE IVA PROGRAMADOS
    if (isset($_GET['accion']) && $_GET['accion'] === 'obtener_cambios_iva_programados') {
        $conexion = ConexionDB::getInstancia()->getConexion();

        // Verificar si la tabla existe
        $tables = $conexion->query("SHOW TABLES LIKE 'cambios_iva_programados'")->fetchAll();
        if (empty($tables)) {
            echo json_encode(['ok' => true, 'cambios' => []]);
            exit();
        }

        // Obtener todos los cambios ordenados por fecha
        $stmt = $conexion->query("
            SELECT c.*, i.porcentaje as iva_porcentaje, i.nombre as iva_nombre 
            FROM cambios_iva_programados c
            LEFT JOIN iva i ON c.iva_id = i.id
            ORDER BY c.fecha_programada DESC
        ");
        $cambios = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calcular productos afectados para cada cambio
        $totalProductos = $conexion->query("SELECT COUNT(*) as total FROM productos")->fetch(PDO::FETCH_ASSOC);
        $total = $totalProductos ? intval($totalProductos['total']) : 0;

        foreach ($cambios as &$cambio) {
            $excluidos = $cambio['productos_excluidos'] ? explode(',', $cambio['productos_excluidos']) : [];
            $cambio['productos_afectados'] = $total - count($excluidos);
        }

        echo json_encode(['ok' => true, 'cambios' => $cambios]);
        exit();
    }

    // OBTENER UN CAMBIO DE IVA PROGRAMADO PARA EDITAR
    if (isset($_GET['accion']) && $_GET['accion'] === 'obtener_cambio_iva_programado') {
        $conexion = ConexionDB::getInstancia()->getConexion();
        $id = intval($_GET['id']);

        $stmt = $conexion->prepare("
            SELECT c.*, i.porcentaje as iva_porcentaje, i.nombre as iva_nombre 
            FROM cambios_iva_programados c
            LEFT JOIN iva i ON c.iva_id = i.id
            WHERE c.id = ?
        ");
        $stmt->execute([$id]);
        $cambio = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($cambio) {
            echo json_encode(['ok' => true, 'cambio' => $cambio]);
        } else {
            echo json_encode(['ok' => false, 'error' => 'Cambio no encontrado']);
        }
        exit();
    }

    // ELIMINAR UN CAMBIO DE IVA PROGRAMADO
    if (isset($_GET['accion']) && $_GET['accion'] === 'eliminar_cambio_iva_programado') {
        $conexion = ConexionDB::getInstancia()->getConexion();
        $id = intval($_GET['id']);

        $stmt = $conexion->prepare("DELETE FROM cambios_iva_programados WHERE id = ? AND estado = 'pendiente'");
        $result = $stmt->execute([$id]);

        if ($result) {
            echo json_encode(['ok' => true]);
        } else {
            echo json_encode(['ok' => false, 'error' => 'Error al eliminar']);
        }
        exit();
    }

    // ACTUALIZAR UN CAMBIO DE IVA PROGRAMADO
    if (isset($_GET['accion']) && $_GET['accion'] === 'actualizar_cambio_iva_programado') {
        $conexion = ConexionDB::getInstancia()->getConexion();
        $id = intval($_GET['id']);
        $iva_id = intval($_POST['iva_id']);
        $fecha_programada = $_POST['fecha_programada'];
        $productos_excluidos = $_POST['productos_excluidos'] ?? '';

        $stmt = $conexion->prepare("
            UPDATE cambios_iva_programados 
            SET iva_id = ?, fecha_programada = ?, productos_excluidos = ?
            WHERE id = ? AND estado = 'pendiente'
        ");
        $result = $stmt->execute([$iva_id, $fecha_programada, $productos_excluidos, $id]);

        if ($result) {
            echo json_encode(['ok' => true]);
        } else {
            echo json_encode(['ok' => false, 'error' => 'Error al actualizar']);
        }
        exit();
    }

    // OBTENER PRODUCTOS AFECTADOS POR UN CAMBIO DE IVA PROGRAMADO
    if (isset($_GET['accion']) && $_GET['accion'] === 'obtener_productos_cambio_iva') {
        try {
            $conexion = ConexionDB::getInstancia()->getConexion();
            $id = intval($_GET['id']);

            // Primero obtener info del cambio
            $stmt = $conexion->prepare("
                SELECT c.*, i.porcentaje as iva_porcentaje, i.nombre as iva_nombre 
                FROM cambios_iva_programados c
                LEFT JOIN iva i ON c.iva_id = i.id
                WHERE c.id = ?
            ");
            $stmt->execute([$id]);
            $cambio = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$cambio) {
                echo json_encode(['ok' => false, 'error' => 'Cambio no encontrado']);
                exit();
            }

            // Obtener productos excluidos
            $excluidos = [];
            if (!empty($cambio['productos_excluidos'])) {
                $excluidos = array_map('intval', explode(',', $cambio['productos_excluidos']));
            }

            // Obtener productos que tendrán cambio de IVA (todos excepto los excluidos)
            $query = "
                SELECT p.id, p.nombre, 
                       p.idIva as iva_actual_id,
                       COALESCE(i_act.porcentaje, 0) as iva_anterior,
                       ? as iva_nuevo
                FROM productos p
                LEFT JOIN iva i_act ON p.idIva = i_act.id
            ";

            $params = [floatval($cambio['iva_porcentaje'])];

            if (count($excluidos) > 0) {
                $placeholders = str_repeat('?,', count($excluidos) - 1) . '?';
                $query .= " WHERE p.id NOT IN ($placeholders)";
                $params = array_merge($params, $excluidos);
            }

            if (count($excluidos) > 0) {
                $placeholders = str_repeat('?,', count($excluidos) - 1) . '?';
                $query .= " AND p.id NOT IN ($placeholders)";
                $params = array_merge($params, $excluidos);
            }

            $query .= " ORDER BY p.nombre LIMIT 100";

            $stmt = $conexion->prepare($query);
            $stmt->execute($params);
            $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['ok' => true, 'productos' => $productos]);
            exit();
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
            exit();
        }
    }

    // PROGRAMAR AJUSTE DE PRECIOS PARA FECHA FUTURA
    if (isset($_GET['accion']) && $_GET['accion'] === 'programar_ajuste_precios') {
        $porcentaje = floatval($_POST['porcentaje']);
        $fechaProgramada = $_POST['fecha_programada'];
        $productosExcluidos = $_POST['productos_excluidos'] ?? '';

        $conexion = ConexionDB::getInstancia()->getConexion();

        // Verificar si la tabla existe y crearla si no
        $tables = $conexion->query("SHOW TABLES LIKE 'cambios_precios_programados'")->fetchAll();
        if (empty($tables)) {
            $conexion->exec("
                CREATE TABLE IF NOT EXISTS cambios_precios_programados (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    porcentaje DECIMAL(10,2) NOT NULL,
                    fecha_programada DATETIME NOT NULL,
                    productos_excluidos TEXT,
                    estado ENUM('pendiente', 'aplicado') DEFAULT 'pendiente',
                    usuario_id INT,
                    usuario_nombre VARCHAR(100),
                    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        }

        // Insertar el cambio programado
        $stmt = $conexion->prepare("INSERT INTO cambios_precios_programados (porcentaje, fecha_programada, productos_excluidos, usuario_id, usuario_nombre) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $porcentaje,
            $fechaProgramada,
            $productosExcluidos,
            $_SESSION['idUsuario'] ?? null,
            $_SESSION['nombreUsuario'] ?? 'Desconocido'
        ]);

        $fechaFormateada = date('d/m/Y H:i', strtotime($fechaProgramada));
        echo json_encode(['ok' => true, 'fecha_formateada' => $fechaFormateada]);
        exit();
    }

    // OBTENER TODOS LOS AJUSTES DE PRECIOS PROGRAMADOS
    if (isset($_GET['accion']) && $_GET['accion'] === 'obtener_ajustes_precios_programados') {
        $conexion = ConexionDB::getInstancia()->getConexion();

        // Verificar si la tabla existe
        $tables = $conexion->query("SHOW TABLES LIKE 'cambios_precios_programados'")->fetchAll();
        if (empty($tables)) {
            echo json_encode(['ok' => true, 'ajustes' => []]);
            exit();
        }

        // Obtener todos los ajustes ordenados por fecha
        $stmt = $conexion->query("
            SELECT * FROM cambios_precios_programados
            ORDER BY fecha_programada DESC
        ");
        $ajustes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calcular productos afectados para cada ajuste
        $totalProductos = $conexion->query("SELECT COUNT(*) as total FROM productos")->fetch(PDO::FETCH_ASSOC);
        $total = $totalProductos ? intval($totalProductos['total']) : 0;

        foreach ($ajustes as &$ajuste) {
            $excluidos = $ajuste['productos_excluidos'] ? explode(',', $ajuste['productos_excluidos']) : [];
            $ajuste['productos_afectados'] = $total - count($excluidos);
        }

        echo json_encode(['ok' => true, 'ajustes' => $ajustes]);
        exit();
    }

    // ELIMINAR UN AJUSTE DE PRECIOS PROGRAMADO
    if (isset($_GET['accion']) && $_GET['accion'] === 'eliminar_ajuste_precios_programado') {
        $conexion = ConexionDB::getInstancia()->getConexion();
        $id = intval($_GET['id']);

        $stmt = $conexion->prepare("DELETE FROM cambios_precios_programados WHERE id = ? AND estado = 'pendiente'");
        $result = $stmt->execute([$id]);

        if ($result) {
            echo json_encode(['ok' => true]);
        } else {
            echo json_encode(['ok' => false, 'error' => 'Error al eliminar']);
        }
        exit();
    }

    // OBTENER UN AJUSTE DE PRECIOS PROGRAMADO PARA EDITAR
    if (isset($_GET['accion']) && $_GET['accion'] === 'obtener_ajuste_precios_programado') {
        $conexion = ConexionDB::getInstancia()->getConexion();
        $id = intval($_GET['id']);

        $stmt = $conexion->prepare("SELECT * FROM cambios_precios_programados WHERE id = ?");
        $stmt->execute([$id]);
        $ajuste = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($ajuste) {
            echo json_encode(['ok' => true, 'ajuste' => $ajuste]);
        } else {
            echo json_encode(['ok' => false, 'error' => 'Ajuste no encontrado']);
        }
        exit();
    }

    // ACTUALIZAR UN AJUSTE DE PRECIOS PROGRAMADO
    if (isset($_GET['accion']) && $_GET['accion'] === 'actualizar_ajuste_precios_programado') {
        $conexion = ConexionDB::getInstancia()->getConexion();
        $id = intval($_GET['id']);
        $porcentaje = floatval($_POST['porcentaje']);
        $fecha_programada = $_POST['fecha_programada'];
        $productos_excluidos = $_POST['productos_excluidos'] ?? '';

        $stmt = $conexion->prepare("
            UPDATE cambios_precios_programados
            SET porcentaje = ?, fecha_programada = ?, productos_excluidos = ?
            WHERE id = ? AND estado = 'pendiente'
        ");
        $result = $stmt->execute([$porcentaje, $fecha_programada, $productos_excluidos, $id]);

        if ($result) {
            echo json_encode(['ok' => true]);
        } else {
            echo json_encode(['ok' => false, 'error' => 'Error al actualizar']);
        }
        exit();
    }

    // OBTENER PRODUCTOS AFECTADOS POR UN AJUSTE DE PRECIOS PROGRAMADO
    if (isset($_GET['accion']) && $_GET['accion'] === 'obtener_productos_ajuste_precios') {
        try {
            $conexion = ConexionDB::getInstancia()->getConexion();
            $id = intval($_GET['id']);

            // Obtener info del ajuste
            $stmt = $conexion->prepare("SELECT * FROM cambios_precios_programados WHERE id = ?");
            $stmt->execute([$id]);
            $ajuste = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$ajuste) {
                echo json_encode(['ok' => false, 'error' => 'Ajuste no encontrado']);
                exit();
            }

            // Obtener productos excluidos
            $excluidos = [];
            if (!empty($ajuste['productos_excluidos'])) {
                $excluidos = array_map('intval', explode(',', $ajuste['productos_excluidos']));
            }

            $porcentaje = floatval($ajuste['porcentaje']);

            // Obtener productos que tendrán ajuste de precio (todos excepto los excluidos)
            $query = "
                SELECT p.id, p.nombre, 
                       p.precio as precio_anterior,
                       ROUND(p.precio * (1 + ? / 100), p.decimales) as precio_nuevo
                FROM productos p
                WHERE p.precio > 0
            ";

            $params = [$porcentaje];

            if (count($excluidos) > 0) {
                $placeholders = str_repeat('?,', count($excluidos) - 1) . '?';
                $query .= " AND p.id NOT IN ($placeholders)";
                $params = array_merge($params, $excluidos);
            }

            $query .= " ORDER BY p.nombre LIMIT 100";

            $stmt = $conexion->prepare($query);
            $stmt->execute($params);
            $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['ok' => true, 'productos' => $productos]);
            exit();
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
            exit();
        }
    }

    // APLICAR AJUSTES DE PRECIOS PROGRAMADOS (se llama al cargar la página)
    if (isset($_GET['accion']) && $_GET['accion'] === 'aplicar_ajustes_precios_programados') {
        $conexion = ConexionDB::getInstancia()->getConexion();

        // Verificar si la tabla existe
        $tables = $conexion->query("SHOW TABLES LIKE 'cambios_precios_programados'")->fetchAll();
        if (empty($tables)) {
            echo json_encode(['ok' => true, 'aplicados' => 0]);
            exit();
        }

        // Obtener ajustes pendientes que ya deberían estar aplicados
        $stmt = $conexion->query("
            SELECT * FROM cambios_precios_programados
            WHERE estado = 'pendiente' AND fecha_programada <= NOW()
        ");
        $ajustes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $aplicados = 0;
        foreach ($ajustes as $ajuste) {
            $porcentaje = floatval($ajuste['porcentaje']);
            $excluidos = $ajuste['productos_excluidos'] ? array_map('intval', explode(',', $ajuste['productos_excluidos'])) : [];

            // Aplicar el ajuste de precios
            if (count($excluidos) > 0) {
                $placeholders = implode(',', array_fill(0, count($excluidos), '?'));
                $stmtUpdate = $conexion->prepare("UPDATE productos SET precio = ROUND(precio * (1 + ? / 100), decimales) WHERE id NOT IN ($placeholders)");
                $params = array_merge([$porcentaje], $excluidos);
                $stmtUpdate->execute($params);
            } else {
                $stmtUpdate = $conexion->prepare("UPDATE productos SET precio = ROUND(precio * (1 + ? / 100), decimales)");
                $stmtUpdate->execute([$porcentaje]);
            }

            // Marcar como aplicado
            $stmtMarca = $conexion->prepare("UPDATE cambios_precios_programados SET estado = 'aplicado' WHERE id = ?");
            $stmtMarca->execute([$ajuste['id']]);

            $aplicados++;
        }

        echo json_encode(['ok' => true, 'aplicados' => $aplicados]);
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
            $prec = intval($prod['decimales'] ?? 2);
            $nuevoPrecio = round($prod['precio'] * (1 + ($porcentaje / 100)), $prec);

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
            echo json_encode(['error' => 'ID de producto inválido']);
            exit;
        }

        $conexion = ConexionDB::getInstancia()->getConexion();

        // Filtro por tarifa (opcional)
        $idTarifa = isset($_GET['id_tarifa']) ? $_GET['id_tarifa'] : '';
        $where = "WHERE h.id_producto = :id_producto";
        $params = [':id_producto' => $idProducto];

        if ($idTarifa !== '') {
            if ($idTarifa === 'base') {
                $where .= " AND h.id_tarifa IS NULL";
            } else {
                $where .= " AND h.id_tarifa = :id_tarifa";
                $params[':id_tarifa'] = intval($idTarifa);
            }
        }

        // Paginación
        $pagina = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;
        $por_pagina = isset($_GET['por_pagina']) ? intval($_GET['por_pagina']) : 6;
        $offset = ($pagina - 1) * $por_pagina;

        // Obtener el total para la paginación
        $stmtTotal = $conexion->prepare("SELECT COUNT(*) as total FROM productos_historial_precios h $where");
        foreach ($params as $key => $val) {
            $stmtTotal->bindValue($key, $val);
        }
        $stmtTotal->execute();
        $total = $stmtTotal->fetch(PDO::FETCH_ASSOC)['total'];

        // Obtener los registros paginados
        $stmt = $conexion->prepare("
            SELECT h.id, h.precio, h.fecha_cambio, h.id_tarifa, t.nombre as tarifa_nombre, u.nombre as usuario_nombre,
                   (SELECT MIN(h2.fecha_cambio) 
                    FROM productos_historial_precios h2 
                    WHERE h2.id_producto = h.id_producto 
                      AND (h2.id_tarifa = h.id_tarifa OR (h2.id_tarifa IS NULL AND h.id_tarifa IS NULL))
                      AND h2.fecha_cambio > h.fecha_cambio
                   ) as fecha_hasta
            FROM productos_historial_precios h
            LEFT JOIN tarifas_prefijadas t ON h.id_tarifa = t.id
            LEFT JOIN usuarios u ON h.usuario_id = u.id
            $where
            ORDER BY h.fecha_cambio DESC
            LIMIT $por_pagina OFFSET $offset
        ");
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->execute();

        $historial = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $historial[] = [
                'precio' => floatval($row['precio']),
                'valido_desde' => $row['fecha_cambio'],
                'valido_hasta' => $row['fecha_hasta'],
                'id_tarifa' => $row['id_tarifa'],
                'tarifa' => $row['tarifa_nombre'] ?? 'Precio Base',
                'usuario' => $row['usuario_nombre'] ?? 'Sistema'
            ];
        }

        echo json_encode([
            'ok' => true,
            'historial' => $historial,
            'total' => intval($total)
        ]);
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
        $decimales = (int) ($_POST['decimales'] ?? 2);

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
            $producto->setDecimales($decimales);

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
            $producto->setDecimales($decimales);
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
                    $prec = intval($producto->getDecimales());

                    if (round($precioAnterior, $prec) !== round($precioNuevo, $prec)) {
                        try {
                            $pdoHistorial = new PDO(RUTA, USUARIO, PASS);
                            $adminId = $_SESSION['idUsuario'] ?? $_SESSION['id'] ?? null;
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

                    /**
                     * Registra un evento relacionado con productos en la auditoría del sistema.
                     * 
                     * @param PDO $pdo Instancia de conexión a la base de datos.
                     * @param string $tipo Tipo de operación (ej: 'ajuste_precio', 'actualizacion_stock').
                     * @param int|null $producto_id Identificador del producto afectado.
                     * @param string|null $producto_nombre Nombre del producto para referencia rápida.
                     * @param string $descripcion Breve texto explicativo de la acción.
                     * @param mixed|null $detalles Información técnica o valores previos en JSON.
                     * @return void
                     */
                    function registrarLogProducto($pdo, $tipo, $producto_id, $producto_nombre, $descripcion, $detalles = null)
                    {
                        $stmtLog = $pdo->prepare("INSERT INTO logs_sistema (tipo, usuario_id, usuario_nombre, descripcion, detalles) VALUES (:tipo, :usuario_id, :usuario_nombre, :descripcion, :detalles)");
                        $stmtLog->execute([
                            ':tipo' => $tipo,
                            ':usuario_id' => $_SESSION['id'] ?? null,
                            ':usuario_nombre' => $_SESSION['nombre'] ?? 'Admin',
                            ':descripcion' => $descripcion,
                            ':detalles' => $detalles
                        ]);
                    }

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
            'decimales' => (int) $prod->getDecimales(),    // ← precision configured
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