/**
 * admin.usuarios.js
 * Gestión de usuarios, clientes y proveedores en el panel de administración.
 * Depende de: admin.state.js, admin.utils.js, admin.pagination.js
 */

// ═══════════════════════════════════════════════════════════════════════════════
// USUARIOS
// ═══════════════════════════════════════════════════════════════════════════════

function getUsuariosTablaHeader(textoBusqueda = '', totalUsuarios = 0) {
    const contador = `${totalUsuarios.toLocaleString('es-ES')} Usuario${totalUsuarios !== 1 ? 's' : ''}`;
    return `
        <div class="admin-tabla-header">
            <div style="display:flex;gap:10px;width:100%;align-items:center;flex-wrap:wrap;">
                <div style="display:flex;align-items:center;gap:10px;">
                    <label for="inputBuscarUsuario" class="admin-label">Buscar:</label>
                    <input type="text" id="inputBuscarUsuario" class="input-buscarUsuario"
                        placeholder="Escribe el nombre del usuario..." oninput="buscarUsuarios()"
                        autocomplete="off" value="${textoBusqueda.replace(/"/g, '&quot;')}" style="width:300px;">
                </div>
                <button class="btn-admin-accion btn-nuevo" onclick="prepararNuevoUsuario()">
                    <i class="fas fa-plus"></i> Nuevo Usuario
                </button>
                <span id="totalUsuariosAviso" class="total-clientes-aviso">${contador}</span>
            </div>
        </div>
        <div class="admin-tabla-wrapper sin-scroll">
            <table class="admin-tabla">
                <thead>
                    <tr>
                        <th>Nombre</th><th>Email</th><th>Rol</th>
                        <th>Fecha de Alta</th><th>Estado</th><th>Acciones</th>
                    </tr>
                </thead>
                <tbody>`;
}

function generarFilaUsuario(usr) {
    const fechaAlta = new Date(usr.fechaAlta).toLocaleDateString('es-ES',
        { day: '2-digit', month: '2-digit', year: 'numeric' });

    const rolBadge = usr.rol === 'admin'
        ? '<span class="admin-badge" style="background:#dbeafe;color:#1e40af;">Admin</span>'
        : '<span class="admin-badge" style="background:#f3f4f6;color:#374151;">Empleado</span>';

    const estadoHtml = usr.activo === 1
        ? '<span class="admin-badge badge-activo">Activo</span>'
        : '<span class="admin-badge badge-inactivo">Inactivo</span>';

    const btnEliminar = usr.rol !== 'admin'
        ? `<button class="btn-admin-accion btn-eliminar"
               onclick="confirmarEliminarUsuario(${usr.id},'${usr.nombre.replace(/'/g, "\\'")}')" title="Eliminar">
               <i class="fas fa-trash"></i></button>` : '';

    return `
        <tr>
            <td class="col-nombre">${usr.nombre}</td>
            <td class="col-email">${usr.email}</td>
            <td class="col-rol">${rolBadge}</td>
            <td class="col-fecha">${fechaAlta}</td>
            <td class="col-estado">${estadoHtml}</td>
            <td class="col-acciones">
                <button class="btn-admin-accion btn-ver" onclick="verUsuario(${usr.id})" title="Ver">
                    <i class="fas fa-eye"></i></button>
                <button class="btn-admin-accion btn-editar" onclick="editarUsuario(${usr.id})" title="Editar">
                    <i class="fas fa-pen"></i></button>
                ${btnEliminar}
            </td>
        </tr>`;
}

function renderizarUsuariosPagina() {
    const contenedor = document.getElementById('adminContenido');
    if (!contenedor) return;
    const tbody = contenedor.querySelector('tbody');
    if (tbody) tbody.innerHTML = usuariosData.map(generarFilaUsuario).join('');
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

    let permisos = '';
    if (rol === 'empleado') {
        const cb = document.getElementById('editUsuarioPermisoCrearProductos');
        if (cb && cb.checked) permisos = 'crear_productos';
    }

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

// ═══════════════════════════════════════════════════════════════════════════════
// CLIENTES
// ═══════════════════════════════════════════════════════════════════════════════

function getClientesTablaHeader(textoBusqueda = '', totalCli = 0, totalInactivos = 0) {
    const activos = totalCli - totalInactivos;
    const hayBusqueda = textoBusqueda && textoBusqueda.trim() !== '';
    const contadorHTML = hayBusqueda
        ? `${totalCli.toLocaleString('es-ES')} Resultado${totalCli !== 1 ? 's' : ''}`
        : `${totalCli.toLocaleString('es-ES')} Total | ${activos.toLocaleString('es-ES')} Activo${activos !== 1 ? 's' : ''} | ${totalInactivos.toLocaleString('es-ES')} Inactivo${totalInactivos !== 1 ? 's' : ''}`;

    return `
        <div class="admin-tabla-header">
            <div style="display:flex;gap:10px;width:100%;align-items:center;flex-wrap:wrap;">
                <div style="display:flex;align-items:center;gap:10px;">
                    <label for="inputBuscarCliente" class="admin-label">Buscar:</label>
                    <input type="text" id="inputBuscarCliente" class="input-buscarProducto"
                        placeholder="Escribe el DNI del cliente..." oninput="buscarClientes()"
                        autocomplete="off" value="${textoBusqueda.replace(/"/g, '&quot;')}" style="width:400px;">
                </div>
                <button class="btn-admin-accion btn-nuevo" onclick="nuevoCliente()">
                    <i class="fas fa-plus"></i> Nuevo Cliente
                </button>
                <span id="totalClientesAviso" class="total-clientes-aviso">${contadorHTML}</span>
            </div>
        </div>
        <div class="admin-tabla-wrapper sin-scroll">
            <table class="admin-tabla">
                <thead>
                    <tr>
                        <th>DNI</th><th>Nombre</th><th>Apellidos</th><th>Fecha Alta</th>
                        <th>Productos</th><th>Compras</th><th>Puntos</th><th>Estado</th><th>Acciones</th>
                    </tr>
                </thead>
                <tbody>`;
}

function cargarClientesAdmin(textoBusqueda = '', resetPagina = true) {
    const contenedor = document.getElementById('adminContenido');
    const tablaExistente = contenedor.querySelector('.admin-tabla');
    if (seccionActual !== 'clientes') { adminTablaHeaderHTML = ''; seccionActual = 'clientes'; }
    const esPrimeraVez = !tablaExistente || !adminTablaHeaderHTML;
    if (resetPagina) paginaActualClientes = 1;
    busquedaClienteActual = textoBusqueda;

    const params = new URLSearchParams({ pagina: paginaActualClientes, porPagina: clientesPorPagina });
    if (textoBusqueda) params.append('dni', textoBusqueda);

    if (esPrimeraVez) {
        contenedor.innerHTML = '<div style="text-align:center;padding:60px 20px;"><i class="fas fa-spinner fa-spin" style="font-size:2rem;color:var(--color-primary);"></i></div>';
    } else {
        const tbody = contenedor.querySelector('tbody');
        if (tbody) tbody.innerHTML = '<tr><td colspan="9" class="sin-productos" style="text-align:center;"><i class="fas fa-spinner fa-spin"></i> Cargando...</td></tr>';
    }

    return fetch('api/clientes.php?' + params)
        .then(r => { if (!r.ok) return r.json().then(e => { throw new Error(e.error || 'Error al cargar clientes'); }); return r.json(); })
        .then(data => renderClientesAdmin(data, esPrimeraVez))
        .catch(err => {
            console.error('Error cargando clientes:', err);
            contenedor.innerHTML = '<p class="sin-productos">' + err.message + '</p>';
        });
}

function renderClientesAdmin(respuesta, esPrimeraVez = true) {
    const contenedor = document.getElementById('adminContenido');
    const clientes = respuesta.clientes || [];
    totalPaginasClientes = respuesta.totalPaginas || 1;
    totalClientes = respuesta.total || 0;
    const totalTodos = respuesta.totalTodos || respuesta.total || 0;
    const totalInactivos = respuesta.totalInactivos || 0;
    paginaActualClientes = respuesta.pagina || 1;

    const actualizarContador = () => {
        const contador = document.getElementById('totalClientesAviso');
        if (!contador) return;
        const hayBusqueda = busquedaClienteActual && busquedaClienteActual.trim() !== '';
        const activos = totalTodos - totalInactivos;
        contador.innerHTML = hayBusqueda
            ? `${totalTodos.toLocaleString('es-ES')} Resultado${totalTodos !== 1 ? 's' : ''}`
            : `${totalTodos.toLocaleString('es-ES')} Total | ${activos.toLocaleString('es-ES')} Activo${activos !== 1 ? 's' : ''} | ${totalInactivos.toLocaleString('es-ES')} Inactivo${totalInactivos !== 1 ? 's' : ''}`;
    };

    if (!clientes.length) {
        if (esPrimeraVez || !adminTablaHeaderHTML) {
            adminTablaHeaderHTML = getClientesTablaHeader(busquedaClienteActual, totalTodos, totalInactivos);
            contenedor.innerHTML = adminTablaHeaderHTML +
                '<tr><td colspan="9" class="sin-productos">No hay clientes disponibles.</td></tr></tbody></table></div>';
        } else {
            const tbody = contenedor.querySelector('tbody');
            if (tbody) tbody.innerHTML = '<tr><td colspan="9" class="sin-productos">No hay clientes disponibles.</td></tr>';
        }
        actualizarContador();
        const pag = contenedor.querySelector('.admin-paginacion-wrapper');
        if (pag) pag.remove();
        return;
    }

    if (esPrimeraVez || !adminTablaHeaderHTML) {
        adminTablaHeaderHTML = getClientesTablaHeader(busquedaClienteActual, totalTodos, totalInactivos);
    }

    const filasHtml = clientes.map(cli => {
        const estadoHtml = cli.activo == 1
            ? '<span class="admin-badge badge-activo">Activo</span>'
            : '<span class="admin-badge badge-inactivo">Inactivo</span>';
        return `
            <tr class="${cli.activo == 0 ? 'fila-inactiva' : ''}"
                data-nombre="${(cli.nombre || '').replace(/"/g, '&quot;')}"
                data-apellidos="${(cli.apellidos || '').replace(/"/g, '&quot;')}"
                data-direccion="${(cli.direccion || '').replace(/"/g, '&quot;')}"
                data-fecha-alta="${cli.fecha_alta || ''}"
                data-puntos="${cli.puntos || 0}" data-activo="${cli.activo}">
                <td class="col-nombre">${cli.dni}</td>
                <td>${cli.nombre || '—'}</td>
                <td>${cli.apellidos || '—'}</td>
                <td>${cli.fecha_alta || '—'}</td>
                <td>${cli.productos_comprados || 0}</td>
                <td>${cli.compras_realizadas || 0}</td>
                <td style="font-weight:bold;color:#10b981;">${(cli.puntos || 0).toLocaleString('es-ES')}</td>
                <td>${estadoHtml}</td>
                <td class="col-acciones">
                    <button class="btn-admin-accion btn-ver" onclick="verCliente(${cli.id})" title="Ver"><i class="fas fa-eye"></i></button>
                    <button class="btn-admin-accion btn-editar" onclick="editarCliente(${cli.id})" title="Editar"><i class="fas fa-pen"></i></button>
                    <button class="btn-admin-accion btn-eliminar" onclick="eliminarCliente(${cli.id})" title="Eliminar"><i class="fas fa-trash"></i></button>
                </td>
            </tr>`;
    }).join('');

    if (esPrimeraVez) {
        contenedor.innerHTML = adminTablaHeaderHTML + filasHtml + '</tbody></table></div>' +
            getPaginacionClientesHTML(totalPaginasClientes);
        ajustarTodosInputsPaginacion();
    } else {
        const tbody = contenedor.querySelector('tbody');
        if (tbody) tbody.innerHTML = filasHtml;
        actualizarContador();
        actualizarPaginacionDOM(contenedor, getPaginacionClientesHTML(totalPaginasClientes));
    }
}

function buscarClientes() {
    clearTimeout(debounceTimerClientes);
    debounceTimerClientes = setTimeout(() => {
        cargarClientesAdmin(document.getElementById('inputBuscarCliente')?.value, true);
    }, 300);
}

function nuevoCliente() {
    ['clienteHabitualDni', 'clienteHabitualNombre', 'clienteHabitualApellidos'].forEach(id => {
        document.getElementById(id).value = '';
    });
    const now = new Date();
    document.getElementById('clienteHabitualFecha').value =
        new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().slice(0, 16);
    document.getElementById('btnGuardarClienteHabitual').onclick = guardarClienteHabitualAdmin;
    document.getElementById('modalClienteHabitual').style.display = 'flex';
    document.getElementById('clienteHabitualDni').focus();
}

async function guardarClienteHabitualAdmin() {
    const dni = document.getElementById('clienteHabitualDni').value.trim();
    const nombre = document.getElementById('clienteHabitualNombre').value.trim();
    const apellidos = document.getElementById('clienteHabitualApellidos').value.trim();
    const direccion = document.getElementById('clienteHabitualDireccion').value.trim();
    if (!dni || !nombre || !apellidos) { alert('Por favor, complete todos los campos obligatorios.'); return; }

    const now = new Date();
    const fecha_alta = new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().slice(0, 16);
    const btn = document.getElementById('btnGuardarClienteHabitual');
    btn.disabled = true; btn.textContent = 'Guardando...';

    try {
        const fd = new FormData();
        fd.append('dni', dni); fd.append('nombre', nombre);
        fd.append('apellidos', apellidos); fd.append('direccion', direccion); fd.append('fecha_alta', fecha_alta);
        const r = await fetch('api/clientes.php', { method: 'POST', body: fd });
        const data = await r.json();
        if (data.ok) { alert('Cliente guardado correctamente'); cerrarModal('modalClienteHabitual'); cargarClientesAdmin(); }
        else alert(data.error || 'Error al guardar el cliente');
    } catch (e) { alert('Error al comunicar con el servidor'); }
    finally { btn.disabled = false; btn.textContent = 'Guardar'; }
}

async function eliminarCliente(id) {
    if (!confirm('¿Estás seguro de que quieres eliminar este cliente?')) return;
    try {
        const r = await fetch('api/clientes.php?eliminar=' + id, { method: 'DELETE' });
        const data = await r.json();
        if (data.ok) { alert('Cliente eliminado correctamente'); cargarClientesAdmin(); }
        else alert(data.error || 'Error al eliminar el cliente');
    } catch (e) { alert('Error al comunicar con el servidor'); }
}

function verCliente(id) {
    const fila = document.querySelector(`tr [onclick="verCliente(${id})"]`).closest('tr');
    const celdas = fila.querySelectorAll('td');
    const estado = celdas[7].querySelector('.admin-badge')?.textContent.trim() || 'Activo';

    const modal = document.createElement('div');
    modal.id = 'modalVerCliente';
    modal.className = 'modal-overlay';
    modal.style.display = 'flex';
    modal.innerHTML = `
        <div class="modal-content" style="max-width:450px;text-align:left;">
            <h3 style="margin-bottom:20px;">Detalles del Cliente</h3>
            <div style="display:grid;gap:12px;">
                ${[['DNI', celdas[0].textContent.trim()], ['Nombre', celdas[1].textContent.trim()],
        ['Apellidos', celdas[2].textContent.trim()], ['Dirección', fila.dataset.direccion || '—'], ['Fecha de Alta', celdas[3].textContent.trim()],
        ['Productos Comprados', celdas[4].textContent.trim()], ['Compras Realizadas', celdas[5].textContent.trim()]]
            .map(([k, v]) => `<div style="display:flex;justify-content:space-between;border-bottom:1px solid var(--border-main);padding-bottom:8px;">
                        <span style="font-weight:500;">${k}:</span><span>${v}</span></div>`).join('')}
                <div style="display:flex;justify-content:space-between;padding-bottom:8px;">
                    <span style="font-weight:500;">Estado:</span>
                    <span class="admin-badge ${estado === 'Activo' ? 'badge-activo' : 'badge-inactivo'}">${estado}</span>
                </div>
            </div>
            <div style="display:flex;justify-content:center;gap:15px;margin-top:25px;">
                <button class="btn-admin-accion btn-ver"
                    onclick="cerrarModal('modalVerCliente');document.getElementById('modalVerCliente').remove();verComprasCliente('${celdas[0].textContent.trim()}')"
                    style="min-width:180px;"><i class="fas fa-shopping-bag"></i> Ver Compras</button>
                <button class="btn-modal-cancelar"
                    onclick="cerrarModal('modalVerCliente');document.getElementById('modalVerCliente').remove();"
                    style="min-width:100px;">Cerrar</button>
            </div>
        </div>`;

    document.getElementById('modalVerCliente')?.remove();
    document.body.appendChild(modal);
}

function editarCliente(id) {
    const fila = document.querySelector(`tr [onclick="editarCliente(${id})"]`).closest('tr');
    const celdas = fila.querySelectorAll('td');
    const form = document.getElementById('modalEditarCliente');
    form.dataset.originalDni = celdas[0].textContent.trim();
    form.dataset.originalNombre = celdas[1].textContent.trim();
    form.dataset.originalApellidos = celdas[2].textContent.trim();
    form.dataset.originalDireccion = fila.dataset.direccion || '';
    form.dataset.originalPuntos = fila.dataset.puntos || 0;

    document.getElementById('editarClienteId').value = id;
    document.getElementById('editarClienteDni').value = celdas[0].textContent.trim();
    document.getElementById('editarClienteNombre').value = celdas[1].textContent.trim() === '—' ? '' : celdas[1].textContent.trim();
    document.getElementById('editarClienteApellidos').value = celdas[2].textContent.trim() === '—' ? '' : celdas[2].textContent.trim();
    document.getElementById('editarClienteDireccion').value = fila.dataset.direccion || '';
    document.getElementById('editarClientePuntos').value = fila.dataset.puntos || 0;
    form.style.display = 'flex';
}

async function guardarClienteEditado() {
    const id = document.getElementById('editarClienteId').value;
    const fila = document.querySelector(`tr [onclick="editarCliente(${id})"]`).closest('tr');
    const fecha_alta = fila.querySelectorAll('td')[3].textContent.trim();
    const form = document.getElementById('modalEditarCliente');

    let dni = document.getElementById('editarClienteDni').value.trim() || form.dataset.originalDni;
    let nombre = document.getElementById('editarClienteNombre').value.trim() || form.dataset.originalNombre;
    let apellidos = document.getElementById('editarClienteApellidos').value.trim() || form.dataset.originalApellidos;
    let direccion = document.getElementById('editarClienteDireccion').value.trim() || form.dataset.originalDireccion;
    let puntos = document.getElementById('editarClientePuntos').value.trim();
    if (puntos === '') puntos = form.dataset.originalPuntos;

    if (!dni) { alert('El DNI es obligatorio'); return; }
    if (parseInt(puntos) < 0) { alert('Los puntos no pueden ser negativos'); return; }

    const btn = document.getElementById('btnGuardarClienteEditado');
    btn.disabled = true; btn.textContent = 'Guardando...';
    try {
        const r = await fetch('api/clientes.php?actualizar=true', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `id=${encodeURIComponent(id)}&dni=${encodeURIComponent(dni)}&nombre=${encodeURIComponent(nombre)}&apellidos=${encodeURIComponent(apellidos)}&direccion=${encodeURIComponent(direccion)}&fecha_alta=${encodeURIComponent(fecha_alta)}&puntos=${encodeURIComponent(puntos)}`
        });
        const data = await r.json();
        if (data.ok) { alert('Cliente actualizado correctamente'); cerrarModal('modalEditarCliente'); cargarClientesAdmin(); }
        else alert(data.error || 'Error al actualizar el cliente');
    } catch (e) { alert('Error al comunicar con el servidor'); }
    finally { btn.disabled = false; btn.textContent = 'Guardar'; }
}

// ── Carrusel de compras ───────────────────────────────────────────────────────

function verComprasCliente(dni) {
    const isDark = document.body.classList.contains('dark-mode');
    const textColor = isDark ? '#e5e7eb' : '#1f2937';
    const labelColor = isDark ? '#9ca3af' : '#6b7280';
    const borderColor = isDark ? '#374151' : '#e5e7eb';
    const bgColor = isDark ? '#1f2937' : '#ffffff';

    document.getElementById('modalVerCompras')?.remove();
    const loadingModal = document.createElement('div');
    loadingModal.id = 'modalVerCompras';
    loadingModal.className = 'modal-overlay';
    loadingModal.style.display = 'flex';
    loadingModal.innerHTML = `<div class="modal-content" style="max-width:700px;text-align:left;">
        <h3 style="margin-bottom:15px;">Compras del Cliente</h3>
        <p>Cargando compras...</p></div>`;
    document.body.appendChild(loadingModal);

    fetch(`api/clientes.php?compras=1&dni=${encodeURIComponent(dni)}`)
        .then(r => r.json())
        .then(ventas => {
            const modal = document.getElementById('modalVerCompras');
            if (!ventas || ventas.error) throw new Error(ventas?.error || 'Error');
            if (!ventas.length) {
                modal.innerHTML = `<div class="modal-content" style="max-width:500px;text-align:left;">
                    <h3>Compras del Cliente</h3>
                    <p>Este cliente no tiene compras registradas.</p>
                    <button class="btn-modal-cancelar" onclick="document.getElementById('modalVerCompras').remove()">Cerrar</button>
                </div>`;
                return;
            }

            let slidesHtml = '';
            ventas.forEach((v, i) => {
                const fecha = new Date(v.fecha).toLocaleString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
                const icon = { efectivo: 'fa-money-bill-wave', tarjeta: 'fa-credit-card', bizum: 'fa-mobile-alt' }[v.metodoPago] || 'fa-money-bill-wave';
                const lineasHtml = (v.lineas || []).map(l => `
                    <tr style="border-bottom:1px solid ${borderColor};">
                        <td style="padding:8px;">${l.producto_nombre || 'Producto'}</td>
                        <td style="padding:8px;text-align:center;">${l.cantidad}</td>
                        <td style="padding:8px;text-align:right;">${l.precioUnitarioConIva.toFixed(2).replace('.', ',')} €</td>
                        <td style="padding:8px;text-align:right;">${l.subtotalConIva.toFixed(2).replace('.', ',')} €</td>
                    </tr>`).join('');

                slidesHtml += `
                    <div class="carousel-sale-slide" data-index="${i}" style="display:${i === 0 ? 'block' : 'none'};">
                        <div style="background:${isDark ? '#374151' : '#f3f4f6'};padding:12px;border-radius:8px;margin-bottom:15px;">
                            <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:10px;">
                                <div><span style="color:${labelColor};font-size:13px;">${v.serie || 'T'}${String(v.numero || v.id).padStart(5, '0')}</span>
                                    <div style="font-weight:600;">${fecha}</div></div>
                                <div style="text-align:right;"><span style="color:${labelColor};font-size:13px;">${v.usuario_nombre || 'Cajero'}</span>
                                    <div><i class="fas ${icon}"></i> ${v.metodoPago}</div></div>
                            </div>
                        </div>
                        <div style="max-height:250px;overflow-y:auto;border:1px solid ${borderColor};border-radius:8px;">
                            <table style="width:100%;border-collapse:collapse;font-size:13px;">
                                <thead style="background:${isDark ? '#1f2937' : '#f9fafb'};position:sticky;top:0;">
                                    <tr>
                                        <th style="padding:8px;text-align:left;">Producto</th>
                                        <th style="padding:8px;text-align:center;">Cant.</th>
                                        <th style="padding:8px;text-align:right;">Precio</th>
                                        <th style="padding:8px;text-align:right;">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>${lineasHtml}</tbody>
                            </table>
                        </div>
                        <div style="display:flex;justify-content:flex-end;margin-top:12px;">
                            <div style="background:${isDark ? '#1f2937' : '#f3f4f6'};padding:10px 20px;border-radius:8px;">
                                Total: <strong>${parseFloat(v.total).toFixed(2).replace('.', ',')} €</strong>
                            </div>
                        </div>
                    </div>`;
            });

            modal.innerHTML = `
                <div class="modal-content" style="width:900px;min-height:520px;max-height:85vh;text-align:left;
                    overflow:hidden;display:flex;flex-direction:column;background:${bgColor};
                    box-sizing:border-box;position:relative;padding:20px 90px;">
                    <button onclick="firstSaleSlide()" style="position:absolute;left:15px;top:50%;transform:translateY(-50%);background:none;border:none;font-size:20px;cursor:pointer;padding:10px;">
                        <i class="fas fa-angle-double-left"></i></button>
                    <button onclick="prevSaleSlide()" style="position:absolute;left:60px;top:50%;transform:translateY(-50%);background:none;border:none;font-size:28px;cursor:pointer;padding:10px;">
                        <i class="fas fa-chevron-left"></i></button>
                    <button onclick="nextSaleSlide()" style="position:absolute;right:60px;top:50%;transform:translateY(-50%);background:none;border:none;font-size:28px;cursor:pointer;padding:10px;">
                        <i class="fas fa-chevron-right"></i></button>
                    <button onclick="lastSaleSlide()" style="position:absolute;right:15px;top:50%;transform:translateY(-50%);background:none;border:none;font-size:20px;cursor:pointer;padding:10px;">
                        <i class="fas fa-angle-double-right"></i></button>
                    <h3 style="margin-bottom:15px;text-align:center;">
                        <span id="compraActualTitulo">Compra 1 de ${ventas.length}</span></h3>
                    <div style="flex:1;overflow-y:auto;padding:5px;min-height:350px;max-height:400px;">${slidesHtml}</div>
                    <div style="display:flex;justify-content:center;margin-top:5px;">
                        <button class="btn-modal-cancelar" onclick="document.getElementById('modalVerCompras').remove()">Cerrar</button>
                    </div>
                </div>`;
            modal.dataset.totalSlides = ventas.length;
            currentSaleSlide = 0;
        })
        .catch(err => {
            document.getElementById('modalVerCompras').innerHTML = `
                <div class="modal-content" style="max-width:500px;">
                    <h3>Error</h3><p>Error al cargar las compras del cliente.</p>
                    <button class="btn-modal-cancelar" onclick="document.getElementById('modalVerCompras').remove()">Cerrar</button>
                </div>`;
        });
}

function changeSaleSlide(index) {
    const modal = document.getElementById('modalVerCompras');
    if (!modal) return;
    const totalSlides = parseInt(modal.dataset.totalSlides) || 0;
    modal.querySelectorAll('.carousel-sale-slide').forEach(s => s.style.display = 'none');
    modal.querySelectorAll('.carousel-sale-slide')[index].style.display = 'block';
    const titulo = document.getElementById('compraActualTitulo');
    if (titulo) titulo.textContent = `Compra ${index + 1} de ${totalSlides}`;
    currentSaleSlide = index;
}
function nextSaleSlide() { const m = document.getElementById('modalVerCompras'); if (m) changeSaleSlide((currentSaleSlide + 1) % parseInt(m.dataset.totalSlides)); }
function prevSaleSlide() { const m = document.getElementById('modalVerCompras'); if (m) changeSaleSlide((currentSaleSlide - 1 + parseInt(m.dataset.totalSlides)) % parseInt(m.dataset.totalSlides)); }
function firstSaleSlide() { changeSaleSlide(0); }
function lastSaleSlide() { const m = document.getElementById('modalVerCompras'); if (m) changeSaleSlide(parseInt(m.dataset.totalSlides) - 1); }

// ═══════════════════════════════════════════════════════════════════════════════
// PROVEEDORES
// ═══════════════════════════════════════════════════════════════════════════════

function getProveedoresTablaHeader(textoBusqueda = '') {
    return `
        <div class="admin-tabla-header">
            <div style="display:flex;gap:10px;width:100%;align-items:center;flex-wrap:wrap;">
                <div style="display:flex;align-items:center;gap:10px;">
                    <label for="inputBuscarProveedor" class="admin-label">Buscar:</label>
                    <input type="text" id="inputBuscarProveedor" class="input-buscarProducto"
                        placeholder="Escribe el nombre del proveedor..." oninput="buscarProveedores()"
                        autocomplete="off" value="${textoBusqueda.replace(/"/g, '&quot;')}" style="width:400px;">
                </div>
                <button class="btn-admin-accion btn-nuevo" onclick="nuevoProveedor()">
                    <i class="fas fa-plus"></i> Nuevo Proveedor
                </button>
            </div>
        </div>
        <div class="admin-tabla-wrapper">
            <table class="admin-tabla">
                <thead><tr>
                    <th>#</th><th>Nombre</th><th>Contacto</th><th>Email</th>
                    <th>Dirección</th><th>Estado</th><th>Acciones</th>
                </tr></thead>
                <tbody>`;
}

function cargarProveedoresAdmin(textoBusqueda = '') {
    const contenedor = document.getElementById('adminContenido');
    const tablaExistente = contenedor.querySelector('.admin-tabla');
    if (seccionActual !== 'proveedores') { adminTablaHeaderHTML = ''; seccionActual = 'proveedores'; }
    const esPrimeraVez = !tablaExistente || !adminTablaHeaderHTML;

    const params = new URLSearchParams();
    if (textoBusqueda) params.append('buscar', textoBusqueda);

    return fetch('api/proveedores.php?' + params)
        .then(r => { if (!r.ok) return r.json().then(e => { throw new Error(e.error || 'Error'); }); return r.json(); })
        .then(data => renderProveedoresAdmin(data, esPrimeraVez))
        .catch(err => { console.error('Error cargando proveedores:', err); contenedor.innerHTML = '<p class="sin-productos">' + err.message + '</p>'; });
}

function renderProveedoresAdmin(proveedores, esPrimeraVez = true) {
    const contenedor = document.getElementById('adminContenido');
    if (!proveedores || !proveedores.length) {
        if (esPrimeraVez || !adminTablaHeaderHTML) {
            adminTablaHeaderHTML = getProveedoresTablaHeader();
            contenedor.innerHTML = adminTablaHeaderHTML + '<tr><td colspan="7" class="sin-productos">No hay proveedores disponibles.</td></tr></tbody></table></div>';
        } else {
            const tbody = contenedor.querySelector('tbody');
            if (tbody) tbody.innerHTML = '<tr><td colspan="7" class="sin-productos">No hay proveedores disponibles.</td></tr>';
        }
        return;
    }

    if (esPrimeraVez || !adminTablaHeaderHTML) adminTablaHeaderHTML = getProveedoresTablaHeader();

    const generarFilas = () => proveedores.map(prov => {
        const estadoHtml = prov.activo === 1
            ? '<span class="admin-badge badge-activo">Activo</span>'
            : '<span class="admin-badge badge-inactivo">Inactivo</span>';
        return `
            <tr class="${prov.activo == 0 ? 'fila-inactiva' : ''}"
                data-contacto="${(prov.contacto || '').replace(/"/g, '&quot;')}"
                data-email="${(prov.email || '').replace(/"/g, '&quot;')}"
                data-direccion="${(prov.direccion || '').replace(/"/g, '&quot;')}"
                data-activo="${prov.activo}">
                <td class="col-id">${prov.id}</td>
                <td class="col-nombre">${prov.nombre}</td>
                <td>${prov.contacto || '—'}</td>
                <td>${prov.email || '—'}</td>
                <td>${prov.direccion || '—'}</td>
                <td class="col-estado">${estadoHtml}</td>
                <td class="col-acciones">
                    <button class="btn-admin-accion btn-ver" onclick="verProveedor(${prov.id})" title="Ver"><i class="fas fa-eye"></i></button>
                    <button class="btn-admin-accion btn-editar" onclick="editarProveedor(${prov.id})" title="Editar"><i class="fas fa-pen"></i></button>
                    <button class="btn-admin-accion btn-eliminar" onclick="confirmarEliminarProveedor(${prov.id},'${prov.nombre.replace(/'/g, "\\'")}')"><i class="fas fa-trash"></i></button>
                </td>
            </tr>`;
    }).join('');

    ejecutarCuandoIdle(generarFilas, (filasHtml) => {
        const html = adminTablaHeaderHTML + filasHtml + '</tbody></table></div>';
        if (esPrimeraVez) {
            contenedor.innerHTML = html;
        } else {
            const tbody = contenedor.querySelector('tbody');
            if (tbody) tbody.innerHTML = filasHtml;
            else contenedor.innerHTML = html;
        }
    });
}

function buscarProveedores() {
    clearTimeout(debounceTimerProveedores);
    debounceTimerProveedores = setTimeout(() => {
        const texto = document.getElementById('inputBuscarProveedor')?.value || '';
        const params = new URLSearchParams();
        if (texto) params.append('buscar', texto);
        fetch('api/proveedores.php?' + params)
            .then(r => r.json())
            .then(data => renderProveedoresAdmin(data, false))
            .catch(err => console.error('Error buscando proveedores:', err));
    }, 300);
}

function verProveedor(id) {
    proveedorActualId = id;
    const fila = document.querySelector(`tr [onclick="verProveedor(${id})"]`).closest('tr');
    const celdas = fila.querySelectorAll('td');
    document.getElementById('verProveedorNombre').textContent = celdas[1].textContent.trim();
    document.getElementById('verProveedorContacto').textContent = fila.dataset.contacto || '—';
    document.getElementById('verProveedorEmail').textContent = fila.dataset.email || '—';
    document.getElementById('verProveedorDireccion').textContent = fila.dataset.direccion || '—';
    const estado = celdas[5].querySelector('.admin-badge')?.textContent.trim() ?? '—';
    document.getElementById('verProveedorEstado').innerHTML = estado === 'Activo'
        ? '<span class="admin-badge badge-activo">Activo</span>'
        : '<span class="admin-badge badge-inactivo">Inactivo</span>';
    document.getElementById('modalVerProveedor').style.display = 'flex';
    cargarProductosProveedor(id);
}

function nuevoProveedor() {
    ['editProveedorId', 'editProveedorNombre', 'editProveedorContacto', 'editProveedorEmail', 'editProveedorDireccion'].forEach(id => {
        document.getElementById(id).value = '';
    });
    document.getElementById('editProveedorEstado').value = '1';
    document.getElementById('editProveedorTitulo').textContent = 'Nuevo Proveedor';
    abrirModal('modalEditarProveedor');
}

function editarProveedor(id) {
    const fila = document.querySelector(`tr [onclick="editarProveedor(${id})"]`).closest('tr');
    const celdas = fila.querySelectorAll('td');
    document.getElementById('editProveedorId').value = id;
    document.getElementById('editProveedorNombre').value = celdas[1].textContent.trim();
    document.getElementById('editProveedorContacto').value = fila.dataset.contacto || '';
    document.getElementById('editProveedorEmail').value = fila.dataset.email || '';
    document.getElementById('editProveedorDireccion').value = fila.dataset.direccion || '';
    document.getElementById('editProveedorEstado').value = fila.dataset.activo;
    document.getElementById('editProveedorTitulo').textContent = 'Editar Proveedor';
    abrirModal('modalEditarProveedor');
}

function guardarCambiosProveedor() {
    const id = document.getElementById('editProveedorId').value;
    const nombre = document.getElementById('editProveedorNombre').value.trim();
    if (!nombre) { alert('El nombre del proveedor es obligatorio.'); return; }

    const fd = new FormData();
    fd.append('id', id);
    fd.append('nombre', nombre);
    fd.append('contacto', document.getElementById('editProveedorContacto').value.trim());
    fd.append('email', document.getElementById('editProveedorEmail').value.trim());
    fd.append('direccion', document.getElementById('editProveedorDireccion').value.trim());
    fd.append('activo', document.getElementById('editProveedorEstado').value);

    fetch('api/proveedores.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.ok) { cerrarModal('modalEditarProveedor'); cargarProveedoresAdmin(); }
            else alert('Error al guardar: ' + (data.error ?? ''));
        })
        .catch(err => console.error('Error guardando proveedor:', err));
}

function confirmarEliminarProveedor(id, nombre) {
    if (confirm(`¿Seguro que quieres eliminar "${nombre}"?`)) eliminarProveedor(id);
}

function eliminarProveedor(id) {
    fetch(`api/proveedores.php?eliminar=${id}`, { method: 'DELETE' })
        .then(r => r.json())
        .then(data => { if (data.ok) cargarProveedoresAdmin(); else alert('Error al eliminar el proveedor.'); })
        .catch(err => console.error('Error eliminando proveedor:', err));
}

// ── Productos por proveedor ───────────────────────────────────────────────────

function cargarProductosProveedor(idProveedor) {
    const tbody = document.getElementById('listaProductosProveedor');
    const msg = document.getElementById('msgSinProductosProveedor');
    tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;">Cargando productos...</td></tr>';
    msg.style.display = 'none';

    fetch(`api/proveedores.php?productos=${idProveedor}`)
        .then(r => r.json())
        .then(productos => {
            tbody.innerHTML = '';
            if (!productos || !productos.length) { msg.style.display = 'block'; return; }
            msg.style.display = 'none';
            productos.forEach(prod => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td style="padding:8px;min-width:150px;">${prod.nombre}</td>
                    <td style="padding:8px;text-align:center;width:150px;">${parseFloat((prod.precio * 0.70) || 0).toFixed(2)} €</td>
                    <td style="padding:8px;text-align:center;width:130px;">${parseFloat(prod.recargoEquivalencia).toFixed(2)}%</td>
                    <td style="padding:8px;text-align:center;width:100px;">
                        <span style="font-size:.75rem;color:#6b7280;">C: ${parseFloat(prod.precio * 0.70 * (1 + parseFloat(prod.recargoEquivalencia || 0) / 100)).toFixed(2)} €</span><br>
                        <span style="font-size:.75rem;color:#22c55e;">V: ${parseFloat(prod.precio || 0).toFixed(2)} €</span>
                    </td>
                    <td style="padding:8px;text-align:center;width:100px;">
                        <button class="btn-admin-accion btn-editar" style="padding:4px;font-size:.8rem;"
                            onclick="editarRecargoProveedor(${prod.idAsociacion},${prod.idProducto},'${prod.nombre.replace(/'/g, "\\'")}',${prod.recargoEquivalencia},${prod.precioProveedor || 0})">
                            <i class="fas fa-pen"></i></button>
                        <button class="btn-admin-accion btn-eliminar" style="padding:4px;font-size:.8rem;"
                            onclick="confirmarEliminarProductoProveedor(${prod.idAsociacion},'${prod.nombre.replace(/'/g, "\\'")}')">
                            <i class="fas fa-trash"></i></button>
                    </td>`;
                tbody.appendChild(tr);
            });
        })
        .catch(err => { tbody.innerHTML = '<tr><td colspan="5" style="color:red;">Error al cargar los productos.</td></tr>'; });
}

function agregarProductoProveedor() {
    document.getElementById('asociarProductoTitulo').textContent = 'Añadir Producto';
    document.getElementById('asociarProductoSubtitulo').textContent = 'Selecciona un producto disponible y fija su recargo';
    document.getElementById('asociarProvIdAsociacion').value = '';
    document.getElementById('asociarProvIdProveedor').value = proveedorActualId;
    document.getElementById('asociarProvPrecio').value = '0.00';
    document.getElementById('asociarProvRecargo').value = '0.00';
    document.getElementById('contenedorSelectProducto').style.display = 'flex';
    document.getElementById('contenedorTextoProducto').style.display = 'none';

    fetch(`api/proveedores.php?productosDisponibles=${proveedorActualId}`)
        .then(r => r.json())
        .then(productos => {
            const sel = document.getElementById('asociarProvIdProducto');
            sel.innerHTML = '';
            if (!productos || !productos.length) { sel.innerHTML = '<option value="">Sin productos disponibles</option>'; alert('No hay productos disponibles para asociar.'); return; }
            productos.forEach(p => { const o = document.createElement('option'); o.value = p.id; o.textContent = p.nombre; sel.appendChild(o); });
            cerrarModal('modalVerProveedor');
            abrirModal('modalAsociarProducto');
        });
}

function editarRecargoProveedor(idAsociacion, idProducto, nombreProducto, recargo, precioProv) {
    document.getElementById('asociarProductoTitulo').textContent = 'Editar Producto de Proveedor';
    document.getElementById('asociarProductoSubtitulo').textContent = 'Modifica el precio y recargo de equivalencia';
    document.getElementById('asociarProvIdAsociacion').value = idAsociacion;
    document.getElementById('asociarProvIdProveedor').value = proveedorActualId;
    document.getElementById('asociarProvPrecio').value = precioProv;
    document.getElementById('asociarProvRecargo').value = recargo;
    document.getElementById('contenedorSelectProducto').style.display = 'none';
    document.getElementById('contenedorTextoProducto').style.display = 'flex';
    document.getElementById('asociarProvNombreProducto').value = nombreProducto;
    cerrarModal('modalVerProveedor');
    abrirModal('modalAsociarProducto');
}

function guardarCambiosAsociarProducto() {
    const idAsociacion = document.getElementById('asociarProvIdAsociacion').value;
    const idProveedor = document.getElementById('asociarProvIdProveedor').value;
    const idProducto = document.getElementById('asociarProvIdProducto').value;
    const precio = document.getElementById('asociarProvPrecio').value;
    const recargo = document.getElementById('asociarProvRecargo').value;

    const fd = new FormData();
    fd.append('precioProveedor', precio);
    fd.append('recargoEquivalencia', recargo);

    if (idAsociacion) {
        fd.append('accion', 'actualizarRecargo');
        fd.append('idAsociacion', idAsociacion);
    } else {
        fd.append('accion', 'agregarProducto');
        fd.append('idProveedor', idProveedor);
        fd.append('idProducto', idProducto);
    }

    fetch('api/proveedores.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.ok) { cerrarModal('modalAsociarProducto'); abrirModal('modalVerProveedor'); cargarProductosProveedor(idProveedor); }
            else alert('Error: ' + (data.error ?? 'Error desconocido'));
        });
}

function confirmarEliminarProductoProveedor(idAsociacion, nombreProducto) {
    if (confirm(`¿Seguro que quieres dejar de suministrar "${nombreProducto}" a través de este proveedor?`)) {
        fetch(`api/proveedores.php?eliminarAsociacion=${idAsociacion}`, { method: 'DELETE' })
            .then(r => r.json())
            .then(data => { if (data.ok) cargarProductosProveedor(proveedorActualId); else alert('Error al eliminar: ' + (data.error ?? '')); });
    }
}