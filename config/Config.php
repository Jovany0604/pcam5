<?php
/* =============================================================================
   config/Config.php  -  Lee y guarda config/config.xml
   =============================================================================
   Leer:
       $config = Config::load();
       echo $config->pagina->titulo1;              // texto
       echo $config->pagina->titulo1['color'];     // atributo
   Guardar (lo usa AdminController::guardarConfiguracion):
       $config->pagina->titulo1 = 'Nuevo título';
       Config::guardar($config);
   ============================================================================= */

class Config
{
    private static $datos = null;

    /** Lee config.xml una sola vez por petición. */
    public static function load()
    {
        if (self::$datos === null) {
            $archivo = CONFIG_PATH . '/config.xml';

            if (!is_file($archivo)) {
                die('No se encontró el archivo de configuración: config/config.xml');
            }

            self::$datos = simplexml_load_file($archivo);

            if (self::$datos === false) {
                die('El archivo config/config.xml tiene un error de formato.');
            }
        }
        return self::$datos;
    }

    /** Escribe los cambios en config.xml. Devuelve true si se pudo guardar. */
    public static function guardar($config)
    {
        return $config->asXML(CONFIG_PATH . '/config.xml') !== false;
    }
}
