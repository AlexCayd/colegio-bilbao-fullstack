<?php
// Fondo Three.js reutilizable para el hero de las páginas de Comunidad.
// Requiere que el three.min.js se cargue en $extra_head del controlador.
// Colores institucionales opcionales vía $bg_colores (array de hex).
$bg_colores = $bg_colores ?? ['#4d8abb', '#2e4b8a', '#46bdc6', '#4267ac', '#6fb1d8'];
$bg_shapes  = $bg_shapes  ?? true;   // false = solo partículas (sin wireframes)
?>
<canvas class="comunidad-bg" data-comunidad-bg
        data-colors='<?= htmlspecialchars(json_encode(array_values($bg_colores)), ENT_QUOTES) ?>'
        data-shapes="<?= $bg_shapes ? '1' : '0' ?>"
        aria-hidden="true"></canvas>
