<?php
/**
 * TPV Bazar - API de Envío de Ticket/Factura por Correo Electrónico
 * 
 * Este script procesa una petición POST con los datos de una venta (ID, fecha, productos, totales, cliente),
 * construye un documento HTML con formato profesional (Ticket o Factura) y lo envía al correo
 * electrónico proporcionado utilizando la librería PHPMailer y el servidor SMTP de Gmail.
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
 * ────────────────────────────────────────────────────────────────────────────
 * 4. EXTRACCIÓN Y LIMPIEZA DE DATOS DE LA VENTA
 * ────────────────────────────────────────────────────────────────────────────
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

// Determinamos el título principal del documento según la elección del usuario
$isFactura = ($tipoDocumento === 'factura');
$tipoTitulo = $isFactura ? 'FACTURA' : 'TICKET DE VENTA (FACTURA SIMPLIFICADA)';

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
    $pvpUnitarioReal = isset($linea['pvpUnitario']) ? (float)$linea['pvpUnitario'] : null;
    $pvpOriginalUnitario = isset($linea['pvpOriginalUnitario']) ? (float)$linea['pvpOriginalUnitario'] : null;
    
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

    // Acumular para el desglose fiscal final (usando el precio base real tras tarifa)
    if (!isset($desgloseIva[$ivaPorc])) {
        $desgloseIva[$ivaPorc] = ['pvpAcumulado' => 0]; // Agrupamos por PVP para back-calculate
    }
    $desgloseIva[$ivaPorc]['pvpAcumulado'] += $subtotalPVP;

    // Formatear precios para la tabla
    $pvpFmt = number_format($subtotalPVP, 2, ',', '.');
    $unitarioFmt = number_format($pvpUnitarioReal ?? ($precioBase * (1 + ($ivaPorc / 100))), 2, ',', '.');

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
    // Cálculo exacto del desglose fiscal prorrateando el descuento manual
    $pvpFinalConDescuento = $valores['pvpAcumulado'] * $factorDescuento;
    
    $baseFinal = round($pvpFinalConDescuento / (1 + ($porc / 100)), 2);
    $cuotaFinal = round($pvpFinalConDescuento - $baseFinal, 2);

    $bf = number_format($baseFinal, 2, ',', '.');
    $cf = number_format($cuotaFinal, 2, ',', '.');

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
    $obsHtml = "<div style='margin-top: 15px; font-size: 13px;'><strong>Observaciones:</strong> " . htmlspecialchars($clienteObs) . "</div>";
}

/**
 * ────────────────────────────────────────────────────────────────────────────
 * 6. ESTRUCTURA FINAL DEL CUERPO DEL EMAIL (HTML)
 * ────────────────────────────────────────────────────────────────────────────
 */
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
                <p><strong>Método de pago:</strong> {$metodoPago}</p>
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

        <div style='font-size: 13px; margin-top: 15px; border-top: 1px dashed #ccc; padding-top: 10px; text-align:right;'>
            <p style='margin: 3px 0;'><strong>Entregado:</strong> {$entregado} €</p>
            <p style='margin: 3px 0;'><strong>Cambio devuelto:</strong> {$cambio} €</p>
        </div>
        
        {$obsHtml}

        <div class='footer'>
            <p style='font-weight:bold;'>GRACIAS POR SU COMPRA</p>
            <p>Los precios mostrados incluyen IVA.</p>
        </div>
    </div>
</body>
</html>";

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