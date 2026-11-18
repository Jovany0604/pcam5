<?php
/* =============================================================================
   app/services/OsiJsonClient.php - Cliente GET para una fuente JSON externa
   =============================================================================
   Hace únicamente lecturas GET y espera una respuesta JSON.
   Soporta autenticación none, basic, bearer y windows/kerberos.
   ============================================================================= */

class OsiJsonClient
{
    private $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

    /** Ejecuta un GET y devuelve el JSON convertido a array asociativo. */
    public function get($endpoint, $query = [])
    {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('La extensión cURL de PHP está desactivada. Activa extension=curl en php.ini.');
        }

        $url = $this->armarUrl($endpoint, $query);
        $ch = curl_init($url);

        $headers = [
            'Accept: application/json',
            'Cache-Control: no-cache',
        ];

        $auth = strtolower(trim((string) ($this->config->autenticacion ?? 'none')));
        $usuario = $this->secreto('PCAM_OSI_USUARIO', 'usuario');
        $password = $this->secreto('PCAM_OSI_PASSWORD', 'password');
        $token = $this->secreto('PCAM_OSI_TOKEN', 'token');

        if ($auth === 'bearer') {
            if ($token === '') {
                throw new RuntimeException('La fuente OSI está configurada como bearer, pero falta el token.');
            }
            $headers[] = 'Authorization: Bearer ' . $token;
        } elseif ($auth === 'basic') {
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_USERPWD, $usuario . ':' . $password);
        } elseif ($auth === 'windows' || $auth === 'kerberos') {
            if (defined('CURLAUTH_NEGOTIATE')) {
                curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_NEGOTIATE);
            } else {
                throw new RuntimeException('La instalación de cURL no soporta autenticación Windows/Negotiate.');
            }
            curl_setopt($ch, CURLOPT_USERPWD, $usuario . ':' . $password);
        } elseif ($auth !== '' && $auth !== 'none') {
            throw new RuntimeException('Autenticación no soportada: ' . $auth . '. Usa none, basic, bearer o windows.');
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => max(1, (int) ($this->config->timeout_conexion ?? 5)),
            CURLOPT_TIMEOUT => max(1, (int) ($this->config->timeout ?? 15)),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_ENCODING => '',
            CURLOPT_SSL_VERIFYPEER => $this->boolConfig('verificar_tls', true),
            CURLOPT_SSL_VERIFYHOST => $this->boolConfig('verificar_tls', true) ? 2 : 0,
        ]);

        $body = curl_exec($ch);
        $curlError = curl_error($ch);
        $curlNumber = curl_errno($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if ($body === false) {
            throw new RuntimeException('No se pudo conectar con la fuente OSI (cURL ' . $curlNumber . '): ' . $curlError);
        }

        if ($status < 200 || $status >= 300) {
            throw new RuntimeException('La fuente OSI respondió HTTP ' . $status . ' en ' . $this->rutaVisible($endpoint) . '.');
        }

        try {
            $json = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $ex) {
            throw new RuntimeException('La fuente OSI no devolvió un JSON válido.');
        }

        if (!is_array($json)) {
            throw new RuntimeException('La fuente OSI devolvió un JSON que no es objeto ni lista.');
        }

        return $json;
    }

    private function armarUrl($endpoint, $query)
    {
        $endpoint = trim((string) $endpoint);
        if ($endpoint === '') {
            throw new RuntimeException('Falta configurar el endpoint GET de la fuente OSI.');
        }

        if (preg_match('#^https?://#i', $endpoint)) {
            $url = $endpoint;
        } else {
            $base = rtrim((string) ($this->config->base_url ?? ''), '/');
            if ($base === '') {
                throw new RuntimeException('Falta configurar datos_api/base_url en config.xml.');
            }
            $url = $base . '/' . ltrim($endpoint, '/');
        }

        if (!empty($query)) {
            $separador = strpos($url, '?') === false ? '?' : '&';
            $url .= $separador . http_build_query($query);
        }

        return $url;
    }

    private function secreto($variable, $campo)
    {
        $valor = getenv($variable);
        if ($valor !== false && $valor !== '') {
            return (string) $valor;
        }
        return trim((string) ($this->config->{$campo} ?? ''));
    }

    private function boolConfig($campo, $defecto)
    {
        $valor = trim((string) ($this->config->{$campo} ?? ''));
        if ($valor === '') {
            return $defecto;
        }
        return in_array(strtolower($valor), ['1', 'true', 'si', 'yes'], true);
    }

    private function rutaVisible($endpoint)
    {
        return preg_replace('#^https?://[^/]+#i', '', (string) $endpoint);
    }
}
