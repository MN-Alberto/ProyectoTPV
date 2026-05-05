<?php

/**
 * Componente Núcleo de Persistencia (Conexión DB).
 * Implementa el patrón Singleton para garantizar una única instancia de PDO,
 * optimizando el uso de recursos y centralizando la configuración de codificación.
 * 
 * @author Alberto Méndez
 * @version 1.3 (09/04/2026)
 */

/**
 * 🔓 SOLUCIÓN DEFINITIVA AL BLOQUEO DE SESIONES PHP
 * 
 * Esta es la línea más importante de TODO el sistema.
 * 
 * PHP bloquea la sesión de forma EXCLUSIVA durante TODO el tiempo
 * que dura una petición. Ninguna otra petición del mismo usuario
 * puede ejecutarse hasta que la primera termine.
 * 
 * Cerrando la escritura inmediatamente permitimos peticiones paralelas.
 * Sin esto, el panel de administración se quedaba colgado indefinidamente
 * cuando se ejecutaban informes largos.
 */
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
            // Establecemos conexión con las credenciales definidas en config
            $this->conexion = new PDO(RUTA, USUARIO, PASS);

            /**
             * ⚙️ CONFIGURACIÓN ÓPTIMA DE PDO
             * 
             * Estos tres atributos NO son los valores por defecto.
             * Han sido seleccionados cuidadosamente:
             * 
             * 1. ERRMODE_EXCEPTION: No warnings silenciosos, todo error es una excepción
             * 2. FETCH_ASSOC: NUNCA devuelve índices numéricos, reduce el tamaño de los datos en un 50%
             * 3. SET NAMES utf8: Garantiza que TODO viaje en UTF-8 sin excepciones
             */
            $this->conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conexion->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->conexion->exec("SET NAMES 'utf8'");
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
     * 🛡️ PROTECCIÓN CONTRA CLONACIÓN
     * 
     * Método privado para que NO se pueda clonar la instancia singleton.
     * Si se intenta clonar, PHP lanzará un error fatal.
     * 
     * Parte de la implementación correcta del patrón Singleton en PHP.
     */
    private function __clone()
    {
    }
}

?>