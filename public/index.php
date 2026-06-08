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

// Auth público (cuentas de familias/alumnos — ahora bajo /cuenta/*)
$router->get('/cuenta/login', [AuthController::class, 'login']);
$router->post('/cuenta/login', [AuthController::class, 'login']);
$router->post('/cuenta/logout', [AuthController::class, 'logout']);

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

// Admin — Login/Logout
$router->get('/login', [BlogController::class, 'login']);
$router->post('/login', [BlogController::class, 'login']);
$router->post('/logout', [BlogController::class, 'logout']);

// Admin — Dashboard
$router->get('/dashboard', [BlogController::class, 'dashboard']);

// Admin — Artículos
$router->get('/dashboard/articulos', [BlogController::class, 'articulos']);
$router->get('/dashboard/articulos/crear', [BlogController::class, 'crearArticulo']);
$router->post('/dashboard/articulos/crear', [BlogController::class, 'crearArticulo']);
$router->get('/dashboard/articulos/editar', [BlogController::class, 'editarArticulo']);
$router->post('/dashboard/articulos/editar', [BlogController::class, 'editarArticulo']);
$router->post('/dashboard/articulos/eliminar', [BlogController::class, 'eliminarArticulo']);
$router->post('/dashboard/articulos/enviar-revision', [BlogController::class, 'enviarRevisionArticulo']);
$router->post('/dashboard/articulos/aprobar', [BlogController::class, 'aprobarArticulo']);
$router->post('/dashboard/articulos/rechazar', [BlogController::class, 'rechazarArticulo']);
$router->post('/dashboard/articulos/like', [BlogController::class, 'likeArticulo']);

// Admin — Usuarios
$router->get('/dashboard/usuarios', [BlogController::class, 'usuarios']);
$router->get('/dashboard/usuarios/crear', [BlogController::class, 'crearUsuario']);
$router->post('/dashboard/usuarios/crear', [BlogController::class, 'crearUsuario']);
$router->get('/dashboard/usuarios/editar', [BlogController::class, 'editarUsuario']);
$router->post('/dashboard/usuarios/editar', [BlogController::class, 'editarUsuario']);
$router->post('/dashboard/usuarios/eliminar', [BlogController::class, 'eliminarUsuario']);

// Admin — Categorías de artículos
$router->get('/dashboard/categorias', [BlogController::class, 'categorias']);
$router->get('/dashboard/categorias/crear', [BlogController::class, 'crearCategoria']);
$router->post('/dashboard/categorias/crear', [BlogController::class, 'crearCategoria']);
$router->get('/dashboard/categorias/editar', [BlogController::class, 'editarCategoria']);
$router->post('/dashboard/categorias/editar', [BlogController::class, 'editarCategoria']);
$router->post('/dashboard/categorias/eliminar', [BlogController::class, 'eliminarCategoria']);

// Admin — Noticias
$router->get('/dashboard/noticias', [BlogController::class, 'noticias']);
$router->get('/dashboard/noticias/crear', [BlogController::class, 'crearNoticia']);
$router->post('/dashboard/noticias/crear', [BlogController::class, 'crearNoticia']);
$router->get('/dashboard/noticias/editar', [BlogController::class, 'editarNoticia']);
$router->post('/dashboard/noticias/editar', [BlogController::class, 'editarNoticia']);
$router->post('/dashboard/noticias/eliminar', [BlogController::class, 'eliminarNoticia']);
$router->post('/dashboard/noticias/enviar-revision', [BlogController::class, 'enviarRevisionNoticia']);
$router->post('/dashboard/noticias/aprobar', [BlogController::class, 'aprobarNoticia']);
$router->post('/dashboard/noticias/rechazar', [BlogController::class, 'rechazarNoticia']);
$router->post('/dashboard/noticias/like', [BlogController::class, 'likeNoticia']);

// Admin — Categorías de noticias
$router->get('/dashboard/noticias/categorias', [BlogController::class, 'categoriasNoticias']);
$router->get('/dashboard/noticias/categorias/crear', [BlogController::class, 'crearCategoriaNoticia']);
$router->post('/dashboard/noticias/categorias/crear', [BlogController::class, 'crearCategoriaNoticia']);
$router->get('/dashboard/noticias/categorias/editar', [BlogController::class, 'editarCategoriaNoticia']);
$router->post('/dashboard/noticias/categorias/editar', [BlogController::class, 'editarCategoriaNoticia']);
$router->post('/dashboard/noticias/categorias/eliminar', [BlogController::class, 'eliminarCategoriaNoticia']);

// Admin — Revisiones y Autores
$router->get('/dashboard/revisiones', [BlogController::class, 'revisiones']);
$router->get('/dashboard/autores', [BlogController::class, 'autores']);

// Aliases de compatibilidad (redirects 301)
$router->get('/blog/login',     function() { header('Location: /login', true, 301); exit; });
$router->get('/blog/articulos', function() { header('Location: /dashboard/articulos', true, 301); exit; });
$router->get('/blog/usuarios',  function() { header('Location: /dashboard/usuarios', true, 301); exit; });
$router->get('/blog/categorias',function() { header('Location: /dashboard/categorias', true, 301); exit; });
$router->get('/blog/noticias',  function() { header('Location: /dashboard/noticias', true, 301); exit; });
$router->get('/blog/dashboard', function() { header('Location: /dashboard', true, 301); exit; });

$router->comprobarRutas();
