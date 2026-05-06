<!-- 
    Dependencias de JavaScript para la Administración
    ------------------------------------------------
    Se cargan todos los módulos necesarios para la gestión del TPV.
    admin-main.js actúa como el orquestador principal.
-->
<script src="webroot/js/admin-backups.js"></script>
<script src="webroot/js/admin-caja.js?v=4"></script>
<script src="webroot/js/admin-clientes.js"></script>
<script src="webroot/js/admin-configuracion.js"></script>
<script src="webroot/js/admin-informes.js"></script>
<script src="webroot/js/admin-logs.js"></script>
<script src="webroot/js/admin-pagination.js"></script>
<script src="webroot/js/admin-productos.js"></script>
<script src="webroot/js/admin-state.js"></script>
<script src="webroot/js/admin-tarifas.js"></script>
<script src="webroot/js/admin-usuarios.js"></script>
<script src="webroot/js/admin-utils.js"></script>
<script src="webroot/js/admin-verifactu.js?v=1"></script>
<script src="webroot/js/lib/qrcode.min.js"></script>
<script src="webroot/js/shared-impresion.js"></script>
<script src="webroot/js/admin-ventas.js?v=4"></script>
<script src="webroot/js/admin-main.js"></script>

<!-- Librerías Externas: Gráficos (Chart.js), PDF (jsPDF), Alertas (SweetAlert2) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    // Configuración global del TPV para impresión y fiscalidad
    window.TPV_CONFIG = {
        nif: '<?php require_once "core/Verifactu.php";
        echo addslashes(Verifactu::getConfig("TPV_NIF", "")); ?>',
        nombre: '<?php echo addslashes(Verifactu::getConfig("TPV_RAZON_SOCIAL", "")); ?>',
        direccion: '<?php echo addslashes(Verifactu::getConfig("TPV_DIRECCION", "")); ?>',
        qrBaseUrl: '<?php echo addslashes(Verifactu::getQRBaseUrl()); ?>'
    };

    // Traducciones para tickets y facturas (compartido)
    window.IDIOMAS_TICKET = {
        'es': <?php echo json_encode(include __DIR__ . '/../lang/es.php'); ?>,
        'en': <?php echo json_encode(include __DIR__ . '/../lang/en.php'); ?>,
        'fr': <?php echo json_encode(include __DIR__ . '/../lang/fr.php'); ?>,
        'de': <?php echo json_encode(include __DIR__ . '/../lang/de.php'); ?>,
        'ru': <?php echo json_encode(include __DIR__ . '/../lang/ru.php'); ?>
    };
</script>
<section id="cajero">
    <!-- Panel izquierdo: Navegación de Admin -->
    <div class="cajero-productos admin-sidebar" style="max-width: 260px; border-right: 1px solid #e5e7eb;">
        <div id="formBuscarProducto" class="admin-sidebar-header" style="padding: 20px;">
            <h2 class="admin-view-title">Administración</h2>
        </div>
        <div class="cajero-categorias admin-nav-buttons" style="flex-direction: column; gap: 10px; padding: 20px;">
            <button class="cat-btn activa" data-seccion="dashboard" style="width: 100%; text-align: left;">
                <i class="fas fa-chart-line" style="margin-right: 10px;"></i> Dashboard
            </button>
            <button class="cat-btn" data-seccion="caja-sesiones" style="width: 100%; text-align: left;">
                <i class="fas fa-cash-register" style="margin-right: 10px;"></i> Sesiones de Caja
            </button>
            <button class="cat-btn" data-seccion="productos" style="width: 100%; text-align: left;">
                <i class="fas fa-box" style="margin-right: 10px;"></i> Productos
            </button>
            <button class="cat-btn" data-seccion="categorias" style="width: 100%; text-align: left;">
                <i class="fas fa-tags" style="margin-right: 10px;"></i> Categorías
            </button>
            <button class="cat-btn" data-seccion="usuarios" style="width: 100%; text-align: left;">
                <i class="fas fa-users" style="margin-right: 10px;"></i> Usuarios
            </button>
            <button class="cat-btn" data-seccion="ventas" style="width: 100%; text-align: left;">
                <i class="fas fa-file-invoice-dollar" style="margin-right: 10px;"></i> Ventas
            </button>
            <button class="cat-btn" data-seccion="retiros" style="width: 100%; text-align: left;">
                <i class="fas fa-money-bill-wave" style="margin-right: 10px;"></i> Retiros de Caja
            </button>
            <button class="cat-btn" data-seccion="devoluciones" style="width: 100%; text-align: left;">
                <i class="fas fa-undo" style="margin-right: 10px;"></i> Devoluciones
            </button>
            <button class="cat-btn" data-seccion="proveedores" style="width: 100%; text-align: left;">
                <i class="fas fa-truck" style="margin-right: 10px;"></i> Proveedores
            </button>
            <button class="cat-btn" data-seccion="clientes" style="width: 100%; text-align: left;">
                <i class="fas fa-user-friends" style="margin-right: 10px;"></i> Clientes
            </button>
            <button class="cat-btn" id="btnTarifas" style="width: 100%; text-align: left;">
                <i class="fas fa-tags" style="margin-right: 10px;"></i> Tarifas Generales ▾
            </button>
            <div id="submenuTarifas" style="display: none; padding-left: 20px;">
                <button class="cat-btn submenu-btn" data-seccion="tarifa-iva"
                    style="width: 100%; text-align: left; font-size: 13px;">
                    <i class="fas fa-percent" style="margin-right: 10px;"></i> Cambiar IVA
                </button>
                <button class="cat-btn submenu-btn" data-seccion="tarifa-ajuste"
                    style="width: 100%; text-align: left; font-size: 13px;">
                    <i class="fas fa-sliders-h" style="margin-right: 10px;"></i> Ajuste de Precios
                </button>
                <button class="cat-btn submenu-btn" data-seccion="tarifa-prefijadas"
                    style="width: 100%; text-align: left; font-size: 13px;">
                    <i class="fas fa-list-ul"></i> Tarifas Prefijadas
                </button>
            </div>
            <button class="cat-btn" data-seccion="backups" style="width: 100%; text-align: left;">
                <i class="fas fa-database" style="margin-right: 10px;"></i> Copia de Seguridad
            </button>
            <button class="cat-btn" id="btnInformes" style="width: 100%; text-align: left;">
                <i class="fas fa-chart-bar" style="margin-right: 10px;"></i> Informes ▾
            </button>
            <div id="submenuInformes" style="display: none; padding-left: 20px;">
                <button class="cat-btn submenu-btn" data-seccion="informe-diario"
                    style="width: 100%; text-align: left; font-size: 13px;">
                    <i class="fas fa-calendar-day" style="margin-right: 10px;"></i> Informe Diario
                </button>
                <button class="cat-btn submenu-btn" data-seccion="informe-semanal"
                    style="width: 100%; text-align: left; font-size: 13px;">
                    <i class="fas fa-calendar-week" style="margin-right: 10px;"></i> Informe Semanal
                </button>
                <button class="cat-btn submenu-btn" data-seccion="informe-mensual"
                    style="width: 100%; text-align: left; font-size: 13px;">
                    <i class="fas fa-calendar-alt" style="margin-right: 10px;"></i> Informe Mensual
                </button>
                <button class="cat-btn submenu-btn" data-seccion="informe-anual"
                    style="width: 100%; text-align: left; font-size: 13px;">
                    <i class="fas fa-calendar" style="margin-right: 10px;"></i> Informe Anual
                </button>
            </div>

            <button class="cat-btn" data-seccion="logs" style="width: 100%; text-align: left;">
                <i class="fas fa-history" style="margin-right: 10px;"></i> Logs
            </button>
            <button class="cat-btn" data-seccion="historial-precios" style="width: 100%; text-align: left;">
                <i class="fas fa-chart-area" style="margin-right: 10px;"></i> Historial de Precios
            </button>
            <button class="cat-btn" data-seccion="envios-aeat"
                style="width: 100%; text-align: left; position: relative;">
                <i class="fas fa-satellite-dish" style="margin-right: 10px;"></i> Envíos AEAT
                <span id="badgePendientesAeat"
                    style="display:none; position:absolute; right:10px; top:50%; transform:translateY(-50%); background:#dc2626; color:#fff; font-size:11px; font-weight:700; padding:2px 7px; border-radius:10px; min-width:18px; text-align:center;">0</span>
            </button>
            <button class="cat-btn" id="btnConfig" style="width: 100%; text-align: left;">
                <i class="fas fa-cog" style="margin-right: 10px;"></i> Configuración ▾
            </button>
            <div id="submenuConfig" style="display: none; padding-left: 20px;">
                <button class="cat-btn submenu-btn" data-seccion="config-tema"
                    style="width: 100%; text-align: left; font-size: 13px;">
                    <i class="fas fa-palette" style="margin-right: 10px;"></i> Tema
                </button>
                <button class="cat-btn submenu-btn" data-seccion="config-acciones"
                    style="width: 100%; text-align: left; font-size: 13px;">
                    <i class="fas fa-cogs" style="margin-right: 10px;"></i> Acciones
                </button>
                <button class="cat-btn submenu-btn" data-seccion="config-fiscal"
                    style="width: 100%; text-align: left; font-size: 13px;">
                    <i class="fas fa-file-invoice" style="margin-right: 10px;"></i> Fiscal / Verifactu
                </button>
            </div>
        </div>
    </div>

    <!-- 
        Panel Derecho: Contenido Principal y Dashboard
        ----------------------------------------------
        Aquí se visualizan las métricas en tiempo real (Dashboard) y se cargan 
        dinámicamente las diferentes secciones de gestión vía AJAX.
    -->
    <div class="admin-dashboard">
        <div class="admin-header">
            <div style="display:flex;align-items:center;gap:15px">
                <h2>Panel de Control</h2>
                <!-- Indicador Global de Tareas -->
                <div id="adminTaskIndicator" class="task-indicator" onclick="abrirCentroTareas()"
                    title="Ver tareas en curso"
                    style="display:none;cursor:pointer;background:var(--bg-secondary);padding:5px 12px;border-radius:20px;border:1px solid var(--border-main);align-items:center;gap:8px;font-size:0.85rem">
                    <i class="fas fa-cog fa-spin" style="color:var(--accent-main)"></i>
                    <span id="taskCountText">1 tarea activa</span>
                </div>
            </div>
            <div class="indicador-efectivo" id="adminIndicadorCaja"
                style="<?php echo !$sesionCaja ? 'background: #fee2e2; border-color: #fecaca;' : ''; ?>">
                <span class="label">Estado del Sistema:</span>
                <span class="amount" id="adminEstadoSistema" style="color: <?php echo $sesionCaja ? '#059669' : '#dc2626'; ?>;">
                    <?php echo $sesionCaja ? 'Online' : 'Offline (Caja Cerrada)'; ?>
                </span>
                <div class="separador"></div>
                <span class="label" id="adminEfectivoLabel"><?php echo $sesionCaja ? 'Efectivo en Caja:' : 'Fondo Siguiente Turno:'; ?></span>
                <span class="amount" id="adminEfectivoValor">
                    <?php echo number_format($stats['efectivoCaja'], 2, ',', '.'); ?> €
                </span>
            </div>
        </div>

        <div class="admin-stats-grid">
            <div class="admin-stat-card">
                <span class="admin-stat-label"><?php echo $tituloVentas; ?></span>
                <span class="admin-stat-value"><?php echo number_format($stats['gananciasHoy'], 2, ',', '.'); ?>
                    €</span>
            </div>
            <div class="admin-stat-card">
                <span class="admin-stat-label"><?php echo $tituloPedidos; ?></span>
                <span class="admin-stat-value"><?php echo $stats['pedidosHoy']; ?></span>
            </div>
            <div class="admin-stat-card">
                <span class="admin-stat-label">Total Productos Activos</span>
                <span class="admin-stat-value"><?php echo $stats['productos']; ?></span>
            </div>
            <div class="admin-stat-card">
                <span class="admin-stat-label">Alertas Stock</span>
                <span class="admin-stat-value" style="color: #dc2626;"><?php echo $stats['alertasStock']; ?></span>
            </div>
            <div class="admin-stat-card">
                <span class="admin-stat-label"><?php echo $tituloRetiros; ?></span>
                <span class="admin-stat-value"
                    style="color: #ea580c;">-<?php echo number_format($stats['retirosHoy'] ?? 0, 2, ',', '.'); ?>
                    €</span>
            </div>
            <div class="admin-stat-card">
                <span class="admin-stat-label"><?php echo $tituloDevoluciones; ?></span>
                <span class="admin-stat-value"
                    style="color: #dc2626;">-<?php echo number_format($stats['devolucionesHoy'] ?? 0, 2, ',', '.'); ?>
                    €</span>
            </div>
            <div class="admin-stat-card">
                <span class="admin-stat-label">Horas trabajadas (Semana)</span>
                <span class="admin-stat-value"
                    style="color: #059669;"><?php echo number_format($stats['horasTrabajadasSemana'] ?? 0, 1, ',', '.'); ?>
                    h</span>
            </div>
        </div>

        <div class="admin-content-panel">
            <h3 id="adminTitulo" class="admin-view-subtitle">
                Resumen de Actividad
            </h3>
            <div id="adminContenido" class="contenido-admin">
                <i class="fas fa-info-circle" style="font-size: 2rem; margin-bottom: 15px; display: block;"></i>
                <p>Aquí se mostrarán los datos detallados de la gestión...</p>
            </div>
        </div>
    </div>
</section>

<!-- 
    SECCIÓN DE MODALES
    ------------------
    Los modales están ocultos por defecto y se activan mediante Javascript para 
    operaciones CRUD (Crear, Leer, Actualizar, Borrar) y visualización de detalles.
-->

<!-- ##-----------------------------------MODAL VER CATEGORÍA-----------------------------------## -->

<div class="modal-overlay" id="modalVerCategoria" style="display:none;">
    <div class="modal-content modal-premium" style="max-width: 550px; padding: 0; overflow: hidden; width: 90%;">
        <!-- Header Premium -->
        <div class="modal-header-premium" style="background: linear-gradient(135deg, #3b82f6, #1d4ed8); padding: 20px 25px; text-align: left; position: relative;">
            <h3 style="margin: 0; color: #fff; font-size: 1.3rem;">Detalle de Categoría</h3>
            <p class="modal-subtitulo" style="margin: 5px 0 0 0; color: rgba(255,255,255,0.8); font-size: 0.85rem;">Información y productos asociados</p>
            <button class="modal-close-btn" onclick="cerrarModal('modalVerCategoria')" style="position: absolute; top: 20px; right: 20px; background: rgba(255,255,255,0.2); border: none; color: white; width: 30px; height: 30px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: background 0.2s;">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div style="padding: 25px;">
            <!-- Info básica -->
            <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 25px;">
                <div class="ver-prod-item-premium">
                    <label style="display: block; font-size: 0.75rem; color: #6b7280; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">ID de Categoría</label>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-hashtag" style="color: #64748b; width: 16px;"></i>
                        <span id="verCategoriaId" style="font-size: 0.95rem; color: #4b5563;"></span>
                    </div>
                </div>
                <div class="ver-prod-item-premium">
                    <label style="display: block; font-size: 0.75rem; color: #6b7280; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">Nombre</label>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-folder-open" style="color: #3b82f6; width: 16px;"></i>
                        <span id="verCategoriaNombre" style="font-size: 1.1rem; font-weight: 700; color: #1f2937;"></span>
                    </div>
                </div>
                <div class="ver-prod-item-premium">
                    <label style="display: block; font-size: 0.75rem; color: #6b7280; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">Descripción</label>
                    <div style="display: flex; align-items: flex-start; gap: 8px;">
                        <i class="fas fa-align-left" style="color: #8b5cf6; width: 16px; margin-top: 3px;"></i>
                        <span id="verCategoriaDescripcion" style="font-size: 0.95rem; color: #4b5563;"></span>
                    </div>
                </div>
            </div>

            <!-- Carrusel de productos debajo -->
            <div class="cat-prod-container" style="background: #f8fafc; border-radius: 12px; border: 1px solid #e2e8f0; padding: 20px;">
                <div class="cat-prod-title" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <span style="font-weight: 600; color: #334155; font-size: 0.9rem; text-transform: uppercase;">Productos vinculados</span>
                    <span id="verCategoriaCantProdBadge" class="admin-badge" style="background: #e0e7ff; color: #3730a3; padding: 4px 10px; font-size: 0.8rem;">0</span>
                </div>

                <div class="cat-carousel-wrapper">
                    <div style="display: flex; gap: 5px;">
                        <button id="firstCatProd" class="cat-carousel-btn small" title="Primero" onclick="cambiarProductoCarrusel('first')">
                            <i class="fas fa-angle-double-left"></i>
                        </button>
                        <button id="prevCatProd" class="cat-carousel-btn" title="Anterior" onclick="cambiarProductoCarrusel(-1)">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                    </div>

                    <div id="verCategoriaListaProductos" class="cat-prod-card-wrapper">
                        <!-- Se carga un solo producto aquí -->
                        <div class="cat-prod-empty">Cargando...</div>
                    </div>

                    <div style="display: flex; gap: 5px;">
                        <button id="nextCatProd" class="cat-carousel-btn" title="Siguiente" onclick="cambiarProductoCarrusel(1)">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                        <button id="lastCatProd" class="cat-carousel-btn small" title="Último" onclick="cambiarProductoCarrusel('last')">
                            <i class="fas fa-angle-double-right"></i>
                        </button>
                    </div>
                </div>

                <div id="catCarouselDots" class="cat-carousel-info" style="justify-content: center; margin-top: 15px; font-size: 0.85rem; color: #64748b;">
                    <span>Producto</span>
                    <input type="number" id="catCarouselInput" class="cat-carousel-input" min="1" onchange="saltarAProductoCarrusel(this.value)">
                    <span>de <span id="catCarouselTotal">0</span></span>
                </div>
            </div>

            <div style="margin-top: 20px; display: flex; justify-content: flex-end; gap: 10px;">
                <button class="btn-modal-cancelar" onclick="cerrarModal('modalVerCategoria')" style="margin: 0; padding: 10px 25px; border-radius: 8px; font-weight: 600;">
                    Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ##-----------------------------------MODAL VER PRODUCTO-----------------------------------## -->

<div class="modal-overlay" id="modalVerProducto" style="display:none;">
    <div class="modal-content modal-premium" style="max-width: 500px; padding: 0; overflow: hidden;">
        <!-- Header Premium -->
        <div class="modal-header-premium" style="background: linear-gradient(135deg, #3b82f6, #1d4ed8); padding: 20px 25px; text-align: left; position: relative;">
            <h3 style="margin: 0; color: #fff; font-size: 1.3rem;">Detalle del Producto</h3>
            <p class="modal-subtitulo" style="margin: 5px 0 0 0; color: rgba(255,255,255,0.8); font-size: 0.85rem;">Ficha técnica e información de inventario</p>
            <button class="modal-close-btn" onclick="cerrarModal('modalVerProducto')" style="position: absolute; top: 20px; right: 20px; background: rgba(255,255,255,0.2); border: none; color: white; width: 30px; height: 30px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: background 0.2s;">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div style="padding: 25px;">
            <div class="ver-prod-layout-premium" style="display: flex; gap: 25px; align-items: flex-start;">
                <!-- Imagen con efecto -->
                <div class="ver-prod-img-container" style="flex-shrink: 0; position: relative;">
                    <img id="verProductoImagen" src="" alt="" 
                        style="width: 140px; height: 140px; object-fit: cover; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 4px 12px rgba(0,0,0,0.08); transition: transform 0.3s ease;">
                    <div id="verProductoBadgeEstado" style="position: absolute; bottom: -10px; left: 50%; transform: translateX(-50%); white-space: nowrap;">
                        <!-- Badge se inyecta por JS -->
                    </div>
                </div>

                <!-- Datos con iconos -->
                <div style="flex: 1; display: flex; flex-direction: column; gap: 12px;">
                    <div class="ver-prod-item-premium">
                        <label style="display: block; font-size: 0.75rem; color: #6b7280; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">Nombre del Producto</label>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-tag" style="color: #3b82f6; width: 16px;"></i>
                            <span id="verProductoNombre" style="font-size: 1.1rem; font-weight: 700; color: #1f2937;"></span>
                        </div>
                    </div>

                    <div class="ver-prod-item-premium">
                        <label style="display: block; font-size: 0.75rem; color: #6b7280; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">Categoría</label>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-folder" style="color: #6366f1; width: 16px;"></i>
                            <span id="verProductoCategoria" style="font-size: 0.95rem; color: #4b5563;"></span>
                        </div>
                    </div>

                    <div style="display: flex; gap: 15px;">
                        <div class="ver-prod-item-premium" style="flex: 1;">
                            <label style="display: block; font-size: 0.75rem; color: #6b7280; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">Stock</label>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <i class="fas fa-cubes" style="color: #f59e0b; width: 16px;"></i>
                                <span id="verProductoStock" style="font-size: 1rem; font-weight: 700; color: #1f2937;"></span>
                            </div>
                        </div>
                        <div class="ver-prod-item-premium" style="flex: 1;">
                            <label style="display: block; font-size: 0.75rem; color: #6b7280; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">IVA</label>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <i class="fas fa-percentage" style="color: #10b981; width: 16px;"></i>
                                <span id="verProductoIva" style="font-size: 0.95rem; color: #4b5563;"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sección de Precio Destacada -->
            <div style="margin-top: 25px; padding: 15px 20px; background: #f8fafc; border-radius: 12px; border: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <span style="display: block; font-size: 0.75rem; color: #64748b; font-weight: 600; text-transform: uppercase;">Precio de Venta</span>
                    <span id="verProductoPrecioLabel" style="font-size: 0.8rem; color: #94a3b8;">Base imponible</span>
                </div>
                <div style="text-align: right;">
                    <span id="verProductoPrecio" style="font-size: 1.8rem; font-weight: 800; color: #059669; letter-spacing: -0.02em;"></span>
                </div>
            </div>

            <div style="margin-top: 20px; display: flex; justify-content: flex-end; gap: 10px;">
                <button class="btn-modal-cancelar" onclick="cerrarModal('modalVerProducto')" style="margin: 0; padding: 10px 25px; border-radius: 8px; font-weight: 600;">
                    Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ##-----------------------------------MODAL EDITAR PRODUCTO-----------------------------------## -->

<div class="modal-overlay" id="modalEditarProducto" style="display:none;">
    <div class="modal-content modal-premium" style="max-width: 600px; padding: 0; overflow: hidden;">
        <!-- Header Premium -->
        <div class="modal-header-premium" style="background: linear-gradient(135deg, #10b981, #059669); padding: 20px 25px; text-align: left; position: relative;">
            <h3 id="editProductoTitulo" style="margin: 0; color: #fff; font-size: 1.3rem;">Editar Producto</h3>
            <p id="editProductoSubtitulo" class="modal-subtitulo" style="margin: 5px 0 0 0; color: rgba(255,255,255,0.8); font-size: 0.85rem;">Modifica los datos del producto</p>
            <button class="modal-close-btn" onclick="cerrarModal('modalEditarProducto')" style="position: absolute; top: 20px; right: 20px; background: rgba(255,255,255,0.2); border: none; color: white; width: 30px; height: 30px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: background 0.2s;">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <input type="hidden" id="editProductoId">

        <div style="padding: 25px;">
            <div class="editar-prod-layout-premium" style="display: flex; gap: 25px; align-items: flex-start;">
                
                <!-- Columna Izquierda: Imagen -->
                <div class="editar-prod-imagen-wrapper" style="flex-shrink: 0; display: flex; flex-direction: column; gap: 10px; width: 140px;">
                    <div style="position: relative; width: 140px; height: 140px; border-radius: 12px; overflow: hidden; border: 1px solid #e5e7eb; box-shadow: 0 4px 12px rgba(0,0,0,0.05); background: #f9fafb;">
                        <img id="editProductoImagen" src="" alt="" style="width: 100%; height: 100%; object-fit: cover; cursor: zoom-in;" onclick="abrirImagenGrande(this.src, this.alt)">
                    </div>
                    <label class="btn-cambiar-imagen" title="Cambiar imagen" style="display: flex; align-items: center; justify-content: center; gap: 8px; background: #f3f4f6; border: 1px solid #d1d5db; padding: 8px; border-radius: 8px; cursor: pointer; font-size: 0.8rem; font-weight: 600; color: #4b5563; transition: all 0.2s;">
                        <i class="fas fa-camera"></i> Subir foto
                        <input type="file" id="editProductoImagenInput" accept="image/*" style="display:none;" onchange="previsualizarImagen(event)">
                    </label>
                </div>

                <!-- Columna Derecha: Formulario -->
                <div class="editar-prod-campos" style="flex: 1; display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="editar-prod-fila-premium" style="grid-column: span 2;">
                        <label style="display: block; font-size: 0.8rem; color: #4b5563; font-weight: 600; margin-bottom: 5px;">Nombre <span style="color:#ef4444">*</span></label>
                        <input type="text" id="editProductoNombre" style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; outline: none; transition: border-color 0.2s;" placeholder="Ej: Café con Leche">
                    </div>
                    
                    <div class="editar-prod-fila-premium" style="grid-column: span 2;">
                        <label style="display: block; font-size: 0.8rem; color: #4b5563; font-weight: 600; margin-bottom: 5px;">Categoría <span style="color:#ef4444">*</span></label>
                        <select id="editProductoCategoria" style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; outline: none; transition: border-color 0.2s; background-color: #fff;">
                        </select>
                    </div>
                    
                    <div class="editar-prod-fila-premium">
                        <label style="display: block; font-size: 0.8rem; color: #4b5563; font-weight: 600; margin-bottom: 5px;">Precio Base (€) <span style="color:#ef4444">*</span></label>
                        <input type="number" id="editProductoPrecio" step="0.0001" min="0" oninput="validarPrecisionDinamica(this, 'editProductoDecimales')" onblur="validarPrecisionDinamica(this, 'editProductoDecimales')" style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; outline: none; transition: border-color 0.2s;">
                    </div>
                    
                    <div class="editar-prod-fila-premium">
                        <label style="display: block; font-size: 0.8rem; color: #4b5563; font-weight: 600; margin-bottom: 5px;">Stock <span style="color:#ef4444">*</span></label>
                        <input type="number" id="editProductoStock" min="0" style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; outline: none; transition: border-color 0.2s;">
                    </div>

                    <div class="editar-prod-fila-premium">
                        <label style="display: block; font-size: 0.8rem; color: #4b5563; font-weight: 600; margin-bottom: 5px;">Tipo de IVA</label>
                        <select id="editProductoIva" style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; outline: none; transition: border-color 0.2s; background-color: #fff;">
                        </select>
                    </div>

                    <div class="editar-prod-fila-premium">
                        <label style="display: block; font-size: 0.8rem; color: #4b5563; font-weight: 600; margin-bottom: 5px;">Estado</label>
                        <select id="editProductoEstado" style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; outline: none; transition: border-color 0.2s; background-color: #fff;">
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>

                    <div class="editar-prod-fila-premium" style="grid-column: span 2;">
                        <label style="display: block; font-size: 0.8rem; color: #4b5563; font-weight: 600; margin-bottom: 5px;">Decimales permitidos (máx 4)</label>
                        <input type="number" id="editProductoDecimales" min="0" max="4" step="1" value="2" oninput="validarDecimalesRango(this, 'editProductoPrecio')" style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; outline: none; transition: border-color 0.2s;">
                    </div>
                </div>
            </div>

            <div style="margin-top: 30px; display: flex; justify-content: flex-end; gap: 12px; border-top: 1px solid #e5e7eb; padding-top: 20px;">
                <button class="btn-modal-cancelar" onclick="cerrarModal('modalEditarProducto')" style="margin: 0; padding: 10px 20px; border-radius: 8px; font-weight: 600;">
                    Cancelar
                </button>
                <button class="btn-exito" onclick="guardarCambiosProducto()" style="margin: 0; padding: 10px 25px; border-radius: 8px; font-weight: 600; background: #10b981; border: none; color: white; display: flex; align-items: center; gap: 8px; cursor: pointer; transition: background 0.2s;">
                    <i class="fas fa-save"></i> Guardar Cambios
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ##-----------------------------------MODAL VER USUARIO-----------------------------------## -->

<div class="modal-overlay" id="modalVerUsuario" style="display:none;">
    <div class="modal-content modal-premium" style="max-width: 600px; padding: 0; overflow: hidden; width: 90%;">
        <!-- Header Premium -->
        <div class="modal-header-premium" style="background: linear-gradient(135deg, #3b82f6, #1d4ed8); padding: 20px 25px; text-align: left; position: relative;">
            <h3 style="margin: 0; color: #fff; font-size: 1.3rem;">Detalle del Usuario</h3>
            <p class="modal-subtitulo" style="margin: 5px 0 0 0; color: rgba(255,255,255,0.8); font-size: 0.85rem;">Información completa y permisos</p>
            <button class="modal-close-btn" onclick="cerrarModal('modalVerUsuario')" style="position: absolute; top: 20px; right: 20px; background: rgba(255,255,255,0.2); border: none; color: white; width: 30px; height: 30px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: background 0.2s;">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div style="padding: 25px; max-height: 75vh; overflow-y: auto;">
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 25px;">
                <div class="ver-prod-item-premium">
                    <label style="display: block; font-size: 0.75rem; color: #6b7280; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">Nombre</label>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-user" style="color: #3b82f6; width: 16px;"></i>
                        <span id="verUsuarioNombre" style="font-size: 1.05rem; font-weight: 700; color: #1f2937;"></span>
                    </div>
                </div>
                <div class="ver-prod-item-premium">
                    <label style="display: block; font-size: 0.75rem; color: #6b7280; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">Email</label>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-envelope" style="color: #8b5cf6; width: 16px;"></i>
                        <span id="verUsuarioEmail" style="font-size: 0.95rem; color: #4b5563;"></span>
                    </div>
                </div>
                <div class="ver-prod-item-premium">
                    <label style="display: block; font-size: 0.75rem; color: #6b7280; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">Rol</label>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-user-shield" style="color: #f59e0b; width: 16px;"></i>
                        <span id="verUsuarioRol" style="font-size: 0.95rem; color: #4b5563; text-transform: capitalize;"></span>
                    </div>
                </div>
                <div class="ver-prod-item-premium">
                    <label style="display: block; font-size: 0.75rem; color: #6b7280; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">Fecha de Alta</label>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-calendar-alt" style="color: #10b981; width: 16px;"></i>
                        <span id="verUsuarioFecha" style="font-size: 0.95rem; color: #4b5563;"></span>
                    </div>
                </div>
                <div class="ver-prod-item-premium">
                    <label style="display: block; font-size: 0.75rem; color: #6b7280; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">Estado</label>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span id="verUsuarioEstado" style="font-size: 0.95rem; font-weight: 600;"></span>
                    </div>
                </div>
            </div>

            <!-- Stats -->
            <h4 style="font-size: 0.9rem; color: #374151; border-bottom: 1px solid #e5e7eb; padding-bottom: 5px; margin-bottom: 15px;"><i class="fas fa-chart-line" style="margin-right: 5px; color: #6366f1;"></i> Estadísticas</h4>
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-bottom: 25px; background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0;">
                <div class="ver-prod-item-premium" style="margin: 0; padding: 0; background: transparent; border: none;">
                    <label style="display: block; font-size: 0.75rem; color: #6b7280; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">Total Descansos</label>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-coffee" style="color: #d97706; width: 16px;"></i>
                        <span id="verUsuarioTotalDescansos" style="font-size: 1.1rem; font-weight: 700; color: #1f2937;"></span>
                    </div>
                </div>
                <div class="ver-prod-item-premium" style="margin: 0; padding: 0; background: transparent; border: none;">
                    <label style="display: block; font-size: 0.75rem; color: #6b7280; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">Total Cambios Turno</label>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-exchange-alt" style="color: #059669; width: 16px;"></i>
                        <span id="verUsuarioTotalTurnos" style="font-size: 1.1rem; font-weight: 700; color: #1f2937;"></span>
                    </div>
                </div>
            </div>

            <!-- Permissions -->
            <h4 style="font-size: 0.9rem; color: #374151; border-bottom: 1px solid #e5e7eb; padding-bottom: 5px; margin-bottom: 15px;"><i class="fas fa-key" style="margin-right: 5px; color: #ef4444;"></i> Permisos Especiales</h4>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px;">
                <div class="ver-prod-item-premium">
                    <label style="display: block; font-size: 0.75rem; color: #6b7280; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">Crear Productos</label>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span id="verUsuarioCrearProductos" style="font-size: 0.95rem; font-weight: 600;"></span>
                    </div>
                </div>
                <div class="ver-prod-item-premium">
                    <label style="display: block; font-size: 0.75rem; color: #6b7280; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">Producto Comodín</label>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span id="verUsuarioProductoComodin" style="font-size: 0.95rem; font-weight: 600;"></span>
                    </div>
                </div>
                <div class="ver-prod-item-premium">
                    <label style="display: block; font-size: 0.75rem; color: #6b7280; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">Retirar Dinero Caja</label>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span id="verUsuarioRetirarDinero" style="font-size: 0.95rem; font-weight: 600;"></span>
                    </div>
                </div>
            </div>

            <div style="margin-top: 25px; display: flex; justify-content: flex-end; padding-top: 15px; border-top: 1px solid #e5e7eb;">
                <button class="btn-modal-cancelar" onclick="cerrarModal('modalVerUsuario')" style="margin: 0; padding: 10px 25px; border-radius: 8px; font-weight: 600;">
                    Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ##-----------------------------------MODAL VER DEVOLUCION-----------------------------------## -->

<div class="modal-overlay" id="modalVerDevolucion" style="display:none;">
    <div class="modal-content modal-premium" style="max-width: 450px; padding: 0; overflow: hidden;">
        <div class="modal-header-premium modal-header-red" style="padding: 15px 25px;">
            <h3 style="margin: 0; font-size: 1.2rem;">Detalle de Devolución</h3>
            <p class="modal-subtitulo" style="margin: 0; opacity: 0.8;">Vista previa del comprobante fiscal</p>
        </div>

        <div id="ticketDevolucionContainer"
            style="background: white; padding: 20px; max-height: 70vh; overflow-y: auto; border-bottom: 1px solid #eee;">
            <!-- El ticket se generará aquí con generarHTMLComprobante -->
            <div style="text-align: center; padding: 40px; color: #666;">
                <i class="fas fa-spinner fa-spin" style="font-size: 2rem; margin-bottom: 10px;"></i>
                <p>Cargando comprobante...</p>
            </div>
        </div>

        <div style="display: flex; justify-content: center; gap: 10px; padding: 15px 25px; background: #f9fafb;">
            <button class="btn-modal-cancelar" onclick="cerrarModal('modalVerDevolucion')"
                style="min-width: 100px; margin: 0;">
                Cerrar
            </button>
            <button class="btn-exito" onclick="verTicketDevolucion()"
                style="min-width: 100px; margin: 0; background: #dc2626; border-color: #dc2626;">
                <i class="fas fa-print"></i> Imprimir
            </button>
        </div>
    </div>
</div>

<!-- ##-----------------------------------MODAL EDITAR/CREAR USUARIO-----------------------------------## -->

<div class="modal-overlay" id="modalEditarUsuario" style="display:none;">
    <div class="modal-content modal-premium" style="max-width: 650px; padding: 0; overflow: hidden; width: 90%;">
        <!-- Header Premium -->
        <div class="modal-header-premium" style="background: linear-gradient(135deg, #10b981, #059669); padding: 20px 25px; text-align: left; position: relative;">
            <h3 id="editUsuarioTitulo" style="margin: 0; color: #fff; font-size: 1.3rem;">Editar Usuario</h3>
            <p class="modal-subtitulo" style="margin: 5px 0 0 0; color: rgba(255,255,255,0.8); font-size: 0.85rem;">Modifica los datos del usuario</p>
            <button class="modal-close-btn" onclick="cerrarModal('modalEditarUsuario')" style="position: absolute; top: 20px; right: 20px; background: rgba(255,255,255,0.2); border: none; color: white; width: 30px; height: 30px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: background 0.2s;">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div style="padding: 25px;">
            <input type="hidden" id="editUsuarioId">

            <div class="editar-prod-campos" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 25px;">
                <!-- Columna Izquierda -->
                <div style="display: flex; flex-direction: column; gap: 15px;">
                    <div class="editar-prod-fila-premium">
                        <label style="display: block; font-size: 0.8rem; color: #4b5563; font-weight: 600; margin-bottom: 5px;">Nombre <span style="color:#ef4444">*</span></label>
                        <input type="text" id="editUsuarioNombre" required style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; outline: none; transition: border-color 0.2s;">
                    </div>
                    <div class="editar-prod-fila-premium">
                        <label style="display: block; font-size: 0.8rem; color: #4b5563; font-weight: 600; margin-bottom: 5px;">Password <span style="color:#ef4444">*</span></label>
                        <input type="password" id="editUsuarioPassword" style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; outline: none; transition: border-color 0.2s;">
                    </div>
                    <div class="editar-prod-fila-premium">
                        <label style="display: block; font-size: 0.8rem; color: #4b5563; font-weight: 600; margin-bottom: 5px;">Rol</label>
                        <select id="editUsuarioRol" style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; outline: none; transition: border-color 0.2s; background-color: #fff;">
                            <option value="empleado">Empleado</option>
                            <option value="admin">Administrador</option>
                        </select>
                    </div>
                </div>

                <!-- Columna Derecha -->
                <div style="display: flex; flex-direction: column; gap: 15px;">
                    <div class="editar-prod-fila-premium">
                        <label style="display: block; font-size: 0.8rem; color: #4b5563; font-weight: 600; margin-bottom: 5px;">Email <span style="color:#ef4444">*</span></label>
                        <input type="email" id="editUsuarioEmail" required style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; outline: none; transition: border-color 0.2s;">
                    </div>
                    <div class="editar-prod-fila-premium">
                        <label style="display: block; font-size: 0.8rem; color: #4b5563; font-weight: 600; margin-bottom: 5px;">Estado</label>
                        <select id="editUsuarioEstado" style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; outline: none; transition: border-color 0.2s; background-color: #fff;">
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Fila Permisos (Ocupa todo el ancho) -->
            <div class="editar-prod-fila-premium" id="filaPermisos" style="display: none; background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 20px;">
                <label style="display: block; font-size: 0.85rem; color: #374151; font-weight: 700; text-transform: uppercase; margin-bottom: 10px;">Permisos Adicionales</label>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px;">
                    <label style="display: flex; align-items: center; gap: 8px; font-size: 0.9rem; color: #4b5563; cursor: pointer;">
                        <input type="checkbox" id="editUsuarioPermisoCrearProductos" value="crear_productos" style="width: 16px; height: 16px; cursor: pointer;">
                        Permitir crear productos
                    </label>
                    <label style="display: flex; align-items: center; gap: 8px; font-size: 0.9rem; color: #4b5563; cursor: pointer;">
                        <input type="checkbox" id="editUsuarioPermisoModificarPrecios" value="modificar_precios" style="width: 16px; height: 16px; cursor: pointer;">
                        Permitir modificar precios
                    </label>
                    <label style="display: flex; align-items: center; gap: 8px; font-size: 0.9rem; color: #4b5563; cursor: pointer;">
                        <input type="checkbox" id="editUsuarioPermisoProductoComodin" value="producto_comodin" style="width: 16px; height: 16px; cursor: pointer;">
                        Usar Producto Comodín
                    </label>
                    <label style="display: flex; align-items: center; gap: 8px; font-size: 0.9rem; color: #4b5563; cursor: pointer;">
                        <input type="checkbox" id="editUsuarioPermisoRetirarDinero" value="retirar_dinero" style="width: 16px; height: 16px; cursor: pointer;">
                        Retirar Dinero de Caja
                    </label>
                </div>
                <p style="font-size: 0.75rem; color: #6b7280; margin-top: 10px; font-style: italic;">El empleado podrá acceder a estas funciones desde su vista de cajero.</p>
            </div>

            <div style="margin-top: 10px; display: flex; justify-content: flex-end; gap: 12px; border-top: 1px solid #e5e7eb; padding-top: 20px;">
                <button class="btn-modal-cancelar" onclick="cerrarModal('modalEditarUsuario')" style="margin: 0; padding: 10px 20px; border-radius: 8px; font-weight: 600;">
                    Cancelar
                </button>
                <button class="btn-exito" onclick="guardarCambiosUsuario()" style="margin: 0; padding: 10px 25px; border-radius: 8px; font-weight: 600; background: #10b981; border: none; color: white; display: flex; align-items: center; gap: 8px; cursor: pointer; transition: background 0.2s;">
                    <i class="fas fa-save"></i> Guardar Cambios
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ##-----------------------------------MODAL VER PROVEEDOR-----------------------------------## -->

<div class="modal-overlay" id="modalVerProveedor" style="display:none;">
    <div class="modal-content modal-verProducto" style="max-width: 900px;">
        <h3>Detalle del Proveedor</h3>
        <p class="modal-subtitulo">Información completa</p>

        <div style="display: flex; flex-direction: column; gap: 15px; margin: 20px 0;">
            <div class="ver-prod-fila">
                <span class="ver-prod-label">Nombre</span>
                <span id="verProveedorNombre" class="ver-prod-valor"></span>
            </div>
            <div class="ver-prod-fila">
                <span class="ver-prod-label">Contacto</span>
                <span id="verProveedorContacto" class="ver-prod-valor"></span>
            </div>
            <div class="ver-prod-fila">
                <span class="ver-prod-label">Email</span>
                <span id="verProveedorEmail" class="ver-prod-valor"></span>
            </div>
            <div class="ver-prod-fila">
                <span class="ver-prod-label">Dirección</span>
                <span id="verProveedorDireccion" class="ver-prod-valor"></span>
            </div>
            <div class="ver-prod-fila">
                <span class="ver-prod-label">Estado</span>
                <span id="verProveedorEstado" class="ver-prod-valor"></span>
            </div>
        </div>

        <div style="border-top: 1px solid #e5e7eb; padding-top: 15px; margin-top: 15px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <h4 style="color: #374151; font-size: 1.1rem; margin: 0;">Productos Suministrados</h4>
                <button class="btn-admin-accion btn-nuevo" onclick="agregarProductoProveedor()"
                    style="padding: 4px 10px; font-size: 0.85rem;">
                    <i class="fas fa-plus"></i> Añadir Producto
                </button>
            </div>

            <div style="max-height: 400px; overflow-y: auto; border: 1px solid #e5e7eb; border-radius: 4px;">
                <table class="admin-tabla" id="tablaProductosProveedor"
                    style="font-size: 0.85rem; margin-bottom: 0; table-layout: fixed; width: 100%;">
                    <thead style="position: sticky; top: 0;">
                        <tr>
                            <th style="padding: 8px; min-width: 150px;">Producto</th>
                            <th style="padding: 8px; width: 150px; text-align: center;">Precio Compra</th>
                            <th style="padding: 8px; width: 130px; text-align: center;">R. Eq (%)</th>
                            <th style="padding: 8px; width: 100px; text-align: center;">Precios</th>
                            <th style="padding: 8px; width: 100px; text-align: center;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="listaProductosProveedor">
                        <!-- Rellenado con Javascript -->
                    </tbody>
                </table>
            </div>
            <p id="msgSinProductosProveedor" class="sin-productos" style="display: none; padding: 15px 0;">Este
                proveedor no tiene productos asignados.</p>
        </div>

        <div style="display: flex; justify-content: center; margin-top: 20px;">
            <button class="btn-modal-cancelar" onclick="cerrarModal('modalVerProveedor')" style="min-width: 100px;">
                Cerrar
            </button>
        </div>
    </div>
</div>

<!-- ##-----------------------------------MODAL ASOCIAR PRODUCTO PROVEEDOR-----------------------------------## -->

<div class="modal-overlay" id="modalAsociarProducto" style="display:none; z-index: 9999;">
    <div class="modal-content modal-editarProducto" style="max-width: 420px;">
        <h3 id="asociarProductoTitulo">Asociar Producto</h3>
        <p class="modal-subtitulo" id="asociarProductoSubtitulo">Selecciona un producto y fija su recargo</p>

        <input type="hidden" id="asociarProvIdAsociacion">
        <input type="hidden" id="asociarProvIdProveedor">

        <div class="editar-prod-campos" style="max-width: 100%;">
            <div class="editar-prod-fila" id="contenedorSelectProducto">
                <label>Producto <span style="color:red">*</span></label>
                <select id="asociarProvIdProducto" style="padding: 8px; border-radius: 4px; border: 1px solid #d1d5db;">
                    <!-- Rellenado con Javascript -->
                </select>
            </div>

            <div class="editar-prod-fila" id="contenedorTextoProducto" style="display: none;">
                <label>Producto</label>
                <input type="text" id="asociarProvNombreProducto" readonly
                    style="background-color: #f3f4f6; color: #6b7280; pointer-events: none;">
            </div>

            <div class="editar-prod-fila">
                <label>Precio Proveedor (€) <span style="color:red">*</span></label>
                <input type="number" id="asociarProvPrecio" step="0.0001" min="0" value="0.00"
                    oninput="validar4Decimales(this)" onblur="validar4Decimales(this)" required>
            </div>

            <div class="editar-prod-fila">
                <label>Recargo Equivalencia (%) <span style="color:red">*</span></label>
                <input type="number" id="asociarProvRecargo" step="0.0001" min="0" value="0.00"
                    oninput="validar4Decimales(this)" onblur="validar4Decimales(this)" required>
            </div>
        </div>

        <div class="editar-prod-botones">
            <button class="btn-modal-cancelar"
                onclick="cerrarModal('modalAsociarProducto'); abrirModal('modalVerProveedor')">Cancelar</button>
            <button class="btn-exito" onclick="guardarCambiosAsociarProducto()">
                <i class="fas fa-save"></i> Guardar
            </button>
        </div>
    </div>
</div>

<!-- ##-----------------------------------MODAL EDITAR/CREAR PROVEEDOR-----------------------------------## -->

<div class="modal-overlay" id="modalEditarProveedor" style="display:none;">
    <div class="modal-content modal-editarProducto">
        <h3 id="editProveedorTitulo">Editar Proveedor</h3>
        <p class="modal-subtitulo">Modifica los datos del proveedor</p>

        <input type="hidden" id="editProveedorId">

        <div class="editar-prod-campos" style="max-width: 100%;">
            <div class="editar-prod-fila">
                <label>Nombre <span style="color:red">*</span></label>
                <input type="text" id="editProveedorNombre" required>
            </div>
            <div class="editar-prod-fila">
                <label>Contacto (Teléfono)</label>
                <input type="text" id="editProveedorContacto">
            </div>
            <div class="editar-prod-fila">
                <label>Email</label>
                <input type="email" id="editProveedorEmail">
            </div>
            <div class="editar-prod-fila">
                <label>Dirección</label>
                <input type="text" id="editProveedorDireccion">
            </div>
            <div class="editar-prod-fila">
                <label>Estado</label>
                <select id="editProveedorEstado">
                    <option value="1">Activo</option>
                    <option value="0">Inactivo</option>
                </select>
            </div>
        </div>

        <div class="editar-prod-botones">
            <button class="btn-modal-cancelar" onclick="cerrarModal('modalEditarProveedor')">Cancelar</button>
            <button class="btn-exito" onclick="guardarCambiosProveedor()">
                <i class="fas fa-save"></i> Guardar Cambios
            </button>
        </div>
    </div>
</div>

<!-- ##=========================== MODAL: NUEVO CLIENTE (ADMIN) ===========================## -->
<!-- Modal para añadir un cliente habitual (DNI, nombre, apellidos, fecha alta) -->
<div class="modal-overlay" id="modalClienteHabitual" style="display:none;">
    <div class="modal-content modal-premium" style="max-width: 600px; padding: 0; overflow: hidden; width: 90%;">
        <!-- Header Premium -->
        <div class="modal-header-premium" style="background: linear-gradient(135deg, #10b981, #059669); padding: 20px 25px; text-align: left; position: relative;">
            <h3 style="margin: 0; color: #fff; font-size: 1.3rem;">Nuevo Cliente</h3>
            <p class="modal-subtitulo" style="margin: 5px 0 0 0; color: rgba(255,255,255,0.8); font-size: 0.85rem;">Complete los datos del cliente</p>
            <button class="modal-close-btn" onclick="cerrarModal('modalClienteHabitual')" style="position: absolute; top: 20px; right: 20px; background: rgba(255,255,255,0.2); border: none; color: white; width: 30px; height: 30px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: background 0.2s;">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div style="padding: 25px;">
            <div class="editar-prod-campos" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                <div class="editar-prod-fila-premium">
                    <label for="clienteHabitualDni" style="display: block; font-size: 0.8rem; color: #4b5563; font-weight: 600; margin-bottom: 5px;">DNI <span style="color:#ef4444">*</span></label>
                    <input type="text" id="clienteHabitualDni" placeholder="12345678A" maxlength="20" style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; outline: none; transition: border-color 0.2s;">
                </div>
                <div class="editar-prod-fila-premium">
                    <label for="clienteHabitualNombre" style="display: block; font-size: 0.8rem; color: #4b5563; font-weight: 600; margin-bottom: 5px;">Nombre <span style="color:#ef4444">*</span></label>
                    <input type="text" id="clienteHabitualNombre" placeholder="Juan" maxlength="100" style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; outline: none; transition: border-color 0.2s;">
                </div>
                <div class="editar-prod-fila-premium">
                    <label for="clienteHabitualApellidos" style="display: block; font-size: 0.8rem; color: #4b5563; font-weight: 600; margin-bottom: 5px;">Apellidos <span style="color:#ef4444">*</span></label>
                    <input type="text" id="clienteHabitualApellidos" placeholder="García López" maxlength="150" style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; outline: none; transition: border-color 0.2s;">
                </div>
                <div class="editar-prod-fila-premium">
                    <label for="clienteHabitualDireccion" style="display: block; font-size: 0.8rem; color: #4b5563; font-weight: 600; margin-bottom: 5px;">Dirección</label>
                    <input type="text" id="clienteHabitualDireccion" placeholder="Calle, Número, Ciudad" maxlength="255" style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; outline: none; transition: border-color 0.2s;">
                </div>
                <div class="editar-prod-fila-premium">
                    <label for="clienteHabitualFecha" style="display: block; font-size: 0.8rem; color: #4b5563; font-weight: 600; margin-bottom: 5px;">Fecha de Alta</label>
                    <input type="datetime-local" id="clienteHabitualFecha" readonly style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; outline: none; transition: border-color 0.2s; background-color: #f3f4f6; color: #6b7280;">
                </div>
            </div>

            <!-- Botones: Cancelar y Guardar -->
            <div style="margin-top: 30px; display: flex; justify-content: flex-end; gap: 12px; border-top: 1px solid #e5e7eb; padding-top: 20px;">
                <button class="btn-modal-cancelar" onclick="cerrarModal('modalClienteHabitual')" style="margin: 0; padding: 10px 20px; border-radius: 8px; font-weight: 600;">Cancelar</button>
                <button class="btn-exito" id="btnGuardarClienteHabitual" onclick="guardarClienteHabitualAdmin()" style="margin: 0; padding: 10px 25px; border-radius: 8px; font-weight: 600; background: #10b981; border: none; color: white; display: flex; align-items: center; gap: 8px; cursor: pointer; transition: background 0.2s;">
                    <i class="fas fa-save"></i> Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ##=========================== MODAL: EDITAR CLIENTE (ADMIN) ===========================## -->
<div class="modal-overlay" id="modalEditarCliente" style="display:none;">
    <div class="modal-content modal-premium" style="max-width: 600px; padding: 0; overflow: hidden; width: 90%;">
        <!-- Header Premium -->
        <div class="modal-header-premium" style="background: linear-gradient(135deg, #10b981, #059669); padding: 20px 25px; text-align: left; position: relative;">
            <h3 style="margin: 0; color: #fff; font-size: 1.3rem;">Editar Cliente</h3>
            <p class="modal-subtitulo" style="margin: 5px 0 0 0; color: rgba(255,255,255,0.8); font-size: 0.85rem;">Modifique los datos del cliente</p>
            <button class="modal-close-btn" onclick="cerrarModal('modalEditarCliente')" style="position: absolute; top: 20px; right: 20px; background: rgba(255,255,255,0.2); border: none; color: white; width: 30px; height: 30px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: background 0.2s;">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div style="padding: 25px;">
            <input type="hidden" id="editarClienteId">

            <div class="editar-prod-campos" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                <div class="editar-prod-fila-premium">
                    <label for="editarClienteDni" style="display: block; font-size: 0.8rem; color: #4b5563; font-weight: 600; margin-bottom: 5px;">DNI <span style="color:#ef4444">*</span></label>
                    <input type="text" id="editarClienteDni" placeholder="12345678A" maxlength="20" style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; outline: none; transition: border-color 0.2s;">
                </div>
                <div class="editar-prod-fila-premium">
                    <label for="editarClienteNombre" style="display: block; font-size: 0.8rem; color: #4b5563; font-weight: 600; margin-bottom: 5px;">Nombre <span style="color:#ef4444">*</span></label>
                    <input type="text" id="editarClienteNombre" placeholder="Juan" maxlength="100" style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; outline: none; transition: border-color 0.2s;">
                </div>
                <div class="editar-prod-fila-premium">
                    <label for="editarClienteApellidos" style="display: block; font-size: 0.8rem; color: #4b5563; font-weight: 600; margin-bottom: 5px;">Apellidos <span style="color:#ef4444">*</span></label>
                    <input type="text" id="editarClienteApellidos" placeholder="García López" maxlength="150" style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; outline: none; transition: border-color 0.2s;">
                </div>
                <div class="editar-prod-fila-premium">
                    <label for="editarClienteDireccion" style="display: block; font-size: 0.8rem; color: #4b5563; font-weight: 600; margin-bottom: 5px;">Dirección</label>
                    <input type="text" id="editarClienteDireccion" placeholder="Calle, Número, Ciudad" maxlength="255" style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; outline: none; transition: border-color 0.2s;">
                </div>
                <div class="editar-prod-fila-premium">
                    <label for="editarClientePuntos" style="display: block; font-size: 0.8rem; color: #4b5563; font-weight: 600; margin-bottom: 5px;">Puntos</label>
                    <input type="number" id="editarClientePuntos" placeholder="0" min="0" onchange="this.value = Math.max(0, this.value);" style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; outline: none; transition: border-color 0.2s;">
                </div>
            </div>

            <!-- Botones: Cancelar y Guardar -->
            <div style="margin-top: 30px; display: flex; justify-content: flex-end; gap: 12px; border-top: 1px solid #e5e7eb; padding-top: 20px;">
                <button class="btn-modal-cancelar" onclick="cerrarModal('modalEditarCliente')" style="margin: 0; padding: 10px 20px; border-radius: 8px; font-weight: 600;">Cancelar</button>
                <button class="btn-exito" id="btnGuardarClienteEditado" onclick="guardarClienteEditado()" style="margin: 0; padding: 10px 25px; border-radius: 8px; font-weight: 600; background: #10b981; border: none; color: white; display: flex; align-items: center; gap: 8px; cursor: pointer; transition: background 0.2s;">
                    <i class="fas fa-save"></i> Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ##-----------------------------------MODAL EDITAR/CREAR TIPO DE IVA-----------------------------------## -->

<div class="modal-overlay" id="modalEditarIva" style="display:none;">
    <div class="modal-content modal-editarProducto" style="max-width: 420px;">
        <h3 id="editIvaTitulo">Nuevo Tipo de IVA</h3>
        <p id="editIvaSubtitulo" class="modal-subtitulo">Introduce los datos del nuevo tipo de IVA</p>

        <input type="hidden" id="editIvaId">

        <div class="editar-prod-campos" style="max-width: 100%;">
            <div class="editar-prod-fila">
                <label>Nombre <span style="color:red">*</span></label>
                <input type="text" id="editIvaNombre" placeholder="Ej: IVA Reducido">
            </div>
            <div class="editar-prod-fila">
                <label>Porcentaje (%) <span style="color:red">*</span></label>
                <input type="number" id="editIvaPorcentaje" step="0.01" min="0" max="100" placeholder="Ej: 10">
            </div>
        </div>

        <div class="editar-prod-botones">
            <button class="btn-modal-cancelar" onclick="cerrarModal('modalEditarIva')">Cancelar</button>
            <button class="btn-exito" onclick="guardarIva()">
                <i class="fas fa-save"></i> Guardar
            </button>
        </div>
    </div>
</div>

<!-- ##=========================== MODAL: PROGRAMAR CAMBIO DE IVA ===========================## -->
<div class="modal-overlay" id="modalProgramarIVA"
    style="display:none; position: fixed; z-index: 10100; left: 0; top: 0; width: 100%; height: 100%; align-items: center; justify-content: center; background-color: rgba(0,0,0,0.6); backdrop-filter: blur(2px);">
    <div class="modal-content" style="max-width: 450px; text-align: left;">
        <h3 style="margin-bottom: 5px;"><i class="fas fa-clock" style="margin-right: 10px;"></i>Programar Cambio de IVA
        </h3>
        <p class="modal-subtitulo" style="margin-bottom: 20px;">El cambio de IVA se aplicará en la fecha y hora
            especificadas.</p>

        <input type="hidden" id="ivaProgramado" value="">

        <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 5px; font-weight: 600;">IVA a aplicar:</label>
            <div id="ivaProgramadoNombre"
                style="padding: 10px; background: var(--bg-input); border-radius: 6px; font-weight: 500;"></div>
        </div>

        <div style="margin-bottom: 20px;">
            <label for="fechaProgramada" style="display: block; margin-bottom: 5px; font-weight: 600;">Fecha y hora
                programada:</label>
            <input type="datetime-local" id="fechaProgramada"
                style="width: 100%; padding: 10px; border: 1px solid var(--border-main); border-radius: 6px; font-size: 14px; background: var(--bg-input); color: var(--text-main);">
        </div>

        <p style="font-size: 13px; color: var(--text-secondary); margin-bottom: 20px;">
            <i class="fas fa-info-circle"></i> El sistema comprobará los cambios programados al acceder a esta sección.
            También puede ver los cambios programados desde el panel de Configuración.
        </p>

        <div class="editar-prod-botones">
            <button class="btn-modal-cancelar" onclick="cerrarModal('modalProgramarIVA')">Cancelar</button>
            <button class="btn-exito" onclick="programarCambioIVA()">
                <i class="fas fa-clock"></i> Programar
            </button>
        </div>
    </div>
</div>

<!-- ##=========================== MODAL: VER CAMBIOS PROGRAMADOS DE IVA ===========================## -->
<div class="modal-overlay" id="modalVerCambiosProgramadosIVA"
    style="display:none; position: fixed; z-index: 10100; left: 0; top: 0; width: 100%; height: 100%; align-items: center; justify-content: center; background-color: rgba(0,0,0,0.6); backdrop-filter: blur(2px);">
    <div class="modal-content" style="max-width: 800px; text-align: left; max-height: 80vh;">
        <h3 style="margin-bottom: 5px;"><i class="fas fa-list" style="margin-right: 10px;"></i>Cambios de IVA
            Programados
        </h3>
        <p class="modal-subtitulo" style="margin-bottom: 15px;">
            Lista de todos los cambios de IVA programados. Puedes editar o eliminar los cambios pendientes.
        </p>

        <div id="listaCambiosProgramadosIVA"
            style="max-height: 400px; overflow-y: auto; margin-bottom: 20px; border: 1px solid var(--border-main); border-radius: 8px;">
            <!-- La tabla se cargará dinámicamente -->
        </div>

        <div class="editar-prod-botones">
            <button class="btn-modal-cancelar" onclick="cerrarModal('modalVerCambiosProgramadosIVA')">Cerrar</button>
        </div>
    </div>
</div>

<!-- ##=========================== MODAL: PROGRAMAR AJUSTE DE PRECIOS ===========================## -->
<div class="modal-overlay" id="modalProgramarAjustePrecios"
    style="display:none; position: fixed; z-index: 10100; left: 0; top: 0; width: 100%; height: 100%; align-items: center; justify-content: center; background-color: rgba(0,0,0,0.6); backdrop-filter: blur(2px);">
    <div class="modal-content" style="max-width: 450px; text-align: left;">
        <h3 style="margin-bottom: 5px;"><i class="fas fa-clock" style="margin-right: 10px;"></i>Programar Ajuste de
            Precios
        </h3>
        <p class="modal-subtitulo" style="margin-bottom: 15px;">
            El ajuste de precios se aplicará a <span id="ajusteProgramadoProductosCount">0</span> productos.
        </p>

        <div class="form-group" style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 5px; font-weight: 600;">Porcentaje de ajuste:</label>
            <input type="number" id="ajusteProgramadoPorcentaje" step="0.01" placeholder="Ej: 10 o -10"
                class="input-buscarProducto"
                style="width: 100%; padding: 10px; background: var(--bg-input); color: var(--text-main); border: 1px solid var(--border-main);">
            <small style="color: var(--text-secondary);">Positivo = subir precios | Negativo = bajar precios</small>
        </div>

        <div class="form-group" style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 5px; font-weight: 600;">Fecha y hora programada:</label>
            <input type="datetime-local" id="fechaProgramadaAjuste" class="input-buscarProducto"
                style="width: 100%; padding: 10px; background: var(--bg-input); color: var(--text-main); border: 1px solid var(--border-main);">
        </div>

        <div class="editar-prod-botones">
            <button class="btn-modal-cancelar" onclick="cerrarModal('modalProgramarAjustePrecios')">Cancelar</button>
            <button class="btn-exito" onclick="programarAjustePrecios()">
                <i class="fas fa-clock"></i> Programar
            </button>
        </div>
    </div>
</div>

<!-- ##=========================== MODAL: VER AJUSTES PROGRAMADOS DE PRECIOS ===========================## -->
<div class="modal-overlay" id="modalVerAjustesProgramadosPrecios"
    style="display:none; position: fixed; z-index: 10100; left: 0; top: 0; width: 100%; height: 100%; align-items: center; justify-content: center; background-color: rgba(0,0,0,0.6); backdrop-filter: blur(2px);">
    <div class="modal-content" style="max-width: 800px; text-align: left; max-height: 80vh;">
        <h3 style="margin-bottom: 5px;"><i class="fas fa-list" style="margin-right: 10px;"></i>Ajustes de Precios
            Programados
        </h3>
        <p class="modal-subtitulo" style="margin-bottom: 15px;">
            Lista de todos los ajustes de precios programados. Puedes editar o eliminar los ajustes pendientes.
        </p>

        <div id="listaAjustesProgramadosPrecios"
            style="max-height: 400px; overflow-y: auto; margin-bottom: 20px; border: 1px solid var(--border-main); border-radius: 8px;">
            <!-- La tabla se cargará dinámicamente -->
        </div>

        <div class="editar-prod-botones">
            <button class="btn-modal-cancelar"
                onclick="cerrarModal('modalVerAjustesProgramadosPrecios')">Cerrar</button>
        </div>
    </div>
</div>

<!-- ##=========================== MODAL: VER DETALLES DE CAMBIO DE IVA PROGRAMADO ===========================## -->
<div class="modal-overlay" id="modalVerDetallesCambioIVA"
    style="display:none; position: fixed; z-index: 10100; left: 0; top: 0; width: 100%; height: 100%; align-items: center; justify-content: center; background-color: rgba(0,0,0,0.6); backdrop-filter: blur(2px);">
    <div class="modal-content" style="max-width: 900px; text-align: left; max-height: 80vh;">
        <h3 style="margin-bottom: 5px;"><i class="fas fa-info-circle" style="margin-right: 10px;"></i>Detalles del
            Cambio de IVA Programado
        </h3>
        <div id="detallesCambioIVAInfo"
            style="margin-bottom: 15px; background: var(--bg-secondary); padding: 15px; border-radius: 8px;">
            <!-- Info se cargará dinámicamente -->
        </div>
        <div id="detallesCambioIVATabla"
            style="max-height: 400px; overflow-y: auto; margin-bottom: 20px; border: 1px solid var(--border-main); border-radius: 8px;">
            <!-- Tabla se cargará dinámicamente -->
        </div>
        <div class="editar-prod-botones">
            <button class="btn-modal-cancelar" onclick="cerrarModal('modalVerDetallesCambioIVA')">Cerrar</button>
        </div>
    </div>
</div>

<!-- ##=========================== MODAL: VER DETALLES DE AJUSTE DE PRECIOS PROGRAMADO ===========================## -->
<div class="modal-overlay" id="modalVerDetallesAjustePrecios"
    style="display:none; position: fixed; z-index: 10100; left: 0; top: 0; width: 100%; height: 100%; align-items: center; justify-content: center; background-color: rgba(0,0,0,0.6); backdrop-filter: blur(2px);">
    <div class="modal-content" style="max-width: 900px; text-align: left; max-height: 80vh;">
        <h3 style="margin-bottom: 5px;"><i class="fas fa-info-circle" style="margin-right: 10px;"></i>Detalles del
            Ajuste de Precios Programado
        </h3>
        <div id="detallesAjustePreciosInfo"
            style="margin-bottom: 15px; background: var(--bg-secondary); padding: 15px; border-radius: 8px;">
            <!-- Info se cargará dinámicamente -->
        </div>
        <div id="detallesAjustePreciosTabla"
            style="max-height: 400px; overflow-y: auto; margin-bottom: 20px; border: 1px solid var(--border-main); border-radius: 8px;">
            <!-- Tabla se cargará dinámicamente -->
        </div>
        <div class="editar-prod-botones">
            <button class="btn-modal-cancelar" onclick="cerrarModal('modalVerDetallesAjustePrecios')">Cerrar</button>
        </div>
    </div>
</div>

<!-- ##=========================== MODAL: CONFLICTO PRECIOS MANUALES ===========================## -->
<div class="modal-overlay" id="modalConflictosTarifa"
    style="display:none; position: fixed; z-index: 10100; left: 0; top: 0; width: 100%; height: 100%; align-items: center; justify-content: center; background-color: rgba(0,0,0,0.6); backdrop-filter: blur(2px);">
    <div class="modal-content" style="max-width: 600px; text-align: left;">
        <h3 style="margin-bottom: 5px;">Conflictos de Precios</h3>
        <p class="modal-subtitulo" style="margin-bottom: 20px;">Se han detectado productos con precios modificados
            manualmente en esta tarifa. ¿Qué desea hacer?</p>

        <div id="listaProductosConflictivos"
            style="max-height: 250px; overflow-y: auto; margin-bottom: 20px; border: 1px solid var(--border-main); border-radius: 8px; padding: 10px;">
            <!-- La lista se llenará dinámicamente -->
        </div>

        <div style="display: flex; gap: 10px; justify-content: flex-end;">
            <button class="btn-modal-cancelar" onclick="cerrarModal('modalConflictosTarifa')">Cancelar</button>
            <button class="btn-editar" onclick="confirmarCambioTarifa(false)" style="margin: 0;">Mantener
                Manuales</button>
            <button class="btn-exito" onclick="confirmarCambioTarifa(true)" style="margin: 0;">Sobreescribir
                Todos</button>
        </div>
    </div>
</div>

<!-- ##=========================== MODAL: ESTADÍSTICAS DE PRODUCTOS ===========================## -->
</div>
</div>
</div>

<!-- ##=========================== MODAL: PROGRAMAR CAMBIOS EN TARIFAS ===========================## -->
<div class="modal-overlay" id="modalProgramarCambiosTarifas"
    style="display:none; position: fixed; z-index: 10100; left: 0; top: 0; width: 100%; height: 100%; align-items: center; justify-content: center; background-color: rgba(0,0,0,0.6); backdrop-filter: blur(2px);">
    <div class="modal-content" style="max-width: 500px; text-align: left;">
        <h3 style="margin-bottom: 5px;"><i class="fas fa-clock" style="margin-right: 10px;"></i>Confirmar Programación
        </h3>
        <p class="modal-subtitulo" style="margin-bottom: 15px;">
            Se van a programar <span id="countCambiosProgramar">0</span> cambios de precios.
        </p>

        <div class="form-group" style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 8px; font-weight: 600;">Fecha y hora de aplicación:</label>
            <input type="datetime-local" id="fechaProgramadaTarifas" class="input-buscarProducto"
                style="width: 100%; padding: 12px; background: var(--bg-input); color: var(--text-main); border: 1px solid var(--border-main); border-radius: 8px; font-size: 16px;">
        </div>

        <div
            style="background: var(--bg-secondary); padding: 15px; border-radius: 8px; margin-bottom: 20px; font-size: 14px;">
            <i class="fas fa-info-circle" style="color: #3b82f6; margin-right: 8px;"></i>
            Los precios cambiarán automáticamente en la fecha seleccionada cuando un administrador acceda al sistema.
        </div>

        <div class="editar-prod-botones">
            <button class="btn-modal-cancelar" onclick="cerrarModal('modalProgramarCambiosTarifas')">Cancelar</button>
            <button class="btn-exito" onclick="ejecutarGuardarProgramacionTarifas()">
                <i class="fas fa-save"></i> Confirmar y Programar
            </button>
        </div>
    </div>
</div>

<!-- ##=========================== MODAL: VER CAMBIOS DE TARIFAS PROGRAMADOS ===========================## -->
<div class="modal-overlay" id="modalVerCambiosTarifasProgramados"
    style="display:none; position: fixed; z-index: 10100; left: 0; top: 0; width: 100%; height: 100%; align-items: center; justify-content: center; background-color: rgba(0,0,0,0.6); backdrop-filter: blur(2px);">
    <div class="modal-content" style="max-width: 850px; text-align: left; max-height: 85vh; width: 90%;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <h3 style="margin: 0;"><i class="fas fa-history" style="margin-right: 10px;"></i>Historial de Programaciones
            </h3>
            <button onclick="cerrarModal('modalVerCambiosTarifasProgramados')"
                style="background:none; border:none; font-size: 24px; cursor:pointer; color: var(--text-secondary);">&times;</button>
        </div>
        <p class="modal-subtitulo" style="margin-bottom: 20px;">
            Listado de lotes de cambios de precios programados. Los lotes 'Pendientes' pueden ser cancelados.
        </p>

        <div id="listaBatchesTarifas"
            style="max-height: 450px; overflow-y: auto; border: 1px solid var(--border-main); border-radius: 8px; background: var(--bg-main);">
            <!-- La tabla se cargará dinámicamente -->
        </div>

        <div class="editar-prod-botones" style="margin-top: 20px;">
            <button class="btn-modal-cancelar"
                onclick="cerrarModal('modalVerCambiosTarifasProgramados')">Cerrar</button>
        </div>
    </div>
</div>

<!-- ##=========================== MODAL: DETALLES DE LOTE DE TARIFAS ===========================## -->
<div class="modal-overlay" id="modalDetalleBatchTarifas"
    style="display:none; position: fixed; z-index: 10200; left: 0; top: 0; width: 100%; height: 100%; align-items: center; justify-content: center; background-color: rgba(0,0,0,0.4); backdrop-filter: blur(1px);">
    <div class="modal-content" style="max-width: 700px; text-align: left; max-height: 80vh; width: 95%;">
        <h3>Detalles del Lote #<span id="detalleBatchId"></span></h3>
        <p id="detalleBatchMeta" style="margin-bottom: 15px; color: var(--text-secondary); font-size: 14px;"></p>

        <div id="tablaDetalleBatch"
            style="max-height: 350px; overflow-y: auto; border: 1px solid var(--border-main); border-radius: 8px; margin-bottom: 20px;">
            <!-- Tabla dinámica -->
        </div>

        <div style="display: flex; justify-content: flex-end;">
            <button class="btn-modal-cancelar" onclick="cerrarModal('modalDetalleBatchTarifas')">Regresar</button>
        </div>
    </div>
</div>

<!-- ##=========================== MODAL: ESTADÍSTICAS DE PRODUCTOS ===========================## -->
<div class="modal-overlay" id="modalEstadisticasProductos"
    style="display:none; position: fixed; z-index: 10100; left: 0; top: 0; width: 100%; height: 100%; align-items: center; justify-content: center; background-color: rgba(0,0,0,0.6); backdrop-filter: blur(2px);">
    <div class="modal-content" style="max-width: 700px; width: 90%; max-height: 80vh; overflow-y: auto;">
        <div class="modal-header"
            style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 15px; border-bottom: 1px solid #e5e7eb; margin-bottom: 20px;">
            <h2 style="margin: 0; font-size: 20px; font-weight: 600;"><i class="fas fa-chart-bar"
                    style="margin-right: 10px;"></i>Estadísticas de Productos</h2>
            <button onclick="cerrarModal('modalEstadisticasProductos')"
                style="background: none; border: none; font-size: 24px; cursor: pointer; color: #6b7280;">&times;</button>
        </div>
        <div id="estadisticasProductosContenido">
            <!-- Contenido cargado dinámicamente -->
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">



<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<!-- ##-----------------------------------MODAL CENTRO DE TAREAS-----------------------------------## -->
<!-- Muestra el progreso de tareas pesadas (Backups, Actualizaciones de IVA, etc.) -->
<div class="modal-overlay" id="modalCentroTareas" style="display:none;">
    <div class="modal-content" style="max-width: 600px; width: 90%;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px">
            <h3 style="margin:0"><i class="fas fa-tasks"></i> Centro de Tareas</h3>
            <button class="btn-tpv" onclick="cerrarModal('modalCentroTareas')"
                style="background:none;color:var(--text-main);font-size:1.2rem;padding:5px;border:none"><i
                    class="fas fa-times"></i></button>
        </div>
        <p class="modal-subtitulo">Estado de los procesos en segundo plano</p>

        <div id="listaTareasAdmin" style="margin-top:20px;max-height:400px;overflow-y:auto">
            <div class="reports-loading"><i class="fas fa-spinner fa-spin"></i> Cargando tareas...</div>
        </div>

        <div
            style="display: flex; justify-content: center; margin-top: 25px; padding-top: 15px; border-top: 1px solid var(--border-main);">
            <button class="btn-modal-cancelar" onclick="cerrarModal('modalCentroTareas')" style="min-width: 120px;">
                Cerrar
            </button>
        </div>
    </div>
</div>