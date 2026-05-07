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
                            <th>Nombre</th><th>Productos</th><th>Fecha Creación</th><th>Acciones</th>
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
                    : '<tr><td colspan="4" style="text-align:center;padding:20px;color:#6b7280;">No hay categorías.</td></tr>';

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
    const decimals = parseInt(p.decimales ?? 2);
    const precio = parseFloat(p.precio).toLocaleString('es-ES', { minimumFractionDigits: decimals, maximumFractionDigits: decimals });

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
        modal.innerHTML = '<div class="modal-content modal-premium" style="max-width:500px; padding: 0; overflow: hidden;"></div>';
        document.body.appendChild(modal);
    }
    modal.querySelector('.modal-content').innerHTML = `
        <div class="modal-header-premium" style="background: linear-gradient(135deg, #10b981, #059669); padding: 20px 25px; text-align: left; position: relative;">
            <h3 style="margin: 0; color: #fff; font-size: 1.3rem;">Nueva Categoría</h3>
            <p class="modal-subtitulo" style="margin: 5px 0 0 0; color: rgba(255,255,255,0.8); font-size: 0.85rem;">Crea una nueva clasificación para tus productos</p>
            <button onclick="cerrarModal('modalNuevaCategoria')" style="position: absolute; top: 20px; right: 20px; background: rgba(255,255,255,0.2); border: none; color: white; width: 30px; height: 30px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: background 0.2s;">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div style="padding: 25px;">
            <div class="editar-prod-campos" style="display: flex; flex-direction: column; gap: 15px;">
                <div class="editar-prod-fila-premium">
                    <label style="display: block; font-size: 0.8rem; color: #4b5563; font-weight: 600; margin-bottom: 5px;">Nombre <span style="color:#ef4444">*</span></label>
                    <input type="text" id="nuevaCategoriaNombre" placeholder="Ej: Bebidas Calientes" style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; outline: none; transition: border-color 0.2s;">
                </div>
                <div class="editar-prod-fila-premium">
                    <label style="display: block; font-size: 0.8rem; color: #4b5563; font-weight: 600; margin-bottom: 5px;">Descripción</label>
                    <textarea id="nuevaCategoriaDescripcion" placeholder="Opcional..." rows="3" style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; outline: none; transition: border-color 0.2s; resize: vertical;"></textarea>
                </div>
            </div>
            <div style="margin-top: 30px; display: flex; justify-content: flex-end; gap: 12px; border-top: 1px solid #e5e7eb; padding-top: 20px;">
                <button onclick="cerrarModal('modalNuevaCategoria')" class="btn-modal-cancelar" style="margin: 0; padding: 10px 20px; border-radius: 8px; font-weight: 600;">
                    Cancelar
                </button>
                <button onclick="guardarNuevaCategoria()" class="btn-exito" style="margin: 0; padding: 10px 25px; border-radius: 8px; font-weight: 600; background: #10b981; border: none; color: white; display: flex; align-items: center; gap: 8px; cursor: pointer; transition: background 0.2s;">
                    <i class="fas fa-save"></i> Guardar
                </button>
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
            <div class="modal-content modal-premium" style="max-width:500px; padding: 0; overflow: hidden;">
                <div class="modal-header-premium" style="background: linear-gradient(135deg, #10b981, #059669); padding: 20px 25px; text-align: left; position: relative;">
                    <h3 style="margin: 0; color: #fff; font-size: 1.3rem;">Editar Categoría</h3>
                    <p class="modal-subtitulo" style="margin: 5px 0 0 0; color: rgba(255,255,255,0.8); font-size: 0.85rem;">Modifica la información de la clasificación</p>
                    <button onclick="cerrarModal('modalEditarCategoria')" style="position: absolute; top: 20px; right: 20px; background: rgba(255,255,255,0.2); border: none; color: white; width: 30px; height: 30px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: background 0.2s;">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div style="padding: 25px;">
                    <input type="hidden" id="editarCategoriaId">
                    <div class="editar-prod-campos" style="display: flex; flex-direction: column; gap: 15px;">
                        <div class="editar-prod-fila-premium">
                            <label style="display: block; font-size: 0.8rem; color: #4b5563; font-weight: 600; margin-bottom: 5px;">Nombre <span style="color:#ef4444">*</span></label>
                            <input type="text" id="editarCategoriaNombre" required style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; outline: none; transition: border-color 0.2s;">
                        </div>
                        <div class="editar-prod-fila-premium">
                            <label style="display: block; font-size: 0.8rem; color: #4b5563; font-weight: 600; margin-bottom: 5px;">Descripción</label>
                            <textarea id="editarCategoriaDescripcion" rows="4" style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; outline: none; transition: border-color 0.2s; resize: vertical;"></textarea>
                        </div>
                    </div>
                    <div style="margin-top: 30px; display: flex; justify-content: flex-end; gap: 12px; border-top: 1px solid #e5e7eb; padding-top: 20px;">
                        <button onclick="cerrarModal('modalEditarCategoria')" class="btn-modal-cancelar" style="margin: 0; padding: 10px 20px; border-radius: 8px; font-weight: 600;">
                            Cancelar
                        </button>
                        <button onclick="guardarEditarCategoria()" class="btn-exito" style="margin: 0; padding: 10px 25px; border-radius: 8px; font-weight: 600; background: #10b981; border: none; color: white; display: flex; align-items: center; gap: 8px; cursor: pointer; transition: background 0.2s;">
                            <i class="fas fa-save"></i> Guardar
                        </button>
                    </div>
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

function switchIvaSubSeccion(sub) {
    const btnTipos = document.getElementById('tabIvaTipos');
    const btnMasivo = document.getElementById('tabIvaMasivo');
    const secTipos = document.getElementById('ivaSeccionTipos');
    const secMasivo = document.getElementById('ivaSeccionMasivo');
    if (!btnTipos || !btnMasivo || !secTipos || !secMasivo) return;

    if (sub === 'tipos') {
        btnTipos.classList.add('active');
        btnTipos.style.background = '#6366f1';
        btnTipos.style.color = 'white';
        btnMasivo.classList.remove('active');
        btnMasivo.style.background = 'transparent';
        btnMasivo.style.color = 'var(--text-muted)';
        secTipos.style.display = 'block';
        secMasivo.style.display = 'none';
    } else {
        btnMasivo.classList.add('active');
        btnMasivo.style.background = '#6366f1';
        btnMasivo.style.color = 'white';
        btnTipos.classList.remove('active');
        btnTipos.style.background = 'transparent';
        btnTipos.style.color = 'var(--text-muted)';
        secTipos.style.display = 'none';
        secMasivo.style.display = 'flex';
    }
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
            <td style="text-align:center;width:60px;">${t.id}</td>
            <td>${t.nombre}</td>
            <td style="text-align:center;font-weight:600;width:120px;">${t.porcentaje}%</td>
            <td style="text-align:center;width:150px;">
                <button class="btn-admin-accion" onclick="editarIva(${t.id},'${t.nombre}',${t.porcentaje})"><i class="fas fa-pen"></i></button>
                <button class="btn-admin-accion btn-eliminar" onclick="eliminarIva(${t.id})"><i class="fas fa-trash"></i></button>
            </td>
        </tr>`).join('');

    contenedor.innerHTML = `
        <div class="iva-panel-container">
            <div class="iva-panel-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                <div>
                    <h2><i class="fas fa-percentage"></i> Cambiar IVA General</h2>
                    <p class="iva-panel-subtitle">Gestión de tipos de IVA y actualización masiva de productos</p>
                </div>
                <div class="iva-tabs" style="display: flex; background: var(--bg-input); padding: 5px; border-radius: 12px; border: 1px solid var(--border-main);">
                    <button id="tabIvaTipos" onclick="switchIvaSubSeccion('tipos')" class="active" style="padding: 10px 20px; border-radius: 8px; border: none; cursor: pointer; font-weight: 600; display: flex; align-items: center; gap: 8px; transition: all 0.2s; background: #6366f1; color: white;">
                        <i class="fas fa-tags"></i> Tipos de IVA
                    </button>
                    <button id="tabIvaMasivo" onclick="switchIvaSubSeccion('masivo')" style="padding: 10px 20px; border-radius: 8px; border: none; cursor: pointer; font-weight: 600; display: flex; align-items: center; gap: 8px; transition: all 0.2s; background: transparent; color: var(--text-muted);">
                        <i class="fas fa-magic"></i> Actualización Masiva
                    </button>
                </div>
            </div>
            
            <div class="iva-panel-content">
                
                <!-- SECCIÓN 1: TABLA DE TIPOS DE IVA -->
                <div id="ivaSeccionTipos" class="iva-panel-section-1">
                    <div class="iva-panel-card">
                        <div class="iva-tipos-header">
                            <h3 style="font-size: 16px; margin-bottom: 15px;"><i class="fas fa-tags"></i> Tipos de IVA Registrados</h3>
                            <button onclick="abrirModalNuevoIva()" class="btn-admin-accion btn-nuevo" style="padding: 8px 15px;">
                                <i class="fas fa-plus"></i> Nuevo Tipo de IVA
                            </button>
                        </div>
                        <div class="iva-tipos-table-wrapper" style="max-height: 500px; border: 1px solid var(--border-main); border-radius: 8px; overflow: hidden;">
                            <table class="iva-tipos-tabla">
                                <thead>
                                    <tr>
                                        <th style="width: 60px; text-align: center;">ID</th>
                                        <th>Nombre Informativo</th>
                                        <th style="text-align: center; width: 120px;">Porcentaje (%)</th>
                                        <th style="text-align: center; width: 150px;">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>${filasTablaIva}</tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- SECCIÓN 2: PANEL DE CAMBIO Y PREVIEW -->
                <div id="ivaSeccionMasivo" class="iva-panel-section-2" style="display: none; gap: 20px; align-items: flex-start;">
                    
                    <!-- Columna Izquierda: Configuración -->
                    <div class="iva-panel-card" style="width: 380px; flex-shrink: 0; position: sticky; top: 10px;">
                        <h3 style="font-size: 16px; margin-bottom: 20px;"><i class="fas fa-magic"></i> Actualización Masiva</h3>
                        <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 15px;">
                            Selecciona un nuevo tipo de IVA para aplicarlo a todos los productos (puedes excluir productos específicos en la tabla de la derecha).
                        </p>
                        
                        <div class="iva-form-group" style="margin-bottom: 25px;">
                            <label for="nuevoIVA" style="font-weight: 700; display: block; margin-bottom: 10px;">Nuevo tipo de IVA a aplicar:</label>
                            <select id="nuevoIVA" class="iva-select" onchange="actualizarPrevisualizacionIVAAuto()" style="padding: 12px; font-size: 14px; border-width: 2px;">
                                ${opcionesIva}
                            </select>
                        </div>
                        
                        <div class="iva-actions" style="display: flex; flex-direction: column; gap: 12px;">
                            <button onclick="aplicarCambioIVA()" class="iva-btn-aplicar" style="padding: 14px; font-size: 14px; width: 100%;">
                                <i class="fas fa-check-circle"></i> Aplicar a Productos Seleccionados
                            </button>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                <button onclick="abrirModalProgramarIVA()" class="iva-btn-secondary" style="padding: 10px;">
                                    <i class="fas fa-clock"></i> Programar
                                </button>
                                <button onclick="abrirModalVerCambiosProgramados()" class="iva-btn-secondary" style="padding: 10px;">
                                    <i class="fas fa-list-ul"></i> Ver Tareas
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Columna Derecha: Previsualización -->
                    <div id="previsualizacionCambios" style="flex: 1; min-height: 400px; background: var(--bg-main); border-radius: 12px; border: 1px dashed var(--border-main); display: flex; align-items: center; justify-content: center; position: relative;">
                         <div style="text-align: center; color: var(--text-muted); padding: 40px;">
                            <i class="fas fa-eye" style="font-size: 3rem; opacity: 0.2; margin-bottom: 15px; display: block;"></i>
                            <p>Selecciona un IVA para ver la previsualización de los cambios</p>
                         </div>
                    </div>
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
        <div class="previsualizacion-tabla-container" style="width: 100%; display: flex; flex-direction: column;">
            <div class="previsualizacion-tabla-header" style="width: 100%; box-sizing: border-box;">
                <h3>Previsualización del cambio de IVA (${nuevoIVA}%)</h3>
                <div class="previsualizacion-botones">
                    <span class="previsualizacion-hint">💡 Clic en fila para excluir</span>
                    <button class="btn-excluir-todos" onclick="excluirTodosProductos('iva')">Excluir todos</button>
                    <button class="btn-incluir-todos" onclick="incluirTodosProductos('iva')">Incluir todos</button>
                </div>
            </div>
            <div class="previsualizacion-tabla-wrapper" style="width: 100%; overflow-x: auto; flex: 1;">
                <table class="previsualizacion-tabla" style="width: 100%; border-collapse: collapse;">
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
        const prec = parseInt(p.decimales ?? 2);
        html += `
            <tr class="${clase}" onclick="toggleExcluirProducto(${p.id},'iva')">
                <td style="text-align:center;">${excluido ? '❌' : i + 1}</td>
                <td>${p.id}</td>
                <td>${p.nombre}</td>
                <td style="text-align:right;">${parseFloat(p.precio).toFixed(prec)} €</td>
                <td style="text-align:center;">${p.iva_actual}%</td>
                <td style="text-align:center;" class="precio-iva-nuevo">${excluido ? p.iva_actual + '%' : nuevoIVA + '%'}</td>
                <td style="text-align:right;" class="precio-destacado">${precioConIVA.toFixed(prec)} €</td>
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
                    <input type="number" id="porcentajeAjuste" step="0.0001" placeholder="Ej: 10 o -10" oninput="validar4Decimales(this); actualizarPrevisualizacionPreciosAuto()"
                        class="tarifa-input">
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
        <div class="previsualizacion-tabla-container" style="width: 100%; display: flex; flex-direction: column;">
            <div class="previsualizacion-tabla-header" style="width: 100%; box-sizing: border-box;">
                <h3>Previsualización del ajuste (${porcentaje}%)</h3>
                <div class="previsualizacion-botones">
                    <span class="previsualizacion-hint">💡 Clic en fila para excluir</span>
                    <button class="btn-excluir-todos" onclick="excluirTodosProductos('precios')">Excluir todos</button>
                    <button class="btn-incluir-todos" onclick="incluirTodosProductos('precios')">Incluir todos</button>
                </div>
            </div>
            <div class="previsualizacion-tabla-wrapper" style="width: 100%; overflow-x: auto; flex: 1;">
                <table class="previsualizacion-tabla" style="width: 100%; border-collapse: collapse;">
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
        const prec = parseInt(p.decimales ?? 2);
        html += `
            <tr class="${clase}" onclick="toggleExcluirProducto(${p.id},'precios')">
                <td style="text-align:center;">${excluido ? '❌' : i + 1}</td>
                <td>${p.id}</td>
                <td>${p.nombre}</td>
                <td style="text-align:right;">${parseFloat(p.precio_actual).toFixed(prec)} €</td>
                <td style="text-align:right;font-weight:bold;">${parseFloat(precioNuevo).toFixed(prec)} €</td>
                <td style="text-align:right;" class="${excluido ? '' : claseDif}">${signo}${diferencia.toFixed(prec)} €</td>
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

        // Formateador dinámico: mínimo 2 decimales, máximo 4, basado en el precio original (sin inflar por IVA)
        const getPrec = (v, d) => {
            const s = v.toString();
            const decPart = s.split('.')[1] || '';
            return Math.min(4, Math.max(2, d || 2, decPart.length));
        };
        const prec = getPrec(parseFloat(prod.precio), prod.decimales);
        let fila = `
            <tr style="border-bottom:1px solid ${tableRowBorder};">
                <td style="padding:8px 6px;font-weight:500;color:${textColor};">${prod.nombre}</td>
                <td style="padding:8px 6px;font-weight:600;text-align:right;">${precioBase.toFixed(prec)} €</td>`;

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
                        <input type="number" step="0.0001" value="${valueToShow.toFixed(prec)}"
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

                const getPrec = (v, d) => {
                    const s = v.toString();
                    const decPart = s.split('.')[1] || '';
                    return Math.min(4, Math.max(2, d || 2, decPart.length));
                };
                const prec = getPrec(precioBaseOriginal, prod.decimales);
                let fila = `
                <tr style="border-bottom: 1px solid ${tableRowBorder};">
                    <td style="padding: 8px 6px; font-weight: 500; color: ${textColor};">${prod.nombre}</td>
                    <td style="padding: 8px 6px; color: ${isDark ? '#f3f4f6' : '#1f2937'}; font-weight: 600; text-align: right;">${precioBaseAMostrar.toFixed(prec)} €</td>`;

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
                            <input type="number" step="0.0001"
                                value="${valueToShow.toFixed(prec)}"
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

            <!-- Modal de Tarifas (Redesigned Premium) -->
            <div id="modalTarifas" class="modal-overlay" style="display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.6); align-items: center; justify-content: center; backdrop-filter: blur(4px);">
                <div class="modal-content" style="background: ${modalContentBg}; border-radius: 16px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.4); max-width: 900px; width: 95%; max-height: 85vh; overflow: hidden; display: flex; flex-direction: column; border: 1px solid ${borderColor};">
                    <!-- Header Premium -->
                    <div style="background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #a855f7 100%); color: white; padding: 24px 28px; display: flex; justify-content: space-between; align-items: center; position: relative; overflow: hidden;">
                        <div style="position: absolute; top: -20px; right: -20px; width: 120px; height: 120px; background: rgba(255,255,255,0.08); border-radius: 50%;"></div>
                        <div style="position: absolute; bottom: -30px; left: 40%; width: 80px; height: 80px; background: rgba(255,255,255,0.05); border-radius: 50%;"></div>
                        <div style="position: relative; z-index: 1;">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <div style="background: rgba(255,255,255,0.15); border-radius: 12px; padding: 10px; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-tags" style="font-size: 20px;"></i>
                                </div>
                                <div>
                                    <h3 style="margin: 0; font-size: 20px; font-weight: 700; letter-spacing: -0.3px;">Gestión de Tarifas</h3>
                                    <p style="margin: 3px 0 0; font-size: 13px; opacity: 0.85;">Administra las tarifas y descuentos del sistema</p>
                                </div>
                            </div>
                        </div>
                        <button onclick="cerrarModal('modalTarifas')" style="background: rgba(255,255,255,0.15); border: none; color: white; width: 36px; height: 36px; border-radius: 10px; cursor: pointer; font-size: 18px; display: flex; align-items: center; justify-content: center; transition: all 0.2s; position: relative; z-index: 1;" onmouseover="this.style.background='rgba(255,255,255,0.25)'" onmouseout="this.style.background='rgba(255,255,255,0.15)'">&times;</button>
                    </div>

                    <!-- Body -->
                    <div style="padding: 24px 28px; overflow-y: auto; flex: 1;">
                        <!-- Stats Cards Row -->
                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; margin-bottom: 22px;">
                            <div style="background: ${isDark ? '#1e1b4b' : '#eef2ff'}; border: 1px solid ${isDark ? '#312e81' : '#c7d2fe'}; border-radius: 12px; padding: 16px; text-align: center;">
                                <div style="font-size: 24px; font-weight: 700; color: #6366f1;">${tarifas.length}</div>
                                <div style="font-size: 12px; font-weight: 600; color: ${isDark ? '#a5b4fc' : '#6366f1'}; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 2px;">Tarifas Activas</div>
                            </div>
                            <div style="background: ${isDark ? '#052e16' : '#ecfdf5'}; border: 1px solid ${isDark ? '#166534' : '#a7f3d0'}; border-radius: 12px; padding: 16px; text-align: center;">
                                <div style="font-size: 24px; font-weight: 700; color: #10b981;">${tarifas.filter(t => parseFloat(t.descuento_porcentaje) > 0).length}</div>
                                <div style="font-size: 12px; font-weight: 600; color: ${isDark ? '#6ee7b7' : '#059669'}; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 2px;">Con Descuento</div>
                            </div>
                            <div style="background: ${isDark ? '#1e293b' : '#f0f9ff'}; border: 1px solid ${isDark ? '#334155' : '#bae6fd'}; border-radius: 12px; padding: 16px; text-align: center;">
                                <div style="font-size: 24px; font-weight: 700; color: #3b82f6;">${tarifas.filter(t => t.requiere_cliente).length}</div>
                                <div style="font-size: 12px; font-weight: 600; color: ${isDark ? '#93c5fd' : '#2563eb'}; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 2px;">Requieren Cliente</div>
                            </div>
                        </div>

                        <!-- Action Bar -->
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
                            <p style="color: ${subTextColor}; margin: 0; font-size: 13px;"><i class="fas fa-info-circle" style="margin-right: 5px; color: #6366f1;"></i>Gestiona las tarifas disponibles en el selector de tickets del cajero.</p>
                            <button onclick="abrirModalNuevaTarifa()" style="padding: 10px 20px; background: linear-gradient(135deg, #059669, #10b981); color: white; border: none; border-radius: 10px; cursor: pointer; font-weight: 600; font-size: 14px; display: flex; align-items: center; gap: 8px; transition: all 0.2s; box-shadow: 0 2px 8px rgba(16,185,129,0.3);" onmouseover="this.style.transform='translateY(-1px)';this.style.boxShadow='0 4px 12px rgba(16,185,129,0.4)'" onmouseout="this.style.transform='translateY(0)';this.style.boxShadow='0 2px 8px rgba(16,185,129,0.3)'">
                                <i class="fas fa-plus"></i> Nueva Tarifa
                            </button>
                        </div>

                        <!-- Table -->
                        <div style="border: 1px solid ${borderColor}; border-radius: 12px; overflow: hidden;">
                            <table style="width: 100%; border-collapse: collapse; background: ${isDark ? '#111827' : 'white'};" class="tabla-tarifas">
                                <thead class="tabla-tarifas-head" style="background: ${isDark ? '#1f2937' : '#f8fafc'}; border-bottom: 2px solid ${isDark ? '#374151' : '#e2e8f0'};">
                                    <tr>
                                        <th style="padding: 14px 16px; text-align: left; font-weight: 700; font-size: 12px; text-transform: uppercase; letter-spacing: 0.8px; color: ${isDark ? '#94a3b8' : '#64748b'};">Nombre</th>
                                        <th style="padding: 14px 16px; text-align: left; font-weight: 700; font-size: 12px; text-transform: uppercase; letter-spacing: 0.8px; color: ${isDark ? '#94a3b8' : '#64748b'};">Descripción</th>
                                        <th style="padding: 14px 16px; text-align: center; font-weight: 700; font-size: 12px; text-transform: uppercase; letter-spacing: 0.8px; color: ${isDark ? '#94a3b8' : '#64748b'};">Descuento</th>
                                        <th style="padding: 14px 16px; text-align: center; font-weight: 700; font-size: 12px; text-transform: uppercase; letter-spacing: 0.8px; color: ${isDark ? '#94a3b8' : '#64748b'};">Cliente</th>
                                        <th style="padding: 14px 16px; text-align: right; font-weight: 700; font-size: 12px; text-transform: uppercase; letter-spacing: 0.8px; color: ${isDark ? '#94a3b8' : '#64748b'};">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="tablaTarifas">${tarifas.map((tarifa, idx) => {
                                    const rowBg = idx % 2 === 0
                                        ? (isDark ? '#111827' : '#ffffff')
                                        : (isDark ? '#1a2234' : '#f8fafc');
                                    const descPct = parseFloat(tarifa.descuento_porcentaje);
                                    const descBadgeBg = descPct === 0
                                        ? (isDark ? '#1f2937' : '#f1f5f9')
                                        : (isDark ? '#1e3a5f' : '#dbeafe');
                                    const descBadgeColor = descPct === 0
                                        ? (isDark ? '#94a3b8' : '#64748b')
                                        : (isDark ? '#93c5fd' : '#1d4ed8');
                                    const clienteIcon = tarifa.requiere_cliente
                                        ? '<i class="fas fa-user-check" style="color: #10b981; font-size: 16px;" title="Sí"></i>'
                                        : '<i class="fas fa-user-times" style="color: ' + (isDark ? '#4b5563' : '#cbd5e1') + '; font-size: 16px;" title="No"></i>';
                                    return `
                                    <tr style="border-bottom: 1px solid ${isDark ? '#1f2937' : '#f1f5f9'}; background: ${rowBg}; transition: background 0.15s;">
                                        <td style="padding: 14px 16px;">
                                            <div style="display: flex; align-items: center; gap: 10px;">
                                                <div style="width: 36px; height: 36px; border-radius: 10px; background: linear-gradient(135deg, #6366f1, #a855f7); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 14px; flex-shrink: 0;">${tarifa.nombre.charAt(0).toUpperCase()}</div>
                                                <span style="font-weight: 600; color: ${textColor}; font-size: 14px;">${tarifa.nombre}</span>
                                            </div>
                                        </td>
                                        <td style="padding: 14px 16px; color: ${subTextColor}; font-size: 13px; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${tarifa.descripcion || '<span style="opacity: 0.4; font-style: italic;">Sin descripción</span>'}</td>
                                        <td style="padding: 14px 16px; text-align: center;">
                                            <span style="background: ${descBadgeBg}; color: ${descBadgeColor}; padding: 5px 14px; border-radius: 20px; font-weight: 700; font-size: 13px; display: inline-block; min-width: 50px;">${descPct}%</span>
                                        </td>
                                        <td style="padding: 14px 16px; text-align: center;">${clienteIcon}</td>
                                        <td style="padding: 14px 16px; text-align: right;">
                                            <div style="display: flex; justify-content: flex-end; gap: 8px;">
                                                <button onclick="abrirModalEditarTarifa(${tarifa.id}, '${tarifa.nombre.replace(/'/g, "\\'")}', '${(tarifa.descripcion || '').replace(/'/g, "\\'")}', ${tarifa.descuento_porcentaje}, ${tarifa.requiere_cliente ? 1 : 0})" style="padding: 8px 14px; background: ${isDark ? '#312e81' : '#eef2ff'}; color: #6366f1; border: 1px solid ${isDark ? '#4338ca' : '#c7d2fe'}; border-radius: 8px; cursor: pointer; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s;" onmouseover="this.style.background='#6366f1';this.style.color='white'" onmouseout="this.style.background='${isDark ? '#312e81' : '#eef2ff'}';this.style.color='#6366f1'"><i class="fas fa-pen" style="font-size: 11px;"></i> Editar</button>
                                                <button onclick="eliminarTarifa(${tarifa.id}, '${tarifa.nombre.replace(/'/g, "\\'")}')" style="padding: 8px 14px; background: ${isDark ? '#450a0a' : '#fef2f2'}; color: #ef4444; border: 1px solid ${isDark ? '#7f1d1d' : '#fecaca'}; border-radius: 8px; cursor: pointer; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s;" onmouseover="this.style.background='#ef4444';this.style.color='white'" onmouseout="this.style.background='${isDark ? '#450a0a' : '#fef2f2'}';this.style.color='#ef4444'"><i class="fas fa-trash" style="font-size: 11px;"></i> Eliminar</button>
                                            </div>
                                        </td>
                                    </tr>`;
                                }).join('')}</tbody>
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
    
    modalesDiv.innerHTML = `
        <div id="modalNuevaTarifa" class="modal-overlay" style="display: flex; position: fixed; z-index: 10001; backdrop-filter: blur(4px);">
            <div class="modal-content modal-premium" style="max-width: 500px; padding: 0; overflow: hidden; width: 95%;">
                <!-- Header Premium -->
                <div class="modal-header-premium" style="background: linear-gradient(135deg, #10b981, #059669); padding: 25px 30px; text-align: left; position: relative;">
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <div style="background: rgba(255,255,255,0.2); width: 45px; height: 45px; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-plus-circle" style="color: #fff; font-size: 1.5rem;"></i>
                        </div>
                        <div>
                            <h3 style="margin: 0; color: #fff; font-size: 1.4rem; font-weight: 700; letter-spacing: -0.5px;">Nueva Tarifa</h3>
                            <p class="modal-subtitulo" style="margin: 3px 0 0 0; color: rgba(255,255,255,0.85); font-size: 0.9rem;">Cree una nueva tarifa personalizada para sus productos</p>
                        </div>
                    </div>
                    <button class="modal-close-btn" onclick="cerrarModal('modalNuevaTarifa')" 
                        style="position: absolute; top: 25px; right: 25px; background: rgba(255,255,255,0.15); border: none; color: white; width: 32px; height: 32px; border-radius: 10px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s;">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div style="padding: 30px; background: var(--bg-panel);">
                    <div style="display: flex; flex-direction: column; gap: 20px;">
                        <div class="ver-prod-item-premium">
                            <label style="display: block; font-size: 0.75rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase; margin-bottom: 8px;">Nombre de la Tarifa <span style="color:#ef4444">*</span></label>
                            <div style="display: flex; align-items: center; gap: 12px; background: var(--bg-main); padding: 5px 15px; border-radius: 10px; border: 1px solid var(--border-main);">
                                <i class="fas fa-tag" style="color: #10b981; width: 16px;"></i>
                                <input type="text" id="nuevaTarifaNombre" placeholder="Ej: Tarifa VIP" style="flex: 1; padding: 10px 0; border: none; background: transparent; outline: none; font-size: 0.95rem; color: var(--text-main);" required>
                            </div>
                        </div>

                        <div class="ver-prod-item-premium">
                            <label style="display: block; font-size: 0.75rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase; margin-bottom: 8px;">Descripción</label>
                            <div style="display: flex; align-items: flex-start; gap: 12px; background: var(--bg-main); padding: 12px 15px; border-radius: 10px; border: 1px solid var(--border-main);">
                                <i class="fas fa-align-left" style="color: #8b5cf6; width: 16px; margin-top: 4px;"></i>
                                <textarea id="nuevaTarifaDescripcion" rows="3" placeholder="Breve descripción de la tarifa..." style="flex: 1; border: none; background: transparent; outline: none; font-size: 0.95rem; color: var(--text-main); resize: vertical; min-height: 80px;"></textarea>
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div class="ver-prod-item-premium">
                                <label style="display: block; font-size: 0.75rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase; margin-bottom: 8px;">Descuento (%)</label>
                                <div style="display: flex; align-items: center; gap: 12px; background: var(--bg-main); padding: 5px 15px; border-radius: 10px; border: 1px solid var(--border-main);">
                                    <i class="fas fa-percent" style="color: #f59e0b; width: 16px;"></i>
                                    <input type="number" id="nuevaTarifaDescuento" step="0.0001" min="0" max="100" value="0" oninput="validar4Decimales(this)" style="flex: 1; padding: 10px 0; border: none; background: transparent; outline: none; font-size: 0.95rem; color: var(--text-main);">
                                </div>
                            </div>
                            <div class="ver-prod-item-premium" style="display: flex; flex-direction: column; justify-content: center;">
                                <label style="display: flex; align-items: center; cursor: pointer; gap: 10px; padding: 10px; background: var(--bg-main); border-radius: 10px; border: 1px solid var(--border-main); height: 100%;">
                                    <input type="checkbox" id="nuevaTarifaRequiereCliente" style="width: 20px; height: 20px; cursor: pointer; accent-color: #10b981;">
                                    <span style="font-size: 0.85rem; font-weight: 600; color: var(--text-main);">Requiere Cliente</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div style="padding: 20px 30px; background: var(--bg-panel); border-top: 1px solid var(--border-main); display: flex; justify-content: flex-end; gap: 12px;">
                    <button class="btn-modal-cancelar" onclick="cerrarModal('modalNuevaTarifa')" 
                        style="margin: 0; padding: 12px 25px; border-radius: 10px; font-weight: 600; background: var(--bg-secondary); color: var(--text-muted); border: 1px solid var(--border-main); cursor: pointer; transition: all 0.2s;">
                        <i class="fas fa-times" style="margin-right: 8px;"></i> Cancelar
                    </button>
                    <button class="btn-exito" onclick="guardarNuevaTarifa()" 
                        style="margin: 0; padding: 12px 30px; border-radius: 10px; font-weight: 600; background: #10b981; color: #fff; border: none; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-save"></i> Guardar Tarifa
                    </button>
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
    
    modalesDiv.innerHTML = `
        <div id="modalEditarTarifa" class="modal-overlay" style="display: flex; position: fixed; z-index: 10001; backdrop-filter: blur(4px);">
            <div class="modal-content modal-premium" style="max-width: 500px; padding: 0; overflow: hidden; width: 95%;">
                <!-- Header Premium -->
                <div class="modal-header-premium" style="background: linear-gradient(135deg, #6366f1, #4f46e5); padding: 25px 30px; text-align: left; position: relative;">
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <div style="background: rgba(255,255,255,0.2); width: 45px; height: 45px; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-pen-fancy" style="color: #fff; font-size: 1.5rem;"></i>
                        </div>
                        <div>
                            <h3 style="margin: 0; color: #fff; font-size: 1.4rem; font-weight: 700; letter-spacing: -0.5px;">Editar Tarifa</h3>
                            <p class="modal-subtitulo" style="margin: 3px 0 0 0; color: rgba(255,255,255,0.85); font-size: 0.9rem;">Modifique los parámetros de la tarifa seleccionada</p>
                        </div>
                    </div>
                    <button class="modal-close-btn" onclick="cerrarModal('modalEditarTarifa')" 
                        style="position: absolute; top: 25px; right: 25px; background: rgba(255,255,255,0.15); border: none; color: white; width: 32px; height: 32px; border-radius: 10px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s;">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div style="padding: 30px; background: var(--bg-panel);">
                    <div style="display: flex; flex-direction: column; gap: 20px;">
                        <input type="hidden" id="editarTarifaId" value="${id}">
                        
                        <div class="ver-prod-item-premium">
                            <label style="display: block; font-size: 0.75rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase; margin-bottom: 8px;">Nombre de la Tarifa <span style="color:#ef4444">*</span></label>
                            <div style="display: flex; align-items: center; gap: 12px; background: var(--bg-main); padding: 5px 15px; border-radius: 10px; border: 1px solid var(--border-main);">
                                <i class="fas fa-tag" style="color: #6366f1; width: 16px;"></i>
                                <input type="text" id="editarTarifaNombre" value="${nombre}" placeholder="Ej: Tarifa VIP" style="flex: 1; padding: 10px 0; border: none; background: transparent; outline: none; font-size: 0.95rem; color: var(--text-main);" required>
                            </div>
                        </div>

                        <div class="ver-prod-item-premium">
                            <label style="display: block; font-size: 0.75rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase; margin-bottom: 8px;">Descripción</label>
                            <div style="display: flex; align-items: flex-start; gap: 12px; background: var(--bg-main); padding: 12px 15px; border-radius: 10px; border: 1px solid var(--border-main);">
                                <i class="fas fa-align-left" style="color: #8b5cf6; width: 16px; margin-top: 4px;"></i>
                                <textarea id="editarTarifaDescripcion" rows="3" placeholder="Breve descripción de la tarifa..." style="flex: 1; border: none; background: transparent; outline: none; font-size: 0.95rem; color: var(--text-main); resize: vertical; min-height: 80px;">${descripcion}</textarea>
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div class="ver-prod-item-premium">
                                <label style="display: block; font-size: 0.75rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase; margin-bottom: 8px;">Descuento (%)</label>
                                <div style="display: flex; align-items: center; gap: 12px; background: var(--bg-main); padding: 5px 15px; border-radius: 10px; border: 1px solid var(--border-main);">
                                    <i class="fas fa-percent" style="color: #f59e0b; width: 16px;"></i>
                                    <input type="number" id="editarTarifaDescuento" step="0.01" min="0" max="100" value="${descuento}" style="flex: 1; padding: 10px 0; border: none; background: transparent; outline: none; font-size: 0.95rem; color: var(--text-main);">
                                </div>
                            </div>
                            <div class="ver-prod-item-premium" style="display: flex; flex-direction: column; justify-content: center;">
                                <label style="display: flex; align-items: center; cursor: pointer; gap: 10px; padding: 10px; background: var(--bg-main); border-radius: 10px; border: 1px solid var(--border-main); height: 100%;">
                                    <input type="checkbox" id="editarTarifaRequiereCliente" ${requiereCliente ? 'checked' : ''} style="width: 20px; height: 20px; cursor: pointer; accent-color: #6366f1;">
                                    <span style="font-size: 0.85rem; font-weight: 600; color: var(--text-main);">Requiere Cliente</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div style="padding: 20px 30px; background: var(--bg-panel); border-top: 1px solid var(--border-main); display: flex; justify-content: flex-end; gap: 12px;">
                    <button class="btn-modal-cancelar" onclick="cerrarModal('modalEditarTarifa')" 
                        style="margin: 0; padding: 12px 25px; border-radius: 10px; font-weight: 600; background: var(--bg-secondary); color: var(--text-muted); border: 1px solid var(--border-main); cursor: pointer; transition: all 0.2s;">
                        <i class="fas fa-times" style="margin-right: 8px;"></i> Cancelar
                    </button>
                    <button class="btn-exito" onclick="guardarEditarTarifa()" 
                        style="margin: 0; padding: 12px 30px; border-radius: 10px; font-weight: 600; background: #6366f1; color: #fff; border: none; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>
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
            <table class="admin-tabla" style="width:100%; border-collapse: collapse;">
                <thead>
                    <tr>
                        <th style="padding: 15px; text-align: center; width: 60px;">ID</th>
                        <th style="padding: 15px; text-align: left;">Fecha Programada</th>
                        <th style="padding: 15px; text-align: center;">Productos</th>
                        <th style="padding: 15px; text-align: center;">Estado</th>
                        <th style="padding: 15px; text-align: center; width: 120px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>`;

                data.batches.forEach(b => {
                    const isPendiente = b.estado === 'pendiente';
                    const statusBadge = isPendiente
                        ? '<span class="admin-badge" style="background: #fef3c7; color: #92400e; padding: 5px 12px; border-radius: 20px; font-weight: 700; font-size: 0.75rem;">PENDIENTE</span>'
                        : '<span class="admin-badge" style="background: #d1fae5; color: #065f46; padding: 5px 12px; border-radius: 20px; font-weight: 700; font-size: 0.75rem;">APLICADO</span>';

                    const fecha = new Date(b.fecha_programada).toLocaleString('es-ES', {
                        day: '2-digit', month: '2-digit', year: 'numeric',
                        hour: '2-digit', minute: '2-digit'
                    });

                    html += `
                <tr>
                    <td style="padding: 12px; text-align: center; font-weight: 600; color: var(--text-muted);">#${b.id}</td>
                    <td style="padding: 12px; font-weight: 500;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <i class="far fa-calendar-alt" style="color: #6366f1;"></i>
                            ${fecha}
                        </div>
                    </td>
                    <td style="padding: 12px; text-align: center;">
                        <span style="background: #eef2ff; color: #4f46e5; padding: 2px 10px; border-radius: 6px; font-weight: 600; font-size: 0.85rem;">
                            ${b.total_productos} ítems
                        </span>
                    </td>
                    <td style="padding: 12px; text-align: center;">${statusBadge}</td>
                    <td style="padding: 12px; text-align: center;">
                        <div style="display: flex; justify-content: center; gap: 8px;">
                            <button onclick="verDetalleBatchTarifas(${b.id})" class="btn-admin-accion btn-ver" title="Ver Detalles">
                                <i class="fas fa-eye"></i>
                            </button>
                            ${isPendiente ? `
                                <button onclick="eliminarBatchTarifas(${b.id})" class="btn-admin-accion btn-eliminar" title="Cancelar Programación">
                                    <i class="fas fa-trash-alt"></i>
                                </button>` : ''}
                        </div>
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
                <table class="admin-tabla" style="width:100%; border-collapse: collapse; font-size: 0.85rem;">
                    <thead>
                        <tr>
                            <th style="padding: 12px; text-align: left;">Producto</th>
                            <th style="padding: 12px; text-align: left;">Tarifa</th>
                            <th style="padding: 12px; text-align: right;">P. Anterior</th>
                            <th style="padding: 12px; text-align: right;">P. Nuevo</th>
                            <th style="padding: 12px; text-align: right;">Cambio</th>
                        </tr>
                    </thead>
                    <tbody>`;

                    data.detalles.forEach(d => {
                        const precioAnterior = parseFloat(d.precio_anterior || 0);
                        const precioNuevo = parseFloat(d.precio_nuevo);
                        const diferencia = precioNuevo - precioAnterior;
                        const prec = parseInt(d.decimales ?? 2);
                        
                        const diffPercent = precioAnterior !== 0 ? ((diferencia / precioAnterior) * 100).toFixed(2) : '100';
                        const colorDiff = diferencia > 0 ? '#059669' : (diferencia < 0 ? '#dc2626' : 'var(--text-muted)');
                        const iconDiff = diferencia > 0 ? 'fa-arrow-up' : (diferencia < 0 ? 'fa-arrow-down' : 'fa-equals');

                        html += `
                    <tr>
                        <td style="padding: 10px 15px;">
                            <div style="font-weight: 600; color: var(--text-main);">${d.producto_nombre}</div>
                            <div style="font-size: 0.75rem; color: var(--text-muted);">ID: ${d.id_producto}</div>
                        </td>
                        <td style="padding: 10px 15px;">
                            <span style="background: #f3f4f6; color: #374151; padding: 2px 8px; border-radius: 4px; font-weight: 500; font-size: 0.75rem;">
                                ${d.tarifa_nombre}
                            </span>
                        </td>
                        <td style="padding: 10px 15px; text-align: right; color: var(--text-muted);">${precioAnterior.toFixed(prec)} €</td>
                        <td style="padding: 10px 15px; text-align: right; font-weight: 700; color: var(--text-main);">${precioNuevo.toFixed(prec)} €</td>
                        <td style="padding: 10px 15px; text-align: right;">
                            <div style="color: ${colorDiff}; font-weight: 700; display: flex; align-items: center; justify-content: flex-end; gap: 5px;">
                                <i class="fas ${iconDiff}" style="font-size: 0.7rem;"></i>
                                ${Math.abs(diffPercent)}%
                            </div>
                            <div style="font-size: 0.7rem; color: ${colorDiff}; opacity: 0.8;">
                                ${diferencia >= 0 ? '+' : ''}${diferencia.toFixed(prec)} €
                            </div>
                        </td>
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

function abrirModalProgramarIVA() {
    const nuevoIdIva = document.getElementById('nuevoIVA').value;
    const cambioId = document.getElementById('ivaProgramado')?.dataset?.cambioId;

    if (!nuevoIdIva && !cambioId) {
        alert('Por favor, selecciona un tipo de IVA');
        return;
    }

    if (nuevoIdIva && document.getElementById('ivaProgramado')) {
        document.getElementById('ivaProgramado').value = nuevoIdIva;
    }

    const selectIva = document.getElementById('nuevoIVA');
    if (selectIva) {
        const nombreIva = selectIva.options[selectIva.selectedIndex]?.textContent;
        const nombreEl = document.getElementById('ivaProgramadoNombre');
        if (nombreEl) nombreEl.textContent = nombreIva;
    }

    if (!cambioId) {
        const ahora = new Date();
        ahora.setMinutes(ahora.getMinutes() - ahora.getTimezoneOffset());
        const fechaEl = document.getElementById('fechaProgramada');
        if (fechaEl) fechaEl.min = ahora.toISOString().slice(0, 16);
    }

    const modal = document.getElementById('modalProgramarIVA');
    if (modal) modal.style.display = 'flex';
}

function abrirModalVerCambiosProgramados() {
    const contenedor = document.getElementById('listaCambiosProgramadosIVA');
    if (contenedor) {
        contenedor.innerHTML = `
            <div style="padding: 40px; text-align: center; color: #64748b;">
                <i class="fas fa-spinner fa-spin" style="font-size: 2rem; margin-bottom: 10px;"></i>
                <p>Cargando tareas...</p>
            </div>`;
    }

    fetch('api/productos.php?accion=obtener_cambios_iva_programados')
        .then(res => res.json())
        .then(data => {
            if (!contenedor) return;

            if (!data.cambios || data.cambios.length === 0) {
                contenedor.innerHTML = `
                    <div style="padding: 60px 20px; text-align: center; color: #94a3b8;">
                        <i class="fas fa-calendar-times" style="font-size: 3.5rem; opacity: 0.2; margin-bottom: 15px; display: block;"></i>
                        <p style="font-size: 1.1rem; font-weight: 500;">No hay cambios programados</p>
                        <p style="font-size: 0.9rem; opacity: 0.7;">Los cambios de IVA que programes aparecerán aquí.</p>
                    </div>`;
            } else {
                let html = `
                    <table class="admin-tabla" style="width: 100%; margin-bottom: 0; border: none;">
                        <thead style="background: #f8fafc; position: sticky; top: 0; z-index: 10;">
                            <tr>
                                <th style="padding: 15px 20px; text-align: left; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.025em; color: #64748b;">Fecha Ejecución</th>
                                <th style="padding: 15px 20px; text-align: center; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.025em; color: #64748b;">Nuevo IVA</th>
                                <th style="padding: 15px 20px; text-align: center; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.025em; color: #64748b;">Estado</th>
                                <th style="padding: 15px 20px; text-align: right; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.025em; color: #64748b;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody style="background: #fff;">`;

                data.cambios.forEach(cambio => {
                    const fecha = new Date(cambio.fecha_programada).toLocaleString('es-ES', { 
                        day: '2-digit', month: '2-digit', year: 'numeric', 
                        hour: '2-digit', minute: '2-digit' 
                    });
                    
                    // Badge de estado
                    let statusBadge = '';
                    if (cambio.estado === 'aplicado') {
                        statusBadge = '<span style="background: #dcfce7; color: #15803d; padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase;"><i class="fas fa-check-circle" style="margin-right: 5px;"></i>Aplicado</span>';
                    } else if (cambio.estado === 'pendiente') {
                        statusBadge = '<span style="background: #fef3c7; color: #92400e; padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase;"><i class="fas fa-clock" style="margin-right: 5px;"></i>Pendiente</span>';
                    } else {
                        statusBadge = `<span style="background: #f1f5f9; color: #475569; padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase;">${cambio.estado}</span>`;
                    }

                    const esPendiente = cambio.estado === 'pendiente';
                    
                    html += `
                        <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.2s;">
                            <td style="padding: 15px 20px; font-weight: 600; color: #1e293b;">${fecha}</td>
                            <td style="padding: 15px 20px; text-align: center;">
                                <span style="background: #e0f2fe; color: #0369a1; padding: 4px 10px; border-radius: 8px; font-weight: 800; font-size: 1rem;">${cambio.iva_porcentaje}%</span>
                            </td>
                            <td style="padding: 15px 20px; text-align: center;">${statusBadge}</td>
                            <td style="padding: 15px 20px; text-align: right;">
                                <div style="display: flex; justify-content: flex-end; gap: 8px;">
                                    <button class="btn-admin-accion btn-ver" onclick="verDetallesCambioIVA(${cambio.id})" title="Ver Detalles"
                                        style="width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; background: #f1f5f9; color: #6366f1; border: none; cursor: pointer; transition: all 0.2s;">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    ${esPendiente ? `
                                        <button class="btn-admin-accion btn-editar" onclick="editarCambioProgramadoIVA(${cambio.id})" title="Editar"
                                            style="width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; background: #f1f5f9; color: #f59e0b; border: none; cursor: pointer; transition: all 0.2s;">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn-admin-accion btn-eliminar" onclick="eliminarCambioProgramadoIVA(${cambio.id})" title="Eliminar"
                                            style="width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; background: #fef2f2; color: #ef4444; border: none; cursor: pointer; transition: all 0.2s;">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    ` : ''}
                                </div>
                            </td>
                        </tr>`;
                });
                html += '</tbody></table>';
                contenedor.innerHTML = html;
            }
            const modal = document.getElementById('modalVerCambiosProgramadosIVA');
            if (modal) modal.style.display = 'flex';
        });
}

function eliminarCambioProgramadoIVA(id) {
    if (!confirm('¿Estás seguro de que deseas eliminar este cambio programado?')) return;
    fetch('api/productos.php?accion=eliminar_cambio_iva_programado&id=' + id, { method: 'DELETE' })
        .then(res => res.json())
        .then(data => {
            if (data.ok) { alert('Cambio eliminado'); abrirModalVerCambiosProgramados(); }
            else alert('Error: ' + data.error);
        });
}

function verDetallesCambioIVA(id) {
    const infoEl = document.getElementById('detallesCambioIVAInfo');
    const tablaEl = document.getElementById('detallesCambioIVATabla');
    
    if (infoEl) infoEl.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 20px;"><i class="fas fa-spinner fa-spin"></i> Cargando información...</div>';
    if (tablaEl) tablaEl.innerHTML = '';

    fetch('api/productos.php?accion=obtener_cambio_iva_programado&id=' + id)
        .then(res => res.json())
        .then(data => {
            if (data.cambio) {
                const cambio = data.cambio;
                const fecha = new Date(cambio.fecha_programada).toLocaleString('es-ES', { 
                    day: '2-digit', month: '2-digit', year: 'numeric', 
                    hour: '2-digit', minute: '2-digit' 
                });

                let statusBadge = '';
                if (cambio.estado === 'aplicado') {
                    statusBadge = '<span style="background: #dcfce7; color: #15803d; padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase;">Aplicado</span>';
                } else if (cambio.estado === 'pendiente') {
                    statusBadge = '<span style="background: #fef3c7; color: #92400e; padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase;">Pendiente</span>';
                }

                if (infoEl) {
                    infoEl.innerHTML = `
                        <div style="display: flex; flex-direction: column; gap: 5px;">
                            <span style="font-size: 0.75rem; font-weight: 700; color: #64748b; text-transform: uppercase;">Fecha Ejecución</span>
                            <span style="font-size: 1rem; font-weight: 600; color: #1e293b;"><i class="far fa-calendar-alt" style="margin-right: 8px; color: #7c3aed;"></i>${fecha}</span>
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 5px; border-left: 1px solid #e2e8f0; padding-left: 20px;">
                            <span style="font-size: 0.75rem; font-weight: 700; color: #64748b; text-transform: uppercase;">Nuevo IVA</span>
                            <span style="font-size: 1.1rem; font-weight: 800; color: #7c3aed;">${cambio.iva_porcentaje}%</span>
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 5px; border-left: 1px solid #e2e8f0; padding-left: 20px;">
                            <span style="font-size: 0.75rem; font-weight: 700; color: #64748b; text-transform: uppercase;">Estado</span>
                            <div style="margin-top: 2px;">${statusBadge}</div>
                        </div>`;
                }

                fetch('api/productos.php?accion=obtener_productos_cambio_iva&id=' + id)
                    .then(res => res.json())
                    .then(dataProd => {
                        if (dataProd.productos?.length > 0) {
                            let tablaHTML = `
                                <table class="admin-tabla" style="width: 100%; margin-bottom: 0; border: none;">
                                    <thead style="background: #f8fafc; position: sticky; top: 0; z-index: 10;">
                                        <tr>
                                            <th style="padding: 12px 20px; text-align: left; font-size: 0.75rem; text-transform: uppercase; color: #64748b;">Producto</th>
                                            <th style="padding: 12px 20px; text-align: center; font-size: 0.75rem; text-transform: uppercase; color: #64748b;">IVA Anterior</th>
                                            <th style="padding: 12px 20px; text-align: center; font-size: 0.75rem; text-transform: uppercase; color: #64748b;"></th>
                                            <th style="padding: 12px 20px; text-align: center; font-size: 0.75rem; text-transform: uppercase; color: #64748b;">IVA Nuevo</th>
                                        </tr>
                                    </thead>
                                    <tbody>`;
                            
                            dataProd.productos.forEach(p => {
                                tablaHTML += `
                                    <tr style="border-bottom: 1px solid #f1f5f9;">
                                        <td style="padding: 12px 20px;">
                                            <div style="font-weight: 600; color: #1e293b;">${p.nombre}</div>
                                            <div style="font-size: 0.7rem; color: #94a3b8;">ID: ${p.id}</div>
                                        </td>
                                        <td style="padding: 12px 20px; text-align: center; color: #64748b; font-weight: 500;">
                                            ${p.iva_anterior}%
                                        </td>
                                        <td style="padding: 12px 20px; text-align: center; color: #94a3b8;">
                                            <i class="fas fa-arrow-right"></i>
                                        </td>
                                        <td style="padding: 12px 20px; text-align: center;">
                                            <span style="font-weight: 800; color: #7c3aed; background: #f5f3ff; padding: 4px 10px; border-radius: 6px;">
                                                ${p.iva_nuevo}%
                                            </span>
                                        </td>
                                    </tr>`;
                            });
                            tablaHTML += '</tbody></table>';
                            if (tablaEl) tablaEl.innerHTML = tablaHTML;
                        } else {
                            if (tablaEl) tablaEl.innerHTML = '<div style="padding: 40px; text-align: center; color: #94a3b8;"><i class="fas fa-box-open" style="font-size: 2.5rem; margin-bottom: 10px; display: block; opacity: 0.3;"></i> No hay productos afectados</div>';
                        }
                    });
                abrirModal('modalVerDetallesCambioIVA');
            }
        });
}

function editarCambioProgramadoIVA(id) {
    cerrarModal('modalVerCambiosProgramadosIVA');
    fetch('api/productos.php?accion=obtener_cambio_iva_programado&id=' + id)
        .then(res => res.json())
        .then(data => {
            if (data.cambio) {
                const cambio = data.cambio;
                const selectIva = document.getElementById('nuevoIVA');
                if (selectIva) selectIva.value = cambio.iva_id;
                const nombreEl = document.getElementById('ivaProgramadoNombre');
                if (nombreEl && selectIva) nombreEl.textContent = selectIva.options[selectIva.selectedIndex]?.textContent;
                const fechaEl = document.getElementById('fechaProgramada');
                if (fechaEl) {
                    const fecha = new Date(cambio.fecha_programada);
                    fecha.setMinutes(fecha.getMinutes() - fecha.getTimezoneOffset());
                    fechaEl.value = fecha.toISOString().slice(0, 16);
                }
                if (cambio.productos_excluidos) {
                    productosExcluidos = cambio.productos_excluidos.split(',').map(Number);
                }
                const ivaEl = document.getElementById('ivaProgramado');
                if (ivaEl) {
                    ivaEl.value = cambio.iva_id;
                    ivaEl.dataset.cambioId = id;
                }
                abrirModalProgramarIVA();
            }
        });
}

function programarCambioIVA() {
    const nuevoIdIva = document.getElementById('ivaProgramado')?.value;
    const fechaHora = document.getElementById('fechaProgramada')?.value;
    const productosExcluir = productosExcluidos.join(',');
    const cambioId = document.getElementById('ivaProgramado')?.dataset?.cambioId;

    if (!fechaHora) { alert('Selecciona fecha y hora'); return; }
    const fechaObj = new Date(fechaHora);
    const ahora = new Date();
    if (fechaObj <= ahora && !cambioId) { alert('La fecha debe ser posterior'); return; }

    if (cambioId) {
        fetch('api/productos.php?accion=actualizar_cambio_iva_programado&id=' + cambioId, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'iva_id=' + nuevoIdIva + '&fecha_programada=' + encodeURIComponent(fechaHora) + '&productos_excluidos=' + productosExcluir
        }).then(res => res.json()).then(data => {
            if (data.error) alert(data.error);
            else { alert('Cambio actualizado'); cerrarModal('modalProgramarIVA'); productosExcluidos = []; }
        });
    } else {
        fetch('api/productos.php?accion=programar_cambio_iva', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'iva_id=' + nuevoIdIva + '&fecha_programada=' + encodeURIComponent(fechaHora) + '&productos_excluidos=' + productosExcluir
        }).then(res => res.json()).then(data => {
            if (data.error) alert(data.error);
            else { alert('Cambio programado'); cerrarModal('modalProgramarIVA'); productosExcluidos = []; }
        });
    }
}

function abrirModalProgramarAjustePrecios() {
    const ajusteId = document.getElementById('ajusteProgramadoPorcentaje')?.dataset?.ajusteId;
    const porcentajeInput = document.getElementById('porcentajeAjuste')?.value;

    if (!ajusteId && porcentajeInput && document.getElementById('ajusteProgramadoPorcentaje')) {
        document.getElementById('ajusteProgramadoPorcentaje').value = porcentajeInput;
    }

    const totalProductos = productosPrevisualizacionPrecios ? productosPrevisualizacionPrecios.length : 0;
    const productosAfectados = totalProductos - productosExcluidos.length;
    const countEl = document.getElementById('ajusteProgramadoProductosCount');
    if (countEl) countEl.textContent = productosAfectados;

    if (!ajusteId) {
        const ahora = new Date();
        ahora.setMinutes(ahora.getMinutes() - ahora.getTimezoneOffset());
        const fechaEl = document.getElementById('fechaProgramadaAjuste');
        if (fechaEl) fechaEl.min = ahora.toISOString().slice(0, 16);
    }

    const modal = document.getElementById('modalProgramarAjustePrecios');
    if (modal) modal.style.display = 'flex';
}

function abrirModalVerAjustesProgramados() {
    const contenedor = document.getElementById('listaAjustesProgramadosPrecios');
    if (contenedor) {
        contenedor.innerHTML = `
            <div style="padding: 40px; text-align: center; color: #64748b;">
                <i class="fas fa-spinner fa-spin" style="font-size: 2rem; margin-bottom: 10px;"></i>
                <p>Cargando ajustes...</p>
            </div>`;
    }

    fetch('api/productos.php?accion=obtener_ajustes_precios_programados')
        .then(res => res.json())
        .then(data => {
            if (!contenedor) return;

            if (!data.ajustes || data.ajustes.length === 0) {
                contenedor.innerHTML = `
                    <div style="padding: 60px 20px; text-align: center; color: #94a3b8;">
                        <i class="fas fa-calendar-times" style="font-size: 3.5rem; opacity: 0.2; margin-bottom: 15px; display: block;"></i>
                        <p style="font-size: 1.1rem; font-weight: 500;">No hay ajustes programados</p>
                        <p style="font-size: 0.9rem; opacity: 0.7;">Los ajustes que programes aparecerán aquí.</p>
                    </div>`;
            } else {
                let html = `
                    <table class="admin-tabla" style="width: 100%; margin-bottom: 0; border: none;">
                        <thead style="background: #f8fafc; position: sticky; top: 0; z-index: 10;">
                            <tr>
                                <th style="padding: 15px 20px; text-align: left; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.025em; color: #64748b;">Fecha Ejecución</th>
                                <th style="padding: 15px 20px; text-align: center; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.025em; color: #64748b;">Ajuste (%)</th>
                                <th style="padding: 15px 20px; text-align: center; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.025em; color: #64748b;">Estado</th>
                                <th style="padding: 15px 20px; text-align: right; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.025em; color: #64748b;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody style="background: #fff;">`;

                data.ajustes.forEach(ajuste => {
                    const fecha = new Date(ajuste.fecha_programada).toLocaleString('es-ES', { 
                        day: '2-digit', month: '2-digit', year: 'numeric', 
                        hour: '2-digit', minute: '2-digit' 
                    });
                    const signo = ajuste.porcentaje > 0 ? '+' : '';
                    const colorPct = ajuste.porcentaje > 0 ? '#10b981' : '#ef4444';
                    
                    // Badge de estado
                    let statusBadge = '';
                    if (ajuste.estado === 'pendiente') {
                        statusBadge = '<span style="background: #e0e7ff; color: #4338ca; padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase;"><i class="fas fa-clock" style="margin-right: 5px;"></i>Pendiente</span>';
                    } else if (ajuste.estado === 'ejecutado') {
                        statusBadge = '<span style="background: #dcfce7; color: #15803d; padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase;"><i class="fas fa-check-circle" style="margin-right: 5px;"></i>Ejecutado</span>';
                    } else {
                        statusBadge = `<span style="background: #f1f5f9; color: #475569; padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase;">${ajuste.estado}</span>`;
                    }

                    const esPendiente = ajuste.estado === 'pendiente';
                    
                    html += `
                        <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.2s;">
                            <td style="padding: 15px 20px; font-weight: 600; color: #1e293b;">${fecha}</td>
                            <td style="padding: 15px 20px; text-align: center;">
                                <span style="color: ${colorPct}; font-weight: 800; font-size: 1.1rem;">${signo}${ajuste.porcentaje}%</span>
                            </td>
                            <td style="padding: 15px 20px; text-align: center;">${statusBadge}</td>
                            <td style="padding: 15px 20px; text-align: right;">
                                <div style="display: flex; justify-content: flex-end; gap: 8px;">
                                    <button class="btn-admin-accion btn-ver" onclick="verDetallesAjustePrecios(${ajuste.id})" title="Ver Detalles"
                                        style="width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; background: #f1f5f9; color: #6366f1; border: none; cursor: pointer; transition: all 0.2s;">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    ${esPendiente ? `
                                        <button class="btn-admin-accion btn-editar" onclick="editarAjusteProgramadoPrecios(${ajuste.id})" title="Editar"
                                            style="width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; background: #f1f5f9; color: #f59e0b; border: none; cursor: pointer; transition: all 0.2s;">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn-admin-accion btn-eliminar" onclick="eliminarAjusteProgramadoPrecios(${ajuste.id})" title="Eliminar"
                                            style="width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; background: #fef2f2; color: #ef4444; border: none; cursor: pointer; transition: all 0.2s;">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    ` : ''}
                                </div>
                            </td>
                        </tr>`;
                });
                html += '</tbody></table>';
                contenedor.innerHTML = html;
            }
            const modal = document.getElementById('modalVerAjustesProgramadosPrecios');
            if (modal) modal.style.display = 'flex';
        });
}

function eliminarAjusteProgramadoPrecios(id) {
    if (!confirm('¿Eliminar este ajuste?')) return;
    fetch('api/productos.php?accion=eliminar_ajuste_precios_programado&id=' + id, { method: 'DELETE' })
        .then(res => res.json())
        .then(data => { if (data.ok) abrirModalVerAjustesProgramados(); else alert(data.error); });
}

function verDetallesAjustePrecios(id) {
    const infoEl = document.getElementById('detallesAjustePreciosInfo');
    const tablaEl = document.getElementById('detallesAjustePreciosTabla');
    
    if (infoEl) infoEl.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 20px;"><i class="fas fa-spinner fa-spin"></i> Cargando información...</div>';
    if (tablaEl) tablaEl.innerHTML = '';

    fetch('api/productos.php?accion=obtener_ajuste_precios_programado&id=' + id)
        .then(res => res.json())
        .then(data => {
            if (data.ajuste) {
                const ajuste = data.ajuste;
                const fecha = new Date(ajuste.fecha_programada).toLocaleString('es-ES', { 
                    day: '2-digit', month: '2-digit', year: 'numeric', 
                    hour: '2-digit', minute: '2-digit' 
                });
                const signo = ajuste.porcentaje > 0 ? '+' : '';
                const colorPct = ajuste.porcentaje > 0 ? '#10b981' : '#ef4444';

                let statusBadge = '';
                if (ajuste.estado === 'pendiente') {
                    statusBadge = '<span style="background: #e0e7ff; color: #4338ca; padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase;">Pendiente</span>';
                } else if (ajuste.estado === 'ejecutado') {
                    statusBadge = '<span style="background: #dcfce7; color: #15803d; padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase;">Ejecutado</span>';
                }

                if (infoEl) {
                    infoEl.innerHTML = `
                        <div style="display: flex; flex-direction: column; gap: 5px;">
                            <span style="font-size: 0.75rem; font-weight: 700; color: #64748b; text-transform: uppercase;">Fecha Programada</span>
                            <span style="font-size: 1rem; font-weight: 600; color: #1e293b;"><i class="far fa-calendar-alt" style="margin-right: 8px; color: #6366f1;"></i>${fecha}</span>
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 5px; border-left: 1px solid #e2e8f0; padding-left: 20px;">
                            <span style="font-size: 0.75rem; font-weight: 700; color: #64748b; text-transform: uppercase;">Porcentaje Ajuste</span>
                            <span style="font-size: 1.1rem; font-weight: 800; color: ${colorPct};">${signo}${ajuste.porcentaje}%</span>
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 5px; border-left: 1px solid #e2e8f0; padding-left: 20px;">
                            <span style="font-size: 0.75rem; font-weight: 700; color: #64748b; text-transform: uppercase;">Estado Actual</span>
                            <div style="margin-top: 2px;">${statusBadge}</div>
                        </div>`;
                }

                fetch('api/productos.php?accion=obtener_productos_ajuste_precios&id=' + id)
                    .then(res => res.json())
                    .then(dataProd => {
                        if (dataProd.productos?.length > 0) {
                            let tablaHTML = `
                                <table class="admin-tabla" style="width: 100%; margin-bottom: 0; border: none;">
                                    <thead style="background: #f8fafc; position: sticky; top: 0; z-index: 10;">
                                        <tr>
                                            <th style="padding: 12px 20px; text-align: left; font-size: 0.75rem; text-transform: uppercase; color: #64748b;">Producto</th>
                                            <th style="padding: 12px 20px; text-align: right; font-size: 0.75rem; text-transform: uppercase; color: #64748b;">Precio Anterior</th>
                                            <th style="padding: 12px 20px; text-align: center; font-size: 0.75rem; text-transform: uppercase; color: #64748b;"></th>
                                            <th style="padding: 12px 20px; text-align: right; font-size: 0.75rem; text-transform: uppercase; color: #64748b;">Nuevo Precio</th>
                                        </tr>
                                    </thead>
                                    <tbody>`;
                            
                            dataProd.productos.forEach(p => {
                                tablaHTML += `
                                    <tr style="border-bottom: 1px solid #f1f5f9;">
                                        <td style="padding: 12px 20px;">
                                            <div style="font-weight: 600; color: #1e293b;">${p.nombre}</div>
                                            <div style="font-size: 0.7rem; color: #94a3b8;">ID: ${p.id}</div>
                                        </td>
                                        <td style="padding: 12px 20px; text-align: right; color: #64748b; font-weight: 500;">
                                            ${parseFloat(p.precio_anterior).toFixed(2)}€
                                        </td>
                                        <td style="padding: 12px 20px; text-align: center; color: #94a3b8;">
                                            <i class="fas fa-long-arrow-alt-right"></i>
                                        </td>
                                        <td style="padding: 12px 20px; text-align: right;">
                                            <span style="font-weight: 800; color: #1e293b; background: #f1f5f9; padding: 4px 8px; border-radius: 6px;">
                                                ${parseFloat(p.precio_nuevo).toFixed(2)}€
                                            </span>
                                        </td>
                                    </tr>`;
                            });
                            tablaHTML += '</tbody></table>';
                            if (tablaEl) tablaEl.innerHTML = tablaHTML;
                        } else {
                            if (tablaEl) tablaEl.innerHTML = '<div style="padding: 40px; text-align: center; color: #94a3b8;"><i class="fas fa-box-open" style="font-size: 2.5rem; margin-bottom: 10px; display: block; opacity: 0.3;"></i> No hay productos afectados</div>';
                        }
                    });
                abrirModal('modalVerDetallesAjustePrecios');
            }
        });
}

function editarAjusteProgramadoPrecios(id) {
    cerrarModal('modalVerAjustesProgramadosPrecios');
    fetch('api/productos.php?accion=obtener_ajuste_precios_programado&id=' + id)
        .then(res => res.json())
        .then(data => {
            if (data.ajuste) {
                const ajuste = data.ajuste;
                const pctEl = document.getElementById('porcentajeAjuste');
                if (pctEl) pctEl.value = ajuste.porcentaje;
                const fechaEl = document.getElementById('fechaProgramadaAjuste');
                if (fechaEl) {
                    const fecha = new Date(ajuste.fecha_programada);
                    fecha.setMinutes(fecha.getMinutes() - fecha.getTimezoneOffset());
                    fechaEl.value = fecha.toISOString().slice(0, 16);
                }
                if (ajuste.productos_excluidos) productosExcluidos = ajuste.productos_excluidos.split(',').map(Number);
                const progEl = document.getElementById('ajusteProgramadoPorcentaje');
                if (progEl) progEl.dataset.ajusteId = id;
                abrirModalProgramarAjustePrecios();
            }
        });
}