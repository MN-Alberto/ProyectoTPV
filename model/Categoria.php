<?php

/**
 * Clase modelo para la gestión de categorías de productos.
 * 
 * @author Alberto Méndez
 * @version 1.2 (02/03/2026)
 */

// Requerimos el fichero de conexión a la base de datos
require_once(__DIR__ . '/../core/conexionDB.php');

// Definimos la clase Categoria
class Categoria
{
    /** 
     * @var int|null Identificador único de la categoría de producto. 
     */
    private $id;
    /** 
     * @var string|null Nombre descriptivo de la categoría (ej: 'Bebidas', 'Alimentos'). 
     */
    private $nombre;
    /** 
     * @var string|null Breve explicación sobre los productos que engloba esta categoría. 
     */
    private $descripcion;
    /** 
     * @var string|null Fecha y hora en la que se registró la categoría en el sistema. 
     */
    private $fecha_creacion;

    // ======================== GETTERS ========================

    /** 
     * Obtiene el ID de la categoría.
     * @return int|null 
     */
    public function getId()
    {
        return $this->id;
    }

    /** 
     * Obtiene el nombre de la categoría.
     * @return string|null 
     */
    public function getNombre()
    {
        return $this->nombre;
    }

    /** 
     * Obtiene la descripción detallada de la categoría.
     * @return string|null 
     */
    public function getDescripcion()
    {
        return $this->descripcion;
    }

    /** 
     * Obtiene la fecha de creación del registro.
     * @return string|null 
     */
    public function getFechaCreacion()
    {
        return $this->fecha_creacion;
    }

    // ======================== SETTERS ========================

    /** 
     * Establece el identificador único de la categoría.
     * @param int $id 
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /** 
     * Define el nombre público de la categoría.
     * @param string $nombre 
     */
    public function setNombre($nombre)
    {
        $this->nombre = $nombre;
    }

    /** 
     * Establece la descripción detallada de los productos incluidos.
     * @param string $descripcion 
     */
    public function setDescripcion($descripcion)
    {
        $this->descripcion = $descripcion;
    }

    /** 
     * Define el momento de creación del registro en el sistema.
     * @param string $fecha_creacion 
     */
    public function setFechaCreacion($fecha_creacion)
    {
        $this->fecha_creacion = $fecha_creacion;
    }

    // ======================== MÉTODOS CRUD ========================

    /**
     * Busca una categoría por su ID.
     * @param int $id
     * @return Categoria|null
     */
    public static function buscarPorId($id)
    {
        // Obtenemos la instancia de la conexión
        $conexion = ConexionDB::getInstancia()->getConexion();
        // Preparamos la consulta
        $stmt = $conexion->prepare("SELECT * FROM categorias WHERE id = :id");
        // Vinculamos los parámetros
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        // Ejecutamos la consulta
        $stmt->execute();
        // Obtenemos la fila
        $fila = $stmt->fetch();

        // Si la fila existe, la devolvemos
        if ($fila) {
            return self::crearDesdeArray($fila);
        }
        // Si no existe, devolvemos null
        return null;
    }

    /**
     * Obtiene todas las categorías.
     * @return array
     */
    public static function obtenerTodas()
    {
        // Obtenemos la instancia de la conexión
        $conexion = ConexionDB::getInstancia()->getConexion();
        // Ejecutamos la consulta
        $stmt = $conexion->query("SELECT * FROM categorias ORDER BY nombre");
        // Creamos un array para guardar las categorías
        $categorias = [];
        // Recorremos las filas
        while ($fila = $stmt->fetch()) {
            // Creamos una nueva categoría y la añadimos al array
            $categorias[] = self::crearDesdeArray($fila);
        }
        return $categorias;
    }

    /**
     * Obtiene el número de productos en una categoría.
     * @return int
     */
    public function contarProductos()
    {
        $conexion = ConexionDB::getInstancia()->getConexion();
        $stmt = $conexion->prepare("SELECT COUNT(*) as total FROM productos WHERE idCategoria = :id AND activo = 1");
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        $stmt->execute();
        $fila = $stmt->fetch();
        return $fila['total'] ?? 0;
    }

    /**
     * Inserta una nueva categoría en la base de datos.
     * @return bool
     */
    public function insertar()
    {
        // Obtenemos la instancia de la conexión
        $conexion = ConexionDB::getInstancia()->getConexion();
        // Preparamos la consulta
        $stmt = $conexion->prepare(
            "INSERT INTO categorias (nombre, descripcion) VALUES (:nombre, :descripcion)"
        );
        // Vinculamos los parámetros
        $stmt->bindParam(':nombre', $this->nombre);
        $stmt->bindParam(':descripcion', $this->descripcion);
        $resultado = $stmt->execute();
        $this->id = $conexion->lastInsertId();
        return $resultado;
    }

    /**
     * Actualiza los datos de la categoría en la base de datos.
     * @return bool
     */
    public function actualizar()
    {
        // Obtenemos la instancia de la conexión
        $conexion = ConexionDB::getInstancia()->getConexion();
        // Preparamos la consulta
        $stmt = $conexion->prepare(
            "UPDATE categorias SET nombre = :nombre, descripcion = :descripcion WHERE id = :id"
        );
        // Vinculamos los parámetros
        $stmt->bindParam(':nombre', $this->nombre);
        $stmt->bindParam(':descripcion', $this->descripcion);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        // Ejecutamos la consulta
        return $stmt->execute();
    }

    /**
     * Elimina la categoría de la base de datos.
     * @return bool
     */
    public function eliminar()
    {
        // Obtenemos la instancia de la conexión
        $conexion = ConexionDB::getInstancia()->getConexion();
        // Preparamos la consulta
        $stmt = $conexion->prepare("DELETE FROM categorias WHERE id = :id");
        // Vinculamos los parámetros
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        // Ejecutamos la consulta
        return $stmt->execute();
    }

    // ======================== MÉTODOS AUXILIARES ========================

    /**
     * Crea un objeto Categoria a partir de un array asociativo.
     * @param array $fila
     * @return Categoria
     */
    private static function crearDesdeArray($fila)
    {
        // Creamos una nueva instancia de Categoria
        $categoria = new Categoria();
        // Establecemos los valores de la instancia
        $categoria->setId($fila['id']);
        $categoria->setNombre($fila['nombre']);
        $categoria->setDescripcion($fila['descripcion']);
        $categoria->setFechaCreacion(isset($fila['fecha_creacion']) ? $fila['fecha_creacion'] : null);
        // Devolvemos la instancia
        return $categoria;
    }
}

?>