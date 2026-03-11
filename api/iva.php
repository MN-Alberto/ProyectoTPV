<?php
/**
 * API para gestionar los tipos de IVA
 * @author Alberto Méndez
 * @version 1.0 (11/03/2026)
 */

// Iniciamos la sesión
session_start();

// Requerimos los archivos necesarios
require_once(__DIR__ . '/../config/confDB.php');
require_once(__DIR__ . '/../model/Iva.php');

// Indicamos al navegador que es un tipo JSON
header('Content-Type: application/json; charset=utf-8');

try {
    // ── ELIMINAR TIPO DE IVA (DELETE) ──
    if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        $id = isset($_GET['eliminar']) ? (int) $_GET['eliminar'] : 0;

        if ($id > 0) {
            $iva = Iva::buscarPorId($id);
            if ($iva && $iva->eliminar()) {
                echo json_encode(['ok' => true]);
            } else {
                http_response_code(400);
                echo json_encode(['ok' => false, 'error' => 'No se pudo eliminar el tipo de IVA. Puede que esté asignado a productos.']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'ID de IVA inválido.']);
        }
        exit();
    }

    // ── POST (crear o actualizar) ──
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $id = (int) ($_POST['id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $porcentaje = (float) ($_POST['porcentaje'] ?? 0);

        if (empty($nombre)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'El nombre es obligatorio.']);
            exit();
        }

        if ($porcentaje < 0 || $porcentaje > 100) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'El porcentaje debe estar entre 0 y 100.']);
            exit();
        }

        if ($id > 0) {
            // Actualizar
            $iva = Iva::buscarPorId($id);
            if (!$iva) {
                http_response_code(404);
                echo json_encode(['ok' => false, 'error' => 'Tipo de IVA no encontrado.']);
                exit();
            }
            $iva->setNombre($nombre);
            $iva->setPorcentaje($porcentaje);
            if ($iva->actualizar()) {
                echo json_encode(['ok' => true]);
            } else {
                http_response_code(400);
                echo json_encode(['ok' => false, 'error' => 'No se pudo actualizar el tipo de IVA.']);
            }
        } else {
            // Crear nuevo
            $iva = new Iva();
            $iva->setNombre($nombre);
            $iva->setPorcentaje($porcentaje);
            if ($iva->insertar()) {
                echo json_encode(['ok' => true, 'id' => $iva->getId()]);
            } else {
                http_response_code(400);
                echo json_encode(['ok' => false, 'error' => 'No se pudo crear el tipo de IVA.']);
            }
        }
        exit();
    }

    // ── GET (listar todos) ──
    $tipos = Iva::obtenerTodos();
    $resultado = [];
    foreach ($tipos as $tipo) {
        $resultado[] = [
            'id' => (int) $tipo->getId(),
            'nombre' => $tipo->getNombre(),
            'porcentaje' => (float) $tipo->getPorcentaje()
        ];
    }

    echo json_encode($resultado);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
