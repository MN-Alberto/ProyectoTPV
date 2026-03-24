<?php
/**
 * Script de Automatización de Backup.
 * Diseñado para ser ejecutado vía Cron o Tarea Programada de Windows.
 * 
 * @author Alberto Méndez
 * @version 1.0 (2026)
 */

require_once(__DIR__ . '/../config/confDB.php');
require_once(__DIR__ . '/../core/BackupManager.php');

// Configuración de logs de backup
$logFile = __DIR__ . '/../backups/backup_log.txt';

function logMessage($msg) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $msg\n", FILE_APPEND);
    echo "[$timestamp] $msg\n";
}

try {
    logMessage("Iniciando proceso de backup automático...");

    $pdo = new PDO(RUTA, USUARIO, PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $manager = new BackupManager($pdo);

    // 1. Crear backup completo
    $resultado = $manager->crearBackupCompleto();
    if ($resultado['ok']) {
        logMessage("Backup completado con éxito: " . $resultado['archivo']);
    }

    // 2. Rotar backups antiguos (30 días)
    $eliminados = $manager->rotarBackups(30);
    if ($eliminados > 0) {
        logMessage("Rotación completada: se eliminaron $eliminados archivos antiguos.");
    }

    logMessage("Proceso finalizado correctamente.");

} catch (Exception $e) {
    logMessage("ERROR CRÍTICO: " . $e->getMessage());
    exit(1);
}
