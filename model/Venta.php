<?php

/**
 * Clase modelo para la gestión de ventas/tickets del TPV.
 * 
 * @author Alberto Méndez
 * @version 1.2 (02/03/2026)
 */

// Requerimos el fichero de conexión a la base de datos
require_once(__DIR__ . '/../core/conexionDB.php');
require_once(__DIR__ . '/../core/Verifactu.php');

class Venta
{

    /** 
     * @var int|null Identificador único de la venta. 
     */
    private $id;
    /** 
     * @var int|null ID del usuario (cajero/admin) que procesó la venta. 
     */
    private $idUsuario;
    /** 
     * @var string|null Fecha y hora en la que se cerró la transacción. 
     */
    private $fecha;
    /** 
     * @var float|null Importe total de la operación (incluyendo impuestos y descuentos). 
     */
    private $total;
    /** 
     * @var string|null Modalidad de pago utilizada (ej: 'efectivo', 'tarjeta', 'bizum'). 
     */
    private $metodoPago; // 'efectivo', 'tarjeta', 'bizum'
    /** 
     * @var string|null Situación administrativa de la venta (ej: 'completada', 'anulada'). 
     */
    private $estado; // 'completada', 'anulada'
    /** 
     * @var string|null Categoría legal del comprobante emitido (ej: 'ticket', 'factura'). 
     */
    private $tipoDocumento; // 'ticket', 'factura'
    /** 
     * @var bool|int Estado de la transacción (1 si la venta está finalizada y cerrada). 
     */
    private $cerrada; // 0 o 1
    /** 
     * @var float|null Cantidad de dinero físico entregada por el cliente. 
     */
    private $importeEntregado;
    /** 
     * @var float|null Diferencia monetaria devuelta al cliente tras el pago en efectivo. 
     */
    private $cambioDevuelto;
    /** 
     * @var int|null ID de la tarifa global aplicada a toda la venta. 
     */
    private $idTarifa; // ID de la tarifa prefijada aplicada
    /** 
     * @var string|null Documento Nacional de Identidad del cliente registrado. 
     */
    private $clienteDni; // DNI del cliente asociado a la venta
    /** 
     * @var int|null Referencia a la sesión de caja en la que se integró esta venta. 
     */
    private $idSesionCaja; // ID de la sesión de caja en la que se realizó la venta

    // Campos de cliente extendidos
    private $clienteNombre;
    private $clienteDireccion;
    private $clienteObservaciones;

    // Campos de descuento
    /** 
     * @var string|null Tipo de bonificación general aplicada (ej: 'porcentaje', 'fijo'). 
     */
    private $descuentoTipo;
    /** 
     * @var float|null Valor numérico de la reducción aplicada al total. 
     */
    private $descuentoValor;
    /** 
     * @var string|null Código de campaña o cupón utilizado para el descuento general. 
     */
    private $descuentoCupon;
    /** 
     * @var string|null Tipo de ajuste automático por tarifa aplicada. 
     */
    private $descuentoTarifaTipo;
    /** 
     * @var float|null Valor del ajuste por tarifa. 
     */
    private $descuentoTarifaValor;
    /** 
     * @var string|null Identificador promocional de la tarifa. 
     */
    private $descuentoTarifaCupon;
    /** 
     * @var string|null Tipo de rebaja introducida manualmente por el cajero. 
     */
    private $descuentoManualTipo;
    /** 
     * @var float|null Valor de la rebaja manual. 
     */
    private $descuentoManualValor;
    /** 
     * @var string|null Justificación o código del descuento manual. 
     */
    private $descuentoManualCupon;

    // Campos de número correlativo
    private $serie;
    private $numero;

    // Campos de puntos
    private $puntosGanados;
    private $puntosCanjeados;
    private $puntosBalance;

    // Mensaje personalizado
    private $mensajePersonalizado;

    // Desglose de pago mixto (JSON)
    private $desglosePago;

    // Idioma del ticket
    private $idioma_ticket;

    // Verifactu
    private $hash;
    private $hashPrevio;
    private $xmlDatos;
    private $estadoAeat;
    private $csvAeat;
    private $errorAeat;

    // Verifactu: rectificativas y anulaciones extendidas
    private $esRectificativa;
    private $idDocumentoOriginal;
    private $tipoFacturaVerifactu;
    private $hashAnulacion;
    private $csvAnulacion;
    private $fechaAnulacion;
    
    /** @var array|null Datos temporales del registro anterior para el XML */
    public $datosRegistroAnterior;

    // ======================== GETTERS ========================

    /** 
     * Obtiene el ID de la venta.
     * @return int|null 
     */
    public function getId()
    {
        return $this->id;
    }

    /** 
     * Obtiene el ID del usuario que procesó la transacción.
     * @return int|null 
     */
    public function getIdUsuario()
    {
        return $this->idUsuario;
    }

    /** 
     * Obtiene la fecha y hora de la venta.
     * @return string|null 
     */
    public function getFecha()
    {
        return $this->fecha;
    }

    /** 
     * Obtiene el importe total acumulado.
     * @return float|null 
     */
    public function getTotal()
    {
        return $this->total;
    }

    /** 
     * Obtiene el método de pago empleado.
     * @return string|null 
     */
    public function getMetodoPago()
    {
        return $this->metodoPago;
    }

    /** 
     * Obtiene el estado actual de la venta.
     * @return string|null 
     */
    public function getEstado()
    {
        return $this->estado;
    }

    /** 
     * Obtiene la naturaleza del comprobante legal.
     * @return string|null 
     */
    public function getTipoDocumento()
    {
        return $this->tipoDocumento;
    }

    /** 
     * Comprueba si la venta está bloqueada y cerrada.
     * @return bool|int 
     */
    public function getCerrada()
    {
        return $this->cerrada;
    }

    public function getSerie()
    {
        return $this->serie;
    }

    public function getNumero()
    {
        return $this->numero;
    }

    public function getNumeroCompleto()
    {
        if (!$this->serie || !$this->numero) {
            return '';
        }
        return $this->serie . str_pad($this->numero, 5, '0', STR_PAD_LEFT);
    }

    public function getClienteNombre()
    {
        return $this->clienteNombre;
    }

    public function getClienteDireccion()
    {
        return $this->clienteDireccion;
    }

    public function getClienteObservaciones()
    {
        return $this->clienteObservaciones;
    }

    public function getMensajePersonalizado()
    {
        return $this->mensajePersonalizado;
    }

    public function getDesglosePago()
    {
        return $this->desglosePago;
    }

    public function getIdiomaTicket()
    {
        return $this->idioma_ticket;
    }

    public function getHash()
    {
        return $this->hash;
    }
    public function getHashPrevio()
    {
        return $this->hashPrevio;
    }
    public function getXmlDatos()
    {
        return $this->xmlDatos;
    }
    public function getEstadoAeat()
    {
        return $this->estadoAeat;
    }
    public function getCsvAeat()
    {
        return $this->csvAeat;
    }
    public function getErrorAeat()
    {
        return $this->errorAeat;
    }

    // Getters rectificativa/anulación
    public function getEsRectificativa()
    {
        return $this->esRectificativa;
    }
    public function getIdDocumentoOriginal()
    {
        return $this->idDocumentoOriginal;
    }
    public function getTipoFacturaVerifactu()
    {
        return $this->tipoFacturaVerifactu;
    }
    public function getHashAnulacion()
    {
        return $this->hashAnulacion;
    }
    public function getCsvAnulacion()
    {
        return $this->csvAnulacion;
    }
    public function getFechaAnulacion()
    {
        return $this->fechaAnulacion;
    }

    // Setters rectificativa/anulación
    public function setEsRectificativa($v)
    {
        $this->esRectificativa = $v;
    }
    public function setIdDocumentoOriginal($v)
    {
        $this->idDocumentoOriginal = $v;
    }
    public function setTipoFacturaVerifactu($v)
    {
        $this->tipoFacturaVerifactu = $v;
    }
    public function setHashAnulacion($v)
    {
        $this->hashAnulacion = $v;
    }
    public function setCsvAnulacion($v)
    {
        $this->csvAnulacion = $v;
    }
    public function setFechaAnulacion($v)
    {
        $this->fechaAnulacion = $v;
    }
    public function setHash($v)
    {
        $this->hash = $v;
    }
    public function setHashPrevio($v)
    {
        $this->hashPrevio = $v;
    }
    public function setErrorAeat($v)
    {
        $this->errorAeat = $v;
    }

    // ======================== SETTERS ========================
    /** 
     * Establece el ID único de la venta.
     * @param int $id 
     */
    public function setId($id)
    {
        $this->id = $id;
    }
    /** 
     * Establece el ID del usuario que realizó la venta.
     * @param int $idUsuario 
     */
    public function setIdUsuario($idUsuario)
    {
        $this->idUsuario = $idUsuario;
    }
    /** 
     * Establece la fecha y hora de la transacción.
     * @param string $fecha Formato Y-m-d H:i:s
     */
    public function setFecha($fecha)
    {
        $this->fecha = $fecha;
    }
    /** 
     * Establece el importe total de la venta.
     * @param float $total 
     */
    public function setTotal($total)
    {
        $this->total = $total;
    }
    /** 
     * Define el método de pago utilizado.
     * @param string $metodoPago 'efectivo', 'tarjeta' o 'bizum'.
     */
    public function setMetodoPago($metodoPago)
    {
        $this->metodoPago = $metodoPago;
    }
    /** 
     * Establece el estado administrativo de la venta.
     * @param string $estado 'completada' o 'anulada'.
     */
    public function setEstado($estado)
    {
        $this->estado = $estado;
    }
    /** 
     * Define si el comprobante es un ticket o una factura legal.
     * @param string $tipoDocumento 'ticket' o 'factura'.
     */
    public function setTipoDocumento($tipoDocumento)
    {
        $this->tipoDocumento = $tipoDocumento;
    }
    /** 
     * Define si la venta ha sido cerrada definitivamente.
     * @param bool|int $cerrada 1 para cerrada, 0 para abierta.
     */
    public function setCerrada($cerrada)
    {
        $this->cerrada = $cerrada;
    }
    /** 
     * Establece la cantidad de efectivo entregada por el cliente.
     * @param float $importeEntregado 
     */
    public function setImporteEntregado($importeEntregado)
    {
        $this->importeEntregado = $importeEntregado;
    }
    /** 
     * Establece el cambio monetario devuelto al cliente.
     * @param float $cambioDevuelto 
     */
    public function setCambioDevuelto($cambioDevuelto)
    {
        $this->cambioDevuelto = $cambioDevuelto;
    }
    /** 
     * Asocia una tarifa específica a toda la transacción.
     * @param int $idTarifa 
     */
    public function setIdTarifa($idTarifa)
    {
        $this->idTarifa = $idTarifa;
    }
    /** 
     * Asocia un cliente a la venta mediante su DNI.
     * @param string|null $clienteDni 
     */
    public function setClienteDni($clienteDni)
    {
        $this->clienteDni = $clienteDni;
    }
    /** 
     * Vincula la venta a una sesión de caja activa.
     * @param int|null $idSesionCaja 
     */
    public function setIdSesionCaja($idSesionCaja)
    {
        $this->idSesionCaja = $idSesionCaja;
    }

    public function setSerie($serie)
    {
        $this->serie = $serie;
    }

    public function setNumero($numero)
    {
        $this->numero = $numero;
    }

    public function setClienteNombre($v)
    {
        $this->clienteNombre = $v;
    }

    public function setClienteDireccion($v)
    {
        $this->clienteDireccion = $v;
    }

    public function setClienteObservaciones($v)
    {
        $this->clienteObservaciones = $v;
    }

    public function setMensajePersonalizado($v)
    {
        $this->mensajePersonalizado = $v;
    }

    public function setDesglosePago($v)
    {
        $this->desglosePago = $v;
    }

    /**
     * Establece el idioma del ticket.
     * @param string $v 'es', 'en', 'fr', 'de', 'ru'
     */
    public function setIdiomaTicket($v)
    {
        $this->idioma_ticket = $v;
    }

    public function getImporteEntregado()
    {
        return $this->importeEntregado;
    }
    public function getCambioDevuelto()
    {
        return $this->cambioDevuelto;
    }

    public function getIdTarifa()
    {
        return $this->idTarifa;
    }

    public function getClienteDni()
    {
        return $this->clienteDni;
    }

    public function getIdSesionCaja()
    {
        return $this->idSesionCaja;
    }

    // Getters y Setters de descuentos
    public function getDescuentoTipo()
    {
        return $this->descuentoTipo;
    }
    public function setDescuentoTipo($v)
    {
        $this->descuentoTipo = $v;
    }

    public function getDescuentoValor()
    {
        return $this->descuentoValor;
    }
    public function setDescuentoValor($v)
    {
        $this->descuentoValor = $v;
    }

    public function getDescuentoCupon()
    {
        return $this->descuentoCupon;
    }
    public function setDescuentoCupon($v)
    {
        $this->descuentoCupon = $v;
    }

    public function getDescuentoTarifaTipo()
    {
        return $this->descuentoTarifaTipo;
    }
    public function setDescuentoTarifaTipo($v)
    {
        $this->descuentoTarifaTipo = $v;
    }

    public function getDescuentoTarifaValor()
    {
        return $this->descuentoTarifaValor;
    }
    public function setDescuentoTarifaValor($v)
    {
        $this->descuentoTarifaValor = $v;
    }

    public function getDescuentoTarifaCupon()
    {
        return $this->descuentoTarifaCupon;
    }
    public function setDescuentoTarifaCupon($v)
    {
        $this->descuentoTarifaCupon = $v;
    }

    public function getDescuentoManualTipo()
    {
        return $this->descuentoManualTipo;
    }
    public function setDescuentoManualTipo($v)
    {
        $this->descuentoManualTipo = $v;
    }

    public function getDescuentoManualValor()
    {
        return $this->descuentoManualValor;
    }
    public function setDescuentoManualValor($v)
    {
        $this->descuentoManualValor = $v;
    }

    public function getDescuentoManualCupon()
    {
        return $this->descuentoManualCupon;
    }
    public function setDescuentoManualCupon($v)
    {
        $this->descuentoManualCupon = $v;
    }

    public function getPuntosGanados()
    {
        return $this->puntosGanados;
    }
    public function setPuntosGanados($v)
    {
        $this->puntosGanados = $v;
    }

    public function getPuntosCanjeados()
    {
        return $this->puntosCanjeados;
    }
    public function setPuntosCanjeados($v)
    {
        $this->puntosCanjeados = $v;
    }

    public function getPuntosBalance()
    {
        return $this->puntosBalance;
    }
    public function setPuntosBalance($v)
    {
        $this->puntosBalance = $v;
    }

    // ======================== MÉTODOS AUXILIARES DE TABLA ========================

    /**
     * Devuelve el nombre de la tabla real según el tipo de documento.
     * @return string 'tickets' o 'facturas'
     */
    private function getTabla()
    {
        return ($this->tipoDocumento === 'factura') ? 'facturas' : 'tickets';
    }

    /**
     * Determina en qué tabla real se encuentra una venta por su ID.
     * Busca primero en tickets, luego en facturas.
     * @param int $id
     * @return string|null 'tickets', 'facturas' o null si no existe
     */
    private static function getTablaById($id)
    {
        $conexion = ConexionDB::getInstancia()->getConexion();
        $stmt = $conexion->prepare("SELECT id FROM tickets WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        if ($stmt->fetch()) {
            return 'tickets';
        }
        $stmt = $conexion->prepare("SELECT id FROM facturas WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        if ($stmt->fetch()) {
            return 'facturas';
        }
        return null;
    }

    // ======================== MÉTODOS CRUD ========================

    /**
     * Recupera un registro de venta (ticket o factura) utilizando su ID único.
     * 
     * @param int $id El identificador de la venta.
     * @return Venta|null El objeto Venta si existe, null si no se encuentra.
     */
    public static function buscarPorId($id)
    {
        // Obtenemos la instancia de la conexión
        $conexion = ConexionDB::getInstancia()->getConexion();
        // Preparamos la consulta
        $stmt = $conexion->prepare("SELECT * FROM ventas WHERE id = :id");
        // Vinculamos los parámetros
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        // Ejecutamos la consulta
        $stmt->execute();
        // Obtenemos la fila
        $fila = $stmt->fetch();
        // Si se encuentra la fila, creamos una nueva venta
        if ($fila) {
            return self::crearDesdeArray($fila);
        }
        // Si no se encuentra la fila, devolvemos null
        return null;
    }

    /**
     * Obtiene el listado histórico de todas las ventas registradas, ordenadas por fecha descendente.
     * 
     * @return array Colección de objetos Venta.
     */
    public static function obtenerTodas()
    {
        // Obtenemos la instancia de la conexión
        $conexion = ConexionDB::getInstancia()->getConexion();
        // Preparamos la consulta
        $stmt = $conexion->query("SELECT * FROM ventas WHERE es_rectificativa = 0 ORDER BY fecha DESC");
        // Creamos un array para guardar las ventas
        $ventas = [];
        // Recorremos las filas
        while ($fila = $stmt->fetch()) {
            // Creamos una nueva venta y la añadimos al array
            $ventas[] = self::crearDesdeArray($fila);
        }
        // Devolvemos las ventas
        return $ventas;
    }

    /**
     * Filtra las ventas realizadas por un empleado específico.
     * 
     * @param int $idUsuario ID del cajero o administrador.
     * @return array Listado de ventas procesadas por dicho usuario.
     */
    public static function obtenerPorUsuario($idUsuario)
    {
        // Obtenemos la instancia de la conexión
        $conexion = ConexionDB::getInstancia()->getConexion();
        // Preparamos la consulta
        $stmt = $conexion->prepare(
            "SELECT * FROM ventas WHERE idUsuario = :idUsuario AND es_rectificativa = 0 ORDER BY fecha DESC"
        );
        // Vinculamos los parámetros
        $stmt->bindParam(':idUsuario', $idUsuario, PDO::PARAM_INT);
        // Ejecutamos la consulta
        $stmt->execute();
        // Creamos un array para guardar las ventas
        $ventas = [];
        // Recorremos las filas
        while ($fila = $stmt->fetch()) {
            // Creamos una nueva venta y la añadimos al array
            $ventas[] = self::crearDesdeArray($fila);
        }
        // Devolvemos las ventas
        return $ventas;
    }

    /**
     * Recupera el conjunto de ventas realizadas en un rango temporal determinado.
     * 
     * @param string $fechaInicio Fecha de inicio (Y-m-d).
     * @param string $fechaFin Fecha de fin (Y-m-d).
     * @return array Ventas dentro del intervalo.
     */
    public static function obtenerPorFechas($fechaInicio, $fechaFin)
    {
        // Obtenemos la instancia de la conexión
        $conexion = ConexionDB::getInstancia()->getConexion();
        // Preparamos la consulta
        $stmt = $conexion->prepare(
            "SELECT * FROM ventas WHERE (fecha BETWEEN :fechaInicio AND :fechaFin) AND es_rectificativa = 0 ORDER BY fecha DESC"
        );
        // Vinculamos los parámetros
        $stmt->bindParam(':fechaInicio', $fechaInicio);
        $stmt->bindParam(':fechaFin', $fechaFin);
        // Ejecutamos la consulta
        $stmt->execute();
        // Creamos un array para guardar las ventas
        $ventas = [];
        // Recorremos las filas
        while ($fila = $stmt->fetch()) {
            // Creamos una nueva venta y la añadimos al array
            $ventas[] = self::crearDesdeArray($fila);
        }
        // Devolvemos las ventas
        return $ventas;
    }

    /**
     * Registra una nueva venta persistiendo los datos en la tabla correspondiente (tickets o facturas).
     * Implementa una transacción SQL para asegurar que se genere un ID único compartido.
     * 
     * @return bool True si la venta se guardó correctamente.
     */
    public function insertar()
    {
        // Obtenemos la instancia de la conexión
        $conexion = ConexionDB::getInstancia()->getConexion();

        try {
            // Iniciamos transacción para asegurar consistencia entre tablas
            $conexion->beginTransaction();

            // 1. Determinar serie según tipo de documento: ticket -> T, factura -> F
            $serie = ($this->tipoDocumento === 'factura') ? 'F' : 'T';

            // 2. Obtener el siguiente número correlativo para esta serie
            $stmtMax = $conexion->prepare("SELECT COALESCE(MAX(numero), 0) + 1 as siguiente FROM ventas_ids WHERE serie = ?");
            $stmtMax->execute([$serie]);
            $rowMax = $stmtMax->fetch(PDO::FETCH_ASSOC);
            $siguienteNumero = $rowMax['siguiente'];

            // 3. Generar un nuevo ID único en la tabla ventas_ids con el correlativo
            $stmtId = $conexion->prepare("INSERT INTO ventas_ids (tipo, serie, numero) VALUES (:tipo, :serie, :numero)");
            $stmtId->bindParam(':tipo', $this->tipoDocumento);
            $stmtId->bindParam(':serie', $serie);
            $stmtId->bindParam(':numero', $siguienteNumero, PDO::PARAM_INT);
            $stmtId->execute();

            // Obtenemos el ID generado
            $this->id = $conexion->lastInsertId();

            // --- Lógica Verifactu Encadenamiento ---
            $ultimo = self::getUltimoRegistroFiscal();
            if ($ultimo) {
                $this->hashPrevio = $ultimo['hash'];
                $this->datosRegistroAnterior = $ultimo;
            } else {
                $this->hashPrevio = null;
                $this->datosRegistroAnterior = null;
            }

            // 4. Insertar en la tabla real correspondiente (tickets o facturas) con el ID obtenido
            $tabla = $this->getTabla();
            $sql = "INSERT INTO {$tabla} (id, idUsuario, fecha, total, metodoPago, estado, tipoDocumento, cerrada, importeEntregado, cambioDevuelto, descuentoTipo, descuentoValor, descuentoCupon, descuentoTarifaTipo, descuentoTarifaValor, descuentoTarifaCupon, descuentoManualTipo, descuentoManualValor, descuentoManualCupon, idTarifa, cliente_dni, cliente_nombre, cliente_direccion, cliente_observaciones, mensaje_personalizado, desglose_pago, idSesionCaja, puntos_ganados, puntos_canjeados, puntos_balance, idioma_ticket, hash_previo) 
                  VALUES (:id, :idUsuario, :fecha, :total, :metodoPago, :estado, :tipoDocumento, :cerrada, :importeEntregado, :cambioDevuelto, :descuentoTipo, :descuentoValor, :descuentoCupon, :descuentoTarifaTipo, :descuentoTarifaValor, :descuentoTarifaCupon, :descuentoManualTipo, :descuentoManualValor, :descuentoManualCupon, :idTarifa, :clienteDni, :clienteNombre, :clienteDireccion, :clienteObservaciones, :mensajePersonalizado, :desglosePago, :idSesionCaja, :puntosGanados, :puntosCanjeados, :puntosBalance, :idiomaTicket, :hashPrevio)";

            $stmt = $conexion->prepare($sql);

            // Vinculamos los parámetros
            $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
            $stmt->bindParam(':idUsuario', $this->idUsuario, PDO::PARAM_INT);
            $stmt->bindParam(':fecha', $this->fecha);
            $stmt->bindParam(':total', $this->total);
            $stmt->bindParam(':metodoPago', $this->metodoPago);
            $stmt->bindParam(':estado', $this->estado);
            $stmt->bindParam(':tipoDocumento', $this->tipoDocumento);
            $cerradaVal = $this->cerrada ? 1 : 0;
            $stmt->bindParam(':cerrada', $cerradaVal, PDO::PARAM_INT);
            $stmt->bindParam(':importeEntregado', $this->importeEntregado);
            $stmt->bindParam(':cambioDevuelto', $this->cambioDevuelto);
            $stmt->bindParam(':descuentoTipo', $this->descuentoTipo);
            $stmt->bindParam(':descuentoValor', $this->descuentoValor);
            $stmt->bindParam(':descuentoCupon', $this->descuentoCupon);
            $stmt->bindParam(':descuentoTarifaTipo', $this->descuentoTarifaTipo);
            $stmt->bindParam(':descuentoTarifaValor', $this->descuentoTarifaValor);
            $stmt->bindParam(':descuentoTarifaCupon', $this->descuentoTarifaCupon);
            $stmt->bindParam(':descuentoManualTipo', $this->descuentoManualTipo);
            $stmt->bindParam(':descuentoManualValor', $this->descuentoManualValor);
            $stmt->bindParam(':descuentoManualCupon', $this->descuentoManualCupon);
            $stmt->bindParam(':idTarifa', $this->idTarifa, PDO::PARAM_INT);
            $stmt->bindParam(':clienteDni', $this->clienteDni, PDO::PARAM_STR);
            $stmt->bindParam(':clienteNombre', $this->clienteNombre, PDO::PARAM_STR);
            $stmt->bindParam(':clienteDireccion', $this->clienteDireccion, PDO::PARAM_STR);
            $stmt->bindParam(':clienteObservaciones', $this->clienteObservaciones, PDO::PARAM_STR);
            $stmt->bindParam(':mensajePersonalizado', $this->mensajePersonalizado, PDO::PARAM_STR);
            $stmt->bindParam(':desglosePago', $this->desglosePago, PDO::PARAM_STR);
            $stmt->bindParam(':idSesionCaja', $this->idSesionCaja, PDO::PARAM_INT);
            $stmt->bindParam(':puntosGanados', $this->puntosGanados, PDO::PARAM_INT);
            $stmt->bindParam(':puntosCanjeados', $this->puntosCanjeados, PDO::PARAM_INT);
            $stmt->bindParam(':puntosBalance', $this->puntosBalance, PDO::PARAM_INT);
            $stmt->bindParam(':idiomaTicket', $this->idioma_ticket, PDO::PARAM_STR);
            $stmt->bindParam(':hashPrevio', $this->hashPrevio, PDO::PARAM_STR);

            // Ejecutamos la consulta de inserción
            try {
                $stmt->execute();
            } catch (Exception $e) {
                // Si falla por columna inexistente, intentar añadirla y repetir UNA vez
                if (strpos($e->getMessage(), 'Unknown column') !== false || strpos($e->getMessage(), 'column') !== false) {
                    $conexion->rollBack();
                    $conexion->beginTransaction();
                    // Aseguramos que AMBAS tablas (tickets y facturas) tengan las columnas para que la vista UNION ALL funcione
                    foreach (['tickets', 'facturas'] as $t) {
                        $conexion->exec("ALTER TABLE {$t} ADD COLUMN IF NOT EXISTS puntos_ganados INT DEFAULT 0");
                        $conexion->exec("ALTER TABLE {$t} ADD COLUMN IF NOT EXISTS puntos_canjeados INT DEFAULT 0");
                        $conexion->exec("ALTER TABLE {$t} ADD COLUMN IF NOT EXISTS puntos_balance INT DEFAULT 0");
                        $conexion->exec("ALTER TABLE {$t} ADD COLUMN IF NOT EXISTS mensaje_personalizado TEXT DEFAULT NULL");
                        $conexion->exec("ALTER TABLE {$t} ADD COLUMN IF NOT EXISTS desglose_pago TEXT DEFAULT NULL");
                    }

                    // Refrescar la vista 'ventas' usando columnas explícitas para evitar desalineación
                    $conexion->exec("DROP VIEW IF EXISTS ventas");
                    $cols = "id,idUsuario,fecha,total,descuentoTipo,descuentoValor,descuentoCupon,descuentoTarifaTipo,descuentoTarifaValor,descuentoTarifaCupon,descuentoManualTipo,descuentoManualValor,descuentoManualCupon,metodoPago,estado,tipoDocumento,importeEntregado,cambioDevuelto,cerrada,idSesionCaja,idTarifa,cliente_dni,cliente_nombre,cliente_direccion,cliente_observaciones,mensaje_personalizado,desglose_pago,puntos_ganados,puntos_canjeados,puntos_balance,idioma_ticket";
                    $conexion->exec("CREATE VIEW ventas AS SELECT $cols FROM tickets UNION ALL SELECT $cols FROM facturas");

                    // Re-intentar la inserción
                    $stmt->execute();
                } else {
                    throw $e;
                }
            }

            // Confirmamos la transacción (líneas se insertan después en cCajero)
            $conexion->commit();
            return true;

        } catch (Exception $e) {
            // En caso de error, revertimos los cambios
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            error_log("Error en Venta::insertar: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Genera XML Verifactu, calcula hash, envía a AEAT y actualiza BD.
     * DEBE llamarse DESPUÉS de insertar las líneas de venta.
     */
    public function enviarVerifactu()
    {
        require_once(__DIR__ . '/../core/Verifactu.php');
        $conexion = ConexionDB::getInstancia()->getConexion();
        $tabla = self::getTablaById($this->id);
        if (!$tabla)
            return;

        // Obtener líneas de venta (ya insertadas)
        $stmtLines = $conexion->prepare("SELECT * FROM lineasVenta WHERE idVenta = ?");
        $stmtLines->execute([$this->id]);
        $lineasParaXML = $stmtLines->fetchAll(PDO::FETCH_ASSOC);

        // ✅ Validación pre-envío (NIF, fecha, importes, certificado...)
        $validacion = Verifactu::validarDatosPreEnvio($this, $lineasParaXML);
        if (!$validacion['valid']) {
            $errMsg = implode('; ', array_map(fn($e) => "[{$e['code']}] {$e['message']}", $validacion['errors']));
            $this->estadoAeat = 'error';
            $this->errorAeat = 'Validación: ' . $errMsg;
            $stmtErr = $conexion->prepare("UPDATE {$tabla} SET estado_aeat = 'error', error_aeat = ? WHERE id = ?");
            $stmtErr->execute([$this->errorAeat, $this->id]);
            Verifactu::registrarEvento('validacion_fallida', $this->id, $tabla, $errMsg, $validacion['errors']);
            return;
        }

        $this->xmlDatos = Verifactu::generarXML($this, $lineasParaXML);
        $this->hash = Verifactu::calcularHashEncadenado($this->xmlDatos, $this->hashPrevio);

        // Actualizar registro con Hash y XML
        $stmtVeri = $conexion->prepare("UPDATE {$tabla} SET hash = ?, xml_datos = ? WHERE id = ?");
        $stmtVeri->execute([$this->hash, $this->xmlDatos, $this->id]);

        // Envío a AEAT
        error_log("DEBUG Verifactu: Intentando enviar venta " . $this->id . " a la AEAT...");
        $resAeat = Verifactu::enviarAEAT($this->xmlDatos);
        error_log("DEBUG Verifactu: Resultado envío: " . json_encode($resAeat));
        
        $this->estadoAeat = $resAeat['success'] ? 'enviado' : 'error';
        $this->csvAeat = $resAeat['csv'] ?? null;
        $this->errorAeat = $resAeat['success'] ? null : $resAeat['message'];

        $stmtRes = $conexion->prepare("UPDATE {$tabla} SET estado_aeat = ?, csv_aeat = ?, error_aeat = ? WHERE id = ?");
        $stmtRes->execute([$this->estadoAeat, $this->csvAeat, $this->errorAeat, $this->id]);

        // Registrar evento
        if ($resAeat['success']) {
            Verifactu::registrarEvento('envio_ok', $this->id, $tabla,
                'Envío exitoso. CSV: ' . ($this->csvAeat ?? ''));
        } else {
            $esConexion = $resAeat['es_error_conexion'] ?? false;
            $codigoError = $resAeat['codigo_error'] ?? null;

            // Encolar siempre los errores para que aparezcan en la gestión (Cola de Envíos)
            $esErrorTemporal = ($esConexion || ($codigoError && substr($codigoError, 0, 1) === '5'));
            $estadoCola = $esErrorTemporal ? 'error_temporal' : 'error_permanente';
            
            Verifactu::encolarEnvio($this->id, $tabla, 'alta', $this->xmlDatos,
                $resAeat['message'], $codigoError, $esConexion, $numDoc, $estadoCola);

            $tipoEvento = $esConexion ? 'conexion_perdida' : 'envio_error';
            Verifactu::registrarEvento($tipoEvento, $this->id, $tabla,
                ($esErrorTemporal ? 'Encolado para reintento: ' : 'Error permanente encolado: ') . $resAeat['message'],
                ['codigo' => $codigoError, 'conexion' => $esConexion]);
        }
    }

    /**
     * Actualiza los datos de la venta en la base de datos.
     * @return bool
     */
    public function actualizar()
    {
        // Obtenemos la instancia de la conexión
        $conexion = ConexionDB::getInstancia()->getConexion();
        // Determinamos la tabla real donde se encuentra esta venta
        $tabla = self::getTablaById($this->id);
        if (!$tabla) {
            return false;
        }
        // Preparamos la consulta
        $stmt = $conexion->prepare(
            "UPDATE {$tabla} SET idUsuario = :idUsuario, fecha = :fecha, total = :total, 
             metodoPago = :metodoPago, estado = :estado, tipoDocumento = :tipoDocumento, cerrada = :cerrada WHERE id = :id"
        );
        // Vinculamos los parámetros
        $stmt->bindParam(':idUsuario', $this->idUsuario, PDO::PARAM_INT);
        $stmt->bindParam(':fecha', $this->fecha);
        $stmt->bindParam(':total', $this->total);
        $stmt->bindParam(':metodoPago', $this->metodoPago);
        $stmt->bindParam(':estado', $this->estado);
        $stmt->bindParam(':tipoDocumento', $this->tipoDocumento);
        $cerradaVal = $this->cerrada ? 1 : 0;
        $stmt->bindParam(':cerrada', $cerradaVal, PDO::PARAM_INT);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        // Ejecutamos la consulta
        return $stmt->execute();
    }

    /**
     * Anula una venta (cambia su estado a 'anulada').
     * Valida que no esté ya anulada y que tenga hash válido.
     * @return array ['success' => bool, 'message' => string, 'csv' => string|null]
     */
    public function anular()
    {
        // Validaciones
        if ($this->estado === 'anulada') {
            return ['success' => true, 'message' => 'El documento ya está anulado.'];
        }

        // --- ✅ NUEVO ORDEN CORRECTO: PRIMERO AEAT, DESPUES BD ---
        // --- Verifactu: Registro de Anulación ---
        $xmlAnu = Verifactu::generarXMLAnulacion($this);
        $hashAnu = Verifactu::calcularHashEncadenado($xmlAnu, $this->hash);
        $resAeat = Verifactu::enviarAEAT($xmlAnu);

        // Si la AEAT devuelve que YA ESTA ANULADO, tambien lo marcamos como OK
        if (!$resAeat['success'] && str_contains(mb_strtolower($resAeat['message']), 'anulado')) {
            $resAeat['success'] = true;
            $resAeat['message'] = 'Documento ya estaba anulado en AEAT. Actualizado en BD.';
        }

        // ✅ SOLO SI AEAT DEVUELVE EXITO, MARCAMOS COMO ANULADO
        if ($resAeat['success']) {
            $this->estado = 'anulada';
            $res = $this->actualizar();

            if (!$res) {
                return ['success' => false, 'message' => 'Error al actualizar estado en BD.'];
            }
        }

        $estadoAnulacion = $resAeat['success'] ? 'enviado' : 'error';
        $csvAnulacion = $resAeat['csv'] ?? null;
        $errorMsg = $resAeat['success'] ? null : $resAeat['message'];

        $tabla = self::getTablaById($this->id);
        if ($tabla) {
            $conexion = ConexionDB::getInstancia()->getConexion();
            $stmt = $conexion->prepare(
                "UPDATE {$tabla} SET estado_aeat = ?, csv_anulacion = ?, 
                 hash_anulacion = ?, fecha_anulacion = NOW(), error_aeat = ?, xml_datos_anu = ? WHERE id = ?"
            );
            $stmt->execute([$estadoAnulacion, $csvAnulacion, $hashAnu, $errorMsg, $xmlAnu, $this->id]);

            // ✅ Si AEAT confirmo anulacion, asegurar que estado quede anulado definitivamente
            if ($resAeat['success']) {
                $stmtFinal = $conexion->prepare("UPDATE {$tabla} SET estado = 'anulada' WHERE id = ?");
                $stmtFinal->execute([$this->id]);
            }
        }

        return [
            'success' => $resAeat['success'],
            'message' => $resAeat['success'] ? ($csvAnulacion ? "Documento anulado correctamente. CSV: $csvAnulacion" : 'Documento anulado correctamente.') : $errorMsg,
            'csv' => $csvAnulacion
        ];
    }

    /**
     * Busca y anula un documento por serie+número.
     * @param string $serie 'T' o 'F'
     * @param int $numero Número correlativo
     * @return array ['success' => bool, 'message' => string]
     */
    public static function anularDocumento($serie, $numero)
    {
        $conexion = ConexionDB::getInstancia()->getConexion();

        // 1. Buscar en ventas_ids
        $stmt = $conexion->prepare("SELECT id FROM ventas_ids WHERE serie = ? AND numero = ?");
        $stmt->execute([$serie, (int) $numero]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return ['success' => false, 'message' => 'Documento no encontrado.'];
        }

        $venta = self::buscarPorId($row['id']);
        if (!$venta) {
            return ['success' => false, 'message' => 'Documento no encontrado en BD.'];
        }

        // Hidratar serie/numero
        $venta->setSerie($serie);
        $venta->setNumero($numero);

        // ✅ Verifactu: Obtener el ÚLTIMO registro global para el encadenamiento (Anterior)
        // Una anulación DEBE encadenarse al último registro enviado (sea Alta o Anulación de cualquier factura)
        $ultimo = self::getUltimoRegistroFiscal();
        if ($ultimo) {
            $venta->setHashPrevio($ultimo['hash']);
            // Guardamos temporalmente los datos del registro anterior para el XML
            $venta->datosRegistroAnterior = $ultimo;
        } else {
            $venta->setHashPrevio(null);
        }

        // Cargar hash propio (el de su Alta) para el bloque de datos de la factura a anular
        $tabla = self::getTablaById($venta->getId());
        if ($tabla) {
            $stmtH = $conexion->prepare("SELECT hash FROM {$tabla} WHERE id = ?");
            $stmtH->execute([$venta->getId()]);
            $hashPropio = $stmtH->fetchColumn();
            $venta->setHash($hashPropio);
        }

        return $venta->anular();
    }

    /**
     * Crea una factura rectificativa (R1/R5) referenciando un documento original.
     * Se usa para devoluciones: importes negativos, referencia al original.
     *
     * @param string $serie Serie del original ('T' o 'F')
     * @param int $numero Número del original
     * @param array $lineasDevolucion Array de líneas: [{precioUnitario, cantidad, iva, subtotal, nombre}]
     * @param int $idUsuario ID del usuario que rectifica
     * @param int|null $idSesionCaja ID de la sesión de caja activa
     * @return array ['success' => bool, 'message' => string, 'venta' => Venta|null]
     */
    public static function rectificarDocumento($serie, $numero, $lineasDevolucion, $idUsuario, $idSesionCaja = null)
    {
        $conexion = ConexionDB::getInstancia()->getConexion();

        // 1. Buscar documento original
        $stmt = $conexion->prepare("SELECT id FROM ventas_ids WHERE serie = ? AND numero = ?");
        $stmt->execute([$serie, (int) $numero]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return ['success' => false, 'message' => 'Documento original no encontrado.'];
        }

        $idOriginal = $row['id'];
        $ventaOriginal = self::buscarPorId($idOriginal);
        if (!$ventaOriginal) {
            return ['success' => false, 'message' => 'Documento original no encontrado en BD.'];
        }

        if ($ventaOriginal->getEstado() === 'anulada') {
            return ['success' => false, 'message' => 'No se puede rectificar un documento anulado.'];
        }

        $ventaOriginal->setSerie($serie);
        $ventaOriginal->setNumero($numero);

        // 2. Detectar tipo original: F1 (factura) o F2 (ticket)
        $tipoOriginal = $ventaOriginal->getClienteDni() ? 'F1' : 'F2';
        $tipoDocOriginal = $ventaOriginal->getTipoDocumento();
        // Serie rectificativa: Siempre 'D' según requerimiento
        $serieRect = 'D';

        // 3. Calcular total negativo
        $totalNegativo = 0;
        foreach ($lineasDevolucion as $linea) {
            $base = abs((float) ($linea['precioUnitario'] ?? 0));
            $cant = abs((int) ($linea['cantidad'] ?? 1));
            $iva = (float) ($linea['iva'] ?? 21);
            $pvp = $base * (1 + $iva / 100);
            $totalNegativo += round($pvp * $cant, 2);
        }
        $totalNegativo = -abs($totalNegativo);

        // 4. Crear nueva venta rectificativa
        $rectificativa = new Venta();
        $rectificativa->setIdUsuario($idUsuario);
        $rectificativa->setFecha(date('Y-m-d H:i:s'));
        $rectificativa->setTotal($totalNegativo);
        $rectificativa->setMetodoPago($ventaOriginal->getMetodoPago());
        $rectificativa->setEstado('completada');
        $rectificativa->setTipoDocumento($tipoDocOriginal);
        $rectificativa->setCerrada(0);
        $rectificativa->setIdSesionCaja($idSesionCaja);
        $rectificativa->setEsRectificativa(1);
        $rectificativa->setIdDocumentoOriginal($idOriginal);
        $rectificativa->setTipoFacturaVerifactu(($tipoOriginal === 'F1') ? 'R1' : 'R5');
        $rectificativa->setClienteDni($ventaOriginal->getClienteDni());
        $rectificativa->setClienteNombre($ventaOriginal->getClienteNombre());

        try {
            $conexion->beginTransaction();

            // Obtener siguiente número para serie rectificativa
            $stmtMax = $conexion->prepare("SELECT COALESCE(MAX(numero), 0) + 1 as siguiente FROM ventas_ids WHERE serie = ?");
            $stmtMax->execute([$serieRect]);
            $siguienteNumero = $stmtMax->fetch(PDO::FETCH_ASSOC)['siguiente'];

            // Insertar en ventas_ids
            $stmtId = $conexion->prepare("INSERT INTO ventas_ids (tipo, serie, numero) VALUES (:tipo, :serie, :numero)");
            $stmtId->execute([
                ':tipo' => $tipoDocOriginal,
                ':serie' => $serieRect,
                ':numero' => $siguienteNumero
            ]);
            $rectificativa->setId($conexion->lastInsertId());
            $rectificativa->setSerie($serieRect);
            $rectificativa->setNumero($siguienteNumero);

            // Hash previo
            $ultimo = self::getUltimoRegistroFiscal();
            if ($ultimo) {
                $rectificativa->setHashPrevio($ultimo['hash']);
                $rectificativa->datosRegistroAnterior = $ultimo;
            } else {
                $rectificativa->setHashPrevio(null);
            }

            // Insertar en tabla
            $tabla = ($tipoDocOriginal === 'factura') ? 'facturas' : 'tickets';
            $sql = "INSERT INTO {$tabla} (id, idUsuario, fecha, total, metodoPago, estado, tipoDocumento, cerrada, 
                    idSesionCaja, cliente_dni, cliente_nombre, hash_previo, es_rectificativa, id_documento_original, tipo_factura_verifactu) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmtIns = $conexion->prepare($sql);
            $stmtIns->execute([
                $rectificativa->getId(),
                $rectificativa->getIdUsuario(),
                $rectificativa->getFecha(),
                $rectificativa->getTotal(),
                $rectificativa->getMetodoPago(),
                'completada',
                $tipoDocOriginal,
                0,
                $idSesionCaja,
                $rectificativa->getClienteDni(),
                $rectificativa->getClienteNombre(),
                $rectificativa->getHashPrevio(),
                1,
                $idOriginal,
                $rectificativa->getTipoFacturaVerifactu()
            ]);

            // Insertar líneas de venta negativas
            foreach ($lineasDevolucion as $linea) {
                $stmtLinea = $conexion->prepare(
                    "INSERT INTO lineasVenta (idVenta, idProducto, nombreProducto, cantidad, precioUnitario, iva, subtotal) 
                     VALUES (?, ?, ?, ?, ?, ?, ?)"
                );
                $cant = -abs((int) ($linea['cantidad'] ?? 1));
                $precio = (float) ($linea['precioUnitario'] ?? 0);
                $iva = (float) ($linea['iva'] ?? 21);
                $subtotal = round($precio * $cant, 2);
                $stmtLinea->execute([
                    $rectificativa->getId(),
                    $linea['idProducto'] ?? null,
                    $linea['nombre'] ?? 'Producto',
                    $cant,
                    $precio,
                    $iva,
                    $subtotal
                ]);
            }

            $conexion->commit();
        } catch (Exception $e) {
            if ($conexion->inTransaction())
                $conexion->rollBack();
            return ['success' => false, 'message' => 'Error BD: ' . $e->getMessage()];
        }

        // 5. Generar XML rectificativa y enviar a AEAT
        require_once(__DIR__ . '/../core/Verifactu.php');
        $qrUrl = Verifactu::generarURLQR($rectificativa);
        $datosRect = [
            'serie' => $serie,
            'numero' => str_pad($numero, 5, '0', STR_PAD_LEFT),
            'fecha' => $ventaOriginal->getFecha(),
            'tipoOriginal' => $tipoOriginal
        ];

        // Obtener líneas para XML (con subtotals negativos)
        $stmtLines = $conexion->prepare("SELECT * FROM lineasVenta WHERE idVenta = ?");
        $stmtLines->execute([$rectificativa->getId()]);
        $lineasXML = $stmtLines->fetchAll(PDO::FETCH_ASSOC);

        $xmlRect = Verifactu::generarXML($rectificativa, $lineasXML, $datosRect);
        $hashRect = Verifactu::calcularHashEncadenado($xmlRect, $rectificativa->getHashPrevio());

        // Guardar hash y XML
        $tabla = ($tipoDocOriginal === 'factura') ? 'facturas' : 'tickets';
        $stmtVeri = $conexion->prepare("UPDATE {$tabla} SET hash = ?, xml_datos = ? WHERE id = ?");
        $stmtVeri->execute([$hashRect, $xmlRect, $rectificativa->getId()]);

        // Enviar a AEAT (endpoint normal, NO VerifactuAnuSOAP)
        $resAeat = Verifactu::enviarAEAT($xmlRect);
        $estadoAeat = $resAeat['success'] ? 'enviado' : 'error';
        $csvRect = $resAeat['csv'] ?? null;
        $errorMsg = $resAeat['success'] ? null : $resAeat['message'];

        $stmtRes = $conexion->prepare("UPDATE {$tabla} SET estado_aeat = ?, csv_aeat = ?, error_aeat = ? WHERE id = ?");
        $stmtRes->execute([$estadoAeat, $csvRect, $errorMsg, $rectificativa->getId()]);

        return [
            'success' => $resAeat['success'],
            'message' => $resAeat['success']
                ? 'Rectificativa generada correctamente.'
                : 'Rectificativa guardada pero error AEAT: ' . $errorMsg,
            'venta' => $rectificativa,
            'csv' => $csvRect,
            'qrUrl' => $qrUrl,
            'tipoFactura' => $rectificativa->getTipoFacturaVerifactu(),
            'serieNumero' => $serieRect . str_pad($siguienteNumero, 5, '0', STR_PAD_LEFT)
        ];
    }

    /**
     * Elimina la venta de la base de datos.
     * @return bool
     */
    public function eliminar()
    {
        // Obtenemos la instancia de la conexión
        $conexion = ConexionDB::getInstancia()->getConexion();
        // Determinamos la tabla real donde se encuentra esta venta
        $tabla = self::getTablaById($this->id);
        if (!$tabla) {
            return false;
        }

        try {
            $conexion->beginTransaction();

            // 1. Eliminar de la tabla real
            $stmt = $conexion->prepare("DELETE FROM {$tabla} WHERE id = :id");
            $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
            $stmt->execute();

            // 2. Eliminar de la tabla maestra de IDs
            $stmtId = $conexion->prepare("DELETE FROM ventas_ids WHERE id = :id");
            $stmtId->bindParam(':id', $this->id, PDO::PARAM_INT);
            $stmtId->execute();

            $conexion->commit();
            return true;
        } catch (Exception $e) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            error_log("Error en Venta::eliminar: " . $e->getMessage());
            return false;
        }
    }

    // ======================== MÉTODOS AUXILIARES ========================

    /**
     * Obtiene el resumen de ventas abiertas (no cerradas) agrupadas por método de pago.
     * @return array
     */
    public static function obtenerResumenCajaAbierta()
    {
        // Obtenemos la instancia de la conexión
        $conexion = ConexionDB::getInstancia()->getConexion();
        // Preparamos la consulta
        $stmt = $conexion->query(
            "SELECT metodoPago, SUM(total) as sumaTotal, COUNT(id) as cantidad 
             FROM ventas 
             WHERE cerrada = 0 AND estado = 'completada' 
             GROUP BY metodoPago"
        );
        // Creamos un array para guardar el resumen
        $resumen = [
            'efectivo' => ['total' => 0, 'cantidad' => 0, 'devoluciones' => 0],
            'tarjeta' => ['total' => 0, 'cantidad' => 0, 'devoluciones' => 0],
            'bizum' => ['total' => 0, 'cantidad' => 0, 'devoluciones' => 0],
            'totalGeneral' => 0,
            'totalDevoluciones' => 0
        ];

        // Recorremos las filas
        while ($fila = $stmt->fetch()) {
            // Obtenemos el método de pago
            $metodo = $fila['metodoPago'];
            $sumaTotal = (float) $fila['sumaTotal'];
            
            // Siempre sumamos al total general, independientemente del método
            $resumen['totalGeneral'] += $sumaTotal;
            
            // Si el método de pago existe en el resumen (para el desglose detallado)
            if (isset($resumen[$metodo])) {
                // Actualizamos el total y la cantidad del método específico
                $resumen[$metodo]['total'] = $sumaTotal;
                $resumen[$metodo]['cantidad'] = (int) $fila['cantidad'];
            }
        }

        // Restar devoluciones
        // Requerimos los ficheros de Devolucion y Caja
        require_once(__DIR__ . '/Devolucion.php');
        require_once(__DIR__ . '/Caja.php');
        // Obtenemos la sesión abierta
        $sesion = Caja::obtenerSesionAbierta();
        // Si la sesión está abierta
        if ($sesion) {
            // Obtenemos el ID de la sesión
            $idSesion = $sesion->getId();

            // Obtenemos las devoluciones por método de pago
            $devEfectivo = Devolucion::obtenerTotalPorMetodo($idSesion, 'Efectivo');
            $devTarjeta = Devolucion::obtenerTotalPorMetodo($idSesion, 'Tarjeta');
            $devBizum = Devolucion::obtenerTotalPorMetodo($idSesion, 'Bizum');

            // Actualizamos el resumen
            $resumen['efectivo']['devoluciones'] = $devEfectivo;
            $resumen['tarjeta']['devoluciones'] = $devTarjeta;
            $resumen['bizum']['devoluciones'] = $devBizum;

            // NO restamos las devoluciones del total de forma manual aquí,
            // ya que las rectificativas ya son registros negativos en la tabla 'ventas'
            // y se han incluido en el SUM(total) inicial.

            // Actualizamos el total de devoluciones para información de la vista
            $resumen['totalDevoluciones'] = $devEfectivo + $devTarjeta + $devBizum;
            // El totalGeneral ya es neto (Ventas - Devoluciones) por los registros negativos.
        }

        // Devolvemos el resumen
        return $resumen;
    }

    /**
     * Obtiene el resumen de ventas de una sesión cerrada específica, en base a fechas.
     * @return array
     */
    public static function obtenerResumenCerrada($fechaInicio, $fechaFin, $idSesion)
    {
        // Obtenemos la instancia de la conexión
        $conexion = ConexionDB::getInstancia()->getConexion();
        // Preparamos la consulta
        $stmt = $conexion->prepare(
            "SELECT metodoPago, SUM(total) as sumaTotal, COUNT(id) as cantidad 
             FROM ventas 
             WHERE cerrada = 1 AND estado = 'completada' AND fecha >= :fechaInicio AND fecha <= :fechaFin 
             GROUP BY metodoPago"
        );
        // Vinculamos los parámetros
        $stmt->bindParam(':fechaInicio', $fechaInicio);
        $stmt->bindParam(':fechaFin', $fechaFin);
        // Ejecutamos la consulta
        $stmt->execute();

        // Creamos un array para guardar el resumen
        $resumen = [
            'efectivo' => ['total' => 0, 'cantidad' => 0, 'devoluciones' => 0],
            'tarjeta' => ['total' => 0, 'cantidad' => 0, 'devoluciones' => 0],
            'bizum' => ['total' => 0, 'cantidad' => 0, 'devoluciones' => 0],
            'totalGeneral' => 0,
            'totalDevoluciones' => 0
        ];

        // Recorremos las filas
        while ($fila = $stmt->fetch()) {
            // Obtenemos el método de pago
            $metodo = $fila['metodoPago'];
            $sumaTotal = (float) $fila['sumaTotal'];
            
            // Siempre sumamos al total general, independientemente del método
            $resumen['totalGeneral'] += $sumaTotal;
            
            // Si el método de pago existe en el resumen (para el desglose detallado)
            if (isset($resumen[$metodo])) {
                // Actualizamos el total y la cantidad del método específico
                $resumen[$metodo]['total'] = $sumaTotal;
                $resumen[$metodo]['cantidad'] = (int) $fila['cantidad'];
            }
        }

        // Requerimos el fichero de Devolucion
        require_once(__DIR__ . '/Devolucion.php');
        // Si la sesión está abierta
        if ($idSesion) {
            // Obtenemos las devoluciones por método de pago
            $devEfectivo = Devolucion::obtenerTotalPorMetodo($idSesion, 'Efectivo');
            $devTarjeta = Devolucion::obtenerTotalPorMetodo($idSesion, 'Tarjeta');
            $devBizum = Devolucion::obtenerTotalPorMetodo($idSesion, 'Bizum');

            // Actualizamos el resumen
            $resumen['efectivo']['devoluciones'] = $devEfectivo;
            $resumen['tarjeta']['devoluciones'] = $devTarjeta;
            $resumen['bizum']['devoluciones'] = $devBizum;

            // NO restamos las devoluciones del total de forma manual aquí,
            // ya que las rectificativas ya son registros negativos en la tabla 'ventas'
            // y se han incluido en el SUM(total) inicial.

            // Actualizamos el total de devoluciones para información de la vista
            $resumen['totalDevoluciones'] = $devEfectivo + $devTarjeta + $devBizum;
            // El totalGeneral ya es neto (Ventas - Devoluciones) por los registros negativos.
        }

        // Devolvemos el resumen
        return $resumen;
    }

    /**
     * Marca todas las ventas completadas abiertas como cerradas.
     * @return int Número de ventas cerradas
     */
    public static function cerrarCaja()
    {
        // Obtenemos la instancia de la conexión
        $conexion = ConexionDB::getInstancia()->getConexion();
        // Cerramos las ventas en ambas tablas (tickets y facturas)
        $stmtTickets = $conexion->prepare("UPDATE tickets SET cerrada = 1 WHERE cerrada = 0 AND estado = 'completada'");
        $stmtTickets->execute();
        $countTickets = $stmtTickets->rowCount();

        $stmtFacturas = $conexion->prepare("UPDATE facturas SET cerrada = 1 WHERE cerrada = 0 AND estado = 'completada'");
        $stmtFacturas->execute();
        $countFacturas = $stmtFacturas->rowCount();

        // Devolvemos el número total de ventas cerradas
        return $countTickets + $countFacturas;
    }

    /**
     * Crea un objeto Venta a partir de un array asociativo.
     * @param array $fila
     * @return Venta
     */
    public static function crearDesdeArray($fila)
    {
        // Creamos una nueva venta
        $venta = new Venta();
        // Asignamos los valores de la fila a la venta
        $venta->setId($fila['id']);
        $venta->setIdUsuario($fila['idUsuario']);
        $venta->setFecha($fila['fecha']);
        $venta->setTotal($fila['total']);
        $venta->setMetodoPago($fila['metodoPago']);
        $venta->setEstado($fila['estado']);
        $venta->setTipoDocumento($fila['tipoDocumento'] ?? 'ticket');
        $venta->setCerrada($fila['cerrada'] ?? 0);
        $venta->setImporteEntregado($fila['importeEntregado'] ?? null);
        $venta->setCambioDevuelto($fila['cambioDevuelto'] ?? null);
        $venta->setIdSesionCaja($fila['idSesionCaja'] ?? null);
        if (isset($fila['serie'])) {
            $venta->setSerie($fila['serie']);
        }
        if (isset($fila['numero'])) {
            $venta->setNumero($fila['numero']);
        }
        if (isset($fila['puntos_ganados'])) {
            $venta->setPuntosGanados($fila['puntos_ganados']);
        }
        if (isset($fila['puntos_canjeados'])) {
            $venta->setPuntosCanjeados($fila['puntos_canjeados']);
        }
        if (isset($fila['puntos_balance'])) {
            $venta->setPuntosBalance($fila['puntos_balance']);
        }
        if (isset($fila['mensaje_personalizado'])) {
            $venta->setMensajePersonalizado($fila['mensaje_personalizado']);
        }
        if (isset($fila['desglose_pago'])) {
            $venta->setDesglosePago($fila['desglose_pago']);
        }
        if (isset($fila['idioma_ticket'])) {
            $venta->setIdiomaTicket($fila['idioma_ticket']);
        }
        if (isset($fila['cliente_nombre'])) {
            $venta->setClienteNombre($fila['cliente_nombre']);
        }
        if (isset($fila['cliente_direccion'])) {
            $venta->setClienteDireccion($fila['cliente_direccion']);
        }
        if (isset($fila['cliente_observaciones'])) {
            $venta->setClienteObservaciones($fila['cliente_observaciones']);
        }
        // Verifactu extendido
        if (isset($fila['hash']))
            $venta->setHash($fila['hash']);
        if (isset($fila['hash_previo']))
            $venta->setHashPrevio($fila['hash_previo']);
        if (isset($fila['es_rectificativa']))
            $venta->setEsRectificativa($fila['es_rectificativa']);
        if (isset($fila['id_documento_original']))
            $venta->setIdDocumentoOriginal($fila['id_documento_original']);
        if (isset($fila['tipo_factura_verifactu']))
            $venta->setTipoFacturaVerifactu($fila['tipo_factura_verifactu']);
        if (isset($fila['hash_anulacion']))
            $venta->setHashAnulacion($fila['hash_anulacion']);
        if (isset($fila['csv_anulacion']))
            $venta->setCsvAnulacion($fila['csv_anulacion']);
        if (isset($fila['fecha_anulacion']))
            $venta->setFechaAnulacion($fila['fecha_anulacion']);
        return $venta;
    }

    /**
     * Obtiene la URL del código QR para Verifactu.
     * @return string
     */
    public function getURLQR()
    {
        require_once(__DIR__ . '/../core/Verifactu.php');
        return Verifactu::generarURLQR($this);
    }

    /**
     * Obtiene los datos del último registro fiscal (Alta o Anulación) 
     * para realizar el encadenamiento Verifactu correctamente.
     * @return array|null ['serie', 'numero', 'fecha', 'hash']
     */
    public static function getUltimoRegistroFiscal()
    {
        $conexion = ConexionDB::getInstancia()->getConexion();

        // Buscamos el último evento fiscal (Alta o Anulación)
        // Usamos la fecha de anulación si existe, de lo contrario la fecha del documento.
        // Se ordena por el evento más reciente globalmente.
        $sql = "
            SELECT v.serie, v.numero, v.fecha, 
                   COALESCE(v.hash_anulacion, v.hash) as last_hash
            FROM (
                SELECT vi.serie, vi.numero, t.fecha, t.hash, t.hash_anulacion, t.fecha_anulacion, t.id
                FROM tickets t
                JOIN ventas_ids vi ON t.id = vi.id
                WHERE t.hash IS NOT NULL
                UNION ALL
                SELECT vi.serie, vi.numero, f.fecha, f.hash, f.hash_anulacion, f.fecha_anulacion, f.id
                FROM facturas f
                JOIN ventas_ids vi ON f.id = vi.id
                WHERE f.hash IS NOT NULL
            ) v
            ORDER BY GREATEST(COALESCE(v.fecha_anulacion, '2000-01-01'), COALESCE(v.fecha, '2000-01-01')) DESC, v.id DESC
            LIMIT 1
        ";

        $stmt = $conexion->query($sql);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row && $row['last_hash']) {
            return [
                'serie' => $row['serie'],
                'numero' => $row['numero'],
                'fecha' => $row['fecha'], // Fecha de expedición del documento original
                'hash' => $row['last_hash']
            ];
        }

        return null;
    }
}

?>