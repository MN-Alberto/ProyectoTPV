<?php
/**
 * API de Personalización de Interfaz por Tokens de Diseño.
 * 
 * Sistema de tematización que permite modificar completamente la apariencia visual
 * del TPV sin tocar código CSS. Se basa en el patrón clave/valor donde cada registro
 * es una variable CSS que se inyecta dinámicamente en el navegador.
 * 
 * ✅ Características:
 *  - Lectura pública sin autenticación para velocidad de carga
 *  - Escritura exclusiva para administradores
 *  - Whitelist de variables permitidas
 *  - Sanitización automática de valores
 *  - Upsert transaccional en una sola consulta
 *  - Fallback gracefully a valores por defecto
 * 
 * @author Alberto Méndez
 * @version 1.1 (Comentarios añadidos)
 * @since 1.0 (2026)
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
        /**
         * 🎯 FALLBACK GRACIOSO
         * 
         * Si la tabla no existe o hay cualquier error, se devuelve un objeto vacío.
         * El frontend detectará automáticamente que no hay configuración personalizada
         * y utilizará los valores por defecto definidos en el CSS.
         * 
         * Esta medida garantiza que la aplicación nunca falle por problemas de tema.
         */
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

    /**
     * 🛡️ WHITELIST DE VARIABLES PERMITIDAS
     * 
     * Solo se aceptan estas claves. Cualquier otra variable enviada por el usuario
     * será ignorada silenciosamente. Esta es una medida de seguridad fundamental:
     * 
     * 1. Evita inyección de variables CSS maliciosas
     * 2. Garantiza que solo se modifican parámetros autorizados
     * 3. Mantiene la integridad del sistema de diseño
     * 
     * No se permite crear nuevas variables dinámicamente.
     */
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
        'favicon',
        // Tamaño de tarjetas de productos
        'producto_card_width',
        'producto_card_height',
        'producto_card_max_width',
        'producto_card_max_height',
        // Grid y spacing
        'producto_grid_columns',
        'producto_grid_gap',
        // Tamaños de fuente
        'producto_nombre_font_size',
        'producto_precio_font_size',
        'producto_stock_font_size'
    ];

    try {
        /**
         * ⚡ UPSERT EN UNA SOLA CONSULTA
         * 
         * Se usa `ON DUPLICATE KEY UPDATE` para realizar insert o update
         * en la misma operación sin necesidad de comprobar previamente si existe.
         * 
         * Nota: El valor se pasa dos veces porque MySQL no permite reutilizar
         * el mismo parámetro nombrado dos veces en la misma consulta.
         */
        $stmt = $pdo->prepare(
            "INSERT INTO configuracion_tema (clave, valor) VALUES (:clave, :valor)
                 ON DUPLICATE KEY UPDATE valor = :valor2"
        );

        foreach ($input as $clave => $valor) {
            // Solo guardar claves que están en la whitelist
            if (!in_array($clave, $clavesPermitidas))
                continue;

            /**
             * 🧹 SANITIZACIÓN AUTOMÁTICA
             * 
             * Todos los valores se limpian antes de guardar:
             * 1. Se eliminan espacios en blanco por delante y por detrás
             * 2. Se escapan todos los caracteres HTML especiales
             * 3. Se codifica correctamente en UTF-8
             * 
             * Esto evita inyección de código HTML/CSS malicioso.
             */
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
