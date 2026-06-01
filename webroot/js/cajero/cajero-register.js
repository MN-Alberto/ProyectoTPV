/**
 * Muestra el modal de apertura de caja
 */
function mostrarModalAbrirCaja() {
    const modal = document.getElementById('modalAbrirCaja');
    const recovery = document.getElementById('cambioRecovery');
    const importeInput = document.getElementById('importeInicial');
    const divImporte = document.getElementById('divImporteInicial');
    const radioRecuperar = document.querySelector('input[name="opcionCambio"][value="recuperar"]');

    if (!modal) return;

    modal.style.display = 'flex';
    if (recovery) recovery.value = '0';

    if (importeInput) {
        importeInput.value = '';
        importeInput.required = false;
    }

    if (divImporte) {
        divImporte.style.opacity = '0.5';
    }

    if (radioRecuperar) {
        radioRecuperar.checked = true;
        toggleCambio(false);
    }

    if (importeInput) importeInput.focus();
}

/**
 * Alterna entre recuperar el cambio anterior o introducir un importe inicial nuevo
 * @param {boolean} mostrarNuevo 
 */
function toggleCambio(mostrarNuevo) {
    const divImporte = document.getElementById('divImporteInicial');
    const importeInput = document.getElementById('importeInicial');
    const cambioInput = document.getElementById('cambioRecovery');

    if (!divImporte || !importeInput || !cambioInput) return;

    if (mostrarNuevo) {
        divImporte.style.opacity = '1';
        importeInput.required = true;
        cambioInput.value = '0';
    } else {
        divImporte.style.opacity = '0.5';
        importeInput.required = false;
        // El cambio anterior se inyecta en TPV_CONTEXT
        const cambioAnterior = (typeof TPV_CONTEXT !== 'undefined') ? TPV_CONTEXT.cambioAnterior : 0;
        cambioInput.value = cambioAnterior;
    }
}

/**
 * Muestra el modal de retiro de dinero
 */
function mostrarModalRetiro() {
    const modal = document.getElementById('modalRetiro');
    const input = document.getElementById('importeRetiro');
    if (modal) {
        modal.style.display = 'flex';
        if (input) input.focus();
    }
}

/**
 * Valida que el importe a retirar sea correcto y no exceda el efectivo disponible
 * @returns {boolean}
 */
function validarRetiro() {
    const importeInput = document.getElementById('importeRetiro');
    if (!importeInput) return false;

    const importe = parseFloat(importeInput.value);
    const efectivoDisponible = (typeof TPV_CONTEXT !== 'undefined') ? TPV_CONTEXT.efectivoActualCaja : 0;

    if (isNaN(importe) || importe <= 0) {
        alert(t('cashier.alert_valid_amount'));
        return false;
    }

    if (importe > efectivoDisponible) {
        alert(t('cashier.alert_insufficient_cash') + ': ' + efectivoDisponible.toFixed(2).replace('.', ',') + ' €');
        return false;
    }

    return true;
}

/**
 * Actualiza la UI de los métodos de pago en el modal de devolución
 * @param {HTMLInputElement} radio 
 */
function updateMethodUI(radio) {
    document.querySelectorAll('.method-chip').forEach(chip => {
        chip.style.border = '2px solid var(--border-main)';
        chip.style.color = 'var(--text-muted)';
        chip.style.fontWeight = '400';
        chip.classList.remove('active');
    });

    const chip = document.getElementById('chip-' + radio.value);
    if (chip) {
        chip.style.border = '2px solid var(--accent-danger)';
        chip.style.color = 'var(--accent-danger)';
        chip.style.fontWeight = '600';
        chip.classList.add('active');
    }
}

/**
 * Calcula el arqueo de caja en tiempo real
 */
function calcularArqueo() {
    let total = 0;

    // Calcular total de billetes
    document.querySelectorAll('.arqueo-billete').forEach(input => {
        const cantidad = parseInt(input.value) || 0;
        const denominacion = parseFloat(input.dataset.denominacion);
        total += cantidad * denominacion;
    });

    // Calcular total de monedas
    document.querySelectorAll('.arqueo-moneda').forEach(input => {
        const cantidad = parseInt(input.value) || 0;
        const denominacion = parseFloat(input.dataset.denominacion);
        total += cantidad * denominacion;
    });

    // Redondear a 2 decimales
    total = Math.round(total * 100) / 100;

    // Obtener el efectivo esperado
    const arqueoEsperado = document.getElementById('arqueoEsperado');
    if (!arqueoEsperado) return;

    const efectivoEsperadoStr = arqueoEsperado.textContent.replace('€', '').trim();
    const efectivoEsperado = parseFloat(efectivoEsperadoStr.replace(/\./g, '').replace(',', '.')) || 0;

    // Calcular diferencia
    const diferencia = Math.round((total - efectivoEsperado) * 100) / 100;

    // Actualizar displays
    const arqueoContado = document.getElementById('arqueoContado');
    if (arqueoContado) arqueoContado.textContent = total.toFixed(2).replace('.', ',') + ' €';

    const diffElement = document.getElementById('arqueoDiferencia');
    if (diffElement) {
        diffElement.textContent = (diferencia >= 0 ? '+' : '') + diferencia.toFixed(2).replace('.', ',') + ' €';

        // Cambiar color según la diferencia
        if (diferencia === 0) {
            diffElement.style.color = '#059669';
            diffElement.textContent = '✓ ' + t('cash_count.correct');
        } else if (diferencia > 0) {
            diffElement.style.color = '#2563eb';
            diffElement.textContent = '+' + diferencia.toFixed(2).replace('.', ',') + ' € (' + t('cash_count.surplus') + ')';
        } else {
            diffElement.style.color = '#dc2626';
            diffElement.textContent = diferencia.toFixed(2).replace('.', ',') + ' € (' + t('cash_count.shortage') + ')';
        }
    }

    // Guardar el total en campos ocultos si existen
    const arqueoTotalContado = document.getElementById('arqueoTotalContado');
    if (arqueoTotalContado) arqueoTotalContado.value = total;

    const arqueoDetalleConteo = document.getElementById('arqueoDetalleConteo');
    if (arqueoDetalleConteo) {
        const detalle = {};
        document.querySelectorAll('.arqueo-billete').forEach(input => {
            detalle['billete_' + input.dataset.denominacion] = parseInt(input.value) || 0;
        });
        document.querySelectorAll('.arqueo-moneda').forEach(input => {
            detalle['moneda_' + input.dataset.denominacion] = parseInt(input.value) || 0;
        });
        arqueoDetalleConteo.value = JSON.stringify(detalle);
    }
}

/**
 * Continúa del arqueo al resumen de caja
 */
function continuarArqueo() {
    // Calcular el total del arqueo
    let total = 0;
    document.querySelectorAll('.arqueo-billete').forEach(input => {
        total += (parseInt(input.value) || 0) * parseFloat(input.dataset.denominacion);
    });
    document.querySelectorAll('.arqueo-moneda').forEach(input => {
        total += (parseInt(input.value) || 0) * parseFloat(input.dataset.denominacion);
    });
    total = Math.round(total * 100) / 100;

    // Obtener el efectivo esperado
    const arqueoEsperado = document.getElementById('arqueoEsperado');
    if (!arqueoEsperado) return;

    const efectivoEsperadoStr = arqueoEsperado.textContent.replace('€', '').trim();
    const efectivoEsperado = parseFloat(efectivoEsperadoStr.replace(/\./g, '').replace(',', '.')) || 0;

    // Calcular diferencia
    const diferencia = Math.round((total - efectivoEsperado) * 100) / 100;

    // Crear detalle del conteo
    const detalle = {};
    document.querySelectorAll('.arqueo-billete').forEach(input => {
        detalle['billete_' + input.dataset.denominacion] = parseInt(input.value) || 0;
    });
    document.querySelectorAll('.arqueo-moneda').forEach(input => {
        detalle['moneda_' + input.dataset.denominacion] = parseInt(input.value) || 0;
    });

    // Actualizar los campos hidden del formulario de confirmación
    const arqueoTotalForm = document.getElementById('arqueoTotalContadoForm');
    const arqueoDetalleForm = document.getElementById('arqueoDetalleConteoForm');
    if (arqueoTotalForm) arqueoTotalForm.value = total.toFixed(2);
    if (arqueoDetalleForm) arqueoDetalleForm.value = JSON.stringify(detalle);

    // Actualizar la visualización del arqueo en el resumen
    const arqueoContadoResumen = document.getElementById('arqueoContadoResumen');
    const arqueoDiferenciaResumen = document.getElementById('arqueoDiferenciaResumen');
    if (arqueoContadoResumen) {
        arqueoContadoResumen.textContent = total.toFixed(2).replace('.', ',') + ' €';
    }
    if (arqueoDiferenciaResumen) {
        if (diferencia === 0) {
            arqueoDiferenciaResumen.textContent = '✓ ' + t('cash_count.correct');
            arqueoDiferenciaResumen.style.color = '#059669';
        } else if (diferencia > 0) {
            arqueoDiferenciaResumen.textContent = '+' + diferencia.toFixed(2).replace('.', ',') + ' € (' + t('cash_count.surplus') + ')';
            arqueoDiferenciaResumen.style.color = '#2563eb';
        } else {
            arqueoDiferenciaResumen.textContent = diferencia.toFixed(2).replace('.', ',') + ' € (' + t('cash_count.shortage') + ')';
            arqueoDiferenciaResumen.style.color = '#dc2626';
        }
    }

    // Copiar observaciones si existen
    const obs = document.getElementById('arqueoObservaciones');
    const obsHidden = document.getElementById('arqueoObservacionesHidden');
    if (obs && obsHidden) obsHidden.value = obs.value;

    // Cerrar arqueo y abrir resumen
    const arqueoModal = document.getElementById('arqueoModal');
    const previsualizacion = document.getElementById('cajaPrevisualizacion');
    if (arqueoModal) arqueoModal.style.display = 'none';
    if (previsualizacion) previsualizacion.style.display = 'flex';
}

/**
 * Actualiza el elemento .ticket-fecha con la fecha y hora actual
 */
function actualizarFechaHora() {
    const ahora = new Date();
    const dia = String(ahora.getDate()).padStart(2, '0');
    const mes = String(ahora.getMonth() + 1).padStart(2, '0');
    const anio = ahora.getFullYear();
    const horas = String(ahora.getHours()).padStart(2, '0');
    const minutos = String(ahora.getMinutes()).padStart(2, '0');
    const segundos = String(ahora.getSeconds()).padStart(2, '0');

    const fechaHora = `${dia}/${mes}/${anio} ${horas}:${minutos}:${segundos}`;
    const el = document.querySelector('.ticket-fecha');
    if (el) el.textContent = fechaHora;
}

/**
 * Cierra el modal de bienvenida y limpia el estado en el servidor.
 */
function cerrarModalBienvenida(idModal) {
    const modal = document.getElementById(idModal);
    if (modal) modal.style.display = 'none';

    fetch('api/caja.php?accion=limpiarInterrupcion')
        .then(response => response.json())
        .then(data => {
            if (data && !data.success) {
                console.error('Error al limpiar interrupción:', data.message);
            }
        })
        .catch(error => console.error('Error en fetch:', error));
}