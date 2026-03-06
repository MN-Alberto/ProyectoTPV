<?php

/**
 * Configuración de la aplicación
 * @author Alberto Méndez
 * @version 1.2 (02/03/2026)
 */


//Array asociativo para las distintas páginas del controlador con sus respectivas rutas.
$controller = [
    "login" => "controller/cLogin.php",
    "cajero" => "controller/cCajero.php",
    "admin" => "controller/cAdmin.php"
];

//Array asociativo para las distintas páginas de la vista con sus respectivas rutas.
$view = [
    "Layout" => "view/Layout.php",
    "login" => "view/vLogin.php",
    "cajero" => "view/vCajero.php",
    "admin" => "view/vAdmin.php"
];

?>