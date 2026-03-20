<?php
/**
 * API de Gestión de Usuarios y Permisos.
 * Centraliza las operaciones CRUD para el personal del establecimiento,
 * incluyendo la asignación de roles, auditoría de cambios y gestión de credenciales.
 * 
 * @author Alberto Méndez
 * @version 1.0 (03/03/2026)
 */

// Iniciamos la sesión para acceder a las variables de sesión
session_start();

// Requerimos los archivos necesarios
require_once(__DIR__ . '/../config/confDB.php');
require_once(__DIR__ . '/../model/Usuario.php');

// Obtener conexión a la base de datos
$pdo = ConexionDB::getInstancia()->getConexion();

// Indicamos al navegador que es un tipo JSON
header('Content-Type: application/json; charset=utf-8');

/**
 * Registra eventos de auditoría relacionados con la gestión de usuarios.
 * Almacena quién realizó la acción, sobre qué usuario y una descripción legible.
 * 
 * @param PDO $pdo Instancia de conexión activa a la base de datos.
 * @param string $tipo Categoría técnica del evento (ej: 'alta_usuario', 'cambio_password').
 * @param int|null $usuario_id ID del usuario sobre el que se actúa (puede ser nulo en logins fallidos).
 * @param string $usuario_nombre Nombre o login del usuario afectado.
 * @param string $descripcion Explicación en lenguaje natural de lo sucedido.
 * @param mixed|null $detalles Metadatos adicionales (arrays o JSON) para depuración o historial.
 * @return void
 */
function registrarLogUsuario($pdo, $tipo, $usuario_id, $usuario_nombre, $descripcion, $detalles = null)
{
    try {
        $stmt = $pdo->prepare("INSERT INTO logs_sistema (tipo, usuario_id, usuario_nombre, descripcion, detalles) VALUES (:tipo, :usuario_id, :usuario_nombre, :descripcion, :detalles)");
        $stmt->execute([
            ':tipo' => $tipo,
            ':usuario_id' => $usuario_id,
            ':usuario_nombre' => $usuario_nombre,
            ':descripcion' => $descripcion,
            ':detalles' => $detalles ? (is_array($detalles) ? json_encode($detalles, JSON_UNESCAPED_UNICODE) : $detalles) : null
        ]);
    }
    catch (Exception $e) {
    // Silenciar errores de logging
    }
}

// NOTA: La seguridad ya está garantizada por el controlador cAdmin.php
// que verifica que el usuario es administrador antes de mostrar la vista.
// No necesitamos verificar la sesión aquí.

// ── ELIMINAR USUARIO (DELETE) ────────────────────────────────────────────
/**
 * Procesa la eliminación física de un usuario del sistema.
 * Registra quién autorizó la baja para fines de auditoría.
 * @param int $_GET['eliminar'] Identificador del usuario a borrar.
 */
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    // Obtenemos el id del usuario a eliminar
    $id = isset($_GET['eliminar']) ? (int)$_GET['eliminar'] : 0;

    // Si el id es válido
    if ($id > 0) {
        $usuario = Usuario::buscarPorId($id);
        if ($usuario && $usuario->eliminar()) {
            // Registrar log de eliminación de usuario
            $adminId = $_SESSION['id'] ?? null;
            $adminNombre = $_SESSION['nombre'] ?? 'Admin';
            $usuarioEliminado = $usuario->getNombre() . ' (' . $usuario->getEmail() . ')';
            registrarLogUsuario($pdo, 'eliminacion_usuario', $adminId, $adminNombre, 'Usuario eliminado: ' . $usuarioEliminado . ' (ID: ' . $id . ')');

            http_response_code(200);
            echo json_encode(['ok' => true]);
        }
        else {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'No se pudo eliminar el usuario.']);
        }
    }
    else {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'ID de usuario inválido.']);
    }
    exit();
}

// ── POST (Crear o Actualizar) ─────────────────────────────────────────────
/**
 * Lógica dual para la creación de nuevos empleados o edición de perfiles existentes.
 * Realiza validaciones estrictas de formato de email y persistencia segura de contraseñas.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $rol = $_POST['rol'] ?? 'empleado';
    $activo = isset($_POST['activo']) ? (int)$_POST['activo'] : 1;
    $permisos = $_POST['permisos'] ?? null;

    // Validaciones básicas
    if (empty($nombre) || empty($email)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'El nombre y email son obligatorios.']);
        exit();
    }

    // Validar formato de email (formato: texto@texto.dominio)
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

    // Si es una actualización (id > 0)
    if ($id > 0) {
        $usuario = Usuario::buscarPorId($id);
        if (!$usuario) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'Usuario no encontrado.']);
            exit();
        }

        // Guardar valores anteriores para comparar cambios
        $valoresAnteriores = array(
            'nombre' => $usuario->getNombre(),
            'email' => $usuario->getEmail(),
            'rol' => $usuario->getRol(),
            'activo' => (bool)$usuario->getActivo(),
            'permisos' => $usuario->getPermisos()
        );

        $usuario->setNombre($nombre);
        $usuario->setEmail($email);
        $usuario->setRol($rol);
        $usuario->setActivo($activo);
        $usuario->setPermisos($permisos);

        // Solo actualizar contraseña si se proporciona una nueva
        $passwordCambiada = false;
        if (!empty($password)) {
            $usuario->setPassword(password_hash($password, PASSWORD_DEFAULT));
            $passwordCambiada = true;
        }

        if ($usuario->actualizar()) {
            // Registrar log de modificación de usuario con detalles de cambios
            $adminId = $_SESSION['id'] ?? null;
            $adminNombre = $_SESSION['nombre'] ?? 'Admin';

            // Comparar cambios
            $cambios = array();
            if ($valoresAnteriores['nombre'] !== $nombre) {
                $cambios['nombre'] = array('antes' => $valoresAnteriores['nombre'], 'después' => $nombre);
            }
            if ($valoresAnteriores['email'] !== $email) {
                $cambios['email'] = array('antes' => $valoresAnteriores['email'], 'después' => $email);
            }
            if ($valoresAnteriores['rol'] !== $rol) {
                $cambios['rol'] = array('antes' => $valoresAnteriores['rol'], 'después' => $rol);
            }
            if ($valoresAnteriores['activo'] !== (bool)$activo) {
                $cambios['activo'] = array('antes' => $valoresAnteriores['activo'] ? 'Sí' : 'No', 'después' => $activo ? 'Sí' : 'No');
            }
            if ($passwordCambiada) {
                $cambios['password'] = array('antes' => '(oculta)', 'después' => '(nueva)');
            }
            if ($valoresAnteriores['permisos'] !== $permisos) {
                $cambios['permisos'] = array('antes' => $valoresAnteriores['permisos'], 'después' => $permisos);
            }

            $detalles = count($cambios) > 0 ? $cambios : null;
            registrarLogUsuario($pdo, 'modificacion_usuario', $adminId, $adminNombre, 'Usuario modificado: ' . $nombre . ' (ID: ' . $id . ')', $detalles);

            http_response_code(200);
            echo json_encode(['ok' => true]);
        }
        else {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'No se pudo actualizar el usuario.']);
        }
    }
    else {
        // Es un nuevo usuario
        if (empty($password)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'La contraseña es obligatoria para nuevos usuarios.']);
            exit();
        }

        // Verificar que no existe otro usuario con el mismo email
        if (Usuario::buscarPorEmail($email)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Ya existe un usuario con ese email.']);
            exit();
        }

        $nuevoUsuario = new Usuario();
        $nuevoUsuario->setNombre($nombre);
        $nuevoUsuario->setEmail($email);
        $nuevoUsuario->setPassword(password_hash($password, PASSWORD_DEFAULT));
        $nuevoUsuario->setRol($rol);
        $nuevoUsuario->setActivo($activo);
        $nuevoUsuario->setPermisos($permisos);
        $nuevoUsuario->setFechaAlta(date('Y-m-d H:i:s'));

        if ($nuevoUsuario->insertar()) {
            // Registrar log de creación de usuario con detalles
            $adminId = $_SESSION['id'] ?? null;
            $adminNombre = $_SESSION['nombre'] ?? 'Admin';
            $detallesUsuario = array(
                'nombre' => $nombre,
                'email' => $email,
                'rol' => $rol,
                'activo' => (bool)$activo,
                'permisos' => $permisos
            );
            registrarLogUsuario($pdo, 'creacion_usuario', $adminId, $adminNombre, 'Usuario creado: ' . $nombre . ' (' . $email . ')', $detallesUsuario);

            http_response_code(201);
            echo json_encode(['ok' => true]);
        }
        else {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'No se pudo crear el usuario.']);
        }
    }
    exit();
}

// ── GET: Obtener usuarios ─────────────────────────────────────────────────

// Si se busca un usuario específico
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $usuario = Usuario::buscarPorId((int)$_GET['id']);
    if ($usuario) {
        echo json_encode([
            'id' => $usuario->getId(),
            'nombre' => $usuario->getNombre(),
            'email' => $usuario->getEmail(),
            'rol' => $usuario->getRol(),
            'fechaAlta' => $usuario->getFechaAlta(),
            'activo' => (int)$usuario->getActivo(),
            'total_descansos' => $usuario->getTotalDescansos(),
            'total_turnos' => $usuario->getTotalTurnos(),
            'permisos' => $usuario->getPermisos()
        ]);
    }
    else {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'Usuario no encontrado.']);
    }
    exit();
}

// Si se busca por nombre - soporte de paginación
if (isset($_GET['buscar']) && !empty(trim($_GET['buscar']))) {
    $busqueda = trim($_GET['buscar']);
    $pagina = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
    $porPagina = isset($_GET['porPagina']) ? max(1, (int)$_GET['porPagina']) : 6;

    $resultado = Usuario::buscarPorNombreParcialPaginado($busqueda, $pagina, $porPagina);
    $usuarios = $resultado['usuarios'];
}
else {
    // Obtener todos los usuarios con paginación
    $pagina = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
    $porPagina = isset($_GET['porPagina']) ? max(1, (int)$_GET['porPagina']) : 6;

    $resultado = Usuario::obtenerTodosPaginados($pagina, $porPagina);
    $usuarios = $resultado['usuarios'];
}

// Formatear respuesta
$formateado = [];
foreach ($usuarios as $usr) {
    $formateado[] = [
        'id' => $usr->getId(),
        'nombre' => $usr->getNombre(),
        'email' => $usr->getEmail(),
        'rol' => $usr->getRol(),
        'fechaAlta' => $usr->getFechaAlta(),
        'activo' => (int)$usr->getActivo(),
        'total_descansos' => $usr->getTotalDescansos(),
        'total_turnos' => $usr->getTotalTurnos(),
        'permisos' => $usr->getPermisos()
    ];
}

// Devolver con información de paginación
if (isset($_GET['buscar']) && !empty(trim($_GET['buscar']))) {
    echo json_encode([
        'usuarios' => $formateado,
        'total' => $resultado['total'],
        'pagina' => $resultado['pagina'],
        'porPagina' => $resultado['porPagina'],
        'totalPaginas' => $resultado['totalPaginas']
    ]);
}
else {
    echo json_encode([
        'usuarios' => $formateado,
        'total' => $resultado['total'],
        'pagina' => $resultado['pagina'],
        'porPagina' => $resultado['porPagina'],
        'totalPaginas' => $resultado['totalPaginas']
    ]);
}
?>