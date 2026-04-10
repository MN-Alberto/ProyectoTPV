<?php

/**
 * Componente Núcleo de Persistencia (Conexión DB).
 * Implementa el patrón Singleton para garantizar una única instancia de PDO,
 * optimizando el uso de recursos y centralizando la configuración de codificación.
 * 
 * @author Alberto Méndez
 * @version 1.3 (09/04/2026)
 */

// ✅ SOLUCIÓN BLOQUEO DE SESIONES PHP
// Se cierra el bloqueo de sesion inmediatamente para permitir peticiones paralelas
// Esto soluciona que las peticiones se queden en estado PENDING indefinidamente
if (session_status() == PHP_SESSION_ACTIVE) {
    session_write_close();
}

class ConexionDB
{

    private static $instancia = null; // Instancia única de la conexión.
    private $conexion; // Objeto PDO.

    /**
     * Constructor privado para evitar instanciación directa.
     * Establece la conexión con la base de datos.
     */
    private function __construct()
    {
        try {
            // Realizamos la conexión a la base de datos
            $this->conexion = new PDO(RUTA, USUARIO, PASS);
            // Establecemos el modo de errores a excepciones
            $this->conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Modo de errores: excepciones.
            // Establecemos el modo de fetch a array asociativo
            $this->conexion->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC); // Modo de fetch: array asociativo.
            // Establecemos la codificación a UTF-8
            $this->conexion->exec("SET NAMES 'utf8'"); // Codificación UTF-8.
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