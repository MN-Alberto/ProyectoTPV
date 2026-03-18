<?php
/**
 * API de Acciones Rápidas de Caja.
 * Permite realizar operaciones administrativas ligeras sobre la sesión activa,
 * como la gestión de notificaciones de interrupción de turno.
 * 
 * @author Alberto Méndez
 * @version 1.1 (04/03/2026)
 */
session_start();
require_once(__DIR__ . '/../model/Caja.php');

header('Content-Type: application/json');

if (!isset($_SESSION['idUsuario'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

$accion = $_REQUEST['accion'] ?? '';

/**
 * ACCIÓN: Limpiar Interrupción.
 * Desactiva el aviso de "sesión recuperada" tras un descanso o pausa del cajero.
 */
if ($accion === 'limpiarInterrupcion') {
    $sesionCaja = Caja::obtenerSesionAbierta();
    if ($sesionCaja) {
        if ($sesionCaja->limpiarInterrupcion()) {
            // También limpiar de la sesión para evitar que PHP lo vuelva a pasar a la vista en la siguiente carga
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
