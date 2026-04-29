<?php
require_once(__DIR__ . '/../config/confDB.php');
require_once(__DIR__ . '/../core/conexionDB.php');
$pdo = ConexionDB::getInstancia()->getConexion();
$res = $pdo->query('SELECT id, num_documento, estado, fecha_ultimo_intento FROM verifactu_cola_envios ORDER BY id DESC LIMIT 5')->fetchAll(PDO::FETCH_ASSOC);
print_r($res);
