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
        <div id="formBuscarProducto">
            <div style="display: flex; gap: 10px; align-items: center; flex: 1;">
                <label for="inputBuscarProducto" style="font-weight: 600; white-space: nowrap;">Buscar:</label>
                <input type="text" id="inputBuscarProducto" class="input-buscarProducto"
                    placeholder="Escribe el nombre del producto a buscar..." oninput="buscarProductos()" autocomplete="off"
                    style="width: 100%;" />
            </div>

            <!-- INDICADOR DE EFECTIVO EN CAJA -->
            <?php if ($sesionCaja): ?>
                <div class="indicador-efectivo" title="Efectivo actual en caja">
                    <span class="label">Efectivo en caja:</span>
                    <span class="amount"><?php echo number_format($sesionCaja->getImporteActual(), 2, ',', '.'); ?> €</span>
                </div>
            <?php
else: ?>
                <div class="indicador-efectivo caja-cerrada">
                    <span class="label">Caja Cerrada</span>
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
                Todos
            </button>
            <!-- Bucle PHP: genera un botón por cada categoría existente en la base de datos -->
            <?php foreach ($categorias as $cat): ?>
                <button class="cat-btn" data-categoria="<?php echo $cat->getId(); ?>"
                    onclick="seleccionarCategoria(this, <?php echo $cat->getId(); ?>)">
                    <?php echo htmlspecialchars($cat->getNombre()); ?>
                </button>
            <?php
endforeach; ?>
        </div>

        <!-- ==================== BARRA DE OPCIONES EXTRA ==================== -->
        <!-- Contiene los botones de acción principales y el indicador de efectivo en caja -->
        <div class="cajero-opciones-extra">

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

                <!-- Botón CIERRE TEMPORAL: permite pausar la sesión o cambiar de turno -->
                <button type="button" class="btn-historial" id="btnCierreTemporal" onclick="mostrarModalCierreTemporal()" <?php echo !$sesionCaja ? 'disabled' : ''; ?>
                    style="background: #64748b; color: white; <?php echo !$sesionCaja ? 'opacity: 0.5; cursor: not-allowed;' : ''; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                    Pausa / Turno
                </button>

                <!-- Botón HISTORIAL DE VENTAS: abre el modal con el historial de ventas de la sesión actual -->
                <button type="button" class="btn-historial" id="btnHistorial" onclick="mostrarHistorialVentas()" <?php echo !$sesionCaja ? 'disabled' : ''; ?>
                    style="<?php echo !$sesionCaja ? 'opacity: 0.5; cursor: not-allowed;' : ''; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 8v4l3 3"></path>
                        <circle cx="12" cy="12" r="10"></circle>
                    </svg>
                    Historial Ventas
                </button>

                <!-- Botón NUEVO PRODUCTO: abre el modal para crear un nuevo producto (requiere permiso: crear_productos) -->
                <button type="button" class="btn-nuevo-producto" id="btnNuevoProducto"
                    onclick="abrirModalNuevoProducto()" <?php echo !$sesionCaja ? 'disabled' : ''; ?>
                    style="display:none; <?php echo !$sesionCaja ? 'opacity: 0.3; cursor: not-allowed;' : ''; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    Nuevo Producto
                </button>

                <!-- Botón CLIENTE HABITUAL: abre el modal para gestionar clientes habituales -->
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
                    Nuevo Cliente
                </button>
            </div>
        </div>

        <!-- ==================== GRID DE PRODUCTOS ==================== -->
        <!-- Muestra las tarjetas de producto en formato grid -->
        <!-- Cada tarjeta contiene: nombre, imagen, precio y stock -->
        <!-- Al hacer clic en una tarjeta se ejecuta agregarAlCarrito(this) -->
        <div class="productos-grid" id="productosGrid">
            <?php if (empty($productos)): ?>
                <!-- Mensaje cuando no hay productos disponibles -->
                <p class="sin-productos">No hay productos disponibles.</p>
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
                        data-precios-tarifas='<?php echo htmlspecialchars(json_encode($prod->getPreciosTarifas()), ENT_QUOTES, 'UTF-8'); ?>'
                        data-stock="<?php echo $prod->getStock(); ?>" onclick="agregarAlCarrito(this)" style="<?php if ($prod->getStock() <= 0) {
            echo 'opacity: 0.5; cursor: not-allowed; scale: 1; transform: translateY(0px);';
        }?>">

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
                            <span class="producto-precio"><?php echo number_format($precioPVP, 2, ',', '.'); ?> €</span>

                            <!-- Selector de tarifa -->
                            <select class="tarifa-selector" onclick="event.stopPropagation()"
                                onfocus="guardarTarifaAnterior(this)"
                                onchange="actualizarPrecioCard(this, <?php echo $prod->getPrecio(); ?>, <?php echo $prod->getIvaPorcentaje(); ?>)">
                                <?php foreach ($tarifas as $tarifa): ?>
                                    <option value="<?php echo $tarifa['descuento_porcentaje']; ?>"
                                        data-requiere-cliente="<?php echo $tarifa['requiere_cliente']; ?>"
                                        data-tarifa-id="<?php echo $tarifa['id']; ?>" <?php echo($tarifa['nombre'] === 'Cliente') ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($tarifa['nombre']); ?>
                                    </option>
                                <?php
        endforeach; ?>
                            </select>

                            <span class="producto-stock" <?php if ($prod->getStock() <= 0) {
            echo 'style="color: red; text-decoration: underline;"';
        }?>>Stock: <?php echo $prod->getStock(); ?></span>
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
            <h3>Productos añadidos</h3>
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
            <!-- Fila 1: Botones de descuento, puntos, vaciar, posponer y recuperar -->
            <div class="ticket-acciones-fila">
                <!-- Botón DESCUENTO: abre el modal para aplicar descuento porcentual o por cupón -->
                <button class="btn-descuento" id="btnDescuento" onclick="aplicarDescuento()" disabled
                    title="Aplicar descuento">
                    🏷️
                </button>

                <!-- Botón PUNTOS: abre el modal para consultar y canjear puntos -->
                <button class="btn-descuento" id="btnPuntos" onclick="abrirModalPuntosCliente()"
                    title="Consultar/Canjear puntos" style="background: #10b981;">
                    ⭐
                </button>

                <!-- Botón VACIAR: elimina todos los productos del carrito -->
                <button class="btn-cancelar" onclick="vaciarCarrito()" title="Vaciar carrito">
                    🗑️
                </button>

                <!-- Botón POSPONER: guarda la venta sin terminar para recuperarla después -->
                <button class="btn-descuento" id="btnPosponer" onclick="posponerVenta()" disabled
                    style="background: #8b5cf6;" title="Posponer venta">
                    ⏳
                </button>

                <!-- Botón RECUPERAR: recupera la última venta pospuesta -->
                <button class="btn-descuento" id="btnRecuperar" onclick="recuperarVenta()" style="background: #f59e0b;"
                    title="Recuperar venta">
                    🔄
                </button>
            </div>

            <!-- Fila 2: Método de pago y botón cobrar -->
            <div class="ticket-acciones-fila">
                <!-- Selector de método de pago: Efectivo, Tarjeta o Bizum -->
                <select id="metodoPago">
                    <option value="efectivo">Efectivo</option>
                    <option value="tarjeta">Tarjeta</option>
                    <option value="bizum">Bizum</option>
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
                    ⚠️ No se permite pago en efectivo > 1.000€
                </div>

                <!-- Botón COBRAR: inicia el proceso de cobro (modal de cambio o tipo de documento) -->
                <button class="btn-cobrar" id="btnCobrar" onclick="intentarCobrar()" disabled>
                    Cobrar
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
        <div class="calculo-cambio-container">

            <!-- Fila 1: Total a pagar (se rellena con JS) -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <span style="font-size: 1.1rem; color: #4b5563;">Total a pagar:</span>
                <span id="cambioTotalPagar" class="cambio-total-monto">0,00 €</span>
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
        <h3 id="tituloDatosCliente" style="margin-bottom: 5px;">Datos del Cliente</h3>
        <p id="subtituloDatosCliente" class="modal-subtitulo-cliente">Complete los datos (Opcional en Ticket)</p>

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

<!-- ##=========================== MODAL: CLIENTE HABITUAL ===========================## -->
<!-- Modal para añadir un cliente habitual (DNI, nombre, apellidos, fecha alta, compras) -->
<div class="modal-overlay" id="modalClienteHabitual" style="display:none;">
    <div class="modal-content" style="max-width: 500px; text-align: left;">
        <h3 style="margin-bottom: 5px;">Nuevo Cliente Habitual</h3>
        <p class="modal-subtitulo" style="margin-bottom: 20px;">Complete los datos del cliente</p>

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
                    style="display: block; margin-bottom: 5px; font-weight: 500; font-size: 0.9rem;">Nombre <span
                        style="color: #ef4444;">*</span></label>
                <input type="text" id="clienteHabitualNombre"
                    style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;"
                    placeholder="Juan" maxlength="100">
            </div>

            <!-- Campo Apellidos -->
            <div>
                <label for="clienteHabitualApellidos"
                    style="display: block; margin-bottom: 5px; font-weight: 500; font-size: 0.9rem;">Apellidos <span
                        style="color: #ef4444;">*</span></label>
                <input type="text" id="clienteHabitualApellidos"
                    style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;"
                    placeholder="García López" maxlength="150">
            </div>

            <!-- Campo Fecha de Alta -->
            <div>
                <label for="clienteHabitualFecha"
                    style="display: block; margin-bottom: 5px; font-weight: 500; font-size: 0.9rem;">Fecha de
                    Alta</label>
                <input type="datetime-local" id="clienteHabitualFecha"
                    style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;" disabled>
            </div>
        </div>

        <!-- Botones: Cancelar y Guardar -->
        <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 25px;">
            <button class="btn-modal-cancelar" onclick="cerrarModal('modalClienteHabitual')">Cancelar</button>
            <button class="btn-exito" id="btnGuardarClienteHabitual" style="margin: 0;">Guardar</button>
        </div>
    </div>
</div>

<!-- ##=========================== MODAL: PUNTOS (venta > 20€) ===========================## -->
<!-- Modal que aparece cuando el total de la venta es >= 20€ y no se ha especificado cliente -->
<div class="modal-overlay" id="modalPuntos" style="display:none;">
    <div class="modal-content" style="max-width: 400px; text-align: left;">
        <h3 style="margin-bottom: 5px;">¡Gana puntos!</h3>
        <p class="modal-subtitulo" style="margin-bottom: 20px;">Esta compra es elegible para acumular puntos de fidelidad.</p>
        
        <div style="background: #f0fdf4; border: 1px solid #22c55e; border-radius: 8px; padding: 15px; margin-bottom: 20px; text-align: center;">
            <span style="font-size: 1.2rem; color: #166534;">El cliente podría ganar </span>
            <span id="puntosPosibles" style="font-size: 1.5rem; font-weight: bold; color: #15803d;">0</span>
            <span style="font-size: 1.2rem; color: #166534;"> puntos</span>
        </div>

        <p style="color: #6b7280; font-size: 0.9rem; margin-bottom: 20px;">
            ¿Desea introducir el DNI del cliente para registrar la compra y acumular puntos? Los puntos se pueden canjear en futuras compras.
        </p>

        <div style="display: flex; gap: 10px;">
            <button class="btn-modal-cancelar" onclick="confirmarSinPuntos()" style="flex: 1;">
                Ahora no
            </button>
            <button class="btn-exito" onclick="confirmarConPuntos()" style="flex: 1;">
                Introducir DNI
            </button>
        </div>
    </div>
</div>

<!-- ##=========================== MODAL: PUNTOS CLIENTE (consultar/canjear) ===========================## -->
<!-- Modal para consultar los puntos de un cliente y canjearlos por descuento -->
<div class="modal-overlay" id="modalPuntosCliente" style="display:none;">
    <div class="modal-content" style="max-width: 450px; text-align: left;">
        <h3 style="margin-bottom: 5px;">Puntos del Cliente</h3>
        <p class="modal-subtitulo" style="margin-bottom: 20px;">Consulta los puntos y canjéalos por descuento</p>
        
        <!-- Buscar cliente por DNI -->
        <div id="puntosClienteBusqueda">
            <div>
                <label for="dniPuntosCliente" style="display: block; margin-bottom: 5px; font-weight: 500; font-size: 0.9rem;">DNI del Cliente <span style="color: #ef4444;">*</span></label>
                <input type="text" id="dniPuntosCliente" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;" placeholder="12345678A" maxlength="20" onkeypress="if(event.key==='Enter') buscarPuntosCliente()">
            </div>
            <div id="mensajePuntosCliente" style="margin-top: 15px; padding: 10px; border-radius: 6px; display: none;"></div>
            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button class="btn-modal-cancelar" onclick="cerrarModal('modalPuntosCliente')" style="flex: 1;">
                    Cerrar
                </button>
                <button class="btn-exito" onclick="buscarPuntosCliente()" style="flex: 1;">
                    Buscar
                </button>
            </div>
        </div>
        
        <!-- Mostrar puntos del cliente (se muestra después de buscar) -->
        <div id="puntosClienteInfo" style="display: none;">
            <div style="background: #f0fdf4; border: 1px solid #22c55e; border-radius: 8px; padding: 20px; margin-bottom: 20px; text-align: center;">
                <p style="color: #6b7280; margin-bottom: 5px;">Puntos disponibles</p>
                <span id="puntosDisponiblesCliente" style="font-size: 2.5rem; font-weight: bold; color: #15803d;">0</span>
                <p style="color: #6b7280; margin-top: 5px;">1€ = 10 puntos | 1.000 puntos = 5€</p>
            </div>
            
            <!-- Información de puntos que se pueden usar y ganar -->
            <div id="infoPointsPanel" style="background: #eff6ff; border: 1px solid #3b82f6; border-radius: 8px; padding: 15px; margin-bottom: 15px; font-size: 0.9rem;">
                <p id="puntosQueSePuedenUsar" style="color: #1e40af; margin-bottom: 5px;"></p>
                <p id="puntosQueSeGanaran" style="color: #059669; margin-bottom: 0;"></p>
            </div>
            
            <div>
                <label for="puntosACanjeer" style="display: block; margin-bottom: 5px; font-weight: 500; font-size: 0.9rem;">Puntos a canjear (múltiplos de 1000)</label>
                <input type="number" id="puntosACanjeer" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;" placeholder="0" min="0" step="1000" oninput="calcularDescuentoPuntos()">
                <p id="descuentoPuntosPreview" style="color: #10b981; font-weight: 600; margin-top: 10px;"></p>
            </div>
            
            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button class="btn-modal-cancelar" onclick="cerrarModal('modalPuntosCliente'); document.getElementById('puntosClienteBusqueda').style.display='block'; document.getElementById('puntosClienteInfo').style.display='none';" style="flex: 1;">
                    Cerrar
                </button>
                <button class="btn-exito" id="btnAplicarDescuentoPuntos" onclick="aplicarDescuentoPuntos()" style="flex: 1;">
                    Aplicar Descuento
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ##=========================== MODAL: BUSCAR CLIENTE REGISTRADO ===========================## -->
<!-- Modal para buscar un cliente registrado por DNI y aplicar descuento según tarifa -->
<div class="modal-overlay" id="modalBuscarClienteRegistrado" style="display:none;" data-modo="">
    <div class="modal-content" style="max-width: 400px; text-align: left;">
        <h3 style="margin-bottom: 5px;">Cliente Registrado</h3>
        <p class="modal-subtitulo" style="margin-bottom: 20px;">Introduce el DNI del cliente</p>

        <div>
            <label for="dniBusquedaCliente"
                style="display: block; margin-bottom: 5px; font-weight: 500; font-size: 0.9rem;">DNI <span
                    style="color: #ef4444;">*</span></label>
            <input type="text" id="dniBusquedaCliente"
                style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;"
                placeholder="12345678A" maxlength="20" onkeypress="if(event.key==='Enter') buscarClienteRegistrado()">
        </div>

        <div id="mensajeResultadoBusqueda" style="margin-top: 15px; padding: 10px; border-radius: 6px; display: none;">
        </div>

        <div style="display: flex; gap: 10px; margin-top: 20px;">
            <button class="btn-modal-cancelar" onclick="cerrarModalBuscarClienteRegistrado()" style="flex: 1;">
                Cancelar
            </button>
            <button class="btn-exito" onclick="buscarClienteRegistrado()" style="flex: 1;">
                Buscar
            </button>
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
                <?php echo($_SESSION['ultimaVentaTipo'] === 'factura') ? 'Factura' : 'Ticket'; ?>
                #<?php echo $_SESSION['ultimaVentaId']; ?> — Total:
                <?php echo number_format($_SESSION['ultimaVentaTotal'], 2, ',', '.'); ?> €
            </p>

            <!-- Puntos ganados (si el cliente estaba registrado y ganó puntos) -->
            <?php if (isset($_SESSION['puntosGanados']) && $_SESSION['puntosGanados'] > 0): ?>
                <div style="background: #f0fdf4; border: 1px solid #22c55e; border-radius: 8px; padding: 15px; margin: 15px 0; text-align: center;">
                    <span style="font-size: 1.1rem; color: #166534;">El cliente ha ganado </span>
                    <span style="font-size: 1.4rem; font-weight: bold; color: #15803d;"><?php echo number_format($_SESSION['puntosGanados'], 0, ',', '.'); ?> puntos</span>
                    <br><small style="color: #166534;">(1€ = 10 puntos)</small>
                </div>
            <?php
    endif; ?>

            <!-- Puntos canjeados (si el cliente usó puntos para descuento) -->
            <?php if (isset($_SESSION['ultimaVentaPuntosCanjeados']) && $_SESSION['ultimaVentaPuntosCanjeados'] > 0): ?>
                <div style="background: #fef3c7; border: 1px solid #f59e0b; border-radius: 8px; padding: 15px; margin: 15px 0; text-align: center;">
                    <span style="font-size: 1.1rem; color: #92400e;">El cliente ha canjeado </span>
                    <span style="font-size: 1.4rem; font-weight: bold; color: #b45309;"><?php echo number_format($_SESSION['ultimaVentaPuntosCanjeados'], 0, ',', '.'); ?> puntos</span>
                    <span style="font-size: 1rem; color: #92400e;"> (<?php echo number_format(floor($_SESSION['ultimaVentaPuntosCanjeados'] / 1000) * 5, 2, ',', '.'); ?>€ de descuento)</span>
                </div>
            <?php
    endif; ?>

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
        let ultimaVenta = {
            id: <?php echo $_SESSION['ultimaVentaId'] ?? 'null'; ?>,                                          // ID de        la venta
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
            descuentoManualCupon: '<?php echo $_SESSION['ultimaVentaDescuentoManualCupon'] ?? ''; ?>'           // Código de cupón manual
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
            <h3>Apertura de Caja</h3>
            <p>Introduce el efectivo inicial para comenzar la jornada</p>
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
                            <span>💰 Recuperar cambio anterior:
                                <strong><?php echo number_format($cambioAnterior, 2, ',', '.'); ?> €</strong></span>
                        </label>
                    </div>
                    <div class="resumen-caja-container" style="margin-bottom: 15px;">
                        <label style="display: flex; align-items: center; cursor: pointer;">
                            <input type="radio" name="opcionCambio" value="nuevo" onclick="toggleCambio(true)"
                                style="margin-right: 8px;">
                            <span>✨ Introducir nuevo cambio</span>
                        </label>
                    </div>
                <?php
else: ?>
                    <input type="radio" name="opcionCambio" value="nuevo" checked style="display:none;">
                <?php
endif; ?>

                <!-- Input para el importe inicial (fondo de caja) -->
                <div class="form-group-premium" id="divImporteInicial"
                    style="<?php echo($cambioAnterior > 0) ? 'opacity: 0.5;' : ''; ?>">
                    <label for="importeInicial">Fondo de caja inicial (€)</label>
                    <div class="input-cupon">
                        <input type="number" name="importeInicial" id="importeInicial" step="0.01" min="0"
                            placeholder="0,00" <?php echo($cambioAnterior > 0) ? '' : 'required'; ?>
                            style="text-align: center; padding-right: 15px;">
                    </div>
                </div>

                <!-- Botones: Cancelar y Confirmar Apertura -->
                <div style="display: flex; gap: 15px; margin-top: 30px;">
                    <button type="button" class="btn-modal-cancelar" onclick="cerrarModal('modalAbrirCaja')"
                        style="flex: 1;">Cancelar</button>
                    <button type="submit" class="btn-apply-premium"
                        style="flex: 1; background: #16a34a; color: white;">Confirmar
                        Apertura</button>
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
            <h3 style="margin-bottom: 5px;">Tramitar Devolución</h3>
            <p id="devolucionSubtitulo" style="opacity: 0.9;">Introduce el ID del Ticket para comenzar</p>
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
                        Recuperar Ticket</h4>
                    <p
                        style="color: var(--text-muted); font-size: 0.95rem; margin-bottom: 30px; max-width: 400px; margin-left: auto; margin-right: auto;">
                        Introduce el número identificador para procesar la devolución de los productos.
                    </p>

                    <div style="max-width: 340px; margin: 0 auto;">
                        <input type="number" id="inputTicketIdDev" placeholder="Ej: 123"
                            style="width: 100%; padding: 18px; font-size: 1.6rem; text-align: center; border-radius: 14px; border: 2px solid var(--border-main); background: var(--bg-input); color: var(--text-main); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); transition: all 0.2s; outline: none; font-weight: 600;"
                            onkeypress="if(event.key === 'Enter') buscarTicketParaDevolucion()"
                            onfocus="this.style.borderColor = 'var(--accent-danger)'; this.style.boxShadow = '0 0 0 4px rgba(220, 38, 38, 0.1)'; this.style.transform = 'translateY(-2px)'"
                            onblur="this.style.borderColor = 'var(--border-main)'; this.style.boxShadow = '0 4px 6px -1px rgba(0,0,0,0.05)'; this.style.transform = 'translateY(0)'">

                        <button type="button" class="btn-apply-premium" onclick="buscarTicketParaDevolucion()"
                            style="width: 100%; background: var(--accent-danger); color: white; margin-top: 20px; padding: 16px; border-radius: 14px; font-size: 1.1rem; font-weight: 700; border: none; box-shadow: 0 6px 15px rgba(220, 38, 38, 0.3); transition: all 0.2s; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px;">
                            <span>Continuar</span>
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
                            El número de ticket se encuentra en la parte superior del comprobante.
                        </span>
                    </div>
                </div>
            </div>

            <!-- PASO 2: Selección de Productos -->
            <div id="devolucionPaso2" style="display: none;">
                <div
                    style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; padding: 15px; background: var(--bg-accent-danger); border-radius: 12px; border: 1px solid var(--accent-danger); opacity: 0.9;">
                    <div style="flex: 1;">
                        <div style="font-weight: 700; color: var(--text-main); font-size: 1rem; margin-bottom: 5px;">
                            Información
                            del Ticket</div>
                        <div style="font-size: 0.9rem; color: var(--text-muted);">
                            <span id="infoTicketId" style="font-weight: 600;"></span> ·
                            <span id="infoTicketFecha"></span>
                        </div>
                    </div>
                    <div style="text-align: right; display: flex; gap: 20px; align-items: center;">
                        <button type="button" onclick="seleccionarTodosProductos()"
                            style="background: var(--bg-panel); color: var(--text-main); border: 1px solid var(--border-main); padding: 8px 16px; border-radius: 8px; font-size: 0.85rem; font-weight: 600; cursor: pointer; transition: all 0.2s;">
                            <i class="fas fa-check-double" style="margin-right: 6px;"></i> Seleccionar Todo
                        </button>
                        <div style="text-align: right;">
                            <div
                                style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em;">
                                Total Original</div>
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
                                    Producto</th>
                                <th
                                    style="text-align: center; padding: 12px; font-weight: 700; font-size: 0.8rem; color: #64748b; text-transform: uppercase; width: 80px;">
                                    Disp.</th>
                                <th
                                    style="text-align: center; padding: 12px 15px; font-weight: 700; font-size: 0.8rem; color: #64748b; text-transform: uppercase; width: 120px;">
                                    A Devolver</th>
                            </tr>
                        </thead>
                        <tbody id="tablaProductosDev">
                            <!-- Se rellena dinámicamente -->
                        </tbody>
                    </table>
                </div>

                <div class="form-group-premium" style="margin-top: 25px;">
                    <label style="font-size: 1rem;">Método de Reembolso</label>
                    <div style="display: flex; gap: 12px; margin-top: 10px;">
                        <label style="flex: 1; cursor: pointer;">
                            <input type="radio" name="metodoPagoDev" value="Efectivo" checked style="display: none;"
                                onchange="updateMethodUI(this)">
                            <div class="method-chip active" id="chip-Efectivo"
                                style="padding: 12px; text-align: center; border-radius: 10px; border: 2px solid var(--accent-danger); color: var(--accent-danger); font-weight: 700; transition: all 0.2s;">
                                <i class="fas fa-money-bill-wave" style="margin-right: 8px;"></i> Efectivo
                            </div>
                        </label>
                        <label style="flex: 1; cursor: pointer;">
                            <input type="radio" name="metodoPagoDev" value="Tarjeta" style="display: none;"
                                onchange="updateMethodUI(this)">
                            <div class="method-chip" id="chip-Tarjeta"
                                style="padding: 12px; text-align: center; border-radius: 10px; border: 2px solid var(--border-main); color: var(--text-muted); font-weight: 600; transition: all 0.2s;">
                                <i class="fas fa-credit-card" style="margin-right: 8px;"></i> Tarjeta
                            </div>
                        </label>
                        <label style="flex: 1; cursor: pointer;">
                            <input type="radio" name="metodoPagoDev" value="Bizum" style="display: none;"
                                onchange="updateMethodUI(this)">
                            <div class="method-chip" id="chip-Bizum"
                                style="padding: 12px; text-align: center; border-radius: 10px; border: 2px solid var(--border-main); color: var(--text-muted); font-weight: 600; transition: all 0.2s;">
                                <i class="fas fa-mobile-alt" style="margin-right: 8px;"></i> Bizum
                            </div>
                        </label>
                    </div>
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
                            venta original</span>
                        <span id="totalOriginalDisplay"
                            style="font-size: 1.2rem; font-weight: 600; color: var(--text-main);">0,00 €</span>
                    </div>
                    <div>
                        <span
                            style="display: block; font-size: 0.75rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase; margin-bottom: 2px;">Total
                            a reembolsar</span>
                        <span id="totalReembolsoDisplay"
                            style="font-size: 1.6rem; font-weight: 800; color: var(--accent-danger);">0,00 €</span>
                    </div>
                </div>
                <div id="errorEfectivoInsuficiente"
                    style="display: none; color: var(--accent-danger); font-size: 0.8rem; font-weight: 600; margin-top: 5px; background: rgba(220, 38, 38, 0.1); padding: 5px 10px; border-radius: 6px;">
                    <i class="fas fa-exclamation-triangle"></i> No hay suficiente efectivo en caja (Disponible: <span
                        id="efectivoDisponibleDisplay"></span>)
                </div>
            </div>
            <div style="margin-left: auto; display: flex; gap: 15px;">
                <button type="button" class="btn-cancel-flat" onclick="cerrarModalDevolucion()"
                    style="padding: 10px 20px;">Cancelar</button>
                <button type="button" class="btn-apply-premium" id="btnConfirmarMultiDev" disabled
                    style="background: var(--accent-danger); color: white; margin: 0; padding: 12px 25px; border-radius: 10px; box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3); display: none;"
                    onclick="procesarMultiDevolucion()">
                    Confirmar Devolución
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
        <div class="modal-content modal-exito modal-border-red"
            style="max-width: 450px; background: #1f2937; border-radius: 12px; padding: 25px; margin: auto; color: #f3f4f6; box-shadow: 0 10px 25px rgba(0,0,0,0.5);">
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
            <h3 style="text-align: center; margin: 10px 0; font-size: 1.4rem; color: #f3f4f6;">Devolución Realizada</h3>
            <p style="text-align: center; margin: 10px 0; color: #9ca3af;">La devolución se ha procesado correctamente.</p>
            <?php if (isset($_SESSION['devolucionDetalles']) && is_array($_SESSION['devolucionDetalles'])): ?>
                <div style="background: #374151; border-radius: 8px; padding: 15px; margin: 15px 0; text-align: left;">
                    <p style="margin: 5px 0; font-size: 0.9rem; color: #e5e7eb;"><strong>Ticket:</strong>
                        #<?php echo htmlspecialchars($_SESSION['devolucionDetalles']['ticket'] ?? ''); ?></p>
                    <p style="margin: 5px 0; font-size: 0.9rem; color: #e5e7eb;"><strong>Productos devueltos:</strong>
                        <?php echo htmlspecialchars($_SESSION['devolucionDetalles']['productos'] ?? ''); ?></p>
                    <p style="margin: 5px 0; font-size: 0.9rem; color: #e5e7eb;"><strong>Importe devuelto:</strong> <span
                            style="color: #f87171; font-weight: bold;">-<?php echo number_format($_SESSION['devolucionDetalles']['total'] ?? 0, 2, ',', '.'); ?>
                            €</span></p>
                </div>
                <?php unset($_SESSION['devolucionDetalles']); ?>
            <?php
    endif; ?>
            <div style="text-align: center; margin-top: 15px;">
                <button onclick="document.getElementById('devolucionExito').remove()"
                    style="background: #dc2626; color: white; padding: 10px 30px; border: none; border-radius: 6px; cursor: pointer; font-size: 1rem; width: 100%;">Aceptar</button>
            </div>
        </div>
    </div>
    <?php
    // Limpiar la sesión después de mostrar
    unset($_SESSION['devolucionExito']);
?>
<?php
endif; ?>

<!-- ##=========================== MODAL: CIERRE TEMPORAL ===========================## -->
<!-- Modal para elegir entre pausa (descanso) o cambio de turno (cierre sesión caja) -->
<div class="modal-overlay" id="modalCierreTemporal" style="display:none;">
    <div class="modal-content modal-premium" style="max-width: 500px;">
        <div class="modal-header-premium" style="background: linear-gradient(135deg, #4b5563, #1f2937);">
            <div class="icon-container-discount">
                <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none"
                    stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
            </div>
            <h3>Cierre Temporal</h3>
            <p>Selecciona el motivo de la interrupción</p>
        </div>

        <div class="modal-body-premium">
            <div class="modal-opciones-doc" style="margin-top: 20px;">
                <!-- Opción 1: Pausa / Descanso (Logout simple, caja abierta) -->
                <div class="opcion-doc" onclick="pausarSesion()">
                    <div style="background: #eff6ff; padding: 15px; border-radius: 50%; margin-bottom: 10px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none"
                            stroke="#2563eb" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="6" y="4" width="4" height="16"></rect>
                            <rect x="14" y="4" width="4" height="16"></rect>
                        </svg>
                    </div>
                    <span class="opcion-titulo">Pausa / Descanso</span>
                    <span class="opcion-desc">Cerrar sesión de usuario.<br>La caja se mantiene <b>Abierta</b>.</span>
                </div>

                <!-- Opción 2: Cambio de Turno (Cierre de caja completo) -->
                <div class="opcion-doc" onclick="cambiarTurno()">
                    <div style="background: #f0fdf4; padding: 15px; border-radius: 50%; margin-bottom: 10px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none"
                            stroke="#16a34a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 2.1l4 4-4 4"></path>
                            <path d="M3 12.2v-2a4 4 0 0 1 4-4h12.8"></path>
                            <path d="M7 21.9l-4-4 4-4"></path>
                            <path d="M21 11.8v2a4 4 0 0 1-4 4H4.2"></path>
                        </svg>
                    </div>
                    <span class="opcion-titulo">Cambio de Turno</span>
                    <span class="opcion-desc">Cerrar sesión de usuario.<br>La caja se mantiene <b>Abierta</b>.</span>
                </div>
            </div>

            <div style="margin-top: 30px; border-top: 1px solid var(--border-main); padding-top: 20px; text-align: center;">
                <button type="button" class="btn-modal-cancelar" onclick="cerrarModal('modalCierreTemporal')">
                    Cancelar
                </button>
            </div>
        </div>
    </div>
</div>

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
            <h3>Retirar Dinero</h3>
            <p>Ingresa la cantidad que deseas retirar de la caja</p>
        </div>

        <div class="modal-body-premium">
            <!-- Formulario de retiro de dinero -->
            <form id="formRetiro" method="POST" action="index.php" onsubmit="return validarRetiro()">
                <input type="hidden" name="accion" value="retirarDinero">

                <!-- Campo: cantidad a retirar en euros -->
                <div class="form-group-premium">
                    <label for="importeRetiro">Cantidad a Retirar (€)</label>
                    <input type="number" name="importeRetiro" id="importeRetiro" step="0.01" min="0.01"
                        placeholder="0.00" required>
                    <small style="color: var(--text-muted); display: block; margin-top: 5px;">
                        Efectivo disponible: <span
                            id="efectivoDisponible"><?php echo number_format($sesionCaja ? $sesionCaja->getImporteActual() : 0, 2, ',', '.'); ?></span>
                        €
                    </small>
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
                        style="flex: 1; background: #ea580c; color: white;">Confirmar
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
            <h3 class="resumen-total-ventas" style="margin-top: 10px; border-top:none; padding-top:0;">Retiro Realizado</h3>
            <p class="modal-subtitulo-cliente">El importe ha sido restado del efectivo en caja correctamente.</p>
            <button class="btn-cerrar-exito" style="background: #ea580c; margin-top: 20px;"
                onclick="document.getElementById('retiroExito').remove()">Aceptar</button>
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
            <h3 style="color: var(--accent-danger);">Error en el retiro</h3>
            <!-- Mensaje de error escapado con htmlspecialchars para seguridad XSS -->
            <p style="color: var(--text-main); margin-bottom: 25px;">
                <?php echo htmlspecialchars($_SESSION['retiroError']); ?>
            </p>
            <button class="btn-modal-cancelar" onclick="document.getElementById('retiroError').remove()"
                style="width: 100%;">Aceptar</button>
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
            <h3 style="color: var(--accent-danger);">Error en la venta</h3>
            <!-- Mensaje de error escapado con htmlspecialchars para seguridad XSS -->
            <p style="color: var(--text-main); margin-bottom: 25px;">
                <?php echo htmlspecialchars($_SESSION['ventaError']); ?>
            </p>
            <button class="btn-modal-cancelar" onclick="cerrarModal('ventaError')" style="width: 100%;">Aceptar</button>
        </div>
    </div>
    <?php unset($_SESSION['ventaError']); ?>
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
            <h3 style="color: var(--text-main); font-size: 1.4rem; margin-bottom: 10px;">Arqueo de Caja</h3>
            <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 20px;">
                Efectivo esperado: <strong
                    style="color: var(--accent); font-size: 1.1rem;"><?php echo number_format($_SESSION['resumenCaja']['importeActual'], 2, ',', '.'); ?>
                    €</strong>
            </p>

            <!-- Billetes -->
            <div style="margin-bottom: 12px;">
                <p style="margin: 0 0 6px 0; font-size: 0.8rem; font-weight: 600; color: var(--text-muted);">BILLETES</p>
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
                <p style="margin: 0 0 6px 0; font-size: 0.8rem; font-weight: 600; color: var(--text-muted);">MONEDAS</p>
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
                    <span style="color: var(--text-muted);">Efectivo esperado:</span>
                    <span id="arqueoEsperado"
                        style="font-weight: 600;"><?php echo number_format($_SESSION['resumenCaja']['importeActual'], 2, ',', '.'); ?>
                        €</span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                    <span style="color: var(--text-muted);">Efectivo contado:</span>
                    <span id="arqueoContado" style="font-weight: 600; color: var(--accent);">0,00 €</span>
                </div>
                <div
                    style="display: flex; justify-content: space-between; padding-top: 8px; border-top: 1px dashed var(--border-main);">
                    <span style="font-weight: 600;">Diferencia:</span>
                    <span id="arqueoDiferencia" style="font-weight: 700; color: var(--accent-success);">0,00 €</span>
                </div>
            </div>

            <!-- Observaciones -->
            <textarea id="arqueoObservaciones" placeholder="Observaciones (opcional)"
                style="width: 100%; padding: 10px; border: 1px solid var(--border-main); border-radius: 6px; resize: none; font-size: 0.85rem; margin-bottom: 20px; background: var(--bg-input); color: var(--text-main);"
                rows="2"></textarea>

            <!-- Botones -->
            <div style="display: flex; gap: 10px; justify-content: center;">
                <button class="btn-modal-cancelar"
                    onclick="document.getElementById('arqueoModal').style.display='none';">Cancelar</button>
                <button class="btn-cerrar-exito" style="margin-top: 0; background: var(--accent);"
                    onclick="continuarArqueo()">Continuar</button>
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
                    <p>Cierre de Caja - <?php echo date('d/m/Y H:i'); ?></p>
                </div>

                <h4 class="resumen-caja-titulo">
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
                        <?php
    endif; ?>
                        <?php if (isset($_SESSION['resumenCaja']['totalRetiros']) && $_SESSION['resumenCaja']['totalRetiros'] > 0): ?>
                            <br><span style="font-size: 0.75rem; color: #ea580c;">(Retiros:
                                -<?php echo number_format($_SESSION['resumenCaja']['totalRetiros'], 2, ',', '.'); ?>
                                €)</span>
                        <?php
    endif; ?>
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
                        <?php
    endif; ?>
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
                        <?php
    endif; ?>
                    </div>
                </div>

                <!-- TOTAL GENERAL de ventas del día (suma de todos los métodos) -->
                <div class="resumen-total-ventas">
                    <strong>TOTAL VENTAS:</strong>
                    <strong
                        class="total-monto-verde"><?php echo number_format($_SESSION['resumenCaja']['totalGeneral'], 2, ',', '.'); ?>
                        €</strong>
                </div>

                <!-- Detalles reales de la caja: fondo inicial, devoluciones y efectivo real -->
                <div class="resumen-caja-detalles">
                    <!-- Fondo de caja inicial (importe con el que se abrió la caja) -->
                    <div class="resumen-detalle-fila">
                        <span>Fondo inicial:</span>
                        <span><?php echo number_format($_SESSION['resumenCaja']['importeInicial'], 2, ',', '.'); ?> €</span>
                    </div>
                    <!-- Total de devoluciones realizadas durante la sesión -->
                    <div class="resumen-detalle-fila" style="color: #ef4444;">
                        <span>Total Devoluciones:</span>
                        <span
                            style="font-weight: 600;">-<?php echo number_format($_SESSION['resumenCaja']['totalDevoluciones'], 2, ',', '.'); ?>
                            €</span>
                    </div>
                    <!-- Total de retiros realizados durante la sesión -->
                    <div class="resumen-detalle-fila" style="color: #ef4444;">
                        <span>Total Retiros:</span>
                        <span
                            style="font-weight: 600;">-<?php echo number_format($_SESSION['resumenCaja']['totalRetiros'] ?? 0, 2, ',', '.'); ?>
                            €</span>
                    </div>
                    <!-- Efectivo real que debería haber en la caja física -->
                    <div class="caja-efectivo-real">
                        <span>EFECTIVO ESPERADO:</span>
                        <span><?php echo number_format($_SESSION['resumenCaja']['importeActual'], 2, ',', '.'); ?> €</span>
                    </div>
                    <!-- Arqueo: efectivo contado y diferencia -->
                    <div id="arqueoResumen"
                        style="background: var(--bg-accent-success); padding: 10px; border-radius: 8px; margin-top: 10px; border: 1px solid var(--accent-success); opacity: 0.9;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                            <span style="color: var(--text-main);">Efectivo contado:</span>
                            <span id="arqueoContadoResumen" style="font-weight: 600; color: var(--text-main);">--</span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: var(--text-main);">Diferencia:</span>
                            <span id="arqueoDiferenciaResumen" style="font-weight: 600;">--</span>
                        </div>
                    </div>
                    <input type="hidden" id="arqueoTotalContado" value="0">
                    <input type="hidden" id="arqueoDiferenciaValue" value="0">
                </div>

                <!-- Footer visible solo al imprimir: espacio para firma y sello -->
                <div class="solo-impresion solo-impresion-footer">
                    <p>Firma y sello:</p>
                    <br><br><br>
                </div>
            </div>

            <!-- Opción para guardar cambio para el siguiente turno -->
            <div class="cambio-turno-container">
                <label for="cambio" class="cambio-turno-label">
                    💰 Cambio a guardar para el siguiente turno:
                </label>
                <input type="number" id="cambio" name="cambio" step="0.01" min="0"
                    value="<?php echo number_format($_SESSION['resumenCaja']['importeActual'], 2, '.', ''); ?>"
                    placeholder="0,00"
                    style="width: 100%; padding: 10px; text-align: center; font-size: 16px; border-radius: 6px;">
                <p class="cambio-turno-subtitulo">
                    Cantidad de efectivo que se quedará en la caja para el próximo turno
                </p>
            </div>

            <!-- Botones: Cancelar (cierra sin cerrar caja) y Confirmar Cierre (cierra la caja definitivamente) -->
            <div style="display: flex; gap: 10px; justify-content: center; margin-top: 20px;">
                <button class="btn-modal-cancelar"
                    onclick="document.getElementById('cajaPrevisualizacion').style.display='none';">Cancelar</button>
                <form method="POST" action="index.php" style="margin: 0;">
                    <input type="hidden" name="accion" value="confirmarCaja">
                    <input type="hidden" name="cambio" id="cambioHidden"
                        value="<?php echo $_SESSION['resumenCaja']['importeActual']; ?>">
                    <input type="hidden" name="arqueoTotalContado" id="arqueoTotalContadoForm" value="0">
                    <input type="hidden" name="arqueoDetalleConteo" id="arqueoDetalleConteoForm" value="">
                    <input type="hidden" name="arqueoObservaciones" id="arqueoObservacionesHidden" value="">
                    <button type="submit" class="btn-cerrar-exito" style="margin-top: 0; background: #2563eb;"
                        onclick="document.getElementById('cambioHidden').value = document.getElementById('cambio').value; document.getElementById('arqueoObservacionesHidden').value = document.getElementById('arqueoObservaciones').value || '';">Confirmar
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
            <h3 style="color: var(--text-main); font-size: 1.4rem; margin-bottom: 20px;">Caja Cerrada Correctamente</h3>
            <p style="color: var(--text-muted); font-size: 0.95rem; margin-bottom: 20px;">El recuento de ventas ha vuelto a
                0 para el
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
                    <?php
    endif; ?>
                    <?php if (isset($_SESSION['resumenCaja']['totalRetiros']) && $_SESSION['resumenCaja']['totalRetiros'] > 0): ?>
                        <p style="margin: 0px 0 5px 0; display:flex; justify-content:flex-end; font-size: 0.8rem;">
                            <span>(Retiros:
                                -<?php echo number_format($_SESSION['resumenCaja']['totalRetiros'], 2, ',', '.'); ?>
                                €)</span>
                        </p>
                    <?php
    endif; ?>

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
                    <?php
    endif; ?>

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
                    <?php
    endif; ?>
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
                    <p style="margin: 5px 0; display:flex; justify-content:space-between; font-size: 0.9em; color: #000;">
                        <span>Total Retiros:</span>
                        <span>- <?php echo number_format($_SESSION['resumenCaja']['totalRetiros'] ?? 0, 2, ',', '.'); ?>
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
        <h3 style="color: var(--text-main); margin-top: 15px;">¡Bienvenido de nuevo!</h3>
        <p style="color: var(--text-muted); margin-bottom: 20px;">Tu descanso ha terminado. La sesión de caja sigue activa y lista para trabajar.</p>
        <button class="btn-cerrar-exito" style="background: #2563eb; width: 100%;"
            onclick="cerrarModalBienvenida('modalDescansoTerminado')">Continuar</button>
    </div>
</div>

<!-- Modal: Nuevo Turno -->
<div class="modal-overlay" id="modalNuevoTurno" style="display:none;">
    <div class="modal-content modal-premium" style="max-width: 450px;">
        <div class="modal-header-premium modal-header-blue">
            <div class="icon-container-discount">
                <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none"
                    stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <polyline points="16 11 18 13 22 9"></polyline>
                </svg>
            </div>
            <h3>Inicio de Nuevo Turno</h3>
            <p>Información sobre el relevo de caja</p>
        </div>
        <div class="modal-body-premium" style="text-align: center;">
            <div style="background: var(--bg-main); padding: 15px; border-radius: 12px; border: 1px solid var(--border-main); margin-bottom: 20px;">
                <p style="margin-bottom: 10px; font-size: 0.9rem; color: var(--text-muted);">El turno anterior fue cerrado por:</p>
                <div style="font-weight: 600; font-size: 1.1rem; color: var(--text-main); margin-bottom: 5px;" id="welcomeOldUser">--</div>
                <div style="font-size: 0.85rem; color: var(--accent);" id="welcomeCloseTime">--</div>
            </div>
            
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px; border-top: 1px dashed var(--border-main);">
                <span style="color: var(--text-muted); font-size: 0.9rem;">Hora actual:</span>
                <span style="font-weight: 600;" id="welcomeCurrentTime"><?php echo date('H:i'); ?></span>
            </div>
            
            <button class="btn-apply-premium" style="width: 100%; margin-top: 20px; background: #2563eb; color: white;"
                onclick="cerrarModalBienvenida('modalNuevoTurno')">Empezar Turno</button>
        </div>
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
    document.addEventListener('DOMContentLoaded', function() {
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
                } catch(e) {
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
            <h3>Historial de Ventas</h3>
            <p id="historialFecha">Ventas desde la apertura de caja</p>
        </div>

        <div class="modal-body-premium">
            <div id="historialVentasContenido" style="max-height: 400px; overflow-y: auto;">
                <!-- Aquí se cargarán las ventas -->
            </div>
            <div
                style="display: flex; justify-content: space-between; margin-top: 20px; padding-top: 15px; border-top: 1px solid var(--border-main);">
                <div id="historialTotal" style="font-weight: bold; font-size: 1.1rem;"></div>
                <button class="btn-modal-cancelar" onclick="cerrarModal('modalHistorialVentas')"
                    style="min-width: 100px;">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- ##=========================== MODAL: DETALLE DE VENTA ===========================## -->
<div class="modal-overlay" id="modalDetalleVenta" style="display:none;">
    <div class="modal-content modal-premium" style="max-width: 700px; padding: 20px;">
        <div class="modal-header-premium">
            <h3>Detalle de Venta</h3>
        </div>
        <div class="modal-body-premium" style="padding: 10px;">
            <div id="detalleVentaContenido">
                <!-- Aquí se cargarán los detalles de la venta -->
            </div>
        </div>
        <div
            style="display: flex; justify-content: flex-end; margin-top: 20px; padding: 15px; border-top: 1px solid var(--border-main);">
            <button class="btn-modal-cancelar" onclick="cerrarModal('modalDetalleVenta')"
                style="min-width: 100px; margin-right: 10px;">Cerrar</button>
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
            diffElement.textContent = '✓ Caja correcta';
        } else if (diferencia > 0) {
            diffElement.style.color = '#2563eb'; // Azul - sobrante
            diffElement.textContent = '+' + diferencia.toFixed(2).replace('.', ',') + ' € (Sobrante)';
        } else {
            diffElement.style.color = '#dc2626'; // Rojo - faltante
            diffElement.textContent = diferencia.toFixed(2).replace('.', ',') + ' € (Faltante)';
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
                arqueoDiferenciaResumen.textContent = '✓ Caja correcta';
                arqueoDiferenciaResumen.style.color = '#059669';
            } else if (diferencia > 0) {
                arqueoDiferenciaResumen.textContent = '+' + diferencia.toFixed(2).replace('.', ',') + ' € (Sobrante)';
                arqueoDiferenciaResumen.style.color = '#2563eb';
            } else {
                arqueoDiferenciaResumen.textContent = diferencia.toFixed(2).replace('.', ',') + ' € (Faltante)';
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
            fechaDiv.textContent = 'Ventas desde la apertura de caja - ' + hoy.toLocaleDateString('es-ES', { day: 'numeric', month: 'long', year: 'numeric' });
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
                        contenido.innerHTML = '<p style="text-align: center; color: var(--text-muted); padding: 40px;">No hay sesión de caja abierta.</p>';
                        totalDiv.textContent = '';
                        return;
                    }
                    throw new Error(ventas.error);
                }
                if (!ventas || ventas.length === 0) {
                    contenido.innerHTML = '<p style="text-align: center; color: var(--text-muted); padding: 40px;">No hay ventas registradas hoy.</p>';
                    totalDiv.textContent = 'Total: 0.00 €';
                    return;
                }

                // Calcular total
                let total = 0;
                let html = '<table class="historial-ventas-tabla">';
                html += '<thead><tr>';
                html += '<th>Hora</th>';
                html += '<th>Usuario</th>';
                html += '<th>Cantidad</th>';
                html += '<th>Forma de pago</th>';
                html += '<th>Total</th>';
                html += '<th>Acciones</th>';
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
                totalDiv.textContent = 'Total del día: ' + total.toFixed(2).replace('.', ',') + ' € (' + ventas.length + ' ventas)';
            })
            .catch(err => {
                console.error('Error cargando historial:', err);
                contenido.innerHTML = '<p style="text-align: center; color: #dc2626; padding: 40px;">Error al cargar el historial de ventas: ' + err.message + '</p>';
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
        contenido.innerHTML = '<p style="text-align: center; padding: 20px;">Cargando...</p>';

        fetch('api/ventas.php?detalleVenta=' + idVenta)
            .then(res => res.json())
            .then(data => {
                if (data.error) {
                    contenido.innerHTML = '<p style="text-align: center; color: #dc2626; padding: 20px;">Error: ' + data.error + '</p>';
                    return;
                }

                const venta = data.venta;
                const lineas = data.lineas;
                const fecha = new Date(venta.fecha).toLocaleString('es-ES');

                let html = '<div class="detalle-venta-header">';
                html += '<div style="display: flex; justify-content: space-between; align-items: center;">';
                html += '<div><strong style="font-size: 18px;">Venta #' + venta.id + '</strong></div>';
                html += '<div style="font-size: 12px; opacity: 0.9;">' + fecha + '</div>';
                html += '</div>';
                html += '</div>';

                html += '<div class="detalle-venta-info">';
                html += '<div><strong>Tipo:</strong> ' + (venta.tipoDocumento === 'factura' ? '📄 Factura' : '🧾 Ticket') + '</div>';
                html += '<div><strong>Pago:</strong> ' + (venta.metodoPago || '💵 Efectivo') + '</div>';
                html += '</div>';

                html += '<div style="max-height: 300px; overflow-y: auto; margin: 15px 0;">';
                html += '<table class="detalle-venta-tabla">';
                html += '<thead><tr>';
                html += '<th style="padding: 10px; text-align: left;">Producto</th>';
                html += '<th style="padding: 10px; text-align: center;">Cant.</th>';
                html += '<th style="padding: 10px; text-align: right;">P.U.</th>';
                html += '<th style="padding: 10px; text-align: right;">Subtotal</th>';
                html += '</tr></thead><tbody class="detalle-venta-body">';

                lineas.forEach(item => {
                    const subtotal = (item.precioUnitario * item.cantidad).toFixed(2).replace('.', ',');
                    html += '<tr>';
                    html += '<td>' + item.producto_nombre + '</td>';
                    html += '<td style="text-align: center;">' + item.cantidad + '</td>';
                    html += '<td style="text-align: right;">' + parseFloat(item.precioUnitario).toFixed(2).replace('.', ',') + ' €</td>';
                    html += '<td style="text-align: right; font-weight: 600;">' + subtotal + ' €</td>';
                    html += '</tr>';
                });

                html += '</tbody></table>';
                html += '</div>';
                html += '<div style="margin-top: 20px; padding: 15px; background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white; border-radius: 8px; text-align: center; font-weight: bold; font-size: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">';
                html += 'TOTAL: ' + parseFloat(venta.total).toFixed(2).replace('.', ',') + ' €';
                html += '</div>';

                contenido.innerHTML = html;
            })
            .catch(err => {
                console.error('Error:', err);
                contenido.innerHTML = '<p style="text-align: center; color: #dc2626; padding: 20px;">Error al cargar los detalles</p>';
            });
    }


    /**
     * Envía un ticket por correo electrónico
     */
    function enviarTicketCorreo(idVenta) {
        const email = prompt('Introduce el correo electrónico del cliente:');
        if (!email || !email.includes('@')) { alert('Por favor, introduce un correo válido.'); return; }

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
                    id: venta.id, tipo: venta.tipoDocumento, total: parseFloat(venta.total),
                    fecha: venta.fecha, metodoPago: venta.metodoPago,
                    entregado: parseFloat(venta.importeEntregado) || 0,
                    cambio: parseFloat(venta.cambioDevuelto) || 0,
                    descuentoTipo: 'ninguno', descuentoValor: 0,
                    clienteNif: '', clienteNombre: '', clienteDir: '', clienteObs: '',
                    carrito: lineas.map(l => ({ idProducto: l.idProducto, nombre: l.producto_nombre, precio: parseFloat(l.precioUnitario), iva: parseInt(l.iva) || 21, cantidad: l.cantidad }))
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
                    id: venta.id, tipo: venta.tipoDocumento, total: parseFloat(venta.total),
                    fecha: venta.fecha, metodoPago: venta.metodoPago,
                    entregado: parseFloat(venta.importeEntregado) || 0,
                    cambio: parseFloat(venta.cambioDevuelto) || 0,
                    descuentoTipo: 'ninguno', descuentoValor: 0,
                    clienteNif: '', clienteNombre: '', clienteDir: '', clienteObs: '',
                    carrito: lineas.map(l => ({ idProducto: l.idProducto, nombre: l.producto_nombre, precio: parseFloat(l.precioUnitario), iva: parseInt(l.iva) || 21, cantidad: l.cantidad }))
                };
                ultimaVenta = ventaHistorialTemporal;
                // Abrir primero el modal de éxito y luego mostrar el formulario de email
                document.getElementById('ventaExito').style.display = 'flex';
                mostrarFormEmail();
            })
            .catch(err => { console.error('Error:', err); alert('Error al obtener los datos del ticket: ' + err.message); });
    }
</script>

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

        // PVP Actual: Intentar leer de data-pvp, si no existe o es NaN, calcularlo ahora.
        let pvpActual = parseFloat(elemento.dataset.pvp);
        if (isNaN(pvpActual)) {
            pvpActual = round2(precioBase * (1 + (iva / 100)));
        } else {
            pvpActual = round2(pvpActual);
        }

        const stockMax = parseInt(elemento.dataset.stock) || 0;

        // Obtener datos de la tarifa seleccionada
        const selectTarifa = elemento.querySelector('.tarifa-selector');
        let tarifaNombre = 'Cliente';
        let tarifaDescuento = 0;
        let precioBaseSinTarifa = parseFloat(elemento.dataset.precioOriginal || elemento.dataset.precio) || precioBase;

        // PVP Original sin ninguna tarifa aplicada
        let pvpOriginalUnitario = round2(precioBaseSinTarifa * (1 + (iva / 100)));

        if (selectTarifa) {
            const selectedOption = selectTarifa.options[selectTarifa.selectedIndex];
            tarifaNombre = selectedOption.text;
            tarifaDescuento = parseFloat(selectTarifa.value) || 0;
        }

        const existente = carrito.find(item => item.idProducto === id && item.tarifaNombre === tarifaNombre);

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
            carrito.push({
                idProducto: id,
                nombre: nombre,
                precio: precioBase,
                pvpOriginalUnitario: pvpOriginalUnitario,
                pvpUnitario: pvpActual,
                iva: iva,
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
        carrito = [];
        descuento = { tipo: 'ninguno', valor: 0, cupon: '' };
        puntosCanjeados = null; // Resetear puntos canjeados al vaciar el carrito
        // Resetear también el select de tarifa a Cliente
        const tarifaCliente = tarifasPrefijadas.find(t => t.nombre === 'Cliente');
        if (tarifaCliente) {
            document.getElementById('tarifaVenta').value = tarifaCliente.id;
        } else if (tarifasPrefijadas.length > 0) {
            document.getElementById('tarifaVenta').value = tarifasPrefijadas[0].id;
        }
        actualizarTicket();
    }

    // Función global para resetear puntos canjeados después de una venta exitosa
    function resetearPuntosCanjeados() {
        puntosCanjeados = null;
    }

    /**
     * posponerVenta()
     * Guarda la venta actual en sessionStorage para recuperarla después.
     * Guarda: carrito, descuento, y fecha/hora de la posposición.
     */
    function posponerVenta() {
        if (carrito.length === 0) {
            alert('No hay productos en el carrito para posponer');
            return;
        }

        const ventaPospuesta = {
            carrito: carrito,
            descuento: descuento,
            tarifa: document.getElementById('tarifaVenta').value,
            fecha: new Date().toLocaleString('es-ES')
        };

        // Guardar en sessionStorage
        sessionStorage.setItem('ventaPospuesta', JSON.stringify(ventaPospuesta));

        // Vaciar el carrito
        carrito = [];
        descuento = { tipo: 'ninguno', valor: 0, cupon: '' };
        const tarifaCliente2 = tarifasPrefijadas.find(t => t.nombre === 'Cliente');
        if (tarifaCliente2) {
            document.getElementById('tarifaVenta').value = tarifaCliente2.id;
        } else if (tarifasPrefijadas.length > 0) {
            document.getElementById('tarifaVenta').value = tarifasPrefijadas[0].id;
        }
        actualizarTicket();

        // Mostrar mensaje
        alert('✅ Venta pospuesta. Puedes recuperarla cuando quieras.');

        // Actualizar estado del botón recuperar
        actualizarBotonRecuperar();
    }

    /**
     * recuperarVenta()
     * Recupera la última venta pospuesta y la vuelve al carrito.
     */
    function recuperarVenta() {
        const ventaJson = sessionStorage.getItem('ventaPospuesta');

        if (!ventaJson) {
            alert('No hay ninguna venta pospuesta para recuperar');
            return;
        }

        const ventaPospuesta = JSON.parse(ventaJson);

        // Restaurar el carrito y descuento
        if (carrito.length > 0) {
            if (!confirm('⚠️ Ya hay productos en el carrito. ¿Quieres añadir los productos pospuestos?')) {
                return;
            }
            // Añadir los productos pospuestos al carrito existente manteniendo sus datos redondeados
            ventaPospuesta.carrito.forEach(productoPospuesto => {
                // Buscamos si ya existe el mismo producto con la misma tarifa para agruparlo
                const existente = carrito.find(item =>
                    item.idProducto === productoPospuesto.idProducto &&
                    item.tarifaNombre === productoPospuesto.tarifaNombre
                );

                if (existente) {
                    existente.cantidad += productoPospuesto.cantidad;
                } else {
                    carrito.push(productoPospuesto);
                }
            });
        } else {
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
        }

        actualizarTicket();

        // Limpiar la venta pospuesta
        sessionStorage.removeItem('ventaPospuesta');

        alert('✅ Venta recuperada: ' + ventaPospuesta.fecha);

        // Actualizar estado del botón recuperar
        actualizarBotonRecuperar();
    }

    /**
     * actualizarBotonRecuperar()
     * Habilita/deshabilita el botón de recuperar según si hay venta pospuesta.
     */
    function actualizarBotonRecuperar() {
        const btnRecuperar = document.getElementById('btnRecuperar');
        const ventaJson = sessionStorage.getItem('ventaPospuesta');

        if (ventaJson) {
            btnRecuperar.disabled = false;
            btnRecuperar.style.opacity = '1';
        } else {
            btnRecuperar.disabled = true;
            btnRecuperar.style.opacity = '0.5';
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
                                const precioBase = parseFloat(item.precio || item.precioConDescuento || 0);
                                item.pvpUnitario = round2(precioBase * (1 + (nuevoIVA / 100)));
                                // También actualizar el PVP original si existe
                                if (item.pvpOriginalUnitario) {
                                    const precioOriginal = parseFloat(item.precio || 0);
                                    item.pvpOriginalUnitario = round2(precioOriginal * (1 + (nuevoIVA / 100)));
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
        actualizarBotonRecuperar();
        verificarCambiosIvaProgramados();
    });

    /**
     * obtenerTotalCalculado()
     * Calcula el total del carrito aplicando el descuento vigente.
     * Los descuentos se aplican sobre la BASE IMPONIBLE (sin IVA), y luego se suma el IVA.
     * @returns {number} Total final (mínimo 0)
     */
    function obtenerTotalCalculado() {
        // El total es la suma de los subtotales de cada línea (PVP ya redondeado)
        let totalPVPBruto = carrito.reduce((sum, item) => {
            const subtotalLinea = round2(item.pvpUnitario * item.cantidad);
            return sum + subtotalLinea;
        }, 0);

        // Calcular descuento manual (global) sobre el total PVP acumulado
        let importeDescuentoManual = 0;
        if (descuento.tipo === 'porcentaje') {
            importeDescuentoManual = round2(totalPVPBruto * (descuento.valor / 100));
        } else if (descuento.tipo === 'fijo') {
            importeDescuentoManual = round2(descuento.valor);
        }

        return Math.max(0, round2(totalPVPBruto - importeDescuentoManual));
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
            // Si se vacía el carrito, resetear los puntos canjeados también
            puntosCanjeados = null;
            descuento = { tipo: 'ninguno', valor: 0, cupon: '' };
            document.getElementById('btnPosponer').disabled = true;
            return;
        }

        // Generar tabla HTML con las líneas del ticket
        let html = '<table class="ticket-tabla"><thead><tr><th>Producto</th><th>Cant.</th><th>Precio (PVP)</th><th>Subt.</th><th></th></tr></thead><tbody>';

        // Iterar sobre cada producto del carrito
        carrito.forEach((item, i) => {
            // Asegurar que pvpUnitario existe (fallback para items antiguos o corruptos)
            if (isNaN(item.pvpUnitario) || item.pvpUnitario === undefined) {
                const precio = parseFloat(item.precioConDescuento || item.precio || 0);
                const iva = parseInt(item.iva || 21);
                item.pvpUnitario = round2(precio * (1 + (iva / 100)));
            }
            if (isNaN(item.pvpOriginalUnitario) || item.pvpOriginalUnitario === undefined) {
                const precio = parseFloat(item.precio || 0);
                const iva = parseInt(item.iva || 21);
                item.pvpOriginalUnitario = round2(precio * (1 + (iva / 100)));
            }

            const subtotalRebajado = round2(item.pvpUnitario * item.cantidad);

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
                <td>${item.pvpUnitario.toFixed(2).replace('.', ',')} €</td>
                <td>${subtotalRebajado.toFixed(2).replace('.', ',')} €</td>
                <td><button class="btn-quitar" onclick="eliminarDelCarrito(${i})">✕</button></td>
            </tr>`;
        });

        html += '</tbody></table>';
        contenedor.innerHTML = html;

        // Calcular desglose de totales
        let totalPVPBruto = carrito.reduce((sum, item) => sum + round2(item.pvpUnitario * item.cantidad), 0);
        let totalPVPFinal = obtenerTotalCalculado();
        let descuentoManualImporte = round2(totalPVPBruto - totalPVPFinal);

        // Agrupar ahorros de tarifas
        const ahorrosTarifasAgrupados = {};
        carrito.forEach(item => {
            const ahorroUnitario = round2(item.pvpOriginalUnitario - item.pvpUnitario);
            const ahorroLinea = round2(ahorroUnitario * item.cantidad);

            if (ahorroLinea > 0) {
                const nombre = item.tarifaNombre;
                if (!ahorrosTarifasAgrupados[nombre]) {
                    ahorrosTarifasAgrupados[nombre] = 0;
                }
                ahorrosTarifasAgrupados[nombre] = round2(ahorrosTarifasAgrupados[nombre] + ahorroLinea);
            }
        });

        const ahorroTarifasTotal = round2(Object.values(ahorrosTarifasAgrupados).reduce((a, b) => a + b, 0));

        let htmlDesglose = `<div class="resumen-final-premium">`;

        // IVA Total: Recalculo exacto agrupando por tipo de IVA
        const desglosePorIVA = {};
        carrito.forEach(item => {
            const subtotalLineaPVP = round2(item.pvpUnitario * item.cantidad);
            const factorDescuentoManual = totalPVPBruto > 0 ? (totalPVPFinal / totalPVPBruto) : 0;
            const subtotalFinalPVP = subtotalLineaPVP * factorDescuentoManual;

            const tipoIVA = parseInt(item.iva);
            if (!desglosePorIVA[tipoIVA]) desglosePorIVA[tipoIVA] = 0;
            desglosePorIVA[tipoIVA] += subtotalFinalPVP;
        });

        let baseImponibleCalculada = 0;
        for (const [iva, pvpAcumulado] of Object.entries(desglosePorIVA)) {
            baseImponibleCalculada += round2(pvpAcumulado / (1 + (parseInt(iva) / 100)));
        }

        baseImponibleCalculada = round2(baseImponibleCalculada);
        const ivaTotal = round2(totalPVPFinal - baseImponibleCalculada);

        // Mostrar Base Imponible (Total Final - IVA)
        htmlDesglose += `
        <div class="resumen-fila-mini">
            <span>Base Imponible:</span>
            <span>${baseImponibleCalculada.toFixed(2).replace('.', ',')} €</span>
        </div>`;

        // Mostrar ahorros por tarifa si existen
        if (ahorroTarifasTotal > 0.005) {
            for (const [nombre, importe] of Object.entries(ahorrosTarifasAgrupados)) {
                htmlDesglose += `
                <div class="resumen-fila-mini descuento-texto">
                    <span>Ahorro ${nombre}:</span>
                    <span>- ${importe.toFixed(2).replace('.', ',')} €</span>
                </div>`;
            }
        }

        // Subtotal tras tarifas solo si hay cupón manual
        if (descuentoManualImporte > 0.005) {
            const textoManual = descuento.tipo === 'porcentaje' ? 'Descuento (' + descuento.valor + '%)' : 'Cupón ' + (descuento.cupon || 'Manual');
            htmlDesglose += `
            <div class="resumen-fila-mini descuento-texto" style="display: flex; justify-content: space-between; align-items: center;">
                <span style="color: #16a34a;">
                    <span style="cursor: pointer; color: #ef4444; margin-right: 5px;" onclick="quitarDescuento()" title="Quitar descuento">
                        <i class="fas fa-times-circle"></i>
                    </span>${textoManual}:
                </span>
                <span style="color: #16a34a;">- ${descuentoManualImporte.toFixed(2).replace('.', ',')} €</span>
            </div>`;
        }

        htmlDesglose += `
            <div class="resumen-fila-mini">
                <span>IVA:</span>
                <span>${ivaTotal.toFixed(2).replace('.', ',')} €</span>
            </div>
            <div class="resumen-fila-mini" style="font-weight: bold; border-top: 1px solid #e5e7eb; padding-top: 8px;">
                <span>Total Final (IVA incl.):</span>
                <span>${totalPVPFinal.toFixed(2).replace('.', ',')} €</span>
            </div>
        </div>`;

        // Actualizar el DOM
        document.getElementById('ticketDesglose').innerHTML = htmlDesglose;
        totalEl.textContent = totalPVPFinal.toFixed(2).replace('.', ',') + ' €';

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
        // Usar un pequeño epsilon o redondear para evitar errores de precisión en punto flotante
        const devolucionRedondeada = Math.round(devolucion * 100) / 100;

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
                spanDevolver.textContent = devolucionRedondeada.toFixed(2).replace('.', ',') + ' €';
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

        // Cerrar modal de cambio y abrir modal de tipo de documento
        cerrarModal('modalCambio');
        mostrarModalTipoDocumento();
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
            alert('No se pueden realizar ventas si la caja no está abierta. Por favor, realiza la Apertura de Caja.');
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

        // Cerrar modal de datos del cliente y proceder con la venta
        cerrarModal('modalDatosCliente');
        confirmarVenta(tipoDocumentoActual, nif, nombre, direccion, observaciones);
    }

    /**
     * mostrarModalPuntos()
     * Muestra el modal para preguntar si el cliente quiere registrar su DNI para acumular puntos.
     * Ahora simplemente salta directamente al modal de tipo de documento.
     */
    function mostrarModalPuntos() {
        // Saltar el modal de puntos y mostrar directamente el tipo de documento
        document.getElementById('modalTipoDoc').style.display = 'flex';
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
     * Se cierra el modal y se muestra el tipo de comprobante.
     */
    function confirmarSinPuntos() {
        cerrarModal('modalPuntos');
        // Mostrar modal de tipo de documento (ticket/factura)
        document.getElementById('modalTipoDoc').style.display = 'flex';
    }

    /**
     * abrirModalBuscarClienteRegistradoParaPuntos()
     * Abre el modal de búsqueda de cliente para acumular puntos.
     */
    function abrirModalBuscarClienteRegistradoParaPuntos() {
        document.getElementById('dniBusquedaCliente').value = '';
        document.getElementById('mensajeResultadoBusqueda').style.display = 'none';
        // Cambiamos el título para indicar que es para puntos
        document.querySelector('#modalBuscarClienteRegistrado h3').textContent = 'Cliente para Puntos';
        document.querySelector('#modalBuscarClienteRegistrado .modal-subtitulo').textContent = 'Introduce el DNI del cliente para acumular ' + document.getElementById('puntosPosibles').textContent + ' puntos';
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
        if (puntosCanjeados && puntosCanjeados.dni && puntosCanjeados.puntos > 0) {
            document.getElementById('inputPuntosCanjeadosDni').value = puntosCanjeados.dni;
            document.getElementById('inputPuntosCanjeadosCantidad').value = puntosCanjeados.puntos;
        } else {
            document.getElementById('inputPuntosCanjeadosDni').value = '';
            document.getElementById('inputPuntosCanjeadosCantidad').value = 0;
        }

        // Tarifa seleccionada
        document.getElementById('inputIdTarifa').value = document.getElementById('tarifaVenta').value;

        // Enviar el formulario al servidor
        document.getElementById('formVenta').submit();
        
        // Resetear puntos canjeados después de enviar (para la próxima venta)
        puntosCanjeados = null;
        descuento = { tipo: 'ninguno', valor: 0, cupon: '' };
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
        let sumaTotalesNumeric = 0; // PVP acumulado
        let desgloseIva = {}; // Para el resumen final por cada %

        ultimaVenta.carrito.forEach(item => {
            const cantidad = parseFloat(item.cantidad);
            const precioBase = parseFloat(item.precio);
            let ivaPorc = parseInt(item.iva);
            // Si el IVA es NaN, usar 21 por defecto
            if (isNaN(ivaPorc)) {
                ivaPorc = 21;
            }

            // Cálculos por unidad
            let precioPVP = 0;
            let precioBaseIndividual = precioBase;

            if (item.pvpUnitario !== null && item.pvpUnitario !== undefined) {
                // Si tenemos el PVP unitario exacto guardado, lo usamos como fuente de verdad
                precioPVP = parseFloat(item.pvpUnitario) || 0;
                // Calculamos la base hacia atrás para el desglose fiscal
                precioBaseIndividual = precioPVP / (1 + (ivaPorc / 100));
            } else {
                // Fallback para ventas antiguas sin pvpUnitario
                const cuotaIvaUnidad = precioBase * (ivaPorc / 100);
                precioPVP = precioBase + cuotaIvaUnidad;
            }

            // Cálculos totales de la línea
            const subtotalPVP = round2(precioPVP * cantidad);
            const subtotalBase = round2(subtotalPVP / (1 + (ivaPorc / 100)));
            const subtotalIva = round2(subtotalPVP - subtotalBase);

            sumaTotalesNumeric += subtotalPVP;

            // Acumular para el desglose fiscal final
            if (!desgloseIva[ivaPorc]) {
                desgloseIva[ivaPorc] = { base: 0, cuota: 0 };
            }
            desgloseIva[ivaPorc].base += subtotalBase;
            desgloseIva[ivaPorc].cuota += subtotalIva;

            // Formatear precios
            const baseFmt = subtotalBase.toFixed(2).replace('.', ',');
            const pvpFmt = subtotalPVP.toFixed(2).replace('.', ',');
            const unitarioFmt = precioBase.toFixed(2).replace('.', ',');

            lineasHtml += `<tr>
                <td style="padding: 8px 4px;">${item.nombre}</td>
                <td style="text-align:center">${cantidad}</td>
                <td style="text-align:right">${unitarioFmt} €</td>
                <td style="text-align:center">${ivaPorc}%</td>
                <td style="text-align:right">${pvpFmt} €</td>
            </tr>`;
        });

        // Calcular descuentos aplicados sobre el total PVP
        // Calcular ahorros de tarifas agrupados por nombre
        const ahorrosTarifasAgrupados = {};
        let importeDescuentoTarifa = 0;

        if (ultimaVenta.carrito && Array.isArray(ultimaVenta.carrito)) {
            ultimaVenta.carrito.forEach(item => {
                const pvpOrig = parseFloat(item.pvpOriginalUnitario || item.pvpUnitario || 0);
                const pvpReal = parseFloat(item.pvpUnitario || 0);
                const ahorroUnitario = round2(pvpOrig - pvpReal);
                const ahorroLinea = round2(ahorroUnitario * (parseInt(item.cantidad) || 0));

                if (ahorroLinea > 0.005) {
                    const nombre = item.tarifaNombre || 'Tarifa';
                    if (!ahorrosTarifasAgrupados[nombre]) {
                        ahorrosTarifasAgrupados[nombre] = 0;
                    }
                    ahorrosTarifasAgrupados[nombre] = round2(ahorrosTarifasAgrupados[nombre] + ahorroLinea);
                    importeDescuentoTarifa = round2(importeDescuentoTarifa + ahorroLinea);
                }
            });
        }

        // Texto descriptivo para el descuento de tarifa (se mantiene por compatibilidad si fuera necesario)
        let textoDescuentoTarifa = Object.keys(ahorrosTarifasAgrupados).join(', ');

        // Calcular descuento manual (código promocional o porcentaje manual)
        let importeDescuentoManual = 0;
        let textoDescuentoManual = '';
        let tipoManual = ultimaVenta.descuentoManualTipo || ultimaVenta.descuentoTipo;
        let valorManual = parseFloat(ultimaVenta.descuentoManualValor || ultimaVenta.descuentoValor) || 0;
        let cuponManual = ultimaVenta.descuentoManualCupon || ultimaVenta.descuentoCupon;

        if (tipoManual === 'porcentaje') {
            importeDescuentoManual = sumaTotalesNumeric * (valorManual / 100);
            textoDescuentoManual = valorManual + '%';
        } else if (tipoManual === 'fijo') {
            importeDescuentoManual = valorManual;
            textoDescuentoManual = 'Cupón ' + (cuponManual || '');
        } else if (cuponManual && cuponManual !== '') {
            // También puede haber un cupón sin tipo definido
            textoDescuentoManual = 'Cupón ' + cuponManual;
        }

        // Descuento total (solo el manual, ya que las tarifas ya están en sumaTotalesNumeric)
        const totalVentaPVP = Math.max(0, sumaTotalesNumeric - importeDescuentoManual);

        // Si hay descuento manual, debemos prorratearlo en la base e IVA para el desglose
        let factorDescuento = sumaTotalesNumeric > 0 ? (totalVentaPVP / sumaTotalesNumeric) : 1;

        // Generar tabla de totales con desglose fiscal
        let totalesHtml = `
            <table class="tabla-totales" style="width:100%; font-size: 0.9rem; margin-top:10px;">
        `;

        // Mostrar ahorros por tarifa agrupados si existen (como información, no restan del total de nuevo)
        if (importeDescuentoTarifa > 0.01) {
            for (const [nombre, importe] of Object.entries(ahorrosTarifasAgrupados)) {
                totalesHtml += `
                    <tr>
                        <td style="color: #16a34a;"><strong>Ahorro ${nombre}:</strong></td>
                        <td style="text-align:right; color: #16a34a;">- ${importe.toFixed(2).replace('.', ',')} €*</td>
                    </tr>
                `;
            }
        }

        // Si hay descuento manual, lo mostramos
        if (importeDescuentoManual > 0.01 && textoDescuentoManual) {
            totalesHtml += `
                <tr>
                    <td style="color: #16a34a;"><strong>Descuento (${textoDescuentoManual}):</strong></td>
                    <td style="text-align:right; color: #16a34a;">- ${importeDescuentoManual.toFixed(2).replace('.', ',')} €</td>
                </tr>
            `;
        }

        // Base imponible y cuota IVA por cada tipo
        totalesHtml += `
            <tr style="border-top: 1px solid #eee;">
                <td colspan="2" style="padding-top:10px;"><strong>Desglose de IVA:</strong></td>
            </tr>
        `;

        Object.keys(desgloseIva).sort().forEach(porc => {
            const baseFinal = round2(desgloseIva[porc].base * factorDescuento);
            const cuotaFinal = round2(desgloseIva[porc].cuota * factorDescuento);

            totalesHtml += `
                <tr style="font-size: 0.8rem; color: #444;">
                    <td>Base al ${porc}%:</td>
                    <td style="text-align:right">${baseFinal.toFixed(2).replace('.', ',')} €</td>
                </tr>
                <tr style="font-size: 0.8rem; color: #444;">
                    <td>Cuota IVA (${porc}%):</td>
                    <td style="text-align:right">${cuotaFinal.toFixed(2).replace('.', ',')} €</td>
                </tr>
            `;
        });

        totalesHtml += `
            <tr style="border-top: 1px solid #000;">
                <td style="font-size: 1.2rem; padding-top:10px;"><strong>TOTAL:</strong></td>
                <td style="font-size: 1.2rem; font-weight: bold; text-align:right; padding-top:10px;">${totalVentaPVP.toFixed(2).replace('.', ',')} €</td>
            </tr>
            </table>
            ${importeDescuentoTarifa > 0.01 ? '<div style="font-size: 0.75rem; color: #666; margin-top: 5px;">* Los ahorros por tarifa ya están aplicados en el precio de cada artículo.</div>' : ''}
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
            alert('Por favor rellena todos los campos obligatorios.');
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
                    alert('Producto creado correctamente');
                    cerrarModal('modalNuevoProducto');
                    // Recargar productos
                    location.reload();
                } else {
                    alert('Error: ' + (data.error ?? ''));
                }
            })
            .catch(err => {
                console.error('Error:', err);
                alert('Error al crear el producto');
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
                descuentoCupon: ultimaVenta.descuentoCupon,
                descuentoTarifaTipo: ultimaVenta.descuentoTarifaTipo,
                descuentoTarifaValor: ultimaVenta.descuentoTarifaValor,
                descuentoTarifaCupon: ultimaVenta.descuentoTarifaCupon,
                descuentoManualTipo: ultimaVenta.descuentoManualTipo,
                descuentoManualValor: ultimaVenta.descuentoManualValor,
                descuentoManualCupon: ultimaVenta.descuentoManualCupon
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
            alert('Por favor, ingresa un importe válido mayor a 0.');
            return false;
        }

        if (importe > efectivoDisponible) {
            alert('No hay suficiente efectivo en la caja. Efectivo disponible: ' + efectivoDisponible.toFixed(2).replace('.', ',') + ' €');
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
        const idTicket = document.getElementById('inputTicketIdDev').value;
        const errorEl = document.getElementById('errorTicketDev');

        if (!idTicket) {
            errorEl.textContent = 'Por favor, introduce un ID de ticket.';
            errorEl.style.display = 'block';
            return;
        }

        errorEl.style.display = 'none';

        // Consultar API para verificar el ticket y obtener productos
        fetch(`api/ventas.php?checkVentaDevolucion=${idTicket}`)
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
                    errorEl.textContent = 'Este ticket no tiene productos disponibles para devolver (ya se han devuelto todos).';
                    errorEl.style.display = 'block';
                    return;
                }

                // Actualizar UI - Paso 2
                document.getElementById('devolucionPaso1').style.display = 'none';
                document.getElementById('devolucionPaso2').style.display = 'block';
                document.getElementById('btnConfirmarMultiDev').style.display = 'block';
                document.getElementById('resumenReembolso').style.display = 'block';
                document.getElementById('devolucionSubtitulo').textContent = 'Selecciona las unidades a devolver';

                document.getElementById('infoTicketId').textContent = 'TICKET #' + ticketActualDevolucion.id;
                document.getElementById('infoTicketFecha').textContent = new Date(ticketActualDevolucion.fecha).toLocaleString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
                document.getElementById('infoTicketTotal').textContent = parseFloat(ticketActualDevolucion.total).toFixed(2).replace('.', ',') + ' €';
                // Also update the original total in the footer
                document.getElementById('totalOriginalDisplay').textContent = parseFloat(ticketActualDevolucion.total).toFixed(2).replace('.', ',') + ' €';

                renderizarTablaDevolucion();
            })
            .catch(err => {
                console.error(err);
                errorEl.textContent = 'Error de conexión al buscar el ticket.';
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

            const tr = document.createElement('tr');
            tr.style.borderBottom = '1px solid #f1f5f9';
            tr.innerHTML = `
            <td style="padding: 12px 15px;">
                    <div class="producto-nombre-dev" style="font-weight: 600;">${linea.producto_nombre}</div>
                    <div class="producto-precio-dev" style="font-size: 0.75rem;">Precio: ${precioMostrar.toFixed(2)} € (IVA incl.)</div>
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
     */
    function recalcularTotalReembolso() {
        let total = 0;
        let hayDevolucion = false;
        const inputs = document.querySelectorAll('.cant-dev-input');

        // Obtener los descuentos de la venta original
        const venta = ticketActualDevolucion;
        let factorDescuento = 1;

        // Calcular el factor de descuento aplicable
        // Usar los nuevos campos si existen, si no usar los antiguos
        const descuentoTarifaCupon = venta.descuentoTarifaCupon || '';
        const descuentoTarifaValor = parseFloat(venta.descuentoTarifaValor) || 0;
        const descuentoManualCupon = venta.descuentoManualCupon || venta.descuentoCupon || '';
        const descuentoManualValor = parseFloat(venta.descuentoManualValor || venta.descuentoValor) || 0;
        const descuentoManualTipo = venta.descuentoManualTipo || venta.descuentoTipo || 'ninguno';

        // Calcular descuento total aplicado a la venta
        let descuentoTotal = 0;

        // Descuento de tarifa (Cliente registrado, Mayorista)
        if (descuentoTarifaCupon && descuentoTarifaCupon !== '' && descuentoTarifaValor > 0) {
            descuentoTotal += descuentoTarifaValor;
        }

        // Descuento manual (porcentaje o fijo)
        if (descuentoManualTipo === 'porcentaje' && descuentoManualValor > 0) {
            descuentoTotal += descuentoManualValor;
        } else if (descuentoManualTipo === 'fijo') {
            // Para descuento fijo, calculamos el porcentaje equivalente
            // Esto es aproximado ya que no sabemos el subtotal original
            // Usamos una estimación basada en el total de la venta
        }

        // Si hay descuento fijo sin tipo porcentaje, calculamos el porcentaje basado en el total
        if (descuentoManualTipo === 'fijo' && descuentoManualValor > 0 && venta.total > 0) {
            const totalSinDescuento = parseFloat(venta.total) / (1 - descuentoTotal / 100);
            descuentoTotal = ((totalSinDescuento - parseFloat(venta.total)) / totalSinDescuento) * 100;
        }

        factorDescuento = 1 - (descuentoTotal / 100);
        if (factorDescuento < 0) factorDescuento = 0;

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

            // Usar precio con IVA (el que se pagó en el momento de la compra)
            // Y aplicar el factor de descuento
            const precioBase = linea.precioConIva ? parseFloat(linea.precioConIva) : parseFloat(linea.precioUnitario) * (1 + (linea.iva || 21) / 100);
            const precioConDescuento = precioBase * factorDescuento;

            if (cant > 0) {
                total += cant * precioConDescuento;
                hayDevolucion = true;
            }
        });

        // Redondear el total hacia abajo a 2 decimales
        total = Math.floor(total * 100) / 100;

        // Si se devuelven TODOS los productos disponibles, mostrar el total original de la venta
        let totalUnidadesDisponibles = 0;
        let totalUnidadesSeleccionadas = 0;
        const inputsCheck = document.querySelectorAll('.cant-dev-input');
        inputsCheck.forEach(input => {
            const index = input.dataset.index;
            const linea = lineasVentaDevolucion[index];
            const comprado = parseInt(linea.cantidad);
            const devuelto = parseInt(linea.cantidad_devuelta);
            const disponible = comprado - devuelto;
            totalUnidadesDisponibles += disponible;
            totalUnidadesSeleccionadas += parseInt(input.value) || 0;
        });

        if (totalUnidadesSeleccionadas === totalUnidadesDisponibles && totalUnidadesDisponibles > 0) {
            total = parseFloat(ticketActualDevolucion.total);
        }

        document.getElementById('totalReembolsoDisplay').textContent = total.toFixed(2).replace('.', ',') + ' €';

        // Validación de efectivo disponible
        const errorEl = document.getElementById('errorEfectivoInsuficiente');
        const btnConfirmar = document.getElementById('btnConfirmarMultiDev');
        const dispEl = document.getElementById('efectivoDisponibleDisplay');

        const totalRedondeado = Math.round(total * 100) / 100;
        const efectivoRedondeado = Math.round(efectivoActualCaja * 100) / 100;

        if (totalRedondeado > efectivoRedondeado) {
            errorEl.style.display = 'block';
            dispEl.textContent = efectivoActualCaja.toFixed(2).replace('.', ',') + ' €';
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
        document.getElementById('devolucionSubtitulo').textContent = 'Introduce el ID del Ticket para comenzar';
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
        const productosDev = [];
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
                const linea = lineasVentaDevolucion[index];
                // Usar precio con IVA (el que se pagó en el momento de la compra)
                const precioConIva = linea.precioConIva ? parseFloat(linea.precioConIva) : parseFloat(linea.precioUnitario) * (1 + (linea.iva || 21) / 100);
                // Redondear a 2 decimales para evitar errores de precisión
                const subtotal = Math.round(cant * precioConIva * 100) / 100;

                productosDev.push({
                    idProducto: linea.idProducto,
                    cantidad: cant,
                    importe: subtotal
                });
                totalReembolso += subtotal;
            }
        });

        if (productosDev.length === 0) return;

        // Si se devuelven TODOS los productos disponibles, usar el total original de la venta
        // Esto asegura que el reembolso sea exacto incluyendo descuentos
        if (totalUnidadesSeleccionadas === totalUnidadesDisponibles && totalUnidadesDisponibles > 0) {
            totalReembolso = parseFloat(ticketActualDevolucion.total);
        }

        // Redondear total final a 2 decimales
        totalReembolso = Math.round(totalReembolso * 100) / 100;

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
            btnCanjear.title = 'Añade productos al carrito para canjear puntos';
            btnCanjear.style.opacity = '0.5';
            btnCanjear.style.cursor = 'not-allowed';
        } else {
            btnCanjear.disabled = false;
            btnCanjear.title = 'Aplicar descuento de puntos';
            btnCanjear.style.opacity = '1';
            btnCanjear.style.cursor = 'pointer';
        }
        
        document.getElementById('modalPuntosCliente').style.display = 'flex';
        document.getElementById('dniPuntosCliente').focus();
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
                mensajeDiv.textContent = 'No se encuentra ningún cliente con ese DNI';
                mensajeDiv.className = 'mensaje-error';
                mensajeDiv.style.display = 'block';
                return;
            }
            
            const cliente = await response.json();
            
            if (cliente && cliente.activo == 1) {
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
                        const puedenUsarse = Math.min(puntosDisponibles, maxPuntosCanjeables);
                        const descuentoMax = Math.floor(puedenUsarse / 1000) * 5;
                        mensajeUsar = `Puedes usar hasta ${puedenUsarse.toLocaleString('es-ES')} puntos = ${descuentoMax.toFixed(2)}€ de descuento (30% máximo del ticket)`;
                    } else {
                        mensajeUsar = `Te faltan ${puntosParaSiguienteDescuento.toLocaleString('es-ES')} puntos para tu próximo descuento`;
                    }
                    
                    puntosUsarMsg.textContent = mensajeUsar;
                    puntosGanadosMsg.textContent = `Con esta compra ganarás ${puntosQueSeGanaran.toLocaleString('es-ES')} puntos (1€ = 10 puntos)`;
                    infoPanel.style.display = 'block';
                } else {
                    infoPanel.style.display = 'none';
                }
            } else {
                mensajeDiv.textContent = 'El cliente está inactivo o no existe';
                mensajeDiv.className = 'mensaje-error';
                mensajeDiv.style.display = 'block';
            }
        } catch (error) {
            console.error('Error al buscar cliente:', error);
            mensajeDiv.textContent = 'Error al buscar el cliente';
            mensajeDiv.className = 'mensaje-error';
            mensajeDiv.style.display = 'block';
        }
    }

    /**
     * Calcula el descuento basado en los puntos a canjear
     */
    function calcularDescuentoPuntos() {
        const puntos = parseInt(document.getElementById('puntosACanjeer').value) || 0;
        const descuento = Math.floor(puntos / 1000) * 5;
        const preview = document.getElementById('descuentoPuntosPreview');
        
        if (puntos > 0 && puntos >= 1000) {
            preview.textContent = `Descuento: ${descuento.toFixed(2)}€ (${puntos.toLocaleString('es-ES')} puntos)`;
        } else if (puntos > 0 && puntos < 1000) {
            preview.textContent = 'Mínimo 1000 puntos para canjear';
            preview.style.color = '#ef4444';
        } else {
            preview.textContent = '';
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
            alert('Error: No se ha especificado el DNI del cliente');
            return;
        }
        
        if (puntosRedondeados < 1000) {
            alert('Mínimo 1000 puntos para canjear');
            return;
        }
        
        // Validar que no canjee más puntos de los que tiene
        if (puntosRedondeados > puntosDisponibles) {
            alert(`No puedes canjear más puntos de los que tienes. Tienes ${puntosDisponibles.toLocaleString('es-ES')} puntos.`);
            return;
        }
        
        // Calcular el total actual del ticket
        const totalTicket = obtenerTotalCalculado();
        const maxDescuento = totalTicket * 0.30; // Máximo 30%
        const maxPuntos = Math.floor(maxDescuento / 5) * 1000;
        
        // Limitar los puntos al máximo permitido
        let puntosFinales = puntosRedondeados;
        if (puntosRedondeados > maxPuntos) {
            alert(`Has superado el máximo permitido (30% del ticket = ${maxDescuento.toFixed(2)}€). Se usarán ${maxPuntos.toLocaleString('es-ES')} puntos.`);
            puntosFinales = maxPuntos;
        }
        
        const descuentoEuros = Math.floor(puntosFinales / 1000) * 5;
        
        // Verificar que el total no sea 0
        if (totalTicket - descuentoEuros <= 0) {
            alert('El descuento no puede hacer el ticket 0. Reduce los puntos a canjear.');
            return;
        }
        
        // Aplicar descuento como descuento manual
        descuento.tipo = 'fijo';
        descuento.valor = descuentoEuros;
        descuento.cupon = 'PUNTOS_' + puntosFinales;
        
        // Calcular puntos que se ganarán
        const puntosGanados = Math.round((totalTicket - descuentoEuros) * 10);
        
        actualizarTicket();
        
        // Cerrar modal
        cerrarModal('modalPuntosCliente');
        document.getElementById('puntosClienteBusqueda').style.display = 'block';
        document.getElementById('puntosClienteInfo').style.display = 'none';
        
        // Guardar los puntos canjeados para procesarlos al confirmar la venta
        puntosCanjeados = {
            dni: dni,
            puntos: puntosFinales
        };
        
        alert(`Descuento de ${descuentoEuros.toFixed(2)}€ aplicado (${puntosFinales.toLocaleString('es-ES')} puntos canjeados)\n` +
              `Con esta compra ganarás ${puntosGanados.toLocaleString('es-ES')} puntos`);
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
                    mensajeDiv.textContent = 'Cliente no encontrado. ¿Deseas continuar sin registrar puntos?';
                    mensajeDiv.className = 'mensaje-error';
                    mensajeDiv.style.display = 'block';
                    // Añadir botón para continuar sin cliente
                    mensajeDiv.innerHTML += '<br><button class="btn-modal-cancelar" onclick="confirmarSinPuntos()" style="margin-top:10px; width:100%;">Continuar sin puntos</button>';
                    return;
                }
                mensajeDiv.textContent = 'No se encuentra ningún cliente con ese DNI';
                mensajeDiv.className = 'mensaje-error';
                mensajeDiv.style.display = 'block';
                // Cambiar la tarifa a Cliente
                const tarifaCliente4 = tarifasPrefijadas.find(t => t.nombre === 'Cliente');
                if (tarifaCliente4) {
                    document.getElementById('tarifaVenta').value = tarifaCliente4.id;
                } else if (tarifasPrefijadas.length > 0) {
                    document.getElementById('tarifaVenta').value = tarifasPrefijadas[0].id;
                }
                return;
            }

            const cliente = await response.json();

            if (cliente && cliente.activo == 1) {
                // Poblamos los campos del cliente para que se guarden con la venta
                document.getElementById('clienteNif').value = cliente.dni;
                document.getElementById('clienteNombre').value = cliente.nombre + ' ' + cliente.apellidos;
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
                mensajeDiv.textContent = `Cliente encontrado: ${cliente.nombre} ${cliente.apellidos}. Tarifa ${nombreTarifa} (${descuentoValor}%) validada.`;
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
                mensajeDiv.textContent = 'El cliente está inactivo';
                mensajeDiv.className = 'mensaje-error';
                mensajeDiv.style.display = 'block';
            } else {
                mensajeDiv.textContent = 'No se encuentra ningún cliente con ese DNI';
                mensajeDiv.className = 'mensaje-error';
                mensajeDiv.style.display = 'block';
                // Cambiar la tarifa a Cliente
                const tarifaCliente5 = tarifasPrefijadas.find(t => t.nombre === 'Cliente');
                if (tarifaCliente5) {
                    document.getElementById('tarifaVenta').value = tarifaCliente5.id;
                } else if (tarifasPrefijadas.length > 0) {
                    document.getElementById('tarifaVenta').value = tarifasPrefijadas[0].id;
                }
            }
        } catch (error) {
            console.error('Error al buscar cliente:', error);
            mensajeDiv.textContent = 'Error al buscar el cliente';
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
            document.querySelector('#modalBuscarClienteRegistrado h3').textContent = 'Cliente Registrado';
            document.querySelector('#modalBuscarClienteRegistrado .modal-subtitulo').textContent = 'Introduce el DNI del cliente';
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
        const fecha_alta = document.getElementById('clienteHabitualFecha').value || new Date(new Date().getTime() - (new Date().getTimezoneOffset() * 60000)).toISOString().slice(0, 16);

        // Validar campos obligatorios
        if (!dni || !nombre || !apellidos) {
            alert('Por favor, complete todos los campos obligatorios (DNI, Nombre, Apellidos)');
            return;
        }

        const btnGuardar = document.getElementById('btnGuardarClienteHabitual');
        btnGuardar.disabled = true;
        btnGuardar.textContent = 'Guardando...';

        try {
            const formData = new FormData();
            formData.append('dni', dni);
            formData.append('nombre', nombre);
            formData.append('apellidos', apellidos);
            formData.append('fecha_alta', fecha_alta);

            const response = await fetch('api/clientes.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.ok) {
                alert('Cliente habitual guardado correctamente');
                cerrarModal('modalClienteHabitual');
            } else {
                alert(data.error || 'Error al guardar el cliente');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error al comunicar con el servidor');
        } finally {
            btnGuardar.disabled = false;
            btnGuardar.textContent = 'Guardar';
        }
    }
</script>

<!-- Modal para crear nuevo producto (permiso: crear_productos) -->
<div class="modal-overlay" id="modalNuevoProducto" style="display:none;">
    <div class="modal-content modal-editarProducto">
        <h3 id="editProductoTitulo">Nuevo Producto</h3>
        <p id="editProductoSubtitulo" class="modal-subtitulo">Introduce los datos del nuevo producto</p>

        <input type="hidden" id="editProductoId">

        <div class="editar-prod-layout">
            <!-- Imagen -->
            <div class="editar-prod-imagen-wrapper">
                <img id="editProductoImagen" src="webroot/img/logoCPU.PNG" alt="" style="cursor: zoom-in;"
                    onclick="abrirImagenGrande(this.src, this.alt)">
                <label class="btn-cambiar-imagen" title="Cambiar imagen">
                    <i class="fas fa-camera"></i> Cambiar imagen
                    <input type="file" id="editProductoImagenInput" accept="image/*" style="display:none;"
                        onchange="previsualizarImagen(event)">
                </label>
            </div>

            <!-- Campos -->
            <div class="editar-prod-campos">
                <div class="editar-prod-fila">
                    <label>Nombre</label>
                    <input type="text" id="nuevoProductoNombre">
                </div>
                <div class="editar-prod-fila">
                    <label>Categoría</label>
                    <select id="nuevoProductoCategoria"
                        style="padding: 8px; border-radius: 4px; border: 1px solid #d1d5db;">
                    </select>
                </div>
                <div class="editar-prod-fila">
                    <label>Precio (€)</label>
                    <input type="number" id="nuevoProductoPrecio" step="0.01" min="0">
                </div>
                <div class="editar-prod-fila">
                    <label>Stock</label>
                    <input type="number" id="nuevoProductoStock" min="0" value="0">
                </div>
                <div class="editar-prod-fila">
                    <label>Tipo de IVA (%)</label>
                    <select id="nuevoProductoIva" style="padding: 8px; border-radius: 4px; border: 1px solid #d1d5db;">
                        <option value="21">21% (General)</option>
                        <option value="10">10% (Reducido)</option>
                        <option value="4">4% (Superreducido)</option>
                        <option value="0">0% (Exento)</option>
                    </select>
                </div>
                <div class="editar-prod-fila">
                    <label>Estado</label>
                    <select id="nuevoProductoEstado">
                        <option value="1">Activo</option>
                        <option value="0">Inactivo</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="editar-prod-botones">
            <button class="btn-modal-cancelar" onclick="cerrarModal('modalNuevoProducto')">Cancelar</button>
            <button class="btn-exito" onclick="guardarNuevoProducto()">
                <i class="fas fa-save"></i> Guardar
            </button>
        </div>
    </div>
</div>