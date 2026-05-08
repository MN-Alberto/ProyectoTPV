/**
 * admin.ventas.js
 * Gestión de ventas, devoluciones, retiros y sesiones de caja.
 * Depende de: admin.state.js, admin.utils.js, admin.pagination.js
 */

let totalPaginasVentas = 1;
let devolucionesListData = [];

// ═══════════════════════════════════════════════════════════════════════════════
// VENTAS
// ═══════════════════════════════════════════════════════════════════════════════

function getVentasTablaHeader(filtroFecha = 'todos', metodoPago = 'todos', tipoDocumento = 'todos', orden = 'fecha_desc', busqueda = '', totalVentas = 0) {
    const contador = `${totalVentas} Venta${totalVentas !== 1 ? 's' : ''}`;
    const opt = (val, actual, label) => `<option value="${val}" ${actual === val ? 'selected' : ''}>${label}</option>`;
    return `
        ${getPremiumHeaderHTML('fa-receipt', 'Historial de Ventas', 'Registro detallado de transacciones y tickets emitidos', 'linear-gradient(135deg, #3b82f6, #1d4ed8)')}
        <div class="admin-tabla-header products-header">
            <div class="header-filters-grid sales-optimized-grid">
                <div class="filter-main">
                    <div class="search-wrapper">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" id="busquedaVentaId" class="input-modern-search"
                            placeholder="ID o Ticket..."
                            oninput="buscarVentasPorId()" autocomplete="off"
                            value="${busqueda.replace(/"/g, '&quot;')}">
                    </div>
                </div>

                <div class="filter-secondary">
                    <div class="select-modern-wrapper">
                        <select id="ventasFiltroFecha" class="select-modern" onchange="aplicarFiltrosVentas()">
                            ${opt('todos', filtroFecha, 'Fecha: Todas')}${opt('hoy', filtroFecha, 'Hoy')}
                            ${opt('7dias', filtroFecha, 'Últimos 7 días')}${opt('30dias', filtroFecha, 'Último mes')}
                        </select>
                    </div>

                    <div class="select-modern-wrapper">
                        <select id="ventasFiltroMetodo" class="select-modern" onchange="aplicarFiltrosVentas()">
                            ${opt('todos', metodoPago, 'Pago: Todos')}${opt('efectivo', metodoPago, 'Efectivo')}
                            ${opt('tarjeta', metodoPago, 'Tarjeta')}${opt('bizum', metodoPago, 'Bizum')}
                            ${opt('mixto', metodoPago, 'Mixto')}
                        </select>
                    </div>

                    <div class="select-modern-wrapper">
                        <select id="ventasFiltroDocumento" class="select-modern" onchange="aplicarFiltrosVentas()">
                            ${opt('todos', tipoDocumento, 'Doc: Todos')}${opt('ticket', tipoDocumento, 'Tickets')}
                            ${opt('factura', tipoDocumento, 'Facturas')}
                        </select>
                    </div>

                    <div class="select-modern-wrapper">
                        <select id="ventasOrdenar" class="select-modern" onchange="aplicarFiltrosVentas()">
                            ${opt('fecha_desc', orden, 'Más recientes')}${opt('fecha_asc', orden, 'Más antiguos')}
                            ${opt('importe_desc', orden, 'Mayor importe')}${opt('importe_asc', orden, 'Menor importe')}
                        </select>
                    </div>
                </div>

                <div class="header-status-info">
                    <span id="totalVentasAviso" class="info-tag">${contador}</span>
                </div>

                <div class="header-actions">
                    <button class="btn-modern btn-danger btn-clear-all" onclick="limpiarTodasVentas()" title="Eliminar historial">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>
            </div>
        </div>
        <div class="admin-tabla-wrapper products-table-wrapper">
            <table class="admin-tabla">
                <thead><tr>
                    <th style="width: 85px;"># ID</th>
                    <th style="width: 150px;">Fecha / Hora</th>
                    <th>Vendedor</th>
                    <th style="width: 60px; text-align:center;">Art.</th>
                    <th style="width: 100px;">Tarifa</th>
                    <th style="width: 100px;">Documento</th>
                    <th style="width: 120px;">Pago</th>
                    <th style="width: 110px; text-align:right;">Total</th>
                    <th style="width: 120px; text-align:center;">Acciones</th>
                </tr></thead>
                <tbody>`;
}

function generarFilaVenta(venta) {
    const d = new Date(venta.fecha);
    const fecha = d.toLocaleString('es-ES', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' });
    
    const totalVal = parseFloat(venta.total);
    const totalStr = totalVal.toFixed(2).replace('.', ',');
    
    const pagoIcons = { efectivo: 'fa-money-bill-wave', tarjeta: 'fa-credit-card', bizum: 'fa-mobile-alt', mixto: 'fa-sync-alt' };
    const pagoLabels = { efectivo: 'Efectivo', tarjeta: 'Tarjeta', bizum: 'Bizum', mixto: 'Mixto' };
    const pagoIcon = pagoIcons[venta.forma_pago] || 'fa-receipt';
    const pagoLabel = pagoLabels[venta.forma_pago] || (venta.forma_pago || '—');
    
    const docIcons = { ticket: 'fa-receipt', factura: 'fa-file-invoice' };
    const docLabels = { ticket: 'Ticket', factura: 'Factura' };
    const docIcon = docIcons[venta.tipoDocumento] || 'fa-receipt';
    const docLabel = docLabels[venta.tipoDocumento] || (venta.tipoDocumento || 'Ticket');

    const esAnulada = venta.estado === 'anulada';
    const esRect = venta.es_rectificativa == 1;
    
    let statusPill = '';
    if (esAnulada) statusPill = '<span class="status-pill status-inactive" style="margin-top:4px;"><i class="fas fa-ban"></i> ANULADA</span>';
    else if (esRect) statusPill = `<span class="status-pill status-rect" style="margin-top:4px; background: rgba(139, 92, 246, 0.1); color: #8b5cf6;"><i class="fas fa-undo"></i> ${venta.tipo_factura_verifactu || 'RECT'}</span>`;

    return `
        <tr class="sale-row ${esAnulada ? 'row-disabled' : ''}">
            <td class="col-id">
                <div class="sale-id-group">
                    <span class="sale-serie">${venta.serie || 'T'}</span>
                    <span class="sale-num">${String(venta.numero || venta.id).padStart(5, '0').slice(-5)}</span>
                    ${statusPill}
                </div>
            </td>
            <td class="col-fecha">
                <div class="date-info">
                    <i class="far fa-clock"></i>
                    <span>${fecha}</span>
                </div>
            </td>
            <td class="col-usuario">
                <div class="user-profile-small" style="display: flex; align-items: center; gap: 8px;">
                    <div class="user-avatar-xs" style="width:24px; height:24px; background:#f1f5f9; color:#64748b; border-radius:6px; display:flex; align-items:center; justify-content:center; font-size:0.7rem; font-weight:800;">
                        ${(venta.usuario_nombre || 'U').charAt(0).toUpperCase()}
                    </div>
                    <span style="font-size: 0.85rem; color: #475569; font-weight: 600;">${venta.usuario_nombre || '—'}</span>
                </div>
            </td>
            <td class="col-productos" style="text-align:center;">
                <span class="count-tag" style="background:#f1f5f9; color:#475569; padding:2px 8px; border-radius:6px; font-weight:700; font-size:0.75rem;">
                    ${venta.cantidad_productos || 0}
                </span>
            </td>
            <td class="col-tarifa">
                <span class="tarifa-tag" style="font-size:0.75rem; font-weight:600; color:#6366f1; background:rgba(99, 102, 241, 0.08); padding:2px 8px; border-radius:6px;">
                    ${venta.tarifa_nombre || 'Cliente'}
                </span>
            </td>
            <td class="col-documento">
                <div class="doc-wrapper" style="display:flex; align-items:center; gap:8px; font-size:0.85rem; color:#64748b;">
                    <i class="fas ${docIcon}" style="font-size:0.8rem; opacity:0.7;"></i>
                    <span>${docLabel}</span>
                </div>
            </td>
            <td class="col-pago">
                <div class="pago-wrapper" style="display:flex; align-items:center; gap:8px; font-size:0.85rem; color:#64748b;">
                    <i class="fas ${pagoIcon}" style="font-size:0.8rem; opacity:0.7;"></i>
                    <span>${pagoLabel}</span>
                </div>
            </td>
            <td class="col-total" style="text-align:right;">
                <span class="total-amount ${totalVal < 0 ? 'negative' : 'positive'}" style="font-weight:800; font-size:1rem; color: ${totalVal < 0 ? '#ef4444' : '#10b981'};">
                    ${totalStr} €
                </span>
            </td>
            <td class="col-acciones">
                <div class="actions-group">
                    <button class="action-btn btn-view" onclick="event.stopPropagation(); verDetalleVenta(${venta.id})" title="Ver detalles">
                        <i class="fas fa-eye"></i></button>
                    <button class="action-btn btn-print" onclick="event.stopPropagation(); imprimirVentaDesdeHistorial(${venta.id})" title="Reimprimir">
                        <i class="fas fa-print"></i></button>
                    ${!esAnulada && !esRect ? `<button class="action-btn btn-delete" onclick="event.stopPropagation(); anularVentaAdmin('${venta.serie || 'T'}', ${venta.numero || venta.id})" title="Anular">
                        <i class="fas fa-ban"></i></button>` : ''}
                </div>
            </td>
        </tr>`;
}

function renderizarVentasPagina() {
    cargarVentasAdmin();
}

// Estado global
let hayMasVentas = false;

function cargarVentasAdmin(resetPagina = false) {
    if (seccionActual !== 'ventas') { adminTablaHeaderHTML = ''; seccionActual = 'ventas'; }
    const contenedor = document.getElementById('adminContenido');

    // ✅ Capturar filtros del DOM ANTES de mostrar el loader (el loader borra el DOM)
    const filtroFecha = document.getElementById('ventasFiltroFecha')?.value || 'todos';
    const metodoPago = document.getElementById('ventasFiltroMetodo')?.value || 'todos';
    const tipoDocumento = document.getElementById('ventasFiltroDocumento')?.value || 'todos';
    const orden = document.getElementById('ventasOrdenar')?.value || 'fecha_desc';
    const busqueda = document.getElementById('busquedaVentaId')?.value || '';

    if (resetPagina) paginaActualVentas = 1;

    // ✅ Mostrar loader mientras carga
    const esPrimeraVez = !contenedor.querySelector('.admin-tabla') || !adminTablaHeaderHTML;
    if (esPrimeraVez) {
        contenedor.innerHTML = `
            <div style="text-align:center;padding:80px 20px;">
                <i class="fas fa-spinner fa-spin" style="font-size:3rem;color:var(--color-primary);opacity:0.6;"></i>
                <p style="margin-top:20px;color:var(--text-muted);font-weight:500;">Cargando ventas...</p>
            </div>`;
    } else {
        const tbody = contenedor.querySelector('tbody');
        if (tbody) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="9" style="text-align:center;padding:40px;color:var(--text-muted);">
                        <i class="fas fa-spinner fa-spin"></i> Cargando datos...
                    </td>
                </tr>`;
        }
    }



    let url = 'api/ventas.php?todas=1';
    url += '&pagina=' + paginaActualVentas;
    url += '&porPagina=' + ventasPorPagina;

    if (filtroFecha !== 'todos') url += '&filtroFecha=' + filtroFecha;
    if (metodoPago !== 'todos') url += '&metodoPago=' + metodoPago;
    if (tipoDocumento !== 'todos') url += '&tipoDocumento=' + tipoDocumento;
    if (orden !== 'fecha_desc') url += '&orden=' + orden;
    if (busqueda.trim()) url += '&busqueda=' + encodeURIComponent(busqueda.trim());

    fetch(url)
        .then(r => { if (!r.ok) return r.json().then(e => { throw new Error(e.error || 'Error al cargar ventas'); }); return r.json(); })
        .then(data => {
            renderVentasAdmin(data, esPrimeraVez, filtroFecha, metodoPago, tipoDocumento, orden, busqueda);
        })
        .catch(err => { contenedor.innerHTML = '<p class="sin-productos">Error: ' + err.message + '</p>'; });
}

function renderVentasAdmin(respuesta, esPrimeraVez = true, filtroFecha = 'todos', metodoPago = 'todos', tipoDocumento = 'todos', orden = 'fecha_desc', busqueda = '') {
    const ventas = respuesta.ventas || [];
    const total = respuesta.total || 0;
    const totalPaginas = respuesta.totalPaginas || 1;
    paginaActualVentas = respuesta.pagina || 1;
    totalPaginasVentas = totalPaginas;

    const contenedor = document.getElementById('adminContenido');

    if (!total) {
        if (esPrimeraVez || !adminTablaHeaderHTML) {
            adminTablaHeaderHTML = getVentasTablaHeader(filtroFecha, metodoPago, tipoDocumento, orden, busqueda, 0);
            contenedor.innerHTML = adminTablaHeaderHTML +
                '<tr><td colspan="9" class="sin-productos">No hay ventas registradas.</td></tr></tbody></table></div>';
        } else {
            const tbody = contenedor.querySelector('tbody');
            if (tbody) tbody.innerHTML = '<tr><td colspan="9" class="sin-productos">No hay ventas registradas.</td></tr>';
            const c = document.getElementById('totalVentasAviso');
            if (c) c.textContent = '0 Ventas';
        }
        return;
    }

    if (busqueda !== '' || esPrimeraVez || !adminTablaHeaderHTML)
        adminTablaHeaderHTML = getVentasTablaHeader(filtroFecha, metodoPago, tipoDocumento, orden, busqueda, total);

    ventasData = ventas;

    ejecutarCuandoIdle(
        () => ventas.map(generarFilaVenta).join(''),
        filasHtml => {
            const html = adminTablaHeaderHTML + filasHtml + '</tbody></table></div>' +
                getPaginacionVentasHTML(totalPaginas);
            if (esPrimeraVez || busqueda !== '') {
                contenedor.innerHTML = html;
            } else {
                const tbody = contenedor.querySelector('tbody');
                if (tbody) {
                    tbody.innerHTML = filasHtml;
                    actualizarPaginacionDOM(contenedor, getPaginacionVentasHTML(totalPaginas));
                } else {
                    contenedor.innerHTML = html;
                }
            }
        }
    );

    const c = document.getElementById('totalVentasAviso');
    if (c) c.textContent = `${total.toLocaleString('es-ES')} Venta${total !== 1 ? 's' : ''}`;
}

function aplicarFiltrosVentas() {
    adminTablaHeaderHTML = '';
    cargarVentasAdmin(true);
}

function buscarVentasPorId() {
    clearTimeout(debounceTimerVentas);
    debounceTimerVentas = setTimeout(() => {
        cargarVentasAdmin(true);
    }, 300);
}

function limpiarTodasVentas() {
    if (!confirm('¿Estás seguro de que quieres eliminar TODAS las ventas?\n\nEsta acción no se puede deshacer.')) return;
    if (!confirm('¿SEGURO? Se eliminarán todas las ventas de forma permanente.')) return;
    fetch('api/ventas.php?limpiarVentas=1', { method: 'POST' })
        .then(r => r.json())
        .then(data => {
            if (data.error) alert('Error: ' + data.error);
            else { alert(data.message); adminTablaHeaderHTML = ''; cargarVentasAdmin(); }
        });
}

function verDetalleVenta(idVenta) {
    fetch(`api/ventas.php?detalleVenta=${idVenta}`)
        .then(r => r.json())
        .then(data => {
            if (data.error) { alert(data.error); return; }
            const { venta, lineas, descuentos = {} } = data;
            const isDark = document.body.classList.contains('dark-mode');
            
            const bg = isDark ? '#111827' : '#ffffff';
            const textMain = isDark ? '#f3f4f6' : '#1f2937';
            const textMuted = isDark ? '#9ca3af' : '#6b7280';
            const cardBg = isDark ? '#1f2937' : '#f9fafb';
            const borderColor = isDark ? '#374151' : '#e5e7eb';
            const primaryColor = '#6366f1';
            const successColor = '#10b981';

            const fecha = new Date(venta.fecha).toLocaleString('es-ES',
                { day: '2-digit', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' });
            const tipoDoc = venta.tipoDocumento === 'factura' ? '📄 Factura' : '🧾 Ticket';
            const ticketNum = `${venta.serie || 'T'}${String(venta.numero || venta.id).padStart(5, '0').slice(-5)}`;

            // Información de pago mejorada
            let pagoLabel = { efectivo: 'Efectivo', tarjeta: 'Tarjeta', bizum: 'Bizum', mixto: 'Mixto' }[venta.metodoPago] || venta.metodoPago;
            let pagoIcon = { efectivo: 'fa-money-bill-wave', tarjeta: 'fa-credit-card', bizum: 'fa-mobile-alt', mixto: 'fa-sync-alt' }[venta.metodoPago] || 'fa-receipt';
            
            let desgloseHtml = '';
            if (venta.metodoPago === 'mixto' && venta.desglose_pago) {
                try {
                    const desc = JSON.parse(venta.desglose_pago);
                    let parts = [];
                    if (desc.efectivo) parts.push(`Efectivo: ${parseFloat(desc.efectivo).toFixed(2)}€`);
                    if (desc.tarjeta) parts.push(`Tarjeta: ${parseFloat(desc.tarjeta).toFixed(2)}€`);
                    if (desc.bizum) parts.push(`Bizum: ${parseFloat(desc.bizum).toFixed(2)}€`);
                    desgloseHtml = `<div style="font-size:0.75rem;margin-top:4px;color:${textMuted};opacity:0.8;">${parts.join(' | ')}</div>`;
                } catch (e) {}
            }

            // Descuentos
            let discountSection = '';
            const manualCupon = descuentos.descuento_manual_cupon !== undefined ? descuentos.descuento_manual_cupon : descuentos.descuentoManualCupon;
            const manualTipo = descuentos.descuento_manual_tipo !== undefined ? descuentos.descuento_manual_tipo : descuentos.descuentoManualTipo;
            const manualValor = descuentos.descuento_manual_valor !== undefined ? descuentos.descuento_manual_valor : descuentos.descuentoManualValor;
            const tarifaCupon = descuentos.descuento_tarifa_cupon !== undefined ? descuentos.descuento_tarifa_cupon : descuentos.descuentoTarifaCupon;
            const tarifaValor = descuentos.descuento_tarifa_valor !== undefined ? descuentos.descuento_tarifa_valor : descuentos.descuentoTarifaValor;
            const ptsCanjeados = parseInt(descuentos.puntos_canjeados !== undefined ? descuentos.puntos_canjeados : (descuentos.puntosCanjeados !== undefined ? descuentos.puntosCanjeados : (manualCupon && typeof manualCupon === 'string' && (manualCupon.startsWith('PUNTOS_CANJEADOS:') || manualCupon.startsWith('PUNTOS_')) ? (manualCupon.includes(':') ? manualCupon.split(':')[1] : manualCupon.split('_')[1]) : 0))) || 0;

            const hasManualValor = manualValor !== undefined && manualValor !== null && manualValor !== '' && parseFloat(manualValor) !== 0;

            if (tarifaCupon === 'CLIENTE_REGISTRADO' || (manualCupon && manualCupon !== '') || ptsCanjeados > 0 || hasManualValor) {
                let items = [];
                if (tarifaCupon === 'CLIENTE_REGISTRADO') 
                    items.push(`<li><i class="fas fa-user-tag"></i> Cliente registrado: <strong>-${tarifaValor}%</strong></li>`);
                
                if (ptsCanjeados > 0) {
                    let val = parseFloat(manualValor || 0);
                    if (val === 0) val = (ptsCanjeados / 1000) * 5;
                    const valStr = val.toFixed(2);
                    items.push(`<li><i class="fas fa-coins" style="color:#f59e0b"></i> Puntos canjeados: <strong>${ptsCanjeados} pts (-${valStr}€)</strong></li>`);
                } else if (manualCupon && manualCupon !== '') {
                    const val = manualTipo === 'porcentaje' ? `-${manualValor}%` : `-${parseFloat(manualValor || 0).toFixed(2)}€`;
                    items.push(`<li><i class="fas fa-ticket-alt" style="color:#6366f1"></i> Cupón aplicado (${manualCupon}): <strong>${val}</strong></li>`);
                } else if (hasManualValor) {
                    const val = manualTipo === 'porcentaje' ? `-${manualValor}%` : `-${parseFloat(manualValor || 0).toFixed(2)}€`;
                    items.push(`<li><i class="fas fa-tag"></i> Descuento manual: <strong>${val}</strong></li>`);
                }
                discountSection = `
                    <div style="background:${isDark ? 'rgba(16,185,129,0.1)' : '#ecfdf5'}; border:1px solid ${isDark ? 'rgba(16,185,129,0.2)' : '#bbf7d0'}; border-radius:12px; padding:15px; margin-bottom:20px;">
                        <h5 style="margin:0 0 10px 0; color:${successColor}; font-size:0.9rem;"><i class="fas fa-percentage"></i> DESCUENTOS APLICADOS</h5>
                        <ul style="margin:0; padding:0; list-style:none; display:grid; grid-template-columns:1fr 1fr; gap:10px; font-size:0.85rem; color:${isDark ? '#34d399' : '#065f46'};">
                            ${items.join('')}
                        </ul>
                    </div>`;
            }

            let html = `
            <div class="modal-premium" style="max-width:750px; width:95%; border-radius:24px; overflow:hidden; background:${bg}; box-shadow:0 25px 50px -12px rgba(0,0,0,0.5);">
                <div class="modal-header-premium" style="background:linear-gradient(135deg, ${primaryColor} 0%, #4f46e5 100%); padding:35px 30px; position:relative;">
                    <div class="icon-container-discount" style="background:rgba(255,255,255,0.2); width:50px; height:50px; margin-bottom:15px;">
                        <i class="fas fa-shopping-bag" style="font-size:24px; color:white;"></i>
                    </div>
                    <h3 style="margin:0; font-size:1.8rem; letter-spacing:-0.02em;">Detalle de Venta ${ticketNum}</h3>
                    <p style="margin:10px 0 0 0; opacity:0.9; font-weight:500;"><i class="far fa-calendar-alt"></i> ${fecha}</p>
                    <button onclick="cerrarModal('modalVerVenta')" style="position:absolute; top:20px; right:20px; background:rgba(255,255,255,0.1); border:none; color:white; width:36px; height:36px; border-radius:50%; cursor:pointer; display:flex; align-items:center; justify-content:center; transition:0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.2)'" onmouseout="this.style.background='rgba(255,255,255,0.1)'">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div style="padding:30px;">
                    <!-- Grid de información rápida -->
                    <div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:15px; margin-bottom:25px;">
                        <div style="background:${cardBg}; padding:15px; border-radius:16px; border:1px solid ${borderColor};">
                            <span style="font-size:0.75rem; text-transform:uppercase; color:${textMuted}; font-weight:700; letter-spacing:0.05em;">Vendedor</span>
                            <div style="margin-top:5px; font-weight:600; color:${textMain}; font-size:1.1rem;"><i class="fas fa-user-circle" style="color:${primaryColor};"></i> ${venta.usuario_nombre || '—'}</div>
                        </div>
                        <div style="background:${cardBg}; padding:15px; border-radius:16px; border:1px solid ${borderColor};">
                            <span style="font-size:0.75rem; text-transform:uppercase; color:${textMuted}; font-weight:700; letter-spacing:0.05em;">Documento</span>
                            <div style="margin-top:5px; font-weight:600; color:${textMain}; font-size:1.1rem;">${tipoDoc}</div>
                        </div>
                        <div style="background:${cardBg}; padding:15px; border-radius:16px; border:1px solid ${borderColor};">
                            <span style="font-size:0.75rem; text-transform:uppercase; color:${textMuted}; font-weight:700; letter-spacing:0.05em;">Pago</span>
                            <div style="margin-top:5px; font-weight:600; color:${textMain}; font-size:1.1rem;"><i class="fas ${pagoIcon}" style="color:${primaryColor};"></i> ${pagoLabel}</div>
                            ${desgloseHtml}
                        </div>
                    </div>

                    ${discountSection}

                    <h4 style="margin:0 0 15px 0; font-size:1.1rem; color:${textMain}; display:flex; align-items:center; gap:10px;">
                        <i class="fas fa-list-ul" style="color:${primaryColor};"></i> Artículos vendidos
                    </h4>

                    <div style="max-height:300px; overflow-y:auto; border:1px solid ${borderColor}; border-radius:16px; background:${bg};">
                        <table style="width:100%; border-collapse:collapse; font-size:0.95rem;">
                            <thead style="position:sticky; top:0; background:${cardBg}; z-index:5;">
                                <tr>
                                    <th style="padding:15px; text-align:left; color:${textMuted}; font-weight:700; font-size:0.75rem; text-transform:uppercase; border-bottom:1px solid ${borderColor};">Producto</th>
                                    <th style="padding:15px; text-align:center; color:${textMuted}; font-weight:700; font-size:0.75rem; text-transform:uppercase; border-bottom:1px solid ${borderColor};">Cant.</th>
                                    <th style="padding:15px; text-align:right; color:${textMuted}; font-weight:700; font-size:0.75rem; text-transform:uppercase; border-bottom:1px solid ${borderColor};">Precio Un.</th>
                                    <th style="padding:15px; text-align:center; color:${textMuted}; font-weight:700; font-size:0.75rem; text-transform:uppercase; border-bottom:1px solid ${borderColor};">IVA</th>
                                    <th style="padding:15px; text-align:right; color:${textMuted}; font-weight:700; font-size:0.75rem; text-transform:uppercase; border-bottom:1px solid ${borderColor};">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>`;

            lineas.forEach((l, idx) => {
                const iva = l.iva || 21;
                const base = parseFloat(l.precioUnitario);
                const sub = l.cantidad * base * (1 + iva / 100);
                const rowBg = idx % 2 === 0 ? 'transparent' : (isDark ? 'rgba(255,255,255,0.02)' : 'rgba(0,0,0,0.01)');
                
                html += `
                    <tr style="background:${rowBg};">
                        <td style="padding:15px; border-bottom:1px solid ${borderColor}; color:${textMain}; font-weight:500;">${l.producto_nombre || 'Producto #' + l.idProducto}</td>
                        <td style="padding:15px; border-bottom:1px solid ${borderColor}; text-align:center; color:${textMain}; font-weight:600;">${l.cantidad}</td>
                        <td style="padding:15px; border-bottom:1px solid ${borderColor}; text-align:right; color:${textMain};">${base.toFixed(2).replace('.', ',')} €</td>
                        <td style="padding:15px; border-bottom:1px solid ${borderColor}; text-align:center; color:${textMuted};"><span style="background:${isDark ? '#374151' : '#f3f4f6'}; padding:2px 8px; border-radius:6px; font-size:0.8rem;">${iva}%</span></td>
                        <td style="padding:15px; border-bottom:1px solid ${borderColor}; text-align:right; font-weight:700; color:${textMain};">${sub.toFixed(2).replace('.', ',')} €</td>
                    </tr>`;
            });

            html += `
                            </tbody>
                        </table>
                    </div>

                    <!-- Footer / Total -->
                    <div style="margin-top:25px; padding:25px; background:linear-gradient(135deg, #059669 0%, #10b981 100%); border-radius:20px; display:flex; justify-content:space-between; align-items:center; color:white; box-shadow:0 10px 20px -5px rgba(16,185,129,0.3);">
                        <div style="text-align:left;">
                            <span style="font-size:0.85rem; opacity:0.9; text-transform:uppercase; font-weight:700; letter-spacing:0.05em;">Total de la operación</span>
                            <div style="font-size:2.2rem; font-weight:800; line-height:1;">${parseFloat(venta.total).toFixed(2).replace('.', ',')} <span style="font-size:1.5rem; font-weight:500;">€</span></div>
                        </div>
                        <div style="text-align:right;">
                             <button class="btn-exito" onclick="imprimirVentaDesdeHistorial(${venta.id})" style="background:white; color:#059669; border:none; padding:12px 20px; border-radius:12px; font-weight:700; box-shadow:0 4px 12px rgba(0,0,0,0.1); cursor:pointer; transition:0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                                <i class="fas fa-print"></i> Reimprimir Ticket
                             </button>
                        </div>
                    </div>

                    <div style="margin-top:25px; display:flex; justify-content:center;">
                        <button onclick="cerrarModal('modalVerVenta')" style="background:transparent; border:1px solid ${borderColor}; color:${textMuted}; padding:10px 30px; border-radius:12px; cursor:pointer; font-weight:600; transition:0.2s;" onmouseover="this.style.background='${isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)'}'; this.style.color='${textMain}'" onmouseout="this.style.background='transparent'; this.style.color='${textMuted}'">
                            Cerrar detalle
                        </button>
                    </div>
                </div>
            </div>`;

            let modal = document.getElementById('modalVerVenta');
            if (!modal) {
                modal = document.createElement('div');
                modal.id = 'modalVerVenta';
                modal.className = 'modal-overlay';
                modal.style.display = 'none';
                modal.style.backdropFilter = 'blur(4px)';
                modal.innerHTML = '<div class="modal-content" style="max-width:750px; padding:0; background:transparent; border:none; box-shadow:none;"></div>';
                document.body.appendChild(modal);
            }
            modal.querySelector('.modal-content').innerHTML = html;
            modal.style.display = 'flex';
        });
}


// ── FIN SECCIÓN ──────────────────────────────────────────────────────────────

function verDetalleDevolucion(id) {
    const container = document.getElementById('ticketDevolucionContainer');
    if (container) {
        container.innerHTML = `
            <div style="text-align: center; padding: 40px; color: #666;">
                <i class="fas fa-spinner fa-spin" style="font-size: 2rem; margin-bottom: 10px;"></i>
                <p>Cargando comprobante...</p>
            </div>`;
    }

    // Buscar la devolución en la lista local para obtener el idVenta (original)
    const devLocal = devolucionesListData.find(d => d.id == id);
    if (!devLocal) { alert('No se encontró la devolución en la lista'); return; }

    const idVentaOriginal = devLocal.idVenta;

    fetch(`api/devoluciones.php?detalleVenta=${idVentaOriginal}`)
        .then(r => r.json())
        .then(data => {
            if (!data || data.length === 0) { alert('No se encontraron detalles de la devolución'); return; }
            
            // Filtrar lote: aquellas que coincidan en fecha con la seleccionada (o todas si solo hay un lote)
            // En la API detalleVenta ya vienen agrupadas por el idVentaOriginal
            const lote = data.filter(d => d.fecha === devLocal.fecha);
            if (lote.length === 0) { alert('No se encontraron productos para esta fecha de devolución'); return; }

            const devInfo = lote[0]; // Info común (serie, numero, rect_serie, etc)
            window._ultimaDevAdmin = devLocal;

            const carrito = lote.map(linea => ({
                nombre: linea.producto_nombre || '—',
                cantidad: linea.cantidad,
                precio: parseFloat(linea.precioUnitario) || 0,
                iva: linea.iva || 21,
                importeTotal: parseFloat(linea.importeTotal) || 0
            }));

            const totalGeneral = carrito.reduce((sum, item) => sum + item.importeTotal, 0);

            // Mapeo correcto para generarHTMLComprobante
            const datosVenta = {
                id: devInfo.rect_numero || devInfo.id,
                serie: devInfo.rect_serie || 'D',
                numero: devInfo.rect_numero || devInfo.id,
                fecha: new Date(devInfo.fecha).toLocaleString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' }),
                tipo: 'ticket',
                es_rectificativa: true,
                id_original: devInfo.idVenta,
                serie_original: devInfo.orig_serie || 'T',
                numero_original: devInfo.orig_numero,
                total: -totalGeneral,
                metodoPago: devInfo.metodoPago,
                carrito: carrito,
                usuario_nombre: devLocal.usuario_nombre,
                qrUrl: devInfo.qrUrl
            };

            const html = generarHTMLComprobante(datosVenta, 'es');
            if (container) {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const bodyContent = doc.body.innerHTML;
                container.innerHTML = `<div class="ticket-preview-wrapper" style="transform: scale(0.95); transform-origin: top center;">${bodyContent}</div>`;
            }
            abrirModal('modalVerDevolucion');
        })
        .catch(err => {
            console.error(err);
            alert('Error al cargar detalles');
        });
}


// ── FIN ──────────────────────────────────────────────────────────────────────

/**
 * imprimirVentaDesdeHistorial(idVenta)
 * Recupera los detalles de una venta y la imprime usando el motor compartido.
 */
function imprimirVentaDesdeHistorial(idVenta) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Generando documento...',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });
    }

    fetch(`api/ventas.php?detalleVenta=${idVenta}`)
        .then(r => r.json())
        .then(data => {
            if (typeof Swal !== 'undefined') Swal.close();
            if (data.error) {
                if (typeof Swal !== 'undefined') Swal.fire('Error', data.error, 'error');
                else alert(data.error);
                return;
            }

            const { venta, lineas, descuentos = {} } = data;

            const carrito = lineas.map(l => ({
                id: l.idProducto,
                nombre: l.producto_nombre,
                nombre_es: l.nombre_es,
                nombre_en: l.nombre_en,
                nombre_fr: l.nombre_fr,
                nombre_de: l.nombre_de,
                nombre_ru: l.nombre_ru,
                cantidad: l.cantidad,
                precio: l.precioOriginal !== undefined ? l.precioOriginal : l.precioUnitario,
                iva: l.iva,
                importeTotal: l.subtotal,
                pvpUnitario: l.precioUnitario * (1 + (l.iva || 0) / 100)
            }));

            // QR para Verifactu
            const nifTpv = (window.TPV_CONFIG && window.TPV_CONFIG.nif) ? window.TPV_CONFIG.nif : '';

            // Construir numserie: SERIE + número padded (ej: T00001, F00003)
            const serie = venta.serie || (venta.tipoDocumento === 'factura' ? 'F' : 'T');
            // Usar numero de ventas_ids, o id de la venta si no existe numero
            // Intentamos buscar ID en diferentes claves por si acaso (id, ID, idVenta)
            const numeroReal = venta.numero || venta.id || venta.ID || venta.idVenta || idVenta;
            const numserie = (serie + String(numeroReal).padStart(5, '0')).replace(/\s+/g, '');

            const qrParams = new URLSearchParams({
                nif: nifTpv,
                numserie: numserie,
                fecha: (() => {
                    // ✅ FIX: Formato fecha MySQL con espacio no funciona en todos los navegadores, reemplazar por T
                    const fechaStr = venta.fecha.replace(' ', 'T');
                    const d = new Date(fechaStr);
                    return String(d.getDate()).padStart(2, '0') + '-' +
                        String(d.getMonth() + 1).padStart(2, '0') + '-' +
                        d.getFullYear();
                })(),
                importe: parseFloat(venta.total).toFixed(2)
            });

            // Formatear fecha en formato ISO 2026-04-27 08:20:18
            const d = new Date(venta.fecha);
            const fechaFormateada =
                d.getFullYear() + '-' +
                String(d.getMonth() + 1).padStart(2, '0') + '-' +
                String(d.getDate()).padStart(2, '0') + ' ' +
                String(d.getHours()).padStart(2, '0') + ':' +
                String(d.getMinutes()).padStart(2, '0') + ':' +
                String(d.getSeconds()).padStart(2, '0');

            const datosVenta = {
                id: numeroReal,
                serie: serie,
                numero: venta.numero || numeroReal,
                fecha: fechaFormateada,
                tipo: venta.tipoDocumento || 'ticket',
                idioma_ticket: venta.idioma_ticket || 'es',
                total: parseFloat(venta.total) || 0,
                metodoPago: venta.metodoPago,
                desglose_pago: venta.desglose_pago,
                clienteNombre: venta.cliente_nombre || venta.clienteNombre || null,
                clienteNif: venta.cliente_dni || venta.clienteDni || null,
                clienteDir: venta.cliente_direccion || venta.clienteDireccion || null,
                carrito: carrito,
                descuentoTipo: descuentos.descuentoManualTipo || 'porcentaje',
                descuentoValor: parseFloat(descuentos.descuentoManualValor) || 0,
                descuentoCupon: descuentos.descuentoManualCupon || null,
                qrUrl: ((window.TPV_CONFIG && window.TPV_CONFIG.qrBaseUrl) || 'https://prewww2.aeat.es/wlpl/TIKE-CONT/ValidarQR') + '?' + qrParams.toString()
            };

            const html = generarHTMLComprobante(datosVenta, venta.idioma_ticket || 'es');
            const iframe = document.createElement('iframe');
            iframe.style.position = 'absolute';
            iframe.style.top = '-10000px';
            document.body.appendChild(iframe);
            iframe.contentDocument.write(html);
            iframe.contentDocument.close();
            iframe.onload = function () {
                iframe.contentWindow.print();
                setTimeout(() => { if (iframe.parentNode) iframe.remove(); }, 1000);
            };
        })
        .catch(err => {
            if (typeof Swal !== 'undefined') {
                Swal.close();
                Swal.fire('Error', 'No se pudo conectar con el servidor', 'error');
            } else {
                alert('No se pudo conectar con el servidor');
            }
        });
}

function anularVentaAdmin(serie, numero) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: '¿Anular Documento?',
            text: `Vas a anular el documento ${serie}${String(numero).padStart(5, '0')}. Esta acción comunicará la anulación a la AEAT mediante Verifactu y no se puede deshacer.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Sí, Anular',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                ejecutarAnulacionVenta(serie, numero);
            }
        });
    } else {
        if (confirm(`¿Estás seguro de anular el documento ${serie}${numero}?`)) {
            ejecutarAnulacionVenta(serie, numero);
        }
    }
}

function ejecutarAnulacionVenta(serie, numero) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Procesando...',
            text: 'Comunicando con AEAT...',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });
    }

    fetch('api/ventas.php?accion=anularDocumento', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ serie: serie, numero: numero })
    })
        .then(r => r.json())
        .then(data => {
            if (typeof Swal !== 'undefined') {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Documento Anulado',
                        text: data.message || 'La anulación se comunicó correctamente a la AEAT.',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => cargarVentasAdmin());
                } else {
                    Swal.fire('Error al Anular', data.message || 'Error desconocido.', 'error');
                }
            } else {
                alert(data.message || (data.success ? 'Anulado correctamente' : 'Error al anular'));
                if (data.success) cargarVentasAdmin();
            }
        })
        .catch(err => {
            console.error("Error en anulación:", err);
            if (typeof Swal !== 'undefined') {
                Swal.fire('Error de Conexión', 'No se pudo contactar con el servidor.', 'error');
            } else {
                alert('Error de conexión.');
            }
        });
}