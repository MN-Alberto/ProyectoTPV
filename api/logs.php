<?php
/**
 * API de Auditoría de Eventos (Logs del Sistema).
 * 
 * Sistema centralizado de registro de toda la actividad realizada en el TPV.
 * Almacena de forma inmutable todas las acciones de los usuarios, eventos del sistema
 * y errores para auditoría, depuración y cumplimiento normativo.
 * 
 * Métodos soportados:
 * - GET:    Consulta paginada y filtrada de registros
 * - POST:   Crear nuevo registro de auditoría
 * - POST?accion=limpiar: Vaciar completamente la tabla de logs
 * 
 * Todas las operaciones devuelven respuesta en formato JSON con códigos HTTP estándar.
 * 
 * @author Alberto Méndez
 * @version 1.4 (Comentarios añadidos)
 * @since 1.3 (04/03/2026)
 */

// Activamos reporte de errores completo para capturar cualquier anomalía
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once(__DIR__ . '/../config/confDB.php');
    session_start();

    header('Content-Type: application/json; charset=utf-8');

    $pdo = new PDO(RUTA, USUARIO, PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de conexión', 'detalle' => $e->getMessage()]);
    exit;
}

/** 
 * MANEJADOR DE CONSULTAS (GET)
 * Permite la extracción de registros aplicando filtros de tipo, fecha y paginación masiva.
 */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // Verificar si la tabla existe
        $stmt = $pdo->query("SHOW TABLES LIKE 'logs_sistema'");
        if ($stmt->rowCount() === 0) {
            echo json_encode(['logs' => [], 'total' => 0, 'mensaje' => 'Tabla no existe']);
            exit;
        }

        // 📑 PARÁMETROS DE PAGINACIÓN
        // Aseguramos valores mínimo 1 para evitar inyecciones y errores lógicos
        $pagina = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
        $porPagina = isset($_GET['por_pagina']) ? max(1, intval($_GET['por_pagina'])) : 6;
        $offset = ($pagina - 1) * $porPagina;

        // 🔍 PARÁMETROS DE FILTRO
        $tipo = isset($_GET['tipo']) && $_GET['tipo'] !== '' ? $_GET['tipo'] : null;
        $fecha = isset($_GET['fecha']) && $_GET['fecha'] !== '' ? $_GET['fecha'] : null;

        // -----------------------------------------------------------------------------
        // 🔢 CONSULTA PARA CONTAR TOTAL DE REGISTROS
        // Para esta consulta SI usamos prepared statements correctamente ya que no hay LIMIT
        // -----------------------------------------------------------------------------
        $where = [];
        $params = [];

        // Filtro por tipo de log (soporta múltiples valores separados por coma)
        if ($tipo) {
            if (strpos($tipo, ',') !== false) {
                // Filtro múltiple: generamos placeholders dinámicamente para cada valor
                $tipos = array_map('trim', explode(',', $tipo));
                $placeholders = [];
                foreach ($tipos as $i => $t) {
                    $placeholder = ":tipo" . $i;
                    $placeholders[] = $placeholder;
                    $params[$placeholder] = $t;
                }
                $where[] = "tipo IN (" . implode(',', $placeholders) . ")";
            } else {
                // Filtro simple de un solo tipo
                $where[] = "tipo = :tipo";
                $params[':tipo'] = $tipo;
            }
        }

        // Filtro por fecha: intervalo completo del día seleccionado
        if ($fecha) {
            $where[] = "fecha >= :fecha";
            $params[':fecha'] = $fecha . ' 00:00:00';
            $where[] = "fecha <= :fecha_hasta";
            $params[':fecha_hasta'] = $fecha . ' 23:59:59';
        }

        $whereClause = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

        // Ejecutamos consulta de conteo total para la paginación
        $sqlTotal = "SELECT COUNT(*) as total FROM logs_sistema $whereClause";
        $stmtTotal = $pdo->prepare($sqlTotal);
        $stmtTotal->execute($params);
        $total = $stmtTotal->fetch(PDO::FETCH_ASSOC)['total'];

        // -----------------------------------------------------------------------------
        // 📋 CONSULTA PARA OBTENER LOS DATOS
        // ⚠️ IMPORTANTE: Para esta consulta NO usamos prepared statements por una limitación
        // conocida de PDO en PHP que no permite bindear parámetros en las cláusulas LIMIT y OFFSET.
        // En su lugar usamos PDO::quote() para escapar correctamente todos los valores.
        // -----------------------------------------------------------------------------
        $sqlSelect = "SELECT id, fecha, tipo, usuario_id, usuario_nombre, descripcion, detalles 
                FROM logs_sistema";

        $whereSelect = [];

        if ($tipo) {
            if (strpos($tipo, ',') !== false) {
                $tipos = array_map('trim', explode(',', $tipo));
                $tipoQuotes = array_map(function ($t) use ($pdo) {
                    return $pdo->quote($t);
                }, $tipos);
                $whereSelect[] = "tipo IN (" . implode(',', $tipoQuotes) . ")";
            } else {
                $whereSelect[] = "tipo = " . $pdo->quote($tipo);
            }
        }

        if ($fecha) {
            $whereSelect[] = "fecha >= " . $pdo->quote($fecha . ' 00:00:00');
            $whereSelect[] = "fecha <= " . $pdo->quote($fecha . ' 23:59:59');
        }

        // Construimos consulta final con orden descendente (últimos eventos primero)
        $sqlSelect .= count($whereSelect) > 0 ? ' WHERE ' . implode(' AND ', $whereSelect) : '';
        $sqlSelect .= " ORDER BY fecha DESC LIMIT $porPagina OFFSET $offset";

        $stmt = $pdo->query($sqlSelect);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        /**
         * 📦 TRANSFORMACIÓN DE DATOS
         * El campo 'detalles' se almacena en BD como string JSON para flexibilidad.
         * Lo decodificamos automáticamente a array para que el cliente lo reciba
         * como objeto JSON nativo y no tenga que parsearlo él mismo.
         */
        foreach ($logs as &$log) {
            if ($log['detalles']) {
                $log['detalles'] = json_decode($log['detalles'], true);
            }
        }

        echo json_encode([
            'logs' => $logs,
            'total' => $total
        ]);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al obtener logs', 'detalle' => $e->getMessage()]);
    }
    exit;
}

/** 
 * ACCIÓN ESPECIAL: Limpiar Logs del Sistema
 * 
 * Realiza un TRUNCATE físico de la tabla para liberar espacio en disco.
 * ⚠️ IMPORTANTE: Después de borrar TODO, se inserta UN NUEVO LOG que registra
 * quién realizó la acción de borrado. Nunca se borra la traza completa.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['accion']) && $_GET['accion'] === 'limpiar') {
    try {
        // Capturamos datos del usuario ANTES de borrar cualquier cosa
        $usuarioId = $_SESSION['idUsuario'] ?? null;
        $usuarioNombre = $_SESSION['nombreUsuario'] ?? 'Sistema';

        // Eliminamos absolutamente todos los registros
        $pdo->exec("TRUNCATE TABLE logs_sistema");

        // ✅ Dejamos constancia de que se realizó el borrado
        // Este será el primer y único registro de la tabla después del truncate
        $stmt = $pdo->prepare("INSERT INTO logs_sistema (tipo, usuario_id, usuario_nombre, descripcion, detalles) VALUES (:tipo, :usuario_id, :usuario_nombre, :descripcion, :detalles)");
        $stmt->execute([
            ':tipo' => 'borrado_logs',
            ':usuario_id' => $usuarioId,
            ':usuario_nombre' => $usuarioNombre,
            ':descripcion' => 'Se han borrado todos los logs del sistema',
            ':detalles' => json_encode(['accion' => 'borrado_logs', 'timestamp' => date('Y-m-d H:i:s')])
        ]);

        echo json_encode(['ok' => true, 'mensaje' => 'Logs eliminados correctamente']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'Error al eliminar logs', 'detalle' => $e->getMessage()]);
    }
    exit;
}

// ======================== POST: Crear log ========================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar que hay sesión activa
    if (!isset($_SESSION['idUsuario'])) {
        http_response_code(401);
        echo json_encode(['error' => 'No autorizado']);
        exit;
    }

    // Leer datos
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !is_array($input)) {
        http_response_code(400);
        echo json_encode(['error' => 'Datos inválidos']);
        exit;
    }

    $tipo = isset($input['tipo']) ? $input['tipo'] : null;
    $descripcion = isset($input['descripcion']) ? $input['descripcion'] : '';
    $detalles = isset($input['detalles']) ? $input['detalles'] : null;

    /**
     * ✅ LISTA BLANCA DE TIPOS PERMITIDOS
     * Seguridad: Solo se admiten estos tipos de eventos predefinidos.
     * Esto evita que se puedan insertar tipos arbitrarios mediante llamadas a la API.
     * Cualquier nuevo tipo de evento debe añadirse explícitamente a esta lista.
     */
    $tiposPermitidos = [
        'login',
        'logout',
        'login_fallido',
        'venta',
        'apertura_caja',
        'cierre_caja',
        'retiro_caja',
        'acceso_admin',
        'acceso_cajero',
        'acceso_login',
        'creacion_usuario',
        'modificacion_usuario',
        'eliminacion_usuario',
        'borrado_logs'
    ];

    if (!$tipo || !in_array($tipo, $tiposPermitidos)) {
        http_response_code(400);
        echo json_encode(['error' => 'Tipo de log no válido']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO logs_sistema (tipo, usuario_id, usuario_nombre, descripcion, detalles) VALUES (:tipo, :usuario_id, :usuario_nombre, :descripcion, :detalles)");

        $stmt->execute([
            ':tipo' => $tipo,
            ':usuario_id' => $_SESSION['idUsuario'],
            ':usuario_nombre' => $_SESSION['nombreUsuario'] ?? 'Desconocido',
            ':descripcion' => $descripcion,
            ':detalles' => $detalles ? json_encode($detalles) : null
        ]);

        echo json_encode(['ok' => true, 'id' => $pdo->lastInsertId()]);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al guardar log', 'detalle' => $e->getMessage()]);
    }
    exit;
}

// Método no soportado
http_response_code(405);
echo json_encode(['error' => 'Método no permitido']);
