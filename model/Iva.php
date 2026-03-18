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

    /** 
     * @var int|null Identificador único del tipo de IVA. 
     */
    private $id;
    /** 
     * @var string|null Nombre común del impuesto (ej: 'General', 'Reducido', 'Superreducido'). 
     */
    private $nombre;
    /** 
     * @var float|null Valor numérico porcentual del impuesto (ej: 21.0, 10.0, 4.0). 
     */
    private $porcentaje;

    // ======================== GETTERS ========================

    /** 
     * Obtiene el ID del tipo de IVA.
     * @return int|null 
     */
    public function getId()
    {
        return $this->id;
    }

    /** 
     * Obtiene el nombre descriptivo del tipo de IVA.
     * @return string|null 
     */
    public function getNombre()
    {
        return $this->nombre;
    }

    /** 
     * Obtiene el valor porcentual del IVA.
     * @return float|null 
     */
    public function getPorcentaje()
    {
        return $this->porcentaje;
    }

    // ======================== SETTERS ========================

    /** 
     * Establece el ID único del tipo de IVA.
     * @param int $id 
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /** 
     * Define el nombre descriptivo del impuesto (ej: 'General').
     * @param string $nombre 
     */
    public function setNombre($nombre)
    {
        $this->nombre = $nombre;
    }

    /** 
     * Establece el valor porcentual (ej: 21.0).
     * @param float $porcentaje 
     */
    public function setPorcentaje($porcentaje)
    {
        $this->porcentaje = $porcentaje;
    }

    // ======================== MÉTODOS CRUD ========================

    /**
     * Recupera el listado completo de tipos de IVA registrados, ordenados por valor descendente.
     * 
     * @return array Colección de objetos Iva.
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
     * Busca la configuración de un IVA específico por su identificador primario.
     * 
     * @param int $id ID del registro.
     * @return Iva|null Objeto Iva o null si no se encuentra.
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
     * Inserta un nuevo gravamen de IVA en el sistema.
     * 
     * @return bool True si la inserción fue exitosa.
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
