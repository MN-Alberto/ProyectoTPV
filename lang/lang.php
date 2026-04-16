<?php
/**
 * Sistema de Internacionalización (i18n) del TPV
 * 
 * Detecta idioma, carga traducciones y define la función t() para uso global.
 * Soporta claves anidadas con dot-notation: t('buttons.pay')
 * 
 * @author Alberto Méndez
 * @version 1.0
 */

// Idiomas soportados
$IDIOMAS_SOPORTADOS = ['es', 'en', 'fr', 'de', 'ru'];
$IDIOMA_DEFAULT = 'es';

// Detectar idioma: GET > SESSION > default
if (isset($_GET['lang']) && in_array($_GET['lang'], $IDIOMAS_SOPORTADOS)) {
    $_SESSION['lang'] = $_GET['lang'];
}

if (!isset($_SESSION['lang']) || !in_array($_SESSION['lang'], $IDIOMAS_SOPORTADOS)) {
    $_SESSION['lang'] = $IDIOMA_DEFAULT;
}

$LANG_CODE = $_SESSION['lang'];

// Cargar archivo de idioma
$langFile = __DIR__ . '/' . $LANG_CODE . '.php';
if (file_exists($langFile)) {
    $LANG = require $langFile;
} else {
    // Fallback al idioma por defecto
    $LANG = require __DIR__ . '/' . $IDIOMA_DEFAULT . '.php';
}

/**
 * Obtiene una traducción usando dot-notation.
 * Ejemplo: t('buttons.pay') => 'Cobrar'
 * 
 * @param string $key Clave de traducción (soporta dot-notation)
 * @param array|null $params Parámetros opcionales para reemplazo (ej: [':name' => 'Juan'])
 * @return string Traducción o la clave si no existe
 */
function t(string $key, ?array $params = null): string
{
    global $LANG;

    $keys = explode('.', $key);
    $value = $LANG;

    foreach ($keys as $k) {
        if (is_array($value) && isset($value[$k])) {
            $value = $value[$k];
        } else {
            // Fallback: devolver la clave
            return $key;
        }
    }

    if (!is_string($value)) {
        return $key;
    }

    // Reemplazo de parámetros
    if ($params) {
        foreach ($params as $placeholder => $replacement) {
            $value = str_replace($placeholder, $replacement, $value);
        }
    }

    return $value;
}
