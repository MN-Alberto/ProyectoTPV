<?php
/**
 * Constantes de Infraestructura de Datos.
 * Contiene las credenciales y el DNS para la conexión a MySQL/MariaDB.
 * 
 * @author Alberto Méndez
 * @version 1.0 (2026)
 */
const RUTA = 'mysql:host=localhost;dbname=ProyectoTPV';
const USUARIO = 'root';
const PASS = '';

// --- CONFIGURACIÓN VERIFACTU ---
const TPV_NIF = '99999910G';
const TPV_RAZON_SOCIAL = 'CERTIFICADO FISICA PRUEBAS';
const TPV_DIRECCION = 'C/ Falsa 123, Madrid';
const AEAT_URL_VERIFACTU = 'https://prewww1.aeat.es/wlpl/TIKE-CONT/ws/SistemaFacturacion/VerifactuSOAP';
const CERT_PATH = __DIR__ . '/../certs/99999910G_prueba.pfx';
const CERT_PASS = '1234';
?>