<?php
/**
 * API de Inteligencia de Negocio e Informes.
 * Agrega y procesa datos transaccionales para generar estadísticas de rendimiento,
 * rankings de productos, márgenes de beneficio y análisis de franjas horarias.
 * 
 * @author Alberto Méndez
 * @version 1.0 (2026)
 */
require_once(__DIR__ . '/../model/Venta.php');
require_once(__DIR__ . '/../model/Producto.php');
require_once(__DIR__ . '/../model/Caja.php');
require_once(__DIR__ . '/../config/confDB.php');
require_once(__DIR__ . '/../core/Cache.php');

header('Content-Type: application/json');

// ✅ GESTIÓN DE TAREAS EN SEGUNDO PLANO
if (isset($_GET['get_tasks'])) {
    try {
        $pdo = new PDO(RUTA, USUARIO, PASS);
        $stmt = $pdo->query("SELECT * FROM tareas_segundo_plano ORDER BY creado_en DESC LIMIT 10");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (Exception $e) { echo json_encode(['error' => $e->getMessage()]); }
    exit;
}

if (isset($_GET['check_task'])) {
    try {
        $pdo = new PDO(RUTA, USUARIO, PASS);
        $stmt = $pdo->prepare("SELECT * FROM tareas_segundo_plano WHERE id = ?");
        $stmt->execute([$_GET['check_task']]);
        echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
    } catch (Exception $e) { echo json_encode(['error' => $e->getMessage()]); }
    exit;
}

// Parámetro de segmentación temporal (diario, semanal, mensual, anual)
$periodo = $_GET['periodo'] ?? 'diario';
$background = isset($_GET['background']) && $_GET['background'] == '1';

if ($background) {
    try {
        $pdo = new PDO(RUTA, USUARIO, PASS);
        $params = json_encode(['periodo' => $periodo]);
        $stmt = $pdo->prepare("INSERT INTO tareas_segundo_plano (tipo, parametros, estado) VALUES ('informe', ?, 'pendiente')");
        $stmt->execute([$params]);
        $taskId = $pdo->lastInsertId();

        // Lanzar el worker en segundo plano (Windows)
        $command = "start /B C:\\xampp\\php\\php.exe " . __DIR__ . "/informes_worker.php $taskId";
        pclose(popen($command, "r"));

        echo json_encode(['ok' => true, 'taskId' => $taskId, 'mensaje' => 'Generación iniciada en segundo plano']);
    } catch (Exception $e) { echo json_encode(['error' => $e->getMessage()]); }
    exit;
}

// CACHE: Devolver directamente si existe
$claveCache = "informes_$periodo";
$cache = Cache::get($claveCache);

if ($cache !== null && !isset($_GET['refresh'])) {
    header('X-Cache: HIT');
    echo $cache;
    exit;
}

/**
 * DETERMINACIÓN DEL RANGO DE TIEMPO
 * Calcula los límites inferior y superior para la consulta SQL basándose en el periodo solicitado.
 */
$fechaInicio = '';
$fechaFin = date('Y-m-d 23:59:59');

switch ($periodo) {
    case 'diario':
        $fechaInicio = date('Y-m-d 00:00:00');
        break;
    case 'semanal':
        $fechaInicio = date('Y-m-d 00:00:00', strtotime('-6 days'));
        break;
    case 'mensual':
        $fechaInicio = date('Y-m-01 00:00:00');
        break;
    case 'anual':
        $fechaInicio = date('Y-01-01 00:00:00');
        break;
    default:
        $fechaInicio = date('Y-m-d 00:00:00');
}

try {
    $pdo = new PDO(RUTA, USUARIO, PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Necesario para repetir parámetros con el mismo nombre en una sola query (UNION ALL)
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);

    // Calcular periodo anterior para comparativa
    $fechaInicioAnt = '';
    $fechaFinAnt = date('Y-m-d 23:59:59', strtotime($fechaInicio . ' -1 second'));

    switch ($periodo) {
        case 'diario':
            $fechaInicioAnt = date('Y-m-d 00:00:00', strtotime('-1 day'));
            break;
        case 'semanal':
            $fechaInicioAnt = date('Y-m-d 00:00:00', strtotime($fechaInicio . ' -7 days'));
            break;
        case 'mensual':
            $fechaInicioAnt = date('Y-m-01 00:00:00', strtotime($fechaInicio . ' -1 month'));
            break;
        case 'anual':
            $fechaInicioAnt = date('Y-01-01 00:00:00', strtotime($fechaInicio . ' -1 year'));
            break;
    }

    $respuesta = [];

    // ✅ OPTIMIZACIÓN HÍBRIDA: Detectar hasta qué fecha tenemos sumarios
    $stmtCutoff = $pdo->query("SELECT MAX(fecha) FROM informes_sumarios_diarios");
    $cutoffDate = $stmtCutoff->fetchColumn() ?: '2000-01-01';

    $usarSumarios = ($periodo !== 'diario');
    $cutoffTimestamp = strtotime($cutoffDate);

    /**
     * Calcula los agregados de ventas (bruto, volumen, métodos de pago) para un intervalo.
     * 
     * @param PDO $pdo Conexión a la base de datos.
     * @param string $ini Fecha de inicio (Y-m-d H:i:s).
     * @param string $fin Fecha de fin (Y-m-d H:i:s).
     * @param bool $usarSumarios Si se debe usar la tabla de sumarios diarios.
     * @return array Desglose indexado de estadísticas.
     */
    function getVentasRango($pdo, $ini, $fin, $usarSumarios, $cutoffDate)
    {
        if (!$usarSumarios) {
            $sql = "SELECT SUM(total) as bruto, COUNT(*) as num_tickets,
                           SUM(CASE WHEN metodoPago = 'efectivo' THEN total ELSE 0 END) as efectivo,
                           SUM(CASE WHEN metodoPago = 'tarjeta' THEN total ELSE 0 END) as tarjeta,
                           SUM(CASE WHEN metodoPago = 'bizum' THEN total ELSE 0 END) as bizum,
                           0 as descuentos, 0 as devoluciones
                    FROM ventas WHERE fecha BETWEEN :inicio AND :fin AND estado = 'completada'";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':inicio' => $ini, ':fin' => $fin]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }

        // Modo Híbrido: Unión de Sumarios + Raw (Post-Cutoff)
        $sql = "SELECT SUM(bruto) as bruto, SUM(num_tickets) as num_tickets,
                       SUM(efectivo) as efectivo, SUM(tarjeta) as tarjeta, SUM(bizum) as bizum,
                       SUM(descuentos) as descuentos, SUM(devoluciones) as devoluciones
                FROM (
                    -- Parte 1: Sumarios
                    SELECT SUM(total_ventas) as bruto, SUM(num_tickets) as num_tickets,
                           0 as efectivo, 0 as tarjeta, 0 as bizum,
                           SUM(descuentos) as descuentos, SUM(devoluciones) as devoluciones
                    FROM informes_sumarios_diarios 
                    WHERE fecha BETWEEN DATE(:inicio) AND DATE(LEAST(:fin, :cutoff))

                    UNION ALL

                    -- Parte 2: Live (Post-Cutoff)
                    SELECT SUM(total) as bruto, COUNT(*) as num_tickets,
                           SUM(CASE WHEN metodoPago = 'efectivo' THEN total ELSE 0 END) as efectivo,
                           SUM(CASE WHEN metodoPago = 'tarjeta' THEN total ELSE 0 END) as tarjeta,
                           SUM(CASE WHEN metodoPago = 'bizum' THEN total ELSE 0 END) as bizum,
                           SUM(descuentoValor + descuentoTarifaValor + descuentoManualValor) as descuentos,
                           0 as devoluciones -- Las devoluciones usan su propia tabla
                    FROM ventas 
                    WHERE fecha BETWEEN GREATEST(:inicio, DATE_ADD(:cutoff, INTERVAL 1 DAY)) AND :fin 
                    AND estado = 'completada'
                ) as hybrid";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':inicio' => $ini, ':fin' => $fin, ':cutoff' => $cutoffDate]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);

        // Si hay periodo post-cutoff, sumar devoluciones live
        if (strtotime($fin) > strtotime($cutoffDate)) {
             $startLive = max($ini, date('Y-m-d 00:00:00', strtotime($cutoffDate . ' +1 day')));
             $stmtDevoLive = $pdo->prepare("SELECT SUM(importeTotal) as total FROM devoluciones WHERE fecha BETWEEN :ini AND :fin");
             $stmtDevoLive->execute(['ini' => $startLive, 'fin' => $fin]);
             $res['devoluciones'] += floatval($stmtDevoLive->fetchColumn() ?: 0);
        }

        return $res;
    }

    $ventasActual = getVentasRango($pdo, $fechaInicio, $fechaFin, $usarSumarios, $cutoffDate);
    $ventasAnterior = getVentasRango($pdo, $fechaInicioAnt, $fechaFinAnt, $usarSumarios, $cutoffDate);

    // Desglose de IVA Híbrido
    if ($usarSumarios) {
        $sqlIva = "SELECT tipo, SUM(base) as base, SUM(cuota) as cuota
                    FROM (
                        SELECT iva as tipo, SUM(base) as base, SUM(cuota) as cuota
                        FROM informes_iva_diario
                        WHERE fecha BETWEEN DATE(:inicio) AND DATE(LEAST(:fin, :cutoff))
                        GROUP BY iva
                        
                        UNION ALL
                        
                        SELECT lv.iva as tipo, 
                               SUM(lv.subtotal / (1 + (lv.iva / 100))) as base,
                               SUM(lv.subtotal - (lv.subtotal / (1 + (lv.iva / 100)))) as cuota
                        FROM lineasVenta lv
                        JOIN ventas v ON lv.idVenta = v.id
                        WHERE v.fecha BETWEEN GREATEST(:inicio, DATE_ADD(:cutoff, INTERVAL 1 DAY)) AND :fin
                        AND v.estado = 'completada'
                        GROUP BY lv.iva
                    ) as combined
                    GROUP BY tipo";
        $stmtIva = $pdo->prepare($sqlIva);
        $stmtIva->execute([':inicio' => $fechaInicio, ':fin' => $fechaFin, ':cutoff' => $cutoffDate]);
        $desgloseIva = [];
        foreach ($stmtIva->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $desgloseIva[] = [
                'tipo' => floatval($row['tipo']),
                'base' => floatval($row['base']),
                'cuota' => floatval($row['cuota'])
            ];
        }
    } else {
        // ... (el bloque else ya tiene la lógica raw correcta)
        $stmtIva = $pdo->prepare("
            SELECT 
                lv.iva,
                SUM(lv.subtotal) as total_con_iva
            FROM lineasVenta lv
            JOIN ventas v ON lv.idVenta = v.id
            WHERE v.fecha BETWEEN :inicio AND :fin AND v.estado = 'completada'
            GROUP BY lv.iva
        ");
        $stmtIva->execute([':inicio' => $fechaInicio, ':fin' => $fechaFin]);
        $ivasRaw = $stmtIva->fetchAll(PDO::FETCH_ASSOC);
        $desgloseIva = [];
        foreach ($ivasRaw as $row) {
            $porcentaje = floatval($row['iva']);
            $totalConIva = floatval($row['total_con_iva']);
            $base = $totalConIva / (1 + ($porcentaje / 100));
            $cuota = $totalConIva - $base;
            $desgloseIva[] = [
                'tipo' => $porcentaje,
                'base' => $base,
                'cuota' => $cuota
            ];
        }
    }

    // Devoluciones y Descuentos (Capturados del sumario si es posible)
    $descuentosActual = $usarSumarios ? floatval($ventasActual['descuentos'] ?? 0) : null;
    $devolucionesActual = $usarSumarios ? floatval($ventasActual['devoluciones'] ?? 0) : null;

    if ($descuentosActual === null) {
        $stmtDesc = $pdo->prepare("
            SELECT SUM(descuentoValor + descuentoTarifaValor + descuentoManualValor) as total
            FROM ventas WHERE fecha BETWEEN :inicio AND :fin AND estado = 'completada'
        ");
        $stmtDesc->execute([':inicio' => $fechaInicio, ':fin' => $fechaFin]);
        $descuentosActual = floatval($stmtDesc->fetchColumn() ?? 0);
    }

    if ($devolucionesActual === null) {
        $stmtDevo = $pdo->prepare("SELECT SUM(importeTotal) as total FROM devoluciones WHERE fecha BETWEEN :inicio AND :fin");
        $stmtDevo->execute([':inicio' => $fechaInicio, ':fin' => $fechaFin]);
        $devolucionesActual = floatval($stmtDevo->fetchColumn() ?? 0);
    }

    $respuesta['ventas'] = [
        'periodoActual' => [
            'bruto' => floatval($ventasActual['bruto'] ?? 0),
            'tickets' => intval($ventasActual['num_tickets'] ?? 0),
            'ticket_medio' => $ventasActual['num_tickets'] > 0 ? floatval($ventasActual['bruto'] / $ventasActual['num_tickets']) : 0,
            'metodos' => [
                'efectivo' => floatval($ventasActual['efectivo'] ?? 0),
                'tarjeta' => floatval($ventasActual['tarjeta'] ?? 0),
                'bizum' => floatval($ventasActual['bizum'] ?? 0)
            ],
            'devoluciones' => $devolucionesActual,
            'descuentos' => $descuentosActual,
            'iva' => $desgloseIva
        ],
        'periodoAnterior' => [
            'bruto' => floatval($ventasAnterior['bruto'] ?? 0),
            'tickets' => intval($ventasAnterior['num_tickets'] ?? 0)
        ]
    ];

    /**
     * INFORME DE RENDIMIENTO DE PRODUCTOS
     * Genera un ranking de artículos basado en unidades vendidas, ingresos y márgenes.
     */
    /**
     * INFORME DE RENDIMIENTO DE PRODUCTOS HÍBRIDO
     */
    if ($usarSumarios) {
        $sqlProd = "SELECT nombre, SUM(unidades) as unidades, SUM(ingresos) as ingresos, SUM(coste) as coste
                    FROM (
                        SELECT nombreProducto as nombre, SUM(unidades) as unidades, SUM(ingresos) as ingresos, SUM(coste) as coste
                        FROM informes_productos_diarios
                        WHERE fecha BETWEEN DATE(:inicio) AND DATE(LEAST(:fin, :cutoff))
                        GROUP BY idProducto, nombreProducto

                        UNION ALL

                        SELECT COALESCE(lv.nombreProducto, p.nombre) as nombre,
                               SUM(lv.cantidad) as unidades,
                               SUM(lv.subtotal) as ingresos,
                               SUM(lv.cantidad * COALESCE(pp.precioProveedor, 0)) as coste
                        FROM lineasVenta lv
                        JOIN ventas v ON lv.idVenta = v.id
                        LEFT JOIN productos p ON lv.idProducto = p.id
                        LEFT JOIN (SELECT idProducto, MAX(precioProveedor) as precioProveedor FROM proveedor_producto GROUP BY idProducto) pp ON lv.idProducto = pp.idProducto
                        WHERE v.fecha BETWEEN GREATEST(:inicio, DATE_ADD(:cutoff, INTERVAL 1 DAY)) AND :fin
                        AND v.estado = 'completada'
                        GROUP BY COALESCE(lv.nombreProducto, p.nombre)
                    ) as combined
                    GROUP BY nombre
                    ORDER BY unidades DESC
                    LIMIT 10";
        $stmtProdTop = $pdo->prepare($sqlProd);
        $stmtProdTop->execute([':inicio' => $fechaInicio, ':fin' => $fechaFin, ':cutoff' => $cutoffDate]);
    } else {
        $stmtProdTop = $pdo->prepare("
            SELECT 
                COALESCE(lv.nombreProducto, p.nombre) as nombre,
                SUM(lv.cantidad) as unidades,
                SUM(lv.subtotal) as ingresos,
                SUM(lv.cantidad * COALESCE(pp.precioProveedor, 0)) as coste
            FROM lineasVenta lv
            JOIN ventas v ON lv.idVenta = v.id
            LEFT JOIN productos p ON lv.idProducto = p.id
            LEFT JOIN (SELECT idProducto, MAX(precioProveedor) as precioProveedor FROM proveedor_producto GROUP BY idProducto) pp ON p.id = pp.idProducto
            WHERE v.fecha BETWEEN :inicio AND :fin AND v.estado = 'completada'
            GROUP BY COALESCE(lv.nombreProducto, p.nombre)
            ORDER BY unidades DESC
            LIMIT 10
        ");
        $stmtProdTop->execute([':inicio' => $fechaInicio, ':fin' => $fechaFin]);
    }
    $prodsRanking = $stmtProdTop->fetchAll(PDO::FETCH_ASSOC);
    foreach ($prodsRanking as &$pr) {
        $pr['beneficio'] = floatval($pr['ingresos']) - floatval($pr['coste']);
        $pr['margen'] = ($pr['ingresos'] > 0) ? ($pr['beneficio'] / $pr['ingresos'] * 100) : 0;
    }
    $respuesta['productos_ranking'] = $prodsRanking;

    // Optimización del "Peor vendidos": Subconsulta para evitar el escaneo completo
    $stmtProdBottom = $pdo->prepare("
        SELECT p.nombre, COALESCE(sold.unidades, 0) as unidades
        FROM productos p
        LEFT JOIN (
            SELECT lv.idProducto, SUM(lv.cantidad) as unidades
            FROM lineasVenta lv
            JOIN ventas v ON lv.idVenta = v.id
            WHERE v.fecha BETWEEN :inicio AND :fin AND v.estado = 'completada'
            GROUP BY lv.idProducto
        ) sold ON p.id = sold.idProducto
        WHERE p.activo = 1
        ORDER BY unidades ASC
        LIMIT 5
    ");
    $stmtProdBottom->execute([':inicio' => $fechaInicio, ':fin' => $fechaFin]);
    $respuesta['productos_bottom'] = $stmtProdBottom->fetchAll(PDO::FETCH_ASSOC);

    if ($usarSumarios) {
        $sqlCat = "SELECT categoria, SUM(ingresos) as ingresos, SUM(coste) as coste
                   FROM (
                       SELECT COALESCE(c.nombre, 'Producto propio sin categoría') as categoria,
                              SUM(ipd.ingresos) as ingresos,
                              SUM(ipd.coste) as coste
                       FROM informes_productos_diarios ipd
                       LEFT JOIN categorias c ON ipd.idCategoria = c.id
                       WHERE ipd.fecha BETWEEN DATE(:inicio) AND DATE(LEAST(:fin, :cutoff))
                       GROUP BY ipd.idCategoria

                       UNION ALL

                       SELECT COALESCE(c.nombre, 'Producto propio sin categoría') as categoria,
                              SUM(lv.subtotal) as ingresos,
                              SUM(lv.cantidad * COALESCE(pp.precioP, 0)) as coste
                       FROM lineasVenta lv
                       JOIN ventas v ON lv.idVenta = v.id
                       LEFT JOIN productos p ON lv.idProducto = p.id
                       LEFT JOIN categorias c ON p.idCategoria = c.id
                       LEFT JOIN (SELECT idProducto, MAX(precioProveedor) as precioP FROM proveedor_producto GROUP BY idProducto) pp ON lv.idProducto = pp.idProducto
                       WHERE v.fecha BETWEEN GREATEST(:inicio, DATE_ADD(:cutoff, INTERVAL 1 DAY)) AND :fin
                       AND v.estado = 'completada'
                       GROUP BY c.id
                   ) as combined
                   GROUP BY categoria
                   ORDER BY ingresos DESC";
        $stmtCat = $pdo->prepare($sqlCat);
        $stmtCat->execute([':inicio' => $fechaInicio, ':fin' => $fechaFin, ':cutoff' => $cutoffDate]);
    } else {
        $stmtCat = $pdo->prepare("
            SELECT 
                COALESCE(c.nombre, 'Producto propio sin categoría') as categoria,
                SUM(lv.subtotal) as ingresos,
                SUM(lv.cantidad * COALESCE(pp.precioProveedor, 0)) as coste
            FROM lineasVenta lv
            JOIN ventas v ON lv.idVenta = v.id
            LEFT JOIN productos p ON lv.idProducto = p.id
            LEFT JOIN categorias c ON p.idCategoria = c.id
            LEFT JOIN (SELECT idProducto, MAX(precioProveedor) as precioProveedor FROM proveedor_producto GROUP BY idProducto) pp ON p.id = pp.idProducto
            WHERE v.fecha BETWEEN :inicio AND :fin AND v.estado = 'completada'
            GROUP BY c.id, COALESCE(c.nombre, 'Producto propio sin categoría')
            ORDER BY ingresos DESC
        ");
        $stmtCat->execute([':inicio' => $fechaInicio, ':fin' => $fechaFin]);
    }
    $catsRanking = $stmtCat->fetchAll(PDO::FETCH_ASSOC);
    foreach ($catsRanking as &$cr) {
        $cr['beneficio'] = floatval($cr['ingresos']) - floatval($cr['coste']);
        $cr['margen'] = ($cr['ingresos'] > 0) ? ($cr['beneficio'] / $cr['ingresos'] * 100) : 0;
    }
    $respuesta['categorias_ranking'] = $catsRanking;

    // 3. Márgenes Globales Híbrido
    if ($usarSumarios) {
        $sqlMargen = "SELECT SUM(ingresos) as ingresos, SUM(coste) as coste
                      FROM (
                          SELECT SUM(total_ventas) as ingresos, SUM(total_coste) as coste
                          FROM informes_sumarios_diarios
                          WHERE fecha BETWEEN DATE(:inicio) AND DATE(LEAST(:fin, :cutoff))
                          
                          UNION ALL
                          
                          SELECT SUM(lv.subtotal) as ingresos,
                                 SUM(lv.cantidad * COALESCE(pp.precioP, 0)) as coste
                          FROM lineasVenta lv
                          JOIN ventas v ON lv.idVenta = v.id
                          LEFT JOIN (
                              SELECT idProducto, MAX(precioProveedor) as precioP 
                              FROM proveedor_producto 
                              GROUP BY idProducto
                          ) pp ON lv.idProducto = pp.idProducto
                          WHERE v.fecha BETWEEN GREATEST(:inicio, DATE_ADD(:cutoff, INTERVAL 1 DAY)) AND :fin
                          AND v.estado = 'completada'
                      ) as combined";
        $stmtMargen = $pdo->prepare($sqlMargen);
        $stmtMargen->execute([':inicio' => $fechaInicio, ':fin' => $fechaFin, ':cutoff' => $cutoffDate]);
    } else {
        $stmtMargen = $pdo->prepare("
            SELECT 
                SUM(lv.subtotal) as ingresos,
                SUM(lv.cantidad * COALESCE(pp.precioP, 0)) as coste
            FROM lineasVenta lv
            JOIN ventas v ON lv.idVenta = v.id
            LEFT JOIN (
                SELECT idProducto, MAX(precioProveedor) as precioP 
                FROM proveedor_producto 
                GROUP BY idProducto
            ) pp ON lv.idProducto = pp.idProducto
            WHERE v.fecha BETWEEN :inicio AND :fin AND v.estado = 'completada'
        ");
        $stmtMargen->execute([':inicio' => $fechaInicio, ':fin' => $fechaFin]);
    }
    $margen = $stmtMargen->fetch(PDO::FETCH_ASSOC);
    $respuesta['margenes'] = [
        'ingresos' => floatval($margen['ingresos'] ?? 0),
        'coste' => floatval($margen['coste'] ?? 0),
        'beneficio' => floatval(($margen['ingresos'] ?? 0) - ($margen['coste'] ?? 0)),
        'porcentaje' => ($margen['ingresos'] > 0) ? ((($margen['ingresos'] - $margen['coste']) / $margen['ingresos']) * 100) : 0
    ];

    if ($usarSumarios) {
        $sqlEmp = "SELECT nombre, SUM(tickets) as tickets, SUM(total) as total
                   FROM (
                       SELECT u.nombre, SUM(ied.tickets) as tickets, SUM(ied.total) as total
                       FROM informes_empleados_diario ied
                       JOIN usuarios u ON ied.idUsuario = u.id
                       WHERE ied.fecha BETWEEN DATE(:inicio) AND DATE(LEAST(:fin, :cutoff))
                       GROUP BY ied.idUsuario

                       UNION ALL

                       SELECT u.nombre, COUNT(v.id) as tickets, SUM(v.total) as total
                       FROM ventas v
                       JOIN usuarios u ON v.idUsuario = u.id
                       WHERE v.fecha BETWEEN GREATEST(:inicio, DATE_ADD(:cutoff, INTERVAL 1 DAY)) AND :fin
                       AND v.estado = 'completada'
                       GROUP BY v.idUsuario
                   ) as combined
                   GROUP BY nombre
                   ORDER BY total DESC";
        $stmtEmp = $pdo->prepare($sqlEmp);
        $stmtEmp->execute([':inicio' => $fechaInicio, ':fin' => $fechaFin, ':cutoff' => $cutoffDate]);
    } else {
        $stmtEmp = $pdo->prepare("
            SELECT 
                u.nombre,
                COUNT(v.id) as tickets,
                SUM(v.total) as total
            FROM ventas v
            JOIN usuarios u ON v.idUsuario = u.id
            WHERE v.fecha BETWEEN :inicio AND :fin AND v.estado = 'completada'
            GROUP BY v.idUsuario
            ORDER BY total DESC
        ");
        $stmtEmp->execute([':inicio' => $fechaInicio, ':fin' => $fechaFin]);
    }
    $empleados = $stmtEmp->fetchAll(PDO::FETCH_ASSOC);
    foreach ($empleados as &$e) {
        $e['ticket_medio'] = $e['tickets'] > 0 ? ($e['total'] / $e['tickets']) : 0;
    }
    $respuesta['empleados'] = $empleados;

    if ($usarSumarios) {
        $sqlHoras = "SELECT hora, SUM(tickets) as tickets, SUM(total) as total
                     FROM (
                         SELECT hora, SUM(tickets) as tickets, SUM(total) as total
                         FROM informes_horas_diario
                         WHERE fecha BETWEEN DATE(:inicio) AND DATE(LEAST(:fin, :cutoff))
                         GROUP BY hora

                         UNION ALL

                         SELECT HOUR(v.fecha) as hora, COUNT(*) as tickets, SUM(v.total) as total
                         FROM ventas v
                         WHERE v.fecha BETWEEN GREATEST(:inicio, DATE_ADD(:cutoff, INTERVAL 1 DAY)) AND :fin
                         AND v.estado = 'completada'
                         GROUP BY HOUR(v.fecha)
                     ) as combined
                     GROUP BY hora
                     ORDER BY hora ASC";
        $stmtHoras = $pdo->prepare($sqlHoras);
        $stmtHoras->execute([':inicio' => $fechaInicio, ':fin' => $fechaFin, ':cutoff' => $cutoffDate]);
    } else {
        $stmtHoras = $pdo->prepare("
            SELECT 
                HOUR(v.fecha) as hora,
                COUNT(*) as tickets,
                SUM(v.total) as total
            FROM ventas v
            WHERE v.fecha BETWEEN :inicio AND :fin AND v.estado = 'completada'
            GROUP BY HOUR(v.fecha)
            ORDER BY hora ASC
        ");
        $stmtHoras->execute([':inicio' => $fechaInicio, ':fin' => $fechaFin]);
    }
    $respuesta['franjas'] = $stmtHoras->fetchAll(PDO::FETCH_ASSOC);

    // 6. Caja y Desajustes (Arqueos)
    $stmtCaja = $pdo->prepare("
        SELECT 
            SUM(importeInicial) as fondo_inicial,
            SUM(importeActual) as efectivo_final
        FROM caja_sesiones
        WHERE fechaApertura BETWEEN :inicio AND :fin
    ");
    $stmtCaja->execute([':inicio' => $fechaInicio, ':fin' => $fechaFin]);
    $resumenCaja = $stmtCaja->fetch(PDO::FETCH_ASSOC);

    $stmtRetiros = $pdo->prepare("SELECT SUM(importe) as total FROM retiros WHERE fecha BETWEEN :inicio AND :fin");
    $stmtRetiros->execute([':inicio' => $fechaInicio, ':fin' => $fechaFin]);
    $totalRetiros = $stmtRetiros->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    $stmtArqueo = $pdo->prepare("
        SELECT 
            SUM(diferencia) as total_diferencia,
            COUNT(*) as num_arqueos
        FROM arqueos_caja
        WHERE fechaArqueo BETWEEN :inicio AND :fin AND tipoArqueo = 'cierre'
    ");
    $stmtArqueo->execute([':inicio' => $fechaInicio, ':fin' => $fechaFin]);
    $resumenArqueo = $stmtArqueo->fetch(PDO::FETCH_ASSOC);

    $respuesta['caja_resumen'] = [
        'fondo_inicial' => floatval($resumenCaja['fondo_inicial'] ?? 0),
        'efectivo_final' => floatval($resumenCaja['efectivo_final'] ?? 0),
        'retiros' => floatval($totalRetiros),
        'desajuste' => floatval($resumenArqueo['total_diferencia'] ?? 0),
        'num_cierres' => intval($resumenArqueo['num_arqueos'] ?? 0)
    ];

    // 7. Devoluciones detalladas
    $stmtDevoTotal = $pdo->prepare("SELECT SUM(importeTotal) as total, COUNT(*) as cantidad FROM devoluciones WHERE fecha BETWEEN :inicio AND :fin");
    $stmtDevoTotal->execute([':inicio' => $fechaInicio, ':fin' => $fechaFin]);
    $devoResumen = $stmtDevoTotal->fetch(PDO::FETCH_ASSOC);

    $stmtDevoProds = $pdo->prepare("
        SELECT 
            COALESCE(d.nombreProducto, p.nombre) as nombre,
            COUNT(*) as veces,
            SUM(d.importeTotal) as total
        FROM devoluciones d
        LEFT JOIN productos p ON d.idProducto = p.id
        WHERE d.fecha BETWEEN :inicio AND :fin
        GROUP BY COALESCE(d.nombreProducto, p.nombre)
        ORDER BY veces DESC
        LIMIT 5
    ");
    $stmtDevoProds->execute([':inicio' => $fechaInicio, ':fin' => $fechaFin]);
    $devoProds = $stmtDevoProds->fetchAll(PDO::FETCH_ASSOC);

    $respuesta['devoluciones_detalle'] = [
        'total' => floatval($devoResumen['total'] ?? 0),
        'cantidad' => intval($devoResumen['cantidad'] ?? 0),
        'productos' => $devoProds,
        'porcentaje_ventas' => ($ventasActual['bruto'] > 0) ? (floatval($devoResumen['total'] ?? 0) / floatval($ventasActual['bruto']) * 100) : 0
    ];

    // 8. Stock
    $stmtStock = $pdo->query("
        SELECT 
            SUM(stock * precio) as valor_venta,
            SUM(stock * COALESCE((SELECT MAX(precioProveedor) FROM proveedor_producto pp WHERE pp.idProducto = productos.id), 0)) as valor_coste,
            COUNT(CASE WHEN stock <= 0 THEN 1 END) as sin_stock,
            COUNT(CASE WHEN stock > 0 AND stock <= 3 THEN 1 END) as alertas
        FROM productos
        WHERE activo = 1
    ");
    $respuesta['stock'] = $stmtStock->fetch(PDO::FETCH_ASSOC);

    $jsonRespuesta = json_encode($respuesta);

    // Guardar en cache
    Cache::set($claveCache, $jsonRespuesta);

    header('X-Cache: MISS');
    echo $jsonRespuesta;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

