<?php
/**
 * Script de Pruebas y Diagnóstico para la API de Logs.
 * 
 * ⚠️ IMPORTANTE: ESTE ARCHIVO ES SOLAMENTE PARA DESARROLLO Y DEPURACIÓN.
 * NO DEBE ESTAR DISPONIBLE EN ENTORNOS DE PRODUCCIÓN.
 * 
 * Proporciona un entorno controlado para verificar paso a paso cada componente
 * necesario para el funcionamiento del sistema de auditoría. Muestra trazas
 * exactas del punto donde se produce el fallo en caso de error.
 * 
 * Comprobaciones que realiza:
 * 1. Carga correcta de archivos de configuración
 * 2. Inicio correcto de sesión PHP
 * 3. Conexión exitosa a la base de datos
 * 4. Existencia de la tabla de logs del sistema
 * 
 * @author Alberto Méndez
 * @version 1.2 (Comentarios añadidos)
 * @since 1.1 (2026)
 */

/**
 * Activamos el reporte COMPLETO de errores.
 * En este script de depuración queremos ver TODOS los avisos, warnings y errores,
 * a diferencia del entorno normal donde estos se ocultan al usuario final.
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Inicio del proceso de diagnóstico
echo "Starting API...<br>";

try {
    // ✅ PASO 1: Cargar configuración de base de datos
    require_once(__DIR__ . '/../config/confDB.php');
    echo "Config loaded<br>";

    // ✅ PASO 2: Inicializar sesión del sistema
    session_start();
    echo "Session started<br>";

    // Establecemos cabecera de respuesta como en la API real
    header('Content-Type: application/json; charset=utf-8');

    // ✅ PASO 3: Establecer conexión directa con base de datos
    $pdo = new PDO(RUTA, USUARIO, PASS);
    // Forzamos modo de excepción para capturar cualquier error de BD
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Database connected<br>";

} catch (Exception $e) {
    // Si falla cualquiera de los pasos anteriores, mostramos error completo y terminamos
    echo "❌ Error en inicialización: " . $e->getMessage() . "<br>";
    echo "📋 Traza completa: " . $e->getTraceAsString() . "<br>";
    exit;
}

/**
 * ✅ PASO 4: Verificar existencia de la tabla de logs
 * Comprobamos que la estructura de base de datos se ha creado correctamente
 * mediante la migración correspondiente.
 */
try {
    // Consulta nativa MySQL para comprobar existencia de tabla
    $stmt = $pdo->query("SHOW TABLES LIKE 'logs_sistema'");

    if ($stmt->rowCount() === 0) {
        // La tabla no existe: es necesario ejecutar la migración
        echo json_encode([
            'logs' => [],
            'total' => 0,
            'mensaje' => '⚠️ Tabla logs_sistema no existe. Ejecutar migración add_logs_sistema.sql'
        ]);
        exit;
    }

    echo "✅ Table exists<br>";

} catch (Exception $e) {
    echo "❌ Error comprobando tabla: " . $e->getMessage() . "<br>";
    exit;
}

// ✅ TODAS LAS COMPROBACIONES SUPERADAS CORRECTAMENTE
echo "✅ API Logs lista y funcionando correctamente";
