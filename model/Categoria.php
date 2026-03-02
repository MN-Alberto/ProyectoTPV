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
    private $id;
    private $nombre;
    private $descripcion;

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
        // Devolvemos la instancia
        return $categoria;
    }
}

?>