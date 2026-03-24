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

    const contadorHTML = hayBusqueda
        ? `${totalClientes.toLocaleString('es-ES')} Resultado${totalClientes !== 1 ? 's' : ''}`
        : `${totalClientes.toLocaleString('es-ES')} Total | ${totalActivos.toLocaleString('es-ES')} Activo${totalActivos !== 1 ? 's' : ''} | ${totalInactivos.toLocaleString('es-ES')} Inactivo${totalInactivos !== 1 ? 's' : ''}`;

    return `
        <div class="admin-tabla-header">
            <div style="display: flex; gap: 10px; width: 100%; align-items: center; flex-wrap: wrap;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <label for="inputBuscarCliente" class="admin-label">Buscar:</label>
                    <input type="text" id="inputBuscarCliente" class="input-buscarProducto"
                        placeholder="Escribe el DNI del cliente..." oninput="buscarClientes()" autocomplete="off"
                        value="${textoBusqueda.replace(/"/g, '&quot;')}" style="width: 400px;" />
                </div>
                <button class="btn-admin-accion btn-nuevo" onclick="nuevoCliente()">
                    <i class="fas fa-plus"></i> Nuevo Cliente
                </button>
                <span id="totalClientesAviso" class="total-clientes-aviso">
                    ${contadorHTML}
                </span>
            </div>
        </div>
        <div class="admin-tabla-wrapper sin-scroll">
            <table class="admin-tabla">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>DNI</th>
                        <th>Nombre</th>
                        <th>Apellidos</th>
                        <th>Fecha Alta</th>
                        <th>Productos</th>
                        <th>Compras</th>
                        <th>Puntos</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
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

/**
 * Renderiza la respuesta paginada de clientes en formato tabla.
 */
function renderClientesAdmin(respuesta, esPrimeraVez = true) {
    const contenedor = document.getElementById('adminContenido');

    const clientes = respuesta.clientes || [];
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
            const estadoHtml = cli.activo === 1
                ? '<span class="admin-badge badge-activo">Activo</span>'
                : '<span class="admin-badge badge-inactivo">Inactivo</span>';
            const fechaAlta = cli.fecha_alta
                ? new Date(cli.fecha_alta).toLocaleDateString('es-ES')
                : '—';

            filasHtml += `
                <tr class="${cli.activo == 0 ? 'fila-inactiva' : ''}">
                    <td class="col-id">${cli.id}</td>
                    <td>${cli.dni || '—'}</td>
                    <td>${cli.nombre || '—'}</td>
                    <td>${cli.apellidos || '—'}</td>
                    <td>${fechaAlta}</td>
                    <td style="text-align:center">${cli.total_productos || 0}</td>
                    <td style="text-align:center">${cli.total_compras || 0}</td>
                    <td style="text-align:center">${cli.puntos || 0}</td>
                    <td class="col-estado">${estadoHtml}</td>
                    <td class="col-acciones">
                        <button class="btn-admin-accion btn-ver" onclick="verCliente(${cli.id})" title="Ver">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn-admin-accion btn-editar" onclick="editarCliente(${cli.id})" title="Editar">
                            <i class="fas fa-pen"></i>
                        </button>
                        <button class="btn-admin-accion btn-eliminar" onclick="confirmarEliminarCliente(${cli.id}, '${(cli.nombre || '').replace(/'/g, "\\'")}')" title="Eliminar">
                            <i class="fas fa-trash"></i>
                        </button>
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

// ── PROVEEDORES ───────────────────────────────────────────────────────────────

/**
 * Genera el HTML del header de la tabla de proveedores con buscador.
 */
function getProveedoresTablaHeader(textoBusqueda = '') {
    return `
        <div class="admin-tabla-header">
            <div style="display: flex; gap: 10px; width: 100%; align-items: center; flex-wrap: wrap;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <label for="inputBuscarProveedor" class="admin-label">Buscar:</label>
                    <input type="text" id="inputBuscarProveedor" class="input-buscarProducto"
                        placeholder="Escribe el nombre del proveedor..." oninput="buscarProveedores()" autocomplete="off"
                        value="${textoBusqueda.replace(/"/g, '&quot;')}" style="width: 400px;" />
                </div>
                <button class="btn-admin-accion btn-nuevo" onclick="nuevoProveedor()">
                    <i class="fas fa-plus"></i> Nuevo Proveedor
                </button>
            </div>
        </div>
        <div class="admin-tabla-wrapper">
            <table class="admin-tabla">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nombre</th>
                        <th>Contacto</th>
                        <th>Email</th>
                        <th>Dirección</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
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

    const generarFilasProveedores = () => {
        let filasHtml = '';
        proveedores.forEach(prov => {
            const estadoHtml = prov.activo === 1
                ? '<span class="admin-badge badge-activo">Activo</span>'
                : '<span class="admin-badge badge-inactivo">Inactivo</span>';

            filasHtml += `
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
                        <button class="btn-admin-accion btn-ver" onclick="verProveedor(${prov.id})" title="Ver">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn-admin-accion btn-editar" onclick="editarProveedor(${prov.id})" title="Editar">
                            <i class="fas fa-pen"></i>
                        </button>
                        <button class="btn-admin-accion btn-eliminar" onclick="confirmarEliminarProveedor(${prov.id}, '${prov.nombre.replace(/'/g, "\\'")}')" title="Eliminar">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>`;
        });
        return filasHtml;
    };

    ejecutarCuandoIdle(generarFilasProveedores, (filasHtml) => {
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