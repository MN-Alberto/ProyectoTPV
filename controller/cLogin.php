<?php
/**
 * Controlador de login. Procesa el formulario y redirige según el rol.
 * 
 * @author Alberto Méndez
 * @version 1.2 (02/03/2026)
 */

// Incluimos el modelo de Usuario
require_once(__DIR__ . '/../model/Usuario.php');

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
    } else {
        // Intentamos iniciar sesión
        $usuario = Usuario::login($nombre, $password);

        // Si el usuario existe
        if ($usuario) {
            // Guardamos los datos del usuario en la sesión.
            $_SESSION['idUsuario'] = $usuario->getId();
            $_SESSION['nombreUsuario'] = $usuario->getNombre();
            $_SESSION['rolUsuario'] = $usuario->getRol();

            // Redirigimos según el rol, si es admin a la vista de admin y si es cajero a la de cajero
            if ($usuario->getRol() === 'admin') {
                $_SESSION['paginaEnCurso'] = 'admin';
            } else {
                $_SESSION['paginaEnCurso'] = 'cajero';
            }

            // Recargamos la página para mostrar así la vista del usuario
            header('Location: index.php');
            exit();
        } else {
            // Si el usuario no existe o las credenciales no son correctas indicamos un error
            $error = 'Usuario o contraseña incorrectos.';
        }
    }
}

// Si el usuario solicita cerrar sesión.
if (isset($_GET['accion']) && $_GET['accion'] === 'cerrarSesion') {
    // Destruimos la sesión
    session_destroy();
    // Recargamos la página
    header('Location: index.php');
    exit();
}

// Llamamos a la vista del login
require_once $view['Layout'];
?>