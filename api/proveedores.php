<?php
/**
 * API REST de Suministros (Proveedores).
 * Gestiona el directorio de contactos comerciales y los recargos de equivalencia
 * aplicables a los productos suministrados por cada entidad.
 * 
 * @author Alberto Méndez
 * @version 1.0 (04/03/2026)
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

// Verificar que el usuario sea admin
if (!isset($_SESSION['rolUsuario']) || $_SESSION['rolUsuario'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso denegado. Solo administradores.']);
    exit();
}

// Requerimos los archivos necesarios
require_once(__DIR__ . '/../config/confDB.php');
require_once(__DIR__ . '/../model/Proveedor.php');

/** 
 * MANEJADOR DE CONSULTAS (GET)
 * Permite listar proveedores o consultar productos específicos de un suministrador.
 */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    // Si se piden los productos de un proveedor (asociados)
    if (isset($_GET['productos'])) {
        $idProveedor = (int) $_GET['productos'];
        echo json_encode(Proveedor::obtenerProductos($idProveedor));
        exit();
    }

    // Si se piden los productos disponibles (no asociados) para un proveedor
    if (isset($_GET['productosDisponibles'])) {
        $idProveedor = (int) $_GET['productosDisponibles'];
        echo json_encode(Proveedor::obtenerProductosDisponibles($idProveedor));
        exit();
    }

    // Si se recibe un parámetro de búsqueda
    if (isset($_GET['buscar']) && trim($_GET['buscar']) !== '') {
        $proveedores = Proveedor::buscarPorNombre(trim($_GET['buscar']));
    } else {
        $proveedores = Proveedor::obtenerTodos();
    }

    // Convertir objetos a arrays para JSON
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

    // Validar formato de email si se proporciona
    if (!empty($email)) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'El formato del email no es válido.']);
            exit();
        }

        // Validar formato específico de email (debe tener @ y al menos un punto en el dominio)
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