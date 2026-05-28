<?php

require_once __DIR__ . '/../includes/app.php';

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

// Voces Bilbao — Noticias
$router->get('/noticias', [EstaticasController::class, 'noticias']);
$router->pattern('/noticias/{slug}', [EstaticasController::class, 'noticiaDetalle']);

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

$router->get('/dashboard', [BlogController::class, 'dashboard']);
$router->get('/blog/dashboard', function() { header('Location: /dashboard'); exit; });

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

// Blog Admin — Noticias
$router->get('/dashboard/noticias', [BlogController::class, 'noticias']);
$router->get('/dashboard/noticias/crear', [BlogController::class, 'crearNoticia']);
$router->post('/dashboard/noticias/crear', [BlogController::class, 'crearNoticia']);
$router->get('/dashboard/noticias/editar', [BlogController::class, 'editarNoticia']);
$router->post('/dashboard/noticias/editar', [BlogController::class, 'editarNoticia']);
$router->post('/dashboard/noticias/eliminar', [BlogController::class, 'eliminarNoticia']);

$router->get('/dashboard/noticias/categorias', [BlogController::class, 'categoriasNoticias']);
$router->get('/dashboard/noticias/categorias/crear', [BlogController::class, 'crearCategoriaNoticia']);
$router->post('/dashboard/noticias/categorias/crear', [BlogController::class, 'crearCategoriaNoticia']);
$router->get('/dashboard/noticias/categorias/editar', [BlogController::class, 'editarCategoriaNoticia']);
$router->post('/dashboard/noticias/categorias/editar', [BlogController::class, 'editarCategoriaNoticia']);
$router->post('/dashboard/noticias/categorias/eliminar', [BlogController::class, 'eliminarCategoriaNoticia']);

// Alias de compatibilidad
$router->get('/blog/noticias', function() { header('Location: /dashboard/noticias'); exit; });

$router->comprobarRutas();
