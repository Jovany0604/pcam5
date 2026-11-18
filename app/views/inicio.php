<?php

/**
 * Variables proporcionadas por los controladores.
 *
 * @var array{nombre:string, rol:int, acceso:string} $usuario
 * @var SimpleXMLElement $config
 * @var string $seccion
 * @var array{tipo:string, texto:string}|null $aviso
 */

$roles   = [1 => 'Administrador', 2 => 'Usuario', 3 => 'Invitado'];
$icono   = URL_BASE . 'img/iconos/';
$esAdmin = ($usuario['rol'] == 1);
$enDatos = (strpos($seccion, 'datos_') === 0); // Datos > Status / Analógicas

// La configuración se abre desde la barra en una ventana flotante.
// Se crea el token aquí también porque la ventana puede abrirse desde Datos.
if ($esAdmin && empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}
$csrf = $csrf ?? ($_SESSION['csrf'] ?? '');
$aviso = $aviso ?? null;

// Logo del sistema (config.xml > imagenes > logo_sistema; si no existe usa este)
$logoSistema = (string) $config->imagenes->logo_sistema;
if ($logoSistema === '') {
    $logoSistema = 'img/logo_sistema.png';
}

/* =============================================================================
   app/views/inicio.php  -  VISTA principal (cascarón de la segunda página)
   =============================================================================
   La usan InicioController, DatosController y AdminController. Variables:
     $usuario   datos de la sesión (nombre, rol, acceso)
     $config    config.xml
     $seccion   qué se dibuja en el centro:
                  'datos_analogicas' -> app/views/datos/analogicas.php
                  'datos_status'     -> app/views/datos/status.php
                  'configuracion'    -> ventana flotante de configuración (solo admin)
   Usuarios NO es una sección: es una VENTANA FLOTANTE (#modal-usuarios, solo admin)
   que se abre con el botón Usuarios sin salir de la página en la que estás.

   Distribución de la pantalla (cuadrícula, ver estilos.css > .app):

        +----------+-------------------------------------------+
        |  LOGO    |  header (logos y títulos)                 |
        +----------+-------------------------------------------+
        |  Datos > |  barra: [Subestación] [Status|Analógicas] |
        |          |         ...   [Usuarios][Descarga][Configuración][Salir]
        |  Datos > +-------------------------------------------+
        |          |  contenido                                |
        +----------+-------------------------------------------+
        |  pie                                                 |
        +------------------------------------------------------+

   Botones laterales (todos):        Datos
   Botones de la barra:
     Todos:            Descarga, Salir
     Solo admin (1):   Usuarios, Configuración
   ============================================================================= */

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($config->pagina->titulo_pestana) ?></title>
    <link rel="stylesheet" href="<?= URL_BASE ?>css/estilos.css">
</head>
<body class="pagina-inicio" style="background-image: url('<?= URL_BASE . e($config->imagenes->imagen_fondo) ?>')">

<div class="app">

    <!-- ===================== LOGO DEL SISTEMA (arriba a la izquierda) ===================== -->
    <div class="app-logo">
        <a href="<?= ruta('datos', 'analogicas') ?>" title="Datos">
            <img class="logo-sistema" src="<?= URL_BASE . e($logoSistema) ?>" alt="Logo del sistema">
        </a>
    </div>

    <!-- ===================== ENCABEZADO ===================== -->
    <div class="app-header">
        <?php require VIEW_PATH . '/header.php'; ?>
    </div>

    <!-- ===================== NAVBAR IZQUIERDO ===================== -->
    <nav class="lateral">
        <!-- Datos: al entrar aparecen arriba el buscador de subestación y las pestañas Status / Analógicas -->
        <a class="btn-lateral<?= $enDatos ? ' activo' : '' ?>" href="<?= ruta('datos', 'analogicas') ?>">
            <img src="<?= $icono ?>datos.svg" alt=""><span>Datos</span><b class="flecha">&rsaquo;</b>
        </a>

        <!-- AQUÍ AGREGAS MÁS BOTONES LATERALES -->
    </nav>

    <!-- ===================== BARRA DE OPCIONES ===================== -->
    <nav class="barra">

        <?php if ($enDatos): ?>
            <?php require VIEW_PATH . '/datos/filtro.php'; ?>
        <?php endif; ?>

        <div class="espacio"></div>

        <!-- AQUÍ AGREGAS MÁS BOTONES -->

        <?php if ($esAdmin): ?>
            <!-- Solo el administrador ve este botón: abre la ventana flotante de usuarios (app.js: abrirUsuarios) -->
            <button class="btn-barra" type="button" onclick="abrirUsuarios()">
                <img src="<?= $icono ?>usuarios.svg" alt=""><span>Usuarios</span>
            </button>
        <?php endif; ?>

        <!-- Descarga: exporta a CSV las filas visibles de la tabla actual -->
        <button class="btn-barra" type="button" onclick="descargarDatos()">
            <img src="<?= $icono ?>descarga.svg" alt=""><span>Descarga</span>
        </button>

        <?php if ($esAdmin): ?>
            <!-- Solo el administrador ve este botón: abre configuración en ventana flotante -->
            <button class="btn-barra<?= $seccion === 'configuracion' ? ' activo' : '' ?>" type="button" onclick="abrirConfiguracion()">
                <img src="<?= $icono ?>administrar.svg" alt=""><span>Configuración</span>
            </button>
        <?php endif; ?>

        <a class="btn-barra" href="<?= ruta('login', 'salir') ?>">
            <img src="<?= $icono ?>salir.svg" alt=""><span>Salir</span>
        </a>
    </nav>

    <!-- ===================== CONTENIDO ===================== -->
    <main class="cuerpo">
        <section class="panel contenido<?= $enDatos ? ' limpio' : '' ?>">

            <?php if ($enDatos): ?>
                <?php if (!empty($datosError)): ?>
                    <div class="aviso error datos-error"><?= e($datosError) ?></div>
                <?php endif; ?>
                <!-- Datos: tabla de Status o de Analógicas (con filtro por subestación) -->
                <?php require VIEW_PATH . '/datos/' . ($seccion === 'datos_status' ? 'status' : 'analogicas') . '.php'; ?>
            <?php endif; ?>

        </section>
    </main>

    <!-- ===================== PIE ===================== -->
    <footer class="pie">
        <span><b><?= e($usuario['nombre']) ?></b> &middot; <?= e($roles[$usuario['rol']] ?? '') ?></span>
        <span><?= e($usuario['acceso']) ?></span>
    </footer>

</div>

<?php if ($esAdmin): ?>
<!-- ===================== VENTANA FLOTANTE DE CONFIGURACIÓN ===================== -->
<div id="modal-configuracion" class="modal<?= $seccion === 'configuracion' ? ' abierto' : '' ?>" onclick="if (event.target === this) cerrarConfiguracion()">
    <div class="ventana ventana-configuracion" role="dialog" aria-modal="true" aria-labelledby="titulo-configuracion">
        <div class="ventana-barra">
            <h3 id="titulo-configuracion">Configuración</h3>
            <button type="button" class="ventana-cerrar" onclick="cerrarConfiguracion()" title="Cerrar" aria-label="Cerrar">&times;</button>
        </div>
        <div class="ventana-cuerpo">
            <?php if (!empty($aviso)): ?>
                <div class="aviso <?= e($aviso['tipo']) ?>"><?= e($aviso['texto']) ?></div>
            <?php endif; ?>
            <?php require VIEW_PATH . '/admin/configuracion.php'; ?>
        </div>
    </div>
</div>

<!-- ===================== VENTANA FLOTANTE DE USUARIOS (solo admin) ===================== -->
<div id="modal-usuarios" class="modal" onclick="if (event.target === this) cerrarUsuarios()">
    <div class="ventana" role="dialog" aria-modal="true" aria-labelledby="titulo-usuarios">
        <div class="ventana-barra">
            <h3 id="titulo-usuarios">Usuarios</h3>
            <button type="button" class="ventana-cerrar" onclick="cerrarUsuarios()" title="Cerrar" aria-label="Cerrar">&times;</button>
        </div>
        <p id="usuarios-estado" class="ventana-estado"></p>
        <!-- app.js mete aquí el formulario y la lista (admin/usuarios.php) -->
        <div id="usuarios-contenido" class="ventana-cuerpo"></div>
    </div>
</div>
<?php endif; ?>

<!-- URLs para app.js (siempre pasan por index.php) -->
<script>
<?php if ($esAdmin): ?>
const URL_USUARIOS        = <?= json_encode(ruta('admin', 'usuarios')) ?>;
const URL_GUARDAR_USUARIO = <?= json_encode(ruta('admin', 'guardarUsuario')) ?>;
const URL_BORRAR_USUARIO  = <?= json_encode(ruta('admin', 'borrarUsuario')) ?>;
<?php endif; ?>
</script>
<script src="<?= URL_BASE ?>js/app.js"></script>
</body>
</html>
