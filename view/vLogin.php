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

    <div class="header-usuario">
        <div class="lang-toggle">
            <?php $currentLang = isset($_COOKIE['lang']) ? $_COOKIE['lang'] : 'es'; ?>
            <a href="?lang=es" class="lang-btn <?php echo $currentLang === 'es' ? 'active' : ''; ?>" title="Español">
                <svg width="20" height="15" viewBox="0 0 20 15">
                    <rect width="20" height="15" fill="#AA151B" />
                    <rect y="5" width="20" height="5" fill="#F1BF00" />
                </svg>
            </a>
            <a href="?lang=en" class="lang-btn <?php echo $currentLang === 'en' ? 'active' : ''; ?>"
                title="English (USA)">
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
            <a href="?lang=fr" class="lang-btn <?php echo $currentLang === 'fr' ? 'active' : ''; ?>" title="Français">
                <svg width="20" height="15" viewBox="0 0 20 15">
                    <rect x="0" width="7" height="15" fill="#0055A4" />
                    <rect x="7" width="6" height="15" fill="#FFFFFF" />
                    <rect x="13" width="7" height="15" fill="#EF4135" />
                </svg>
            </a>
            <a href="?lang=de" class="lang-btn <?php echo $currentLang === 'de' ? 'active' : ''; ?>" title="Deutsch">
                <svg width="20" height="15" viewBox="0 0 20 15">
                    <rect y="0" width="20" height="5" fill="#000000" />
                    <rect y="5" width="20" height="5" fill="#FF0000" />
                    <rect y="10" width="20" height="5" fill="#FFCE00" />
                </svg>
            </a>
            <a href="?lang=ru" class="lang-btn <?php echo $currentLang === 'ru' ? 'active' : ''; ?>" title="Русский">
                <svg width="20" height="15" viewBox="0 0 20 15">
                    <rect y="0" width="20" height="5" fill="#FFFFFF" stroke="#ddd" />
                    <rect y="5" width="20" height="5" fill="#0039A6" />
                    <rect y="10" width="20" height="5" fill="#D52B1E" />
                </svg>
            </a>
        </div>
        <div class="theme-toggle">
            <button class="theme-btn" id="btnModoClaro" onclick="setTheme('light')" title="Modo Claro">
                <i class="fas fa-sun"></i>
            </button>
            <button class="theme-btn" id="btnModoOscuro" onclick="setTheme('dark')" title="Modo Oscuro">
                <i class="fas fa-moon"></i>
            </button>
        </div>
    </div>
</header>

<section id="login">

    <div class="login-card">
        <div class="login-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
            </svg>
        </div>
        <h2>
            <?php echo t('login.title'); ?>
        </h2>
        <p class="login-subtitle">
            <?php echo t('app.subtitle'); ?>
        </p>

        <!-- Si hay algún error, se muestra en un div con la clase login-error -->
        <?php if (!empty($error)): ?>
            <div class="login-error">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="15" y1="9" x2="9" y2="15"></line>
                    <line x1="9" y1="9" x2="15" y2="15"></line>
                </svg>
                <?php echo $error; ?>
            </div>
            <?php
        endif; ?>

        <form method="POST" action="index.php" autocomplete="off">
            <div class="form-group">
                <label for="usuario">
                    <?php echo t('login.user'); ?>
                </label>
                <input type="text" id="usuario" name="usuario" placeholder="<?php echo t('login.user_placeholder'); ?>"
                    required autofocus>
            </div>
            <div class="form-group">
                <label for="password"><?php echo t('login.password'); ?></label>
                <input type="password" id="password" name="password"
                    placeholder="<?php echo t('login.password_placeholder'); ?>" required>
            </div>
            <button type="submit" class="btn-login"><?php echo t('login.submit'); ?></button>
        </form>

        <div class="forgot-password-link">
            <a href="#" id="link-recuperar-password"><?php echo t('login.forgot_password'); ?></a>
        </div>
    </div>

    <!-- Modal de Recuperación de Contraseña -->
    <div id="modal-recuperar-password" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="modal-close" id="cerrar-modal-recuperar">&times;</span>
            <h3><?php echo t('login.recover_title'); ?></h3>

            <!-- Paso 1: Introducir nombre de usuario -->
            <div id="paso-usuario">
                <p class="modal-subtitle"><?php echo t('login.recover_step1'); ?></p>
                <div class="form-group">
                    <label for="recup-usuario"><?php echo t('login.user'); ?></label>
                    <input type="text" id="recup-usuario" placeholder="<?php echo t('login.user_placeholder'); ?>"
                        required>
                </div>
                <button type="button" class="btn-login"
                    id="btn-enviar-codigo"><?php echo t('login.recover_send_code'); ?></button>
            </div>

            <!-- Paso 2: Introducir código -->
            <div id="paso-codigo" style="display: none;">
                <p class="modal-subtitle"><?php echo t('login.recover_step2'); ?></p>
                <div class="form-group">
                    <label for="recup-codigo"><?php echo t('login.recover_code'); ?></label>
                    <input type="text" id="recup-codigo" placeholder="123456" maxlength="6" required>
                </div>
                <button type="button" class="btn-login"
                    id="btn-verificar-codigo"><?php echo t('login.recover_verify'); ?></button>
                <button type="button" class="btn-secondary"
                    id="btn-volver-usuario"><?php echo t('login.recover_back'); ?></button>
            </div>

            <!-- Paso 3: Nueva contraseña -->
            <div id=" paso-nueva-password" style="display: none;">
                <p class="modal-subtitle"><?php echo t('login.recover_step3'); ?></p>
                <div class="form-group">
                    <label for="recup-nueva-password"><?php echo t('login.recover_new_password'); ?></label>
                    <input type="password" id="recup-nueva-password"
                        placeholder="<?php echo t('login.recover_min_chars'); ?>" required>
                </div>
                <div class="form-group">
                    <label for="recup-confirmar-password"><?php echo t('login.recover_confirm_password'); ?></label>
                    <input type="password" id="recup-confirmar-password"
                        placeholder="<?php echo t('login.recover_repeat'); ?>" required>
                </div>
                <button type="button" class="btn-login"
                    id="btn-cambiar-password"><?php echo t('login.recover_change'); ?></button>
            </div>

            <div id="recuperar-mensaje" class="login-error" style="display: none;"></div>
        </div>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Elementos del modal
        const modal = document.getElementById('modal-recuperar-password');
        const linkRecuperar = document.getElementById('link-recuperar-password');
        const cerrarModal = document.getElementById('cerrar-modal-recuperar');

        // Pasos del modal
        const pasoUsuario = document.getElementById('paso-usuario');
        const pasoCodigo = document.getElementById('paso-codigo');
        const pasoNuevaPassword = document.getElementById(' paso-nueva-password');

        // Botones
        const btnEnviarCodigo = document.getElementById('btn-enviar-codigo');
        const btnVerificarCodigo = document.getElementById('btn-verificar-codigo');
        const btnVolverUsuario = document.getElementById('btn-volver-usuario');
        const btnCambiarPassword = document.getElementById('btn-cambiar-password');

        // Inputs
        const inputUsuario = document.getElementById('recup-usuario');
        const inputCodigo = document.getElementById('recup-codigo');
        const inputNuevaPassword = document.getElementById('recup-nueva-password');
        const inputConfirmarPassword = document.getElementById('recup-confirmar-password');

        // Mensaje
        const mensajeDiv = document.getElementById('recuperar-mensaje');

        // Función para mostrar mensaje
        function mostrarMensaje(texto, tipo) {
            mensajeDiv.textContent = texto;
            mensajeDiv.className = 'login-error ' + tipo;
            mensajeDiv.style.display = 'block';
        }

        // Función para ocultar mensaje
        function ocultarMensaje() {
            mensajeDiv.style.display = 'none';
        }

        // Abrir modal
        linkRecuperar.addEventListener('click', function (e) {
            e.preventDefault();
            modal.style.display = 'flex';
            resetModal();
        });

        // Cerrar modal
        cerrarModal.addEventListener('click', function () {
            modal.style.display = 'none';
            resetModal();
        });

        // Cerrar modal al hacer clic fuera
        modal.addEventListener('click', function (e) {
            if (e.target === modal) {
                modal.style.display = 'none';
                resetModal();
            }
        });

        // Resetear modal
        function resetModal() {
            pasoUsuario.style.display = 'block';
            pasoCodigo.style.display = 'none';
            pasoNuevaPassword.style.display = 'none';
            inputUsuario.value = '';
            inputCodigo.value = '';
            inputNuevaPassword.value = '';
            inputConfirmarPassword.value = '';
            ocultarMensaje();
        }

        // Enviar código
        btnEnviarCodigo.addEventListener('click', async function () {
            const nombre = inputUsuario.value.trim();

            if (!nombre) {
                mostrarMensaje(_t('login.error_enter_user'), 'error');
                return;
            }

            btnEnviarCodigo.disabled = true;
            btnEnviarCodigo.textContent = _t('login.recover_sending');

            let response;
            try {
                const formData = new FormData();
                formData.append('action', 'send_recovery_code');
                formData.append('nombre', nombre);

                response = await fetch('/proyectoTPV/api/recuperar-password.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.ok) {
                    mostrarMensaje(data.message, 'success');
                    pasoUsuario.style.display = 'none';
                    pasoCodigo.style.display = 'block';
                    inputCodigo.focus();
                } else {
                    mostrarMensaje(data.error, 'error');
                }
            } catch (err) {
                console.error('Error:', err);
                if (response) {
                    console.log('Response status:', response.status);
                    mostrarMensaje(_t('login.error_connection_status') + ' ' + response.status, 'error');
                } else {
                    mostrarMensaje(_t('login.error_connection'), 'error');
                }
            }

            btnEnviarCodigo.disabled = false;
            btnEnviarCodigo.textContent = _t('login.recover_send_code');
        });

        // Permitir enviar con Enter en usuario
        inputUsuario.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                btnEnviarCodigo.click();
            }
        });

        // Volver a usuario
        btnVolverUsuario.addEventListener('click', function () {
            pasoCodigo.style.display = 'none';
            pasoUsuario.style.display = 'block';
            ocultarMensaje();
        });

        // Verificar código
        btnVerificarCodigo.addEventListener('click', async function () {
            const codigo = inputCodigo.value.trim();

            if (!codigo) {
                mostrarMensaje(_t('login.error_enter_code'), 'error');
                return;
            }

            if (codigo.length !== 6) {
                mostrarMensaje(_t('login.error_code_6digits'), 'error');
                return;
            }

            btnVerificarCodigo.disabled = true;
            btnVerificarCodigo.textContent = _t('login.recover_verifying');

            try {
                const formData = new FormData();
                formData.append('action', 'verify_recovery_code');
                formData.append('codigo', codigo);

                const response = await fetch('/proyectoTPV/api/recuperar-password.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.ok) {
                    pasoCodigo.style.display = 'none';
                    pasoNuevaPassword.style.display = 'block';
                    inputNuevaPassword.focus();
                } else {
                    mostrarMensaje(data.error, 'error');
                }
            } catch (err) {
                mostrarMensaje(_t('login.error_connection'), 'error');
            }

            btnVerificarCodigo.disabled = false;
            btnVerificarCodigo.textContent = _t('login.recover_verify');
        });

        // Permitir enviar con Enter en código
        inputCodigo.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                btnVerificarCodigo.click();
            }
        });

        // Cambiar contraseña
        btnCambiarPassword.addEventListener('click', async function () {
            const password = inputNuevaPassword.value;
            const confirmPassword = inputConfirmarPassword.value;

            if (!password || !confirmPassword) {
                mostrarMensaje(_t('login.error_fill_both'), 'error');
                return;
            }

            if (password.length < 6) {
                mostrarMensaje(_t('login.error_min_password'), 'error');
                return;
            }

            if (password !== confirmPassword) {
                mostrarMensaje(_t('login.error_no_match'), 'error');
                // Limpiar campos de contraseña
                inputNuevaPassword.value = '';
                inputConfirmarPassword.value = '';
                return;
            }

            btnCambiarPassword.disabled = true;
            btnCambiarPassword.textContent = _t('login.recover_changing');

            try {
                const formData = new FormData();
                formData.append('action', 'change_password');
                formData.append('password', password);
                formData.append('confirm_password', confirmPassword);

                const response = await fetch('/proyectoTPV/api/recuperar-password.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.ok) {
                    mostrarMensaje(data.message, 'success');
                    setTimeout(function () {
                        modal.style.display = 'none';
                        resetModal();
                    }, 2000);
                } else {
                    mostrarMensaje(data.error, 'error');
                    // Limpiar campos de contraseña en caso de error
                    inputNuevaPassword.value = '';
                    inputConfirmarPassword.value = '';
                }
            } catch (err) {
                mostrarMensaje(_t('login.error_connection'), 'error');
            }

            btnCambiarPassword.disabled = false;
            btnCambiarPassword.textContent = _t('login.recover_change');
        });

        // Permitir enviar con Enter en password
        inputConfirmarPassword.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                btnCambiarPassword.click();
            }
        });

        // Limpiar mensaje de error al escribir en los campos de contraseña
        inputNuevaPassword.addEventListener('input', function () {
            ocultarMensaje();
        });
        inputConfirmarPassword.addEventListener('input', function () {
            ocultarMensaje();
        });
    });

    // Theme toggle functionality igual que en Layout
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
        window.dispatchEvent(new Event('themeChange'));
    }

    // Cargar tema guardado al cargar pagina
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
    });
</script>