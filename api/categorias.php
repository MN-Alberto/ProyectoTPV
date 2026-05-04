<?php
/**
 * API de Clasificación de Productos (Categorías).
 * Proporciona endpoints para organizar el catálogo en diferentes grupos lógicos,
 * permitiendo la gestión administrativa de etiquetas y descripciones de categoría.
 * 
 * @author Alberto Méndez
 * @version 1.1 (05/03/2026)
 */

/**
 * CONFIGURACIÓN INICIAL
 * 
 * Se habilitan todos los errores para depuración durante el desarrollo.
 * En producción debería establecerse display_errors a 0.
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * CARGA DE DEPENDENCIAS
 * 
 * - Configuración de conexión a base de datos
 * - Modelo Categoria con toda la lógica de negocio
 */
require_once(__DIR__ . '/../config/confDB.php');
require_once(__DIR__ . '/../model/Categoria.php');

// Iniciar sesión para identificar al usuario autenticado
session_start();

header('Content-Type: application/json; charset=utf-8');

try {
    /**
     * ENDPOINT: Editar categoría existente
     * 
     * Actualiza nombre y descripción de una categoria ya creada.
     * El nombre es el unico campo obligatorio.
     * 
     * @param editar Identificador de la categoria a modificar
     */
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar'])) {
        $id = $_POST['editar'];
        $nombre = $_POST['nombre'] ?? '';
        $descripcion = $_POST['descripcion'] ?? '';

        // Validación: el nombre no puede estar vacio
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
            /**
             * REGISTRO DE AUDITORÍA
             * 
             * Todas las modificaciones de categorias quedan registradas
             * en el log del sistema. Si falla el registro del log,
             * NO se interrumpe la operación principal, simplemente se ignora.
             */
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
                // Los errores de log no deben romper la operación principal
            }

            echo json_encode(['ok' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error al actualizar la categoría']);
        }
        exit();
    }

    /**
     * ENDPOINT: Crear nueva categoría
     * 
     * Registra una nueva categoria en el sistema. Genera
     * automaticamente la fecha de creación y el identificador.
     * 
     * NOTA: Este endpoint se ejecuta SOLAMENTE si no existe
     * el parametro 'editar' del endpoint anterior.
     */
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $nombre = $_POST['nombre'] ?? '';
        $descripcion = $_POST['descripcion'] ?? '';

        // Validación: el nombre es obligatorio
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

    /**
     * ENDPOINT: Eliminar categoría
     * 
     * Elimina una categoria del sistema. CON PROTECCIÓN:
     * No se permite eliminar una categoria si tiene productos
     * asociados para evitar inconsistencia de datos.
     * 
     * @param eliminar Identificador de la categoria a borrar
     */
    if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && isset($_GET['eliminar'])) {
        $id = $_GET['eliminar'];
        $categoria = Categoria::buscarPorId($id);

        if (!$categoria) {
            http_response_code(404);
            echo json_encode(['error' => 'Categoría no encontrada']);
            exit;
        }

        /**
         * PROTECCIÓN DE INTEGRIDAD DE DATOS
         * 
         * Antes de eliminar se verifica que no existan productos
         * asignados a esta categoria. Si existen, se cancela la eliminación
         * y se informa al usuario cuantos productos hay afectados.
         */
        $numProductos = $categoria->contarProductos();
        if ($numProductos > 0) {
            http_response_code(400);
            echo json_encode([
                'error' => 'No se puede eliminar la categoría porque tiene productos asociados',
                'num_productos' => $numProductos,
                'categoria' => $categoria->getNombre()
            ]);
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

    /**
     * ENDPOINT: Obtener una categoria individual
     * 
     * Devuelve los datos completos de una categoria concreta
     * incluyendo el numero de productos que tiene asignados.
     * 
     * @param id Identificador de la categoria a consultar
     */
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

    /**
     * ENDPOINT: Listar TODAS las categorias
     * 
     * Comportamiento por defecto cuando no hay parametros.
     * Devuelve un array con todas las categorias existentes,
     * cada una con el numero de productos asignados.
     */
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