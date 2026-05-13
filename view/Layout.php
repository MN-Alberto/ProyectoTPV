<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang'] ?? 'es'; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('app.title'); ?></title>

    <!-- Configuración Global de Idioma para JavaScript -->
    <script>
        // Inyectamos el diccionario actual y el código de idioma para uso en scripts del cliente (layout.js, cajero.js, etc.)
        window.__LANG__ = <?php echo json_encode($LANG, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}'; ?>;
        window.__LANG_CODE__ = '<?php echo $_SESSION['lang'] ?? 'es'; ?>';
    </script>

    <!-- Scripts y Fuentes Base -->
    <script src="webroot/js/layout.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="webroot/css/login.css" rel="stylesheet" type="text/css">

    <!-- Carga Condicional de Estilos según el Contexto -->
    <?php
    /**
     * Si el usuario está en el panel del Cajero o en el Admin, cargamos los estilos específicos.
     * Se cargan por separado para mantener el CSS ligero en la pantalla de login.
     */
    if (isset($_SESSION['paginaEnCurso']) && ($_SESSION['paginaEnCurso'] === 'cajero' || $_SESSION['paginaEnCurso'] === 'admin')): ?>
        <link href="webroot/css/cajero.css" rel="stylesheet" type="text/css">
        <link href="webroot/css/idiomas-ticket.css" rel="stylesheet" type="text/css">
    <?php endif; ?>

    <?php
    /**
     * El CSS de Admin contiene estilos para modales y tablas avanzadas que también
     * se reutilizan en algunas partes del cajero.
     */
    if (isset($_SESSION['paginaEnCurso']) && ($_SESSION['paginaEnCurso'] === 'admin' || $_SESSION['paginaEnCurso'] === 'cajero')): ?>
        <link href="webroot/css/admin.css?v=5" rel="stylesheet" type="text/css">
    <?php endif; ?>

    <link rel="icon" href="webroot/img/logoCPU.PNG" type="image/png" id="favicon-link">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <header>
        <div class="header-brand">
            <div class="brand-icon-wrapper">
                <i class="fas fa-microchip"></i>
            </div>
            <div class="brand-text-wrapper">
                <h1 class="app-title-header"><?php echo t('app.title'); ?></h1>
                <!-- Distintivo de cumplimiento fiscal Premium -->
                <span class="badge-verifactu">
                    <i class="fas fa-check-circle"></i> VERI*FACTU
                </span>
            </div>
        </div>

        <div class="header-usuario">
            <!-- Bloque: Selector de Idiomas (Flags) -->
            <div class="lang-toggle"
                title="<?php echo isset($_SESSION['idUsuario']) ? t('header.language') : 'Idioma'; ?>">
                <?php
                $currentPage = $_SESSION['paginaEnCurso'] ?? 'login';
                $currentLang = $_SESSION['lang'] ?? (isset($_COOKIE['lang']) ? $_COOKIE['lang'] : 'es');
                ?>
                <!-- Botón: Español -->
                <a href="?lang=es&ctl=<?php echo $currentPage; ?>"
                    class="lang-btn <?php echo $currentLang === 'es' ? 'active' : ''; ?>" title="Español">
                    <svg width="20" height="15" viewBox="0 0 20 15">
                        <rect width="20" height="15" fill="#AA151B" />
                        <rect y="5" width="20" height="5" fill="#F1BF00" />
                    </svg>
                </a>
                <!-- Botón: Inglés -->
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
                <!-- Botón: Francés -->
                <a href="?lang=fr&ctl=<?php echo $currentPage; ?>"
                    class="lang-btn <?php echo $currentLang === 'fr' ? 'active' : ''; ?>" title="Français">
                    <svg width="20" height="15" viewBox="0 0 20 15">
                        <rect x="0" width="7" height="15" fill="#0055A4" />
                        <rect x="7" width="6" height="15" fill="#FFFFFF" />
                        <rect x="13" width="7" height="15" fill="#EF4135" />
                    </svg>
                </a>
                <!-- Botón: Alemán -->
                <a href="?lang=de&ctl=<?php echo $currentPage; ?>"
                    class="lang-btn <?php echo $currentLang === 'de' ? 'active' : ''; ?>" title="Deutsch">
                    <svg width="20" height="15" viewBox="0 0 20 15">
                        <rect y="0" width="20" height="5" fill="#000000" />
                        <rect y="5" width="20" height="5" fill="#FF0000" />
                        <rect y="10" width="20" height="5" fill="#FFCE00" />
                    </svg>
                </a>
                <!-- Botón: Ruso -->
                <a href="?lang=ru&ctl=<?php echo $currentPage; ?>"
                    class="lang-btn <?php echo $currentLang === 'ru' ? 'active' : ''; ?>" title="Русский">
                    <svg width="20" height="15" viewBox="0 0 20 15">
                        <rect y="0" width="20" height="5" fill="#FFFFFF" stroke="#ddd" />
                        <rect y="5" width="20" height="5" fill="#0039A6" />
                        <rect y="10" width="20" height="5" fill="#D52B1E" />
                    </svg>
                </a>
            </div>

            <!-- Bloque: Control de Tema (Dark/Light) -->
            <div class="theme-toggle"
                title="<?php echo isset($_SESSION['idUsuario']) ? t('header.change_theme') : 'Cambiar tema'; ?>">
                <button class="theme-btn" id="btnModoClaro" onclick="setTheme('light')" title="Modo Claro">
                    <i class="fas fa-sun"></i>
                </button>
                <button class="theme-btn" id="btnModoOscuro" onclick="setTheme('dark')" title="Modo Oscuro">
                    <i class="fas fa-moon"></i>
                </button>
            </div>

            <!-- Bloque: Bloquear Sesión (Solo Cajero) -->
            <?php if (isset($_SESSION['paginaEnCurso']) && $_SESSION['paginaEnCurso'] === 'cajero'): ?>
                <div class="theme-toggle">
                    <button class="theme-btn" onclick="bloquearSesion()"
                        title="<?php echo t('header.lock_session') ?? 'Bloquear sesión'; ?>">
                        <i class="fas fa-lock"></i>
                    </button>
                </div>
            <?php endif; ?>

            <!-- Bloque: Información de Usuario y Navegación de Rol -->
            <?php if (isset($_SESSION['idUsuario'])): ?>
                <?php
                /**
                 * Lógica de intercambio de paneles (Admin <-> Cajero)
                 * Solo permitida si el usuario tiene rol de administrador.
                 */
                if (isset($_SESSION['paginaEnCurso']) && $_SESSION['paginaEnCurso'] === 'admin'): ?>
                    <a href="?ctl=cajero" class="btn-ir-cajero" title="<?php echo t('header.go_cashier'); ?>">
                        <i class="fas fa-cash-register"></i> <?php echo t('header.cashier'); ?>
                    </a>
                <?php elseif (isset($_SESSION['paginaEnCurso']) && $_SESSION['paginaEnCurso'] === 'cajero' && isset($_SESSION['rolUsuario']) && $_SESSION['rolUsuario'] === 'admin'): ?>
                    <a href="?ctl=admin" class="btn-ir-cajero" title="<?php echo t('header.go_admin'); ?>">
                        <i class="fas fa-user-shield"></i> <?php echo t('header.admin'); ?>
                    </a>
                <?php endif; ?>

                <span><?php echo t('header.hello'); ?>,
                    <strong><?php echo htmlspecialchars($_SESSION['nombreUsuario']); ?></strong></span>

                <!-- Botón de Cierre de Sesión -->
                <form action="<?php echo $_SERVER["PHP_SELF"]; ?>" method="post">
                    <input type="submit" name="cerrarSesion" class="btn-cerrar-sesion"
                        value="<?php echo t('header.logout'); ?>">
                </form>
            <?php endif; ?>
        </div>
    </header>

    <!-- Área de Contenido Principal -->
    <main>
        <?php
        /**
         * Inyección Dinámica de la Vista
         * El controlador determina qué archivo debe cargarse en $_SESSION["paginaEnCurso"]
         * y lo insertamos aquí para que el layout actúe como marco.
         */
        require_once $view[$_SESSION["paginaEnCurso"]];
        ?>
    </main>

    <!-- Pie de Página -->
    <footer>
        <a href="https://github.com/MN-Alberto/ProyectoTPV" target="blank" id="link-repositorio">
            <h4>Alberto Méndez Núñez &copy; <?php echo date('Y'); ?></h4>
        </a>
    </footer>
</body>

</html>