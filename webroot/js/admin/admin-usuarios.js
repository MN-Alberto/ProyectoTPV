/**
 * admin.usuarios.js
 * Gestión de usuarios en el panel de administración.
 * Depende de: admin.state.js, admin.utils.js, admin.pagination.js
 */

// ═══════════════════════════════════════════════════════════════════════════════
// USUARIOS
// ═══════════════════════════════════════════════════════════════════════════════

function getUsuariosTablaHeader(textoBusqueda = '', totalUsuarios = 0) {
    const contador = `${totalUsuarios} Usuario${totalUsuarios !== 1 ? 's' : ''}`;
    return `
        ${getPremiumHeaderHTML('fa-users-cog', 'Gestión de Usuarios', 'Control de acceso y permisos del personal', 'linear-gradient(135deg, #8b5cf6, #7c3aed)')}
        <div class="admin-tabla-header products-header">
            <div class="header-filters-grid">
                <div class="filter-main">
                    <div class="search-wrapper">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" id="inputBuscarUsuario" class="input-modern-search"
                            placeholder="Buscar por nombre, email o rol..."
                            oninput="buscarUsuarios()" autocomplete="off"
                            value="${textoBusqueda.replace(/"/g, '&quot;')}">
                    </div>
                </div>
                
                <div class="header-status-info">
                    <span id="totalUsuariosAviso" class="info-tag">${contador}</span>
                </div>

                <div class="header-actions">
                    <button class="btn-modern btn-primary btn-add-user" onclick="prepararNuevoUsuario()">
                        <i class="fas fa-plus"></i>
                        <span>Nuevo Usuario</span>
                    </button>
                </div>
            </div>
        </div>
        <div class="admin-tabla-wrapper products-table-wrapper">
            <table class="admin-tabla">
                <thead>
                    <tr>
                        <th>Identidad del Usuario</th>
                        <th>Correo Electrónico</th>
                        <th style="width: 130px; text-align:center;">Rol</th>
                        <th style="width: 150px;">Fecha de Alta</th>
                        <th style="width: 130px; text-align:center;">Estado</th>
                        <th style="width: 130px; text-align:center;">Acciones</th>
                    </tr>
                </thead>
                <tbody>`;
}

function generarFilaUsuario(usr) {
    const fechaAlta = new Date(usr.fechaAlta).toLocaleDateString('es-ES',
        { day: '2-digit', month: '2-digit', year: 'numeric' });

    const rolBadgeCls = usr.rol === 'admin' ? 'role-admin' : 'role-staff';
    const rolText = usr.rol === 'admin' ? 'Administrador' : 'Empleado';
    const rolIcon = usr.rol === 'admin' ? 'fa-user-shield' : 'fa-user-tag';

    const estadoHtml = usr.activo === 1
        ? '<span class="status-pill status-active"><i class="fas fa-check-circle"></i> Activo</span>'
        : '<span class="status-pill status-inactive"><i class="fas fa-times-circle"></i> Inactivo</span>';

    const btnEliminar = usr.rol !== 'admin'
        ? `<button class="action-btn btn-delete"
               onclick="confirmarEliminarUsuario(${usr.id},'${usr.nombre.replace(/'/g, "\\'")}')" title="Eliminar">
               <i class="fas fa-trash"></i></button>` : '';

    return `
        <tr class="user-row ${usr.activo === 0 ? 'row-disabled' : ''}">
            <td class="col-nombre">
                <div class="user-profile">
                    <div class="user-avatar">${usr.nombre.charAt(0).toUpperCase()}</div>
                    <div class="user-main-info">
                        <span class="user-name-text">${usr.nombre}</span>
                        <span class="user-id-sub">ID: ${usr.id}</span>
                    </div>
                </div>
            </td>
            <td class="col-email">
                <div class="email-wrapper">
                    <i class="far fa-envelope email-icon"></i>
                    <span>${usr.email}</span>
                </div>
            </td>
            <td class="col-rol" style="text-align:center;">
                <span class="role-badge ${rolBadgeCls}">
                    <i class="fas ${rolIcon}"></i> ${rolText}
                </span>
            </td>
            <td class="col-fecha">
                <div class="date-info">
                    <i class="far fa-calendar-alt"></i>
                    <span>${fechaAlta}</span>
                </div>
            </td>
            <td class="col-estado" style="text-align:center;">${estadoHtml}</td>
            <td class="col-acciones">
                <div class="actions-group">
                    <button class="action-btn btn-view" onclick="verUsuario(${usr.id})" title="Ver detalles">
                        <i class="fas fa-eye"></i></button>
                    <button class="action-btn btn-edit" onclick="editarUsuario(${usr.id})" title="Editar">
                        <i class="fas fa-pen"></i></button>
                    ${btnEliminar}
                </div>
            </td>
        </tr>`;
}

function renderizarUsuariosPagina() {
    const contenedor = document.getElementById('adminContenido');
    if (!contenedor) return;
    const tbody = contenedor.querySelector('tbody');
    if (tbody) tbody.innerHTML = (typeof usuariosData !== 'undefined' ? usuariosData : []).map(generarFilaUsuario).join('');
    actualizarPaginacionDOM(contenedor, getPaginacionUsuariosHTML(totalPaginasUsuarios));
}

function renderUsuariosAdmin(respuesta, esPrimeraVez = true, busquedaActual = '') {
    const contenedor = document.getElementById('adminContenido');
    const usuarios = respuesta.usuarios || [];
    totalPaginasUsuarios = respuesta.totalPaginas || 1;
    totalUsuariosData = respuesta.total || 0;
    paginaActualUsuarios = respuesta.pagina || 1;
    usuariosData = usuarios;

    if (!usuarios.length) {
        if (esPrimeraVez || !adminTablaHeaderHTML) {
            adminTablaHeaderHTML = getUsuariosTablaHeader('', totalUsuariosData);
            contenedor.innerHTML = adminTablaHeaderHTML +
                '<tr><td colspan="6" class="sin-productos">No hay usuarios disponibles.</td></tr></tbody></table></div>';
        } else {
            const tbody = contenedor.querySelector('tbody');
            if (tbody) tbody.innerHTML = '<tr><td colspan="6" class="sin-productos">No hay usuarios disponibles.</td></tr>';
        }
        return;
    }

    if (esPrimeraVez || !adminTablaHeaderHTML) {
        adminTablaHeaderHTML = getUsuariosTablaHeader(busquedaActual, totalUsuariosData);
    }

    const filasHtml = usuarios.map(generarFilaUsuario).join('');
    let html = adminTablaHeaderHTML + filasHtml + '</tbody></table></div>' +
        getPaginacionUsuariosHTML(totalPaginasUsuarios);

    if (esPrimeraVez) {
        contenedor.innerHTML = html;
    } else {
        const tbody = contenedor.querySelector('tbody');
        if (tbody) tbody.innerHTML = filasHtml;
        const contador = document.getElementById('totalUsuariosAviso');
        if (contador) contador.textContent = `${totalUsuariosData.toLocaleString('es-ES')} Usuario${totalUsuariosData !== 1 ? 's' : ''}`;
    }
    actualizarPaginacionDOM(contenedor, getPaginacionUsuariosHTML(totalPaginasUsuarios));
}

function cargarUsuariosAdmin(textoBusqueda = '', resetPagina = true) {
    const contenedor = document.getElementById('adminContenido');
    const tablaExistente = contenedor.querySelector('.admin-tabla');

    if (seccionActual !== 'usuarios') { adminTablaHeaderHTML = ''; seccionActual = 'usuarios'; }
    const esPrimeraVez = !tablaExistente || !adminTablaHeaderHTML;
    if (resetPagina) paginaActualUsuarios = 1;
    busquedaUsuarioActual = textoBusqueda;

    const params = new URLSearchParams({ pagina: paginaActualUsuarios, porPagina: usuariosPorPagina });
    if (textoBusqueda) params.append('buscar', textoBusqueda);

    if (esPrimeraVez) {
        contenedor.innerHTML = '<div style="text-align:center;padding:60px 20px;"><i class="fas fa-spinner fa-spin" style="font-size:2rem;color:var(--color-primary);"></i></div>';
    } else {
        const tbody = contenedor.querySelector('tbody');
        if (tbody) tbody.innerHTML = '<tr><td colspan="7" class="sin-productos" style="text-align:center;"><i class="fas fa-spinner fa-spin"></i> Cargando...</td></tr>';
    }

    return fetch('api/usuarios.php?' + params)
        .then(r => { if (!r.ok) return r.json().then(e => { throw new Error(e.error || 'Error al cargar usuarios'); }); return r.json(); })
        .then(data => renderUsuariosAdmin(data, esPrimeraVez, textoBusqueda))
        .catch(err => {
            console.error('Error cargando usuarios:', err);
            contenedor.innerHTML = '<p class="sin-productos">' + err.message + '</p>';
        });
}

function buscarUsuarios() {
    clearTimeout(debounceTimerUsuarios);
    paginaActualUsuarios = 1;
    debounceTimerUsuarios = setTimeout(() => {
        cargarUsuariosAdmin(document.getElementById('inputBuscarUsuario')?.value, true);
    }, 300);
}

function prepararNuevoUsuario() {
    ['editUsuarioId', 'editUsuarioNombre', 'editUsuarioEmail', 'editUsuarioPassword'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = '';
    });
    document.getElementById('editUsuarioPassword').required = true;
    document.getElementById('editUsuarioRol').value = 'empleado';
    document.getElementById('editUsuarioEstado').value = '1';
    actualizarVisibilidadPermisos('empleado');
    document.getElementById('editUsuarioPermisoCrearProductos').checked = false;
    document.getElementById('editUsuarioTitulo').textContent = 'Nuevo Usuario';
    document.getElementById('editUsuarioRol').onchange = function () { actualizarVisibilidadPermisos(this.value); };
    abrirModal('modalEditarUsuario');
}

function actualizarVisibilidadPermisos(rol) {
    const fila = document.getElementById('filaPermisos');
    if (fila) fila.style.display = rol === 'empleado' ? 'block' : 'none';
}

function verUsuario(id) {
    fetch(`api/usuarios.php?id=${id}`)
        .then(r => r.json())
        .then(data => {
            if (data.ok === false) { alert(data.error); return; }
            const fechaAlta = new Date(data.fechaAlta).toLocaleDateString('es-ES',
                { day: '2-digit', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' });
            document.getElementById('verUsuarioNombre').textContent = data.nombre;
            document.getElementById('verUsuarioEmail').textContent = data.email;
            document.getElementById('verUsuarioRol').textContent = data.rol === 'admin' ? 'Administrador' : 'Empleado';
            document.getElementById('verUsuarioFecha').textContent = fechaAlta;
            document.getElementById('verUsuarioEstado').innerHTML = data.activo === 1
                ? '<span class="admin-badge badge-activo">Activo</span>'
                : '<span class="admin-badge badge-inactivo">Inactivo</span>';
            const crearProductos = data.permisos && data.permisos.includes('crear_productos');
            document.getElementById('verUsuarioCrearProductos').innerHTML = crearProductos
                ? '<span class="admin-badge badge-activo">Sí</span>'
                : '<span class="admin-badge badge-inactivo">No</span>';

            const productoComodin = data.permisos && data.permisos.includes('producto_comodin');
            document.getElementById('verUsuarioProductoComodin').innerHTML = productoComodin
                ? '<span class="admin-badge badge-activo">Sí</span>'
                : '<span class="admin-badge badge-inactivo">No</span>';

            const retirarDinero = data.permisos && data.permisos.includes('retirar_dinero');
            document.getElementById('verUsuarioRetirarDinero').innerHTML = retirarDinero
                ? '<span class="admin-badge badge-activo">Sí</span>'
                : '<span class="admin-badge badge-inactivo">No</span>';

            document.getElementById('verUsuarioTotalDescansos').textContent = data.total_descansos || 0;
            document.getElementById('verUsuarioTotalTurnos').textContent = data.total_turnos || 0;
            abrirModal('modalVerUsuario');
        })
        .catch(err => console.error('Error cargando usuario:', err));
}

function editarUsuario(id) {
    fetch(`api/usuarios.php?id=${id}`)
        .then(r => r.json())
        .then(data => {
            if (data.ok === false) { alert(data.error); return; }
            document.getElementById('editUsuarioId').value = data.id;
            document.getElementById('editUsuarioNombre').value = data.nombre;
            document.getElementById('editUsuarioEmail').value = data.email;
            document.getElementById('editUsuarioPassword').value = '';
            document.getElementById('editUsuarioPassword').required = false;
            document.getElementById('editUsuarioRol').value = data.rol;
            document.getElementById('editUsuarioEstado').value = data.activo;

            const esAdmin = data.id == 1;
            ['editUsuarioRol', 'editUsuarioEstado'].forEach(id => {
                const el = document.getElementById(id);
                el.disabled = esAdmin;
                el.style.opacity = esAdmin ? '0.6' : '1';
            });

            actualizarVisibilidadPermisos(data.rol);
            document.getElementById('editUsuarioPermisoCrearProductos').checked =
                (data.permisos || '').includes('crear_productos');
            document.getElementById('editUsuarioPermisoModificarPrecios').checked =
                (data.permisos || '').includes('modificar_precios');
            document.getElementById('editUsuarioPermisoProductoComodin').checked =
                (data.permisos || '').includes('producto_comodin');
            document.getElementById('editUsuarioPermisoRetirarDinero').checked =
                (data.permisos || '').includes('retirar_dinero');
            document.getElementById('editUsuarioTitulo').textContent = 'Editar Usuario';
            abrirModal('modalEditarUsuario');
        })
        .catch(err => console.error('Error cargando usuario:', err));
}

function guardarCambiosUsuario() {
    const id = document.getElementById('editUsuarioId').value;
    const nombre = document.getElementById('editUsuarioNombre').value.trim();
    const email = document.getElementById('editUsuarioEmail').value.trim();
    const password = document.getElementById('editUsuarioPassword').value;
    let rol = document.getElementById('editUsuarioRol').value;
    let activo = document.getElementById('editUsuarioEstado').value;

    if (!nombre || !email) { alert('Por favor completa todos los campos obligatorios.'); return; }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { alert('Por favor ingresa un email válido.'); return; }
    if (id == 1) { rol = 'admin'; activo = '1'; }

    let permisos = [];
    if (rol === 'empleado') {
        if (document.getElementById('editUsuarioPermisoCrearProductos')?.checked) permisos.push('crear_productos');
        if (document.getElementById('editUsuarioPermisoModificarPrecios')?.checked) permisos.push('modificar_precios');
        if (document.getElementById('editUsuarioPermisoProductoComodin')?.checked) permisos.push('producto_comodin');
        if (document.getElementById('editUsuarioPermisoRetirarDinero')?.checked) permisos.push('retirar_dinero');
    }
    permisos = permisos.join(',');

    const fd = new FormData();
    if (id) fd.append('id', id);
    fd.append('nombre', nombre); fd.append('email', email);
    if (password) fd.append('password', password);
    fd.append('rol', rol); fd.append('activo', activo); fd.append('permisos', permisos);

    fetch('api/usuarios.php', { method: 'POST', body: fd })
        .then(r => { if (!r.ok) return r.json().then(e => { throw new Error(e.error || 'Error al guardar'); }); return r.json(); })
        .then(data => {
            if (data.ok) { cerrarModal('modalEditarUsuario'); cargarUsuariosAdmin(); }
            else alert('Error al guardar: ' + (data.error ?? ''));
        })
        .catch(err => { console.error('Error:', err); alert('Error: ' + err.message); });
}

function confirmarEliminarUsuario(id, nombre) {
    if (confirm(`¿Seguro que quieres eliminar al usuario "${nombre}"?`)) eliminarUsuario(id);
}

function eliminarUsuario(id) {
    fetch(`api/usuarios.php?eliminar=${id}`, { method: 'DELETE' })
        .then(r => { if (!r.ok) return r.json().then(e => { throw new Error(e.error || 'Error al eliminar'); }); return r.json(); })
        .then(data => { if (data.ok) cargarUsuariosAdmin(); else alert('Error: ' + (data.error ?? '')); })
        .catch(err => { console.error('Error:', err); alert('Error: ' + err.message); });
}

// ── FIN SECCIÓN USUARIOS ─────────────────────────────────────────────────────