<?php
/**
 * Motor de Agregación de Informes Pro.
 * Procesa datos transaccionales masivos para generar sumarios diarios óptimos,
 * incluyendo desgloses por producto y categoría.
 */
require_once(__DIR__ . '/../config/confDB.php');

/**
 * CONFIGURACIÓN PARA PROCESOS MASIVOS
 * 
 * Este script puede procesar millones de registros.
 * - Se elimina el limite de tiempo de ejecución
 * - Se aumenta el limite de memoria a 1GB para evitar fallos
 * Se ejecuta por CLI o cron, no por petición web normal.
 */
set_time_limit(0);
ini_set('memory_limit', '1024M');

try {
    $pdo = new PDO(RUTA, USUARIO, PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Iniciando agregación de datos pro...\n";

    /**
     * CALCULAR RANGO COMPLETO DE FECHAS
     * 
     * Se procesan TODOS los dias desde la PRIMERA venta registrada
     * hasta la ULTIMA venta existente. Incluye dias sin ventas.
     * Esto asegura que no queden huecos en las estadisticas.
     */
    $stmtFechas = $pdo->query("SELECT MIN(DATE(fecha)) as inicio, MAX(DATE(fecha)) as fin FROM ventas");
    $rango = $stmtFechas->fetch(PDO::FETCH_ASSOC);

    if (!$rango['inicio']) {
        die("No hay ventas para procesar.\n");
    }

    // Iniciar cursor en el primer dia de ventas
    $current = new DateTime($rango['inicio']);
    $end = new DateTime($rango['fin']);

    /**
     * BUCLE PRINCIPAL POR DIA
     * 
     * Se procesa un dia completo en cada iteración.
     * Todas las estadisticas se calculan de forma aislada por dia natural.
     */
    while ($current <= $end) {
        $fecha = $current->format('Y-m-d');
        // Definir rango horario completo del dia
        $ini = $fecha . ' 00:00:00';
        $fin = $fecha . ' 23:59:59';

        echo "Procesando $fecha... ";

        /**
         * PASO 1: AGREGACIÓN GLOBAL DIARIA
         * 
         * Obtiene los totales basicos del dia:
         * - Importe bruto total de ventas
         * - Numero de tickets completados
         * - Suma total de descuentos aplicados
         */
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

        /**
         * PASO 2: AGREGACIÓN POR PRODUCTO
         * 
         * Calcula estadisticas por cada producto individual:
         * - Unidades vendidas
         * - Ingresos totales
         * - Coste de mercancia
         * 
         * OPTIMIZACIÓN: Se obtiene el precio de proveedor con una subconsulta
         * agrupada para evitar duplicados y obtener siempre el ultimo precio.
         */
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
            // Acumular coste total del dia para el calculo final de margen
            $costeTotalDia += floatval($pd['coste']);

            /**
             * PATRON UPSERT: Actualizar o Insertar
             * 
             * Si ya existen datos para este producto y este dia, se actualizan.
             * Si no existen, se crea un registro nuevo automaticamente.
             * Esto permite volver a ejecutar el script tantas veces como se quiera
             * sin generar registros duplicados.
             */
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

        /**
         * PASO 3: DESGLOCE DE IVA DIARIO
         * 
         * Separa los importes por tipo de IVA para contabilidad.
         * Se calcula la base imponible y la cuota de IVA automaticamente
         * a partir del importe total con IVA de cada linea de venta.
         */
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

        /**
         * PASO 4: ESTADISTICAS POR EMPLEADO
         * 
         * Agrupa las ventas por cada usuario del sistema para medir
         * el rendimiento individual de cada cajero.
         */
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

        /**
         * PASO 5: DISTRIBUCIÓN POR HORARIO
         * 
         * Agrupa las ventas por hora del dia para identificar
         * las horas punta y el comportamiento de los clientes.
         * Se usa para optimizar horarios de personal.
         */
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

        /**
         * PASO 6: DEVOLUCIONES
         * 
         * Obtiene el importe total de devoluciones realizadas durante el dia.
         * Las devoluciones se restan del total de ventas en el informe final.
         */
        // 7. Devoluciones
        $stmtDevo = $pdo->prepare("SELECT SUM(importeTotal) as total FROM devoluciones WHERE fecha BETWEEN :ini AND :fin");
        $stmtDevo->execute(['ini' => $ini, 'fin' => $fin]);
        $d = $stmtDevo->fetch(PDO::FETCH_ASSOC);

        // CALCULO DEL BENEFICIO BRUTO DEL DIA
        $bruto = floatval($v['bruto'] ?? 0);
        $coste = $costeTotalDia;
        $beneficio = $bruto - $coste;

        /**
         * PASO 7: SUMARIO GLOBAL DIARIO
         * 
         * Guarda todos los totales finales del dia en una sola fila.
         * Esta tabla es la que usan los informes de administración
         * para mostrar los indicadores generales de forma instantanea.
         */
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
