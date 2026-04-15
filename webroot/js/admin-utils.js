/**
 * admin.utils.js
 * Utilidades compartidas: modales, paginación, debounce, idle callback, etc.
 */

// ── Modales ───────────────────────────────────────────────────────────────────

function cerrarModal(id) {
    const el = document.getElementById(id);
    if (el) el.style.display = 'none';
}

function abrirModal(id) {
    const el = document.getElementById(id);
    if (el) el.style.display = 'flex';
}

// ── requestIdleCallback ──────────────────────────────────────────────────────

/**
 * Ejecuta una tarea pesada cuando el navegador está idle.
 * @param {Function} task       Función a ejecutar
 * @param {Function} onComplete Callback opcional con el resultado
 */
function ejecutarCuandoIdle(task, onComplete) {
    if ('requestIdleCallback' in window) {
        requestIdleCallback(() => {
            const result = task();
            if (onComplete) onComplete(result);
        }, { timeout: 2000 });
    } else {
        setTimeout(() => {
            const result = task();
            if (onComplete) onComplete(result);
        }, 0);
    }
}

// ── Inputs de paginación ──────────────────────────────────────────────────────

/**
 * Ajusta el ancho de un input de paginación al contenido.
 * @param {HTMLInputElement} input
 */
function ajustarAnchoInput(input) {
    if (!input) return;
    const tempSpan = document.createElement('span');
    tempSpan.style.cssText = 'visibility:hidden;position:absolute;';
    tempSpan.style.font = window.getComputedStyle(input).font;
    tempSpan.style.padding = '0 5px';
    tempSpan.textContent = input.value || '0';
    document.body.appendChild(tempSpan);
    input.style.width = (tempSpan.offsetWidth + 15) + 'px';
    document.body.removeChild(tempSpan);
}

/** Ajusta todos los inputs de paginación tras un cambio de página. */
function ajustarTodosInputsPaginacion() {
    setTimeout(() => {
        document.querySelectorAll('.input-numero-pagina')
            .forEach(input => ajustarAnchoInput(input));
    }, 50);
}

// ── Generador genérico de HTML de paginación ──────────────────────────────────

/**
 * Genera el HTML de controles de paginación reutilizables.
 * @param {number} paginaActual
 * @param {number} totalPaginas
 * @param {string} fnCambiar   Nombre de la función JS a llamar con (pagina)
 * @param {string} fnIr        Nombre de la función JS a llamar sin args
 * @param {string} inputId     ID del <input> de página
 * @returns {string}
 */
function generarPaginacionHTML(paginaActual, totalPaginas, fnCambiar, fnIr, inputId) {
    if (totalPaginas <= 1) return '';

    let botones = '';

    if (paginaActual > 1) {
        botones += `<button class="btn-paginacion" onclick="${fnCambiar}(1)" title="Primera página">
            <i class="fas fa-angle-double-left"></i></button>`;
        botones += `<button class="btn-paginacion" onclick="${fnCambiar}(${paginaActual - 1})" title="Página anterior">
            <i class="fas fa-chevron-left"></i></button>`;
    }

    botones += `<div class="input-paginacion">
        <input type="number" id="${inputId}" class="input-numero-pagina"
            value="${paginaActual}" min="1" max="${totalPaginas}"
            onfocus="ajustarAnchoInput(this)" oninput="ajustarAnchoInput(this)"
            onblur="ajustarAnchoInput(this)" onchange="${fnIr}()"
            onkeypress="if(event.key==='Enter') ${fnIr}()">
        <span class="info-paginacion"> de ${totalPaginas}</span>
    </div>`;

    if (paginaActual < totalPaginas) {
        botones += `<button class="btn-paginacion" onclick="${fnCambiar}(${paginaActual + 1})" title="Siguiente página">
            <i class="fas fa-chevron-right"></i></button>`;
        botones += `<button class="btn-paginacion" onclick="${fnCambiar}(${totalPaginas})" title="Última página">
            <i class="fas fa-angle-double-right"></i></button>`;
    }

    return `<div class="admin-paginacion-wrapper">
                <div class="admin-paginacion">${botones}</div>
            </div>`;
}

/**
 * Reemplaza la paginación existente en el DOM y añade la nueva.
 * @param {HTMLElement} contenedor  Contenedor padre
 * @param {string}      htmlPag     HTML generado por generarPaginacionHTML()
 */
function actualizarPaginacionDOM(contenedor, htmlPag) {
    const existing = contenedor.querySelector('.admin-paginacion-wrapper');
    if (existing) existing.remove();
    const wrapper = contenedor.querySelector('.admin-tabla-wrapper');
    if (wrapper) wrapper.insertAdjacentHTML('afterend', htmlPag);
    ajustarTodosInputsPaginacion();
}

// ── Imagen a pantalla completa ─────────────────────────────────────────────────

function abrirImagenGrande(src, alt = '') {
    const overlay = document.createElement('div');
    overlay.style.cssText = `
        position:fixed;inset:0;z-index:9999;
        background:rgba(0,0,0,.85);
        display:flex;align-items:center;justify-content:center;
        cursor:zoom-out;animation:fadeIn .15s ease;`;
    overlay.innerHTML = `<img src="${src}" alt="${alt}"
        style="max-width:90vw;max-height:90vh;object-fit:contain;
               border-radius:8px;box-shadow:0 25px 60px rgba(0,0,0,.5);">`;
    overlay.addEventListener('click', () => overlay.remove());
    const cerrarConEsc = e => {
        if (e.key === 'Escape') { overlay.remove(); document.removeEventListener('keydown', cerrarConEsc); }
    };
    document.addEventListener('keydown', cerrarConEsc);
    document.body.appendChild(overlay);
}

// ── Validación de Decimales ──────────────────────────────────────────────────

/**
 * Limita el input a un máximo de 4 decimales en tiempo real.
 * @param {HTMLInputElement} input
 */
function validarPrecisionDinamica(input, limitInputId) {
    let limitInput = document.getElementById(limitInputId);
    let limit = limitInput ? parseInt(limitInput.value) : 4;
    if (isNaN(limit)) limit = 4;
    if (limit > 4) limit = 4;
    if (limit < 0) limit = 0;

    let value = input.value;
    if (!value) return;

    // Use regex to keep only up to N decimals
    let regex = new RegExp('^-?\\d*(\\.\\d{0,' + limit + '})?');
    let match = value.match(regex);
    if (match && match[0] !== value) {
        input.value = match[0];
    }
}

/**
 * Valida que el número de decimales esté entre 0 y 4.
 * Además, actualiza la precisión del input de precio relacionado.
 * @param {HTMLInputElement} input
 * @param {string} priceInputId
 */
function validarDecimalesRango(input, priceInputId) {
    if (input.value > 4) input.value = 4;
    if (input.value < 0 && input.value !== "") input.value = 0;
    
    let priceInput = document.getElementById(priceInputId);
    if (priceInput) {
        validarPrecisionDinamica(priceInput, input.id);
    }
}