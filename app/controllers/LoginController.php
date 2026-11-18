<?php
/* =============================================================================
   app/controllers/LoginController.php  -  CONTROLADOR del acceso
   =============================================================================
   Acciones (las llama public/index.php):
     validarIp()       -> controller=login&action=loginIP   (entrada por defecto)
     validarUsuario()  -> controller=login&action=loginUser (formulario)
     noValidar()       -> controller=login&action=nologin   (botón invitado)
     salir()           -> controller=login&action=salir     (cerrar sesión)

   Al entrar se guarda en $_SESSION['usuario']:
     ['id', 'nombre', 'username', 'rol', 'acceso']
   ============================================================================= */

require_once MODEL_PATH . '/UsuarioModel.php';

class LoginController
{
    /** Si la IP del equipo está registrada, entra sin pedir login. */
    public function validarIp()
    {
        // Ya tiene sesión: directo a inicio
        if (!empty($_SESSION['usuario'])) {
            redirigir('inicio');
        }

        // Si acaba de pulsar "Salir" no lo volvemos a meter por IP
        if (!empty($_SESSION['sin_ip'])) {
            return; // index.php mostrará el login
        }

        $usuario = UsuarioModel::buscarPorIp($this->ipCliente());
        if ($usuario) {
            $this->iniciarSesion($usuario, 'Acceso automático por IP');
        }
        // Si no se encontró la IP, regresa y index.php muestra el login
    }

    /** Valida usuario y contraseña. Si son correctos entra; si no, devuelve el error. */
    public function validarUsuario($username, $password)
    {
        $usuario = UsuarioModel::buscarPorUsuario(trim($username));

        // password_verify compara contra la contraseña cifrada de la base de datos
        if ($usuario && password_verify($password, $usuario['password'])) {
            $this->iniciarSesion($usuario, 'Acceso con usuario');
        }

        return 'Usuario o contraseña incorrectos.';
    }

    /** Entra como invitado (rol 3): puede consultar y descargar Datos. */
    public function noValidar()
    {
        $invitado = [
            'id'       => 0,
            'nombre'   => 'Invitado',
            'username' => 'invitado',
            'rol'      => 3,
        ];
        $this->iniciarSesion($invitado, 'Invitado');
    }

    /** Cierra la sesión y regresa al login. */
    public function salir()
    {
        $_SESSION = [];
        session_regenerate_id(true);
        $_SESSION['sin_ip'] = true; // evita volver a entrar por IP al instante
        redirigir('login', 'loginIP');
    }

    // ------------------------------------------------------------------ privadas

    /** Guarda los datos del usuario en la sesión y lo manda a inicio. */
    private function iniciarSesion($usuario, $tipoAcceso)
    {
        session_regenerate_id(true); // seguridad: nuevo id de sesión al entrar
        $_SESSION['usuario'] = [
            'id'       => $usuario['id'],
            'nombre'   => $usuario['nombre'],
            'username' => $usuario['username'],
            'rol'      => (int) $usuario['rol'],
            'acceso'   => $tipoAcceso,
        ];
        unset($_SESSION['sin_ip']);
        redirigir('inicio');
    }

    /** IP real del equipo (REMOTE_ADDR no se puede falsificar como X-Forwarded-For). */
    private function ipCliente()
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        return ($ip === '::1') ? '127.0.0.1' : $ip; // ::1 es localhost en IPv6
    }
}
