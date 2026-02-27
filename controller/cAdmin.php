<?php
/*
 * Autor: Alberto Méndez 
 * Fecha de actualización: 27/02/2026
 * 
 * Controlador de la vista de administrador.
 */

require_once(__DIR__ . '/../model/Venta.php');
require_once(__DIR__ . '/../model/Caja.php');
require_once(__DIR__ . '/../model/Producto.php');

// Si no hay una sesión iniciada o el usuario no es admin, redirigimos al login.
if (!isset($_SESSION['rolUsuario']) || $_SESSION['rolUsuario'] !== 'admin') {
    $_SESSION['paginaEnCurso'] = 'login';
    header('Location: index.php');
    exit();
}

// Si el usuario solicita cerrar sesión.
if (isset($_REQUEST['cerrarSesion'])) {
    session_destroy();
    header('Location: index.php');
    exit();
}

// Cargar estado de la caja
$sesionCaja = Caja::obtenerSesionAbierta();

$tituloVentas = "Ventas (Sesión Actual)";
$tituloPedidos = "Ventas realizadas hoy";

// Lógica para el dashboard
if ($sesionCaja) {
    // Caja abierta
    $resumenCaja = Venta::obtenerResumenCajaAbierta();
} else {
    // Buscar la última caja cerrada
    $ultimaCaja = Caja::obtenerUltimaSesionCerrada();
    if ($ultimaCaja && $ultimaCaja->getFechaCierre()) {
        $resumenCaja = Venta::obtenerResumenCerrada($ultimaCaja->getFechaApertura(), $ultimaCaja->getFechaCierre(), $ultimaCaja->getId());
        $tituloVentas = "Ventas (Sesión Anterior)";
        $tituloPedidos = "Ventas realizadas (Sesión Anterior)";
    } else {
        $resumenCaja = [
            'totalGeneral' => 0,
            'efectivo' => ['cantidad' => 0],
            'tarjeta' => ['cantidad' => 0],
            'bizum' => ['cantidad' => 0]
        ];
    }
}

$productosTotal = Producto::obtenerTodos();
$alertasStock = 0;
foreach ($productosTotal as $p) {
    if ($p->getStock() <= 3)
        $alertasStock++;
}

$stats = [
    'ventasHoy' => $resumenCaja['totalGeneral'],
    'pedidosHoy' => $resumenCaja['efectivo']['cantidad'] + $resumenCaja['tarjeta']['cantidad'] + $resumenCaja['bizum']['cantidad'],
    'productos' => count($productosTotal),
    'alertasStock' => $alertasStock
];

require_once $view['Layout'];
?>