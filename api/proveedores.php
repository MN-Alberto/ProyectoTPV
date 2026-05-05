<?php
/**
 * API REST de Gestión de Proveedores y Suministros.
 * 
 * Gestiona el directorio de contactos comerciales y el sistema de vinculación
 * de productos con precios específicos y recargos de equivalencia por proveedor.
 * 
 * ✅ Características:
 *  - Gestión CRUD completa de fichas de proveedores
 *  - Vinculación de productos individuales con precios específicos
 *  - Sistema de recargos de equivalencia comerciales
 *  - Filtrado y búsqueda por nombre
 *  - Control de acceso estricto solo para usuarios administradores
 * 
 * @author Alberto Méndez
 * @version 1.1 (Comentarios añadidos)
 * @since 1.0 (04/03/2026)
 */

// Iniciamos sesión y establecemos formato de respuesta JSON
session_start();
header('Content-Type: application/json; charset=utf-8');

/**
 * 🔒 CONTROL DE ACCESO ESTRICTO
 * 
 * IMPORTANTE: Esta API SOLO es accesible para usuarios con rol administrador.
 * La comprobación se realiza ANTES de cargar cualquier dependencia para evitar
 * cualquier consumo de recursos innecesario en caso de acceso no autorizado.
 * 
 * Cualquier operación sobre proveedores requiere privilegios máximos.
 */
if (!isset($_SESSION['rolUsuario']) || $_SESSION['rolUsuario'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso denegado. Solo administradores.']);
    exit();
}

// Cargamos configuración y modelo de dominio
require_once(__DIR__ . '/../config/confDB.php');
require_once(__DIR__ . '/../model/Proveedor.php');

/** 
 * 📋 MANEJADOR DE CONSULTAS (GET)
 * 
 * Permite listar proveedores, buscar por nombre y consultar productos vinculados.
 * 
 * Endpoints disponibles:
 * - `GET /proveedores.php` → Listado completo de todos los proveedores
 * - `GET /proveedores.php?buscar=texto` → Búsqueda por nombre
 * - `GET /proveedores.php?productos=ID` → Productos YA ASOCIADOS al proveedor
 * - `GET /proveedores.php?productosDisponibles=ID` → Productos que AÚN NO están asociados
 */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    // Obtener productos YA VINCULADOS a este proveedor con su precio y recargo específico
    if (isset($_GET['productos'])) {
        $idProveedor = (int) $_GET['productos'];
        echo json_encode(Proveedor::obtenerProductos($idProveedor));
        exit();
    }

    // Obtener productos DISPONIBLES para asociar (todos los que aún no están vinculados)
    if (isset($_GET['productosDisponibles'])) {
        $idProveedor = (int) $_GET['productosDisponibles'];
        echo json_encode(Proveedor::obtenerProductosDisponibles($idProveedor));
        exit();
    }

    // Búsqueda por nombre o listado completo
    if (isset($_GET['buscar']) && trim($_GET['buscar']) !== '') {
        $proveedores = Proveedor::buscarPorNombre(trim($_GET['buscar']));
    } else {
        $proveedores = Proveedor::obtenerTodos();
    }

    // Normalización de datos para serialización JSON
    $resultado = [];
    foreach ($proveedores as $prov) {
        $resultado[] = [
            'id' => $prov->getId(),
            'nombre' => $prov->getNombre(),
            'contacto' => $prov->getContacto(),
            'email' => $prov->getEmail(),
            'direccion' => $prov->getDireccion(),
            'activo' => (int) $prov->getActivo()
        ];
    }

    echo json_encode($resultado);
    exit();
}

// ======================== DELETE ========================
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    // 1. Eliminar asociación entre proveedor y producto (recargo)
    if (isset($_GET['eliminarAsociacion'])) {
        $idAsociacion = (int) $_GET['eliminarAsociacion'];
        if (Proveedor::eliminarProductoProveedor($idAsociacion)) {
            echo json_encode(['ok' => true]);
        } else {
            echo json_encode(['ok' => false, 'error' => 'Error al eliminar la asociación.']);
        }
        exit();
    }

    // 2. Eliminar proveedor completo
    if (isset($_GET['eliminar'])) {
        $id = (int) $_GET['eliminar'];
        $proveedor = Proveedor::buscarPorId($id);

        if (!$proveedor) {
            echo json_encode(['ok' => false, 'error' => 'Proveedor no encontrado.']);
            exit();
        }

        if ($proveedor->eliminar()) {
            echo json_encode(['ok' => true]);
        } else {
            echo json_encode(['ok' => false, 'error' => 'Error al eliminar el proveedor.']);
        }
    }
    exit();
}

/** 
 * MANEJADOR DE PERSISTENCIA (POST)
 * Proprocesa tanto el alta/modificación de perfiles como la vinculación de artículos.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    // Manejar asociación y recargo de equivalencia
    if ($accion === 'agregarProducto') {
        $idProveedor = (int) ($_POST['idProveedor'] ?? 0);
        $idProducto = (int) ($_POST['idProducto'] ?? 0);
        $recargo = (float) ($_POST['recargoEquivalencia'] ?? 0);
        $precioProv = (float) ($_POST['precioProveedor'] ?? 0);

        if ($idProveedor > 0 && $idProducto > 0) {
            if (Proveedor::agregarProducto($idProveedor, $idProducto, $recargo, $precioProv)) {
                echo json_encode(['ok' => true]);
            } else {
                echo json_encode(['ok' => false, 'error' => 'No se pudo asociar el producto.']);
            }
        } else {
            echo json_encode(['ok' => false, 'error' => 'Faltan datos para la asociación.']);
        }
        exit();
    }

    if ($accion === 'actualizarRecargo') {
        $idAsociacion = (int) ($_POST['idAsociacion'] ?? 0);
        $recargo = (float) ($_POST['recargoEquivalencia'] ?? 0);
        $precioProv = (float) ($_POST['precioProveedor'] ?? 0);

        if ($idAsociacion > 0) {
            if (Proveedor::actualizarAsociacion($idAsociacion, $recargo, $precioProv)) {
                echo json_encode(['ok' => true]);
            } else {
                echo json_encode(['ok' => false, 'error' => 'No se pudo actualizar la asociación.']);
            }
        } else {
            echo json_encode(['ok' => false, 'error' => 'Falta el ID de la asociación.']);
        }
        exit();
    }

    // Creación y actualización de proveedor
    $nombre = trim($_POST['nombre'] ?? '');
    $contacto = trim($_POST['contacto'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $activo = isset($_POST['activo']) ? (int) $_POST['activo'] : 1;
    $id = $_POST['id'] ?? '';

    // Validar campo obligatorio
    if (empty($nombre)) {
        echo json_encode(['ok' => false, 'error' => 'El nombre es obligatorio.']);
        exit();
    }

    /**
     * ✉️ DOBLE VALIDACIÓN DE FORMATO DE EMAIL
     * 
     * Se aplican dos niveles de validación por seguridad:
     * 1. Validación nativa de PHP `filter_var` para comprobaciones básicas
     * 2. Validación mediante expresión regular estricta para garantizar el formato correcto
     * 
     * Esta medida evita correos electrónicos con formatos técnicamente válidos
     * pero que no funcionan correctamente en sistemas reales de envío.
     */
    if (!empty($email)) {
        // Validación nivel 1: filtro nativo PHP
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'El formato del email no es válido.']);
            exit();
        }

        // Validación nivel 2: expresión regular estricta
        if (!preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $email)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'El email debe tener formato: nombre@dominio.com']);
            exit();
        }
    }

    if ($id !== '') {
        // Actualizar proveedor existente
        $proveedor = Proveedor::buscarPorId((int) $id);
        if (!$proveedor) {
            echo json_encode(['ok' => false, 'error' => 'Proveedor no encontrado.']);
            exit();
        }
    } else {
        // Crear nuevo proveedor
        $proveedor = new Proveedor();
    }

    $proveedor->setNombre($nombre);
    $proveedor->setContacto($contacto);
    $proveedor->setEmail($email);
    $proveedor->setDireccion($direccion);
    $proveedor->setActivo($activo);

    if ($id !== '') {
        $resultado = $proveedor->actualizar();
    } else {
        $resultado = $proveedor->insertar();
    }

    if ($resultado) {
        echo json_encode(['ok' => true, 'id' => $proveedor->getId()]);
    } else {
        echo json_encode(['ok' => false, 'error' => 'Error al guardar el proveedor.']);
    }
    exit();
}

// Método no permitido
http_response_code(405);
echo json_encode(['error' => 'Método no permitido.']);
?>