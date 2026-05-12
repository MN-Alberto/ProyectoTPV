<div class="login-page-wrapper">
    <!-- Fondo dinámico / Gradiente Mesh -->
    <div class="login-mesh-bg"></div>

    <section id="login">
        <div class="login-card">
            <div class="login-brand">
                <div class="brand-logo">
                    <i class="fas fa-cash-register"></i>
                </div>
                <div class="brand-info">
                    <h2><?php echo t('login.title'); ?></h2>
                    <p class="login-subtitle"><?php echo t('app.subtitle'); ?></p>
                </div>
            </div>

            <!-- Manejo de Errores -->
            <?php if (!empty($error)): ?>
                <div class="login-error-premium">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo $error; ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="index.php" autocomplete="off" class="login-form">
                <div class="form-group-premium">
                    <label for="usuario"><?php echo t('login.user'); ?></label>
                    <div class="input-with-icon">
                        <i class="fas fa-user"></i>
                        <input type="text" id="usuario" name="usuario" placeholder="<?php echo t('login.user_placeholder'); ?>" required autofocus>
                    </div>
                </div>
                
                <div class="form-group-premium">
                    <label for="password"><?php echo t('login.password'); ?></label>
                    <div class="input-with-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" placeholder="<?php echo t('login.password_placeholder'); ?>" required>
                        <button type="button" class="toggle-password" onclick="togglePasswordVisibility()">
                            <i class="fas fa-eye" id="eye-icon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-login-premium">
                    <span><?php echo t('login.submit'); ?></span>
                    <i class="fas fa-arrow-right"></i>
                </button>
            </form>

            <div class="login-footer-links">
                <a href="#" id="link-recuperar-password" class="link-forgot">
                    <i class="fas fa-key"></i> <?php echo t('login.forgot_password'); ?>
                </a>
            </div>
        </div>

        <!-- Modal de Recuperación de Contraseña (Premium) -->
        <div id="modal-recuperar-password" class="modal-overlay" style="display: none;">
            <div class="modal-content modal-premium" style="max-width: 450px;">
                <div class="modal-header-premium" style="background: linear-gradient(135deg, #6366f1, #4f46e5); padding: 30px;">
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <div style="background: rgba(255,255,255,0.2); width: 45px; height: 45px; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-shield-alt" style="color: #fff; font-size: 1.5rem;"></i>
                        </div>
                        <div>
                            <h3 style="margin: 0; color: #fff; font-size: 1.3rem; font-weight: 700;"><?php echo t('login.recover_title'); ?></h3>
                            <p style="margin: 3px 0 0 0; color: rgba(255,255,255,0.8); font-size: 0.85rem;">Siga los pasos para restablecer su acceso</p>
                        </div>
                    </div>
                    <button class="modal-close-btn" id="cerrar-modal-recuperar" style="position: absolute; top: 25px; right: 25px; background: rgba(255,255,255,0.15); border: none; color: white; width: 32px; height: 32px; border-radius: 10px; cursor: pointer; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <!-- Indicador de Progreso -->
                <div class="recovery-progress" style="display: flex; justify-content: space-between; padding: 25px 50px 10px; position: relative;">
                    <div class="progress-line" style="position: absolute; top: 40px; left: 50px; right: 50px; height: 2px; background: #e2e8f0; z-index: 1;">
                        <div id="progress-fill" style="width: 0%; height: 100%; background: #6366f1; transition: width 0.3s ease;"></div>
                    </div>
                    <div class="progress-step active" id="step-dot-1" style="z-index: 2; position: relative; display: flex; flex-direction: column; align-items: center; gap: 8px;">
                        <div style="width: 30px; height: 30px; border-radius: 50%; background: #6366f1; color: white; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; font-weight: 700; border: 4px solid #fff; box-shadow: 0 0 0 1px #6366f1;"><i class="fas fa-user" style="font-size: 0.7rem;"></i></div>
                    </div>
                    <div class="progress-step" id="step-dot-2" style="z-index: 2; position: relative; display: flex; flex-direction: column; align-items: center; gap: 8px;">
                        <div style="width: 30px; height: 30px; border-radius: 50%; background: #fff; color: #94a3b8; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; font-weight: 700; border: 2px solid #e2e8f0;"><i class="fas fa-key" style="font-size: 0.7rem;"></i></div>
                    </div>
                    <div class="progress-step" id="step-dot-3" style="z-index: 2; position: relative; display: flex; flex-direction: column; align-items: center; gap: 8px;">
                        <div style="width: 30px; height: 30px; border-radius: 50%; background: #fff; color: #94a3b8; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; font-weight: 700; border: 2px solid #e2e8f0;"><i class="fas fa-lock" style="font-size: 0.7rem;"></i></div>
                    </div>
                </div>

                <div style="padding: 30px;">
                    <!-- Paso 1: Introducir nombre de usuario -->
                    <div id="paso-usuario">
                        <p class="modal-instruction"><?php echo t('login.recover_step1'); ?></p>
                        <div class="form-group-premium">
                            <label for="recup-usuario"><?php echo t('login.user'); ?></label>
                            <div class="input-with-icon">
                                <i class="fas fa-user"></i>
                                <input type="text" id="recup-usuario" placeholder="<?php echo t('login.user_placeholder'); ?>" required>
                            </div>
                        </div>
                        <button type="button" class="btn-login-premium" id="btn-enviar-codigo" style="margin-top: 10px;">
                            <?php echo t('login.recover_send_code'); ?>
                        </button>
                    </div>

                    <!-- Paso 2: Introducir código -->
                    <div id="paso-codigo" style="display: none;">
                        <p class="modal-instruction"><?php echo t('login.recover_step2'); ?></p>
                        <div class="form-group-premium">
                            <label for="recup-codigo"><?php echo t('login.recover_code'); ?></label>
                            <div class="input-with-icon">
                                <i class="fas fa-hashtag"></i>
                                <input type="text" id="recup-codigo" placeholder="123456" maxlength="6" required style="text-align: center; letter-spacing: 5px; font-weight: 800; font-size: 1.2rem;">
                            </div>
                        </div>
                        <div style="display: flex; gap: 10px; margin-top: 15px;">
                            <button type="button" class="btn-modal-cancelar" id="btn-volver-usuario" style="flex: 1;">
                                <?php echo t('login.recover_back'); ?>
                            </button>
                            <button type="button" class="btn-login-premium" id="btn-verificar-codigo" style="flex: 2;">
                                <?php echo t('login.recover_verify'); ?>
                            </button>
                        </div>
                    </div>

                    <!-- Paso 3: Nueva contraseña -->
                    <div id="paso-nueva-password" style="display: none;">
                        <p class="modal-instruction"><?php echo t('login.recover_step3'); ?></p>
                        <div class="form-group-premium">
                            <label for="recup-nueva-password"><?php echo t('login.recover_new_password'); ?></label>
                            <div class="input-with-icon">
                                <i class="fas fa-lock"></i>
                                <input type="password" id="recup-nueva-password" placeholder="<?php echo t('login.recover_min_chars'); ?>" required>
                            </div>
                        </div>
                        <div class="form-group-premium">
                            <label for="recup-confirmar-password"><?php echo t('login.recover_confirm_password'); ?></label>
                            <div class="input-with-icon">
                                <i class="fas fa-check-circle"></i>
                                <input type="password" id="recup-confirmar-password" placeholder="<?php echo t('login.recover_repeat'); ?>" required>
                            </div>
                        </div>
                        <button type="button" class="btn-login-premium" id="btn-cambiar-password" style="margin-top: 10px;">
                            <?php echo t('login.recover_change'); ?>
                        </button>
                    </div>

                    <div id="recuperar-mensaje" class="login-error-premium" style="display: none; margin-top: 20px;"></div>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
    function togglePasswordVisibility() {
        const passwordInput = document.getElementById('password');
        const eyeIcon = document.getElementById('eye-icon');
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            eyeIcon.classList.remove('fa-eye');
            eyeIcon.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            eyeIcon.classList.remove('fa-eye-slash');
            eyeIcon.classList.add('fa-eye');
        }
    }
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Elementos del modal
        const modal = document.getElementById('modal-recuperar-password');
        const linkRecuperar = document.getElementById('link-recuperar-password');
        const cerrarModal = document.getElementById('cerrar-modal-recuperar');

        // Pasos del modal
        const pasoUsuario = document.getElementById('paso-usuario');
        const pasoCodigo = document.getElementById('paso-codigo');
        const pasoNuevaPassword = document.getElementById('paso-nueva-password');

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
        let recoveryToken = ''; // Token temporal para el flujo sin sesión

        // Función para mostrar mensaje
        function mostrarMensaje(texto, tipo) {
            mensajeDiv.innerHTML = `<i class="fas ${tipo === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i> <span>${texto}</span>`;
            mensajeDiv.className = 'login-error-premium ' + tipo;
            mensajeDiv.style.display = 'flex';
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

        // Indicadores de progreso
        const progressFill = document.getElementById('progress-fill');
        const stepDots = [
            document.getElementById('step-dot-1'),
            document.getElementById('step-dot-2'),
            document.getElementById('step-dot-3')
        ];

        function updateProgress(step) {
            const percentages = [0, 50, 100];
            progressFill.style.width = percentages[step - 1] + '%';
            
            stepDots.forEach((dot, index) => {
                const dotInner = dot.querySelector('div');
                if (index < step) {
                    dotInner.style.background = '#6366f1';
                    dotInner.style.color = 'white';
                    dotInner.style.borderColor = '#fff';
                    dotInner.style.boxShadow = '0 0 0 1px #6366f1';
                    if (index < step - 1) {
                        dotInner.innerHTML = '<i class="fas fa-check" style="font-size: 0.7rem;"></i>';
                    }
                } else {
                    dotInner.style.background = '#fff';
                    dotInner.style.color = '#94a3b8';
                    dotInner.style.borderColor = '#e2e8f0';
                    dotInner.style.boxShadow = 'none';
                    // Reset icons
                    const icons = ['fa-user', 'fa-key', 'fa-lock'];
                    dotInner.innerHTML = `<i class="fas ${icons[index]}" style="font-size: 0.7rem;"></i>`;
                }
            });
        }

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
            updateProgress(1);
        }

        // Enviar código
        btnEnviarCodigo.addEventListener('click', async function () {
            const nombre = inputUsuario.value.trim();

            if (!nombre) {
                mostrarMensaje(_t('login.error_enter_user'), 'error');
                return;
            }

            btnEnviarCodigo.disabled = true;
            btnEnviarCodigo.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ' + _t('login.recover_sending');

            let response;
            try {
                const formData = new FormData();
                formData.append('action', 'send_recovery_code');
                formData.append('nombre', nombre);

                response = await fetch('api/recuperar-password.php', {
                    method: 'POST',
                    credentials: 'include',
                    body: formData
                });

                const data = await response.json();
                console.log('Recovery API Response (Step 1):', data);

                if (data.ok) {
                    mostrarMensaje(data.message, 'success');
                    pasoUsuario.style.display = 'none';
                    pasoCodigo.style.display = 'block';
                    inputCodigo.focus();
                    updateProgress(2);
                } else {
                    mostrarMensaje(data.error, 'error');
                }
            } catch (err) {
                console.error('Error:', err);
                mostrarMensaje(_t('login.error_connection'), 'error');
            }

            btnEnviarCodigo.disabled = false;
            btnEnviarCodigo.textContent = _t('login.recover_send_code');
        });

        // Volver a usuario
        btnVolverUsuario.addEventListener('click', function () {
            pasoCodigo.style.display = 'none';
            pasoUsuario.style.display = 'block';
            ocultarMensaje();
            updateProgress(1);
        });

        // Verificar código
        btnVerificarCodigo.addEventListener('click', async function () {
            const codigo = inputCodigo.value.trim();

            if (!codigo) {
                mostrarMensaje(_t('login.error_enter_code'), 'error');
                return;
            }

            btnVerificarCodigo.disabled = true;
            btnVerificarCodigo.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ' + _t('login.recover_verifying');

            try {
                const formData = new FormData();
                formData.append('action', 'verify_recovery_code');
                formData.append('codigo', codigo);

                const response = await fetch('api/recuperar-password.php', {
                    method: 'POST',
                    credentials: 'include',
                    body: formData
                });

                const data = await response.json();

                if (data.ok) {
                    recoveryToken = data.temp_token; // Guardamos el token
                    pasoCodigo.style.display = 'none';
                    pasoNuevaPassword.style.display = 'block';
                    inputNuevaPassword.focus();
                    updateProgress(3);
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
                formData.append('temp_token', recoveryToken); // Enviamos el token

                const response = await fetch('api/recuperar-password.php', {
                    method: 'POST',
                    credentials: 'include',
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