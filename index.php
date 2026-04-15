<?php
/*
 * Autor: Alberto Méndez 
 * Fecha de actualización: 24/02/2026
 */

// 1. CARGA LOS MODELOS PRIMERO (Importante para que la sesión reconozca los objetos)
require_once("./model/Usuario.php");
// Añade aquí otros modelos si guardas objetos de ellos en la sesión

// 2. CONFIGURACIÓN
require_once("./config/confAPP.php");
require_once("./config/confDB.php");

date_default_timezone_set('Europe/Madrid');

// 3. INICIAR SESIÓN (Después de cargar los modelos)
session_start();

// 3.5 SISTEMA DE INTERNACIONALIZACIÓN (i18n)
require_once("./lang/lang.php");

// 4. LÓGICA DE NAVEGACIÓN
if (isset($_GET['ctl']) && isset($controller[$_GET['ctl']])) {
    $_SESSION['paginaEnCurso'] = $_GET['ctl'];
}

if (!isset($_SESSION['paginaEnCurso'])) {
    $_SESSION['paginaEnCurso'] = 'login';
}

// 5. CARGA DEL CONTROLADOR
// Verificamos que el archivo existe antes de cargarlo para evitar errores fatales
if (isset($controller[$_SESSION['paginaEnCurso']]) && file_exists($controller[$_SESSION['paginaEnCurso']])) {
    require_once($controller[$_SESSION['paginaEnCurso']]);
} else {
    // Si algo falla, forzamos login
    require_once($controller['login']);
}
?>