document.addEventListener('DOMContentLoaded', function() {
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
        'config-acciones': 'Configuración: Acciones',
        'config-ajustes': 'Configuración: Ajustes',
        'envios-aeat': 'Monitor Envíos AEAT',
        'config-fiscal': 'Configuración Fiscal'
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
            const panel = document.querySelector('.admin-content-panel');
            if (seccion.startsWith('config-') || seccion === 'configuracion') {
                dashboard.classList.add('admin-mode-config');
            } else {
                dashboard.classList.remove('admin-mode-config');
            }

            if (seccion.startsWith('informe-')) {
                panel.classList.add('informes-view');
            } else {
                panel.classList.remove('informes-view');
            }

            switch (seccion) {
                case 'dashboard':
                    if (typeof HTML_DASHBOARD !== 'undefined') {
                        document.getElementById('adminContenido').innerHTML = HTML_DASHBOARD;
                    }
                    if (typeof cargarGraficoDashboard === 'function') cargarGraficoDashboard();
                    break;
                case 'productos':
                    if (typeof cargarCategoriasAdmin === 'function') cargarCategoriasAdmin().then(() => {
                        if (typeof cargarProductosAdmin === 'function') cargarProductosAdmin();
                    });
                    break;
                case 'usuarios':
                    if (typeof cargarUsuariosAdmin === 'function') cargarUsuariosAdmin();
                    break;
                case 'ventas':
                    if (typeof cargarVentasAdmin === 'function') cargarVentasAdmin();
                    break;
                case 'retiros':
                    if (typeof cargarRetirosAdmin === 'function') cargarRetirosAdmin();
                    break;
                case 'devoluciones':
                    if (typeof cargarDevolucionesAdmin === 'function') cargarDevolucionesAdmin();
                    break;
                case 'proveedores':
                    if (typeof cargarProveedoresAdmin === 'function') cargarProveedoresAdmin();
                    break;
                case 'configuracion':
                    if (typeof cargarConfiguracion === 'function') cargarConfiguracion();
                    break;
                case 'config-tema':
                    if (typeof cargarConfiguracion === 'function') cargarConfiguracion('tema');
                    break;
                case 'config-acciones':
                    if (typeof cargarConfiguracion === 'function') cargarConfiguracion('acciones');
                    break;
                case 'config-ajustes':
                    if (typeof cargarConfiguracion === 'function') cargarConfiguracion('ajustes');
                    break;
                case 'logs':
                    if (typeof cargarLogs === 'function') cargarLogs();
                    break;
                case 'caja-sesiones':
                    if (typeof cargarCajaSesionesAdmin === 'function') cargarCajaSesionesAdmin();
                    break;
                case 'backups':
                    if (typeof mostrarPanelBackups === 'function') mostrarPanelBackups();
                    break;
                case 'categorias':
                    if (typeof cargarCategoriasAdmin === 'function') cargarCategoriasAdmin().then(() => {
                        if (typeof mostrarPanelCategorias === 'function') mostrarPanelCategorias();
                    });
                    break;
                case 'tarifa-iva':
                    if (typeof mostrarPanelCambiarIVA === 'function') mostrarPanelCambiarIVA();
                    break;
                case 'tarifa-ajuste':
                    if (typeof mostrarPanelAjustePrecios === 'function') mostrarPanelAjustePrecios();
                    break;
                case 'tarifa-prefijadas':
                    if (typeof mostrarPanelTarifasPrefijadas === 'function') mostrarPanelTarifasPrefijadas();
                    break;
                case 'historial-precios':
                    if (typeof mostrarPanelHistorialPrecios === 'function') mostrarPanelHistorialPrecios();
                    break;
                case 'clientes':
                    if (typeof cargarClientesAdmin === 'function') cargarClientesAdmin();
                    break;
                case 'informe-diario':
                    if (typeof mostrarSeccionInformes === 'function') mostrarSeccionInformes('diario');
                    break;
                case 'informe-semanal':
                    if (typeof mostrarSeccionInformes === 'function') mostrarSeccionInformes('semanal');
                    break;
                case 'informe-mensual':
                    if (typeof mostrarSeccionInformes === 'function') mostrarSeccionInformes('mensual');
                    break;
                case 'informe-anual':
                    if (typeof mostrarSeccionInformes === 'function') mostrarSeccionInformes('anual');
                    break;
                case 'envios-aeat':
                    if (typeof cargarEnviosAeat === 'function') cargarEnviosAeat();
                    break;
                case 'config-fiscal':
                    if (typeof cargarConfiguracionFiscal === 'function') cargarConfiguracionFiscal();
                    break;
            }
        });
    });

    // Toggle submenu de Configuración
    const btnConfig = document.getElementById('btnConfig');
    if (btnConfig) {
        btnConfig.addEventListener('click', function (e) {
            e.stopPropagation();
            var submenu = document.getElementById('submenuConfig');
            if (submenu) submenu.style.display = submenu.style.display === 'none' ? 'block' : 'none';

            // Cerrar otros submenus
            var subTarifas = document.getElementById('submenuTarifas');
            if (subTarifas) subTarifas.style.display = 'none';
            var subInformes = document.getElementById('submenuInformes');
            if (subInformes) subInformes.style.display = 'none';
        });
    }

    // Toggle submenu de Tarifas
    const btnTarifas = document.getElementById('btnTarifas');
    if (btnTarifas) {
        btnTarifas.addEventListener('click', function (e) {
            e.stopPropagation();
            var submenu = document.getElementById('submenuTarifas');
            if (submenu) submenu.style.display = submenu.style.display === 'none' ? 'block' : 'none';

            // Cerrar otros submenus
            var subConfig = document.getElementById('submenuConfig');
            if (subConfig) subConfig.style.display = 'none';
            var subInformes = document.getElementById('submenuInformes');
            if (subInformes) subInformes.style.display = 'none';
        });
    }

    // Toggle submenu de Informes
    const btnInformes = document.getElementById('btnInformes');
    if (btnInformes) {
        btnInformes.addEventListener('click', function (e) {
            e.stopPropagation();
            var submenu = document.getElementById('submenuInformes');
            if (submenu) submenu.style.display = submenu.style.display === 'none' ? 'block' : 'none';

            // Cerrar otros submenus
            var subConfig = document.getElementById('submenuConfig');
            if (subConfig) subConfig.style.display = 'none';
            var subTarifas = document.getElementById('submenuTarifas');
            if (subTarifas) subTarifas.style.display = 'none';
        });
    }

    // Cerrar submenus al hacer click fuera
    document.addEventListener('click', function (e) {
        var subTarifas = document.getElementById('submenuTarifas');
        var subConfig = document.getElementById('submenuConfig');
        var subInformes = document.getElementById('submenuInformes');
        
        if (btnTarifas && subTarifas && !btnTarifas.contains(e.target) && !subTarifas.contains(e.target)) {
            subTarifas.style.display = 'none';
        }
        if (btnConfig && subConfig && !btnConfig.contains(e.target) && !subConfig.contains(e.target)) {
            subConfig.style.display = 'none';
        }
        if (btnInformes && subInformes && !btnInformes.contains(e.target) && !subInformes.contains(e.target)) {
            subInformes.style.display = 'none';
        }
    });

    if (typeof HTML_DASHBOARD !== 'undefined') {
        const adminContenido = document.getElementById('adminContenido');
        if (adminContenido) adminContenido.innerHTML = HTML_DASHBOARD;
    }
    if (typeof cargarGraficoDashboard === 'function') cargarGraficoDashboard();

    // Cargar categorías y tipos de IVA al inicio
    if (typeof cargarCategoriasAdmin === 'function') cargarCategoriasAdmin();
    if (typeof cargarTiposIva === 'function') cargarTiposIva();
    if (typeof verificarCambiosIvaProgramados === 'function') verificarCambiosIvaProgramados();
    if (typeof verificarAjustesPreciosProgramados === 'function') verificarAjustesPreciosProgramados();
});
