<?php
/*
 * Autor: Alberto Méndez
 * Fecha de actualización: 25/02/2026
 *
 * API para enviar el ticket/factura por correo electrónico.
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

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

// Construir el cuerpo del correo en HTML.
$tipoTitulo = ($tipoDocumento === 'factura') ? 'FACTURA' : 'TICKET DE VENTA';

$cuerpo = "
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; color: #333; }
        .header { background: #1a1a2e; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th { background: #f0f2f5; padding: 10px; text-align: left; border-bottom: 2px solid #ddd; }
        td { padding: 10px; border-bottom: 1px solid #eee; }
        .total { font-size: 18px; font-weight: bold; text-align: right; padding: 15px 0; }
        .footer { text-align: center; color: #999; font-size: 12px; padding: 20px; }
    </style>
</head>
<body>
    <div class='header'>
        <h1>TPV Bazar — Productos Informáticos</h1>
        <h2>{$tipoTitulo}</h2>
    </div>
    <div class='content'>
        <p><strong>Nº:</strong> {$ventaId}</p>
        <p><strong>Fecha:</strong> {$fecha}</p>
        <p><strong>Método de pago:</strong> {$metodoPago}</p>
        <table>
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Cant.</th>
                    <th>Precio</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>";

foreach ($lineas as $linea) {
    $subtotal = number_format($linea['precio'] * $linea['cantidad'], 2, ',', '.');
    $precioFmt = number_format($linea['precio'], 2, ',', '.');
    $cuerpo .= "
                <tr>
                    <td>{$linea['nombre']}</td>
                    <td>{$linea['cantidad']}</td>
                    <td>{$precioFmt} €</td>
                    <td>{$subtotal} €</td>
                </tr>";
}

$cuerpo .= "
            </tbody>
        </table>
        <div class='total'>TOTAL: {$total} €</div>
    </div>
    <div class='footer'>
        <p>TPV Bazar — Productos Informáticos</p>
        <p>Gracias por su compra</p>
    </div>
</body>
</html>";

// Headers del correo.
$asunto = "TPV Bazar — {$tipoTitulo} #{$ventaId}";
$headers = "MIME-Version: 1.0\r\n";
$headers .= "Content-type: text/html; charset=UTF-8\r\n";
$headers .= "From: TPV Bazar <noreply@tpvbazar.com>\r\n";

// Intentar enviar el correo.
$enviado = @mail($email, $asunto, $cuerpo, $headers);

if ($enviado) {
    echo json_encode(['ok' => true, 'mensaje' => 'Correo enviado correctamente']);
} else {
    echo json_encode(['ok' => false, 'mensaje' => 'No se pudo enviar el correo. Verifique la configuración del servidor de correo.']);
}
?>