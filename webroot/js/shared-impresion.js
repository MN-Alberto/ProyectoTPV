/**
 * shared-impresion.js
 * Lógica compartida para la generación e impresión de tickets y facturas.
 * Se utiliza tanto en el Cajero como en el Historial de Ventas (Admin).
 */

/**
 * parseEsp(val)
 * Parsea un numero en formato español (1.234,56) a float de JS (1234.56).
 */
function parseEsp(val) {
    if (typeof val === 'number') return val;
    if (!val || typeof val !== 'string') return 0;
    return parseFloat(val.replace(/\./g, '').replace(',', '.'));
}

/**
 * ticketGetQRDataURL(text)
 * Usa qrcode.js para generar un QR en un canvas oculto y devolver el DataURL (Base64).
 */
function ticketGetQRDataURL(text) {
    if (!text) return '';
    if (typeof QRCode === 'undefined') {
        console.warn('QRCode library not loaded.');
        return '';
    }
    try {
        const tempDiv = document.createElement('div');
        tempDiv.style.visibility = 'hidden';
        tempDiv.style.position = 'absolute';
        tempDiv.style.left = '-9999px';
        document.body.appendChild(tempDiv);
        
        const qrcode = new QRCode(tempDiv, {
            text: text,
            width: 128,
            height: 128,
            colorDark : "#000000",
            colorLight : "#ffffff",
            correctLevel : QRCode.CorrectLevel ? QRCode.CorrectLevel.M : 0
        });
        
        // El qrcode.js de David Shim crea un canvas Y una imagen.
        // Algunos navegadores tardan un ciclo en pintar el canvas.
        const canvas = tempDiv.querySelector('canvas');
        const img = tempDiv.querySelector('img');
        
        let result = '';
        if (canvas) {
            result = canvas.toDataURL("image/png");
        } else if (img && img.src) {
            result = img.src;
        }
        
        document.body.removeChild(tempDiv);
        return result;
    } catch (e) {
        console.error('Error generando QR local:', e);
        return '';
    }
}

/**
 * generarDesgloseFormaPago(T, datosVenta, isFactura)
 * Genera el HTML para la sección de método de pago en el comprobante.
 */
function generarDesgloseFormaPago(T, datosVenta, isFactura) {
    const metodoLabels = {
        efectivo: '💵 ' + T.print.cash,
        tarjeta: '💳 ' + T.print.card,
        bizum: '📱 ' + T.print.bizum
    };
    const precTotal = 2;

    if (datosVenta.metodoPago === 'mixto' && datosVenta.pagoMixtoDesglose) {
        let d = datosVenta.pagoMixtoDesglose;
        // Si viene de DB puede ser string
        if (typeof d === 'string') d = JSON.parse(d);

        let rows = '';
        if (d.efectivo > 0) rows += `<tr><td style="padding:3px 0; font-size:${isFactura ? '12px' : '10px'};">💵 ${T.print.cash}</td><td style="text-align:right; padding:3px 0; font-size:${isFactura ? '12px' : '10px'};">${parseFloat(d.efectivo).toFixed(precTotal).replace('.', ',')} €</td></tr>`;
        if (d.tarjeta > 0) rows += `<tr><td style="padding:3px 0; font-size:${isFactura ? '12px' : '10px'};">💳 ${T.print.card}</td><td style="text-align:right; padding:3px 0; font-size:${isFactura ? '12px' : '10px'};">${parseFloat(d.tarjeta).toFixed(precTotal).replace('.', ',')} €</td></tr>`;
        if (d.bizum > 0) rows += `<tr><td style="padding:3px 0; font-size:${isFactura ? '12px' : '10px'};">📱 ${T.print.bizum}</td><td style="text-align:right; padding:3px 0; font-size:${isFactura ? '12px' : '10px'};">${parseFloat(d.bizum).toFixed(precTotal).replace('.', ',')} €</td></tr>`;
        if (d.cambio > 0) rows += `<tr><td style="padding:3px 0; font-size:${isFactura ? '12px' : '10px'}; color:#888;">${T.print.change_returned}</td><td style="text-align:right; padding:3px 0; font-size:${isFactura ? '12px' : '10px'}; color:#888;">-${parseFloat(d.cambio).toFixed(precTotal).replace('.', ',')} €</td></tr>`;

        if (isFactura) {
            return `<div style="clear:both; margin-top:30px;">
                <p style="font-size:13px; color:#444; font-weight:bold; margin-bottom:6px;">${T.print.payment_method.toUpperCase()}: MIXTO</p>
                <table style="width:auto; border-collapse:collapse;">${rows}</table>
            </div>`;
        } else {
            return `<div style="margin-top:10px; border-top:1px dashed #000; padding-top:6px;">
                <div style="font-size:10px; font-weight:bold; margin-bottom:4px;">${T.print.payment_method.toUpperCase()}: MIXTO</div>
                <table style="width:100%; border-collapse:collapse;">${rows}</table>
            </div>`;
        }
    }

    const label = metodoLabels[datosVenta.metodoPago] || (datosVenta.metodoPago ? datosVenta.metodoPago.toUpperCase() : '');
    
    let extraHTMLFactura = '';
    let extraHTMLTicket = '';
    
    if (datosVenta.metodoPago === 'efectivo' && datosVenta.entregado !== undefined && datosVenta.cambio !== undefined && datosVenta.entregado > 0) {
        const lblEntregado = T.print.delivered || 'Entregado';
        const lblCambio = T.print.change_returned || 'Cambio devuelto';
        extraHTMLFactura = `<br><span style="font-size:11px;">${lblEntregado}: ${parseFloat(datosVenta.entregado).toFixed(precTotal).replace('.', ',')} € | ${lblCambio}: ${parseFloat(datosVenta.cambio).toFixed(precTotal).replace('.', ',')} €</span>`;
        extraHTMLTicket = `<div style="margin-top:4px; font-size:10px; color:#444; font-weight:normal;">${lblEntregado}: ${parseFloat(datosVenta.entregado).toFixed(precTotal).replace('.', ',')} €<br>${lblCambio}: ${parseFloat(datosVenta.cambio).toFixed(precTotal).replace('.', ',')} €</div>`;
    }

    if (isFactura) {
        return `<p style="clear:both; margin-top:30px; font-size:12px; color:#666;">${T.print.payment_method}: ${label}${extraHTMLFactura}</p>`;
    } else {
        return `<div style="margin-top:8px; font-size:10px; text-align:center;">
                    <div style="font-weight:bold;">${T.print.payment_method}: ${label}</div>
                    ${extraHTMLTicket}
                </div>`;
    }
}

/**
 * generarHTMLComprobante(datosVenta, idioma)
 * Genera el HTML completo para un ticket o factura.
 * @param {Object} datosVenta 
 * @param {string} idioma - 'es', 'en', etc.
 */
function generarHTMLComprobante(datosVenta, idioma = 'es') {
    const T = IDIOMAS_TICKET[idioma] || IDIOMAS_TICKET['es'];
    const isFactura = (datosVenta.tipo === 'factura');
    const isRectificativa = !!datosVenta.es_rectificativa;
    
    let tipoTitulo = isFactura ? T.print.factura_title : T.print.ticket_title;
    if (isRectificativa) {
        tipoTitulo = T.print.rectificativa_title || 'FACTURA RECTIFICATIVA';
    }

    const precTotal = 2; 
    let lineasHtmlTicket = '';
    let lineasHtmlFactura = '';
    let sumaTotalesNumeric = 0;
    let desgloseIva = {};

    datosVenta.carrito.forEach(item => {
        const cant = parseFloat(item.cantidad) || 0;
        const dec = 2; 
        const precioBaseUnitario = parseFloat(item.precio) || 0;
        const ivaPorc = (item.iva !== undefined && item.iva !== null && item.iva !== "") ? parseInt(item.iva) : 21;

        let pvpUnitario = parseFloat(item.pvpUnitario) || 0;
        if (pvpUnitario === 0 && precioBaseUnitario > 0) {
            pvpUnitario = Math.round(precioBaseUnitario * (1 + (ivaPorc / 100)) * 100) / 100;
        }

        const subtotalPVP = (item.importeTotal !== undefined) ? parseFloat(item.importeTotal) : Math.round(pvpUnitario * cant * 100) / 100;
        const subtotalBase = Math.round(subtotalPVP / (1 + (ivaPorc / 100)) * 100) / 100;
        const subtotalIva = Math.round((subtotalPVP - subtotalBase) * 100) / 100;

        sumaTotalesNumeric += subtotalPVP;
        if (!desgloseIva[ivaPorc]) desgloseIva[ivaPorc] = { base: 0, cuota: 0 };
        desgloseIva[ivaPorc].base += subtotalBase;
        desgloseIva[ivaPorc].cuota += subtotalIva;

        let nombreProducto = item['nombre_' + idioma] || item.nombre;
        
        // Si no hay traducción en el objeto, intentar con el diccionario
        if (nombreProducto === item.nombre) {
            let claveNormalizada = nombreProducto.toLowerCase().replace(/\s+/g, '_');
            if (T.products) {
                if (T.products[claveNormalizada]) {
                    nombreProducto = T.products[claveNormalizada];
                } else if (T.products[nombreProducto]) {
                    nombreProducto = T.products[nombreProducto];
                }
            }
        }

        lineasHtmlTicket += `<tr>
            <td style="padding: 6px 4px; font-size: 11px;">${nombreProducto}</td>
            <td style="text-align:center; font-size: 11px;">${cant}</td>
            <td style="text-align:right; font-size: 11px; padding-right: 12px;">${precioBaseUnitario.toFixed(dec).replace('.', ',')}€</td>
            <td style="text-align:center; font-size: 11px; padding-left: 12px;">${ivaPorc}%</td>
            <td style="text-align:right; font-size: 11px;">${subtotalPVP.toFixed(dec).replace('.', ',')}€</td>
        </tr>`;

        lineasHtmlFactura += `<tr>
            <td style="padding: 10px 5px;">${nombreProducto}</td>
            <td style="text-align:center">${cant}</td>
            <td style="text-align:right">${precioBaseUnitario.toFixed(dec).replace('.', ',')} €</td>
            <td style="text-align:center">${ivaPorc}%</td>
            <td style="text-align:right">${subtotalPVP.toFixed(dec).replace('.', ',')} €</td>
        </tr>`;
    });

    let totalesHtml = `<table style="width:100%; border-top: 1px solid #000; margin-top:10px;">`;
    Object.keys(desgloseIva).sort().forEach(porc => {
        totalesHtml += `
            <tr style="font-size: 0.8rem; color: #444;">
                <td>${T.print.base_at} ${porc}%:</td>
                <td style="text-align:right">${desgloseIva[porc].base.toFixed(precTotal).replace('.', ',')} €</td>
            </tr>
            <tr style="font-size: 0.8rem; color: #444;">
                <td>${T.print.iva_quote} (${porc}%):</td>
                <td style="text-align:right">${desgloseIva[porc].cuota.toFixed(precTotal).replace('.', ',')} €</td>
            </tr>`;
    });

    const descuentoValor = parseFloat(datosVenta.descuentoValor) || 0;
    if (descuentoValor > 0 && datosVenta.descuentoCupon && !datosVenta.descuentoCupon.startsWith('PUNTOS_')) {
        let descImporte = 0;
        let descLabel = '';
        if (datosVenta.descuentoTipo === 'porcentaje') {
            descImporte = Math.round(sumaTotalesNumeric * (descuentoValor / 100) * 100) / 100;
            descLabel = `${T.print.dto} (${descuentoValor}%):`;
        } else {
            descImporte = descuentoValor;
            descLabel = `${T.print.discount}:`;
        }
        totalesHtml += `
            <tr style="font-size: 0.85rem; color: #d32f2f;">
                <td>${descLabel}</td>
                <td style="text-align:right">-${descImporte.toFixed(precTotal).replace('.', ',')} €</td>
            </tr>`;
    }

    if (datosVenta.puntosCanjeados) {
        let pc = datosVenta.puntosCanjeados;
        if (typeof pc === 'string') pc = JSON.parse(pc);
        totalesHtml += `
            <tr style="font-size: 0.85rem; color: #d32f2f;">
                <td>${T.print.points_exchange} (${pc.puntos} pts):</td>
                <td style="text-align:right">-${parseFloat(pc.descuento).toFixed(precTotal).replace('.', ',')} €</td>
            </tr>`;
    }

    totalesHtml += `
        <tr style="border-top: 2px solid #000;">
            <td style="font-size: 1.1rem; padding-top:8px;"><strong>${T.print.total}:</strong></td>
            <td style="font-size: 1.1rem; font-weight: bold; text-align:right; padding-top:8px;">${parseEsp(datosVenta.total).toFixed(precTotal).replace('.', ',')} €</td>
        </tr>
    </table>`;

    const rawNum = datosVenta.numero || datosVenta.id || '0';
    const paddedNum = (!isNaN(rawNum) && String(rawNum).trim() !== '' && rawNum !== '—') ? String(rawNum).padStart(5, '0') : rawNum;
    const numComprobante = (datosVenta.serie || '') + paddedNum;

    let finalPuntosBalance = parseInt(datosVenta.puntosBalance);
    if (isNaN(finalPuntosBalance)) {
        finalPuntosBalance = (parseInt(datosVenta.clientePuntos) || 0) - (parseInt(datosVenta.puntosCanjeados?.puntos) || 0) + (parseInt(datosVenta.puntosGanados) || 0);
    }

    const puntosFooterHtml = (datosVenta.clienteNif || datosVenta.puntosBalance > 0) ? `
        <div style="margin-top:10px; border-top:1px dashed #ccc; padding-top:5px; font-size:10px;">
            ${datosVenta.puntosGanados > 0 ? `<div>${T.print.earned_points}: <strong>+${datosVenta.puntosGanados}</strong></div>` : ''}
            <div>${T.print.new_balance}: <strong>${finalPuntosBalance.toLocaleString('es-ES')}</strong></div>
        </div>` : '';

    if (isFactura) {
        return `<html><head><style>
            body { font-family: 'Helvetica Neue', Arial, sans-serif; padding: 20px; color: #1a1a1a; line-height: 1.4; font-size: 14px; overflow: hidden; }
            .header { border-bottom: 3px solid #2563eb; padding-bottom: 10px; margin-bottom: 20px; color: #2563eb; }
            .header h1 { margin: 0; font-size: 24px; text-transform: uppercase; }
            .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
            .col h3 { font-size: 11px; color: #666; text-transform: uppercase; margin: 0 0 5px 0; border-bottom: 1px solid #eee; }
            .col p { margin: 2px 0; }
            table { width: 100%; border-collapse: collapse; margin: 20px 0; }
            th { text-align: left; background: #f8fafc; padding: 10px 5px; border-bottom: 2px solid #2563eb; }
            td { padding: 10px 5px; border-bottom: 1px solid #e5e7eb; }
        </style></head><body>
            <div class="header"><h1>${tipoTitulo}</h1></div>
            <div class="two-col"><div class="col"><h3>${T.print.emitter}</h3><p><strong>${TPV_CONFIG.nombre}</strong></p><p>NIF: ${TPV_CONFIG.nif}</p><p>${TPV_CONFIG.direccion}</p></div>
            <div class="col" style="text-align:right">
                <div style="font-size: 18px; font-weight: bold;">Nº ${numComprobante}</div>
                <div style="color:#666">${T.print.date}: ${datosVenta.fecha}</div>
                ${isRectificativa && datosVenta.id_original ? `
                    <div style="margin-top:5px; font-size:12px; font-weight:bold; color:#dc2626;">
                        ${T.print.rectificativa_original_ref || 'Rectifica a:'} ${datosVenta.serie_original || 'T'}${String(datosVenta.id_original).padStart(5, '0')}
                    </div>
                ` : ''}
            </div></div>
            <div style="background:#f8fafc; padding:15px; border-radius:8px; margin-bottom:20px;">
                <h3>${T.print.receiver}</h3>
                <p><strong>${datosVenta.clienteNombre || T.print.no_name}</strong></p>
                ${datosVenta.clienteNif ? `<p>NIF: ${datosVenta.clienteNif}</p>` : ''}
                ${datosVenta.clienteDir ? `<p>${datosVenta.clienteDir}</p>` : ''}
                ${puntosFooterHtml ? `<div style="margin-top:10px; padding-top:10px; border-top:1px solid #e5e7eb;">${puntosFooterHtml}</div>` : ''}
            </div>
            <table><thead><tr><th>${T.print.description_th}</th><th style="text-align:center">${T.print.cant_th}</th><th style="text-align:right">${T.print.base_ud_th}</th><th style="text-align:center">IVA</th><th style="text-align:right">${T.print.importe_th}</th></tr></thead>
            <tbody>${lineasHtmlFactura}</tbody></table>
             <div style="float:right; width: 45%;">${totalesHtml}</div>
             ${generarDesgloseFormaPago(T, datosVenta, true)}
             
            ${datosVenta.mensajePersonalizado ? `
                <div style="clear:both; margin-top:20px; padding:12px; border-top:1px dashed #ccc; border-bottom:1px dashed #ccc; text-align:center; font-style:italic; color:#444; word-wrap: break-word; overflow-wrap: break-word; line-height: 1.5;">
                    ${datosVenta.mensajePersonalizado.replace(/(.{45})/g, "$1<br>")}
                </div>
            ` : ''}
                     
            ${datosVenta.qrUrl ? `
                <div style="clear:both; margin-top:30px; text-align:center;">
                    <div style="display:inline-block; border:1px solid #eee; padding:10px; background:#fff;">
                        <img src="${ticketGetQRDataURL(datosVenta.qrUrl)}" style="width:120px; height:120px;" alt="QR Verifactu">
                        <p style="margin:5px 0 0 0; font-size:10px; font-weight:bold; color:#000;">SISTEMA VERI*FACTU</p>
                    </div>
                    <p style="margin:5px 0 0 0; font-size:9px; color:#666;">${T.print.verifactu_verify}</p>
                </div>
            ` : ''}
         </body></html>`;
    } else {
        return `<html><head><style>
            @page { size: 80mm auto; margin: 0; }
            body { font-family: 'Inter', sans-serif; margin: 0; padding: 0; color: #000; background: #fff; width: 80mm; }
            .ticket-container { width: 80mm; padding: 5mm; box-sizing: border-box; font-size: 11px; line-height: 1.3; }
            .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 8px; margin-bottom: 12px; }
            .header h1 { margin: 0; font-size: 15px; text-transform: uppercase; }
            table { width: 100%; border-collapse: collapse; margin: 8px 0; table-layout: fixed; }
            th { text-align: left; border-bottom: 1px solid #000; padding: 4px 0; font-size: 10px; }
            td { padding: 4px 0; border-bottom: 1px dashed #eee; font-size: 10px; word-wrap: break-word; }
            .footer { text-align: center; margin-top: 15px; font-size: 9px; border-top: 1px solid #000; padding-top: 8px; }
            .flex-row { display: flex; justify-content: space-between; margin-bottom: 5px; font-size: 10px; }
        </style></head><body>
            <div class="ticket-container">
            <div class="header">
                <h1>${TPV_CONFIG.nombre}</h1>
                <div style="font-size:9px;">NIF: ${TPV_CONFIG.nif} | ${TPV_CONFIG.direccion}</div>
                <div style="font-size:10px; margin-top:4px; font-weight:bold;">${tipoTitulo}</div>
            </div>
                <div class="flex-row"><span>Nº: ${numComprobante}</span><span>${datosVenta.fecha}</span></div>
                ${isRectificativa && datosVenta.id_original ? `
                    <div style="margin-bottom:8px; font-size:10px; font-weight:bold; color:#dc2626;">
                        ${T.print.rectificativa_original_ref || 'Rectifica a:'} ${datosVenta.serie_original || 'T'}${String(datosVenta.id_original).padStart(5, '0')}
                    </div>
                ` : ''}
                ${datosVenta.clienteNombre ? `<div style="margin-bottom:8px; font-size:10px; border:1px solid #eee; padding:4px;"><strong>Cliente:</strong> ${datosVenta.clienteNombre}</div>` : ''}
                <table>
                    <thead>
                        <tr>
                            <th style="width: 35%;">${T.print.art_th}</th>
                            <th style="text-align:center; width: 10%;">${T.print.ud_th}</th>
                            <th style="text-align:right; width: 20%; padding-right: 5px;">${T.print.base_th}</th>
                            <th style="text-align:center; width: 15%; padding-left: 5px;">IVA</th>
                            <th style="text-align:right; width: 20%;">${T.print.total_th}</th>
                        </tr>
                    </thead>
                    <tbody>${lineasHtmlTicket}</tbody>
                </table>
                ${totalesHtml}
                ${puntosFooterHtml}

                ${generarDesgloseFormaPago(T, datosVenta, false)}

                ${datosVenta.mensajePersonalizado ? `
                    <div style="margin-top:15px; padding:10px; border-top:1px dashed #ccc; border-bottom:1px dashed #ccc; text-align:center; font-style:italic; word-wrap: break-word; overflow-wrap: break-word; line-height: 1.4;">
                        ${datosVenta.mensajePersonalizado.replace(/(.{45})/g, "$1<br>")}
                    </div>
                ` : ''}

                <div class="footer"><p>${T.print.thanks_for_purchase}</p></div>
                
                ${datosVenta.qrUrl ? `
                    <div style="margin-top:15px; text-align:center; padding-bottom:10px;">
                        <div style="display:inline-block; border:1px solid #000; padding:5px; background:#fff;">
                            <img src="${ticketGetQRDataURL(datosVenta.qrUrl)}" style="width:100px; height:100px;" alt="QR Verifactu">
                            <p style="margin:2px 0 0 0; font-size:9px; font-weight:bold;">SISTEMA VERI*FACTU</p>
                        </div>
                        <p style="margin:5px 0 0 0; font-size:8px;">${T.print.verifactu_verify}</p>
                    </div>
                ` : ''}
            </div>
        </body></html>`;
    }
}
