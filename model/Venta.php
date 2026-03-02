<?php

/**
 * Clase modelo para la gestión de ventas/tickets del TPV.
 * 
 * @author Alberto Méndez
 * @version 1.2 (02/03/2026)
 */

// Requerimos el fichero de conexión a la base de datos
require_once(__DIR__ . '/../core/conexionDB.php');

class Venta
{

    private $id;
    private $idUsuario;
    private $fecha;
    private $total;
    private $metodoPago; // 'efectivo', 'tarjeta', 'bizum'
    private $estado; // 'completada', 'anulada'
    private $tipoDocumento; // 'ticket', 'factura'
    private $cerrada; // 0 o 1
    private $importeEntregado;
    private $cambioDevuelto;

    // ======================== GETTERS ========================

    public function getId()
    {
        return $this->id;
    }

    public function getIdUsuario()
    {
        return $this->idUsuario;
    }

    public function getFecha()
    {
        return $this->fecha;
    }

    public function getTotal()
    {
        return $this->total;
    }

    public function getMetodoPago()
    {
        return $this->metodoPago;
    }

    public function getEstado()
    {
        return $this->estado;
    }

    public function getTipoDocumento()
    {
        return $this->tipoDocumento;
    }

    public function getCerrada()
    {
        return $this->cerrada;
    }

    // ======================== SETTERS ========================

    public function setId($id)
    {
        $this->id = $id;
    }

    public function setIdUsuario($idUsuario)
    {
        $this->idUsuario = $idUsuario;
    }

    public function setFecha($fecha)
    {
        $this->fecha = $fecha;
    }

    public function setTotal($total)
    {
        $this->total = $total;
    }

    public function setMetodoPago($metodoPago)
    {
        $this->metodoPago = $metodoPago;
    }

    public function setEstado($estado)
    {
        $this->estado = $estado;
    }

    public function setTipoDocumento($tipoDocumento)
    {
        $this->tipoDocumento = $tipoDocumento;
    }

    public function setCerrada($cerrada)
    {
        $this->cerrada = $cerrada;
    }

    public function getImporteEntregado()
    {
        return $this->importeEntregado;
    }
    public function setImporteEntregado($importeEntregado)
    {
        $this->importeEntregado = $importeEntregado;
    }

    public function getCambioDevuelto()
    {
        return $this->cambioDevuelto;
    }
    public function setCambioDevuelto($cambioDevuelto)
    {
        $this->cambioDevuelto = $cambioDevuelto;
    }

    // ======================== MÉTODOS CRUD ========================

    /**
     * Busca una venta por su ID.
     * @param int $id
     * @return Venta|null
     */
    public static function buscarPorId($id)
    {
        // Obtenemos la instancia de la conexión
        $conexion = ConexionDB::getInstancia()->getConexion();
        // Preparamos la consulta
        $stmt = $conexion->prepare("SELECT * FROM ventas WHERE id = :id");
        // Vinculamos los parámetros
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        // Ejecutamos la consulta
        $stmt->execute();
        // Obtenemos la fila
        $fila = $stmt->fetch();
        // Si se encuentra la fila, creamos una nueva venta
        if ($fila) {
            return self::crearDesdeArray($fila);
        }
        // Si no se encuentra la fila, devolvemos null
        return null;
    }

    /**
     * Obtiene todas las ventas.
     * @return array
     */
    public static function obtenerTodas()
    {
        // Obtenemos la instancia de la conexión
        $conexion = ConexionDB::getInstancia()->getConexion();
        // Preparamos la consulta
        $stmt = $conexion->query("SELECT * FROM ventas ORDER BY fecha DESC");
        // Creamos un array para guardar las ventas
        $ventas = [];
        // Recorremos las filas
        while ($fila = $stmt->fetch()) {
            // Creamos una nueva venta y la añadimos al array
            $ventas[] = self::crearDesdeArray($fila);
        }
        // Devolvemos las ventas
        return $ventas;
    }

    /**
     * Obtiene las ventas de un usuario concreto.
     * @param int $idUsuario
     * @return array
     */
    public static function obtenerPorUsuario($idUsuario)
    {
        // Obtenemos la instancia de la conexión
        $conexion = ConexionDB::getInstancia()->getConexion();
        // Preparamos la consulta
        $stmt = $conexion->prepare(
            "SELECT * FROM ventas WHERE idUsuario = :idUsuario ORDER BY fecha DESC"
        );
        // Vinculamos los parámetros
        $stmt->bindParam(':idUsuario', $idUsuario, PDO::PARAM_INT);
        // Ejecutamos la consulta
        $stmt->execute();
        // Creamos un array para guardar las ventas
        $ventas = [];
        // Recorremos las filas
        while ($fila = $stmt->fetch()) {
            // Creamos una nueva venta y la añadimos al array
            $ventas[] = self::crearDesdeArray($fila);
        }
        // Devolvemos las ventas
        return $ventas;
    }

    /**
     * Obtiene las ventas entre dos fechas.
     * @param string $fechaInicio
     * @param string $fechaFin
     * @return array
     */
    public static function obtenerPorFechas($fechaInicio, $fechaFin)
    {
        // Obtenemos la instancia de la conexión
        $conexion = ConexionDB::getInstancia()->getConexion();
        // Preparamos la consulta
        $stmt = $conexion->prepare(
            "SELECT * FROM ventas WHERE fecha BETWEEN :fechaInicio AND :fechaFin ORDER BY fecha DESC"
        );
        // Vinculamos los parámetros
        $stmt->bindParam(':fechaInicio', $fechaInicio);
        $stmt->bindParam(':fechaFin', $fechaFin);
        // Ejecutamos la consulta
        $stmt->execute();
        // Creamos un array para guardar las ventas
        $ventas = [];
        // Recorremos las filas
        while ($fila = $stmt->fetch()) {
            // Creamos una nueva venta y la añadimos al array
            $ventas[] = self::crearDesdeArray($fila);
        }
        // Devolvemos las ventas
        return $ventas;
    }

    /**
     * Inserta una nueva venta en la base de datos.
     * @return bool
     */
    public function insertar()
    {
        // Obtenemos la instancia de la conexión
        $conexion = ConexionDB::getInstancia()->getConexion();
        // Preparamos la consulta
        $stmt = $conexion->prepare(
            "INSERT INTO ventas (idUsuario, fecha, total, metodoPago, estado, tipoDocumento, cerrada, importeEntregado, cambioDevuelto) 
             VALUES (:idUsuario, :fecha, :total, :metodoPago, :estado, :tipoDocumento, :cerrada, :importeEntregado, :cambioDevuelto)"
        );
        // Vinculamos los parámetros
        $stmt->bindParam(':idUsuario', $this->idUsuario, PDO::PARAM_INT);
        $stmt->bindParam(':fecha', $this->fecha);
        $stmt->bindParam(':total', $this->total);
        $stmt->bindParam(':metodoPago', $this->metodoPago);
        $stmt->bindParam(':estado', $this->estado);
        $stmt->bindParam(':tipoDocumento', $this->tipoDocumento);
        $cerradaVal = $this->cerrada ? 1 : 0;
        $stmt->bindParam(':cerrada', $cerradaVal, PDO::PARAM_INT);
        $stmt->bindParam(':importeEntregado', $this->importeEntregado);
        $stmt->bindParam(':cambioDevuelto', $this->cambioDevuelto);
        // Ejecutamos la consulta
        $resultado = $stmt->execute();
        // Obtenemos el ID de la nueva venta
        $this->id = $conexion->lastInsertId();
        // Devolvemos el resultado
        return $resultado;
    }

    /**
     * Actualiza los datos de la venta en la base de datos.
     * @return bool
     */
    public function actualizar()
    {
        // Obtenemos la instancia de la conexión
        $conexion = ConexionDB::getInstancia()->getConexion();
        // Preparamos la consulta
        $stmt = $conexion->prepare(
            "UPDATE ventas SET idUsuario = :idUsuario, fecha = :fecha, total = :total, 
             metodoPago = :metodoPago, estado = :estado, tipoDocumento = :tipoDocumento, cerrada = :cerrada WHERE id = :id"
        );
        // Vinculamos los parámetros
        $stmt->bindParam(':idUsuario', $this->idUsuario, PDO::PARAM_INT);
        $stmt->bindParam(':fecha', $this->fecha);
        $stmt->bindParam(':total', $this->total);
        $stmt->bindParam(':metodoPago', $this->metodoPago);
        $stmt->bindParam(':estado', $this->estado);
        $stmt->bindParam(':tipoDocumento', $this->tipoDocumento);
        $cerradaVal = $this->cerrada ? 1 : 0;
        $stmt->bindParam(':cerrada', $cerradaVal, PDO::PARAM_INT);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        // Ejecutamos la consulta
        return $stmt->execute();
    }

    /**
     * Anula una venta (cambia su estado a 'anulada').
     * @return bool
     */
    public function anular()
    {
        $this->estado = 'anulada';
        return $this->actualizar();
    }

    /**
     * Elimina la venta de la base de datos.
     * @return bool
     */
    public function eliminar()
    {
        // Obtenemos la instancia de la conexión
        $conexion = ConexionDB::getInstancia()->getConexion();
        // Preparamos la consulta
        $stmt = $conexion->prepare("DELETE FROM ventas WHERE id = :id");
        // Vinculamos los parámetros
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        // Ejecutamos la consulta
        return $stmt->execute();
    }

    // ======================== MÉTODOS AUXILIARES ========================

    /**
     * Obtiene el resumen de ventas abiertas (no cerradas) agrupadas por método de pago.
     * @return array
     */
    public static function obtenerResumenCajaAbierta()
    {
        // Obtenemos la instancia de la conexión
        $conexion = ConexionDB::getInstancia()->getConexion();
        // Preparamos la consulta
        $stmt = $conexion->query(
            "SELECT metodoPago, SUM(total) as sumaTotal, COUNT(id) as cantidad 
             FROM ventas 
             WHERE cerrada = 0 AND estado = 'completada' 
             GROUP BY metodoPago"
        );
        // Creamos un array para guardar el resumen
        $resumen = [
            'efectivo' => ['total' => 0, 'cantidad' => 0, 'devoluciones' => 0],
            'tarjeta' => ['total' => 0, 'cantidad' => 0, 'devoluciones' => 0],
            'bizum' => ['total' => 0, 'cantidad' => 0, 'devoluciones' => 0],
            'totalGeneral' => 0,
            'totalDevoluciones' => 0
        ];

        // Recorremos las filas
        while ($fila = $stmt->fetch()) {
            // Obtenemos el método de pago
            $metodo = $fila['metodoPago'];
            // Si el método de pago existe en el resumen
            if (isset($resumen[$metodo])) {
                // Actualizamos el total y la cantidad
                $resumen[$metodo]['total'] = (float) $fila['sumaTotal'];
                $resumen[$metodo]['cantidad'] = (int) $fila['cantidad'];
                $resumen['totalGeneral'] += (float) $fila['sumaTotal'];
            }
        }

        // Restar devoluciones
        // Requerimos los ficheros de Devolucion y Caja
        require_once(__DIR__ . '/Devolucion.php');
        require_once(__DIR__ . '/Caja.php');
        // Obtenemos la sesión abierta
        $sesion = Caja::obtenerSesionAbierta();
        // Si la sesión está abierta
        if ($sesion) {
            // Obtenemos el ID de la sesión
            $idSesion = $sesion->getId();

            // Obtenemos las devoluciones por método de pago
            $devEfectivo = Devolucion::obtenerTotalPorMetodo($idSesion, 'Efectivo');
            $devTarjeta = Devolucion::obtenerTotalPorMetodo($idSesion, 'Tarjeta');
            $devBizum = Devolucion::obtenerTotalPorMetodo($idSesion, 'Bizum');

            // Actualizamos el resumen
            $resumen['efectivo']['devoluciones'] = $devEfectivo;
            $resumen['tarjeta']['devoluciones'] = $devTarjeta;
            $resumen['bizum']['devoluciones'] = $devBizum;

            // Restamos las devoluciones del total
            $resumen['efectivo']['total'] -= $devEfectivo;
            $resumen['tarjeta']['total'] -= $devTarjeta;
            $resumen['bizum']['total'] -= $devBizum;

            // Actualizamos el total de devoluciones
            $resumen['totalDevoluciones'] = $devEfectivo + $devTarjeta + $devBizum;
            // Actualizamos el total general
            $resumen['totalGeneral'] -= $resumen['totalDevoluciones'];
        }

        // Devolvemos el resumen
        return $resumen;
    }

    /**
     * Obtiene el resumen de ventas de una sesión cerrada específica, en base a fechas.
     * @return array
     */
    public static function obtenerResumenCerrada($fechaInicio, $fechaFin, $idSesion)
    {
        // Obtenemos la instancia de la conexión
        $conexion = ConexionDB::getInstancia()->getConexion();
        // Preparamos la consulta
        $stmt = $conexion->prepare(
            "SELECT metodoPago, SUM(total) as sumaTotal, COUNT(id) as cantidad 
             FROM ventas 
             WHERE cerrada = 1 AND estado = 'completada' AND fecha >= :fechaInicio AND fecha <= :fechaFin 
             GROUP BY metodoPago"
        );
        // Vinculamos los parámetros
        $stmt->bindParam(':fechaInicio', $fechaInicio);
        $stmt->bindParam(':fechaFin', $fechaFin);
        // Ejecutamos la consulta
        $stmt->execute();

        // Creamos un array para guardar el resumen
        $resumen = [
            'efectivo' => ['total' => 0, 'cantidad' => 0, 'devoluciones' => 0],
            'tarjeta' => ['total' => 0, 'cantidad' => 0, 'devoluciones' => 0],
            'bizum' => ['total' => 0, 'cantidad' => 0, 'devoluciones' => 0],
            'totalGeneral' => 0,
            'totalDevoluciones' => 0
        ];

        // Recorremos las filas
        while ($fila = $stmt->fetch()) {
            // Obtenemos el método de pago
            $metodo = $fila['metodoPago'];
            // Si el método de pago existe en el resumen
            if (isset($resumen[$metodo])) {
                // Actualizamos el total y la cantidad
                $resumen[$metodo]['total'] = (float) $fila['sumaTotal'];
                $resumen[$metodo]['cantidad'] = (int) $fila['cantidad'];
                $resumen['totalGeneral'] += (float) $fila['sumaTotal'];
            }
        }

        // Requerimos el fichero de Devolucion
        require_once(__DIR__ . '/Devolucion.php');
        // Si la sesión está abierta
        if ($idSesion) {
            // Obtenemos las devoluciones por método de pago
            $devEfectivo = Devolucion::obtenerTotalPorMetodo($idSesion, 'Efectivo');
            $devTarjeta = Devolucion::obtenerTotalPorMetodo($idSesion, 'Tarjeta');
            $devBizum = Devolucion::obtenerTotalPorMetodo($idSesion, 'Bizum');

            // Actualizamos el resumen
            $resumen['efectivo']['devoluciones'] = $devEfectivo;
            $resumen['tarjeta']['devoluciones'] = $devTarjeta;
            $resumen['bizum']['devoluciones'] = $devBizum;

            // Restamos las devoluciones del total
            $resumen['efectivo']['total'] -= $devEfectivo;
            $resumen['tarjeta']['total'] -= $devTarjeta;
            $resumen['bizum']['total'] -= $devBizum;

            // Actualizamos el total de devoluciones
            $resumen['totalDevoluciones'] = $devEfectivo + $devTarjeta + $devBizum;
            // Actualizamos el total general
            $resumen['totalGeneral'] -= $resumen['totalDevoluciones'];
        }

        // Devolvemos el resumen
        return $resumen;
    }

    /**
     * Marca todas las ventas completadas abiertas como cerradas.
     * @return int Número de ventas cerradas
     */
    public static function cerrarCaja()
    {
        // Obtenemos la instancia de la conexión
        $conexion = ConexionDB::getInstancia()->getConexion();
        // Preparamos la consulta
        $stmt = $conexion->prepare("UPDATE ventas SET cerrada = 1 WHERE cerrada = 0 AND estado = 'completada'");
        // Ejecutamos la consulta
        $stmt->execute();
        // Devolvemos el número de ventas cerradas
        return $stmt->rowCount();
    }

    /**
     * Crea un objeto Venta a partir de un array asociativo.
     * @param array $fila
     * @return Venta
     */
    private static function crearDesdeArray($fila)
    {
        // Creamos una nueva venta
        $venta = new Venta();
        // Asignamos los valores de la fila a la venta
        $venta->setId($fila['id']);
        $venta->setIdUsuario($fila['idUsuario']);
        $venta->setFecha($fila['fecha']);
        $venta->setTotal($fila['total']);
        $venta->setMetodoPago($fila['metodoPago']);
        $venta->setEstado($fila['estado']);
        $venta->setTipoDocumento($fila['tipoDocumento'] ?? 'ticket');
        $venta->setCerrada($fila['cerrada'] ?? 0);
        $venta->setImporteEntregado($fila['importeEntregado'] ?? null);
        $venta->setCambioDevuelto($fila['cambioDevuelto'] ?? null);
        // Devolvemos la venta
        return $venta;
    }
}

?>