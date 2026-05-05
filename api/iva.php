<?php
/**
 * API de Gestión de Tipos de IVA.
 * Permite la configuración dinámica de los tipos impositivos aplicables a los productos,
 * facilitando la actualización de porcentajes y nombres de impuestos.
 * 
 * Esta API sigue el patrón RESTful utilizando los métodos HTTP estándar:
 * - GET:    Obtener todos los tipos de IVA registrados
 * - POST:   Crear un nuevo tipo o actualizar uno existente (UPSERT)
 * - DELETE: Eliminar un tipo de IVA existente
 * 
 * Todas las respuestas se devuelven en formato JSON con códigos de estado HTTP apropiados.
 * 
 * @author Alberto Méndez
 * @version 1.1 (Comentarios añadidos)
 * @since 1.0 (11/03/2026)
 */

// Iniciamos sesión para mantener el contexto del usuario autenticado
session_start();

// Cargamos configuración de base de datos y modelo de dominio de IVA
require_once(__DIR__ . '/../config/confDB.php');
require_once(__DIR__ . '/../model/Iva.php');

// Establecemos cabecera para que el cliente interprete correctamente la respuesta JSON
header('Content-Type: application/json; charset=utf-8');

try {
    /** 
     * MANEJADOR DE ELIMINACIÓN (DELETE)
     * Borra un tipo de IVA si no tiene dependencias activas con productos.
     * 
     * NOTA: Existe restricción de integridad referencial en base de datos.
     * No se permite eliminar un IVA que tenga productos asignados para evitar
     * inconsistencias históricas en ventas ya registradas.
     */
    if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        // Obtenemos y sanitizamos el identificador del IVA a eliminar
        $id = isset($_GET['eliminar']) ? (int) $_GET['eliminar'] : 0;

        // Validamos que el ID sea válido
        if ($id > 0) {
            // Buscamos la entidad en base de datos
            $iva = Iva::buscarPorId($id);

            // Intentamos eliminar (el método ya comprueba las dependencias)
            if ($iva && $iva->eliminar()) {
                // Eliminación correcta: código 200 OK por defecto
                echo json_encode(['ok' => true]);
            } else {
                // Error de negocio: código 400 Bad Request
                http_response_code(400);
                echo json_encode(['ok' => false, 'error' => 'No se pudo eliminar el tipo de IVA. Puede que esté asignado a productos.']);
            }
        } else {
            // Datos de entrada inválidos
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'ID de IVA inválido.']);
        }

        // Finalizamos ejecución para no procesar otros métodos
        exit();
    }

    /** 
     * MANEJADOR DE PERSISTENCIA (POST)
     * Procesa tanto el alta de nuevos tipos como la edición de los existentes.
     * 
     * Patrón UPSERT: Se utiliza el mismo endpoint para ambas operaciones.
     * Si se recibe un ID > 0 se actualiza, sino se crea un nuevo registro.
     */
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Recogemos y convertimos los parámetros recibidos a sus tipos correctos
        $id = (int) ($_POST['id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $porcentaje = (float) ($_POST['porcentaje'] ?? 0);

        // ✅ VALIDACIÓN 1: Nombre es campo obligatorio
        if (empty($nombre)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'El nombre es obligatorio.']);
            exit();
        }

        // ✅ VALIDACIÓN 2: Porcentaje dentro de rango lógico
        if ($porcentaje < 0 || $porcentaje > 100) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'El porcentaje debe estar entre 0 y 100.']);
            exit();
        }

        // ► OPERACIÓN DE ACTUALIZACIÓN
        if ($id > 0) {
            // Buscamos el registro existente
            $iva = Iva::buscarPorId($id);

            if (!$iva) {
                // Recurso no encontrado: código 404 Not Found
                http_response_code(404);
                echo json_encode(['ok' => false, 'error' => 'Tipo de IVA no encontrado.']);
                exit();
            }

            // Actualizamos propiedades
            $iva->setNombre($nombre);
            $iva->setPorcentaje($porcentaje);

            // Persistimos cambios
            if ($iva->actualizar()) {
                echo json_encode(['ok' => true]);
            } else {
                http_response_code(400);
                echo json_encode(['ok' => false, 'error' => 'No se pudo actualizar el tipo de IVA.']);
            }
        }
        // ► OPERACIÓN DE CREACIÓN
        else {
            // Instanciamos nuevo objeto
            $iva = new Iva();
            $iva->setNombre($nombre);
            $iva->setPorcentaje($porcentaje);

            // Guardamos en base de datos
            if ($iva->insertar()) {
                // Devolvemos el ID generado automáticamente al cliente
                echo json_encode(['ok' => true, 'id' => $iva->getId()]);
            } else {
                http_response_code(400);
                echo json_encode(['ok' => false, 'error' => 'No se pudo crear el tipo de IVA.']);
            }
        }

        exit();
    }

    /**
     * MANEJADOR DE LECTURA (GET)
     * Devuelve el listado completo de todos los tipos de IVA registrados.
     * 
     * Se normalizan los tipos de datos para garantizar consistencia en la respuesta JSON,
     * evitando que números aparezcan como strings y viceversa.
     */
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Obtenemos todas las entidades desde el modelo
        $tipos = Iva::obtenerTodos();

        // Transformamos objetos de dominio a array plano para serialización JSON
        $resultado = [];
        foreach ($tipos as $tipo) {
            $resultado[] = [
                'id' => (int) $tipo->getId(),
                'nombre' => (string) $tipo->getNombre(),
                'porcentaje' => (float) $tipo->getPorcentaje()
            ];
        }

        // Devolvemos el listado directamente como respuesta
        echo json_encode($resultado);
    }
} catch (Exception $e) {
    /**
     * MANEJO GLOBAL DE EXCEPCIONES
     * Cualquier error no controlado durante la ejecución se captura aquí.
     * Se devuelve código 500 Internal Server Error indicando fallo en servidor.
     */
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>