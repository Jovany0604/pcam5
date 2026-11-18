<?php
/* =============================================================================
   app/views/datos/analogicas.php  -  Datos > ANALÓGICAS (se incluye en inicio.php)
   =============================================================================
   Variables: $filas (puntos analógicos del DatosModel)
   Columnas: No. | Key | Type | Station | Name | Units | Value | TLQ
   Cada fila lleva data-estacion con el código y nombre de la subestación,
   que es lo que lee el filtro (app.js).
   ============================================================================= */
?>
<div class="datos-encabezado">
    <h2>Analógicas</h2>
    <span class="datos-contador" id="datos-contador"></span>
</div>

<div class="tabla-contenedor tabla-datos">
    <table class="tabla">
        <thead>
            <tr>
                <th>No.</th><th>Key</th><th>Type</th><th>Station</th><th>Name</th>
                <th>Units</th><th class="num">Value</th><th class="centro">TLQ</th>
            </tr>
        </thead>
        <tbody id="tabla-datos">
        <?php foreach ($filas as $f): ?>
            <tr data-estacion="<?= e($f['codigo'] . ' ' . $f['estacion']) ?>">
                <td><?= (int) $f['id'] ?></td>
                <td><?= e($f['clave']) ?></td>
                <td><?= e($f['tipo']) ?></td>
                <td><?= e($f['estacion']) ?> <span class="cod"><?= e($f['codigo']) ?></span></td>
                <td class="nombre-punto"><?= e($f['nombre']) ?></td>
                <td class="unidad"><?= e($f['unidad']) ?></td>
                <td class="num"><?= number_format((float) $f['valor'], 2, '.', '') ?></td>
                <td class="centro"><?= indicador_tlq($f['etiquetado'], $f['limite'], $f['calidad']) ?></td>
            </tr>
        <?php endforeach; ?>
            <tr id="sin-resultados" class="sin-resultados" hidden>
                <td colspan="8">No hay datos para esa subestación.</td>
            </tr>
        </tbody>
    </table>
</div>
