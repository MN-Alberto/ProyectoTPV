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

// Cargar PHPMailer para recuperación de contraseña (deshabilitado temporalmente)
// require_once __DIR__ . '/../core/Exception.php';
// require_once __DIR__ . '/../core/PHPMailer.php';
// require_once __DIR__ . '/../core/SMTP.php';

// use PHPMailer\PHPMailer\PHPMailer;
// use PHPMailer\PHPMailer\Exception;

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
        // Verificar si el usuario tiene ventas asociadas
        $stmtCheckVentas = $pdo->prepare("SELECT COUNT(*) as total FROM ventas WHERE idUsuario = :idUsuario");
        $stmtCheckVentas->execute([':idUsuario' => $id]);
        $resultadoVentas = $stmtCheckVentas->fetch(PDO::FETCH_ASSOC);

        if ($resultadoVentas && $resultadoVentas['total'] > 0) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'No se puede eliminar el usuario porque tiene ventas asociadas.']);
            exit();
        }

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
    // ────────────────────────────────────────────────────────────────────────────
    // RECUPERACIÓN DE CONTRASEÑA (primero, antes de cualquier otra cosa)
    // ────────────────────────────────────────────────────────────────────────────

    // Endpoint: Enviar código de recuperación
    if (isset($_POST['action']) && $_POST['action'] === 'send_recovery_code') {
        // Disable error output to prevent HTML in JSON response
        error_reporting(0);
        ini_set('display_errors', 0);

        header('Content-Type: application/json; charset=utf-8');

        // Debug: log received data
        error_log('Received action: ' . $_POST['action']);
        error_log('Received nombre: ' . ($_POST['nombre'] ?? 'none'));

        // SIMPLE TEST - return hardcoded response
        echo json_encode(['ok' => true, 'message' => 'API funciona!']);
        exit();

        $nombre = trim($_POST['nombre'] ?? '');

        if (empty($nombre)) {
            echo json_encode(['ok' => false, 'error' => 'El nombre de usuario es obligatorio.']);
            exit();
        }

        // Buscar usuario por nombre
        $usuario = Usuario::buscarPorNombre($nombre);

        if (!$usuario) {
            // Por seguridad, no revelar si el usuario existe o no
            echo json_encode(['ok' => true, 'message' => 'Si el usuario existe, se enviará un código a su correo.']);
            exit();
        }

        $email = $usuario->getEmail();

        if (empty($email)) {
            echo json_encode(['ok' => false, 'error' => 'El usuario no tiene un correo asociado.']);
            exit();
        }

        // Generar código de 6 dígitos
        $codigo = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Guardar código en sesión (temporalmente)
        $_SESSION['recovery_code'] = $codigo;
        $_SESSION['recovery_user'] = $nombre;
        $_SESSION['recovery_time'] = time();

        // MOSTRAR EL CÓDIGO EN LA RESPUESTA PARA PRUEBAS
        // (Quitar esto en producción)
        echo json_encode(['ok' => true, 'message' => 'Código enviado correctamente (debug: ' . $codigo . ')']);
        exit();

        // Enviar correo con el código (deshabilitado temporalmente para pruebas)
        // $mail = new PHPMailer(true);

        try {
            // Configuración del servidor SMTP
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'albertomennun04@gmail.com';
            $mail->Password = 'jdpq cfwd whpm ekmc';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            // Remitente
            $mail->setFrom('albertomennun04@gmail.com', 'TPV Bazar');

            // Destinatario
            $mail->addAddress($email, $usuario->getNombre());

            // Contenido
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = 'Código de recuperación de contraseña - TPV Bazar';
            $mail->Body = '
            <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
                <h2 style="color: #333;">Recuperación de Contraseña</h2>
                <p>Has solicitado recuperar tu contraseña en <strong>TPV Bazar</strong>.</p>
                <div style="background: #f5f5f5; padding: 20px; text-align: center; margin: 20px 0; border-radius: 8px;">
                    <p style="margin: 0; font-size: 14px; color: #666;">Tu código de verificación es:</p>
                    <p style="margin: 10px 0 0 0; font-size: 32px; font-weight: bold; color: #333; letter-spacing: 8px;">' . $codigo . '</p>
                </div>
                <p style="color: #666; font-size: 12px;">Este código expira en 30 minutos.</p>
                <p style="color: #666; font-size: 12px;">Si no has solicitado este código, puedes ignorar este mensaje.</p>
            </div>
            ';
            $mail->AltBody = 'Tu código de verificación es: ' . $codigo . '. Este código expira en 30 minutos.';

            $mail->send();

            echo json_encode(['ok' => true, 'message' => 'Código enviado correctamente.']);
        }
        catch (Exception $e) {
            error_log('Error al enviar correo: ' . $mail->ErrorInfo);
            echo json_encode(['ok' => false, 'error' => 'Error al enviar el correo. Inténtalo más tarde.']);
        }
        exit();
    }

    // Endpoint: Verificar código
    if (isset($_POST['action']) && $_POST['action'] === 'verify_recovery_code') {
        header('Content-Type: application/json; charset=utf-8');

        $codigoIngresado = trim($_POST['codigo'] ?? '');

        if (empty($codigoIngresado)) {
            echo json_encode(['ok' => false, 'error' => 'El código es obligatorio.']);
            exit();
        }

        // Verificar que existe una sesión de recuperación
        if (!isset($_SESSION['recovery_code']) || !isset($_SESSION['recovery_time'])) {
            echo json_encode(['ok' => false, 'error' => 'No hay una solicitud de recuperación activa.']);
            exit();
        }

        // Verificar que no ha expirado (30 minutos)
        $tiempoTranscurrido = time() - $_SESSION['recovery_time'];
        if ($tiempoTranscurrido > 1800) {
            unset($_SESSION['recovery_code'], $_SESSION['recovery_user'], $_SESSION['recovery_time']);
            echo json_encode(['ok' => false, 'error' => 'El código ha expirado. Solicita uno nuevo.']);
            exit();
        }

        // Verificar código
        if ($codigoIngresado !== $_SESSION['recovery_code']) {
            echo json_encode(['ok' => false, 'error' => 'Código incorrecto.']);
            exit();
        }

        // Código válido, marcar como verificado
        $_SESSION['recovery_verified'] = true;

        echo json_encode(['ok' => true, 'message' => 'Código verificado correctamente.']);
        exit();
    }

    // Endpoint: Cambiar contraseña
    if (isset($_POST['action']) && $_POST['action'] === 'change_password') {
        header('Content-Type: application/json; charset=utf-8');

        $nuevaPassword = $_POST['password'] ?? '';
        $confirmarPassword = $_POST['confirm_password'] ?? '';

        // Validaciones
        if (empty($nuevaPassword) || empty($confirmarPassword)) {
            echo json_encode(['ok' => false, 'error' => 'Ambas contraseñas son obligatorias.']);
            exit();
        }

        if ($nuevaPassword !== $confirmarPassword) {
            echo json_encode(['ok' => false, 'error' => 'Las contraseñas no coinciden.']);
            exit();
        }

        if (strlen($nuevaPassword) < 6) {
            echo json_encode(['ok' => false, 'error' => 'La contraseña debe tener al menos 6 caracteres.']);
            exit();
        }

        // Verificar que la sesión está validada
        if (!isset($_SESSION['recovery_verified']) || !$_SESSION['recovery_verified'] || !isset($_SESSION['recovery_user'])) {
            echo json_encode(['ok' => false, 'error' => 'Debes verificar el código primero.']);
            exit();
        }

        $nombreUsuario = $_SESSION['recovery_user'];

        // Buscar usuario
        $usuario = Usuario::buscarPorNombre($nombreUsuario);

        if (!$usuario) {
            echo json_encode(['ok' => false, 'error' => 'Usuario no encontrado.']);
            exit();
        }

        // Actualizar contraseña
        $nuevaPasswordHash = password_hash($nuevaPassword, PASSWORD_DEFAULT);

        try {
            $stmt = $pdo->prepare("UPDATE usuarios SET password = :password WHERE nombre = :nombre");
            $stmt->execute([':password' => $nuevaPasswordHash, ':nombre' => $nombreUsuario]);

            // Limpiar sesión de recuperación
            unset($_SESSION['recovery_code'], $_SESSION['recovery_user'], $_SESSION['recovery_time'], $_SESSION['recovery_verified']);

            // Registrar en log
            registrarLogUsuario($pdo, 'cambio_password', $usuario->getId(), $nombreUsuario, 'Cambio de contraseña mediante recuperación');

            echo json_encode(['ok' => true, 'message' => 'Contraseña actualizada correctamente.']);
        }
        catch (Exception $e) {
            echo json_encode(['ok' => false, 'error' => 'Error al actualizar la contraseña.']);
        }
        exit();
    }

    // ────────────────────────────────────────────────────────────────────────────
    // FIN DE RECUPERACIÓN DE CONTRASEÑA
    // ────────────────────────────────────────────────────────────────────────────

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

        try {
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
        catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
    }
    else {
        // Es un nuevo usuario
        if (empty($password)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'La contraseña es obligatoria para nuevos usuarios.']);
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