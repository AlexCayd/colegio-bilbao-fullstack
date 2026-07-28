<?php
// Fondo Three.js reutilizable para el hero de las páginas de Comunidad.
// Requiere que el three.min.js se cargue en $extra_head del controlador.
// Parámetros opcionales (definidos antes del include):
//   $bg_colores → array de hex (paleta de partículas/atmósfera)
//   $bg_scene   → 'bosque' (escuela en el bosque) | 'orbes' (nube clásica de orbes)
//   $bg_shapes  → true/false (solo aplica a la escena 'orbes')
$bg_scene   = $bg_scene   ?? 'bosque';
$bg_colores = $bg_colores ?? ['#4D8ABB', '#7DC6E5', '#46bdc6', '#374C69', '#F1C400'];
$bg_shapes  = $bg_shapes  ?? true;
?>
<canvas class="comunidad-bg" data-comunidad-bg
        data-scene="<?= htmlspecialchars($bg_scene, ENT_QUOTES) ?>"
        data-colors='<?= htmlspecialchars(json_encode(array_values($bg_colores)), ENT_QUOTES) ?>'
        data-shapes="<?= $bg_shapes ? '1' : '0' ?>"
        aria-hidden="true"></canvas>
