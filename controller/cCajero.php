<?php
/**
 * Controlador del cajero. Gestiona la vista del TPV para empleados.
 * 
 * @author Alberto Méndez
 * @version 1.7 (09/03/2026)
 */

// Requerimos los modelos necesarios
require_once(__DIR__ . '/../model/Producto.php');
require_once(__DIR__ . '/../model/Categoria.php');
require_once(__DIR__ . '/../model/Venta.php');
require_once(__DIR__ . '/../model/LineaVenta.php');
require_once(__DIR__ . '/../model/Caja.php');
require_once(__DIR__ . '/../model/Usuario.php');
require_once(__DIR__ . '/../core/conexionDB.php');

// Verificamos que el usuario está guardado en la sesión, si no lo está redirigimos al login
if (!isset($_SESSION['idUsuario'])) {
    $_SESSION['paginaEnCurso'] = 'login';
    header('Location: index.php');
    exit();
}

// Si el usuario solicita cerrar sesión destruimos la sesión completamente
if (isset($_REQUEST['cerrarSesion'])) {
    // Si se especificó un motivo (pausa o turno), lo registramos en la caja abierta
    if (isset($_REQUEST['motivoCierre'])) {
        $sesionCaja = Caja::obtenerSesionAbierta();
        if ($sesionCaja) {
            $sesionCaja->registrarInterrupcion(
                $_SESSION['idUsuario'],
                $_SESSION['nombreUsuario'] ?? 'Desconocido',
                $_REQUEST['motivoCierre']
            );
        }

        // Incrementar contadores de usuario
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

// Comprobar si hay una interrupción previa para mostrar el modal de bienvenida
if ($sesionCaja && $sesionCaja->getInterrupcionTipo()) {
    // Preparamos los datos para que la vista los use
    $_SESSION['interrupcionRecuperada'] = [
        'tipo' => $sesionCaja->getInterrupcionTipo(),
        'usuarioId' => $sesionCaja->getInterrupcionUsuarioId(),
        'usuarioNombre' => $sesionCaja->getInterrupcionUsuarioNombre(),
        'fecha' => $sesionCaja->getInterrupcionFecha()
    ];
    // Limpiar en BD de inmediato para que sea de un solo uso (evita que reaparezca en cada carga/venta)
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

            // Relacionar con la sesión de caja abierta si existe
            if ($sesionCaja) {
                $venta->setIdSesionCaja($sesionCaja->getId());
            }

            //Calculamos el total y validamos el stock.
            $total = 0;
            $stockValido = true;
            // Recorremos los productos del carrito para validar el stock y calcular el total con IVA
            foreach ($carrito as $item) {
                // Buscamos el producto por id para asegurar datos frescos
                $producto = Producto::buscarPorId($item['idProducto']);

                // Si el producto no existe o no hay stock suficiente
                if (!$producto || $producto->getStock() < $item['cantidad']) {
                    $stockValido = false;
                    break;
                }

                // Usar el PVP unitario redondeado enviado desde el carrito (garantiza precisión financiera)
                // Si por algún motivo no viniera, lo calculamos como fallback
                $precioUnitarioConIva = isset($item['pvpUnitario']) ? (float)$item['pvpUnitario'] : (float)$item['precio'] * (1 + ($producto->getIvaPorcentaje() / 100));

                // Sumamos al total acumulado redondeado a 2 decimales para evitar arrastre de coma flotante
                $total += round($precioUnitarioConIva * $item['cantidad'], 2);
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

            // Descuento total (Ya no hay importeDescuentoTarifa global porque es por producto)
            $importeDescuento = $importeDescuentoManual;

            // Restamos el descuento al total
            $total = max(0, $total - $importeDescuento);

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
            }

            // Insertamos la venta
            // Guardamos el DNI del cliente en la venta
            $clienteNif = $_POST['clienteNif'] ?? '';
            error_log("Cliente NIF recibido: '" . $clienteNif . "'");
            $venta->setClienteDni($clienteNif);
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
                    // Actualizar productos comprados y ventas realizadas del cliente
                    $stmtCliente = $conexion->prepare(
                        "UPDATE clientes SET 
                            productos_comprados = productos_comprados + ?, 
                            compras_realizadas = compras_realizadas + 1 
                        WHERE dni = ? AND activo = 1"
                    );
                    $stmtCliente->execute([$totalProductos, $clienteNif]);
                } catch (Exception $e) {
                    // Si falla la actualización del cliente, no detenemos la venta
                    error_log("Error al actualizar estadísticas del cliente: " . $e->getMessage());
                }
            }

            // Insertamos las líneas de venta y actualizamos el stock
            $lineasVenta = [];
            // Recorremos los productos del carrito
            foreach ($carrito as $item) {
                // Re-buscamos el producto para obtener su IVA y stock individual
                $producto = Producto::buscarPorId($item['idProducto']);
                // Creamos una nueva línea de venta
                $linea = new LineaVenta();
                // Indicamos el id de la venta
                $linea->setIdVenta($venta->getId());
                // Indicamos el id del producto
                $linea->setIdProducto($item['idProducto']);
                // Indicamos la cantidad
                $linea->setCantidad($item['cantidad']);
                // Indicamos el precio unitario (Base sin IVA)
                // Lo calculamos a partir del PVP unitario redondeado para mantener consistencia perfecta
                $pvpUnitarioItem = isset($item['pvpUnitario']) ? (float)$item['pvpUnitario'] : null;
                $ivaItem = $producto ? (float)$producto->getIvaPorcentaje() : 21;
                
                if ($pvpUnitarioItem !== null) {
                    $precioBaseCalculado = $pvpUnitarioItem / (1 + ($ivaItem / 100));
                } else {
                    $precioBaseCalculado = isset($item['precio']) ? (float)$item['precio'] : 0;
                }
                
                $linea->setPrecioUnitario($precioBaseCalculado);
                
                // Calculamos el precio original base (sin IVA) para guardarlo
                $pvpOriginalUnitario = isset($item['pvpOriginalUnitario']) ? (float)$item['pvpOriginalUnitario'] : $pvpUnitarioItem;
                $precioOriginalBase = $pvpOriginalUnitario / (1 + ($ivaItem / 100));
                $linea->setPrecioOriginal($precioOriginalBase);
                
                // Guardamos el nombre de la tarifa aplicada
                $linea->setTarifaNombre($item['tarifaNombre'] ?? null);
                
                // Indicamos el IVA aplicado
                $linea->setIva($producto ? $producto->getIvaPorcentaje() : 21);
                // Insertamos la línea de venta
                $linea->insertar();

                // Si el producto existe
                if ($producto) {
                    // Actualizamos el stock
                    $producto->actualizarStock(-$item['cantidad']);
                }

                // Guardamos la línea de venta (incluyendo pvpUnitario y pvpOriginal para impresión exacta)
                $lineasVenta[] = [
                    'nombre' => $item['nombre'],
                    'cantidad' => $item['cantidad'],
                    'precio' => $precioBaseCalculado,
                    'pvpUnitario' => $pvpUnitarioItem,
                    'pvpOriginalUnitario' => isset($item['pvpOriginalUnitario']) ? (float)$item['pvpOriginalUnitario'] : $pvpUnitarioItem,
                    'tarifaNombre' => $item['tarifaNombre'] ?? 'Tarifa',
                    'iva' => $ivaItem
                ];
            }

            // Guardamos los datos de la venta en la sesión
            $_SESSION['ventaExito'] = true;
            $_SESSION['ultimaVentaId'] = $venta->getId();
            $_SESSION['ultimaVentaTotal'] = $total;
            $_SESSION['ultimaVentaTipo'] = $_POST['tipoDocumento'] ?? 'ticket';
            $_SESSION['ultimaVentaCarrito'] = json_encode($lineasVenta);
            $_SESSION['ultimaVentaMetodoPago'] = $_POST['metodoPago'] ?? 'efectivo';
            $_SESSION['ultimaVentaFecha'] = date('d/m/Y H:i');
            $_SESSION['ultimaVentaEntregado'] = $_POST['dineroEntregado'] ?? $total;
            $_SESSION['ultimaVentaCambio'] = $_POST['cambioDevuelto'] ?? 0;

            // Guardamos los datos del descuento en la sesión
            $_SESSION['ultimaVentaDescuentoTipo'] = $_POST['descuentoTipo'] ?? 'ninguno';
            $_SESSION['ultimaVentaDescuentoValor'] = $_POST['descuentoValor'] ?? 0;
            $_SESSION['ultimaVentaDescuentoCupon'] = $_POST['descuentoCupon'] ?? '';

            // Guardamos los datos del descuento de tarifa (cliente registrado, mayorista)
            $_SESSION['ultimaVentaDescuentoTarifaTipo'] = 'ninguno';
            $_SESSION['ultimaVentaDescuentoTarifaValor'] = 0;
            $_SESSION['ultimaVentaDescuentoTarifaCupon'] = '';

            // Guardamos los datos del descuento manual (código promocional)
            $_SESSION['ultimaVentaDescuentoManualTipo'] = $_POST['descuentoManualTipo'] ?? 'ninguno';
            $_SESSION['ultimaVentaDescuentoManualValor'] = $_POST['descuentoManualValor'] ?? 0;
            $_SESSION['ultimaVentaDescuentoManualCupon'] = $_POST['descuentoManualCupon'] ?? '';

            // Guardamos los datos del cliente en la sesión
            $_SESSION['ultimaVentaClienteNif'] = $_POST['clienteNif'] ?? '';
            $_SESSION['ultimaVentaClienteNombre'] = $_POST['clienteNombre'] ?? '';
            $_SESSION['ultimaVentaClienteDir'] = $_POST['clienteDireccion'] ?? '';
            $_SESSION['ultimaVentaClienteObs'] = $_POST['observaciones'] ?? '';
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
                $idProducto = (int) $item['idProducto'];
                $cantidadSolicitada = (int) $item['cantidad'];

                // Buscar la línea correspondiente en el detalle del ticket
                $lineaOriginal = null;
                foreach ($detalleTicket as $detalle) {
                    if ((int) $detalle['idProducto'] === $idProducto) {
                        $lineaOriginal = $detalle;
                        break;
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
                $devolucion->setIdProducto($idProducto);
                $devolucion->setCantidad($cantidadSolicitada);
                // Redondear importe a 2 decimales
                $devolucion->setImporteTotal(round((float) $item['importe'], 2));
                $devolucion->setIdVenta($idVenta);
                $devolucion->setIdSesionCaja($sesionCaja ? $sesionCaja->getId() : null);
                $devolucion->setMetodoPago($metodoPago);

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
                    // Redondear el total a 2 decimales
                    $totalReembolsoRedondeado = round($totalReembolso, 2);
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
                // Guardar detalles para mostrar en el modal - usar el total reales procesado
                $_SESSION['devolucionDetalles'] = array(
                    'ticket' => $idVenta,
                    'productos' => $cantidadTotalDevuelta,
                    'total' => $totalReembolsoRedondeado
                );
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

// Llamamos a la vista del cajero
require_once $view['Layout'];
?>