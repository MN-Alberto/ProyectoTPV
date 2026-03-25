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

// Endpoint para configurar numeración correlativa (solo admin)
if (isset($_GET['setupNumeracion'])) {
    header('Content-Type: application/json; charset=utf-8');
    try {
        $conexion = ConexionDB::getInstancia()->getConexion();

        // Add columns
        $conexion->exec("ALTER TABLE ventas_ids ADD COLUMN IF NOT EXISTS numero INT DEFAULT 0");
        $conexion->exec("ALTER TABLE ventas_ids ADD COLUMN IF NOT EXISTS serie VARCHAR(10) DEFAULT ''");

        // Update existing records
        $conexion->exec("UPDATE ventas_ids SET serie = 'T', numero = id WHERE serie IS NULL OR serie = ''");

        echo json_encode(['ok' => true, 'message' => 'Numeración correlativa configurada']);
    }
    catch (Exception $e) {
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

require_once(__DIR__ . '/../model/Devolucion.php');

// Iniciar sesión de forma segura
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
elseif (session_status() === PHP_SESSION_ACTIVE) {
    // La sesión ya está activa, verificar que tenemos datos de usuario
    if (!isset($_SESSION['idUsuario']) && !isset($_SESSION['nombreUsuario'])) {
        // Intentar recuperar la sesión
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
    // Obtener devoluciones por sesión de caja
    if (isset($_GET['historialSesion'])) {
        try {
            $conexion = ConexionDB::getInstancia()->getConexion();

            // Obtener la sesión de caja activa
            $stmtCaja = $conexion->prepare("SELECT id FROM caja_sesiones WHERE estado = 'abierta' ORDER BY id DESC LIMIT 1");
            $stmtCaja->execute();
            $caja = $stmtCaja->fetch(PDO::FETCH_ASSOC);

            if (!$caja) {
                echo json_encode(['error' => 'No hay sesión de caja abierta']);
                exit;
            }

            $idSesion = $caja['id'];

            // Query simple sin joins
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
        }
        catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Error al obtener devoluciones: ' . $e->getMessage()]);
        }
        exit;
    }

    // Endpoint para obtener total de devoluciones por sesión
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
        }
        catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }

    // Obtener detalle de devolución por ticket
    if (isset($_GET['detalleVenta'])) {
        $input = $_GET['detalleVenta'];
        $idVenta = 0;

        // Intentar primero por ID directo
        $idVenta = (int)$input;

        // Si no es número válido, intentar por correlativo
        if ($idVenta <= 0) {
            if (preg_match('/^([TF]?)0*(\d+)$/i', $input, $matches)) {
                $serie = strtoupper($matches[1]);
                $numero = (int)$matches[2];

                $conexion = ConexionDB::getInstancia()->getConexion();
                if ($serie !== '') {
                    $stmtIds = $conexion->prepare("SELECT id FROM ventas_ids WHERE serie = ? AND numero = ?");
                    $stmtIds->execute([$serie, $numero]);
                }
                else {
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
            // Query simple sin joins
            $sql = "SELECT
                id, idVenta, idProducto, cantidad, precioUnitario, iva,
                importeTotal, motivo, fecha, metodoPago
            FROM devoluciones
            WHERE idVenta = ?
            ORDER BY fecha DESC";
            $stmt = $conexion->prepare($sql);
            $stmt->execute([$idVenta]);
            $detalle = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Obtener nombres de productos
            $sqlProductos = "SELECT id, nombre FROM productos";
            $stmtProductos = $conexion->query($sqlProductos);
            $productos = [];
            while ($p = $stmtProductos->fetch(PDO::FETCH_ASSOC)) {
                $productos[$p['id']] = $p['nombre'];
            }

            // Agregar nombre de producto
            foreach ($detalle as &$d) {
                $idProd = $d['idProducto'];
                $d['producto_nombre'] = $productos[$idProd] ?? 'Producto #' . $idProd;
            }

            echo json_encode($detalle);
        }
        catch (Exception $e) {
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
        }
        catch (Exception $e) {
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
