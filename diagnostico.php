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
$count = 0;
$iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/views'));
foreach ($iter as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        if (strpos(file_get_contents($file->getPathname()), 'src="build/') !== false ||
            strpos(file_get_contents($file->getPathname()), "src='build/") !== false) {
            $count++;
        }
    }
}
if ($count === 0) {
    echo "✓ Todas las vistas usan rutas absolutas <code>/build/...</code><br>";
} else {
    echo "<b style='color:red'>✗ $count vistas usan src=\"build/\" sin slash — deben ser src=\"/build/\"</b><br>";
}

echo "<h2>Entorno PHP</h2>";
echo "PHP: " . phpversion() . "<br>";
echo "mysqli: " . (extension_loaded('mysqli') ? '✓ cargada' : '<b style="color:red">✗ NO disponible — Fatal error en BD</b>') . "<br>";
echo "mbstring: " . (extension_loaded('mbstring') ? '✓' : '⚠️ ausente') . "<br>";
echo "vendor/autoload.php: " . (file_exists($root . '/vendor/autoload.php') ? '✓ existe' : '<b style="color:red">✗ FALTA — ejecutar composer install</b>') . "<br>";
echo "includes/.env: " . (file_exists($root . '/includes/.env') ? '✓ existe' : '<b style="color:red">✗ FALTA — crear con credenciales de producción</b>') . "<br>";

echo "<h2>Conexión a BD</h2>";
if (extension_loaded('mysqli')) {
    $envFile = $root . '/includes/.env';
    $env = [];
    if (file_exists($envFile)) {
        foreach (file($envFile) as $line) {
            $line = trim($line);
            if ($line && strpos($line, '=') !== false && $line[0] !== '#') {
                [$k, $v] = explode('=', $line, 2);
                $env[trim($k)] = trim($v);
            }
        }
    }
    $host = $env['DB_HOST'] ?? 'localhost';
    $user = $env['DB_USER'] ?? '';
    $pass = $env['DB_PASS'] ?? '';
    $name = $env['DB_NAME'] ?? '';
    $port = (int)($env['DB_PORT'] ?? 3306);
    echo "Host: <code>$host</code>, DB: <code>$name</code>, User: <code>$user</code><br>";
    $db = @mysqli_connect($host, $user, $pass, $name, $port);
    if ($db) {
        echo "✓ Conexión exitosa a la base de datos<br>";
        mysqli_close($db);
    } else {
        echo "<b style='color:red'>✗ Error BD: " . mysqli_connect_error() . " (errno " . mysqli_connect_errno() . ")</b><br>";
        echo "<b>→ Actualiza las credenciales en <code>includes/.env</code> con las del servidor de producción</b><br>";
    }
} else {
    echo "No se puede probar — mysqli no disponible<br>";
}
