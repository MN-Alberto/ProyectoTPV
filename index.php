<?php
/*
 * Autor: Alberto Méndez 
 * Fecha de actualización: 24/02/2026
 */

require_once("./config/confAPP.php"); //Incluimos el fichero de configuración de la APP.
require_once("./config/confDB.php"); //Incluimos el fichero de configuración de la BD.

date_default_timezone_set('Europe/Madrid');
session_start(); //Iniciamos o recuperamos la sesión.

if (!isset($_SESSION['paginaEnCurso'])) { //Si la página en curso no existe.
    $_SESSION['paginaEnCurso'] = 'login'; //La crea y le asigna el login.
}

require_once($controller[$_SESSION['paginaEnCurso']]); //Añade el fichero del controlador en base a la página en curso.

?>