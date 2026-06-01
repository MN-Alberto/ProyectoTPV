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

    document.querySelectorAll('.nav-item-premium[data-seccion], .submenu-item-premium[data-seccion]').forEach(btn => {
        btn.addEventListener('click', () => {
            // Actualizar botón activo
            document.querySelectorAll('.nav-item-premium, .submenu-item-premium').forEach(b => b.classList.remove('activa'));
            btn.classList.add('activa');

            const seccion = btn.dataset.seccion;
            const tituloElement = document.getElementById('adminTitulo');
            if (tituloElement) tituloElement.textContent = TITULOS[seccion] ?? seccion;

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

    // Toggle submenus premium
    document.querySelectorAll('.nav-item-premium.has-submenu').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const submenuId = this.id === 'btnTarifas' ? 'submenuTarifas' : 
                            this.id === 'btnInformes' ? 'submenuInformes' : 
                            this.id === 'btnConfig' ? 'submenuConfig' : null;
            
            if (submenuId) {
                const submenu = document.getElementById(submenuId);
                const isVisible = submenu.style.display === 'flex';
                
                // Cerrar otros
                document.querySelectorAll('.nav-submenu-premium').forEach(s => s.style.display = 'none');
                document.querySelectorAll('.nav-item-premium.has-submenu .arrow').forEach(a => a.style.transform = 'rotate(0deg)');

                if (!isVisible) {
                    submenu.style.display = 'flex';
                    this.querySelector('.arrow').style.transform = 'rotate(180deg)';
                }
            }
        });
    });

    // Cerrar submenus al hacer click fuera
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.nav-item-premium.has-submenu') && !e.target.closest('.nav-submenu-premium')) {
            document.querySelectorAll('.nav-submenu-premium').forEach(s => s.style.display = 'none');
            document.querySelectorAll('.nav-item-premium.has-submenu .arrow').forEach(a => a.style.transform = 'rotate(0deg)');
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

    // ======================== POLLING: EFECTIVO EN CAJA (cada 10s) ========================
    function actualizarIndicadorCaja() {
        fetch('api/caja.php?accion=estado&_=' + Date.now())
            .then(r => r.json())
            .then(data => {
                if (!data.success) return;

                const elValor = document.getElementById('adminEfectivoValor');
                const elLabel = document.getElementById('adminEfectivoLabel');
                const elEstado = document.getElementById('adminEstadoSistema');
                const elContainer = document.getElementById('adminIndicadorCaja');

                if (!elValor) return;

                if (data.cajaAbierta) {
                    const fmt = data.importeActual.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                    elValor.textContent = fmt + ' €';
                    if (elLabel) elLabel.textContent = 'Efectivo en Caja:';
                    if (elEstado) {
                        elEstado.textContent = 'Online';
                        elEstado.style.color = '#059669';
                    }
                    if (elContainer) {
                        elContainer.style.background = '';
                        elContainer.style.borderColor = '';
                    }
                } else {
                    const cambio = (data.cambioSiguiente || 0).toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                    elValor.textContent = cambio + ' €';
                    if (elLabel) elLabel.textContent = 'Fondo Siguiente Turno:';
                    if (elEstado) {
                        elEstado.textContent = 'Offline (Caja Cerrada)';
                        elEstado.style.color = '#dc2626';
                    }
                    if (elContainer) {
                        elContainer.style.background = '#fee2e2';
                        elContainer.style.borderColor = '#fecaca';
                    }
                }
            })
            .catch(() => {}); // Silenciar errores de red
    }

    // Polling cada 10 segundos
    setInterval(actualizarIndicadorCaja, 10000);
});
