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
            <td class="col-precio">${precioFmt} €</td>
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