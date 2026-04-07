/**
 * admin.tarifas.js
 * Gestión de categorías, tipos de IVA, ajuste de precios y tarifas prefijadas.
 * Depende de: admin.state.js, admin.utils.js, admin.pagination.js
 */

// ═══════════════════════════════════════════════════════════════════════════════
// CATEGORÍAS
// ═══════════════════════════════════════════════════════════════════════════════

function generarFilaCategoria(cat) {
    const fecha = cat.fecha_creacion ? new Date(cat.fecha_creacion).toLocaleDateString('es-ES') : '—';
    return `<tr>
        <td>${cat.id}</td>
        <td>${cat.nombre}</td>
        <td style="text-align:center;">
            <span class="admin-badge" style="background:#e0e7ff;color:#3730a3;">${cat.num_productos}</span>
        </td>
        <td>${fecha}</td>
        <td class="col-acciones">
            <button class="btn-admin-accion btn-ver" onclick="verCategoria(${cat.id})" title="Ver">
                <i class="fas fa-eye"></i></button>
            <button class="btn-admin-accion btn-editar"
                onclick="abrirModalEditarCategoria(${cat.id},'${cat.nombre}','${cat.descripcion || ''}')" title="Editar">
                <i class="fas fa-pen"></i></button>
            <button class="btn-admin-accion btn-eliminar"
                onclick="confirmarEliminarCategoria(${cat.id},'${cat.nombre}')" title="Eliminar">
                <i class="fas fa-trash"></i></button>
        </td>
    </tr>`;
}

function renderizarCategoriasPagina() {
    const tablaBody = document.getElementById('tablaCategoriasBody');
    if (!tablaBody) return;
    const inicio = (paginaActualCategorias - 1) * categoriasPorPagina;
    const pag = categoriasData.slice(inicio, inicio + categoriasPorPagina);
    const totalPaginas = Math.ceil(categoriasData.length / categoriasPorPagina);
    tablaBody.innerHTML = pag.map(generarFilaCategoria).join('');
    const existing = document.querySelector('.admin-paginacion-wrapper');
    if (existing) existing.remove();
    const wrapper = document.querySelector('.admin-tabla-wrapper');
    if (wrapper) wrapper.insertAdjacentHTML('afterend', getPaginacionCategoriasHTML(totalPaginas));
    ajustarTodosInputsPaginacion();
}

function mostrarPanelCategorias(textoBusqueda = '') {
    const contenedor = document.getElementById('adminContenido');
    const input = document.getElementById('busquedaCategorias');
    if (input && !textoBusqueda) textoBusqueda = input.value;

    if (seccionActual !== 'categorias') { adminTablaHeaderHTML = ''; seccionActual = 'categorias'; paginaActualCategorias = 1; }

    if (!adminTablaHeaderHTML) {
        adminTablaHeaderHTML = `
            <div class="admin-tabla-header">
                <div style="display:flex;gap:10px;width:100%;align-items:center;flex-wrap:wrap;">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <label style="margin:0;font-weight:600;">Buscar:</label>
                        <input type="text" id="busquedaCategorias"
                            placeholder="Escribe el nombre de la categoría..."
                            value="${textoBusqueda}"
                            style="padding:8px 15px;border:1px solid #e5e7eb;border-radius:10px;width:250px;height:40px;"
                            oninput="buscarCategorias()">
                    </div>
                    <button class="btn-admin-accion btn-nuevo" onclick="abrirModalNuevaCategoria()">
                        <i class="fas fa-plus"></i> Nueva Categoría
                    </button>
                    <span id="totalCategoriasAviso" class="total-clientes-aviso">0 Categoría(s)</span>
                </div>
            </div>`;
    }

    fetch('api/categorias.php')
        .then(r => r.json())
        .then(data => {
            if (data.error) { contenedor.innerHTML = adminTablaHeaderHTML + '<p style="color:red;">Error: ' + data.error + '</p>'; return; }

            let filtrado = data;
            if (textoBusqueda) {
                const s = textoBusqueda.toLowerCase();
                filtrado = data.filter(c => c.nombre.toLowerCase().includes(s));
            }

            categoriasData = filtrado;
            paginaActualCategorias = 1;

            if (!filtrado.length) { contenedor.innerHTML = adminTablaHeaderHTML + '<p class="sin-productos">No hay categorías.</p>'; return; }

            const totalPaginas = Math.ceil(filtrado.length / categoriasPorPagina);
            const pag = filtrado.slice(0, categoriasPorPagina);

            let html = adminTablaHeaderHTML + `
                <div class="admin-tabla-wrapper sin-scroll">
                    <table class="admin-tabla">
                        <thead><tr>
                            <th>#</th><th>Nombre</th><th>Productos</th><th>Fecha Creación</th><th>Acciones</th>
                        </tr></thead>
                        <tbody id="tablaCategoriasBody">
                            ${pag.map(generarFilaCategoria).join('')}
                        </tbody>
                    </table>
                </div>` + getPaginacionCategoriasHTML(totalPaginas);

            contenedor.innerHTML = html;

            const contador = document.getElementById('totalCategoriasAviso');
            if (contador) {
                const hayBusqueda = textoBusqueda && textoBusqueda.trim() !== '';
                contador.textContent = hayBusqueda
                    ? `${filtrado.length.toLocaleString('es-ES')} Resultado${filtrado.length !== 1 ? 's' : ''}`
                    : `${data.length.toLocaleString('es-ES')} Categoría${data.length !== 1 ? 's' : ''}`;
            }
        })
        .catch(err => { contenedor.innerHTML = adminTablaHeaderHTML + '<p style="color:red;">Error al cargar las categorías.</p>'; });
}

function buscarCategorias() {
    clearTimeout(debounceTimerCategorias);
    debounceTimerCategorias = setTimeout(() => {
        const input = document.getElementById('busquedaCategorias');
        if (!input) return;
        paginaActualCategorias = 1;

        fetch('api/categorias.php')
            .then(r => r.json())
            .then(data => {
                if (data.error) return;
                const s = input.value.toLowerCase();
                let filtrado = data;
                if (s) filtrado = data.filter(c => c.nombre.toLowerCase().includes(s));
                categoriasData = filtrado;

                const totalPaginas = Math.ceil(filtrado.length / categoriasPorPagina);
                const pag = filtrado.slice(0, categoriasPorPagina);
                const tablaBody = document.getElementById('tablaCategoriasBody');
                if (!tablaBody) return;

                tablaBody.innerHTML = filtrado.length
                    ? pag.map(generarFilaCategoria).join('')
                    : '<tr><td colspan="5" style="text-align:center;padding:20px;color:#6b7280;">No hay categorías.</td></tr>';

                const existing = document.querySelector('.admin-paginacion-wrapper');
                if (existing) existing.remove();
                const wrapper = document.querySelector('.admin-tabla-wrapper');
                if (wrapper) wrapper.insertAdjacentHTML('afterend', getPaginacionCategoriasHTML(totalPaginas));
            });
    }, 300);
}

function verCategoria(id) {
    const listCont = document.getElementById('verCategoriaListaProductos');
    const badge = document.getElementById('verCategoriaCantProdBadge');
    listCont.innerHTML = '<div class="cat-prod-empty">Cargando...</div>';
    badge.textContent = '0';
    productosCategoriaActual = [];
    indexProductoActual = 0;

    fetch('api/categorias.php?id=' + id)
        .then(r => r.json())
        .then(data => {
            if (data.error) { alert(data.error); return; }
            document.getElementById('verCategoriaId').textContent = data.id;
            document.getElementById('verCategoriaNombre').textContent = data.nombre;
            document.getElementById('verCategoriaDescripcion').textContent = data.descripcion || 'Sin descripción';
            badge.textContent = data.num_productos;
            abrirModal('modalVerCategoria');
            return fetch('api/productos.php?idCategoria=' + id);
        })
        .then(r => r ? r.json() : null)
        .then(productos => { if (!productos) return; productosCategoriaActual = productos; renderizarProductoCarrusel(); })
        .catch(err => { listCont.innerHTML = '<div class="cat-prod-empty" style="color:#dc2626;">Error al cargar productos</div>'; });
}

function renderizarProductoCarrusel() {
    const listCont = document.getElementById('verCategoriaListaProductos');
    const inputPag = document.getElementById('catCarouselInput');
    const totalSpan = document.getElementById('catCarouselTotal');
    const btnFirst = document.getElementById('firstCatProd');
    const btnPrev = document.getElementById('prevCatProd');
    const btnNext = document.getElementById('nextCatProd');
    const btnLast = document.getElementById('lastCatProd');

    if (!productosCategoriaActual.length) {
        listCont.innerHTML = '<div class="cat-prod-empty">No hay productos en esta categoría</div>';
        [btnFirst, btnPrev, btnNext, btnLast].forEach(b => { if (b) b.disabled = true; });
        if (inputPag) inputPag.value = 0;
        if (totalSpan) totalSpan.textContent = '0';
        return;
    }

    const p = productosCategoriaActual[indexProductoActual];
    const img = p.imagen || 'webroot/img/productos/default.png';
    const precio = parseFloat(p.precio).toLocaleString('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    listCont.innerHTML = `
        <div class="cat-prod-card animate-fade-in">
            <img src="${img}" alt="${p.nombre}" class="cat-prod-img" onerror="this.src='webroot/img/productos/default.png'">
            <div class="cat-prod-name">${p.nombre}</div>
            <div class="cat-prod-price">${precio} €</div>
            <div class="cat-prod-stock ${p.stock <= 5 ? 'low' : ''}">Stock: ${p.stock} unidades</div>
        </div>`;

    if (inputPag) { inputPag.value = indexProductoActual + 1; inputPag.max = productosCategoriaActual.length; }
    if (totalSpan) totalSpan.textContent = productosCategoriaActual.length;

    const isFirst = indexProductoActual === 0;
    const isLast = indexProductoActual === productosCategoriaActual.length - 1;
    if (btnFirst) btnFirst.disabled = isFirst;
    if (btnPrev) btnPrev.disabled = isFirst;
    if (btnNext) btnNext.disabled = isLast;
    if (btnLast) btnLast.disabled = isLast;
}

function cambiarProductoCarrusel(op) {
    if (op === 'first') indexProductoActual = 0;
    else if (op === 'last') indexProductoActual = productosCategoriaActual.length - 1;
    else indexProductoActual += op;
    indexProductoActual = Math.max(0, Math.min(indexProductoActual, productosCategoriaActual.length - 1));
    renderizarProductoCarrusel();
}

function saltarAProductoCarrusel(valor) {
    let num = parseInt(valor);
    if (isNaN(num)) return;
    num = Math.max(1, Math.min(num, productosCategoriaActual.length));
    indexProductoActual = num - 1;
    renderizarProductoCarrusel();
}

function abrirModalNuevaCategoria() {
    let modal = document.getElementById('modalNuevaCategoria');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'modalNuevaCategoria';
        modal.className = 'modal-overlay';
        modal.style.display = 'none';
        modal.innerHTML = '<div class="modal-content" style="max-width:450px;max-height:80vh;overflow-y:auto;"></div>';
        document.body.appendChild(modal);
    }
    modal.querySelector('.modal-content').innerHTML = `
        <div class="modal-nueva-cat-container">
            <div class="modal-nueva-cat-header"><h3>Nueva Categoría</h3></div>
            <div class="modal-nueva-cat-body">
                <div class="modal-nueva-cat-field">
                    <label>Nombre:</label>
                    <input type="text" id="nuevaCategoriaNombre" placeholder="Nombre de la categoría" class="modal-nueva-cat-input">
                </div>
                <div class="modal-nueva-cat-field">
                    <label>Descripción:</label>
                    <textarea id="nuevaCategoriaDescripcion" placeholder="Descripción opcional" class="modal-nueva-cat-input" rows="3"></textarea>
                </div>
                <div class="modal-nueva-cat-actions">
                    <button onclick="cerrarModal('modalNuevaCategoria')" class="modal-nueva-cat-btn-cancelar">Cancelar</button>
                    <button onclick="guardarNuevaCategoria()" class="modal-nueva-cat-btn-guardar">Guardar</button>
                </div>
            </div>
        </div>`;
    modal.style.display = 'flex';
}

function guardarNuevaCategoria() {
    const nombre = document.getElementById('nuevaCategoriaNombre').value.trim();
    const descripcion = document.getElementById('nuevaCategoriaDescripcion').value.trim();
    if (!nombre) { alert('El nombre de la categoría es obligatorio'); return; }

    const fd = new FormData();
    fd.append('nombre', nombre);
    fd.append('descripcion', descripcion);

    fetch('api/categorias.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.error) { alert(data.error); return; }
            cerrarModal('modalNuevaCategoria');
            categoriasAdmin = [];
            cargarCategoriasAdmin().then(() => { adminTablaHeaderHTML = ''; mostrarPanelCategorias(); });
        })
        .catch(() => alert('Error al guardar la categoría'));
}

function abrirModalEditarCategoria(id, nombre, descripcion = '') {
    if (!document.getElementById('modalEditarCategoria')) {
        const div = document.createElement('div');
        div.id = 'modalEditarCategoria';
        div.className = 'modal-overlay';
        div.style.display = 'none';
        div.innerHTML = `
            <div class="modal-content" style="max-width:500px;overflow:hidden;">
                <div style="background:linear-gradient(135deg,#667eea,#764ba2);color:white;padding:20px;display:flex;justify-content:space-between;align-items:center;">
                    <h3 style="margin:0;">Editar Categoría</h3>
                    <button onclick="cerrarModal('modalEditarCategoria')" style="background:none;border:none;color:white;font-size:24px;cursor:pointer;">&times;</button>
                </div>
                <div style="padding:25px;">
                    <input type="hidden" id="editarCategoriaId">
                    <div style="margin-bottom:20px;">
                        <label style="display:block;margin-bottom:8px;font-weight:600;">Nombre:</label>
                        <input type="text" id="editarCategoriaNombre" style="width:100%;padding:12px;border:1px solid var(--border-main);border-radius:8px;box-sizing:border-box;background:var(--bg-input);color:var(--text-main);" required>
                    </div>
                    <div style="margin-bottom:20px;">
                        <label style="display:block;margin-bottom:8px;font-weight:600;">Descripción:</label>
                        <textarea id="editarCategoriaDescripcion" rows="4" style="width:100%;padding:12px;border:1px solid var(--border-main);border-radius:8px;box-sizing:border-box;resize:vertical;background:var(--bg-input);color:var(--text-main);"></textarea>
                    </div>
                </div>
                <div style="padding:15px 25px;background:var(--bg-secondary);display:flex;justify-content:flex-end;gap:10px;border-top:1px solid var(--border-main);">
                    <button onclick="cerrarModal('modalEditarCategoria')" class="btn-modal-cancelar">Cancelar</button>
                    <button onclick="guardarEditarCategoria()" style="padding:10px 20px;background:#4f46e5;color:white;border:none;border-radius:8px;cursor:pointer;font-weight:500;">Guardar</button>
                </div>
            </div>`;
        document.body.appendChild(div);
    }
    document.getElementById('editarCategoriaId').value = id;
    document.getElementById('editarCategoriaNombre').value = nombre;
    document.getElementById('editarCategoriaDescripcion').value = descripcion || '';
    document.getElementById('modalEditarCategoria').style.display = 'flex';
}

function guardarEditarCategoria() {
    const id = document.getElementById('editarCategoriaId').value;
    const nombre = document.getElementById('editarCategoriaNombre').value.trim();
    const descripcion = document.getElementById('editarCategoriaDescripcion').value.trim();
    if (!nombre) { alert('El nombre de la categoría es obligatorio'); return; }

    fetch('api/categorias.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'editar=' + id + '&nombre=' + encodeURIComponent(nombre) + '&descripcion=' + encodeURIComponent(descripcion)
    })
        .then(r => r.json())
        .then(data => {
            if (data.error) { alert(data.error); return; }
            cerrarModal('modalEditarCategoria');
            categoriasAdmin = [];
            cargarCategoriasAdmin().then(() => { adminTablaHeaderHTML = ''; mostrarPanelCategorias(); });
        })
        .catch(() => alert('Error al guardar los cambios'));
}

function confirmarEliminarCategoria(id, nombre) {
    if (confirm(`¿Seguro que quieres eliminar la categoría "${nombre}"?`)) eliminarCategoria(id);
}

function eliminarCategoria(id) {
    fetch('api/categorias.php?eliminar=' + id, { method: 'DELETE' })
        .then(r => r.json())
        .then(data => {
            if (data.error) {
                if (data.num_productos && data.categoria) {
                    Swal.fire({
                        title: 'No se puede eliminar',
                        html: `La categoría <b>"${data.categoria}"</b> tiene <b>${data.num_productos}</b> productos asociados.`,
                        icon: 'warning', confirmButtonText: 'Entendido'
                    });
                } else { Swal.fire('Error', data.error, 'error'); }
                return;
            }
            categoriasAdmin = [];
            cargarCategoriasAdmin().then(() => { adminTablaHeaderHTML = ''; mostrarPanelCategorias(); });
        })
        .catch(() => Swal.fire('Error', 'Error al eliminar la categoría', 'error'));
}

// ═══════════════════════════════════════════════════════════════════════════════
// IVA
// ═══════════════════════════════════════════════════════════════════════════════

function abrirModalNuevoIva() {
    document.getElementById('editIvaId').value = '';
    document.getElementById('editIvaNombre').value = '';
    document.getElementById('editIvaPorcentaje').value = '';
    document.getElementById('editIvaTitulo').textContent = 'Nuevo Tipo de IVA';
    document.getElementById('editIvaSubtitulo').textContent = 'Introduce los datos del nuevo tipo de IVA';
    document.getElementById('modalEditarIva').style.display = 'flex';
}

function editarIva(id, nombre, porcentaje) {
    document.getElementById('editIvaId').value = id;
    document.getElementById('editIvaNombre').value = nombre;
    document.getElementById('editIvaPorcentaje').value = porcentaje;
    document.getElementById('editIvaTitulo').textContent = 'Editar Tipo de IVA';
    document.getElementById('editIvaSubtitulo').textContent = 'Modifica los datos del tipo de IVA';
    document.getElementById('modalEditarIva').style.display = 'flex';
}

function guardarIva() {
    const id = document.getElementById('editIvaId').value;
    const nombre = document.getElementById('editIvaNombre').value.trim();
    const porcentaje = parseFloat(document.getElementById('editIvaPorcentaje').value);
    if (!nombre) { alert('El nombre es obligatorio'); return; }
    if (isNaN(porcentaje) || porcentaje < 0 || porcentaje > 100) { alert('El porcentaje debe estar entre 0 y 100'); return; }

    const fd = new FormData();
    if (id) fd.append('id', id);
    fd.append('nombre', nombre);
    fd.append('porcentaje', porcentaje);

    fetch('api/iva.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.ok) {
                document.getElementById('modalEditarIva').style.display = 'none';
                cargarTiposIva().then(() => { if (seccionActual === 'tarifa-iva') mostrarPanelCambiarIVA(); });
                actualizarSelectsIva();
            } else { alert(data.error || 'Error al guardar el tipo de IVA'); }
        });
}

function eliminarIva(id) {
    if (!confirm('¿Estás seguro de que quieres eliminar este tipo de IVA?')) return;
    fetch('api/iva.php?eliminar=' + id, { method: 'DELETE' })
        .then(r => r.json())
        .then(data => {
            if (data.ok) {
                cargarTiposIva().then(() => { if (seccionActual === 'tarifa-iva') mostrarPanelCambiarIVA(); });
                actualizarSelectsIva();
            } else { alert(data.error || 'No se pudo eliminar el tipo de IVA'); }
        });
}

function mostrarPanelCambiarIVA() {
    productosExcluidos = [];
    const contenedor = document.getElementById('adminContenido');
    seccionActual = 'tarifa-iva';
    adminTablaHeaderHTML = '';

    let opcionesIva = '<option value="">Selecciona un tipo de IVA</option>' +
        tiposIva.map(t => `<option value="${t.id}">${t.porcentaje}% (${t.nombre})</option>`).join('');
    let filasTablaIva = tiposIva.map(t => `
        <tr>
            <td style="text-align:center;width:40px;">${t.id}</td>
            <td>${t.nombre}</td>
            <td style="text-align:center;font-weight:600;width:80px;">${t.porcentaje}%</td>
            <td style="text-align:center;width:100px;">
                <button class="btn-admin-accion" onclick="editarIva(${t.id},'${t.nombre}',${t.porcentaje})"><i class="fas fa-pen"></i></button>
                <button class="btn-admin-accion btn-eliminar" onclick="eliminarIva(${t.id})"><i class="fas fa-trash"></i></button>
            </td>
        </tr>`).join('');

    contenedor.innerHTML = `
        <div class="iva-panel-container">
            <div class="iva-panel-header">
                <h2><i class="fas fa-percentage"></i> Cambiar IVA General</h2>
                <p class="iva-panel-subtitle">Cambia el IVA de todos los productos de forma masiva</p>
            </div>
            
            <div class="iva-panel-content">
                <div class="iva-panel-left">
                    <div class="iva-panel-card">
                        <h3><i class="fas fa-cog"></i> Configuración</h3>
                        <div class="iva-form-group">
                            <label for="nuevoIVA">Nuevo tipo de IVA:</label>
                            <select id="nuevoIVA" class="iva-select" onchange="actualizarPrevisualizacionIVAAuto()">
                                ${opcionesIva}
                            </select>
                        </div>
                        <div class="iva-actions">
                            <button onclick="aplicarCambioIVA()" class="iva-btn-aplicar">
                                <i class="fas fa-check"></i> Aplicar Cambio
                            </button>
                            <button onclick="abrirModalProgramarIVA()" class="iva-btn-secondary">
                                <i class="fas fa-clock"></i> Programar
                            </button>
                            <button onclick="abrirModalVerCambiosProgramados()" class="iva-btn-secondary">
                                <i class="fas fa-list"></i> Ver Programados
                            </button>
                        </div>
                    </div>

                    <div class="iva-panel-card">
                        <div class="iva-tipos-header">
                            <h3><i class="fas fa-tags"></i> Tipos de IVA</h3>
                            <button onclick="abrirModalNuevoIva()" class="btn-admin-accion btn-nuevo">
                                <i class="fas fa-plus"></i> Añadir
                            </button>
                        </div>
                        <div class="iva-tipos-table-wrapper">
                            <table class="iva-tipos-tabla">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nombre</th>
                                        <th>%</th>
                                        <th>Acción</th>
                                    </tr>
                                </thead>
                                <tbody>${filasTablaIva}</tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="iva-panel-right">
                    <div id="previsualizacionCambios"></div>
                </div>
            </div>
        </div>`;
}

function actualizarPrevisualizacionIVAAuto() {
    const input = document.getElementById('nuevoIVA');
    const contenedor = document.getElementById('previsualizacionCambios');
    if (!input.value) { contenedor.innerHTML = ''; return; }
    clearTimeout(debounceTimerIVA);
    debounceTimerIVA = setTimeout(() => previsualizarCambioIVA(), 300);
}

function previsualizarCambioIVA() {
    const nuevoIdIva = document.getElementById('nuevoIVA').value;
    if (!nuevoIdIva) { alert('Por favor, selecciona un tipo de IVA'); return; }
    fetch('api/productos.php?previsualizarIVA=' + nuevoIdIva)
        .then(r => r.json())
        .then(data => {
            if (data.error) { alert(data.error); return; }
            productosPrevisualizacionIVA = data.productos;
            const pct = data.productos.length > 0 ? data.productos[0].iva_nuevo : 0;
            mostrarTablaPrevisualizacionIVA(data.productos, pct);
        });
}

function mostrarTablaPrevisualizacionIVA(productos, nuevoIVA) {
    const contenedor = document.getElementById('previsualizacionCambios');
    let html = `
        <div class="previsualizacion-tabla-container">
            <div class="previsualizacion-tabla-header">
                <h3>Previsualización del cambio de IVA (${nuevoIVA}%)</h3>
                <div class="previsualizacion-botones">
                    <span class="previsualizacion-hint">💡 Clic en fila para excluir</span>
                    <button class="btn-excluir-todos" onclick="excluirTodosProductos('iva')">Excluir todos</button>
                    <button class="btn-incluir-todos" onclick="incluirTodosProductos('iva')">Incluir todos</button>
                </div>
            </div>
            <div class="previsualizacion-tabla-wrapper">
                <table class="previsualizacion-tabla">
                    <thead><tr>
                        <th style="width:30px;">#</th><th>ID</th><th>Producto</th>
                        <th style="text-align:right;">Precio</th>
                        <th style="text-align:center;">IVA Actual</th>
                        <th style="text-align:center;">IVA Nuevo</th>
                        <th style="text-align:right;">Precio c/IVA</th>
                    </tr></thead>
                    <tbody>`;

    productos.forEach((p, i) => {
        const excluido = productosExcluidos.includes(p.id);
        const precioConIVA = parseFloat(p.precio) * (1 + (excluido ? p.iva_actual : nuevoIVA) / 100);
        const clase = excluido ? 'fila-excluida' : (p.iva_actual !== nuevoIVA ? 'fila-destacada' : '');
        html += `
            <tr class="${clase}" onclick="toggleExcluirProducto(${p.id},'iva')">
                <td style="text-align:center;">${excluido ? '❌' : i + 1}</td>
                <td>${p.id}</td>
                <td>${p.nombre}</td>
                <td style="text-align:right;">${parseFloat(p.precio).toFixed(2)} €</td>
                <td style="text-align:center;">${p.iva_actual}%</td>
                <td style="text-align:center;" class="precio-iva-nuevo">${excluido ? p.iva_actual + '%' : nuevoIVA + '%'}</td>
                <td style="text-align:right;" class="precio-destacado">${precioConIVA.toFixed(2)} €</td>
            </tr>`;
    });

    html += '</tbody></table></div></div>';
    contenedor.innerHTML = html;
    const wrapper = contenedor.querySelector('.previsualizacion-tabla-wrapper');
    if (wrapper && scrollPrevisualizacion > 0) wrapper.scrollTop = scrollPrevisualizacion;
}

function aplicarCambioIVA() {
    const nuevoIdIva = document.getElementById('nuevoIVA').value;
    if (!nuevoIdIva) { alert('Por favor, selecciona un tipo de IVA'); return; }
    const select = document.getElementById('nuevoIVA');
    const nombre = select.options[select.selectedIndex].textContent;
    const msg = productosExcluidos.length > 0
        ? `¿Cambiar el IVA a ${nombre}? (${productosExcluidos.length} productos excluidos)`
        : `¿Cambiar el IVA a ${nombre} para todos los productos?`;
    if (!confirm(msg)) return;

    let url = 'api/productos.php?cambiarIVA=' + nuevoIdIva;
    if (productosExcluidos.length > 0) url += '&excluidos=' + productosExcluidos.join(',');

    fetch(url, { method: 'POST' })
        .then(r => r.json())
        .then(data => {
            if (data.error) { alert(data.error); return; }
            alert('IVA actualizado. Productos afectados: ' + data.actualizados);
            productosExcluidos = [];
            cargarTiposIva().then(() => { if (seccionActual === 'tarifa-iva') mostrarPanelCambiarIVA(); });
            actualizarSelectsIva();
        });
}

// ═══════════════════════════════════════════════════════════════════════════════
// AJUSTE DE PRECIOS
// ═══════════════════════════════════════════════════════════════════════════════

function mostrarPanelAjustePrecios() {
    const contenedor = document.getElementById('adminContenido');
    seccionActual = 'tarifa-ajuste';
    adminTablaHeaderHTML = '';
    contenedor.innerHTML = `
        <div class="admin-tabla-header">
            <h2 style="margin:0;font-size:24px;font-weight:600;">Ajuste de Precios</h2>
        </div>
        <div style="display:flex;gap:20px;align-items:flex-start;">
            <div class="tarifa-panel-inputs">
                <p class="tarifa-panel-desc">Aplica un porcentaje de subida o bajada a todos los productos.</p>
                <div class="tarifa-input-group">
                    <label>Porcentaje de ajuste (%):</label>
                    <input type="number" id="porcentajeAjuste" step="0.01" placeholder="Ej: 10 o -10"
                        class="tarifa-input" oninput="actualizarPrevisualizacionPreciosAuto()">
                    <small class="tarifa-hint">Positivo = subir | Negativo = bajar</small>
                </div>
                <button onclick="aplicarAjustePrecios()" class="tarifa-btn-aplicar tarifa-btn-precios">
                    <i class="fas fa-save"></i> Aplicar Ajuste de Precios
                </button>
                <button onclick="abrirModalProgramarAjustePrecios()" class="tarifa-btn-programar" style="margin-top:10px;">
                    <i class="fas fa-clock"></i> Programar Ajuste
                </button>
                <button onclick="abrirModalVerAjustesProgramados()" class="tarifa-btn-programar" style="margin-top:10px;">
                    <i class="fas fa-list"></i> Ver Ajustes Programados
                </button>
            </div>
            <div id="previsualizacionCambios" style="flex:1;"></div>
        </div>`;
}

function actualizarPrevisualizacionPreciosAuto() {
    const input = document.getElementById('porcentajeAjuste');
    if (!input.value) { document.getElementById('previsualizacionCambios').innerHTML = ''; return; }
    clearTimeout(debounceTimerPrecios);
    debounceTimerPrecios = setTimeout(() => previsualizarAjustePrecios(), 500);
}

function previsualizarAjustePrecios() {
    const porcentaje = parseFloat(document.getElementById('porcentajeAjuste').value);
    if (isNaN(porcentaje)) { alert('Por favor, introduce un porcentaje válido'); return; }
    fetch('api/productos.php?previsualizarAjuste=' + porcentaje)
        .then(r => r.json())
        .then(data => {
            if (data.error) { alert(data.error); return; }
            productosPrevisualizacionPrecios = data.productos;
            mostrarTablaPrevisualizacionPrecios(data.productos, porcentaje);
        });
}

function mostrarTablaPrevisualizacionPrecios(productos, porcentaje) {
    const contenedor = document.getElementById('previsualizacionCambios');
    const esSubida = porcentaje > 0;
    const claseDif = esSubida ? 'diferencia-subida' : 'diferencia-bajada';

    let html = `
        <div class="previsualizacion-tabla-container">
            <div class="previsualizacion-tabla-header">
                <h3>Previsualización del ajuste (${porcentaje}%)</h3>
                <div class="previsualizacion-botones">
                    <span class="previsualizacion-hint">💡 Clic en fila para excluir</span>
                    <button class="btn-excluir-todos" onclick="excluirTodosProductos('precios')">Excluir todos</button>
                    <button class="btn-incluir-todos" onclick="incluirTodosProductos('precios')">Incluir todos</button>
                </div>
            </div>
            <div class="previsualizacion-tabla-wrapper">
                <table class="previsualizacion-tabla">
                    <thead><tr>
                        <th style="width:30px;">#</th><th>ID</th><th>Producto</th>
                        <th style="text-align:right;">Precio Actual</th>
                        <th style="text-align:right;">Precio Nuevo</th>
                        <th style="text-align:right;">Diferencia</th>
                    </tr></thead>
                    <tbody>`;

    productos.forEach((p, i) => {
        const excluido = productosExcluidos.includes(p.id);
        const precioNuevo = excluido ? p.precio_actual : p.precio_nuevo;
        const diferencia = excluido ? 0 : p.diferencia;
        const clase = excluido ? 'fila-excluida' : (p.diferencia !== 0 ? 'fila-destacada' : '');
        const signo = esSubida && !excluido ? '+' : '';
        html += `
            <tr class="${clase}" onclick="toggleExcluirProducto(${p.id},'precios')">
                <td style="text-align:center;">${excluido ? '❌' : i + 1}</td>
                <td>${p.id}</td>
                <td>${p.nombre}</td>
                <td style="text-align:right;">${parseFloat(p.precio_actual).toFixed(2)} €</td>
                <td style="text-align:right;font-weight:bold;">${parseFloat(precioNuevo).toFixed(2)} €</td>
                <td style="text-align:right;" class="${excluido ? '' : claseDif}">${signo}${diferencia.toFixed(2)} €</td>
            </tr>`;
    });

    html += '</tbody></table></div></div>';
    contenedor.innerHTML = html;
    const wrapper = contenedor.querySelector('.previsualizacion-tabla-wrapper');
    if (wrapper && scrollPrevisualizacion > 0) wrapper.scrollTop = scrollPrevisualizacion;
}

function toggleExcluirProducto(idProducto, tipo) {
    const wrapper = document.querySelector('.previsualizacion-tabla-wrapper');
    if (wrapper) scrollPrevisualizacion = wrapper.scrollTop;
    const idx = productosExcluidos.indexOf(idProducto);
    if (idx > -1) productosExcluidos.splice(idx, 1);
    else productosExcluidos.push(idProducto);
    if (tipo === 'iva') previsualizarCambioIVA();
    else previsualizarAjustePrecios();
}

function excluirTodosProductos(tipo) {
    const wrapper = document.querySelector('.previsualizacion-tabla-wrapper');
    if (wrapper) scrollPrevisualizacion = wrapper.scrollTop;
    const productos = tipo === 'iva' ? productosPrevisualizacionIVA : productosPrevisualizacionPrecios;
    if (!productos || !productos.length) return;
    productos.forEach(p => { if (!productosExcluidos.includes(p.id)) productosExcluidos.push(p.id); });
    if (tipo === 'iva') mostrarTablaPrevisualizacionIVA(productos, parseFloat(document.getElementById('nuevoIVA').value));
    else mostrarTablaPrevisualizacionPrecios(productos, parseFloat(document.getElementById('porcentajeAjuste').value));
}

function incluirTodosProductos(tipo) {
    const wrapper = document.querySelector('.previsualizacion-tabla-wrapper');
    if (wrapper) scrollPrevisualizacion = wrapper.scrollTop;
    productosExcluidos = [];
    if (tipo === 'iva' && productosPrevisualizacionIVA.length)
        mostrarTablaPrevisualizacionIVA(productosPrevisualizacionIVA, parseFloat(document.getElementById('nuevoIVA').value));
    else if (productosPrevisualizacionPrecios.length)
        mostrarTablaPrevisualizacionPrecios(productosPrevisualizacionPrecios, parseFloat(document.getElementById('porcentajeAjuste').value));
}

function aplicarAjustePrecios() {
    const porcentaje = parseFloat(document.getElementById('porcentajeAjuste').value);
    if (isNaN(porcentaje)) { alert('Por favor, introduce un porcentaje válido'); return; }
    const msg = productosExcluidos.length > 0
        ? `¿${porcentaje > 0 ? 'Subir' : 'Bajar'} los precios un ${Math.abs(porcentaje)}%? (${productosExcluidos.length} productos excluidos)`
        : `¿${porcentaje > 0 ? 'Subir' : 'Bajar'} los precios un ${Math.abs(porcentaje)}%?`;
    if (!confirm(msg)) return;

    let url = 'api/productos.php?ajustePrecios=' + porcentaje;
    if (productosExcluidos.length > 0) url += '&excluidos=' + productosExcluidos.join(',');

    fetch(url, { method: 'POST' })
        .then(r => r.json())
        .then(data => {
            if (data.error) { alert(data.error); return; }
            alert('Precios actualizados. Productos afectados: ' + data.actualizados);
            productosExcluidos = [];
        });
}

// ═══════════════════════════════════════════════════════════════════════════════
// TARIFAS PREFIJADAS (tabla de precios por producto)
// ═══════════════════════════════════════════════════════════════════════════════

function filtrarTablaTarifas() {
    const termino = tarifaBusquedaProducto.toLowerCase();
    paginaActualTarifas = 1;
    todosLosProductosTarifas = termino
        ? productosOriginalesTarifas.filter(p => p.nombre.toLowerCase().includes(termino))
        : [...productosOriginalesTarifas];
    actualizarTablaTarifas();
}

function actualizarTablaTarifas() {
    const isDark = document.body.classList.contains('dark-mode');
    const textColor = isDark ? '#e5e7eb' : '#374151';
    const tableRowBorder = isDark ? '#374151' : '#e5d7eb';
    const tarifas = window.tarifasData || [];

    const inicio = (paginaActualTarifas - 1) * productosPorPaginaTarifas;
    const productosPagina = todosLosProductosTarifas.slice(inicio, inicio + productosPorPaginaTarifas);
    const totalPaginas = Math.ceil(todosLosProductosTarifas.length / productosPorPaginaTarifas);

    let filasHtml = '';
    productosPagina.forEach(prod => {
        const ivaProd = parseFloat(prod.iva) || 21;
        let precioBase = parseFloat(prod.precio);
        if (tarifasMostrarConIva) precioBase *= (1 + ivaProd / 100);

        let fila = `
            <tr style="border-bottom:1px solid ${tableRowBorder};">
                <td style="padding:8px 6px;font-weight:500;color:${textColor};">${prod.nombre}</td>
                <td style="padding:8px 6px;font-weight:600;text-align:right;">${precioBase.toFixed(2)} €</td>`;

        tarifas.forEach(tarifa => {
            const dataTarifa = prod.preciosTarifas && prod.preciosTarifas[tarifa.id];
            let precioFinal = 0;
            let esManual = false;

            if (dataTarifa) {
                precioFinal = parseFloat(dataTarifa.precio);
                esManual = dataTarifa.es_manual == 1;
                if (tarifasMostrarConIva) precioFinal *= (1 + ivaProd / 100);
            } else {
                precioFinal = precioBase * (1 - (parseFloat(tarifa.descuento_porcentaje) || 0) / 100);
            }

            const style = esManual
                ? 'border:1px solid #10b981;background:#ecfdf5;color:#065f46;'
                : (isDark ? 'border:1px solid #374151;background:#111827;color:#10b981;' : 'border:1px solid #d1d5db;background:white;color:#10b981;');
            const disabledAttr = (tarifasMostrarConIva && !modoProgramacionTarifas) ? 'disabled' : '';
            const disabledStyle = (tarifasMostrarConIva && !modoProgramacionTarifas) ? 'opacity:.5;cursor:not-allowed;' : '';

            const key = `${prod.id}-${tarifa.id}`;
            let valueToShow = precioFinal;
            let customClass = '';
            if (loteCambiosTarifas[key] !== undefined) {
                valueToShow = parseFloat(typeof loteCambiosTarifas[key] === 'object' ? loteCambiosTarifas[key].nuevo : loteCambiosTarifas[key]);
                if (tarifasMostrarConIva) valueToShow *= (1 + ivaProd / 100);
                customClass = 'input-precio-programado';
            }

            fila += `
                <td style="padding:8px 6px;">
                    <div style="display:flex;align-items:center;gap:4px;">
                        <input type="number" step="0.01" value="${valueToShow.toFixed(2)}"
                            data-precio-anterior="${precioFinal.toFixed(4)}"
                            onchange="actualizarPrecioTarifaIndividual(${prod.id},${tarifa.id},this,${ivaProd})"
                            ${disabledAttr} class="${customClass}"
                            style="width:70px;padding:4px 6px;border-radius:4px;font-weight:600;text-align:right;${style}${disabledStyle}">
                        <span style="font-size:14px;font-weight:600;color:#10b981;">€</span>
                        ${esManual ? '<i class="fas fa-hand-paper" title="Precio manual" style="color:#10b981;font-size:12px;"></i>' : ''}
                    </div>
                </td>`;
        });

        fila += '</tr>';
        filasHtml += fila;
    });

    const tbody = document.getElementById('tablaPreciosProductos');
    if (tbody) tbody.innerHTML = filasHtml;

    const pagCont = document.getElementById('paginacionTarifas');
    if (pagCont) pagCont.innerHTML = getPaginacionTarifasHTML(totalPaginas);
    ajustarTodosInputsPaginacion();
}

function actualizarPrecioTarifaIndividual(idProducto, idTarifa, input, iva) {
    let nuevoPrecio = parseFloat(input.value) || 0;
    if (tarifasMostrarConIva) nuevoPrecio /= (1 + iva / 100);

    if (modoProgramacionTarifas) {
        const key = `${idProducto}-${idTarifa}`;
        loteCambiosTarifas[key] = { nuevo: nuevoPrecio.toFixed(4), anterior: parseFloat(input.getAttribute('data-precio-anterior') || 0) };
        input.classList.add('input-precio-programado');
        const btn = document.querySelector('button[onclick="abrirModalProgramarCambiosTarifas()"]');
        if (btn) btn.innerHTML = `<i class="fas fa-check"></i> Finalizar y Programar (${Object.keys(loteCambiosTarifas).length})`;
        return;
    }

    const fd = new FormData();
    fd.append('actualizarPrecioIndividual', '1');
    fd.append('idTarifa', idTarifa);
    fd.append('idProducto', idProducto);
    fd.append('precio', nuevoPrecio.toFixed(4));
    fd.append('esManual', '1');

    fetch('api/tarifas.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.ok) {
                input.style.border = '1px solid #10b981';
                input.style.background = '#ecfdf5';
                input.style.color = '#065f46';
                const parent = input.parentElement;
                if (!parent.querySelector('.fa-hand-paper')) {
                    const icon = document.createElement('i');
                    icon.className = 'fas fa-hand-paper';
                    icon.title = 'Precio manual';
                    icon.style.cssText = 'color:#10b981;font-size:12px;';
                    parent.appendChild(icon);
                }
            } else {
                alert('Error al actualizar el precio: ' + (data.error || ''));
                mostrarPanelTarifasPrefijadas();
            }
        });
}

function toggleTarifasIva() { tarifasMostrarConIva = !tarifasMostrarConIva; mostrarPanelTarifasPrefijadas(); }

function alternarModoProgramacionTarifas() {
    if (modoProgramacionTarifas) {
        if (Object.keys(loteCambiosTarifas).length > 0 && !confirm('Tienes cambios pendientes. ¿Deseas salir?')) return;
        modoProgramacionTarifas = false;
        loteCambiosTarifas = {};
    } else {
        modoProgramacionTarifas = true;
        loteCambiosTarifas = {};
        alert('Modo Programación Activado. Los cambios se guardarán en un lote para ser programados.');
    }
    mostrarPanelTarifasPrefijadas();
}

function abrirModalProgramarCambiosTarifas() {
    const count = Object.keys(loteCambiosTarifas).length;
    if (!count) { alert('No hay cambios en el lote para programar.'); return; }
    document.getElementById('countCambiosProgramar').textContent = count;
    const now = new Date();
    now.setDate(now.getDate() + 1);
    document.getElementById('fechaProgramadaTarifas').value = now.toISOString().slice(0, 16);
    document.getElementById('modalProgramarCambiosTarifas').style.display = 'flex';
}

function ejecutarGuardarProgramacionTarifas() {
    const fecha = document.getElementById('fechaProgramadaTarifas').value;
    if (!fecha) { alert('Por favor, selecciona una fecha y hora.'); return; }

    const cambiosArr = Object.entries(loteCambiosTarifas).map(([key, cambio]) => {
        const [idProducto, idTarifa] = key.split('-');
        return {
            idProducto,
            idTarifa,
            precioNuevo: typeof cambio === 'object' ? cambio.nuevo : cambio,
            precioAnterior: typeof cambio === 'object' ? cambio.anterior : 0
        };
    });

    const fd = new FormData();
    fd.append('programarCambiosTarifas', '1');
    fd.append('fecha_programada', fecha);
    fd.append('cambios', JSON.stringify(cambiosArr));

    fetch('api/tarifas.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.ok) {
                alert('¡Cambios programados correctamente!');
                cerrarModal('modalProgramarCambiosTarifas');
                modoProgramacionTarifas = false;
                loteCambiosTarifas = {};
                mostrarPanelTarifasPrefijadas();
            } else {
                alert('Error al programar: ' + (data.error || 'Desconocido'));
            }
        });
}

// ═══════════════════════════════════════════════════════════════════════════════
// TARIFAS PREFIJADAS - FUNCIONES PRINCIPALES
// ═══════════════════════════════════════════════════════════════════════════════

/**
 * Muestra el panel de tarifas prefijadas
 */
function mostrarPanelTarifasPrefijadas(abrirModal = false) {
    const contenedor = document.getElementById('adminContenido');
    seccionActual = 'tarifas-prefijadas';
    adminTablaHeaderHTML = '';

    // Detectar tema actual
    const isDark = document.body.classList.contains('dark-mode');
    const bgColor = isDark ? '#1f2937' : 'white';
    const textColor = isDark ? '#e5e7eb' : '#374151';
    const subTextColor = isDark ? '#9ca3af' : '#6b7280';
    const borderColor = isDark ? '#374151' : '#e5e7eb';
    const tableHeaderBg = isDark ? '#111827' : '#f9fafb';
    const tableRowBorder = isDark ? '#374151' : '#e5e7eb';
    const cardBg = isDark ? '#1f2937' : 'white';
    const modalContentBg = isDark ? '#1f2937' : 'white';

    return Promise.all([
        fetch('api/tarifas.php').then(res => res.json()),
        fetch('api/productos.php').then(res => res.json())
    ])
        .then(([tarifas, productos]) => {
            // Guardar todos los productos para paginación
            window.tarifasData = tarifas;
            todosLosProductosTarifas = productos;
            productosOriginalesTarifas = [...productos];
            paginaActualTarifas = 1;

            // Calcular índices de la página actual
            const inicio = (paginaActualTarifas - 1) * productosPorPaginaTarifas;
            const fin = inicio + productosPorPaginaTarifas;
            const productosPagina = productos.slice(inicio, fin);

            // Generar filas de la tabla de precios
            let filasTablaProductos = '';
            productosPagina.forEach(prod => {
                let precioBaseOriginal = parseFloat(prod.precio);
                const iva = parseFloat(prod.iva) || 21;

                let precioBaseAMostrar = precioBaseOriginal;
                if (tarifasMostrarConIva) {
                    precioBaseAMostrar = precioBaseOriginal * (1 + iva / 100);
                }

                let fila = `
                <tr style="border-bottom: 1px solid ${tableRowBorder};">
                    <td style="padding: 8px 6px; font-weight: 500; color: ${textColor};">${prod.nombre}</td>
                    <td style="padding: 8px 6px; color: ${isDark ? '#f3f4f6' : '#1f2937'}; font-weight: 600; text-align: right;">${precioBaseAMostrar.toFixed(2)} €</td>`;

                tarifas.forEach(tarifa => {
                    const idTarifa = tarifa.id;
                    const dataTarifa = prod.preciosTarifas && prod.preciosTarifas[idTarifa];

                    let precioFinal = 0;
                    let esManual = false;

                    if (dataTarifa) {
                        precioFinal = parseFloat(dataTarifa.precio);
                        esManual = dataTarifa.es_manual == 1;
                        if (tarifasMostrarConIva) {
                            precioFinal = precioFinal * (1 + iva / 100);
                        }
                    } else {
                        const descuento = parseFloat(tarifa.descuento_porcentaje) || 0;
                        precioFinal = precioBaseAMostrar * (1 - descuento / 100);
                    }

                    const manualStyle = esManual
                        ? 'border: 1px solid #10b981; background: #ecfdf5; color: #065f46;'
                        : (isDark
                            ? 'border: 1px solid #374151; background: #111827; color: #10b981;'
                            : 'border: 1px solid #d1d5db; background: white; color: #10b981;');
                    const disabledAttr = (tarifasMostrarConIva && !modoProgramacionTarifas) ? 'disabled' : '';
                    const disabledStyle = (tarifasMostrarConIva && !modoProgramacionTarifas) ? 'opacity: 0.5; cursor: not-allowed;' : '';

                    // Comprobar si hay un cambio programado en el lote local
                    const key = `${prod.id}-${idTarifa}`;
                    let valueToShow = precioFinal;
                    let customClass = '';
                    if (loteCambiosTarifas[key] !== undefined) {
                        valueToShow = parseFloat(loteCambiosTarifas[key]);
                        if (tarifasMostrarConIva) {
                            valueToShow = valueToShow * (1 + iva / 100);
                        }
                        customClass = 'input-precio-programado';
                    }

                    fila += `
                    <td style="padding: 8px 6px;">
                        <div style="display: flex; align-items: center; gap: 4px;">
                            <input type="number" step="0.01"
                                value="${valueToShow.toFixed(2)}"
                                data-precio-anterior="${precioFinal.toFixed(4)}"
                                onchange="actualizarPrecioTarifaIndividual(${prod.id}, ${idTarifa}, this, ${iva})"
                                ${disabledAttr}
                                class="${customClass}"
                                style="width: 70px; padding: 4px 6px; border-radius: 4px; font-weight: 600; text-align: right; ${manualStyle} ${disabledStyle}">
                            <span style="font-size: 14px; font-weight: 600; color: #10b981;">€</span>
                            ${esManual ? '<i class="fas fa-hand-paper" title="Precio manual" style="color: #10b981; font-size: 12px;"></i>' : ''}
                        </div>
                    </td>`;
                });

                fila += `</tr>`;
                filasTablaProductos += fila;
            });

            let filasTablaTarifas = '';
            tarifas.forEach(tarifa => {
                const requiereCliente = tarifa.requiere_cliente ? 'Sí' : 'No';
                const descuentoBadge = `<span style="background: ${isDark ? '#1e3a8a' : '#dbeafe'}; color: ${isDark ? '#bfdbfe' : '#1e40af'}; padding: 4px 10px; border-radius: 12px; font-weight: 600; font-size: 13px;">${tarifa.descuento_porcentaje}%</span>`;
                filasTablaTarifas += `
                <tr style="border-bottom: 1px solid ${tableRowBorder};">
                    <td style="padding: 12px; font-weight: 600; color: ${textColor};">${tarifa.nombre}</td>
                    <td style="padding: 12px; color: ${subTextColor};">${tarifa.descripcion || '-'}</td>
                    <td style="padding: 12px;">${descuentoBadge}</td>
                    <td style="padding: 12px; color: ${tarifa.requiere_cliente ? '#10b981' : subTextColor};">${requiereCliente}</td>
                    <td style="padding: 12px;">
                        <button onclick="abrirModalEditarTarifa(${tarifa.id}, '${tarifa.nombre.replace(/'/g, "\\'")}', '${(tarifa.descripcion || '').replace(/'/g, "\\'")}', ${tarifa.descuento_porcentaje}, ${tarifa.requiere_cliente ? 1 : 0})" style="padding: 6px 12px; background: #6366f1; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 13px; margin-right: 5px;"><i class="fas fa-pen"></i> Editar</button>
                        <button onclick="eliminarTarifa(${tarifa.id}, '${tarifa.nombre.replace(/'/g, "\\'")}')" style="padding: 6px 12px; background: #ef4444; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 13px;"><i class="fas fa-trash"></i> Eliminar</button>
                    </td>
                </tr>`;
            });

            // Generar encabezados dinámicos con sticky y outline
            let cabecerasPrecios = `
                <th style="padding: 12px 8px; text-align: left; font-weight: 600; font-size: 13px; text-transform: uppercase; color: ${textColor}; position: -webkit-sticky; position: sticky; top: -1px; z-index: 10; background: ${tableHeaderBg}; outline: 1px solid ${borderColor}; outline-offset: -1px; border: none;">Producto</th>
                <th style="padding: 12px 8px; text-align: left; font-weight: 600; font-size: 13px; text-transform: uppercase; color: ${textColor}; position: -webkit-sticky; position: sticky; top: -1px; z-index: 10; background: ${tableHeaderBg}; outline: 1px solid ${borderColor}; outline-offset: -1px; border: none;">Precio</th>`;

            let detalleDescuentos = [];
            tarifas.forEach(tarifa => {
                cabecerasPrecios += `<th style="padding: 12px 8px; text-align: left; font-weight: 600; font-size: 13px; text-transform: uppercase; color: ${textColor}; position: -webkit-sticky; position: sticky; top: -1px; z-index: 10; background: ${tableHeaderBg}; outline: 1px solid ${borderColor}; outline-offset: -1px; border: none;">${tarifa.nombre}</th>`;
                detalleDescuentos.push(`${tarifa.nombre} (${tarifa.descuento_porcentaje}%)`);
            });
            let descripcionDescuentos = 'Vista de precios según las tarifas aplicadas. Descuentos: ' + (detalleDescuentos.length > 0 ? detalleDescuentos.join(', ') : 'Ninguno');

            contenedor.innerHTML = `
            <div class="admin-tabla-header">
                <h2 style="margin: 0; font-size: 24px; font-weight: 600; color: ${textColor};">Tarifas Prefijadas ${modoProgramacionTarifas ? '<span style="color: #f59e0b; font-size: 14px; margin-left: 10px;">(MODO PROGRAMACIÓN ACTIVO)</span>' : ''}</h2>
                <p style="color: ${subTextColor}; margin-top: 5px;">${modoProgramacionTarifas ? 'Planifica los cambios de precios para una fecha futura. Estos no se aplicarán inmediatamente.' : 'Vista de precios según las tarifas aplicadas.'}</p>
            </div>
            <div style="display: flex; gap: 10px; margin-bottom: 20px; align-items: center; flex-wrap: wrap;">
                <input type="text"
                    id="buscarProductoTarifa"
                    placeholder="Buscar producto..."
                    value="${tarifaBusquedaProducto}"
                    oninput="tarifaBusquedaProducto = this.value; filtrarTablaTarifas();"
                    style="padding: 10px 15px; border: 1px solid ${borderColor}; border-radius: 8px; font-size: 14px; background: ${isDark ? '#374151' : 'white'}; color: ${textColor}; outline: none; transition: border-color 0.2s; min-width: 250px;"
                    onfocus="this.style.borderColor = '#6366f1';"
                    onblur="this.style.borderColor = '${borderColor}';">

                ${!modoProgramacionTarifas ? `
                    <button onclick="abrirModalTarifas()" class="admin-top-btn" style="background: #6366f1; color: white;">
                        <i class="fas fa-tags"></i> Ver/Editar Tarifas
                    </button>
                    <button onclick="toggleTarifasIva()" class="admin-top-btn" style="background: ${tarifasMostrarConIva ? '#10b981' : (isDark ? '#4b5563' : '#4b5563')}; color: white;">
                        <i class="fas ${tarifasMostrarConIva ? 'fa-file-invoice-dollar' : 'fa-coins'}"></i>
                        ${tarifasMostrarConIva ? 'Ver Sin IVA' : 'Ver Con IVA'}
                    </button>
                    <button onclick="alternarModoProgramacionTarifas()" class="admin-top-btn" style="background: #f59e0b; color: white;">
                        <i class="fas fa-clock"></i> Programar Cambios
                    </button>
                    <button onclick="abrirModalVerCambiosTarifasProgramados()" class="admin-top-btn" style="background: #3b82f6; color: white;">
                        <i class="fas fa-history"></i> Ver Programaciones
                    </button>
                ` : `
                    <button onclick="abrirModalProgramarCambiosTarifas()" class="admin-top-btn" style="background: #10b981; color: white;">
                        <i class="fas fa-check"></i> Finalizar y Programar (${Object.keys(loteCambiosTarifas).length})
                    </button>
                    <button onclick="alternarModoProgramacionTarifas()" class="admin-top-btn" style="background: #ef4444; color: white;">
                        <i class="fas fa-times"></i> Cancelar Modo Programación
                    </button>
                `}
            </div>

            <div style="margin-top: 25px; border-top: 2px solid ${borderColor}; padding-top: 20px;">
                <div class="admin-tabla-header">
                    <h2 style="margin: 0; font-size: 24px; font-weight: 600; color: ${textColor};">Precios por Producto</h2>
                    <p style="color: ${subTextColor}; margin-top: 5px;">${descripcionDescuentos} ${tarifasMostrarConIva ? '(Precios con IVA incluido)' : '(Precios base sin IVA)'}</p>
                </div>
                <div style="border: 1px solid ${borderColor}; border-radius: 8px; overflow: hidden;">
                    <table style="width: 100%; border-collapse: separate; border-spacing: 0; border-radius: 8px; background: ${cardBg};" class="tabla-precios-producto">
                        <thead class="tabla-precios-head">
                            <tr>
                                ${cabecerasPrecios}
                            </tr>
                        </thead>
                        <tbody id="tablaPreciosProductos">${filasTablaProductos}</tbody>
                    </table>
                </div>
                <div id="paginacionTarifas">
                    ${getPaginacionTarifasHTML(Math.ceil(productos.length / productosPorPaginaTarifas))}
                </div>
            </div>
            <div id="modalesTarifas"></div>

            <style>
                .admin-top-btn {
                    padding: 10px 15px;
                    border: none;
                    border-radius: 8px;
                    cursor: pointer;
                    font-weight: 500;
                    transition: all 0.2s;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    font-size: 14px;
                }
                .admin-top-btn:hover {
                    filter: brightness(0.9);
                    transform: translateY(-1px);
                }
                .input-precio-programado {
                    border: 2px solid #f59e0b !important;
                    background: #fffbeb !important;
                    color: #92400e !important;
                }
                .dark-mode .input-precio-programado {
                    background: #451a03 !important;
                    color: #fbbf24 !important;
                }
            </style>

            <!-- Modal de Tarifas -->
            <div id="modalTarifas" class="modal-overlay" style="display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.6); align-items: center; justify-content: center; backdrop-filter: blur(2px);">
                <div class="modal-content" style="background: ${modalContentBg}; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.3); max-width: 800px; width: 90%; max-height: 80vh; overflow: hidden; display: flex; flex-direction: column; border: 1px solid ${borderColor};">
                    <div style="background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%); color: white; padding: 20px; display: flex; justify-content: space-between; align-items: center;">
                        <h3 style="margin: 0; font-size: 18px; font-weight: 600;">Gestión de Tarifas</h3>
                        <button onclick="cerrarModal('modalTarifas')" style="background: none; border: none; color: white; font-size: 24px; cursor: pointer; padding: 0; line-height: 1; opacity: 0.8; transition: opacity 0.2s;" onmouseover="this.style.opacity=1" onmouseout="this.style.opacity=0.8">&times;</button>
                    </div>
                    <div style="padding: 20px; overflow-y: auto; flex: 1;">
                        <div style="margin-bottom: 20px;">
                            <button onclick="abrirModalNuevaTarifa()" style="padding: 10px 20px; background: #10b981; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; display: flex; align-items: center; gap: 8px; transition: transform 0.1s;" onmousedown="this.style.transform='scale(0.98)'" onmouseup="this.style.transform='scale(1)'">
                                <i class="fas fa-plus"></i> Nueva Tarifa
                            </button>
                        </div>
                        <p style="color: ${subTextColor}; margin-top: 0; margin-bottom: 15px; font-size: 14px;">Gestiona las tarifas disponibles en el selector de tickets del cajero.</p>
                        <div style="overflow-x: auto; max-height: 400px; overflow-y: auto; border: 1px solid ${borderColor}; border-radius: 8px;">
                            <table style="width: 100%; border-collapse: collapse; background: ${isDark ? '#111827' : 'white'};" class="tabla-tarifas">
                                <thead class="tabla-tarifas-head" style="position: sticky; top: 0; z-index: 10; background: ${tableHeaderBg}; border-bottom: 2px solid ${borderColor};">
                                    <tr>
                                        <th style="padding: 14px 12px; text-align: left; font-weight: 600; font-size: 13px; text-transform: uppercase; color: ${textColor};">Nombre</th>
                                        <th style="padding: 14px 12px; text-align: left; font-weight: 600; font-size: 13px; text-transform: uppercase; color: ${textColor};">Descripción</th>
                                        <th style="padding: 14px 12px; text-align: left; font-weight: 600; font-size: 13px; text-transform: uppercase; color: ${textColor};">Descuento</th>
                                        <th style="padding: 14px 12px; text-align: left; font-weight: 600; font-size: 13px; text-transform: uppercase; color: ${textColor};">Requiere Cliente</th>
                                        <th style="padding: 14px 12px; text-align: left; font-weight: 600; font-size: 13px; text-transform: uppercase; color: ${textColor};">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="tablaTarifas">${filasTablaTarifas}</tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>`;

            if (abrirModal) {
                abrirModalTarifas();
            }
        })
        .catch(err => {
            console.error('Error:', err);
            contenedor.innerHTML = '<p style="color: red;">Error al cargar las tarifas o productos</p>';
        });
}

/**
 * Abre el modal de tarifas
 */
function abrirModalTarifas() {
    document.getElementById('modalTarifas').style.display = 'flex';
}

/**
 * Abre el modal para crear una nueva tarifa
 */
function abrirModalNuevaTarifa() {
    const modalesDiv = document.getElementById('modalesTarifas');
    const isDark = document.body.classList.contains('dark-mode');
    const bgColor = isDark ? '#1f2937' : 'white';
    const textColor = isDark ? '#e5e7eb' : '#374151';

    modalesDiv.innerHTML = `
        <div id="modalNuevaTarifa" class="modal-overlay" style="display: flex; position: fixed; z-index: 10001;">
            <div class="modal-content" style="background: ${bgColor}; border-radius: 12px; max-width: 500px; width: 90%;">
                <div style="background: linear-gradient(135deg, #059669, #10b981); color: white; padding: 20px;">
                    <h3 style="margin: 0;">Nueva Tarifa</h3>
                </div>
                <div style="padding: 25px;">
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 6px; font-weight: 600; color: ${textColor};">Nombre:</label>
                        <input type="text" id="nuevaTarifaNombre" style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 6px; box-sizing: border-box;" required>
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 6px; font-weight: 600; color: ${textColor};">Descripción:</label>
                        <textarea id="nuevaTarifaDescripcion" rows="3" style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 6px; box-sizing: border-box; resize: vertical;"></textarea>
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 6px; font-weight: 600; color: ${textColor};">Descuento (%):</label>
                        <input type="number" id="nuevaTarifaDescuento" step="0.01" min="0" max="100" value="0" style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 6px; box-sizing: border-box;">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: flex; align-items: center; cursor: pointer;">
                            <input type="checkbox" id="nuevaTarifaRequiereCliente" style="width: 18px; height: 18px; margin-right: 8px;">
                            <span style="font-weight: 500; color: ${textColor};">Requiere búsqueda de cliente</span>
                        </label>
                    </div>
                </div>
                <div style="padding: 15px 25px; display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid #e5e7eb;">
                    <button onclick="cerrarModal('modalNuevaTarifa')" style="padding: 10px 20px; border: 1px solid #d1d5db; background: white; border-radius: 6px; cursor: pointer;">Cancelar</button>
                    <button onclick="guardarNuevaTarifa()" style="padding: 10px 20px; background: #059669; color: white; border: none; border-radius: 6px; cursor: pointer;">Guardar</button>
                </div>
            </div>
        </div>`;
}

/**
 * Guarda una nueva tarifa
 */
function guardarNuevaTarifa() {
    const nombre = document.getElementById('nuevaTarifaNombre').value.trim();
    const descripcion = document.getElementById('nuevaTarifaDescripcion').value.trim();
    const descuento_porcentaje = parseFloat(document.getElementById('nuevaTarifaDescuento').value) || 0;
    const requiere_cliente = document.getElementById('nuevaTarifaRequiereCliente').checked ? 1 : 0;

    if (!nombre) {
        alert('El nombre es obligatorio');
        return;
    }

    const formData = new FormData();
    formData.append('nombre', nombre);
    formData.append('descripcion', descripcion);
    formData.append('descuento_porcentaje', descuento_porcentaje);
    formData.append('requiere_cliente', requiere_cliente);

    fetch('api/tarifas.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.ok) {
                alert('Tarifa creada correctamente');
                cerrarModal('modalNuevaTarifa');
                mostrarPanelTarifasPrefijadas(true);
            } else {
                alert('Error al crear: ' + (data.error || 'Desconocido'));
            }
        })
        .catch(err => alert('Error: ' + err.message));
}

/**
 * Abre el modal para editar una tarifa
 */
function abrirModalEditarTarifa(id, nombre, descripcion, descuento, requiereCliente) {
    const modalesDiv = document.getElementById('modalesTarifas');
    const isDark = document.body.classList.contains('dark-mode');
    const bgColor = isDark ? '#1f2937' : 'white';
    const textColor = isDark ? '#e5e7eb' : '#374151';

    modalesDiv.innerHTML = `
        <div id="modalEditarTarifa" class="modal-overlay" style="display: flex; position: fixed; z-index: 10001;">
            <div class="modal-content" style="background: ${bgColor}; border-radius: 12px; max-width: 500px; width: 90%;">
                <div style="background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; padding: 20px;">
                    <h3 style="margin: 0;">Editar Tarifa</h3>
                </div>
                <div style="padding: 25px;">
                    <input type="hidden" id="editarTarifaId" value="${id}">
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 6px; font-weight: 600; color: ${textColor};">Nombre:</label>
                        <input type="text" id="editarTarifaNombre" value="${nombre}" style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 6px; box-sizing: border-box;" required>
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 6px; font-weight: 600; color: ${textColor};">Descripción:</label>
                        <textarea id="editarTarifaDescripcion" rows="3" style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 6px; box-sizing: border-box; resize: vertical;">${descripcion}</textarea>
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 6px; font-weight: 600; color: ${textColor};">Descuento (%):</label>
                        <input type="number" id="editarTarifaDescuento" step="0.01" min="0" max="100" value="${descuento}" style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 6px; box-sizing: border-box;">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: flex; align-items: center; cursor: pointer;">
                            <input type="checkbox" id="editarTarifaRequiereCliente" ${requiereCliente ? 'checked' : ''} style="width: 18px; height: 18px; margin-right: 8px;">
                            <span style="font-weight: 500; color: ${textColor};">Requiere búsqueda de cliente</span>
                        </label>
                    </div>
                </div>
                <div style="padding: 15px 25px; display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid #e5e7eb;">
                    <button onclick="cerrarModal('modalEditarTarifa')" style="padding: 10px 20px; border: 1px solid #d1d5db; background: white; border-radius: 6px; cursor: pointer;">Cancelar</button>
                    <button onclick="guardarEditarTarifa()" style="padding: 10px 20px; background: #6366f1; color: white; border: none; border-radius: 6px; cursor: pointer;">Guardar</button>
                </div>
            </div>
        </div>`;
}

/**
 * Guarda los cambios de una tarifa
 */
function guardarEditarTarifa() {
    const id = document.getElementById('editarTarifaId').value;
    const nombre = document.getElementById('editarTarifaNombre').value.trim();
    const descripcion = document.getElementById('editarTarifaDescripcion').value.trim();
    const descuento_porcentaje = parseFloat(document.getElementById('editarTarifaDescuento').value) || 0;
    const requiere_cliente = document.getElementById('editarTarifaRequiereCliente').checked ? 1 : 0;

    if (!nombre) {
        alert('El nombre es obligatorio');
        return;
    }

    ejecutarGuardarTarifa({ id, nombre, descripcion, descuento_porcentaje, requiere_cliente });
}

/**
 * Ejecuta el guardado de tarifa (nueva o editada)
 */
function ejecutarGuardarTarifa(formData) {
    fetch('api/tarifas.php', {
        method: 'POST',
        body: new URLSearchParams(formData)
    })
        .then(res => res.json())
        .then(data => {
            if (data.ok) {
                alert('Tarifa guardada correctamente');
                const modalId = formData.id ? 'modalEditarTarifa' : 'modalNuevaTarifa';
                cerrarModal(modalId);
                mostrarPanelTarifasPrefijadas(true);
            } else {
                alert('Error al guardar: ' + (data.error || 'Desconocido'));
            }
        })
        .catch(err => alert('Error: ' + err.message));
}

/**
 * Confirma antes de cambiar tarifa
 */
function confirmarCambioTarifa(sobreescribir) {
    if (!tarifaDataPendiente) return;
    // Implementation depends on the specific requirement
    mostrarPanelTarifasPrefijadas();
}

/**
 * Elimina una tarifa
 */
function eliminarTarifa(id, nombre) {
    if (!confirm('¿Estás seguro de que quieres eliminar la tarifa "' + nombre + '"?')) return;

    fetch('api/tarifas.php?eliminar=' + id, { method: 'DELETE' })
        .then(res => res.json())
        .then(data => {
            if (data.ok) {
                alert('Tarifa eliminada');
                mostrarPanelTarifasPrefijadas();
            } else {
                alert('Error al eliminar: ' + (data.error || 'Desconocido'));
            }
        });
}

function abrirModalVerCambiosTarifasProgramados() {
    document.getElementById('modalVerCambiosTarifasProgramados').style.display = 'flex';
    cargarCambiosTarifasBatches();
}

function cargarCambiosTarifasBatches() {
    const container = document.getElementById('listaBatchesTarifas');
    container.innerHTML = '<div style="padding: 20px; text-align: center;"><i class="fas fa-spinner fa-spin"></i> Cargando...</div>';

    fetch('api/tarifas.php?obtenerCambiosProgramados=1')
        .then(res => res.json())
        .then(data => {
            if (data.ok) {
                if (data.batches.length === 0) {
                    container.innerHTML = '<div style="padding: 30px; text-align: center; color: #6b7280;">No hay programaciones registradas.</div>';
                    return;
                }

                let html = `
            <table style="width:100%; border-collapse: collapse;">
                <thead style="background: var(--bg-secondary);">
                    <tr>
                        <th style="padding: 12px; text-align: left; border-bottom: 2px solid var(--border-main);">ID</th>
                        <th style="padding: 12px; text-align: left; border-bottom: 2px solid var(--border-main);">Fecha Programada</th>
                        <th style="padding: 12px; text-align: left; border-bottom: 2px solid var(--border-main);">Productos</th>
                        <th style="padding: 12px; text-align: left; border-bottom: 2px solid var(--border-main);">Estado</th>
                        <th style="padding: 12px; text-align: right; border-bottom: 2px solid var(--border-main);">Acciones</th>
                    </tr>
                </thead>
                <tbody>`;

                data.batches.forEach(b => {
                    const isPendiente = b.estado === 'pendiente';
                    const statusBadge = isPendiente
                        ? '<span style="background: #fef3c7; color: #92400e; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600;">Pendiente</span>'
                        : '<span style="background: #d1fae5; color: #065f46; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600;">Aplicado</span>';

                    const fecha = new Date(b.fecha_programada).toLocaleString();

                    html += `
                <tr style="border-bottom: 1px solid var(--border-main);">
                    <td style="padding: 12px;">#${b.id}</td>
                    <td style="padding: 12px;">${fecha}</td>
                    <td style="padding: 12px;">${b.total_productos} ítems</td>
                    <td style="padding: 12px;">${statusBadge}</td>
                    <td style="padding: 12px; text-align: right; display: flex; justify-content: flex-end; gap: 5px;">
                        <button onclick="verDetalleBatchTarifas(${b.id})" class="btn-info" style="padding: 5px 10px; font-size: 12px;"><i class="fas fa-eye"></i></button>
                        ${isPendiente ? `<button onclick="eliminarBatchTarifas(${b.id})" class="btn-danger" style="padding: 5px 10px; font-size: 12px;"><i class="fas fa-trash"></i></button>` : ''}
                    </td>
                </tr>`;
                });

                html += `</tbody></table>`;
                container.innerHTML = html;
            }
        })
        .catch(err => {
            console.error('Error al cargar programaciones de tarifas:', err);
            container.innerHTML = '<div style="padding: 20px; text-align: center; color: red;">Error al cargar las programaciones. Por favor, inténtelo de nuevo o contacte con el administrador.</div>';
        });
}

function verDetalleBatchTarifas(id) {
    document.getElementById('detalleBatchId').textContent = id;
    const tableDiv = document.getElementById('tablaDetalleBatch');
    tableDiv.innerHTML = '<div style="padding: 20px; text-align: center;"><i class="fas fa-spinner fa-spin"></i> Cargando...</div>';

    document.getElementById('modalDetalleBatchTarifas').style.display = 'flex';

    fetch(`api/tarifas.php?verDetalleBatch=${id}`)
        .then(res => res.json())
        .then(data => {
            console.log('Response from API:', data);
            if (data.ok) {
                if (data.detalles && data.detalles.length > 0) {
                    let html = `
                <table style="width:100%; border-collapse: collapse; font-size: 13px;">
                    <thead style="background: var(--bg-secondary);">
                        <tr>
                            <th style="padding: 8px; text-align: left;">Producto</th>
                            <th style="padding: 8px; text-align: left;">Tarifa</th>
                            <th style="padding: 8px; text-align: right;">Precio Anterior</th>
                            <th style="padding: 8px; text-align: right;">Nuevo Precio</th>
                            <th style="padding: 8px; text-align: right;">Diferencia</th>
                        </tr>
                    </thead>
                    <tbody>`;

                    data.detalles.forEach(d => {
                        const precioAnterior = parseFloat(d.precio_anterior || 0);
                        const precioNuevo = parseFloat(d.precio_nuevo);
                        const diferencia = precioNuevo - precioAnterior;
                        const diferenciaSigno = diferencia >= 0 ? '+' : '';
                        const diferenciaColor = diferencia >= 0 ? '#10b981' : '#ef4444';

                        html += `
                    <tr style="border-bottom: 1px solid var(--border-main);">
                        <td style="padding: 8px;">${d.producto_nombre}</td>
                        <td style="padding: 8px;">${d.tarifa_nombre}</td>
                        <td style="padding: 8px; text-align: right;">${precioAnterior.toFixed(2)} €</td>
                        <td style="padding: 8px; text-align: right; font-weight: 600;">${precioNuevo.toFixed(2)} €</td>
                        <td style="padding: 8px; text-align: right; font-weight: 600; color: ${diferenciaColor};">${diferenciaSigno}${diferencia.toFixed(2)} €</td>
                    </tr>`;
                    });

                    html += `</tbody></table>`;
                    tableDiv.innerHTML = html;
                } else {
                    tableDiv.innerHTML = '<div style="padding: 20px; text-align: center; color: #6b7280;">No hay detalles para esta programación.</div>';
                }
            } else {
                tableDiv.innerHTML = '<div style="padding: 20px; text-align: center; color: red;">Error: ' + (data.error || 'Error desconocido. Ver consola para detalles.') + '</div>';
            }
        })
        .catch(err => {
            console.error('Error al cargar detalles del batch:', err);
            tableDiv.innerHTML = '<div style="padding: 20px; text-align: center; color: red;">Error al cargar los detalles. Por favor, inténtelo de nuevo.</div>';
        });
}

function eliminarBatchTarifas(id) {
    if (!confirm('¿Estás seguro de que deseas cancelar esta programación?')) return;

    fetch(`api/tarifas.php?eliminarBatch=${id}`, {
        method: 'DELETE'
    })
        .then(res => res.json())
        .then(data => {
            if (data.ok) {
                cargarCambiosTarifasBatches();
            } else {
                alert('Error al eliminar: ' + (data.error || 'Desconocido'));
            }
        });
}