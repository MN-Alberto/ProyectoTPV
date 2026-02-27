<script src="webroot/js/admin.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<section id="cajero">
    <!-- Panel izquierdo: Navegación de Admin -->
    <div class="cajero-productos" style="max-width: 300px; border-right: 1px solid #e5e7eb;">
        <div id="formBuscarProducto" style="padding: 20px;">
            <h2 style="font-size: 1.2rem; color: #1a1a2e;">Administración</h2>
        </div>
        <div class="cajero-categorias" style="flex-direction: column; height: 100%; gap: 10px; padding: 20px;">
            <button class="cat-btn activa" data-seccion="dashboard" style="width: 100%; text-align: left;">
                <i class="fas fa-chart-line" style="margin-right: 10px;"></i> Dashboard
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
        </div>

        <div class="admin-content-panel">
            <h3 id="adminTitulo"
                style="margin-bottom: 20px; color: #1a1a2e; border-bottom: 2px solid #f0f2f5; padding-bottom: 10px;">
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
        <h3>Editar Producto</h3>
        <p class="modal-subtitulo">Modifica los datos del producto</p>

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
                    <input type="text" id="editProductoCategoria" readonly
                        style="background:#f3f4f6; cursor:not-allowed;">
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

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<script>
    // ======================== NAVEGACIÓN PANEL ADMIN ========================

    const TITULOS = {
        dashboard: 'Resumen de Actividad',
        productos: 'Gestión de Productos',
        usuarios: 'Gestión de Usuarios',
        ventas: 'Historial de Ventas',
        configuracion: 'Configuración'
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
                    cargarProductosAdmin();
                    break;
                case 'usuarios':
                    // TODO: cargarUsuariosAdmin();
                    document.getElementById('adminContenido').innerHTML =
                        '<p style="color:#9ca3af; padding:40px; text-align:center;">Sección en construcción...</p>';
                    break;
                case 'ventas':
                    // TODO: cargarVentasAdmin();
                    document.getElementById('adminContenido').innerHTML =
                        '<p style="color:#9ca3af; padding:40px; text-align:center;">Sección en construcción...</p>';
                    break;
                case 'configuracion':
                    // TODO: cargarConfiguracion();
                    document.getElementById('adminContenido').innerHTML =
                        '<p style="color:#9ca3af; padding:40px; text-align:center;">Sección en construcción...</p>';
                    break;
            }
        });
    });
    document.getElementById('adminContenido').innerHTML = HTML_DASHBOARD;
    cargarGraficoDashboard();
</script>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">