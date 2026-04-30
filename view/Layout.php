<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang'] ?? 'es'; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('app.title'); ?></title>
    <script>
        window.__LANG__ = <?php echo json_encode($LANG, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
        window.__LANG_CODE__ = '<?php echo $_SESSION['lang'] ?? 'es'; ?>';
    </script>
    <script src="webroot/js/layout.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="webroot/css/login.css" rel="stylesheet" type="text/css">
    <!-- Si la pagina en curso solicitada es la del cajero y la solicita el cajero o el admin, cargamos la hoja de estilos de cajero -->
    <?php if (isset($_SESSION['paginaEnCurso']) && ($_SESSION['paginaEnCurso'] === 'cajero' || $_SESSION['paginaEnCurso'] === 'admin')): ?>
        <link href="webroot/css/cajero.css" rel="stylesheet" type="text/css">
        <link href="webroot/css/idiomas-ticket.css" rel="stylesheet" type="text/css">
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
            <div style="display: flex; align-items: baseline; gap: 12px;">
                <h1 style="margin: 0;"><?php echo t('app.title'); ?></h1>
                <span style="font-size: 10px; background: #ecfdf5; color: #059669; border: 1px solid #10b981; padding: 2px 6px; border-radius: 4px; font-weight: 800; letter-spacing: 0.5px; text-transform: uppercase;">VERI*FACTU</span>
            </div>
        </div>

        <div class="header-usuario">
            <!-- Selector de idioma - SIEMPRE VISIBLE incluso sin login -->
            <div class="lang-toggle"
                title="<?php echo isset($_SESSION['idUsuario']) ? t('header.language') : 'Idioma'; ?>">
                <?php
                $currentPage = $_SESSION['paginaEnCurso'] ?? 'login';
                $currentLang = $_SESSION['lang'] ?? (isset($_COOKIE['lang']) ? $_COOKIE['lang'] : 'es');
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
            <div class="theme-toggle"
                title="<?php echo isset($_SESSION['idUsuario']) ? t('header.change_theme') : 'Cambiar tema'; ?>">
                <button class="theme-btn" id="btnModoClaro" onclick="setTheme('light')" title="Modo Claro">
                    <i class="fas fa-sun"></i>
                </button>
                <button class="theme-btn" id="btnModoOscuro" onclick="setTheme('dark')" title="Modo Oscuro">
                    <i class="fas fa-moon"></i>
                </button>
            </div>

            <!-- Si el id del usuario está guardado en la sesión, mostramos el nombre del usuario y el botón de cerrar sesión -->
            <?php if (isset($_SESSION['idUsuario'])): ?>
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


</html>