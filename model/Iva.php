<?php

/**
 * Clase modelo para la gestión de los tipos de IVA.
 * 
 * @author Alberto Méndez
 * @version 1.0 (11/03/2026)
 */

// Requerimos el fichero de conexión a la base de datos
require_once(__DIR__ . '/../core/conexionDB.php');

class Iva
{

    private $id;
    private $nombre;
    private $porcentaje;

    // ======================== GETTERS ========================

    public function getId()
    {
        return $this->id;
    }

    public function getNombre()
    {
        return $this->nombre;
    }

    public function getPorcentaje()
    {
        return $this->porcentaje;
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

    public function setPorcentaje($porcentaje)
    {
        $this->porcentaje = $porcentaje;
    }

    // ======================== MÉTODOS CRUD ========================

    /**
     * Obtiene todos los tipos de IVA.
     * @return array
     */
    public static function obtenerTodos()
    {
        $conexion = ConexionDB::getInstancia()->getConexion();
        $stmt = $conexion->query("SELECT * FROM iva ORDER BY porcentaje DESC");
        $tipos = [];
        while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $tipos[] = self::crearDesdeArray($fila);
        }
        return $tipos;
    }

    /**
     * Busca un tipo de IVA por su ID.
     * @param int $id
     * @return Iva|null
     */
    public static function buscarPorId($id)
    {
        $conexion = ConexionDB::getInstancia()->getConexion();
        $stmt = $conexion->prepare("SELECT * FROM iva WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($fila) {
            return self::crearDesdeArray($fila);
        }
        return null;
    }

    /**
     * Inserta un nuevo tipo de IVA en la base de datos.
     * @return bool
     */
    public function insertar()
    {
        $conexion = ConexionDB::getInstancia()->getConexion();
        $stmt = $conexion->prepare(
            "INSERT INTO iva (nombre, porcentaje) VALUES (:nombre, :porcentaje)"
        );
        $stmt->bindParam(':nombre', $this->nombre);
        $stmt->bindParam(':porcentaje', $this->porcentaje);
        $resultado = $stmt->execute();
        $this->id = $conexion->lastInsertId();
        return $resultado;
    }

    /**
     * Actualiza el tipo de IVA en la base de datos.
     * @return bool
     */
    public function actualizar()
    {
        $conexion = ConexionDB::getInstancia()->getConexion();
        $stmt = $conexion->prepare(
            "UPDATE iva SET nombre = :nombre, porcentaje = :porcentaje WHERE id = :id"
        );
        $stmt->bindParam(':nombre', $this->nombre);
        $stmt->bindParam(':porcentaje', $this->porcentaje);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Elimina el tipo de IVA de la base de datos.
     * @return bool
     */
    public function eliminar()
    {
        $conexion = ConexionDB::getInstancia()->getConexion();
        $stmt = $conexion->prepare("DELETE FROM iva WHERE id = :id");
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // ======================== MÉTODOS AUXILIARES ========================

    /**
     * Crea un objeto Iva a partir de un array asociativo.
     * @param array $fila
     * @return Iva
     */
    private static function crearDesdeArray($fila)
    {
        $iva = new Iva();
        $iva->setId($fila['id']);
        $iva->setNombre($fila['nombre']);
        $iva->setPorcentaje($fila['porcentaje']);
        return $iva;
    }
}

?>
