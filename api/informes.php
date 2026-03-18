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

header('Content-Type: application/json');

// Parámetro de segmentación temporal (diario, semanal, mensual, anual)
$periodo = $_GET['periodo'] ?? 'diario'; 

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

    /**
     * Calcula los agregados de ventas (bruto, volumen, métodos de pago) para un intervalo.
     * 
     * @param PDO $pdo Conexión a la base de datos.
     * @param string $ini Fecha de inicio (Y-m-d H:i:s).
     * @param string $fin Fecha de fin (Y-m-d H:i:s).
     * @return array Desglose indexado de estadísticas.
     */
    function getVentasRango($pdo, $ini, $fin) {
        $stmt = $pdo->prepare("
            SELECT 
                SUM(total) as bruto,
                COUNT(*) as num_tickets,
                SUM(CASE WHEN metodoPago = 'efectivo' THEN total ELSE 0 END) as efectivo,
                SUM(CASE WHEN metodoPago = 'tarjeta' THEN total ELSE 0 END) as tarjeta,
                SUM(CASE WHEN metodoPago = 'bizum' THEN total ELSE 0 END) as bizum
            FROM ventas 
            WHERE fecha BETWEEN :inicio AND :fin AND estado = 'completada'
        ");
        $stmt->execute([':inicio' => $ini, ':fin' => $fin]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    $ventasActual = getVentasRango($pdo, $fechaInicio, $fechaFin);
    $ventasAnterior = getVentasRango($pdo, $fechaInicioAnt, $fechaFinAnt);
    
    // Desglose de IVA
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

    // Descuentos
    $stmtDesc = $pdo->prepare("
        SELECT 
            SUM(descuentoValor) as desc_gen,
            SUM(descuentoTarifaValor) as desc_tar,
            SUM(descuentoManualValor) as desc_man
        FROM ventas 
        WHERE fecha BETWEEN :inicio AND :fin AND estado = 'completada'
    ");
    $stmtDesc->execute([':inicio' => $fechaInicio, ':fin' => $fechaFin]);
    $rowDesc = $stmtDesc->fetch(PDO::FETCH_ASSOC);
    $totalDescuentos = floatval($rowDesc['desc_gen'] ?? 0) + floatval($rowDesc['desc_tar'] ?? 0) + floatval($rowDesc['desc_man'] ?? 0);

    // Devoluciones
    $stmtDevo = $pdo->prepare("SELECT SUM(importeTotal) as total FROM devoluciones WHERE fecha BETWEEN :inicio AND :fin");
    $stmtDevo->execute([':inicio' => $fechaInicio, ':fin' => $fechaFin]);
    $devoluciones = $stmtDevo->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

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
            'devoluciones' => floatval($devoluciones),
            'descuentos' => $totalDescuentos,
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
    $stmtProdTop = $pdo->prepare("
        SELECT 
            p.nombre,
            SUM(lv.cantidad) as unidades,
            SUM(lv.subtotal) as ingresos,
            SUM(lv.cantidad * COALESCE(pp.precioProveedor, 0)) as coste
        FROM lineasVenta lv
        JOIN ventas v ON lv.idVenta = v.id
        JOIN productos p ON lv.idProducto = p.id
        LEFT JOIN (SELECT idProducto, MAX(precioProveedor) as precioProveedor FROM proveedor_producto GROUP BY idProducto) pp ON p.id = pp.idProducto
        WHERE v.fecha BETWEEN :inicio AND :fin AND v.estado = 'completada'
        GROUP BY lv.idProducto
        ORDER BY unidades DESC
        LIMIT 10
    ");
    $stmtProdTop->execute([':inicio' => $fechaInicio, ':fin' => $fechaFin]);
    $prodsRanking = $stmtProdTop->fetchAll(PDO::FETCH_ASSOC);
    foreach ($prodsRanking as &$pr) {
        $pr['beneficio'] = floatval($pr['ingresos']) - floatval($pr['coste']);
        $pr['margen'] = ($pr['ingresos'] > 0) ? ($pr['beneficio'] / $pr['ingresos'] * 100) : 0;
    }
    $respuesta['productos_ranking'] = $prodsRanking;

    $stmtProdBottom = $pdo->prepare("
        SELECT p.nombre, COALESCE(SUM(lv.cantidad), 0) as unidades
        FROM productos p
        LEFT JOIN lineasVenta lv ON p.id = lv.idProducto
        LEFT JOIN ventas v ON lv.idVenta = v.id AND v.fecha BETWEEN :inicio AND :fin AND v.estado = 'completada'
        WHERE p.activo = 1
        GROUP BY p.id
        ORDER BY unidades ASC
        LIMIT 5
    ");
    $stmtProdBottom->execute([':inicio' => $fechaInicio, ':fin' => $fechaFin]);
    $respuesta['productos_bottom'] = $stmtProdBottom->fetchAll(PDO::FETCH_ASSOC);

    // 3. Márgenes y Ventas por Categoría
    $stmtCat = $pdo->prepare("
        SELECT 
            c.nombre as categoria,
            SUM(lv.subtotal) as ingresos,
            SUM(lv.cantidad * COALESCE(pp.precioProveedor, 0)) as coste
        FROM lineasVenta lv
        JOIN ventas v ON lv.idVenta = v.id
        JOIN productos p ON lv.idProducto = p.id
        JOIN categorias c ON p.idCategoria = c.id
        LEFT JOIN (SELECT idProducto, MAX(precioProveedor) as precioProveedor FROM proveedor_producto GROUP BY idProducto) pp ON p.id = pp.idProducto
        WHERE v.fecha BETWEEN :inicio AND :fin AND v.estado = 'completada'
        GROUP BY c.id
        ORDER BY ingresos DESC
    ");
    $stmtCat->execute([':inicio' => $fechaInicio, ':fin' => $fechaFin]);
    $catsRanking = $stmtCat->fetchAll(PDO::FETCH_ASSOC);
    foreach ($catsRanking as &$cr) {
        $cr['beneficio'] = floatval($cr['ingresos']) - floatval($cr['coste']);
        $cr['margen'] = ($cr['ingresos'] > 0) ? ($cr['beneficio'] / $cr['ingresos'] * 100) : 0;
    }
    $respuesta['categorias_ranking'] = $catsRanking;

    $stmtMargen = $pdo->prepare("
        SELECT 
            SUM(lv.subtotal) as ingresos,
            SUM(lv.cantidad * COALESCE(pp.precioProveedor, 0)) as coste
        FROM lineasVenta lv
        JOIN ventas v ON lv.idVenta = v.id
        JOIN productos p ON lv.idProducto = p.id
        LEFT JOIN (SELECT idProducto, MAX(precioProveedor) as precioProveedor FROM proveedor_producto GROUP BY idProducto) pp ON p.id = pp.idProducto
        WHERE v.fecha BETWEEN :inicio AND :fin AND v.estado = 'completada'
    ");
    $stmtMargen->execute([':inicio' => $fechaInicio, ':fin' => $fechaFin]);
    $margen = $stmtMargen->fetch(PDO::FETCH_ASSOC);
    $respuesta['margenes'] = [
        'ingresos' => floatval($margen['ingresos'] ?? 0),
        'coste' => floatval($margen['coste'] ?? 0),
        'beneficio' => floatval(($margen['ingresos'] ?? 0) - ($margen['coste'] ?? 0)),
        'porcentaje' => ($margen['ingresos'] > 0) ? ((($margen['ingresos'] - $margen['coste']) / $margen['ingresos']) * 100) : 0
    ];

    // 4. Empleados con Ticket Medio
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
    $empleados = $stmtEmp->fetchAll(PDO::FETCH_ASSOC);
    foreach ($empleados as &$e) {
        $e['ticket_medio'] = $e['tickets'] > 0 ? ($e['total'] / $e['tickets']) : 0;
    }
    $respuesta['empleados'] = $empleados;

    // 5. Franjas Horarias
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
            p.nombre,
            COUNT(*) as veces,
            SUM(d.importeTotal) as total
        FROM devoluciones d
        JOIN productos p ON d.idProducto = p.id
        WHERE d.fecha BETWEEN :inicio AND :fin
        GROUP BY d.idProducto
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

    echo json_encode($respuesta);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

