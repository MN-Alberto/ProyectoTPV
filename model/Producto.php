<?php

/*
 * Autor: Alberto Méndez 
 * Fecha de actualización: 24/02/2026
 * 
 * Clase modelo para la gestión de productos informáticos del bazar.
 */

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

    // ======================== MÉTODOS CRUD ========================

    /**
     * Busca un producto por su ID.
     * @param int $id
     * @return Producto|null
     */
    public static function buscarPorId($id)
    {
        $conexion = ConexionDB::getInstancia()->getConexion();
        $stmt = $conexion->prepare("SELECT * FROM productos WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $fila = $stmt->fetch();

        if ($fila) {
            return self::crearDesdeArray($fila);
        }
        return null;
    }

    /**
     * Obtiene todos los productos activos.
     * @return array
     */
    public static function obtenerTodos()
    {
        $conexion = ConexionDB::getInstancia()->getConexion();
        $stmt = $conexion->query("SELECT * FROM productos WHERE activo = 1 ORDER BY nombre");
        $productos = [];
        while ($fila = $stmt->fetch()) {
            $productos[] = self::crearDesdeArray($fila);
        }
        return $productos;
    }

    /**
     * Obtiene todos los productos de una categoría.
     * @param int $idCategoria
     * @return array
     */
    public static function obtenerPorCategoria($idCategoria)
    {
        $conexion = ConexionDB::getInstancia()->getConexion();
        $stmt = $conexion->prepare(
            "SELECT * FROM productos WHERE idCategoria = :idCategoria AND activo = 1 ORDER BY nombre"
        );
        $stmt->bindParam(':idCategoria', $idCategoria, PDO::PARAM_INT);
        $stmt->execute();
        $productos = [];
        while ($fila = $stmt->fetch()) {
            $productos[] = self::crearDesdeArray($fila);
        }
        return $productos;
    }

    /**
     * Busca productos por nombre (búsqueda parcial).
     * @param string $nombre
     * @return array
     */
    public static function buscarPorNombre($nombre)
    {
        $conexion = ConexionDB::getInstancia()->getConexion();
        $stmt = $conexion->prepare(
            "SELECT * FROM productos WHERE nombre LIKE :nombre AND activo = 1 ORDER BY nombre"
        );
        $busqueda = '%' . $nombre . '%';
        $stmt->bindParam(':nombre', $busqueda);
        $stmt->execute();
        $productos = [];
        while ($fila = $stmt->fetch()) {
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
        $conexion = ConexionDB::getInstancia()->getConexion();
        $stmt = $conexion->prepare(
            "SELECT * FROM productos WHERE nombre LIKE :nombre AND idCategoria = :categoria AND activo = 1 ORDER BY nombre"
        );
        $busqueda = '%' . $nombre . '%';
        $stmt->bindParam(':nombre', $busqueda);
        $stmt->bindParam(':categoria', $categoria, PDO::PARAM_STR);
        $stmt->execute();
        $productos = [];
        while ($fila = $stmt->fetch()) {
            $productos[] = self::crearDesdeArray($fila);
        }
        return $productos;
    }

    /**
     * Inserta un nuevo producto en la base de datos.
     * @return bool
     */
    public function insertar()
    {
        $conexion = ConexionDB::getInstancia()->getConexion();
        $stmt = $conexion->prepare(
            "INSERT INTO productos (nombre, descripcion, precio, stock, idCategoria, imagen, activo) 
             VALUES (:nombre, :descripcion, :precio, :stock, :idCategoria, :imagen, :activo)"
        );
        $stmt->bindParam(':nombre', $this->nombre);
        $stmt->bindParam(':descripcion', $this->descripcion);
        $stmt->bindParam(':precio', $this->precio);
        $stmt->bindParam(':stock', $this->stock, PDO::PARAM_INT);
        $stmt->bindParam(':idCategoria', $this->idCategoria, PDO::PARAM_INT);
        $stmt->bindParam(':imagen', $this->imagen);
        $stmt->bindParam(':activo', $this->activo, PDO::PARAM_BOOL);
        $resultado = $stmt->execute();
        $this->id = $conexion->lastInsertId();
        return $resultado;
    }

    /**
     * Actualiza los datos del producto en la base de datos.
     * @return bool
     */
    public function actualizar()
    {
        $conexion = ConexionDB::getInstancia()->getConexion();
        $stmt = $conexion->prepare(
            "UPDATE productos SET nombre = :nombre, descripcion = :descripcion, precio = :precio, 
             stock = :stock, idCategoria = :idCategoria, 
             imagen = :imagen, activo = :activo WHERE id = :id"
        );
        $stmt->bindParam(':nombre', $this->nombre);
        $stmt->bindParam(':descripcion', $this->descripcion);
        $stmt->bindParam(':precio', $this->precio);
        $stmt->bindParam(':stock', $this->stock, PDO::PARAM_INT);
        $stmt->bindParam(':idCategoria', $this->idCategoria, PDO::PARAM_INT);
        $stmt->bindParam(':imagen', $this->imagen);
        $stmt->bindParam(':activo', $this->activo, PDO::PARAM_BOOL);
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
        if ($this->stock < 0) {
            $this->stock = 0;
        }
        $conexion = ConexionDB::getInstancia()->getConexion();
        $stmt = $conexion->prepare("UPDATE productos SET stock = :stock WHERE id = :id");
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
        $conexion = ConexionDB::getInstancia()->getConexion();
        $stmt = $conexion->prepare("DELETE FROM productos WHERE id = :id");
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
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
        $producto = new Producto();
        $producto->setId($fila['id']);
        $producto->setNombre($fila['nombre']);
        $producto->setDescripcion($fila['descripcion']);
        $producto->setPrecio($fila['precio']);
        $producto->setStock($fila['stock']);
        $producto->setIdCategoria($fila['idCategoria']);
        $producto->setImagen($fila['imagen']);
        $producto->setActivo($fila['activo']);
        return $producto;
    }
}

?>