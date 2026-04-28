/**
 * admin-caja.js
 * Retiros de caja, Sesiones de caja y Devoluciones en el panel de administración.
 * Depende de: admin-state.js, admin-utils.js, admin-pagination.js
 */

// ── RETIROS ───────────────────────────────────────────────────────────────────

/**
 * Genera el HTML del header de la tabla de retiros.
 */
function getRetirosTablaHeader(orden = 'fecha_desc', totalRetiros = 0) {
    const contadorHTML = `${totalRetiros.toLocaleString('es-ES')} Retiro${totalRetiros !== 1 ? 's' : ''}`;
    return `
        <div class="admin-tabla-header retiros-header">
            <div class="ventas-filtros">
                <div class="filtro-group">
                    <label for="retirosOrdenar">Ordenar por:</label>
                    <select id="retirosOrdenar" class="filtro-select" onchange="cargarRetirosAdmin(this.value)">
                        <option value="fecha_desc" ${orden === 'fecha_desc' ? 'selected' : ''}>Más recientes</option>
                        <option value="fecha_asc" ${orden === 'fecha_asc' ? 'selected' : ''}>Más antiguos</option>
                        <option value="importe_desc" ${orden === 'importe_desc' ? 'selected' : ''}>Mayor importe</option>
                        <option value="importe_asc" ${orden === 'importe_asc' ? 'selected' : ''}>Menor importe</option>
                    </select>
                </div>
                <span id="totalRetirosAviso" class="total-clientes-aviso">${contadorHTML}</span>
            </div>
        </div>
        <div class="admin-tabla-wrapper sin-scroll">
            <table class="admin-tabla">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Fecha</th>
                        <th>Usuario</th>
                        <th>Importe</th>
                        <th>Motivo</th>
                        <th>Sesión Caja</th>
                    </tr>
                </thead>
                <tbody>`;
}

/**
 * Carga los retiros de caja desde la API y los renderiza en una tabla.
 */
function cargarRetirosAdmin(orden = 'fecha_desc') {
    if (seccionActual !== 'retiros') {
        adminTablaHeaderHTML = '';
        seccionActual = 'retiros';
    }

    const contenedor = document.getElementById('adminContenido');
    const isFirstTime = !contenedor.querySelector('.admin-tabla') || adminTablaHeaderHTML === '';

    let url = 'api/retiros.php';
    if (orden !== 'fecha_desc') url += '?orden=' + orden;

    fetch(url)
        .then(res => {
            if (!res.ok) return res.json().then(err => { throw new Error(err.error || 'Error al cargar retiros'); });
            return res.json();
        })
        .then(data => renderRetirosAdmin(data, isFirstTime, orden))
        .catch(err => {
            console.error('Error cargando retiros:', err);
            contenedor.innerHTML = '<p class="sin-productos">Error: ' + (err.message || 'Error desconocido') + '</p>';
        });
}

/**
 * Renderiza los retiros en la tabla.
 */
function renderRetirosAdmin(retiros, isFirstTime = true, orden = 'fecha_desc') {
    const contenedor = document.getElementById('adminContenido');
    const totalRetiros = retiros ? retiros.length : 0;

    if (!retiros || retiros.length === 0) {
        if (isFirstTime || adminTablaHeaderHTML === '') {
            adminTablaHeaderHTML = getRetirosTablaHeader(orden, 0);
            contenedor.innerHTML = adminTablaHeaderHTML +
                '<tr><td colspan="6" class="sin-productos">No hay retiros de caja registrados.</td></tr></tbody></table></div>';
        } else {
            const tbody = contenedor.querySelector('tbody');
            if (tbody) tbody.innerHTML = '<tr><td colspan="6" class="sin-productos">No hay retiros de caja registrados.</td></tr>';
            const contador = document.getElementById('totalRetirosAviso');
            if (contador) contador.textContent = '0 Retiros';
        }
        return;
    }

    retirosData = retiros;
    paginaActualRetiros = 1;

    const totalPaginas = Math.ceil(retiros.length / retirosPorPagina);
    const inicio = 0;
    const retirosPagina = retiros.slice(inicio, retirosPorPagina);
    const paginacionHTML = getPaginacionRetirosHTML(totalPaginas);

    if (isFirstTime || adminTablaHeaderHTML === '') {
        adminTablaHeaderHTML = getRetirosTablaHeader(orden, totalRetiros);
        let html = adminTablaHeaderHTML;
        retirosPagina.forEach((retiro, index) => { html += generarFilaRetiro(retiro, index); });
        html += '</tbody></table></div>' + paginacionHTML;
        contenedor.innerHTML = html;
    } else {
        const tbody = contenedor.querySelector('tbody');
        if (tbody) {
            let html = '';
            retirosPagina.forEach((retiro, index) => { html += generarFilaRetiro(retiro, index); });
            tbody.innerHTML = html;
        }
        actualizarPaginacionDOM(contenedor, paginacionHTML);
    }

    const contador = document.getElementById('totalRetirosAviso');
    if (contador) contador.textContent = `${totalRetiros.toLocaleString('es-ES')} Retiro${totalRetiros !== 1 ? 's' : ''}`;
}

/**
 * Genera el HTML de una fila de retiro.
 */
function generarFilaRetiro(retiro, index) {
    const fecha = new Date(retiro.fecha).toLocaleString('es-ES', {
        day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit'
    });
    const importe = parseFloat(retiro.importe).toFixed(2).replace('.', ',');
    return `
        <tr>
            <td class="col-id">${retiro.id}</td>
            <td>${fecha}</td>
            <td>${retiro.usuario_nombre || '—'}</td>
            <td style="text-align:right;font-weight: 700; color: #dc2626;">${importe} €</td>
            <td>${retiro.motivo || '—'}</td>
            <td>${retiro.idCajaSesion || '—'}</td>
        </tr>`;
}

/**
 * Renderiza la página actual de retiros.
 */
function renderizarRetirosPagina() {
    const contenedor = document.getElementById('adminContenido');
    const totalPaginas = Math.ceil(retirosData.length / retirosPorPagina);
    const inicio = (paginaActualRetiros - 1) * retirosPorPagina;
    const retirosPagina = retirosData.slice(inicio, inicio + retirosPorPagina);

    const tbody = contenedor.querySelector('tbody');
    if (tbody) {
        let html = '';
        retirosPagina.forEach((retiro, index) => { html += generarFilaRetiro(retiro, index); });
        tbody.innerHTML = html;
    }
    actualizarPaginacionDOM(contenedor, getPaginacionRetirosHTML(totalPaginas));
}

// ── SESIONES DE CAJA ──────────────────────────────────────────────────────────

/**
 * Genera el HTML del header de la tabla de sesiones de caja.
 */
function getCajaSesionesTablaHeader(orden = 'fecha_desc') {
    return `
        <div class="admin-tabla-header caja-sesiones-header">
            <div class="ventas-filtros">
                <div class="filtro-group">
                    <label for="cajaSesionesOrdenar">Ordenar por:</label>
                    <select id="cajaSesionesOrdenar" class="filtro-select" onchange="cargarCajaSesionesAdmin(this.value)">
                        <option value="fecha_desc" ${orden === 'fecha_desc' ? 'selected' : ''}>Más recientes</option>
                        <option value="fecha_asc" ${orden === 'fecha_asc' ? 'selected' : ''}>Más antiguos</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="admin-tabla-wrapper sin-scroll">
            <table class="admin-tabla">
                <thead>
                    <tr>
                        <th style="width:9%;">U. Apertura</th>
                        <th style="width:9%;">U. Cierre</th>
                        <th style="width:10%;text-align:center;">Apertura</th>
                        <th style="width:12%;text-align:center;">Cierre</th>
                        <th style="width:7%;text-align:center;">Importe</th>
                        <th style="width:7%;text-align:center;">Efectivo</th>
                        <th style="width:9%;text-align:center;">Cambio</th>
                        <th style="width:9%;text-align:center;">Retiros</th>
                        <th style="width:10%;text-align:center;">Devoluciones</th>
                        <th style="width:10%;text-align:center;">Arqueo</th>
                    </tr>
                </thead>
                <tbody>`;
}

/**
 * Carga las sesiones de caja desde la API y las renderiza.
 */
function cargarCajaSesionesAdmin(orden = 'fecha_desc') {
    if (seccionActual !== 'caja-sesiones') {
        adminTablaHeaderHTML = '';
        seccionActual = 'caja-sesiones';
    }

    const contenedor = document.getElementById('adminContenido');
    const isFirstTime = !contenedor.querySelector('.admin-tabla') || adminTablaHeaderHTML === '';

    let url = 'api/caja-sesiones.php?_=' + Date.now();
    if (orden !== 'fecha_desc') url += '&orden=' + orden;

    fetch(url, { cache: "no-store", headers: { "Cache-Control": "no-cache" } })
        .then(res => {
            if (!res.ok) return res.json().then(err => { throw new Error(err.error || 'Error al cargar sesiones de caja'); });
            return res.json();
        })
        .then(data => renderCajaSesionesAdmin(data, isFirstTime, orden))
        .catch(err => {
            console.error('Error cargando sesiones de caja:', err);
            contenedor.innerHTML = '<p class="sin-productos">Error: ' + (err.message || 'Error desconocido') + '</p>';
        });
}

/**
 * Renderiza las sesiones de caja en la tabla con paginación.
 */
function renderCajaSesionesAdmin(sesiones, isFirstTime = true, orden = 'fecha_desc') {
    const contenedor = document.getElementById('adminContenido');

    // ELIMINAR DEFINITIVAMENTE LAS COLUMNAS
    sesiones.forEach(sesion => {
        delete sesion.totalVentas;
        delete sesion.totalProductos;
    });

    sesionesData = sesiones;

    if (isFirstTime) paginaActualSesiones = 1;

    if (!sesiones || sesiones.length === 0) {
        if (isFirstTime || adminTablaHeaderHTML === '') {
            adminTablaHeaderHTML = getCajaSesionesTablaHeader(orden);
            contenedor.innerHTML = adminTablaHeaderHTML +
                '<tr><td colspan="10" class="sin-productos">No hay sesiones de caja registradas.</td></tr></tbody></table></div>';
        } else {
            const tbody = contenedor.querySelector('tbody');
            if (tbody) tbody.innerHTML = '<tr><td colspan="10" class="sin-productos">No hay sesiones de caja registradas.</td></tr>';
        }
        return;
    }

    const totalPaginas = Math.ceil(sesiones.length / sesionesPorPagina);
    const inicio = (paginaActualSesiones - 1) * sesionesPorPagina;
    const sesionesPagina = sesiones.slice(inicio, inicio + sesionesPorPagina);

    if (isFirstTime || adminTablaHeaderHTML === '') {
        adminTablaHeaderHTML = getCajaSesionesTablaHeader(orden);
        let html = adminTablaHeaderHTML;
        sesionesPagina.forEach((sesion, index) => { html += generarFilaSesion(sesion, index); });
        html += '</tbody></table></div>' + getPaginacionSesionesHTML(totalPaginas);
        contenedor.innerHTML = html;
    } else {
        const tbody = contenedor.querySelector('tbody');
        if (tbody) {
            let html = '';
            sesionesPagina.forEach((sesion, index) => { html += generarFilaSesion(sesion, index); });
            tbody.innerHTML = html;
        }
        actualizarPaginacionDOM(contenedor, getPaginacionSesionesHTML(totalPaginas));
    }
}

/**
 * Renderiza la página actual de sesiones.
 */
function renderizarSesionesPagina() {
    const contenedor = document.getElementById('adminContenido');
    const totalPaginas = Math.ceil(sesionesData.length / sesionesPorPagina);
    const inicio = (paginaActualSesiones - 1) * sesionesPorPagina;
    const sesionesPagina = sesionesData.slice(inicio, inicio + sesionesPorPagina);

    const tbody = contenedor.querySelector('tbody');
    if (tbody) {
        let html = '';
        sesionesPagina.forEach((sesion, index) => { html += generarFilaSesion(sesion, index); });
        tbody.innerHTML = html;
    }
    actualizarPaginacionDOM(contenedor, getPaginacionSesionesHTML(totalPaginas));
}

/**
 * Genera el HTML de una fila de sesión de caja.
 */
function generarFilaSesion(sesion, index) {
    const fmtFecha = (f) => f ? new Date(f).toLocaleString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '—';
    const fmtEur = (v) => v != null ? parseFloat(v).toFixed(2).replace('.', ',') + ' €' : '—';

    const desajuste = sesion.desajuste != null ? parseFloat(sesion.desajuste) : null;
    const desajusteColor = desajuste === null ? '' : (desajuste >= 0 ? 'color:#059669;' : 'color:#dc2626;');

    return `
        <tr>
            <td style="width:120px; font-size: 0.9em;">${sesion.usuario_nombre || 'Usuario #' + sesion.idUsuario}</td>
            <td style="width:120px; font-size: 0.9em;">${sesion.usuario_cierre_nombre || '—'}</td>
            <td style="text-align:center;">${fmtFecha(sesion.fechaApertura)}</td>
            <td style="text-align:center;">${fmtFecha(sesion.fechaCierre)}</td>
            <td style="text-align:right;">${fmtEur(sesion.importeInicial)}</td>
            <td style="text-align:right;">${fmtEur(sesion.importeActual)}</td>
            <td style="text-align:right;">${fmtEur(sesion.cambio)}</td>
            <td style="text-align:right;">${fmtEur(sesion.totalRetiros)}</td>
            <td style="text-align:right;">${fmtEur(sesion.totalDevoluciones)}</td>
            <td style="text-align:right;${desajusteColor}">${desajuste !== null ? fmtEur(desajuste) : '—'}</td>
        </tr>`;
}

// ── DEVOLUCIONES ──────────────────────────────────────────────────────────────

/**
 * Genera el HTML del header de la tabla de devoluciones.
 */
function getDevolucionesTablaHeader(orden = 'fecha_desc', busquedaTicket = '', totalDevoluciones = 0) {
    const contadorHTML = `${totalDevoluciones.toLocaleString('es-ES')} Devolución${totalDevoluciones !== 1 ? 'es' : ''}`;
    return `
        <div class="admin-tabla-header devoluciones-header">
            <div class="ventas-filtros">
                <div class="filtro-group" style="display:flex;align-items:center;gap:10px;">
                    <input type="text" id="busquedaTicketDevolucion"
                        class="filtro-input"
                        placeholder="Buscar por ticket..."
                        value="${busquedaTicket || ''}"
                        oninput="buscarDevolucionesPorTicket()"
                        autocomplete="off"
                        style="padding:8px 12px;border:1px solid #d1d5db;border-radius:6px;font-size:0.9rem;width:180px;">
                </div>
                <div class="filtro-group">
                    <label for="devolucionesOrdenar">Ordenar por:</label>
                    <select id="devolucionesOrdenar" class="filtro-select"
                        onchange="cargarDevolucionesAdmin(this.value, document.getElementById('busquedaTicketDevolucion').value)">
                        <option value="fecha_desc" ${orden === 'fecha_desc' ? 'selected' : ''}>Más recientes</option>
                        <option value="fecha_asc" ${orden === 'fecha_asc' ? 'selected' : ''}>Más antiguos</option>
                        <option value="importe_desc" ${orden === 'importe_desc' ? 'selected' : ''}>Mayor importe</option>
                        <option value="importe_asc" ${orden === 'importe_asc' ? 'selected' : ''}>Menor importe</option>
                    </select>
                </div>
                <span id="totalDevolucionesAviso" class="total-clientes-aviso">${contadorHTML}</span>
            </div>
        </div>
        <div class="admin-tabla-wrapper">
            <table class="admin-tabla">
                <thead>
                    <tr>
                        <th>Ticket</th>
                        <th>Fecha</th>
                        <th>Empleado</th>
                        <th>Producto</th>
                        <th>Cant.</th>
                        <th>Importe</th>
                        <th>Método</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>`;
}

/**
 * Carga las devoluciones desde la API y las renderiza en una tabla.
 */
function cargarDevolucionesAdmin(orden = 'fecha_desc', busquedaTicket = '', resetPagina = true) {
    if (seccionActual !== 'devoluciones') {
        adminTablaHeaderHTML = '';
        seccionActual = 'devoluciones';
    }

    if (resetPagina) paginaActualDevoluciones = 1;

    const contenedor = document.getElementById('adminContenido');
    const isFirstTime = !contenedor.querySelector('.admin-tabla') || adminTablaHeaderHTML === '';

    let url = 'api/devoluciones.php?todas=1&pagina=' + paginaActualDevoluciones + '&porPagina=' + devolucionesPorPagina;
    if (orden !== 'fecha_desc') url += '&orden=' + orden;
    if (busquedaTicket && busquedaTicket.trim() !== '') url += '&busqueda=' + encodeURIComponent(busquedaTicket.trim());

    fetch(url)
        .then(res => {
            if (!res.ok) return res.json().then(err => { throw new Error(err.error || 'Error al cargar devoluciones'); });
            return res.json();
        })
        .then(data => {
            totalPaginasDevoluciones = data.totalPaginas || 1;
            renderDevolucionesAdmin(data.devoluciones, isFirstTime, orden, busquedaTicket, data.total);
        })
        .catch(err => {
            console.error('Error cargando devoluciones:', err);
            contenedor.innerHTML = '<p class="sin-productos">Error: ' + err.message + '</p>';
        });
}

/**
 * Renderiza las devoluciones en la tabla.
 */
function renderDevolucionesAdmin(devoluciones, isFirstTime = true, orden = 'fecha_desc', busquedaTicket = '', total = 0) {
    const contenedor = document.getElementById('adminContenido');
    const totalDevoluciones = total || (devoluciones ? devoluciones.length : 0);

    if (!devoluciones || devoluciones.length === 0) {
        if (isFirstTime || adminTablaHeaderHTML === '') {
            adminTablaHeaderHTML = getDevolucionesTablaHeader(orden, busquedaTicket, totalDevoluciones);
            contenedor.innerHTML = adminTablaHeaderHTML +
                '<tr><td colspan="8" class="sin-productos">No hay devoluciones registradas.</td></tr></tbody></table></div>';
        } else {
            const tbody = contenedor.querySelector('tbody');
            if (tbody) tbody.innerHTML = '<tr><td colspan="7" class="sin-productos">No hay devoluciones registradas.</td></tr>';
            const contador = document.getElementById('totalDevolucionesAviso');
            if (contador) contador.textContent = '0 Devoluciones';
        }
        const paginacionExistente = contenedor.querySelector('.admin-paginacion-wrapper');
        if (paginacionExistente) paginacionExistente.remove();
        return;
    }

    if (isFirstTime || adminTablaHeaderHTML === '' || busquedaTicket !== '') {
        adminTablaHeaderHTML = getDevolucionesTablaHeader(orden, busquedaTicket, totalDevoluciones);
    }

    const generarFilasDevoluciones = () => {
        let filasHtml = '';
        devoluciones.forEach(dev => {
            const fecha = new Date(dev.fecha).toLocaleString('es-ES', {
                day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit'
            });
            const total = parseFloat(dev.importeTotal).toFixed(2).replace('.', ',');

            filasHtml += `
                <tr>
                    <td class="col-ticket" style="font-weight:600;color:#1e40af;">${dev.serie || 'T'}${String(dev.numero || dev.idVenta || '—').padStart(5, '0')}</td>
                    <td class="col-fecha">${fecha}</td>
                    <td class="col-usuario">${dev.usuario_nombre || '—'}</td>
                    <td class="col-producto">${dev.producto_nombre || '—'}</td>
                    <td class="col-cantidad">${dev.cantidad}</td>
                    <td class="col-total" style="text-align:right;font-weight:700;color:#dc2626;">-${total} €</td>
                    <td class="col-pago">${dev.metodoPago}</td>
                    <td class="col-acciones">
                        <button class="btn-admin-accion btn-ver" onclick="verDetalleDevolucion(${dev.id})" title="Ver Detalles">
                            <i class="fas fa-eye"></i>
                        </button>
                    </td>
                </tr>`;
        });
        return filasHtml;
    };

    ejecutarCuandoIdle(generarFilasDevoluciones, (filasHtml) => {
        const paginacionHTML = getPaginacionDevolucionesHTML(totalPaginasDevoluciones);

        if (isFirstTime || busquedaTicket !== '') {
            contenedor.innerHTML = adminTablaHeaderHTML + filasHtml + '</tbody></table></div>' + paginacionHTML;
        } else {
            const tbody = contenedor.querySelector('tbody');
            if (tbody) {
                tbody.innerHTML = filasHtml;
                actualizarPaginacionDOM(contenedor, paginacionHTML);
            } else {
                contenedor.innerHTML = adminTablaHeaderHTML + filasHtml + '</tbody></table></div>' + paginacionHTML;
            }
        }

        const contador = document.getElementById('totalDevolucionesAviso');
        if (contador) contador.textContent = `${totalDevoluciones.toLocaleString('es-ES')} Devolución${totalDevoluciones !== 1 ? 'es' : ''}`;

        ajustarTodosInputsPaginacion();
    });
}

/**
 * Muestra los detalles de una devolución en un modal.
 */
function verDetalleDevolucion(id) {
    fetch('api/devoluciones.php?todas=1')
        .then(res => res.json())
        .then(data => {
            const lista = data.devoluciones || data;
            const dev = lista.find(d => d.id == id);
            if (!dev) { alert('No se encontró la devolución'); return; }

            // Guardar para el botón "Ver Ticket"
            window._ultimaDevAdmin = dev;
            window._todasDevolucionesAdmin = lista;

            const fecha = new Date(dev.fecha).toLocaleString('es-ES', {
                day: '2-digit', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit'
            });

            document.getElementById('verDevolucionId').textContent = dev.id;
            if (document.getElementById('verDevolucionTicket')) {
                document.getElementById('verDevolucionTicket').textContent = (dev.serie || 'T') + String(dev.numero || dev.idVenta || '—').padStart(5, '0');
            }
            document.getElementById('verDevolucionFecha').textContent = fecha;
            document.getElementById('verDevolucionProducto').textContent = dev.producto_nombre || '—';
            document.getElementById('verDevolucionCantidad').textContent = dev.cantidad;
            document.getElementById('verDevolucionImporte').textContent = '-' + parseFloat(dev.importeTotal).toFixed(2).replace('.', ',') + ' €';
            document.getElementById('verDevolucionMetodo').textContent = dev.metodoPago;
            document.getElementById('verDevolucionUsuario').textContent = dev.usuario_nombre || '—';

            abrirModal('modalVerDevolucion');
        })
        .catch(err => {
            console.error('Error al ver detalle de devolución:', err);
            alert('Error al cargar detalles');
        });
}

/**
 * Genera y abre un ticket de devolución completo agrupando todos los productos
 * devueltos en el mismo lote (mismo idVenta y misma fecha).
 */
function verTicketDevolucion(idDevolucion) {
    console.log('🔴 verTicketDevolucion llamada para id: ', idDevolucion);

    fetch('api/devoluciones.php?todas=1&_=' + Date.now(), {
        cache: 'no-store',
        headers: { 'Cache-Control': 'no-cache', 'Pragma': 'no-cache' }
    })
    .then(res => res.json())
    .then(data => {
        const todas = data.devoluciones || data || [];
        let dev = idDevolucion ? todas.find(d => d.id == idDevolucion) : window._ultimaDevAdmin;

        if (!dev) {
            alert('No hay datos de devolución');
            return;
        }

        // Agrupar todas las devoluciones del mismo lote (mismo idVenta y misma fecha)
        const lote = todas.filter(d => d.idVenta == dev.idVenta && d.fecha === dev.fecha);
        
        // Preparar carrito para generarHTMLComprobante
        const carrito = lote.map(linea => ({
            nombre: linea.producto_nombre || '—',
            cantidad: linea.cantidad,
            precio: parseFloat(linea.precioUnitario) || 0,
            iva: linea.iva || 21,
            importeTotal: parseFloat(linea.importeTotal) || 0
        }));

        const totalGeneral = carrito.reduce((sum, item) => sum + item.importeTotal, 0);

        // QR para Verifactu
        const nifTpv = (window.TPV_CONFIG && window.TPV_CONFIG.nif) ? window.TPV_CONFIG.nif : '';
        const serie = dev.serie || 'D'; // Devoluciones suelen ir en Serie D
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
        const ventana = window.open('', '_blank', 'width=400,height=600');
        ventana.document.write(html);
        ventana.document.close();
    })
    .catch(err => {
        console.error('Error al ver ticket de devolución:', err);
        alert('Error al cargar datos');
    });
}

/**
 * Busca devoluciones por ticket con debounce.
 */
function buscarDevolucionesPorTicket() {
    clearTimeout(debounceTimerDevoluciones);
    debounceTimerDevoluciones = setTimeout(() => {
        const busqueda = document.getElementById('busquedaTicketDevolucion')?.value || '';
        cargarDevolucionesAdmin('fecha_desc', busqueda);
    }, 300);
}