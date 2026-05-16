<?php

require_once __DIR__ . '/includes/app.php';

use MVC\Router;
use Controllers\EstaticasController;
use Controllers\AuthController;

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

$router->comprobarRutas();
