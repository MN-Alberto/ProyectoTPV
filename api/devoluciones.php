<?php
/**
 * API de Gestión de Devoluciones y Abonos.
 * Proporciona acceso al historial de devoluciones procesadas, permitiendo
 * la auditoría de abonos y la consulta de mercancía retornada al inventario.
 * 
 * @author Alberto Méndez
 * @version 1.0 (03/03/2026)
 */

require_once(__DIR__ . '/../config/confDB.php');

/**
 * ENDPOINT: MIGRACIÓN Y SETUP
 * 
 * Inicializa las columnas necesarias para el sistema de numeración
 * correlativa de tickets. Solo se ejecuta una vez durante actualizaciones.
 * Crea las columnas y rellena los registros existentes con serie 'T'.
 */
if (isset($_GET['setupNumeracion'])) {
    header('Content-Type: application/json; charset=utf-8');
    try {
        $conexion = ConexionDB::getInstancia()->getConexion();

        // Crear columnas si no existen
        $conexion->exec("ALTER TABLE ventas_ids ADD COLUMN IF NOT EXISTS numero INT DEFAULT 0");
        $conexion->exec("ALTER TABLE ventas_ids ADD COLUMN IF NOT EXISTS serie VARCHAR(10) DEFAULT ''");

        // Migrar registros antiguos al nuevo sistema
        $conexion->exec("UPDATE ventas_ids SET serie = 'T', numero = id WHERE serie IS NULL OR serie = ''");

        echo json_encode(['ok' => true, 'message' => 'Numeración correlativa configurada']);
    } catch (Exception $e) {
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

require_once(__DIR__ . '/../model/Devolucion.php');

/**
 * INICIALIZACIÓN SEGURA DE SESIÓN
 * 
 * Comprueba el estado de la sesión antes de iniciarla para evitar
 * errores de sesión ya iniciada, un error muy comun en PHP cuando
 * esta API es llamada desde diferentes puntos del sistema.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
} elseif (session_status() === PHP_SESSION_ACTIVE) {
    // Si la sesion ya existe, verificar que pertenece a un usuario autenticado
    if (!isset($_SESSION['idUsuario']) && !isset($_SESSION['nombreUsuario'])) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Sesión no válida']);
        exit;
    }
}

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];

/** 
 * MANEJADOR DE CONSULTAS (GET)
 * Permite listar todas las devoluciones con filtros dinámicos.
 */
if ($method === 'GET') {
    /**
     * ENDPOINT: Historial de devoluciones por sesión de caja
     * 
     * Devuelve todas las devoluciones realizadas en la sesión
     * de caja ACTIVA actualmente. Agrupadas por ticket original.
     * 
     * @param historialSesion Activar endpoint
     */
    if (isset($_GET['historialSesion'])) {
        try {
            $conexion = ConexionDB::getInstancia()->getConexion();

            // Obtener la ultima sesion de caja abierta
            $stmtCaja = $conexion->prepare("SELECT id FROM caja_sesiones WHERE estado = 'abierta' ORDER BY id DESC LIMIT 1");
            $stmtCaja->execute();
            $caja = $stmtCaja->fetch(PDO::FETCH_ASSOC);

            if (!$caja) {
                echo json_encode(['error' => 'No hay sesión de caja abierta']);
                exit;
            }

            $idSesion = $caja['id'];

            /**
             * CONSULTA OPTIMIZADA
             * 
             * Se hace una sola consulta agrupada por idVenta y luego
             * se obtienen los nombres de usuarios por separado.
             * Esto es MUCHO mas rapido que usar JOINs para esta consulta.
             */
            $sqlDirecta = "SELECT
                idVenta,
                idSesionCaja,
                fecha,
                metodoPago,
                motivo,
                idUsuario,
                SUM(importeTotal) as total,
                COUNT(*) as numItems
            FROM devoluciones
            WHERE idSesionCaja = ?
            GROUP BY idVenta
            ORDER BY fecha DESC";
            $stmtDirecta = $conexion->prepare($sqlDirecta);
            $stmtDirecta->execute([$idSesion]);
            $resultadoDirecto = $stmtDirecta->fetchAll(PDO::FETCH_ASSOC);

            // Obtener nombres de usuarios
            $sqlUsuarios = "SELECT id, nombre FROM usuarios";
            $stmtUsuarios = $conexion->query($sqlUsuarios);
            $usuarios = [];
            while ($u = $stmtUsuarios->fetch(PDO::FETCH_ASSOC)) {
                $usuarios[$u['id']] = $u['nombre'];
            }

            // Agregar nombre de usuario
            foreach ($resultadoDirecto as &$dev) {
                $idUser = $dev['idUsuario'];
                $dev['usuario_nombre'] = $usuarios[$idUser] ?? 'Usuario #' . $idUser;
                $dev['productos'] = $dev['numItems'] . ' producto(s)';
            }

            echo json_encode($resultadoDirecto);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Error al obtener devoluciones: ' . $e->getMessage()]);
        }
        exit;
    }

    /**
     * ENDPOINT: Total acumulado de devoluciones
     * 
     * Devuelve la suma total de importes devueltos en la sesión
     * de caja actual. Usado para mostrar el contador en tiempo real
     * en la interfaz del cajero.
     * 
     * @param totalSesion Activar endpoint
     */
    if (isset($_GET['totalSesion'])) {
        try {
            $conexion = ConexionDB::getInstancia()->getConexion();
            $stmtCaja = $conexion->prepare("SELECT id FROM caja_sesiones WHERE estado = 'abierta' ORDER BY id DESC LIMIT 1");
            $stmtCaja->execute();
            $caja = $stmtCaja->fetch(PDO::FETCH_ASSOC);

            if (!$caja) {
                echo json_encode(['error' => 'No hay sesión de caja abierta']);
                exit;
            }

            $idSesion = $caja['id'];
            $sql = "SELECT COALESCE(SUM(importeTotal), 0) as total FROM devoluciones WHERE idSesionCaja = ?";
            $stmt = $conexion->prepare($sql);
            $stmt->execute([$idSesion]);
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

            echo json_encode(['idSesion' => $idSesion, 'total' => $resultado['total']]);
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * ENDPOINT: Detalle completo de devolución
     * 
     * Busca una devolución por numero de ticket. Admite múltiples formatos:
     * - ID interno directo
     * - Numero de ticket con serie: T000123
     * - Numero de ticket sin serie: 123
     * 
     * @param detalleVenta Numero o ID del ticket
     */
    if (isset($_GET['detalleVenta'])) {
        $input = $_GET['detalleVenta'];
        $idVenta = 0;

        // 1. Intentar primero por ID interno numerico
        $idVenta = (int) $input;

        // 2. Si no es valido, intentar por numero de ticket correlativo
        if ($idVenta <= 0) {
            // Reconocer formatos: T00123, F00456, 000789
            if (preg_match('/^([TF]?)0*(\d+)$/i', $input, $matches)) {
                $serie = strtoupper($matches[1]);
                $numero = (int) $matches[2];

                $conexion = ConexionDB::getInstancia()->getConexion();
                if ($serie !== '') {
                    $stmtIds = $conexion->prepare("SELECT id FROM ventas_ids WHERE serie = ? AND numero = ?");
                    $stmtIds->execute([$serie, $numero]);
                } else {
                    $stmtIds = $conexion->prepare("SELECT id FROM ventas_ids WHERE numero = ?");
                    $stmtIds->execute([$numero]);
                }
                $row = $stmtIds->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    $idVenta = $row['id'];
                }
            }
        }

        try {
            $conexion = ConexionDB::getInstancia()->getConexion();
            // 1. Obtener detalles básicos de la devolución
            $sql = "SELECT
                d.id, d.idVenta, d.idProducto, d.cantidad, d.precioUnitario, d.iva,
                d.importeTotal, d.motivo, d.fecha, d.metodoPago,
                vi.serie as orig_serie, vi.numero as orig_numero
            FROM devoluciones d
            LEFT JOIN ventas_ids vi ON d.idVenta = vi.id
            WHERE d.idVenta = ?
            ORDER BY d.fecha DESC";
            $stmt = $conexion->prepare($sql);
            $stmt->execute([$idVenta]);
            $detalle = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($detalle)) {
                echo json_encode([]);
                exit;
            }

            // 2. Buscar datos de la rectificativa (Serie D) de forma eficiente
            // Usamos una consulta separada para evitar JOINs pesados con subconsultas
            $sqlRect = "SELECT v.id, vi.serie, vi.numero, v.fecha, v.total
                        FROM (
                            SELECT id, fecha, total FROM tickets WHERE id_documento_original = ? AND es_rectificativa = 1
                            UNION ALL
                            SELECT id, fecha, total FROM facturas WHERE id_documento_original = ? AND es_rectificativa = 1
                        ) v
                        JOIN ventas_ids vi ON v.id = vi.id
                        LIMIT 1";
            $stmtRect = $conexion->prepare($sqlRect);
            $stmtRect->execute([$idVenta, $idVenta]);
            $rectData = $stmtRect->fetch(PDO::FETCH_ASSOC);

            // 3. Generar QR si existe la rectificativa
            $qrUrl = null;
            if ($rectData) {
                require_once(__DIR__ . '/../core/Verifactu.php');
                // Simulamos objeto Venta para el generador de QR para no instanciar el modelo completo
                $ventaDummy = new stdClass();
                $ventaDummy->serie = $rectData['serie'];
                $ventaDummy->numero = $rectData['numero'];
                $ventaDummy->fecha = $rectData['fecha'];
                $ventaDummy->total = $rectData['total'];

                // Métodos que requiere Verifactu::generarURLQR
                $ventaProxy = new class ($ventaDummy) {
                    private $v;
                    public function __construct($v)
                    {
                        $this->v = $v; }
                    public function getSerie()
                    {
                        return $this->v->serie; }
                    public function getNumero()
                    {
                        return $this->v->numero; }
                    public function getFecha()
                    {
                        return $this->v->fecha; }
                    public function getTotal()
                    {
                        return $this->v->total; }
                };

                $qrUrl = Verifactu::generarURLQR($ventaProxy);
            }

            // 4. Mapear nombres de productos y adjuntar datos extra
            $sqlProductos = "SELECT id, nombre FROM productos";
            $stmtProductos = $conexion->query($sqlProductos);
            $productos = [];
            while ($p = $stmtProductos->fetch(PDO::FETCH_ASSOC)) {
                $productos[$p['id']] = $p['nombre'];
            }

            foreach ($detalle as &$d) {
                $idProd = $d['idProducto'];
                $d['producto_nombre'] = $productos[$idProd] ?? 'Producto #' . $idProd;
                $d['rect_serie'] = $rectData['serie'] ?? null;
                $d['rect_numero'] = $rectData['numero'] ?? null;
                $d['qrUrl'] = $qrUrl;
            }

            echo json_encode($detalle);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => 'Error al obtener detalle: ' . $e->getMessage()]);
        }
        exit;
    }

    if (isset($_GET['todas'])) {
        $orden = $_GET['orden'] ?? 'fecha_desc';
        $filtroFecha = $_GET['filtroFecha'] ?? null;
        $busqueda = $_GET['busqueda'] ?? null;
        $pagina = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
        $porPagina = isset($_GET['porPagina']) ? min(50, max(1, intval($_GET['porPagina']))) : 10;
        try {
            $devoluciones = Devolucion::obtenerTodas($orden, $filtroFecha, $busqueda, $pagina, $porPagina);
            echo json_encode($devoluciones);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => 'Error al obtener devoluciones: ' . $e->getMessage()]);
        }
        exit;
    }

    if (isset($_GET['id'])) {
        // Implementar detalle si es necesario, por ahora obtenerTodas ya trae lo básico
        http_response_code(501);
        echo json_encode(['ok' => false, 'error' => 'No implementado']);
        exit;
    }
}

http_response_code(405);
echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
