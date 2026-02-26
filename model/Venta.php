<?php

/*
 * Autor: Alberto Méndez 
 * Fecha de actualización: 24/02/2026
 * 
 * Clase modelo para la gestión de ventas/tickets del TPV.
 */

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

    // ======================== MÉTODOS CRUD ========================

    /**
     * Busca una venta por su ID.
     * @param int $id
     * @return Venta|null
     */
    public static function buscarPorId($id)
    {
        $conexion = ConexionDB::getInstancia()->getConexion();
        $stmt = $conexion->prepare("SELECT * FROM ventas WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $fila = $stmt->fetch();

        if ($fila) {
            return self::crearDesdeArray($fila);
        }
        return null;
    }

    /**
     * Obtiene todas las ventas.
     * @return array
     */
    public static function obtenerTodas()
    {
        $conexion = ConexionDB::getInstancia()->getConexion();
        $stmt = $conexion->query("SELECT * FROM ventas ORDER BY fecha DESC");
        $ventas = [];
        while ($fila = $stmt->fetch()) {
            $ventas[] = self::crearDesdeArray($fila);
        }
        return $ventas;
    }

    /**
     * Obtiene las ventas de un usuario concreto.
     * @param int $idUsuario
     * @return array
     */
    public static function obtenerPorUsuario($idUsuario)
    {
        $conexion = ConexionDB::getInstancia()->getConexion();
        $stmt = $conexion->prepare(
            "SELECT * FROM ventas WHERE idUsuario = :idUsuario ORDER BY fecha DESC"
        );
        $stmt->bindParam(':idUsuario', $idUsuario, PDO::PARAM_INT);
        $stmt->execute();
        $ventas = [];
        while ($fila = $stmt->fetch()) {
            $ventas[] = self::crearDesdeArray($fila);
        }
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
        $conexion = ConexionDB::getInstancia()->getConexion();
        $stmt = $conexion->prepare(
            "SELECT * FROM ventas WHERE fecha BETWEEN :fechaInicio AND :fechaFin ORDER BY fecha DESC"
        );
        $stmt->bindParam(':fechaInicio', $fechaInicio);
        $stmt->bindParam(':fechaFin', $fechaFin);
        $stmt->execute();
        $ventas = [];
        while ($fila = $stmt->fetch()) {
            $ventas[] = self::crearDesdeArray($fila);
        }
        return $ventas;
    }

    /**
     * Inserta una nueva venta en la base de datos.
     * @return bool
     */
    public function insertar()
    {
        $conexion = ConexionDB::getInstancia()->getConexion();
        $stmt = $conexion->prepare(
            "INSERT INTO ventas (idUsuario, fecha, total, metodoPago, estado, tipoDocumento, cerrada) 
             VALUES (:idUsuario, :fecha, :total, :metodoPago, :estado, :tipoDocumento, :cerrada)"
        );
        $stmt->bindParam(':idUsuario', $this->idUsuario, PDO::PARAM_INT);
        $stmt->bindParam(':fecha', $this->fecha);
        $stmt->bindParam(':total', $this->total);
        $stmt->bindParam(':metodoPago', $this->metodoPago);
        $stmt->bindParam(':estado', $this->estado);
        $stmt->bindParam(':tipoDocumento', $this->tipoDocumento);
        $cerradaVal = $this->cerrada ? 1 : 0;
        $stmt->bindParam(':cerrada', $cerradaVal, PDO::PARAM_INT);
        $resultado = $stmt->execute();
        $this->id = $conexion->lastInsertId();
        return $resultado;
    }

    /**
     * Actualiza los datos de la venta en la base de datos.
     * @return bool
     */
    public function actualizar()
    {
        $conexion = ConexionDB::getInstancia()->getConexion();
        $stmt = $conexion->prepare(
            "UPDATE ventas SET idUsuario = :idUsuario, fecha = :fecha, total = :total, 
             metodoPago = :metodoPago, estado = :estado, tipoDocumento = :tipoDocumento, cerrada = :cerrada WHERE id = :id"
        );
        $stmt->bindParam(':idUsuario', $this->idUsuario, PDO::PARAM_INT);
        $stmt->bindParam(':fecha', $this->fecha);
        $stmt->bindParam(':total', $this->total);
        $stmt->bindParam(':metodoPago', $this->metodoPago);
        $stmt->bindParam(':estado', $this->estado);
        $stmt->bindParam(':tipoDocumento', $this->tipoDocumento);
        $cerradaVal = $this->cerrada ? 1 : 0;
        $stmt->bindParam(':cerrada', $cerradaVal, PDO::PARAM_INT);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
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
        $conexion = ConexionDB::getInstancia()->getConexion();
        $stmt = $conexion->prepare("DELETE FROM ventas WHERE id = :id");
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // ======================== MÉTODOS AUXILIARES ========================

    /**
     * Obtiene el resumen de ventas abiertas (no cerradas) agrupadas por método de pago.
     * @return array
     */
    public static function obtenerResumenCajaAbierta()
    {
        $conexion = ConexionDB::getInstancia()->getConexion();
        $stmt = $conexion->query(
            "SELECT metodoPago, SUM(total) as sumaTotal, COUNT(id) as cantidad 
             FROM ventas 
             WHERE cerrada = 0 AND estado = 'completada' 
             GROUP BY metodoPago"
        );
        $resumen = [
            'efectivo' => ['total' => 0, 'cantidad' => 0],
            'tarjeta' => ['total' => 0, 'cantidad' => 0],
            'bizum' => ['total' => 0, 'cantidad' => 0],
            'totalGeneral' => 0
        ];

        while ($fila = $stmt->fetch()) {
            $metodo = $fila['metodoPago'];
            if (isset($resumen[$metodo])) {
                $resumen[$metodo]['total'] = (float) $fila['sumaTotal'];
                $resumen[$metodo]['cantidad'] = (int) $fila['cantidad'];
                $resumen['totalGeneral'] += (float) $fila['sumaTotal'];
            }
        }
        return $resumen;
    }

    /**
     * Marca todas las ventas completadas abiertas como cerradas.
     * @return int Número de ventas cerradas
     */
    public static function cerrarCaja()
    {
        $conexion = ConexionDB::getInstancia()->getConexion();
        $stmt = $conexion->prepare("UPDATE ventas SET cerrada = 1 WHERE cerrada = 0 AND estado = 'completada'");
        $stmt->execute();
        return $stmt->rowCount();
    }

    /**
     * Crea un objeto Venta a partir de un array asociativo.
     * @param array $fila
     * @return Venta
     */
    private static function crearDesdeArray($fila)
    {
        $venta = new Venta();
        $venta->setId($fila['id']);
        $venta->setIdUsuario($fila['idUsuario']);
        $venta->setFecha($fila['fecha']);
        $venta->setTotal($fila['total']);
        $venta->setMetodoPago($fila['metodoPago']);
        $venta->setEstado($fila['estado']);
        $venta->setTipoDocumento($fila['tipoDocumento'] ?? 'ticket');
        $venta->setCerrada($fila['cerrada'] ?? 0);
        return $venta;
    }
}

?>