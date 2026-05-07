/**
 * admin-logs.js
 * Carga, renderizado, filtrado y detalle de Logs del sistema.
 * Depende de: admin-state.js, admin-utils.js, admin-pagination.js
 */

// ── CARGA Y RENDER ────────────────────────────────────────────────────────────

/**
 * Carga y muestra los logs del sistema.
 */
function cargarLogs(filtros = {}) {
    seccionActual = 'logs';
    adminTablaHeaderHTML = '';

    paginaActualLogs = filtros.pagina !== undefined ? filtros.pagina : 1;

    const contenedor = document.getElementById('adminContenido');
    contenedor.innerHTML = '<p style="text-align:center;padding:40px;color:var(--text-muted);">Cargando logs...</p>';

    const params = new URLSearchParams();
    const tipo = filtros.tipo !== undefined ? filtros.tipo : window.filtroTipoLog;
    const fecha = filtros.fecha !== undefined ? filtros.fecha : window.filtroFechaLog;

    if (tipo) params.set('tipo', tipo);
    if (fecha) params.set('fecha', fecha);
    params.set('pagina', paginaActualLogs);
    params.set('por_pagina', logsPorPagina);

    fetch('api/logs.php?' + params.toString())
        .then(res => res.json())
        .then(data => {
            window.logsData = data.logs || [];
            totalPaginasLogs = Math.ceil((data.total || 0) / logsPorPagina);
            renderLogs(data);
        })
        .catch(err => {
            console.error('Error cargando logs:', err);
            contenedor.innerHTML = '<p style="text-align:center;padding:40px;color:var(--error);">Error al cargar los logs</p>';
        });
}

/**
 * Renderiza la tabla de logs.
 */
function renderLogs(data) {
    const contenedor = document.getElementById('adminContenido');
    const logs = data.logs || [];

    const tiposLog = [
        { valor: '', texto: 'Todos los tipos' },
        { valor: 'login', texto: 'Inicios de sesión' },
        { valor: 'login_fallido', texto: 'Credenciales incorrectas' },
        { valor: 'logout', texto: 'Cierres de sesión' },
        { valor: 'venta', texto: 'Ventas' },
        { valor: 'devolucion', texto: 'Devoluciones' },
        { valor: 'apertura_caja', texto: 'Apertura de caja' },
        { valor: 'cierre_caja', texto: 'Cierre de caja' },
        { valor: 'retiro_caja', texto: 'Retiros de caja' },
        { valor: 'creacion_usuario', texto: 'Creación de usuarios' },
        { valor: 'modificacion_usuario', texto: 'Modificación de usuarios' },
        { valor: 'eliminacion_usuario', texto: 'Eliminación de usuarios' },
        { valor: 'creacion_producto', texto: 'Creación de productos' },
        { valor: 'modificacion_producto', texto: 'Modificación de productos' },
        { valor: 'eliminacion_producto', texto: 'Eliminación de productos' },
        { valor: 'creacion_categoria', texto: 'Creación de categorías' },
        { valor: 'modificacion_categoria', texto: 'Modificación de categorías' },
        { valor: 'eliminacion_categoria', texto: 'Eliminación de categorías' },
        { valor: 'acceso_admin', texto: 'Accesos al admin' }
    ];

    window.tiposLogMap = tiposLog;

    const tipoSelect = tiposLog.map(t =>
        `<option value="${t.valor}" ${(window.filtroTipoLog || '') === t.valor ? 'selected' : ''}>${t.texto}</option>`
    ).join('');

    let html = `
        <div class="logs-container">
            <div class="logs-filtros">
                <div class="logs-filtro-item">
                    <label>Tipo de evento:</label>
                    <select id="filtroTipoLog" onchange="aplicarFiltroLogs()">${tipoSelect}</select>
                </div>
                <div class="logs-filtro-item">
                    <label>Fecha:</label>
                    <div style="display:flex;align-items:center;gap:5px;">
                        <input type="date" id="filtroFecha" value="${window.filtroFechaLog || ''}" onchange="aplicarFiltroLogs()">
                        <button onclick="limpiarFiltroFecha()" style="padding:4px 8px;background:#6b7280;color:white;border:none;border-radius:4px;cursor:pointer;font-size:0.8rem;" title="Limpiar fecha">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <div class="logs-filtro-item" style="margin-left:auto;">
                    <button onclick="limpiarLogs()" style="background:#dc3545;color:white;padding:8px 16px;border:none;border-radius:5px;cursor:pointer;font-size:0.85rem;">
                        <i class="fas fa-trash"></i> Limpiar logs
                    </button>
                </div>
            </div>
            <div class="logs-tabla-container">
                <table class="admin-tabla logs-tabla">
                    <thead>
                        <tr>
                            <th>Fecha/Hora</th>
                            <th>Tipo</th>
                            <th>Usuario</th>
                            <th>Descripción</th>
                            <th>Detalles</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>`;

    if (logs.length === 0) {
        html += '<tr><td colspan="6" style="text-align:center;padding:40px;color:var(--text-muted);">No se encontraron logs</td></tr>';
    } else {
        logs.forEach(log => {
            const tipoIcono = getTipoLogIcono(log.tipo);
            const tipoClase = getTipoLogClase(log.tipo);
            const fecha = new Date(log.fecha).toLocaleString('es-ES');
            const detalles = log.detalles ? JSON.stringify(log.detalles) : '-';

            html += `
                <tr>
                    <td>${fecha}</td>
                    <td><span class="logs-tipo ${tipoClase}"><i class="${tipoIcono}"></i> ${getTipoLogTexto(log.tipo)}</span></td>
                    <td>${log.usuario_nombre || '-'}</td>
                    <td>${log.descripcion || '-'}</td>
                    <td style="font-size:0.8rem;max-width:200px;overflow:hidden;text-overflow:ellipsis;" title="${detalles}">${detalles}</td>
                    <td>
                        <button class="btn-admin-accion btn-ver" onclick="verDetalleLog(${log.id})" title="Ver detalles">
                            <i class="fas fa-eye"></i>
                        </button>
                    </td>
                </tr>`;
        });
    }

    html += `</tbody></table></div>${getPaginacionLogsHTML(totalPaginasLogs)}</div>`;
    contenedor.innerHTML = html;
    ajustarTodosInputsPaginacion();
}

// ── HELPERS DE TIPO ───────────────────────────────────────────────────────────

function getTipoLogIcono(tipo) {
    const iconos = {
        'login': 'fas fa-sign-in-alt', 'login_fallido': 'fas fa-times-circle',
        'logout': 'fas fa-sign-out-alt', 'venta': 'fas fa-shopping-cart',
        'devolucion': 'fas fa-undo-alt', 'apertura_caja': 'fas fa-cash-register',
        'cierre_caja': 'fas fa-money-check', 'retiro_caja': 'fas fa-money-bill-wave',
        'acceso_admin': 'fas fa-user-shield', 'acceso_cajero': 'fas fa-user',
        'acceso_login': 'fas fa-door-open', 'creacion_usuario': 'fas fa-user-plus',
        'modificacion_usuario': 'fas fa-user-edit', 'eliminacion_usuario': 'fas fa-user-minus',
        'creacion_producto': 'fas fa-box-plus', 'modificacion_producto': 'fas fa-box-open',
        'eliminacion_producto': 'fas fa-trash', 'creacion_categoria': 'fas fa-folder-plus',
        'modificacion_categoria': 'fas fa-folder-open', 'eliminacion_categoria': 'fas fa-folder-minus'
    };
    return iconos[tipo] || 'fas fa-info-circle';
}

function getTipoLogClase(tipo) {
    const clases = {
        'login': 'logs-login', 'login_fallido': 'logs-error', 'logout': 'logs-logout',
        'venta': 'logs-venta', 'devolucion': 'logs-retiro', 'apertura_caja': 'logs-caja',
        'cierre_caja': 'logs-caja', 'retiro_caja': 'logs-retiro', 'acceso_admin': 'logs-admin',
        'creacion_usuario': 'logs-usuario', 'modificacion_usuario': 'logs-usuario',
        'eliminacion_usuario': 'logs-usuario', 'creacion_producto': 'logs-producto',
        'modificacion_producto': 'logs-producto', 'eliminacion_producto': 'logs-producto',
        'creacion_categoria': 'logs-categoria', 'modificacion_categoria': 'logs-categoria',
        'eliminacion_categoria': 'logs-categoria'
    };
    return clases[tipo] || '';
}

function getTipoLogTexto(tipo) {
    const textos = {
        'login': 'Login', 'login_fallido': 'Credenciales incorrectas', 'logout': 'Logout',
        'venta': 'Venta', 'devolucion': 'Devolución', 'apertura_caja': 'Apertura Caja',
        'cierre_caja': 'Cierre Caja', 'retiro_caja': 'Retiro', 'acceso_admin': 'Acceso Admin',
        'acceso_cajero': 'Acceso Cajero', 'acceso_login': 'Acceso Login',
        'creacion_usuario': 'Usuario Creado', 'modificacion_usuario': 'Usuario Modificado',
        'eliminacion_usuario': 'Usuario Eliminado', 'creacion_producto': 'Producto Creado',
        'modificacion_producto': 'Producto Modificado', 'eliminacion_producto': 'Producto Eliminado',
        'creacion_categoria': 'Categoría Creada', 'modificacion_categoria': 'Categoría Modificada',
        'eliminacion_categoria': 'Categoría Eliminada'
    };
    return textos[tipo] || tipo;
}

// ── DETALLE Y ACCIONES ────────────────────────────────────────────────────────

/**
 * Muestra los detalles de un log en un modal dinámico.
 */
function verDetalleLog(idLog) {
    const log = window.logsData?.find(l => l.id === idLog);
    if (!log) { alert('No se encontraron los detalles del log'); return; }

    const tipoIcono = getTipoLogIcono(log.tipo);
    const tipoClase = getTipoLogClase(log.tipo);
    const fecha = new Date(log.fecha).toLocaleString('es-ES');
    
    // Simplificamos los colores usando variables de CSS del sistema
    let detallesHtml = `<p style="margin:5px 0; color: var(--text-muted); font-size: 0.9rem; font-style: italic;">Sin detalles adicionales</p>`;

    if (log.detalles && Object.keys(log.detalles).length > 0) {
        if (Array.isArray(log.detalles) && log.detalles.length > 0 && (log.detalles[0].campo || log.detalles[0].antes !== undefined)) {
            const isTypeCampo = log.detalles[0].campo !== undefined;
            detallesHtml = `<div style="overflow-x: auto; border-radius: 8px; border: 1px solid var(--border-main); margin-top: 5px;">
                <table style="width:100%; border-collapse: collapse; font-size: 0.85rem;">
                <tr style="background: var(--bg-secondary);">
                    <th style="padding: 10px; text-align: left; color: var(--text-muted); font-weight: 600;">Campo</th>
                    <th style="padding: 10px; text-align: left; color: var(--text-muted); font-weight: 600;">Anterior / Antes</th>
                    <th style="padding: 10px; text-align: left; color: var(--text-muted); font-weight: 600;">Nuevo / Después</th>
                </tr>`;
            log.detalles.forEach(cambio => {
                const valAnt = isTypeCampo ? (cambio.anterior ?? '(vacío)') : (cambio.antes ?? '(vacío)');
                const valNew = isTypeCampo ? (cambio.nuevo ?? '(vacío)') : (cambio.despues ?? '(vacío)');
                detallesHtml += `<tr style="border-bottom: 1px solid var(--border-main); background: var(--bg-main);">
                    <td style="padding: 10px; font-weight: 600; color: var(--text-main);">${cambio.campo || Object.keys(cambio)[0]}</td>
                    <td style="padding: 10px; color: #dc2626; text-decoration: line-through; opacity: 0.8;">${valAnt}</td>
                    <td style="padding: 10px; color: #059669; font-weight: 700;">${valNew}</td>
                </tr>`;
            });
            detallesHtml += '</table></div>';
        } else {
            detallesHtml = `<div style="overflow-x: auto; border-radius: 8px; border: 1px solid var(--border-main); margin-top: 5px;">
                <table style="width:100%; border-collapse: collapse; font-size: 0.85rem;">`;
            for (const [key, value] of Object.entries(log.detalles)) {
                let displayValue = typeof value === 'object' && value !== null
                    ? `<pre style="margin:0; font-family: monospace; font-size: 0.75rem;">${JSON.stringify(value, null, 2)}</pre>`
                    : (typeof value === 'number' ? parseFloat(value).toFixed(2).replace('.', ',') : value);
                detallesHtml += `<tr style="border-bottom: 1px solid var(--border-main); background: var(--bg-main);">
                    <td style="padding: 10px; font-weight: 600; width: 35%; color: var(--text-muted); background: var(--bg-secondary);">${key}</td>
                    <td style="padding: 10px; color: var(--text-main); word-break: break-all;">${displayValue}</td>
                </tr>`;
            }
            detallesHtml += '</table></div>';
        }
    }

    const modalContent = `
        <div class="modal-content modal-premium" style="max-width: 600px; padding: 0; overflow: hidden; width: 95%; background: var(--bg-panel);">
            <!-- Header Premium -->
            <div class="modal-header-premium" style="background: linear-gradient(135deg, #4f46e5, #7c3aed); padding: 25px 30px; text-align: left; position: relative; color: white;">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div style="background: rgba(255,255,255,0.2); width: 45px; height: 45px; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-list-alt" style="color: #fff; font-size: 1.5rem;"></i>
                    </div>
                    <div>
                        <h3 style="margin: 0; color: #fff; font-size: 1.4rem; font-weight: 700; letter-spacing: -0.5px;">Detalles del Log</h3>
                        <p style="margin: 3px 0 0 0; color: rgba(255,255,255,0.8); font-size: 0.9rem;">Referencia del sistema #${log.id}</p>
                    </div>
                </div>
                <button class="modal-close-btn" onclick="document.getElementById('modalDetalleLog').remove()" 
                    style="position: absolute; top: 25px; right: 25px; background: rgba(255,255,255,0.15); border: none; color: white; width: 32px; height: 32px; border-radius: 10px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div style="padding: 30px;">
                <div style="display: grid; grid-template-columns: 1fr; gap: 15px;">
                    <!-- Información Principal en Grid -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 10px;">
                        <div class="ver-prod-item-premium" style="padding: 12px; background: var(--bg-main); border: 1px solid var(--border-main); border-radius: 10px;">
                            <label style="display: block; font-size: 0.7rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase; margin-bottom: 5px;">Fecha / Hora</label>
                            <div style="display: flex; align-items: center; gap: 8px; color: var(--text-main); font-weight: 600;">
                                <i class="far fa-clock" style="color: #6366f1;"></i> ${fecha}
                            </div>
                        </div>
                        <div class="ver-prod-item-premium" style="padding: 12px; background: var(--bg-main); border: 1px solid var(--border-main); border-radius: 10px;">
                            <label style="display: block; font-size: 0.7rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase; margin-bottom: 5px;">Usuario Ejecutor</label>
                            <div style="display: flex; align-items: center; gap: 8px; color: var(--text-main); font-weight: 600;">
                                <i class="far fa-user" style="color: #10b981;"></i> ${log.usuario_nombre || 'Sistema'}
                            </div>
                        </div>
                    </div>

                    <div class="ver-prod-item-premium" style="padding: 12px; background: var(--bg-main); border: 1px solid var(--border-main); border-radius: 10px;">
                        <label style="display: block; font-size: 0.7rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase; margin-bottom: 5px;">Tipo de Evento</label>
                        <span class="logs-tipo ${tipoClase}" style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; border-radius: 20px; font-weight: 700; font-size: 0.8rem;">
                            <i class="${tipoIcono}"></i> ${getTipoLogTexto(log.tipo)}
                        </span>
                    </div>

                    <div class="ver-prod-item-premium" style="padding: 12px; background: var(--bg-main); border: 1px solid var(--border-main); border-radius: 10px;">
                        <label style="display: block; font-size: 0.7rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase; margin-bottom: 5px;">Descripción</label>
                        <div style="color: var(--text-main); font-weight: 500; line-height: 1.4;">${log.descripcion || '-'}</div>
                    </div>

                    <div class="ver-prod-item-premium">
                        <label style="display: block; font-size: 0.7rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase; margin-bottom: 8px;">Detalle Técnico / Cambios</label>
                        ${detallesHtml}
                    </div>
                </div>
            </div>

            <div style="padding: 20px 30px; background: var(--bg-panel); border-top: 1px solid var(--border-main); display: flex; justify-content: flex-end;">
                <button class="btn-modal-cancelar" onclick="document.getElementById('modalDetalleLog').remove()" 
                    style="margin: 0; padding: 12px 25px; border-radius: 10px; font-weight: 600; background: var(--bg-secondary); color: var(--text-muted); border: 1px solid var(--border-main); cursor: pointer; transition: all 0.2s;">
                    Cerrar Detalle
                </button>
            </div>
        </div>`;

    const existingModal = document.getElementById('modalDetalleLog');
    if (existingModal) existingModal.remove();

    const modalDiv = document.createElement('div');
    modalDiv.id = 'modalDetalleLog';
    modalDiv.className = 'modal-overlay';
    modalDiv.style.cssText = 'position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:10000; display:flex; justify-content:center; align-items:center; backdrop-filter: blur(4px);';

    modalDiv.innerHTML = modalContent;
    document.body.appendChild(modalDiv);
    
    // Animación suave de entrada
    const inner = modalDiv.querySelector('.modal-content');
    inner.style.opacity = '0';
    inner.style.transform = 'translateY(20px)';
    inner.style.transition = 'all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1)';
    
    requestAnimationFrame(() => {
        inner.style.opacity = '1';
        inner.style.transform = 'translateY(0)';
    });

    modalDiv.addEventListener('click', e => { if (e.target === modalDiv) modalDiv.remove(); });
}

/**
 * Aplica los filtros y recarga los logs.
 */
function aplicarFiltroLogs() {
    let tipoSeleccionado = document.getElementById('filtroTipoLog')?.value || '';
    const fecha = document.getElementById('filtroFecha')?.value || '';

    if (fecha && fecha.length < 10) return;

    window.filtroFechaLog = fecha;

    const tipoMultiMap = { 'login': 'login,login_fallido' };
    if (tipoMultiMap[tipoSeleccionado]) tipoSeleccionado = tipoMultiMap[tipoSeleccionado];

    window.filtroTipoLog = tipoSeleccionado;
    cargarLogs({ tipo: tipoSeleccionado, fecha });
}

/**
 * Limpia el filtro de fecha y recarga los logs.
 */
function limpiarFiltroFecha() {
    const fechaInput = document.getElementById('filtroFecha');
    if (fechaInput) fechaInput.value = '';
    window.filtroFechaLog = '';
    aplicarFiltroLogs();
}

/**
 * Limpia todos los logs del sistema.
 */
function limpiarLogs() {
    if (!confirm('¿Estás seguro de que quieres eliminar todos los logs? Esta acción no se puede deshacer.')) return;

    fetch('api/logs.php?accion=limpiar', { method: 'POST' })
        .then(res => res.json())
        .then(data => {
            if (data.ok) {
                alert('Logs eliminados correctamente');
                cargarLogs({});
            } else {
                alert('Error al eliminar los logs: ' + (data.error || 'Error desconocido'));
            }
        })
        .catch(err => {
            console.error('Error al limpiar logs:', err);
            alert('Error al conectar con el servidor');
        });
}