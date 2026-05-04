/**
 * Traduce una clave usando el diccionario global LANG (inyectado desde PHP).
 * Soporta dot-notation (ej: 'cajero.search').
 * @param {string} key 
 * @returns {string}
 */
function t(key) {
    if (typeof window.__LANG__ === 'undefined') return key;
    const keys = key.split('.');
    let value = window.__LANG__;
    for (const k of keys) {
        if (value === undefined || value === null || value[k] === undefined) {
            return key;
        }
        value = value[k];
    }
    return typeof value === 'string' ? value : key;
}

/**
 * Redondeo financiero a N decimales.
 * @param {number} num 
 * @param {number} decimals 
 * @returns {number}
 */
function roundTo(num, decimals = 2) {
    if (isNaN(num) || num === null) return 0;
    const factor = Math.pow(10, decimals);
    return Math.round((num + Number.EPSILON) * factor) / factor;
}

/**
 * Redondeo financiero a 2 decimales exactos (Legacy/Frecuente).
 * @param {number} num 
 * @returns {number}
 */
function round2(num) {
    return roundTo(num, 2);
}

/**
 * Determina el número máximo de decimales presentes en el carrito actual.
 * @returns {number} Mínimo 2, máximo 4.
 */
function obtenerDecimalesMaximosCarrito() {
    if (typeof carrito === 'undefined' || carrito.length === 0) return 2;
    const max = Math.max(...carrito.map(item => item.decimales || 2));
    return Math.max(2, Math.min(4, max));
}

/**
 * Carga las tarifas desde la API para usarlas en el selector de productos.
 */
function cargarTarifasCajero() {
    return fetch('api/tarifas.php')
        .then(res => res.json())
        .then(data => {
            tarifasDisponibles = data;
            return data;
        })
        .catch(err => console.error('Error cargando tarifas:', err));
}

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
                '<p class="sin-productos">' + t('common.error_loading') + '</p>';
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
        grid.innerHTML = '<p class="sin-productos">' + t('cajero.no_products') + '</p>';
        return;
    }

    // Construir el HTML de todas las tarjetas de producto.
    let html = '';
    productos.forEach(prod => {
        // Formatear el precio con 2 decimales y coma.
        const ivaProd = (prod.iva !== null && prod.iva !== undefined && prod.iva !== "") ? parseInt(prod.iva) : 21;

        // 1. Encontrar tarifa 'Cliente' (por defecto)
        const tarifaCliente = tarifasDisponibles.find(t => t.nombre === 'Cliente');
        const tarifaClienteId = tarifaCliente ? tarifaCliente.id : null;

        // 2. Comprobar si hay precio para esa tarifa (ya sea manual o calculado)
        const preciosManuales = prod.preciosTarifas || {};
        const precioBaseOriginal = parseFloat(prod.precio) || 0;
        let precioBaseEfectivo = precioBaseOriginal;

        // Usar precio de tarifa si existe, sin importar si es manual o calculado
        if (tarifaClienteId && preciosManuales[tarifaClienteId]) {
            precioBaseEfectivo = preciosManuales[tarifaClienteId].precio;
        }

        const decimales = parseInt(prod.decimales) !== undefined ? parseInt(prod.decimales) : 2;
        const precioPVP = precioBaseEfectivo * (1 + (ivaProd / 100));
        let precioFmt = roundTo(precioPVP, decimales).toFixed(decimales).replace('.', ',');

        // Usar la imagen del producto si existe; de lo contrario, usar el logo por defecto.
        let imgSrc = prod.imagen && prod.imagen !== '' ? prod.imagen : 'webroot/img/logo.PNG';

        // Generar selector de tarifas
        let selectorTarifas = `<select class="tarifa-selector" 
                                onclick="event.stopPropagation()" 
                                onfocus="guardarTarifaAnterior(this)"
                                onchange="actualizarPrecioCard(this, ${precioBaseOriginal}, ${ivaProd})">`;

        tarifasDisponibles.forEach(tarifa => {
            const selected = tarifa.nombre === 'Cliente' ? 'selected' : '';
            const claveTraduccion = 'tarifas.' + tarifa.nombre.toLowerCase().replaceAll(' ', '_');
            selectorTarifas += `<option value="${tarifa.descuento_porcentaje}" 
                                        data-requiere-cliente="${tarifa.requiere_cliente}" 
                                        data-tarifa-id="${tarifa.id}"
                                        ${selected}>${t(claveTraduccion)}</option>`;
        });
        selectorTarifas += `</select>`;

        // Generar la tarjeta del producto.
        html += `<div class="producto-card" data-id="${prod.id}"
                    data-nombre="${(prod.nombre || '').replace(/"/g, '&quot;')}"
                    data-nombre-es="${(prod.nombre_es || '').replace(/"/g, '&quot;')}"
                    data-nombre-en="${(prod.nombre_en || '').replace(/"/g, '&quot;')}"
                    data-nombre-fr="${(prod.nombre_fr || '').replace(/"/g, '&quot;')}"
                    data-nombre-de="${(prod.nombre_de || '').replace(/"/g, '&quot;')}"
                    data-nombre-ru="${(prod.nombre_ru || '').replace(/"/g, '&quot;')}"
                    data-precio="${precioBaseEfectivo}" 
                    data-precio-original="${precioBaseOriginal}"
                    data-pvp="${roundTo(precioPVP, decimales).toFixed(decimales)}"
                    data-iva="${ivaProd}"
                    data-decimales="${decimales}"
                    data-precios-tarifas='${JSON.stringify(preciosManuales)}'
                    data-stock="${prod.stock || 0}"
                    onclick="agregarAlCarrito(this)" style="${prod.stock <= 0 ? 'opacity: 0.5; cursor: not-allowed; scale: 1; transform: translateY(0px);' : ''}">
                    <div class="producto-nombre">${prod.nombre}</div>
                    <div class="producto-imagen">
                        <img src="${imgSrc}" alt="${prod.nombre.replace(/"/g, '"')}">
                    </div>
                    <div class="producto-info-inferior" style="display: flex; flex-direction: column; gap: 2px;">
                        <span class="producto-precio">${precioFmt} €</span>
                        ${selectorTarifas}
                        <span class="producto-stock" ${prod.stock <= 0 ? 'style="color: red; text-decoration: underline;"' : ''}>Stock: ${prod.stock}</span>
                    </div>
                </div>`;
    });

    // Insertar todo el HTML generado en la cuadrícula de productos
    let contenidoGrid = html;

    // Añadir Producto Comodín SOLAMENTE si el usuario tiene permiso
    if (window.PUEDE_PRODUCTO_COMODIN === true) {
        const comodinCard = `
            <div class="producto-card producto-comodin" onclick="abrirModalProductoComodin()"
                 style="cursor: pointer; border: 2px dashed var(--accent-main); background: var(--bg-panel);">
                <div class="producto-nombre" style="color: var(--accent-main); font-weight: 700;">
                    <i class="fas fa-plus-circle"></i> ${t('products.comodin_title')}
                </div>
                <div class="producto-imagen" style="display: flex; align-items: center; justify-content: center; height: 120px;">
                    <i class="fas fa-tag" style="font-size: 3rem; color: var(--accent-main);"></i>
                </div>
                <div class="producto-info-inferior" style="text-align: center;">
                    <span class="producto-precio" style="color: var(--accent-main);">${t('products.comodin_subtitle')}</span>
                </div>
            </div>`;
        contenidoGrid = comodinCard + contenidoGrid;
    }

    grid.innerHTML = contenidoGrid;
}

/**
 * Actualiza el precio PVP desde el input editable en la tarjeta del producto.
 * @param {HTMLElement} input - El input que contiene el nuevo precio
 */
function actualizarPrecioDesdeInput(input) {
    const card = input.closest('.producto-card');
    if (!card) return;

    const nuevoPVP = parseFloat(input.value);
    const ivaProd = parseInt(card.dataset.iva) || 21;

    if (isNaN(nuevoPVP) || nuevoPVP < 0) {
        return; // No actualizar si el valor no es válido
    }

    // Calcular el precio base sin IVA
    const precioBase = nuevoPVP / (1 + (ivaProd / 100));

    const decimales = parseInt(card.dataset.decimales) || 2;

    // Actualizar los datos de la tarjeta
    card.dataset.pvp = nuevoPVP.toFixed(decimales);
    card.dataset.precio = precioBase.toFixed(decimales);

    // Actualizar el precio mostrado en la tarjeta
    const precioSpan = card.querySelector('.producto-precio');
    if (precioSpan) {
        precioSpan.textContent = nuevoPVP.toFixed(decimales).replace('.', ',') + ' €';
    }
}

// Variable para almacenar la tarifa anterior de cada card y poder revertir
let tarifaAnteriorCard = new Map();

/**
 * Guarda la tarifa actual antes de cambiarla para poder revertir si es necesario.
 */
function guardarTarifaAnterior(selectElement) {
    const card = selectElement.closest('.producto-card');
    const id = card.dataset.id;
    tarifaAnteriorCard.set(id, selectElement.value);
}

/**
 * Revierte el selector de tarifa a su valor anterior.
 */
function revertirTarifaCard(cardId) {
    const card = document.querySelector(`.producto-card[data-id="${cardId}"]`);
    if (card) {
        const select = card.querySelector('.tarifa-selector');
        if (select && tarifaAnteriorCard.has(cardId)) {
            select.value = tarifaAnteriorCard.get(cardId);
            // Actualizar precio visualmente
            const iva = parseInt(card.dataset.iva);
            const precioOriginal = parseFloat(card.dataset.precioOriginal || card.dataset.precio);
            actualizarPrecioCard(select, precioOriginal, iva, false);
        }
    }
}

/**
 * Resetea el selector de tarifa de una card a la tarifa "Cliente".
 */
function resetearTarifaCard(card) {
    if (!card) return;
    const select = card.querySelector('.tarifa-selector');
    if (select) {
        // Buscar la opción de "Cliente" (normalmente descuento 0 y nombre "Cliente")
        let optionCliente = null;
        let tarifaClienteId = null;
        for (let i = 0; i < select.options.length; i++) {
            if (select.options[i].text === 'Cliente') {
                optionCliente = select.options[i];
                tarifaClienteId = optionCliente.dataset.tarifaId;
                break;
            }
        }

        if (optionCliente) {
            select.value = optionCliente.value;
            const iva = parseInt(card.dataset.iva);

            // Obtener los precios de tarifa almacenados
            const preciosTarifasStr = card.dataset.preciosTarifas || '{}';
            let preciosTarifas = {};
            try {
                preciosTarifas = JSON.parse(preciosTarifasStr);
            } catch (e) { console.error("Error parseando preciosTarifas", e); }

            // Si existe precio para la tarifa Cliente, usarlo directamente
            let precioBase;
            if (tarifaClienteId && preciosTarifas[tarifaClienteId]) {
                precioBase = preciosTarifas[tarifaClienteId].precio;
            } else {
                // Si no hay precio de tarifa, usar el precio original
                precioBase = parseFloat(card.dataset.precioOriginal || card.dataset.precio);
            }

            actualizarPrecioCard(select, precioBase, iva, false);
        }
    }
}

/**
 * Actualiza el precio mostrado en la tarjeta del producto según la tarifa seleccionada.
 * @param {boolean} triggerAutoAdd - Si es true, intentará añadir al carrito si se cumplen las condiciones.
 */
function actualizarPrecioCard(selectElement, precioBase, iva, triggerAutoAdd = true) {
    const card = selectElement.closest('.producto-card');
    const precioSpan = card.querySelector('.producto-precio');
    const selectedOption = selectElement.options[selectElement.selectedIndex];
    const tarifaId = selectedOption.dataset.tarifaId;
    const descuento = parseFloat(selectElement.value) || 0;
    const requiereCliente = selectedOption.dataset.requiereCliente === "1" || selectedOption.dataset.requiereCliente === "true";

    // Si no tenemos el precio original guardado, lo guardamos ahora
    if (!card.dataset.precioOriginal) {
        card.dataset.precioOriginal = precioBase;
    }
    const precioBaseOriginal = parseFloat(card.dataset.precioOriginal);

    // Obtener precios por tarifa
    const preciosTarifasStr = card.dataset.preciosTarifas || '{}';
    let preciosTarifas = {};
    try {
        preciosTarifas = JSON.parse(preciosTarifasStr);
    } catch (e) { console.error("Error parseando preciosTarifas", e); }

    // Calcular nuevo precio base
    let nuevoPrecioBase;
    // Usar precio de tarifa si existe (ya sea manual o calculado)
    if (preciosTarifas[tarifaId]) {
        nuevoPrecioBase = preciosTarifas[tarifaId].precio;
    } else {
        // Usar descuento porcentual sobre el precio base original
        // NOTA: Aquí se añadirían los datos de nombre al carrito en la implementación real del push al array carrito
        nuevoPrecioBase = precioBaseOriginal * (1 - (descuento / 100));
    }

    // Calcular precio final con IVA para mostrar (PVP unitario)
    // REDONDEAMOS EL PVP UNITARIO A SUS DECIMALES PARA EVITAR DESCUADRES
    const decimales = parseInt(card.dataset.decimales) || 2;
    const precioFinalConIva = roundTo(nuevoPrecioBase * (1 + (iva / 100)), decimales);

    // Formateador dinámico (min 2, max 4, anclado a base)
    const getPrecDinamico = (v, d) => {
        const s = v.toString();
        const decPart = s.split('.')[1] || '';
        return Math.min(4, Math.max(2, d || 2, decPart.length));
    };

    const decimalesShow = getPrecDinamico(precioBaseOriginal, decimales);

    // Actualizar texto en la tarjeta
    precioSpan.textContent = precioFinalConIva.toFixed(decimalesShow).replace('.', ',') + ' €';

    // El nuevo precio base efectivo para el carrito lo recalculamos desde el PVP redondeado
    const nuevoPrecioBaseAjustado = precioFinalConIva / (1 + (iva / 100));

    // Actualizar el data-precio y data-pvp de la card
    card.dataset.precio = nuevoPrecioBaseAjustado.toFixed(decimalesShow);
    card.dataset.pvp = precioFinalConIva.toFixed(decimalesShow);

    // Lógica de cliente registrado
    if (requiereCliente && triggerAutoAdd) {
        // No hay cliente o el cliente cambia: guardar estado y abrir modal
        productoPendienteTarifa = {
            card: card,
            precioBase: precioBase,
            ivaSize: iva
        };
        abrirModalBuscarClienteRegistrado();
    } else if (triggerAutoAdd && descuento > 0) {
        // Tarifa con descuento pero sin requerir cliente (ej: una oferta puntual)
        // El usuario dijo "si cambio a alguna otra se actualiza el precio", 
        // pero para el caso del cliente dijo explícitamente "si está se añade al carrito".
        // Para tarifas normales parece que solo Actualiza el precio. No añadiré al carrito automáticamente salvo que sea restringida.
    }
}

// ======================== PERMISOS Y CREAR PRODUCTOS ========================

/**
 * Verifica si el usuario tiene permiso para crear productos y muestra el botón si corresponde.
 */
function verificarPermisoCrearProductos() {
    console.log('Verificando permiso para crear productos...');
    fetch('api/productos.php?checkPermisoCrear=1')
        .then(res => {
            console.log('Respuesta verificación permiso:', res.status);
            return res.json();
        })
        .then(data => {
            console.log('Datos de permiso:', data);
            console.log('Usuario tiene permiso:', data.tienePermiso);

            // Buscar el botón existente en la barra de opciones
            const btnExistente = document.getElementById('btnNuevoProducto');

            if (btnExistente) {
                if (data.tienePermiso) {
                    // Mostrar el botón existente
                    btnExistente.style.display = 'flex';
                    btnExistente.onclick = function () {
                        abrirModalNuevoProducto();
                    };
                    console.log('Botón mostrado exitosamente');
                } else {
                    // Ocultar el botón si no tiene permiso
                    btnExistente.style.display = 'none';
                    console.log('Usuario sin permiso - botón oculto');
                }
            } else {
                // Fallback: crear el botón dinámicamente si no existe
                console.log('Botón no encontrado, creando dinámicamente...');
                const formBuscar = document.getElementById('formBuscarProducto');
                if (formBuscar) {
                    const btn = document.createElement('button');
                    btn.className = 'btn-nuevo-producto';
                    btn.innerHTML = 'Nuevo';
                    btn.onclick = function () {
                        if (data.tienePermiso) {
                            abrirModalNuevoProducto();
                        } else {
                            alert('No tienes permiso para crear productos. Contacta al administrador.');
                        }
                    };
                    btn.style.marginLeft = '10px';
                    btn.style.padding = '8px 16px';
                    btn.style.background = '#10b981';
                    btn.style.color = 'white';
                    btn.style.border = 'none';
                    btn.style.borderRadius = '6px';
                    btn.style.cursor = 'pointer';
                    btn.style.fontWeight = '600';
                    formBuscar.appendChild(btn);
                    console.log('Botón creado dinámicamente');
                } else {
                    console.error('No se encontró el elemento formBuscarProducto');
                }
            }
        })
        .catch(err => console.error('Error verificando permisos:', err));
}

/**
 * Abre el modal para crear un nuevo producto.
 */
function abrirModalNuevoProducto() {
    console.log('Intentando abrir modal de nuevo producto...');
    const select = document.getElementById('nuevoProductoCategoria');
    console.log('Elemento select encontrado:', select);

    if (!select) {
        console.error('No se encontró el elemento nuevoProductoCategoria');
        alert('Error: No se encontró el formulario del producto. Por favor, recarga la página.');
        return;
    }

    // Limpiar campos del formulario
    document.getElementById('nuevoProductoNombre').value = '';
    document.getElementById('nuevoProductoPrecio').value = '';
    document.getElementById('nuevoProductoStock').value = '0';
    if (document.getElementById('nuevoProductoEstado')) {
        document.getElementById('nuevoProductoEstado').value = '1';
    }
    document.getElementById('editProductoImagen').src = 'webroot/img/logoCPU.PNG';
    document.getElementById('editProductoImagenInput').value = '';

    // Obtener categorías para el select
    fetch('api/categorias.php')
        .then(res => res.json())
        .then(categorias => {
            select.innerHTML = '<option value="">Selecciona una categoría</option>';
            categorias.forEach(cat => {
                select.innerHTML += `<option value="${cat.nombre}">${cat.nombre}</option>`;
            });
            document.getElementById('modalNuevoProducto').style.display = 'flex';
            console.log('Modal mostrado');
        })
        .catch(err => {
            console.error('Error al obtener categorías:', err);
            alert('Error al cargar las categorías. Por favor, recarga la página e intenta de nuevo.');
        });
}

// Verificar permisos y cargar tarifas al cargar la página
document.addEventListener('DOMContentLoaded', function () {
    verificarPermisoCrearProductos();
    cargarTarifasCajero().then(() => {
        // Cargar productos al inicio para que aparezca el Producto Comodín
        buscarProductos();
    });
    initCarouselBotones();
});

// ======================== FUNCIONES DE MODAL (necesarias para el modal de nuevo producto) ========================

/**
 * Cierra un modal ocultándolo.
 * @param {string} id - ID del modal a cerrar.
 */
function cerrarModal(id) {
    document.getElementById(id).style.display = 'none';
}

/**
 * Previsualiza la imagen seleccionada antes de subirla.
 * @param {Event} event - Evento de cambio del input file.
 */
function previsualizarImagen(event) {
    const file = event.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function (e) {
            document.getElementById('editProductoImagen').src = e.target.result;
        };
        reader.readAsDataURL(file);
    }
}

/**
 * Abre una imagen en grande en un modal.
 * @param {string} src - URL de la imagen.
 * @param {string} alt - Texto alternativo de la imagen.
 */
function abrirImagenGrande(src, alt = '') {
    // Crear el elemento overlay
    const overlay = document.createElement('div');
    overlay.id = 'modalImagenGrande';
    overlay.style.cssText = 'position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.9);display:flex;justify-content:center;align-items:center;z-index:9999;cursor:pointer;';

    const img = document.createElement('img');
    img.src = src;
    img.alt = alt;
    img.style.cssText = 'max-width:90%;max-height:90%;object-fit:contain;border-radius:8px;';

    overlay.appendChild(img);
    overlay.onclick = function () {
        document.body.removeChild(overlay);
    };

    document.body.appendChild(overlay);
}

// ======================== CARRUSEL DE BOTONES ========================

/**
 * Inicializa el carrusel de botones de la barra de opciones.
 * Muestra/oculta la flecha según si hay desbordamiento.
 */
function initCarouselBotones() {
    const track = document.getElementById('cajeroCarouselTrack');
    const arrowRight = document.getElementById('cajeroCarouselArrow');
    const arrowLeft = document.getElementById('cajeroCarouselArrowLeft');
    if (!track) return;

    function actualizarFlechasCarousel() {
        const hayOverflow = track.scrollWidth > track.clientWidth + 5;
        const alFinal = track.scrollLeft + track.clientWidth >= track.scrollWidth - 5;
        const alInicio = track.scrollLeft <= 5;

        if (arrowRight) {
            if (!hayOverflow || alFinal) {
                arrowRight.style.opacity = '0.3';
                arrowRight.style.pointerEvents = 'none';
            } else {
                arrowRight.style.opacity = '1';
                arrowRight.style.pointerEvents = 'auto';
            }
        }

        if (arrowLeft) {
            if (!hayOverflow || alInicio) {
                arrowLeft.style.opacity = '0.3';
                arrowLeft.style.pointerEvents = 'none';
            } else {
                arrowLeft.style.opacity = '1';
                arrowLeft.style.pointerEvents = 'auto';
            }
        }
    }

    track.addEventListener('scroll', actualizarFlechasCarousel);
    window.addEventListener('resize', actualizarFlechasCarousel);

    // Esperar a que se rendericen los botones (alguno puede estar hidden)
    setTimeout(actualizarFlechasCarousel, 300);
}

/**
 * Desplaza el carrusel de botones hacia la derecha.
 */
function scrollCarouselBotones() {
    const track = document.getElementById('cajeroCarouselTrack');
    if (!track) return;
    track.scrollBy({ left: 200, behavior: 'smooth' });
}

/**
 * Desplaza el carrusel de botones hacia la izquierda.
 */
function scrollCarouselBotonesIzquierda() {
    const track = document.getElementById('cajeroCarouselTrack');
    if (!track) return;
    track.scrollBy({ left: -200, behavior: 'smooth' });
}

// ======================== MODAL CAMBIAR PRECIOS ========================

let cambiarPreciosTodosProductos = [];
let cambiarPreciosProductosFiltrados = [];
let cambiarPreciosTarifas = [];
let cambiarPreciosPaginaActual = 1;
const CAMBIAR_PRECIOS_POR_PAGINA = 10;
let cambiarPreciosDebounce = null;
let cambiarPreciosMostrarConIva = false;
let cambiosPendientesCajero = {};

/**
 * Actualiza el estado del botón "Aplicar Cambios".
 */
function actualizarBotonAplicarCambios() {
    const btn = document.getElementById('btnAplicarCambiosPrecios');
    if (!btn) return;

    const numCambios = Object.keys(cambiosPendientesCajero).length;
    if (numCambios > 0) {
        btn.disabled = false;
        btn.style.opacity = '1';
        btn.style.cursor = 'pointer';
        btn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg> Aplicar Cambios (${numCambios})`;
    } else {
        btn.disabled = true;
        btn.style.opacity = '0.5';
        btn.style.cursor = 'not-allowed';
        btn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg> Aplicar Cambios`;
    }
}

/**
 * Abre el modal de Cambiar Precios y carga los datos.
 */
function mostrarModalCambiarPrecios() {
    document.getElementById('modalCambiarPrecios').style.display = 'flex';
    document.getElementById('buscarProductoCambiarPrecio').value = '';
    cambiarPreciosPaginaActual = 1;
    cambiosPendientesCajero = {};
    actualizarBotonAplicarCambios();
    cargarDatosCambiarPrecios();
}

/**
 * Carga productos y tarifas desde la API para el modal de cambiar precios.
 */
function cargarDatosCambiarPrecios() {
    const tbody = document.getElementById('bodyTablaCambiarPrecios');
    tbody.innerHTML = '<tr><td colspan="10" style="text-align:center;padding:40px;color:var(--text-muted);">Cargando productos...</td></tr>';

    Promise.all([
        fetch('api/tarifas.php').then(r => r.json()),
        fetch('api/productos.php').then(r => r.json())
    ])
        .then(([tarifas, productos]) => {
            cambiarPreciosTarifas = tarifas;
            cambiarPreciosTodosProductos = productos;
            cambiarPreciosProductosFiltrados = [...productos];
            cambiarPreciosPaginaActual = 1;
            renderizarCabecerasCambiarPrecios();
            renderizarTablaCambiarPrecios();
        })
        .catch(err => {
            console.error('Error cargando datos para cambiar precios:', err);
            tbody.innerHTML = '<tr><td colspan="10" style="text-align:center;padding:40px;color:var(--accent-danger);">Error al cargar los datos</td></tr>';
        });
}

/**
 * Genera las cabeceras dinámicas de la tabla de cambiar precios.
 */
function renderizarCabecerasCambiarPrecios() {
    const thead = document.getElementById('cabeceraCambiarPrecios');
    const thStyle = 'padding:12px 8px;text-align:left;font-weight:600;font-size:12px;text-transform:uppercase;color:var(--text-main);border-bottom:2px solid var(--border-main);background:var(--bg-panel);white-space:nowrap;';

    let html = `<th style="${thStyle}">Producto</th>`;
    html += `<th style="${thStyle}text-align:right;">Precio Base</th>`;

    cambiarPreciosTarifas.forEach(tarifa => {
        html += `<th style="${thStyle}">${tarifa.nombre}</th>`;
    });

    thead.innerHTML = html;
}

/**
 * Renderiza la tabla paginada de productos y tarifas.
 */
function renderizarTablaCambiarPrecios() {
    const tbody = document.getElementById('bodyTablaCambiarPrecios');
    const prods = cambiarPreciosProductosFiltrados;
    const totalPaginas = Math.max(1, Math.ceil(prods.length / CAMBIAR_PRECIOS_POR_PAGINA));

    if (cambiarPreciosPaginaActual > totalPaginas) cambiarPreciosPaginaActual = totalPaginas;

    const inicio = (cambiarPreciosPaginaActual - 1) * CAMBIAR_PRECIOS_POR_PAGINA;
    const productosPagina = prods.slice(inicio, inicio + CAMBIAR_PRECIOS_POR_PAGINA);

    if (!productosPagina.length) {
        tbody.innerHTML = `<tr><td colspan="${2 + cambiarPreciosTarifas.length}" style="text-align:center;padding:40px;color:var(--text-muted);">No se encontraron productos</td></tr>`;
        renderizarPaginacionCambiarPrecios(totalPaginas);
        return;
    }

    let html = '';
    productosPagina.forEach(prod => {
        const ivaProd = parseFloat(prod.iva) || 21;
        const precioBase = parseFloat(prod.precio) || 0;

        const getPrec = (v, d) => {
            const s = v.toString();
            const decPart = s.split('.')[1] || '';
            return Math.min(4, Math.max(2, d || 2, decPart.length));
        };
        const prec = getPrec(precioBase, prod.decimales);

        html += `<tr style="border-bottom:1px solid var(--border-main);">`;
        html += `<td style="padding:8px 6px;font-weight:500;color:var(--text-main);white-space:nowrap;">${prod.nombre}</td>`;

        let precioBaseAMostrar = precioBase;
        if (cambiarPreciosMostrarConIva) precioBaseAMostrar = precioBase * (1 + ivaProd / 100);
        html += `<td style="padding:8px 6px;font-weight:600;text-align:right;">${precioBaseAMostrar.toFixed(prec).replace('.', ',')} €</td>`;

        cambiarPreciosTarifas.forEach(tarifa => {
            const dataTarifa = prod.preciosTarifas && prod.preciosTarifas[tarifa.id];
            let precioFinal = 0;
            let esManual = false;

            if (dataTarifa) {
                precioFinal = parseFloat(dataTarifa.precio);
                esManual = dataTarifa.es_manual == 1;
            } else {
                const descuento = parseFloat(tarifa.descuento_porcentaje) || 0;
                precioFinal = precioBase * (1 - descuento / 100);
            }

            const claveCambio = `${prod.id}_${tarifa.id}`;
            const tieneCambioPendiente = cambiosPendientesCajero.hasOwnProperty(claveCambio);

            if (tieneCambioPendiente) {
                precioFinal = cambiosPendientesCajero[claveCambio].precioModificado;
            }

            let precioAMostrar = precioFinal;
            if (cambiarPreciosMostrarConIva) precioAMostrar = precioFinal * (1 + ivaProd / 100);

            let manualStyle = esManual
                ? 'border:1px solid var(--accent-success);background:var(--bg-accent-success);color:var(--accent-success);'
                : 'border:1px solid var(--border-main);background:var(--bg-input);color:var(--accent-success);';

            if (tieneCambioPendiente) {
                manualStyle = 'border:2px solid var(--accent-warning);background:var(--bg-accent-warning);color:var(--accent-warning);';
            }

            const disabledAttr = cambiarPreciosMostrarConIva ? 'disabled' : '';
            const disabledStyle = cambiarPreciosMostrarConIva ? 'opacity:0.6;cursor:not-allowed;' : '';

            html += `<td style="padding:8px 6px;">
                <div style="display:flex;align-items:center;gap:4px;">
                    <input type="number" step="0.0001" value="${precioAMostrar.toFixed(prec)}"
                        data-precio-original="${precioFinal.toFixed(4)}"
                        onchange="estacionarCambioPrecioCajero(${prod.id},${tarifa.id},this,${ivaProd})"
                        style="width:70px;padding:4px 6px;border-radius:4px;font-weight:600;text-align:right;${manualStyle}${disabledStyle}"
                        onclick="event.stopPropagation()" ${disabledAttr}>
                    <span style="font-size:14px;font-weight:600;color:var(--accent-success);">€</span>
                    ${esManual ? '<i class="fas fa-hand-paper" title="Precio manual" style="color:#10b981;font-size:12px;"></i>' : ''}
                </div>
            </td>`;
        });

        html += '</tr>';
    });

    tbody.innerHTML = html;
    renderizarPaginacionCambiarPrecios(totalPaginas);
}

/**
 * Renderiza la paginación del modal de cambiar precios usando el estilo del panel admin.
 */
function renderizarPaginacionCambiarPrecios(totalPaginas) {
    const container = document.getElementById('paginacionCambiarPrecios');
    if (totalPaginas <= 1) {
        container.innerHTML = `<span style="color:var(--text-muted);font-size:0.85rem;">${cambiarPreciosProductosFiltrados.length} producto(s)</span>`;
        return;
    }

    let botones = '';

    if (cambiarPreciosPaginaActual > 1) {
        botones += `<button class="btn-paginacion" onclick="cambiarPaginaCambiarPrecios(1)" title="Primera página">
            <i class="fas fa-angle-double-left"></i></button>`;
        botones += `<button class="btn-paginacion" onclick="cambiarPaginaCambiarPrecios(${cambiarPreciosPaginaActual - 1})" title="Página anterior">
            <i class="fas fa-chevron-left"></i></button>`;
    }

    botones += `<div class="input-paginacion">
        <input type="number" id="inputPaginaCambiarPrecios" class="input-numero-pagina"
            value="${cambiarPreciosPaginaActual}" min="1" max="${totalPaginas}"
            onchange="irAPaginaCambiarPrecios()"
            onkeypress="if(event.key==='Enter') irAPaginaCambiarPrecios()">
        <span class="info-paginacion"> de ${totalPaginas}</span>
    </div>`;

    if (cambiarPreciosPaginaActual < totalPaginas) {
        botones += `<button class="btn-paginacion" onclick="cambiarPaginaCambiarPrecios(${cambiarPreciosPaginaActual + 1})" title="Siguiente página">
            <i class="fas fa-chevron-right"></i></button>`;
        botones += `<button class="btn-paginacion" onclick="cambiarPaginaCambiarPrecios(${totalPaginas})" title="Última página">
            <i class="fas fa-angle-double-right"></i></button>`;
    }

    container.innerHTML = `<div class="admin-paginacion-wrapper" style="padding:0; position:static;">
                <div class="admin-paginacion">${botones}</div>
            </div>`;
}

/**
 * Ir a una página específica desde el input de paginación del modal.
 */
function irAPaginaCambiarPrecios() {
    const input = document.getElementById('inputPaginaCambiarPrecios');
    if (!input) return;
    let p = parseInt(input.value);
    const totalPaginas = Math.ceil(cambiarPreciosProductosFiltrados.length / CAMBIAR_PRECIOS_POR_PAGINA);
    if (isNaN(p) || p < 1) p = 1;
    else if (p > totalPaginas) p = totalPaginas;
    cambiarPaginaCambiarPrecios(p);
}

/**
 * Cambia la página de la tabla de cambiar precios.
 */
function cambiarPaginaCambiarPrecios(pagina) {
    const totalPaginas = Math.ceil(cambiarPreciosProductosFiltrados.length / CAMBIAR_PRECIOS_POR_PAGINA);
    if (pagina < 1 || pagina > totalPaginas) return;
    cambiarPreciosPaginaActual = pagina;
    renderizarTablaCambiarPrecios();
}

/**
 * Filtra productos en el modal de cambiar precios por nombre (con debounce).
 */
function buscarProductosCambiarPrecio() {
    clearTimeout(cambiarPreciosDebounce);
    cambiarPreciosDebounce = setTimeout(() => {
        const texto = document.getElementById('buscarProductoCambiarPrecio').value.trim().toLowerCase();
        if (texto) {
            cambiarPreciosProductosFiltrados = cambiarPreciosTodosProductos.filter(p =>
                p.nombre.toLowerCase().includes(texto)
            );
        } else {
            cambiarPreciosProductosFiltrados = [...cambiarPreciosTodosProductos];
        }
        cambiarPreciosPaginaActual = 1;
        renderizarTablaCambiarPrecios();
    }, 300);
}

/**
 * Alterna la vista de precios con o sin IVA en el modal.
 */
function toggleIvaCambiarPrecios() {
    cambiarPreciosMostrarConIva = !cambiarPreciosMostrarConIva;
    const btn = document.getElementById('btnToggleIvaCambiarPrecios');
    if (btn) {
        if (cambiarPreciosMostrarConIva) {
            btn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg> Ver Sin IVA`;
            btn.style.background = '#10b981';
        } else {
            btn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="16"></line><line x1="8" y1="12" x2="16" y2="12"></line></svg> Ver Con IVA`;
            btn.style.background = '#4b5563';
        }
    }
    renderizarTablaCambiarPrecios();
}

/**
 * Estaciona un cambio de precio localmente en cambiosPendientesCajero.
 * Modifica el estilo visual del input.
 */
function estacionarCambioPrecioCajero(idProducto, idTarifa, input, iva) {
    const nuevoPrecio = parseFloat(input.value) || 0;

    // Si la vista está en IVA, no debería disparar el onchange por estar disabled, 
    // pero por si acaso, si sucede, no hacemos nada o lo revertimos.
    if (cambiarPreciosMostrarConIva || nuevoPrecio < 0) {
        input.value = parseFloat(input.getAttribute('data-precio-original') || 0).toFixed(2);
        return;
    }

    const clave = `${idProducto}_${idTarifa}`;

    cambiosPendientesCajero[clave] = {
        idProducto: idProducto,
        idTarifa: idTarifa,
        precioModificado: nuevoPrecio
    };

    // Estilo visual de "cambio pendiente" (naranja) - adaptativo para modo oscuro
    const isDark = document.body.classList.contains('dark-mode');
    if (isDark) {
        input.style.border = '2px solid var(--accent-warning)';
        input.style.background = 'var(--bg-accent-warning)';
        input.style.color = 'var(--text-main)';
    } else {
        input.style.border = '2px solid #f59e0b';
        input.style.background = '#fffbeb';
        input.style.color = '#92400e';
    }

    actualizarBotonAplicarCambios();
}

/**
 * Aplica todos los cambios de precios pendientes realizando peticiones secuenciales o paralelas a la API.
 */
function aplicarCambiosPreciosCajero() {
    const claves = Object.keys(cambiosPendientesCajero);
    if (claves.length === 0) return;

    const btn = document.getElementById('btnAplicarCambiosPrecios');
    const textoOriginal = btn.innerHTML;

    btn.disabled = true;
    btn.style.opacity = '0.5';
    btn.style.cursor = 'wait';
    btn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Aplicando...`;

    const promesas = claves.map(clave => {
        const cambio = cambiosPendientesCajero[clave];
        const fd = new FormData();
        fd.append('actualizarPrecioIndividual', '1');
        fd.append('idTarifa', cambio.idTarifa);
        fd.append('idProducto', cambio.idProducto);
        fd.append('precio', cambio.precioModificado.toFixed(4));
        fd.append('esManual', '1');

        return fetch('api/tarifas.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                if (data.ok) {
                    // Actualizar memoria
                    const prod = cambiarPreciosTodosProductos.find(p => p.id == cambio.idProducto);
                    if (prod) {
                        if (!prod.preciosTarifas) prod.preciosTarifas = {};
                        prod.preciosTarifas[cambio.idTarifa] = { precio: cambio.precioModificado, es_manual: 1 };
                    }
                    return { ok: true };
                }
                return { ok: false, error: data.error };
            })
            .catch(err => ({ ok: false, error: err.message }));
    });

    Promise.all(promesas).then(resultados => {
        const errores = resultados.filter(r => !r.ok);

        if (errores.length > 0) {
            alert(`Hubo errores al aplicar ${errores.length} cambios.`);
            cambiosPendientesCajero = {};
            actualizarBotonAplicarCambios();
            renderizarTablaCambiarPrecios();
            cargarTarifasCajero().then(() => buscarProductos());
        } else {
            btn.innerHTML = `<i class="fas fa-check"></i> ¡Guardado!`;
            btn.style.background = '#059669';
            btn.style.opacity = '1';

            setTimeout(() => {
                cambiosPendientesCajero = {};
                actualizarBotonAplicarCambios();
                renderizarTablaCambiarPrecios();
                cargarTarifasCajero().then(() => buscarProductos());
            }, 1500);
        }

    }).catch(err => {
        console.error('Error aplicando cambios:', err);
        alert('Error grave de conexión al aplicar los cambios.');
        actualizarBotonAplicarCambios();
    });
}

// ==============================================
// FUNCIONES FALTANTES - CORRECCIÓN ERRORES
// ==============================================

/**
 * Abre el modal de Retiro de Dinero
 */
function mostrarModalRetiro() {
    const modal = document.getElementById('modalRetiro');
    if (modal) {
        modal.style.display = 'flex';
        const importeInput = document.getElementById('importeRetiro');
        if (importeInput) importeInput.focus();
    }
}

/**
 * Abre el modal de Devoluciones
 */
function mostrarModalDevolucion() {
    const modal = document.getElementById('modalDevolucion');
    if (modal) {
        modal.style.display = 'flex';
        const input = document.getElementById('inputTicketIdDev');
        if (input) input.focus();
    }
}

/**
 * Abre el modal para AÑADIR un NUEVO cliente habitual
 */
function abrirModalClienteHabitual() {
    const modal = document.getElementById('modalClienteHabitual');
    if (modal) {
        modal.style.display = 'flex';
        // Limpiar todos los campos
        const inputs = modal.querySelectorAll('input');
        inputs.forEach(input => input.value = '');
        // Enfocar el primer campo
        const primerInput = modal.querySelector('input');
        if (primerInput) primerInput.focus();
    }
}

/**
 * Busca un ticket mediante su ID para iniciar el proceso de devolución.
 */
function buscarTicketParaDevolucion() {
    const input = document.getElementById('inputTicketIdDev');
    if (!input) return;

    const ticketId = input.value.trim();
    const errorEl = document.getElementById('errorTicketDev');

    if (!ticketId) {
        if (errorEl) {
            errorEl.textContent = 'Introduce el número de ticket';
            errorEl.style.display = 'block';
        }
        return;
    }

    if (errorEl) errorEl.style.display = 'none';

    // Parse formato número de ticket (ej: "T00001" -> serie="T", numero=1)
    let serie = '';
    let numero = ticketId;

    const match = ticketId.match(/^([TF]?)0*(\d+)$/i);
    if (match) {
        serie = match[1].toUpperCase();
        numero = match[2];
    }

    // Construir URL con serie y numero
    let url = `api/ventas.php?checkVentaDevolucion=${numero}`;
    if (serie) {
        url += `&serie=${serie}`;
    }

    // Consultar API para verificar el ticket y obtener productos
    fetch(url)
        .then(res => res.json())
        .then(data => {
            if (data.error) {
                if (errorEl) {
                    errorEl.textContent = 'Error: ' + data.error;
                    errorEl.style.display = 'block';
                }
                return;
            }

            // Ticket encontrado correctamente, mostrar paso 2

            // Ocultar paso 1 y mostrar paso 2
            const paso1 = document.getElementById('devolucionPaso1');
            const paso2 = document.getElementById('devolucionPaso2');

            if (paso1) paso1.style.display = 'none';
            if (paso2) paso2.style.display = 'block';

            // Cargar los productos del ticket en el paso 2
            console.log('Datos del ticket cargados:', data);

            // ✅ CORRECCION: La API devuelve anidado: { venta: {}, lineas: [] }
            // Renderizar los productos de la venta
            renderizarProductosDevolucion(data);

            // Actualizar totales (con comprobación de existencia para evitar errores)
            const elTotalOriginal = document.getElementById('devTotalOriginal');
            const elTotalDevolver = document.getElementById('devTotalDevolver');

            if (elTotalOriginal && data.venta) elTotalOriginal.textContent = (data.venta.total || 0).toFixed(2).replace('.', ',') + ' €';
            if (elTotalDevolver) elTotalDevolver.textContent = '0,00 €';
        })
        .catch(err => {
            console.error(err);
            if (errorEl) {
                errorEl.textContent = 'Error de conexión';
                errorEl.style.display = 'block';
            }
        });
}

/**
 * Cierra el modal y resetea su estado.
 */
function cerrarModalDevolucion() {
    cerrarModal('modalDevolucion');
    // Resetear estado del modal
    const paso1 = document.getElementById('devolucionPaso1');
    const paso2 = document.getElementById('devolucionPaso2');
    const input = document.getElementById('inputTicketIdDev');
    const errorEl = document.getElementById('errorTicketDev');

    if (paso1) paso1.style.display = 'block';
    if (paso2) paso2.style.display = 'none';
    if (input) input.value = '';
    if (errorEl) errorEl.style.display = 'none';
}

/**
 * Limita el input a un máximo de 4 decimales en tiempo real.
 * @param {HTMLInputElement} input
 */
function validar4Decimales(input) {
    if (input.value.includes('.')) {
        const parts = input.value.split('.');
        if (parts[1].length > 4) {
            input.value = parts[0] + '.' + parts[1].slice(0, 4);
        }
    }
}

/**
 * Renderiza la lista de productos disponibles para devolver
 * @param {Object} venta - Objeto de venta con lineas de productos
 */
function renderizarProductosDevolucion(data) {
    const container = document.getElementById('listaProductosDevolucion');
    if (!container) return;

    // ✅ CORRECCION: API devuelve { venta: {}, lineas: [] }
    const lineas = data.lineas || [];

    if (lineas.length === 0) {
        container.innerHTML = '<p style="text-align:center; color: var(--text-muted); padding: 30px;">No hay productos disponibles para devolver</p>';
        return;
    }

    let html = '';

    lineas.forEach((linea, index) => {
        const cantMax = linea.cantidad - (linea.devuelta || 0);

        if (cantMax <= 0) return;

        html += `
        <div class="dev-producto-item" style="display: flex; align-items: center; gap: 12px; padding: 12px; background: var(--bg-input); border-radius: 8px; margin-bottom: 8px;">
            <div style="flex-grow: 1; text-align: left;">
                <div style="font-weight: 600; font-size: 0.95rem;">${linea.nombre}</div>
                <div style="font-size: 0.85rem; color: var(--text-muted);">Precio: ${parseFloat(linea.precio).toFixed(2).replace('.', ',')} € | Disponibles: ${cantMax}</div>
            </div>
            <input type="number" 
                   min="0" 
                   max="${cantMax}" 
                   value="0" 
                   onchange="calcularTotalDevolucion()"
                   class="dev-cantidad-input"
                   style="width: 70px; padding: 6px 8px; border: 2px solid var(--border-main); border-radius: 6px; text-align: center; font-weight: 600;"
                   data-precio="${linea.precio}"
                   data-indice="${index}">
        </div>`;
    });

    container.innerHTML = html;
}

/**
 * Calcula el total de devolución automaticamente al cambiar cantidades
 */
function calcularTotalDevolucion() {
    let total = 0;
    const inputs = document.querySelectorAll('.dev-cantidad-input');

    inputs.forEach(input => {
        const cant = parseInt(input.value) || 0;
        const precio = parseFloat(input.dataset.precio) || 0;
        total += cant * precio;
    });

    const elTotalDevolver = document.getElementById('devTotalDevolver');
    if (elTotalDevolver) elTotalDevolver.textContent = total.toFixed(2).replace('.', ',') + ' €';
}
