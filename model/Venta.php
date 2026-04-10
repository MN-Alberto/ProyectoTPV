<?php

/**
 * Clase modelo para la gestión de ventas/tickets del TPV.
 * 
 * @author Alberto Méndez
 * @version 1.2 (02/03/2026)
 */

// Requerimos el fichero de conexión a la base de datos
require_once(__DIR__ . '/../core/conexionDB.php');

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
        $stmt = $conexion->query("SELECT * FROM ventas ORDER BY fecha DESC");
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
            "SELECT * FROM ventas WHERE idUsuario = :idUsuario ORDER BY fecha DESC"
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
            "SELECT * FROM ventas WHERE fecha BETWEEN :fechaInicio AND :fechaFin ORDER BY fecha DESC"
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

            // 4. Insertar en la tabla real correspondiente (tickets o facturas) con el ID obtenido
            $tabla = $this->getTabla();
            $sql = "INSERT INTO {$tabla} (id, idUsuario, fecha, total, metodoPago, estado, tipoDocumento, cerrada, importeEntregado, cambioDevuelto, descuentoTipo, descuentoValor, descuentoCupon, descuentoTarifaTipo, descuentoTarifaValor, descuentoTarifaCupon, descuentoManualTipo, descuentoManualValor, descuentoManualCupon, idTarifa, cliente_dni, cliente_nombre, cliente_direccion, cliente_observaciones, mensaje_personalizado, idSesionCaja, puntos_ganados, puntos_canjeados, puntos_balance) 
                  VALUES (:id, :idUsuario, :fecha, :total, :metodoPago, :estado, :tipoDocumento, :cerrada, :importeEntregado, :cambioDevuelto, :descuentoTipo, :descuentoValor, :descuentoCupon, :descuentoTarifaTipo, :descuentoTarifaValor, :descuentoTarifaCupon, :descuentoManualTipo, :descuentoManualValor, :descuentoManualCupon, :idTarifa, :clienteDni, :clienteNombre, :clienteDireccion, :clienteObservaciones, :mensajePersonalizado, :idSesionCaja, :puntosGanados, :puntosCanjeados, :puntosBalance)";

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
            $stmt->bindParam(':idSesionCaja', $this->idSesionCaja, PDO::PARAM_INT);
            $stmt->bindParam(':puntosGanados', $this->puntosGanados, PDO::PARAM_INT);
            $stmt->bindParam(':puntosCanjeados', $this->puntosCanjeados, PDO::PARAM_INT);
            $stmt->bindParam(':puntosBalance', $this->puntosBalance, PDO::PARAM_INT);

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
                    }

                    // Refrescar la vista 'ventas' usando columnas explícitas para evitar desalineación (ej: puntos_canjeados y puntos_balance)
                    $conexion->exec("DROP VIEW IF EXISTS ventas");
                    $cols = "id,idUsuario,fecha,total,descuentoTipo,descuentoValor,descuentoCupon,descuentoTarifaTipo,descuentoTarifaValor,descuentoTarifaCupon,descuentoManualTipo,descuentoManualValor,descuentoManualCupon,metodoPago,estado,tipoDocumento,importeEntregado,cambioDevuelto,cerrada,idSesionCaja,idTarifa,cliente_dni,cliente_nombre,cliente_direccion,cliente_observaciones,mensaje_personalizado,puntos_ganados,puntos_canjeados,puntos_balance";
                    $conexion->exec("CREATE VIEW ventas AS SELECT $cols FROM tickets UNION ALL SELECT $cols FROM facturas");

                    // Re-intentar la inserción
                    $stmt->execute();
                } else {
                    throw $e;
                }
            }

            // Confirmamos la transacción
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
     * @return bool
     */
    public function anular()
    {
        $this->estado = 'anulada';
        return $this->actualizar();
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
            // Si el método de pago existe en el resumen
            if (isset($resumen[$metodo])) {
                // Actualizamos el total y la cantidad
                $resumen[$metodo]['total'] = (float) $fila['sumaTotal'];
                $resumen[$metodo]['cantidad'] = (int) $fila['cantidad'];
                $resumen['totalGeneral'] += (float) $fila['sumaTotal'];
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

            // Restamos las devoluciones del total
            $resumen['efectivo']['total'] -= $devEfectivo;
            $resumen['tarjeta']['total'] -= $devTarjeta;
            $resumen['bizum']['total'] -= $devBizum;

            // Actualizamos el total de devoluciones
            $resumen['totalDevoluciones'] = $devEfectivo + $devTarjeta + $devBizum;
            // Actualizamos el total general
            $resumen['totalGeneral'] -= $resumen['totalDevoluciones'];
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
            // Si el método de pago existe en el resumen
            if (isset($resumen[$metodo])) {
                // Actualizamos el total y la cantidad
                $resumen[$metodo]['total'] = (float) $fila['sumaTotal'];
                $resumen[$metodo]['cantidad'] = (int) $fila['cantidad'];
                $resumen['totalGeneral'] += (float) $fila['sumaTotal'];
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

            // Restamos las devoluciones del total
            $resumen['efectivo']['total'] -= $devEfectivo;
            $resumen['tarjeta']['total'] -= $devTarjeta;
            $resumen['bizum']['total'] -= $devBizum;

            // Actualizamos el total de devoluciones
            $resumen['totalDevoluciones'] = $devEfectivo + $devTarjeta + $devBizum;
            // Actualizamos el total general
            $resumen['totalGeneral'] -= $resumen['totalDevoluciones'];
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
    private static function crearDesdeArray($fila)
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
        return $venta;
    }
}

?>