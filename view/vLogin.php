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

</script>