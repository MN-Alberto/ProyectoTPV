<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TPV Bazar Electrónico</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="webroot/css/login.css" rel="stylesheet" type="text/css">
    <?php if (isset($_SESSION['paginaEnCurso']) && $_SESSION['paginaEnCurso'] === 'cajero'): ?>
        <link href="webroot/css/cajero.css" rel="stylesheet" type="text/css">
    <?php endif; ?>
    <link rel="icon" href="webroot/img/logo.PNG" type="image/png">
</head>

<body>
    <header>
        <h1>TPV Bazar Electrónico</h1>
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