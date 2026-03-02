<!-- ============================================================================
     vCajero.php - Vista principal del Terminal Punto de Venta (TPV)
     ============================================================================
     Este archivo define la interfaz completa del cajero del TPV. Incluye:
       - Panel izquierdo: buscador de productos, categorías, botones de acción
         (Hacer Caja, Abrir Caja, Devolución, Retirar Dinero) y grid de productos
       - Panel derecho: ticket/carrito con líneas de venta, totales, método de pago
         y botones de cobro/descuento/vaciar
       - Modales: Cambio en efectivo, Tipo de documento, Descuento, Datos del cliente,
         Venta exitosa, Abrir Caja, Devolución, Retiro de dinero, Cierre de caja, Errores
       - Scripts JS: lógica del carrito, descuentos, cobro, impresión, envío por correo,
         devoluciones y utilidades
     
     Variables PHP disponibles (inyectadas desde el controlador cCajero.php):
       - $categorias: array de objetos Categoria para los filtros
       - $productos: array de objetos Producto para mostrar en el grid
       - $sesionCaja: objeto Caja con la sesión activa (o null si la caja está cerrada)
       - $_SESSION: variables de sesión para modales de éxito/error/cierre de caja
     ============================================================================ -->

<!-- ##=========================== SECCIÓN PRINCIPAL DEL CAJERO ===========================## -->
<!-- Contenedor principal dividido en dos paneles: productos (izquierda) y ticket (derecha) -->
<section id="cajero">

    <!-- ======================== PANEL IZQUIERDO: CATEGORÍAS Y PRODUCTOS ======================== -->
    <!-- Contiene el buscador, filtros de categoría, botones de acción y el grid de productos -->
    <div class="cajero-productos">

        <!-- ==================== BUSCADOR DE PRODUCTOS ==================== -->
        <!-- Input de búsqueda que filtra productos en tiempo real mediante la función buscarProductos() -->
        <div id="formBuscarProducto" style="align-items: center;">
            <label for="inputBuscarProducto"
                style="font-weight: 600; color: #1a1a2e; white-space: nowrap;">Buscar:</label>
            <input type="text" id="inputBuscarProducto" class="input-buscarProducto"
                placeholder="Escribe el nombre del producto a buscar..." oninput="buscarProductos()" autocomplete="off"
                style="width: 100%;" />
        </div>

        <!-- ==================== FILTROS DE CATEGORÍA ==================== -->
        <!-- Botones generados dinámicamente desde PHP para filtrar productos por categoría -->
        <!-- El botón "Todos" (data-categoria="") muestra todos los productos sin filtro -->
        <div class="cajero-categorias">
            <!-- Botón "Todos": muestra todos los productos, activo por defecto -->
            <button class="cat-btn activa" data-categoria="" onclick="seleccionarCategoria(this, null)">
                Todos
            </button>
            <!-- Bucle PHP: genera un botón por cada categoría existente en la base de datos -->
            <?php foreach ($categorias as $cat): ?>
                <button class="cat-btn" data-categoria="<?php echo $cat->getId(); ?>"
                    onclick="seleccionarCategoria(this, <?php echo $cat->getId(); ?>)">
                    <?php echo htmlspecialchars($cat->getNombre()); ?>
                </button>
            <?php endforeach; ?>
        </div>

        <!-- ==================== BARRA DE OPCIONES EXTRA ==================== -->
        <!-- Contiene los botones de acción principales y el indicador de efectivo en caja -->
        <div class="cajero-opciones-extra"
            style="padding: 15px 20px; background: #fff; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center; gap: 15px;">

            <div style="display: flex; gap: 10px; align-items: center;">

                <!-- Botón HACER CAJA: envía formulario POST para previsualizar el cierre de caja -->
                <!-- Se deshabilita si no hay sesión de caja activa ($sesionCaja es null) -->
                <form method="POST" action="index.php" style="margin: 0;">
                    <input type="hidden" name="accion" value="previsualizarCaja">
                    <button type="submit" class="btn-hacerCaja" id="btnHacerCaja" <?php echo !$sesionCaja ? 'disabled' : ''; ?>
                        style="<?php echo !$sesionCaja ? 'opacity: 0.3; background: red; cursor: not-allowed;' : ''; ?>">
                        <!-- Icono SVG de símbolo de dólar/moneda -->
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="1" x2="12" y2="23"></line>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                        </svg>
                        Hacer Caja
                    </button>
                </form>

                <!-- Botón ABRIR CAJA: abre el modal para introducir el fondo de caja inicial -->
                <!-- Se deshabilita si ya hay una sesión de caja activa -->
                <button type="button" class="btn-abrirCaja" id="btnAbrirCaja" onclick="mostrarModalAbrirCaja()" <?php echo $sesionCaja ? 'disabled' : ''; ?>
                    style="<?php echo $sesionCaja ? 'opacity: 0.3; background: red; cursor: not-allowed;' : ''; ?>">
                    <!-- Icono SVG de candado abierto -->
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                    </svg>
                    Abrir Caja
                </button>

                <!-- Botón DEVOLUCIÓN: abre el modal para tramitar una devolución de producto -->
                <!-- Se deshabilita si no hay sesión de caja activa -->
                <button type="button" class="btn-devolucion" id="btnDevolucion" onclick="mostrarModalDevolucion()" <?php echo !$sesionCaja ? 'disabled' : ''; ?>
                    style="<?php echo !$sesionCaja ? 'opacity: 0.3; background: #991b1b; cursor: not-allowed;' : ''; ?>">
                    <!-- Icono SVG de flecha de retorno (devolución) -->
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1 4v6h6"></path>
                        <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
                    </svg>
                    Devolución
                </button>

                <!-- Botón RETIRAR DINERO: abre el modal para retirar efectivo de la caja -->
                <!-- Se deshabilita si no hay sesión de caja activa -->
                <button type="button" class="btn-retiro" id="btnRetiro" onclick="mostrarModalRetiro()" <?php echo !$sesionCaja ? 'disabled' : ''; ?>
                    style="<?php echo !$sesionCaja ? 'opacity: 0.3; background: #ea580c; cursor: not-allowed;' : ''; ?>">
                    <!-- Icono SVG de billete/dinero -->
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect width="20" height="12" x="2" y="6" rx="2"></rect>
                        <circle cx="12" cy="12" r="2"></circle>
                        <path d="M6 12h.01M18 12h.01"></path>
                    </svg>
                    Retirar Dinero
                </button>
            </div>

            <!-- INDICADOR DE EFECTIVO EN CAJA -->
            <!-- Muestra el importe actual en caja si hay sesión activa, o "Caja Cerrada" si no -->
            <?php if ($sesionCaja): ?>
                <div class="indicador-efectivo" title="Efectivo actual en caja">
                    <span class="label">Efectivo en caja:</span>
                    <span class="amount"><?php echo number_format($sesionCaja->getImporteActual(), 2, ',', '.'); ?> €</span>
                </div>
            <?php else: ?>
                <div class="indicador-efectivo caja-cerrada">
                    <span class="label">Caja Cerrada</span>
                </div>
            <?php endif; ?>
        </div>

        <!-- ==================== GRID DE PRODUCTOS ==================== -->
        <!-- Muestra las tarjetas de producto en formato grid -->
        <!-- Cada tarjeta contiene: nombre, imagen, precio y stock -->
        <!-- Al hacer clic en una tarjeta se ejecuta agregarAlCarrito(this) -->
        <div class="productos-grid" id="productosGrid">
            <?php if (empty($productos)): ?>
                <!-- Mensaje cuando no hay productos disponibles -->
                <p class="sin-productos">No hay productos disponibles.</p>
            <?php else: ?>
                <!-- Bucle PHP: genera una tarjeta por cada producto -->
                <?php foreach ($productos as $prod): ?>
                    <!-- Tarjeta de producto con atributos data-* para el carrito JS -->
                    <!-- data-id: ID del producto -->
                    <!-- data-nombre: nombre del producto (escapado con htmlspecialchars) -->
                    <!-- data-precio: precio unitario -->
                    <!-- data-stock: stock disponible -->
                    <!-- Si el stock es 0, la tarjeta se muestra con opacidad reducida y sin interacción -->
                    <div class="producto-card" data-id="<?php echo $prod->getId(); ?>"
                        data-nombre="<?php echo htmlspecialchars($prod->getNombre()); ?>"
                        data-precio="<?php echo $prod->getPrecio(); ?>" data-stock="<?php echo $prod->getStock(); ?>"
                        onclick="agregarAlCarrito(this)" style="<?php if ($prod->getStock() <= 0) {
                            echo 'opacity: 0.5; cursor: not-allowed; scale: 1; transform: translateY(0px);';
                        } ?>">

                        <!-- Nombre del producto -->
                        <div class="producto-nombre">
                            <?php echo htmlspecialchars($prod->getNombre()); ?>
                        </div>

                        <!-- Imagen del producto (usa logo.PNG como fallback si no tiene imagen) -->
                        <div class="producto-imagen">
                            <?php
                            $imgSrc = !empty($prod->getImagen()) ? $prod->getImagen() : 'webroot/img/logo.PNG';
                            echo '<img src="' . htmlspecialchars($imgSrc) . '" alt="' . htmlspecialchars($prod->getNombre()) . '">';
                            ?>
                        </div>

                        <!-- Precio y stock del producto -->
                        <!-- El stock se muestra en rojo y subrayado si es 0 o menor -->
                        <div class="producto-info-inferior">
                            <span class="producto-precio"><?php echo number_format($prod->getPrecio(), 2, ',', '.'); ?> €</span>
                            <span class="producto-stock" <?php if ($prod->getStock() <= 0) {
                                echo 'style="color: red; text-decoration: underline;"';
                            } ?>>Stock: <?php echo $prod->getStock(); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- ======================== PANEL DERECHO: TICKET / CARRITO ======================== -->
    <!-- Muestra los productos añadidos al carrito, el total, método de pago y acciones -->
    <div class="cajero-ticket">

        <!-- Cabecera del ticket: título y reloj en tiempo real -->
        <div class="ticket-header">
            <h3>Productos añadidos</h3>
            <!-- Reloj que se actualiza cada segundo mostrando fecha y hora actual -->
            <span class="ticket-fecha">
                <script>
                    /**
                     * actualizarFechaHora()
                     * Actualiza el elemento .ticket-fecha con la fecha y hora actual
                     * en formato DD/MM/AAAA HH:MM:SS. Se ejecuta cada segundo.
                     */
                    function actualizarFechaHora() {
                        const ahora = new Date();

                        // Formatear día, mes y año con padding de ceros
                        const dia = String(ahora.getDate()).padStart(2, '0');
                        const mes = String(ahora.getMonth() + 1).padStart(2, '0');
                        const anio = ahora.getFullYear();

                        // Formatear horas, minutos y segundos con padding de ceros
                        const horas = String(ahora.getHours()).padStart(2, '0');
                        const minutos = String(ahora.getMinutes()).padStart(2, '0');
                        const segundos = String(ahora.getSeconds()).padStart(2, '0');

                        // Componer la cadena de fecha y hora
                        const fechaHora = `${dia}/${mes}/${anio} ${horas}:${minutos}:${segundos}`;

                        // Inyectar en el DOM
                        document.querySelector('.ticket-fecha').textContent = fechaHora;
                    }

                    // Ejecutar inmediatamente y luego cada 1000ms (1 segundo)
                    actualizarFechaHora();
                    setInterval(actualizarFechaHora, 1000);
                </script>
            </span>
        </div>

        <!-- Contenedor de las líneas del ticket (se rellena dinámicamente con JS) -->
        <div class="ticket-lineas" id="ticketLineas">
            <p class="ticket-vacio">Añade productos para realizar la venta</p>
        </div>

        <!-- Contenedor para el desglose de subtotal y descuento (se rellena con JS) -->
        <div id="ticketDesglose"></div>

        <!-- Total del ticket -->
        <div class="ticket-total">
            <span>TOTAL:</span>
            <span id="ticketTotal">0,00 €</span>
        </div>

        <!-- ==================== ACCIONES DEL TICKET ==================== -->
        <!-- Selector de método de pago, aviso de límite de efectivo y botones de acción -->
        <div class="ticket-acciones">
            <!-- Selector de método de pago: Efectivo, Tarjeta o Bizum -->
            <select id="metodoPago">
                <option value="efectivo">Efectivo</option>
                <option value="tarjeta">Tarjeta</option>
                <option value="bizum">Bizum</option>
            </select>

            <!-- Aviso legal: no se permite pago en efectivo superior a 1.000€ -->
            <div id="avisoLimiteEfectivo"
                style="display: none; color: #dc2626; font-size: 0.75rem; margin-top: 5px; font-weight: 600;">
                ⚠️ No se permite pago en efectivo > 1.000€
            </div>

            <!-- Botón COBRAR: inicia el proceso de cobro (modal de cambio o tipo de documento) -->
            <button class="btn-cobrar" id="btnCobrar" onclick="intentarCobrar()" disabled>
                Cobrar
            </button>

            <!-- Botón DESCUENTO: abre el modal para aplicar descuento porcentual o por cupón -->
            <button class="btn-descuento" id="btnDescuento" onclick="aplicarDescuento()" disabled>
                Descuento
            </button>

            <!-- Botón VACIAR: elimina todos los productos del carrito -->
            <button class="btn-cancelar" onclick="vaciarCarrito()">
                Vaciar
            </button>
        </div>
    </div>

    <!-- ==================== FORMULARIO OCULTO PARA ENVIAR LA VENTA ==================== -->
    <!-- Este formulario se rellena con JavaScript y se envía por POST al confirmar la venta -->
    <!-- Contiene todos los datos necesarios: carrito, método de pago, tipo de documento,
         dinero entregado/cambio, datos del cliente y descuento aplicado -->
    <form id="formVenta" method="POST" action="index.php" style="display:none;">
        <input type="hidden" name="accion" value="registrarVenta">
        <!-- Carrito serializado como JSON -->
        <input type="hidden" name="carrito" id="inputCarrito">
        <!-- Método de pago seleccionado (efectivo/tarjeta/bizum) -->
        <input type="hidden" name="metodoPago" id="inputMetodoPago">
        <!-- Tipo de documento (ticket/factura) -->
        <input type="hidden" name="tipoDocumento" id="inputTipoDocumento">
        <!-- Dinero entregado por el cliente (en pago efectivo) -->
        <input type="hidden" name="dineroEntregado" id="inputDineroEntregadoFinal">
        <!-- Cambio devuelto al cliente -->
        <input type="hidden" name="cambioDevuelto" id="inputCambioDevueltoFinal">
        <!-- Campos de datos del cliente -->
        <input type="hidden" name="clienteNif" id="inputClienteNifFinal">
        <input type="hidden" name="clienteNombre" id="inputClienteNombreFinal">
        <input type="hidden" name="clienteDireccion" id="inputClienteDireccionFinal">
        <input type="hidden" name="observaciones" id="inputObservacionesFinal">
        <!-- Campos de descuento aplicado -->
        <input type="hidden" name="descuentoTipo" id="inputDescuentoTipo">
        <input type="hidden" name="descuentoValor" id="inputDescuentoValor">
        <input type="hidden" name="descuentoCupon" id="inputDescuentoCupon">
    </form>
</section>

<!-- ##=========================== MODAL: CALCULAR CAMBIO (EFECTIVO) ===========================## -->
<!-- Modal que aparece cuando el método de pago es "efectivo" -->
<!-- Permite al cajero introducir la cantidad entregada por el cliente y calcula el cambio -->
<div class="modal-overlay" id="modalCambio" style="display:none;">
    <div class="modal-content modal-cambio" style="max-width: 400px;">
        <h3>Pago en Efectivo</h3>
        <p class="modal-subtitulo">Introduce la cantidad entregada por el cliente</p>

        <!-- Contenedor del cálculo de cambio -->
        <div class="calculo-cambio-container"
            style="text-align: left; background: #f9fafb; padding: 20px; border-radius: 8px; margin: 15px 0;">

            <!-- Fila 1: Total a pagar (se rellena con JS) -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <span style="font-size: 1.1rem; color: #4b5563;">Total a pagar:</span>
                <span id="cambioTotalPagar" style="font-size: 1.4rem; font-weight: bold; color: #1f2937;">0,00 €</span>
            </div>

            <!-- Fila 2: Input para la cantidad entregada por el cliente -->
            <!-- oninput: recalcula el cambio en tiempo real -->
            <!-- onkeypress Enter: confirma el cambio directamente -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <label for="inputDineroEntregado" style="font-size: 1.1rem; color: #4b5563;">Entregado:</label>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <input type="number" id="inputDineroEntregado" step="0.01" min="0" placeholder="0.00"
                        style="padding: 10px; font-size: 1.2rem; width: 120px; text-align: right; border: 1px solid #d1d5db; border-radius: 6px; outline: none;"
                        oninput="calcularCambio()" onkeypress="if(event.key === 'Enter') confirmarCambio()">
                    <span style="font-size: 1.2rem; color: #4b5563;">€</span>
                </div>
            </div>

            <!-- Fila 3: Cambio a devolver (calculado automáticamente) -->
            <div
                style="display: flex; justify-content: space-between; align-items: center; border-top: 2px solid #e5e7eb; padding-top: 15px;">
                <span style="font-size: 1.1rem; font-weight: bold; color: #4b5563;">Cambio a devolver:</span>
                <span id="cambioDevolver" style="font-size: 1.8rem; font-weight: bold; color: #22c55e;">0,00 €</span>
            </div>

            <!-- Mensaje de error: se muestra si la cantidad entregada es insuficiente -->
            <p id="cambioError"
                style="color: #ef4444; font-size: 0.9rem; margin-top: 15px; text-align: center; display: none;">La
                cantidad entregada es insuficiente para realizar el cobro.</p>
        </div>

        <!-- Botones: Cancelar (cierra el modal) y Continuar (valida y avanza al tipo de documento) -->
        <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
            <button class="btn-modal-cancelar" onclick="cerrarModal('modalCambio')" style="flex: 1;">Cancelar</button>
            <button class="btn-exito" onclick="confirmarCambio()"
                style="flex: 1; margin: 0; display: flex; justify-content: center; align-items: center;">Continuar</button>
        </div>
    </div>
</div>

<!-- ##=========================== MODAL: TIPO DE DOCUMENTO ===========================## -->
<!-- Modal que permite al usuario elegir entre Ticket (simplificado) o Factura (completa) -->
<!-- Aparece después del modal de cambio (efectivo) o directamente (tarjeta/bizum) -->
<div class="modal-overlay" id="modalTipoDoc" style="display:none;">
    <div class="modal-content modal-tipodoc">
        <h3>¿Cómo desea el comprobante?</h3>
        <p class="modal-subtitulo">Seleccione el tipo de documento para esta venta</p>
        <div class="modal-opciones-doc">

            <!-- Opción TICKET: comprobante simplificado (datos del cliente opcionales) -->
            <button class="opcion-doc" onclick="seleccionarDatosCliente('ticket')">
                <!-- Icono SVG de libro/ticket -->
                <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path>
                    <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>
                </svg>
                <span class="opcion-titulo">Ticket</span>
                <span class="opcion-desc">Comprobante de venta simplificado</span>
            </button>

            <!-- Opción FACTURA: documento fiscal completo (datos del cliente obligatorios) -->
            <button class="opcion-doc" onclick="seleccionarDatosCliente('factura')">
                <!-- Icono SVG de documento/factura -->
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

<!-- ##=========================== MODAL: DESCUENTO ===========================## -->
<!-- Modal para aplicar un descuento a la venta actual -->
<!-- Permite dos opciones: porcentaje manual (0-100%) o código de cupón promocional -->
<div class="modal-overlay" id="modalDescuento" style="display:none;">
    <div class="modal-content modal-premium" style="max-width: 420px;">
        <!-- Cabecera del modal con icono de descuento -->
        <div class="modal-header-premium">
            <div class="icon-container-discount">
                <!-- Icono SVG de tijeras/descuento -->
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="5" x2="5" y2="19"></line>
                    <circle cx="6.5" cy="6.5" r="2.5"></circle>
                    <circle cx="17.5" cy="17.5" r="2.5"></circle>
                </svg>
            </div>
            <h3>Aplicar Descuento</h3>
            <p>Elige un porcentaje o usa un código promocional</p>
        </div>

        <div class="modal-body-premium">
            <!-- Opción 1: Descuento por porcentaje (0% a 100%) -->
            <div class="form-group-premium">
                <label for="inputPorcentajeDescuento">Porcentaje de Descuento</label>
                <div class="input-with-icon">
                    <input type="number" id="inputPorcentajeDescuento" min="0" max="100" step="1" placeholder="0">
                    <span class="input-suffix">%</span>
                </div>
                <span class="input-hint">Rango permitido: 0% a 100%</span>
            </div>

            <!-- Separador visual entre las dos opciones -->
            <div class="divider-text">o bien</div>

            <!-- Opción 2: Descuento por código de cupón -->
            <!-- Cupones válidos: PROMO10 (10%), BIENVENIDA5 (5%), FIJO5 (5€ fijo) -->
            <div class="form-group-premium">
                <label for="inputCuponDescuento">Código de Cupón</label>
                <div class="input-cupon">
                    <input type="text" id="inputCuponDescuento" placeholder="Introduce el código"
                        style="text-transform: uppercase;">
                </div>
            </div>
        </div>

        <!-- Botones: Cancelar y Aplicar Descuento -->
        <div class="modal-footer-premium">
            <button class="btn-cancel-flat" onclick="cerrarModal('modalDescuento')">Cancelar</button>
            <button class="btn-apply-premium" onclick="procesarDescuento()">Aplicar Descuento</button>
        </div>
    </div>
</div>

<!-- ##=========================== MODAL: DATOS DEL CLIENTE ===========================## -->
<!-- Modal para introducir los datos del cliente antes de finalizar la venta -->
<!-- En modo Ticket: los campos son opcionales -->
<!-- En modo Factura: NIF, Nombre y Dirección son obligatorios (marcados con *) -->
<div class="modal-overlay" id="modalDatosCliente" style="display:none;">
    <div class="modal-content" style="max-width: 500px; text-align: left;">
        <!-- Título dinámico que cambia según sea Ticket o Factura -->
        <h3 id="tituloDatosCliente" style="margin-bottom: 5px; color: #1a1a2e;">Datos del Cliente</h3>
        <p id="subtituloDatosCliente" style="color: #6b7280; font-size: 0.9rem; margin-bottom: 20px;">Complete los datos
            (Opcional en Ticket)</p>

        <div style="display: grid; gap: 15px;">
            <!-- Campo NIF/CIF del cliente -->
            <!-- El asterisco rojo (*) se muestra solo en modo Factura -->
            <div>
                <label for="clienteNif"
                    style="display: block; margin-bottom: 5px; font-weight: 500; font-size: 0.9rem;">NIF/CIF <span
                        id="reqNif" style="color: #ef4444; display: none;">*</span></label>
                <input type="text" id="clienteNif"
                    style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;"
                    placeholder="B12345678">
            </div>

            <!-- Campo Razón Social / Nombre del cliente -->
            <div>
                <label for="clienteNombre"
                    style="display: block; margin-bottom: 5px; font-weight: 500; font-size: 0.9rem;">Razón Social /
                    Nombre <span id="reqNombre" style="color: #ef4444; display: none;">*</span></label>
                <input type="text" id="clienteNombre"
                    style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;"
                    placeholder="Nombre de la empresa o persona">
            </div>

            <!-- Campo Domicilio Fiscal (solo visible en modo Factura) -->
            <div id="divDireccionCliente" style="display: none;">
                <label for="clienteDireccion"
                    style="display: block; margin-bottom: 5px; font-weight: 500; font-size: 0.9rem;">Domicilio Fiscal
                    <span id="reqDir" style="color: #ef4444; display: none;">*</span></label>
                <input type="text" id="clienteDireccion"
                    style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;"
                    placeholder="Calle, Número, C.P, Ciudad">
            </div>

            <!-- Campo Observaciones (solo visible en modo Factura, siempre opcional) -->
            <div id="divObservacionesCliente" style="display: none;">
                <label for="clienteObservaciones"
                    style="display: block; margin-bottom: 5px; font-weight: 500; font-size: 0.9rem;">Observaciones
                    (Régimen, Exento, etc.)</label>
                <input type="text" id="clienteObservaciones"
                    style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;"
                    placeholder="Opcional">
            </div>
        </div>

        <!-- Mensaje de error: se muestra si faltan campos obligatorios en modo Factura -->
        <p id="errorDatosCliente" style="color: #ef4444; font-size: 0.9rem; margin-top: 15px; display: none;">Por favor,
            rellene todos los campos obligatorios (*).</p>

        <!-- Botones: Atrás (vuelve al modal anterior) y Finalizar Venta (valida y envía) -->
        <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 25px;">
            <button class="btn-modal-cancelar" onclick="cerrarModal('modalDatosCliente')">Atrás</button>
            <button class="btn-exito" id="btnConfirmarDatos" onclick="validarYConfirmarVenta()"
                style="margin: 0;">Finalizar Venta</button>
        </div>
    </div>
</div>

<!-- ##=========================== MODAL: VENTA EXITOSA ===========================## -->
<!-- Se muestra automáticamente cuando $_SESSION['ventaExito'] es true -->
<!-- Contiene: resumen de la venta, botones para imprimir y enviar por correo -->
<?php if (isset($_SESSION['ventaExito']) && $_SESSION['ventaExito']): ?>
    <div class="modal-overlay" id="ventaExito">
        <div class="modal-content modal-exito">
            <!-- Icono de check/éxito animado -->
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icono-exito">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                <polyline points="22 4 12 14.01 9 11.01"></polyline>
            </svg>
            <h3>¡Venta registrada!</h3>

            <!-- Detalle de la venta: tipo de documento, número y total -->
            <p class="exito-detalle">
                <?php echo ($_SESSION['ultimaVentaTipo'] === 'factura') ? 'Factura' : 'Ticket'; ?>
                #<?php echo $_SESSION['ultimaVentaId']; ?> — Total:
                <?php echo number_format($_SESSION['ultimaVentaTotal'], 2, ',', '.'); ?> €
            </p>

            <!-- Botones de acción post-venta -->
            <div class="exito-acciones">
                <!-- Botón IMPRIMIR: genera el documento en una ventana de impresión -->
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

                <!-- Botón ENVIAR POR CORREO: muestra el formulario de email -->
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

            <!-- Formulario de envío por email (oculto por defecto, se muestra al pulsar "Enviar por correo") -->
            <div class="form-email" id="formEmail" style="display:none;">
                <label for="inputEmail">Correo electrónico:</label>
                <div class="email-input-group">
                    <input type="email" id="inputEmail" placeholder="cliente@ejemplo.com" />
                    <button class="btn-enviar-email" onclick="enviarPorCorreo()">Enviar</button>
                </div>
                <!-- Estado del envío: enviando, éxito o error -->
                <p class="email-status" id="emailStatus"></p>
            </div>

            <!-- Botón para cerrar el modal de éxito -->
            <button class="btn-cerrar-exito" onclick="cerrarExito()">Aceptar</button>
        </div>
    </div>

    <!-- Script: datos de la última venta pasados de PHP a JavaScript -->
    <!-- Se usa para las funciones de impresión y envío por correo -->
    <script>
        const ultimaVenta = {
            id: <?php echo $_SESSION['ultimaVentaId']; ?>,                                          // ID de la venta
            total: '<?php echo number_format($_SESSION['ultimaVentaTotal'], 2, ',', '.'); ?>',       // Total formateado
            tipo: '<?php echo $_SESSION['ultimaVentaTipo']; ?>',                                     // 'ticket' o 'factura'
            carrito: <?php echo $_SESSION['ultimaVentaCarrito']; ?>,                                  // Array de productos (JSON)
            metodoPago: '<?php echo $_SESSION['ultimaVentaMetodoPago']; ?>',                         // Método de pago usado
            fecha: '<?php echo $_SESSION['ultimaVentaFecha']; ?>',                                   // Fecha de la venta
            entregado: '<?php echo number_format($_SESSION['ultimaVentaEntregado'] ?? $_SESSION['ultimaVentaTotal'], 2, ',', '.'); ?>', // Dinero entregado
            cambio: '<?php echo number_format($_SESSION['ultimaVentaCambio'] ?? 0, 2, ',', '.'); ?>', // Cambio devuelto
            clienteNif: '<?php echo addslashes($_SESSION['ultimaVentaClienteNif'] ?? ''); ?>',       // NIF del cliente
            clienteNombre: '<?php echo addslashes($_SESSION['ultimaVentaClienteNombre'] ?? ''); ?>', // Nombre del cliente
            clienteDir: '<?php echo addslashes($_SESSION['ultimaVentaClienteDir'] ?? ''); ?>',       // Dirección del cliente
            clienteObs: '<?php echo addslashes($_SESSION['ultimaVentaClienteObs'] ?? ''); ?>',       // Observaciones
            descuentoTipo: '<?php echo $_SESSION['ultimaVentaDescuentoTipo'] ?? 'ninguno'; ?>',      // Tipo de descuento
            descuentoValor: <?php echo $_SESSION['ultimaVentaDescuentoValor'] ?? 0; ?>,              // Valor del descuento
            descuentoCupon: '<?php echo $_SESSION['ultimaVentaDescuentoCupon'] ?? ''; ?>'            // Código de cupón usado
        };
    </script>

    <?php
    // Limpiar todas las variables de sesión de la última venta para evitar que se muestren de nuevo
    unset($_SESSION['ventaExito']);
    unset($_SESSION['ultimaVentaId']);
    unset($_SESSION['ultimaVentaTotal']);
    unset($_SESSION['ultimaVentaTipo']);
    unset($_SESSION['ultimaVentaCarrito']);
    unset($_SESSION['ultimaVentaMetodoPago']);
    unset($_SESSION['ultimaVentaFecha']);
    unset($_SESSION['ultimaVentaEntregado']);
    unset($_SESSION['ultimaVentaCambio']);
    unset($_SESSION['ultimaVentaDescuentoTipo']);
    unset($_SESSION['ultimaVentaDescuentoValor']);
    unset($_SESSION['ultimaVentaDescuentoCupon']);
?>
<?php endif; ?>

<!-- ##=========================== MODAL: ABRIR CAJA ===========================## -->
<!-- Modal para iniciar una nueva sesión de caja introduciendo el fondo de caja inicial -->
<!-- Se envía por POST con la acción "abrirCaja" al controlador -->
<div class="modal-overlay" id="modalAbrirCaja" style="display:none;">
    <div class="modal-content modal-premium" style="max-width: 450px;">
        <!-- Cabecera con gradiente verde y icono de candado -->
        <div class="modal-header-premium" style="background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);">
            <div class="icon-container-discount">
                <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none"
                    stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
            </div>
            <h3>Apertura de Caja</h3>
            <p>Introduce el efectivo inicial para comenzar la jornada</p>
        </div>

        <div class="modal-body-premium">
            <!-- Formulario de apertura de caja -->
            <form id="formAbrirCaja" method="POST" action="index.php">
                <input type="hidden" name="accion" value="abrirCaja">
                <!-- Input para el importe inicial (fondo de caja) -->
                <div class="form-group-premium">
                    <label for="importeInicial">Fondo de caja inicial (€)</label>
                    <div class="input-cupon">
                        <input type="number" name="importeInicial" id="importeInicial" step="0.01" min="0"
                            placeholder="0,00" required style="text-align: center; padding-right: 15px;">
                    </div>
                </div>

                <!-- Botones: Cancelar y Confirmar Apertura -->
                <div style="display: flex; gap: 15px; margin-top: 30px;">
                    <button type="button" class="btn-modal-cancelar" onclick="cerrarModal('modalAbrirCaja')"
                        style="flex: 1;">Cancelar</button>
                    <button type="submit" class="btn-apply-premium"
                        style="flex: 1; background: #16a34a; color: white; border:none; border-radius:12px; cursor:pointer;">Confirmar
                        Apertura</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ##=========================== MODAL: DEVOLUCIÓN (REEMBOLSO) ===========================## -->
<!-- Modal para tramitar la devolución de un producto -->
<!-- Permite buscar un producto, seleccionar cantidad y método de devolución -->
<div class="modal-overlay" id="modalDevolucion" style="display:none;">
    <div class="modal-content modal-premium" style="max-width: 550px;">
        <!-- Cabecera con gradiente rojo y icono de devolución -->
        <div class="modal-header-premium" style="background: linear-gradient(135deg, #b91c1c 0%, #7f1d1d 100%);">
            <div class="icon-container-discount">
                <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none"
                    stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M1 4v6h6"></path>
                    <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
                </svg>
            </div>
            <h3>Tramitar Devolución</h3>
            <p>Selecciona un producto y la cantidad a devolver</p>
        </div>

        <div class="modal-body-premium">
            <!-- Formulario de devolución -->
            <form id="formDevolucion" method="POST" action="index.php">
                <input type="hidden" name="accion" value="tramitarDevolucion">

                <!-- Buscador de productos para devolución -->
                <!-- Usa AJAX (filtrarProductosDev) para buscar productos en la API -->
                <div class="form-group-premium">
                    <label for="buscarProductoDev">Buscar Producto</label>
                    <div style="position: relative;">
                        <input type="text" id="buscarProductoDev" placeholder="Escribe el nombre del producto..."
                            onkeyup="filtrarProductosDev(this.value)" autocomplete="off">
                        <!-- Contenedor de resultados de búsqueda (dropdown dinámico) -->
                        <div id="resultadosBusquedaDev" class="resultados-busqueda-dev" style="display: none;"></div>
                    </div>
                </div>

                <!-- Producto seleccionado y cantidad a devolver -->
                <div style="display: flex; gap: 15px; margin-top: 15px;">
                    <!-- Campo de producto seleccionado (solo lectura) -->
                    <div class="form-group-premium" style="flex: 2;">
                        <label>Producto Seleccionado</label>
                        <input type="text" id="nombreProductoDev" readonly placeholder="Ninguno seleccionado"
                            style="background: #f3f4f6; color: #4b5563;">
                        <input type="hidden" name="idProductoDev" id="idProductoDev" required>
                    </div>
                    <!-- Campo de cantidad a devolver -->
                    <div class="form-group-premium" style="flex: 1;">
                        <label for="cantidadDev">Cantidad</label>
                        <input type="number" name="cantidadDev" id="cantidadDev" min="1" value="1"
                            onchange="calcularTotalDev()" onkeyup="calcularTotalDev()">
                        <!-- Precio unitario oculto para calcular el total -->
                        <input type="hidden" id="precioUnitarioDev">
                    </div>
                </div>

                <!-- Selector de método de devolución (Efectivo, Tarjeta, Bizum) -->
                <!-- Usa radio buttons ocultos con chips visuales personalizados -->
                <div class="form-group-premium" style="margin-top: 15px;">
                    <label>Método de Devolución</label>
                    <div style="display: flex; gap: 10px; margin-top: 5px;">
                        <!-- Chip Efectivo (seleccionado por defecto) -->
                        <label style="flex: 1; cursor: pointer;">
                            <input type="radio" name="metodoPagoDev" value="Efectivo" checked style="display: none;"
                                onchange="updateMethodUI(this)">
                            <div class="method-chip active" id="chip-Efectivo"
                                style="padding: 10px; border: 2px solid #b91c1c; border-radius: 8px; text-align: center; color: #b91c1c; font-weight: 600;">
                                Efectivo</div>
                        </label>
                        <!-- Chip Tarjeta -->
                        <label style="flex: 1; cursor: pointer;">
                            <input type="radio" name="metodoPagoDev" value="Tarjeta" style="display: none;"
                                onchange="updateMethodUI(this)">
                            <div class="method-chip" id="chip-Tarjeta"
                                style="padding: 10px; border: 2px solid #e5e7eb; border-radius: 8px; text-align: center; color: #6b7280;">
                                Tarjeta</div>
                        </label>
                        <!-- Chip Bizum -->
                        <label style="flex: 1; cursor: pointer;">
                            <input type="radio" name="metodoPagoDev" value="Bizum" style="display: none;"
                                onchange="updateMethodUI(this)">
                            <div class="method-chip" id="chip-Bizum"
                                style="padding: 10px; border: 2px solid #e5e7eb; border-radius: 8px; text-align: center; color: #6b7280;">
                                Bizum</div>
                        </label>
                    </div>
                </div>

                <!-- Indicador del total a devolver (calculado dinámicamente) -->
                <div
                    style="margin-top: 25px; padding: 15px; background: #fee2e2; border-radius: 12px; display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-weight: 600; color: #991b1b;">TOTAL A DEVOLVER:</span>
                    <span id="totalDevDisplay" style="font-size: 1.5rem; font-weight: 800; color: #b91c1c;">0,00
                        €</span>
                    <input type="hidden" name="importeTotalDev" id="importeTotalDev">
                </div>

                <!-- Botones: Cancelar y Confirmar Devolución -->
                <!-- El botón de confirmar está deshabilitado hasta que se seleccione un producto -->
                <div style="display: flex; gap: 15px; margin-top: 25px;">
                    <button type="button" class="btn-modal-cancelar" onclick="cerrarModal('modalDevolucion')"
                        style="flex: 1;">Cancelar</button>
                    <button type="submit" class="btn-apply-premium" id="btnConfirmarDev" disabled
                        style="flex: 1; background: #b91c1c; color: white; border:none; border-radius:12px; cursor:pointer; opacity: 0.5;">Confirmar
                        Devolución</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ##=========================== MODAL: DEVOLUCIÓN ÉXITO ===========================## -->
<!-- Se muestra automáticamente cuando $_SESSION['devolucionExito'] está definida -->
<!-- Confirma que la devolución se ha procesado correctamente -->
<?php if (isset($_SESSION['devolucionExito'])): ?>
    <div class="modal-overlay" id="devolucionExito">
        <div class="modal-content modal-exito" style="max-width: 400px; border-top: 5px solid #b91c1c;">
            <!-- Icono de devolución en fondo rojo claro -->
            <div class="icon-container-discount" style="background: #fee2e2;">
                <svg xmlns="http://www.w3.org/2000/svg" width="35" height="35" viewBox="0 0 24 24" fill="none"
                    stroke="#b91c1c" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M1 4v6h6"></path>
                    <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
                </svg>
            </div>
            <h3 style="color: #991b1b; margin-top: 10px;">Devolución Realizada</h3>
            <p style="color: #6b7280; font-size: 0.9rem;">El importe ha sido restado de las ganancias del método
                seleccionado correctamente.</p>
            <button class="btn-cerrar-exito" style="background: #b91c1c; margin-top: 20px;"
                onclick="document.getElementById('devolucionExito').remove()">Aceptar</button>
        </div>
    </div>
    <?php unset($_SESSION['devolucionExito']); ?>
<?php endif; ?>

<!-- ##=========================== MODAL: RETIRAR DINERO ===========================## -->
<!-- Modal para retirar efectivo de la caja (ej: pago a proveedor, ingreso en banco) -->
<!-- Se envía por POST con la acción "retirarDinero" al controlador -->
<div class="modal-overlay" id="modalRetiro" style="display:none;">
    <div class="modal-content modal-premium" style="max-width: 450px;">
        <!-- Cabecera con gradiente naranja y icono de billete -->
        <div class="modal-header-premium" style="background: linear-gradient(135deg, #ea580c 0%, #9a3412 100%);">
            <div class="icon-container-discount">
                <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none"
                    stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect width="20" height="12" x="2" y="6" rx="2"></rect>
                    <circle cx="12" cy="12" r="2"></circle>
                    <path d="M6 12h.01M18 12h.01"></path>
                </svg>
            </div>
            <h3>Retirar Dinero</h3>
            <p>Ingresa la cantidad que deseas retirar de la caja</p>
        </div>

        <div class="modal-body-premium">
            <!-- Formulario de retiro de dinero -->
            <form id="formRetiro" method="POST" action="index.php">
                <input type="hidden" name="accion" value="retirarDinero">

                <!-- Campo: cantidad a retirar en euros -->
                <div class="form-group-premium">
                    <label for="importeRetiro">Cantidad a Retirar (€)</label>
                    <input type="number" name="importeRetiro" id="importeRetiro" step="0.01" min="0.01"
                        placeholder="0.00" required>
                </div>

                <!-- Campo: motivo del retiro (opcional) -->
                <div class="form-group-premium" style="margin-top: 15px;">
                    <label for="motivoRetiro">Motivo (Opcional)</label>
                    <input type="text" name="motivoRetiro" id="motivoRetiro"
                        placeholder="Ej: Pago a proveedor, ingreso banco...">
                </div>

                <!-- Botones: Cancelar y Confirmar Retiro -->
                <div style="display: flex; gap: 15px; margin-top: 25px;">
                    <button type="button" class="btn-modal-cancelar" onclick="cerrarModal('modalRetiro')"
                        style="flex: 1;">Cancelar</button>
                    <button type="submit" class="btn-apply-premium"
                        style="flex: 1; background: #ea580c; color: white; border:none; border-radius:12px; cursor:pointer;">Confirmar
                        Retiro</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ##=========================== MODAL: RETIRO ÉXITO ===========================## -->
<!-- Se muestra automáticamente cuando $_SESSION['retiroExito'] está definida -->
<!-- Confirma que el retiro de dinero se ha procesado correctamente -->
<?php if (isset($_SESSION['retiroExito'])): ?>
    <div class="modal-overlay" id="retiroExito">
        <div class="modal-content modal-exito" style="max-width: 400px; border-top: 5px solid #ea580c;">
            <!-- Icono de billete en fondo naranja claro -->
            <div class="icon-container-discount" style="background: #ffedd5;">
                <svg xmlns="http://www.w3.org/2000/svg" width="35" height="35" viewBox="0 0 24 24" fill="none"
                    stroke="#ea580c" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect width="20" height="12" x="2" y="6" rx="2"></rect>
                    <circle cx="12" cy="12" r="2"></circle>
                    <path d="M6 12h.01M18 12h.01"></path>
                </svg>
            </div>
            <h3 style="color: #9a3412; margin-top: 10px;">Retiro Realizado</h3>
            <p style="color: #6b7280; font-size: 0.9rem;">El importe ha sido restado del efectivo en caja correctamente.</p>
            <button class="btn-cerrar-exito" style="background: #ea580c; margin-top: 20px;"
                onclick="document.getElementById('retiroExito').remove()">Aceptar</button>
        </div>
    </div>
    <?php unset($_SESSION['retiroExito']); ?>
<?php endif; ?>

<!-- ##=========================== MODAL: ERROR ===========================## -->
<!-- Se muestra automáticamente cuando $_SESSION['ventaError'] está definida -->
<!-- Muestra el mensaje de error de la venta fallida -->
<?php if (isset($_SESSION['ventaError'])): ?>
    <div class="modal-overlay" id="ventaError">
        <div class="modal-content modal-error-content">
            <!-- Icono SVG de X/error en rojo -->
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#dc2626"
                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="15" y1="9" x2="9" y2="15"></line>
                <line x1="9" y1="9" x2="15" y2="15"></line>
            </svg>
            <h3>Error en la venta</h3>
            <!-- Mensaje de error escapado con htmlspecialchars para seguridad XSS -->
            <p><?php echo htmlspecialchars($_SESSION['ventaError']); ?></p>
            <button onclick="cerrarModal('ventaError')">Aceptar</button>
        </div>
    </div>
    <?php unset($_SESSION['ventaError']); ?>
<?php endif; ?>

<!-- ##=========================== MODAL: PREVISUALIZACIÓN DE CIERRE DE CAJA ===========================## -->
<!-- Se muestra cuando el cajero pulsa "Hacer Caja" y se genera la previsualización -->
<!-- Muestra el resumen de ventas del día desglosado por método de pago -->
<!-- Permite cancelar o confirmar el cierre definitivo de la caja -->
<?php if (isset($_SESSION['cajaPrevisualizacion']) && $_SESSION['cajaPrevisualizacion'] && isset($_SESSION['resumenCaja'])): ?>
    <div class="modal-overlay" id="cajaPrevisualizacion">
        <div class="modal-content modal-exito" style="max-width: 450px;">
            <!-- Icono de caja/billete en azul -->
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#2563eb"
                stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 15px;">
                <rect x="2" y="6" width="20" height="12" rx="2"></rect>
                <path d="M12 12h.01"></path>
                <path d="M17 12h.01"></path>
                <path d="M7 12h.01"></path>
            </svg>
            <h3 style="color: #1a1a2e; font-size: 1.4rem; margin-bottom: 20px;">Cierre de Caja</h3>

            <!-- Contenedor imprimible del resumen de caja -->
            <div id="cajaResumenImprimible"
                style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; text-align: left; margin-bottom: 20px;">

                <!-- Header visible solo al imprimir (clase .solo-impresion) -->
                <div class="solo-impresion" style="text-align: center; margin-bottom: 15px;">
                    <h2>TPV Bazar</h2>
                    <p>Cierre de Caja - <?php echo date('d/m/Y H:i'); ?></p>
                </div>

                <h4
                    style="margin: 0 0 15px 0; font-size: 1.1rem; border-bottom: 1px solid #e5e7eb; padding-bottom: 5px; color: #374151;">
                    Resumen de Ventas de Hoy</h4>

                <!-- Desglose por EFECTIVO: cantidad de tickets, total y devoluciones -->
                <div style="display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 0.95rem;">
                    <span><strong style="color: #4b5563;">Efectivo:</strong>
                        (<?php echo $_SESSION['resumenCaja']['efectivo']['cantidad']; ?> tickets)</span>
                    <div style="text-align: right;">
                        <span
                            style="font-weight: 600;"><?php echo number_format($_SESSION['resumenCaja']['efectivo']['total'], 2, ',', '.'); ?>
                            €</span>
                        <?php if ($_SESSION['resumenCaja']['efectivo']['devoluciones'] > 0): ?>
                            <br><span style="font-size: 0.75rem; color: #b91c1c;">(Dev:
                                -<?php echo number_format($_SESSION['resumenCaja']['efectivo']['devoluciones'], 2, ',', '.'); ?>
                                €)</span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Desglose por TARJETA: cantidad de tickets, total y devoluciones -->
                <div style="display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 0.95rem;">
                    <span><strong style="color: #4b5563;">Tarjeta:</strong>
                        (<?php echo $_SESSION['resumenCaja']['tarjeta']['cantidad']; ?> tickets)</span>
                    <div style="text-align: right;">
                        <span
                            style="font-weight: 600;"><?php echo number_format($_SESSION['resumenCaja']['tarjeta']['total'], 2, ',', '.'); ?>
                            €</span>
                        <?php if ($_SESSION['resumenCaja']['tarjeta']['devoluciones'] > 0): ?>
                            <br><span style="font-size: 0.75rem; color: #b91c1c;">(Dev:
                                -<?php echo number_format($_SESSION['resumenCaja']['tarjeta']['devoluciones'], 2, ',', '.'); ?>
                                €)</span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Desglose por BIZUM: cantidad de tickets, total y devoluciones -->
                <div style="display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 0.95rem;">
                    <span><strong style="color: #4b5563;">Bizum:</strong>
                        (<?php echo $_SESSION['resumenCaja']['bizum']['cantidad']; ?> tickets)</span>
                    <div style="text-align: right;">
                        <span
                            style="font-weight: 600;"><?php echo number_format($_SESSION['resumenCaja']['bizum']['total'], 2, ',', '.'); ?>
                            €</span>
                        <?php if ($_SESSION['resumenCaja']['bizum']['devoluciones'] > 0): ?>
                            <br><span style="font-size: 0.75rem; color: #b91c1c;">(Dev:
                                -<?php echo number_format($_SESSION['resumenCaja']['bizum']['devoluciones'], 2, ',', '.'); ?>
                                €)</span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- TOTAL GENERAL de ventas del día (suma de todos los métodos) -->
                <div
                    style="display: flex; justify-content: space-between; border-top: 2px solid #1a1a2e; padding-top: 15px; font-size: 1.2rem;">
                    <strong style="color: #1a1a2e;">TOTAL VENTAS:</strong>
                    <strong
                        style="color: #059669;"><?php echo number_format($_SESSION['resumenCaja']['totalGeneral'], 2, ',', '.'); ?>
                        €</strong>
                </div>

                <!-- Detalles reales de la caja: fondo inicial, devoluciones y efectivo real -->
                <div style="margin-top: 20px; border-top: 1px dashed #d1d5db; padding-top: 15px;">
                    <!-- Fondo de caja inicial (importe con el que se abrió la caja) -->
                    <div
                        style="display: flex; justify-content: space-between; margin-bottom: 5px; font-size: 0.9rem; color: #4b5563;">
                        <span>Fondo inicial:</span>
                        <span><?php echo number_format($_SESSION['resumenCaja']['importeInicial'], 2, ',', '.'); ?> €</span>
                    </div>
                    <!-- Total de devoluciones realizadas durante la sesión -->
                    <div
                        style="display: flex; justify-content: space-between; margin-bottom: 5px; font-size: 0.9rem; color: #b91c1c;">
                        <span>Total Devoluciones:</span>
                        <span style="font-weight: 600;">-
                            <?php echo number_format($_SESSION['resumenCaja']['totalDevoluciones'], 2, ',', '.'); ?>
                            €</span>
                    </div>
                    <!-- Efectivo real que debería haber en la caja física -->
                    <div
                        style="display: flex; justify-content: space-between; font-size: 1.1rem; font-weight: bold; color: #1a1a2e;">
                        <span>EFECTIVO REAL EN CAJA:</span>
                        <span><?php echo number_format($_SESSION['resumenCaja']['importeActual'], 2, ',', '.'); ?> €</span>
                    </div>
                </div>

                <!-- Footer visible solo al imprimir: espacio para firma y sello -->
                <div class="solo-impresion"
                    style="text-align: center; margin-top: 20px; border-top: 1px dashed #ccc; padding-top: 10px; font-size: 0.9rem; color: #666;">
                    <p>Firma y sello:</p>
                    <br><br><br>
                </div>
            </div>

            <!-- Botones: Cancelar (cierra sin cerrar caja) y Confirmar Cierre (cierra la caja definitivamente) -->
            <div style="display: flex; gap: 10px; justify-content: center; margin-top: 20px;">
                <button class="btn-modal-cancelar"
                    onclick="document.getElementById('cajaPrevisualizacion').style.display='none';">Cancelar</button>
                <form method="POST" action="index.php" style="margin: 0;">
                    <input type="hidden" name="accion" value="confirmarCaja">
                    <button type="submit" class="btn-cerrar-exito" style="margin-top: 0; background: #2563eb;">Confirmar
                        Cierre</button>
                </form>
            </div>
        </div>
    </div>
    <?php
    // Limpiamos la flag de previsualización pero NO el resumenCaja,
    // ya que se necesita si el usuario confirma el cierre e imprime
    unset($_SESSION['cajaPrevisualizacion']);
?>
<?php endif; ?>

<!-- ##=========================== MODAL: CONFIRMACIÓN DE CIERRE DE CAJA ===========================## -->
<!-- Se muestra después de confirmar el cierre de caja -->
<!-- Permite imprimir el resumen final antes de cerrar -->
<?php if (isset($_SESSION['cajaConfirmacion']) && $_SESSION['cajaConfirmacion'] && isset($_SESSION['resumenCaja'])): ?>
    <div class="modal-overlay" id="cajaConfirmacion">
        <div class="modal-content modal-exito" style="max-width: 450px;">
            <!-- Icono de check/éxito en verde -->
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#059669"
                stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icono-exito">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                <polyline points="22 4 12 14.01 9 11.01"></polyline>
            </svg>
            <h3 style="color: #1a1a2e; font-size: 1.4rem; margin-bottom: 20px;">Caja Cerrada Correctamente</h3>
            <p style="color: #6b7280; font-size: 0.95rem; margin-bottom: 20px;">El recuento de ventas ha vuelto a 0 para el
                contador del día de mañana.</p>

            <!-- Bloque oculto con el HTML del resumen para imprimir -->
            <!-- Se inyecta en una ventana nueva al pulsar "Imprimir Resumen" -->
            <div id="cajaOcultaImprimible" style="display: none;">
                <!-- Cabecera del documento impreso -->
                <div style="text-align: center; margin-bottom: 15px; font-family: 'Inter', sans-serif;">
                    <h2 style="margin:0;">TPV Bazar</h2>
                    <p style="margin:5px 0 15px 0;">Cierre de Caja - <?php echo date('d/m/Y H:i'); ?></p>
                </div>

                <!-- Desglose por método de pago para impresión -->
                <div
                    style="border-top: 1px solid #000; padding-top: 10px; padding-bottom: 5px; font-family: 'Inter', sans-serif;">
                    <!-- Efectivo -->
                    <p style="margin: 5px 0; display:flex; justify-content:space-between;">
                        <span>Efectivo (<?php echo $_SESSION['resumenCaja']['efectivo']['cantidad']; ?>):</span>
                        <span><?php echo number_format($_SESSION['resumenCaja']['efectivo']['total'], 2, ',', '.'); ?>
                            €</span>
                    </p>
                    <?php if ($_SESSION['resumenCaja']['efectivo']['devoluciones'] > 0): ?>
                        <p style="margin: 0px 0 5px 0; display:flex; justify-content:flex-end; font-size: 0.8rem;">
                            <span>(Devolución:
                                -<?php echo number_format($_SESSION['resumenCaja']['efectivo']['devoluciones'], 2, ',', '.'); ?>
                                €)</span>
                        </p>
                    <?php endif; ?>

                    <!-- Tarjeta -->
                    <p style="margin: 5px 0; display:flex; justify-content:space-between;">
                        <span>Tarjeta (<?php echo $_SESSION['resumenCaja']['tarjeta']['cantidad']; ?>):</span>
                        <span><?php echo number_format($_SESSION['resumenCaja']['tarjeta']['total'], 2, ',', '.'); ?>
                            €</span>
                    </p>
                    <?php if ($_SESSION['resumenCaja']['tarjeta']['devoluciones'] > 0): ?>
                        <p style="margin: 0px 0 5px 0; display:flex; justify-content:flex-end; font-size: 0.8rem;">
                            <span>(Devolución:
                                -<?php echo number_format($_SESSION['resumenCaja']['tarjeta']['devoluciones'], 2, ',', '.'); ?>
                                €)</span>
                        </p>
                    <?php endif; ?>

                    <!-- Bizum -->
                    <p style="margin: 5px 0; display:flex; justify-content:space-between;">
                        <span>Bizum (<?php echo $_SESSION['resumenCaja']['bizum']['cantidad']; ?>):</span>
                        <span><?php echo number_format($_SESSION['resumenCaja']['bizum']['total'], 2, ',', '.'); ?> €</span>
                    </p>
                    <?php if ($_SESSION['resumenCaja']['bizum']['devoluciones'] > 0): ?>
                        <p style="margin: 0px 0 5px 0; display:flex; justify-content:flex-end; font-size: 0.8rem;">
                            <span>(Devolución:
                                -<?php echo number_format($_SESSION['resumenCaja']['bizum']['devoluciones'], 2, ',', '.'); ?>
                                €)</span>
                        </p>
                    <?php endif; ?>
                </div>

                <!-- Total general de ventas para impresión -->
                <div
                    style="border-top: 1px solid #000; padding-top: 10px; margin-top: 10px; font-weight: bold; display:flex; justify-content:space-between; font-family: 'Inter', sans-serif;">
                    <span>TOTAL VENTAS:</span>
                    <span><?php echo number_format($_SESSION['resumenCaja']['totalGeneral'], 2, ',', '.'); ?> €</span>
                </div>

                <!-- Detalles de caja para impresión: fondo, devoluciones y efectivo real -->
                <div
                    style="border-top: 1px dashed #000; margin-top: 15px; padding-top: 10px; font-family: 'Inter', sans-serif;">
                    <p style="margin: 5px 0; display:flex; justify-content:space-between; font-size: 0.9em;">
                        <span>Fondo inicial:</span>
                        <span><?php echo number_format($_SESSION['resumenCaja']['importeInicial'], 2, ',', '.'); ?> €</span>
                    </p>
                    <p style="margin: 5px 0; display:flex; justify-content:space-between; font-size: 0.9em; color: #000;">
                        <span>Total Devoluciones:</span>
                        <span>- <?php echo number_format($_SESSION['resumenCaja']['totalDevoluciones'], 2, ',', '.'); ?>
                            €</span>
                    </p>
                    <p
                        style="margin: 5px 0; display:flex; justify-content:space-between; font-weight: bold; font-size: 1.1em;">
                        <span>EFECTIVO REAL CAJA:</span>
                        <span><?php echo number_format($_SESSION['resumenCaja']['importeActual'], 2, ',', '.'); ?> €</span>
                    </p>
                </div>

                <!-- Espacio para firma/sello en el documento impreso -->
                <div style="text-align: center; margin-top: 30px; font-size: 0.8rem; font-family: 'Inter', sans-serif;">
                    <p>Firma / Sello</p>
                </div>
            </div>

            <!-- Botones: Imprimir Resumen y Aceptar (cierra el modal) -->
            <div style="display: flex; gap: 10px; justify-content: center; margin-top: 20px;">
                <button class="btn-cerrar-exito" onclick="imprimirCierreCaja()">Imprimir Resumen</button>
                <button class="btn-modal-cancelar"
                    onclick="document.getElementById('cajaConfirmacion').style.display='none';">Aceptar</button>
            </div>
        </div>
    </div>

    <script>
        /**
         * imprimirCierreCaja()
         * Abre una ventana emergente con el contenido del resumen de cierre de caja
         * formateado para impresión. Tras imprimir, cierra la ventana y oculta el modal.
         */
        function imprimirCierreCaja() {
            // Obtener el HTML del bloque oculto de impresión
            const contenido = document.getElementById('cajaOcultaImprimible').innerHTML;

            // Abrir ventana emergente para impresión
            const ventana = window.open('', '', 'width=400,height=600');
            ventana.document.write(`
                <html>
                <head>
                    <title>Cierre de Caja</title>
                    <style>
                        body { font-family: 'Inter', sans-serif; font-size: 12px; padding: 20px; color: #000; }
                    </style>
                </head>
                <body>${contenido}</body>
                </html>
            `);
        ventana.document.close();
        ventana.focus();

        // Esperar 500ms para que se renderice el contenido y luego imprimir
        setTimeout(() => {
            ventana.print();
            ventana.close();
            document.getElementById('cajaConfirmacion').style.display = 'none';
        }, 500);
    }
</script>
<?php
        // Limpiar las variables de sesión del cierre de caja
        unset($_SESSION['cajaConfirmacion']);
        unset($_SESSION['resumenCaja']);
?>
<?php endif; ?>

<!-- Carga del script externo del cajero (funciones de búsqueda y filtrado de productos) -->
<script src="webroot/js/cajero.js"></script>

<!-- ##=========================== SCRIPT PRINCIPAL DEL CAJERO ===========================## -->
<!-- Contiene toda la lógica JavaScript del carrito, descuentos, cobro, impresión,
     envío por correo, devoluciones y utilidades -->
<script>
    // ======================== CARRITO (persiste en memoria durante la sesión del navegador) ========================

    /**
     * Array que almacena los productos del carrito.
     * Cada elemento tiene: { idProducto, nombre, precio, cantidad, stockMax }
     */
    let carrito = [];

    /**
     * Objeto que almacena el descuento aplicado actualmente.
     * tipo: 'ninguno' | 'porcentaje' | 'fijo'
     * valor: número (porcentaje o importe fijo)
     * cupon: código del cupón usado (si aplica)
     */
    let descuento = { tipo: 'ninguno', valor: 0, cupon: '' };

    /**
     * Flag que indica si la caja está abierta (true) o cerrada (false).
     * Se inicializa desde PHP según el estado de $sesionCaja.
     */
    let cajaAbierta = <?php echo $sesionCaja ? 'true' : 'false'; ?>;

    /**
     * agregarAlCarrito(elemento)
     * Añade un producto al carrito o incrementa su cantidad si ya existe.
     * Lee los datos del producto desde los atributos data-* del elemento HTML.
     * Valida que no se exceda el stock máximo disponible.
     * @param {HTMLElement} elemento - La tarjeta de producto clickeada
     */
    function agregarAlCarrito(elemento) {
        const id = parseInt(elemento.dataset.id);
        const nombre = elemento.dataset.nombre;
        const precio = parseFloat(elemento.dataset.precio);
        const stockMax = parseInt(elemento.dataset.stock);

        // Buscar si el producto ya está en el carrito
        const existente = carrito.find(item => item.idProducto === id);
        if (existente) {
            // Si ya existe, verificar stock antes de incrementar
            if (existente.cantidad >= stockMax) {
                alert('No hay más stock disponible para este producto.');
                return;
            }
            existente.cantidad++;
        } else {
            // Si no existe, verificar que tenga stock y añadirlo
            if (stockMax <= 0) {
                alert('Este producto no tiene stock disponible.');
                return;
            }
            carrito.push({ idProducto: id, nombre: nombre, precio: precio, cantidad: 1, stockMax: stockMax });
        }

        // Actualizar la visualización del ticket
        actualizarTicket();
    }

    /**
     * eliminarDelCarrito(index)
     * Elimina un producto del carrito por su índice.
     * @param {number} index - Índice del producto en el array carrito
     */
    function eliminarDelCarrito(index) {
        carrito.splice(index, 1);
        actualizarTicket();
    }

    /**
     * cambiarCantidad(indice, nuevaCantidad)
     * Modifica la cantidad de un producto en el carrito.
     * Valida que la cantidad esté entre 1 y el stock máximo.
     * @param {number} indice - Índice del producto en el array carrito
     * @param {number} nuevaCantidad - Nueva cantidad deseada
     */
    function cambiarCantidad(indice, nuevaCantidad) {
        const item = carrito[indice];
        nuevaCantidad = parseInt(nuevaCantidad) || 1;

        // Limitar entre 1 y el stock máximo
        if (nuevaCantidad < 1) nuevaCantidad = 1;
        if (nuevaCantidad > item.stockMax) nuevaCantidad = item.stockMax;

        item.cantidad = nuevaCantidad;
        actualizarTicket();
    }

    /**
     * vaciarCarrito()
     * Elimina todos los productos del carrito y resetea el descuento.
     */
    function vaciarCarrito() {
        carrito = [];
        descuento = { tipo: 'ninguno', valor: 0, cupon: '' };
        actualizarTicket();
    }

    /**
     * obtenerTotalCalculado()
     * Calcula el total del carrito aplicando el descuento vigente.
     * @returns {number} Total final (mínimo 0)
     */
    function obtenerTotalCalculado() {
        // Calcular subtotal sumando precio * cantidad de cada producto
        let subtotal = carrito.reduce((sum, item) => sum + (item.precio * item.cantidad), 0);
        let importeDescuento = 0;

        // Aplicar descuento según el tipo
        if (descuento.tipo === 'porcentaje') {
            importeDescuento = subtotal * (descuento.valor / 100);
        } else if (descuento.tipo === 'fijo') {
            importeDescuento = descuento.valor;
        }

        // Retornar total (nunca negativo)
        return Math.max(0, subtotal - importeDescuento);
    }

    /**
     * actualizarTicket()
     * Regenera completamente el HTML del ticket/carrito en el panel derecho.
     * Incluye: tabla de productos, controles de cantidad, desglose de descuento,
     * total y estado de los botones Cobrar/Descuento.
     */
    function actualizarTicket() {
        const contenedor = document.getElementById('ticketLineas');
        const totalEl = document.getElementById('ticketTotal');
        const btnCobrar = document.getElementById('btnCobrar');
        const btnDescuento = document.getElementById('btnDescuento');

        // Si el carrito está vacío, mostrar mensaje y deshabilitar botones
        if (carrito.length === 0) {
            contenedor.innerHTML = '<p class="ticket-vacio">Añade productos al ticket</p>';
            document.getElementById('ticketDesglose').innerHTML = '';
            totalEl.textContent = '0,00 €';
            btnCobrar.disabled = true;
            btnDescuento.disabled = true;
            return;
        }

        // Generar tabla HTML con las líneas del ticket
        let html = '<table class="ticket-tabla"><thead><tr><th>Producto</th><th>Cant.</th><th>Precio</th><th>Subt.</th><th></th></tr></thead><tbody>';
        let total = 0;

        // Iterar sobre cada producto del carrito
        carrito.forEach((item, i) => {
            const subtotal = item.precio * item.cantidad;
            total += subtotal;

            // Generar fila con: nombre, controles de cantidad (−/input/+), precio, subtotal y botón eliminar
            html += `<tr>
                <td>${item.nombre}</td>
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
                <td>${item.precio.toFixed(2).replace('.', ',')} €</td>
                <td>${subtotal.toFixed(2).replace('.', ',')} €</td>
                <td><button class="btn-quitar" onclick="eliminarDelCarrito(${i})">✕</button></td>
            </tr>`;
        });

        html += '</tbody></table>';
        contenedor.innerHTML = html;

        // Calcular desglose de totales con descuento
        let subtotal = total;
        total = obtenerTotalCalculado();
        let importeDescuento = subtotal - total;

        // Generar HTML del desglose (solo si hay descuento aplicado)
        let htmlDesglose = '';
        if (importeDescuento > 0) {
            htmlDesglose = `
                <div class="resumen-final-premium">
                    <div class="resumen-fila-mini">
                        <span>Subtotal:</span>
                        <span>${subtotal.toFixed(2).replace('.', ',')} €</span>
                    </div>
                    <div class="resumen-fila-mini descuento-texto">
                        <span>Descuento (${descuento.tipo === 'porcentaje' ? descuento.valor + '%' : 'Cupón ' + descuento.cupon}):</span>
                        <span>- ${importeDescuento.toFixed(2).replace('.', ',')} €</span>
                    </div>
                </div>`;
        }

        // Actualizar el DOM con el desglose y el total
        document.getElementById('ticketDesglose').innerHTML = htmlDesglose;
        totalEl.textContent = total.toFixed(2).replace('.', ',') + ' €';

        // Verificar si se supera el límite de 1.000€ en efectivo
        verificarLimiteEfectivo();

        // Habilitar botones de cobro y descuento
        btnCobrar.disabled = false;
        btnDescuento.disabled = false;
    }

    // ======================== DESCUENTOS ========================

    /**
     * aplicarDescuento()
     * Abre el modal de descuento si hay productos en el carrito.
     */
    function aplicarDescuento() {
        if (carrito.length === 0) return;
        document.getElementById('modalDescuento').style.display = 'flex';
        document.getElementById('inputPorcentajeDescuento').focus();
    }

    /**
     * procesarDescuento()
     * Procesa el descuento introducido (porcentaje o cupón) y lo aplica al carrito.
     * Cupones válidos: PROMO10 (10%), BIENVENIDA5 (5%), FIJO5 (5€ fijo).
     * Si no se introduce nada, se elimina el descuento actual.
     */
    function procesarDescuento() {
        let porcentaje = parseFloat(document.getElementById('inputPorcentajeDescuento').value);
        const cupon = document.getElementById('inputCuponDescuento').value.trim().toUpperCase();

        if (!isNaN(porcentaje) && document.getElementById('inputPorcentajeDescuento').value !== '') {
            // Opción 1: Descuento por porcentaje manual
            if (porcentaje < 0 || porcentaje > 100) {
                alert('El porcentaje de descuento debe estar entre 0 y 100');
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
                alert('Cupón no válido');
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

    // ======================== PROCESO DE COBRO Y CAMBIO ========================

    /**
     * intentarCobrar()
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
                alert('No se permite el pago en efectivo para importes superiores a 1.000€. Por favor, selecciona otro método de pago.');
                return;
            }
            // Mostrar modal para calcular el cambio
            mostrarModalCambio();
        } else {
            // Para tarjeta/bizum, ir directamente al tipo de documento
            mostrarModalTipoDocumento();
        }
    }

    /**
     * mostrarModalCambio()
     * Muestra el modal de cálculo de cambio para pago en efectivo.
     * Inicializa los valores y pone el foco en el input de dinero entregado.
     */
    function mostrarModalCambio() {
        const total = obtenerTotalCalculado();
        document.getElementById('cambioTotalPagar').textContent = total.toFixed(2).replace('.', ',') + ' €';

        // Resetear campos del modal
        const inputEntregado = document.getElementById('inputDineroEntregado');
        inputEntregado.value = '';
        document.getElementById('cambioDevolver').textContent = '0,00 €';
        document.getElementById('cambioDevolver').style.color = '#22c55e';
        document.getElementById('cambioError').style.display = 'none';

        // Mostrar modal y enfocar el input
        document.getElementById('modalCambio').style.display = 'flex';
        setTimeout(() => inputEntregado.focus(), 100);
    }

    /**
     * calcularCambio()
     * Calcula en tiempo real el cambio a devolver según la cantidad entregada.
     * Muestra un mensaje de error si la cantidad es insuficiente.
     */
    function calcularCambio() {
        const total = obtenerTotalCalculado();
        const entregado = parseFloat(document.getElementById('inputDineroEntregado').value) || 0;
        const devolucion = entregado - total;

        const spanDevolver = document.getElementById('cambioDevolver');
        const errorMsg = document.getElementById('cambioError');

        if (devolucion < 0 && entregado > 0) {
            // Cantidad insuficiente: mostrar error
            spanDevolver.textContent = '0,00 €';
            spanDevolver.style.color = '#333';
            errorMsg.style.display = 'block';
        } else {
            // Cantidad suficiente o vacía: mostrar cambio
            errorMsg.style.display = 'none';
            if (entregado === 0) {
                spanDevolver.textContent = '0,00 €';
                spanDevolver.style.color = '#333';
            } else {
                spanDevolver.textContent = devolucion.toFixed(2).replace('.', ',') + ' €';
                spanDevolver.style.color = '#22c55e';
            }
        }
    }

    /**
     * confirmarCambio()
     * Valida que la cantidad entregada sea suficiente y avanza al modal de tipo de documento.
     */
    function confirmarCambio() {
        const total = obtenerTotalCalculado();
        const entregado = parseFloat(document.getElementById('inputDineroEntregado').value) || 0;

        // Validar que el entregado cubra el total
        if (entregado < total) {
            document.getElementById('cambioError').style.display = 'block';
            return;
        }

        // Cerrar modal de cambio y abrir modal de tipo de documento
        cerrarModal('modalCambio');
        mostrarModalTipoDocumento();
    }

    // ======================== MODAL TIPO DOCUMENTO / CLIENTE ========================

    /**
     * mostrarModalTipoDocumento()
     * Muestra el modal para elegir entre Ticket o Factura.
     * Valida que haya productos en el carrito y que la caja esté abierta.
     */
    function mostrarModalTipoDocumento() {
        if (carrito.length === 0) return;

        // Verificar que la caja esté abierta antes de permitir ventas
        if (!cajaAbierta) {
            alert('No se pueden realizar ventas si la caja no está abierta. Por favor, realiza la Apertura de Caja.');
            return;
        }

        document.getElementById('modalTipoDoc').style.display = 'flex';
    }

    /** Variable que almacena el tipo de documento seleccionado ('ticket' o 'factura') */
    let tipoDocumentoActual = 'ticket';

    /**
     * seleccionarDatosCliente(tipo)
     * Configura y muestra el modal de datos del cliente según el tipo de documento.
     * En modo Factura: muestra campos adicionales (dirección, observaciones) y marca obligatorios.
     * En modo Ticket: los campos son opcionales y se ocultan los extras.
     * @param {string} tipo - 'ticket' o 'factura'
     */
    function seleccionarDatosCliente(tipo) {
        tipoDocumentoActual = tipo;
        cerrarModal('modalTipoDoc');

        // Limpiar errores previos
        document.getElementById('errorDatosCliente').style.display = 'none';

        // Obtener referencias a los elementos del formulario
        const divDir = document.getElementById('divDireccionCliente');
        const divObs = document.getElementById('divObservacionesCliente');
        const subTitulo = document.getElementById('subtituloDatosCliente');

        const reqNif = document.getElementById('reqNif');
        const reqNombre = document.getElementById('reqNombre');
        const reqDir = document.getElementById('reqDir');

        if (tipo === 'factura') {
            // Modo Factura: mostrar todos los campos y marcar obligatorios con *
            subTitulo.textContent = 'Complete los datos (Obligatorios para Factura)';
            divDir.style.display = 'block';
            divObs.style.display = 'block';
            reqNif.style.display = 'inline';
            reqNombre.style.display = 'inline';
            reqDir.style.display = 'inline';
        } else {
            // Modo Ticket: ocultar campos extra y quitar indicadores de obligatorio
            subTitulo.textContent = 'Complete los datos (Opcional en Ticket)';
            divDir.style.display = 'none';
            divObs.style.display = 'none';
            reqNif.style.display = 'none';
            reqNombre.style.display = 'none';
            reqDir.style.display = 'none';
        }

        // Mostrar el modal de datos del cliente
        document.getElementById('modalDatosCliente').style.display = 'flex';
    }

    /**
     * validarYConfirmarVenta()
     * Valida los datos del cliente (obligatorios en Factura) y confirma la venta.
     * Si la validación falla, muestra un mensaje de error.
     */
    function validarYConfirmarVenta() {
        const nif = document.getElementById('clienteNif').value.trim();
        const nombre = document.getElementById('clienteNombre').value.trim();
        const direccion = document.getElementById('clienteDireccion').value.trim();
        const observaciones = document.getElementById('clienteObservaciones').value.trim();

        // En modo Factura, NIF, Nombre y Dirección son obligatorios
        if (tipoDocumentoActual === 'factura') {
            if (!nif || !nombre || !direccion) {
                document.getElementById('errorDatosCliente').style.display = 'block';
                return;
            }
        }

        // Cerrar modal y proceder con la venta
        cerrarModal('modalDatosCliente');
        confirmarVenta(tipoDocumentoActual, nif, nombre, direccion, observaciones);
    }

    /**
     * confirmarVenta(tipoDocumento, nif, nombre, direccion, observaciones)
     * Rellena el formulario oculto con todos los datos de la venta y lo envía por POST.
     * Incluye: carrito, método de pago, tipo de documento, dinero entregado/cambio,
     * datos del cliente y descuento aplicado.
     * @param {string} tipoDocumento - 'ticket' o 'factura'
     * @param {string} nif - NIF/CIF del cliente
     * @param {string} nombre - Nombre/Razón social del cliente
     * @param {string} direccion - Domicilio fiscal del cliente
     * @param {string} observaciones - Observaciones adicionales
     */
    function confirmarVenta(tipoDocumento, nif, nombre, direccion, observaciones) {
        const total = obtenerTotalCalculado();
        const metodoPago = document.getElementById('metodoPago').value;
        let entregado = total;
        let cambio = 0;

        // Si el pago es en efectivo, calcular entregado y cambio
        if (metodoPago === 'efectivo') {
            const inputVal = parseFloat(document.getElementById('inputDineroEntregado').value);
            if (!isNaN(inputVal) && inputVal >= total) {
                entregado = inputVal;
                cambio = inputVal - total;
            }
        }

        // Rellenar los campos ocultos del formulario
        document.getElementById('inputCarrito').value = JSON.stringify(carrito);
        document.getElementById('inputMetodoPago').value = metodoPago;
        document.getElementById('inputTipoDocumento').value = tipoDocumento;
        document.getElementById('inputDineroEntregadoFinal').value = entregado.toFixed(2);
        document.getElementById('inputCambioDevueltoFinal').value = cambio.toFixed(2);

        // Datos del cliente
        document.getElementById('inputClienteNifFinal').value = nif;
        document.getElementById('inputClienteNombreFinal').value = nombre;
        document.getElementById('inputClienteDireccionFinal').value = direccion;
        document.getElementById('inputObservacionesFinal').value = observaciones;

        // Datos del descuento
        document.getElementById('inputDescuentoTipo').value = descuento.tipo;
        document.getElementById('inputDescuentoValor').value = descuento.valor;
        document.getElementById('inputDescuentoCupon').value = descuento.cupon;

        // Enviar el formulario al servidor
        document.getElementById('formVenta').submit();
    }

    /**
     * cerrarExito()
     * Cierra y elimina del DOM el modal de venta exitosa.
     */
    function cerrarExito() {
        document.getElementById('ventaExito').remove();
    }

    // ======================== IMPRIMIR ========================

    /**
     * imprimirDocumento()
     * Genera un documento HTML formateado (ticket o factura) con los datos de la última venta
     * y lo envía a la impresora mediante un iframe oculto.
     * Incluye: datos del emisor, datos del cliente, líneas de venta, desglose de IVA,
     * descuentos, totales, datos de pago y observaciones.
     */
    function imprimirDocumento() {
        if (typeof ultimaVenta === 'undefined') return;

        // Determinar si es factura o ticket para el título
        const isFactura = (ultimaVenta.tipo === 'factura');
        const tipoTitulo = isFactura ? 'FACTURA' : 'TICKET DE VENTA (FACTURA SIMPLIFICADA)';

        // Datos del emisor (empresa) - fijos
        const emisorHtml = `
            <strong>TPV Bazar — Productos Informáticos</strong><br>
            NIF: B12345678<br>
            C/ Falsa 123, 28000 Madrid<br>
        `;

        // Datos del receptor (cliente) - solo si hay datos disponibles
        let receptorHtml = '';
        if (isFactura || ultimaVenta.clienteNif || ultimaVenta.clienteNombre) {
            receptorHtml = `
                <div class="datos-cliente" style="margin-top: 15px; padding-top: 10px; border-top: 1px dashed #ccc;">
                    <strong>Datos del Cliente:</strong><br>
                    ${ultimaVenta.clienteNombre ? ultimaVenta.clienteNombre + '<br>' : ''}
                    ${ultimaVenta.clienteNif ? 'NIF/CIF: ' + ultimaVenta.clienteNif + '<br>' : ''}
                    ${ultimaVenta.clienteDir ? ultimaVenta.clienteDir + '<br>' : ''}
                </div>
            `;
        }

        // Generar las líneas de productos para la tabla
        let lineasHtml = '';
        let sumaTotalesNumeric = 0;

        ultimaVenta.carrito.forEach(item => {
            const subtotalNumeric = item.precio * item.cantidad;
            sumaTotalesNumeric += subtotalNumeric;

            // Formatear precios con coma decimal (formato español)
            const subtotal = subtotalNumeric.toFixed(2).replace('.', ',');
            const precioFmt = parseFloat(item.precio).toFixed(2).replace('.', ',');

            lineasHtml += `<tr>
                <td>${item.nombre}</td>
                <td style="text-align:center">${item.cantidad}</td>
                <td style="text-align:right">${precioFmt} €</td>
                <td style="text-align:center">21%</td>
                <td style="text-align:right">${subtotal} €</td>
            </tr>`;
        });

        // Calcular descuento aplicado
        let importeDescuento = 0;
        if (ultimaVenta.descuentoTipo === 'porcentaje') {
            importeDescuento = sumaTotalesNumeric * (ultimaVenta.descuentoValor / 100);
        } else if (ultimaVenta.descuentoTipo === 'fijo') {
            importeDescuento = ultimaVenta.descuentoValor;
        }

        // Calcular base imponible y cuota de IVA (21%)
        const subtotalSinDescuento = sumaTotalesNumeric;
        const totalVenta = Math.max(0, subtotalSinDescuento - importeDescuento);
        const baseImponible = totalVenta / 1.21;    // Base imponible (sin IVA)
        const cuotaIva = totalVenta - baseImponible; // Cuota de IVA

        // Generar tabla de totales con desglose fiscal
        let totalesHtml = `
            <table class="tabla-totales" style="width:100%; font-size: 0.9rem; margin-top:10px;">
        `;

        // Si hay descuento, mostrar subtotal y línea de descuento
        if (importeDescuento > 0) {
            totalesHtml += `
                <tr>
                    <td><strong>Subtotal:</strong></td>
                    <td style="text-align:right">${subtotalSinDescuento.toFixed(2).replace('.', ',')} €</td>
                </tr>
                <tr>
                    <td style="color: #16a34a;"><strong>Descuento (${ultimaVenta.descuentoTipo === 'porcentaje' ? ultimaVenta.descuentoValor + '%' : 'Cupón ' + ultimaVenta.descuentoCupon}):</strong></td>
                    <td style="text-align:right; color: #16a34a;">- ${importeDescuento.toFixed(2).replace('.', ',')} €</td>
                </tr>
            `;
        }

        // Base imponible, cuota IVA y total con IVA incluido
        totalesHtml += `
            <tr>
                <td><strong>Base Imponible:</strong></td>
                <td style="text-align:right">${baseImponible.toFixed(2).replace('.', ',')} €</td>
            </tr>
            <tr>
                <td><strong>Cuota IVA (21%):</strong></td>
                <td style="text-align:right">${cuotaIva.toFixed(2).replace('.', ',')} €</td>
            </tr>
            <tr>
                <td style="font-size: 1.1rem; padding-top:10px;"><strong>TOTAL (IVA INCLUIDO):</strong></td>
                <td style="font-size: 1.1rem; font-weight: bold; text-align:right; padding-top:10px;">${ultimaVenta.total} €</td>
            </tr>
        </table>
        `;

        // Observaciones del cliente (si las hay)
        let obsHtml = '';
        if (ultimaVenta.clienteObs) {
            obsHtml = `<div style="margin-top: 15px; font-size: 0.8rem;"><strong>Observaciones:</strong> ${ultimaVenta.clienteObs}</div>`;
        }

        // Componer el documento HTML completo para impresión
        const contenido = `
        <html>
        <head>
            <title>${tipoTitulo} #${ultimaVenta.id}</title>
            <style>
                body { font-family: 'Inter', 'Helvetica Neue', Helvetica, Arial, sans-serif; padding: 20px; color: #1a1a1a; max-width: 80mm; margin: 0 auto; line-height: 1.4; }
                .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 15px; }
                .header h1 { margin: 0; font-size: 1.2rem; text-transform: uppercase; }
                .header h2 { margin: 5px 0 0; font-size: 0.9rem; font-weight: normal;}
                .datos { margin-bottom: 15px; font-size: 0.85rem; }
                .datos p { margin: 3px 0; }
                table.tabla-lineas { width: 100%; border-collapse: collapse; margin: 10px 0; font-size: 0.8rem; }
                table.tabla-lineas th { background: #f0f0f0; padding: 6px 4px; text-align: left; border-bottom: 1px solid #ccc;  }
                table.tabla-lineas td { padding: 6px 4px; border-bottom: 1px dashed #eee; }
                .footer { text-align: center; font-size: 0.75rem; padding-top: 15px; border-top: 1px solid #ccc; margin-top: 20px;}
            </style>
        </head>
        <body>
            <div class="header">
                <h1>${tipoTitulo}</h1>
            </div>
            
            <div class="datos">
                ${emisorHtml}
                <div style="margin-top: 10px;">
                    <p><strong>Nº Factura/Ticket:</strong> ${ultimaVenta.id}</p>
                    <p><strong>Fecha Operación y Expedición:</strong> ${ultimaVenta.fecha}</p>
                    <p><strong>Método de pago:</strong> ${ultimaVenta.metodoPago}</p>
                </div>
                ${receptorHtml}
            </div>

            <table class="tabla-lineas">
                <thead>
                    <tr><th>Desc.</th><th style="text-align:center">Cant</th><th style="text-align:right">Precio</th><th style="text-align:center">IVA</th><th style="text-align:right">Subt.</th></tr>
                </thead>
                <tbody>${lineasHtml}</tbody>
            </table>
            
            ${totalesHtml}

            <div class="datos-pago" style="font-size: 0.85rem; margin-top: 15px; border-top: 1px dashed #ccc; padding-top: 10px;">
                <p><strong>Entregado:</strong> ${ultimaVenta.entregado} €</p>
                <p><strong>Cambio devuelto:</strong> ${ultimaVenta.cambio} €</p>
            </div>
            
            ${obsHtml}

            <div class="footer">
                <p><strong>GRACIAS POR SU COMPRA</strong></p>
                <p>Los precios mostrados incluyen IVA.</p>
            </div>
        </body>
        </html>`;

        // Crear un iframe oculto para imprimir sin afectar la página actual
        const iframe = document.createElement('iframe');
        iframe.style.position = 'absolute';
        iframe.style.top = '-10000px';
        document.body.appendChild(iframe);
        iframe.contentDocument.write(contenido);
        iframe.contentDocument.close();

        // Cuando el iframe cargue, ejecutar la impresión y luego eliminarlo
        iframe.onload = function () {
            iframe.contentWindow.print();
            setTimeout(() => iframe.remove(), 1000);
        };
    }

    // ======================== ENVIAR POR CORREO ========================

    /**
     * mostrarFormEmail()
     * Muestra el formulario de envío por correo electrónico dentro del modal de venta exitosa.
     */
    function mostrarFormEmail() {
        document.getElementById('formEmail').style.display = 'block';
        document.getElementById('inputEmail').focus();
    }

    /**
     * enviarPorCorreo()
     * Envía los datos de la última venta por correo electrónico al cliente.
     * Realiza una petición AJAX POST a api/enviarCorreo.php con todos los datos de la venta.
     * Muestra el estado del envío (enviando, éxito o error) en el elemento #emailStatus.
     */
    function enviarPorCorreo() {
        if (typeof ultimaVenta === 'undefined') return;

        const email = document.getElementById('inputEmail').value.trim();
        const statusEl = document.getElementById('emailStatus');

        // Validación básica del email
        if (!email || !email.includes('@')) {
            statusEl.textContent = 'Por favor, introduce un correo válido.';
            statusEl.className = 'email-status email-error';
            return;
        }

        // Mostrar estado "Enviando..."
        statusEl.textContent = 'Enviando...';
        statusEl.className = 'email-status email-enviando';

        // Petición AJAX al endpoint de envío de correo
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
                metodoPago: ultimaVenta.metodoPago,
                entregado: ultimaVenta.entregado,
                cambio: ultimaVenta.cambio,
                clienteNif: ultimaVenta.clienteNif,
                clienteNombre: ultimaVenta.clienteNombre,
                clienteDir: ultimaVenta.clienteDir,
                clienteObs: ultimaVenta.clienteObs,
                descuentoTipo: ultimaVenta.descuentoTipo,
                descuentoValor: ultimaVenta.descuentoValor,
                descuentoCupon: ultimaVenta.descuentoCupon
            })
        })
            .then(res => res.json())
            .then(data => {
                if (data.ok) {
                    // Envío exitoso
                    statusEl.textContent = '✓ Correo enviado correctamente a ' + email;
                    statusEl.className = 'email-status email-ok';
                } else {
                    // Error del servidor
                    statusEl.textContent = '✗ ' + (data.mensaje || 'Error al enviar el correo.');
                    statusEl.className = 'email-status email-error';
                }
            })
            .catch(err => {
                // Error de conexión
                statusEl.textContent = '✗ Error de conexión al enviar el correo.';
                statusEl.className = 'email-status email-error';
            });
    }

    // ======================== CAJA ========================

    /**
     * mostrarModalAbrirCaja()
     * Muestra el modal de apertura de caja y enfoca el input del importe inicial.
     */
    function mostrarModalAbrirCaja() {
        document.getElementById('modalAbrirCaja').style.display = 'flex';
        document.getElementById('importeInicial').focus();
    }

    /**
     * mostrarModalDevolucion()
     * Muestra el modal de devolución y enfoca el buscador de productos.
     */
    function mostrarModalDevolucion() {
        document.getElementById('modalDevolucion').style.display = 'flex';
        document.getElementById('buscarProductoDev').focus();
    }

    /**
     * mostrarModalRetiro()
     * Muestra el modal de retiro de dinero y enfoca el input del importe.
     */
    function mostrarModalRetiro() {
        document.getElementById('modalRetiro').style.display = 'flex';
        document.getElementById('importeRetiro').focus();
    }

    // ======================== DEVOLUCIONES ========================

    /** Array para almacenar productos cargados por AJAX (reservado para uso futuro) */
    let todosLosProductos = [];

    /**
     * filtrarProductosDev(query)
     * Busca productos por nombre mediante AJAX para el formulario de devolución.
     * Muestra los resultados en un dropdown debajo del input de búsqueda.
     * Requiere al menos 2 caracteres para iniciar la búsqueda.
     * @param {string} query - Texto de búsqueda introducido por el usuario
     */
    function filtrarProductosDev(query) {
        const resultados = document.getElementById('resultadosBusquedaDev');

        // No buscar si hay menos de 2 caracteres
        if (query.length < 2) {
            resultados.style.display = 'none';
            return;
        }

        // Petición AJAX a la API de productos
        fetch('api/productos.php?buscarProducto=' + encodeURIComponent(query))
            .then(res => res.json())
            .then(productos => {
                if (productos.length === 0) {
                    resultados.style.display = 'none';
                    return;
                }

                // Generar los elementos del dropdown con nombre y precio
                resultados.innerHTML = '';
                productos.forEach(p => {
                    const div = document.createElement('div');
                    div.className = 'resultado-item-dev';
                    div.innerHTML = `
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <span><strong>${p.nombre}</strong></span>
                            <span style="color: #6b7280; font-size: 0.8rem;">${p.precio} €</span>
                        </div>
                    `;
                    // Al hacer clic en un resultado, seleccionar ese producto
                    div.onclick = () => seleccionarProductoDev(p);
                    resultados.appendChild(div);
                });
                resultados.style.display = 'block';
            });
    }

    /**
     * seleccionarProductoDev(producto)
     * Selecciona un producto del dropdown de búsqueda para la devolución.
     * Rellena los campos del formulario y habilita el botón de confirmación.
     * @param {Object} producto - Objeto con id, nombre y precio del producto
     */
    function seleccionarProductoDev(producto) {
        document.getElementById('nombreProductoDev').value = producto.nombre;
        document.getElementById('idProductoDev').value = producto.id;
        document.getElementById('precioUnitarioDev').value = producto.precio;
        document.getElementById('buscarProductoDev').value = '';
        document.getElementById('resultadosBusquedaDev').style.display = 'none';

        // Recalcular el total a devolver
        calcularTotalDev();

        // Habilitar el botón de confirmación de devolución
        const btn = document.getElementById('btnConfirmarDev');
        btn.disabled = false;
        btn.style.opacity = '1';
    }

    /**
     * calcularTotalDev()
     * Calcula el total a devolver multiplicando precio unitario × cantidad.
     * Actualiza el display visual y el campo oculto del formulario.
     */
    function calcularTotalDev() {
        const precio = parseFloat(document.getElementById('precioUnitarioDev').value) || 0;
        const cantidad = parseInt(document.getElementById('cantidadDev').value) || 0;
        const total = precio * cantidad;

        // Actualizar el display formateado y el campo oculto
        document.getElementById('totalDevDisplay').textContent = total.toLocaleString('es-ES', { minimumFractionDigits: 2 }) + ' €';
        document.getElementById('importeTotalDev').value = total.toFixed(2);
    }

    /**
     * updateMethodUI(radio)
     * Actualiza la interfaz visual de los chips de método de pago en el modal de devolución.
     * Resalta el chip seleccionado con borde rojo y desactiva los demás.
     * @param {HTMLInputElement} radio - El radio button seleccionado
     */
    function updateMethodUI(radio) {
        // Desactivar todos los chips (estilo gris)
        document.querySelectorAll('.method-chip').forEach(chip => {
            chip.style.border = '2px solid #e5e7eb';
            chip.style.color = '#6b7280';
            chip.style.fontWeight = '400';
            chip.classList.remove('active');
        });

        // Activar el chip seleccionado (estilo rojo)
        const chip = document.getElementById('chip-' + radio.value);
        if (chip) {
            chip.style.border = '2px solid #b91c1c';
            chip.style.color = '#b91c1c';
            chip.style.fontWeight = '600';
            chip.classList.add('active');
        }
    }

    // ======================== UTILIDADES ========================

    /**
     * cerrarModal(id)
     * Cierra un modal ocultándolo (display: none).
     * @param {string} id - ID del elemento modal-overlay a ocultar
     */
    function cerrarModal(id) {
        document.getElementById(id).style.display = 'none';
    }

    /**
     * verificarLimiteEfectivo()
     * Verifica si el total del carrito supera los 1.000€ con método de pago en efectivo.
     * Si se supera, muestra un aviso visual debajo del selector de método de pago.
     */
    function verificarLimiteEfectivo() {
        const metodo = document.getElementById('metodoPago')?.value;
        const total = obtenerTotalCalculado();
        const aviso = document.getElementById('avisoLimiteEfectivo');

        if (aviso) {
            if (metodo === 'efectivo' && total > 1000) {
                aviso.style.display = 'block';
            } else {
                aviso.style.display = 'none';
            }
        }
    }

    // Listener para el selector de método de pago:
    // Cada vez que cambia, verifica si se debe mostrar el aviso de límite de efectivo
    document.addEventListener('DOMContentLoaded', () => {
        const selectPago = document.getElementById('metodoPago');
        if (selectPago) {
            selectPago.addEventListener('change', verificarLimiteEfectivo);
        }
    });
</script>