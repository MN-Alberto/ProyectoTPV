<?php
/**
 * API de Gestión de Clientes Fidelizados.
 * Centraliza las operaciones de registro, consulta de histórico de compras
 * y validación de identidad para clientes habituales del establecimiento.
 * 
 * @author Alberto Méndez
 * @version 1.0 (05/03/2026)
 */

/**
 * CONFIGURACIÓN INICIAL
 * 
 * Se habilitan todos los errores para desarrollo.
 * En producción se debe establecer display_errors a 0
 * por seguridad y evitar fuga de información.
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Tipo de respuesta estandar para toda la API
header('Content-Type: application/json; charset=utf-8');

try {
    /**
     * CONEXIÓN A BASE DE DATOS
     * 
     * Se establece conexión con codificación UTF8MB4 para soportar
     * emojis y caracteres especiales en nombres y direcciones.
     * Se activa el modo excepciones para manejo uniforme de errores.
     */
    $pdo = new PDO('mysql:host=localhost;dbname=ProyectoTPV', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET NAMES utf8mb4");

    /**
     * Valida un DNI español (8 dígitos + 1 letra).
     * La letra se calcula dividiendo el número entre 23 y cogiendo el resto.
     * @param string $dni El DNI a validar
     * @return bool True si el DNI es válido
     */
    function validarDNI($dni)
    {
        // Verificar formato: 8 dígitos + 1 letra
        if (!preg_match('/^\\d{8}[A-Z]$/', strtoupper($dni))) {
            return false;
        }

        // Extraer números y letra
        $dni = strtoupper($dni);
        $numeros = intval(substr($dni, 0, 8));
        $letra = substr($dni, 8, 1);

        // Calcular la letra correcta según el algoritmo español
        $letras = 'TRWAGMYFPDXBNJZSQVHLCKE';
        $letraCorrecta = $letras[$numeros % 23];

        return $letra === $letraCorrecta;
    }

    /**
     * ==============================================
     * METODO POST: Crear nuevo cliente
     * ==============================================
     * 
     * Registra un nuevo cliente en el sistema. Realiza validación
     * previa del DNI según el algoritmo oficial español y verifica
     * que no exista previamente otro cliente con el mismo documento.
     */
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $dni = strtoupper(trim($_POST['dni'] ?? ''));
        $nombre = $_POST['nombre'] ?? '';
        $apellidos = $_POST['apellidos'] ?? '';
        $direccion = $_POST['direccion'] ?? '';
        $fecha_alta = $_POST['fecha_alta'] ?? date('Y-m-d');

        if (empty($dni) || empty($nombre) || empty($apellidos)) {
            http_response_code(400);
            echo json_encode(['error' => 'DNI, nombre y apellidos son obligatorios']);
            exit;
        }

        // Validar formato DNI (8 dígitos + letra válida)
        if (!validarDNI($dni)) {
            http_response_code(400);
            echo json_encode(['error' => 'El DNI debe tener 8 dígitos seguidos de una letra válida (ejemplo: 12345678A)']);
            exit;
        }

        /**
         * VERIFICACIÓN DE DUPLICADOS
         * 
         * Solo se verifica que no existan clientes ACTIVOS con el mismo DNI.
         * Los clientes eliminados (activo=0) no cuentan para esta comprobación.
         * Se devuelve codigo HTTP 409 Conflict en caso de duplicado.
         */
        $stmt = $pdo->prepare("SELECT id FROM clientes WHERE dni = ? AND activo = 1");
        $stmt->execute([$dni]);
        if ($stmt->fetch()) {
            http_response_code(409);
            echo json_encode(['error' => 'Ya existe un cliente con este DNI']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO clientes (dni, nombre, apellidos, direccion, fecha_alta, productos_comprados, compras_realizadas, activo) VALUES (?, ?, ?, ?, ?, 0, 0, 1)");

        if ($stmt->execute([$dni, $nombre, $apellidos, $direccion, $fecha_alta])) {
            echo json_encode(['ok' => true, 'id' => $pdo->lastInsertId()]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error al guardar el cliente']);
        }
        exit;
    }

    /**
     * ==============================================
     * METODO PUT: Actualizar cliente existente
     * ==============================================
     * 
     * Actualiza los datos de un cliente ya registrado.
     * Se usa parse_str porque los navegadores no envian datos formulario
     * nativamente por metodo PUT, se leen desde el cuerpo de la petición.
     */
    if ($_SERVER['REQUEST_METHOD'] === 'PUT' && isset($_GET['actualizar'])) {
        parse_str(file_get_contents("php://input"), $_PUT);

        $id = $_PUT['id'] ?? '';
        $dni = strtoupper(trim($_PUT['dni'] ?? ''));
        $nombre = $_PUT['nombre'] ?? '';
        $apellidos = $_PUT['apellidos'] ?? '';
        $direccion = $_PUT['direccion'] ?? '';
        $fecha_alta = $_PUT['fecha_alta'] ?? '';
        $puntos = isset($_PUT['puntos']) ? (int) $_PUT['puntos'] : null;

        if (empty($id) || empty($dni) || empty($nombre) || empty($apellidos)) {
            http_response_code(400);
            echo json_encode(['error' => 'Todos los campos son obligatorios']);
            exit;
        }

        /**
         * PROTECCIÓN DE INTEGRIDAD
         * 
         * Los puntos de fidelidad nunca pueden ser negativos.
         * Esta validación evita errores de datos corruptos.
         */
        if ($puntos !== null && $puntos < 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Los puntos del cliente no pueden ser negativos']);
            exit;
        }

        // Validar formato DNI (8 dígitos + letra válida)
        if (!validarDNI($dni)) {
            http_response_code(400);
            echo json_encode(['error' => 'El DNI debe tener 8 dígitos seguidos de una letra válida (ejemplo: 12345678A)']);
            exit;
        }

        // Verificar si el DNI ya existe en otro cliente
        $stmt = $pdo->prepare("SELECT id FROM clientes WHERE dni = ? AND id != ? AND activo = 1");
        $stmt->execute([$dni, $id]);
        if ($stmt->fetch()) {
            http_response_code(409);
            echo json_encode(['error' => 'Ya existe otro cliente con este DNI']);
            exit;
        }

        if ($puntos !== null) {
            $stmt = $pdo->prepare("UPDATE clientes SET dni = ?, nombre = ?, apellidos = ?, direccion = ?, fecha_alta = ?, puntos = ? WHERE id = ?");
            $success = $stmt->execute([$dni, $nombre, $apellidos, $direccion, $fecha_alta, $puntos, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE clientes SET dni = ?, nombre = ?, apellidos = ?, direccion = ?, fecha_alta = ? WHERE id = ?");
            $success = $stmt->execute([$dni, $nombre, $apellidos, $direccion, $fecha_alta, $id]);
        }

        if ($success) {
            echo json_encode(['ok' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error al actualizar el cliente']);
        }
        exit;
    }

    /**
     * ==============================================
     * METODO DELETE: Eliminar cliente definitivamente
     * ==============================================
     * 
     * Realiza una eliminación FISICA permanente del cliente.
     * NOTA: En versiones posteriores este sistema cambiará a
     * eliminación lógica (marcar como activo=0).
     */
    if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && isset($_GET['eliminar'])) {
        $id = $_GET['eliminar'];

        $stmt = $pdo->prepare("DELETE FROM clientes WHERE id = ?");

        if ($stmt->execute([$id])) {
            echo json_encode(['ok' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error al eliminar el cliente']);
        }
        exit;
    }

    /**
     * ==============================================
     * METODO PUT: Reactivar cliente eliminado
     * ==============================================
     * 
     * Vuelve a marcar un cliente como activo. Esta acción
     * es solo para clientes que fueron eliminados lógicamente.
     */
    if ($_SERVER['REQUEST_METHOD'] === 'PUT' && isset($_GET['reactivar'])) {
        $id = $_GET['reactivar'];

        $stmt = $pdo->prepare("UPDATE clientes SET activo = 1 WHERE id = ?");

        if ($stmt->execute([$id])) {
            echo json_encode(['ok' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error al activar el cliente']);
        }
        exit;
    }

    /**
     * MANEJADOR DE SOLICITUDES GET (CONSULTA DE COMPRAS)
     * Recupera el histórico detallado de tickets asociados a un DNI de cliente.
     */
    if (isset($_GET['compras']) && isset($_GET['dni'])) {
        $dni = $_GET['dni'];

        // Primero verificar que el cliente existe
        $stmt = $pdo->prepare("SELECT id FROM clientes WHERE dni = ? AND activo = 1");
        $stmt->execute([$dni]);
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$cliente) {
            http_response_code(404);
            echo json_encode(['error' => 'Cliente no encontrado']);
            exit;
        }

        // Obtener las ventas del cliente
        $stmt = $pdo->prepare("
            SELECT v.*, u.nombre as usuario_nombre 
            FROM ventas v 
            LEFT JOIN usuarios u ON v.idUsuario = u.id 
            WHERE v.cliente_dni = ? AND v.estado = 'completada' 
            ORDER BY v.fecha DESC
        ");
        $stmt->execute([$dni]);
        $ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Para cada venta, obtener las líneas de productos
        foreach ($ventas as &$venta) {
            $stmtLineas = $pdo->prepare("
                SELECT lv.*, COALESCE(lv.nombreProducto, p.nombre) as producto_nombre, p.imagen as producto_imagen
                FROM lineasVenta lv
                LEFT JOIN productos p ON lv.idProducto = p.id
                WHERE lv.idVenta = ?
            ");
            $stmtLineas->execute([$venta['id']]);
            $lineas = $stmtLineas->fetchAll(PDO::FETCH_ASSOC);

            // Calcular precio con IVA para cada línea
            foreach ($lineas as &$linea) {
                $iva = isset($linea['iva']) ? floatval($linea['iva']) : 21;
                $precioBase = floatval($linea['precioUnitario']);
                $precioConIva = $precioBase * (1 + $iva / 100);
                $linea['precioUnitarioConIva'] = round($precioConIva, 2);
                $linea['subtotalConIva'] = round($precioConIva * $linea['cantidad'], 2);
            }

            $venta['lineas'] = $lineas;
        }

        echo json_encode($ventas);
        exit;
    }

    // Manejar GET para obtener clientes por DNI (búsqueda parcial)
    if (isset($_GET['dni']) && !isset($_GET['compras'])) {
        $dni = $_GET['dni'];

        // Dar más tiempo para búsquedas sobre datasets grandes
        set_time_limit(60);

        // Si se pide paginación server-side (admin), devolver con metadatos
        if (isset($_GET['pagina'])) {
            $pagina = max(1, intval($_GET['pagina']));
            $porPagina = min(50, max(1, intval($_GET['porPagina'] ?? 6)));
            $offset = ($pagina - 1) * $porPagina;

            // Count total — WHERE en orden del índice (activo, dni)
            $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM clientes WHERE dni LIKE ?");
            $stmtCount->execute([$dni . '%']);
            $total = (int) $stmtCount->fetchColumn();

            // Fetch página — solo columnas necesarias, WHERE en orden del índice
            $stmt = $pdo->prepare("SELECT id, dni, nombre, apellidos, direccion, fecha_alta, productos_comprados, compras_realizadas, puntos, activo FROM clientes WHERE dni LIKE ? ORDER BY dni ASC LIMIT ? OFFSET ?");
            $stmt->bindValue(1, $dni . '%', PDO::PARAM_STR);
            $stmt->bindValue(2, $porPagina, PDO::PARAM_INT);
            $stmt->bindValue(3, $offset, PDO::PARAM_INT);
            $stmt->execute();
            $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'clientes' => $clientes,
                'total' => $total,
                'pagina' => $pagina,
                'porPagina' => $porPagina,
                'totalPaginas' => $total > 0 ? (int) ceil($total / $porPagina) : 1
            ]);
            exit;
        }

        // Búsqueda simple sin paginación (compatibilidad cajero)
        $stmt = $pdo->prepare("SELECT id, dni, nombre, apellidos, direccion, fecha_alta, productos_comprados, compras_realizadas, puntos, activo FROM clientes WHERE dni LIKE ? ORDER BY dni ASC LIMIT 20");
        $stmt->execute([$dni . '%']);
        $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($clientes && count($clientes) > 0) {
            echo json_encode($clientes);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'No se encontraron clientes con ese DNI']);
        }
        exit;
    }

    // Verificar si se pide un cliente específico
    if (isset($_GET['id'])) {
        $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($cliente) {
            echo json_encode($cliente);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Cliente no encontrado']);
        }
        exit;
    }

    // Obtener clientes activos con paginación server-side
    $pagina = max(1, intval($_GET['pagina'] ?? 1));
    $porPagina = min(50, max(1, intval($_GET['porPagina'] ?? 6)));
    $offset = ($pagina - 1) * $porPagina;

    // ✅ OPTIMIZADO: Conteos ultra-rápidos usando solo los índices (Covering Index Count)
    // En lugar de SUM(CASE) que lee 10M de filas, hacemos dos COUNT indexados.
    $totalActivos = (int) $pdo->query("SELECT COUNT(*) FROM clientes WHERE activo = 1")->fetchColumn();
    $totalInactivos = (int) $pdo->query("SELECT COUNT(*) FROM clientes WHERE activo = 0")->fetchColumn();
    $totalTodos = $totalActivos + $totalInactivos;
    $total = $totalActivos; // Por defecto mostramos activos

    // ✅ TRUCO DE INVERSIÓN: Si pides las últimas páginas, buscamos desde el final
    $invertirOrden = false;
    if ($total > $porPagina * 2 && $offset > ($total / 2)) {
        $invertirOrden = true;
        $offset = max(0, $total - $offset - $porPagina);
    }

    $orderBy = $invertirOrden ? "id ASC" : "id DESC";

    // ✅ ETAPA 1: Obtener solo los IDs (Muy rápido con el índice)
    $stmtIds = $pdo->prepare("SELECT id FROM clientes WHERE activo = 1 ORDER BY $orderBy LIMIT ? OFFSET ?");
    $stmtIds->bindValue(1, $porPagina, PDO::PARAM_INT);
    $stmtIds->bindValue(2, $offset, PDO::PARAM_INT);
    $stmtIds->execute();
    $targetIds = $stmtIds->fetchAll(PDO::FETCH_COLUMN, 0);

    if (empty($targetIds)) {
        echo json_encode([
            'clientes' => [],
            'total' => $total,
            'totalTodos' => $totalTodos,
            'totalInactivos' => $totalInactivos,
            'pagina' => $pagina,
            'porPagina' => $porPagina,
            'totalPaginas' => 1
        ]);
        exit;
    }

    // ✅ ETAPA 2: Obtener detalles solo para los IDs seleccionados (Deferred Join)
    $placeholders = implode(',', array_fill(0, count($targetIds), '?'));
    $stmt = $pdo->prepare("SELECT id, dni, nombre, apellidos, direccion, fecha_alta, productos_comprados, compras_realizadas, puntos, activo FROM clientes WHERE id IN ($placeholders) ORDER BY $orderBy");
    $stmt->execute($targetIds);
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Revertir si usamos truco de inversión
    if ($invertirOrden) {
        $clientes = array_reverse($clientes);
    }

    echo json_encode([
        'clientes' => $clientes,
        'total' => $total,
        'totalTodos' => $totalTodos,
        'totalInactivos' => $totalInactivos,
        'pagina' => $pagina,
        'porPagina' => $porPagina,
        'totalPaginas' => $total > 0 ? (int) ceil($total / $porPagina) : 1
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

// =============================================================================
// END OF MAIN API
// =============================================================================

// =============================================================================
// API DE HISTORIAL DE PUNTOS
// =============================================================================

/**
 * Obtiene el historial de puntos de un cliente por DNI
 * GET: ?historial_puntos&dni=12345678A
 */
if (isset($_GET['historial_puntos']) && isset($_GET['dni'])) {
    $cliente_dni = $_GET['dni'];

    $stmt = $pdo->prepare(
        "SELECT ph.*, u.nombre as usuario_nombre 
         FROM puntos_historial ph 
         LEFT JOIN usuarios u ON ph.usuario_id = u.id 
         WHERE ph.cliente_dni = ? 
         ORDER BY ph.fecha DESC
         LIMIT 50"
    );
    $stmt->execute([$cliente_dni]);
    $historial = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($historial);
    exit;
}

/**
 * Ajusta los puntos de un cliente por DNI (admin)
 * POST: ?ajustar_puntos
 * Body: {dni, puntos, observacion}
 */
if (isset($_GET['ajustar_puntos']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar que es admin (aquí debería haber validación de sesión)
    $input = json_decode(file_get_contents('php://input'), true);

    $cliente_dni = trim($input['dni'] ?? '');
    $puntos = (int) ($input['puntos'] ?? 0);
    $observacion = trim($input['observacion'] ?? '');
    $usuario_id = (int) ($input['usuario_id'] ?? 0);

    if (empty($cliente_dni)) {
        http_response_code(400);
        echo json_encode(['error' => 'DNI de cliente inválido']);
        exit;
    }

    if ($puntos === 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Los puntos no pueden ser 0']);
        exit;
    }

    try {
        // Iniciar transacción
        $pdo->beginTransaction();

        // Determinar si son puntos ganados o canjeados según el signo
        $puntos_ganados = $puntos > 0 ? $puntos : 0;
        $puntos_canjeados = $puntos < 0 ? abs($puntos) : 0;

        // Actualizar puntos del cliente
        $stmtUpdate = $pdo->prepare("UPDATE clientes SET puntos = puntos + ? WHERE dni = ?");
        $stmtUpdate->execute([$puntos, $cliente_dni]);

        // Registrar en historial
        $stmtHistorial = $pdo->prepare(
            "INSERT INTO puntos_historial (cliente_dni, puntos_ganados, puntos_canjeados, descuento_euros, usuario_id, observacion, fecha) 
             VALUES (?, ?, ?, 0, ?, ?, NOW())"
        );
        $stmtHistorial->execute([$cliente_dni, $puntos_ganados, $puntos_canjeados, $usuario_id, $observacion]);

        $pdo->commit();

        echo json_encode(['success' => true, 'message' => 'Puntos ajustados correctamente']);
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}
?>