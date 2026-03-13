// ======================== RENDER PRODUCTOS (MODO TABLA - ADMIN) ========================
// Este módulo contiene todas las funciones relacionadas con la renderización de productos
// en el panel de administración, incluyendo búsqueda, filtrado, ordenación y visualización.

// ======================== VARIABLES GLOBALES ========================

// Variable global para el término de búsqueda en tarifas
let tarifaBusquedaProducto = '';

// Función para filtrar la tabla de tarifas por nombre de producto
function filtrarTablaTarifas() {
    const termino = tarifaBusquedaProducto.toLowerCase();
    const filas = document.querySelectorAll('#tablaPreciosProductos tr');
    filas.forEach(fila => {
        const texto = fila.textContent.toLowerCase();
        fila.style.display = texto.includes(termino) ? '' : 'none';
    });
}

// Variable global para guardar el header HTML (con el input de búsqueda fijo)
let adminTablaHeaderHTML = '';

// Variable para guardar las categorías
let categoriasAdmin = [];

// Variable para identificar la sección actual
let seccionActual = '';

// Variable para controlar si se muestra el precio con o sin IVA
let mostrarConIva = false;

// Variable para controlar el IVA en la sección de Tarifas Prefijadas
let tarifasMostrarConIva = false;

// Variable para almacenar temporalmente los datos de una tarifa que tiene conflictos
let tarifaDataPendiente = null;

// Variable global para los tipos de IVA
let tiposIva = [];

/**
 * Carga los tipos de IVA desde la API y los guarda en la variable global.
 */
function cargarTiposIva() {
    return fetch('api/iva.php')
        .then(res => res.json())
        .then(data => {
            tiposIva = data;
            return data;
        })
        .catch(err => console.error('Error cargando tipos de IVA:', err));
}

/**
 * Verifica y aplica cambios de IVA programados
 */
function verificarCambiosIvaProgramados() {
    fetch('api/productos.php?accion=aplicar_cambios_iva_programados')
        .then(res => res.json())
        .then(data => {
            if (data.aplicados > 0) {
                console.log('Se aplicaron ' + data.aplicados + ' cambios de IVA programados');
                // Recargar tipos de IVA si estamos en esa sección
                cargarTiposIva();
            }
        })
        .catch(err => console.error('Error verificando cambios IVA programados:', err));
}

/**
 * Verifica y aplica ajustes de precios programados
 */
function verificarAjustesPreciosProgramados() {
    fetch('api/productos.php?accion=aplicar_ajustes_precios_programados')
        .then(res => res.json())
        .then(data => {
            if (data.aplicados > 0) {
                console.log('Se aplicaron ' + data.aplicados + ' ajustes de precios programados');
                // Recargar la sección actual si es necesario
                if (seccionActual === 'tarifa-ajuste') {
                    mostrarPanelAjustePrecios();
                }
            }
        })
        .catch(err => console.error('Error verificando ajustes de precios programados:', err));
}

/**
 * Abre el modal para crear un nuevo tipo de IVA.
 */
function abrirModalNuevoIva() {
    document.getElementById('editIvaId').value = '';
    document.getElementById('editIvaNombre').value = '';
    document.getElementById('editIvaPorcentaje').value = '';
    document.getElementById('editIvaTitulo').textContent = 'Nuevo Tipo de IVA';
    document.getElementById('editIvaSubtitulo').textContent = 'Introduce los datos del nuevo tipo de IVA';
    document.getElementById('modalEditarIva').style.display = 'flex';
}

/**
 * Abre el modal para editar un tipo de IVA existente.
 */
function editarIva(id, nombre, porcentaje) {
    document.getElementById('editIvaId').value = id;
    document.getElementById('editIvaNombre').value = nombre;
    document.getElementById('editIvaPorcentaje').value = porcentaje;
    document.getElementById('editIvaTitulo').textContent = 'Editar Tipo de IVA';
    document.getElementById('editIvaSubtitulo').textContent = 'Modifica los datos del tipo de IVA';
    document.getElementById('modalEditarIva').style.display = 'flex';
}

/**
 * Guarda los cambios de un tipo de IVA (crear o actualizar).
 */
function guardarIva() {
    const id = document.getElementById('editIvaId').value;
    const nombre = document.getElementById('editIvaNombre').value.trim();
    const porcentaje = parseFloat(document.getElementById('editIvaPorcentaje').value);

    if (!nombre) {
        alert('El nombre es obligatorio');
        return;
    }

    if (isNaN(porcentaje) || porcentaje < 0 || porcentaje > 100) {
        alert('El porcentaje debe estar entre 0 y 100');
        return;
    }

    const formData = new FormData();
    if (id) {
        formData.append('id', id);
    }
    formData.append('nombre', nombre);
    formData.append('porcentaje', porcentaje);

    fetch('api/iva.php', {
        method: 'POST',
        body: formData
    })
        .then(res => res.json())
        .then(data => {
            if (data.ok) {
                document.getElementById('modalEditarIva').style.display = 'none';
                cargarTiposIva().then(() => {
                    if (seccionActual === 'tarifa-iva') {
                        mostrarPanelCambiarIVA();
                    }
                });
                // Actualizar también los selects de IVA en otros modales
                actualizarSelectsIva();
            } else {
                alert(data.error || 'Error al guardar el tipo de IVA');
            }
        })
        .catch(err => {
            console.error('Error:', err);
            alert('Error al guardar el tipo de IVA');
        });
}

/**
 * Elimina un tipo de IVA.
 */
function eliminarIva(id) {
    if (!confirm('¿Estás seguro de que quieres eliminar este tipo de IVA?')) {
        return;
    }

    fetch('api/iva.php?eliminar=' + id, {
        method: 'DELETE'
    })
        .then(res => res.json())
        .then(data => {
            if (data.ok) {
                cargarTiposIva().then(() => {
                    if (seccionActual === 'tarifa-iva') {
                        mostrarPanelCambiarIVA();
                    }
                });
                actualizarSelectsIva();
            } else {
                alert(data.error || 'No se pudo eliminar el tipo de IVA');
            }
        })
        .catch(err => {
            console.error('Error:', err);
            alert('Error al eliminar el tipo de IVA');
        });
}

/**
 * Actualiza los selects de IVA en los formularios de productos.
 */
function actualizarSelectsIva() {
    // Actualizar select en modal de editar producto
    const selectIvaProducto = document.getElementById('editProductoIva');
    if (selectIvaProducto) {
        let opciones = '<option value="">Selecciona un tipo de IVA</option>';
        tiposIva.forEach(tipo => {
            opciones += `<option value="${tipo.id}">${tipo.porcentaje}% (${tipo.nombre})</option>`;
        });
        selectIvaProducto.innerHTML = opciones;
    }
}

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
                <button class="btn-admin-accion ${mostrarConIva ? 'btn-ver' : 'btn-editar'}" onclick="toggleMostrarIva()" style="min-width: 150px;">
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
        <div class="admin-tabla-wrapper">
            <table class="admin-tabla">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Imagen</th>
                        <th>Nombre</th>
                        <th>Categoría</th>
                        <th>Precio ${mostrarConIva ? '(PVP)' : '(Base)'}</th>
                        <th>Stock</th>
                        <th>Estado</th>
                        <th>IVA</th>
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
            contenedor.innerHTML = adminTablaHeaderHTML + '<tr><td colspan="9" class="sin-productos">No hay productos disponibles.</td></tr></tbody></table></div>';
        } else {
            // Búsquedas posteriores: solo actualizar el tbody
            const tbody = contenedor.querySelector('tbody');
            if (tbody) tbody.innerHTML = '<tr><td colspan="9" class="sin-productos">No hay productos disponibles.</td></tr>';
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
        // Calcular el precio a mostrar según el estado del toggle
        let precioMostrado = parseFloat(prod.precio);
        if (mostrarConIva) {
            // Usar check estricto para evitar que el 0 sea tratado como falsy
            const ivaPorcentaje = (prod.iva !== null && prod.iva !== undefined && prod.iva !== "") ? parseInt(prod.iva) : 21;
            precioMostrado = precioMostrado * (1 + (ivaPorcentaje / 100));
        }

        // Formatear el precio con 2 decimales y coma como separador decimal (formato europeo).
        const precioFmt = precioMostrado.toFixed(2).replace('.', ',');

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
                    <tr class="${prod.stock <= 0 ? 'fila-agotada' : ''}${prod.activo == 0 ? 'fila-inactiva' : ''}" 
                        data-precio-base="${prod.precio}" 
                        data-iva="${prod.iva}"
                        data-iva-id="${prod.idIva}"
                        data-iva-nombre="${prod.ivaNombre || ''}">
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
                        <td class="col-iva">${prod.iva}% (${prod.ivaNombre || 'General'})</td>
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
    document.getElementById('editProductoIva').value = '1';
    // Llenar el select de IVA dinámicamente
    const selectIva = document.getElementById('editProductoIva');
    selectIva.innerHTML = '';
    tiposIva.forEach(tipo => {
        const selected = tipo.id === 1 ? 'selected' : '';
        selectIva.innerHTML += `<option value="${tipo.id}" ${selected}>${tipo.porcentaje}% (${tipo.nombre})</option>`;
    });
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
 * Abre el modal de estadísticas de productos y carga los datos.
 */
async function abrirModalEstadisticasProductos() {
    const modal = document.getElementById('modalEstadisticasProductos');
    const contenido = document.getElementById('estadisticasProductosContenido');

    // Mostrar modal
    modal.style.display = 'flex';

    // Mostrar cargando
    contenido.innerHTML = '<div style="text-align: center; padding: 40px;"><i class="fas fa-spinner fa-spin" style="font-size: 40px; color: #3b82f6;"></i><p>Cargando estadísticas...</p></div>';

    try {
        const response = await fetch('api/ventas.php?accion=estadisticas_productos');
        const data = await response.json();

        if (data.success) {
            const stats = data.estadisticas;
            const isDark = document.body.classList.contains('dark-mode');
            const textColor = isDark ? '#e5e7eb' : '#1f2937';
            const cardBg = isDark ? '#374151' : '#f3f4f6';
            const borderColor = isDark ? '#4b5563' : '#e5e7eb';

            contenido.innerHTML = `
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; padding: 20px;">
                    <div style="background: ${cardBg}; padding: 20px; border-radius: 8px; border: 1px solid ${borderColor};">
                        <h4 style="margin: 0 0 10px 0; color: ${textColor};"><i class="fas fa-trophy" style="color: #fbbf24;"></i> Más vendido (Historia)</h4>
                        <p style="font-size: 18px; font-weight: bold; color: ${textColor}; margin: 0;">${stats.mas_vendido_historia?.nombre || 'Sin datos'}</p>
                        <p style="color: #059669; font-weight: bold; margin: 5px 0 0 0;">${stats.mas_vendido_historia?.cantidad || 0} unidades</p>
                    </div>
                    <div style="background: ${cardBg}; padding: 20px; border-radius: 8px; border: 1px solid ${borderColor};">
                        <h4 style="margin: 0 0 10px 0; color: ${textColor};"><i class="fas fa-calendar-alt" style="color: #3b82f6;"></i> Más vendido (Mes)</h4>
                        <p style="font-size: 18px; font-weight: bold; color: ${textColor}; margin: 0;">${stats.mas_vendido_mes?.nombre || 'Sin datos'}</p>
                        <p style="color: #059669; font-weight: bold; margin: 5px 0 0 0;">${stats.mas_vendido_mes?.cantidad || 0} unidades</p>
                    </div>
                    <div style="background: ${cardBg}; padding: 20px; border-radius: 8px; border: 1px solid ${borderColor};">
                        <h4 style="margin: 0 0 10px 0; color: ${textColor};"><i class="fas fa-calendar-week" style="color: #10b981;"></i> Más vendido (Semana)</h4>
                        <p style="font-size: 18px; font-weight: bold; color: ${textColor}; margin: 0;">${stats.mas_vendido_semana?.nombre || 'Sin datos'}</p>
                        <p style="color: #059669; font-weight: bold; margin: 5px 0 0 0;">${stats.mas_vendido_semana?.cantidad || 0} unidades</p>
                    </div>
                    <div style="background: ${cardBg}; padding: 20px; border-radius: 8px; border: 1px solid ${borderColor};">
                        <h4 style="margin: 0 0 10px 0; color: ${textColor};"><i class="fas fa-arrow-down" style="color: #ef4444;"></i> Menos vendido (Mes)</h4>
                        <p style="font-size: 18px; font-weight: bold; color: ${textColor}; margin: 0;">${stats.menos_vendido_mes?.nombre || 'Sin datos'}</p>
                        <p style="color: #dc2626; font-weight: bold; margin: 5px 0 0 0;">${stats.menos_vendido_mes?.cantidad || 0} unidades</p>
                    </div>
                </div>
            `;
        } else {
            contenido.innerHTML = `<p style="color: #dc2626; text-align: center;">Error: ${data.error || 'Error desconocido'}</p>`;
        }
    } catch (error) {
        console.error('Error cargando estadísticas:', error);
        contenido.innerHTML = `<p style="color: #dc2626; text-align: center;">Error al cargar las estadísticas</p>`;
    }
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

    // Extraer los datos del producto desde los atributos data de la fila y el DOM.
    const nombre = celdas[2].textContent.trim();
    const categoria = celdas[3].textContent.trim();
    const precio = fila.dataset.precioBase; // Usar el precio base real del atributo data
    const stock = celdas[5].textContent.trim();
    const activo = celdas[6].querySelector('.badge-activo') ? 1 : 0;
    const idIva = fila.dataset.ivaId; // Usar el ID de IVA del atributo data
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

    // Llenar el select de IVA dinámicamente
    const selectIva = document.getElementById('editProductoIva');
    selectIva.innerHTML = '';
    tiposIva.forEach(tipo => {
        const selected = tipo.id == idIva ? 'selected' : '';
        selectIva.innerHTML += `<option value="${tipo.id}" ${selected}>${tipo.porcentaje}% (${tipo.nombre})</option>`;
    });

    // Limpiar el input de archivo de imagen por si había una previsualización anterior.
    document.getElementById('editProductoImagenInput').value = '';

    // Cambiar el título y subtítulo para modo edición
    document.getElementById('editProductoTitulo').textContent = 'Editar Producto';
    document.getElementById('editProductoSubtitulo').textContent = 'Modifica los datos del producto';

    // Cargar las categorías en el select para permitir cambios
    const selectCategoria = document.getElementById('editProductoCategoria');
    selectCategoria.innerHTML = '<option value="">Selecciona una categoría</option>';
    categoriasAdmin.forEach(cat => {
        const selected = cat.nombre === categoria ? 'selected' : '';
        selectCategoria.innerHTML += `<option value="${cat.nombre}" ${selected}>${cat.nombre}</option>`;
    });
    selectCategoria.style.background = '#fff';
    selectCategoria.style.cursor = 'pointer';

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
    formData.append('idIva', document.getElementById('editProductoIva').value);
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

    // Extraer los datos del producto desde los atributos data y celdas del DOM.
    const nombre = celdas[2].textContent.trim();
    const categoria = celdas[3].textContent.trim();
    const precioBase = parseFloat(fila.dataset.precioBase);
    const ivaValue = parseInt(fila.dataset.iva) || 0;
    const precioPVP = precioBase * (1 + (ivaValue / 100));

    const stock = celdas[5].textContent.trim();
    const estado = celdas[6].querySelector('.admin-badge')?.textContent.trim() ?? '—';
    const ivaNombre = fila.dataset.ivaNombre || 'General';
    const ivaTexto = ivaValue + '% (' + ivaNombre + ')';
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
    document.getElementById('verProductoPrecio').textContent = (mostrarConIva ? precioPVP.toFixed(2).replace('.', ',') : precioBase.toFixed(2).replace('.', ',')) + ' €';
    document.getElementById('verProductoStock').textContent = stock;
    document.getElementById('verProductoIva').textContent = ivaTexto;

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

            // Mostrar permiso de crear productos
            const crearProductos = data.permisos && data.permisos.includes('crear_productos');
            document.getElementById('verUsuarioCrearProductos').innerHTML = crearProductos
                ? '<span class="admin-badge badge-activo">Sí</span>'
                : '<span class="admin-badge badge-inactivo">No</span>';

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

            // Si es el usuario admin (id=1), deshabilitar rol y estado
            const esAdmin = (data.id == 1);
            const rolSelect = document.getElementById('editUsuarioRol');
            const estadoSelect = document.getElementById('editUsuarioEstado');

            if (esAdmin) {
                rolSelect.disabled = true;
                estadoSelect.disabled = true;
                // Añadir clase para indicar visualmente que está deshabilitado
                rolSelect.style.opacity = '0.6';
                estadoSelect.style.opacity = '0.6';
            } else {
                rolSelect.disabled = false;
                estadoSelect.disabled = false;
                rolSelect.style.opacity = '1';
                estadoSelect.style.opacity = '1';
            }

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
    let rol = document.getElementById('editUsuarioRol').value;
    let activo = document.getElementById('editUsuarioEstado').value;

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

    // Si es el usuario admin (id=1), mantener su rol y estado originales
    // ya que los campos están deshabilitados pero sendrá el valor por defecto
    if (id == 1) {
        rol = 'admin';
        activo = '1';
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
                        <th>Tarifa</th>
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
                '<tr><td colspan="9" class="sin-productos">No hay ventas registradas.</td></tr></tbody></table></div>';
        } else {
            const tbody = contenedor.querySelector('tbody');
            if (tbody) tbody.innerHTML = '<tr><td colspan="9" class="sin-productos">No hay ventas registradas.</td></tr>';
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

        // Formatear tarifa
        let tarifaNombre = venta.tarifa_nombre || 'Cliente';

        html += `
            <tr>
                <td class="col-id">${venta.id}</td>
                <td class="col-fecha">${fecha}</td>
                <td class="col-usuario">${venta.usuario_nombre || '—'}</td>
                <td class="col-productos">${productos}</td>
                <td class="col-tarifa">${tarifaNombre}</td>
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
            const descuentos = data.descuentos || {};

            // Detectar tema actual
            const isDark = document.body.classList.contains('dark-mode');
            const bgColor = isDark ? '#1f2937' : '#fff';
            const textColor = isDark ? '#e5e7eb' : '#374151';
            const cardBg = isDark ? '#374151' : '#f8f9fa';
            const borderColor = isDark ? '#4b5563' : '#e5e7eb';
            const discountBg = isDark ? '#064e3b' : '#ecfdf5';
            const discountBorder = isDark ? '#065f46' : '#a7f3d0';
            const discountColor = isDark ? '#34d399' : '#16a34a';
            const headerBg = isDark ? '#4b5563' : '#374151';
            const totalBg = isDark ? 'linear-gradient(135deg, #059669 0%, #10b981 100%)' : 'linear-gradient(135deg, #11998e 0%, #38ef7d 100%)';

            const fecha = new Date(venta.fecha).toLocaleString('es-ES', {
                day: '2-digit', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit'
            });

            const tipoDoc = venta.tipoDocumento === 'factura' ? '📄 Factura' : '🧾 Ticket';

            // Procesar descuentos para mostrar
            let descuentoHtml = '';
            if (descuentos.descuentoTarifaCupon === 'CLIENTE_REGISTRADO') {
                descuentoHtml += `<div style="color: ${discountColor};"><strong>Cliente registrado:</strong> ${descuentos.descuentoTarifaValor}%</div>`;
            } else if (descuentos.descuentoTarifaCupon === 'MAYORISTA_NIVEL1') {
                descuentoHtml += `<div style="color: ${discountColor};"><strong>Mayorista nivel 1:</strong> ${descuentos.descuentoTarifaValor}%</div>`;
            } else if (descuentos.descuentoTarifaCupon === 'MAYORISTA_NIVEL2') {
                descuentoHtml += `<div style="color: ${discountColor};"><strong>Mayorista nivel 2:</strong> ${descuentos.descuentoTarifaValor}%</div>`;
            }
            if (descuentos.descuentoManualCupon && descuentos.descuentoManualCupon !== '') {
                if (descuentos.descuentoManualTipo === 'porcentaje') {
                    descuentoHtml += `<div style="color: ${discountColor};"><strong>Descuento:</strong> ${descuentos.descuentoManualValor}%</div>`;
                } else {
                    descuentoHtml += `<div style="color: ${discountColor};"><strong>Cupón:</strong> ${descuentos.descuentoManualCupon}</div>`;
                }
            } else if (descuentos.descuentoCupon && descuentos.descuentoCupon !== '' && descuentos.descuentoCupon !== 'CLIENTE_REGISTRADO' && descuentos.descuentoCupon !== 'MAYORISTA_NIVEL1' && descuentos.descuentoCupon !== 'MAYORISTA_NIVEL2') {
                descuentoHtml += `<div style="color: ${discountColor};"><strong>Cupón:</strong> ${descuentos.descuentoCupon}</div>`;
            }

            let html = `
                <div style="border-radius: 12px; overflow: hidden; background: ${bgColor};">
                    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <h3 style="margin: 0; font-size: 18px;">Venta #${venta.id}</h3>
                            <span style="font-size: 14px; opacity: 0.9;">${fecha}</span>
                        </div>
                    </div>
                    <div style="padding: 20px;">
                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 20px; padding: 15px; background: ${cardBg}; border-radius: 8px;">
                            <div style="color: ${textColor};"><strong>👤 Usuario:</strong><br>${venta.usuario_nombre || '—'}</div>
                            <div style="color: ${textColor};"><strong>📄 Tipo:</strong><br>${tipoDoc}</div>
                            <div style="color: ${textColor};"><strong>💳 Pago:</strong><br>${venta.metodoPago || '💵 Efectivo'}</div>
                        </div>`;

            if (descuentoHtml) {
                html += `<div style="margin-bottom: 20px; padding: 15px; background: ${discountBg}; border-radius: 8px; border: 1px solid ${discountBorder};">
                            <strong style="color: ${discountColor};">💰 Descuentos aplicados:</strong><br>
                            ${descuentoHtml}
                        </div>`;
            }

            html += `<h4 style="margin: 0 0 10px 0; color: ${textColor};">Productos:</h4>
                        <div style="max-height: 250px; overflow-y: auto; border: 1px solid ${borderColor}; border-radius: 8px;">
                            <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
                                <thead>
                                    <tr style="background: ${headerBg}; color: white;">
                                        <th style="padding: 10px; text-align: left;">Producto</th>
                                        <th style="padding: 10px; text-align: center;">Cant.</th>
                                        <th style="padding: 10px; text-align: right;">P.U.</th>
                                        <th style="padding: 10px; text-align: center;">IVA</th>
                                        <th style="padding: 10px; text-align: right;">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>`;

            lineas.forEach(linea => {
                const iva = linea.iva || 21;
                const precioBase = parseFloat(linea.precioUnitario);
                const precioBaseOriginal = parseFloat(linea.precioOriginal || linea.precioUnitario);
                const pvpUnitario = precioBase * (1 + iva / 100);
                const pvpOriginal = precioBaseOriginal * (1 + iva / 100);
                // El subtotal de la línea debe incluir el IVA para ser consistente con el total final
                const subtotalValor = linea.cantidad * pvpUnitario;
                const subtotalTexto = subtotalValor.toFixed(2).replace('.', ',');

                const tieneTarifa = linea.tarifaNombre && linea.tarifaNombre !== '' && linea.tarifaNombre !== 'Tarifa';
                const ahorroUnitario = pvpOriginal - pvpUnitario;
                const ahorroTotal = ahorroUnitario * linea.cantidad;

                html += `
                    <tr>
                        <td style="padding: 10px; border-bottom: 1px solid ${borderColor}; color: ${textColor};">
                            ${linea.producto_nombre || 'Producto #' + linea.idProducto}
                            ${tieneTarifa ? `<br><small style="color: ${discountColor}; font-size: 0.75rem;">* Tarifa: ${linea.tarifaNombre} (Ahorro: ${(ahorroTotal).toFixed(2).replace('.', ',')} €)</small>` : ''}
                        </td>
                        <td style="padding: 10px; border-bottom: 1px solid ${borderColor}; text-align: center; color: ${textColor};">${linea.cantidad}</td>
                        <td style="padding: 10px; border-bottom: 1px solid ${borderColor}; text-align: right; color: ${textColor};">
                            ${pvpUnitario.toFixed(2).replace('.', ',')} €
                        </td>
                        <td style="padding: 10px; border-bottom: 1px solid ${borderColor}; text-align: center; color: ${textColor};">${iva}%</td>
                        <td style="padding: 10px; border-bottom: 1px solid ${borderColor}; text-align: right; font-weight: 600; color: ${textColor};">
                            ${subtotalTexto} €
                        </td>
                    </tr>`;
            });

            html += `
                                </tbody>
                            </table>
                        </div>`;

            // Fila de Descuento Manual si existe
            if (descuentos.descuentoManualValor && parseFloat(descuentos.descuentoManualValor) > 0) {
                const descVal = parseFloat(descuentos.descuentoManualValor);
                const descTexto = descuentos.descuentoManualTipo === 'porcentaje' ? descVal + '%' : descVal.toFixed(2).replace('.', ',') + '€';
                html += `
                    <div style="margin-top: 10px; padding: 10px; background: ${discountBg}; border: 1px solid ${discountBorder}; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; color: ${discountColor};">
                        <span><strong>Descuento Manual:</strong> ${descuentos.descuentoManualCupon || ''}</span>
                        <span style="font-weight: bold;">-${descTexto}</span>
                    </div>`;
            }

            html += `
                        <div style="margin-top: 15px; padding: 15px; background: ${totalBg}; color: white; border-radius: 8px; text-align: center; font-weight: bold; font-size: 18px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                            TOTAL: ${parseFloat(venta.total).toFixed(2).replace('.', ',')} €
                        </div>
                        <div style="margin-top: 20px; text-align: right;">
                            <button class="btn-modal-cancelar" onclick="cerrarModal('modalVerVenta')">Cerrar</button>
                        </div>
                    </div>
                </div>`;

            // Crear o mostrar el modal
            let modal = document.getElementById('modalVerVenta');
            if (!modal) {
                modal = document.createElement('div');
                modal.id = 'modalVerVenta';
                modal.className = 'modal-overlay';
                modal.style.display = 'none';
                modal.innerHTML = '<div class="modal-content" style="max-width: 700px; max-height: 85vh; overflow-y: auto;"></div>';
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
let debounceTimerCategorias;

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
    header_icon: '',  // SVG code for custom header icon
    favicon: ''      // Path to favicon image
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
    { id: 'iconos', titulo: 'Iconos', icono: 'fa-icons', tipo: 'iconos' }
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
    // Si es la sección de iconos, generar HTML especial
    if (seccion.tipo === 'iconos') {
        const headerIconVal = temaActual['header_icon'] || '';
        const faviconVal = temaActual['favicon'] || '';

        return `
            <div class="tema-seccion-card">
                <div class="tema-seccion-header">
                    <i class="fas ${seccion.icono} tema-seccion-icono"></i>
                    <h4 class="tema-seccion-titulo">${seccion.titulo}</h4>
                </div>
                <div class="tema-seccion-body">
                    <div class="tema-campo">
                        <label class="tema-label">Icono del Header (SVG)</label>
                        <textarea id="tema_header_icon" class="tema-textarea-icono" 
                            placeholder="Pega el código SVG aquí..." 
                            oninput="previsualizarIcono()">${headerIconVal}</textarea>
                        <p class="tema-ayuda">Pega el código completo de un icono SVG (incluyendo las etiquetas &lt;svg&gt;)</p>
                    </div>
                    <div class="tema-preview-icono" id="preview_header_icon">
                        ${headerIconVal ? headerIconVal : '<span style="color: var(--text-muted);">Vista previa del icono</span>'}
                    </div>
                    <div class="tema-campo" style="margin-top: 20px;">
                        <label class="tema-label">Favicon</label>
                        <input type="file" id="tema_favicon" accept="image/*" onchange="previsualizarFavicon(this)">
                        <p class="tema-ayuda">Sube una imagen para el favicon (16x16, 32x32 o 48x48 píxeles)</p>
                    </div>
                    <div class="tema-preview-favicon" id="preview_favicon">
                        ${faviconVal ? `<img src="${faviconVal}" alt="Favicon" style="width: 32px; height: 32px;">` : '<span style="color: var(--text-muted);">Vista previa del favicon</span>'}
                    </div>
                </div>
            </div>
        `;
    }

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
 * Carga y muestra los logs del sistema.
 */
function cargarLogs(filtros = {}) {
    seccionActual = 'logs';
    adminTablaHeaderHTML = '';

    // Inicializar filtros si no existen
    if (window.filtroFechaLog === undefined) {
        window.filtroFechaLog = '';
    }
    if (window.filtroTipoLog === undefined) {
        window.filtroTipoLog = '';
    }

    const contenedor = document.getElementById('adminContenido');
    contenedor.innerHTML = '<p style="text-align: center; padding: 40px; color: var(--text-muted);">Cargando logs...</p>';

    // Construir URL con filtros
    const params = new URLSearchParams(filtros);
    params.set('pagina', filtros.pagina || 1);
    params.set('por_pagina', filtros.porPagina || 50);

    const url = 'api/logs.php?' + params.toString();
    console.log('Cargar logs - URL:', url);

    fetch(url)
        .then(res => res.json())
        .then(data => {
            // Guardar los datos globalmente para acceder desde verDetalleLog
            window.logsData = data.logs || [];
            renderLogs(data);
        })
        .catch(err => {
            console.error('Error cargando logs:', err);
            contenedor.innerHTML = '<p style="text-align: center; padding: 40px; color: var(--error);">Error al cargar los logs</p>';
        });
}

/**
 * Renderiza la tabla de logs.
 */
function renderLogs(data) {
    const contenedor = document.getElementById('adminContenido');
    const logs = data.logs || [];

    // Tipos de log para el filtro
    const tiposLog = [
        { valor: '', texto: 'Todos los tipos' },
        { valor: 'login', texto: 'Inicios de sesión' },
        { valor: 'login_fallido', texto: 'Credenciales incorrectas' },
        { valor: 'logout', texto: 'Cierres de sesión' },
        { valor: 'venta', texto: 'Ventas' },
        { valor: 'devolucion', texto: 'Devoluciones' },
        { valor: 'apertura_caja', texto: 'Apertura de caja' },
        { valor: 'cierre_caja', texto: 'Cierre de caja' },
        { valor: 'retiro_caja', texto: 'Retiros de caja' },
        { valor: 'creacion_usuario', texto: 'Creación de usuarios' },
        { valor: 'modificacion_usuario', texto: 'Modificación de usuarios' },
        { valor: 'eliminacion_usuario', texto: 'Eliminación de usuarios' },
        { valor: 'creacion_producto', texto: 'Creación de productos' },
        { valor: 'modificacion_producto', texto: 'Modificación de productos' },
        { valor: 'eliminacion_producto', texto: 'Eliminación de productos' },
        { valor: 'creacion_categoria', texto: 'Creación de categorías' },
        { valor: 'modificacion_categoria', texto: 'Modificación de categorías' },
        { valor: 'eliminacion_categoria', texto: 'Eliminación de categorías' },
        { valor: 'acceso_admin', texto: 'Accesos al admin' }
    ];

    const tipoSelect = tiposLog.map(t =>
        `<option value="${t.valor}" ${(window.filtroTipoLog || '') === t.valor ? 'selected' : ''}>${t.texto}</option>`
    ).join('');

    // Guardar los tipos para usarlos en el render
    window.tiposLogMap = tiposLog;

    let html = `
        <div class="logs-container">
            <div class="logs-filtros">
                <div class="logs-filtro-item">
                    <label>Tipo de evento:</label>
                    <select id="filtroTipoLog" onchange="aplicarFiltroLogs()">
                        ${tipoSelect}
                    </select>
                </div>
                <div class="logs-filtro-item">
                    <label>Fecha:</label>
                    <div style="display: flex; align-items: center; gap: 5px;">
                        <input type="date" id="filtroFecha" value="${window.filtroFechaLog || ''}" onchange="aplicarFiltroLogs()">
                        <button onclick="limpiarFiltroFecha()" style="padding: 4px 8px; background: #6b7280; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 0.8rem;" title="Limpiar fecha">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <div class="logs-filtro-item" style="margin-left: auto;">
                    <button onclick="limpiarLogs()" style="background: #dc3545; color: white; padding: 8px 16px; border: none; border-radius: 5px; cursor: pointer; font-size: 0.85rem;">
                        <i class="fas fa-trash"></i> Limpiar logs
                    </button>
                </div>
            </div>
            
            <div class="logs-tabla-container">
                <table class="admin-tabla logs-tabla">
                    <thead>
                        <tr>
                            <th>Fecha/Hora</th>
                            <th>Tipo</th>
                            <th>Usuario</th>
                            <th>Descripción</th>
                            <th>Detalles</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
    `;

    if (logs.length === 0) {
        html += `
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px; color: var(--text-muted);">
                                No se encontraron logs
                            </td>
                        </tr>
        `;
    } else {
        logs.forEach(log => {
            const tipoIcono = getTipoLogIcono(log.tipo);
            const tipoClase = getTipoLogClase(log.tipo);
            const fecha = new Date(log.fecha).toLocaleString('es-ES');
            const detalles = log.detalles ? JSON.stringify(log.detalles) : '-';

            html += `
                        <tr>
                            <td>${fecha}</td>
                            <td><span class="logs-tipo ${tipoClase}"><i class="${tipoIcono}"></i> ${getTipoLogTexto(log.tipo)}</span></td>
                            <td>${log.usuario_nombre || '-'}</td>
                            <td>${log.descripcion || '-'}</td>
                            <td style="font-size: 0.8rem; max-width: 200px; overflow: hidden; text-overflow: ellipsis;" title="${detalles}">${detalles}</td>
                            <td>
                                <button class="btn-admin-accion btn-ver" onclick="verDetalleLog(${log.id})" title="Ver detalles">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>
            `;
        });
    }

    html += `
                    </tbody>
                </table>
            </div>
        </div>
    `;

    contenedor.innerHTML = html;
}

/**
 * Aplica los filtros y recarga los logs.
/**
 * Muestra los detalles de un log en un modal.
 */
function verDetalleLog(idLog) {
    // Buscar el log en los datos actuales
    const log = window.logsData?.find(l => l.id === idLog);
    if (!log) {
        alert('No se encontraron los detalles del log');
        return;
    }

    const tipoIcono = getTipoLogIcono(log.tipo);
    const tipoClase = getTipoLogClase(log.tipo);
    const fecha = new Date(log.fecha).toLocaleString('es-ES');

    // Detectar el tema actual
    const isDarkMode = document.body.classList.contains('dark-mode');

    // Colores según el tema
    const colors = isDarkMode ? {
        background: '#1f2937',
        text: '#e5e7eb',
        textMuted: '#9ca3af',
        border: '#4b5563',
        headerBg: '#374151',
        buttonBg: '#4b5563',
        buttonText: '#f3f4f6',
        delete: '#f87171',
        add: '#34d399'
    } : {
        background: '#ffffff',
        text: '#333333',
        textMuted: '#666666',
        border: '#e0e0e0',
        headerBg: '#f3f4f6',
        buttonBg: '#e5e7eb',
        buttonText: '#333333',
        delete: '#dc2626',
        add: '#059669'
    };

    // Formatear los detalles como una tabla
    let detallesHtml = `<p style="margin: 5px 0; color: ${colors.textMuted};">Sin detalles</p>`;
    if (log.detalles && Object.keys(log.detalles).length > 0) {
        // Verificar si es un array de cambios (formato de modificación de producto)
        if (Array.isArray(log.detalles) && log.detalles.length > 0 && log.detalles[0].campo) {
            // Formato nuevo: array de cambios con campo, anterior, nuevo
            detallesHtml = '<table style="width: 100%; margin-top: 5px; border-collapse: collapse;">';
            detallesHtml += `<tr style="background: ${colors.headerBg};"><th style="padding: 5px; text-align: left; font-size: 0.85rem; color: ${colors.text};">Campo</th><th style="padding: 5px; text-align: left; font-size: 0.85rem; color: ${colors.text};">Valor Anterior</th><th style="padding: 5px; text-align: left; font-size: 0.85rem; color: ${colors.text};">Valor Nuevo</th></tr>`;
            log.detalles.forEach(cambio => {
                let valorAnterior = cambio.anterior !== null ? cambio.anterior : '(vacío)';
                let valorNuevo = cambio.nuevo !== null ? cambio.nuevo : '(vacío)';
                // Resaltar cambios en rojo/verde
                detallesHtml += `<tr style="border-bottom: 1px solid ${colors.border};">
                    <td style="padding: 5px; font-weight: bold; color: ${colors.text};">${cambio.campo}</td>
                    <td style="padding: 5px; color: ${colors.delete}; text-decoration: line-through;">${valorAnterior}</td>
                    <td style="padding: 5px; color: ${colors.add}; font-weight: bold;">${valorNuevo}</td>
                </tr>`;
            });
            detallesHtml += '</table>';
        } else if (Array.isArray(log.detalles) && log.detalles.length > 0 && log.detalles[0].antes) {
            // Formato de usuario: array de cambios con antes, después
            detallesHtml = '<table style="width: 100%; margin-top: 5px; border-collapse: collapse;">';
            detallesHtml += `<tr style="background: ${colors.headerBg};"><th style="padding: 5px; text-align: left; font-size: 0.85rem; color: ${colors.text};">Campo</th><th style="padding: 5px; text-align: left; font-size: 0.85rem; color: ${colors.text};">Antes</th><th style="padding: 5px; text-align: left; font-size: 0.85rem; color: ${colors.text};">Después</th></tr>`;
            log.detalles.forEach(cambio => {
                let antes = cambio.antes !== null ? cambio.antes : '(vacío)';
                let despues = cambio.despues !== null ? cambio.despues : '(vacío)';
                detallesHtml += `<tr style="border-bottom: 1px solid ${colors.border};">
                    <td style="padding: 5px; font-weight: bold; color: ${colors.text};">${cambio.campo || Object.keys(cambio)[0]}</td>
                    <td style="padding: 5px; color: ${colors.delete}; text-decoration: line-through;">${antes}</td>
                    <td style="padding: 5px; color: ${colors.add}; font-weight: bold;">${despues}</td>
                </tr>`;
            });
            detallesHtml += '</table>';
        } else {
            // Formato genérico: objeto con clave-valor
            detallesHtml = '<table style="width: 100%; margin-top: 5px; border-collapse: collapse;">';
            for (const [key, value] of Object.entries(log.detalles)) {
                let displayValue = value;

                // Traducir claves al español para devoluciones
                let keyDisplay = key;
                if (log.tipo === 'devolucion') {
                    if (key === 'ticket') keyDisplay = 'Número de Ticket';
                    else if (key === 'productos_devueltos') keyDisplay = 'Productos Devueltos';
                    else if (key === 'total_devolucion') keyDisplay = 'Total Devolución';
                }

                if (typeof value === 'object' && value !== null) {
                    displayValue = JSON.stringify(value, null, 2);
                } else if (typeof value === 'number') {
                    // Para devoluciones, no mostrar decimales en ticket ni productos
                    if (log.tipo === 'devolucion' && (key === 'ticket' || key === 'productos_devueltos')) {
                        displayValue = Math.round(value);
                    } else if (log.tipo === 'devolucion' && key === 'total_devolucion') {
                        displayValue = parseFloat(value).toFixed(2).replace('.', ',') + ' €';
                    } else {
                        displayValue = parseFloat(value).toFixed(2).replace('.', ',');
                    }
                }
                detallesHtml += `<tr style="border-bottom: 1px solid ${colors.border};">
                    <td style="padding: 5px; font-weight: bold; width: 40%; color: ${colors.textMuted};">${keyDisplay}</td>
                    <td style="padding: 5px; color: ${colors.text}; word-break: break-all;">${displayValue}</td>
                </tr>`;
            }
            detallesHtml += '</table>';
        }
    }

    const modalContent = `
        <div style="padding: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h3 style="margin: 0; color: ${colors.text}; font-size: 1.2rem;">Detalles del Log</h3>
                <button onclick="this.closest('#modalDetalleLog').remove()" style="background: ${colors.buttonBg}; border: none; font-size: 1.5rem; cursor: pointer; color: ${colors.buttonText}; line-height: 1; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center;" title="Cerrar">&times;</button>
            </div>
            <div style="color: ${colors.text}; font-size: 0.9rem;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr style="border-bottom: 1px solid ${colors.border};">
                        <td style="padding: 8px 5px; font-weight: bold; color: ${colors.textMuted}; width: 35%;">ID</td>
                        <td style="padding: 8px 5px; color: ${colors.text};">${log.id}</td>
                    </tr>
                    <tr style="border-bottom: 1px solid ${colors.border};">
                        <td style="padding: 8px 5px; font-weight: bold; color: ${colors.textMuted};">Fecha/Hora</td>
                        <td style="padding: 8px 5px; color: ${colors.text};">${fecha}</td>
                    </tr>
                    <tr style="border-bottom: 1px solid ${colors.border};">
                        <td style="padding: 8px 5px; font-weight: bold; color: ${colors.textMuted};">Tipo</td>
                        <td style="padding: 8px 5px;"><span class="logs-tipo ${tipoClase}"><i class="${tipoIcono}"></i> ${getTipoLogTexto(log.tipo)}</span></td>
                    </tr>
                    <tr style="border-bottom: 1px solid ${colors.border};">
                        <td style="padding: 8px 5px; font-weight: bold; color: ${colors.textMuted};">Usuario</td>
                        <td style="padding: 8px 5px; color: ${colors.text};">${log.usuario_nombre || 'Sistema'} (ID: ${log.usuario_id || '-'})</td>
                    </tr>
                    <tr style="border-bottom: 1px solid ${colors.border};">
                        <td style="padding: 8px 5px; font-weight: bold; color: ${colors.textMuted};">Descripción</td>
                        <td style="padding: 8px 5px; color: ${colors.text};">${log.descripcion || '-'}</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 5px; font-weight: bold; color: ${colors.textMuted}; vertical-align: top;">Detalles</td>
                        <td style="padding: 8px 5px;">${detallesHtml}</td>
                    </tr>
                </table>
            </div>
            <div style="margin-top: 20px; text-align: right;">
                <button onclick="document.getElementById('modalDetalleLog').remove()" style="background: #3b82f6; color: white; padding: 8px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 0.9rem;">Cerrar</button>
            </div>
        </div>
    `;

    // Eliminar modal anterior si existe
    const existingModal = document.getElementById('modalDetalleLog');
    if (existingModal) {
        existingModal.remove();
    }

    // Crear el modal
    const modalDiv = document.createElement('div');
    modalDiv.id = 'modalDetalleLog';
    modalDiv.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 10000; display: flex; justify-content: center; align-items: center;';

    const modalInner = document.createElement('div');
    modalInner.style.cssText = `background: ${colors.background}; border-radius: 10px; width: 90%; max-width: 550px; max-height: 85vh; overflow-y: auto; box-shadow: 0 4px 20px rgba(0,0,0,0.5); color: ${colors.text}; border: 1px solid ${colors.border};`;
    modalInner.innerHTML = modalContent;

    modalDiv.appendChild(modalInner);
    document.body.appendChild(modalDiv);

    // Cerrar al hacer clic fuera
    modalDiv.addEventListener('click', function (e) {
        if (e.target === modalDiv) {
            modalDiv.remove();
        }
    });
}

/**
 * Aplica los filtros y recarga los logs.
 */
function aplicarFiltroLogs() {
    let tipoSeleccionado = document.getElementById('filtroTipoLog')?.value || '';
    const fechaInput = document.getElementById('filtroFecha');
    const fecha = fechaInput?.value || '';

    // Verificar que la fecha tenga formato completo (YYYY-MM-DD) antes de aplicar el filtro
    // Esto evita que el filtro se aplique mientras se selecciona la fecha en el picker
    if (fecha && fecha.length < 10) {
        return; // No aplicar filtro si la fecha no está completa
    }

    // Guardar filtros en variables globales para preservar entre renderizados
    window.filtroFechaLog = fecha;

    console.log('Aplicar filtro - Tipo:', tipoSeleccionado, 'Fecha:', fecha);

    // Verificar si hay mapeo de tipos múltiples
    const tipoMultiMap = {
        'login': 'login,login_fallido'
    };

    // Si el tipo tiene mapeo múltiple, usarlo
    if (tipoMultiMap[tipoSeleccionado]) {
        tipoSeleccionado = tipoMultiMap[tipoSeleccionado];
    }

    window.filtroTipoLog = tipoSeleccionado;

    cargarLogs({
        tipo: tipoSeleccionado,
        fecha: fecha
    });
}

/**
 * Limpia el filtro de fecha y recarga los logs.
 */
function limpiarFiltroFecha() {
    window.filtroFechaLog = '';
    aplicarFiltroLogs();
}

/**
 * Limpia todos los logs del sistema.
 */
function limpiarLogs() {
    if (!confirm('¿Estás seguro de que quieres eliminar todos los logs? Esta acción no se puede deshacer.')) {
        return;
    }

    fetch('api/logs.php?accion=limpiar', {
        method: 'POST'
    })
        .then(res => res.json())
        .then(data => {
            if (data.ok) {
                alert('Logs eliminados correctamente');
                cargarLogs({});
            } else {
                alert('Error al eliminar los logs: ' + (data.error || 'Error desconocido'));
            }
        })
        .catch(err => {
            console.error('Error al limpiar logs:', err);
            alert('Error al conectar con el servidor');
        });
}

/**
 * Obtiene el icono para el tipo de log.
 */
function getTipoLogIcono(tipo) {
    const iconos = {
        'login': 'fas fa-sign-in-alt',
        'login_fallido': 'fas fa-times-circle',
        'logout': 'fas fa-sign-out-alt',
        'venta': 'fas fa-shopping-cart',
        'devolucion': 'fas fa-undo-alt',
        'apertura_caja': 'fas fa-cash-register',
        'cierre_caja': 'fas fa-money-check',
        'retiro_caja': 'fas fa-money-bill-wave',
        'acceso_admin': 'fas fa-user-shield',
        'acceso_cajero': 'fas fa-user',
        'acceso_login': 'fas fa-door-open',
        'creacion_usuario': 'fas fa-user-plus',
        'modificacion_usuario': 'fas fa-user-edit',
        'eliminacion_usuario': 'fas fa-user-minus',
        'creacion_producto': 'fas fa-box-plus',
        'modificacion_producto': 'fas fa-box-open',
        'eliminacion_producto': 'fas fa-trash',
        'creacion_categoria': 'fas fa-folder-plus',
        'modificacion_categoria': 'fas fa-folder-open',
        'eliminacion_categoria': 'fas fa-folder-minus'
    };
    return iconos[tipo] || 'fas fa-info-circle';
}

/**
 * Obtiene la clase CSS para el tipo de log.
 */
function getTipoLogClase(tipo) {
    const clases = {
        'login': 'logs-login',
        'login_fallido': 'logs-error',
        'logout': 'logs-logout',
        'venta': 'logs-venta',
        'devolucion': 'logs-retiro',
        'apertura_caja': 'logs-caja',
        'cierre_caja': 'logs-caja',
        'retiro_caja': 'logs-retiro',
        'acceso_admin': 'logs-admin',
        'creacion_usuario': 'logs-usuario',
        'modificacion_usuario': 'logs-usuario',
        'eliminacion_usuario': 'logs-usuario',
        'creacion_producto': 'logs-producto',
        'modificacion_producto': 'logs-producto',
        'eliminacion_producto': 'logs-producto',
        'creacion_categoria': 'logs-categoria',
        'modificacion_categoria': 'logs-categoria',
        'eliminacion_categoria': 'logs-categoria'
    };
    return clases[tipo] || '';
}

/**
 * Obtiene el texto legible para el tipo de log.
 */
function getTipoLogTexto(tipo) {
    const textos = {
        'login': 'Login',
        'login_fallido': 'Credenciales incorrectas',
        'logout': 'Logout',
        'venta': 'Venta',
        'devolucion': 'Devolución',
        'apertura_caja': 'Apertura Caja',
        'cierre_caja': 'Cierre Caja',
        'retiro_caja': 'Retiro',
        'acceso_admin': 'Acceso Admin',
        'acceso_cajero': 'Acceso Cajero',
        'acceso_login': 'Acceso Login',
        'creacion_usuario': 'Usuario Creado',
        'modificacion_usuario': 'Usuario Modificado',
        'eliminacion_usuario': 'Usuario Eliminado',
        'creacion_producto': 'Producto Creado',
        'modificacion_producto': 'Producto Modificado',
        'eliminacion_producto': 'Producto Eliminado',
        'creacion_categoria': 'Categoría Creada',
        'modificacion_categoria': 'Categoría Modificada',
        'eliminacion_categoria': 'Categoría Eliminada'
    };
    return textos[tipo] || tipo;
}

/**
 * Renderiza el editor de tema completo.
 */
function renderEditorTema() {
    const contenedor = document.getElementById('adminContenido');

    let html = `
        <div class="tema-editor">
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
 * Previsualiza el icono del header en tiempo real.
 */
function previsualizarIcono() {
    const svgInput = document.getElementById('tema_header_icon');
    const preview = document.getElementById('preview_header_icon');

    if (!svgInput || !preview) return;

    const svgCode = svgInput.value.trim();
    if (svgCode) {
        preview.innerHTML = svgCode;
        // Ajustar tamaño del icono en el preview
        const svg = preview.querySelector('svg');
        if (svg) {
            svg.style.width = '48px';
            svg.style.height = '48px';
        }
    } else {
        preview.innerHTML = '<span style="color: var(--text-muted);">Vista previa del icono</span>';
    }
}

/**
 * Previsualiza el favicon cuando se selecciona un archivo.
 */
function previsualizarFavicon(input) {
    const preview = document.getElementById('preview_favicon');

    if (!preview || !input.files || !input.files[0]) return;

    const file = input.files[0];
    const reader = new FileReader();

    reader.onload = function (e) {
        preview.innerHTML = `<img src="${e.target.result}" alt="Favicon" style="width: 32px; height: 32px;">`;
    };

    reader.readAsDataURL(file);
}

/**
 * Guarda toda la configuración del tema via POST a la API.
 */
function guardarTema() {
    const datos = {};

    // Primero, copiar todos los valores actuales de temaActual como base
    Object.assign(datos, temaActual);

    // Luego, sobrescribir con los valores de los inputs
    SECCIONES_TEMA.forEach(seccion => {
        if (seccion.bgKey) {
            const bgInput = document.getElementById('tema_' + seccion.bgKey);
            if (bgInput) datos[seccion.bgKey] = bgInput.value;
        }
        if (seccion.colorKey) {
            const colorInput = document.getElementById('tema_' + seccion.colorKey);
            if (colorInput) datos[seccion.colorKey] = colorInput.value;
        }
        if (seccion.fontKey) {
            const fontSelect = document.getElementById('tema_' + seccion.fontKey);
            if (fontSelect) datos[seccion.fontKey] = fontSelect.value;
        }
    });

    // Actualizar icono SVG del header desde el textarea
    const headerIconInput = document.getElementById('tema_header_icon');
    if (headerIconInput && headerIconInput.value.trim()) {
        datos['header_icon'] = headerIconInput.value;
    }

    // Manejar favicon - convertir a base64 si se ha seleccionado un archivo
    const faviconInput = document.getElementById('tema_favicon');

    if (faviconInput && faviconInput.files && faviconInput.files[0]) {
        const file = faviconInput.files[0];
        const reader = new FileReader();
        reader.readAsDataURL(file);
        reader.onload = function () {
            datos['favicon'] = reader.result;
            guardarTemaCompleto(datos);
        };
        reader.onerror = function () {
            alert('Error al leer el archivo del favicon');
        };
    } else {
        // Preservar el favicon existente si no se seleccionó nuevo archivo
        guardarTemaCompleto(datos);
    }
}

/**
 * Envía los datos del tema a la API.
 */
function guardarTemaCompleto(datos) {
    // Actualizar temaActual con los nuevos valores antes de guardar
    temaActual = { ...temaActual, ...datos };

    // Guardar en localStorage para acceso rápido
    localStorage.setItem('temaTPV', JSON.stringify(datos));
    console.log('Tema guardado en localStorage:', datos);

    // Aplicar tema inmediatamente en la vista actual
    if (typeof aplicarTemaGuardado === 'function') {
        aplicarTemaGuardado();
    }

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

        // Aplicar icono personalizado del header
        if (tema.header_icon) {
            const iconContainer = document.getElementById('header-icon-container');
            if (iconContainer) {
                iconContainer.innerHTML = tema.header_icon;
                const svg = iconContainer.querySelector('svg');
                if (svg) {
                    svg.setAttribute('width', '36');
                    svg.setAttribute('height', '36');
                }
            }
        }

        // Aplicar favicon personalizado
        if (tema.favicon) {
            const faviconLink = document.getElementById('favicon-link');
            if (faviconLink) {
                faviconLink.href = tema.favicon;
            }
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
 * Carga los retiros de caja desde la API y los renderiza en una tabla.
 */
function cargarRetirosAdmin(orden = 'fecha_desc') {
    if (seccionActual !== 'retiros') {
        adminTablaHeaderHTML = '';
        seccionActual = 'retiros';
    }

    const contenedor = document.getElementById('adminContenido');
    const tableExisting = contenedor.querySelector('.admin-tabla');
    const isFirstTime = !tableExisting || adminTablaHeaderHTML === '';

    let url = 'api/retiros.php';
    if (orden !== 'fecha_desc') {
        url += '?orden=' + orden;
    }

    fetch(url)
        .then(res => {
            if (!res.ok) {
                return res.json().then(err => { throw new Error(err.error || 'Error al cargar retiros'); });
            }
            return res.json();
        })
        .then(data => renderRetirosAdmin(data, isFirstTime, orden))
        .catch(err => {
            console.error('Error cargando retiros:', err);
            contenedor.innerHTML = '<p class="sin-productos">Error: ' + (err.message || 'Error desconocido') + '</p>';
        });
}

/**
 * Genera el HTML del header de la tabla de retiros.
 */
function getRetirosTablaHeader(orden = 'fecha_desc') {
    return `
        <div class="admin-tabla-header retiros-header">
            <div class="ventas-filtros">
                <div class="filtro-group">
                    <label for="retirosOrdenar">Ordenar por:</label>
                    <select id="retirosOrdenar" class="filtro-select" onchange="cargarRetirosAdmin(this.value)">
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
                        <th>Fecha</th>
                        <th>Usuario</th>
                        <th>Importe</th>
                        <th>Motivo</th>
                        <th>Sesión Caja</th>
                    </tr>
                </thead>
                <tbody>`;
}

/**
 * Renderiza los retiros en la tabla.
 */
function renderRetirosAdmin(retiros, isFirstTime = true, orden = 'fecha_desc') {
    const contenedor = document.getElementById('adminContenido');

    if (!retiros || retiros.length === 0) {
        if (isFirstTime || adminTablaHeaderHTML === '') {
            adminTablaHeaderHTML = getRetirosTablaHeader(orden);
            contenedor.innerHTML = adminTablaHeaderHTML +
                '<tr><td colspan="6" class="sin-productos">No hay retiros de caja registrados.</td></tr></tbody></table></div>';
        } else {
            const tbody = contenedor.querySelector('tbody');
            if (tbody) tbody.innerHTML = '<tr><td colspan="6" class="sin-productos">No hay retiros de caja registrados.</td></tr>';
        }
        return;
    }

    if (isFirstTime || adminTablaHeaderHTML === '') {
        adminTablaHeaderHTML = getRetirosTablaHeader(orden);
        let html = adminTablaHeaderHTML;

        retiros.forEach((retiro, index) => {
            const fecha = new Date(retiro.fecha).toLocaleString('es-ES', {
                day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit'
            });
            const usuario = retiro.usuario_nombre ?
                retiro.usuario_nombre + ' ' + (retiro.usuario_apellidos || '') : 'Usuario #' + retiro.idUsuario;
            const motivo = retiro.motivo || 'Sin motivo';
            const cajaSesion = retiro.caja_fecha_apertura ?
                new Date(retiro.caja_fecha_apertura).toLocaleDateString('es-ES') : '#' + retiro.idCajaSesion;

            html += `
                <tr>
                    <td>${index + 1}</td>
                    <td>${fecha}</td>
                    <td>${usuario}</td>
                    <td style="color: #dc2626; font-weight: bold;">-${parseFloat(retiro.importe).toFixed(2)} €</td>
                    <td>${motivo}</td>
                    <td>${cajaSesion}</td>
                </tr>`;
        });

        html += '</tbody></table></div>';
        contenedor.innerHTML = html;
    } else {
        const tbody = contenedor.querySelector('tbody');
        if (tbody) {
            let html = '';
            retiros.forEach((retiro, index) => {
                const fecha = new Date(retiro.fecha).toLocaleString('es-ES', {
                    day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit'
                });
                const usuario = retiro.usuario_nombre ?
                    retiro.usuario_nombre + ' ' + (retiro.usuario_apellidos || '') : 'Usuario #' + retiro.idUsuario;
                const motivo = retiro.motivo || 'Sin motivo';
                const cajaSesion = retiro.caja_fecha_apertura ?
                    new Date(retiro.caja_fecha_apertura).toLocaleDateString('es-ES') : '#' + retiro.idCajaSesion;

                html += `
                    <tr>
                        <td>${index + 1}</td>
                        <td>${fecha}</td>
                        <td>${usuario}</td>
                        <td style="color: #dc2626; font-weight: bold;">-${parseFloat(retiro.importe).toFixed(2)} €</td>
                        <td>${motivo}</td>
                        <td>${cajaSesion}</td>
                    </tr>`;
            });
            tbody.innerHTML = html;
        }
    }
}

/**
 * Carga las sesiones de caja desde la API y las renderiza en una tabla.
 */
function cargarCajaSesionesAdmin(orden = 'fecha_desc') {
    if (seccionActual !== 'caja-sesiones') {
        adminTablaHeaderHTML = '';
        seccionActual = 'caja-sesiones';
    }

    const contenedor = document.getElementById('adminContenido');
    const tableExisting = contenedor.querySelector('.admin-tabla');
    const isFirstTime = !tableExisting || adminTablaHeaderHTML === '';

    let url = 'api/caja-sesiones.php';
    if (orden !== 'fecha_desc') {
        url += '?orden=' + orden;
    }

    fetch(url)
        .then(res => {
            if (!res.ok) {
                return res.json().then(err => { throw new Error(err.error || 'Error al cargar sesiones de caja'); });
            }
            return res.json();
        })
        .then(data => renderCajaSesionesAdmin(data, isFirstTime, orden))
        .catch(err => {
            console.error('Error cargando sesiones de caja:', err);
            contenedor.innerHTML = '<p class="sin-productos">Error: ' + (err.message || 'Error desconocido') + '</p>';
        });
}

/**
 * Genera el HTML del header de la tabla de sesiones de caja.
 */
function getCajaSesionesTablaHeader(orden = 'fecha_desc') {
    return `
        <div class="admin-tabla-header caja-sesiones-header">
            <div class="ventas-filtros">
                <div class="filtro-group">
                    <label for="cajaSesionesOrdenar">Ordenar por:</label>
                    <select id="cajaSesionesOrdenar" class="filtro-select" onchange="cargarCajaSesionesAdmin(this.value)">
                        <option value="fecha_desc" ${orden === 'fecha_desc' ? 'selected' : ''}>Más recientes</option>
                        <option value="fecha_asc" ${orden === 'fecha_asc' ? 'selected' : ''}>Más antiguos</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="admin-tabla-wrapper">
            <table class="admin-tabla">
                <thead>
                    <tr>
                        <th style="width: 40px; text-align: center;">#</th>
                        <th style="width: 120px;">Usuario</th>
                        <th style="text-align: center;">Apertura</th>
                        <th style="text-align: center;">Cierre</th>
                        <th style="text-align: center;">Importe Inicial</th>
                        <th style="text-align: center;">Importe Final</th>
                        <th style="text-align: center;">Ventas</th>
                        <th style="text-align: center;">Productos</th>
                        <th style="text-align: center;">Retiros</th>
                        <th style="text-align: center;">Devoluciones</th>
                    </tr>
                </thead>
                <tbody>`;
}

/**
 * Renderiza las sesiones de caja en la tabla.
 */
function renderCajaSesionesAdmin(sesiones, isFirstTime = true, orden = 'fecha_desc') {
    const contenedor = document.getElementById('adminContenido');

    if (!sesiones || sesiones.length === 0) {
        if (isFirstTime || adminTablaHeaderHTML === '') {
            adminTablaHeaderHTML = getCajaSesionesTablaHeader(orden);
            contenedor.innerHTML = adminTablaHeaderHTML +
                '<tr><td colspan="10" class="sin-productos">No hay sesiones de caja registradas.</td></tr></tbody></table></div>';
        } else {
            const tbody = contenedor.querySelector('tbody');
            if (tbody) tbody.innerHTML = '<tr><td colspan="10" class="sin-productos">No hay sesiones de caja registradas.</td></tr>';
        }
        return;
    }

    if (isFirstTime || adminTablaHeaderHTML === '') {
        adminTablaHeaderHTML = getCajaSesionesTablaHeader(orden);
        let html = adminTablaHeaderHTML;

        sesiones.forEach((sesion, index) => {
            const fechaApertura = sesion.fechaApertura ? new Date(sesion.fechaApertura).toLocaleString('es-ES', {
                day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit'
            }) : '-';
            const fechaCierre = sesion.fechaCierre ? new Date(sesion.fechaCierre).toLocaleString('es-ES', {
                day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit'
            }) : '-';
            const usuario = sesion.usuario_nombre || 'Usuario #' + sesion.idUsuario;
            const retiros = parseFloat(sesion.total_retiros || 0);
            const devoluciones = parseFloat(sesion.total_devoluciones || 0);
            const totalProductos = parseInt(sesion.total_productos || 0);
            const totalVentas = parseInt(sesion.total_ventas || 0);

            html += `
                <tr>
                    <td style="text-align: center; width: 40px;">${index + 1}</td>
                    <td style="width: 120px;">${usuario}</td>
                    <td style="text-align: center;">${fechaApertura}</td>
                    <td style="text-align: center;">${fechaCierre}</td>
                    <td style="text-align: center;">${parseFloat(sesion.importeInicial).toFixed(2)} €</td>
                    <td style="text-align: center;">${parseFloat(sesion.importeActual).toFixed(2)} €</td>
                    <td style="text-align: center; font-weight: bold;">${totalVentas}</td>
                    <td style="text-align: center; font-weight: bold;">${totalProductos}</td>
                    <td style="text-align: center; color: #ea580c; font-weight: bold;">-${retiros.toFixed(2)} €</td>
                    <td style="text-align: center; color: #dc2626; font-weight: bold;">-${devoluciones.toFixed(2)} €</td>
                </tr>`;
        });

        html += '</tbody></table></div>';
        contenedor.innerHTML = html;
    } else {
        const tbody = contenedor.querySelector('tbody');
        if (tbody) {
            let html = '';
            sesiones.forEach((sesion, index) => {
                const fechaApertura = sesion.fechaApertura ? new Date(sesion.fechaApertura).toLocaleString('es-ES', {
                    day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit'
                }) : '-';
                const fechaCierre = sesion.fechaCierre ? new Date(sesion.fechaCierre).toLocaleString('es-ES', {
                    day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit'
                }) : '-';
                const usuario = sesion.usuario_nombre || 'Usuario #' + sesion.idUsuario;
                const retiros = parseFloat(sesion.total_retiros || 0);
                const devoluciones = parseFloat(sesion.total_devoluciones || 0);
                const totalProductos = parseInt(sesion.total_productos || 0);
                const totalVentas = parseInt(sesion.total_ventas || 0);

                html += `
                    <tr>
                        <td style="text-align: center; width: 40px;">${index + 1}</td>
                        <td style="width: 120px;">${usuario}</td>
                        <td style="text-align: center;">${fechaApertura}</td>
                        <td style="text-align: center;">${fechaCierre}</td>
                        <td style="text-align: center;">${parseFloat(sesion.importeInicial).toFixed(2)} €</td>
                        <td style="text-align: center;">${parseFloat(sesion.importeActual).toFixed(2)} €</td>
                        <td style="text-align: center; font-weight: bold;">${totalVentas}</td>
                        <td style="text-align: center; font-weight: bold;">${totalProductos}</td>
                        <td style="text-align: center; color: #ea580c; font-weight: bold;">-${retiros.toFixed(2)} €</td>
                        <td style="text-align: center; color: #dc2626; font-weight: bold;">-${devoluciones.toFixed(2)} €</td>
                    </tr>`;
            });
            tbody.innerHTML = html;
        }
    }
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

/**
 * Alterna el estado de mostrarConIva y recarga la tabla de productos.
 */
function toggleMostrarIva() {
    mostrarConIva = !mostrarConIva;

    // Forzar la regeneración del header para actualizar el texto del botón
    adminTablaHeaderHTML = '';

    // Obtener los filtros actuales para mantener la vista
    const texto = document.getElementById('inputBuscarProducto')?.value || '';
    const idCat = document.getElementById('selectCategoria')?.value || 'todas';
    const orden = document.getElementById('selectOrden')?.value || '';

    cargarProductosAdmin(idCat, texto, orden);
}

// ======================== GESTIÓN DE PROVEEDORES ========================

/** Temporizador para debounce de búsqueda de proveedores */
let debounceTimerProveedores = null;

/** Temporizador para debounce de búsqueda de clientes */
let debounceTimerClientes = null;

/**
 * Genera el HTML del header de la tabla de clientes con buscador.
 * @param {string} textoBusqueda
 * @returns {string}
 */
function getClientesTablaHeader(textoBusqueda = '') {
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
            </div>
        </div>
        <div class="admin-tabla-wrapper">
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
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>`;
}

/**
 * Genera el HTML del header de la tabla de proveedores con buscador.
 * @param {string} textoBusqueda
 * @returns {string}
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
 * @param {string} textoBusqueda
 * @returns {Promise}
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
            if (!res.ok) {
                return res.json().then(err => { throw new Error(err.error || 'Error al cargar proveedores'); });
            }
            return res.json();
        })
        .then(data => renderProveedoresAdmin(data, esPrimeraVez))
        .catch(err => {
            console.error('Error cargando proveedores:', err);
            document.getElementById('adminContenido').innerHTML =
                '<p class="sin-productos">' + err.message + '</p>';
        });
}

/**
 * Renderiza un array de proveedores en formato tabla.
 * @param {Array} proveedores
 * @param {boolean} esPrimeraVez
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

    let html = adminTablaHeaderHTML;

    proveedores.forEach(prov => {
        const estadoHtml = prov.activo === 1
            ? '<span class="admin-badge badge-activo">Activo</span>'
            : '<span class="admin-badge badge-inactivo">Inactivo</span>';

        html += `
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
 * Busca proveedores por nombre con debounce.
 */
function buscarProveedores() {
    clearTimeout(debounceTimerProveedores);
    debounceTimerProveedores = setTimeout(() => {
        const texto = document.getElementById('inputBuscarProveedor').value;
        const params = new URLSearchParams();
        if (texto) params.append('buscar', texto);

        fetch('api/proveedores.php?' + params.toString())
            .then(res => {
                if (!res.ok) {
                    return res.json().then(err => { throw new Error(err.error || 'Error al buscar'); });
                }
                return res.json();
            })
            .then(data => renderProveedoresAdmin(data, false))
            .catch(err => {
                console.error('Error buscando proveedores:', err);
                document.getElementById('adminContenido').innerHTML =
                    '<p class="sin-productos">Error: ' + err.message + '</p>';
            });
    }, 300);
}

/**
 * Carga los clientes desde la API y los renderiza en la tabla.
 * @param {string} textoBusqueda
 * @returns {Promise}
 */
function cargarClientesAdmin(textoBusqueda = '') {
    const contenedor = document.getElementById('adminContenido');
    const tablaExistente = contenedor.querySelector('.admin-tabla');

    if (seccionActual !== 'clientes') {
        adminTablaHeaderHTML = '';
        seccionActual = 'clientes';
    }

    const esPrimeraVez = !tablaExistente || adminTablaHeaderHTML === '';

    const params = new URLSearchParams();
    if (textoBusqueda) params.append('dni', textoBusqueda);

    return fetch('api/clientes.php?' + params.toString())
        .then(res => {
            if (!res.ok) {
                return res.json().then(err => { throw new Error(err.error || 'Error al cargar clientes'); });
            }
            return res.json();
        })
        .then(data => renderClientesAdmin(data, esPrimeraVez))
        .catch(err => {
            console.error('Error cargando clientes:', err);
            document.getElementById('adminContenido').innerHTML =
                '<p class="sin-productos">' + err.message + '</p>';
        });
}

/**
 * Renderiza un array de clientes en formato tabla.
 * @param {Array} clientes
 * @param {boolean} esPrimeraVez
 */
function renderClientesAdmin(clientes, esPrimeraVez = true) {
    const contenedor = document.getElementById('adminContenido');

    if (!clientes || clientes.length === 0) {
        if (esPrimeraVez || adminTablaHeaderHTML === '') {
            adminTablaHeaderHTML = getClientesTablaHeader();
            contenedor.innerHTML = adminTablaHeaderHTML +
                '<tr><td colspan="9" class="sin-productos">No hay clientes disponibles.</td></tr></tbody></table></div>';
        } else {
            const tbody = contenedor.querySelector('tbody');
            if (tbody) tbody.innerHTML = '<tr><td colspan="9" class="sin-productos">No hay clientes disponibles.</td></tr>';
        }
        return;
    }

    if (esPrimeraVez || adminTablaHeaderHTML === '') {
        adminTablaHeaderHTML = getClientesTablaHeader();
    }

    let html = adminTablaHeaderHTML;

    clientes.forEach(cli => {
        const estadoHtml = cli.activo == 1
            ? '<span class="admin-badge badge-activo">Activo</span>'
            : '<span class="admin-badge badge-inactivo">Inactivo</span>';

        const btnEliminar = `<button class="btn-admin-accion btn-eliminar" onclick="eliminarCliente(${cli.id})" title="Eliminar">
                <i class="fas fa-trash"></i>
               </button>`;

        const btnVer = `<button class="btn-admin-accion btn-ver" onclick="verCliente(${cli.id})" title="Ver">
                <i class="fas fa-eye"></i>
               </button>`;

        const btnEditar = `<button class="btn-admin-accion btn-editar" onclick="editarCliente(${cli.id})" title="Editar">
                <i class="fas fa-pen"></i>
               </button>`;

        html += `
            <tr class="${cli.activo == 0 ? 'fila-inactiva' : ''}"
                data-nombre="${(cli.nombre || '').replace(/"/g, '&quot;')}"
                data-apellidos="${(cli.apellidos || '').replace(/"/g, '&quot;')}"
                data-fecha-alta="${cli.fecha_alta || ''}"
                data-productos="${cli.productos_comprados || 0}"
                data-compras="${cli.compras_realizadas || 0}"
                data-activo="${cli.activo}">
                <td class="col-id">${cli.id}</td>
                <td class="col-nombre">${cli.dni}</td>
                <td>${cli.nombre || '—'}</td>
                <td>${cli.apellidos || '—'}</td>
                <td>${cli.fecha_alta || '—'}</td>
                <td>${cli.productos_comprados || 0}</td>
                <td>${cli.compras_realizadas || 0}</td>
                <td>${estadoHtml}</td>
                <td class="col-acciones">${btnVer} ${btnEditar} ${btnEliminar}</td>
            </tr>`;
    });

    html += '</tbody></table></div>';

    if (esPrimeraVez) {
        contenedor.innerHTML = html;
    } else {
        const tbody = contenedor.querySelector('tbody');
        if (tbody) {
            tbody.innerHTML = html.replace(adminTablaHeaderHTML, '').replace('</tbody></table></div>', '');
        }
    }
}

/**
 * Busca clientes por DNI con debounce.
 */
function buscarClientes() {
    clearTimeout(debounceTimerClientes);
    debounceTimerClientes = setTimeout(() => {
        const texto = document.getElementById('inputBuscarCliente').value;
        const params = new URLSearchParams();
        if (texto) params.append('dni', texto);

        fetch('api/clientes.php?' + params.toString())
            .then(res => {
                if (!res.ok) {
                    return res.json().then(err => { throw new Error(err.error || 'Error al buscar'); });
                }
                return res.json();
            })
            .then(data => renderClientesAdmin(data, false))
            .catch(err => {
                console.error('Error buscando clientes:', err);
                document.getElementById('adminContenido').innerHTML =
                    '<p class="sin-productos">Error: ' + err.message + '</p>';
            });
    }, 300);
}

/**
 * Muestra el modal para crear un nuevo cliente.
 */
function nuevoCliente() {
    // Limpiar campos del modal
    document.getElementById('clienteHabitualDni').value = '';
    document.getElementById('clienteHabitualNombre').value = '';
    document.getElementById('clienteHabitualApellidos').value = '';
    // Función para obtener la fecha local en formato datetime-local
    const now = new Date();
    const localDate = new Date(now.getTime() - (now.getTimezoneOffset() * 60000)).toISOString().slice(0, 16);
    document.getElementById('clienteHabitualFecha').value = localDate;

    // Configurar el onclick del botón guardar para el admin
    const btnGuardar = document.getElementById('btnGuardarClienteHabitual');
    btnGuardar.onclick = guardarClienteHabitualAdmin;

    // Mostrar modal
    document.getElementById('modalClienteHabitual').style.display = 'flex';
    document.getElementById('clienteHabitualDni').focus();
}

/**
 * Guarda el cliente desde el admin y recarga la tabla
 */
async function guardarClienteHabitualAdmin() {
    const dni = document.getElementById('clienteHabitualDni').value.trim();
    const nombre = document.getElementById('clienteHabitualNombre').value.trim();
    const apellidos = document.getElementById('clienteHabitualApellidos').value.trim();
    // La fecha de alta siempre se establece automáticamente (no se permite modificarla)
    const now = new Date();
    const fecha_alta = new Date(now.getTime() - (now.getTimezoneOffset() * 60000)).toISOString().slice(0, 16);

    // Validar campos obligatorios
    if (!dni || !nombre || !apellidos) {
        alert('Por favor, complete todos los campos obligatorios (DNI, Nombre, Apellidos)');
        return;
    }

    const btnGuardar = document.getElementById('btnGuardarClienteHabitual');
    btnGuardar.disabled = true;
    btnGuardar.textContent = 'Guardando...';

    try {
        const formData = new FormData();
        formData.append('dni', dni);
        formData.append('nombre', nombre);
        formData.append('apellidos', apellidos);
        formData.append('fecha_alta', fecha_alta);

        const response = await fetch('api/clientes.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.ok) {
            alert('Cliente guardado correctamente');
            cerrarModal('modalClienteHabitual');
            // Recargar la tabla de clientes
            cargarClientesAdmin();
        } else {
            alert(data.error || 'Error al guardar el cliente');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error al comunicar con el servidor');
    } finally {
        btnGuardar.disabled = false;
        btnGuardar.textContent = 'Guardar';
    }
}

/**
 * Elimina (desactiva) un cliente
 * @param {number} id
 */
async function eliminarCliente(id) {
    if (!confirm('¿Estás seguro de que quieres eliminar este cliente?')) {
        return;
    }

    try {
        const response = await fetch('api/clientes.php?eliminar=' + id, {
            method: 'DELETE'
        });

        const data = await response.json();

        if (data.ok) {
            alert('Cliente eliminado correctamente');
            cargarClientesAdmin();
        } else {
            alert(data.error || 'Error al eliminar el cliente');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error al comunicar con el servidor');
    }
}

/**
 * Muestra los detalles de un cliente en un modal
 * @param {number} id
 */
function verCliente(id) {
    const fila = document.querySelector(`tr [onclick="verCliente(${id})"]`).closest('tr');
    const celdas = fila.querySelectorAll('td');

    const dni = celdas[1].textContent.trim();
    const nombre = celdas[2].textContent.trim();
    const apellidos = celdas[3].textContent.trim();
    const fechaAlta = celdas[4].textContent.trim();
    const productos = celdas[5].textContent.trim();
    const compras = celdas[6].textContent.trim();
    const estado = celdas[7].querySelector('.admin-badge')?.textContent.trim() || 'Activo';

    // Detectar tema actual
    const isDark = document.body.classList.contains('dark-mode');
    const textColor = isDark ? '#e5e7eb' : '#1f2937';
    const labelColor = isDark ? '#9ca3af' : '#6b7280';
    const borderColor = isDark ? '#374151' : '#e5e7eb';

    // Crear modal dinámicamente
    const modalHtml = `
        <div class="modal-overlay" id="modalVerCliente" style="display: flex;">
            <div class="modal-content" style="max-width: 450px; text-align: left;">
                <h3 style="margin-bottom: 20px; color: ${textColor};">Detalles del Cliente</h3>
                
                <div style="display: grid; gap: 12px;">
                    <div style="display: flex; justify-content: space-between; border-bottom: 1px solid ${borderColor}; padding-bottom: 8px;">
                        <span style="color: ${labelColor}; font-weight: 500;">DNI:</span>
                        <span style="color: ${textColor}; font-weight: 600;">${dni}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; border-bottom: 1px solid ${borderColor}; padding-bottom: 8px;">
                        <span style="color: ${labelColor}; font-weight: 500;">Nombre:</span>
                        <span style="color: ${textColor};">${nombre}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; border-bottom: 1px solid ${borderColor}; padding-bottom: 8px;">
                        <span style="color: ${labelColor}; font-weight: 500;">Apellidos:</span>
                        <span style="color: ${textColor};">${apellidos}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; border-bottom: 1px solid ${borderColor}; padding-bottom: 8px;">
                        <span style="color: ${labelColor}; font-weight: 500;">Fecha de Alta:</span>
                        <span style="color: ${textColor};">${fechaAlta}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; border-bottom: 1px solid ${borderColor}; padding-bottom: 8px;">
                        <span style="color: ${labelColor}; font-weight: 500;">Productos Comprados:</span>
                        <span style="color: ${textColor};">${productos}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; border-bottom: 1px solid ${borderColor}; padding-bottom: 8px;">
                        <span style="color: ${labelColor}; font-weight: 500;">Compras Realizadas:</span>
                        <span style="color: ${textColor};">${compras}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding-bottom: 8px;">
                        <span style="color: ${labelColor}; font-weight: 500;">Estado:</span>
                        <span class="admin-badge ${estado === 'Activo' ? 'badge-activo' : 'badge-inactivo'}">${estado}</span>
                    </div>
                </div>

                <div style="display: flex; justify-content: center; gap: 15px; margin-top: 25px;">
                    <button class="btn-admin-accion btn-ver" onclick="cerrarModal('modalVerCliente'); document.getElementById('modalVerCliente').remove(); verComprasCliente('${dni}')" style="min-width: 180px;">
                        <i class="fas fa-shopping-bag"></i> Ver Compras
                    </button>
                    <button class="btn-modal-cancelar" onclick="cerrarModal('modalVerCliente'); document.getElementById('modalVerCliente').remove();" style="min-width: 100px;">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    `;

    // Eliminar modal anterior si existe
    const modalExistente = document.getElementById('modalVerCliente');
    if (modalExistente) {
        modalExistente.remove();
    }

    // Añadir modal al body
    document.body.insertAdjacentHTML('beforeend', modalHtml);
}

/**
 * Muestra el modal con el carrusel de compras del cliente
 * @param {string} dni - DNI del cliente
 */
function verComprasCliente(dni) {
    // Mostrar indicador de carga
    const isDark = document.body.classList.contains('dark-mode');
    const bgColor = isDark ? '#1f2937' : '#ffffff';
    const textColor = isDark ? '#e5e7eb' : '#1f2937';
    const labelColor = isDark ? '#9ca3af' : '#6b7280';
    const borderColor = isDark ? '#374151' : '#e5e7eb';

    const loadingModal = `
        <div class="modal-overlay" id="modalVerCompras" style="display: flex;">
            <div class="modal-content" style="max-width: 700px; max-height: 80vh; text-align: left; overflow: hidden; display: flex; flex-direction: column;">
                <h3 style="margin-bottom: 15px; color: ${textColor};">Compras del Cliente</h3>
                <p style="color: ${labelColor}; margin-bottom: 20px;">Cargando compras...</p>
            </div>
        </div>
    `;

    // Eliminar modal anterior si existe
    const modalExistente = document.getElementById('modalVerCompras');
    if (modalExistente) {
        modalExistente.remove();
    }

    document.body.insertAdjacentHTML('beforeend', loadingModal);

    // Fetch purchases from API
    fetch(`api/clientes.php?compras=1&dni=${encodeURIComponent(dni)}`)
        .then(res => res.json())
        .then(data => {
            // Check if the response is error, null, or not an array
            if (!data || (typeof data !== 'object') || (Array.isArray(data) === false && !data.error)) {
                console.error('Invalid API response:', data);
                throw new Error('Respuesta inválida del servidor');
            }

            if (data.error) {
                throw new Error(data.error);
            }

            const ventas = data;
            const modal = document.getElementById('modalVerCompras');

            if (!ventas || ventas.length === 0) {
                modal.innerHTML = `
                    <div class="modal-content" style="max-width: 500px; text-align: left;">
                        <h3 style="margin-bottom: 15px; color: ${textColor};">Compras del Cliente</h3>
                        <p style="color: ${labelColor}; margin-bottom: 20px;">Este cliente no tiene compras registradas.</p>
                        <div style="display: flex; justify-content: center;">
                            <button class="btn-modal-cancelar" onclick="cerrarModal('modalVerCompras'); document.getElementById('modalVerCompras').remove();" style="min-width: 100px;">
                                Cerrar
                            </button>
                        </div>
                    </div>
                `;
                return;
            }

            // Generate carousel slides for each sale
            let slidesHtml = '';
            let indicatorsHtml = '';

            ventas.forEach((venta, index) => {
                const fecha = new Date(venta.fecha).toLocaleString('es-ES', {
                    day: '2-digit', month: '2-digit', year: 'numeric',
                    hour: '2-digit', minute: '2-digit'
                });

                const metodoPagoIcon = {
                    'efectivo': 'fa-money-bill-wave',
                    'tarjeta': 'fa-credit-card',
                    'bizum': 'fa-mobile-alt'
                }[venta.metodoPago] || 'fa-money-bill-wave';

                // Generate table rows for products
                let lineasHtml = '';
                let totalIva = 0;
                if (venta.lineas && venta.lineas.length > 0) {
                    venta.lineas.forEach(linea => {
                        lineasHtml += `
                            <tr style="border-bottom: 1px solid ${borderColor};">
                                <td style="padding: 8px; color: ${textColor};">${linea.producto_nombre || 'Producto'}</td>
                                <td style="padding: 8px; text-align: center; color: ${textColor};">${linea.cantidad}</td>
                                <td style="padding: 8px; text-align: right; color: ${textColor};">${linea.precioUnitarioConIva.toFixed(2).replace('.', ',')} €</td>
                                <td style="padding: 8px; text-align: right; color: ${textColor};">${linea.subtotalConIva.toFixed(2).replace('.', ',')} €</td>
                            </tr>
                        `;
                        totalIva += linea.subtotalConIva;
                    });
                }

                const activeClass = index === 0 ? 'active' : '';
                const displayStyle = index === 0 ? 'block' : 'none';

                slidesHtml += `
                    <div class="carousel-sale-slide ${activeClass}" data-index="${index}" style="display: ${displayStyle};">
                        <div style="background: ${isDark ? '#374151' : '#f3f4f6'}; padding: 12px; border-radius: 8px; margin-bottom: 15px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                                <div>
                                    <span style="color: ${labelColor}; font-size: 13px;">Ticket #${venta.id}</span>
                                    <div style="color: ${textColor}; font-weight: 600;">${fecha}</div>
                                </div>
                                <div style="text-align: right;">
                                    <span style="color: ${labelColor}; font-size: 13px;">${venta.usuario_nombre || 'Cajero'}</span>
                                    <div style="color: ${textColor};"><i class="fas ${metodoPagoIcon}"></i> ${venta.metodoPago}</div>
                                </div>
                            </div>
                        </div>
                        
                        <div style="max-height: 250px; overflow-y: auto; border: 1px solid ${borderColor}; border-radius: 8px;">
                            <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                                <thead style="background: ${isDark ? '#1f2937' : '#f9fafb'}; position: sticky; top: 0;">
                                    <tr>
                                        <th style="padding: 8px; text-align: left; color: ${labelColor};">Producto</th>
                                        <th style="padding: 8px; text-align: center; color: ${labelColor};">Cant.</th>
                                        <th style="padding: 8px; text-align: right; color: ${labelColor};">Precio</th>
                                        <th style="padding: 8px; text-align: right; color: ${labelColor};">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${lineasHtml}
                                </tbody>
                            </table>
                        </div>
                        
                        <div style="display: flex; justify-content: flex-end; margin-top: 12px;">
                            <div style="background: ${isDark ? '#1f2937' : '#f3f4f6'}; padding: 10px 20px; border-radius: 8px;">
                                <span style="color: ${labelColor};">Total: </span>
                                <span style="color: ${textColor}; font-size: 18px; font-weight: bold;">${parseFloat(venta.total).toFixed(2).replace('.', ',')} €</span>
                            </div>
                        </div>
                    </div>
                `;

                indicatorsHtml += `
                    <button class="carousel-indicator ${activeClass}" onclick="changeSaleSlide(${index})" 
                        style="width: 10px; height: 10px; border-radius: 50%; border: none; background: ${index === 0 ? (isDark ? '#60a5fa' : '#3b82f6') : (isDark ? '#4b5563' : '#d1d5db')}; cursor: pointer; transition: background 0.3s;"></button>
                `;
            });

            // Build carousel modal
            modal.innerHTML = `
                <div class="modal-content" style="width: 900px; min-height: 520px; max-height: 85vh; text-align: left; overflow: hidden; display: flex; flex-direction: column; background: ${bgColor}; box-sizing: border-box; position: relative; padding: 20px 90px;">
                    <!-- Flecha ir a primera -->
                    <button onclick="firstSaleSlide()" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); background: none; border: none; color: ${labelColor}; font-size: 20px; cursor: pointer; padding: 10px; z-index: 10;">
                        <i class="fas fa-angle-double-left"></i>
                    </button>
                    
                    <!-- Flecha izquierda -->
                    <button onclick="prevSaleSlide()" style="position: absolute; left: 60px; top: 50%; transform: translateY(-50%); background: none; border: none; color: ${labelColor}; font-size: 28px; cursor: pointer; padding: 10px; z-index: 10;">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    
                    <!-- Flecha derecha -->
                    <button onclick="nextSaleSlide()" style="position: absolute; right: 60px; top: 50%; transform: translateY(-50%); background: none; border: none; color: ${labelColor}; font-size: 28px; cursor: pointer; padding: 10px; z-index: 10;">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                    
                    <!-- Flecha ir a última -->
                    <button onclick="lastSaleSlide()" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); background: none; border: none; color: ${labelColor}; font-size: 20px; cursor: pointer; padding: 10px; z-index: 10;">
                        <i class="fas fa-angle-double-right"></i>
                    </button>
                    
                    <h3 style="margin-bottom: 15px; color: ${textColor}; text-align: center;">
                        <span id="compraActualTitulo">Compra 1 de ${ventas.length}</span>
                    </h3>
                    
                    <div style="flex: 1; overflow-y: auto; padding: 5px; min-height: 350px; max-height: 400px;">
                        ${slidesHtml}
                    </div>
                    
                    <div style="display: flex; justify-content: center; margin-top: 5px;">
                        <button class="btn-modal-cancelar" onclick="cerrarModal('modalVerCompras'); document.getElementById('modalVerCompras').remove();" style="min-width: 100px;">
                            Cerrar
                        </button>
                    </div>
                </div>
            `;

            // Actualizar título al cambiar de slide
            modal.dataset.totalSlides = ventas.length;

            // Agregar función para actualizar el título
            window.updateCompraTitulo = function (index, total) {
                const titulo = document.getElementById('compraActualTitulo');
                if (titulo) {
                    titulo.textContent = `Compra ${index + 1} de ${total}`;
                }
            };
        })
        .catch(err => {
            console.error('Error cargando compras:', err);
            const modal = document.getElementById('modalVerCompras');
            modal.innerHTML = `
                <div class="modal-content" style="max-width: 500px; text-align: left;">
                    <h3 style="margin-bottom: 15px; color: ${textColor};">Error</h3>
                    <p style="color: ${labelColor}; margin-bottom: 20px;">Error al cargar las compras del cliente.</p>
                    <div style="display: flex; justify-content: center;">
                        <button class="btn-modal-cancelar" onclick="cerrarModal('modalVerCompras'); document.getElementById('modalVerCompras').remove();" style="min-width: 100px;">
                            Cerrar
                        </button>
                    </div>
                </div>
            `;
        });
}

let currentSaleSlide = 0;

function changeSaleSlide(index) {
    const modal = document.getElementById('modalVerCompras');
    if (!modal) return;

    const isDark = document.body.classList.contains('dark-mode');
    const totalSlides = parseInt(modal.dataset.totalSlides) || 0;
    const slides = modal.querySelectorAll('.carousel-sale-slide');

    // Hide all slides
    slides.forEach(slide => slide.style.display = 'none');

    // Show selected slide
    slides[index].style.display = 'block';

    // Actualizar título
    const titulo = document.getElementById('compraActualTitulo');
    if (titulo) {
        titulo.textContent = `Compra ${index + 1} de ${totalSlides}`;
    }

    currentSaleSlide = index;
}

function nextSaleSlide() {
    const modal = document.getElementById('modalVerCompras');
    if (!modal) return;

    const totalSlides = parseInt(modal.dataset.totalSlides) || 0;
    const nextIndex = (currentSaleSlide + 1) % totalSlides;
    changeSaleSlide(nextIndex);
}

function prevSaleSlide() {
    const modal = document.getElementById('modalVerCompras');
    if (!modal) return;

    const totalSlides = parseInt(modal.dataset.totalSlides) || 0;
    const prevIndex = (currentSaleSlide - 1 + totalSlides) % totalSlides;
    changeSaleSlide(prevIndex);
}

function firstSaleSlide() {
    changeSaleSlide(0);
}

function lastSaleSlide() {
    const modal = document.getElementById('modalVerCompras');
    if (!modal) return;
    const totalSlides = parseInt(modal.dataset.totalSlides) || 0;
    changeSaleSlide(totalSlides - 1);
}

/**
 * Abre el modal para editar un cliente existente
 * @param {number} id
 */
function editarCliente(id) {
    const fila = document.querySelector(`tr [onclick="editarCliente(${id})"]`).closest('tr');
    const celdas = fila.querySelectorAll('td');

    const dni = celdas[1].textContent.trim();
    const nombre = celdas[2].textContent.trim();
    const apellidos = celdas[3].textContent.trim();

    // Rellenar el formulario del modal de edición
    document.getElementById('editarClienteId').value = id;
    document.getElementById('editarClienteDni').value = dni;
    document.getElementById('editarClienteNombre').value = nombre === '—' ? '' : nombre;
    document.getElementById('editarClienteApellidos').value = apellidos === '—' ? '' : apellidos;

    // Mostrar el modal
    const modal = document.getElementById('modalEditarCliente');
    modal.style.display = 'flex';
}

/**
 * Guarda los cambios del cliente editado
 */
async function guardarClienteEditado() {
    // Obtener la fila para recuperar la fecha original
    const id = document.getElementById('editarClienteId').value;
    const fila = document.querySelector(`tr [onclick="editarCliente(${id})"]`).closest('tr');
    const celdas = fila.querySelectorAll('td');
    const fecha_alta = celdas[4].textContent.trim();

    const dni = document.getElementById('editarClienteDni').value.trim();
    const nombre = document.getElementById('editarClienteNombre').value.trim();
    const apellidos = document.getElementById('editarClienteApellidos').value.trim();

    // Validar campos obligatorios
    if (!dni || !nombre || !apellidos) {
        alert('Por favor, complete todos los campos obligatorios (DNI, Nombre, Apellidos)');
        return;
    }

    const btnGuardar = document.getElementById('btnGuardarClienteEditado');
    btnGuardar.disabled = true;
    btnGuardar.textContent = 'Guardando...';

    try {
        const response = await fetch(`api/clientes.php?actualizar=true`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${encodeURIComponent(id)}&dni=${encodeURIComponent(dni)}&nombre=${encodeURIComponent(nombre)}&apellidos=${encodeURIComponent(apellidos)}&fecha_alta=${encodeURIComponent(fecha_alta)}`
        });

        const data = await response.json();

        if (data.ok) {
            alert('Cliente actualizado correctamente');
            cerrarModal('modalEditarCliente');
            // Recargar la tabla de clientes
            cargarClientesAdmin();
        } else {
            alert(data.error || 'Error al actualizar el cliente');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error al comunicar con el servidor');
    } finally {
        btnGuardar.disabled = false;
        btnGuardar.textContent = 'Guardar';
    }
}

/**
 * Muestra el modal de detalle (solo lectura) de un proveedor.
 * @param {number} id
 */
function verProveedor(id) {
    const fila = document.querySelector(`tr [onclick="verProveedor(${id})"]`).closest('tr');
    const celdas = fila.querySelectorAll('td');

    const nombre = celdas[1].textContent.trim();
    const contacto = fila.dataset.contacto || '—';
    const email = fila.dataset.email || '—';
    const direccion = fila.dataset.direccion || '—';
    const estado = celdas[5].querySelector('.admin-badge')?.textContent.trim() ?? '—';

    document.getElementById('verProveedorNombre').textContent = nombre;
    document.getElementById('verProveedorContacto').textContent = contacto || '—';
    document.getElementById('verProveedorEmail').textContent = email || '—';
    document.getElementById('verProveedorDireccion').textContent = direccion || '—';
    document.getElementById('verProveedorEstado').innerHTML =
        estado === 'Activo'
            ? '<span class="admin-badge badge-activo">Activo</span>'
            : '<span class="admin-badge badge-inactivo">Inactivo</span>';

    document.getElementById('modalVerProveedor').style.display = 'flex';
}

/**
 * Abre el formulario para crear un nuevo proveedor.
 */
function nuevoProveedor() {
    document.getElementById('editProveedorId').value = '';
    document.getElementById('editProveedorNombre').value = '';
    document.getElementById('editProveedorContacto').value = '';
    document.getElementById('editProveedorEmail').value = '';
    document.getElementById('editProveedorDireccion').value = '';
    document.getElementById('editProveedorEstado').value = '1';

    document.getElementById('editProveedorTitulo').textContent = 'Nuevo Proveedor';

    abrirModal('modalEditarProveedor');
}

/**
 * Abre el formulario de edición de un proveedor con datos precargados.
 * @param {number} id
 */
function editarProveedor(id) {
    const fila = document.querySelector(`tr [onclick="editarProveedor(${id})"]`).closest('tr');
    const celdas = fila.querySelectorAll('td');

    const nombre = celdas[1].textContent.trim();
    const contacto = fila.dataset.contacto || '';
    const email = fila.dataset.email || '';
    const direccion = fila.dataset.direccion || '';
    const activo = fila.dataset.activo;

    document.getElementById('editProveedorId').value = id;
    document.getElementById('editProveedorNombre').value = nombre;
    document.getElementById('editProveedorContacto').value = contacto;
    document.getElementById('editProveedorEmail').value = email;
    document.getElementById('editProveedorDireccion').value = direccion;
    document.getElementById('editProveedorEstado').value = activo;

    document.getElementById('editProveedorTitulo').textContent = 'Editar Proveedor';

    abrirModal('modalEditarProveedor');
}

/**
 * Guarda los cambios del proveedor (crear o actualizar).
 */
function guardarCambiosProveedor() {
    const id = document.getElementById('editProveedorId').value;
    const nombre = document.getElementById('editProveedorNombre').value.trim();
    const contacto = document.getElementById('editProveedorContacto').value.trim();
    const email = document.getElementById('editProveedorEmail').value.trim();
    const direccion = document.getElementById('editProveedorDireccion').value.trim();
    const activo = document.getElementById('editProveedorEstado').value;

    if (!nombre) {
        alert('El nombre del proveedor es obligatorio.');
        return;
    }

    const formData = new FormData();
    formData.append('id', id);
    formData.append('nombre', nombre);
    formData.append('contacto', contacto);
    formData.append('email', email);
    formData.append('direccion', direccion);
    formData.append('activo', activo);

    fetch('api/proveedores.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.ok) {
                cerrarModal('modalEditarProveedor');
                cargarProveedoresAdmin();
            } else {
                alert('Error al guardar: ' + (data.error ?? ''));
            }
        })
        .catch(err => console.error('Error guardando proveedor:', err));
}

/**
 * Muestra un diálogo de confirmación antes de eliminar un proveedor.
 * @param {number} id
 * @param {string} nombre
 */
function confirmarEliminarProveedor(id, nombre) {
    if (confirm(`¿Seguro que quieres eliminar "${nombre}"?`)) {
        eliminarProveedor(id);
    }
}

/**
 * Elimina un proveedor enviando una petición DELETE a la API.
 * @param {number} id
 */
function eliminarProveedor(id) {
    fetch(`api/proveedores.php?eliminar=${id}`, { method: 'DELETE' })
        .then(res => res.json())
        .then(data => {
            if (data.ok) {
                cargarProveedoresAdmin();
            } else {
                alert('Error al eliminar el proveedor.');
            }
        })
        .catch(err => console.error('Error eliminando proveedor:', err));
}

// ======================== PRODUCTOS DEL PROVEEDOR ========================

// Mantenemos el ID del proveedor actual para las operaciones de sus productos
let proveedorActualId = null;

let verProveedorOriginal = verProveedor;

// Sobrescribimos la función verProveedor para cargar también los productos
verProveedor = function (id) {
    proveedorActualId = id;
    verProveedorOriginal(id);
    cargarProductosProveedor(id);
};

/**
 * Carga la lista de productos suministrados por un proveedor
 */
function cargarProductosProveedor(idProveedor) {
    const tbody = document.getElementById('listaProductosProveedor');
    const msgSinProductos = document.getElementById('msgSinProductosProveedor');

    tbody.innerHTML = '<tr><td colspan="5" style="text-align: center;">Cargando productos...</td></tr>';
    msgSinProductos.style.display = 'none';

    fetch(`api/proveedores.php?productos=${idProveedor}`)
        .then(res => res.json())
        .then(productos => {
            tbody.innerHTML = '';
            if (!productos || productos.length === 0) {
                msgSinProductos.style.display = 'block';
                return;
            }

            msgSinProductos.style.display = 'none';
            productos.forEach(prod => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td style="padding: 8px; min-width: 150px;">${prod.nombre}</td>
                    <td style="padding: 8px; text-align: center; width: 150px;">${parseFloat((prod.precio * 0.70) || 0).toFixed(2)} €</td>
                    <td style="padding: 8px; text-align: center; width: 130px;">${parseFloat(prod.recargoEquivalencia).toFixed(2)}%</td>
                    <td style="padding: 8px; text-align: center; width: 100px;">
                        <span style="font-size: 0.75rem; color: #6b7280;">C: ${parseFloat(prod.precio * 0.70 * (1 + parseFloat(prod.recargoEquivalencia || 0) / 100)).toFixed(2)} €</span><br>
                        <span style="font-size: 0.75rem; color: #22c55e;">V: ${parseFloat(prod.precio || 0).toFixed(2)} €</span>
                    </td>
                    <td style="padding: 8px; text-align: center; width: 100px;">
                        <button class="btn-admin-accion btn-editar" onclick="editarRecargoProveedor(${prod.idAsociacion}, ${prod.idProducto}, '${prod.nombre.replace(/'/g, "\\'")}', ${prod.recargoEquivalencia}, ${prod.precioProveedor || 0})" title="Editar Recargo" style="padding: 4px; font-size: 0.8rem;">
                            <i class="fas fa-pen"></i>
                        </button>
                        <button class="btn-admin-accion btn-eliminar" onclick="confirmarEliminarProductoProveedor(${prod.idAsociacion}, '${prod.nombre.replace(/'/g, "\\'")}')" title="Eliminar Asociación" style="padding: 4px; font-size: 0.8rem;">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        })
        .catch(err => {
            console.error('Error cargando productos del proveedor:', err);
            tbody.innerHTML = '<tr><td colspan="5" class="sin-productos" style="color: red;">Error al cargar los productos.</td></tr>';
        });
}

/**
 * Prepara y abre el modal para asociar un nuevo producto al proveedor
 */
function agregarProductoProveedor() {
    document.getElementById('asociarProductoTitulo').textContent = 'Añadir Producto';
    document.getElementById('asociarProductoSubtitulo').textContent = 'Selecciona un producto disponible y fija su recargo';

    document.getElementById('asociarProvIdAsociacion').value = '';
    document.getElementById('asociarProvIdProveedor').value = proveedorActualId;
    document.getElementById('asociarProvPrecio').value = '0.00';
    document.getElementById('asociarProvRecargo').value = '0.00';

    const selectProducto = document.getElementById('asociarProvIdProducto');
    document.getElementById('contenedorSelectProducto').style.display = 'flex';
    document.getElementById('contenedorTextoProducto').style.display = 'none';

    // Cargar productos disponibles (no asociados aún)
    fetch(`api/proveedores.php?productosDisponibles=${proveedorActualId}`)
        .then(res => res.json())
        .then(productos => {
            selectProducto.innerHTML = '';
            if (!productos || productos.length === 0) {
                selectProducto.innerHTML = '<option value="">Cargando...</option>';
                alert('No hay productos disponibles para asociar o hubo un error.');
                return;
            }

            productos.forEach(prod => {
                const option = document.createElement('option');
                option.value = prod.id;
                option.textContent = prod.nombre;
                selectProducto.appendChild(option);
            });

            cerrarModal('modalVerProveedor');
            abrirModal('modalAsociarProducto');
        })
        .catch(err => console.error('Error cargando productos disponibles:', err));
}

/**
 * Prepara y abre el modal para editar el recargo de equivalencia
 */
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

// ======================== GESTIÓN DE CATEGORÍAS ========================

function mostrarPanelCategorias(textoBusqueda = '') {
    const contenedor = document.getElementById('adminContenido');
    const inputBusqueda = document.getElementById('busquedaCategorias');

    // Si ya existe el input, obtener el valor actual para mantener la búsqueda
    if (inputBusqueda && textoBusqueda === '') {
        textoBusqueda = inputBusqueda.value;
    }

    if (seccionActual !== 'categorias') {
        adminTablaHeaderHTML = '';
        seccionActual = 'categorias';
    }

    // Solo generar header si no existe
    if (!adminTablaHeaderHTML) {
        adminTablaHeaderHTML = `
            <div class="admin-tabla-header">
                <div style="display: flex; gap: 10px; width: 100%; align-items: center; flex-wrap: wrap;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <label style="margin: 0; font-weight: 600;">Buscar:</label>
                        <input type="text" id="busquedaCategorias" placeholder="Escribe el nombre de la categoría..." 
                            value="${textoBusqueda}" 
                            style="padding: 8px 15px; border: 1px solid #e5e7eb; border-radius: 10px; width: 250px; height: 40px;"
                            oninput="buscarCategorias()">
                    </div>
                    <button class="btn-admin-accion btn-nuevo" onclick="abrirModalNuevaCategoria()">
                        <i class="fas fa-plus"></i> Nueva Categoría
                    </button>
                </div>
            </div>`;
    }

    fetch('api/categorias.php')
        .then(res => res.json())
        .then(data => {
            if (data.error) {
                contenedor.innerHTML = adminTablaHeaderHTML + '<p style="color: red;">Error: ' + data.error + '</p>';
                return;
            }

            let filteredData = data;
            if (textoBusqueda) {
                const search = textoBusqueda.toLowerCase();
                filteredData = data.filter(cat => cat.nombre.toLowerCase().includes(search));
            }

            if (filteredData.length === 0) {
                contenedor.innerHTML = adminTablaHeaderHTML + '<p class="sin-productos">No hay categorías.</p>';
                return;
            }

            let html = adminTablaHeaderHTML;
            html += `<div class="admin-tabla-wrapper">
                <table class="admin-tabla">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nombre</th>
                            <th>Productos</th>
                            <th>Fecha Creación</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tablaCategoriasBody">`;

            filteredData.forEach(cat => {
                const fecha = cat.fecha_creacion ? new Date(cat.fecha_creacion).toLocaleDateString('es-ES') : '—';
                html += `<tr>
                    <td>${cat.id}</td>
                    <td>${cat.nombre}</td>
                    <td style="text-align: center;"><span class="admin-badge" style="background: #e0e7ff; color: #3730a3;">${cat.num_productos}</span></td>
                    <td>${fecha}</td>
                    <td class="col-acciones">
                        <button class="btn-admin-accion btn-ver" onclick="verCategoria(${cat.id})" title="Ver">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn-admin-accion btn-editar" onclick="abrirModalEditarCategoria(${cat.id}, '${cat.nombre}', '${cat.descripcion || ''}')" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn-admin-accion btn-eliminar" onclick="confirmarEliminarCategoria(${cat.id}, '${cat.nombre}')" title="Eliminar">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>`;
            });

            html += '</tbody></table></div>';
            contenedor.innerHTML = html;
        })
        .catch(err => {
            console.error('Error:', err);
            contenedor.innerHTML = adminTablaHeaderHTML + '<p style="color: red;">Error al cargar las categorías.</p>';
        });
}

function buscarCategorias() {
    clearTimeout(debounceTimerCategorias);
    debounceTimerCategorias = setTimeout(() => {
        const input = document.getElementById('busquedaCategorias');
        if (!input) return;

        const textoBusqueda = input.value;

        // Solo buscar, sin regenerar el header (como en productos)
        fetch('api/categorias.php')
            .then(res => res.json())
            .then(data => {
                if (data.error) return;

                let filteredData = data;
                if (textoBusqueda) {
                    const search = textoBusqueda.toLowerCase();
                    filteredData = data.filter(cat => cat.nombre.toLowerCase().includes(search));
                }

                const tablaBody = document.getElementById('tablaCategoriasBody');
                if (!tablaBody) return;

                if (filteredData.length === 0) {
                    tablaBody.innerHTML = '<tr><td colspan="5" style="text-align: center; padding: 20px; color: #6b7280;">No hay categorías.</td></tr>';
                    return;
                }

                let html = '';
                filteredData.forEach(cat => {
                    const fecha = cat.fecha_creacion ? new Date(cat.fecha_creacion).toLocaleDateString('es-ES') : '—';
                    html += `<tr style="border-bottom: 1px solid #e5e7eb;">
                        <td style="padding: 12px;">${cat.id}</td>
                        <td style="padding: 12px; font-weight: 500;">${cat.nombre}</td>
                        <td style="padding: 12px; text-align: center;"><span class="admin-badge" style="background: #e0e7ff; color: #3730a3;">${cat.num_productos}</span></td>
                        <td style="padding: 12px;">${fecha}</td>
                        <td style="padding: 12px; text-align: center;">
                            <button onclick="verCategoria(${cat.id})" style="background: #3b82f6; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer;">
                                <i class="fas fa-eye"></i> Ver
                            </button>
                        </td>
                    </tr>`;
                });

                tablaBody.innerHTML = html;
            })
            .catch(err => console.error('Error:', err));
    }, 300);
}

function verCategoria(id) {
    fetch('api/categorias.php?id=' + id)
        .then(res => res.json())
        .then(data => {
            if (data.error) {
                alert(data.error);
                return;
            }

            const fecha = data.fecha_creacion ? new Date(data.fecha_creacion).toLocaleString('es-ES') : '—';

            // Llenar los campos del modal
            document.getElementById('verCategoriaId').textContent = data.id;
            document.getElementById('verCategoriaNombre').textContent = data.nombre;
            document.getElementById('verCategoriaProductos').innerHTML = '<span class="admin-badge" style="background: #e0e7ff; color: #3730a3;">' + data.num_productos + '</span>';
            document.getElementById('verCategoriaFecha').textContent = fecha;
            document.getElementById('verCategoriaDescripcion').textContent = data.descripcion || 'Sin descripción';

            // Abrir el modal
            abrirModal('modalVerCategoria');
        })
        .catch(err => {
            console.error('Error:', err);
            alert('Error al cargar los datos de la categoría.');
        });
}

function abrirModalNuevaCategoria() {
    let html = `
        <div class="modal-nueva-cat-container">
            <div class="modal-nueva-cat-header">
                <h3>Nueva Categoría</h3>
            </div>
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

    let modal = document.getElementById('modalNuevaCategoria');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'modalNuevaCategoria';
        modal.className = 'modal-overlay';
        modal.style.display = 'none';
        modal.innerHTML = '<div class="modal-content" style="max-width: 450px; max-height: 80vh; overflow-y: auto;"></div>';
        document.body.appendChild(modal);
    }

    modal.querySelector('.modal-content').innerHTML = html;
    modal.style.display = 'flex';
}

function guardarNuevaCategoria() {
    const nombre = document.getElementById('nuevaCategoriaNombre').value.trim();
    const descripcion = document.getElementById('nuevaCategoriaDescripcion').value.trim();

    if (!nombre) {
        alert('El nombre de la categoría es obligatorio');
        return;
    }

    const formData = new FormData();
    formData.append('nombre', nombre);
    formData.append('descripcion', descripcion);

    fetch('api/categorias.php', {
        method: 'POST',
        body: formData
    })
        .then(res => res.json())
        .then(data => {
            if (data.error) {
                alert(data.error);
                return;
            }
            cerrarModal('modalNuevaCategoria');
            // Recargar categorías
            categoriasAdmin = [];
            cargarCategoriasAdmin().then(() => {
                // Forzar regeneración del header
                adminTablaHeaderHTML = '';
                mostrarPanelCategorias();
            });
        })
        .catch(err => {
            console.error('Error:', err);
            alert('Error al guardar la categoría');
        });
}

function confirmarEliminarCategoria(id, nombre) {
    if (confirm('¿Seguro que quieres eliminar la categoría "' + nombre + '"?')) {
        eliminarCategoria(id);
    }
}

/**
 * Abre el modal para editar una categoría
 */
function abrirModalEditarCategoria(id, nombre, descripcion = '') {
    const modal = document.getElementById('modalEditarCategoria');

    // Detectar tema actual
    const isDark = document.body.classList.contains('dark-mode');
    const bgColor = isDark ? '#1f2937' : 'white';
    const textColor = isDark ? '#e5e7eb' : '#374151';
    const inputBg = isDark ? '#374151' : 'white';
    const inputBorder = isDark ? '#4b5563' : '#e5e7eb';
    const footerBg = isDark ? '#374151' : '#f9fafb';
    const footerBorder = isDark ? '#4b5563' : '#e5e7eb';
    const btnCancelBg = isDark ? '#4b5563' : 'white';
    const btnCancelColor = isDark ? '#e5e7eb' : '#374151';
    const btnCancelBorder = isDark ? '#6b7280' : '#d1d5db';

    if (!modal) {
        // Crear el modal si no existe
        const modalHtml = `
            <div id="modalEditarCategoria" class="modal-overlay" style="display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); align-items: center; justify-content: center;">
                <div class="modal-content" style="background: ${bgColor}; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04); max-width: 500px; width: 90%; overflow: hidden;">
                    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; display: flex; justify-content: space-between; align-items: center;">
                        <h3 style="margin: 0; font-size: 18px;">Editar Categoría</h3>
                        <button onclick="cerrarModal('modalEditarCategoria')" style="background: none; border: none; color: white; font-size: 24px; cursor: pointer; padding: 0; line-height: 1;">&times;</button>
                    </div>
                    <div style="padding: 25px;">
                        <input type="hidden" id="editarCategoriaId">
                        <div style="margin-bottom: 20px;">
                            <label for="editarCategoriaNombre" style="display: block; margin-bottom: 8px; font-weight: 600; color: ${textColor};">Nombre:</label>
                            <input type="text" id="editarCategoriaNombre" style="width: 100%; padding: 12px; border: 1px solid ${inputBorder}; border-radius: 8px; font-size: 14px; box-sizing: border-box; background: ${inputBg}; color: ${textColor};" required>
                        </div>
                        <div style="margin-bottom: 20px;">
                            <label for="editarCategoriaDescripcion" style="display: block; margin-bottom: 8px; font-weight: 600; color: ${textColor};">Descripción:</label>
                            <textarea id="editarCategoriaDescripcion" rows="4" style="width: 100%; padding: 12px; border: 1px solid ${inputBorder}; border-radius: 8px; font-size: 14px; box-sizing: border-box; resize: vertical; font-family: inherit; background: ${inputBg}; color: ${textColor};"></textarea>
                        </div>
                    </div>
                    <div style="padding: 15px 25px; background: ${footerBg}; display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid ${footerBorder};">
                        <button onclick="cerrarModal('modalEditarCategoria')" style="padding: 10px 20px; border: 1px solid ${btnCancelBorder}; background: ${btnCancelBg}; color: ${btnCancelColor}; border-radius: 8px; cursor: pointer; font-weight: 500; transition: all 0.2s;">Cancelar</button>
                        <button onclick="guardarEditarCategoria()" style="padding: 10px 20px; background: #4f46e5; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 500; transition: all 0.2s;">Guardar</button>
                    </div>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', modalHtml);
    }

    // Rellenar los datos
    document.getElementById('editarCategoriaId').value = id;
    document.getElementById('editarCategoriaNombre').value = nombre;
    document.getElementById('editarCategoriaDescripcion').value = descripcion || '';

    // Mostrar el modal
    document.getElementById('modalEditarCategoria').style.display = 'flex';
}

/**
 * Guarda los cambios de la categoría editada
 */
function guardarEditarCategoria() {
    const id = document.getElementById('editarCategoriaId').value;
    const nombre = document.getElementById('editarCategoriaNombre').value.trim();
    const descripcion = document.getElementById('editarCategoriaDescripcion').value.trim();

    if (!nombre) {
        alert('El nombre de la categoría es obligatorio');
        return;
    }

    fetch('api/categorias.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'editar=' + id + '&nombre=' + encodeURIComponent(nombre) + '&descripcion=' + encodeURIComponent(descripcion)
    })
        .then(res => res.json())
        .then(data => {
            if (data.error) {
                alert(data.error);
                return;
            }
            cerrarModal('modalEditarCategoria');
            // Recargar categorías
            categoriasAdmin = [];
            cargarCategoriasAdmin().then(() => {
                adminTablaHeaderHTML = '';
                mostrarPanelCategorias();
            });
        })
        .catch(err => {
            console.error('Error:', err);
            alert('Error al guardar los cambios');
        });
}

function eliminarCategoria(id) {
    fetch('api/categorias.php?eliminar=' + id, { method: 'DELETE' })
        .then(res => res.json())
        .then(data => {
            if (data.error) {
                alert(data.error);
                return;
            }
            // Recargar categorías
            categoriasAdmin = [];
            cargarCategoriasAdmin().then(() => {
                adminTablaHeaderHTML = '';
                mostrarPanelCategorias();
            });
        })
        .catch(err => {
            console.error('Error:', err);
            alert('Error al eliminar la categoría');
        });
}

/**
 * Guarda la asociación del producto o actualiza su recargo
 */
function guardarCambiosAsociarProducto() {
    const idAsociacion = document.getElementById('asociarProvIdAsociacion').value;
    const idProveedor = document.getElementById('asociarProvIdProveedor').value;
    const idProducto = document.getElementById('asociarProvIdProducto').value;
    const precio = document.getElementById('asociarProvPrecio').value;
    const recargo = document.getElementById('asociarProvRecargo').value;

    const formData = new FormData();
    formData.append('precioProveedor', precio);
    formData.append('recargoEquivalencia', recargo);

    if (idAsociacion) {
        // Modo Edición: Actualizar recargo
        formData.append('accion', 'actualizarRecargo');
        formData.append('idAsociacion', idAsociacion);
    } else {
        // Modo Creación: Asociar nuevo producto
        formData.append('accion', 'agregarProducto');
        formData.append('idProveedor', idProveedor);
        formData.append('idProducto', idProducto);
    }

    fetch('api/proveedores.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.ok) {
                cerrarModal('modalAsociarProducto');
                abrirModal('modalVerProveedor');
                cargarProductosProveedor(idProveedor);
            } else {
                alert('Error: ' + (data.error ?? 'Error desconocido'));
            }
        })
        .catch(err => console.error('Error guardando asociación:', err));
}

// ======================== TARIFAS GENERALES ========================

// Array para almacenar IDs de productos excluidos del ajuste de precios
let productosExcluidos = [];
let productosPrevisualizacionIVA = [];
let productosPrevisualizacionPrecios = [];

function mostrarPanelCambiarIVA() {
    productosExcluidos = []; // Resetear excluidos al cambiar de panel
    const contenedor = document.getElementById('adminContenido');
    seccionActual = 'tarifa-iva';
    adminTablaHeaderHTML = '';

    // Generar opciones del select de tipos de IVA
    let opcionesIva = '<option value="">Selecciona un tipo de IVA</option>';
    tiposIva.forEach(tipo => {
        opcionesIva += `<option value="${tipo.id}">${tipo.porcentaje}% (${tipo.nombre})</option>`;
    });

    // Generar filas de la tabla de tipos de IVA
    let filasTablaIva = '';
    tiposIva.forEach(tipo => {
        filasTablaIva += `
            <tr>
                <td style="text-align: center; width: 40px;">${tipo.id}</td>
                <td style="">${tipo.nombre}</td>
                <td style="text-align: center; font-weight: 600; width: 80px;">${tipo.porcentaje}%</td>
                <td style="text-align: center; width: 100px;">
                    <button class="btn-admin-accion" onclick="editarIva(${tipo.id}, '${tipo.nombre}', ${tipo.porcentaje})" title="Editar">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn-admin-accion btn-eliminar" onclick="eliminarIva(${tipo.id})" title="Eliminar">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>`;
    });

    contenedor.innerHTML = `
        <div class="admin-tabla-header">
            <h2 style="margin: 0; font-size: 24px; font-weight: 600;">Cambiar IVA General</h2>
        </div>
        <div style="display: flex; gap: 20px; align-items: flex-start;">
            <div class="tarifa-panel-inputs" style="min-width: 350px;">
                <p class="tarifa-panel-desc">
                    Esta opción cambiará el IVA de todos los productos. El IVA actual de los productos se mostrará a continuación.
                </p>
                <div class="tarifa-input-group">
                    <label>Nuevo tipo de IVA:</label>
                    <select id="nuevoIVA" class="tarifa-input" onchange="actualizarPrevisualizacionIVAAuto()">
                        ${opcionesIva}
                    </select>
                </div>
                <button onclick="aplicarCambioIVA()" class="tarifa-btn-aplicar">
                    <i class="fas fa-save"></i> Aplicar Cambio de IVA
                </button>
                <button onclick="abrirModalProgramarIVA()" class="tarifa-btn-programar" style="margin-top: 10px;">
                    <i class="fas fa-clock"></i> Programar Cambio
                </button>
                <button onclick="abrirModalVerCambiosProgramados()" class="tarifa-btn-programar" style="margin-top: 10px;">
                    <i class="fas fa-list"></i> Ver Cambios Programados
                </button>
            </div>
            <div style="flex: 1; max-width: 550px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                    <h3 style="margin: 0; font-size: 16px; font-weight: 600;">Tipos de IVA</h3>
                    <button onclick="abrirModalNuevoIva()" class="btn-admin-accion btn-nuevo" style="padding: 4px 10px; font-size: 0.8rem;">
                        <i class="fas fa-plus"></i> Añadir
                    </button>
                </div>
                <div style="max-height: 200px; overflow-y: auto; border: 1px solid #e5e7eb; border-radius: 4px;">
                    <table class="admin-tabla" style="width: 100%; font-size: 0.85rem;">
                        <thead style="position: sticky; top: 0; background: #f9fafb; z-index: 1;">
                            <tr>
                                <th style="width: 40px;">ID</th>
                                <th>Nombre</th>
                                <th style="width: 80px; text-align: center;">%</th>
                                <th style="width: 100px; text-align: center;">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${filasTablaIva}
                        </tbody>
                    </table>
                </div>
            </div>
            <div id="previsualizacionCambios" style="flex: 1;"></div>
        </div>`;
}

let debounceTimerIVA = null;
function actualizarPrevisualizacionIVAAuto() {
    const input = document.getElementById('nuevoIVA');
    const contenedor = document.getElementById('previsualizacionCambios');

    if (!input.value || input.value === '') {
        contenedor.innerHTML = '';
        return;
    }

    clearTimeout(debounceTimerIVA);
    debounceTimerIVA = setTimeout(() => {
        previsualizarCambioIVA();
    }, 300);
}

function previsualizarCambioIVA() {
    const nuevoIdIva = document.getElementById('nuevoIVA').value;

    if (!nuevoIdIva) {
        alert('Por favor, selecciona un tipo de IVA');
        return;
    }

    fetch('api/productos.php?previsualizarIVA=' + nuevoIdIva)
        .then(res => res.json())
        .then(data => {
            if (data.error) {
                alert(data.error);
                return;
            }
            productosPrevisualizacionIVA = data.productos;
            const nuevoIVAPorcentaje = data.productos.length > 0 ? data.productos[0].iva_nuevo : 0;
            mostrarTablaPrevisualizacionIVA(data.productos, nuevoIVAPorcentaje);
        })
        .catch(err => {
            console.error('Error:', err);
            alert('Error al previsualizar');
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
                    <thead>
                        <tr>
                            <th style="width: 30px;">#</th>
                            <th>ID</th>
                            <th>Producto</th>
                            <th style="text-align: right;">Precio</th>
                            <th style="text-align: center;">IVA Actual</th>
                            <th style="text-align: center;">IVA Nuevo</th>
                            <th style="text-align: right;">Precio c/IVA</th>
                        </tr>
                    </thead>
                    <tbody>`;

    productos.forEach((p, index) => {
        const excluido = productosExcluidos.includes(p.id);
        const precioBase = parseFloat(p.precio);
        const precioConIVA = excluido ? precioBase * (1 + p.iva_actual / 100) : precioBase * (1 + nuevoIVA / 100);
        const claseFila = excluido ? 'fila-excluida' : (p.iva_actual !== nuevoIVA ? 'fila-destacada' : '');
        const indicador = excluido ? '❌' : (index + 1);

        html += `
            <tr class="${claseFila}" onclick="toggleExcluirProducto(${p.id}, 'iva')">
                <td style="text-align: center;">${indicador}</td>
                <td>${p.id}</td>
                <td>${p.nombre}</td>
                <td style="text-align: right;">${precioBase.toFixed(2)} €</td>
                <td style="text-align: center;">${p.iva_actual}%</td>
                <td style="text-align: center;" class="precio-iva-nuevo">${excluido ? p.iva_actual + '%' : nuevoIVA + '%'}</td>
                <td style="text-align: right;" class="precio-destacado">${precioConIVA.toFixed(2)} €</td>
            </tr>`;
    });

    html += `</tbody></table></div></div>`;
    contenedor.innerHTML = html;
}

/**
 * Abre el modal para programar un cambio de IVA
 */
function abrirModalProgramarIVA() {
    const nuevoIdIva = document.getElementById('nuevoIVA').value;
    const cambioId = document.getElementById('ivaProgramado').dataset.cambioId;

    // Si no hay cambioId, es un nuevo cambio - solo resetear si no hay productos seleccionados
    if (!cambioId && productosExcluidos.length === 0) {
        document.getElementById('ivaProgramado').value = '';
        delete document.getElementById('ivaProgramado').dataset.cambioId;
    }

    if (!nuevoIdIva && !cambioId) {
        alert('Por favor, selecciona un tipo de IVA');
        return;
    }

    // Guardar el IVA seleccionado en un campo oculto
    if (nuevoIdIva) {
        document.getElementById('ivaProgramado').value = nuevoIdIva;
    }

    // Obtener el nombre del tipo de IVA seleccionado
    const selectIva = document.getElementById('nuevoIVA');
    const nombreIva = selectIva.options[selectIva.selectedIndex].textContent;
    document.getElementById('ivaProgramadoNombre').textContent = nombreIva;

    // Solo establecer fecha mínima si es un nuevo cambio (no edición)
    if (!cambioId) {
        // Establecer fecha y hora mínima (ahora)
        const ahora = new Date();
        ahora.setMinutes(ahora.getMinutes() - ahora.getTimezoneOffset());
        document.getElementById('fechaProgramada').min = ahora.toISOString().slice(0, 16);
    }

    // Abrir el modal
    document.getElementById('modalProgramarIVA').style.display = 'flex';
}

/**
 * Abre el modal para ver los cambios de IVA programados
 */
function abrirModalVerCambiosProgramados() {
    // Cargar los cambios programados desde la API
    fetch('api/productos.php?accion=obtener_cambios_iva_programados')
        .then(res => res.json())
        .then(data => {
            const contenedor = document.getElementById('listaCambiosProgramadosIVA');

            if (!data.cambios || data.cambios.length === 0) {
                contenedor.innerHTML = '<p style="padding: 20px; text-align: center; color: var(--text-secondary);">No hay cambios de IVA programados</p>';
            } else {
                let html = `
                    <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
                        <thead style="background: var(--bg-secondary); position: sticky; top: 0;">
                            <tr>
                                <th style="padding: 10px; text-align: left; border-bottom: 2px solid var(--border-main);">Fecha Programada</th>
                                <th style="padding: 10px; text-align: left; border-bottom: 2px solid var(--border-main);">Nuevo IVA</th>
                                <th style="padding: 10px; text-align: center; border-bottom: 2px solid var(--border-main);">Estado</th>
                                <th style="padding: 10px; text-align: center; border-bottom: 2px solid var(--border-main);">Afectados</th>
                                <th style="padding: 10px; text-align: center; border-bottom: 2px solid var(--border-main);">Excluidos</th>
                                <th style="padding: 10px; text-align: center; border-bottom: 2px solid var(--border-main);">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>`;

                data.cambios.forEach(cambio => {
                    const fecha = new Date(cambio.fecha_programada);
                    const fechaFormateada = fecha.toLocaleString('es-ES', {
                        day: '2-digit', month: '2-digit', year: 'numeric',
                        hour: '2-digit', minute: '2-digit'
                    });

                    const estadoClass = cambio.estado === 'aplicado' ? 'style="color: green;"' :
                        cambio.estado === 'pendiente' ? 'style="color: orange;"' : 'style="color: red;"';

                    const excluidos = cambio.productos_excluidos ?
                        cambio.productos_excluidos.split(',').length + ' productos' : 'Ninguno';

                    const afectados = cambio.productos_afectados !== undefined ? cambio.productos_afectados + ' productos' : '-';

                    const acciones = cambio.estado === 'pendiente' ? `
                        <button class="btn-admin-accion" onclick="verDetallesCambioIVA(${cambio.id})" title="Ver Detalles">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn-admin-accion" onclick="editarCambioProgramadoIVA(${cambio.id})" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn-admin-accion btn-eliminar" onclick="eliminarCambioProgramadoIVA(${cambio.id})" title="Eliminar">
                            <i class="fas fa-trash"></i>
                        </button>` : `
                        <button class="btn-admin-accion" onclick="verDetallesCambioIVA(${cambio.id})" title="Ver Detalles">
                            <i class="fas fa-eye"></i>
                        </button>`;

                    html += `
                        <tr style="border-bottom: 1px solid var(--border-main);">
                            <td style="padding: 10px;">${fechaFormateada}</td>
                            <td style="padding: 10px;">${cambio.iva_porcentaje}% (${cambio.iva_nombre})</td>
                            <td style="padding: 10px; text-align: center;" ${estadoClass}>${cambio.estado.toUpperCase()}</td>
                            <td style="padding: 10px; text-align: center;">${afectados}</td>
                            <td style="padding: 10px; text-align: center;">${excluidos}</td>
                            <td style="padding: 10px; text-align: center;">${acciones}</td>
                        </tr>`;
                });

                html += '</tbody></table>';
                contenedor.innerHTML = html;
            }

            // Abrir el modal
            document.getElementById('modalVerCambiosProgramadosIVA').style.display = 'flex';
        })
        .catch(err => {
            console.error('Error cargando cambios programados:', err);
            alert('Error al cargar los cambios programados');
        });
}

/**
 * Elimina un cambio de IVA programado
 */
function eliminarCambioProgramadoIVA(id) {
    if (!confirm('¿Estás seguro de que deseas eliminar este cambio programado?')) {
        return;
    }

    fetch('api/productos.php?accion=eliminar_cambio_iva_programado&id=' + id, {
        method: 'DELETE'
    })
        .then(res => res.json())
        .then(data => {
            if (data.ok) {
                alert('Cambio programado eliminado correctamente');
                abrirModalVerCambiosProgramados(); // Recargar la lista
            } else {
                alert('Error al eliminar: ' + data.error);
            }
        })
        .catch(err => {
            console.error('Error:', err);
            alert('Error al eliminar el cambio programado');
        });
}

/**
 * Ver los detalles de un cambio de IVA programado
 */
function verDetallesCambioIVA(id) {
    fetch('api/productos.php?accion=obtener_cambio_iva_programado&id=' + id)
        .then(res => res.json())
        .then(data => {
            console.log('Cambio IVA data:', data);
            if (data.cambio) {
                const cambio = data.cambio;

                // Mostrar info del cambio
                const fechaProgramada = cambio.fecha_programada ? new Date(cambio.fecha_programada).toLocaleString('es-ES') : 'No definida';
                const fechaCreacion = cambio.created_at ? new Date(cambio.created_at).toLocaleString('es-ES') : 'No definida';
                const infoHTML = `
                    <div style="background: var(--bg-secondary); padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                        <p><strong>Fecha programada:</strong> ${fechaProgramada}</p>
                        <p><strong>Nuevo IVA:</strong> ${cambio.iva_nombre || 'General'} (${cambio.iva_porcentaje}%)</p>
                        <p><strong>Estado:</strong> ${cambio.estado === 'aplicado' ? '<span style="color: green;">Aplicado</span>' : '<span style="color: orange;">Pendiente</span>'}</p>
                        <p><strong>Creado:</strong> ${fechaCreacion}</p>
                    </div>
                `;
                document.getElementById('detallesCambioIVAInfo').innerHTML = infoHTML;

                // Cargar productos afectados
                fetch('api/productos.php?accion=obtener_productos_cambio_iva&id=' + id)
                    .then(res => res.json())
                    .then(dataProductos => {
                        console.log('IVA response:', dataProductos);
                        let tablaHTML = '';
                        if (dataProductos.ok && dataProductos.productos && dataProductos.productos.length > 0) {
                            tablaHTML = `
                                <table class="tabla-admin" style="width: 100%; font-size: 13px;">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Nombre</th>
                                            <th>IVA Anterior</th>
                                            <th>IVA Nuevo</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${dataProductos.productos.map(p => `
                                            <tr>
                                                <td>${p.id}</td>
                                                <td>${p.nombre}</td>
                                                <td>${p.iva_anterior}%</td>
                                                <td>${p.iva_nuevo}%</td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            `;
                        } else {
                            tablaHTML = '<p style="padding: 20px; text-align: center;">' +
                                (dataProductos.error ? 'Error: ' + dataProductos.error : 'No hay productos afectados') +
                                '</p>';
                        }
                        document.getElementById('detallesCambioIVATabla').innerHTML = tablaHTML;
                    });

                abrirModal('modalVerDetallesCambioIVA');
            } else {
                alert('Error al cargar los detalles del cambio');
            }
        })
        .catch(err => {
            console.error('Error:', err);
            alert('Error al cargar los detalles del cambio');
        });
}

/**
 * Edita un cambio de IVA programado (abre el modal de programación con los datos cargados)
 */
function editarCambioProgramadoIVA(id) {
    // Primero cerrar el modal de ver cambios
    cerrarModal('modalVerCambiosProgramadosIVA');

    // Cargar los datos del cambio y abrir el modal de programación
    fetch('api/productos.php?accion=obtener_cambio_iva_programado&id=' + id)
        .then(res => res.json())
        .then(data => {
            if (data.cambio) {
                const cambio = data.cambio;

                // Seleccionar el IVA
                document.getElementById('nuevoIVA').value = cambio.iva_id;

                // Obtener el nombre del IVA seleccionado y guardarlo
                const selectIva = document.getElementById('nuevoIVA');
                const nombreIva = selectIva.options[selectIva.selectedIndex].textContent;
                document.getElementById('ivaProgramadoNombre').textContent = nombreIva;

                // Establecer la fecha
                const fecha = new Date(cambio.fecha_programada);
                fecha.setMinutes(fecha.getMinutes() - fecha.getTimezoneOffset());
                document.getElementById('fechaProgramada').value = fecha.toISOString().slice(0, 16);

                // Cargar productos excluidos si hay
                if (cambio.productos_excluidos) {
                    productosExcluidos = cambio.productos_excluidos.split(',').map(Number);
                } else {
                    productosExcluidos = [];
                }

                // Guardar el ID del cambio para actualizarlo y el IVA
                document.getElementById('ivaProgramado').value = cambio.iva_id;
                document.getElementById('ivaProgramado').dataset.cambioId = id;

                // Abrir el modal de programación
                abrirModalProgramarIVA();
            }
        })
        .catch(err => {
            console.error('Error:', err);
            alert('Error al cargar los datos del cambio');
        });
}

/**
 * Programa un cambio de IVA para una fecha futura
 */
function programarCambioIVA() {
    const nuevoIdIva = document.getElementById('ivaProgramado').value;
    const fechaHora = document.getElementById('fechaProgramada').value;
    const productosExcluir = productosExcluidos.join(',');
    const cambioId = document.getElementById('ivaProgramado').dataset.cambioId;

    if (!fechaHora) {
        alert('Por favor, selecciona una fecha y hora');
        return;
    }

    const fechaObj = new Date(fechaHora);
    const ahora = new Date();

    if (fechaObj <= ahora && !cambioId) {
        alert('La fecha programada debe ser posterior a la hora actual');
        return;
    }

    // Si hay un cambioId, es una edición; otherwise, es nuevo
    if (cambioId) {
        // Actualizar cambio existente
        fetch('api/productos.php?accion=actualizar_cambio_iva_programado&id=' + cambioId, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'iva_id=' + nuevoIdIva + '&fecha_programada=' + encodeURIComponent(fechaHora) + '&productos_excluidos=' + productosExcluir
        })
            .then(res => res.json())
            .then(data => {
                if (data.error) {
                    alert(data.error);
                    return;
                }
                alert('Cambio de IVA actualizado correctamente');
                cerrarModal('modalProgramarIVA');
                productosExcluidos = [];
                delete document.getElementById('ivaProgramado').dataset.cambioId;
            })
            .catch(err => {
                console.error('Error:', err);
                alert('Error al actualizar el cambio');
            });
    } else {
        // Crear nuevo cambio
        fetch('api/productos.php?accion=programar_cambio_iva', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'iva_id=' + nuevoIdIva + '&fecha_programada=' + encodeURIComponent(fechaHora) + '&productos_excluidos=' + productosExcluir
        })
            .then(res => res.json())
            .then(data => {
                if (data.error) {
                    alert(data.error);
                    return;
                }
                alert('Cambio de IVA programado para el ' + data.fecha_formateada);
                cerrarModal('modalProgramarIVA');
                productosExcluidos = [];
            })
            .catch(err => {
                console.error('Error:', err);
                alert('Error al programar el cambio');
            });
    }
}

function aplicarCambioIVA() {
    const nuevoIdIva = document.getElementById('nuevoIVA').value;

    if (!nuevoIdIva) {
        alert('Por favor, selecciona un tipo de IVA');
        return;
    }

    // Obtener el nombre del tipo de IVA seleccionado
    const selectIva = document.getElementById('nuevoIVA');
    const nombreIva = selectIva.options[selectIva.selectedIndex].textContent;

    const mensaje = productosExcluidos.length > 0
        ? `¿Estás seguro de cambiar el IVA a ${nombreIva}? (${productosExcluidos.length} productos excluidos)`
        : `¿Estás seguro de cambiar el IVA a ${nombreIva} para todos los productos?`;

    if (!confirm(mensaje)) {
        return;
    }

    let url = 'api/productos.php?cambiarIVA=' + nuevoIdIva;
    if (productosExcluidos.length > 0) {
        url += '&excluidos=' + productosExcluidos.join(',');
    }

    fetch(url, { method: 'POST' })
        .then(res => res.json())
        .then(data => {
            if (data.error) {
                alert(data.error);
                return;
            }
            alert('IVA actualizado correctamente. Productos afectados: ' + data.actualizados);
            productosExcluidos = [];
            // Recargar tipos de IVA y actualizar la tabla
            cargarTiposIva().then(() => {
                if (seccionActual === 'tarifa-iva') {
                    mostrarPanelCambiarIVA();
                }
            });
            actualizarSelectsIva();
        })
        .catch(err => {
            console.error('Error:', err);
            alert('Error al cambiar el IVA');
        });
}

function mostrarPanelAjustePrecios() {
    const contenedor = document.getElementById('adminContenido');
    seccionActual = 'tarifa-ajuste';
    adminTablaHeaderHTML = '';

    contenedor.innerHTML = `
        <div class="admin-tabla-header">
            <h2 style="margin: 0; font-size: 24px; font-weight: 600;">Ajuste de Precios</h2>
        </div>
        <div style="display: flex; gap: 20px; align-items: flex-start;">
            <div class="tarifa-panel-inputs">
                <p class="tarifa-panel-desc">
                    Esta opción aplicará un porcentaje de subida o baisse a todos los productos. 
                    Usa un número positivo para subir precios o negativo para bajarlos.
                </p>
                <div class="tarifa-input-group">
                    <label>Porcentaje de ajuste (%):</label>
                    <input type="number" id="porcentajeAjuste" step="0.01" placeholder="Ej: 10 o -10" 
                        class="tarifa-input"
                        oninput="actualizarPrevisualizacionPreciosAuto()">
                    <small class="tarifa-hint">Positivo = subir precios | Negativo = bajar precios</small>
                </div>
                <button onclick="aplicarAjustePrecios()" class="tarifa-btn-aplicar tarifa-btn-precios">
                    <i class="fas fa-save"></i> Aplicar Ajuste de Precios
                </button>
                <button onclick="abrirModalProgramarAjustePrecios()" class="tarifa-btn-programar" style="margin-top: 10px;">
                    <i class="fas fa-clock"></i> Programar Ajuste
                </button>
                <button onclick="abrirModalVerAjustesProgramados()" class="tarifa-btn-programar" style="margin-top: 10px;">
                    <i class="fas fa-list"></i> Ver Ajustes Programados
                </button>
            </div>
            <div id="previsualizacionCambios" style="flex: 1;"></div>
        </div>`;
}

/**
 * Muestra el panel de tarifas prefijadas en la vista de admin
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

    // Cargar tarifas y productos desde las APIs
    return Promise.all([
        fetch('api/tarifas.php').then(res => res.json()),
        fetch('api/productos.php').then(res => res.json())
    ])
        .then(([tarifas, productos]) => {
            // Calcular precios para cada producto
            let filasTablaProductos = '';
            productos.forEach(prod => {
                let precioBaseOriginal = parseFloat(prod.precio);
                const iva = parseFloat(prod.iva) || 21;

                // Si mostramos con IVA, el precio base sobre el que editamos será con IVA
                let precioBaseAMostrar = precioBaseOriginal;
                if (tarifasMostrarConIva) {
                    precioBaseAMostrar = precioBaseOriginal * (1 + iva / 100);
                }

                let fila = `
                <tr style="border-bottom: 1px solid ${tableRowBorder};">
                    <td style="padding: 10px; font-weight: 500; color: ${textColor};">${prod.nombre}</td>
                    <td style="padding: 10px; color: ${isDark ? '#f3f4f6' : '#1f2937'}; font-weight: 600;">${precioBaseAMostrar.toFixed(2)} €</td>`;

                tarifas.forEach(tarifa => {
                    const idTarifa = tarifa.id;
                    const dataTarifa = prod.preciosTarifas && prod.preciosTarifas[idTarifa];

                    let precioFinal = 0;
                    let esManual = false;

                    if (dataTarifa) {
                        // Usar el precio de la tabla productos_tarifas
                        precioFinal = parseFloat(dataTarifa.precio);
                        esManual = dataTarifa.es_manual == 1;

                        // Aplicar IVA si es necesario (el precio en DB es base)
                        if (tarifasMostrarConIva) {
                            precioFinal = precioFinal * (1 + iva / 100);
                        }
                    } else {
                        // Cálculo tradicional por si no está en la tabla (no debería ocurrir tras migración)
                        const descuento = parseFloat(tarifa.descuento_porcentaje) || 0;
                        precioFinal = precioBaseAMostrar * (1 - descuento / 100);
                    }

                    const manualStyle = esManual ? 'border: 1px solid #10b981; background: #ecfdf5; color: #065f46;' : (isDark ? 'border: 1px solid #374151; background: #111827; color: #10b981;' : 'border: 1px solid #d1d5db; background: white; color: #10b981;');
                    const disabledAttr = tarifasMostrarConIva ? 'disabled' : '';
                    const disabledStyle = tarifasMostrarConIva ? 'opacity: 0.5; cursor: not-allowed;' : '';

                    fila += `
                    <td style="padding: 10px;">
                        <div style="display: flex; align-items: center; gap: 4px;">
                            <input type="number" step="0.01" 
                                value="${precioFinal.toFixed(2)}" 
                                onchange="actualizarPrecioTarifaIndividual(${prod.id}, ${idTarifa}, this, ${iva})"
                                ${disabledAttr}
                                style="width: 80px; padding: 4px 6px; border-radius: 4px; font-weight: 600; text-align: right; ${manualStyle} ${disabledStyle}">
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
                        <button onclick="abrirModalEditarTarifa(${tarifa.id}, '${tarifa.nombre.replace(/'/g, "\\'")}', '${(tarifa.descripcion || '').replace(/'/g, "\\'")}', ${tarifa.descuento_porcentaje}, ${tarifa.requiere_cliente ? 1 : 0})" style="padding: 6px 12px; background: #6366f1; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 13px; margin-right: 5px;"><i class="fas fa-edit"></i> Editar</button>
                        <button onclick="eliminarTarifa(${tarifa.id}, '${tarifa.nombre.replace(/'/g, "\\'")}')" style="padding: 6px 12px; background: #ef4444; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 13px;"><i class="fas fa-trash"></i> Eliminar</button>
                    </td>
                </tr>`;
            });

            // Generar los encabezados dinámicamente
            let cabecerasPrecios = `
                <th style="padding: 14px 12px; text-align: left; font-weight: 600; font-size: 13px; text-transform: uppercase; color: ${textColor}; border-bottom: 2px solid ${borderColor}; position: -webkit-sticky; position: sticky; top: -1px; z-index: 10; background: ${tableHeaderBg}; outline: 1px solid ${borderColor}; outline-offset: -1px; border:none;">Producto</th>
                <th style="padding: 14px 12px; text-align: left; font-weight: 600; font-size: 13px; text-transform: uppercase; color: ${textColor}; border-bottom: 2px solid ${borderColor}; position: -webkit-sticky; position: sticky; top: -1px; z-index: 10; background: ${tableHeaderBg}; outline: 1px solid ${borderColor}; outline-offset: -1px; border:none;">Precio</th>`;

            let detalleDescuentos = [];
            tarifas.forEach(tarifa => {
                cabecerasPrecios += `<th style="padding: 14px 12px; text-align: left; font-weight: 600; font-size: 13px; text-transform: uppercase; color: ${textColor}; border-bottom: 2px solid ${borderColor}; position: -webkit-sticky; position: sticky; top: -1px; z-index: 10; background: ${tableHeaderBg}; outline: 1px solid ${borderColor}; outline-offset: -1px; border:none;">${tarifa.nombre}</th>`;
                detalleDescuentos.push(`${tarifa.nombre} (${tarifa.descuento_porcentaje}%)`);
            });
            let descripcionDescuentos = "Vista de precios según las tarifas aplicadas. Descuentos: " + (detalleDescuentos.length > 0 ? detalleDescuentos.join(", ") : "Ninguno");

            contenedor.innerHTML = `
            <div class="admin-tabla-header">
                <h2 style="margin: 0; font-size: 24px; font-weight: 600; color: ${textColor};">Tarifas Prefijadas</h2>
                <p style="color: ${subTextColor}; margin-top: 5px;">Vista de precios según las tarifas aplicadas.</p>
            </div>
            <div style="display: flex; gap: 15px; margin-bottom: 20px; align-items: center; flex-wrap: wrap;">
                <input type="text" 
                    id="buscarProductoTarifa"
                    placeholder="Buscar producto..." 
                    value="${tarifaBusquedaProducto}"
                    oninput="tarifaBusquedaProducto = this.value; filtrarTablaTarifas();"
                    style="padding: 10px 15px; border: 1px solid ${borderColor}; border-radius: 8px; font-size: 14px; background: ${isDark ? '#374151' : 'white'}; color: ${textColor}; outline: none; transition: border-color 0.2s; min-width: 600px;"
                    onfocus="this.style.borderColor = '#6366f1';"
                    onblur="this.style.borderColor = '${borderColor}';">
                <button onclick="abrirModalTarifas()" style="padding: 10px 20px; background: #6366f1; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 500; transition: background 0.2s;">
                    <i class="fas fa-tags" style="margin-right: 8px;"></i> Ver/Editar Tarifas
                </button>
                <button onclick="toggleTarifasIva()" style="padding: 10px 20px; background: ${tarifasMostrarConIva ? '#10b981' : (isDark ? '#4b5563' : '#4b5563')}; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 500; transition: background 0.2s;">
                    <i class="fas ${tarifasMostrarConIva ? 'fa-file-invoice-dollar' : 'fa-coins'}" style="margin-right: 8px;"></i>
                    ${tarifasMostrarConIva ? 'Ver Sin IVA' : 'Ver Con IVA'}
                </button>
            </div>
            

            <div style="margin-top: 40px; border-top: 2px solid ${borderColor}; padding-top: 30px;">
                <div class="admin-tabla-header">
                    <h2 style="margin: 0; font-size: 24px; font-weight: 600; color: ${textColor};">Precios por Producto</h2>
                    <p style="color: ${subTextColor}; margin-top: 5px;">${descripcionDescuentos} ${tarifasMostrarConIva ? '(Precios con IVA incluido)' : '(Precios base sin IVA)'}</p>
                </div>
                <div style="overflow-x: auto; max-height: 300px; overflow-y: auto; border: 1px solid ${borderColor}; border-radius: 8px;">
                    <table style="width: 100%; border-collapse: separate; border-spacing: 0; border-radius: 8px; background: ${cardBg};" class="tabla-precios-producto">
                        <thead class="tabla-precios-head">
                            <tr>
                                ${cabecerasPrecios}
                            </tr>
                        </thead>
                        <tbody id="tablaPreciosProductos">${filasTablaProductos}</tbody>
                    </table>
                </div>
            </div>
            <div id="modalesTarifas"></div>
            
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

    // Detectar tema actual
    const isDark = document.body.classList.contains('dark-mode');
    const bgColor = isDark ? '#1f2937' : 'white';
    const textColor = isDark ? '#e5e7eb' : '#374151';
    const inputBg = isDark ? '#374151' : 'white';
    const inputBorder = isDark ? '#4b5563' : '#e5e7eb';
    const footerBg = isDark ? '#374151' : '#f9fafb';
    const footerBorder = isDark ? '#4b5563' : '#e5e7eb';
    const btnCancelBg = isDark ? '#4b5563' : 'white';
    const btnCancelColor = isDark ? '#e5e7eb' : '#374151';
    const btnCancelBorder = isDark ? '#6b7280' : '#d1d5db';

    modalesDiv.innerHTML = `
        <div id="modalNuevaTarifa" class="modal-overlay" style="display: flex; position: fixed; z-index: 10001; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); align-items: center; justify-content: center;">
            <div class="modal-content" style="background: ${bgColor}; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); max-width: 500px; width: 90%; overflow: hidden;">
                <div style="background: linear-gradient(135deg, #059669 0%, #10b981 100%); color: white; padding: 20px; display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="margin: 0; font-size: 18px;">Nueva Tarifa</h3>
                    <button onclick="cerrarModal('modalNuevaTarifa')" style="background: none; border: none; color: white; font-size: 24px; cursor: pointer; padding: 0; line-height: 1;">&times;</button>
                </div>
                <div style="padding: 25px;">
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 6px; font-weight: 600; color: ${textColor};">Nombre:</label>
                        <input type="text" id="nuevaTarifaNombre" style="width: 100%; padding: 10px; border: 1px solid ${inputBorder}; border-radius: 6px; font-size: 14px; box-sizing: border-box; background: ${inputBg}; color: ${textColor};" required>
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 6px; font-weight: 600; color: ${textColor};">Descripción:</label>
                        <textarea id="nuevaTarifaDescripcion" rows="3" style="width: 100%; padding: 10px; border: 1px solid ${inputBorder}; border-radius: 6px; font-size: 14px; box-sizing: border-box; resize: vertical; background: ${inputBg}; color: ${textColor};"></textarea>
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 6px; font-weight: 600; color: ${textColor};">Descuento (%):</label>
                        <input type="number" id="nuevaTarifaDescuento" step="0.01" min="0" max="100" value="0" style="width: 100%; padding: 10px; border: 1px solid ${inputBorder}; border-radius: 6px; font-size: 14px; box-sizing: border-box; background: ${inputBg}; color: ${textColor};">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: flex; align-items: center; cursor: pointer;">
                            <input type="checkbox" id="nuevaTarifaRequiereCliente" style="width: 18px; height: 18px; margin-right: 8px;">
                            <span style="font-weight: 500; color: ${textColor};">Requiere búsqueda de cliente</span>
                        </label>
                    </div>
                </div>
                <div style="padding: 15px 25px; background: ${footerBg}; display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid ${footerBorder};">
                    <button onclick="cerrarModal('modalNuevaTarifa')" style="padding: 10px 20px; border: 1px solid ${btnCancelBorder}; background: ${btnCancelBg}; color: ${btnCancelColor}; border-radius: 6px; cursor: pointer; font-weight: 500;">Cancelar</button>
                    <button onclick="guardarNuevaTarifa()" style="padding: 10px 20px; background: #059669; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 500;">Guardar</button>
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

    fetch('api/tarifas.php', {
        method: 'POST',
        body: formData
    })
        .then(res => res.json())
        .then(data => {
            if (data.error) {
                alert(data.error);
                return;
            }
            cerrarModal('modalNuevaTarifa');
            // Recargar panel y reabrir modal
            cerrarModal('modalTarifas');
            mostrarPanelTarifasPrefijadas(true);
        })
        .catch(err => {
            console.error('Error:', err);
            alert('Error al guardar la tarifa');
        });
}

/**
 * Abre el modal para editar una tarifa
 */
function abrirModalEditarTarifa(id, nombre, descripcion, descuento, requiereCliente) {
    const modalesDiv = document.getElementById('modalesTarifas');

    // Detectar tema actual
    const isDark = document.body.classList.contains('dark-mode');
    const bgColor = isDark ? '#1f2937' : 'white';
    const textColor = isDark ? '#e5e7eb' : '#374151';
    const inputBg = isDark ? '#374151' : 'white';
    const inputBorder = isDark ? '#4b5563' : '#e5e7eb';
    const footerBg = isDark ? '#374151' : '#f9fafb';
    const footerBorder = isDark ? '#4b5563' : '#e5e7eb';
    const btnCancelBg = isDark ? '#4b5563' : 'white';
    const btnCancelColor = isDark ? '#e5e7eb' : '#374151';
    const btnCancelBorder = isDark ? '#6b7280' : '#d1d5db';

    modalesDiv.innerHTML = `
        <div id="modalEditarTarifa" class="modal-overlay" style="display: flex; position: fixed; z-index: 10001; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); align-items: center; justify-content: center;">
            <div class="modal-content" style="background: ${bgColor}; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); max-width: 500px; width: 90%; overflow: hidden;">
                <div style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); color: white; padding: 20px; display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="margin: 0; font-size: 18px;">Editar Tarifa</h3>
                    <button onclick="cerrarModal('modalEditarTarifa')" style="background: none; border: none; color: white; font-size: 24px; cursor: pointer; padding: 0; line-height: 1;">&times;</button>
                </div>
                <div style="padding: 25px;">
                    <input type="hidden" id="editarTarifaId" value="${id}">
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 6px; font-weight: 600; color: ${textColor};">Nombre:</label>
                        <input type="text" id="editarTarifaNombre" value="${nombre}" style="width: 100%; padding: 10px; border: 1px solid ${inputBorder}; border-radius: 6px; font-size: 14px; box-sizing: border-box; background: ${inputBg}; color: ${textColor};" required>
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 6px; font-weight: 600; color: ${textColor};">Descripción:</label>
                        <textarea id="editarTarifaDescripcion" rows="3" style="width: 100%; padding: 10px; border: 1px solid ${inputBorder}; border-radius: 6px; font-size: 14px; box-sizing: border-box; resize: vertical; background: ${inputBg}; color: ${textColor};">${descripcion || ''}</textarea>
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 6px; font-weight: 600; color: ${textColor};">Descuento (%):</label>
                        <input type="number" id="editarTarifaDescuento" step="0.01" min="0" max="100" value="${descuento}" style="width: 100%; padding: 10px; border: 1px solid ${inputBorder}; border-radius: 6px; font-size: 14px; box-sizing: border-box; background: ${inputBg}; color: ${textColor};">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: flex; align-items: center; cursor: pointer;">
                            <input type="checkbox" id="editarTarifaRequiereCliente" ${requiereCliente === 1 ? 'checked' : ''} style="width: 18px; height: 18px; margin-right: 8px;">
                            <span style="font-weight: 500; color: ${textColor};">Requiere búsqueda de cliente</span>
                        </label>
                    </div>
                </div>
                <div style="padding: 15px 25px; background: ${footerBg}; display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid ${footerBorder};">
                    <button onclick="cerrarModal('modalEditarTarifa')" style="padding: 10px 20px; border: 1px solid ${btnCancelBorder}; background: ${btnCancelBg}; color: ${btnCancelColor}; border-radius: 6px; cursor: pointer; font-weight: 500;">Cancelar</button>
                    <button onclick="guardarEditarTarifa()" style="padding: 10px 20px; background: #4f46e5; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 500;">Guardar</button>
                </div>
            </div>
        </div>`;
}

/**
 * Guarda los cambios de una tarifa editada
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

    const formData = new FormData();
    formData.append('editar', id);
    formData.append('nombre', nombre);
    formData.append('descripcion', descripcion);
    formData.append('descuento_porcentaje', descuento_porcentaje);
    formData.append('requiere_cliente', requiere_cliente);

    // Siempre mostrar modal de confirmación para todas las tarifas al guardar
    tarifaDataPendiente = formData;

    // Mostrar modal de confirmación
    const listaDiv = document.getElementById('listaProductosConflictivos');
    listaDiv.innerHTML = '<p style="margin: 10px 0;">¿Desea recalcular los precios de todos los productos según el nuevo descuento?</p>';

    abrirModal('modalConflictosTarifa');
}

/**
 * Envía la petición para guardar la tarifa
 */
function ejecutarGuardarTarifa(formData) {
    fetch('api/tarifas.php', {
        method: 'POST',
        body: formData
    })
        .then(res => res.json())
        .then(data => {
            if (data.error) {
                alert(data.error);
                return;
            }
            cerrarModal('modalEditarTarifa');
            cerrarModal('modalConflictosTarifa');
            // Recargar panel y reabrir modal
            cerrarModal('modalTarifas');
            mostrarPanelTarifasPrefijadas(true);
        })
        .catch(err => {
            console.error('Error:', err);
            alert('Error al guardar los cambios');
        });
}

/**
 * Resuelve el conflicto de precios manuales
 */
function confirmarCambioTarifa(sobreescribir) {
    if (!tarifaDataPendiente) return;

    if (sobreescribir) {
        tarifaDataPendiente.append('sobreescribirManuales', '1');
    } else {
        tarifaDataPendiente.append('sobreescribirManuales', '0');
    }

    ejecutarGuardarTarifa(tarifaDataPendiente);
    tarifaDataPendiente = null;
}

/**
 * Actualiza el precio de un producto para una tarifa específica (edición manual)
 */
function actualizarPrecioTarifaIndividual(idProducto, idTarifa, input, iva) {
    let nuevoPrecio = parseFloat(input.value) || 0;

    // Si mostramos con IVA, el valor del input tiene IVA y hay que quitárselo
    if (tarifasMostrarConIva) {
        nuevoPrecio = nuevoPrecio / (1 + iva / 100);
    }

    const formData = new FormData();
    formData.append('actualizarPrecioIndividual', '1');
    formData.append('idTarifa', idTarifa);
    formData.append('idProducto', idProducto);
    formData.append('precio', nuevoPrecio.toFixed(4));
    formData.append('esManual', '1');

    fetch('api/tarifas.php', {
        method: 'POST',
        body: formData
    })
        .then(res => res.json())
        .then(data => {
            if (data.ok) {
                // Marcar el input visualmente como manual
                input.style.border = '1px solid #10b981';
                input.style.background = '#ecfdf5';
                input.style.color = '#065f46';

                // Añadir el icono si no existe
                const parent = input.parentElement;
                if (!parent.querySelector('.fa-hand-paper')) {
                    const icon = document.createElement('i');
                    icon.className = 'fas fa-hand-paper';
                    icon.title = 'Precio manual';
                    icon.style.color = '#10b981';
                    icon.style.fontSize = '12px';
                    parent.appendChild(icon);
                }
            } else {
                alert('Error al actualizar el precio: ' + (data.error || ''));
                mostrarPanelTarifasPrefijadas(); // Recargar para revertir
            }
        })
        .catch(err => {
            console.error('Error:', err);
            alert('Error al conectar con la API');
            mostrarPanelTarifasPrefijadas();
        });
}

/**
 * Elimina una tarifa (la marca como inactiva)
 */
function eliminarTarifa(id, nombre) {
    if (!confirm('¿Estás seguro de que quieres eliminar la tarifa "' + nombre + '"?')) {
        return;
    }

    console.log('Eliminando tarifa con ID:', id);

    fetch('api/tarifas.php?eliminar=' + id, {
        method: 'DELETE'
    })
        .then(res => {
            console.log('Response status:', res.status);
            if (!res.ok) {
                throw new Error('HTTP error! status: ' + res.status);
            }
            return res.json();
        })
        .then(data => {
            console.log('Response data:', data);
            if (data.error) {
                alert(data.error);
                return;
            }
            alert('Tarifa eliminada correctamente');
            // Recargar panel y reabrir modal
            cerrarModal('modalTarifas');
            mostrarPanelTarifasPrefijadas(true);
        })
        .catch(err => {
            console.error('Error:', err);
            alert('Error al eliminar la tarifa: ' + err.message);
        });
}

function toggleTarifasIva() {
    tarifasMostrarConIva = !tarifasMostrarConIva;
    mostrarPanelTarifasPrefijadas();
}

let debounceTimerPrecios = null;
function actualizarPrevisualizacionPreciosAuto() {
    const input = document.getElementById('porcentajeAjuste');
    const contenedor = document.getElementById('previsualizacionCambios');

    if (!input.value || input.value === '') {
        contenedor.innerHTML = '';
        return;
    }

    clearTimeout(debounceTimerPrecios);
    debounceTimerPrecios = setTimeout(() => {
        previsualizarAjustePrecios();
    }, 500);
}

function previsualizarAjustePrecios() {
    const porcentaje = parseFloat(document.getElementById('porcentajeAjuste').value);

    if (isNaN(porcentaje)) {
        alert('Por favor, introduce un porcentaje válido');
        return;
    }

    fetch('api/productos.php?previsualizarAjuste=' + porcentaje)
        .then(res => res.json())
        .then(data => {
            if (data.error) {
                alert(data.error);
                return;
            }
            productosPrevisualizacionPrecios = data.productos;
            mostrarTablaPrevisualizacionPrecios(data.productos, porcentaje);
        })
        .catch(err => {
            console.error('Error:', err);
            alert('Error al previsualizar');
        });
}

/**
 * Muestra el panel de historial de precios en la vista de admin
 */
function mostrarPanelHistorialPrecios() {
    const contenedor = document.getElementById('adminContenido');
    seccionActual = 'historial-precios';
    adminTablaHeaderHTML = '';

    // Detectar tema actual
    const isDark = document.body.classList.contains('dark-mode');
    const bgColor = isDark ? '#1f2937' : '#ffffff';
    const textColor = isDark ? '#e5e7eb' : '#1f2937';
    const subTextColor = isDark ? '#9ca3af' : '#6b7280';
    const borderColor = isDark ? '#374151' : '#e5e7eb';
    const inputBg = isDark ? '#374151' : '#ffffff';
    const tableHeaderBg = isDark ? '#111827' : '#f3f4f6';

    contenedor.innerHTML = `
        <div style="padding: 20px; background: ${bgColor}; border-radius: 8px;">
            <h3 style="margin-bottom: 20px; color: ${textColor};">Historial de Precios</h3>
            
            <div style="display: flex; gap: 20px; margin-bottom: 20px; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 250px;">
                    <label style="display: block; margin-bottom: 8px; color: ${textColor}; font-weight: 500;">Seleccionar Producto:</label>
                    <select id="selectHistorialProducto" 
                        style="width: 100%; padding: 10px; border: 1px solid ${borderColor}; border-radius: 8px; background: ${inputBg}; color: ${textColor}; font-size: 14px; cursor: pointer;"
                        onchange="cargarHistorialPrecios()">
                        <option value="" style="background: ${bgColor}; color: ${textColor};">-- Seleccionar un producto --</option>
                    </select>
                </div>
                <div style="flex: 1; min-width: 250px;">
                    <label style="display: block; margin-bottom: 8px; color: ${textColor}; font-weight: 500;">Seleccionar Tarifa:</label>
                    <select id="selectHistorialTarifa" 
                        style="width: 100%; padding: 10px; border: 1px solid ${borderColor}; border-radius: 8px; background: ${inputBg}; color: ${textColor}; font-size: 14px; cursor: pointer;"
                        onchange="cargarHistorialPrecios()">
                        <option value="" style="background: ${bgColor}; color: ${textColor};">-- Todas las tarifas --</option>
                        <option value="base" style="background: ${bgColor}; color: ${textColor};">Precio Base</option>
                    </select>
                </div>
            </div>

            <div id="tablaHistorialPreciosContainer" style="display: none;">
                <table style="width: 100%; border-collapse: collapse; margin-top: 20px; border: 1px solid ${borderColor}; background: ${bgColor};">
                    <thead>
                        <tr style="background: ${tableHeaderBg};">
                            <th style="padding: 12px; text-align: left; border-bottom: 2px solid ${borderColor}; color: ${textColor};">Precio</th>
                            <th style="padding: 12px; text-align: left; border-bottom: 2px solid ${borderColor}; color: ${textColor};">Válido Desde</th>
                            <th style="padding: 12px; text-align: left; border-bottom: 2px solid ${borderColor}; color: ${textColor};">Válido Hasta</th>
                            <th style="padding: 12px; text-align: left; border-bottom: 2px solid ${borderColor}; color: ${textColor};">Tarifa</th>
                            <th style="padding: 12px; text-align: left; border-bottom: 2px solid ${borderColor}; color: ${textColor};">Usuario</th>
                        </tr>
                    </thead>
                    <tbody id="tablaHistorialPreciosBody">
                    </tbody>
                </table>
            </div>

            <div id="historialPreciosMensaje" style="margin-top: 20px; color: ${subTextColor}; font-style: italic;">
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
            const select = document.getElementById('selectHistorialProducto');
            productos.forEach(prod => {
                const option = document.createElement('option');
                option.value = prod.id;
                option.textContent = prod.nombre;
                select.appendChild(option);
            });
        })
        .catch(err => {
            console.error('Error cargando productos:', err);
        });

    // Cargar la lista de tarifas
    fetch('api/tarifas.php')
        .then(res => res.json())
        .then(tarifas => {
            const select = document.getElementById('selectHistorialTarifa');
            tarifas.forEach(tarifa => {
                const option = document.createElement('option');
                option.value = tarifa.id;
                option.textContent = tarifa.nombre;
                option.style.background = bgColor;
                option.style.color = textColor;
                select.appendChild(option);
            });
        })
        .catch(err => {
            console.error('Error cargando tarifas:', err);
        });
}

/**
 * Carga el historial de precios del producto seleccionado
 */
function cargarHistorialPrecios() {
    const select = document.getElementById('selectHistorialProducto');
    const idProducto = select.value;
    const selectTarifa = document.getElementById('selectHistorialTarifa');
    const idTarifa = selectTarifa ? selectTarifa.value : '';
    const container = document.getElementById('tablaHistorialPreciosContainer');
    const mensaje = document.getElementById('historialPreciosMensaje');
    const tbody = document.getElementById('tablaHistorialPreciosBody');

    // Detectar tema actual
    const isDark = document.body.classList.contains('dark-mode');
    const textColor = isDark ? '#e5e7eb' : '#1f2937';
    const subTextColor = isDark ? '#9ca3af' : '#6b7280';
    const borderColor = isDark ? '#374151' : '#e5e7eb';
    const rowBg = isDark ? '#1f2937' : '#ffffff';
    const rowAltBg = isDark ? '#111827' : '#f3f4f6';

    if (!idProducto) {
        container.style.display = 'none';
        mensaje.style.display = 'block';
        mensaje.textContent = 'Seleccione un producto para ver su historial de precios';
        return;
    }

    // Mostrar mensaje de carga
    mensaje.textContent = 'Cargando historial...';
    mensaje.style.display = 'block';
    container.style.display = 'none';

    fetch('api/productos.php?historialPrecios=' + idProducto)
        .then(res => res.json())
        .then(historial => {
            mensaje.style.display = 'none';

            if (!historial || historial.length === 0) {
                mensaje.textContent = 'No hay historial de precios para este producto';
                mensaje.style.display = 'block';
                container.style.display = 'none';
                return;
            }

            // Filtrar por tarifa si se ha seleccionado una
            let historialFiltrado = historial;
            if (idTarifa) {
                if (idTarifa === 'base') {
                    // Mostrar solo precios base (sin tarifa)
                    historialFiltrado = historial.filter(item => !item.id_tarifa || item.id_tarifa === null);
                } else {
                    // Mostrar solo precios de la tarifa seleccionada
                    historialFiltrado = historial.filter(item => item.id_tarifa == idTarifa);
                }
            }

            if (historialFiltrado.length === 0) {
                mensaje.textContent = idTarifa === 'base'
                    ? 'No hay historial de precios base para este producto'
                    : 'No hay historial de precios para esta tarifa';
                mensaje.style.display = 'block';
                container.style.display = 'none';
                return;
            }

            let html = '';
            historialFiltrado.forEach((item, index) => {
                const fechaDesde = new Date(item.valido_desde).toLocaleString('es-ES', {
                    day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit'
                });

                // El precio más reciente es "Actual", los demás muestran la fecha del siguiente cambio
                let fechaHasta = '—';
                if (index === 0) {
                    fechaHasta = 'Actual';
                } else if (historialFiltrado[index - 1] && historialFiltrado[index - 1].valido_desde) {
                    const fechaAnterior = new Date(historialFiltrado[index - 1].valido_desde).toLocaleString('es-ES', {
                        day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit'
                    });
                    fechaHasta = fechaAnterior;
                }

                html += `
                    <tr style="background: ${index % 2 === 0 ? rowBg : rowAltBg}; border-bottom: 1px solid ${borderColor};">
                        <td style="padding: 12px; color: ${textColor}; font-weight: 600;">${item.precio.toFixed(2)} €</td>
                        <td style="padding: 12px; color: ${textColor};">${fechaDesde}</td>
                        <td style="padding: 12px; color: ${subTextColor};">${fechaHasta}</td>
                        <td style="padding: 12px; color: ${subTextColor};">${item.tarifa || 'Precio Base'}</td>
                        <td style="padding: 12px; color: ${subTextColor};">${item.usuario || 'Sistema'}</td>
                    </tr>
                `;
            });

            tbody.innerHTML = html;
            container.style.display = 'block';
        })
        .catch(err => {
            console.error('Error cargando historial:', err);
            mensaje.textContent = 'Error al cargar el historial de precios';
            mensaje.style.display = 'block';
        });
}

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
}

function toggleExcluirProducto(idProducto, tipo) {
    const index = productosExcluidos.indexOf(idProducto);
    if (index > -1) {
        // Quitar de excluidos
        productosExcluidos.splice(index, 1);
    } else {
        // Añadir a excluidos
        productosExcluidos.push(idProducto);
    }
    // Volver a renderizar la tabla según el tipo
    if (tipo === 'iva') {
        previsualizarCambioIVA();
    } else {
        previsualizarAjustePrecios();
    }
}

function excluirTodosProductos(tipo) {
    // Obtener los productos según el tipo
    const productos = tipo === 'iva' ? productosPrevisualizacionIVA : productosPrevisualizacionPrecios;

    if (!productos || productos.length === 0) {
        return;
    }

    // Obtener todos los IDs de productos
    const todosIds = productos.map(p => p.id);

    // Añadir todos a excluidos (los que ya estén se mantienen)
    todosIds.forEach(id => {
        if (!productosExcluidos.includes(id)) {
            productosExcluidos.push(id);
        }
    });

    // Volver a renderizar la tabla según el tipo
    if (tipo === 'iva') {
        mostrarTablaPrevisualizacionIVA(productos, parseFloat(document.getElementById('nuevoIVA').value));
    } else {
        mostrarTablaPrevisualizacionPrecios(productos, parseFloat(document.getElementById('porcentajeAjuste').value));
    }
}

function incluirTodosProductos(tipo) {
    // Limpiar el array de excluidos
    productosExcluidos = [];

    // Volver a renderizar la tabla según el tipo
    if (tipo === 'iva') {
        const productos = productosPrevisualizacionIVA;
        if (productos && productos.length > 0) {
            mostrarTablaPrevisualizacionIVA(productos, parseFloat(document.getElementById('nuevoIVA').value));
        }
    } else {
        const productos = productosPrevisualizacionPrecios;
        if (productos && productos.length > 0) {
            mostrarTablaPrevisualizacionPrecios(productos, parseFloat(document.getElementById('porcentajeAjuste').value));
        }
    }
}

function aplicarAjustePrecios() {
    const porcentaje = parseFloat(document.getElementById('porcentajeAjuste').value);

    if (isNaN(porcentaje)) {
        alert('Por favor, introduce un porcentaje válido');
        return;
    }

    const mensaje = productosExcluidos.length > 0
        ? (porcentaje > 0
            ? `¿Estás seguro de subir los precios un ${porcentaje}%? (${productosExcluidos.length} productos excluidos)`
            : `¿Estás seguro de bajar los precios un ${Math.abs(porcentaje)}%? (${productosExcluidos.length} productos excluidos)`)
        : (porcentaje > 0
            ? `¿Estás seguro de subir los precios un ${porcentaje}%?`
            : `¿Estás seguro de bajar los precios un ${Math.abs(porcentaje)}%?`);

    if (!confirm(mensaje)) {
        return;
    }

    // Construir la URL con los productos excluidos
    let url = 'api/productos.php?ajustePrecios=' + porcentaje;
    if (productosExcluidos.length > 0) {
        url += '&excluidos=' + productosExcluidos.join(',');
    }

    fetch(url, { method: 'POST' })
        .then(res => res.json())
        .then(data => {
            if (data.error) {
                alert(data.error);
                return;
            }
            alert('Precios actualizados correctamente. Productos afectados: ' + data.actualizados);
            // Resetear excluidos después de aplicar
            productosExcluidos = [];
        })
        .catch(err => {
            console.error('Error:', err);
            alert('Error al ajustar los precios');
        });
}

/**
 * Abre el modal para programar un ajuste de precios
 */
function abrirModalProgramarAjustePrecios() {
    const ajusteId = document.getElementById('ajusteProgramadoPorcentaje').dataset.ajusteId;
    const porcentajeInput = document.getElementById('porcentajeAjuste').value;

    // Si no hay ajusteId, es un nuevo ajuste - tomar el valor del input principal
    if (!ajusteId && porcentajeInput) {
        document.getElementById('ajusteProgramadoPorcentaje').value = porcentajeInput;
    }

    // Calcular productos afectados
    const totalProductos = productosPrevisualizacionPrecios ? productosPrevisualizacionPrecios.length : 0;
    const productosAfectados = totalProductos - productosExcluidos.length;
    document.getElementById('ajusteProgramadoProductosCount').textContent = productosAfectados;

    // Solo establecer fecha mínima si es un nuevo ajuste (no edición)
    if (!ajusteId) {
        // Establecer fecha y hora mínima (ahora)
        const ahora = new Date();
        ahora.setMinutes(ahora.getMinutes() - ahora.getTimezoneOffset());
        document.getElementById('fechaProgramadaAjuste').min = ahora.toISOString().slice(0, 16);
    }

    // Abrir el modal
    document.getElementById('modalProgramarAjustePrecios').style.display = 'flex';
}

/**
 * Programa un ajuste de precios para una fecha futura
 */
function programarAjustePrecios() {
    const porcentaje = parseFloat(document.getElementById('ajusteProgramadoPorcentaje').value);
    const fechaHora = document.getElementById('fechaProgramadaAjuste').value;
    const productosExcluir = productosExcluidos.join(',');
    const ajusteId = document.getElementById('ajusteProgramadoPorcentaje').dataset.ajusteId;

    if (isNaN(porcentaje)) {
        alert('Por favor, introduce un porcentaje válido');
        return;
    }

    if (!fechaHora) {
        alert('Por favor, selecciona una fecha y hora');
        return;
    }

    const fechaObj = new Date(fechaHora);
    const ahora = new Date();

    if (fechaObj <= ahora && !ajusteId) {
        alert('La fecha programada debe ser posterior a la hora actual');
        return;
    }

    // Si hay un ajusteId, es una edición; otherwise, es nuevo
    if (ajusteId) {
        // Actualizar ajuste existente
        fetch('api/productos.php?accion=actualizar_ajuste_precios_programado&id=' + ajusteId, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'porcentaje=' + porcentaje + '&fecha_programada=' + encodeURIComponent(fechaHora) + '&productos_excluidos=' + productosExcluir
        })
            .then(res => res.json())
            .then(data => {
                if (data.error) {
                    alert(data.error);
                    return;
                }
                alert('Ajuste de precios actualizado correctamente');
                cerrarModal('modalProgramarAjustePrecios');
                delete document.getElementById('ajusteProgramadoPorcentaje').dataset.ajusteId;
            })
            .catch(err => {
                console.error('Error:', err);
                alert('Error al actualizar el ajuste');
            });
    } else {
        // Crear nuevo ajuste
        fetch('api/productos.php?accion=programar_ajuste_precios', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'porcentaje=' + porcentaje + '&fecha_programada=' + encodeURIComponent(fechaHora) + '&productos_excluidos=' + productosExcluir
        })
            .then(res => res.json())
            .then(data => {
                if (data.error) {
                    alert(data.error);
                    return;
                }
                alert('Ajuste de precios programado para el ' + data.fecha_formateada);
                cerrarModal('modalProgramarAjustePrecios');
            })
            .catch(err => {
                console.error('Error:', err);
                alert('Error al programar el ajuste');
            });
    }
}

/**
 * Abre el modal para ver los ajustes de precios programados
 */
function abrirModalVerAjustesProgramados() {
    // Cargar los ajustes programados desde la API
    fetch('api/productos.php?accion=obtener_ajustes_precios_programados')
        .then(res => res.json())
        .then(data => {
            const contenedor = document.getElementById('listaAjustesProgramadosPrecios');

            if (!data.ajustes || data.ajustes.length === 0) {
                contenedor.innerHTML = '<p style="padding: 20px; text-align: center; color: var(--text-secondary);">No hay ajustes de precios programados</p>';
            } else {
                let html = `
                    <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
                        <thead style="background: var(--bg-secondary); position: sticky; top: 0;">
                            <tr>
                                <th style="padding: 10px; text-align: left; border-bottom: 2px solid var(--border-main);">Fecha Programada</th>
                                <th style="padding: 10px; text-align: center; border-bottom: 2px solid var(--border-main);">Porcentaje</th>
                                <th style="padding: 10px; text-align: center; border-bottom: 2px solid var(--border-main);">Estado</th>
                                <th style="padding: 10px; text-align: center; border-bottom: 2px solid var(--border-main);">Afectados</th>
                                <th style="padding: 10px; text-align: center; border-bottom: 2px solid var(--border-main);">Excluidos</th>
                                <th style="padding: 10px; text-align: center; border-bottom: 2px solid var(--border-main);">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>`;

                data.ajustes.forEach(ajuste => {
                    const fecha = new Date(ajuste.fecha_programada);
                    const fechaFormateada = fecha.toLocaleString('es-ES', {
                        day: '2-digit', month: '2-digit', year: 'numeric',
                        hour: '2-digit', minute: '2-digit'
                    });

                    const estadoClass = ajuste.estado === 'aplicado' ? 'style="color: green;"' :
                        ajuste.estado === 'pendiente' ? 'style="color: orange;"' : 'style="color: red;"';

                    const signoPorcentaje = ajuste.porcentaje > 0 ? '+' : '';

                    const excluidos = ajuste.productos_excluidos ?
                        ajuste.productos_excluidos.split(',').length + ' productos' : 'Ninguno';

                    const afectados = ajuste.productos_afectados !== undefined ? ajuste.productos_afectados + ' productos' : '-';

                    const acciones = ajuste.estado === 'pendiente' ? `
                        <button class="btn-admin-accion" onclick="verDetallesAjustePrecios(${ajuste.id})" title="Ver Detalles">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn-admin-accion" onclick="editarAjusteProgramadoPrecios(${ajuste.id})" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn-admin-accion btn-eliminar" onclick="eliminarAjusteProgramadoPrecios(${ajuste.id})" title="Eliminar">
                            <i class="fas fa-trash"></i>
                        </button>` : `
                        <button class="btn-admin-accion" onclick="verDetallesAjustePrecios(${ajuste.id})" title="Ver Detalles">
                            <i class="fas fa-eye"></i>
                        </button>`;

                    html += `
                        <tr style="border-bottom: 1px solid var(--border-main);">
                            <td style="padding: 10px;">${fechaFormateada}</td>
                            <td style="padding: 10px; text-align: center; font-weight: 600;">${signoPorcentaje}${ajuste.porcentaje}%</td>
                            <td style="padding: 10px; text-align: center;" ${estadoClass}>${ajuste.estado.toUpperCase()}</td>
                            <td style="padding: 10px; text-align: center;">${afectados}</td>
                            <td style="padding: 10px; text-align: center;">${excluidos}</td>
                            <td style="padding: 10px; text-align: center;">${acciones}</td>
                        </tr>`;
                });

                html += '</tbody></table>';
                contenedor.innerHTML = html;
            }

            // Abrir el modal
            document.getElementById('modalVerAjustesProgramadosPrecios').style.display = 'flex';
        })
        .catch(err => {
            console.error('Error cargando ajustes programados:', err);
            alert('Error al cargar los ajustes programados');
        });
}

/**
 * Elimina un ajuste de precios programado
 */
function eliminarAjusteProgramadoPrecios(id) {
    if (!confirm('¿Estás seguro de que deseas eliminar este ajuste programado?')) {
        return;
    }

    fetch('api/productos.php?accion=eliminar_ajuste_precios_programado&id=' + id, {
        method: 'DELETE'
    })
        .then(res => res.json())
        .then(data => {
            if (data.ok) {
                alert('Ajuste programado eliminado correctamente');
                abrirModalVerAjustesProgramados(); // Recargar la lista
            } else {
                alert('Error al eliminar: ' + data.error);
            }
        })
        .catch(err => {
            console.error('Error:', err);
            alert('Error al eliminar el ajuste programado');
        });
}

/**
 * Ver los detalles de un ajuste de precios programado
 */
function verDetallesAjustePrecios(id) {
    fetch('api/productos.php?accion=obtener_ajuste_precios_programado&id=' + id)
        .then(res => res.json())
        .then(data => {
            if (data.ajuste) {
                const ajuste = data.ajuste;

                // Determinar tipo de ajuste
                const tipoTexto = ajuste.porcentaje > 0 ? 'Aumento' : 'Reducción';

                // Mostrar info del ajuste
                const fechaProgramada = ajuste.fecha_programada ? new Date(ajuste.fecha_programada).toLocaleString('es-ES') : 'No definida';
                const fechaCreacion = ajuste.created_at ? new Date(ajuste.created_at).toLocaleString('es-ES') : 'No definida';
                const infoHTML = `
                    <div style="background: var(--bg-secondary); padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                        <p><strong>Fecha programada:</strong> ${fechaProgramada}</p>
                        <p><strong>Tipo:</strong> ${tipoTexto}</p>
                        <p><strong>Porcentaje:</strong> ${Math.abs(ajuste.porcentaje)}%</p>
                        <p><strong>Estado:</strong> ${ajuste.estado === 'aplicado' ? '<span style="color: green;">Aplicado</span>' : '<span style="color: orange;">Pendiente</span>'}</p>
                        <p><strong>Creado:</strong> ${fechaCreacion}</p>
                    </div>
                `;
                document.getElementById('detallesAjustePreciosInfo').innerHTML = infoHTML;

                // Cargar productos afectados
                fetch('api/productos.php?accion=obtener_productos_ajuste_precios&id=' + id)
                    .then(res => res.json())
                    .then(dataProductos => {
                        console.log('Precios response:', dataProductos);
                        let tablaHTML = '';
                        if (dataProductos.ok && dataProductos.productos && dataProductos.productos.length > 0) {
                            tablaHTML = `
                                <table class="tabla-admin" style="width: 100%; font-size: 13px;">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Nombre</th>
                                            <th>Precio Anterior</th>
                                            <th>Nuevo Precio</th>
                                            <th>Cambio</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${dataProductos.productos.map(p => {
                                const cambio = p.precio_nuevo - p.precio_anterior;
                                const cambioTexto = cambio >= 0 ? `+${cambio.toFixed(2)}€` : `${cambio.toFixed(2)}€`;
                                return `
                                                <tr>
                                                    <td>${p.id}</td>
                                                    <td>${p.nombre}</td>
                                                    <td>${parseFloat(p.precio_anterior).toFixed(2)}€</td>
                                                    <td>${parseFloat(p.precio_nuevo).toFixed(2)}€</td>
                                                    <td>${cambioTexto}</td>
                                                </tr>
                                            `;
                            }).join('')}
                                    </tbody>
                                </table>
                            `;
                        } else {
                            tablaHTML = '<p style="padding: 20px; text-align: center;">' +
                                (dataProductos.error ? 'Error: ' + dataProductos.error : 'No hay productos afectados') +
                                '</p>';
                        }
                        document.getElementById('detallesAjustePreciosTabla').innerHTML = tablaHTML;
                    });

                abrirModal('modalVerDetallesAjustePrecios');
            } else {
                alert('Error al cargar los detalles del ajuste');
            }
        })
        .catch(err => {
            console.error('Error:', err);
            alert('Error al cargar los detalles del ajuste');
        });
}

/**
 * Edita un ajuste de precios programado
 */
function editarAjusteProgramadoPrecios(id) {
    // Cerrar el modal de ver ajustes
    cerrarModal('modalVerAjustesProgramadosPrecios');

    // Cargar los datos del ajuste
    fetch('api/productos.php?accion=obtener_ajuste_precios_programado&id=' + id)
        .then(res => res.json())
        .then(data => {
            if (data.ajuste) {
                const ajuste = data.ajuste;

                // Establecer el porcentaje
                document.getElementById('porcentajeAjuste').value = ajuste.porcentaje;

                // Establecer la fecha
                const fecha = new Date(ajuste.fecha_programada);
                fecha.setMinutes(fecha.getMinutes() - fecha.getTimezoneOffset());
                document.getElementById('fechaProgramadaAjuste').value = fecha.toISOString().slice(0, 16);

                // Cargar productos excluidos si hay
                if (ajuste.productos_excluidos) {
                    productosExcluidos = ajuste.productos_excluidos.split(',').map(Number);
                } else {
                    productosExcluidos = [];
                }

                // Guardar el ID del ajuste para actualizarlo
                document.getElementById('ajusteProgramadoPorcentaje').dataset.ajusteId = id;

                // Actualizar el contador de productos
                const totalProductos = productosPrevisualizacionPrecios ? productosPrevisualizacionPrecios.length : 0;
                const productosAfectados = totalProductos - productosExcluidos.length;
                document.getElementById('ajusteProgramadoProductosCount').textContent = productosAfectados;

                // Abrir el modal de programación
                abrirModalProgramarAjustePrecios();
            }
        })
        .catch(err => {
            console.error('Error:', err);
            alert('Error al cargar los datos del ajuste');
        });
}

/**
 * Confirma la eliminación de la asociación proveedor-producto
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