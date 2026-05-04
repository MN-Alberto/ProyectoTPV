<?php
/**
 * API de Configuración Fiscal (Verifactu).
 * Permite gestionar los datos fiscales y la conexión con la AEAT.
 */

// Iniciar sesión para verificar permisos de usuario
session_start();

// Cargar configuración de conexión a base de datos
require_once(__DIR__ . '/../config/confDB.php');

// Establecer tipo de respuesta JSON con codificación UTF8
header('Content-Type: application/json; charset=utf-8');

/**
 * CONTROL DE ACCESO
 * 
 * Esta API SOLO es accesible para usuarios con rol de administrador.
 * Los cajeros y usuarios normales no pueden modificar ni consultar
 * la configuración fiscal del sistema.
 */
if (!isset($_SESSION['rolUsuario']) || $_SESSION['rolUsuario'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso denegado.']);
    exit;
}

try {
    /**
     * CONEXIÓN A BASE DE DATOS
     * 
     * Se establece conexión independiente para evitar conflictos
     * con transacciones abiertas en otras partes del sistema.
     * Se activa modo excepciones para manejo uniforme de errores.
     */
    $pdo = new PDO(RUTA, USUARIO, PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de conexión a la base de datos']);
    exit;
}

/**
 * ENDPOINT: Obtener configuración fiscal actual
 * 
 * Devuelve todos los parámetros de configuración de Verifactu
 * almacenados en la tabla de base de datos.
 * 
 * Funcionalidad especial:
 * Si la tabla esta vacia (primer uso), devuelve automaticamente
 * los valores por defecto definidos en las constantes del archivo
 * de configuración para hidratar el formulario de administración.
 */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $stmt = $pdo->query("SELECT clave, valor FROM configuracion_fiscal");
        $config = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $config[$row['clave']] = $row['valor'];
        }

        /**
         * FALLBACK DE VALORES POR DEFECTO
         * 
         * Si no hay valores en la base de datos (nueva instalación),
         * se cargan las constantes del archivo confAPP.php como valores
         * iniciales para que el administrador no tenga que introducirlos todos.
         */
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
 * ENDPOINT: Guardar configuración fiscal
 * 
 * Almacena los parametros de configuración en la base de datos.
 * Utiliza la sentencia INSERT ... ON DUPLICATE KEY UPDATE para
 * actualizar valores existentes o crear nuevos automaticamente.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Leer datos enviados en formato JSON desde el formulario
    $input = json_decode(file_get_contents("php://input"), true);

    if (!$input || !is_array($input)) {
        http_response_code(400);
        echo json_encode(['error' => 'Datos inválidos.']);
        exit;
    }

    /**
     * LISTA BLANCA DE CAMPOS
     * 
     * Solo se permiten guardar estas claves concretas. Cualquier
     * otra clave enviada en la petición sera ignorada automaticamente.
     * Medida de seguridad para evitar inyección de campos no autorizados.
     */
    $clavesPermitidas = [
        'tpv_nif',                     // NIF del establecimiento
        'tpv_razon_social',            // Razón social de la empresa
        'aeat_url_verifactu',          // URL del entorno de Verifactu (pruebas/producción)
        'tpv_direccion',               // Dirección fiscal completa
        'cert_path',                   // Ruta al certificado digital .pfx
        'cert_pass',                   // Contraseña del certificado digital
        'verifactu_intervalo_reintento' // Tiempo entre reintentos de envío a AEAT
    ];

    try {
        /**
         * SENTENCIA UPSERT (Actualizar o Insertar)
         * 
         * Si la clave ya existe, actualiza su valor.
         * Si la clave no existe, la crea automaticamente.
         * Esto evita tener que hacer SELECT + INSERT/UPDATE separados.
         */
        $stmt = $pdo->prepare(
            "INSERT INTO configuracion_fiscal (clave, valor) VALUES (:clave, :valor)
             ON DUPLICATE KEY UPDATE valor = :valor2"
        );

        foreach ($input as $clave => $valor) {
            // Saltar cualquier clave que no este en la lista blanca
            if (!in_array($clave, $clavesPermitidas))
                continue;

            // Limpiar espacios en blanco antes de guardar
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
