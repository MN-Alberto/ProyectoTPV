// ======================== RENDER PRODUCTOS (MODO TABLA - ADMIN) ========================

// Variable global para guardar el header HTML (con el input de búsqueda fijo)
let adminTablaHeaderHTML = '';

// Variable para guardar las categorías
let categoriasAdmin = [];

// Variable para identificar la sección actual
let seccionActual = '';

/**
 * Carga las categorías desde la API y las guarda en la variable global.
 */
function cargarCategoriasAdmin() {
    return fetch('api/categorias.php')
        .then(res => res.json())
        .then(data => {
            categoriasAdmin = data;
            return data;
        })
        .catch(err => console.error('Error cargando categorías:', err));
}

/**
 * Genera el HTML del header con el input de búsqueda y el select de categorías (solo se genera una vez).
 */
// Opciones de ordenación
const OPCIONES_ORDEN = [
    { value: '', text: 'Sin orden' },
    { value: 'nombre_asc', text: 'Orden alfabético A-Z' },
    { value: 'nombre_desc', text: 'Orden alfabético Z-A' },
    { value: 'id_asc', text: 'ID menor a mayor' },
    { value: 'id_desc', text: 'ID mayor a menor' },
    { value: 'precio_asc', text: 'Precio menor a mayor' },
    { value: 'precio_desc', text: 'Precio mayor a menor' },
    { value: 'stock_asc', text: 'Stock menor a mayor' },
    { value: 'stock_desc', text: 'Stock mayor a menor' }
];

function getAdminTablaHeader(textoBusqueda = '', idCategoriaSeleccionada = '', ordenSeleccionado = '') {
    // Generar las opciones del select de categorías
    let opcionesCategorias = '<option value="todas">Todas</option>';
    categoriasAdmin.forEach(cat => {
        const selected = cat.id == idCategoriaSeleccionada ? 'selected' : '';
        opcionesCategorias += `<option value="${cat.id}" ${selected}>${cat.nombre}</option>`;
    });

    // Generar las opciones del select de ordenación
    let opcionesOrden = '';
    OPCIONES_ORDEN.forEach(opc => {
        const selected = opc.value == ordenSeleccionado ? 'selected' : '';
        opcionesOrden += `<option value="${opc.value}" ${selected}>${opc.text}</option>`;
    });

    return `
        <div class="admin-tabla-header">
            <div style="display: flex; gap: 10px; width: 100%; align-items: center; flex-wrap: wrap;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <label for="inputBuscarProducto" class="admin-label">Buscar:</label>
                    <input type="text" id="inputBuscarProducto" class="input-buscarProducto"
                        placeholder="Escribe el nombre del producto..." oninput="buscarProductos()" autocomplete="off"
                        value="${textoBusqueda.replace(/"/g, '&quot;')}" style="width: 400px;" />
                </div>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <label for="selectCategoria" class="admin-label">Categoría:</label>
                    <select id="selectCategoria" onchange="buscarProductos()" style="padding: 8px; border-radius: 4px; border: 1px solid #d1d5db;">
                        ${opcionesCategorias}
                    </select>
                </div>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <label for="selectOrden" class="admin-label">Ordenar:</label>
                    <select id="selectOrden" onchange="buscarProductos()" style="padding: 8px; border-radius: 4px; border: 1px solid #d1d5db;">
                        ${opcionesOrden}
                    </select>
                </div>
                <button class="btn-admin-accion btn-nuevo" onclick="nuevoProducto()">
                    <i class="fas fa-plus"></i> Nuevo Producto
                </button>
            </div>
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
}

/**
 * Renderiza un array de productos en formato tabla dentro del panel de administración.
 * El header con el input de búsqueda se genera solo la primera vez y se mantiene fijo.
 *
 * @param {Array<Object>} productos - Array de objetos producto devueltos por la API.
 * @param {boolean} esPrimeraVez - Indica si es la primera vez que se renderiza.
 */
function renderProductosAdmin(productos, esPrimeraVez = true, idCategoria = '', orden = '') {
    // Obtener el contenedor principal donde se inyectará la tabla de productos.
    const contenedor = document.getElementById('adminContenido');

    // Si no hay productos, mostrar un mensaje informativo.
    if (!productos || productos.length === 0) {
        if (esPrimeraVez || adminTablaHeaderHTML === '') {
            // Primera vez o header vacío: generar estructura completa con mensaje
            adminTablaHeaderHTML = getAdminTablaHeader('', idCategoria, orden);
            contenedor.innerHTML = adminTablaHeaderHTML + '<tr><td colspan="8" class="sin-productos">No hay productos disponibles.</td></tr></tbody></table></div>';
        } else {
            // Búsquedas posteriores: solo actualizar el tbody
            const tbody = contenedor.querySelector('tbody');
            if (tbody) tbody.innerHTML = '<tr><td colspan="8" class="sin-productos">No hay productos disponibles.</td></tr>';
        }
        return;
    }

    // Si es la primera vez o el header está vacío, generar el header completo
    if (esPrimeraVez || adminTablaHeaderHTML === '') {
        adminTablaHeaderHTML = getAdminTablaHeader('', idCategoria, orden);
    }

    // Ordenar los productos según el criterio seleccionado
    if (orden) {
        const [campo, direccion] = orden.split('_');
        const esAscendente = direccion === 'asc';

        productos.sort((a, b) => {
            let valorA, valorB;

            switch (campo) {
                case 'nombre':
                    valorA = a.nombre.toLowerCase();
                    valorB = b.nombre.toLowerCase();
                    break;
                case 'id':
                    valorA = a.id;
                    valorB = b.id;
                    break;
                case 'precio':
                    valorA = a.precio;
                    valorB = b.precio;
                    break;
                case 'stock':
                    valorA = a.stock;
                    valorB = b.stock;
                    break;
                default:
                    return 0;
            }

            if (valorA < valorB) return esAscendente ? -1 : 1;
            if (valorA > valorB) return esAscendente ? 1 : -1;
            return 0;
        });
    }

    // Usar el header guardado (input fijo)
    let html = adminTablaHeaderHTML;

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
    if (esPrimeraVez) {
        // Primera vez: reemplazar todo el contenido
        contenedor.innerHTML = html;
    } else {
        // Búsquedas posteriores: solo actualizar el tbody para mantener el input fijo
        const tbody = contenedor.querySelector('tbody');
        if (tbody) {
            // Extraer solo el tbody del HTML generado
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = html;
            const nuevoTbody = tempDiv.querySelector('tbody');
            tbody.innerHTML = nuevoTbody.innerHTML;
        } else {
            // Si no existe tbody, reemplazar todo
            contenedor.innerHTML = html;
        }
    }
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
function cargarProductosAdmin(idCategoria = 'todas', textoBusqueda = '', orden = '') {
    // Verificar si ya existe la tabla (es una búsqueda, no la primera carga)
    const contenedor = document.getElementById('adminContenido');
    const tablaExistente = contenedor.querySelector('.admin-tabla');

    // Si la sección actual no es productos, forzamos primera carga para regenerar el header
    if (seccionActual !== 'productos') {
        adminTablaHeaderHTML = '';
        seccionActual = 'productos';
    }

    const esPrimeraVez = !tablaExistente || adminTablaHeaderHTML === '';

    // Guardar el orden actual para pasarlo al render
    window.ordenActual = orden;

    // Construir los parámetros de la petición.
    const params = new URLSearchParams();
    params.append('idCategoria', idCategoria);
    params.append('admin', '1');  // Indicar que es una petición desde el panel admin (incluye productos inactivos).
    if (textoBusqueda) params.append('buscarProducto', textoBusqueda);

    // Realizar la petición AJAX a la API de productos.
    return fetch('api/productos.php?' + params.toString())
        .then(res => res.json())                         // Parsear la respuesta JSON.
        .then(data => renderProductosAdmin(data, esPrimeraVez, idCategoria, orden))  // Renderizar los productos en la tabla.
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
 */
function nuevoProducto() {
    // Limpiar el formulario para crear un nuevo producto
    document.getElementById('editProductoId').value = '';
    document.getElementById('editProductoNombre').value = '';
    document.getElementById('editProductoPrecio').value = '';
    document.getElementById('editProductoStock').value = '';
    document.getElementById('editProductoEstado').value = '1';
    document.getElementById('editProductoImagen').src = 'webroot/img/logo.PNG';
    document.getElementById('editProductoImagen').alt = '';
    document.getElementById('editProductoImagenInput').value = '';

    // Cambiar el título y subtítulo
    document.getElementById('editProductoTitulo').textContent = 'Nuevo Producto';
    document.getElementById('editProductoSubtitulo').textContent = 'Completa los datos del nuevo producto';

    // Cargar las categorías en el select
    const selectCategoria = document.getElementById('editProductoCategoria');
    selectCategoria.innerHTML = '<option value="">Selecciona una categoría</option>';
    categoriasAdmin.forEach(cat => {
        selectCategoria.innerHTML += `<option value="${cat.nombre}">${cat.nombre}</option>`;
    });
    selectCategoria.style.background = '#fff';
    selectCategoria.style.cursor = 'pointer';

    // Abrir el modal de edición (que sirve tanto para crear como para editar)
    abrirModal('modalEditarProducto');
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

    // Cambiar el título y subtítulo para modo edición
    document.getElementById('editProductoTitulo').textContent = 'Editar Producto';
    document.getElementById('editProductoSubtitulo').textContent = 'Modifica los datos del producto';

    // Hacer la categoría de solo lectura para productos existentes
    const selectCategoria = document.getElementById('editProductoCategoria');
    selectCategoria.innerHTML = `<option value="${categoria}">${categoria}</option>`;
    selectCategoria.style.background = '#f3f4f6';
    selectCategoria.style.cursor = 'not-allowed';

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
    const categoria = document.getElementById('editProductoCategoria').value;
    const precio = document.getElementById('editProductoPrecio').value;
    const stock = document.getElementById('editProductoStock').value;
    const activo = document.getElementById('editProductoEstado').value;
    const imgInput = document.getElementById('editProductoImagenInput');

    // Validar que los campos obligatorios no estén vacíos.
    if (!nombre || !categoria || !precio || stock === '') {
        alert('Por favor rellena todos los campos obligatorios.');
        return;
    }

    // Construir un FormData para enviar los datos, incluyendo la imagen si se seleccionó una nueva.
    const formData = new FormData();
    formData.append('id', id);
    formData.append('nombre', nombre);
    formData.append('categoria', categoria);
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

// ======================== GESTIÓN DE USUARIOS ========================

/**
 * Carga los usuarios desde la API y los renderiza en la tabla del panel de administración.
 */
function cargarUsuariosAdmin() {
    const contenedor = document.getElementById('adminContenido');
    const tablaExistente = contenedor.querySelector('.admin-tabla');

    if (seccionActual !== 'usuarios') {
        adminTablaHeaderHTML = '';
        seccionActual = 'usuarios';
    }

    const esPrimeraVez = !tablaExistente || adminTablaHeaderHTML === '';

    return fetch('api/usuarios.php')
        .then(res => {
            if (!res.ok) {
                return res.json().then(err => { throw new Error(err.error || 'Error al cargar usuarios'); });
            }
            return res.json();
        })
        .then(data => renderUsuariosAdmin(data, esPrimeraVez))
        .catch(err => {
            console.error('Error cargando usuarios:', err);
            document.getElementById('adminContenido').innerHTML =
                '<p class="sin-productos">' + err.message + '</p>';
        });
}

/**
 * Genera el HTML del header de la tabla de usuarios.
 */
function getUsuariosTablaHeader(textoBusqueda = '') {
    return `
        <div class="admin-tabla-header">
            <div style="display: flex; gap: 10px; width: 100%; align-items: center; flex-wrap: wrap;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <label for="inputBuscarUsuario" class="admin-label">Buscar:</label>
                    <input type="text" id="inputBuscarUsuario" class="input-buscarUsuario"
                        placeholder="Escribe el nombre del usuario..." oninput="buscarUsuarios()" autocomplete="off"
                        value="${textoBusqueda.replace(/"/g, '&quot;')}" style="width: 300px;" />
                </div>
                <button class="btn-admin-accion btn-nuevo" onclick="prepararNuevoUsuario()">
                    <i class="fas fa-plus"></i> Nuevo Usuario
                </button>
            </div>
        </div>
        <div class="admin-tabla-wrapper">
            <table class="admin-tabla">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Rol</th>
                        <th>Fecha de Alta</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>`;
}

/**
 * Renderiza un array de usuarios en formato tabla.
 * @param {Array} usuarios - Array de objetos usuario.
 * @param {boolean} esPrimeraVez - Indica si es la primera vez que se renderiza.
 */
function renderUsuariosAdmin(usuarios, esPrimeraVez = true) {
    const contenedor = document.getElementById('adminContenido');

    // Si no hay usuarios, mostrar mensaje
    if (!usuarios || usuarios.length === 0) {
        if (esPrimeraVez || adminTablaHeaderHTML === '') {
            adminTablaHeaderHTML = getUsuariosTablaHeader();
            contenedor.innerHTML = adminTablaHeaderHTML +
                '<tr><td colspan="7" class="sin-productos">No hay usuarios disponibles.</td></tr></tbody></table></div>';
        } else {
            // Solo actualizar tbody
            const tbody = contenedor.querySelector('tbody');
            if (tbody) tbody.innerHTML = '<tr><td colspan="7" class="sin-productos">No hay usuarios disponibles.</td></tr>';
        }
        return;
    }

    // Si es la primera vez o header vacío, generar header completo
    if (esPrimeraVez || adminTablaHeaderHTML === '') {
        adminTablaHeaderHTML = getUsuariosTablaHeader();
    }

    let html = adminTablaHeaderHTML;

    usuarios.forEach(usr => {
        const fechaAlta = new Date(usr.fechaAlta).toLocaleDateString('es-ES', {
            day: '2-digit', month: '2-digit', year: 'numeric'
        });

        const rolBadge = usr.rol === 'admin'
            ? '<span class="admin-badge" style="background: #dbeafe; color: #1e40af;">Admin</span>'
            : '<span class="admin-badge" style="background: #f3f4f6; color: #374151;">Empleado</span>';

        const estadoHtml = usr.activo === 1
            ? '<span class="admin-badge badge-activo">Activo</span>'
            : '<span class="admin-badge badge-inactivo">Inactivo</span>';

        html += `
            <tr>
                <td class="col-id">${usr.id}</td>
                <td class="col-nombre">${usr.nombre}</td>
                <td class="col-email">${usr.email}</td>
                <td class="col-rol">${rolBadge}</td>
                <td class="col-fecha">${fechaAlta}</td>
                <td class="col-estado">${estadoHtml}</td>
                <td class="col-acciones">
                    <button class="btn-admin-accion btn-ver" onclick="verUsuario(${usr.id})" title="Ver">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn-admin-accion btn-editar" onclick="editarUsuario(${usr.id})" title="Editar">
                        <i class="fas fa-pen"></i>
                    </button>
                    ${usr.rol !== 'admin' ? `
                    <button class="btn-admin-accion btn-eliminar" onclick="confirmarEliminarUsuario(${usr.id}, '${usr.nombre.replace(/'/g, "\\'")}')" title="Eliminar">
                        <i class="fas fa-trash"></i>
                    </button>` : ''}
                </td>
            </tr>`;
    });

    html += `
                </tbody>
            </table>
        </div>`;

    // Si es la primera vez, reemplazar todo el contenido
    if (esPrimeraVez) {
        contenedor.innerHTML = html;
    } else {
        // Solo actualizar tbody
        const tbody = contenedor.querySelector('tbody');
        if (tbody) {
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = html;
            const nuevoTbody = tempDiv.querySelector('tbody');
            tbody.innerHTML = nuevoTbody.innerHTML;
        } else {
            contenedor.innerHTML = html;
        }
    }
}

/**
 * Busca usuarios por nombre con debounce.
 */
function buscarUsuarios() {
    // Cancelar la búsqueda anterior si el usuario sigue escribiendo
    clearTimeout(debounceTimerUsuarios);

    // Establecer un nuevo temporizador de 300ms
    debounceTimerUsuarios = setTimeout(() => {
        const texto = document.getElementById('inputBuscarUsuario').value;
        const params = new URLSearchParams();
        if (texto) params.append('buscar', texto);

        fetch('api/usuarios.php?' + params.toString())
            .then(res => {
                if (!res.ok) {
                    return res.json().then(err => { throw new Error(err.error || 'Error al buscar'); });
                }
                return res.json();
            })
            .then(data => renderUsuariosAdmin(data, false))
            .catch(err => {
                console.error('Error buscando usuarios:', err);
                document.getElementById('adminContenido').innerHTML =
                    '<p class="sin-productos">Error: ' + err.message + '</p>';
            });
    }, 300); // 300ms de espera después de que el usuario deje de escribir
}

/**
 * Abre el formulario para crear un nuevo usuario.
 */
nuevoUsuario = () => {
    // Limpiar el formulario
    document.getElementById('editUsuarioId').value = '';
    document.getElementById('editUsuarioNombre').value = '';
    document.getElementById('editUsuarioEmail').value = '';
    document.getElementById('editUsuarioPassword').value = '';
    document.getElementById('editUsuarioPassword').required = true;
    document.getElementById('editUsuarioRol').value = 'empleado';
    document.getElementById('editUsuarioEstado').value = '1';
    document.getElementById('editUsuarioTitulo').textContent = 'Nuevo Usuario';
    document.getElementById('editUsuarioPassword').closest('.editar-prod-fila').querySelector('label').innerHTML = 'Password <span style="color:red">*</span>';

    abrirModal('modalEditarUsuario');
};

/**
 * Abre el modal de detalle de un usuario.
 */
function verUsuario(id) {
    fetch(`api/usuarios.php?id=${id}`)
        .then(res => res.json())
        .then(data => {
            if (data.ok === false) {
                alert(data.error);
                return;
            }

            const fechaAlta = new Date(data.fechaAlta).toLocaleDateString('es-ES', {
                day: '2-digit', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit'
            });

            const rolTexto = data.rol === 'admin' ? 'Administrador' : 'Empleado';

            document.getElementById('verUsuarioNombre').textContent = data.nombre;
            document.getElementById('verUsuarioEmail').textContent = data.email;
            document.getElementById('verUsuarioRol').textContent = rolTexto;
            document.getElementById('verUsuarioFecha').textContent = fechaAlta;
            document.getElementById('verUsuarioEstado').innerHTML = data.activo === 1
                ? '<span class="admin-badge badge-activo">Activo</span>'
                : '<span class="admin-badge badge-inactivo">Inactivo</span>';

            abrirModal('modalVerUsuario');
        })
        .catch(err => console.error('Error cargando usuario:', err));
}

/**
 * Abre el modal de edición de un usuario.
 */
function editarUsuario(id) {
    fetch(`api/usuarios.php?id=${id}`)
        .then(res => res.json())
        .then(data => {
            if (data.ok === false) {
                alert(data.error);
                return;
            }

            document.getElementById('editUsuarioId').value = data.id;
            document.getElementById('editUsuarioNombre').value = data.nombre;
            document.getElementById('editUsuarioEmail').value = data.email;
            document.getElementById('editUsuarioPassword').value = '';
            document.getElementById('editUsuarioPassword').required = false;
            document.getElementById('editUsuarioRol').value = data.rol;
            document.getElementById('editUsuarioEstado').value = data.activo;

            // Mostrar/ocultar permisos según el rol
            actualizarVisibilidadPermisos(data.rol);

            // Verificar si tiene el permiso de crear productos
            const permisos = data.permisos || '';
            document.getElementById('editUsuarioPermisoCrearProductos').checked = permisos.includes('crear_productos');

            document.getElementById('editUsuarioTitulo').textContent = 'Editar Usuario';
            document.getElementById('editUsuarioPassword').closest('.editar-prod-fila').querySelector('label').innerHTML = 'Password (dejar vacío para mantener)';

            abrirModal('modalEditarUsuario');
        })
        .catch(err => console.error('Error cargando usuario:', err));
}

/**
 * Muestra u oculta los permisos según el rol seleccionado.
 */
function actualizarVisibilidadPermisos(rol) {
    const filaPermisos = document.getElementById('filaPermisos');
    if (filaPermisos) {
        filaPermisos.style.display = (rol === 'empleado') ? 'block' : 'none';
    }
}

/**
 * Prepara el modal para crear un nuevo usuario.
 */
function prepararNuevoUsuario() {
    document.getElementById('editUsuarioId').value = '';
    document.getElementById('editUsuarioNombre').value = '';
    document.getElementById('editUsuarioEmail').value = '';
    document.getElementById('editUsuarioPassword').value = '';
    document.getElementById('editUsuarioPassword').required = true;
    document.getElementById('editUsuarioRol').value = 'empleado';
    document.getElementById('editUsuarioEstado').value = '1';

    // Por defecto, ocultar permisos (se mostrará si es empleado)
    actualizarVisibilidadPermisos('empleado');
    document.getElementById('editUsuarioPermisoCrearProductos').checked = false;

    document.getElementById('editUsuarioTitulo').textContent = 'Nuevo Usuario';
    document.getElementById('editUsuarioPassword').closest('.editar-prod-fila').querySelector('label').innerHTML = 'Password <span style="color:red">*</span>';

    // Añadir listener para mostrar/ocultar permisos cuando cambie el rol
    const rolSelect = document.getElementById('editUsuarioRol');
    rolSelect.onchange = function () {
        actualizarVisibilidadPermisos(this.value);
    };

    abrirModal('modalEditarUsuario');
}

/**
 * Guarda los cambios de un usuario (crear o actualizar).
 */
function guardarCambiosUsuario() {
    const id = document.getElementById('editUsuarioId').value;
    const nombre = document.getElementById('editUsuarioNombre').value.trim();
    const email = document.getElementById('editUsuarioEmail').value.trim();
    const password = document.getElementById('editUsuarioPassword').value;
    const rol = document.getElementById('editUsuarioRol').value;
    const activo = document.getElementById('editUsuarioEstado').value;

    if (!nombre || !email) {
        alert('Por favor completa todos los campos obligatorios.');
        return;
    }

    // Validar email
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        alert('Por favor ingresa un email válido.');
        return;
    }

    // Obtener permisos (solo para empleados)
    let permisos = '';
    if (rol === 'empleado') {
        const checkboxPermiso = document.getElementById('editUsuarioPermisoCrearProductos');
        if (checkboxPermiso && checkboxPermiso.checked) {
            permisos = 'crear_productos';
        }
    }

    const formData = new FormData();
    if (id) formData.append('id', id);
    formData.append('nombre', nombre);
    formData.append('email', email);
    if (password) formData.append('password', password);
    formData.append('rol', rol);
    formData.append('activo', activo);
    formData.append('permisos', permisos);

    fetch('api/usuarios.php', { method: 'POST', body: formData })
        .then(res => {
            if (!res.ok) {
                return res.json().then(err => { throw new Error(err.error || 'Error al guardar'); });
            }
            return res.json();
        })
        .then(data => {
            if (data.ok) {
                cerrarModal('modalEditarUsuario');
                cargarUsuariosAdmin();
            } else {
                alert('Error al guardar: ' + (data.error ?? ''));
            }
        })
        .catch(err => {
            console.error('Error guardando usuario:', err);
            alert('Error: ' + err.message);
        });
}

/**
 * Confirma la eliminación de un usuario.
 */
function confirmarEliminarUsuario(id, nombre) {
    if (confirm(`¿Seguro que quieres eliminar al usuario "${nombre}"?`)) {
        eliminarUsuario(id);
    }
}

/**
 * Elimina un usuario.
 */
function eliminarUsuario(id) {
    fetch(`api/usuarios.php?eliminar=${id}`, { method: 'DELETE' })
        .then(res => {
            if (!res.ok) {
                return res.json().then(err => { throw new Error(err.error || 'Error al eliminar'); });
            }
            return res.json();
        })
        .then(data => {
            if (data.ok) {
                cargarUsuariosAdmin();
            } else {
                alert('Error al eliminar el usuario: ' + (data.error ?? ''));
            }
        })
        .catch(err => {
            console.error('Error eliminando usuario:', err);
            alert('Error: ' + err.message);
        });
}

// ======================== GESTIÓN DE VENTAS ========================

/**
 * Carga las ventas desde la API y las renderiza en una tabla.
 * @param {string} filtroFecha - Filtrar por: 'todos', 'hoy', '7dias', '30dias'
 * @param {string} metodoPago - Filtrar por: 'todos', 'efectivo', 'tarjeta', 'bizum'
 * @param {string} tipoDocumento - Filtrar por: 'todos', 'ticket', 'factura'
 * @param {string} orden - Ordenar por: 'fecha_desc', 'fecha_asc', 'importe_desc', 'importe_asc', 'cantidad_desc', 'cantidad_asc', 'id_desc', 'id_asc'
 */
function cargarVentasAdmin(filtroFecha = 'todos', metodoPago = 'todos', tipoDocumento = 'todos', orden = 'fecha_desc') {
    // Si la sección actual no es ventas, forzamos primera carga
    if (seccionActual !== 'ventas') {
        adminTablaHeaderHTML = '';
        seccionActual = 'ventas';
    }

    const contenedor = document.getElementById('adminContenido');
    const tablaExistente = contenedor.querySelector('.admin-tabla');
    const esPrimeraVez = !tablaExistente || adminTablaHeaderHTML === '';

    // Construir URL con filtros
    let url = 'api/ventas.php?todas=1';
    if (filtroFecha && filtroFecha !== 'todos') {
        url += '&filtroFecha=' + filtroFecha;
    }
    if (metodoPago && metodoPago !== 'todos') {
        url += '&metodoPago=' + metodoPago;
    }
    if (tipoDocumento && tipoDocumento !== 'todos') {
        url += '&tipoDocumento=' + tipoDocumento;
    }
    if (orden && orden !== 'fecha_desc') {
        url += '&orden=' + orden;
    }

    fetch(url)
        .then(res => {
            if (!res.ok) {
                return res.json().then(err => { throw new Error(err.error || 'Error al cargar ventas'); });
            }
            return res.json();
        })
        .then(data => renderVentasAdmin(data, esPrimeraVez, filtroFecha, metodoPago, tipoDocumento, orden))
        .catch(err => {
            console.error('Error cargando ventas:', err);
            contenedor.innerHTML = '<p class="sin-productos">Error: ' + err.message + '</p>';
        });
}

/**
 * Genera el HTML del header de la tabla de ventas.
 */
function getVentasTablaHeader(filtroFecha = 'todos', metodoPago = 'todos', tipoDocumento = 'todos', orden = 'fecha_desc') {
    return `
        <div class="admin-tabla-header ventas-header">
            <div class="ventas-filtros">
                <div class="filtro-group">
                    <label for="ventasFiltroFecha">Período:</label>
                    <select id="ventasFiltroFecha" class="filtro-select" onchange="aplicarFiltrosVentas()">
                        <option value="todos" ${filtroFecha === 'todos' ? 'selected' : ''}>Todas</option>
                        <option value="hoy" ${filtroFecha === 'hoy' ? 'selected' : ''}>Hoy</option>
                        <option value="7dias" ${filtroFecha === '7dias' ? 'selected' : ''}>Últimos 7 días</option>
                        <option value="30dias" ${filtroFecha === '30dias' ? 'selected' : ''}>Último mes</option>
                    </select>
                </div>
                <div class="filtro-group">
                    <label for="ventasFiltroMetodo">Método de pago:</label>
                    <select id="ventasFiltroMetodo" class="filtro-select" onchange="aplicarFiltrosVentas()">
                        <option value="todos" ${metodoPago === 'todos' ? 'selected' : ''}>Todos</option>
                        <option value="efectivo" ${metodoPago === 'efectivo' ? 'selected' : ''}>Efectivo</option>
                        <option value="tarjeta" ${metodoPago === 'tarjeta' ? 'selected' : ''}>Tarjeta</option>
                        <option value="bizum" ${metodoPago === 'bizum' ? 'selected' : ''}>Bizum</option>
                    </select>
                </div>
                <div class="filtro-group">
                    <label for="ventasFiltroDocumento">Documento:</label>
                    <select id="ventasFiltroDocumento" class="filtro-select" onchange="aplicarFiltrosVentas()">
                        <option value="todos" ${tipoDocumento === 'todos' ? 'selected' : ''}>Todos</option>
                        <option value="ticket" ${tipoDocumento === 'ticket' ? 'selected' : ''}>Ticket</option>
                        <option value="factura" ${tipoDocumento === 'factura' ? 'selected' : ''}>Factura</option>
                    </select>
                </div>
                <div class="filtro-group">
                    <label for="ventasOrdenar">Ordenar por:</label>
                    <select id="ventasOrdenar" class="filtro-select" onchange="aplicarFiltrosVentas()">
                        <option value="fecha_desc" ${orden === 'fecha_desc' ? 'selected' : ''}>Más recientes</option>
                        <option value="fecha_asc" ${orden === 'fecha_asc' ? 'selected' : ''}>Más antiguos</option>
                        <option value="importe_desc" ${orden === 'importe_desc' ? 'selected' : ''}>Mayor importe</option>
                        <option value="importe_asc" ${orden === 'importe_asc' ? 'selected' : ''}>Menor importe</option>
                        <option value="cantidad_desc" ${orden === 'cantidad_desc' ? 'selected' : ''}>Más productos</option>
                        <option value="cantidad_asc" ${orden === 'cantidad_asc' ? 'selected' : ''}>Menos productos</option>
                        <option value="id_desc" ${orden === 'id_desc' ? 'selected' : ''}>ID mayor</option>
                        <option value="id_asc" ${orden === 'id_asc' ? 'selected' : ''}>ID menor</option>
                    </select>
                </div>
                <button class="btn-limpiar-ventas" onclick="limpiarTodasVentas()" title="Eliminar todas las ventas">
                    🗑️ Limpiar ventas
                </button>
            </div>
        </div>
        <div class="admin-tabla-wrapper">
            <table class="admin-tabla">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Fecha</th>
                        <th>Usuario</th>
                        <th>Productos</th>
                        <th>Documento</th>
                        <th>Forma de Pago</th>
                        <th>Total</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>`;
}

/**
 * Renderiza las ventas en una tabla.
 */
function renderVentasAdmin(ventas, esPrimeraVez = true, filtroFecha = 'todos', metodoPago = 'todos', tipoDocumento = 'todos', orden = 'fecha_desc') {
    const contenedor = document.getElementById('adminContenido');

    if (!ventas || ventas.length === 0) {
        if (esPrimeraVez || adminTablaHeaderHTML === '') {
            adminTablaHeaderHTML = getVentasTablaHeader(filtroFecha, metodoPago, tipoDocumento, orden);
            contenedor.innerHTML = adminTablaHeaderHTML +
                '<tr><td colspan="8" class="sin-productos">No hay ventas registradas.</td></tr></tbody></table></div>';
        } else {
            const tbody = contenedor.querySelector('tbody');
            if (tbody) tbody.innerHTML = '<tr><td colspan="8" class="sin-productos">No hay ventas registradas.</td></tr>';
        }
        return;
    }

    if (esPrimeraVez || adminTablaHeaderHTML === '') {
        adminTablaHeaderHTML = getVentasTablaHeader(filtroFecha, metodoPago, tipoDocumento, orden);
    }

    let html = adminTablaHeaderHTML;

    ventas.forEach(venta => {
        const fecha = new Date(venta.fecha).toLocaleString('es-ES', {
            day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit'
        });
        const total = parseFloat(venta.total).toFixed(2).replace('.', ',');
        const productos = venta.cantidad_productos || 0;

        // Formatear forma de pago
        let formaPago = venta.forma_pago || '—';
        if (formaPago === 'efectivo') formaPago = '💵 Efectivo';
        else if (formaPago === 'tarjeta') formaPago = '💳 Tarjeta';
        else if (formaPago === 'bizum') formaPago = '📱 Bizum';

        // Formatear tipo de documento
        let tipoDocumento = venta.tipoDocumento || 'ticket';
        if (tipoDocumento === 'ticket') tipoDocumento = '🧾 Ticket';
        else if (tipoDocumento === 'factura') tipoDocumento = '📄 Factura';

        html += `
            <tr>
                <td class="col-id">${venta.id}</td>
                <td class="col-fecha">${fecha}</td>
                <td class="col-usuario">${venta.usuario_nombre || '—'}</td>
                <td class="col-productos">${productos}</td>
                <td class="col-documento">${tipoDocumento}</td>
                <td class="col-pago">${formaPago}</td>
                <td class="col-total" style="font-weight: 700; color: #059669;">${total} €</td>
                <td class="col-acciones">
                    <button class="btn-admin-accion btn-ver" onclick="verDetalleVenta(${venta.id})" title="Ver Detalles">
                        <i class="fas fa-eye"></i>
                    </button>
                </td>
            </tr>`;
    });

    html += `
                </tbody>
            </table>
        </div>`;

    if (esPrimeraVez) {
        contenedor.innerHTML = html;
    } else {
        const tbody = contenedor.querySelector('tbody');
        if (tbody) {
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = html;
            const nuevoTbody = tempDiv.querySelector('tbody');
            tbody.innerHTML = nuevoTbody.innerHTML;
        } else {
            contenedor.innerHTML = html;
        }
    }
}

/**
 * Aplica los filtros seleccionados y recarga la tabla de ventas.
 */
function aplicarFiltrosVentas() {
    const filtroFecha = document.getElementById('ventasFiltroFecha')?.value || 'todos';
    const metodoPago = document.getElementById('ventasFiltroMetodo')?.value || 'todos';
    const tipoDocumento = document.getElementById('ventasFiltroDocumento')?.value || 'todos';
    const orden = document.getElementById('ventasOrdenar')?.value || 'fecha_desc';

    // Mantener los filtros seleccionados en la URL
    adminTablaHeaderHTML = '';

    cargarVentasAdmin(filtroFecha, metodoPago, tipoDocumento, orden);
}

/**
 * Elimina todas las ventas del historial tras confirmación.
 */
function limpiarTodasVentas() {
    if (!confirm('¿Estás seguro de que quieres eliminar TODAS las ventas?\n\nEsta acción no se puede deshacer y borrará todo el historial de ventas.')) {
        return;
    }

    if (!confirm('¿SEGURO? Se eliminarán todas las ventas de forma permanente.')) {
        return;
    }

    fetch('api/ventas.php?limpiarVentas=1', {
        method: 'POST'
    })
        .then(res => res.json())
        .then(data => {
            if (data.error) {
                alert('Error: ' + data.error);
            } else {
                alert(data.message);
                // Recargar la tabla
                adminTablaHeaderHTML = '';
                cargarVentasAdmin();
            }
        })
        .catch(err => {
            console.error('Error al limpiar ventas:', err);
            alert('Error al intentar eliminar las ventas.');
        });
}

/**
 * Muestra los detalles de una venta.
 */
function verDetalleVenta(idVenta) {
    fetch(`api/ventas.php?detalleVenta=${idVenta}`)
        .then(res => res.json())
        .then(data => {
            if (data.error) {
                alert(data.error);
                return;
            }

            const venta = data.venta;
            const lineas = data.lineas;

            const fecha = new Date(venta.fecha).toLocaleString('es-ES', {
                day: '2-digit', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit'
            });

            let html = `
                <div style="padding: 20px;">
                    <h3 style="margin-top: 0;">Detalles de Venta #${venta.id}</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                        <div><strong>Fecha:</strong> ${fecha}</div>
                        <div><strong>Usuario:</strong> ${venta.usuario_nombre || '—'}</div>
                        <div><strong>Forma de Pago:</strong> ${venta.metodoPago || '—'}</div>
                        <div><strong>Total:</strong> <span style="color: #059669; font-weight: 700;">${parseFloat(venta.total).toFixed(2).replace('.', ',')} €</span></div>
                    </div>
                    <h4>Productos:</h4>
                    <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
                        <thead>
                            <tr style="background: #f0f2f5;">
                                <th style="padding: 8px; text-align: left;">Producto</th>
                                <th style="padding: 8px; text-align: center;">Cantidad</th>
                                <th style="padding: 8px; text-align: right;">Precio</th>
                                <th style="padding: 8px; text-align: right;">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>`;

            lineas.forEach(linea => {
                const subtotal = (linea.cantidad * linea.precioUnitario).toFixed(2).replace('.', ',');
                html += `
                    <tr>
                        <td style="padding: 8px; border-bottom: 1px solid #e5e7eb;">${linea.producto_nombre || 'Producto #' + linea.idProducto}</td>
                        <td style="padding: 8px; border-bottom: 1px solid #e5e7eb; text-align: center;">${linea.cantidad}</td>
                        <td style="padding: 8px; border-bottom: 1px solid #e5e7eb; text-align: right;">${parseFloat(linea.precioUnitario).toFixed(2).replace('.', ',')} €</td>
                        <td style="padding: 8px; border-bottom: 1px solid #e5e7eb; text-align: right; font-weight: 600;">${subtotal} €</td>
                    </tr>`;
            });

            html += `
                        </tbody>
                    </table>
                    <div style="margin-top: 20px; text-align: right;">
                        <button class="btn-modal-cancelar" onclick="cerrarModal('modalVerVenta')">Cerrar</button>
                    </div>
                </div>`;

            // Crear o mostrar el modal
            let modal = document.getElementById('modalVerVenta');
            if (!modal) {
                modal = document.createElement('div');
                modal.id = 'modalVerVenta';
                modal.className = 'modal-overlay';
                modal.style.display = 'none';
                modal.innerHTML = '<div class="modal-content" style="max-width: 600px; max-height: 80vh; overflow-y: auto;"></div>';
                document.body.appendChild(modal);
            }

            modal.querySelector('.modal-content').innerHTML = html;
            modal.style.display = 'flex';
        })
        .catch(err => {
            console.error('Error cargando detalles de venta:', err);
            alert('Error al cargar los detalles de la venta.');
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

            // Obtener colores según el tema actual
            const isDark = document.body.classList.contains('dark-mode');
            const gridColor = isDark ? '#374151' : '#f0f2f5';
            const textColor = isDark ? '#ffffff' : '#374151';

            // Definir opciones comunes para ambos gráficos (responsive, sin leyenda, ejes).
            const opcionesComunes = {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false }, ticks: { color: textColor } },           // Sin líneas de cuadrícula en el eje X.
                    y: { beginAtZero: true, grid: { color: gridColor }, ticks: { color: textColor } } // Eje Y desde 0 con cuadrícula suave.
                }
            };

            // Escuchar cambios de tema para actualizar los gráficos
            const actualizarGraficos = () => {
                const isDarkNow = document.body.classList.contains('dark-mode');
                const newGridColor = isDarkNow ? '#374151' : '#f0f2f5';
                const newTextColor = isDarkNow ? '#ffffff' : '#374151';

                // Destruir gráficos existentes y recrearlos
                const chartVentas = Chart.getChart('graficaVentas');
                const chartPedidos = Chart.getChart('graficaPedidos');

                if (chartVentas) {
                    chartVentas.options.scales.x.ticks.color = newTextColor;
                    chartVentas.options.scales.y.ticks.color = newTextColor;
                    chartVentas.options.scales.y.grid.color = newGridColor;
                    chartVentas.update();
                }

                if (chartPedidos) {
                    chartPedidos.options.scales.x.ticks.color = newTextColor;
                    chartPedidos.options.scales.y.ticks.color = newTextColor;
                    chartPedidos.options.scales.y.grid.color = newGridColor;
                    chartPedidos.update();
                }
            };

            // Agregar listener para cambios de tema
            window.removeEventListener('themeChange', actualizarGraficos);
            window.addEventListener('themeChange', actualizarGraficos);

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

/**
 * Busca productos en tiempo real (live search) combinando el texto introducido
 * con la categoría actualmente seleccionada.
 * Se invoca cada vez que el usuario escribe en el campo de búsqueda.
 */
// Variable para almacenar el temporizador de debounce
let debounceTimer;
let debounceTimerUsuarios;

/**
 * Función de búsqueda con debounce para evitar múltiples peticiones
 * mientras el usuario está escribiendo.
 */
function buscarProductos() {
    // Cancelar la búsqueda anterior si el usuario sigue escribiendo
    clearTimeout(debounceTimer);

    // Establecer un nuevo temporizador de 300ms
    debounceTimer = setTimeout(() => {
        // Obtener el texto escrito en el campo de búsqueda
        const texto = document.getElementById('inputBuscarProducto').value.trim();

        // Obtener la categoría seleccionada del select
        const selectCategoria = document.getElementById('selectCategoria');
        const idCategoria = selectCategoria ? selectCategoria.value : 'todas';

        // Obtener el orden seleccionado del select
        const selectOrden = document.getElementById('selectOrden');
        const orden = selectOrden ? selectOrden.value : '';

        // Delegar la carga al método centralizado que ya incluye el parámetro admin=1
        // y renderiza correctamente en la tabla del panel de administración.
        cargarProductosAdmin(idCategoria, texto, orden);
    }, 300); // 300ms de espera después de que el usuario deje de escribir
}

// ======================== RENDER PRODUCTOS ========================

/**
 * Renderiza la lista de productos en la cuadrícula (grid) del cajero.
 * Genera dinámicamente las tarjetas HTML de cada producto con su imagen,
 * nombre, precio y stock.
 * 
 * @param {Array} productos - Array de objetos producto devueltos por la API.
 *   Cada objeto contiene: id, nombre, precio, stock, imagen.
 */
function renderProductos(productos) {
    // Obtener el contenedor de la cuadrícula de productos.
    const grid = document.getElementById('productosGrid');

    // Si no hay productos o el array está vacío, mostrar un mensaje informativo.
    if (!productos || productos.length === 0) {
        grid.innerHTML = '<p class="sin-productos">No hay productos disponibles.</p>';
        return;
    }

    // Construir el HTML de todas las tarjetas de producto.
    let html = '';
    productos.forEach(prod => {
        // Formatear el precio con 2 decimales y coma como separador decimal (formato europeo).
        let precioFmt = parseFloat(prod.precio).toFixed(2).replace('.', ',');

        // Usar la imagen del producto si existe; de lo contrario, usar el logo por defecto.
        let imgSrc = prod.imagen && prod.imagen !== '' ? prod.imagen : 'webroot/img/logo.PNG';

        // Generar la tarjeta del producto.
        // Si el stock es 0 o menor, se aplica un estilo visual de "no disponible"
        // (opacidad reducida, cursor no permitido, sin animaciones de hover).
        html += `<div class="producto-card" data-id="${prod.id}"
                    data-nombre="${prod.nombre.replace(/"/g, '&quot;')}"
                    data-precio="${prod.precio}" data-stock="${prod.stock}"
                    onclick="agregarAlCarrito(this)" style="${prod.stock <= 0 ? 'opacity: 0.5; cursor: not-allowed; scale: 1; transform: translateY(0px);' : ''}">
                    <div class="producto-nombre">${prod.nombre}</div>
                    <div class="producto-imagen">
                        <img src="${imgSrc}" alt="${prod.nombre.replace(/"/g, '&quot;')}">
                    </div>
                    <div class="producto-info-inferior">
                        <span class="producto-precio">${precioFmt} €</span>
                        <span class="producto-stock" ${prod.stock <= 0 ? 'style="color: red; text-decoration: underline;"' : ''}>Stock: ${prod.stock}</span>
                    </div>
                </div>`;
    });

    // Insertar todo el HTML generado en la cuadrícula de productos.
    grid.innerHTML = html;
}


// ======================== CONFIGURACIÓN DE TEMA ========================

/**
 * Lista de fuentes de Google Fonts disponibles para seleccionar.
 */
const FUENTES_DISPONIBLES = [
    'Inter', 'Roboto', 'Poppins', 'Open Sans', 'Montserrat',
    'Lato', 'Outfit', 'Nunito', 'Raleway', 'Source Sans 3'
];

/**
 * Valores predeterminados del tema.
 */
const TEMA_DEFAULTS = {
    header_bg: '#1a1a2e', header_color: '#ffffff', header_font: 'Inter',
    footer_bg: '#1a1a2e', footer_color: '#e5e7eb', footer_font: 'Inter',
    primary_bg: '#2563eb', primary_color: '#ffffff', primary_font: 'Inter',
    sidebar_bg: '#ffffff', sidebar_color: '#1a1a2e', sidebar_font: 'Inter',
    btn_bg: '#1a1a2e', btn_color: '#ffffff', btn_font: 'Inter',
    btn_white_bg: '#ffffff', btn_white_color: '#1a1a2e', btn_white_font: 'Inter'
};

/**
 * Configuración actual del tema (se carga desde la API).
 */
let temaActual = { ...TEMA_DEFAULTS };

/**
 * Definición de las secciones del editor de tema.
 */
const SECCIONES_TEMA = [
    { id: 'header', titulo: 'Header (Cabecera)', icono: 'fa-heading', bgKey: 'header_bg', colorKey: 'header_color', fontKey: 'header_font' },
    { id: 'footer', titulo: 'Footer (Pie de página)', icono: 'fa-shoe-prints', bgKey: 'footer_bg', colorKey: 'footer_color', fontKey: 'footer_font' },
    { id: 'primary', titulo: 'Color Principal', icono: 'fa-palette', bgKey: 'primary_bg', colorKey: 'primary_color', fontKey: 'primary_font' },
    { id: 'sidebar', titulo: 'Menús Laterales / Categorías', icono: 'fa-bars', bgKey: 'sidebar_bg', colorKey: 'sidebar_color', fontKey: 'sidebar_font' },
    { id: 'btn', titulo: 'Botones (Default)', icono: 'fa-square', bgKey: 'btn_bg', colorKey: 'btn_color', fontKey: 'btn_font' },
    { id: 'btn_white', titulo: 'Botones (Blancos/Secundarios)', icono: 'fa-square-full', bgKey: 'btn_white_bg', colorKey: 'btn_white_color', fontKey: 'btn_white_font' }
];

/**
 * Genera el HTML de un selector de fuentes.
 */
function generarSelectFuente(id, valorActual) {
    let opciones = FUENTES_DISPONIBLES.map(f =>
        `<option value="${f}" ${f === valorActual ? 'selected' : ''} style="font-family: '${f}', sans-serif;">${f}</option>`
    ).join('');
    return `<select id="${id}" class="tema-select-font" onchange="previsualizarTema()">${opciones}</select>`;
}

/**
 * Genera el HTML de una sección del editor de tema.
 */
function generarSeccionTema(seccion) {
    const bgVal = temaActual[seccion.bgKey] || TEMA_DEFAULTS[seccion.bgKey];
    const colorVal = temaActual[seccion.colorKey] || TEMA_DEFAULTS[seccion.colorKey];
    const fontVal = temaActual[seccion.fontKey] || TEMA_DEFAULTS[seccion.fontKey];

    return `
        <div class="tema-seccion-card">
            <div class="tema-seccion-header">
                <i class="fas ${seccion.icono} tema-seccion-icono"></i>
                <h4 class="tema-seccion-titulo">${seccion.titulo}</h4>
            </div>
            <div class="tema-seccion-body">
                <div class="tema-campo">
                    <label class="tema-label">Color de Fondo</label>
                    <div class="tema-color-wrapper">
                        <input type="color" id="tema_${seccion.bgKey}" value="${bgVal}" oninput="previsualizarTema()">
                        <span class="tema-color-hex" id="hex_${seccion.bgKey}">${bgVal}</span>
                    </div>
                </div>
                <div class="tema-campo">
                    <label class="tema-label">Color de Texto</label>
                    <div class="tema-color-wrapper">
                        <input type="color" id="tema_${seccion.colorKey}" value="${colorVal}" oninput="previsualizarTema()">
                        <span class="tema-color-hex" id="hex_${seccion.colorKey}">${colorVal}</span>
                    </div>
                </div>
                <div class="tema-campo">
                    <label class="tema-label">Fuente</label>
                    ${generarSelectFuente('tema_' + seccion.fontKey, fontVal)}
                </div>
                <div class="tema-preview" id="preview_${seccion.id}" style="background: ${bgVal}; color: ${colorVal}; font-family: '${fontVal}', sans-serif;">
                    Vista previa del texto
                </div>
            </div>
        </div>
    `;
}

/**
 * Carga la configuración del tema desde la API y renderiza el editor.
 */
function cargarConfiguracion() {
    seccionActual = 'configuracion';
    adminTablaHeaderHTML = '';

    const contenedor = document.getElementById('adminContenido');
    contenedor.innerHTML = '<p style="text-align: center; padding: 40px; color: var(--text-muted);">Cargando configuración...</p>';

    fetch('api/tema.php')
        .then(res => res.json())
        .then(config => {
            // Mezclar config de la BD con los defaults
            temaActual = { ...TEMA_DEFAULTS, ...config };
            renderEditorTema();
        })
        .catch(err => {
            console.error('Error cargando configuración:', err);
            temaActual = { ...TEMA_DEFAULTS };
            renderEditorTema();
        });
}

/**
 * Renderiza el editor de tema completo.
 */
function renderEditorTema() {
    const contenedor = document.getElementById('adminContenido');

    let html = `
        <div class="tema-editor">
            <div class="tema-editor-intro">
                <i class="fas fa-paint-brush" style="font-size: 1.5rem; color: var(--accent, #2563eb);"></i>
                <div>
                    <p class="tema-intro-titulo">Personaliza la apariencia de tu TPV</p>
                    <p class="tema-intro-subtitulo">Los cambios se previsualizan en tiempo real. Pulsa "Guardar" para aplicarlos de forma permanente.</p>
                </div>
            </div>
            <div class="tema-secciones-grid">
                ${SECCIONES_TEMA.map(s => generarSeccionTema(s)).join('')}
            </div>
            <div class="tema-botones">
                <button class="btn-modal-cancelar tema-btn-reset" onclick="restaurarTemaDefault()">
                    <i class="fas fa-undo"></i> Restaurar Predeterminados
                </button>
                <button class="btn-exito tema-btn-guardar" onclick="guardarTema()">
                    <i class="fas fa-save"></i> Guardar Cambios
                </button>
            </div>
        </div>
    `;

    contenedor.innerHTML = html;
}

/**
 * Lee los valores actuales del editor y aplica CSS variables en tiempo real.
 */
function previsualizarTema() {
    const root = document.documentElement;

    SECCIONES_TEMA.forEach(seccion => {
        const bgInput = document.getElementById('tema_' + seccion.bgKey);
        const colorInput = document.getElementById('tema_' + seccion.colorKey);
        const fontSelect = document.getElementById('tema_' + seccion.fontKey);
        const preview = document.getElementById('preview_' + seccion.id);

        if (!bgInput || !colorInput || !fontSelect) return;

        const bgVal = bgInput.value;
        const colorVal = colorInput.value;
        const fontVal = fontSelect.value;

        // Actualizar hex labels
        const hexBg = document.getElementById('hex_' + seccion.bgKey);
        const hexColor = document.getElementById('hex_' + seccion.colorKey);
        if (hexBg) hexBg.textContent = bgVal;
        if (hexColor) hexColor.textContent = colorVal;

        // Actualizar preview card
        if (preview) {
            preview.style.background = bgVal;
            preview.style.color = colorVal;
            preview.style.fontFamily = `'${fontVal}', sans-serif`;
        }

        // Aplicar CSS variables en vivo
        root.style.setProperty('--theme-' + seccion.bgKey.replace(/_/g, '-'), bgVal);
        root.style.setProperty('--theme-' + seccion.colorKey.replace(/_/g, '-'), colorVal);
    });

    // Aplicar al header y footer directamente
    const header = document.querySelector('header');
    const footer = document.querySelector('footer');
    const bgH = document.getElementById('tema_header_bg');
    const colorH = document.getElementById('tema_header_color');
    const fontH = document.getElementById('tema_header_font');
    const bgF = document.getElementById('tema_footer_bg');
    const colorF = document.getElementById('tema_footer_color');
    const fontF = document.getElementById('tema_footer_font');

    if (header && bgH && colorH && fontH) {
        header.style.background = bgH.value;
        header.style.color = colorH.value;
        header.style.fontFamily = `'${fontH.value}', sans-serif`;
    }
    if (footer && bgF && colorF && fontF) {
        footer.style.background = bgF.value;
        footer.style.color = colorF.value;
        footer.style.fontFamily = `'${fontF.value}', sans-serif`;
    }

    // Cargar la fuente de Google Fonts si no está ya cargada
    const fuentesUsadas = new Set();
    SECCIONES_TEMA.forEach(s => {
        const fontSel = document.getElementById('tema_' + s.fontKey);
        if (fontSel) fuentesUsadas.add(fontSel.value);
    });
    cargarGoogleFonts([...fuentesUsadas]);
}

/**
 * Carga dinámicamente fuentes de Google Fonts.
 */
function cargarGoogleFonts(fuentes) {
    // Eliminar link anterior si existe
    let linkExistente = document.getElementById('google-fonts-tema');
    if (linkExistente) linkExistente.remove();

    const fuentesFiltradas = fuentes.filter(f => f !== 'Inter'); // Inter ya está cargada
    if (fuentesFiltradas.length === 0) return;

    const familias = fuentesFiltradas.map(f => f.replace(/ /g, '+')).join('&family=');
    const link = document.createElement('link');
    link.id = 'google-fonts-tema';
    link.rel = 'stylesheet';
    link.href = `https://fonts.googleapis.com/css2?family=${familias}&display=swap`;
    document.head.appendChild(link);
}

/**
 * Guarda toda la configuración del tema via POST a la API.
 */
function guardarTema() {
    const datos = {};

    SECCIONES_TEMA.forEach(seccion => {
        const bgInput = document.getElementById('tema_' + seccion.bgKey);
        const colorInput = document.getElementById('tema_' + seccion.colorKey);
        const fontSelect = document.getElementById('tema_' + seccion.fontKey);

        if (bgInput) datos[seccion.bgKey] = bgInput.value;
        if (colorInput) datos[seccion.colorKey] = colorInput.value;
        if (fontSelect) datos[seccion.fontKey] = fontSelect.value;
    });

    // Guardar en localStorage para acceso rápido
    localStorage.setItem('temaTPV', JSON.stringify(datos));

    // Guardar en la BD via API
    fetch('api/tema.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(datos)
    })
        .then(res => res.json())
        .then(data => {
            if (data.ok) {
                // Feedback visual
                const btn = document.querySelector('.tema-btn-guardar');
                if (btn) {
                    const textoOriginal = btn.innerHTML;
                    btn.innerHTML = '<i class="fas fa-check"></i> ¡Guardado!';
                    btn.style.background = '#059669';
                    setTimeout(() => {
                        btn.innerHTML = textoOriginal;
                        btn.style.background = '';
                    }, 2000);
                }
            } else {
                alert('Error al guardar: ' + (data.error || 'Error desconocido'));
            }
        })
        .catch(err => {
            console.error('Error guardando tema:', err);
            alert('Error al guardar la configuración.');
        });
}

/**
 * Restaura los valores predeterminados del tema.
 */
function restaurarTemaDefault() {
    if (!confirm('¿Restaurar todos los colores y fuentes a los valores predeterminados?')) return;

    temaActual = { ...TEMA_DEFAULTS };

    // Actualizar inputs del editor
    SECCIONES_TEMA.forEach(seccion => {
        const bgInput = document.getElementById('tema_' + seccion.bgKey);
        const colorInput = document.getElementById('tema_' + seccion.colorKey);
        const fontSelect = document.getElementById('tema_' + seccion.fontKey);

        if (bgInput) bgInput.value = TEMA_DEFAULTS[seccion.bgKey];
        if (colorInput) colorInput.value = TEMA_DEFAULTS[seccion.colorKey];
        if (fontSelect) fontSelect.value = TEMA_DEFAULTS[seccion.fontKey];
    });

    // Aplicar preview
    previsualizarTema();

    // Limpiar estilos inline del header/footer
    const header = document.querySelector('header');
    const footer = document.querySelector('footer');
    if (header) { header.style.background = ''; header.style.color = ''; header.style.fontFamily = ''; }
    if (footer) { footer.style.background = ''; footer.style.color = ''; footer.style.fontFamily = ''; }

    // Guardar defaults en BD
    guardarTema();
}

/**
 * Aplica el tema guardado en localStorage al cargar la página.
 * Se llama desde Layout.php.
 */
function aplicarTemaGuardado() {
    const temaJSON = localStorage.getItem('temaTPV');
    if (!temaJSON) return;

    try {
        const tema = JSON.parse(temaJSON);
        const header = document.querySelector('header');
        const footer = document.querySelector('footer');

        if (header && tema.header_bg) {
            header.style.background = tema.header_bg;
            header.style.color = tema.header_color || '';
            header.style.fontFamily = tema.header_font ? `'${tema.header_font}', sans-serif` : '';
        }
        if (footer && tema.footer_bg) {
            footer.style.background = tema.footer_bg;
            footer.style.color = tema.footer_color || '';
            footer.style.fontFamily = tema.footer_font ? `'${tema.footer_font}', sans-serif` : '';
        }

        // Cargar fuentes necesarias
        const fuentes = new Set();
        Object.keys(tema).forEach(k => {
            if (k.endsWith('_font') && tema[k]) fuentes.add(tema[k]);
        });
        cargarGoogleFonts([...fuentes]);

    } catch (e) {
        console.error('Error aplicando tema:', e);
    }
}

// ======================== GESTIÓN DE DEVOLUCIONES ========================

/**
 * Carga las devoluciones desde la API y las renderiza en una tabla.
 */
function cargarDevolucionesAdmin(orden = 'fecha_desc') {
    if (seccionActual !== 'devoluciones') {
        adminTablaHeaderHTML = '';
        seccionActual = 'devoluciones';
    }

    const contenedor = document.getElementById('adminContenido');
    const tableExisting = contenedor.querySelector('.admin-tabla');
    const isFirstTime = !tableExisting || adminTablaHeaderHTML === '';

    let url = 'api/devoluciones.php?todas=1';
    if (orden !== 'fecha_desc') {
        url += '&orden=' + orden;
    }

    fetch(url)
        .then(res => {
            if (!res.ok) {
                return res.json().then(err => { throw new Error(err.error || 'Error al cargar devoluciones'); });
            }
            return res.json();
        })
        .then(data => renderDevolucionesAdmin(data, isFirstTime, orden))
        .catch(err => {
            console.error('Error cargando devoluciones:', err);
            contenedor.innerHTML = '<p class="sin-productos">Error: ' + err.message + '</p>';
        });
}

/**
 * Genera el HTML del header de la tabla de devoluciones.
 */
function getDevolucionesTablaHeader(orden = 'fecha_desc') {
    return `
        <div class="admin-tabla-header devoluciones-header">
            <div class="ventas-filtros">
                <div class="filtro-group">
                    <label for="devolucionesOrdenar">Ordenar por:</label>
                    <select id="devolucionesOrdenar" class="filtro-select" onchange="cargarDevolucionesAdmin(this.value)">
                        <option value="fecha_desc" ${orden === 'fecha_desc' ? 'selected' : ''}>Más recientes</option>
                        <option value="fecha_asc" ${orden === 'fecha_asc' ? 'selected' : ''}>Más antiguos</option>
                        <option value="importe_desc" ${orden === 'importe_desc' ? 'selected' : ''}>Mayor importe</option>
                        <option value="importe_asc" ${orden === 'importe_asc' ? 'selected' : ''}>Menor importe</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="admin-tabla-wrapper">
            <table class="admin-tabla">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Ticket</th>
                        <th>Fecha</th>
                        <th>Empleado</th>
                        <th>Producto</th>
                        <th>Cant.</th>
                        <th>Importe</th>
                        <th>Método</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>`;
}

/**
 * Renderiza las devoluciones en la tabla.
 */
function renderDevolucionesAdmin(devoluciones, isFirstTime = true, orden = 'fecha_desc') {
    const contenedor = document.getElementById('adminContenido');

    if (!devoluciones || devoluciones.length === 0) {
        if (isFirstTime || adminTablaHeaderHTML === '') {
            adminTablaHeaderHTML = getDevolucionesTablaHeader(orden);
            contenedor.innerHTML = adminTablaHeaderHTML +
                '<tr><td colspan="8" class="sin-productos">No hay devoluciones registradas.</td></tr></tbody></table></div>';
        } else {
            const tbody = contenedor.querySelector('tbody');
            if (tbody) tbody.innerHTML = '<tr><td colspan="8" class="sin-productos">No hay devoluciones registradas.</td></tr>';
        }
        return;
    }

    if (isFirstTime || adminTablaHeaderHTML === '') {
        adminTablaHeaderHTML = getDevolucionesTablaHeader(orden);
    }

    let html = adminTablaHeaderHTML;

    devoluciones.forEach(dev => {
        const fecha = new Date(dev.fecha).toLocaleString('es-ES', {
            day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit'
        });
        const total = parseFloat(dev.importeTotal).toFixed(2).replace('.', ',');

        html += `
            <tr>
                <td class="col-id">${dev.id}</td>
                <td class="col-ticket" style="font-weight: 600; color: #1e40af;">#${dev.idVenta || '—'}</td>
                <td class="col-fecha">${fecha}</td>
                <td class="col-usuario">${dev.usuario_nombre || '—'}</td>
                <td class="col-producto">${dev.producto_nombre || '—'}</td>
                <td class="col-cantidad">${dev.cantidad}</td>
                <td class="col-total" style="font-weight: 700; color: #dc2626;">-${total} €</td>
                <td class="col-pago">${dev.metodoPago}</td>
                <td class="col-acciones">
                    <button class="btn-admin-accion btn-ver" onclick="verDetalleDevolucion(${dev.id})" title="Ver Detalles">
                        <i class="fas fa-eye"></i>
                    </button>
                </td>
            </tr>`;
    });

    html += `</tbody></table></div>`;

    if (isFirstTime) {
        contenedor.innerHTML = html;
    } else {
        const tbody = contenedor.querySelector('tbody');
        if (tbody) {
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = html;
            const newTbody = tempDiv.querySelector('tbody');
            tbody.innerHTML = newTbody.innerHTML;
        } else {
            contenedor.innerHTML = html;
        }
    }
}

/**
 * Muestra los detalles de una devolución en un modal.
 */
function verDetalleDevolucion(id) {
    // Como obtenerTodas() ya trae la información necesaria, podemos buscarla en los datos cargados
    // Pero para ser robustos, si quisiéramos detalles extra (como el ID de sesión de caja),
    // podríamos hacer un fetch específico. Por ahora usaremos los datos actuales filtrando.

    // Si queremos datos frescos o más detallados:
    fetch(`api/devoluciones.php?todas=1`) // Podríamos filtrar por ID en la API si estuviera implementado
        .then(res => res.json())
        .then(data => {
            const dev = data.find(d => d.id == id);
            if (!dev) {
                alert('No se encontró la devolución');
                return;
            }

            const fecha = new Date(dev.fecha).toLocaleString('es-ES', {
                day: '2-digit', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit'
            });

            document.getElementById('verDevolucionId').textContent = dev.id;
            if (document.getElementById('verDevolucionTicket')) {
                document.getElementById('verDevolucionTicket').textContent = '#' + (dev.idVenta || '—');
            }
            document.getElementById('verDevolucionFecha').textContent = fecha;
            document.getElementById('verDevolucionProducto').textContent = dev.producto_nombre || '—';
            document.getElementById('verDevolucionCantidad').textContent = dev.cantidad;
            document.getElementById('verDevolucionImporte').textContent = '-' + parseFloat(dev.importeTotal).toFixed(2).replace('.', ',') + ' €';
            document.getElementById('verDevolucionMetodo').textContent = dev.metodoPago;
            document.getElementById('verDevolucionUsuario').textContent = dev.usuario_nombre || '—';

            abrirModal('modalVerDevolucion');
        })
        .catch(err => {
            console.error('Error al ver detalle de devolución:', err);
            alert('Error al cargar detalles');
        });
}
