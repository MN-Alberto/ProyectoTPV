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
    let html = '<div class="tema-editor">';

    if (subseccion === 'todas' || subseccion === 'tema') {
        // Filtrar las secciones que no son de tamaño de productos para el grid principal
        const seccionesPrincipales = SECCIONES_TEMA.filter(s => s.tipo !== 'tamano_productos');
        const seccionTamanoProductos = SECCIONES_TEMA.find(s => s.tipo === 'tamano_productos');

        html += `
            <div class="config-section">
                <div class="tema-secciones-grid">
                    ${seccionesPrincipales.map(s => generarSeccionTema(s)).join('')}
                </div>
                <div class="tema-botones" style="margin-top:25px;border-top:1px solid var(--border-main);padding-top:20px;">
                    <button class="btn-modal-cancelar tema-btn-reset" onclick="restaurarTemaDefault()">
                        <i class="fas fa-undo"></i> Restaurar Predeterminados
                    </button>
                    <button class="btn-exito tema-btn-guardar" onclick="guardarTema()">
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>
                </div>
            </div>`;

        // Agregar la sección de tamaño de productos como una sección separada
        if (seccionTamanoProductos) {
            html += `
                <div class="config-section" style="margin-top: 20px;">
                    ${generarSeccionTema(seccionTamanoProductos)}
                </div>`;
        }
    }

    if (subseccion === 'todas' || subseccion === 'acciones') {
        const exportaciones = [
            { tipo: 'ventas', titulo: 'Ventas de la Semana', desc: 'Exportar todas las ventas realizadas en los últimos 7 días.', icono: 'fa-file-invoice-dollar' },
            { tipo: 'sesiones', titulo: 'Sesiones de Caja', desc: 'Exportar el historial de sesiones de caja de la semana.', icono: 'fa-cash-register' },
            { tipo: 'retiros', titulo: 'Retiros de Caja', desc: 'Exportar todos los retiros de efectivo de la semana.', icono: 'fa-money-bill-wave' },
            { tipo: 'devoluciones', titulo: 'Devoluciones', desc: 'Exportar el registro de devoluciones de la semana.', icono: 'fa-undo' }
        ];

        html += `<div class="config-section"><div class="export-grid">`;
        exportaciones.forEach(exp => {
            html += `
                <div class="export-card">
                    <div class="export-card-icon"><i class="fas ${exp.icono}"></i></div>
                    <div class="export-card-info"><h4>${exp.titulo}</h4><p>${exp.desc}</p></div>
                    <div class="export-card-actions">
                        <button class="btn-export json" onclick="exportarSemanal('${exp.tipo}', 'json')">JSON</button>
                        <button class="btn-export pdf" onclick="exportarSemanal('${exp.tipo}', 'pdf')">PDF</button>
                        <button class="btn-export excel" style="background:#1e7e34;color:white;" onclick="exportarSemanal('${exp.tipo}', 'excel')">EXCEL</button>
                        <button class="btn-export csv" style="background:#5a6268;color:white;" onclick="exportarSemanal('${exp.tipo}', 'csv')">CSV</button>
                    </div>
                </div>`;
        });
        html += `</div></div>`;
    }



    html += '</div>';
    contenedor.innerHTML = html;

    // Inicializar preview de tamaño de productos si existe
    setTimeout(() => previsualizarTamanoProductos(), 100);
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
        const maxWidthVal = temaActual['producto_card_max_width'] || TEMA_DEFAULTS.producto_card_max_width;
        const maxHeightVal = temaActual['producto_card_max_height'] || TEMA_DEFAULTS.producto_card_max_height;
        const columnsVal = temaActual['producto_grid_columns'] || TEMA_DEFAULTS.producto_grid_columns;
        const gapVal = temaActual['producto_grid_gap'] || TEMA_DEFAULTS.producto_grid_gap;
        const nombreFontVal = temaActual['producto_nombre_font_size'] || TEMA_DEFAULTS.producto_nombre_font_size;
        const precioFontVal = temaActual['producto_precio_font_size'] || TEMA_DEFAULTS.producto_precio_font_size;
        const stockFontVal = temaActual['producto_stock_font_size'] || TEMA_DEFAULTS.producto_stock_font_size;

        return `
            <div class="tema-seccion-card">
                <div class="tema-seccion-header">
                    <i class="fas ${seccion.icono} tema-seccion-icono"></i>
                    <h4 class="tema-seccion-titulo">${seccion.titulo}</h4>
                </div>
                <div class="tema-seccion-body">
                    <div class="tamano-productos-grid">
                        <div class="tamano-productos-controles">
                            <h5 style="margin:0 0 15px 0;color:var(--text-main);font-weight:600;">Tamaño de tarjetas</h5>
                            <div class="tema-campo">
                                <label class="tema-label">Ancho (min-width)</label>
                                <div class="tamano-input-wrapper">
                                    <input type="range" id="tema_producto_card_width" min="100" max="300" value="${parseInt(widthVal)}" oninput="previsualizarTamanoProductos()">
                                    <span class="tamano-value" id="val_producto_card_width">${widthVal}</span>
                                </div>
                            </div>
                            <div class="tema-campo">
                                <label class="tema-label">Alto (height)</label>
                                <div class="tamano-input-wrapper">
                                    <input type="range" id="tema_producto_card_height" min="200" max="500" value="${parseInt(heightVal)}" oninput="previsualizarTamanoProductos()">
                                    <span class="tamano-value" id="val_producto_card_height">${heightVal}</span>
                                </div>
                            </div>
                            <div class="tema-campo">
                                <label class="tema-label">Ancho máximo (max-width)</label>
                                <div class="tamano-input-wrapper">
                                    <input type="range" id="tema_producto_card_max_width" min="150" max="400" value="${parseInt(maxWidthVal)}" oninput="previsualizarTamanoProductos()">
                                    <span class="tamano-value" id="val_producto_card_max_width">${maxWidthVal}</span>
                                </div>
                            </div>
                            <div class="tema-campo">
                                <label class="tema-label">Alto máximo (max-height)</label>
                                <div class="tamano-input-wrapper">
                                    <input type="range" id="tema_producto_card_max_height" min="250" max="600" value="${parseInt(maxHeightVal)}" oninput="previsualizarTamanoProductos()">
                                    <span class="tamano-value" id="val_producto_card_max_height">${maxHeightVal}</span>
                                </div>
                            </div>
                            
                            <h5 style="margin:20px 0 15px 0;color:var(--text-main);font-weight:600;">Grid y espaciado</h5>
                            <div class="tema-campo">
                                <label class="tema-label">Cajas por fila</label>
                                <div class="tamano-input-wrapper">
                                    <input type="range" id="tema_producto_grid_columns" min="2" max="8" value="${parseInt(columnsVal)}" oninput="previsualizarTamanoProductos()">
                                    <span class="tamano-value" id="val_producto_grid_columns">${columnsVal}</span>
                                </div>
                            </div>
                            <div class="tema-campo">
                                <label class="tema-label">Espacio entre cajas (gap)</label>
                                <div class="tamano-input-wrapper">
                                    <input type="range" id="tema_producto_grid_gap" min="5" max="30" value="${parseInt(gapVal)}" oninput="previsualizarTamanoProductos()">
                                    <span class="tamano-value" id="val_producto_grid_gap">${gapVal}</span>
                                </div>
                            </div>
                            
                            <h5 style="margin:20px 0 15px 0;color:var(--text-main);font-weight:600;">Tamaños de fuente</h5>
                            <div class="tema-campo">
                                <label class="tema-label">Tamaño del nombre</label>
                                <div class="tamano-input-wrapper">
                                    <input type="range" id="tema_producto_nombre_font_size" min="0.7" max="2" step="0.1" value="${parseFloat(nombreFontVal)}" oninput="previsualizarTamanoProductos()">
                                    <span class="tamano-value" id="val_producto_nombre_font_size">${nombreFontVal}</span>
                                </div>
                            </div>
                            <div class="tema-campo">
                                <label class="tema-label">Tamaño del precio</label>
                                <div class="tamano-input-wrapper">
                                    <input type="range" id="tema_producto_precio_font_size" min="0.7" max="2" step="0.1" value="${parseFloat(precioFontVal)}" oninput="previsualizarTamanoProductos()">
                                    <span class="tamano-value" id="val_producto_precio_font_size">${precioFontVal}</span>
                                </div>
                            </div>
                            <div class="tema-campo">
                                <label class="tema-label">Tamaño del stock</label>
                                <div class="tamano-input-wrapper">
                                    <input type="range" id="tema_producto_stock_font_size" min="0.6" max="1.5" step="0.1" value="${parseFloat(stockFontVal)}" oninput="previsualizarTamanoProductos()">
                                    <span class="tamano-value" id="val_producto_stock_font_size">${stockFontVal}</span>
                                </div>
                            </div>
                            
                            <button class="btn-restablecer-tamano" onclick="restablecerTamanoProductos()">
                                <i class="fas fa-undo"></i> Restablecer valores predeterminados
                            </button>
                        </div>
                        <div class="tamano-productos-preview">
                            <h5 style="margin-bottom:10px;color:var(--text-muted);font-size:0.9rem;">Vista previa</h5>
                            <div class="preview-grid-container" id="preview_grid_container">
                                <div class="preview-producto-card-preview">
                                    <div class="preview-producto-nombre">Producto 1</div>
                                    <div class="preview-producto-imagen">
                                        <i class="fas fa-image" style="color:#9ca3af;"></i>
                                    </div>
                                    <div class="preview-producto-info">
                                        <span class="preview-producto-precio">99,99 €</span>
                                        <select class="preview-tarifa-selector">
                                            <option>General</option>
                                        </select>
                                        <span class="preview-producto-stock">Stock: 50</span>
                                    </div>
                                </div>
                                <div class="preview-producto-card-preview">
                                    <div class="preview-producto-nombre">Producto 2</div>
                                    <div class="preview-producto-imagen">
                                        <i class="fas fa-image" style="color:#9ca3af;"></i>
                                    </div>
                                    <div class="preview-producto-info">
                                        <span class="preview-producto-precio">49,99 €</span>
                                        <select class="preview-tarifa-selector">
                                            <option>General</option>
                                        </select>
                                        <span class="preview-producto-stock">Stock: 25</span>
                                    </div>
                                </div>
                                <div class="preview-producto-card-preview">
                                    <div class="preview-producto-nombre">Producto 3</div>
                                    <div class="preview-producto-imagen">
                                        <i class="fas fa-image" style="color:#9ca3af;"></i>
                                    </div>
                                    <div class="preview-producto-info">
                                        <span class="preview-producto-precio">149,99 €</span>
                                        <select class="preview-tarifa-selector">
                                            <option>General</option>
                                        </select>
                                        <span class="preview-producto-stock">Stock: 10</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>`;
    }

    if (seccion.tipo === 'iconos') {
        const headerIconVal = temaActual['header_icon'] || '';
        const faviconVal = temaActual['favicon'] || '';
        return `
            <div class="tema-seccion-card">
                <div class="tema-seccion-header">
                    <i class="fas ${seccion.icono} tema-seccion-icono"></i>
                    <h4 class="tema-seccion-titulo">${seccion.titulo}</h4>
                </div>
                <div class="tema-seccion-body">
                    <div class="tema-campo">
                        <label class="tema-label">Icono del Header (SVG)</label>
                        <textarea id="tema_header_icon" class="tema-textarea-icono"
                            placeholder="Pega el código SVG aquí..."
                            oninput="previsualizarIcono()">${headerIconVal}</textarea>
                        <p class="tema-ayuda">Pega el código completo de un icono SVG (incluyendo las etiquetas &lt;svg&gt;)</p>
                    </div>
                    <div class="tema-preview-icono" id="preview_header_icon">
                        ${headerIconVal ? headerIconVal : '<span style="color:var(--text-muted);">Vista previa del icono</span>'}
                    </div>
                    <div class="tema-campo" style="margin-top:15px;">
                        <label class="tema-label">Favicon</label>
                        <input type="file" id="tema_favicon" accept="image/*" onchange="previsualizarFavicon(this)">
                        <p class="tema-ayuda">Sube una imagen para el favicon (16x16, 32x32 o 48x48 píxeles)</p>
                    </div>
                    <div class="tema-preview-favicon" id="preview_favicon">
                        ${faviconVal ? `<img src="${faviconVal}" alt="Favicon" style="width:32px;height:32px;">` : '<span style="color:var(--text-muted);">Vista previa del favicon</span>'}
                    </div>
                </div>
            </div>`;
    }

    const bgVal = temaActual[seccion.bgKey] || TEMA_DEFAULTS[seccion.bgKey];
    const colorVal = temaActual[seccion.colorKey] || TEMA_DEFAULTS[seccion.colorKey];
    const fontVal = temaActual[seccion.fontKey] || TEMA_DEFAULTS[seccion.fontKey];

    return `
        <div class="tema-seccion-card">
            <div class="tema-seccion-header">
                <i class="fas ${seccion.icono} tema-seccion-icono"></i>
                <h4 class="tema-seccion-titulo">${seccion.titulo}</h4>
            </div>
            <div class="tema-seccion-body">
                <div class="tema-campo">
                    <label class="tema-label">Color de Fondo</label>
                    <div class="tema-color-wrapper">
                        <input type="color" id="tema_${seccion.bgKey}" value="${bgVal}" oninput="previsualizarTema()">
                        <span class="tema-color-hex" id="hex_${seccion.bgKey}">${bgVal}</span>
                    </div>
                </div>
                <div class="tema-campo">
                    <label class="tema-label">Color de Texto</label>
                    <div class="tema-color-wrapper">
                        <input type="color" id="tema_${seccion.colorKey}" value="${colorVal}" oninput="previsualizarTema()">
                        <span class="tema-color-hex" id="hex_${seccion.colorKey}">${colorVal}</span>
                    </div>
                </div>
                <div class="tema-campo">
                    <label class="tema-label">Fuente</label>
                    ${generarSelectFuente('tema_' + seccion.fontKey, fontVal)}
                </div>
                <div class="tema-preview" id="preview_${seccion.id}"
                    style="background:${bgVal};color:${colorVal};font-family:'${fontVal}',sans-serif;">
                    Vista previa del texto
                </div>
            </div>
        </div>`;
}

// ── PREVISUALIZACIÓN ──────────────────────────────────────────────────────────

function previsualizarTema() {
    const root = document.documentElement;
    SECCIONES_TEMA.forEach(seccion => {
        if (seccion.tipo === 'iconos') return;
        const bgInput = document.getElementById('tema_' + seccion.bgKey);
        const colorInput = document.getElementById('tema_' + seccion.colorKey);
        const fontSelect = document.getElementById('tema_' + seccion.fontKey);
        const preview = document.getElementById('preview_' + seccion.id);
        if (!bgInput || !colorInput || !fontSelect) return;

        const bgVal = bgInput.value;
        const colorVal = colorInput.value;
        const fontVal = fontSelect.value;

        const hexBg = document.getElementById('hex_' + seccion.bgKey);
        const hexColor = document.getElementById('hex_' + seccion.colorKey);
        if (hexBg) hexBg.textContent = bgVal;
        if (hexColor) hexColor.textContent = colorVal;

        if (preview) {
            preview.style.background = bgVal;
            preview.style.color = colorVal;
            preview.style.fontFamily = `'${fontVal}', sans-serif`;
        }

        root.style.setProperty('--theme-' + seccion.bgKey.replace(/_/g, '-'), bgVal);
        root.style.setProperty('--theme-' + seccion.colorKey.replace(/_/g, '-'), colorVal);
    });

    const header = document.querySelector('header');
    const footer = document.querySelector('footer');
    const bgH = document.getElementById('tema_header_bg');
    const colorH = document.getElementById('tema_header_color');
    const fontH = document.getElementById('tema_header_font');
    const bgF = document.getElementById('tema_footer_bg');
    const colorF = document.getElementById('tema_footer_color');
    const fontF = document.getElementById('tema_footer_font');

    if (header && bgH && colorH && fontH) {
        header.style.background = bgH.value;
        header.style.color = colorH.value;
        header.style.fontFamily = `'${fontH.value}', sans-serif`;
    }
    if (footer && bgF && colorF && fontF) {
        footer.style.background = bgF.value;
        footer.style.color = colorF.value;
        footer.style.fontFamily = `'${fontF.value}', sans-serif`;
    }

    const fuentesUsadas = new Set();
    SECCIONES_TEMA.forEach(s => {
        const fontSel = document.getElementById('tema_' + s.fontKey);
        if (fontSel) fuentesUsadas.add(fontSel.value);
    });
    cargarGoogleFonts([...fuentesUsadas]);
}

function previsualizarIcono() {
    const svgInput = document.getElementById('tema_header_icon');
    const preview = document.getElementById('preview_header_icon');
    if (!svgInput || !preview) return;

    const svgCode = svgInput.value.trim();
    if (svgCode) {
        preview.innerHTML = svgCode;
        const svg = preview.querySelector('svg');
        if (svg) { svg.style.width = '48px'; svg.style.height = '48px'; }
    } else {
        preview.innerHTML = '<span style="color:var(--text-muted);">Vista previa del icono</span>';
    }
}

function previsualizarFavicon(input) {
    const preview = document.getElementById('preview_favicon');
    if (!preview || !input.files || !input.files[0]) return;
    const reader = new FileReader();
    reader.onload = e => { preview.innerHTML = `<img src="${e.target.result}" alt="Favicon" style="width:32px;height:32px;">`; };
    reader.readAsDataURL(input.files[0]);
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





