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

            $venta->setTotal($total);
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

    if ($_POST['accion'] === 'previsualizarCaja') {
        $resumenCaja = Venta::obtenerResumenCajaAbierta();
        $_SESSION['cajaPrevisualizacion'] = true;
        $_SESSION['resumenCaja'] = $resumenCaja;
        header('Location: index.php');
        exit();
    }

    if ($_POST['accion'] === 'confirmarCaja') {
        Venta::cerrarCaja();
        $_SESSION['cajaConfirmacion'] = true;
        header('Location: index.php');
        exit();
    }
}

require_once $view['Layout'];
?>