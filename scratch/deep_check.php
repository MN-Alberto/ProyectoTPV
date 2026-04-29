<?php
require_once(__DIR__ . '/../config/confDB.php');
require_once(__DIR__ . '/../core/conexionDB.php');
$pdo = ConexionDB::getInstancia()->getConexion();
$res = $pdo->query("SELECT COUNT(*) FROM verifactu_cola_envios WHERE estado = 'enviando'")->fetchColumn();
echo "TOTAL ENVIANDO: $res\n";
$res2 = $pdo->query("SELECT id, num_documento, estado FROM verifactu_cola_envios WHERE estado != 'enviado' LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
print_r($res2);
