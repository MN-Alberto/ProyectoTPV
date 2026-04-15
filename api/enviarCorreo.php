<?php
/**
 * Servicio API de Notificaciones Electrónicas.
 * Se encarga de la generación de documentos (Tickets/Facturas) en formato HTML
 * y su despacho vía SMTP hacia el correo electrónico del cliente final.
 * 
 * @author Alberto Méndez
 * @version 1.2 (02/03/2026)
 */

// Iniciamos la sesión para acceder a posibles variables de entorno o estado del usuario
session_start();

// Establecemos la cabecera para que la respuesta sea interpretada como JSON por el cliente (JavaScript)
header('Content-Type: application/json; charset=utf-8');

/**
 * ────────────────────────────────────────────────────────────────────────────
 * 1. CONFIGURACIÓN DEL SERVIDOR SMTP (GMAIL)
 * ────────────────────────────────────────────────────────────────────────────
 * Se definen las constantes de conexión para el envío de correos.
 */
define('SMTP_HOST', 'smtp.gmail.com');                 // Host del servidor SMTP de Google
define('SMTP_USUARIO', 'albertomennun04@gmail.com');   // Dirección de correo remitente
define('SMTP_PASSWORD', 'jdpq cfwd whpm ekmc');        // Contraseña de aplicación generada en Google
define('SMTP_PUERTO', 587);                            // Puerto estándar para TLS (STARTTLS)
define('CORREO_FROM', 'albertomennun04@gmail.com');    // Email de origen para el envío
define('NOMBRE_FROM', 'TPV Bazar');                    // Nombre que aparecerá como remitente


/**
 * ────────────────────────────────────────────────────────────────────────────
 * 2. CARGA DE DEPENDENCIAS (PHPMailer)
 * ────────────────────────────────────────────────────────────────────────────
 * Requerimos los archivos necesarios para instanciar el cliente SMTP.
 */
require_once __DIR__ . '/../core/Exception.php';
require_once __DIR__ . '/../core/PHPMailer.php';
require_once __DIR__ . '/../core/SMTP.php';

// Importamos los espacios de nombres (namespaces) de la librería
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * ────────────────────────────────────────────────────────────────────────────
 * 3. VALIDACIÓN DE LA PETICIÓN
 * ────────────────────────────────────────────────────────────────────────────
 */

// Verificamos que la petición se reciba mediante el método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'mensaje' => 'Método no permitido']);
    exit();
}

// Obtenemos los datos enviados en formato JSON (raw data) y los decodificamos a array asociativo
$datos = json_decode(file_get_contents('php://input'), true);

// Validamos que se haya proporcionado un correo electrónico y que tenga un formato correcto
if (!isset($datos['email']) || !filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['ok' => false, 'mensaje' => 'Email no válido']);
    exit();
}

/** 
 * PROCESAMIENTO DE DATOS DE VENTA
 * Extracción y sanitización de los metadatos de la transacción para su renderizado.
 */
$email = $datos['email'];
$tipoDocumento = $datos['tipoDocumento'] ?? 'ticket'; // Defecto: ticket
$ventaId = $datos['ventaId'] ?? '—';
$total = $datos['total'] ?? '0,00';
$lineas = $datos['lineas'] ?? [];               // Array con los productos de la cesta
$fecha = $datos['fecha'] ?? date('d/m/Y H:i');
$metodoPago = $datos['metodoPago'] ?? 'efectivo';
$entregado = $datos['entregado'] ?? '0,00';
$cambio = $datos['cambio'] ?? '0,00';

$pagoMixtoDesglose = $datos['pagoMixtoDesglose'] ?? null;

// Datos opcionales del cliente (necesarios para facturas)
$clienteNif = $datos['clienteNif'] ?? '';
$clienteNombre = $datos['clienteNombre'] ?? '';
$clienteDir = $datos['clienteDir'] ?? '';
$clienteObs = $datos['clienteObs'] ?? '';

// Datos de Descuento
$descuentoTipo = $datos['descuentoTipo'] ?? 'ninguno';
$descuentoValor = (float) ($datos['descuentoValor'] ?? 0);
$descuentoCupon = $datos['descuentoCupon'] ?? '';

// Datos de Descuento de Tarifa (Cliente registrado, Mayorista)
$descuentoTarifaTipo = $datos['descuentoTarifaTipo'] ?? 'ninguno';
$descuentoTarifaValor = (float) ($datos['descuentoTarifaValor'] ?? 0);
$descuentoTarifaCupon = $datos['descuentoTarifaCupon'] ?? '';

// Datos de Descuento Manual (Código promocional)
// Si no se proporcionan los nuevos campos, usamos los originales como respaldo
$descuentoManualTipo = $datos['descuentoManualTipo'] ?? $descuentoTipo;
$descuentoManualValor = (float) ($datos['descuentoManualValor'] ?? $descuentoValor);
$descuentoManualCupon = $datos['descuentoManualCupon'] ?? $descuentoCupon;

// Si hay descuento de tarifa, usarlo como respaldo si no hay descuento manual
if ($descuentoTarifaCupon && $descuentoTarifaCupon !== '') {
    if ((!$descuentoManualCupon || $descuentoManualCupon === '') && $descuentoManualValor == 0) {
        $descuentoManualTipo = $descuentoTarifaTipo;
        $descuentoManualValor = $descuentoTarifaValor;
        $descuentoManualCupon = $descuentoTarifaCupon;
    }
}

// Inicializamos variables de descuento manual para evitar "Undefined variable"
$importeDescuentoManual = 0;
$textoDescuentoManual = '';

// Puntos de fidelidad
$puntosGanados = (int) ($datos['puntos_ganados'] ?? 0);
$puntosCanjeados = (int) ($datos['puntos_canjeados'] ?? 0);
$puntosBalance = (int) ($datos['puntos_balance'] ?? 0);

// Mensaje personalizado
$mensajePersonalizado = $datos['mensajePersonalizado'] ?? '';

// Determinamos el título principal del documento según la elección del usuario
$isFactura = ($tipoDocumento === 'factura');
$isDevolucion = ($tipoDocumento === 'devolucion');

if ($isFactura) {
    $tipoTitulo = 'FACTURA';
} elseif ($isDevolucion) {
    $tipoTitulo = 'TICKET DE DEVOLUCIÓN';
} else {
    $tipoTitulo = 'TICKET DE VENTA (FACTURA SIMPLIFICADA)';
}

/**
 * ────────────────────────────────────────────────────────────────────────────
 * 5. CONSTRUCCIÓN DE SEGMENTOS HTML
 * ────────────────────────────────────────────────────────────────────────────
 */

// A. Bloque del Emisor (Datos fijos del establecimiento)
$emisorHtml = "
    <strong>TPV Bazar — Productos Informáticos</strong><br>
    NIF: B12345678<br>
    C/ Falsa 123, 23000 León<br>
";

// B. Bloque del Receptor (Solo se muestra si hay datos del cliente o es factura)
$receptorHtml = '';
if ($isFactura || $clienteNif || $clienteNombre) {
    $receptorHtml = "<div style='margin-top: 15px; padding-top: 10px; border-top: 1px dashed #ccc;'>
        <strong>Datos del Cliente:</strong><br>";
    if ($clienteNombre)
        $receptorHtml .= htmlspecialchars($clienteNombre) . "<br>"; // Mostramos el nombre del cliente
    if ($clienteNif)
        $receptorHtml .= "NIF/CIF: " . htmlspecialchars($clienteNif) . "<br>"; // Mostramos el NIF del cliente
    if ($clienteDir)
        $receptorHtml .= htmlspecialchars($clienteDir) . "<br>"; // Mostramos la dirección del cliente
    $receptorHtml .= "</div>";
}

// C. Generación de las filas de productos (Tabla de líneas)
$filasLineas = '';
$sumaTotalesNumeric = 0; // PVP acumulado
$desgloseIva = []; // Para el resumen final por cada %
$ahorrosTarifasAgrupados = []; // NUEVO: Para agrupar ahorros por nombre de tarifa
$importeDescuentoTarifaTotal = 0;

foreach ($lineas as $linea) {
    $cantidad = (float) ($linea['cantidad'] ?? 1);

    // Usamos el PVP unitario real (con descuento aplicado) si viene en el JSON
    // Si no, lo calculamos de la forma tradicional (precioBase + IVA)
    $pvpUnitarioReal = isset($linea['pvpUnitario']) ? (float) $linea['pvpUnitario'] : null;
    $pvpOriginalUnitario = isset($linea['pvpOriginalUnitario']) ? (float) $linea['pvpOriginalUnitario'] : null;

    $precioBase = (float) ($linea['precio'] ?? 0);
    $ivaPorc = (int) ($linea['iva'] ?? 21);

    if ($pvpUnitarioReal !== null) {
        $subtotalPVP = round($pvpUnitarioReal * $cantidad, 2);
        // El precio base lo recalculamos del PVP real para consistencia fiscal
        $precioBaseUnitarioReal = $pvpUnitarioReal / (1 + ($ivaPorc / 100));
    } else {
        $precioPVP = $precioBase * (1 + ($ivaPorc / 100));
        $subtotalPVP = round($precioPVP * $cantidad, 2);
        $precioBaseUnitarioReal = $precioBase;
    }

    $sumaTotalesNumeric += $subtotalPVP;

    // Calcular ahorro si existe pvpOriginalUnitario
    if ($pvpOriginalUnitario !== null && $pvpUnitarioReal !== null) {
        $ahorroUnitario = $pvpOriginalUnitario - $pvpUnitarioReal;
        if ($ahorroUnitario > 0.005) {
            $ahorroLinea = round($ahorroUnitario * $cantidad, 2);
            $tarifaNombre = $linea['tarifaNombre'] ?? 'Tarifa';
            if (!isset($ahorrosTarifasAgrupados[$tarifaNombre])) {
                $ahorrosTarifasAgrupados[$tarifaNombre] = 0;
            }
            $ahorrosTarifasAgrupados[$tarifaNombre] += $ahorroLinea;
            $importeDescuentoTarifaTotal += $ahorroLinea;
        }
    }

    $subtotalBase = round($subtotalPVP / (1 + ($ivaPorc / 100)), 2);
    $subtotalIva = round($subtotalPVP - $subtotalBase, 2);

    // Acumular para el desglose fiscal final
    if (!isset($desgloseIva[$ivaPorc])) {
        $desgloseIva[$ivaPorc] = ['base' => 0, 'cuota' => 0];
    }
    $desgloseIva[$ivaPorc]['base'] += $subtotalBase;
    $desgloseIva[$ivaPorc]['cuota'] += $subtotalIva;

    // Formatear precios para la tabla
    $pvpFmt = number_format($subtotalPVP, 2, ',', '.');
    $unitarioFmt = number_format($precioBaseUnitarioReal, 2, ',', '.');

    $filasLineas .= "
        <tr>
            <td>{$linea['nombre']}</td>
            <td style='text-align:center;'>{$cantidad}</td>
            <td style='text-align:right;'>{$unitarioFmt} €</td>
            <td style='text-align:center;'>{$ivaPorc}%</td>
            <td style='text-align:right;'>{$pvpFmt} €</td>
        </tr>";
}

// Descuento de tarifa total ya calculado en el bucle principal
$importeDescuentoTarifa = $importeDescuentoTarifaTotal;

// Calcular descuento manual (código promocional o porcentaje manual)
if ($descuentoManualCupon && $descuentoManualCupon !== '') {
    if ($descuentoManualTipo === 'porcentaje') {
        $importeDescuentoManual = $sumaTotalesNumeric * ($descuentoManualValor / 100);
        $textoDescuentoManual = "{$descuentoManualValor}%";
    } else if ($descuentoManualTipo === 'fijo') {
        $importeDescuentoManual = $descuentoManualValor;
        $textoDescuentoManual = "Cupón {$descuentoManualCupon}";
    } else {
        $textoDescuentoManual = "Cupón {$descuentoManualCupon}";
    }
}

// Descuento total (solo el manual, ya que las tarifas ya están en sumaTotalesNumeric)
$totalFinalPVP = max(0, $sumaTotalesNumeric - $importeDescuentoManual);
$factorDescuento = $sumaTotalesNumeric > 0 ? ($totalFinalPVP / $sumaTotalesNumeric) : 1;

$totalFinalPVFmt = number_format($totalFinalPVP, 2, ',', '.');

// Bloque de pie de tabla con los sumatorios
$totalesHtml = "<table style='width:100%; border: none; margin-top:10px;' >";

// --- Desglose informativo de ahorros (ya aplicados en las líneas) ---

// Mostrar ahorros por tarifa si existen
if ($importeDescuentoTarifaTotal > 0.01) {
    foreach ($ahorrosTarifasAgrupados as $nombre => $importe) {
        $descFmt = number_format($importe, 2, ',', '.');
        $totalesHtml .= "
        <tr>
            <td style='border: none; color: #16a34a;'><strong>Ahorro {$nombre}:</strong></td>
            <td style='border: none; text-align:right; color: #16a34a;'>- {$descFmt} €*</td>
        </tr>";
    }
}

// Mostrar descuento manual si existe (éste sí resta del total)
if ($importeDescuentoManual > 0.01 && $textoDescuentoManual) {
    $descManualFmt = number_format($importeDescuentoManual, 2, ',', '.');
    $totalesHtml .= "
    <tr>
        <td style='border: none; color: #16a34a;'><strong>Descuento ({$textoDescuentoManual}):</strong></td>
        <td style='border: none; text-align:right; color: #16a34a;'>- {$descManualFmt} €</td>
    </tr>";
}


$totalesHtml .= "
    <tr style='border-top: 1px solid #eee;'>
        <td colspan='2' style='border: none; padding-top:10px;'><strong>Desglose Fiscal:</strong></td>
    </tr>";

ksort($desgloseIva);
foreach ($desgloseIva as $porc => $valores) {
    // Calculamos igual que en vCajero.php (acumulado por lineas sin prorrateo de descuento general)
    $bf = number_format($valores['base'], 2, ',', '.');
    $cf = number_format($valores['cuota'], 2, ',', '.');

    $totalesHtml .= "
        <tr style='font-size: 12px; color: #555;'>
            <td style='border: none;'>Base al {$porc}%:</td>
            <td style='border: none; text-align:right'>{$bf} €</td>
        </tr>
        <tr style='font-size: 12px; color: #555;'>
            <td style='border: none;'>IVA ({$porc}%):</td>
            <td style='border: none; text-align:right'>{$cf} €</td>
        </tr>";
}


$totalFinalPVFmt = number_format($totalFinalPVP, 2, ',', '.');

$totalesHtml .= "
    <tr style='border-top: 1px solid #000;'>
        <td style='border: none; font-size: 1.1rem; padding-top:10px;'><strong>TOTAL:</strong></td>
        <td style='border: none; font-size: 1.1rem; font-weight: bold; text-align:right; padding-top:10px;'>{$totalFinalPVFmt} €</td>
    </tr>
</table>
" . ($importeDescuentoTarifaTotal > 0.01 ? "<div style='font-size: 10px; color: #666; margin-top: 5px;'>* Los ahorros por tarifa ya están aplicados en el precio de cada artículo.</div>" : "");

// E. Bloque de observaciones (si existen)
$obsHtml = '';
if ($clienteObs) {
    $tituloObs = $isDevolucion ? 'Motivo' : 'Observaciones';
    $obsHtml = "<div style='margin-top: 15px; font-size: 13px;'><strong>{$tituloObs}:</strong> " . htmlspecialchars($clienteObs) . "</div>";
}

// F. Bloque de puntos (si aplican)
$puntosHtml = '';
if ($puntosGanados > 0 || $puntosCanjeados > 0 || $puntosBalance > 0) {
    $puntosHtml = "
    <div style='margin-top: 20px; padding: 15px; background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; font-size: 0.9rem;'>
        <div style='color: #d97706; font-weight: bold; margin-bottom: 8px;'>★ Programa de Puntos</div>
        <table style='width:100%; border:none; margin: 0;'>";

    if ($puntosGanados > 0) {
        $puntosHtml .= "<tr><td style='padding:3px 0; color:#16a34a;'>Puntos ganados en esta compra:</td><td style='padding:3px 0; text-align:right; color:#16a34a;'>+ " . number_format($puntosGanados, 0, ',', '.') . "</td></tr>";
    }
    if ($puntosCanjeados > 0) {
        $puntosHtml .= "<tr><td style='padding:3px 0; color:#ef4444;'>Puntos canjeados en esta compra:</td><td style='padding:3px 0; text-align:right; color:#ef4444;'>- " . number_format($puntosCanjeados, 0, ',', '.') . "</td></tr>";
    }

    $puntosHtml .= "
            <tr>
                <td style='padding-top: 8px; border-top: 1px solid #fde68a;'><strong>Saldo disponible:</strong></td>
                <td style='padding-top: 8px; border-top: 1px solid #fde68a; text-align:right;'><strong>" . number_format($puntosBalance, 0, ',', '.') . "</strong></td>
            </tr>
        </table>
    </div>";
}

/**
 * ────────────────────────────────────────────────────────────────────────────
 * 5B. RENDERIZACIÓN DE MÉTODO DE PAGO
 * ────────────────────────────────────────────────────────────────────────────
 */
$metodoPagoHtmlFactura = "<p><strong>Método de pago:</strong> " . strtoupper($metodoPago) . "</p>";
$metodoPagoHtmlTicket = "<p><strong>Método de pago:</strong> " . strtoupper($metodoPago) . "</p>";

if ($metodoPago === 'mixto' && is_array($pagoMixtoDesglose)) {
    $rows = "";
    if (isset($pagoMixtoDesglose['efectivo']) && $pagoMixtoDesglose['efectivo'] > 0) {
        $rows .= "<tr><td style='padding:3px 0;'>💵 Efectivo</td><td style='text-align:right; padding:3px 0;'>" . number_format($pagoMixtoDesglose['efectivo'], 2, ',', '.') . " €</td></tr>";
    }
    if (isset($pagoMixtoDesglose['tarjeta']) && $pagoMixtoDesglose['tarjeta'] > 0) {
        $rows .= "<tr><td style='padding:3px 0;'>💳 Tarjeta</td><td style='text-align:right; padding:3px 0;'>" . number_format($pagoMixtoDesglose['tarjeta'], 2, ',', '.') . " €</td></tr>";
    }
    if (isset($pagoMixtoDesglose['bizum']) && $pagoMixtoDesglose['bizum'] > 0) {
        $rows .= "<tr><td style='padding:3px 0;'>📱 Bizum</td><td style='text-align:right; padding:3px 0;'>" . number_format($pagoMixtoDesglose['bizum'], 2, ',', '.') . " €</td></tr>";
    }
    if (isset($pagoMixtoDesglose['cambio']) && $pagoMixtoDesglose['cambio'] > 0) {
        $rows .= "<tr><td style='padding:3px 0; color:#888;'>Cambio devuelto</td><td style='text-align:right; padding:3px 0; color:#888;'>-" . number_format($pagoMixtoDesglose['cambio'], 2, ',', '.') . " €</td></tr>";
    }

    $metodoPagoHtmlFactura = "
        <p style='font-size:13px; color:#444; font-weight:bold; margin-bottom:6px;'>Forma de pago: MIXTO</p>
        <table style='width:auto; border-collapse:collapse;'>$rows</table>
    ";

    $metodoPagoHtmlTicket = "
        <div style='font-size:12px; font-weight:bold; margin-bottom:4px;'>FORMA DE PAGO: MIXTO</div>
        <table style='width:100%; border-collapse:collapse;'>$rows</table>
    ";
}

/**
 * ────────────────────────────────────────────────────────────────────────────
 * 6. ESTRUCTURA FINAL DEL CUERPO DEL EMAIL (HTML) - IDÉNTICO A imprimirDocumento()
 * ────────────────────────────────────────────────────────────────────────────
 */
if ($isFactura) {
    // FACTURA - Formato exacto copiado de vCajero.php function imprimirDocumento()
    $cuerpo = "
<html>
<head>
    <title>{$tipoTitulo} #{$ventaId}</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; padding: 30px; color: #1a1a1a; max-width: 180mm; margin: 0 auto; line-height: 1.5; }
        .header { border-bottom: 3px solid #2563eb; padding-bottom: 20px; margin-bottom: 25px; }
        .header h1 { margin: 0; font-size: 1.8rem; color: #2563eb; text-transform: uppercase; letter-spacing: 2px; }
        .two-col { display: flex; justify-content: space-between; margin-bottom: 25px; }
        .col { flex: 1; }
        .col h3 { font-size: 0.9rem; color: #666; text-transform: uppercase; margin-bottom: 8px; border-bottom: 1px solid #eee; padding-bottom: 5px; }
        .col p { margin: 3px 0; font-size: 0.9rem; }
        .num-doc { text-align: right; }
        .num-doc .numero { font-size: 1.5rem; font-weight: bold; color: #1a1a1a; }
        .num-doc .fecha { font-size: 0.9rem; color: #666; }
        table.tabla-lineas { width: 100%; border-collapse: collapse; margin: 20px 0; font-size: 0.9rem; }
        table.tabla-lineas th { background: #f8fafc; padding: 10px 8px; text-align: left; border-bottom: 2px solid #2563eb; }
        table.tabla-lineas td { padding: 10px 8px; border-bottom: 1px solid #e5e7eb; }
        .totales-box { float: right; width: 45%; margin-top: 20px; }
        table.tabla-totales { width: 100%; font-size: 0.95rem; }
        table.tabla-totales tr { border-bottom: 1px solid #e5e7eb; }
        table.tabla-totales td { padding: 8px; }
        table.tabla-totales .total-row { background: #2563eb; color: white; font-size: 1.1rem; }
        .footer { margin-top: 40px; padding-top: 20px; border-top: 1px solid #e5e7eb; text-align: center; font-size: 0.85rem; color: #666; }
        .nota { margin-top: 20px; font-size: 0.8rem; color: #888; font-style: italic; }
    </style>
</head>
<body>
    <div class=\"header\">
        <h1>{$tipoTitulo}</h1>
    </div>
    
    <div class=\"two-col\">
        <div class=\"col\">
            <h3>Emisor</h3>
            <p><strong>TPV Bazar — Productos Informáticos</strong></p>
            <p>NIF: B12345678</p>
            <p>C/ Falsa 123, 28000 Madrid</p>
            <p>Tel: 912 345 678</p>
            <p>Email: info@tpvbazar.es</p>
        </div>
        <div class=\"col num-doc\">
            <div class=\"numero\">Nº {$ventaId}</div>
            <div class=\"fecha\">Fecha: {$fecha}</div>
        </div>
    </div>
    
    {$receptorHtml}

    <table class=\"tabla-lineas\">
        <thead>
            <tr>
                <th style=\"width:50%\">Descripción</th>
                <th style=\"text-align:center;width:10%\">Cantidad</th>
                <th style=\"text-align:right;width:15%\">Precio Unit.</th>
                <th style=\"text-align:center;width:10%\">IVA %</th>
                <th style=\"text-align:right;width:15%\">Importe</th>
            </tr>
        </thead>
        <tbody>{$filasLineas}</tbody>
    </table>
    
    <div class=\"totales-box\">
        {$totalesHtml}
    </div>
    
    <div style=\"clear:both\"></div>
    
    {$puntosHtml}
    
    <div class=\"datos-pago\" style=\"margin-top: 25px; padding: 15px; background: #f8fafc; border-radius: 8px;\">
        {$metodoPagoHtmlFactura}
    </div>
    
    {$obsHtml}

    " . (!empty($mensajePersonalizado) ? "
    <div style='margin-top: 20px; padding: 12px; background: #f0f9ff; border-left: 4px solid #3b82f6; border-radius: 4px; font-size: 0.9rem;'>
        <strong>✉️ Mensaje personalizado:</strong><br>
        " . nl2br(htmlspecialchars($mensajePersonalizado)) . "
    </div>
    " : "") . "

    <div class=\"nota\">
        <p>Los precios incluyen IVA. Esta factura está sujeta a las condiciones generales de venta.</p>
    </div>
    
    <div class=\"footer\">
        <p>TPV Bazar — Productos Informáticos | www.tpvbazar.es</p>
    </div>
</body>
</html>";
} else {
    // TICKET - Formato original
    $cuerpo = "
<html>
<head>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #1a1a1a; line-height: 1.4; padding: 20px;}
        .cont { max-width: 600px; margin: 0 auto; background: white; padding: 20px;}
        .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 15px; }
        .header h1 { margin: 0; font-size: 20px; text-transform: uppercase; color: #1a1a2e;}
        .header h2 { margin: 5px 0 0; font-size: 15px; font-weight: normal; color: #666;}
        .datos { margin-bottom: 15px; font-size: 14px; }
        .datos p { margin: 3px 0; }
        table.tabla-lineas { width: 100%; border-collapse: collapse; margin: 10px 0; font-size: 13px; }
        table.tabla-lineas th { background: #f0f0f0; padding: 8px 6px; text-align: left; border-bottom: 1px solid #ccc;  }
        table.tabla-lineas td { padding: 8px 6px; border-bottom: 1px dashed #eee; }
        .footer { text-align: center; color: #666; font-size: 12px; padding-top: 15px; border-top: 1px solid #ccc; margin-top: 20px;}
    </style>
</head>
<body style='background-color:#f0f2f5;'>
    <div class='cont'>
        <div class='header'>
            <h1>{$tipoTitulo}</h1>
            <h2>TPV Bazar — Productos Informáticos</h2>
        </div>
        
        <div class='datos'>
            {$emisorHtml}
            <div style='margin-top: 10px;'>
                <p><strong>Nº Factura/Ticket:</strong> {$ventaId}</p>
                <p><strong>Fecha Operación y Expedición:</strong> {$fecha}</p>
                <div style='margin-top:5px;'>{$metodoPagoHtmlTicket}</div>
            </div>
            {$receptorHtml}
        </div>

        <table class='tabla-lineas'>
            <thead>
                <tr><th>Desc.</th><th style='text-align:center;'>Cant</th><th style='text-align:right;'>Base</th><th style='text-align:center;'>IVA</th><th style='text-align:right;'>PVP</th></tr>
            </thead>
            <tbody>{$filasLineas}</tbody>
        </table>
        
        {$totalesHtml}

        {$puntosHtml}

        " . ($isDevolucion ? "
        <div style='font-size: 13px; margin-top: 15px; border-top: 1px dashed #ccc; padding-top: 10px; text-align:right;'>
            <p style='margin: 3px 0; color: #dc2626; font-weight: bold;'><strong>TOTAL REEMBOLSADO:</strong> -{$totalFinalPVFmt} €</p>
        </div>
        " : "
        <div style='font-size: 13px; margin-top: 15px; border-top: 1px dashed #ccc; padding-top: 10px; text-align:right;'>
            <p style='margin: 3px 0;'><strong>Entregado:</strong> {$entregado} €</p>
            <p style='margin: 3px 0;'><strong>Cambio devuelto:</strong> {$cambio} €</p>
        </div>
        ") . "
        
        {$obsHtml}

        " . (!empty($mensajePersonalizado) ? "
        <div style='margin-top: 15px; padding: 10px; background: #f0f9ff; border-left: 3px solid #3b82f6; border-radius: 4px; font-size: 13px;'>
            <strong>✉️ Mensaje personalizado:</strong><br>
            " . nl2br(htmlspecialchars($mensajePersonalizado)) . "
        </div>
        " : "") . "

        <div class='footer'>
            <p style='font-weight:bold;'>GRACIAS POR SU COMPRA</p>
            <p>Los precios mostrados incluyen IVA.</p>
        </div>
    </div>
</body>
</html>";
}

/**
 * ────────────────────────────────────────────────────────────────────────────
 * 7. PROCESO DE ENVÍO CON PHPMailer
 * ────────────────────────────────────────────────────────────────────────────
 */
$mail = new PHPMailer(true);

try {
    // Configuración SMTP para Gmail
    $mail->isSMTP();
    $mail->Host = SMTP_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = SMTP_USUARIO;
    $mail->Password = SMTP_PASSWORD;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = SMTP_PUERTO;
    $mail->CharSet = 'UTF-8';

    // Destinatarios y Remitente
    $mail->setFrom(CORREO_FROM, NOMBRE_FROM);
    $mail->addAddress($email);                                  // El correo del cliente recogido por el modal

    // Contenido del correo
    $mail->isHTML(true);                                        // Habilitar formato HTML
    $mail->Subject = "TPV Bazar — {$tipoTitulo} #{$ventaId}";  // Asunto del correo
    $mail->Body = $cuerpo;                                   // Cuerpo generado anteriormente

    // Acción de envío
    $mail->send();

    // Si el envío es exitoso, devolvemos respuesta JSON positiva
    echo json_encode(['ok' => true, 'mensaje' => 'Correo enviado correctamente']);

} catch (Exception $e) {
    // Si falla, capturamos el error y lo enviamos al frontend
    echo json_encode([
        'ok' => false,
        'mensaje' => 'No se pudo enviar el correo: ' . $mail->ErrorInfo
    ]);
}
?>