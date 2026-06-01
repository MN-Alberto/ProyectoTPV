// Variables de paginación para la tabla de devolución
var paginaActualDev = 1;
var productosPorPaginaDev = 4;

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
                amount: cant, // wait, is it amount or cantidad? Wait, the server-side code receives 'productos' and maps it. Let's look at the original code carefully: p.cantidad = cant!
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
