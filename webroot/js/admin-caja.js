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
    const contador = `${totalRetiros} Retiro${totalRetiros !== 1 ? 's' : ''}`;
    const opt = (val, actual, label) => `<option value="${val}" ${actual === val ? 'selected' : ''}>${label}</option>`;
    return `
        ${getPremiumHeaderHTML('fa-hand-holding-usd', 'Retiros de Caja', 'Control de salidas de efectivo y gastos operativos', 'linear-gradient(135deg, #ef4444, #dc2626)')}
        <div class="admin-tabla-header products-header">
            <div class="header-filters-grid sales-optimized-grid">
                <div class="filter-main">
                    <div class="search-wrapper">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" id="busquedaRetiroId" class="input-modern-search"
                            placeholder="Buscar por ID o motivo..."
                            oninput="buscarRetirosAdmin()" autocomplete="off">
                    </div>
                </div>

                <div class="filter-secondary">
                    <div class="select-modern-wrapper">
                        <select id="retirosOrdenar" class="select-modern" onchange="cargarRetirosAdmin(this.value)">
                            ${opt('fecha_desc', orden, 'Más recientes')}${opt('fecha_asc', orden, 'Más antiguos')}
                            ${opt('importe_desc', orden, 'Mayor importe')}${opt('importe_asc', orden, 'Menor importe')}
                        </select>
                    </div>
                </div>

                <div class="header-status-info">
                    <span id="totalRetirosAviso" class="info-tag">${contador}</span>
                </div>
            </div>
        </div>
        <div class="admin-tabla-wrapper products-table-wrapper">
            <table class="admin-tabla">
                <thead><tr>
                    <th style="width: 80px;"># ID</th>
                    <th style="width: 170px;">Fecha y Hora</th>
                    <th>Usuario / Vendedor</th>
                    <th style="width: 140px; text-align:right;">Importe</th>
                    <th>Motivo / Concepto</th>
                    <th style="width: 120px; text-align:center;">Sesión</th>
                </tr></thead>
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
        day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit'
    });
    const importeVal = parseFloat(retiro.importe);
    const importeStr = importeVal.toFixed(2).replace('.', ',');
    
    return `
        <tr class="withdrawal-row">
            <td class="col-id">
                <span class="id-badge">#${retiro.id}</span>
            </td>
            <td class="col-fecha">
                <div class="date-info">
                    <i class="far fa-calendar-alt"></i>
                    <span>${fecha}</span>
                </div>
            </td>
            <td class="col-usuario">
                <div class="user-profile-small" style="display: flex; align-items: center; gap: 8px;">
                    <div class="user-avatar-xs" style="width:24px; height:24px; background:#f1f5f9; color:#64748b; border-radius:6px; display:flex; align-items:center; justify-content:center; font-size:0.7rem; font-weight:800;">
                        ${(retiro.usuario_nombre || 'U').charAt(0).toUpperCase()}
                    </div>
                    <span style="font-size: 0.85rem; color: #475569; font-weight: 600;">${retiro.usuario_nombre || '—'}</span>
                </div>
            </td>
            <td class="col-importe" style="text-align:right;">
                <span class="amount-badge negative" style="font-weight:800; font-size:1rem; color: #ef4444; background: rgba(239, 68, 68, 0.08); padding: 4px 10px; border-radius: 8px;">
                    -${importeStr} €
                </span>
            </td>
            <td class="col-motivo">
                <span class="reason-text" style="color: #64748b; font-size: 0.85rem;">${retiro.motivo || '—'}</span>
            </td>
            <td class="col-sesion" style="text-align:center;">
                <span class="session-tag" style="background: #f1f5f9; color: #475569; padding: 2px 8px; border-radius: 6px; font-weight: 700; font-size: 0.75rem;">
                    S-${retiro.idCajaSesion || '—'}
                </span>
            </td>
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
        ${getPremiumHeaderHTML('fa-cash-register', 'Sesiones de Caja', 'Historial de aperturas, cierres y arqueos de efectivo', 'linear-gradient(135deg, #10b981, #059669)')}
        <div class="admin-tabla-header caja-sesiones-header">
            <div class="ventas-filtros">
                <div class="filtro-group">
                    <label for="cajaSesionesOrdenar"><i class="fas fa-sort-amount-down"></i> Ordenar por:</label>
                    <select id="cajaSesionesOrdenar" class="filtro-select" onchange="cargarCajaSesionesAdmin(this.value)">
                        <option value="fecha_desc" ${orden === 'fecha_desc' ? 'selected' : ''}>Más recientes</option>
                        <option value="fecha_asc" ${orden === 'fecha_asc' ? 'selected' : ''}>Más antiguos</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="admin-tabla-wrapper sessions-table-wrapper">
            <table class="admin-tabla">
                <thead>
                    <tr>
                        <th style="width:10%;">U. Apertura</th>
                        <th style="width:10%;">U. Cierre</th>
                        <th style="width:12%; text-align:center;">Apertura</th>
                        <th style="width:12%; text-align:center;">Cierre</th>
                        <th style="width:8%; text-align:right;">Fondo Ini.</th>
                        <th style="width:9%; text-align:right;">Efectivo</th>
                        <th style="width:7%; text-align:right;">Cambio</th>
                        <th style="width:9%; text-align:right;">Retiros</th>
                        <th style="width:10%; text-align:right;">Devol.</th>
                        <th style="width:13%; text-align:right;">Arqueo</th>
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
    const fmtFecha = (f) => f ? new Date(f).toLocaleString('es-ES', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' }) : '—';
    const fmtEur = (v) => v != null ? parseFloat(v).toFixed(2).replace('.', ',') + ' €' : '—';

    const desajuste = sesion.desajuste != null ? parseFloat(sesion.desajuste) : null;
    let badgeArqueo = '—';
    if (desajuste !== null) {
        const cls = desajuste === 0 ? 'arqueo-ok' : (desajuste > 0 ? 'arqueo-sobrente' : 'arqueo-faltante');
        const icon = desajuste === 0 ? 'check-circle' : (desajuste > 0 ? 'plus-circle' : 'minus-circle');
        badgeArqueo = `<span class="badge-arqueo ${cls}"><i class="fas fa-${icon}"></i> ${fmtEur(desajuste)}</span>`;
    }

    return `
        <tr class="sesion-row">
            <td style="font-weight: 600; color: #3b82f6;"><i class="fas fa-sign-in-alt" style="font-size:0.75rem; opacity:0.7;"></i> ${sesion.usuario_nombre || '—'}</td>
            <td style="font-weight: 600; color: #64748b;"><i class="fas fa-sign-out-alt" style="font-size:0.75rem; opacity:0.7;"></i> ${sesion.usuario_cierre_nombre || '—'}</td>
            <td style="text-align:center; font-size: 0.85rem; color: #64748b;">${fmtFecha(sesion.fechaApertura)}</td>
            <td style="text-align:center; font-size: 0.85rem; color: #64748b;">${fmtFecha(sesion.fechaCierre)}</td>
            <td style="text-align:right; font-weight: 600;">${fmtEur(sesion.importeInicial)}</td>
            <td style="text-align:right; font-weight: 700; color: #3b82f6;">${fmtEur(sesion.importeActual)}</td>
            <td style="text-align:right; color: #64748b;">${fmtEur(sesion.cambio)}</td>
            <td style="text-align:right; color: #dc2626;">-${fmtEur(sesion.totalRetiros)}</td>
            <td style="text-align:right; color: #dc2626;">-${fmtEur(sesion.totalDevoluciones)}</td>
            <td style="text-align:right;">${badgeArqueo}</td>
        </tr>`;
}

// ── DEVOLUCIONES ──────────────────────────────────────────────────────────────

/**
 * Genera el HTML del header de la tabla de devoluciones.
 */
function getDevolucionesTablaHeader(orden = 'fecha_desc', busquedaTicket = '', totalDevoluciones = 0) {
    const contador = `${totalDevoluciones} Devolución${totalDevoluciones !== 1 ? 'es' : ''}`;
    const opt = (val, actual, label) => `<option value="${val}" ${actual === val ? 'selected' : ''}>${label}</option>`;
    return `
        ${getPremiumHeaderHTML('fa-undo-alt', 'Gestión de Devoluciones', 'Tramitación de reembolsos y gestión de tickets anulados', 'linear-gradient(135deg, #6b7280, #374151)')}
        <div class="admin-tabla-header products-header">
            <div class="header-filters-grid sales-optimized-grid">
                <div class="filter-main">
                    <div class="search-wrapper">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" id="busquedaTicketDevolucion" class="input-modern-search"
                            placeholder="Buscar por ticket..."
                            oninput="buscarDevolucionesPorTicket()" autocomplete="off"
                            value="${(busquedaTicket || '').replace(/"/g, '&quot;')}">
                    </div>
                </div>

                <div class="filter-secondary">
                    <div class="select-modern-wrapper">
                        <select id="devolucionesOrdenar" class="select-modern" 
                            onchange="cargarDevolucionesAdmin(this.value, document.getElementById('busquedaTicketDevolucion').value)">
                            ${opt('fecha_desc', orden, 'Más recientes')}${opt('fecha_asc', orden, 'Más antiguos')}
                            ${opt('importe_desc', orden, 'Mayor importe')}${opt('importe_asc', orden, 'Menor importe')}
                        </select>
                    </div>
                </div>

                <div class="header-status-info">
                    <span id="totalDevolucionesAviso" class="info-tag">${contador}</span>
                </div>
            </div>
        </div>
        <div class="admin-tabla-wrapper products-table-wrapper">
            <table class="admin-tabla">
                <thead><tr>
                    <th style="width: 100px;">Ticket Orig.</th>
                    <th style="width: 150px;">Fecha / Hora</th>
                    <th>Vendedor</th>
                    <th>Producto</th>
                    <th style="width: 60px; text-align:center;">Cant.</th>
                    <th style="width: 110px; text-align:right;">Importe</th>
                    <th style="width: 110px;">Pago</th>
                    <th style="width: 100px; text-align:center;">Acciones</th>
                </tr></thead>
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
    devolucionesListData = devoluciones || [];

    if (!devoluciones || devoluciones.length === 0) {
        if (isFirstTime || adminTablaHeaderHTML === '') {
            adminTablaHeaderHTML = getDevolucionesTablaHeader(orden, busquedaTicket, totalDevoluciones);
            contenedor.innerHTML = adminTablaHeaderHTML +
                '<tr><td colspan="8" class="sin-productos">No hay devoluciones registradas.</td></tr></tbody></table></div>';
        } else {
            const tbody = contenedor.querySelector('tbody');
            if (tbody) tbody.innerHTML = '<tr><td colspan="8" class="sin-productos">No hay devoluciones registradas.</td></tr>';
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
                day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit'
            });
            const totalImp = parseFloat(dev.importeTotal).toFixed(2).replace('.', ',');

            filasHtml += `
                <tr class="return-row">
                    <td class="col-id">
                        <div class="sale-id-group">
                            <span class="sale-serie">${dev.orig_serie || 'T'}</span>
                            <span class="sale-num">${String(dev.orig_numero || dev.idVenta || '—').padStart(5, '0').slice(-5)}</span>
                        </div>
                    </td>
                    <td class="col-fecha">
                        <div class="date-info">
                            <i class="far fa-calendar-alt"></i>
                            <span>${fecha}</span>
                        </div>
                    </td>
                    <td class="col-usuario">
                        <div class="user-profile-small" style="display: flex; align-items: center; gap: 8px;">
                            <div class="user-avatar-xs" style="width:24px; height:24px; background:#f1f5f9; color:#64748b; border-radius:6px; display:flex; align-items:center; justify-content:center; font-size:0.7rem; font-weight:800;">
                                ${(dev.usuario_nombre || 'U').charAt(0).toUpperCase()}
                            </div>
                            <span style="font-size: 0.85rem; color: #475569; font-weight: 600;">${dev.usuario_nombre || '—'}</span>
                        </div>
                    </td>
                    <td class="col-producto">
                        <span class="product-name-text" style="font-size: 0.85rem; font-weight:600; color:#1e293b;">${dev.producto_nombre || '—'}</span>
                    </td>
                    <td class="col-cantidad" style="text-align:center;">
                        <span class="count-tag">${dev.cantidad}</span>
                    </td>
                    <td class="col-total" style="text-align:right;">
                        <span class="amount-badge negative" style="font-weight:800; font-size:1rem; color: #ef4444; background: rgba(239, 68, 68, 0.08); padding: 4px 10px; border-radius: 8px;">
                            -${totalImp} €
                        </span>
                    </td>
                    <td class="col-pago">
                        <div class="pago-wrapper">
                            <i class="fas fa-wallet pago-icon"></i>
                            <span>${dev.metodoPago}</span>
                        </div>
                    </td>
                    <td class="col-acciones" style="text-align:center;">
                        <div class="actions-group">
                            <button class="action-btn btn-view" onclick="verDetalleDevolucion(${dev.id})" title="Ver Detalles">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
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
                document.getElementById('verDevolucionTicket').textContent = (dev.orig_serie || 'T') + String(dev.orig_numero || dev.idVenta || '—').padStart(5, '0').slice(-5);
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
    let dev = idDevolucion ? null : window._ultimaDevAdmin;
    
    // Si no tenemos el objeto dev, no podemos saber el idVenta original para la API detalle
    if (!dev && !idDevolucion) {
        alert('No hay datos de devolución');
        return;
    }

    // Si tenemos el ID pero no el objeto, lo buscamos en el listado global (si existe)
    if (!dev && idDevolucion) {
        dev = typeof devolucionesListData !== 'undefined' ? devolucionesListData.find(d => d.id == idDevolucion) : null;
    }

    // Si seguimos sin dev, error
    if (!dev) {
        alert('No se pudieron recuperar los datos base de la devolución');
        return;
    }

    const idVentaOriginal = dev.idVenta;
    const fechaReferencia = dev.fecha;

    fetch(`api/devoluciones.php?detalleVenta=${idVentaOriginal}`)
        .then(res => res.json())
        .then(data => {
            if (!data || data.length === 0) { alert('No se encontraron detalles'); return; }
            
            const lote = data.filter(d => d.fecha === fechaReferencia);
            if (lote.length === 0) { alert('No se encontraron productos para este lote'); return; }

            const devInfo = lote[0];

            const carrito = lote.map(linea => ({
                nombre: linea.producto_nombre || '—',
                cantidad: linea.cantidad,
                precio: parseFloat(linea.precioUnitario) || 0,
                iva: linea.iva || 21,
                importeTotal: parseFloat(linea.importeTotal) || 0
            }));

            const totalGeneral = carrito.reduce((sum, item) => sum + item.importeTotal, 0);

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
                usuario_nombre: dev.usuario_nombre || devInfo.usuario_nombre,
                qrUrl: devInfo.qrUrl
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