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
        { valor: '', texto: 'Todos los tipos', icono: 'fa-list' },
        { valor: 'login', texto: 'Inicios de sesión', icono: 'fa-sign-in-alt' },
        { valor: 'login_fallido', texto: 'Accesos fallidos', icono: 'fa-user-lock' },
        { valor: 'logout', texto: 'Cierres de sesión', icono: 'fa-sign-out-alt' },
        { valor: 'venta', texto: 'Ventas', icono: 'fa-shopping-cart' },
        { valor: 'devolucion', texto: 'Devoluciones', icono: 'fa-undo' },
        { valor: 'caja', texto: 'Movimientos de Caja', icono: 'fa-cash-register' },
        { valor: 'producto', texto: 'Productos', icono: 'fa-box' },
        { valor: 'usuario', texto: 'Usuarios', icono: 'fa-user-cog' }
    ];

    let html = `
        <div class="logs-modern-view animate-fade-in">
            ${getPremiumHeaderHTML('fa-history', 'Logs del Sistema', 'Auditoría completa de acciones y eventos de seguridad', 'linear-gradient(135deg, #1e293b, #334155)')}

            <div class="premium-filters-bar" style="display: flex; flex-wrap: wrap; gap: 15px; background: white; padding: 20px; border-radius: 16px; border: 1px solid #e2e8f0; margin-bottom: 25px; align-items: center; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                <div style="flex: 1; min-width: 250px;">
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 8px; margin-left: 5px;">Filtrar por Categoría</label>
                    <div style="position: relative;">
                        <i class="fas fa-filter" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 0.9rem;"></i>
                        <select id="filtroTipoLog" onchange="aplicarFiltroLogs()" style="width: 100%; padding: 12px 15px 12px 40px; border-radius: 10px; border: 1px solid #e2e8f0; background: #f8fafc; font-weight: 600; color: #1e293b; cursor: pointer; appearance: none;">
                            ${tiposLog.map(t => `<option value="${t.valor}" ${(window.filtroTipoLog || '') === t.valor ? 'selected' : ''}>${t.texto}</option>`).join('')}
                        </select>
                        <i class="fas fa-chevron-down" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); color: #94a3b8; pointer-events: none; font-size: 0.8rem;"></i>
                    </div>
                </div>

                <div style="width: 220px;">
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 8px; margin-left: 5px;">Filtrar por Fecha</label>
                    <div style="display: flex; gap: 8px;">
                        <input type="date" id="filtroFecha" value="${window.filtroFechaLog || ''}" onchange="aplicarFiltroLogs()" style="flex: 1; padding: 11px 15px; border-radius: 10px; border: 1px solid #e2e8f0; background: #f8fafc; font-weight: 600; color: #1e293b;">
                        <button onclick="limpiarFiltroFecha()" style="width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; border-radius: 10px; cursor: pointer; transition: all 0.2s;">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>

                <div style="margin-left: auto; padding-top: 22px;">
                    <button class="btn-premium-secondary" onclick="limpiarLogs()" style="background: #fef2f2; color: #dc2626 !important; border-color: #fee2e2;">
                        <i class="fas fa-trash-alt"></i> Vaciar Historial
                    </button>
                </div>
            </div>

            <div class="premium-card" style="padding: 0; overflow: hidden; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05); background: white;">
                <table class="premium-table" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                            <th style="padding: 15px 20px; text-align: left; font-size: 0.75rem; font-weight: 800; color: #64748b; text-transform: uppercase; width: 180px;">Fecha y Hora</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 0.75rem; font-weight: 800; color: #64748b; text-transform: uppercase; width: 150px;">Tipo de Evento</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 0.75rem; font-weight: 800; color: #64748b; text-transform: uppercase; width: 150px;">Usuario</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 0.75rem; font-weight: 800; color: #64748b; text-transform: uppercase;">Descripción de Actividad</th>
                            <th style="padding: 15px 20px; text-align: center; font-size: 0.75rem; font-weight: 800; color: #64748b; text-transform: uppercase; width: 100px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>`;

    if (logs.length === 0) {
        html += `
            <tr>
                <td colspan="5" style="padding: 80px 20px; text-align: center;">
                    <div style="background: #f8fafc; width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; color: #cbd5e1; font-size: 1.5rem;">
                        <i class="fas fa-history"></i>
                    </div>
                    <p style="margin: 0; font-weight: 600; color: #64748b;">No se encontraron registros en el historial</p>
                    <p style="margin: 5px 0 0 0; font-size: 0.85rem; color: #94a3b8;">Pruebe ajustando los filtros de búsqueda</p>
                </td>
            </tr>`;
    } else {
        logs.forEach(log => {
            const fecha = new Date(log.fecha);
            const fechaFormat = fecha.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' });
            const horaFormat = fecha.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
            
            const tipoIcono = getTipoLogIcono(log.tipo);
            const tipoClase = getTipoLogClase(log.tipo);
            const tipoPill = `
                <div class="log-pill ${tipoClase}" style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 700;">
                    <i class="${tipoIcono}"></i> ${getTipoLogTexto(log.tipo)}
                </div>`;

            html += `
                <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.2s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                    <td style="padding: 15px 20px;">
                        <div style="font-weight: 700; color: #1e293b; font-size: 0.9rem;">${fechaFormat}</div>
                        <div style="font-size: 0.75rem; color: #94a3b8; font-weight: 600;">${horaFormat}</div>
                    </td>
                    <td style="padding: 15px 20px;">${tipoPill}</td>
                    <td style="padding: 15px 20px;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <div style="width: 28px; height: 28px; border-radius: 50%; background: #e0e7ff; color: #4f46e5; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; font-weight: 800;">
                                ${log.usuario_nombre ? log.usuario_nombre.charAt(0).toUpperCase() : 'S'}
                            </div>
                            <span style="font-weight: 600; color: #475569; font-size: 0.85rem;">${log.usuario_nombre || 'Sistema'}</span>
                        </div>
                    </td>
                    <td style="padding: 15px 20px;">
                        <div style="font-weight: 500; color: #334155; font-size: 0.9rem; line-height: 1.4;">${log.descripcion || '-'}</div>
                    </td>
                    <td style="padding: 15px 20px; text-align: center;">
                        <button class="log-detail-btn" onclick="verDetalleLog(${log.id})" style="width: 36px; height: 36px; border-radius: 10px; border: 1px solid #e2e8f0; background: white; color: #6366f1; cursor: pointer; transition: all 0.2s; display: inline-flex; align-items: center; justify-content: center;" onmouseover="this.style.background='#6366f1'; this.style.color='white'" onmouseout="this.style.background='white'; this.style.color='#6366f1'">
                            <i class="fas fa-eye"></i>
                        </button>
                    </td>
                </tr>`;
        });
    }

    html += `
                    </tbody>
                </table>
            </div>
            
            <div style="margin-top: 20px; display: flex; justify-content: center;">
                ${getPaginacionLogsHTML(totalPaginasLogs)}
            </div>

            <style>
                .log-pill.logs-login { background: #ecfdf5; color: #059669; }
                .log-pill.logs-error { background: #fef2f2; color: #dc2626; }
                .log-pill.logs-logout { background: #f8fafc; color: #64748b; }
                .log-pill.logs-venta { background: #eff6ff; color: #2563eb; }
                .log-pill.logs-retiro { background: #fff7ed; color: #ea580c; }
                .log-pill.logs-caja { background: #f5f3ff; color: #7c3aed; }
                .log-pill.logs-admin { background: #1e293b; color: white; }
                .log-pill.logs-usuario { background: #fdf2f8; color: #db2777; }
                .log-pill.logs-producto { background: #f0fdfa; color: #0d9488; }
                .log-pill.logs-categoria { background: #fefce8; color: #ca8a04; }
                
                body.dark-mode .premium-filters-bar { background: #111827 !important; border-color: #1e293b !important; }
                body.dark-mode .premium-filters-bar select,
                body.dark-mode .premium-filters-bar input { background: #1e293b !important; border-color: #374151 !important; color: #f1f5f9 !important; }
                body.dark-mode .premium-card { background: #111827 !important; border-color: #1e293b !important; }
                body.dark-mode .premium-table thead tr { background: #1f2937 !important; border-color: #374151 !important; }
                body.dark-mode .premium-table td { border-color: #1e293b !important; }
                body.dark-mode .log-detail-btn { background: #1e293b !important; border-color: #374151 !important; }
            </style>
        </div>`;
        
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

            <div style="padding: 25px 30px; background: var(--bg-panel); border-top: 1px solid var(--border-main); display: flex; justify-content: flex-end;">
                <button class="btn-premium-primary" onclick="document.getElementById('modalDetalleLog').remove()" style="padding: 10px 25px; font-size: 0.9rem;">
                    Entendido
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