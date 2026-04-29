<?php
require_once(__DIR__ . '/../config/confDB.php');
require_once(__DIR__ . '/../core/conexionDB.php');
$pdo = ConexionDB::getInstancia()->getConexion();
$res = $pdo->query("SELECT id, num_documento, respuesta_xml, csv_aeat FROM verifactu_cola_envios WHERE estado = 'enviado' ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
echo "ID: " . $res['id'] . "\n";
echo "DOC: " . $res['num_documento'] . "\n";
echo "CSV: " . $res['csv_aeat'] . "\n";
echo "RESPONSE:\n" . $res['respuesta_xml'] . "\n";
