<?php
$_SESSION = [
    'idUsuario' => 1,
    'nombreUsuario' => 'Admin',
    'rolUsuario' => 'admin',
    'paginaEnCurso' => 'cajero',
    'lang' => 'es',
    'permisosUsuario' => 'modificar_precios,producto_comodin,retirar_dinero'
];

// Mock t() function if needed, or just include lang
require_once("model/Usuario.php");
require_once("config/confAPP.php");
require_once("config/confDB.php");
require_once("lang/lang.php");

// Set up the view
$_SESSION['paginaEnCurso'] = 'cajero';

// Include the controller but don't exit/header
// We need to capture the output
ob_start();
include("controller/cCajero.php");
$output = ob_get_clean();

file_put_contents('rendered_output.html', $output);
echo "Rendered to rendered_output.html\n";
?>
