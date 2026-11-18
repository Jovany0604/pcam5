<?php
/* =============================================================================
   app/views/header.php  -  Encabezado institucional (login e inicio)
   [logo izquierdo]        [3 títulos]        [logo derecho]
   Textos, colores e imágenes salen de config/config.xml.
   ============================================================================= */
$config = Config::load();
$p = $config->pagina;
?>
<header id="header-pcam">
    <div class="header-logo izquierdo">
        <img src="<?= URL_BASE . e($config->imagenes->logo_izquierdo) ?>" alt="Gobierno de México y CFE">
    </div>

    <div class="header-titulos">
        <h2 style="color: <?= e($p->titulo1['color']) ?>"><?= e($p->titulo1) ?></h2>
        <h3 style="color: <?= e($p->titulo2['color']) ?>"><?= e($p->titulo2) ?></h3>
        <h1 style="color: <?= e($p->titulo3['color']) ?>"><?= e($p->titulo3) ?></h1>
    </div>

    <div class="header-logo derecho">
        <img src="<?= URL_BASE . e($config->imagenes->logo_derecho) ?>" alt="ZOTGM">
    </div>
</header>
