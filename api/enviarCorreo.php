<?php
/*
 * Autor: Alberto Méndez
 * Fecha de actualización: 25/02/2026
 *
 * API para enviar el ticket/factura por correo electrónico.
 * Usa PHPMailer + SMTP de Gmail en lugar de mail() nativo.
 */

session_start();
header('Content-Type: application/json; charset=utf-8');


define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USUARIO', 'albertomennun04@gmail.com');
define('SMTP_PASSWORD', 'jdpq cfwd whpm ekmc');
define('SMTP_PUERTO', 587);
define('CORREO_FROM', 'albertomennun04@gmail.com');
define('NOMBRE_FROM', 'TPV Bazar');


require_once __DIR__ . '/../core/Exception.php';
require_once __DIR__ . '/../core/PHPMailer.php';
require_once __DIR__ . '/../core/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'mensaje' => 'Método no permitido']);
    exit();
}

$datos = json_decode(file_get_contents('php://input'), true);

if (!isset($datos['email']) || !filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['ok' => false, 'mensaje' => 'Email no válido']);
    exit();
}

$email = $datos['email'];
$tipoDocumento = $datos['tipoDocumento'] ?? 'ticket';
$ventaId = $datos['ventaId'] ?? '—';
$total = $datos['total'] ?? '0,00';
$lineas = $datos['lineas'] ?? [];
$fecha = $datos['fecha'] ?? date('d/m/Y H:i');
$metodoPago = $datos['metodoPago'] ?? 'efectivo';
$entregado = $datos['entregado'] ?? '0,00';
$cambio = $datos['cambio'] ?? '0,00';
$clienteNif = $datos['clienteNif'] ?? '';
$clienteNombre = $datos['clienteNombre'] ?? '';
$clienteDir = $datos['clienteDir'] ?? '';
$clienteObs = $datos['clienteObs'] ?? '';

$isFactura = ($tipoDocumento === 'factura');
$tipoTitulo = $isFactura ? 'FACTURA' : 'TICKET DE VENTA (FACTURA SIMPLIFICADA)';

// Emisor fijo
$emisorHtml = "
    <strong>TPV Bazar — Productos Informáticos</strong><br>
    NIF: B12345678<br>
    C/ Falsa 123, 28000 Madrid<br>
";

// Receptor
$receptorHtml = '';
if ($isFactura || $clienteNif || $clienteNombre) {
    $receptorHtml = "<div style='margin-top: 15px; padding-top: 10px; border-top: 1px dashed #ccc;'>
        <strong>Datos del Cliente:</strong><br>";
    if ($clienteNombre)
        $receptorHtml .= htmlspecialchars($clienteNombre) . "<br>";
    if ($clienteNif)
        $receptorHtml .= "NIF/CIF: " . htmlspecialchars($clienteNif) . "<br>";
    if ($clienteDir)
        $receptorHtml .= htmlspecialchars($clienteDir) . "<br>";
    $receptorHtml .= "</div>";
}

// ── Construir el cuerpo HTML ────────────────────────────────────
$filasLineas = '';
$sumaTotalesNumeric = 0;

foreach ($lineas as $linea) {
    $subtotalNumeric = $linea['precio'] * $linea['cantidad'];
    $sumaTotalesNumeric += $subtotalNumeric;

    $subtotal = number_format($subtotalNumeric, 2, ',', '.');
    $precioFmt = number_format($linea['precio'], 2, ',', '.');
    $filasLineas .= "
        <tr>
            <td>{$linea['nombre']}</td>
            <td style='text-align:center;'>{$linea['cantidad']}</td>
            <td style='text-align:right;'>{$precioFmt} €</td>
            <td style='text-align:center;'>21%</td>
            <td style='text-align:right;'>{$subtotal} €</td>
        </tr>";
}

// Cálculos de IVA
$baseImponible = $sumaTotalesNumeric / 1.21;
$cuotaIva = $sumaTotalesNumeric - $baseImponible;

$baseImpFmt = number_format($baseImponible, 2, ',', '.');
$cuotaIvaFmt = number_format($cuotaIva, 2, ',', '.');

$totalesHtml = "
    <table style='width:100%; border: none; margin-top:10px;'>
        <tr>
            <td style='border: none;'><strong>Base Imponible:</strong></td>
            <td style='border: none; text-align:right'>{$baseImpFmt} €</td>
        </tr>
        <tr>
            <td style='border: none;'><strong>Cuota IVA (21%):</strong></td>
            <td style='border: none; text-align:right'>{$cuotaIvaFmt} €</td>
        </tr>
        <tr>
            <td style='border: none; font-size: 1.1rem; padding-top:10px;'><strong>TOTAL (IVA INCLUIDO):</strong></td>
            <td style='border: none; font-size: 1.1rem; font-weight: bold; text-align:right; padding-top:10px;'>{$total} €</td>
        </tr>
    </table>
";

$obsHtml = '';
if ($clienteObs) {
    $obsHtml = "<div style='margin-top: 15px; font-size: 13px;'><strong>Observaciones:</strong> " . htmlspecialchars($clienteObs) . "</div>";
}

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
                <tr><th>Desc.</th><th style='text-align:center;'>Cant</th><th style='text-align:right;'>Precio</th><th style='text-align:center;'>IVA</th><th style='text-align:right;'>Subt.</th></tr>
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

// ── Enviar con PHPMailer ────────────────────────────────────────
$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host = SMTP_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = SMTP_USUARIO;
    $mail->Password = SMTP_PASSWORD;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = SMTP_PUERTO;
    $mail->CharSet = 'UTF-8';

    $mail->setFrom(CORREO_FROM, NOMBRE_FROM);
    $mail->addAddress($email);

    $mail->isHTML(true);
    $mail->Subject = "TPV Bazar — {$tipoTitulo} #{$ventaId}";
    $mail->Body = $cuerpo;

    $mail->send();
    echo json_encode(['ok' => true, 'mensaje' => 'Correo enviado correctamente']);

} catch (Exception $e) {
    echo json_encode([
        'ok' => false,
        'mensaje' => 'No se pudo enviar el correo: ' . $mail->ErrorInfo
    ]);
}
?>