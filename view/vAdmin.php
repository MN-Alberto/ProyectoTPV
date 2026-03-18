<script src="webroot/js/admin.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<section id="cajero">
    <!-- Panel izquierdo: Navegación de Admin -->
    <div class="cajero-productos admin-sidebar" style="max-width: 300px; border-right: 1px solid #e5e7eb;">
        <div id="formBuscarProducto" class="admin-sidebar-header" style="padding: 20px;">
            <h2 class="admin-view-title">Administración</h2>
        </div>
        <div class="cajero-categorias admin-nav-buttons"
            style="flex-direction: column; gap: 10px; padding: 20px;">
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
                    <i class="fas fa-tags" style="margin-right: 10px;"></i> Tarifas Prefijadas
                </button>
            </div>

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
            </div>
        </div>
    </div>

    <!-- Panel derecho: Contenido del Dashboard -->
    <div class="admin-dashboard">
        <div class="admin-header">
            <h2>Panel de Control</h2>
            <div class="indicador-efectivo"
                style="<?php echo !$sesionCaja ? 'background: #fee2e2; border-color: #fecaca;' : ''; ?>">
                <span class="label">Estado del Sistema:</span>
                <span class="amount" style="color: <?php echo $sesionCaja ? '#059669' : '#dc2626'; ?>;">
                    <?php echo $sesionCaja ? 'Online' : 'Offline (Caja Cerrada)'; ?>
                </span>
                <div class="separador"></div>
                <span class="label"><?php echo $sesionCaja ? 'Efectivo en Caja:' : 'Fondo Siguiente Turno:'; ?></span>
                <span class="amount">
                    <?php echo number_format($stats['efectivoCaja'], 2, ',', '.'); ?> €
                </span>
            </div>
        </div>

        <div class="admin-stats-grid">
            <div class="admin-stat-card">
                <span class="admin-stat-label"><?php echo $tituloVentas; ?></span>
                <span class="admin-stat-value"><?php echo number_format($stats['ventasHoy'], 2, ',', '.'); ?> €</span>
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

<!-- ##-----------------------------------MODAL VER PRODUCTO-----------------------------------## -->

<!-- ##-----------------------------------MODAL VER CATEGORÍA-----------------------------------## -->

<div class="modal-overlay" id="modalVerCategoria" style="display:none;">
    <div class="modal-content modal-verCategoria" style="max-width: 420px;">
        <h3>Detalle de Categoría</h3>
        <p class="modal-subtitulo">Información completa</p>

        <div style="display: flex; flex-direction: column; gap: 12px; margin: 15px 0;">
            <div class="ver-cat-fila">
                <span class="ver-cat-label">ID</span>
                <span id="verCategoriaId" class="ver-cat-valor"></span>
            </div>
            <div class="ver-cat-fila">
                <span class="ver-cat-label">Nombre</span>
                <span id="verCategoriaNombre" class="ver-cat-valor"></span>
            </div>
            <div class="ver-cat-fila">
                <span class="ver-cat-label">Productos</span>
                <span id="verCategoriaProductos" class="ver-cat-valor"></span>
            </div>
            <div class="ver-cat-fila">
                <span class="ver-cat-label">Fecha de Creación</span>
                <span id="verCategoriaFecha" class="ver-cat-valor"></span>
            </div>
            <div class="ver-cat-fila">
                <span class="ver-cat-label">Descripción</span>
                <span id="verCategoriaDescripcion" class="ver-cat-valor"></span>
            </div>
        </div>

        <div style="display: flex; justify-content: center; margin-top: 20px;">
            <button class="btn-modal-cancelar" onclick="cerrarModal('modalVerCategoria')" style="min-width: 100px;">
                Cerrar
            </button>
        </div>
    </div>
</div>

<!-- ##-----------------------------------MODAL VER PRODUCTO-----------------------------------## -->

<div class="modal-overlay" id="modalVerProducto" style="display:none;">
    <div class="modal-content modal-verProducto" style="max-width: 420px;">
        <h3>Detalle del Producto</h3>
        <p class="modal-subtitulo">Información completa</p>

        <div style="display: flex; gap: 20px; align-items: flex-start; margin: 15px 0;">
            <!-- Imagen -->
            <img id="verProductoImagen" src="" alt=""
                style="width: 100px; height: 100px; object-fit: cover; border-radius: 8px; border: 1px solid #e5e7eb; flex-shrink: 0;">

            <!-- Datos -->
            <div style="flex: 1; display: flex; flex-direction: column; gap: 10px;">
                <div class="ver-prod-fila">
                    <span class="ver-prod-label">Nombre</span>
                    <span id="verProductoNombre" class="ver-prod-valor"></span>
                </div>
                <div class="ver-prod-fila">
                    <span class="ver-prod-label">Categoría</span>
                    <span id="verProductoCategoria" class="ver-prod-valor"></span>
                </div>
                <div class="ver-prod-fila">
                    <span class="ver-prod-label">Precio</span>
                    <span id="verProductoPrecio" class="ver-prod-valor"
                        style="color: #059669; font-weight: 700;"></span>
                </div>
                <div class="ver-prod-fila">
                    <span class="ver-prod-label">Stock</span>
                    <span id="verProductoStock" class="ver-prod-valor"></span>
                </div>
                <div class="ver-prod-fila">
                    <span class="ver-prod-label">Estado</span>
                    <span id="verProductoEstado" class="ver-prod-valor"></span>
                </div>
                <div class="ver-prod-fila">
                    <span class="ver-prod-label">IVA</span>
                    <span id="verProductoIva" class="ver-prod-valor"></span>
                </div>
            </div>
        </div>

        <div style="display: flex; justify-content: center; margin-top: 20px;">
            <button class="btn-modal-cancelar" onclick="cerrarModal('modalVerProducto')" style="min-width: 100px;">
                Cerrar
            </button>
        </div>
    </div>
</div>

<!-- ##-----------------------------------MODAL EDITAR PRODUCTO-----------------------------------## -->

<div class="modal-overlay" id="modalEditarProducto" style="display:none;">
    <div class="modal-content modal-editarProducto">
        <h3 id="editProductoTitulo">Editar Producto</h3>
        <p id="editProductoSubtitulo" class="modal-subtitulo">Modifica los datos del producto</p>

        <input type="hidden" id="editProductoId">

        <div class="editar-prod-layout">
            <!-- Imagen -->
            <div class="editar-prod-imagen-wrapper">
                <img id="editProductoImagen" src="" alt="" style="cursor: zoom-in;"
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
                    <input type="text" id="editProductoNombre">
                </div>
                <div class="editar-prod-fila">
                    <label>Categoría</label>
                    <select id="editProductoCategoria"
                        style="padding: 8px; border-radius: 4px; border: 1px solid #d1d5db;">
                    </select>
                </div>
                <div class="editar-prod-fila">
                    <label>Precio (€)</label>
                    <input type="number" id="editProductoPrecio" step="0.01" min="0">
                </div>
                <div class="editar-prod-fila">
                    <label>Stock</label>
                    <input type="number" id="editProductoStock" min="0">
                </div>
                <div class="editar-prod-fila">
                    <label>Estado</label>
                    <select id="editProductoEstado">
                        <option value="1">Activo</option>
                        <option value="0">Inactivo</option>
                    </select>
                </div>
                <div class="editar-prod-fila">
                    <label>Tipo de IVA</label>
                    <select id="editProductoIva">
                        <!-- Se rellena dinámicamente desde api/iva.php -->
                    </select>
                </div>
            </div>
        </div>

        <div class="editar-prod-botones">
            <button class="btn-modal-cancelar" onclick="cerrarModal('modalEditarProducto')">Cancelar</button>
            <button class="btn-exito" onclick="guardarCambiosProducto()">
                <i class="fas fa-save"></i> Guardar Cambios
            </button>
        </div>
    </div>
</div>

<!-- ##-----------------------------------MODAL VER USUARIO-----------------------------------## -->

<div class="modal-overlay" id="modalVerUsuario" style="display:none;">
    <div class="modal-content modal-verProducto" style="max-width: 420px;">
        <h3>Detalle del Usuario</h3>
        <p class="modal-subtitulo">Información completa</p>

        <div style="display: flex; flex-direction: column; gap: 15px; margin: 20px 0;">
            <div class="ver-prod-fila">
                <span class="ver-prod-label">Nombre</span>
                <span id="verUsuarioNombre" class="ver-prod-valor"></span>
            </div>
            <div class="ver-prod-fila">
                <span class="ver-prod-label">Email</span>
                <span id="verUsuarioEmail" class="ver-prod-valor"></span>
            </div>
            <div class="ver-prod-fila">
                <span class="ver-prod-label">Rol</span>
                <span id="verUsuarioRol" class="ver-prod-valor"></span>
            </div>
            <div class="ver-prod-fila">
                <span class="ver-prod-label">Fecha de Alta</span>
                <span id="verUsuarioFecha" class="ver-prod-valor"></span>
            </div>
            <div class="ver-prod-fila">
                <span class="ver-prod-label">Estado</span>
                <span id="verUsuarioEstado" class="ver-prod-valor"></span>
            </div>
            <div class="ver-prod-fila">
                <span class="ver-prod-label">Crear Productos</span>
                <span id="verUsuarioCrearProductos" class="ver-prod-valor"></span>
            </div>
            <div class="ver-prod-fila">
                <span class="ver-prod-label">Total Descansos</span>
                <span id="verUsuarioTotalDescansos" class="ver-prod-valor"></span>
            </div>
            <div class="ver-prod-fila">
                <span class="ver-prod-label">Total Cambios Turno</span>
                <span id="verUsuarioTotalTurnos" class="ver-prod-valor"></span>
            </div>
        </div>

        <div style="display: flex; justify-content: center; margin-top: 20px;">
            <button class="btn-modal-cancelar" onclick="cerrarModal('modalVerUsuario')" style="min-width: 100px;">
                Cerrar
            </button>
        </div>
    </div>
</div>

<!-- ##-----------------------------------MODAL VER DEVOLUCION-----------------------------------## -->

<div class="modal-overlay" id="modalVerDevolucion" style="display:none;">
    <div class="modal-content modal-verProducto" style="max-width: 420px;">
        <h3>Detalle de Devolución</h3>
        <p class="modal-subtitulo">Información de la operación</p>

        <div style="display: flex; flex-direction: column; gap: 15px; margin: 20px 0;">
            <div class="ver-prod-fila">
                <span class="ver-prod-label">ID Devolución</span>
                <span id="verDevolucionId" class="ver-prod-valor"></span>
            </div>
            <div class="ver-prod-fila">
                <span class="ver-prod-label">Ticket Original</span>
                <span id="verDevolucionTicket" class="ver-prod-valor" style="font-weight: 700; color: #1e40af;"></span>
            </div>
            <div class="ver-prod-fila">
                <span class="ver-prod-label">Fecha</span>
                <span id="verDevolucionFecha" class="ver-prod-valor"></span>
            </div>
            <div class="ver-prod-fila">
                <span class="ver-prod-label">Producto</span>
                <span id="verDevolucionProducto" class="ver-prod-valor"></span>
            </div>
            <div class="ver-prod-fila">
                <span class="ver-prod-label">Cantidad</span>
                <span id="verDevolucionCantidad" class="ver-prod-valor"></span>
            </div>
            <div class="ver-prod-fila">
                <span class="ver-prod-label">Importe Total</span>
                <span id="verDevolucionImporte" class="ver-prod-valor" style="color: #dc2626; font-weight: 700;"></span>
            </div>
            <div class="ver-prod-fila">
                <span class="ver-prod-label">Método Reembolso</span>
                <span id="verDevolucionMetodo" class="ver-prod-valor"></span>
            </div>
            <div class="ver-prod-fila">
                <span class="ver-prod-label">Empleado</span>
                <span id="verDevolucionUsuario" class="ver-prod-valor"></span>
            </div>
        </div>

        <div style="display: flex; justify-content: center; margin-top: 20px;">
            <button class="btn-modal-cancelar" onclick="cerrarModal('modalVerDevolucion')" style="min-width: 100px;">
                Cerrar
            </button>
        </div>
    </div>
</div>

<!-- ##-----------------------------------MODAL EDITAR/CREAR USUARIO-----------------------------------## -->

<div class="modal-overlay" id="modalEditarUsuario" style="display:none;">
    <div class="modal-content modal-editarProducto">
        <h3 id="editUsuarioTitulo">Editar Usuario</h3>
        <p class="modal-subtitulo">Modifica los datos del usuario</p>

        <input type="hidden" id="editUsuarioId">

        <div class="editar-prod-campos" style="max-width: 100%;">
            <div class="editar-prod-fila">
                <label>Nombre <span style="color:red">*</span></label>
                <input type="text" id="editUsuarioNombre" required>
            </div>
            <div class="editar-prod-fila">
                <label>Email <span style="color:red">*</span></label>
                <input type="email" id="editUsuarioEmail" required>
            </div>
            <div class="editar-prod-fila">
                <label>Password <span style="color:red">*</span></label>
                <input type="password" id="editUsuarioPassword">
            </div>
            <div class="editar-prod-fila">
                <label>Rol</label>
                <select id="editUsuarioRol">
                    <option value="empleado">Empleado</option>
                    <option value="admin">Administrador</option>
                </select>
            </div>
            <div class="editar-prod-fila">
                <label>Estado</label>
                <select id="editUsuarioEstado">
                    <option value="1">Activo</option>
                    <option value="0">Inactivo</option>
                </select>
            </div>
            <div class="editar-prod-fila" id="filaPermisos" style="display: none;">
                <label>Permisos adicionales</label>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <input type="checkbox" id="editUsuarioPermisoCrearProductos" value="crear_productos"
                        style="width: auto;">
                    <span>Permitir crear productos</span>
                </div>
                <p style="font-size: 0.8rem; color: #6b7280; margin-top: 4px;">El empleado podrá añadir productos desde
                    su vista de cajero</p>
            </div>
        </div>

        <div class="editar-prod-botones">
            <button class="btn-modal-cancelar" onclick="cerrarModal('modalEditarUsuario')">Cancelar</button>
            <button class="btn-exito" onclick="guardarCambiosUsuario()">
                <i class="fas fa-save"></i> Guardar Cambios
            </button>
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
                <input type="number" id="asociarProvPrecio" step="0.01" min="0" value="0.00" required>
            </div>

            <div class="editar-prod-fila">
                <label>Recargo Equivalencia (%) <span style="color:red">*</span></label>
                <input type="number" id="asociarProvRecargo" step="0.01" min="0" value="0.00" required>
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
    <div class="modal-content" style="max-width: 500px; text-align: left;">
        <h3 style="margin-bottom: 5px;">Nuevo Cliente</h3>
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

            <!-- Campo Fecha de Alta (solo lectura - se establece automáticamente) -->
            <div>
                <label for="clienteHabitualFecha"
                    style="display: block; margin-bottom: 5px; font-weight: 500; font-size: 0.9rem;">Fecha de
                    Alta</label>
                <input type="datetime-local" id="clienteHabitualFecha" readonly
                    style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; background-color: #f3f4f6; color: #6b7280;">
            </div>
        </div>

        <!-- Botones: Cancelar y Guardar -->
        <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 25px;">
            <button class="btn-modal-cancelar" onclick="cerrarModal('modalClienteHabitual')">Cancelar</button>
            <button class="btn-exito" id="btnGuardarClienteHabitual" onclick="guardarClienteHabitualAdmin()"
                style="margin: 0;">Guardar</button>
        </div>
    </div>
</div>

<!-- ##=========================== MODAL: EDITAR CLIENTE (ADMIN) ===========================## -->
<div class="modal-overlay" id="modalEditarCliente" style="display:none;">
    <div class="modal-content" style="max-width: 500px; text-align: left;">
        <h3 style="margin-bottom: 5px;">Editar Cliente</h3>
        <p class="modal-subtitulo" style="margin-bottom: 20px;">Modifique los datos del cliente</p>

        <input type="hidden" id="editarClienteId">

        <div style="display: grid; gap: 15px;">
            <!-- Campo DNI -->
            <div>
                <label for="editarClienteDni"
                    style="display: block; margin-bottom: 5px; font-weight: 500; font-size: 0.9rem;">DNI <span
                        style="color: #ef4444;">*</span></label>
                <input type="text" id="editarClienteDni"
                    style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;"
                    placeholder="12345678A" maxlength="20">
            </div>

            <!-- Campo Nombre -->
            <div>
                <label for="editarClienteNombre"
                    style="display: block; margin-bottom: 5px; font-weight: 500; font-size: 0.9rem;">Nombre <span
                        style="color: #ef4444;">*</span></label>
                <input type="text" id="editarClienteNombre"
                    style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;"
                    placeholder="Juan" maxlength="100">
            </div>

            <!-- Campo Apellidos -->
            <div>
                <label for="editarClienteApellidos"
                    style="display: block; margin-bottom: 5px; font-weight: 500; font-size: 0.9rem;">Apellidos <span
                        style="color: #ef4444;">*</span></label>
                <input type="text" id="editarClienteApellidos"
                    style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;"
                    placeholder="García López" maxlength="150">
            </div>
        </div>

        <!-- Botones: Cancelar y Guardar -->
        <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 25px;">
            <button class="btn-modal-cancelar" onclick="cerrarModal('modalEditarCliente')">Cancelar</button>
            <button class="btn-exito" id="btnGuardarClienteEditado" onclick="guardarClienteEditado()"
                style="margin: 0;">Guardar</button>
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
        <h3 style="margin-bottom: 5px;"><i class="fas fa-clock" style="margin-right: 10px;"></i>Confirmar Programación</h3>
        <p class="modal-subtitulo" style="margin-bottom: 15px;">
            Se van a programar <span id="countCambiosProgramar">0</span> cambios de precios.
        </p>

        <div class="form-group" style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 8px; font-weight: 600;">Fecha y hora de aplicación:</label>
            <input type="datetime-local" id="fechaProgramadaTarifas" class="input-buscarProducto"
                style="width: 100%; padding: 12px; background: var(--bg-input); color: var(--text-main); border: 1px solid var(--border-main); border-radius: 8px; font-size: 16px;">
        </div>

        <div style="background: var(--bg-secondary); padding: 15px; border-radius: 8px; margin-bottom: 20px; font-size: 14px;">
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
            <h3 style="margin: 0;"><i class="fas fa-history" style="margin-right: 10px;"></i>Historial de Programaciones</h3>
            <button onclick="cerrarModal('modalVerCambiosTarifasProgramados')" style="background:none; border:none; font-size: 24px; cursor:pointer; color: var(--text-secondary);">&times;</button>
        </div>
        <p class="modal-subtitulo" style="margin-bottom: 20px;">
            Listado de lotes de cambios de precios programados. Los lotes 'Pendientes' pueden ser cancelados.
        </p>

        <div id="listaBatchesTarifas" style="max-height: 450px; overflow-y: auto; border: 1px solid var(--border-main); border-radius: 8px; background: var(--bg-main);">
            <!-- La tabla se cargará dinámicamente -->
        </div>

        <div class="editar-prod-botones" style="margin-top: 20px;">
            <button class="btn-modal-cancelar" onclick="cerrarModal('modalVerCambiosTarifasProgramados')">Cerrar</button>
        </div>
    </div>
</div>

<!-- ##=========================== MODAL: DETALLES DE LOTE DE TARIFAS ===========================## -->
<div class="modal-overlay" id="modalDetalleBatchTarifas"
    style="display:none; position: fixed; z-index: 10200; left: 0; top: 0; width: 100%; height: 100%; align-items: center; justify-content: center; background-color: rgba(0,0,0,0.4); backdrop-filter: blur(1px);">
    <div class="modal-content" style="max-width: 700px; text-align: left; max-height: 80vh; width: 95%;">
        <h3>Detalles del Lote #<span id="detalleBatchId"></span></h3>
        <p id="detalleBatchMeta" style="margin-bottom: 15px; color: var(--text-secondary); font-size: 14px;"></p>

        <div id="tablaDetalleBatch" style="max-height: 350px; overflow-y: auto; border: 1px solid var(--border-main); border-radius: 8px; margin-bottom: 20px;">
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

<script>
    // ======================== NAVEGACIÓN PANEL ADMIN ========================

    // Títulos para el panel lateral
    const TITULOS = {
        dashboard: 'Resumen de Actividad',
        productos: 'Gestión de Productos',
        usuarios: 'Gestión de Usuarios',
        ventas: 'Historial de Ventas',
        devoluciones: 'Gestión de Devoluciones',
        proveedores: 'Gestión de Proveedores',
        configuracion: 'Configuración',
        logs: 'Logs del Sistema',
        retiros: 'Retiros de Caja',
        'caja-sesiones': 'Sesiones de Caja',
        categorias: 'Gestión de Categorías',
        'tarifa-iva': 'Cambiar IVA General',
        'tarifa-ajuste': 'Ajuste de Precios',
        clientes: 'Gestión de Clientes',
        'tarifa-prefijadas': 'Tarifas Prefijadas',
        'historial-precios': 'Historial de Precios',
        'config-tema': 'Configuración: Tema',
        'config-acciones': 'Configuración: Acciones'
    };

    document.querySelectorAll('.cat-btn[data-seccion]').forEach(btn => {
        btn.addEventListener('click', () => {
            // Actualizar botón activo
            document.querySelectorAll('.cat-btn').forEach(b => b.classList.remove('activa'));
            btn.classList.add('activa');

            const seccion = btn.dataset.seccion;
            document.getElementById('adminTitulo').textContent = TITULOS[seccion] ?? seccion;

            // Toggle modo configuración para ganar espacio
            const dashboard = document.querySelector('.admin-dashboard');
            if (seccion.startsWith('config-') || seccion === 'configuracion') {
                dashboard.classList.add('admin-mode-config');
            } else {
                dashboard.classList.remove('admin-mode-config');
            }

            switch (seccion) {
                case 'dashboard':
                    document.getElementById('adminContenido').innerHTML = HTML_DASHBOARD;
                    cargarGraficoDashboard();
                    break;
                case 'productos':
                    cargarCategoriasAdmin().then(() => cargarProductosAdmin());
                    break;
                case 'usuarios':
                    cargarUsuariosAdmin();
                    break;
                case 'ventas':
                    cargarVentasAdmin();
                    break;
                case 'retiros':
                    cargarRetirosAdmin();
                    break;
                case 'devoluciones':
                    cargarDevolucionesAdmin();
                    break;
                case 'proveedores':
                    cargarProveedoresAdmin();
                    break;
                case 'configuracion':
                    cargarConfiguracion();
                    break;
                case 'config-tema':
                    cargarConfiguracion('tema');
                    break;
                case 'config-acciones':
                    cargarConfiguracion('acciones');
                    break;
                case 'logs':
                    cargarLogs();
                    break;
                case 'caja-sesiones':
                    cargarCajaSesionesAdmin();
                    break;
                case 'categorias':
                    cargarCategoriasAdmin().then(() => mostrarPanelCategorias());
                    break;
                case 'tarifa-iva':
                    mostrarPanelCambiarIVA();
                    break;
                case 'tarifa-ajuste':
                    mostrarPanelAjustePrecios();
                    break;
                case 'tarifa-prefijadas':
                    mostrarPanelTarifasPrefijadas();
                    break;
                case 'historial-precios':
                    mostrarPanelHistorialPrecios();
                    break;
                case 'clientes':
                    cargarClientesAdmin();
                    break;
                case 'informe-diario':
                    mostrarSeccionInformes('diario');
                    break;
                case 'informe-semanal':
                    mostrarSeccionInformes('semanal');
                    break;
                case 'informe-mensual':
                    mostrarSeccionInformes('mensual');
                    break;
                case 'informe-anual':
                    mostrarSeccionInformes('anual');
                    break;
            }
        });
    });

    // Toggle submenu de Configuración
    document.getElementById('btnConfig').addEventListener('click', function (e) {
        e.stopPropagation();
        var submenu = document.getElementById('submenuConfig');
        submenu.style.display = submenu.style.display === 'none' ? 'block' : 'none';
        
        // Cerrar otros submenus
        document.getElementById('submenuTarifas').style.display = 'none';
        document.getElementById('submenuInformes').style.display = 'none';
    });

    // Toggle submenu de Tarifas
    document.getElementById('btnTarifas').addEventListener('click', function (e) {
        e.stopPropagation();
        var submenu = document.getElementById('submenuTarifas');
        submenu.style.display = submenu.style.display === 'none' ? 'block' : 'none';

        // Cerrar otros submenus
        document.getElementById('submenuConfig').style.display = 'none';
        document.getElementById('submenuInformes').style.display = 'none';
    });

    // Toggle submenu de Informes
    document.getElementById('btnInformes').addEventListener('click', function (e) {
        e.stopPropagation();
        var submenu = document.getElementById('submenuInformes');
        submenu.style.display = submenu.style.display === 'none' ? 'block' : 'none';

        // Cerrar otros submenus
        document.getElementById('submenuConfig').style.display = 'none';
        document.getElementById('submenuTarifas').style.display = 'none';
    });

    // Cerrar submenus al hacer click fuera
    document.addEventListener('click', function (e) {
        var subTarifas = document.getElementById('submenuTarifas');
        var subConfig = document.getElementById('submenuConfig');
        var subInformes = document.getElementById('submenuInformes');
        var btnTarifas = document.getElementById('btnTarifas');
        var btnConfig = document.getElementById('btnConfig');
        var btnInformes = document.getElementById('btnInformes');

        if (!btnTarifas.contains(e.target) && !subTarifas.contains(e.target)) {
            subTarifas.style.display = 'none';
        }
        if (!btnConfig.contains(e.target) && !subConfig.contains(e.target)) {
            subConfig.style.display = 'none';
        }
        if (!btnInformes.contains(e.target) && !subInformes.contains(e.target)) {
            subInformes.style.display = 'none';
        }
    });
    document.getElementById('adminContenido').innerHTML = HTML_DASHBOARD;
    cargarGraficoDashboard();

    // Cargar categorías y tipos de IVA al inicio
    cargarCategoriasAdmin();
    cargarTiposIva();
    verificarCambiosIvaProgramados();
    verificarAjustesPreciosProgramados();
</script>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">