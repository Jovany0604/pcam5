<?php
/* =============================================================================
   app/views/datos/status.php  -  Datos > STATUS (se incluye en inicio.php)
   =============================================================================
   Variables: $filas (puntos de estado del DatosModel)
   Columnas: No. | Key | Type | Station | Name | State | TLQ
   ============================================================================= */

// Color de la etiqueta de estado según su texto
$clasesEstado = [
    'CERRADO'      => 'cerrado',
    'CERRADA'      => 'cerrado',
    'NORMAL'       => 'normal',
    'ABIERTO'      => 'abierto',
    'ABIERTA'      => 'abierto',
    'DESBLOQUEADO' => 'desbloqueado',
];
?>
<div class="datos-encabezado">
    <h2>Status</h2>
    <span class="datos-contador" id="datos-contador"></span>
</div>

<div class="tabla-contenedor tabla-datos">
    <table class="tabla">
        <thead>
            <tr>
                <th>No.</th><th>Key</th><th>Type</th><th>Station</th><th>Name</th>
                <th>State</th><th class="centro">TLQ</th>
            </tr>
        </thead>
        <tbody id="tabla-datos">
        <?php foreach ($filas as $f): ?>
            <?php $clase = $clasesEstado[strtoupper($f['estado'])] ?? ''; ?>
            <tr data-estacion="<?= e($f['codigo'] . ' ' . $f['estacion']) ?>">
                <td><?= (int) $f['id'] ?></td>
                <td><?= e($f['clave']) ?></td>
                <td><?= e($f['tipo']) ?></td>
                <td><?= e($f['estacion']) ?> <span class="cod"><?= e($f['codigo']) ?></span></td>
                <td class="nombre-punto"><?= e($f['nombre']) ?></td>
                <td><span class="estado <?= e($clase) ?>"><?= e($f['estado']) ?></span></td>
                <td class="centro"><?= indicador_tlq($f['etiquetado'], $f['limite'], $f['calidad']) ?></td>
            </tr>
        <?php endforeach; ?>
            <tr id="sin-resultados" class="sin-resultados" hidden>
                <td colspan="7">No hay datos para esa subestación.</td>
            </tr>
        </tbody>
    </table>
</div>
