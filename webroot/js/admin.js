// ======================== RENDER PRODUCTOS (MODO TABLA - ADMIN) ========================

/**
 * Renderiza un array de productos en formato tabla dentro del panel de administración.
 * Genera la cabecera con buscador y botón de nuevo producto, y luego una tabla HTML
 * con columnas de ID, imagen, nombre, categoría, precio, stock, estado y acciones.
 *
 * @param {Array<Object>} productos - Array de objetos producto devueltos por la API.
 *   Cada objeto debe contener: id, nombre, precio, stock, imagen, categoria, activo.
 */
function renderProductosAdmin(productos) {
    // Obtener el contenedor principal donde se inyectará la tabla de productos.
    const contenedor = document.getElementById('adminContenido');

    // Si no hay productos, mostrar un mensaje informativo y salir.
    if (!productos || productos.length === 0) {
        contenedor.innerHTML = '<p class="sin-productos">No hay productos disponibles.</p>';
        return;
    }

    // Construir la cabecera de la tabla: campo de búsqueda y botón "Nuevo Producto".
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

    // Iterar sobre cada producto para generar las filas de la tabla.
    productos.forEach(prod => {
        // Formatear el precio con 2 decimales y coma como separador decimal (formato europeo).
        const precioFmt = parseFloat(prod.precio).toFixed(2).replace('.', ',');

        // Usar la imagen del producto si existe; de lo contrario, usar el logo por defecto.
        const imgSrc = prod.imagen && prod.imagen !== '' ? prod.imagen : 'webroot/img/logo.PNG';

        // Determinar la clase CSS del badge de stock según el nivel de inventario.
        let stockBadge = '';
        if (prod.stock <= 0) {
            stockBadge = 'badge-agotado';    // Sin stock: badge rojo "agotado".
        } else if (prod.stock <= 3) {
            stockBadge = 'badge-bajo';       // Stock bajo (<=3): badge de advertencia.
        } else {
            stockBadge = 'badge-ok';         // Stock suficiente: badge verde.
        }

        // Determinar el badge de estado (activo/inactivo) según el campo 'activo' del producto.
        const estadoHtml = prod.activo == 1
            ? `<span class="admin-badge badge-activo">Activo</span>`
            : `<span class="admin-badge badge-inactivo">Inactivo</span>`;

        // Generar la fila HTML del producto.
        // - Las filas de productos agotados reciben la clase 'fila-agotada' (estilo atenuado).
        // - Las filas de productos inactivos reciben la clase 'fila-inactiva'.
        // - Cada fila incluye botones de acción: ver, editar y eliminar.
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

    // Cerrar las etiquetas de la tabla y del contenedor wrapper.
    html += `
                </tbody>
            </table>
        </div>`;

    // Inyectar todo el HTML generado en el contenedor del panel de administración.
    contenedor.innerHTML = html;
}

// ======================== FUNCIONES DE MODAL ========================

/**
 * Cierra un modal ocultándolo (display: none).
 *
 * @param {string} id - El ID del elemento modal a cerrar.
 */
function cerrarModal(id) {
    document.getElementById(id).style.display = 'none';
}

/**
 * Abre un modal mostrándolo con display flex (centrado).
 *
 * @param {string} id - El ID del elemento modal a abrir.
 */
function abrirModal(id) {
    document.getElementById(id).style.display = 'flex';
}

// ======================== CARGAR PRODUCTOS EN PANEL ADMIN ========================

/**
 * Carga los productos desde la API y los renderiza en la tabla del panel de administración.
 * Permite filtrar por categoría y por texto de búsqueda.
 *
 * @param {string|number} idCategoria - ID de la categoría a filtrar, o 'todas' para mostrar todas.
 * @param {string} textoBusqueda - Texto de búsqueda para filtrar productos por nombre.
 * @returns {Promise} - Promesa que se resuelve cuando los productos han sido renderizados.
 */
function cargarProductosAdmin(idCategoria = 'todas', textoBusqueda = '') {
    // Construir los parámetros de la petición.
    const params = new URLSearchParams();
    params.append('idCategoria', idCategoria);
    params.append('admin', '1');  // Indicar que es una petición desde el panel admin (incluye productos inactivos).
    if (textoBusqueda) params.append('buscarProducto', textoBusqueda);

    // Realizar la petición AJAX a la API de productos.
    return fetch('api/productos.php?' + params.toString())
        .then(res => res.json())                         // Parsear la respuesta JSON.
        .then(data => renderProductosAdmin(data))        // Renderizar los productos en la tabla.
        .catch(err => {
            // En caso de error, mostrar mensaje en el contenedor y registrar en consola.
            console.error('Error cargando productos (admin):', err);
            document.getElementById('adminContenido').innerHTML =
                '<p class="sin-productos">Error al cargar los productos.</p>';
        });
}

// ======================== ACCIONES ADMIN ========================

/**
 * Abre el formulario/modal para crear un nuevo producto.
 * TODO: Implementar la apertura del modal de creación.
 */
function nuevoProducto() {
    // TODO: abrir modal de creación
    console.log('Nuevo producto');
}

/**
 * Abre el modal de edición de un producto, rellenando los campos del formulario
 * con los datos actuales del producto extraídos de la fila de la tabla.
 *
 * @param {number} id - El ID del producto a editar.
 */
function editarProducto(id) {
    // Localizar la fila de la tabla que contiene el botón de editar para este producto.
    const fila = document.querySelector(`tr [onclick="editarProducto(${id})"]`).closest('tr');
    const celdas = fila.querySelectorAll('td');

    // Extraer los datos del producto desde las celdas de la fila.
    const nombre = celdas[2].textContent.trim();
    const categoria = celdas[3].textContent.trim();
    const precio = celdas[4].textContent.replace('€', '').replace(',', '.').trim(); // Convertir formato europeo a decimal.
    const stock = celdas[5].textContent.trim();
    const activo = celdas[6].querySelector('.badge-activo') ? 1 : 0; // Determinar si está activo por la presencia del badge.
    const imgSrc = celdas[1].querySelector('img')?.src ?? 'webroot/img/logo.PNG';

    // Guardar el ID del producto en un campo oculto para usarlo al guardar los cambios.
    document.getElementById('editProductoId').value = id;

    // Rellenar la imagen de previsualización del modal.
    const imgEl = document.getElementById('editProductoImagen');
    imgEl.src = imgSrc;
    imgEl.alt = nombre;

    // Rellenar los campos del formulario de edición con los datos del producto.
    document.getElementById('editProductoNombre').value = nombre;
    document.getElementById('editProductoCategoria').value = categoria;
    document.getElementById('editProductoPrecio').value = precio;
    document.getElementById('editProductoStock').value = stock;
    document.getElementById('editProductoEstado').value = activo;

    // Limpiar el input de archivo de imagen por si había una previsualización anterior.
    document.getElementById('editProductoImagenInput').value = '';

    // Abrir el modal de edición.
    abrirModal('modalEditarProducto');
}

/**
 * Previsualiza la imagen seleccionada por el usuario en el input de archivo,
 * mostrándola en el elemento <img> del modal de edición.
 *
 * @param {Event} event - El evento 'change' del input de tipo file.
 */
function previsualizarImagen(event) {
    const file = event.target.files[0];
    if (!file) return; // Si no se seleccionó ningún archivo, no hacer nada.

    // Usar FileReader para leer el archivo como Data URL y mostrarlo en la imagen.
    const reader = new FileReader();
    reader.onload = (e) => {
        document.getElementById('editProductoImagen').src = e.target.result;
    };
    reader.readAsDataURL(file);
}

/**
 * Guarda los cambios realizados en el formulario de edición de un producto.
 * Recoge los valores de los campos, valida que los obligatorios estén rellenos,
 * construye un FormData (para soportar subida de imagen) y envía la petición POST a la API.
 */
function guardarCambiosProducto() {
    // Recoger los valores de todos los campos del formulario de edición.
    const id = document.getElementById('editProductoId').value;
    const nombre = document.getElementById('editProductoNombre').value.trim();
    const precio = document.getElementById('editProductoPrecio').value;
    const stock = document.getElementById('editProductoStock').value;
    const activo = document.getElementById('editProductoEstado').value;
    const imgInput = document.getElementById('editProductoImagenInput');

    // Validar que los campos obligatorios no estén vacíos.
    if (!nombre || !precio || stock === '') {
        alert('Por favor rellena todos los campos obligatorios.');
        return;
    }

    // Construir un FormData para enviar los datos, incluyendo la imagen si se seleccionó una nueva.
    const formData = new FormData();
    formData.append('id', id);
    formData.append('nombre', nombre);
    formData.append('precio', precio);
    formData.append('stock', stock);
    formData.append('activo', activo);
    if (imgInput.files[0]) {
        formData.append('imagen', imgInput.files[0]); // Adjuntar el archivo de imagen si existe.
    }

    // Enviar la petición POST a la API de productos con los datos del formulario.
    fetch('api/productos.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.ok) {
                // Si la operación fue exitosa, cerrar el modal y recargar la tabla de productos.
                cerrarModal('modalEditarProducto');
                cargarProductosAdmin().then(() => actualizarContadorProductos());
            } else {
                // Mostrar el mensaje de error devuelto por la API.
                alert('Error al guardar los cambios: ' + (data.error ?? ''));
            }
        })
        .catch(err => console.error('Error guardando producto:', err));
}

/**
 * Muestra un diálogo de confirmación antes de eliminar un producto.
 *
 * @param {number} id - El ID del producto a eliminar.
 * @param {string} nombre - El nombre del producto (se muestra en el mensaje de confirmación).
 */
function confirmarEliminarProducto(id, nombre) {
    if (confirm(`¿Seguro que quieres eliminar "${nombre}"?`)) {
        eliminarProducto(id);
    }
}

/**
 * Elimina un producto enviando una petición DELETE a la API.
 * Si la eliminación es exitosa, recarga la tabla de productos y actualiza el contador.
 *
 * @param {number} id - El ID del producto a eliminar.
 */
function eliminarProducto(id) {
    // Enviar petición DELETE a la API con el ID del producto como parámetro.
    fetch(`api/productos.php?eliminar=${id}`, { method: 'DELETE' })
        .then(res => res.json())
        .then(data => {
            if (data.ok) {
                // Recargar la tabla y actualizar el contador de productos activos.
                cargarProductosAdmin().then(() => actualizarContadorProductos());
            } else {
                alert('Error al eliminar el producto.');
            }
        })
        .catch(err => console.error('Error eliminando producto:', err));
}

/**
 * Abre el modal de detalle (solo lectura) de un producto, mostrando toda su información.
 * Los datos se extraen directamente de la fila de la tabla en el DOM.
 *
 * @param {number} id - El ID del producto a visualizar.
 */
function verProducto(id) {
    // Buscar la fila del producto en la tabla del DOM a partir del botón "ver".
    const fila = document.querySelector(`tr [onclick="verProducto(${id})"]`).closest('tr');
    const celdas = fila.querySelectorAll('td');

    // Extraer los datos del producto desde las celdas de la fila.
    const nombre = celdas[2].textContent.trim();
    const categoria = celdas[3].textContent.trim();
    const precio = celdas[4].textContent.trim();
    const stock = celdas[5].textContent.trim();
    const estado = celdas[6].querySelector('.admin-badge')?.textContent.trim() ?? '—';
    const imgSrc = celdas[1].querySelector('img')?.src ?? 'webroot/img/logo.PNG';

    // Configurar la imagen del modal con funcionalidad de zoom al hacer clic.
    const imgEl = document.getElementById('verProductoImagen');
    imgEl.src = imgSrc;
    imgEl.alt = nombre;
    imgEl.style.cursor = 'zoom-in';
    imgEl.onclick = () => abrirImagenGrande(imgSrc, nombre);

    // Rellenar los campos de texto del modal con los datos del producto.
    document.getElementById('verProductoNombre').textContent = nombre;
    document.getElementById('verProductoCategoria').textContent = categoria;
    document.getElementById('verProductoPrecio').textContent = precio;
    document.getElementById('verProductoStock').textContent = stock;

    // Mostrar el badge de estado (activo/inactivo) con el estilo correspondiente.
    document.getElementById('verProductoEstado').innerHTML =
        estado === 'Activo'
            ? '<span class="admin-badge badge-activo">Activo</span>'
            : '<span class="admin-badge badge-inactivo">Inactivo</span>';

    // Abrir el modal de visualización del producto.
    document.getElementById('modalVerProducto').style.display = 'flex';
}

/**
 * Abre una imagen a pantalla completa en un overlay oscuro.
 * El overlay se cierra al hacer clic sobre él o al pulsar la tecla Escape.
 *
 * @param {string} src - La URL de la imagen a mostrar en grande.
 * @param {string} alt - Texto alternativo para la imagen (accesibilidad).
 */
function abrirImagenGrande(src, alt = '') {
    // Crear el elemento overlay que cubrirá toda la pantalla con fondo oscuro semitransparente.
    const overlay = document.createElement('div');
    overlay.style.cssText = `
        position: fixed; inset: 0; z-index: 9999;
        background: rgba(0,0,0,0.85);
        display: flex; align-items: center; justify-content: center;
        cursor: zoom-out; animation: fadeIn 0.15s ease;
    `;

    // Insertar la imagen centrada dentro del overlay con estilos de presentación.
    overlay.innerHTML = `
        <img src="${src}" alt="${alt}"
            style="max-width: 90vw; max-height: 90vh; object-fit: contain;
                   border-radius: 8px; box-shadow: 0 25px 60px rgba(0,0,0,0.5);">
    `;

    // Cerrar el overlay al hacer clic en cualquier parte de él.
    overlay.addEventListener('click', () => overlay.remove());

    // Cerrar el overlay al pulsar la tecla Escape.
    const cerrarConEsc = (e) => {
        if (e.key === 'Escape') {
            overlay.remove();
            document.removeEventListener('keydown', cerrarConEsc); // Limpiar el listener tras cerrar.
        }
    };
    document.addEventListener('keydown', cerrarConEsc);

    // Añadir el overlay al body del documento para mostrarlo.
    document.body.appendChild(overlay);
}

/**
 * Actualiza el contador de productos activos en la tarjeta del dashboard.
 * Cuenta las filas de la tabla que tienen el badge 'badge-activo' y actualiza
 * el valor mostrado en la tarjeta correspondiente del panel de estadísticas.
 */
function actualizarContadorProductos() {
    // Contar el número de badges "activo" presentes en la tabla de productos.
    const totalActivos = document.querySelectorAll('#adminContenido .badge-activo').length;

    // Buscar la tarjeta de estadísticas que muestra "Total Productos Activos" y actualizar su valor.
    const tarjetas = document.querySelectorAll('.admin-stat-card');
    tarjetas.forEach(card => {
        if (card.querySelector('.admin-stat-label')?.textContent.includes('Total Productos Activos')) {
            card.querySelector('.admin-stat-value').textContent = totalActivos;
        }
    });
}

// ======================== DASHBOARD - HTML Y GRÁFICOS ========================

/**
 * Plantilla HTML del dashboard con dos tarjetas de gráficos:
 * - Gráfico de barras: ventas (en €) de los últimos 7 días.
 * - Gráfico de líneas: número de pedidos de los últimos 7 días.
 * Cada tarjeta incluye un <canvas> donde Chart.js renderizará el gráfico.
 */
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

/**
 * Carga los datos de ventas de los últimos 7 días desde la API y renderiza
 * dos gráficos en el dashboard usando la librería Chart.js:
 * 1. Gráfico de barras con el total de ventas en euros por día.
 * 2. Gráfico de líneas con el número de pedidos por día.
 * También actualiza los totales acumulados en las cabeceras de cada gráfico.
 */
function cargarGraficoDashboard() {
    // Realizar la petición AJAX a la API de ventas para obtener los datos de la última semana.
    fetch('api/ventas.php')
        .then(res => res.json())
        .then(data => {
            // Preparar las etiquetas del eje X: día de la semana abreviado + número + mes.
            const labels = data.map(d => {
                const fecha = new Date(d.dia);
                return fecha.toLocaleDateString('es-ES', { weekday: 'short', day: 'numeric', month: 'short' });
            });

            // Extraer los valores de ventas (€) y pedidos de cada día.
            const ventas = data.map(d => parseFloat(d.total));
            const pedidos = data.map(d => parseInt(d.pedidos));

            // Calcular los totales acumulados de la semana.
            const totalVentas = ventas.reduce((a, b) => a + b, 0);
            const totalPedidos = pedidos.reduce((a, b) => a + b, 0);

            // Mostrar los totales en las cabeceras de los gráficos.
            document.getElementById('dashTotalSemana').textContent =
                totalVentas.toFixed(2).replace('.', ',') + ' €';
            document.getElementById('dashTotalPedidos').textContent = totalPedidos + ' pedidos';

            // Definir opciones comunes para ambos gráficos (responsive, sin leyenda, ejes).
            const opcionesComunes = {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false } },           // Sin líneas de cuadrícula en el eje X.
                    y: { beginAtZero: true, grid: { color: '#f0f2f5' } } // Eje Y desde 0 con cuadrícula suave.
                }
            };

            // Crear el gráfico de barras para las ventas en euros.
            new Chart(document.getElementById('graficaVentas'), {
                type: 'bar',
                data: {
                    labels,
                    datasets: [{
                        data: ventas,
                        backgroundColor: 'rgba(5, 150, 105, 0.08)',  // Fondo verde claro semitransparente.
                        borderColor: '#059669',                       // Borde verde.
                        borderWidth: 2,
                        borderRadius: 6,                              // Bordes redondeados en las barras.
                    }]
                },
                options: {
                    ...opcionesComunes,
                    scales: {
                        ...opcionesComunes.scales,
                        y: { ...opcionesComunes.scales.y, ticks: { callback: v => v + ' €' } } // Añadir símbolo € a las etiquetas del eje Y.
                    }
                }
            });

            // Crear el gráfico de líneas para el número de pedidos.
            new Chart(document.getElementById('graficaPedidos'), {
                type: 'line',
                data: {
                    labels,
                    datasets: [{
                        data: pedidos,
                        borderColor: '#3b82f6',                       // Línea azul.
                        backgroundColor: 'rgba(59, 130, 246, 0.15)',  // Relleno azul claro semitransparente.
                        borderWidth: 2,
                        pointBackgroundColor: '#3b82f6',              // Puntos azules.
                        pointRadius: 4,                               // Tamaño de los puntos.
                        fill: true,                                   // Rellenar el área bajo la línea.
                        tension: 0.3                                  // Suavizado de la curva.
                    }]
                },
                options: opcionesComunes
            });
        })
        .catch(err => console.error('Error cargando gráficas:', err));
}
