<?php

/*
 * Autor: Alberto Méndez 
 * 
 * Clase modelo para la gestión de las sesiones de caja (apertura y cierre).
 */

require_once(__DIR__ . '/../core/conexionDB.php');

class Caja
{
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
        $conexion = ConexionDB::getInstancia()->getConexion();
        $stmt = $conexion->query("SELECT * FROM caja_sesiones WHERE estado = 'abierta' LIMIT 1");
        $fila = $stmt->fetch();
        if ($fila) {
            return self::crearDesdeArray($fila);
        }
        return null;
    }

    /**
     * Busca la última sesión de caja cerrada.
     * @return Caja|null
     */
    public static function obtenerUltimaSesionCerrada()
    {
        $conexion = ConexionDB::getInstancia()->getConexion();
        $stmt = $conexion->query("SELECT * FROM caja_sesiones WHERE estado = 'cerrada' ORDER BY fechaCierre DESC LIMIT 1");
        $fila = $stmt->fetch();
        if ($fila) {
            return self::crearDesdeArray($fila);
        }
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
        // Verificar si ya hay una abierta
        if (self::obtenerSesionAbierta()) {
            return false;
        }

        $conexion = ConexionDB::getInstancia()->getConexion();
        $stmt = $conexion->prepare(
            "INSERT INTO caja_sesiones (idUsuario, importeInicial, importeActual, estado) 
             VALUES (:idUsuario, :importeInicial, :importeActual, 'abierta')"
        );
        $stmt->bindParam(':idUsuario', $idUsuario, PDO::PARAM_INT);
        $stmt->bindParam(':importeInicial', $importeInicial);
        $stmt->bindParam(':importeActual', $importeInicial); // Al empezar, el actual es el inicial.

        if ($stmt->execute()) {
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
        $conexion = ConexionDB::getInstancia()->getConexion();
        $stmt = $conexion->prepare(
            "UPDATE caja_sesiones SET importeActual = importeActual + :variacion WHERE id = :id"
        );
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
        $conexion = ConexionDB::getInstancia()->getConexion();
        $stmt = $conexion->prepare(
            "UPDATE caja_sesiones SET estado = 'cerrada', fechaCierre = CURRENT_TIMESTAMP WHERE id = :id"
        );
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    private static function crearDesdeArray($fila)
    {
        $caja = new Caja();
        $caja->setId($fila['id']);
        $caja->setIdUsuario($fila['idUsuario']);
        $caja->setFechaApertura($fila['fechaApertura']);
        $caja->setFechaCierre($fila['fechaCierre']);
        $caja->setImporteInicial($fila['importeInicial']);
        $caja->setImporteActual($fila['importeActual']);
        $caja->setEstado($fila['estado']);
        return $caja;
    }
}
