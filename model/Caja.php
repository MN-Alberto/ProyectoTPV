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
    /** 
     * @var int|null Identificador único de la sesión de caja. 
     */
    private $id;
    /** 
     * @var int|null ID del usuario encargado que realizó la apertura de la caja. 
     */
    private $idUsuario;
    /** 
     * @var string|null Momento exacto (fecha y hora) en que se abrió la sesión de caja. 
     */
    private $fechaApertura;
    /** 
     * @var string|null Momento exacto (fecha y hora) en que se cerró la sesión de caja. 
     */
    private $fechaCierre;
    /** 
     * @var float|null Cantidad de dinero en efectivo con la que se inició el turno. 
     */
    private $importeInicial;
    /** 
     * @var float|null Cantidad actual de dinero en efectivo acumulado en el cajón durante la sesión. 
     */
    private $importeActual;
    /** 
     * @var float|null Cantidad de dinero destinada a servir como fondo de cambio para el siguiente turno. 
     */
    private $cambio; // Cambio guardado para el siguiente turno
    /** 
     * @var string|null Estado actual de la sesión (ej: 'abierta' cuando está en uso, 'cerrada' al finalizar el turno). 
     */
    private $estado; // 'abierta', 'cerrada'
    /** 
     * @var string|null Motivo por el cual se interrumpió la sesión (ej: 'pausa' para descansos, 'turno' para cambio de empleado). 
     */
    private $interrupcionTipo; // 'pausa', 'turno'
    /** 
     * @var int|null ID del usuario que se encontraba operando la caja en el momento de la interrupción. 
     */
    private $interrupcionUsuarioId;
    /** 
     * @var string|null Nombre del usuario responsable en el momento de la interrupción del servicio. 
     */
    private $interrupcionUsuarioNombre;
    /** 
     * @var string|null Fecha y hora en la que se registró la última interrupción de la sesión. 
     */
    private $interrupcionFecha;

    // ======================== GETTERS ========================
    /** 
     * Obtiene el ID de la sesión de caja.
     * @return int|null 
     */
    public function getId()
    {
        return $this->id;
    }
    /** 
     * Obtiene el ID del usuario que abrió la caja.
     * @return int|null 
     */
    public function getIdUsuario()
    {
        return $this->idUsuario;
    }
    /** 
     * Obtiene la fecha y hora de apertura.
     * @return string|null 
     */
    public function getFechaApertura()
    {
        return $this->fechaApertura;
    }
    /** 
     * Obtiene la fecha y hora de cierre.
     * @return string|null 
     */
    public function getFechaCierre()
    {
        return $this->fechaCierre;
    }
    /** 
     * Obtiene el importe con el que se inició la sesión.
     * @return float|null 
     */
    public function getImporteInicial()
    {
        return $this->importeInicial;
    }
    /** 
     * Obtiene el importe actual acumulado en efectivo.
     * @return float|null 
     */
    public function getImporteActual()
    {
        return $this->importeActual;
    }
    /** 
     * Obtiene la cantidad reservada para el fondo de cambio.
     * @return float|null 
     */
    public function getCambio()
    {
        return $this->cambio;
    }
    /** 
     * Obtiene el estado actual de la caja.
     * @return string|null 
     */
    public function getEstado()
    {
        return $this->estado;
    }
    /** 
     * Obtiene el tipo de interrupción registrada.
     * @return string|null 
     */
    public function getInterrupcionTipo()
    {
        return $this->interrupcionTipo;
    }
    /** 
     * Obtiene el ID del usuario en el momento del parón.
     * @return int|null 
     */
    public function getInterrupcionUsuarioId()
    {
        return $this->interrupcionUsuarioId;
    }
    /** 
     * Obtiene el nombre del usuario responsable del parón.
     * @return string|null 
     */
    public function getInterrupcionUsuarioNombre()
    {
        return $this->interrupcionUsuarioNombre;
    }
    /** 
     * Obtiene la fecha de la última interrupción.
     * @return string|null 
     */
    public function getInterrupcionFecha()
    {
        return $this->interrupcionFecha;
    }

    // ======================== SETTERS ========================
    /** 
     * Establece el ID único de la sesión de caja.
     * @param int $id 
     */
    public function setId($id)
    {
        $this->id = $id;
    }
    /** 
     * Establece el ID del usuario que inicia la sesión.
     * @param int $idUsuario 
     */
    public function setIdUsuario($idUsuario)
    {
        $this->idUsuario = $idUsuario;
    }
    /** 
     * Define el momento de apertura de la caja.
     * @param string $fechaApertura Formato Y-m-d H:i:s
     */
    public function setFechaApertura($fechaApertura)
    {
        $this->fechaApertura = $fechaApertura;
    }
    /** 
     * Define el momento de cierre de la caja.
     * @param string $fechaCierre Formato Y-m-d H:i:s
     */
    public function setFechaCierre($fechaCierre)
    {
        $this->fechaCierre = $fechaCierre;
    }
    /** 
     * Establece el importe en efectivo al abrir el turno.
     * @param float $importeInicial 
     */
    public function setImporteInicial($importeInicial)
    {
        $this->importeInicial = $importeInicial;
    }
    /** 
     * Establece el importe acumulado actual en el cajón.
     * @param float $importeActual 
     */
    public function setImporteActual($importeActual)
    {
        $this->importeActual = $importeActual;
    }
    /** 
     * Define la cantidad reservada para el fondo de cambio.
     * @param float $cambio 
     */
    public function setCambio($cambio)
    {
        $this->cambio = $cambio;
    }
    /** 
     * Establece el estado operativo de la sesión.
     * @param string $estado 'abierta' o 'cerrada'.
     */
    public function setEstado($estado)
    {
        $this->estado = $estado;
    }
    /** 
     * Define el motivo de un parón temporal en el servicio.
     * @param string $tipo 'pausa' o 'turno'.
     */
    public function setInterrupcionTipo($tipo)
    {
        $this->interrupcionTipo = $tipo;
    }
    /** 
     * Establece el ID del usuario que deja la caja en espera.
     * @param int $id 
     */
    public function setInterrupcionUsuarioId($id)
    {
        $this->interrupcionUsuarioId = $id;
    }
    /** 
     * Establece el nombre del empleado que causa la interrupción.
     * @param string $nombre 
     */
    public function setInterrupcionUsuarioNombre($nombre)
    {
        $this->interrupcionUsuarioNombre = $nombre;
    }
    /** 
     * Define el momento exacto de la interrupción del turno.
     * @param string $fecha Formato Y-m-d H:i:s
     */
    public function setInterrupcionFecha($fecha)
    {
        $this->interrupcionFecha = $fecha;
    }

    /**
     * Recupera la sesión de caja que se encuentra en estado 'abierta' en este momento.
     * Solo debería existir una sesión abierta simultáneamente en el sistema.
     * 
     * @return Caja|null Objeto Caja activo o null si no hay turno iniciado.
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
     * Localiza el último registro de una caja que fue finalizada con éxito.
     * Útil para recuperar el fondo de cambio del turno anterior.
     * 
     * @return Caja|null Datos de la sesión cerrada más reciente.
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
     * Inicia un nuevo turno o jornada de caja registrando el fondo inicial disponible.
     * 
     * @param int $idUsuario Empleado responsable de la apertura.
     * @param float $importeInicial Dinero físico aportado al cajón.
     * @param float $cambioOptional Fondo de maniobra recuperado (opcional).
     * @return Caja|bool Objeto de la nueva sesión si se abre con éxito, false en caso contrario.
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
     * Incrementa o decrementa el saldo de efectivo en el cajón de forma inmediata.
     * Se usa habitualmente al procesar una venta o una devolución.
     * 
     * @param float $variacion Cantidad neta a sumar (positiva) o restar (negativa).
     * @return bool Éxito de la operación en BD.
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
     * Finaliza la sesión de caja actual, registrando la hora de cierre y el arqueo final.
     * 
     * @param float $cambioOptional Importe que se deja en el cajón para el turno siguiente (opcional).
     * @return bool True si el cierre se registró correctamente.
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
     * Documenta una salida de efectivo de la caja (ej: pago a proveedores o gastos menores).
     * 
     * @param float $importe Cantidad extraída.
     * @param int $idUsuario Empleado que autoriza el movimiento.
     * @param string|null $motivo Justificación breve de la extracción.
     * @return bool Confirmación del registro.
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
        $caja->setInterrupcionTipo($fila['interrupcion_tipo'] ?? null);
        $caja->setInterrupcionUsuarioId($fila['interrupcion_usuario_id'] ?? null);
        $caja->setInterrupcionUsuarioNombre($fila['interrupcion_usuario_nombre'] ?? null);
        $caja->setInterrupcionFecha($fila['interrupcion_fecha'] ?? null);
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
            WHERE (v.idSesionCaja = :idSesion OR (v.idSesionCaja IS NULL AND v.fecha >= :fechaApertura AND (v.fecha <= :fechaCierre OR :fechaCierre2 IS NULL)))
            AND v.metodoPago = 'efectivo'
            AND v.estado = 'completada'
        ");
        $stmtVentas->bindParam(':idSesion', $this->id, PDO::PARAM_INT);
        $stmtVentas->bindParam(':fechaApertura', $this->fechaApertura);
        $stmtVentas->bindParam(':fechaCierre', $this->fechaCierre);
        $stmtVentas->bindParam(':fechaCierre2', $this->fechaCierre);
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

        // calcular efectivo esperado
        // fórmula: fondo inicial + ventas efectivo (netas) - retiros
        // NOTA: ventasEfectivo ya tiene restadas las devoluciones porque son registros negativos en la tabla 'ventas'
        $efectivoEsperado = $this->importeInicial + $ventasEfectivo - $retiros;

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

    /**
     * Registra una interrupción temporal en la sesión (pausa o cambio de turno).
     * @param int $idUsuario
     * @param string $nombreUsuario
     * @param string $tipo
     * @return bool
     */
    public function registrarInterrupcion($idUsuario, $nombreUsuario, $tipo)
    {
        $conexion = ConexionDB::getInstancia()->getConexion();
        $stmt = $conexion->prepare("
            UPDATE caja_sesiones 
            SET interrupcion_tipo = :tipo, 
                interrupcion_usuario_id = :idUsuario, 
                interrupcion_usuario_nombre = :nombreUsuario, 
                interrupcion_fecha = CURRENT_TIMESTAMP 
            WHERE id = :id
        ");
        $stmt->bindParam(':tipo', $tipo);
        $stmt->bindParam(':idUsuario', $idUsuario, PDO::PARAM_INT);
        $stmt->bindParam(':nombreUsuario', $nombreUsuario);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Limpia los datos de interrupción de la sesión actual.
     * @return bool
     */
    public function limpiarInterrupcion()
    {
        $conexion = ConexionDB::getInstancia()->getConexion();
        $stmt = $conexion->prepare("
            UPDATE caja_sesiones 
            SET interrupcion_tipo = NULL, 
                interrupcion_usuario_id = NULL, 
                interrupcion_usuario_nombre = NULL, 
                interrupcion_fecha = NULL 
            WHERE id = :id
        ");
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
