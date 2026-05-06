<?php
// vCajero.php - Terminal Punto de Venta
?>
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
    // Cargar TODOS los idiomas disponibles para el ticket
    var IDIOMAS_TICKET = {
        es: <?php echo json_encode(include __DIR__ . '/../lang/es.php'); ?>,
        en: <?php echo json_encode(include __DIR__ . '/../lang/en.php'); ?>,
        fr: <?php echo json_encode(include __DIR__ . '/../lang/fr.php'); ?>,
        de: <?php echo json_encode(include __DIR__ . '/../lang/de.php'); ?>,
        ru: <?php echo json_encode(include __DIR__ . '/../lang/ru.php'); ?>
    };

    var idiomaTicketSeleccionado = '<?php echo $_SESSION['lang'] ?? 'es'; ?>';
    var LANG = IDIOMAS_TICKET[idiomaTicketSeleccionado] || IDIOMAS_TICKET.es;

    // Datos fiscales del TPV para impresin (Dinmicos - Verifactu)
    var TPV_CONFIG = {
        nif: '<?php echo addslashes(Verifactu::getConfig('TPV_NIF', 'B00000000')); ?>',
        nombre: '<?php echo addslashes(Verifactu::getConfig('TPV_RAZON_SOCIAL', 'TPV Bazar')); ?>',
        direccion: '<?php echo addslashes(Verifactu::getConfig('TPV_DIRECCION', 'C/ Principal 1, Madrid')); ?>',
        qrBaseUrl: '<?php echo addslashes(Verifactu::getQRBaseUrl()); ?>'
    };

    // Contexto global del cajero (variables inyectadas desde PHP)
    var TPV_CONTEXT = {
        puedeProductoComodin: <?php echo ($puedeProductoComodin ?? false) ? 'true' : 'false'; ?>,
        cajaAbierta: <?php echo ($sesionCaja ?? false) ? 'true' : 'false'; ?>,
        tarifasPrefijadas: <?php echo json_encode($tarifas ?? []) ?: '[]'; ?>,
        efectivoActualCaja: <?php echo ($sesionCaja ?? false) ? $sesionCaja->getImporteActual() : 0; ?>,
        cambioAnterior: <?php echo json_encode($cambioAnterior ?? null) ?: 'null'; ?>,
        sesionCajaId: <?php echo ($sesionCaja ?? false) ? $sesionCaja->getId() : 'null'; ?>,
        adminPermissions: <?php echo json_encode(isset($_SESSION['usuario']) ? $_SESSION['usuario']->getPermisos() : []) ?: '[]'; ?>,
        config: TPV_CONFIG
    };
</script>

<!-- Librería local para generación de QR -->
<script src="webroot/js/lib/qrcode.min.js"></script>
<script src="webroot/js/shared-impresion.js"></script>
<script src="webroot/js/cajero.js"></script>


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
                <div class="indicador-efectivo" id="cajeroIndicadorCaja" title="Efectivo actual en caja">
                    <span class="label"><?php echo t('cajero.cash_in_register'); ?></span>
                    <span class="amount" id="cajeroEfectivoValor"><?php echo number_format($sesionCaja->getImporteActual(), 2, ',', '.'); ?> €</span>
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
                        data-nombre_es="<?php echo htmlspecialchars($prod->getNombreEs() ?? $prod->getNombre()); ?>"
                        data-nombre_en="<?php echo htmlspecialchars($prod->getNombreEn() ?? $prod->getNombre()); ?>"
                        data-nombre_fr="<?php echo htmlspecialchars($prod->getNombreFr() ?? $prod->getNombre()); ?>"
                        data-nombre_de="<?php echo htmlspecialchars($prod->getNombreDe() ?? $prod->getNombre()); ?>"
                        data-nombre_ru="<?php echo htmlspecialchars($prod->getNombreRu() ?? $prod->getNombre()); ?>"
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
                                        <?php echo t('tarifas.' . strtolower(str_replace(' ', '_', $tarifa['nombre']))); ?>
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
        <input type="hidden" name="idioma_ticket" id="inputIdiomaTicket" value="es">
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
                        oninput="validarPrecisionDinamica(this); calcularCambio()" min="0" placeholder="0.00"
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



                <!-- Sección: Idioma del Ticket -->
                <div class="control-group">
                    <div class="control-section-title">Idioma del Ticket</div>
                    <div class="idiomas-grid">
                        <div class="idioma-option-card active" data-idioma="es" onclick="cambiarIdiomaTicket('es')"
                            title="Español">
                            <img src="https://flagcdn.com/w40/es.png" alt="Español" width="32" height="24"
                                style="border-radius: 4px;">
                        </div>
                        <div class="idioma-option-card" data-idioma="en" onclick="cambiarIdiomaTicket('en')"
                            title="Inglés">
                            <img src="https://flagcdn.com/w40/gb.png" alt="Inglés" width="32" height="24"
                                style="border-radius: 4px;">
                        </div>
                        <div class="idioma-option-card" data-idioma="fr" onclick="cambiarIdiomaTicket('fr')"
                            title="Francés">
                            <img src="https://flagcdn.com/w40/fr.png" alt="Francés" width="32" height="24"
                                style="border-radius: 4px;">
                        </div>
                        <div class="idioma-option-card" data-idioma="de" onclick="cambiarIdiomaTicket('de')"
                            title="Alemán">
                            <img src="https://flagcdn.com/w40/de.png" alt="Alemán" width="32" height="24"
                                style="border-radius: 4px;">
                        </div>
                        <div class="idioma-option-card" data-idioma="ru" onclick="cambiarIdiomaTicket('ru')"
                            title="Ruso">
                            <img src="https://flagcdn.com/w40/ru.png" alt="Ruso" width="32" height="24"
                                style="border-radius: 4px;">
                        </div>
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
            mensajePersonalizado: '<?php echo addslashes($_SESSION['ultimaVentaMensajePersonalizado'] ?? ''); ?>',
            idioma_ticket: '<?php echo $_SESSION['ultimaVentaIdiomaTicket'] ?? 'es'; ?>',
            qrUrl: '<?php echo $_SESSION['ultimaVentaQR'] ?? ''; ?>'
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
    unset($_SESSION['ultimaVentaIdiomaTicket']);
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
    unset($_SESSION['ultimaVentaQR']);
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
                            oninput="validarPrecisionDinamica(this)" onblur="validarPrecisionDinamica(this)" min="0"
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
                    style="border: 1px solid var(--border-main); border-radius: 12px; overflow: hidden; margin-bottom: 10px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
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
                <!-- Paginación de productos de devolución -->
                <div id="paginacionDevolucion" style="display: flex; justify-content: center; margin-bottom: 15px;">
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
/**
 * Debug: mostrar si la sesión está definida
 */
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
                    <?php if (isset($_SESSION['devolucionDetalles']['rectificativa']) && !$_SESSION['devolucionDetalles']['rectificativa']['success']): ?>
                        <div
                            style="margin-top: 10px; padding: 10px; background: #fff7ed; border: 1px solid #fed7aa; border-radius: 6px; color: #9a3412; font-size: 0.8rem;">
                            <strong>⚠️ Error Fiscal (AEAT):</strong>
                            <?php echo htmlspecialchars($_SESSION['devolucionDetalles']['rectificativa']['message'] ?? 'Error desconocido'); ?>
                        </div>
                    <?php endif; ?>
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
                        lineas: <?php echo json_encode($_SESSION['devolucionDetalles']['lineas'] ?? []); ?>,
                        qrUrl: '<?php echo $_SESSION['devolucionDetalles']['qrUrl'] ?? ""; ?>'
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
                        oninput="validarPrecisionDinamica(this)" onblur="validarPrecisionDinamica(this)"
                        placeholder="0.00" required>
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
            <h3
                style="text-align: center; margin-top: 10px; border-top:none; padding-top:0; color: var(--text-header); font-size: 1.4rem;">
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
                <input type="number" id="cambio" name="cambio" step="0.0001" oninput="validarPrecisionDinamica(this)"
                    onblur="validarPrecisionDinamica(this)" min="0"
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
    <div class="modal-content modal-premium" style="max-width: 850px;">
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
                <!-- Aquí se cargarán los detalles -->
            </div>
            <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                <button class="btn-modal-cancelar"
                    onclick="cerrarModal('modalDetalleVenta')"><?php echo t('common.close'); ?></button>
            </div>
        </div>
    </div>
</div>

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
                            oninput="validarPrecisionDinamica(this); actualizarComodinPrecioTotal()" min="0"
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