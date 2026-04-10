<?php
/**
 * Cache simple en archivos para informes pesados
 * Optimización para 2.000.000 de registros
 */

class Cache
{

    private static $rutaCache = __DIR__ . '/../cache/';
    private static $duracion = 300; // 5 minutos

    public static function init()
    {
        if (!file_exists(self::$rutaCache)) {
            mkdir(self::$rutaCache, 0777, true);
        }
    }

    public static function get($clave)
    {
        self::init();
        $fichero = self::$rutaCache . md5($clave) . '.cache';

        if (!file_exists($fichero)) {
            return null;
        }

        $datos = file_get_contents($fichero);
        $datos = unserialize($datos);

        if ($datos['expiracion'] < time()) {
            unlink($fichero);
            return null;
        }

        return $datos['contenido'];
    }

    public static function set($clave, $contenido, $duracion = null)
    {
        self::init();
        $fichero = self::$rutaCache . md5($clave) . '.cache';

        $datos = [
            'expiracion' => time() + ($duracion ?? self::$duracion),
            'contenido' => $contenido
        ];

        file_put_contents($fichero, serialize($datos));
    }

    public static function limpiar()
    {
        $ficheros = glob(self::$rutaCache . '*.cache');
        foreach ($ficheros as $f) {
            unlink($f);
        }
    }
}