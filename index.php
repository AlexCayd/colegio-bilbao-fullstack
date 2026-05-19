<?php
// Servir assets estáticos de /build/ desde public/build/ (sin depender de IIS URL Rewrite)
(function() {
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    if (!preg_match('#^/build/(.+)$#', $uri, $m)) return;
    $file = __DIR__ . '/public/build/' . $m[1];
    if (!is_file($file)) { http_response_code(404); exit; }
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    $types = [
        'css'  => 'text/css', 'js' => 'application/javascript',
        'png'  => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
        'gif'  => 'image/gif', 'svg' => 'image/svg+xml', 'ico' => 'image/x-icon',
        'webp' => 'image/webp', 'avif' => 'image/avif',
        'mp4'  => 'video/mp4', 'webm' => 'video/webm',
        'woff' => 'font/woff', 'woff2' => 'font/woff2',
    ];
    header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));
    header('Cache-Control: public, max-age=86400');
    readfile($file);
    exit;
})();

require_once __DIR__ . '/includes/app.php';

use MVC\Router;
use Controllers\EstaticasController;
use Controllers\AuthController;
use Controllers\BlogController;

$router = new Router();

// Página principal
$router->get('/', [EstaticasController::class, 'index']);

// Conócenos
$router->get('/conocenos/quienes-somos', [EstaticasController::class, 'quienessomos']);
$router->get('/conocenos/equipo-educativo', [EstaticasController::class, 'equipoeducativo']);
$router->get('/conocenos/instalaciones', [EstaticasController::class, 'instalaciones']);
$router->get('/conocenos/certificaciones-y-reconocimientos', [EstaticasController::class, 'certificaciones']);

// Modelo Educativo
$router->get('/modelo-educativo/modelo-vida', [EstaticasController::class, 'modelovida']);
$router->get('/modelo-educativo/filosofia-y-metodologia', [EstaticasController::class, 'filosofiametodologia']);
$router->get('/modelo-educativo/aprendizaje-integral', [EstaticasController::class, 'aprendizajeintegral']);
$router->get('/modelo-educativo/idiomas', [EstaticasController::class, 'idiomas']);

// Niveles Académicos
$router->get('/niveles-academicos/preescolar', [EstaticasController::class, 'preescolar']);
$router->get('/niveles-academicos/primaria', [EstaticasController::class, 'primaria']);
$router->get('/niveles-academicos/secundaria', [EstaticasController::class, 'secundaria']);
$router->get('/niveles-academicos/preparatoria', [EstaticasController::class, 'preparatoria']);

// Vida Escolar
$router->get('/vida-escolar/afterschool-extracurriculares', [EstaticasController::class, 'afterschool']);
$router->get('/vida-escolar/cuidado-y-bienestar', [EstaticasController::class, 'cuidadobienestar']);
$router->get('/vida-escolar/eventos-y-tradiciones', [EstaticasController::class, 'eventostradicones']);
$router->get('/vida-escolar/futuro-universitario-becas', [EstaticasController::class, 'futurouniversitario']);
$router->get('/vida-escolar/programa-dual', [EstaticasController::class, 'programadual']);
$router->get('/vida-escolar/servicios-para-familias', [EstaticasController::class, 'serviciofamilias']);

// Admisiones
$router->get('/admisiones', [EstaticasController::class, 'inicio']);
$router->get('/admisiones/proceso', [EstaticasController::class, 'proceso']);
$router->get('/admisiones/preguntas-frecuentes', [EstaticasController::class, 'preguntasfrecuentes']);
$router->get('/admisiones/convenios', [EstaticasController::class, 'convenios']);
$router->get('/admisiones/convocatoria-becas', [EstaticasController::class, 'convocatoriabecas']);
$router->get('/admisiones/contacto', [EstaticasController::class, 'contacto']);

// Comunidad
$router->get('/comunidad/estudiantes', [EstaticasController::class, 'estudiantes']);
$router->get('/comunidad/familias', [EstaticasController::class, 'familias']);
$router->get('/comunidad/exalumnos', [EstaticasController::class, 'exalumnos']);

// Voces Bilbao
$router->get('/voces-bilbao/noticias', [EstaticasController::class, 'noticias']);
$router->get('/voces-bilbao/entrevistas', [EstaticasController::class, 'entrevistas']);
$router->get('/voces-bilbao/articulos', [EstaticasController::class, 'articulos']);
$router->get('/voces-bilbao/testimonios', [EstaticasController::class, 'testimonios']);

// Contacto
$router->get('/contacto', [EstaticasController::class, 'contacto']);
$router->get('/contacto/directorio', [EstaticasController::class, 'directorio']);
$router->get('/contacto/cultura-y-talento', [EstaticasController::class, 'culturatalento']);
$router->get('/contacto/proveedores', [EstaticasController::class, 'proveedores']);

// Legal y utilidad
$router->get('/aviso-privacidad', [EstaticasController::class, 'avisoprivacidad']);
$router->get('/terminos-y-condiciones', [EstaticasController::class, 'terminoscondiciones']);
$router->get('/mapa-del-sitio', [EstaticasController::class, 'mapadelsitio']);
$router->get('/en', [EstaticasController::class, 'en']);

// Auth
$router->get('/login', [AuthController::class, 'login']);
$router->post('/login', [AuthController::class, 'login']);
$router->post('/logout', [AuthController::class, 'logout']);

$router->get('/registro', [AuthController::class, 'registro']);
$router->post('/registro', [AuthController::class, 'registro']);

$router->get('/olvide', [AuthController::class, 'olvide']);
$router->post('/olvide', [AuthController::class, 'olvide']);

$router->get('/reestablecer', [AuthController::class, 'reestablecer']);
$router->post('/reestablecer', [AuthController::class, 'reestablecer']);

$router->get('/mensaje', [AuthController::class, 'mensaje']);
$router->get('/confirmar-cuenta', [AuthController::class, 'confirmar']);

// Blog Público
$router->get('/blog', [BlogController::class, 'blogPublico']);
$router->pattern('/blog/{slug}', [BlogController::class, 'verArticulo']);

// Blog Admin
$router->get('/blog/login', [BlogController::class, 'login']);
$router->post('/blog/login', [BlogController::class, 'login']);
$router->post('/blog/logout', [BlogController::class, 'logout']);

$router->get('/blog/dashboard', [BlogController::class, 'dashboard']);

$router->get('/blog/articulos', [BlogController::class, 'articulos']);
$router->get('/blog/articulos/crear', [BlogController::class, 'crearArticulo']);
$router->post('/blog/articulos/crear', [BlogController::class, 'crearArticulo']);
$router->get('/blog/articulos/editar', [BlogController::class, 'editarArticulo']);
$router->post('/blog/articulos/editar', [BlogController::class, 'editarArticulo']);
$router->post('/blog/articulos/eliminar', [BlogController::class, 'eliminarArticulo']);

$router->get('/blog/usuarios', [BlogController::class, 'usuarios']);
$router->get('/blog/usuarios/crear', [BlogController::class, 'crearUsuario']);
$router->post('/blog/usuarios/crear', [BlogController::class, 'crearUsuario']);
$router->get('/blog/usuarios/editar', [BlogController::class, 'editarUsuario']);
$router->post('/blog/usuarios/editar', [BlogController::class, 'editarUsuario']);
$router->post('/blog/usuarios/eliminar', [BlogController::class, 'eliminarUsuario']);

$router->get('/blog/categorias', [BlogController::class, 'categorias']);
$router->get('/blog/categorias/crear', [BlogController::class, 'crearCategoria']);
$router->post('/blog/categorias/crear', [BlogController::class, 'crearCategoria']);
$router->get('/blog/categorias/editar', [BlogController::class, 'editarCategoria']);
$router->post('/blog/categorias/editar', [BlogController::class, 'editarCategoria']);
$router->post('/blog/categorias/eliminar', [BlogController::class, 'eliminarCategoria']);

$router->comprobarRutas();
