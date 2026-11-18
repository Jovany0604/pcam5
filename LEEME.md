# PCAM MVC conectado a la API REST de la VM

Este proyecto conserva el patrón MVC. La sección Datos ya no toma señales
inventadas de SQLite: `app/models/DatosModel.php` consulta por GET la API
configurada en `config/config.xml`.

## Configuración actual

La configuración apunta a:

`https://192.168.1.41/api-pcam-demo/public/index.php`

El modelo solicita:

- `?recurso=subestaciones`
- `?recurso=analogicas`
- `?recurso=digitales`

Si la IP de la VM cambia, edita solamente `config/config.xml`:

```xml
<base_url>https://IP_DE_LA_VM</base_url>
<endpoint>/api-pcam-demo/public/index.php</endpoint>
```

Si Apache usa HTTP en lugar de HTTPS, cambia `https://` por `http://` y deja
`verificar_tls` en `0`.

La extensión cURL debe estar habilitada en el PHP que ejecuta Apache de PCAM.
