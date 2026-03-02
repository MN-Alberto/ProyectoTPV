<?php
/**
 * Controlador del cajero. Gestiona la vista del TPV para empleados.
 * 
 * @author Alberto Méndez
 * @version 1.6 (02/03/2026)
 */

// Requerimos los modelos necesarios
require_once(__DIR__ . '/../model/Producto.php');
require_once(__DIR__ . '/../model/Categoria.php');
require_once(__DIR__ . '/../model/Venta.php');
require_once(__DIR__ . '/../model/LineaVenta.php');
require_once(__DIR__ . '/../model/Caja.php');

// Verificamos que el usuario está guardado en la sesión, si no lo está redirigimos al login
if (!isset($_SESSION['idUsuario'])) {
    $_SESSION['paginaEnCurso'] = 'login';
    header('Location: index.php');
    exit();
}

// Si el usuario solicita cerrar sesión redirigimos al login
if (isset($_REQUEST['cerrarSesion'])) {
    $_SESSION['paginaEnCurso'] = 'login';
    header('Location: index.php');
    exit();
}

// Cargar categorías para el menú
$categorias = Categoria::obtenerTodas();

// Cargar estado de la caja
$sesionCaja = Caja::obtenerSesionAbierta();

// Los productos se cargan por AJAX desde api/productos.php, pero cargamos una lista inicial para la primera vista
$idCategoriaSeleccionada = null;
$productos = Producto::obtenerTodos();

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

            //Calculamos el total y validamos el stock.
            $total = 0;
            $stockValido = true;
            // Recorremos los productos del carrito para validar el stock y calcular el total
            foreach ($carrito as $item) {
                // Buscamos el producto por id
                $producto = Producto::buscarPorId($item['idProducto']);
                // Si el producto no existe o no hay stock suficiente
                if ($producto && $producto->getStock() < $item['cantidad']) {
                    $stockValido = false;
                    break;
                }
                // Sumamos el total
                $total += $item['precio'] * $item['cantidad'];
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
            $importeDescuento = 0;
            // Si el descuento es en porcentaje
            if ($descuentoTipo === 'porcentaje') {
                // Calculamos el importe del descuento
                $importeDescuento = $total * ($descuentoValor / 100);
            } elseif ($descuentoTipo === 'fijo') {
                // Si el descuento es fijo
                $importeDescuento = $descuentoValor;
            }
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
            $venta->insertar();

            // Insertamos las líneas de venta y actualizamos el stock
            $lineasVenta = [];
            // Recorremos los productos del carrito
            foreach ($carrito as $item) {
                // Creamos una nueva línea de venta
                $linea = new LineaVenta();
                // Indicamos el id de la venta
                $linea->setIdVenta($venta->getId());
                // Indicamos el id del producto
                $linea->setIdProducto($item['idProducto']);
                // Indicamos la cantidad
                $linea->setCantidad($item['cantidad']);
                // Indicamos el precio unitario
                $linea->setPrecioUnitario($item['precio']);
                // Insertamos la línea de venta
                $linea->insertar();

                // Buscamos el producto por id
                $producto = Producto::buscarPorId($item['idProducto']);
                // Si el producto existe
                if ($producto) {
                    // Actualizamos el stock
                    $producto->actualizarStock(-$item['cantidad']);
                }

                // Guardamos la línea de venta
                $lineasVenta[] = [
                    'nombre' => $item['nombre'],
                    'cantidad' => $item['cantidad'],
                    'precio' => $item['precio']
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

            // Obtenemos el importe inicial y actual de la sesión de caja y indicamos el total de las devoluciones que se han hecho
            $resumenCaja['importeInicial'] = $sesionCaja->getImporteInicial();
            $resumenCaja['importeActual'] = $sesionCaja->getImporteActual();
            $resumenCaja['totalDevoluciones'] = $totalDevoluciones;
        } else {
            // Si no existe una sesión de caja, indicamos que el importe inicial y actual es 0 y el total de devoluciones es 0
            $resumenCaja['importeInicial'] = 0;
            $resumenCaja['importeActual'] = 0;
            $resumenCaja['totalDevoluciones'] = 0;
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
        // Cerramos la sesión de caja formal
        if ($sesionCaja) {
            $sesionCaja->cerrar();
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
        // Abrimos la caja indicando el id del usuario que la abrió y el importe inicial con el que se abrió
        Caja::abrir($_SESSION['idUsuario'], $importeInicial);
        // Recargamos la página
        header('Location: index.php');
        exit();
    }

    // Si el cajero tramita una devolución
    if ($_POST['accion'] === 'tramitarDevolucion' && isset($_POST['idProductoDev'])) {
        // Creamos una nueva devolución
        $devolucion = new Devolucion();
        // Indicamos el id del usuario que la tramita
        $devolucion->setIdUsuario($_SESSION['idUsuario']);
        // Indicamos el id del producto que se devuelve
        $devolucion->setIdProducto((int) $_POST['idProductoDev']);
        // Indicamos la cantidad que se devuelve
        $devolucion->setCantidad((int) $_POST['cantidadDev']);
        // Indicamos el importe total de la devolución
        $devolucion->setImporteTotal((float) $_POST['importeTotalDev']);
        // Indicamos el id de la sesión de caja
        $devolucion->setIdSesionCaja($sesionCaja ? $sesionCaja->getId() : null);
        // Indicamos el método de pago
        $devolucion->setMetodoPago($_POST['metodoPagoDev'] ?? 'Efectivo');

        // Insertamos la devolución
        if ($devolucion->insertar()) {
            // Si la devolución es en efectivo, restamos el importe total de la devolución de la caja
            if ($sesionCaja && $devolucion->getMetodoPago() === 'Efectivo') {
                $sesionCaja->actualizarEfectivo(-$devolucion->getImporteTotal());
            }

            // Buscamos el producto por id
            $producto = Producto::buscarPorId($devolucion->getIdProducto());
            // Si el producto existe
            if ($producto) {
                // Sumamos la cantidad devuelta al stock del producto
                $producto->actualizarStock($devolucion->getCantidad());
            }

            // Guardamos en la sesión que se ha realizado una devolución
            $_SESSION['ventaExito'] = false;
            $_SESSION['devolucionExito'] = true;
        }
        // Recargamos la página
        header('Location: index.php');
        exit();
    }

    // Si el cajero retira dinero
    if ($_POST['accion'] === 'retirarDinero' && isset($_POST['importeRetiro'])) {
        // Obtenemos el importe a retirar del modal del retiro de dinero
        $importe = (float) $_POST['importeRetiro'];
        // Si la sesión de caja existe y el importe es mayor a 0
        if ($sesionCaja && $importe > 0) {
            // Actualizamos el efectivo de la caja restandole el dinero que se ha retirado
            $sesionCaja->actualizarEfectivo(-$importe);
            // Guardamos en la sesión que se ha realizado un retiro
            $_SESSION['retiroExito'] = true;
        }
        // Recargamos la página
        header('Location: index.php');
        exit();
    }
}

// Llamamos a la vista del cajero
require_once $view['Layout'];
?>