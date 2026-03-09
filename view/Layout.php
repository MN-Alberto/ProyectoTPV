<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TPV Bazar Electrónico</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="webroot/css/login.css" rel="stylesheet" type="text/css">
    <!-- Si la pagina en curso solicitada es la del cajero y la solicita el cajero o el admin, cargamos la hoja de estilos de cajero -->
    <?php if (isset($_SESSION['paginaEnCurso']) && ($_SESSION['paginaEnCurso'] === 'cajero' || $_SESSION['paginaEnCurso'] === 'admin')): ?>
        <link href="webroot/css/cajero.css" rel="stylesheet" type="text/css">
    <?php endif; ?>
    <!-- Si la pagina en curso solicitada es la del admin o cajero, cargamos la hoja de estilos de admin (para modales) -->
    <?php if (isset($_SESSION['paginaEnCurso']) && ($_SESSION['paginaEnCurso'] === 'admin' || $_SESSION['paginaEnCurso'] === 'cajero')): ?>
        <link href="webroot/css/admin.css" rel="stylesheet" type="text/css">
    <?php endif; ?>
    <link rel="icon" href="webroot/img/logoCPU.PNG" type="image/png" id="favicon-link">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <header>
        <div style="display: flex; align-items: center; gap: 15px;">
            <div id="header-icon-container">
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
            </div>
            <h1>TPV Bazar Electrónico</h1>
        </div>
        <!-- Si el id del usuario está guardado en la sesión, mostramos el nombre del usuario y el botón de cerrar sesión -->
        <?php if (isset($_SESSION['idUsuario'])): ?>
            <div class="header-usuario">
                <div class="theme-toggle" title="Cambiar tema">
                    <button class="theme-btn" id="btnModoClaro" onclick="setTheme('light')" title="Modo claro">
                        <i class="fas fa-sun"></i>
                    </button>
                    <button class="theme-btn" id="btnModoOscuro" onclick="setTheme('dark')" title="Modo oscuro">
                        <i class="fas fa-moon"></i>
                    </button>
                </div>
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

<script>
    // Theme toggle functionality
    function setTheme(theme) {
        if (theme === 'dark') {
            document.body.classList.add('dark-mode');
            localStorage.setItem('theme', 'dark');
            document.getElementById('btnModoOscuro').classList.add('active');
            document.getElementById('btnModoClaro').classList.remove('active');
        } else {
            document.body.classList.remove('dark-mode');
            localStorage.setItem('theme', 'light');
            document.getElementById('btnModoClaro').classList.add('active');
            document.getElementById('btnModoOscuro').classList.remove('active');
        }
        // Dispatch custom event for charts to update
        window.dispatchEvent(new Event('themeChange'));
    }

    // Aplicar tema personalizado guardado (header y footer)
    function aplicarTemaPersonalizado() {
        const temaJSON = localStorage.getItem('temaTPV');
        console.log('Leyendo tema de localStorage:', temaJSON);
        if (!temaJSON) {
            console.log('No hay tema guardado en localStorage');
            return;
        }

        try {
            const tema = JSON.parse(temaJSON);
            console.log('Tema parseado:', tema);

            // Aplicar al header
            const header = document.querySelector('header');
            if (header && tema.header_bg) {
                header.style.background = tema.header_bg;
                header.style.color = tema.header_color || '#ffffff';
            }

            // Aplicar al footer
            const footer = document.querySelector('footer');
            if (footer && tema.footer_bg) {
                footer.style.background = tema.footer_bg;
                footer.style.color = tema.footer_color || '#e5e7eb';
            }

            // Aplicar icono personalizado del header
            if (tema.header_icon) {
                const iconContainer = document.getElementById('header-icon-container');
                if (iconContainer) {
                    iconContainer.innerHTML = tema.header_icon;
                    // Ajustar tamaño del icono
                    const svg = iconContainer.querySelector('svg');
                    if (svg) {
                        svg.setAttribute('width', '36');
                        svg.setAttribute('height', '36');
                    }
                }
            }

            // Aplicar favicon personalizado
            if (tema.favicon) {
                const faviconLink = document.getElementById('favicon-link');
                if (faviconLink) {
                    faviconLink.href = tema.favicon;
                }
            }
        } catch (e) {
            console.error('Error applying theme:', e);
        }
    }

    // Load saved theme on page load
    document.addEventListener('DOMContentLoaded', function () {
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme === 'dark') {
            document.body.classList.add('dark-mode');
            const btnModoOscuro = document.getElementById('btnModoOscuro');
            if (btnModoOscuro) btnModoOscuro.classList.add('active');
        } else {
            const btnModoClaro = document.getElementById('btnModoClaro');
            if (btnModoClaro) btnModoClaro.classList.add('active');
        }

        // Aplicar tema personalizado guardado
        if (typeof aplicarTemaGuardado === 'function') {
            aplicarTemaGuardado();
        } else if (typeof aplicarTemaPersonalizado === 'function') {
            aplicarTemaPersonalizado();
        }
    });
</script>

</html>