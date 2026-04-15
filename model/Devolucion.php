<?php
/**
 * Modelo para gestionar las devoluciones de ventas.
 * Proporciona métodos para insertar, consultar y eliminar devoluciones del sistema.
 * 
 * @author Alberto Méndez
 * @version 1.0 (03/03/2026)
 */

require_once(__DIR__ . '/../core/conexionDB.php');

class Devolucion
{
    private $id;
    private $idVenta;
    private $idProducto;
    private $cantidad;
    private $precioUnitario;
    private $iva;
    private $importeTotal;
    private $idUsuario;
    private $metodoPago;
    private $fecha;
    private $motivo;
    private $nombreProducto;
    private $idSesionCaja;
    private $decimales;

    /**
     * Constructor de la clase Devolucion.
     */
    public function __construct(
        $idVenta = null,
        $idProducto = null,
        $cantidad = null,
        $precioUnitario = null,
        $iva = null,
        $importeTotal = null,
        $idUsuario = null,
        $metodoPago = null,
        $motivo = null,
        $id = null,
        $fecha = null,
        $idSesionCaja = null,
        $decimales = 2
        )
    {
        $this->idVenta = $idVenta;
        $this->idProducto = $idProducto;
        $this->cantidad = $cantidad;
        $this->precioUnitario = $precioUnitario;
        $this->iva = $iva;
        $this->importeTotal = $importeTotal;
        $this->idUsuario = $idUsuario;
        $this->metodoPago = $metodoPago;
        $this->motivo = $motivo;
        $this->id = $id;
        $this->fecha = $fecha;
        $this->idSesionCaja = $idSesionCaja;
        $this->decimales = $decimales;
    }

    /**
     * Obtiene las devoluciones agrupadas por ticket de venta de una sesión de caja.
     * @param int $idSesionCaja ID de la sesión de caja
     * @return array Array de devoluciones agrupadas por ticket
     */
    public static function obtenerPorSesion($idSesionCaja)
    {
        try {
            $conexion = ConexionDB::getInstancia()->getConexion();

            // Obtener devoluciones agrupadas por ticket de venta
            $sql = "SELECT
                        d.idVenta,
                        d.idSesionCaja,
                        d.fecha,
                        d.metodoPago,
                        d.motivo,
                        d.idUsuario,
                        GROUP_CONCAT(DISTINCT p.nombre ORDER BY p.nombre SEPARATOR ', ') as productos,
                        SUM(d.importeTotal) as total,
                        COUNT(*) as numItems,
                        u.nombre as usuario_nombre
                    FROM devoluciones d
                    LEFT JOIN producto p ON d.idProducto = p.id
                    LEFT JOIN usuario u ON d.idUsuario = u.id
                    WHERE d.idSesionCaja = ?
                    GROUP BY d.idVenta, d.idSesionCaja, d.fecha, d.metodoPago, d.motivo, d.idUsuario, u.nombre
                    ORDER BY d.fecha DESC";

            $stmt = $conexion->prepare($sql);
            $stmt->execute([$idSesionCaja]);
            $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $resultados;
        }
        catch (Exception $e) {
            error_log("Error al obtener devoluciones por sesión: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene el detalle de productos devueltos de un ticket específico.
     * @param int $idVenta ID de la venta
     * @return array Array con los productos devueltos
     */
    public static function obtenerDetallePorVenta($idVenta)
    {
        try {
            $conexion = ConexionDB::getInstancia()->getConexion();

            $sql = "SELECT
                        d.id,
                        d.idVenta,
                        d.idProducto,
                        COALESCE(d.nombreProducto, p.nombre) as producto_nombre,
                        d.cantidad,
                        d.precioUnitario,
                        d.iva,
                        d.importeTotal,
                        d.motivo,
                        d.fecha,
                        d.decimales
                    FROM devoluciones d
                    LEFT JOIN productos p ON d.idProducto = p.id
                    WHERE d.idVenta = ?
                    ORDER BY d.fecha DESC";

            $stmt = $conexion->prepare($sql);
            $stmt->execute([$idVenta]);
            $lineas = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Calcular precio con IVA usando los decimales guardados
            foreach ($lineas as &$l) {
                $dec = isset($l['decimales']) ? (int) $l['decimales'] : 2;
                $pBase = (float) $l['precioUnitario'];
                $iva = (float) $l['iva'];
                $l['precioConIva'] = round($pBase * (1 + $iva / 100), $dec);
            }
            return $lineas;
        }
        catch (Exception $e) {
            error_log("Error al obtener detalle de devolución: " . $e->getMessage());
            return [];
        }
    }

    // Setters
    public function setId($id)
    {
        $this->id = $id;
    }

    public function setNombreProducto($nombreProducto)
    {
        $this->nombreProducto = $nombreProducto;
    }

    public function setIdVenta($idVenta)
    {
        $this->idVenta = $idVenta;
    }

    public function setIdProducto($idProducto)
    {
        $this->idProducto = $idProducto;
    }

    public function setCantidad($cantidad)
    {
        $this->cantidad = $cantidad;
    }

    public function setPrecioUnitario($precioUnitario)
    {
        $this->precioUnitario = $precioUnitario;
    }

    public function setIva($iva)
    {
        $this->iva = $iva;
    }

    public function setImporteTotal($importeTotal)
    {
        $this->importeTotal = $importeTotal;
    }

    public function setIdUsuario($idUsuario)
    {
        $this->idUsuario = $idUsuario;
    }

    public function setMetodoPago($metodoPago)
    {
        $this->metodoPago = $metodoPago;
    }

    public function setFecha($fecha)
    {
        $this->fecha = $fecha;
    }

    public function setMotivo($motivo)
    {
        $this->motivo = $motivo;
    }

    public function setIdSesionCaja($idSesionCaja)
    {
        $this->idSesionCaja = $idSesionCaja;
    }

    public function setDecimales($decimales)
    {
        $this->decimales = $decimales;
    }

    // Getters
    public function getId()
    {
        return $this->id;
    }

    public function getNombreProducto()
    {
        return $this->nombreProducto;
    }

    public function getIdVenta()
    {
        return $this->idVenta;
    }

    public function getIdProducto()
    {
        return $this->idProducto;
    }

    public function getCantidad()
    {
        return $this->cantidad;
    }

    public function getPrecioUnitario()
    {
        return $this->precioUnitario;
    }

    public function getIva()
    {
        return $this->iva;
    }

    public function getImporteTotal()
    {
        return $this->importeTotal;
    }

    public function getIdUsuario()
    {
        return $this->idUsuario;
    }

    public function getMetodoPago()
    {
        return $this->metodoPago;
    }

    public function getFecha()
    {
        return $this->fecha;
    }

    public function getMotivo()
    {
        return $this->motivo;
    }

    public function getDecimales()
    {
        return $this->decimales;
    }

    /**
     * Crea una nueva devolución en la base de datos.
     * @return bool True si la operación fue exitosa, False en caso contrario.
     */
    public function insertar()
    {
        try {
            $conexion = ConexionDB::getInstancia()->getConexion();

            // Inserción completa con todos los campos (Requiere script scriptDB/fix_devoluciones_columns.sql)
            $sql = "INSERT INTO devoluciones (idVenta, idProducto, nombreProducto, cantidad, precioUnitario, iva, importeTotal, idUsuario, metodoPago, motivo, idSesionCaja, decimales, fecha) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $conexion->prepare($sql);
            return $stmt->execute([
                $this->idVenta,
                $this->idProducto,
                $this->nombreProducto,
                $this->cantidad,
                $this->precioUnitario,
                $this->iva,
                $this->importeTotal,
                $this->idUsuario,
                $this->metodoPago,
                $this->motivo,
                $this->idSesionCaja,
                $this->decimales
            ]);
        }
        catch (Exception $e) {
            file_put_contents(__DIR__ . '/../tmp/devolucion_error.txt', "Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            error_log("Error al insertar devolución: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene una devolución por su ID.
     * @param int $id ID de la devolución.
     * @return array|null Datos de la devolución o null si no existe.
     */
    public static function obtenerPorId($id)
    {
        try {
            $conexion = ConexionDB::getInstancia()->getConexion();
            $sql = "SELECT d.*, COALESCE(d.nombreProducto, p.nombre) as producto_nombre, u.nombre as usuario_nombre 
                    FROM devoluciones d
                    LEFT JOIN productos p ON d.idProducto = p.id
                    LEFT JOIN usuarios u ON d.idUsuario = u.id
                    WHERE d.id = ?";
            $stmt = $conexion->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        catch (Exception $e) {
            error_log("Error al obtener devolución por ID: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtiene las devoluciones asociadas a una venta específica.
     * @param int $idVenta ID de la venta.
     * @return array Array con las devoluciones de la venta.
     */
    public static function obtenerPorIdVenta($idVenta)
    {
        try {
            $conexion = ConexionDB::getInstancia()->getConexion();
            $sql = "SELECT d.*, COALESCE(d.nombreProducto, p.nombre) as producto_nombre, u.nombre as usuario_nombre 
                    FROM devoluciones d
                    LEFT JOIN productos p ON d.idProducto = p.id
                    LEFT JOIN usuarios u ON d.idUsuario = u.id
                    WHERE d.idVenta = ?
                    ORDER BY d.fecha DESC";
            $stmt = $conexion->prepare($sql);
            $stmt->execute([$idVenta]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (Exception $e) {
            error_log("Error al obtener devoluciones por ID de venta: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene la cantidad total devuelta de un producto en una venta específica.
     * @param int $idVenta ID de la venta.
     * @param int $idProducto ID del producto.
     * @return int Cantidad total devuelta.
     */
    public static function obtenerCantidadDevuelta($idVenta, $idProducto)
    {
        try {
            $conexion = ConexionDB::getInstancia()->getConexion();
            $sql = "SELECT COALESCE(SUM(cantidad), 0) as cantidad_devuelta 
                    FROM devoluciones 
                    WHERE idVenta = ? AND idProducto = ?";
            $stmt = $conexion->prepare($sql);
            $stmt->execute([$idVenta, $idProducto]);
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return intval($resultado['cantidad_devuelta']);
        }
        catch (Exception $e) {
            error_log("Error al obtener cantidad devuelta: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obtiene todas las devoluciones con filtros opcionales.
     * @param string $orden Orden de los resultados (fecha_desc, fecha_asc, importe_desc, importe_asc).
     * @param string|null $filtroFecha Filtro de fecha (hoy, 7dias, 30dias).
     * @param string|null $busqueda Búsqueda por número de ticket.
     * @return array Array con las devoluciones.
     */
    public static function obtenerTodas($orden = 'fecha_desc', $filtroFecha = null, $busqueda = null, $pagina = 1, $porPagina = 10)
    {
        $conexion = ConexionDB::getInstancia()->getConexion();

        $condiciones = [];
        $parametros = [];
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

        // Búsqueda por número de ticket
        if ($busqueda && $busqueda !== '') {
            $busquedaInt = intval($busqueda);
            
            // Comprobar si es formato correlativo (T00001, F00001, etc.)
            if (preg_match('/^([TF]?)0*(\d+)$/i', $busqueda, $matches)) {
                $serie = strtoupper($matches[1]);
                $numero = (int)$matches[2];

                if ($serie !== '') {
                    $condiciones[] = "(d.idVenta = ? OR (vi.serie = ? AND vi.numero = ?))";
                    $parametros[] = $busquedaInt;
                    $parametros[] = $serie;
                    $parametros[] = $numero;
                } else {
                    $condiciones[] = "(d.idVenta = ? OR vi.numero = ?)";
                    $parametros[] = $busquedaInt;
                    $parametros[] = $busquedaInt;
                }
            } elseif ($busquedaInt > 0) {
                $condiciones[] = "(d.idVenta = ? OR vi.numero = ?)";
                $parametros[] = $busquedaInt;
                $parametros[] = $busquedaInt;
            }
        }

        // Contar total de resultados
        $sqlCount = "SELECT COUNT(*) FROM devoluciones d LEFT JOIN ventas_ids vi ON d.idVenta = vi.id";
        if (!empty($condiciones)) {
            $sqlCount .= " WHERE " . implode(" AND ", $condiciones);
        }
        $stmtCount = $conexion->prepare($sqlCount);
        $stmtCount->execute($parametros);
        $total = (int)$stmtCount->fetchColumn();

        $sql = "SELECT d.*, COALESCE(d.nombreProducto, p.nombre) as producto_nombre, u.nombre as usuario_nombre, vi.serie, vi.numero
                FROM devoluciones d
                LEFT JOIN productos p ON d.idProducto = p.id
                LEFT JOIN usuarios u ON d.idUsuario = u.id
                LEFT JOIN ventas_ids vi ON d.idVenta = vi.id";

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

        // Agregar paginación
        $offset = ($pagina - 1) * $porPagina;
        $sql .= " LIMIT $porPagina OFFSET $offset";

        $stmt = $conexion->prepare($sql);
        $stmt->execute($parametros);
        $devoluciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'devoluciones' => $devoluciones,
            'total' => $total,
            'pagina' => $pagina,
            'porPagina' => $porPagina,
            'totalPaginas' => $total > 0 ? (int)ceil($total / $porPagina) : 1
        ];
    }

    /**
     * Obtiene el total de devoluciones por método de pago para una sesión de caja.
     * @param int $idSesionCaja ID de la sesión de caja.
     * @param string $metodoPago Método de pago (Efectivo, Tarjeta, Bizum).
     * @return float Total de devoluciones.
     */
    public static function obtenerTotalPorMetodo($idSesionCaja, $metodoPago)
    {
        try {
            $conexion = ConexionDB::getInstancia()->getConexion();
            // Usar el campo directo idSesionCaja en lugar de join con ventas
            $sql = "SELECT COALESCE(SUM(importeTotal), 0) as total
                    FROM devoluciones
                    WHERE idSesionCaja = ? AND metodoPago = ?";
            $stmt = $conexion->prepare($sql);
            $stmt->execute([$idSesionCaja, $metodoPago]);
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return floatval($resultado['total']);
        }
        catch (Exception $e) {
            error_log("Error al obtener total de devoluciones por método: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obtiene el total de devoluciones para una sesión de caja.
     * @param int $idSesionCaja ID de la sesión de caja.
     * @return float Total de devoluciones.
     */
    public static function obtenerTotalPorSesion($idSesionCaja)
    {
        try {
            $conexion = ConexionDB::getInstancia()->getConexion();
            // Usar el campo directo idSesionCaja en lugar de join con ventas
            $sql = "SELECT COALESCE(SUM(importeTotal), 0) as total
                    FROM devoluciones
                    WHERE idSesionCaja = ?";
            $stmt = $conexion->prepare($sql);
            $stmt->execute([$idSesionCaja]);
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return floatval($resultado['total']);
        }
        catch (Exception $e) {
            error_log("Error al obtener total de devoluciones por sesión: " . $e->getMessage());
            return 0;
        }
    }
}
?>
