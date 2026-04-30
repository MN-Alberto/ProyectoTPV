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

    let html = `
    <div class="tema-editor">
        <div class="config-section">
            <div class="tema-seccion-card">
                <div style="
                    background: linear-gradient(135deg, rgba(59, 130, 246, 0.08) 0%, rgba(99, 102, 241, 0.04) 100%);
                    border: 1px solid rgba(59, 130, 246, 0.15);
                    border-radius: 10px;
                    padding: 16px 18px;
                    margin-bottom: 18px;
                    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.05), inset 0 1px 0 rgba(255, 255, 255, 0.6);
                    backdrop-filter: blur(12px);
                    position: relative;
                    overflow: hidden;
                ">
                    <!-- Efecto de brillo superior -->
                    <div style="
                        position: absolute;
                        top: 0;
                        left: 0;
                        right: 0;
                        height: 1px;
                        background: linear-gradient(90deg, transparent, rgba(59, 130, 246, 0.4), transparent);
                    "></div>

                    <div style="
                        display: flex;
                        align-items: center;
                        gap: 12px;
                        margin-bottom: 14px;
                        padding-bottom: 12px;
                        border-bottom: 1px solid rgba(59, 130, 246, 0.1);
                    ">
                        <div style="
                            width: 36px;
                            height: 36px;
                            border-radius: 8px;
                            background: linear-gradient(135deg, #3b82f6 0%, #6366f1 100%);
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            box-shadow: 0 3px 8px rgba(59, 130, 246, 0.3);
                            flex-shrink: 0;
                        ">
                            <i class="fas fa-landmark" style="color: white; font-size: 1.1rem;"></i>
                        </div>
                        
                        <div>
                            <h3 style="
                                margin: 0;
                                font-size: 1.05rem;
                                font-weight: 700;
                                color: var(--text-main);
                                letter-spacing: 0.2px;
                            ">
                                Datos Fiscales del Obligado Emisor
                            </h3>
                            <p style="
                                margin: 2px 0 0 0;
                                font-size: 0.8rem;
                                color: var(--text-muted);
                            ">
                                Datos oficiales requeridos por AEAT
                            </p>
                        </div>
                    </div>
                <div class="tema-seccion-body" style="max-width: 800px; margin: 0 auto;">
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="tema-campo">
                            <label class="tema-label">NIF / CIF</label>
                            <input type="text" id="fiscal_tpv_nif" value="${config.tpv_nif || ''}" 
                                   class="tema-input" style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid var(--border-main); background: var(--bg-input); color: var(--text-main);">
                        </div>
                        
                        <div class="tema-campo">
                            <label class="tema-label">Razón Social</label>
                            <input type="text" id="fiscal_tpv_razon_social" value="${config.tpv_razon_social || ''}" 
                                   class="tema-input" style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid var(--border-main); background: var(--bg-input); color: var(--text-main);">
                        </div>
                    </div>

                    <div class="tema-campo" style="margin-top: 20px;">
                        <label class="tema-label">Dirección Fiscal / Local comercial</label>
                        <input type="text" id="fiscal_tpv_direccion" value="${config.tpv_direccion || ''}" 
                               class="tema-input" style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid var(--border-main); background: var(--bg-input); color: var(--text-main);"
                               placeholder="Ej: C/ Falsa 123, Madrid">
                    </div>

                    <div class="tema-campo" style="margin-top: 20px;">
                        <label class="tema-label">URL Endpoint AEAT (Entorno)</label>
                        <select id="fiscal_aeat_url" class="tema-input" style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid var(--border-main); background: var(--bg-input); color: var(--text-main);">
                            <option value="https://prewww1.aeat.es/wlpl/TIKE-CONT/ws/SistemaFacturacion/VerifactuSOAP" ${(config.aeat_url_verifactu && config.aeat_url_verifactu.indexOf('prewww1') !== -1) ? 'selected' : ''}>PRE-PRODUCCIÓN (Pruebas Reales)</option>
                            <option value="https://www1.aeat.es/wlpl/TIKE-CONT/ws/SistemaFacturacion/VerifactuSOAP" ${(!config.aeat_url_verifactu || (config.aeat_url_verifactu.indexOf('prewww1') === -1 && config.aeat_url_verifactu.indexOf('servidor-falso') === -1)) ? 'selected' : ''}>PRODUCCIÓN (Envío Real)</option>
                            <option value="https://servidor-falso.aeat.es/VerifactuSOAP" ${config.aeat_url_verifactu && config.aeat_url_verifactu.indexOf('servidor-falso') !== -1 ? 'selected' : ''}>⚠️ SIMULAR ERROR (URL inexistente)</option>
                        </select>
                        <p class="tema-ayuda" style="margin-top: 5px; font-size: 0.8rem; color: #6b7280;">Use el entorno de pruebas para validar la conexión antes de pasar a producción.</p>
                    </div>

                    <div class="tema-campo" style="margin-top: 20px;">
                        <label class="tema-label">Intervalo de Reintento (minutos)</label>
                        <input type="number" id="fiscal_verifactu_intervalo_reintento" value="${config.verifactu_intervalo_reintento || 15}" 
                               class="tema-input" style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid var(--border-main); background: var(--bg-input); color: var(--text-main);"
                               min="1" max="1440">
                        <p class="tema-ayuda" style="margin-top: 5px; font-size: 0.8rem; color: #6b7280;">Tiempo de espera antes de reintentar envíos fallidos automáticamente.</p>
                    </div>

                    <div style="margin-top: 30px; border-top: 1px solid var(--border-main); padding-top: 20px;">
                        <h4 style="margin-bottom: 15px; color: var(--text-main);">Certificado Digital (.pfx)</h4>
                        
                        <div class="tema-campo">
                            <label class="tema-label">Ruta absoluta del archivo</label>
                            <input type="text" id="fiscal_cert_path" value="${config.cert_path || ''}" 
                                   class="tema-input" style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid var(--border-main); background: var(--bg-input); color: var(--text-main);"
                                   placeholder="Ej: C:/Proyectos/TPV/certs/mi_cert.pfx">
                        </div>

                        <div class="tema-campo" style="margin-top: 15px;">
                            <label class="tema-label">Contraseña del certificado</label>
                            <div style="position: relative;">
                                <input type="password" id="fiscal_cert_pass" value="${config.cert_pass || ''}" 
                                       class="tema-input" style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid var(--border-main); background: var(--bg-input); color: var(--text-main);">
                                <i class="fas fa-eye" onclick="togglePassword('fiscal_cert_pass')" style="position: absolute; right: 10px; top: 12px; cursor: pointer; color: var(--text-muted);"></i>
                            </div>
                        </div>
                    </div>

                    <div class="tema-botones" style="margin-top:35px;border-top:1px solid var(--border-main);padding-top:20px;display: flex; gap: 10px; justify-content: flex-end;">
                        <button class="btn-exito tema-btn-guardar" onclick="guardarConfiguracionFiscal()">
                            <i class="fas fa-save"></i> Guardar Configuración Fiscal
                        </button>
                    </div>
                </div>
            </div>
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
    contenedor.innerHTML = `
    <div class="verifactu-dashboard">
        <div class="verifactu-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap: wrap; gap: 10px; margin-bottom:20px;">
            <h2><i class="fas fa-satellite-dish" style="color:var(--accent-main)"></i> Monitor Verifactu AEAT</h2>
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <button id="btnLimpiarColaHeader" onclick="limpiarColaEnvios()" style="display:none; padding: 8px 15px; border-radius: 6px; border: none; background: #ef4444; color: white; cursor: pointer; font-weight: 500;">
                    <i class="fas fa-trash-alt"></i> Limpiar Cola
                </button>
                <button id="btnProcesarColaHeader" onclick="procesarColaManual()" style="display:none; padding: 8px 15px; border-radius: 6px; border: none; background: #3b82f6; color: white; cursor: pointer; font-weight: 500;">
                    <i class="fas fa-sync-alt"></i> Procesar Pendientes
                </button>
                <button id="btnLimpiarLibroHeader" onclick="limpiarLibroEventos()" style="display:none; padding: 8px 15px; border-radius: 6px; border: none; background: #ef4444; color: white; cursor: pointer; font-weight: 500;">
                    <i class="fas fa-trash-alt"></i> Limpiar Libro
                </button>
            </div>
        </div>

        <div id="aeatCooldownBanner" style="display:none; align-items:center; gap:10px; padding:10px 18px; margin-bottom:15px; background:linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); color:#78350f; border-radius:8px; font-weight:600; font-size:0.95rem;">
            <i class="fas fa-hourglass-half"></i>
            <span>Esperando AEAT...</span>
        </div>

        <div class="verifactu-tabs" style="display:flex; gap:10px; margin-bottom:20px; border-bottom: 1px solid var(--border-main); padding-bottom: 10px;">
            <button class="v-tab-btn ${verifactuTabActual === 'cola' ? 'active' : ''}" onclick="cargarTabEnvios('cola')" style="padding: 8px 15px; border: none; background: transparent; cursor: pointer; font-weight: 600; color: ${verifactuTabActual === 'cola' ? 'var(--accent-main)' : 'var(--text-muted)'}; border-bottom: ${verifactuTabActual === 'cola' ? '2px solid var(--accent-main)' : 'none'};">
                <i class="fas fa-list"></i> Cola de Envíos
            </button>
            <button class="v-tab-btn ${verifactuTabActual === 'eventos' ? 'active' : ''}" onclick="cargarTabEnvios('eventos')" style="padding: 8px 15px; border: none; background: transparent; cursor: pointer; font-weight: 600; color: ${verifactuTabActual === 'eventos' ? 'var(--accent-main)' : 'var(--text-muted)'}; border-bottom: ${verifactuTabActual === 'eventos' ? '2px solid var(--accent-main)' : 'none'};">
                <i class="fas fa-book"></i> Libro de Eventos
            </button>
            <button class="v-tab-btn ${verifactuTabActual === 'stats' ? 'active' : ''}" onclick="cargarTabEnvios('stats')" style="padding: 8px 15px; border: none; background: transparent; cursor: pointer; font-weight: 600; color: ${verifactuTabActual === 'stats' ? 'var(--accent-main)' : 'var(--text-muted)'}; border-bottom: ${verifactuTabActual === 'stats' ? '2px solid var(--accent-main)' : 'none'};">
                <i class="fas fa-chart-pie"></i> Estadísticas
            </button>
        </div>

        <div id="verifactuContenidoTab" style="background: var(--bg-secondary); border: 1px solid var(--border-main); border-radius: 8px; padding: 20px;">
            <div style="text-align:center; padding:40px;"><i class="fas fa-spinner fa-spin fa-2x"></i></div>
        </div>
    </div>
    `;
}

function cargarTabEnvios(tab, pagina = 1) {
    verifactuTabActual = tab;
    verifactuPaginaActual = pagina;

    // Actualizar UI tabs
    document.querySelectorAll('.v-tab-btn').forEach(btn => {
        btn.style.color = 'var(--text-muted)';
        btn.style.borderBottom = 'none';
    });
    const activeBtn = document.querySelector(`.v-tab-btn[onclick="cargarTabEnvios('${tab}')"]`);
    if (activeBtn) {
        activeBtn.style.color = 'var(--accent-main)';
        activeBtn.style.borderBottom = '2px solid var(--accent-main)';
    }

    // Mostrar/ocultar botones de la cabecera
    document.getElementById('btnLimpiarColaHeader').style.display = (tab === 'cola') ? 'block' : 'none';
    document.getElementById('btnProcesarColaHeader').style.display = (tab === 'cola') ? 'block' : 'none';
    document.getElementById('btnLimpiarLibroHeader').style.display = (tab === 'eventos') ? 'block' : 'none';

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
        <table class="tema-tabla" style="width:100%; border-collapse: collapse;">
            <thead style="position: sticky; top: 0; z-index: 10; background: #374151; color: white;">
                <tr>
                    <th style="padding: 12px; text-align: left;">ID Doc</th>
                    <th style="padding: 12px; text-align: left;">Origen</th>
                    <th style="padding: 12px; text-align: left;">Estado</th>
                    <th style="padding: 12px; text-align: center;">Intentos</th>
                    <th style="padding: 12px; text-align: left;">Próx. Reintento</th>
                    <th style="padding: 12px; text-align: left;">Último Error</th>
                    <th style="padding: 12px; text-align: right;">Acciones</th>
                </tr>
            </thead>
            <tbody>
    `;

    envios.forEach(e => {
        let badgeColor = '#6b7280';
        let estadoTexto = (e.estado || 'ERROR').replace('_', ' ').toUpperCase();
        let aeatEstadoStr = '';
        let aeatBadge = '';

        if (e.estado === 'pendiente') badgeColor = '#f59e0b';
        if (e.estado === 'subsanado') badgeColor = '#6366f1';
        if (e.estado === 'enviando') badgeColor = '#3b82f6';
        if (e.estado === 'error_temporal') badgeColor = '#ef4444';
        if (e.estado === 'error_permanente') badgeColor = '#991b1b';
        if (e.estado === 'enviado') badgeColor = '#10b981';

        // Extraer estado REAL de respuesta AEAT con logica correcta
        if (e.respuesta_xml) {
            // NO declarar variable de nuevo, usar la que esta definida fuera
            aeatEstadoStr = '';
            let aeatColor = '#6b7280';

            // DEBUG: Sacar en consola para ver que recibimos
            console.log('✅ DEBUG AEAT RESPONSE id=' + e.id, e.respuesta_xml.substring(0, 500));

            // PROBAR TODAS LAS VARIANTES DE CAMPOS QUE USA LA AEAT
            let m;
            const camposAEAT = [
                /<EstadoEnvio[^>]*>([^<]+)<\//,
                /<EstadoRegistro[^>]*>([^<]+)<\//,
                /<ResultadoRegistro[^>]*>([^<]+)<\//,
                /EstadoRespuesta[^>]*>([^<]+)<\//,
                /<CodigoEstado[^>]*>([^<]+)<\//
            ];

            for (let regex of camposAEAT) {
                m = e.respuesta_xml.match(regex);
                if (m && m[1]) {
                    aeatEstadoStr = m[1].trim();
                    console.log('✅ ENCONTRADO CAMPO AEAT:', regex, aeatEstadoStr);
                    break;
                }
            }

            // Buscar errores o avisos existentes
            let hayErrores = e.respuesta_xml.includes('<CodigoError>') || e.respuesta_xml.includes('<Error>');
            let hayAvisos = e.respuesta_xml.includes('<Aviso>') || e.respuesta_xml.includes('"Avisos":');
            let esRechazado = e.respuesta_xml.includes('Rechazado') || e.respuesta_xml.includes('"Rechazado"') || e.respuesta_xml.includes('Rechazada');
            let esAceptadoErrores = e.respuesta_xml.includes('AceptadoConErrores') || e.respuesta_xml.includes('"AceptadoConErrores"') || e.respuesta_xml.includes('Aceptado con errores');

            // Determinar estado FINAL real
            if (esRechazado) {
                aeatEstadoStr = 'Rechazado';
                aeatColor = '#ef4444';
            } else if (esAceptadoErrores || (aeatEstadoStr === 'Correcto' && hayErrores)) {
                aeatEstadoStr = 'Aceptado c/Errores';
                aeatColor = '#f59e0b';
            } else if (hayAvisos) {
                aeatEstadoStr = 'Correcto (Avisos)';
                aeatColor = '#6366f1';
            } else if (aeatEstadoStr === 'Correcto' || aeatEstadoStr === 'Aceptado') {
                aeatEstadoStr = 'Correcto';
                aeatColor = '#10b981';
            } else if (aeatEstadoStr) {
                aeatColor = '#6b7280';
            }

            // Crear badge solo si tenemos estado valido
            if (aeatEstadoStr) {
                aeatBadge = `<span style="background: ${aeatColor}; color: white; padding: 2px 6px; border-radius: 8px; font-size: 0.65rem; font-weight: bold; margin-left: 8px; display: inline-block;" title="Respuesta oficial AEAT">
                    ${aeatEstadoStr}
                </span>`;
            }
        }

        const esErrorAEAT = e.codigo_error_aeat ? true : false;
        const mostrarErrorConexion = e.es_error_conexion == 1 && e.estado !== 'enviado';

        html += `
            <tr style="border-bottom: 1px solid var(--border-main); background: ${mostrarErrorConexion ? 'rgba(245, 158, 11, 0.05)' : 'transparent'};">
                <td style="padding: 12px; font-weight: bold;">${e.display_num || e.num_documento || '#' + e.id_documento}</td>
                <td style="padding: 12px; text-transform: capitalize;">${e.tabla_origen}</td>
                <td style="padding: 12px;">
                    <span style="background: ${badgeColor}; color: white; padding: 3px 8px; border-radius: 12px; font-size: 0.75rem; font-weight: bold;">
                        ${e.estado === 'pendiente' ? 'PENDIENTE POR COOLDOWN' : (e.estado === 'subsanado' ? 'SUBSANADO (MANUAL)' : estadoTexto)}
                    </span>
                    ${aeatBadge}
                    ${mostrarErrorConexion ? '<br><i class="fas fa-wifi" style="color:#ef4444; margin-top:4px;" title="Error de Conexión"></i>' : ''}
                </td>
                <td style="padding: 12px; text-align: center;">${e.intentos}/${e.max_intentos}</td>
                <td style="padding: 12px; font-size: 0.85rem;">${e.proximo_reintento || '-'}</td>
                <td style="padding: 12px; font-size: 0.85rem; max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="${e.ultimo_error || ''}">
                    ${e.codigo_error_aeat ? `<b>[${e.codigo_error_aeat}]</b> ` : ''}${e.ultimo_error || '-'}
                </td>
                <td style="padding: 12px; text-align: right;">
                    <button onclick="verDetallesEnvioManual(${e.id})" title="Ver Detalles AEAT" style="background:transparent; border:none; color:#6366f1; cursor:pointer; margin-right:8px;"><i class="fas fa-eye"></i></button>
                    ${(e.estado !== 'enviado' && e.estado !== 'enviando') ? `
                        <button onclick="reenviarEnvioManual(${e.id})" title="Forzar Reintento" style="background:transparent; border:none; color:#3b82f6; cursor:pointer; margin-right:8px;"><i class="fas fa-redo"></i></button>
                        ${(e.estado === 'error_permanente' || e.estado === 'error_temporal') ? `
                        <button class="btn-admin-accion btn-editar" onclick="abrirEditorDocumentoAeat(${e.id_documento}, '${e.tabla_origen}', '${e.display_num || e.num_documento}')" title="Editar datos del documento" style="background:transparent; border:none; color:#6b7280; cursor:pointer; margin-right:8px;">
                            <i class="fas fa-pencil-alt"></i>
                        </button>
                        ` : ''}
                    ` : (e.estado === 'enviado' ? `
                        <i class="fas fa-check" style="color:#10b981; margin-right: 8px;" title="Documento enviado"></i>
                    ` : '')}
                    ${(e.estado === 'error_permanente' || esErrorAEAT) && (e.estado !== 'enviado' && e.estado !== 'pendiente' && e.estado !== 'enviando') ? `
                        <button onclick="subsanarDocumentoManual(${e.id_documento}, '${e.tabla_origen}')" title="Subsanar" style="background:transparent; border:none; color:#10b981; cursor:pointer; margin-right:8px;"><i class="fas fa-tools"></i></button>
                        <button onclick="descartarEnvioManual(${e.id})" title="Descartar (Ignorar)" style="background:transparent; border:none; color:#ef4444; cursor:pointer;"><i class="fas fa-trash"></i></button>
                    ` : ''}
                </td>
            </tr>
        `;
    });

    html += `</tbody></table>`;

    // Añadir controles de paginación
    html += `
        <div style="display:flex; justify-content:space-between; align-items:center; margin-top:20px; padding-top:15px; border-top:1px solid var(--border-main);">
            <div style="color:var(--text-muted); font-size:0.9rem;">
                Página <b>${verifactuPaginaActual}</b> de <b>${totalPages}</b>
            </div>
            <div style="display:flex; gap:8px;">
                <button onclick="cargarTabEnvios('cola', ${verifactuPaginaActual - 1})" 
                        ${verifactuPaginaActual <= 1 ? 'disabled' : ''} 
                        style="padding:6px 12px; border-radius:6px; border:1px solid var(--border-main); background:var(--bg-panel); cursor:${verifactuPaginaActual <= 1 ? 'not-allowed' : 'pointer'}; opacity:${verifactuPaginaActual <= 1 ? '0.5' : '1'};">
                    <i class="fas fa-chevron-left"></i> Anterior
                </button>
                <button onclick="cargarTabEnvios('cola', ${verifactuPaginaActual + 1})" 
                        ${verifactuPaginaActual >= totalPages ? 'disabled' : ''} 
                        style="padding:6px 12px; border-radius:6px; border:1px solid var(--border-main); background:var(--bg-panel); cursor:${verifactuPaginaActual >= totalPages ? 'not-allowed' : 'pointer'}; opacity:${verifactuPaginaActual >= totalPages ? '0.5' : '1'};">
                    Siguiente <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>
    `;

    contenedor.innerHTML = html;
}

function renderLibroEventos(eventos) {
    const contenedor = document.getElementById('verifactuContenidoTab');
    let html = `
        <div style="background:var(--bg-panel); border-radius:6px; max-height: 250px; overflow-y:auto;">
            <table class="tema-tabla" style="width:100%; border-collapse: collapse; font-size: 0.9rem;">
                <thead style="position: sticky; top: 0; z-index: 10; background: #374151; color: white;">
                    <tr>
                        <th style="padding: 10px; text-align: left;">Fecha</th>
                        <th style="padding: 10px; text-align: left;">Tipo</th>
                        <th style="padding: 10px; text-align: left;">Doc ID</th>
                        <th style="padding: 10px; text-align: left;">Descripción</th>
                    </tr>
                </thead>
                <tbody>
    `;

    if (!eventos || eventos.length === 0) {
        html += `
            <tr style="border-bottom: 1px solid var(--border-main);">
                <td colspan="4" style="padding: 40px; text-align: center; color: var(--text-muted);">No hay eventos registrados.</td>
            </tr>
        `;
    } else {
        eventos.forEach(e => {
            let icon = 'fa-info-circle';
            let color = 'var(--text-main)';

            if (e.tipo.includes('error') || e.tipo.includes('fallida')) { icon = 'fa-times-circle'; color = '#ef4444'; }
            if (e.tipo.includes('ok') || e.tipo.includes('recuperada')) { icon = 'fa-check-circle'; color = '#10b981'; }
            if (e.tipo.includes('perdida')) { icon = 'fa-wifi'; color = '#f59e0b'; }
            if (e.tipo === 'subsanacion') { icon = 'fa-tools'; color = '#3b82f6'; }

            html += `
                <tr style="border-bottom: 1px solid var(--border-main);">
                    <td style="padding: 10px; white-space: nowrap; color: var(--text-muted);">${e.fecha}</td>
                    <td style="padding: 10px;">
                        <span style="color: ${color}; font-weight: 500;">
                            <i class="fas ${icon}" style="margin-right:5px;"></i> ${e.tipo.replace('_', ' ').toUpperCase()}
                        </span>
                    </td>
                    <td style="padding: 10px; font-weight:bold; color:var(--accent-main);">${e.display_num || (e.id_documento ? '#' + e.id_documento : '-')}</td>
                    <td style="padding: 10px;">${e.descripcion}</td>
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
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
            <div style="background: var(--bg-panel); border: 1px solid var(--border-main); padding: 20px; border-radius: 8px; text-align: center;">
                <h3 style="color: var(--text-muted); font-size: 0.9rem; text-transform: uppercase; margin-bottom: 10px;">Pendientes</h3>
                <div style="font-size: 2.5rem; font-weight: bold; color: #f59e0b;">${stats.pendientes || 0}</div>
            </div>
            
            <div style="background: var(--bg-panel); border: 1px solid var(--border-main); padding: 20px; border-radius: 8px; text-align: center;">
                <h3 style="color: var(--text-muted); font-size: 0.9rem; text-transform: uppercase; margin-bottom: 10px;">Enviados Hoy</h3>
                <div style="font-size: 2.5rem; font-weight: bold; color: #10b981;">${stats.enviados_hoy || 0}</div>
            </div>

            <div style="background: var(--bg-panel); border: 1px solid var(--border-main); padding: 20px; border-radius: 8px; text-align: center;">
                <h3 style="color: var(--text-muted); font-size: 0.9rem; text-transform: uppercase; margin-bottom: 10px;">Errores de Red</h3>
                <div style="font-size: 2.5rem; font-weight: bold; color: #ef4444;">${stats.sin_conexion || 0}</div>
            </div>

            <div style="background: var(--bg-panel); border: 1px solid var(--border-main); padding: 20px; border-radius: 8px; text-align: center;">
                <h3 style="color: var(--text-muted); font-size: 0.9rem; text-transform: uppercase; margin-bottom: 10px;">Err. Permanentes</h3>
                <div style="font-size: 2.5rem; font-weight: bold; color: #991b1b;">${stats.errores_permanentes || 0}</div>
            </div>
        </div>
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

                Swal.fire({ title: title, html: htmlMsg, icon: icon });
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
        cancelButtonText: 'Cancelar'
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
    let colorEstado = '#6b7280'; // gris por defecto
    let bgEstado = '#f3f4f6';
    if (e.estado === 'enviado') { colorEstado = '#059669'; bgEstado = '#d1fae5'; }
    else if (e.estado === 'pendiente' || e.estado === 'subsanado') { colorEstado = '#d97706'; bgEstado = '#fef3c7'; }
    else if (e.estado.includes('error')) { colorEstado = '#dc2626'; bgEstado = '#fee2e2'; }

    // Extraer EstadoRegistro de la respuesta XML
    let aeatEstadoStr = '';
    if (e.respuesta_xml) {
        let m = e.respuesta_xml.match(/EstadoRegistro[^>]*>([^<]+)<\//);
        if (m && m[1]) {
            aeatEstadoStr = m[1];
        }
    }

    let info = `
        <div style="text-align: left; font-family: 'Inter', system-ui, sans-serif; max-height: 75vh; overflow-y: auto; overflow-x: hidden; padding: 10px; padding-right: 15px;">
            
            <!-- TARJETA DE RESUMEN (Glassmorphism) -->
            <div style="background: linear-gradient(135deg, rgba(255,255,255,0.9), rgba(249,250,251,0.7)); border: 1px solid rgba(229,231,235,0.8); border-radius: 12px; padding: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); margin-bottom: 25px; backdrop-filter: blur(10px);">
                
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px; border-bottom: 1px dashed #e5e7eb; padding-bottom: 15px;">
                    <div>
                        <span style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: #6b7280; font-weight: 600;">ID de Documento</span>
                        <div style="font-size: 1.5rem; font-weight: 800; color: #111827; margin-top: 4px; display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-file-invoice" style="color: #3b82f6;"></i>
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
    `;

    Swal.fire({
        title: '<i class="fas fa-info-circle"></i> Información Técnica AEAT',
        html: info,
        width: '1200px', // Ampliado para soportar las dos columnas de forma cómoda
        confirmButtonText: 'Entendido',
        confirmButtonColor: '#3b82f6'
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
