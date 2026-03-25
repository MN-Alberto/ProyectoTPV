/**
 * admin.ventas.js
 * Gestión de ventas, devoluciones, retiros y sesiones de caja.
 * Depende de: admin.state.js, admin.utils.js, admin.pagination.js
 */

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
                        oninput="buscarVentasPorId()" autocomplete="off"
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
    const pago = { efectivo: '💵 Efectivo', tarjeta: '💳 Tarjeta', bizum: '📱 Bizum' }[venta.forma_pago] || (venta.forma_pago || '—');
    const doc = { ticket: '🧾 Ticket', factura: '📄 Factura' }[venta.tipoDocumento] || (venta.tipoDocumento || 'ticket');
    return `
        <tr>
            <td class="col-id">${venta.serie || 'T'}${String(venta.numero || venta.id).padStart(5, '0')}</td>
            <td class="col-fecha">${fecha}</td>
            <td class="col-usuario">${venta.usuario_nombre || '—'}</td>
            <td class="col-productos">${venta.cantidad_productos || 0}</td>
            <td class="col-tarifa">${venta.tarifa_nombre || 'Cliente'}</td>
            <td class="col-documento">${doc}</td>
            <td class="col-pago">${pago}</td>
            <td class="col-total" style="font-weight:700;color:#059669;">${total} €</td>
            <td class="col-acciones">
                <button class="btn-admin-accion btn-ver" onclick="verDetalleVenta(${venta.id})" title="Ver Detalles">
                    <i class="fas fa-eye"></i></button>
            </td>
        </tr>`;
}

function renderizarVentasPagina() {
    const contenedor = document.getElementById('adminContenido');
    if (!contenedor) return;
    const inicio = (paginaActualVentas - 1) * ventasPorPagina;
    const ventasPagina = ventasData.slice(inicio, inicio + ventasPorPagina);
    const totalPaginas = Math.ceil(ventasData.length / ventasPorPagina);
    const tbody = contenedor.querySelector('tbody');
    if (tbody) tbody.innerHTML = ventasPagina.map(generarFilaVenta).join('');
    actualizarPaginacionDOM(contenedor, getPaginacionVentasHTML(totalPaginas));
}

function cargarVentasAdmin(filtroFecha = 'todos', metodoPago = 'todos', tipoDocumento = 'todos', orden = 'fecha_desc', busqueda = '') {
    if (seccionActual !== 'ventas') { adminTablaHeaderHTML = ''; seccionActual = 'ventas'; }
    const contenedor = document.getElementById('adminContenido');
    const esPrimeraVez = !contenedor.querySelector('.admin-tabla') || !adminTablaHeaderHTML;

    let url = 'api/ventas.php?todas=1';
    if (filtroFecha !== 'todos') url += '&filtroFecha=' + filtroFecha;
    if (metodoPago !== 'todos') url += '&metodoPago=' + metodoPago;
    if (tipoDocumento !== 'todos') url += '&tipoDocumento=' + tipoDocumento;
    if (orden !== 'fecha_desc') url += '&orden=' + orden;
    if (busqueda.trim()) url += '&busqueda=' + encodeURIComponent(busqueda.trim());

    fetch(url)
        .then(r => { if (!r.ok) return r.json().then(e => { throw new Error(e.error || 'Error al cargar ventas'); }); return r.json(); })
        .then(data => renderVentasAdmin(data, esPrimeraVez, filtroFecha, metodoPago, tipoDocumento, orden, busqueda))
        .catch(err => { contenedor.innerHTML = '<p class="sin-productos">Error: ' + err.message + '</p>'; });
}

function renderVentasAdmin(ventas, esPrimeraVez = true, filtroFecha = 'todos', metodoPago = 'todos', tipoDocumento = 'todos', orden = 'fecha_desc', busqueda = '') {
    const contenedor = document.getElementById('adminContenido');
    const total = ventas ? ventas.length : 0;

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
    paginaActualVentas = 1;

    const ventasPagina = ventasData.slice(0, ventasPorPagina);
    const totalPaginas = Math.ceil(ventasData.length / ventasPorPagina);

    ejecutarCuandoIdle(
        () => ventasPagina.map(generarFilaVenta).join(''),
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
    cargarVentasAdmin(
        document.getElementById('ventasFiltroFecha')?.value || 'todos',
        document.getElementById('ventasFiltroMetodo')?.value || 'todos',
        document.getElementById('ventasFiltroDocumento')?.value || 'todos',
        document.getElementById('ventasOrdenar')?.value || 'fecha_desc',
        document.getElementById('busquedaVentaId')?.value || ''
    );
}

function buscarVentasPorId() {
    clearTimeout(debounceTimerVentas);
    debounceTimerVentas = setTimeout(() => {
        const b = document.getElementById('busquedaVentaId')?.value || '';
        cargarVentasAdmin(
            document.getElementById('ventasFiltroFecha')?.value || 'todos',
            document.getElementById('ventasFiltroMetodo')?.value || 'todos',
            document.getElementById('ventasFiltroDocumento')?.value || 'todos',
            document.getElementById('ventasOrdenar')?.value || 'fecha_desc',
            b
        );
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
            const bg = isDark ? '#1f2937' : '#fff';
            const text = isDark ? '#e5e7eb' : '#374151';
            const card = isDark ? '#374151' : '#f8f9fa';
            const border = isDark ? '#4b5563' : '#e5e7eb';
            const discBg = isDark ? '#064e3b' : '#ecfdf5';
            const discColor = isDark ? '#34d399' : '#16a34a';

            const fecha = new Date(venta.fecha).toLocaleString('es-ES',
                { day: '2-digit', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' });
            const tipoDoc = venta.tipoDocumento === 'factura' ? '📄 Factura' : '🧾 Ticket';

            let descuentoHtml = '';
            if (descuentos.descuentoTarifaCupon === 'CLIENTE_REGISTRADO')
                descuentoHtml += `<div style="color:${discColor};"><strong>Cliente registrado:</strong> ${descuentos.descuentoTarifaValor}%</div>`;
            if (descuentos.descuentoManualCupon && descuentos.descuentoManualCupon !== '') {
                if (descuentos.descuentoManualTipo === 'porcentaje')
                    descuentoHtml += `<div style="color:${discColor};"><strong>Descuento:</strong> ${descuentos.descuentoManualValor}%</div>`;
                else
                    descuentoHtml += `<div style="color:${discColor};"><strong>Cupón:</strong> ${descuentos.descuentoManualCupon}</div>`;
            }

            let html = `<div style="border-radius:12px;overflow:hidden;background:${bg};">
                <div style="background:linear-gradient(135deg,#667eea,#764ba2);color:white;padding:20px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;">
                        <h3 style="margin:0;">${venta.serie || 'T'}${String(venta.numero || venta.id).padStart(5, '0')}</h3>
                        <span style="font-size:14px;opacity:.9;">${fecha}</span>
                    </div>
                </div>
                <div style="padding:20px;">
                    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:15px;margin-bottom:20px;padding:15px;background:${card};border-radius:8px;">
                        <div style="color:${text};"><strong>👤 Usuario:</strong><br>${venta.usuario_nombre || '—'}</div>
                        <div style="color:${text};"><strong>📄 Tipo:</strong><br>${tipoDoc}</div>
                        <div style="color:${text};"><strong>💳 Pago:</strong><br>${venta.metodoPago || '💵 Efectivo'}</div>
                    </div>`;

            if (descuentoHtml)
                html += `<div style="margin-bottom:20px;padding:15px;background:${discBg};border-radius:8px;">
                    <strong style="color:${discColor};">💰 Descuentos:</strong><br>${descuentoHtml}</div>`;

            html += `<h4 style="color:${text};">Productos:</h4>
                <div style="max-height:250px;overflow-y:auto;border:1px solid ${border};border-radius:8px;">
                <table style="width:100%;border-collapse:collapse;font-size:.9rem;">
                    <thead><tr style="background:#374151;color:white;">
                        <th style="padding:10px;text-align:left;">Producto</th>
                        <th style="padding:10px;text-align:center;">Cant.</th>
                        <th style="padding:10px;text-align:right;">P.U.</th>
                        <th style="padding:10px;text-align:center;">IVA</th>
                        <th style="padding:10px;text-align:right;">Subtotal</th>
                    </tr></thead>
                    <tbody>`;

            lineas.forEach(l => {
                const iva = l.iva || 21;
                const pvp = parseFloat(l.precioUnitario) * (1 + iva / 100);
                const sub = l.cantidad * pvp;
                html += `<tr>
                    <td style="padding:10px;border-bottom:1px solid ${border};color:${text};">${l.producto_nombre || 'Producto #' + l.idProducto}</td>
                    <td style="padding:10px;border-bottom:1px solid ${border};text-align:center;">${l.cantidad}</td>
                    <td style="padding:10px;border-bottom:1px solid ${border};text-align:right;">${pvp.toFixed(2).replace('.', ',')} €</td>
                    <td style="padding:10px;border-bottom:1px solid ${border};text-align:center;">${iva}%</td>
                    <td style="padding:10px;border-bottom:1px solid ${border};text-align:right;font-weight:600;">${sub.toFixed(2).replace('.', ',')} €</td>
                </tr>`;
            });

            html += `</tbody></table></div>
                <div style="margin-top:15px;padding:15px;background:linear-gradient(135deg,#11998e,#38ef7d);color:white;border-radius:8px;text-align:center;font-weight:bold;font-size:18px;">
                    TOTAL: ${parseFloat(venta.total).toFixed(2).replace('.', ',')} €
                </div>
                <div style="margin-top:20px;text-align:right;">
                    <button class="btn-modal-cancelar" onclick="cerrarModal('modalVerVenta')">Cerrar</button>
                </div></div></div>`;

            let modal = document.getElementById('modalVerVenta');
            if (!modal) {
                modal = document.createElement('div');
                modal.id = 'modalVerVenta';
                modal.className = 'modal-overlay';
                modal.style.display = 'none';
                modal.innerHTML = '<div class="modal-content" style="max-width:700px;max-height:85vh;overflow-y:auto;"></div>';
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
                    <th>#</th><th>Ticket</th><th>Fecha</th><th>Empleado</th>
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
            if (tbody) tbody.innerHTML = '<tr><td colspan="9" class="sin-productos">No hay devoluciones registradas.</td></tr>';
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
                <td class="col-id">${dev.id}</td>
                <td class="col-ticket" style="font-weight:600;color:#1e40af;">${dev.serie || 'T'}${String(dev.numero || dev.idVenta || '—').padStart(5, '0')}</td>
                <td class="col-fecha">${fecha}</td>
                <td class="col-usuario">${dev.usuario_nombre || '—'}</td>
                <td class="col-producto">${dev.producto_nombre || '—'}</td>
                <td class="col-cantidad">${dev.cantidad}</td>
                <td class="col-total" style="font-weight:700;color:#dc2626;">-${parseFloat(dev.importeTotal).toFixed(2).replace('.', ',')} €</td>
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
    fetch('api/devoluciones.php?todas=1')
        .then(r => r.json())
        .then(data => {
            const lista = data.devoluciones || data;
            const dev = lista.find(d => d.id == id);
            if (!dev) { alert('No se encontró la devolución'); return; }

            // Guardar para el botón "Ver Ticket" (dev individual + lista completa para agrupar por lote)
            window._ultimaDevAdmin = dev;
            window._todasDevolucionesAdmin = lista;

            const fecha = new Date(dev.fecha).toLocaleString('es-ES',
                { day: '2-digit', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' });

            document.getElementById('verDevolucionId').textContent = dev.id;
            const t = document.getElementById('verDevolucionTicket');
            if (t) t.textContent = (dev.serie || 'T') + String(dev.numero || dev.idVenta || '—').padStart(5, '0');
            document.getElementById('verDevolucionFecha').textContent = fecha;
            document.getElementById('verDevolucionProducto').textContent = dev.producto_nombre || '—';
            document.getElementById('verDevolucionCantidad').textContent = dev.cantidad;
            document.getElementById('verDevolucionImporte').textContent = '-' + parseFloat(dev.importeTotal).toFixed(2).replace('.', ',') + ' €';
            document.getElementById('verDevolucionMetodo').textContent = dev.metodoPago;
            document.getElementById('verDevolucionUsuario').textContent = dev.usuario_nombre || '—';
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
            <td style="color:#dc2626;font-weight:bold;">-${parseFloat(retiro.importe).toFixed(2)} €</td>
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
                    <th style="width:3%;text-align:center;">#</th>
                    <th style="width:10%;">Usuario</th>
                    <th style="width:10%;text-align:center;">Apertura</th>
                    <th style="width:12%;text-align:center;">Cierre</th>
                    <th style="width:7%;text-align:center;">Importe</th>
                    <th style="width:7%;text-align:center;">Efectivo</th>
                    <th style="width:7%;text-align:center;">Cambio</th>
                    <th style="width:7%;text-align:center;">Ventas</th>
                    <th style="width:9%;text-align:center;">Productos</th>
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
            <td style="text-align:center;width:40px;">${index + 1}</td>
            <td style="width:120px;">${sesion.usuario_nombre || 'Usuario #' + sesion.idUsuario}</td>
            <td style="text-align:center;">${fmt(sesion.fechaApertura)}</td>
            <td style="text-align:center;">${fmt(sesion.fechaCierre)}</td>
            <td style="text-align:center;">${parseFloat(sesion.importeInicial).toFixed(2)} €</td>
            <td style="text-align:center;">${importeActual.toFixed(2)} €</td>
            <td style="text-align:center;color:#0284c7;font-weight:bold;">${parseFloat(sesion.cambio || 0).toFixed(2)} €</td>
            <td style="text-align:center;font-weight:bold;">${parseInt(sesion.total_ventas || 0)}</td>
            <td style="text-align:center;font-weight:bold;">${parseInt(sesion.total_productos || 0)}</td>
            <td style="text-align:center;color:#ea580c;font-weight:bold;">-${retiros.toFixed(2)} €</td>
            <td style="text-align:center;color:#dc2626;font-weight:bold;">-${devoluciones.toFixed(2)} €</td>
            <td style="text-align:center;font-weight:bold;color:${diffColor};">
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