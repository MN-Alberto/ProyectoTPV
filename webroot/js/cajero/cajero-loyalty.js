/**
 * Abre el modal para buscar un cliente registrado por DNI
 */
function abrirModalBuscarClienteRegistrado() {
    const dniBusqueda = document.getElementById('dniBusquedaCliente');
    const mensaje = document.getElementById('mensajeResultadoBusqueda');
    const modal = document.getElementById('modalBuscarClienteRegistrado');

    if (dniBusqueda) dniBusqueda.value = '';
    if (mensaje) {
        mensaje.textContent = '';
        mensaje.className = '';
    }
    if (modal) {
        modal.style.display = 'flex';
        if (dniBusqueda) dniBusqueda.focus();
    }
}

/**
 * Muestra el DNI y nombre del cliente identificado en la zona del ticket
 */
function mostrarDniEnTicket(dni) {
    const indicador = document.getElementById('indicadorClienteDni');
    const valorDni = document.getElementById('indicadorClienteDniValor');
    const valorNombre = document.getElementById('indicadorClienteNombre');
    const nombreInput = document.getElementById('clienteNombre');

    if (indicador && valorDni && dni) {
        valorDni.textContent = dni.toUpperCase();
        if (valorNombre && nombreInput) {
            valorNombre.textContent = nombreInput.value.toUpperCase();
            valorNombre.title = nombreInput.value;
        }
        indicador.style.display = 'flex';
    }
}

/**
 * Oculta el indicador de DNI del cliente en la zona del ticket
 */
function ocultarDniEnTicket() {
    const indicador = document.getElementById('indicadorClienteDni');
    const valor = document.getElementById('indicadorClienteDniValor');
    if (indicador) indicador.style.display = 'none';
    if (valor) valor.textContent = '';
}

/**
 * Desvincula el cliente actual de la venta, limpiando todos sus datos y puntos
 */
function desvincularCliente() {
    clienteIdentificadoEnModalPuntos = false;

    // Limpiar campos de datos del cliente
    const ids = ['clienteNif', 'clienteNombre', 'clienteDireccion', 'clienteObservaciones',
        'inputClienteNifFinal', 'inputClienteNombreFinal', 'inputClienteDireccionFinal',
        'inputObservacionesFinal', 'inputPuntosCanjeadosDni', 'inputPuntosCanjeadosCantidad',
        'dniPuntosCliente', 'dniBusquedaCliente', 'clientePuntos'];

    ids.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = (id === 'clientePuntos') ? '0' : '';
    });

    // Resetear flag de puntos
    const identificadorPuntos = document.getElementById('inputClienteIdentificadoPuntos');
    if (identificadorPuntos) identificadorPuntos.value = 'false';

    // Resetear variables globales
    puntosCanjeados = null;

    // Ocultar indicador en UI
    ocultarDniEnTicket();

    // Si el descuento actual era por puntos (ej: PUNTOS_1000), lo quitamos también
    if (descuento.cupon && descuento.cupon.startsWith('PUNTOS_')) {
        descuento = { tipo: 'ninguno', valor: 0, cupon: '' };
    }

    // Restaurar tarifas por defecto (Cliente) al desvincular
    restaurarTarifasCarritoPorDefecto();

    actualizarTicket();
}

function cerrarYLimpiarClientePuntos() {
    desvincularCliente();
    cerrarModal('modalPuntosCliente');
    const busqueda = document.getElementById('puntosClienteBusqueda');
    const info = document.getElementById('puntosClienteInfo');
    if (busqueda) busqueda.style.display = 'block';
    if (info) info.style.display = 'none';
}

/**
 * Solo acumula puntos sin aplicar descuento, y cierra el modal
 */
function acumularPuntosSolamente() {
    puntosCanjeados = null;
    clienteIdentificadoEnModalPuntos = true;

    const dniCliente = document.getElementById('dniPuntosCliente')?.value.trim() || '';
    mostrarDniEnTicket(dniCliente);

    cerrarModal('modalPuntosCliente');
    const busqueda = document.getElementById('puntosClienteBusqueda');
    const info = document.getElementById('puntosClienteInfo');
    if (busqueda) busqueda.style.display = 'block';
    if (info) info.style.display = 'none';

    // Actualizar tarifas del carrito al identificar cliente
    actualizarTarifasCarritoPorCliente();

    actualizarTicket();
}

/**
 * Calcula el descuento basado en los puntos a canjear
 */
function calcularDescuentoPuntos() {
    const inputPuntos = document.getElementById('puntosACanjeer');
    let puntos = inputPuntos ? parseInt(inputPuntos.value) || 0 : 0;
    const preview = document.getElementById('descuentoPuntosPreview');
    const puntosGanadosMsg = document.getElementById('puntosQueSeGanaran');
    const totalTicket = typeof obtenerTotalCalculado === 'function' ? obtenerTotalCalculado() : 0;

    let nuevoTotal = totalTicket;
    const esMultiploDeMil = puntos % 1000 === 0;
    const descVal = Math.floor(puntos / 1000) * 5;

    if (preview) {
        if (puntos >= 1000) {
            preview.textContent = t('cart.discount') + `: ${descVal.toFixed(2)}€ (${puntos.toLocaleString('es-ES')} ` + t('points.points_text') + ')';
            preview.style.color = '';
            nuevoTotal = Math.max(0, totalTicket - descVal);
        } else if (puntos > 0 && puntos < 1000) {
            preview.textContent = t('points.error_min_1000');
            preview.style.color = '#ef4444';
        } else if (puntos > 0 && !esMultiploDeMil) {
            preview.textContent = t('points.error_multiples_1000');
            preview.style.color = '#ef4444';
        } else {
            preview.textContent = '';
            preview.style.color = '';
        }
    }

    if (puntosGanadosMsg) {
        const puntosQueSeGanaran = Math.round(nuevoTotal * 10);
        puntosGanadosMsg.textContent = t('points.with_purchase_earn') + ` ${puntosQueSeGanaran.toLocaleString('es-ES')} ` + t('points.points_text') + ' (1€ = 10 ' + t('points.points_text') + ')';
    }
}

/**
 * Aplica el descuento de puntos a la venta actual
 */
function aplicarDescuentoPuntos() {
    const puntosInputVal = document.getElementById('puntosACanjeer')?.value || 0;
    const puntosInput = parseInt(puntosInputVal) || 0;
    const puntosRedondeados = Math.floor(puntosInput / 1000) * 1000;
    const dni = document.getElementById('dniPuntosCliente')?.value.trim() || '';
    const puntosDisponiblesStr = document.getElementById('puntosDisponiblesCliente')?.textContent.replace(/\./g, '') || '0';
    const puntosDisponibles = parseInt(puntosDisponiblesStr) || 0;

    if (!dni) {
        alert(t('points.error_no_dni_specified'));
        return;
    }

    if (puntosRedondeados < 1000) {
        alert(t('points.error_min_1000_alert'));
        return;
    }

    if (puntosRedondeados > puntosDisponibles) {
        alert(t('points.error_not_enough_points') + '. ' + t('points.you_have') + ` ${puntosDisponibles.toLocaleString('es-ES')} ` + t('points.points_text') + '.');
        return;
    }

    const totalTicket = typeof obtenerTotalCalculado === 'function' ? obtenerTotalCalculado() : 0;
    const maxDescuento = totalTicket * 0.30;
    const maxPuntos = Math.floor(maxDescuento / 5) * 1000;

    let puntosFinales = puntosRedondeados;
    if (puntosRedondeados > maxPuntos) {
        alert(t('points.alert_exceeded_max') + ` (30% ` + t('points.of_ticket') + ` = ${maxDescuento.toFixed(2)}€). ` + t('points.will_use') + ` ${maxPuntos.toLocaleString('es-ES')} ` + t('points.points_text') + '.');
        puntosFinales = maxPuntos;
    }

    const descuentoEuros = Math.floor(puntosFinales / 1000) * 5;

    if (totalTicket - descuentoEuros <= 0) {
        alert(t('points.error_discount_ticket_zero'));
        return;
    }

    descuento.tipo = 'fijo';
    descuento.valor = descuentoEuros;
    descuento.cupon = 'PUNTOS_' + puntosFinales;

    const puntosGanados = Math.round((totalTicket - descuentoEuros) * 10);

    // Actualizar tarifas del carrito al identificar cliente
    actualizarTarifasCarritoPorCliente();

    actualizarTicket();
    mostrarDniEnTicket(dni);

    cerrarModal('modalPuntosCliente');
    const busqueda = document.getElementById('puntosClienteBusqueda');
    const info = document.getElementById('puntosClienteInfo');
    if (busqueda) busqueda.style.display = 'block';
    if (info) info.style.display = 'none';

    clienteIdentificadoEnModalPuntos = true;
    puntosCanjeados = {
        dni: dni,
        puntos: puntosFinales,
        descuento: descuentoEuros
    };

    alert(t('points.alert_discount_applied') + ` ${descuentoEuros.toFixed(2)}€ (` + t('points.canjeados') + ` ${puntosFinales.toLocaleString('es-ES')} ` + t('points.points_text') + ')\n' +
        t('points.with_purchase_earn') + ` ${puntosGanados.toLocaleString('es-ES')} ` + t('points.points_text'));
}

/**
 * Recorre el carrito y cambia todos los productos que tengan la tarifa "Cliente" 
 * a la tarifa "Cliente Registrado" si existe.
 */
function actualizarTarifasCarritoPorCliente() {
    const tarifasPrefijadas = (typeof TPV_CONTEXT !== 'undefined') ? TPV_CONTEXT.tarifasPrefijadas : [];

    // Buscar la tarifa de cliente registrado de forma más flexible
    const tarifaRegistrado = tarifasPrefijadas.find(t =>
        (t.nombre && t.nombre.toLowerCase().trim() === 'cliente registrado') ||
        (t.requiere_cliente == 1 && parseFloat(t.descuento_porcentaje) === 0)
    );

    if (!tarifaRegistrado) {
        console.warn("TPV: No se encontró la tarifa 'Cliente Registrado' en TPV_CONTEXT.tarifasPrefijadas");
        return;
    }

    let huboCambios = false;
    const idTarifaRegistrado = tarifaRegistrado.id;

    carrito.forEach(item => {
        if (item.subtotalModificado) {
            return;
        }
        // Solo cambiamos si está en tarifa "Cliente" (ID 1 o nombre exacto)
        // O si ya es "Cliente Registrado" pero queremos forzar actualización (aunque esto último no es necesario)
        if (item.tarifaNombre === 'Cliente') {
            const descuento = parseFloat(tarifaRegistrado.descuento_porcentaje) || 0;
            const preciosTarifas = item.preciosTarifas || {};

            let nuevoPrecioBase;
            if (preciosTarifas[idTarifaRegistrado]) {
                nuevoPrecioBase = preciosTarifas[idTarifaRegistrado].precio;
            } else {
                nuevoPrecioBase = (item.precioBaseOriginal || item.precio) * (1 - (descuento / 100));
            }

            const pvpUnitario = roundTo(nuevoPrecioBase * (1 + (item.iva / 100)), item.decimales || 2);

            item.pvpUnitario = pvpUnitario;
            item.tarifaNombre = tarifaRegistrado.nombre;
            item.tarifaDescuento = descuento;
            huboCambios = true;
        }
    });

    if (huboCambios) {
        // También actualizar el selector de tarifa global si está en "Cliente"
        const selectTarifa = document.getElementById('tarifaVenta');
        if (selectTarifa) {
            const tarifaCliente = tarifasPrefijadas.find(t => t.nombre === 'Cliente');
            if (tarifaCliente && selectTarifa.value == tarifaCliente.id) {
                selectTarifa.value = idTarifaRegistrado;
            }
        }
        actualizarTicket();
    }
}

/**
 * Restaura todos los productos del carrito a la tarifa "Cliente" por defecto.
 */
function restaurarTarifasCarritoPorDefecto() {
    const tarifasPrefijadas = (typeof TPV_CONTEXT !== 'undefined') ? TPV_CONTEXT.tarifasPrefijadas : [];
    const tarifaCliente = tarifasPrefijadas.find(t => t.id == 1 || t.nombre === 'Cliente');

    if (!tarifaCliente) return;

    carrito.forEach(item => {
        if (item.subtotalModificado) {
            return;
        }
        const idTarifa = tarifaCliente.id;
        const descuento = parseFloat(tarifaCliente.descuento_porcentaje) || 0;
        const preciosTarifas = item.preciosTarifas || {};

        let nuevoPrecioBase;
        if (preciosTarifas[idTarifa]) {
            nuevoPrecioBase = preciosTarifas[idTarifa].precio;
        } else {
            nuevoPrecioBase = (item.precioBaseOriginal || item.precio) * (1 - (descuento / 100));
        }

        const pvpUnitario = roundTo(nuevoPrecioBase * (1 + (item.iva / 100)), item.decimales || 2);

        item.pvpUnitario = pvpUnitario;
        item.tarifaNombre = tarifaCliente.nombre;
        item.tarifaDescuento = descuento;
    });

    // Resetear el selector global de tarifas si existe
    const selectTarifa = document.getElementById('tarifaVenta');
    if (selectTarifa) {
        selectTarifa.value = tarifaCliente.id;
    }
}

/**
 * Sobrescribimos el cierre del modal para manejar la reversión de tarifa si se cancela
 */
function cerrarModalBuscarClienteRegistrado() {
    const modal = document.getElementById('modalBuscarClienteRegistrado');

    if (modal?.dataset.modo === 'puntos') {
        modal.dataset.modo = '';
        const h3 = modal.querySelector('h3');
        const subtitulo = modal.querySelector('.modal-subtitulo');
        if (h3) h3.textContent = t('points.registered_client');
        if (subtitulo) subtitulo.textContent = t('points.enter_client_dni');
        confirmarSinPuntos();
        return;
    }

    if (productoPendienteTarifa) {
        revertirTarifaCard(productoPendienteTarifa.card.dataset.id);
        productoPendienteTarifa = null;
    }
    cerrarModal('modalBuscarClienteRegistrado');
}

/**
 * Elimina el descuento aplicado por tarifa
 */
function eliminarDescuentoPorTarifa() {
    if (descuentoTarifa && descuentoTarifa.tipo !== 'ninguno') {
        descuentoTarifa = { tipo: 'ninguno', valor: 0, cupon: '' };
        actualizarTicket();
    }
}

/**
 * Abre el modal para añadir un nuevo cliente habitual
 */
function abrirModalClienteHabitual() {
    const dniEl = document.getElementById('clienteHabitualDni');
    const nombreEl = document.getElementById('clienteHabitualNombre');
    const apellidosEl = document.getElementById('clienteHabitualApellidos');
    const fechaEl = document.getElementById('clienteHabitualFecha');

    if (dniEl) dniEl.value = '';
    if (nombreEl) nombreEl.value = '';
    if (apellidosEl) apellidosEl.value = '';

    const now = new Date();
    const localDate = new Date(now.getTime() - (now.getTimezoneOffset() * 60000)).toISOString().slice(0, 16);
    if (fechaEl) fechaEl.value = localDate;

    const btnGuardar = document.getElementById('btnGuardarClienteHabitual');
    if (btnGuardar) btnGuardar.onclick = guardarClienteHabitual;

    const modal = document.getElementById('modalClienteHabitual');
    if (modal) {
        modal.style.display = 'flex';
        if (dniEl) dniEl.focus();
    }
}