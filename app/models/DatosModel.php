<?php
/* =============================================================================
   app/models/DatosModel.php - MODELO CLIENTE DE LA API MVC DE LA VM
   =============================================================================
   PCAM no inventa ni guarda señales en SQLite. El SQLite de PCAM se conserva
   para usuarios y permisos; las subestaciones y sus señales llegan por GET
   desde la API REST instalada en la máquina virtual.

   Consultas que realiza este modelo:
     ?recurso=subestaciones
     ?recurso=analogicas
     ?recurso=digitales

   La API del ZIP devuelve nombres de campos como Key, Station, Value, State
   y TLQ. Aquí se convierten al contrato que ya utilizan las vistas de PCAM.
   ============================================================================= */

require_once APP_PATH . '/services/OsiJsonClient.php';

class DatosModel
{
    private static $fuente = null;

    /** Lista de subestaciones recibida desde la API de la VM. */
    public static function subestaciones()
    {
        self::cargarFuente();
        return self::$fuente['subestaciones'];
    }

    /** Señales analógicas recibidas desde la API de la VM. */
    public static function analogicas()
    {
        self::cargarFuente();
        return self::$fuente['analogicas'];
    }

    /** Señales digitales/status recibidas desde la API de la VM. */
    public static function estados()
    {
        self::cargarFuente();
        return self::$fuente['status'];
    }

    /** Hace una sola lectura de cada recurso por petición HTTP de PCAM. */
    private static function cargarFuente()
    {
        if (self::$fuente !== null) {
            return;
        }

        $config = Config::load();
        $api = $config->datos_api ?? null;
        if ($api === null || self::texto($api->habilitado ?? '') !== '1') {
            throw new RuntimeException(
                'La API de la VM está deshabilitada. Revisa datos_api/habilitado en config.xml.'
            );
        }

        $endpoint = self::texto($api->endpoint ?? '');
        if ($endpoint === '') {
            throw new RuntimeException(
                'Falta configurar datos_api/endpoint en config.xml.'
            );
        }

        $cliente = new OsiJsonClient($api);

        $respuestaSubestaciones = $cliente->get($endpoint, [
            'recurso' => 'subestaciones',
        ]);
        $subestaciones = self::normalizarSubestaciones(
            self::coleccion($respuestaSubestaciones, ['subestaciones', 'stations'])
        );

        if (count($subestaciones) === 0) {
            throw new RuntimeException(
                'La API respondió, pero no entregó subestaciones.'
            );
        }

        $mapaEstaciones = [];
        foreach ($subestaciones as $subestacion) {
            $mapaEstaciones[self::claveEstacion($subestacion['nombre'])] = $subestacion['codigo'];
        }

        $respuestaAnalogicas = $cliente->get($endpoint, [
            'recurso' => 'analogicas',
        ]);
        $respuestaDigitales = $cliente->get($endpoint, [
            'recurso' => 'digitales',
        ]);

        $analogicas = self::coleccion(
            $respuestaAnalogicas,
            ['analogicas', 'analógicas', 'analog']
        );
        $digitales = self::coleccion(
            $respuestaDigitales,
            ['digitales', 'status', 'estados', 'states']
        );

        self::$fuente = [
            'subestaciones' => $subestaciones,
            'analogicas' => self::normalizarFilas($analogicas, 'analogica', $mapaEstaciones),
            'status' => self::normalizarFilas($digitales, 'status', $mapaEstaciones),
        ];

        if (count(self::$fuente['analogicas']) === 0 && count(self::$fuente['status']) === 0) {
            throw new RuntimeException(
                'La API respondió, pero no entregó señales analógicas ni digitales.'
            );
        }
    }

    /** Normaliza el catálogo {id, nombre} que usa el filtro de PCAM. */
    private static function normalizarSubestaciones($filas)
    {
        $resultado = [];
        $numero = 1;

        foreach ($filas as $fila) {
            if (!is_array($fila)) {
                continue;
            }

            $codigo = self::texto(self::campo($fila, [
                'id', 'codigo', 'code', 'substation_code'
            ]));
            $nombre = self::texto(self::campo($fila, [
                'nombre', 'name', 'estacion', 'station_name'
            ], $codigo));

            if ($codigo === '' && $nombre === '') {
                continue;
            }

            if ($codigo === '') {
                $codigo = 'SUB' . $numero;
            }

            $resultado[] = [
                'id' => $codigo,
                'codigo' => $codigo,
                'nombre' => $nombre !== '' ? $nombre : $codigo,
            ];
            $numero++;
        }

        usort($resultado, static function ($a, $b) {
            return strcasecmp($a['nombre'], $b['nombre']);
        });

        return $resultado;
    }

    /** Convierte la respuesta del API en una lista aunque venga envuelta. */
    private static function coleccion($respuesta, $nombres)
    {
        if (self::esLista($respuesta)) {
            return $respuesta;
        }

        if (!is_array($respuesta)) {
            return [];
        }

        $porNombre = [];
        foreach ($respuesta as $nombre => $valor) {
            $porNombre[self::claveCampo($nombre)] = $valor;
        }

        foreach ($nombres as $nombre) {
            $clave = self::claveCampo($nombre);
            if (!array_key_exists($clave, $porNombre) || !is_array($porNombre[$clave])) {
                continue;
            }

            $valor = $porNombre[$clave];
            if (self::esLista($valor)) {
                return $valor;
            }

            return self::coleccion($valor, ['data', 'items', 'records']);
        }

        return [];
    }

    /** Convierte Key/Station/Value/State/TLQ al formato de las vistas PCAM. */
    private static function normalizarFilas($filas, $tipo, $mapaEstaciones)
    {
        $resultado = [];
        $numero = 1;

        foreach ($filas as $fila) {
            if (!is_array($fila)) {
                continue;
            }

            $estacion = self::texto(self::campo($fila, [
                'estacion', 'station_name', 'substation_name', 'nombre_subestacion', 'station'
            ]));
            $codigo = self::texto(self::campo($fila, [
                'codigo', 'code', 'substation_code', 'station_code', 'substation'
            ]));

            if ($codigo === '' && $estacion !== '') {
                $codigo = $mapaEstaciones[self::claveEstacion($estacion)] ?? '';
            }
            if ($estacion === '' && $codigo !== '') {
                foreach ($mapaEstaciones as $nombre => $valor) {
                    if ($valor === $codigo) {
                        $estacion = $nombre;
                        break;
                    }
                }
            }

            $clave = self::texto(self::campo($fila, [
                'clave', 'key', 'tag', 'point', 'point_name', 'name'
            ]));
            $nombre = self::texto(self::campo($fila, [
                'nombre', 'name', 'description', 'point_description'
            ], $clave));
            $id = self::campo($fila, ['id', 'record_id', 'point_id'], $numero);

            $tlq = self::campo($fila, ['tlq'], null);
            $tlqSeparado = self::interpretarTlq($tlq);

            $etiquetado = self::campo($fila, ['etiquetado', 'tagged', 't'], null);
            $limite = self::campo($fila, ['limite', 'out_of_limit', 'limit', 'l'], null);
            $calidad = self::campo($fila, ['calidad', 'quality', 'good_quality', 'q'], null);

            $base = [
                'id' => is_numeric($id) ? (int) $id : $numero,
                'clave' => $clave,
                'tipo' => self::texto(self::campo(
                    $fila,
                    ['tipo', 'type', 'point_type'],
                    $tipo === 'status' ? 'T_IND' : 'T_ANLG'
                )),
                'codigo' => $codigo,
                'estacion' => $estacion !== '' ? $estacion : $codigo,
                'nombre' => $nombre,
                'etiquetado' => $etiquetado === null
                    ? $tlqSeparado[0]
                    : self::booleano($etiquetado),
                'limite' => $limite === null
                    ? $tlqSeparado[1]
                    : self::booleano($limite),
                'calidad' => $calidad === null
                    ? $tlqSeparado[2]
                    : self::calidad($calidad),
            ];

            if ($tipo === 'status') {
                $base['estado'] = strtoupper(self::texto(self::campo(
                    $fila,
                    ['estado', 'state', 'status'],
                    'NORMAL'
                )));
            } else {
                $valor = self::campo($fila, [
                    'valor', 'value', 'current_value', 'currentvalue'
                ], 0);
                $base['unidad'] = self::texto(self::campo(
                    $fila,
                    ['unidad', 'unit', 'units']
                ));
                $base['valor'] = is_numeric($valor) ? (float) $valor : 0;
            }

            $resultado[] = $base;
            $numero++;
        }

        return $resultado;
    }

    /** Busca una clave sin depender de mayúsculas, guiones o espacios. */
    private static function campo($fila, $nombres, $defecto = '')
    {
        $normalizados = [];
        foreach ($fila as $clave => $valor) {
            $normalizados[self::claveCampo($clave)] = $valor;
        }

        foreach ($nombres as $nombre) {
            $llave = self::claveCampo($nombre);
            if (array_key_exists($llave, $normalizados)) {
                return $normalizados[$llave];
            }
        }

        return $defecto;
    }

    private static function claveCampo($valor)
    {
        return strtolower(preg_replace('/[^a-z0-9]+/i', '_', (string) $valor));
    }

    private static function claveEstacion($valor)
    {
        $valor = strtolower(trim((string) $valor));
        return strtr($valor, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'ü' => 'u', 'ñ' => 'n',
        ]);
    }

    private static function texto($valor)
    {
        return trim((string) $valor);
    }

    private static function booleano($valor)
    {
        if (is_bool($valor)) {
            return $valor ? 1 : 0;
        }
        if (is_numeric($valor)) {
            return ((int) $valor) !== 0 ? 1 : 0;
        }
        return in_array(strtolower(trim((string) $valor)), [
            '1', 'true', 'si', 'sí', 'yes', 'ok', 'alarm'
        ], true) ? 1 : 0;
    }

    /** En PCAM, calidad=1 significa buena y calidad=0 significa mala. */
    private static function calidad($valor)
    {
        if ($valor === '' || $valor === null) {
            return 1;
        }
        if (is_numeric($valor)) {
            return ((int) $valor) !== 0 ? 1 : 0;
        }
        return in_array(strtolower(trim((string) $valor)), [
            'good', 'buena', 'ok', 'true', '1', '✓'
        ], true) ? 1 : 0;
    }

    /** Traduce el TLQ del API: ✓=bueno, T=etiquetado, L=límite, Q=mala calidad. */
    private static function interpretarTlq($valor)
    {
        if ($valor === null) {
            return [0, 0, 1];
        }

        $texto = trim((string) $valor);
        if ($texto === '') {
            return [0, 0, 0];
        }

        return [
            strpos($texto, 'T') !== false ? 1 : 0,
            strpos($texto, 'L') !== false ? 1 : 0,
            strpos($texto, 'Q') === false ? 1 : 0,
        ];
    }

    private static function esLista($valor)
    {
        if (!is_array($valor)) {
            return false;
        }
        if ($valor === []) {
            return true;
        }
        return array_keys($valor) === range(0, count($valor) - 1);
    }
}
