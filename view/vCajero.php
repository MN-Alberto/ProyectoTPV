<section id="cajero">
    <!-- Panel izquierdo: Categorías y productos -->
    <div class="cajero-productos">
        <form id="formBuscarProducto" onsubmit="buscarProductos(event)">
            <label for="buscarProducto">Buscar producto:</label>
            <input type="text" id="inputBuscarProducto" class="input-buscarProducto"
                placeholder="Introduce nombre del producto" />
            <button type="submit" class="btn-buscar">Buscar</button>
            <button type="button" class="btn-buscar btn-limpiar" onclick="limpiarBusqueda()">✕</button>
        </form>
        <div class="cajero-categorias">
            <button class="cat-btn activa" data-categoria="" onclick="seleccionarCategoria(this, null)">
                Todas
            </button>
            <?php foreach ($categorias as $cat): ?>
                <button class="cat-btn" data-categoria="<?php echo $cat->getId(); ?>"
                    onclick="seleccionarCategoria(this, <?php echo $cat->getId(); ?>)">
                    <?php echo htmlspecialchars($cat->getNombre()); ?>
                </button>
            <?php endforeach; ?>
        </div>

        <div class="productos-grid" id="productosGrid">
            <?php if (empty($productos)): ?>
                <p class="sin-productos">No hay productos disponibles.</p>
            <?php else: ?>
                <?php foreach ($productos as $prod): ?>
                    <div class="producto-card" data-id="<?php echo $prod->getId(); ?>"
                        data-nombre="<?php echo htmlspecialchars($prod->getNombre()); ?>"
                        data-precio="<?php echo $prod->getPrecio(); ?>" data-stock="<?php echo $prod->getStock(); ?>"
                        onclick="agregarAlCarrito(this)">
                        <div class="producto-nombre">
                            <?php echo htmlspecialchars($prod->getNombre()); ?>
                        </div>
                        <div class="producto-precio">
                            <?php echo number_format($prod->getPrecio(), 2, ',', '.'); ?> €
                        </div>
                        <div class="producto-stock">Stock:
                            <?php echo $prod->getStock(); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Panel derecho: Ticket / Carrito -->
    <div class="cajero-ticket">
        <div class="ticket-header">
            <h3>Ticket de Venta</h3>
            <span class="ticket-fecha">
                <?php echo date('d/m/Y H:i'); ?>
            </span>
        </div>

        <div class="ticket-lineas" id="ticketLineas">
            <p class="ticket-vacio">Añade productos al ticket</p>
        </div>

        <div class="ticket-total">
            <span>TOTAL:</span>
            <span id="ticketTotal">0,00 €</span>
        </div>

        <div class="ticket-acciones">
            <select id="metodoPago">
                <option value="efectivo">Efectivo</option>
                <option value="tarjeta">Tarjeta</option>
                <option value="bizum">Bizum</option>
            </select>
            <button class="btn-cobrar" id="btnCobrar" onclick="mostrarModalTipoDocumento()" disabled>
                Cobrar
            </button>
            <button class="btn-cancelar" onclick="vaciarCarrito()">
                Vaciar
            </button>
        </div>
    </div>

    <!-- Formulario oculto para enviar la venta -->
    <form id="formVenta" method="POST" action="index.php" style="display:none;">
        <input type="hidden" name="accion" value="registrarVenta">
        <input type="hidden" name="carrito" id="inputCarrito">
        <input type="hidden" name="metodoPago" id="inputMetodoPago">
        <input type="hidden" name="tipoDocumento" id="inputTipoDocumento">
    </form>
</section>

<!-- ==================== MODAL: TIPO DE DOCUMENTO ==================== -->
<div class="modal-overlay" id="modalTipoDoc" style="display:none;">
    <div class="modal-content modal-tipodoc">
        <h3>¿Cómo desea el comprobante?</h3>
        <p class="modal-subtitulo">Seleccione el tipo de documento para esta venta</p>
        <div class="modal-opciones-doc">
            <button class="opcion-doc" onclick="confirmarVenta('ticket')">
                <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path>
                    <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>
                </svg>
                <span class="opcion-titulo">Ticket</span>
                <span class="opcion-desc">Comprobante de venta simplificado</span>
            </button>
            <button class="opcion-doc" onclick="confirmarVenta('factura')">
                <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                    <polyline points="10 9 9 9 8 9"></polyline>
                </svg>
                <span class="opcion-titulo">Factura</span>
                <span class="opcion-desc">Documento fiscal completo</span>
            </button>
        </div>
        <button class="btn-modal-cancelar" onclick="cerrarModal('modalTipoDoc')">Cancelar</button>
    </div>
</div>

<!-- ==================== MODAL: VENTA EXITOSA ==================== -->
<?php if (isset($_SESSION['ventaExito']) && $_SESSION['ventaExito']): ?>
    <div class="modal-overlay" id="ventaExito">
        <div class="modal-content modal-exito">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icono-exito">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                <polyline points="22 4 12 14.01 9 11.01"></polyline>
            </svg>
            <h3>¡Venta registrada!</h3>
            <p class="exito-detalle">
                <?php echo ($_SESSION['ultimaVentaTipo'] === 'factura') ? 'Factura' : 'Ticket'; ?>
                #<?php echo $_SESSION['ultimaVentaId']; ?> — Total:
                <?php echo number_format($_SESSION['ultimaVentaTotal'], 2, ',', '.'); ?> €
            </p>

            <div class="exito-acciones">
                <button class="btn-exito btn-imprimir" onclick="imprimirDocumento()">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 9 6 2 18 2 18 9"></polyline>
                        <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2">
                        </path>
                        <rect x="6" y="14" width="12" height="8"></rect>
                    </svg>
                    Imprimir
                </button>
                <button class="btn-exito btn-email" onclick="mostrarFormEmail()">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z">
                        </path>
                        <polyline points="22,6 12,13 2,6"></polyline>
                    </svg>
                    Enviar por correo
                </button>
            </div>

            <div class="form-email" id="formEmail" style="display:none;">
                <label for="inputEmail">Correo electrónico:</label>
                <div class="email-input-group">
                    <input type="email" id="inputEmail" placeholder="cliente@ejemplo.com" />
                    <button class="btn-enviar-email" onclick="enviarPorCorreo()">Enviar</button>
                </div>
                <p class="email-status" id="emailStatus"></p>
            </div>

            <button class="btn-cerrar-exito" onclick="cerrarExito()">Aceptar</button>
        </div>
    </div>

    <!-- Datos de la última venta para JS -->
    <script>
        const ultimaVenta = {
            id: <?php echo $_SESSION['ultimaVentaId']; ?>,
            total: '<?php echo number_format($_SESSION['ultimaVentaTotal'], 2, ',', '.'); ?>',
            tipo: '<?php echo $_SESSION['ultimaVentaTipo']; ?>',
            carrito: <?php echo $_SESSION['ultimaVentaCarrito']; ?>,
            metodoPago: '<?php echo $_SESSION['ultimaVentaMetodoPago']; ?>',
            fecha: '<?php echo $_SESSION['ultimaVentaFecha']; ?>'
        };
    </script>

    <?php
    unset($_SESSION['ventaExito']);
    unset($_SESSION['ultimaVentaId']);
    unset($_SESSION['ultimaVentaTotal']);
    unset($_SESSION['ultimaVentaTipo']);
    unset($_SESSION['ultimaVentaCarrito']);
    unset($_SESSION['ultimaVentaMetodoPago']);
    unset($_SESSION['ultimaVentaFecha']);
?>
<?php endif; ?>

<!-- ==================== MODAL: ERROR ==================== -->
<?php if (isset($_SESSION['ventaError'])): ?>
    <div class="modal-overlay" id="ventaError">
        <div class="modal-content modal-error-content">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#dc2626"
                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="15" y1="9" x2="9" y2="15"></line>
                <line x1="9" y1="9" x2="15" y2="15"></line>
            </svg>
            <h3>Error en la venta</h3>
            <p><?php echo htmlspecialchars($_SESSION['ventaError']); ?></p>
            <button onclick="cerrarModal('ventaError')">Aceptar</button>
        </div>
    </div>
    <?php unset($_SESSION['ventaError']); ?>
<?php endif; ?>

<script>
    // ======================== CARRITO (persiste en memoria) ========================
    let carrito = [];

    function agregarAlCarrito(elemento) {
        const id = parseInt(elemento.dataset.id);
        const nombre = elemento.dataset.nombre;
        const precio = parseFloat(elemento.dataset.precio);
        const stockMax = parseInt(elemento.dataset.stock);

        const existente = carrito.find(item => item.idProducto === id);
        if (existente) {
            if (existente.cantidad >= stockMax) {
                alert('No hay más stock disponible para este producto.');
                return;
            }
            existente.cantidad++;
        } else {
            if (stockMax <= 0) {
                alert('Este producto no tiene stock disponible.');
                return;
            }
            carrito.push({ idProducto: id, nombre: nombre, precio: precio, cantidad: 1, stockMax: stockMax });
        }

        actualizarTicket();
    }

    function eliminarDelCarrito(index) {
        carrito.splice(index, 1);
        actualizarTicket();
    }

    function cambiarCantidad(index, nueva) {
        if (nueva <= 0) {
            eliminarDelCarrito(index);
            return;
        }
        // No permitir superar el stock máximo.
        if (nueva > carrito[index].stockMax) {
            alert('No hay más stock disponible para este producto.');
            return;
        }
        carrito[index].cantidad = nueva;
        actualizarTicket();
    }

    function vaciarCarrito() {
        carrito = [];
        actualizarTicket();
    }

    function actualizarTicket() {
        const contenedor = document.getElementById('ticketLineas');
        const totalEl = document.getElementById('ticketTotal');
        const btnCobrar = document.getElementById('btnCobrar');

        if (carrito.length === 0) {
            contenedor.innerHTML = '<p class="ticket-vacio">Añade productos al ticket</p>';
            totalEl.textContent = '0,00 €';
            btnCobrar.disabled = true;
            return;
        }

        let html = '<table class="ticket-tabla"><thead><tr><th>Producto</th><th>Cant.</th><th>Precio</th><th>Subt.</th><th></th></tr></thead><tbody>';
        let total = 0;

        carrito.forEach((item, i) => {
            const subtotal = item.precio * item.cantidad;
            total += subtotal;
            html += `<tr>
                <td>${item.nombre}</td>
                <td>
                    <div class="cantidad-control">
                        <button onclick="cambiarCantidad(${i}, ${item.cantidad - 1})">−</button>
                        <span>${item.cantidad}</span>
                        <button onclick="cambiarCantidad(${i}, ${item.cantidad + 1})">+</button>
                    </div>
                </td>
                <td>${item.precio.toFixed(2).replace('.', ',')} €</td>
                <td>${subtotal.toFixed(2).replace('.', ',')} €</td>
                <td><button class="btn-quitar" onclick="eliminarDelCarrito(${i})">✕</button></td>
            </tr>`;
        });

        html += '</tbody></table>';
        contenedor.innerHTML = html;
        totalEl.textContent = total.toFixed(2).replace('.', ',') + ' €';
        btnCobrar.disabled = false;
    }

    // ======================== CATEGORÍAS (AJAX) ========================
    function seleccionarCategoria(boton, idCategoria) {
        // Actualizar clase activa.
        document.querySelectorAll('.cat-btn').forEach(b => b.classList.remove('activa'));
        boton.classList.add('activa');

        // Limpiar campo de búsqueda.
        document.getElementById('inputBuscarProducto').value = '';

        // Cargar productos por AJAX.
        let url = 'api/productos.php';
        if (idCategoria !== null) {
            url += '?idCategoria=' + idCategoria;
        }

        fetch(url)
            .then(res => res.json())
            .then(data => renderProductos(data))
            .catch(err => {
                console.error('Error cargando productos:', err);
                document.getElementById('productosGrid').innerHTML =
                    '<p class="sin-productos">Error al cargar los productos.</p>';
            });
    }

    // ======================== BÚSQUEDA (AJAX) ========================
    function buscarProductos(event) {
        event.preventDefault();
        const texto = document.getElementById('inputBuscarProducto').value.trim();
        if (!texto) return;

        // Quitar selección activa de categorías.
        document.querySelectorAll('.cat-btn').forEach(b => b.classList.remove('activa'));

        fetch('api/productos.php?buscarProducto=' + encodeURIComponent(texto))
            .then(res => res.json())
            .then(data => renderProductos(data))
            .catch(err => {
                console.error('Error buscando productos:', err);
                document.getElementById('productosGrid').innerHTML =
                    '<p class="sin-productos">Error al buscar productos.</p>';
            });
    }

    function limpiarBusqueda() {
        document.getElementById('inputBuscarProducto').value = '';
        // Recargar todos los productos y activar "Todas".
        const btnTodas = document.querySelector('.cat-btn[data-categoria=""]');
        if (btnTodas) seleccionarCategoria(btnTodas, null);
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
            html += `<div class="producto-card" data-id="${prod.id}"
                        data-nombre="${prod.nombre.replace(/"/g, '&quot;')}"
                        data-precio="${prod.precio}" data-stock="${prod.stock}"
                        onclick="agregarAlCarrito(this)">
                        <div class="producto-nombre">${prod.nombre}</div>
                        <div class="producto-precio">${prod.precio.toFixed(2).replace('.', ',')} €</div>
                        <div class="producto-stock">Stock: ${prod.stock}</div>
                    </div>`;
        });

        grid.innerHTML = html;
    }

    // ======================== MODAL TIPO DOCUMENTO ========================
    function mostrarModalTipoDocumento() {
        if (carrito.length === 0) return;
        document.getElementById('modalTipoDoc').style.display = 'flex';
    }

    function confirmarVenta(tipoDocumento) {
        cerrarModal('modalTipoDoc');

        document.getElementById('inputCarrito').value = JSON.stringify(carrito);
        document.getElementById('inputMetodoPago').value = document.getElementById('metodoPago').value;
        document.getElementById('inputTipoDocumento').value = tipoDocumento;
        document.getElementById('formVenta').submit();
    }

    // ======================== MODAL GENÉRICO ========================
    function cerrarModal(id) {
        const modal = document.getElementById(id);
        if (modal) modal.style.display = 'none';
    }

    // ======================== MODAL VENTA EXITOSA ========================
    function cerrarExito() {
        document.getElementById('ventaExito').remove();
    }

    // ======================== IMPRIMIR ========================
    function imprimirDocumento() {
        if (typeof ultimaVenta === 'undefined') return;

        const tipoTitulo = (ultimaVenta.tipo === 'factura') ? 'FACTURA' : 'TICKET DE VENTA';

        let lineasHtml = '';
        ultimaVenta.carrito.forEach(item => {
            const subtotal = (item.precio * item.cantidad).toFixed(2).replace('.', ',');
            const precioFmt = item.precio.toFixed(2).replace('.', ',');
            lineasHtml += `<tr>
                <td>${item.nombre}</td>
                <td style="text-align:center">${item.cantidad}</td>
                <td style="text-align:right">${precioFmt} €</td>
                <td style="text-align:right">${subtotal} €</td>
            </tr>`;
        });

        const contenido = `
        <html>
        <head>
            <title>${tipoTitulo} #${ultimaVenta.id}</title>
            <style>
                body { font-family: 'Inter', Arial, sans-serif; padding: 30px; color: #333; max-width: 600px; margin: 0 auto; }
                .header { text-align: center; border-bottom: 2px solid #1a1a2e; padding-bottom: 15px; margin-bottom: 20px; }
                .header h1 { margin: 0; font-size: 1.4rem; color: #1a1a2e; }
                .header h2 { margin: 5px 0 0; font-size: 1rem; color: #666; }
                .datos { margin-bottom: 20px; font-size: 0.9rem; }
                .datos p { margin: 4px 0; }
                table { width: 100%; border-collapse: collapse; margin: 15px 0; }
                th { background: #f0f2f5; padding: 10px 8px; text-align: left; border-bottom: 2px solid #ddd; font-size: 0.85rem; }
                td { padding: 10px 8px; border-bottom: 1px solid #eee; font-size: 0.85rem; }
                .total { text-align: right; font-size: 1.3rem; font-weight: bold; padding: 15px 0; border-top: 2px solid #1a1a2e; }
                .footer { text-align: center; color: #999; font-size: 0.8rem; padding-top: 20px; border-top: 1px solid #eee; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>TPV Bazar — Productos Informáticos</h1>
                <h2>${tipoTitulo}</h2>
            </div>
            <div class="datos">
                <p><strong>Nº:</strong> ${ultimaVenta.id}</p>
                <p><strong>Fecha:</strong> ${ultimaVenta.fecha}</p>
                <p><strong>Método de pago:</strong> ${ultimaVenta.metodoPago}</p>
            </div>
            <table>
                <thead>
                    <tr><th>Producto</th><th style="text-align:center">Cant.</th><th style="text-align:right">Precio</th><th style="text-align:right">Subtotal</th></tr>
                </thead>
                <tbody>${lineasHtml}</tbody>
            </table>
            <div class="total">TOTAL: ${ultimaVenta.total} €</div>
            <div class="footer">
                <p>TPV Bazar — Productos Informáticos</p>
                <p>Gracias por su compra</p>
            </div>
        </body>
        </html>`;

        // Crear iframe oculto para imprimir.
        const iframe = document.createElement('iframe');
        iframe.style.position = 'absolute';
        iframe.style.top = '-10000px';
        document.body.appendChild(iframe);
        iframe.contentDocument.write(contenido);
        iframe.contentDocument.close();

        iframe.onload = function () {
            iframe.contentWindow.print();
            setTimeout(() => iframe.remove(), 1000);
        };
    }

    // ======================== ENVIAR POR CORREO ========================
    function mostrarFormEmail() {
        document.getElementById('formEmail').style.display = 'block';
        document.getElementById('inputEmail').focus();
    }

    function enviarPorCorreo() {
        if (typeof ultimaVenta === 'undefined') return;

        const email = document.getElementById('inputEmail').value.trim();
        const statusEl = document.getElementById('emailStatus');

        if (!email || !email.includes('@')) {
            statusEl.textContent = 'Por favor, introduce un correo válido.';
            statusEl.className = 'email-status email-error';
            return;
        }

        statusEl.textContent = 'Enviando...';
        statusEl.className = 'email-status email-enviando';

        fetch('api/enviarCorreo.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                email: email,
                tipoDocumento: ultimaVenta.tipo,
                ventaId: ultimaVenta.id,
                total: ultimaVenta.total,
                lineas: ultimaVenta.carrito,
                fecha: ultimaVenta.fecha,
                metodoPago: ultimaVenta.metodoPago
            })
        })
            .then(res => res.json())
            .then(data => {
                if (data.ok) {
                    statusEl.textContent = '✓ Correo enviado correctamente a ' + email;
                    statusEl.className = 'email-status email-ok';
                } else {
                    statusEl.textContent = '✗ ' + (data.mensaje || 'Error al enviar el correo.');
                    statusEl.className = 'email-status email-error';
                }
            })
            .catch(err => {
                statusEl.textContent = '✗ Error de conexión al enviar el correo.';
                statusEl.className = 'email-status email-error';
            });
    }
</script>