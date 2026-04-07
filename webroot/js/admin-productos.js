/**
 * admin.productos.js
 * Carga, renderizado, búsqueda y CRUD de productos en el panel de administración.
 * Depende de: admin.state.js, admin.utils.js, admin.pagination.js
 */

// ── Header HTML de la tabla (con filtros) ─────────────────────────────────────

function getAdminTablaHeader(textoBusqueda = '', idCategoriaSeleccionada = '', ordenSeleccionado = '') {
    let opcionesCategorias = '<option value="todas">Todas</option>';
    categoriasAdmin.forEach(cat => {
        const sel = cat.id == idCategoriaSeleccionada ? 'selected' : '';
        opcionesCategorias += `<option value="${cat.id}" ${sel}>${cat.nombre}</option>`;
    });

    let opcionesOrden = '';
    OPCIONES_ORDEN.forEach(opc => {
        const sel = opc.value == ordenSeleccionado ? 'selected' : '';
        opcionesOrden += `<option value="${opc.value}" ${sel}>${opc.text}</option>`;
    });

    return `
        <div class="admin-tabla-header">
            <div style="display:flex;gap:10px;width:100%;align-items:center;flex-wrap:wrap;">
                <div style="display:flex;align-items:center;gap:10px;">
                    <label for="inputBuscarProducto" class="admin-label">Buscar:</label>
                    <input type="text" id="inputBuscarProducto" class="input-buscarProducto"
                        placeholder="Escribe el nombre del producto..."
                        oninput="buscarProductos()" autocomplete="off"
                        value="${textoBusqueda.replace(/"/g, '&quot;')}" style="width:400px;">
                </div>
                <div style="display:flex;align-items:center;gap:10px;">
                    <label for="selectCategoria" class="admin-label">Categoría:</label>
                    <select id="selectCategoria" onchange="buscarProductos()"
                        style="padding:8px;border-radius:4px;border:1px solid #d1d5db;">
                        ${opcionesCategorias}
                    </select>
                </div>
                <div style="display:flex;align-items:center;gap:10px;">
                    <label for="selectOrden" class="admin-label">Ordenar:</label>
                    <select id="selectOrden" onchange="buscarProductos()"
                        style="padding:8px;border-radius:4px;border:1px solid #d1d5db;">
                        ${opcionesOrden}
                    </select>
                </div>
                <button class="btn-admin-accion ${mostrarConIva ? 'btn-ver' : 'btn-editar'}"
                    onclick="toggleMostrarIva()" style="min-width:150px;">
                    <i class="fas ${mostrarConIva ? 'fa-file-invoice-dollar' : 'fa-coins'}"></i>
                    ${mostrarConIva ? 'Ver Sin IVA' : 'Ver Con IVA'}
                </button>
                <button class="btn-admin-accion btn-ver" onclick="abrirModalEstadisticasProductos()">
                    <i class="fas fa-chart-bar"></i> Estadísticas
                </button>
                <button class="btn-admin-accion btn-nuevo" onclick="nuevoProducto()">
                    <i class="fas fa-plus"></i> Nuevo Producto
                </button>
            </div>
        </div>
        <div class="admin-tabla-wrapper sin-scroll">
            <table class="admin-tabla">
                <thead>
                    <tr>
                        <th>#</th><th>Imagen</th><th>Nombre</th><th>Categoría</th>
                        <th>Precio ${mostrarConIva ? '(PVP)' : '(Base)'}</th>
                        <th>Stock</th><th>Estado</th><th>IVA</th><th>Acciones</th>
                    </tr>
                </thead>
                <tbody>`;
}

// ── Generación de fila ────────────────────────────────────────────────────────

function generarFilaProducto(prod) {
    let precioMostrado = parseFloat(prod.precio);
    if (mostrarConIva) {
        const iva = (prod.iva != null && prod.iva !== '') ? parseInt(prod.iva) : 21;
        precioMostrado *= (1 + iva / 100);
    }
    const precioFmt = precioMostrado.toFixed(2).replace('.', ',');
    const imgSrc = prod.imagen && prod.imagen !== '' ? prod.imagen : 'webroot/img/logo.PNG';

    let stockBadge = prod.stock <= 0 ? 'badge-agotado' : prod.stock <= 3 ? 'badge-bajo' : 'badge-ok';
    const estadoHtml = prod.activo == 1
        ? '<span class="admin-badge badge-activo">Activo</span>'
        : '<span class="admin-badge badge-inactivo">Inactivo</span>';

    return `
        <tr class="${prod.stock <= 0 ? 'fila-agotada' : ''}${prod.activo == 0 ? 'fila-inactiva' : ''}"
            data-precio-base="${prod.precio}" data-iva="${prod.iva}"
            data-iva-id="${prod.idIva}" data-iva-nombre="${prod.ivaNombre || ''}">
            <td class="col-id">${prod.id}</td>
            <td class="col-img">
                <img src="${imgSrc}" alt="${prod.nombre.replace(/"/g, '&quot;')}" class="admin-tabla-img">
            </td>
            <td class="col-nombre">${prod.nombre}</td>
            <td class="col-categoria">${prod.categoria ?? '—'}</td>
            <td class="col-precio" style="text-align:right;">${precioFmt} €</td>
            <td class="col-stock"><span class="admin-badge ${stockBadge}">${prod.stock}</span></td>
            <td class="col-estado">${estadoHtml}</td>
            <td class="col-iva">${prod.iva}% (${prod.ivaNombre || 'General'})</td>
            <td class="col-acciones">
                <button class="btn-admin-accion btn-ver" onclick="verProducto(${prod.id})" title="Ver">
                    <i class="fas fa-eye"></i></button>
                <button class="btn-admin-accion btn-editar" onclick="editarProducto(${prod.id})" title="Editar">
                    <i class="fas fa-pen"></i></button>
                <button class="btn-admin-accion btn-eliminar"
                    onclick="confirmarEliminarProducto(${prod.id},'${prod.nombre.replace(/'/g, "\\'")}')" title="Eliminar">
                    <i class="fas fa-trash"></i></button>
            </td>
        </tr>`;
}

// ── Renderizado paginado ──────────────────────────────────────────────────────

function renderizarProductosPagina() {
    const contenedor = document.getElementById('adminContenido');
    if (!contenedor) return;

    const inicio = (paginaActualProductos - 1) * productosPorPagina;
    const productosPagina = productosData.slice(inicio, inicio + productosPorPagina);
    const totalPaginas = Math.ceil(productosData.length / productosPorPagina);

    const tbody = contenedor.querySelector('tbody');
    if (tbody) tbody.innerHTML = productosPagina.map(generarFilaProducto).join('');

    actualizarPaginacionDOM(contenedor, getPaginacionProductosHTML(totalPaginas));
}

function renderProductosAdmin(productos, esPrimeraVez = true, idCategoria = '', orden = '') {
    const contenedor = document.getElementById('adminContenido');
    productosData = productos || [];

    if (esPrimeraVez) paginaActualProductos = 1;

    if (!productosData.length) {
        if (esPrimeraVez || !adminTablaHeaderHTML) {
            adminTablaHeaderHTML = getAdminTablaHeader('', idCategoria, orden);
            contenedor.innerHTML = adminTablaHeaderHTML +
                '<tr><td colspan="9" class="sin-productos">No hay productos disponibles.</td></tr></tbody></table></div>';
        } else {
            const tbody = contenedor.querySelector('tbody');
            if (tbody) tbody.innerHTML = '<tr><td colspan="9" class="sin-productos">No hay productos disponibles.</td></tr>';
        }
        return;
    }

    if (esPrimeraVez || !adminTablaHeaderHTML) {
        adminTablaHeaderHTML = getAdminTablaHeader('', idCategoria, orden);
    }

    // Ordenar
    if (orden) {
        const [campo, dir] = orden.split('_');
        const asc = dir === 'asc';
        productosData.sort((a, b) => {
            let va, vb;
            switch (campo) {
                case 'nombre': va = a.nombre.toLowerCase(); vb = b.nombre.toLowerCase(); break;
                case 'id': va = a.id; vb = b.id; break;
                case 'precio': va = a.precio; vb = b.precio; break;
                case 'stock': va = a.stock; vb = b.stock; break;
                default: return 0;
            }
            if (va < vb) return asc ? -1 : 1;
            if (va > vb) return asc ? 1 : -1;
            return 0;
        });
    }

    const totalPaginas = Math.ceil(productosData.length / productosPorPagina);
    const inicio = (paginaActualProductos - 1) * productosPorPagina;
    const productosPagina = productosData.slice(inicio, inicio + productosPorPagina);

    let html = adminTablaHeaderHTML + productosPagina.map(generarFilaProducto).join('');
    html += '</tbody></table></div>' + getPaginacionProductosHTML(totalPaginas);

    if (esPrimeraVez) {
        contenedor.innerHTML = html;
    } else {
        const tbody = contenedor.querySelector('tbody');
        if (tbody) {
            tbody.innerHTML = productosPagina.map(generarFilaProducto).join('');
            actualizarPaginacionDOM(contenedor, getPaginacionProductosHTML(totalPaginas));
        } else {
            contenedor.innerHTML = html;
        }
    }
}

// ── Carga desde API ───────────────────────────────────────────────────────────

function cargarProductosAdmin(idCategoria = 'todas', textoBusqueda = '', orden = '') {
    const contenedor = document.getElementById('adminContenido');
    const tablaExistente = contenedor.querySelector('.admin-tabla');

    if (seccionActual !== 'productos') {
        adminTablaHeaderHTML = '';
        seccionActual = 'productos';
    }

    const esPrimeraVez = !tablaExistente || !adminTablaHeaderHTML;
    window.ordenActual = orden;

    const params = new URLSearchParams({ idCategoria, admin: '1' });
    if (textoBusqueda) params.append('buscarProducto', textoBusqueda);

    return fetch('api/productos.php?' + params)
        .then(r => r.json())
        .then(data => renderProductosAdmin(data, esPrimeraVez, idCategoria, orden))
        .catch(err => {
            console.error('Error cargando productos (admin):', err);
            contenedor.innerHTML = '<p class="sin-productos">Error al cargar los productos.</p>';
        });
}

// ── Búsqueda con debounce ─────────────────────────────────────────────────────

function buscarProductos() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        const texto = document.getElementById('inputBuscarProducto')?.value.trim() || '';
        const categoria = document.getElementById('selectCategoria')?.value || 'todas';
        const orden = document.getElementById('selectOrden')?.value || '';
        cargarProductosAdmin(categoria, texto, orden);
    }, 300);
}

// ── Toggle IVA ────────────────────────────────────────────────────────────────

function toggleMostrarIva() {
    mostrarConIva = !mostrarConIva;
    adminTablaHeaderHTML = '';
    const texto = document.getElementById('inputBuscarProducto')?.value || '';
    const categoria = document.getElementById('selectCategoria')?.value || 'todas';
    const orden = document.getElementById('selectOrden')?.value || '';
    cargarProductosAdmin(categoria, texto, orden);
}

// ── CRUD ──────────────────────────────────────────────────────────────────────

function nuevoProducto() {
    document.getElementById('editProductoId').value = '';
    document.getElementById('editProductoNombre').value = '';
    document.getElementById('editProductoPrecio').value = '';
    document.getElementById('editProductoStock').value = '';
    document.getElementById('editProductoEstado').value = '1';

    const selectIva = document.getElementById('editProductoIva');
    selectIva.innerHTML = tiposIva.map(t =>
        `<option value="${t.id}" ${t.id === 1 ? 'selected' : ''}>${t.porcentaje}% (${t.nombre})</option>`
    ).join('');

    document.getElementById('editProductoImagen').src = 'webroot/img/logo.PNG';
    document.getElementById('editProductoImagen').alt = '';
    document.getElementById('editProductoImagenInput').value = '';

    document.getElementById('editProductoTitulo').textContent = 'Nuevo Producto';
    document.getElementById('editProductoSubtitulo').textContent = 'Completa los datos del nuevo producto';

    const selectCat = document.getElementById('editProductoCategoria');
    selectCat.innerHTML = '<option value="">Selecciona una categoría</option>' +
        categoriasAdmin.map(c => `<option value="${c.nombre}">${c.nombre}</option>`).join('');
    selectCat.style.cssText = 'background:#fff;cursor:pointer;';

    abrirModal('modalEditarProducto');
}

function editarProducto(id) {
    const fila = document.querySelector(`tr [onclick="editarProducto(${id})"]`).closest('tr');
    const celdas = fila.querySelectorAll('td');

    const nombre = celdas[2].textContent.trim();
    const categoria = celdas[3].textContent.trim();
    const precio = fila.dataset.precioBase;
    const stock = celdas[5].textContent.trim();
    const activo = celdas[6].querySelector('.badge-activo') ? 1 : 0;
    const idIva = fila.dataset.ivaId;
    const imgSrc = celdas[1].querySelector('img')?.src ?? 'webroot/img/logo.PNG';

    document.getElementById('editProductoId').value = id;
    document.getElementById('editProductoImagen').src = imgSrc;
    document.getElementById('editProductoImagen').alt = nombre;
    document.getElementById('editProductoNombre').value = nombre;
    document.getElementById('editProductoPrecio').value = precio;
    document.getElementById('editProductoStock').value = stock;
    document.getElementById('editProductoEstado').value = activo;

    const selectIva = document.getElementById('editProductoIva');
    selectIva.innerHTML = tiposIva.map(t =>
        `<option value="${t.id}" ${t.id == idIva ? 'selected' : ''}>${t.porcentaje}% (${t.nombre})</option>`
    ).join('');

    document.getElementById('editProductoImagenInput').value = '';
    document.getElementById('editProductoTitulo').textContent = 'Editar Producto';
    document.getElementById('editProductoSubtitulo').textContent = 'Modifica los datos del producto';

    const selectCat = document.getElementById('editProductoCategoria');
    selectCat.innerHTML = '<option value="">Selecciona una categoría</option>' +
        categoriasAdmin.map(c =>
            `<option value="${c.nombre}" ${c.nombre === categoria ? 'selected' : ''}>${c.nombre}</option>`
        ).join('');
    selectCat.style.cssText = 'background:#fff;cursor:pointer;';

    abrirModal('modalEditarProducto');
}

function previsualizarImagen(event) {
    const file = event.target.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => { document.getElementById('editProductoImagen').src = e.target.result; };
    reader.readAsDataURL(file);
}

function guardarCambiosProducto() {
    const id = document.getElementById('editProductoId').value;
    const nombre = document.getElementById('editProductoNombre').value.trim();
    const categoria = document.getElementById('editProductoCategoria').value;
    const precio = document.getElementById('editProductoPrecio').value;
    const stock = document.getElementById('editProductoStock').value;
    const activo = document.getElementById('editProductoEstado').value;
    const imgInput = document.getElementById('editProductoImagenInput');

    if (!nombre || !categoria || !precio || stock === '') {
        alert('Por favor rellena todos los campos obligatorios.');
        return;
    }

    const fd = new FormData();
    fd.append('id', id);
    fd.append('nombre', nombre);
    fd.append('categoria', categoria);
    fd.append('precio', precio);
    fd.append('stock', stock);
    fd.append('activo', activo);
    fd.append('idIva', document.getElementById('editProductoIva').value);
    if (imgInput.files[0]) fd.append('imagen', imgInput.files[0]);

    fetch('api/productos.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.ok) {
                cerrarModal('modalEditarProducto');
                cargarProductosAdmin().then(() => actualizarContadorProductos());
            } else {
                alert('Error al guardar: ' + (data.error ?? ''));
            }
        })
        .catch(err => console.error('Error guardando producto:', err));
}

function confirmarEliminarProducto(id, nombre) {
    if (confirm(`¿Seguro que quieres eliminar "${nombre}"?`)) eliminarProducto(id);
}

function eliminarProducto(id) {
    fetch(`api/productos.php?eliminar=${id}`, { method: 'DELETE' })
        .then(r => r.json())
        .then(data => {
            if (data.ok) cargarProductosAdmin().then(() => actualizarContadorProductos());
            else alert('Error al eliminar el producto.');
        })
        .catch(err => console.error('Error eliminando producto:', err));
}

function verProducto(id) {
    const fila = document.querySelector(`tr [onclick="verProducto(${id})"]`).closest('tr');
    const celdas = fila.querySelectorAll('td');

    const nombre = celdas[2].textContent.trim();
    const categoria = celdas[3].textContent.trim();
    const precioBase = parseFloat(fila.dataset.precioBase);
    const ivaValue = parseInt(fila.dataset.iva) || 0;
    const precioPVP = precioBase * (1 + ivaValue / 100);
    const stock = celdas[5].textContent.trim();
    const estado = celdas[6].querySelector('.admin-badge')?.textContent.trim() ?? '—';
    const ivaNombre = fila.dataset.ivaNombre || 'General';
    const imgSrc = celdas[1].querySelector('img')?.src ?? 'webroot/img/logo.PNG';

    const imgEl = document.getElementById('verProductoImagen');
    imgEl.src = imgSrc; imgEl.alt = nombre; imgEl.style.cursor = 'zoom-in';
    imgEl.onclick = () => abrirImagenGrande(imgSrc, nombre);

    document.getElementById('verProductoNombre').textContent = nombre;
    document.getElementById('verProductoCategoria').textContent = categoria;
    document.getElementById('verProductoPrecio').textContent =
        (mostrarConIva ? precioPVP : precioBase).toFixed(2).replace('.', ',') + ' €';
    document.getElementById('verProductoStock').textContent = stock;
    document.getElementById('verProductoIva').textContent = `${ivaValue}% (${ivaNombre})`;
    document.getElementById('verProductoEstado').innerHTML = estado === 'Activo'
        ? '<span class="admin-badge badge-activo">Activo</span>'
        : '<span class="admin-badge badge-inactivo">Inactivo</span>';

    document.getElementById('modalVerProducto').style.display = 'flex';
}

// ── Contador del dashboard ────────────────────────────────────────────────────

function actualizarContadorProductos() {
    if (!productosData.length) return;
    const totalActivos = productosData.filter(p => p.activo === 1).length;
    const alertasStock = productosData.filter(p => p.stock <= 3 && p.stock > 0).length;

    document.querySelectorAll('.admin-stat-card').forEach(card => {
        const label = card.querySelector('.admin-stat-label');
        if (!label) return;
        if (label.textContent.includes('Total Productos Activos'))
            card.querySelector('.admin-stat-value').textContent = totalActivos;
        if (label.textContent.includes('Alertas Stock'))
            card.querySelector('.admin-stat-value').textContent = alertasStock;
    });
}

// ── Estadísticas de productos ─────────────────────────────────────────────────

async function abrirModalEstadisticasProductos() {
    const modal = document.getElementById('modalEstadisticasProductos');
    const contenido = document.getElementById('estadisticasProductosContenido');
    modal.style.display = 'flex';
    contenido.innerHTML = '<div style="text-align:center;padding:40px;"><i class="fas fa-spinner fa-spin" style="font-size:40px;color:#3b82f6;"></i><p>Cargando estadísticas...</p></div>';

    try {
        const res = await fetch('api/ventas.php?accion=estadisticas_productos');
        const data = await res.json();

        if (!data.success) {
            contenido.innerHTML = `<p style="color:#dc2626;text-align:center;">Error: ${data.error || 'Error desconocido'}</p>`;
            return;
        }

        const stats = data.estadisticas;
        const isDark = document.body.classList.contains('dark-mode');
        const textColor = isDark ? '#e5e7eb' : '#1f2937';
        const borderColor = isDark ? '#4b5563' : '#e5e7eb';

        const tarjeta = (icono, colorIcon, colBg, periodo, titulo, nombre, cantidad, unidad) => `
            <div class="stat-card-premium" style="
                background:${isDark ? 'linear-gradient(135deg,#1e293b,#0f172a)' : 'white'};
                padding:24px;border-radius:16px;border:1px solid ${borderColor};
                box-shadow:0 4px 20px rgba(0,0,0,.05);display:flex;flex-direction:column;gap:12px;">
                <div style="display:flex;align-items:center;justify-content:space-between;">
                    <div style="padding:10px;border-radius:12px;background:${colBg};">
                        <i class="fas ${icono}" style="color:${colorIcon};font-size:20px;"></i></div>
                    <span style="font-size:.75rem;text-transform:uppercase;letter-spacing:.05em;color:#64748b;font-weight:600;">${periodo}</span>
                </div>
                <div>
                    <h4 style="margin:0;color:#64748b;font-size:.9rem;font-weight:500;">${titulo}</h4>
                    <p style="font-size:1.25rem;font-weight:700;color:${textColor};margin:4px 0 0 0;line-height:1.2;">${nombre || 'Sin datos'}</p>
                </div>
                <div style="margin-top:auto;display:flex;align-items:baseline;gap:6px;">
                    <span style="font-size:1.5rem;font-weight:800;color:${colorIcon};">${cantidad || 0}</span>
                    <span style="color:#64748b;font-size:.85rem;">${unidad}</span>
                </div>
            </div>`;

        contenido.innerHTML = `
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:20px;padding:24px;animation:fadeIn .4s ease-out;">
                ${tarjeta('fa-trophy', '#d97706', 'rgba(251,191,36,.15)', 'Todo el tiempo', 'Producto Estrella', stats.mas_vendido_historia?.nombre, stats.mas_vendido_historia?.cantidad, 'unidades vendidas')}
                ${tarjeta('fa-calendar-check', '#2563eb', 'rgba(59,130,246,.15)', 'Este Mes', 'Líder Mensual', stats.mas_vendido_mes?.nombre, stats.mas_vendido_mes?.cantidad, 'u. este mes')}
                ${tarjeta('fa-bolt', '#059669', 'rgba(16,185,129,.15)', 'Esta Semana', 'Tendencia Semanal', stats.mas_vendido_semana?.nombre, stats.mas_vendido_semana?.cantidad, 'u. esta semana')}
            </div>
            <style>
                @keyframes fadeIn{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}
                .stat-card-premium{transition:transform .2s cubic-bezier(.4,0,.2,1),box-shadow .2s cubic-bezier(.4,0,.2,1)!important}
                .stat-card-premium:hover{transform:translateY(-5px);box-shadow:0 12px 30px rgba(0,0,0,.1)!important}
            </style>`;
    } catch (err) {
        console.error('Error cargando estadísticas:', err);
        contenido.innerHTML = '<p style="color:#dc2626;text-align:center;">Error al cargar las estadísticas</p>';
    }
}

// ── Carga inicial de categorías e IVA ─────────────────────────────────────────

function cargarCategoriasAdmin() {
    return fetch('api/categorias.php')
        .then(r => r.json())
        .then(data => { categoriasAdmin = data; return data; })
        .catch(err => console.error('Error cargando categorías:', err));
}

function cargarTiposIva() {
    return fetch('api/iva.php')
        .then(r => r.json())
        .then(data => { tiposIva = data; return data; })
        .catch(err => console.error('Error cargando tipos de IVA:', err));
}

function actualizarSelectsIva() {
    const select = document.getElementById('editProductoIva');
    if (!select) return;
    select.innerHTML = '<option value="">Selecciona un tipo de IVA</option>' +
        tiposIva.map(t => `<option value="${t.id}">${t.porcentaje}% (${t.nombre})</option>`).join('');
}

// ── Renderizado en modo cajero (grid) ─────────────────────────────────────────

function renderProductos(productos) {
    const grid = document.getElementById('productosGrid');
    if (!productos || !productos.length) {
        grid.innerHTML = '<p class="sin-productos">No hay productos disponibles.</p>';
        return;
    }
    grid.innerHTML = productos.map(prod => {
        const precioFmt = parseFloat(prod.precio).toFixed(2).replace('.', ',');
        const imgSrc = prod.imagen && prod.imagen !== '' ? prod.imagen : 'webroot/img/logo.PNG';
        const agotado = prod.stock <= 0;
        return `<div class="producto-card" data-id="${prod.id}"
                    data-nombre="${prod.nombre.replace(/"/g, '&quot;')}"
                    data-precio="${prod.precio}" data-stock="${prod.stock}"
                    onclick="agregarAlCarrito(this)"
                    style="${agotado ? 'opacity:.5;cursor:not-allowed;scale:1;transform:translateY(0);' : ''}">
                    <div class="producto-nombre">${prod.nombre}</div>
                    <div class="producto-imagen"><img src="${imgSrc}" alt="${prod.nombre.replace(/"/g, '&quot;')}"></div>
                    <div class="producto-info-inferior">
                        <span class="producto-precio">${precioFmt} €</span>
                        <span class="producto-stock" ${agotado ? 'style="color:red;text-decoration:underline;"' : ''}>Stock: ${prod.stock}</span>
                    </div>
                </div>`;
    }).join('');
}


// --- Historial de precios -------------------------------------

function mostrarPanelHistorialPrecios() {
    const contenedor = document.getElementById('adminContenido');
    seccionActual = 'historial-precios';
    adminTablaHeaderHTML = '';
    paginaActualHistorial = 1;

    contenedor.innerHTML = `
        <div class="logs-container">
            <div class="logs-filtros">
                <div class="logs-filtro-item" style="flex: 1; min-width: 250px; position: relative;">
                    <label>Seleccionar Producto:</label>
                    <input type="text" id="inputHistorialProducto" 
                        placeholder="Escriba el nombre del producto..."
                        oninput="filtrarProductosAutocomplete(this.value)"
                        onfocus="filtrarProductosAutocomplete(this.value)"
                        autocomplete="off">
                    <input type="hidden" id="selectHistorialProducto">
                    <div id="autocompleteResults" class="autocomplete-results" style="display: none; position: absolute; top: 100%; left: 0; right: 0; z-index: 1000; background: var(--bg-card); border: 1px solid var(--border-main); border-radius: 0 0 8px 8px; max-height: 200px; overflow-y: auto; box-shadow: var(--shadow-md);"></div>
                </div>
                <div class="logs-filtro-item" style="flex: 1; min-width: 250px;">
                    <label>Seleccionar Tarifa:</label>
                    <select id="selectHistorialTarifa" onchange="cargarHistorialPrecios()">
                        <option value="">-- Todas las tarifas --</option>
                        <option value="base">Precio Base</option>
                    </select>
                </div>
            </div>

            <div id="tablaHistorialPreciosContainer" class="admin-tabla-wrapper sin-scroll" style="display: none; margin-bottom: 20px;">
                <table class="admin-tabla">
                    <thead>
                        <tr>
                            <th>Precio</th>
                            <th>Válido Desde</th>
                            <th>Válido Hasta</th>
                            <th>Tarifa</th>
                            <th>Usuario</th>
                        </tr>
                    </thead>
                    <tbody id="tablaHistorialPreciosBody">
                    </tbody>
                </table>
            </div>

            <div id="historialPreciosMensaje" class="admin-mensaje-vacio" style="margin-top: 20px; text-align: center; padding: 40px; color: var(--text-muted); font-style: italic; background: var(--bg-card); border: 1px dashed var(--border-main); border-radius: 8px;">
                Seleccione un producto para ver su historial de precios
            </div>
        </div>
    `;

    // Observer para detectar cambios en el tema
    const observer = new MutationObserver(() => {
        // Cuando cambie el tema, volver a cargar el panel
        if (seccionActual === 'historial-precios') {
            mostrarPanelHistorialPrecios();
        }
    });
    observer.observe(document.body, { attributes: true, attributeFilter: ['class'] });

    // Cargar la lista de productos
    fetch('api/productos.php?listaProductos')
        .then(res => res.json())
        .then(productos => {
            window.catalogoProductos = productos;
        })
        .catch(err => {
            console.error('Error cargando productos:', err);
        });

    // Cargar la lista de tarifas
    fetch('api/tarifas.php')
        .then(res => res.json())
        .then(tarifas => {
            const select = document.getElementById('selectHistorialTarifa');
            if (select) {
                tarifas.forEach(tarifa => {
                    const option = document.createElement('option');
                    option.value = tarifa.id;
                    option.textContent = tarifa.nombre;
                    select.appendChild(option);
                });
            }
        })
        .catch(err => {
            console.error('Error cargando tarifas:', err);
        });
}

function filtrarProductosAutocomplete(query) {
    const resultsContainer = document.getElementById('autocompleteResults');
    if (!resultsContainer) return;

    if (!query || query.length < 1) {
        resultsContainer.innerHTML = '';
        resultsContainer.style.display = 'none';
        return;
    }

    if (!window.catalogoProductos) return;

    const filtered = window.catalogoProductos.filter(p =>
        p.nombre.toLowerCase().includes(query.toLowerCase())
    ).slice(0, 10);

    if (filtered.length === 0) {
        resultsContainer.innerHTML = '<div style="padding: 10px; color: var(--text-muted);">No se encontraron productos</div>';
        resultsContainer.style.display = 'block';
        return;
    }

    let html = '';
    const isDark = document.body.classList.contains('dark-mode');
    const hoverBg = isDark ? '#4b5563' : '#f3f4f6';
    const textColor = isDark ? '#e5e7eb' : '#1f2937';
    const borderColor = isDark ? '#374151' : '#e5e7eb';

    filtered.forEach(p => {
        html += `<div class="autocomplete-item" 
            style="padding: 10px 15px; cursor: pointer; border-bottom: 1px solid var(--border-main); color: var(--text-main); transition: background 0.2s;" 
            onmouseover="this.style.background='var(--bg-hover)'" 
            onmouseout="this.style.background='transparent'"
            onclick="seleccionarProductoAutocomplete('${p.id}', '${p.nombre.replace(/'/g, "\\'")}')">
            ${p.nombre}
        </div>`;
    });

    resultsContainer.innerHTML = html;
    resultsContainer.style.display = 'block';
}

function seleccionarProductoAutocomplete(id, nombre) {
    const input = document.getElementById('inputHistorialProducto');
    const hidden = document.getElementById('selectHistorialProducto');
    if (input) input.value = nombre;
    if (hidden) hidden.value = id;

    const resultsContainer = document.getElementById('autocompleteResults');
    if (resultsContainer) resultsContainer.style.display = 'none';

    cargarHistorialPrecios();
}

// Cerrar autocomplete al hacer clic fuera
document.addEventListener('click', (e) => {
    const resultsContainer = document.getElementById('autocompleteResults');
    const input = document.getElementById('inputHistorialProducto');
    if (resultsContainer && input && !resultsContainer.contains(e.target) && e.target !== input) {
        resultsContainer.style.display = 'none';
    }
});

function mostrarTablaPrevisualizacionPrecios(productos, porcentaje) {
    const contenedor = document.getElementById('previsualizacionCambios');

    const esSubida = porcentaje > 0;
    const claseDiferencia = esSubida ? 'diferencia-subida' : 'diferencia-bajada';

    let html = `
        <div class="previsualizacion-tabla-container">
            <div class="previsualizacion-tabla-header">
                <h3>Previsualización del ajuste de precios (${porcentaje}%)</h3>
                <div class="previsualizacion-botones">
                    <span class="previsualizacion-hint">💡 Clic en fila para excluir</span>
                    <button class="btn-excluir-todos" onclick="excluirTodosProductos('precios')">Excluir todos</button>
                    <button class="btn-incluir-todos" onclick="incluirTodosProductos('precios')">Incluir todos</button>
                </div>
            </div>
            <div class="previsualizacion-tabla-wrapper">
                <table class="previsualizacion-tabla">
                    <thead>
                        <tr>
                            <th style="width: 30px;">#</th>
                            <th>ID</th>
                            <th>Producto</th>
                            <th style="text-align: right;">Precio Actual</th>
                            <th style="text-align: right;">Precio Nuevo</th>
                            <th style="text-align: right;">Diferencia</th>
                        </tr>
                    </thead>
                    <tbody>`;

    productos.forEach((p, index) => {
        const excluido = productosExcluidos.includes(p.id);
        const claseFila = excluido ? 'fila-excluida' : (p.diferencia !== 0 ? 'fila-destacada' : '');
        const precioNuevo = excluido ? p.precio_actual : p.precio_nuevo;
        const diferencia = excluido ? 0 : p.diferencia;
        const claseDif = excluido ? '' : claseDiferencia;
        const simboloDif = esSubida && !excluido ? '+' : '';
        const indicador = excluido ? '❌' : (index + 1);

        html += `
            <tr class="${claseFila}" onclick="toggleExcluirProducto(${p.id}, 'precios')">
                <td style="text-align: center;">${indicador}</td>
                <td>${p.id}</td>
                <td>${p.nombre}</td>
                <td style="text-align: right;">${parseFloat(p.precio_actual).toFixed(2)} €</td>
                <td style="text-align: right; font-weight: bold;">${parseFloat(precioNuevo).toFixed(2)} €</td>
                <td style="text-align: right;" class="${claseDif}">${simboloDif}${diferencia.toFixed(2)} €</td>
            </tr>`;
    });

    html += `</tbody></table></div></div>`;
    contenedor.innerHTML = html;

    // Restaurar scroll si existía
    const wrapper = contenedor.querySelector('.previsualizacion-tabla-wrapper');
    if (wrapper && scrollPrevisualizacion > 0) {
        wrapper.scrollTop = scrollPrevisualizacion;
    }
}

/**
 * Carga el historial de precios del producto seleccionado
 */
function cargarHistorialPrecios(filtros = {}) {
    const select = document.getElementById('selectHistorialProducto');
    const idProducto = select.value;
    const selectTarifa = document.getElementById('selectHistorialTarifa');
    const idTarifa = selectTarifa ? selectTarifa.value : '';
    const container = document.getElementById('tablaHistorialPreciosContainer');
    const mensaje = document.getElementById('historialPreciosMensaje');
    const tbody = document.getElementById('tablaHistorialPreciosBody');

    // Si se pasa una página en filtros, actualizar la página actual
    if (filtros.pagina !== undefined) {
        paginaActualHistorial = filtros.pagina;
    } else if (filtros.resetPaginacion !== false) {
        paginaActualHistorial = 1;
    }

    if (!idProducto) {
        container.style.display = 'none';
        mensaje.style.display = 'block';
        mensaje.textContent = 'Seleccione un producto para ver su historial de precios';
        // Limpiar controles de paginación previos si los hay
        const oldPaginacion = document.getElementById('paginacionHistorial');
        if (oldPaginacion) oldPaginacion.remove();
        return;
    }

    // Mostrar mensaje de carga
    mensaje.textContent = 'Cargando historial...';
    mensaje.style.display = 'block';
    container.style.display = 'none';

    // Construir URL con filtros y paginación
    let url = `api/productos.php?historialPrecios=${idProducto}&pagina=${paginaActualHistorial}&por_pagina=${historialPorPagina}`;
    if (idTarifa) url += `&id_tarifa=${idTarifa}`;

    fetch(url)
        .then(res => res.json())
        .then(data => {
            mensaje.style.display = 'none';

            if (!data.ok || !data.historial || data.historial.length === 0) {
                mensaje.textContent = data.error || 'No hay historial de precios para este producto con los filtros seleccionados';
                mensaje.style.display = 'block';
                container.style.display = 'none';

                // Limpiar paginación si no hay datos
                const oldPaginacion = document.getElementById('paginacionHistorial');
                if (oldPaginacion) oldPaginacion.remove();
                return;
            }

            const historial = data.historial;
            totalPaginasHistorial = Math.ceil(data.total / historialPorPagina);

            let html = '';
            historial.forEach((item, index) => {
                const fechaDesde = new Date(item.valido_desde).toLocaleString('es-ES', {
                    day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit'
                });

                // Lógica simplificada para fechaHasta: sólo mostrar "Actual" si es el primer registro de la página 1
                // de lo contrario mostrar "—" o podrías intentar compararlos si estuvieran todos (pero ya no)
                let fechaHasta = '—';
                if (paginaActualHistorial === 1 && index === 0) {
                    fechaHasta = 'Actual';
                } else if (item.valido_hasta) {
                    fechaHasta = new Date(item.valido_hasta).toLocaleString('es-ES', {
                        day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit'
                    });
                }

                html += `
                    <tr>
                        <td style="font-weight: 600; text-align: right;">${item.precio.toFixed(2)} €</td>
                        <td>${fechaDesde}</td>
                        <td style="color: var(--text-muted);">${fechaHasta}</td>
                        <td style="color: var(--text-muted);">${item.tarifa || 'Precio Base'}</td>
                        <td style="color: var(--text-muted);">${item.usuario || 'Sistema'}</td>
                    </tr>
                `;
            });

            tbody.innerHTML = html;
            container.style.display = 'block';

            // Renderizar paginación
            let paginacionDiv = document.getElementById('paginacionHistorial');
            if (!paginacionDiv) {
                paginacionDiv = document.createElement('div');
                paginacionDiv.id = 'paginacionHistorial';
                container.parentNode.insertBefore(paginacionDiv, container.nextSibling);
            }
            paginacionDiv.innerHTML = getPaginacionHistorialHTML(totalPaginasHistorial);
            ajustarTodosInputsPaginacion();
        })
        .catch(err => {
            console.error('Error cargando historial:', err);
            mensaje.textContent = 'Error al cargar el historial de precios';
            mensaje.style.display = 'block';
        });
}