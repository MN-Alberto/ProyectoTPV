<!-- 
    Dependencias de JavaScript para la Administración
    ------------------------------------------------
    Se cargan todos los módulos necesarios para la gestión del TPV.
    admin-main.js actúa como el orquestador principal.
-->
<!-- DEBUG: Global error handler - REMOVE after fixing -->
<script>
window.onerror = function(msg, url, line, col, error) {
    var file = url ? url.split('/').pop() : 'unknown';
    alert('JS ERROR in ' + file + ' (line ' + line + '):\n' + msg);
    console.error('JS ERROR:', {file: file, line: line, col: col, msg: msg, error: error});
    return false;
};
</script>
<script src="webroot/js/admin-state.js"></script>
<script src="webroot/js/admin-utils.js?v=10"></script>
<script src="webroot/js/admin-backups.js"></script>
<script src="webroot/js/admin-caja.js?v=10"></script>
<script src="webroot/js/admin-clientes.js?v=10"></script>
<script src="webroot/js/admin-configuracion.js"></script>
<script src="webroot/js/admin-informes.js"></script>
<script src="webroot/js/admin-logs.js"></script>
<script src="webroot/js/admin-pagination.js"></script>
<script src="webroot/js/admin-productos.js"></script>
<script src="webroot/js/admin-tarifas.js?v=10"></script>
<script src="webroot/js/admin-usuarios.js?v=10"></script>
<script src="webroot/js/admin-verifactu.js?v=10"></script>
<script src="webroot/js/lib/qrcode.min.js"></script>
<script src="webroot/js/shared-impresion.js"></script>
<script src="webroot/js/admin-ventas.js?v=10"></script>
<script src="webroot/js/admin-main.js?v=10"></script>

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

<!-- DEBUG: Floating test button - REMOVE after fixing -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    var btn = document.createElement('button');
    btn.textContent = '🔧 TEST MODAL';
    btn.style.cssText = 'position:fixed;bottom:20px;right:20px;z-index:99999;padding:15px 25px;background:red;color:white;border:none;border-radius:10px;font-size:16px;font-weight:bold;cursor:pointer;box-shadow:0 4px 20px rgba(0,0,0,0.3);';
    btn.onclick = function() {
        var modal = document.getElementById('modalClienteHabitual');
        var info = '';
        info += 'modalClienteHabitual: ' + (modal ? 'EXISTS' : 'NOT FOUND') + '\n';
        if (modal) {
            info += 'Current display: ' + modal.style.display + '\n';
            info += 'Parent: ' + modal.parentElement.tagName + '#' + (modal.parentElement.id || '') + '.' + (modal.parentElement.className || '').split(' ')[0] + '\n';
            modal.style.display = 'flex';
            var cs = window.getComputedStyle(modal);
            info += 'After flex - computed display: ' + cs.display + '\n';
            info += 'position: ' + cs.position + '\n';
            info += 'z-index: ' + cs.zIndex + '\n';
            info += 'opacity: ' + cs.opacity + '\n';
            info += 'visibility: ' + cs.visibility + '\n';
            info += 'width: ' + cs.width + '\n';
            info += 'height: ' + cs.height + '\n';
            info += 'top: ' + cs.top + '\n';
            info += 'left: ' + cs.left + '\n';
            var rect = modal.getBoundingClientRect();
            info += 'BoundingRect: ' + Math.round(rect.width) + 'x' + Math.round(rect.height) + ' at (' + Math.round(rect.left) + ',' + Math.round(rect.top) + ')\n';
        }
        info += '\nnuevoCliente: ' + (typeof nuevoCliente) + '\n';
        info += 'editarIva: ' + (typeof editarIva) + '\n';
        info += 'abrirModalNuevoIva: ' + (typeof abrirModalNuevoIva) + '\n';
        info += 'cerrarModal: ' + (typeof cerrarModal) + '\n';
        alert(info);
    };
    document.body.appendChild(btn);
});
</script>

<section id="cajero">
    <!-- Panel izquierdo: Navegación de Admin -->
    <div class="admin-sidebar-premium">
        <div class="admin-sidebar-header-premium">
            <div class="admin-logo-box">
                <i class="fas fa-shield-alt"></i>
            </div>
            <div>
                <h2 class="admin-sidebar-title">Admin Panel</h2>
                <p class="admin-sidebar-status">Sistema Activo</p>
            </div>
        </div>
        
        <div class="admin-nav-scroll">
            <div class="nav-section-label">Principal</div>
            <button class="nav-item-premium activa" data-seccion="dashboard">
                <i class="fas fa-chart-line"></i> <span>Dashboard</span>
            </button>
            <button class="nav-item-premium" data-seccion="caja-sesiones">
                <i class="fas fa-cash-register"></i> <span>Sesiones de Caja</span>
            </button>
            
            <div class="nav-section-label">Gestión</div>
            <button class="nav-item-premium" data-seccion="productos">
                <i class="fas fa-box"></i> <span>Productos</span>
            </button>
            <button class="nav-item-premium" data-seccion="categorias">
                <i class="fas fa-tags"></i> <span>Categorías</span>
            </button>
            <button class="nav-item-premium" data-seccion="usuarios">
                <i class="fas fa-users"></i> <span>Usuarios</span>
            </button>
            <button class="nav-item-premium" data-seccion="clientes">
                <i class="fas fa-user-friends"></i> <span>Clientes</span>
            </button>
            <button class="nav-item-premium" data-seccion="proveedores">
                <i class="fas fa-truck"></i> <span>Proveedores</span>
            </button>

            <div class="nav-section-label">Operaciones</div>
            <button class="nav-item-premium" data-seccion="ventas">
                <i class="fas fa-file-invoice-dollar"></i> <span>Ventas</span>
            </button>
            <button class="nav-item-premium" data-seccion="retiros">
                <i class="fas fa-money-bill-wave"></i> <span>Retiros</span>
            </button>
            <button class="nav-item-premium" data-seccion="devoluciones">
                <i class="fas fa-undo"></i> <span>Devoluciones</span>
            </button>

            <div class="nav-section-label">Configuración</div>
            <button class="nav-item-premium has-submenu" id="btnTarifas">
                <i class="fas fa-percent"></i> <span>Tarifas / IVA</span> <i class="fas fa-chevron-down arrow"></i>
            </button>
            <div id="submenuTarifas" class="nav-submenu-premium">
                <button class="submenu-item-premium" data-seccion="tarifa-iva">
                    <i class="fas fa-receipt"></i> Cambiar IVA
                </button>
                <button class="submenu-item-premium" data-seccion="tarifa-ajuste">
                    <i class="fas fa-sliders-h"></i> Ajuste Precios
                </button>
                <button class="submenu-item-premium" data-seccion="tarifa-prefijadas">
                    <i class="fas fa-list-ul"></i> Prefijadas
                </button>
            </div>

            <button class="nav-item-premium has-submenu" id="btnInformes">
                <i class="fas fa-chart-bar"></i> <span>Informes</span> <i class="fas fa-chevron-down arrow"></i>
            </button>
            <div id="submenuInformes" class="nav-submenu-premium">
                <button class="submenu-item-premium" data-seccion="informe-diario">Diario</button>
                <button class="submenu-item-premium" data-seccion="informe-semanal">Semanal</button>
                <button class="submenu-item-premium" data-seccion="informe-mensual">Mensual</button>
                <button class="submenu-item-premium" data-seccion="informe-anual">Anual</button>
            </div>

            <button class="nav-item-premium" data-seccion="envios-aeat">
                <i class="fas fa-satellite-dish"></i> <span>Envíos AEAT</span>
                <span id="badgePendientesAeat" class="nav-badge">0</span>
            </button>

            <button class="nav-item-premium has-submenu" id="btnConfig">
                <i class="fas fa-cog"></i> <span>Sistema</span> <i class="fas fa-chevron-down arrow"></i>
            </button>
            <div id="submenuConfig" class="nav-submenu-premium">
                <button class="submenu-item-premium" data-seccion="config-tema">Tema</button>
                <button class="submenu-item-premium" data-seccion="config-acciones">Acciones</button>
                <button class="submenu-item-premium" data-seccion="config-fiscal">Fiscal</button>
                <button class="submenu-item-premium" data-seccion="backups">Backups</button>
            </div>
            
            <button class="nav-item-premium" data-seccion="logs">
                <i class="fas fa-history"></i> <span>Logs</span>
            </button>

            <div class="nav-section-label">Sistema</div>
            <button class="nav-item-premium" onclick="abrirCentroTareas()">
                <i class="fas fa-tasks"></i> <span>Centro de Tareas</span>
                <span id="badgeTareasSide" class="nav-badge" style="display: none;">0</span>
            </button>
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
            <div class="flex-center-gap-15">
                <h2>Panel de Control</h2>
                <!-- Indicador Global de Tareas -->
                <div id="adminTaskIndicator" class="task-indicator admin-task-indicator-panel" style="display: none;" onclick="abrirCentroTareas()"
                    title="Ver tareas en curso">
                    <i class="fas fa-cog fa-spin accent-icon"></i>
                    <span id="taskCountText">1 tarea activa</span>
                </div>
            </div>
            <div class="indicador-efectivo <?php echo !$sesionCaja ? 'caja-cerrada' : ''; ?>" id="adminIndicadorCaja">
                <span class="label">Sistema:</span>
                <span class="amount status-text" id="adminEstadoSistema">
                    <?php echo $sesionCaja ? 'Online' : 'Offline'; ?>
                </span>
                <div class="separador"></div>
                <span class="label" id="adminEfectivoLabel">
                    <?php echo $sesionCaja ? 'Efectivo:' : 'Fondo:'; ?>
                </span>
                <span class="amount" id="adminEfectivoValor">
                    <?php echo number_format($stats['efectivoCaja'], 2, ',', '.'); ?> €
                </span>
            </div>
        </div>

        <div class="admin-stats-grid">
            <div class="admin-stat-card card-sales">
                <div class="card-icon"><i class="fas fa-euro-sign"></i></div>
                <div class="card-info">
                    <span class="admin-stat-label"><?php echo $tituloVentas; ?></span>
                    <span class="admin-stat-value"><?php echo number_format($stats['gananciasHoy'], 2, ',', '.'); ?> €</span>
                </div>
            </div>
            <div class="admin-stat-card card-orders">
                <div class="card-icon"><i class="fas fa-shopping-basket"></i></div>
                <div class="card-info">
                    <span class="admin-stat-label"><?php echo $tituloPedidos; ?></span>
                    <span class="admin-stat-value"><?php echo $stats['pedidosHoy']; ?></span>
                </div>
            </div>
            <div class="admin-stat-card card-products">
                <div class="card-icon"><i class="fas fa-box-open"></i></div>
                <div class="card-info">
                    <span class="admin-stat-label">Productos Activos</span>
                    <span class="admin-stat-value"><?php echo $stats['productos']; ?></span>
                </div>
            </div>
            <div class="admin-stat-card card-alerts">
                <div class="card-icon"><i class="fas fa-exclamation-triangle"></i></div>
                <div class="card-info">
                    <span class="admin-stat-label">Alertas Stock</span>
                    <span class="admin-stat-value"><?php echo $stats['alertasStock']; ?></span>
                </div>
            </div>
            <div class="admin-stat-card card-withdrawals">
                <div class="card-icon"><i class="fas fa-money-bill-wave"></i></div>
                <div class="card-info">
                    <span class="admin-stat-label"><?php echo $tituloRetiros; ?></span>
                    <span class="admin-stat-value">-<?php echo number_format($stats['retirosHoy'] ?? 0, 2, ',', '.'); ?> €</span>
                </div>
            </div>
            <div class="admin-stat-card card-returns">
                <div class="card-icon"><i class="fas fa-undo"></i></div>
                <div class="card-info">
                    <span class="admin-stat-label"><?php echo $tituloDevoluciones; ?></span>
                    <span class="admin-stat-value">-<?php echo number_format($stats['devolucionesHoy'] ?? 0, 2, ',', '.'); ?> €</span>
                </div>
            </div>
            <div class="admin-stat-card card-hours">
                <div class="card-icon"><i class="fas fa-clock"></i></div>
                <div class="card-info">
                    <span class="admin-stat-label">Horas (Semana)</span>
                    <span class="admin-stat-value"><?php echo number_format($stats['horasTrabajadasSemana'] ?? 0, 1, ',', '.'); ?> h</span>
                </div>
            </div>
        </div>

        <div class="admin-content-panel">
            <div id="adminContenido" class="contenido-admin">
                <i class="fas fa-info-circle info-icon-large"></i>
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

<!-- ##-----------------------------------MODAL VER CATEGORÁA-----------------------------------## -->

<div class="modal-overlay" style="display: none;" id="modalVerCategoria">
    <div class="modal-content modal-premium modal-premium-content-550">
        <!-- Header Premium -->
        <div class="modal-header-premium modal-header-blue-gradient">
            <div class="header-text-container">
                <h3 class="modal-header-title-white">Detalle de Categoría</h3>
                <p class="modal-subtitulo modal-header-subtitle-white">Información y productos asociados</p>
            </div>
            <button class="modal-close-btn modal-close-round-btn" onclick="cerrarModal('modalVerCategoria')">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="modal-body-padding-25">
            <!-- Info básica -->
            <div class="view-item-container-column">
                <div class="ver-prod-item-premium">
                    <label class="view-item-label-premium">ID de Categoría</label>
                    <div class="view-item-value-wrapper">
                        <i class="fas fa-hashtag view-icon-standard" style="color: #64748b;"></i>
                        <span id="verCategoriaId" class="view-text-standard"></span>
                    </div>
                </div>
                <div class="ver-prod-item-premium">
                    <label class="view-item-label-premium">Nombre</label>
                    <div class="view-item-value-wrapper">
                        <i class="fas fa-folder-open view-icon-standard" style="color: #3b82f6;"></i>
                        <span id="verCategoriaNombre" class="view-text-bold"></span>
                    </div>
                </div>
                <div class="ver-prod-item-premium">
                    <label class="view-item-label-premium">Descripción</label>
                    <div class="view-item-value-wrapper" style="align-items: flex-start;">
                        <i class="fas fa-align-left view-icon-standard mt-10" style="color: #8b5cf6;"></i>
                        <span id="verCategoriaDescripcion" class="view-text-standard"></span>
                    </div>
                </div>
            </div>

            <!-- Carrusel de productos debajo -->
            <div class="cat-prod-container container-bg-f8fafc-rounded">
                <div class="cat-prod-title flex-between-center-mb-15">
                    <span class="label-uppercase-600">Productos vinculados</span>
                    <span id="verCategoriaCantProdBadge" class="admin-badge badge-premium-blue">0</span>
                </div>

                <div class="cat-carousel-wrapper">
                    <div class="view-item-value-wrapper" style="gap: 5px;">
                        <button id="firstCatProd" class="cat-carousel-btn small" title="Primero"
                            onclick="cambiarProductoCarrusel('first')">
                            <i class="fas fa-angle-double-left"></i>
                        </button>
                        <button id="prevCatProd" class="cat-carousel-btn" title="Anterior"
                            onclick="cambiarProductoCarrusel(-1)">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                    </div>

                    <div id="verCategoriaListaProductos" class="cat-prod-card-wrapper">
                        <!-- Se carga un solo producto aquí -->
                        <div class="cat-prod-empty">Cargando...</div>
                    </div>

                    <div style="display: flex; gap: 5px;">
                        <button id="nextCatProd" class="cat-carousel-btn" title="Siguiente"
                            onclick="cambiarProductoCarrusel(1)">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                        <button id="lastCatProd" class="cat-carousel-btn small" title="Ášltimo"
                            onclick="cambiarProductoCarrusel('last')">
                            <i class="fas fa-angle-double-right"></i>
                        </button>
                    </div>
                </div>

                <div id="catCarouselDots" class="cat-carousel-info carousel-dots-info-centered">
                    <span>Producto</span>
                    <input type="number" id="catCarouselInput" class="cat-carousel-input" min="1"
                        onchange="saltarAProductoCarrusel(this.value)">
                    <span>de <span id="catCarouselTotal">0</span></span>
                </div>
            </div>

            <div class="modal-footer-border-top" style="margin-top: 20px;">
                <button class="btn-modal-cancelar" onclick="cerrarModal('modalVerCategoria')">
                    Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ##-----------------------------------MODAL VER PRODUCTO-----------------------------------## -->

<div class="modal-overlay" style="display: none;" id="modalVerProducto">
    <div class="modal-content modal-premium modal-premium-content-500">
        <!-- Header Premium -->
        <div class="modal-header-premium modal-header-blue-gradient">
            <div class="header-text-container">
                <h3 class="modal-header-title-white">Detalle del Producto</h3>
                <p class="modal-subtitulo modal-header-subtitle-white">Ficha técnica e información de inventario</p>
            </div>
            <button class="modal-close-btn modal-close-round-btn" onclick="cerrarModal('modalVerProducto')">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="modal-body-padding-25">
            <div class="ver-prod-layout-premium layout-gap-25-top">
                <!-- Imagen con efecto -->
                <div class="ver-prod-img-container img-container-fixed-140">
                    <img id="verProductoImagen" src="" alt="" class="img-standard-view">
                    <div id="verProductoBadgeEstado" class="badge-absolute-bottom-center">
                        <!-- Badge se inyecta por JS -->
                    </div>
                </div>

                <!-- Datos con iconos -->
                <div class="view-item-container-column flex-1">
                    <div class="ver-prod-item-premium">
                        <label class="view-item-label-premium">Nombre del Producto</label>
                        <div class="view-item-value-wrapper">
                            <i class="fas fa-tag view-icon-standard" style="color: #3b82f6;"></i>
                            <span id="verProductoNombre" class="view-text-bold"></span>
                        </div>
                    </div>

                    <div class="ver-prod-item-premium">
                        <label class="view-item-label-premium">Categoría</label>
                        <div class="view-item-value-wrapper">
                            <i class="fas fa-folder view-icon-standard" style="color: #6366f1;"></i>
                            <span id="verProductoCategoria" class="view-text-standard"></span>
                        </div>
                    </div>

                    <div class="view-item-value-wrapper" style="gap: 15px;">
                        <div class="ver-prod-item-premium flex-1">
                            <label class="view-item-label-premium">Stock</label>
                            <div class="view-item-value-wrapper">
                                <i class="fas fa-cubes view-icon-standard" style="color: #f59e0b;"></i>
                                <span id="verProductoStock" class="view-text-bold" style="font-size: 1rem;"></span>
                            </div>
                        </div>
                        <div class="ver-prod-item-premium flex-1">
                            <label class="view-item-label-premium">IVA</label>
                            <div class="view-item-value-wrapper">
                                <i class="fas fa-percentage view-icon-standard" style="color: #10b981;"></i>
                                <span id="verProductoIva" class="view-text-standard"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sección de Precio Destacada -->
            <div class="price-box-highlight">
                <div>
                    <span class="price-label-muted">Precio de Venta</span>
                    <span id="verProductoPrecioLabel" class="label-price-muted">Base imponible</span>
                </div>
                <div class="text-right">
                    <span id="verProductoPrecio" class="price-value-large"></span>
                </div>
            </div>

            <div class="modal-footer-border-top" style="margin-top: 20px;">
                <button class="btn-modal-cancelar" onclick="cerrarModal('modalVerProducto')">
                    Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ##-----------------------------------MODAL EDITAR PRODUCTO-----------------------------------## -->

<div class="modal-overlay" style="display: none;" id="modalEditarProducto">
    <div class="modal-content modal-premium modal-premium-content-600">
        <!-- Header Premium -->
        <div class="modal-header-premium modal-header-green-gradient">
            <div class="header-text-container">
                <h3 id="editProductoTitulo" class="modal-header-title-white">Editar Producto</h3>
                <p id="editProductoSubtitulo" class="modal-subtitulo modal-header-subtitle-white">Modifica los datos del producto</p>
            </div>
            <button class="modal-close-btn modal-close-round-btn" onclick="cerrarModal('modalEditarProducto')">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <input type="hidden" id="editProductoId">

        <div class="modal-body-padding-25">
            <div class="editar-prod-layout-premium layout-gap-25-top">

                <!-- Columna Izquierda: Imagen -->
                <div class="editar-prod-imagen-wrapper edit-img-wrapper">
                    <div class="edit-img-container">
                        <img id="editProductoImagen" src="" alt="" class="edit-img-standard"
                            onclick="abrirImagenGrande(this.src, this.alt)">
                    </div>
                    <label class="btn-cambiar-imagen btn-upload-photo" title="Cambiar imagen">
                        <i class="fas fa-camera"></i> Subir foto
                        <input type="file" id="editProductoImagenInput" accept="image/*" style="display: none;"
                            onchange="previsualizarImagen(event)">
                    </label>
                </div>

                <!-- Columna Derecha: Formulario -->
                <div class="editar-prod-campos grid-edit-2-cols">
                    <div class="editar-prod-fila-premium col-span-2">
                        <label class="view-item-label-premium" style="font-size: 0.8rem; color: #4b5563; margin-bottom: 5px;">Nombre
                            <span style="color:#ef4444">*</span></label>
                        <input type="text" id="editProductoNombre" class="input-edit-standard"
                            placeholder="Ej: Café con Leche">
                    </div>

                    <div class="editar-prod-fila-premium col-span-2">
                        <label class="view-item-label-premium" style="font-size: 0.8rem; color: #4b5563; margin-bottom: 5px;">Categoría
                            <span style="color:#ef4444">*</span></label>
                        <select id="editProductoCategoria" class="input-edit-standard">
                        </select>
                    </div>

                    <div class="editar-prod-fila-premium">
                        <label class="view-item-label-premium" style="font-size: 0.8rem; color: #4b5563; margin-bottom: 5px;">Precio
                            Base (€) <span style="color:#ef4444">*</span></label>
                        <input type="number" id="editProductoPrecio" step="0.0001" min="0"
                            oninput="validarPrecisionDinamica(this, 'editProductoDecimales')"
                            onblur="validarPrecisionDinamica(this, 'editProductoDecimales')"
                            class="input-edit-standard">
                    </div>

                    <div class="editar-prod-fila-premium">
                        <label class="view-item-label-premium" style="font-size: 0.8rem; color: #4b5563; margin-bottom: 5px;">Stock
                            <span style="color:#ef4444">*</span></label>
                        <input type="number" id="editProductoStock" min="0" class="input-edit-standard">
                    </div>

                    <div class="editar-prod-fila-premium">
                        <label class="view-item-label-premium" style="font-size: 0.8rem; color: #4b5563; margin-bottom: 5px;">Tipo
                            de IVA</label>
                        <select id="editProductoIva" class="input-edit-standard">
                        </select>
                    </div>

                    <div class="editar-prod-fila-premium">
                        <label class="view-item-label-premium" style="font-size: 0.8rem; color: #4b5563; margin-bottom: 5px;">Estado</label>
                        <select id="editProductoEstado" class="input-edit-standard">
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>

                    <div class="editar-prod-fila-premium col-span-2">
                        <label class="view-item-label-premium" style="font-size: 0.8rem; color: #4b5563; margin-bottom: 5px;">Decimales
                            permitidos (máx 4)</label>
                        <input type="number" id="editProductoDecimales" min="0" max="4" step="1" value="2"
                            oninput="validarDecimalesRango(this, 'editProductoPrecio')"
                            class="input-edit-standard">
                    </div>
                </div>
            </div>

            <div class="modal-footer-border-top">
                <button class="btn-modal-cancelar" onclick="cerrarModal('modalEditarProducto')">
                    Cancelar
                </button>
                <button class="btn-exito btn-footer-save-green" onclick="guardarCambiosProducto()">
                    <i class="fas fa-save"></i> Guardar Cambios
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ##-----------------------------------MODAL VER USUARIO-----------------------------------## -->

<div class="modal-overlay" style="display: none;" id="modalVerUsuario">
    <div class="modal-content modal-premium modal-premium-content-600">
        <!-- Header Premium -->
        <div class="modal-header-premium modal-header-blue-gradient">
            <div class="header-text-container">
                <h3 class="modal-header-title-white">Detalle del Usuario</h3>
                <p class="modal-subtitulo modal-header-subtitle-white">Información completa y permisos</p>
            </div>
            <button class="modal-close-btn modal-close-round-btn" onclick="cerrarModal('modalVerUsuario')">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="modal-body-padding-25 overflow-y-auto" style="max-height: 75vh;">

            <div class="grid-edit-2-cols mb-25 grid-responsive-auto-fit">
                <div class="ver-prod-item-premium">
                    <label class="view-item-label-premium">Nombre</label>
                    <div class="view-item-value-wrapper">
                        <i class="fas fa-user view-icon-standard view-icon-blue"></i>
                        <span id="verUsuarioNombre" class="view-text-bold view-text-large"></span>
                    </div>
                </div>
                <div class="ver-prod-item-premium">
                    <label class="view-item-label-premium">Email</label>
                    <div class="view-item-value-wrapper">
                        <i class="fas fa-envelope view-icon-standard" style="color: #8b5cf6;"></i>
                        <span id="verUsuarioEmail" class="view-text-standard"></span>
                    </div>
                </div>
                <div class="ver-prod-item-premium">
                    <label class="view-item-label-premium">Rol</label>
                    <div class="view-item-value-wrapper">
                        <i class="fas fa-user-shield view-icon-standard" style="color: #f59e0b;"></i>
                        <span id="verUsuarioRol" class="view-text-standard" style="text-transform: capitalize;"></span>
                    </div>
                </div>
                <div class="ver-prod-item-premium">
                    <label class="view-item-label-premium">Fecha de Alta</label>
                    <div class="view-item-value-wrapper">
                        <i class="fas fa-calendar-alt view-icon-standard" style="color: #10b981;"></i>
                        <span id="verUsuarioFecha" class="view-text-standard"></span>
                    </div>
                </div>
                <div class="ver-prod-item-premium">
                    <label class="view-item-label-premium">Estado</label>
                    <div class="view-item-value-wrapper">
                        <span id="verUsuarioEstado" class="view-text-standard" style="font-weight: 600;"></span>
                    </div>
                </div>
            </div>

            <!-- Stats -->
            <h4 class="label-uppercase-600 mb-15" style="border-bottom: 1px solid #e5e7eb; padding-bottom: 5px;">
                <i class="fas fa-chart-line" style="margin-right: 5px; color: #6366f1;"></i> Estadísticas
            </h4>
            <div class="grid-stats-2-cols mb-25">
                <div class="ver-prod-item-premium"
                    style="margin: 0; padding: 0; background: transparent; border: none;">
                    <label class="view-item-label-premium">Total Descansos</label>
                    <div class="view-item-value-wrapper">
                        <i class="fas fa-coffee view-icon-standard" style="color: #d97706;"></i>
                        <span id="verUsuarioTotalDescansos" class="view-text-bold"></span>
                    </div>
                </div>
                <div class="ver-prod-item-premium"
                    style="margin: 0; padding: 0; background: transparent; border: none;">
                    <label class="view-item-label-premium">Total Cambios Turno</label>
                    <div class="view-item-value-wrapper">
                        <i class="fas fa-exchange-alt view-icon-standard" style="color: #059669;"></i>
                        <span id="verUsuarioTotalTurnos" class="view-text-bold"></span>
                    </div>
                </div>
            </div>

            <!-- Permissions -->
            <h4 class="label-uppercase-600 mb-15" style="border-bottom: 1px solid #e5e7eb; padding-bottom: 5px;">
                <i class="fas fa-key" style="margin-right: 5px; color: #ef4444;"></i> Permisos Especiales
            </h4>
            <div class="grid-edit-2-cols" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));">
                <div class="ver-prod-item-premium">
                    <label class="view-item-label-premium">Crear Productos</label>
                    <div class="view-item-value-wrapper">
                        <span id="verUsuarioCrearProductos" class="view-text-standard" style="font-weight: 600;"></span>
                    </div>
                </div>
                <div class="ver-prod-item-premium">
                    <label class="view-item-label-premium">Producto Comodín</label>
                    <div class="view-item-value-wrapper">
                        <span id="verUsuarioProductoComodin" class="view-text-standard" style="font-weight: 600;"></span>
                    </div>
                </div>
                <div class="ver-prod-item-premium">
                    <label class="view-item-label-premium">Retirar Dinero Caja</label>
                    <div class="view-item-value-wrapper">
                        <span id="verUsuarioRetirarDinero" class="view-text-standard" style="font-weight: 600;"></span>
                    </div>
                </div>
            </div>

            <div class="modal-footer-border-top" style="margin-top: 25px; padding-top: 15px;">
                <button class="btn-modal-cancelar" onclick="cerrarModal('modalVerUsuario')">
                    Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ##-----------------------------------MODAL VER DEVOLUCION-----------------------------------## -->

<!-- ##-----------------------------------MODAL VER DEVOLUCION-----------------------------------## -->
<div class="modal-overlay modal-backdrop-blur" style="display: none;" id="modalVerDevolucion">
    <div class="modal-content modal-premium modal-premium-content-500">
        <!-- Header Premium -->
        <div class="modal-header-premium modal-header-red-gradient">
            <div class="flex-center-gap-15">
                <div class="modal-icon-box-premium">
                    <i class="fas fa-undo-alt view-icon-white view-icon-large"></i>
                </div>
                <div>
                    <h3 class="modal-header-title-white" style="font-weight: 700; letter-spacing: -0.5px;">Detalle de Devolución</h3>
                    <p class="modal-subtitulo modal-header-subtitle-white" style="color: rgba(255,255,255,0.85); font-size: 0.9rem;">Vista previa del comprobante rectificativo</p>
                </div>
            </div>
            <button class="modal-close-btn modal-close-btn-premium" onclick="cerrarModal('modalVerDevolucion')">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div id="ticketDevolucionContainer" class="ticket-preview-container">
            <!-- El ticket se generará aquí con generarHTMLComprobante -->
            <div class="loading-placeholder-centered">
                <i class="fas fa-spinner fa-spin loading-icon-red"></i>
                <p style="font-weight: 500;">Generando vista previa del ticket...</p>
            </div>
        </div>

        <div class="modal-footer-plain modal-footer-panel-bg">
            <button class="btn-modal-cancelar btn-modal-footer-cancel" onclick="cerrarModal('modalVerDevolucion')">
                <i class="fas fa-times" style="margin-right: 8px;"></i> Cerrar
            </button>
            <button class="btn-exito btn-footer-print" onclick="verTicketDevolucion()">
                <i class="fas fa-print"></i> Re-imprimir Ticket
            </button>
        </div>
    </div>
</div>

<!-- ##-----------------------------------MODAL EDITAR/CREAR USUARIO-----------------------------------## -->

<div class="modal-overlay" style="display: none;" id="modalEditarUsuario">
    <div class="modal-content modal-premium modal-premium-content-650">
        <!-- Header Premium -->
        <div class="modal-header-premium modal-header-green-gradient">
            <div class="header-text-container">
                <h3 id="editUsuarioTitulo" class="modal-header-title-white">Editar Usuario</h3>
                <p class="modal-subtitulo modal-header-subtitle-white">Modifica los datos del usuario</p>
            </div>
            <button class="modal-close-btn modal-close-round-btn" onclick="cerrarModal('modalEditarUsuario')">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="modal-body-padding-25">
            <input type="hidden" id="editUsuarioId">

            <div class="editar-prod-campos grid-responsive-auto-fit" style="gap: 20px; margin-bottom: 25px;">
                <!-- Columna Izquierda -->
                <div class="flex-column-gap-12" style="gap: 15px;">
                    <div class="editar-prod-fila-premium">
                        <label class="view-item-label-premium" style="font-size: 0.8rem; color: #4b5563; margin-bottom: 5px;">Nombre
                            <span style="color:#ef4444">*</span></label>
                        <input type="text" id="editUsuarioNombre" required class="input-edit-standard">
                    </div>
                    <div class="editar-prod-fila-premium">
                        <label class="view-item-label-premium" style="font-size: 0.8rem; color: #4b5563; margin-bottom: 5px;">Password
                            <span style="color:#ef4444">*</span></label>
                        <input type="password" id="editUsuarioPassword" class="input-edit-standard">
                    </div>
                    <div class="editar-prod-fila-premium">
                        <label class="view-item-label-premium" style="font-size: 0.8rem; color: #4b5563; margin-bottom: 5px;">Rol</label>
                        <select id="editUsuarioRol" class="input-edit-standard">
                            <option value="empleado">Empleado</option>
                            <option value="admin">Administrador</option>
                        </select>
                    </div>
                </div>

                <!-- Columna Derecha -->
                <div class="flex-column-gap-12" style="gap: 15px;">
                    <div class="editar-prod-fila-premium">
                        <label class="view-item-label-premium" style="font-size: 0.8rem; color: #4b5563; margin-bottom: 5px;">Email
                            <span style="color:#ef4444">*</span></label>
                        <input type="email" id="editUsuarioEmail" required class="input-edit-standard">
                    </div>
                    <div class="editar-prod-fila-premium">
                        <label class="view-item-label-premium" style="font-size: 0.8rem; color: #4b5563; margin-bottom: 5px;">Estado</label>
                        <select id="editUsuarioEstado" class="input-edit-standard">
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Fila Permisos (Ocupa todo el ancho) -->
            <div class="editar-prod-fila-premium container-bg-f8fafc-rounded mb-20" id="filaPermisos" style="display: none;">
                <label class="label-uppercase-600 mb-10">Permisos Adicionales</label>
                <div class="grid-edit-2-cols" style="gap: 10px;">
                    <label class="checkbox-label-standard">
                        <input type="checkbox" id="editUsuarioPermisoCrearProductos" value="crear_productos" class="input-checkbox-standard">
                        Permitir crear productos
                    </label>
                    <label class="checkbox-label-standard">
                        <input type="checkbox" id="editUsuarioPermisoModificarPrecios" value="modificar_precios" class="input-checkbox-standard">
                        Permitir modificar precios
                    </label>
                    <label class="checkbox-label-standard">
                        <input type="checkbox" id="editUsuarioPermisoProductoComodin" value="producto_comodin" class="input-checkbox-standard">
                        Usar Producto Comodín
                    </label>
                    <label class="checkbox-label-standard">
                        <input type="checkbox" id="editUsuarioPermisoRetirarDinero" value="retirar_dinero" class="input-checkbox-standard">
                        Retirar Dinero de Caja
                    </label>
                </div>
                <p class="modal-header-subtitle-white" style="margin-top: 10px; font-style: italic; color: #6b7280;">El empleado podrá
                    acceder a estas funciones desde su vista de cajero.</p>
            </div>

            <div class="modal-footer-border-top" style="margin-top: 10px;">
                <button class="btn-modal-cancelar" onclick="cerrarModal('modalEditarUsuario')">
                    Cancelar
                </button>
                <button class="btn-exito btn-footer-save-green" onclick="guardarCambiosUsuario()">
                    <i class="fas fa-save"></i> Guardar Cambios
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ##-----------------------------------MODAL VER PROVEEDOR-----------------------------------## -->

<div class="modal-overlay" style="display: none;" id="modalVerProveedor">
    <div class="modal-content modal-premium modal-premium-content-1000">
        <div class="modal-header-premium modal-header-blue-gradient">
            <div class="header-text-container">
                <h3 class="modal-header-title-white">Detalle del Proveedor</h3>
                <p class="modal-subtitulo modal-header-subtitle-white">Información completa</p>
            </div>
            <button class="modal-close-btn modal-close-round-btn" onclick="cerrarModal('modalVerProveedor')">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="modal-body-padding-25">
            <div class="view-item-container-column mb-20">
                <div class="ver-prod-fila flex-between-center-mb-15">
                    <span class="view-item-label-premium">Nombre</span>
                    <span id="verProveedorNombre" class="view-text-bold"></span>
                </div>
                <div class="ver-prod-fila flex-between-center-mb-15">
                    <span class="view-item-label-premium">Contacto</span>
                    <span id="verProveedorContacto" class="view-text-standard"></span>
                </div>
                <div class="ver-prod-fila flex-between-center-mb-15">
                    <span class="view-item-label-premium">Email</span>
                    <span id="verProveedorEmail" class="view-text-standard"></span>
                </div>
                <div class="ver-prod-fila flex-between-center-mb-15">
                    <span class="view-item-label-premium">Dirección</span>
                    <span id="verProveedorDireccion" class="view-text-standard"></span>
                </div>
                <div class="ver-prod-fila flex-between-center-mb-15">
                    <span class="view-item-label-premium">Estado</span>
                    <span id="verProveedorEstado" class="view-text-standard"></span>
                </div>
            </div>

            <div class="modal-footer-border-top" style="margin-top: 15px; padding-top: 15px;">
                <div class="flex-between-center-mb-15 w-full">
                    <h4 class="label-uppercase-600 p-0">Productos Suministrados</h4>
                    <button class="btn-admin-accion btn-nuevo" onclick="agregarProductoProveedor()">
                        <i class="fas fa-plus"></i> Añadir Producto
                    </button>
                </div>
            </div>

            <div class="overflow-y-auto" style="max-height: 400px; border: 1px solid #e5e7eb; border-radius: 4px;">
                <table class="admin-tabla" id="tablaProductosProveedor" style="font-size: 0.85rem; margin-bottom: 0;">
                    <thead class="table-header-sticky">
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
            <p id="msgSinProductosProveedor" class="sin-productos p-25" style="display: none;">Este
                proveedor no tiene productos asignados.</p>
        </div>

        <div class="modal-footer-plain">
            <button class="btn-modal-cancelar" onclick="cerrarModal('modalVerProveedor')">
                Cerrar
            </button>
        </div>
    </div>
</div>

<!-- ##-----------------------------------MODAL ASOCIAR PRODUCTO PROVEEDOR-----------------------------------## -->

<div class="modal-overlay" style="display: none;" id="modalAsociarProducto" style="z-index: 9999;">
    <div class="modal-content modal-premium modal-premium-content-450">
        <div class="modal-header-premium modal-header-purple-gradient">
            <h3 id="asociarProductoTitulo" class="modal-header-title-white">Asociar Producto</h3>
            <p class="modal-subtitulo modal-header-subtitle-white" id="asociarProductoSubtitulo">Selecciona un producto y fija su recargo</p>
            <button class="modal-close-btn modal-close-round-btn" onclick="cerrarModal('modalAsociarProducto'); abrirModal('modalVerProveedor')">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="modal-body-padding-25">
            <input type="hidden" id="asociarProvIdAsociacion">
            <input type="hidden" id="asociarProvIdProveedor">

            <div class="editar-prod-campos flex-column-gap-12">
            <div class="editar-prod-fila-premium" id="contenedorSelectProducto">
                <label class="view-item-label-premium">Producto <span style="color:red">*</span></label>
                <select id="asociarProvIdProducto" class="input-edit-standard">
                    <!-- Rellenado con Javascript -->
                </select>
            </div>

            <div class="editar-prod-fila-premium" style="display: none;" id="contenedorTextoProducto">
                <label class="view-item-label-premium">Producto</label>
                <input type="text" id="asociarProvNombreProducto" readonly class="input-edit-standard"
                    style="background-color: #f3f4f6; color: #6b7280;">
            </div>

            <div class="editar-prod-fila-premium">
                <label class="view-item-label-premium">Precio Proveedor (€) <span style="color:red">*</span></label>
                <input type="number" id="asociarProvPrecio" step="0.0001" min="0" value="0.00"
                    oninput="validar4Decimales(this)" onblur="validar4Decimales(this)" required class="input-edit-standard">
            </div>

            <div class="editar-prod-fila-premium">
                <label class="view-item-label-premium">Recargo Equivalencia (%) <span style="color:red">*</span></label>
                <input type="number" id="asociarProvRecargo" step="0.0001" min="0" value="0.00"
                    oninput="validar4Decimales(this)" onblur="validar4Decimales(this)" required class="input-edit-standard">
            </div>
        </div>

        <div class="modal-footer-border-top">
            <button class="btn-modal-cancelar"
                onclick="cerrarModal('modalAsociarProducto'); abrirModal('modalVerProveedor')">Cancelar</button>
            <button class="btn-exito btn-footer-save-purple" onclick="guardarCambiosAsociarProducto()">
                <i class="fas fa-save"></i> Guardar
            </button>
        </div>
    </div>
</div>
</div>

<!-- ##-----------------------------------MODAL EDITAR/CREAR PROVEEDOR-----------------------------------## -->

<div class="modal-overlay" style="display: none;" id="modalEditarProveedor">
    <div class="modal-content modal-premium modal-premium-content-500">
        <div class="modal-header-premium modal-header-green-gradient">
            <h3 id="editProveedorTitulo" class="modal-header-title-white">Editar Proveedor</h3>
            <p class="modal-subtitulo modal-header-subtitle-white">Modifica los datos del proveedor</p>
            <button class="modal-close-btn modal-close-round-btn" onclick="cerrarModal('modalEditarProveedor')">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="modal-body-padding-25">
            <input type="hidden" id="editProveedorId">
            <div class="editar-prod-campos flex-column-gap-12">
            <div class="editar-prod-fila-premium">
                <label class="view-item-label-premium">Nombre <span style="color:red">*</span></label>
                <input type="text" id="editProveedorNombre" required class="input-edit-standard">
            </div>
            <div class="editar-prod-fila-premium">
                <label class="view-item-label-premium">Contacto (Teléfono)</label>
                <input type="text" id="editProveedorContacto" class="input-edit-standard">
            </div>
            <div class="editar-prod-fila-premium">
                <label class="view-item-label-premium">Email</label>
                <input type="email" id="editProveedorEmail" class="input-edit-standard">
            </div>
            <div class="editar-prod-fila-premium">
                <label class="view-item-label-premium">Dirección</label>
                <input type="text" id="editProveedorDireccion" class="input-edit-standard">
            </div>
            <div class="editar-prod-fila-premium">
                <label class="view-item-label-premium">Estado</label>
                <select id="editProveedorEstado" class="input-edit-standard">
                    <option value="1">Activo</option>
                    <option value="0">Inactivo</option>
                </select>
            </div>
        </div>

        <div class="modal-footer-border-top">
            <button class="btn-modal-cancelar" onclick="cerrarModal('modalEditarProveedor')">Cancelar</button>
            <button class="btn-exito btn-footer-save-green" onclick="guardarCambiosProveedor()">
                <i class="fas fa-save"></i> Guardar Cambios
            </button>
        </div>
    </div>
</div>
</div>

<!-- ##=========================== MODAL: NUEVO CLIENTE (ADMIN) ===========================## -->
<!-- Modal para añadir un cliente habitual (DNI, nombre, apellidos, fecha alta) -->
<div class="modal-overlay" style="display: none;" id="modalClienteHabitual">
    <div class="modal-content modal-premium modal-premium-content-600">
        <!-- Header Premium -->
        <div class="modal-header-premium modal-header-green-gradient">
            <div class="header-text-container">
                <h3 class="modal-header-title-white">Nuevo Cliente</h3>
                <p class="modal-subtitulo modal-header-subtitle-white">Complete los datos del cliente</p>
            </div>
            <button class="modal-close-btn modal-close-round-btn" onclick="cerrarModal('modalClienteHabitual')">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="modal-body-padding-25">
            <div class="editar-prod-campos grid-responsive-auto-fit" style="gap: 20px;">
                <div class="editar-prod-fila-premium">
                    <label class="view-item-label-premium">DNI <span style="color:#ef4444">*</span></label>
                    <input type="text" id="clienteHabitualDni" placeholder="12345678A" maxlength="20" class="input-edit-standard">
                </div>
                <div class="editar-prod-fila-premium">
                    <label class="view-item-label-premium">Nombre <span style="color:#ef4444">*</span></label>
                    <input type="text" id="clienteHabitualNombre" placeholder="Juan" maxlength="100" class="input-edit-standard">
                </div>
                <div class="editar-prod-fila-premium">
                    <label class="view-item-label-premium">Apellidos <span style="color:#ef4444">*</span></label>
                    <input type="text" id="clienteHabitualApellidos" placeholder="García López" maxlength="150" class="input-edit-standard">
                </div>
                <div class="editar-prod-fila-premium">
                    <label class="view-item-label-premium">Dirección</label>
                    <input type="text" id="clienteHabitualDireccion" placeholder="Calle, Número, Ciudad" maxlength="255" class="input-edit-standard">
                </div>
                <div class="editar-prod-fila-premium">
                    <label class="view-item-label-premium">Fecha de Alta</label>
                    <input type="datetime-local" id="clienteHabitualFecha" readonly class="input-edit-standard"
                        style="background-color: #f3f4f6; color: #6b7280;">
                </div>
            </div>

            <!-- Botones: Cancelar y Guardar -->
            <div class="modal-footer-border-top" style="margin-top: 30px;">
                <button class="btn-modal-cancelar" onclick="cerrarModal('modalClienteHabitual')">Cancelar</button>
                <button class="btn-exito btn-footer-save-green" id="btnGuardarClienteHabitual" onclick="guardarClienteHabitualAdmin()">
                    <i class="fas fa-save"></i> Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ##=========================== MODAL: EDITAR CLIENTE (ADMIN) ===========================## -->
<div class="modal-overlay" style="display: none;" id="modalEditarCliente">
    <div class="modal-content modal-premium modal-premium-content-600">
        <!-- Header Premium -->
        <div class="modal-header-premium modal-header-green-gradient">
            <div class="header-text-container">
                <h3 class="modal-header-title-white">Editar Cliente</h3>
                <p class="modal-subtitulo modal-header-subtitle-white">Modifique los datos del cliente</p>
            </div>
            <button class="modal-close-btn modal-close-round-btn" onclick="cerrarModal('modalEditarCliente')">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="modal-body-padding-25">
            <input type="hidden" id="editarClienteId">
            <div class="editar-prod-campos grid-responsive-auto-fit" style="gap: 20px;">
                <div class="editar-prod-fila-premium">
                    <label class="view-item-label-premium">DNI <span style="color:#ef4444">*</span></label>
                    <input type="text" id="editarClienteDni" placeholder="12345678A" maxlength="20" class="input-edit-standard">
                </div>
                <div class="editar-prod-fila-premium">
                    <label class="view-item-label-premium">Nombre <span style="color:#ef4444">*</span></label>
                    <input type="text" id="editarClienteNombre" placeholder="Juan" maxlength="100" class="input-edit-standard">
                </div>
                <div class="editar-prod-fila-premium">
                    <label class="view-item-label-premium">Apellidos <span style="color:#ef4444">*</span></label>
                    <input type="text" id="editarClienteApellidos" placeholder="García López" maxlength="150" class="input-edit-standard">
                </div>
                <div class="editar-prod-fila-premium">
                    <label class="view-item-label-premium">Dirección</label>
                    <input type="text" id="editarClienteDireccion" placeholder="Calle, Número, Ciudad" maxlength="255" class="input-edit-standard">
                </div>
                <div class="editar-prod-fila-premium">
                    <label class="view-item-label-premium">Puntos</label>
                    <input type="number" id="editarClientePuntos" placeholder="0" min="0"
                        onchange="this.value = Math.max(0, this.value);" class="input-edit-standard">
                </div>
            </div>

            <!-- Botones: Cancelar y Guardar -->
            <div class="modal-footer-border-top" style="margin-top: 30px;">
                <button class="btn-modal-cancelar" onclick="cerrarModal('modalEditarCliente')">Cancelar</button>
                <button class="btn-exito btn-footer-save-green" id="btnGuardarClienteEditado" onclick="guardarClienteEditado()">
                    <i class="fas fa-save"></i> Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ##-----------------------------------MODAL EDITAR/CREAR TIPO DE IVA-----------------------------------## -->

<div class="modal-overlay" style="display: none;" id="modalEditarIva">
    <div class="modal-content modal-premium modal-premium-content-450">
        <!-- Header Premium -->
        <div class="modal-header-premium modal-header-purple-gradient">
            <div class="header-text-container">
                <h3 id="editIvaTitulo" class="modal-header-title-white">Nuevo Tipo de IVA</h3>
                <p id="editIvaSubtitulo" class="modal-subtitulo modal-header-subtitle-white">Configura el porcentaje del IVA</p>
            </div>
            <button class="modal-close-btn modal-close-btn-white-20" onclick="cerrarModal('modalEditarIva')">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="modal-body-padding-25">
            <input type="hidden" id="editIvaId">

            <div style="display: flex; flex-direction: column; gap: 20px;">
                <div class="ver-prod-item-premium">
                    <label class="view-item-label-premium">Nombre <span style="color:#ef4444">*</span></label>
                    <div class="flex-center-gap-10">
                        <i class="fas fa-tag view-icon-purple" style="width: 16px;"></i>
                        <input type="text" id="editIvaNombre" placeholder="Ej: IVA Reducido" class="input-edit-standard">
                    </div>
                </div>

                <div class="ver-prod-item-premium">
                    <label class="view-item-label-premium">Porcentaje (%) <span style="color:#ef4444">*</span></label>
                    <div class="flex-center-gap-10">
                        <i class="fas fa-percent" style="color: #f59e0b; width: 16px;"></i>
                        <input type="number" id="editIvaPorcentaje" step="0.01" min="0" max="100" placeholder="Ej: 10" class="input-edit-standard">
                    </div>
                </div>
            </div>

            <div class="modal-footer-border-top" style="margin-top: 30px;">
                <button class="btn-modal-cancelar" onclick="cerrarModal('modalEditarIva')">
                    Cancelar
                </button>
                <button class="btn-exito btn-footer-save-purple" onclick="guardarIva()">
                    <i class="fas fa-save"></i> Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ##=========================== MODAL: PROGRAMAR CAMBIO DE IVA ===========================## -->
<div class="modal-overlay" style="display: none;" id="modalProgramarIVA">
    <div class="modal-content modal-premium modal-premium-content-480">
        <!-- Header Premium -->
        <div class="modal-header-premium modal-header-purple-gradient">
            <h3 class="modal-header-title-white">
                <i class="fas fa-clock" style="margin-right: 10px;"></i>Programar Cambio de IVA
            </h3>
            <p class="modal-subtitulo modal-header-subtitle-white">
                El cambio se aplicará en la fecha y hora seleccionada
            </p>
            <button class="modal-close-btn modal-close-btn-white-20" onclick="cerrarModal('modalProgramarIVA')">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="modal-body-padding-25">
            <input type="hidden" id="ivaProgramado" value="">

            <div style="display: flex; flex-direction: column; gap: 20px;">
                <!-- IVA a aplicar -->
                <div class="ver-prod-item-premium">
                    <label class="view-item-label-premium">IVA a aplicar <span style="color:#ef4444">*</span></label>
                    <div class="flex-center-gap-10">
                        <i class="fas fa-percent" style="color: #8b5cf6; width: 16px;"></i>
                        <div id="ivaProgramadoNombre" class="input-edit-standard container-bg-f3f4f6-rounded" style="font-weight: 600;">
                        </div>
                    </div>
                </div>

                <!-- Fecha y hora programada -->
                <div class="ver-prod-item-premium">
                    <label for="fechaProgramada" class="view-item-label-premium">Fecha y hora programada <span style="color:#ef4444">*</span></label>
                    <div class="flex-center-gap-10">
                        <i class="fas fa-calendar-alt" style="color: #f59e0b; width: 16px;"></i>
                        <input type="datetime-local" id="fechaProgramada" class="input-edit-standard">
                    </div>
                </div>
            </div>

            <!-- Mensaje informativo -->
            <div class="info-box-premium info-box-indigo">
                <i class="fas fa-info-circle" style="font-size: 1rem; margin-top: 1px;"></i>
                <p class="view-text-small" style="margin: 0; line-height: 1.4;">
                    El sistema verificará los cambios programados al acceder a esta sección. También puede gestionarlos
                    desde <strong>Configuración → Acciones</strong>.
                </p>
            </div>

            <!-- Botones -->
            <div class="modal-footer-border-top" style="margin-top: 25px;">
                <button class="btn-modal-cancelar" onclick="cerrarModal('modalProgramarIVA')">
                    Cancelar
                </button>
                <button class="btn-exito btn-footer-save-purple" onclick="programarCambioIVA()">
                    <i class="fas fa-clock"></i> Programar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ##=========================== MODAL: VER CAMBIOS PROGRAMADOS DE IVA ===========================## -->
<div class="modal-overlay" style="display: none;" id="modalVerCambiosProgramadosIVA">
    <div class="modal-content modal-premium modal-premium-content-850">
        <!-- Header Premium -->
        <div class="modal-header-premium modal-header-purple-gradient" style="padding: 25px 30px;">
            <div class="flex-center-gap-15">
                <div class="modal-icon-box-white-20">
                    <i class="fas fa-history view-icon-white view-icon-large"></i>
                </div>
                <div>
                    <h3 class="modal-header-title-white" style="font-size: 1.4rem;">
                        IVA Programado
                    </h3>
                    <p class="modal-subtitulo modal-header-subtitle-white" style="font-size: 0.95rem;">
                        Historial y próximos cambios de IVA masivos
                    </p>
                </div>
            </div>
            <button class="modal-close-btn modal-close-btn-white-15" onclick="cerrarModal('modalVerCambiosProgramadosIVA')">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Body -->
        <div class="modal-body-padding-25-30" style="background: #f8fafc;">
            <div id="listaCambiosProgramadosIVA" class="list-container-premium">
                <!-- La tabla se cargará dinámicamente con estilos premium en JS -->
                <div class="loading-placeholder-centered">
                    <i class="fas fa-spinner fa-spin loading-icon-large"></i>
                    <p>Cargando tareas...</p>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="modal-footer-plain" style="padding: 20px 30px; background: #fff;">
            <button class="btn-modal-cancelar btn-modal-footer-cancel" onclick="cerrarModal('modalVerCambiosProgramadosIVA')">
                <i class="fas fa-times" style="margin-right: 8px;"></i> Cerrar
            </button>
        </div>
    </div>
</div>
    </div>
</div>

<!-- ##=========================== MODAL: PROGRAMAR AJUSTE DE PRECIOS ===========================## -->
<div class="modal-overlay" style="display: none;" id="modalProgramarAjustePrecios">
    <div class="modal-content modal-premium modal-premium-content-480">
        <!-- Header Premium -->
        <div class="modal-header-premium modal-header-blue-dark-gradient">
            <div class="header-text-container">
                <h3 class="modal-header-title-white">
                    <i class="fas fa-sliders-h" style="margin-right: 10px;"></i>Programar Ajuste de Precios
                </h3>
                <p class="modal-subtitulo modal-header-subtitle-white">
                    El ajuste se aplicará a <span id="ajusteProgramadoProductosCount" style="font-weight: 700;">0</span> productos en la fecha seleccionada
                </p>
            </div>
            <button class="modal-close-btn modal-close-btn-white-20" onclick="cerrarModal('modalProgramarAjustePrecios')">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="modal-body-padding-25">
            <div style="display: flex; flex-direction: column; gap: 20px;">
                <!-- Porcentaje de ajuste -->
                <div class="ver-prod-item-premium">
                    <label class="view-item-label-premium">Porcentaje de ajuste <span style="color:#ef4444">*</span></label>
                    <div class="flex-center-gap-10">
                        <i class="fas fa-percent view-icon-blue" style="width: 16px;"></i>
                        <input type="number" id="ajusteProgramadoPorcentaje" step="0.01" placeholder="Ej: 10 o -10" class="input-edit-standard">
                    </div>
                    <p class="view-text-small mt-10" style="margin-left: 26px; color: #6b7280;">
                        <i class="fas fa-arrow-up" style="color: #22c55e; font-size: 0.65rem;"></i> Positivo = subir
                        precios &nbsp;&nbsp;
                        <i class="fas fa-arrow-down" style="color: #ef4444; font-size: 0.65rem;"></i> Negativo = bajar
                        precios
                    </p>
                </div>

                <!-- Fecha y hora programada -->
                <div class="ver-prod-item-premium">
                    <label for="fechaProgramadaAjuste" class="view-item-label-premium">Fecha y hora programada <span style="color:#ef4444">*</span></label>
                    <div class="flex-center-gap-10">
                        <i class="fas fa-calendar-alt" style="color: #f59e0b; width: 16px;"></i>
                        <input type="datetime-local" id="fechaProgramadaAjuste" class="input-edit-standard">
                    </div>
                </div>
            </div>

            <!-- Mensaje informativo -->
            <div class="info-box-premium info-box-blue">
                <i class="fas fa-info-circle" style="font-size: 1rem; margin-top: 1px;"></i>
                <p class="view-text-small" style="margin: 0; line-height: 1.4;">
                    Los precios se ajustarán automáticamente en la fecha programada cuando un administrador acceda al
                    sistema.
                </p>
            </div>

            <!-- Botones -->
            <div class="modal-footer-border-top" style="margin-top: 25px;">
                <button class="btn-modal-cancelar" onclick="cerrarModal('modalProgramarAjustePrecios')">
                    Cancelar
                </button>
                <button class="btn-exito btn-footer-save-blue" onclick="programarAjustePrecios()">
                    <i class="fas fa-clock"></i> Programar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ##=========================== MODAL: VER AJUSTES PROGRAMADOS DE PRECIOS ===========================## -->
<div class="modal-overlay" style="display: none;" id="modalVerAjustesProgramadosPrecios">
    <div class="modal-content modal-premium modal-premium-content-850">
        <!-- Header Premium -->
        <div class="modal-header-premium modal-header-indigo-gradient">
            <div class="flex-center-gap-15">
                <div class="modal-icon-box-white-20">
                    <i class="fas fa-clock view-icon-white view-icon-large"></i>
                </div>
                <div class="header-text-container">
                    <h3 class="modal-header-title-white">Ajustes Programados</h3>
                    <p class="modal-subtitulo modal-header-subtitle-white">Gestión y seguimiento de cambios automáticos</p>
                </div>
            </div>
            <button class="modal-close-btn modal-close-btn-white-15" onclick="cerrarModal('modalVerAjustesProgramadosPrecios')">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Body -->
        <div class="modal-body-padding-25-30" style="background: #f8fafc;">
            <div id="listaAjustesProgramadosPrecios" class="list-container-premium">
                <!-- La tabla se cargará dinámicamente con estilos premium en JS -->
                <div class="loading-placeholder-centered">
                    <i class="fas fa-spinner fa-spin loading-icon-large"></i>
                    <p>Cargando ajustes...</p>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="modal-footer-plain" style="background: #fff; padding: 20px 30px;">
            <button class="btn-modal-cancelar btn-modal-footer-cancel" onclick="cerrarModal('modalVerAjustesProgramadosPrecios')">
                <i class="fas fa-times" style="margin-right: 8px;"></i> Cerrar
            </button>
        </div>
    </div>
</div>

<!-- ##=========================== MODAL: VER DETALLES DE CAMBIO DE IVA PROGRAMADO ===========================## -->
<div class="modal-overlay" style="display: none;" id="modalVerDetallesCambioIVA">
    <div class="modal-content modal-premium modal-premium-content-900">
        <!-- Header Premium -->
        <div class="modal-header-premium modal-header-purple-deep-gradient">
            <div class="flex-center-gap-15">
                <div class="modal-icon-box-white-20">
                    <i class="fas fa-info-circle view-icon-white view-icon-large"></i>
                </div>
                <div class="header-text-container">
                    <h3 class="modal-header-title-white">Detalles del Cambio IVA</h3>
                    <p class="modal-subtitulo modal-header-subtitle-white">Información detallada sobre la actualización de impuestos</p>
                </div>
            </div>
            <button class="modal-close-btn modal-close-btn-white-15" onclick="cerrarModal('modalVerDetallesCambioIVA')">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Body -->
        <div class="modal-body-padding-25-30" style="background: #f8fafc;">
            <div id="detallesCambioIVAInfo" class="grid-responsive-auto-fit container-bg-white-rounded-shadow mb-20 p-25">
                <!-- Info se cargará dinámicamente -->
            </div>
            
            <div id="detallesCambioIVATabla" class="list-container-premium">
                <!-- Tabla se cargará dinámicamente -->
            </div>
        </div>

        <!-- Footer -->
        <div class="modal-footer-plain" style="padding: 20px 30px; background: #fff;">
            <button class="btn-modal-cancelar btn-modal-footer-cancel" onclick="cerrarModal('modalVerDetallesCambioIVA')">
                <i class="fas fa-times" style="margin-right: 8px;"></i> Cerrar
            </button>
        </div>
    </div>
</div>

<!-- ##=========================== MODAL: VER DETALLES DE AJUSTE DE PRECIOS PROGRAMADO ===========================## -->
<div class="modal-overlay" style="display: none;" id="modalVerDetallesAjustePrecios">
    <div class="modal-content modal-premium modal-premium-content-900">
        <!-- Header Premium -->
        <div class="modal-header-premium modal-header-indigo-gradient">
            <div class="flex-center-gap-15">
                <div class="modal-icon-box-white-20">
                    <i class="fas fa-info-circle view-icon-white view-icon-large"></i>
                </div>
                <div class="header-text-container">
                    <h3 class="modal-header-title-white">Detalles del Ajuste</h3>
                    <p class="modal-subtitulo modal-header-subtitle-white">Información detallada sobre el cambio de precios</p>
                </div>
            </div>
            <button class="modal-close-btn modal-close-btn-white-15" onclick="cerrarModal('modalVerDetallesAjustePrecios')">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Body -->
        <div class="modal-body-padding-25-30" style="background: #f8fafc;">
            <div id="detallesAjustePreciosInfo" class="grid-responsive-auto-fit container-bg-white-rounded-shadow mb-20 p-25">
                <!-- Info se cargará dinámicamente -->
            </div>
            
            <div id="detallesAjustePreciosTabla" class="list-container-premium">
                <!-- Tabla se cargará dinámicamente -->
            </div>
        </div>

        <!-- Footer -->
        <div class="modal-footer-plain" style="padding: 20px 30px; background: #fff;">
            <button class="btn-modal-cancelar btn-modal-footer-cancel" onclick="cerrarModal('modalVerDetallesAjustePrecios')">
                <i class="fas fa-times" style="margin-right: 8px;"></i> Cerrar
            </button>
        </div>
    </div>
</div>

<!-- ##=========================== MODAL: CONFLICTO PRECIOS MANUALES ===========================## -->
<div class="modal-overlay modal-backdrop-blur" style="display: none; position: fixed; z-index: 10100; left: 0; top: 0; width: 100%; height: 100%; align-items: center; justify-content: center;" id="modalConflictosTarifa">
    <div class="modal-content modal-premium modal-premium-content-600">
        <div class="modal-header-premium modal-header-yellow-gradient">
            <div class="header-text-container">
                <h3 class="modal-header-title-white">Conflictos de Precios</h3>
                <p class="modal-subtitulo modal-header-subtitle-white">Se han detectado productos con precios modificados manualmente.</p>
            </div>
            <button class="modal-close-btn modal-close-btn-white-20" onclick="cerrarModal('modalConflictosTarifa')">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="modal-body-padding-25">
        <div id="listaProductosConflictivos" class="list-container-premium mb-20 p-10" style="max-height: 250px;">
            <!-- La lista se llenará dinámicamente -->
        </div>

        <div class="modal-footer-plain">
            <button class="btn-modal-cancelar" onclick="cerrarModal('modalConflictosTarifa')">Cancelar</button>
            <button class="btn-editar btn-footer-edit" onclick="confirmarCambioTarifa(false)">Mantener Manuales</button>
            <button class="btn-exito btn-footer-save-blue" onclick="confirmarCambioTarifa(true)">Sobreescribir Todos</button>
        </div>
        </div>
    </div>
</div>

<!-- ##=========================== MODAL: ESTADÁSTICAS DE PRODUCTOS ===========================## -->
 <!-- End of Statistics (closing tags were misaligned or part of another modal) -->

<!-- ##=========================== MODAL: PROGRAMAR CAMBIOS EN TARIFAS ===========================## -->
<div class="modal-overlay modal-backdrop-blur" style="display: none; position: fixed; z-index: 10100; left: 0; top: 0; width: 100%; height: 100%; align-items: center; justify-content: center;" id="modalProgramarCambiosTarifas">
    <div class="modal-content modal-premium modal-premium-content-500">
        <div class="modal-header-premium modal-header-indigo-gradient">
            <div class="header-text-container">
                <h3 class="modal-header-title-white"><i class="fas fa-clock" style="margin-right: 10px;"></i>Confirmar Programación</h3>
                <p class="modal-subtitulo modal-header-subtitle-white">Se van a programar <span id="countCambiosProgramar" style="font-weight: 700;">0</span> cambios de precios.</p>
            </div>
            <button class="modal-close-btn modal-close-btn-white-20" onclick="cerrarModal('modalProgramarCambiosTarifas')">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="modal-body-padding-25">
        <div class="form-group mb-20">
            <label class="view-item-label-premium">Fecha y hora de aplicación:</label>
            <input type="datetime-local" id="fechaProgramadaTarifas" class="input-edit-standard">
        </div>

        <div class="info-box-premium info-box-blue mb-20">
            <i class="fas fa-info-circle view-icon-blue" style="margin-right: 8px;"></i>
            <p class="view-text-small" style="margin: 0;">Los precios cambiarán automáticamente en la fecha seleccionada cuando un administrador acceda al sistema.</p>
        </div>

        <div class="modal-footer-plain">
            <button class="btn-modal-cancelar" onclick="cerrarModal('modalProgramarCambiosTarifas')">Cancelar</button>
            <button class="btn-exito btn-footer-save-indigo" onclick="ejecutarGuardarProgramacionTarifas()">
                <i class="fas fa-save"></i> Confirmar y Programar
            </button>
        </div>
        </div>
    </div>
</div>

<!-- ##=========================== MODAL: VER CAMBIOS DE TARIFAS PROGRAMADOS ===========================## -->
<div class="modal-overlay" style="display: none;" id="modalVerCambiosTarifasProgramados">
    <div class="modal-content modal-premium modal-premium-content-850">
        <!-- Header Premium -->
        <div class="modal-header-premium modal-header-indigo-gradient">
            <div class="flex-center-gap-15">
                <div class="modal-icon-box-white-20">
                    <i class="fas fa-history view-icon-white view-icon-large"></i>
                </div>
                <div class="header-text-container">
                    <h3 class="modal-header-title-white">Historial de Cambios</h3>
                    <p class="modal-subtitulo modal-header-subtitle-white">Registro de actualizaciones de tarifas</p>
                </div>
            </div>
            <button class="modal-close-btn modal-close-btn-white-15" onclick="cerrarModal('modalVerCambiosTarifasProgramados')">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Body -->
        <div class="modal-body-padding-25-30" style="background: var(--bg-panel);">
            <div id="listaBatchesTarifas" class="list-container-premium" style="background: var(--bg-main); border: 1px solid var(--border-main);">
                <!-- La tabla se cargará dinámicamente con estilos premium en JS -->
                <div class="loading-placeholder-centered">
                    <i class="fas fa-spinner fa-spin loading-icon-large"></i>
                    <p>Cargando programaciones...</p>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="modal-footer-plain" style="background: var(--bg-panel); border-top: 1px solid var(--border-main);">
            <button class="btn-modal-cancelar btn-modal-footer-cancel" onclick="cerrarModal('modalVerCambiosTarifasProgramados')">
                <i class="fas fa-times" style="margin-right: 8px;"></i> Cerrar
            </button>
        </div>
    </div>
</div>

<!-- ##=========================== MODAL: DETALLES DE LOTE DE TARIFAS ===========================## -->
<div class="modal-overlay" style="display: none;" id="modalDetalleBatchTarifas">
    <div class="modal-content modal-premium modal-premium-content-850">
        <!-- Header Premium -->
        <div class="modal-header-premium modal-header-blue-dark-gradient">
            <div class="flex-center-gap-15">
                <div class="modal-icon-box-white-20">
                    <i class="fas fa-info-circle view-icon-white view-icon-large"></i>
                </div>
                <div class="header-text-container">
                    <h3 class="modal-header-title-white">Detalle de Actualización #<span id="detalleBatchId"></span></h3>
                    <p id="detalleBatchMeta" class="modal-subtitulo modal-header-subtitle-white">Productos y precios procesados</p>
                </div>
            </div>
            <button class="modal-close-btn modal-close-btn-white-15" onclick="cerrarModal('modalDetalleBatchTarifas')">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Body -->
        <div class="modal-body-padding-25-30" style="background: var(--bg-panel);">
            <div id="tablaDetalleBatch" class="list-container-premium" style="background: var(--bg-main); border: 1px solid var(--border-main);">
                <!-- Tabla dinámica se carga en JS -->
            </div>
        </div>

        <!-- Footer -->
        <div class="modal-footer-plain" style="background: var(--bg-panel); border-top: 1px solid var(--border-main);">
            <button class="btn-modal-cancelar btn-modal-footer-cancel" onclick="cerrarModal('modalDetalleBatchTarifas')">
                <i class="fas fa-arrow-left" style="margin-right: 8px;"></i> Regresar
            </button>
        </div>
    </div>
</div>

<!-- ##=========================== MODAL: ESTADÁSTICAS DE PRODUCTOS ===========================## -->
<div class="modal-overlay modal-backdrop-blur" style="display: none; position: fixed; z-index: 10100; left: 0; top: 0; width: 100%; height: 100%; align-items: center; justify-content: center;" id="modalEstadisticasProductos">
    <div class="modal-content modal-premium modal-premium-content-1000">
        <div class="modal-header-premium modal-header-blue-gradient">
            <div class="header-text-container">
                <h3 class="modal-header-title-white"><i class="fas fa-chart-bar" style="margin-right: 10px;"></i>Estadísticas de Productos</h3>
                <p class="modal-subtitulo modal-header-subtitle-white">Análisis de ventas y stock</p>
            </div>
            <button class="modal-close-btn modal-close-btn-white-20" onclick="cerrarModal('modalEstadisticasProductos')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body-padding-25" id="estadisticasProductosContenido">
            <!-- Contenido cargado dinámicamente -->
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<!-- ##-----------------------------------MODAL CENTRO DE TAREAS-----------------------------------## -->
<div class="modal-overlay" style="display: none;" id="modalCentroTareas">
    <div class="modal-content modal-premium modal-premium-content-650">
        <!-- Header Premium -->
        <div class="modal-header-premium modal-header-dark-gradient">
            <div class="flex-center-gap-15">
                <div class="modal-icon-box-white-10">
                    <i class="fas fa-tasks view-icon-white view-icon-large"></i>
                </div>
                <div>
                    <h3 class="modal-header-title-white">Centro de Tareas</h3>
                    <p class="modal-subtitulo modal-header-subtitle-white" style="color: rgba(255,255,255,0.7);">Estado de procesos en segundo plano</p>
                </div>
            </div>
            <button class="modal-close-btn modal-close-btn-white-15" onclick="cerrarModal('modalCentroTareas')">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Body -->
        <div class="modal-body-padding-25-30">
            <div id="listaTareasAdmin" class="list-container-premium" style="background: var(--bg-main); border: 1px solid var(--border-main); box-shadow: var(--shadow-sm);">
                <!-- Las tareas se cargan dinámicamente -->
                <div class="loading-placeholder-centered">
                    <i class="fas fa-spinner fa-spin loading-icon-large"></i>
                    <p>Cargando historial de tareas...</p>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="modal-footer-plain" style="justify-content: space-between; align-items: center;">
            <p class="view-text-small" style="margin: 0; color: var(--text-muted);">
                <i class="fas fa-sync-alt fa-spin" style="margin-right: 5px;"></i> Auto-actualizado cada 10s
            </p>
            <div class="flex-center-gap-10">
                <button class="btn-tpv btn-footer-clear" onclick="limpiarCentroTareas()" 
                    style="margin: 0; padding: 10px 20px; border-radius: 10px; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-trash-alt"></i> Limpiar Historial
                </button>
                <button class="btn-modal-cancelar btn-modal-footer-cancel" onclick="cerrarModal('modalCentroTareas')" 
                    style="margin: 0; padding: 10px 25px;">
                    Cerrar
                </button>
            </div>
        </div>
    </div>
</div>


