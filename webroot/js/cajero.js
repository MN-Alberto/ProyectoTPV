/**
 * Traduce una clave usando el diccionario global LANG (inyectado desde PHP).
 * Soporta dot-notation (ej: 'cajero.search').
 * @param {string} key 
 * @returns {string}
 */
function t(key) {
    // Si LANG no está definido, intentar usar window.__LANG__ o devolver la clave
    const dictionary = (typeof LANG !== 'undefined') ? LANG : (typeof window.__LANG__ !== 'undefined' ? window.__LANG__ : null);
    if (!dictionary) return key;

    const keys = key.split('.');
    let value = dictionary;
    for (const k of keys) {
        if (value === undefined || value === null || value[k] === undefined) {
            return key;
        }
        value = value[k];
    }
    return typeof value === 'string' ? value : key;
}

// ======================== VARIABLES GLOBALES DEL CAJERO ========================
// NOTE: LANG, IDIOMAS_TICKET, idiomaTicketSeleccionado, TPV_CONFIG, TPV_CONTEXT
// are declared in vCajero.php inline <script> block. Do NOT redeclare with let/const here.
var carrito = [];
var descuento = { tipo: 'ninguno', valor: 0, cupon: '' };
var descuentoTarifa = { tipo: 'ninguno', valor: 0, cupon: '' };
if (typeof idiomaTicketSeleccionado === 'undefined') { var idiomaTicketSeleccionado = 'es'; }

// Variables inicializadas desde el contexto de PHP (TPV_CONTEXT)
var cajaAbierta = (typeof TPV_CONTEXT !== 'undefined') ? TPV_CONTEXT.cajaAbierta : false;
var efectivoActualCaja = (typeof TPV_CONTEXT !== 'undefined') ? TPV_CONTEXT.efectivoActualCaja : 0;
var tarifasPrefijadas = (typeof TPV_CONTEXT !== 'undefined') ? TPV_CONTEXT.tarifasPrefijadas : [];
var productoPendienteTarifa = null;
var tarifasDisponibles = []; // Se poblará via AJAX
var tipoDocumentoActual = 'ticket';
var metodoEntregaActual = 'imprimir';
var proximosNumeros = { ticket: 'T00000', factura: 'F00000' };
var ticketZoomed = false;
var ticketEsGrandeLocal = false;
var timeoutEscalaTicket = null;
var puntosCanjeados = null;
var clienteIdentificadoEnModalPuntos = false;
var PUEDE_PRODUCTO_COMODIN = (typeof TPV_CONTEXT !== 'undefined') ? TPV_CONTEXT.puedeProductoComodin : false;


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

/**
 * Obtiene el nombre traducido de un producto según el idioma activo.
 * @param {Object} prod 
 * @returns {string}
 */
function getNombreTraducido(prod) {
    if (!prod) return '';
    const lang = idiomaTicketSeleccionado || 'es';
    // Mapeo de códigos de idioma a campos de la base de datos
    const campos = {
        'es': prod.nombre_es || prod.nombre,
        'en': prod.nombre_en || prod.nombre,
        'fr': prod.nombre_fr || prod.nombre,
        'de': prod.nombre_de || prod.nombre,
        'ru': prod.nombre_ru || prod.nombre
    };
    return campos[lang] || prod.nombre;
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

    // Si no hay productos y no se puede mostrar el comodín, mostrar mensaje de "no hay productos"
    if ((!productos || productos.length === 0) && !PUEDE_PRODUCTO_COMODIN) {
        grid.innerHTML = '<p class="sin-productos">' + t('cajero.no_products') + '</p>';
        return;
    }

    // Construir el HTML de todas las tarjetas de producto.
    let html = '';
    if (productos && productos.length > 0) {
        productos.forEach(prod => {
            // Formatear el precio con 2 decimales y coma.
            const ivaProd = (prod.iva !== null && prod.iva !== undefined && prod.iva !== "") ? parseInt(prod.iva) : 21;

            // 1. Encontrar tarifa 'Cliente' (por defecto)
            const tarifaCliente = tarifasDisponibles.find(t => t.id == 1 || t.nombre === 'Cliente');
            const tarifaClienteId = tarifaCliente ? tarifaCliente.id : 1;

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
                const selected = (tarifa.id == 1 || tarifa.nombre === 'Cliente') ? 'selected' : '';
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
                        <div class="producto-nombre">${getNombreTraducido(prod)}</div>
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
    }

    // Insertar todo el HTML generado en la cuadrícula de productos
    let contenidoGrid = html;

    // Añadir Producto Comodín SOLAMENTE si el usuario tiene permiso
    if (PUEDE_PRODUCTO_COMODIN === true) {
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
var tarifaAnteriorCard = new Map();

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
            if (select.options[i].dataset.tarifaId == 1 || select.options[i].text === 'Cliente') {
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
    actualizarBotonesPospuestos();
    verificarCambiosIvaProgramados();

    // ======================== POLLING: SINCRONIZAR EFECTIVO EN CAJA (cada 15s) ========================
    function sincronizarEfectivoCaja() {
        fetch('api/caja.php?accion=estado&_=' + Date.now())
            .then(r => r.json())
            .then(data => {
                if (!data.success || !data.cajaAbierta) return;

                // Actualizar variable global JS para validaciones
                if (typeof TPV_CONTEXT !== 'undefined') {
                    TPV_CONTEXT.efectivoActualCaja = data.importeActual;
                }
                efectivoActualCaja = data.importeActual;

                // Actualizar indicador visual del cajero
                const elValor = document.getElementById('cajeroEfectivoValor');
                if (elValor) {
                    const fmt = data.importeActual.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                    elValor.textContent = fmt + ' €';
                }

                // Actualizar "efectivo disponible" del modal de retiro
                const elDisponible = document.getElementById('efectivoDisponible');
                if (elDisponible) {
                    elDisponible.textContent = data.importeActual.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                }
            })
            .catch(() => {}); // Silenciar errores de red
    }

    // Polling cada 15 segundos
    setInterval(sincronizarEfectivoCaja, 15000);

    // Invalidar estado de puntos si se cambian datos manualmente
    ['clienteNif', 'clienteNombre'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('input', () => { 
            if (clienteIdentificadoEnModalPuntos) {
                clienteIdentificadoEnModalPuntos = false; 
                renderizarVistaPreviaTicket(); 
            }
        });
    });
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

var cambiarPreciosTodosProductos = [];
var cambiarPreciosProductosFiltrados = [];
var cambiarPreciosTarifas = [];
var cambiarPreciosPaginaActual = 1;
var CAMBIAR_PRECIOS_POR_PAGINA = 10;
var cambiarPreciosDebounce = null;
var cambiarPreciosMostrarConIva = false;
var cambiosPendientesCajero = {};

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
        const claveTraduccion = 'tarifas.' + tarifa.nombre.toLowerCase().replaceAll(' ', '_');
        html += `<th style="${thStyle}">${t(claveTraduccion)}</th>`;
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
        html += `<td style="padding:8px 6px;font-weight:500;color:var(--text-main);white-space:nowrap;">${getNombreTraducido(prod)}</td>`;

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



// ======================== CARRITO (persiste en memoria durante la sesión del navegador) ========================

/**
 * Añade un producto al carrito o incrementa su cantidad si ya existe.
 * Lee los datos del producto desde los atributos data-* del elemento HTML.
 * Valida que no se exceda el stock máximo disponible.
 * @param {HTMLElement} elemento - La tarjeta de producto clickeada
 */
function agregarAlCarrito(elemento) {
    if (!cajaAbierta) {
        alert(t('cajero.alert_open_box'));
        return;
    }

    const id = parseInt(elemento.dataset.id) || 0;
    const nombre = elemento.dataset.nombre || 'Producto sin nombre';
    const nombre_es = elemento.dataset.nombreEs || nombre;
    const nombre_en = elemento.dataset.nombreEn || nombre;
    const nombre_fr = elemento.dataset.nombreFr || nombre;
    const nombre_de = elemento.dataset.nombreDe || nombre;
    const nombre_ru = elemento.dataset.nombreRu || nombre;
    const precioBase = parseFloat(elemento.dataset.precio) || 0;
    const iva = parseInt(elemento.dataset.iva || 21);

    const decimales = parseInt(elemento.dataset.decimales) || 2;

    // PVP Actual: Intentar leer de data-pvp, si no existe o es NaN, calcularlo ahora.
    let pvpActual = parseFloat(elemento.dataset.pvp);
    if (isNaN(pvpActual)) {
        pvpActual = roundTo(precioBase * (1 + (iva / 100)), decimales);
    } else {
        pvpActual = roundTo(pvpActual, decimales);
    }

    const stockMax = parseInt(elemento.dataset.stock) || 0;

    // Obtener datos de la tarifa seleccionada
    const selectTarifa = elemento.querySelector('.tarifa-selector');
    let tarifaNombre = 'Cliente';
    let tarifaDescuento = 0;
    let precioBaseSinTarifa = parseFloat(elemento.dataset.precioOriginal || elemento.dataset.precio) || precioBase;

    // PVP Original sin ninguna tarifa aplicada
    let pvpOriginalUnitario = roundTo(precioBaseSinTarifa * (1 + (iva / 100)), decimales);

    if (selectTarifa) {
        const selectedOption = selectTarifa.options[selectTarifa.selectedIndex];
        tarifaNombre = selectedOption.text;
        tarifaDescuento = parseFloat(selectTarifa.value) || 0;
    }

    const existente = carrito.find(item => item.idProducto === id && item.tarifaNombre === tarifaNombre);

    if (existente) {
        if (existente.cantidad >= stockMax) {
            alert(t('cart.alert_no_more_stock'));
            return;
        }
        existente.cantidad++;
    } else {
        if (stockMax <= 0) {
            alert(t('cart.alert_no_stock_available'));
            return;
        }
        carrito.push({
            idProducto: id,
            nombre: nombre,
            nombre_es: nombre_es,
            nombre_en: nombre_en,
            nombre_fr: nombre_fr,
            nombre_de: nombre_de,
            nombre_ru: nombre_ru,
            precio: precioBase,
            precioBaseOriginal: precioBaseSinTarifa,
            preciosTarifas: elemento.dataset.preciosTarifas ? JSON.parse(elemento.dataset.preciosTarifas) : {},
            pvpOriginalUnitario: pvpOriginalUnitario,
            pvpUnitario: pvpActual,
            iva: iva,
            decimales: decimales,
            cantidad: 1,
            stockMax: stockMax,
            tarifaNombre: tarifaNombre,
            tarifaDescuento: tarifaDescuento
        });
    }

    resetearTarifaCard(elemento);
    if (typeof actualizarTicket === 'function') {
        actualizarTicket();
    }
}

/**
 * Elimina un producto del carrito por su índice.
 * @param {number} index - Índice del producto en el array carrito
 */
function eliminarDelCarrito(index) {
    carrito.splice(index, 1);
    if (typeof actualizarTicket === 'function') {
        actualizarTicket();
    }
}

/**
 * Modifica la cantidad de un producto en el carrito.
 * Valida que la cantidad esté entre 1 y el stock máximo.
 * @param {number} indice - Índice del producto en el array carrito
 * @param {number} nuevaCantidad - Nueva cantidad deseada
 */
function cambiarCantidad(indice, nuevaCantidad) {
    const item = carrito[indice];
    if (!item) return;
    nuevaCantidad = parseInt(nuevaCantidad) || 1;

    // Limitar entre 1 y el stock máximo
    if (nuevaCantidad < 1) nuevaCantidad = 1;
    if (nuevaCantidad > item.stockMax) nuevaCantidad = item.stockMax;

    item.cantidad = nuevaCantidad;
    if (typeof actualizarTicket === 'function') {
        actualizarTicket();
    }
}

/**
 * Elimina todos los productos del carrito y resetea el descuento.
 */
function vaciarCarrito() {
    if (carrito.length === 0) return;
    if (confirm(t('cart.confirm_empty'))) {
        carrito = [];

        // Desvincular cliente y resetar descuentos
        descuento = { tipo: 'ninguno', valor: 0, cupon: '' };
        descuentoTarifa = { tipo: 'ninguno', valor: 0, cupon: '' };
        if (typeof desvincularCliente === 'function') {
            desvincularCliente();
        }

        // Resetear también el select de tarifa global a Cliente
        const tarifaCliente = (typeof tarifasPrefijadas !== 'undefined') ? tarifasPrefijadas.find(t => t.nombre === 'Cliente') : null;
        if (tarifaCliente) {
            const selectTarifa = document.getElementById('tarifaVenta');
            if (selectTarifa) selectTarifa.value = tarifaCliente.id;
        }

        if (typeof actualizarTicket === 'function') {
            actualizarTicket();
        }
    }
}

/**
 * Resetea puntos canjeados después de una venta exitosa.
 */
function resetearPuntosCanjeados() {
    puntosCanjeados = null;
}

/**
 * posponerVenta()
 * Guarda la venta actual en sessionStorage para recuperarla después.
 */
function posponerVenta() {
    if (carrito.length === 0) {
        alert(t('cart.alert_no_products_postpone'));
        return;
    }

    const clienteDniInput = document.getElementById('clienteNif');
    const clienteDni = clienteDniInput ? clienteDniInput.value.trim() : '';

    let puntosGanados = 0;
    if (clienteDni !== '') {
        const totalTicket = obtenerTotalCalculado();
        puntosGanados = Math.round(totalTicket * 10);
    }

    const puntosCanjeadosData = (typeof puntosCanjeados !== 'undefined' && puntosCanjeados)
        ? { dni: puntosCanjeados.dni, puntos: puntosCanjeados.puntos }
        : null;

    const cNombreInput = document.getElementById('clienteNombre');
    const clienteNombre = cNombreInput ? cNombreInput.value.trim() : '';

    const ventaId = Date.now();
    const ventaPospuesta = {
        id: ventaId,
        carrito: JSON.parse(JSON.stringify(carrito)),
        descuento: JSON.parse(JSON.stringify(descuento)),
        tarifa: document.getElementById('tarifaVenta')?.value || '',
        clienteDni: clienteDni,
        clienteNombre: clienteNombre,
        puntosCanjeados: puntosCanjeadosData,
        puntosGanados: puntosGanados,
        fecha: new Date().toLocaleString('es-ES')
    };

    let ventasPospuestas = [];
    const ventasJson = sessionStorage.getItem('ventasPospuestas');
    if (ventasJson) {
        try {
            ventasPospuestas = JSON.parse(ventasJson);
        } catch (e) {
            ventasPospuestas = [];
        }
    }

    ventasPospuestas.push(ventaPospuesta);
    sessionStorage.setItem('ventasPospuestas', JSON.stringify(ventasPospuestas));

    carrito = [];
    descuento = { tipo: 'ninguno', valor: 0, cupon: '' };
    puntosCanjeados = null;

    const clienteNifInput = document.getElementById('clienteNif');
    if (clienteNifInput) clienteNifInput.value = '';
    const clienteNombreInput = document.getElementById('clienteNombre');
    if (clienteNombreInput) clienteNombreInput.value = '';
    const indicador = document.getElementById('indicadorClienteDni');
    if (indicador) indicador.style.display = 'none';

    const tarifaCliente = tarifasPrefijadas.find(t => t.nombre === 'Cliente');
    const tarifaVenta = document.getElementById('tarifaVenta');
    if (tarifaCliente && tarifaVenta) {
        tarifaVenta.value = tarifaCliente.id;
    } else if (tarifasPrefijadas.length > 0 && tarifaVenta) {
        tarifaVenta.value = tarifasPrefijadas[0].id;
    }
    actualizarTicket();

    const totalPospuestas = ventasPospuestas.length;
    alert('✅ ' + t('cart.postponed_success1') + ' ' + totalPospuestas + ' ' + t('cart.postponed_success2'));

    actualizarBotonesPospuestos();
}

/**
 * mostrarModalVentasPospuestas()
 * Muestra un modal con todas las ventas pospuestas.
 */
function mostrarModalVentasPospuestas() {
    const ventasJson = sessionStorage.getItem('ventasPospuestas');
    let ventasPospuestas = [];

    if (ventasJson) {
        try {
            ventasPospuestas = JSON.parse(ventasJson);
        } catch (e) {
            ventasPospuestas = [];
        }
    }

    if (ventasPospuestas.length === 0) {
        alert(t('cart.alert_no_postponed_recover'));
        return;
    }

    const isDark = document.body.classList.contains('dark-mode');
    const textColor = isDark ? '#e5e7eb' : '#1a1a2e';
    const borderColor = isDark ? '#374151' : '#e5e7eb';
    const subTextColor = isDark ? '#9ca3af' : '#6b7280';
    const modalHtml = `
        <div id="modalVentasPospuestas" class="modal-overlay" style="display: flex; backdrop-filter: blur(8px); background: rgba(0,0,0,0.4); z-index: 10000; transition: all 0.3s ease;">
            <div class="modal-content glass-modal" style="padding: 0 !important; background: ${isDark ? 'rgba(31, 41, 55, 0.98)' : 'rgba(255, 255, 255, 0.98)'}; border-radius: 24px; border: none; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); max-width: 550px; width: 95%; max-height: 85vh; overflow: hidden; animation: modalFadeIn 0.3s ease-out; display: flex; flex-direction: column;">
                
                <!-- Cabecera estilo Puntos (Premium Gradiente) -->
                <div style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); padding: 40px 30px; text-align: center; position: relative; overflow: hidden;">
                    <!-- Botón Cerrar Flotante -->
                    <button onclick="cerrarModalVentasPospuestas()" 
                        style="position: absolute; top: 20px; right: 20px; background: rgba(255,255,255,0.2); border: none; width: 32px; height: 32px; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white; cursor: pointer; transition: all 0.2s; backdrop-filter: blur(4px);">
                        <i class="fas fa-times"></i>
                    </button>

                    <div style="display: flex; align-items: center; justify-content: center; width: 64px; height: 64px; background: rgba(255, 255, 255, 0.2); backdrop-filter: blur(10px); border-radius: 20px; border: 1px solid rgba(255,255,255,0.3); color: white; margin: 0 auto 20px; box-shadow: 0 10px 20px rgba(0,0,0,0.1);">
                        <i class="fas fa-pause-circle" style="font-size: 1.8rem;"></i>
                    </div>
                    <h3 style="margin: 0; font-size: 1.6rem; font-weight: 800; color: white; letter-spacing: -0.02em;">
                        ${t('cart.postponed_sales_title')}
                    </h3>
                    <p style="margin: 8px 0 0; color: white; opacity: 0.9; font-size: 1rem; font-weight: 500;">
                        ${ventasPospuestas.length} ${ventasPospuestas.length === 1 ? t('cart.pending_sale') || 'venta pendiente' : t('cart.pending_sales') || 'ventas pendientes'}
                    </p>
                </div>

                <div style="padding: 24px; overflow-y: auto; flex: 1; background: ${isDark ? 'transparent' : '#f8fafc'};">
                    ${ventasPospuestas.map((venta, index) => {
                        const totalVenta = venta.carrito.reduce((sum, item) => sum + (item.pvpUnitario * item.cantidad), 0);
                        const numProductos = venta.carrito.reduce((sum, item) => sum + item.cantidad, 0);
                        return `
                            <div class="pospuesta-card" style="background: ${isDark ? '#111827' : 'white'}; border: 1px solid ${borderColor}; border-radius: 18px; padding: 20px; margin-bottom: 16px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); transition: transform 0.2s, box-shadow 0.2s; position: relative; overflow: hidden; border-left: 5px solid #3b82f6;">
                                
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px;">
                                    <div>
                                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                                            <span style="background: rgba(59, 130, 246, 0.1); color: #3b82f6; padding: 3px 10px; border-radius: 8px; font-size: 12px; font-weight: 800; border: 1px solid rgba(59, 130, 246, 0.2);">#${String(venta.numero || (index + 1)).padStart(5, '0').slice(-5)}</span>
                                            <span style="font-size: 12px; color: ${subTextColor}; font-weight: 500;"><i class="far fa-clock" style="margin-right: 4px;"></i> ${venta.fecha}</span>
                                        </div>
                                        <div style="font-size: 16px; font-weight: 700; color: ${textColor};">
                                            <i class="fas fa-user-circle" style="color: #3b82f6; margin-right: 6px; opacity: 0.7;"></i>
                                            ${venta.clienteNombre || t('cart.no_customer')}
                                        </div>
                                    </div>
                                    <div style="text-align: right;">
                                        <div style="font-size: 22px; font-weight: 900; color: #10b981; letter-spacing: -0.5px;">${totalVenta.toFixed(2)} €</div>
                                        <div style="font-size: 12px; color: ${subTextColor}; font-weight: 600; text-transform: uppercase;">${numProductos} ${numProductos === 1 ? t('cart.product_label') || 'Producto' : t('cart.products_label') || 'Productos'}</div>
                                    </div>
                                </div>

                                <div style="display: flex; gap: 12px; margin-top: 5px; position: relative; z-index: 2;">
                                    <button onclick="recuperarVenta(${venta.id})" class="btn-tpv" style="background: #3b82f6; flex: 2; height: 46px; border-radius: 12px; font-weight: 700; display: flex; align-items: center; justify-content: center; gap: 10px; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.25); border: none; color: white; cursor: pointer; transition: transform 0.2s;">
                                        <i class="fas fa-file-import" style="font-size: 14px;"></i> 
                                        ${t('cart.recover_btn')}
                                    </button>
                                    <button onclick="eliminarVentaPospuesta(${venta.id})" class="btn-tpv" style="background: rgba(239, 68, 68, 0.1); color: #ef4444; flex: 1; height: 46px; border-radius: 12px; font-weight: 700; display: flex; align-items: center; justify-content: center; border: 1px solid rgba(239, 68, 68, 0.2); cursor: pointer; transition: all 0.2s;">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>

                                <!-- Decoración Background -->
                                <div style="position: absolute; right: -20px; top: -10px; opacity: 0.05; font-size: 100px; pointer-events: none; transform: rotate(-15deg); color: ${isDark ? 'white' : 'black'};">
                                    <i class="fas fa-receipt"></i>
                                </div>
                            </div>
                        `;
                    }).join('')}
                </div>
            </div>
        </div>
    `;

    document.body.insertAdjacentHTML('beforeend', modalHtml);
}

function cerrarModalVentasPospuestas() {
    const modal = document.getElementById('modalVentasPospuestas');
    if (modal) modal.remove();
}

function eliminarVentaPospuesta(id) {
    if (!confirm(t('cart.confirm_delete_postponed'))) return;

    const ventasJson = sessionStorage.getItem('ventasPospuestas');
    let ventasPospuestas = [];
    if (ventasJson) {
        try {
            ventasPospuestas = JSON.parse(ventasJson);
        } catch (e) {
            ventasPospuestas = [];
        }
    }

    const ventaIndex = ventasPospuestas.findIndex(v => v.id === id);
    if (ventaIndex === -1) {
        alert('No se encontró la venta pospuesta');
        return;
    }

    ventasPospuestas.splice(ventaIndex, 1);
    sessionStorage.setItem('ventasPospuestas', JSON.stringify(ventasPospuestas));

    alert('✅ ' + t('cart.alert_postponed_deleted'));
    actualizarBotonesPospuestos();

    if (ventasPospuestas.length === 0) {
        cerrarModalVentasPospuestas();
    } else {
        cerrarModalVentasPospuestas();
        mostrarModalVentasPospuestas();
    }
}

/**
 * recuperarVenta(id)
 */
function recuperarVenta(id) {
    const ventasJson = sessionStorage.getItem('ventasPospuestas');
    let ventasPospuestas = [];
    if (ventasJson) {
        try {
            ventasPospuestas = JSON.parse(ventasJson);
        } catch (e) {
            alert(t('cart.alert_error_recovering'));
            return;
        }
    }

    const ventaIndex = ventasPospuestas.findIndex(v => v.id === id);
    if (ventaIndex === -1) {
        alert('No se encontró la venta pospuesta');
        return;
    }

    const ventaPospuesta = ventasPospuestas[ventaIndex];
    carrito = ventaPospuesta.carrito;
    descuento = ventaPospuesta.descuento || { tipo: 'ninguno', valor: 0, cupon: '' };

    const tarifaVenta = document.getElementById('tarifaVenta');
    if (ventaPospuesta.tarifa && tarifaVenta) {
        tarifaVenta.value = ventaPospuesta.tarifa;
    } else if (tarifaVenta) {
        const tarifaCliente = tarifasPrefijadas.find(t => t.nombre === 'Cliente');
        if (tarifaCliente) {
            tarifaVenta.value = tarifaCliente.id;
        } else if (tarifasPrefijadas.length > 0) {
            tarifaVenta.value = tarifasPrefijadas[0].id;
        }
    }

    const cNifInput = document.getElementById('clienteNif');
    const cNomInput = document.getElementById('clienteNombre');
    const indicador = document.getElementById('indicadorClienteDni');

    if (cNifInput) cNifInput.value = ventaPospuesta.clienteDni || '';
    if (cNomInput) cNomInput.value = ventaPospuesta.clienteNombre || '';
    if (indicador) indicador.style.display = ventaPospuesta.clienteDni ? 'block' : 'none';

    if (ventaPospuesta.clienteDni && typeof mostrarDniEnTicket === 'function') {
        mostrarDniEnTicket(ventaPospuesta.clienteDni);
    }

    if (ventaPospuesta.puntosCanjeados && ventaPospuesta.puntosCanjeados.dni && ventaPospuesta.puntosCanjeados.puntos > 0) {
        puntosCanjeados = {
            dni: ventaPospuesta.puntosCanjeados.dni,
            puntos: ventaPospuesta.puntosCanjeados.puntos
        };
    } else {
        puntosCanjeados = null;
    }

    actualizarTicket();
    cerrarModalVentasPospuestas();

    ventasPospuestas.splice(ventaIndex, 1);
    sessionStorage.setItem('ventasPospuestas', JSON.stringify(ventasPospuestas));

    alert('✅ ' + t('cart.alert_recovered') + ': ' + ventaPospuesta.fecha);
    actualizarBotonesPospuestos();
}

/**
 * actualizarBotonesPospuestos()
 */
function actualizarBotonesPospuestos() {
    const btnVerPospuestas = document.getElementById('btnVerPospuestas');
    const ventasJson = sessionStorage.getItem('ventasPospuestas');
    let ventasPospuestas = [];
    if (ventasJson) {
        try {
            ventasPospuestas = JSON.parse(ventasJson);
        } catch (e) {
            ventasPospuestas = [];
        }
    }

    const tieneVentas = ventasPospuestas && ventasPospuestas.length > 0;
    if (btnVerPospuestas) {
        if (tieneVentas) {
            btnVerPospuestas.disabled = false;
            btnVerPospuestas.style.opacity = '1';
            btnVerPospuestas.textContent = '📋 (' + ventasPospuestas.length + ')';
        } else {
            btnVerPospuestas.disabled = true;
            btnVerPospuestas.style.opacity = '0.5';
            btnVerPospuestas.textContent = '📋';
        }
    }
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

                // Actualizar los productos del carrito con los nuevos IVA y precios
                if (carrito.length > 0 && data.nuevosIVA) {
                    carrito.forEach(item => {
                        const nuevoIVA = data.nuevosIVA[item.id];
                        if (nuevoIVA) {
                            // Actualizar el IVA del producto
                            item.iva = nuevoIVA;
                            // Recalcular el PVP con el nuevo IVA
                            const prec = item.decimales || 2;
                            const precioBase = parseFloat(item.precio || item.precioConDescuento || 0);
                            item.pvpUnitario = roundTo(precioBase * (1 + (nuevoIVA / 100)), prec);
                            // También actualizar el PVP original si existe
                            if (item.pvpOriginalUnitario) {
                                const precioOriginal = parseFloat(item.precio || 0);
                                item.pvpOriginalUnitario = roundTo(precioOriginal * (1 + (nuevoIVA / 100)), prec);
                            }
                        }
                    });
                    // Actualizar el ticket con los nuevos precios
                    actualizarTicket();
                }

                // Recargar los productos del catálogo para mostrar los nuevos precios
                if (typeof buscarProductos === 'function') {
                    buscarProductos();
                }
            }
        })
        .catch(err => console.error('Error verificando cambios IVA programados:', err));
}

/**
 * Calcula el total del carrito aplicando el descuento vigente.
 * @returns {number} Total final (mínimo 0)
 */
function obtenerTotalCalculado() {
    const precTotal = obtenerDecimalesMaximosCarrito();

    // El total es la suma de los subtotales de cada línea (PVP ya redondeado)
    let totalPVPBruto = carrito.reduce((sum, item) => {
        const subtotalLinea = roundTo(item.pvpUnitario * item.cantidad, precTotal);
        return sum + subtotalLinea;
    }, 0);

    // Calcular descuento manual (global) sobre el total PVP acumulado
    let importeDescuentoManual = 0;
    if (descuento.tipo === 'porcentaje') {
        importeDescuentoManual = roundTo(totalPVPBruto * (descuento.valor / 100), precTotal);
    } else if (descuento.tipo === 'fijo') {
        importeDescuentoManual = roundTo(descuento.valor, precTotal);
    }

    return Math.max(0, roundTo(totalPVPBruto - importeDescuentoManual, precTotal));
}

/**
 * aplicarDescuento()
 * Abre el modal de descuento si hay productos en el carrito.
 */
function aplicarDescuento() {
    if (carrito.length === 0) return;
    const modal = document.getElementById('modalDescuento');
    if (!modal) return;
    modal.style.display = 'flex';

    // Mostrar/ocultar botón de quitar descuento según si hay descuento activo
    const btnQuitar = document.getElementById('btnQuitarDescuento');
    if (btnQuitar) {
        if (descuento.tipo !== 'ninguno') {
            btnQuitar.style.display = 'block';
        } else {
            btnQuitar.style.display = 'none';
        }
    }

    const input = document.getElementById('inputPorcentajeDescuento');
    if (input) input.focus();
}

/**
 * quitarDescuento()
 * Elimina el descuento activo y actualiza el ticket.
 */
function quitarDescuento() {
    // Resetear descuento
    descuento = { tipo: 'ninguno', valor: 0, cupon: '' };

    // Limpiar los inputs del modal
    if (document.getElementById('inputPorcentajeDescuento')) document.getElementById('inputPorcentajeDescuento').value = '';
    if (document.getElementById('inputCuponDescuento')) document.getElementById('inputCuponDescuento').value = '';

    // Cerrar modal y actualizar ticket
    cerrarModal('modalDescuento');
    actualizarTicket();
}

/**
 * procesarDescuento()
 * Procesa el descuento introducido (porcentaje o cupón) y lo aplica al carrito.
 */
function procesarDescuento() {
    let porcentaje = parseFloat(document.getElementById('inputPorcentajeDescuento').value);
    const cupon = document.getElementById('inputCuponDescuento').value.trim().toUpperCase();

    if (!isNaN(porcentaje) && document.getElementById('inputPorcentajeDescuento').value !== '') {
        // Opción 1: Descuento por porcentaje manual
        if (porcentaje < 0 || porcentaje > 100) {
            alert(t('cart.alert_discount_range'));
            return;
        }
        descuento = { tipo: 'porcentaje', valor: porcentaje, cupon: '' };
    } else if (cupon) {
        // Opción 2: Descuento por código de cupón
        if (cupon === 'PROMO10') {
            descuento = { tipo: 'porcentaje', valor: 10, cupon: 'PROMO10' };
        } else if (cupon === 'BIENVENIDA5') {
            descuento = { tipo: 'porcentaje', valor: 5, cupon: 'BIENVENIDA5' };
        } else if (cupon === 'FIJO5') {
            descuento = { tipo: 'fijo', valor: 5, cupon: 'FIJO5' };
        } else {
            alert(t('cart.alert_invalid_coupon'));
            return;
        }
    } else {
        // Sin descuento: resetear
        descuento = { tipo: 'ninguno', valor: 0, cupon: '' };
    }

    // Limpiar los inputs del modal
    document.getElementById('inputPorcentajeDescuento').value = '';
    document.getElementById('inputCuponDescuento').value = '';

    // Cerrar modal y actualizar ticket con el nuevo descuento
    cerrarModal('modalDescuento');
    actualizarTicket();
}

/**
 * Regenera completamente el HTML del ticket/carrito en el panel derecho.
 */
function actualizarTicket() {
    const contenedor = document.getElementById('ticketLineas');
    const totalEl = document.getElementById('ticketTotal');
    const btnCobrar = document.getElementById('btnCobrar');
    const btnDescuento = document.getElementById('btnDescuento');
    const btnPosponer = document.getElementById('btnPosponer');

    if (!contenedor || !totalEl || !btnCobrar || !btnDescuento || !btnPosponer) return;

    // Si el carrito está vacío, mostrar mensaje y deshabilitar botones
    if (carrito.length === 0) {
        contenedor.innerHTML = `<p class="ticket-vacio">${t('cart.empty_message')}</p>`;
        const ticketDesglose = document.getElementById('ticketDesglose');
        if (ticketDesglose) ticketDesglose.innerHTML = '';
        totalEl.textContent = '0,00 €';
        btnCobrar.disabled = true;
        btnDescuento.disabled = true;
        btnPosponer.disabled = true;
        // Si se vacía el carrito, resetear los puntos canjeados también
        if (typeof puntosCanjeados !== 'undefined') puntosCanjeados = null;
        descuento = { tipo: 'ninguno', valor: 0, cupon: '' };
        return;
    }

    // Generar tabla HTML con las líneas del ticket
    let html = `<table class="ticket-tabla"><thead><tr><th>${t('cart.product_th')}</th><th>${t('cart.quantity_th')}</th><th>${t('cart.price_th')}</th><th>${t('cart.subtotal_th')}</th><th></th></tr></thead><tbody>`;

    // Iterar sobre cada producto del carrito
    carrito.forEach((item, i) => {
        const dec = item.decimales || 2;
        // Asegurar que pvpUnitario existe (fallback para items antiguos o corruptos)
        if (isNaN(item.pvpUnitario) || item.pvpUnitario === undefined) {
            const precio = parseFloat(item.precioConDescuento || item.precio || 0);
            const iva = parseInt(item.iva || 21);
            item.pvpUnitario = roundTo(precio * (1 + (iva / 100)), dec);
        }
        if (isNaN(item.pvpOriginalUnitario) || item.pvpOriginalUnitario === undefined) {
            const precio = parseFloat(item.precio || 0);
            const iva = parseInt(item.iva || 21);
            item.pvpOriginalUnitario = roundTo(precio * (1 + (iva / 100)), dec);
        }

        const subtotalRebajado = roundTo(item.pvpUnitario * item.cantidad, dec);

        html += `<tr>
            <td>
                ${item.nombre} 
                <small style="color: #666; display: block; font-size: 0.7rem;">
                    ${item.tarifaNombre !== 'Cliente' ? '<strong>' + item.tarifaNombre + '</strong> | ' : ''} IVA: ${item.iva}%
                </small>
            </td>
            <td>
                <div class="cantidad-control">
                    <button onclick="cambiarCantidad(${i}, ${item.cantidad - 1})">−</button>
                    <input type="number" value="${item.cantidad}" 
                        min="1" 
                        max="${item.stockMax}"
                        onchange="cambiarCantidad(${i}, Math.min(Math.max(1, parseInt(this.value) || 1), ${item.stockMax}))">
                    <button onclick="cambiarCantidad(${i}, ${item.cantidad + 1})">+</button>
                </div>
            </td>
            <td>${item.pvpUnitario.toFixed(dec).replace('.', ',')} €</td>
            <td>${subtotalRebajado.toFixed(dec).replace('.', ',')} €</td>
            <td><button class="btn-quitar" onclick="eliminarDelCarrito(${i})">✕</button></td>
        </tr>`;
    });

    html += '</tbody></table>';
    contenedor.innerHTML = html;

    // Calcular desglose de totales
    const precTotal = obtenerDecimalesMaximosCarrito();
    let totalPVPBruto = carrito.reduce((sum, item) => sum + roundTo(item.pvpUnitario * item.cantidad, precTotal), 0);
    let totalPVPFinal = obtenerTotalCalculado();
    let descuentoManualImporte = roundTo(totalPVPBruto - totalPVPFinal, precTotal);

    // Agrupar ahorros de tarifas
    const ahorrosTarifasAgrupados = {};
    carrito.forEach(item => {
        const dec = item.decimales || 2;
        const ahorroUnitario = roundTo(item.pvpOriginalUnitario - item.pvpUnitario, dec);
        const ahorroLinea = roundTo(ahorroUnitario * item.cantidad, dec);

        if (ahorroLinea > 0) {
            const nombre = item.tarifaNombre;
            if (!ahorrosTarifasAgrupados[nombre]) {
                ahorrosTarifasAgrupados[nombre] = 0;
            }
            ahorrosTarifasAgrupados[nombre] = roundTo(ahorrosTarifasAgrupados[nombre] + ahorroLinea, dec);
        }
    });

    const ahorroTarifasTotal = roundTo(Object.values(ahorrosTarifasAgrupados).reduce((a, b) => a + b, 0), precTotal);

    let htmlDesglose = `<div class="resumen-final-premium">`;

    // IVA Total: Recalculo exacto agrupando por tipo de IVA
    const desglosePorIVA = {};
    carrito.forEach(item => {
        const dec = item.decimales || 2;
        const subtotalLineaPVP = roundTo(item.pvpUnitario * item.cantidad, dec);
        const factorDescuentoManual = totalPVPBruto > 0 ? (totalPVPFinal / totalPVPBruto) : 0;
        const subtotalFinalPVP = subtotalLineaPVP * factorDescuentoManual;

        const tipoIVA = parseInt(item.iva);
        if (!desglosePorIVA[tipoIVA]) desglosePorIVA[tipoIVA] = 0;
        desglosePorIVA[tipoIVA] += subtotalFinalPVP;
    });

    let baseImponibleCalculada = 0;
    for (const [iva, pvpAcumulado] of Object.entries(desglosePorIVA)) {
        baseImponibleCalculada += roundTo(pvpAcumulado / (1 + (parseInt(iva) / 100)), precTotal);
    }

    baseImponibleCalculada = roundTo(baseImponibleCalculada, precTotal);
    const ivaTotal = roundTo(totalPVPFinal - baseImponibleCalculada, precTotal);

    // Mostrar Base Imponible (Total Final - IVA)
    htmlDesglose += `
    <div class="resumen-fila-mini">
        <span>${t('cart.tax_base')}:</span>
        <span>${baseImponibleCalculada.toFixed(precTotal).replace('.', ',')} €</span>
    </div>`;

    // Mostrar ahorros por tarifa si existen
    if (ahorroTarifasTotal > 0.005) {
        for (const [nombre, importe] of Object.entries(ahorrosTarifasAgrupados)) {
            htmlDesglose += `
            <div class="resumen-fila-mini descuento-texto">
                <span>${t('cart.saving')} ${nombre}:</span>
                <span>- ${importe.toFixed(precTotal).replace('.', ',')} €</span>
            </div>`;
        }
    }

    // Subtotal tras tarifas solo si hay cupón manual
    if (descuentoManualImporte > 0.005) {
        const textoManual = descuento.tipo === 'porcentaje' ? `${t('cart.discount')} (${descuento.valor}%)` : `${t('cart.coupon')} ` + (descuento.cupon || t('cart.manual'));
        htmlDesglose += `
        <div class="resumen-fila-mini descuento-texto" style="display: flex; justify-content: space-between; align-items: center;">
            <span style="color: #16a34a;">
                <span style="cursor: pointer; color: #ef4444; margin-right: 5px;" onclick="quitarDescuento()" title="${t('cart.remove_discount_title')}">
                    <i class="fas fa-times-circle"></i>
                </span>${textoManual}:
            </span>
            <span style="color: #16a34a;">- ${descuentoManualImporte.toFixed(precTotal).replace('.', ',')} €</span>
        </div>`;
    }

    htmlDesglose += `
        <div class="resumen-fila-mini">
            <span>${t('cart.tax')}:</span>
            <span>${ivaTotal.toFixed(precTotal).replace('.', ',')} €</span>
        </div>
        <div class="resumen-fila-mini" style="font-weight: bold; border-top: 1px solid #e5e7eb; padding-top: 8px;">
            <span>${t('cart.final_total')}:</span>
            <span>${totalPVPFinal.toFixed(precTotal).replace('.', ',')} €</span>
        </div>`;

    // Añadir puntos previstos a ganar si el cliente está identificado
    const nifActual = document.getElementById('clienteNif') ? document.getElementById('clienteNif').value.trim() : '';
    if (nifActual !== '' && totalPVPFinal > 0) {
        const puntosAGanar = Math.round(totalPVPFinal * 10);
        htmlDesglose += `
        <div class="resumen-fila-mini" style="color: #059669; font-weight: 600; font-size: 0.85rem; padding-top: 4px;">
            <span>${t('cart.points_to_earn')}:</span>
            <span>+${puntosAGanar.toLocaleString('es-ES')} pts</span>
        </div>`;
    }

    htmlDesglose += `</div>`;

    // Actualizar el DOM
    const ticketDesglose = document.getElementById('ticketDesglose');
    if (ticketDesglose) ticketDesglose.innerHTML = htmlDesglose;
    totalEl.textContent = totalPVPFinal.toFixed(precTotal).replace('.', ',') + ' €';

    // Verificar si se supera el límite de 1.000€ en efectivo
    if (typeof verificarLimiteEfectivo === 'function') {
        verificarLimiteEfectivo();
    }

    // Habilitar botones de cobro y descuento
    btnCobrar.disabled = false;
    btnDescuento.disabled = false;
    btnPosponer.disabled = false;
}

// ======================== PROCESO DE COBRO Y CAMBIO ========================

/**
 * Inicia el proceso de cobro. Si el método es efectivo, muestra el modal de cambio.
 * Si es tarjeta/bizum, va directamente al modal de tipo de documento.
 * Valida el límite legal de 1.000€ para pagos en efectivo.
 */
function intentarCobrar() {
    if (carrito.length === 0) return;
    const metodoPago = document.getElementById('metodoPago').value;

    if (metodoPago === 'efectivo') {
        // Verificar límite legal de efectivo (1.000€)
        const total = obtenerTotalCalculado();
        if (total > 1000) {
            alert(t('cart.alert_cash_limit_exceeded'));
            return;
        }
        // Mostrar modal para calcular el cambio
        mostrarModalCambio();
    } else if (metodoPago === 'mixto') {
        // Mostrar modal de pago mixto para distribuir entre métodos
        mostrarModalPagoMixto();
    } else {
        // Para tarjeta/bizum, ir directamente al tipo de documento
        if (typeof mostrarModalTipoDocumento === 'function') {
            mostrarModalTipoDocumento();
        }
    }
}

/**
 * Muestra el modal de cálculo de cambio para pago en efectivo.
 * Inicializa los valores y pone el foco en el input de dinero entregado.
 */
function mostrarModalCambio() {
    const total = obtenerTotalCalculado();
    const precTotal = obtenerDecimalesMaximosCarrito();
    const cambioTotalPagar = document.getElementById('cambioTotalPagar');
    if (cambioTotalPagar) cambioTotalPagar.textContent = total.toFixed(precTotal).replace('.', ',') + ' €';

    // Resetear campos del modal
    const inputEntregado = document.getElementById('inputDineroEntregado');
    if (inputEntregado) inputEntregado.value = '';

    const cambioDevolver = document.getElementById('cambioDevolver');
    if (cambioDevolver) {
        cambioDevolver.textContent = '0,00';
        cambioDevolver.style.color = 'var(--text-muted)';
    }

    const container = document.getElementById('cambioResultContainer');
    if (container) {
        container.style.background = 'var(--bg-main)';
        container.style.borderColor = 'transparent';
        container.style.transform = 'scale(1)';
    }

    const cambioError = document.getElementById('cambioError');
    if (cambioError) cambioError.style.display = 'none';

    // Mostrar modal y enfocar el input
    const modalCambio = document.getElementById('modalCambio');
    if (modalCambio) {
        modalCambio.style.display = 'flex';
        if (inputEntregado) setTimeout(() => inputEntregado.focus(), 100);
    }
}

/**
 * Establece el importe recibido igual al total de la venta actual (importe exacto).
 */
function fijarImporteExacto() {
    const total = obtenerTotalCalculado();
    const precTotal = obtenerDecimalesMaximosCarrito();
    const input = document.getElementById('inputDineroEntregado');
    if (input) {
        input.value = total.toFixed(precTotal);
        calcularCambio();
        // Foco al botón de continuar para agilizar
        setTimeout(() => {
            const btnContinuar = document.querySelector('#modalCambio .btn-exito');
            if (btnContinuar) btnContinuar.focus();
        }, 50);
    }
}

/**
 * Calcula en tiempo real el cambio a devolver según la cantidad entregada.
 * Muestra un mensaje de error si la cantidad es insuficiente.
 */
function calcularCambio() {
    const total = obtenerTotalCalculado();
    const precTotal = obtenerDecimalesMaximosCarrito();
    const inputEntregado = document.getElementById('inputDineroEntregado');
    const spanDevolver = document.getElementById('cambioDevolver');
    const errorMsg = document.getElementById('cambioError');
    const container = document.getElementById('cambioResultContainer');
    
    if (!inputEntregado || !spanDevolver || !errorMsg) return;

    const entregado = parseFloat(inputEntregado.value) || 0;
    const devolucion = entregado - total;
    const devolucionRedondeada = roundTo(devolucion, precTotal);

    if (devolucionRedondeada < 0 && entregado > 0) {
        // Cantidad insuficiente: mostrar error
        spanDevolver.textContent = '0,00';
        spanDevolver.style.color = 'var(--text-muted)';
        errorMsg.style.display = 'flex';
        if (container) {
            container.style.background = 'var(--bg-main)';
            container.style.borderColor = 'transparent';
            container.style.transform = 'scale(1)';
        }
    } else {
        // Cantidad suficiente o vacía: mostrar cambio
        errorMsg.style.display = 'none';
        if (entregado === 0) {
            spanDevolver.textContent = '0,00';
            spanDevolver.style.color = 'var(--text-muted)';
            if (container) {
                container.style.background = 'var(--bg-main)';
                container.style.borderColor = 'transparent';
                container.style.transform = 'scale(1)';
            }
        } else {
            spanDevolver.textContent = devolucionRedondeada.toFixed(precTotal).replace('.', ',');
            spanDevolver.style.color = 'var(--accent-success)';
            if (container) {
                container.style.background = 'var(--bg-accent-success)';
                container.style.borderColor = 'rgba(22, 163, 74, 0.2)';
                container.style.transform = 'scale(1.02)';
            }
        }
    }
}

/**
 * Valida que la cantidad entregada sea suficiente y avanza al modal de tipo de documento.
 */
function confirmarCambio() {
    const total = obtenerTotalCalculado();
    const inputEntregado = document.getElementById('inputDineroEntregado');
    if (!inputEntregado) return;

    const entregado = parseFloat(inputEntregado.value) || 0;

    // Validar que el entregado cubra el total (usando redondeo para evitar errores de precisión)
    if (Math.round(entregado * 100) < Math.round(total * 100)) {
        const cambioError = document.getElementById('cambioError');
        if (cambioError) cambioError.style.display = 'block';
        return;
    }

    // Cerrar modal de cambio y abrir el nuevo Centro de Finalización de Venta
    cerrarModal('modalCambio');
    if (typeof abrirModalFinalizarVenta === 'function') {
        abrirModalFinalizarVenta();
    }
}

// ======================== PAGO MIXTO ========================

/**
 * Inicializa y muestra el modal para distribuir el pago entre múltiples métodos.
 */
function mostrarModalPagoMixto() {
    const total = obtenerTotalCalculado();
    const precTotal = obtenerDecimalesMaximosCarrito();
    const mixtoTotalDistribuir = document.getElementById('mixtoTotalDistribuir');
    if (mixtoTotalDistribuir) mixtoTotalDistribuir.textContent = total.toFixed(precTotal).replace('.', ',') + ' €';

    // Resetear campos
    const inputs = ['mixtoEfectivo', 'mixtoTarjeta', 'mixtoBizum'];
    inputs.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = '';
    });

    const mixtoError = document.getElementById('mixtoError');
    if (mixtoError) mixtoError.style.display = 'none';
    const mixtoAvisoEfectivo = document.getElementById('mixtoAvisoEfectivo');
    if (mixtoAvisoEfectivo) mixtoAvisoEfectivo.style.display = 'none';

    // Resetear indicador restante
    const restanteValor = document.getElementById('mixtoRestanteValor');
    if (restanteValor) restanteValor.textContent = total.toFixed(precTotal).replace('.', ',') + ' €';

    const container = document.getElementById('mixtoRestanteContainer');
    const label = document.getElementById('mixtoRestanteLabel');
    const sub = document.getElementById('mixtoRestanteSub');

    if (container && label && sub && restanteValor) {
        container.style.background = 'var(--bg-accent-danger)';
        label.style.color = 'var(--accent-danger)';
        sub.style.color = 'var(--accent-danger)';
        restanteValor.style.color = 'var(--accent-danger)';
        label.textContent = t('cart.mixed_remaining_assign');
        sub.textContent = t('cart.mixed_distribute_full');
    }

    // Mostrar modal y enfocar primer campo
    const modalPagoMixto = document.getElementById('modalPagoMixto');
    if (modalPagoMixto) {
        modalPagoMixto.style.display = 'flex';
        const mixtoEfectivo = document.getElementById('mixtoEfectivo');
        if (mixtoEfectivo) setTimeout(() => mixtoEfectivo.focus(), 100);
    }
}

/**
 * Calcula en tiempo real cuánto queda por asignar y actualiza el indicador visual.
 */
function calcularRestanteMixto() {
    const total = obtenerTotalCalculado();
    const precTotal = obtenerDecimalesMaximosCarrito();
    const efectivo = parseFloat(document.getElementById('mixtoEfectivo')?.value) || 0;
    const tarjeta = parseFloat(document.getElementById('mixtoTarjeta')?.value) || 0;
    const bizum = parseFloat(document.getElementById('mixtoBizum')?.value) || 0;

    const asignado = roundTo(efectivo + tarjeta + bizum, precTotal);
    const restante = roundTo(total - asignado, precTotal);

    const restanteValor = document.getElementById('mixtoRestanteValor');
    const container = document.getElementById('mixtoRestanteContainer');
    const label = document.getElementById('mixtoRestanteLabel');
    const sub = document.getElementById('mixtoRestanteSub');
    const errorEl = document.getElementById('mixtoError');

    if (!restanteValor || !container || !label || !sub || !errorEl) return;

    // Aviso límite efectivo
    const avisoEfectivo = document.getElementById('mixtoAvisoEfectivo');
    if (avisoEfectivo) avisoEfectivo.style.display = (efectivo > 1000) ? 'block' : 'none';

    if (restante > 0.005) {
        // Falta por asignar
        container.style.background = 'var(--bg-accent-danger)';
        container.style.borderColor = 'rgba(220, 38, 38, 0.1)';
        container.style.transform = 'scale(1)';
        label.style.color = 'var(--accent-danger)';
        sub.style.color = 'var(--accent-danger)';
        restanteValor.style.color = 'var(--accent-danger)';
        label.textContent = t('mixed_modal.remaining') || 'RESTANTE';
        sub.textContent = t('mixed_modal.distribute_total') || 'Distribuye el total';
        restanteValor.textContent = restante.toFixed(precTotal).replace('.', ',');
        errorEl.style.display = 'none';
    } else if (restante < -0.005) {
        // Excedente (cambio)
        const cambio = Math.abs(restante);
        container.style.background = 'var(--bg-accent-success)';
        container.style.borderColor = 'rgba(22, 163, 74, 0.2)';
        container.style.transform = 'scale(1.02)';
        label.style.color = 'var(--accent-success)';
        sub.style.color = 'var(--accent-success)';
        restanteValor.style.color = 'var(--accent-success)';
        label.textContent = t('cash_modal.change') || 'CAMBIO';
        sub.textContent = t('cash_modal.for_client') || 'Para el cliente';
        restanteValor.textContent = cambio.toFixed(precTotal).replace('.', ',');
        errorEl.style.display = 'none';
    } else {
        // Exacto
        container.style.background = 'var(--bg-accent-success)';
        container.style.borderColor = 'rgba(22, 163, 74, 0.4)';
        container.style.transform = 'scale(1.05)';
        label.style.color = 'var(--accent-success)';
        sub.style.color = 'var(--accent-success)';
        restanteValor.style.color = 'var(--accent-success)';
        label.textContent = '✓ ' + (t('mixed_modal.confirm_distribution') || 'CUBIERTO');
        sub.textContent = 'DISTRIBUCIÓN CORRECTA';
        restanteValor.textContent = '0,00';
        errorEl.style.display = 'none';
    }

    // Gestión dinámica de botones de autocompletar
    const fillButtons = {
        'mixtoEfectivo': document.getElementById('btnFillMixtoEfectivo'),
        'mixtoTarjeta': document.getElementById('btnFillMixtoTarjeta'),
        'mixtoBizum': document.getElementById('btnFillMixtoBizum')
    };

    Object.keys(fillButtons).forEach(id => {
        const btn = fillButtons[id];
        const input = document.getElementById(id);
        if (btn && input) {
            const valInput = parseFloat(input.value) || 0;
            // Solo mostrar si hay restante real y el input está vacío (o es 0)
            if (restante > 0.005 && valInput < 0.005) {
                btn.style.display = 'block';
                btn.textContent = '+ ' + restante.toFixed(precTotal).replace('.', ',') + ' €';
            } else {
                btn.style.display = 'none';
            }
        }
    });
}

/**
 * Calcula el importe faltante para cubrir el total de la venta y lo asigna al input especificado.
 * @param {string} targetId ID del elemento input al que se asignará el restante.
 */
function fijarRestanteMixto(targetId) {
    const total = obtenerTotalCalculado();
    const precTotal = obtenerDecimalesMaximosCarrito();
    
    // IDs de los métodos de pago mixto
    const ids = ['mixtoEfectivo', 'mixtoTarjeta', 'mixtoBizum'];
    
    // Calcular cuánto se ha asignado ya en los OTROS inputs
    let asignadoEnOtros = 0;
    ids.forEach(id => {
        if (id !== targetId) {
            const val = parseFloat(document.getElementById(id)?.value) || 0;
            asignadoEnOtros += val;
        }
    });
    
    // El restante para este input es (Total - lo que hay en los otros)
    const restante = Math.max(0, roundTo(total - asignadoEnOtros, precTotal));
    
    const input = document.getElementById(targetId);
    if (input) {
        input.value = restante > 0 ? restante.toFixed(precTotal) : '';
        calcularRestanteMixto();
        
        // Focus para feedback visual
        input.focus();
        
        // Si el restante cubre exactamente lo que faltaba, dar foco al botón confirmar
        const sumaFinal = roundTo(asignadoEnOtros + restante, precTotal);
        if (Math.abs(sumaFinal - total) < 0.005) {
            setTimeout(() => {
                document.getElementById('btnConfirmarPagoMixto')?.focus();
            }, 100);
        }
    }
}

/**
 * Valida la distribución y avanza al modal de finalización de venta.
 */
function confirmarPagoMixto() {
    const total = obtenerTotalCalculado();
    const precTotal = obtenerDecimalesMaximosCarrito();
    const efectivo = parseFloat(document.getElementById('mixtoEfectivo')?.value) || 0;
    const tarjeta = parseFloat(document.getElementById('mixtoTarjeta')?.value) || 0;
    const bizum = parseFloat(document.getElementById('mixtoBizum')?.value) || 0;
    const errorEl = document.getElementById('mixtoError');

    if (!errorEl) return;

    const asignado = roundTo(efectivo + tarjeta + bizum, precTotal);

    // Validar que la suma cubra el total
    if (Math.round(asignado * 100) < Math.round(total * 100)) {
        const spanError = document.getElementById('mixtoErrorSpan');
        if (spanError) spanError.textContent = (t('cart.mixed_error_not_covered') || 'Falta cubrir') + ': ' + roundTo(total - asignado, precTotal).toFixed(precTotal).replace('.', ',') + ' €';
        errorEl.style.display = 'flex';
        return;
    }

    // Validar límite de efectivo
    if (efectivo > 1000) {
        const spanError = document.getElementById('mixtoErrorSpan');
        if (spanError) spanError.textContent = t('cart.mixed_error_cash_limit') || 'Límite efectivo superado';
        errorEl.style.display = 'flex';
        return;
    }

    // Validar que al menos 2 métodos tengan importe (sino no tiene sentido "mixto")
    const metodosUsados = [efectivo, tarjeta, bizum].filter(v => v > 0).length;
    if (metodosUsados < 2) {
        const spanError = document.getElementById('mixtoErrorSpan');
        if (spanError) spanError.textContent = t('cart.mixed_error_two_methods') || 'Usa al menos 2 métodos';
        errorEl.style.display = 'flex';
        return;
    }

    // Calcular cambio (solo posible si hay efectivo y el asignado > total)
    const cambio = roundTo(Math.max(0, asignado - total), precTotal);

    // Guardar desglose
    pagoMixtoDesglose = {
        efectivo: roundTo(efectivo, precTotal),
        tarjeta: roundTo(tarjeta, precTotal),
        bizum: roundTo(bizum, precTotal),
        cambio: cambio
    };

    // Cerrar modal mixto y abrir el modal de finalización
    cerrarModal('modalPagoMixto');
    if (typeof abrirModalFinalizarVenta === 'function') {
        abrirModalFinalizarVenta();
    }
}

// ======================== MODAL TIPO DOCUMENTO / CLIENTE ========================

/**
 * Muestra el modal para elegir entre Ticket o Factura.
 * Valida que haya productos en el carrito y que la caja esté abierta.
 * Si el total es >= 20€ y no hay cliente, pregunta por los puntos primero.
 */
function mostrarModalTipoDocumento() {
    if (carrito.length === 0) return;

    // Verificar que la caja esté abierta antes de permitir ventas
    if (!cajaAbierta) {
        alert(t('cart.alert_box_closed'));
        return;
    }

    // Verificar si el total es mayor a 20€ y no hay cliente registrado para preguntar por puntos
    const total = obtenerTotalCalculado();
    const clienteNifEl = document.getElementById('clienteNif');
    const clienteNif = clienteNifEl ? clienteNifEl.value.trim() : '';

    if (total >= 20 && !clienteNif) {
        // Mostrar modal de puntos antes del tipo de documento
        if (typeof mostrarModalPuntos === 'function') {
            mostrarModalPuntos();
        }
        return;
    }

    abrirModalFinalizarVenta();
}

// ======================== FLUJO DE FINALIZAR VENTA ========================

/**
 * Cambia el idioma seleccionado para el ticket, actualiza la selección visual 
 * y regenera la vista previa en el nuevo idioma
 */
function cambiarIdiomaTicket(idioma) {
    // Actualizar variable global
    idiomaTicketSeleccionado = idioma;

    // Actualizar variable de traducciones si existe IDIOMAS_TICKET
    if (typeof IDIOMAS_TICKET !== 'undefined' && IDIOMAS_TICKET[idioma]) {
        LANG = IDIOMAS_TICKET[idioma];
    }

    // Actualizar estado visual de los botones
    document.querySelectorAll('.idioma-option-card').forEach(el => {
        el.classList.remove('active');
    });
    const selectedBtn = document.querySelector(`.idioma-option-card[data-idioma="${idioma}"]`);
    if (selectedBtn) selectedBtn.classList.add('active');

    // Actualizar campo oculto del formulario
    const inputIdiomaTicket = document.getElementById('inputIdiomaTicket');
    if (inputIdiomaTicket) inputIdiomaTicket.value = idioma;

    // ACTUALIZAR NOMBRES DE TODOS LOS PRODUCTOS EN EL CARRITO
    if (typeof carrito !== 'undefined' && carrito.length > 0) {
        carrito.forEach(item => {
            // Buscar el nombre correspondiente al idioma seleccionado
            const campoNombre = `nombre_${idioma}`;
            if (item[campoNombre] && item[campoNombre].trim() !== '') {
                item.nombre = item[campoNombre];
            } else {
                // Fallback: si no tiene traduccion usar nombre español, si tampoco nombre base
                if (item.nombre_es && item.nombre_es.trim() !== '') {
                    item.nombre = item.nombre_es;
                }
            }
        });

        // Volver a renderizar el carrito en el panel derecho
        actualizarTicket();
    }

    // Regenerar vista previa del ticket con el nuevo idioma
    renderizarVistaPreviaTicket();
}

/**
 * Inicializa y muestra el nuevo modal de finalización con vista previa.
 */
function abrirModalFinalizarVenta() {
    if (carrito.length === 0) return;

    // Resetear selecciones
    tipoDocumentoActual = 'ticket';
    metodoEntregaActual = 'imprimir';
    idiomaTicketSeleccionado = 'es';

    // Resetear selección de idioma a Español por defecto
    document.querySelectorAll('.idioma-option-card').forEach(el => {
        el.classList.remove('active');
    });
    const esBtn = document.querySelector('.idioma-option-card[data-idioma="es"]');
    if (esBtn) esBtn.classList.add('active');

    const inputIdiomaTicket = document.getElementById('inputIdiomaTicket');
    if (inputIdiomaTicket) inputIdiomaTicket.value = 'es';

    // Fetch de los próximos números para la vista previa
    fetch('api/ventas.php?accion=proximos_numeros')
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                proximosNumeros.ticket = data.proximo_ticket;
                proximosNumeros.factura = data.proximo_factura;
                renderizarVistaPreviaTicket();
            }
        })
        .catch(err => console.error('Error al obtener números correlativos:', err));

    // Actualizar UI de botones
    document.querySelectorAll('.checkout-option-card').forEach(c => c.classList.remove('active'));
    const optTicket = document.getElementById('optTicket');
    const optImprimir = document.getElementById('optImprimir');
    if (optTicket) optTicket.classList.add('active');
    if (optImprimir) optImprimir.classList.add('active');

    const emailContainerCheckout = document.getElementById('emailContainerCheckout');
    if (emailContainerCheckout) emailContainerCheckout.style.display = 'none';

    // Si no hay datos de cliente, asegurar que no se acumulen puntos
    const nifValRes = document.getElementById('clienteNif')?.value.trim() || '';
    const nomValRes = document.getElementById('clienteNombre')?.value.trim() || '';
    if (!nifValRes && !nomValRes) {
        clienteIdentificadoEnModalPuntos = false;
    }

    // Actualizar resumen de cliente
    actualizarResumenClienteCheckout();

    // Renderizar vista previa inicial
    renderizarVistaPreviaTicket();

    // Mostrar modal principal
    const modalFinalizarVenta = document.getElementById('modalFinalizarVenta');
    if (modalFinalizarVenta) modalFinalizarVenta.style.display = 'flex';
}

/**
 * Actualiza el pequeño recuadro de datos de cliente en el modal de checkout.
 */
function actualizarResumenClienteCheckout() {
    const nifEl = document.getElementById('clienteNif');
    const nombreEl = document.getElementById('clienteNombre');
    const textEl = document.getElementById('clientDataTextCheckout');
    const btnRemove = document.getElementById('btnRemoveClientCheckout');

    if (!textEl) return;

    const nif = nifEl ? nifEl.value.trim() : '';
    const nombre = nombreEl ? nombreEl.value.trim() : '';

    if (nif || nombre) {
        textEl.innerHTML = `<div style="color:var(--text-main); font-weight:600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${nombre || t('cart.no_name')}</div><div style="font-size:0.8rem; opacity: 0.7;">${nif || t('cart.no_nif')}</div>`;
        if (btnRemove) btnRemove.style.display = 'block';
    } else {
        textEl.textContent = t('cart.no_customer_assigned');
        if (btnRemove) btnRemove.style.display = 'none';
    }
}

/**
 * Borra los datos del receptor (cliente) y resetea puntos.
 */
function quitarClienteFinalizar() {
    // 1. Limpiar campos de datos del cliente
    const campos = ['clienteNif', 'clienteNombre', 'clienteDireccion', 'clienteNotas', 'inputEmailFinal'];
    campos.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = '';
    });

    // 2. Limpiar puntos si los había
    const pDni = document.getElementById('inputPuntosCanjeadosDni');
    const pCant = document.getElementById('inputPuntosCanjeadosCantidad');
    const pTicket = document.getElementById('inputPuntosCanjeadosTicket');
    
    if (pDni) pDni.value = '';
    if (pCant) pCant.value = '0';
    if (pTicket) pTicket.value = '0';
    
    clienteIdentificadoEnModalPuntos = false;

    // 3. Actualizar UI
    actualizarResumenClienteCheckout();
    
    // 4. Regenerar vista previa del ticket (para que desaparezcan los datos)
    renderizarVistaPreviaTicket();
}

/**
 * Abre el modal de datos de cliente y prepara el retorno al checkout al terminar.
 */
function abrirDatosClienteDesdeCheckout() {
    cerrarModal('modalFinalizarVenta');
    window.retornarAlCheckout = true; // Flag para volver aquí después
    if (typeof seleccionarDatosCliente === 'function') {
        seleccionarDatosCliente(tipoDocumentoActual);
    }
}

/**
 * Maneja el botón 'Atrás' en el modal de datos de cliente.
 */
function cerrarModalDatosClienteAtras() {
    cerrarModal('modalDatosCliente');
    if (window.retornarAlCheckout) {
        window.retornarAlCheckout = false;
        const modalFinalizarVenta = document.getElementById('modalFinalizarVenta');
        if (modalFinalizarVenta) modalFinalizarVenta.style.display = 'flex';
    } else {
        // Flujo normal previo
        const modalTipoDoc = document.getElementById('modalTipoDoc');
        if (modalTipoDoc) modalTipoDoc.style.display = 'flex';
    }
}

/**
 * Cambia entre 'ticket' y 'factura' en el modo checkout.
 */
function cambiarTipoDocumentoCheckout(tipo) {
    tipoDocumentoActual = tipo;

    // Actualizar botones
    const optTicket = document.getElementById('optTicket');
    const optFactura = document.getElementById('optFactura');
    if (optTicket) optTicket.classList.toggle('active', tipo === 'ticket');
    if (optFactura) optFactura.classList.toggle('active', tipo === 'factura');

    // Actualizar la vista previa
    renderizarVistaPreviaTicket();
}

/**
 * Cambia entre 'imprimir' y 'email'.
 */
function cambiarMetodoEntregaCheckout(metodo) {
    metodoEntregaActual = metodo;

    // Actualizar botones
    const optImprimir = document.getElementById('optImprimir');
    const optEmail = document.getElementById('optEmail');
    if (optImprimir) optImprimir.classList.toggle('active', metodo === 'imprimir');
    if (optEmail) optEmail.classList.toggle('active', metodo === 'email');

    // Mostrar/ocultar contenedores específicos
    const emailContainerCheckout = document.getElementById('emailContainerCheckout');
    if (emailContainerCheckout) emailContainerCheckout.style.display = (metodo === 'email') ? 'block' : 'none';

    if (metodo === 'email') {
        const emailCheckout = document.getElementById('emailCheckout');
        if (emailCheckout) emailCheckout.focus();
    }
}

/**
 * Crea un objeto con la estructura de 'ultimaVenta' a partir de los datos actuales del carrito
 * y el formulario de cliente para poder previsualizar el documento fielmente.
 */
function construirObjetoVentaTemporal(tipoDoc) {
    const totalPVP = obtenerTotalCalculado();
    const nif = document.getElementById('clienteNif')?.value.trim() || '';
    const nombre = document.getElementById('clienteNombre')?.value.trim() || '';
    const direccion = document.getElementById('clienteDireccion')?.value.trim() || '';
    const observaciones = document.getElementById('clienteObservaciones')?.value.trim() || '';
    const mensajePersonalizado = document.getElementById('mensajePersonalizadoVenta')?.value.trim() || '';
    const idiomaTicket = idiomaTicketSeleccionado;

    // Clonar y preparar líneas de carrito
    const lineas = carrito.map(item => {
        // Obtener nombre en el idioma seleccionado actualmente
        const campoNombre = `nombre_${idiomaTicketSeleccionado}`;
        let nombreFinal = item.nombre;

        if (item[campoNombre] && item[campoNombre].trim() !== '') {
            nombreFinal = item[campoNombre];
        } else if (item.nombre_es && item.nombre_es.trim() !== '') {
            nombreFinal = item.nombre_es;
        }

        return {
            ...item,
            nombre: nombreFinal,
            precio: parseFloat(item.precio),
            pvpUnitario: parseFloat(item.pvpUnitario),
            cantidad: parseFloat(item.cantidad),
            iva: (item.iva !== undefined && item.iva !== null && item.iva !== "") ? parseInt(item.iva) : 21
        };
    });

    const metodoPagoActual = document.getElementById('metodoPago')?.value || 'efectivo';
    let entregadoVal = totalPVP;
    let cambioVal = 0;

    if (metodoPagoActual === 'efectivo') {
        const inputVal = parseFloat(document.getElementById('inputDineroEntregado')?.value);
        if (!isNaN(inputVal) && inputVal >= totalPVP) {
            entregadoVal = inputVal;
            cambioVal = inputVal - totalPVP;
        }
    }

    return {
        id: proximosNumeros[tipoDoc],
        numero: proximosNumeros[tipoDoc].replace(/\D/g, ''),
        serie: proximosNumeros[tipoDoc].replace(/\d/g, ''),
        fecha: new Date().toLocaleDateString('es-ES') + ' ' + new Date().toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' }),
        tipo: tipoDoc,
        idioma_ticket: idiomaTicket,
        metodoPago: metodoPagoActual,
        entregado: entregadoVal,
        cambio: cambioVal,
        carrito: lineas,
        total: totalPVP,
        clienteNif: nif,
        clienteNombre: nombre,
        clienteDir: direccion,
        clienteObs: observaciones,
        esClienteRegistrado: clienteIdentificadoEnModalPuntos,
        clientePuntos: parseInt(document.getElementById('clientePuntos')?.value) || 0,
        descuentoTipo: descuento.tipo,
        descuentoValor: descuento.valor,
        descuentoCupon: descuento.cupon,
        puntosGanados: (clienteIdentificadoEnModalPuntos && totalPVP >= 20) ? Math.round(totalPVP * 10) : 0,
        puntosCanjeados: (typeof puntosCanjeados !== 'undefined' && puntosCanjeados && puntosCanjeados.puntos > 0) ? puntosCanjeados : null,
        mensajePersonalizado: mensajePersonalizado,
        pagoMixtoDesglose: (metodoPagoActual === 'mixto') ? pagoMixtoDesglose : null,
        qrUrl: (TPV_CONTEXT.config.qrBaseUrl || 'https://prewww2.aeat.es/wlpl/TIKE-CONT/ValidarQR') + '?' +
            (new URLSearchParams({
                nif: TPV_CONTEXT.config.nif,
                numserie: (proximosNumeros[tipoDoc] || '').replace(/\s+/g, ''),
                fecha: (() => {
                    const d = new Date();
                    return String(d.getDate()).padStart(2, '0') + '-' +
                        String(d.getMonth() + 1).padStart(2, '0') + '-' +
                        d.getFullYear();
                })(),
                importe: totalPVP.toFixed(2)
            })).toString()
    };
}

/**
 * Limpia la sesion y vuelve al estado inicial.
 */
function cerrarExito() {
    window.location.href = 'index.php?v=cajero';
}

/**
 * Alterna entre vista ajustada y vista real (con scroll)
 */
function toggleZoomTicket() {
    ticketZoomed = !ticketZoomed;
    const previewContainer = document.getElementById('ticketPreviewContent');
    if (previewContainer) {
        ajustarEscalaTicket(previewContainer, false);
    }
}

/**
 * Calcula y aplica el factor de escala o habilita el scroll.
 * @param {HTMLElement} previewContainer - El contenedor del ticket.
 * @param {boolean} esNuevaMedicion - Indica si hay que recalcular si el ticket es grande.
 */
function ajustarEscalaTicket(previewContainer, esNuevaMedicion = false) {
    const viewport = document.querySelector('.ticket-preview-viewport');
    const btnZoom = document.getElementById('btnZoomTicket');
    const iconMinus = document.querySelector('.icon-minus');
    const iconPlusElements = document.querySelectorAll('.icon-plus');

    if (!viewport || !previewContainer || !btnZoom) return;

    // Si es una nueva medición, reseteamos estilos para medir altura real al 100%
    if (esNuevaMedicion) {
        viewport.classList.remove('is-zoomed');
        previewContainer.style.transform = 'none';
        // Forzamos un pequeño reflow si fuese necesario, aunque offsetHeight ya lo hace
        const viewportHeight = viewport.clientHeight - 32;
        const ticketFullHeight = previewContainer.offsetHeight;
        ticketEsGrandeLocal = ticketFullHeight > viewportHeight;
    }

    // Si el ticket es grande para el viewport actual
    if (ticketEsGrandeLocal) {
        btnZoom.style.display = 'flex';

        if (ticketZoomed) {
            // MODO ZOOM: Tamaño real con scroll
            viewport.classList.add('is-zoomed');
            btnZoom.classList.add('active');
            if (iconMinus) iconMinus.style.display = 'block';
            iconPlusElements.forEach(el => el.style.display = 'none');
            previewContainer.style.transform = 'none'; // Asegurar tamaño real
        } else {
            // MODO AJUSTADO: Escalado para que quepa totalmente
            viewport.classList.remove('is-zoomed');
            btnZoom.classList.remove('active');
            if (iconMinus) iconMinus.style.display = 'none';
            iconPlusElements.forEach(el => el.style.display = 'block');

            // Recalculamos escala basada en altura real
            const viewportHeight = viewport.clientHeight - 32;
            const ticketFullHeight = previewContainer.offsetHeight;

            if (ticketFullHeight > 0) {
                const scale = viewportHeight / ticketFullHeight;
                previewContainer.style.transform = `scale(${scale})`;
                previewContainer.style.transformOrigin = 'top center';
            }
        }
    } else {
        // El ticket cabe perfectamente: ocultamos botón y reset de estados
        btnZoom.style.display = 'none';
        viewport.classList.remove('is-zoomed');
        previewContainer.style.transform = 'none';
        ticketZoomed = false;
    }
}

/**
 * Actualiza la previsualización del modal de cobro usando un iframe para exactitud 1:1.
 */
function renderizarVistaPreviaTicket() {
    const previewContainer = document.getElementById('ticketPreviewContent');
    const badge = document.getElementById('tipoDocBadgeCheckout');
    if (!previewContainer || !badge) return;

    const isFactura = (tipoDocumentoActual === 'factura');

    // Limpieza de estados y timers previos para evitar condiciones de carrera
    ticketZoomed = false;
    ticketEsGrandeLocal = false;
    if (timeoutEscalaTicket) {
        clearTimeout(timeoutEscalaTicket);
        timeoutEscalaTicket = null;
    }

    const datosMock = construirObjetoVentaTemporal(tipoDocumentoActual);
    if (typeof generarHTMLComprobante !== 'function') return;
    const fullHTML = generarHTMLComprobante(datosMock, idiomaTicketSeleccionado);

    previewContainer.className = 'paper-simulation ' + (isFactura ? 'tipo-factura' : 'tipo-ticket');
    badge.textContent = isFactura ? 'FACTURA A4' : t('cart.thermal_ticket');
    badge.style.background = isFactura ? 'var(--bg-accent)' : 'var(--bg-accent-success)';
    badge.style.color = isFactura ? 'var(--accent)' : 'var(--accent-success)';

    previewContainer.innerHTML = '';
    const iframe = document.createElement('iframe');
    iframe.style.width = '100%';
    iframe.style.height = '1px';
    iframe.style.border = 'none';
    iframe.style.background = 'white';
    iframe.scrolling = 'no';
    previewContainer.appendChild(iframe);

    const doc = iframe.contentWindow ? iframe.contentWindow.document : null;
    if (doc) {
        doc.open();
        doc.write(fullHTML);
        doc.close();
    }

    iframe.onload = function () {
        if (timeoutEscalaTicket) clearTimeout(timeoutEscalaTicket);

        timeoutEscalaTicket = setTimeout(() => {
            // Comprobación defensiva antes de acceder al iframe
            if (!iframe || !iframe.contentWindow || !iframe.contentWindow.document) return;

            const body = iframe.contentWindow.document.body;
            if (!body) return;

            iframe.style.height = body.scrollHeight + 'px';

            // Aplicamos el ajuste de escala/zoom con medición nueva
            ajustarEscalaTicket(previewContainer, true);
        }, 180);
    };

    const totalEl = document.getElementById('checkoutTotalAmount');
    if (totalEl) totalEl.textContent = datosMock.total.toFixed(2).replace('.', ',') + ' €';
}

/**
 * Realiza las comprobaciones finales y dispara el envío de la venta.
 */
function procesarVentaFinal() {
    const mensajePersonalizadoVenta = document.getElementById('mensajePersonalizadoVenta');
    const mensajePersonalizado = mensajePersonalizadoVenta ? mensajePersonalizadoVenta.value.trim() : '';
    const inputMensajePersonalizado = document.getElementById('inputMensajePersonalizado');
    if (inputMensajePersonalizado) inputMensajePersonalizado.value = mensajePersonalizado;

    const nifEl = document.getElementById('clienteNif');
    const nombreEl = document.getElementById('clienteNombre');
    const direccionEl = document.getElementById('clienteDireccion');

    const nif = nifEl ? nifEl.value.trim() : '';
    const nombre = nombreEl ? nombreEl.value.trim() : '';
    const direccion = direccionEl ? direccionEl.value.trim() : '';

    // 1. Validar Factura
    if (tipoDocumentoActual === 'factura') {
        if (!nif || !nombre || !direccion) {
            // Si faltan datos, redirigir al modal de datos del cliente
            // Marcamos flag para que al terminar vuelva al checkout
            window.retornarAlCheckout = true;
            cerrarModal('modalFinalizarVenta');
            if (typeof seleccionarDatosCliente === 'function') {
                seleccionarDatosCliente('factura');
            }
            return;
        }
    }

    // 2. Validar Email si está seleccionado
    if (metodoEntregaActual === 'email') {
        const emailCheckout = document.getElementById('emailCheckout');
        const email = emailCheckout ? emailCheckout.value.trim() : '';
        if (!email || !email.includes('@')) {
            alert(t('cart.alert_valid_email'));
            return;
        }
        // Sincronizar con el input global por si se usa después
        const inputEmailGlobal = document.getElementById('inputEmail');
        if (inputEmailGlobal) inputEmailGlobal.value = email;
    }

    // 3. Sincronizar preferencias en localStorage para persistir tras el reload
    const emailCheckout = document.getElementById('emailCheckout');
    localStorage.setItem('tpv_post_sale_action', JSON.stringify({
        imprimir: (metodoEntregaActual === 'imprimir'),
        email: (metodoEntregaActual === 'email'),
        emailDestino: emailCheckout ? emailCheckout.value.trim() : ''
    }));

    // 4. Proceder con el registro
    const observacionesEl = document.getElementById('clienteObservaciones');
    const observaciones = observacionesEl ? observacionesEl.value.trim() : '';

    cerrarModal('modalFinalizarVenta');
    confirmarVenta(tipoDocumentoActual, nif, nombre, direccion, observaciones, mensajePersonalizado);
}

/**
 * Buscar cliente por DNI en el checkout
 */
function buscarDatosCliente() {
    const buscarDniCliente = document.getElementById('buscarDniCliente');
    const dniBusqueda = buscarDniCliente ? buscarDniCliente.value.trim() : '';
    const msgEl = document.getElementById('mensajeBusquedaClienteDatos');

    if (!dniBusqueda) {
        if (msgEl) {
            msgEl.style.display = 'block';
            msgEl.style.color = '#ef4444';
            msgEl.textContent = t('cart.alert_valid_dni');
        }
        return;
    }

    if (msgEl) {
        msgEl.style.display = 'block';
        msgEl.style.color = '#3b82f6';
        msgEl.textContent = t('cart.searching');
    }

    fetch('api/clientes.php?dni=' + encodeURIComponent(dniBusqueda))
        .then(res => res.json())
        .then(data => {
            if (data && !data.error && data.length > 0) {
                const cliente = data.find(c => c.dni.toUpperCase() === dniBusqueda.toUpperCase()) || data[0];

                const fields = {
                    'clienteNif': cliente.dni,
                    'clienteNombre': (cliente.nombre + ' ' + (cliente.apellidos || '')).trim(),
                    'clienteDireccion': cliente.direccion || '',
                    'clientePuntos': cliente.puntos || 0
                };

                for (const [id, val] of Object.entries(fields)) {
                    const el = document.getElementById(id);
                    if (el) el.value = val;
                }

                if (msgEl) {
                    msgEl.style.color = '#10b981';
                    msgEl.textContent = t('cart.client_found_filled');
                }
                clienteIdentificadoEnModalPuntos = true;
                renderizarVistaPreviaTicket();
            } else {
                clienteIdentificadoEnModalPuntos = false;
                renderizarVistaPreviaTicket();
                if (msgEl) msgEl.style.display = 'none';
                if (confirm(t('cart.confirm_add_client'))) {
                    const clienteHabitualDni = document.getElementById('clienteHabitualDni');
                    if (clienteHabitualDni) clienteHabitualDni.value = dniBusqueda;
                    if (typeof abrirModalClienteHabitual === 'function') {
                        abrirModalClienteHabitual();
                    }
                }
            }
        })
        .catch(err => {
            console.error('Error buscando cliente:', err);
            clienteIdentificadoEnModalPuntos = false;
            renderizarVistaPreviaTicket();
            if (msgEl) {
                msgEl.style.display = 'block';
                msgEl.style.color = '#ef4444';
                msgEl.textContent = t('cart.error_searching_client');
            }
        });
}

/**
 * Configura y muestra el modal de datos del cliente según el tipo de documento.
 */
function seleccionarDatosCliente(tipo) {
    tipoDocumentoActual = tipo;
    cerrarModal('modalTipoDoc');

    // Limpiar errores previos
    const errorDatosCliente = document.getElementById('errorDatosCliente');
    if (errorDatosCliente) errorDatosCliente.style.display = 'none';

    // Obtener referencias a los elementos del formulario
    const divDir = document.getElementById('divDireccionCliente');
    const divObs = document.getElementById('divObservacionesCliente');
    const subTitulo = document.getElementById('subtituloDatosCliente');

    const reqs = ['reqNif', 'reqNombre', 'reqDir'];

    if (tipo === 'factura') {
        // Modo Factura: mostrar todos los campos y marcar obligatorios
        if (subTitulo) subTitulo.textContent = t('cart.complete_data_mandatory');
        if (divDir) divDir.style.display = 'block';
        if (divObs) divObs.style.display = 'block';
        reqs.forEach(id => {
            const el = document.getElementById(id);
            if (el) el.style.display = 'inline';
        });
    } else {
        // Modo Ticket: ocultar campos extra
        if (subTitulo) subTitulo.textContent = t('cart.complete_data_optional');
        if (divDir) divDir.style.display = 'none';
        if (divObs) divObs.style.display = 'none';
        reqs.forEach(id => {
            const el = document.getElementById(id);
            if (el) el.style.display = 'none';
        });
    }

    // Mostrar el modal de datos del cliente
    const modalDatosCliente = document.getElementById('modalDatosCliente');
    if (modalDatosCliente) modalDatosCliente.style.display = 'flex';
}

/**
 * Valida los datos del cliente (obligatorios en Factura) y confirma la venta.
 */
function validarYConfirmarVenta() {
    const nifEl = document.getElementById('clienteNif');
    const nombreEl = document.getElementById('clienteNombre');
    const direccionEl = document.getElementById('clienteDireccion');
    const observacionesEl = document.getElementById('clienteObservaciones');

    const nif = nifEl ? nifEl.value.trim() : '';
    const nombre = nombreEl ? nombreEl.value.trim() : '';
    const direccion = direccionEl ? direccionEl.value.trim() : '';
    const observaciones = observacionesEl ? observacionesEl.value.trim() : '';

    // En modo Factura, NIF, Nombre y Dirección son obligatorios
    if (tipoDocumentoActual === 'factura') {
        if (!nif || !nombre || !direccion) {
            const errorDatosCliente = document.getElementById('errorDatosCliente');
            if (errorDatosCliente) errorDatosCliente.style.display = 'block';
            return;
        }
    } else {
        // Modo Ticket: Si rellena uno, el otro es obligatorio
        if ((nif && !nombre) || (!nif && nombre)) {
            const errorDatosCliente = document.getElementById('errorDatosCliente');
            if (errorDatosCliente) errorDatosCliente.style.display = 'block';
            return;
        }
    }

    // Cerrar modal de datos del cliente y proceder con la venta o volver al checkout
    cerrarModal('modalDatosCliente');

    if (window.retornarAlCheckout) {
        window.retornarAlCheckout = false;
        // Actualizar resumen y vista previa antes de volver
        actualizarResumenClienteCheckout();
        renderizarVistaPreviaTicket();
        const modalFinalizarVenta = document.getElementById('modalFinalizarVenta');
        if (modalFinalizarVenta) modalFinalizarVenta.style.display = 'flex';
    } else {
        confirmarVenta(tipoDocumentoActual, nif, nombre, direccion, observaciones);
    }
}

/**
 * El cliente decide registrar su DNI para obtener puntos.
 */
function confirmarConPuntos() {
    cerrarModal('modalPuntos');
    // Abrir modal para buscar cliente registrado
    if (typeof abrirModalBuscarClienteRegistradoParaPuntos === 'function') {
        abrirModalBuscarClienteRegistradoParaPuntos();
    }
}

/**
 * El cliente decide no registrar su DNI para puntos.
 */
function confirmarSinPuntos() {
    cerrarModal('modalPuntos');
    // Mostrar nuevo modal de finalizar venta
    abrirModalFinalizarVenta();
}

/**
 * Abre el modal de búsqueda de cliente para acumular puntos.
 */
function abrirModalBuscarClienteRegistradoParaPuntos() {
    const dniBusquedaCliente = document.getElementById('dniBusquedaCliente');
    if (dniBusquedaCliente) dniBusquedaCliente.value = '';

    const mensajeResultadoBusqueda = document.getElementById('mensajeResultadoBusqueda');
    if (mensajeResultadoBusqueda) mensajeResultadoBusqueda.style.display = 'none';

    // Cambiamos el título para indicar que es para puntos
    const h3 = document.querySelector('#modalBuscarClienteRegistrado h3');
    if (h3) h3.textContent = t('cart.client_for_points');

    const modalSubtitulo = document.querySelector('#modalBuscarClienteRegistrado .modal-subtitulo');
    const puntosPosibles = document.getElementById('puntosPosibles');
    if (modalSubtitulo && puntosPosibles) {
        modalSubtitulo.textContent = t('cart.enter_dni_accumulate') + ' ' + puntosPosibles.textContent + ' ' + t('cart.points_text');
    }

    // Cambiamos el comportamiento del botón buscar
    const modalBuscarClienteRegistrado = document.getElementById('modalBuscarClienteRegistrado');
    if (modalBuscarClienteRegistrado) {
        modalBuscarClienteRegistrado.dataset.modo = 'puntos';
        modalBuscarClienteRegistrado.style.display = 'flex';
    }
}

/**
 * Rellena el formulario oculto con todos los datos de la venta y lo envía por POST.
 */
function confirmarVenta(tipoDocumento, nif, nombre, direccion, observaciones, mensajePersonalizado = '') {
    const total = obtenerTotalCalculado();
    const metodoPago = document.getElementById('metodoPago')?.value || 'efectivo';
    let entregado = total;
    let cambio = 0;

    // Si el pago es en efectivo, calcular entregado y cambio
    if (metodoPago === 'efectivo') {
        const inputVal = parseFloat(document.getElementById('inputDineroEntregado')?.value);
        if (!isNaN(inputVal) && inputVal >= total) {
            entregado = inputVal;
            cambio = inputVal - total;
        }
    } else if (metodoPago === 'mixto' && pagoMixtoDesglose) {
        if (pagoMixtoDesglose.efectivo > 0 && pagoMixtoDesglose.cambio >= 0) {
            entregado = pagoMixtoDesglose.efectivo + pagoMixtoDesglose.cambio;
            cambio = pagoMixtoDesglose.cambio;
        } else {
            entregado = total;
            cambio = 0;
        }
    }

    const precTotal = obtenerDecimalesMaximosCarrito();

    // Rellenar los campos ocultos del formulario
    const fields = {
        'inputCarrito': JSON.stringify(carrito),
        'inputMetodoPago': metodoPago,
        'inputTipoDocumento': tipoDocumento,
        'inputDineroEntregadoFinal': entregado.toFixed(precTotal),
        'inputCambioDevueltoFinal': cambio.toFixed(precTotal),
        'inputDesglosePago': (metodoPago === 'mixto' && pagoMixtoDesglose) ? JSON.stringify(pagoMixtoDesglose) : '',
        'inputClienteNifFinal': nif,
        'inputClienteNombreFinal': nombre,
        'inputClienteDireccionFinal': direccion,
        'inputObservacionesFinal': observaciones,
        'inputDescuentoTipo': descuento.tipo,
        'inputDescuentoValor': descuento.valor,
        'inputDescuentoCupon': descuento.cupon,
        'inputDescuentoTarifaTipo': 'ninguno',
        'inputDescuentoTarifaValor': 0,
        'inputDescuentoTarifaCupon': '',
        'inputDescuentoManualTipo': descuento.tipo,
        'inputDescuentoManualValor': descuento.valor,
        'inputDescuentoManualCupon': descuento.cupon,
        'inputMensajePersonalizado': mensajePersonalizado
    };

    for (const [id, val] of Object.entries(fields)) {
        const el = document.getElementById(id);
        if (el) el.value = val;
    }

    // Guardar puntos canjeados si existen
    if (typeof puntosCanjeados !== 'undefined' && puntosCanjeados && puntosCanjeados.dni && puntosCanjeados.puntos > 0) {
        const pDni = document.getElementById('inputPuntosCanjeadosDni');
        const pCant = document.getElementById('inputPuntosCanjeadosCantidad');
        if (pDni) pDni.value = puntosCanjeados.dni;
        if (pCant) pCant.value = puntosCanjeados.puntos;
    } else {
        const pDni = document.getElementById('inputPuntosCanjeadosDni');
        const pCant = document.getElementById('inputPuntosCanjeadosCantidad');
        if (pDni) pDni.value = '';
        if (pCant) pCant.value = 0;
    }

    // Estado del cliente identificado en modal puntos
    let clienteIdentificadoPuntos = false;
    if (typeof clienteIdentificadoEnModalPuntos !== 'undefined') {
        clienteIdentificadoPuntos = !!clienteIdentificadoEnModalPuntos;
        const inputCIP = document.getElementById('inputClienteIdentificadoPuntos');
        if (inputCIP) inputCIP.value = clienteIdentificadoPuntos ? 'true' : 'false';
    }

    // Calcular puntos ganados y balance final
    const puntosGanados = (clienteIdentificadoPuntos && total >= 20) ? Math.round(total * 10) : 0;
    const inputPuntosCanjeadosCantidad = document.getElementById('inputPuntosCanjeadosCantidad');
    const puntosCanjeadosVal = inputPuntosCanjeadosCantidad ? (parseInt(inputPuntosCanjeadosCantidad.value) || 0) : 0;
    const clientePuntos = document.getElementById('clientePuntos');
    const puntosOriginales = (clienteIdentificadoPuntos) ? (parseInt(clientePuntos?.value) || 0) : 0;
    const puntosBalanceFinal = (clienteIdentificadoPuntos) ? (puntosOriginales - puntosCanjeadosVal + puntosGanados) : 0;

    const inputPG = document.getElementById('inputPuntosGanados');
    const inputPB = document.getElementById('inputPuntosBalance');
    if (inputPG) inputPG.value = puntosGanados;
    if (inputPB) inputPB.value = puntosBalanceFinal;

    // Tarifa seleccionada
    const inputIdTarifa = document.getElementById('inputIdTarifa');
    const tarifaVenta = document.getElementById('tarifaVenta');
    if (inputIdTarifa && tarifaVenta) inputIdTarifa.value = tarifaVenta.value;

    // Enviar el formulario al servidor
    const formVenta = document.getElementById('formVenta');
    if (formVenta) formVenta.submit();

    // Resetear estados después de enviar
    if (typeof puntosCanjeados !== 'undefined') puntosCanjeados = null;
    if (typeof clienteIdentificadoEnModalPuntos !== 'undefined') clienteIdentificadoEnModalPuntos = false;
    descuento = { tipo: 'ninguno', valor: 0, cupon: '' };
}

/**
 * Genera un documento HTML formateado (ticket o factura) con los datos de la última venta
 * y lo envía a la impresora mediante un iframe oculto.
 */
function imprimirDocumento() {
    if (typeof ultimaVenta === 'undefined') return;

    // Generar el contenido HTML usando el idioma guardado en la venta o el seleccionado
    if (typeof generarHTMLComprobante !== 'function') return;
    const contenido = generarHTMLComprobante(ultimaVenta, ultimaVenta.idioma_ticket || idiomaTicketSeleccionado);

    // Crear un iframe oculto para imprimir sin afectar la página actual
    const iframe = document.createElement('iframe');
    iframe.style.position = 'absolute';
    iframe.style.top = '-10000px';
    document.body.appendChild(iframe);
    iframe.contentDocument.open();
    iframe.contentDocument.write(contenido);
    iframe.contentDocument.close();

    // Cuando el iframe cargue, ejecutar la impresión y luego eliminarlo
    iframe.onload = function () {
        iframe.contentWindow.print();
        setTimeout(() => {
            if (iframe.parentNode) iframe.remove();
        }, 1000);
    };
}





/**
* Muestra el formulario de envío por correo electrónico dentro del modal de venta exitosa.
*/
function mostrarFormEmail() {
    const formEmail = document.getElementById('formEmail');
    if (formEmail) formEmail.style.display = 'block';
    const inputEmail = document.getElementById('inputEmail');
    if (inputEmail) inputEmail.focus();
}

/**
* Envía los datos de la última venta por correo electrónico al cliente.
*/
function enviarPorCorreo() {
    if (typeof ultimaVenta === 'undefined') return;

    const inputEmail = document.getElementById('inputEmail');
    const email = inputEmail ? inputEmail.value.trim() : '';
    const statusEl = document.getElementById('emailStatus');

    if (!statusEl) return;

    // Validación básica del email
    if (!email || !email.includes('@')) {
        statusEl.textContent = t('cart.alert_valid_email');
        statusEl.className = 'email-status email-error';
        return;
    }

    // Generar el número de ticket con formato serieNumero
    const ventaIdNumero = (ultimaVenta.serie || 'T') + String(ultimaVenta.numero || ultimaVenta.id || '').padStart(5, '0');
    console.log('Enviando email con ventaId:', ventaIdNumero, 'serie:', ultimaVenta.serie, 'numero:', ultimaVenta.numero);

    // Mostrar estado "Enviando..."
    statusEl.textContent = t('cart.email_sending');
    statusEl.className = 'email-status email-enviando';

    // Petición AJAX al endpoint de envío de correo
    ultimaVenta.mensajePersonalizado = ultimaVenta.mensajePersonalizado || '';

    fetch('api/enviarCorreo.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            email: email,
            tipoDocumento: ultimaVenta.tipo,
            ventaId: ventaIdNumero,
            total: ultimaVenta.total,
            lineas: ultimaVenta.carrito,
            fecha: ultimaVenta.fecha,
            metodoPago: ultimaVenta.metodoPago,
            entregado: ultimaVenta.entregado,
            cambio: ultimaVenta.cambio,
            clienteNif: ultimaVenta.clienteNif,
            clienteNombre: ultimaVenta.clienteNombre,
            clienteDir: ultimaVenta.clienteDir,
            clienteObs: ultimaVenta.clienteObs,
            descuentoTipo: ultimaVenta.descuentoTipo,
            descuentoValor: ultimaVenta.descuentoValor,
            descuentoCupon: ultimaVenta.descuentoCupon,
            descuentoTarifaTipo: ultimaVenta.descuentoTarifaTipo,
            descuentoTarifaValor: ultimaVenta.descuentoTarifaValor,
            descuentoTarifaCupon: ultimaVenta.descuentoTarifaCupon,
            descuentoManualTipo: ultimaVenta.descuentoManualTipo,
            descuentoManualValor: ultimaVenta.descuentoManualValor,
            descuentoManualCupon: ultimaVenta.descuentoManualCupon,
            puntos_ganados: ultimaVenta.puntosGanados || 0,
            puntos_canjeados: ultimaVenta.puntosCanjeados ? ultimaVenta.puntosCanjeados.puntos : 0,
            puntos_balance: ultimaVenta.puntosBalance || 0,
            mensajePersonalizado: ultimaVenta.mensajePersonalizado || '',
            pagoMixtoDesglose: ultimaVenta.pagoMixtoDesglose || null,
            qrUrl: ultimaVenta.qrUrl || '',
            lang: ultimaVenta.idioma_ticket || 'es'
        })
    })
        .then(res => res.json())
        .then(data => {
            if (data.ok) {
                // Envío exitoso
                statusEl.textContent = '✓ ' + t('cart.email_sent_to') + ' ' + email;
                statusEl.className = 'email-status email-ok';
            } else {
                // Error del servidor
                statusEl.textContent = '✗ ' + (data.mensaje || t('cart.email_error_sending'));
                statusEl.className = 'email-status email-error';
            }
        })
        .catch(err => {
            // Error de conexión
            statusEl.textContent = '✗ ' + t('cart.email_error_connection');
            statusEl.className = 'email-status email-error';
        });
}

/**
* Muestra el formulario de envío por correo electrónico dentro del modal de devolución exitosa.
*/
function mostrarFormEmailDevolucion() {
    const formEmailDev = document.getElementById('formEmailDev');
    if (formEmailDev) formEmailDev.style.display = 'block';
    const inputEmailDev = document.getElementById('inputEmailDev');
    if (inputEmailDev) inputEmailDev.focus();
}

/**
* Envía los datos de la devolución por correo electrónico al cliente.
*/
function enviarPorCorreoDevolucion() {
    if (typeof ultimaDevolucion === 'undefined') return;

    const inputEmailDev = document.getElementById('inputEmailDev');
    const email = inputEmailDev ? inputEmailDev.value.trim() : '';
    const statusEl = document.getElementById('emailStatusDev');

    if (!statusEl) return;

    // Validación básica del email
    if (!email || !email.includes('@')) {
        statusEl.textContent = t('cart.alert_valid_email');
        statusEl.style.color = '#ef4444';
        return;
    }

    // Mostrar estado "Enviando..."
    statusEl.textContent = t('cart.email_sending');
    statusEl.style.color = '#3b82f6';

    // Petición AJAX al endpoint de envío de correo
    fetch('api/enviarCorreo.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            email: email,
            tipoDocumento: 'devolucion',
            ventaId: ultimaDevolucion.id || '',
            serie: ultimaDevolucion.serie || 'D',
            numero: ultimaDevolucion.numero || '',
            orig_serie: ultimaDevolucion.orig_serie || '',
            orig_numero: ultimaDevolucion.orig_numero || '',
            total: ultimaDevolucion.total,
            lineas: ultimaDevolucion.lineas,
            fecha: ultimaDevolucion.fecha,
            metodoPago: ultimaDevolucion.metodoPago,
            clienteObs: ultimaDevolucion.motivo, // pasamos el motivo como observaciones
            qrUrl: ultimaDevolucion.qrUrl || ''
        })
    })
        .then(res => res.json())
        .then(data => {
            if (data.ok) {
                // Envío exitoso
                statusEl.textContent = '✓ ' + t('cart.email_sent_to') + ' ' + email;
                statusEl.style.color = '#10b981';
            } else {
                // Error del servidor
                statusEl.textContent = '✗ ' + (data.mensaje || t('cart.email_error_sending'));
                statusEl.style.color = '#ef4444';
            }
        })
        .catch(err => {
            // Error de conexión
            statusEl.textContent = '✗ ' + t('cart.email_error_connection');
            statusEl.style.color = '#ef4444';
        });
}





/**
* Verifica si el total del carrito supera los 1.000€ con método de pago en efectivo.
*/
function verificarLimiteEfectivo() {
    const metodo = document.getElementById('metodoPago')?.value;
    const total = typeof obtenerTotalCalculado === 'function' ? obtenerTotalCalculado() : 0;
    const aviso = document.getElementById('avisoLimiteEfectivo');

    if (aviso) {
        if (metodo === 'efectivo' && total > 1000) {
            aviso.style.display = 'block';
        } else {
            aviso.style.display = 'none';
        }
    }
}

/**
 * Maneja el cambio de tarifa para aplicar descuentos según el tipo de cliente
 */
function cambiarTarifa() {
    const tarifaId = document.getElementById('tarifaVenta')?.value;
    const tarifasPrefijadas = (typeof TPV_CONTEXT !== 'undefined') ? TPV_CONTEXT.tarifasPrefijadas : [];

    const tarifa = tarifasPrefijadas.find(t => t.id == tarifaId);

    if (!tarifa) {
        eliminarDescuentoPorTarifa();
        return;
    }

    if (tarifa.requiere_cliente == 1 || tarifa.requiere_cliente === true) {
        abrirModalBuscarClienteRegistrado();
    } else if (parseFloat(tarifa.descuento_porcentaje) === 0) {
        eliminarDescuentoPorTarifa();
    } else {
        descuentoTarifa = {
            tipo: 'porcentaje',
            valor: parseFloat(tarifa.descuento_porcentaje),
            cupon: tarifa.nombre.toUpperCase().replace(/\s+/g, '_')
        };
        actualizarTicket();
    }
}

/**
 * Abre el modal para buscar un cliente registrado por DNI
 */
function abrirModalBuscarClienteRegistrado() {
    const dniBusqueda = document.getElementById('dniBusquedaCliente');
    const mensaje = document.getElementById('mensajeResultadoBusqueda');
    const modal = document.getElementById('modalBuscarClienteRegistrado');

    if (dniBusqueda) dniBusqueda.value = '';
    if (mensaje) {
        mensaje.textContent = '';
        mensaje.className = '';
    }
    if (modal) {
        modal.style.display = 'flex';
        if (dniBusqueda) dniBusqueda.focus();
    }
}

/**
 * Muestra el DNI y nombre del cliente identificado en la zona del ticket
 */
function mostrarDniEnTicket(dni) {
    const indicador = document.getElementById('indicadorClienteDni');
    const valorDni = document.getElementById('indicadorClienteDniValor');
    const valorNombre = document.getElementById('indicadorClienteNombre');
    const nombreInput = document.getElementById('clienteNombre');

    if (indicador && valorDni && dni) {
        valorDni.textContent = dni.toUpperCase();
        if (valorNombre && nombreInput) {
            valorNombre.textContent = nombreInput.value.toUpperCase();
            valorNombre.title = nombreInput.value;
        }
        indicador.style.display = 'flex';
    }
}

/**
 * Oculta el indicador de DNI del cliente en la zona del ticket
 */
function ocultarDniEnTicket() {
    const indicador = document.getElementById('indicadorClienteDni');
    const valor = document.getElementById('indicadorClienteDniValor');
    if (indicador) indicador.style.display = 'none';
    if (valor) valor.textContent = '';
}

/**
 * Desvincula el cliente actual de la venta, limpiando todos sus datos y puntos
 */
function desvincularCliente() {
    clienteIdentificadoEnModalPuntos = false;

    // Limpiar campos de datos del cliente
    const ids = ['clienteNif', 'clienteNombre', 'clienteDireccion', 'clienteObservaciones',
        'inputClienteNifFinal', 'inputClienteNombreFinal', 'inputClienteDireccionFinal',
        'inputObservacionesFinal', 'inputPuntosCanjeadosDni', 'inputPuntosCanjeadosCantidad',
        'dniPuntosCliente', 'dniBusquedaCliente', 'clientePuntos'];

    ids.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = (id === 'clientePuntos') ? '0' : '';
    });

    // Resetear flag de puntos
    const identificadorPuntos = document.getElementById('inputClienteIdentificadoPuntos');
    if (identificadorPuntos) identificadorPuntos.value = 'false';

    // Resetear variables globales
    puntosCanjeados = null;

    // Ocultar indicador en UI
    ocultarDniEnTicket();

    // Si el descuento actual era por puntos (ej: PUNTOS_1000), lo quitamos también
    if (descuento.cupon && descuento.cupon.startsWith('PUNTOS_')) {
        descuento = { tipo: 'ninguno', valor: 0, cupon: '' };
    }

    // Restaurar tarifas por defecto (Cliente) al desvincular
    restaurarTarifasCarritoPorDefecto();

    actualizarTicket();
}

function cerrarYLimpiarClientePuntos() {
    desvincularCliente();
    cerrarModal('modalPuntosCliente');
    const busqueda = document.getElementById('puntosClienteBusqueda');
    const info = document.getElementById('puntosClienteInfo');
    if (busqueda) busqueda.style.display = 'block';
    if (info) info.style.display = 'none';
}

/**
 * Solo acumula puntos sin aplicar descuento, y cierra el modal
 */
function acumularPuntosSolamente() {
    puntosCanjeados = null;
    clienteIdentificadoEnModalPuntos = true;

    const dniCliente = document.getElementById('dniPuntosCliente')?.value.trim() || '';
    mostrarDniEnTicket(dniCliente);

    cerrarModal('modalPuntosCliente');
    const busqueda = document.getElementById('puntosClienteBusqueda');
    const info = document.getElementById('puntosClienteInfo');
    if (busqueda) busqueda.style.display = 'block';
    if (info) info.style.display = 'none';

    // Actualizar tarifas del carrito al identificar cliente
    actualizarTarifasCarritoPorCliente();

    actualizarTicket();
}

/**
 * Busca los puntos de un cliente por DNI
 */
async function buscarPuntosCliente() {
    const dniInput = document.getElementById('dniPuntosCliente');
    const dni = dniInput ? dniInput.value.trim() : '';
    const mensajeDiv = document.getElementById('mensajePuntosCliente');

    if (!dni) {
        if (mensajeDiv) {
            mensajeDiv.textContent = 'Por favor, introduce un DNI';
            mensajeDiv.className = 'mensaje-error';
            mensajeDiv.style.display = 'block';
        }
        return;
    }

    try {
        const response = await fetch('api/clientes.php?dni=' + encodeURIComponent(dni));

        if (!response.ok) {
            if (mensajeDiv) {
                mensajeDiv.textContent = t('points.error_client_not_found');
                mensajeDiv.className = 'mensaje-error';
                mensajeDiv.style.display = 'block';
            }
            return;
        }

        const data = await response.json();
        const cliente = Array.isArray(data) ? data[0] : data;
        if (cliente && cliente.activo == 1) {
            const nifEl = document.getElementById('clienteNif');
            const nombreEl = document.getElementById('clienteNombre');
            const puntosEl = document.getElementById('clientePuntos');
            const dirEl = document.getElementById('clienteDireccion');
            const obsEl = document.getElementById('clienteObservaciones');

            if (nifEl) nifEl.value = cliente.dni;
            if (nombreEl) nombreEl.value = cliente.nombre + ' ' + cliente.apellidos;
            if (puntosEl) puntosEl.value = cliente.puntos || 0;
            if (dirEl) dirEl.value = '';
            if (obsEl) obsEl.value = '';

            const puntosDisponibles = cliente.puntos || 0;
            const puntosDisponiblesCliente = document.getElementById('puntosDisponiblesCliente');
            if (puntosDisponiblesCliente) puntosDisponiblesCliente.textContent = puntosDisponibles.toLocaleString('es-ES');

            const busqueda = document.getElementById('puntosClienteBusqueda');
            const info = document.getElementById('puntosClienteInfo');
            if (busqueda) busqueda.style.display = 'none';
            if (info) info.style.display = 'block';

            const aCanjeer = document.getElementById('puntosACanjeer');
            if (aCanjeer) {
                aCanjeer.value = '';
                aCanjeer.max = puntosDisponibles;
            }
            const preview = document.getElementById('descuentoPuntosPreview');
            if (preview) preview.textContent = '';

            const totalTicket = typeof obtenerTotalCalculado === 'function' ? obtenerTotalCalculado() : 0;
            const infoPanel = document.getElementById('infoPointsPanel');
            const puntosUsarMsg = document.getElementById('puntosQueSePuedenUsar');
            const puntosGanadosMsg = document.getElementById('puntosQueSeGanaran');

            if (totalTicket > 0) {
                const puntosQueSeGanaran = Math.round(totalTicket * 10);
                const maxDescuento = totalTicket * 0.30;
                const maxPuntosCanjeables = Math.floor(maxDescuento / 5) * 1000;
                const puntosParaSiguienteDescuento = Math.max(0, 1000 - (puntosDisponibles % 1000));

                let mensajeUsar = '';
                if (puntosDisponibles >= 1000) {
                    const puedenUsarse = Math.floor(Math.min(puntosDisponibles, maxPuntosCanjeables) / 1000) * 1000;
                    const descuentoMax = Math.floor(puedenUsarse / 1000) * 5;
                    mensajeUsar = t('points.you_can_use') + ` ${puedenUsarse.toLocaleString('es-ES')} ` + t('points.points_text') + ` = ${descuentoMax.toFixed(2)}€ ` + t('points.of_discount') + ' (30% ' + t('points.max_of_ticket') + ')';
                    if (aCanjeer) aCanjeer.max = puedenUsarse;
                } else {
                    mensajeUsar = t('points.you_need') + ` ${puntosParaSiguienteDescuento.toLocaleString('es-ES')} ` + t('points.points_for_next_discount');
                }

                if (puntosUsarMsg) puntosUsarMsg.textContent = mensajeUsar;
                if (puntosGanadosMsg) puntosGanadosMsg.textContent = t('points.with_purchase_earn') + ` ${puntosQueSeGanaran.toLocaleString('es-ES')} ` + t('points.points_text') + ' (1€ = 10 ' + t('points.points_text') + ')';
                if (infoPanel) infoPanel.style.display = 'block';
            } else {
                if (infoPanel) infoPanel.style.display = 'none';
            }
        } else {
            if (mensajeDiv) {
                mensajeDiv.textContent = t('points.error_client_inactive_or_none');
                mensajeDiv.className = 'mensaje-error';
                mensajeDiv.style.display = 'block';
            }
        }
    } catch (error) {
        console.error('Error al buscar cliente:', error);
        if (mensajeDiv) {
            mensajeDiv.textContent = t('points.error_searching_client');
            mensajeDiv.className = 'mensaje-error';
            mensajeDiv.style.display = 'block';
        }
    }
}

/**
 * Calcula el descuento basado en los puntos a canjear
 */
function calcularDescuentoPuntos() {
    const inputPuntos = document.getElementById('puntosACanjeer');
    let puntos = inputPuntos ? parseInt(inputPuntos.value) || 0 : 0;
    const preview = document.getElementById('descuentoPuntosPreview');
    const puntosGanadosMsg = document.getElementById('puntosQueSeGanaran');
    const totalTicket = typeof obtenerTotalCalculado === 'function' ? obtenerTotalCalculado() : 0;

    let nuevoTotal = totalTicket;
    const esMultiploDeMil = puntos % 1000 === 0;
    const descVal = Math.floor(puntos / 1000) * 5;

    if (preview) {
        if (puntos >= 1000) {
            preview.textContent = t('cart.discount') + `: ${descVal.toFixed(2)}€ (${puntos.toLocaleString('es-ES')} ` + t('points.points_text') + ')';
            preview.style.color = '';
            nuevoTotal = Math.max(0, totalTicket - descVal);
        } else if (puntos > 0 && puntos < 1000) {
            preview.textContent = t('points.error_min_1000');
            preview.style.color = '#ef4444';
        } else if (puntos > 0 && !esMultiploDeMil) {
            preview.textContent = t('points.error_multiples_1000');
            preview.style.color = '#ef4444';
        } else {
            preview.textContent = '';
            preview.style.color = '';
        }
    }

    if (puntosGanadosMsg) {
        const puntosQueSeGanaran = Math.round(nuevoTotal * 10);
        puntosGanadosMsg.textContent = t('points.with_purchase_earn') + ` ${puntosQueSeGanaran.toLocaleString('es-ES')} ` + t('points.points_text') + ' (1€ = 10 ' + t('points.points_text') + ')';
    }
}

/**
 * Aplica el descuento de puntos a la venta actual
 */
function aplicarDescuentoPuntos() {
    const puntosInputVal = document.getElementById('puntosACanjeer')?.value || 0;
    const puntosInput = parseInt(puntosInputVal) || 0;
    const puntosRedondeados = Math.floor(puntosInput / 1000) * 1000;
    const dni = document.getElementById('dniPuntosCliente')?.value.trim() || '';
    const puntosDisponiblesStr = document.getElementById('puntosDisponiblesCliente')?.textContent.replace(/\./g, '') || '0';
    const puntosDisponibles = parseInt(puntosDisponiblesStr) || 0;

    if (!dni) {
        alert(t('points.error_no_dni_specified'));
        return;
    }

    if (puntosRedondeados < 1000) {
        alert(t('points.error_min_1000_alert'));
        return;
    }

    if (puntosRedondeados > puntosDisponibles) {
        alert(t('points.error_not_enough_points') + '. ' + t('points.you_have') + ` ${puntosDisponibles.toLocaleString('es-ES')} ` + t('points.points_text') + '.');
        return;
    }

    const totalTicket = typeof obtenerTotalCalculado === 'function' ? obtenerTotalCalculado() : 0;
    const maxDescuento = totalTicket * 0.30;
    const maxPuntos = Math.floor(maxDescuento / 5) * 1000;

    let puntosFinales = puntosRedondeados;
    if (puntosRedondeados > maxPuntos) {
        alert(t('points.alert_exceeded_max') + ` (30% ` + t('points.of_ticket') + ` = ${maxDescuento.toFixed(2)}€). ` + t('points.will_use') + ` ${maxPuntos.toLocaleString('es-ES')} ` + t('points.points_text') + '.');
        puntosFinales = maxPuntos;
    }

    const descuentoEuros = Math.floor(puntosFinales / 1000) * 5;

    if (totalTicket - descuentoEuros <= 0) {
        alert(t('points.error_discount_ticket_zero'));
        return;
    }

    descuento.tipo = 'fijo';
    descuento.valor = descuentoEuros;
    descuento.cupon = 'PUNTOS_' + puntosFinales;

    const puntosGanados = Math.round((totalTicket - descuentoEuros) * 10);

    // Actualizar tarifas del carrito al identificar cliente
    actualizarTarifasCarritoPorCliente();

    actualizarTicket();
    mostrarDniEnTicket(dni);

    cerrarModal('modalPuntosCliente');
    const busqueda = document.getElementById('puntosClienteBusqueda');
    const info = document.getElementById('puntosClienteInfo');
    if (busqueda) busqueda.style.display = 'block';
    if (info) info.style.display = 'none';

    clienteIdentificadoEnModalPuntos = true;
    puntosCanjeados = {
        dni: dni,
        puntos: puntosFinales,
        descuento: descuentoEuros
    };

    alert(t('points.alert_discount_applied') + ` ${descuentoEuros.toFixed(2)}€ (` + t('points.canjeados') + ` ${puntosFinales.toLocaleString('es-ES')} ` + t('points.points_text') + ')\n' +
        t('points.with_purchase_earn') + ` ${puntosGanados.toLocaleString('es-ES')} ` + t('points.points_text'));
}

/**
 * Busca un cliente por DNI y aplica el descuento configurado en la tarifa
 */
async function buscarClienteRegistrado() {
    const dniInput = document.getElementById('dniBusquedaCliente');
    const dni = dniInput ? dniInput.value.trim() : '';
    const mensajeDiv = document.getElementById('mensajeResultadoBusqueda');
    const modal = document.getElementById('modalBuscarClienteRegistrado');
    const esModoPuntos = modal?.dataset.modo === 'puntos';
    const tarifasPrefijadas = (typeof TPV_CONTEXT !== 'undefined') ? TPV_CONTEXT.tarifasPrefijadas : [];

    let tarifaActual = null;
    if (productoPendienteTarifa) {
        const select = productoPendienteTarifa.card.querySelector('.tarifa-selector');
        const tarifaId = select.options[select.selectedIndex].dataset.tarifaId;
        tarifaActual = tarifasPrefijadas.find(t => t.id == tarifaId);
    } else {
        const tarifaIdActual = document.getElementById('tarifaVenta')?.value;
        tarifaActual = tarifasPrefijadas.find(t => t.id == tarifaIdActual);
    }

    if (!dni) {
        if (mensajeDiv) {
            mensajeDiv.textContent = 'Por favor, introduce un DNI';
            mensajeDiv.className = 'mensaje-error';
            mensajeDiv.style.display = 'block';
        }
        return;
    }

    try {
        const response = await fetch('api/clientes.php?dni=' + encodeURIComponent(dni));

        if (!response.ok) {
            if (esModoPuntos) {
                if (mensajeDiv) {
                    mensajeDiv.textContent = t('points.error_client_not_found_points');
                    mensajeDiv.className = 'mensaje-error';
                    mensajeDiv.style.display = 'block';
                    mensajeDiv.innerHTML += `<br><button class="btn-modal-cancelar" onclick="confirmarSinPuntos()" style="margin-top:10px; width:100%;">` + t('points.btn_continue_no_points') + `</button>`;
                }
                return;
            }
            if (mensajeDiv) {
                mensajeDiv.textContent = t('points.error_client_not_found');
                mensajeDiv.className = 'mensaje-error';
                mensajeDiv.style.display = 'block';
            }
            const tarifaVenta = document.getElementById('tarifaVenta');
            if (tarifaVenta) {
                let tarifaCliente = tarifasPrefijadas.find(t => t.nombre === 'Cliente');
                if (tarifaCliente) {
                    tarifaVenta.value = tarifaCliente.id;
                } else if (tarifasPrefijadas.length > 0) {
                    tarifaVenta.value = tarifasPrefijadas[0].id;
                }
            }
            return;
        }

        const data = await response.json();
        const cliente = Array.isArray(data) ? data[0] : data;

        if (cliente && cliente.activo == 1) {
            const nifEl = document.getElementById('clienteNif');
            const nombreEl = document.getElementById('clienteNombre');
            const puntosEl = document.getElementById('clientePuntos');
            const dirEl = document.getElementById('clienteDireccion');
            const obsEl = document.getElementById('clienteObservaciones');

            if (nifEl) nifEl.value = cliente.dni;
            if (nombreEl) nombreEl.value = cliente.nombre + ' ' + cliente.apellidos;
            if (puntosEl) puntosEl.value = cliente.puntos || 0;
            if (dirEl) dirEl.value = '';
            if (obsEl) obsEl.value = '';

            if (esModoPuntos) {
                cerrarModal('modalBuscarClienteRegistrado');
                cerrarModal('modalDatosCliente');
                const modalTipoDoc = document.getElementById('modalTipoDoc');
                if (modalTipoDoc) modalTipoDoc.style.display = 'flex';
                return;
            }

            const nombreTarifa = tarifaActual ? tarifaActual.nombre : 'Cliente Registrado';
            const descuentoValor = tarifaActual ? parseFloat(tarifaActual.descuento_porcentaje) : 0;

            // Cambiar automáticamente todos los productos con tarifa "Cliente" a "Cliente Registrado"
            actualizarTarifasCarritoPorCliente();

            actualizarTicket();
            if (mensajeDiv) {
                mensajeDiv.textContent = t('points.client_found') + `: ${cliente.nombre} ${cliente.apellidos}. ` + t('cart.tarifa') + ` ${nombreTarifa} (${descuentoValor}%) ` + t('points.validated') + '.';
                mensajeDiv.className = 'mensaje-exito';
                mensajeDiv.style.display = 'block';
            }

            if (productoPendienteTarifa) {
                agregarAlCarrito(productoPendienteTarifa.card);
                productoPendienteTarifa = null;
            }

            setTimeout(() => {
                cerrarModal('modalBuscarClienteRegistrado');
            }, 1500);
        } else if (cliente && cliente.activo == 0) {
            if (mensajeDiv) {
                mensajeDiv.textContent = t('points.error_client_inactive');
                mensajeDiv.className = 'mensaje-error';
                mensajeDiv.style.display = 'block';
            }
        } else {
            if (mensajeDiv) {
                mensajeDiv.textContent = t('points.error_client_not_found');
                mensajeDiv.className = 'mensaje-error';
                mensajeDiv.style.display = 'block';
            }
            const tarifaVenta = document.getElementById('tarifaVenta');
            if (tarifaVenta) {
                let tarifaCliente = tarifasPrefijadas.find(t => t.nombre === 'Cliente');
                if (tarifaCliente) {
                    tarifaVenta.value = tarifaCliente.id;
                } else if (tarifasPrefijadas.length > 0) {
                    tarifaVenta.value = tarifasPrefijadas[0].id;
                }
            }
        }
    } catch (error) {
        console.error('Error al buscar cliente:', error);
        if (mensajeDiv) {
            mensajeDiv.textContent = t('points.error_searching_client');
            mensajeDiv.className = 'mensaje-error';
        }
        if (productoPendienteTarifa) {
            revertirTarifaCard(productoPendienteTarifa.card.dataset.id);
            productoPendienteTarifa = null;
        }
    }
}

/**
 * Recorre el carrito y cambia todos los productos que tengan la tarifa "Cliente" 
 * a la tarifa "Cliente Registrado" si existe.
 */
function actualizarTarifasCarritoPorCliente() {
    const tarifasPrefijadas = (typeof TPV_CONTEXT !== 'undefined') ? TPV_CONTEXT.tarifasPrefijadas : [];

    // Buscar la tarifa de cliente registrado de forma más flexible
    const tarifaRegistrado = tarifasPrefijadas.find(t =>
        (t.nombre && t.nombre.toLowerCase().trim() === 'cliente registrado') ||
        (t.requiere_cliente == 1 && parseFloat(t.descuento_porcentaje) === 0)
    );

    if (!tarifaRegistrado) {
        console.warn("TPV: No se encontró la tarifa 'Cliente Registrado' en TPV_CONTEXT.tarifasPrefijadas");
        return;
    }

    let huboCambios = false;
    const idTarifaRegistrado = tarifaRegistrado.id;

    carrito.forEach(item => {
        // Solo cambiamos si está en tarifa "Cliente" (ID 1 o nombre exacto)
        // O si ya es "Cliente Registrado" pero queremos forzar actualización (aunque esto último no es necesario)
        if (item.tarifaNombre === 'Cliente') {
            const descuento = parseFloat(tarifaRegistrado.descuento_porcentaje) || 0;
            const preciosTarifas = item.preciosTarifas || {};

            let nuevoPrecioBase;
            if (preciosTarifas[idTarifaRegistrado]) {
                nuevoPrecioBase = preciosTarifas[idTarifaRegistrado].precio;
            } else {
                nuevoPrecioBase = (item.precioBaseOriginal || item.precio) * (1 - (descuento / 100));
            }

            const pvpUnitario = roundTo(nuevoPrecioBase * (1 + (item.iva / 100)), item.decimales || 2);

            item.pvpUnitario = pvpUnitario;
            item.tarifaNombre = tarifaRegistrado.nombre;
            item.tarifaDescuento = descuento;
            huboCambios = true;
        }
    });

    if (huboCambios) {
        // También actualizar el selector de tarifa global si está en "Cliente"
        const selectTarifa = document.getElementById('tarifaVenta');
        if (selectTarifa) {
            const tarifaCliente = tarifasPrefijadas.find(t => t.nombre === 'Cliente');
            if (tarifaCliente && selectTarifa.value == tarifaCliente.id) {
                selectTarifa.value = idTarifaRegistrado;
            }
        }
        actualizarTicket();
    }
}

/**
 * Restaura todos los productos del carrito a la tarifa "Cliente" por defecto.
 */
function restaurarTarifasCarritoPorDefecto() {
    const tarifasPrefijadas = (typeof TPV_CONTEXT !== 'undefined') ? TPV_CONTEXT.tarifasPrefijadas : [];
    const tarifaCliente = tarifasPrefijadas.find(t => t.id == 1 || t.nombre === 'Cliente');

    if (!tarifaCliente) return;

    carrito.forEach(item => {
        const idTarifa = tarifaCliente.id;
        const descuento = parseFloat(tarifaCliente.descuento_porcentaje) || 0;
        const preciosTarifas = item.preciosTarifas || {};

        let nuevoPrecioBase;
        if (preciosTarifas[idTarifa]) {
            nuevoPrecioBase = preciosTarifas[idTarifa].precio;
        } else {
            nuevoPrecioBase = (item.precioBaseOriginal || item.precio) * (1 - (descuento / 100));
        }

        const pvpUnitario = roundTo(nuevoPrecioBase * (1 + (item.iva / 100)), item.decimales || 2);

        item.pvpUnitario = pvpUnitario;
        item.tarifaNombre = tarifaCliente.nombre;
        item.tarifaDescuento = descuento;
    });

    // Resetear el selector global de tarifas si existe
    const selectTarifa = document.getElementById('tarifaVenta');
    if (selectTarifa) {
        selectTarifa.value = tarifaCliente.id;
    }
}

/**
 * Sobrescribimos el cierre del modal para manejar la reversión de tarifa si se cancela
 */
function cerrarModalBuscarClienteRegistrado() {
    const modal = document.getElementById('modalBuscarClienteRegistrado');

    if (modal?.dataset.modo === 'puntos') {
        modal.dataset.modo = '';
        const h3 = modal.querySelector('h3');
        const subtitulo = modal.querySelector('.modal-subtitulo');
        if (h3) h3.textContent = t('points.registered_client');
        if (subtitulo) subtitulo.textContent = t('points.enter_client_dni');
        confirmarSinPuntos();
        return;
    }

    if (productoPendienteTarifa) {
        revertirTarifaCard(productoPendienteTarifa.card.dataset.id);
        productoPendienteTarifa = null;
    }
    cerrarModal('modalBuscarClienteRegistrado');
}

/**
 * Elimina el descuento aplicado por tarifa
 */
function eliminarDescuentoPorTarifa() {
    if (descuentoTarifa && descuentoTarifa.tipo !== 'ninguno') {
        descuentoTarifa = { tipo: 'ninguno', valor: 0, cupon: '' };
        actualizarTicket();
    }
}

/**
 * Abre el modal para añadir un nuevo cliente habitual
 */
function abrirModalClienteHabitual() {
    const dniEl = document.getElementById('clienteHabitualDni');
    const nombreEl = document.getElementById('clienteHabitualNombre');
    const apellidosEl = document.getElementById('clienteHabitualApellidos');
    const fechaEl = document.getElementById('clienteHabitualFecha');

    if (dniEl) dniEl.value = '';
    if (nombreEl) nombreEl.value = '';
    if (apellidosEl) apellidosEl.value = '';

    const now = new Date();
    const localDate = new Date(now.getTime() - (now.getTimezoneOffset() * 60000)).toISOString().slice(0, 16);
    if (fechaEl) fechaEl.value = localDate;

    const btnGuardar = document.getElementById('btnGuardarClienteHabitual');
    if (btnGuardar) btnGuardar.onclick = guardarClienteHabitual;

    const modal = document.getElementById('modalClienteHabitual');
    if (modal) {
        modal.style.display = 'flex';
        if (dniEl) dniEl.focus();
    }
}

/**
 * Guarda un nuevo cliente habitual
 */
async function guardarClienteHabitual() {
    const dni = document.getElementById('clienteHabitualDni')?.value.trim();
    const nombre = document.getElementById('clienteHabitualNombre')?.value.trim();
    const apellidos = document.getElementById('clienteHabitualApellidos')?.value.trim();
    const direccion = document.getElementById('clienteHabitualDireccion')?.value.trim();
    const fecha_alta = document.getElementById('clienteHabitualFecha')?.value || new Date(new Date().getTime() - (new Date().getTimezoneOffset() * 60000)).toISOString().slice(0, 16);

    if (!dni || !nombre || !apellidos) {
        alert(t('points.error_mandatory_habitual'));
        return;
    }

    const btnGuardar = document.getElementById('btnGuardarClienteHabitual');
    if (btnGuardar) {
        btnGuardar.disabled = true;
        btnGuardar.textContent = t('points.btn_saving');
    }

    try {
        const formData = new FormData();
        formData.append('dni', dni);
        formData.append('nombre', nombre);
        formData.append('apellidos', apellidos);
        formData.append('direccion', direccion);
        formData.append('fecha_alta', fecha_alta);

        const response = await fetch('api/clientes.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.ok) {
            alert(t('points.alert_habitual_saved'));
            cerrarModal('modalClienteHabitual');
        } else {
            alert(data.error || t('points.error_saving_habitual'));
        }
    } catch (error) {
        console.error('Error:', error);
        alert(t('points.error_server_communication'));
    } finally {
        if (btnGuardar) {
            btnGuardar.disabled = false;
            btnGuardar.textContent = t('points.btn_save');
        }
    }
}

/**
 * Guarda un nuevo producto desde el modal de creación.
 */
function guardarNuevoProducto() {
    const nombre = document.getElementById('nuevoProductoNombre')?.value.trim();
    const categoria = document.getElementById('nuevoProductoCategoria')?.value;
    const precio = document.getElementById('nuevoProductoPrecio')?.value;
    const stock = document.getElementById('nuevoProductoStock')?.value;
    const iva = document.getElementById('nuevoProductoIva')?.value;
    const activo = document.getElementById('nuevoProductoEstado')?.value;
    const imgInput = document.getElementById('editProductoImagenInput');

    if (!nombre || !categoria || !precio || stock === '') {
        alert(t('products.alert_fill_mandatory'));
        return;
    }

    const formData = new FormData();
    formData.append('nombre', nombre);
    formData.append('categoria', categoria);
    formData.append('precio', precio);
    formData.append('stock', stock);
    formData.append('iva', iva);
    formData.append('activo', activo);
    if (imgInput?.files[0]) {
        formData.append('imagen', imgInput.files[0]);
    }

    fetch('api/productos.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.ok) {
                alert(t('products.alert_created_successfully'));
                cerrarModal('modalNuevoProducto');
                location.reload();
            } else {
                alert(t('products.alert_error') + ': ' + (data.error ?? ''));
            }
        })
        .catch(err => {
            console.error('Error:', err);
            alert(t('products.alert_error_creating'));
        });
}

/**
 * Carga el historial de ventas del día para el cajero
 */
function cargarHistorialVentas() {
    const contenido = document.getElementById('historialVentasContenido');
    const totalDiv = document.getElementById('historialVentasTotal');
    const fechaDiv = document.getElementById('historialVentasFecha');

    if (!contenido) return;

    // Actualizar la fecha en el subtítulo
    const hoy = new Date();
    if (fechaDiv) {
        fechaDiv.textContent = t('history.subtitle') + ' - ' + hoy.toLocaleDateString('es-ES', { day: 'numeric', month: 'long', year: 'numeric' });
    }

    contenido.innerHTML = '<p style="text-align: center; padding: 40px;">' + t('history.loading') + '...</p>';

    // Cargar ventas desde la API (la sesión se obtiene automáticamente en el servidor)
    fetch('api/ventas.php?historialCaja=1')
        .then(res => {
            if (!res.ok) throw new Error('HTTP error ' + res.status);
            return res.json();
        })
        .then(ventas => {
            if (ventas.error) {
                if (ventas.error.includes('No hay sesión')) {
                    contenido.innerHTML = '<p style="text-align: center; color: var(--text-muted); padding: 40px;">' + t('history.no_session') + '</p>';
                    if (totalDiv) totalDiv.textContent = '';
                    return;
                }
                throw new Error(ventas.error);
            }
            if (!ventas || ventas.length === 0) {
                contenido.innerHTML = '<p style="text-align: center; color: var(--text-muted); padding: 40px;">' + t('history.no_sales') + '</p>';
                if (totalDiv) totalDiv.textContent = t('history.total') + ': 0.00 €';
                return;
            }

            // Calcular total
            let total = 0;
            let html = '<table class="historial-ventas-tabla">';
            html += '<thead><tr>';
            html += '<th>' + t('history.time') + '</th>';
            html += '<th>' + t('history.ticket_num') + '</th>'; // Nueva columna
            html += '<th>' + t('history.user') + '</th>';
            html += '<th>' + t('history.quantity') + '</th>';
            html += '<th>' + t('history.payment_method') + '</th>';
            html += '<th>' + t('history.total_table') + '</th>';
            html += '<th>' + t('history.actions') + '</th>';
            html += '</tr></thead><tbody>';

            ventas.forEach(v => {
                const fecha = new Date(v.fecha);
                const hora = fecha.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
                const totalVenta = parseFloat(v.total);
                const cantidad = v.cantidad_productos || 0;
                total += totalVenta;

                let formaPago = v.forma_pago || 'Efectivo';
                let usuario = v.usuario_nombre || 'Cajero';

                // Formatear el número de ticket (ej: T00001)
                const serie = v.serie || 'T';
                const numero = v.numero || v.id;
                const numTicket = serie + String(numero).padStart(5, '0');

                html += '<tr>';
                html += '<td>' + hora + '</td>';
                html += '<td style="font-family: monospace; font-weight: 600;">' + numTicket + '</td>';
                html += '<td>' + usuario + '</td>';
                html += '<td>' + cantidad + '</td>';
                html += '<td>' + formaPago + '</td>';
                html += '<td style="font-weight: 600;">' + totalVenta.toFixed(2).replace('.', ',') + ' €</td>';
                html += '<td>';
                html += '<div style="display: flex; gap: 5px; justify-content: center;">';
                html += '<button class="btn-exito" onclick="verDetalleVenta(' + v.id + ')" title="Ver detalles" style="padding: 5px 10px; font-size: 12px;">👁️</button>';
                html += '<button class="btn-exito" onclick="reimprimirTicket(' + v.id + ')" title="Reimprimir ticket" style="padding: 5px 10px; font-size: 12px;">🖨️</button>';
                html += '</div>';
                html += '</td>';
                html += '</tr>';
            });

            html += '</tbody></table>';
            contenido.innerHTML = html;
            if (totalDiv) totalDiv.textContent = t('history.total_day') + ': ' + total.toFixed(2).replace('.', ',') + ' € (' + ventas.length + ' ' + t('history.sales') + ')';
        })
        .catch(err => {
            console.error('Error cargando historial:', err);
            contenido.innerHTML = '<p style="text-align: center; color: #dc2626; padding: 40px;">' + t('history.error_loading') + ': ' + err.message + '</p>';
            if (totalDiv) totalDiv.textContent = '';
        });
}

/**
 * Muestra los detalles de una venta específica en un modal
 * @param {number} idVenta 
 */
function verDetalleVenta(idVenta) {
    const modal = document.getElementById('modalDetalleVenta');
    const contenido = document.getElementById('detalleVentaContenido');

    if (!modal || !contenido) return;

    modal.style.display = 'flex';
    contenido.innerHTML = '<p style="text-align: center; padding: 20px;">' + t('sale_details.loading') + '</p>';

    fetch('api/ventas.php?detalleVenta=' + idVenta)
        .then(res => res.json())
        .then(data => {
            if (data.error) {
                contenido.innerHTML = '<p style="text-align: center; color: #dc2626; padding: 20px;">' + t('sale_details.error') + ': ' + data.error + '</p>';
                return;
            }

            const venta = data.venta;
            const lineas = data.lineas;
            const fecha = new Date(venta.fecha).toLocaleString('es-ES');

            // Update header info
            const serie = venta.serie || (venta.tipoDocumento === 'factura' ? 'F' : 'T');
            const numero = venta.numero || venta.id;
            const detalleVentaId = document.getElementById('detalleVentaId');
            if (detalleVentaId) detalleVentaId.textContent = serie + String(numero).padStart(5, '0').slice(-5) + ' - ' + fecha;

            const tipoIcono = venta.tipoDocumento === 'factura' ? '📄' : '🧾';
            const tipoLabel = venta.tipoDocumento === 'factura' ? 'Factura' : 'Ticket';
            const pagoIcono = venta.metodoPago && venta.metodoPago.toLowerCase().includes('tarjeta') ? '💳' : '💵';
            const pagoLabel = venta.metodoPago || 'Efectivo';

            let html = '';

            // Info row
            html += '<div style="display: flex; gap: 15px; margin-bottom: 20px;">';
            html += '<div style="flex: 1; background: var(--bg-secondary); padding: 12px; border-radius: 8px;">';
            html += '<div style="font-size: 11px; color: var(--text-muted); margin-bottom: 4px;">' + t('sale_details.type') + '</div>';
            html += '<div style="font-weight: 600;">' + tipoIcono + ' ' + tipoLabel + '</div>';
            html += '</div>';
            html += '<div style="flex: 1; background: var(--bg-secondary); padding: 12px; border-radius: 8px;">';
            html += '<div style="font-size: 11px; color: var(--text-muted); margin-bottom: 4px;">' + t('sale_details.payment') + '</div>';
            html += '<div style="font-weight: 600;">' + pagoIcono + ' ' + pagoLabel + '</div>';
            html += '</div>';
            html += '</div>';

            // Products table
            html += '<div style="max-height: 250px; overflow-y: auto; margin-bottom: 20px;">';
            html += '<table style="width: 100%; border-collapse: collapse;">';
            html += '<thead><tr style="background: var(--bg-secondary);">';
            html += '<th style="padding: 10px; text-align: left; font-size: 12px; color: var(--text-muted);">' + t('sale_details.product') + '</th>';
            html += '<th style="padding: 10px; text-align: center; font-size: 12px; color: var(--text-muted);">' + t('sale_details.quantity') + '</th>';
            html += '<th style="padding: 10px; text-align: right; font-size: 12px; color: var(--text-muted);">' + t('sale_details.price') + '</th>';
            html += '<th style="padding: 10px; text-align: right; font-size: 12px; color: var(--text-muted);">' + t('sale_details.amount') + '</th>';
            html += '</tr></thead><tbody>';

            lineas.forEach(item => {
                const iva = parseFloat(item.iva) || 0;
                const precioBase = parseFloat(item.precioUnitario) || 0;
                const precioConIVA = precioBase * (1 + iva / 100);
                const subtotal = (precioConIVA * item.cantidad).toFixed(2).replace('.', ',');
                html += '<tr style="border-bottom: 1px solid var(--border-main);">';
                html += '<td style="padding: 10px;">' + item.producto_nombre + '</td>';
                html += '<td style="padding: 10px; text-align: center;">' + item.cantidad + '</td>';
                html += '<td style="padding: 10px; text-align: right;">' + precioConIVA.toFixed(2).replace('.', ',') + ' €</td>';
                html += '<td style="padding: 10px; text-align: right; font-weight: 600; color: var(--accent);">' + subtotal + ' €</td>';
                html += '</tr>';
            });

            html += '</tbody></table>';
            html += '</div>';

            // Total
            html += '<div style="background: var(--accent); color: white; padding: 15px; border-radius: 8px; text-align: center; font-weight: bold; font-size: 18px;">';
            html += t('sale_details.total') + ': ' + parseFloat(venta.total).toFixed(2).replace('.', ',') + ' €';
            html += '</div>';

            contenido.innerHTML = html;
        })
        .catch(err => {
            console.error('Error:', err);
            contenido.innerHTML = '<p style="text-align: center; color: #dc2626; padding: 20px;">' + t('sale_details.error_loading') + '</p>';
        });
}

/**
 * Envía un ticket por correo electrónico
 * @param {number} idVenta 
 */
function enviarTicketCorreo(idVenta) {
    const email = prompt(t('email.prompt_ticket'));
    if (!email || !email.includes('@')) {
        alert(t('email.invalid_email'));
        return;
    }

    fetch('api/ventas.php?detalleVenta=' + idVenta)
        .then(res => res.json())
        .then(data => {
            if (data.error) { alert('Error: ' + data.error); return; }

            const venta = data.venta;
            const lineas = data.lineas;

            const payload = {
                ventaId: venta.serie + String(venta.numero || venta.id).padStart(5, '0').slice(-5),
                email: email,
                tipoDocumento: venta.tipoDocumento || 'ticket',
                lineas: lineas.map(item => ({
                    nombre: item.producto_nombre,
                    nombre_es: item.nombre_es,
                    nombre_en: item.nombre_en,
                    nombre_fr: item.nombre_fr,
                    nombre_de: item.nombre_de,
                    nombre_ru: item.nombre_ru,
                    cantidad: item.cantidad,
                    precio: item.precioUnitario,
                    iva: (item.iva !== undefined && item.iva !== null && item.iva !== "") ? parseInt(item.iva) : 21,
                    subtotal: item.subtotal
                })),
                total: venta.total,
                fecha: venta.fecha,
                metodoPago: venta.metodoPago,
                lang: venta.idioma_ticket || 'es',
                qrUrl: venta.qrUrl || ''
            };

            fetch('api/enviarCorreo.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
                .then(res => res.json())
                .then(response => {
                    alert(response.ok ? 'Ticket enviado correctamente al correo: ' + email : 'Error al enviar el correo');
                })
                .catch(err => {
                    console.error('Error:', err);
                    alert('Error al enviar el correo');
                });
        })
        .catch(err => {
            console.error('Error:', err);
            alert('Error al obtener los datos del ticket');
        });
}

/**
 * Muestra el modal de puntos del cliente
 */
function abrirModalPuntosCliente() {
    // Habilitar o deshabilitar el botón de canjear según si hay productos en el carrito
    const btnCanjear = document.getElementById('btnAplicarDescuentoPuntos');
    if (btnCanjear) {
        if (carrito.length === 0) {
            btnCanjear.disabled = true;
            btnCanjear.title = t('points.title_add_to_canjear');
            btnCanjear.style.opacity = '0.5';
            btnCanjear.style.cursor = 'not-allowed';
        } else {
            btnCanjear.disabled = false;
            btnCanjear.title = t('points.title_apply_descuento');
            btnCanjear.style.opacity = '1';
            btnCanjear.style.cursor = 'pointer';
        }
    }

    const modal = document.getElementById('modalPuntosCliente');
    if (modal) modal.style.display = 'flex';
    const input = document.getElementById('dniPuntosCliente');
    if (input) input.focus();
}

// Cache temporal para historial
var ventaHistorialTemporal = null;
var devolucionHistorialTemporal = null;

/**
 * Reimprime un ticket de una venta existente
 * @param {number} idVenta 
 */
function reimprimirTicket(idVenta) {
    fetch('api/ventas.php?detalleVenta=' + idVenta)
        .then(res => res.json())
        .then(data => {
            if (data.error) { alert('Error: ' + data.error); return; }
            const venta = data.venta;
            const lineas = data.lineas;

            // Usamos la variable global ultimaVenta esperada por imprimirDocumento()
            window.ultimaVenta = {
                id: venta.id,
                serie: venta.serie,
                numero: venta.numero,
                tipo: venta.tipoDocumento,
                total: parseFloat(venta.total),
                fecha: venta.fecha,
                metodoPago: venta.metodoPago,
                entregado: parseFloat(venta.importeEntregado) || 0,
                cambio: parseFloat(venta.cambioDevuelto) || 0,
                descuentoTipo: venta.descuentoTipo || 'ninguno',
                descuentoValor: parseFloat(venta.descuentoValor) || 0,
                descuentoCupon: venta.descuentoCupon || '',
                clienteNif: venta.cliente_dni || '',
                clienteNombre: venta.cliente_nombre || '',
                clienteDir: venta.cliente_direccion || '',
                clienteObs: venta.cliente_observaciones || '',
                puntosGanados: parseInt(venta.puntos_ganados) || 0,
                puntosCanjeados: (parseInt(venta.puntos_canjeados) > 0) ? {
                    puntos: parseInt(venta.puntos_canjeados),
                    descuento: parseFloat(venta.descuentoValor) || 0
                } : null,
                puntosBalance: parseInt(venta.puntos_balance) || 0,
                mensajePersonalizado: venta.mensaje_personalizado || '',
                idioma_ticket: venta.idioma_ticket || 'es',
                pagoMixtoDesglose: venta.desglose_pago ? JSON.parse(venta.desglose_pago) : null,
                qrUrl: venta.qrUrl || '',
                carrito: lineas.map(l => ({
                    idProducto: l.idProducto,
                    nombre: l.producto_nombre,
                    nombre_es: l.nombre_es,
                    nombre_en: l.nombre_en,
                    nombre_fr: l.nombre_fr,
                    nombre_de: l.nombre_de,
                    nombre_ru: l.nombre_ru,
                    precio: parseFloat(l.precioUnitario),
                    iva: (l.iva !== undefined && l.iva !== null && l.iva !== "") ? parseInt(l.iva) : 21,
                    cantidad: l.cantidad
                }))
            };
            imprimirDocumento();
        })
        .catch(err => {
            console.error('Error:', err);
            alert('Error al obtener los datos del ticket');
        });
}

/**
 * Muestra el modal para enviar un ticket por correo electrónico
 * @param {number} idVenta 
 */
function mostrarModalEnviarCorreo(idVenta) {
    fetch('api/ventas.php?detalleVenta=' + idVenta)
        .then(res => res.json())
        .then(data => {
            if (data.error) { alert('Error: ' + data.error); return; }
            const venta = data.venta;
            const lineas = data.lineas;

            window.ultimaVenta = {
                id: venta.id,
                serie: venta.serie,
                numero: venta.numero,
                tipo: venta.tipoDocumento,
                total: parseFloat(venta.total),
                fecha: venta.fecha,
                metodoPago: venta.metodoPago,
                entregado: parseFloat(venta.importeEntregado) || 0,
                cambio: parseFloat(venta.cambioDevuelto) || 0,
                descuentoTipo: venta.descuentoTipo || 'ninguno',
                descuentoValor: parseFloat(venta.descuentoValor) || 0,
                descuentoCupon: venta.descuentoCupon || '',
                clienteNif: venta.cliente_dni || '',
                clienteNombre: venta.cliente_nombre || '',
                clienteDir: venta.cliente_direccion || '',
                clienteObs: venta.cliente_observaciones || '',
                puntosGanados: parseInt(venta.puntos_ganados) || 0,
                puntosCanjeados: (parseInt(venta.puntos_canjeados) > 0) ? {
                    puntos: parseInt(venta.puntos_canjeados),
                    descuento: parseFloat(venta.descuentoValor) || 0
                } : null,
                puntosBalance: parseInt(venta.puntos_balance) || 0,
                idioma_ticket: venta.idioma_ticket || 'es',
                qrUrl: venta.qrUrl || '',
                carrito: lineas.map(l => ({
                    idProducto: l.idProducto,
                    nombre: l.producto_nombre,
                    nombre_es: l.nombre_es,
                    nombre_en: l.nombre_en,
                    nombre_fr: l.nombre_fr,
                    nombre_de: l.nombre_de,
                    nombre_ru: l.nombre_ru,
                    precio: parseFloat(l.precioUnitario),
                    iva: (l.iva !== undefined && l.iva !== null && l.iva !== "") ? parseInt(l.iva) : 21,
                    cantidad: l.cantidad
                }))
            };

            const ventaExito = document.getElementById('ventaExito');
            if (ventaExito) ventaExito.style.display = 'flex';
            if (typeof mostrarFormEmail === 'function') mostrarFormEmail();
        })
        .catch(err => {
            console.error('Error:', err);
            alert('Error al obtener los datos del ticket');
        });
}

// Variable para almacenar la devolución actual del historial

/**
 * Muestra el modal con el historial de devoluciones de hoy
 */
function mostrarHistorialDevoluciones() {
    const modal = document.getElementById('modalHistorialDevoluciones');
    const contenido = document.getElementById('historialDevolucionesContenido');
    const totalDiv = document.getElementById('historialDevolucionesTotal');
    const fechaDiv = document.getElementById('historialDevolucionesFecha');

    if (!modal || !contenido) return;

    // Actualizar la fecha en el subtítulo
    const hoy = new Date();
    if (fechaDiv) {
        fechaDiv.textContent = t('history.returns_subtitle') + ' - ' + hoy.toLocaleDateString('es-ES', { day: 'numeric', month: 'long', year: 'numeric' });
    }

    modal.style.display = 'flex';
    contenido.innerHTML = '<p style="text-align: center; padding: 40px;">' + t('history.loading') + '...</p>';

    fetch('api/devoluciones.php?historialSesion=1')
        .then(res => {
            if (!res.ok) throw new Error('HTTP error ' + res.status);
            return res.json();
        })
        .then(devoluciones => {
            if (devoluciones.error) {
                if (devoluciones.error.includes('No hay sesión')) {
                    contenido.innerHTML = '<p style="text-align: center; color: var(--text-muted); padding: 40px;">' + t('history.no_session') + '</p>';
                    if (totalDiv) totalDiv.textContent = '';
                    return;
                }
                throw new Error(devoluciones.error);
            }
            if (!devoluciones || devoluciones.length === 0) {
                contenido.innerHTML = '<p style="text-align: center; color: var(--text-muted); padding: 40px;">' + t('history.no_returns') + '</p>';
                if (totalDiv) totalDiv.textContent = t('history.total') + ': 0.00 €';
                return;
            }

            // Calcular total
            let total = 0;
            let html = '<table class="historial-ventas-tabla">';
            html += '<thead><tr>';
            html += '<th>' + t('history.time') + '</th>';
            html += '<th>' + t('history.user') + '</th>';
            html += '<th>' + t('history.products') + '</th>';
            html += '<th>' + t('history.payment_method') + '</th>';
            html += '<th>' + t('history.total_table') + '</th>';
            html += '<th>' + t('history.actions') + '</th>';
            html += '</tr></thead><tbody>';

            devoluciones.forEach(d => {
                const fecha = new Date(d.fecha);
                const hora = fecha.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
                const totalDevolucion = parseFloat(d.total);
                const numItems = d.numItems || 1;
                total += totalDevolucion;

                let formaPago = d.metodoPago || 'Efectivo';
                let usuario = d.usuario_nombre || 'Cajero';

                html += '<tr>';
                html += '<td>' + hora + '</td>';
                html += '<td>' + usuario + '</td>';
                html += '<td>' + numItems + '</td>';
                html += '<td>' + formaPago + '</td>';
                html += '<td style="font-weight: 600; color: #ef4444;">-' + totalDevolucion.toFixed(2).replace('.', ',') + ' €</td>';
                html += '<td>';
                html += '<div style="display: flex; gap: 5px; justify-content: center;">';
                html += '<button class="btn-exito" onclick="verDetalleDevolucion(' + d.idVenta + ')" title="Ver detalles" style="padding: 5px 10px; font-size: 12px;">👁️</button>';
                html += '<button class="btn-exito" onclick="reimprimirTicketDevolucionDesdeHistorial(' + d.idVenta + ')" title="Reimprimir ticket" style="padding: 5px 10px; font-size: 12px;">🖨️</button>';
                html += '</div>';
                html += '</td>';
                html += '</tr>';
            });

            html += '</tbody></table>';
            contenido.innerHTML = html;
            if (totalDiv) totalDiv.textContent = t('history.total_returned') + ': -' + total.toFixed(2).replace('.', ',') + ' € (' + devoluciones.length + ' ' + t('history.returns') + ')';
        })
        .catch(err => {
            console.error('Error cargando historial:', err);
            contenido.innerHTML = '<p style="text-align: center; color: #dc2626; padding: 40px;">' + t('history.error_loading') + ': ' + err.message + '</p>';
            if (totalDiv) totalDiv.textContent = '';
        });
}

/**
 * Muestra los detalles de una devolución específica en un modal
 * @param {number} idVenta 
 */
function verDetalleDevolucion(idVenta) {
    const modal = document.getElementById('modalDetalleDevolucion');
    const contenido = document.getElementById('detalleDevolucionContenido');

    if (!modal || !contenido) return;

    modal.style.display = 'flex';
    contenido.innerHTML = '<p style="text-align: center; padding: 20px;">' + t('return_details.loading') + '</p>';

    fetch('api/devoluciones.php?detalleVenta=' + idVenta)
        .then(res => res.json())
        .then(data => {
            if (data.error) {
                contenido.innerHTML = '<p style="text-align: center; color: #dc2626; padding: 20px;">' + t('return_details.error') + ': ' + data.error + '</p>';
                return;
            }

            if (!data || data.length === 0) {
                contenido.innerHTML = '<p style="text-align: center; color: var(--text-muted); padding: 20px;">' + t('return_details.not_found') + '</p>';
                return;
            }

            const primera = data[0];
            const fecha = new Date(primera.fecha).toLocaleString('es-ES');

            // Update header info
            const serie = primera.serie || 'T';
            const numero = primera.numero || primera.idVenta || idVenta;
            const detalleDevolucionId = document.getElementById('detalleDevolucionId');
            if (detalleDevolucionId) detalleDevolucionId.textContent = t('return_details.return_name') + ' ' + serie + String(numero).padStart(5, '0').slice(-5) + ' - ' + fecha;

            let html = '';

            // Info row
            html += '<div style="display: flex; gap: 15px; margin-bottom: 20px;">';
            html += '<div style="flex: 1; background: var(--bg-secondary); padding: 12px; border-radius: 8px;">';
            html += '<div style="font-size: 11px; color: var(--text-muted); margin-bottom: 4px;">' + t('return_details.payment') + '</div>';
            html += '<div style="font-weight: 600;">💵 ' + (primera.metodoPago || 'Efectivo') + '</div>';
            html += '</div>';
            html += '<div style="flex: 1; background: var(--bg-secondary); padding: 12px; border-radius: 8px;">';
            html += '<div style="font-size: 11px; color: var(--text-muted); margin-bottom: 4px;">' + t('return_details.reason') + '</div>';
            html += '<div style="font-weight: 600;">' + (primera.motivo || t('return_details.no_reason')) + '</div>';
            html += '</div>';
            html += '</div>';

            // Products table
            html += '<div style="max-height: 250px; overflow-y: auto; margin-bottom: 20px;">';
            html += '<table style="width: 100%; border-collapse: collapse;">';
            html += '<thead><tr style="background: var(--bg-secondary);">';
            html += '<th style="padding: 10px; text-align: left; font-size: 12px; color: var(--text-muted);">' + t('return_details.product') + '</th>';
            html += '<th style="padding: 10px; text-align: center; font-size: 12px; color: var(--text-muted);">' + t('return_details.quantity') + '</th>';
            html += '<th style="padding: 10px; text-align: right; font-size: 12px; color: var(--text-muted);">' + t('return_details.price') + '</th>';
            html += '<th style="padding: 10px; text-align: right; font-size: 12px; color: var(--text-muted);">' + t('return_details.amount') + '</th>';
            html += '</tr></thead><tbody>';

            let totalDevolucion = 0;
            data.forEach(item => {
                const subtotal = parseFloat(item.importeTotal || 0);
                totalDevolucion += subtotal;
                html += '<tr style="border-bottom: 1px solid var(--border-main);">';
                html += '<td style="padding: 10px;">' + (item.producto_nombre || t('return_details.product_default')) + '</td>';
                html += '<td style="padding: 10px; text-align: center;">' + item.cantidad + '</td>';
                html += '<td style="padding: 10px; text-align: right;">' + parseFloat(item.precioUnitario || 0).toFixed(2).replace('.', ',') + ' €</td>';
                html += '<td style="padding: 10px; text-align: right; font-weight: 600; color: #ef4444;">-' + subtotal.toFixed(2).replace('.', ',') + ' €</td>';
                html += '</tr>';
            });

            html += '</tbody></table>';
            html += '</div>';

            // Total
            html += '<div style="background: #ef4444; color: white; padding: 15px; border-radius: 8px; text-align: center; font-weight: bold; font-size: 18px;">';
            html += t('return_details.total_returned') + ': -' + totalDevolucion.toFixed(2).replace('.', ',') + ' €';
            html += '</div>';

            contenido.innerHTML = html;
        })
        .catch(err => {
            console.error('Error:', err);
            contenido.innerHTML = '<p style="text-align: center; color: #dc2626; padding: 20px;">' + t('return_details.error_loading') + '</p>';
        });
}

/**
 * Reimprime un ticket de devolución desde el historial
 * @param {number} idVenta 
 */
function reimprimirTicketDevolucionDesdeHistorial(idVenta) {
    fetch('api/devoluciones.php?detalleVenta=' + idVenta + '&_=' + Date.now(), {
        cache: 'no-store',
        headers: { 'Cache-Control': 'no-cache' }
    })
        .then(res => res.json())
        .then(data => {
            if (data.error) { alert('Error: ' + data.error); return; }
            if (!data || data.length === 0) { alert(t('return_details.alert_not_found')); return; }

            const primera = data[0];
            const orig_serie = primera.orig_serie || '';
            const orig_numero = primera.orig_numero || '';

            if (!orig_numero) {
                console.warn('No se encontró el número de ticket original para la devolución', idVenta);
            }

            const devolucion = {
                id: idVenta,
                orig_serie: orig_serie,
                orig_numero: orig_numero,
                serie: primera.rect_serie || 'D',
                numero: primera.rect_numero || '',
                qrUrl: primera.qrUrl || null,
                fecha: primera.fecha,
                metodoPago: primera.metodoPago || 'Efectivo',
                total: data.reduce((sum, item) => sum + parseFloat(item.importeTotal || 0), 0),
                motivo: primera.motivo || '',
                lineas: data.map(l => ({
                    idProducto: l.idProducto,
                    nombre: l.producto_nombre || 'Producto',
                    cantidad: l.cantidad,
                    precioUnitario: l.precioUnitario,
                    importeTotal: l.importeTotal,
                    iva: (l.iva !== undefined && l.iva !== null && l.iva !== "") ? parseInt(l.iva) : 21
                }))
            };
            imprimirDocumentoDevolucionConDatos(devolucion);
        })
        .catch(err => {
            console.error('Error:', err);
            alert('Error al obtener los datos del ticket');
        });
}

/**
 * Imprime ticket de devolucion recibiendo el objeto de devolucion directamente
 * @param {Object} devolucion 
 */
function imprimirDocumentoDevolucionConDatos(devolucion) {
    if (!devolucion) {
        alert('No hay datos de devolución para imprimir');
        return;
    }

    const carrito = (devolucion.lineas || []).map(linea => ({
        nombre: linea.nombre || linea.producto_nombre || 'Producto',
        cantidad: linea.cantidad,
        precio: parseFloat(linea.precioUnitario || linea.precio) || 0,
        iva: (linea.iva !== undefined) ? parseInt(linea.iva) : 21,
        importeTotal: parseFloat(linea.importeTotal || linea.importe) || 0
    }));

    const totalGeneral = carrito.reduce((sum, item) => sum + item.importeTotal, 0);

    const datosVenta = {
        id: devolucion.numero || devolucion.id || '—',
        serie: devolucion.serie || 'D',
        numero: devolucion.numero || devolucion.id || '—',
        fecha: new Date(devolucion.fecha).toLocaleString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' }),
        tipo: 'ticket',
        es_rectificativa: true,
        id_original: devolucion.orig_numero || devolucion.idVenta,
        serie_original: devolucion.orig_serie || devolucion.serie_original || 'T',
        total: -totalGeneral,
        metodoPago: devolucion.metodoPago,
        carrito: carrito,
        usuario_nombre: devolucion.usuario_nombre,
        qrUrl: devolucion.qrUrl
    };

    if (typeof generarHTMLComprobante === 'function') {
        const html = generarHTMLComprobante(datosVenta, 'es');
        const printWindow = window.open('', '_blank', 'width=400,height=600');
        if (printWindow) {
            printWindow.document.write(html);
            printWindow.document.close();
            printWindow.focus();
            setTimeout(() => {
                printWindow.print();
                printWindow.close();
            }, 500);
        }
    } else {
        console.error('generarHTMLComprobante not found');
    }
}

/**
 * Imprime el ticket de la última devolución realizada
 */
function imprimirTicketDevolucion() {
    if (typeof ultimaDevolucion === 'undefined' || !ultimaDevolucion) return;
    imprimirDocumentoDevolucionConDatos(ultimaDevolucion);
}

/**
 * Muestra el modal de apertura de caja
 */
function mostrarModalAbrirCaja() {
    const modal = document.getElementById('modalAbrirCaja');
    const recovery = document.getElementById('cambioRecovery');
    const importeInput = document.getElementById('importeInicial');
    const divImporte = document.getElementById('divImporteInicial');
    const radioRecuperar = document.querySelector('input[name="opcionCambio"][value="recuperar"]');

    if (!modal) return;

    modal.style.display = 'flex';
    if (recovery) recovery.value = '0';

    if (importeInput) {
        importeInput.value = '';
        importeInput.required = false;
    }

    if (divImporte) {
        divImporte.style.opacity = '0.5';
    }

    if (radioRecuperar) {
        radioRecuperar.checked = true;
        toggleCambio(false);
    }

    if (importeInput) importeInput.focus();
}

/**
 * Alterna entre recuperar el cambio anterior o introducir un importe inicial nuevo
 * @param {boolean} mostrarNuevo 
 */
function toggleCambio(mostrarNuevo) {
    const divImporte = document.getElementById('divImporteInicial');
    const importeInput = document.getElementById('importeInicial');
    const cambioInput = document.getElementById('cambioRecovery');

    if (!divImporte || !importeInput || !cambioInput) return;

    if (mostrarNuevo) {
        divImporte.style.opacity = '1';
        importeInput.required = true;
        cambioInput.value = '0';
    } else {
        divImporte.style.opacity = '0.5';
        importeInput.required = false;
        // El cambio anterior se inyecta en TPV_CONTEXT
        const cambioAnterior = (typeof TPV_CONTEXT !== 'undefined') ? TPV_CONTEXT.cambioAnterior : 0;
        cambioInput.value = cambioAnterior;
    }
}

/**
 * Muestra el modal de retiro de dinero
 */
function mostrarModalRetiro() {
    const modal = document.getElementById('modalRetiro');
    const input = document.getElementById('importeRetiro');
    if (modal) {
        modal.style.display = 'flex';
        if (input) input.focus();
    }
}

/**
 * Valida que el importe a retirar sea correcto y no exceda el efectivo disponible
 * @returns {boolean}
 */
function validarRetiro() {
    const importeInput = document.getElementById('importeRetiro');
    if (!importeInput) return false;

    const importe = parseFloat(importeInput.value);
    const efectivoDisponible = (typeof TPV_CONTEXT !== 'undefined') ? TPV_CONTEXT.efectivoActualCaja : 0;

    if (isNaN(importe) || importe <= 0) {
        alert(t('cashier.alert_valid_amount'));
        return false;
    }

    if (importe > efectivoDisponible) {
        alert(t('cashier.alert_insufficient_cash') + ': ' + efectivoDisponible.toFixed(2).replace('.', ',') + ' €');
        return false;
    }

    return true;
}

/**
 * Actualiza la UI de los métodos de pago en el modal de devolución
 * @param {HTMLInputElement} radio 
 */
function updateMethodUI(radio) {
    document.querySelectorAll('.method-chip').forEach(chip => {
        chip.style.border = '2px solid var(--border-main)';
        chip.style.color = 'var(--text-muted)';
        chip.style.fontWeight = '400';
        chip.classList.remove('active');
    });

    const chip = document.getElementById('chip-' + radio.value);
    if (chip) {
        chip.style.border = '2px solid var(--accent-danger)';
        chip.style.color = 'var(--accent-danger)';
        chip.style.fontWeight = '600';
        chip.classList.add('active');
    }
}

// Variables para el flujo de devoluciones
var ticketActualDevolucion = null;
var lineasVentaDevolucion = [];
var cantidadesDevSeleccion = [];



/**
 * Muestra el modal de devolución
 */
function mostrarModalDevolucion() {
    const modal = document.getElementById('modalDevolucion');
    const input = document.getElementById('inputTicketIdDev');
    if (modal) {
        modal.style.display = 'flex';
        if (input) input.focus();
    }
}

/**
 * Busca un ticket para realizar una devolución
 */
function buscarTicketParaDevolucion() {
    const input = document.getElementById('inputTicketIdDev');
    const errorEl = document.getElementById('errorTicketDev');
    if (!input || !errorEl) return;

    const ticketId = input.value.trim();

    if (!ticketId) {
        errorEl.textContent = t('returns.error_no_ticket');
        errorEl.style.display = 'block';
        return;
    }

    errorEl.style.display = 'none';

    // Parsear serie y número (ej: T00001)
    let serie = '';
    let numero = ticketId;
    const match = ticketId.match(/^([TF]?)0*(\d+)$/i);
    if (match) {
        serie = match[1].toUpperCase();
        numero = match[2];
    }

    let url = `api/ventas.php?checkVentaDevolucion=${numero}`;
    if (serie) url += `&serie=${serie}`;

    fetch(url)
        .then(res => res.json())
        .then(data => {
            if (data.error) {
                errorEl.textContent = 'Error: ' + data.error;
                errorEl.style.display = 'block';
                return;
            }

            ticketActualDevolucion = data.venta;
            lineasVentaDevolucion = data.lineas;
            cantidadesDevSeleccion = lineasVentaDevolucion.map(function () { return 0; });

            const totalDisponible = lineasVentaDevolucion.reduce((acc, linea) => {
                return acc + (parseInt(linea.cantidad) - (parseInt(linea.cantidad_devuelta) || 0));
            }, 0);

            if (totalDisponible <= 0) {
                errorEl.textContent = t('returns.error_no_products_avail');
                errorEl.style.display = 'block';
                return;
            }

            // UI Transitions
            document.getElementById('devolucionPaso1').style.display = 'none';
            document.getElementById('devolucionPaso2').style.display = 'block';
            document.getElementById('btnConfirmarMultiDev').style.display = 'block';
            document.getElementById('resumenReembolso').style.display = 'block';
            document.getElementById('devolucionSubtitulo').textContent = t('returns.subtitle_select_units');

            const serieVenta = ticketActualDevolucion.serie || 'T';
            const tipoDoc = serieVenta === 'F' ? t('print.factura') : t('print.ticket');
            const numFmt = String(ticketActualDevolucion.numero || ticketActualDevolucion.id).padStart(5, '0');
            const precGlobal = Math.max(2, ...lineasVentaDevolucion.map(l => l.decimales || 2));

            document.getElementById('infoTicketId').textContent = tipoDoc + ' ' + serieVenta + numFmt;
            document.getElementById('infoTicketFecha').textContent = new Date(ticketActualDevolucion.fecha).toLocaleString('es-ES');
            document.getElementById('infoTicketTotal').textContent = parseFloat(ticketActualDevolucion.total).toFixed(precGlobal).replace('.', ',') + ' €';
            document.getElementById('totalOriginalDisplay').textContent = parseFloat(ticketActualDevolucion.total).toFixed(precGlobal).replace('.', ',') + ' €';

            renderizarTablaDevolucion();
        })
        .catch(err => {
            console.error(err);
            errorEl.textContent = t('returns.error_connection');
            errorEl.style.display = 'block';
        });
}

// Variables de paginación para la tabla de devolución
var paginaActualDev = 1;
var productosPorPaginaDev = 4;

/**
 * Renderiza la tabla de productos para la devolución (paginada)
 */
function renderizarTablaDevolucion() {
    const tbody = document.getElementById('tablaProductosDev');
    if (!tbody) return;

    tbody.innerHTML = '';

    const totalItems = lineasVentaDevolucion.length;
    const totalPaginas = Math.max(1, Math.ceil(totalItems / productosPorPaginaDev));
    if (paginaActualDev > totalPaginas) paginaActualDev = totalPaginas;

    const inicio = (paginaActualDev - 1) * productosPorPaginaDev;
    const fin = Math.min(inicio + productosPorPaginaDev, totalItems);
    const paginaLineas = lineasVentaDevolucion.slice(inicio, fin);

    paginaLineas.forEach((linea, idx) => {
        const index = inicio + idx;
        const disponible = parseInt(linea.cantidad) - (parseInt(linea.cantidad_devuelta) || 0);
        const precio = linea.precioConIva ? parseFloat(linea.precioConIva) : parseFloat(linea.precioUnitario) * (1 + (linea.iva || 21) / 100);
        const dec = linea.decimales || 2;
        const valorActual = (typeof cantidadesDevSeleccion !== 'undefined' && cantidadesDevSeleccion[index] !== undefined) ? cantidadesDevSeleccion[index] : 0;

        const tr = document.createElement('tr');
        tr.style.borderBottom = '1px solid #f1f5f9';
        tr.innerHTML = `
            <td style="padding: 12px 15px;">
                <div class="producto-nombre-dev" style="font-weight: 600;">${linea.producto_nombre}</div>
                <div class="producto-precio-dev" style="font-size: 0.75rem;">Precio: ${precio.toFixed(dec)} € (IVA incl.)</div>
            </td>
            <td style="padding: 12px; text-align: center; color: #64748b; font-weight: 500;">${disponible}</td>
            <td style="padding: 12px 15px; text-align: center;">
                <div class="cantidad-control" style="justify-content: center;">
                    <button onclick="cambiarCantidadDev(${index}, -1)" ${disponible <= 0 ? 'disabled' : ''}>−</button>
                    <input type="number" class="cant-dev-input" 
                        data-index="${index}" 
                        min="0" max="${disponible}" value="${valorActual}" 
                        style="width: 50px; text-align: center; padding: 5px; border: 1px solid #e2e8f0; border-radius: 4px;"
                        onchange="cambiarCantidadDev(${index}, 0)"
                        ${disponible <= 0 ? 'disabled' : ''}>
                    <button onclick="cambiarCantidadDev(${index}, 1)" ${disponible <= 0 ? 'disabled' : ''}>+</button>
                </div>
            </td>
        `;
        tbody.appendChild(tr);
    });

    renderizarPaginacionDevolucion(totalPaginas);
    recalcularTotalReembolso();
}

/**
 * Renderiza los controles de paginación para la tabla de devolución
 */
function renderizarPaginacionDevolucion(totalPaginas) {
    const contenedor = document.getElementById('paginacionDevolucion');
    if (!contenedor) return;

    if (totalPaginas <= 1) {
        contenedor.innerHTML = '';
        return;
    }

    let html = '';
    const pagina = paginaActualDev;

    if (pagina > 1) {
        html += `<button class="btn-paginacion" onclick="irAPaginaDev(1)" title="Primera página"><i class="fas fa-angle-double-left"></i></button>`;
        html += `<button class="btn-paginacion" onclick="irAPaginaDev(${pagina - 1})" title="Página anterior"><i class="fas fa-chevron-left"></i></button>`;
    }

    html += `<div class="input-paginacion">
        <input type="number" id="inputPaginaDev" class="input-numero-pagina"
            value="${pagina}" min="1" max="${totalPaginas}"
            onfocus="ajustarAnchoInput(this)" oninput="ajustarAnchoInput(this)"
            onblur="irAPaginaDevInput(this)"
            onkeypress="if(event.key==='Enter') irAPaginaDevInput(this)">
        <span class="info-paginacion"> de ${totalPaginas}</span>
    </div>`;

    if (pagina < totalPaginas) {
        html += `<button class="btn-paginacion" onclick="irAPaginaDev(${pagina + 1})" title="Siguiente página"><i class="fas fa-chevron-right"></i></button>`;
        html += `<button class="btn-paginacion" onclick="irAPaginaDev(${totalPaginas})" title="Última página"><i class="fas fa-angle-double-right"></i></button>`;
    }

    contenedor.innerHTML = `<div class="admin-paginacion-wrapper"><div class="admin-paginacion">${html}</div></div>`;

    setTimeout(function () {
        var input = document.getElementById('inputPaginaDev');
        if (input) ajustarAnchoInput(input);
    }, 50);
}

/**
 * Navega a una página específica en la tabla de devolución
 */
function irAPaginaDev(pagina) {
    const totalPaginas = Math.max(1, Math.ceil(lineasVentaDevolucion.length / productosPorPaginaDev));
    if (pagina < 1) pagina = 1;
    if (pagina > totalPaginas) pagina = totalPaginas;
    paginaActualDev = pagina;
    renderizarTablaDevolucion();
}

/**
 * Maneja la navegación desde el input de página al perder el foco o presionar Enter
 */
function irAPaginaDevInput(input) {
    if (!input) return;
    let pagina = parseInt(input.value);
    if (!pagina || pagina < 1) pagina = 1;
    const totalPaginas = Math.max(1, Math.ceil(lineasVentaDevolucion.length / productosPorPaginaDev));
    if (pagina > totalPaginas) pagina = totalPaginas;
    irAPaginaDev(pagina);
}

/**
 * Ajusta el ancho de un input de paginación al contenido (fallback si admin-utils.js no está cargado)
 */
function ajustarAnchoInput(input) {
    if (!input) return;
    const tempSpan = document.createElement('span');
    tempSpan.style.cssText = 'visibility:hidden;position:absolute;';
    tempSpan.style.font = window.getComputedStyle(input).font;
    tempSpan.style.padding = '0 5px';
    tempSpan.textContent = input.value || '0';
    document.body.appendChild(tempSpan);
    input.style.width = (tempSpan.offsetWidth + 15) + 'px';
    document.body.removeChild(tempSpan);
}

/**
 * Selecciona o deselecciona todos los productos disponibles para devolver
 */
function seleccionarTodosProductos() {
    // Verificar si todos los productos ya están seleccionados (basado en el array, no en el DOM)
    let allSelected = true;
    for (let i = 0; i < lineasVentaDevolucion.length; i++) {
        const linea = lineasVentaDevolucion[i];
        const disponible = parseInt(linea.cantidad) - (parseInt(linea.cantidad_devuelta) || 0);
        if ((cantidadesDevSeleccion[i] || 0) !== disponible) {
            allSelected = false;
            break;
        }
    }

    // Actualizar TODOS los productos en el array
    lineasVentaDevolucion.forEach((linea, index) => {
        const disponible = parseInt(linea.cantidad) - (parseInt(linea.cantidad_devuelta) || 0);
        cantidadesDevSeleccion[index] = allSelected ? 0 : disponible;
    });

    renderizarTablaDevolucion();
}

/**
 * Cambia la cantidad a devolver de un producto
 * @param {number} index 
 * @param {number} delta 
 */
function cambiarCantidadDev(index, delta) {
    const input = document.querySelector('.cant-dev-input[data-index="' + index + '"]');
    if (!input) return;

    const linea = lineasVentaDevolucion[index];
    const disponible = parseInt(linea.cantidad) - (parseInt(linea.cantidad_devuelta) || 0);

    let cant = (parseInt(input.value) || 0) + delta;
    if (cant < 0) cant = 0;
    if (cant > disponible) cant = disponible;

    input.value = cant;
    if (typeof cantidadesDevSeleccion !== 'undefined') {
        cantidadesDevSeleccion[index] = cant;
    }
    recalcularTotalReembolso();
}

/**
 * Recalcula el total del reembolso aplicando descuentos proporcionales
 */
function recalcularTotalReembolso() {
    const precDevTotal = 2;
    let total = 0;
    let hayDevolucion = false;
    const inputs = document.querySelectorAll('.cant-dev-input');
    const venta = ticketActualDevolucion;
    if (!venta) return;

    const ventaTotal = parseFloat(venta.total);

    let sumaBruta = 0;
    lineasVentaDevolucion.forEach(linea => {
        const precioBase = linea.precioConIva ? parseFloat(linea.precioConIva) : parseFloat(linea.precioUnitario) * (1 + (linea.iva || 21) / 100);
        sumaBruta += parseInt(linea.cantidad) * precioBase;
    });

    const factorDescuento = sumaBruta > 0 ? ventaTotal / sumaBruta : 1;

    lineasVentaDevolucion.forEach((linea, index) => {
        let cant = (typeof cantidadesDevSeleccion !== 'undefined' && cantidadesDevSeleccion[index] !== undefined) ? cantidadesDevSeleccion[index] : 0;

        if (cant > 0) {
            const precioBase = linea.precioConIva ? parseFloat(linea.precioConIva) : parseFloat(linea.precioUnitario) * (1 + (linea.iva || 21) / 100);
            total += cant * precioBase * factorDescuento;
            hayDevolucion = true;
        }
    });

    if (total > ventaTotal) total = ventaTotal;
    total = roundTo(total, precDevTotal);

    const display = document.getElementById('totalReembolsoDisplay');
    if (display) display.textContent = total.toFixed(precDevTotal).replace('.', ',') + ' €';

    const errorEl = document.getElementById('errorEfectivoInsuficiente');
    const btnConfirmar = document.getElementById('btnConfirmarMultiDev');
    const dispEl = document.getElementById('efectivoDisponibleDisplay');
    const efectivoActual = (typeof TPV_CONTEXT !== 'undefined') ? TPV_CONTEXT.efectivoActualCaja : 0;

    if (Math.round(total * 100) > Math.round(efectivoActual * 100)) {
        if (errorEl) errorEl.style.display = 'block';
        if (dispEl) dispEl.textContent = efectivoActual.toFixed(precDevTotal).replace('.', ',') + ' €';
        if (btnConfirmar) {
            btnConfirmar.disabled = true;
            btnConfirmar.style.opacity = '0.5';
            btnConfirmar.style.cursor = 'not-allowed';
        }
    } else {
        if (errorEl) errorEl.style.display = 'none';
        if (btnConfirmar) {
            btnConfirmar.disabled = !hayDevolucion;
            btnConfirmar.style.opacity = hayDevolucion ? '1' : '0.5';
            btnConfirmar.style.cursor = hayDevolucion ? 'pointer' : 'not-allowed';
        }
    }
}

/**
 * Cierra el modal de devolución y resetea su estado
 */
function cerrarModalDevolucion() {
    cerrarModal('modalDevolucion');
    document.getElementById('devolucionPaso1').style.display = 'block';
    document.getElementById('devolucionPaso2').style.display = 'none';
    document.getElementById('btnConfirmarMultiDev').style.display = 'none';
    document.getElementById('resumenReembolso').style.display = 'none';
    document.getElementById('devolucionSubtitulo').textContent = 'Introduce el número del Ticket (ej: T00001)';
    const input = document.getElementById('inputTicketIdDev');
    if (input) input.value = '';
    const error = document.getElementById('errorTicketDev');
    if (error) error.style.display = 'none';
    const display = document.getElementById('totalOriginalDisplay');
    if (display) display.textContent = '0,00 €';
    lineasVentaDevolucion = [];
    cantidadesDevSeleccion = [];
    ticketActualDevolucion = null;
}

/**
 * Procesa la devolución enviando los datos al servidor
 */
function procesarMultiDevolucion() {
    const inputs = document.querySelectorAll('.cant-dev-input');
    let productosDev = [];
    let totalReembolso = 0;

    if (!ticketActualDevolucion) return;

    lineasVentaDevolucion.forEach((linea, index) => {
        const cant = (typeof cantidadesDevSeleccion !== 'undefined' && cantidadesDevSeleccion[index] !== undefined) ? cantidadesDevSeleccion[index] : 0;
        if (cant > 0) {
            const dec = linea.decimales || 2;
            const precioConIva = linea.precioConIva ? parseFloat(linea.precioConIva) : parseFloat(linea.precioUnitario) * (1 + (linea.iva || 21) / 100);

            productosDev.push({
                idProducto: linea.idProducto,
                nombreProducto: linea.producto_nombre,
                idLineaOriginal: linea.id,
                cantidad: cant,
                importe: roundTo(cant * precioConIva, dec),
                decimales: dec
            });
        }
    });

    if (productosDev.length === 0) return;

    const ventaTotal = parseFloat(ticketActualDevolucion.total);
    let sumaBruta = lineasVentaDevolucion.reduce((sum, l) => {
        const p = l.precioConIva ? parseFloat(l.precioConIva) : parseFloat(l.precioUnitario) * (1 + (l.iva || 21) / 100);
        return sum + (parseInt(l.cantidad) * p);
    }, 0);

    const factorDescuento = sumaBruta > 0 ? ventaTotal / sumaBruta : 1;

    productosDev = productosDev.map(p => ({
        ...p,
        importe: roundTo(p.importe * factorDescuento, p.decimales || 2)
    }));

    totalReembolso = productosDev.reduce((sum, p) => sum + p.importe, 0);

    if (totalReembolso > ventaTotal) {
        const factorAjuste = ventaTotal / totalReembolso;
        productosDev = productosDev.map(p => ({
            ...p,
            importe: roundTo(p.importe * factorAjuste, p.decimales || 2)
        }));
        totalReembolso = ventaTotal;
    }

    const metodoRadio = document.querySelector('input[name="metodoPagoDev"]:checked');
    const metodoPago = metodoRadio ? metodoRadio.value : 'efectivo';

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'index.php';

    const params = {
        accion: 'tramitarMultiDevolucion',
        motivo: document.getElementById('motivoDevolucionDev')?.value.trim() || '',
        idVenta: ticketActualDevolucion.id,
        metodoPago: metodoPago,
        productos: JSON.stringify(productosDev),
        totalReembolso: totalReembolso
    };

    for (const key in params) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = key;
        input.value = params[key];
        form.appendChild(input);
    }

    document.body.appendChild(form);
    form.submit();
}

/**
 * Limita el input a un máximo de N decimales en tiempo real.
 * @param {HTMLInputElement} input
 * @param {string} limitInputId
 */
function validarPrecisionDinamica(input, limitInputId) {
    let limitInput = document.getElementById(limitInputId);
    let limit = limitInput ? parseInt(limitInput.value) : 4;
    if (isNaN(limit)) limit = 4;
    if (limit > 4) limit = 4;
    if (limit < 0) limit = 0;

    let value = input.value;
    if (!value) return;

    // Use regex to keep only up to N decimals
    let regex = new RegExp('^-?\\d*(\\.\\d{0,' + limit + '})?');
    let match = value.match(regex);
    if (match && match[0] !== value) {
        input.value = match[0];
    }
}

/**
 * Abre el modal para crear un producto comodín
 */
function abrirModalProductoComodin() {
    const modal = document.getElementById('modalProductoComodin');
    const nombre = document.getElementById('comodinNombre');
    const desc = document.getElementById('comodinDescripcion');
    const precio = document.getElementById('comodinPrecio');
    const iva = document.getElementById('comodinIva');

    if (modal) modal.style.display = 'flex';
    if (nombre) nombre.value = '';
    if (desc) desc.value = '';
    if (precio) precio.value = '';
    if (iva) iva.value = '21';

    actualizarComodinPrecioTotal();
    if (nombre) nombre.focus();
}

/**
 * Calcula el precio total con IVA en tiempo real para el producto comodín
 */
function actualizarComodinPrecioTotal() {
    const precioEl = document.getElementById('comodinPrecio');
    const ivaEl = document.getElementById('comodinIva');
    const totalEl = document.getElementById('comodinPrecioTotal');

    if (!precioEl || !ivaEl || !totalEl) return;

    const precioBase = parseFloat(precioEl.value) || 0;
    const iva = parseFloat(ivaEl.value) || 0;
    const total = precioBase * (1 + (iva / 100));

    totalEl.textContent = total.toLocaleString('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' €';
}

/**
 * Crea un producto temporal y lo añade al carrito
 */
function agregarProductoComodin() {
    const nombreEl = document.getElementById('comodinNombre');
    const descEl = document.getElementById('comodinDescripcion');
    const precioEl = document.getElementById('comodinPrecio');
    const ivaEl = document.getElementById('comodinIva');

    if (!nombreEl || !precioEl || !ivaEl) return;

    const nombre = nombreEl.value.trim();
    const descripcion = descEl ? descEl.value.trim() : '';
    const precioBase = parseFloat(precioEl.value);
    const ivaPorcentaje = parseFloat(ivaEl.value);

    // Validaciones
    if (!nombre) {
        alert(t('products.alert_enter_name'));
        nombreEl.focus();
        return;
    }

    if (nombre.length > 26) {
        alert(t('products.alert_name_too_long') || 'El nombre no puede tener más de 26 caracteres');
        nombreEl.focus();
        return;
    }

    if (isNaN(precioBase) || precioBase < 0) {
        alert(t('products.alert_enter_valid_price'));
        precioEl.focus();
        return;
    }

    if (isNaN(ivaPorcentaje) || 0 > ivaPorcentaje) {
        alert(t('products.alert_enter_valid_iva'));
        ivaEl.focus();
        return;
    }

    // Calcular precio con IVA
    const precioConIva = Math.round(precioBase * (1 + (ivaPorcentaje / 100)) * 100) / 100;

    // Crear objeto producto comodín
    const productoComodin = {
        idProducto: 'comodin_' + Date.now(),
        nombre: nombre,
        descripcion: descripcion,
        precio: precioBase,
        pvpUnitario: precioConIva,
        pvpOriginalUnitario: precioConIva,
        cantidad: 1,
        iva: ivaPorcentaje,
        tarifaNombre: t('cart.client_default_tarifa'),
        stockMax: 999,
        esComodin: true
    };

    // Añadir al carrito
    if (typeof carrito !== 'undefined') {
        const indiceExistente = carrito.findIndex(item =>
            item.esComodin &&
            item.nombre.toLowerCase() === nombre.toLowerCase() &&
            parseFloat(item.precio) === precioBase &&
            parseFloat(item.iva) === ivaPorcentaje
        );

        if (indiceExistente >= 0) {
            carrito[indiceExistente].cantidad += 1;
        } else {
            carrito.push(productoComodin);
        }

        if (typeof actualizarTicket === 'function') actualizarTicket();
        cerrarModal('modalProductoComodin');
    }
}

/**
 * Calcula el arqueo de caja en tiempo real
 */
function calcularArqueo() {
    let total = 0;

    // Calcular total de billetes
    document.querySelectorAll('.arqueo-billete').forEach(input => {
        const cantidad = parseInt(input.value) || 0;
        const denominacion = parseFloat(input.dataset.denominacion);
        total += cantidad * denominacion;
    });

    // Calcular total de monedas
    document.querySelectorAll('.arqueo-moneda').forEach(input => {
        const cantidad = parseInt(input.value) || 0;
        const denominacion = parseFloat(input.dataset.denominacion);
        total += cantidad * denominacion;
    });

    // Redondear a 2 decimales
    total = Math.round(total * 100) / 100;

    // Obtener el efectivo esperado
    const arqueoEsperado = document.getElementById('arqueoEsperado');
    if (!arqueoEsperado) return;

    const efectivoEsperadoStr = arqueoEsperado.textContent.replace('€', '').trim();
    const efectivoEsperado = parseFloat(efectivoEsperadoStr.replace(/\./g, '').replace(',', '.')) || 0;

    // Calcular diferencia
    const diferencia = Math.round((total - efectivoEsperado) * 100) / 100;

    // Actualizar displays
    const arqueoContado = document.getElementById('arqueoContado');
    if (arqueoContado) arqueoContado.textContent = total.toFixed(2).replace('.', ',') + ' €';

    const diffElement = document.getElementById('arqueoDiferencia');
    if (diffElement) {
        diffElement.textContent = (diferencia >= 0 ? '+' : '') + diferencia.toFixed(2).replace('.', ',') + ' €';

        // Cambiar color según la diferencia
        if (diferencia === 0) {
            diffElement.style.color = '#059669';
            diffElement.textContent = '✓ ' + t('cash_count.correct');
        } else if (diferencia > 0) {
            diffElement.style.color = '#2563eb';
            diffElement.textContent = '+' + diferencia.toFixed(2).replace('.', ',') + ' € (' + t('cash_count.surplus') + ')';
        } else {
            diffElement.style.color = '#dc2626';
            diffElement.textContent = diferencia.toFixed(2).replace('.', ',') + ' € (' + t('cash_count.shortage') + ')';
        }
    }

    // Guardar el total en campos ocultos si existen
    const arqueoTotalContado = document.getElementById('arqueoTotalContado');
    if (arqueoTotalContado) arqueoTotalContado.value = total;

    const arqueoDetalleConteo = document.getElementById('arqueoDetalleConteo');
    if (arqueoDetalleConteo) {
        const detalle = {};
        document.querySelectorAll('.arqueo-billete').forEach(input => {
            detalle['billete_' + input.dataset.denominacion] = parseInt(input.value) || 0;
        });
        document.querySelectorAll('.arqueo-moneda').forEach(input => {
            detalle['moneda_' + input.dataset.denominacion] = parseInt(input.value) || 0;
        });
        arqueoDetalleConteo.value = JSON.stringify(detalle);
    }
}

/**
 * Continúa del arqueo al resumen de caja
 */
function continuarArqueo() {
    // Calcular el total del arqueo
    let total = 0;
    document.querySelectorAll('.arqueo-billete').forEach(input => {
        total += (parseInt(input.value) || 0) * parseFloat(input.dataset.denominacion);
    });
    document.querySelectorAll('.arqueo-moneda').forEach(input => {
        total += (parseInt(input.value) || 0) * parseFloat(input.dataset.denominacion);
    });
    total = Math.round(total * 100) / 100;

    // Obtener el efectivo esperado
    const arqueoEsperado = document.getElementById('arqueoEsperado');
    if (!arqueoEsperado) return;

    const efectivoEsperadoStr = arqueoEsperado.textContent.replace('€', '').trim();
    const efectivoEsperado = parseFloat(efectivoEsperadoStr.replace(/\./g, '').replace(',', '.')) || 0;

    // Calcular diferencia
    const diferencia = Math.round((total - efectivoEsperado) * 100) / 100;

    // Crear detalle del conteo
    const detalle = {};
    document.querySelectorAll('.arqueo-billete').forEach(input => {
        detalle['billete_' + input.dataset.denominacion] = parseInt(input.value) || 0;
    });
    document.querySelectorAll('.arqueo-moneda').forEach(input => {
        detalle['moneda_' + input.dataset.denominacion] = parseInt(input.value) || 0;
    });

    // Actualizar los campos hidden del formulario de confirmación
    const arqueoTotalForm = document.getElementById('arqueoTotalContadoForm');
    const arqueoDetalleForm = document.getElementById('arqueoDetalleConteoForm');
    if (arqueoTotalForm) arqueoTotalForm.value = total.toFixed(2);
    if (arqueoDetalleForm) arqueoDetalleForm.value = JSON.stringify(detalle);

    // Actualizar la visualización del arqueo en el resumen
    const arqueoContadoResumen = document.getElementById('arqueoContadoResumen');
    const arqueoDiferenciaResumen = document.getElementById('arqueoDiferenciaResumen');
    if (arqueoContadoResumen) {
        arqueoContadoResumen.textContent = total.toFixed(2).replace('.', ',') + ' €';
    }
    if (arqueoDiferenciaResumen) {
        if (diferencia === 0) {
            arqueoDiferenciaResumen.textContent = '✓ ' + t('cash_count.correct');
            arqueoDiferenciaResumen.style.color = '#059669';
        } else if (diferencia > 0) {
            arqueoDiferenciaResumen.textContent = '+' + diferencia.toFixed(2).replace('.', ',') + ' € (' + t('cash_count.surplus') + ')';
            arqueoDiferenciaResumen.style.color = '#2563eb';
        } else {
            arqueoDiferenciaResumen.textContent = diferencia.toFixed(2).replace('.', ',') + ' € (' + t('cash_count.shortage') + ')';
            arqueoDiferenciaResumen.style.color = '#dc2626';
        }
    }

    // Copiar observaciones si existen
    const obs = document.getElementById('arqueoObservaciones');
    const obsHidden = document.getElementById('arqueoObservacionesHidden');
    if (obs && obsHidden) obsHidden.value = obs.value;

    // Cerrar arqueo y abrir resumen
    const arqueoModal = document.getElementById('arqueoModal');
    const previsualizacion = document.getElementById('cajaPrevisualizacion');
    if (arqueoModal) arqueoModal.style.display = 'none';
    if (previsualizacion) previsualizacion.style.display = 'flex';
}

/**
 * Muestra el historial de ventas (alias de cargarHistorialVentas para compatibilidad)
 */
function mostrarHistorialVentas() {
    if (typeof cargarHistorialVentas === 'function') {
        cargarHistorialVentas();
        const modal = document.getElementById('modalHistorialVentas');
        if (modal) modal.style.display = 'flex';
    }
}

/**
 * Actualiza el elemento .ticket-fecha con la fecha y hora actual
 */
function actualizarFechaHora() {
    const ahora = new Date();
    const dia = String(ahora.getDate()).padStart(2, '0');
    const mes = String(ahora.getMonth() + 1).padStart(2, '0');
    const anio = ahora.getFullYear();
    const horas = String(ahora.getHours()).padStart(2, '0');
    const minutos = String(ahora.getMinutes()).padStart(2, '0');
    const segundos = String(ahora.getSeconds()).padStart(2, '0');

    const fechaHora = `${dia}/${mes}/${anio} ${horas}:${minutos}:${segundos}`;
    const el = document.querySelector('.ticket-fecha');
    if (el) el.textContent = fechaHora;
}

/**
 * Cierra el modal de bienvenida y limpia el estado en el servidor.
 */
function cerrarModalBienvenida(idModal) {
    const modal = document.getElementById(idModal);
    if (modal) modal.style.display = 'none';

    fetch('api/caja.php?accion=limpiarInterrupcion')
        .then(response => response.json())
        .then(data => {
            if (data && !data.success) {
                console.error('Error al limpiar interrupción:', data.message);
            }
        })
        .catch(error => console.error('Error en fetch:', error));
}


console.log('[DEBUG cajero.js] File fully parsed - all functions defined');


// ======================== BLOQUEO DE SESIÓN ========================

/**
 * Bloquea la sesión del cajero y muestra la pantalla de bloqueo.
 */
function bloquearSesion() {
    sessionStorage.setItem('cajero_bloqueado', 'true');
    mostrarPantallaBloqueo();
}

/**
 * Muestra visualmente la pantalla de bloqueo.
 */
function mostrarPantallaBloqueo() {
    const pantalla = document.getElementById('pantallaBloqueo');
    if (pantalla) {
        pantalla.style.display = 'flex';
        // Desenfocar elementos de fondo
        const header = document.querySelector('header');
        const cajero = document.getElementById('cajero');
        if (header) header.style.filter = 'blur(5px)';
        if (cajero) cajero.style.filter = 'blur(5px)';
        
        setTimeout(() => {
            const input = document.getElementById('inputPasswordDesbloqueo');
            if (input) input.focus();
        }, 100);
    }
}

/**
 * Intenta desbloquear la sesión validando la contraseña en el servidor.
 */
function desbloquearSesion() {
    const input = document.getElementById('inputPasswordDesbloqueo');
    const pwd = input.value;
    const errorMsg = document.getElementById('errorDesbloqueo');
    
    if (!pwd) return;
    
    fetch('api/unlock.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ password: pwd })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            sessionStorage.removeItem('cajero_bloqueado');
            document.getElementById('pantallaBloqueo').style.display = 'none';
            input.value = '';
            errorMsg.style.display = 'none';
            
            // Quitar desenfoque
            const header = document.querySelector('header');
            const cajero = document.getElementById('cajero');
            if (header) header.style.filter = '';
            if (cajero) cajero.style.filter = '';
        } else {
            errorMsg.style.display = 'block';
            errorMsg.textContent = data.message || 'Contraseña incorrecta';
        }
    })
    .catch(e => {
        console.error(e);
        errorMsg.style.display = 'block';
        errorMsg.textContent = 'Error de conexión';
    });
}

// Check at startup
document.addEventListener('DOMContentLoaded', () => {
    if (sessionStorage.getItem('cajero_bloqueado') === 'true') {
        mostrarPantallaBloqueo();
    }
});
