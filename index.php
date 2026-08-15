<?php
// Servir assets estáticos de /build/ desde public/build/ (sin depender de IIS URL Rewrite)
(function() {
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    if (!preg_match('#^/build/(.+)$#', $uri, $m)) return;

    // ⚠️ Los justificantes vivieron aquí y NO deben servirse por esta vía: este shim
    // corre ANTES de includes/app.php, o sea sin sesión y sin permisos — cualquiera
    // con la URL se descargaba el parte médico. Los nuevos van a storage/ y solo
    // salen por /dashboard/suplencias/justificante, que sí comprueba quién pregunta;
    // este portazo cubre los archivos heredados que sigan en disco.
    if (str_starts_with($m[1], 'assets/suplencias/')) { http_response_code(404); exit; }

    // Normalizar antes de tocar el disco: sin esto un `/build/../../includes/.env`
    // se resolvería fuera de public/build/ y filtraría secretos.
    $base = realpath(__DIR__ . '/public/build');
    $file = realpath(__DIR__ . '/public/build/' . rawurldecode($m[1]));
    if ($base === false || $file === false
        || !str_starts_with($file, $base . DIRECTORY_SEPARATOR)
        || !is_file($file)) { http_response_code(404); exit; }
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    $types = [
        'css'  => 'text/css', 'js' => 'application/javascript',
        'png'  => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
        'gif'  => 'image/gif', 'svg' => 'image/svg+xml', 'ico' => 'image/x-icon',
        'webp' => 'image/webp', 'avif' => 'image/avif',
        'mp4'  => 'video/mp4', 'webm' => 'video/webm',
        'woff' => 'font/woff', 'woff2' => 'font/woff2',
    ];
    $mime = $types[$ext] ?? 'application/octet-stream';
    $size = filesize($file);

    // Range request support (required for HTML5 video playback in browsers)
    if (in_array($ext, ['mp4', 'webm'], true)) {
        header('Accept-Ranges: bytes');
        $start = 0;
        $end   = $size - 1;

        if (isset($_SERVER['HTTP_RANGE'])) {
            preg_match('/bytes=(\d*)-(\d*)/i', $_SERVER['HTTP_RANGE'], $m);
            $start = $m[1] !== '' ? (int)$m[1] : 0;
            $end   = $m[2] !== '' ? (int)$m[2] : $size - 1;
            $end   = min($end, $size - 1);
            $length = $end - $start + 1;

            http_response_code(206);
            header('Content-Range: bytes ' . $start . '-' . $end . '/' . $size);
            header('Content-Length: ' . $length);
            header('Content-Type: ' . $mime);
            header('Cache-Control: public, max-age=86400');

            $fp = fopen($file, 'rb');
            fseek($fp, $start);
            $remaining = $length;
            while ($remaining > 0 && !feof($fp)) {
                $chunk = min(8192, $remaining);
                echo fread($fp, $chunk);
                $remaining -= $chunk;
            }
            fclose($fp);
        } else {
            header('Content-Length: ' . $size);
            header('Content-Type: ' . $mime);
            header('Cache-Control: public, max-age=86400');
            readfile($file);
        }
        exit;
    }

    header('Content-Type: ' . $mime);
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
$router->get('/niveles', [EstaticasController::class, 'niveles']);
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
$router->get('/comunidad/colaboradores', [EstaticasController::class, 'colaboradores']);

// Voces Bilbao — Noticias
$router->get('/noticias', [EstaticasController::class, 'noticias']);
$router->pattern('/noticias/{slug}', [EstaticasController::class, 'noticiaDetalle']);

// Contacto
$router->get('/contacto', [EstaticasController::class, 'contacto']);
$router->get('/contacto/directorio', [EstaticasController::class, 'directorio']);
$router->get('/contacto/cultura-y-talento', [EstaticasController::class, 'culturatalento']);
$router->get('/contacto/proveedores', [EstaticasController::class, 'proveedores']);

// Feedback y testimoniales (formulario público)
$router->get('/feedback-testimoniales',  [EstaticasController::class, 'feedbackTestimoniales']);
$router->post('/feedback-testimoniales', [EstaticasController::class, 'feedbackTestimoniales']);

// Legal y utilidad
$router->get('/aviso-privacidad', [EstaticasController::class, 'avisoprivacidad']);
$router->get('/terminos-y-condiciones', [EstaticasController::class, 'terminoscondiciones']);
$router->get('/mapa-del-sitio', [EstaticasController::class, 'mapadelsitio']);

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

// Admin — Home de módulos + módulos
$router->get('/dashboard', [BlogController::class, 'home']);
$router->get('/dashboard/redaccion', [BlogController::class, 'redaccion']);

// Admin — Módulo Suplencias
$router->get('/dashboard/suplencias', [BlogController::class, 'suplencias']);
$router->get('/dashboard/suplencias/dashboard', [BlogController::class, 'suplenciasDashboard']);
$router->get('/dashboard/suplencias/solicitar', [BlogController::class, 'solicitarSuplencia']);
$router->post('/dashboard/suplencias/solicitar', [BlogController::class, 'solicitarSuplencia']);
$router->get('/dashboard/suplencias/crear', [BlogController::class, 'crearSuplencia']);
$router->post('/dashboard/suplencias/crear', [BlogController::class, 'crearSuplencia']);
$router->get('/dashboard/suplencias/agendar', [BlogController::class, 'agendarSuplencia']);
$router->post('/dashboard/suplencias/agendar', [BlogController::class, 'agendarSuplencia']);
$router->post('/dashboard/suplencias/justificar', [BlogController::class, 'justificarSuplencia']);
$router->get('/dashboard/suplencias/mis-coberturas', [BlogController::class, 'misCoberturas']);
$router->get('/dashboard/suplencias/historial', [BlogController::class, 'historialSuplencias']);
$router->post('/dashboard/suplencias/validar', [BlogController::class, 'validarCobertura']);
$router->post('/dashboard/suplencias/eliminar', [BlogController::class, 'eliminarSuplencia']);
$router->post('/dashboard/suplencias/aprobar-justificante', [BlogController::class, 'aprobarJustificante']);
$router->post('/dashboard/suplencias/reabrir-hora',          [BlogController::class, 'reabrirHora']);
// ¿El profesor ausente dejó trabajo para el grupo? Lo marca prefectura, hora a hora.
$router->post('/dashboard/suplencias/trabajo',               [BlogController::class, 'marcarTrabajo']);
// Única puerta al archivo del justificante: vive fuera de public/ y este endpoint
// comprueba permisos (dirección, o el propio ausente).
$router->get('/dashboard/suplencias/justificante',           [BlogController::class, 'descargarJustificante']);
// Cola de justificantes vencidos (pasados Suplencia::DIAS_DESCARGA días)
$router->get('/dashboard/suplencias/justificantes',          [BlogController::class, 'justificantes']);
$router->post('/dashboard/suplencias/justificantes/resolver', [BlogController::class, 'resolverJustificante']);
$router->post('/dashboard/suplencias/validar-prefectura',   [BlogController::class, 'validarPrefectura']);
$router->get('/dashboard/suplencias/buscar-colaboradores', [BlogController::class, 'buscarColaboradores']);
$router->get('/dashboard/suplencias/sugerir', [BlogController::class, 'sugerirSuplentes']);
$router->get('/dashboard/suplencias/horario', [BlogController::class, 'horarioProfesorJson']);

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
$router->get('/dashboard/usuarios/cumpleanos', [BlogController::class, 'cumpleanos']);
$router->get('/dashboard/usuarios/crear', [BlogController::class, 'crearUsuario']);
$router->post('/dashboard/usuarios/crear', [BlogController::class, 'crearUsuario']);
$router->get('/dashboard/usuarios/editar', [BlogController::class, 'editarUsuario']);
$router->post('/dashboard/usuarios/editar', [BlogController::class, 'editarUsuario']);
$router->post('/dashboard/usuarios/eliminar', [BlogController::class, 'eliminarUsuario']);
// Editor del horario de un profesor. Vive en Usuarios (y no en Horarios) porque es el
// único punto del panel que ESCRIBE horario: el módulo Horarios es de solo lectura.
$router->get('/dashboard/usuarios/horario',           [BlogController::class, 'horarioEditor']);
$router->post('/dashboard/usuarios/horario/bloque',   [BlogController::class, 'guardarBloqueHorario']);
$router->post('/dashboard/usuarios/horario/lugar',    [BlogController::class, 'crearLugarGuardia']);
$router->post('/dashboard/usuarios/horario/eliminar', [BlogController::class, 'eliminarBloqueHorario']);
$router->get('/dashboard/usuarios/horario/profesores', [BlogController::class, 'buscarProfesoresHorario']);
$router->get('/dashboard/perfil', [BlogController::class, 'perfil']);
$router->post('/dashboard/perfil', [BlogController::class, 'perfil']);

// Directorios de personal (módulos profesores / prefectura / administrativos / directivos)
$router->get('/dashboard/profesores',      [BlogController::class, 'profesores']);
$router->get('/dashboard/prefectura',      [BlogController::class, 'prefectura']);
$router->get('/dashboard/administrativos', [BlogController::class, 'administrativos']);
$router->get('/dashboard/directivos',      [BlogController::class, 'directivos']);

// Admin — Módulo Horarios
$router->get('/dashboard/horarios',                [BlogController::class, 'horarios']);
$router->get('/dashboard/horarios/profesor',       [BlogController::class, 'horariosProfesor']);
$router->get('/dashboard/horarios/aula',           [BlogController::class, 'horariosAula']);
$router->get('/dashboard/horarios/grupo',          [BlogController::class, 'horariosGrupo']);
$router->get('/dashboard/horarios/mi-horario',     [BlogController::class, 'miHorario']);
// El mismo horario en PDF, para llevarlo en papel. Mismo guard: es el suyo.
$router->get('/dashboard/horarios/mi-horario.pdf', [BlogController::class, 'miHorarioPdf']);
$router->get('/dashboard/horarios/importar',       [BlogController::class, 'importarHorarios']);

// Catálogos académicos (módulos aulas / grupos)
$router->get('/dashboard/aulas',          [BlogController::class, 'aulas']);
$router->get('/dashboard/aulas/crear',    [BlogController::class, 'crearAula']);
$router->post('/dashboard/aulas/crear',   [BlogController::class, 'crearAula']);
$router->get('/dashboard/aulas/editar',   [BlogController::class, 'editarAula']);
$router->post('/dashboard/aulas/editar',  [BlogController::class, 'editarAula']);
$router->post('/dashboard/aulas/eliminar',[BlogController::class, 'eliminarAula']);

$router->get('/dashboard/grupos',          [BlogController::class, 'grupos']);
$router->get('/dashboard/grupos/crear',    [BlogController::class, 'crearGrupo']);
$router->post('/dashboard/grupos/crear',   [BlogController::class, 'crearGrupo']);
$router->get('/dashboard/grupos/editar',   [BlogController::class, 'editarGrupo']);
$router->post('/dashboard/grupos/editar',  [BlogController::class, 'editarGrupo']);
$router->post('/dashboard/grupos/eliminar',[BlogController::class, 'eliminarGrupo']);
$router->post('/dashboard/horarios/importar',      [BlogController::class, 'importarHorarios']);

// Admin — Módulo Eventos
$router->get('/dashboard/eventos',          [BlogController::class, 'eventos']);
$router->get('/dashboard/eventos/crear',    [BlogController::class, 'crearEvento']);
$router->post('/dashboard/eventos/crear',   [BlogController::class, 'crearEvento']);
$router->get('/dashboard/eventos/editar',   [BlogController::class, 'editarEvento']);
$router->post('/dashboard/eventos/editar',  [BlogController::class, 'editarEvento']);
$router->post('/dashboard/eventos/eliminar',[BlogController::class, 'eliminarEvento']);

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

// Admin — Testimoniales
$router->get('/dashboard/testimoniales',           [BlogController::class, 'testimoniales']);
$router->post('/dashboard/testimoniales/aprobar',  [BlogController::class, 'aprobarTestimonial']);
$router->post('/dashboard/testimoniales/rechazar', [BlogController::class, 'rechazarTestimonial']);

// Admin — Revisiones pendientes
$router->get('/dashboard/revisiones', [BlogController::class, 'revisiones']);

// Editor — Mis revisiones
$router->get('/dashboard/mis-revisiones', [BlogController::class, 'misRevisiones']);

// Notificaciones (transversales a todos los módulos)
$router->get('/dashboard/notificaciones',              [BlogController::class, 'notificaciones']);
$router->post('/dashboard/notificaciones/leer',        [BlogController::class, 'marcarNotificacionLeida']);
$router->post('/dashboard/notificaciones/leer-todas',  [BlogController::class, 'marcarTodasLeidas']);
$router->post('/dashboard/notificaciones/eliminar',    [BlogController::class, 'eliminarNotificacion']);
$router->post('/dashboard/notificaciones/restaurar',   [BlogController::class, 'restaurarNotificacion']);
$router->post('/dashboard/notificaciones/limpiar',     [BlogController::class, 'limpiarNotificaciones']);

// Admin — Autores
// Intercambios de clase entre profesores (swap puntual, no altera el horario)
$router->get('/dashboard/swaps',           [BlogController::class, 'swaps']);
$router->get('/dashboard/swaps/crear',     [BlogController::class, 'crearSwap']);
$router->post('/dashboard/swaps/crear',    [BlogController::class, 'crearSwap']);
$router->get('/dashboard/swaps/clases',    [BlogController::class, 'clasesSwapJson']);
$router->get('/dashboard/swaps/horario',   [BlogController::class, 'horarioSwapJson']);
$router->get('/dashboard/swaps/buscar',    [BlogController::class, 'buscarProfesoresSwap']);
$router->post('/dashboard/swaps/responder',[BlogController::class, 'responderSwap']);
$router->post('/dashboard/swaps/validar',  [BlogController::class, 'validarSwap']);
$router->post('/dashboard/swaps/cancelar', [BlogController::class, 'cancelarSwap']);

// Soporte técnico: transversal, lo tiene todo el mundo (MODULOS_TRANSVERSALES)
$router->get('/dashboard/soporte', [BlogController::class, 'soporte']);

$router->get('/dashboard/autores', [BlogController::class, 'autores']);

// Alias de compatibilidad (redirects 301)
$router->get('/blog/login',     function() { header('Location: /login', true, 301); exit; });
$router->get('/blog/articulos', function() { header('Location: /dashboard/articulos', true, 301); exit; });
$router->get('/blog/usuarios',  function() { header('Location: /dashboard/usuarios', true, 301); exit; });
$router->get('/blog/categorias',function() { header('Location: /dashboard/categorias', true, 301); exit; });
$router->get('/blog/noticias',  function() { header('Location: /dashboard/noticias', true, 301); exit; });
$router->get('/blog/dashboard', function() { header('Location: /dashboard', true, 301); exit; });

$router->comprobarRutas();
