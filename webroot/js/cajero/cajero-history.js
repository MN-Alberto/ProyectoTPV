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
 * Muestra el historial de ventas (alias de cargarHistorialVentas para compatibilidad)
 */
function mostrarHistorialVentas() {
    if (typeof cargarHistorialVentas === 'function') {
        cargarHistorialVentas();
        const modal = document.getElementById('modalHistorialVentas');
        if (modal) modal.style.display = 'flex';
    }
}