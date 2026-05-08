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
    return `
        <tr class="category-row">
            <td class="col-nombre">
                <div class="category-info">
                    <span class="category-name-text">${cat.nombre}</span>
                </div>
            </td>
            <td class="col-productos" style="text-align:center;">
                <span class="count-badge">${cat.num_productos} <small>productos</small></span>
            </td>
            <td class="col-fecha" style="color: #64748b; font-size: 0.85rem;">
                <i class="far fa-calendar-alt" style="margin-right: 6px; opacity: 0.5;"></i> ${fecha}
            </td>
            <td class="col-acciones">
                <div class="actions-group">
                    <button class="action-btn btn-view" onclick="verCategoria(${cat.id})" title="Ver detalles">
                        <i class="fas fa-eye"></i></button>
                    <button class="action-btn btn-edit"
                        onclick="abrirModalEditarCategoria(${cat.id},'${cat.nombre}','${cat.descripcion || ''}')" title="Editar">
                        <i class="fas fa-pen"></i></button>
                    <button class="action-btn btn-delete"
                        onclick="confirmarEliminarCategoria(${cat.id},'${cat.nombre}')" title="Eliminar">
                        <i class="fas fa-trash"></i></button>
                </div>
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
            ${getPremiumHeaderHTML('fa-tags', 'Gestión de Categorías', 'Organice su catálogo de productos por grupos lógicos', 'linear-gradient(135deg, #10b981, #059669)')}
            <div class="admin-tabla-header products-header">
                <div class="header-filters-grid">
                    <div class="filter-main">
                        <div class="search-wrapper">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" id="busquedaCategorias" class="input-modern-search"
                                placeholder="Buscar categorías..."
                                oninput="buscarCategorias()" autocomplete="off"
                                value="${textoBusqueda.replace(/"/g, '&quot;')}">
                        </div>
                    </div>
                    
                    <div class="header-status-info">
                        <span id="totalCategoriasAviso" class="info-tag">0 Categorías</span>
                    </div>

                    <div class="header-actions">
                        <button class="btn-modern btn-primary btn-add-category" onclick="abrirModalNuevaCategoria()">
                            <i class="fas fa-plus"></i>
                            <span>Nueva Categoría</span>
                        </button>
                    </div>
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

            if (!filtrado.length) { contenedor.innerHTML = adminTablaHeaderHTML + '<p class="sin-productos">No hay categorías que coincidan con la búsqueda.</p>'; return; }

            const totalPaginas = Math.ceil(filtrado.length / categoriasPorPagina);
            const pag = filtrado.slice(0, categoriasPorPagina);

            let html = adminTablaHeaderHTML + `
                <div class="admin-tabla-wrapper products-table-wrapper">
                    <table class="admin-tabla">
                        <thead><tr>
                            <th>Nombre de la Categoría</th>
                            <th style="width: 150px; text-align:center;">Nº Productos</th>
                            <th style="width: 180px;">Fecha Creación</th>
                            <th style="width: 150px; text-align:center;">Acciones</th>
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
                    ? `${filtrado.length} Encontrada${filtrado.length !== 1 ? 's' : ''}`
                    : `${data.length} Categoría${data.length !== 1 ? 's' : ''}`;
            }
        })
        .catch(err => { 
            console.error(err);
            contenedor.innerHTML = adminTablaHeaderHTML + '<p class="sin-productos">Error al cargar las categorías.</p>'; 
        });
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
        btnMasivo.classList.remove('active');
        secTipos.style.display = 'block';
        secMasivo.style.display = 'none';
    } else {
        btnMasivo.classList.add('active');
        btnTipos.classList.remove('active');
        secTipos.style.display = 'none';
        secMasivo.style.display = 'block';
    }
}

function switchAjusteSubSeccion(seccion) {
    const tabDirecto = document.getElementById('tabAjusteDirecto');
    const tabProgramados = document.getElementById('tabAjusteProgramados');
    const divDirecto = document.getElementById('ajusteSeccionDirecto');
    const divProgramados = document.getElementById('ajusteSeccionProgramados');
    if (!tabDirecto || !tabProgramados || !divDirecto || !divProgramados) return;

    if (seccion === 'directo') {
        tabDirecto.classList.add('active');
        tabProgramados.classList.remove('active');
        divDirecto.style.display = 'block';
        divProgramados.style.display = 'none';
    } else {
        tabDirecto.classList.remove('active');
        tabProgramados.classList.add('active');
        divDirecto.style.display = 'none';
        divProgramados.style.display = 'block';
        if (typeof cargarAjustesPreciosProgramadosTabla === 'function') {
            cargarAjustesPreciosProgramadosTabla();
        }
    }
}

function mostrarPanelCambiarIVA() {
    productosExcluidos = [];
    const contenedor = document.getElementById('adminContenido');
    seccionActual = 'tarifa-iva';
    adminTablaHeaderHTML = '';

    const opcionesIva = '<option value="">Selecciona un tipo de IVA</option>' +
        tiposIva.map(t => `<option value="${t.id}">${t.porcentaje}% (${t.nombre})</option>`).join('');

    const filasTablaIva = tiposIva.map(t => `
        <tr class="iva-row">
            <td style="text-align:center; font-weight: 700; color: #6366f1;">#${t.id}</td>
            <td>
                <div style="font-weight: 600; color: #374151;">${t.nombre}</div>
            </td>
            <td style="text-align:center;">
                <span class="iva-pct-badge">${t.porcentaje}%</span>
            </td>
            <td style="text-align:center;">
                <div class="actions-group" style="justify-content: center;">
                    <button class="action-btn btn-edit" onclick="editarIva(${t.id},'${t.nombre}',${t.porcentaje})" title="Editar">
                        <i class="fas fa-pen"></i>
                    </button>
                    <button class="action-btn btn-delete" onclick="eliminarIva(${t.id})" title="Eliminar">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        </tr>`).join('');

    contenedor.innerHTML = `
        <div class="premium-panel animate-fade-in">
            <div class="premium-panel-header">
                <div class="header-left">
                    <div class="header-icon-box">
                        <i class="fas fa-percentage"></i>
                    </div>
                    <div>
                        <h2 class="premium-title">Cambiar IVA General</h2>
                        <p class="premium-subtitle">Gestión centralizada de tipos impositivos y actualización masiva</p>
                    </div>
                </div>
                <div class="header-right">
                    <div class="premium-tabs-modern">
                        <button id="tabIvaTipos" onclick="switchIvaSubSeccion('tipos')" class="tab-modern active">
                            <i class="fas fa-tags"></i> <span>Tipos de IVA</span>
                        </button>
                        <button id="tabIvaMasivo" onclick="switchIvaSubSeccion('masivo')" class="tab-modern">
                            <i class="fas fa-magic"></i> <span>Actualización Masiva</span>
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="premium-panel-body">
                <!-- SECCIÓN 1: TABLA DE TIPOS DE IVA -->
                <div id="ivaSeccionTipos" class="iva-content-section active">
                    <div class="iva-grid-container">
                        <div class="iva-info-card">
                            <div class="card-header-flex">
                                <h3><i class="fas fa-list-check"></i> Listado de Tipos</h3>
                                <button onclick="abrirModalNuevoIva()" class="btn-modern btn-primary btn-sm">
                                    <i class="fas fa-plus"></i> Nuevo IVA
                                </button>
                            </div>
                            <div class="modern-table-wrapper" style="margin-top: 15px; border-radius: 12px; border: 1px solid #e5e7eb; overflow: hidden;">
                                <table class="admin-tabla">
                                    <thead>
                                        <tr>
                                            <th style="width: 80px; text-align: center;">ID</th>
                                            <th>Nombre Descriptivo</th>
                                            <th style="text-align: center; width: 140px;">Porcentaje</th>
                                            <th style="text-align: center; width: 140px;">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>${filasTablaIva || '<tr><td colspan="4" class="sin-productos">No hay tipos de IVA definidos</td></tr>'}</tbody>
                                </table>
                            </div>
                        </div>
                        
                        <div class="iva-help-card">
                            <h4><i class="fas fa-info-circle"></i> Ayuda sobre IVA</h4>
                            <p>Los tipos de IVA aquí definidos aparecerán como opciones al crear o editar productos.</p>
                            <div class="help-item">
                                <i class="fas fa-lightbulb"></i>
                                <span>El <b>IVA General</b> (21%) es el estándar para la mayoría de productos.</span>
                            </div>
                            <div class="help-item">
                                <i class="fas fa-lightbulb"></i>
                                <span>El <b>IVA Reducido</b> (10%) se aplica a alimentos y hostelería.</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECCIÓN 2: PANEL DE CAMBIO Y PREVIEW -->
                <div id="ivaSeccionMasivo" class="iva-content-section" style="display: none;">
                    <div class="iva-massive-layout">
                        <!-- Sidebar de configuración -->
                        <div class="massive-config-sidebar">
                            <div class="config-card-premium">
                                <h3><i class="fas fa-sliders-h"></i> Configuración</h3>
                                <p class="config-desc">Selecciona el nuevo IVA para previsualizar el impacto en los precios finales de tus productos.</p>
                                
                                <div class="premium-field-group">
                                    <label>Nuevo IVA a aplicar</label>
                                    <div class="select-wrapper-modern">
                                        <select id="nuevoIVA" class="input-modern" onchange="actualizarPrevisualizacionIVAAuto()">
                                            ${opcionesIva}
                                        </select>
                                        <i class="fas fa-chevron-down select-icon"></i>
                                    </div>
                                </div>
                                
                                <div class="massive-actions-stack">
                                    <button onclick="aplicarCambioIVA()" class="btn-modern btn-success btn-lg btn-full">
                                        <i class="fas fa-check-double"></i> Aplicar Cambio Ahora
                                    </button>
                                    <div class="action-row-split">
                                        <button onclick="abrirModalProgramarIVA()" class="btn-modern btn-outline btn-full">
                                            <i class="fas fa-calendar-plus"></i> Programar
                                        </button>
                                        <button onclick="abrirModalVerCambiosProgramados()" class="btn-modern btn-outline btn-full">
                                            <i class="fas fa-tasks"></i> Tareas
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Panel de Previsualización -->
                        <div class="massive-preview-area">
                            <div id="previsualizacionCambios" class="preview-placeholder">
                                <div class="placeholder-content">
                                    <div class="placeholder-icon">
                                        <i class="fas fa-eye"></i>
                                    </div>
                                    <h3>Esperando Selección</h3>
                                    <p>Elige un tipo de IVA en el panel lateral para ver cómo afectará a tu catálogo de productos.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <style>
            .premium-panel { background: #fff; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); border: 1px solid #e5e7eb; overflow: hidden; margin-bottom: 20px; }
            .premium-panel-header { padding: 25px 30px; border-bottom: 1px solid #f3f4f6; display: flex; justify-content: space-between; align-items: center; background: #fafafa; }
            .header-left { display: flex; align-items: center; gap: 18px; }
            .header-icon-box { width: 48px; height: 48px; background: #6366f1; color: #fff; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; box-shadow: 0 4px 12px rgba(99, 102, 241, 0.2); }
            .premium-title { margin: 0; font-size: 1.4rem; font-weight: 800; color: #111827; letter-spacing: -0.5px; }
            .premium-subtitle { margin: 3px 0 0 0; font-size: 0.9rem; color: #6b7280; font-weight: 500; }
            
            .premium-tabs-modern { display: flex; background: #f1f5f9; padding: 4px; border-radius: 10px; border: 1px solid #e2e8f0; }
            .tab-modern { padding: 8px 16px; border-radius: 7px; border: none; cursor: pointer; font-weight: 600; font-size: 0.85rem; display: flex; align-items: center; gap: 8px; transition: all 0.2s; background: transparent; color: #64748b; }
            .tab-modern.active { background: #fff; color: #6366f1; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
            .tab-modern:hover:not(.active) { color: #334155; background: rgba(255,255,255,0.5); }
            
            .premium-panel-body { padding: 30px; }
            .iva-grid-container { display: grid; grid-template-columns: 1fr 300px; gap: 25px; }
            .iva-info-card { background: #fff; }
            .card-header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px; }
            .card-header-flex h3 { margin: 0; font-size: 1.1rem; color: #1f2937; display: flex; align-items: center; gap: 10px; }
            
            .iva-pct-badge { background: #eef2ff; color: #4f46e5; padding: 4px 12px; border-radius: 20px; font-weight: 800; font-size: 0.9rem; border: 1px solid #e0e7ff; }
            
            .iva-help-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 16px; padding: 25px; }
            .iva-help-card h4 { margin: 0 0 15px 0; font-size: 1rem; color: #334155; display: flex; align-items: center; gap: 10px; }
            .iva-help-card p { font-size: 0.85rem; color: #64748b; line-height: 1.5; margin-bottom: 20px; }
            .help-item { display: flex; align-items: flex-start; gap: 12px; margin-bottom: 12px; font-size: 0.85rem; color: #475569; }
            .help-item i { color: #f59e0b; margin-top: 3px; }
            
            .iva-massive-layout { display: grid; grid-template-columns: 350px 1fr; gap: 30px; align-items: flex-start; }
            .config-card-premium { background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; padding: 25px; position: sticky; top: 20px; }
            .config-card-premium h3 { margin: 0 0 10px 0; font-size: 1.1rem; color: #111827; display: flex; align-items: center; gap: 10px; }
            .config-desc { font-size: 0.85rem; color: #6b7280; margin-bottom: 25px; line-height: 1.5; }
            
            .premium-field-group { margin-bottom: 25px; }
            .premium-field-group label { display: block; font-size: 0.85rem; font-weight: 700; color: #374151; margin-bottom: 8px; }
            
            .select-wrapper-modern { position: relative; }
            .input-modern { width: 100%; padding: 12px 15px; border-radius: 10px; border: 2px solid #e5e7eb; font-size: 0.95rem; font-weight: 600; color: #111827; outline: none; appearance: none; transition: all 0.2s; background: #fff; }
            .input-modern:focus { border-color: #6366f1; box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1); }
            .select-icon { position: absolute; right: 15px; top: 50%; transform: translateY(-50%); color: #9ca3af; pointer-events: none; font-size: 0.8rem; }
            
            .massive-actions-stack { display: flex; flex-direction: column; gap: 12px; }
            .btn-full { width: 100%; justify-content: center; }
            .action-row-split { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
            
            .preview-placeholder { height: 400px; background: #f9fafb; border: 2px dashed #e5e7eb; border-radius: 20px; display: flex; align-items: center; justify-content: center; text-align: center; }
            .placeholder-icon { width: 80px; height: 80px; background: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; color: #d1d5db; font-size: 2.5rem; box-shadow: 0 4px 10px rgba(0,0,0,0.03); }
            .placeholder-content h3 { margin: 0 0 8px 0; font-size: 1.1rem; color: #4b5563; }
            .placeholder-content p { margin: 0; font-size: 0.9rem; color: #9ca3af; max-width: 250px; line-height: 1.4; }
            
            .btn-modern.btn-lg { padding: 15px 25px; font-size: 1rem; }
            .btn-outline { background: #fff; border: 2px solid #e5e7eb; color: #4b5563; }
            .btn-outline:hover { background: #f8fafc; border-color: #d1d5db; color: #1f2937; }
        </style>
    `;
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
        <div class="massive-preview-card animate-slide-up">
            <div class="preview-header-sticky">
                <div class="preview-title-info">
                    <h3><i class="fas fa-microscope"></i> Análisis de Impacto (${nuevoIVA}%)</h3>
                    <p>Haga clic en un producto para excluirlo de la actualización masiva.</p>
                </div>
                <div class="preview-header-actions">
                    <button class="btn-modern btn-outline btn-sm" onclick="excluirTodosProductos('iva')">
                        <i class="fas fa-times-circle"></i> Excluir Todos
                    </button>
                    <button class="btn-modern btn-outline btn-sm" onclick="incluirTodosProductos('iva')">
                        <i class="fas fa-check-circle"></i> Incluir Todos
                    </button>
                </div>
            </div>
            
            <div class="preview-table-viewport">
                <table class="premium-table-preview">
                    <thead>
                        <tr>
                            <th style="width: 50px; text-align: center;">ID</th>
                            <th>Producto</th>
                            <th style="text-align: right; width: 100px;">Base</th>
                            <th style="text-align: center; width: 100px;">IVA Ant.</th>
                            <th style="text-align: center; width: 100px;">IVA Sig.</th>
                            <th style="text-align: right; width: 120px;">Total Final</th>
                        </tr>
                    </thead>
                    <tbody>`;

    productos.forEach((p, i) => {
        const excluido = productosExcluidos.includes(p.id);
        const ivaFinal = excluido ? p.iva_actual : nuevoIVA;
        const precioConIVA = parseFloat(p.precio) * (1 + ivaFinal / 100);
        
        const rowClass = excluido ? 'row-excluded' : (p.iva_actual !== nuevoIVA ? 'row-changed' : 'row-equal');
        const prec = parseInt(p.decimales ?? 2);

        html += `
            <tr class="${rowClass}" onclick="toggleExcluirProducto(${p.id},'iva')">
                <td style="text-align:center; font-weight: 600; opacity: 0.6;">${p.id}</td>
                <td>
                    <div class="prod-name-flex">
                        <span class="prod-name-main">${p.nombre}</span>
                        ${excluido ? '<span class="excluded-pill">Excluido</span>' : ''}
                    </div>
                </td>
                <td style="text-align:right; font-family: 'JetBrains Mono', monospace;">${parseFloat(p.precio).toFixed(prec)} €</td>
                <td style="text-align:center; color: #6b7280;">${p.iva_actual}%</td>
                <td style="text-align:center;">
                    <span class="iva-next-pill ${excluido ? 'neutral' : 'active'}">${ivaFinal}%</span>
                </td>
                <td style="text-align:right; font-weight: 800; color: #111827;">
                    ${precioConIVA.toFixed(prec)} €
                </td>
            </tr>`;
    });

    html += `
                    </tbody>
                </table>
            </div>
        </div>
    `;
    contenedor.innerHTML = html;
    const wrapper = contenedor.querySelector('.preview-table-viewport');
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
    productosExcluidos = [];
    const contenedor = document.getElementById('adminContenido');
    seccionActual = 'tarifa-ajuste';
    adminTablaHeaderHTML = '';

    contenedor.innerHTML = `
        <div class="premium-panel animate-fade-in">
            <div class="premium-panel-header">
                <div class="header-left">
                    <div class="header-icon-box" style="background: linear-gradient(135deg, #10b981, #059669);">
                        <i class="fas fa-sliders-h"></i>
                    </div>
                    <div>
                        <h2 class="premium-title">Ajuste de Precios</h2>
                        <p class="premium-subtitle">Actualización porcentual masiva de precios base</p>
                    </div>
                </div>
            </div>
            
            <div class="premium-panel-body">
                <!-- SECCIÓN: AJUSTE DIRECTO -->
                <div id="ajusteSeccionDirecto" class="iva-content-section active">
                    <div class="iva-massive-layout">
                        <!-- Sidebar de configuración -->
                        <div class="massive-config-sidebar">
                            <div class="config-card-premium">
                                <h3 style="margin-bottom: 5px;"><i class="fas fa-percentage"></i> Configuración</h3>
                                <p class="config-desc" style="margin-bottom: 20px;">Aplica un porcentaje de ajuste a la base imponible de tus productos.</p>
                                
                                <div class="premium-field-group">
                                    <label>Porcentaje de ajuste (%)</label>
                                    <div class="input-with-hint">
                                        <input type="number" id="porcentajeAjuste" step="0.0001" 
                                            placeholder="Ej: 10 o -10" 
                                            oninput="validar4Decimales(this); actualizarPrevisualizacionPreciosAuto()"
                                            class="input-modern">
                                        <small class="field-hint">Positivo: subir | Negativo: bajar</small>
                                    </div>
                                </div>
                                
                                <div class="massive-actions-stack">
                                    <button onclick="aplicarAjustePrecios()" class="btn-modern btn-success btn-lg btn-full">
                                        <i class="fas fa-save"></i> Aplicar Ajuste Ahora
                                    </button>
                                    <div class="action-row-split">
                                        <button onclick="abrirModalProgramarAjustePrecios()" class="btn-modern btn-outline btn-full">
                                            <i class="fas fa-calendar-plus"></i> Programar
                                        </button>
                                        <button onclick="abrirModalVerAjustesProgramados()" class="btn-modern btn-outline btn-full">
                                            <i class="fas fa-list-ul"></i> Ver Lista
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Panel de Previsualización -->
                        <div class="massive-preview-area">
                            <div id="previsualizacionCambios" class="preview-placeholder">
                                <div class="placeholder-content">
                                    <div class="placeholder-icon">
                                        <i class="fas fa-search-dollar"></i>
                                    </div>
                                    <h3>Esperando Porcentaje</h3>
                                    <p>Introduce un valor en el panel lateral para ver la previsualización del ajuste de precios.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div style="margin-top: 40px; padding-top: 30px; border-top: 1px solid #f3f4f6;">
                    <div class="config-card-premium">
                        <div class="card-header-flex">
                            <h3><i class="fas fa-clock-rotate-left"></i> Historial de Tareas Programadas</h3>
                            <div class="header-actions">
                                <span class="info-tag"><i class="fas fa-info-circle"></i> Gestiona las actualizaciones automáticas aquí</span>
                            </div>
                        </div>
                        <div id="listaAjustesProgramadosTabla" class="modern-table-wrapper" style="margin-top: 15px; border-radius: 12px; border: 1px solid #e5e7eb; overflow: hidden;">
                            <div class="reports-loading"><i class="fas fa-spinner fa-spin"></i> Cargando tareas...</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    cargarAjustesPreciosProgramadosTabla();
}

function actualizarPrevisualizacionPreciosAuto() {
    const input = document.getElementById('porcentajeAjuste');
    const contenedor = document.getElementById('previsualizacionCambios');
    if (!input || !input.value) { if (contenedor) contenedor.innerHTML = ''; return; }
    clearTimeout(debounceTimerPrecios);
    debounceTimerPrecios = setTimeout(() => previsualizarAjustePrecios(), 500);
}

function previsualizarAjustePrecios() {
    const input = document.getElementById('porcentajeAjuste');
    if (!input) return;
    const porcentaje = parseFloat(input.value);
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
    
    let html = `
        <div class="massive-preview-card animate-slide-up">
            <div class="preview-header-sticky">
                <div class="preview-title-info">
                    <h3><i class="fas fa-search-dollar"></i> Análisis de Ajuste (${porcentaje}%)</h3>
                    <p>Haz clic en un producto para excluirlo de la actualización masiva.</p>
                </div>
                <div class="preview-header-actions">
                    <button class="btn-modern btn-outline btn-sm" onclick="excluirTodosProductos('precios')">
                        <i class="fas fa-times-circle"></i> Excluir Todos
                    </button>
                    <button class="btn-modern btn-outline btn-sm" onclick="incluirTodosProductos('precios')">
                        <i class="fas fa-check-circle"></i> Incluir Todos
                    </button>
                </div>
            </div>
            
            <div class="preview-table-viewport">
                <table class="premium-table-preview">
                    <thead>
                        <tr>
                            <th style="width: 50px; text-align: center;">ID</th>
                            <th>Producto</th>
                            <th style="text-align: right; width: 100px;">Base Act.</th>
                            <th style="text-align: center; width: 100px;">Ajuste</th>
                            <th style="text-align: center; width: 100px;">Variación</th>
                            <th style="text-align: right; width: 120px;">Precio Sig.</th>
                        </tr>
                    </thead>
                    <tbody>`;

    productos.forEach((p) => {
        const excluido = productosExcluidos.includes(p.id);
        const precioNuevo = excluido ? p.precio_actual : p.precio_nuevo;
        const diferencia = excluido ? 0 : p.diferencia;
        const rowClass = excluido ? 'row-excluded' : (p.diferencia !== 0 ? 'row-changed' : 'row-equal');
        const prec = parseInt(p.decimales ?? 2);
        const signo = p.diferencia > 0 ? '+' : '';

        html += `
            <tr class="${rowClass}" onclick="toggleExcluirProducto(${p.id},'precios')">
                <td style="text-align:center; font-weight: 600; opacity: 0.6;">${p.id}</td>
                <td>
                    <div class="prod-name-flex">
                        <span class="prod-name-main">${p.nombre}</span>
                        ${excluido ? '<span class="excluded-pill">Excluido</span>' : ''}
                    </div>
                </td>
                <td style="text-align:right; font-family: 'JetBrains Mono', monospace;">${parseFloat(p.precio_actual).toFixed(prec)} €</td>
                <td style="text-align:center; color: #6b7280;">${excluido ? '0' : porcentaje}%</td>
                <td style="text-align:center;">
                    <span class="iva-next-pill ${excluido ? 'neutral' : 'active'}">
                        ${excluido ? '=' : signo + diferencia.toFixed(prec)} €
                    </span>
                </td>
                <td style="text-align:right; font-weight: 800; color: #111827;">
                    ${parseFloat(precioNuevo).toFixed(prec)} €
                </td>
            </tr>`;
    });

    html += `
                    </tbody>
                </table>
            </div>
        </div>
    `;
    contenedor.innerHTML = html;
    const wrapper = contenedor.querySelector('.preview-table-viewport');
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
    const tarifas = window.tarifasData || [];
    const inicio = (paginaActualTarifas - 1) * productosPorPaginaTarifas;
    const productosPagina = todosLosProductosTarifas.slice(inicio, inicio + productosPorPaginaTarifas);
    const totalPaginas = Math.ceil(todosLosProductosTarifas.length / productosPorPaginaTarifas);

    let html = '';
    productosPagina.forEach(prod => {
        const ivaProd = parseFloat(prod.iva) || 21;
        let precioBase = parseFloat(prod.precio);
        if (tarifasMostrarConIva) precioBase *= (1 + ivaProd / 100);

        const getPrec = (v, d) => {
            const s = v.toString();
            const decPart = s.split('.')[1] || '';
            return Math.min(4, Math.max(2, d || 2, decPart.length));
        };
        const prec = getPrec(parseFloat(prod.precio), prod.decimales);

        html += `
            <tr style="border-bottom: 1px solid #f3f4f6; transition: background 0.15s;">
                <td style="padding: 12px 20px;">
                    <div style="font-weight: 600; color: #1e293b;">${prod.nombre}</div>
                    <div style="font-size: 11px; color: #94a3b8;">Ref: ${prod.id} | IVA: ${ivaProd}%</div>
                </td>
                <td style="padding: 12px 20px; text-align: right; font-weight: 700; color: #64748b; font-size: 0.95rem;">
                    ${precioBase.toFixed(prec)} €
                </td>`;

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

            const key = `${prod.id}-${tarifa.id}`;
            let valueToShow = precioFinal;
            let customClass = '';
            let badgeHtml = '';
            let indicatorDot = '';
            
            if (loteCambiosTarifas[key] !== undefined) {
                const val = loteCambiosTarifas[key];
                const nuevoVal = parseFloat(typeof val === 'object' ? val.nuevo : val);
                const anteriorVal = parseFloat(typeof val === 'object' ? val.anterior : precioFinal);
                
                valueToShow = nuevoVal;
                if (tarifasMostrarConIva) valueToShow *= (1 + ivaProd / 100);
                customClass = 'input-precio-programado';

                const diff = nuevoVal - anteriorVal;
                if (Math.abs(diff) > 0.0001) {
                    const diffClass = diff > 0 ? 'plus' : 'minus';
                    const diffSign = diff > 0 ? '+' : '';
                    badgeHtml = `<span class="price-diff-badge ${diffClass}">${diffSign}${diff.toFixed(2)}€</span>`;
                    indicatorDot = '<div class="change-indicator-dot"></div>';
                }
            }

            const inputStyle = esManual 
                ? 'border-color: #10b981; background: #ecfdf5; color: #065f46;' 
                : 'border-color: #e5e7eb; background: #fff; color: #1e293b;';

            const disabledAttr = (tarifasMostrarConIva && !modoProgramacionTarifas) ? 'disabled' : '';
            const disabledStyle = (tarifasMostrarConIva && !modoProgramacionTarifas) ? 'opacity: 0.5; cursor: not-allowed; background: #f8fafc;' : '';

            html += `
                <td style="padding: 12px 20px; text-align: right;">
                    <div style="display: inline-flex; align-items: center; gap: 4px; position: relative;">
                        <input type="number" step="0.0001" 
                            value="${valueToShow.toFixed(prec)}"
                            data-precio-anterior="${precioFinal.toFixed(4)}"
                            onchange="actualizarPrecioTarifaIndividual(${prod.id}, ${tarifa.id}, this, ${ivaProd})"
                            ${disabledAttr}
                            class="${customClass}"
                            style="width: 85px; padding: 6px 10px; border: 1.5px solid; border-radius: 8px; font-weight: 700; text-align: right; outline: none; transition: all 0.2s; ${inputStyle} ${disabledStyle}">
                        <div style="display: flex; flex-direction: column; align-items: flex-start;">
                            <span style="font-size: 13px; font-weight: 700; color: #94a3b8;">€</span>
                            ${badgeHtml}
                        </div>
                        ${indicatorDot}
                        ${esManual && !indicatorDot ? '<i class="fas fa-hand-paper" title="Precio establecido manualmente" style="color: #10b981; font-size: 11px; position: absolute; top: -8px; right: -8px; background: white; border-radius: 50%; padding: 2px;"></i>' : ''}
                    </div>
                </td>`;
        });

        html += `</tr>`;
    });

    const tbody = document.getElementById('tablaPreciosProductos');
    if (tbody) tbody.innerHTML = html;

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
    const keys = Object.keys(loteCambiosTarifas);
    if (!keys.length) { alert('No hay cambios en el lote para programar.'); return; }

    const modalesDiv = document.getElementById('modalesTarifas');
    if (!modalesDiv) return;

    const isDark = document.body.classList.contains('dark-mode');
    const now = new Date();
    now.setDate(now.getDate() + 1);
    const defaultDate = now.toISOString().slice(0, 16);

    const modalBg = isDark ? '#1f2937' : 'white';
    const bodyBg = isDark ? '#111827' : '#f8fafc';
    const cardBg = isDark ? '#1e293b' : 'white';
    const borderColor = isDark ? '#374151' : '#e2e8f0';
    const textColor = isDark ? '#f1f5f9' : '#1e293b';
    const subTextColor = isDark ? '#94a3b8' : '#64748b';

    let listaCambiosHtml = '';
    keys.forEach(key => {
        const [idP, idT] = key.split('-');
        const prod = todosLosProductosTarifas.find(p => p.id == idP);
        const tarifa = window.tarifasData.find(t => t.id == idT);
        const cambio = loteCambiosTarifas[key];
        
        if (prod && tarifa) {
            const anterior = parseFloat(cambio.anterior || 0);
            const nuevo = parseFloat(cambio.nuevo);
            const diff = nuevo - anterior;
            const diffClass = diff >= 0 ? 'text-success' : 'text-danger';
            const diffIcon = diff >= 0 ? 'fa-caret-up' : 'fa-caret-down';

            listaCambiosHtml += `
                <div style="display: grid; grid-template-columns: 2fr 1.5fr 2fr; align-items: center; padding: 12px 15px; border-bottom: 1px solid ${borderColor}; font-size: 0.9rem;">
                    <div>
                        <div style="font-weight: 700; color: ${textColor};">${prod.nombre}</div>
                        <div style="font-size: 11px; color: ${subTextColor}; font-weight: 600; text-transform: uppercase;">${tarifa.nombre}</div>
                    </div>
                    <div style="text-align: center; font-weight: 600; color: ${subTextColor};">
                        ${anterior.toFixed(2)}€ <i class="fas fa-long-arrow-alt-right" style="margin: 0 8px; font-size: 0.8rem;"></i>
                    </div>
                    <div style="text-align: right;">
                        <span style="font-weight: 800; color: ${textColor}; font-size: 1rem;">${nuevo.toFixed(2)}€</span>
                        <div style="font-size: 11px; font-weight: 700;" class="${diffClass}">
                            <i class="fas ${diffIcon}"></i> ${diff.toFixed(2)}€
                        </div>
                    </div>
                </div>`;
        }
    });

    modalesDiv.innerHTML = `
    <div id="modalProgramarCambiosTarifas" class="modal-overlay" style="display: flex; position: fixed; z-index: 10000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.6); align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div class="modal-content animate-scale-up" style="background: ${modalBg}; border-radius: 20px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); max-width: 600px; width: 95%; max-height: 85vh; overflow: hidden; display: flex; flex-direction: column; border: 1px solid ${borderColor};">
            <!-- Header -->
            <div style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; padding: 25px 30px; display: flex; justify-content: space-between; align-items: center;">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div style="background: rgba(255,255,255,0.2); width: 45px; height: 45px; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-clock" style="font-size: 1.5rem;"></i>
                    </div>
                    <div>
                        <h3 style="margin: 0; font-size: 1.4rem; font-weight: 800; letter-spacing: -0.5px;">Programar Lote</h3>
                        <p style="margin: 2px 0 0; font-size: 0.9rem; opacity: 0.9; font-weight: 500;">Confirmar y planificar cambios de precios</p>
                    </div>
                </div>
                <button onclick="cerrarModal('modalProgramarCambiosTarifas')" style="background: rgba(255,255,255,0.2); border: none; color: white; width: 32px; height: 32px; border-radius: 10px; cursor: pointer;">&times;</button>
            </div>

            <!-- Body -->
            <div style="padding: 25px 30px; overflow-y: auto; flex: 1; background: ${bodyBg};">
                <div class="alert-modern warning" style="margin-bottom: 20px;">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div>
                        <div style="font-weight: 700;">Revisión de Cambios</div>
                        <div>Se han detectado <strong>${keys.length}</strong> modificaciones de precio en este lote.</div>
                    </div>
                </div>

                <div style="background: ${cardBg}; border-radius: 16px; border: 1px solid ${borderColor}; overflow: hidden; margin-bottom: 25px;">
                    <div style="padding: 12px 15px; background: ${isDark ? '#374151' : '#f1f5f9'}; border-bottom: 1px solid ${borderColor}; font-size: 0.75rem; font-weight: 800; color: ${subTextColor}; text-transform: uppercase; display: grid; grid-template-columns: 2fr 1.5fr 2fr;">
                        <span>Producto / Tarifa</span>
                        <span style="text-align: center;">Transición</span>
                        <span style="text-align: right;">Nuevo Precio</span>
                    </div>
                    <div style="max-height: 250px; overflow-y: auto;">
                        ${listaCambiosHtml}
                    </div>
                </div>

                <div class="ver-prod-item-premium">
                    <label style="display: block; font-size: 0.75rem; color: ${subTextColor}; font-weight: 700; text-transform: uppercase; margin-bottom: 10px;">Fecha y Hora de Aplicación</label>
                    <div style="display: flex; align-items: center; gap: 15px; background: ${cardBg}; padding: 15px; border-radius: 12px; border: 2px solid ${borderColor}; transition: border-color 0.2s;">
                        <i class="fas fa-calendar-alt" style="color: #f59e0b; font-size: 1.2rem;"></i>
                        <input type="datetime-local" id="fechaProgramadaTarifas" value="${defaultDate}" 
                            style="flex: 1; border: none; outline: none; font-size: 1rem; font-weight: 700; color: ${textColor}; background: transparent;">
                    </div>
                    <p style="margin: 8px 0 0; font-size: 0.8rem; color: ${subTextColor}; font-weight: 500;">Los precios se actualizarán automáticamente en el sistema al llegar esta fecha.</p>
                </div>
            </div>

            <!-- Footer -->
            <div style="padding: 20px 30px; background: ${modalBg}; border-top: 1px solid ${borderColor}; display: flex; justify-content: flex-end; gap: 15px;">
                <button class="btn-modern btn-outline" onclick="cerrarModal('modalProgramarCambiosTarifas')" style="padding: 12px 25px;">Cancelar</button>
                <button class="btn-modern btn-warning" onclick="ejecutarGuardarProgramacionTarifas()" style="padding: 12px 30px; background: #f59e0b; color: white;">
                    <i class="fas fa-save"></i> <span>Confirmar Programación</span>
                </button>
            </div>
        </div>
    </div>`;
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
function mostrarPanelTarifasPrefijadas() {
    const contenedor = document.getElementById('adminContenido');
    seccionActual = 'tarifa-prefijadas';
    adminTablaHeaderHTML = '';

    return Promise.all([
        fetch('api/tarifas.php').then(res => res.json()),
        fetch('api/productos.php').then(res => res.json())
    ])
    .then(([tarifas, productos]) => {
        window.tarifasData = tarifas;
        todosLosProductosTarifas = productos;
        productosOriginalesTarifas = [...productos];
        paginaActualTarifas = 1;

        const inicio = (paginaActualTarifas - 1) * productosPorPaginaTarifas;
        const productosPagina = productos.slice(inicio, inicio + productosPorPaginaTarifas);

        // Ocultar el título de la vista admin para ganar espacio
        const adminTitulo = document.getElementById('adminTitulo');
        if (adminTitulo) adminTitulo.style.display = 'none';

        contenedor.innerHTML = `
            <div class="premium-panel animate-fade-in">
                <div class="premium-panel-header">
                    <div class="header-left">
                        <div class="header-icon-box" style="background: linear-gradient(135deg, #6366f1, #8b5cf6);">
                            <i class="fas fa-list-ul"></i>
                        </div>
                        <div>
                            <h2 class="premium-title">Tarifas Prefijadas</h2>
                            <p class="premium-subtitle">Gestiona precios específicos por producto para cada tipo de cliente</p>
                        </div>
                    </div>
                    <div class="header-right">
                        <div class="premium-tabs-modern">
                            <button onclick="abrirModalTarifas()" class="tab-modern">
                                <i class="fas fa-cog"></i> <span>Configurar Tarifas</span>
                            </button>
                            <button onclick="abrirModalVerCambiosTarifasProgramados()" class="tab-modern">
                                <i class="fas fa-history"></i> <span>Ver Programaciones</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="premium-panel-body">
                    <!-- Toolbar de Acciones -->
                    <div class="admin-tabla-header" style="padding: 0; margin-bottom: 25px; background: transparent; border: none;">
                        <div class="header-filters-grid">
                            <div class="filter-main">
                                <div class="search-wrapper">
                                    <i class="fas fa-search search-icon"></i>
                                    <input type="text" id="buscarProductoTarifa" class="input-modern-search" 
                                        placeholder="Buscar por nombre de producto..." 
                                        value="${tarifaBusquedaProducto}"
                                        oninput="tarifaBusquedaProducto = this.value; filtrarTablaTarifas();">
                                </div>
                            </div>
                            
                            <div class="header-status-info">
                                <button onclick="toggleTarifasIva()" class="btn-modern btn-outline" style="min-width: 150px; justify-content: center;">
                                    <i class="fas ${tarifasMostrarConIva ? 'fa-file-invoice-dollar' : 'fa-coins'}"></i>
                                    <span>${tarifasMostrarConIva ? 'Ver Sin IVA' : 'Ver Con IVA'}</span>
                                </button>
                            </div>

                            <div class="header-actions">
                                ${!modoProgramacionTarifas ? `
                                    <button onclick="alternarModoProgramacionTarifas()" class="btn-modern btn-warning" style="background: #f59e0b; color: white;">
                                        <i class="fas fa-clock"></i> <span>Modo Programación</span>
                                    </button>
                                ` : `
                                    <div style="display: flex; gap: 10px;">
                                        <button onclick="abrirModalProgramarCambiosTarifas()" class="btn-modern btn-success">
                                            <i class="fas fa-check"></i> <span>Guardar Lote (${Object.keys(loteCambiosTarifas).length})</span>
                                        </button>
                                        <button onclick="alternarModoProgramacionTarifas()" class="btn-modern btn-danger" style="background: #ef4444; color: white;">
                                            <i class="fas fa-times"></i> <span>Cancelar</span>
                                        </button>
                                    </div>
                                `}
                            </div>
                        </div>
                    </div>

                    ${modoProgramacionTarifas ? `
                        <div id="alertModoProgramacion" class="alert-modern info animate-slide-up" style="margin-bottom: 15px; display: flex; align-items: center; gap: 12px; padding: 10px 15px; background: #fffbeb; border: 1px solid #fde68a; border-radius: 10px; color: #92400e; width: fit-content; max-width: 100%; transition: opacity 0.5s ease;">
                            <i class="fas fa-info-circle" style="font-size: 1rem;"></i>
                            <p style="margin: 0; font-size: 0.85rem; font-weight: 500;">
                                <strong style="font-weight: 700;">Modo Programación:</strong> Los cambios se guardarán en un lote para ser programados.
                            </p>
                        </div>
                    ` : ''}

                    <!-- Grid de Precios -->
                    <div class="modern-table-wrapper" style="border: 1px solid #e5e7eb; border-radius: 16px; overflow: hidden; background: #fff;">
                        <table class="premium-table-preview" style="width: 100%; border-collapse: collapse;">
                            <thead id="cabeceraTarifasGrid">
                                <!-- Se genera dinámicamente -->
                            </thead>
                            <tbody id="tablaPreciosProductos">
                                <!-- Se genera dinámicamente -->
                            </tbody>
                        </table>
                    </div>

                    <div id="paginacionTarifas" style="margin-top: 20px; min-height: 50px;">
                        ${getPaginacionTarifasHTML(Math.ceil(productosOriginalesTarifas.length / productosPorPaginaTarifas))}
                    </div>
                </div>
            </div>
            <div id="modalesTarifas"></div>
        `;
        
        actualizarCabeceraTarifas();
        actualizarTablaTarifas();

        if (modoProgramacionTarifas) {
            setTimeout(() => {
                const alert = document.getElementById('alertModoProgramacion');
                if (alert) {
                    alert.style.opacity = '0';
                    setTimeout(() => alert.style.display = 'none', 500);
                }
            }, 4000);
        }
    });
}

function actualizarCabeceraTarifas() {
    const tarifas = window.tarifasData || [];
    let html = `
        <tr>
            <th style="padding: 15px 20px; text-align: left; background: #f9fafb; color: #6b7280; font-size: 0.75rem; text-transform: uppercase; font-weight: 700; border-bottom: 2px solid #f3f4f6;">Producto</th>
            <th style="padding: 15px 20px; text-align: right; background: #f9fafb; color: #6b7280; font-size: 0.75rem; text-transform: uppercase; font-weight: 700; border-bottom: 2px solid #f3f4f6; width: 120px;">Precio Base</th>`;
    
    tarifas.forEach(tarifa => {
        html += `<th style="padding: 15px 20px; text-align: right; background: #f9fafb; color: #6366f1; font-size: 0.75rem; text-transform: uppercase; font-weight: 800; border-bottom: 2px solid #f3f4f6; width: 130px;">
            <div style="display: flex; flex-direction: column; align-items: flex-end;">
                <span>${tarifa.nombre}</span>
                <small style="opacity: 0.7; font-size: 10px;">Dto: ${tarifa.descuento_porcentaje}%</small>
            </div>
        </th>`;
    });
    
    html += `</tr>`;
    const thead = document.getElementById('cabeceraTarifasGrid');
    if (thead) thead.innerHTML = html;
}


/**
 * Abre el modal de tarifas con diseño premium
 */
function abrirModalTarifas() {
    const modalesDiv = document.getElementById('modalesTarifas');
    if (!modalesDiv) return;

    fetch('api/tarifas.php')
        .then(res => res.json())
        .then(tarifas => {
            const isDark = document.body.classList.contains('dark-mode');
            const textColor = isDark ? '#e5e7eb' : '#374151';
            const subTextColor = isDark ? '#9ca3af' : '#6b7280';
            const borderColor = isDark ? '#374151' : '#e5e7eb';
            const modalContentBg = isDark ? '#1f2937' : 'white';

            modalesDiv.innerHTML = `
            <div id="modalTarifas" class="modal-overlay" style="display: flex; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.6); align-items: center; justify-content: center; backdrop-filter: blur(4px);">
                <div class="modal-content animate-scale-up" style="background: ${modalContentBg}; border-radius: 20px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); max-width: 950px; width: 95%; max-height: 90vh; overflow: hidden; display: flex; flex-direction: column; border: 1px solid ${borderColor};">
                    <!-- Header Premium -->
                    <div style="background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #a855f7 100%); color: white; padding: 25px 35px; display: flex; justify-content: space-between; align-items: center; position: relative; overflow: hidden;">
                        <div style="position: absolute; top: -20px; right: -20px; width: 150px; height: 150px; background: rgba(255,255,255,0.1); border-radius: 50%;"></div>
                        <div style="position: relative; z-index: 1;">
                            <div style="display: flex; align-items: center; gap: 15px;">
                                <div style="background: rgba(255,255,255,0.2); border-radius: 14px; padding: 12px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                                    <i class="fas fa-tags" style="font-size: 22px;"></i>
                                </div>
                                <div>
                                    <h3 style="margin: 0; font-size: 22px; font-weight: 800; letter-spacing: -0.5px;">Gestión de Tarifas</h3>
                                    <p style="margin: 4px 0 0; font-size: 14px; opacity: 0.9; font-weight: 500;">Configura los niveles de precios y descuentos globales</p>
                                </div>
                            </div>
                        </div>
                        <button onclick="cerrarModal('modalTarifas')" style="background: rgba(255,255,255,0.2); border: none; color: white; width: 40px; height: 40px; border-radius: 12px; cursor: pointer; font-size: 20px; display: flex; align-items: center; justify-content: center; transition: all 0.2s; position: relative; z-index: 1;" onmouseover="this.style.background='rgba(255,255,255,0.3)';this.style.transform='rotate(90deg)'" onmouseout="this.style.background='rgba(255,255,255,0.2)';this.style.transform='rotate(0deg)'">&times;</button>
                    </div>

                    <!-- Body -->
                    <div style="padding: 30px; overflow-y: auto; flex: 1; background: ${isDark ? 'rgba(15, 23, 42, 0.2)' : '#f8fafc'};">
                        <!-- Stats Summary -->
                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 25px;">
                            <div style="background: ${isDark ? '#1e1b4b' : '#ffffff'}; border: 1px solid ${isDark ? '#312e81' : '#e2e8f0'}; border-radius: 16px; padding: 20px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 15px;">
                                <div style="width: 48px; height: 48px; border-radius: 12px; background: #eef2ff; color: #6366f1; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;"><i class="fas fa-layer-group"></i></div>
                                <div>
                                    <div style="font-size: 1.5rem; font-weight: 800; color: #6366f1; line-height: 1;">${tarifas.length}</div>
                                    <div style="font-size: 0.75rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; margin-top: 4px;">Tarifas Totales</div>
                                </div>
                            </div>
                            <div style="background: ${isDark ? '#052e16' : '#ffffff'}; border: 1px solid ${isDark ? '#166534' : '#e2e8f0'}; border-radius: 16px; padding: 20px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 15px;">
                                <div style="width: 48px; height: 48px; border-radius: 12px; background: #ecfdf5; color: #10b981; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;"><i class="fas fa-percentage"></i></div>
                                <div>
                                    <div style="font-size: 1.5rem; font-weight: 800; color: #10b981; line-height: 1;">${tarifas.filter(t => parseFloat(t.descuento_porcentaje) > 0).length}</div>
                                    <div style="font-size: 0.75rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; margin-top: 4px;">Con Descuento</div>
                                </div>
                            </div>
                            <div style="background: ${isDark ? '#1e293b' : '#ffffff'}; border: 1px solid ${isDark ? '#334155' : '#e2e8f0'}; border-radius: 16px; padding: 20px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 15px;">
                                <div style="width: 48px; height: 48px; border-radius: 12px; background: #f0f9ff; color: #3b82f6; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;"><i class="fas fa-user-tag"></i></div>
                                <div>
                                    <div style="font-size: 1.5rem; font-weight: 800; color: #3b82f6; line-height: 1;">${tarifas.filter(t => t.requiere_cliente).length}</div>
                                    <div style="font-size: 0.75rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; margin-top: 4px;">Exclusivas Cliente</div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Bar -->
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; background: ${isDark ? '#1e293b' : '#ffffff'}; padding: 15px 20px; border-radius: 16px; border: 1px solid ${borderColor};">
                            <p style="color: ${subTextColor}; margin: 0; font-size: 14px; font-weight: 500;"><i class="fas fa-info-circle" style="margin-right: 8px; color: #6366f1;"></i> Estas tarifas se aplican automáticamente según el perfil del cliente seleccionado en el cajero.</p>
                            <button onclick="abrirModalNuevaTarifa()" class="btn-modern btn-success" style="padding: 10px 25px; border-radius: 12px; font-weight: 700; box-shadow: 0 4px 12px rgba(16,185,129,0.2);">
                                <i class="fas fa-plus-circle"></i> Nueva Tarifa
                            </button>
                        </div>

                        <!-- Table -->
                        <div class="modern-table-wrapper" style="border: 1px solid ${borderColor}; border-radius: 18px; overflow: hidden; background: ${isDark ? '#111827' : 'white'}; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);">
                            <table style="width: 100%; border-collapse: collapse;">
                                <thead style="background: ${isDark ? '#1f2937' : '#f1f5f9'}; border-bottom: 2px solid ${borderColor};">
                                    <tr>
                                        <th style="padding: 18px 24px; text-align: left; font-weight: 700; font-size: 12px; text-transform: uppercase; color: #64748b; letter-spacing: 0.05em;">Tarifa</th>
                                        <th style="padding: 18px 24px; text-align: left; font-weight: 700; font-size: 12px; text-transform: uppercase; color: #64748b; letter-spacing: 0.05em;">Descripción</th>
                                        <th style="padding: 18px 24px; text-align: center; font-weight: 700; font-size: 12px; text-transform: uppercase; color: #64748b; letter-spacing: 0.05em;">Descuento</th>
                                        <th style="padding: 18px 24px; text-align: center; font-weight: 700; font-size: 12px; text-transform: uppercase; color: #64748b; letter-spacing: 0.05em;">Acceso</th>
                                        <th style="padding: 18px 24px; text-align: right; font-weight: 700; font-size: 12px; text-transform: uppercase; color: #64748b; letter-spacing: 0.05em;">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${tarifas.map((tarifa, idx) => {
                                        const isEven = idx % 2 === 0;
                                        const rowBg = isEven ? 'transparent' : (isDark ? 'rgba(255,255,255,0.02)' : 'rgba(0,0,0,0.01)');
                                        return `
                                        <tr style="background: ${rowBg}; border-bottom: 1px solid ${borderColor}; transition: all 0.2s;">
                                            <td style="padding: 16px 24px;">
                                                <div style="display: flex; align-items: center; gap: 12px;">
                                                    <div style="width: 38px; height: 38px; border-radius: 12px; background: linear-gradient(135deg, #6366f1, #a855f7); display: flex; align-items: center; justify-content: center; color: white; font-weight: 800; font-size: 14px; box-shadow: 0 4px 8px rgba(99,102,241,0.2);">${tarifa.nombre.charAt(0).toUpperCase()}</div>
                                                    <span style="font-weight: 700; color: ${textColor}; font-size: 15px;">${tarifa.nombre}</span>
                                                </div>
                                            </td>
                                            <td style="padding: 16px 24px; color: ${subTextColor}; font-size: 14px; font-weight: 500;">${tarifa.descripcion || '<span style="opacity: 0.3; font-style: italic;">Sin descripción</span>'}</td>
                                            <td style="padding: 16px 24px; text-align: center;">
                                                <span style="background: ${parseFloat(tarifa.descuento_porcentaje) > 0 ? (isDark ? '#1e3a5f' : '#dbeafe') : (isDark ? '#1f2937' : '#f1f5f9')}; color: ${parseFloat(tarifa.descuento_porcentaje) > 0 ? (isDark ? '#93c5fd' : '#1d4ed8') : (isDark ? '#94a3b8' : '#64748b')}; padding: 6px 16px; border-radius: 10px; font-weight: 800; font-size: 14px;">${tarifa.descuento_porcentaje}%</span>
                                            </td>
                                            <td style="padding: 16px 24px; text-align: center;">
                                                <div style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 10px; background: ${tarifa.requiere_cliente ? '#ecfdf5' : '#fff7ed'}; color: ${tarifa.requiere_cliente ? '#059669' : '#d97706'}; font-weight: 700; font-size: 12px;">
                                                    <i class="fas ${tarifa.requiere_cliente ? 'fa-user-check' : 'fa-users'}"></i>
                                                    ${tarifa.requiere_cliente ? 'CLIENTE' : 'GENERAL'}
                                                </div>
                                            </td>
                                            <td style="padding: 16px 24px; text-align: right;">
                                                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                                                    <button onclick="abrirModalEditarTarifa(${tarifa.id}, '${tarifa.nombre.replace(/'/g, "\\'")}', '${(tarifa.descripcion || '').replace(/'/g, "\\'")}', ${tarifa.descuento_porcentaje}, ${tarifa.requiere_cliente ? 1 : 0})" class="btn-admin-accion" style="background: #eef2ff; color: #6366f1; border: 1px solid #c7d2fe;" title="Editar"><i class="fas fa-edit"></i></button>
                                                    <button onclick="eliminarTarifa(${tarifa.id}, '${tarifa.nombre.replace(/'/g, "\\'")}')" class="btn-admin-accion" style="background: #fef2f2; color: #ef4444; border: 1px solid #fecaca;" title="Eliminar"><i class="fas fa-trash-alt"></i></button>
                                                </div>
                                            </td>
                                        </tr>`;
                                    }).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>`;
        });
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

function cargarAjustesPreciosProgramadosTabla() {
    const contenedor = document.getElementById('listaAjustesProgramadosTabla');
    if (!contenedor) return;

    fetch('api/productos.php?accion=obtener_ajustes_precios_programados')
        .then(res => res.json())
        .then(data => {
            if (!data.ajustes || data.ajustes.length === 0) {
                contenedor.innerHTML = `
                    <div style="padding: 60px 20px; text-align: center; color: #94a3b8;">
                        <i class="fas fa-calendar-times" style="font-size: 3.5rem; opacity: 0.2; margin-bottom: 15px; display: block;"></i>
                        <p style="font-size: 1.1rem; font-weight: 500;">No hay ajustes programados</p>
                    </div>`;
                return;
            }

            let html = `
                <table class="admin-tabla">
                    <thead>
                        <tr>
                            <th style="padding-left: 20px;">Fecha Programada</th>
                            <th style="text-align: center;">Porcentaje</th>
                            <th style="text-align: center;">Estado</th>
                            <th style="text-align: right; padding-right: 20px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>`;

            data.ajustes.forEach(ajuste => {
                const fecha = new Date(ajuste.fecha_programada).toLocaleString('es-ES', {
                    day: '2-digit', month: '2-digit', year: 'numeric',
                    hour: '2-digit', minute: '2-digit'
                });

                const esSubida = ajuste.porcentaje > 0;
                const pctColor = esSubida ? '#10b981' : '#ef4444';
                const pctBg = esSubida ? '#ecfdf5' : '#fef2f2';
                const signo = esSubida ? '+' : '';

                let statusBadge = '';
                if (ajuste.estado === 'aplicado') {
                    statusBadge = '<span style="background: #dcfce7; color: #15803d; padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase;"><i class="fas fa-check-circle" style="margin-right: 5px;"></i>Aplicado</span>';
                } else if (ajuste.estado === 'pendiente') {
                    statusBadge = '<span style="background: #fef3c7; color: #92400e; padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase;"><i class="fas fa-clock" style="margin-right: 5px;"></i>Pendiente</span>';
                } else {
                    statusBadge = `<span style="background: #f1f5f9; color: #475569; padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase;">${ajuste.estado}</span>`;
                }

                const esPendiente = ajuste.estado === 'pendiente';

                html += `
                    <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.2s;">
                        <td style="padding: 15px 20px; font-weight: 600; color: #1e293b;">${fecha}</td>
                        <td style="padding: 15px 20px; text-align: center;">
                            <span style="background: ${pctBg}; color: ${pctColor}; padding: 4px 12px; border-radius: 8px; font-weight: 800; font-size: 1rem;">${signo}${ajuste.porcentaje}%</span>
                        </td>
                        <td style="padding: 15px 20px; text-align: center;">${statusBadge}</td>
                        <td style="padding: 15px 20px; text-align: right;">
                            <div style="display: flex; justify-content: flex-end; gap: 8px;">
                                <button class="action-btn btn-view" onclick="verDetallesAjustePrecios(${ajuste.id})" title="Ver Detalles"
                                    style="width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; background: #f1f5f9; color: #6366f1; border: none; cursor: pointer;">
                                    <i class="fas fa-eye"></i>
                                </button>
                                ${esPendiente ? `
                                    <button class="action-btn btn-edit" onclick="editarAjusteProgramadoPrecios(${ajuste.id})" title="Editar"
                                        style="width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; background: #f1f5f9; color: #f59e0b; border: none; cursor: pointer;">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="action-btn btn-delete" onclick="eliminarAjusteProgramadoPrecios(${ajuste.id})" title="Eliminar"
                                        style="width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; background: #fef2f2; color: #ef4444; border: none; cursor: pointer;">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                ` : ''}
                            </div>
                        </td>
                    </tr>`;
            });

            html += '</tbody></table>';
            contenedor.innerHTML = html;
        });
}