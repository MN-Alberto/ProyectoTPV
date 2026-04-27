<?php
require_once(__DIR__ . '/config/confDB.php');

try {
    $conexion = ConexionDB::getInstancia()->getConexion();
    $sql = file_get_contents(__DIR__ . '/scriptDB/add_verifactu_rectificativa.sql');
    
    // Ejecutar statements (necesitamos emulación preparada para ejecutar múltiples queries a la vez, 
    // o simplemente usamos PDO::exec)
    $conexion->setAttribute(PDO::ATTR_EMULATE_PREPARES, 0);
    $queries = explode(';', $sql);
    
    foreach ($queries as $query) {
        $query = trim($query);
        if (!empty($query)) {
            $conexion->exec($query);
        }
    }
    
    echo "Migracion completada exitosamente.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
