<?php
/**
 * API de Configuración Fiscal (Verifactu).
 * Permite gestionar los datos fiscales y la conexión con la AEAT.
 */

session_start();
require_once(__DIR__ . '/../config/confDB.php');

header('Content-Type: application/json; charset=utf-8');

// Solo admin puede acceder
if (!isset($_SESSION['rolUsuario']) || $_SESSION['rolUsuario'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso denegado.']);
    exit;
}

try {
    $pdo = new PDO(RUTA, USUARIO, PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de conexión a la base de datos']);
    exit;
}

/** 
 * GET: Obtener configuración
 */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $stmt = $pdo->query("SELECT clave, valor FROM configuracion_fiscal");
        $config = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $config[$row['clave']] = $row['valor'];
        }
        
        // Si está vacía, intentar devolver constantes como fallback para hidratar el form
        if (empty($config)) {
            $config = [
                'tpv_nif' => TPV_NIF,
                'tpv_razon_social' => TPV_RAZON_SOCIAL,
                'aeat_url_verifactu' => AEAT_URL_VERIFACTU,
                'tpv_direccion' => TPV_DIRECCION,
                'cert_path' => CERT_PATH,
                'cert_pass' => CERT_PASS
            ];
        }
        
        echo json_encode($config);
    } catch (PDOException $e) {
        echo json_encode(['error' => 'La tabla de configuración fiscal no existe.']);
    }
    exit;
}

/** 
 * POST: Guardar configuración
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !is_array($input)) {
        http_response_code(400);
        echo json_encode(['error' => 'Datos inválidos.']);
        exit;
    }

    $clavesPermitidas = [
        'tpv_nif',
        'tpv_razon_social',
        'aeat_url_verifactu',
        'tpv_direccion',
        'cert_path',
        'cert_pass'
    ];

    try {
        $stmt = $pdo->prepare(
            "INSERT INTO configuracion_fiscal (clave, valor) VALUES (:clave, :valor)
             ON DUPLICATE KEY UPDATE valor = :valor2"
        );

        foreach ($input as $clave => $valor) {
            if (!in_array($clave, $clavesPermitidas)) continue;

            $valorLimpio = trim($valor);

            $stmt->execute([
                ':clave' => $clave,
                ':valor' => $valorLimpio,
                ':valor2' => $valorLimpio
            ]);
        }

        echo json_encode(['ok' => true, 'message' => 'Configuración fiscal guardada.']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al guardar: ' . $e->getMessage()]);
    }
    exit;
}
