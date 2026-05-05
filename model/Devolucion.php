<?php

/**
 * Modelo de Gestión de Devoluciones
 * --------------------------------
 * Esta clase gestiona el registro y consulta de productos devueltos por clientes.
 * Permite revertir ventas total o parcialmente, manteniendo la trazabilidad con 
 * la sesión de caja y la venta original.
 * 
 * @author Alberto Méndez
 * @version 1.1
 */

require_once(__DIR__ . '/../core/conexionDB.php');

class Devolucion
{
    /**
     * Atributos del Objeto (Estado de una devolución)
     */
    private $id;                // ID único del registro de devolución
    private $idVenta;           // Referencia a la venta original
    private $idProducto;        // ID del producto que se devuelve
    private $cantidad;          // Unidades devueltas
    private $precioUnitario;    // Precio base al que se vendió
    private $iva;               // Porcentaje de IVA aplicado
    private $importeTotal;      // Dinero total a devolver (Precio + IVA * Cantidad)
    private $idUsuario;         // Empleado que autorizó la devolución
    private $metodoPago;        // Cómo se devuelve el dinero (efectivo, tarjeta, etc)
    private $fecha;             // Momento exacto del registro
    private $motivo;            // Explicación textual del porqué se devuelve
    private $nombreProducto;    // Caché del nombre del producto (por si se borra de la BD)
    private $idSesionCaja;      // Sesión de caja donde se restará el importe
    private $decimales;         // Precisión monetaria a aplicar

    /**
     * Constructor
     * Inicializa una instancia de devolución lista para ser insertada o manipulada.
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
     * Consultas por Sesión de Caja
     * ---------------------------
     * Agrupa las devoluciones por Ticket (idVenta) para mostrarlas de forma compacta.
     * Útil para el informe de arqueo de caja.
     * 
     * @param int $idSesionCaja
     * @return array Resumen de tickets con productos devueltos.
     */
    public static function obtenerPorSesion($idSesionCaja)
    {
        try {
            $conexion = ConexionDB::getInstancia()->getConexion();

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
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (Exception $e) {
            error_log("Error al obtener devoluciones por sesión: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Desglose de Ticket
     * ------------------
     * Obtiene línea por línea qué productos se devolvieron en una venta concreta.
     * 
     * @param int $idVenta
     * @return array Detalle de cada producto devuelto con su precio calculado.
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

            // Re-calculamos el precio final con IVA para mostrar en la interfaz
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

    // =========================================================================
    // ACCESORES (SETTERS Y GETTERS)
    // =========================================================================

    public function setId($id) { $this->id = $id; }
    public function setNombreProducto($nombreProducto) { $this->nombreProducto = $nombreProducto; }
    public function setIdVenta($idVenta) { $this->idVenta = $idVenta; }
    public function setIdProducto($idProducto) { $this->idProducto = $idProducto; }
    public function setCantidad($cantidad) { $this->cantidad = $cantidad; }
    public function setPrecioUnitario($precioUnitario) { $this->precioUnitario = $precioUnitario; }
    public function setIva($iva) { $this->iva = $iva; }
    public function setImporteTotal($importeTotal) { $this->importeTotal = $importeTotal; }
    public function setIdUsuario($idUsuario) { $this->idUsuario = $idUsuario; }
    public function setMetodoPago($metodoPago) { $this->metodoPago = $metodoPago; }
    public function setFecha($fecha) { $this->fecha = $fecha; }
    public function setMotivo($motivo) { $this->motivo = $motivo; }
    public function setIdSesionCaja($idSesionCaja) { $this->idSesionCaja = $idSesionCaja; }
    public function setDecimales($decimales) { $this->decimales = $decimales; }

    public function getId() { return $this->id; }
    public function getNombreProducto() { return $this->nombreProducto; }
    public function getIdVenta() { return $this->idVenta; }
    public function getIdProducto() { return $this->idProducto; }
    public function getCantidad() { return $this->cantidad; }
    public function getPrecioUnitario() { return $this->precioUnitario; }
    public function getIva() { return $this->iva; }
    public function getImporteTotal() { return $this->importeTotal; }
    public function getIdUsuario() { return $this->idUsuario; }
    public function getMetodoPago() { return $this->metodoPago; }
    public function getFecha() { return $this->fecha; }
    public function getMotivo() { return $this->motivo; }
    public function getDecimales() { return $this->decimales; }

    // =========================================================================
    // OPERACIONES DE BASE DE DATOS
    // =========================================================================

    /**
     * Persiste la devolución en la base de datos.
     * Se recomienda llamar a este método dentro de una transacción junto con
     * la actualización del stock y del saldo de la caja.
     * 
     * @return bool
     */
    public function insertar()
    {
        try {
            $conexion = ConexionDB::getInstancia()->getConexion();

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
            // Log de emergencia por si falla la base de datos
            file_put_contents(__DIR__ . '/../tmp/devolucion_error.txt', "Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            error_log("Error al insertar devolución: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Busca una devolución por su identificador primario.
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
     * Lista todas las devoluciones de una venta específica.
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
     * Control de Stock / Validación
     * -----------------------------
     * Indica cuántas unidades de un producto se han devuelto ya de un ticket.
     * Evita que el cliente devuelva más unidades de las que compró originalmente.
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
     * Consulta Global con Paginación
     * ------------------------------
     * Utilizada en el panel de administración para listar todas las devoluciones.
     * Soporta filtros de fecha, búsqueda por número de ticket y ordenación dinámica.
     */
    public static function obtenerTodas($orden = 'fecha_desc', $filtroFecha = null, $busqueda = null, $pagina = 1, $porPagina = 10)
    {
        $conexion = ConexionDB::getInstancia()->getConexion();

        $condiciones = [];
        $parametros = [];

        // Filtros temporales predefinidos
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

        // Búsqueda inteligente por número de ticket (acepta correlativos T0001, etc.)
        if ($busqueda && $busqueda !== '') {
            $busquedaInt = intval($busqueda);
            if (preg_match('/^([TF]?)0*(\d+)$/i', $busqueda, $matches)) {
                $serie = strtoupper($matches[1]);
                $numero = (int)$matches[2];

                if ($serie !== '') {
                    $condiciones[] = "(d.idVenta = ? OR (vi.serie = ? AND vi.numero = ?))";
                    array_push($parametros, $busquedaInt, $serie, $numero);
                } else {
                    $condiciones[] = "(d.idVenta = ? OR vi.numero = ?)";
                    array_push($parametros, $busquedaInt, $busquedaInt);
                }
            } elseif ($busquedaInt > 0) {
                $condiciones[] = "(d.idVenta = ? OR vi.numero = ?)";
                array_push($parametros, $busquedaInt, $busquedaInt);
            }
        }

        // 1. Contamos el total para la paginación
        $sqlCount = "SELECT COUNT(*) FROM devoluciones d LEFT JOIN ventas_ids vi ON d.idVenta = vi.id";
        if (!empty($condiciones)) {
            $sqlCount .= " WHERE " . implode(" AND ", $condiciones);
        }
        $stmtCount = $conexion->prepare($sqlCount);
        $stmtCount->execute($parametros);
        $total = (int)$stmtCount->fetchColumn();

        // 2. Obtenemos los registros de la página actual
        $sql = "SELECT d.*, COALESCE(d.nombreProducto, p.nombre) as producto_nombre, u.nombre as usuario_nombre, vi.serie, vi.numero
                FROM devoluciones d
                LEFT JOIN productos p ON d.idProducto = p.id
                LEFT JOIN usuarios u ON d.idUsuario = u.id
                LEFT JOIN ventas_ids vi ON d.idVenta = vi.id";

        if (!empty($condiciones)) {
            $sql .= " WHERE " . implode(" AND ", $condiciones);
        }

        // Ordenación
        switch ($orden) {
            case 'fecha_asc': $sql .= " ORDER BY d.fecha ASC"; break;
            case 'importe_desc': $sql .= " ORDER BY d.importeTotal DESC"; break;
            case 'importe_asc': $sql .= " ORDER BY d.importeTotal ASC"; break;
            default: $sql .= " ORDER BY d.fecha DESC";
        }

        // Paginación LIMIT/OFFSET
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
     * Informe de Arqueo: Total por Método de Pago
     * ------------------------------------------
     * Indica cuánto dinero ha salido de la caja por devoluciones según el método.
     */
    public static function obtenerTotalPorMetodo($idSesionCaja, $metodoPago)
    {
        try {
            $conexion = ConexionDB::getInstancia()->getConexion();
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
     * Informe de Arqueo: Total Global
     * -------------------------------
     * Suma de todas las devoluciones de una sesión de caja.
     */
    public static function obtenerTotalPorSesion($idSesionCaja)
    {
        try {
            $conexion = ConexionDB::getInstancia()->getConexion();
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
