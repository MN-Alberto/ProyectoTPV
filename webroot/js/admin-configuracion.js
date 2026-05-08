/**
 * admin-configuracion.js
 * Editor de tema (colores, fuentes, iconos, favicon), exportaciones semanales
 * y funciones de verificación de cambios programados.
 * Depende de: admin-state.js, admin-utils.js
 */

// ── CONSTANTES DE TEMA ────────────────────────────────────────────────────────

const FUENTES_DISPONIBLES = [
    'Inter', 'Roboto', 'Poppins', 'Open Sans', 'Montserrat',
    'Lato', 'Outfit', 'Nunito', 'Raleway', 'Source Sans 3'
];

const TEMA_DEFAULTS = {
    header_bg: '#1a1a2e', header_color: '#ffffff', header_font: 'Inter',
    footer_bg: '#1a1a2e', footer_color: '#e5e7eb', footer_font: 'Inter',
    header_icon: '',
    favicon: '',
    // Valores predeterminados para tamaño de tarjetas de productos
    producto_card_width: '200px',
    producto_card_height: '380px',
    producto_card_max_width: '250px',
    producto_card_max_height: '450px',
    // Grid y spacing
    producto_grid_columns: '4',
    producto_grid_gap: '10px',
    // Tamaños de fuente
    producto_nombre_font_size: '1.1rem',
    producto_precio_font_size: '1.15rem',
    producto_stock_font_size: '0.9rem',
    // Datos del TPV
    tpv_nombre: 'Mi TPV',
    tpv_telefono: '',
    tpv_direccion: ''
};

const SECCIONES_TEMA = [
    { id: 'header', titulo: 'Header (Cabecera)', icono: 'fa-heading', bgKey: 'header_bg', colorKey: 'header_color', fontKey: 'header_font' },
    { id: 'footer', titulo: 'Footer (Pie de página)', icono: 'fa-shoe-prints', bgKey: 'footer_bg', colorKey: 'footer_color', fontKey: 'footer_font' },
    { id: 'iconos', titulo: 'Iconos', icono: 'fa-icons', tipo: 'iconos' },
    { id: 'tamano_productos', titulo: 'Tamaño de Productos', icono: 'fa-th-large', tipo: 'tamano_productos' }
];

// ── CARGA Y RENDER ────────────────────────────────────────────────────────────

/**
 * Carga la configuración del tema desde la API y renderiza el editor.
 */
function cargarConfiguracion(subseccion = 'todas') {
    seccionActual = 'configuracion';
    adminTablaHeaderHTML = '';

    const contenedor = document.getElementById('adminContenido');
    contenedor.innerHTML = '<p style="text-align:center;padding:40px;color:var(--text-muted);">Cargando configuración...</p>';

    fetch('api/tema.php')
        .then(res => res.json())
        .then(config => {
            temaActual = { ...TEMA_DEFAULTS, ...config };
            renderEditorTema(subseccion);
        })
        .catch(err => {
            console.error('Error cargando configuración:', err);
            temaActual = { ...TEMA_DEFAULTS };
            renderEditorTema();
        });
}

/**
 * Renderiza el editor de tema completo.
 */
function renderEditorTema(subseccion = 'todas') {
    const contenedor = document.getElementById('adminContenido');
    let html = '<div class="tema-editor-premium animate-fade-in">';

    if (subseccion === 'todas' || subseccion === 'tema') {
        const configHeader = { t: 'Diseño y Experiencia', s: 'Configure la identidad visual y la ergonomía del terminal de venta', i: 'fa-magic', g: 'linear-gradient(135deg, #4f46e5, #9333ea)' };
        html += getPremiumHeaderHTML(configHeader.i, configHeader.t, configHeader.s, configHeader.g);

        const seccionTamanoProductos = SECCIONES_TEMA.find(s => s.tipo === 'tamano_productos');
        const seccionIconos = SECCIONES_TEMA.find(s => s.tipo === 'iconos');
        const seccionesVisuales = SECCIONES_TEMA.filter(s => s.bgKey);

        html += `
            <div class="premium-layout-grid" style="display: flex; flex-wrap: wrap; gap: 30px; margin-top: 30px; align-items: flex-start;">
                
                <!-- COLUMNA IZQUIERDA: CONFIGURACIÓN -->
                <div class="config-column" style="flex: 1; min-width: 400px; display: flex; flex-direction: column; gap: 25px;">
                    
                    <!-- GRUPO 1: IDENTIDAD VISUAL -->
                    <div class="premium-group-card">
                        <div class="group-header">
                            <i class="fas fa-fingerprint"></i> Identidad y Logotipos
                        </div>
                        <div class="premium-config-grid" style="display: grid; grid-template-columns: 1fr; gap: 20px;">
                            ${generarSeccionTema(seccionIconos)}
                        </div>
                    </div>

                    <!-- GRUPO 2: COLORES Y TIPOGRAFÍAS -->
                    <div class="premium-group-card">
                        <div class="group-header">
                            <i class="fas fa-paint-brush"></i> Paleta y Tipografía
                        </div>
                        <div class="premium-config-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                            ${seccionesVisuales.map(s => generarSeccionTema(s)).join('')}
                        </div>
                    </div>

                    <!-- GRUPO 3: INTERFAZ DE PRODUCTOS -->
                    <div class="premium-group-card">
                        <div class="group-header">
                            <i class="fas fa-th"></i> Cuadrícula de Venta
                        </div>
                        ${generarSeccionTema(seccionTamanoProductos)}
                    </div>
                </div>

                <!-- COLUMNA DERECHA: PREVIEW EN TIEMPO REAL -->
                <div class="preview-column" style="flex: 0 0 380px; align-self: start;">
                    <div class="preview-tpv-card" style="background: #ffffff; border: 4px solid #1e293b; border-radius: 24px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); overflow: hidden;">
                        <div style="background: #0f172a; padding: 12px 20px; display: flex; justify-content: space-between; align-items: center;">
                            <div style="display:flex; align-items:center; gap:8px;">
                                <div style="width:10px; height:10px; border-radius:50%; background:#ff5f56;"></div>
                                <div style="width:10px; height:10px; border-radius:50%; background:#ffbd2e;"></div>
                                <div style="width:10px; height:10px; border-radius:50%; background:#27c93f;"></div>
                            </div>
                            <span style="color: #64748b; font-size: 0.7rem; font-weight: 700; text-transform: uppercase;">Live Preview</span>
                        </div>
                        
                        <div id="tpv_live_preview" style="height: 600px; background: #f8fafc; display: flex; flex-direction: column; opacity: 1 !important; visibility: visible !important;">
                            <!-- Header Simulado -->
                            <div id="sim_header" style="height: 60px; padding: 0 20px; display: flex; align-items: center; justify-content: space-between; background: #1a1a2e; color: white;">
                                <div style="display:flex; align-items:center; gap:10px;">
                                    <div id="sim_icon" style="width:24px; height:24px;">
                                        <i class="fas fa-store"></i>
                                    </div>
                                    <span style="font-weight:700; font-size:0.85rem;">Terminal TPV [v2]</span>
                                </div>
                            </div>

                            <!-- Body Simulado -->
                            <div style="flex:1; padding:15px; background: #f1f5f9; overflow-y: auto;">
                                <div id="sim_grid" style="display: grid !important; grid-template-columns: repeat(4, 1fr); gap: 8px; min-height: 50px;">
                                    <div style="background:#fff; height:60px; border-radius:5px; border:1px solid #ddd;"></div>
                                    <div style="background:#fff; height:60px; border-radius:5px; border:1px solid #ddd;"></div>
                                    <div style="background:#fff; height:60px; border-radius:5px; border:1px solid #ddd;"></div>
                                    <div style="background:#fff; height:60px; border-radius:5px; border:1px solid #ddd;"></div>
                                </div>
                            </div>

                            <!-- Footer Simulado -->
                            <div id="sim_footer" style="height: 40px; padding: 0 20px; display: flex; align-items: center; justify-content: space-between; background: #1a1a2e; color: #94a3b8; font-size: 0.7rem;">
                                <span>Online</span>
                                <span id="sim_clock">12:00</span>
                            </div>
                        </div>
                    </div>
                </div>

                    <div style="margin-top: 20px; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 15px; padding: 15px; display: flex; gap: 15px;">
                        <div style="color:#3b82f6; font-size:1.2rem; padding-top:2px;">
                            <i class="fas fa-info-circle"></i>
                        </div>
                        <p style="margin:0; font-size:0.8rem; color:#1e40af; line-height:1.4;">
                            Los cambios se aplican instantáneamente en esta vista previa para que pueda ajustar los parámetros con precisión antes de guardar.
                        </p>
                    </div>
                </div>
            </div>

            <div class="premium-actions-bar" style="display:flex !important; visibility:visible !important; justify-content:flex-end; gap:15px; padding:25px; background:white; border-radius:20px; border:1px solid #e2e8f0; box-shadow:0 10px 25px rgba(0,0,0,0.1); margin-top: 40px; position: sticky; bottom: 20px; z-index: 1000;">
                <button type="button" class="btn-premium-secondary" onclick="restaurarTemaDefault()">
                    <i class="fas fa-undo"></i> Restaurar Valores
                </button>
                <button type="button" class="btn-premium-primary" onclick="guardarTema()">
                    <i class="fas fa-save"></i> Publicar Cambios
                </button>
            </div>

            <style>
                .premium-layout-grid {
                    display: flex !important;
                    flex-wrap: wrap !important;
                    gap: 30px;
                    margin-top: 30px;
                }
                @media (max-width: 1200px) {
                    .premium-layout-grid {
                        grid-template-columns: 1fr;
                    }
                    .preview-column {
                        position: relative !important;
                        top: 0 !important;
                    }
                }
                .premium-group-card {
                    background: var(--bg-card);
                    border: 1px solid var(--border-main);
                    border-radius: 20px;
                    padding: 0;
                    overflow: hidden;
                    box-shadow: var(--shadow-sm);
                }
                .group-header {
                    padding: 15px 25px;
                    background: rgba(0,0,0,0.02);
                    border-bottom: 1px solid var(--border-main);
                    font-weight: 800;
                    font-size: 0.85rem;
                    text-transform: uppercase;
                    letter-spacing: 1px;
                    color: var(--text-muted);
                    display: flex;
                    align-items: center;
                    gap: 10px;
                }
                .group-header i {
                    color: var(--accent-main);
                    font-size: 1rem;
                }
                .preview-tpv-card {
                    background: #fff;
                    border: 4px solid #1e293b;
                    border-radius: 24px;
                    overflow: hidden;
                    box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
                    width: 100%;
                }
                .tema-select-font {
                    width: 100%;
                    padding: 12px;
                    border-radius: 10px;
                    border: 1px solid var(--border-main);
                    background: var(--bg-panel);
                    color: var(--text-main);
                    font-weight: 600;
                    cursor: pointer;
                    outline: none;
                    transition: border-color 0.2s;
                }
                .tema-select-font:focus {
                    border-color: var(--accent-main);
                }
                .tamano-value {
                    float: right;
                    background: var(--accent-main);
                    color: white;
                    padding: 2px 8px;
                    border-radius: 5px;
                    font-size: 0.75rem;
                    font-weight: 800;
                }
                .premium-range {
                    width: 100%;
                    height: 6px;
                    background: #e2e8f0;
                    border-radius: 5px;
                    outline: none;
                    -webkit-appearance: none;
                }
                .premium-range::-webkit-slider-thumb {
                    -webkit-appearance: none;
                    width: 18px;
                    height: 18px;
                    background: var(--accent-main);
                    border-radius: 50%;
                    cursor: pointer;
                    border: 3px solid #fff;
                    box-shadow: 0 0 0 1px #cbd5e1;
                }
            </style>
        `;
    }

    if (subseccion === 'todas' || subseccion === 'acciones') {
        const configHeader = { t: 'Herramientas y Datos', s: 'Exportación de información y mantenimiento del sistema', i: 'fa-database', g: 'linear-gradient(135deg, #f59e0b, #d97706)' };
        html += getPremiumHeaderHTML(configHeader.i, configHeader.t, configHeader.s, configHeader.g);

        const exportaciones = [
            { tipo: 'ventas', titulo: 'Ventas Semanales', desc: 'Resumen de transacciones recientes', icono: 'fa-file-invoice-dollar', color: '#3b82f6' },
            { tipo: 'sesiones', titulo: 'Sesiones de Caja', desc: 'Aperturas y cierres de la semana', icono: 'fa-cash-register', color: '#10b981' },
            { tipo: 'retiros', titulo: 'Retiros de Efectivo', desc: 'Movimientos de salida de caja', icono: 'fa-money-bill-wave', color: '#f59e0b' },
            { tipo: 'devoluciones', titulo: 'Devoluciones', desc: 'Registro de tickets abonados', icono: 'fa-undo', color: '#ef4444' }
        ];

        html += `
            <div class="premium-config-grid animate-fade-in" style="margin-top:25px;">
                ${exportaciones.map(exp => `
                    <div class="premium-card export-card-premium">
                        <div style="display:flex; align-items:center; gap:15px; margin-bottom:20px;">
                            <div style="width:45px; height:45px; border-radius:12px; background:${exp.color}15; color:${exp.color}; display:flex; align-items:center; justify-content:center; font-size:1.3rem;">
                                <i class="fas ${exp.icono}"></i>
                            </div>
                            <div>
                                <h4 style="margin:0; font-size:1rem; font-weight:700;">${exp.titulo}</h4>
                                <p style="margin:0; font-size:0.8rem; color:var(--text-muted);">${exp.desc}</p>
                            </div>
                        </div>
                        
                        <div style="display:grid; grid-template-columns: repeat(2, 1fr); gap:10px;">
                            <button class="btn-premium-export" onclick="exportarSemanal('${exp.tipo}', 'json')">
                                <i class="fas fa-code"></i> JSON
                            </button>
                            <button class="btn-premium-export" onclick="exportarSemanal('${exp.tipo}', 'pdf')">
                                <i class="fas fa-file-pdf"></i> PDF
                            </button>
                            <button class="btn-premium-export" onclick="exportarSemanal('${exp.tipo}', 'excel')">
                                <i class="fas fa-file-excel"></i> Excel
                            </button>
                            <button class="btn-premium-export" onclick="exportarSemanal('${exp.tipo}', 'csv')">
                                <i class="fas fa-file-csv"></i> CSV
                            </button>
                        </div>
                    </div>
                `).join('')}
            </div>
            
            <style>
                .btn-premium-export {
                    padding: 10px;
                    border-radius: 10px;
                    border: 1px solid var(--border-main);
                    background: var(--bg-panel);
                    color: var(--text-main);
                    font-weight: 700;
                    font-size: 0.8rem;
                    cursor: pointer;
                    transition: all 0.2s ease;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    gap: 8px;
                }
                .btn-premium-export:hover {
                    background: var(--bg-main);
                    border-color: var(--accent-main);
                    color: var(--accent-main);
                    transform: translateY(-2px);
                }
                .export-card-premium:hover {
                    transform: translateY(-5px);
                    box-shadow: var(--shadow-md);
                    border-color: var(--accent-main);
                }
            </style>
        `;
    }



    html += '</div>';
    contenedor.innerHTML = html;

    // Inicializar previews
    setTimeout(() => {
        try { previsualizarTamanoProductos(); } catch(e) { console.error('P1:', e); }
        try { previsualizarTema(); } catch(e) { console.error('P2:', e); }
        try { previsualizarIcono(); } catch(e) { console.error('P3:', e); }
    }, 500);
}

// ── HELPERS DE SECCIÓN TEMA ───────────────────────────────────────────────────

function generarSelectFuente(id, valorActual) {
    const opciones = FUENTES_DISPONIBLES.map(f =>
        `<option value="${f}" ${f === valorActual ? 'selected' : ''} style="font-family:'${f}',sans-serif;">${f}</option>`
    ).join('');
    return `<select id="${id}" class="tema-select-font" onchange="previsualizarTema()">${opciones}</select>`;
}

function generarSeccionTema(seccion) {
    if (seccion.tipo === 'tamano_productos') {
        const widthVal = temaActual['producto_card_width'] || TEMA_DEFAULTS.producto_card_width;
        const heightVal = temaActual['producto_card_height'] || TEMA_DEFAULTS.producto_card_height;
        const columnsVal = temaActual['producto_grid_columns'] || TEMA_DEFAULTS.producto_grid_columns;
        const nombreFontVal = temaActual['producto_nombre_font_size'] || TEMA_DEFAULTS.producto_nombre_font_size;

        return `
            <div class="premium-card" style="padding: 25px;">
                <div class="tamano-productos-controles" style="display:grid; grid-template-columns: 1fr 1fr; gap: 25px;">
                    <div>
                        <div class="tema-campo">
                            <label class="tema-label">Ancho de Tarjeta <span class="tamano-value" id="val_producto_card_width">${widthVal}</span></label>
                            <input type="range" id="tema_producto_card_width" min="120" max="350" value="${parseInt(widthVal)}" oninput="previsualizarTamanoProductos()" class="premium-range">
                        </div>
                        <div class="tema-campo" style="margin-top:20px;">
                            <label class="tema-label">Alto de Tarjeta <span class="tamano-value" id="val_producto_card_height">${heightVal}</span></label>
                            <input type="range" id="tema_producto_card_height" min="150" max="550" value="${parseInt(heightVal)}" oninput="previsualizarTamanoProductos()" class="premium-range">
                        </div>
                    </div>
                    <div>
                        <div class="tema-campo">
                            <label class="tema-label">Columnas (Grid) <span class="tamano-value" id="val_producto_grid_columns">${columnsVal}</span></label>
                            <input type="range" id="tema_producto_grid_columns" min="2" max="10" value="${parseInt(columnsVal)}" oninput="previsualizarTamanoProductos()" class="premium-range">
                        </div>
                        <div class="tema-campo" style="margin-top:20px;">
                            <label class="tema-label">Tamaño Fuente <span class="tamano-value" id="val_producto_nombre_font_size">${nombreFontVal}</span></label>
                            <input type="range" id="tema_producto_nombre_font_size" min="0.7" max="1.8" step="0.05" value="${parseFloat(nombreFontVal)}" oninput="previsualizarTamanoProductos()" class="premium-range">
                        </div>
                    </div>
                </div>
            </div>`;
    }

    if (seccion.tipo === 'iconos') {
        const headerIconVal = temaActual['header_icon'] || '';
        const faviconVal = temaActual['favicon'] || '';
        return `
            <div class="premium-card" style="padding: 25px;">
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 25px;">
                    <div>
                        <label class="tema-label">Logo SVG (Cabecera)</label>
                        <textarea id="tema_header_icon" class="tema-textarea-icono" style="height:120px; font-family:monospace; border-radius:12px; border:1px solid var(--border-main); width:100%; padding:10px; font-size:0.75rem; background:var(--bg-panel); color:var(--text-main);"
                            placeholder='<svg ...> ... </svg>' oninput="previsualizarIcono()">${headerIconVal}</textarea>
                    </div>
                    <div style="display:flex; flex-direction:column; gap:15px;">
                        <label class="tema-label">Favicon (32x32)</label>
                        <div style="display:flex; align-items:center; gap:15px; background:var(--bg-panel); padding:15px; border-radius:12px; border:1px dashed var(--border-main);">
                            <div id="preview_favicon" style="width:40px; height:40px; background:white; border-radius:8px; display:flex; align-items:center; justify-content:center; box-shadow:0 2px 5px rgba(0,0,0,0.1);">
                                ${faviconVal ? `<img src="${faviconVal}" style="width:24px; height:24px; object-fit:contain;">` : '<i class="fas fa-image" style="color:#cbd5e1;"></i>'}
                            </div>
                            <button class="btn-premium-secondary" onclick="document.getElementById('tema_favicon').click()" style="flex:1; font-size:0.75rem; padding:8px 12px;">
                                <i class="fas fa-cloud-upload-alt"></i> Cambiar Archivo
                            </button>
                            <input type="file" id="tema_favicon" accept="image/*" onchange="previsualizarFavicon(this)" style="display:none;">
                        </div>
                        <p style="margin:0; font-size:0.7rem; color:var(--text-muted);">Recomendado: Archivo .ico o .png transparente de 32x32px.</p>
                    </div>
                </div>
            </div>`;
    }

    const bgVal = temaActual[seccion.bgKey] || TEMA_DEFAULTS[seccion.bgKey];
    const colorVal = temaActual[seccion.colorKey] || TEMA_DEFAULTS[seccion.colorKey];
    const fontVal = temaActual[seccion.fontKey] || TEMA_DEFAULTS[seccion.fontKey];

    return `
        <div class="premium-card" style="padding: 25px;">
            <div style="display:flex; align-items:center; gap:12px; margin-bottom:15px;">
                <div style="width:32px; height:32px; border-radius:8px; background:rgba(99, 102, 241, 0.1); color:#6366f1; display:flex; align-items:center; justify-content:center; font-size:0.9rem;">
                    <i class="fas ${seccion.icono}"></i>
                </div>
                <h5 style="margin:0; font-size:0.95rem; font-weight:700; color:var(--text-main);">${seccion.titulo}</h5>
            </div>
            
            <div class="tema-campo" style="margin-bottom:15px;">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                    <div class="tema-color-wrapper" style="height:45px; border-radius:10px; overflow:hidden; border:1px solid var(--border-main); display:flex; align-items:center; padding:0 10px; background:var(--bg-panel);">
                        <input type="color" id="tema_${seccion.bgKey}" value="${bgVal}" oninput="previsualizarTema()" style="width:25px; height:25px; border:none; background:none; cursor:pointer;">
                        <span class="tema-color-hex" id="hex_${seccion.bgKey}" style="margin-left:10px; font-family:monospace; font-size:0.8rem; font-weight:700;">${bgVal}</span>
                    </div>
                    <div class="tema-color-wrapper" style="height:45px; border-radius:10px; overflow:hidden; border:1px solid var(--border-main); display:flex; align-items:center; padding:0 10px; background:var(--bg-panel);">
                        <input type="color" id="tema_${seccion.colorKey}" value="${colorVal}" oninput="previsualizarTema()" style="width:25px; height:25px; border:none; background:none; cursor:pointer;">
                        <span class="tema-color-hex" id="hex_${seccion.colorKey}" style="margin-left:10px; font-family:monospace; font-size:0.8rem; font-weight:700;">${colorVal}</span>
                    </div>
                </div>
            </div>
            
            <div class="tema-campo">
                ${generarSelectFuente('tema_' + seccion.fontKey, fontVal)}
            </div>
        </div>`;
}

// ── PREVISUALIZACIÓN ──────────────────────────────────────────────────────────

function previsualizarTema() {
    try {
        const root = document.documentElement;
        
        // Sincronizar Header Simulado
        const simHeader = document.getElementById('sim_header');
        const bgH = document.getElementById('tema_header_bg');
        const colorH = document.getElementById('tema_header_color');
        const fontH = document.getElementById('tema_header_font');
        
        if (simHeader && bgH && colorH && fontH) {
            simHeader.style.background = bgH.value;
            simHeader.style.color = colorH.value;
            simHeader.style.fontFamily = `'${fontH.value}', sans-serif`;
            const hexBg = document.getElementById('hex_header_bg');
            const hexColor = document.getElementById('hex_header_color');
            if (hexBg) hexBg.textContent = bgH.value;
            if (hexColor) hexColor.textContent = colorH.value;
        }

        // Sincronizar Footer Simulado
        const simFooter = document.getElementById('sim_footer');
        const bgF = document.getElementById('tema_footer_bg');
        const colorF = document.getElementById('tema_footer_color');
        const fontF = document.getElementById('tema_footer_font');
        
        if (simFooter && bgF && colorF && fontF) {
            simFooter.style.background = bgF.value;
            simFooter.style.color = colorF.value;
            simFooter.style.fontFamily = `'${fontF.value}', sans-serif`;
            const hexBg = document.getElementById('hex_footer_bg');
            const hexColor = document.getElementById('hex_footer_color');
            if (hexBg) hexBg.textContent = bgF.value;
            if (hexColor) hexColor.textContent = colorF.value;
        }

        // Cargar Fuentes
        const fuentesUsadas = new Set();
        if (fontH) fuentesUsadas.add(fontH.value);
        if (fontF) fuentesUsadas.add(fontF.value);
        cargarGoogleFonts([...fuentesUsadas]);
        
        // Aplicar a la interfaz real
        if (document.querySelector('header')) {
            document.querySelector('header').style.background = bgH.value;
            document.querySelector('header').style.color = colorH.value;
        }

        previsualizarTamanoProductos();
    } catch(e) {
        console.error('previsualizarTema error:', e);
    }
}

function previsualizarIcono() {
    const svgInput = document.getElementById('tema_header_icon');
    const preview = document.getElementById('sim_icon');
    if (!svgInput || !preview) return;

    const svgCode = svgInput.value.trim();
    if (svgCode) {
        preview.innerHTML = svgCode;
        const svg = preview.querySelector('svg');
        if (svg) { 
            svg.style.width = '24px'; 
            svg.style.height = '24px'; 
            svg.style.fill = 'currentColor';
        }
    } else {
        preview.innerHTML = '<i class="fas fa-store"></i>';
    }
}

function previsualizarFavicon(input) {
    const preview = document.getElementById('preview_favicon');
    if (!preview || !input.files || !input.files[0]) return;
    const reader = new FileReader();
    reader.onload = e => { preview.innerHTML = `<img src="${e.target.result}" alt="Favicon" style="width:24px;height:24px;object-fit:contain;">`; };
    reader.readAsDataURL(input.files[0]);
}

function previsualizarTamanoProductos() {
    const grid = document.getElementById('sim_grid');
    if (!grid) return;
    
    try {
        // Obtener valores de los inputs o del temaActual
        const width = parseInt(document.getElementById('tema_producto_card_width')?.value || parseInt(temaActual.producto_card_width) || 200);
        const height = parseInt(document.getElementById('tema_producto_card_height')?.value || parseInt(temaActual.producto_card_height) || 350);
        const cols = parseInt(document.getElementById('tema_producto_grid_columns')?.value || parseInt(temaActual.producto_grid_columns) || 4);
        const font = parseFloat(document.getElementById('tema_producto_nombre_font_size')?.value || parseFloat(temaActual.producto_nombre_font_size) || 1);

        // Actualizar etiquetas de valor
        const updateLabel = (id, val) => {
            const el = document.getElementById(id);
            if (el) el.textContent = val;
        };
        updateLabel('val_producto_card_width', width + 'px');
        updateLabel('val_producto_card_height', height + 'px');
        updateLabel('val_producto_grid_columns', cols);
        updateLabel('val_producto_nombre_font_size', font + 'rem');

        // Configurar grid
        grid.style.display = 'grid';
        grid.style.gridTemplateColumns = `repeat(${cols}, 1fr)`;
        grid.style.gap = '8px';
        
        // Escalar para el preview (contenedor de 600px)
        const scale = 0.35; 
        let cards = '';
        const numCards = Math.max(cols * 2, 8);

        for (let i = 0; i < numCards; i++) {
            cards += `
                <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:8px; overflow:hidden; display:flex; flex-direction:column; height:${height * scale}px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                    <div style="flex:1; background:#f8fafc; display:flex; align-items:center; justify-content:center; overflow:hidden;">
                        <i class="fas fa-image" style="color:#cbd5e1; font-size:1.5rem;"></i>
                    </div>
                    <div style="padding:8px; border-top:1px solid #f1f5f9; background: #fff;">
                        <div style="font-weight:700; font-size:${font * 0.45}rem; color:#1e293b; margin-bottom:2px; line-height:1.2; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">Producto Demo ${i+1}</div>
                        <div style="font-weight:800; font-size:${font * 0.5}rem; color:#4f46e5;">9.99€</div>
                    </div>
                </div>`;
        }
        grid.innerHTML = cards;
    } catch (e) {
        grid.innerHTML = `<p style="color:red; font-size:10px;">Error: ${e.message}</p>`;
    }
}

// ── GUARDAR TEMA ──────────────────────────────────────────────────────────────

function guardarTema() {
    const datos = { ...temaActual };

    SECCIONES_TEMA.forEach(seccion => {
        if (seccion.bgKey) { const el = document.getElementById('tema_' + seccion.bgKey); if (el) datos[seccion.bgKey] = el.value; }
        if (seccion.colorKey) { const el = document.getElementById('tema_' + seccion.colorKey); if (el) datos[seccion.colorKey] = el.value; }
        if (seccion.fontKey) { const el = document.getElementById('tema_' + seccion.fontKey); if (el) datos[seccion.fontKey] = el.value; }
    });

    // Guardar configuración de tamaño de productos
    const widthInput = document.getElementById('tema_producto_card_width');
    const heightInput = document.getElementById('tema_producto_card_height');
    const maxWidthInput = document.getElementById('tema_producto_card_max_width');
    const maxHeightInput = document.getElementById('tema_producto_card_max_height');
    const columnsInput = document.getElementById('tema_producto_grid_columns');
    const gapInput = document.getElementById('tema_producto_grid_gap');
    const nombreFontInput = document.getElementById('tema_producto_nombre_font_size');
    const precioFontInput = document.getElementById('tema_producto_precio_font_size');
    const stockFontInput = document.getElementById('tema_producto_stock_font_size');

    if (widthInput) datos['producto_card_width'] = widthInput.value + 'px';
    if (heightInput) datos['producto_card_height'] = heightInput.value + 'px';
    if (maxWidthInput) datos['producto_card_max_width'] = maxWidthInput.value + 'px';
    if (maxHeightInput) datos['producto_card_max_height'] = maxHeightInput.value + 'px';
    if (columnsInput) datos['producto_grid_columns'] = columnsInput.value;
    if (gapInput) datos['producto_grid_gap'] = gapInput.value + 'px';
    if (nombreFontInput) datos['producto_nombre_font_size'] = nombreFontInput.value + 'rem';
    if (precioFontInput) datos['producto_precio_font_size'] = precioFontInput.value + 'rem';
    if (stockFontInput) datos['producto_stock_font_size'] = stockFontInput.value + 'rem';

    const headerIconInput = document.getElementById('tema_header_icon');
    if (headerIconInput && headerIconInput.value.trim()) datos['header_icon'] = headerIconInput.value;

    const faviconInput = document.getElementById('tema_favicon');
    if (faviconInput && faviconInput.files && faviconInput.files[0]) {
        const reader = new FileReader();
        reader.readAsDataURL(faviconInput.files[0]);
        reader.onload = () => { datos['favicon'] = reader.result; guardarTemaCompleto(datos); };
        reader.onerror = () => alert('Error al leer el archivo del favicon');
    } else {
        guardarTemaCompleto(datos);
    }
}

function guardarTemaCompleto(datos) {
    temaActual = { ...temaActual, ...datos };
    localStorage.setItem('temaTPV', JSON.stringify(datos));
    if (typeof aplicarTemaGuardado === 'function') aplicarTemaGuardado();

    fetch('api/tema.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(datos)
    })
        .then(res => res.json())
        .then(data => {
            if (data.ok) {
                const btn = document.querySelector('.tema-btn-guardar');
                if (btn) {
                    const textoOriginal = btn.innerHTML;
                    btn.innerHTML = '<i class="fas fa-check"></i> ¡Guardado!';
                    btn.style.background = '#059669';
                    setTimeout(() => { btn.innerHTML = textoOriginal; btn.style.background = ''; }, 2000);
                }
            } else {
                alert('Error al guardar: ' + (data.error || 'Error desconocido'));
            }
        })
        .catch(err => { console.error('Error guardando tema:', err); alert('Error al guardar la configuración.'); });
}

function restaurarTemaDefault() {
    if (!confirm('¿Restaurar todos los colores y fuentes a los valores predeterminados?')) return;
    temaActual = { ...TEMA_DEFAULTS };

    SECCIONES_TEMA.forEach(seccion => {
        const bgInput = document.getElementById('tema_' + seccion.bgKey);
        const colorInput = document.getElementById('tema_' + seccion.colorKey);
        const fontSelect = document.getElementById('tema_' + seccion.fontKey);
        if (bgInput) bgInput.value = TEMA_DEFAULTS[seccion.bgKey];
        if (colorInput) colorInput.value = TEMA_DEFAULTS[seccion.colorKey];
        if (fontSelect) fontSelect.value = TEMA_DEFAULTS[seccion.fontKey];
    });

    // Restablecer tamaño de productos
    const widthInput = document.getElementById('tema_producto_card_width');
    const heightInput = document.getElementById('tema_producto_card_height');
    const maxWidthInput = document.getElementById('tema_producto_card_max_width');
    const maxHeightInput = document.getElementById('tema_producto_card_max_height');
    const columnsInput = document.getElementById('tema_producto_grid_columns');
    const gapInput = document.getElementById('tema_producto_grid_gap');
    const nombreFontInput = document.getElementById('tema_producto_nombre_font_size');
    const precioFontInput = document.getElementById('tema_producto_precio_font_size');
    const stockFontInput = document.getElementById('tema_producto_stock_font_size');

    if (widthInput) widthInput.value = parseInt(TEMA_DEFAULTS.producto_card_width);
    if (heightInput) heightInput.value = parseInt(TEMA_DEFAULTS.producto_card_height);
    if (maxWidthInput) maxWidthInput.value = parseInt(TEMA_DEFAULTS.producto_card_max_width);
    if (maxHeightInput) maxHeightInput.value = parseInt(TEMA_DEFAULTS.producto_card_max_height);
    if (columnsInput) columnsInput.value = parseInt(TEMA_DEFAULTS.producto_grid_columns);
    if (gapInput) gapInput.value = parseInt(TEMA_DEFAULTS.producto_grid_gap);
    if (nombreFontInput) nombreFontInput.value = parseFloat(TEMA_DEFAULTS.producto_nombre_font_size);
    if (precioFontInput) precioFontInput.value = parseFloat(TEMA_DEFAULTS.producto_precio_font_size);
    if (stockFontInput) stockFontInput.value = parseFloat(TEMA_DEFAULTS.producto_stock_font_size);

    previsualizarTema();

    const header = document.querySelector('header');
    const footer = document.querySelector('footer');
    if (header) { header.style.background = ''; header.style.color = ''; header.style.fontFamily = ''; }
    if (footer) { footer.style.background = ''; footer.style.color = ''; footer.style.fontFamily = ''; }

    guardarTema();
}

/**
 * Aplica el tema guardado en localStorage al cargar la página.
 */
function aplicarTemaGuardado() {
    const temaJSON = localStorage.getItem('temaTPV');
    if (!temaJSON) return;

    try {
        const tema = JSON.parse(temaJSON);
        const header = document.querySelector('header');
        const footer = document.querySelector('footer');

        if (header && tema.header_bg) {
            header.style.background = tema.header_bg;
            header.style.color = tema.header_color || '';
            header.style.fontFamily = tema.header_font ? `'${tema.header_font}', sans-serif` : '';
        }
        if (footer && tema.footer_bg) {
            footer.style.background = tema.footer_bg;
            footer.style.color = tema.footer_color || '';
            footer.style.fontFamily = tema.footer_font ? `'${tema.footer_font}', sans-serif` : '';
        }
        if (tema.header_icon) {
            const iconContainer = document.getElementById('header-icon-container');
            if (iconContainer) {
                iconContainer.innerHTML = tema.header_icon;
                const svg = iconContainer.querySelector('svg');
                if (svg) { svg.setAttribute('width', '36'); svg.setAttribute('height', '36'); }
            }
        }
        if (tema.favicon) {
            const faviconLink = document.getElementById('favicon-link');
            if (faviconLink) faviconLink.href = tema.favicon;
        }

        // Aplicar tamaño de tarjetas de productos
        if (tema.producto_card_width || tema.producto_card_height || tema.producto_card_max_width || tema.producto_card_max_height || tema.producto_grid_columns || tema.producto_grid_gap || tema.producto_nombre_font_size || tema.producto_precio_font_size || tema.producto_stock_font_size) {
            const root = document.documentElement;
            if (tema.producto_card_width) root.style.setProperty('--producto-card-width', tema.producto_card_width);
            if (tema.producto_card_height) root.style.setProperty('--producto-card-height', tema.producto_card_height);
            if (tema.producto_card_max_width) root.style.setProperty('--producto-card-max-width', tema.producto_card_max_width);
            if (tema.producto_card_max_height) root.style.setProperty('--producto-card-max-height', tema.producto_card_max_height);
            if (tema.producto_grid_columns) root.style.setProperty('--producto-grid-columns', tema.producto_grid_columns);
            if (tema.producto_grid_gap) root.style.setProperty('--producto-grid-gap', tema.producto_grid_gap);
            if (tema.producto_nombre_font_size) root.style.setProperty('--producto-nombre-font-size', tema.producto_nombre_font_size);
            if (tema.producto_precio_font_size) root.style.setProperty('--producto-precio-font-size', tema.producto_precio_font_size);
            if (tema.producto_stock_font_size) root.style.setProperty('--producto-stock-font-size', tema.producto_stock_font_size);
        }

        const fuentes = new Set();
        Object.keys(tema).forEach(k => { if (k.endsWith('_font') && tema[k]) fuentes.add(tema[k]); });
        cargarGoogleFonts([...fuentes]);
    } catch (e) {
        console.error('Error aplicando tema:', e);
    }
}

/**
 * Carga dinámicamente fuentes de Google Fonts.
 */
function cargarGoogleFonts(fuentes) {
    let linkExistente = document.getElementById('google-fonts-tema');
    if (linkExistente) linkExistente.remove();

    const fuentesFiltradas = fuentes.filter(f => f !== 'Inter');
    if (fuentesFiltradas.length === 0) return;

    const familias = fuentesFiltradas.map(f => f.replace(/ /g, '+')).join('&family=');
    const link = document.createElement('link');
    link.id = 'google-fonts-tema';
    link.rel = 'stylesheet';
    link.href = `https://fonts.googleapis.com/css2?family=${familias}&display=swap`;
    document.head.appendChild(link);
}

// ── EXPORTACIONES SEMANALES ───────────────────────────────────────────────────

/**
 * Exporta datos semanales en el formato especificado.
 * @param {string} tipo - 'ventas' | 'sesiones' | 'retiros' | 'devoluciones'
 * @param {string} formato - 'json' | 'pdf' | 'excel' | 'csv'
 */
async function exportarSemanal(tipo, formato) {
    try {
        Swal.fire({ title: 'Preparando exportación...', text: 'Obteniendo datos de la última semana', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

        const urlMap = {
            ventas: 'api/ventas.php?todas=1&filtroFecha=7dias',
            sesiones: 'api/caja-sesiones.php?filtroFecha=7dias',
            retiros: 'api/retiros.php?filtroFecha=7dias',
            devoluciones: 'api/devoluciones.php?todas=1&filtroFecha=7dias'
        };
        const prefixMap = { ventas: 'ventas_semanales', sesiones: 'sesiones_semanales', retiros: 'retiros_semanales', devoluciones: 'devoluciones_semanales' };

        const response = await fetch(urlMap[tipo]);
        const data = await response.json();

        if (!data || data.length === 0) {
            Swal.fire('Atención', 'No hay datos disponibles para la última semana en esta categoría.', 'info');
            return;
        }

        const timestamp = new Date().toISOString().slice(0, 10);
        const fileName = `${prefixMap[tipo]}_${timestamp}`;

        const columnasMap = {
            ventas: ["ID", "Fecha", "Total", "Forma Pago", "Documento", "Usuario"],
            sesiones: ["ID", "Apertura", "Cierre", "I. Inicial", "I. Final", "Estado", "Usuario"],
            retiros: ["ID", "Fecha", "Importe", "Motivo", "Caja", "Usuario"],
            devoluciones: ["ID", "Fecha", "Importe Total", "Motivo", "Ticket", "Caja"]
        };

        const getRow = (item) => {
            if (tipo === 'ventas') return [item.id, item.fecha, item.total, item.forma_pago, item.tipoDocumento, item.usuario_nombre];
            if (tipo === 'sesiones') return [item.id, item.fechaApertura, item.fechaCierre || '-', item.importeInicial, item.importeActual, item.estado, item.usuario_nombre];
            if (tipo === 'retiros') return [item.id, item.fecha, item.importe, item.motivo, item.idCajaSesion, item.usuario_nombre];
            if (tipo === 'devoluciones') return [item.id, item.fecha, item.importeTotal, item.motivo, item.idVenta, item.idSesionCaja];
        };

        const columns = columnasMap[tipo];

        if (formato === 'json') {
            const a = document.createElement('a');
            a.setAttribute('href', "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(data, null, 2)));
            a.setAttribute('download', fileName + ".json");
            document.body.appendChild(a); a.click(); a.remove();
        } else if (formato === 'csv') {
            let csv = "data:text/csv;charset=utf-8," + columns.join(",") + "\n";
            data.forEach(item => { csv += getRow(item).join(",") + "\n"; });
            const a = document.createElement("a");
            a.setAttribute("href", encodeURI(csv));
            a.setAttribute("download", fileName + ".csv");
            document.body.appendChild(a); a.click(); a.remove();
        } else if (formato === 'excel') {
            let tableHtml = `<table border="1"><thead><tr>${columns.map(c => `<th>${c}</th>`).join('')}</tr></thead><tbody>`;
            data.forEach(item => { tableHtml += `<tr>${getRow(item).map(r => `<td>${r}</td>`).join('')}</tr>`; });
            tableHtml += '</tbody></table>';
            const a = document.createElement("a");
            a.href = 'data:application/vnd.ms-excel, ' + encodeURIComponent(tableHtml);
            a.download = fileName + '.xls';
            document.body.appendChild(a); a.click(); a.remove();
        } else if (formato === 'pdf') {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF('l', 'mm', 'a4');
            doc.setFontSize(18);
            doc.text(`Reporte Semanal: ${tipo.toUpperCase()}`, 14, 22);
            doc.setFontSize(11);
            doc.setTextColor(100);
            doc.text(`Generado el: ${new Date().toLocaleString()}`, 14, 30);
            doc.autoTable({
                startY: 35,
                head: [columns],
                body: data.map(item => getRow(item)),
                theme: 'striped',
                headStyles: { fillColor: [41, 128, 185], textColor: 255 },
                alternateRowStyles: { fillColor: [245, 245, 245] },
                margin: { top: 35 }
            });
            doc.save(fileName + ".pdf");
        }

        Swal.fire('¡Éxito!', 'Archivo exportado correctamente.', 'success');
    } catch (error) {
        console.error('Error en exportación:', error);
        Swal.fire('Error', 'No se pudo completar la exportación.', 'error');
    }
}

// ── VERIFICACIÓN DE CAMBIOS PROGRAMADOS ───────────────────────────────────────

/**
 * Previsualiza los cambios de tamaño de productos en tiempo real.
 */
function previsualizarTamanoProductos() {
    const widthInput = document.getElementById('tema_producto_card_width');
    const heightInput = document.getElementById('tema_producto_card_height');
    const maxWidthInput = document.getElementById('tema_producto_card_max_width');
    const maxHeightInput = document.getElementById('tema_producto_card_max_height');
    const columnsInput = document.getElementById('tema_producto_grid_columns');
    const gapInput = document.getElementById('tema_producto_grid_gap');
    const nombreFontInput = document.getElementById('tema_producto_nombre_font_size');
    const precioFontInput = document.getElementById('tema_producto_precio_font_size');
    const stockFontInput = document.getElementById('tema_producto_stock_font_size');

    if (!widthInput || !heightInput || !maxWidthInput || !maxHeightInput) return;

    const width = widthInput.value + 'px';
    const height = heightInput.value + 'px';
    const maxWidth = maxWidthInput.value + 'px';
    const maxHeight = maxHeightInput.value + 'px';
    const columns = columnsInput ? columnsInput.value : '4';
    const gap = gapInput ? gapInput.value + 'px' : '10px';
    const nombreFont = nombreFontInput ? nombreFontInput.value + 'rem' : '1.1rem';
    const precioFont = precioFontInput ? precioFontInput.value + 'rem' : '1.15rem';
    const stockFont = stockFontInput ? stockFontInput.value + 'rem' : '0.9rem';

    // Actualizar los valores mostrados
    const valWidth = document.getElementById('val_producto_card_width');
    const valHeight = document.getElementById('val_producto_card_height');
    const valMaxWidth = document.getElementById('val_producto_card_max_width');
    const valMaxHeight = document.getElementById('val_producto_card_max_height');
    const valColumns = document.getElementById('val_producto_grid_columns');
    const valGap = document.getElementById('val_producto_grid_gap');
    const valNombreFont = document.getElementById('val_producto_nombre_font_size');
    const valPrecioFont = document.getElementById('val_producto_precio_font_size');
    const valStockFont = document.getElementById('val_producto_stock_font_size');

    if (valWidth) valWidth.textContent = width;
    if (valHeight) valHeight.textContent = height;
    if (valMaxWidth) valMaxWidth.textContent = maxWidth;
    if (valMaxHeight) valMaxHeight.textContent = maxHeight;
    if (valColumns) valColumns.textContent = columns;
    if (valGap) valGap.textContent = gap;
    if (valNombreFont) valNombreFont.textContent = nombreFont;
    if (valPrecioFont) valPrecioFont.textContent = precioFont;
    if (valStockFont) valStockFont.textContent = stockFont;

    // Aplicar al preview de la tarjeta
    const preview = document.getElementById('preview_tamano_producto');
    const allPreviews = document.querySelectorAll('.preview-producto-card-preview');

    if (preview) {
        preview.style.width = width;
        preview.style.height = height;
        preview.style.maxWidth = maxWidth;
        preview.style.maxHeight = maxHeight;
    }

    // Aplicar el mismo tamaño a todas las tarjetas de preview
    allPreviews.forEach(card => {
        card.style.width = width;
        card.style.height = height;
        card.style.maxWidth = maxWidth;
        card.style.maxHeight = maxHeight;
    });

    // Ajustar el tamaño del container según el tamaño de las tarjetas
    const gridContainer = document.getElementById('preview_grid_container');
    if (gridContainer) {
        const columns = columnsInput ? columnsInput.value : '4';
        gridContainer.style.gridTemplateColumns = `repeat(${columns}, 1fr)`;
        gridContainer.style.gap = gap;
        // Calcular ancho total basado en el tamaño de las tarjetas
        const cardWidth = parseInt(maxWidth) || 250;
        const totalWidth = cardWidth * parseInt(columns) + (parseInt(gap) * (parseInt(columns) - 1));
        gridContainer.style.maxWidth = (totalWidth + 20) + 'px';
    }

    // Aplicar tamaños de fuente al preview
    const previewNombre = preview ? preview.querySelector('.preview-producto-nombre') : null;
    const previewPrecio = preview ? preview.querySelector('.preview-producto-precio') : null;
    const previewStock = preview ? preview.querySelector('.preview-producto-stock') : null;

    if (previewNombre) previewNombre.style.fontSize = nombreFont;
    if (previewPrecio) previewPrecio.style.fontSize = precioFont;
    if (previewStock) previewStock.style.fontSize = stockFont;

    // Aplicar variables CSS para previsualización en tiempo real
    const root = document.documentElement;
    root.style.setProperty('--preview-producto-width', width);
    root.style.setProperty('--preview-producto-height', height);
    root.style.setProperty('--preview-producto-max-width', maxWidth);
    root.style.setProperty('--preview-producto-max-height', maxHeight);
    root.style.setProperty('--preview-producto-columns', columns);
    root.style.setProperty('--preview-producto-gap', gap);
    root.style.setProperty('--preview-nombre-font', nombreFont);
    root.style.setProperty('--preview-precio-font', precioFont);
    root.style.setProperty('--preview-stock-font', stockFont);
}

/**
 * Restablece los valores de tamaño de productos a los predeterminados.
 */
function restablecerTamanoProductos() {
    if (!confirm('¿Restablecer el tamaño de las tarjetas de productos a los valores predeterminados?')) return;

    const defaults = TEMA_DEFAULTS;

    // Actualizar inputs
    const widthInput = document.getElementById('tema_producto_card_width');
    const heightInput = document.getElementById('tema_producto_card_height');
    const maxWidthInput = document.getElementById('tema_producto_card_max_width');
    const maxHeightInput = document.getElementById('tema_producto_card_max_height');
    const columnsInput = document.getElementById('tema_producto_grid_columns');
    const gapInput = document.getElementById('tema_producto_grid_gap');
    const nombreFontInput = document.getElementById('tema_producto_nombre_font_size');
    const precioFontInput = document.getElementById('tema_producto_precio_font_size');
    const stockFontInput = document.getElementById('tema_producto_stock_font_size');

    if (widthInput) widthInput.value = parseInt(defaults.producto_card_width);
    if (heightInput) heightInput.value = parseInt(defaults.producto_card_height);
    if (maxWidthInput) maxWidthInput.value = parseInt(defaults.producto_card_max_width);
    if (maxHeightInput) maxHeightInput.value = parseInt(defaults.producto_card_max_height);
    if (columnsInput) columnsInput.value = parseInt(defaults.producto_grid_columns);
    if (gapInput) gapInput.value = parseInt(defaults.producto_grid_gap);
    if (nombreFontInput) nombreFontInput.value = parseFloat(defaults.producto_nombre_font_size);
    if (precioFontInput) precioFontInput.value = parseFloat(defaults.producto_precio_font_size);
    if (stockFontInput) stockFontInput.value = parseFloat(defaults.producto_stock_font_size);

    // Guardar en temaActual
    temaActual['producto_card_width'] = defaults.producto_card_width;
    temaActual['producto_card_height'] = defaults.producto_card_height;
    temaActual['producto_card_max_width'] = defaults.producto_card_max_width;
    temaActual['producto_card_max_height'] = defaults.producto_card_max_height;
    temaActual['producto_grid_columns'] = defaults.producto_grid_columns;
    temaActual['producto_grid_gap'] = defaults.producto_grid_gap;
    temaActual['producto_nombre_font_size'] = defaults.producto_nombre_font_size;
    temaActual['producto_precio_font_size'] = defaults.producto_precio_font_size;
    temaActual['producto_stock_font_size'] = defaults.producto_stock_font_size;

    // Actualizar preview
    previsualizarTamanoProductos();
}

/**
 * Verifica y aplica cambios de IVA programados.
 */
function verificarCambiosIvaProgramados() {
    fetch('api/productos.php?accion=aplicar_cambios_iva_programados')
        .then(res => res.json())
        .then(data => {
            if (data.aplicados > 0) {
                console.log('Se aplicaron ' + data.aplicados + ' cambios de IVA programados');
                cargarTiposIva();
            }
        })
        .catch(err => console.error('Error verificando cambios IVA programados:', err));
}

/**
 * Verifica y aplica ajustes de precios programados.
 */
function verificarAjustesPreciosProgramados() {
    fetch('api/productos.php?accion=aplicar_ajustes_precios_programados')
        .then(res => res.json())
        .then(data => {
            if (data.aplicados > 0) {
                console.log('Se aplicaron ' + data.aplicados + ' ajustes de precios programados');
                if (seccionActual === 'tarifa-ajuste') mostrarPanelAjustePrecios();
            }
        })
        .catch(err => console.error('Error verificando ajustes de precios programados:', err));
}

function cargarGoogleFonts(fuentes) {
    if (!fuentes || !fuentes.length) return;
    const linkId = 'google-fonts-preview';
    let link = document.getElementById(linkId);
    if (!link) {
        link = document.createElement('link');
        link.id = linkId;
        link.rel = 'stylesheet';
        document.head.appendChild(link);
    }
    const family = fuentes.map(f => f.replace(/ /g, '+')).join('|');
    link.href = `https://fonts.googleapis.com/css?family=${family}:400,700&display=swap`;
}

// Clock update for preview
setInterval(() => {
    const el = document.getElementById('sim_clock');
    if (el) el.textContent = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}, 1000);





