<section id="cajero">
    <!-- Panel izquierdo: Categorías y productos -->
    <div class="cajero-productos">
        <div id="formBuscarProducto" style="align-items: center;">
            <label for="inputBuscarProducto"
                style="font-weight: 600; color: #1a1a2e; white-space: nowrap;">Buscar:</label>
            <input type="text" id="inputBuscarProducto" class="input-buscarProducto"
                placeholder="Escribe el nombre del producto a buscar..." oninput="buscarProductos()" autocomplete="off"
                style="width: 100%;" />
        </div>
        <div class="cajero-categorias">
            <button class="cat-btn activa" data-categoria="" onclick="seleccionarCategoria(this, null)">
                Todos
            </button>
            <?php foreach ($categorias as $cat): ?>
                <button class="cat-btn" data-categoria="<?php echo $cat->getId(); ?>"
                    onclick="seleccionarCategoria(this, <?php echo $cat->getId(); ?>)">
                    <?php echo htmlspecialchars($cat->getNombre()); ?>
                </button>
            <?php endforeach; ?>
        </div>
        <div class="cajero-opciones-extra"
            style="padding: 15px 20px; background: #fff; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: flex-start;">
            <form method="POST" action="index.php" style="margin: 0;">
                <input type="hidden" name="accion" value="previsualizarCaja">
                <button type="submit" class="btn-cancelar"
                    style="font-size: 0.85rem; padding: 10px 15px; background: #fff; border: 2px solid #1a1a2e; color: #1a1a2e; display: flex; align-items: center; gap: 8px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="1" x2="12" y2="23"></line>
                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                    </svg>
                    Hacer Caja
                </button>
            </form>
        </div>

        <div class="productos-grid" id="productosGrid">
            <?php if (empty($productos)): ?>
                <p class="sin-productos">No hay productos disponibles.</p>
            <?php else: ?>
                <?php foreach ($productos as $prod): ?>
                    <div class="producto-card" data-id="<?php echo $prod->getId(); ?>"
                        data-nombre="<?php echo htmlspecialchars($prod->getNombre()); ?>"
                        data-precio="<?php echo $prod->getPrecio(); ?>" data-stock="<?php echo $prod->getStock(); ?>"
                        onclick="agregarAlCarrito(this)" style="<?php if ($prod->getStock() <= 0) {
                            echo 'opacity: 0.5; cursor: not-allowed; scale: 1; transform: translateY(0px);';
                        } ?>">
                        <div class="producto-nombre">
                            <?php echo htmlspecialchars($prod->getNombre()); ?>
                        </div>
                        <div class="producto-imagen">
                            <?php
                            $imgSrc = !empty($prod->getImagen()) ? $prod->getImagen() : 'webroot/img/logo.PNG';
                            echo '<img src="' . htmlspecialchars($imgSrc) . '" alt="' . htmlspecialchars($prod->getNombre()) . '">';
                            ?>
                        </div>
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

    <!-- Panel derecho: Ticket / Carrito -->
    <div class="cajero-ticket">
        <div class="ticket-header">
            <h3>Productos añadidos</h3>
            <span class="ticket-fecha">
                <script>
                    function actualizarFechaHora() {
                        const ahora = new Date();

                        const dia = String(ahora.getDate()).padStart(2, '0');
                        const mes = String(ahora.getMonth() + 1).padStart(2, '0');
                        const anio = ahora.getFullYear();

                        const horas = String(ahora.getHours()).padStart(2, '0');
                        const minutos = String(ahora.getMinutes()).padStart(2, '0');
                        const segundos = String(ahora.getSeconds()).padStart(2, '0');

                        const fechaHora = `${dia}/${mes}/${anio} ${horas}:${minutos}:${segundos}`;

                        document.querySelector('.ticket-fecha').textContent = fechaHora;
                    }

                    actualizarFechaHora();
                    setInterval(actualizarFechaHora, 1000);
                </script>
            </span>
        </div>

        <div class="ticket-lineas" id="ticketLineas">
            <p class="ticket-vacio">Añade productos para realizar la venta</p>
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
            <button class="btn-cobrar" id="btnCobrar" onclick="intentarCobrar()" disabled>
                Cobrar
            </button>
            <button class="btn-descuento" id="btnDescuento" onclick="aplicarDescuento()" disabled>
                Descuento
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
        <input type="hidden" name="dineroEntregado" id="inputDineroEntregadoFinal">
        <input type="hidden" name="cambioDevuelto" id="inputCambioDevueltoFinal">
        <!-- Campos de cliente (Nuevos) -->
        <input type="hidden" name="clienteNif" id="inputClienteNifFinal">
        <input type="hidden" name="clienteNombre" id="inputClienteNombreFinal">
        <input type="hidden" name="clienteDireccion" id="inputClienteDireccionFinal">
        <input type="hidden" name="observaciones" id="inputObservacionesFinal">
    </form>
</section>

<!-- ==================== MODAL: CALCULAR CAMBIO (EFECTIVO) ==================== -->
<div class="modal-overlay" id="modalCambio" style="display:none;">
    <div class="modal-content modal-cambio" style="max-width: 400px;">
        <h3>Pago en Efectivo</h3>
        <p class="modal-subtitulo">Introduce la cantidad entregada por el cliente</p>

        <div class="calculo-cambio-container"
            style="text-align: left; background: #f9fafb; padding: 20px; border-radius: 8px; margin: 15px 0;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <span style="font-size: 1.1rem; color: #4b5563;">Total a pagar:</span>
                <span id="cambioTotalPagar" style="font-size: 1.4rem; font-weight: bold; color: #1f2937;">0,00 €</span>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <label for="inputDineroEntregado" style="font-size: 1.1rem; color: #4b5563;">Entregado:</label>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <input type="number" id="inputDineroEntregado" step="0.01" min="0" placeholder="0.00"
                        style="padding: 10px; font-size: 1.2rem; width: 120px; text-align: right; border: 1px solid #d1d5db; border-radius: 6px; outline: none;"
                        oninput="calcularCambio()" onkeypress="if(event.key === 'Enter') confirmarCambio()">
                    <span style="font-size: 1.2rem; color: #4b5563;">€</span>
                </div>
            </div>

            <div
                style="display: flex; justify-content: space-between; align-items: center; border-top: 2px solid #e5e7eb; padding-top: 15px;">
                <span style="font-size: 1.1rem; font-weight: bold; color: #4b5563;">Cambio a devolver:</span>
                <span id="cambioDevolver" style="font-size: 1.8rem; font-weight: bold; color: #22c55e;">0,00 €</span>
            </div>

            <p id="cambioError"
                style="color: #ef4444; font-size: 0.9rem; margin-top: 15px; text-align: center; display: none;">La
                cantidad entregada es insuficiente para realizar el cobro.</p>
        </div>

        <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
            <button class="btn-modal-cancelar" onclick="cerrarModal('modalCambio')" style="flex: 1;">Cancelar</button>
            <button class="btn-exito" onclick="confirmarCambio()"
                style="flex: 1; margin: 0; display: flex; justify-content: center; align-items: center;">Continuar</button>
        </div>
    </div>
</div>

<!-- ==================== MODAL: TIPO DE DOCUMENTO ==================== -->
<div class="modal-overlay" id="modalTipoDoc" style="display:none;">
    <div class="modal-content modal-tipodoc">
        <h3>¿Cómo desea el comprobante?</h3>
        <p class="modal-subtitulo">Seleccione el tipo de documento para esta venta</p>
        <div class="modal-opciones-doc">
            <button class="opcion-doc" onclick="seleccionarDatosCliente('ticket')">
                <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path>
                    <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>
                </svg>
                <span class="opcion-titulo">Ticket</span>
                <span class="opcion-desc">Comprobante de venta simplificado</span>
            </button>
            <button class="opcion-doc" onclick="seleccionarDatosCliente('factura')">
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

<!-- ==================== MODAL: DATOS DEL CLIENTE ==================== -->
<div class="modal-overlay" id="modalDatosCliente" style="display:none;">
    <div class="modal-content" style="max-width: 500px; text-align: left;">
        <h3 id="tituloDatosCliente" style="margin-bottom: 5px; color: #1a1a2e;">Datos del Cliente</h3>
        <p id="subtituloDatosCliente" style="color: #6b7280; font-size: 0.9rem; margin-bottom: 20px;">Complete los datos
            (Opcional en Ticket)</p>

        <div style="display: grid; gap: 15px;">
            <div>
                <label for="clienteNif"
                    style="display: block; margin-bottom: 5px; font-weight: 500; font-size: 0.9rem;">NIF/CIF <span
                        id="reqNif" style="color: #ef4444; display: none;">*</span></label>
                <input type="text" id="clienteNif"
                    style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;"
                    placeholder="B12345678">
            </div>
            <div>
                <label for="clienteNombre"
                    style="display: block; margin-bottom: 5px; font-weight: 500; font-size: 0.9rem;">Razón Social /
                    Nombre <span id="reqNombre" style="color: #ef4444; display: none;">*</span></label>
                <input type="text" id="clienteNombre"
                    style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;"
                    placeholder="Nombre de la empresa o persona">
            </div>
            <div id="divDireccionCliente" style="display: none;">
                <label for="clienteDireccion"
                    style="display: block; margin-bottom: 5px; font-weight: 500; font-size: 0.9rem;">Domicilio Fiscal
                    <span id="reqDir" style="color: #ef4444; display: none;">*</span></label>
                <input type="text" id="clienteDireccion"
                    style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;"
                    placeholder="Calle, Número, C.P, Ciudad">
            </div>
            <div id="divObservacionesCliente" style="display: none;">
                <label for="clienteObservaciones"
                    style="display: block; margin-bottom: 5px; font-weight: 500; font-size: 0.9rem;">Observaciones
                    (Régimen, Exento, etc.)</label>
                <input type="text" id="clienteObservaciones"
                    style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;"
                    placeholder="Opcional">
            </div>
        </div>
        <p id="errorDatosCliente" style="color: #ef4444; font-size: 0.9rem; margin-top: 15px; display: none;">Por favor,
            rellene todos los campos obligatorios (*).</p>

        <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 25px;">
            <button class="btn-modal-cancelar" onclick="cerrarModal('modalDatosCliente')">Atrás</button>
            <button class="btn-exito" id="btnConfirmarDatos" onclick="validarYConfirmarVenta()"
                style="margin: 0;">Finalizar Venta</button>
        </div>
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
            fecha: '<?php echo $_SESSION['ultimaVentaFecha']; ?>',
            entregado: '<?php echo number_format($_SESSION['ultimaVentaEntregado'] ?? $_SESSION['ultimaVentaTotal'], 2, ',', '.'); ?>',
            cambio: '<?php echo number_format($_SESSION['ultimaVentaCambio'] ?? 0, 2, ',', '.'); ?>',
            clienteNif: '<?php echo addslashes($_SESSION['ultimaVentaClienteNif'] ?? ''); ?>',
            clienteNombre: '<?php echo addslashes($_SESSION['ultimaVentaClienteNombre'] ?? ''); ?>',
            clienteDir: '<?php echo addslashes($_SESSION['ultimaVentaClienteDir'] ?? ''); ?>',
            clienteObs: '<?php echo addslashes($_SESSION['ultimaVentaClienteObs'] ?? ''); ?>'
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
    unset($_SESSION['ultimaVentaEntregado']);
    unset($_SESSION['ultimaVentaCambio']);
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

<!-- ==================== MODAL: PREVISUALIZACIÓN DE CIERRE DE CAJA ==================== -->
<?php if (isset($_SESSION['cajaPrevisualizacion']) && $_SESSION['cajaPrevisualizacion'] && isset($_SESSION['resumenCaja'])): ?>
    <div class="modal-overlay" id="cajaPrevisualizacion">
        <div class="modal-content modal-exito" style="max-width: 450px;">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#2563eb"
                stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 15px;">
                <rect x="2" y="6" width="20" height="12" rx="2"></rect>
                <path d="M12 12h.01"></path>
                <path d="M17 12h.01"></path>
                <path d="M7 12h.01"></path>
            </svg>
            <h3 style="color: #1a1a2e; font-size: 1.4rem; margin-bottom: 20px;">Cierre de Caja</h3>

            <div id="cajaResumenImprimible"
                style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; text-align: left; margin-bottom: 20px;">
                <!-- Header visible solo al imprimir -->
                <div class="solo-impresion" style="text-align: center; margin-bottom: 15px;">
                    <h2>TPV Bazar</h2>
                    <p>Cierre de Caja - <?php echo date('d/m/Y H:i'); ?></p>
                </div>

                <h4
                    style="margin: 0 0 15px 0; font-size: 1.1rem; border-bottom: 1px solid #e5e7eb; padding-bottom: 5px; color: #374151;">
                    Resumen de Ventas de Hoy</h4>

                <div style="display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 0.95rem;">
                    <span><strong style="color: #4b5563;">Efectivo:</strong>
                        (<?php echo $_SESSION['resumenCaja']['efectivo']['cantidad']; ?> tickets)</span>
                    <span
                        style="font-weight: 600;"><?php echo number_format($_SESSION['resumenCaja']['efectivo']['total'], 2, ',', '.'); ?>
                        €</span>
                </div>

                <div style="display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 0.95rem;">
                    <span><strong style="color: #4b5563;">Tarjeta:</strong>
                        (<?php echo $_SESSION['resumenCaja']['tarjeta']['cantidad']; ?> tickets)</span>
                    <span
                        style="font-weight: 600;"><?php echo number_format($_SESSION['resumenCaja']['tarjeta']['total'], 2, ',', '.'); ?>
                        €</span>
                </div>

                <div style="display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 0.95rem;">
                    <span><strong style="color: #4b5563;">Bizum:</strong>
                        (<?php echo $_SESSION['resumenCaja']['bizum']['cantidad']; ?> tickets)</span>
                    <span
                        style="font-weight: 600;"><?php echo number_format($_SESSION['resumenCaja']['bizum']['total'], 2, ',', '.'); ?>
                        €</span>
                </div>

                <div
                    style="display: flex; justify-content: space-between; border-top: 2px solid #1a1a2e; padding-top: 15px; font-size: 1.2rem;">
                    <strong style="color: #1a1a2e;">TOTAL GENERADO:</strong>
                    <strong
                        style="color: #059669;"><?php echo number_format($_SESSION['resumenCaja']['totalGeneral'], 2, ',', '.'); ?>
                        €</strong>
                </div>

                <!-- Footer visible solo al imprimir -->
                <div class="solo-impresion"
                    style="text-align: center; margin-top: 20px; border-top: 1px dashed #ccc; padding-top: 10px; font-size: 0.9rem; color: #666;">
                    <p>Firma y sello:</p>
                    <br><br><br>
                </div>
            </div>

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
    unset($_SESSION['cajaPrevisualizacion']);
        // No borramos resumenCaja aquí porque lo utilizaremos si confirman e imprimen.
    ?>
<?php endif; ?>

<!-- ==================== MODAL: COMPLETADO (Imprimir y resetear) ==================== -->
<?php if (isset($_SESSION['cajaConfirmacion']) && $_SESSION['cajaConfirmacion'] && isset($_SESSION['resumenCaja'])): ?>
    <div class="modal-overlay" id="cajaConfirmacion">
        <div class="modal-content modal-exito" style="max-width: 450px;">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#059669"
                stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icono-exito">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                <polyline points="22 4 12 14.01 9 11.01"></polyline>
            </svg>
            <h3 style="color: #1a1a2e; font-size: 1.4rem; margin-bottom: 20px;">Caja Cerrada Correctamente</h3>
            <p style="color: #6b7280; font-size: 0.95rem; margin-bottom: 20px;">El recuento de ventas ha vuelto a 0 para el
                contador del día de mañana.</p>

            <div id="cajaOcultaImprimible" style="display: none;">
                <!-- Usamos este bloque inyectado para imprimir -->
                <div style="text-align: center; margin-bottom: 15px; font-family: 'Inter', sans-serif;">
                    <h2 style="margin:0;">TPV Bazar</h2>
                    <p style="margin:5px 0 15px 0;">Cierre de Caja - <?php echo date('d/m/Y H:i'); ?></p>
                </div>
                <div
                    style="border-top: 1px solid #000; padding-top: 10px; padding-bottom: 5px; font-family: 'Inter', sans-serif;">
                    <p style="margin: 5px 0; display:flex; justify-content:space-between;"><span>Efectivo
                            (<?php echo $_SESSION['resumenCaja']['efectivo']['cantidad']; ?>):</span>
                        <span><?php echo number_format($_SESSION['resumenCaja']['efectivo']['total'], 2, ',', '.'); ?>
                            €</span>
                    </p>
                    <p style="margin: 5px 0; display:flex; justify-content:space-between;"><span>Tarjeta
                            (<?php echo $_SESSION['resumenCaja']['tarjeta']['cantidad']; ?>):</span>
                        <span><?php echo number_format($_SESSION['resumenCaja']['tarjeta']['total'], 2, ',', '.'); ?>
                            €</span>
                    </p>
                    <p style="margin: 5px 0; display:flex; justify-content:space-between;"><span>Bizum
                            (<?php echo $_SESSION['resumenCaja']['bizum']['cantidad']; ?>):</span>
                        <span><?php echo number_format($_SESSION['resumenCaja']['bizum']['total'], 2, ',', '.'); ?> €</span>
                    </p>
                </div>
                <div
                    style="border-top: 2px solid #000; padding-top: 10px; margin-top: 10px; font-weight: bold; display:flex; justify-content:space-between; font-family: 'Inter', sans-serif;">
                    <span>TOTAL:</span>
                    <span><?php echo number_format($_SESSION['resumenCaja']['totalGeneral'], 2, ',', '.'); ?> €</span>
                </div>
                <div style="text-align: center; margin-top: 30px; font-size: 0.8rem; font-family: 'Inter', sans-serif;">
                    <p>Firma / Sello</p>
                </div>
            </div>

            <div style="display: flex; gap: 10px; justify-content: center; margin-top: 20px;">
                <button class="btn-cerrar-exito" onclick="imprimirCierreCaja()">Imprimir Resumen</button>
                <button class="btn-modal-cancelar"
                    onclick="document.getElementById('cajaConfirmacion').style.display='none';">Aceptar</button>
            </div>
        </div>
    </div>
    <script>
        function imprimirCierreCaja() {
            const contenido = document.getElementById('cajaOcultaImprimible').innerHTML;
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
        setTimeout(() => {
            ventana.print();
            ventana.close();
            document.getElementById('cajaConfirmacion').style.display = 'none';
        }, 500);
    }
</script>
<?php
        unset($_SESSION['cajaConfirmacion']);
        unset($_SESSION['resumenCaja']);
?>
<?php endif; ?>

<script src="webroot/js/cajero.js"></script>
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

    function cambiarCantidad(indice, nuevaCantidad) {
        const item = carrito[indice];
        nuevaCantidad = parseInt(nuevaCantidad) || 1;

        if (nuevaCantidad < 1) nuevaCantidad = 1;
        if (nuevaCantidad > item.stockMax) nuevaCantidad = item.stockMax;

        item.cantidad = nuevaCantidad;
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
        totalEl.textContent = total.toFixed(2).replace('.', ',') + ' €';
        btnCobrar.disabled = false;
    }

    // ======================== PROCESO DE COBRO Y CAMBIO ========================
    function intentarCobrar() {
        if (carrito.length === 0) return;
        const metodoPago = document.getElementById('metodoPago').value;

        if (metodoPago === 'efectivo') {
            mostrarModalCambio();
        } else {
            mostrarModalTipoDocumento();
        }
    }

    function mostrarModalCambio() {
        const total = carrito.reduce((sum, item) => sum + (item.precio * item.cantidad), 0);
        document.getElementById('cambioTotalPagar').textContent = total.toFixed(2).replace('.', ',') + ' €';

        const inputEntregado = document.getElementById('inputDineroEntregado');
        inputEntregado.value = '';
        document.getElementById('cambioDevolver').textContent = '0,00 €';
        document.getElementById('cambioDevolver').style.color = '#22c55e';
        document.getElementById('cambioError').style.display = 'none';

        document.getElementById('modalCambio').style.display = 'flex';
        // Foco automático en el input
        setTimeout(() => inputEntregado.focus(), 100);
    }

    function calcularCambio() {
        const total = carrito.reduce((sum, item) => sum + (item.precio * item.cantidad), 0);
        const entregado = parseFloat(document.getElementById('inputDineroEntregado').value) || 0;
        const devolucion = entregado - total;

        const spanDevolver = document.getElementById('cambioDevolver');
        const errorMsg = document.getElementById('cambioError');

        if (devolucion < 0 && entregado > 0) {
            spanDevolver.textContent = '0,00 €';
            spanDevolver.style.color = '#333';
            errorMsg.style.display = 'block';
        } else {
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

    function confirmarCambio() {
        const total = carrito.reduce((sum, item) => sum + (item.precio * item.cantidad), 0);
        const entregado = parseFloat(document.getElementById('inputDineroEntregado').value) || 0;

        if (entregado < total) {
            document.getElementById('cambioError').style.display = 'block';
            return;
        }

        cerrarModal('modalCambio');
        mostrarModalTipoDocumento();
    }

    // ======================== MODAL TIPO DOCUMENTO / CLIENTE ========================
    function mostrarModalTipoDocumento() {
        if (carrito.length === 0) return;
        document.getElementById('modalTipoDoc').style.display = 'flex';
    }

    let tipoDocumentoActual = 'ticket';

    function seleccionarDatosCliente(tipo) {
        tipoDocumentoActual = tipo;
        cerrarModal('modalTipoDoc');

        // Limpiamos errores
        document.getElementById('errorDatosCliente').style.display = 'none';

        // Configuramos la vista según sea Ticket o Factura
        const divDir = document.getElementById('divDireccionCliente');
        const divObs = document.getElementById('divObservacionesCliente');
        const subTitulo = document.getElementById('subtituloDatosCliente');

        const reqNif = document.getElementById('reqNif');
        const reqNombre = document.getElementById('reqNombre');
        const reqDir = document.getElementById('reqDir');

        if (tipo === 'factura') {
            subTitulo.textContent = 'Complete los datos (Obligatorios para Factura)';
            divDir.style.display = 'block';
            divObs.style.display = 'block';
            reqNif.style.display = 'inline';
            reqNombre.style.display = 'inline';
            reqDir.style.display = 'inline';
        } else {
            subTitulo.textContent = 'Complete los datos (Opcional en Ticket)';
            divDir.style.display = 'none';
            divObs.style.display = 'none';
            reqNif.style.display = 'none';
            reqNombre.style.display = 'none';
            reqDir.style.display = 'none';
        }

        document.getElementById('modalDatosCliente').style.display = 'flex';
    }

    function validarYConfirmarVenta() {
        const nif = document.getElementById('clienteNif').value.trim();
        const nombre = document.getElementById('clienteNombre').value.trim();
        const direccion = document.getElementById('clienteDireccion').value.trim();
        const observaciones = document.getElementById('clienteObservaciones').value.trim();

        if (tipoDocumentoActual === 'factura') {
            if (!nif || !nombre || !direccion) {
                document.getElementById('errorDatosCliente').style.display = 'block';
                return;
            }
        }

        cerrarModal('modalDatosCliente');
        confirmarVenta(tipoDocumentoActual, nif, nombre, direccion, observaciones);
    }

    function confirmarVenta(tipoDocumento, nif, nombre, direccion, observaciones) {
        const total = carrito.reduce((sum, item) => sum + (item.precio * item.cantidad), 0);
        const metodoPago = document.getElementById('metodoPago').value;
        let entregado = total;
        let cambio = 0;

        if (metodoPago === 'efectivo') {
            const inputVal = parseFloat(document.getElementById('inputDineroEntregado').value);
            if (!isNaN(inputVal) && inputVal >= total) {
                entregado = inputVal;
                cambio = inputVal - total;
            }
        }

        document.getElementById('inputCarrito').value = JSON.stringify(carrito);
        document.getElementById('inputMetodoPago').value = metodoPago;
        document.getElementById('inputTipoDocumento').value = tipoDocumento;
        document.getElementById('inputDineroEntregadoFinal').value = entregado.toFixed(2);
        document.getElementById('inputCambioDevueltoFinal').value = cambio.toFixed(2);

        // Asignar los valores del cliente
        document.getElementById('inputClienteNifFinal').value = nif;
        document.getElementById('inputClienteNombreFinal').value = nombre;
        document.getElementById('inputClienteDireccionFinal').value = direccion;
        document.getElementById('inputObservacionesFinal').value = observaciones;

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

        const isFactura = (ultimaVenta.tipo === 'factura');
        const tipoTitulo = isFactura ? 'FACTURA' : 'TICKET DE VENTA (FACTURA SIMPLIFICADA)';

        // Emisor fijo
        const emisorHtml = `
            <strong>TPV Bazar — Productos Informáticos</strong><br>
            NIF: B12345678<br>
            C/ Falsa 123, 28000 Madrid<br>
        `;

        // Receptor
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

        let lineasHtml = '';
        let sumaTotalesNumeric = 0;

        ultimaVenta.carrito.forEach(item => {
            const subtotalNumeric = item.precio * item.cantidad;
            sumaTotalesNumeric += subtotalNumeric;

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

        // Suposición: Todo lleva IVA incluído 21%
        const totalVenta = sumaTotalesNumeric;
        const baseImponible = totalVenta / 1.21;
        const cuotaIva = totalVenta - baseImponible;

        let totalesHtml = `
            <table class="tabla-totales" style="width:100%; font-size: 0.9rem; margin-top:10px;">
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

        let obsHtml = '';
        if (ultimaVenta.clienteObs) {
            obsHtml = `<div style="margin-top: 15px; font-size: 0.8rem;"><strong>Observaciones:</strong> ${ultimaVenta.clienteObs}</div>`;
        }

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
                metodoPago: ultimaVenta.metodoPago,
                entregado: ultimaVenta.entregado,
                cambio: ultimaVenta.cambio,
                clienteNif: ultimaVenta.clienteNif,
                clienteNombre: ultimaVenta.clienteNombre,
                clienteDir: ultimaVenta.clienteDir,
                clienteObs: ultimaVenta.clienteObs
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