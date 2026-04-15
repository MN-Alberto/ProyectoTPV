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
        'diario': 'Informe Diario (Hoy)',
        'semanal': 'Informe Semanal (Últimos 7 días)',
        'mensual': 'Informe Mensual (Mes actual)',
        'anual': 'Informe Anual (Año actual)'
    };

    document.getElementById('adminTitulo').textContent = titulos[periodo];
    contenedor.innerHTML = `
        <div style="text-align:center;padding:60px 20px">
            <i class="fas fa-chart-bar" style="font-size:4rem;color:#cbd5e1;margin-bottom:20px"></i>
            <h3 style="color:#64748b;margin-bottom:10px">${titulos[periodo]}</h3>
            <p style="color:#94a3b8;margin-bottom:30px">Haz click en el botón para generar el informe</p>
            <div style="display:flex;gap:15px;justify-content:center">
                <button class="btn-tpv" onclick="cargarInforme('${periodo}')" style="padding:15px 30px;font-size:1.1rem">
                    <i class="fas fa-play"></i> Generar Ahora
                </button>
                <button class="btn-tpv" onclick="cargarInforme('${periodo}', true)" style="padding:15px 30px;font-size:1.1rem;background:var(--bg-secondary);color:var(--text-main);border:1px solid var(--border-main)">
                    <i class="fas fa-clock"></i> En Segundo Plano
                </button>
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

    const calcularTendencia = (actual, anterior) => {
        if (!anterior || anterior === 0) return null;
        const diff = ((actual - anterior) / anterior) * 100;
        const icon = diff >= 0 ? 'fa-arrow-up' : 'fa-arrow-down';
        const color = diff >= 0 ? '#059669' : '#dc2626';
        return `<span style="color:${color};font-size:0.75rem;font-weight:600;margin-left:8px">
                    <i class="fas ${icon}"></i> ${Math.abs(diff).toFixed(1)}%
                </span>`;
    };

    const vAct = data.ventas.periodoActual;
    const vAnt = data.ventas.periodoAnterior;

    const html = `
        <div class="reports-container">
            <!-- Resumen de Ventas -->
            <div class="report-card">
                <div class="report-card-header"><i class="fas fa-shopping-cart"></i> Resumen de Ventas</div>
                <div class="report-card-body">
                    <div class="report-stat">
                        <span class="label">Ventas Totales:</span>
                        <div style="display:flex;align-items:center">
                            <span class="value">${vAct.bruto.toFixed(2)} €</span>
                            ${calcularTendencia(vAct.bruto, vAnt.bruto) || ''}
                        </div>
                    </div>
                    <div class="report-stat">
                        <span class="label">Tickets Emitidos:</span>
                        <div style="display:flex;align-items:center">
                            <span class="value">${vAct.tickets}</span>
                            ${calcularTendencia(vAct.tickets, vAnt.tickets) || ''}
                        </div>
                    </div>
                    <div class="report-stat">
                        <span class="label">Ticket Medio:</span>
                        <span class="value">${vAct.ticket_medio.toFixed(2)} €</span>
                    </div>
                    <div class="report-divider"></div>
                    <div class="report-substat"><span>Efectivo: ${vAct.metodos.efectivo.toFixed(2)} €</span></div>
                    <div class="report-substat"><span>Tarjeta: ${vAct.metodos.tarjeta.toFixed(2)} €</span></div>
                    <div class="report-substat"><span>Bizum: ${vAct.metodos.bizum.toFixed(2)} €</span></div>
                    <div class="report-substat"><span style="color:#dc2626">Descuentos: ${vAct.descuentos.toFixed(2)} €</span></div>
                    <div class="report-substat"><span style="color:#dc2626">Devoluciones: ${data.devoluciones_detalle.total.toFixed(2)} €</span></div>
                    <div class="report-divider"></div>
                    <p style="font-size:0.75rem;color:#64748b;margin-bottom:4px">Impuestos:</p>
                    ${vAct.iva.map(i => `
                        <div class="report-substat" style="display:flex;justify-content:space-between">
                            <span>IVA ${i.tipo}%</span><span>${i.cuota.toFixed(2)} €</span>
                        </div>`).join('')}
                </div>
            </div>

            <!-- Top 10 Productos -->
            <div class="report-card">
                <div class="report-card-header"><i class="fas fa-arrow-trend-up"></i> Top 10 Productos</div>
                <div class="report-card-body">
                    <table class="report-table">
                        <thead><tr><th>Producto</th><th>Und.</th><th>Ingresos</th><th>Margen</th></tr></thead>
                        <tbody>
                            ${data.productos_ranking.map(p => `
                                <tr>
                                    <td>${p.nombre}</td>
                                    <td style="text-align:center">${p.unidades}</td>
                                    <td style="text-align:right">${parseFloat(p.ingresos).toFixed(2)} €</td>
                                    <td style="text-align:right;color:#059669">${p.margen.toFixed(0)}%</td>
                                </tr>`).join('') || '<tr><td colspan="4">Sin datos</td></tr>'}
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Menos Vendidos -->
            <div class="report-card">
                <div class="report-card-header"><i class="fas fa-arrow-trend-down"></i> Menos Vendidos (Rotación)</div>
                <div class="report-card-body">
                    <table class="report-table">
                        <thead><tr><th>Producto</th><th>Unidades</th></tr></thead>
                        <tbody>
                            ${data.productos_bottom.map(p => `
                                <tr>
                                    <td>${p.nombre}</td>
                                    <td style="text-align:center">${p.unidades || 0}</td>
                                </tr>`).join('') || '<tr><td colspan="2">Sin datos</td></tr>'}
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Ventas por Categoría -->
            <div class="report-card">
                <div class="report-card-header"><i class="fas fa-tags"></i> Ventas por Categoría</div>
                <div class="report-card-body">
                    <table class="report-table">
                        <thead><tr><th>Categoría</th><th>Ingresos</th><th>Margen</th></tr></thead>
                        <tbody>
                            ${data.categorias_ranking.map(c => `
                                <tr>
                                    <td>${c.categoria}</td>
                                    <td style="text-align:right">${parseFloat(c.ingresos).toFixed(2)} €</td>
                                    <td style="text-align:right;color:#059669">${c.margen.toFixed(0)}%</td>
                                </tr>`).join('') || '<tr><td colspan="3">Sin datos</td></tr>'}
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Beneficios Generales -->
            <div class="report-card">
                <div class="report-card-header"><i class="fas fa-funnel-dollar"></i> Beneficios Generales</div>
                <div class="report-card-body">
                    <div class="report-stat"><span class="label">Ingresos:</span><span class="value">${data.margenes.ingresos.toFixed(2)} €</span></div>
                    <div class="report-stat"><span class="label">Coste Estimado:</span><span class="value" style="color:#ea580c">-${data.margenes.coste.toFixed(2)} €</span></div>
                    <div class="report-divider"></div>
                    <div class="report-stat"><span class="label">Beneficio Bruto:</span><span class="value" style="color:#059669">${data.margenes.beneficio.toFixed(2)} €</span></div>
                    <div class="report-stat"><span class="label">Margen Global:</span><span class="value" style="color:#059669">${data.margenes.porcentaje.toFixed(1)}%</span></div>
                </div>
            </div>

            <!-- Rendimiento Empleados -->
            <div class="report-card">
                <div class="report-card-header"><i class="fas fa-users"></i> Rendimiento Empleados</div>
                <div class="report-card-body">
                    <table class="report-table">
                        <thead><tr><th>Camarero</th><th>Tickets</th><th>Total</th><th>T. Medio</th></tr></thead>
                        <tbody>
                            ${data.empleados.map(e => `
                                <tr>
                                    <td>${e.nombre}</td>
                                    <td style="text-align:center">${e.tickets}</td>
                                    <td style="text-align:right">${parseFloat(e.total).toFixed(2)} €</td>
                                    <td style="text-align:right">${parseFloat(e.ticket_medio).toFixed(2)} €</td>
                                </tr>`).join('') || '<tr><td colspan="4">Sin datos</td></tr>'}
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Franjas Horarias -->
            <div class="report-card">
                <div class="report-card-header"><i class="fas fa-clock"></i> Horas Pico y Personal</div>
                <div class="report-card-body">
                    <table class="report-table">
                        <thead><tr><th>Hora</th><th>Tickets</th><th>Total</th><th>Sugerencia</th></tr></thead>
                        <tbody>
                            ${data.franjas.map(f => {
        const personal = f.tickets > 15 ? '3 pers.' : (f.tickets > 8 ? '2 pers.' : '1 pers.');
        return `<tr>
                                    <td>${f.hora}:00</td>
                                    <td style="text-align:center">${f.tickets}</td>
                                    <td style="text-align:right">${parseFloat(f.total).toFixed(2)} €</td>
                                    <td style="text-align:center;font-size:0.75rem;color:#64748b">${personal}</td>
                                </tr>`;
    }).join('') || '<tr><td colspan="4">Sin datos</td></tr>'}
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Informe de Caja -->
            <div class="report-card">
                <div class="report-card-header"><i class="fas fa-cash-register"></i> Informe de Caja</div>
                <div class="report-card-body">
                    <div class="report-stat"><span class="label">Fondo Inicial:</span><span class="value">${parseFloat(data.caja_resumen.fondo_inicial || 0).toFixed(2)} €</span></div>
                    <div class="report-stat"><span class="label">Efectivo Final:</span><span class="value">${parseFloat(data.caja_resumen.efectivo_final || 0).toFixed(2)} €</span></div>
                    <div class="report-stat"><span class="label">Retiros:</span><span class="value" style="color:#dc2626">-${parseFloat(data.caja_resumen.retiros || 0).toFixed(2)} €</span></div>
                    <div class="report-divider"></div>
                    <div class="report-stat">
                        <span class="label">Desajuste (Arqueos):</span>
                        <span class="value" style="color:${data.caja_resumen.desajuste >= 0 ? '#059669' : '#dc2626'}">
                            ${(data.caja_resumen.desajuste || 0).toFixed(2)} €
                        </span>
                    </div>
                    <p style="font-size:0.75rem;color:#64748b">Basado en ${data.caja_resumen.num_cierres} cierres registrados.</p>
                </div>
            </div>

            <!-- Devoluciones y Pérdidas -->
            <div class="report-card">
                <div class="report-card-header"><i class="fas fa-undo"></i> Devoluciones y Pérdidas</div>
                <div class="report-card-body">
                    <div class="report-stat"><span class="label">Total Devuelto:</span><span class="value" style="color:#dc2626">${data.devoluciones_detalle.total.toFixed(2)} €</span></div>
                    <div class="report-stat"><span class="label">% sobre Ventas:</span><span class="value">${data.devoluciones_detalle.porcentaje_ventas.toFixed(1)}%</span></div>
                    <div class="report-divider"></div>
                    <p style="font-size:0.75rem;color:#64748b;margin-bottom:8px">Top Productos Devueltos:</p>
                    <table class="report-table">
                        <tbody>
                            ${data.devoluciones_detalle.productos.map(p => `
                                <tr><td>${p.nombre}</td><td style="text-align:right">${p.veces} dev.</td></tr>
                            `).join('') || '<tr><td>Sin devoluciones</td></tr>'}
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Valor de Stock -->
            <div class="report-card">
                <div class="report-card-header"><i class="fas fa-warehouse"></i> Valor de Stock</div>
                <div class="report-card-body">
                    <div class="report-stat"><span class="label">Valor Venta:</span><span class="value">${parseFloat(data.stock.valor_venta || 0).toFixed(2)} €</span></div>
                    <div class="report-stat"><span class="label">Valor Coste:</span><span class="value">${parseFloat(data.stock.valor_coste || 0).toFixed(2)} €</span></div>
                    <div class="report-divider"></div>
                    <div class="report-stat"><span class="label">Sin Stock:</span><span class="value" style="color:#dc2626">${data.stock.sin_stock} prod.</span></div>
                    <div class="report-stat"><span class="label">Alertas Stock:</span><span class="value" style="color:#ea580c">${data.stock.alertas} prod.</span></div>
                </div>
            </div>
        </div>

        <div style="margin-top:30px;padding:20px;border-top:1px solid var(--border-main);display:flex;justify-content:center;">
            <button class="btn-tpv" onclick="exportarInformePDF('${periodo}')" style="padding:12px 25px;font-size:1.1rem;gap:10px;box-shadow:0 4px 15px rgba(0,0,0,0.1);">
                <i class="fas fa-file-pdf"></i> Exportar Informe Completo a PDF
            </button>
        </div>`;

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
    <div class="dashboard-graficos">
        <div class="grafico-card">
            <div class="grafico-header">
                <span class="grafico-titulo">Ventas últimos 7 días</span>
                <span id="dashTotalSemana" class="grafico-total">—</span>
            </div>
            <canvas id="graficaVentas" height="180"></canvas>
        </div>
        <div class="grafico-card">
            <div class="grafico-header">
                <span class="grafico-titulo">Ventas últimos 7 días</span>
                <span id="dashTotalPedidos" class="grafico-total">—</span>
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
            if (!tareas || tareas.length === 0) {
                lista.innerHTML = '<div style="text-align:center;padding:20px;color:#64748b">No hay tareas recientes.</div>';
                return;
            }

            lista.innerHTML = tareas.map(t => {
                const labels = {
                    'pendiente': 'Espera...',
                    'procesando': 'Generando...',
                    'completado': 'Listo',
                    'error': 'Fallido'
                };
                return `
                    <div class="task-item">
                        <div class="task-info">
                            <span class="task-name">${t.tipo === 'informe' ? 'Generación de Informe' : t.tipo}</span>
                            <span class="task-meta">ID: ${t.id} • ${new Date(t.creado_en).toLocaleString()}</span>
                        </div>
                        <div class="task-status status-${t.estado}">${labels[t.estado] || t.estado}</div>
                    </div>
                `;
            }).join('');

            // Actualizar indicador del header
            const activas = tareas.filter(t => t.estado === 'procesando' || t.estado === 'pendiente').length;
            const indicador = document.getElementById('adminTaskIndicator');
            const countText = document.getElementById('taskCountText');

            if (activas > 0) {
                indicador.style.display = 'flex';
                countText.textContent = `${activas} ${activas === 1 ? 'tarea activa' : 'tareas activas'}`;
            } else {
                indicador.style.display = 'none';
            }
        })
        .catch(err => console.error('Error actualizando tareas:', err));
}

// Iniciar polling global cada 10 segundos para el indicador de tareas
setInterval(actualizarListaTareas, 10000);
// Y una vez al cargar
document.addEventListener('DOMContentLoaded', () => { setTimeout(actualizarListaTareas, 2000); });
