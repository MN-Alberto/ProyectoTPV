<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang'] ?? 'es'; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('app.title'); ?></title>
    <script>
        window.__LANG__ = <?php echo json_encode($LANG, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
        window.__LANG_CODE__ = '<?php echo $_SESSION['lang'] ?? 'es'; ?>';
        function _t(key, params) {
            const keys = key.split('.');
            let val = window.__LANG__;
            for (const k of keys) {
                if (val === undefined || val === null || val[k] === undefined) {
                    return key;
                }
                val = val[k];
            }
            if (typeof val !== 'string') return key;
            if (params) {
                Object.keys(params).forEach(p => { val = val.replace(p, params[p]); });
            }
            return val;
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="webroot/css/login.css" rel="stylesheet" type="text/css">
    <!-- Si la pagina en curso solicitada es la del cajero y la solicita el cajero o el admin, cargamos la hoja de estilos de cajero -->
    <?php if (isset($_SESSION['paginaEnCurso']) && ($_SESSION['paginaEnCurso'] === 'cajero' || $_SESSION['paginaEnCurso'] === 'admin')): ?>
        <link href="webroot/css/cajero.css" rel="stylesheet" type="text/css">
        <?php
    endif; ?>
    <!-- Si la pagina en curso solicitada es la del admin o cajero, cargamos la hoja de estilos de admin (para modales) -->
    <?php if (isset($_SESSION['paginaEnCurso']) && ($_SESSION['paginaEnCurso'] === 'admin' || $_SESSION['paginaEnCurso'] === 'cajero')): ?>
        <link href="webroot/css/admin.css" rel="stylesheet" type="text/css">
        <?php
    endif; ?>
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
            <h1><?php echo t('app.title'); ?></h1>
        </div>
        <!-- Si el id del usuario está guardado en la sesión, mostramos el nombre del usuario y el botón de cerrar sesión -->
        <?php if (isset($_SESSION['idUsuario'])): ?>
            <div class="header-usuario">
                <!-- Selector de idioma -->
                <div class="lang-toggle" title="<?php echo t('header.language'); ?>">
                    <?php
                    $currentPage = $_SESSION['paginaEnCurso'] ?? 'login';
                    $currentLang = $_SESSION['lang'] ?? 'es';
                    ?>
                    <a href="?lang=es&ctl=<?php echo $currentPage; ?>"
                        class="lang-btn <?php echo $currentLang === 'es' ? 'active' : ''; ?>" title="Español">
                        <svg width="20" height="15" viewBox="0 0 20 15">
                            <rect width="20" height="15" fill="#AA151B" />
                            <rect y="5" width="20" height="5" fill="#F1BF00" />
                        </svg>
                    </a>
                    <a href="?lang=en&ctl=<?php echo $currentPage; ?>"
                        class="lang-btn <?php echo $currentLang === 'en' ? 'active' : ''; ?>" title="English (USA)">
                        <svg width="20" height="15" viewBox="0 0 20 15">
                            <rect width="20" height="15" fill="#B22234" />
                            <rect width="20" y="1" height="1" fill="white" />
                            <rect width="20" y="3" height="1" fill="white" />
                            <rect width="20" y="5" height="1" fill="white" />
                            <rect width="20" y="7" height="1" fill="white" />
                            <rect width="20" y="9" height="1" fill="white" />
                            <rect width="20" y="11" height="1" fill="white" />
                            <rect width="8" height="9" fill="#3C3B6E" />
                            <circle cx="2" cy="2" r="0.5" fill="white" />
                            <circle cx="4" cy="2" r="0.5" fill="white" />
                            <circle cx="6" cy="2" r="0.5" fill="white" />
                            <circle cx="2" cy="4.5" r="0.5" fill="white" />
                            <circle cx="4" cy="4.5" r="0.5" fill="white" />
                            <circle cx="6" cy="4.5" r="0.5" fill="white" />
                            <circle cx="2" cy="7" r="0.5" fill="white" />
                            <circle cx="4" cy="7" r="0.5" fill="white" />
                            <circle cx="6" cy="7" r="0.5" fill="white" />
                        </svg>
                    </a>
                    <a href="?lang=fr&ctl=<?php echo $currentPage; ?>"
                        class="lang-btn <?php echo $currentLang === 'fr' ? 'active' : ''; ?>" title="Français">
                        <svg width="20" height="15" viewBox="0 0 20 15">
                            <rect x="0" width="7" height="15" fill="#0055A4" />
                            <rect x="7" width="6" height="15" fill="#FFFFFF" />
                            <rect x="13" width="7" height="15" fill="#EF4135" />
                        </svg>
                    </a>
                    <a href="?lang=de&ctl=<?php echo $currentPage; ?>"
                        class="lang-btn <?php echo $currentLang === 'de' ? 'active' : ''; ?>" title="Deutsch">
                        <svg width="20" height="15" viewBox="0 0 20 15">
                            <rect y="0" width="20" height="5" fill="#000000" />
                            <rect y="5" width="20" height="5" fill="#FF0000" />
                            <rect y="10" width="20" height="5" fill="#FFCE00" />
                        </svg>
                    </a>
                    <a href="?lang=ru&ctl=<?php echo $currentPage; ?>"
                        class="lang-btn <?php echo $currentLang === 'ru' ? 'active' : ''; ?>" title="Русский">
                        <svg width="20" height="15" viewBox="0 0 20 15">
                            <rect y="0" width="20" height="5" fill="#FFFFFF" stroke="#ddd" />
                            <rect y="5" width="20" height="5" fill="#0039A6" />
                            <rect y="10" width="20" height="5" fill="#D52B1E" />
                        </svg>
                    </a>
                </div>
                <div class="theme-toggle" title="<?php echo t('header.change_theme'); ?>">
                    <button class="theme-btn" id="btnModoClaro" onclick="setTheme('light')"
                        title="<?php echo t('header.light_mode'); ?>">
                        <i class="fas fa-sun"></i>
                    </button>
                    <button class="theme-btn" id="btnModoOscuro" onclick="setTheme('dark')"
                        title="<?php echo t('header.dark_mode'); ?>">
                        <i class="fas fa-moon"></i>
                    </button>
                </div>
                <?php if (isset($_SESSION['paginaEnCurso']) && $_SESSION['paginaEnCurso'] === 'admin'): ?>
                    <a href="?ctl=cajero" class="btn-ir-cajero" title="<?php echo t('header.go_cashier'); ?>">
                        <i class="fas fa-cash-register"></i> <?php echo t('header.cashier'); ?>
                    </a>
                    <?php
                elseif (isset($_SESSION['paginaEnCurso']) && $_SESSION['paginaEnCurso'] === 'cajero' && isset($_SESSION['rolUsuario']) && $_SESSION['rolUsuario'] === 'admin'): ?>
                    <a href="?ctl=admin" class="btn-ir-cajero" title="<?php echo t('header.go_admin'); ?>">
                        <i class="fas fa-user-shield"></i> <?php echo t('header.admin'); ?>
                    </a>
                    <?php
                endif; ?>
                <span><?php echo t('header.hello'); ?>,
                    <strong><?php echo htmlspecialchars($_SESSION['nombreUsuario']); ?></strong></span>
                <form action="<?php echo $_SERVER["PHP_SELF"]; ?>" method="post">
                    <input type="submit" name="cerrarSesion" class="btn-cerrar-sesion"
                        value="<?php echo t('header.logout'); ?>">
                </form>
            </div>
            <?php
        endif; ?>
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

            // Aplicar tamaño de tarjetas de productos
            if (tema.producto_card_width || tema.producto_card_height || tema.producto_card_max_width || tema.producto_card_max_height || tema.producto_grid_columns || tema.producto_grid_gap || tema.producto_nombre_font_size || tema.producto_precio_font_size || tema.producto_stock_font_size) {
                const root = document.documentElement;
                if (tema.producto_card_width) root.style.setProperty('--producto-card-width', tema.producto_card_width);
                if (tema.producto_card_height) root.style.setProperty('--producto-card-height', tema.producto_card_height);
                if (tema.producto_card_max_width) root.style.setProperty('--producto-card-max-width', tema.producto_card_max_width);
                if (tema.producto_card_max_height) root.style.setProperty('--producto-card-max-height', tema.producto_card_max_height);
                if (tema.producto_grid_columns) root.style.setProperty('--producto-grid-columns', tema.producto_grid_columns);
                if (tema.producto_grid_gap) root.style.setProperty('--producto-grid-gap', tema.producto_grid_gap);
                if (tema.producto_nombre_font_size) root.style.setProperty('--producto-nombre-font-size', tema.producto_nombre_font_size);
                if (tema.producto_precio_font_size) root.style.setProperty('--producto-precio-font-size', tema.producto_precio_font_size);
                if (tema.producto_stock_font_size) root.style.setProperty('--producto-stock-font-size', tema.producto_stock_font_size);
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