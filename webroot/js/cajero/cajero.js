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

// Método modularizado en webroot/js/cajero-catalog.js: seleccionarCategoria

// Método modularizado en webroot/js/cajero-catalog.js: getNombreTraducido

// ======================== BÚSQUEDA (AJAX) - LIVE SEARCH ========================

// Método modularizado en webroot/js/cajero-catalog.js: buscarProductos

// ======================== RENDER PRODUCTOS ========================

// Método modularizado en webroot/js/cajero-catalog.js: renderProductos

// Método modularizado en webroot/js/cajero-catalog.js: actualizarPrecioDesdeInput

// Variable para almacenar la tarifa anterior de cada card y poder revertir
var tarifaAnteriorCard = new Map();

// Método modularizado en webroot/js/cajero-catalog.js: guardarTarifaAnterior

// Método modularizado en webroot/js/cajero-catalog.js: revertirTarifaCard

// Método modularizado en webroot/js/cajero-catalog.js: resetearTarifaCard

// Método modularizado en webroot/js/cajero-catalog.js: actualizarPrecioCard

// ======================== PERMISOS Y CREAR PRODUCTOS ========================

// Método modularizado en webroot/js/cajero-catalog.js: verificarPermisoCrearProductos

// Método modularizado en webroot/js/cajero-catalog.js: abrirModalNuevoProducto

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

// Método modularizado en webroot/js/cajero-catalog.js: initCarouselBotones

// Método modularizado en webroot/js/cajero-catalog.js: scrollCarouselBotones

// Método modularizado en webroot/js/cajero-catalog.js: scrollCarouselBotonesIzquierda

// ======================== MODAL CAMBIAR PRECIOS ========================

var cambiarPreciosTodosProductos = [];
var cambiarPreciosProductosFiltrados = [];
var cambiarPreciosTarifas = [];
var cambiarPreciosPaginaActual = 1;
var CAMBIAR_PRECIOS_POR_PAGINA = 10;
var cambiarPreciosDebounce = null;
var cambiarPreciosMostrarConIva = false;
var cambiosPendientesCajero = {};

// Método modularizado en webroot/js/cajero-catalog.js: actualizarBotonAplicarCambios

// Método modularizado en webroot/js/cajero-catalog.js: mostrarModalCambiarPrecios

// Método modularizado en webroot/js/cajero-catalog.js: cargarDatosCambiarPrecios

// Método modularizado en webroot/js/cajero-catalog.js: renderizarCabecerasCambiarPrecios

// Método modularizado en webroot/js/cajero-catalog.js: renderizarTablaCambiarPrecios

// Método modularizado en webroot/js/cajero-catalog.js: renderizarPaginacionCambiarPrecios

// Método modularizado en webroot/js/cajero-catalog.js: irAPaginaCambiarPrecios

// Método modularizado en webroot/js/cajero-catalog.js: cambiarPaginaCambiarPrecios

// Método modularizado en webroot/js/cajero-catalog.js: buscarProductosCambiarPrecio

// Método modularizado en webroot/js/cajero-catalog.js: toggleIvaCambiarPrecios

// Método modularizado en webroot/js/cajero-catalog.js: estacionarCambioPrecioCajero

// Método modularizado en webroot/js/cajero-catalog.js: aplicarCambiosPreciosCajero



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
 * Modifica el subtotal de una línea de producto en el carrito.
 * Calcula el nuevo PVP unitario para que cuadre exactamente con la cantidad,
 * y marca la línea como modificada manualmente para bloquear tarifas/descuentos.
 * @param {number} index - Índice de la línea de producto en el carrito
 * @param {string|number} nuevoSubtotalStr - El nuevo subtotal introducido
 */
function modificarSubtotalLinea(index, nuevoSubtotalStr) {
    const item = carrito[index];
    if (!item) return;

    const nuevoSubtotal = parseFloat(nuevoSubtotalStr);
    if (isNaN(nuevoSubtotal) || nuevoSubtotal < 0) {
        if (typeof actualizarTicket === 'function') {
            actualizarTicket();
        }
        return;
    }

    const dec = item.decimales || 2;
    // Usamos hasta 4 decimales para mayor precisión en la división y evitar descuadres de céntimos en multiplicaciones
    item.pvpUnitario = roundTo(nuevoSubtotal / item.cantidad, 4);
    item.subtotalModificado = true;

    // Reseteamos el descuento/tarifa local del item a Cliente normal (sin tarifa especial)
    item.tarifaNombre = 'Cliente';
    item.tarifaDescuento = 0;

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

// Los métodos de ventas pospuestas se han modularizado en webroot/js/cajero-postponed.js

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
    let totalPVPBruto = 0;
    let totalPVPBrutoNormal = 0;

    carrito.forEach(item => {
        const subtotalLinea = roundTo(item.pvpUnitario * item.cantidad, precTotal);
        totalPVPBruto += subtotalLinea;
        if (!item.subtotalModificado) {
            totalPVPBrutoNormal += subtotalLinea;
        }
    });

    // Calcular descuento manual (global) sobre el total PVP acumulado de las líneas normales
    let importeDescuentoManual = 0;
    if (descuento.tipo === 'porcentaje') {
        importeDescuentoManual = roundTo(totalPVPBrutoNormal * (descuento.valor / 100), precTotal);
    } else if (descuento.tipo === 'fijo') {
        importeDescuentoManual = Math.min(totalPVPBrutoNormal, roundTo(descuento.valor, precTotal));
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
            <td>
                <input type="number" step="0.01" class="ticket-subtotal-input" 
                    value="${subtotalRebajado.toFixed(dec)}" 
                    onchange="modificarSubtotalLinea(${i}, this.value)"> €
            </td>
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
    
    let totalPVPBrutoNormal = 0;
    carrito.forEach(it => {
        if (!it.subtotalModificado) {
            totalPVPBrutoNormal += roundTo(it.pvpUnitario * it.cantidad, it.decimales || 2);
        }
    });
    let totalPVPFinalNormal = Math.max(0, totalPVPBrutoNormal - descuentoManualImporte);
    let factorDescuentoManual = totalPVPBrutoNormal > 0 ? (totalPVPFinalNormal / totalPVPBrutoNormal) : 0;

    carrito.forEach(item => {
        const dec = item.decimales || 2;
        const subtotalLineaPVP = roundTo(item.pvpUnitario * item.cantidad, dec);
        
        let subtotalFinalPVP;
        if (item.subtotalModificado) {
            subtotalFinalPVP = subtotalLineaPVP;
        } else {
            subtotalFinalPVP = subtotalLineaPVP * factorDescuentoManual;
        }

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

// Método modularizado en webroot/js/cajero-checkout.js: intentarCobrar

// Método modularizado en webroot/js/cajero-checkout.js: mostrarModalCambio

// Método modularizado en webroot/js/cajero-checkout.js: fijarImporteExacto

// Método modularizado en webroot/js/cajero-checkout.js: calcularCambio

// Método modularizado en webroot/js/cajero-checkout.js: confirmarCambio

// ======================== PAGO MIXTO ========================

// Método modularizado en webroot/js/cajero-checkout.js: mostrarModalPagoMixto

// Método modularizado en webroot/js/cajero-checkout.js: calcularRestanteMixto

// Método modularizado en webroot/js/cajero-checkout.js: fijarRestanteMixto

// Método modularizado en webroot/js/cajero-checkout.js: confirmarPagoMixto

// ======================== MODAL TIPO DOCUMENTO / CLIENTE ========================

// Método modularizado en webroot/js/cajero-checkout.js: mostrarModalTipoDocumento

// ======================== FLUJO DE FINALIZAR VENTA ========================

// Método modularizado en webroot/js/cajero-checkout.js: cambiarIdiomaTicket

// Método modularizado en webroot/js/cajero-checkout.js: abrirModalFinalizarVenta

// Método modularizado en webroot/js/cajero-checkout.js: actualizarResumenClienteCheckout

// Método modularizado en webroot/js/cajero-checkout.js: quitarClienteFinalizar

// Método modularizado en webroot/js/cajero-checkout.js: abrirDatosClienteDesdeCheckout

// Método modularizado en webroot/js/cajero-checkout.js: cerrarModalDatosClienteAtras

// Método modularizado en webroot/js/cajero-checkout.js: cambiarTipoDocumentoCheckout

// Método modularizado en webroot/js/cajero-checkout.js: cambiarMetodoEntregaCheckout

// Método modularizado en webroot/js/cajero-checkout.js: construirObjetoVentaTemporal

// Método modularizado en webroot/js/cajero-checkout.js: cerrarExito

// Método modularizado en webroot/js/cajero-checkout.js: toggleZoomTicket

// Método modularizado en webroot/js/cajero-checkout.js: ajustarEscalaTicket

// Método modularizado en webroot/js/cajero-checkout.js: renderizarVistaPreviaTicket

// Método modularizado en webroot/js/cajero-checkout.js: procesarVentaFinal

// Método modularizado en webroot/js/cajero-checkout.js: buscarDatosCliente

// Método modularizado en webroot/js/cajero-checkout.js: seleccionarDatosCliente

// Método modularizado en webroot/js/cajero-checkout.js: validarYConfirmarVenta

// Método modularizado en webroot/js/cajero-checkout.js: confirmarConPuntos

// Método modularizado en webroot/js/cajero-checkout.js: confirmarSinPuntos

// Método modularizado en webroot/js/cajero-checkout.js: abrirModalBuscarClienteRegistradoParaPuntos

// Método modularizado en webroot/js/cajero-checkout.js: confirmarVenta

// Método modularizado en webroot/js/cajero-checkout.js: imprimirDocumento





// Método modularizado en webroot/js/cajero-checkout.js: mostrarFormEmail

// Método modularizado en webroot/js/cajero-checkout.js: enviarPorCorreo

// Método modularizado en webroot/js/cajero-checkout.js: mostrarFormEmailDevolucion

// Método modularizado en webroot/js/cajero-checkout.js: enviarPorCorreoDevolucion





// Método modularizado en webroot/js/cajero-checkout.js: verificarLimiteEfectivo

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

// Método modularizado en webroot/js/cajero-loyalty.js: abrirModalBuscarClienteRegistrado

// Método modularizado en webroot/js/cajero-loyalty.js: mostrarDniEnTicket

// Método modularizado en webroot/js/cajero-loyalty.js: ocultarDniEnTicket

// Método modularizado en webroot/js/cajero-loyalty.js: desvincularCliente

// Método modularizado en webroot/js/cajero-loyalty.js: cerrarYLimpiarClientePuntos

// Método modularizado en webroot/js/cajero-loyalty.js: acumularPuntosSolamente

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

// Método modularizado en webroot/js/cajero-loyalty.js: calcularDescuentoPuntos

// Método modularizado en webroot/js/cajero-loyalty.js: aplicarDescuentoPuntos

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

// Método modularizado en webroot/js/cajero-loyalty.js: actualizarTarifasCarritoPorCliente

// Método modularizado en webroot/js/cajero-loyalty.js: restaurarTarifasCarritoPorDefecto

// Método modularizado en webroot/js/cajero-loyalty.js: cerrarModalBuscarClienteRegistrado

// Método modularizado en webroot/js/cajero-loyalty.js: eliminarDescuentoPorTarifa

// Método modularizado en webroot/js/cajero-loyalty.js: abrirModalClienteHabitual

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

// Método modularizado en webroot/js/cajero-catalog.js: guardarNuevoProducto

// Método modularizado en webroot/js/cajero-history.js: cargarHistorialVentas

// Método modularizado en webroot/js/cajero-history.js: verDetalleVenta

// Método modularizado en webroot/js/cajero-history.js: enviarTicketCorreo

// Método modularizado en webroot/js/cajero-history.js: abrirModalPuntosCliente

// Cache temporal para historial
var ventaHistorialTemporal = null;
var devolucionHistorialTemporal = null;

// Método modularizado en webroot/js/cajero-history.js: reimprimirTicket

// Método modularizado en webroot/js/cajero-history.js: mostrarModalEnviarCorreo

// Variable para almacenar la devolución actual del historial

// Método modularizado en webroot/js/cajero-history.js: mostrarHistorialDevoluciones

// Método modularizado en webroot/js/cajero-history.js: verDetalleDevolucion

// Método modularizado en webroot/js/cajero-history.js: reimprimirTicketDevolucionDesdeHistorial

// Método modularizado en webroot/js/cajero-history.js: imprimirDocumentoDevolucionConDatos

// Método modularizado en webroot/js/cajero-history.js: imprimirTicketDevolucion

// Método modularizado en webroot/js/cajero-register.js: mostrarModalAbrirCaja

// Método modularizado en webroot/js/cajero-register.js: toggleCambio

// Método modularizado en webroot/js/cajero-register.js: mostrarModalRetiro

// Método modularizado en webroot/js/cajero-register.js: validarRetiro

// Método modularizado en webroot/js/cajero-register.js: updateMethodUI

// Variables para el flujo de devoluciones
var ticketActualDevolucion = null;
var lineasVentaDevolucion = [];
var cantidadesDevSeleccion = [];



// Los métodos de devolución (multi-devolución) se han modularizado en webroot/js/cajero-devoluciones.js

// Los métodos de producto comodín se han modularizado en webroot/js/cajero-comodin.js

// Método modularizado en webroot/js/cajero-register.js: calcularArqueo

// Método modularizado en webroot/js/cajero-register.js: continuarArqueo

// Método modularizado en webroot/js/cajero-history.js: mostrarHistorialVentas

// Método modularizado en webroot/js/cajero-register.js: actualizarFechaHora

// Método modularizado en webroot/js/cajero-register.js: cerrarModalBienvenida


console.log('[DEBUG cajero.js] File fully parsed - all functions defined');


// Los métodos de bloqueo de sesión se han modularizado en webroot/js/cajero-lock.js
