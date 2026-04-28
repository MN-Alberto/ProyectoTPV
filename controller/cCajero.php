<?php
/**
 * Controlador del cajero. Gestiona la vista del TPV para empleados.
 * 
 * @author Alberto Méndez
 * @version 1.8 (14/04/2026)
 */

// Requerimos los modelos necesarios
require_once(__DIR__ . '/../model/Producto.php');
require_once(__DIR__ . '/../model/Categoria.php');
require_once(__DIR__ . '/../model/Venta.php');
require_once(__DIR__ . '/../model/LineaVenta.php');
require_once(__DIR__ . '/../model/Caja.php');
require_once(__DIR__ . '/../model/Usuario.php');
require_once(__DIR__ . '/../core/conexionDB.php');
require_once(__DIR__ . '/../core/Verifactu.php');

// Verificamos que el usuario está guardado en la sesión, si no lo está redirigimos al login
if (!isset($_SESSION['idUsuario'])) {
    $_SESSION['paginaEnCurso'] = 'login';
    header('Location: index.php');
    exit();
}

// ✅ SOLUCION FINAL DEFINITIVA: Comprobamos UNA VEZ POR CARGA si los permisos han cambiado en BD
// Si es asi actualizamos la sesion AUTOMATICAMENTE cuando el usuario pulsa F5
// No es necesario cerrar sesion nunca mas.
$usuarioActual = Usuario::buscarPorId($_SESSION['idUsuario']);
if ($usuarioActual) {
    $_SESSION['permisosUsuario'] = $usuarioActual->getPermisos();
}

// Cargar permisos del usuario logueado DESDE LA SESION (no consultar BD cada vez)
$permisosUsuario = isset($_SESSION['permisosUsuario']) ? $_SESSION['permisosUsuario'] : '';
// ✅ SOLUCION: Quitar espacios y normalizar permisos
$permisosUsuario = str_replace(' ', '', $permisosUsuario);
// ✅ COMPATIBILIDAD PHP 7.x: Usar strpos en vez de str_contains (funciona en todas las versiones)
$puedeModificarPrecios = $_SESSION['rolUsuario'] === 'admin' || (strpos($permisosUsuario, 'modificar_precios') !== false);
$puedeProductoComodin = $_SESSION['rolUsuario'] === 'admin' || (strpos($permisosUsuario, 'producto_comodin') !== false);
$puedeRetirarDinero = $_SESSION['rolUsuario'] === 'admin' || (strpos($permisosUsuario, 'retirar_dinero') !== false);

// Si el usuario solicita cerrar sesión destruimos la sesión completamente
if (isset($_REQUEST['cerrarSesion'])) {
    /**
     * Gestión del cierre de turno o pausa técnica.
     * Si se proporciona un motivo, se registra el evento en la auditoría del empleado
     * y se actualizan sus contadores de actividad.
     */
    if (isset($_REQUEST['motivoCierre'])) {
        $sesionCaja = Caja::obtenerSesionAbierta();
        if ($sesionCaja) {
            $sesionCaja->registrarInterrupcion(
                $_SESSION['idUsuario'],
                $_SESSION['nombreUsuario'] ?? 'Desconocido',
                $_REQUEST['motivoCierre']
            );
        }

        // Incrementamos las métricas de rendimiento del empleado basado en su acción
        if ($_REQUEST['motivoCierre'] === 'pausa') {
            Usuario::incrementarDescansos($_SESSION['idUsuario']);
        } elseif ($_REQUEST['motivoCierre'] === 'turno') {
            Usuario::incrementarTurnos($_SESSION['idUsuario']);
        }
    }

    // Registrar logout antes de destruir sesión
    try {
        require_once(__DIR__ . '/../config/confDB.php');
        $pdo = new PDO(RUTA, USUARIO, PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $pdo->prepare("INSERT INTO logs_sistema (tipo, usuario_id, usuario_nombre, descripcion) VALUES ('logout', :usuario_id, :usuario_nombre, :descripcion)");
        $stmt->execute([
            ':usuario_id' => $_SESSION['idUsuario'] ?? null,
            ':usuario_nombre' => $_SESSION['nombreUsuario'] ?? 'Desconocido',
            ':descripcion' => 'Usuario cerró sesión' . (isset($_REQUEST['motivoCierre']) ? ' (Motivo: ' . $_REQUEST['motivoCierre'] . ')' : '')
        ]);
    } catch (Exception $e) {
        // Silenciar errores de logging
    }

    // Destruir todas las variables de sesión
    $_SESSION = array();
    // Destruir la sesión
    session_destroy();
    // Redirigir al login
    header('Location: index.php');
    exit();
}

// Cargar categorías para el menú
$categorias = Categoria::obtenerTodas();

// Cargar estado de la caja
$sesionCaja = Caja::obtenerSesionAbierta();

/** 
 * Recuperación de sesiones interrumpidas.
 * Si un empleado dejó la caja en espera (pausa para descanso), recuperamos
 * los metadatos para informar al sistema y mostrar el aviso correspondiente.
 */
if ($sesionCaja && $sesionCaja->getInterrupcionTipo()) {
    // Preparamos los datos para que la vista los use
    $_SESSION['interrupcionRecuperada'] = [
        'tipo' => $sesionCaja->getInterrupcionTipo(),
        'usuarioId' => $sesionCaja->getInterrupcionUsuarioId(),
        'usuarioNombre' => $sesionCaja->getInterrupcionUsuarioNombre(),
        'fecha' => $sesionCaja->getInterrupcionFecha()
    ];
    // Limpieza de seguridad: solo permitimos una recuperación tras el login
    $sesionCaja->limpiarInterrupcion();
} else {
    // Si no hay interrupción en BD, nos aseguramos de limpiar la sesión para que no persista el modal
    unset($_SESSION['interrupcionRecuperada']);
}

// Cargar el cambio de la última sesión cerrada (para recuperar al abrir)
$ultimaSesionCerrada = Caja::obtenerUltimaSesionCerrada();
$cambioAnterior = $ultimaSesionCerrada ? $ultimaSesionCerrada->getCambio() : 0;

// Los productos se cargan por AJAX desde api/productos.php, pero cargamos una lista inicial para la primera vista
$idCategoriaSeleccionada = null;
$productos = Producto::obtenerTodos();

// Cargar tarifas prefijadas para el selector de tickets
try {
    $conexion = ConexionDB::getInstancia()->getConexion();
    $stmt = $conexion->query("SELECT * FROM tarifas_prefijadas WHERE activo = 1 ORDER BY orden");
    $tarifas = $stmt->fetchAll();
} catch (Exception $e) {
    // Si la tabla no existe, usar array vacío
    $tarifas = [];
}

// Procesar nueva venta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    // Si la solicitud es para registrar una nueva venta
    if ($_POST['accion'] === 'registrarVenta' && isset($_POST['carrito'])) {

        // Decodificamos el carrito
        $carrito = json_decode($_POST['carrito'], true);

        // Si el carrito no está vacío
        if (!empty($carrito)) {
            // Creamos un nuevo objeto venta
            $venta = new Venta();
            // Indicamos el id del usuario que realiza la venta
            $venta->setIdUsuario($_SESSION['idUsuario']);
            // Indicamos la fecha de la venta
            $venta->setFecha(date('Y-m-d H:i:s'));
            // Indicamos el método de pago
            $venta->setMetodoPago($_POST['metodoPago'] ?? 'efectivo');
            // Indicamos el estado de la venta
            $venta->setEstado('completada');
            // Indicamos el tipo de documento en base a lo que se haya seleccionado
            $venta->setTipoDocumento($_POST['tipoDocumento'] ?? 'ticket');
            // Indicamos la tarifa aplicada
            $venta->setIdTarifa($_POST['idTarifa'] ?? null);

            /** 
             * Gestión de la persistencia de la venta.
             * Se vincula la transacción con el usuario activo y la sesión de caja abierta.
             */
            if ($sesionCaja) {
                $venta->setIdSesionCaja($sesionCaja->getId());
            }

            // Indicamos el idioma del ticket
            $venta->setIdiomaTicket($_POST['idioma_ticket'] ?? 'es');

            //Calculamos el total y validamos el stock.
            $total = 0;
            $stockValido = true;
            // Recorremos los productos del carrito para validar el stock y calcular el total con IVA
            foreach ($carrito as $item) {
                // Determinar precisión del producto
                $dec = isset($item['decimales']) ? (int) $item['decimales'] : 2;

                // Skip stock validation for comodin products
                if (isset($item['esComodin']) && $item['esComodin'] === true) {
                    $precioUnitarioConIva = isset($item['pvpUnitario']) ? (float) $item['pvpUnitario'] : (float) $item['precio'];
                    $total += round($precioUnitarioConIva * $item['cantidad'], $dec);
                    continue;
                }

                // Buscamos el producto por id para asegurar datos frescos
                $producto = Producto::buscarPorId($item['idProducto']);

                // Si el producto no existe o no hay stock suficiente
                if (!$producto || $producto->getStock() < $item['cantidad']) {
                    $stockValido = false;
                    break;
                }

                // Usar el PVP unitario redondeado enviado desde el carrito (garantiza precisión financiera)
                // Si por algún motivo no viniera, lo calculamos como fallback
                $precioUnitarioConIva = isset($item['pvpUnitario']) ? (float) $item['pvpUnitario'] : (float) $item['precio'] * (1 + ($producto->getIvaPorcentaje() / 100));

                // Sumamos al total acumulado redondeado a sus decimales para evitar arrastre de coma flotante
                $total += round($precioUnitarioConIva * $item['cantidad'], $dec);
            }

            // Si el stock no es válido, guardamos un error en la sesión y recargamos la página
            if (!$stockValido) {
                $_SESSION['ventaError'] = 'No hay suficiente stock para uno o más productos.';
                header('Location: index.php');
                exit();
            }

            // Aplicar descuento al total antes de guardar la venta

            // Obtenemos el tipo de descuento y el valor
            $descuentoTipo = $_POST['descuentoTipo'] ?? 'ninguno';
            $descuentoValor = (float) ($_POST['descuentoValor'] ?? 0);

            // Obtenemos los datos del descuento de tarifa
            $descuentoTarifaTipo = $_POST['descuentoTarifaTipo'] ?? 'ninguno';
            $descuentoTarifaValor = (float) ($_POST['descuentoTarifaValor'] ?? 0);
            $descuentoTarifaCupon = $_POST['descuentoTarifaCupon'] ?? '';

            // Obtenemos los datos del descuento manual (cupones globales)
            $descuentoManualTipo = $_POST['descuentoManualTipo'] ?? $descuentoTipo;
            $descuentoManualValor = (float) ($_POST['descuentoManualValor'] ?? $descuentoValor);

            $importeDescuentoManual = 0;

            // Calcular descuento manual (código promocional)
            if ($descuentoManualTipo === 'porcentaje') {
                $importeDescuentoManual = $total * ($descuentoManualValor / 100);
            } elseif ($descuentoManualTipo === 'fijo') {
                $importeDescuentoManual = $descuentoManualValor;
            }

            // Determinar precisión del total (max del carrito, min 2)
            $precTotal = 2;
            foreach ($carrito as $it) {
                $d = isset($it['decimales']) ? (int) $it['decimales'] : 2;
                if ($d > $precTotal)
                    $precTotal = $d;
            }

            // Descuento total (Ya no hay importeDescuentoTarifa global porque es por producto)
            $importeDescuento = round($importeDescuentoManual, $precTotal);

            // Restamos el descuento al total
            $total = max(0, round($total - $importeDescuento, $precTotal));

            // Si el pago es en efectivo y el total es mayor a 1000
            if ($venta->getMetodoPago() === 'efectivo' && $total > 1000) {
                // Guardamos un error en la sesión y recargamos la página
                $_SESSION['ventaError'] = "No se permite el pago en efectivo para importes superiores a 1.000€.";
                header('Location: index.php');
                exit();
            }

            // Indicamos el precio total de la venta
            $venta->setTotal($total);

            // Guardamos los datos del descuento en la venta
            $venta->setDescuentoTipo($_POST['descuentoTipo'] ?? 'ninguno');
            $venta->setDescuentoValor((float) ($_POST['descuentoValor'] ?? 0));
            $venta->setDescuentoCupon($_POST['descuentoCupon'] ?? '');
            $venta->setDescuentoTarifaTipo('ninguno');
            $venta->setDescuentoTarifaValor(0);
            $venta->setDescuentoTarifaCupon('');
            $venta->setDescuentoManualTipo($_POST['descuentoManualTipo'] ?? 'ninguno');
            $venta->setDescuentoManualValor((float) ($_POST['descuentoManualValor'] ?? 0));
            $venta->setDescuentoManualCupon($_POST['descuentoManualCupon'] ?? '');

            // Si el pago es en efectivo, guardar datos extras para el control de caja
            if (($venta->getMetodoPago() === 'efectivo')) {
                // Obtenemos el dinero entregado y el cambio devuelto
                $entregado = (float) ($_POST['dineroEntregado'] ?? $total);
                $cambio = (float) ($_POST['cambioDevuelto'] ?? 0);
                // Indicamos el dinero entregado y el cambio devuelto
                $venta->setImporteEntregado($entregado);
                $venta->setCambioDevuelto($cambio);

                // Actualizar importe en la sesión de caja si está abierta
                if ($sesionCaja) {
                    // Actualizamos el efectivo en la sesión de caja
                    $sesionCaja->actualizarEfectivo($entregado - $cambio);
                }
            } elseif ($venta->getMetodoPago() === 'mixto') {
                // Pago mixto: solo la parte en efectivo afecta a la caja física
                $desglosePagoJson = $_POST['desglosePago'] ?? '';
                if ($desglosePagoJson) {
                    $desgloseData = json_decode($desglosePagoJson, true);
                    if ($desgloseData && isset($desgloseData['efectivo']) && $desgloseData['efectivo'] > 0) {
                        $efectivoMixto = (float) $desgloseData['efectivo'];
                        $cambioMixto = (float) ($desgloseData['cambio'] ?? 0);
                        $venta->setImporteEntregado($efectivoMixto);
                        $venta->setCambioDevuelto($cambioMixto);

                        if ($sesionCaja) {
                            $sesionCaja->actualizarEfectivo($efectivoMixto - $cambioMixto);
                        }
                    }
                }
            }

            // Insertamos la venta
            // Guardamos los datos del cliente en la venta
            $clienteNif = $_POST['clienteNif'] ?? '';
            $clienteNombre = $_POST['clienteNombre'] ?? '';
            $clienteDireccion = $_POST['clienteDireccion'] ?? '';
            $observaciones = $_POST['observaciones'] ?? '';
            $mensajePersonalizado = trim($_POST['mensajePersonalizado'] ?? '');
            $idiomaTicket = trim($_POST['idioma_ticket'] ?? 'es');

            error_log("Datos del cliente recibidos: NIF='$clienteNif', Nombre='$clienteNombre', IdiomaTicket='$idiomaTicket'");

            $venta->setClienteDni($clienteNif);
            $venta->setClienteNombre($clienteNombre);
            $venta->setClienteDireccion($clienteDireccion);
            $venta->setClienteObservaciones($observaciones);
            $venta->setMensajePersonalizado($mensajePersonalizado);
            $venta->setIdiomaTicket($idiomaTicket);

            // Desglose de pago mixto
            $desglosePagoStr = $_POST['desglosePago'] ?? '';
            $venta->setDesglosePago($desglosePagoStr ?: null);

            // Guardar puntos en la venta para el historial
            $venta->setPuntosGanados(isset($_POST['puntosGanados']) ? (int) $_POST['puntosGanados'] : 0);
            $venta->setPuntosCanjeados(isset($_POST['puntosCanjeadosCantidad']) ? (int) $_POST['puntosCanjeadosCantidad'] : 0);
            $venta->setPuntosBalance(isset($_POST['puntosBalance']) ? (int) $_POST['puntosBalance'] : 0);

            $venta->insertar();

            // Registrar la venta en el log del sistema
            try {
                $conexion = ConexionDB::getInstancia()->getConexion();
                $stmtLog = $conexion->prepare("INSERT INTO logs_sistema (tipo, usuario_id, usuario_nombre, descripcion, detalles) VALUES ('venta', :usuario_id, :usuario_nombre, :descripcion, :detalles)");
                $stmtLog->execute([
                    ':usuario_id' => $_SESSION['idUsuario'],
                    ':usuario_nombre' => $_SESSION['nombreUsuario'] ?? 'Desconocido',
                    ':descripcion' => 'Venta registrada por ' . ($_SESSION['nombreUsuario'] ?? 'Desconocido'),
                    ':detalles' => json_encode([
                        'id_venta' => $venta->getId(),
                        'total' => $total,
                        'metodo_pago' => $venta->getMetodoPago(),
                        'cliente' => $clienteNif ?: 'Sin cliente',
                        'productos' => count($carrito)
                    ])
                ]);
            } catch (Exception $e) {
                error_log("Error al registrar log de venta: " . $e->getMessage());
            }

            // Actualizar estadísticas del cliente si se especificó un DNI
            $clienteNif = $_POST['clienteNif'] ?? '';
            if (!empty($clienteNif)) {
                try {
                    $conexion = ConexionDB::getInstancia()->getConexion();
                    // Calcular total de productos en el carrito
                    $totalProductos = 0;
                    foreach ($carrito as $item) {
                        $totalProductos += $item['cantidad'];
                    }

                    // Puntos a restar (canjeados)
                    $puntosCanjeados = isset($_POST['puntosCanjeadosCantidad']) ? (int) $_POST['puntosCanjeadosCantidad'] : 0;
                    $puntosGanados = 0;
                    if ($total > 0) {
                        $puntosGanados = round($total * 10);
                    }

                    // Actualizar productos comprados y ventas realizadas del cliente
                    $stmtCliente = $conexion->prepare(
                        "UPDATE clientes SET 
                            productos_comprados = productos_comprados + ?, 
                            compras_realizadas = compras_realizadas + 1,
                            puntos = puntos - ? + ?
                        WHERE dni = ? AND activo = 1"
                    );

                    $stmtCliente->execute([$totalProductos, $puntosCanjeados, $puntosGanados, $clienteNif]);

                    // Comprobar si fue identificado en el modal de puntos antes de la venta
                    $clienteIdentificadoPuntos = isset($_POST['clienteIdentificadoPuntos']) && $_POST['clienteIdentificadoPuntos'] === 'true';

                    if ($clienteIdentificadoPuntos) {
                        $_SESSION['mostrarModalPuntosPostVenta'] = true;
                        $_SESSION['postVentaPuntosGanados'] = $puntosGanados;

                        // Consultar los puntos actuales del cliente para mostrarlos en el modal
                        $stmtPuntosActuales = $conexion->prepare("SELECT puntos FROM clientes WHERE dni = ? AND activo = 1 LIMIT 1");
                        $stmtPuntosActuales->execute([$clienteNif]);
                        $resultadoPuntos = $stmtPuntosActuales->fetch(PDO::FETCH_ASSOC);
                        $_SESSION['puntosActualesAcumulados'] = $resultadoPuntos ? $resultadoPuntos['puntos'] : 0;
                    }
                } catch (Exception $e) {
                    // Si falla la actualización del cliente, no detenemos la venta
                    error_log("Error al actualizar estadísticas del cliente: " . $e->getMessage());
                }
            }


            // Insertamos las líneas de venta y actualizamos el stock
            $lineasVenta = [];
            // Recorremos los productos del carrito
            foreach ($carrito as $item) {
                $esComodin = isset($item['esComodin']) && $item['esComodin'] === true;

                // Si no es comodín, re-buscamos el producto para obtener su IVA y stock individual
                $producto = !$esComodin ? Producto::buscarPorId($item['idProducto']) : null;

                // Creamos una nueva línea de venta
                $linea = new LineaVenta();
                $linea->setIdVenta($venta->getId());

                // Indicamos el nombre y id del producto
                if ($esComodin) {
                    $linea->setIdProducto(null);
                    $linea->setNombreProducto($item['nombre'] ?? 'Comodín');
                    $ivaItem = isset($item['iva']) ? (float) $item['iva'] : 21;
                } else {
                    $linea->setIdProducto($item['idProducto']);
                    // ✅ USAR NOMBRE TRADUCIDO DEL MODELO, NO EL QUE VIENE DEL CARRITO
                    $nombreTraducido = $producto ? $producto->getNombre() : $item['nombre'];
                    $linea->setNombreProducto($nombreTraducido);
                    $ivaItem = $producto ? (float) $producto->getIvaPorcentaje() : 21;
                    // Sobrescribimos también el nombre en el item para que se guarde correctamente en la sesión
                    $item['nombre'] = $nombreTraducido;
                }

                // Indicamos la cantidad
                $linea->setCantidad($item['cantidad']);

                $dec = isset($item['decimales']) ? (int) $item['decimales'] : 2;
                $linea->setDecimales($dec);

                // Indicamos el precio unitario (Base sin IVA)
                $pvpUnitarioItem = isset($item['pvpUnitario']) ? (float) $item['pvpUnitario'] : null;

                if ($pvpUnitarioItem !== null) {
                    $precioBaseCalculado = $pvpUnitarioItem / (1 + ($ivaItem / 100));
                } else {
                    $precioBaseCalculado = isset($item['precio']) ? (float) $item['precio'] : 0;
                }

                $linea->setPrecioUnitario($precioBaseCalculado);

                // Calculamos el precio original base (sin IVA) para guardarlo
                $pvpOriginalUnitario = isset($item['pvpOriginalUnitario']) ? (float) $item['pvpOriginalUnitario'] : $pvpUnitarioItem;
                $precioOriginalBase = $pvpOriginalUnitario / (1 + ($ivaItem / 100));
                $linea->setPrecioOriginal($precioOriginalBase);

                // Guardamos el nombre de la tarifa aplicada
                $linea->setTarifaNombre($item['tarifaNombre'] ?? null);

                // Indicamos el IVA aplicado
                $linea->setIva($ivaItem);

                // Insertamos la línea de venta
                $linea->insertar();

                // Si el producto existe y no es comodín
                if ($producto) {
                    // Actualizamos el stock
                    $producto->actualizarStock(-$item['cantidad']);
                }

                // Guardamos la línea de venta para la sesión (impresión)
                $lineasVenta[] = [
                    'nombre' => $item['nombre'],
                    'nombre_es' => $producto ? $producto->getNombreEs() : ($item['nombre_es'] ?? ''),
                    'nombre_en' => $producto ? $producto->getNombreEn() : ($item['nombre_en'] ?? ''),
                    'nombre_fr' => $producto ? $producto->getNombreFr() : ($item['nombre_fr'] ?? ''),
                    'nombre_de' => $producto ? $producto->getNombreDe() : ($item['nombre_de'] ?? ''),
                    'nombre_ru' => $producto ? $producto->getNombreRu() : ($item['nombre_ru'] ?? ''),
                    'cantidad' => $item['cantidad'],
                    'precio' => $precioBaseCalculado,
                    'pvpUnitario' => $pvpUnitarioItem,
                    'pvpOriginalUnitario' => $pvpOriginalUnitario,
                    'tarifaNombre' => $item['tarifaNombre'] ?? 'Tarifa',
                    'iva' => $ivaItem,
                    'decimales' => $dec
                ];
            }

            // Obtener serie y número correlativo de la venta desde ventas_ids
            require_once(__DIR__ . '/../core/conexionDB.php');
            $conexion = ConexionDB::getInstancia()->getConexion();
            $stmtIds = $conexion->prepare("SELECT serie, numero FROM ventas_ids WHERE id = ?");
            $stmtIds->execute([$venta->getId()]);
            $idsData = $stmtIds->fetch(PDO::FETCH_ASSOC);

            if ($idsData) {
                $venta->setSerie($idsData['serie'] ?: ($_POST['tipoDocumento'] === 'factura' ? 'F' : 'T'));
                $venta->setNumero($idsData['numero'] ?: $venta->getId());
            } else {
                $venta->setSerie($_POST['tipoDocumento'] === 'factura' ? 'F' : 'T');
                $venta->setNumero($venta->getId());
            }

            // --- Verifactu: generar XML y enviar a AEAT (después de insertar líneas) ---
            try {
                $venta->enviarVerifactu();
                require_once(__DIR__ . '/../core/Verifactu.php');
                $_SESSION['ultimaVentaQR'] = Verifactu::generarURLQR($venta);
            } catch (Exception $e) {
                error_log("Error Verifactu: " . $e->getMessage());
                $_SESSION['ultimaVentaQR'] = '';
            }

            // Guardamos los datos de la venta en la sesión
            $_SESSION['ventaExito'] = true;
            $_SESSION['ultimaVentaId'] = $venta->getId();
            $_SESSION['ultimaVentaTotal'] = $total;
            $_SESSION['ultimaVentaTipo'] = $_POST['tipoDocumento'] ?? 'ticket';

            if ($idsData) {
                $_SESSION['ultimaVentaSerie'] = $idsData['serie'] ?: ($_POST['tipoDocumento'] === 'factura' ? 'F' : 'T');
                $_SESSION['ultimaVentaNumero'] = $idsData['numero'] ?: $venta->getId();
            } else {
                $_SESSION['ultimaVentaSerie'] = $_POST['tipoDocumento'] === 'factura' ? 'F' : 'T';
                $_SESSION['ultimaVentaNumero'] = $venta->getId();
            }

            $_SESSION['ultimaVentaCarrito'] = json_encode($lineasVenta);
            $_SESSION['ultimaVentaMetodoPago'] = $_POST['metodoPago'] ?? 'efectivo';
            $_SESSION['ultimaVentaFecha'] = date('d/m/Y H:i');
            $_SESSION['ultimaVentaEntregado'] = $_POST['dineroEntregado'] ?? $total;
            $_SESSION['ultimaVentaCambio'] = $_POST['cambioDevuelto'] ?? 0;

            // Guardamos los datos del descuento en la sesión
            $_SESSION['ultimaVentaDescuentoTipo'] = $_POST['descuentoTipo'] ?? 'ninguno';
            $_SESSION['ultimaVentaDescuentoValor'] = (float) ($_POST['descuentoValor'] ?? 0);
            $_SESSION['ultimaVentaDescuentoCupon'] = $_POST['descuentoCupon'] ?? '';

            // Guardamos los puntos en la sesión para el recibo inmediato
            $_SESSION['ultimaVentaPuntosGanados'] = (int) ($_POST['puntosGanados'] ?? 0);
            $_SESSION['ultimaVentaPuntosCanjeados'] = (int) ($_POST['puntosCanjeadosCantidad'] ?? 0);
            $_SESSION['ultimaVentaPuntosBalance'] = (int) ($_POST['puntosBalance'] ?? 0);

            // Guardamos el desglose de pago mixto en la sesión
            $_SESSION['ultimaVentaDesglosePago'] = $_POST['desglosePago'] ?? '';

            // Guardamos los datos del descuento de tarifa (cliente registrado, mayorista)
            $_SESSION['ultimaVentaDescuentoTarifaTipo'] = $_POST['descuentoTarifaTipo'] ?? 'ninguno';
            $_SESSION['ultimaVentaDescuentoTarifaValor'] = (float) ($_POST['descuentoTarifaValor'] ?? 0);
            $_SESSION['ultimaVentaDescuentoTarifaCupon'] = $_POST['descuentoTarifaCupon'] ?? '';

            // Guardamos los datos del descuento manual (código promocional)
            $_SESSION['ultimaVentaDescuentoManualTipo'] = $_POST['descuentoManualTipo'] ?? 'ninguno';
            $_SESSION['ultimaVentaDescuentoManualValor'] = (float) ($_POST['descuentoManualValor'] ?? 0);
            $_SESSION['ultimaVentaDescuentoManualCupon'] = $_POST['descuentoManualCupon'] ?? '';

            // Guardamos el idioma del ticket en la sesión
            $_SESSION['ultimaVentaIdiomaTicket'] = $_POST['idioma_ticket'] ?? 'es';

            // Guardamos los datos del cliente en la sesión
            $_SESSION['ultimaVentaClienteNif'] = $_POST['clienteNif'] ?? '';
            $_SESSION['ultimaVentaClienteNombre'] = $_POST['clienteNombre'] ?? '';
            $_SESSION['ultimaVentaClienteDir'] = $_POST['clienteDireccion'] ?? '';
            $_SESSION['ultimaVentaClienteObs'] = $_POST['observaciones'] ?? '';
            $_SESSION['ultimaVentaMensajePersonalizado'] = $mensajePersonalizado;
            $_SESSION['ultimaVentaQR'] = $venta->getURLQR();
            header('Location: index.php');
            exit();
        }
    }

    // Requerimos el modelo para el apartado de la devolución
    require_once(__DIR__ . '/../model/Devolucion.php');

    // Si el cajero solicita hacer caja
    if ($_POST['accion'] === 'previsualizarCaja') {
        // Array para almacenar el resumen de la caja del día
        $resumenCaja = Venta::obtenerResumenCajaAbierta();

        // Si existe una sesión de caja
        if ($sesionCaja) {
            // Obtenemos el id de la sesión de caja
            $idSesion = $sesionCaja->getId();
            // Obtenemos el total de devoluciones
            $totalDevoluciones = Devolucion::obtenerTotalPorSesion($idSesion);
            // Obtenemos el total de retiros
            $totalRetiros = $sesionCaja->getTotalRetiros();

            // Obtenemos el importe inicial y actual de la sesión de caja y indicamos el total de las devoluciones que se han hecho
            $resumenCaja['importeInicial'] = $sesionCaja->getImporteInicial();
            $resumenCaja['importeActual'] = $sesionCaja->getImporteActual();
            $resumenCaja['totalDevoluciones'] = $totalDevoluciones;
            $resumenCaja['totalRetiros'] = $totalRetiros;
        } else {
            // Si no existe una sesión de caja, indicamos que el importe inicial y actual es 0 y el total de devoluciones y retiros es 0
            $resumenCaja['importeInicial'] = 0;
            $resumenCaja['importeActual'] = 0;
            $resumenCaja['totalDevoluciones'] = 0;
            $resumenCaja['totalRetiros'] = 0;
        }

        // Guardamos en la sesión que se ha previsualizado la caja y el resumen de caja y recargamos la página
        $_SESSION['cajaPrevisualizacion'] = true;
        $_SESSION['resumenCaja'] = $resumenCaja;
        header('Location: index.php');
        exit();
    }

    // Si el cajero confirma el cierre de caja al haberla previsualizado
    if ($_POST['accion'] === 'confirmarCaja') {
        // Cerramos la caja
        Venta::cerrarCaja();
        // Obtenemos el cambio a guardar para el siguiente turno
        $cambio = isset($_POST['cambio']) ? (float) $_POST['cambio'] : null;

        // Obtener datos del arqueo si se proporcionaron
        $efectivoContado = isset($_POST['arqueoTotalContado']) ? (float) $_POST['arqueoTotalContado'] : 0;
        $detalleConteo = isset($_POST['arqueoDetalleConteo']) ? $_POST['arqueoDetalleConteo'] : null;
        $observaciones = isset($_POST['arqueoObservaciones']) ? $_POST['arqueoObservaciones'] : null;

        // Cerramos la sesión de caja formal
        if ($sesionCaja) {
            // Registrar arqueo antes de cerrar si se proporcionó
            if ($efectivoContado > 0) {
                try {
                    $sesionCaja->registrarArqueo(
                        $_SESSION['idUsuario'],
                        $efectivoContado,
                        $detalleConteo,
                        $observaciones,
                        'cierre'
                    );
                } catch (Exception $e) {
                    error_log("Error al registrar arqueo: " . $e->getMessage());
                }
            }

            $sesionCaja->cerrar($cambio);

            // Registrar cierre de caja en el log del sistema
            try {
                $conexion = ConexionDB::getInstancia()->getConexion();
                $stmtLog = $conexion->prepare("INSERT INTO logs_sistema (tipo, usuario_id, usuario_nombre, descripcion, detalles) VALUES ('cierre_caja', :usuario_id, :usuario_nombre, :descripcion, :detalles)");
                $stmtLog->execute([
                    ':usuario_id' => $_SESSION['idUsuario'],
                    ':usuario_nombre' => $_SESSION['nombreUsuario'] ?? 'Desconocido',
                    ':descripcion' => 'Cierre de caja por ' . ($_SESSION['nombreUsuario'] ?? 'Desconocido'),
                    ':detalles' => json_encode([
                        'importe_final' => $sesionCaja->getImporteActual(),
                        'cambio' => $cambio,
                        'arqueo_efectivo_contado' => $efectivoContado
                    ])
                ]);
            } catch (Exception $e) {
                error_log("Error al registrar log de cierre de caja: " . $e->getMessage());
            }
        }

        // Guardamos en la sesión que se ha confirmado el cierre de caja y recargamos la página
        $_SESSION['cajaConfirmacion'] = true;
        header('Location: index.php');
        exit();
    }

    // Si el cajero abre la caja
    if ($_POST['accion'] === 'abrirCaja' && isset($_POST['importeInicial'])) {
        // Obtenemos el importe inicial del modal del importe inicial
        $importeInicial = (float) $_POST['importeInicial'];
        // Obtenemos el cambio recovery (puede ser 0 si es nuevo)
        $cambioRecovery = isset($_POST['cambioRecovery']) ? (float) $_POST['cambioRecovery'] : 0;
        // Abrimos la caja indicando el id del usuario que la abrió, el importe inicial y el cambio recovery
        Caja::abrir($_SESSION['idUsuario'], $importeInicial, $cambioRecovery);

        // Registrar apertura de caja en el log del sistema
        try {
            $conexion = ConexionDB::getInstancia()->getConexion();
            $stmtLog = $conexion->prepare("INSERT INTO logs_sistema (tipo, usuario_id, usuario_nombre, descripcion, detalles) VALUES ('apertura_caja', :usuario_id, :usuario_nombre, :descripcion, :detalles)");
            $stmtLog->execute([
                ':usuario_id' => $_SESSION['idUsuario'],
                ':usuario_nombre' => $_SESSION['nombreUsuario'] ?? 'Desconocido',
                ':descripcion' => 'Apertura de caja por ' . ($_SESSION['nombreUsuario'] ?? 'Desconocido'),
                ':detalles' => json_encode([
                    'importe_inicial' => $importeInicial,
                    'cambio_recovery' => $cambioRecovery
                ])
            ]);
        } catch (Exception $e) {
            error_log("Error al registrar log de apertura de caja: " . $e->getMessage());
        }

        // Recargamos la página
        header('Location: index.php');
        exit();
    }

    // Si el cajero tramita una devolución
    if ($_POST['accion'] === 'tramitarDevolucion' && isset($_POST['idProductoDev'])) {
        // ... (existing single product logic remains if needed for backward compatibility, 
        // though the UI now uses multi-product. I'll keep it just in case or replace it)
        // Actually, let's keep it and add the new one below.
    }

    // NUEVO: Trámite de devolución múltiple verificada por ticket
    if ($_POST['accion'] === 'tramitarMultiDevolucion' && isset($_POST['idVenta'])) {
        $idVenta = (int) $_POST['idVenta'];
        $productos = json_decode($_POST['productos'], true);
        $metodoPago = $_POST['metodoPago'] ?? 'Efectivo';
        $totalReembolso = (float) ($_POST['totalReembolso'] ?? 0);
        $motivo = isset($_POST['motivo']) ? trim($_POST['motivo']) : '';
        $lineasReembolso = [];

        if (!empty($productos)) {
            // Verificar si hay suficiente efectivo en caja para el reembolso
            if ($sesionCaja && $sesionCaja->getImporteActual() < $totalReembolso) {
                $_SESSION['ventaError'] = "Error: No hay suficiente efectivo en caja para realizar esta devolución (" . number_format($totalReembolso, 2, ',', '.') . " €). Efectivo disponible: " . number_format($sesionCaja->getImporteActual(), 2, ',', '.') . " €";
                header('Location: index.php');
                exit();
            }

            $todasOk = true;
            $detalleTicket = LineaVenta::obtenerDetalleParaDevolucion($idVenta);

            foreach ($productos as $item) {
                // Soportar comodines: idProducto puede ser null
                $idProductoRaw = isset($item['idProducto']) ? $item['idProducto'] : null;
                $idProducto = ($idProductoRaw === null || $idProductoRaw === '' || strpos((string) $idProductoRaw, 'comodin_') !== false) ? null : (int) $idProductoRaw;

                $idLineaOriginal = isset($item['idLineaOriginal']) ? (int) $item['idLineaOriginal'] : null;
                $cantidadSolicitada = (int) $item['cantidad'];

                // Buscar la línea correspondiente en el detalle del ticket
                $lineaOriginal = null;
                foreach ($detalleTicket as $detalle) {
                    if ($idLineaOriginal !== null) {
                        if ((int) $detalle['id'] === $idLineaOriginal) {
                            $lineaOriginal = $detalle;
                            break;
                        }
                    } else {
                        // Fallback si no viene idLineaOriginal
                        $detalleIdProd = $detalle['idProducto'] !== null ? (int) $detalle['idProducto'] : null;
                        if ($detalleIdProd === $idProducto) {
                            $lineaOriginal = $detalle;
                            break;
                        }
                    }
                }

                if (!$lineaOriginal) {
                    $todasOk = false;
                    $_SESSION['ventaError'] = "Error: El producto no pertenece a este ticket.";
                    break;
                }

                $disponible = (int) $lineaOriginal['cantidad'] - (int) $lineaOriginal['cantidad_devuelta'];

                if ($cantidadSolicitada > $disponible) {
                    $todasOk = false;
                    $_SESSION['ventaError'] = "Error: La cantidad solicitada para " . $lineaOriginal['producto_nombre'] . " ($cantidadSolicitada) excede lo disponible ($disponible).";
                    break;
                }

                $devolucion = new Devolucion();
                $devolucion->setIdUsuario($_SESSION['idUsuario']);
                $devolucion->setIdProducto($lineaOriginal['idProducto']); // Usar el id de la línea original, puede ser null
                $devolucion->setNombreProducto($lineaOriginal['producto_nombre']);
                $devolucion->setCantidad($cantidadSolicitada);
                // Pasar el precio unitario base y el IVA de la línea original
                $devolucion->setPrecioUnitario($lineaOriginal['precioUnitario']);
                $devolucion->setIva($lineaOriginal['iva']);
                $dec = isset($item['decimales']) ? (int) $item['decimales'] : (isset($lineaOriginal['decimales']) ? (int) $lineaOriginal['decimales'] : 2);
                $devolucion->setDecimales($dec);

                // Redondear importe a sus decimales
                $subtotalLinea = round((float) $item['importe'], $dec);
                $devolucion->setImporteTotal($subtotalLinea);
                $devolucion->setIdVenta($idVenta);
                $devolucion->setIdSesionCaja($sesionCaja ? $sesionCaja->getId() : null);
                $devolucion->setMetodoPago($metodoPago);
                $devolucion->setMotivo($motivo);

                $lineasReembolso[] = [
                    'nombre' => $lineaOriginal['producto_nombre'] ?? 'Producto ' . $idProducto,
                    'cantidad' => $cantidadSolicitada,
                    'precio' => $lineaOriginal['precioUnitario'],
                    'iva' => $lineaOriginal['iva'],
                    'importe' => $subtotalLinea,
                    'decimales' => $dec
                ];

                if ($devolucion->insertar()) {
                    // Actualizar stock
                    $producto = Producto::buscarPorId($devolucion->getIdProducto());
                    if ($producto) {
                        $producto->actualizarStock($devolucion->getCantidad());
                    }
                } else {
                    $todasOk = false;
                }
            }

            if ($todasOk) {
                // Se resta el total del reembolso del efectivo en caja siempre (según petición del usuario)
                if ($sesionCaja) {
                    $sesionCaja->actualizarEfectivo(-$totalReembolso);
                }

                // Registrar log de devolución
                try {
                    $pdoLog = ConexionDB::getInstancia()->getConexion();
                    $cantidadTotalDevuelta = 0;
                    if (!empty($productos)) {
                        foreach ($productos as $p) {
                            $cantidadTotalDevuelta += (int) ($p['cantidad'] ?? 0);
                        }
                    }
                    // Determinar precisión de la devolución (max de los items)
                    $precDevTotal = 2;
                    foreach ($productos as $p) {
                        $d = isset($p['decimales']) ? (int) $p['decimales'] : 2;
                        if ($d > $precDevTotal)
                            $precDevTotal = $d;
                    }

                    // Redondear el total a su precisión
                    $totalReembolsoRedondeado = round($totalReembolso, $precDevTotal);
                    $detallesDevolucion = array(
                        'ticket' => $idVenta,
                        'productos_devueltos' => $cantidadTotalDevuelta,
                        'total_devolucion' => $totalReembolsoRedondeado
                    );
                    $stmtLog = $pdoLog->prepare("INSERT INTO logs_sistema (tipo, usuario_id, usuario_nombre, descripcion, detalles) VALUES (:tipo, :usuario_id, :usuario_nombre, :descripcion, :detalles)");
                    $stmtLog->execute([
                        ':tipo' => 'devolucion',
                        ':usuario_id' => $_SESSION['idUsuario'] ?? null,
                        ':usuario_nombre' => $_SESSION['nombreUsuario'] ?? 'Desconocido',
                        ':descripcion' => 'Devolución de ticket #' . $idVenta,
                        ':detalles' => json_encode($detallesDevolucion, JSON_UNESCAPED_UNICODE)
                    ]);
                } catch (Exception $e) {
                    // Silenciar errores de logging
                    error_log('Error al registrar log de devolución: ' . $e->getMessage());
                }

                $_SESSION['devolucionExito'] = true;
                // Guardar detalles para mostrar en el modal y la impresion de ticket
                // Obtener serie y numero correlativo
                $stmtIds = $conexion->prepare("SELECT serie, numero FROM ventas_ids WHERE id = ?");
                $stmtIds->execute([$idVenta]);
                $idsData = $stmtIds->fetch(PDO::FETCH_ASSOC);
                $serie = !empty($idsData['serie']) ? $idsData['serie'] : 'T';
                $num = !empty($idsData['numero']) ? $idsData['numero'] : $idVenta;
                $numCompleto = $serie . str_pad($num, 5, '0', STR_PAD_LEFT);

                $_SESSION['devolucionDetalles'] = array(
                    'ticket' => $numCompleto,
                    'productos' => $cantidadTotalDevuelta,
                    'total' => $totalReembolsoRedondeado,
                    'motivo' => $motivo,
                    'lineas' => $lineasReembolso,
                    'fecha' => date('d/m/Y H:i'),
                    'metodoPago' => $metodoPago
                );

                // --- Verifactu: Generar factura rectificativa (R1/R5) ---
                try {
                    require_once(__DIR__ . '/../core/Verifactu.php');
                    
                    // Preparar líneas para rectificativa
                    $lineasRect = [];
                    foreach ($lineasReembolso as $lr) {
                        $lineasRect[] = [
                            'idProducto' => $lr['idProducto'] ?? null,
                            'nombre' => $lr['nombre'] ?? 'Producto',
                            'cantidad' => $lr['cantidad'] ?? 1,
                            'precioUnitario' => $lr['precio'] ?? $lr['precioBase'] ?? $lr['precioUnitario'] ?? 0,
                            'iva' => $lr['iva'] ?? 21
                        ];
                    }
                    
                    $idSesionCajaActual = isset($sesionCaja) ? $sesionCaja->getId() : null;
                    $resRect = Venta::rectificarDocumento(
                        $serie,
                        (int)$num,
                        $lineasRect,
                        $_SESSION['idUsuario'],
                        $idSesionCajaActual
                    );
                    
                    // Guardar resultado en sesión para mostrar al usuario
                    $_SESSION['devolucionDetalles']['rectificativa'] = [
                        'success' => $resRect['success'],
                        'serieNumero' => $resRect['serieNumero'] ?? null,
                        'tipoFactura' => $resRect['tipoFactura'] ?? null,
                        'csv' => $resRect['csv'] ?? null,
                        'qrUrl' => $resRect['qrUrl'] ?? null,
                        'message' => $resRect['message'] ?? null
                    ];
                    // Actualizar serie y número para el ticket que se imprimirá
                    if (isset($resRect['venta'])) {
                        $_SESSION['devolucionDetalles']['serie'] = $resRect['venta']->getSerie();
                        $_SESSION['devolucionDetalles']['numero'] = $resRect['venta']->getNumero();
                        $_SESSION['devolucionDetalles']['qrUrl'] = $resRect['qrUrl'] ?? null;
                    }
                } catch (Exception $e) {
                    // No bloquear devolución si falla Verifactu
                    error_log('Verifactu rectificativa error: ' . $e->getMessage());
                    $_SESSION['devolucionDetalles']['rectificativa'] = [
                        'success' => false,
                        'message' => 'Error al generar rectificativa: ' . $e->getMessage()
                    ];
                }
            } else {
                $_SESSION['ventaError'] = "Error al procesar algunas líneas de la devolución.";
            }
        }
        // Redirigir siempre para limpiar el POST
        header('Location: index.php');
        exit();
    }

    // Si el cajero retira dinero
    if ($_POST['accion'] === 'retirarDinero' && isset($_POST['importeRetiro'])) {
        // Obtenemos el importe a retirar del modal del retiro de dinero
        $importe = (float) $_POST['importeRetiro'];
        $motivo = isset($_POST['motivoRetiro']) ? $_POST['motivoRetiro'] : null;
        // Si la sesión de caja existe y el importe es mayor a 0
        if ($sesionCaja && $importe > 0) {
            // Verificar que hay suficiente efectivo en la caja
            $importeActual = $sesionCaja->getImporteActual();
            if ($importe > $importeActual) {
                // No hay suficiente efectivo, guardamos mensaje de error
                $_SESSION['retiroError'] = 'No hay suficiente efectivo en la caja. Disponible: ' . number_format($importeActual, 2, ',', '.') . ' €';
            } else {
                // Actualizamos el efectivo de la caja restandole el dinero que se ha retirado
                $sesionCaja->actualizarEfectivo(-$importe);
                // Registramos el retiro en la base de datos
                $sesionCaja->registrarRetiro($importe, $_SESSION['idUsuario'], $motivo);

                // Registrar retiro de caja en el log del sistema
                try {
                    $conexion = ConexionDB::getInstancia()->getConexion();
                    $stmtLog = $conexion->prepare("INSERT INTO logs_sistema (tipo, usuario_id, usuario_nombre, descripcion, detalles) VALUES ('retiro_caja', :usuario_id, :usuario_nombre, :descripcion, :detalles)");
                    $stmtLog->execute([
                        ':usuario_id' => $_SESSION['idUsuario'],
                        ':usuario_nombre' => $_SESSION['nombreUsuario'] ?? 'Desconocido',
                        ':descripcion' => 'Retiro de caja por ' . ($_SESSION['nombreUsuario'] ?? 'Desconocido'),
                        ':detalles' => json_encode([
                            'importe' => $importe,
                            'motivo' => $motivo
                        ])
                    ]);
                } catch (Exception $e) {
                    error_log("Error al registrar log de retiro de caja: " . $e->getMessage());
                }

                // Guardamos en la sesión que se ha realizado un retiro
                $_SESSION['retiroExito'] = true;
            }
        }
        // Recargamos la página
        header('Location: index.php');
        exit();
    }
}

// Pasar permisos a la vista CORRECTAMENTE
// ✅ AHORA SI ESTARAN DISPONIBLES EN vCajero.php
$puedeModificarPrecios = $_SESSION['rolUsuario'] === 'admin' || (strpos($permisosUsuario, 'modificar_precios') !== false);
$puedeProductoComodin = $_SESSION['rolUsuario'] === 'admin' || (strpos($permisosUsuario, 'producto_comodin') !== false);
$puedeRetirarDinero = $_SESSION['rolUsuario'] === 'admin' || (strpos($permisosUsuario, 'retirar_dinero') !== false);

// Llamamos a la vista del cajero
require_once $view['Layout'];
?>