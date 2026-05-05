<?php
/**
 * Sistema de Internacionalización (i18n) del TPV
 * 
 * Este archivo gestiona la detección del idioma del usuario, la carga de los
 * archivos de traducción correspondientes y proporciona una función global
 * para acceder a las cadenas de texto traducidas.
 * 
 * @author Alberto Méndez
 * @version 1.1
 */

/**
 * Configuración de Idiomas
 * -----------------------
 * $IDIOMAS_SOPORTADOS: Lista de códigos de idioma que tienen archivo de traducción.
 * $IDIOMA_DEFAULT: Idioma que se usará si no hay preferencia o el solicitado no existe.
 */
$IDIOMAS_SOPORTADOS = ['es', 'en', 'fr', 'de', 'ru'];
$IDIOMA_DEFAULT = 'es';

/**
 * Lógica de Selección de Idioma
 * ----------------------------
 * 1. Prioridad: Parámetro URL (?lang=xx) -> Sesión activa -> Idioma por defecto.
 * 2. Se valida que el idioma solicitado esté en la lista de soportados para evitar errores.
 */
if (isset($_GET['lang']) && in_array($_GET['lang'], $IDIOMAS_SOPORTADOS)) {
    $_SESSION['lang'] = $_GET['lang'];
}

// Si no hay idioma en sesión o el que hay no es válido, asignamos el por defecto
if (!isset($_SESSION['lang']) || !in_array($_SESSION['lang'], $IDIOMAS_SOPORTADOS)) {
    $_SESSION['lang'] = $IDIOMA_DEFAULT;
}

// Código de idioma final que se utilizará en esta ejecución
$LANG_CODE = $_SESSION['lang'];

/**
 * Carga de Diccionario
 * -------------------
 * Intenta cargar el archivo PHP que contiene el array de traducciones.
 * El archivo debe estar en la misma carpeta y llamarse como el código de idioma (ej: es.php).
 */
$langFile = __DIR__ . '/' . $LANG_CODE . '.php';
if (file_exists($langFile)) {
    // El archivo de idioma debe retornar un array asociativo
    $LANG = require $langFile;
} else {
    // Si falla la carga, recurrimos al diccionario por defecto como medida de seguridad
    $LANG = require __DIR__ . '/' . $IDIOMA_DEFAULT . '.php';
}

/**
 * Función Global de Traducción: t()
 * --------------------------------
 * Es la herramienta principal para mostrar textos en la interfaz.
 * 
 * Características:
 * - Dot-notation: Permite acceder a niveles profundos de un array usando puntos.
 * - Parámetros: Permite inyectar valores dinámicos en las cadenas (ej: "Hola :nombre").
 * - Fallback: Si la clave no existe, devuelve la propia clave para facilitar el depurado.
 * 
 * @param string $key Clave de traducción (ej: 'nav.home' o 'messages.success_save')
 * @param array|null $params Array asociativo de búsqueda y reemplazo [':label' => 'valor']
 * @return string Texto traducido o clave original
 */
function t(string $key, ?array $params = null): string
{
    global $LANG;

    // Dividimos la clave por los puntos para navegar por el array asociativo
    $keys = explode('.', $key);
    $value = $LANG;

    // Navegación recursiva por el array de idiomas
    foreach ($keys as $k) {
        if (is_array($value) && isset($value[$k])) {
            $value = $value[$k];
        } else {
            // Si perdemos el rastro de la clave, cancelamos y devolvemos el string original
            return $key;
        }
    }

    // Si el resultado final no es un string (es un array intermedio), no es una traducción válida
    if (!is_string($value)) {
        return $key;
    }

    /**
     * Procesamiento de Parámetros Dinámicos
     * Si se pasan parámetros, realizamos una sustitución simple de texto.
     */
    if ($params) {
        foreach ($params as $placeholder => $replacement) {
            $value = str_replace($placeholder, $replacement, $value);
        }
    }

    return $value;
}
