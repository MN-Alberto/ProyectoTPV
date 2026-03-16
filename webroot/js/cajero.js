/**
 * cajero.js
 * 
 * Script principal para la vista del cajero del sistema TPV.
 * Gestiona la interacción con categorías, la búsqueda de productos
 * y el renderizado dinámico de la cuadrícula de productos mediante AJAX.
 */

// ======================== TARIFAS (AJAX) ========================
let tarifasDisponibles = [];
// Almacena el producto que está esperando a que se identifique un cliente
let productoPendienteTarifa = null;
// Almacena la tarifa anterior para poder revertir si el cliente no se encuentra
let tarifaAnteriorCard = new Map();

/**
 * Redondeo financiero a 2 decimales exactos.
 * @param {number} num 
 * @returns {number}
 */
function round2(num) {
    if (isNaN(num) || num === null) return 0;
    return Math.round((num + Number.EPSILON) * 100) / 100;
}

/**
 * Carga las tarifas desde la API para usarlas en el selector de productos.
 */
function cargarTarifasCajero() {
    return fetch('api/tarifas.php')
        .then(res => res.json())
        .then(data => {
            tarifasDisponibles = data;
            return data;
        })
        .catch(err => console.error('Error cargando tarifas:', err));
}

// ======================== CATEGORÍAS (AJAX) ========================

/**
 * Selecciona una categoría y carga los productos correspondientes mediante AJAX.
 * También tiene en cuenta el texto de búsqueda activo para combinar ambos filtros.
 * 
 * @param {HTMLElement} boton - El botón de categoría que fue pulsado.
 * @param {number|null} idCategoria - El ID de la categoría seleccionada, o null para "todas".
 */
function seleccionarCategoria(boton, idCategoria) {
    // Eliminar la clase 'activa' de todos los botones de categoría
    // y añadirla únicamente al botón que se acaba de pulsar.
    document.querySelectorAll('.cat-btn').forEach(b => b.classList.remove('activa'));
    boton.classList.add('activa');

    // Obtener el texto del campo de búsqueda por si el usuario ya ha escrito algo,
    // de forma que se pueda combinar el filtro de categoría con el de búsqueda.
    const textoBusqueda = document.getElementById('inputBuscarProducto').value.trim();

    // Construir la URL de la petición AJAX con los parámetros necesarios.
    let url = 'api/productos.php?';
    let params = new URLSearchParams();

    // Si se ha seleccionado una categoría concreta, se envía su ID;
    // en caso contrario se envía 'todas' para obtener todos los productos.
    if (idCategoria !== null) {
        params.append('idCategoria', idCategoria);
    } else {
        params.append('idCategoria', 'todas');
    }

    // Si hay texto de búsqueda, se añade como parámetro adicional.
    if (textoBusqueda) {
        params.append('buscarProducto', textoBusqueda);
    }

    // Completar la URL con los parámetros codificados.
    url += params.toString();

    // Realizar la petición fetch al endpoint de la API de productos.
    fetch(url)
        .then(res => res.json())                // Parsear la respuesta como JSON
        .then(data => renderProductos(data))     // Renderizar los productos recibidos
        .catch(err => {
            // En caso de error, mostrar un mensaje en consola y en la cuadrícula.
            console.error('Error cargando productos:', err);
            document.getElementById('productosGrid').innerHTML =
                '<p class="sin-productos">Error al cargar los productos.</p>';
        });
}

// ======================== BÚSQUEDA (AJAX) - LIVE SEARCH ========================

/**
 * Busca productos en tiempo real (live search) combinando el texto introducido
 * con la categoría actualmente seleccionada.
 * Se invoca cada vez que el usuario escribe en el campo de búsqueda.
 */
function buscarProductos() {
    // Obtener el texto escrito en el campo de búsqueda, eliminando espacios sobrantes.
    const texto = document.getElementById('inputBuscarProducto').value.trim();

    // Determinar la categoría activa actualmente.
    // Por defecto se asume 'todas' si no hay ningún botón con la clase 'activa'.
    let idCategoriaActiva = 'todas';
    const botonActivo = document.querySelector('.cat-btn.activa');
    if (botonActivo && botonActivo.dataset.categoria) {
        idCategoriaActiva = botonActivo.dataset.categoria;
    }

    // Construir la URL con los parámetros de búsqueda y categoría.
    let url = 'api/productos.php?';
    let params = new URLSearchParams();

    // Solo añadir el parámetro de búsqueda si el usuario ha escrito algo.
    if (texto) {
        params.append('buscarProducto', texto);
    }

    // Siempre incluir la categoría activa en la petición.
    params.append('idCategoria', idCategoriaActiva);
    url += params.toString();

    // Realizar la petición fetch al endpoint de la API de productos.
    fetch(url)
        .then(res => res.json())                // Parsear la respuesta como JSON
        .then(data => renderProductos(data))     // Renderizar los productos recibidos
        .catch(err => {
            // En caso de error, mostrar un mensaje en consola y en la cuadrícula.
            console.error('Error buscando productos:', err);
            document.getElementById('productosGrid').innerHTML =
                '<p class="sin-productos">Error al buscar productos.</p>';
        });
}

// ======================== RENDER PRODUCTOS ========================

/**
 * Renderiza la lista de productos en la cuadrícula (grid) del cajero.
 * Genera dinámicamente las tarjetas HTML de cada producto con su imagen,
 * nombre, precio y stock.
 * 
 * @param {Array} productos - Array de objetos producto devueltos por la API.
 *   Cada objeto contiene: id, nombre, precio, stock, imagen.
 */
function renderProductos(productos) {
    // Obtener el contenedor de la cuadrícula de productos.
    const grid = document.getElementById('productosGrid');

    // Si no hay productos o el array está vacío, mostrar un mensaje informativo.
    if (!productos || productos.length === 0) {
        grid.innerHTML = '<p class="sin-productos">No hay productos disponibles.</p>';
        return;
    }

    // Construir el HTML de todas las tarjetas de producto.
    let html = '';
    productos.forEach(prod => {
        // Formatear el precio con 2 decimales y coma.
        const ivaProd = (prod.iva !== null && prod.iva !== undefined && prod.iva !== "") ? parseInt(prod.iva) : 21;

        // 1. Encontrar tarifa 'Cliente' (por defecto)
        const tarifaCliente = tarifasDisponibles.find(t => t.nombre === 'Cliente');
        const tarifaClienteId = tarifaCliente ? tarifaCliente.id : null;

        // 2. Comprobar si hay precio para esa tarifa (ya sea manual o calculado)
        const preciosManuales = prod.preciosTarifas || {};
        const precioBaseOriginal = parseFloat(prod.precio) || 0;
        let precioBaseEfectivo = precioBaseOriginal;

        // Usar precio de tarifa si existe, sin importar si es manual o calculado
        if (tarifaClienteId && preciosManuales[tarifaClienteId]) {
            precioBaseEfectivo = preciosManuales[tarifaClienteId].precio;
        }

        const precioPVP = precioBaseEfectivo * (1 + (ivaProd / 100));
        let precioFmt = round2(precioPVP).toFixed(2).replace('.', ',');

        // Usar la imagen del producto si existe; de lo contrario, usar el logo por defecto.
        let imgSrc = prod.imagen && prod.imagen !== '' ? prod.imagen : 'webroot/img/logo.PNG';

        // Generar selector de tarifas
        let selectorTarifas = `<select class="tarifa-selector" 
                                onclick="event.stopPropagation()" 
                                onfocus="guardarTarifaAnterior(this)"
                                onchange="actualizarPrecioCard(this, ${precioBaseOriginal}, ${ivaProd})">`;

        tarifasDisponibles.forEach(tarifa => {
            const selected = tarifa.nombre === 'Cliente' ? 'selected' : '';
            selectorTarifas += `<option value="${tarifa.descuento_porcentaje}" 
                                        data-requiere-cliente="${tarifa.requiere_cliente}" 
                                        data-tarifa-id="${tarifa.id}"
                                        ${selected}>${tarifa.nombre}</option>`;
        });
        selectorTarifas += `</select>`;

        // Generar la tarjeta del producto.
        html += `<div class="producto-card" data-id="${prod.id}"
                    data-nombre="${prod.nombre.replace(/"/g, '&quot;')}"
                    data-precio="${precioBaseEfectivo}" 
                    data-precio-original="${precioBaseOriginal}"
                    data-pvp="${round2(precioPVP).toFixed(2)}"
                    data-iva="${ivaProd}"
                    data-precios-tarifas='${JSON.stringify(preciosManuales)}'
                    data-stock="${prod.stock || 0}"
                    onclick="agregarAlCarrito(this)" style="${prod.stock <= 0 ? 'opacity: 0.5; cursor: not-allowed; scale: 1; transform: translateY(0px);' : ''}">
                    <div class="producto-nombre">${prod.nombre}</div>
                    <div class="producto-imagen">
                        <img src="${imgSrc}" alt="${prod.nombre.replace(/"/g, '"')}">
                    </div>
                    <div class="producto-info-inferior" style="display: flex; flex-direction: column; gap: 2px;">
                        <span class="producto-precio">${precioFmt} €</span>
                        ${selectorTarifas}
                        <span class="producto-stock" ${prod.stock <= 0 ? 'style="color: red; text-decoration: underline;"' : ''}>Stock: ${prod.stock}</span>
                    </div>
                </div>`;
    });

    // Insertar todo el HTML generado en la cuadrícula de productos.
    grid.innerHTML = html;
}

/**
 * Guarda la tarifa actual antes de cambiarla para poder revertir si es necesario.
 */
function guardarTarifaAnterior(selectElement) {
    const card = selectElement.closest('.producto-card');
    const id = card.dataset.id;
    tarifaAnteriorCard.set(id, selectElement.value);
}

/**
 * Revierte el selector de tarifa a su valor anterior.
 */
function revertirTarifaCard(cardId) {
    const card = document.querySelector(`.producto-card[data-id="${cardId}"]`);
    if (card) {
        const select = card.querySelector('.tarifa-selector');
        if (select && tarifaAnteriorCard.has(cardId)) {
            select.value = tarifaAnteriorCard.get(cardId);
            // Actualizar precio visualmente
            const iva = parseInt(card.dataset.iva);
            const precioOriginal = parseFloat(card.dataset.precioOriginal || card.dataset.precio);
            actualizarPrecioCard(select, precioOriginal, iva, false);
        }
    }
}

/**
 * Resetea el selector de tarifa de una card a la tarifa "Cliente".
 */
function resetearTarifaCard(card) {
    if (!card) return;
    const select = card.querySelector('.tarifa-selector');
    if (select) {
        // Buscar la opción de "Cliente" (normalmente descuento 0 y nombre "Cliente")
        let optionCliente = null;
        let tarifaClienteId = null;
        for (let i = 0; i < select.options.length; i++) {
            if (select.options[i].text === 'Cliente') {
                optionCliente = select.options[i];
                tarifaClienteId = optionCliente.dataset.tarifaId;
                break;
            }
        }

        if (optionCliente) {
            select.value = optionCliente.value;
            const iva = parseInt(card.dataset.iva);
            
            // Obtener los precios de tarifa almacenados
            const preciosTarifasStr = card.dataset.preciosTarifas || '{}';
            let preciosTarifas = {};
            try {
                preciosTarifas = JSON.parse(preciosTarifasStr);
            } catch (e) { console.error("Error parseando preciosTarifas", e); }
            
            // Si existe precio para la tarifa Cliente, usarlo directamente
            let precioBase;
            if (tarifaClienteId && preciosTarifas[tarifaClienteId]) {
                precioBase = preciosTarifas[tarifaClienteId].precio;
            } else {
                // Si no hay precio de tarifa, usar el precio original
                precioBase = parseFloat(card.dataset.precioOriginal || card.dataset.precio);
            }
            
            actualizarPrecioCard(select, precioBase, iva, false);
        }
    }
}

/**
 * Actualiza el precio mostrado en la tarjeta del producto según la tarifa seleccionada.
 * @param {boolean} triggerAutoAdd - Si es true, intentará añadir al carrito si se cumplen las condiciones.
 */
function actualizarPrecioCard(selectElement, precioBase, iva, triggerAutoAdd = true) {
    const card = selectElement.closest('.producto-card');
    const precioSpan = card.querySelector('.producto-precio');
    const selectedOption = selectElement.options[selectElement.selectedIndex];
    const tarifaId = selectedOption.dataset.tarifaId;
    const descuento = parseFloat(selectElement.value) || 0;
    const requiereCliente = selectedOption.dataset.requiereCliente === "1" || selectedOption.dataset.requiereCliente === "true";

    // Si no tenemos el precio original guardado, lo guardamos ahora
    if (!card.dataset.precioOriginal) {
        card.dataset.precioOriginal = precioBase;
    }
    const precioBaseOriginal = parseFloat(card.dataset.precioOriginal);

    // Obtener precios por tarifa
    const preciosTarifasStr = card.dataset.preciosTarifas || '{}';
    let preciosTarifas = {};
    try {
        preciosTarifas = JSON.parse(preciosTarifasStr);
    } catch (e) { console.error("Error parseando preciosTarifas", e); }

    // Calcular nuevo precio base
    let nuevoPrecioBase;
    // Usar precio de tarifa si existe (ya sea manual o calculado)
    if (preciosTarifas[tarifaId]) {
        nuevoPrecioBase = preciosTarifas[tarifaId].precio;
    } else {
        // Usar descuento porcentual sobre el precio base original
        nuevoPrecioBase = precioBaseOriginal * (1 - (descuento / 100));
    }

    // Calcular precio final con IVA para mostrar (PVP unitario)
    // REDONDEAMOS EL PVP UNITARIO A 2 DECIMALES PARA EVITAR DESCUADRES (PVP x Cantidad)
    const precioFinalConIva = round2(nuevoPrecioBase * (1 + (iva / 100)));

    // Actualizar texto en la tarjeta
    precioSpan.textContent = precioFinalConIva.toFixed(2).replace('.', ',') + ' €';

    // El nuevo precio base efectivo para el carrito lo recalculamos desde el PVP redondeado
    const nuevoPrecioBaseAjustado = precioFinalConIva / (1 + (iva / 100));

    // Actualizar el data-precio y data-pvp de la card
    card.dataset.precio = nuevoPrecioBaseAjustado;
    card.dataset.pvp = precioFinalConIva.toFixed(2);

    // Lógica de cliente registrado
    if (requiereCliente && triggerAutoAdd) {
        // No hay cliente o el cliente cambia: guardar estado y abrir modal
        productoPendienteTarifa = {
            card: card,
            precioBase: precioBase,
            ivaSize: iva
        };
        abrirModalBuscarClienteRegistrado();
    } else if (triggerAutoAdd && descuento > 0) {
        // Tarifa con descuento pero sin requerir cliente (ej: una oferta puntual)
        // El usuario dijo "si cambio a alguna otra se actualiza el precio", 
        // pero para el caso del cliente dijo explícitamente "si está se añade al carrito".
        // Para tarifas normales parece que solo Actualiza el precio. No añadiré al carrito automáticamente salvo que sea restringida.
    }
}

// ======================== PERMISOS Y CREAR PRODUCTOS ========================

/**
 * Verifica si el usuario tiene permiso para crear productos y muestra el botón si corresponde.
 */
function verificarPermisoCrearProductos() {
    console.log('Verificando permiso para crear productos...');
    fetch('api/productos.php?checkPermisoCrear=1')
        .then(res => {
            console.log('Respuesta verificación permiso:', res.status);
            return res.json();
        })
        .then(data => {
            console.log('Datos de permiso:', data);
            console.log('Usuario tiene permiso:', data.tienePermiso);

            // Buscar el botón existente en la barra de opciones
            const btnExistente = document.getElementById('btnNuevoProducto');

            if (btnExistente) {
                if (data.tienePermiso) {
                    // Mostrar el botón existente
                    btnExistente.style.display = 'flex';
                    btnExistente.onclick = function () {
                        abrirModalNuevoProducto();
                    };
                    console.log('Botón mostrado exitosamente');
                } else {
                    // Ocultar el botón si no tiene permiso
                    btnExistente.style.display = 'none';
                    console.log('Usuario sin permiso - botón oculto');
                }
            } else {
                // Fallback: crear el botón dinámicamente si no existe
                console.log('Botón no encontrado, creando dinámicamente...');
                const formBuscar = document.getElementById('formBuscarProducto');
                if (formBuscar) {
                    const btn = document.createElement('button');
                    btn.className = 'btn-nuevo-producto';
                    btn.innerHTML = 'Nuevo';
                    btn.onclick = function () {
                        if (data.tienePermiso) {
                            abrirModalNuevoProducto();
                        } else {
                            alert('No tienes permiso para crear productos. Contacta al administrador.');
                        }
                    };
                    btn.style.marginLeft = '10px';
                    btn.style.padding = '8px 16px';
                    btn.style.background = '#10b981';
                    btn.style.color = 'white';
                    btn.style.border = 'none';
                    btn.style.borderRadius = '6px';
                    btn.style.cursor = 'pointer';
                    btn.style.fontWeight = '600';
                    formBuscar.appendChild(btn);
                    console.log('Botón creado dinámicamente');
                } else {
                    console.error('No se encontró el elemento formBuscarProducto');
                }
            }
        })
        .catch(err => console.error('Error verificando permisos:', err));
}

/**
 * Abre el modal para crear un nuevo producto.
 */
function abrirModalNuevoProducto() {
    console.log('Intentando abrir modal de nuevo producto...');
    const select = document.getElementById('nuevoProductoCategoria');
    console.log('Elemento select encontrado:', select);

    if (!select) {
        console.error('No se encontró el elemento nuevoProductoCategoria');
        alert('Error: No se encontró el formulario del producto. Por favor, recarga la página.');
        return;
    }

    // Limpiar campos del formulario
    document.getElementById('nuevoProductoNombre').value = '';
    document.getElementById('nuevoProductoPrecio').value = '';
    document.getElementById('nuevoProductoStock').value = '0';
    if (document.getElementById('nuevoProductoEstado')) {
        document.getElementById('nuevoProductoEstado').value = '1';
    }
    document.getElementById('editProductoImagen').src = 'webroot/img/logoCPU.PNG';
    document.getElementById('editProductoImagenInput').value = '';

    // Obtener categorías para el select
    fetch('api/categorias.php')
        .then(res => res.json())
        .then(categorias => {
            select.innerHTML = '<option value="">Selecciona una categoría</option>';
            categorias.forEach(cat => {
                select.innerHTML += `<option value="${cat.nombre}">${cat.nombre}</option>`;
            });
            document.getElementById('modalNuevoProducto').style.display = 'flex';
            console.log('Modal mostrado');
        })
        .catch(err => {
            console.error('Error al obtener categorías:', err);
            alert('Error al cargar las categorías. Por favor, recarga la página e intenta de nuevo.');
        });
}

// Verificar permisos y cargar tarifas al cargar la página
document.addEventListener('DOMContentLoaded', function () {
    verificarPermisoCrearProductos();
    cargarTarifasCajero().then(() => {
        // Si ya hay productos cargados (por PHP), podríamos querer refrescarlos o confiar en la carga inicial de PHP
        // Pero para que el selector funcione con los productos iniciales de PHP, PHP también debe generarlo.
    });
});

// ======================== FUNCIONES DE MODAL (necesarias para el modal de nuevo producto) ========================

/**
 * Cierra un modal ocultándolo.
 * @param {string} id - ID del modal a cerrar.
 */
function cerrarModal(id) {
    document.getElementById(id).style.display = 'none';
}

/**
 * Previsualiza la imagen seleccionada antes de subirla.
 * @param {Event} event - Evento de cambio del input file.
 */
function previsualizarImagen(event) {
    const file = event.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function (e) {
            document.getElementById('editProductoImagen').src = e.target.result;
        };
        reader.readAsDataURL(file);
    }
}

/**
 * Abre una imagen en grande en un modal.
 * @param {string} src - URL de la imagen.
 * @param {string} alt - Texto alternativo de la imagen.
 */
function abrirImagenGrande(src, alt = '') {
    // Crear el elemento overlay
    const overlay = document.createElement('div');
    overlay.id = 'modalImagenGrande';
    overlay.style.cssText = 'position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.9);display:flex;justify-content:center;align-items:center;z-index:9999;cursor:pointer;';

    const img = document.createElement('img');
    img.src = src;
    img.alt = alt;
    img.style.cssText = 'max-width:90%;max-height:90%;object-fit:contain;border-radius:8px;';

    overlay.appendChild(img);
    overlay.onclick = function () {
        document.body.removeChild(overlay);
    };

    document.body.appendChild(overlay);
}

/**
 * Muestra el modal de cierre temporal.
 */
function mostrarModalCierreTemporal() {
    document.getElementById('modalCierreTemporal').style.display = 'flex';
}

/**
 * Realiza una pausa/descanso cerrando la sesión del usuario pero manteniendo la caja abierta.
 */
function pausarSesion(motivo = 'pausa') {
    const btnCerrarSesion = document.querySelector('input[name="cerrarSesion"]');
    if (btnCerrarSesion && !motivo) {
        btnCerrarSesion.click();
    } else {
        // Fallback: enviar el cierre de sesión manualmente con el motivo
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'index.php';
        
        const inputLogout = document.createElement('input');
        inputLogout.type = 'hidden';
        inputLogout.name = 'cerrarSesion';
        inputLogout.value = 'Cerrar Sesión';
        form.appendChild(inputLogout);

        if (motivo) {
            const inputMotivo = document.createElement('input');
            inputMotivo.type = 'hidden';
            inputMotivo.name = 'motivoCierre';
            inputMotivo.value = motivo;
            form.appendChild(inputMotivo);
        }

        document.body.appendChild(form);
        form.submit();
    }
}

/**
 * Inicia el proceso de cambio de turno.
 * En este sistema, el cambio de turno simplemente cierra la sesión del usuario
 * manteniendo la caja abierta para el siguiente empleado.
 */
function cambiarTurno() {
    pausarSesion('turno');
}

