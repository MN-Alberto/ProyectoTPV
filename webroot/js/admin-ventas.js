/**
 * admin.ventas.js
 * Gestión de ventas, devoluciones, retiros y sesiones de caja.
 * Depende de: admin.state.js, admin.utils.js, admin.pagination.js
 */

let totalPaginasVentas = 1;

// ═══════════════════════════════════════════════════════════════════════════════
// VENTAS
// ═══════════════════════════════════════════════════════════════════════════════

function getVentasTablaHeader(filtroFecha = 'todos', metodoPago = 'todos', tipoDocumento = 'todos', orden = 'fecha_desc', busqueda = '', totalVentas = 0) {
    const contador = `${totalVentas.toLocaleString('es-ES')} Venta${totalVentas !== 1 ? 's' : ''}`;
    const opt = (val, actual, label) => `<option value="${val}" ${actual === val ? 'selected' : ''}>${label}</option>`;
    return `
        <div class="admin-tabla-header ventas-header">
            <div class="ventas-filtros">
                <div class="filtro-group" style="display:flex;align-items:center;gap:10px;">
                    <input type="text" id="busquedaVentaId" class="filtro-input"
                        placeholder="Buscar # venta..." value="${busqueda || ''}"
                        oninput="buscarVentasPorId()" onblur="buscarVentasPorId()"
                        autocomplete="off"
                        style="padding:8px 12px;border:1px solid #d1d5db;border-radius:6px;font-size:.9rem;width:140px;">
                </div>
                <div class="filtro-group">
                    <label for="ventasFiltroFecha">Período:</label>
                    <select id="ventasFiltroFecha" class="filtro-select" onchange="aplicarFiltrosVentas()">
                        ${opt('todos', filtroFecha, 'Todas')}${opt('hoy', filtroFecha, 'Hoy')}
                        ${opt('7dias', filtroFecha, 'Últimos 7 días')}${opt('30dias', filtroFecha, 'Último mes')}
                    </select>
                </div>
                <div class="filtro-group">
                    <label for="ventasFiltroMetodo">Método de pago:</label>
                    <select id="ventasFiltroMetodo" class="filtro-select" onchange="aplicarFiltrosVentas()">
                        ${opt('todos', metodoPago, 'Todos')}${opt('efectivo', metodoPago, 'Efectivo')}
                        ${opt('tarjeta', metodoPago, 'Tarjeta')}${opt('bizum', metodoPago, 'Bizum')}
                        ${opt('mixto', metodoPago, 'Mixto')}
                    </select>
                </div>
                <div class="filtro-group">
                    <label for="ventasFiltroDocumento">Documento:</label>
                    <select id="ventasFiltroDocumento" class="filtro-select" onchange="aplicarFiltrosVentas()">
                        ${opt('todos', tipoDocumento, 'Todos')}${opt('ticket', tipoDocumento, 'Ticket')}
                        ${opt('factura', tipoDocumento, 'Factura')}
                    </select>
                </div>
                <div class="filtro-group">
                    <label for="ventasOrdenar">Ordenar por:</label>
                    <select id="ventasOrdenar" class="filtro-select" onchange="aplicarFiltrosVentas()">
                        ${opt('fecha_desc', orden, 'Más recientes')}${opt('fecha_asc', orden, 'Más antiguos')}
                        ${opt('importe_desc', orden, 'Mayor importe')}${opt('importe_asc', orden, 'Menor importe')}
                        ${opt('cantidad_desc', orden, 'Más productos')}${opt('cantidad_asc', orden, 'Menos productos')}
                        ${opt('id_desc', orden, 'ID mayor')}${opt('id_asc', orden, 'ID menor')}
                    </select>
                </div>
                <button class="btn-limpiar-ventas" onclick="limpiarTodasVentas()">🗑️ Limpiar ventas</button>
                <span id="totalVentasAviso" class="total-clientes-aviso">${contador}</span>
            </div>
        </div>
        <div class="admin-tabla-wrapper sin-scroll">
            <table class="admin-tabla">
                <thead><tr>
                    <th>#</th><th>Fecha</th><th>Usuario</th><th>Productos</th>
                    <th>Tarifa</th><th>Documento</th><th>Forma de Pago</th><th>Total</th><th>Acciones</th>
                </tr></thead>
                <tbody>`;
}

function generarFilaVenta(venta) {
    const fecha = new Date(venta.fecha).toLocaleString('es-ES',
        { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
    const total = parseFloat(venta.total).toFixed(2).replace('.', ',');
    const pago = { efectivo: '💵 Efectivo', tarjeta: '💳 Tarjeta', bizum: '📱 Bizum', mixto: '🔄 Mixto' }[venta.forma_pago] || (venta.forma_pago || '—');
    const doc = { ticket: '🧾 Ticket', factura: '📄 Factura' }[venta.tipoDocumento] || (venta.tipoDocumento || 'ticket');
    const esAnulada = venta.estado === 'anulada';
    const esRect = venta.es_rectificativa == 1;
    let estadoBadge = '';
    if (esAnulada) estadoBadge = '<span style="background:#ef4444;color:#fff;padding:2px 6px;border-radius:4px;font-size:0.7rem;margin-left:4px;">ANULADO</span>';
    else if (esRect) estadoBadge = '<span style="background:#8b5cf6;color:#fff;padding:2px 6px;border-radius:4px;font-size:0.7rem;margin-left:4px;">' + (venta.tipo_factura_verifactu || 'RECT') + '</span>';
    return `
        <tr style="${esAnulada ? 'opacity:0.6;' : ''}">
            <td class="col-id">${venta.serie || 'T'}${String(venta.numero || venta.id).padStart(5, '0')}${estadoBadge}</td>
            <td class="col-fecha">${fecha}</td>
            <td class="col-usuario">${venta.usuario_nombre || '—'}</td>
            <td class="col-productos">${venta.cantidad_productos || 0}</td>
            <td class="col-tarifa">${venta.tarifa_nombre || 'Cliente'}</td>
            <td class="col-documento">${doc}</td>
            <td class="col-pago">${pago}</td>
            <td class="col-total" style="text-align:right;font-weight:700;color:${parseFloat(venta.total) < 0 ? '#ef4444' : '#059669'};">${total} €</td>
            <td class="col-acciones">
                <button class="btn-admin-accion btn-ver" onclick="event.stopPropagation(); verDetalleVenta(${venta.id})" title="Ver Detalles">
                    <i class="fas fa-eye"></i>
                </button>
                <button class="btn-admin-accion btn-editar" onclick="event.stopPropagation(); imprimirVentaDesdeHistorial(${venta.id})" title="Reimprimir Ticket">
                    <i class="fas fa-print"></i>
                </button>
                ${!esAnulada && !esRect ? `<button class="btn-admin-accion btn-eliminar" onclick="event.stopPropagation(); anularVentaAdmin('${venta.serie || 'T'}', ${venta.numero || venta.id})" title="Anular Documento">
                    <i class="fas fa-ban"></i>
                </button>` : ''}
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
            const ticketNum = `${venta.serie || 'T'}${String(venta.numero || venta.id).padStart(5, '0')}`;

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
            if (descuentos.descuentoTarifaCupon === 'CLIENTE_REGISTRADO' || (descuentos.descuentoManualCupon && descuentos.descuentoManualCupon !== '')) {
                let items = [];
                if (descuentos.descuentoTarifaCupon === 'CLIENTE_REGISTRADO') 
                    items.push(`<li><i class="fas fa-user-tag"></i> Cliente registrado: <strong>-${descuentos.descuentoTarifaValor}%</strong></li>`);
                if (descuentos.descuentoManualCupon && descuentos.descuentoManualCupon !== '') {
                    const val = descuentos.descuentoManualTipo === 'porcentaje' ? `-${descuentos.descuentoManualValor}%` : `-${parseFloat(descuentos.descuentoManualValor).toFixed(2)}€`;
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


// ═══════════════════════════════════════════════════════════════════════════════
// DEVOLUCIONES
// ═══════════════════════════════════════════════════════════════════════════════

function getDevolucionesTablaHeader(orden = 'fecha_desc', busquedaTicket = '', total = 0) {
    const contador = `${total.toLocaleString('es-ES')} Devolución${total !== 1 ? 'es' : ''}`;
    const opt = (v, a, l) => `<option value="${v}" ${a === v ? 'selected' : ''}>${l}</option>`;
    return `
        <div class="admin-tabla-header devoluciones-header">
            <div class="ventas-filtros">
                <div class="filtro-group" style="display:flex;align-items:center;gap:10px;">
                    <input type="text" id="busquedaTicketDevolucion" class="filtro-input"
                        placeholder="Buscar por ticket..." value="${busquedaTicket || ''}"
                        oninput="buscarDevolucionesPorTicket()" autocomplete="off"
                        style="padding:8px 12px;border:1px solid #d1d5db;border-radius:6px;font-size:.9rem;width:180px;">
                </div>
                <div class="filtro-group">
                    <label for="devolucionesOrdenar">Ordenar por:</label>
                    <select id="devolucionesOrdenar" class="filtro-select"
                        onchange="cargarDevolucionesAdmin(this.value,document.getElementById('busquedaTicketDevolucion').value)">
                        ${opt('fecha_desc', orden, 'Más recientes')}${opt('fecha_asc', orden, 'Más antiguos')}
                        ${opt('importe_desc', orden, 'Mayor importe')}${opt('importe_asc', orden, 'Menor importe')}
                    </select>
                </div>
                <span id="totalDevolucionesAviso" class="total-clientes-aviso">${contador}</span>
            </div>
        </div>
        <div class="admin-tabla-wrapper">
            <table class="admin-tabla">
                <thead><tr>
                    <th>Ticket</th><th>Fecha</th><th>Empleado</th>
                    <th>Producto</th><th>Cant.</th><th>Importe</th><th>Método</th><th>Acciones</th>
                </tr></thead>
                <tbody>`;
}

function cargarDevolucionesAdmin(orden = 'fecha_desc', busquedaTicket = '', resetPagina = true) {
    if (seccionActual !== 'devoluciones') { adminTablaHeaderHTML = ''; seccionActual = 'devoluciones'; }
    if (resetPagina) paginaActualDevoluciones = 1;

    const contenedor = document.getElementById('adminContenido');
    const esPrimeraVez = !contenedor.querySelector('.admin-tabla') || !adminTablaHeaderHTML;

    let url = `api/devoluciones.php?todas=1&pagina=${paginaActualDevoluciones}&porPagina=${devolucionesPorPagina}`;
    if (orden !== 'fecha_desc') url += '&orden=' + orden;
    if (busquedaTicket.trim()) url += '&busqueda=' + encodeURIComponent(busquedaTicket.trim());

    fetch(url)
        .then(r => r.json())
        .then(data => {
            totalPaginasDevoluciones = data.totalPaginas || 1;
            renderDevolucionesAdmin(data.devoluciones, esPrimeraVez, orden, busquedaTicket, data.total);
        })
        .catch(err => { contenedor.innerHTML = '<p class="sin-productos">Error: ' + err.message + '</p>'; });
}

function renderDevolucionesAdmin(devoluciones, esPrimeraVez = true, orden = 'fecha_desc', busquedaTicket = '', total = 0) {
    const contenedor = document.getElementById('adminContenido');
    const totalDev = total || (devoluciones ? devoluciones.length : 0);

    if (!devoluciones || !devoluciones.length) {
        if (esPrimeraVez || !adminTablaHeaderHTML) {
            adminTablaHeaderHTML = getDevolucionesTablaHeader(orden, busquedaTicket, totalDev);
            contenedor.innerHTML = adminTablaHeaderHTML +
                '<tr><td colspan="9" class="sin-productos">No hay devoluciones registradas.</td></tr></tbody></table></div>';
        } else {
            const tbody = contenedor.querySelector('tbody');
            if (tbody) tbody.innerHTML = '<tr><td colspan="8" class="sin-productos">No hay devoluciones registradas.</td></tr>';
            const c = document.getElementById('totalDevolucionesAviso');
            if (c) c.textContent = '0 Devoluciones';
        }
        const pag = contenedor.querySelector('.admin-paginacion-wrapper');
        if (pag) pag.remove();
        return;
    }

    if (esPrimeraVez || !adminTablaHeaderHTML || busquedaTicket !== '')
        adminTablaHeaderHTML = getDevolucionesTablaHeader(orden, busquedaTicket, totalDev);

    ejecutarCuandoIdle(() => devoluciones.map(dev => {
        const fecha = new Date(dev.fecha).toLocaleString('es-ES',
            { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
        return `
            <tr>
                <td class="col-ticket" style="font-weight:600;color:#1e40af;">${dev.serie || 'T'}${String(dev.numero || dev.idVenta || '—').padStart(5, '0')}</td>
                <td class="col-fecha">${fecha}</td>
                <td class="col-usuario">${dev.usuario_nombre || '—'}</td>
                <td class="col-producto">${dev.producto_nombre || '—'}</td>
                <td class="col-cantidad">${dev.cantidad}</td>
                <td class="col-total" style="text-align:right;font-weight:700;color:#dc2626;">-${parseFloat(dev.importeTotal).toFixed(2).replace('.', ',')} €</td>
                <td class="col-pago">${dev.metodoPago}</td>
                <td class="col-acciones">
                    <button class="btn-admin-accion btn-ver" onclick="verDetalleDevolucion(${dev.id})" title="Ver Detalles">
                        <i class="fas fa-eye"></i></button>
                </td>
            </tr>`;
    }).join(''), filasHtml => {
        const html = adminTablaHeaderHTML + filasHtml + '</tbody></table></div>' +
            getPaginacionDevolucionesHTML(totalPaginasDevoluciones);

        if (esPrimeraVez || busquedaTicket !== '') {
            contenedor.innerHTML = html;
        } else {
            const tbody = contenedor.querySelector('tbody');
            if (tbody) {
                tbody.innerHTML = filasHtml;
                actualizarPaginacionDOM(contenedor, getPaginacionDevolucionesHTML(totalPaginasDevoluciones));
            } else {
                contenedor.innerHTML = html;
            }
        }
        ajustarTodosInputsPaginacion();
    });

    const c = document.getElementById('totalDevolucionesAviso');
    if (c) c.textContent = `${totalDev.toLocaleString('es-ES')} Devolución${totalDev !== 1 ? 'es' : ''}`;
}

function buscarDevolucionesPorTicket() {
    clearTimeout(debounceTimerDevoluciones);
    debounceTimerDevoluciones = setTimeout(() => {
        const b = document.getElementById('busquedaTicketDevolucion')?.value || '';
        cargarDevolucionesAdmin('fecha_desc', b);
    }, 300);
}

function verDetalleDevolucion(id) {
    const container = document.getElementById('ticketDevolucionContainer');
    if (container) {
        container.innerHTML = `
            <div style="text-align: center; padding: 40px; color: #666;">
                <i class="fas fa-spinner fa-spin" style="font-size: 2rem; margin-bottom: 10px;"></i>
                <p>Cargando comprobante...</p>
            </div>`;
    }

    fetch('api/devoluciones.php?todas=1')
        .then(r => r.json())
        .then(data => {
            const lista = data.devoluciones || data;
            const dev = lista.find(d => d.id == id);
            if (!dev) { alert('No se encontró la devolución'); return; }

            // Guardar para el botón "Imprimir" (antes Ver Ticket)
            window._ultimaDevAdmin = dev;
            window._todasDevolucionesAdmin = lista;

            // Agrupar lote
            const lote = lista.filter(d => d.idVenta == dev.idVenta && d.fecha === dev.fecha);
            
            const carrito = lote.map(linea => ({
                nombre: linea.producto_nombre || '—',
                cantidad: linea.cantidad,
                precio: parseFloat(linea.precioUnitario) || 0,
                iva: linea.iva || 21,
                importeTotal: parseFloat(linea.importeTotal) || 0
            }));

            const totalGeneral = carrito.reduce((sum, item) => sum + item.importeTotal, 0);

            const nifTpv = (window.TPV_CONFIG && window.TPV_CONFIG.nif) ? window.TPV_CONFIG.nif : '';
            const serie = dev.serie || 'D';
            const numeroReal = dev.numero || dev.idVenta || dev.id;
            const numserie = serie + numeroReal;

            const qrParams = new URLSearchParams({
                nif: nifTpv,
                numserie: numserie,
                fecha: (() => {
                    const d = new Date(dev.fecha);
                    return String(d.getDate()).padStart(2, '0') + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + d.getFullYear();
                })(),
                importe: (-totalGeneral).toFixed(2)
            });

            const datosVenta = {
                id: numeroReal,
                serie: serie,
                numero: numeroReal,
                fecha: new Date(dev.fecha).toLocaleString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' }),
                tipo: 'ticket',
                es_rectificativa: true,
                id_original: dev.idVenta,
                serie_original: dev.serie_original || 'T',
                total: -totalGeneral,
                metodoPago: dev.metodoPago,
                carrito: carrito,
                usuario_nombre: dev.usuario_nombre,
                qrUrl: ((window.TPV_CONFIG && window.TPV_CONFIG.qrBaseUrl) || 'https://prewww2.aeat.es/wlpl/TIKE-CONT/ValidarQR') + '?' + qrParams.toString()
            };

            const html = generarHTMLComprobante(datosVenta, 'es');
            if (container) {
                // Insertar solo el body del ticket generado para que se vea bien en el modal
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const bodyContent = doc.body.innerHTML;
                container.innerHTML = `<div class="ticket-preview-wrapper" style="transform: scale(0.95); transform-origin: top center;">${bodyContent}</div>`;
            }
            abrirModal('modalVerDevolucion');
        })
        .catch(() => alert('Error al cargar detalles'));
}


// ═══════════════════════════════════════════════════════════════════════════════
// RETIROS DE CAJA
// ═══════════════════════════════════════════════════════════════════════════════

function getRetirosTablaHeader(orden = 'fecha_desc', total = 0) {
    const contador = `${total.toLocaleString('es-ES')} Retiro${total !== 1 ? 's' : ''}`;
    return `
        <div class="admin-tabla-header retiros-header">
            <div class="ventas-filtros">
                <div class="filtro-group">
                    <label for="retirosOrdenar">Ordenar por:</label>
                    <select id="retirosOrdenar" class="filtro-select" onchange="cargarRetirosAdmin(this.value)">
                        <option value="fecha_desc" ${orden === 'fecha_desc' ? 'selected' : ''}>Más recientes</option>
                        <option value="fecha_asc"  ${orden === 'fecha_asc' ? 'selected' : ''}>Más antiguos</option>
                        <option value="importe_desc" ${orden === 'importe_desc' ? 'selected' : ''}>Mayor importe</option>
                        <option value="importe_asc"  ${orden === 'importe_asc' ? 'selected' : ''}>Menor importe</option>
                    </select>
                </div>
                <span id="totalRetirosAviso" class="total-clientes-aviso">${contador}</span>
            </div>
        </div>
        <div class="admin-tabla-wrapper sin-scroll">
            <table class="admin-tabla">
                <thead><tr>
                    <th>#</th><th>Fecha</th><th>Usuario</th><th>Importe</th><th>Motivo</th><th>Sesión Caja</th>
                </tr></thead>
                <tbody>`;
}

function generarFilaRetiro(retiro, index) {
    const fecha = new Date(retiro.fecha).toLocaleString('es-ES',
        { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
    const usuario = retiro.usuario_nombre
        ? retiro.usuario_nombre + ' ' + (retiro.usuario_apellidos || '')
        : 'Usuario #' + retiro.idUsuario;
    const cajaSesion = retiro.caja_fecha_apertura
        ? new Date(retiro.caja_fecha_apertura).toLocaleDateString('es-ES')
        : '#' + retiro.idCajaSesion;
    return `
        <tr>
            <td>${index + 1}</td>
            <td>${fecha}</td>
            <td>${usuario}</td>
            <td style="text-align:right;color:#dc2626;font-weight:bold;">-${parseFloat(retiro.importe).toFixed(2)} €</td>
            <td>${retiro.motivo || 'Sin motivo'}</td>
            <td>${cajaSesion}</td>
        </tr>`;
}

function renderizarRetirosPagina() {
    const contenedor = document.getElementById('adminContenido');
    if (!contenedor) return;
    const inicio = (paginaActualRetiros - 1) * retirosPorPagina;
    const pag = retirosData.slice(inicio, inicio + retirosPorPagina);
    const totalPaginas = Math.ceil(retirosData.length / retirosPorPagina);
    const tbody = contenedor.querySelector('tbody');
    if (tbody) tbody.innerHTML = pag.map((r, i) => generarFilaRetiro(r, inicio + i)).join('');
    actualizarPaginacionDOM(contenedor, getPaginacionRetirosHTML(totalPaginas));
}

function cargarRetirosAdmin(orden = 'fecha_desc') {
    if (seccionActual !== 'retiros') { adminTablaHeaderHTML = ''; seccionActual = 'retiros'; }
    const contenedor = document.getElementById('adminContenido');
    const esPrimeraVez = !contenedor.querySelector('.admin-tabla') || !adminTablaHeaderHTML;

    let url = 'api/retiros.php';
    if (orden !== 'fecha_desc') url += '?orden=' + orden;

    fetch(url)
        .then(r => r.json())
        .then(data => renderRetirosAdmin(data, esPrimeraVez, orden))
        .catch(err => { contenedor.innerHTML = '<p class="sin-productos">Error: ' + (err.message || 'Error desconocido') + '</p>'; });
}

function renderRetirosAdmin(retiros, esPrimeraVez = true, orden = 'fecha_desc') {
    const contenedor = document.getElementById('adminContenido');
    const total = retiros ? retiros.length : 0;
    retirosData = retiros || [];
    paginaActualRetiros = 1;

    if (!total) {
        if (esPrimeraVez || !adminTablaHeaderHTML) {
            adminTablaHeaderHTML = getRetirosTablaHeader(orden, 0);
            contenedor.innerHTML = adminTablaHeaderHTML +
                '<tr><td colspan="6" class="sin-productos">No hay retiros de caja registrados.</td></tr></tbody></table></div>';
        } else {
            const tbody = contenedor.querySelector('tbody');
            if (tbody) tbody.innerHTML = '<tr><td colspan="6" class="sin-productos">No hay retiros de caja registrados.</td></tr>';
            const c = document.getElementById('totalRetirosAviso');
            if (c) c.textContent = '0 Retiros';
        }
        return;
    }

    const totalPaginas = Math.ceil(retirosData.length / retirosPorPagina);
    const pag = retirosData.slice(0, retirosPorPagina);

    if (esPrimeraVez || !adminTablaHeaderHTML) adminTablaHeaderHTML = getRetirosTablaHeader(orden, total);

    const filasHtml = pag.map((r, i) => generarFilaRetiro(r, i)).join('');

    if (esPrimeraVez) {
        contenedor.innerHTML = adminTablaHeaderHTML + filasHtml +
            '</tbody></table></div>' + getPaginacionRetirosHTML(totalPaginas);
    } else {
        const tbody = contenedor.querySelector('tbody');
        if (tbody) {
            tbody.innerHTML = filasHtml;
            actualizarPaginacionDOM(contenedor, getPaginacionRetirosHTML(totalPaginas));
        }
    }

    const c = document.getElementById('totalRetirosAviso');
    if (c) c.textContent = `${total.toLocaleString('es-ES')} Retiro${total !== 1 ? 's' : ''}`;
}

// ═══════════════════════════════════════════════════════════════════════════════
// SESIONES DE CAJA
// ═══════════════════════════════════════════════════════════════════════════════

function getCajaSesionesTablaHeader(orden = 'fecha_desc') {
    return `
        <div class="admin-tabla-header caja-sesiones-header">
            <div class="ventas-filtros">
                <div class="filtro-group">
                    <label for="cajaSesionesOrdenar">Ordenar por:</label>
                    <select id="cajaSesionesOrdenar" class="filtro-select" onchange="cargarCajaSesionesAdmin(this.value)">
                        <option value="fecha_desc" ${orden === 'fecha_desc' ? 'selected' : ''}>Más recientes</option>
                        <option value="fecha_asc"  ${orden === 'fecha_asc' ? 'selected' : ''}>Más antiguos</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="admin-tabla-wrapper sin-scroll">
            <table class="admin-tabla">
                <thead><tr>
                    <th style="width:9%;">U. Apertura</th>
                    <th style="width:9%;">U. Cierre</th>
                    <th style="width:10%;text-align:center;">Apertura</th>
                    <th style="width:12%;text-align:center;">Cierre</th>
                    <th style="width:7%;text-align:center;">Importe Ini</th>
                    <th style="width:7%;text-align:center;">Efectivo</th>
                    <th style="width:7%;text-align:center;">Cambio</th>
                    <th style="width:8%;text-align:center;">Retiros</th>
                    <th style="width:10%;text-align:center;">Devoluciones</th>
                    <th style="width:10%;text-align:center;">Arqueo</th>
                </tr></thead>
                <tbody>`;
}

function generarFilaSesion(sesion, index) {
    const fmt = d => d ? new Date(d).toLocaleString('es-ES',
        { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '-';
    const retiros = parseFloat(sesion.total_retiros || 0);
    const devoluciones = parseFloat(sesion.total_devoluciones || 0);
    const efectivoContado = sesion.efectivoContado;
    const importeActual = parseFloat(sesion.importeActual);
    const diff = efectivoContado !== null ? parseFloat(efectivoContado) - importeActual : null;
    const diffColor = diff !== null ? (diff > 0.01 ? '#059669' : (diff < -0.01 ? '#dc2626' : 'inherit')) : 'inherit';
    const diffLabel = diff !== null ? (diff > 0.01 ? '(Sobrante)' : (diff < -0.01 ? '(Faltante)' : '')) : '';

    return `
        <tr>
            <td style="width:120px; font-size: 0.9em;">${sesion.usuario_nombre || 'Usuario #' + sesion.idUsuario}</td>
            <td style="width:120px; font-size: 0.9em;">${sesion.usuario_cierre_nombre || '—'}</td>
            <td style="text-align:center;">${fmt(sesion.fechaApertura)}</td>
            <td style="text-align:center;">${fmt(sesion.fechaCierre)}</td>
            <td style="text-align:right;">${parseFloat(sesion.importeInicial).toFixed(2)} €</td>
            <td style="text-align:right;">${importeActual.toFixed(2)} €</td>
            <td style="text-align:right;color:#0284c7;font-weight:bold;">${parseFloat(sesion.cambio || 0).toFixed(2)} €</td>
            <td style="text-align:right;color:#ea580c;font-weight:bold;">-${retiros.toFixed(2)} €</td>
            <td style="text-align:right;color:#dc2626;font-weight:bold;">-${devoluciones.toFixed(2)} €</td>
            <td style="text-align:right;font-weight:bold;color:${diffColor};">
                ${diff !== null ? `${diff.toFixed(2).replace('.', ',')} € <small>${diffLabel}</small>` : '—'}
            </td>
        </tr>`;
}

function renderizarSesionesPagina() {
    const contenedor = document.getElementById('adminContenido');
    if (!contenedor) return;
    const inicio = (paginaActualSesiones - 1) * sesionesPorPagina;
    const pag = sesionesData.slice(inicio, inicio + sesionesPorPagina);
    const totalPaginas = Math.ceil(sesionesData.length / sesionesPorPagina);
    const tbody = contenedor.querySelector('tbody');
    if (tbody) tbody.innerHTML = pag.map((s, i) => generarFilaSesion(s, i)).join('');
    actualizarPaginacionDOM(contenedor, getPaginacionSesionesHTML(totalPaginas));
}

function cargarCajaSesionesAdmin(orden = 'fecha_desc') {
    if (seccionActual !== 'caja-sesiones') { adminTablaHeaderHTML = ''; seccionActual = 'caja-sesiones'; }
    const contenedor = document.getElementById('adminContenido');
    const esPrimeraVez = !contenedor.querySelector('.admin-tabla') || !adminTablaHeaderHTML;

    let url = 'api/caja-sesiones.php';
    if (orden !== 'fecha_desc') url += '?orden=' + orden;

    fetch(url)
        .then(r => r.json())
        .then(data => renderCajaSesionesAdmin(data, esPrimeraVez, orden))
        .catch(err => { contenedor.innerHTML = '<p class="sin-productos">Error: ' + (err.message || 'Error desconocido') + '</p>'; });
}

function renderCajaSesionesAdmin(sesiones, esPrimeraVez = true, orden = 'fecha_desc') {
    const contenedor = document.getElementById('adminContenido');
    sesionesData = sesiones || [];
    if (esPrimeraVez) paginaActualSesiones = 1;

    if (!sesiones || !sesiones.length) {
        if (esPrimeraVez || !adminTablaHeaderHTML) {
            adminTablaHeaderHTML = getCajaSesionesTablaHeader(orden);
            contenedor.innerHTML = adminTablaHeaderHTML +
                '<tr><td colspan="12" class="sin-productos">No hay sesiones de caja registradas.</td></tr></tbody></table></div>';
        } else {
            const tbody = contenedor.querySelector('tbody');
            if (tbody) tbody.innerHTML = '<tr><td colspan="12" class="sin-productos">No hay sesiones de caja registradas.</td></tr>';
        }
        return;
    }

    const totalPaginas = Math.ceil(sesiones.length / sesionesPorPagina);
    const pag = sesiones.slice(0, sesionesPorPagina);

    if (esPrimeraVez || !adminTablaHeaderHTML) adminTablaHeaderHTML = getCajaSesionesTablaHeader(orden);

    const filasHtml = pag.map((s, i) => generarFilaSesion(s, i)).join('');

    if (esPrimeraVez) {
        contenedor.innerHTML = adminTablaHeaderHTML + filasHtml +
            '</tbody></table></div>' + getPaginacionSesionesHTML(totalPaginas);
    } else {
        const tbody = contenedor.querySelector('tbody');
        if (tbody) {
            tbody.innerHTML = filasHtml;
            actualizarPaginacionDOM(contenedor, getPaginacionSesionesHTML(totalPaginas));
        }
    }
}

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
            const numserie = serie + numeroReal;

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