<?php
/**
 * API de Personalización de Interfaz (Tematización).
 * Permite gestionar la apariencia visual del TPV de forma dinámica,
 * almacenando y recuperando tokens de diseño (colores, fuentes e iconos).
 * 
 * @author Alberto Méndez
 * @version 1.0 (2026)
 */

session_start();
require_once(__DIR__ . '/../config/confDB.php');

header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = new PDO(RUTA, USUARIO, PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de conexión a la base de datos']);
    exit;
}

/** 
 * MANEJADOR DE CONSULTAS (GET)
 * Devuelve el mapa completo de variables de diseño para ser inyectadas en el CSS/JS.
 */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $stmt = $pdo->query("SELECT clave, valor FROM configuracion_tema");
        $config = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $config[$row['clave']] = $row['valor'];
        }
        echo json_encode($config);
    } catch (PDOException $e) {
        // Si la tabla no existe, devolver objeto vacío (se usarán defaults)
        echo json_encode(new stdClass());
    }
    exit;
}

/** 
 * MANEJADOR DE ACTUALIZACIÓN (POST)
 * Sobrescribe los valores de diseño en la base de datos tras validar permisos de administrador.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Solo admin puede guardar
    if (!isset($_SESSION['rolUsuario']) || $_SESSION['rolUsuario'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['error' => 'Acceso denegado. Solo el administrador puede modificar el tema.']);
        exit;
    }

    // Leer el body JSON
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !is_array($input)) {
        http_response_code(400);
        echo json_encode(['error' => 'Datos inválidos.']);
        exit;
    }

    // Claves permitidas
    $clavesPermitidas = [
        'header_bg',
        'header_color',
        'header_font',
        'footer_bg',
        'footer_color',
        'footer_font',
        'primary_bg',
        'primary_color',
        'primary_font',
        'sidebar_bg',
        'sidebar_color',
        'sidebar_font',
        'btn_bg',
        'btn_color',
        'btn_font',
        'btn_white_bg',
        'btn_white_color',
        'btn_white_font',
        'header_icon',
        'favicon'
    ];

    try {
        $stmt = $pdo->prepare(
            "INSERT INTO configuracion_tema (clave, valor) VALUES (:clave, :valor)
             ON DUPLICATE KEY UPDATE valor = :valor2"
        );

        foreach ($input as $clave => $valor) {
            // Solo guardar claves permitidas
            if (!in_array($clave, $clavesPermitidas))
                continue;

            // Sanitizar valor
            $valorLimpio = htmlspecialchars(trim($valor), ENT_QUOTES, 'UTF-8');

            $stmt->execute([
                ':clave' => $clave,
                ':valor' => $valorLimpio,
                ':valor2' => $valorLimpio
            ]);
        }

        echo json_encode(['ok' => true, 'message' => 'Tema guardado correctamente.']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al guardar la configuración: ' . $e->getMessage()]);
    }
    exit;
}

// Método no soportado
http_response_code(405);
echo json_encode(['error' => 'Método no permitido.']);
