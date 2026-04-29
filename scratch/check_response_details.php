<?php
require_once(__DIR__ . '/../config/confDB.php');
require_once(__DIR__ . '/../core/conexionDB.php');
$pdo = ConexionDB::getInstancia()->getConexion();
$res = $pdo->query("SELECT respuesta_xml FROM verifactu_cola_envios WHERE estado = 'enviado' ORDER BY id DESC LIMIT 1")->fetchColumn();
if (preg_match('/<[^:]*:EstadoRegistro[^>]*>([^<]+)</s', $res, $m)) echo "ESTADO: " . $m[1] . "\n";
if (preg_match('/<[^:]*:CSV[^>]*>([^<]+)</s', $res, $m)) echo "CSV: " . $m[1] . "\n";
if (preg_match('/<[^:]*:CodigoErrorRegistro[^>]*>([^<]+)</s', $res, $m)) echo "ERROR_CODE: " . $m[1] . "\n";
if (preg_match('/<[^:]*:DescripcionErrorRegistro[^>]*>([^<]+)</s', $res, $m)) echo "ERROR_DESC: " . $m[1] . "\n";
