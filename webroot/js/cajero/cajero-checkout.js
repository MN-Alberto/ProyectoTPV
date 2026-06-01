/**
 * Inicia el proceso de cobro. Si el método es efectivo, muestra el modal de cambio.
 * Si es tarjeta/bizum, va directamente al modal de tipo de documento.
 * Valida el límite legal de 1.000€ para pagos en efectivo.
 */
function intentarCobrar() {
    if (carrito.length === 0) return;
    const metodoPago = document.getElementById('metodoPago').value;

    if (metodoPago === 'efectivo') {
        // Verificar límite legal de efectivo (1.000€)
        const total = obtenerTotalCalculado();
        if (total > 1000) {
            alert(t('cart.alert_cash_limit_exceeded'));
            return;
        }
        // Mostrar modal para calcular el cambio
        mostrarModalCambio();
    } else if (metodoPago === 'mixto') {
        // Mostrar modal de pago mixto para distribuir entre métodos
        mostrarModalPagoMixto();
    } else {
        // Para tarjeta/bizum, ir directamente al tipo de documento
        if (typeof mostrarModalTipoDocumento === 'function') {
            mostrarModalTipoDocumento();
        }
    }
}

/**
 * Muestra el modal de cálculo de cambio para pago en efectivo.
 * Inicializa los valores y pone el foco en el input de dinero entregado.
 */
function mostrarModalCambio() {
    const total = obtenerTotalCalculado();
    const precTotal = obtenerDecimalesMaximosCarrito();
    const cambioTotalPagar = document.getElementById('cambioTotalPagar');
    if (cambioTotalPagar) cambioTotalPagar.textContent = total.toFixed(precTotal).replace('.', ',') + ' €';

    // Resetear campos del modal
    const inputEntregado = document.getElementById('inputDineroEntregado');
    if (inputEntregado) inputEntregado.value = '';

    const cambioDevolver = document.getElementById('cambioDevolver');
    if (cambioDevolver) {
        cambioDevolver.textContent = '0,00';
        cambioDevolver.style.color = 'var(--text-muted)';
    }

    const container = document.getElementById('cambioResultContainer');
    if (container) {
        container.style.background = 'var(--bg-main)';
        container.style.borderColor = 'transparent';
        container.style.transform = 'scale(1)';
    }

    const cambioError = document.getElementById('cambioError');
    if (cambioError) cambioError.style.display = 'none';

    // Mostrar modal y enfocar el input
    const modalCambio = document.getElementById('modalCambio');
    if (modalCambio) {
        modalCambio.style.display = 'flex';
        if (inputEntregado) setTimeout(() => inputEntregado.focus(), 100);
    }
}

/**
 * Establece el importe recibido igual al total de la venta actual (importe exacto).
 */
function fijarImporteExacto() {
    const total = obtenerTotalCalculado();
    const precTotal = obtenerDecimalesMaximosCarrito();
    const input = document.getElementById('inputDineroEntregado');
    if (input) {
        input.value = total.toFixed(precTotal);
        calcularCambio();
        // Foco al botón de continuar para agilizar
        setTimeout(() => {
            const btnContinuar = document.querySelector('#modalCambio .btn-exito');
            if (btnContinuar) btnContinuar.focus();
        }, 50);
    }
}

/**
 * Calcula en tiempo real el cambio a devolver según la cantidad entregada.
 * Muestra un mensaje de error si la cantidad es insuficiente.
 */
function calcularCambio() {
    const total = obtenerTotalCalculado();
    const precTotal = obtenerDecimalesMaximosCarrito();
    const inputEntregado = document.getElementById('inputDineroEntregado');
    const spanDevolver = document.getElementById('cambioDevolver');
    const errorMsg = document.getElementById('cambioError');
    const container = document.getElementById('cambioResultContainer');
    
    if (!inputEntregado || !spanDevolver || !errorMsg) return;

    const entregado = parseFloat(inputEntregado.value) || 0;
    const devolucion = entregado - total;
    const devolucionRedondeada = roundTo(devolucion, precTotal);

    if (devolucionRedondeada < 0 && entregado > 0) {
        // Cantidad insuficiente: mostrar error
        spanDevolver.textContent = '0,00';
        spanDevolver.style.color = 'var(--text-muted)';
        errorMsg.style.display = 'flex';
        if (container) {
            container.style.background = 'var(--bg-main)';
            container.style.borderColor = 'transparent';
            container.style.transform = 'scale(1)';
        }
    } else {
        // Cantidad suficiente o vacía: mostrar cambio
        errorMsg.style.display = 'none';
        if (entregado === 0) {
            spanDevolver.textContent = '0,00';
            spanDevolver.style.color = 'var(--text-muted)';
            if (container) {
                container.style.background = 'var(--bg-main)';
                container.style.borderColor = 'transparent';
                container.style.transform = 'scale(1)';
            }
        } else {
            spanDevolver.textContent = devolucionRedondeada.toFixed(precTotal).replace('.', ',');
            spanDevolver.style.color = 'var(--accent-success)';
            if (container) {
                container.style.background = 'var(--bg-accent-success)';
                container.style.borderColor = 'rgba(22, 163, 74, 0.2)';
                container.style.transform = 'scale(1.02)';
            }
        }
    }
}

/**
 * Valida que la cantidad entregada sea suficiente y avanza al modal de tipo de documento.
 */
function confirmarCambio() {
    const total = obtenerTotalCalculado();
    const inputEntregado = document.getElementById('inputDineroEntregado');
    if (!inputEntregado) return;

    const entregado = parseFloat(inputEntregado.value) || 0;

    // Validar que el entregado cubra el total (usando redondeo para evitar errores de precisión)
    if (Math.round(entregado * 100) < Math.round(total * 100)) {
        const cambioError = document.getElementById('cambioError');
        if (cambioError) cambioError.style.display = 'block';
        return;
    }

    // Cerrar modal de cambio y abrir el nuevo Centro de Finalización de Venta
    cerrarModal('modalCambio');
    if (typeof abrirModalFinalizarVenta === 'function') {
        abrirModalFinalizarVenta();
    }
}

/**
 * Inicializa y muestra el modal para distribuir el pago entre múltiples métodos.
 */
function mostrarModalPagoMixto() {
    const total = obtenerTotalCalculado();
    const precTotal = obtenerDecimalesMaximosCarrito();
    const mixtoTotalDistribuir = document.getElementById('mixtoTotalDistribuir');
    if (mixtoTotalDistribuir) mixtoTotalDistribuir.textContent = total.toFixed(precTotal).replace('.', ',') + ' €';

    // Resetear campos
    const inputs = ['mixtoEfectivo', 'mixtoTarjeta', 'mixtoBizum'];
    inputs.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = '';
    });

    const mixtoError = document.getElementById('mixtoError');
    if (mixtoError) mixtoError.style.display = 'none';
    const mixtoAvisoEfectivo = document.getElementById('mixtoAvisoEfectivo');
    if (mixtoAvisoEfectivo) mixtoAvisoEfectivo.style.display = 'none';

    // Resetear indicador restante
    const restanteValor = document.getElementById('mixtoRestanteValor');
    if (restanteValor) restanteValor.textContent = total.toFixed(precTotal).replace('.', ',') + ' €';

    const container = document.getElementById('mixtoRestanteContainer');
    const label = document.getElementById('mixtoRestanteLabel');
    const sub = document.getElementById('mixtoRestanteSub');

    if (container && label && sub && restanteValor) {
        container.style.background = 'var(--bg-accent-danger)';
        label.style.color = 'var(--accent-danger)';
        sub.style.color = 'var(--accent-danger)';
        restanteValor.style.color = 'var(--accent-danger)';
        label.textContent = t('cart.mixed_remaining_assign');
        sub.textContent = t('cart.mixed_distribute_full');
    }

    // Mostrar modal y enfocar primer campo
    const modalPagoMixto = document.getElementById('modalPagoMixto');
    if (modalPagoMixto) {
        modalPagoMixto.style.display = 'flex';
        const mixtoEfectivo = document.getElementById('mixtoEfectivo');
        if (mixtoEfectivo) setTimeout(() => mixtoEfectivo.focus(), 100);
    }
}

/**
 * Calcula en tiempo real cuánto queda por asignar y actualiza el indicador visual.
 */
function calcularRestanteMixto() {
    const total = obtenerTotalCalculado();
    const precTotal = obtenerDecimalesMaximosCarrito();
    const efectivo = parseFloat(document.getElementById('mixtoEfectivo')?.value) || 0;
    const tarjeta = parseFloat(document.getElementById('mixtoTarjeta')?.value) || 0;
    const bizum = parseFloat(document.getElementById('mixtoBizum')?.value) || 0;

    const asignado = roundTo(efectivo + tarjeta + bizum, precTotal);
    const restante = roundTo(total - asignado, precTotal);

    const restanteValor = document.getElementById('mixtoRestanteValor');
    const container = document.getElementById('mixtoRestanteContainer');
    const label = document.getElementById('mixtoRestanteLabel');
    const sub = document.getElementById('mixtoRestanteSub');
    const errorEl = document.getElementById('mixtoError');

    if (!restanteValor || !container || !label || !sub || !errorEl) return;

    // Aviso límite efectivo
    const avisoEfectivo = document.getElementById('mixtoAvisoEfectivo');
    if (avisoEfectivo) avisoEfectivo.style.display = (efectivo > 1000) ? 'block' : 'none';

    if (restante > 0.005) {
        // Falta por asignar
        container.style.background = 'var(--bg-accent-danger)';
        container.style.borderColor = 'rgba(220, 38, 38, 0.1)';
        container.style.transform = 'scale(1)';
        label.style.color = 'var(--accent-danger)';
        sub.style.color = 'var(--accent-danger)';
        restanteValor.style.color = 'var(--accent-danger)';
        label.textContent = t('mixed_modal.remaining') || 'RESTANTE';
        sub.textContent = t('mixed_modal.distribute_total') || 'Distribuye el total';
        restanteValor.textContent = restante.toFixed(precTotal).replace('.', ',');
        errorEl.style.display = 'none';
    } else if (restante < -0.005) {
        // Excedente (cambio)
        const cambio = Math.abs(restante);
        container.style.background = 'var(--bg-accent-success)';
        container.style.borderColor = 'rgba(22, 163, 74, 0.2)';
        container.style.transform = 'scale(1.02)';
        label.style.color = 'var(--accent-success)';
        sub.style.color = 'var(--accent-success)';
        restanteValor.style.color = 'var(--accent-success)';
        label.textContent = t('cash_modal.change') || 'CAMBIO';
        sub.textContent = t('cash_modal.for_client') || 'Para el cliente';
        restanteValor.textContent = cambio.toFixed(precTotal).replace('.', ',');
        errorEl.style.display = 'none';
    } else {
        // Exacto
        container.style.background = 'var(--bg-accent-success)';
        container.style.borderColor = 'rgba(22, 163, 74, 0.4)';
        container.style.transform = 'scale(1.05)';
        label.style.color = 'var(--accent-success)';
        sub.style.color = 'var(--accent-success)';
        restanteValor.style.color = 'var(--accent-success)';
        label.textContent = '✓ ' + (t('mixed_modal.confirm_distribution') || 'CUBIERTO');
        sub.textContent = 'DISTRIBUCIÓN CORRECTA';
        restanteValor.textContent = '0,00';
        errorEl.style.display = 'none';
    }

    // Gestión dinámica de botones de autocompletar
    const fillButtons = {
        'mixtoEfectivo': document.getElementById('btnFillMixtoEfectivo'),
        'mixtoTarjeta': document.getElementById('btnFillMixtoTarjeta'),
        'mixtoBizum': document.getElementById('btnFillMixtoBizum')
    };

    Object.keys(fillButtons).forEach(id => {
        const btn = fillButtons[id];
        const input = document.getElementById(id);
        if (btn && input) {
            const valInput = parseFloat(input.value) || 0;
            // Solo mostrar si hay restante real y el input está vacío (o es 0)
            if (restante > 0.005 && valInput < 0.005) {
                btn.style.display = 'block';
                btn.textContent = '+ ' + restante.toFixed(precTotal).replace('.', ',') + ' €';
            } else {
                btn.style.display = 'none';
            }
        }
    });
}

/**
 * Calcula el importe faltante para cubrir el total de la venta y lo asigna al input especificado.
 * @param {string} targetId ID del elemento input al que se asignará el restante.
 */
function fijarRestanteMixto(targetId) {
    const total = obtenerTotalCalculado();
    const precTotal = obtenerDecimalesMaximosCarrito();
    
    // IDs de los métodos de pago mixto
    const ids = ['mixtoEfectivo', 'mixtoTarjeta', 'mixtoBizum'];
    
    // Calcular cuánto se ha asignado ya en los OTROS inputs
    let asignadoEnOtros = 0;
    ids.forEach(id => {
        if (id !== targetId) {
            const val = parseFloat(document.getElementById(id)?.value) || 0;
            asignadoEnOtros += val;
        }
    });
    
    // El restante para este input es (Total - lo que hay en los otros)
    const restante = Math.max(0, roundTo(total - asignadoEnOtros, precTotal));
    
    const input = document.getElementById(targetId);
    if (input) {
        input.value = restante > 0 ? restante.toFixed(precTotal) : '';
        calcularRestanteMixto();
        
        // Focus para feedback visual
        input.focus();
        
        // Si el restante cubre exactamente lo que faltaba, dar foco al botón confirmar
        const sumaFinal = roundTo(asignadoEnOtros + restante, precTotal);
        if (Math.abs(sumaFinal - total) < 0.005) {
            setTimeout(() => {
                document.getElementById('btnConfirmarPagoMixto')?.focus();
            }, 100);
        }
    }
}

/**
 * Valida la distribución y avanza al modal de finalización de venta.
 */
function confirmarPagoMixto() {
    const total = obtenerTotalCalculado();
    const precTotal = obtenerDecimalesMaximosCarrito();
    const efectivo = parseFloat(document.getElementById('mixtoEfectivo')?.value) || 0;
    const tarjeta = parseFloat(document.getElementById('mixtoTarjeta')?.value) || 0;
    const bizum = parseFloat(document.getElementById('mixtoBizum')?.value) || 0;
    const errorEl = document.getElementById('mixtoError');

    if (!errorEl) return;

    const asignado = roundTo(efectivo + tarjeta + bizum, precTotal);

    // Validar que la suma cubra el total
    if (Math.round(asignado * 100) < Math.round(total * 100)) {
        const spanError = document.getElementById('mixtoErrorSpan');
        if (spanError) spanError.textContent = (t('cart.mixed_error_not_covered') || 'Falta cubrir') + ': ' + roundTo(total - asignado, precTotal).toFixed(precTotal).replace('.', ',') + ' €';
        errorEl.style.display = 'flex';
        return;
    }

    // Validar límite de efectivo
    if (efectivo > 1000) {
        const spanError = document.getElementById('mixtoErrorSpan');
        if (spanError) spanError.textContent = t('cart.mixed_error_cash_limit') || 'Límite efectivo superado';
        errorEl.style.display = 'flex';
        return;
    }

    // Validar que al menos 2 métodos tengan importe (sino no tiene sentido "mixto")
    const metodosUsados = [efectivo, tarjeta, bizum].filter(v => v > 0).length;
    if (metodosUsados < 2) {
        const spanError = document.getElementById('mixtoErrorSpan');
        if (spanError) spanError.textContent = t('cart.mixed_error_two_methods') || 'Usa al menos 2 métodos';
        errorEl.style.display = 'flex';
        return;
    }

    // Calcular cambio (solo posible si hay efectivo y el asignado > total)
    const cambio = roundTo(Math.max(0, asignado - total), precTotal);

    // Guardar desglose
    pagoMixtoDesglose = {
        efectivo: roundTo(efectivo, precTotal),
        tarjeta: roundTo(tarjeta, precTotal),
        bizum: roundTo(bizum, precTotal),
        cambio: cambio
    };

    // Cerrar modal mixto y abrir el modal de finalización
    cerrarModal('modalPagoMixto');
    if (typeof abrirModalFinalizarVenta === 'function') {
        abrirModalFinalizarVenta();
    }
}

/**
 * Muestra el modal para elegir entre Ticket o Factura.
 * Valida que haya productos en el carrito y que la caja esté abierta.
 * Si el total es >= 20€ y no hay cliente, pregunta por los puntos primero.
 */
function mostrarModalTipoDocumento() {
    if (carrito.length === 0) return;

    // Verificar que la caja esté abierta antes de permitir ventas
    if (!cajaAbierta) {
        alert(t('cart.alert_box_closed'));
        return;
    }

    // Verificar si el total es mayor a 20€ y no hay cliente registrado para preguntar por puntos
    const total = obtenerTotalCalculado();
    const clienteNifEl = document.getElementById('clienteNif');
    const clienteNif = clienteNifEl ? clienteNifEl.value.trim() : '';

    if (total >= 20 && !clienteNif) {
        // Mostrar modal de puntos antes del tipo de documento
        if (typeof mostrarModalPuntos === 'function') {
            mostrarModalPuntos();
        }
        return;
    }

    abrirModalFinalizarVenta();
}

/**
 * Cambia el idioma seleccionado para el ticket, actualiza la selección visual 
 * y regenera la vista previa en el nuevo idioma
 */
function cambiarIdiomaTicket(idioma) {
    // Actualizar variable global
    idiomaTicketSeleccionado = idioma;

    // Actualizar variable de traducciones si existe IDIOMAS_TICKET
    if (typeof IDIOMAS_TICKET !== 'undefined' && IDIOMAS_TICKET[idioma]) {
        LANG = IDIOMAS_TICKET[idioma];
    }

    // Actualizar estado visual de los botones
    document.querySelectorAll('.idioma-option-card').forEach(el => {
        el.classList.remove('active');
    });
    const selectedBtn = document.querySelector(`.idioma-option-card[data-idioma="${idioma}"]`);
    if (selectedBtn) selectedBtn.classList.add('active');

    // Actualizar campo oculto del formulario
    const inputIdiomaTicket = document.getElementById('inputIdiomaTicket');
    if (inputIdiomaTicket) inputIdiomaTicket.value = idioma;

    // ACTUALIZAR NOMBRES DE TODOS LOS PRODUCTOS EN EL CARRITO
    if (typeof carrito !== 'undefined' && carrito.length > 0) {
        carrito.forEach(item => {
            // Buscar el nombre correspondiente al idioma seleccionado
            const campoNombre = `nombre_${idioma}`;
            if (item[campoNombre] && item[campoNombre].trim() !== '') {
                item.nombre = item[campoNombre];
            } else {
                // Fallback: si no tiene traduccion usar nombre español, si tampoco nombre base
                if (item.nombre_es && item.nombre_es.trim() !== '') {
                    item.nombre = item.nombre_es;
                }
            }
        });

        // Volver a renderizar el carrito en el panel derecho
        actualizarTicket();
    }

    // Regenerar vista previa del ticket con el nuevo idioma
    renderizarVistaPreviaTicket();
}

/**
 * Inicializa y muestra el nuevo modal de finalización con vista previa.
 */
function abrirModalFinalizarVenta() {
    if (carrito.length === 0) return;

    // Resetear selecciones
    tipoDocumentoActual = 'ticket';
    metodoEntregaActual = 'imprimir';
    idiomaTicketSeleccionado = 'es';

    // Resetear selección de idioma a Español por defecto
    document.querySelectorAll('.idioma-option-card').forEach(el => {
        el.classList.remove('active');
    });
    const esBtn = document.querySelector('.idioma-option-card[data-idioma="es"]');
    if (esBtn) esBtn.classList.add('active');

    const inputIdiomaTicket = document.getElementById('inputIdiomaTicket');
    if (inputIdiomaTicket) inputIdiomaTicket.value = 'es';

    // Fetch de los próximos números para la vista previa
    fetch('api/ventas.php?accion=proximos_numeros')
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                proximosNumeros.ticket = data.proximo_ticket;
                proximosNumeros.factura = data.proximo_factura;
                renderizarVistaPreviaTicket();
            }
        })
        .catch(err => console.error('Error al obtener números correlativos:', err));

    // Actualizar UI de botones
    document.querySelectorAll('.checkout-option-card').forEach(c => c.classList.remove('active'));
    const optTicket = document.getElementById('optTicket');
    const optImprimir = document.getElementById('optImprimir');
    if (optTicket) optTicket.classList.add('active');
    if (optImprimir) optImprimir.classList.add('active');

    const emailContainerCheckout = document.getElementById('emailContainerCheckout');
    if (emailContainerCheckout) emailContainerCheckout.style.display = 'none';

    // Si no hay datos de cliente, asegurar que no se acumulen puntos
    const nifValRes = document.getElementById('clienteNif')?.value.trim() || '';
    const nomValRes = document.getElementById('clienteNombre')?.value.trim() || '';
    if (!nifValRes && !nomValRes) {
        clienteIdentificadoEnModalPuntos = false;
    }

    // Actualizar resumen de cliente
    actualizarResumenClienteCheckout();

    // Renderizar vista previa inicial
    renderizarVistaPreviaTicket();

    // Mostrar modal principal
    const modalFinalizarVenta = document.getElementById('modalFinalizarVenta');
    if (modalFinalizarVenta) modalFinalizarVenta.style.display = 'flex';
}

/**
 * Actualiza el pequeño recuadro de datos de cliente en el modal de checkout.
 */
function actualizarResumenClienteCheckout() {
    const nifEl = document.getElementById('clienteNif');
    const nombreEl = document.getElementById('clienteNombre');
    const textEl = document.getElementById('clientDataTextCheckout');
    const btnRemove = document.getElementById('btnRemoveClientCheckout');

    if (!textEl) return;

    const nif = nifEl ? nifEl.value.trim() : '';
    const nombre = nombreEl ? nombreEl.value.trim() : '';

    if (nif || nombre) {
        textEl.innerHTML = `<div style="color:var(--text-main); font-weight:600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${nombre || t('cart.no_name')}</div><div style="font-size:0.8rem; opacity: 0.7;">${nif || t('cart.no_nif')}</div>`;
        if (btnRemove) btnRemove.style.display = 'block';
    } else {
        textEl.textContent = t('cart.no_customer_assigned');
        if (btnRemove) btnRemove.style.display = 'none';
    }
}

/**
 * Borra los datos del receptor (cliente) y resetea puntos.
 */
function quitarClienteFinalizar() {
    // 1. Limpiar campos de datos del cliente
    const campos = ['clienteNif', 'clienteNombre', 'clienteDireccion', 'clienteNotas', 'inputEmailFinal'];
    campos.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = '';
    });

    // 2. Limpiar puntos si los había
    const pDni = document.getElementById('inputPuntosCanjeadosDni');
    const pCant = document.getElementById('inputPuntosCanjeadosCantidad');
    const pTicket = document.getElementById('inputPuntosCanjeadosTicket');
    
    if (pDni) pDni.value = '';
    if (pCant) pCant.value = '0';
    if (pTicket) pTicket.value = '0';
    
    clienteIdentificadoEnModalPuntos = false;

    // 3. Actualizar UI
    actualizarResumenClienteCheckout();
    
    // 4. Regenerar vista previa del ticket (para que desaparezcan los datos)
    renderizarVistaPreviaTicket();
}

/**
 * Abre el modal de datos de cliente y prepara el retorno al checkout al terminar.
 */
function abrirDatosClienteDesdeCheckout() {
    cerrarModal('modalFinalizarVenta');
    window.retornarAlCheckout = true; // Flag para volver aquí después
    if (typeof seleccionarDatosCliente === 'function') {
        seleccionarDatosCliente(tipoDocumentoActual);
    }
}

/**
 * Maneja el botón 'Atrás' en el modal de datos de cliente.
 */
function cerrarModalDatosClienteAtras() {
    cerrarModal('modalDatosCliente');
    if (window.retornarAlCheckout) {
        window.retornarAlCheckout = false;
        const modalFinalizarVenta = document.getElementById('modalFinalizarVenta');
        if (modalFinalizarVenta) modalFinalizarVenta.style.display = 'flex';
    } else {
        // Flujo normal previo
        const modalTipoDoc = document.getElementById('modalTipoDoc');
        if (modalTipoDoc) modalTipoDoc.style.display = 'flex';
    }
}

/**
 * Cambia entre 'ticket' y 'factura' en el modo checkout.
 */
function cambiarTipoDocumentoCheckout(tipo) {
    tipoDocumentoActual = tipo;

    // Actualizar botones
    const optTicket = document.getElementById('optTicket');
    const optFactura = document.getElementById('optFactura');
    if (optTicket) optTicket.classList.toggle('active', tipo === 'ticket');
    if (optFactura) optFactura.classList.toggle('active', tipo === 'factura');

    // Actualizar la vista previa
    renderizarVistaPreviaTicket();
}

/**
 * Cambia entre 'imprimir' y 'email'.
 */
function cambiarMetodoEntregaCheckout(metodo) {
    metodoEntregaActual = metodo;

    // Actualizar botones
    const optImprimir = document.getElementById('optImprimir');
    const optEmail = document.getElementById('optEmail');
    if (optImprimir) optImprimir.classList.toggle('active', metodo === 'imprimir');
    if (optEmail) optEmail.classList.toggle('active', metodo === 'email');

    // Mostrar/ocultar contenedores específicos
    const emailContainerCheckout = document.getElementById('emailContainerCheckout');
    if (emailContainerCheckout) emailContainerCheckout.style.display = (metodo === 'email') ? 'block' : 'none';

    if (metodo === 'email') {
        const emailCheckout = document.getElementById('emailCheckout');
        if (emailCheckout) emailCheckout.focus();
    }
}

/**
 * Crea un objeto con la estructura de 'ultimaVenta' a partir de los datos actuales del carrito
 * y el formulario de cliente para poder previsualizar el documento fielmente.
 */
function construirObjetoVentaTemporal(tipoDoc) {
    const totalPVP = obtenerTotalCalculado();
    const nif = document.getElementById('clienteNif')?.value.trim() || '';
    const nombre = document.getElementById('clienteNombre')?.value.trim() || '';
    const direccion = document.getElementById('clienteDireccion')?.value.trim() || '';
    const observaciones = document.getElementById('clienteObservaciones')?.value.trim() || '';
    const mensajePersonalizado = document.getElementById('mensajePersonalizadoVenta')?.value.trim() || '';
    const idiomaTicket = idiomaTicketSeleccionado;

    // Clonar y preparar líneas de carrito
    const lineas = carrito.map(item => {
        // Obtener nombre en el idioma seleccionado actualmente
        const campoNombre = `nombre_${idiomaTicketSeleccionado}`;
        let nombreFinal = item.nombre;

        if (item[campoNombre] && item[campoNombre].trim() !== '') {
            nombreFinal = item[campoNombre];
        } else if (item.nombre_es && item.nombre_es.trim() !== '') {
            nombreFinal = item.nombre_es;
        }

        return {
            ...item,
            nombre: nombreFinal,
            precio: parseFloat(item.precio),
            pvpUnitario: parseFloat(item.pvpUnitario),
            cantidad: parseFloat(item.cantidad),
            iva: (item.iva !== undefined && item.iva !== null && item.iva !== "") ? parseInt(item.iva) : 21
        };
    });

    const metodoPagoActual = document.getElementById('metodoPago')?.value || 'efectivo';
    let entregadoVal = totalPVP;
    let cambioVal = 0;

    if (metodoPagoActual === 'efectivo') {
        const inputVal = parseFloat(document.getElementById('inputDineroEntregado')?.value);
        if (!isNaN(inputVal) && inputVal >= totalPVP) {
            entregadoVal = inputVal;
            cambioVal = inputVal - totalPVP;
        }
    }

    return {
        id: proximosNumeros[tipoDoc],
        numero: proximosNumeros[tipoDoc].replace(/\D/g, ''),
        serie: proximosNumeros[tipoDoc].replace(/\d/g, ''),
        fecha: new Date().toLocaleDateString('es-ES') + ' ' + new Date().toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' }),
        tipo: tipoDoc,
        idioma_ticket: idiomaTicket,
        metodoPago: metodoPagoActual,
        entregado: entregadoVal,
        cambio: cambioVal,
        carrito: lineas,
        total: totalPVP,
        clienteNif: nif,
        clienteNombre: nombre,
        clienteDir: direccion,
        clienteObs: observaciones,
        esClienteRegistrado: clienteIdentificadoEnModalPuntos,
        clientePuntos: parseInt(document.getElementById('clientePuntos')?.value) || 0,
        descuentoTipo: descuento.tipo,
        descuentoValor: descuento.valor,
        descuentoCupon: descuento.cupon,
        puntosGanados: (clienteIdentificadoEnModalPuntos && totalPVP >= 20) ? Math.round(totalPVP * 10) : 0,
        puntosCanjeados: (typeof puntosCanjeados !== 'undefined' && puntosCanjeados && puntosCanjeados.puntos > 0) ? puntosCanjeados : null,
        mensajePersonalizado: mensajePersonalizado,
        pagoMixtoDesglose: (metodoPagoActual === 'mixto') ? pagoMixtoDesglose : null,
        qrUrl: (TPV_CONTEXT.config.qrBaseUrl || 'https://prewww2.aeat.es/wlpl/TIKE-CONT/ValidarQR') + '?' +
            (new URLSearchParams({
                nif: TPV_CONTEXT.config.nif,
                numserie: (proximosNumeros[tipoDoc] || '').replace(/\s+/g, ''),
                fecha: (() => {
                    const d = new Date();
                    return String(d.getDate()).padStart(2, '0') + '-' +
                        String(d.getMonth() + 1).padStart(2, '0') + '-' +
                        d.getFullYear();
                })(),
                importe: totalPVP.toFixed(2)
            })).toString()
    };
}

/**
 * Limpia la sesion y vuelve al estado inicial.
 */
function cerrarExito() {
    window.location.href = 'index.php?v=cajero';
}

/**
 * Alterna entre vista ajustada y vista real (con scroll)
 */
function toggleZoomTicket() {
    ticketZoomed = !ticketZoomed;
    const previewContainer = document.getElementById('ticketPreviewContent');
    if (previewContainer) {
        ajustarEscalaTicket(previewContainer, false);
    }
}

/**
 * Calcula y aplica el factor de escala o habilita el scroll.
 * @param {HTMLElement} previewContainer - El contenedor del ticket.
 * @param {boolean} esNuevaMedicion - Indica si hay que recalcular si el ticket es grande.
 */
function ajustarEscalaTicket(previewContainer, esNuevaMedicion = false) {
    const viewport = document.querySelector('.ticket-preview-viewport');
    const btnZoom = document.getElementById('btnZoomTicket');
    const iconMinus = document.querySelector('.icon-minus');
    const iconPlusElements = document.querySelectorAll('.icon-plus');

    if (!viewport || !previewContainer || !btnZoom) return;

    // Si es una nueva medición, reseteamos estilos para medir altura real al 100%
    if (esNuevaMedicion) {
        viewport.classList.remove('is-zoomed');
        previewContainer.style.transform = 'none';
        // Forzamos un pequeño reflow si fuese necesario, aunque offsetHeight ya lo hace
        const viewportHeight = viewport.clientHeight - 32;
        const ticketFullHeight = previewContainer.offsetHeight;
        ticketEsGrandeLocal = ticketFullHeight > viewportHeight;
    }

    // Si el ticket es grande para el viewport actual
    if (ticketEsGrandeLocal) {
        btnZoom.style.display = 'flex';

        if (ticketZoomed) {
            // MODO ZOOM: Tamaño real con scroll
            viewport.classList.add('is-zoomed');
            btnZoom.classList.add('active');
            if (iconMinus) iconMinus.style.display = 'block';
            iconPlusElements.forEach(el => el.style.display = 'none');
            previewContainer.style.transform = 'none'; // Asegurar tamaño real
        } else {
            // MODO AJUSTADO: Escalado para que quepa totalmente
            viewport.classList.remove('is-zoomed');
            btnZoom.classList.remove('active');
            if (iconMinus) iconMinus.style.display = 'none';
            iconPlusElements.forEach(el => el.style.display = 'block');

            // Recalculamos escala basada en altura real
            const viewportHeight = viewport.clientHeight - 32;
            const ticketFullHeight = previewContainer.offsetHeight;

            if (ticketFullHeight > 0) {
                const scale = viewportHeight / ticketFullHeight;
                previewContainer.style.transform = `scale(${scale})`;
                previewContainer.style.transformOrigin = 'top center';
            }
        }
    } else {
        // El ticket cabe perfectamente: ocultamos botón y reset de estados
        btnZoom.style.display = 'none';
        viewport.classList.remove('is-zoomed');
        previewContainer.style.transform = 'none';
        ticketZoomed = false;
    }
}

/**
 * Actualiza la previsualización del modal de cobro usando un iframe para exactitud 1:1.
 */
function renderizarVistaPreviaTicket() {
    const previewContainer = document.getElementById('ticketPreviewContent');
    const badge = document.getElementById('tipoDocBadgeCheckout');
    if (!previewContainer || !badge) return;

    const isFactura = (tipoDocumentoActual === 'factura');

    // Limpieza de estados y timers previos para evitar condiciones de carrera
    ticketZoomed = false;
    ticketEsGrandeLocal = false;
    if (timeoutEscalaTicket) {
        clearTimeout(timeoutEscalaTicket);
        timeoutEscalaTicket = null;
    }

    const datosMock = construirObjetoVentaTemporal(tipoDocumentoActual);
    if (typeof generarHTMLComprobante !== 'function') return;
    const fullHTML = generarHTMLComprobante(datosMock, idiomaTicketSeleccionado);

    previewContainer.className = 'paper-simulation ' + (isFactura ? 'tipo-factura' : 'tipo-ticket');
    badge.textContent = isFactura ? 'FACTURA A4' : t('cart.thermal_ticket');
    badge.style.background = isFactura ? 'var(--bg-accent)' : 'var(--bg-accent-success)';
    badge.style.color = isFactura ? 'var(--accent)' : 'var(--accent-success)';

    previewContainer.innerHTML = '';
    const iframe = document.createElement('iframe');
    iframe.style.width = '100%';
    iframe.style.height = '1px';
    iframe.style.border = 'none';
    iframe.style.background = 'white';
    iframe.scrolling = 'no';
    previewContainer.appendChild(iframe);

    const doc = iframe.contentWindow ? iframe.contentWindow.document : null;
    if (doc) {
        doc.open();
        doc.write(fullHTML);
        doc.close();
    }

    iframe.onload = function () {
        if (timeoutEscalaTicket) clearTimeout(timeoutEscalaTicket);

        timeoutEscalaTicket = setTimeout(() => {
            // Comprobación defensiva antes de acceder al iframe
            if (!iframe || !iframe.contentWindow || !iframe.contentWindow.document) return;

            const body = iframe.contentWindow.document.body;
            if (!body) return;

            iframe.style.height = body.scrollHeight + 'px';

            // Aplicamos el ajuste de escala/zoom con medición nueva
            ajustarEscalaTicket(previewContainer, true);
        }, 180);
    };

    const totalEl = document.getElementById('checkoutTotalAmount');
    if (totalEl) totalEl.textContent = datosMock.total.toFixed(2).replace('.', ',') + ' €';
}

/**
 * Realiza las comprobaciones finales y dispara el envío de la venta.
 */
function procesarVentaFinal() {
    const mensajePersonalizadoVenta = document.getElementById('mensajePersonalizadoVenta');
    const mensajePersonalizado = mensajePersonalizadoVenta ? mensajePersonalizadoVenta.value.trim() : '';
    const inputMensajePersonalizado = document.getElementById('inputMensajePersonalizado');
    if (inputMensajePersonalizado) inputMensajePersonalizado.value = mensajePersonalizado;

    const nifEl = document.getElementById('clienteNif');
    const nombreEl = document.getElementById('clienteNombre');
    const direccionEl = document.getElementById('clienteDireccion');

    const nif = nifEl ? nifEl.value.trim() : '';
    const nombre = nombreEl ? nombreEl.value.trim() : '';
    const direccion = direccionEl ? direccionEl.value.trim() : '';

    // 1. Validar Factura
    if (tipoDocumentoActual === 'factura') {
        if (!nif || !nombre || !direccion) {
            // Si faltan datos, redirigir al modal de datos del cliente
            // Marcamos flag para que al terminar vuelva al checkout
            window.retornarAlCheckout = true;
            cerrarModal('modalFinalizarVenta');
            if (typeof seleccionarDatosCliente === 'function') {
                seleccionarDatosCliente('factura');
            }
            return;
        }
    }

    // 2. Validar Email si está seleccionado
    if (metodoEntregaActual === 'email') {
        const emailCheckout = document.getElementById('emailCheckout');
        const email = emailCheckout ? emailCheckout.value.trim() : '';
        if (!email || !email.includes('@')) {
            alert(t('cart.alert_valid_email'));
            return;
        }
        // Sincronizar con el input global por si se usa después
        const inputEmailGlobal = document.getElementById('inputEmail');
        if (inputEmailGlobal) inputEmailGlobal.value = email;
    }

    // 3. Sincronizar preferencias en localStorage para persistir tras el reload
    const emailCheckout = document.getElementById('emailCheckout');
    localStorage.setItem('tpv_post_sale_action', JSON.stringify({
        imprimir: (metodoEntregaActual === 'imprimir'),
        email: (metodoEntregaActual === 'email'),
        emailDestino: emailCheckout ? emailCheckout.value.trim() : ''
    }));

    // 4. Proceder con el registro
    const observacionesEl = document.getElementById('clienteObservaciones');
    const observaciones = observacionesEl ? observacionesEl.value.trim() : '';

    cerrarModal('modalFinalizarVenta');
    confirmarVenta(tipoDocumentoActual, nif, nombre, direccion, observaciones, mensajePersonalizado);
}

/**
 * Buscar cliente por DNI en el checkout
 */
function buscarDatosCliente() {
    const buscarDniCliente = document.getElementById('buscarDniCliente');
    const dniBusqueda = buscarDniCliente ? buscarDniCliente.value.trim() : '';
    const msgEl = document.getElementById('mensajeBusquedaClienteDatos');

    if (!dniBusqueda) {
        if (msgEl) {
            msgEl.style.display = 'block';
            msgEl.style.color = '#ef4444';
            msgEl.textContent = t('cart.alert_valid_dni');
        }
        return;
    }

    if (msgEl) {
        msgEl.style.display = 'block';
        msgEl.style.color = '#3b82f6';
        msgEl.textContent = t('cart.searching');
    }

    fetch('api/clientes.php?dni=' + encodeURIComponent(dniBusqueda))
        .then(res => res.json())
        .then(data => {
            if (data && !data.error && data.length > 0) {
                const cliente = data.find(c => c.dni.toUpperCase() === dniBusqueda.toUpperCase()) || data[0];

                const fields = {
                    'clienteNif': cliente.dni,
                    'clienteNombre': (cliente.nombre + ' ' + (cliente.apellidos || '')).trim(),
                    'clienteDireccion': cliente.direccion || '',
                    'clientePuntos': cliente.puntos || 0
                };

                for (const [id, val] of Object.entries(fields)) {
                    const el = document.getElementById(id);
                    if (el) el.value = val;
                }

                if (msgEl) {
                    msgEl.style.color = '#10b981';
                    msgEl.textContent = t('cart.client_found_filled');
                }
                clienteIdentificadoEnModalPuntos = true;
                renderizarVistaPreviaTicket();
            } else {
                clienteIdentificadoEnModalPuntos = false;
                renderizarVistaPreviaTicket();
                if (msgEl) msgEl.style.display = 'none';
                if (confirm(t('cart.confirm_add_client'))) {
                    const clienteHabitualDni = document.getElementById('clienteHabitualDni');
                    if (clienteHabitualDni) clienteHabitualDni.value = dniBusqueda;
                    if (typeof abrirModalClienteHabitual === 'function') {
                        abrirModalClienteHabitual();
                    }
                }
            }
        })
        .catch(err => {
            console.error('Error buscando cliente:', err);
            clienteIdentificadoEnModalPuntos = false;
            renderizarVistaPreviaTicket();
            if (msgEl) {
                msgEl.style.display = 'block';
                msgEl.style.color = '#ef4444';
                msgEl.textContent = t('cart.error_searching_client');
            }
        });
}

/**
 * Configura y muestra el modal de datos del cliente según el tipo de documento.
 */
function seleccionarDatosCliente(tipo) {
    tipoDocumentoActual = tipo;
    cerrarModal('modalTipoDoc');

    // Limpiar errores previos
    const errorDatosCliente = document.getElementById('errorDatosCliente');
    if (errorDatosCliente) errorDatosCliente.style.display = 'none';

    // Obtener referencias a los elementos del formulario
    const divDir = document.getElementById('divDireccionCliente');
    const divObs = document.getElementById('divObservacionesCliente');
    const subTitulo = document.getElementById('subtituloDatosCliente');

    const reqs = ['reqNif', 'reqNombre', 'reqDir'];

    if (tipo === 'factura') {
        // Modo Factura: mostrar todos los campos y marcar obligatorios
        if (subTitulo) subTitulo.textContent = t('cart.complete_data_mandatory');
        if (divDir) divDir.style.display = 'block';
        if (divObs) divObs.style.display = 'block';
        reqs.forEach(id => {
            const el = document.getElementById(id);
            if (el) el.style.display = 'inline';
        });
    } else {
        // Modo Ticket: ocultar campos extra
        if (subTitulo) subTitulo.textContent = t('cart.complete_data_optional');
        if (divDir) divDir.style.display = 'none';
        if (divObs) divObs.style.display = 'none';
        reqs.forEach(id => {
            const el = document.getElementById(id);
            if (el) el.style.display = 'none';
        });
    }

    // Mostrar el modal de datos del cliente
    const modalDatosCliente = document.getElementById('modalDatosCliente');
    if (modalDatosCliente) modalDatosCliente.style.display = 'flex';
}

/**
 * Valida los datos del cliente (obligatorios en Factura) y confirma la venta.
 */
function validarYConfirmarVenta() {
    const nifEl = document.getElementById('clienteNif');
    const nombreEl = document.getElementById('clienteNombre');
    const direccionEl = document.getElementById('clienteDireccion');
    const observacionesEl = document.getElementById('clienteObservaciones');

    const nif = nifEl ? nifEl.value.trim() : '';
    const nombre = nombreEl ? nombreEl.value.trim() : '';
    const direccion = direccionEl ? direccionEl.value.trim() : '';
    const observaciones = observacionesEl ? observacionesEl.value.trim() : '';

    // En modo Factura, NIF, Nombre y Dirección son obligatorios
    if (tipoDocumentoActual === 'factura') {
        if (!nif || !nombre || !direccion) {
            const errorDatosCliente = document.getElementById('errorDatosCliente');
            if (errorDatosCliente) errorDatosCliente.style.display = 'block';
            return;
        }
    } else {
        // Modo Ticket: Si rellena uno, el otro es obligatorio
        if ((nif && !nombre) || (!nif && nombre)) {
            const errorDatosCliente = document.getElementById('errorDatosCliente');
            if (errorDatosCliente) errorDatosCliente.style.display = 'block';
            return;
        }
    }

    // Cerrar modal de datos del cliente y proceder con la venta o volver al checkout
    cerrarModal('modalDatosCliente');

    if (window.retornarAlCheckout) {
        window.retornarAlCheckout = false;
        // Actualizar resumen y vista previa antes de volver
        actualizarResumenClienteCheckout();
        renderizarVistaPreviaTicket();
        const modalFinalizarVenta = document.getElementById('modalFinalizarVenta');
        if (modalFinalizarVenta) modalFinalizarVenta.style.display = 'flex';
    } else {
        confirmarVenta(tipoDocumentoActual, nif, nombre, direccion, observaciones);
    }
}

/**
 * El cliente decide registrar su DNI para obtener puntos.
 */
function confirmarConPuntos() {
    cerrarModal('modalPuntos');
    // Abrir modal para buscar cliente registrado
    if (typeof abrirModalBuscarClienteRegistradoParaPuntos === 'function') {
        abrirModalBuscarClienteRegistradoParaPuntos();
    }
}

/**
 * El cliente decide no registrar su DNI para puntos.
 */
function confirmarSinPuntos() {
    cerrarModal('modalPuntos');
    // Mostrar nuevo modal de finalizar venta
    abrirModalFinalizarVenta();
}

/**
 * Abre el modal de búsqueda de cliente para acumular puntos.
 */
function abrirModalBuscarClienteRegistradoParaPuntos() {
    const dniBusquedaCliente = document.getElementById('dniBusquedaCliente');
    if (dniBusquedaCliente) dniBusquedaCliente.value = '';

    const mensajeResultadoBusqueda = document.getElementById('mensajeResultadoBusqueda');
    if (mensajeResultadoBusqueda) mensajeResultadoBusqueda.style.display = 'none';

    // Cambiamos el título para indicar que es para puntos
    const h3 = document.querySelector('#modalBuscarClienteRegistrado h3');
    if (h3) h3.textContent = t('cart.client_for_points');

    const modalSubtitulo = document.querySelector('#modalBuscarClienteRegistrado .modal-subtitulo');
    const puntosPosibles = document.getElementById('puntosPosibles');
    if (modalSubtitulo && puntosPosibles) {
        modalSubtitulo.textContent = t('cart.enter_dni_accumulate') + ' ' + puntosPosibles.textContent + ' ' + t('cart.points_text');
    }

    // Cambiamos el comportamiento del botón buscar
    const modalBuscarClienteRegistrado = document.getElementById('modalBuscarClienteRegistrado');
    if (modalBuscarClienteRegistrado) {
        modalBuscarClienteRegistrado.dataset.modo = 'puntos';
        modalBuscarClienteRegistrado.style.display = 'flex';
    }
}

/**
 * Rellena el formulario oculto con todos los datos de la venta y lo envía por POST.
 */
function confirmarVenta(tipoDocumento, nif, nombre, direccion, observaciones, mensajePersonalizado = '') {
    const total = obtenerTotalCalculado();
    const metodoPago = document.getElementById('metodoPago')?.value || 'efectivo';
    let entregado = total;
    let cambio = 0;

    // Si el pago es en efectivo, calcular entregado y cambio
    if (metodoPago === 'efectivo') {
        const inputVal = parseFloat(document.getElementById('inputDineroEntregado')?.value);
        if (!isNaN(inputVal) && inputVal >= total) {
            entregado = inputVal;
            cambio = inputVal - total;
        }
    } else if (metodoPago === 'mixto' && pagoMixtoDesglose) {
        if (pagoMixtoDesglose.efectivo > 0 && pagoMixtoDesglose.cambio >= 0) {
            entregado = pagoMixtoDesglose.efectivo + pagoMixtoDesglose.cambio;
            cambio = pagoMixtoDesglose.cambio;
        } else {
            entregado = total;
            cambio = 0;
        }
    }

    const precTotal = obtenerDecimalesMaximosCarrito();

    // Rellenar los campos ocultos del formulario
    const fields = {
        'inputCarrito': JSON.stringify(carrito),
        'inputMetodoPago': metodoPago,
        'inputTipoDocumento': tipoDocumento,
        'inputDineroEntregadoFinal': entregado.toFixed(precTotal),
        'inputCambioDevueltoFinal': cambio.toFixed(precTotal),
        'inputDesglosePago': (metodoPago === 'mixto' && pagoMixtoDesglose) ? JSON.stringify(pagoMixtoDesglose) : '',
        'inputClienteNifFinal': nif,
        'inputClienteNombreFinal': nombre,
        'inputClienteDireccionFinal': direccion,
        'inputObservacionesFinal': observaciones,
        'inputDescuentoTipo': descuento.tipo,
        'inputDescuentoValor': descuento.valor,
        'inputDescuentoCupon': descuento.cupon,
        'inputDescuentoTarifaTipo': 'ninguno',
        'inputDescuentoTarifaValor': 0,
        'inputDescuentoTarifaCupon': '',
        'inputDescuentoManualTipo': descuento.tipo,
        'inputDescuentoManualValor': descuento.valor,
        'inputDescuentoManualCupon': descuento.cupon,
        'inputMensajePersonalizado': mensajePersonalizado
    };

    for (const [id, val] of Object.entries(fields)) {
        const el = document.getElementById(id);
        if (el) el.value = val;
    }

    // Guardar puntos canjeados si existen
    if (typeof puntosCanjeados !== 'undefined' && puntosCanjeados && puntosCanjeados.dni && puntosCanjeados.puntos > 0) {
        const pDni = document.getElementById('inputPuntosCanjeadosDni');
        const pCant = document.getElementById('inputPuntosCanjeadosCantidad');
        if (pDni) pDni.value = puntosCanjeados.dni;
        if (pCant) pCant.value = puntosCanjeados.puntos;
    } else {
        const pDni = document.getElementById('inputPuntosCanjeadosDni');
        const pCant = document.getElementById('inputPuntosCanjeadosCantidad');
        if (pDni) pDni.value = '';
        if (pCant) pCant.value = 0;
    }

    // Estado del cliente identificado en modal puntos
    let clienteIdentificadoPuntos = false;
    if (typeof clienteIdentificadoEnModalPuntos !== 'undefined') {
        clienteIdentificadoPuntos = !!clienteIdentificadoEnModalPuntos;
        const inputCIP = document.getElementById('inputClienteIdentificadoPuntos');
        if (inputCIP) inputCIP.value = clienteIdentificadoPuntos ? 'true' : 'false';
    }

    // Calcular puntos ganados y balance final
    const puntosGanados = (clienteIdentificadoPuntos && total >= 20) ? Math.round(total * 10) : 0;
    const inputPuntosCanjeadosCantidad = document.getElementById('inputPuntosCanjeadosCantidad');
    const puntosCanjeadosVal = inputPuntosCanjeadosCantidad ? (parseInt(inputPuntosCanjeadosCantidad.value) || 0) : 0;
    const clientePuntos = document.getElementById('clientePuntos');
    const puntosOriginales = (clienteIdentificadoPuntos) ? (parseInt(clientePuntos?.value) || 0) : 0;
    const puntosBalanceFinal = (clienteIdentificadoPuntos) ? (puntosOriginales - puntosCanjeadosVal + puntosGanados) : 0;

    const inputPG = document.getElementById('inputPuntosGanados');
    const inputPB = document.getElementById('inputPuntosBalance');
    if (inputPG) inputPG.value = puntosGanados;
    if (inputPB) inputPB.value = puntosBalanceFinal;

    // Tarifa seleccionada
    const inputIdTarifa = document.getElementById('inputIdTarifa');
    const tarifaVenta = document.getElementById('tarifaVenta');
    if (inputIdTarifa && tarifaVenta) inputIdTarifa.value = tarifaVenta.value;

    // Enviar el formulario al servidor
    const formVenta = document.getElementById('formVenta');
    if (formVenta) formVenta.submit();

    // Resetear estados después de enviar
    if (typeof puntosCanjeados !== 'undefined') puntosCanjeados = null;
    if (typeof clienteIdentificadoEnModalPuntos !== 'undefined') clienteIdentificadoEnModalPuntos = false;
    descuento = { tipo: 'ninguno', valor: 0, cupon: '' };
}

/**
 * Genera un documento HTML formateado (ticket o factura) con los datos de la última venta
 * y lo envía a la impresora mediante un iframe oculto.
 */
function imprimirDocumento() {
    if (typeof ultimaVenta === 'undefined') return;

    // Generar el contenido HTML usando el idioma guardado en la venta o el seleccionado
    if (typeof generarHTMLComprobante !== 'function') return;
    const contenido = generarHTMLComprobante(ultimaVenta, ultimaVenta.idioma_ticket || idiomaTicketSeleccionado);

    // Crear un iframe oculto para imprimir sin afectar la página actual
    const iframe = document.createElement('iframe');
    iframe.style.position = 'absolute';
    iframe.style.top = '-10000px';
    document.body.appendChild(iframe);
    iframe.contentDocument.open();
    iframe.contentDocument.write(contenido);
    iframe.contentDocument.close();

    // Cuando el iframe cargue, ejecutar la impresión y luego eliminarlo
    iframe.onload = function () {
        iframe.contentWindow.print();
        setTimeout(() => {
            if (iframe.parentNode) iframe.remove();
        }, 1000);
    };
}

/**
* Muestra el formulario de envío por correo electrónico dentro del modal de venta exitosa.
*/
function mostrarFormEmail() {
    const formEmail = document.getElementById('formEmail');
    if (formEmail) formEmail.style.display = 'block';
    const inputEmail = document.getElementById('inputEmail');
    if (inputEmail) inputEmail.focus();
}

/**
* Envía los datos de la última venta por correo electrónico al cliente.
*/
function enviarPorCorreo() {
    if (typeof ultimaVenta === 'undefined') return;

    const inputEmail = document.getElementById('inputEmail');
    const email = inputEmail ? inputEmail.value.trim() : '';
    const statusEl = document.getElementById('emailStatus');

    if (!statusEl) return;

    // Validación básica del email
    if (!email || !email.includes('@')) {
        statusEl.textContent = t('cart.alert_valid_email');
        statusEl.className = 'email-status email-error';
        return;
    }

    // Generar el número de ticket con formato serieNumero
    const ventaIdNumero = (ultimaVenta.serie || 'T') + String(ultimaVenta.numero || ultimaVenta.id || '').padStart(5, '0');
    console.log('Enviando email con ventaId:', ventaIdNumero, 'serie:', ultimaVenta.serie, 'numero:', ultimaVenta.numero);

    // Mostrar estado "Enviando..."
    statusEl.textContent = t('cart.email_sending');
    statusEl.className = 'email-status email-enviando';

    // Petición AJAX al endpoint de envío de correo
    ultimaVenta.mensajePersonalizado = ultimaVenta.mensajePersonalizado || '';

    fetch('api/enviarCorreo.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            email: email,
            tipoDocumento: ultimaVenta.tipo,
            ventaId: ventaIdNumero,
            total: ultimaVenta.total,
            lineas: ultimaVenta.carrito,
            fecha: ultimaVenta.fecha,
            metodoPago: ultimaVenta.metodoPago,
            entregado: ultimaVenta.entregado,
            cambio: ultimaVenta.cambio,
            clienteNif: ultimaVenta.clienteNif,
            clienteNombre: ultimaVenta.clienteNombre,
            clienteDir: ultimaVenta.clienteDir,
            clienteObs: ultimaVenta.clienteObs,
            descuentoTipo: ultimaVenta.descuentoTipo,
            descuentoValor: ultimaVenta.descuentoValor,
            descuentoCupon: ultimaVenta.descuentoCupon,
            descuentoTarifaTipo: ultimaVenta.descuentoTarifaTipo,
            descuentoTarifaValor: ultimaVenta.descuentoTarifaValor,
            descuentoTarifaCupon: ultimaVenta.descuentoTarifaCupon,
            descuentoManualTipo: ultimaVenta.descuentoManualTipo,
            descuentoManualValor: ultimaVenta.descuentoManualValor,
            descuentoManualCupon: ultimaVenta.descuentoManualCupon,
            puntos_ganados: ultimaVenta.puntosGanados || 0,
            puntos_canjeados: ultimaVenta.puntosCanjeados ? ultimaVenta.puntosCanjeados.puntos : 0,
            puntos_balance: ultimaVenta.puntosBalance || 0,
            mensajePersonalizado: ultimaVenta.mensajePersonalizado || '',
            pagoMixtoDesglose: ultimaVenta.pagoMixtoDesglose || null,
            qrUrl: ultimaVenta.qrUrl || '',
            lang: ultimaVenta.idioma_ticket || 'es'
        })
    })
        .then(res => res.json())
        .then(data => {
            if (data.ok) {
                // Envío exitoso
                statusEl.textContent = '✓ ' + t('cart.email_sent_to') + ' ' + email;
                statusEl.className = 'email-status email-ok';
            } else {
                // Error del servidor
                statusEl.textContent = '✗ ' + (data.mensaje || t('cart.email_error_sending'));
                statusEl.className = 'email-status email-error';
            }
        })
        .catch(err => {
            // Error de conexión
            statusEl.textContent = '✗ ' + t('cart.email_error_connection');
            statusEl.className = 'email-status email-error';
        });
}

/**
* Muestra el formulario de envío por correo electrónico dentro del modal de devolución exitosa.
*/
function mostrarFormEmailDevolucion() {
    const formEmailDev = document.getElementById('formEmailDev');
    if (formEmailDev) formEmailDev.style.display = 'block';
    const inputEmailDev = document.getElementById('inputEmailDev');
    if (inputEmailDev) inputEmailDev.focus();
}

/**
* Envía los datos de la devolución por correo electrónico al cliente.
*/
function enviarPorCorreoDevolucion() {
    if (typeof ultimaDevolucion === 'undefined') return;

    const inputEmailDev = document.getElementById('inputEmailDev');
    const email = inputEmailDev ? inputEmailDev.value.trim() : '';
    const statusEl = document.getElementById('emailStatusDev');

    if (!statusEl) return;

    // Validación básica del email
    if (!email || !email.includes('@')) {
        statusEl.textContent = t('cart.alert_valid_email');
        statusEl.style.color = '#ef4444';
        return;
    }

    // Mostrar estado "Enviando..."
    statusEl.textContent = t('cart.email_sending');
    statusEl.style.color = '#3b82f6';

    // Petición AJAX al endpoint de envío de correo
    fetch('api/enviarCorreo.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            email: email,
            tipoDocumento: 'devolucion',
            ventaId: ultimaDevolucion.id || '',
            serie: ultimaDevolucion.serie || 'D',
            numero: ultimaDevolucion.numero || '',
            orig_serie: ultimaDevolucion.orig_serie || '',
            orig_numero: ultimaDevolucion.orig_numero || '',
            total: ultimaDevolucion.total,
            lineas: ultimaDevolucion.lineas,
            fecha: ultimaDevolucion.fecha,
            metodoPago: ultimaDevolucion.metodoPago,
            clienteObs: ultimaDevolucion.motivo, // pasamos el motivo como observaciones
            qrUrl: ultimaDevolucion.qrUrl || ''
        })
    })
        .then(res => res.json())
        .then(data => {
            if (data.ok) {
                // Envío exitoso
                statusEl.textContent = '✓ ' + t('cart.email_sent_to') + ' ' + email;
                statusEl.style.color = '#10b981';
            } else {
                // Error del servidor
                statusEl.textContent = '✗ ' + (data.mensaje || t('cart.email_error_sending'));
                statusEl.style.color = '#ef4444';
            }
        })
        .catch(err => {
            // Error de conexión
            statusEl.textContent = '✗ ' + t('cart.email_error_connection');
            statusEl.style.color = '#ef4444';
        });
}

/**
* Verifica si el total del carrito supera los 1.000€ con método de pago en efectivo.
*/
function verificarLimiteEfectivo() {
    const metodo = document.getElementById('metodoPago')?.value;
    const total = typeof obtenerTotalCalculado === 'function' ? obtenerTotalCalculado() : 0;
    const aviso = document.getElementById('avisoLimiteEfectivo');

    if (aviso) {
        if (metodo === 'efectivo' && total > 1000) {
            aviso.style.display = 'block';
        } else {
            aviso.style.display = 'none';
        }
    }
}