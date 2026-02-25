// ======================== CATEGORÍAS (AJAX) ========================
function seleccionarCategoria(boton, idCategoria) {
    // Actualizar clase activa.
    document.querySelectorAll('.cat-btn').forEach(b => b.classList.remove('activa'));
    boton.classList.add('activa');

    // Leer si hay texto de búsqueda activo
    const textoBusqueda = document.getElementById('inputBuscarProducto').value.trim();

    // Cargar productos por AJAX combinando.
    let url = 'api/productos.php?';
    let params = new URLSearchParams();

    if (idCategoria !== null) {
        params.append('idCategoria', idCategoria);
    } else {
        params.append('idCategoria', 'todas');
    }

    if (textoBusqueda) {
        params.append('buscarProducto', textoBusqueda);
    }

    url += params.toString();

    fetch(url)
        .then(res => res.json())
        .then(data => renderProductos(data))
        .catch(err => {
            console.error('Error cargando productos:', err);
            document.getElementById('productosGrid').innerHTML =
                '<p class="sin-productos">Error al cargar los productos.</p>';
        });
}

// ======================== BÚSQUEDA (AJAX) - LIVE SEARCH ========================
function buscarProductos() {
    const texto = document.getElementById('inputBuscarProducto').value.trim();

    // Encontrar la categoría activa actual
    let idCategoriaActiva = 'todas';
    const botonActivo = document.querySelector('.cat-btn.activa');
    if (botonActivo && botonActivo.dataset.categoria) {
        idCategoriaActiva = botonActivo.dataset.categoria;
    }

    let url = 'api/productos.php?';
    let params = new URLSearchParams();

    if (texto) {
        params.append('buscarProducto', texto);
    }

    params.append('idCategoria', idCategoriaActiva);
    url += params.toString();

    fetch(url)
        .then(res => res.json())
        .then(data => renderProductos(data))
        .catch(err => {
            console.error('Error buscando productos:', err);
            document.getElementById('productosGrid').innerHTML =
                '<p class="sin-productos">Error al buscar productos.</p>';
        });
}

// ======================== RENDER PRODUCTOS ========================
function renderProductos(productos) {
    const grid = document.getElementById('productosGrid');

    if (!productos || productos.length === 0) {
        grid.innerHTML = '<p class="sin-productos">No hay productos disponibles.</p>';
        return;
    }

    let html = '';
    productos.forEach(prod => {
        let precioFmt = parseFloat(prod.precio).toFixed(2).replace('.', ',');
        let imgSrc = prod.imagen && prod.imagen !== '' ? prod.imagen : 'webroot/img/logo.PNG';
        html += `<div class="producto-card" data-id="${prod.id}"
                    data-nombre="${prod.nombre.replace(/"/g, '&quot;')}"
                    data-precio="${prod.precio}" data-stock="${prod.stock}"
                    onclick="agregarAlCarrito(this)" style="${prod.stock <= 0 ? 'opacity: 0.5; cursor: not-allowed;' : ''}">
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

    grid.innerHTML = html;
}