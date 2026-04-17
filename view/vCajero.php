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
<!-- Inyectar traducciones para JS -->
<script>
    const LANG = <?php echo json_encode($LANG); ?>;
    window.PUEDE_PRODUCTO_COMODIN = <?php echo $puedeProductoComodin ? 'true' : 'false'; ?>;
</script>

<!-- ##=========================== SECCIÓN PRINCIPAL DEL CAJERO ===========================## -->
<!-- Contenedor principal dividido en dos paneles: productos (izquierda) y ticket (derecha) -->
<section id="cajero">

    <!-- ======================== PANEL IZQUIERDO: CATEGORÍAS Y PRODUCTOS ======================== -->
    <!-- Contiene el buscador, filtros de categoría, botones de acción y el grid de productos -->
    <div class="cajero-productos">

        <!-- ==================== BUSCADOR DE PRODUCTOS ==================== -->
        <!-- Input de búsqueda que filtra productos en tiempo real mediante la función buscarProductos() -->
        <div id="formBuscarProducto">
            <div style="display: flex; gap: 10px; align-items: center; flex: 1;">
                <label for="inputBuscarProducto"
                    style="font-weight: 600; white-space: nowrap;"><?php echo t('cajero.search'); ?></label>
                <input type="text" id="inputBuscarProducto" class="input-buscarProducto"
                    placeholder="<?php echo t('cajero.search_placeholder'); ?>" oninput="buscarProductos()"
                    autocomplete="off" style="width: 100%;" />
            </div>

            <!-- INDICADOR DE EFECTIVO EN CAJA -->
            <?php if ($sesionCaja): ?>
                <div class="indicador-efectivo" title="Efectivo actual en caja">
                    <span class="label"><?php echo t('cajero.cash_in_register'); ?></span>
                    <span class="amount"><?php echo number_format($sesionCaja->getImporteActual(), 2, ',', '.'); ?> €</span>
                </div>
                <?php
            else: ?>
                <div class="indicador-efectivo caja-cerrada">
                    <span class="label"><?php echo t('cajero.register_closed'); ?></span>
                </div>
                <?php
            endif; ?>
        </div>

        <!-- ==================== FILTROS DE CATEGORÍA ==================== -->
        <!-- Botones generados dinámicamente desde PHP para filtrar productos por categoría -->
        <!-- El botón "Todos" (data-categoria="") muestra todos los productos sin filtro -->
        <div class="cajero-categorias">
            <!-- Botón "Todos": muestra todos los productos, activo por defecto -->
            <button class="cat-btn activa" data-categoria="" onclick="seleccionarCategoria(this, null)">
                <?php echo t('cajero.all'); ?>
            </button>
            <!-- Bucle PHP: genera un botón por cada categoría existente en la base de datos -->
            <?php foreach ($categorias as $cat): ?>
                <button class="cat-btn" data-categoria="<?php echo $cat->getId(); ?>"
                    onclick="seleccionarCategoria(this, <?php echo $cat->getId(); ?>)">
                    <?php echo t('categories.' . $cat->getSlug()); ?>
                </button>
                <?php
            endforeach; ?>
        </div>

        <!-- ==================== BARRA DE OPCIONES EXTRA ==================== -->
        <!-- Contiene los botones de acción principales y el indicador de efectivo en caja -->
        <div class="cajero-opciones-extra">

            <!-- Flecha izquierda del carrusel -->
            <button type="button" class="cajero-carousel-arrow cajero-carousel-arrow-left" id="cajeroCarouselArrowLeft"
                onclick="scrollCarouselBotonesIzquierda()" title="<?php echo t('cajero.see_prev_options'); ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
            </button>

            <!-- Contenedor carrusel de botones -->
            <div class="cajero-botones-carousel" id="cajeroCarousel">
                <div class="cajero-botones-track" id="cajeroCarouselTrack">

                    <!-- Botón HACER CAJA -->
                    <form method="POST" action="index.php" style="margin: 0; flex-shrink: 0;">
                        <input type="hidden" name="accion" value="previsualizarCaja">
                        <button type="submit" class="btn-hacerCaja" id="btnHacerCaja" <?php echo !$sesionCaja ? 'disabled' : ''; ?>
                            style="<?php echo !$sesionCaja ? 'opacity: 0.3; background: red; cursor: not-allowed;' : ''; ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <line x1="12" y1="1" x2="12" y2="23"></line>
                                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                            </svg>
                            <?php echo t('cajero.close_register'); ?>
                        </button>
                    </form>

                    <!-- Botón ABRIR CAJA -->
                    <button type="button" class="btn-abrirCaja" id="btnAbrirCaja" onclick="mostrarModalAbrirCaja()"
                        <?php echo $sesionCaja ? 'disabled' : ''; ?>
                        style="<?php echo $sesionCaja ? 'opacity: 0.3; background: red; cursor: not-allowed;' : ''; ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                        <?php echo t('cajero.open_register'); ?>
                    </button>

                    <!-- Botón DEVOLUCIÓN -->
                    <button type="button" class="btn-devolucion" id="btnDevolucion" onclick="mostrarModalDevolucion()"
                        <?php echo !$sesionCaja ? 'disabled' : ''; ?>
                        style="<?php echo !$sesionCaja ? 'opacity: 0.3; background: #991b1b; cursor: not-allowed;' : ''; ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 4v6h6"></path>
                            <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
                        </svg>
                        <?php echo t('cajero.return'); ?>
                    </button>

                    <!-- Botón RETIRAR DINERO -->
                    <?php if ($puedeRetirarDinero): ?>
                        <button type="button" class="btn-retiro" id="btnRetiro" onclick="mostrarModalRetiro()" <?php echo !$sesionCaja ? 'disabled' : ''; ?>
                            style="<?php echo !$sesionCaja ? 'opacity: 0.3; background: #ea580c; cursor: not-allowed;' : ''; ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect width="20" height="12" x="2" y="6" rx="2"></rect>
                            <circle cx="12" cy="12" r="2"></circle>
                            <path d="M6 12h.01M18 12h.01"></path>
                            </svg>
                            <?php echo t('cajero.withdraw_money'); ?>
                        </button>
                    <?php endif; ?>

                    <!-- Botón HISTORIAL DE VENTAS -->
                    <button type="button" class="btn-historial" id="btnHistorial" onclick="mostrarHistorialVentas()"
                        <?php echo !$sesionCaja ? 'disabled' : ''; ?>
                        style="<?php echo !$sesionCaja ? 'opacity: 0.5; cursor: not-allowed;' : ''; ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 8v4l3 3"></path>
                            <circle cx="12" cy="12" r="10"></circle>
                        </svg>
                        <?php echo t('cajero.sales_history'); ?>
                    </button>

                    <!-- Botón NUEVO PRODUCTO -->
                    <button type="button" class="btn-nuevo-producto" id="btnNuevoProducto"
                        onclick="abrirModalNuevoProducto()" <?php echo !$sesionCaja ? 'disabled' : ''; ?>
                        style="display:none; <?php echo !$sesionCaja ? 'opacity: 0.3; cursor: not-allowed;' : ''; ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        <?php echo t('cajero.new_product'); ?>
                    </button>

                    <!-- Botón CLIENTE HABITUAL -->
                    <button type="button" class="btn-nuevo-producto" id="btnClienteHabitual"
                        onclick="abrirModalClienteHabitual()" <?php echo !$sesionCaja ? 'disabled' : ''; ?>
                        style="<?php echo !$sesionCaja ? 'opacity: 0.3; cursor: not-allowed;' : ''; ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <line x1="19" y1="8" x2="19" y2="14"></line>
                            <line x1="22" y1="11" x2="16" y2="11"></line>
                        </svg>
                        <?php echo t('cajero.new_client'); ?>
                    </button>

                    <!-- Botón HISTORIAL DE DEVOLUCIONES -->
                    <button type="button" class="btn-nuevo-producto" id="btnHistorialDevoluciones"
                        onclick="mostrarHistorialDevoluciones()" <?php echo !$sesionCaja ? 'disabled' : ''; ?>
                        style="<?php echo !$sesionCaja ? 'opacity: 0.3; cursor: not-allowed;' : ''; ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 4v6h6"></path>
                            <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
                        </svg>
                        <?php echo t('cajero.returns_history'); ?>
                    </button>

                    <!-- Botón CAMBIAR PRECIOS: abre el modal para cambiar precios base/tarifas -->
                    <?php if ($puedeModificarPrecios): ?>
                        <button type="button" class="btn-nuevo-producto" id="btnCambiarPrecios"
                            onclick="mostrarModalCambiarPrecios()" <?php echo !$sesionCaja ? 'disabled' : ''; ?>
                            style="<?php echo !$sesionCaja ? 'opacity: 0.3; cursor: not-allowed;' : ''; ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="12" y1="1" x2="12" y2="23"></line>
                                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                            </svg>
                            <?php echo t('cajero.change_prices'); ?>
                        </button>
                    <?php endif; ?>

                </div>
            </div>

            <!-- Flecha de scroll del carrusel -->
            <button type="button" class="cajero-carousel-arrow" id="cajeroCarouselArrow"
                onclick="scrollCarouselBotones()" title="<?php echo t('cajero.see_more_options'); ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
            </button>
        </div>

        <!-- ==================== GRID DE PRODUCTOS ==================== -->
        <!-- Muestra las tarjetas de producto en formato grid -->
        <!-- Cada tarjeta contiene: nombre, imagen, precio y stock -->
        <!-- Al hacer clic en una tarjeta se ejecuta agregarAlCarrito(this) -->
        <div class="productos-grid" id="productosGrid">
            <?php if (empty($productos)): ?>
                <!-- Mensaje cuando no hay productos disponibles -->
                <p class="sin-productos"><?php echo t('cajero.no_products'); ?></p>
                <?php
            else: ?>
                <!-- Bucle PHP: genera una tarjeta por cada producto -->
                <?php foreach ($productos as $prod): ?>
                    <?php
                    // 1. Encontrar la tarifa 'Cliente' (por defecto)
                    $tarifaClienteId = null;
                    foreach ($tarifas as $t) {
                        if ($t['nombre'] === 'Cliente') {
                            $tarifaClienteId = $t['id'];
                            break;
                        }
                    }

                    // 2. Comprobar si hay precio para esa tarifa (ya sea manual o calculado)
                    $preciosManuales = $prod->getPreciosTarifas();
                    $precioBaseEfectivo = $prod->getPrecio();
                    if ($tarifaClienteId && isset($preciosManuales[$tarifaClienteId])) {
                        $precioBaseEfectivo = $preciosManuales[$tarifaClienteId]['precio'];
                    }

                    // 3. Calcular PVP inicial
                    $precioPVP = $precioBaseEfectivo * (1 + ($prod->getIvaPorcentaje() / 100));
                    $precioPVP_fmt = number_format($precioPVP, 2, '.', '');
                    ?>
                    <!-- Tarjeta de producto con atributos data-* para el carrito JS -->
                    <!-- data-id: ID del producto -->
                    <!-- data-nombre: nombre del producto (escapado con htmlspecialchars) -->
                    <!-- data-precio: precio unitario -->
                    <!-- data-stock: stock disponible -->
                    <!-- Si el stock es 0, la tarjeta se muestra con opacidad reducida y sin interacción -->
                    <div class="producto-card" data-id="<?php echo $prod->getId(); ?>"
                        data-nombre="<?php echo htmlspecialchars($prod->getNombre()); ?>"
                        data-precio="<?php echo $precioBaseEfectivo; ?>"
                        data-precio-original="<?php echo $prod->getPrecio(); ?>" data-pvp="<?php echo $precioPVP_fmt; ?>"
                        data-iva="<?php echo $prod->getIvaPorcentaje(); ?>"
                        data-decimales="<?php echo $prod->getDecimales(); ?>"
                        data-precios-tarifas='<?php echo htmlspecialchars(json_encode($prod->getPreciosTarifas()), ENT_QUOTES, 'UTF-8'); ?>'
                        data-stock="<?php echo $prod->getStock(); ?>" onclick="agregarAlCarrito(this)" style="<?php if ($prod->getStock() <= 0) {
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
                        <div class="producto-info-inferior" style="display: flex; flex-direction: column; gap: 2px;">
                            <span
                                class="producto-precio"><?php echo number_format($precioPVP, $prod->getDecimales(), ',', '.'); ?>
                                €</span>

                            <!-- Selector de tarifa -->
                            <select class="tarifa-selector" onclick="event.stopPropagation()"
                                onfocus="guardarTarifaAnterior(this)"
                                onchange="actualizarPrecioCard(this, <?php echo $prod->getPrecio(); ?>, <?php echo $prod->getIvaPorcentaje(); ?>)">
                                <?php foreach ($tarifas as $tarifa): ?>
                                    <option value="<?php echo $tarifa['descuento_porcentaje']; ?>"
                                        data-requiere-cliente="<?php echo $tarifa['requiere_cliente']; ?>"
                                        data-tarifa-id="<?php echo $tarifa['id']; ?>" <?php echo ($tarifa['nombre'] === 'Cliente') ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($tarifa['nombre']); ?>
                                    </option>
                                    <?php
                                endforeach; ?>
                            </select>

                            <span class="producto-stock" <?php if ($prod->getStock() <= 0) {
                                echo 'style="color: red; text-decoration: underline;"';
                            } ?>><?php echo t('cajero.stock'); ?>:
                                <?php echo $prod->getStock(); ?></span>
                        </div>
                    </div>
                    <?php
                endforeach; ?>
                <?php
            endif; ?>
        </div>
    </div>

    <!-- ======================== PANEL DERECHO: TICKET / CARRITO ======================== -->
    <!-- Muestra los productos añadidos al carrito, el total, método de pago y acciones -->
    <div class="cajero-ticket">

        <!-- Cabecera del ticket: título y reloj en tiempo real -->
        <div class="ticket-header">
            <h3><?php echo t('ticket.added_products'); ?></h3>
            <!-- Reloj que se actualiza cada segundo mostrando fecha y hora actual -->
            <span class="ticket-fecha">
                <script>
                    /*
                        * actualizarFechaHora()
                        * Actualiza el elemento.ticket - fecha con la fecha y hora actual
                            * en formato DD / MM / AAAA HH: MM: SS.Se ejecuta cada segundo.
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

        <!-- Indicador de cliente identificado (DNI) - se muestra al acumular puntos o aplicar descuento -->
        <div id="indicadorClienteDni"
            style="display: none; background: linear-gradient(135deg, #eff6ff, #dbeafe); border: 1px solid #3b82f6; border-radius: 8px; padding: 8px 14px; margin: 0 10px 8px 10px; align-items: center; gap: 8px; font-size: 0.85rem;">
            <span style="font-size: 1.1rem;">👤</span>
            <span id="indicadorClienteNombre"
                style="color: #1e40af; font-weight: 600; font-size: 0.75rem; max-width: 150px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"
                title=""></span>
            <span style="color: #1e40af; font-weight: 600; opacity: 0.6;">-</span>
            <span id="indicadorClienteDniValor" style="color: #1e3a8a; font-weight: 700; letter-spacing: 0.5px;"></span>
            <button type="button" onclick="desvincularCliente()"
                style="background: #eff6ff; border: 1px solid #3b82f6; color: #ef4444; font-size: 1rem; cursor: pointer; padding: 2px 8px; border-radius: 4px; line-height: 1; margin-left: auto; font-weight: bold; transition: all 0.2s;"
                onmouseover="this.style.background='#ef4444'; this.style.color='white'"
                onmouseout="this.style.background='#eff6ff'; this.style.color='#ef4444'"
                title="<?php echo t('ticket.remove_client'); ?>">✕</button>
        </div>

        <!-- Contenedor de las líneas del ticket (se rellena dinámicamente con JS) -->
        <div class="ticket-lineas" id="ticketLineas">
            <p class="ticket-vacio"><?php echo t('ticket.empty'); ?></p>
        </div>

        <!-- Contenedor para el desglose de subtotal y descuento (se rellena con JS) -->
        <div id="ticketDesglose"></div>

        <!-- Total del ticket -->
        <div class="ticket-total">
            <span><?php echo t('ticket.total'); ?></span>
            <span id="ticketTotal">0,00 €</span>
        </div>

        <!-- ==================== ACCIONES DEL TICKET ==================== -->
        <!-- Selector de método de pago, aviso de límite de efectivo y botones de acción -->
        <div class="ticket-acciones">
            <!-- Fila 1: Botones de descuento, puntos, vaciar, posponer y recuperar -->
            <div class="ticket-acciones-fila">
                <!-- Botón DESCUENTO: abre el modal para aplicar descuento porcentual o por cupón -->
                <button class="btn-descuento" id="btnDescuento" onclick="aplicarDescuento()" disabled
                    title="<?php echo t('ticket.discount'); ?>">
                    🏷️
                </button>

                <!-- Botón PUNTOS: abre el modal para consultar y canjear puntos -->
                <button class="btn-descuento" id="btnPuntos" onclick="abrirModalPuntosCliente()"
                    title="<?php echo t('ticket.points'); ?>" style="background: #10b981;">
                    👤
                </button>

                <!-- Botón VACIAR: elimina todos los productos del carrito -->
                <button class="btn-cancelar" onclick="vaciarCarrito()" title="<?php echo t('ticket.empty_cart'); ?>">
                    🗑️
                </button>

                <!-- Botón POSPONER: guarda la venta sin terminar para recuperarla después -->
                <button class="btn-descuento" id="btnPosponer" onclick="posponerVenta()" disabled
                    style="background: #8b5cf6;" title="<?php echo t('ticket.postpone'); ?>">
                    ⏳
                </button>

                <!-- Botón VER POSPUESTAS: muestra modal con todas las ventas pospuestas para recuperar -->
                <button class="btn-descuento" id="btnVerPospuestas" onclick="mostrarModalVentasPospuestas()" disabled
                    style="background: #8b5cf6;" title="<?php echo t('ticket.see_postponed'); ?>">
                    📋
                </button>
            </div>

            <!-- Fila 2: Método de pago y botón cobrar -->
            <div class="ticket-acciones-fila">
                <!-- Selector de método de pago: Efectivo, Tarjeta o Bizum -->
                <select id="metodoPago">
                    <option value="efectivo"><?php echo t('ticket.cash'); ?></option>
                    <option value="tarjeta"><?php echo t('ticket.card'); ?></option>
                    <option value="bizum"><?php echo t('ticket.bizum'); ?></option>
                    <option value="mixto"><?php echo t('ticket.mixed'); ?></option>
                </select>

                <!-- Selector de tarifa: ahora gestionado por producto, mantenemos el ID occulto para compatibilidad JS -->
                <?php
                $idTarifaCliente = 1;
                foreach ($tarifas as $t) {
                    if ($t['nombre'] === 'Cliente') {
                        $idTarifaCliente = $t['id'];
                        break;
                    }
                }
                ?>
                <input type="hidden" id="tarifaVenta" value="<?php echo $idTarifaCliente; ?>">

                <!-- Aviso legal: no se permite pago en efectivo superior a 1.000€ -->
                <div id="avisoLimiteEfectivo"
                    style="display: none; color: #dc2626; font-size: 0.75rem; margin-top: 5px; font-weight: 600;">
                    <?php echo t('ticket.cash_limit_warning'); ?>
                </div>

                <!-- Botón COBRAR: inicia el proceso de cobro (modal de cambio o tipo de documento) -->
                <button class="btn-cobrar" id="btnCobrar" onclick="intentarCobrar()" disabled>
                    <?php echo t('ticket.charge'); ?>
                </button>
            </div>
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
        <!-- Tarifa seleccionada -->
        <input type="hidden" name="idTarifa" id="inputIdTarifa">
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
        <input type="hidden" name="descuentoTarifaCupon" id="inputDescuentoTarifaCupon">
        <input type="hidden" name="descuentoTarifaValor" id="inputDescuentoTarifaValor">
        <input type="hidden" name="descuentoTarifaTipo" id="inputDescuentoTarifaTipo">
        <input type="hidden" name="descuentoManualCupon" id="inputDescuentoManualCupon">
        <input type="hidden" name="descuentoManualValor" id="inputDescuentoManualValor">
        <input type="hidden" name="descuentoManualTipo" id="inputDescuentoManualTipo">
        <!-- Campos de puntos canjeados -->
        <input type="hidden" name="puntosCanjeadosDni" id="inputPuntosCanjeadosDni">
        <input type="hidden" name="puntosCanjeadosCantidad" id="inputPuntosCanjeadosCantidad">
        <input type="hidden" name="puntosGanados" id="inputPuntosGanados">
        <input type="hidden" name="puntosBalance" id="inputPuntosBalance">
        <input type="hidden" name="clienteIdentificadoPuntos" id="inputClienteIdentificadoPuntos">
        <input type="hidden" name="clienteIdentificadoPuntos" id="inputClienteIdentificadoPuntos" value="false">
        <input type="hidden" name="mensajePersonalizado" id="inputMensajePersonalizado">
        <!-- Desglose de pago mixto (JSON) -->
        <input type="hidden" name="desglosePago" id="inputDesglosePago">
    </form>
</section>

<!-- ##=========================== MODAL: CALCULAR CAMBIO (EFECTIVO) ===========================## -->
<!-- Modal que aparece cuando el método de pago es "efectivo" -->
<!-- Permite al cajero introducir la cantidad entregada por el cliente y calcula el cambio -->
<div class="modal-overlay" id="modalCambio" style="display:none;">
    <div class="modal-content"
        style="max-width: 440px; padding: 40px; border-radius: 24px; background: var(--bg-card); border: 1px solid var(--border-main); text-align: left; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);">

        <!-- Cabecera Minimalista -->
        <div style="margin-bottom: 32px;">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
                <div
                    style="display: flex; align-items: center; justify-content: center; width: 36px; height: 36px; background: var(--bg-main); border-radius: 10px; color: var(--text-muted);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="6" width="20" height="12" rx="2"></rect>
                        <circle cx="12" cy="12" r="2"></circle>
                    </svg>
                </div>
                <h3
                    style="margin: 0; font-size: 1.4rem; font-weight: 800; color: var(--text-main); letter-spacing: -0.01em;">
                    <?php echo t('cash_modal.title'); ?>
                </h3>
            </div>
            <p style="margin: 0; color: var(--text-muted); font-size: 0.95rem; line-height: 1.5;">
                <?php echo t('cash_modal.subtitle'); ?>
            </p>
        </div>

        <div style="display: grid; gap: 28px;">
            <!-- Display de Total -->
            <div style="padding-bottom: 12px; border-bottom: 1px solid var(--border-main);">
                <span
                    style="display: block; font-size: 0.75rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 8px;"><?php echo t('cash_modal.total_to_charge'); ?></span>
                <span id="cambioTotalPagar"
                    style="font-size: 3rem; font-weight: 950; color: var(--text-main); letter-spacing: -0.04em; line-height: 1;">0,00
                    €</span>
            </div>

            <!-- Fila de Entrada -->
            <div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                    <label for="inputDineroEntregado"
                        style="font-size: 0.75rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.08em;"><?php echo t('cash_modal.money_received'); ?></label>
                    <button type="button" onclick="fijarImporteExacto()"
                        style="background: none; border: none; color: var(--accent); font-size: 0.8rem; font-weight: 700; cursor: pointer; padding: 0; text-decoration: underline; text-underline-offset: 4px; transition: color 0.2s;"
                        onmouseover="this.style.color='var(--accent-hover)'"
                        onmouseout="this.style.color='var(--accent)'">
                        <?php echo t('cash_modal.exact_amount'); ?>
                    </button>
                </div>
                <div style="position: relative; display: flex; align-items: center;">
                    <input type="number" id="inputDineroEntregado" step="0.0001"
                        oninput="validar4Decimales(this); calcularCambio()" min="0" placeholder="0.00"
                        style="width: 100%; padding: 14px 45px 14px 20px; font-size: 1.8rem; font-weight: 700; border: 2px solid var(--border-main); border-radius: 14px; background: var(--bg-input); color: var(--text-main); outline: none; transition: border-color 0.2s, box-shadow 0.2s;"
                        oninput="calcularCambio()" onkeypress="if(event.key === 'Enter') confirmarCambio()"
                        onfocus="this.style.borderColor='var(--accent)'; this.style.boxShadow='0 0 0 4px rgba(37, 99, 235, 0.1)'"
                        onblur="this.style.borderColor='var(--border-main)'; this.style.boxShadow='none'">
                    <span
                        style="position: absolute; right: 20px; font-size: 1.5rem; font-weight: 700; color: var(--text-muted); pointer-events: none;">€</span>
                </div>
                <p id="cambioError"
                    style="color: var(--accent-danger); font-size: 0.85rem; margin-top: 10px; font-weight: 700; display: none; align-items: center; gap: 5px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <?php echo t('cash_modal.insufficient'); ?>
                </p>
            </div>

            <!-- Resultado de Cambio -->
            <div
                style="margin-top: 4px; padding: 24px; border-radius: 18px; background: var(--bg-accent-success); border: 2px solid transparent; display: flex; justify-content: space-between; align-items: center; transition: all 0.3s ease;">
                <div>
                    <span
                        style="display: block; font-size: 0.85rem; font-weight: 800; color: var(--accent-success); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 2px;"><?php echo t('cash_modal.change'); ?></span>
                    <span
                        style="font-size: 0.8rem; color: var(--accent-success); opacity: 0.7;"><?php echo t('cash_modal.for_client'); ?></span>
                </div>
                <span id="cambioDevolver"
                    style="font-size: 2.2rem; font-weight: 900; color: var(--accent-success); letter-spacing: -0.02em;">0,00
                    €</span>
            </div>
        </div>

        <div style="display: flex; gap: 12px; margin-top: 40px;">
            <button class="btn-modal-cancelar" onclick="cerrarModal('modalCambio')"
                style="flex: 1; padding: 16px; border-radius: 14px; font-weight: 700; font-size: 0.95rem; border: 1px solid var(--border-main); background: var(--bg-card); color: var(--text-muted); cursor: pointer; transition: all 0.15s;"
                onmouseover="this.style.background='var(--bg-main)'; this.style.color='var(--text-main)'"
                onmouseout="this.style.background='var(--bg-card)'; this.style.color='var(--text-muted)'"><?php echo t('cash_modal.close'); ?></button>
            <button class="btn-exito" onclick="confirmarCambio()"
                style="flex: 2; padding: 16px; border-radius: 14px; font-weight: 800; font-size: 1.05rem; margin: 0; cursor: pointer; transition: transform 0.1s, opacity 0.2s;"
                onmousedown="this.style.transform='scale(0.98)'"
                onmouseup="this.style.transform='scale(1)'"><?php echo t('cash_modal.confirm_payment'); ?></button>
        </div>
    </div>
</div>

<!-- ##=========================== MODAL: PAGO MIXTO ===========================## -->
<!-- Modal que aparece cuando el método de pago es "mixto" -->
<!-- Permite distribuir el total de la compra entre varios métodos de pago -->
<div class="modal-overlay" id="modalPagoMixto" style="display:none;">
    <div class="modal-content"
        style="max-width: 520px; padding: 40px; border-radius: 24px; background: var(--bg-card); border: 1px solid var(--border-main); text-align: left; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);">

        <!-- Cabecera -->
        <div style="margin-bottom: 28px;">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
                <div
                    style="display: flex; align-items: center; justify-content: center; width: 36px; height: 36px; background: linear-gradient(135deg, #8b5cf6, #6366f1); border-radius: 10px; color: white;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="8" y1="12" x2="16" y2="12"></line>
                        <line x1="12" y1="8" x2="12" y2="16"></line>
                    </svg>
                </div>
                <h3
                    style="margin: 0; font-size: 1.4rem; font-weight: 800; color: var(--text-main); letter-spacing: -0.01em;">
                    <?php echo t('mixed_modal.title'); ?>
                </h3>
            </div>
            <p style="margin: 0; color: var(--text-muted); font-size: 0.95rem; line-height: 1.5;">
                <?php echo t('mixed_modal.subtitle'); ?>
            </p>
        </div>

        <!-- Total a distribuir -->
        <div
            style="padding: 16px; border-radius: 14px; background: var(--bg-main); border: 1px solid var(--border-main); margin-bottom: 24px;">
            <span
                style="display: block; font-size: 0.7rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 6px;"><?php echo t('mixed_modal.total_to_distribute'); ?></span>
            <span id="mixtoTotalDistribuir"
                style="font-size: 2.4rem; font-weight: 950; color: var(--text-main); letter-spacing: -0.04em; line-height: 1;">0,00
                €</span>
        </div>

        <!-- Campos de distribución -->
        <div style="display: grid; gap: 16px; margin-bottom: 20px;">
            <!-- Efectivo -->
            <div style="display: flex; align-items: center; gap: 12px;">
                <div
                    style="display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; background: #ecfdf5; border-radius: 10px; flex-shrink: 0;">
                    <span style="font-size: 1.2rem;">💵</span>
                </div>
                <div style="flex: 1;">
                    <label
                        style="display: block; font-size: 0.72rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 4px;"><?php echo t('ticket.cash'); ?></label>
                    <div style="position: relative;">
                        <input type="number" id="mixtoEfectivo" step="0.01" min="0" placeholder="0.00"
                            oninput="calcularRestanteMixto()"
                            onkeypress="if(event.key === 'Enter') confirmarPagoMixto()"
                            style="width: 100%; padding: 10px 35px 10px 14px; font-size: 1.2rem; font-weight: 700; border: 2px solid var(--border-main); border-radius: 10px; background: var(--bg-input); color: var(--text-main); outline: none; transition: border-color 0.2s;"
                            onfocus="this.style.borderColor='#10b981'"
                            onblur="this.style.borderColor='var(--border-main)'">
                        <span
                            style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); font-size: 1rem; font-weight: 700; color: var(--text-muted); pointer-events: none;">€</span>
                    </div>
                </div>
            </div>

            <!-- Tarjeta -->
            <div style="display: flex; align-items: center; gap: 12px;">
                <div
                    style="display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; background: #eff6ff; border-radius: 10px; flex-shrink: 0;">
                    <span style="font-size: 1.2rem;">💳</span>
                </div>
                <div style="flex: 1;">
                    <label
                        style="display: block; font-size: 0.72rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 4px;"><?php echo t('ticket.card'); ?></label>
                    <div style="position: relative;">
                        <input type="number" id="mixtoTarjeta" step="0.01" min="0" placeholder="0.00"
                            oninput="calcularRestanteMixto()"
                            onkeypress="if(event.key === 'Enter') confirmarPagoMixto()"
                            style="width: 100%; padding: 10px 35px 10px 14px; font-size: 1.2rem; font-weight: 700; border: 2px solid var(--border-main); border-radius: 10px; background: var(--bg-input); color: var(--text-main); outline: none; transition: border-color 0.2s;"
                            onfocus="this.style.borderColor='#3b82f6'"
                            onblur="this.style.borderColor='var(--border-main)'">
                        <span
                            style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); font-size: 1rem; font-weight: 700; color: var(--text-muted); pointer-events: none;">€</span>
                    </div>
                </div>
            </div>

            <!-- Bizum -->
            <div style="display: flex; align-items: center; gap: 12px;">
                <div
                    style="display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; background: #fef3c7; border-radius: 10px; flex-shrink: 0;">
                    <span style="font-size: 1.2rem;">📱</span>
                </div>
                <div style="flex: 1;">
                    <label
                        style="display: block; font-size: 0.72rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 4px;"><?php echo t('ticket.bizum'); ?></label>
                    <div style="position: relative;">
                        <input type="number" id="mixtoBizum" step="0.01" min="0" placeholder="0.00"
                            oninput="calcularRestanteMixto()"
                            onkeypress="if(event.key === 'Enter') confirmarPagoMixto()"
                            style="width: 100%; padding: 10px 35px 10px 14px; font-size: 1.2rem; font-weight: 700; border: 2px solid var(--border-main); border-radius: 10px; background: var(--bg-input); color: var(--text-main); outline: none; transition: border-color 0.2s;"
                            onfocus="this.style.borderColor='#f59e0b'"
                            onblur="this.style.borderColor='var(--border-main)'">
                        <span
                            style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); font-size: 1rem; font-weight: 700; color: var(--text-muted); pointer-events: none;">€</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Indicador de Restante -->
        <div id="mixtoRestanteContainer"
            style="padding: 16px; border-radius: 14px; display: flex; justify-content: space-between; align-items: center; transition: all 0.3s ease; background: var(--bg-accent-danger); border: 2px solid transparent;">
            <div>
                <span id="mixtoRestanteLabel"
                    style="display: block; font-size: 0.8rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 2px; color: var(--accent-danger);"><?php echo t('mixed_modal.remaining'); ?></span>
                <span id="mixtoRestanteSub"
                    style="font-size: 0.75rem; color: var(--accent-danger); opacity: 0.7;"><?php echo t('mixed_modal.distribute_total'); ?></span>
            </div>
            <span id="mixtoRestanteValor"
                style="font-size: 1.8rem; font-weight: 900; letter-spacing: -0.02em; color: var(--accent-danger);">0,00
                €</span>
        </div>

        <!-- Aviso límite efectivo -->
        <p id="mixtoAvisoEfectivo"
            style="display: none; color: #dc2626; font-size: 0.8rem; margin-top: 10px; font-weight: 700; align-items: center; gap: 5px;">
            <?php echo t('mixed_modal.cash_limit'); ?>
        </p>

        <!-- Error de validación -->
        <p id="mixtoError"
            style="display: none; color: var(--accent-danger); font-size: 0.85rem; margin-top: 10px; font-weight: 700;">
        </p>

        <!-- Botones -->
        <div style="display: flex; gap: 12px; margin-top: 28px;">
            <button class="btn-modal-cancelar" onclick="cerrarModal('modalPagoMixto')"
                style="flex: 1; padding: 16px; border-radius: 14px; font-weight: 700; font-size: 0.95rem; border: 1px solid var(--border-main); background: var(--bg-card); color: var(--text-muted); cursor: pointer; transition: all 0.15s;"
                onmouseover="this.style.background='var(--bg-main)'; this.style.color='var(--text-main)'"
                onmouseout="this.style.background='var(--bg-card)'; this.style.color='var(--text-muted)'"><?php echo t('mixed_modal.close'); ?></button>
            <button class="btn-exito" onclick="confirmarPagoMixto()"
                style="flex: 2; padding: 16px; border-radius: 14px; font-weight: 800; font-size: 1.05rem; margin: 0; cursor: pointer; transition: transform 0.1s, opacity 0.2s;"
                onmousedown="this.style.transform='scale(0.98)'"
                onmouseup="this.style.transform='scale(1)'"><?php echo t('mixed_modal.confirm_distribution'); ?></button>
        </div>
    </div>
</div>

<!-- ##=========================== MODAL: TIPO DE DOCUMENTO ===========================## -->
<!-- Modal que permite al usuario elegir entre Ticket (simplificado) o Factura (completa) -->
<!-- Aparece después del modal de cambio (efectivo) o directamente (tarjeta/bizum) -->
<div class="modal-overlay" id="modalTipoDoc" style="display:none;">
    <div class="modal-content modal-tipodoc">
        <h3><?php echo t('doc_type.title'); ?></h3>
        <p class="modal-subtitulo"><?php echo t('doc_type.subtitle'); ?></p>
        <div class="modal-opciones-doc">

            <!-- Opción TICKET: comprobante simplificado (datos del cliente opcionales) -->
            <button class="opcion-doc" onclick="seleccionarDatosCliente('ticket')">
                <!-- Icono SVG de libro/ticket -->
                <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path>
                    <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>
                </svg>
                <span class="opcion-titulo"><?php echo t('doc_type.ticket'); ?></span>
                <span class="opcion-desc"><?php echo t('doc_type.ticket_desc'); ?></span>
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
                <span class="opcion-titulo"><?php echo t('doc_type.invoice'); ?></span>
                <span class="opcion-desc"><?php echo t('doc_type.invoice_desc'); ?></span>
            </button>
        </div>
        <button class="btn-modal-cancelar"
            onclick="cerrarModal('modalTipoDoc')"><?php echo t('doc_type.cancel'); ?></button>
    </div>
</div>

<!-- ##=========================== MODAL: FINALIZAR VENTA (REDiseñado) ===========================## -->
<div class="modal-overlay" id="modalFinalizarVenta" style="display:none;">
    <div class="modal-content modal-finalizar-venta">
        <div class="finalizar-venta-wrapper">
            <!-- Panel Izquierdo: Previsualización con Simulación de Papel -->
            <div class="previsualizacion-col">
                <div class="previsualizacion-header">
                    <div class="control-section-title" style="margin-bottom: 0; display: flex; align-items: center;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            style="margin-right: 5px;">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                        </svg>
                        <?php echo t('checkout.preview'); ?>
                    </div>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <button id="btnZoomTicket" onclick="toggleZoomTicket()" class="btn-zoom-ticket"
                            title="<?php echo t('checkout.zoom'); ?>" style="display: none;">
                            <svg id="iconZoom" xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                                stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="8"></circle>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                <line x1="11" y1="8" x2="11" y2="14" class="icon-plus"></line>
                                <line x1="8" y1="11" x2="14" y2="11" class="icon-plus"></line>
                                <line x1="8" y1="11" x2="14" y2="11" class="icon-minus" style="display: none;"></line>
                            </svg>
                        </button>
                        <div id="tipoDocBadgeCheckout"
                            style="font-size: 0.65rem; font-weight: 800; text-transform: uppercase; padding: 4px 10px; border-radius: 20px; background: var(--bg-accent-success); color: var(--accent-success); letter-spacing: 0.05em;">
                            TICKET</div>
                    </div>
                </div>

                <div class="ticket-preview-viewport">
                    <!-- El contenedor que simula el papel físico -->
                    <div id="ticketPreviewContent" class="paper-simulation tipo-ticket">
                        <div style="text-align:center; padding-top:100px; color:#cbd5e1; font-family: sans-serif;">
                            <?php echo t('checkout.generating'); ?>
                        </div>
                    </div>
                </div>
            </div>


            <!-- Panel Derecho: Controles -->
            <div class="controles-col">
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <h3 style="margin: 0; font-size: 1.35rem; font-weight: 800; color: var(--text-main);">
                            <?php echo t('checkout.finalize_sale'); ?>
                        </h3>
                        <p style="margin: 4px 0 0 0; color: var(--text-muted); font-size: 0.85rem;">
                            <?php echo t('checkout.configure_receipt'); ?>
                        </p>
                    </div>
                    <button onclick="cerrarModal('modalFinalizarVenta')"
                        style="background: var(--bg-main); border: 1px solid var(--border-main); color: var(--text-muted); font-size: 1.1rem; cursor: pointer; padding: 6px 10px; border-radius: 8px; transition: all 0.2s;"
                        onmouseover="this.style.background='var(--bg-accent-danger)'; this.style.color='var(--accent-danger)'"
                        onmouseout="this.style.background='var(--bg-main)'; this.style.color='var(--text-muted)'">✕</button>
                </div>

                <!-- Sección: Tipo de Documento -->
                <div class="control-group">
                    <div class="control-section-title"><?php echo t('checkout.doc_type'); ?></div>
                    <div class="opciones-grid">
                        <div class="checkout-option-card active" id="optTicket"
                            onclick="cambiarTipoDocumentoCheckout('ticket')">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path>
                                <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>
                            </svg>
                            <span><?php echo t('doc_type.ticket'); ?></span>
                        </div>
                        <div class="checkout-option-card" id="optFactura"
                            onclick="cambiarTipoDocumentoCheckout('factura')">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                <line x1="16" y1="17" x2="8" y2="17"></line>
                            </svg>
                            <span><?php echo t('doc_type.invoice'); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Sección: Datos del Cliente -->
                <div class="control-group" id="seccionDatosReceptor">
                    <div class="control-section-title"><?php echo t('checkout.receiver_data'); ?></div>
                    <div id="clientDataSummaryCheckout"
                        style="background: var(--bg-card); border: 2px solid var(--border-main); border-radius: 12px; padding: 14px 16px; display: flex; justify-content: space-between; align-items: center; transition: border-color 0.2s;">
                        <div id="clientDataTextCheckout" style="font-size: 0.85rem; color: var(--text-muted);">
                            <?php echo t('checkout.no_client_data'); ?>
                        </div>
                        <button class="btn-descuento" onclick="abrirDatosClienteDesdeCheckout()"
                            style="padding: 7px 14px; font-size: 0.75rem; white-space: nowrap;">
                            <?php echo t('checkout.edit'); ?>
                        </button>
                    </div>
                </div>

                <!-- Sección: Método de Entrega -->
                <div class="control-group">
                    <div class="control-section-title"><?php echo t('checkout.delivery_method'); ?></div>
                    <div class="opciones-grid">
                        <div class="checkout-option-card active" id="optImprimir"
                            onclick="cambiarMetodoEntregaCheckout('imprimir')">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <polyline points="6 9 6 2 18 2 18 9"></polyline>
                                <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2">
                                </path>
                                <rect x="6" y="14" width="12" height="8"></rect>
                            </svg>
                            <span><?php echo t('checkout.print'); ?></span>
                        </div>
                        <div class="checkout-option-card" id="optEmail" onclick="cambiarMetodoEntregaCheckout('email')">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z">
                                </path>
                                <polyline points="22,6 12,13 2,6"></polyline>
                            </svg>
                            <span><?php echo t('checkout.email'); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Campo Email (oculto por defecto) -->
                <div id="emailContainerCheckout" class="email-delivery-container" style="display: none;">
                    <label
                        style="display: block; font-size: 0.78rem; margin-bottom: 6px; font-weight: 600; color: var(--text-muted);"><?php echo t('checkout.email_label'); ?></label>
                    <input type="email" id="emailCheckout" placeholder="ejemplo@correo.com">
                </div>

                <!-- Información de Impresora -->
                <div class="email-delivery-container" id="infoImpresoraCheckout">
                    <div style="display: flex; align-items: center; gap: 10px; color: #16a34a; font-size: 0.82rem;">
                        <span
                            style="width: 8px; height: 8px; background: #16a34a; border-radius: 50%; flex-shrink: 0; animation: pulse 2s infinite;"></span>
                        <?php echo t('checkout.printer_ready'); ?>
                    </div>
                </div>

                <!-- Sección: Mensaje Personalizado -->
                <div class="control-group">
                    <div class="control-section-title"><?php echo t('checkout.custom_message'); ?></div>
                    <textarea id="mensajePersonalizadoVenta"
                        placeholder="<?php echo t('checkout.custom_message_placeholder'); ?>" style="width: 100%; min-height: 80px; padding: 12px; border: 2px solid var(--border-main); border-radius: 12px; 
                           background: var(--bg-input); color: var(--text-main); font-size: 0.9rem; resize: vertical;
                           outline: none; transition: border-color 0.2s, box-shadow 0.2s;"
                        onfocus="this.style.borderColor='var(--accent)'; this.style.boxShadow='0 0 0 4px rgba(37, 99, 235, 0.1)'"
                        onblur="this.style.borderColor='var(--border-main)'; this.style.boxShadow='none'"
                        oninput="renderizarVistaPreviaTicket()"></textarea>
                </div>

                <!-- Total resumen -->
                <div class="checkout-total-bar" id="checkoutTotalBar">
                    <span class="total-label"><?php echo t('checkout.total_to_charge'); ?></span>
                    <span class="total-amount" id="checkoutTotalAmount">0,00 €</span>
                </div>

                <!-- Botón de acción final -->
                <button class="btn-realizar-venta" onclick="procesarVentaFinal()">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    <?php echo t('checkout.confirm_sale'); ?>
                </button>
            </div>
        </div>
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
            <h3><?php echo t('discount.title'); ?></h3>
            <p><?php echo t('discount.subtitle'); ?></p>
        </div>

        <div class="modal-body-premium">
            <!-- Opción 1: Descuento por porcentaje (0% a 100%) -->
            <div class="form-group-premium">
                <label for="inputPorcentajeDescuento"><?php echo t('discount.percentage'); ?></label>
                <div class="input-with-icon">
                    <input type="number" id="inputPorcentajeDescuento" min="0" max="100" step="1" placeholder="0">
                    <span class="input-suffix">%</span>
                </div>
                <span class="input-hint"><?php echo t('discount.range_hint'); ?></span>
            </div>

            <!-- Separador visual entre las dos opciones -->
            <div class="divider-text"><?php echo t('discount.or'); ?></div>

            <!-- Opción 2: Descuento por código de cupón -->
            <!-- Cupones válidos: PROMO10 (10%), BIENVENIDA5 (5%), FIJO5 (5€ fijo) -->
            <div class="form-group-premium">
                <label for="inputCuponDescuento"><?php echo t('discount.coupon_code'); ?></label>
                <div class="input-cupon">
                    <input type="text" id="inputCuponDescuento"
                        placeholder="<?php echo t('discount.coupon_placeholder'); ?>"
                        style="text-transform: uppercase;">
                </div>
            </div>
        </div>

        <!-- Botones: Cancelar y Aplicar Descuento -->
        <div class="modal-footer-premium" style="display: flex; gap: 10px; align-items: center;">
            <button id="btnQuitarDescuento" class="btn-cancel-flat" onclick="quitarDescuento()"
                style="display: none; background: #fee2e2; color: #dc2626;"><?php echo t('discount.remove'); ?></button>
            <div style="flex-grow: 1;"></div>
            <button class="btn-cancel-flat"
                onclick="cerrarModal('modalDescuento')"><?php echo t('discount.cancel'); ?></button>
            <button class="btn-apply-premium" onclick="procesarDescuento()"><?php echo t('discount.apply'); ?></button>
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
        <h3 id="tituloDatosCliente" style="margin-bottom: 5px;"><?php echo t('client_data.title'); ?></h3>
        <p id="subtituloDatosCliente" class="modal-subtitulo-cliente"><?php echo t('client_data.subtitle'); ?></p>

        <!-- Buscador por DNI -->
        <div style="margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #e5e7eb;">
            <label for="buscarDniCliente"
                style="display: block; margin-bottom: 5px; font-weight: 500; font-size: 0.9rem;"><?php echo t('client_data.search_dni'); ?></label>
            <div style="display: flex; gap: 10px;">
                <input type="text" id="buscarDniCliente"
                    style="flex: 1; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;"
                    placeholder="12345678A" onkeypress="if(event.key === 'Enter') buscarDatosCliente()">
                <button type="button" class="btn-exito" style="margin: 0; padding: 10px 15px;"
                    onclick="buscarDatosCliente()"><?php echo t('client_data.search_btn'); ?></button>
            </div>
            <p id="mensajeBusquedaClienteDatos" style="font-size: 0.85rem; margin-top: 5px; display: none;"></p>
        </div>

        <div style="display: grid; gap: 15px;">
            <!-- Campo NIF/CIF del cliente -->
            <!-- El asterisco rojo (*) se muestra solo en modo Factura -->
            <div>
                <label for="clienteNif"
                    style="display: block; margin-bottom: 5px; font-weight: 500; font-size: 0.9rem;"><?php echo t('client_data.nif_cif'); ?>
                    <span id="reqNif" style="color: #ef4444; display: none;">*</span></label>
                <input type="text" id="clienteNif"
                    style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;"
                    placeholder="B12345678">
                <input type="hidden" id="clientePuntos" value="0">
            </div>

            <!-- Campo Razón Social / Nombre del cliente -->
            <div>
                <label for="clienteNombre"
                    style="display: block; margin-bottom: 5px; font-weight: 500; font-size: 0.9rem;"><?php echo t('client_data.name_company'); ?>
                    <span id="reqNombre" style="color: #ef4444; display: none;">*</span></label>
                <input type="text" id="clienteNombre"
                    style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;"
                    placeholder="<?php echo t('client_data.name_placeholder'); ?>">
            </div>

            <!-- Campo Domicilio Fiscal (solo visible en modo Factura) -->
            <div id="divDireccionCliente" style="display: none;">
                <label for="clienteDireccion"
                    style="display: block; margin-bottom: 5px; font-weight: 500; font-size: 0.9rem;"><?php echo t('client_data.fiscal_address'); ?>
                    <span id="reqDir" style="color: #ef4444; display: none;">*</span></label>
                <input type="text" id="clienteDireccion"
                    style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;"
                    placeholder="<?php echo t('client_data.address_placeholder'); ?>">
            </div>

            <!-- Campo Observaciones (solo visible en modo Factura, siempre opcional) -->
            <div id="divObservacionesCliente" style="display: none;">
                <label for="clienteObservaciones"
                    style="display: block; margin-bottom: 5px; font-weight: 500; font-size: 0.9rem;"><?php echo t('client_data.observations'); ?></label>
                <input type="text" id="clienteObservaciones"
                    style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;"
                    placeholder="<?php echo t('client_data.observations_placeholder'); ?>">
            </div>
        </div>

        <!-- Mensaje de error: se muestra si faltan campos obligatorios en modo Factura -->
        <p id="errorDatosCliente" style="color: #ef4444; font-size: 0.9rem; margin-top: 15px; display: none;">
            <?php echo t('client_data.error_required'); ?>
        </p>

        <!-- Botones: Atrás (vuelve al modal anterior) y Finalizar Venta (valida y envía) -->
        <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 25px;">
            <button class="btn-modal-cancelar"
                onclick="cerrarModalDatosClienteAtras()"><?php echo t('client_data.back'); ?></button>
            <button class="btn-exito" id="btnConfirmarDatos" onclick="validarYConfirmarVenta()"
                style="margin: 0;"><?php echo t('client_data.accept'); ?></button>
        </div>
    </div>
</div>

<!-- ##=========================== MODAL: CLIENTE HABITUAL ===========================## -->
<!-- Modal para añadir un cliente habitual (DNI, nombre, apellidos, fecha alta, compras) -->
<div class="modal-overlay" id="modalClienteHabitual" style="display:none;">
    <div class="modal-content" style="max-width: 500px; text-align: left;">
        <h3 style="margin-bottom: 5px;"><?php echo t('new_client.title'); ?></h3>
        <p class="modal-subtitulo" style="margin-bottom: 20px;"><?php echo t('new_client.subtitle'); ?></p>

        <div style="display: grid; gap: 15px;">
            <!-- Campo DNI -->
            <div>
                <label for="clienteHabitualDni"
                    style="display: block; margin-bottom: 5px; font-weight: 500; font-size: 0.9rem;">DNI <span
                        style="color: #ef4444;">*</span></label>
                <input type="text" id="clienteHabitualDni"
                    style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;"
                    placeholder="12345678A" maxlength="20">
            </div>

            <!-- Campo Nombre -->
            <div>
                <label for="clienteHabitualNombre"
                    style="display: block; margin-bottom: 5px; font-weight: 500; font-size: 0.9rem;"><?php echo t('new_client.name'); ?>
                    <span style="color: #ef4444;">*</span></label>
                <input type="text" id="clienteHabitualNombre"
                    style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;"
                    placeholder="Juan" maxlength="100">
            </div>

            <!-- Campo Apellidos -->
            <div>
                <label for="clienteHabitualApellidos"
                    style="display: block; margin-bottom: 5px; font-weight: 500; font-size: 0.9rem;"><?php echo t('new_client.surname'); ?>
                    <span style="color: #ef4444;">*</span></label>
                <input type="text" id="clienteHabitualApellidos"
                    style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;"
                    placeholder="García López" maxlength="150">
            </div>

            <!-- Campo Dirección -->
            <div>
                <label for="clienteHabitualDireccion"
                    style="display: block; margin-bottom: 5px; font-weight: 500; font-size: 0.9rem;"><?php echo t('new_client.address'); ?></label>
                <input type="text" id="clienteHabitualDireccion"
                    style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;"
                    placeholder="Calle Principal 123" maxlength="255">
            </div>

            <!-- Campo Fecha de Alta -->
            <div>
                <label for="clienteHabitualFecha"
                    style="display: block; margin-bottom: 5px; font-weight: 500; font-size: 0.9rem;"><?php echo t('new_client.created_at'); ?></label>
                <input type="datetime-local" id="clienteHabitualFecha"
                    style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;" disabled>
            </div>
        </div>

        <!-- Botones: Cancelar y Guardar -->
        <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 25px;">
            <button class="btn-modal-cancelar"
                onclick="cerrarModal('modalClienteHabitual')"><?php echo t('new_client.cancel'); ?></button>
            <button class="btn-exito" id="btnGuardarClienteHabitual"
                style="margin: 0;"><?php echo t('new_client.save'); ?></button>
        </div>
    </div>
</div>

<!-- ##=========================== MODAL: PUNTOS (venta > 20€) ===========================## -->
<!-- Modal que aparece cuando el total de la venta es >= 20€ y no se ha especificado cliente -->
<div class="modal-overlay" id="modalPuntos" style="display:none;">
    <div class="modal-content" style="max-width: 400px; text-align: left;">
        <h3 style="margin-bottom: 5px;"><?php echo t('points_modal.title'); ?></h3>
        <p class="modal-subtitulo" style="margin-bottom: 20px;"><?php echo t('points_modal.subtitle'); ?></p>

        <div
            style="background: #f0fdf4; border: 1px solid #22c55e; border-radius: 8px; padding: 15px; margin-bottom: 20px; text-align: center;">
            <span style="font-size: 1.2rem; color: #166534;"><?php echo t('points_modal.can_earn'); ?> </span>
            <span id="puntosPosibles" style="font-size: 1.5rem; font-weight: bold; color: #15803d;">0</span>
            <span style="font-size: 1.2rem; color: #166534;"> <?php echo t('points_modal.points'); ?></span>
        </div>

        <p style="color: #6b7280; font-size: 0.9rem; margin-bottom: 20px;">
            <?php echo t('points_modal.explanation'); ?>
        </p>

        <div style="display: flex; gap: 10px;">
            <button class="btn-modal-cancelar" onclick="confirmarSinPuntos()" style="flex: 1;">
                <?php echo t('points_modal.not_now'); ?>
            </button>
            <button class="btn-exito" onclick="confirmarConPuntos()" style="flex: 1;">
                <?php echo t('points_modal.enter_dni'); ?>
            </button>
        </div>
    </div>
</div>

<!-- ##=========================== MODAL: PUNTOS CLIENTE (consultar/canjear) ===========================## -->
<!-- Modal para consultar los puntos de un cliente y canjearlos por descuento -->
<div class="modal-overlay" id="modalPuntosCliente" style="display:none;">
    <div class="modal-content" style="max-width: 450px; text-align: left;">
        <h3 style="margin-bottom: 5px;"><?php echo t('points_client.title'); ?></h3>
        <p class="modal-subtitulo" style="margin-bottom: 20px;"><?php echo t('points_client.subtitle'); ?></p>

        <!-- Buscar cliente por DNI -->
        <div id="puntosClienteBusqueda">
            <div>
                <label for="dniPuntosCliente"
                    style="display: block; margin-bottom: 5px; font-weight: 500; font-size: 0.9rem;"><?php echo t('points_client.dni'); ?>
                    <span style="color: #ef4444;">*</span></label>
                <input type="text" id="dniPuntosCliente"
                    style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;"
                    placeholder="12345678A" maxlength="20" onkeypress="if(event.key==='Enter') buscarPuntosCliente()">
            </div>
            <div id="mensajePuntosCliente" style="margin-top: 15px; padding: 10px; border-radius: 6px; display: none;">
            </div>
            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button class="btn-modal-cancelar" onclick="cerrarModal('modalPuntosCliente')" style="flex: 1;">
                    <?php echo t('points_client.close'); ?>
                </button>
                <button class="btn-exito" onclick="buscarPuntosCliente()" style="flex: 1;">
                    <?php echo t('points_client.search'); ?>
                </button>
            </div>
        </div>

        <!-- Mostrar puntos del cliente (se muestra después de buscar) -->
        <div id="puntosClienteInfo" style="display: none;">
            <div
                style="background: #f0fdf4; border: 1px solid #22c55e; border-radius: 8px; padding: 20px; margin-bottom: 20px; text-align: center;">
                <p style="color: #6b7280; margin-bottom: 5px;"><?php echo t('points_client.available_points'); ?></p>
                <span id="puntosDisponiblesCliente"
                    style="font-size: 2.5rem; font-weight: bold; color: #15803d;">0</span>
                <p style="color: #6b7280; margin-top: 5px;"><?php echo t('points_client.conversion_rule'); ?></p>
            </div>

            <!-- Información de puntos que se pueden usar y ganar -->
            <div id="infoPointsPanel"
                style="background: #eff6ff; border: 1px solid #3b82f6; border-radius: 8px; padding: 15px; margin-bottom: 15px; font-size: 0.9rem;">
                <p id="puntosQueSePuedenUsar" style="color: #1e40af; margin-bottom: 5px;"></p>
                <p id="puntosQueSeGanaran" style="color: #059669; margin-bottom: 0;"></p>
            </div>

            <div>
                <label for="puntosACanjeer"
                    style="display: block; margin-bottom: 5px; font-weight: 500; font-size: 0.9rem;"><?php echo t('points_client.points_to_redeem'); ?></label>
                <input type="number" id="puntosACanjeer"
                    style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;" placeholder="0"
                    min="0" step="1000" oninput="calcularDescuentoPuntos()">
                <p id="descuentoPuntosPreview" style="color: #10b981; font-weight: 600; margin-top: 10px;"></p>
            </div>

            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button class="btn-modal-cancelar" onclick="cerrarYLimpiarClientePuntos()" style="flex: 1;">
                    <?php echo t('points_client.cancel'); ?>
                </button>
                <button class="btn-exito" onclick="acumularPuntosSolamente()" style="flex: 1; background: #3b82f6;">
                    <?php echo t('points_client.accumulate'); ?>
                </button>
                <button class="btn-exito" id="btnAplicarDescuentoPuntos" onclick="aplicarDescuentoPuntos()"
                    style="flex: 1;">
                    <?php echo t('points_client.apply_discount'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ##=========================== MODAL: BUSCAR CLIENTE REGISTRADO ===========================## -->
<!-- Modal para buscar un cliente registrado por DNI y aplicar descuento según tarifa -->
<div class="modal-overlay" id="modalBuscarClienteRegistrado" style="display:none;" data-modo="">
    <div class="modal-content" style="max-width: 400px; text-align: left;">
        <h3 style="margin-bottom: 5px;"><?php echo t('search_client.title'); ?></h3>
        <p class="modal-subtitulo" style="margin-bottom: 20px;"><?php echo t('search_client.subtitle'); ?></p>

        <div>
            <label for="dniBusquedaCliente"
                style="display: block; margin-bottom: 5px; font-weight: 500; font-size: 0.9rem;"><?php echo t('search_client.dni'); ?>
                <span style="color: #ef4444;">*</span></label>
            <input type="text" id="dniBusquedaCliente"
                style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;"
                placeholder="12345678A" maxlength="20" onkeypress="if(event.key==='Enter') buscarClienteRegistrado()">
        </div>

        <div id="mensajeResultadoBusqueda" style="margin-top: 15px; padding: 10px; border-radius: 6px; display: none;">
        </div>

        <div style="display: flex; gap: 10px; margin-top: 20px;">
            <button class="btn-modal-cancelar" onclick="cerrarModalBuscarClienteRegistrado()" style="flex: 1;">
                <?php echo t('search_client.cancel'); ?>
            </button>
            <button class="btn-exito" onclick="buscarClienteRegistrado()" style="flex: 1;">
                <?php echo t('search_client.search'); ?>
            </button>
        </div>
    </div>
</div>

<!-- ##=========================== MODAL: VENTA EXITOSA ===========================## -->
<!-- Se muestra automáticamente cuando $_SESSION['ventaExito'] es true -->
<!-- Contiene: resumen de la venta, botones para imprimir y enviar por correo -->
<?php if (isset($_SESSION['ventaExito']) && $_SESSION['ventaExito']): ?>
    <?php
    $styleVentaExito = (isset($_SESSION['mostrarModalPuntosPostVenta']) && $_SESSION['mostrarModalPuntosPostVenta']) ? 'display: none;' : '';
    ?>
    <div class="modal-overlay" id="ventaExito" style="<?php echo $styleVentaExito; ?>">
        <div class="modal-content modal-exito">
            <!-- Icono de check/éxito animado -->
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icono-exito">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                <polyline points="22 4 12 14.01 9 11.01"></polyline>
            </svg>
            <h3><?php echo t('sale_success.title'); ?></h3>

            <!-- Detalle de la venta: tipo de documento, número y total -->
            <p class="exito-detalle">
                <?php echo ($_SESSION['ultimaVentaTipo'] === 'factura') ? t('sale_success.invoice') : t('sale_success.ticket'); ?>
                #<?php
                $serie = $_SESSION['ultimaVentaSerie'] ?? 'T';
                $numero = $_SESSION['ultimaVentaNumero'] ?? $_SESSION['ultimaVentaId'];
                echo $serie . str_pad($numero, 5, '0', STR_PAD_LEFT);
                ?> — <?php echo t('sale_success.total'); ?>:
                <?php echo number_format($_SESSION['ultimaVentaTotal'] ?? 0, 2, ',', '.'); ?> €
            </p>

            <!-- Puntos ganados (si el cliente estaba registrado y ganó puntos) -->
            <?php if (isset($_SESSION['ultimaVentaPuntosGanados']) && $_SESSION['ultimaVentaPuntosGanados'] > 0): ?>
                <div
                    style="background: #f0fdf4; border: 1px solid #22c55e; border-radius: 8px; padding: 15px; margin: 15px 0; text-align: center;">
                    <span style="font-size: 1.1rem; color: #166534;"><?php echo t('sale_success.points_earned'); ?> </span>
                    <span
                        style="font-size: 1.4rem; font-weight: bold; color: #15803d;"><?php echo number_format($_SESSION['ultimaVentaPuntosGanados'], 0, ',', '.'); ?>
                        <?php echo t('sale_success.points'); ?></span>
                    <br><small style="color: #166534;"><?php echo t('sale_success.points_info'); ?></small>
                </div>
            <?php endif; ?>

            <!-- Puntos canjeados (si el cliente usó puntos para descuento) -->
            <?php if (isset($_SESSION['ultimaVentaPuntosCanjeados']) && $_SESSION['ultimaVentaPuntosCanjeados'] > 0): ?>
                <div
                    style="background: #fef3c7; border: 1px solid #f59e0b; border-radius: 8px; padding: 15px; margin: 15px 0; text-align: center;">
                    <span style="font-size: 1.1rem; color: #92400e;"><?php echo t('sale_success.points_redeemed'); ?> </span>
                    <span
                        style="font-size: 1.4rem; font-weight: bold; color: #b45309;"><?php echo number_format($_SESSION['ultimaVentaPuntosCanjeados'], 0, ',', '.'); ?>
                        <?php echo t('sale_success.points'); ?></span>
                    <span style="font-size: 1rem; color: #92400e;">
                        (<?php echo number_format($_SESSION['ultimaVentaDescuentoValor'] ?? 0, 2, ',', '.'); ?>€
                        <?php echo t('sale_success.of_discount'); ?>)</span>
                </div>
            <?php endif; ?>

            <!-- Botones de acción post-venta (Ocultos: ahora se configuran en el checkout) -->
            <div class="exito-acciones" style="display: none;">
                <!-- Botón IMPRIMIR: genera el documento en una ventana de impresión -->
                <button class="btn-exito btn-imprimir" onclick="imprimirDocumento()">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 9 6 2 18 2 18 9"></polyline>
                        <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2">
                        </path>
                        <polyline points="6 14 6 22 18 22 18 14"></polyline>
                        <path d="M6 18h12"></path>
                    </svg>
                    <?php echo t('sale_success.print'); ?>
                </button>

                <!-- Botón ENVIAR POR CORREO: muestra el formulario de email -->
                <button class="btn-exito btn-email" onclick="mostrarFormEmail()">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z">
                        </path>
                        <polyline points="22,6 12,13 2,6"></polyline>
                    </svg>
                    <?php echo t('sale_success.send_email'); ?>
                </button>
            </div>

            <!-- Formulario de envío por email (oculto por defecto, se muestra al pulsar "Enviar por correo") -->
            <div class="form-email" id="formEmail" style="display:none;">
                <label for="inputEmail"><?php echo t('sale_success.email_label'); ?></label>
                <div class="email-input-group">
                    <input type="email" id="inputEmail" placeholder="<?php echo t('sale_success.email_placeholder'); ?>" />
                    <button class="btn-enviar-email"
                        onclick="enviarPorCorreo()"><?php echo t('sale_success.send'); ?></button>
                </div>
                <!-- Estado del envío: enviando, éxito o error -->
                <p class="email-status" id="emailStatus"></p>
            </div>

            <!-- Botón para cerrar el modal de éxito -->
            <button class="btn-cerrar-exito" onclick="cerrarExito()"><?php echo t('sale_success.accept'); ?></button>
        </div>
    </div>

    <!-- Script: datos de la última venta pasados de PHP a JavaScript -->
    <!-- Se usa para las funciones de impresión y envío por correo -->
    <script>
        let ultimaVenta = {
            id: <?php echo $_SESSION['ultimaVentaId'] ?? 'null'; ?>,
            serie: '<?php echo $_SESSION['ultimaVentaSerie'] ?? 'T'; ?>',
            numero: <?php echo $_SESSION['ultimaVentaNumero'] ?? 'null'; ?>,
            total: '<?php echo number_format($_SESSION['ultimaVentaTotal'] ?? 0, 2, ',', '.'); ?>',       // Total formateado
            tipo: '<?php echo $_SESSION['ultimaVentaTipo'] ?? 'ticket'; ?>',                                     // 'ticket' o 'factura'
            carrito: <?php echo $_SESSION['ultimaVentaCarrito'] ?? '[]'; ?>,                                  // Array de productos (JSON)
            metodoPago: '<?php echo $_SESSION['ultimaVentaMetodoPago'] ?? 'efectivo'; ?>',                         // Método de pago usado
            fecha: '<?php echo $_SESSION['ultimaVentaFecha'] ?? ''; ?>',                                   // Fecha de la venta
            entregado: '<?php echo number_format($_SESSION['ultimaVentaEntregado'] ?? ($_SESSION['ultimaVentaTotal'] ?? 0), 2, ',', '.'); ?>', // Dinero entregado
            cambio: '<?php echo number_format($_SESSION['ultimaVentaCambio'] ?? 0, 2, ',', '.'); ?>', // Cambio devuelto
            clienteNif: '<?php echo addslashes($_SESSION['ultimaVentaClienteNif'] ?? ''); ?>',       // NIF del cliente
            clienteNombre: '<?php echo addslashes($_SESSION['ultimaVentaClienteNombre'] ?? ''); ?>', // Nombre del cliente
            clienteDir: '<?php echo addslashes($_SESSION['ultimaVentaClienteDir'] ?? ''); ?>',       // Dirección del cliente
            clienteObs: '<?php echo addslashes($_SESSION['ultimaVentaClienteObs'] ?? ''); ?>',       // Observaciones
            descuentoTipo: '<?php echo $_SESSION['ultimaVentaDescuentoTipo'] ?? 'ninguno'; ?>',      // Tipo de descuento
            descuentoValor: <?php echo $_SESSION['ultimaVentaDescuentoValor'] ?? 0; ?>,              // Valor del descuento
            descuentoCupon: '<?php echo $_SESSION['ultimaVentaDescuentoCupon'] ?? ''; ?>',           // Código de cupón usado
            descuentoTarifaTipo: '<?php echo $_SESSION['ultimaVentaDescuentoTarifaTipo'] ?? 'ninguno'; ?>',      // Tipo de descuento de tarifa
            descuentoTarifaValor: <?php echo $_SESSION['ultimaVentaDescuentoTarifaValor'] ?? 0; ?>,              // Valor del descuento de tarifa
            descuentoTarifaCupon: '<?php echo $_SESSION['ultimaVentaDescuentoTarifaCupon'] ?? ''; ?>',           // Código de cupón de tarifa (CLIENTE_REGISTRADO, MAYORISTA_NIVEL1, MAYORISTA_NIVEL2)
            descuentoManualTipo: '<?php echo $_SESSION['ultimaVentaDescuentoManualTipo'] ?? 'ninguno'; ?>',      // Tipo de descuento manual
            descuentoManualValor: <?php echo $_SESSION['ultimaVentaDescuentoManualValor'] ?? 0; ?>,            // Valor del descuento manual
            descuentoManualCupon: '<?php echo addslashes($_SESSION['ultimaVentaDescuentoManualCupon'] ?? ''); ?>',
            puntosGanados: <?php echo $_SESSION['ultimaVentaPuntosGanados'] ?? 0; ?>,
            puntosBalance: <?php echo $_SESSION['ultimaVentaPuntosBalance'] ?? 0; ?>,
            puntosCanjeados: <?php echo (isset($_SESSION['ultimaVentaPuntosCanjeados']) && $_SESSION['ultimaVentaPuntosCanjeados'] > 0) ? '{ puntos: ' . ($_SESSION['ultimaVentaPuntosCanjeados']) . ', descuento: ' . ($_SESSION['ultimaVentaDescuentoValor'] ?? 0) . ' }' : 'null'; ?>,
            pagoMixtoDesglose: <?php echo !empty($_SESSION['ultimaVentaDesglosePago']) ? $_SESSION['ultimaVentaDesglosePago'] : 'null'; ?>,
            mensajePersonalizado: '<?php echo addslashes($_SESSION['ultimaVentaMensajePersonalizado'] ?? ''); ?>'
        };
    </script>

    <script>
        // EJECUCIÓN AUTOMÁTICA POST-VENTA (Impresión o Email según checkout)
        window.addEventListener('DOMContentLoaded', () => {
            const postSaleConfig = localStorage.getItem('tpv_post_sale_action');
            if (postSaleConfig) {
                const config = JSON.parse(postSaleConfig);
                localStorage.removeItem('tpv_post_sale_action'); // Limpiar para que no se repita

                // Pequeño retardo para asegurar que el modal y JS de ticket están listos
                setTimeout(() => {
                    if (config.imprimir) {
                        console.log('Post-venta: Ejecutando impresión automática...');
                        imprimirDocumento();
                    } else if (config.email && config.emailDestino) {
                        console.log('Post-venta: Ejecutando envío de email automático a', config.emailDestino);
                        const inputEmail = document.getElementById('inputEmail');
                        if (inputEmail) {
                            inputEmail.value = config.emailDestino;
                            enviarPorCorreo();
                        }
                    }
                }, 800);
            }
        });
    </script>
    <?php
    // Limpiar todas las variables de sesión de la última venta para evitar que se muestren de nuevo
    unset($_SESSION['ventaExito']);
    unset($_SESSION['ultimaVentaId']);
    unset($_SESSION['ultimaVentaSerie']);
    unset($_SESSION['ultimaVentaNumero']);
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
    unset($_SESSION['ultimaVentaDescuentoTarifaTipo']);
    unset($_SESSION['ultimaVentaDescuentoTarifaValor']);
    unset($_SESSION['ultimaVentaDescuentoTarifaCupon']);
    unset($_SESSION['ultimaVentaDescuentoManualTipo']);
    unset($_SESSION['ultimaVentaDescuentoManualValor']);
    unset($_SESSION['ultimaVentaDescuentoManualCupon']);
    unset($_SESSION['puntosGanados']);
    unset($_SESSION['ultimaVentaPuntosGanados']);
    unset($_SESSION['ultimaVentaPuntosCanjeados']);
?>
<?php
endif; ?>

<!-- ##=========================== MODAL: ABRIR CAJA ===========================## -->
<!-- Modal para iniciar una nueva sesión de caja introduciendo el fondo de caja inicial -->
<!-- Se envía por POST con la acción "abrirCaja" al controlador -->
<div class="modal-overlay" id="modalAbrirCaja" style="display:none;">
    <div class="modal-content modal-premium" style="max-width: 450px;">
        <!-- Cabecera con gradiente verde y icono de candado -->
        <div class="modal-header-premiummodal-header-green">
            <div class="icon-container-discount">
                <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none"
                    stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
            </div>
            <h3><?php echo t('open_register.title'); ?></h3>
            <p><?php echo t('open_register.subtitle'); ?></p>
        </div>

        <div class="modal-body-premium">
            <!-- Formulario de apertura de caja -->
            <form id="formAbrirCaja" method="POST" action="index.php">
                <input type="hidden" name="accion" value="abrirCaja">
                <input type="hidden" name="cambioRecovery" id="cambioRecovery" value="0">

                <?php if ($cambioAnterior > 0): ?>
                    <!-- Opción para recuperar cambio anterior -->
                    <div class="resumen-caja-container" style="margin-bottom: 15px; border-color: #10b981;">
                        <label style="display: flex; align-items: center; cursor: pointer;">
                            <input type="radio" name="opcionCambio" value="recuperar" checked onclick="toggleCambio(false)"
                                style="margin-right: 8px;">
                            <span>💰 <?php echo t('open_register.recover_change'); ?>
                                <strong><?php echo number_format($cambioAnterior, 2, ',', '.'); ?> €</strong></span>
                        </label>
                    </div>
                    <div class="resumen-caja-container" style="margin-bottom: 15px;">
                        <label style="display: flex; align-items: center; cursor: pointer;">
                            <input type="radio" name="opcionCambio" value="nuevo" onclick="toggleCambio(true)"
                                style="margin-right: 8px;">
                            <span>✨ <?php echo t('open_register.new_change'); ?></span>
                        </label>
                    </div>
                    <?php
                else: ?>
                    <input type="radio" name="opcionCambio" value="nuevo" checked style="display:none;">
                    <?php
                endif; ?>

                <!-- Input para el importe inicial (fondo de caja) -->
                <div class="form-group-premium" id="divImporteInicial"
                    style="<?php echo ($cambioAnterior > 0) ? 'opacity: 0.5;' : ''; ?>">
                    <label for="importeInicial"><?php echo t('open_register.initial_cash'); ?></label>
                    <div class="input-cupon">
                        <input type="number" name="importeInicial" id="importeInicial" step="0.0001"
                            oninput="validar4Decimales(this)" onblur="validar4Decimales(this)" min="0"
                            placeholder="0,00" <?php echo ($cambioAnterior > 0) ? '' : 'required'; ?>
                            style="text-align: center; padding-right: 15px;">
                    </div>
                </div>

                <!-- Botones: Cancelar y Confirmar Apertura -->
                <div style="display: flex; gap: 15px; margin-top: 30px;">
                    <button type="button" class="btn-modal-cancelar" onclick="cerrarModal('modalAbrirCaja')"
                        style="flex: 1;"><?php echo t('open_register.cancel'); ?></button>
                    <button type="submit" class="btn-apply-premium"
                        style="flex: 1; background: #16a34a; color: white;"><?php echo t('open_register.confirm'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ##=========================== MODAL: DEVOLUCIÓN (REEMBOLSO) ===========================## -->
<!-- Modal para tramitar la devolución de un producto -->
<!-- Permite buscar un producto, seleccionar cantidad y método de devolución -->
<!-- ##=========================== MODAL: DEVOLUCIÓN (REEMBOLSO) ===========================## -->
<!-- Modal rediseñado para devoluciones con verificación de ticket -->
<div class="modal-overlay" id="modalDevolucion" style="display:none;">
    <div class="modal-content modal-premium"
        style="max-width: 700px; display: flex; flex-direction: column; max-height: 90vh;">
        <!-- Cabecera con gradiente rojo -->
        <div class="modal-header-premium modal-header-red" style="flex-shrink: 0;">
            <div class="icon-container-discount">
                <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none"
                    stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M1 4v6h6"></path>
                    <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
                </svg>
            </div>
            <h3 style="margin-bottom: 5px;"><?php echo t('return.title'); ?></h3>
            <p id="devolucionSubtitulo" style="opacity: 0.9;"><?php echo t('return.subtitle'); ?></p>
        </div>

        <div class="modal-body-premium" style="flex: 1; overflow-y: auto; padding: 25px;">
            <!-- PASO 1: Búsqueda de Ticket -->
            <div id="devolucionPaso1">
                <div style="text-align: center; padding: 20px 0;">
                    <!-- Icono representativo grande -->
                    <div
                        style="background: rgba(220, 38, 38, 0.1); width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 25px; color: var(--accent-danger); border: 2px solid rgba(220, 38, 38, 0.2); box-shadow: 0 8px 16px -4px rgba(220, 38, 38, 0.15);">
                        <i class="fas fa-receipt" style="font-size: 2.2rem;"></i>
                    </div>

                    <h4 style="color: var(--text-main); font-size: 1.4rem; margin-bottom: 10px; font-weight: 700;">
                        <?php echo t('return.step1_title'); ?>
                    </h4>
                    <p
                        style="color: var(--text-muted); font-size: 0.95rem; margin-bottom: 30px; max-width: 400px; margin-left: auto; margin-right: auto;">
                        <?php echo t('return.step1_desc'); ?>
                    </p>

                    <div style="max-width: 340px; margin: 0 auto;">
                        <input type="text" id="inputTicketIdDev"
                            placeholder="<?php echo t('return.ticket_placeholder'); ?>"
                            style="width: 100%; padding: 18px; font-size: 1.6rem; text-align: center; border-radius: 14px; border: 2px solid var(--border-main); background: var(--bg-input); color: var(--text-main); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); transition: all 0.2s; outline: none; font-weight: 600;"
                            onkeypress="if(event.key === 'Enter') buscarTicketParaDevolucion()"
                            onfocus="this.style.borderColor = 'var(--accent-danger)'; this.style.boxShadow = '0 0 0 4px rgba(220, 38, 38, 0.1)'; this.style.transform = 'translateY(-2px)'"
                            onblur="this.style.borderColor = 'var(--border-main)'; this.style.boxShadow = '0 4px 6px -1px rgba(0,0,0,0.05)'; this.style.transform = 'translateY(0)'">

                        <button type="button" class="btn-apply-premium" onclick="buscarTicketParaDevolucion()"
                            style="width: 100%; background: var(--accent-danger); color: white; margin-top: 20px; padding: 16px; border-radius: 14px; font-size: 1.1rem; font-weight: 700; border: none; box-shadow: 0 6px 15px rgba(220, 38, 38, 0.3); transition: all 0.2s; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px;">
                            <span><?php echo t('return.continue'); ?></span>
                            <i class="fas fa-arrow-right" style="font-size: 0.9rem;"></i>
                        </button>
                    </div>

                    <p id="errorTicketDev"
                        style="color: var(--accent-danger); font-size: 0.85rem; margin-top: 20px; font-weight: 600; display: none; background: rgba(220, 38, 38, 0.05); padding: 10px; border-radius: 8px;">
                    </p>

                    <div
                        style="margin-top: 45px; padding: 15px 20px; background: var(--bg-panel); border-radius: 12px; border: 1px dashed var(--border-main); display: inline-flex; align-items: center; gap: 12px; opacity: 0.8;">
                        <i class="fas fa-info-circle" style="color: var(--accent-danger);"></i>
                        <span style="color: var(--text-muted); font-size: 0.85rem; font-weight: 500;">
                            <?php echo t('return.ticket_info'); ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- PASO 2: Selección de Productos -->
            <div id="devolucionPaso2" style="display: none;">

                <!-- Lista de productos a devolver (REQUIRED ID) -->
                <div id="listaProductosDevolucion" style="margin-top: 20px; margin-bottom: 20px;"></div>
                <div
                    style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; padding: 15px; background: var(--bg-accent-danger); border-radius: 12px; border: 1px solid var(--accent-danger); opacity: 0.9;">
                    <div style="flex: 1;">
                        <div style="font-weight: 700; color: var(--text-main); font-size: 1rem; margin-bottom: 5px;">
                            <?php echo t('return.ticket_info_title'); ?>
                        </div>
                        <div style="font-size: 0.9rem; color: var(--text-muted);">
                            <span id="infoTicketId" style="font-weight: 600;"></span> ·
                            <span id="infoTicketFecha"></span>
                        </div>
                    </div>
                    <div style="text-align: right; display: flex; gap: 20px; align-items: center;">
                        <button type="button" onclick="seleccionarTodosProductos()"
                            style="background: var(--bg-panel); color: var(--text-main); border: 1px solid var(--border-main); padding: 8px 16px; border-radius: 8px; font-size: 0.85rem; font-weight: 600; cursor: pointer; transition: all 0.2s;">
                            <i class="fas fa-check-double" style="margin-right: 6px;"></i>
                            <?php echo t('return.select_all'); ?>
                        </button>
                        <div style="text-align: right;">
                            <div
                                style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em;">
                                <?php echo t('return.original_total'); ?>
                            </div>
                            <div id="infoTicketTotal"
                                style="font-size: 1.2rem; font-weight: 800; color: var(--accent-danger);"></div>
                        </div>
                    </div>
                </div>

                <div
                    style="border: 1px solid var(--border-main); border-radius: 12px; overflow: hidden; margin-bottom: 25px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); max-height: 300px; overflow-y: auto;">
                    <table class="tabla-productos-devolucion" style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: var(--bg-panel); border-bottom: 2px solid var(--border-main);">
                                <th
                                    style="text-align: left; padding: 12px 15px; font-weight: 700; font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase;">
                                    <?php echo t('return.product_col'); ?>
                                </th>
                                <th
                                    style="text-align: center; padding: 12px; font-weight: 700; font-size: 0.8rem; color: #64748b; text-transform: uppercase; width: 80px;">
                                    <?php echo t('return.disp_col'); ?>
                                </th>
                                <th
                                    style="text-align: center; padding: 12px 15px; font-weight: 700; font-size: 0.8rem; color: #64748b; text-transform: uppercase; width: 120px;">
                                    <?php echo t('return.to_return_col'); ?>
                                </th>
                            </tr>
                        </thead>
                        <tbody id="tablaProductosDev">
                            <!-- Se rellena dinámicamente -->
                        </tbody>
                    </table>
                </div>

                <div class="form-group-premium" style="margin-top: 25px;">
                    <label style="font-size: 1rem;"><?php echo t('return.refund_method'); ?></label>
                    <div style="display: flex; gap: 12px; margin-top: 10px;">
                        <label style="flex: 1; cursor: pointer;">
                            <input type="radio" name="metodoPagoDev" value="Efectivo" checked style="display: none;"
                                onchange="updateMethodUI(this)">
                            <div class="method-chip active" id="chip-Efectivo"
                                style="padding: 12px; text-align: center; border-radius: 10px; border: 2px solid var(--accent-danger); color: var(--accent-danger); font-weight: 700; transition: all 0.2s;">
                                <i class="fas fa-money-bill-wave" style="margin-right: 8px;"></i>
                                <?php echo t('ticket.cash'); ?>
                            </div>
                        </label>
                        <label style="flex: 1; cursor: pointer;">
                            <input type="radio" name="metodoPagoDev" value="Tarjeta" style="display: none;"
                                onchange="updateMethodUI(this)">
                            <div class="method-chip" id="chip-Tarjeta"
                                style="padding: 12px; text-align: center; border-radius: 10px; border: 2px solid var(--border-main); color: var(--text-muted); font-weight: 600; transition: all 0.2s;">
                                <i class="fas fa-credit-card" style="margin-right: 8px;"></i>
                                <?php echo t('ticket.card'); ?>
                            </div>
                        </label>
                        <label style="flex: 1; cursor: pointer;">
                            <input type="radio" name="metodoPagoDev" value="Bizum" style="display: none;"
                                onchange="updateMethodUI(this)">
                            <div class="method-chip" id="chip-Bizum"
                                style="padding: 12px; text-align: center; border-radius: 10px; border: 2px solid var(--border-main); color: var(--text-muted); font-weight: 600; transition: all 0.2s;">
                                <i class="fas fa-mobile-alt" style="margin-right: 8px;"></i>
                                <?php echo t('ticket.bizum'); ?>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="form-group-premium" style="margin-top: 20px;">
                    <label style="font-size: 1rem;"><?php echo t('return.reason'); ?></label>
                    <textarea id="motivoDevolucionDev" placeholder="<?php echo t('return.reason_placeholder'); ?>"
                        style="width: 100%; border-radius: 8px; border: 1px solid var(--border-main); padding: 12px; background: var(--bg-input); color: var(--text-main); height: 80px; resize: vertical; margin-top: 5px; font-family: inherit; font-size: 0.9rem;"></textarea>
                </div>
            </div>
        </div>

        <!-- Footer siempre visible -->
        <div class="modal-footer-premium"
            style="flex-shrink: 0; background: var(--bg-panel); border-top: 1px solid var(--border-main); display: flex; justify-content: space-between; align-items: center; padding: 20px 30px;">
            <div id="resumenReembolso" style="display: none;">
                <div style="display: flex; gap: 30px; align-items: flex-end;">
                    <div>
                        <span
                            style="display: block; font-size: 0.75rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase; margin-bottom: 2px;">Total
                            <?php echo t('return.original_sale_total'); ?></span>
                        <span id="totalOriginalDisplay"
                            style="font-size: 1.2rem; font-weight: 600; color: var(--text-main);">0,00 €</span>
                    </div>
                    <div>
                        <span
                            style="display: block; font-size: 0.75rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase; margin-bottom: 2px;">Total
                            <?php echo t('return.refund_total'); ?></span>
                        <span id="totalReembolsoDisplay"
                            style="font-size: 1.6rem; font-weight: 800; color: var(--accent-danger);">0,00 €</span>
                    </div>
                </div>
                <div id="errorEfectivoInsuficiente"
                    style="display: none; color: var(--accent-danger); font-size: 0.8rem; font-weight: 600; margin-top: 5px; background: rgba(220, 38, 38, 0.1); padding: 5px 10px; border-radius: 6px;">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo t('return.insufficient_cash'); ?>
                    (<?php echo t('return.available'); ?>: <span id="efectivoDisponibleDisplay"></span>)
                </div>
            </div>
            <div style="margin-left: auto; display: flex; gap: 15px;">
                <button type="button" class="btn-cancel-flat" onclick="cerrarModalDevolucion()"
                    style="padding: 10px 20px;"><?php echo t('return.cancel'); ?></button>
                <button type="button" class="btn-apply-premium" id="btnConfirmarMultiDev" disabled
                    style="background: var(--accent-danger); color: white; margin: 0; padding: 12px 25px; border-radius: 10px; box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3); display: none;"
                    onclick="procesarMultiDevolucion()">
                    <?php echo t('return.confirm'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ##=========================== MODAL: DEVOLUCIÓN ÉXITO ===========================## -->
<!-- Se muestra automáticamente cuando $_SESSION['devolucionExito'] está definida -->
<!-- Confirma que la devolución se ha procesado correctamente -->
<?php
// Debug: mostrar si la sesión está definida
// var_dump(isset($_SESSION['devolucionExito']));
// var_dump($_SESSION['devolucionExito'] ?? 'no definido');
// var_dump($_SESSION['devolucionDetalles'] ?? 'no definido');
?>
<?php if (isset($_SESSION['devolucionExito']) && $_SESSION['devolucionExito'] === true): ?>
    <div class="modal-overlay" id="devolucionExito"
        style="display: flex !important; z-index: 99999; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6);">
        <div class="modal-content modal-exito modal-border-red devolucion-modal"
            style="max-width: 450px; border-radius: 12px; padding: 25px; margin: auto; box-shadow: 0 10px 25px rgba(0,0,0,0.5);">
            <!-- Icono de devolución en fondo rojo claro -->
            <div style="text-align: center; margin-bottom: 15px;">
                <div
                    style="background: #fee2e2; width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none"
                        stroke="#dc2626" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1 4v6h6"></path>
                        <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
                    </svg>
                </div>
            </div>
            <h3 style="text-align: center; margin: 10px 0; font-size: 1.4rem;" class="devolucion-titulo">
                <?php echo t('return_success.title'); ?>
            </h3>
            <p style="text-align: center; margin: 10px 0;" class="devolucion-subtitulo">
                <?php echo t('return_success.subtitle'); ?>
            </p>
            <?php if (isset($_SESSION['devolucionDetalles']) && is_array($_SESSION['devolucionDetalles'])): ?>
                <div class="devolucion-detalles" style="border-radius: 8px; padding: 15px; margin: 15px 0; text-align: left;">
                    <p style="margin: 5px 0; font-size: 0.9rem;"><strong><?php echo t('return_success.ticket'); ?>:</strong>
                        #<?php echo htmlspecialchars($_SESSION['devolucionDetalles']['ticket'] ?? ''); ?></p>
                    <p style="margin: 5px 0; font-size: 0.9rem;">
                        <strong><?php echo t('return_success.returned_products'); ?>:</strong>
                        <?php echo htmlspecialchars($_SESSION['devolucionDetalles']['productos'] ?? ''); ?>
                    </p>
                    <?php if (!empty($_SESSION['devolucionDetalles']['motivo'])): ?>
                        <p style="margin: 5px 0; font-size: 0.9rem;"><strong><?php echo t('return_success.reason'); ?>:</strong>
                            <?php echo htmlspecialchars($_SESSION['devolucionDetalles']['motivo']); ?></p>
                        <?php
                    endif; ?>
                    <p style="margin: 5px 0; font-size: 0.9rem;">
                        <strong><?php echo t('return_success.refunded_amount'); ?>:</strong> <span
                            style="color: #f87171; font-weight: bold;">-<?php echo number_format($_SESSION['devolucionDetalles']['total'] ?? 0, 2, ',', '.'); ?>
                            €</span>
                    </p>
                </div>

                <div style="display: flex; gap: 10px; margin-bottom: 15px;">
                    <button onclick="imprimirTicketDevolucion()"
                        style="flex: 1; background: #3b82f6; color: white; padding: 10px; border: none; border-radius: 6px; cursor: pointer; font-size: 0.9rem;">
                        <i class="fas fa-print"></i> <?php echo t('sale_success.print'); ?>
                    </button>
                    <button onclick="mostrarFormEmailDevolucion()"
                        style="flex: 1; background: #10b981; color: white; padding: 10px; border: none; border-radius: 6px; cursor: pointer; font-size: 0.9rem;">
                        <i class="fas fa-envelope"></i> <?php echo t('sale_success.email'); ?>
                    </button>
                </div>

                <div id="formEmailDev" class="devolucion-email-form"
                    style="display: none; margin-bottom: 15px; padding: 15px; border-radius: 8px;">
                    <input type="email" id="inputEmailDev" placeholder="<?php echo t('sale_success.email_placeholder'); ?>"
                        style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid; margin-bottom: 10px;"
                        class="devolucion-email-input">
                    <button onclick="enviarPorCorreoDevolucion()"
                        style="width: 100%; background: #10b981; color: white; padding: 8px; border: none; border-radius: 6px; cursor: pointer;">
                        <?php echo t('sale_success.send'); ?>
                    </button>
                    <div id="emailStatusDev" style="margin-top: 10px; font-size: 0.85rem; text-align: center;"></div>
                </div>

                <script>
                    const ultimaDevolucion = {
                        id: '<?php echo $_SESSION['devolucionDetalles']['ticket'] ?? ""; ?>',
                        serie: '<?php echo $_SESSION['devolucionDetalles']['serie'] ?? "T"; ?>',
                        numero: '<?php echo $_SESSION['devolucionDetalles']['numero'] ?? ""; ?>',
                        fecha: '<?php echo $_SESSION['devolucionDetalles']['fecha'] ?? date("d/m/Y H:i"); ?>',
                        metodoPago: '<?php echo $_SESSION['devolucionDetalles']['metodoPago'] ?? "Efectivo"; ?>',
                        total: '<?php echo number_format($_SESSION['devolucionDetalles']['total'] ?? 0, 2, ".", ""); ?>',
                        motivo: '<?php echo addslashes($_SESSION['devolucionDetalles']['motivo'] ?? ""); ?>',
                        lineas: <?php echo json_encode($_SESSION['devolucionDetalles']['lineas'] ?? []); ?>
                    };
                </script>
                <?php unset($_SESSION['devolucionDetalles']); ?>
                <?php
            endif; ?>
            <div style="text-align: center; margin-top: 15px;">
                <button onclick="document.getElementById('devolucionExito').remove()"
                    style="background: #dc2626; color: white; padding: 10px 30px; border: none; border-radius: 6px; cursor: pointer; font-size: 1rem; width: 100%;"><?php echo t('sale_success.accept'); ?></button>
            </div>
        </div>
    </div>
    <?php
    // Limpiar la sesión después de mostrar
    unset($_SESSION['devolucionExito']);
?>
<?php
endif; ?>

<!-- ##=========================== MODAL: RETIRAR DINERO ===========================## -->
<!-- Modal para retirar efectivo de la caja (ej: pago a proveedor, ingreso en banco) -->
<!-- Se envía por POST con la acción "retirarDinero" al controlador -->
<div class="modal-overlay" id="modalRetiro" style="display:none;">
    <div class="modal-content modal-premium" style="max-width: 450px;">
        <!-- Cabecera con gradiente naranja y icono de billete -->
        <div class="modal-header-premium modal-header-orange">
            <div class="icon-container-discount">
                <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none"
                    stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect width="20" height="12" x="2" y="6" rx="2"></rect>
                    <circle cx="12" cy="12" r="2"></circle>
                    <path d="M6 12h.01M18 12h.01"></path>
                </svg>
            </div>
            <h3><?php echo t('withdraw.title'); ?></h3>
            <p><?php echo t('withdraw.subtitle'); ?></p>
        </div>

        <div class="modal-body-premium">
            <!-- Formulario de retiro de dinero -->
            <form id="formRetiro" method="POST" action="index.php" onsubmit="return validarRetiro()">
                <input type="hidden" name="accion" value="retirarDinero">

                <!-- Campo: cantidad a retirar en euros -->
                <div class="form-group-premium">
                    <label for="importeRetiro"><?php echo t('withdraw.amount'); ?></label>
                    <input type="number" name="importeRetiro" id="importeRetiro" step="0.0001" min="0.0001"
                        oninput="validar4Decimales(this)" onblur="validar4Decimales(this)" placeholder="0.00" required>
                    <small style="color: var(--text-muted); display: block; margin-top: 5px;">
                        <?php echo t('withdraw.available'); ?>: <span
                            id="efectivoDisponible"><?php echo number_format($sesionCaja ? $sesionCaja->getImporteActual() : 0, 2, ',', '.'); ?></span>
                        €
                    </small>
                </div>

                <!-- Campo: motivo del retiro (opcional) -->
                <div class="form-group-premium" style="margin-top: 15px;">
                    <label for="motivoRetiro"><?php echo t('withdraw.reason_optional'); ?></label>
                    <input type="text" name="motivoRetiro" id="motivoRetiro"
                        placeholder="<?php echo t('withdraw.reason_placeholder'); ?>">
                </div>

                <!-- Botones: Cancelar y Confirmar Retiro -->
                <div style="display: flex; gap: 15px; margin-top: 25px;">
                    <button type="button" class="btn-modal-cancelar" onclick="cerrarModal('modalRetiro')"
                        style="flex: 1;"><?php echo t('withdraw.cancel'); ?></button>
                    <button type="submit" class="btn-apply-premium"
                        style="flex: 1; background: #ea580c; color: white;"><?php echo t('withdraw.confirm'); ?></button>
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
        <div class="modal-content modal-exito modal-border-orange" style="max-width: 400px;">
            <!-- Icono de billete en fondo naranja claro -->
            <div class="icon-container-discount icon-bg-orange">
                <svg xmlns="http://www.w3.org/2000/svg" width="35" height="35" viewBox="0 0 24 24" fill="none"
                    stroke="#ea580c" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect width="20" height="12" x="2" y="6" rx="2"></rect>
                    <circle cx="12" cy="12" r="2"></circle>
                    <path d="M6 12h.01M18 12h.01"></path>
                </svg>
            </div>
            <h3 class="resumen-total-ventas" style="margin-top: 10px; border-top:none; padding-top:0;">
                <?php echo t('withdraw_success.title'); ?>
            </h3>
            <p class="modal-subtitulo-cliente"><?php echo t('withdraw_success.subtitle'); ?></p>
            <button class="btn-cerrar-exito" style="background: #ea580c; margin-top: 20px;"
                onclick="document.getElementById('retiroExito').remove()"><?php echo t('withdraw_success.accept'); ?></button>
        </div>
    </div>
    <?php unset($_SESSION['retiroExito']); ?>
<?php
endif; ?>

<!-- ##=========================== MODAL: RETIRO ERROR ===========================## -->
<!-- Se muestra automáticamente cuando $_SESSION['retiroError'] está definida -->
<!-- Muestra el mensaje de error del retiro fallido -->
<?php if (isset($_SESSION['retiroError'])): ?>
    <div class="modal-overlay" id="retiroError">
        <div class="modal-content modal-error-content modal-border-red" style="max-width: 400px;">
            <!-- Icono SVG de X/error en rojo -->
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none"
                stroke="var(--accent-danger)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                style="margin-bottom: 15px;">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="15" y1="9" x2="9" y2="15"></line>
                <line x1="9" y1="9" x2="15" y2="15"></line>
            </svg>
            <h3 style="color: var(--accent-danger);"><?php echo t('withdraw_error.title'); ?></h3>
            <!-- Mensaje de error escapado con htmlspecialchars para seguridad XSS -->
            <p style="color: var(--text-main); margin-bottom: 25px;">
                <?php echo htmlspecialchars($_SESSION['retiroError']); ?>
            </p>
            <button class="btn-modal-cancelar" onclick="document.getElementById('retiroError').remove()"
                style="width: 100%;"><?php echo t('withdraw_error.accept'); ?></button>
        </div>
    </div>
    <?php unset($_SESSION['retiroError']); ?>
<?php
endif; ?>

<!-- ##=========================== MODAL: ERROR ===========================## -->
<!-- Se muestra automáticamente cuando $_SESSION['ventaError'] está definida -->
<!-- Muestra el mensaje de error de la venta fallida -->
<?php if (isset($_SESSION['ventaError'])): ?>
    <div class="modal-overlay" id="ventaError">
        <div class="modal-content modal-error-content modal-border-red" style="max-width: 400px;">
            <!-- Icono SVG de X/error en rojo -->
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none"
                stroke="var(--accent-danger)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                style="margin-bottom: 15px;">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="15" y1="9" x2="9" y2="15"></line>
                <line x1="9" y1="9" x2="15" y2="15"></line>
            </svg>
            <h3 style="color: var(--accent-danger);"><?php echo t('sale_error.title'); ?></h3>
            <!-- Mensaje de error escapado con htmlspecialchars para seguridad XSS -->
            <p style="color: var(--text-main); margin-bottom: 25px;">
                <?php echo htmlspecialchars($_SESSION['ventaError']); ?>
            </p>
            <button class="btn-modal-cancelar" onclick="cerrarModal('ventaError')"
                style="width: 100%;"><?php echo t('sale_error.accept'); ?></button>
        </div>
    </div>
    <?php unset($_SESSION['ventaError']); ?>
<?php
endif; ?>

<!-- ##=========================== MODAL: PUNTOS POST VENTA ===========================## -->
<!-- Se muestra automáticamente cuando $_SESSION['mostrarModalPuntosPostVenta'] está definida -->
<?php if (isset($_SESSION['mostrarModalPuntosPostVenta']) && $_SESSION['mostrarModalPuntosPostVenta']): ?>
    <div class="modal-overlay" id="puntosPostVentaExito" style="display: flex !important; z-index: 99999;">
        <div class="modal-content modal-exito" style="max-width: 450px;">
            <!-- Icono de regalo -->
            <div style="text-align: center; margin-bottom: 15px;">
                <div
                    style="background: var(--bg-accent-success); width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none"
                        stroke="var(--accent-success)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 12 20 22 4 22 4 12"></polyline>
                        <rect x="2" y="7" width="20" height="5"></rect>
                        <line x1="12" y1="22" x2="12" y2="7"></line>
                        <path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"></path>
                        <path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"></path>
                    </svg>
                </div>
            </div>
            <h3 style="text-align: center; margin: 10px 0; font-size: 1.4rem; color: var(--text-header);">
                <?php echo t('points_earned.title'); ?>
            </h3>
            <p style="text-align: center; margin: 10px 0; color: var(--text-muted);">
                <?php echo t('points_earned.subtitle'); ?>
            </p>

            <div
                style="background: var(--bg-main); border: 1px solid var(--border-main); border-radius: 8px; padding: 15px; margin: 15px 0; text-align: center;">
                <p style="margin: 5px 0; font-size: 1rem; color: var(--text-main);">
                    <strong><?php echo t('points_earned.earned_now'); ?></strong>
                    <span
                        style="color: var(--accent-success); font-size: 1.2rem;">+<?php echo number_format($_SESSION['postVentaPuntosGanados'] ?? 0, 0, ',', '.'); ?></span>
                </p>
                <p style="margin: 10px 0 5px 0; font-size: 1rem; color: var(--text-main);">
                    <strong><?php echo t('points_earned.total_accumulated'); ?></strong> <span
                        style="font-weight: bold; font-size: 1.5rem; color: var(--accent);"><?php echo number_format($_SESSION['puntosActualesAcumulados'] ?? 0, 0, ',', '.'); ?></span>
                </p>
            </div>

            <div style="text-align: center; margin-top: 15px;">
                <button onclick="cerrarPuntosPostVenta()"
                    style="background: var(--accent-success); color: white; padding: 10px 30px; border: none; border-radius: 6px; cursor: pointer; font-size: 1rem; width: 100%; font-weight: 600;">Aceptar</button>
            </div>
        </div>
    </div>

    <script>
        function cerrarPuntosPostVenta() {
            document.getElementById('puntosPostVentaExito').remove();
            const modalVenta = document.getElementById('ventaExito');
            if (modalVenta) {
                modalVenta.style.display = 'flex';
            }
        }
    </script>

    <?php
    unset($_SESSION['mostrarModalPuntosPostVenta']);
    unset($_SESSION['postVentaPuntosGanados']);
    unset($_SESSION['puntosActualesAcumulados']);
?>
<?php
endif; ?>

<!-- ##=========================== MODAL: PREVISUALIZACIÓN DE CIERRE DE CAJA ===========================## -->
<!-- Se muestra cuando el cajero pulsa "Hacer Caja" y se genera la previsualización -->
<!-- ##=========================== MODAL: ARQUEO DE CAJA ===========================## -->
<!-- Primer modal: conteo de dinero antes del cierre -->
<?php if (isset($_SESSION['cajaPrevisualizacion']) && $_SESSION['cajaPrevisualizacion'] && isset($_SESSION['resumenCaja'])): ?>
    <div class="modal-overlay" id="arqueoModal">
        <div class="modal-content modal-exito" style="max-width: 500px;">
            <!-- Icono de caja/billete en azul -->
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#2563eb"
                stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 15px;">
                <rect x="2" y="6" width="20" height="12" rx="2"></rect>
                <path d="M12 12h.01"></path>
                <path d="M17 12h.01"></path>
                <path d="M7 12h.01"></path>
            </svg>
            <h3 style="color: var(--text-main); font-size: 1.4rem; margin-bottom: 10px;">
                <?php echo t('cash_count.title'); ?>
            </h3>
            <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 20px;">
                <?php echo t('cash_count.expected_cash'); ?>: <strong
                    style="color: var(--accent); font-size: 1.1rem;"><?php echo number_format($_SESSION['resumenCaja']['importeActual'], 2, ',', '.'); ?>
                    €</strong>
            </p>

            <!-- Billetes -->
            <div style="margin-bottom: 12px;">
                <p style="margin: 0 0 6px 0; font-size: 0.8rem; font-weight: 600; color: var(--text-muted);">
                    <?php echo t('cash_count.bills'); ?>
                </p>
                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 6px;">
                    <?php foreach ([500, 200, 100, 50, 20, 10, 5] as $valor): ?>
                        <div style="display: flex; align-items: center; gap: 4px;">
                            <span style="font-size: 0.75rem; width: 40px;"><?php echo $valor; ?>€</span>
                            <input type="number" min="0" value="0" data-denominacion="<?php echo $valor; ?>"
                                class="arqueo-billete"
                                style="width: 50px; padding: 4px; text-align: center; border: 1px solid var(--border-main); border-radius: 4px; font-size: 0.8rem; background: var(--bg-input); color: var(--text-main);"
                                onchange="calcularArqueo()" oninput="calcularArqueo()">
                        </div>
                        <?php
                    endforeach; ?>
                </div>
            </div>

            <!-- Monedas -->
            <div style="margin-bottom: 12px;">
                <p style="margin: 0 0 6px 0; font-size: 0.8rem; font-weight: 600; color: var(--text-muted);">
                    <?php echo t('cash_count.coins'); ?>
                </p>
                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 6px;">
                    <?php foreach ([2, 1, 0.50, 0.20, 0.10, 0.05, 0.02, 0.01] as $valor): ?>
                        <div style="display: flex; align-items: center; gap: 4px;">
                            <span style="font-size: 0.75rem; width: 40px;"><?php echo str_replace('.', ',', $valor); ?>€</span>
                            <input type="number" min="0" value="0" data-denominacion="<?php echo $valor; ?>"
                                class="arqueo-moneda"
                                style="width: 50px; padding: 4px; text-align: center; border: 1px solid var(--border-main); border-radius: 4px; font-size: 0.8rem; background: var(--bg-input); color: var(--text-main);"
                                onchange="calcularArqueo()" oninput="calcularArqueo()">
                        </div>
                        <?php
                    endforeach; ?>
                </div>
            </div>

            <!-- Resultado -->
            <div
                style="background: var(--bg-main); padding: 10px; border-radius: 8px; margin-bottom: 12px; border: 1px solid var(--border-main);">
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                    <span style="color: var(--text-muted);"><?php echo t('cash_count.expected_cash'); ?>:</span>
                    <span id="arqueoEsperado"
                        style="font-weight: 600;"><?php echo number_format($_SESSION['resumenCaja']['importeActual'], 2, ',', '.'); ?>
                        €</span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                    <span style="color: var(--text-muted);"><?php echo t('cash_count.counted_cash'); ?>:</span>
                    <span id="arqueoContado" style="font-weight: 600; color: var(--accent);">0,00 €</span>
                </div>
                <div
                    style="display: flex; justify-content: space-between; padding-top: 8px; border-top: 1px dashed var(--border-main);">
                    <span style="font-weight: 600;"><?php echo t('cash_count.difference'); ?>:</span>
                    <span id="arqueoDiferencia" style="font-weight: 700; color: var(--accent-success);">0,00 €</span>
                </div>
            </div>

            <!-- Observaciones -->
            <textarea id="arqueoObservaciones" placeholder="<?php echo t('cash_count.observations'); ?>"
                style="width: 100%; padding: 10px; border: 1px solid var(--border-main); border-radius: 6px; resize: none; font-size: 0.85rem; margin-bottom: 20px; background: var(--bg-input); color: var(--text-main);"
                rows="2"></textarea>

            <!-- Botones -->
            <div style="display: flex; gap: 10px; justify-content: center;">
                <button class="btn-modal-cancelar"
                    onclick="document.getElementById('arqueoModal').style.display='none';"><?php echo t('cash_count.cancel'); ?></button>
                <button class="btn-cerrar-exito" style="margin-top: 0; background: var(--accent);"
                    onclick="continuarArqueo()"><?php echo t('cash_count.continue'); ?></button>
            </div>
        </div>
    </div>

    <!-- ##=========================== MODAL: RESUMEN DE CAJA ===========================## -->
    <!-- Segundo modal: resumen de ventas -->
    <div class="modal-overlay" id="cajaPrevisualizacion" style="display: none;">
        <div class="modal-content modal-exito" style="max-width: 450px;">

            <!-- Contenedor imprimible del resumen de caja -->
            <div id="cajaResumenImprimible" class="resumen-caja-container">

                <!-- Header visible solo al imprimir (clase .solo-impresion) -->
                <div class="solo-impresion" style="text-align: center; margin-bottom: 15px;">
                    <h2>TPV Bazar</h2>
                    <p><?php echo t('cash_summary.title'); ?> - <?php echo date('d/m/Y H:i'); ?></p>
                </div>

                <h4 class="resumen-caja-titulo">
                    <?php echo t('cash_summary.sales_summary'); ?>
                </h4>

                <!-- Desglose por EFECTIVO: cantidad de tickets, total y devoluciones -->
                <div style="display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 0.95rem;">
                    <span><strong style="color: #4b5563;"><?php echo t('cash_summary.cash'); ?>:</strong>
                        (<?php echo $_SESSION['resumenCaja']['efectivo']['cantidad']; ?>
                        <?php echo t('cash_summary.tickets'); ?>)</span>
                    <div style="text-align: right;">
                        <span
                            style="font-weight: 600;"><?php echo number_format($_SESSION['resumenCaja']['efectivo']['total'], 2, ',', '.'); ?>
                            €</span>
                        <?php if ($_SESSION['resumenCaja']['efectivo']['devoluciones'] > 0): ?>
                            <br><span style="font-size: 0.75rem; color: #b91c1c;">(<?php echo t('cash_summary.returns'); ?>:
                                -<?php echo number_format($_SESSION['resumenCaja']['efectivo']['devoluciones'], 2, ',', '.'); ?>
                                €)</span>
                            <?php
                        endif; ?>
                        <?php if (isset($_SESSION['resumenCaja']['totalRetiros']) && $_SESSION['resumenCaja']['totalRetiros'] > 0): ?>
                            <br><span style="font-size: 0.75rem; color: #ea580c;">(<?php echo t('cash_summary.withdrawals'); ?>:
                                -<?php echo number_format($_SESSION['resumenCaja']['totalRetiros'], 2, ',', '.'); ?>
                                €)</span>
                            <?php
                        endif; ?>
                    </div>
                </div>

                <!-- Desglose por TARJETA: cantidad de tickets, total y devoluciones -->
                <div style="display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 0.95rem;">
                    <span><strong style="color: #4b5563;"><?php echo t('cash_summary.card'); ?>:</strong>
                        (<?php echo $_SESSION['resumenCaja']['tarjeta']['cantidad']; ?>
                        <?php echo t('cash_summary.tickets'); ?>)</span>
                    <div style="text-align: right;">
                        <span
                            style="font-weight: 600;"><?php echo number_format($_SESSION['resumenCaja']['tarjeta']['total'], 2, ',', '.'); ?>
                            €</span>
                        <?php if ($_SESSION['resumenCaja']['tarjeta']['devoluciones'] > 0): ?>
                            <br><span style="font-size: 0.75rem; color: #b91c1c;">(<?php echo t('cash_summary.returns'); ?>:
                                -<?php echo number_format($_SESSION['resumenCaja']['tarjeta']['devoluciones'], 2, ',', '.'); ?>
                                €)</span>
                            <?php
                        endif; ?>
                    </div>
                </div>

                <!-- Desglose por BIZUM: cantidad de tickets, total y devoluciones -->
                <div style="display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 0.95rem;">
                    <span><strong style="color: #4b5563;"><?php echo t('cash_summary.bizum'); ?>:</strong>
                        (<?php echo $_SESSION['resumenCaja']['bizum']['cantidad']; ?>
                        <?php echo t('cash_summary.tickets'); ?>)</span>
                    <div style="text-align: right;">
                        <span
                            style="font-weight: 600;"><?php echo number_format($_SESSION['resumenCaja']['bizum']['total'], 2, ',', '.'); ?>
                            €</span>
                        <?php if ($_SESSION['resumenCaja']['bizum']['devoluciones'] > 0): ?>
                            <br><span style="font-size: 0.75rem; color: #b91c1c;">(<?php echo t('cash_summary.returns'); ?>:
                                -<?php echo number_format($_SESSION['resumenCaja']['bizum']['devoluciones'], 2, ',', '.'); ?>
                                €)</span>
                            <?php
                        endif; ?>
                    </div>
                </div>

                <!-- TOTAL GENERAL de ventas del día (suma de todos los métodos) -->
                <div class="resumen-total-ventas">
                    <strong><?php echo t('cash_summary.total_sales'); ?>:</strong>
                    <strong
                        class="total-monto-verde"><?php echo number_format($_SESSION['resumenCaja']['totalGeneral'], 2, ',', '.'); ?>
                        €</strong>
                </div>

                <!-- Detalles reales de la caja: fondo inicial, devoluciones y efectivo real -->
                <div class="resumen-caja-detalles">
                    <!-- Fondo de caja inicial (importe con el que se abrió la caja) -->
                    <div class="resumen-detalle-fila">
                        <span><?php echo t('cash_summary.initial_cash'); ?>:</span>
                        <span><?php echo number_format($_SESSION['resumenCaja']['importeInicial'], 2, ',', '.'); ?> €</span>
                    </div>
                    <!-- Total de devoluciones realizadas durante la sesión -->
                    <div class="resumen-detalle-fila" style="color: #ef4444;">
                        <span><?php echo t('cash_summary.total_returns'); ?>:</span>
                        <span
                            style="font-weight: 600;">-<?php echo number_format($_SESSION['resumenCaja']['totalDevoluciones'], 2, ',', '.'); ?>
                            €</span>
                    </div>
                    <!-- Total de retiros realizados durante la sesión -->
                    <div class="resumen-detalle-fila" style="color: #ef4444;">
                        <span><?php echo t('cash_summary.total_withdrawals'); ?>:</span>
                        <span
                            style="font-weight: 600;">-<?php echo number_format($_SESSION['resumenCaja']['totalRetiros'] ?? 0, 2, ',', '.'); ?>
                            €</span>
                    </div>
                    <!-- Efectivo real que debería haber en la caja física -->
                    <div class="caja-efectivo-real">
                        <span><?php echo t('cash_summary.expected_cash'); ?>:</span>
                        <span><?php echo number_format($_SESSION['resumenCaja']['importeActual'], 2, ',', '.'); ?> €</span>
                    </div>
                    <!-- Arqueo: efectivo contado y diferencia -->
                    <div id="arqueoResumen"
                        style="background: var(--bg-accent-success); padding: 10px; border-radius: 8px; margin-top: 10px; border: 1px solid var(--accent-success); opacity: 0.9;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                            <span style="color: var(--text-main);"><?php echo t('cash_count.counted_cash'); ?>:</span>
                            <span id="arqueoContadoResumen" style="font-weight: 600; color: var(--text-main);">--</span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: var(--text-main);"><?php echo t('cash_count.difference'); ?>:</span>
                            <span id="arqueoDiferenciaResumen" style="font-weight: 600;">--</span>
                        </div>
                    </div>
                    <input type="hidden" id="arqueoTotalContado" value="0">
                    <input type="hidden" id="arqueoDiferenciaValue" value="0">
                </div>

                <!-- Footer visible solo al imprimir: espacio para firma y sello -->
                <div class="solo-impresion solo-impresion-footer">
                    <p><?php echo t('cash_summary.signature'); ?>:</p>
                    <br><br><br>
                </div>
            </div>

            <!-- Opción para guardar cambio para el siguiente turno -->
            <div class="cambio-turno-container">
                <label for="cambio" class="cambio-turno-label">
                    💰 <?php echo t('cash_summary.next_turn_change'); ?>
                </label>
                <input type="number" id="cambio" name="cambio" step="0.0001" oninput="validar4Decimales(this)"
                    onblur="validar4Decimales(this)" min="0"
                    value="<?php echo number_format($_SESSION['resumenCaja']['importeActual'], 2, '.', ''); ?>"
                    placeholder="0,00"
                    style="width: 100%; padding: 10px; text-align: center; font-size: 16px; border-radius: 6px;">
                <p class="cambio-turno-subtitulo">
                    <?php echo t('cash_summary.change_help'); ?>
                </p>
            </div>

            <!-- Botones: Cancelar (cierra sin cerrar caja) y Confirmar Cierre (cierra la caja definitivamente) -->
            <div style="display: flex; gap: 10px; justify-content: center; margin-top: 20px;">
                <button class="btn-modal-cancelar"
                    onclick="document.getElementById('cajaPrevisualizacion').style.display='none';"><?php echo t('cash_summary.cancel'); ?></button>
                <form method="POST" action="index.php" style="margin: 0;">
                    <input type="hidden" name="accion" value="confirmarCaja">
                    <input type="hidden" name="cambio" id="cambioHidden"
                        value="<?php echo $_SESSION['resumenCaja']['importeActual']; ?>">
                    <input type="hidden" name="arqueoTotalContado" id="arqueoTotalContadoForm" value="0">
                    <input type="hidden" name="arqueoDetalleConteo" id="arqueoDetalleConteoForm" value="">
                    <input type="hidden" name="arqueoObservaciones" id="arqueoObservacionesHidden" value="">
                    <button type="submit" class="btn-cerrar-exito" style="margin-top: 0; background: #2563eb;"
                        onclick="document.getElementById('cambioHidden').value = document.getElementById('cambio').value; document.getElementById('arqueoObservacionesHidden').value = document.getElementById('arqueoObservaciones').value || '';"><?php echo t('cash_summary.confirm'); ?></button>
                </form>
            </div>
        </div>
    </div>
    <?php
    // Limpiamos la flag de previsualización pero NO el resumenCaja,
    // ya que se necesita si el usuario confirma el cierre e imprime
    unset($_SESSION['cajaPrevisualizacion']);
?>
<?php
endif; ?>

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
            <h3 style="color: var(--text-main); font-size: 1.4rem; margin-bottom: 20px;">
                <?php echo t('cash_summary.closed_success'); ?>
            </h3>
            <p style="color: var(--text-muted); font-size: 0.95rem; margin-bottom: 20px;">
                <?php echo t('cash_summary.closed_message'); ?>
            </p>

            <!-- Bloque oculto con el HTML del resumen para imprimir -->
            <!-- Se inyecta en una ventana nueva al pulsar "Imprimir Resumen" -->
            <div id="cajaOcultaImprimible" style="display: none;">
                <!-- Cabecera del documento impreso -->
                <div style="text-align: center; margin-bottom: 15px; font-family: 'Inter', sans-serif;">
                    <h2 style="margin:0;">TPV Bazar</h2>
                    <p style="margin:5px 0 15px 0;"><?php echo t('cash_summary.title'); ?> -
                        <?php echo date('d/m/Y H:i'); ?>
                    </p>
                </div>

                <!-- Desglose por método de pago para impresión -->
                <div
                    style="border-top: 1px solid #000; padding-top: 10px; padding-bottom: 5px; font-family: 'Inter', sans-serif;">
                    <!-- Efectivo -->
                    <p style="margin: 5px 0; display:flex; justify-content:space-between;">
                        <span><?php echo t('cash_summary.cash'); ?>
                            (<?php echo $_SESSION['resumenCaja']['efectivo']['cantidad']; ?>):</span>
                        <span><?php echo number_format($_SESSION['resumenCaja']['efectivo']['total'], 2, ',', '.'); ?>
                            €</span>
                    </p>
                    <?php if ($_SESSION['resumenCaja']['efectivo']['devoluciones'] > 0): ?>
                        <p style="margin: 0px 0 5px 0; display:flex; justify-content:flex-end; font-size: 0.8rem;">
                            <span>(<?php echo t('cash_summary.returns'); ?>:
                                -<?php echo number_format($_SESSION['resumenCaja']['efectivo']['devoluciones'], 2, ',', '.'); ?>
                                €)</span>
                        </p>
                        <?php
                    endif; ?>
                    <?php if (isset($_SESSION['resumenCaja']['totalRetiros']) && $_SESSION['resumenCaja']['totalRetiros'] > 0): ?>
                        <p style="margin: 0px 0 5px 0; display:flex; justify-content:flex-end; font-size: 0.8rem;">
                            <span>(<?php echo t('cash_summary.withdrawals'); ?>:
                                -<?php echo number_format($_SESSION['resumenCaja']['totalRetiros'], 2, ',', '.'); ?>
                                €)</span>
                        </p>
                        <?php
                    endif; ?>

                    <!-- Tarjeta -->
                    <p style="margin: 5px 0; display:flex; justify-content:space-between;">
                        <span><?php echo t('cash_summary.card'); ?>
                            (<?php echo $_SESSION['resumenCaja']['tarjeta']['cantidad']; ?>):</span>
                        <span><?php echo number_format($_SESSION['resumenCaja']['tarjeta']['total'], 2, ',', '.'); ?>
                            €</span>
                    </p>
                    <?php if ($_SESSION['resumenCaja']['tarjeta']['devoluciones'] > 0): ?>
                        <p style="margin: 0px 0 5px 0; display:flex; justify-content:flex-end; font-size: 0.8rem;">
                            <span>(<?php echo t('cash_summary.returns'); ?>:
                                -<?php echo number_format($_SESSION['resumenCaja']['tarjeta']['devoluciones'], 2, ',', '.'); ?>
                                €)</span>
                        </p>
                        <?php
                    endif; ?>

                    <!-- Bizum -->
                    <p style="margin: 5px 0; display:flex; justify-content:space-between;">
                        <span><?php echo t('cash_summary.bizum'); ?>
                            (<?php echo $_SESSION['resumenCaja']['bizum']['cantidad']; ?>):</span>
                        <span><?php echo number_format($_SESSION['resumenCaja']['bizum']['total'], 2, ',', '.'); ?> €</span>
                    </p>
                    <?php if ($_SESSION['resumenCaja']['bizum']['devoluciones'] > 0): ?>
                        <p style="margin: 0px 0 5px 0; display:flex; justify-content:flex-end; font-size: 0.8rem;">
                            <span>(<?php echo t('cash_summary.returns'); ?>:
                                -<?php echo number_format($_SESSION['resumenCaja']['bizum']['devoluciones'], 2, ',', '.'); ?>
                                €)</span>
                        </p>
                        <?php
                    endif; ?>
                </div>

                <!-- Total general de ventas para impresión -->
                <div
                    style="border-top: 1px solid #000; padding-top: 10px; margin-top: 10px; font-weight: bold; display:flex; justify-content:space-between; font-family: 'Inter', sans-serif;">
                    <span><?php echo t('cash_summary.total_sales'); ?>:</span>
                    <span><?php echo number_format($_SESSION['resumenCaja']['totalGeneral'], 2, ',', '.'); ?> €</span>
                </div>

                <!-- Detalles de caja para impresión: fondo, devoluciones y efectivo real -->
                <div
                    style="border-top: 1px dashed #000; margin-top: 15px; padding-top: 10px; font-family: 'Inter', sans-serif;">
                    <p style="margin: 5px 0; display:flex; justify-content:space-between; font-size: 0.9em;">
                        <span><?php echo t('cash_summary.initial_cash'); ?>:</span>
                        <span><?php echo number_format($_SESSION['resumenCaja']['importeInicial'], 2, ',', '.'); ?> €</span>
                    </p>
                    <p style="margin: 5px 0; display:flex; justify-content:space-between; font-size: 0.9em; color: #000;">
                        <span><?php echo t('cash_summary.total_returns'); ?>:</span>
                        <span>- <?php echo number_format($_SESSION['resumenCaja']['totalDevoluciones'], 2, ',', '.'); ?>
                            €</span>
                    </p>
                    <p style="margin: 5px 0; display:flex; justify-content:space-between; font-size: 0.9em; color: #000;">
                        <span><?php echo t('cash_summary.total_withdrawals'); ?>:</span>
                        <span>- <?php echo number_format($_SESSION['resumenCaja']['totalRetiros'] ?? 0, 2, ',', '.'); ?>
                            €</span>
                    </p>
                    <p
                        style="margin: 5px 0; display:flex; justify-content:space-between; font-weight: bold; font-size: 1.1em;">
                        <span><?php echo t('cash_summary.real_cash'); ?>:</span>
                        <span><?php echo number_format($_SESSION['resumenCaja']['importeActual'], 2, ',', '.'); ?> €</span>
                    </p>
                </div>

                <!-- Espacio para firma/sello en el documento impreso -->
                <div style="text-align: center; margin-top: 30px; font-size: 0.8rem; font-family: 'Inter', sans-serif;">
                    <p><?php echo t('cash_summary.signature'); ?></p>
                </div>
            </div>

            <!-- Botones: Imprimir Resumen y Aceptar (cierra el modal) -->
            <div style="display: flex; gap: 10px; justify-content: center; margin-top: 20px;">
                <button class="btn-cerrar-exito"
                    onclick="imprimirCierreCaja()"><?php echo t('cash_summary.print'); ?></button>
                <button class="btn-modal-cancelar"
                    onclick="document.getElementById('cajaConfirmacion').style.display='none';"><?php echo t('cash_summary.accept'); ?></button>
            </div>
        </div>
    </div>

    <script>
        /**
    * imprimirCierreCaja()
    * Abre una ventana emergente con el contenido del resumen de cierre de caja
    * formateado para impresión. Tras imprimir, cierra la ventan    a y oculta el modal.
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
<?php
endif; ?>

<!-- ##=========================== MODALES DE BIENVENIDA (RECUPERACIÓN DE SESIÓN) ===========================## -->

<!-- Modal: Descanso Terminado -->
<div class="modal-overlay" id="modalDescansoTerminado" style="display:none;">
    <div class="modal-content modal-exito modal-border-blue" style="max-width: 400px;">
        <div class="icon-container-discount" style="background: rgba(37, 99, 235, 0.1); border: 2px solid #2563eb;">
            <svg xmlns="http://www.w3.org/2000/svg" width="35" height="35" viewBox="0 0 24 24" fill="none"
                stroke="#2563eb" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 12h1m8-9v1m8 8h1m-9 8v1M5.6 5.6l.7.7m12.1 12.1l.7.7m0-12.8l-.7.7m-12.1 12.1l-.7.7"></path>
                <circle cx="12" cy="12" r="4"></circle>
            </svg>
        </div>
        <h3 style="color: var(--text-main); margin-top: 15px;"><?php echo t('resume.welcome_back'); ?></h3>
        <p style="color: var(--text-muted); margin-bottom: 20px;"><?php echo t('resume.session_active'); ?></p>
        <button class="btn-cerrar-exito" style="background: #2563eb; width: 100%;"
            onclick="cerrarModalBienvenida('modalDescansoTerminado')"><?php echo t('resume.continue'); ?></button>
    </div>
</div>

<script>
    /**
     * Cierra el modal de bienvenida y limpia el estado en el servidor.
     */
    function cerrarModalBienvenida(idModal) {
        document.getElementById(idModal).style.display = 'none';

        // Llamada AJAX para limpiar la interrupción en la base de datos y sesión
        fetch('api/caja.php?accion=limpiarInterrupcion')
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    console.error('Error al limpiar interrupción:', data.message);
                }
            })
            .catch(error => console.error('Error en fetch:', error));
    }

    // Comprobar si hay datos de recuperación al cargar la página
    document.addEventListener('DOMContentLoaded', function () {
        <?php if (isset($_SESSION['interrupcionRecuperada'])): ?>
        const datos = <?php echo json_encode($_SESSION['interrupcionRecuperada']); ?>;

        if (datos.tipo === 'pausa') {
            // Si es pausa, solo mostramos el modal si es el mismo usuario
            if (datos.usuarioId == <?php echo $_SESSION['idUsuario']; ?>) {
                document.getElementById('modalDescansoTerminado').style.display = 'flex';
            } else {
                // Si entró otro usuario después de una pausa, limpiar silenciosamente
                fetch('api/caja.php?accion=limpiarInterrupcion');
            }
        } else if (datos.tipo === 'turno') {
            // Si es cambio de turno, siempre lo mostramos
            document.getElementById('welcomeOldUser').textContent = datos.usuarioNombre;

            // Formatear fecha/hora de cierre
            try {
                const fechaCierre = new Date(datos.fecha);
                document.getElementById('welcomeCloseTime').textContent = 'Cerrado a las ' +
                    fechaCierre.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            } catch (e) {
                document.getElementById('welcomeCloseTime').textContent = 'Cerrado recientemente';
            }

            document.getElementById('modalNuevoTurno').style.display = 'flex';
        }
        <?php
        endif; ?>
    });
</script>
<?php unset($_SESSION['interrupcionRecuperada']); ?>

<!-- Carga del script externo del cajero (funciones de búsqueda y filtrado de productos) -->
<script src="webroot/js/cajero.js"></script>

<!-- ##=========================== MODAL: HISTORIAL DE VENTAS ===========================## -->
<div class="modal-overlay" id="modalHistorialVentas" style="display:none;">
    <div class="modal-content modal-premium" style="max-width: 650px;">
        <!-- Cabecera con gradiente azul y icono de historial -->
        <div class="modal-header-premium modal-header-blue">
            <div class="icon-container-discount">
                <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none"
                    stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 8v4l3 3"></path>
                    <circle cx="12" cy="12" r="10"></circle>
                </svg>
            </div>
            <h3><?php echo t('history.sales_title'); ?></h3>
            <p id="historialFecha"><?php echo t('history.sales_subtitle'); ?></p>
        </div>

        <div class="modal-body-premium">
            <div id="historialVentasContenido" style="max-height: 400px; overflow-y: auto;">
                <!-- Aquí se cargarán las ventas -->
            </div>
            <div
                style="display: flex; justify-content: space-between; margin-top: 20px; padding-top: 15px; border-top: 1px solid var(--border-main);">
                <div id="historialTotal" style="font-weight: bold; font-size: 1.1rem;"></div>
                <button class="btn-modal-cancelar" onclick="cerrarModal('modalHistorialVentas')"
                    style="min-width: 100px;"><?php echo t('history.close'); ?></button>
            </div>
        </div>
    </div>
</div>

<!-- ##=========================== MODAL: HISTORIAL DE DEVOLUCIONES ===========================## -->
<div class="modal-overlay" id="modalHistorialDevoluciones" style="display:none;">
    <div class="modal-content modal-premium" style="max-width: 650px;">
        <!-- Cabecera con gradiente rojo y icono de devoluciones -->
        <div class="modal-header-premium modal-header-red">
            <div class="icon-container-discount">
                <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none"
                    stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M1 4v6h6"></path>
                    <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
                </svg>
            </div>
            <h3><?php echo t('history.returns_title'); ?></h3>
            <p id="historialDevolucionesFecha"><?php echo t('history.returns_subtitle'); ?></p>
        </div>

        <div class="modal-body-premium">
            <div id="historialDevolucionesContenido" style="max-height: 400px; overflow-y: auto;">
                <!-- Aquí se cargarán las devoluciones -->
            </div>
            <div
                style="display: flex; justify-content: space-between; margin-top: 20px; padding-top: 15px; border-top: 1px solid var(--border-main);">
                <div id="historialDevolucionesTotal" style="font-weight: bold; font-size: 1.1rem;"></div>
                <button class="btn-modal-cancelar" onclick="cerrarModal('modalHistorialDevoluciones')"
                    style="min-width: 100px;"><?php echo t('history.close'); ?></button>
            </div>
        </div>
    </div>
</div>

<!-- ##=========================== MODAL: DETALLE DE DEVOLUCION ===========================## -->
<div class="modal-overlay" id="modalDetalleDevolucion" style="display:none;">
    <div class="modal-content modal-premium" style="max-width: 700px;">
        <div class="modal-header-premium modal-header-red">
            <div class="icon-container-discount">
                <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none"
                    stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M1 4v6h6"></path>
                    <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
                </svg>
            </div>
            <h3><?php echo t('return_details.title'); ?></h3>
            <p id="detalleDevolucionId"><?php echo t('return_details.subtitle'); ?></p>
        </div>

        <div class="modal-body-premium">
            <div id="detalleDevolucionContenido">
                <!-- Aquí se cargarán los detalles -->
            </div>
            <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                <button class="btn-modal-cancelar"
                    onclick="cerrarModal('modalDetalleDevolucion')"><?php echo t('return_details.close'); ?></button>
            </div>
        </div>
    </div>
</div>

<!-- ##=========================== MODAL: DETALLE DE VENTA ===========================## -->
<div class="modal-overlay" id="modalDetalleVenta" style="display:none;">
    <div class="modal-content modal-premium" style="max-width: 700px;">
        <div class="modal-header-premium modal-header-blue">
            <div class="icon-container-discount">
                <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none"
                    stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                    <polyline points="10 9 9 9 8 9"></polyline>
                </svg>
            </div>
            <h3><?php echo t('sale_details.title'); ?></h3>
            <p id="detalleVentaId"><?php echo t('sale_details.subtitle'); ?></p>
        </div>
        <div class="modal-body-premium">
            <div id="detalleVentaContenido">
                <!-- Aquí se cargarán los detalles de la venta -->
            </div>
        </div>
    </div>
</div>

<script>
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

        // Obtener el efectivo esperado (quitar puntos de miles y cambiar coma por punto)
        const efectivoEsperadoStr = document.getElementById('arqueoEsperado').textContent.replace('€', '').trim();
        const efectivoEsperado = parseFloat(efectivoEsperadoStr.replace(/\./g, '').replace(',', '.')) || 0;

        // Calcular diferencia
        const diferencia = Math.round((total - efectivoEsperado) * 100) / 100;

        // Actualizar displays
        document.getElementById('arqueoContado').textContent = total.toFixed(2).replace('.', ',') + ' €';

        const diffElement = document.getElementById('arqueoDiferencia');
        diffElement.textContent = (diferencia >= 0 ? '+' : '') + diferencia.toFixed(2).replace('.', ',') + ' €';

        // Cambiar color según la diferencia
        if (diferencia === 0) {
            diffElement.style.color = '#059669'; // Verde - caja correcta
            diffElement.textContent = '✓ <?php echo t('cash_count.correct'); ?>';
        } else if (diferencia > 0) {
            diffElement.style.color = '#2563eb'; // Azul - sobrante
            diffElement.textContent = '+' + diferencia.toFixed(2).replace('.', ',') + ' € (<?php echo t('cash_count.surplus'); ?>)';
        } else {
            diffElement.style.color = '#dc2626'; // Rojo - faltante
            diffElement.textContent = diferencia.toFixed(2).replace('.', ',') + ' € (<?php echo t('cash_count.shortage'); ?>)';
        }

        // Guardar el total en un campo oculto para el formulario
        const form = document.querySelector('#cajaPrevisualizacion form');
        if (form) {
            let oculto = document.getElementById('arqueoTotalContado');
            if (!oculto) {
                oculto = document.createElement('input');
                oculto.type = 'hidden';
                oculto.id = 'arqueoTotalContado';
                oculto.name = 'arqueoTotalContado';
                form.appendChild(oculto);
            }
            oculto.value = total;

            // Guardar detalle del conteo
            let detalleOculto = document.getElementById('arqueoDetalleConteo');
            if (!detalleOculto) {
                detalleOculto = document.createElement('input');
                detalleOculto.type = 'hidden';
                detalleOculto.id = 'arqueoDetalleConteo';
                detalleOculto.name = 'arqueoDetalleConteo';
                form.appendChild(detalleOculto);
            }

            const detalle = {};
            document.querySelectorAll('.arqueo-billete').forEach(input => {
                detalle['billete_' + input.dataset.denominacion] = parseInt(input.value) || 0;
            });
            document.querySelectorAll('.arqueo-moneda').forEach(input => {
                detalle['moneda_' + input.dataset.denominacion] = parseInt(input.value) || 0;
            });
            detalleOculto.value = JSON.stringify(detalle);
        }
    }

    /**
     * Continúa del arqueo al resumen de caja
     */
    function continuarArqueo() {
        // Guardar datos del arqueo en campos hidden del formulario del segundo modal
        let form = document.querySelector('#cajaPrevisualizacion form');
        if (!form) {
            // Crear formulario si no existe
            form = document.createElement('form');
            form.method = 'POST';
            form.action = 'index.php';
            form.innerHTML = `
                <input type="hidden" name="accion" value="confirmarCaja">
                <input type="hidden" name="cambio" id="cambioHidden" value="<?php echo $_SESSION['resumenCaja']['importeActual']; ?>">
                <input type="hidden" name="arqueoTotalContado" id="arqueoTotalContado" value="0">
                <input type="hidden" name="arqueoDetalleConteo" id="arqueoDetalleConteo" value="">
                <input type="hidden" name="arqueoObservaciones" id="arqueoObservacionesHidden" value="">
            `;
            document.getElementById('cajaPrevisualizacion').appendChild(form);
        }

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
        const efectivoEsperadoStr = document.getElementById('arqueoEsperado').textContent.replace('€', '').trim();
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

        // Actualizar los campos hidden del formulario
        const arqueoTotalContadoEl = document.getElementById('arqueoTotalContado');
        const arqueoDetalleConteoEl = document.getElementById('arqueoDetalleConteo');
        if (arqueoTotalContadoEl) {
            arqueoTotalContadoEl.value = total.toFixed(2);
        }
        if (arqueoDetalleConteoEl) {
            arqueoDetalleConteoEl.value = JSON.stringify(detalle);
        }

        // Actualizar la visualización del arqueo en el resumen
        const arqueoContadoResumen = document.getElementById('arqueoContadoResumen');
        const arqueoDiferenciaResumen = document.getElementById('arqueoDiferenciaResumen');
        if (arqueoContadoResumen) {
            arqueoContadoResumen.textContent = total.toFixed(2).replace('.', ',') + ' €';
        }
        if (arqueoDiferenciaResumen) {
            if (diferencia === 0) {
                arqueoDiferenciaResumen.textContent = '✓ <?php echo t('cash_count.correct'); ?>';
                arqueoDiferenciaResumen.style.color = '#059669';
            } else if (diferencia > 0) {
                arqueoDiferenciaResumen.textContent = '+' + diferencia.toFixed(2).replace('.', ',') + ' € (<?php echo t('cash_count.surplus'); ?>)';
                arqueoDiferenciaResumen.style.color = '#2563eb';
            } else {
                arqueoDiferenciaResumen.textContent = diferencia.toFixed(2).replace('.', ',') + ' € (<?php echo t('cash_count.shortage'); ?>)';
                arqueoDiferenciaResumen.style.color = '#dc2626';
            }
        }

        // También actualizar los campos del formulario de confirmación
        const arqueoTotalForm = document.getElementById('arqueoTotalContadoForm');
        const arqueoDetalleForm = document.getElementById('arqueoDetalleConteoForm');
        if (arqueoTotalForm) {
            arqueoTotalForm.value = total.toFixed(2);
        }
        if (arqueoDetalleForm) {
            arqueoDetalleForm.value = JSON.stringify(detalle);
        }

        // Copiar observaciones
        const observaciones = document.getElementById('arqueoObservaciones');
        if (observaciones && document.getElementById('arqueoObservacionesHidden')) {
            document.getElementById('arqueoObservacionesHidden').value = observaciones.value;
        }

        // Cerrar arqueo y abrir resumen
        document.getElementById('arqueoModal').style.display = 'none';
        document.getElementById('cajaPrevisualizacion').style.display = 'flex';
    }

    /**
     * Muestra el modal con el historial de ventas de hoy
     */
    function mostrarHistorialVentas() {
        const modal = document.getElementById('modalHistorialVentas');
        const contenido = document.getElementById('historialVentasContenido');
        const totalDiv = document.getElementById('historialTotal');
        const fechaDiv = document.getElementById('historialFecha');

        // Actualizar la fecha en el subtítulo
        const hoy = new Date();
        if (fechaDiv) {
            fechaDiv.textContent = '<?php echo t('history.sales_subtitle'); ?> - ' + hoy.toLocaleDateString('es-ES', { day: 'numeric', month: 'long', year: 'numeric' });
        }

        // Mostrar modal
        modal.style.display = 'flex';

        // Cargar ventas desde la API de la sesión de caja actual
        fetch('api/ventas.php?historialCaja=1')
            .then(res => {
                console.log('Response status:', res.status);
                if (!res.ok) {
                    throw new Error('HTTP error ' + res.status);
                }
                return res.json();
            })
            .then(ventas => {
                console.log('Ventas:', ventas);
                if (ventas.error) {
                    if (ventas.error.includes('No hay sesión')) {
                        contenido.innerHTML = '<p style="text-align: center; color: var(--text-muted); padding: 40px;"><?php echo t('history.no_session'); ?></p>';
                        totalDiv.textContent = '';
                        return;
                    }
                    throw new Error(ventas.error);
                }
                if (!ventas || ventas.length === 0) {
                    contenido.innerHTML = '<p style="text-align: center; color: var(--text-muted); padding: 40px;"><?php echo t('history.no_sales'); ?></p>';
                    totalDiv.textContent = '<?php echo t('history.total'); ?>: 0.00 €';
                    return;
                }

                // Calcular total
                let total = 0;
                let html = '<table class="historial-ventas-tabla">';
                html += '<thead><tr>';
                html += '<th><?php echo t('history.time'); ?></th>';
                html += '<th><?php echo t('history.user'); ?></th>';
                html += '<th><?php echo t('history.quantity'); ?></th>';
                html += '<th><?php echo t('history.payment_method'); ?></th>';
                html += '<th><?php echo t('history.total_table'); ?></th>';
                html += '<th><?php echo t('history.actions'); ?></th>';
                html += '</tr></thead><tbody>';

                ventas.forEach(v => {
                    const fecha = new Date(v.fecha);
                    const hora = fecha.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
                    const totalVenta = parseFloat(v.total);
                    const cantidad = v.cantidad_productos || 0;
                    total += totalVenta;

                    let formaPago = v.forma_pago || 'Efectivo';
                    let usuario = v.usuario_nombre || 'Cajero';

                    html += '<tr>';
                    html += '<td >' + hora + '</td>';
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
                totalDiv.textContent = '<?php echo t('history.total_day'); ?>: ' + total.toFixed(2).replace('.', ',') + ' € (' + ventas.length + ' <?php echo t('history.sales'); ?>)';
            })
            .catch(err => {
                console.error('Error cargando historial:', err);
                contenido.innerHTML = '<p style="text-align: center; color: #dc2626; padding: 40px;"><?php echo t('history.error_loading'); ?>: ' + err.message + '</p>';
                totalDiv.textContent = '';
            });
    }

    /**
     * Muestra los detalles de una venta específica en un modal
     */
    function verDetalleVenta(idVenta) {
        const modal = document.getElementById('modalDetalleVenta');
        const contenido = document.getElementById('detalleVentaContenido');

        modal.style.display = 'flex';
        contenido.innerHTML = '<p style="text-align: center; padding: 20px;"><?php echo t('sale_details.loading'); ?></p>';

        fetch('api/ventas.php?detalleVenta=' + idVenta)
            .then(res => res.json())
            .then(data => {
                if (data.error) {
                    contenido.innerHTML = '<p style="text-align: center; color: #dc2626; padding: 20px;"><?php echo t('sale_details.error'); ?>: ' + data.error + '</p>';
                    return;
                }

                const venta = data.venta;
                const lineas = data.lineas;
                const fecha = new Date(venta.fecha).toLocaleString('es-ES');

                // Update header info
                const serie = venta.serie || (venta.tipoDocumento === 'factura' ? 'F' : 'T');
                const numero = venta.numero || venta.id;
                document.getElementById('detalleVentaId').textContent = serie + String(numero).padStart(5, '0') + ' - ' + fecha;

                const tipoIcono = venta.tipoDocumento === 'factura' ? '📄' : '🧾';
                const tipoLabel = venta.tipoDocumento === 'factura' ? 'Factura' : 'Ticket';
                const pagoIcono = venta.metodoPago && venta.metodoPago.toLowerCase().includes('tarjeta') ? '💳' : '💵';
                const pagoLabel = venta.metodoPago || 'Efectivo';

                // Simple style matching other modals
                let html = '';

                // Info row
                html += '<div style="display: flex; gap: 15px; margin-bottom: 20px;">';
                html += '<div style="flex: 1; background: var(--bg-secondary); padding: 12px; border-radius: 8px;">';
                html += '<div style="font-size: 11px; color: var(--text-muted); margin-bottom: 4px;"><?php echo t('sale_details.type'); ?></div>';
                html += '<div style="font-weight: 600;">' + tipoIcono + ' ' + tipoLabel + '</div>';
                html += '</div>';
                html += '<div style="flex: 1; background: var(--bg-secondary); padding: 12px; border-radius: 8px;">';
                html += '<div style="font-size: 11px; color: var(--text-muted); margin-bottom: 4px;"><?php echo t('sale_details.payment'); ?></div>';
                html += '<div style="font-weight: 600;">' + pagoIcono + ' ' + pagoLabel + '</div>';
                html += '</div>';
                html += '</div>';

                // Products table
                html += '<div style="max-height: 250px; overflow-y: auto; margin-bottom: 20px;">';
                html += '<table style="width: 100%; border-collapse: collapse;">';
                html += '<thead><tr style="background: var(--bg-secondary);">';
                html += '<th style="padding: 10px; text-align: left; font-size: 12px; color: var(--text-muted);"><?php echo t('sale_details.product'); ?></th>';
                html += '<th style="padding: 10px; text-align: center; font-size: 12px; color: var(--text-muted);"><?php echo t('sale_details.quantity'); ?></th>';
                html += '<th style="padding: 10px; text-align: right; font-size: 12px; color: var(--text-muted);"><?php echo t('sale_details.price'); ?></th>';
                html += '<th style="padding: 10px; text-align: right; font-size: 12px; color: var(--text-muted);"><?php echo t('sale_details.amount'); ?></th>';
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
                html += '<?php echo t('sale_details.total'); ?>: ' + parseFloat(venta.total).toFixed(2).replace('.', ',') + ' €';
                html += '</div>';

                // Close button
                html += '<div style="margin-top: 20px; text-align: right;">';
                html += '<button class="btn-modal-cancelar" onclick="cerrarModal(\'modalDetalleVenta\')">Cerrar</button>';
                html += '</div>';

                contenido.innerHTML = html;
            })
            .catch(err => {
                console.error('Error:', err);
                contenido.innerHTML = '<p style="text-align: center; color: #dc2626; padding: 20px;"><?php echo t('sale_details.error_loading'); ?></p>';
            });
    }


    /**
     * Envía un ticket por correo electrónico
     */
    function enviarTicketCorreo(idVenta) {
        const email = prompt('<?php echo t('email.prompt_ticket'); ?>');
        if (!email || !email.includes('@')) { alert('<?php echo t('email.invalid_email'); ?>'); return; }

        fetch('api/ventas.php?detalleVenta=' + idVenta)
            .then(res => res.json())
            .then(data => {
                if (data.error) { alert('Error: ' + data.error); return; }

                const venta = data.venta;
                const lineas = data.lineas;

                const formData = new FormData();
                formData.append('idVenta', venta.id);
                formData.append('email', email);
                formData.append('tipo', venta.tipoDocumento || 'ticket');

                let productosData = [];
                lineas.forEach(item => {
                    productosData.push({ nombre: item.producto_nombre, cantidad: item.cantidad, precio: item.precioUnitario, subtotal: item.subtotal });
                });
                formData.append('productos', JSON.stringify(productosData));
                formData.append('total', venta.total);
                formData.append('fecha', venta.fecha);
                formData.append('metodoPago', venta.metodoPago);

                fetch('api/enviarCorreo.php', { method: 'POST', body: formData })
                    .then(res => res.json())
                    .then(response => { alert(response.ok ? 'Ticket enviado correctamente al correo: ' + email : 'Error al enviar el correo'); })
                    .catch(err => { console.error('Error:', err); alert('Error al enviar el correo'); });
            })
            .catch(err => { console.error('Error:', err); alert('Error al obtener los datos del ticket'); });
    }

    // Variable global para almacenar datos de venta del historial temporal
    let ventaHistorialTemporal = null;

    /**
     * Reimprime un ticket de una venta existente usando la función existente
     */
    function reimprimirTicket(idVenta) {
        fetch('api/ventas.php?detalleVenta=' + idVenta)
            .then(res => res.json())
            .then(data => {
                if (data.error) { alert('Error: ' + data.error); return; }
                const venta = data.venta;
                const lineas = data.lineas;
                ultimaVenta = {
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
                    pagoMixtoDesglose: venta.desglose_pago ? JSON.parse(venta.desglose_pago) : null,
                    carrito: lineas.map(l => ({
                        idProducto: l.idProducto,
                        nombre: l.producto_nombre,
                        precio: parseFloat(l.precioUnitario),
                        iva: (l.iva !== undefined && l.iva !== null && l.iva !== "") ? parseInt(l.iva) : 21,
                        cantidad: l.cantidad
                    }))
                };
                imprimirDocumento();
            })
            .catch(err => { console.error('Error:', err); alert('Error al obtener los datos del ticket'); });
    }

    /**
     * Muestra el modal para enviar un ticket por correo electrónico
     */
    function mostrarModalEnviarCorreo(idVenta) {
        console.log('Fetching sale details for ID:', idVenta);
        fetch('api/ventas.php?detalleVenta=' + idVenta)
            .then(res => {
                console.log('Response status:', res.status);
                return res.text();
            })
            .then(text => {
                console.log('Response text:', text);
                const data = JSON.parse(text);
                if (data.error) { alert('Error: ' + data.error); return; }
                const venta = data.venta;
                const lineas = data.lineas;
                ventaHistorialTemporal = {
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
                    carrito: lineas.map(l => ({
                        idProducto: l.idProducto,
                        nombre: l.producto_nombre,
                        precio: parseFloat(l.precioUnitario),
                        iva: (l.iva !== undefined && l.iva !== null && l.iva !== "") ? parseInt(l.iva) : 21,
                        cantidad: l.cantidad
                    }))
                };
                ultimaVenta = ventaHistorialTemporal;
                // Abrir primero el modal de éxito y luego mostrar el formulario de email
                document.getElementById('ventaExito').style.display = 'flex';
                mostrarFormEmail();
            })
            .catch(err => { console.error('Error:', err); alert('Error al obtener los datos del ticket: ' + err.message); });
    }

    // Variable para almacenar la devolución actual del historial
    let devolucionHistorialTemporal = null;

    /**
     * Muestra el modal con el historial de devoluciones de hoy
     */
    function mostrarHistorialDevoluciones() {
        const modal = document.getElementById('modalHistorialDevoluciones');
        const contenido = document.getElementById('historialDevolucionesContenido');
        const totalDiv = document.getElementById('historialDevolucionesTotal');
        const fechaDiv = document.getElementById('historialDevolucionesFecha');

        // Actualizar la fecha en el subtítulo
        const hoy = new Date();
        if (fechaDiv) {
            fechaDiv.textContent = '<?php echo t('history.returns_subtitle'); ?> - ' + hoy.toLocaleDateString('es-ES', { day: 'numeric', month: 'long', year: 'numeric' });
        }

        // Mostrar modal
        modal.style.display = 'flex';

        // Cargar devoluciones desde la API (la sesión se obtiene automáticamente en el servidor)
        fetch('api/devoluciones.php?historialSesion=1')
            .then(res => {
                console.log('Response status:', res.status);
                if (!res.ok) {
                    throw new Error('HTTP error ' + res.status);
                }
                return res.json();
            })
            .then(devoluciones => {
                console.log('Devoluciones:', devoluciones);
                if (devoluciones.error) {
                    if (devoluciones.error.includes('No hay sesión')) {
                        contenido.innerHTML = '<p style="text-align: center; color: var(--text-muted); padding: 40px;"><?php echo t('history.no_session'); ?></p>';
                        totalDiv.textContent = '';
                        return;
                    }
                    throw new Error(devoluciones.error);
                }
                if (!devoluciones || devoluciones.length === 0) {
                    contenido.innerHTML = '<p style="text-align: center; color: var(--text-muted); padding: 40px;"><?php echo t('history.no_returns'); ?></p>';
                    totalDiv.textContent = '<?php echo t('history.total'); ?>: 0.00 €';
                    return;
                }

                // Calcular total
                let total = 0;
                let html = '<table class="historial-ventas-tabla">';
                html += '<thead><tr>';
                html += '<th><?php echo t('history.time'); ?></th>';
                html += '<th><?php echo t('history.user'); ?></th>';
                html += '<th><?php echo t('history.products'); ?></th>';
                html += '<th><?php echo t('history.payment_method'); ?></th>';
                html += '<th><?php echo t('history.total_table'); ?></th>';
                html += '<th><?php echo t('history.actions'); ?></th>';
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
                totalDiv.textContent = '<?php echo t('history.total_returned'); ?>: -' + total.toFixed(2).replace('.', ',') + ' € (' + devoluciones.length + ' <?php echo t('history.returns'); ?>)';
            })
            .catch(err => {
                console.error('Error cargando historial:', err);
                contenido.innerHTML = '<p style="text-align: center; color: #dc2626; padding: 40px;"><?php echo t('history.error_loading'); ?>: ' + err.message + '</p>';
                totalDiv.textContent = '';
            });
    }

    /**
     * Muestra los detalles de una devolución específica en un modal
     */
    function verDetalleDevolucion(idVenta) {
        const modal = document.getElementById('modalDetalleDevolucion');
        const contenido = document.getElementById('detalleDevolucionContenido');

        modal.style.display = 'flex';
        contenido.innerHTML = '<p style="text-align: center; padding: 20px;"><?php echo t('return_details.loading'); ?></p>';

        fetch('api/devoluciones.php?detalleVenta=' + idVenta)
            .then(res => res.json())
            .then(data => {
                if (data.error) {
                    contenido.innerHTML = '<p style="text-align: center; color: #dc2626; padding: 20px;"><?php echo t('return_details.error'); ?>: ' + data.error + '</p>';
                    return;
                }

                if (!data || data.length === 0) {
                    contenido.innerHTML = '<p style="text-align: center; color: var(--text-muted); padding: 20px;"><?php echo t('return_details.not_found'); ?></p>';
                    return;
                }

                const primera = data[0];
                const fecha = new Date(primera.fecha).toLocaleString('es-ES');

                // Update header info
                const serie = primera.serie || 'T';
                const numero = primera.numero || primera.idVenta || idVenta;
                document.getElementById('detalleDevolucionId').textContent = '<?php echo t('return_details.return_name'); ?> ' + serie + String(numero).padStart(5, '0') + ' - ' + fecha;

                // Simple style matching other modals
                let html = '';

                // Info row
                html += '<div style="display: flex; gap: 15px; margin-bottom: 20px;">';
                html += '<div style="flex: 1; background: var(--bg-secondary); padding: 12px; border-radius: 8px;">';
                html += '<div style="font-size: 11px; color: var(--text-muted); margin-bottom: 4px;"><?php echo t('return_details.payment'); ?></div>';
                html += '<div style="font-weight: 600;">💵 ' + (primera.metodoPago || 'Efectivo') + '</div>';
                html += '</div>';
                html += '<div style="flex: 1; background: var(--bg-secondary); padding: 12px; border-radius: 8px;">';
                html += '<div style="font-size: 11px; color: var(--text-muted); margin-bottom: 4px;"><?php echo t('return_details.reason'); ?></div>';
                html += '<div style="font-weight: 600;">' + (primera.motivo || '<?php echo t('return_details.no_reason'); ?>') + '</div>';
                html += '</div>';
                html += '</div>';

                // Products table
                html += '<div style="max-height: 250px; overflow-y: auto; margin-bottom: 20px;">';
                html += '<table style="width: 100%; border-collapse: collapse;">';
                html += '<thead><tr style="background: var(--bg-secondary);">';
                html += '<th style="padding: 10px; text-align: left; font-size: 12px; color: var(--text-muted);"><?php echo t('return_details.product'); ?></th>';
                html += '<th style="padding: 10px; text-align: center; font-size: 12px; color: var(--text-muted);"><?php echo t('return_details.quantity'); ?></th>';
                html += '<th style="padding: 10px; text-align: right; font-size: 12px; color: var(--text-muted);"><?php echo t('return_details.price'); ?></th>';
                html += '<th style="padding: 10px; text-align: right; font-size: 12px; color: var(--text-muted);"><?php echo t('return_details.amount'); ?></th>';
                html += '</tr></thead><tbody>';

                let totalDevolucion = 0;
                data.forEach(item => {
                    const subtotal = parseFloat(item.importeTotal || 0);
                    totalDevolucion += subtotal;
                    html += '<tr style="border-bottom: 1px solid var(--border-main);">';
                    html += '<td style="padding: 10px;">' + (item.producto_nombre || '<?php echo t('return_details.product_default'); ?>') + '</td>';
                    html += '<td style="padding: 10px; text-align: center;">' + item.cantidad + '</td>';
                    html += '<td style="padding: 10px; text-align: right;">' + parseFloat(item.precioUnitario || 0).toFixed(2).replace('.', ',') + ' €</td>';
                    html += '<td style="padding: 10px; text-align: right; font-weight: 600; color: #ef4444;">-' + subtotal.toFixed(2).replace('.', ',') + ' €</td>';
                    html += '</tr>';
                });

                html += '</tbody></table>';
                html += '</div>';

                // Total
                html += '<div style="background: #ef4444; color: white; padding: 15px; border-radius: 8px; text-align: center; font-weight: bold; font-size: 18px;">';
                html += '<?php echo t('return_details.total_returned'); ?>: -' + totalDevolucion.toFixed(2).replace('.', ',') + ' €';
                html += '</div>';

                contenido.innerHTML = html;
            })
            .catch(err => {
                console.error('Error:', err);
                contenido.innerHTML = '<p style="text-align: center; color: #dc2626; padding: 20px;"><?php echo t('return_details.error_loading'); ?></p>';
            });
    }

    /**
     * Reimprime un ticket de devolución desde el historial
     */
    function reimprimirTicketDevolucionDesdeHistorial(idVenta) {
        console.log('🔴 reimprimirTicketDevolucionDesdeHistorial llamada para idVenta: ', idVenta);

        fetch('api/devoluciones.php?detalleVenta=' + idVenta + '&_=' + Date.now(), {
            cache: 'no-store',
            headers: { 'Cache-Control': 'no-cache' }
        })
            .then(res => res.json())
            .then(data => {
                if (data.error) { alert('Error: ' + data.error); return; }
                if (!data || data.length === 0) { alert('<?php echo t('return_details.alert_not_found'); ?>'); return; }

                const primera = data[0];
                const serie = primera.serie || 'T';
                const numero = primera.numero || idVenta;
                devolucionHistorialTemporal = {
                    id: idVenta,
                    serie: serie,
                    numero: numero,
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
                // ✅ CORREGIDO: No sobrescribimos variable const, pasamos objeto directamente
                imprimirDocumentoDevolucionConDatos(devolucionHistorialTemporal);
            })
            .catch(err => { console.error('Error:', err); alert('Error al obtener los datos del ticket'); });
    }

    /**
     * ✅ NUEVA FUNCION: Imprime ticket de devolucion SIN USAR VARIABLES GLOBALES
     * Recibe el objeto de devolucion directamente como parametro
     * Usada desde el historial de devoluciones
     */
    function imprimirDocumentoDevolucionConDatos(devolucion) {
        if (!devolucion) {
            alert('No hay datos de devolución para imprimir');
            return;
        }

        const fecha = new Date(devolucion.fecha).toLocaleString('es-ES');

        let lineasHtml = '';
        if (devolucion.lineas && devolucion.lineas.length > 0) {
            devolucion.lineas.forEach(linea => {
                const cant = linea.cantidad || 0;
                const prec = linea.precioUnitario || 0;
                const imp = linea.importeTotal || 0;
                const precFmt = parseFloat(prec).toFixed(2).replace('.', ',');
                const impFmt = parseFloat(imp).toFixed(2).replace('.', ',');
                lineasHtml += `
                    <tr>
                        <td>${linea.nombre || 'Producto'}</td>
                        <td style="text-align:center">${cant}</td>
                        <td style="text-align:right">${precFmt} €</td>
                        <td style="text-align:right">${impFmt} €</td>
                    </tr>
                `;
            });
        }

        const totalesHtml = `
            <table style="width: 100%; border-top: 2px solid #000; margin-top: 10px; padding-top: 5px;">
                <tr>
                    <td style="font-size: 1.2rem; font-weight: bold;">TOTAL DEVUELTO:</td>
                    <td style="font-size: 1.2rem; font-weight: bold; text-align:right; color: #dc2626;">-${parseFloat(devolucion.total).toFixed(2).replace('.', ',')} €</td>
                </tr>
            </table>
        `;

        let obsHtml = '';
        if (devolucion.motivo) {
            obsHtml = `<div style="margin-top: 15px; font-size: 0.8rem;"><strong>Motivo:</strong> ${devolucion.motivo}</div>`;
        }

        const ticketOriginal = (devolucion.serie || '') + String(devolucion.numero || devolucion.id || '').padStart(5, '0');

        const contenido = `
        <html>
        <head>
            <title>TICKET DE DEVOLUCIÓN ${ticketOriginal}</title>
            <style>
                body { font-family: 'Inter', 'Helvetica Neue', Helvetica, Arial, sans-serif; padding: 20px; color: #1a1a1a; max-width: 80mm; margin: 0 auto; line-height: 1.4; }
                .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 15px; }
                .header h1 { margin: 0; font-size: 1.2rem; text-transform: uppercase; color: #dc2626; }
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
                <h1><?php echo mb_strtoupper(t('return_details.title')); ?></h1>
            </div>
            
            <div class="datos">
                <strong>TPV Bazar — Productos Informáticos</strong><br>
                NIF: B12345678<br>
                C/ Falsa 123, 23000 León<br>
                <div style="margin-top: 10px;">
                    <p><strong><?php echo t('resume.original_ticket'); ?>:</strong> ${ticketOriginal}</p>
                    <p><strong><?php echo t('resume.date'); ?>:</strong> ${fecha}</p>
                    <p><strong><?php echo t('return_details.payment'); ?>:</strong> ${devolucion.metodoPago}</p>
                </div>
            </div>

            <table class="tabla-lineas">
                <thead>
                    <tr><th><?php echo t('return_details.product'); ?></th><th style="text-align:center"><?php echo t('return_details.quantity'); ?></th><th style="text-align:right"><?php echo t('return_details.price'); ?></th><th style="text-align:right"><?php echo t('return_details.amount'); ?></th></tr>
                </thead>
                <tbody>${lineasHtml}</tbody>
            </table>
            
            ${totalesHtml}
            ${obsHtml}

            <div class="footer">
                <p><?php echo t('return_details.refund_method'); ?> ${devolucion.metodoPago}.</p>
                <p><?php echo t('return_details.keep_ticket'); ?></p>
            </div>
        </body>
        </html>
        `;

        const printWindow = window.open('', '_blank', 'width=400,height=600');
        if (!printWindow) {
            alert('<?php echo t('print.allow_popups'); ?>');
            return;
        }

        printWindow.document.write(contenido);
        printWindow.document.close();
        printWindow.print();
    }

    /**
     * Función para imprimir el documento de devolución (MANTENIDA PARA COMPATIBILIDAD)
     * Sigue funcionando normalmente para cuando se hace una devolución nueva
     */
    function imprimirDocumentoDevolucion() {
        if (!ultimaDevolucion) {
            alert('<?php echo t('return_details.alert_not_found'); ?>');
            return;
        }
        imprimirDocumentoDevolucionConDatos(ultimaDevolucion);
    }

    /**
     * Obtiene el ID de la sesión de caja actual
     */
    function obtenerIdSesionCaja() {
        // Intentar obtener de la variable global de sesión
        if (typeof idSesionCajaActual !== 'undefined' && idSesionCajaActual) {
            return idSesionCajaActual;
        }
        // Intentar obtener del elemento en el DOM
        const sesionElement = document.getElementById('idSesionCajaActual');
        if (sesionElement) {
            return sesionElement.value;
        }
        return null;
    }
</script>

<!-- ##=========================== SCRIPT PRINCIPAL DEL CAJERO ===========================## -->
<!-- Contiene toda la lógica JavaScript del carrito, descuentos, cobro, impresión,
     envío por correo, devoluciones y utilidades -->
<script>
    /**
     * roundTo(num, dec)
     * Redondea un número al número de decimales especificado.
     * @param {number} num - El número a redondear
     * @param {number} dec - Número de decimales (por defecto 2)
     * @returns {number} Número redondeado
     */
    function roundTo(num, dec) {
        if (isNaN(num)) return 0;
        const factor = Math.pow(10, dec || 2);
        return Math.round(num * factor) / factor;
    }

    /**
     * round2(num)
     * Redondea un número a 2 decimales.
     * @param {number} num - El número a redondear
     * @returns {number} Número redondeado a 2 decimales
     */
    function round2(num) {
        return roundTo(num, 2);
    }

    /**
     * obtenerDecimalesMaximosCarrito()
     * Determina la precisión decimal necesaria basándose en los productos del carrito.
     * Devuelve el máximo de decimales de los productos del carrito, con un mínimo de 2.
     * @returns {number} Cantidad de decimales (2 a 4)
     */
    function obtenerDecimalesMaximosCarrito() {
        if (!typeof carrito !== 'undefined' || !carrito || carrito.length === 0) return 2;
        const max = Math.max(...carrito.map(item => item.decimales || 2));
        return Math.max(2, max);
    }

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
     * Tarifas prefijadas cargadas desde la base de datos
     */
    const tarifasPrefijadas = <?php echo json_encode($tarifas); ?>;

    /**
     * Efectivo actual en caja para validaciones (p.ej. devoluciones).
     */
    const efectivoActualCaja = <?php echo $sesionCaja ? $sesionCaja->getImporteActual() : 0; ?>;

    /**
     * agregarAlCarrito(elemento)
     * Añade un producto al carrito o incrementa su cantidad si ya existe.
     * Lee los datos del producto desde los atributos data-* del elemento HTML.
     * Valida que no se exceda el stock máximo disponible.
     * @param {HTMLElement} elemento - La tarjeta de producto clickeada
     */
    function agregarAlCarrito(elemento) {
        const id = parseInt(elemento.dataset.id) || 0;
        const nombre = elemento.dataset.nombre || 'Producto sin nombre';
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
                alert('<?php echo t('cart.alert_no_more_stock'); ?>');
                return;
            }
            existente.cantidad++;
        } else {
            if (stockMax <= 0) {
                alert('<?php echo t('cart.alert_no_stock_available'); ?>');
                return;
            }
            carrito.push({
                idProducto: id,
                nombre: nombre,
                precio: precioBase,
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
        if (carrito.length === 0) return;
        if (confirm('<?php echo t('cart.confirm_empty'); ?>')) {
            carrito = [];

            // Desvincular cliente y resetar descuentos
            descuento = { tipo: 'ninguno', valor: 0, cupon: '' };
            descuentoTarifa = { tipo: 'ninguno', valor: 0, cupon: '' };
            desvincularCliente();

            // Resetear también el select de tarifa global a Cliente
            const tarifaCliente = tarifasPrefijadas.find(t => t.nombre === 'Cliente');
            if (tarifaCliente) {
                const selectTarifa = document.getElementById('tarifaVenta');
                if (selectTarifa) selectTarifa.value = tarifaCliente.id;
            }

            actualizarTicket();
        }
    }

    // Función global para resetear puntos canjeados después de una venta exitosa
    function resetearPuntosCanjeados() {
        puntosCanjeados = null;
    }

    /**
     * posponerVenta()
     * Guarda la venta actual en sessionStorage para recuperarla después.
     * Guarda: carrito, descuento, tarifa, cliente DNI, puntos canjeados y puntos ganados.
     */
    function posponerVenta() {
        if (carrito.length === 0) {
            alert('<?php echo t('cart.alert_no_products_postpone'); ?>');
            return;
        }

        // Obtener DNI del cliente
        const clienteDniInput = document.getElementById('clienteNif');
        const clienteDni = clienteDniInput ? clienteDniInput.value.trim() : '';

        // Calcular puntos que se ganarán con la compra actual
        let puntosGanados = 0;
        if (clienteDni !== '') {
            const totalTicket = obtenerTotalCalculado();
            puntosGanados = Math.round(totalTicket * 10);
        }

        // Obtener puntos canjeados si existen
        const puntosCanjeadosData = (typeof puntosCanjeados !== 'undefined' && puntosCanjeados)
            ? { dni: puntosCanjeados.dni, puntos: puntosCanjeados.puntos }
            : null;

        // Generar ID único para la venta pospuesta
        const ventaId = Date.now();

        const ventaPospuesta = {
            id: ventaId,
            carrito: carrito,
            descuento: descuento,
            tarifa: document.getElementById('tarifaVenta').value,
            clienteDni: clienteDni,
            puntosCanjeados: puntosCanjeadosData,
            puntosGanados: puntosGanados,
            fecha: new Date().toLocaleString('es-ES')
        };

        // Obtener ventas pospuestas existentes o crear array vacío
        let ventasPospuestas = [];
        const ventasJson = sessionStorage.getItem('ventasPospuestas');
        if (ventasJson) {
            try {
                ventasPospuestas = JSON.parse(ventasJson);
            } catch (e) {
                ventasPospuestas = [];
            }
        }

        // Añadir nueva venta pospuesta al array
        ventasPospuestas.push(ventaPospuesta);

        // Guardar en sessionStorage como array
        sessionStorage.setItem('ventasPospuestas', JSON.stringify(ventasPospuestas));

        // Vaciar el carrito y resetear datos del cliente
        carrito = [];
        descuento = { tipo: 'ninguno', valor: 0, cupon: '' };
        puntosCanjeados = null;

        // Limpiar datos del cliente para que no se mezclen con otras ventas
        const clienteNifInput = document.getElementById('clienteNif');
        if (clienteNifInput) clienteNifInput.value = '';
        const clienteNombreInput = document.getElementById('clienteNombre');
        if (clienteNombreInput) clienteNombreInput.value = '';
        // Ocultar indicador de cliente
        const indicador = document.getElementById('indicadorClienteDni');
        if (indicador) indicador.style.display = 'none';

        const tarifaCliente2 = tarifasPrefijadas.find(t => t.nombre === 'Cliente');
        if (tarifaCliente2) {
            document.getElementById('tarifaVenta').value = tarifaCliente2.id;
        } else if (tarifasPrefijadas.length > 0) {
            document.getElementById('tarifaVenta').value = tarifasPrefijadas[0].id;
        }
        actualizarTicket();

        // Mostrar mensaje
        const totalPospuestas = ventasPospuestas.length;
        alert('✅ <?php echo t('cart.postponed_success1'); ?> ' + totalPospuestas + ' <?php echo t('cart.postponed_success2'); ?>');

        // Actualizar estado del botón recuperar
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
            alert('<?php echo t('cart.alert_no_postponed_recover'); ?>');
            return;
        }

        // Crear el HTML del modal usando las clases CSS del sistema
        const isDark = document.body.classList.contains('dark-mode');
        const bgColor = isDark ? '#1f2937' : 'white';
        const textColor = isDark ? '#e5e7eb' : '#1a1a2e';
        const borderColor = isDark ? '#374151' : '#e5e7eb';
        const subTextColor = isDark ? '#9ca3af' : '#6b7280';

        const modalHtml = `
            <div id="modalVentasPospuestas" class="modal-overlay" style="display: flex;">
                <div class="modal-content" style="background: ${bgColor}; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.3); max-width: 500px; width: 90%; max-height: 80vh; overflow: hidden;">
                    <div style="padding: 20px; border-bottom: 1px solid ${borderColor}; display: flex; justify-content: space-between; align-items: center;">
                        <h2 style="margin: 0; font-size: 20px; color: ${textColor};"><?php echo t('cart.postponed_sales_title'); ?></h2>
                        <button onclick="cerrarModalVentasPospuestas()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: ${subTextColor};">&times;</button>
                    </div>
                    <div style="padding: 20px; overflow-y: auto; max-height: 60vh;">
                        ${ventasPospuestas.map((venta, index) => {
            const totalVenta = venta.carrito.reduce((sum, item) => sum + (item.pvpUnitario * item.cantidad), 0);
            const numProductos = venta.carrito.reduce((sum, item) => sum + item.cantidad, 0);
            return `
                                <div style="border: 1px solid ${borderColor}; border-radius: 8px; padding: 15px; margin-bottom: 15px; background: ${isDark ? '#111827' : '#f9fafb'};">
                                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px;">
                                        <div>
                                            <strong style="color: ${textColor};">${venta.serie || 'T'}${String(venta.numero || (index + 1)).padStart(5, '0')}</strong>
                                            <div style="font-size: 12px; color: ${subTextColor};">${venta.fecha}</div>
                                        </div>
                                        <div style="text-align: right;">
                                            <div style="font-size: 16px; font-weight: 600; color: #059669;">${totalVenta.toFixed(2)} €</div>
                                            <div style="font-size: 12px; color: ${subTextColor};">${numProductos} <?php echo t('cart.product_s'); ?></div>
                                        </div>
                                    </div>
                                    <div style="font-size: 13px; color: ${subTextColor}; margin-bottom: 10px;">
                                        ${venta.clienteDni ? '<?php echo t('cart.customer_label'); ?>: ' + venta.clienteDni : '<?php echo t('cart.no_customer'); ?>'}
                                    </div>
                                    <div style="display: flex; gap: 10px; margin-top: 12px;">
                                        <button onclick="recuperarVenta(${venta.id})" class="btn-tpv" style="background: #059669; flex: 1;">
                                            <i class="fas fa-reply"></i> <?php echo t('cart.recover_btn'); ?>
                                        </button>
                                        <button onclick="eliminarVentaPospuesta(${venta.id})" class="btn-tpv" style="background: #dc2626; flex: 1;">
                                            <i class="fas fa-trash"></i> <?php echo t('cart.delete_btn'); ?>
                                        </button>
                                    </div>
                                </div>
                            `;
        }).join('')}
                    </div>
                </div>
            </div>
        `;

        // Añadir el modal al body
        document.body.insertAdjacentHTML('beforeend', modalHtml);
    }

    /**
     * cerrarModalVentasPospuestas()
     * Cierra el modal de ventas pospuestas.
     */
    function cerrarModalVentasPospuestas() {
        const modal = document.getElementById('modalVentasPospuestas');
        if (modal) {
            modal.remove();
        }
    }

    /**
     * eliminarVentaPospuesta(id)
     * Elimina una venta pospuesta sin recuperarla.
     */
    function eliminarVentaPospuesta(id) {
        if (!confirm('<?php echo t('cart.confirm_delete_postponed'); ?>')) {
            return;
        }

        const ventasJson = sessionStorage.getItem('ventasPospuestas');
        let ventasPospuestas = [];

        if (ventasJson) {
            try {
                ventasPospuestas = JSON.parse(ventasJson);
            } catch (e) {
                ventasPospuestas = [];
            }
        }

        // Buscar la venta por ID
        const ventaIndex = ventasPospuestas.findIndex(v => v.id === id);
        if (ventaIndex === -1) {
            alert('No se encontró la venta pospuesta');
            return;
        }

        // Eliminar la venta del array
        ventasPospuestas.splice(ventaIndex, 1);
        sessionStorage.setItem('ventasPospuestas', JSON.stringify(ventasPospuestas));

        alert('✅ <?php echo t('cart.alert_postponed_deleted'); ?>');

        // Actualizar estado del botón
        actualizarBotonesPospuestos();

        // Si no quedan ventas, cerrar el modal
        if (ventasPospuestas.length === 0) {
            cerrarModalVentasPospuestas();
        } else {
            // Actualizar el modal mostrando las ventas restantes
            mostrarModalVentasPospuestas();
        }
    }

    /**
     * recuperarVenta(id)
     * Recupera una venta pospuesta específica y la vuelve al carrito.
     */
    function recuperarVenta(id) {
        const ventasJson = sessionStorage.getItem('ventasPospuestas');
        let ventasPospuestas = [];

        if (ventasJson) {
            try {
                ventasPospuestas = JSON.parse(ventasJson);
            } catch (e) {
                alert('<?php echo t('cart.alert_error_recovering'); ?>');
                return;
            }
        }

        // Buscar la venta por ID
        const ventaIndex = ventasPospuestas.findIndex(v => v.id === id);
        if (ventaIndex === -1) {
            alert('No se encontró la venta pospuesta');
            return;
        }

        const ventaPospuesta = ventasPospuestas[ventaIndex];

        // Sobrescribir el carrito directamente con los productos de la venta pospuesta
        // Primero vaciamos el carrito actual
        carrito = [];

        // Restaurar el carrito y descuento directamente
        carrito = ventaPospuesta.carrito;
        descuento = ventaPospuesta.descuento || { tipo: 'ninguno', valor: 0, cupon: '' };

        // Restaurar la tarifa seleccionada en el selector global
        if (ventaPospuesta.tarifa) {
            document.getElementById('tarifaVenta').value = ventaPospuesta.tarifa;
        } else {
            const tarifaCliente3 = tarifasPrefijadas.find(t => t.nombre === 'Cliente');
            if (tarifaCliente3) {
                document.getElementById('tarifaVenta').value = tarifaCliente3.id;
            } else if (tarifasPrefijadas.length > 0) {
                document.getElementById('tarifaVenta').value = tarifasPrefijadas[0].id;
            }
        }

        // Restaurar DNI del cliente si existe
        if (ventaPospuesta.clienteDni) {
            const clienteNifInput = document.getElementById('clienteNif');
            if (clienteNifInput) {
                clienteNifInput.value = ventaPospuesta.clienteDni;
                // Mostrar indicador de cliente
                mostrarDniEnTicket(ventaPospuesta.clienteDni);
            }
        } else {
            // Limpiar datos del cliente si no hay en la venta pospuesta
            const clienteNifInput = document.getElementById('clienteNif');
            if (clienteNifInput) clienteNifInput.value = '';
            const clienteNombreInput = document.getElementById('clienteNombre');
            if (clienteNombreInput) clienteNombreInput.value = '';
            const indicador = document.getElementById('indicadorClienteDni');
            if (indicador) indicador.style.display = 'none';
        }

        // Restaurar puntos canjeados si existen, sino limpiar
        if (ventaPospuesta.puntosCanjeados && ventaPospuesta.puntosCanjeados.dni && ventaPospuesta.puntosCanjeados.puntos > 0) {
            puntosCanjeados = {
                dni: ventaPospuesta.puntosCanjeados.dni,
                puntos: ventaPospuesta.puntosCanjeados.puntos
            };
        } else {
            puntosCanjeados = null;
        }

        actualizarTicket();

        // Cerrar el modal
        cerrarModalVentasPospuestas();

        // Eliminar la venta pospuesta del array
        ventasPospuestas.splice(ventaIndex, 1);
        sessionStorage.setItem('ventasPospuestas', JSON.stringify(ventasPospuestas));

        alert('✅ <?php echo t('cart.alert_recovered'); ?>: ' + ventaPospuesta.fecha);

        // Actualizar estado del botón recuperar
        actualizarBotonesPospuestos();
    }

    /**
     * actualizarBotonesPospuestos()
     * Habilita/deshabilita los botones de recuperar según si hay ventas pospuestas.
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
                    buscarProductos();
                }
            })
            .catch(err => console.error('Error verificando cambios IVA programados:', err));
    }

    // Inicializar botón de recuperar al cargar la página
    document.addEventListener('DOMContentLoaded', function () {
        actualizarBotonesPospuestos();
        verificarCambiosIvaProgramados();
    });

    /**
     * obtenerTotalCalculado()
     * Calcula el total del carrito aplicando el descuento vigente.
     * Los descuentos se aplican sobre la BASE IMPONIBLE (sin IVA), y luego se suma el IVA.
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
            contenedor.innerHTML = '<p class="ticket-vacio"><?php echo t('cart.empty_message'); ?></p>';
            document.getElementById('ticketDesglose').innerHTML = '';
            totalEl.textContent = '0,00 €';
            btnCobrar.disabled = true;
            btnDescuento.disabled = true;
            // Si se vacía el carrito, resetear los puntos canjeados también
            puntosCanjeados = null;
            descuento = { tipo: 'ninguno', valor: 0, cupon: '' };
            document.getElementById('btnPosponer').disabled = true;
            return;
        }

        // Generar tabla HTML con las líneas del ticket
        let html = '<table class="ticket-tabla"><thead><tr><th><?php echo t('cart.product_th'); ?></th><th><?php echo t('cart.quantity_th'); ?></th><th><?php echo t('cart.price_th'); ?></th><th><?php echo t('cart.subtotal_th'); ?></th><th></th></tr></thead><tbody>';

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
            <span><?php echo t('cart.tax_base'); ?>:</span>
            <span>${baseImponibleCalculada.toFixed(precTotal).replace('.', ',')} €</span>
        </div>`;

        // Mostrar ahorros por tarifa si existen
        if (ahorroTarifasTotal > 0.005) {
            for (const [nombre, importe] of Object.entries(ahorrosTarifasAgrupados)) {
                htmlDesglose += `
                <div class="resumen-fila-mini descuento-texto">
                    <span><?php echo t('cart.saving'); ?> ${nombre}:</span>
                    <span>- ${importe.toFixed(precTotal).replace('.', ',')} €</span>
                </div>`;
            }
        }

        // Subtotal tras tarifas solo si hay cupón manual
        if (descuentoManualImporte > 0.005) {
            const textoManual = descuento.tipo === 'porcentaje' ? '<?php echo t('cart.discount'); ?> (' + descuento.valor + '%)' : '<?php echo t('cart.coupon'); ?> ' + (descuento.cupon || '<?php echo t('cart.manual'); ?>');
            htmlDesglose += `
            <div class="resumen-fila-mini descuento-texto" style="display: flex; justify-content: space-between; align-items: center;">
                <span style="color: #16a34a;">
                    <span style="cursor: pointer; color: #ef4444; margin-right: 5px;" onclick="quitarDescuento()" title="<?php echo t('cart.remove_discount_title'); ?>">
                        <i class="fas fa-times-circle"></i>
                    </span>${textoManual}:
                </span>
                <span style="color: #16a34a;">- ${descuentoManualImporte.toFixed(precTotal).replace('.', ',')} €</span>
            </div>`;
        }

        htmlDesglose += `
            <div class="resumen-fila-mini">
                <span><?php echo t('cart.tax'); ?>:</span>
                <span>${ivaTotal.toFixed(precTotal).replace('.', ',')} €</span>
            </div>
            <div class="resumen-fila-mini" style="font-weight: bold; border-top: 1px solid #e5e7eb; padding-top: 8px;">
                <span><?php echo t('cart.final_total'); ?>:</span>
                <span>${totalPVPFinal.toFixed(precTotal).replace('.', ',')} €</span>
            </div>`;

        // Añadir puntos previstos a ganar si el cliente está identificado
        const nifActual = document.getElementById('clienteNif') ? document.getElementById('clienteNif').value.trim() : '';
        if (nifActual !== '' && totalPVPFinal > 0) {
            const puntosAGanar = Math.round(totalPVPFinal * 10);
            htmlDesglose += `
            <div class="resumen-fila-mini" style="color: #059669; font-weight: 600; font-size: 0.85rem; padding-top: 4px;">
                <span><?php echo t('cart.points_to_earn'); ?>:</span>
                <span>+${puntosAGanar.toLocaleString('es-ES')} pts</span>
            </div>`;
        }

        htmlDesglose += `</div>`;

        // Actualizar el DOM
        document.getElementById('ticketDesglose').innerHTML = htmlDesglose;
        totalEl.textContent = totalPVPFinal.toFixed(precTotal).replace('.', ',') + ' €';

        // Verificar si se supera el límite de 1.000€ en efectivo
        // verificarLimiteEfectivo();

        // Habilitar botones de cobro y descuento
        btnCobrar.disabled = false;
        btnDescuento.disabled = false;
        document.getElementById('btnPosponer').disabled = false;
    }

    // ======================== DESCUENTOS ========================

    /**
     * aplicarDescuento()
     * Abre el modal de descuento si hay productos en el carrito.
     */
    function aplicarDescuento() {
        if (carrito.length === 0) return;
        document.getElementById('modalDescuento').style.display = 'flex';

        // Mostrar/ocultar botón de quitar descuento según si hay descuento activo
        const btnQuitar = document.getElementById('btnQuitarDescuento');
        if (descuento && descuento.tipo !== 'ninguno' && descuento.valor > 0) {
            btnQuitar.style.display = 'block';
        } else {
            btnQuitar.style.display = 'none';
        }

        document.getElementById('inputPorcentajeDescuento').focus();
    }

    /**
     * quitarDescuento()
     * Elimina el descuento activo y actualiza el ticket.
     */
    function quitarDescuento() {
        // Resetear descuento
        descuento = { tipo: 'ninguno', valor: 0, cupon: '' };

        // Limpiar los inputs del modal
        document.getElementById('inputPorcentajeDescuento').value = '';
        document.getElementById('inputCuponDescuento').value = '';

        // Cerrar modal y actualizar ticket
        cerrarModal('modalDescuento');
        actualizarTicket();
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
                alert('<?php echo t('cart.alert_discount_range'); ?>');
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
                alert('<?php echo t('cart.alert_invalid_coupon'); ?>');
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
                alert('<?php echo t('cart.alert_cash_limit_exceeded'); ?>');
                return;
            }
            // Mostrar modal para calcular el cambio
            mostrarModalCambio();
        } else if (metodoPago === 'mixto') {
            // Mostrar modal de pago mixto para distribuir entre métodos
            mostrarModalPagoMixto();
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
        const precTotal = obtenerDecimalesMaximosCarrito();
        document.getElementById('cambioTotalPagar').textContent = total.toFixed(precTotal).replace('.', ',') + ' €';

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
     * fijarImporteExacto()
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
     * calcularCambio()
     * Calcula en tiempo real el cambio a devolver según la cantidad entregada.
     * Muestra un mensaje de error si la cantidad es insuficiente.
     */
    function calcularCambio() {
        const total = obtenerTotalCalculado();
        const precTotal = obtenerDecimalesMaximosCarrito();
        const entregado = parseFloat(document.getElementById('inputDineroEntregado').value) || 0;
        const devolucion = entregado - total;
        // Usar un pequeño epsilon o redondear para evitar errores de precisión en punto flotante
        const devolucionRedondeada = roundTo(devolucion, precTotal);

        const spanDevolver = document.getElementById('cambioDevolver');
        const errorMsg = document.getElementById('cambioError');

        if (devolucionRedondeada < 0 && entregado > 0) {
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
                spanDevolver.textContent = devolucionRedondeada.toFixed(precTotal).replace('.', ',') + ' €';
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

        // Validar que el entregado cubra el total (usando redondeo para evitar errores de precisión)
        if (Math.round(entregado * 100) < Math.round(total * 100)) {
            document.getElementById('cambioError').style.display = 'block';
            return;
        }

        // Cerrar modal de cambio y abrir el nuevo Centro de Finalización de Venta
        cerrarModal('modalCambio');
        abrirModalFinalizarVenta();
    }

    // ======================== PAGO MIXTO ========================

    /** Almacena el desglose del pago mixto: {efectivo, tarjeta, bizum, cambio} */
    let pagoMixtoDesglose = null;

    /**
     * mostrarModalPagoMixto()
     * Inicializa y muestra el modal para distribuir el pago entre múltiples métodos.
     */
    function mostrarModalPagoMixto() {
        const total = obtenerTotalCalculado();
        const precTotal = obtenerDecimalesMaximosCarrito();
        document.getElementById('mixtoTotalDistribuir').textContent = total.toFixed(precTotal).replace('.', ',') + ' €';

        // Resetear campos
        document.getElementById('mixtoEfectivo').value = '';
        document.getElementById('mixtoTarjeta').value = '';
        document.getElementById('mixtoBizum').value = '';
        document.getElementById('mixtoError').style.display = 'none';
        document.getElementById('mixtoAvisoEfectivo').style.display = 'none';

        // Resetear indicador restante
        const restanteValor = document.getElementById('mixtoRestanteValor');
        restanteValor.textContent = total.toFixed(precTotal).replace('.', ',') + ' €';

        const container = document.getElementById('mixtoRestanteContainer');
        container.style.background = 'var(--bg-accent-danger)';
        document.getElementById('mixtoRestanteLabel').style.color = 'var(--accent-danger)';
        document.getElementById('mixtoRestanteSub').style.color = 'var(--accent-danger)';
        restanteValor.style.color = 'var(--accent-danger)';
        document.getElementById('mixtoRestanteLabel').textContent = '<?php echo t('cart.mixed_remaining_assign'); ?>';
        document.getElementById('mixtoRestanteSub').textContent = '<?php echo t('cart.mixed_distribute_full'); ?>';

        // Mostrar modal y enfocar primer campo
        document.getElementById('modalPagoMixto').style.display = 'flex';
        setTimeout(() => document.getElementById('mixtoEfectivo').focus(), 100);
    }

    /**
     * calcularRestanteMixto()
     * Calcula en tiempo real cuánto queda por asignar y actualiza el indicador visual.
     */
    function calcularRestanteMixto() {
        const total = obtenerTotalCalculado();
        const precTotal = obtenerDecimalesMaximosCarrito();
        const efectivo = parseFloat(document.getElementById('mixtoEfectivo').value) || 0;
        const tarjeta = parseFloat(document.getElementById('mixtoTarjeta').value) || 0;
        const bizum = parseFloat(document.getElementById('mixtoBizum').value) || 0;

        const asignado = roundTo(efectivo + tarjeta + bizum, precTotal);
        const restante = roundTo(total - asignado, precTotal);

        const restanteValor = document.getElementById('mixtoRestanteValor');
        const container = document.getElementById('mixtoRestanteContainer');
        const label = document.getElementById('mixtoRestanteLabel');
        const sub = document.getElementById('mixtoRestanteSub');
        const errorEl = document.getElementById('mixtoError');

        // Aviso límite efectivo
        const avisoEfectivo = document.getElementById('mixtoAvisoEfectivo');
        avisoEfectivo.style.display = (efectivo > 1000) ? 'block' : 'none';

        if (restante > 0.005) {
            // Falta por asignar
            container.style.background = 'var(--bg-accent-danger)';
            label.style.color = 'var(--accent-danger)';
            sub.style.color = 'var(--accent-danger)';
            restanteValor.style.color = 'var(--accent-danger)';
            label.textContent = '<?php echo t('cart.mixed_remaining_assign'); ?>';
            sub.textContent = '<?php echo t('cart.mixed_distribute_full'); ?>';
            restanteValor.textContent = restante.toFixed(precTotal).replace('.', ',') + ' €';
            errorEl.style.display = 'none';
        } else if (restante < -0.005) {
            // Excedente (cambio)
            const cambio = Math.abs(restante);
            container.style.background = 'var(--bg-accent-success)';
            label.style.color = 'var(--accent-success)';
            sub.style.color = 'var(--accent-success)';
            restanteValor.style.color = 'var(--accent-success)';
            label.textContent = '<?php echo t('cart.mixed_change_return'); ?>';
            sub.textContent = '<?php echo t('cart.mixed_cash_excess'); ?>';
            restanteValor.textContent = cambio.toFixed(precTotal).replace('.', ',') + ' €';
            errorEl.style.display = 'none';
        } else {
            // Exacto
            container.style.background = 'var(--bg-accent-success)';
            label.style.color = 'var(--accent-success)';
            sub.style.color = 'var(--accent-success)';
            restanteValor.style.color = 'var(--accent-success)';
            label.textContent = '✓ <?php echo t('cart.mixed_total_covered'); ?>';
            sub.textContent = '<?php echo t('cart.mixed_exact_assigned'); ?>';
            restanteValor.textContent = '0,00 €';
            errorEl.style.display = 'none';
        }
    }

    /**
     * confirmarPagoMixto()
     * Valida la distribución y avanza al modal de finalización de venta.
     */
    function confirmarPagoMixto() {
        const total = obtenerTotalCalculado();
        const precTotal = obtenerDecimalesMaximosCarrito();
        const efectivo = parseFloat(document.getElementById('mixtoEfectivo').value) || 0;
        const tarjeta = parseFloat(document.getElementById('mixtoTarjeta').value) || 0;
        const bizum = parseFloat(document.getElementById('mixtoBizum').value) || 0;
        const errorEl = document.getElementById('mixtoError');

        const asignado = roundTo(efectivo + tarjeta + bizum, precTotal);

        // Validar que la suma cubra el total
        if (Math.round(asignado * 100) < Math.round(total * 100)) {
            errorEl.textContent = '<?php echo t('cart.mixed_error_not_covered'); ?>: ' + roundTo(total - asignado, precTotal).toFixed(precTotal).replace('.', ',') + ' €';
            errorEl.style.display = 'block';
            return;
        }

        // Validar límite de efectivo
        if (efectivo > 1000) {
            errorEl.textContent = '<?php echo t('cart.mixed_error_cash_limit'); ?>';
            errorEl.style.display = 'block';
            return;
        }

        // Validar que al menos 2 métodos tengan importe (sino no tiene sentido "mixto")
        const metodosUsados = [efectivo, tarjeta, bizum].filter(v => v > 0).length;
        if (metodosUsados < 2) {
            errorEl.textContent = '<?php echo t('cart.mixed_error_two_methods'); ?>';
            errorEl.style.display = 'block';
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
        abrirModalFinalizarVenta();
    }

    // ======================== MODAL TIPO DOCUMENTO / CLIENTE ========================

    /**
     * mostrarModalTipoDocumento()
     * Muestra el modal para elegir entre Ticket o Factura.
     * Valida que haya productos en el carrito y que la caja esté abierta.
     * Si el total es >= 20€ y no hay cliente, pregunta por los puntos primero.
     */
    function mostrarModalTipoDocumento() {
        if (carrito.length === 0) return;

        // Verificar que la caja esté abierta antes de permitir ventas
        if (!cajaAbierta) {
            alert('<?php echo t('cart.alert_box_closed'); ?>');
            return;
        }

        // Verificar si el total es mayor a 20€ y no hay cliente registrado para preguntar por puntos
        const total = obtenerTotalCalculado();
        const clienteNif = document.getElementById('clienteNif').value.trim();

        if (total >= 20 && !clienteNif) {
            // Mostrar modal de puntos antes del tipo de documento
            mostrarModalPuntos();
            return;
        }

        abrirModalFinalizarVenta();
    }

    // ======================== NUEVO FLUJO DE FINALIZAR VENTA ========================
    let metodoEntregaActual = 'imprimir'; // 'imprimir' o 'email'
    let proximosNumeros = { ticket: 'T00000', factura: 'F00000' };

    /**
     * abrirModalFinalizarVenta()
     * Inicializa y muestra el nuevo modal de finalización con vista previa.
     */
    function abrirModalFinalizarVenta() {
        if (carrito.length === 0) return;

        // Resetear selecciones
        tipoDocumentoActual = 'ticket';
        metodoEntregaActual = 'imprimir';

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
        document.getElementById('optTicket').classList.add('active');
        document.getElementById('optImprimir').classList.add('active');

        document.getElementById('emailContainerCheckout').style.display = 'none';
        document.getElementById('infoImpresoraCheckout').style.display = 'block';

        // Actualizar resumen de cliente
        actualizarResumenClienteCheckout();

        // Renderizar vista previa inicial
        renderizarVistaPreviaTicket();

        // Mostrar modal principal
        document.getElementById('modalFinalizarVenta').style.display = 'flex';
    }

    /**
     * actualizarResumenClienteCheckout()
     * Actualiza el pequeño recuadro de datos de cliente en el modal de checkout.
     */
    function actualizarResumenClienteCheckout() {
        const nif = document.getElementById('clienteNif').value.trim();
        const nombre = document.getElementById('clienteNombre').value.trim();
        const textEl = document.getElementById('clientDataTextCheckout');

        if (nif || nombre) {
            textEl.innerHTML = `<div style="color:var(--text-main); font-weight:600;">${nombre || '<?php echo t('cart.no_name'); ?>'}</div><div style="font-size:0.8rem;">${nif || '<?php echo t('cart.no_nif'); ?>'}</div>`;
        } else {
            textEl.textContent = '<?php echo t('cart.no_customer_assigned'); ?>';
        }
    }

    /**
     * abrirDatosClienteDesdeCheckout()
     * Abre el modal de datos de cliente y prepara el retorno al checkout al terminar.
     */
    function abrirDatosClienteDesdeCheckout() {
        cerrarModal('modalFinalizarVenta');
        window.retornarAlCheckout = true; // Flag para volver aquí después
        seleccionarDatosCliente(tipoDocumentoActual);
    }

    /**
     * cerrarModalDatosClienteAtras()
     * Maneja el botón 'Atrás' en el modal de datos de cliente.
     */
    function cerrarModalDatosClienteAtras() {
        cerrarModal('modalDatosCliente');
        if (window.retornarAlCheckout) {
            window.retornarAlCheckout = false;
            document.getElementById('modalFinalizarVenta').style.display = 'flex';
        } else {
            // Flujo normal previo
            document.getElementById('modalTipoDoc').style.display = 'flex';
        }
    }

    /**
     * cambiarTipoDocumentoCheckout(tipo)
     * Cambia entre 'ticket' y 'factura' en el modo checkout.
     */
    function cambiarTipoDocumentoCheckout(tipo) {
        tipoDocumentoActual = tipo;

        // Actualizar botones
        document.getElementById('optTicket').classList.toggle('active', tipo === 'ticket');
        document.getElementById('optFactura').classList.toggle('active', tipo === 'factura');

        // Si elige factura y no hay datos de cliente, abrir modal de datos ANTES de permitir finalizar
        // Pero primero actualizamos la vista previa
        renderizarVistaPreviaTicket();
    }

    /**
     * cambiarMetodoEntregaCheckout(metodo)
     * Cambia entre 'imprimir' y 'email'.
     */
    function cambiarMetodoEntregaCheckout(metodo) {
        metodoEntregaActual = metodo;

        // Actualizar botones
        document.getElementById('optImprimir').classList.toggle('active', metodo === 'imprimir');
        document.getElementById('optEmail').classList.toggle('active', metodo === 'email');

        // Mostrar/ocultar contenedores específicos
        document.getElementById('emailContainerCheckout').style.display = (metodo === 'email') ? 'block' : 'none';
        document.getElementById('infoImpresoraCheckout').style.display = (metodo === 'imprimir') ? 'block' : 'none';

        if (metodo === 'email') {
            document.getElementById('emailCheckout').focus();
        }
    }

    /**
     * construirObjetoVentaTemporal(tipoDoc)
     * Crea un objeto con la estructura de 'ultimaVenta' a partir de los datos actuales del carrito
     * y el formulario de cliente para poder previsualizar el documento fielmente.
     */
    function construirObjetoVentaTemporal(tipoDoc) {
        const totalPVP = obtenerTotalCalculado();
        const nif = document.getElementById('clienteNif').value.trim();
        const nombre = document.getElementById('clienteNombre').value.trim();
        const direccion = document.getElementById('clienteDireccion').value.trim();
        const observaciones = document.getElementById('clienteObservaciones').value.trim();
        const mensajePersonalizado = document.getElementById('mensajePersonalizadoVenta').value.trim();

        // Clonar y preparar líneas de carrito
        const lineas = carrito.map(item => ({
            ...item,
            precio: parseFloat(item.precio),
            pvpUnitario: parseFloat(item.pvpUnitario),
            cantidad: parseFloat(item.cantidad),
            iva: (item.iva !== undefined && item.iva !== null && item.iva !== "") ? parseInt(item.iva) : 21
        }));

        return {
            id: proximosNumeros[tipoDoc],
            numero: proximosNumeros[tipoDoc].replace(/\D/g, ''),
            serie: proximosNumeros[tipoDoc].replace(/\d/g, ''),
            fecha: new Date().toLocaleDateString('es-ES') + ' ' + new Date().toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' }),
            tipo: tipoDoc,
            metodoPago: document.getElementById('metodoPago').value,
            entregado: totalPVP,
            cambio: 0,
            carrito: lineas,
            total: totalPVP,
            clienteNif: nif,
            clienteNombre: nombre,
            clienteDir: direccion,
            clienteObs: observaciones,
            clientePuntos: parseInt(document.getElementById('clientePuntos').value) || 0,
            descuentoTipo: descuento.tipo,
            descuentoValor: descuento.valor,
            descuentoCupon: descuento.cupon,
            puntosGanados: totalPVP >= 20 ? Math.round(totalPVP * 10) : 0,
            puntosCanjeados: puntosCanjeados,
            mensajePersonalizado: mensajePersonalizado,
            pagoMixtoDesglose: (document.getElementById('metodoPago').value === 'mixto') ? pagoMixtoDesglose : null
        };
    }

    /**
     * parseEsp()
     * Parsea un numero en formato español (1.234,56) a float de JS (1234.56).
     */
    function parseEsp(val) {
        if (typeof val === 'number') return val;
        if (!val || typeof val !== 'string') return 0;
        return parseFloat(val.replace(/\./g, '').replace(',', '.'));
    }

    /**
     * cerrarExito()
     * Limpia la sesion y vuelve al estado inicial.
     */
    function cerrarExito() {
        window.location.href = 'index.php?v=cajero';
    }

    /**
     * generarDesgloseFormaPago(datosVenta, isFactura)
     * Genera el HTML para la sección de método de pago en el comprobante.
     * Si es pago mixto, muestra el desglose por método; si no, muestra una línea simple.
     */
    function generarDesgloseFormaPago(datosVenta, isFactura) {
        const metodoLabels = { efectivo: '💵 <?php echo t('print.cash'); ?>', tarjeta: '💳 <?php echo t('print.card'); ?>', bizum: '📱 <?php echo t('print.bizum'); ?>' };
        const precTotal = 2;

        if (datosVenta.metodoPago === 'mixto' && datosVenta.pagoMixtoDesglose) {
            const d = datosVenta.pagoMixtoDesglose;
            let rows = '';
            if (d.efectivo > 0) rows += `<tr><td style="padding:3px 0; font-size:${isFactura ? '12px' : '10px'};">💵 <?php echo t('print.cash'); ?></td><td style="text-align:right; padding:3px 0; font-size:${isFactura ? '12px' : '10px'};">${d.efectivo.toFixed(precTotal).replace('.', ',')} €</td></tr>`;
            if (d.tarjeta > 0) rows += `<tr><td style="padding:3px 0; font-size:${isFactura ? '12px' : '10px'};">💳 <?php echo t('print.card'); ?></td><td style="text-align:right; padding:3px 0; font-size:${isFactura ? '12px' : '10px'};">${d.tarjeta.toFixed(precTotal).replace('.', ',')} €</td></tr>`;
            if (d.bizum > 0) rows += `<tr><td style="padding:3px 0; font-size:${isFactura ? '12px' : '10px'};">📱 <?php echo t('print.bizum'); ?></td><td style="text-align:right; padding:3px 0; font-size:${isFactura ? '12px' : '10px'};">${d.bizum.toFixed(precTotal).replace('.', ',')} €</td></tr>`;
            if (d.cambio > 0) rows += `<tr><td style="padding:3px 0; font-size:${isFactura ? '12px' : '10px'}; color:#888;"><?php echo t('print.change_returned'); ?></td><td style="text-align:right; padding:3px 0; font-size:${isFactura ? '12px' : '10px'}; color:#888;">-${d.cambio.toFixed(precTotal).replace('.', ',')} €</td></tr>`;

            if (isFactura) {
                return `<div style="clear:both; margin-top:30px;">
                    <p style="font-size:13px; color:#444; font-weight:bold; margin-bottom:6px;"><?php echo mb_strtoupper(t('print.payment_method')); ?>: MIXTO</p>
                    <table style="width:auto; border-collapse:collapse;">${rows}</table>
                </div>`;
            } else {
                return `<div style="margin-top:10px; border-top:1px dashed #000; padding-top:6px;">
                    <div style="font-size:10px; font-weight:bold; margin-bottom:4px;"><?php echo mb_strtoupper(t('print.payment_method')); ?>: MIXTO</div>
                    <table style="width:100%; border-collapse:collapse;">${rows}</table>
                </div>`;
            }
        }

        // Pago simple
        const label = metodoLabels[datosVenta.metodoPago] || datosVenta.metodoPago.toUpperCase();
        if (isFactura) {
            return `<p style="clear:both; margin-top:30px; font-size:12px; color:#666;"><?php echo t('print.payment_method'); ?>: ${label}</p>`;
        } else {
            return `<div style="margin-top:8px; font-size:10px; text-align:center;"><?php echo t('print.payment_method'); ?>: ${label}</div>`;
        }
    }

    /**
     * generarHTMLComprobante(datosVenta)
     * Genera el HTML completo (con <style> e <html>) para un ticket o factura.
     * ÚNICA FUENTE DE VERDAD para el formato de impresión.
     */
    function generarHTMLComprobante(datosVenta) {
        const isFactura = (datosVenta.tipo === 'factura');
        const tipoTitulo = isFactura ? '<?php echo t('print.factura_title'); ?>' : '<?php echo t('print.ticket_title'); ?>';

        // --- LÓGICA DE CÁLCULO ---
        const precTotal = 2; // Siempre 2 para ticket/factura impresos
        let lineasHtmlTicket = '';
        let lineasHtmlFactura = '';
        let sumaTotalesNumeric = 0;
        let desgloseIva = {};

        datosVenta.carrito.forEach(item => {
            const cant = parseFloat(item.cantidad) || 0;
            const dec = 2; // Forzar 2 decimales en ticket/factura
            const precioBaseUnitario = parseFloat(item.precio) || 0; // Precio sin IVA
            const ivaPorc = (item.iva !== undefined && item.iva !== null && item.iva !== "") ? parseInt(item.iva) : 21;

            // Si pvpUnitario no viene (ej: historial), lo calculamos del base + iva
            let pvpUnitario = parseFloat(item.pvpUnitario) || 0;
            if (pvpUnitario === 0 && precioBaseUnitario > 0) {
                pvpUnitario = roundTo(precioBaseUnitario * (1 + (ivaPorc / 100)), dec);
            }

            // Cálculos
            const subtotalPVP = (item.importeTotal !== undefined) ? parseFloat(item.importeTotal) : roundTo(pvpUnitario * cant, dec);
            const subtotalBase = roundTo(subtotalPVP / (1 + (ivaPorc / 100)), dec);
            const subtotalIva = roundTo(subtotalPVP - subtotalBase, dec);

            sumaTotalesNumeric += subtotalPVP;
            if (!desgloseIva[ivaPorc]) desgloseIva[ivaPorc] = { base: 0, cuota: 0 };
            desgloseIva[ivaPorc].base += subtotalBase;
            desgloseIva[ivaPorc].cuota += subtotalIva;

            // Fila para TICKET: 5 columnas (Art, Ud, Base, IVA%, Total)
            lineasHtmlTicket += `<tr>
                <td style="padding: 6px 4px; font-size: 11px;">${item.nombre}</td>
                <td style="text-align:center; font-size: 11px;">${cant}</td>
                <td style="text-align:right; font-size: 11px; padding-right: 12px;">${precioBaseUnitario.toFixed(dec).replace('.', ',')}€</td>
                <td style="text-align:center; font-size: 11px; padding-left: 12px;">${ivaPorc}%</td>
                <td style="text-align:right; font-size: 11px;">${subtotalPVP.toFixed(dec).replace('.', ',')}€</td>
            </tr>`;

            // Fila para FACTURA: 5 columnas (Descripción, Cant, Unitario_Base, IVA%, Importe)
            lineasHtmlFactura += `<tr>
                <td style="padding: 10px 5px;">${item.nombre}</td>
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
                    <td><?php echo t('print.base_at'); ?> ${porc}%:</td>
                    <td style="text-align:right">${desgloseIva[porc].base.toFixed(precTotal).replace('.', ',')} €</td>
                </tr>
                <tr style="font-size: 0.8rem; color: #444;">
                    <td><?php echo t('print.iva_quote'); ?> (${porc}%):</td>
                    <td style="text-align:right">${desgloseIva[porc].cuota.toFixed(precTotal).replace('.', ',')} €</td>
                </tr>`;
        });

        // --- DESCUENTOS Y PUNTOS ---
        if (datosVenta.descuentoValor > 0 && datosVenta.descuentoCupon && !datosVenta.descuentoCupon.startsWith('PUNTOS_')) {
            let descImporte = 0;
            let descLabel = '';
            if (datosVenta.descuentoTipo === 'porcentaje') {
                descImporte = round2(sumaTotalesNumeric * (datosVenta.descuentoValor / 100));
                descLabel = `<?php echo t('print.dto'); ?> (${datosVenta.descuentoValor}%):`;
            } else {
                descImporte = datosVenta.descuentoValor;
                descLabel = '<?php echo t('print.discount'); ?>:';
            }
            totalesHtml += `
                <tr style="font-size: 0.85rem; color: #d32f2f;">
                    <td>${descLabel}</td>
                    <td style="text-align:right">-${descImporte.toFixed(precTotal).replace('.', ',')} €</td>
                </tr>`;
        }

        if (datosVenta.puntosCanjeados) {
            totalesHtml += `
                <tr style="font-size: 0.85rem; color: #d32f2f;">
                    <td><?php echo t('print.points_exchange'); ?> (${datosVenta.puntosCanjeados.puntos} pts):</td>
                    <td style="text-align:right">-${parseFloat(datosVenta.puntosCanjeados.descuento).toFixed(precTotal).replace('.', ',')} €</td>
                </tr>`;
        }

        totalesHtml += `
            <tr style="border-top: 2px solid #000;">
                <td style="font-size: 1.1rem; padding-top:8px;"><strong><?php echo t('print.total'); ?>:</strong></td>
                <td style="font-size: 1.1rem; font-weight: bold; text-align:right; padding-top:8px;">${parseEsp(datosVenta.total).toFixed(precTotal).replace('.', ',')} €</td>
            </tr>
        </table>`;

        const paddedNum = (datosVenta.numero && !isNaN(datosVenta.numero)) ? String(datosVenta.numero).padStart(5, '0') : (datosVenta.numero || datosVenta.id);
        const numComprobante = (datosVenta.serie || '') + paddedNum;

        // Priorizar el balance guardado en la venta (historial) o calcularlo (para preview)
        let finalPuntosBalance = parseInt(datosVenta.puntosBalance);
        if (isNaN(finalPuntosBalance)) {
            finalPuntosBalance = (parseInt(datosVenta.clientePuntos) || 0) - (parseInt(datosVenta.puntosCanjeados?.puntos) || 0) + (parseInt(datosVenta.puntosGanados) || 0);
        }

        const puntosFooterHtml = datosVenta.clienteNif ? `
            <div style="margin-top:10px; border-top:1px dashed #ccc; padding-top:5px; font-size:10px;">
                ${datosVenta.puntosGanados > 0 ? `<div><?php echo t('print.earned_points'); ?>: <strong>+${datosVenta.puntosGanados}</strong></div>` : ''}
                <div><?php echo t('print.new_balance'); ?>: <strong>${finalPuntosBalance.toLocaleString('es-ES')}</strong></div>
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
                <div class="two-col"><div class="col"><h3><?php echo t('print.emitter'); ?></h3><p><strong>TPV Bazar</strong></p><p>NIF: B12345678</p><p>C/ Falsa 123, Madrid</p></div>
                <div class="col" style="text-align:right"><div style="font-size: 18px; font-weight: bold;">Nº ${numComprobante}</div><div style="color:#666"><?php echo t('print.date'); ?>: ${datosVenta.fecha}</div></div></div>
                <div style="background:#f8fafc; padding:15px; border-radius:8px; margin-bottom:20px;">
                    <h3><?php echo t('print.receiver'); ?></h3>
                    <p><strong>${datosVenta.clienteNombre || '<?php echo t('print.no_name'); ?>'}</strong></p>
                    ${datosVenta.clienteNif ? `<p>NIF: ${datosVenta.clienteNif}</p>` : ''}
                    ${datosVenta.clienteDir ? `<p>${datosVenta.clienteDir}</p>` : ''}
                    ${puntosFooterHtml ? `<div style="margin-top:10px; padding-top:10px; border-top:1px solid #e5e7eb;">${puntosFooterHtml}</div>` : ''}
                </div>
                <table><thead><tr><th><?php echo t('print.description_th'); ?></th><th style="text-align:center"><?php echo t('print.cant_th'); ?></th><th style="text-align:right"><?php echo t('print.base_ud_th'); ?></th><th style="text-align:center">IVA</th><th style="text-align:right"><?php echo t('print.importe_th'); ?></th></tr></thead>
                <tbody>${lineasHtmlFactura}</tbody></table>
                 <div style="float:right; width: 45%;">${totalesHtml}</div>
                 ${generarDesgloseFormaPago(datosVenta, true)}
                 
                 
        ${datosVenta.mensajePersonalizado ? `
            <div style="clear:both; margin-top:20px; padding:12px; border-top:1px dashed #ccc; border-bottom:1px dashed #ccc; text-align:center; font-style:italic; color:#444; word-wrap: break-word; overflow-wrap: break-word; line-height: 1.5;">
                ${datosVenta.mensajePersonalizado.replace(/(.{45})/g, "$1<br>")}
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
                        <h1>TPV Bazar</h1>
                        <div style="font-size:9px;">NIF: B12345678 | C/ Falsa 123, Madrid</div>
                        <div style="font-size:10px; margin-top:4px; font-weight:bold;">${tipoTitulo}</div>
                    </div>
                    <div class="flex-row"><span>Nº: ${numComprobante}</span><span>${datosVenta.fecha}</span></div>
                    ${datosVenta.clienteNombre ? `<div style="margin-bottom:8px; font-size:10px; border:1px solid #eee; padding:4px;"><strong><?php echo t('print.client'); ?>:</strong> ${datosVenta.clienteNombre}</div>` : ''}
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 35%;"><?php echo t('print.art_th'); ?></th>
                                <th style="text-align:center; width: 10%;"><?php echo t('print.ud_th'); ?></th>
                                <th style="text-align:right; width: 20%; padding-right: 5px;"><?php echo t('print.base_th'); ?></th>
                                <th style="text-align:center; width: 15%; padding-left: 5px;">IVA</th>
                                <th style="text-align:right; width: 20%;"><?php echo t('print.total_th'); ?></th>
                            </tr>
                        </thead>
                        <tbody>${lineasHtmlTicket}</tbody>
                    </table>
                    ${totalesHtml}
        ${puntosFooterHtml}

        ${datosVenta.mensajePersonalizado ? `
            <div style="margin-top:15px; padding:10px; border-top:1px dashed #ccc; border-bottom:1px dashed #ccc; text-align:center; font-style:italic; word-wrap: break-word; overflow-wrap: break-word; line-height: 1.4;">
                ${datosVenta.mensajePersonalizado.replace(/(.{45})/g, "$1<br>")}
            </div>
        ` : ''}

        ${generarDesgloseFormaPago(datosVenta, false)}

        <div class="footer"><p><?php echo t('print.thanks_for_purchase'); ?></p></div>
                </div>
            </body></html>`;
        }
    }

    /** Estado global de zoom para la vista previa */
    let ticketZoomed = false;
    let ticketEsGrandeLocal = false;
    let timeoutEscalaTicket = null;

    /**
     * toggleZoomTicket()
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
     * ajustarEscalaTicket(previewContainer, esNuevaMedicion)
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
     * renderizarVistaPreviaTicket()
     * Actualiza la previsualización del modal de cobro usando un iframe para exactitud 1:1.
     */
    function renderizarVistaPreviaTicket() {
        const previewContainer = document.getElementById('ticketPreviewContent');
        const badge = document.getElementById('tipoDocBadgeCheckout');
        const isFactura = (tipoDocumentoActual === 'factura');

        // Limpieza de estados y timers previos para evitar condiciones de carrera y errores de null
        ticketZoomed = false;
        ticketEsGrandeLocal = false;
        if (timeoutEscalaTicket) {
            clearTimeout(timeoutEscalaTicket);
            timeoutEscalaTicket = null;
        }

        const datosMock = construirObjetoVentaTemporal(tipoDocumentoActual);
        const fullHTML = generarHTMLComprobante(datosMock);

        previewContainer.className = 'paper-simulation ' + (isFactura ? 'tipo-factura' : 'tipo-ticket');
        badge.textContent = isFactura ? 'FACTURA A4' : 'TICKET TÉRMICO';
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
                // Comprobación defensiva antes de acceder al iframe (puede haber sido eliminado del DOM)
                if (!iframe || !iframe.contentWindow || !iframe.contentWindow.document) return;

                const body = iframe.contentWindow.document.body;
                if (!body) return;

                iframe.style.height = body.scrollHeight + 'px';

                // Aplicamos el ajuste de escala/zoom con medición nueva
                ajustarEscalaTicket(previewContainer, true);
            }, 180);
        };

        const totalEl = document.getElementById('checkoutTotalAmount');
        const precTotal = obtenerDecimalesMaximosCarrito();
        if (totalEl) totalEl.textContent = datosMock.total.toFixed(2).replace('.', ',') + ' €';

    }

    /**
     * procesarVentaFinal()
     * Realiza las comprobaciones finales y dispara el envío de la venta.
     */
    function procesarVentaFinal() {
        // Obtener mensaje personalizado
        const mensajePersonalizado = document.getElementById('mensajePersonalizadoVenta').value.trim();
        document.getElementById('inputMensajePersonalizado').value = mensajePersonalizado;

        const nif = document.getElementById('clienteNif').value.trim();
        const nombre = document.getElementById('clienteNombre').value.trim();
        const direccion = document.getElementById('clienteDireccion').value.trim();

        // 1. Validar Factura
        if (tipoDocumentoActual === 'factura') {
            if (!nif || !nombre || !direccion) {
                // Si faltan datos, redirigir al modal de datos del cliente
                // Marcamos flag para que al terminar vuelva al checkout
                window.retornarAlCheckout = true;
                cerrarModal('modalFinalizarVenta');
                seleccionarDatosCliente('factura');
                return;
            }
        }

        // 2. Validar Email si está seleccionado
        if (metodoEntregaActual === 'email') {
            const email = document.getElementById('emailCheckout').value.trim();
            if (!email || !email.includes('@')) {
                alert('<?php echo t('cart.alert_valid_email'); ?>');
                return;
            }
            // Sincronizar con el input global por si se usa después
            const inputEmailGlobal = document.getElementById('inputEmail');
            if (inputEmailGlobal) inputEmailGlobal.value = email;
        }

        // 3. Sincronizar preferencias en localStorage para persistir tras el reload
        localStorage.setItem('tpv_post_sale_action', JSON.stringify({
            imprimir: (metodoEntregaActual === 'imprimir'),
            email: (metodoEntregaActual === 'email'),
            emailDestino: document.getElementById('emailCheckout').value.trim()
        }));

        // 4. Proceder con el registro
        const observaciones = document.getElementById('clienteObservaciones').value.trim();

        cerrarModal('modalFinalizarVenta');
        confirmarVenta(tipoDocumentoActual, nif, nombre, direccion, observaciones, mensajePersonalizado);
    }

    /** Variable que almacena el tipo de documento seleccionado ('ticket' o 'factura') */
    let tipoDocumentoActual = 'ticket';

    /**
     * Buscar cliente por DNI en el checkout
     */
    function buscarDatosCliente() {
        const dniBusqueda = document.getElementById('buscarDniCliente').value.trim();
        const msgEl = document.getElementById('mensajeBusquedaClienteDatos');

        if (!dniBusqueda) {
            msgEl.style.display = 'block';
            msgEl.style.color = '#ef4444';
            msgEl.textContent = '<?php echo t('cart.alert_valid_dni'); ?>';
            return;
        }

        msgEl.style.display = 'block';
        msgEl.style.color = '#3b82f6';
        msgEl.textContent = '<?php echo t('cart.searching'); ?>';

        fetch('api/clientes.php?dni=' + encodeURIComponent(dniBusqueda))
            .then(res => res.json())
            .then(data => {
                if (data && !data.error && data.length > 0) {
                    const cliente = data.find(c => c.dni.toUpperCase() === dniBusqueda.toUpperCase()) || data[0];

                    document.getElementById('clienteNif').value = cliente.dni;
                    document.getElementById('clienteNombre').value = cliente.nombre + ' ' + cliente.apellidos;
                    document.getElementById('clienteDireccion').value = cliente.direccion || '';
                    if (document.getElementById('clientePuntos')) {
                        document.getElementById('clientePuntos').value = cliente.puntos || 0;
                    }

                    msgEl.style.color = '#10b981';
                    msgEl.textContent = '<?php echo t('cart.client_found_filled'); ?>';
                } else {
                    msgEl.style.display = 'none';
                    if (confirm('<?php echo t('cart.confirm_add_client'); ?>')) {
                        document.getElementById('clienteHabitualDni').value = dniBusqueda;
                        abrirModalClienteHabitual();
                    }
                }
            })
            .catch(err => {
                console.error('Error buscando cliente:', err);
                msgEl.style.display = 'block';
                msgEl.style.color = '#ef4444';
                msgEl.textContent = '<?php echo t('cart.error_searching_client'); ?>';
            });
    }

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
            subTitulo.textContent = '<?php echo t('cart.complete_data_mandatory'); ?>';
            divDir.style.display = 'block';
            divObs.style.display = 'block';
            reqNif.style.display = 'inline';
            reqNombre.style.display = 'inline';
            reqDir.style.display = 'inline';
        } else {
            // Modo Ticket: ocultar campos extra y quitar indicadores de obligatorio
            subTitulo.textContent = '<?php echo t('cart.complete_data_optional'); ?>';
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

        // Cerrar modal de datos del cliente y proceder con la venta o volver al checkout
        cerrarModal('modalDatosCliente');

        if (window.retornarAlCheckout) {
            window.retornarAlCheckout = false;
            // Actualizar resumen y vista previa antes de volver
            actualizarResumenClienteCheckout();
            renderizarVistaPreviaTicket();
            document.getElementById('modalFinalizarVenta').style.display = 'flex';
        } else {
            confirmarVenta(tipoDocumentoActual, nif, nombre, direccion, observaciones);
        }
    }

    /**
     * mostrarModalPuntos()
     * Muestra el modal para preguntar si el cliente quiere registrar su DNI para acumular puntos.
     * Ahora salta directamente al nuevo modal de finalizar venta.
     */
    function mostrarModalPuntos() {
        // Saltar el modal de puntos y mostrar directamente el checkout
        abrirModalFinalizarVenta();
    }

    /**
     * confirmarConPuntos()
     * El cliente decide registrar su DNI para obtener puntos.
     */
    function confirmarConPuntos() {
        cerrarModal('modalPuntos');
        // Abrir modal para buscar cliente registrado
        abrirModalBuscarClienteRegistradoParaPuntos();
    }

    /**
     * confirmarSinPuntos()
     * El cliente decide no registrar su DNI para puntos.
     * Se cierra el modal y se muestra el nuevo checkout.
     */
    function confirmarSinPuntos() {
        cerrarModal('modalPuntos');
        // Mostrar nuevo modal de finalizar venta
        abrirModalFinalizarVenta();
    }

    /**
     * abrirModalBuscarClienteRegistradoParaPuntos()
     * Abre el modal de búsqueda de cliente para acumular puntos.
     */
    function abrirModalBuscarClienteRegistradoParaPuntos() {
        document.getElementById('dniBusquedaCliente').value = '';
        document.getElementById('mensajeResultadoBusqueda').style.display = 'none';
        // Cambiamos el título para indicar que es para puntos
        document.querySelector('#modalBuscarClienteRegistrado h3').textContent = '<?php echo t('cart.client_for_points'); ?>';
        document.querySelector('#modalBuscarClienteRegistrado .modal-subtitulo').textContent = '<?php echo t('cart.enter_dni_accumulate'); ?> ' + document.getElementById('puntosPosibles').textContent + ' <?php echo t('cart.points_text'); ?>';
        // Cambiamos el comportamiento del botón buscar
        document.getElementById('modalBuscarClienteRegistrado').dataset.modo = 'puntos';
        document.getElementById('modalBuscarClienteRegistrado').style.display = 'flex';
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
        } else if (metodoPago === 'mixto' && pagoMixtoDesglose) {
            // El entregado/cambio global lo usamos solo para referenciar el "dinero entregado"
            // si hay parte en efectivo.
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
        document.getElementById('inputCarrito').value = JSON.stringify(carrito);
        document.getElementById('inputMetodoPago').value = metodoPago;
        document.getElementById('inputTipoDocumento').value = tipoDocumento;
        document.getElementById('inputDineroEntregadoFinal').value = entregado.toFixed(precTotal);
        document.getElementById('inputCambioDevueltoFinal').value = cambio.toFixed(precTotal);

        // Pago mixto
        if (metodoPago === 'mixto' && pagoMixtoDesglose) {
            document.getElementById('inputDesglosePago').value = JSON.stringify(pagoMixtoDesglose);
        } else {
            document.getElementById('inputDesglosePago').value = '';
        }

        // Datos del cliente
        document.getElementById('inputClienteNifFinal').value = nif;
        document.getElementById('inputClienteNombreFinal').value = nombre;
        document.getElementById('inputClienteDireccionFinal').value = direccion;
        document.getElementById('inputObservacionesFinal').value = observaciones;

        // Datos del descuento manual (cupones, descuentos porcentuales globales, etc.)
        document.getElementById('inputDescuentoTipo').value = descuento.tipo;
        document.getElementById('inputDescuentoValor').value = descuento.valor;
        document.getElementById('inputDescuentoCupon').value = descuento.cupon;

        // Mantener campos legacy vacíos o con valores seguros para evitar errores en el backend si los espera
        document.getElementById('inputDescuentoTarifaTipo').value = 'ninguno';
        document.getElementById('inputDescuentoTarifaValor').value = 0;
        document.getElementById('inputDescuentoTarifaCupon').value = '';

        // Guardar descuento manual por separado también (redundancia de seguridad)
        document.getElementById('inputDescuentoManualTipo').value = descuento.tipo;
        document.getElementById('inputDescuentoManualValor').value = descuento.valor;
        document.getElementById('inputDescuentoManualCupon').value = descuento.cupon;

        // Guardar puntos canjeados si existen
        if (typeof puntosCanjeados !== 'undefined' && puntosCanjeados && puntosCanjeados.dni && puntosCanjeados.puntos > 0) {
            document.getElementById('inputPuntosCanjeadosDni').value = puntosCanjeados.dni;
            document.getElementById('inputPuntosCanjeadosCantidad').value = puntosCanjeados.puntos;
        } else {
            document.getElementById('inputPuntosCanjeadosDni').value = '';
            document.getElementById('inputPuntosCanjeadosCantidad').value = 0;
        }

        // Estado del cliente identificado en modal puntos
        let clienteIdentificadoPuntos = false;
        if (typeof clienteIdentificadoEnModalPuntos !== 'undefined') {
            clienteIdentificadoPuntos = !!clienteIdentificadoEnModalPuntos;
            document.getElementById('inputClienteIdentificadoPuntos').value = clienteIdentificadoPuntos ? 'true' : 'false';
        }

        // Calcular puntos ganados y balance final para guardar en la venta (SOLO si el cliente está identificado)
        const puntosGanados = (clienteIdentificadoPuntos && total >= 20) ? Math.round(total * 10) : 0;
        const puntosCanjeadosVal = parseInt(document.getElementById('inputPuntosCanjeadosCantidad').value) || 0;
        const puntosOriginales = (clienteIdentificadoPuntos) ? (parseInt(document.getElementById('clientePuntos').value) || 0) : 0;
        const puntosBalanceFinal = (clienteIdentificadoPuntos) ? (puntosOriginales - puntosCanjeadosVal + puntosGanados) : 0;

        document.getElementById('inputPuntosGanados').value = puntosGanados;
        document.getElementById('inputPuntosBalance').value = puntosBalanceFinal;

        // Tarifa seleccionada
        document.getElementById('inputIdTarifa').value = document.getElementById('tarifaVenta').value;

        // Enviar el formulario al servidor
        document.getElementById('formVenta').submit();

        // Resetear puntos canjeados después de enviar (para la próxima venta)
        if (typeof puntosCanjeados !== 'undefined') puntosCanjeados = null;
        if (typeof clienteIdentificadoEnModalPuntos !== 'undefined') clienteIdentificadoEnModalPuntos = false;
        if (typeof descuento !== 'undefined') descuento = { tipo: 'ninguno', valor: 0, cupon: '' };
    }

    /**
     * imprimirDocumento()
     * Genera un documento HTML formateado (ticket o factura) con los datos de la última venta
     * y lo envía a la impresora mediante un iframe oculto.
     * Usa la misma lógica de generación que la previsualización para asegurar paridad 1:1.
     */
    function imprimirDocumento() {
        if (typeof ultimaVenta === 'undefined') return;

        // Generar el contenido HTML usando la fuente única de verdad
        const contenido = generarHTMLComprobante(ultimaVenta);

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
            setTimeout(() => {
                if (iframe.parentNode) iframe.remove();
            }, 1000);
        };
    }


</script>

<script>
    function guardarNuevoProducto() {
        const nombre = document.getElementById('nuevoProductoNombre').value.trim();
        const categoria = document.getElementById('nuevoProductoCategoria').value;
        const precio = document.getElementById('nuevoProductoPrecio').value;
        const stock = document.getElementById('nuevoProductoStock').value;
        const iva = document.getElementById('nuevoProductoIva').value;
        const activo = document.getElementById('nuevoProductoEstado').value;
        const imgInput = document.getElementById('editProductoImagenInput');

        // Validar que los campos obligatorios no estén vacíos.
        if (!nombre || !categoria || !precio || stock === '') {
            alert('<?php echo t('products.alert_fill_mandatory'); ?>');
            return;
        }

        const formData = new FormData();
        formData.append('nombre', nombre);
        formData.append('categoria', categoria);
        formData.append('precio', precio);
        formData.append('stock', stock);
        formData.append('iva', iva);
        formData.append('activo', activo);
        if (imgInput.files[0]) {
            formData.append('imagen', imgInput.files[0]);
        }

        fetch('api/productos.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.ok) {
                    alert('<?php echo t('products.alert_created_successfully'); ?>');
                    cerrarModal('modalNuevoProducto');
                    // Recargar productos
                    location.reload();
                } else {
                    alert('<?php echo t('products.alert_error'); ?>: ' + (data.error ?? ''));
                }
            })
            .catch(err => {
                console.error('Error:', err);
                alert('<?php echo t('products.alert_error_creating'); ?>');
            });
    }
</script>
<script>

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
            statusEl.textContent = '<?php echo t('cart.alert_valid_email'); ?>';
            statusEl.className = 'email-status email-error';
            return;
        }

        // Generar el número de ticket con formato serieNumero
        const ventaIdNumero = (ultimaVenta.serie || 'T') + String(ultimaVenta.numero || ultimaVenta.id || '').padStart(5, '0');
        console.log('Enviando email con ventaId:', ventaIdNumero, 'serie:', ultimaVenta.serie, 'numero:', ultimaVenta.numero);

        // Mostrar estado "Enviando..."
        statusEl.textContent = '<?php echo t('cart.email_sending'); ?>';
        statusEl.className = 'email-status email-enviando';

        // Petición AJAX al endpoint de envío de correo
        // Añadir mensaje personalizado a los datos
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
                pagoMixtoDesglose: ultimaVenta.pagoMixtoDesglose || null
            })
        })
            .then(res => res.json())
            .then(data => {
                if (data.ok) {
                    // Envío exitoso
                    statusEl.textContent = '✓ <?php echo t('cart.email_sent_to'); ?> ' + email;
                    statusEl.className = 'email-status email-ok';
                } else {
                    // Error del servidor
                    statusEl.textContent = '✗ ' + (data.mensaje || '<?php echo t('cart.email_error_sending'); ?>');
                    statusEl.className = 'email-status email-error';
                }
            })
            .catch(err => {
                // Error de conexión
                statusEl.textContent = '✗ <?php echo t('cart.email_error_connection'); ?>';
                statusEl.className = 'email-status email-error';
            });
    }

    /**
    * mostrarFormEmailDevolucion()
    * Muestra el formulario de envío por correo electrónico dentro del modal de devolución exitosa.
    */
    function mostrarFormEmailDevolucion() {
        document.getElementById('formEmailDev').style.display = 'block';
        document.getElementById('inputEmailDev').focus();
    }

    /**
    * enviarPorCorreoDevolucion()
    * Envía los datos de la devolución por correo electrónico al cliente.
    */
    function enviarPorCorreoDevolucion() {
        if (typeof ultimaDevolucion === 'undefined') return;

        const email = document.getElementById('inputEmailDev').value.trim();
        const statusEl = document.getElementById('emailStatusDev');

        // Validación básica del email
        if (!email || !email.includes('@')) {
            statusEl.textContent = '<?php echo t('cart.alert_valid_email'); ?>';
            statusEl.style.color = '#ef4444';
            return;
        }

        // Mostrar estado "Enviando..."
        statusEl.textContent = '<?php echo t('cart.email_sending'); ?>';
        statusEl.style.color = '#3b82f6';

        // Petición AJAX al endpoint de envío de correo
        fetch('api/enviarCorreo.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                email: email,
                tipoDocumento: 'devolucion',
                ventaId: ultimaDevolucion.id || '',
                total: ultimaDevolucion.total,
                lineas: ultimaDevolucion.lineas,
                fecha: ultimaDevolucion.fecha,
                metodoPago: ultimaDevolucion.metodoPago,
                clienteObs: ultimaDevolucion.motivo // pasamos el motivo como observaciones
            })
        })
            .then(res => res.json())
            .then(data => {
                if (data.ok) {
                    // Envío exitoso
                    statusEl.textContent = '✓ <?php echo t('cart.email_sent_to'); ?> ' + email;
                    statusEl.style.color = '#10b981';
                } else {
                    // Error del servidor
                    statusEl.textContent = '✗ ' + (data.mensaje || '<?php echo t('cart.email_error_sending'); ?>');
                    statusEl.style.color = '#ef4444';
                }
            })
            .catch(err => {
                // Error de conexión
                statusEl.textContent = '✗ <?php echo t('cart.email_error_connection'); ?>';
                statusEl.style.color = '#ef4444';
            });
    }

    /**
    * imprimirTicketDevolucion()
    * Genera un iframe oculto e imprime el ticket de devolución usando ultimaDevolucion.
    */
    function imprimirTicketDevolucion() {
        if (typeof ultimaDevolucion === 'undefined') return;

        let lineasHtml = '';
        const dec = 2; // Redondear a 2 para ticket de devolución
        if (ultimaDevolucion.lineas && ultimaDevolucion.lineas.length > 0) {
            ultimaDevolucion.lineas.forEach(linea => {
                const dec = 2; // Forzar 2 decimales en ticket de devolución
                // Formatting depending on the object fields
                const cant = linea.cantidad || 0;
                const prec = linea.precio || 0;
                const imp = linea.importe || 0;

                // Formatear precios (asegurar que es numérico y usar replace para la coma)
                const precFmt = parseFloat(prec).toFixed(dec).replace('.', ',');
                const impFmt = parseFloat(imp).toFixed(dec).replace('.', ',');

                lineasHtml += `
                    <tr>
                        <td>${linea.nombre || 'Producto'}</td>
                        <td style="text-align:center">${cant}</td>
                        <td style="text-align:right">${precFmt} €</td>
                        <td style="text-align:right">${impFmt} €</td>
                    </tr>
                `;
            });
        }

        const totalesHtml = `
            <table style="width: 100%; border-top: 2px solid #000; margin-top: 10px; padding-top: 5px;">
                <tr>
                    <td style="font-size: 1.2rem; font-weight: bold;">TOTAL DEVUELTO:</td>
                    <td style="font-size: 1.2rem; font-weight: bold; text-align:right; color: #dc2626;">-${parseFloat(ultimaDevolucion.total).toFixed(2).replace('.', ',')} €</td>
                </tr>
            </table>
        `;

        let obsHtml = '';
        if (ultimaDevolucion.motivo) {
            obsHtml = `<div style="margin-top: 15px; font-size: 0.8rem;"><strong>Motivo:</strong> ${ultimaDevolucion.motivo}</div>`;
        }

        const contenido = `
        <html>
        <head>
            <title>TICKET DE DEVOLUCIÓN ${ultimaDevolucion.serie || 'T'}${String(ultimaDevolucion.numero || ultimaDevolucion.id || '').padStart(5, '0')}</title>
            <style>
                body { font-family: 'Inter', 'Helvetica Neue', Helvetica, Arial, sans-serif; padding: 20px; color: #1a1a1a; max-width: 80mm; margin: 0 auto; line-height: 1.4; }
                .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 15px; }
                .header h1 { margin: 0; font-size: 1.2rem; text-transform: uppercase; color: #dc2626; }
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
        <h1>${_t('return.ticket_title')}</h1>
            </div>
            
            <div class="datos">
                <strong>TPV Bazar — Productos Informáticos</strong><br>
                NIF: B12345678<br>
                C/ Falsa 123, 23000 León<br>
                <div style="margin-top: 10px;">
<p><strong>Nº Ticket Original:</strong> ${ultimaDevolucion.serie || 'T'}${String(ultimaDevolucion.numero || ultimaDevolucion.id || '').padStart(5, '0')}</p>
                    <p><strong>Fecha Operación:</strong> ${ultimaDevolucion.fecha}</p>
                    <p><strong>Método de pago:</strong> ${ultimaDevolucion.metodoPago}</p>
                </div>
            </div>

            <table class="tabla-lineas">
                <thead>
                    <tr><th>Desc.</th><th style="text-align:center">Cant</th><th style="text-align:right">Precio Base</th><th style="text-align:right">Subt.</th></tr>
                </thead>
                <tbody>${lineasHtml}</tbody>
            </table>
            
            ${totalesHtml}
            ${obsHtml}

            <div class="footer">
                <p>Las cantidades han sido reembolsadas mediante ${ultimaDevolucion.metodoPago}.</p>
                <p>Conserve este ticket como justificante.</p>
            </div>
        </body>
        </html>
        `;

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

    // ======================== CAJA ========================

    /**
    * mostrarModalAbrirCaja()
    * Muestra el modal de apertura de caja y enfoca el input del importe inicial.
    */
    function mostrarModalAbrirCaja() {
        document.getElementById('modalAbrirCaja').style.display = 'flex';
        // Resetear el formulario
        document.getElementById('cambioRecovery').value = '0';
        const importeInput = document.getElementById('importeInicial');
        if (importeInput) {
            importeInput.value = '';
            importeInput.required = false;
        }
        const divImporte = document.getElementById('divImporteInicial');
        if (divImporte) {
            divImporte.style.opacity = '0.5';
        }
        // Seleccionar opción de recuperar por defecto si existe
        const radioRecuperar = document.querySelector('input[name="opcionCambio"][value="recuperar"]');
        if (radioRecuperar) {
            radioRecuperar.checked = true;
            toggleCambio(false);
        }
        document.getElementById('importeInicial').focus();
    }

    function toggleCambio(mostrarNuevo) {
        const divImporte = document.getElementById('divImporteInicial');
        const importeInput = document.getElementById('importeInicial');
        const cambioInput = document.getElementById('cambioRecovery');

        if (mostrarNuevo) {
            divImporte.style.opacity = '1';
            importeInput.required = true;
            cambioInput.value = '0';
        } else {
            divImporte.style.opacity = '0.5';
            importeInput.required = false;
            // Obtener el valor del cambio anterior
            const cambioAnterior = <?php echo json_encode($cambioAnterior); ?>;
            cambioInput.value = cambioAnterior;
        }
    }


    /**
    * mostrarModalRetiro()
    * Muestra el modal de retiro de dinero y enfoca el input del importe.
    */
    function mostrarModalRetiro() {
        document.getElementById('modalRetiro').style.display = 'flex';
        document.getElementById('importeRetiro').focus();
    }

    /**
    * validarRetiro()
    * Valida que el importe a retirar no exceda el efectivo disponible.
    */
    function validarRetiro() {
        const importeInput = document.getElementById('importeRetiro');
        const importe = parseFloat(importeInput.value);
        const efectivoDisponible = <?php echo $sesionCaja ? $sesionCaja->getImporteActual() : 0; ?>;

        if (isNaN(importe) || importe <= 0) {
            alert('<?php echo t('cashier.alert_valid_amount'); ?>');
            return false;
        }

        if (importe > efectivoDisponible) {
            alert('<?php echo t('cashier.alert_insufficient_cash'); ?>: ' + efectivoDisponible.toFixed(2).replace('.', ',') + ' €');
            return false;
        }

        return true;
    }

    /**
    * Actualiza la interfaz visual de los chips de método de pago en el modal de devolución.
    * Resalta el chip seleccionado con borde rojo y desactiva los demás.
    * @param { HTMLInputElement } radio - El radio button seleccionado
            */
    function updateMethodUI(radio) {
        // Desactivar todos los chips (estilo gris)
        document.querySelectorAll('.method-chip').forEach(chip => {
            chip.style.border = '2px solid var(--border-main)';
            chip.style.color = 'var(--text-muted)';
            chip.style.fontWeight = '400';
            chip.classList.remove('active');
        });

        // Activar el chip seleccionado (estilo rojo)
        const chip = document.getElementById('chip-' + radio.value);
        if (chip) {
            chip.style.border = '2px solid var(--accent-danger)';
            chip.style.color = 'var(--accent-danger)';
            chip.style.fontWeight = '600';
            chip.classList.add('active');
        }
    }

    // ======================== NUEVA LÓGICA DE DEVOLUCIONES MULTI-PRODUCTO ========================

    let lineasVentaDevolucion = []; // Almacena las líneas cargadas del ticket
    let ticketActualDevolucion = null;

    /**
     * Muestra el modal de devolución y enfoca el input del ID del ticket.
     */
    function mostrarModalDevolucion() {
        document.getElementById('modalDevolucion').style.display = 'flex';
        document.getElementById('inputTicketIdDev').focus();
    }

    /**
     * Busca un ticket mediante su ID para iniciar el proceso de devolución.
     */
    function buscarTicketParaDevolucion() {
        const input = document.getElementById('inputTicketIdDev').value.trim();
        const errorEl = document.getElementById('errorTicketDev');

        if (!input) {
            errorEl.textContent = '<?php echo t('returns.error_no_ticket'); ?>';
            errorEl.style.display = 'block';
            return;
        }

        errorEl.style.display = 'none';

        // Parse correlative number format (e.g., "T00001" -> serie="T", numero=1)
        let serie = '';
        let numero = input;

        const match = input.match(/^([TF]?)0*(\d+)$/i);
        if (match) {
            serie = match[1].toUpperCase(); // Serie: T, F, o vacio
            numero = match[2]; // Numero sin ceros a la izquierda
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
                    errorEl.textContent = 'Error: ' + data.error;
                    errorEl.style.display = 'block';
                    return;
                }

                // Guardar datos
                ticketActualDevolucion = data.venta;
                lineasVentaDevolucion = data.lineas;

                // Verificar si hay productos disponibles para devolver
                const totalDisponible = lineasVentaDevolucion.reduce((acc, linea) => {
                    return acc + (parseInt(linea.cantidad) - (parseInt(linea.cantidad_devuelta) || 0));
                }, 0);

                if (totalDisponible <= 0) {
                    errorEl.textContent = '<?php echo t('returns.error_no_products_avail'); ?>';
                    errorEl.style.display = 'block';
                    return;
                }

                // Actualizar UI - Paso 2
                document.getElementById('devolucionPaso1').style.display = 'none';
                document.getElementById('devolucionPaso2').style.display = 'block';
                document.getElementById('btnConfirmarMultiDev').style.display = 'block';
                document.getElementById('resumenReembolso').style.display = 'block';
                document.getElementById('devolucionSubtitulo').textContent = '<?php echo t('returns.subtitle_select_units'); ?>';

                // Mostrar TICKET T00001 o FACTURA F00001 según la serie
                const serie = ticketActualDevolucion.serie || 'T';
                const tipoDoc = serie === 'F' ? '<?php echo t('print.factura'); ?>' : '<?php echo t('print.ticket'); ?>';
                const numero = String(ticketActualDevolucion.numero || ticketActualDevolucion.id).padStart(5, '0');
                const precGlobal = Math.max(2, ...lineasVentaDevolucion.map(l => l.decimales || 2));
                document.getElementById('infoTicketId').textContent = tipoDoc + ' ' + serie + numero;
                document.getElementById('infoTicketFecha').textContent = new Date(ticketActualDevolucion.fecha).toLocaleString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
                document.getElementById('infoTicketTotal').textContent = parseFloat(ticketActualDevolucion.total).toFixed(precGlobal).replace('.', ',') + ' €';
                // Also update the original total in the footer
                document.getElementById('totalOriginalDisplay').textContent = parseFloat(ticketActualDevolucion.total).toFixed(precGlobal).replace('.', ',') + ' €';

                renderizarTablaDevolucion();
            })
            .catch(err => {
                console.error(err);
                errorEl.textContent = '<?php echo t('returns.error_connection'); ?>';
                errorEl.style.display = 'block';
            });
    }

    /**
     * Renderiza la tabla de productos disponibles para devolver.
     */
    function renderizarTablaDevolucion() {
        const tbody = document.getElementById('tablaProductosDev');
        tbody.innerHTML = '';

        lineasVentaDevolucion.forEach((linea, index) => {
            const comprado = parseInt(linea.cantidad);
            const devuelto = parseInt(linea.cantidad_devuelta);
            const disponible = comprado - devuelto;

            // Usar precio con IVA (el que se pagó en el momento de la compra)
            const precioMostrar = linea.precioConIva ? parseFloat(linea.precioConIva) : parseFloat(linea.precioUnitario) * (1 + (linea.iva || 21) / 100);

            const dec = linea.decimales || 2;
            const tr = document.createElement('tr');
            tr.style.borderBottom = '1px solid #f1f5f9';
            tr.innerHTML = `
            <td style="padding: 12px 15px;">
                    <div class="producto-nombre-dev" style="font-weight: 600;">${linea.producto_nombre}</div>
                    <div class="producto-precio-dev" style="font-size: 0.75rem;">Precio: ${precioMostrar.toFixed(dec)} € (IVA incl.)</div>
                </td>
                <td style="padding: 12px; text-align: center; color: #64748b; font-weight: 500;">${disponible}</td>
                <td style="padding: 12px 15px; text-align: center;">
                    <div class="cantidad-control" style="justify-content: center;">
                        <button onclick="cambiarCantidadDev(${index}, -1)" ${disponible <= 0 ? 'disabled' : ''}>−</button>
                        <input type="number" class="cant-dev-input" 
                            data-index="${index}" 
                            min="0" max="${disponible}" value="0" 
                            style="width: 50px; text-align: center; padding: 5px; border: 1px solid #e2e8f0; border-radius: 4px;"
                            onchange="cambiarCantidadDev(${index}, 0)"
                            ${disponible <= 0 ? 'disabled' : ''}>
                        <button onclick="cambiarCantidadDev(${index}, 1)" ${disponible <= 0 ? 'disabled' : ''}>+</button>
                    </div>
                </td>
        `;
            tbody.appendChild(tr);
        });

        recalcularTotalReembolso();
    }

    /**
     * Selecciona todos los productos disponibles para devolver (toggle).
     */
    function seleccionarTodosProductos() {
        const inputs = document.querySelectorAll('.cant-dev-input');
        let allSelected = true;

        // Check if all products are already selected
        inputs.forEach(input => {
            const index = input.dataset.index;
            const linea = lineasVentaDevolucion[index];
            const disponible = parseInt(linea.cantidad) - parseInt(linea.cantidad_devuelta);
            if (parseInt(input.value) !== disponible) {
                allSelected = false;
            }
        });

        // Toggle: select all or deselect all
        inputs.forEach(input => {
            const index = input.dataset.index;
            const linea = lineasVentaDevolucion[index];
            const disponible = parseInt(linea.cantidad) - parseInt(linea.cantidad_devuelta);

            if (allSelected) {
                input.value = 0;
            } else {
                input.value = disponible;
            }
        });
        recalcularTotalReembolso();
    }

    /**
     * cambiarCantidadDev(index, delta)
     * Incrementa o decrementa la cantidad a devolver.
     * @param {number} index - Índice del producto en lineasVentaDevolucion
     * @param {number} delta - Cambio a aplicar (+1, -1, 0 para onchange)
     */
    function cambiarCantidadDev(index, delta) {
        const input = document.querySelector('.cant-dev-input[data-index="' + index + '"]');
        if (!input) return;

        const linea = lineasVentaDevolucion[index];
        const disponible = parseInt(linea.cantidad) - parseInt(linea.cantidad_devuelta);

        let cant = (parseInt(input.value) || 0) + delta;

        // Limitar entre 0 y el disponible
        if (cant < 0) cant = 0;
        if (cant > disponible) cant = disponible;

        input.value = cant;
        recalcularTotalReembolso();
    }

    /**
     * Calcula el total a reembolsar basándose en las cantidades introducidas.
     * El factor de descuento se calcula directamente como la ratio entre el total
     * real pagado (venta.total) y la suma bruta de todas las líneas a precio completo.
     * Esto cubre automáticamente cualquier tipo de descuento, canje de puntos, etc.
     */
    function recalcularTotalReembolso() {
        const precDevTotal = 2; // Forzar 2 decimales en documentos de reembolso
        let total = 0;
        let hayDevolucion = false;
        const inputs = document.querySelectorAll('.cant-dev-input');
        const venta = ticketActualDevolucion;
        const ventaTotal = parseFloat(venta.total);

        // Calcular la suma bruta de TODAS las líneas originales (precio completo con IVA, sin descuentos)
        let sumaBruta = 0;
        lineasVentaDevolucion.forEach(linea => {
            const precioBase = linea.precioConIva ? parseFloat(linea.precioConIva) : parseFloat(linea.precioUnitario) * (1 + (linea.iva || 21) / 100);
            sumaBruta += parseInt(linea.cantidad) * precioBase;
        });

        // Factor de descuento real = lo que pagó / lo que costarían todos los productos a precio completo
        const factorDescuento = sumaBruta > 0 ? ventaTotal / sumaBruta : 1;

        inputs.forEach(input => {
            const index = input.dataset.index;
            const linea = lineasVentaDevolucion[index];
            const comprado = parseInt(linea.cantidad);
            const devuelto = parseInt(linea.cantidad_devuelta);
            const disponible = comprado - devuelto;

            let cant = parseInt(input.value) || 0;

            // Forzar que no sea mayor al disponible
            if (cant > disponible) {
                cant = disponible;
                input.value = disponible;
            }
            if (cant < 0) {
                cant = 0;
                input.value = 0;
            }

            if (cant > 0) {
                const precioBase = linea.precioConIva ? parseFloat(linea.precioConIva) : parseFloat(linea.precioUnitario) * (1 + (linea.iva || 21) / 100);
                const precioConDescuento = precioBase * factorDescuento;
                total += cant * precioConDescuento;
                hayDevolucion = true;
            }
        });

        // Asegurar que el total no exceda lo que el cliente pagó (ya considerando devoluciones previas)
        if (total > ventaTotal) {
            total = ventaTotal;
        }

        // Redondear el total
        total = roundTo(total, precDevTotal);

        document.getElementById('totalReembolsoDisplay').textContent = total.toFixed(precDevTotal).replace('.', ',') + ' €';

        // Validación de efectivo disponible
        const errorEl = document.getElementById('errorEfectivoInsuficiente');
        const btnConfirmar = document.getElementById('btnConfirmarMultiDev');
        const dispEl = document.getElementById('efectivoDisponibleDisplay');

        const totalRedondeado = Math.round(total * 100) / 100;
        const efectivoRedondeado = Math.round(efectivoActualCaja * 100) / 100;

        if (totalRedondeado > efectivoRedondeado) {
            errorEl.style.display = 'block';
            dispEl.textContent = efectivoActualCaja.toFixed(precDevTotal).replace('.', ',') + ' €';
            btnConfirmar.disabled = true;
            btnConfirmar.style.opacity = '0.5';
            btnConfirmar.style.cursor = 'not-allowed';
        } else {
            errorEl.style.display = 'none';
            btnConfirmar.disabled = !hayDevolucion;
            btnConfirmar.style.opacity = hayDevolucion ? '1' : '0.5';
            btnConfirmar.style.cursor = hayDevolucion ? 'pointer' : 'not-allowed';
        }
    }

    /**
     * Cierra el modal y resetea su estado.
     */
    function cerrarModalDevolucion() {
        cerrarModal('modalDevolucion');
        // Resetear a paso 1
        document.getElementById('devolucionPaso1').style.display = 'block';
        document.getElementById('devolucionPaso2').style.display = 'none';
        document.getElementById('btnConfirmarMultiDev').style.display = 'none';
        document.getElementById('resumenReembolso').style.display = 'none';
        document.getElementById('devolucionSubtitulo').textContent = 'Introduce el número del Ticket (ej: T00001)';
        document.getElementById('inputTicketIdDev').value = '';
        document.getElementById('errorTicketDev').style.display = 'none';
        document.getElementById('totalOriginalDisplay').textContent = '0,00 €';
        lineasVentaDevolucion = [];
        ticketActualDevolucion = null;
    }

    /**
     * Procesa la devolución enviando los datos al servidor.
     */
    function procesarMultiDevolucion() {
        const inputs = document.querySelectorAll('.cant-dev-input');
        let productosDev = [];
        let totalReembolso = 0;

        // Verificar si se devuelven TODOS los productos disponibles
        let totalUnidadesDisponibles = 0;
        let totalUnidadesSeleccionadas = 0;

        inputs.forEach(input => {
            const cant = parseInt(input.value) || 0;
            const index = input.dataset.index;
            const linea = lineasVentaDevolucion[index];
            const comprado = parseInt(linea.cantidad);
            const devuelto = parseInt(linea.cantidad_devuelta);
            const disponible = comprado - devuelto;

            totalUnidadesDisponibles += disponible;
            totalUnidadesSeleccionadas += cant;

            if (cant > 0) {
                const dec = linea.decimales || 2;
                // Usar precio con IVA (el que se pagó en el momento de la compra)
                const precioConIva = linea.precioConIva ? parseFloat(linea.precioConIva) : parseFloat(linea.precioUnitario) * (1 + (linea.iva || 21) / 100);
                // Redondear para evitar errores de precisión
                const subtotal = roundTo(cant * precioConIva, dec);

                productosDev.push({
                    idProducto: linea.idProducto,
                    nombreProducto: linea.producto_nombre,
                    idLineaOriginal: linea.id,
                    cantidad: cant,
                    importe: subtotal,
                    decimales: dec
                });
                totalReembolso += subtotal;
            }
        });

        if (productosDev.length === 0) return;

        // Calcular factor de descuento para asegurar que el reembolso no exceda lo pagado
        const venta = ticketActualDevolucion;
        const ventaTotal = parseFloat(venta.total);

        let sumaBruta = 0;
        lineasVentaDevolucion.forEach(linea => {
            const precioBase = linea.precioConIva ? parseFloat(linea.precioConIva) : parseFloat(linea.precioUnitario) * (1 + (linea.iva || 21) / 100);
            sumaBruta += parseInt(linea.cantidad) * precioBase;
        });

        const factorDescuento = sumaBruta > 0 ? ventaTotal / sumaBruta : 1;

        // Redondear total final
        const precDevMax = Math.max(2, ...lineasVentaDevolucion.map(l => l.decimales || 2));

        // Recalcular importes aplicando el factor de descuento
        productosDev = productosDev.map(p => {
            const linea = lineasVentaDevolucion.find(l => l.id === p.idLineaOriginal);
            if (linea) {
                const dec = linea.decimales || 2;
                const precioBase = linea.precioConIva ? parseFloat(linea.precioConIva) : parseFloat(linea.precioUnitario) * (1 + (linea.iva || 21) / 100);
                const subtotal = roundTo(p.cantidad * precioBase * factorDescuento, dec);
                return { ...p, importe: subtotal };
            }
            return p;
        });

        // Recalcular totalReembolso con los importes corregidos
        totalReembolso = productosDev.reduce((sum, p) => sum + p.importe, 0);

        // Asegurar que el total no exceda lo que el cliente pagó
        if (totalReembolso > ventaTotal) {
            totalReembolso = ventaTotal;
            // Ajustar proporcionalmente los importes de los productos
            const factorAjuste = ventaTotal / (totalReembolso / (factorDescuento || 1));
            productosDev = productosDev.map(p => ({
                ...p,
                importe: roundTo(p.importe * factorAjuste, p.decimales || 2)
            }));
            totalReembolso = ventaTotal;
        }

        // Redondear total final
        totalReembolso = roundTo(totalReembolso, precDevMax);

        const metodoPago = document.querySelector('input[name="metodoPagoDev"]:checked').value;

        // Crear formulario oculto y enviarlo para procesar la devolución
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'index.php';

        const accionInput = document.createElement('input');
        accionInput.type = 'hidden';
        accionInput.name = 'accion';
        accionInput.value = 'tramitarMultiDevolucion';
        form.appendChild(accionInput);

        const motivoInput = document.createElement('input');
        motivoInput.type = 'hidden';
        motivoInput.name = 'motivo';
        motivoInput.value = document.getElementById('motivoDevolucionDev') ? document.getElementById('motivoDevolucionDev').value.trim() : '';
        form.appendChild(motivoInput);

        const idVentaInput = document.createElement('input');
        idVentaInput.type = 'hidden';
        idVentaInput.name = 'idVenta';
        idVentaInput.value = ticketActualDevolucion.id;
        form.appendChild(idVentaInput);

        const metodoPagoInput = document.createElement('input');
        metodoPagoInput.type = 'hidden';
        metodoPagoInput.name = 'metodoPago';
        metodoPagoInput.value = metodoPago;
        form.appendChild(metodoPagoInput);

        const productosInput = document.createElement('input');
        productosInput.type = 'hidden';
        productosInput.name = 'productos';
        productosInput.value = JSON.stringify(productosDev);
        form.appendChild(productosInput);

        const totalInput = document.createElement('input');
        totalInput.type = 'hidden';
        totalInput.name = 'totalReembolso';
        totalInput.value = totalReembolso;
        form.appendChild(totalInput);

        document.body.appendChild(form);
        form.submit();
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
     * validar4Decimales(input)
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
     * abrirModalProductoComodin()
     * Abre el modal para crear un producto comodín
     */
    function abrirModalProductoComodin() {
        document.getElementById('modalProductoComodin').style.display = 'flex';
        document.getElementById('comodinNombre').value = '';
        document.getElementById('comodinDescripcion').value = '';
        document.getElementById('comodinPrecio').value = '';
        document.getElementById('comodinIva').value = '21';
        actualizarComodinPrecioTotal();
        document.getElementById('comodinNombre').focus();
    }

    /**
     * actualizarComodinPrecioTotal()
     * Calcula el precio total con IVA en tiempo real
     */
    function actualizarComodinPrecioTotal() {
        const precioBase = parseFloat(document.getElementById('comodinPrecio').value) || 0;
        const iva = parseFloat(document.getElementById('comodinIva').value) || 0;
        const total = precioBase * (1 + (iva / 100));

        document.getElementById('comodinPrecioTotal').textContent = total.toLocaleString('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' €';
    }

    /**
     * agregarProductoComodin()
     * Crea un producto temporal y lo añade al carrito
     */
    function agregarProductoComodin() {
        const nombre = document.getElementById('comodinNombre').value.trim();
        const descripcion = document.getElementById('comodinDescripcion').value.trim();
        const precioBase = parseFloat(document.getElementById('comodinPrecio').value);
        const ivaPorcentaje = parseFloat(document.getElementById('comodinIva').value);

        // Validaciones
        if (!nombre) {
            alert('<?php echo t('products.alert_enter_name'); ?>');
            document.getElementById('comodinNombre').focus();
            return;
        }

        if (isNaN(precioBase) || precioBase < 0) {
            alert('<?php echo t('products.alert_enter_valid_price'); ?>');
            document.getElementById('comodinPrecio').focus();
            return;
        }

        if (isNaN(ivaPorcentaje) || ivaPorcentaje < 0) {
            alert('<?php echo t('products.alert_enter_valid_iva'); ?>');
            document.getElementById('comodinIva').focus();
            return;
        }

        // Calcular precio con IVA
        const precioConIva = Math.round(precioBase * (1 + (ivaPorcentaje / 100)) * 100) / 100;

        // Crear objeto producto comodín
        const productoComodin = {
            idProducto: 'comodin_' + Date.now(), // ID único temporal
            nombre: nombre,
            descripcion: descripcion,
            precio: precioBase, // Precio sin IVA
            pvpUnitario: precioConIva, // Precio con IVA
            pvpOriginalUnitario: precioConIva, // Precio original sin descuentos de tarifa
            cantidad: 1,
            iva: ivaPorcentaje,
            tarifaNombre: '<?php echo t('cart.client_default_tarifa'); ?>', // Tarifa por defecto
            stockMax: 999, // Sin límite real de stock
            esComodin: true // Flag para identificar como producto comodín
        };

        // Añadir al carrito usando la función existente
        if (typeof carrito !== 'undefined') {
            // Buscar si ya existe un producto comodín con el mismo nombre, precio e IVA
            const indiceExistente = carrito.findIndex(item =>
                item.esComodin &&
                item.nombre.toLowerCase() === nombre.toLowerCase() &&
                parseFloat(item.precio) === precioBase &&
                parseFloat(item.iva) === ivaPorcentaje
            );

            if (indiceExistente >= 0) {
                // Incrementar cantidad
                carrito[indiceExistente].cantidad += 1;
            } else {
                // Añadir nuevo
                carrito.push(productoComodin);
            }

            // Actualizar ticket
            actualizarTicket();

            // Cerrar modal
            cerrarModal('modalProductoComodin');

            // Feedback visual
            console.log('Producto comodín añadido:', productoComodin);
        } else {
            alert('<?php echo t('cart.error_cart_not_available'); ?>');
        }
    }

    /**
     * previsualizarImagen(event)
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
     * abrirImagenGrande(src, alt)
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
    const selectPago = document.getElementById('metodoPago');
    if (selectPago) {
        selectPago.addEventListener('change', verificarLimiteEfectivo);
    }

    /**
     * Maneja el cambio de tarifa para aplicar descuentos según el tipo de cliente
     */
    function cambiarTarifa() {
        const tarifaId = document.getElementById('tarifaVenta').value;

        // Buscar la tarifa en el array de tarifas prefijadas
        const tarifa = tarifasPrefijadas.find(t => t.id == tarifaId);

        if (!tarifa) {
            eliminarDescuentoPorTarifa();
            return;
        }

        if (tarifa.requiere_cliente == 1 || tarifa.requiere_cliente === true) {
            // Abrir modal para buscar cliente registrado
            abrirModalBuscarClienteRegistrado();
        } else if (parseFloat(tarifa.descuento_porcentaje) === 0) {
            // Tarifa sin descuento
            eliminarDescuentoPorTarifa();
        } else {
            // Aplicar descuento
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
        document.getElementById('dniBusquedaCliente').value = '';
        document.getElementById('mensajeResultadoBusqueda').textContent = '';
        document.getElementById('mensajeResultadoBusqueda').className = '';
        document.getElementById('modalBuscarClienteRegistrado').style.display = 'flex';
        document.getElementById('dniBusquedaCliente').focus();
    }

    // Variable para almacenar los puntos canjeados en la venta actual
    let puntosCanjeados = null;
    let clienteIdentificadoEnModalPuntos = false;

    /**
     * Abre el modal para consultar y canjear puntos del cliente
     */
    function abrirModalPuntosCliente() {
        document.getElementById('dniPuntosCliente').value = '';
        document.getElementById('mensajePuntosCliente').style.display = 'none';
        document.getElementById('puntosClienteBusqueda').style.display = 'block';
        document.getElementById('puntosClienteInfo').style.display = 'none';

        // Habilitar o deshabilitar el botón de canjear según si hay productos en el carrito
        const btnCanjear = document.getElementById('btnAplicarDescuentoPuntos');
        if (carrito.length === 0) {
            btnCanjear.disabled = true;
            btnCanjear.title = '<?php echo t('points.title_add_to_canjear'); ?>';
            btnCanjear.style.opacity = '0.5';
            btnCanjear.style.cursor = 'not-allowed';
        } else {
            btnCanjear.disabled = false;
            btnCanjear.title = '<?php echo t('points.title_apply_descuento'); ?>';
            btnCanjear.style.opacity = '1';
            btnCanjear.style.cursor = 'pointer';
        }

        document.getElementById('modalPuntosCliente').style.display = 'flex';
        document.getElementById('dniPuntosCliente').focus();
    }

    /**
     * Cancela la selección del cliente y cierra el modal
     */
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
            'dniPuntosCliente', 'dniBusquedaCliente'];

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

        // Actualizar ticket para reflejar que ya no hay cliente (puntos previstos, etc)
        actualizarTicket();
    }

    function cerrarYLimpiarClientePuntos() {
        desvincularCliente();

        cerrarModal('modalPuntosCliente');
        document.getElementById('puntosClienteBusqueda').style.display = 'block';
        document.getElementById('puntosClienteInfo').style.display = 'none';
    }

    /**
     * Solo acumula puntos sin aplicar descuento, y cierra el modal
     */
    function acumularPuntosSolamente() {
        puntosCanjeados = null;
        // El cajero confirmó identificar al cliente → activar el modal de puntos post-venta
        clienteIdentificadoEnModalPuntos = true;

        // Mostrar el DNI del cliente identificado en el ticket
        const dniCliente = document.getElementById('dniPuntosCliente').value.trim();
        mostrarDniEnTicket(dniCliente);

        cerrarModal('modalPuntosCliente');
        document.getElementById('puntosClienteBusqueda').style.display = 'block';
        document.getElementById('puntosClienteInfo').style.display = 'none';
        actualizarTicket();
    }

    /**
     * Busca los puntos de un cliente por DNI
     */
    async function buscarPuntosCliente() {
        const dni = document.getElementById('dniPuntosCliente').value.trim();
        const mensajeDiv = document.getElementById('mensajePuntosCliente');

        if (!dni) {
            mensajeDiv.textContent = 'Por favor, introduce un DNI';
            mensajeDiv.className = 'mensaje-error';
            mensajeDiv.style.display = 'block';
            return;
        }

        try {
            const response = await fetch('api/clientes.php?dni=' + encodeURIComponent(dni));

            if (!response.ok) {
                mensajeDiv.textContent = '<?php echo t('points.error_client_not_found'); ?>';
                mensajeDiv.className = 'mensaje-error';
                mensajeDiv.style.display = 'block';
                return;
            }

            const data = await response.json();
            // La API devuelve un array de clientes (búsqueda parcial con LIKE)
            const cliente = Array.isArray(data) ? data[0] : data;
            if (cliente && cliente.activo == 1) {
                // IMPORTANTE: Poblamos los campos del cliente para que la venta se asocie a este cliente principal
                document.getElementById('clienteNif').value = cliente.dni;
                document.getElementById('clienteNombre').value = cliente.nombre + ' ' + cliente.apellidos;
                document.getElementById('clientePuntos').value = cliente.puntos || 0;
                document.getElementById('clienteDireccion').value = '';
                document.getElementById('clienteObservaciones').value = '';

                // Mostrar los puntos del cliente
                const puntosDisponibles = cliente.puntos || 0;
                document.getElementById('puntosDisponiblesCliente').textContent = puntosDisponibles.toLocaleString('es-ES');
                document.getElementById('puntosClienteBusqueda').style.display = 'none';
                document.getElementById('puntosClienteInfo').style.display = 'block';
                document.getElementById('puntosACanjeer').value = '';
                document.getElementById('puntosACanjeer').max = puntosDisponibles;
                document.getElementById('descuentoPuntosPreview').textContent = '';

                // Calcular y mostrar información de puntos
                const totalTicket = obtenerTotalCalculado();
                const infoPanel = document.getElementById('infoPointsPanel');
                const puntosUsarMsg = document.getElementById('puntosQueSePuedenUsar');
                const puntosGanadosMsg = document.getElementById('puntosQueSeGanaran');

                if (totalTicket > 0) {
                    const puntosQueSeGanaran = Math.round(totalTicket * 10);
                    const maxDescuento = totalTicket * 0.30;
                    const maxPuntosCanjeables = Math.floor(maxDescuento / 5) * 1000;
                    const puntosParaSiguienteDescuento = Math.max(0, 1000 - (puntosDisponibles % 1000));

                    // Mensajes informativos
                    let mensajeUsar = '';
                    if (puntosDisponibles >= 1000) {
                        const puedenUsarse = Math.floor(Math.min(puntosDisponibles, maxPuntosCanjeables) / 1000) * 1000;
                        const descuentoMax = Math.floor(puedenUsarse / 1000) * 5;
                        mensajeUsar = `<?php echo t('points.you_can_use'); ?> ${puedenUsarse.toLocaleString('es-ES')} <?php echo t('points.points_text'); ?> = ${descuentoMax.toFixed(2)}€ <?php echo t('points.of_discount'); ?> (30% <?php echo t('points.max_of_ticket'); ?>)`;
                        document.getElementById('puntosACanjeer').max = puedenUsarse;
                    } else {
                        mensajeUsar = `<?php echo t('points.you_need'); ?> ${puntosParaSiguienteDescuento.toLocaleString('es-ES')} <?php echo t('points.points_for_next_discount'); ?>`;
                    }

                    puntosUsarMsg.textContent = mensajeUsar;
                    puntosGanadosMsg.textContent = `<?php echo t('points.with_purchase_earn'); ?> ${puntosQueSeGanaran.toLocaleString('es-ES')} <?php echo t('points.points_text'); ?> (1€ = 10 <?php echo t('points.points_text'); ?>)`;
                    infoPanel.style.display = 'block';
                } else {
                    infoPanel.style.display = 'none';
                }
            } else {
                mensajeDiv.textContent = '<?php echo t('points.error_client_inactive_or_none'); ?>';
                mensajeDiv.className = 'mensaje-error';
                mensajeDiv.style.display = 'block';
            }
        } catch (error) {
            console.error('Error al buscar cliente:', error);
            mensajeDiv.textContent = '<?php echo t('points.error_searching_client'); ?>';
            mensajeDiv.className = 'mensaje-error';
            mensajeDiv.style.display = 'block';
        }
    }

    /**
     * Calcula el descuento basado en los puntos a canjear
     */
    function calcularDescuentoPuntos() {
        const inputPuntos = document.getElementById('puntosACanjeer');
        let puntos = parseInt(inputPuntos.value) || 0;
        const preview = document.getElementById('descuentoPuntosPreview');
        const puntosGanadosMsg = document.getElementById('puntosQueSeGanaran');
        const totalTicket = obtenerTotalCalculado();

        let nuevoTotal = totalTicket;

        // Solo forzar múltiplos de 1000 al aplicar, no mientras escribe
        // Guardamos el valor original sin redondear para no molestar al usuario mientras escribe
        const esMultiploDeMil = puntos % 1000 === 0;

        const descuento = Math.floor(puntos / 1000) * 5;

        if (puntos >= 1000) {
            preview.textContent = `<?php echo t('cart.discount'); ?>: ${descuento.toFixed(2)}€ (${puntos.toLocaleString('es-ES')} <?php echo t('points.points_text'); ?>)`;
            preview.style.color = '';
            nuevoTotal = Math.max(0, totalTicket - descuento);
        } else if (puntos > 0 && puntos < 1000) {
            preview.textContent = '<?php echo t('points.error_min_1000'); ?>';
            preview.style.color = '#ef4444';
        } else if (puntos > 0 && !esMultiploDeMil) {
            // Si wrote un valor que no es múltiplo de 1000, mostrar mensaje pero no modificar el input
            preview.textContent = '<?php echo t('points.error_multiples_1000'); ?>';
            preview.style.color = '#ef4444';
        } else {
            preview.textContent = '';
            preview.style.color = '';
        }

        // Actualizar dinámicamente los puntos que se ganarán basados en el nuevo total
        if (puntosGanadosMsg) {
            const puntosQueSeGanaran = Math.round(nuevoTotal * 10);
            puntosGanadosMsg.textContent = `<?php echo t('points.with_purchase_earn'); ?> ${puntosQueSeGanaran.toLocaleString('es-ES')} <?php echo t('points.points_text'); ?> (1€ = 10 <?php echo t('points.points_text'); ?>)`;
        }
    }

    /**
     * Aplica el descuento de puntos a la venta actual
     */
    function aplicarDescuentoPuntos() {
        const puntosInput = parseInt(document.getElementById('puntosACanjeer').value) || 0;
        const puntosRedondeados = Math.floor(puntosInput / 1000) * 1000; // Redondear a múltiplos de 1000
        const dni = document.getElementById('dniPuntosCliente').value.trim();
        const puntosDisponibles = parseInt(document.getElementById('puntosDisponiblesCliente').textContent.replace(/\./g, '')) || 0;

        if (!dni) {
            alert('<?php echo t('points.error_no_dni_specified'); ?>');
            return;
        }

        if (puntosRedondeados < 1000) {
            alert('<?php echo t('points.error_min_1000_alert'); ?>');
            return;
        }

        // Validar que no canjee más puntos de los que tiene
        if (puntosRedondeados > puntosDisponibles) {
            alert(`<?php echo t('points.error_not_enough_points'); ?>. <?php echo t('points.you_have'); ?> ${puntosDisponibles.toLocaleString('es-ES')} <?php echo t('points.points_text'); ?>.`);
            return;
        }

        // Calcular el total actual del ticket
        const totalTicket = obtenerTotalCalculado();
        const maxDescuento = totalTicket * 0.30; // Máximo 30%
        const maxPuntos = Math.floor(maxDescuento / 5) * 1000;

        // Limitar los puntos al máximo permitido
        let puntosFinales = puntosRedondeados;
        if (puntosRedondeados > maxPuntos) {
            alert(`<?php echo t('points.alert_exceeded_max'); ?> (30% <?php echo t('points.of_ticket'); ?> = ${maxDescuento.toFixed(2)}€). <?php echo t('points.will_use'); ?> ${maxPuntos.toLocaleString('es-ES')} <?php echo t('points.points_text'); ?>.`);
            puntosFinales = maxPuntos;
        }

        const descuentoEuros = Math.floor(puntosFinales / 1000) * 5;

        // Verificar que el total no sea 0
        if (totalTicket - descuentoEuros <= 0) {
            alert('<?php echo t('points.error_discount_ticket_zero'); ?>');
            return;
        }

        // Aplicar descuento como descuento manual
        descuento.tipo = 'fijo';
        descuento.valor = descuentoEuros;
        descuento.cupon = 'PUNTOS_' + puntosFinales;

        // Calcular puntos que se ganarán
        const puntosGanados = Math.round((totalTicket - descuentoEuros) * 10);

        actualizarTicket();

        // Mostrar el DNI del cliente identificado en el ticket
        mostrarDniEnTicket(dni);

        // Cerrar modal
        cerrarModal('modalPuntosCliente');
        document.getElementById('puntosClienteBusqueda').style.display = 'block';
        document.getElementById('puntosClienteInfo').style.display = 'none';

        // Guardar los puntos canjeados para procesarlos al confirmar la venta
        // El cajero confirmó identificar al cliente → activar el modal de puntos post-venta
        clienteIdentificadoEnModalPuntos = true;
        puntosCanjeados = {
            dni: dni,
            puntos: puntosFinales,
            descuento: descuentoEuros
        };

        alert(`<?php echo t('points.alert_discount_applied'); ?> ${descuentoEuros.toFixed(2)}€ (<?php echo t('points.canjeados'); ?> ${puntosFinales.toLocaleString('es-ES')} <?php echo t('points.points_text'); ?>)\n` +
            `<?php echo t('points.with_purchase_earn'); ?> ${puntosGanados.toLocaleString('es-ES')} <?php echo t('points.points_text'); ?>`);
    }

    /**
     * Busca un cliente por DNI y aplica el descuento configurado en la tarifa
     * También puede ser usado para registrar un cliente para acumular puntos
     */
    async function buscarClienteRegistrado() {
        const dni = document.getElementById('dniBusquedaCliente').value.trim();
        const mensajeDiv = document.getElementById('mensajeResultadoBusqueda');
        const modal = document.getElementById('modalBuscarClienteRegistrado');
        const esModoPuntos = modal.dataset.modo === 'puntos';

        // Determinar qué tarifa estamos validando
        let tarifaActual = null;
        if (productoPendienteTarifa) {
            const select = productoPendienteTarifa.card.querySelector('.tarifa-selector');
            const tarifaId = select.options[select.selectedIndex].dataset.tarifaId;
            tarifaActual = tarifasPrefijadas.find(t => t.id == tarifaId);
        } else {
            const tarifaIdActual = document.getElementById('tarifaVenta').value;
            tarifaActual = tarifasPrefijadas.find(t => t.id == tarifaIdActual);
        }

        if (!dni) {
            mensajeDiv.textContent = 'Por favor, introduce un DNI';
            mensajeDiv.className = 'mensaje-error';
            mensajeDiv.style.display = 'block';
            return;
        }

        try {
            const response = await fetch('api/clientes.php?dni=' + encodeURIComponent(dni));

            if (!response.ok) {
                // Cliente no encontrado (404)
                if (esModoPuntos) {
                    // En modo puntos, permitimos continuar sin cliente registrado
                    mensajeDiv.textContent = '<?php echo t('points.error_client_not_found_points'); ?>';
                    mensajeDiv.className = 'mensaje-error';
                    mensajeDiv.style.display = 'block';
                    // Añadir botón para continuar sin cliente
                    mensajeDiv.innerHTML += `<br><button class="btn-modal-cancelar" onclick="confirmarSinPuntos()" style="margin-top:10px; width:100%;"><?php echo t('points.btn_continue_no_points'); ?></button>`;
                    return;
                }
                mensajeDiv.textContent = '<?php echo t('points.error_client_not_found'); ?>';
                mensajeDiv.className = 'mensaje-error';
                mensajeDiv.style.display = 'block';
                // Cambiar la tarifa a Cliente
                let tarifaCliente4 = tarifasPrefijadas.find(t => t.nombre === 'Cliente');
                if (tarifaCliente4) {
                    document.getElementById('tarifaVenta').value = tarifaCliente4.id;
                } else if (tarifasPrefijadas.length > 0) {
                    document.getElementById('tarifaVenta').value = tarifasPrefijadas[0].id;
                }
                return;
            }

            const data = await response.json();
            const cliente = Array.isArray(data) ? data[0] : data;

            if (cliente && cliente.activo == 1) {
                // Poblamos los campos del cliente para que se guarden con la venta
                document.getElementById('clienteNif').value = cliente.dni;
                document.getElementById('clienteNombre').value = cliente.nombre + ' ' + cliente.apellidos;
                document.getElementById('clientePuntos').value = cliente.puntos || 0;
                document.getElementById('clienteDireccion').value = '';
                document.getElementById('clienteObservaciones').value = '';

                // Si estamos en modo puntos, mostrar mensaje de éxito y proceder con el tipo de documento
                if (esModoPuntos) {
                    // Cerrar el modal directamente y mostrar tipo de documento
                    cerrarModal('modalBuscarClienteRegistrado');
                    // Cerrar también el modal de cliente (Datos del Cliente) si está abierto
                    cerrarModal('modalDatosCliente');
                    // Mostrar modal de tipo de documento (ticket/factura)
                    document.getElementById('modalTipoDoc').style.display = 'flex';
                    return;
                }

                // El descuento ya no es global, se aplica al añadir el producto al carrito abajo
                const nombreTarifa = tarifaActual ? tarifaActual.nombre : 'Cliente Registrado';
                const descuentoValor = tarifaActual ? parseFloat(tarifaActual.descuento_porcentaje) : 0;

                actualizarTicket();
                mensajeDiv.textContent = `<?php echo t('points.client_found'); ?>: ${cliente.nombre} ${cliente.apellidos}. <?php echo t('cart.tarifa'); ?> ${nombreTarifa} (${descuentoValor}%) <?php echo t('points.validated'); ?>.`;
                mensajeDiv.className = 'mensaje-exito';
                mensajeDiv.style.display = 'block';

                // Si había un producto esperando por esta identificación, lo añadimos al carrito
                if (productoPendienteTarifa) {
                    agregarAlCarrito(productoPendienteTarifa.card);
                    productoPendienteTarifa = null;
                }

                setTimeout(() => {
                    cerrarModal('modalBuscarClienteRegistrado');
                }, 1500);
            } else if (cliente && cliente.activo == 0) {
                mensajeDiv.textContent = '<?php echo t('points.error_client_inactive'); ?>';
                mensajeDiv.className = 'mensaje-error';
                mensajeDiv.style.display = 'block';
            } else {
                mensajeDiv.textContent = '<?php echo t('points.error_client_not_found'); ?>';
                mensajeDiv.className = 'mensaje-error';
                mensajeDiv.style.display = 'block';
                // Cambiar la tarifa a Cliente
                let tarifaCliente5 = tarifasPrefijadas.find(t => t.nombre === 'Cliente');
                if (tarifaCliente5) {
                    document.getElementById('tarifaVenta').value = tarifaCliente5.id;
                } else if (tarifasPrefijadas.length > 0) {
                    document.getElementById('tarifaVenta').value = tarifasPrefijadas[0].id;
                }
            }
        } catch (error) {
            console.error('Error al buscar cliente:', error);
            mensajeDiv.textContent = '<?php echo t('points.error_searching_client'); ?>';
            mensajeDiv.className = 'mensaje-error';
            if (productoPendienteTarifa) {
                revertirTarifaCard(productoPendienteTarifa.card.dataset.id);
                productoPendienteTarifa = null;
            }
        }
    }

    /**
     * Sobrescribimos el cierre del modal para manejar la reversión de tarifa si se cancela
     */
    function cerrarModalBuscarClienteRegistrado() {
        const modal = document.getElementById('modalBuscarClienteRegistrado');

        // Si estaba en modo puntos y se cancela, continuar sin puntos
        if (modal.dataset.modo === 'puntos') {
            modal.dataset.modo = '';
            // Restaurar títulos
            document.querySelector('#modalBuscarClienteRegistrado h3').textContent = '<?php echo t('points.registered_client'); ?>';
            document.querySelector('#modalBuscarClienteRegistrado .modal-subtitulo').textContent = '<?php echo t('points.enter_client_dni'); ?>';
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
        // Eliminar cualquier descuento de tarifa activo (no importa el nombre del cupón)
        if (descuentoTarifa && descuentoTarifa.tipo !== 'ninguno') {
            descuentoTarifa = { tipo: 'ninguno', valor: 0, cupon: '' };
            actualizarTicket();
        }
    }

    // ============================================================
    // FUNCIONES PARA CLIENTES HABITUALES
    // ============================================================

    /**
     * Abre el modal para añadir un nuevo cliente habitual
     */
    function abrirModalClienteHabitual() {
        // Limpiar campos
        document.getElementById('clienteHabitualDni').value = '';
        document.getElementById('clienteHabitualNombre').value = '';
        document.getElementById('clienteHabitualApellidos').value = '';
        // Función para obtener la fecha local en formato datetime-local
        const now = new Date();
        const localDate = new Date(now.getTime() - (now.getTimezoneOffset() * 60000)).toISOString().slice(0, 16);
        document.getElementById('clienteHabitualFecha').value = localDate;

        // Configurar el onclick del botón guardar para el cajero
        const btnGuardar = document.getElementById('btnGuardarClienteHabitual');
        btnGuardar.onclick = guardarClienteHabitual;

        // Mostrar modal
        document.getElementById('modalClienteHabitual').style.display = 'flex';
        document.getElementById('clienteHabitualDni').focus();
    }

    /**
     * Guarda un nuevo cliente habitual
     */
    async function guardarClienteHabitual() {
        const dni = document.getElementById('clienteHabitualDni').value.trim();
        const nombre = document.getElementById('clienteHabitualNombre').value.trim();
        const apellidos = document.getElementById('clienteHabitualApellidos').value.trim();
        const direccion = document.getElementById('clienteHabitualDireccion').value.trim();
        const fecha_alta = document.getElementById('clienteHabitualFecha').value || new Date(new Date().getTime() - (new Date().getTimezoneOffset() * 60000)).toISOString().slice(0, 16);

        // Validar campos obligatorios
        if (!dni || !nombre || !apellidos) {
            alert('<?php echo t('points.error_mandatory_habitual'); ?>');
            return;
        }

        const btnGuardar = document.getElementById('btnGuardarClienteHabitual');
        btnGuardar.disabled = true;
        btnGuardar.textContent = '<?php echo t('points.btn_saving'); ?>';

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
                alert('<?php echo t('points.alert_habitual_saved'); ?>');
                cerrarModal('modalClienteHabitual');
            } else {
                alert(data.error || '<?php echo t('points.error_saving_habitual'); ?>');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('<?php echo t('points.error_server_communication'); ?>');
        } finally {
            btnGuardar.disabled = false;
            btnGuardar.textContent = '<?php echo t('points.btn_save'); ?>';
        }
    }
</script>

<!-- Modal para crear nuevo producto (permiso: crear_productos) -->
<div class="modal-overlay" id="modalNuevoProducto" style="display:none;">
    <div class="modal-content modal-editarProducto">
        <h3 id="editProductoTitulo"><?php echo t('products.new_product_title'); ?></h3>
        <p id="editProductoSubtitulo" class="modal-subtitulo"><?php echo t('products.enter_data_subtitle'); ?></p>

        <input type="hidden" id="editProductoId">

        <div class="editar-prod-layout">
            <!-- Imagen -->
            <div class="editar-prod-imagen-wrapper">
                <img id="editProductoImagen" src="webroot/img/logoCPU.PNG" alt="" style="cursor: zoom-in;"
                    onclick="abrirImagenGrande(this.src, this.alt)">
                <label class="btn-cambiar-imagen" title="<?php echo t('products.title_change_image'); ?>">
                    <i class="fas fa-camera"></i> <?php echo t('products.btn_change_image'); ?>
                    <input type="file" id="editProductoImagenInput" accept="image/*" style="display:none;"
                        onchange="previsualizarImagen(event)">
                </label>
            </div>

            <!-- Campos -->
            <div class="editar-prod-campos">
                <div class="editar-prod-fila">
                    <label><?php echo t('products.label_name'); ?></label>
                    <input type="text" id="nuevoProductoNombre">
                </div>
                <div class="editar-prod-fila">
                    <label><?php echo t('products.label_category'); ?></label>
                    <select id="nuevoProductoCategoria"
                        style="padding: 8px; border-radius: 4px; border: 1px solid #d1d5db;">
                    </select>
                </div>
                <div class="editar-prod-fila">
                    <label><?php echo t('products.label_price'); ?> (€) <span style="color:red">*</span></label>
                    <input type="number" id="nuevoProductoPrecio" step="0.0001" min="0"
                        oninput="validarPrecisionDinamica(this, 'nuevoProductoDecimales')"
                        onblur="validarPrecisionDinamica(this, 'nuevoProductoDecimales')">
                </div>
                <div class="editar-prod-fila">
                    <label><?php echo t('products.label_stock'); ?></label>
                    <input type="number" id="nuevoProductoStock" min="0" value="0">
                </div>
                <div class="editar-prod-fila">
                    <label><?php echo t('products.label_iva_type'); ?> (%)</label>
                    <select id="nuevoProductoIva" style="padding: 8px; border-radius: 4px; border: 1px solid #d1d5db;">
                        <option value="21">21% (<?php echo t('products.iva_general'); ?>)</option>
                        <option value="10">10% (<?php echo t('products.iva_reduced'); ?>)</option>
                        <option value="4">4% (<?php echo t('products.iva_super_reduced'); ?>)</option>
                        <option value="0">0% (<?php echo t('products.iva_exempt'); ?>)</option>
                    </select>
                </div>
                <div class="editar-prod-fila">
                    <label><?php echo t('products.label_status'); ?></label>
                    <select id="nuevoProductoEstado">
                        <option value="1"><?php echo t('products.status_active'); ?></option>
                        <option value="0"><?php echo t('products.status_inactive'); ?></option>
                    </select>
                </div>
            </div>
        </div>

        <div class="editar-prod-botones">
            <button class="btn-modal-cancelar"
                onclick="cerrarModal('modalNuevoProducto')"><?php echo t('common.cancel'); ?></button>
            <button class="btn-exito" onclick="guardarNuevoProducto()">
                <i class="fas fa-save"></i> <?php echo t('common.save'); ?>
            </button>
        </div>
    </div>
</div>

<!-- ##=========================== MODAL: CAMBIAR PRECIOS ===========================## -->
<!-- Modal para cambiar precios base y tarifas desde el cajero -->
<div class="modal-overlay" id="modalCambiarPrecios" style="display:none;">
    <div class="modal-content"
        style="max-width: 900px; width: 95%; max-height: 85vh; display: flex; flex-direction: column; padding: 0; overflow: hidden; background: var(--bg-modal); color: var(--text-main);">
        <!-- Cabecera del modal -->
        <div
            style="background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%); color: white; padding: 20px 25px; display: flex; justify-content: center; align-items: center; flex-shrink: 0; position: relative;">
            <div style="text-align: center;">
                <h3 style="margin: 0; font-size: 1.2rem; font-weight: 700;">
                    <?php echo t('products.change_prices_title'); ?>
                </h3>
                <p style="margin: 4px 0 0 0; font-size: 0.85rem; opacity: 0.9;">
                    <?php echo t('products.change_prices_subtitle'); ?>
                </p>
            </div>
            <button onclick="cerrarModal('modalCambiarPrecios')"
                style="background: none; border: none; color: white; font-size: 24px; cursor: pointer; padding: 0; line-height: 1; opacity: 0.8; transition: opacity 0.2s; position: absolute; right: 20px;"
                onmouseover="this.style.opacity=1" onmouseout="this.style.opacity=0.8">&times;</button>
        </div>

        <!-- Barra de búsqueda y botón IVA -->
        <div
            style="padding: 15px 25px; border-bottom: 1px solid var(--border-main); flex-shrink: 0; display: flex; gap: 10px; align-items: center;">
            <input type="text" id="buscarProductoCambiarPrecio"
                placeholder="<?php echo t('products.search_placeholder'); ?>..."
                oninput="buscarProductosCambiarPrecio()"
                style="flex: 1; padding: 10px 15px; border: 1px solid var(--border-main); border-radius: 8px; font-size: 14px; outline: none; transition: border-color 0.2s; box-sizing: border-box; background: var(--bg-input); color: var(--text-main);"
                onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='var(--border-main)'">
            <button type="button" id="btnToggleIvaCambiarPrecios" onclick="toggleIvaCambiarPrecios()"
                style="padding: 10px 15px; border: none; border-radius: 8px; cursor: pointer; font-weight: 500; font-size: 13px; transition: all 0.2s; display: flex; align-items: center; gap: 6px; white-space: nowrap; background: var(--accent); color: white;">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="16"></line>
                    <line x1="8" y1="12" x2="16" y2="12"></line>
                </svg>
                <?php echo t('products.btn_view_with_iva'); ?>
            </button>
            <button type="button" id="btnAplicarCambiosPrecios" onclick="aplicarCambiosPreciosCajero()" disabled
                style="padding: 10px 15px; border: none; border-radius: 8px; cursor: not-allowed; font-weight: 500; font-size: 13px; transition: all 0.2s; display: flex; align-items: center; gap: 6px; white-space: nowrap; background: #10b981; color: white; opacity: 0.5;">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                    <polyline points="7 3 7 8 15 8"></polyline>
                </svg>
                <?php echo t('products.btn_apply_changes'); ?>
            </button>
        </div>

        <!-- Tabla de productos y tarifas -->
        <div style="flex: 1; overflow-y: auto; padding: 0 25px 15px 25px;">
            <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem; color: var(--text-main);"
                id="tablaCambiarPrecios">
                <thead style="position: sticky; top: 0; z-index: 10; background: var(--bg-panel);">
                    <tr id="cabeceraCambiarPrecios">
                        <!-- Se genera dinámicamente -->
                    </tr>
                </thead>
                <tbody id="bodyTablaCambiarPrecios">
                    <tr>
                        <td colspan="10" style="text-align: center; padding: 40px; color: var(--text-muted);">
                            <?php echo t('products.loading_products'); ?>...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        <div id="paginacionCambiarPrecios"
            style="padding: 10px 25px 15px 25px; border-top: 1px solid var(--border-main); display: flex; justify-content: center; align-items: center; gap: 10px; flex-shrink: 0; color: var(--text-muted);">
        </div>
    </div>
</div>

<!-- ##=========================== MODAL: PRODUCTO COMODÍN ===========================## -->
<!-- Modal para crear un producto temporal "comodín" y añadirlo directamente al carrito -->
<div class="modal-overlay" id="modalProductoComodin" style="display:none;">
    <div class="modal-content" style="max-width: 400px; padding: 0;">
        <!-- Cabecera -->
        <div class="modal-header-premium modal-header-blue">
            <div class="icon-container-discount">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                    stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polygon
                        points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2">
                    </polygon>
                </svg>
            </div>
            <h3><?php echo t('products.comodin_title'); ?></h3>
            <p><?php echo t('products.comodin_subtitle'); ?></p>
        </div>

        <!-- Formulario -->
        <div style="padding: 25px;">
            <div style="margin-bottom: 20px;">
                <label for="comodinNombre"
                    style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-main);"><?php echo t('products.label_comodin_name'); ?>
                    *</label>
                <input type="text" id="comodinNombre"
                    placeholder="Ej: <?php echo t('products.comodin_name_placeholder'); ?>"
                    style="width: 100%; padding: 12px 15px; border: 1px solid var(--border-main); border-radius: 8px; font-size: 14px; box-sizing: border-box; background: var(--bg-input); color: var(--text-main);"
                    onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='var(--border-main)'">
            </div>

            <div style="margin-bottom: 20px;">
                <label for="comodinDescripcion"
                    style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-main);"><?php echo t('products.label_comodin_desc'); ?>
                    (<?php echo t('common.optional'); ?>)</label>
                <textarea id="comodinDescripcion" placeholder="<?php echo t('products.comodin_desc_placeholder'); ?>..."
                    style="width: 100%; padding: 12px 15px; border: 1px solid var(--border-main); border-radius: 8px; font-size: 14px; box-sizing: border-box; background: var(--bg-input); color: var(--text-main); resize: vertical; min-height: 80px;"
                    onfocus="this.style.borderColor='#6366f1'"
                    onblur="this.style.borderColor='var(--border-main)'"></textarea>
            </div>

            <div style="display: flex; gap: 15px; margin-bottom: 20px;">
                <div style="flex: 1;">
                    <label for="comodinIva"
                        style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-main);">IVA (%)
                        *</label>
                    <input type="number" id="comodinIva" value="21" step="1" min="0"
                        oninput="actualizarComodinPrecioTotal()"
                        style="width: 100%; padding: 12px 15px; border: 1px solid var(--border-main); border-radius: 8px; font-size: 14px; box-sizing: border-box; background: var(--bg-input); color: var(--text-main);"
                        onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='var(--border-main)'">
                </div>
                <div style="flex: 2;">
                    <label for="comodinPrecio"
                        style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-main);"><?php echo t('products.label_price'); ?>
                        (<?php echo t('products.price_base'); ?>) *</label>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <input type="number" id="comodinPrecio" placeholder="0,00" step="0.0001"
                            oninput="validar4Decimales(this); actualizarComodinPrecioTotal()" min="0"
                            oninput="actualizarComodinPrecioTotal()"
                            style="flex: 1; padding: 12px 15px; border: 1px solid var(--border-main); border-radius: 8px; font-size: 14px; box-sizing: border-box; background: var(--bg-input); color: var(--text-main);"
                            onfocus="this.style.borderColor='#6366f1'"
                            onblur="this.style.borderColor='var(--border-main)'">
                        <span style="font-size: 16px; font-weight: 600; color: var(--text-main);">€</span>
                    </div>
                </div>
            </div>

            <div id="comodinTotalContainer"
                style="margin-bottom: 25px; padding: 15px; background: var(--bg-panel); border-radius: 10px; border: 1px dashed var(--border-main); text-align: center;">
                <span
                    style="display: block; font-size: 0.85rem; color: var(--text-muted); font-weight: 600; margin-bottom: 4px;"><?php echo mb_strtoupper(t('products.total_price_with_iva')); ?></span>
                <span id="comodinPrecioTotal" style="font-size: 1.5rem; font-weight: 800; color: var(--text-main);">0,00
                    €</span>
            </div>

            <div style="display: flex; gap: 10px; margin-top: 30px;">
                <button class="btn-modal-cancelar" onclick="cerrarModal('modalProductoComodin')"
                    style="flex: 1; padding: 14px;">
                    <?php echo t('common.cancel'); ?>
                </button>
                <button class="btn-apply-premium" onclick="agregarProductoComodin()"
                    style="flex: 1; background: var(--accent); color: white; padding: 14px;">
                    <?php echo t('cart.add_to_cart'); ?>
                </button>
            </div>
        </div>
    </div>
</div>