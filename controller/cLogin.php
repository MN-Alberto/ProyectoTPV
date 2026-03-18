<?php
/**
 * Controlador de acceso (Login).
 * Se encarga de la validación de credenciales, gestión de sesiones de usuario
 * y registro de auditoría de intentos de acceso.
 * 
 * @author Alberto Méndez
 * @version 1.2 (02/03/2026)
 */

// Incluimos el modelo de Usuario
require_once(__DIR__ . '/../model/Usuario.php');

/**
 * Registra un evento de auditoría en la tabla de logs del sistema.
 * Útil para rastrear inicios de sesión, cierres de sesión e intentos fallidos.
 * 
 * @param PDO $pdo Instancia de conexión a la base de datos para realizar la inserción.
 * @param string $tipo Categoría del log (ej: 'login', 'logout', 'error').
 * @param string $descripcion Mensaje legible describiendo detalladamente el suceso.
 * @param mixed|null $detalles Metadatos adicionales (navegador, IP, etc.) en formato JSON.
 * @return void No retorna valor; los errores se silencian para no interrumpir el flujo del usuario.
 */
function registrarLog($pdo, $tipo, $descripcion, $detalles = null)
{
    try {
        $stmt = $pdo->prepare("INSERT INTO logs_sistema (tipo, usuario_id, usuario_nombre, descripcion, detalles) VALUES (:tipo, :usuario_id, :usuario_nombre, :descripcion, :detalles)");
        $stmt->execute([
            ':tipo' => $tipo,
            ':usuario_id' => $_SESSION['idUsuario'] ?? null,
            ':usuario_nombre' => $_SESSION['nombreUsuario'] ?? null,
            ':descripcion' => $descripcion,
            ':detalles' => $detalles ? json_encode($detalles) : null
        ]);
    }
    catch (Exception $e) {
    // Silenciamos los errores de auditoría para priorizar la disponibilidad de la aplicación.
    }
}

$error = ''; // Variable para almacenar mensajes de error.

// Si se ha enviado el formulario de login por POST.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['usuario'], $_POST['password'])) {
    // Obtenemos el nombre de usuario y la contraseña del formulario
    $nombre = trim($_POST['usuario']);
    $password = $_POST['password'];

    // Si el nombre de usuario o la contraseña están vacíos
    if (empty($nombre) || empty($password)) {
        // Añadimos un error a la variable para almacenar los errores
        $error = 'Por favor, rellena todos los campos.';
    }
    else {
        // Intento de autenticación mediante el modelo Usuario
        $usuario = Usuario::login($nombre, $password);

        // Si las credenciales son válidas y el usuario es activo
        if ($usuario) {
            // Persistimos la identidad del trabajador en la sesión global de PHP
            $_SESSION['idUsuario'] = $usuario->getId();
            $_SESSION['nombreUsuario'] = $usuario->getNombre();
            $_SESSION['rolUsuario'] = $usuario->getRol();
            $_SESSION['permisosUsuario'] = $usuario->getPermisos();

            // Determinamos el destino inicial basándonos en los privilegios del perfil
            if ($usuario->getRol() === 'admin') {
                $_SESSION['paginaEnCurso'] = 'admin';
                $tipoLog = 'acceso_admin';
            }
            else {
                $_SESSION['paginaEnCurso'] = 'cajero';
                $tipoLog = 'acceso_cajero';
            }

            // Registrar log de login
            try {
                require_once(__DIR__ . '/../config/confDB.php');
                $pdo = new PDO(RUTA, USUARIO, PASS);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                // Primero registrar login
                $stmt = $pdo->prepare("INSERT INTO logs_sistema (tipo, usuario_id, usuario_nombre, descripcion) VALUES ('login', :usuario_id, :usuario_nombre, :descripcion)");
                $stmt->execute([
                    ':usuario_id' => $usuario->getId(),
                    ':usuario_nombre' => $usuario->getNombre(),
                    ':descripcion' => 'Usuario inició sesión'
                ]);

                // Luego registrar acceso
                $stmt = $pdo->prepare("INSERT INTO logs_sistema (tipo, usuario_id, usuario_nombre, descripcion) VALUES (:tipo, :usuario_id, :usuario_nombre, :descripcion)");
                $stmt->execute([
                    ':tipo' => $tipoLog,
                    ':usuario_id' => $usuario->getId(),
                    ':usuario_nombre' => $usuario->getNombre(),
                    ':descripcion' => 'Acceso a ' . ($usuario->getRol() === 'admin' ? 'panel de administración' : 'panel de cajero')
                ]);
            }
            catch (Exception $e) {
            // Silenciar errores de logging
            }

            // Recargamos la página para mostrar así la vista del usuario
            header('Location: index.php');
            exit();
        }
        else {
            // Si el usuario no existe o las credenciales no son correctas indicamos un error
            $error = 'Usuario o contraseña incorrectos.';

            // Registrar intento de login fallido
            try {
                require_once(__DIR__ . '/../config/confDB.php');
                $pdo = new PDO(RUTA, USUARIO, PASS);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                $stmt = $pdo->prepare("INSERT INTO logs_sistema (tipo, usuario_id, usuario_nombre, descripcion) VALUES ('login_fallido', :usuario_id, :usuario_nombre, :descripcion)");
                $stmt->execute([
                    ':usuario_id' => null,
                    ':usuario_nombre' => $nombre,
                    ':descripcion' => 'Intento de inicio de sesión fallido'
                ]);
            }
            catch (Exception $e) {
            // Silenciar errores de logging
            }
        }
    }
}

// Si el usuario solicita cerrar sesión (por GET o POST).
if (
(isset($_GET['accion']) && $_GET['accion'] === 'cerrarSesion') ||
(isset($_POST['cerrarSesion']))
) {
    $nombreUsuario = $_SESSION['nombreUsuario'] ?? 'Desconocido';
    $idUsuario = $_SESSION['idUsuario'] ?? null;

    // Registrar logout antes de destruir sesión
    try {
        require_once(__DIR__ . '/config/confDB.php');
        $pdo = new PDO(RUTA, USUARIO, PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $pdo->prepare("INSERT INTO logs_sistema (tipo, usuario_id, usuario_nombre, descripcion) VALUES ('logout', :usuario_id, :usuario_nombre, :descripcion)");
        $stmt->execute([
            ':usuario_id' => $idUsuario,
            ':usuario_nombre' => $nombreUsuario,
            ':descripcion' => 'Usuario cerró sesión'
        ]);
    }
    catch (Exception $e) {
    // Silenciar errores de logging
    }

    // Destruimos la sesión
    session_destroy();
    // Recargamos la página
    header('Location: index.php');
    exit();
}

// Llamamos a la vista del login
require_once $view['Layout'];
?>