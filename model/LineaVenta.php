<?php

/*
 * Autor: Alberto Méndez 
 * Fecha de actualización: 24/02/2026
 * 
 * Clase modelo para las líneas de detalle de cada venta.
 */

require_once(__DIR__ . '/../core/conexionDB.php');

class LineaVenta
{

    private $id;
    private $idVenta;
    private $idProducto;
    private $cantidad;
    private $precioUnitario;
    private $subtotal;

    // ======================== GETTERS ========================

    public function getId()
    {
        return $this->id;
    }

    public function getIdVenta()
    {
        return $this->idVenta;
    }

    public function getIdProducto()
    {
        return $this->idProducto;
    }

    public function getCantidad()
    {
        return $this->cantidad;
    }

    public function getPrecioUnitario()
    {
        return $this->precioUnitario;
    }

    public function getSubtotal()
    {
        return $this->subtotal;
    }

    // ======================== SETTERS ========================

    public function setId($id)
    {
        $this->id = $id;
    }

    public function setIdVenta($idVenta)
    {
        $this->idVenta = $idVenta;
    }

    public function setIdProducto($idProducto)
    {
        $this->idProducto = $idProducto;
    }

    public function setCantidad($cantidad)
    {
        $this->cantidad = $cantidad;
    }

    public function setPrecioUnitario($precioUnitario)
    {
        $this->precioUnitario = $precioUnitario;
    }

    public function setSubtotal($subtotal)
    {
        $this->subtotal = $subtotal;
    }

    // ======================== MÉTODOS CRUD ========================

    /**
     * Obtiene todas las líneas de una venta.
     * @param int $idVenta
     * @return array
     */
    public static function obtenerPorVenta($idVenta)
    {
        $conexion = ConexionDB::getInstancia()->getConexion();
        $stmt = $conexion->prepare(
            "SELECT * FROM lineasVenta WHERE idVenta = :idVenta"
        );
        $stmt->bindParam(':idVenta', $idVenta, PDO::PARAM_INT);
        $stmt->execute();
        $lineas = [];
        while ($fila = $stmt->fetch()) {
            $lineas[] = self::crearDesdeArray($fila);
        }
        return $lineas;
    }

    /**
     * Busca una línea de venta por su ID.
     * @param int $id
     * @return LineaVenta|null
     */
    public static function buscarPorId($id)
    {
        $conexion = ConexionDB::getInstancia()->getConexion();
        $stmt = $conexion->prepare("SELECT * FROM lineasVenta WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $fila = $stmt->fetch();

        if ($fila) {
            return self::crearDesdeArray($fila);
        }
        return null;
    }

    /**
     * Inserta una nueva línea de venta en la base de datos.
     * @return bool
     */
    public function insertar()
    {
        $this->subtotal = $this->cantidad * $this->precioUnitario; //Calcula el subtotal automáticamente.
        $conexion = ConexionDB::getInstancia()->getConexion();
        $stmt = $conexion->prepare(
            "INSERT INTO lineasVenta (idVenta, idProducto, cantidad, precioUnitario, subtotal) 
             VALUES (:idVenta, :idProducto, :cantidad, :precioUnitario, :subtotal)"
        );
        $stmt->bindParam(':idVenta', $this->idVenta, PDO::PARAM_INT);
        $stmt->bindParam(':idProducto', $this->idProducto, PDO::PARAM_INT);
        $stmt->bindParam(':cantidad', $this->cantidad, PDO::PARAM_INT);
        $stmt->bindParam(':precioUnitario', $this->precioUnitario);
        $stmt->bindParam(':subtotal', $this->subtotal);
        $resultado = $stmt->execute();
        $this->id = $conexion->lastInsertId();
        return $resultado;
    }

    /**
     * Actualiza la línea de venta en la base de datos.
     * @return bool
     */
    public function actualizar()
    {
        $this->subtotal = $this->cantidad * $this->precioUnitario;
        $conexion = ConexionDB::getInstancia()->getConexion();
        $stmt = $conexion->prepare(
            "UPDATE lineasVenta SET idVenta = :idVenta, idProducto = :idProducto, 
             cantidad = :cantidad, precioUnitario = :precioUnitario, subtotal = :subtotal WHERE id = :id"
        );
        $stmt->bindParam(':idVenta', $this->idVenta, PDO::PARAM_INT);
        $stmt->bindParam(':idProducto', $this->idProducto, PDO::PARAM_INT);
        $stmt->bindParam(':cantidad', $this->cantidad, PDO::PARAM_INT);
        $stmt->bindParam(':precioUnitario', $this->precioUnitario);
        $stmt->bindParam(':subtotal', $this->subtotal);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Elimina la línea de venta de la base de datos.
     * @return bool
     */
    public function eliminar()
    {
        $conexion = ConexionDB::getInstancia()->getConexion();
        $stmt = $conexion->prepare("DELETE FROM lineasVenta WHERE id = :id");
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Elimina todas las líneas de una venta.
     * @param int $idVenta
     * @return bool
     */
    public static function eliminarPorVenta($idVenta)
    {
        $conexion = ConexionDB::getInstancia()->getConexion();
        $stmt = $conexion->prepare("DELETE FROM lineasVenta WHERE idVenta = :idVenta");
        $stmt->bindParam(':idVenta', $idVenta, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // ======================== MÉTODOS AUXILIARES ========================

    /**
     * Crea un objeto LineaVenta a partir de un array asociativo.
     * @param array $fila
     * @return LineaVenta
     */
    private static function crearDesdeArray($fila)
    {
        $linea = new LineaVenta();
        $linea->setId($fila['id']);
        $linea->setIdVenta($fila['idVenta']);
        $linea->setIdProducto($fila['idProducto']);
        $linea->setCantidad($fila['cantidad']);
        $linea->setPrecioUnitario($fila['precioUnitario']);
        $linea->setSubtotal($fila['subtotal']);
        return $linea;
    }
}

?>