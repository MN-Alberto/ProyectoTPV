<?php
/**
 * API de Clasificación de Productos (Categorías).
 * Proporciona endpoints para organizar el catálogo en diferentes grupos lógicos,
 * permitiendo la gestión administrativa de etiquetas y descripciones de categoría.
 * 
 * @author Alberto Méndez
 * @version 1.1 (05/03/2026)
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once(__DIR__ . '/../config/confDB.php');
require_once(__DIR__ . '/../model/Categoria.php');

// Iniciamos la sesión
session_start();

header('Content-Type: application/json; charset=utf-8');

try {
    /** 
     * MANEJADOR DE EDICIÓN (POST con flag 'editar')
     * Actualiza la información de una categoría existente.
     */
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar'])) {
        $id = $_POST['editar'];
        $nombre = $_POST['nombre'] ?? '';
        $descripcion = $_POST['descripcion'] ?? '';

        if (empty($nombre)) {
            http_response_code(400);
            echo json_encode(['error' => 'El nombre es obligatorio']);
            exit();
        }

        $categoria = Categoria::buscarPorId($id);
        if (!$categoria) {
            http_response_code(404);
            echo json_encode(['error' => 'Categoría no encontrada']);
            exit();
        }

        $categoria->setNombre($nombre);
        $categoria->setDescripcion($descripcion);
        if ($categoria->actualizar()) {
            // Registrar log de modificación de categoría
            try {
                $pdoLog = new PDO(RUTA, USUARIO, PASS);
                $pdoLog->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $adminId = $_SESSION['id'] ?? null;
                $adminNombre = $_SESSION['nombre'] ?? 'Admin';

                $stmtLog = $pdoLog->prepare("INSERT INTO logs_sistema (tipo, usuario_id, usuario_nombre, descripcion) VALUES ('modificacion_categoria', :usuario_id, :usuario_nombre, :descripcion)");
                $stmtLog->execute([
                    ':usuario_id' => $adminId,
                    ':usuario_nombre' => $adminNombre,
                    ':descripcion' => 'Categoría modificada: ' . $nombre
                ]);
            } catch (Exception $e) {
                // Silenciar errores de logging
            }

            echo json_encode(['ok' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error al actualizar la categoría']);
        }
        exit();
    }

    /** 
     * MANEJADOR DE ALTA (POST)
     * Registra una nueva agrupación de productos en el sistema.
     */
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $nombre = $_POST['nombre'] ?? '';
        $descripcion = $_POST['descripcion'] ?? '';

        if (empty($nombre)) {
            http_response_code(400);
            echo json_encode(['error' => 'El nombre es obligatorio']);
            exit;
        }

        $categoria = new Categoria();
        $categoria->setNombre($nombre);
        $categoria->setDescripcion($descripcion);

        if ($categoria->insertar()) {
            // Registrar log de creación de categoría
            try {
                $pdoLog = new PDO(RUTA, USUARIO, PASS);
                $pdoLog->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $adminId = $_SESSION['id'] ?? null;
                $adminNombre = $_SESSION['nombre'] ?? 'Admin';

                $stmtLog = $pdoLog->prepare("INSERT INTO logs_sistema (tipo, usuario_id, usuario_nombre, descripcion) VALUES ('creacion_categoria', :usuario_id, :usuario_nombre, :descripcion)");
                $stmtLog->execute([
                    ':usuario_id' => $adminId,
                    ':usuario_nombre' => $adminNombre,
                    ':descripcion' => 'Categoría creada: ' . $nombre
                ]);
            } catch (Exception $e) {
                // Silenciar errores de logging
            }

            echo json_encode(['ok' => true, 'id' => $categoria->getId()]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error al guardar la categoría']);
        }
        exit;
    }

    // Manejar DELETE para eliminar categoría
    if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && isset($_GET['eliminar'])) {
        $id = $_GET['eliminar'];
        $categoria = Categoria::buscarPorId($id);

        if (!$categoria) {
            http_response_code(404);
            echo json_encode(['error' => 'Categoría no encontrada']);
            exit;
        }

        if ($categoria->eliminar()) {
            // Registrar log de eliminación de categoría
            try {
                $pdoLog = new PDO(RUTA, USUARIO, PASS);
                $pdoLog->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $adminId = $_SESSION['id'] ?? null;
                $adminNombre = $_SESSION['nombre'] ?? 'Admin';
                $categoriaNombre = $categoria->getNombre() ?? 'ID: ' . $id;

                $stmtLog = $pdoLog->prepare("INSERT INTO logs_sistema (tipo, usuario_id, usuario_nombre, descripcion) VALUES ('eliminacion_categoria', :usuario_id, :usuario_nombre, :descripcion)");
                $stmtLog->execute([
                    ':usuario_id' => $adminId,
                    ':usuario_nombre' => $adminNombre,
                    ':descripcion' => 'Categoría eliminada: ' . $categoriaNombre
                ]);
            } catch (Exception $e) {
                // Silenciar errores de logging
            }

            echo json_encode(['ok' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error al eliminar la categoría']);
        }
        exit;
    }

    // Verificar si se pide una categoría específica
    if (isset($_GET['id'])) {
        $categoria = Categoria::buscarPorId($_GET['id']);
        if ($categoria) {
            $productos = $categoria->contarProductos();
            echo json_encode([
                'id' => $categoria->getId(),
                'nombre' => $categoria->getNombre(),
                'descripcion' => $categoria->getDescripcion(),
                'fecha_creacion' => $categoria->getFechaCreacion(),
                'num_productos' => $productos
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Categoría no encontrada']);
        }
        exit;
    }

    // Obtener todas las categorías
    $categorias = Categoria::obtenerTodas();

    $resultado = [];
    foreach ($categorias as $cat) {
        $productos = $cat->contarProductos();
        $resultado[] = [
            'id' => $cat->getId(),
            'nombre' => $cat->getNombre(),
            'descripcion' => $cat->getDescripcion(),
            'fecha_creacion' => $cat->getFechaCreacion(),
            'num_productos' => $productos
        ];
    }

    echo json_encode($resultado);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>