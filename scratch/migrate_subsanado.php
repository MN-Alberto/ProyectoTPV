<?php
require_once(__DIR__ . '/../config/confDB.php');
require_once(__DIR__ . '/../core/conexionDB.php');

try {
    $pdo = ConexionDB::getInstancia()->getConexion();
    // Intentar alterar la tabla para incluir 'subsanado' en el ENUM (si es enum) o asegurar VARCHAR
    // Primero vemos qué tiene
    $stmt = $pdo->query("DESCRIBE verifactu_cola_envios");
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach($cols as $col) {
        if ($col['Field'] === 'estado') {
            echo "Estado actual: " . $col['Type'] . "\n";
            if (strpos($col['Type'], 'enum') !== false) {
                $newType = str_replace(")", ",'subsanado')", $col['Type']);
                $pdo->exec("ALTER TABLE verifactu_cola_envios MODIFY COLUMN estado $newType");
                echo "Alterado a: $newType\n";
            }
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
