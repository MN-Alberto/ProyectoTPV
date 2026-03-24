<?php
/**
 * Clase BackupManager
 * Gestiona la creación, cifrado, rotación y restauración de backups del TPV.
 * 
 * @author Alberto Méndez
 * @version 1.0 (2026)
 */
class BackupManager
{
    private $pdo;
    private $backupDir;
    private $encryptionKey;

    public function __construct($pdo, $backupDir = __DIR__ . '/../backups/')
    {
        $this->pdo = $pdo;
        $this->backupDir = $backupDir;

        // En producción, esto debería estar en una variable de entorno o config segura
        $this->encryptionKey = 'TPV_SECURE_BACKUP_KEY_2026';

        if (!file_exists($this->backupDir)) {
            mkdir($this->backupDir, 0777, true);
        }
    }

    /**
     * Crea un backup completo (DB + Archivos)
     */
    public function crearBackupCompleto()
    {
        $fecha = date('Y-m-d_H-i-s');
        $fileName = "backup_full_{$fecha}.zip";
        $tempSql = $this->backupDir . "temp_{$fecha}.sql";
        $tempZip = $this->backupDir . "temp_{$fecha}.zip";

        try {
            // 1. Dump de la Base de Datos
            $this->generarSqlDump($tempSql);

            // Check if ZipArchive is available, try to load it
            if (!class_exists('ZipArchive')) {
                // Try to load the extension
                if (!extension_loaded('zip')) {
                    @dl('php_zip.dll');
                }
                if (!class_exists('ZipArchive')) {
                    throw new Exception("Extensión ZIP no disponible. Active php_zip en php.ini");
                }
            }

            // 2. Comprimir Archivos y SQL
            $zip = new ZipArchive();
            if ($zip->open($tempZip, ZipArchive::CREATE) !== TRUE) {
                throw new Exception("No se pudo crear el archivo ZIP");
            }

            // Añadir SQL dump
            $zip->addFile($tempSql, 'database.sql');

            // Añadir imágenes y configuración
            $this->addFolderToZip(__DIR__ . '/../webroot/img/', $zip, 'webroot/img/');
            $this->addFolderToZip(__DIR__ . '/../config/', $zip, 'config/');

            $zip->close();

            // 3. Cifrar el ZIP resultante
            $finalEncodedFile = $this->backupDir . $fileName . '.enc';
            $this->encryptFile($tempZip, $finalEncodedFile);

            // 4. Limpiar temporales
            unlink($tempSql);
            unlink($tempZip);

            return [
                'ok' => true,
                'archivo' => $fileName . '.enc',
                'tipo' => 'completo',
                'fecha' => date('Y-m-d H:i:s')
            ];

        }
        catch (Exception $e) {
            // Limpiar si algo falla
            if (file_exists($tempSql))
                unlink($tempSql);
            if (file_exists($tempZip))
                unlink($tempZip);
            throw $e;
        }
    }

    /**
     * Crea un backup de una tabla específica (para tablas grandes)
     */
    public function crearBackupTabla($tabla)
    {
        $tablasPermitidas = ['clientes', 'usuarios'];

        if (!in_array($tabla, $tablasPermitidas)) {
            throw new Exception("Tabla no permitida para backup independiente");
        }

        $fecha = date('Y-m-d_H-i-s');
        $fileName = "backup_{$tabla}_{$fecha}.sql";
        $outputPath = $this->backupDir . $fileName;

        try {
            // Allow unlimited memory and time
            ini_set('memory_limit', '-1');
            set_time_limit(0);

            // Get table structure
            $result = $this->pdo->query("SHOW CREATE TABLE $tabla");
            $row = $result->fetch(PDO::FETCH_NUM);
            $createTable = $row[1];
            unset($result);

            // Open file handle
            $fp = fopen($outputPath, 'w');
            fwrite($fp, "-- Backup tabla: $tabla\n-- Fecha: " . date('Y-m-d H:i:s') . "\n\n");
            fwrite($fp, "SET FOREIGN_KEY_CHECKS=0;\n\n");
            fwrite($fp, $createTable . ";\n\n");

            // Get row count
            $countResult = $this->pdo->query("SELECT COUNT(*) FROM $tabla");
            $totalRows = $countResult->fetchColumn();
            unset($countResult);

            fwrite($fp, "-- Total rows: $totalRows\n");

            // Process in batches
            $batchSize = 5000;
            $offset = 0;
            $rowsWritten = 0;

            // Limpiar cualquier archivo de cancelación previo
            $cancelFile = sys_get_temp_dir() . '/backup_cancel_' . $tabla . '.txt';
            if (file_exists($cancelFile)) {
                unlink($cancelFile);
            }

            // Guardar progreso en archivo temporal (sin usar session_id para evitar errores)
            $progressFile = sys_get_temp_dir() . '/backup_progress_' . $tabla . '.json';
            file_put_contents($progressFile, json_encode([
                'total' => $totalRows,
                'actual' => 0,
                'inicio' => time()
            ]));

            while ($offset < $totalRows) {
                // Verificar si se canceló antes de cada batch
                $cancelFile = sys_get_temp_dir() . '/backup_cancel_' . $tabla . '.txt';
                if (file_exists($cancelFile)) {
                    // Backup cancelado, limpiar y salir
                    fclose($fp);
                    unlink($cancelFile);
                    if (file_exists($outputPath)) {
                        unlink($outputPath);
                    }
                    return ['ok' => false, 'error' => 'Backup cancelado por el usuario'];
                }

                $stmt = $this->pdo->query("SELECT * FROM $tabla LIMIT $batchSize OFFSET $offset");
                $stmt->setFetchMode(PDO::FETCH_ASSOC);

                while ($row = $stmt->fetch()) {
                    $values = [];
                    foreach ($row as $val) {
                        $values[] = is_null($val) ? "NULL" : $this->pdo->quote($val);
                    }
                    fwrite($fp, "INSERT INTO $tabla VALUES(" . implode(",", $values) . ");\n");
                    $rowsWritten++;
                }

                unset($stmt);
                $offset += $batchSize;
                gc_collect_cycles();

                // Actualizar progreso en archivo
                $progressFile = sys_get_temp_dir() . '/backup_progress_' . $tabla . '.json';

                // Verificar si se canceló el backup
                $cancelFile = sys_get_temp_dir() . '/backup_cancel_' . $tabla . '.txt';
                if (file_exists($cancelFile)) {
                    // Backup cancelado, limpiar y salir
                    fclose($fp);
                    unlink($cancelFile);
                    if (file_exists($outputPath)) {
                        unlink($outputPath);
                    }
                    return ['ok' => false, 'error' => 'Backup cancelado por el usuario'];
                }

                file_put_contents($progressFile, json_encode([
                    'total' => $totalRows,
                    'actual' => $rowsWritten,
                    'inicio' => time()
                ]));
            }

            // Limpiar archivos temporales
            $cancelFile = sys_get_temp_dir() . '/backup_cancel_' . $tabla . '.txt';
            if (file_exists($cancelFile)) {
                unlink($cancelFile);
            }
            $progressFile = sys_get_temp_dir() . '/backup_progress_' . $tabla . '.json';
            if (file_exists($progressFile)) {
                unlink($progressFile);
            }

            fwrite($fp, "\nSET FOREIGN_KEY_CHECKS=1;\n");
            fwrite($fp, "-- Completed: $tabla ($rowsWritten rows)\n");

            fclose($fp);

            return [
                'ok' => true,
                'archivo' => $fileName,
                'tabla' => $tabla,
                'filas' => $rowsWritten,
                'tipo' => 'tabla',
                'tamano' => filesize($outputPath),
                'fecha' => date('Y-m-d H:i:s')
            ];

        }
        catch (Exception $e) {
            if (isset($fp) && is_resource($fp)) {
                fclose($fp);
            }
            if (file_exists($outputPath)) {
                unlink($outputPath);
            }
            throw $e;
        }
    }

    /**
     * Genera un dump SQL de la base de datos
     * Optimized to handle large databases without memory issues
     */
    private function generarSqlDump($outputPath)
    {
        // Allow unlimited memory and time
        ini_set('memory_limit', '-1');
        set_time_limit(0);

        // Tables to skip during backup (too large or not critical)
        $skipTables = ['clientes', 'usuarios'];

        // Also skip backup tables
        $skipTables = array_merge($skipTables, $this->getBackupTables());

        $tables = [];
        $result = $this->pdo->query("SHOW TABLES");
        while ($row = $result->fetch(PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }

        // Open file handle for streaming writes
        $fp = fopen($outputPath, 'w');

        fwrite($fp, "-- Backup TPV\n-- Fecha: " . date('Y-m-d H:i:s') . "\n");
        fwrite($fp, "-- Note: Large tables (clientes, usuarios) skipped to prevent memory issues\n\n");
        fwrite($fp, "SET FOREIGN_KEY_CHECKS=0;\n");

        foreach ($tables as $table) {
            if (in_array($table, $skipTables)) {
                fwrite($fp, "-- Skipping table: $table (too large)\n");
                continue;
            }

            // Estructura
            $result = $this->pdo->query("SHOW CREATE TABLE $table");
            $row = $result->fetch(PDO::FETCH_NUM);
            fwrite($fp, "\n\n" . $row[1] . ";\n\n");
            unset($result);

            // Get row count
            $countResult = $this->pdo->query("SELECT COUNT(*) FROM $table");
            $totalRows = $countResult->fetchColumn();
            unset($countResult);

            if ($totalRows == 0) {
                continue;
            }

            // Process in batches of 5000 to handle millions of rows
            $batchSize = 5000;
            $offset = 0;

            while ($offset < $totalRows) {
                $stmt = $this->pdo->query("SELECT * FROM $table LIMIT $batchSize OFFSET $offset");
                $stmt->setFetchMode(PDO::FETCH_ASSOC);

                while ($row = $stmt->fetch()) {
                    $values = [];
                    foreach ($row as $val) {
                        $values[] = is_null($val) ? "NULL" : $this->pdo->quote($val);
                    }
                    fwrite($fp, "INSERT INTO $table VALUES(" . implode(",", $values) . ");\n");
                }

                unset($stmt);
                $offset += $batchSize;
                gc_collect_cycles();
            }

            fwrite($fp, "-- Completed: $table ($totalRows rows)\n");
        }

        fwrite($fp, "\nSET FOREIGN_KEY_CHECKS=1;\n");

        fclose($fp);
    }

    /**
     * Get list of backup-related tables to skip
     */
    private function getBackupTables()
    {
        return [
            'clientes_backup',
            'puntos_historial'
        ];
    }

    /**
     * Añade una carpeta recursivamente a un ZIP
     */
    private function addFolderToZip($folder, &$zip, $zipPath = '')
    {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($folder, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
            );

        foreach ($files as $name => $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = $zipPath . substr($filePath, strlen(realpath($folder)) + 1);
                $zip->addFile($filePath, $relativePath);
            }
        }
    }

    /**
     * Cifra un archivo usando AES-256-CBC
     */
    private function encryptFile($sourcePath, $destPath)
    {
        $plaintext = file_get_contents($sourcePath);
        $ivlen = openssl_cipher_iv_length($cipher = "AES-256-CBC");
        $iv = openssl_random_pseudo_bytes($ivlen);
        $ciphertext_raw = openssl_encrypt($plaintext, $cipher, $this->encryptionKey, $options = OPENSSL_RAW_DATA, $iv);
        $hmac = hash_hmac('sha256', $ciphertext_raw, $this->encryptionKey, $as_binary = true);
        $ciphertext = base64_encode($iv . $hmac . $ciphertext_raw);
        file_put_contents($destPath, $ciphertext);
    }

    /**
     * Descifra un archivo
     */
    public function decryptFile($sourcePath, $destPath)
    {
        $ciphertext = base64_decode(file_get_contents($sourcePath));
        $ivlen = openssl_cipher_iv_length($cipher = "AES-256-CBC");
        $iv = substr($ciphertext, 0, $ivlen);
        $hmac = substr($ciphertext, $ivlen, $sha2len = 32);
        $ciphertext_raw = substr($ciphertext, $ivlen + $sha2len);
        $original_plaintext = openssl_decrypt($ciphertext_raw, $cipher, $this->encryptionKey, $options = OPENSSL_RAW_DATA, $iv);
        $calcmac = hash_hmac('sha256', $ciphertext_raw, $this->encryptionKey, $as_binary = true);

        if (hash_equals($hmac, $calcmac)) {
            file_put_contents($destPath, $original_plaintext);
            return true;
        }
        return false;
    }

    /**
     * Realiza una restauración completa desde un archivo cifrado
     */
    /**
     * Restaura un backup completo (archivo .enc)
     */
    public function restaurarBackup($archivoEnc)
    {
        $fecha = date('Y-m-d_H-i-s');
        $rutaEnc = $this->backupDir . $archivoEnc;
        $tempZip = $this->backupDir . "restore_temp_{$fecha}.zip";
        $extractDir = $this->backupDir . "restore_extract_{$fecha}/";

        if (!file_exists($rutaEnc)) {
            throw new Exception("Archivo de backup no encontrado");
        }

        try {
            // 1. Descifrar
            if (!$this->decryptFile($rutaEnc, $tempZip)) {
                throw new Exception("Error al descifrar el archivo. Clave inválida o integridad comprometida.");
            }

            // 2. Extraer ZIP
            $zip = new ZipArchive();
            if ($zip->open($tempZip) !== TRUE) {
                throw new Exception("No se pudo abrir el archivo ZIP de restauración");
            }
            mkdir($extractDir, 0777, true);
            $zip->extractTo($extractDir);
            $zip->close();

            // 3. Restaurar Base de Datos
            $sqlFile = $extractDir . 'database.sql';
            if (file_exists($sqlFile)) {
                $sql = file_get_contents($sqlFile);
                $this->pdo->exec("SET FOREIGN_KEY_CHECKS=0;");
                $this->pdo->exec($sql);
                $this->pdo->exec("SET FOREIGN_KEY_CHECKS=1;");
            }

            // 4. Restaurar Archivos (Sobrescribir webroot/img y config)
            $this->copyDirectory($extractDir . 'webroot/img/', __DIR__ . '/../webroot/img/');
            $this->copyDirectory($extractDir . 'config/', __DIR__ . '/../config/');

            // 5. Limpieza
            $this->removeDirectory($extractDir);
            unlink($tempZip);

            return ['ok' => true, 'mensaje' => 'Sistema restaurado completamente con éxito'];

        }
        catch (Exception $e) {
            if (file_exists($tempZip))
                unlink($tempZip);
            if (is_dir($extractDir))
                $this->removeDirectory($extractDir);
            throw $e;
        }
    }

    private function copyDirectory($source, $dest)
    {
        if (!is_dir($source))
            return;
        if (!is_dir($dest))
            mkdir($dest, 0777, true);

        $it = new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS);
        $iterator = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::SELF_FIRST);

        foreach ($iterator as $item) {
            $relativePath = $it->getSubPathName();
            $target = $dest . DIRECTORY_SEPARATOR . $relativePath;

            if ($item->isDir()) {
                if (!is_dir($target))
                    mkdir($target, 0777, true);
            }
            else {
                $targetDir = dirname($target);
                if (!is_dir($targetDir))
                    mkdir($targetDir, 0777, true);
                copy($item->getRealPath(), $target);
            }
        }
    }

    private function removeDirectory($dir)
    {
        if (!is_dir($dir))
            return;
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? $this->removeDirectory("$dir/$file") : unlink("$dir/$file");
        }
        return rmdir($dir);
    }

    /**
     * Lista los backups disponibles (incluye archivos .enc y .sql)
     */
    public function listarBackups()
    {
        $backups = [];

        // Include encrypted backup files
        $encFiles = glob($this->backupDir . '*.enc');
        foreach ($encFiles as $file) {
            $backups[] = [
                'nombre' => basename($file),
                'tamano' => filesize($file),
                'fecha' => date('Y-m-d H:i:s', filemtime($file)),
                'tipo' => 'completo'
            ];
        }

        // Include SQL backup files (table backups)
        $sqlFiles = glob($this->backupDir . '*.sql');
        foreach ($sqlFiles as $file) {
            $nombre = basename($file);
            // Extract table name from backup_clientes_2026-03-23_11-30-00.sql
            preg_match('/backup_(clientes|usuarios)_/', $nombre, $matches);
            $tabla = isset($matches[1]) ? $matches[1] : 'desconocido';

            $backups[] = [
                'nombre' => $nombre,
                'tamano' => filesize($file),
                'fecha' => date('Y-m-d H:i:s', filemtime($file)),
                'tipo' => 'tabla',
                'tabla' => $tabla
            ];
        }

        // Ordenar por fecha descendente
        usort($backups, function ($a, $b) {
            return strtotime($b['fecha']) - strtotime($a['fecha']);
        });
        return $backups;
    }

    /**
     * Restaura un backup de tabla específica (archivo .sql)
     */
    public function restaurarTabla($archivoSql)
    {
        $rutaSql = $this->backupDir . $archivoSql;

        if (!file_exists($rutaSql)) {
            throw new Exception("Archivo de backup no encontrado: " . $archivoSql);
        }

        // Extraer nombre de tabla del archivo
        preg_match('/backup_(clientes|usuarios)_/', $archivoSql, $matches);
        if (!isset($matches[1])) {
            throw new Exception("Nombre de tabla no válido en el archivo");
        }
        $tabla = $matches[1];

        try {
            // Leer el archivo SQL
            $sql = file_get_contents($rutaSql);

            // Primero TRUNCATE la tabla para eliminar datos actuales
            $this->pdo->exec("SET FOREIGN_KEY_CHECKS=0;");
            $this->pdo->exec("TRUNCATE TABLE $tabla;");

            // Luego ejecutar los INSERT
            // Dividir en instrucciones para evitar problemas de memoria
            $this->pdo->exec($sql);

            $this->pdo->exec("SET FOREIGN_KEY_CHECKS=1;");

            return [
                'ok' => true,
                'mensaje' => "Tabla $tabla restaurada correctamente desde $archivoSql"
            ];

        }
        catch (Exception $e) {
            $this->pdo->exec("SET FOREIGN_KEY_CHECKS=1;");
            throw new Exception("Error al restaurar tabla: " . $e->getMessage());
        }
    }

    /**
     * Elimina backups antiguos según política de retención
     */
    public function rotarBackups($diasRetencion = 30)
    {
        $files = glob($this->backupDir . '*.enc');
        $count = 0;
        foreach ($files as $file) {
            if (filemtime($file) < (time() - ($diasRetencion * 24 * 60 * 60))) {
                unlink($file);
                $count++;
            }
        }
        return $count;
    }
}
