/**
 * admin-verifactu.js
 * Gestión de la configuración fiscal y cumplimiento Verifactu.
 */

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
                <div class="tema-seccion-header">
                    <i class="fas fa-file-invoice-dollar tema-seccion-icono"></i>
                    <h4 class="tema-seccion-titulo">Datos Fiscales del Obligado</h4>
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
                            <option value="https://prewww1.aeat.es/wlpl/TIKE-CONT/ws/SistemaFacturacion/VerifactuSOAP" ${config.aeat_url_verifactu?.includes('prewww1') ? 'selected' : ''}>PRE-PRODUCCIÓN (Pruebas)</option>
                            <option value="https://www1.aeat.es/wlpl/TIKE-CONT/ws/SistemaFacturacion/VerifactuSOAP" ${!config.aeat_url_verifactu?.includes('prewww1') ? 'selected' : ''}>PRODUCCIÓN (Real)</option>
                        </select>
                        <p class="tema-ayuda" style="margin-top: 5px; font-size: 0.8rem; color: #6b7280;">Use el entorno de pruebas para validar la conexión antes de pasar a producción.</p>
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
        cert_pass: document.getElementById('fiscal_cert_pass').value
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

// Hook para inyectar en la navegación principal (admin.js)
// Debemos asegurarnos de que la función cargarseccion('config-fiscal') llame a cargarConfiguracionFiscal()
document.addEventListener('click', function(e) {
    const btn = e.target.closest('[data-seccion="config-fiscal"]');
    if (btn) {
        cargarConfiguracionFiscal();
    }
});
