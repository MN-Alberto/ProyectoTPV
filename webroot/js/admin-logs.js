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
    const isDarkMode = document.body.classList.contains('dark-mode');

    const c = isDarkMode ? {
        background: '#1f2937', text: '#e5e7eb', textMuted: '#9ca3af', border: '#4b5563',
        headerBg: '#374151', buttonBg: '#4b5563', buttonText: '#f3f4f6',
        delete: '#f87171', add: '#34d399'
    } : {
        background: '#ffffff', text: '#333333', textMuted: '#666666', border: '#e0e0e0',
        headerBg: '#f3f4f6', buttonBg: '#e5e7eb', buttonText: '#333333',
        delete: '#dc2626', add: '#059669'
    };

    let detallesHtml = `<p style="margin:5px 0;color:${c.textMuted};">Sin detalles</p>`;

    if (log.detalles && Object.keys(log.detalles).length > 0) {
        if (Array.isArray(log.detalles) && log.detalles.length > 0 && log.detalles[0].campo) {
            detallesHtml = `<table style="width:100%;margin-top:5px;border-collapse:collapse;">
                <tr style="background:${c.headerBg};"><th style="padding:5px;text-align:left;font-size:0.85rem;color:${c.text};">Campo</th><th style="padding:5px;text-align:left;font-size:0.85rem;color:${c.text};">Valor Anterior</th><th style="padding:5px;text-align:left;font-size:0.85rem;color:${c.text};">Valor Nuevo</th></tr>`;
            log.detalles.forEach(cambio => {
                detallesHtml += `<tr style="border-bottom:1px solid ${c.border};">
                    <td style="padding:5px;font-weight:bold;color:${c.text};">${cambio.campo}</td>
                    <td style="padding:5px;color:${c.delete};text-decoration:line-through;">${cambio.anterior ?? '(vacío)'}</td>
                    <td style="padding:5px;color:${c.add};font-weight:bold;">${cambio.nuevo ?? '(vacío)'}</td>
                </tr>`;
            });
            detallesHtml += '</table>';
        } else if (Array.isArray(log.detalles) && log.detalles.length > 0 && log.detalles[0].antes !== undefined) {
            detallesHtml = `<table style="width:100%;margin-top:5px;border-collapse:collapse;">
                <tr style="background:${c.headerBg};"><th style="padding:5px;text-align:left;font-size:0.85rem;color:${c.text};">Campo</th><th style="padding:5px;text-align:left;font-size:0.85rem;color:${c.text};">Antes</th><th style="padding:5px;text-align:left;font-size:0.85rem;color:${c.text};">Después</th></tr>`;
            log.detalles.forEach(cambio => {
                detallesHtml += `<tr style="border-bottom:1px solid ${c.border};">
                    <td style="padding:5px;font-weight:bold;color:${c.text};">${cambio.campo || Object.keys(cambio)[0]}</td>
                    <td style="padding:5px;color:${c.delete};text-decoration:line-through;">${cambio.antes ?? '(vacío)'}</td>
                    <td style="padding:5px;color:${c.add};font-weight:bold;">${cambio.despues ?? '(vacío)'}</td>
                </tr>`;
            });
            detallesHtml += '</table>';
        } else {
            detallesHtml = `<table style="width:100%;margin-top:5px;border-collapse:collapse;">`;
            for (const [key, value] of Object.entries(log.detalles)) {
                let displayValue = typeof value === 'object' && value !== null
                    ? JSON.stringify(value, null, 2)
                    : (typeof value === 'number' ? parseFloat(value).toFixed(2).replace('.', ',') : value);
                detallesHtml += `<tr style="border-bottom:1px solid ${c.border};">
                    <td style="padding:5px;font-weight:bold;width:40%;color:${c.textMuted};">${key}</td>
                    <td style="padding:5px;color:${c.text};word-break:break-all;">${displayValue}</td>
                </tr>`;
            }
            detallesHtml += '</table>';
        }
    }

    const modalContent = `
        <div style="padding:20px;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px;">
                <h3 style="margin:0;color:${c.text};font-size:1.2rem;">Detalles del Log</h3>
                <button onclick="document.getElementById('modalDetalleLog').remove()"
                    style="background:${c.buttonBg};border:none;font-size:1.5rem;cursor:pointer;color:${c.buttonText};width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;">&times;</button>
            </div>
            <div style="color:${c.text};font-size:0.9rem;">
                <table style="width:100%;border-collapse:collapse;">
                    <tr style="border-bottom:1px solid ${c.border};"><td style="padding:8px 5px;font-weight:bold;color:${c.textMuted};width:35%;">ID</td><td style="padding:8px 5px;color:${c.text};">${log.id}</td></tr>
                    <tr style="border-bottom:1px solid ${c.border};"><td style="padding:8px 5px;font-weight:bold;color:${c.textMuted};">Fecha/Hora</td><td style="padding:8px 5px;color:${c.text};">${fecha}</td></tr>
                    <tr style="border-bottom:1px solid ${c.border};"><td style="padding:8px 5px;font-weight:bold;color:${c.textMuted};">Tipo</td><td style="padding:8px 5px;"><span class="logs-tipo ${tipoClase}"><i class="${tipoIcono}"></i> ${getTipoLogTexto(log.tipo)}</span></td></tr>
                    <tr style="border-bottom:1px solid ${c.border};"><td style="padding:8px 5px;font-weight:bold;color:${c.textMuted};">Usuario</td><td style="padding:8px 5px;color:${c.text};">${log.usuario_nombre || 'Sistema'} (ID: ${log.usuario_id || '-'})</td></tr>
                    <tr style="border-bottom:1px solid ${c.border};"><td style="padding:8px 5px;font-weight:bold;color:${c.textMuted};">Descripción</td><td style="padding:8px 5px;color:${c.text};">${log.descripcion || '-'}</td></tr>
                    <tr><td style="padding:8px 5px;font-weight:bold;color:${c.textMuted};vertical-align:top;">Detalles</td><td style="padding:8px 5px;">${detallesHtml}</td></tr>
                </table>
            </div>
            <div style="margin-top:20px;text-align:right;">
                <button onclick="document.getElementById('modalDetalleLog').remove()"
                    style="background:#3b82f6;color:white;padding:8px 20px;border:none;border-radius:5px;cursor:pointer;font-size:0.9rem;">Cerrar</button>
            </div>
        </div>`;

    const existingModal = document.getElementById('modalDetalleLog');
    if (existingModal) existingModal.remove();

    const modalDiv = document.createElement('div');
    modalDiv.id = 'modalDetalleLog';
    modalDiv.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.7);z-index:10000;display:flex;justify-content:center;align-items:center;';

    const modalInner = document.createElement('div');
    modalInner.style.cssText = `background:${c.background};border-radius:10px;width:90%;max-width:550px;max-height:85vh;overflow-y:auto;box-shadow:0 4px 20px rgba(0,0,0,0.5);color:${c.text};border:1px solid ${c.border};`;
    modalInner.innerHTML = modalContent;

    modalDiv.appendChild(modalInner);
    document.body.appendChild(modalDiv);
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