<?php
/**
 * Router file del servidor embebido: `php -S localhost:3000 dev-server.php`
 *
 * Sin él, `php -S` devuelve 404 en todo `/build/*`: esos assets no existen físicamente
 * en la raíz (los sirve un shim dentro de index.php desde public/build/), y el servidor
 * embebido solo cae al front controller cuando la URI *no* parece un archivo —
 * `/build/css/app.css` tiene extensión, así que nunca llegaba. Resultado: el sitio se
 * pintaba sin CSS ni JS.
 *
 * Replica la condición IsFile de web.config, veta las carpetas sensibles y manda todo lo
 * demás a index.php. En producción (IIS) no se usa: el reparto lo hace web.config.
 *
 * ⚠️ No renombrar a router.php: en Windows el FS es case-insensitive y chocaría con
 * Router.php.
 */

$uri  = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
$ruta = realpath(__DIR__ . urldecode($uri));

// Carpetas que nunca se sirven como archivo estático, aunque exista la ruta.
$vetadas = ['includes', 'vendor', 'database', 'models', 'controllers', 'views', 'classes', 'src', 'node_modules'];
$primer  = strtok(ltrim($uri, '/'), '/');

if (in_array($primer, $vetadas, true) || str_starts_with(basename($uri), '.')) {
    http_response_code(403);
    exit('403 Forbidden');
}

// Archivo real dentro del proyecto → que lo sirva el servidor embebido tal cual.
if ($ruta !== false && is_file($ruta) && str_starts_with($ruta, realpath(__DIR__))) {
    return false;
}

// Todo lo demás (incluido /build/*) al front controller.
require __DIR__ . '/index.php';
