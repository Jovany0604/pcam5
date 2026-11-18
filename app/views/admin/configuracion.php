<?php
/* =============================================================================
   app/views/admin/configuracion.php  -  Pestaña CONFIGURACIÓN (en inicio.php)
   =============================================================================
   Muestra únicamente el encabezado y envía sus cambios a
   controller=admin&action=guardarConfiguracion (POST).
   Variables: $config, $csrf
   ============================================================================= */
$p = $config->pagina;
?>
<form class="formulario ancho" method="POST" action="<?= ruta('admin', 'guardarConfiguracion') ?>">
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">

    <!-- ---------- Encabezado ---------- -->
    <h3>Encabezado</h3>
    <label>Título de la pestaña del navegador
        <input type="text" name="titulo_pestana" value="<?= e($p->titulo_pestana) ?>">
    </label>
    <?php foreach (['titulo1' => 'Título 1', 'titulo2' => 'Título 2', 'titulo3' => 'Título 3'] as $campo => $etiqueta): ?>
        <div class="fila-color">
            <label><?= $etiqueta ?>
                <input type="text" name="<?= $campo ?>" value="<?= e($p->$campo) ?>">
            </label>
            <label>Color
                <input type="color" name="<?= $campo ?>_color" value="<?= e($p->$campo['color']) ?>">
            </label>
        </div>
    <?php endforeach; ?>

    <div class="modal-botones">
        <button type="submit" class="boton btn-entrar">Guardar configuración</button>
    </div>
</form>
