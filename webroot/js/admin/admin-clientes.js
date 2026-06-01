/**
 * admin-clientes.js
 * Carga, renderizado y CRUD de Clientes y Proveedores en el panel de administración.
 * Depende de: admin-state.js, admin-utils.js, admin-pagination.js
 */

// ── CLIENTES ──────────────────────────────────────────────────────────────────

/**
 * Genera el HTML del header de la tabla de clientes con buscador.
 */
function getClientesTablaHeader(textoBusqueda = '', totalClientes = 0, totalInactivos = 0) {
    const totalActivos = totalClientes - totalInactivos;
    const hayBusqueda = textoBusqueda && textoBusqueda.trim() !== '';
    const contador = hayBusqueda
        ? `${totalClientes} Resultado${totalClientes !== 1 ? 's' : ''}`
        : `${totalClientes} Total | ${totalActivos} Act. | ${totalInactivos} Inact.`;

    return `
        ${getPremiumHeaderHTML('fa-user-tie', 'Gestión de Clientes', 'Base de datos de clientes y programas de fidelización', 'linear-gradient(135deg, #f59e0b, #d97706)')}
        <div class="admin-tabla-header products-header">
            <div class="header-filters-grid sales-optimized-grid">
                <div class="filter-main">
                    <div class="search-wrapper">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" id="inputBuscarCliente" class="input-modern-search"
                            placeholder="Buscar por DNI o nombre..."
                            oninput="buscarClientes()" autocomplete="off"
                            value="${textoBusqueda.replace(/"/g, '&quot;')}">
                    </div>
                </div>

                <div class="header-status-info">
                    <span id="totalClientesAviso" class="info-tag">${contador}</span>
                </div>

                <div class="header-actions">
                    <button class="btn-modern btn-primary" onclick="nuevoCliente()">
                        <i class="fas fa-plus"></i>
                        <span>Nuevo Cliente</span>
                    </button>
                </div>
            </div>
        </div>
        <div class="admin-tabla-wrapper products-table-wrapper">
            <table class="admin-tabla">
                <thead><tr>
                    <th style="width: 110px;">DNI / ID</th>
                    <th>Cliente</th>
                    <th style="width: 140px;">Fecha Alta</th>
                    <th style="width: 80px; text-align:center;">Prod.</th>
                    <th style="width: 80px; text-align:center;">Comp.</th>
                    <th style="width: 80px; text-align:center;">Puntos</th>
                    <th style="width: 100px;">Estado</th>
                    <th style="width: 120px; text-align:center;">Acciones</th>
                </tr></thead>
                <tbody>`;
}

/**
 * Carga los clientes desde la API y los renderiza en la tabla.
 */
function cargarClientesAdmin(textoBusqueda = '', resetPagina = true) {
    const contenedor = document.getElementById('adminContenido');
    const tablaExistente = contenedor.querySelector('.admin-tabla');

    if (seccionActual !== 'clientes') {
        adminTablaHeaderHTML = '';
        seccionActual = 'clientes';
    }

    const esPrimeraVez = !tablaExistente || adminTablaHeaderHTML === '';

    if (esPrimeraVez) {
        adminTablaHeaderHTML = '';
    }

    if (resetPagina) {
        paginaActualClientes = 1;
    }

    busquedaClienteActual = textoBusqueda;

    const params = new URLSearchParams();
    if (textoBusqueda) params.append('dni', textoBusqueda);
    params.append('pagina', paginaActualClientes);
    params.append('porPagina', clientesPorPagina);

    if (esPrimeraVez) {
        contenedor.innerHTML = '<div style="text-align:center;padding:60px 20px;"><i class="fas fa-spinner fa-spin" style="font-size:2rem;color:var(--color-primary);"></i><p style="margin-top:15px;color:var(--text-secondary);">Cargando clientes...</p></div>';
    } else {
        const tbody = contenedor.querySelector('tbody');
        if (tbody) {
            tbody.innerHTML = '<tr><td colspan="10" class="sin-productos" style="text-align:center;"><i class="fas fa-spinner fa-spin"></i> Cargando...</td></tr>';
        }
    }

    return fetch('api/clientes.php?' + params.toString())
        .then(res => {
            if (!res.ok) return res.json().then(err => { throw new Error(err.error || 'Error al cargar clientes'); });
            return res.json();
        })
        .then(data => renderClientesAdmin(data, esPrimeraVez))
        .catch(err => {
            console.error('Error cargando clientes:', err);
            document.getElementById('adminContenido').innerHTML = '<p class="sin-productos">' + err.message + '</p>';
        });
}

function verCliente(id) {
    const cli = (typeof clientesData !== 'undefined' ? clientesData : []).find(c => c.id == id);
    if (!cli) return alert('No se encontró el cliente en la lista local');

    const dni = cli.dni || '—';
    const nombre = cli.nombre || '—';
    const apellidos = cli.apellidos || '—';
    const direccion = cli.direccion || '—';
    const fechaAlta = cli.fecha_alta ? new Date(cli.fecha_alta).toLocaleDateString('es-ES') : '—';
    const productosComprados = cli.productos_comprados || 0;
    const comprasRealizadas = cli.compras_realizadas || 0;
    const puntos = cli.puntos || 0;
    const estado = cli.activo == 1 ? 'Activo' : 'Inactivo';

    const modal = document.createElement('div');
    modal.id = 'modalVerCliente';
    modal.className = 'modal-overlay';
    modal.style.display = 'flex';

    const statusPill = cli.activo == 1 
        ? '<span class="status-pill status-active">Activo</span>'
        : '<span class="status-pill status-inactive">Inactivo</span>';

    modal.innerHTML = `
        <div class="modal-content modal-premium" style="max-width: 520px; padding: 0; overflow: hidden; width: 90%;">
            <div class="modal-header-premium" style="background: linear-gradient(135deg, #3b82f6, #1d4ed8); padding: 20px 25px; text-align: left; position: relative;">
                <h3 style="margin: 0; color: #fff; font-size: 1.3rem;">Detalles del Cliente</h3>
                <p class="modal-subtitulo" style="margin: 5px 0 0 0; color: rgba(255,255,255,0.8); font-size: 0.85rem;">Información completa del registro</p>
                <button class="modal-close-btn" onclick="document.getElementById('modalVerCliente').remove();" style="position: absolute; top: 20px; right: 20px; background: rgba(255,255,255,0.2); border: none; color: white; width: 30px; height: 30px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div style="padding: 25px;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 25px;">
                    <div class="ver-prod-item-premium">
                        <label style="display: block; font-size: 0.75rem; color: #6b7280; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">DNI / Identificación</label>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-id-card" style="color: #3b82f6; width: 16px;"></i>
                            <span style="font-size: 1.05rem; font-weight: 700; color: #1f2937;">${dni}</span>
                        </div>
                    </div>
                    <div class="ver-prod-item-premium">
                        <label style="display: block; font-size: 0.75rem; color: #6b7280; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">Nombre</label>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-user" style="color: #3b82f6; width: 16px;"></i>
                            <span style="font-size: 1.05rem; font-weight: 700; color: #1f2937;">${nombre}</span>
                        </div>
                    </div>
                    <div class="ver-prod-item-premium">
                        <label style="display: block; font-size: 0.75rem; color: #6b7280; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">Apellidos</label>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-user-friends" style="color: #8b5cf6; width: 16px;"></i>
                            <span style="font-size: 1rem; color: #4b5563;">${apellidos}</span>
                        </div>
                    </div>
                    <div class="ver-prod-item-premium">
                        <label style="display: block; font-size: 0.75rem; color: #6b7280; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">Dirección</label>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-map-marker-alt" style="color: #f59e0b; width: 16px;"></i>
                            <span style="font-size: 1rem; color: #4b5563;">${direccion}</span>
                        </div>
                    </div>
                    <div class="ver-prod-item-premium">
                        <label style="display: block; font-size: 0.75rem; color: #6b7280; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">Fecha de Alta</label>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-calendar-alt" style="color: #10b981; width: 16px;"></i>
                            <span style="font-size: 1rem; color: #4b5563;">${fechaAlta}</span>
                        </div>
                    </div>
                    <div class="ver-prod-item-premium">
                        <label style="display: block; font-size: 0.75rem; color: #6b7280; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">Estado</label>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            ${statusPill}
                        </div>
                    </div>
                </div>

                <div style="background: #f8fafc; border-radius: 12px; border: 1px solid #e2e8f0; padding: 18px 20px; margin-bottom: 25px;">
                    <h4 style="margin: 0 0 12px 0; font-size: 0.85rem; color: #374151; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-chart-simple" style="color: #6366f1;"></i> Actividad Comercial
                    </h4>
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px;">
                        <div style="text-align: center; padding: 10px; background: #fff; border-radius: 8px; border: 1px solid #e5e7eb;">
                            <span style="display: block; font-size: 0.65rem; color: #6b7280; font-weight: 600; text-transform: uppercase; margin-bottom: 4px;">Productos</span>
                            <span style="display: block; font-size: 1.3rem; font-weight: 800; color: #3b82f6;">${productosComprados}</span>
                        </div>
                        <div style="text-align: center; padding: 10px; background: #fff; border-radius: 8px; border: 1px solid #e5e7eb;">
                            <span style="display: block; font-size: 0.65rem; color: #6b7280; font-weight: 600; text-transform: uppercase; margin-bottom: 4px;">Compras</span>
                            <span style="display: block; font-size: 1.3rem; font-weight: 800; color: #10b981;">${comprasRealizadas}</span>
                        </div>
                        <div style="text-align: center; padding: 10px; background: #fff; border-radius: 8px; border: 1px solid #e5e7eb;">
                            <span style="display: block; font-size: 0.65rem; color: #6b7280; font-weight: 600; text-transform: uppercase; margin-bottom: 4px;">Puntos</span>
                            <span style="display: block; font-size: 1.3rem; font-weight: 800; color: #f59e0b;">${puntos.toLocaleString('es-ES')}</span>
                        </div>
                    </div>
                </div>

                <div style="display: flex; justify-content: center; gap: 15px; border-top: 1px solid #e5e7eb; padding-top: 20px;">
                    <button class="btn-modern btn-primary"
                        onclick="document.getElementById('modalVerCliente').remove();verComprasCliente('${dni}')"
                        style="min-width: 180px;">
                        <i class="fas fa-shopping-bag"></i> Ver Historial
                    </button>
                    <button class="btn-modern"
                        onclick="document.getElementById('modalVerCliente').remove();"
                        style="min-width: 100px; background: #f1f5f9; color: #475569;">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>`;

    document.getElementById('modalVerCliente')?.remove();
    document.body.appendChild(modal);
}

/**
 * Renderiza la respuesta paginada de clientes en formato tabla.
 */
function renderClientesAdmin(respuesta, esPrimeraVez = true) {
    const contenedor = document.getElementById('adminContenido');

    const clientes = respuesta.clientes || [];
    clientesData = clientes;
    totalPaginasClientes = respuesta.totalPaginas || 1;
    totalClientes = respuesta.total || 0;
    const totalTodosClientes = respuesta.totalTodos || respuesta.total || 0;
    const totalInactivos = respuesta.totalInactivos || 0;
    paginaActualClientes = respuesta.pagina || 1;

    if (!clientes || clientes.length === 0) {
        if (esPrimeraVez || adminTablaHeaderHTML === '') {
            adminTablaHeaderHTML = getClientesTablaHeader(busquedaClienteActual, totalTodosClientes, totalInactivos);
            contenedor.innerHTML = adminTablaHeaderHTML +
                '<tr><td colspan="10" class="sin-productos">No hay clientes disponibles.</td></tr></tbody></table></div>';
        } else {
            const tbody = contenedor.querySelector('tbody');
            if (tbody) tbody.innerHTML = '<tr><td colspan="10" class="sin-productos">No hay clientes disponibles.</td></tr>';
            const aviso = document.getElementById('totalClientesAviso');
            if (aviso) aviso.textContent = '0 Clientes';
        }
        const paginacionExistente = contenedor.querySelector('.admin-paginacion-wrapper');
        if (paginacionExistente) paginacionExistente.remove();
        return;
    }

    if (esPrimeraVez || adminTablaHeaderHTML === '') {
        adminTablaHeaderHTML = getClientesTablaHeader(busquedaClienteActual, totalTodosClientes, totalInactivos);
    }

    const generarFilasClientes = () => {
        let filasHtml = '';
        clientes.forEach(cli => {
            const statusClass = cli.activo === 1 ? 'status-active' : 'status-inactive';
            const statusLabel = cli.activo === 1 ? 'Activo' : 'Inactivo';
            const fechaAlta = cli.fecha_alta ? new Date(cli.fecha_alta).toLocaleDateString('es-ES') : '—';
            const iniciales = (cli.nombre || 'C').charAt(0).toUpperCase() + (cli.apellidos || '').charAt(0).toUpperCase();

            filasHtml += `
                <tr class="${cli.activo == 0 ? 'row-disabled' : ''}" 
                    data-dni="${cli.dni || ''}"
                    data-nombre="${(cli.nombre || '').replace(/"/g, '&quot;')}"
                    data-apellidos="${(cli.apellidos || '').replace(/"/g, '&quot;')}"
                    data-fecha-alta="${cli.fecha_alta || ''}"
                    data-direccion="${(cli.direccion || '').replace(/"/g, '&quot;')}"
                    data-puntos="${cli.puntos || 0}"
                    data-productos="${cli.productos_comprados || 0}"
                    data-compras="${cli.compras_realizadas || 0}"
                    data-activo="${cli.activo}">
                    <td class="col-id">
                        <span class="id-badge">${cli.dni || '#' + cli.id}</span>
                    </td>
                    <td class="col-usuario">
                        <div class="user-profile-small" style="display: flex; align-items: center; gap: 10px;">
                            <div class="user-avatar-xs" style="width:32px; height:32px; background:linear-gradient(135deg, #3b82f6, #2563eb); color:white; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:0.75rem; font-weight:800; box-shadow: 0 2px 4px rgba(37, 99, 235, 0.2);">
                                ${iniciales}
                            </div>
                            <div style="display:flex; flex-direction:column;">
                                <span style="font-size: 0.9rem; color: #1e293b; font-weight: 700;">${cli.nombre || '—'}</span>
                                <span style="font-size: 0.75rem; color: #64748b; font-weight: 500;">${cli.apellidos || ''}</span>
                            </div>
                        </div>
                    </td>
                    <td class="col-fecha">
                        <div class="date-info">
                            <i class="far fa-calendar-alt"></i>
                            <span>${fechaAlta}</span>
                        </div>
                    </td>
                    <td style="text-align:center">
                        <span class="count-tag">${cli.productos_comprados || 0}</span>
                    </td>
                    <td style="text-align:center">
                        <span class="count-tag" style="background:#eff6ff; color:#2563eb;">${cli.compras_realizadas || 0}</span>
                    </td>
                    <td style="text-align:center">
                        <span class="count-tag" style="background:#f0fdf4; color:#16a34a; font-weight:800;">${cli.puntos || 0}</span>
                    </td>
                    <td class="col-estado">
                        <span class="status-pill ${statusClass}">${statusLabel}</span>
                    </td>
                    <td class="col-acciones">
                        <div class="actions-group">
                            <button class="action-btn btn-view" onclick="verCliente(${cli.id})" title="Ver">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="action-btn btn-edit" onclick="editarCliente(${cli.id})" title="Editar">
                                <i class="fas fa-pen"></i>
                            </button>
                            <button class="action-btn btn-delete" onclick="confirmarEliminarCliente(${cli.id}, '${(cli.nombre || '').replace(/'/g, "\\'")}')" title="Eliminar">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>`;
        });
        return filasHtml;
    };

    ejecutarCuandoIdle(generarFilasClientes, (filasHtml) => {
        let html = adminTablaHeaderHTML + filasHtml + '</tbody></table></div>';
        html += getPaginacionClientesHTML(totalPaginasClientes);

        if (esPrimeraVez) {
            contenedor.innerHTML = html;
        } else {
            const tbody = contenedor.querySelector('tbody');
            if (tbody) {
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = html;
                tbody.innerHTML = tempDiv.querySelector('tbody').innerHTML;
            } else {
                contenedor.innerHTML = html;
            }
            actualizarPaginacionDOM(contenedor, getPaginacionClientesHTML(totalPaginasClientes));
        }

        const aviso = document.getElementById('totalClientesAviso');
        if (aviso) {
            const txt = busquedaClienteActual
                ? `${totalClientes.toLocaleString('es-ES')} Resultado${totalClientes !== 1 ? 's' : ''}`
                : `${totalTodosClientes.toLocaleString('es-ES')} Total | ${(totalTodosClientes - totalInactivos).toLocaleString('es-ES')} Activos | ${totalInactivos.toLocaleString('es-ES')} Inactivos`;
            aviso.textContent = txt;
        }

        ajustarTodosInputsPaginacion();
    });
}

/**
 * Busca clientes por DNI con debounce.
 */
function buscarClientes() {
    clearTimeout(debounceTimerClientes);
    debounceTimerClientes = setTimeout(() => {
        const texto = document.getElementById('inputBuscarCliente')?.value || '';
        cargarClientesAdmin(texto);
    }, 300);
}

function nuevoCliente() {
    ['clienteHabitualDni', 'clienteHabitualNombre', 'clienteHabitualApellidos', 'clienteHabitualDireccion'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = '';
    });
    const now = new Date();
    const dateStr = new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().slice(0, 16);
    const dateEl = document.getElementById('clienteHabitualFecha');
    if (dateEl) dateEl.value = dateStr;

    const btn = document.getElementById('btnGuardarClienteHabitual');
    if (btn) btn.onclick = guardarClienteHabitualAdmin;

    const modal = document.getElementById('modalClienteHabitual');
    if (modal) {
        modal.style.display = 'flex';
        document.getElementById('clienteHabitualDni')?.focus();
    }
}

async function guardarClienteHabitualAdmin() {
    const dni = document.getElementById('clienteHabitualDni')?.value.trim();
    const nombre = document.getElementById('clienteHabitualNombre')?.value.trim();
    const apellidos = document.getElementById('clienteHabitualApellidos')?.value.trim();
    const direccion = document.getElementById('clienteHabitualDireccion')?.value.trim();
    
    if (!dni || !nombre || !apellidos) {
        alert('Por favor, complete todos los campos obligatorios.');
        return;
    }

    const now = new Date();
    const fecha_alta = new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().slice(0, 16);
    const btn = document.getElementById('btnGuardarClienteHabitual');
    
    if (btn) {
        btn.disabled = true;
        btn.textContent = 'Guardando...';
    }

    try {
        const fd = new FormData();
        fd.append('dni', dni);
        fd.append('nombre', nombre);
        fd.append('apellidos', apellidos);
        fd.append('direccion', direccion);
        fd.append('fecha_alta', fecha_alta);
        
        const r = await fetch('api/clientes.php', { method: 'POST', body: fd });
        const data = await r.json();
        
        if (data.ok) {
            alert('Cliente guardado correctamente');
            cerrarModal('modalClienteHabitual');
            cargarClientesAdmin();
        } else {
            alert(data.error || 'Error al guardar el cliente');
        }
    } catch (e) {
        alert('Error al comunicar con el servidor');
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.textContent = 'Guardar';
        }
    }
}

async function confirmarEliminarCliente(id, nombre) {
    if (!confirm(`¿Estás seguro de que quieres eliminar al cliente "${nombre}"?`)) return;
    try {
        const r = await fetch('api/clientes.php?eliminar=' + id, { method: 'DELETE' });
        const data = await r.json();
        if (data.ok) {
            alert('Cliente eliminado correctamente');
            cargarClientesAdmin();
        } else {
            alert(data.error || 'Error al eliminar el cliente');
        }
    } catch (e) {
        alert('Error al comunicar con el servidor');
    }
}

function editarCliente(id) {
    const cli = (typeof clientesData !== 'undefined' ? clientesData : []).find(c => c.id == id);
    if (!cli) return alert('No se encontró el cliente');

    const form = document.getElementById('modalEditarCliente');
    if (!form) return;

    form.dataset.originalDni = cli.dni || '';
    form.dataset.originalNombre = cli.nombre || '';
    form.dataset.originalApellidos = cli.apellidos || '';
    form.dataset.originalDireccion = cli.direccion || '';
    form.dataset.originalPuntos = cli.puntos || 0;

    document.getElementById('editarClienteId').value = id;
    document.getElementById('editarClienteDni').value = cli.dni || '';
    document.getElementById('editarClienteNombre').value = cli.nombre || '';
    document.getElementById('editarClienteApellidos').value = cli.apellidos || '';
    document.getElementById('editarClienteDireccion').value = cli.direccion || '';
    document.getElementById('editarClientePuntos').value = cli.puntos || 0;
    
    form.style.display = 'flex';
}

async function guardarClienteEditado() {
    const id = document.getElementById('editarClienteId').value;
    const cli = (typeof clientesData !== 'undefined' ? clientesData : []).find(c => c.id == id);
    const fecha_alta = cli ? cli.fecha_alta : '';
    const form = document.getElementById('modalEditarCliente');

    const dni = document.getElementById('editarClienteDni').value.trim() || form.dataset.originalDni;
    const nombre = document.getElementById('editarClienteNombre').value.trim() || form.dataset.originalNombre;
    const apellidos = document.getElementById('editarClienteApellidos').value.trim() || form.dataset.originalApellidos;
    const direccion = document.getElementById('editarClienteDireccion').value.trim() || form.dataset.originalDireccion;
    let puntos = document.getElementById('editarClientePuntos').value.trim();
    if (puntos === '') puntos = form.dataset.originalPuntos;

    if (!dni) { alert('El DNI es obligatorio'); return; }

    const btn = document.getElementById('btnGuardarClienteEditado');
    if (btn) { btn.disabled = true; btn.textContent = 'Guardando...'; }

    try {
        const r = await fetch('api/clientes.php?actualizar=true', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `id=${encodeURIComponent(id)}&dni=${encodeURIComponent(dni)}&nombre=${encodeURIComponent(nombre)}&apellidos=${encodeURIComponent(apellidos)}&direccion=${encodeURIComponent(direccion)}&fecha_alta=${encodeURIComponent(fecha_alta)}&puntos=${encodeURIComponent(puntos)}`
        });
        const data = await r.json();
        if (data.ok) {
            alert('Cliente actualizado correctamente');
            cerrarModal('modalEditarCliente');
            cargarClientesAdmin();
        } else {
            alert(data.error || 'Error al actualizar el cliente');
        }
    } catch (e) {
        alert('Error al comunicar con el servidor');
    } finally {
        if (btn) { btn.disabled = false; btn.textContent = 'Guardar'; }
    }
}

// ── Carrusel de compras ───────────────────────────────────────────────────────
function verComprasCliente(dni) {
    document.getElementById('modalVerCompras')?.remove();

    _comprasModalDNI = dni;
    _comprasData = [];
    _devolucionesData = [];
    _tabActivo = 'compras';

    const overlay = document.createElement('div');
    overlay.id = 'modalVerCompras';
    overlay.className = 'modal-overlay compras-modal-overlay';
    overlay.innerHTML = `
        <div class="compras-modal-shell">
            <div class="compras-modal-header">
                <div class="compras-modal-header-left">
                    <i class="fas fa-receipt"></i>
                    <div>
                        <span class="compras-modal-title">Historial del Cliente</span>
                        <span class="compras-modal-dni">${dni}</span>
                    </div>
                </div>
                <button class="compras-modal-close" onclick="document.getElementById('modalVerCompras').remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="compras-modal-tabs">
                <button class="compras-tab-btn active" data-tab="compras" onclick="_cambiarTab('compras')">
                    <i class="fas fa-shopping-bag"></i> Compras
                </button>
                <button class="compras-tab-btn" data-tab="devoluciones" onclick="_cambiarTab('devoluciones')">
                    <i class="fas fa-undo"></i> Devoluciones
                </button>
            </div>
            <div class="compras-modal-body" id="comprasModalBody">
                <div class="compras-loading">
                    <i class="fas fa-spinner fa-spin"></i>
                    <span>Cargando historial...</span>
                </div>
            </div>
        </div>`;
    document.body.appendChild(overlay);
    overlay.addEventListener('click', e => { if (e.target === overlay) overlay.remove(); });
    _cargarCompras(dni);
}

function _cargarCompras(dni) {
    const body = document.getElementById('comprasModalBody');
    if (!body) return;
    body.innerHTML = '<div class="compras-loading"><i class="fas fa-spinner fa-spin"></i><span>Cargando compras...</span></div>';

    fetch(`api/clientes.php?compras=1&dni=${encodeURIComponent(dni)}`)
        .then(r => r.json())
        .then(ventas => {
            _comprasData = ventas;
            if (_tabActivo === 'compras') _renderizarComprasOVentas('compras');
        })
        .catch(() => {
            if (_tabActivo === 'compras') body.innerHTML = '<div class="compras-empty compras-error"><i class="fas fa-exclamation-triangle"></i><p>No se pudieron cargar las compras.</p></div>';
        });
}

function _cargarDevoluciones(dni) {
    const body = document.getElementById('comprasModalBody');
    if (!body) return;
    body.innerHTML = '<div class="compras-loading"><i class="fas fa-spinner fa-spin"></i><span>Cargando devoluciones...</span></div>';

    fetch(`api/devoluciones.php?cliente_dni=${encodeURIComponent(dni)}`)
        .then(r => r.json())
        .then(devoluciones => {
            _devolucionesData = Array.isArray(devoluciones) ? devoluciones : [];
            if (_tabActivo === 'devoluciones') _renderizarComprasOVentas('devoluciones');
        })
        .catch(() => {
            if (_tabActivo === 'devoluciones') body.innerHTML = '<div class="compras-empty compras-error"><i class="fas fa-exclamation-triangle"></i><p>No se pudieron cargar las devoluciones.</p></div>';
        });
}

function _cambiarTab(tab) {
    if (tab === _tabActivo) return;
    _tabActivo = tab;
    document.querySelectorAll('.compras-tab-btn').forEach(btn => btn.classList.toggle('active', btn.dataset.tab === tab));
    if (tab === 'compras') {
        if (_comprasData.length > 0) _renderizarComprasOVentas('compras');
        else _cargarCompras(_comprasModalDNI);
    } else {
        if (_devolucionesData.length > 0) _renderizarComprasOVentas('devoluciones');
        else _cargarDevoluciones(_comprasModalDNI);
    }
}

function _renderizarComprasOVentas(tipo) {
    const body = document.getElementById('comprasModalBody');
    if (!body) return;
    const datos = tipo === 'compras' ? _comprasData : _devolucionesData;
    if (!datos || !datos.length) {
        const icono = tipo === 'compras' ? 'fa-shopping-bag' : 'fa-undo';
        body.innerHTML = `<div class="compras-empty"><i class="fas ${icono}"></i><p>Sin registros encontrados.</p></div>`;
        return;
    }

    const slidesHtml = datos.map((v, i) => {
        const date = new Date(v.fecha);
        const fecha = date.toLocaleDateString('es-ES', { day: '2-digit', month: 'short', year: 'numeric' });
        const hora = date.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
        const serie = v.ticket_serie || v.serie || 'T';
        const numero = v.ticket_numero || v.numero || v.idVenta || v.id || 0;
        const ticket = `${serie}${String(numero).padStart(5, '0')}`;
        const total = parseFloat(v.total).toFixed(2).replace('.', ',');
        const lineasHtml = (v.lineas || []).map(l => `
            <tr>
                <td class="compras-td-prod">${l.producto_nombre || 'Producto'}</td>
                <td class="compras-td-num">${l.cantidad}</td>
                <td class="compras-td-num">${parseFloat(l.precioUnitarioConIva).toFixed(2).replace('.', ',')} €</td>
                <td class="compras-td-num compras-subtotal">${parseFloat(l.subtotalConIva).toFixed(2).replace('.', ',')} €</td>
            </tr>`).join('');

        return `
            <div class="compras-slide" data-index="${i}" style="display:${i === 0 ? 'flex' : 'none'}">
                <div class="compras-ticket-header">
                    <div class="compras-ticket-num"><span class="compras-ticket-label">Ticket</span><span class="compras-ticket-value">#${ticket}</span></div>
                    <div class="compras-ticket-meta"><span>${fecha}</span> <span>${hora}</span></div>
                </div>
                <div class="compras-tabla-wrapper">
                    <table class="compras-tabla">
                        <thead><tr><th>Producto</th><th>Cant.</th><th>Precio</th><th>Subtotal</th></tr></thead>
                        <tbody>${lineasHtml}</tbody>
                    </table>
                </div>
                <div class="compras-total-row"><span>Total</span><span>${total} €</span></div>
            </div>`;
    }).join('');

    body.innerHTML = `
        <div class="compras-slides-container">${slidesHtml}</div>
        <div class="compras-nav">
            <button class="compras-nav-btn" onclick="prevSaleSlide()"><i class="fas fa-chevron-left"></i></button>
            <span class="compras-counter" id="compraActualTitulo">1 / ${datos.length}</span>
            <button class="compras-nav-btn" onclick="nextSaleSlide()"><i class="fas fa-chevron-right"></i></button>
        </div>`;

    const modalEl = document.getElementById('modalVerCompras');
    if (modalEl) modalEl.dataset.totalSlides = datos.length;
    currentSaleSlide = 0;
}

function _updateNavButtons() {
    const modal = document.getElementById('modalVerCompras');
    if (!modal) return;
    const total = parseInt(modal.dataset.totalSlides) || 1;
    const counter = document.getElementById('compraActualTitulo');
    if (counter) counter.textContent = `${currentSaleSlide + 1} / ${total}`;
}

function changeSaleSlide(index) {
    const modal = document.getElementById('modalVerCompras');
    if (!modal) return;
    const slides = modal.querySelectorAll('.compras-slide');
    slides.forEach(s => s.style.display = 'none');
    if (slides[index]) slides[index].style.display = 'flex';
    currentSaleSlide = index;
    _updateNavButtons();
}

function nextSaleSlide() { const m = document.getElementById('modalVerCompras'); if (m) changeSaleSlide((currentSaleSlide + 1) % parseInt(m.dataset.totalSlides)); }
function prevSaleSlide() { const m = document.getElementById('modalVerCompras'); if (m) changeSaleSlide((currentSaleSlide - 1 + parseInt(m.dataset.totalSlides)) % parseInt(m.dataset.totalSlides)); }

// ── PROVEEDORES ───────────────────────────────────────────────────────────────

function getProveedoresTablaHeader(textoBusqueda = '') {
    return `
        <div class="admin-tabla-header products-header">
            <div class="header-filters-grid sales-optimized-grid">
                <div class="filter-main">
                    <div class="search-wrapper">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" id="inputBuscarProveedor" class="input-modern-search"
                            placeholder="Buscar por nombre..."
                            oninput="buscarProveedores()" autocomplete="off"
                            value="${textoBusqueda.replace(/"/g, '&quot;')}">
                    </div>
                </div>

                <div class="header-actions">
                    <button class="btn-modern btn-primary" onclick="nuevoProveedor()">
                        <i class="fas fa-plus"></i>
                        <span>Nuevo Proveedor</span>
                    </button>
                </div>
            </div>
        </div>
        <div class="admin-tabla-wrapper products-table-wrapper">
            <table class="admin-tabla">
                <thead><tr>
                    <th style="width: 60px;">ID</th>
                    <th>Proveedor</th>
                    <th>Contacto</th>
                    <th>Email</th>
                    <th>Dirección</th>
                    <th style="width: 100px;">Estado</th>
                    <th style="width: 120px; text-align:center;">Acciones</th>
                </tr></thead>
                <tbody>`;
}

/**
 * Carga los proveedores desde la API y los renderiza en la tabla.
 */
function cargarProveedoresAdmin(textoBusqueda = '') {
    const contenedor = document.getElementById('adminContenido');
    const tablaExistente = contenedor.querySelector('.admin-tabla');

    if (seccionActual !== 'proveedores') {
        adminTablaHeaderHTML = '';
        seccionActual = 'proveedores';
    }

    const esPrimeraVez = !tablaExistente || adminTablaHeaderHTML === '';

    if (esPrimeraVez) {
        adminTablaHeaderHTML = '';
    }

    const params = new URLSearchParams();
    if (textoBusqueda) params.append('buscar', textoBusqueda);

    return fetch('api/proveedores.php?' + params.toString())
        .then(res => {
            if (!res.ok) return res.json().then(err => { throw new Error(err.error || 'Error al cargar proveedores'); });
            return res.json();
        })
        .then(data => renderProveedoresAdmin(data, esPrimeraVez))
        .catch(err => {
            console.error('Error cargando proveedores:', err);
            document.getElementById('adminContenido').innerHTML = '<p class="sin-productos">' + err.message + '</p>';
        });
}

/**
 * Renderiza un array de proveedores en formato tabla.
 */
function renderProveedoresAdmin(proveedores, esPrimeraVez = true) {
    const contenedor = document.getElementById('adminContenido');

    if (!proveedores || proveedores.length === 0) {
        if (esPrimeraVez || adminTablaHeaderHTML === '') {
            adminTablaHeaderHTML = getProveedoresTablaHeader();
            contenedor.innerHTML = adminTablaHeaderHTML +
                '<tr><td colspan="7" class="sin-productos">No hay proveedores disponibles.</td></tr></tbody></table></div>';
        } else {
            const tbody = contenedor.querySelector('tbody');
            if (tbody) tbody.innerHTML = '<tr><td colspan="7" class="sin-productos">No hay proveedores disponibles.</td></tr>';
        }
        return;
    }

    if (esPrimeraVez || adminTablaHeaderHTML === '') {
        adminTablaHeaderHTML = getProveedoresTablaHeader();
    }

    const generarFilas = () => proveedores.map(prov => {
        const statusClass = prov.activo === 1 ? 'status-active' : 'status-inactive';
        const statusLabel = prov.activo === 1 ? 'Activo' : 'Inactivo';
        const inicial = (prov.nombre || 'P').charAt(0).toUpperCase();

        return `
            <tr class="${prov.activo == 0 ? 'row-disabled' : ''}"
                data-contacto="${(prov.contacto || '').replace(/"/g, '&quot;')}"
                data-email="${(prov.email || '').replace(/"/g, '&quot;')}"
                data-direccion="${(prov.direccion || '').replace(/"/g, '&quot;')}"
                data-activo="${prov.activo}">
                <td class="col-id">
                    <span class="id-badge">#${prov.id}</span>
                </td>
                <td class="col-usuario">
                    <div class="user-profile-small" style="display: flex; align-items: center; gap: 10px;">
                        <div class="user-avatar-xs" style="width:32px; height:32px; background:linear-gradient(135deg, #10b981, #059669); color:white; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:0.8rem; font-weight:800;">
                            ${inicial}
                        </div>
                        <span style="font-size: 0.9rem; color: #1e293b; font-weight: 700;">${prov.nombre}</span>
                    </div>
                </td>
                <td>
                    <div class="contact-info" style="display:flex; flex-direction:column; gap:2px;">
                        <span style="font-size: 0.85rem; font-weight:600; color:#475569;">${prov.contacto || '—'}</span>
                    </div>
                </td>
                <td>
                    <div class="email-wrapper" style="font-size: 0.8rem;">
                        <i class="far fa-envelope email-icon"></i>
                        <span>${prov.email || '—'}</span>
                    </div>
                </td>
                <td>
                    <div class="address-wrapper" style="font-size: 0.8rem; color:#64748b; display:flex; align-items:center; gap:5px;">
                        <i class="fas fa-map-marker-alt" style="opacity:0.6;"></i>
                        <span style="max-width:150px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">${prov.direccion || '—'}</span>
                    </div>
                </td>
                <td class="col-estado">
                    <span class="status-pill ${statusClass}">${statusLabel}</span>
                </td>
                <td class="col-acciones">
                    <div class="actions-group">
                        <button class="action-btn btn-view" onclick="verProveedor(${prov.id})" title="Ver">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="action-btn btn-edit" onclick="editarProveedor(${prov.id})" title="Editar">
                            <i class="fas fa-pen"></i>
                        </button>
                        <button class="action-btn btn-delete" onclick="confirmarEliminarProveedor(${prov.id},'${prov.nombre.replace(/'/g, "\\'")}')">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>`;
    }).join('');

    ejecutarCuandoIdle(generarFilas, (filasHtml) => {
        const html = adminTablaHeaderHTML + filasHtml + '</tbody></table></div>';

        if (esPrimeraVez) {
            contenedor.innerHTML = html;
        } else {
            const tbody = contenedor.querySelector('tbody');
            if (tbody) {
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = html;
                tbody.innerHTML = tempDiv.querySelector('tbody').innerHTML;
            } else {
                contenedor.innerHTML = html;
            }
        }
    });
}

/**
 * Busca proveedores por nombre con debounce.
 */
function buscarProveedores() {
    clearTimeout(debounceTimerProveedores);
    debounceTimerProveedores = setTimeout(() => {
        const texto = document.getElementById('inputBuscarProveedor')?.value || '';
        const params = new URLSearchParams();
        if (texto) params.append('buscar', texto);

        fetch('api/proveedores.php?' + params.toString())
            .then(res => {
                if (!res.ok) return res.json().then(err => { throw new Error(err.error || 'Error al buscar'); });
                return res.json();
            })
            .then(data => renderProveedoresAdmin(data, false))
            .catch(err => {
                console.error('Error buscando proveedores:', err);
                document.getElementById('adminContenido').innerHTML = '<p class="sin-productos">Error: ' + err.message + '</p>';
            });
    }, 300);
}

/**
 * Confirma la eliminación de la asociación proveedor-producto.
 */
function confirmarEliminarProductoProveedor(idAsociacion, nombreProducto) {
    if (confirm(`¿Seguro que quieres dejar de suministrar el producto "${nombreProducto}" a través de este proveedor?`)) {
        fetch(`api/proveedores.php?eliminarAsociacion=${idAsociacion}`, { method: 'DELETE' })
            .then(res => res.json())
            .then(data => {
                if (data.ok) {
                    cargarProductosProveedor(proveedorActualId);
                } else {
                    alert('Error al eliminar: ' + (data.error ?? ''));
                }
            })
            .catch(err => console.error('Error eliminando asociación:', err));
    }
}