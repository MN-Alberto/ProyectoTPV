<?php
require_once(__DIR__ . '/../model/Venta.php');
require_once(__DIR__ . '/../core/Verifactu.php');

$venta = new Venta();
$venta->setSerie('D');
$venta->setNumero(1);
$venta->setFecha('2026-04-28 10:00:00');
$venta->setTotal(-10.50);

echo "URL: " . Verifactu::generarURLQR($venta) . "\n";
