/**
 * admin-backups.js
 * Funciones para la gestión de copias de seguridad (Backups)
 * Depende de: admin-state.js, admin-utils.js
 */

/**
 * Muestra el panel de gestión de Copias de Seguridad (Backups)
 */
function mostrarPanelBackups() {
    const contenedor = document.getElementById('adminContenido');
    seccionActual = 'backups';
    adminTablaHeaderHTML = '';

    const configHeader = { t: 'Seguridad y Respaldo', s: 'Gestión integral de copias de seguridad y recuperación de datos', i: 'fa-shield-alt', g: 'linear-gradient(135deg, #059669, #10b981)' };

    contenedor.innerHTML = `
        <div class="backups-panel-premium animate-fade-in">
            ${getPremiumHeaderHTML(configHeader.i, configHeader.t, configHeader.s, configHeader.g)}

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px; margin: 25px 0;">
                <div class="premium-card" style="display:flex; align-items:center; gap:20px; padding:25px;">
                    <div style="background: rgba(16, 185, 129, 0.1); color: #10b981; width: 60px; height: 60px; border-radius: 18px; display: flex; align-items: center; justify-content: center; font-size: 1.6rem;">
                        <i class="fas fa-check-double"></i>
                    </div>
                    <div>
                        <h4 style="margin: 0; font-size: 0.8rem; text-transform: uppercase; color: var(--text-muted); letter-spacing: 1px; font-weight:700;">Última Integridad</h4>
                        <div id="ultimaCopiaFecha" style="font-size: 1.15rem; font-weight: 800; color: var(--text-main); margin-top:2px;">Cargando...</div>
                    </div>
                </div>

                <div class="premium-card" style="display:flex; align-items:center; gap:20px; padding:25px;">
                    <div style="background: rgba(59, 130, 246, 0.1); color: #3b82f6; width: 60px; height: 60px; border-radius: 18px; display: flex; align-items: center; justify-content: center; font-size: 1.6rem;">
                        <i class="fas fa-database"></i>
                    </div>
                    <div>
                        <h4 style="margin: 0; font-size: 0.8rem; text-transform: uppercase; color: var(--text-muted); letter-spacing: 1px; font-weight:700;">Almacenamiento</h4>
                        <div id="totalCopias" style="font-size: 1.15rem; font-weight: 800; color: var(--text-main); margin-top:2px;">Cargando...</div>
                    </div>
                </div>
            </div>

            <div class="premium-card" style="padding:0; overflow:hidden;">
                <div style="padding: 25px 30px; border-bottom: 1px solid var(--border-main); display: flex; justify-content: space-between; align-items: center; background: rgba(0,0,0,0.01);">
                    <h3 style="margin: 0; font-size: 1rem; font-weight: 800; color: var(--text-main); text-transform:uppercase; letter-spacing:0.5px;">
                        <i class="fas fa-history" style="margin-right: 12px; color: var(--accent-main);"></i> Registro de Respaldos
                    </h3>
                    <div style="display: flex; gap: 12px; align-items:center;">
                        <button onclick="crearBackupManual()" class="btn-premium-primary" style="padding: 10px 20px; font-size:0.85rem;">
                            <i class="fas fa-plus-circle"></i> Nuevo Respaldo
                        </button>
                        <div style="width:1px; height:25px; background:var(--border-main); margin:0 5px;"></div>
                        <div style="display: flex; background: var(--bg-panel); border-radius: 12px; padding: 4px; border: 1px solid var(--border-main);">
                            <button onclick="crearBackupTabla('clientes')" class="btn-table-icon success" style="padding: 8px 12px; border-radius: 8px;" title="Copia de Clientes">
                                <i class="fas fa-users"></i>
                            </button>
                            <button onclick="crearBackupTabla('usuarios')" class="btn-table-icon accent" style="padding: 8px 12px; border-radius: 8px;" title="Copia de Usuarios">
                                <i class="fas fa-user-shield"></i>
                            </button>
                        </div>
                    </div>
                </div>
                
                <div id="tablaBackupsContainer" style="min-height: 250px;">
                    <div style="padding: 80px; text-align: center;"><i class="fas fa-circle-notch fa-spin fa-3x" style="color: var(--accent-main); opacity:0.5;"></i></div>
                </div>
                
                <div id="backupPagination" style="padding: 20px; border-top: 1px solid var(--border-main); background: rgba(0,0,0,0.005);">
                </div>
            </div>
        </div>
    `;

    cargarListadoBackups();
}

/**
 * Carga el historial de copias de seguridad desde la API
 */
function cargarListadoBackups() {
    const container = document.getElementById('tablaBackupsContainer');
    const paginationContainer = document.getElementById('backupPagination');
    const textColor = 'var(--text-main)';
    const borderColor = 'var(--border-main)';

    console.log('Calling backup API...');
    fetch('api/backups.php', {
        credentials: 'same-origin'
    })
        .then(res => {
            console.log('Backup API response status:', res.status);
            console.log('Backup API response statusText:', res.statusText);
            console.log('Backup API response content-type:', res.headers.get('content-type'));
            if (!res.ok) {
                return res.text().then(text => {
                    console.log('Error response text:', text);
                    throw new Error('HTTP ' + res.status + ': ' + text);
                });
            }
            return res.json();
        })
        .then(data => {
            if (data.ok) {
                backupData = data.backups;
                const totalCopias = backupData.length;
                const totalCopiasDiv = document.getElementById('totalCopias');
                const ultimaCopiaDiv = document.getElementById('ultimaCopiaFecha');

                if (totalCopiasDiv) totalCopiasDiv.textContent = totalCopias + ' archivos';
                if (ultimaCopiaDiv) ultimaCopiaDiv.textContent = totalCopias > 0 ? backupData[0].fecha : 'Ninguna';

                if (totalCopias === 0) {
                    container.innerHTML = '<div style="padding: 50px; text-align: center; color: #6b7280;">No hay copias de seguridad generadas todavía.</div>';
                    return;
                }

                renderBackupPage(1);
                renderBackupPagination();
            } else {
                container.innerHTML = `<div style="padding: 40px; text-align: center; color: #ef4444;">Error al cargar: ${data.error}</div>`;
            }
        })
        .catch(err => {
            console.error('Error loading backups:', err);
            if (container) container.innerHTML = `<div style="padding: 40px; text-align: center; color: #ef4444;">Error de conexión con la API de backups: ${err.message}</div>`;
        });
}

function renderBackupPage(page) {
    currentPage = page;
    const container = document.getElementById('tablaBackupsContainer');
    const textColor = 'var(--text-main)';
    const borderColor = 'var(--border-main)';
    const headerBg = 'var(--bg-secondary)';

    const start = (page - 1) * itemsPerPage;
    const end = start + itemsPerPage;
    const pageItems = backupData.slice(start, end);
    const totalPages = Math.ceil(backupData.length / itemsPerPage);

    let html = `
        <table class="modern-table" style="width: 100%;">
            <thead>
                <tr>
                    <th style="padding: 15px 25px;">Tipo</th>
                    <th>Archivo</th>
                    <th>Fecha</th>
                    <th>Tamaño</th>
                    <th style="text-align: right; padding-right: 25px;">Acciones</th>
                </tr>
            </thead>
            <tbody>`;

    pageItems.forEach(b => {
        const sizeMB = (b.tamano / (1024 * 1024)).toFixed(2);
        let icon, color, tipoLabel, badgeClass;
        if (b.tipo === 'tabla') {
            icon = b.tabla === 'clientes' ? 'fa-users' : 'fa-user-shield';
            color = b.tabla === 'clientes' ? '#10b981' : '#3b82f6';
            tipoLabel = b.tabla === 'clientes' ? 'Clientes' : 'Usuarios';
            badgeClass = b.tabla === 'clientes' ? 'badge-success' : 'badge-accent';
        } else {
            icon = 'fa-file-archive';
            color = '#8b5cf6';
            tipoLabel = 'Sistema';
            badgeClass = 'badge-primary';
        }

        html += `
            <tr>
                <td style="padding: 12px 25px;">
                    <span class="status-badge ${badgeClass}" style="display:inline-flex; align-items:center; gap:8px; padding:6px 12px;">
                        <i class="fas ${icon}"></i> ${tipoLabel}
                    </span>
                </td>
                <td style="font-weight: 600;">${b.nombre}</td>
                <td style="color: var(--text-muted);">${b.fecha}</td>
                <td><span style="background: var(--bg-panel); padding: 4px 10px; border-radius: 8px; font-size: 0.8rem; font-weight: 700;">${sizeMB} MB</span></td>
                <td style="padding-right: 25px;">
                    <div style="display: flex; justify-content: flex-end; gap: 8px;">
                        <button onclick="descargarBackup('${b.nombre}')" class="btn-table-icon accent" title="Descargar">
                            <i class="fas fa-download"></i>
                        </button>
                        <button onclick="confirmarRestauracion('${b.nombre}')" class="btn-table-icon success" title="Restaurar">
                            <i class="fas fa-undo"></i>
                        </button>
                        <button onclick="eliminarBackup('${b.nombre}')" class="btn-table-icon danger" title="Eliminar">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>`;
    });

    html += '</tbody></table>';
    container.innerHTML = html;

    renderBackupPagination();
}

function renderBackupPagination() {
    const paginationContainer = document.getElementById('backupPagination');
    const totalPages = Math.ceil(backupData.length / itemsPerPage);

    if (totalPages <= 1) {
        paginationContainer.innerHTML = '';
        return;
    }

    let html = '';

    // Botón primera página
    if (currentPage > 1) {
        html += `<button class="btn-paginacion" onclick="renderBackupPage(1)" title="Primera página">
            <i class="fas fa-angle-double-left"></i>
        </button>`;
    }

    // Botón anterior
    if (currentPage > 1) {
        html += `<button class="btn-paginacion" onclick="renderBackupPage(${currentPage - 1})" title="Página anterior">
            <i class="fas fa-chevron-left"></i>
        </button>`;
    }

    // Input para número de página
    html += `<div class="input-paginacion">
        <input type="number" id="inputPaginaBackups" class="input-numero-pagina"
            value="${currentPage}" min="1" max="${totalPages}"
            onfocus="ajustarAnchoInput(this)" oninput="ajustarAnchoInput(this)" onblur="ajustarAnchoInput(this)" onchange="irAPaginaBackups()" onkeypress="if(event.key === 'Enter') irAPaginaBackups()">
        <span class="info-paginacion"> de ${totalPages}</span>
    </div>`;

    // Botón siguiente
    if (currentPage < totalPages) {
        html += `<button class="btn-paginacion" onclick="renderBackupPage(${currentPage + 1})" title="Siguiente página">
            <i class="fas fa-chevron-right"></i>
        </button>`;
    }

    // Botón última página
    if (currentPage < totalPages) {
        html += `<button class="btn-paginacion" onclick="renderBackupPage(${totalPages})" title="Última página">
            <i class="fas fa-angle-double-right"></i>
        </button>`;
    }

    paginationContainer.innerHTML = `
        <div class="admin-paginacion-wrapper" style="display: flex !important; visibility: visible !important;">
            <div class="admin-paginacion">
                ${html}
            </div>
        </div>`;

    // Ajustar ancho del input después de renderizar
    setTimeout(() => {
        const input = document.getElementById('inputPaginaBackups');
        if (input) {
            ajustarAnchoInput(input);
        }
    }, 100);
}

function irAPaginaBackups() {
    const input = document.getElementById('inputPaginaBackups');
    if (!input) return;

    const totalPages = Math.ceil(backupData.length / itemsPerPage);
    let nuevaPagina = parseInt(input.value);

    if (isNaN(nuevaPagina) || nuevaPagina < 1) {
        nuevaPagina = 1;
    } else if (nuevaPagina > totalPages) {
        nuevaPagina = totalPages;
    }

    renderBackupPage(nuevaPagina);
}

/**
 * Llama a la API para crear un nuevo backup manual
 */
function crearBackupManual() {
    // Crear modal con barra de progreso
    const progressHtml = `
        <div id="backupProgressContainer" style="text-align: center; padding: 20px;">
            <div style="font-size: 14px; color: var(--text-muted); margin-bottom: 15px;">Generando copia de seguridad completa...</div>
            <div style="background: var(--bg-input); border-radius: 8px; height: 20px; overflow: hidden; margin-bottom: 10px;">
                <div id="backupProgressBar" style="background: linear-gradient(90deg, #6366f1, #8b5cf6); height: 100%; width: 0%; transition: width 0.3s ease;"></div>
            </div>
            <div id="backupProgressText" style="font-size: 13px; color: var(--text-muted);">Iniciando...</div>
        </div>
    `;

    Swal.fire({
        title: 'Backup en progreso',
        html: progressHtml,
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => {
            // Iniciar animación de progreso
            let progress = 0;
            const progressBar = document.getElementById('backupProgressBar');
            const progressText = document.getElementById('backupProgressText');

            // Simular progreso (el servidor no proporciona progreso real para backup completo)
            const progressInterval = setInterval(() => {
                if (progress < 90) {
                    progress += Math.random() * 5;
                    if (progress > 90) progress = 90;
                    progressBar.style.width = progress + '%';
                    progressText.textContent = Math.round(progress) + '% completado';
                }
            }, 1000);

            // Hacer la petición de backup
            fetch('api/backups.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ accion: 'crear' })
            })
                .then(res => {
                    clearInterval(progressInterval);
                    if (!res.ok) {
                        return res.text().then(text => {
                            throw new Error('Server error: ' + res.status);
                        });
                    }
                    return res.json();
                })
                .then(data => {
                    clearInterval(progressInterval);
                    progressBar.style.width = '100%';
                    progressText.textContent = '100% - Completado';

                    if (data.ok) {
                        setTimeout(() => {
                            Swal.fire('¡Éxito!', 'Copia de seguridad creada correctamente.', 'success');
                            cargarListadoBackups();
                        }, 500);
                    } else {
                        Swal.fire('Error', 'No se pudo crear el backup: ' + (data.error || 'Error desconocido'), 'error');
                    }
                })
                .catch(err => {
                    clearInterval(progressInterval);
                    console.error('Fetch error:', err);
                    Swal.fire('Error', 'Fallo en la comunicación con el servidor.', 'error');
                });
        }
    });
}

/**
 * Crea un backup de una tabla específica (para tablas grandes)
 */
function crearBackupTabla(tabla) {
    let progressInterval = null;

    const tablaNombres = {
        'clientes': 'Clientes',
        'usuarios': 'Usuarios'
    };

    const nombreMostrar = tablaNombres[tabla] || tabla;

    // Crear modal con barra de progreso y botón de cancelar
    let backupAborted = false;

    const progressHtml = `
        <div id="backupProgressContainer" style="text-align: center; padding: 20px;">
            <div style="font-size: 14px; color: var(--text-muted); margin-bottom: 15px;">Generando backup de ${nombreMostrar}...</div>
            <div style="background: var(--bg-input); border-radius: 8px; height: 20px; overflow: hidden; margin-bottom: 10px;">
                <div id="backupProgressBar" style="background: linear-gradient(90deg, #059669, #10b981); height: 100%; width: 0%; transition: width 0.3s ease;"></div>
            </div>
            <div id="backupProgressText" style="font-size: 13px; color: var(--text-muted);">Iniciando...</div>
        </div>
    `;

    Swal.fire({
        title: 'Backup de tabla en progreso',
        html: progressHtml,
        allowOutsideClick: false,
        showConfirmButton: false,
        showCancelButton: true,
        cancelButtonText: 'Cancelar',
        cancelButtonColor: '#ef4444',
        willClose: () => {
            // Keep track that we're closing
        },
        didClose: () => {
            // Verificar la razón del cierre - si fue por click en cancelar
            if (backupAborted) {
                console.log('Modal was closed - sending cancel to server');
                // Notificar al servidor
                fetch('api/backups.php?cancelBackup=1&tabla=' + tabla, {
                    credentials: 'same-origin'
                }).then(() => {
                    console.log('Cancel notification sent to server');
                });
            }
        },
        didOpen: () => {
            const progressBar = document.getElementById('backupProgressBar');
            const progressText = document.getElementById('backupProgressText');

            console.log('Starting backup for tabla:', tabla);

            // Add click listener to cancel button
            const cancelBtn = document.querySelector('.swal2-cancel');
            if (cancelBtn) {
                cancelBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('Cancel button clicked - sending cancel request');
                    backupAborted = true;

                    // Mostrar que se está cancelando
                    if (progressText) {
                        progressText.textContent = 'Cancelando...';
                    }

                    if (progressInterval) {
                        clearInterval(progressInterval);
                    }

                    // Notify server immediately
                    fetch('api/backups.php?cancelBackup=1&tabla=' + tabla, {
                        credentials: 'same-origin'
                    }).then(res => res.json()).then(data => {
                        console.log('Cancel response:', data);
                    }).catch(err => {
                        console.error('Cancel error:', err);
                    });
                });
            }

            // Primero obtener el número total de filas
            fetch('api/backups.php?getRowCount=1&tabla=' + tabla, {
                credentials: 'same-origin'
            })
                .then(res => {
                    console.log('Row count response:', res.status);
                    if (!res.ok) {
                        return res.text().then(text => {
                            console.error('Row count error:', text);
                            throw new Error('Row count error: ' + text);
                        });
                    }
                    return res.json();
                })
                .then(countData => {
                    console.log('Row count data:', countData);
                    // Verificar respuesta válida
                    const totalRows = (countData && countData.ok) ? (countData.total || 0) : 0;
                    let processedRows = 0;

                    // Actualizar texto inicial
                    progressText.textContent = '0 / ' + totalRows.toLocaleString() + ' filas';
                    console.log('Total rows:', totalRows);

                    progressInterval = setInterval(() => {
                        if (backupAborted) {
                            clearInterval(progressInterval);
                            return;
                        }

                        fetch(`api/backups.php?getBackupProgress=1&tabla=${tabla}`, {
                            credentials: 'same-origin'
                        })
                            .then(res => res.json())
                            .then(prog => {
                                if (!prog.ok || prog.total === 0) return;

                                const pct = Math.min(99, prog.porcentaje); // dejamos el 100% para cuando termine
                                progressBar.style.width = pct + '%';
                                progressText.textContent =
                                    `${prog.progreso.toLocaleString('es-ES')} / ${prog.total.toLocaleString('es-ES')} filas (${pct}%)`;
                            })
                            .catch(() => { }); // silenciar errores de red durante el proceso
                    }, 1500);

                    // Hacer la petición de backup
                    return fetch('api/backups.php', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ accion: 'crear_tabla', tabla: tabla })
                    })
                        .then(res => {
                            clearInterval(progressInterval);
                            if (backupAborted) {
                                return { ok: false, error: 'Backup cancelado por el usuario' };
                            }
                            if (!res.ok) {
                                throw new Error('Server error: ' + res.status);
                            }
                            return res.json();
                        })
                        .then(data => {
                            if (backupAborted) {
                                return;
                            }

                            // Verificar si el servidor indicó que fue cancelado
                            if (data && data.error && data.error.includes('cancelado')) {
                                progressText.textContent = 'Cancelado';
                                return;
                            }

                            progressBar.style.width = '100%';
                            progressText.textContent = 'Completado (100%)';

                            if (data.ok) {
                                setTimeout(() => {
                                    Swal.fire('¡Éxito!', 'Copia de seguridad de ' + nombreMostrar + ' creada correctamente.', 'success');
                                    cargarListadoBackups();
                                }, 500);
                            } else {
                                Swal.fire('Error', 'No se pudo crear el backup: ' + (data.error || 'Error desconocido'), 'error');
                            }
                        })
                        .catch(err => {
                            clearInterval(progressInterval);
                            progressBar.style.width = '100%';
                            progressText.textContent = 'Completado (100%)';
                            // No relanzar el error si fue cancelado por el usuario
                            if (backupAborted) {
                                return;
                            }
                            throw err;
                        });
                })
                .catch(err => {
                    console.error('Error:', err);
                    Swal.fire('Error', 'Fallo al obtener el progreso del backup.', 'error');
                });
        }
    });
}

/**
 * Descarga un archivo de backup
 */
function descargarBackup(archivo) {
    window.location.href = `api/backups.php?accion=descargar&archivo=${archivo}`;
}

/**
 * Muestra confirmación y ejecuta la restauración
 */
function confirmarRestauracion(archivo) {
    Swal.fire({
        title: '¿Restaurar sistema?',
        html: `Estás a punto de restaurar el TPV al estado del día <b>${archivo}</b>.<br><br><span style="color: #ef4444; font-weight: bold;">ADVERTENCIA:</span> Se sobrescribirán todos los datos actuales de la base de datos y archivos de configuración.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sí, restaurar ahora',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Restaurando...',
                text: 'Por favor, no cierre el navegador.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch('api/backups.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ accion: 'restaurar', archivo: archivo })
            })
                .then(res => res.json())
                .then(data => {
                    if (data.ok) {
                        Swal.fire({
                            title: '¡Sistema Restaurado!',
                            text: 'El TPV se ha restaurado correctamente. La página se recargará ahora.',
                            icon: 'success',
                            confirmButtonText: 'Aceptar'
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire('Error de restauración', data.error || 'Error desconocido', 'error');
                    }
                })
                .catch(err => {
                    Swal.fire('Error fatal', 'Fallo crítico en la comunicación durante la restauración.', 'error');
                });
        }
    });
}

/**
 * Elimina un archivo de backup
 */
function eliminarBackup(archivo) {
    Swal.fire({
        title: '¿Eliminar backup?',
        text: `¿Estás seguro de que deseas eliminar permanentemente el archivo ${archivo}?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`api/backups.php?archivo=${archivo}`, {
                method: 'DELETE',
                credentials: 'same-origin'
            })
                .then(res => res.json())
                .then(data => {
                    if (data.ok) {
                        Swal.fire('Eliminado', 'El archivo ha sido borrado.', 'success');
                        cargarListadoBackups();
                    } else {
                        Swal.fire('Error', data.error, 'error');
                    }
                });
        }
    });
}
