<?php
/**
 * Controlador de la vista de administrador.
 * 
 * @author Alberto Méndez
 * @version 1.4 (02/03/2026)
 */

// Incluimos los modelos necesarios
require_once(__DIR__ . '/../model/Venta.php');
require_once(__DIR__ . '/../model/Caja.php');
require_once(__DIR__ . '/../model/Producto.php');
require_once(__DIR__ . '/../model/Usuario.php');

// Si no hay una sesión iniciada o el usuario no es admin, redirigimos al login.
if (!isset($_SESSION['rolUsuario']) || $_SESSION['rolUsuario'] !== 'admin') {
    $_SESSION['paginaEnCurso'] = 'login';
    header('Location: index.php');
    exit();
}

// Si el usuario solicita cerrar sesión
if (isset($_REQUEST['cerrarSesion'])) {
    // Limpiar todas las variables de sesión
    $_SESSION = array();
    // Destruimos la sesión
    session_destroy();
    // Redirigimos al login
    header('Location: index.php');
    exit();
}

// Obtenemos la sesión de la caja
$sesionCaja = Caja::obtenerSesionAbierta();

// Definimos los títulos de las ventas y pedidos
$tituloVentas = "Ventas (Sesión Actual)";
$tituloPedidos = "Ventas realizadas hoy";
$tituloRetiros = "Retiros (Sesión Actual)";
$tituloDevoluciones = "Devoluciones (Sesión Actual)";

// Si la caja está abierta
if ($sesionCaja) {
    // Obtenemos el resumen de la caja abierta
    $resumenCaja = Venta::obtenerResumenCajaAbierta();
} else {
    // Obtenemos la última caja cerrada
    $ultimaCaja = Caja::obtenerUltimaSesionCerrada();
    // Si la última caja está cerrada
    if ($ultimaCaja && $ultimaCaja->getFechaCierre()) {
        // Obtenemos el resumen de la caja cerrada
        $resumenCaja = Venta::obtenerResumenCerrada($ultimaCaja->getFechaApertura(), $ultimaCaja->getFechaCierre(), $ultimaCaja->getId());
        // Actualizamos los títulos
        $tituloVentas = "Ventas (Sesión Anterior)";
        $tituloPedidos = "Ventas realizadas (Sesión Anterior)";
        $tituloRetiros = "Retiros (Sesión Anterior)";
        $tituloDevoluciones = "Devoluciones (Sesión Anterior)";
    } else {
        // Array para el resumen de las ventas al hacer caja
        $resumenCaja = [
            'totalGeneral' => 0,
            'efectivo' => ['cantidad' => 0],
            'tarjeta' => ['cantidad' => 0],
            'bizum' => ['cantidad' => 0]
        ];
    }
}

// Obtenemos todos los productos
$productosTotal = Producto::obtenerTodos();
// Inicializamos el contador de alertas de stock
$alertasStock = 0;
// Recorremos todos los productos
foreach ($productosTotal as $p) {
    // Si el stock es menor o igual a 3, incrementamos el contador
    if ($p->getStock() <= 3)
        $alertasStock++;
}

// Array para el dashboard de la vista del admin
$stats = [
    'ventasHoy' => $resumenCaja['totalGeneral'],
    'pedidosHoy' => $resumenCaja['efectivo']['cantidad'] + $resumenCaja['tarjeta']['cantidad'] + $resumenCaja['bizum']['cantidad'],
    'productos' => count($productosTotal),
    'alertasStock' => $alertasStock
];

// Obtener el total de retiros según corresponda
if ($sesionCaja) {
    // Caja abierta: obtener retiros de la sesión actual
    $stats['retirosHoy'] = $sesionCaja->getTotalRetiros();
} elseif (isset($ultimaCaja) && $ultimaCaja) {
    // Caja cerrada: obtener retiros de la última sesión
    $stats['retirosHoy'] = $ultimaCaja->getTotalRetiros();
} else {
    $stats['retirosHoy'] = 0;
}

// Obtener el total de devoluciones según corresponda
if ($sesionCaja) {
    // Caja abierta: obtener devoluciones de la sesión actual
    $stats['devolucionesHoy'] = Devolucion::obtenerTotalPorSesion($sesionCaja->getId());
} elseif (isset($ultimaCaja) && $ultimaCaja) {
    // Caja cerrada: obtener devoluciones de la última sesión
    $stats['devolucionesHoy'] = Devolucion::obtenerTotalPorSesion($ultimaCaja->getId());
} else {
    $stats['devolucionesHoy'] = 0;
}

// Llamamos a la vista
require_once $view['Layout'];
?>