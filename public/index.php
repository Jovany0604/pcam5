<?php
/* =============================================================================
   public/index.php  -  PUNTO DE ENTRADA ÚNICO DEL PROYECTO (front controller)
   =============================================================================
   REGLA DEL PROYECTO: el navegador NUNCA sale de este archivo.
   Todas las páginas y acciones se piden así:

       index.php?controller=NOMBRE&action=ACCION

   Este archivo:
     1. Inicia la sesión.
     2. Define las rutas ABSOLUTAS del disco (ROOT_PATH, APP_PATH...) y la
        URL ABSOLUTA de la web (URL_BASE) para css, js e imágenes.
     3. Carga la configuración (config/config.xml).
     4. Decide qué controlador y qué acción ejecutar.
     5. Si nada respondió, muestra el login.
   ============================================================================= */

session_start();

// -----------------------------------------------------------------------------
// 1. RUTAS ABSOLUTAS EN EL DISCO (para require, base de datos, archivos)
// -----------------------------------------------------------------------------
if (!defined('ROOT_PATH')) { // si la ruta raíz no está definida, define todas
    define('ROOT_PATH', dirname(__DIR__));            // .../pcam
    define('CONFIG_PATH', ROOT_PATH . '/config');      // .../pcam/config
    define('APP_PATH', ROOT_PATH . '/app');            // .../pcam/app
    define('VIEW_PATH', APP_PATH . '/views');          // .../pcam/app/views
    define('MODEL_PATH', APP_PATH . '/models');        // .../pcam/app/models
    define('CONTROLLER_PATH', APP_PATH . '/controllers');
    define('LIBS_PATH', APP_PATH . '/libs');           // PHPMailer
    define('DATABASE_PATH', ROOT_PATH . '/database');  // pcam.db
}

// -----------------------------------------------------------------------------
// 2. URL ABSOLUTA DE LA WEB (para <link>, <script>, <img> y enlaces)
//    Ejemplo: http://10.26.1.20/pcam/public/
//    Se calcula sola, así funciona en cualquier servidor o carpeta.
// -----------------------------------------------------------------------------
if (!defined('URL_BASE')) {
    $protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $directorio = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
    define('URL_BASE', $protocolo . '://' . $_SERVER['HTTP_HOST'] . $directorio . '/');
}

// En desarrollo se muestran los errores. En producción descomenta estas líneas.
// ini_set('display_errors', 0);
// error_reporting(0);

// -----------------------------------------------------------------------------
// 3. CONFIGURACIÓN (config.xml) y funciones de ayuda para las vistas
// -----------------------------------------------------------------------------
require_once CONFIG_PATH . '/Config.php';
$config = Config::load();
$contenedor_privado = (string) $config->acceso->contenedor_privado; // '1' = sin invitados

/** Escapa texto antes de imprimirlo en HTML (evita que se inyecte código). */
function e($texto)
{
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}

/** Arma un enlace que SIEMPRE pasa por index.php. Ej: ruta('admin', 'usuarios', ['id' => 2]) */
function ruta($controller, $action = 'index', $parametros = [])
{
    $parametros = array_merge(['controller' => $controller, 'action' => $action], $parametros);
    return URL_BASE . 'index.php?' . http_build_query($parametros);
}

/**
 * Dibuja el indicador TLQ de una fila de Datos.
 *   T = punto etiquetado   L = fuera de límite   Q = mala calidad
 * Si no hay ninguna alerta muestra una palomita verde.
 */
function indicador_tlq($t, $l, $q)
{
    if (!$t && !$l && $q) {
        return '<span class="tlq ok" title="Sin etiqueta, dentro de límite, calidad buena">&#10003;</span>';
    }
    $html = '';
    if ($t)  { $html .= '<span class="tlq t" title="Punto etiquetado">T</span>'; }
    if ($l)  { $html .= '<span class="tlq l" title="Fuera de límite">L</span>'; }
    if (!$q) { $html .= '<span class="tlq q" title="Mala calidad">Q</span>'; }
    return $html;
}

/** Redirige a otra acción (siempre dentro de index.php) y termina. */
function redirigir($controller, $action = 'index', $parametros = [])
{
    header('Location: ' . ruta($controller, $action, $parametros));
    exit;
}

// -----------------------------------------------------------------------------
// 4. ENRUTADOR: qué controlador y qué acción se pidió
// -----------------------------------------------------------------------------
$controller = $_GET['controller'] ?? 'login';
$action     = $_GET['action'] ?? 'loginIP';
$error      = null; // mensaje de error que se mostrará en el login

// ---------- LOGIN ----------
if ($controller == 'login') {
    require_once CONTROLLER_PATH . '/LoginController.php';
    $obj = new LoginController();

    if ($action == 'loginIP') {
        // Entra solo si la IP del equipo está registrada. Si no, sigue y muestra el login.
        $obj->validarIp();

    } else if ($action == 'loginUser') {
        // Existe y tiene valor
        if (!empty($_POST['username']) && !empty($_POST['password'])) {
            $error = $obj->validarUsuario($_POST['username'], $_POST['password']); // si entra, redirige
        } else {
            // las credenciales enviadas están vacías
            $error = 'Escribe tu usuario y contraseña.';
        }

    } else if ($action == 'nologin') {
        // Entrar como invitado (solo si el contenedor NO es privado)
        if ($contenedor_privado == '0') {
            $obj->noValidar();
        }
        $error = 'El acceso como invitado está desactivado.';

    } else if ($action == 'salir') {
        $obj->salir();
    }
}

// ---------- PÁGINA PRINCIPAL ----------
else if ($controller == 'inicio') {
    require_once CONTROLLER_PATH . '/InicioController.php';
    $obj = new InicioController();

    if ($action == 'diagrama') {
        $obj->diagrama();
    } else {
        $obj->index(); // acción por defecto: bienvenida
    }
    exit;
}

// ---------- DATOS: Analógicas y Status (con filtro por subestación) ----------
else if ($controller == 'datos') {
    require_once CONTROLLER_PATH . '/DatosController.php';
    $obj = new DatosController();

    if ($action == 'status') {
        $obj->status();
    } else if ($action == 'probar') {
        $obj->probar();
    } else {
        $obj->analogicas(); // acción por defecto
    }
    exit;
}

// ---------- ADMINISTRACIÓN: botón Usuarios y botón Configuración (solo rol 1) ----------
else if ($controller == 'admin') {
    require_once CONTROLLER_PATH . '/AdminController.php';
    $obj = new AdminController(); // si no es administrador, lo regresa a inicio

    if ($action == 'guardarUsuario') {
        $obj->guardarUsuario();
    } else if ($action == 'borrarUsuario') {
        $obj->borrarUsuario();
    } else if ($action == 'configuracion') {
        $obj->configuracion();
    } else if ($action == 'guardarConfiguracion') {
        $obj->guardarConfiguracion();
    } else {
        $obj->usuarios(); // acción por defecto: lista de usuarios
    }
    exit;
}

// ---------- REPORTES POR CORREO (SMTP Gmail) ----------
else if ($controller == 'reporte') {
    require_once CONTROLLER_PATH . '/ReporteController.php';
    $obj = new ReporteController();
    $obj->enviar();
    exit;
}

// AQUÍ AGREGAS NUEVOS CONTROLADORES, por ejemplo:
// else if ($controller == 'reportes') { require_once CONTROLLER_PATH . '/ReportesController.php'; ... }

// -----------------------------------------------------------------------------
// 5. Si ningún controlador respondió, se muestra el login
// -----------------------------------------------------------------------------
require VIEW_PATH . '/login.php';
exit;
