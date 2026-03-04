<script src="webroot/js/admin.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<section id="cajero">
    <!-- Panel izquierdo: Navegación de Admin -->
    <div class="cajero-productos admin-sidebar" style="max-width: 300px; border-right: 1px solid #e5e7eb;">
        <div id="formBuscarProducto" class="admin-sidebar-header" style="padding: 20px;">
            <h2 class="admin-view-title">Administración</h2>
        </div>
        <div class="cajero-categorias admin-nav-buttons"
            style="flex-direction: column; height: 100%; gap: 10px; padding: 20px;">
            <button class="cat-btn activa" data-seccion="dashboard" style="width: 100%; text-align: left;">
                <i class="fas fa-chart-line" style="margin-right: 10px;"></i> Dashboard
            </button>
            <button class="cat-btn" data-seccion="caja-sesiones" style="width: 100%; text-align: left;">
                <i class="fas fa-cash-register" style="margin-right: 10px;"></i> Sesiones de Caja
            </button>
            <button class="cat-btn" data-seccion="productos" style="width: 100%; text-align: left;">
                <i class="fas fa-box" style="margin-right: 10px;"></i> Productos
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
            <button class="cat-btn" data-seccion="configuracion" style="width: 100%; text-align: left;">
                <i class="fas fa-cog" style="margin-right: 10px;"></i> Configuración
            </button>
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
                    <label>Tipo de IVA (%)</label>
                    <select id="editProductoIva">
                        <option value="21">21% (General)</option>
                        <option value="10">10% (Reducido)</option>
                        <option value="4">4% (Superreducido)</option>
                        <option value="0">0% (Exento)</option>
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
        retiros: 'Retiros de Caja',
        'caja-sesiones': 'Sesiones de Caja'
    };

    document.querySelectorAll('.cat-btn[data-seccion]').forEach(btn => {
        btn.addEventListener('click', () => {
            // Actualizar botón activo
            document.querySelectorAll('.cat-btn').forEach(b => b.classList.remove('activa'));
            btn.classList.add('activa');

            const seccion = btn.dataset.seccion;
            document.getElementById('adminTitulo').textContent = TITULOS[seccion] ?? seccion;

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
                case 'caja-sesiones':
                    cargarCajaSesionesAdmin();
                    break;
            }
        });
    });
    document.getElementById('adminContenido').innerHTML = HTML_DASHBOARD;
    cargarGraficoDashboard();
</script>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">