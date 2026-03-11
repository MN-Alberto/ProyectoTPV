<?php

/** 
 * Clase modelo para la gestión de productos informáticos del bazar.
 * 
 * @author Alberto Méndez
 * @version 1.3 (11/03/2026)
 */

// Requerimos el fichero de conexión a la base de datos
require_once(__DIR__ . '/../core/conexionDB.php');

class Producto
{

    private $id;
    private $nombre;
    private $descripcion;
    private $precio;
    private $stock;
    private $idCategoria;
    private $imagen;
    private $activo;
    private $idIva;
    // Campos auxiliares (vienen del JOIN con la tabla iva)
    private $ivaPorcentaje;
    private $ivaNombre;

    // ======================== GETTERS ========================

    public function getId()
    {
        return $this->id;
    }

    public function getNombre()
    {
        return $this->nombre;
    }

    public function getDescripcion()
    {
        return $this->descripcion;
    }

    public function getPrecio()
    {
        return $this->precio;
    }

    public function getStock()
    {
        return $this->stock;
    }

    public function getIdCategoria()
    {
        return $this->idCategoria;
    }

    public function getImagen()
    {
        return $this->imagen;
    }

    public function getActivo()
    {
        return $this->activo;
    }

    public function getIdIva()
    {
        return $this->idIva;
    }

    public function getIvaPorcentaje()
    {
        return $this->ivaPorcentaje;
    }

    public function getIvaNombre()
    {
        return $this->ivaNombre;
    }

    // ======================== SETTERS ========================

    public function setId($id)
    {
        $this->id = $id;
    }

    public function setNombre($nombre)
    {
        $this->nombre = $nombre;
    }

    public function setDescripcion($descripcion)
    {
        $this->descripcion = $descripcion;
    }

    public function setPrecio($precio)
    {
        $this->precio = $precio;
    }

    public function setStock($stock)
    {
        $this->stock = $stock;
    }

    public function setIdCategoria($idCategoria)
    {
        $this->idCategoria = $idCategoria;
    }

    public function setImagen($imagen)
    {
        $this->imagen = $imagen;
    }

    public function setActivo($activo)
    {
        $this->activo = $activo;
    }

    public function setIdIva($idIva)
    {
        $this->idIva = $idIva;
    }

    public function setIvaPorcentaje($ivaPorcentaje)
    {
        $this->ivaPorcentaje = $ivaPorcentaje;
    }

    public function setIvaNombre($ivaNombre)
    {
        $this->ivaNombre = $ivaNombre;
    }

    // ======================== MÉTODOS CRUD ========================

    /**
     * Busca un producto por su ID.
     * @param int $id
     * @return Producto|null
     */
    public static function buscarPorId($id)
    {
        // Obtenemos la instancia de la conexión
        $conexion = ConexionDB::getInstancia()->getConexion();
        // Preparamos la consulta con JOIN a la tabla iva
        $stmt = $conexion->prepare(
            "SELECT p.*, i.porcentaje as ivaPorcentaje, i.nombre as ivaNombre 
             FROM productos p 
             LEFT JOIN iva i ON p.idIva = i.id 
             WHERE p.id = :id"
        );
        // Vinculamos los parámetros
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        // Ejecutamos la consulta
        $stmt->execute();
        // Obtenemos la fila
        $fila = $stmt->fetch();
        // Si se encuentra la fila, creamos un nuevo producto
        if ($fila) {
            return self::crearDesdeArray($fila);
        }
        // Si no se encuentra la fila, devolvemos null
        return null;
    }

    /**
     * Obtiene todos los productos activos.
     * @return array
     */
    public static function obtenerTodos()
    {
        // Obtenemos la instancia de la conexión
        $conexion = ConexionDB::getInstancia()->getConexion();
        // Preparamos la consulta con JOIN a la tabla iva
        $stmt = $conexion->query(
            "SELECT p.*, i.porcentaje as ivaPorcentaje, i.nombre as ivaNombre 
             FROM productos p 
             LEFT JOIN iva i ON p.idIva = i.id 
             WHERE p.activo = 1 ORDER BY p.nombre"
        );
        // Creamos un array para guardar los productos
        $productos = [];
        // Recorremos las filas
        while ($fila = $stmt->fetch()) {
            // Creamos un nuevo producto y lo añadimos al array
            $productos[] = self::crearDesdeArray($fila);
        }
        // Devolvemos los productos
        return $productos;
    }

    /**
     * Obtiene todos los productos (activos e inactivos).
     * @return array
     */
    public static function obtenerTodosAdmin()
    {
        // Obtenemos la instancia de la conexión
        $conexion = ConexionDB::getInstancia()->getConexion();
        // Preparamos la consulta con JOIN a la tabla iva
        $stmt = $conexion->prepare(
            "SELECT p.*, i.porcentaje as ivaPorcentaje, i.nombre as ivaNombre 
             FROM productos p 
             LEFT JOIN iva i ON p.idIva = i.id 
             ORDER BY p.nombre ASC"
        );
        // Ejecutamos la consulta
        $stmt->execute();
        // Creamos un array para guardar los productos
        $productos = [];
        // Recorremos las filas
        while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Creamos un nuevo producto y lo añadimos al array
            $productos[] = self::crearDesdeArray($fila);
        }
        // Devolvemos los productos
        return $productos;
    }

    /**
     * Obtiene todos los productos de una categoría.
     * @param int $idCategoria
     * @return array
     */
    public static function obtenerPorCategoria($idCategoria)
    {
        // Obtenemos la instancia de la conexión
        $conexion = ConexionDB::getInstancia()->getConexion();
        // Preparamos la consulta con JOIN a la tabla iva
        $stmt = $conexion->prepare(
            "SELECT p.*, i.porcentaje as ivaPorcentaje, i.nombre as ivaNombre 
             FROM productos p 
             LEFT JOIN iva i ON p.idIva = i.id 
             WHERE p.idCategoria = :idCategoria AND p.activo = 1 ORDER BY p.nombre"
        );
        // Vinculamos los parámetros
        $stmt->bindParam(':idCategoria', $idCategoria, PDO::PARAM_INT);
        // Ejecutamos la consulta
        $stmt->execute();
        // Creamos un array para guardar los productos
        $productos = [];
        // Recorremos las filas
        while ($fila = $stmt->fetch()) {
            // Creamos un nuevo producto y lo añadimos al array
            $productos[] = self::crearDesdeArray($fila);
        }
        // Devolvemos los productos
        return $productos;
    }

    /**
     * Busca productos por nombre (búsqueda parcial).
     * @param string $nombre
     * @return array
     */
    public static function buscarPorNombre($nombre)
    {
        // Obtenemos la instancia de la conexión
        $conexion = ConexionDB::getInstancia()->getConexion();
        // Preparamos la consulta con JOIN a la tabla iva
        $stmt = $conexion->prepare(
            "SELECT p.*, i.porcentaje as ivaPorcentaje, i.nombre as ivaNombre 
             FROM productos p 
             LEFT JOIN iva i ON p.idIva = i.id 
             WHERE p.nombre LIKE :nombre AND p.activo = 1 ORDER BY p.nombre"
        );
        // Vinculamos los parámetros
        $busqueda = '%' . $nombre . '%';
        $stmt->bindParam(':nombre', $busqueda);
        // Ejecutamos la consulta
        $stmt->execute();
        // Creamos un array para guardar los productos
        $productos = [];
        // Recorremos las filas
        while ($fila = $stmt->fetch()) {
            // Creamos un nuevo producto y lo añadimos al array
            $productos[] = self::crearDesdeArray($fila);
        }
        return $productos;
    }

    /**
     * Busca productos por nombre y categoría (búsqueda completa).
     * @param string $nombre
     * @param string $categoria
     * @return array
     */
    public static function buscarPorNombreYCategoria($nombre, $categoria)
    {
        // Obtenemos la instancia de la conexión
        $conexion = ConexionDB::getInstancia()->getConexion();
        // Preparamos la consulta con JOIN a la tabla iva
        $stmt = $conexion->prepare(
            "SELECT p.*, i.porcentaje as ivaPorcentaje, i.nombre as ivaNombre 
             FROM productos p 
             LEFT JOIN iva i ON p.idIva = i.id 
             WHERE p.nombre LIKE :nombre AND p.idCategoria = :categoria AND p.activo = 1 ORDER BY p.nombre"
        );
        // Vinculamos los parámetros
        $busqueda = '%' . $nombre . '%';
        $stmt->bindParam(':nombre', $busqueda);
        $stmt->bindParam(':categoria', $categoria, PDO::PARAM_STR);
        // Ejecutamos la consulta
        $stmt->execute();
        // Creamos un array para guardar los productos
        $productos = [];
        // Recorremos las filas
        while ($fila = $stmt->fetch()) {
            // Creamos un nuevo producto y lo añadimos al array
            $productos[] = self::crearDesdeArray($fila);
        }
        // Devolvemos los productos
        return $productos;
    }

    /**
     * Inserta un nuevo producto en la base de datos.
     * @return bool
     */
    public function insertar()
    {
        // Obtenemos la instancia de la conexión
        $conexion = ConexionDB::getInstancia()->getConexion();
        // Preparamos la consulta
        $stmt = $conexion->prepare(
            "INSERT INTO productos (nombre, descripcion, precio, stock, idCategoria, imagen, activo, idIva) 
             VALUES (:nombre, :descripcion, :precio, :stock, :idCategoria, :imagen, :activo, :idIva)"
        );
        // Vinculamos los parámetros
        $stmt->bindParam(':nombre', $this->nombre);
        $stmt->bindParam(':descripcion', $this->descripcion);
        $stmt->bindParam(':precio', $this->precio);
        $stmt->bindParam(':stock', $this->stock, PDO::PARAM_INT);
        $stmt->bindParam(':idCategoria', $this->idCategoria, PDO::PARAM_INT);
        $stmt->bindParam(':imagen', $this->imagen);
        $stmt->bindParam(':activo', $this->activo, PDO::PARAM_BOOL);
        $stmt->bindParam(':idIva', $this->idIva, PDO::PARAM_INT);
        // Ejecutamos la consulta
        $resultado = $stmt->execute();
        // Obtenemos el último ID insertado
        $this->id = $conexion->lastInsertId();
        // Devolvemos el resultado
        return $resultado;
    }

    /**
     * Actualiza los datos del producto en la base de datos.
     * @return bool
     */
    public function actualizar()
    {
        // Obtenemos la instancia de la conexión
        $conexion = ConexionDB::getInstancia()->getConexion();
        // Preparamos la consulta
        $stmt = $conexion->prepare(
            "UPDATE productos SET nombre = :nombre, descripcion = :descripcion, precio = :precio, 
             stock = :stock, idCategoria = :idCategoria, 
             imagen = :imagen, activo = :activo, idIva = :idIva WHERE id = :id"
        );
        // Vinculamos los parámetros
        $stmt->bindParam(':nombre', $this->nombre);
        $stmt->bindParam(':descripcion', $this->descripcion);
        $stmt->bindParam(':precio', $this->precio);
        $stmt->bindParam(':stock', $this->stock, PDO::PARAM_INT);
        $stmt->bindParam(':idCategoria', $this->idCategoria, PDO::PARAM_INT);
        $stmt->bindParam(':imagen', $this->imagen);
        $stmt->bindParam(':activo', $this->activo, PDO::PARAM_INT);
        $stmt->bindParam(':idIva', $this->idIva, PDO::PARAM_INT);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Actualiza el stock del producto.
     * @param int $cantidad (positiva para sumar, negativa para restar)
     * @return bool
     */
    public function actualizarStock($cantidad)
    {
        $this->stock += $cantidad;
        // Si el stock es menor a 0, lo inicializamos a 0
        if ($this->stock < 0) {
            $this->stock = 0;
        }
        // Obtenemos la instancia de la conexión
        $conexion = ConexionDB::getInstancia()->getConexion();
        // Preparamos la consulta
        $stmt = $conexion->prepare("UPDATE productos SET stock = :stock WHERE id = :id");
        // Vinculamos los parámetros
        $stmt->bindParam(':stock', $this->stock, PDO::PARAM_INT);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Elimina el producto de la base de datos.
     * @return bool
     */
    public function eliminar()
    {
        // Obtenemos la instancia de la conexión
        $conexion = ConexionDB::getInstancia()->getConexion();
        // Preparamos la consulta
        $stmt = $conexion->prepare("DELETE FROM productos WHERE id = :id");
        // Vinculamos los parámetros
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        // Ejecutamos la consulta
        return $stmt->execute();
    }

    // ======================== MÉTODOS AUXILIARES ========================

    /**
     * Crea un objeto Producto a partir de un array asociativo.
     * @param array $fila
     * @return Producto
     */
    private static function crearDesdeArray($fila)
    {
        // Creamos un nuevo producto
        $producto = new Producto();
        // Asignamos los valores del array al producto
        $producto->setId($fila['id']);
        $producto->setNombre($fila['nombre']);
        $producto->setDescripcion($fila['descripcion']);
        $producto->setPrecio($fila['precio']);
        $producto->setStock($fila['stock']);
        $producto->setIdCategoria($fila['idCategoria']);
        $producto->setImagen($fila['imagen']);
        $producto->setActivo($fila['activo']);
        $producto->setIdIva($fila['idIva'] ?? 1);
        // Campos auxiliares del JOIN con la tabla iva
        $producto->setIvaPorcentaje($fila['ivaPorcentaje'] ?? 21);
        $producto->setIvaNombre($fila['ivaNombre'] ?? 'General');
        // Devolvemos el producto
        return $producto;
    }
}

?>