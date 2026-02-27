<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TPV Bazar Electrónico</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="webroot/css/login.css" rel="stylesheet" type="text/css">
    <?php if (isset($_SESSION['paginaEnCurso']) && ($_SESSION['paginaEnCurso'] === 'cajero' || $_SESSION['paginaEnCurso'] === 'admin')): ?>
        <link href="webroot/css/cajero.css" rel="stylesheet" type="text/css">
    <?php endif; ?>
    <?php if (isset($_SESSION['paginaEnCurso']) && $_SESSION['paginaEnCurso'] === 'admin'): ?>
        <link href="webroot/css/admin.css" rel="stylesheet" type="text/css">
    <?php endif; ?>
    <link rel="icon" href="webroot/img/logoCPU.PNG" type="image/png">
</head>

<body>
    <header>
        <div style="display: flex; align-items: center; gap: 15px;">
            <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                style="color: #60a5fa;">
                <rect x="4" y="4" width="16" height="16" rx="2"></rect>
                <rect x="9" y="9" width="6" height="6"></rect>
                <path d="M15 2v2"></path>
                <path d="M15 20v2"></path>
                <path d="M2 15h2"></path>
                <path d="M2 9h2"></path>
                <path d="M20 15h2"></path>
                <path d="M20 9h2"></path>
                <path d="M9 2v2"></path>
                <path d="M9 20v2"></path>
            </svg>
            <h1>TPV Bazar Electrónico</h1>
        </div>
        <?php if (isset($_SESSION['idUsuario'])): ?>
            <div class="header-usuario">
                <span>Hola, <strong><?php echo htmlspecialchars($_SESSION['nombreUsuario']); ?></strong></span>
                <form action="<?php echo $_SERVER["PHP_SELF"]; ?>" method="post">
                    <input type="submit" name="cerrarSesion" class="btn-cerrar-sesion" value="Cerrar Sesión">
                </form>
            </div>
        <?php endif; ?>
    </header>
    <?php

    /*
     * Autor: Alberto Méndez 
     * Fecha de actualización: 24/02/2026
     */

    require_once $view[$_SESSION["paginaEnCurso"]]; //Añadimos la pagina en curso para cargarla.
    ?>
    <footer>
        <a href="https://github.com/MN-Alberto/ProyectoTPV" target="blank" id="link-repositorio">
            <h4>Alberto Méndez Núñez</h4>
        </a>
    </footer>
</body>

</html>