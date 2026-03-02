<?php
/**
 * Modelo para las líneas de detalle de cada venta.
 * 
 * @author Alberto Méndez
 * @version 1.2 (02/03/2026)
 */

// Requerimos el fichero de conexión a la base de datos
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
        // Obtenemos la instancia de la conexión
        $conexion = ConexionDB::getInstancia()->getConexion();
        // Preparamos la consulta
        $stmt = $conexion->prepare(
            "SELECT * FROM lineasVenta WHERE idVenta = :idVenta"
        );
        // Vinculamos los parámetros
        $stmt->bindParam(':idVenta', $idVenta, PDO::PARAM_INT);
        // Ejecutamos la consulta
        $stmt->execute();
        // Creamos un array para guardar las líneas
        $lineas = [];
        // Recorremos las filas
        while ($fila = $stmt->fetch()) {
            // Creamos una nueva línea y la añadimos al array
            $lineas[] = self::crearDesdeArray($fila);
        }
        // Devolvemos las líneas
        return $lineas;
    }

    /**
     * Busca una línea de venta por su ID.
     * @param int $id
     * @return LineaVenta|null
     */
    public static function buscarPorId($id)
    {
        // Obtenemos la instancia de la conexión
        $conexion = ConexionDB::getInstancia()->getConexion();
        // Preparamos la consulta
        $stmt = $conexion->prepare("SELECT * FROM lineasVenta WHERE id = :id");
        // Vinculamos los parámetros
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        // Ejecutamos la consulta
        $stmt->execute();
        // Obtenemos la fila
        $fila = $stmt->fetch();
        // Si se encuentra la fila, creamos una nueva línea
        if ($fila) {
            return self::crearDesdeArray($fila);
        }
        // Si no se encuentra la fila, devolvemos null
        return null;
    }

    /**
     * Inserta una nueva línea de venta en la base de datos.
     * @return bool
     */
    public function insertar()
    {
        // Calcula el subtotal automáticamente.
        $this->subtotal = $this->cantidad * $this->precioUnitario;
        // Obtenemos la instancia de la conexión
        $conexion = ConexionDB::getInstancia()->getConexion();
        // Preparamos la consulta
        $stmt = $conexion->prepare(
            "INSERT INTO lineasVenta (idVenta, idProducto, cantidad, precioUnitario, subtotal) 
             VALUES (:idVenta, :idProducto, :cantidad, :precioUnitario, :subtotal)"
        );
        // Vinculamos los parámetros
        $stmt->bindParam(':idVenta', $this->idVenta, PDO::PARAM_INT);
        $stmt->bindParam(':idProducto', $this->idProducto, PDO::PARAM_INT);
        $stmt->bindParam(':cantidad', $this->cantidad, PDO::PARAM_INT);
        $stmt->bindParam(':precioUnitario', $this->precioUnitario);
        $stmt->bindParam(':subtotal', $this->subtotal);
        // Ejecutamos la consulta
        $resultado = $stmt->execute();
        // Obtenemos el último ID insertado
        $this->id = $conexion->lastInsertId();
        // Devolvemos el resultado
        return $resultado;
    }

    /**
     * Actualiza la línea de venta en la base de datos.
     * @return bool
     */
    public function actualizar()
    {
        // Calcula el subtotal automáticamente.
        $this->subtotal = $this->cantidad * $this->precioUnitario;
        // Obtenemos la instancia de la conexión
        $conexion = ConexionDB::getInstancia()->getConexion();
        // Preparamos la consulta
        $stmt = $conexion->prepare(
            "UPDATE lineasVenta SET idVenta = :idVenta, idProducto = :idProducto, 
             cantidad = :cantidad, precioUnitario = :precioUnitario, subtotal = :subtotal WHERE id = :id"
        );
        // Vinculamos los parámetros
        $stmt->bindParam(':idVenta', $this->idVenta, PDO::PARAM_INT);
        $stmt->bindParam(':idProducto', $this->idProducto, PDO::PARAM_INT);
        $stmt->bindParam(':cantidad', $this->cantidad, PDO::PARAM_INT);
        $stmt->bindParam(':precioUnitario', $this->precioUnitario);
        $stmt->bindParam(':subtotal', $this->subtotal);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        // Ejecutamos la consulta
        return $stmt->execute();
    }

    /**
     * Elimina la línea de venta de la base de datos.
     * @return bool
     */
    public function eliminar()
    {
        // Obtenemos la instancia de la conexión
        $conexion = ConexionDB::getInstancia()->getConexion();
        // Preparamos la consulta
        $stmt = $conexion->prepare("DELETE FROM lineasVenta WHERE id = :id");
        // Vinculamos los parámetros
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        // Ejecutamos la consulta
        return $stmt->execute();
    }

    /**
     * Elimina todas las líneas de una venta.
     * @param int $idVenta
     * @return bool
     */
    public static function eliminarPorVenta($idVenta)
    {
        // Obtenemos la instancia de la conexión
        $conexion = ConexionDB::getInstancia()->getConexion();
        // Preparamos la consulta
        $stmt = $conexion->prepare("DELETE FROM lineasVenta WHERE idVenta = :idVenta");
        // Vinculamos los parámetros
        $stmt->bindParam(':idVenta', $idVenta, PDO::PARAM_INT);
        // Ejecutamos la consulta
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
        // Creamos una nueva línea
        $linea = new LineaVenta();
        // Asignamos los valores
        $linea->setId($fila['id']);
        $linea->setIdVenta($fila['idVenta']);
        $linea->setIdProducto($fila['idProducto']);
        $linea->setCantidad($fila['cantidad']);
        $linea->setPrecioUnitario($fila['precioUnitario']);
        $linea->setSubtotal($fila['subtotal']);
        // Devolvemos la linea creada
        return $linea;
    }
}

?>