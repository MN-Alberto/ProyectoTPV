// ======================== RENDER PRODUCTOS (MODO TABLA - ADMIN) ========================
function renderProductosAdmin(productos) {
    const contenedor = document.getElementById('adminContenido');

    if (!productos || productos.length === 0) {
        contenedor.innerHTML = '<p class="sin-productos">No hay productos disponibles.</p>';
        return;
    }

    let html = `
        <div class="admin-tabla-header">

            <label for="inputBuscarProducto"
                style="font-weight: 600; color: #1a1a2e; white-space: nowrap;">Buscar:</label>
            <input type="text" id="inputBuscarProducto" class="input-buscarProducto"
                placeholder="Escribe el nombre del producto a buscar..." oninput="buscarProductos()" autocomplete="off"
                style="width: 82%;" />

            <button class="btn-admin-accion btn-nuevo" onclick="nuevoProducto()">
                <i class="fas fa-plus"></i> Nuevo Producto
            </button>
        </div>
        <div class="admin-tabla-wrapper">
            <table class="admin-tabla">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Imagen</th>
                        <th>Nombre</th>
                        <th>Categoría</th>
                        <th>Precio</th>
                        <th>Stock</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>`;

    productos.forEach(prod => {
        const precioFmt = parseFloat(prod.precio).toFixed(2).replace('.', ',');
        const imgSrc = prod.imagen && prod.imagen !== '' ? prod.imagen : 'webroot/img/logo.PNG';

        // Determinar badge de stock
        let stockBadge = '';
        if (prod.stock <= 0) {
            stockBadge = 'badge-agotado';
        } else if (prod.stock <= 3) {
            stockBadge = 'badge-bajo';
        } else {
            stockBadge = 'badge-ok';
        }

        // Determinar badge de estado (activo/inactivo si tienes ese campo, si no se omite)
        const estadoHtml = prod.activo == 1
            ? `<span class="admin-badge badge-activo">Activo</span>`
            : `<span class="admin-badge badge-inactivo">Inactivo</span>`;

        html += `
                    <tr class="${prod.stock <= 0 ? 'fila-agotada' : ''}${prod.activo == 0 ? 'fila-inactiva' : ''}">
                        <td class="col-id">${prod.id}</td>
                        <td class="col-img">
                            <img src="${imgSrc}" alt="${prod.nombre.replace(/"/g, '&quot;')}" class="admin-tabla-img">
                        </td>
                        <td class="col-nombre">${prod.nombre}</td>
                        <td class="col-categoria">${prod.categoria ?? '—'}</td>
                        <td class="col-precio">${precioFmt} €</td>
                        <td class="col-stock">
                            <span class="admin-badge ${stockBadge}">${prod.stock}</span>
                        </td>
                        <td class="col-estado">${estadoHtml}</td>
                        <td class="col-acciones" style="${prod.stock <= 0 ? 'opacity: 1;' : ''}${prod.activo == 0 ? 'opacity: 1;' : ''}">
                            <button class="btn-admin-accion btn-ver" onclick="verProducto(${prod.id})" title="Ver">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn-admin-accion btn-editar" onclick="editarProducto(${prod.id})" title="Editar">
                                <i class="fas fa-pen"></i>
                            </button>
                            <button class="btn-admin-accion btn-eliminar" onclick="confirmarEliminarProducto(${prod.id}, '${prod.nombre.replace(/'/g, "\\'")}')" title="Eliminar">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>`;
    });

    html += `
                </tbody>
            </table>
        </div>`;

    contenedor.innerHTML = html;
}

function cerrarModal(id) {
    document.getElementById(id).style.display = 'none';
}

function abrirModal(id) {
    document.getElementById(id).style.display = 'flex';
}

// ======================== CARGAR PRODUCTOS EN PANEL ADMIN ========================
function cargarProductosAdmin(idCategoria = 'todas', textoBusqueda = '') {
    const params = new URLSearchParams();
    params.append('idCategoria', idCategoria);
    params.append('admin', '1');
    if (textoBusqueda) params.append('buscarProducto', textoBusqueda);

    return fetch('api/productos.php?' + params.toString())
        .then(res => res.json())
        .then(data => renderProductosAdmin(data))
        .catch(err => {
            console.error('Error cargando productos (admin):', err);
            document.getElementById('adminContenido').innerHTML =
                '<p class="sin-productos">Error al cargar los productos.</p>';
        });
}

// ======================== ACCIONES ADMIN (STUBS) ========================
function nuevoProducto() {
    // TODO: abrir modal de creación
    console.log('Nuevo producto');
}

function editarProducto(id) {
    const fila = document.querySelector(`tr [onclick="editarProducto(${id})"]`).closest('tr');
    const celdas = fila.querySelectorAll('td');

    const nombre = celdas[2].textContent.trim();
    const categoria = celdas[3].textContent.trim();
    const precio = celdas[4].textContent.replace('€', '').replace(',', '.').trim();
    const stock = celdas[5].textContent.trim();
    const activo = celdas[6].querySelector('.badge-activo') ? 1 : 0;
    const imgSrc = celdas[1].querySelector('img')?.src ?? 'webroot/img/logo.PNG';

    // Guardar el ID para el guardado
    document.getElementById('editProductoId').value = id;

    // Rellenar campos
    const imgEl = document.getElementById('editProductoImagen');
    imgEl.src = imgSrc;
    imgEl.alt = nombre;

    document.getElementById('editProductoNombre').value = nombre;
    document.getElementById('editProductoCategoria').value = categoria;
    document.getElementById('editProductoPrecio').value = precio;
    document.getElementById('editProductoStock').value = stock;
    document.getElementById('editProductoEstado').value = activo;

    // Limpiar input de imagen por si había una previsualización anterior
    document.getElementById('editProductoImagenInput').value = '';

    abrirModal('modalEditarProducto');
}

function previsualizarImagen(event) {
    const file = event.target.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = (e) => {
        document.getElementById('editProductoImagen').src = e.target.result;
    };
    reader.readAsDataURL(file);
}

function guardarCambiosProducto() {
    const id = document.getElementById('editProductoId').value;
    const nombre = document.getElementById('editProductoNombre').value.trim();
    const precio = document.getElementById('editProductoPrecio').value;
    const stock = document.getElementById('editProductoStock').value;
    const activo = document.getElementById('editProductoEstado').value;
    const imgInput = document.getElementById('editProductoImagenInput');

    if (!nombre || !precio || stock === '') {
        alert('Por favor rellena todos los campos obligatorios.');
        return;
    }

    const formData = new FormData();
    formData.append('id', id);
    formData.append('nombre', nombre);
    formData.append('precio', precio);
    formData.append('stock', stock);
    formData.append('activo', activo);
    if (imgInput.files[0]) {
        formData.append('imagen', imgInput.files[0]);
    }

    fetch('api/productos.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.ok) {
                cerrarModal('modalEditarProducto');
                cargarProductosAdmin().then(() => actualizarContadorProductos());
            } else {
                alert('Error al guardar los cambios: ' + (data.error ?? ''));
            }
        })
        .catch(err => console.error('Error guardando producto:', err));
}

function confirmarEliminarProducto(id, nombre) {
    if (confirm(`¿Seguro que quieres eliminar "${nombre}"?`)) {
        eliminarProducto(id);
    }
}

function eliminarProducto(id) {
    fetch(`api/productos.php?eliminar=${id}`, { method: 'DELETE' })
        .then(res => res.json())
        .then(data => {
            if (data.ok) {
                cargarProductosAdmin().then(() => actualizarContadorProductos());
            } else {
                alert('Error al eliminar el producto.');
            }
        })
        .catch(err => console.error('Error eliminando producto:', err));
}

function verProducto(id) {
    // Buscar el producto en la tabla del DOM
    const fila = document.querySelector(`tr [onclick="verProducto(${id})"]`).closest('tr');
    const celdas = fila.querySelectorAll('td');

    const nombre = celdas[2].textContent.trim();
    const categoria = celdas[3].textContent.trim();
    const precio = celdas[4].textContent.trim();
    const stock = celdas[5].textContent.trim();
    const estado = celdas[6].querySelector('.admin-badge')?.textContent.trim() ?? '—';
    const imgSrc = celdas[1].querySelector('img')?.src ?? 'webroot/img/logo.PNG';
    const imgEl = document.getElementById('verProductoImagen');
    imgEl.src = imgSrc;
    imgEl.alt = nombre;
    imgEl.style.cursor = 'zoom-in';
    imgEl.onclick = () => abrirImagenGrande(imgSrc, nombre);

    // Rellenar el modal
    document.getElementById('verProductoNombre').textContent = nombre;
    document.getElementById('verProductoCategoria').textContent = categoria;
    document.getElementById('verProductoPrecio').textContent = precio;
    document.getElementById('verProductoStock').textContent = stock;
    document.getElementById('verProductoEstado').innerHTML =
        estado === 'Activo'
            ? '<span class="admin-badge badge-activo">Activo</span>'
            : '<span class="admin-badge badge-inactivo">Inactivo</span>';

    // Abrir modal
    document.getElementById('modalVerProducto').style.display = 'flex';
}

function abrirImagenGrande(src, alt = '') {
    const overlay = document.createElement('div');
    overlay.style.cssText = `
        position: fixed; inset: 0; z-index: 9999;
        background: rgba(0,0,0,0.85);
        display: flex; align-items: center; justify-content: center;
        cursor: zoom-out; animation: fadeIn 0.15s ease;
    `;

    overlay.innerHTML = `
        <img src="${src}" alt="${alt}"
            style="max-width: 90vw; max-height: 90vh; object-fit: contain;
                   border-radius: 8px; box-shadow: 0 25px 60px rgba(0,0,0,0.5);">
    `;

    // Cerrar al hacer clic
    overlay.addEventListener('click', () => overlay.remove());

    // Cerrar con Escape
    const cerrarConEsc = (e) => { if (e.key === 'Escape') { overlay.remove(); document.removeEventListener('keydown', cerrarConEsc); } };
    document.addEventListener('keydown', cerrarConEsc);

    document.body.appendChild(overlay);
}

function actualizarContadorProductos() {
    // Contar filas que tienen el badge-activo
    const totalActivos = document.querySelectorAll('#adminContenido .badge-activo').length;

    // Actualizar la tarjeta del dashboard
    const tarjetas = document.querySelectorAll('.admin-stat-card');
    tarjetas.forEach(card => {
        if (card.querySelector('.admin-stat-label')?.textContent.includes('Total Productos Activos')) {
            card.querySelector('.admin-stat-value').textContent = totalActivos;
        }
    });
}

const HTML_DASHBOARD = `
    <div class="dashboard-graficos">
        <div class="grafico-card">
            <div class="grafico-header">
                <span class="grafico-titulo">Ventas últimos 7 días</span>
                <span id="dashTotalSemana" class="grafico-total">—</span>
            </div>
            <canvas id="graficaVentas" height="180"></canvas>
        </div>
        <div class="grafico-card">
            <div class="grafico-header">
                <span class="grafico-titulo">Ventas últimos 7 días</span>
                <span id="dashTotalPedidos" class="grafico-total">—</span>
            </div>
            <canvas id="graficaPedidos" height="180"></canvas>
        </div>
    </div>`;

function cargarGraficoDashboard() {
    fetch('api/ventas.php')
        .then(res => res.json())
        .then(data => {
            const labels = data.map(d => {
                const fecha = new Date(d.dia);
                return fecha.toLocaleDateString('es-ES', { weekday: 'short', day: 'numeric', month: 'short' });
            });
            const ventas = data.map(d => parseFloat(d.total));
            const pedidos = data.map(d => parseInt(d.pedidos));

            // Totales
            const totalVentas = ventas.reduce((a, b) => a + b, 0);
            const totalPedidos = pedidos.reduce((a, b) => a + b, 0);
            document.getElementById('dashTotalSemana').textContent =
                totalVentas.toFixed(2).replace('.', ',') + ' €';
            document.getElementById('dashTotalPedidos').textContent = totalPedidos + ' pedidos';

            // Opciones comunes
            const opcionesComunes = {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false } },
                    y: { beginAtZero: true, grid: { color: '#f0f2f5' } }
                }
            };

            // Gráfica ventas
            new Chart(document.getElementById('graficaVentas'), {
                type: 'bar',
                data: {
                    labels,
                    datasets: [{
                        data: ventas,
                        backgroundColor: 'rgba(5, 150, 105, 0.08)',
                        borderColor: '#059669',
                        borderWidth: 2,
                        borderRadius: 6,
                    }]
                },
                options: {
                    ...opcionesComunes,
                    scales: {
                        ...opcionesComunes.scales,
                        y: { ...opcionesComunes.scales.y, ticks: { callback: v => v + ' €' } }
                    }
                }
            });

            // Gráfica pedidos
            new Chart(document.getElementById('graficaPedidos'), {
                type: 'line',
                data: {
                    labels,
                    datasets: [{
                        data: pedidos,
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.15)',
                        borderWidth: 2,
                        pointBackgroundColor: '#3b82f6',
                        pointRadius: 4,
                        fill: true,
                        tension: 0.3
                    }]
                },
                options: opcionesComunes
            });
        })
        .catch(err => console.error('Error cargando gráficas:', err));
}