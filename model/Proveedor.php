<?php
/**
 * Clase modelo para la gestión de proveedores.
 * 
 * @author Alberto Méndez
 * @version 1.0 (04/03/2026)
 */

// Requerimos el fichero de conexión a la base de datos
require_once(__DIR__ . '/../core/conexionDB.php');

class Proveedor
{

    private $id;
    private $nombre;
    private $contacto;
    private $email;
    private $direccion;
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

    public function getContacto()
    {
        return $this->contacto;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function getDireccion()
    {
        return $this->direccion;
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

    public function setContacto($contacto)
    {
        $this->contacto = $contacto;
    }

    public function setEmail($email)
    {
        $this->email = $email;
    }

    public function setDireccion($direccion)
    {
        $this->direccion = $direccion;
    }

    public function setActivo($activo)
    {
        $this->activo = $activo;
    }

    // ======================== MÉTODOS CRUD ========================

    /**
     * Busca un proveedor por su ID.
     * @param int $id
     * @return Proveedor|null
     */
    public static function buscarPorId($id)
    {
        $conexion = ConexionDB::getInstancia()->getConexion();
        $stmt = $conexion->prepare("SELECT * FROM proveedores WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $fila = $stmt->fetch();
        if ($fila) {
            return self::crearDesdeArray($fila);
        }
        return null;
    }

    /**
     * Obtiene todos los proveedores.
     * @return array
     */
    public static function obtenerTodos()
    {
        $conexion = ConexionDB::getInstancia()->getConexion();
        $stmt = $conexion->query("SELECT * FROM proveedores ORDER BY nombre ASC");
        $proveedores = [];
        while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $proveedores[] = self::crearDesdeArray($fila);
        }
        return $proveedores;
    }

    /**
     * Busca proveedores por nombre (búsqueda parcial).
     * @param string $nombre
     * @return array
     */
    public static function buscarPorNombre($nombre)
    {
        $conexion = ConexionDB::getInstancia()->getConexion();
        $stmt = $conexion->prepare(
            "SELECT * FROM proveedores WHERE nombre LIKE :nombre ORDER BY nombre"
        );
        $busqueda = '%' . $nombre . '%';
        $stmt->bindParam(':nombre', $busqueda);
        $stmt->execute();
        $proveedores = [];
        while ($fila = $stmt->fetch()) {
            $proveedores[] = self::crearDesdeArray($fila);
        }
        return $proveedores;
    }

    /**
     * Inserta un nuevo proveedor en la base de datos.
     * @return bool
     */
    public function insertar()
    {
        $conexion = ConexionDB::getInstancia()->getConexion();
        $stmt = $conexion->prepare(
            "INSERT INTO proveedores (nombre, contacto, email, direccion, activo) 
             VALUES (:nombre, :contacto, :email, :direccion, :activo)"
        );
        $stmt->bindParam(':nombre', $this->nombre);
        $stmt->bindParam(':contacto', $this->contacto);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':direccion', $this->direccion);
        $stmt->bindParam(':activo', $this->activo, PDO::PARAM_INT);
        $resultado = $stmt->execute();
        $this->id = $conexion->lastInsertId();
        return $resultado;
    }

    /**
     * Actualiza los datos del proveedor en la base de datos.
     * @return bool
     */
    public function actualizar()
    {
        $conexion = ConexionDB::getInstancia()->getConexion();
        $stmt = $conexion->prepare(
            "UPDATE proveedores SET nombre = :nombre, contacto = :contacto, email = :email, 
             direccion = :direccion, activo = :activo WHERE id = :id"
        );
        $stmt->bindParam(':nombre', $this->nombre);
        $stmt->bindParam(':contacto', $this->contacto);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':direccion', $this->direccion);
        $stmt->bindParam(':activo', $this->activo, PDO::PARAM_INT);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Elimina el proveedor de la base de datos.
     * @return bool
     */
    public function eliminar()
    {
        $conexion = ConexionDB::getInstancia()->getConexion();
        $stmt = $conexion->prepare("DELETE FROM proveedores WHERE id = :id");
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // ======================== RELACIÓN CON PRODUCTOS ========================

    /**
     * Obtiene los productos asociados a este proveedor junto con su recargo de equivalencia.
     * @param int $idProveedor
     * @return array
     */
    public static function obtenerProductos($idProveedor)
    {
        $conexion = ConexionDB::getInstancia()->getConexion();
        $stmt = $conexion->prepare(
            "SELECT p.*, pp.recargoEquivalencia, pp.precioProveedor, pp.id as idAsociacion 
             FROM productos p 
             INNER JOIN proveedor_producto pp ON p.id = pp.idProducto 
             WHERE pp.idProveedor = :idProveedor 
             ORDER BY p.nombre ASC"
        );
        $stmt->bindParam(':idProveedor', $idProveedor, PDO::PARAM_INT);
        $stmt->execute();

        $productos = [];
        // No usamos crearDesdeArray de Producto porque necesitamos el recargoEquivalencia
        // Devolvemos mapas (arrays) directamente porque Producto.php no tiene propiedad recargoEquivalencia
        while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $productos[] = [
                'idAsociacion' => $fila['idAsociacion'],
                'idProducto' => $fila['id'],
                'nombre' => $fila['nombre'],
                'precio' => $fila['precio'],
                'stock' => $fila['stock'],
                'activo' => $fila['activo'],
                'recargoEquivalencia' => $fila['recargoEquivalencia'],
                'precioProveedor' => $fila['precioProveedor']
            ];
        }
        return $productos;
    }

    /**
     * Obtiene los productos activos que NO están asociados a este proveedor.
     * Útil para poblar el desplegable de asociar producto.
     * @param int $idProveedor
     * @return array
     */
    public static function obtenerProductosDisponibles($idProveedor)
    {
        $conexion = ConexionDB::getInstancia()->getConexion();
        $stmt = $conexion->prepare(
            "SELECT p.id, p.nombre, p.precio 
             FROM productos p 
             WHERE p.activo = 1 
             AND p.id NOT IN (SELECT idProducto FROM proveedor_producto WHERE idProveedor = :idProveedor) 
             ORDER BY p.nombre ASC"
        );
        $stmt->bindParam(':idProveedor', $idProveedor, PDO::PARAM_INT);
        $stmt->execute();

        $productos = [];
        while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $productos[] = $fila;
        }
        return $productos;
    }

    /**
     * Asocia un producto a un proveedor con un recargo de equivalencia y precio de proveedor.
     */
    public static function agregarProducto($idProveedor, $idProducto, $recargoEquivalencia, $precioProveedor)
    {
        $conexion = ConexionDB::getInstancia()->getConexion();
        $stmt = $conexion->prepare(
            "INSERT INTO proveedor_producto (idProveedor, idProducto, recargoEquivalencia, precioProveedor) 
             VALUES (:idProveedor, :idProducto, :recargo, :precio)"
        );
        $stmt->bindParam(':idProveedor', $idProveedor, PDO::PARAM_INT);
        $stmt->bindParam(':idProducto', $idProducto, PDO::PARAM_INT);
        $stmt->bindParam(':recargo', $recargoEquivalencia);
        $stmt->bindParam(':precio', $precioProveedor);
        return $stmt->execute();
    }

    /**
     * Actualiza el recargo de equivalencia y el precio de proveedor de un producto para un proveedor.
     */
    public static function actualizarAsociacion($idAsociacion, $recargoEquivalencia, $precioProveedor)
    {
        $conexion = ConexionDB::getInstancia()->getConexion();
        $stmt = $conexion->prepare(
            "UPDATE proveedor_producto 
             SET recargoEquivalencia = :recargo, precioProveedor = :precio 
             WHERE id = :idAsociacion"
        );
        $stmt->bindParam(':recargo', $recargoEquivalencia);
        $stmt->bindParam(':precio', $precioProveedor);
        $stmt->bindParam(':idAsociacion', $idAsociacion, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Elimina la asociación de un producto con un proveedor.
     */
    public static function eliminarProductoProveedor($idAsociacion)
    {
        $conexion = ConexionDB::getInstancia()->getConexion();
        $stmt = $conexion->prepare("DELETE FROM proveedor_producto WHERE id = :idAsociacion");
        $stmt->bindParam(':idAsociacion', $idAsociacion, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // ======================== MÉTODOS AUXILIARES ========================

    /**
     * Crea un objeto Proveedor a partir de un array asociativo.
     * @param array $fila
     * @return Proveedor
     */
    private static function crearDesdeArray($fila)
    {
        $proveedor = new Proveedor();
        $proveedor->setId($fila['id']);
        $proveedor->setNombre($fila['nombre']);
        $proveedor->setContacto($fila['contacto'] ?? '');
        $proveedor->setEmail($fila['email'] ?? '');
        $proveedor->setDireccion($fila['direccion'] ?? '');
        $proveedor->setActivo($fila['activo']);
        return $proveedor;
    }
}

?>