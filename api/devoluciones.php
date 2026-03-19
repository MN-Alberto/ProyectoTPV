<?php
/**
 * API de Gestión de Devoluciones y Abonos.
 * Proporciona acceso al historial de devoluciones procesadas, permitiendo
 * la auditoría de abonos y la consulta de mercancía retornada al inventario.
 * 
 * @author Alberto Méndez
 * @version 1.0 (03/03/2026)
 */

session_start();
require_once(__DIR__ . '/../config/confDB.php');
require_once(__DIR__ . '/../model/Devolucion.php');

header('Content-Type: application/json; charset=utf-8');

// Verificar si el usuario es administrador
if (!isset($_SESSION['rolUsuario']) || $_SESSION['rolUsuario'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Acceso denegado']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

/** 
 * MANEJADOR DE CONSULTAS (GET)
 * Permite listar todas las devoluciones con filtros dinámicos.
 */
if ($method === 'GET') {
    if (isset($_GET['todas'])) {
        $orden = $_GET['orden'] ?? 'fecha_desc';
        $filtroFecha = $_GET['filtroFecha'] ?? null;
        $busqueda = $_GET['busqueda'] ?? null;
        $pagina = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
        $porPagina = isset($_GET['porPagina']) ? min(50, max(1, intval($_GET['porPagina']))) : 10;
        try {
            $devoluciones = Devolucion::obtenerTodas($orden, $filtroFecha, $busqueda, $pagina, $porPagina);
            echo json_encode($devoluciones);
        }
        catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => 'Error al obtener devoluciones: ' . $e->getMessage()]);
        }
        exit;
    }

    if (isset($_GET['id'])) {
        // Implementar detalle si es necesario, por ahora obtenerTodas ya trae lo básico
        http_response_code(501);
        echo json_encode(['ok' => false, 'error' => 'No implementado']);
        exit;
    }
}

http_response_code(405);
echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
