<?php
/**
 * Modelo para las devoluciones de productos.
 * 
 * @author Alberto Méndez
 * @version 1.2 (02/03/2026)
 */

// Requerimos el fichero de conexión a la base de datos
require_once(__DIR__ . '/../core/conexionDB.php');

// Definimos la clase Devolucion
class Devolucion
{
    private $id;
    private $idUsuario;
    private $idProducto;
    private $cantidad;
    private $importeTotal;
    private $idSesionCaja;
    private $fecha;
    private $metodoPago;

    public function __construct($idPost = null, $idUsuario = null, $idProducto = null, $cantidad = null, $importeTotal = null, $idSesionCaja = null, $fecha = null, $metodoPago = 'Efectivo')
    {
        $this->id = $idPost;
        $this->idUsuario = $idUsuario;
        $this->idProducto = $idProducto;
        $this->cantidad = $cantidad;
        $this->importeTotal = $importeTotal;
        $this->idSesionCaja = $idSesionCaja;
        $this->fecha = $fecha;
        $this->metodoPago = $metodoPago;
    }

    // Getters y Setters
    public function getId()
    {
        return $this->id;
    }
    public function getIdUsuario()
    {
        return $this->idUsuario;
    }
    public function setIdUsuario($idUsuario)
    {
        $this->idUsuario = $idUsuario;
    }
    public function getIdProducto()
    {
        return $this->idProducto;
    }
    public function setIdProducto($idProducto)
    {
        $this->idProducto = $idProducto;
    }
    public function getCantidad()
    {
        return $this->cantidad;
    }
    public function setCantidad($cantidad)
    {
        $this->cantidad = $cantidad;
    }
    public function getImporteTotal()
    {
        return $this->importeTotal;
    }
    public function setImporteTotal($importeTotal)
    {
        $this->importeTotal = $importeTotal;
    }
    public function getIdSesionCaja()
    {
        return $this->idSesionCaja;
    }
    public function setIdSesionCaja($idSesionCaja)
    {
        $this->idSesionCaja = $idSesionCaja;
    }
    public function getMetodoPago()
    {
        return $this->metodoPago;
    }
    public function setMetodoPago($metodoPago)
    {
        $this->metodoPago = $metodoPago;
    }

    public function getFecha()
    {
        return $this->fecha;
    }
    public function setFecha($fecha)
    {
        $this->fecha = $fecha;
    }

    /**
     * Inserta una nueva devolución en la base de datos.
     */
    public function insertar()
    {
        // Obtenemos la instancia de la conexión
        $conexion = ConexionDB::getInstancia()->getConexion();
        // Preparamos la consulta
        $stmt = $conexion->prepare(
            "INSERT INTO devoluciones (idUsuario, idProducto, cantidad, importeTotal, idSesionCaja, fecha, metodoPago) 
             VALUES (:idUsuario, :idProducto, :cantidad, :importeTotal, :idSesionCaja, :fecha, :metodoPago)"
        );
        $stmt->bindParam(':idUsuario', $this->idUsuario, PDO::PARAM_INT);
        $stmt->bindParam(':idProducto', $this->idProducto, PDO::PARAM_INT);
        $stmt->bindParam(':cantidad', $this->cantidad, PDO::PARAM_INT);
        $stmt->bindParam(':importeTotal', $this->importeTotal);
        $stmt->bindParam(':idSesionCaja', $this->idSesionCaja, PDO::PARAM_INT);
        $fecha = $this->fecha ?? date('Y-m-d H:i:s');
        $stmt->bindParam(':fecha', $fecha);
        $stmt->bindParam(':metodoPago', $this->metodoPago);

        $resultado = $stmt->execute();
        if ($resultado) {
            $this->id = $conexion->lastInsertId();
        }
        return $resultado;
    }

    /**
     * Obtiene el total de devoluciones en una sesión de caja específica.
     * 
     * @param int $idSesionCaja
     * @return float
     */
    public static function obtenerTotalPorSesion($idSesionCaja)
    {
        // Obtenemos la instancia de la conexión
        $conexion = ConexionDB::getInstancia()->getConexion();
        // Preparamos la consulta
        $stmt = $conexion->prepare("SELECT SUM(importeTotal) as total FROM devoluciones WHERE idSesionCaja = :idSesionCaja");
        // Vinculamos los parámetros
        $stmt->bindParam(':idSesionCaja', $idSesionCaja, PDO::PARAM_INT);
        // Ejecutamos la consulta
        $stmt->execute();
        // Obtenemos la fila
        $fila = $stmt->fetch();
        // Devolvemos el total
        return (float) ($fila['total'] ?? 0);
    }

    /**
     * Obtiene el total de devoluciones por método de pago.
     * 
     * @param int $idSesionCaja
     * @param string $metodo
     * @return float
     */
    public static function obtenerTotalPorMetodo($idSesionCaja, $metodo)
    {
        // Obtenemos la instancia de la conexión
        $conexion = ConexionDB::getInstancia()->getConexion();
        // Preparamos la consulta
        $stmt = $conexion->prepare("SELECT SUM(importeTotal) as total FROM devoluciones WHERE idSesionCaja = :idSesionCaja AND metodoPago = :metodo");
        // Vinculamos los parámetros
        $stmt->bindParam(':idSesionCaja', $idSesionCaja, PDO::PARAM_INT);
        $stmt->bindParam(':metodo', $metodo);
        // Ejecutamos la consulta
        $stmt->execute();
        // Obtenemos la fila
        $fila = $stmt->fetch();
        // Devolvemos el total
        return (float) ($fila['total'] ?? 0);
    }
}
?>