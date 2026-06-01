/**
 * admin-informes.js
 * Carga, renderizado y exportación a PDF de los informes del panel de administración.
 * Depende de: admin-state.js, admin-utils.js
 */

/**
 * Muestra la sección de informes en el panel central.
 * @param {string} periodo - 'diario' | 'semanal' | 'mensual' | 'anual'
 */
function mostrarSeccionInformes(periodo = 'diario') {
    seccionActual = 'informe-' + periodo;
    const contenedor = document.getElementById('adminContenido');
    const panel = document.querySelector('.admin-content-panel');
    if (panel) panel.classList.add('informes-view');

    const titulos = {
        'diario': { t: 'Informe Diario', s: 'Estado de ventas y operaciones de hoy', i: 'fa-calendar-day', g: 'linear-gradient(135deg, #3b82f6, #2563eb)' },
        'semanal': { t: 'Informe Semanal', s: 'Rendimiento de los últimos 7 días', i: 'fa-calendar-week', g: 'linear-gradient(135deg, #8b5cf6, #7c3aed)' },
        'mensual': { t: 'Informe Mensual', s: 'Balance consolidado del mes actual', i: 'fa-calendar-alt', g: 'linear-gradient(135deg, #10b981, #059669)' },
        'anual': { t: 'Informe Anual', s: 'Resumen ejecutivo del año en curso', i: 'fa-chart-pie', g: 'linear-gradient(135deg, #f59e0b, #d97706)' }
    };

    const config = titulos[periodo] || titulos.diario;

    contenedor.innerHTML = `
        <div class="reports-selection-view animate-fade-in">
            ${getPremiumHeaderHTML(config.i, config.t, config.s, config.g)}
            
            <div style="display:flex; flex-direction:column; align-items:center; justify-content:center; padding:80px 20px; background:var(--bg-card); border-radius:20px; border:1px solid var(--border-main); margin-top:20px; box-shadow:var(--shadow-sm)">
                <div style="width:100px; height:100px; background:${config.g}; border-radius:30px; display:flex; align-items:center; justify-content:center; margin-bottom:25px; box-shadow:0 15px 30px -10px rgba(0,0,0,0.2)">
                    <i class="fas ${config.i}" style="font-size:3rem; color:white"></i>
                </div>
                <h2 style="font-size:1.8rem; font-weight:800; color:var(--text-main); margin-bottom:10px">${config.t}</h2>
                <p style="color:var(--text-muted); font-size:1.1rem; margin-bottom:40px; text-align:center; max-width:400px">
                    El sistema procesará todas las ventas, arqueos y movimientos de stock para generar este reporte detallado.
                </p>
                
                <div style="display:flex; gap:20px; flex-wrap:wrap; justify-content:center">
                    <button class="btn-tpv" onclick="cargarInforme('${periodo}')" style="padding:18px 40px; font-size:1.1rem; border-radius:15px; display:flex; align-items:center; gap:12px; background:${config.g}; border:none; box-shadow:0 10px 20px -5px rgba(0,0,0,0.1)">
                        <i class="fas fa-play"></i> Generar Informe Ahora
                    </button>
                    <button class="btn-tpv" onclick="cargarInforme('${periodo}', true)" style="padding:18px 40px; font-size:1.1rem; border-radius:15px; display:flex; align-items:center; gap:12px; background:var(--bg-secondary); color:var(--text-main); border:1px solid var(--border-main)">
                        <i class="fas fa-clock"></i> En Segundo Plano
                    </button>
                </div>
                
                <p style="margin-top:30px; font-size:0.85rem; color:var(--text-muted); opacity:0.7">
                    <i class="fas fa-info-circle"></i> Los informes en segundo plano son recomendados para periodos largos (Mensual/Anual).
                </p>
            </div>
        </div>
    `;
}

function cargarInforme(periodo, background = false) {
    const contenedor = document.getElementById('adminContenido');
    
    // Si ya terminó el background, no ponemos el spinner de "Generando" sino uno de "Obteniendo datos"
    if (document.getElementById(`task-status-container`)) {
         contenedor.innerHTML = `<div class="reports-loading"><i class="fas fa-database fa-spin"></i> Obteniendo informes finalizados...</div>`;
    } else {
         contenedor.innerHTML = `<div class="reports-loading"><i class="fas fa-spinner fa-spin"></i> ${background ? 'Iniciando tarea en segundo plano...' : 'Generando informes...'}</div>`;
    }

    const url = `api/informes.php?periodo=${periodo}${background ? '&background=1' : ''}`;

    fetch(url)
        .then(res => res.json())
        .then(data => {
            if (data.error) {
                contenedor.innerHTML = `<div class="error-container" style="padding:20px;color:#dc2626;text-align:center">
                    <i class="fas fa-exclamation-triangle fa-2x"></i>
                    <p style="margin-top:10px">Error: ${data.error}</p>
                    <button class="btn-tpv" onclick="mostrarSeccionInformes('${periodo}')" style="margin-top:10px">Reintentar</button>
                </div>`;
                return;
            }

            if (background && data.taskId) {
                monitorizarTarea(data.taskId, periodo);
            } else {
                renderizarInformes(data, periodo);
            }
        })
        .catch(err => {
            console.error('Error cargando informes:', err);
            contenedor.innerHTML = '<p class="sin-productos">Error al generar informes: ' + (err.message || 'Error desconocido') + '</p>';
        });
}

/**
 * Polling para tareas en segundo plano.
 */
function monitorizarTarea(taskId, periodo) {
    const contenedor = document.getElementById('adminContenido');
    contenedor.innerHTML = `
        <div class="reports-loading" id="task-status-container">
            <div id="task-status-${taskId}">
                <i class="fas fa-cog fa-spin" style="font-size:3rem;margin-bottom:20px;color:var(--accent-main)"></i>
                <h3>Generando Informe #${taskId}</h3>
                <p id="task-msg">Procesando datos en el servidor...</p>
                <div style="margin-top:20px;color:#94a3b8;font-size:0.9rem">Puedes salir de esta página, el proceso continuará.</div>
                <button class="btn-tpv" onclick="mostrarSeccionInformes('${periodo}')" style="margin-top:30px;background:none;border:1px solid var(--border-main);color:var(--text-main)">
                    Volver más tarde
                </button>
            </div>
        </div>
    `;

    const interval = setInterval(() => {
        fetch(`api/informes.php?check_task=${taskId}`)
            .then(res => res.json())
            .then(task => {
                const statusDiv = document.getElementById(`task-status-${taskId}`);
                const taskMsg = document.getElementById('task-msg');
                const taskIcon = statusDiv ? statusDiv.querySelector('i') : null;
                const taskTitle = statusDiv ? statusDiv.querySelector('h3') : null;

                if (task.estado === 'completado') {
                    clearInterval(interval);
                    if (taskTitle) taskTitle.textContent = '¡Informe Completado!';
                    if (taskIcon) {
                        taskIcon.className = 'fas fa-check-circle';
                        taskIcon.style.color = '#059669';
                        taskIcon.classList.remove('fa-spin');
                    }
                    if (taskMsg) taskMsg.textContent = 'El proceso ha finalizado con éxito.';
                    
                    Swal.fire({
                        title: 'Informe Listo',
                        text: 'El informe en segundo plano ha finalizado.',
                        icon: 'success',
                        confirmButtonText: 'Ver ahora'
                    }).then(() => {
                        cargarInforme(periodo);
                    });
                } else if (task.estado === 'error') {
                    clearInterval(interval);
                    if (taskTitle) taskTitle.textContent = 'Error en la Tarea';
                    if (taskIcon) {
                        taskIcon.className = 'fas fa-times-circle';
                        taskIcon.style.color = '#dc2626';
                        taskIcon.classList.remove('fa-spin');
                    }
                    if (taskMsg) taskMsg.innerHTML = `<span style="color:#dc2626">Error: ${task.mensaje_error}</span>`;
                }
            });
    }, 3000);
}

/**
 * Renderiza todos los bloques de informes.
 */
function renderizarInformes(data, periodo) {
    const contenedor = document.getElementById('adminContenido');
    window.ultimoInformeData = data;
    window.ultimoInformePeriodo = periodo;

    const titulosMap = {
        'diario': { t: 'Informe Diario', s: 'Resumen detallado de las operaciones de hoy', i: 'fa-calendar-day', g: 'linear-gradient(135deg, #3b82f6, #2563eb)' },
        'semanal': { t: 'Informe Semanal', s: 'Análisis de rendimiento de los últimos 7 días', i: 'fa-calendar-week', g: 'linear-gradient(135deg, #8b5cf6, #7c3aed)' },
        'mensual': { t: 'Informe Mensual', s: 'Balance consolidado del mes en curso', i: 'fa-calendar-alt', g: 'linear-gradient(135deg, #10b981, #059669)' },
        'anual': { t: 'Informe Anual', s: 'Resumen ejecutivo del ejercicio anual', i: 'fa-chart-pie', g: 'linear-gradient(135deg, #f59e0b, #d97706)' }
    };

    const config = titulosMap[periodo] || titulosMap.diario;

    const formatTrend = (actual, anterior) => {
        if (!anterior || anterior === 0) return '';
        const diff = ((actual - anterior) / anterior) * 100;
        const type = diff >= 0 ? 'trend-up' : 'trend-down';
        const icon = diff >= 0 ? 'fa-arrow-up' : 'fa-arrow-down';
        return `<span class="trend-badge ${type}"><i class="fas ${icon}"></i> ${Math.abs(diff).toFixed(1)}%</span>`;
    };

    const vAct = data.ventas.periodoActual;
    const vAnt = data.ventas.periodoAnterior;

    let html = `
        <div class="reports-modern-view animate-fade-in">
            ${getPremiumHeaderHTML(config.i, config.t, config.s, config.g)}

            <!-- Summary Bar -->
            <div class="report-summary-bar">
                <div class="summary-item highlight">
                    <span class="label">Ingresos Brutos</span>
                    <div style="display:flex; align-items:baseline; gap:10px">
                        <span class="value">${vAct.bruto.toFixed(2)} €</span>
                        ${formatTrend(vAct.bruto, vAnt.bruto)}
                    </div>
                </div>
                <div class="summary-item">
                    <span class="label">Beneficio Estimado</span>
                    <span class="value" style="color: #059669">${data.margenes.beneficio.toFixed(2)} €</span>
                </div>
                <div class="summary-item">
                    <span class="label">Tickets</span>
                    <div style="display:flex; align-items:baseline; gap:10px">
                        <span class="value">${vAct.tickets}</span>
                        ${formatTrend(vAct.tickets, vAnt.tickets)}
                    </div>
                </div>
                <div class="summary-item">
                    <span class="label">Ticket Medio</span>
                    <span class="value">${vAct.ticket_medio.toFixed(2)} €</span>
                </div>
            </div>

            <div class="reports-modern-grid">
                <!-- Ventas Detalle -->
                <div class="report-modern-card">
                    <div class="report-card-header-modern">
                        <div class="header-title-box"><i class="fas fa-shopping-basket"></i> <h3>Desglose de Ventas</h3></div>
                    </div>
                    <div class="report-card-body-modern">
                        <div class="modern-stat-row"><span class="label">Efectivo</span><span class="value">${vAct.metodos.efectivo.toFixed(2)} €</span></div>
                        <div class="modern-stat-row"><span class="label">Tarjeta</span><span class="value">${vAct.metodos.tarjeta.toFixed(2)} €</span></div>
                        <div class="modern-stat-row"><span class="label">Bizum</span><span class="value">${vAct.metodos.bizum.toFixed(2)} €</span></div>
                        <div class="modern-stat-row"><span class="label">Descuentos</span><span class="value" style="color:#dc2626">-${vAct.descuentos.toFixed(2)} €</span></div>
                        <div class="modern-stat-row"><span class="label">Devoluciones</span><span class="value" style="color:#dc2626">-${data.devoluciones_detalle.total.toFixed(2)} €</span></div>
                        <div style="margin-top:15px; padding-top:15px; border-top:1px dashed var(--border-main)">
                             <p style="font-size:0.75rem; color:var(--text-muted); margin-bottom:8px">Impuestos (IVA):</p>
                             ${vAct.iva.map(i => `
                                <div class="modern-stat-row" style="border:none; padding:4px 0">
                                    <span class="label">IVA ${i.tipo}%</span><span class="value">${i.cuota.toFixed(2)} €</span>
                                </div>`).join('')}
                        </div>
                    </div>
                </div>

                <!-- Margenes -->
                <div class="report-modern-card">
                    <div class="report-card-header-modern">
                        <div class="header-title-box"><i class="fas fa-funnel-dollar"></i> <h3>Rentabilidad</h3></div>
                    </div>
                    <div class="report-card-body-modern">
                        <div class="modern-stat-row"><span class="label">Ingresos Netos</span><span class="value">${data.margenes.ingresos.toFixed(2)} €</span></div>
                        <div class="modern-stat-row"><span class="label">Coste Mercancía</span><span class="value" style="color:#ea580c">-${data.margenes.coste.toFixed(2)} €</span></div>
                        <div class="modern-stat-row" style="margin-top:10px; background:rgba(16,185,129,0.05); padding:10px; border-radius:10px; border-bottom:none">
                            <span class="label" style="color:#059669; font-weight:700">Beneficio Neto</span>
                            <span class="value" style="color:#059669">${data.margenes.beneficio.toFixed(2)} €</span>
                        </div>
                        <div class="modern-stat-row" style="border:none"><span class="label">Margen Comercial</span><span class="value" style="color:#059669">${data.margenes.porcentaje.toFixed(1)}%</span></div>
                    </div>
                </div>

                <!-- Top Productos -->
                <div class="report-modern-card" style="grid-column: span 2">
                    <div class="report-card-header-modern">
                        <div class="header-title-box"><i class="fas fa-trophy"></i> <h3>Top 10 Productos</h3></div>
                    </div>
                    <div class="report-card-body-modern" style="padding:0">
                        <table class="modern-table">
                            <thead><tr><th>Producto</th><th style="text-align:center">Unidades</th><th style="text-align:right">Ventas</th><th style="text-align:right">Margen</th></tr></thead>
                            <tbody>
                                ${data.productos_ranking.map(p => `
                                    <tr>
                                        <td style="font-weight:600">${p.nombre}</td>
                                        <td style="text-align:center">${p.unidades}</td>
                                        <td style="text-align:right; font-weight:700">${parseFloat(p.ingresos).toFixed(2)} €</td>
                                        <td style="text-align:right"><span style="color:#059669; font-weight:600">${p.margen.toFixed(0)}%</span></td>
                                    </tr>`).join('') || '<tr><td colspan="4" style="text-align:center; padding:30px">Sin datos suficientes</td></tr>'}
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Ventas por Categoría -->
                <div class="report-modern-card">
                    <div class="report-card-header-modern">
                        <div class="header-title-box"><i class="fas fa-tags"></i> <h3>Ventas por Categoría</h3></div>
                    </div>
                    <div class="report-card-body-modern" style="padding:0">
                        <table class="modern-table">
                            <thead><tr><th>Categoría</th><th style="text-align:right">Ingresos</th><th style="text-align:right">Margen</th></tr></thead>
                            <tbody>
                                ${data.categorias_ranking.map(c => `
                                    <tr>
                                        <td>${c.categoria}</td>
                                        <td style="text-align:right; font-weight:700">${parseFloat(c.ingresos).toFixed(2)} €</td>
                                        <td style="text-align:right; color:#059669">${c.margen.toFixed(0)}%</td>
                                    </tr>`).join('') || '<tr><td colspan="3" style="text-align:center; padding:30px">Sin datos</td></tr>'}
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Empleados -->
                <div class="report-modern-card">
                    <div class="report-card-header-modern">
                        <div class="header-title-box"><i class="fas fa-users"></i> <h3>Rendimiento Personal</h3></div>
                    </div>
                    <div class="report-card-body-modern" style="padding:0">
                        <table class="modern-table">
                            <thead><tr><th>Empleado</th><th style="text-align:center">Tickets</th><th style="text-align:right">Total</th></tr></thead>
                            <tbody>
                                ${data.empleados.map(e => `
                                    <tr>
                                        <td>${e.nombre}</td>
                                        <td style="text-align:center">${e.tickets}</td>
                                        <td style="text-align:right; font-weight:700">${parseFloat(e.total).toFixed(2)} €</td>
                                    </tr>`).join('') || '<tr><td colspan="3" style="text-align:center; padding:30px">Sin datos</td></tr>'}
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Caja y Arqueos -->
                <div class="report-modern-card">
                    <div class="report-card-header-modern">
                        <div class="header-title-box"><i class="fas fa-cash-register"></i> <h3>Estado de Caja</h3></div>
                    </div>
                    <div class="report-card-body-modern">
                        <div class="modern-stat-row"><span class="label">Fondo Inicial</span><span class="value">${parseFloat(data.caja_resumen.fondo_inicial || 0).toFixed(2)} €</span></div>
                        <div class="modern-stat-row"><span class="label">Efectivo Final</span><span class="value">${parseFloat(data.caja_resumen.efectivo_final || 0).toFixed(2)} €</span></div>
                        <div class="modern-stat-row"><span class="label">Retiros</span><span class="value" style="color:#dc2626">-${parseFloat(data.caja_resumen.retiros || 0).toFixed(2)} €</span></div>
                        <div class="modern-stat-row" style="margin-top:10px; border-top:1px solid var(--border-main); padding-top:15px">
                            <span class="label">Desajuste Total</span>
                            <span class="value" style="color:${data.caja_resumen.desajuste >= 0 ? '#059669' : '#dc2626'}">
                                ${(data.caja_resumen.desajuste || 0).toFixed(2)} €
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Stock -->
                <div class="report-modern-card">
                    <div class="report-card-header-modern">
                        <div class="header-title-box"><i class="fas fa-warehouse"></i> <h3>Valor de Inventario</h3></div>
                    </div>
                    <div class="report-card-body-modern">
                        <div class="modern-stat-row"><span class="label">Valor (PVP)</span><span class="value">${parseFloat(data.stock.valor_venta || 0).toFixed(2)} €</span></div>
                        <div class="modern-stat-row"><span class="label">Valor (Coste)</span><span class="value">${parseFloat(data.stock.valor_coste || 0).toFixed(2)} €</span></div>
                        <div class="modern-stat-row"><span class="label">Agotados</span><span class="value" style="color:#dc2626">${data.stock.sin_stock}</span></div>
                        <div class="modern-stat-row"><span class="label">Bajo Mínimos</span><span class="value" style="color:#f59e0b">${data.stock.alertas}</span></div>
                    </div>
                </div>
            </div>

            <div style="margin-top:20px; display:flex; justify-content:center; padding:40px 0; border-top:1px solid var(--border-main)">
                <button class="btn-tpv" onclick="exportarInformePDF('${periodo}')" style="padding:15px 40px; font-size:1.1rem; border-radius:50px; background:linear-gradient(135deg, #ef4444, #dc2626); box-shadow:0 10px 20px -5px rgba(220, 38, 38, 0.3)">
                    <i class="fas fa-file-pdf"></i> Descargar Informe PDF Profesional
                </button>
            </div>
        </div>
    `;

    contenedor.innerHTML = html;
}

/**
 * Genera un PDF profesional con los datos del informe actual.
 * @param {string} periodo - 'diario' | 'semanal' | 'mensual' | 'anual'
 */
function exportarInformePDF(periodo) {
    const data = window.ultimoInformeData;
    if (!data) {
        Swal.fire('Error', 'No hay datos de informe para exportar.', 'error');
        return;
    }

    try {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF('p', 'mm', 'a4');
        const tituloMap = { diario: 'Informe Diario', semanal: 'Informe Semanal', mensual: 'Informe Mensual', anual: 'Informe Anual' };

        doc.setFontSize(20);
        doc.setTextColor(40, 40, 40);
        doc.text(tituloMap[periodo] || 'Informe', 14, 20);
        doc.setFontSize(10);
        doc.setTextColor(100);
        doc.text('Generado el: ' + new Date().toLocaleString('es-ES'), 14, 28);

        let y = 38;

        const vAct = data.ventas.periodoActual;

        // Resumen de ventas
        doc.autoTable({
            startY: y,
            head: [['Concepto', 'Valor']],
            body: [
                ['Ventas Totales', vAct.bruto.toFixed(2) + ' €'],
                ['Tickets Emitidos', vAct.tickets],
                ['Ticket Medio', vAct.ticket_medio.toFixed(2) + ' €'],
                ['Efectivo', vAct.metodos.efectivo.toFixed(2) + ' €'],
                ['Tarjeta', vAct.metodos.tarjeta.toFixed(2) + ' €'],
                ['Bizum', vAct.metodos.bizum.toFixed(2) + ' €'],
                ['Descuentos', '-' + vAct.descuentos.toFixed(2) + ' €'],
                ['Devoluciones', '-' + data.devoluciones_detalle.total.toFixed(2) + ' €'],
            ],
            theme: 'striped',
            headStyles: { fillColor: [41, 128, 185], textColor: 255 },
            margin: { left: 14, right: 14 },
            tableWidth: 'auto',
        });

        y = doc.lastAutoTable.finalY + 10;

        // Top productos
        if (data.productos_ranking && data.productos_ranking.length > 0) {
            doc.setFontSize(13);
            doc.setTextColor(40, 40, 40);
            doc.text('Top 10 Productos', 14, y);
            y += 4;
            doc.autoTable({
                startY: y,
                head: [['Producto', 'Unidades', 'Ingresos', 'Margen']],
                body: data.productos_ranking.map(p => [p.nombre, p.unidades, parseFloat(p.ingresos).toFixed(2) + ' €', p.margen.toFixed(0) + '%']),
                theme: 'striped',
                headStyles: { fillColor: [16, 185, 129], textColor: 255 },
                margin: { left: 14, right: 14 },
            });
            y = doc.lastAutoTable.finalY + 10;
        }

        // Beneficios
        doc.setFontSize(13);
        doc.text('Beneficios Generales', 14, y);
        y += 4;
        doc.autoTable({
            startY: y,
            head: [['Concepto', 'Valor']],
            body: [
                ['Ingresos', data.margenes.ingresos.toFixed(2) + ' €'],
                ['Coste Estimado', '-' + data.margenes.coste.toFixed(2) + ' €'],
                ['Beneficio Bruto', data.margenes.beneficio.toFixed(2) + ' €'],
                ['Margen Global', data.margenes.porcentaje.toFixed(1) + '%'],
            ],
            theme: 'striped',
            headStyles: { fillColor: [124, 58, 237], textColor: 255 },
            margin: { left: 14, right: 14 },
        });

        doc.save(`informe_${periodo}_${new Date().toISOString().slice(0, 10)}.pdf`);
        Swal.fire('¡Exportado!', 'El informe PDF se ha descargado correctamente.', 'success');
    } catch (err) {
        console.error('Error exportando PDF:', err);
        Swal.fire('Error', 'No se pudo generar el PDF. Asegúrate de que jsPDF esté cargado.', 'error');
    }
}
// ═══════════════════════════════════════════════════════════════════════════════
// DASHBOARD — Gráficos de ventas
// ═══════════════════════════════════════════════════════════════════════════════

/**
 * HTML base del dashboard con los canvas para las gráficas de Chart.js.
 * Se inyecta en el contenedor al mostrar el panel principal.
 */
const HTML_DASHBOARD = `
    ${getPremiumHeaderHTML('fa-chart-line', 'Resumen de Actividad', 'Visualización en tiempo real del rendimiento de su negocio', 'linear-gradient(135deg, #6366f1, #4f46e5)')}
    <div class="dashboard-graficos">
        <div class="grafico-card">
            <div class="grafico-header">
                <div class="grafico-info">
                    <span class="grafico-titulo">Ventas últimos 7 días</span>
                    <span id="dashTotalSemana" class="grafico-total">—</span>
                </div>
            </div>
            <canvas id="graficaVentas" height="180"></canvas>
        </div>
        <div class="grafico-card">
            <div class="grafico-header">
                <div class="grafico-info">
                    <span class="grafico-titulo">Pedidos últimos 7 días</span>
                    <span id="dashTotalPedidos" class="grafico-total">—</span>
                </div>
            </div>
            <canvas id="graficaPedidos" height="180"></canvas>
        </div>
    </div>`;

/**
 * Carga los datos de ventas de los últimos 7 días desde la API y renderiza
 * dos gráficos en el dashboard usando Chart.js:
 *   1. Gráfico de barras con el total de ventas en euros por día.
 *   2. Gráfico de líneas con el número de pedidos por día.
 * También actualiza los totales acumulados en las cabeceras de cada gráfico.
 */
function cargarGraficoDashboard() {
    fetch('api/ventas.php')
        .then(res => res.json())
        .then(data => {
            const labels = data.map(d => {
                const fecha = new Date(d.dia);
                return fecha.toLocaleDateString('es-ES', { weekday: 'short', day: 'numeric', month: 'short' });
            });

            const ventas = data.map(d => parseFloat(d.total));
            const pedidos = data.map(d => parseInt(d.pedidos));

            const totalVentas = ventas.reduce((a, b) => a + b, 0);
            const totalPedidos = pedidos.reduce((a, b) => a + b, 0);

            document.getElementById('dashTotalSemana').textContent =
                totalVentas.toFixed(2).replace('.', ',') + ' €';
            document.getElementById('dashTotalPedidos').textContent = totalPedidos + ' ventas';

            const isDark = document.body.classList.contains('dark-mode');
            const gridColor = isDark ? '#374151' : '#f0f2f5';
            const textColor = isDark ? '#ffffff' : '#374151';

            const opcionesComunes = {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false }, ticks: { color: textColor } },
                    y: { beginAtZero: true, grid: { color: gridColor }, ticks: { color: textColor } }
                }
            };

            const actualizarGraficos = () => {
                const isDarkNow = document.body.classList.contains('dark-mode');
                const newGridColor = isDarkNow ? '#374151' : '#f0f2f5';
                const newTextColor = isDarkNow ? '#ffffff' : '#374151';

                const chartVentas = Chart.getChart('graficaVentas');
                const chartPedidos = Chart.getChart('graficaPedidos');

                if (chartVentas) {
                    chartVentas.options.scales.x.ticks.color = newTextColor;
                    chartVentas.options.scales.y.ticks.color = newTextColor;
                    chartVentas.options.scales.y.grid.color = newGridColor;
                    chartVentas.update();
                }

                if (chartPedidos) {
                    chartPedidos.options.scales.x.ticks.color = newTextColor;
                    chartPedidos.options.scales.y.ticks.color = newTextColor;
                    chartPedidos.options.scales.y.grid.color = newGridColor;
                    chartPedidos.update();
                }
            };

            window.removeEventListener('themeChange', actualizarGraficos);
            window.addEventListener('themeChange', actualizarGraficos);

            new Chart(document.getElementById('graficaVentas'), {
                type: 'bar',
                data: {
                    labels,
                    datasets: [{
                        data: ventas,
                        backgroundColor: 'rgba(5, 150, 105, 0.08)',
                        borderColor: '#059669',
                        borderWidth: 2,
                        borderRadius: 6,
                    }]
                },
                options: {
                    ...opcionesComunes,
                    scales: {
                        ...opcionesComunes.scales,
                        y: { ...opcionesComunes.scales.y, ticks: { callback: v => v + ' €' } }
                    }
                }
            });

            new Chart(document.getElementById('graficaPedidos'), {
                type: 'line',
                data: {
                    labels,
                    datasets: [{
                        data: pedidos,
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.15)',
                        borderWidth: 2,
                        pointBackgroundColor: '#3b82f6',
                        pointRadius: 4,
                        fill: true,
                        tension: 0.3
                    }]
                },
                options: opcionesComunes
            });
        })
        .catch(err => console.error('Error cargando gráficas:', err));
}

/**
 * Lógica del Centro de Tareas Global
 */
function abrirCentroTareas() {
    abrirModal('modalCentroTareas');
    actualizarListaTareas();
}

function actualizarListaTareas() {
    const lista = document.getElementById('listaTareasAdmin');
    
    fetch('api/informes.php?get_tasks=1')
        .then(res => res.json())
        .then(tareas => {
            if (!lista) return;

            if (!tareas || tareas.length === 0) {
                lista.innerHTML = '<div style="text-align:center;padding:40px;color:var(--text-muted)">No hay tareas recientes en el historial.</div>';
                return;
            }

            lista.innerHTML = tareas.map(t => {
                const config = {
                    'pendiente': { label: 'En Espera', icon: 'fa-clock', class: 'status-badge-pendiente' },
                    'procesando': { label: 'Procesando', icon: 'fa-sync-alt', class: 'status-badge-procesando' },
                    'completado': { label: 'Completado', icon: 'fa-check-circle', class: 'status-badge-completado' },
                    'error': { label: 'Fallido', icon: 'fa-exclamation-triangle', class: 'status-badge-error' }
                };
                const cfg = config[t.estado] || { label: t.estado, icon: 'fa-info-circle', class: '' };
                const params = typeof t.parametros === 'string' ? JSON.parse(t.parametros) : (t.parametros || {});
                const periodosMap = { 'diario': 'Diario', 'semanal': 'Semanal', 'mensual': 'Mensual', 'anual': 'Anual' };
                const periodoNombre = periodosMap[params.periodo] || params.periodo || '';
                const nombreTarea = t.tipo === 'informe' ? `Informe ${periodoNombre}` : t.tipo;

                return `
                    <div class="task-item-premium">
                        <div class="task-info-box">
                            <span class="task-title">${nombreTarea}</span>
                            <span class="task-meta-info">ID: #${t.id} • ${new Date(t.creado_en).toLocaleString()}</span>
                        </div>
                        <div class="task-status-badge ${cfg.class}">
                            <i class="fas ${cfg.icon}"></i> ${cfg.label}
                        </div>
                    </div>
                `;
            }).join('');

            // Actualizar indicadores (header y sidebar)
            const activas = tareas.filter(t => t.estado === 'procesando' || t.estado === 'pendiente').length;
            
            const indicadorHeader = document.getElementById('adminTaskIndicator');
            const countTextHeader = document.getElementById('taskCountText');
            const badgeSide = document.getElementById('badgeTareasSide');

            if (activas > 0) {
                if (indicadorHeader) indicadorHeader.style.display = 'flex';
                if (countTextHeader) countTextHeader.textContent = `${activas} ${activas === 1 ? 'tarea activa' : 'tareas activas'}`;
                
                if (badgeSide) {
                    badgeSide.textContent = activas;
                    badgeSide.style.display = 'block';
                }
            } else {
                if (indicadorHeader) indicadorHeader.style.display = 'none';
                if (badgeSide) badgeSide.style.display = 'none';
            }
        })
        .catch(err => console.error('Error actualizando tareas:', err));
}

function limpiarCentroTareas() {
    Swal.fire({
        title: '¿Limpiar historial?',
        text: "Se eliminará el registro de todas las tareas finalizadas. Las tareas en proceso no se verán afectadas.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sí, limpiar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('api/informes.php?clear_tasks=1')
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        actualizarListaTareas();
                        Swal.fire({
                            title: '¡Limpiado!',
                            text: 'El historial de tareas ha sido eliminado.',
                            icon: 'success',
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 3000
                        });
                    } else {
                        Swal.fire('Error', data.error || 'No se pudo limpiar el historial', 'error');
                    }
                })
                .catch(err => console.error('Error limpiando tareas:', err));
        }
    });
}

// Iniciar polling global cada 10 segundos para el indicador de tareas
setInterval(actualizarListaTareas, 10000);
// Y una vez al cargar
document.addEventListener('DOMContentLoaded', () => { setTimeout(actualizarListaTareas, 2000); });
