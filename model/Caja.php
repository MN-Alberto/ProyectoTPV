<?php

/**
 * Clase modelo para la gestión de las sesiones de caja (apertura y cierre).
 * 
 * @author Alberto Méndez
 * @version 1.2 (02/03/2026)
 */

// Requerimos la clase de conexión a la base de datos
require_once(__DIR__ . '/../core/conexionDB.php');

class Caja
{
    // Propiedades de la clase
    private $id;
    private $idUsuario;
    private $fechaApertura;
    private $fechaCierre;
    private $importeInicial;
    private $importeActual;
    private $estado; // 'abierta', 'cerrada'

    // ======================== GETTERS ========================
    public function getId()
    {
        return $this->id;
    }
    public function getIdUsuario()
    {
        return $this->idUsuario;
    }
    public function getFechaApertura()
    {
        return $this->fechaApertura;
    }
    public function getFechaCierre()
    {
        return $this->fechaCierre;
    }
    public function getImporteInicial()
    {
        return $this->importeInicial;
    }
    public function getImporteActual()
    {
        return $this->importeActual;
    }
    public function getEstado()
    {
        return $this->estado;
    }

    // ======================== SETTERS ========================
    public function setId($id)
    {
        $this->id = $id;
    }
    public function setIdUsuario($idUsuario)
    {
        $this->idUsuario = $idUsuario;
    }
    public function setFechaApertura($fechaApertura)
    {
        $this->fechaApertura = $fechaApertura;
    }
    public function setFechaCierre($fechaCierre)
    {
        $this->fechaCierre = $fechaCierre;
    }
    public function setImporteInicial($importeInicial)
    {
        $this->importeInicial = $importeInicial;
    }
    public function setImporteActual($importeActual)
    {
        $this->importeActual = $importeActual;
    }
    public function setEstado($estado)
    {
        $this->estado = $estado;
    }

    /**
     * Busca la sesión de caja actualmente abierta.
     * @return Caja|null
     */
    public static function obtenerSesionAbierta()
    {
        // Obtenemos la instancia de la conexión
        $conexion = ConexionDB::getInstancia()->getConexion();
        // Ejecutamos la consulta
        $stmt = $conexion->query("SELECT * FROM caja_sesiones WHERE estado = 'abierta' LIMIT 1");
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
     * Busca la última sesión de caja cerrada.
     * @return Caja|null
     */
    public static function obtenerUltimaSesionCerrada()
    {
        // Obtenemos la instancia de la conexión
        $conexion = ConexionDB::getInstancia()->getConexion();
        // Ejecutamos la consulta
        $stmt = $conexion->query("SELECT * FROM caja_sesiones WHERE estado = 'cerrada' ORDER BY fechaCierre DESC LIMIT 1");
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
     * Abre una nueva sesión de caja.
     * @param int $idUsuario
     * @param float $importeInicial
     * @return Caja|bool
     */
    public static function abrir($idUsuario, $importeInicial)
    {
        // Verificar si ya hay una sesión de caja abierta
        if (self::obtenerSesionAbierta()) {
            return false;
        }

        // Obtenemos la instancia de la conexión
        $conexion = ConexionDB::getInstancia()->getConexion();
        // Preparamos la consulta
        $stmt = $conexion->prepare(
            "INSERT INTO caja_sesiones (idUsuario, importeInicial, importeActual, estado) 
             VALUES (:idUsuario, :importeInicial, :importeActual, 'abierta')"
        );
        // Vinculamos los parámetros
        $stmt->bindParam(':idUsuario', $idUsuario, PDO::PARAM_INT);
        $stmt->bindParam(':importeInicial', $importeInicial);
        $stmt->bindParam(':importeActual', $importeInicial); // Al empezar, el actual es el inicial.

        // Ejecutamos la consulta
        if ($stmt->execute()) {
            // Si la consulta se ejecuta correctamente, devolvemos la sesión de caja abierta
            return self::obtenerSesionAbierta();
        }
        return false;
    }

    /**
     * Actualiza el importe actual de la caja.
     * @param float $variacion Importe a sumar (recibido - cambio)
     * @return bool
     */
    public function actualizarEfectivo($variacion)
    {
        // Obtenemos la instancia de la conexión
        $conexion = ConexionDB::getInstancia()->getConexion();
        // Preparamos la consulta
        $stmt = $conexion->prepare(
            "UPDATE caja_sesiones SET importeActual = importeActual + :variacion WHERE id = :id"
        );
        // Vinculamos los parámetros
        $stmt->bindParam(':variacion', $variacion);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Cierra la sesión de caja actual.
     * @return bool
     */
    public function cerrar()
    {
        // Obtenemos la instancia de la conexión
        $conexion = ConexionDB::getInstancia()->getConexion();
        // Preparamos la consulta
        $stmt = $conexion->prepare(
            "UPDATE caja_sesiones SET estado = 'cerrada', fechaCierre = CURRENT_TIMESTAMP WHERE id = :id"
        );
        // Vinculamos los parámetros
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        // Ejecutamos la consulta
        return $stmt->execute();
    }

    /**
     * Crea una instancia de Caja desde un array.
     * @param array $fila
     * @return Caja
     */
    private static function crearDesdeArray($fila)
    {
        // Creamos una nueva instancia de Caja
        $caja = new Caja();
        // Establecemos los valores de la instancia
        $caja->setId($fila['id']);
        $caja->setIdUsuario($fila['idUsuario']);
        $caja->setFechaApertura($fila['fechaApertura']);
        $caja->setFechaCierre($fila['fechaCierre']);
        $caja->setImporteInicial($fila['importeInicial']);
        $caja->setImporteActual($fila['importeActual']);
        $caja->setEstado($fila['estado']);
        // Devolvemos la instancia
        return $caja;
    }
}
