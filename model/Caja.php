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
    private $cambio; // Cambio guardado para el siguiente turno
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
    public function getCambio()
    {
        return $this->cambio;
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
    public function setCambio($cambio)
    {
        $this->cambio = $cambio;
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
     * @param float $cambioOptional Cambio recovery from previous session (opcional)
     * @return Caja|bool
     */
    public static function abrir($idUsuario, $importeInicial, $cambioOptional = 0)
    {
        // Verificar si ya hay una sesión de caja abierta
        if (self::obtenerSesionAbierta()) {
            return false;
        }

        // Obtenemos la instancia de la conexión
        $conexion = ConexionDB::getInstancia()->getConexion();

        // Si se proporciona un cambio recovery, lo sumamos al importe inicial
        $importeTotal = $importeInicial + $cambioOptional;

        // Preparamos la consulta
        $stmt = $conexion->prepare(
            "INSERT INTO caja_sesiones (idUsuario, importeInicial, importeActual, cambio, estado) 
             VALUES (:idUsuario, :importeInicial, :importeActual, 0, 'abierta')"
        );
        // Vinculamos los parámetros
        $stmt->bindParam(':idUsuario', $idUsuario, PDO::PARAM_INT);
        $stmt->bindParam(':importeInicial', $importeTotal);
        $stmt->bindParam(':importeActual', $importeTotal);

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
     * @param float $cambioOptional Cambio a guardar para el siguiente turno (opcional)
     * @return bool
     */
    public function cerrar($cambioOptional = null)
    {
        // Obtenemos la instancia de la conexión
        $conexion = ConexionDB::getInstancia()->getConexion();

        // Si se proporciona un cambio, lo guardamos; si no, usamos el importe actual como cambio por defecto
        $cambio = $cambioOptional !== null ? $cambioOptional : $this->importeActual;

        // Preparamos la consulta
        $stmt = $conexion->prepare(
            "UPDATE caja_sesiones SET estado = 'cerrada', fechaCierre = CURRENT_TIMESTAMP, cambio = :cambio WHERE id = :id"
        );
        // Vinculamos los parámetros
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        $stmt->bindParam(':cambio', $cambio, PDO::PARAM_STR);
        // Ejecutamos la consulta
        return $stmt->execute();
    }

    /**
     * Registra un retiro de dinero de la caja.
     * @param float $importe Importe a retirar
     * @param int $idUsuario ID del usuario que realiza el retiro
     * @param string|null $motivo Motivo del retiro (opcional)
     * @return bool
     */
    public function registrarRetiro($importe, $idUsuario, $motivo = null)
    {
        // Obtenemos la instancia de la conexión
        $conexion = ConexionDB::getInstancia()->getConexion();
        // Preparamos la consulta
        $stmt = $conexion->prepare(
            "INSERT INTO retiros (idCajaSesion, idUsuario, importe, motivo) VALUES (:idCajaSesion, :idUsuario, :importe, :motivo)"
        );
        // Vinculamos los parámetros
        $stmt->bindParam(':idCajaSesion', $this->id, PDO::PARAM_INT);
        $stmt->bindParam(':idUsuario', $idUsuario, PDO::PARAM_INT);
        $stmt->bindParam(':importe', $importe, PDO::PARAM_STR);
        $stmt->bindParam(':motivo', $motivo, PDO::PARAM_STR);
        return $stmt->execute();
    }

    /**
     * Obtiene todos los retiros de una sesión de caja.
     * @return array
     */
    public function getRetiros()
    {
        // Obtenemos la instancia de la conexión
        $conexion = ConexionDB::getInstancia()->getConexion();
        // Preparamos la consulta
        $stmt = $conexion->prepare(
            "SELECT r.*, u.nombre as usuario_nombre FROM retiros r 
             LEFT JOIN usuarios u ON r.idUsuario = u.id 
             WHERE r.idCajaSesion = :idCajaSesion 
             ORDER BY r.fecha DESC"
        );
        $stmt->bindParam(':idCajaSesion', $this->id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene el total de retiros de una sesión de caja.
     * @return float
     */
    public function getTotalRetiros()
    {
        // Obtenemos la instancia de la conexión
        $conexion = ConexionDB::getInstancia()->getConexion();
        // Preparamos la consulta
        $stmt = $conexion->prepare(
            "SELECT COALESCE(SUM(importe), 0) as total FROM retiros WHERE idCajaSesion = :idCajaSesion"
        );
        $stmt->bindParam(':idCajaSesion', $this->id, PDO::PARAM_INT);
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return (float) $resultado['total'];
    }

    /**
     * Obtiene el total de retiros de una sesión de caja por su ID.
     * @param int $idCajaSesion ID de la sesión de caja
     * @return float
     */
    public static function getTotalRetirosPorSesion($idCajaSesion)
    {
        // Obtenemos la instancia de la conexión
        $conexion = ConexionDB::getInstancia()->getConexion();
        // Preparamos la consulta
        $stmt = $conexion->prepare(
            "SELECT COALESCE(SUM(importe), 0) as total FROM retiros WHERE idCajaSesion = :idCajaSesion"
        );
        $stmt->bindParam(':idCajaSesion', $idCajaSesion, PDO::PARAM_INT);
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return (float) $resultado['total'];
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
        $caja->setCambio(isset($fila['cambio']) ? $fila['cambio'] : 0);
        $caja->setEstado($fila['estado']);
        // Devolvemos la instancia
        return $caja;
    }

    // ======================== MÉTODOS DE ARQUEO ========================

    /**
     * Obtiene los datos para el arqueo de caja.
     * @return array
     */
    public function getDatosArqueo()
    {
        $conexion = ConexionDB::getInstancia()->getConexion();

        // Obtener ventas en efectivo
        $stmtVentas = $conexion->prepare("
            SELECT COALESCE(SUM(v.total), 0) as total 
            FROM ventas v 
            WHERE v.idSesionCaja = :idSesion 
            AND v.metodoPago = 'Efectivo'
        ");
        $stmtVentas->bindParam(':idSesion', $this->id, PDO::PARAM_INT);
        $stmtVentas->execute();
        $ventasEfectivo = (float) $stmtVentas->fetch(PDO::FETCH_ASSOC)['total'];

        // Obtener devoluciones en efectivo
        $stmtDev = $conexion->prepare("
            SELECT COALESCE(SUM(d.importeTotal), 0) as total 
            FROM devoluciones d 
            WHERE d.idSesionCaja = :idSesion
        ");
        $stmtDev->bindParam(':idSesion', $this->id, PDO::PARAM_INT);
        $stmtDev->execute();
        $devolucionesEfectivo = (float) $stmtDev->fetch(PDO::FETCH_ASSOC)['total'];

        // Obtener retiros
        $retiros = $this->getTotalRetiros();

        // Calcular efectivo esperado
        // fórmula: fondo inicial + ventas efectivo - devoluciones efectivo - retiros
        $efectivoEsperado = $this->importeInicial + $ventasEfectivo - $devolucionesEfectivo - $retiros;

        return [
            'fondoInicial' => $this->importeInicial,
            'ventasEfectivo' => $ventasEfectivo,
            'devolucionesEfectivo' => $devolucionesEfectivo,
            'retiros' => $retiros,
            'efectivoEsperado' => $efectivoEsperado,
            'efectivoActual' => $this->importeActual
        ];
    }

    /**
     * Registra un arqueo de caja.
     * @param int $idUsuario
     * @param float $efectivoContado
     * @param string $detalleConteo JSON con detalle de billetes y monedas
     * @param string|null $observaciones
     * @param string $tipoArqueo
     * @return bool
     */
    public function registrarArqueo($idUsuario, $efectivoContado, $detalleConteo = null, $observaciones = null, $tipoArqueo = 'cierre')
    {
        $conexion = ConexionDB::getInstancia()->getConexion();

        // Obtener datos para el arqueo
        $datos = $this->getDatosArqueo();
        $efectivoEsperado = $datos['efectivoEsperado'];
        $diferencia = $efectivoContado - $efectivoEsperado;

        $stmt = $conexion->prepare("
            INSERT INTO arqueos_caja 
            (idCajaSesion, idUsuario, fondoInicial, ventasEfectivo, devolucionesEfectivo, 
             retiros, efectivoEsperado, efectivoContado, diferencia, detalleConteo, observaciones, tipoArqueo)
            VALUES 
            (:idCajaSesion, :idUsuario, :fondoInicial, :ventasEfectivo, :devolucionesEfectivo,
             :retiros, :efectivoEsperado, :efectivoContado, :diferencia, :detalleConteo, :observaciones, :tipoArqueo)
        ");

        $stmt->bindParam(':idCajaSesion', $this->id, PDO::PARAM_INT);
        $stmt->bindParam(':idUsuario', $idUsuario, PDO::PARAM_INT);
        $stmt->bindParam(':fondoInicial', $datos['fondoInicial']);
        $stmt->bindParam(':ventasEfectivo', $datos['ventasEfectivo']);
        $stmt->bindParam(':devolucionesEfectivo', $datos['devolucionesEfectivo']);
        $stmt->bindParam(':retiros', $datos['retiros']);
        $stmt->bindParam(':efectivoEsperado', $efectivoEsperado);
        $stmt->bindParam(':efectivoContado', $efectivoContado);
        $stmt->bindParam(':diferencia', $diferencia);
        $stmt->bindParam(':detalleConteo', $detalleConteo);
        $stmt->bindParam(':observaciones', $observaciones);
        $stmt->bindParam(':tipoArqueo', $tipoArqueo);

        return $stmt->execute();
    }

    /**
     * Obtiene el último arqueo de una sesión.
     * @return array|null
     */
    public function getUltimoArqueo()
    {
        $conexion = ConexionDB::getInstancia()->getConexion();
        $stmt = $conexion->prepare("
            SELECT a.*, u.nombre as usuario_nombre 
            FROM arqueos_caja a
            LEFT JOIN usuarios u ON a.idUsuario = u.id
            WHERE a.idCajaSesion = :idSesion
            ORDER BY a.fechaArqueo DESC
            LIMIT 1
        ");
        $stmt->bindParam(':idSesion', $this->id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene el historial de arqueos de una sesión.
     * @return array
     */
    public function getHistorialArqueos()
    {
        $conexion = ConexionDB::getInstancia()->getConexion();
        $stmt = $conexion->prepare("
            SELECT a.*, u.nombre as usuario_nombre 
            FROM arqueos_caja a
            LEFT JOIN usuarios u ON a.idUsuario = u.id
            WHERE a.idCajaSesion = :idSesion
            ORDER BY a.fechaArqueo DESC
        ");
        $stmt->bindParam(':idSesion', $this->id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
