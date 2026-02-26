<?php
/*
 * Autor: Alberto Méndez 
 * Fecha de actualización: 24/02/2026
 * 
 * Controlador de login. Procesa el formulario y redirige según el rol.
 */

require_once(__DIR__ . '/../model/Usuario.php');

$error = ''; //Variable para almacenar mensajes de error.

//Si se ha enviado el formulario de login por POST.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['usuario'], $_POST['password'])) {
    $nombre = trim($_POST['usuario']);
    $password = $_POST['password'];

    if (empty($nombre) || empty($password)) {
        $error = 'Por favor, rellena todos los campos.';
    } else {
        $usuario = Usuario::login($nombre, $password);

        if ($usuario) {
            //Guardamos los datos del usuario en la sesión.
            $_SESSION['idUsuario'] = $usuario->getId();
            $_SESSION['nombreUsuario'] = $usuario->getNombre();
            $_SESSION['rolUsuario'] = $usuario->getRol();

            //Redirigimos según el rol.
            if ($usuario->getRol() === 'admin') {
                $_SESSION['paginaEnCurso'] = 'admin';
            } else {
                $_SESSION['paginaEnCurso'] = 'cajero';
            }

            //Redirigimos para evitar reenvío del formulario.
            header('Location: index.php');
            exit();
        } else {
            $error = 'Usuario o contraseña incorrectos.';
        }
    }
}

//Si el usuario solicita cerrar sesión.
if (isset($_GET['accion']) && $_GET['accion'] === 'cerrarSesion') {
    session_destroy();
    header('Location: index.php');
    exit();
}

require_once $view['Layout'];
?>