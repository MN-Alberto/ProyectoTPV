/**
 * posponerVenta()
 * Abre el selector de mesas para guardar la venta actual en sessionStorage.
 */
function posponerVenta() {
    if (carrito.length === 0) {
        alert(t('cart.alert_no_products_postpone'));
        return;
    }
    mostrarModalVentasPospuestas('posponer');
}

/**
 * guardarVentaEnMesa(mesaNumero)
 * Guarda la venta actual en sessionStorage asociada a una mesa concreta.
 */
function guardarVentaEnMesa(mesaNumero) {
    if (carrito.length === 0) {
        alert(t('cart.alert_no_products_postpone'));
        return;
    }

    const clienteDniInput = document.getElementById('clienteNif');
    const clienteDni = clienteDniInput ? clienteDniInput.value.trim() : '';

    let puntosGanados = 0;
    if (clienteDni !== '') {
        const totalTicket = obtenerTotalCalculado();
        puntosGanados = Math.round(totalTicket * 10);
    }

    const puntosCanjeadosData = (typeof puntosCanjeados !== 'undefined' && puntosCanjeados)
        ? { dni: puntosCanjeados.dni, puntos: puntosCanjeados.puntos }
        : null;

    const cNombreInput = document.getElementById('clienteNombre');
    const clienteNombre = cNombreInput ? cNombreInput.value.trim() : '';

    const ventaId = Date.now();
    const ventaPospuesta = {
        id: ventaId,
        mesa: mesaNumero,
        carrito: JSON.parse(JSON.stringify(carrito)),
        descuento: JSON.parse(JSON.stringify(descuento)),
        tarifa: document.getElementById('tarifaVenta')?.value || '',
        clienteDni: clienteDni,
        clienteNombre: clienteNombre,
        puntosCanjeados: puntosCanjeadosData,
        puntosGanados: puntosGanados,
        fecha: new Date().toLocaleString('es-ES')
    };

    let ventasPospuestas = [];
    const ventasJson = sessionStorage.getItem('ventasPospuestas');
    if (ventasJson) {
        try {
            ventasPospuestas = JSON.parse(ventasJson);
        } catch (e) {
            ventasPospuestas = [];
        }
    }

    // Limpieza defensiva en caso de que ya hubiese algo en esa mesa
    const existingIndex = ventasPospuestas.findIndex(v => v.mesa === mesaNumero);
    if (existingIndex !== -1) {
        ventasPospuestas.splice(existingIndex, 1);
    }

    ventasPospuestas.push(ventaPospuesta);
    sessionStorage.setItem('ventasPospuestas', JSON.stringify(ventasPospuestas));

    carrito = [];
    descuento = { tipo: 'ninguno', valor: 0, cupon: '' };
    puntosCanjeados = null;

    const clienteNifInput = document.getElementById('clienteNif');
    if (clienteNifInput) clienteNifInput.value = '';
    const clienteNombreInput = document.getElementById('clienteNombre');
    if (clienteNombreInput) clienteNombreInput.value = '';
    const indicador = document.getElementById('indicadorClienteDni');
    if (indicador) indicador.style.display = 'none';

    const tarifaCliente = tarifasPrefijadas.find(t => t.nombre === 'Cliente');
    const tarifaVenta = document.getElementById('tarifaVenta');
    if (tarifaCliente && tarifaVenta) {
        tarifaVenta.value = tarifaCliente.id;
    } else if (tarifasPrefijadas.length > 0 && tarifaVenta) {
        tarifaVenta.value = tarifasPrefijadas[0].id;
    }
    actualizarTicket();
    cerrarModalVentasPospuestas();

    alert('✅ Venta guardada en Cubo ' + mesaNumero + ' correctamente.');
    actualizarBotonesPospuestos();
}

/**
 * mostrarModalVentasPospuestas()
 * Muestra el plano de mesas en modo posponer o recuperar.
 */
function mostrarModalVentasPospuestas(modo = 'recuperar') {
    const ventasJson = sessionStorage.getItem('ventasPospuestas');
    let ventasPospuestas = [];

    if (ventasJson) {
        try {
            ventasPospuestas = JSON.parse(ventasJson);
        } catch (e) {
            ventasPospuestas = [];
        }
    }

    if (modo === 'recuperar' && ventasPospuestas.length === 0) {
        alert(t('cart.alert_no_postponed_recover'));
        return;
    }

    const isDark = document.body.classList.contains('dark-mode');
    const textColor = isDark ? '#e5e7eb' : '#1a1a2e';
    const borderColor = isDark ? '#374151' : '#e5e7eb';
    const subTextColor = isDark ? '#9ca3af' : '#6b7280';

    // Calcular el límite dinámico de cubos (mínimo 9, incrementos de 3 si todo ocupado)
    let limit = 9;
    let maxMesaIndex = 0;
    ventasPospuestas.forEach(v => {
        if (v.mesa > maxMesaIndex) {
            maxMesaIndex = v.mesa;
        }
    });
    if (maxMesaIndex > limit) {
        limit = Math.max(9, Math.ceil(maxMesaIndex / 3) * 3);
    }
    while (true) {
        let allOccupied = true;
        for (let i = 1; i <= limit; i++) {
            const occupied = ventasPospuestas.some(v => v.mesa === i);
            if (!occupied) {
                allOccupied = false;
                break;
            }
        }
        if (allOccupied) {
            limit += 3;
        } else {
            break;
        }
    }

    let mesasHtml = '';
    for (let i = 1; i <= limit; i++) {
        const venta = ventasPospuestas.find(v => v.mesa === i);
        if (venta) {
            const totalVenta = venta.carrito.reduce((sum, item) => sum + (item.pvpUnitario * item.cantidad), 0);
            const numProductos = venta.carrito.reduce((sum, item) => sum + item.cantidad, 0);
            const clienteLabel = venta.clienteNombre ? venta.clienteNombre : (venta.clienteDni ? venta.clienteDni : 'Sin Cliente');

            mesasHtml += `
                <div class="mesa-item ocupada ${modo === 'posponer' ? 'disabled-posponer' : ''}" 
                     onclick="${modo === 'recuperar' ? `recuperarVenta(${venta.id})` : ''}"
                     title="${modo === 'posponer' ? 'Cubo ocupado' : 'Haga clic para recuperar la venta'}">
                    
                    <button class="mesa-delete-btn" onclick="event.stopPropagation(); eliminarVentaPospuesta(${venta.id})" title="Eliminar venta de este cubo">
                        <i class="fas fa-trash-alt" style="font-size: 0.75rem;"></i>
                    </button>
                    
                    <div class="mesa-number">
                        <i class="fas fa-cube" style="font-size: 0.95rem; opacity: 0.7;"></i> Cubo ${i}
                    </div>
                    <div class="mesa-total">${totalVenta.toFixed(2)} €</div>
                    <div class="mesa-info" style="font-weight: 600; color: ${textColor};">${clienteLabel}</div>
                    <div class="mesa-info" style="font-size: 0.7rem;">${numProductos} prod. | ${venta.fecha.split(' ')[1] || venta.fecha}</div>
                    <span class="mesa-status-badge ocupada">Ocupado</span>
                </div>
            `;
        } else {
            mesasHtml += `
                <div class="mesa-item libre ${modo === 'recuperar' ? 'disabled-recuperar' : ''}" 
                     onclick="${modo === 'posponer' ? `guardarVentaEnMesa(${i})` : ''}"
                     title="${modo === 'posponer' ? 'Haga clic para asignar la venta a este cubo' : 'Cubo vacío'}">
                    <div class="mesa-number"><i class="fas fa-cube" style="font-size: 0.95rem; opacity: 0.7;"></i> Cubo ${i}</div>
                    <div style="font-size: 1.8rem; margin: 5px 0; opacity: 0.3;">
                        <i class="fas fa-plus-circle"></i>
                    </div>
                    <span class="mesa-status-badge libre">Libre</span>
                </div>
            `;
        }
    }

    const modalTitle = modo === 'posponer' ? 'Asignar Venta a Cubo' : t('cart.postponed_sales_title');
    const modalSubtitle = modo === 'posponer' ? 'Selecciona un cubo libre para guardar la venta actual' : 'Selecciona un cubo ocupado para recuperar su venta';

    const modalHtml = `
        <div id="modalVentasPospuestas" class="modal-overlay" data-modo="${modo}" style="display: flex; backdrop-filter: blur(8px); background: rgba(0,0,0,0.4); z-index: 10000; transition: all 0.3s ease;">
            <div class="modal-content glass-modal" style="padding: 0 !important; background: ${isDark ? 'rgba(31, 41, 55, 0.98)' : 'rgba(255, 255, 255, 0.98)'}; border-radius: 24px; border: none; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); max-width: 680px; width: 95%; max-height: 90vh; overflow: hidden; animation: modalFadeIn 0.3s ease-out; display: flex; flex-direction: column;">
                
                <!-- Cabecera Premium Gradiente -->
                <div style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); padding: 30px 24px; text-align: center; position: relative; overflow: hidden;">
                    <!-- Botón Cerrar Flotante -->
                    <button onclick="cerrarModalVentasPospuestas()" 
                        style="position: absolute; top: 20px; right: 20px; background: rgba(255,255,255,0.2); border: none; width: 32px; height: 32px; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white; cursor: pointer; transition: all 0.2s; backdrop-filter: blur(4px);">
                        <i class="fas fa-times"></i>
                    </button>
                    
                    <h3 style="margin: 0; font-size: 1.6rem; font-weight: 800; color: white; letter-spacing: -0.02em;">
                        ${modalTitle}
                    </h3>
                    <p style="margin: 6px 0 0; color: white; opacity: 0.9; font-size: 0.95rem; font-weight: 500;">
                        ${modalSubtitle}
                    </p>
                </div>

                <div style="padding: 24px; overflow-y: auto; flex: 1; background: ${isDark ? 'transparent' : '#f8fafc'};">
                    <div class="mesas-grid">
                        ${mesasHtml}
                    </div>
                </div>

                <!-- Footer modal con botón de vaciar todo -->
                ${ventasPospuestas.length > 0 ? `
                <div style="padding: 16px 24px; border-top: 1px solid ${borderColor}; display: flex; justify-content: flex-end; background: ${isDark ? 'rgba(31, 41, 55, 0.5)' : '#ffffff'}; border-bottom-left-radius: 24px; border-bottom-right-radius: 24px;">
                    <button onclick="vaciarTodasVentasPospuestas()" 
                        style="padding: 10px 20px; border-radius: 12px; font-weight: 700; font-size: 0.9rem; background: #ef4444; color: white; border: none; cursor: pointer; transition: all 0.2s; box-shadow: 0 4px 6px -1px rgba(239, 68, 68, 0.2); display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-trash-alt"></i> Vaciar Todos los Cubos
                    </button>
                </div>
                ` : ''}
            </div>
        </div>
    `;

    document.body.insertAdjacentHTML('beforeend', modalHtml);
}

function cerrarModalVentasPospuestas() {
    const modal = document.getElementById('modalVentasPospuestas');
    if (modal) modal.remove();
}

function vaciarTodasVentasPospuestas() {
    if (!confirm('⚠️ ¿Seguro que deseas vaciar todos los cubos y eliminar todas las ventas pospuestas? Esta acción no se puede deshacer.')) return;

    sessionStorage.removeItem('ventasPospuestas');
    alert('✅ Todos los cubos han sido vaciados.');
    actualizarBotonesPospuestos();
    cerrarModalVentasPospuestas();
}

function eliminarVentaPospuesta(id) {
    if (!confirm(t('cart.confirm_delete_postponed') || '¿Seguro que deseas eliminar esta venta?')) return;

    const ventasJson = sessionStorage.getItem('ventasPospuestas');
    let ventasPospuestas = [];
    if (ventasJson) {
        try {
            ventasPospuestas = JSON.parse(ventasJson);
        } catch (e) {
            ventasPospuestas = [];
        }
    }

    const ventaIndex = ventasPospuestas.findIndex(v => v.id === id);
    if (ventaIndex === -1) {
        alert('No se encontró la venta pospuesta');
        return;
    }

    ventasPospuestas.splice(ventaIndex, 1);
    sessionStorage.setItem('ventasPospuestas', JSON.stringify(ventasPospuestas));

    alert('✅ ' + (t('cart.alert_postponed_deleted') || 'Venta eliminada correctamente'));
    actualizarBotonesPospuestos();

    const modal = document.getElementById('modalVentasPospuestas');
    const modo = modal ? modal.getAttribute('data-modo') : 'recuperar';

    cerrarModalVentasPospuestas();

    if (modo === 'posponer' || ventasPospuestas.length > 0) {
        mostrarModalVentasPospuestas(modo);
    }
}

/**
 * recuperarVenta(id)
 */
function recuperarVenta(id) {
    if (carrito.length > 0) {
        const confirmarDescarte = confirm('⚠️ El carrito actual no está vacío. Al recuperar este cubo se descartarán los productos actuales del carrito. ¿Deseas continuar?');
        if (!confirmarDescarte) {
            return;
        }
    }

    const ventasJson = sessionStorage.getItem('ventasPospuestas');
    let ventasPospuestas = [];
    if (ventasJson) {
        try {
            ventasPospuestas = JSON.parse(ventasJson);
        } catch (e) {
            alert(t('cart.alert_error_recovering'));
            return;
        }
    }

    const ventaIndex = ventasPospuestas.findIndex(v => v.id === id);
    if (ventaIndex === -1) {
        alert('No se encontró la venta pospuesta');
        return;
    }

    const ventaPospuesta = ventasPospuestas[ventaIndex];
    carrito = ventaPospuesta.carrito;
    descuento = ventaPospuesta.descuento || { tipo: 'ninguno', valor: 0, cupon: '' };

    const tarifaVenta = document.getElementById('tarifaVenta');
    if (ventaPospuesta.tarifa && tarifaVenta) {
        tarifaVenta.value = ventaPospuesta.tarifa;
    } else if (tarifaVenta) {
        const tarifaCliente = tarifasPrefijadas.find(t => t.nombre === 'Cliente');
        if (tarifaCliente) {
            tarifaVenta.value = tarifaCliente.id;
        } else if (tarifasPrefijadas.length > 0) {
            tarifaVenta.value = tarifasPrefijadas[0].id;
        }
    }

    const cNifInput = document.getElementById('clienteNif');
    const cNomInput = document.getElementById('clienteNombre');
    const indicador = document.getElementById('indicadorClienteDni');

    if (cNifInput) cNifInput.value = ventaPospuesta.clienteDni || '';
    if (cNomInput) cNomInput.value = ventaPospuesta.clienteNombre || '';
    if (indicador) indicador.style.display = ventaPospuesta.clienteDni ? 'block' : 'none';

    if (ventaPospuesta.clienteDni && typeof mostrarDniEnTicket === 'function') {
        mostrarDniEnTicket(ventaPospuesta.clienteDni);
    }

    if (ventaPospuesta.puntosCanjeados && ventaPospuesta.puntosCanjeados.dni && ventaPospuesta.puntosCanjeados.puntos > 0) {
        puntosCanjeados = {
            dni: ventaPospuesta.puntosCanjeados.dni,
            puntos: ventaPospuesta.puntosCanjeados.puntos
        };
    } else {
        puntosCanjeados = null;
    }

    actualizarTicket();
    cerrarModalVentasPospuestas();

    // Eliminar de las ventas pospuestas para liberar el cubo
    ventasPospuestas.splice(ventaIndex, 1);
    sessionStorage.setItem('ventasPospuestas', JSON.stringify(ventasPospuestas));

    alert('✅ Venta recuperada de Cubo ' + (ventaPospuesta.mesa || '') + '. El cubo ahora está libre.');
    actualizarBotonesPospuestos();
}

/**
 * actualizarBotonesPospuestos()
 */
function actualizarBotonesPospuestos() {
    const btnVerPospuestas = document.getElementById('btnVerPospuestas');
    const ventasJson = sessionStorage.getItem('ventasPospuestas');
    let ventasPospuestas = [];
    if (ventasJson) {
        try {
            ventasPospuestas = JSON.parse(ventasJson);
        } catch (e) {
            ventasPospuestas = [];
        }
    }

    const tieneVentas = ventasPospuestas && ventasPospuestas.length > 0;
    if (btnVerPospuestas) {
        if (tieneVentas) {
            btnVerPospuestas.disabled = false;
            btnVerPospuestas.style.opacity = '1';
            btnVerPospuestas.textContent = '📋 (' + ventasPospuestas.length + ')';
        } else {
            btnVerPospuestas.disabled = true;
            btnVerPospuestas.style.opacity = '0.5';
            btnVerPospuestas.textContent = '📋';
        }
    }
}
