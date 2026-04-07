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

    /** 
     * @var int|null Identificador único de la línea de detalle de la venta. 
     */
    private $id;
    /** 
     * @var int|null ID de la venta global a la que pertenece este artículo. 
     */
    private $idVenta;
    /** 
     * @var int|null ID del producto vendido. 
     */
    private $idProducto;
    /** 
     * @var string|null Nombre del producto (especialmente útil para comodines). 
     */
    private $nombreProducto;
    /** 
     * @var int|null Unidades vendidas del producto. 
     */
    private $cantidad;
    /** 
     * @var float|null Precio unitario final cobrado al cliente. 
     */
    private $precioUnitario;
    /** 
     * @var float|null Precio base original del producto antes de aplicar cualquier descuento. 
     */
    private $precioOriginal;
    /** 
     * @var string|null Nombre de la tarifa específica aplicada (ej: 'Mayorista', 'Normal'). 
     */
    private $tarifaNombre;
    /** 
     * @var int|null Tipo de IVA porcentual que se aplicó en el momento de la venta. 
     */
    private $iva;
    /** 
     * @var float|null Importe total de la línea (cantidad multiplicada por precio unitario). 
     */
    private $subtotal;

    // ======================== GETTERS ========================

    /** 
     * Obtiene el ID de la línea de venta.
     * @return int|null 
     */
    public function getId()
    {
        return $this->id;
    }

    /** 
     * Obtiene el ID de la venta asociada.
     * @return int|null 
     */
    public function getIdVenta()
    {
        return $this->idVenta;
    }

    /** 
     * Obtiene el ID del producto vendido.
     * @return int|null 
     */
    public function getIdProducto()
    {
        return $this->idProducto;
    }

    /** 
     * Obtiene el nombre del producto de la línea.
     * @return string|null 
     */
    public function getNombreProducto()
    {
        return $this->nombreProducto;
    }

    /** 
     * Obtiene la cantidad vendida.
     * @return int|null 
     */
    public function getCantidad()
    {
        return $this->cantidad;
    }

    /** 
     * Obtiene el precio unitario final.
     * @return float|null 
     */
    public function getPrecioUnitario()
    {
        return $this->precioUnitario;
    }

    /** 
     * Obtiene el precio original base.
     * @return float|null 
     */
    public function getPrecioOriginal()
    {
        return $this->precioOriginal;
    }

    /** 
     * Obtiene el nombre de la tarifa.
     * @return string|null 
     */
    public function getTarifaNombre()
    {
        return $this->tarifaNombre;
    }

    /** 
     * Obtiene el subtotal acumulado.
     * @return float|null 
     */
    public function getSubtotal()
    {
        return $this->subtotal;
    }

    /** 
     * Obtiene el valor del IVA aplicado.
     * @return int|null 
     */
    public function getIva()
    {
        return $this->iva;
    }

    // ======================== SETTERS ========================

    /** 
     * Establece el ID único de la línea de detalle.
     * @param int $id 
     */
    public function setId($id)
    {
        $this->id = $id;
    }
    /** 
     * Vincula esta línea a una venta global.
     * @param int $idVenta 
     */
    public function setIdVenta($idVenta)
    {
        $this->idVenta = $idVenta;
    }
    /** 
     * Establece el producto que se está vendiendo.
     * @param int|null $idProducto 
     */
    public function setIdProducto($idProducto)
    {
        $this->idProducto = $idProducto;
    }
    /** 
     * Establece el nombre del producto de la línea.
     * @param string|null $nombreProducto 
     */
    public function setNombreProducto($nombreProducto)
    {
        $this->nombreProducto = $nombreProducto;
    }
    /** 
     * Establece el número de unidades vendidas.
     * @param int $cantidad 
     */
    public function setCantidad($cantidad)
    {
        $this->cantidad = $cantidad;
    }
    /** 
     * Establece el precio unitario final (con descuentos aplicados).
     * @param float $precioUnitario 
     */
    public function setPrecioUnitario($precioUnitario)
    {
        $this->precioUnitario = $precioUnitario;
    }
    /** 
     * Establece el precio base original del artículo.
     * @param float $precioOriginal 
     */
    public function setPrecioOriginal($precioOriginal)
    {
        $this->precioOriginal = $precioOriginal;
    }
    /** 
     * Establece el nombre de la tarifa aplicada a esta línea.
     * @param string|null $tarifaNombre 
     */
    public function setTarifaNombre($tarifaNombre)
    {
        $this->tarifaNombre = $tarifaNombre;
    }
    /** 
     * Establece el subtotal de la línea (cantidad * precio).
     * @param float $subtotal 
     */
    public function setSubtotal($subtotal)
    {
        $this->subtotal = $subtotal;
    }
    /** 
     * Establece el tipo de IVA aplicado en esta línea.
     * @param int $iva 
     */
    public function setIva($iva)
    {
        $this->iva = $iva;
    }

    // ======================== MÉTODOS CRUD ========================

    /**
     * Obtiene los detalles de una venta incluyendo la cantidad total ya devuelta de cada producto.
     * Útil para el proceso de validación de devoluciones.
     * 
     * @param int $idVenta
     * @return array
     */
    public static function obtenerDetalleParaDevolucion($idVenta)
    {
        $conexion = ConexionDB::getInstancia()->getConexion();
        // Consulta que une las líneas de venta con la suma de devoluciones ya realizadas para este ticket
        $sql = "SELECT lv.*, COALESCE(lv.nombreProducto, p.nombre) as producto_nombre,
                       IFNULL((SELECT SUM(d.cantidad) FROM devoluciones d 
                               WHERE d.idVenta = lv.idVenta AND (d.idProducto = lv.idProducto OR (lv.idProducto IS NULL AND d.idProducto IS NULL AND d.nombreProducto COLLATE utf8mb4_unicode_ci = lv.nombreProducto COLLATE utf8mb4_unicode_ci))), 0) as cantidad_devuelta
                FROM lineasVenta lv
                LEFT JOIN productos p ON lv.idProducto = p.id
                WHERE lv.idVenta = :idVenta";

        $stmt = $conexion->prepare($sql);
        $stmt->bindParam(':idVenta', $idVenta, PDO::PARAM_INT);
        $stmt->execute();

        $lineas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calcular el precio con IVA para cada línea
        foreach ($lineas as &$linea) {
            $iva = isset($linea['iva']) ? floatval($linea['iva']) : 21;
            $precioBase = floatval($linea['precioUnitario']);
            $precioConIva = $precioBase * (1 + $iva / 100);
            $linea['precioConIva'] = round($precioConIva, 2);
        }

        return $lineas;
    }

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
        // Preparamos la consulta (ahora incluye IVA y nombreProducto)
        $stmt = $conexion->prepare(
            "INSERT INTO lineasVenta (idVenta, idProducto, nombreProducto, cantidad, precioUnitario, precioOriginal, tarifaNombre, iva, subtotal) 
             VALUES (:idVenta, :idProducto, :nombreProducto, :cantidad, :precioUnitario, :precioOriginal, :tarifaNombre, :iva, :subtotal)"
        );
        // Vinculamos los parámetros
        $stmt->bindParam(':idVenta', $this->idVenta, PDO::PARAM_INT);
        
        if ($this->idProducto === null) {
            $stmt->bindValue(':idProducto', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindParam(':idProducto', $this->idProducto, PDO::PARAM_INT);
        }
        
        $stmt->bindParam(':nombreProducto', $this->nombreProducto);
        $stmt->bindParam(':cantidad', $this->cantidad, PDO::PARAM_INT);
        $stmt->bindParam(':precioUnitario', $this->precioUnitario);
        $stmt->bindParam(':precioOriginal', $this->precioOriginal);
        $stmt->bindParam(':tarifaNombre', $this->tarifaNombre);
        $stmt->bindParam(':iva', $this->iva, PDO::PARAM_INT);
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
            "UPDATE lineasVenta SET idVenta = :idVenta, idProducto = :idProducto, nombreProducto = :nombreProducto,
             cantidad = :cantidad, precioUnitario = :precioUnitario, precioOriginal = :precioOriginal, 
             tarifaNombre = :tarifaNombre, iva = :iva, subtotal = :subtotal WHERE id = :id"
        );
        // Vinculamos los parámetros
        $stmt->bindParam(':idVenta', $this->idVenta, PDO::PARAM_INT);
        
        if ($this->idProducto === null) {
            $stmt->bindValue(':idProducto', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindParam(':idProducto', $this->idProducto, PDO::PARAM_INT);
        }
        
        $stmt->bindParam(':nombreProducto', $this->nombreProducto);
        $stmt->bindParam(':cantidad', $this->cantidad, PDO::PARAM_INT);
        $stmt->bindParam(':precioUnitario', $this->precioUnitario);
        $stmt->bindParam(':precioOriginal', $this->precioOriginal);
        $stmt->bindParam(':tarifaNombre', $this->tarifaNombre);
        $stmt->bindParam(':iva', $this->iva, PDO::PARAM_INT);
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
        $linea->setIdProducto($fila['idProducto'] ?? null);
        $linea->setNombreProducto($fila['nombreProducto'] ?? null);
        $linea->setCantidad($fila['cantidad']);
        $linea->setPrecioUnitario($fila['precioUnitario']);
        $linea->setPrecioOriginal($fila['precioOriginal'] ?? $fila['precioUnitario']);
        $linea->setTarifaNombre($fila['tarifaNombre'] ?? null);
        $linea->setIva($fila['iva'] ?? 21);
        $linea->setSubtotal($fila['subtotal']);
        // Devolvemos la linea creada
        return $linea;
    }
}

?>