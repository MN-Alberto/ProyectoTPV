<?php
/**
 * API para gestionar las ventas 
 * @author Alberto Méndez
 * @version 1.2 (02/03/2026)
 */


// Requerimos los archivos necesarios
require_once(__DIR__ . '/../config/confDB.php');
require_once(__DIR__ . '/../model/Venta.php');

// Establecemos el tipo de contenido de la respuesta, en este caso JSON
header('Content-Type: application/json; charset=utf-8');

// Ventas de los últimos 7 días agrupadas por día
// Obtenemos la conexión a la base de datos
$conexion = ConexionDB::getInstancia()->getConexion();
// Preparamos la consulta para obtener las ventas de los últimos 7 días agrupadas por día
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
// Ejecutamos la consulta
$stmt->execute();
// Obtenemos los resultados en forma de array asociativo
$filas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Rellenamos los días vacíos para que siempre salgan 7 días
$resultado = [];
for ($i = 6; $i >= 0; $i--) {
    $dia = date('Y-m-d', strtotime("-$i days"));
    $resultado[$dia] = ['dia' => $dia, 'total' => 0, 'pedidos' => 0];
}
foreach ($filas as $fila) {
    $resultado[$fila['dia']] = $fila;
}

// Mostramos los resultados en formato JSON
echo json_encode(array_values($resultado));
?>