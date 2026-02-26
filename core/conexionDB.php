<?php

/*
 * Autor: Alberto Méndez 
 * Fecha de actualización: 24/02/2026
 * 
 * Clase para gestionar la conexión a la base de datos mediante PDO (Singleton).
 */

class ConexionDB
{

    private static $instancia = null; //Instancia única de la conexión.
    private $conexion; //Objeto PDO.

    /**
     * Constructor privado para evitar instanciación directa.
     * Establece la conexión con la base de datos.
     */
    private function __construct()
    {
        try {
            $this->conexion = new PDO(RUTA, USUARIO, PASS);
            $this->conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); //Modo de errores: excepciones.
            $this->conexion->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC); //Modo de fetch: array asociativo.
            $this->conexion->exec("SET NAMES 'utf8'"); //Codificación UTF-8.
        } catch (PDOException $e) {
            die("Error de conexión: " . $e->getMessage());
        }
    }

    /**
     * Obtiene la instancia única de la conexión (Singleton).
     * @return ConexionDB
     */
    public static function getInstancia()
    {
        if (self::$instancia === null) {
            self::$instancia = new ConexionDB();
        }
        return self::$instancia;
    }

    /**
     * Obtiene el objeto PDO de la conexión.
     * @return PDO
     */
    public function getConexion()
    {
        return $this->conexion;
    }

    /**
     * Evitar la clonación del objeto.
     */
    private function __clone()
    {
    }
}

?>