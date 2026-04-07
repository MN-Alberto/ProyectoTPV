@echo off
set PHP_BIN=php
set SCRIPT_PATH=%~dp0auto_backup.php

echo [%DATE% %TIME%] Ejecutando Backup del TPV...
%PHP_BIN% "%SCRIPT_PATH%"
if %ERRORLEVEL% NEQ 0 (
    echo [%DATE% %TIME%] ERROR: El backup ha fallado. Revisar backups/backup_log.txt
    exit /b %ERRORLEVEL%
)

echo [%DATE% %TIME%] SUCCESS: Backup completado correctamente.
pause
