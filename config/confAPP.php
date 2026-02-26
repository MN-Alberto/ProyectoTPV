<?php

/*
 * Autor: Alberto Méndez 
 * Fecha de actualización: 24/02/2026
 * 
 */


//Array asociativo para las distintas páginas del controlador con sus respectivas rutas.
$controller = [
    "login" => "controller/cLogin.php",
    "cajero" => "controller/cCajero.php"
];

//Array asociativo para las distintas páginas de la vista con sus respectivas rutas.
$view = [
    "Layout" => "view/Layout.php",
    "login" => "view/vLogin.php",
    "cajero" => "view/vCajero.php"
];

?>