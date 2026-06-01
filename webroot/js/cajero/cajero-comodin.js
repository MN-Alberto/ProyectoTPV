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
