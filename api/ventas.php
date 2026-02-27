<?php
require_once(__DIR__ . '/../config/confDB.php');
require_once(__DIR__ . '/../model/Venta.php');

header('Content-Type: application/json; charset=utf-8');

// Ventas de los últimos 7 días agrupadas por día
$conexion = ConexionDB::getInstancia()->getConexion();
$stmt = $conexion->prepare("
    SELECT 
        DATE(fecha) as dia,
        SUM(total) as total,
        COUNT(*) as pedidos
    FROM ventas
    WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY DATE(fecha)
    ORDER BY dia ASC
");
$stmt->execute();
$filas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Rellenar días vacíos para que siempre salgan 7 días
$resultado = [];
for ($i = 6; $i >= 0; $i--) {
    $dia = date('Y-m-d', strtotime("-$i days"));
    $resultado[$dia] = ['dia' => $dia, 'total' => 0, 'pedidos' => 0];
}
foreach ($filas as $fila) {
    $resultado[$fila['dia']] = $fila;
}

echo json_encode(array_values($resultado));
?>