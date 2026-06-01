// ======================== BLOQUEO DE SESIÓN ========================

/**
 * Bloquea la sesión del cajero y muestra la pantalla de bloqueo.
 */
function bloquearSesion() {
    sessionStorage.setItem('cajero_bloqueado', 'true');
    mostrarPantallaBloqueo();
}

/**
 * Muestra visualmente la pantalla de bloqueo.
 */
function mostrarPantallaBloqueo() {
    const pantalla = document.getElementById('pantallaBloqueo');
    if (pantalla) {
        pantalla.style.display = 'flex';
        // Desenfocar elementos de fondo
        const header = document.querySelector('header');
        const cajero = document.getElementById('cajero');
        if (header) header.style.filter = 'blur(5px)';
        if (cajero) cajero.style.filter = 'blur(5px)';
        
        setTimeout(() => {
            const input = document.getElementById('inputPasswordDesbloqueo');
            if (input) input.focus();
        }, 100);
    }
}

/**
 * Intenta desbloquear la sesión validando la contraseña en el servidor.
 */
function desbloquearSesion() {
    const input = document.getElementById('inputPasswordDesbloqueo');
    const pwd = input.value;
    const errorMsg = document.getElementById('errorDesbloqueo');
    
    if (!pwd) return;
    
    fetch('api/unlock.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ password: pwd })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            sessionStorage.removeItem('cajero_bloqueado');
            document.getElementById('pantallaBloqueo').style.display = 'none';
            input.value = '';
            errorMsg.style.display = 'none';
            
            // Quitar desenfoque
            const header = document.querySelector('header');
            const cajero = document.getElementById('cajero');
            if (header) header.style.filter = '';
            if (cajero) cajero.style.filter = '';
        } else {
            errorMsg.style.display = 'block';
            errorMsg.textContent = data.message || 'Contraseña incorrecta';
        }
    })
    .catch(e => {
        console.error(e);
        errorMsg.style.display = 'block';
        errorMsg.textContent = 'Error de conexión';
    });
}

// Check at startup
document.addEventListener('DOMContentLoaded', () => {
    if (sessionStorage.getItem('cajero_bloqueado') === 'true') {
        mostrarPantallaBloqueo();
    }
});
