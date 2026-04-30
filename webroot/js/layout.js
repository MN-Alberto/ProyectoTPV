function _t(key, params) {
    const keys = key.split('.');
    let val = window.__LANG__;
    for (const k of keys) {
        if (val === undefined || val === null || val[k] === undefined) {
            return key;
        }
        val = val[k];
    }
    if (typeof val !== 'string') return key;
    if (params) {
        Object.keys(params).forEach(p => { val = val.replace(p, params[p]); });
    }
    return val;
}

// Theme toggle functionality
function setTheme(theme) {
    if (theme === 'dark') {
        document.body.classList.add('dark-mode');
        localStorage.setItem('theme', 'dark');
        const btnModoOscuro = document.getElementById('btnModoOscuro');
        if (btnModoOscuro) btnModoOscuro.classList.add('active');
        const btnModoClaro = document.getElementById('btnModoClaro');
        if (btnModoClaro) btnModoClaro.classList.remove('active');
    } else {
        document.body.classList.remove('dark-mode');
        localStorage.setItem('theme', 'light');
        const btnModoClaro = document.getElementById('btnModoClaro');
        if (btnModoClaro) btnModoClaro.classList.add('active');
        const btnModoOscuro = document.getElementById('btnModoOscuro');
        if (btnModoOscuro) btnModoOscuro.classList.remove('active');
    }
    // Dispatch custom event for charts to update
    window.dispatchEvent(new Event('themeChange'));
}

// Aplicar tema personalizado guardado (header y footer)
function aplicarTemaPersonalizado() {
    const temaJSON = localStorage.getItem('temaTPV');
    console.log('Leyendo tema de localStorage:', temaJSON);
    if (!temaJSON) {
        console.log('No hay tema guardado en localStorage');
        return;
    }

    try {
        const tema = JSON.parse(temaJSON);
        console.log('Tema parseado:', tema);

        // Aplicar al header
        const header = document.querySelector('header');
        if (header && tema.header_bg) {
            header.style.background = tema.header_bg;
            header.style.color = tema.header_color || '#ffffff';
        }

        // Aplicar al footer
        const footer = document.querySelector('footer');
        if (footer && tema.footer_bg) {
            footer.style.background = tema.footer_bg;
            footer.style.color = tema.footer_color || '#e5e7eb';
        }

        // Aplicar icono personalizado del header
        if (tema.header_icon) {
            const iconContainer = document.getElementById('header-icon-container');
            if (iconContainer) {
                iconContainer.innerHTML = tema.header_icon;
                // Ajustar tamaño del icono
                const svg = iconContainer.querySelector('svg');
                if (svg) {
                    svg.setAttribute('width', '36');
                    svg.setAttribute('height', '36');
                }
            }
        }

        // Aplicar favicon personalizado
        if (tema.favicon) {
            const faviconLink = document.getElementById('favicon-link');
            if (faviconLink) {
                faviconLink.href = tema.favicon;
            }
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
    } catch (e) {
        console.error('Error applying theme:', e);
    }
}

// Load saved theme on page load
document.addEventListener('DOMContentLoaded', function () {
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'dark') {
        document.body.classList.add('dark-mode');
        const btnModoOscuro = document.getElementById('btnModoOscuro');
        if (btnModoOscuro) btnModoOscuro.classList.add('active');
    } else {
        const btnModoClaro = document.getElementById('btnModoClaro');
        if (btnModoClaro) btnModoClaro.classList.add('active');
    }

    // Aplicar tema personalizado guardado
    if (typeof aplicarTemaGuardado === 'function') {
        aplicarTemaGuardado();
    } else if (typeof aplicarTemaPersonalizado === 'function') {
        aplicarTemaPersonalizado();
    }

    // --- PROCESAMIENTO EN SEGUNDO PLANO VERI*FACTU ---
    // Este intervalo se ejecuta en todas las vistas (cajero, admin, etc.)
    // Asegura que los envíos pendientes, retrys y lotes se manden a la AEAT en background.
    setInterval(async () => {
        try {
            const response = await fetch('api/verifactu-cola.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'procesarCola', auto: true })
            });
            const data = await response.json();
            
            // Disparar evento para que si estamos en admin, se actualice la UI
            const event = new CustomEvent('verifactuAutoProcess', { detail: data });
            window.dispatchEvent(event);
        } catch(e) {
            // Silenciar errores de red
        }
    }, 60 * 1000); // 1 minuto
});
