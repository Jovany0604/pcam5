<?php
/* =============================================================================
   app/controllers/AdminController.php  -  Usuarios y Configuración (SOLO ADMIN)
   =============================================================================
   Acciones (controller=admin&action=...):
     usuarios              HTML de la VENTANA FLOTANTE de usuarios (lista + formulario;
                           con &id=N el formulario es para editar). Solo la pide app.js
     guardarUsuario        POST: crea o actualiza un usuario (responde JSON)
     borrarUsuario         POST: borra un usuario (responde JSON)
     configuracion         Ventana flotante con el formulario de encabezado
     guardarConfiguracion  POST: guarda config.xml

   Los usuarios NO tienen página propia: se abren en una ventana flotante
   (app.js: abrirUsuarios, cargarUsuarios, guardarUsuario, borrarUsuario).

   Seguridad:
     - El constructor revisa que el rol sea 1 (Administrador). Si no, a inicio.
     - Los formularios POST llevan un token (csrf) para que otra página no
       pueda enviar cambios a nombre del administrador.
   ============================================================================= */

require_once MODEL_PATH . '/UsuarioModel.php';

class AdminController
{
    public function __construct()
    {
        if (empty($_SESSION['usuario'])) {
            redirigir('login', 'loginIP');
        }
        if ($_SESSION['usuario']['rol'] != 1) {
            redirigir('inicio'); // usuarios e invitados no pueden entrar aquí
        }

        // Token para los formularios (se crea una vez por sesión)
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(16));
        }
    }

    // ================================================================ USUARIOS

    /** Devuelve el contenido de la ventana flotante: formulario + lista de usuarios. */
    public function usuarios()
    {
        // Solo se pide con fetch desde app.js; si alguien escribe la dirección, a inicio
        if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== 'fetch') {
            redirigir('inicio');
        }

        $id = (int) ($_GET['id'] ?? 0);

        // Si viene &id=N se edita ese usuario; si no, el formulario es para uno nuevo
        $editar = ($id > 0) ? UsuarioModel::buscarPorId($id) : false;
        if (!$editar) {
            $editar = ['id' => 0, 'nombre' => '', 'username' => '', 'email' => '', 'ip' => '', 'rol' => 2];
        }

        // Variables que usa app/views/admin/usuarios.php
        $usuarios = UsuarioModel::listar();
        $usuario  = $_SESSION['usuario'];
        $csrf     = $_SESSION['csrf'];
        $roles    = [1 => 'Administrador', 2 => 'Usuario', 3 => 'Invitado'];

        require VIEW_PATH . '/admin/usuarios.php';
        exit;
    }

    /** Crea o actualiza un usuario con los datos del formulario. Responde JSON {ok, mensaje}. */
    public function guardarUsuario()
    {
        $this->validarPost(true);

        $d = [
            'id'       => (int) ($_POST['id'] ?? 0),
            'nombre'   => trim($_POST['nombre'] ?? ''),
            'username' => trim($_POST['username'] ?? ''),
            'password' => (string) ($_POST['password'] ?? ''),
            'email'    => trim($_POST['email'] ?? ''),
            'ip'       => trim($_POST['ip'] ?? ''),
            'rol'      => ((int) ($_POST['rol'] ?? 2) === 1) ? 1 : 2, // solo 1 o 2
        ];

        // Validaciones
        if ($d['nombre'] === '' || $d['username'] === '') {
            $this->json(false, 'Nombre y usuario son obligatorios.');
        }
        if ($d['id'] == 0 && strlen($d['password']) < 6) {
            $this->json(false, 'La contraseña debe tener al menos 6 caracteres.');
        }
        if ($d['id'] > 0 && $d['password'] !== '' && strlen($d['password']) < 6) {
            $this->json(false, 'La contraseña debe tener al menos 6 caracteres.');
        }
        if ($d['ip'] !== '' && !filter_var($d['ip'], FILTER_VALIDATE_IP)) {
            $this->json(false, 'La IP no es válida (ejemplo: 10.26.5.120).');
        }
        if (UsuarioModel::existe('username', $d['username'], $d['id'])) {
            $this->json(false, 'Ese nombre de usuario ya existe.');
        }
        if (UsuarioModel::existe('ip', $d['ip'], $d['id'])) {
            $this->json(false, 'Esa IP ya está asignada a otro usuario.');
        }
        // El administrador no puede quitarse a sí mismo el rol de administrador
        if ($d['id'] == $_SESSION['usuario']['id'] && $d['rol'] != 1) {
            $this->json(false, 'No puedes quitarte tu propio rol de administrador.');
        }

        UsuarioModel::guardar($d);

        // Si se editó a sí mismo, actualiza el nombre que se ve en el pie
        if ($d['id'] == $_SESSION['usuario']['id']) {
            $_SESSION['usuario']['nombre'] = $d['nombre'];
        }

        $this->json(true, $d['id'] == 0 ? 'Usuario creado.' : 'Usuario actualizado.');
    }

    /** Borra un usuario (no se puede borrar a sí mismo). Responde JSON {ok, mensaje}. */
    public function borrarUsuario()
    {
        $this->validarPost(true);
        $id = (int) ($_POST['id'] ?? 0);

        if ($id == $_SESSION['usuario']['id']) {
            $this->json(false, 'No puedes borrar tu propio usuario.');
        }
        UsuarioModel::borrar($id);
        $this->json(true, 'Usuario borrado.');
    }

    // =========================================================== CONFIGURACIÓN

    /** Muestra el formulario con los valores actuales de config.xml. */
    public function configuracion()
    {
        $this->mostrar('configuracion', []);
    }

    /** Guarda en config.xml lo que se envió en el formulario. */
    public function guardarConfiguracion()
    {
        $this->validarPost(false);
        $config = Config::load();

        // ---- Títulos del encabezado y sus colores ----
        $config->pagina->titulo_pestana = trim($_POST['titulo_pestana'] ?? '');
        foreach (['titulo1', 'titulo2', 'titulo3'] as $t) {
            $config->pagina->$t = trim($_POST[$t] ?? '');
            $color = $_POST[$t . '_color'] ?? '#000000';
            $config->pagina->$t['color'] = preg_match('/^#[0-9a-fA-F]{6}$/', $color) ? $color : '#000000';
        }

        if (Config::guardar($config)) {
            $this->mensaje('ok', 'Configuración guardada.', 'configuracion');
        }
        $this->mensaje('error', 'No se pudo escribir config/config.xml (revisa permisos).', 'configuracion');
    }

    // ================================================================ PRIVADAS

    /** Dibuja inicio.php con la sección pedida (configuracion). */
    private function mostrar($seccion, $variables)
    {
        extract($variables);
        $usuario = $_SESSION['usuario'];
        $config  = Config::load();
        $csrf    = $_SESSION['csrf'];
        $aviso   = $_SESSION['aviso'] ?? null; // mensaje de la acción anterior
        unset($_SESSION['aviso']);

        require VIEW_PATH . '/inicio.php';
    }

    /** Solo acepta POST con el token correcto. $json = true responde JSON; false redirige a Configuración. */
    private function validarPost($json)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
            if ($json) {
                $this->json(false, 'La página expiró, recarga e intenta de nuevo.');
            }
            $this->mensaje('error', 'La página expiró, intenta de nuevo.', 'configuracion');
        }
    }

    /** Responde {ok, mensaje} en JSON (lo lee app.js) y termina. */
    private function json($ok, $mensaje)
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => $ok, 'mensaje' => $mensaje]);
        exit;
    }

    /** Guarda un aviso para la siguiente página y redirige (solo Configuración). */
    private function mensaje($tipo, $texto, $action, $parametros = [])
    {
        $_SESSION['aviso'] = ['tipo' => $tipo, 'texto' => $texto];
        redirigir('admin', $action, $parametros);
    }
}
