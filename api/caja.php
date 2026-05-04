<?php
/**
 * API de Acciones Rápidas de Caja.
 * Permite realizar operaciones administrativas ligeras sobre la sesión activa,
 * como la gestión de notificaciones de interrupción de turno.
 * 
 * @author Alberto Méndez
 * @version 1.1 (04/03/2026)
 */
// Iniciar sesión para verificar usuario autenticado
session_start();

/**
 * CARGA DE DEPENDENCIAS
 * 
 * Esta API es ligera y solo necesita el modelo Caja,
 * no requiere conexión directa a base de datos.
 */
require_once(__DIR__ . '/../model/Caja.php');

// Establecer tipo de respuesta JSON
header('Content-Type: application/json');

/**
 * CONTROL DE ACCESO
 * 
 * Cualquier usuario con sesión iniciada puede usar esta API,
 * no se requiere rol de administrador. Los cajeros normales
 * necesitan estas acciones para su trabajo diario.
 */
if (!isset($_SESSION['idUsuario'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

/**
 * SISTEMA DE ACCIONES RÁPIDAS
 * 
 * Esta API esta diseñada para ser llamada mediante AJAX desde
 * el frontend sin recargar la pagina. Se usan peticiones tanto
 * GET como POST, por eso se utiliza $_REQUEST.
 */
$accion = $_REQUEST['accion'] ?? '';

/**
 * ACCIÓN: Limpiar Interrupción de sesión
 * 
 * Cuando el cajero cierra el navegador o se pierde la conexión
 * y luego vuelve a entrar, se muestra un aviso indicando que
 * se ha recuperado la sesión. Esta acción elimina ese aviso.
 * 
 * IMPORTANTE: Hay que limpiarlo tanto en base de datos como
 * en la sesión PHP local para que no vuelva a aparecer.
 */
if ($accion === 'limpiarInterrupcion') {
    // Obtener la sesion de caja abierta actualmente
    $sesionCaja = Caja::obtenerSesionAbierta();

    if ($sesionCaja) {
        // Primero marcar como limpia en base de datos
        if ($sesionCaja->limpiarInterrupcion()) {
            // Despues limpiar tambien la variable de sesion local
            // Si no se hace aqui, volvera a aparecer en la siguiente carga de pagina
            unset($_SESSION['interrupcionRecuperada']);

            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al limpiar la interrupción']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'No hay sesión de caja abierta']);
    }
    exit();
}

echo json_encode(['success' => false, 'message' => 'Acción no válida']);
