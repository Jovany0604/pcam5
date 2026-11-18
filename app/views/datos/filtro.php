<?php
/* =============================================================================
   app/views/datos/filtro.php  -  Buscador de SUBESTACIÓN y pestañas Status / Analógicas
   =============================================================================
   Se dibuja DENTRO de la barra de opciones, solo cuando se está en Datos.
   Variables: $seccion, $subestaciones (lista), $filtro (texto inicial)
   El filtrado lo hace app.js (filtrarSubestacion) mientras se escribe.
   ============================================================================= */
?>
<!-- Buscador: filtra las filas de la tabla por subestación -->
<div class="buscador">
    <svg class="buscador-lupa" viewBox="0 0 24 24" aria-hidden="true">
        <circle cx="10" cy="10" r="6.5" fill="none" stroke="currentColor" stroke-width="2.4"/>
        <path d="M15 15l6 6" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/>
    </svg>
    <input type="search" id="filtro-subestacion" list="lista-subestaciones"
           placeholder="Subestación" value="<?= e($filtro) ?>" autocomplete="off"
           oninput="filtrarSubestacion()" aria-label="Filtrar por subestación">
    <button type="button" class="buscador-limpiar" onclick="limpiarFiltro()" title="Quitar filtro">&times;</button>
    <datalist id="lista-subestaciones">
        <?php foreach ($subestaciones as $s): ?>
            <option value="<?= e($s['nombre']) ?>"><?= e($s['codigo']) ?></option>
        <?php endforeach; ?>
    </datalist>
</div>

<!-- Pestañas: Status | Analógicas (conservan el filtro al cambiar) -->
<div class="tabs-datos">
    <a class="tab-dato<?= $seccion === 'datos_status' ? ' activa' : '' ?>"
       href="<?= ruta('datos', 'status') ?>">Status</a>
    <a class="tab-dato<?= $seccion === 'datos_analogicas' ? ' activa' : '' ?>"
       href="<?= ruta('datos', 'analogicas') ?>">Analógicas</a>
</div>
