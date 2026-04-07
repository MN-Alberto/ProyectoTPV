/**
 * admin.state.js
 * Variables de estado globales compartidas entre todos los módulos del panel de administración.
 */

// ── Sección activa ──────────────────────────────────────────────────────────
let seccionActual = '';

// ── Header HTML de tabla (se reutiliza para no regenerar en cada búsqueda) ──
let adminTablaHeaderHTML = '';

// ── IVA ─────────────────────────────────────────────────────────────────────
let tiposIva = [];
let mostrarConIva = false;

// ── Categorías ───────────────────────────────────────────────────────────────
let categoriasAdmin = [];

// ── Tema ─────────────────────────────────────────────────────────────────────
let temaActual = {};

// ── Scroll previsualización ──────────────────────────────────────────────────
let scrollPrevisualizacion = 0;

// ── Productos excluidos de ajuste/IVA masivo ─────────────────────────────────
let productosExcluidos = [];
let productosPrevisualizacionIVA = [];
let productosPrevisualizacionPrecios = [];

// ── Tarifas ──────────────────────────────────────────────────────────────────
let tarifasMostrarConIva = false;
let modoProgramacionTarifas = false;
let loteCambiosTarifas = {};
let tarifaBusquedaProducto = '';
let tarifaDataPendiente = null;

// ── Paginación: Productos ────────────────────────────────────────────────────
let productosData = [];
let paginaActualProductos = 1;
const productosPorPagina = 5;

// ── Paginación: Categorías ───────────────────────────────────────────────────
let categoriasData = [];
let paginaActualCategorias = 1;
const categoriasPorPagina = 6;

// ── Paginación: Usuarios ─────────────────────────────────────────────────────
let usuariosData = [];
let totalUsuariosData = 0;
let paginaActualUsuarios = 1;
let totalPaginasUsuarios = 1;
let busquedaUsuarioActual = '';
const usuariosPorPagina = 6;

// ── Paginación: Clientes ─────────────────────────────────────────────────────
let paginaActualClientes = 1;
let totalPaginasClientes = 1;
let totalClientes = 0;
let busquedaClienteActual = '';
const clientesPorPagina = 6;

// ── Paginación: Ventas ───────────────────────────────────────────────────────
let ventasData = [];
let paginaActualVentas = 1;
const ventasPorPagina = 6;

// ── Paginación: Retiros ──────────────────────────────────────────────────────
let retirosData = [];
let paginaActualRetiros = 1;
const retirosPorPagina = 6;

// ── Paginación: Sesiones de Caja ─────────────────────────────────────────────
let sesionesData = [];
let paginaActualSesiones = 1;
const sesionesPorPagina = 6;

// ── Paginación: Tarifas ──────────────────────────────────────────────────────
let paginaActualTarifas = 1;
const productosPorPaginaTarifas = 6;
let todosLosProductosTarifas = [];
let productosOriginalesTarifas = [];

// ── Paginación: Devoluciones ─────────────────────────────────────────────────
let paginaActualDevoluciones = 1;
const devolucionesPorPagina = 6;
let totalPaginasDevoluciones = 1;

// ── Paginación: Logs ─────────────────────────────────────────────────────────
let paginaActualLogs = 1;
let totalPaginasLogs = 1;
const logsPorPagina = 6;
if (window.filtroTipoLog === undefined) window.filtroTipoLog = '';
if (window.filtroFechaLog === undefined) window.filtroFechaLog = '';

// ── Paginación: Historial de precios ─────────────────────────────────────────
let paginaActualHistorial = 1;
let totalPaginasHistorial = 1;
const historialPorPagina = 6;

// ── Carrusel de categorías ───────────────────────────────────────────────────
let productosCategoriaActual = [];
let indexProductoActual = 0;

// ── Carrusel de compras de cliente ───────────────────────────────────────────
let currentSaleSlide = 0;

// ── Proveedores ──────────────────────────────────────────────────────────────
let proveedorActualId = null;

// ── Backups ───────────────────────────────────────────────────────────────────
let backupData = [];
let currentPage = 1;
const itemsPerPage = 3;

// ── Timers de debounce ───────────────────────────────────────────────────────
let debounceTimer;
let debounceTimerUsuarios;
let debounceTimerCategorias;
let debounceTimerProveedores;
let debounceTimerClientes;
let debounceTimerVentas;
let debounceTimerDevoluciones;
let debounceTimerIVA;
let debounceTimerPrecios;

// ── Constantes de ordenación ──────────────────────────────────────────────────
const OPCIONES_ORDEN = [
    { value: '', text: 'Sin orden' },
    { value: 'nombre_asc', text: 'Orden alfabético A-Z' },
    { value: 'nombre_desc', text: 'Orden alfabético Z-A' },
    { value: 'id_asc', text: 'ID menor a mayor' },
    { value: 'id_desc', text: 'ID mayor a menor' },
    { value: 'precio_asc', text: 'Precio menor a mayor' },
    { value: 'precio_desc', text: 'Precio mayor a menor' },
    { value: 'stock_asc', text: 'Stock menor a mayor' },
    { value: 'stock_desc', text: 'Stock mayor a menor' },
];