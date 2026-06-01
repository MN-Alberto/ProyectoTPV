/**
 * admin-verifactu.js
 * Gestión de la configuración fiscal y cumplimiento Verifactu.
 */
console.log("DEBUG: admin-verifactu.js is being loaded...");


function cargarConfiguracionFiscal() {
    seccionActual = 'configuracion-fiscal';
    const contenedor = document.getElementById('adminContenido');
    contenedor.innerHTML = '<p style="text-align:center;padding:40px;color:var(--text-muted);">Cargando configuración fiscal...</p>';

    fetch('api/fiscal.php')
        .then(res => res.json())
        .then(config => {
            renderEditorFiscal(config);
        })
        .catch(err => {
            console.error('Error cargando configuración fiscal:', err);
            contenedor.innerHTML = '<p style="color:red;padding:20px;">Error al cargar la configuración fiscal.</p>';
        });
}

function renderEditorFiscal(config) {
    const contenedor = document.getElementById('adminContenido');
    const configHeader = { t: 'Identidad Fiscal AEAT', s: 'Cumplimiento normativo y conectividad con el sistema Verifactu', i: 'fa-landmark', g: 'linear-gradient(135deg, #1e40af, #3b82f6)' };

    let html = `
    <div class="fiscal-editor-premium animate-fade-in">
        ${getPremiumHeaderHTML(configHeader.i, configHeader.t, configHeader.s, configHeader.g)}

        <div class="premium-config-grid" style="margin-top: 25px;">
            
            <!-- TARJETA 1: EMISOR -->
            <div class="premium-card">
                <div style="display:flex; align-items:center; gap:12px; margin-bottom:25px; padding-bottom:15px; border-bottom:1px solid rgba(0,0,0,0.05);">
                    <div style="width:40px; height:40px; border-radius:10px; background:rgba(59, 130, 246, 0.1); color:#3b82f6; display:flex; align-items:center; justify-content:center; font-size:1.2rem;">
                        <i class="fas fa-id-card"></i>
                    </div>
                    <h4 style="margin:0; font-size:1.1rem; font-weight:700; color:var(--text-main);">Datos del Titular</h4>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 15px;">
                    <div class="tema-campo">
                        <label class="tema-label">NIF / CIF</label>
                        <input type="text" id="fiscal_tpv_nif" value="${config.tpv_nif || ''}" class="tema-input" placeholder="Ej: B12345678">
                    </div>
                    <div class="tema-campo">
                        <label class="tema-label">Razón Social</label>
                        <input type="text" id="fiscal_tpv_razon_social" value="${config.tpv_razon_social || ''}" class="tema-input" placeholder="Nombre Fiscal">
                    </div>
                </div>

                <div class="tema-campo" style="margin-top: 10px;">
                    <label class="tema-label">Dirección Administrativa</label>
                    <input type="text" id="fiscal_tpv_direccion" value="${config.tpv_direccion || ''}" class="tema-input" placeholder="Dirección completa">
                </div>
            </div>

            <!-- TARJETA 2: ENTORNO -->
            <div class="premium-card">
                <div style="display:flex; align-items:center; gap:12px; margin-bottom:25px; padding-bottom:15px; border-bottom:1px solid rgba(0,0,0,0.05);">
                    <div style="width:40px; height:40px; border-radius:10px; background:rgba(245, 158, 11, 0.1); color:#f59e0b; display:flex; align-items:center; justify-content:center; font-size:1.2rem;">
                        <i class="fas fa-server"></i>
                    </div>
                    <h4 style="margin:0; font-size:1.1rem; font-weight:700; color:var(--text-main);">Canal de Comunicación</h4>
                </div>

                <div class="tema-campo">
                    <label class="tema-label">URL del Servicio AEAT</label>
                    <select id="fiscal_aeat_url" class="tema-select-font">
                        <option value="https://prewww1.aeat.es/wlpl/TIKE-CONT/ws/SistemaFacturacion/VerifactuSOAP" ${(config.aeat_url_verifactu && config.aeat_url_verifactu.indexOf('prewww1') !== -1) ? 'selected' : ''}>🧪 Entorno de Pruebas (PRE)</option>
                        <option value="https://www1.aeat.es/wlpl/TIKE-CONT/ws/SistemaFacturacion/VerifactuSOAP" ${(!config.aeat_url_verifactu || (config.aeat_url_verifactu.indexOf('prewww1') === -1 && config.aeat_url_verifactu.indexOf('servidor-falso') === -1)) ? 'selected' : ''}>🚀 Entorno Real (PRODUCCIÓN)</option>
                        <option value="https://servidor-falso.aeat.es/VerifactuSOAP" ${config.aeat_url_verifactu && config.aeat_url_verifactu.indexOf('servidor-falso') !== -1 ? 'selected' : ''}>⚠️ Modo Simulación</option>
                    </select>
                </div>

                <div class="tema-campo" style="margin-top: 15px;">
                    <label class="tema-label">Reintento Automático (minutos)</label>
                    <div style="display:flex; align-items:center; gap:10px;">
                        <input type="number" id="fiscal_verifactu_intervalo_reintento" value="${config.verifactu_intervalo_reintento || 15}" class="tema-input" style="width:80px;" min="1" max="1440">
                        <span style="font-size: 0.8rem; color: var(--text-muted);">Frecuencia de reenvío tras error.</span>
                    </div>
                </div>
            </div>

            <!-- TARJETA 3: SEGURIDAD -->
            <div class="premium-card">
                <div style="display:flex; align-items:center; gap:12px; margin-bottom:25px; padding-bottom:15px; border-bottom:1px solid rgba(0,0,0,0.05);">
                    <div style="width:40px; height:40px; border-radius:10px; background:rgba(16, 185, 129, 0.1); color:#10b981; display:flex; align-items:center; justify-content:center; font-size:1.2rem;">
                        <i class="fas fa-lock"></i>
                    </div>
                    <h4 style="margin:0; font-size:1.1rem; font-weight:700; color:var(--text-main);">Certificado de Firma</h4>
                </div>

                <div class="tema-campo">
                    <label class="tema-label">Ruta absoluta del archivo (.pfx)</label>
                    <input type="text" id="fiscal_cert_path" value="${config.cert_path || ''}" class="tema-input" placeholder="C:/certs/firma.pfx">
                </div>

                <div class="tema-campo" style="margin-top: 10px;">
                    <label class="tema-label">Contraseña de acceso</label>
                    <div style="position: relative;">
                        <input type="password" id="fiscal_cert_pass" value="${config.cert_pass || ''}" class="tema-input" style="padding-right:45px;">
                        <i class="fas fa-eye" onclick="togglePassword('fiscal_cert_pass')" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; color: var(--text-muted);"></i>
                    </div>
                </div>
            </div>
        </div>

        <div style="display:flex; justify-content:flex-end; gap:15px; margin-top: 30px;">
            <button class="btn-premium-primary" onclick="guardarConfiguracionFiscal()" style="padding: 12px 30px;">
                <i class="fas fa-check-circle" style="margin-right:8px;"></i> Aplicar Configuración Fiscal
            </button>
        </div>
    </div>`;

    contenedor.innerHTML = html;
}

function guardarConfiguracionFiscal() {
    const datos = {
        tpv_nif: document.getElementById('fiscal_tpv_nif').value.trim(),
        tpv_razon_social: document.getElementById('fiscal_tpv_razon_social').value.trim(),
        tpv_direccion: document.getElementById('fiscal_tpv_direccion').value.trim(),
        aeat_url_verifactu: document.getElementById('fiscal_aeat_url').value,
        cert_path: document.getElementById('fiscal_cert_path').value.trim(),
        cert_pass: document.getElementById('fiscal_cert_pass').value,
        verifactu_intervalo_reintento: document.getElementById('fiscal_verifactu_intervalo_reintento').value
    };

    if (!datos.tpv_nif || !datos.tpv_razon_social || !datos.cert_path) {
        Swal.fire('Error', 'Debe completar el NIF, Razón Social y la ruta del certificado.', 'error');
        return;
    }

    Swal.fire({
        title: 'Guardando...',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    fetch('api/fiscal.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(datos)
    })
        .then(res => res.json())
        .then(data => {
            if (data.ok) {
                Swal.fire('¡Éxito!', 'Configuración fiscal guardada correctamente.', 'success');
            } else {
                Swal.fire('Error', data.error || 'No se pudo guardar la configuración.', 'error');
            }
        })
        .catch(err => {
            console.error('Error guardando fiscal:', err);
            Swal.fire('Error', 'Error de red al conectar con la API.', 'error');
        });
}

function togglePassword(id) {
    const el = document.getElementById(id);
    if (el.type === 'password') {
        el.type = 'text';
    } else {
        el.type = 'password';
    }
}

// El enrutamiento se maneja ahora desde vAdmin.php switch
// ======================== ENVÍOS AEAT ========================

let verifactuTabActual = 'cola';
let verifactuPaginaActual = 1;
const verifactuLimitePorPagina = 5;
let aeatCooldownSegs = 0;
let aeatCooldownTimer = null;
let verifactuColaCache = [];

function formatCooldown(segs) {
    const m = Math.floor(segs / 60);
    const s = segs % 60;
    return `${m}:${s.toString().padStart(2, '0')}`;
}

function iniciarCooldownVisual(segundos) {
    if (aeatCooldownTimer) clearInterval(aeatCooldownTimer);
    aeatCooldownSegs = segundos;
    actualizarCooldownUI();
    if (segundos <= 0) return;
    aeatCooldownTimer = setInterval(() => {
        aeatCooldownSegs--;
        actualizarCooldownUI();
        if (aeatCooldownSegs <= 0) {
            clearInterval(aeatCooldownTimer);
            aeatCooldownTimer = null;
            // Cooldown acabado → procesar cola automáticamente
            fetch('api/verifactu-cola.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'procesarCola' })
            })
                .then(r => r.json())
                .then(data => {
                    if (data.ok && data.resumen) {
                        if (data.resumen.cooldown_segundos > 0) {
                            iniciarCooldownVisual(data.resumen.cooldown_segundos);
                        }
                        if (data.resumen.procesados > 0) {
                            actualizarBadgePendientesAeat();
                            if (seccionActual === 'envios-aeat') cargarTabEnvios(verifactuTabActual);
                        }
                    }
                })
                .catch(e => console.error('Error auto-process cooldown:', e));
        }
    }, 1000);
}

function actualizarCooldownUI() {
    const el = document.getElementById('aeatCooldownBanner');
    if (!el) return;
    if (aeatCooldownSegs > 0) {
        el.style.display = 'flex';
        el.innerHTML = `
            <i class="fas fa-hourglass-half fa-spin"></i>
            <span>Esperando AEAT: <b>${formatCooldown(aeatCooldownSegs)}</b></span>
        `;
    } else {
        el.style.display = 'none';
    }
}

function cargarEnviosAeat() {
    seccionActual = 'envios-aeat';
    renderEnviosAeatLayout();
    cargarTabEnvios(verifactuTabActual);
    // Cargar cooldown inicial
    fetch('api/verifactu-cola.php?estadisticas=1')
        .then(r => r.json())
        .then(stats => {
            if (stats.cooldown_segundos > 0) {
                iniciarCooldownVisual(stats.cooldown_segundos);
            }
        })
        .catch(() => { });
}

function renderEnviosAeatLayout() {
    const contenedor = document.getElementById('adminContenido');
    const config = { t: 'Monitor Verifactu AEAT', s: 'Cumplimiento fiscal y estado de envíos en tiempo real', i: 'fa-satellite-dish', g: 'linear-gradient(135deg, #ef4444, #b91c1c)' };

    contenedor.innerHTML = `
    <div class="verifactu-modern-view animate-fade-in">
        ${getPremiumHeaderHTML(config.i, config.t, config.s, config.g)}

        <div id="aeatCooldownBanner" style="display:none; align-items:center; gap:12px; padding:15px 20px; margin-bottom:20px; background:linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); color:#78350f; border-radius:15px; font-weight:700; font-size:1rem; box-shadow:0 10px 15px -3px rgba(245, 158, 11, 0.2)">
            <i class="fas fa-hourglass-half fa-spin"></i>
            <span>Esperando ventana de envío AEAT...</span>
        </div>

        <div class="verifactu-controls-bar" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:25px; background:var(--bg-card); padding:10px 15px; border-radius:15px; border:1px solid var(--border-main); box-shadow:var(--shadow-sm)">
            <div class="verifactu-tabs-modern" style="display:flex; gap:5px;">
                <button class="v-tab-btn-modern ${verifactuTabActual === 'cola' ? 'active' : ''}" onclick="cargarTabEnvios('cola')">
                    <i class="fas fa-list-ul"></i> Cola de Envíos
                </button>
                <button class="v-tab-btn-modern ${verifactuTabActual === 'eventos' ? 'active' : ''}" onclick="cargarTabEnvios('eventos')">
                    <i class="fas fa-history"></i> Libro de Eventos
                </button>
                <button class="v-tab-btn-modern ${verifactuTabActual === 'stats' ? 'active' : ''}" onclick="cargarTabEnvios('stats')">
                    <i class="fas fa-chart-line"></i> Estadísticas
                </button>
            </div>
            
            <div style="display: flex; gap: 10px;">
                <button id="btnLimpiarColaHeader" onclick="limpiarColaEnvios()" style="display:none; padding:10px 20px; border-radius:10px; border:none; background:#fee2e2; color:#991b1b; cursor:pointer; font-weight:700; font-size:0.85rem; transition:all 0.2s ease;">
                    <i class="fas fa-trash-alt"></i> Limpiar Cola
                </button>
                <button id="btnProcesarColaHeader" onclick="procesarColaManual()" style="display:none; padding:10px 20px; border-radius:10px; border:none; background:linear-gradient(135deg, #3b82f6, #2563eb); color:white; cursor:pointer; font-weight:700; font-size:0.85rem; box-shadow:0 4px 10px rgba(37, 99, 235, 0.2); transition:all 0.2s ease;">
                    <i class="fas fa-paper-plane"></i> Procesar Pendientes
                </button>
                <button id="btnLimpiarLibroHeader" onclick="limpiarLibroEventos()" style="display:none; padding:10px 20px; border-radius:10px; border:none; background:#fee2e2; color:#991b1b; cursor:pointer; font-weight:700; font-size:0.85rem; transition:all 0.2s ease;">
                    <i class="fas fa-broom"></i> Limpiar Libro
                </button>
            </div>
        </div>

        <div id="verifactuContenidoTab" class="verifactu-content-card" style="background: var(--bg-card); border: 1px solid var(--border-main); border-radius: 20px; padding: 15px 25px; box-shadow: var(--shadow-sm); min-height: 350px;">
            <div style="display:flex; flex-direction:column; align-items:center; justify-content:center; height:250px; color:var(--text-muted)">
                <i class="fas fa-circle-notch fa-spin fa-3x" style="margin-bottom:15px; color:var(--accent-main)"></i>
                <p style="font-weight:600">Sincronizando con AEAT...</p>
            </div>
        </div>
    </div>
    <style>
        .v-tab-btn-modern {
            padding: 10px 20px;
            border: none;
            background: transparent;
            cursor: pointer;
            font-weight: 700;
            font-size: 0.9rem;
            color: var(--text-muted);
            border-radius: 10px;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .v-tab-btn-modern:hover {
            background: rgba(0,0,0,0.03);
            color: var(--text-main);
        }
        .v-tab-btn-modern.active {
            background: var(--bg-main);
            color: var(--accent-main);
            box-shadow: var(--shadow-sm);
        }
    </style>
    `;
}
function cargarTabEnvios(tab, pagina = 1) {
    verifactuTabActual = tab;
    verifactuPaginaActual = pagina;

    // Actualizar UI tabs (clases modernas)
    document.querySelectorAll('.v-tab-btn-modern').forEach(btn => {
        btn.classList.remove('active');
    });
    // Buscar por texto o por una mejor forma si fuera posible, pero basándonos en el onclick es seguro
    const activeBtn = document.querySelector(`.v-tab-btn-modern[onclick*="'${tab}'"]`);
    if (activeBtn) activeBtn.classList.add('active');

    // Mostrar/ocultar botones de la cabecera
    const btnLimpiarCola = document.getElementById('btnLimpiarColaHeader');
    const btnProcesarCola = document.getElementById('btnProcesarColaHeader');
    const btnLimpiarLibro = document.getElementById('btnLimpiarLibroHeader');

    if (btnLimpiarCola) btnLimpiarCola.style.display = (tab === 'cola') ? 'block' : 'none';
    if (btnProcesarCola) btnProcesarCola.style.display = (tab === 'cola') ? 'block' : 'none';
    if (btnLimpiarLibro) btnLimpiarLibro.style.display = (tab === 'eventos') ? 'block' : 'none';

    const contenedor = document.getElementById('verifactuContenidoTab');
    contenedor.innerHTML = '<div style="text-align:center; padding:40px;"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';

    if (tab === 'cola') {
        fetch(`api/verifactu-cola.php?pendientes=1&page=${verifactuPaginaActual}&limit=${verifactuLimitePorPagina}`)
            .then(r => r.json())
            .then(data => {
                verifactuColaCache = data.envios || [];
                renderColaEnvios(data);
            })
            .catch(e => contenedor.innerHTML = '<p style="color:red">Error cargando cola.</p>');
    } else if (tab === 'eventos') {
        fetch(`api/verifactu-cola.php?eventos=1&page=${verifactuPaginaActual}`)
            .then(r => r.json())
            .then(data => renderLibroEventos(data.eventos))
            .catch(e => contenedor.innerHTML = '<p style="color:red">Error cargando eventos.</p>');
    } else if (tab === 'stats') {
        fetch('api/verifactu-cola.php?estadisticas=1')
            .then(r => r.json())
            .then(data => renderStatsEnvios(data))
            .catch(e => contenedor.innerHTML = '<p style="color:red">Error cargando estadísticas.</p>');
    }
}

function renderColaEnvios(data) {
    const envios = data.envios || [];
    const totalPages = data.pages || 1;
    const contenedor = document.getElementById('verifactuContenidoTab');

    if (!envios || envios.length === 0) {
        contenedor.innerHTML = `
            <div style="text-align:center; padding: 50px; color: var(--text-muted);">
                <i class="fas fa-check-circle fa-4x" style="color: #10b981; margin-bottom: 20px;"></i>
                <h3>Cola Vacía</h3>
                <p>Todos los documentos han sido enviados a la AEAT correctamente.</p>
            </div>
        `;
        return;
    }

    let html = `
        <div class="modern-table-container" style="max-height: 450px; overflow-y:auto;">
            <table class="modern-table" style="width:100%">
                <thead>
                    <tr>
                        <th>Documento</th>
                        <th>Origen</th>
                        <th>Estado Cola</th>
                        <th style="text-align:center">Intentos</th>
                        <th>Próx. Reintento</th>
                        <th>Último Mensaje AEAT</th>
                        <th style="text-align:right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
    `;

    envios.forEach(e => {
        let badgeClass = 'badge-gray';
        let estadoTexto = (e.estado || 'ERROR').replace('_', ' ').toUpperCase();
        let aeatBadge = '';

        if (e.estado === 'pendiente') badgeClass = 'badge-warning';
        if (e.estado === 'subsanado') badgeClass = 'badge-info';
        if (e.estado === 'enviando') badgeClass = 'badge-accent';
        if (e.estado === 'error_temporal') badgeClass = 'badge-danger';
        if (e.estado === 'error_permanente') badgeClass = 'badge-danger';
        if (e.estado === 'enviado') badgeClass = 'badge-success';

        if (e.respuesta_xml) {
            let relevantXml = e.respuesta_xml;
            
            // ✅ FIX: Si es un lote (varias respuestas en el mismo XML), 
            // buscamos el bloque específico de este documento para que el regex no coja el de otro.
            if (e.num_documento) {
                const regexLinea = new RegExp('<[^:]*:RespuestaLinea>[^]*?' + e.num_documento + '[^]*?</[^:]*:RespuestaLinea>', 'i');
                const matchLinea = e.respuesta_xml.match(regexLinea);
                if (matchLinea) {
                    relevantXml = matchLinea[0];
                }
            }

            let aeatEstadoStr = '';
            let aeatColor = '#64748b';
            const camposAEAT = [/<EstadoEnvio[^>]*>([^<]+)<\//, /<EstadoRegistro[^>]*>([^<]+)<\//, /<ResultadoRegistro[^>]*>([^<]+)<\//, /EstadoRespuesta[^>]*>([^<]+)<\//, /<CodigoEstado[^>]*>([^<]+)<\//];
            for (let regex of camposAEAT) {
                let m = relevantXml.match(regex);
                if (m && m[1]) { aeatEstadoStr = m[1].trim(); break; }
            }
            let hayErrores = relevantXml.includes('<CodigoError>') || relevantXml.includes('<Error>') || relevantXml.includes('CodigoErrorRegistro');
            let hayAvisos = relevantXml.includes('<Aviso>') || relevantXml.includes('"Avisos":');
            let esRechazado = relevantXml.includes('Rechazado') || relevantXml.includes('"Rechazado"') || relevantXml.includes('Rechazada') || relevantXml.includes('Incorrecto');
            let esAceptadoErrores = relevantXml.includes('AceptadoConErrores') || relevantXml.includes('"AceptadoConErrores"') || relevantXml.includes('Aceptado con errores');

            if (esRechazado) { aeatEstadoStr = 'Rechazado'; aeatColor = '#ef4444'; }
            else if (esAceptadoErrores || (aeatEstadoStr === 'Correcto' && hayErrores)) { aeatEstadoStr = 'Aceptado c/Errores'; aeatColor = '#f59e0b'; }
            else if (hayAvisos) { aeatEstadoStr = 'Correcto (Avisos)'; aeatColor = '#3b82f6'; }
            else if (aeatEstadoStr === 'Correcto' || aeatEstadoStr === 'Aceptado') { aeatEstadoStr = 'Correcto'; aeatColor = '#10b981'; }

            if (aeatEstadoStr) {
                aeatBadge = `<span class="badge-aeat-mini" style="background:${aeatColor}" title="Respuesta oficial AEAT">${aeatEstadoStr}</span>`;
            }
        }

        const mostrarErrorConexion = e.es_error_conexion == 1 && e.estado !== 'enviado';

        html += `
            <tr class="${mostrarErrorConexion ? 'row-warning-soft' : ''}">
                <td style="font-weight:700; color:var(--text-main)">${e.display_num || e.num_documento || '#' + e.id_documento}</td>
                <td><span class="text-capitalize">${e.tabla_origen}</span></td>
                <td>
                    <div style="display:flex; flex-direction:column; gap:4px; align-items:flex-start;">
                        <span class="badge-premium ${badgeClass}" style="font-size:0.7rem">
                            ${e.estado === 'pendiente' ? 'REINTENTO AUTOMÁTICO' : (e.estado === 'subsanado' ? 'SUBSANADO MANUAL' : estadoTexto)}
                        </span>
                        ${aeatBadge}
                    </div>
                </td>
                <td style="text-align:center">
                    <div class="progress-mini-container" title="${e.intentos} de ${e.max_intentos} intentos">
                        <div class="progress-mini-bar" style="width:${(e.intentos / e.max_intentos) * 100}%"></div>
                        <span class="progress-mini-text">${e.intentos}/${e.max_intentos}</span>
                    </div>
                </td>
                <td style="font-size:0.8rem; color:var(--text-muted)">${e.proximo_reintento || '<span style="opacity:0.3">—</span>'}</td>
                <td>
                    <div class="error-cell-premium" title="${e.ultimo_error || ''}">
                        ${e.codigo_error_aeat ? `<span class="error-code-aeat">${e.codigo_error_aeat}</span>` : ''}
                        <span class="error-text-aeat">${e.ultimo_error || 'Sin errores registrados'}</span>
                    </div>
                </td>
                <td style="text-align:right">
                    <div style="display:flex; justify-content:flex-end; gap:5px;">
                        <button class="btn-table-icon" onclick="verDetallesEnvioManual(${e.id})" title="Ver Detalles AEAT"><i class="fas fa-eye"></i></button>
                        ${(e.estado !== 'enviado' && e.estado !== 'enviando') ? `
                            <button class="btn-table-icon accent" onclick="reenviarEnvioManual(${e.id})" title="Forzar Reintento"><i class="fas fa-sync-alt"></i></button>
                            <button class="btn-table-icon" onclick="abrirEditorDocumentoAeat(${e.id_documento}, '${e.tabla_origen}', '${e.display_num || e.num_documento}')" title="Editar datos"><i class="fas fa-edit"></i></button>
                        ` : (e.estado === 'enviado' ? `<i class="fas fa-check-circle" style="color:#10b981; font-size:1.2rem; padding:5px" title="Enviado OK"></i>` : '<i class="fas fa-spinner fa-spin" style="padding:5px"></i>')}
                        
                        ${(e.estado === 'error_permanente' || (e.codigo_error_aeat)) && (e.estado !== 'enviado' && e.estado !== 'pendiente' && e.estado !== 'enviando') ? `
                            <button class="btn-table-icon success" onclick="subsanarDocumentoManual(${e.id_documento}, '${e.tabla_origen}')" title="Subsanar"><i class="fas fa-check-double"></i></button>
                            <button class="btn-table-icon danger" onclick="descartarEnvioManual(${e.id})" title="Descartar"><i class="fas fa-times"></i></button>
                        ` : ''}
                    </div>
                </td>
            </tr>
        `;
    });

    html += `</tbody></table></div>`;

    // Añadir controles de paginación modernos
    html += `
        <div class="modern-pagination" style="display:flex; justify-content:space-between; align-items:center; margin-top:25px; padding:15px; background:var(--bg-panel); border-radius:15px; border:1px solid var(--border-main)">
            <div style="color:var(--text-muted); font-size:0.85rem; font-weight:600">
                Página <span style="color:var(--text-main)">${verifactuPaginaActual}</span> de <span style="color:var(--text-main)">${totalPages}</span>
            </div>
            <div style="display:flex; gap:10px;">
                <button class="btn-pagination-modern" onclick="cargarTabEnvios('cola', ${verifactuPaginaActual - 1})" ${verifactuPaginaActual <= 1 ? 'disabled' : ''}>
                    <i class="fas fa-chevron-left"></i> Anterior
                </button>
                <button class="btn-pagination-modern" onclick="cargarTabEnvios('cola', ${verifactuPaginaActual + 1})" ${verifactuPaginaActual >= totalPages ? 'disabled' : ''}>
                    Siguiente <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>
        <style>
            .btn-pagination-modern {
                padding: 8px 18px;
                border-radius: 10px;
                border: 1px solid var(--border-main);
                background: var(--bg-card);
                color: var(--text-main);
                font-weight: 700;
                font-size: 0.85rem;
                cursor: pointer;
                transition: all 0.2s ease;
                display: flex;
                align-items: center;
                gap: 8px;
            }
            .btn-pagination-modern:hover:not(:disabled) {
                background: var(--bg-main);
                border-color: var(--accent-main);
                color: var(--accent-main);
                transform: translateY(-1px);
            }
            .btn-pagination-modern:disabled {
                opacity: 0.4;
                cursor: not-allowed;
            }
        </style>
    `;

    contenedor.innerHTML = html;
}

function renderLibroEventos(eventos) {
    const contenedor = document.getElementById('verifactuContenidoTab');
    let html = `
        <div class="modern-table-container animate-fade-in" style="max-height: 300px; overflow-y:auto;">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>Fecha y Hora</th>
                        <th>Tipo de Evento</th>
                        <th>Documento</th>
                        <th>Descripción Técnica</th>
                    </tr>
                </thead>
                <tbody>
    `;

    if (!eventos || eventos.length === 0) {
        html += `
            <tr>
                <td colspan="4" style="padding: 60px; text-align: center; color: var(--text-muted);">
                    <i class="fas fa-stream fa-3x" style="opacity:0.2; margin-bottom:15px;"></i>
                    <p>No hay eventos registrados en el libro fiscal.</p>
                </td>
            </tr>
        `;
    } else {
        eventos.forEach(e => {
            let icon = 'fa-info-circle';
            let badgeClass = 'badge-gray';

            if (e.tipo.includes('error') || e.tipo.includes('fallida')) { icon = 'fa-exclamation-triangle'; badgeClass = 'badge-danger'; }
            if (e.tipo.includes('ok') || e.tipo.includes('recuperada')) { icon = 'fa-check-circle'; badgeClass = 'badge-success'; }
            if (e.tipo.includes('perdida')) { icon = 'fa-wifi-slash'; badgeClass = 'badge-warning'; }
            if (e.tipo === 'subsanacion') { icon = 'fa-tools'; badgeClass = 'badge-info'; }

            html += `
                <tr>
                    <td style="color:var(--text-muted); font-size:0.85rem; font-weight:600">${e.fecha}</td>
                    <td>
                        <span class="badge-premium ${badgeClass}" style="gap:6px;">
                            <i class="fas ${icon}"></i> ${e.tipo.replace('_', ' ').toUpperCase()}
                        </span>
                    </td>
                    <td style="font-weight:700; color:var(--accent-main)">${e.display_num || (e.id_documento ? '#' + e.id_documento : '<span style="opacity:0.3">—</span>')}</td>
                    <td style="font-size:0.85rem; line-height:1.4">${e.descripcion}</td>
                </tr>
            `;
        });
    }

    html += `</tbody></table></div>`;
    contenedor.innerHTML = html;
}

function renderStatsEnvios(stats) {
    const contenedor = document.getElementById('verifactuContenidoTab');

    contenedor.innerHTML = `
        <div class="verifactu-stats-grid animate-fade-in" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px;">
            <div class="stat-card-premium warning">
                <div class="stat-icon"><i class="fas fa-clock"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Pendientes</span>
                    <span class="stat-value">${stats.pendientes || 0}</span>
                </div>
            </div>
            
            <div class="stat-card-premium success">
                <div class="stat-icon"><i class="fas fa-check-double"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Enviados Hoy</span>
                    <span class="stat-value">${stats.enviados_hoy || 0}</span>
                </div>
            </div>

            <div class="stat-card-premium danger">
                <div class="stat-icon"><i class="fas fa-exclamation-circle"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Errores de Red</span>
                    <span class="stat-value">${stats.sin_conexion || 0}</span>
                </div>
            </div>

            <div class="stat-card-premium critical">
                <div class="stat-icon"><i class="fas fa-ban"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Err. Permanentes</span>
                    <span class="stat-value">${stats.errores_permanentes || 0}</span>
                </div>
            </div>
        </div>
        <style>
            .stat-card-premium {
                background: var(--bg-card);
                border: 1px solid var(--border-main);
                padding: 25px;
                border-radius: 20px;
                display: flex;
                align-items: center;
                gap: 20px;
                box-shadow: var(--shadow-sm);
                transition: all 0.3s ease;
            }
            .stat-card-premium:hover {
                transform: translateY(-5px);
                box-shadow: var(--shadow-md);
                border-color: var(--accent-main);
            }
            .stat-icon {
                width: 55px;
                height: 55px;
                border-radius: 15px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 1.5rem;
                flex-shrink: 0;
            }
            .stat-info {
                display: flex;
                flex-direction: column;
            }
            .stat-label {
                font-size: 0.85rem;
                font-weight: 700;
                color: var(--text-muted);
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            .stat-value {
                font-size: 2rem;
                font-weight: 800;
                color: var(--text-main);
            }
            
            .stat-card-premium.warning .stat-icon { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
            .stat-card-premium.success .stat-icon { background: rgba(16, 185, 129, 0.1); color: #10b981; }
            .stat-card-premium.danger .stat-icon { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
            .stat-card-premium.critical .stat-icon { background: rgba(153, 27, 27, 0.1); color: #991b1b; }
        </style>
    `;
}

// ======================== ACCIONES ========================

function procesarColaManual() {
    const btn = document.getElementById('btnProcesarColaHeader');
    if (!btn) return;
    const prevHtml = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
    btn.disabled = true;

    fetch('api/verifactu-cola.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'procesarCola' })
    })
        .then(r => r.json())
        .then(data => {
            if (data.ok) {
                // Iniciar cooldown si viene en la respuesta
                const cd = data.resumen.cooldown_segundos || 0;
                if (cd > 0) {
                    iniciarCooldownVisual(cd);
                }

                let icon = 'success';
                let title = 'Proceso completado';

                if (data.resumen.procesados === 0 && cd > 0) {
                    icon = 'info';
                    title = 'En espera AEAT';
                } else if (data.resumen.procesados === 0) {
                    icon = 'info';
                    title = 'Sin pendientes';
                } else if (data.resumen.fallidos > 0) {
                    icon = 'warning';
                    title = 'Proceso con errores';
                }

                let htmlMsg = `
                <div style="font-size: 1.1em; text-align: left; display: inline-block;">
                    <p style="margin: 5px 0;"><b>Procesados:</b> ${data.resumen.procesados}</p>
                    <p style="margin: 5px 0; color: #10b981;"><b>Exitosos:</b> ${data.resumen.exitosos}</p>
                    <p style="margin: 5px 0; color: #ef4444;"><b>Fallidos:</b> ${data.resumen.fallidos}</p>
                    ${cd > 0 ? `<p style="margin: 10px 0 5px; color: #f59e0b;"><i class="fas fa-hourglass-half"></i> <b>Cooldown AEAT:</b> ${formatCooldown(cd)}</p>` : ''}
                </div>
            `;

                Swal.fire({
                    title: title,
                    html: htmlMsg,
                    icon: icon,
                    background: 'var(--bg-panel)',
                    color: 'var(--text-main)',
                    iconColor: 'var(--accent-main)'
                });
                if (seccionActual === 'envios-aeat') cargarTabEnvios(verifactuTabActual);
                actualizarBadgePendientesAeat();
            } else {
                Swal.fire('Error', data.error || 'Error al procesar la cola', 'error');
            }
        })
        .catch(e => {
            console.error(e);
            Swal.fire('Error', 'Fallo de conexión', 'error');
        })
        .finally(() => {
            btn.innerHTML = prevHtml;
            btn.disabled = false;
        });
}

function abrirEditorDocumentoAeat(idDoc, tabla, numDoc) {
    // Cargar detalles actuales para el formulario
    fetch(`api/ventas.php?detalleVenta=${idDoc}`)
        .then(r => r.json())
        .then(data => {
            if (data.error) { alert(data.error); return; }
            const v = data.venta;

            let html = `
                <div style="padding:0;">
                    <!-- Cabecera Premium -->
                    <div style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; padding: 25px; border-radius: 12px 12px 0 0; text-align: center;">
                        <i class="fas fa-file-invoice" style="font-size: 2.5rem; margin-bottom: 15px; opacity: 0.9;"></i>
                        <h3 style="margin:0; font-size: 1.4rem;">Corregir Datos Fiscales</h3>
                        <p style="margin: 5px 0 0; opacity: 0.8; font-size: 0.9rem;">Documento: <b>${numDoc}</b></p>
                    </div>

                    <div style="padding: 25px;">
                        <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 20px; line-height: 1.4;">
                            <i class="fas fa-info-circle" style="color: #3b82f6;"></i> Modifique los datos del cliente para que cumplan con las reglas de validación de la AEAT.
                        </p>

                        <!-- Campo NIF -->
                        <div style="margin-bottom: 20px;">
                            <label style="display:block; margin-bottom: 8px; font-weight: 600; color: var(--text-main); font-size: 0.9rem;">
                                <i class="fas fa-id-card" style="width: 20px;"></i> NIF / DNI del Cliente
                            </label>
                            <input type="text" id="editAeatNif" class="filtro-input" value="${v.cliente_dni || ''}" 
                                   placeholder="Ej: 12345678Z"
                                   style="width:100%; box-sizing:border-box; padding: 12px; border: 2px solid var(--border-main); border-radius: 8px; font-size: 1rem; transition: border-color 0.2s;">
                        </div>

                        <!-- Campo Nombre -->
                        <div style="margin-bottom: 20px;">
                            <label style="display:block; margin-bottom: 8px; font-weight: 600; color: var(--text-main); font-size: 0.9rem;">
                                <i class="fas fa-user" style="width: 20px;"></i> Nombre o Razón Social
                            </label>
                            <input type="text" id="editAeatNombre" class="filtro-input" value="${v.cliente_nombre || ''}" 
                                   placeholder="Nombre completo o empresa"
                                   style="width:100%; box-sizing:border-box; padding: 12px; border: 2px solid var(--border-main); border-radius: 8px; font-size: 1rem;">
                        </div>

                        <!-- Campo Dirección -->
                        <div style="margin-bottom: 25px;">
                            <label style="display:block; margin-bottom: 8px; font-weight: 600; color: var(--text-main); font-size: 0.9rem;">
                                <i class="fas fa-map-marker-alt" style="width: 20px;"></i> Dirección Fiscal
                            </label>
                            <input type="text" id="editAeatDireccion" class="filtro-input" value="${v.cliente_direccion || ''}" 
                                   placeholder="Calle, número, CP, Ciudad"
                                   style="width:100%; box-sizing:border-box; padding: 12px; border: 2px solid var(--border-main); border-radius: 8px; font-size: 1rem;">
                        </div>

                        <!-- Botones -->
                        <div style="display:flex; justify-content:flex-end; gap:12px; margin-top:10px;">
                            <button class="btn-modal-cancelar" onclick="cerrarModal('modalEditarAeat')" 
                                    style="padding: 10px 20px; font-weight: 600;">
                                Cancelar
                            </button>
                            <button onclick="guardarCambiosDocumentoAeat(${idDoc}, '${tabla}')" 
                                    style="padding: 10px 25px; background: #f59e0b; color: white; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; display: flex; align-items: center; gap: 8px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
                                <i class="fas fa-save"></i> Guardar Cambios
                            </button>
                        </div>
                    </div>
                </div>
            `;

            let modal = document.getElementById('modalEditarAeat');
            if (!modal) {
                modal = document.createElement('div');
                modal.id = 'modalEditarAeat';
                modal.className = 'modal-overlay';
                modal.style.display = 'none';
                modal.innerHTML = '<div class="modal-content" style="max-width:500px; padding:0; border-radius:12px; overflow:hidden; border:none; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.2);"></div>';
                document.body.appendChild(modal);
            }
            modal.querySelector('.modal-content').innerHTML = html;
            modal.style.display = 'flex';
        });
}

function guardarCambiosDocumentoAeat(idDoc, tabla) {
    const data = {
        action: 'editarDocumento',
        id_documento: idDoc,
        tabla: tabla,
        nif: document.getElementById('editAeatNif').value,
        nombre: document.getElementById('editAeatNombre').value,
        direccion: document.getElementById('editAeatDireccion').value
    };

    fetch('api/verifactu-cola.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
        .then(r => r.json())
        .then(res => {
            if (res.ok) {
                cerrarModal('modalEditarAeat');
                Swal.fire({
                    title: 'Éxito',
                    text: 'Datos actualizados correctamente.',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                });
                cargarEnviosAeat(); // Recargar lista
            } else {
                Swal.fire('Error', res.error || 'No se pudo actualizar.', 'error');
            }
        });
}

function reenviarEnvioManual(id) {
    fetch('api/verifactu-cola.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'reenviar', id: id })
    })
        .then(r => r.json())
        .then(data => {
            if (data.ok) {
                cargarTabEnvios('cola');
                actualizarBadgePendientesAeat();
            } else {
                Swal.fire('Error', data.error, 'error');
            }
        });
}

function descartarEnvioManual(id) {
    Swal.fire({
        title: '¿Descartar este envío?',
        text: 'No se volverá a intentar enviar a la AEAT.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, descartar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('api/verifactu-cola.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'descartarError', id: id })
            }).then(() => cargarTabEnvios('cola'));
        }
    });
}

function subsanarDocumentoManual(idDoc, tabla) {
    Swal.fire({
        title: 'Subsanar Documento',
        html: `
            <p style="margin-bottom:15px; font-size:0.9rem;">
                Asegúrate de haber corregido los datos erróneos (ej. NIF del cliente).<br>
                El sistema regenerará el XML y lo encolará como "Subsanación".
            </p>
        `,
        icon: 'info',
        showCancelButton: true,
        confirmButtonText: 'Regenerar y Encolar',
        showLoaderOnConfirm: true,
        preConfirm: () => {
            return fetch('api/verifactu-cola.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'subsanar', id_documento: idDoc, tabla: tabla })
            })
                .then(r => r.json())
                .then(data => {
                    if (!data.success) {
                        if (data.errors) {
                            const errs = data.errors.map(e => `<li><b>${e.field}</b>: ${e.message}</li>`).join('');
                            throw new Error(`Validación pre-envío fallida:<ul style="text-align:left;font-size:0.85rem;margin-top:10px;">${errs}</ul>`);
                        }
                        throw new Error(data.message || 'Error desconocido');
                    }
                    return data;
                })
                .catch(error => {
                    Swal.showValidationMessage(error.message);
                });
        },
        allowOutsideClick: () => !Swal.isLoading()
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire('Subsanado', 'Documento encolado correctamente.', 'success');
            cargarTabEnvios('cola');
        }
    });
}

function limpiarLibroEventos() {
    Swal.fire({
        title: '¿Limpiar libro de eventos?',
        text: 'Se eliminarán todos los registros de eventos de forma permanente.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        confirmButtonText: 'Sí, limpiar todo',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('api/verifactu-cola.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'limpiarEventos' })
            })
                .then(r => r.json())
                .then(data => {
                    if (data.ok) {
                        Swal.fire('¡Limpio!', 'El libro de eventos ha sido vaciado.', 'success');
                        cargarTabEnvios('eventos');
                    } else {
                        Swal.fire('Error', data.error || 'No se pudo limpiar el libro.', 'error');
                    }
                })
                .catch(e => {
                    console.error(e);
                    Swal.fire('Error', 'Fallo de conexión', 'error');
                });
        }
    });
}

function limpiarColaEnvios() {
    Swal.fire({
        title: '¿Limpiar historial de envíos?',
        text: 'Se eliminarán de la lista únicamente los documentos que ya han sido enviados con éxito y los descartados. Los pendientes o con error se mantendrán.',
        icon: 'info',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        confirmButtonText: 'Sí, limpiar historial',
        cancelButtonText: 'Cancelar',
        background: 'var(--bg-panel)',
        color: 'var(--text-main)',
        iconColor: 'var(--accent-main)'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('api/verifactu-cola.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'limpiarColaEnvios' })
            })
                .then(r => r.json())
                .then(data => {
                    if (data.ok) {
                        Swal.fire('¡Historial limpio!', 'Los documentos enviados han sido borrados de la vista.', 'success');
                        if (seccionActual === 'envios-aeat') {
                            cargarTabEnvios(verifactuTabActual);
                        }
                        actualizarBadgePendientesAeat();
                    } else {
                        Swal.fire('Error', data.error || 'No se pudo vaciar la cola.', 'error');
                    }
                })
                .catch(e => {
                    console.error(e);
                    Swal.fire('Error', 'Fallo de conexión', 'error');
                });
        }
    });
}

// ======================== AUTO-RETRY Y BADGE ========================

function actualizarBadgePendientesAeat() {
    fetch('api/verifactu-cola.php?estadisticas=1')
        .then(r => r.json())
        .then(stats => {
            const badge = document.getElementById('badgePendientesAeat');
            if (!badge) return;
            const num = parseInt(stats.pendientes || 0);
            if (num > 0) {
                badge.textContent = num > 99 ? '+99' : num;
                badge.style.display = 'inline-block';
            } else {
                badge.style.display = 'none';
            }
        })
        .catch(e => console.error("Error stats AEAT", e));
}


function verDetallesEnvioManual(idCola) {
    const e = verifactuColaCache.find(x => x.id == idCola);
    if (!e) return;

    const esc = (txt) => {
        if (!txt) return '<i>(Vacío)</i>';
        return txt.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    };

    const formatXml = (xml) => {
        if (!xml) return '';
        let formatted = '';
        let reg = /(>)(<)(\/*)/g;
        xml = xml.replace(reg, '$1\r\n$2$3');
        let pad = 0;
        xml.split('\r\n').forEach(function (node) {
            let indent = 0;
            if (node.match(/.+<\/\w[^>]*>$/)) {
                indent = 0;
            } else if (node.match(/^<\/\w/)) {
                if (pad != 0) pad -= 1;
            } else if (node.match(/^<\w[^>]*[^\/]>.*$/)) {
                indent = 1;
            } else {
                indent = 0;
            }
            formatted += '  '.repeat(pad) + node + '\r\n';
            pad += indent;
        });
        return formatted.trim();
    };

    // Determinar color de estado
    let colorEstado = 'var(--text-muted)'; // gris por defecto
    let bgEstado = 'var(--bg-panel)';
    if (e.estado === 'enviado') { colorEstado = '#10b981'; bgEstado = 'rgba(16, 185, 129, 0.12)'; }
    else if (e.estado === 'pendiente' || e.estado === 'subsanado') { colorEstado = '#f59e0b'; bgEstado = 'rgba(245, 158, 11, 0.12)'; }
    else if (e.estado.includes('error')) { colorEstado = '#ef4444'; bgEstado = 'rgba(239, 68, 68, 0.12)'; }

    // Extraer EstadoRegistro de la respuesta XML
    let aeatEstadoStr = '';
    if (e.respuesta_xml) {
        let m = e.respuesta_xml.match(/EstadoRegistro[^>]*>([^<]+)<\//);
        if (m && m[1]) {
            aeatEstadoStr = m[1];
        }
    }

    let info = `
        <div style="text-align: left; font-family: 'Inter', system-ui, sans-serif; max-height: 80vh; overflow-y: auto; overflow-x: hidden; color: var(--text-main);">
            ${getPremiumHeaderHTML('fa-file-medical-alt', `Detalle de Envío Fiscal`, `Información técnica y respuesta de la AEAT`, 'linear-gradient(135deg, #4f46e5, #3b82f6)')}
            
            <div style="padding: 20px;">
                
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px; border-bottom: 1px dashed var(--border-main); padding-bottom: 15px;">
                    <div>
                        <span style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted); font-weight: 600;">ID de Documento</span>
                        <div style="font-size: 1.5rem; font-weight: 800; color: var(--text-main); margin-top: 4px; display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-file-invoice" style="color: var(--accent-main);"></i>
                            ${e.display_num || e.num_documento}
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <span style="display: inline-block; padding: 6px 14px; background: ${bgEstado}; color: ${colorEstado}; border-radius: 20px; font-size: 0.85rem; font-weight: 700; border: 1px solid rgba(0,0,0,0.05); box-shadow: 0 2px 4px rgba(0,0,0,0.02); text-transform: uppercase; letter-spacing: 0.5px;">
                            ${e.estado}
                        </span>
                        ${aeatEstadoStr ? `
                        <div style="margin-top: 8px;">
                            <span style="font-size: 0.7rem; color: #6b7280; text-transform: uppercase; font-weight: 700; display: block; margin-bottom: 2px;">Respuesta AEAT</span>
                            <span style="display: inline-block; padding: 3px 10px; background: ${aeatEstadoStr === 'Correcto' ? '#ecfdf5' : '#fff7ed'}; color: ${aeatEstadoStr === 'Correcto' ? '#059669' : '#c2410c'}; border-radius: 6px; border: 1px solid ${aeatEstadoStr === 'Correcto' ? '#a7f3d0' : '#fed7aa'}; font-size: 0.8rem; font-weight: 700;">
                                ${aeatEstadoStr}
                            </span>
                        </div>` : ''}
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                    <div style="display: flex; flex-direction: column;">
                        <span style="font-size: 0.75rem; color: #6b7280; font-weight: 600; text-transform: uppercase;">CSV Seguridad AEAT</span>
                        <span style="font-size: 0.9rem; font-family: 'Courier New', monospace; font-weight: 600; color: #10b981; background: #ecfdf5; padding: 4px 8px; border-radius: 4px; border: 1px solid #a7f3d0; margin-top: 4px; display: inline-block; word-break: break-all;">
                            <i class="fas fa-fingerprint" style="margin-right: 5px;"></i>${e.csv_aeat || 'No asignado'}
                        </span>
                    </div>
                    
                    <div style="display: flex; flex-direction: column;">
                        <span style="font-size: 0.75rem; color: #6b7280; font-weight: 600; text-transform: uppercase;">Intentos Realizados</span>
                        <span style="font-size: 0.95rem; font-weight: 600; color: #374151; margin-top: 4px; display: flex; align-items: center; gap: 6px;">
                            <i class="fas fa-sync-alt" style="color: #8b5cf6;"></i> ${e.intentos} de ${e.max_intentos} permitidos
                        </span>
                    </div>

                    <div style="display: flex; flex-direction: column;">
                        <span style="font-size: 0.75rem; color: #6b7280; font-weight: 600; text-transform: uppercase;">Fecha Cola / Registro</span>
                        <span style="font-size: 0.95rem; font-weight: 600; color: #374151; margin-top: 4px; display: flex; align-items: center; gap: 6px;">
                            <i class="far fa-clock" style="color: #f59e0b;"></i> ${e.fecha_creacion}
                        </span>
                    </div>

                    ${e.codigo_error_aeat ? `
                    <div style="display: flex; flex-direction: column;">
                        <span style="font-size: 0.75rem; color: #ef4444; font-weight: 700; text-transform: uppercase;">Código de Rechazo</span>
                        <span style="font-size: 0.95rem; font-weight: 700; color: #b91c1c; background: #fee2e2; padding: 4px 8px; border-radius: 4px; border: 1px solid #fecaca; margin-top: 4px; display: inline-block;">
                            <i class="fas fa-ban" style="margin-right: 5px;"></i> ERROR ${e.codigo_error_aeat}
                        </span>
                    </div>` : ''}
                </div>
            </div>

            <!-- MENSAJE DE ERROR DESTACADO -->
            ${e.ultimo_error ? `
                <div style="background: linear-gradient(to right, #fef2f2, #fff5f5); border-left: 4px solid #ef4444; border-radius: 8px; padding: 16px 20px; margin-bottom: 25px; box-shadow: 0 4px 6px -1px rgba(220, 38, 38, 0.05);">
                    <div style="display: flex; align-items: flex-start; gap: 12px;">
                        <div style="background: #fee2e2; color: #ef4444; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div>
                            <h4 style="margin: 0 0 5px 0; color: #991b1b; font-size: 0.9rem; font-weight: 700;">Mensaje de Error Interno / AEAT</h4>
                            <p style="margin: 0; color: #7f1d1d; font-size: 0.85rem; line-height: 1.5;">${e.ultimo_error}</p>
                        </div>
                    </div>
                </div>
            ` : ''}

            <!-- VENTANAS DE CÓDIGO TIPO IDE (UNO AL LADO DEL OTRO) -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                
                <!-- Petición XML -->
                <div style="background: #1e1e1e; border-radius: 10px; overflow: hidden; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.2), 0 8px 10px -6px rgba(0,0,0,0.1); border: 1px solid #333; display: flex; flex-direction: column;">
                    <div style="background: #2d2d2d; padding: 10px 15px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #111;">
                        <div style="display: flex; gap: 6px;">
                            <div style="width: 12px; height: 12px; border-radius: 50%; background: #ff5f56; border: 1px solid #e0443e;"></div>
                            <div style="width: 12px; height: 12px; border-radius: 50%; background: #ffbd2e; border: 1px solid #dea123;"></div>
                            <div style="width: 12px; height: 12px; border-radius: 50%; background: #27c93f; border: 1px solid #1aab29;"></div>
                        </div>
                        <span style="color: #858585; font-size: 0.75rem; font-family: monospace; font-weight: 600;"><i class="fas fa-upload" style="margin-right: 5px;"></i>request_payload.xml</span>
                    </div>
                    <div style="padding: 15px; overflow-x: auto; flex-grow: 1;">
                        <pre style="margin: 0; font-family: 'Fira Code', 'Consolas', monospace; font-size: 0.8rem; line-height: 1.5; color: #d4d4d4; max-height: 400px; overflow-y: auto;"><code><span style="color: #4fc1ff;">${esc(formatXml(e.xml_contenido))}</span></code></pre>
                    </div>
                </div>

                <!-- Respuesta XML -->
                <div style="background: #1e1e1e; border-radius: 10px; overflow: hidden; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.2), 0 8px 10px -6px rgba(0,0,0,0.1); border: 1px solid #333; display: flex; flex-direction: column;">
                    <div style="background: #2d2d2d; padding: 10px 15px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #111;">
                        <div style="display: flex; gap: 6px;">
                            <div style="width: 12px; height: 12px; border-radius: 50%; background: #ff5f56; border: 1px solid #e0443e;"></div>
                            <div style="width: 12px; height: 12px; border-radius: 50%; background: #ffbd2e; border: 1px solid #dea123;"></div>
                            <div style="width: 12px; height: 12px; border-radius: 50%; background: #27c93f; border: 1px solid #1aab29;"></div>
                        </div>
                        <span style="color: #858585; font-size: 0.75rem; font-family: monospace; font-weight: 600;"><i class="fas fa-download" style="margin-right: 5px;"></i>aeat_response.xml</span>
                    </div>
                    <div style="padding: 15px; overflow-x: auto; flex-grow: 1;">
                        <pre style="margin: 0; font-family: 'Fira Code', 'Consolas', monospace; font-size: 0.8rem; line-height: 1.5; color: #d4d4d4; max-height: 400px; overflow-y: auto;"><code><span style="color: #ce9178;">${esc(formatXml(e.respuesta_xml) || '<!-- Sin respuesta almacenada -->')}</span></code></pre>
                    </div>
                </div>

            </div>
        </div>
        <div style="margin-top: 30px; display: flex; justify-content: flex-end;">
                    <button class="tema-btn-guardar" onclick="Swal.close()" style="background: var(--accent-main); color: white; padding: 10px 25px; border-radius: 12px; font-weight: 700; border: none; cursor: pointer;">
                        Cerrar Detalles
                    </button>
                </div>
            </div>
        </div>
    `;

    Swal.fire({
        html: info,
        width: '1000px',
        showConfirmButton: false,
        padding: '0',
        background: 'var(--bg-card)',
        color: 'var(--text-main)',
        borderRadius: '20px',
        showCloseButton: true
    });
}

// Escuchar evento global de procesamiento en segundo plano (lanzado desde Layout.php)
window.addEventListener('verifactuAutoProcess', (e) => {
    // Si hay cooldown activo, el timer de cooldown se encarga
    if (aeatCooldownSegs > 0) return;

    const data = e.detail;
    if (data && data.ok && data.resumen) {
        if (data.resumen.cooldown_segundos > 0) {
            iniciarCooldownVisual(data.resumen.cooldown_segundos);
        }
        if (data.resumen.procesados > 0) {
            actualizarBadgePendientesAeat();
            if (seccionActual === 'envios-aeat') {
                cargarTabEnvios(verifactuTabActual, verifactuPaginaActual);
            }
        }
    }
});

// Actualizar badge al cargar la página
document.addEventListener('DOMContentLoaded', () => {
    actualizarBadgePendientesAeat();
});
