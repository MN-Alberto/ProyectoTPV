<?php
/**
 * Modelo para las devoluciones de productos.
 * 
 * @author Alberto Méndez
 * @version 1.2 (02/03/2026)
 */

// Requerimos el fichero de conexión a la base de datos
require_once(__DIR__ . '/../core/conexionDB.php');

// Definimos la clase Devolucion
class Devolucion
{
    /** 
     * @var int|null Identificador único del registro de devolución. 
     */
    private $id;
    /** 
     * @var int|null ID del ticket o venta original donde se adquirió el producto. 
     */
    private $idVenta;
    /** 
     * @var int|null ID del usuario responsable de tramitar el reembolso. 
     */
    private $idUsuario;
    /** 
     * @var int|null ID del producto que se retorna al almacén. 
     */
    private $idProducto;
    /** 
     * @var int|null Número de unidades que el cliente ha devuelto. 
     */
    private $cantidad;
    /** 
     * @var float|null Valor económico total que se reembolsa al cliente. 
     */
    private $importeTotal;
    /** 
     * @var int|null Sesión de caja activa donde se contabiliza la salida de efectivo. 
     */
    private $idSesionCaja;
    /** 
     * @var string|null Fecha y hora exacta del trámite de devolución. 
     */
    private $fecha;
    /** 
     * @var string|null Instrumento de pago usado para devolver el dinero (ej: 'Efectivo'). 
     */
    private $metodoPago;

    public function __construct($idPost = null, $idUsuario = null, $idProducto = null, $cantidad = null, $importeTotal = null, $idSesionCaja = null, $fecha = null, $metodoPago = 'Efectivo', $idVenta = null)
    {
        $this->id = $idPost;
        $this->idVenta = $idVenta;
        $this->idUsuario = $idUsuario;
        $this->idProducto = $idProducto;
        $this->cantidad = $cantidad;
        $this->importeTotal = $importeTotal;
        $this->idSesionCaja = $idSesionCaja;
        $this->fecha = $fecha;
        $this->metodoPago = $metodoPago;
    }

    // Getters y Setters
    /** 
     * Obtiene el ID de la devolución.
     * @return int|null 
     */
    public function getId()
    {
        return $this->id;
    }
    /** 
     * Obtiene el ID de la venta de origen.
     * @return int|null 
     */
    public function getIdVenta()
    {
        return $this->idVenta;
    }
    /** 
     * Establece el ID de la venta original donde se realizó la compra.
     * @param int|null $idVenta 
     */
    public function setIdVenta($idVenta)
    {
        $this->idVenta = $idVenta;
    }
    /** 
     * Obtiene el ID del usuario procesador.
     * @return int|null 
     */
    public function getIdUsuario()
    {
        return $this->idUsuario;
    }
    /** 
     * Establece el ID del usuario/empleado que gestiona la devolución.
     * @param int|null $idUsuario 
     */
    public function setIdUsuario($idUsuario)
    {
        $this->idUsuario = $idUsuario;
    }
    /** 
     * Obtiene el ID del artículo devuelto.
     * @return int|null 
     */
    public function getIdProducto()
    {
        return $this->idProducto;
    }
    /** 
     * Define el producto que se está devolviendo al stock.
     * @param int|null $idProducto 
     */
    public function setIdProducto($idProducto)
    {
        $this->idProducto = $idProducto;
    }
    /** 
     * Obtiene el número de unidades devueltas.
     * @return int|null 
     */
    public function getCantidad()
    {
        return $this->cantidad;
    }
    /** 
     * Establece el número de unidades devueltas por el cliente.
     * @param int|null $cantidad 
     */
    public function setCantidad($cantidad)
    {
        $this->cantidad = $cantidad;
    }
    /** 
     * Obtiene el importe neto reembolsado.
     * @return float|null 
     */
    public function getImporteTotal()
    {
        return $this->importeTotal;
    }
    /** 
     * Define el importe total neto que se entrega al cliente.
     * @param float|null $importeTotal 
     */
    public function setImporteTotal($importeTotal)
    {
        $this->importeTotal = $importeTotal;
    }
    /** 
     * Obtiene la sesión de caja vinculada.
     * @return int|null 
     */
    public function getIdSesionCaja()
    {
        return $this->idSesionCaja;
    }
    /** 
     * Establece el ID de la sesión de caja.
     * @param int|null $idSesionCaja 
     */
    public function setIdSesionCaja($idSesionCaja)
    {
        $this->idSesionCaja = $idSesionCaja;
    }
    /** 
     * Obtiene la forma de pago del reembolso.
     * @return string|null 
     */
    public function getMetodoPago()
    {
        return $this->metodoPago;
    }
    /** 
     * Establece el método de pago usado.
     * @param string|null $metodoPago 
     */
    public function setMetodoPago($metodoPago)
    {
        $this->metodoPago = $metodoPago;
    }

    /** 
     * Obtiene la fecha del evento.
     * @return string|null 
     */
    public function getFecha()
    {
        return $this->fecha;
    }
    /** 
     * Establece la marca de tiempo de la devolución.
     * @param string|null $fecha 
     */
    public function setFecha($fecha)
    {
        $this->fecha = $fecha;
    }

    /**
     * Inserta una nueva devolución en la base de datos.
     */
    public function insertar()
    {
        // Obtenemos la instancia de la conexión
        $conexion = ConexionDB::getInstancia()->getConexion();
        // Preparamos la consulta
        $stmt = $conexion->prepare(
            "INSERT INTO devoluciones (idVenta, idUsuario, idProducto, cantidad, importeTotal, idSesionCaja, fecha, metodoPago) 
             VALUES (:idVenta, :idUsuario, :idProducto, :cantidad, :importeTotal, :idSesionCaja, :fecha, :metodoPago)"
        );
        $stmt->bindParam(':idVenta', $this->idVenta, PDO::PARAM_INT); // Added bindParam for idVenta
        $stmt->bindParam(':idUsuario', $this->idUsuario, PDO::PARAM_INT);
        $stmt->bindParam(':idProducto', $this->idProducto, PDO::PARAM_INT);
        $stmt->bindParam(':cantidad', $this->cantidad, PDO::PARAM_INT);
        $stmt->bindParam(':importeTotal', $this->importeTotal);
        $stmt->bindParam(':idSesionCaja', $this->idSesionCaja, PDO::PARAM_INT);
        $fecha = $this->fecha ?? date('Y-m-d H:i:s');
        $stmt->bindParam(':fecha', $fecha);
        $stmt->bindParam(':metodoPago', $this->metodoPago);

        $resultado = $stmt->execute();
        if ($resultado) {
            $this->id = $conexion->lastInsertId();
        }
        return $resultado;
    }

    /**
     * Obtiene el total de devoluciones en una sesión de caja específica.
     * 
     * @param int $idSesionCaja
     * @return float
     */
    public static function obtenerTotalPorSesion($idSesionCaja)
    {
        // Obtenemos la instancia de la conexión
        $conexion = ConexionDB::getInstancia()->getConexion();
        
        // Obtenemos la fecha de apertura de la sesión para el fallback híbrido
        $stmtSesion = $conexion->prepare("SELECT fechaApertura, fechaCierre FROM caja_sesiones WHERE id = :id");
        $stmtSesion->execute([':id' => $idSesionCaja]);
        $sesion = $stmtSesion->fetch();
        $fechaApertura = $sesion['fechaApertura'] ?? null;
        $fechaCierre = $sesion['fechaCierre'] ?? null;

        // Preparamos la consulta con lógica híbrida
        $stmt = $conexion->prepare("
            SELECT SUM(importeTotal) as total 
            FROM devoluciones 
            WHERE idSesionCaja = :idSesionCaja 
            OR (idSesionCaja IS NULL AND fecha >= :fechaApertura AND (:fechaCierre IS NULL OR fecha <= :fechaCierre2))
        ");
        
        $stmt->bindParam(':idSesionCaja', $idSesionCaja, PDO::PARAM_INT);
        $stmt->bindParam(':fechaApertura', $fechaApertura);
        $stmt->bindParam(':fechaCierre', $fechaCierre);
        $stmt->bindParam(':fechaCierre2', $fechaCierre);
        
        $stmt->execute();
        $fila = $stmt->fetch();
        return (float) ($fila['total'] ?? 0);
    }

    /**
     * Obtiene el total de devoluciones por método de pago.
     * 
     * @param int $idSesionCaja
     * @param string $metodo
     * @return float
     */
    public static function obtenerTotalPorMetodo($idSesionCaja, $metodo)
    {
        // Obtenemos la instancia de la conexión
        $conexion = ConexionDB::getInstancia()->getConexion();

        // Obtenemos la fecha de apertura de la sesión para el fallback híbrido
        $stmtSesion = $conexion->prepare("SELECT fechaApertura, fechaCierre FROM caja_sesiones WHERE id = :id");
        $stmtSesion->execute([':id' => $idSesionCaja]);
        $sesion = $stmtSesion->fetch();
        $fechaApertura = $sesion['fechaApertura'] ?? null;
        $fechaCierre = $sesion['fechaCierre'] ?? null;

        // Preparamos la consulta con lógica híbrida
        $stmt = $conexion->prepare("
            SELECT SUM(importeTotal) as total 
            FROM devoluciones 
            WHERE (idSesionCaja = :idSesionCaja OR (idSesionCaja IS NULL AND fecha >= :fechaApertura AND (:fechaCierre IS NULL OR fecha <= :fechaCierre2)))
            AND metodoPago = :metodo
        ");
        
        $stmt->bindParam(':idSesionCaja', $idSesionCaja, PDO::PARAM_INT);
        $stmt->bindParam(':fechaApertura', $fechaApertura);
        $stmt->bindParam(':fechaCierre', $fechaCierre);
        $stmt->bindParam(':fechaCierre2', $fechaCierre);
        $stmt->bindParam(':metodo', $metodo);
        
        $stmt->execute();
        $fila = $stmt->fetch();
        return (float) ($fila['total'] ?? 0);
    }

    /**
     * Obtiene todas las devoluciones con información de producto y usuario.
     * 
     * @param string $orden
     * @param string|null $filtroFecha
     * @return array
     */
    public static function obtenerTodas($orden = 'fecha_desc', $filtroFecha = null)
    {
        $conexion = ConexionDB::getInstancia()->getConexion();

        $condiciones = [];
        if ($filtroFecha) {
            switch ($filtroFecha) {
                case 'hoy':
                    $condiciones[] = "DATE(d.fecha) = CURDATE()";
                    break;
                case '7dias':
                    $condiciones[] = "d.fecha >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                    break;
                case '30dias':
                    $condiciones[] = "d.fecha >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                    break;
            }
        }

        $sql = "SELECT d.*, p.nombre as producto_nombre, u.nombre as usuario_nombre 
                FROM devoluciones d
                LEFT JOIN productos p ON d.idProducto = p.id
                LEFT JOIN usuarios u ON d.idUsuario = u.id";

        if (!empty($condiciones)) {
            $sql .= " WHERE " . implode(" AND ", $condiciones);
        }

        switch ($orden) {
            case 'fecha_asc':
                $sql .= " ORDER BY d.fecha ASC";
                break;
            case 'importe_desc':
                $sql .= " ORDER BY d.importeTotal DESC";
                break;
            case 'importe_asc':
                $sql .= " ORDER BY d.importeTotal ASC";
                break;
            default:
                $sql .= " ORDER BY d.fecha DESC";
        }

        $stmt = $conexion->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>