<?php
/**
 * Motor de Agregación de Informes Pro.
 * Procesa datos transaccionales masivos para generar sumarios diarios óptimos,
 * incluyendo desgloses por producto y categoría.
 */
require_once(__DIR__ . '/../config/confDB.php');

set_time_limit(0);
ini_set('memory_limit', '1024M');

try {
    $pdo = new PDO(RUTA, USUARIO, PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Iniciando agregación de datos pro...\n";

    $stmtFechas = $pdo->query("SELECT MIN(DATE(fecha)) as inicio, MAX(DATE(fecha)) as fin FROM ventas");
    $rango = $stmtFechas->fetch(PDO::FETCH_ASSOC);

    if (!$rango['inicio']) {
        die("No hay ventas para procesar.\n");
    }

    $current = new DateTime($rango['inicio']);
    $end = new DateTime($rango['fin']);

    while ($current <= $end) {
        $fecha = $current->format('Y-m-d');
        $ini = $fecha . ' 00:00:00';
        $fin = $fecha . ' 23:59:59';

        echo "Procesando $fecha... ";

        // 1. Agregación Global
        $stmtVentas = $pdo->prepare("
            SELECT 
                SUM(total) as bruto,
                COUNT(*) as tickets,
                SUM(descuentoValor + descuentoTarifaValor + descuentoManualValor) as descuentos
            FROM ventas 
            WHERE fecha BETWEEN :ini AND :fin AND estado = 'completada'
        ");
        $stmtVentas->execute(['ini' => $ini, 'fin' => $fin]);
        $v = $stmtVentas->fetch(PDO::FETCH_ASSOC);

        // 2. Agregación por Producto/Categoría
        $stmtProd = $pdo->prepare("
            SELECT 
                lv.idProducto,
                COALESCE(lv.nombreProducto, p.nombre) as nombreProducto,
                p.idCategoria,
                SUM(lv.cantidad) as unidades,
                SUM(lv.subtotal) as ingresos,
                SUM(lv.cantidad * COALESCE(pp.precioProveedor, 0)) as coste
            FROM lineasVenta lv
            JOIN ventas v ON lv.idVenta = v.id
            LEFT JOIN productos p ON lv.idProducto = p.id
            LEFT JOIN (SELECT idProducto, MAX(precioProveedor) as precioProveedor FROM proveedor_producto GROUP BY idProducto) pp ON lv.idProducto = pp.idProducto
            WHERE v.fecha BETWEEN :ini AND :fin AND v.estado = 'completada'
            GROUP BY lv.idProducto, COALESCE(lv.nombreProducto, p.nombre), p.idCategoria
        ");
        $stmtProd->execute(['ini' => $ini, 'fin' => $fin]);
        $productosDia = $stmtProd->fetchAll(PDO::FETCH_ASSOC);

        $costeTotalDia = 0;
        foreach ($productosDia as $pd) {
            $costeTotalDia += floatval($pd['coste']);
            
            $stmtUpsertProd = $pdo->prepare("
                INSERT INTO informes_productos_diarios (fecha, idProducto, nombreProducto, idCategoria, unidades, ingresos, coste)
                VALUES (:fecha, :idp, :nom, :cat, :uni, :ing, :cos)
                ON DUPLICATE KEY UPDATE unidades = VALUES(unidades), ingresos = VALUES(ingresos), coste = VALUES(coste)
            ");
            $stmtUpsertProd->execute([
                'fecha' => $fecha,
                'idp' => $pd['idProducto'],
                'nom' => $pd['nombreProducto'],
                'cat' => $pd['idCategoria'],
                'uni' => $pd['unidades'],
                'ing' => $pd['ingresos'],
                'cos' => $pd['coste']
            ]);
        }

        // 4. IVA diario
        $stmtIva = $pdo->prepare("
            SELECT 
                lv.iva,
                SUM(lv.subtotal) as total_con_iva
            FROM lineasVenta lv
            JOIN ventas v ON lv.idVenta = v.id
            WHERE v.fecha BETWEEN :ini AND :fin AND v.estado = 'completada'
            GROUP BY lv.iva
        ");
        $stmtIva->execute(['ini' => $ini, 'fin' => $fin]);
        $ivas = $stmtIva->fetchAll(PDO::FETCH_ASSOC);
        foreach ($ivas as $ir) {
            $porcentaje = floatval($ir['iva']);
            $totalConIva = floatval($ir['total_con_iva']);
            $base = $totalConIva / (1 + ($porcentaje / 100));
            $cuota = $totalConIva - $base;
            
            $stmtUpsertIva = $pdo->prepare("
                INSERT INTO informes_iva_diario (fecha, iva, base, cuota)
                VALUES (:fecha, :iva, :base, :cuota)
                ON DUPLICATE KEY UPDATE base = VALUES(base), cuota = VALUES(cuota)
            ");
            $stmtUpsertIva->execute(['fecha' => $fecha, 'iva' => $porcentaje, 'base' => $base, 'cuota' => $cuota]);
        }

        // 5. Empleados diario
        $stmtEmp = $pdo->prepare("
            SELECT v.idUsuario, COUNT(*) as tickets, SUM(v.total) as total
            FROM ventas v
            WHERE v.fecha BETWEEN :ini AND :fin AND v.estado = 'completada'
            GROUP BY v.idUsuario
        ");
        $stmtEmp->execute(['ini' => $ini, 'fin' => $fin]);
        $empleados = $stmtEmp->fetchAll(PDO::FETCH_ASSOC);
        foreach ($empleados as $er) {
            $stmtUpsertEmp = $pdo->prepare("
                INSERT INTO informes_empleados_diario (fecha, idUsuario, tickets, total)
                VALUES (:fecha, :idu, :tix, :tot)
                ON DUPLICATE KEY UPDATE tickets = VALUES(tickets), total = VALUES(total)
            ");
            $stmtUpsertEmp->execute(['fecha' => $fecha, 'idu' => $er['idUsuario'], 'tix' => $er['tickets'], 'tot' => $er['total']]);
        }

        // 6. Horas diario
        $stmtHoras = $pdo->prepare("
            SELECT HOUR(v.fecha) as hora, COUNT(*) as tickets, SUM(v.total) as total
            FROM ventas v
            WHERE v.fecha BETWEEN :ini AND :fin AND v.estado = 'completada'
            GROUP BY HOUR(v.fecha)
        ");
        $stmtHoras->execute(['ini' => $ini, 'fin' => $fin]);
        $horas = $stmtHoras->fetchAll(PDO::FETCH_ASSOC);
        foreach ($horas as $hr) {
            $stmtUpsertHora = $pdo->prepare("
                INSERT INTO informes_horas_diario (fecha, hora, tickets, total)
                VALUES (:fecha, :hora, :tix, :tot)
                ON DUPLICATE KEY UPDATE tickets = VALUES(tickets), total = VALUES(total)
            ");
            $stmtUpsertHora->execute(['fecha' => $fecha, 'hora' => $hr['hora'], 'tix' => $hr['tickets'], 'tot' => $hr['total']]);
        }

        // 7. Devoluciones
        $stmtDevo = $pdo->prepare("SELECT SUM(importeTotal) as total FROM devoluciones WHERE fecha BETWEEN :ini AND :fin");
        $stmtDevo->execute(['ini' => $ini, 'fin' => $fin]);
        $d = $stmtDevo->fetch(PDO::FETCH_ASSOC);

        $bruto = floatval($v['bruto'] ?? 0);
        $coste = $costeTotalDia;
        $beneficio = $bruto - $coste;

        // 8. Sumario Global
        $stmtUpsertGlobal = $pdo->prepare("
            INSERT INTO informes_sumarios_diarios (fecha, total_ventas, num_tickets, total_coste, total_beneficio, descuentos, devoluciones)
            VALUES (:fecha, :bruto, :tickets, :coste, :beneficio, :descuentos, :devoluciones)
            ON DUPLICATE KEY UPDATE 
                total_ventas = VALUES(total_ventas), num_tickets = VALUES(num_tickets),
                total_coste = VALUES(total_coste), total_beneficio = VALUES(total_beneficio),
                descuentos = VALUES(descuentos), devoluciones = VALUES(devoluciones)
        ");
        $stmtUpsertGlobal->execute([
            'fecha' => $fecha,
            'bruto' => $bruto,
            'tickets' => intval($v['tickets'] ?? 0),
            'coste' => $coste,
            'beneficio' => $beneficio,
            'descuentos' => floatval($v['descuentos'] ?? 0),
            'devoluciones' => floatval($d['total'] ?? 0)
        ]);

        echo "OK (" . count($productosDia) . " prods)\n";
        $current->modify('+1 day');
    }

    echo "Finalizado con éxito.\n";

} catch (Exception $e) {
    die("Error: " . $e->getMessage() . "\n");
}
