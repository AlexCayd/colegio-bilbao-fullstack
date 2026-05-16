<?php
// ELIMINAR después de diagnosticar
error_reporting(E_ALL);
ini_set('display_errors', 1);

$root = __DIR__;

echo "<h2>Build assets</h2>";
$checks = [
    'public/build/css/app.css',
    'public/build/js/bundle.min.js',
    'public/build/assets/img/global/favicon.png',
    'public/build/assets/img/niveles-academicos/preescolar/nino-burbujas.jpg',
    'public/build/assets/img/niveles-academicos/primaria/alumnos-abrazados.jpg',
];
foreach ($checks as $path) {
    $full = $root . '/' . $path;
    $exists = file_exists($full);
    $size = $exists ? round(filesize($full) / 1024, 1) . ' KB' : '';
    echo ($exists ? '✓' : '<b style="color:red">✗ FALTA</b>') . " $path $size<br>";
}

echo "<h2>Rewrite /build/ → /public/build/</h2>";
$testUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/build/css/app.css';
echo "La URL <code>$testUrl</code> debería servir el CSS.<br>";
echo "Si el CSS de la página carga = rewrite OK ✓<br>";

echo "<h2>Rutas en views</h2>";
echo "⚠️ Algunas vistas usan <code>src=\"build/...\"</code> (sin slash inicial).<br>";
echo "Esto falla en rutas internas como /conocenos/. Deben ser <code>src=\"/build/...\"</code><br>";
$count = (int) shell_exec('grep -rl "src=\"build/" ' . escapeshellarg($root . '/views') . ' | wc -l 2>/dev/null') ?: '(no disponible)';
echo "Vistas afectadas: $count archivos<br>";
