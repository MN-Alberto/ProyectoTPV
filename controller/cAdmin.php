<?php
/**
 * Controlador de la vista de administración.
 * Gestiona el panel principal, estadísticas de ventas, alertas de stock y arqueos de caja.
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

// Incluimos la configuración de la base de datos
require_once(__DIR__ . '/../config/confDB.php');

// Si el usuario solicita cerrar sesión
if (isset($_REQUEST['cerrarSesion'])) {
    // Registrar logout antes de destruir sesión
    try {
        $pdo = new PDO(RUTA, USUARIO, PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $pdo->prepare("INSERT INTO logs_sistema (tipo, usuario_id, usuario_nombre, descripcion) VALUES ('logout', :usuario_id, :usuario_nombre, :descripcion)");
        $stmt->execute([
            ':usuario_id' => $_SESSION['idUsuario'] ?? null,
            ':usuario_nombre' => $_SESSION['nombreUsuario'] ?? 'Desconocido',
            ':descripcion' => 'Usuario cerró sesión'
        ]);
    } catch (Exception $e) {
        // Silenciar errores de logging
    }

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

/** 
 * Títulos y etiquetas descriptivas para las diferentes secciones de la interfaz.
 * Se actualizan dinámicamente si los datos mostrados pertenecen a la sesión actual o anterior.
 */
$tituloVentas = "Ganancias (Sesión Actual)";
$tituloPedidos = "Ventas realizadas hoy";
$tituloRetiros = "Retiros (Sesión Actual)";
$tituloDevoluciones = "Devoluciones (Sesión Actual)";

// Control de estado de la caja para determinar qué datos se muestran en el dashboard
if ($sesionCaja) {
    // Caja abierta: recuperar estadísticas en tiempo real del turno en curso
    $resumenCaja = Venta::obtenerResumenCajaAbierta();
} else {
    // Caja cerrada: recuperar el último arqueo finalizado para consulta histórica
    $ultimaCaja = Caja::obtenerUltimaSesionCerrada();
    // Si la última caja está cerrada
    if ($ultimaCaja && $ultimaCaja->getFechaCierre()) {
        // Obtenemos el resumen de la caja cerrada
        $resumenCaja = Venta::obtenerResumenCerrada($ultimaCaja->getFechaApertura(), $ultimaCaja->getFechaCierre(), $ultimaCaja->getId());
        // Ajustamos etiquetas para informar al administrador que consulta datos pasados
        $tituloVentas = "Ganancias (Sesión Anterior)";
        $tituloPedidos = "Ventas realizadas (Sesión Anterior)";
        $tituloRetiros = "Retiros (Sesión Anterior)";
        $tituloDevoluciones = "Devoluciones (Sesión Anterior)";
    } else {
        // Fallback: inicializar valores a cero si el sistema no tiene registros previos
        $resumenCaja = [
            'totalGeneral' => 0,
            'efectivo' => ['cantidad' => 0],
            'tarjeta' => ['cantidad' => 0],
            'bizum' => ['cantidad' => 0]
        ];
    }
}

// ✅ OPTIMIZACIÓN: NO CARGAR TODOS LOS PRODUCTOS SOLO PARA CONTAR
$pdo = new PDO(RUTA, USUARIO, PASS);
$stmt = $pdo->prepare("SELECT 
    COUNT(*) as total_productos,
    SUM(CASE WHEN stock <= 3 THEN 1 ELSE 0 END) as alertas_stock
    FROM productos WHERE activo = 1");
$stmt->execute();
$resStock = $stmt->fetch(PDO::FETCH_ASSOC);

$productosTotal = $resStock['total_productos'];
$alertasStock = $resStock['alertas_stock'];

/** 
 * Agregador de métricas principales para los indicadores del dashboard.
 * Contiene totales de ventas, pedidos, inventario crítico y saldo en caja.
 */
$stats = [
    'ventasHoy' => $resumenCaja['totalGeneral'],
    'pedidosHoy' => $resumenCaja['efectivo']['cantidad'] + $resumenCaja['tarjeta']['cantidad'] + $resumenCaja['bizum']['cantidad'],
    'productos' => $productosTotal,
    'alertasStock' => $alertasStock,
    'efectivoCaja' => $sesionCaja ? $sesionCaja->getImporteActual() : (isset($ultimaCaja) && $ultimaCaja ? $ultimaCaja->getCambio() : 0)
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

// Obtener las horas trabajadas en la semana actual
// Fix: Calcular correctamente el lunes de la semana actual para todos los dias (incluyendo domingo)
$hoy = new DateTime();
$inicioSemana = (clone $hoy)->modify('monday this week')->setTime(0, 0, 0);

// Si hoy es domingo, strtotime devuelve lunes siguiente, por lo que debemos corregirlo
if ($hoy->format('N') == 7) {
    $inicioSemana->modify('-7 days');
}

$pdo = new PDO(RUTA, USUARIO, PASS);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$stmt = $pdo->prepare("SELECT fechaApertura, fechaCierre FROM caja_sesiones WHERE fechaApertura >= :inicioSemana");
$stmt->execute([':inicioSemana' => $inicioSemana->format('Y-m-d H:i:s')]);
$totalHoras = 0.0;
while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $fechaApertura = new DateTime($fila['fechaApertura']);

    // Si no hay fecha de cierre = sesión abierta actualmente, usar hora actual
    if ($fila['fechaCierre']) {
        $fechaCierre = new DateTime($fila['fechaCierre']);
    } else {
        $fechaCierre = new DateTime();
    }

    $diferencia = $fechaCierre->getTimestamp() - $fechaApertura->getTimestamp();
    $horas = $diferencia / 3600;
    $totalHoras += $horas;
}
$stats['horasTrabajadasSemana'] = $totalHoras;

// Calcular ganancias netas: Ventas - Devoluciones - Retiros
$stats['gananciasHoy'] = $stats['ventasHoy'] - $stats['devolucionesHoy'] - $stats['retirosHoy'];

// Llamamos a la vista
require_once $view['Layout'];
?>