/**
 * admin.pagination.js
 * Funciones de paginación específicas para cada entidad.
 * Depende de: admin.state.js, admin.utils.js
 */

// ── PRODUCTOS ────────────────────────────────────────────────────────────────

function getPaginacionProductosHTML(totalPaginas) {
    return generarPaginacionHTML(paginaActualProductos, totalPaginas,
        'cambiarPaginaProductos', 'irAPaginaProductos', 'inputPaginaProductos');
}
function cambiarPaginaProductos(nuevaPagina) {
    const total = Math.ceil(productosData.length / productosPorPagina);
    if (nuevaPagina < 1 || nuevaPagina > total) return;
    paginaActualProductos = nuevaPagina;
    renderizarProductosPagina();
}
function irAPaginaProductos() {
    const input = document.getElementById('inputPaginaProductos');
    if (!input) return;
    let p = parseInt(input.value);
    const total = Math.ceil(productosData.length / productosPorPagina);
    if (isNaN(p) || p < 1) p = 1;
    else if (p > total) p = total;
    cambiarPaginaProductos(p);
}

// ── CATEGORÍAS ────────────────────────────────────────────────────────────────

function getPaginacionCategoriasHTML(totalPaginas) {
    return generarPaginacionHTML(paginaActualCategorias, totalPaginas,
        'cambiarPaginaCategorias', 'irAPaginaCategorias', 'inputPaginaCategorias');
}
function cambiarPaginaCategorias(nuevaPagina) {
    const total = Math.ceil(categoriasData.length / categoriasPorPagina);
    if (nuevaPagina < 1 || nuevaPagina > total) return;
    paginaActualCategorias = nuevaPagina;
    renderizarCategoriasPagina();
}
function irAPaginaCategorias() {
    const input = document.getElementById('inputPaginaCategorias');
    if (!input) return;
    let p = parseInt(input.value);
    const total = Math.ceil(categoriasData.length / categoriasPorPagina);
    if (isNaN(p) || p < 1) p = 1;
    else if (p > total) p = total;
    cambiarPaginaCategorias(p);
}

// ── USUARIOS ──────────────────────────────────────────────────────────────────

function getPaginacionUsuariosHTML(totalPaginas) {
    return generarPaginacionHTML(paginaActualUsuarios, totalPaginas,
        'cambiarPaginaUsuarios', 'irAPaginaUsuarios', 'inputPaginaUsuarios');
}
function cambiarPaginaUsuarios(nuevaPagina) {
    if (nuevaPagina < 1 || nuevaPagina > totalPaginasUsuarios) return;
    paginaActualUsuarios = nuevaPagina;
    cargarUsuariosAdmin(busquedaUsuarioActual, false);
}
function irAPaginaUsuarios() {
    const input = document.getElementById('inputPaginaUsuarios');
    if (!input) return;
    let p = parseInt(input.value);
    if (isNaN(p) || p < 1) p = 1;
    else if (p > totalPaginasUsuarios) p = totalPaginasUsuarios;
    paginaActualUsuarios = p;
    cambiarPaginaUsuarios(p);
}

// ── CLIENTES ──────────────────────────────────────────────────────────────────

function getPaginacionClientesHTML(totalPaginas) {
    return generarPaginacionHTML(paginaActualClientes, totalPaginas,
        'cambiarPaginaClientes', 'irAPaginaClientes', 'inputPaginaClientes');
}
function cambiarPaginaClientes(nuevaPagina) {
    if (nuevaPagina < 1 || nuevaPagina > totalPaginasClientes) return;
    paginaActualClientes = nuevaPagina;
    cargarClientesAdmin(busquedaClienteActual, false);
}
function irAPaginaClientes() {
    const input = document.getElementById('inputPaginaClientes');
    if (!input) return;
    let p = parseInt(input.value);
    if (isNaN(p) || p < 1) p = 1;
    else if (p > totalPaginasClientes) p = totalPaginasClientes;
    cambiarPaginaClientes(p);
}

// ── VENTAS ────────────────────────────────────────────────────────────────────

function getPaginacionVentasHTML(totalPaginas) {
    return generarPaginacionHTML(paginaActualVentas, totalPaginas,
        'cambiarPaginaVentas', 'irAPaginaVentas', 'inputPaginaVentas');
}
function cambiarPaginaVentas(nuevaPagina) {
    if (nuevaPagina < 1 || nuevaPagina > totalPaginasVentas) return;
    paginaActualVentas = nuevaPagina;
    cargarVentasAdmin(false);
}
function irAPaginaVentas() {
    const input = document.getElementById('inputPaginaVentas');
    if (!input) return;
    let p = parseInt(input.value);
    if (isNaN(p) || p < 1) p = 1;
    else if (p > totalPaginasVentas) p = totalPaginasVentas;
    cambiarPaginaVentas(p);
}

// ── RETIROS ───────────────────────────────────────────────────────────────────

function getPaginacionRetirosHTML(totalPaginas) {
    return generarPaginacionHTML(paginaActualRetiros, totalPaginas,
        'cambiarPaginaRetiros', 'irAPaginaRetiros', 'inputPaginaRetiros');
}
function cambiarPaginaRetiros(nuevaPagina) {
    const total = Math.ceil(retirosData.length / retirosPorPagina);
    if (nuevaPagina < 1 || nuevaPagina > total) return;
    paginaActualRetiros = nuevaPagina;
    renderizarRetirosPagina();
}
function irAPaginaRetiros() {
    const input = document.getElementById('inputPaginaRetiros');
    if (!input) return;
    let p = parseInt(input.value);
    const total = Math.ceil(retirosData.length / retirosPorPagina);
    if (isNaN(p) || p < 1) p = 1;
    else if (p > total) p = total;
    cambiarPaginaRetiros(p);
}

// ── SESIONES DE CAJA ──────────────────────────────────────────────────────────

function getPaginacionSesionesHTML(totalPaginas) {
    return generarPaginacionHTML(paginaActualSesiones, totalPaginas,
        'cambiarPaginaSesiones', 'irAPaginaSesiones', 'inputPaginaSesiones');
}
function cambiarPaginaSesiones(nuevaPagina) {
    const total = Math.ceil(sesionesData.length / sesionesPorPagina);
    if (nuevaPagina < 1 || nuevaPagina > total) return;
    paginaActualSesiones = nuevaPagina;
    renderizarSesionesPagina();
}
function irAPaginaSesiones() {
    const input = document.getElementById('inputPaginaSesiones');
    if (!input) return;
    let p = parseInt(input.value);
    const total = Math.ceil(sesionesData.length / sesionesPorPagina);
    if (isNaN(p) || p < 1) p = 1;
    else if (p > total) p = total;
    cambiarPaginaSesiones(p);
}

// ── TARIFAS ───────────────────────────────────────────────────────────────────

function getPaginacionTarifasHTML(totalPaginas) {
    return generarPaginacionHTML(paginaActualTarifas, totalPaginas,
        'cambiarPaginaTarifas', 'irAPaginaTarifas', 'inputPaginaTarifas');
}
function cambiarPaginaTarifas(nuevaPagina) {
    const total = Math.ceil(todosLosProductosTarifas.length / productosPorPaginaTarifas);
    if (nuevaPagina < 1 || nuevaPagina > total) return;
    paginaActualTarifas = nuevaPagina;
    actualizarTablaTarifas();
}
function irAPaginaTarifas() {
    const input = document.getElementById('inputPaginaTarifas');
    if (!input) return;
    let p = parseInt(input.value);
    const total = Math.ceil(todosLosProductosTarifas.length / productosPorPaginaTarifas);
    if (isNaN(p) || p < 1) p = 1;
    else if (p > total) p = total;
    cambiarPaginaTarifas(p);
}

// ── DEVOLUCIONES ──────────────────────────────────────────────────────────────

function getPaginacionDevolucionesHTML(totalPaginas) {
    return generarPaginacionHTML(paginaActualDevoluciones, totalPaginas,
        'cambiarPaginaDevoluciones', 'irAPaginaDevoluciones', 'inputPaginaDevoluciones');
}
function cambiarPaginaDevoluciones(nuevaPagina) {
    if (nuevaPagina < 1 || nuevaPagina > totalPaginasDevoluciones) return;
    paginaActualDevoluciones = nuevaPagina;
    const orden = document.getElementById('devolucionesOrdenar')?.value || 'fecha_desc';
    const busqueda = document.getElementById('busquedaTicketDevolucion')?.value || '';
    cargarDevolucionesAdmin(orden, busqueda, false);
}
function irAPaginaDevoluciones() {
    const input = document.getElementById('inputPaginaDevoluciones');
    if (!input) return;
    let p = parseInt(input.value);
    if (isNaN(p) || p < 1) p = 1;
    else if (p > totalPaginasDevoluciones) p = totalPaginasDevoluciones;
    cambiarPaginaDevoluciones(p);
}

// ── LOGS ──────────────────────────────────────────────────────────────────────

function getPaginacionLogsHTML(totalPaginas) {
    return generarPaginacionHTML(paginaActualLogs, totalPaginas,
        'cambiarPaginaLogs', 'irAPaginaLogs', 'inputPaginaLogs');
}
function cambiarPaginaLogs(nuevaPagina) {
    if (nuevaPagina < 1 || nuevaPagina > totalPaginasLogs) return;
    paginaActualLogs = nuevaPagina;
    cargarLogs({ tipo: window.filtroTipoLog, fecha: window.filtroFechaLog, pagina: paginaActualLogs });
}
function irAPaginaLogs() {
    const input = document.getElementById('inputPaginaLogs');
    if (!input) return;
    let p = parseInt(input.value);
    if (isNaN(p) || p < 1) p = 1;
    else if (p > totalPaginasLogs) p = totalPaginasLogs;
    cambiarPaginaLogs(p);
}

// ── HISTORIAL DE PRECIOS ──────────────────────────────────────────────────────

function getPaginacionHistorialHTML(totalPaginas) {
    return generarPaginacionHTML(paginaActualHistorial, totalPaginas,
        'cambiarPaginaHistorial', 'irAPaginaHistorial', 'inputPaginaHistorial');
}
function cambiarPaginaHistorial(nuevaPagina) {
    if (nuevaPagina < 1 || nuevaPagina > totalPaginasHistorial) return;
    paginaActualHistorial = nuevaPagina;
    cargarHistorialPrecios({ pagina: paginaActualHistorial });
}
function irAPaginaHistorial() {
    const input = document.getElementById('inputPaginaHistorial');
    if (!input) return;
    let p = parseInt(input.value);
    if (isNaN(p) || p < 1) p = 1;
    else if (p > totalPaginasHistorial) p = totalPaginasHistorial;
    cambiarPaginaHistorial(p);
}

// ── BACKUPS ───────────────────────────────────────────────────────────────────

function renderBackupPagination() {
    const paginationContainer = document.getElementById('backupPagination');
    if (!paginationContainer) return;
    const totalPages = Math.ceil(backupData.length / itemsPerPage);
    paginationContainer.innerHTML = totalPages > 1
        ? generarPaginacionHTML(currentPage, totalPages,
            'renderBackupPage', 'irAPaginaBackups', 'inputPaginaBackups')
        : '';
    setTimeout(() => ajustarAnchoInput(document.getElementById('inputPaginaBackups')), 100);
}
function irAPaginaBackups() {
    const input = document.getElementById('inputPaginaBackups');
    if (!input) return;
    const totalPages = Math.ceil(backupData.length / itemsPerPage);
    let p = parseInt(input.value);
    if (isNaN(p) || p < 1) p = 1;
    else if (p > totalPages) p = totalPages;
    renderBackupPage(p);
}