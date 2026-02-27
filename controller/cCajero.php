<?php
/*
 * Autor: Alberto Méndez 
 * Fecha de actualización: 25/02/2026
 * 
 * Controlador del cajero. Gestiona la vista del TPV para empleados.
 */

require_once(__DIR__ . '/../model/Producto.php');
require_once(__DIR__ . '/../model/Categoria.php');
require_once(__DIR__ . '/../model/Venta.php');
require_once(__DIR__ . '/../model/LineaVenta.php');
require_once(__DIR__ . '/../model/Caja.php');

//Verificar que el usuario está autenticado.
if (!isset($_SESSION['idUsuario'])) {
    $_SESSION['paginaEnCurso'] = 'login';
    header('Location: index.php');
    exit();
}

if (isset($_REQUEST['cerrarSesion'])) {
    $_SESSION['paginaEnCurso'] = 'login';
    header('Location: index.php');
    exit();
}

//Cargar categorías para el menú lateral.
$categorias = Categoria::obtenerTodas();

// Cargar estado de la caja
$sesionCaja = Caja::obtenerSesionAbierta();

//Los productos se cargan por AJAX desde api/productos.php, pero cargamos una lista inicial para la primera vista.
$idCategoriaSeleccionada = null;
$productos = Producto::obtenerTodos();

//Procesar nueva venta.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {

    if ($_POST['accion'] === 'registrarVenta' && isset($_POST['carrito'])) {
        $carrito = json_decode($_POST['carrito'], true);

        if (!empty($carrito)) {
            $venta = new Venta();
            $venta->setIdUsuario($_SESSION['idUsuario']);
            $venta->setFecha(date('Y-m-d H:i:s'));
            $venta->setMetodoPago($_POST['metodoPago'] ?? 'efectivo');
            $venta->setEstado('completada');
            $venta->setTipoDocumento($_POST['tipoDocumento'] ?? 'ticket');

            //Calcular total y validar stock.
            $total = 0;
            $stockValido = true;
            foreach ($carrito as $item) {
                $producto = Producto::buscarPorId($item['idProducto']);
                if ($producto && $producto->getStock() < $item['cantidad']) {
                    $stockValido = false;
                    break;
                }
                $total += $item['precio'] * $item['cantidad'];
            }

            if (!$stockValido) {
                $_SESSION['ventaError'] = 'No hay suficiente stock para uno o más productos.';
                header('Location: index.php');
                exit();
            }

            // Aplicar descuento al total antes de guardar la venta
            $descuentoTipo = $_POST['descuentoTipo'] ?? 'ninguno';
            $descuentoValor = (float) ($_POST['descuentoValor'] ?? 0);
            $importeDescuento = 0;
            if ($descuentoTipo === 'porcentaje') {
                $importeDescuento = $total * ($descuentoValor / 100);
            } elseif ($descuentoTipo === 'fijo') {
                $importeDescuento = $descuentoValor;
            }
            $total = max(0, $total - $importeDescuento);

            if ($venta->getMetodoPago() === 'efectivo' && $total > 1000) {
                $_SESSION['ventaError'] = "No se permite el pago en efectivo para importes superiores a 1.000€.";
                header('Location: index.php');
                exit();
            }

            $venta->setTotal($total);

            // Si el pago es en efectivo, guardar datos extras para el control de caja
            if (($venta->getMetodoPago() === 'efectivo')) {
                $entregado = (float) ($_POST['dineroEntregado'] ?? $total);
                $cambio = (float) ($_POST['cambioDevuelto'] ?? 0);
                $venta->setImporteEntregado($entregado);
                $venta->setCambioDevuelto($cambio);

                // Actualizar importe en la sesión de caja si está abierta
                if ($sesionCaja) {
                    $sesionCaja->actualizarEfectivo($entregado - $cambio);
                }
            }

            $venta->insertar();

            //Insertar líneas de venta y actualizar stock.
            $lineasVenta = [];
            foreach ($carrito as $item) {
                $linea = new LineaVenta();
                $linea->setIdVenta($venta->getId());
                $linea->setIdProducto($item['idProducto']);
                $linea->setCantidad($item['cantidad']);
                $linea->setPrecioUnitario($item['precio']);
                $linea->insertar();

                //Descontar stock (se clampa a 0 automáticamente en el modelo).
                $producto = Producto::buscarPorId($item['idProducto']);
                if ($producto) {
                    $producto->actualizarStock(-$item['cantidad']);
                }

                $lineasVenta[] = [
                    'nombre' => $item['nombre'],
                    'cantidad' => $item['cantidad'],
                    'precio' => $item['precio']
                ];
            }

            $_SESSION['ventaExito'] = true;
            $_SESSION['ultimaVentaId'] = $venta->getId();
            $_SESSION['ultimaVentaTotal'] = $total;
            $_SESSION['ultimaVentaTipo'] = $_POST['tipoDocumento'] ?? 'ticket';
            $_SESSION['ultimaVentaCarrito'] = json_encode($lineasVenta);
            $_SESSION['ultimaVentaMetodoPago'] = $_POST['metodoPago'] ?? 'efectivo';
            $_SESSION['ultimaVentaFecha'] = date('d/m/Y H:i');
            $_SESSION['ultimaVentaEntregado'] = $_POST['dineroEntregado'] ?? $total;
            $_SESSION['ultimaVentaCambio'] = $_POST['cambioDevuelto'] ?? 0;

            // Datos de Descuento
            $_SESSION['ultimaVentaDescuentoTipo'] = $_POST['descuentoTipo'] ?? 'ninguno';
            $_SESSION['ultimaVentaDescuentoValor'] = $_POST['descuentoValor'] ?? 0;
            $_SESSION['ultimaVentaDescuentoCupon'] = $_POST['descuentoCupon'] ?? '';

            // Datos del Cliente
            $_SESSION['ultimaVentaClienteNif'] = $_POST['clienteNif'] ?? '';
            $_SESSION['ultimaVentaClienteNombre'] = $_POST['clienteNombre'] ?? '';
            $_SESSION['ultimaVentaClienteDir'] = $_POST['clienteDireccion'] ?? '';
            $_SESSION['ultimaVentaClienteObs'] = $_POST['observaciones'] ?? '';
            header('Location: index.php');
            exit();
        }
    }

    require_once(__DIR__ . '/../model/Devolucion.php');

    if ($_POST['accion'] === 'previsualizarCaja') {
        $resumenCaja = Venta::obtenerResumenCajaAbierta();

        // Añadir datos de la sesión de caja si existe
        if ($sesionCaja) {
            $idSesion = $sesionCaja->getId();
            $totalDevoluciones = Devolucion::obtenerTotalPorSesion($idSesion);

            $resumenCaja['importeInicial'] = $sesionCaja->getImporteInicial();
            $resumenCaja['importeActual'] = $sesionCaja->getImporteActual();
            $resumenCaja['totalDevoluciones'] = $totalDevoluciones;
        } else {
            $resumenCaja['importeInicial'] = 0;
            $resumenCaja['importeActual'] = 0;
            $resumenCaja['totalDevoluciones'] = 0;
        }

        $_SESSION['cajaPrevisualizacion'] = true;
        $_SESSION['resumenCaja'] = $resumenCaja;
        header('Location: index.php');
        exit();
    }

    if ($_POST['accion'] === 'confirmarCaja') {
        Venta::cerrarCaja();
        // Cerrar también la sesión de caja formal
        if ($sesionCaja) {
            $sesionCaja->cerrar();
        }
        $_SESSION['cajaConfirmacion'] = true;
        header('Location: index.php');
        exit();
    }

    if ($_POST['accion'] === 'abrirCaja' && isset($_POST['importeInicial'])) {
        $importeInicial = (float) $_POST['importeInicial'];
        Caja::abrir($_SESSION['idUsuario'], $importeInicial);
        header('Location: index.php');
        exit();
    }

    if ($_POST['accion'] === 'tramitarDevolucion' && isset($_POST['idProductoDev'])) {
        $devolucion = new Devolucion();
        $devolucion->setIdUsuario($_SESSION['idUsuario']);
        $devolucion->setIdProducto((int) $_POST['idProductoDev']);
        $devolucion->setCantidad((int) $_POST['cantidadDev']);
        $devolucion->setImporteTotal((float) $_POST['importeTotalDev']);
        $devolucion->setIdSesionCaja($sesionCaja ? $sesionCaja->getId() : null);
        $devolucion->setMetodoPago($_POST['metodoPagoDev'] ?? 'Efectivo');

        if ($devolucion->insertar()) {
            // Solo restar de la caja si es efectivo
            if ($sesionCaja && $devolucion->getMetodoPago() === 'Efectivo') {
                $sesionCaja->actualizarEfectivo(-$devolucion->getImporteTotal());
            }

            // Sumar al stock del producto
            $producto = Producto::buscarPorId($devolucion->getIdProducto());
            if ($producto) {
                $producto->actualizarStock($devolucion->getCantidad());
            }

            $_SESSION['ventaExito'] = false;
            $_SESSION['devolucionExito'] = true;
        }
        header('Location: index.php');
        exit();
    }

    if ($_POST['accion'] === 'retirarDinero' && isset($_POST['importeRetiro'])) {
        $importe = (float) $_POST['importeRetiro'];
        if ($sesionCaja && $importe > 0) {
            $sesionCaja->actualizarEfectivo(-$importe);
            $_SESSION['retiroExito'] = true;
        }
        header('Location: index.php');
        exit();
    }
}

require_once $view['Layout'];
?>