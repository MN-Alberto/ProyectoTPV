/**
 * cajero.js
 * 
 * Script principal para la vista del cajero del sistema TPV.
 * Gestiona la interacción con categorías, la búsqueda de productos
 * y el renderizado dinámico de la cuadrícula de productos mediante AJAX.
 */

// ======================== CATEGORÍAS (AJAX) ========================

/**
 * Selecciona una categoría y carga los productos correspondientes mediante AJAX.
 * También tiene en cuenta el texto de búsqueda activo para combinar ambos filtros.
 * 
 * @param {HTMLElement} boton - El botón de categoría que fue pulsado.
 * @param {number|null} idCategoria - El ID de la categoría seleccionada, o null para "todas".
 */
function seleccionarCategoria(boton, idCategoria) {
    // Eliminar la clase 'activa' de todos los botones de categoría
    // y añadirla únicamente al botón que se acaba de pulsar.
    document.querySelectorAll('.cat-btn').forEach(b => b.classList.remove('activa'));
    boton.classList.add('activa');

    // Obtener el texto del campo de búsqueda por si el usuario ya ha escrito algo,
    // de forma que se pueda combinar el filtro de categoría con el de búsqueda.
    const textoBusqueda = document.getElementById('inputBuscarProducto').value.trim();

    // Construir la URL de la petición AJAX con los parámetros necesarios.
    let url = 'api/productos.php?';
    let params = new URLSearchParams();

    // Si se ha seleccionado una categoría concreta, se envía su ID;
    // en caso contrario se envía 'todas' para obtener todos los productos.
    if (idCategoria !== null) {
        params.append('idCategoria', idCategoria);
    } else {
        params.append('idCategoria', 'todas');
    }

    // Si hay texto de búsqueda, se añade como parámetro adicional.
    if (textoBusqueda) {
        params.append('buscarProducto', textoBusqueda);
    }

    // Completar la URL con los parámetros codificados.
    url += params.toString();

    // Realizar la petición fetch al endpoint de la API de productos.
    fetch(url)
        .then(res => res.json())                // Parsear la respuesta como JSON
        .then(data => renderProductos(data))     // Renderizar los productos recibidos
        .catch(err => {
            // En caso de error, mostrar un mensaje en consola y en la cuadrícula.
            console.error('Error cargando productos:', err);
            document.getElementById('productosGrid').innerHTML =
                '<p class="sin-productos">Error al cargar los productos.</p>';
        });
}

// ======================== BÚSQUEDA (AJAX) - LIVE SEARCH ========================

/**
 * Busca productos en tiempo real (live search) combinando el texto introducido
 * con la categoría actualmente seleccionada.
 * Se invoca cada vez que el usuario escribe en el campo de búsqueda.
 */
function buscarProductos() {
    // Obtener el texto escrito en el campo de búsqueda, eliminando espacios sobrantes.
    const texto = document.getElementById('inputBuscarProducto').value.trim();

    // Determinar la categoría activa actualmente.
    // Por defecto se asume 'todas' si no hay ningún botón con la clase 'activa'.
    let idCategoriaActiva = 'todas';
    const botonActivo = document.querySelector('.cat-btn.activa');
    if (botonActivo && botonActivo.dataset.categoria) {
        idCategoriaActiva = botonActivo.dataset.categoria;
    }

    // Construir la URL con los parámetros de búsqueda y categoría.
    let url = 'api/productos.php?';
    let params = new URLSearchParams();

    // Solo añadir el parámetro de búsqueda si el usuario ha escrito algo.
    if (texto) {
        params.append('buscarProducto', texto);
    }

    // Siempre incluir la categoría activa en la petición.
    params.append('idCategoria', idCategoriaActiva);
    url += params.toString();

    // Realizar la petición fetch al endpoint de la API de productos.
    fetch(url)
        .then(res => res.json())                // Parsear la respuesta como JSON
        .then(data => renderProductos(data))     // Renderizar los productos recibidos
        .catch(err => {
            // En caso de error, mostrar un mensaje en consola y en la cuadrícula.
            console.error('Error buscando productos:', err);
            document.getElementById('productosGrid').innerHTML =
                '<p class="sin-productos">Error al buscar productos.</p>';
        });
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
