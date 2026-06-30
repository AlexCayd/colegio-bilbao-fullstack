<?php

namespace Controllers;

use MVC\Router;
use Model\Articulo;
use Model\Noticia;
use Model\Testimonial;

class EstaticasController {

    // ---- HOME ----
    public static function index(Router $router) {
        Articulo::publicarProgramados();
        Noticia::publicarProgramadas();

        $todos = Articulo::allConDetalles('publicado');
        $articulos_recientes = array_slice($todos, 0, 5);

        $noticia_destacada = Noticia::destacada();
        $excluirId = $noticia_destacada ? (int)$noticia_destacada->id : 0;
        $noticias_recientes = Noticia::recientes(4, $excluirId);

        $testimoniales = Testimonial::aprobados();

        $extra_head = '<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>'
            . '<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>'
            . '<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>';

        $router->render('estaticas/index', [
            'titulo'              => 'Home',
            'articulos_recientes' => $articulos_recientes,
            'noticia_destacada'   => $noticia_destacada,
            'noticias_recientes'  => $noticias_recientes,
            'testimoniales'       => $testimoniales,
            'extra_head'          => $extra_head,
        ]);
    }

    // ---- CONÓCENOS ----
    public static function quienessomos(Router $router) {
        $router->render('estaticas/conocenos/quienes-somos', ['titulo' => 'Quiénes Somos']);
    }

    public static function equipoeducativo(Router $router) {
        $router->render('estaticas/conocenos/equipo-educativo', ['titulo' => 'Equipo Educativo']);
    }

    public static function instalaciones(Router $router) {
        $router->render('estaticas/conocenos/instalaciones', ['titulo' => 'Instalaciones']);
    }

    public static function certificaciones(Router $router) {
        $router->render('estaticas/conocenos/certificaciones-y-reconocimientos', ['titulo' => 'Certificaciones y Reconocimientos']);
    }

    // ---- MODELO EDUCATIVO ----
    public static function modelovida(Router $router) {
        $router->render('estaticas/modelo-educativo/modelo-vida', ['titulo' => 'Modelo Educativo VIDA']);
    }

    public static function filosofiametodologia(Router $router) {
        $router->render('estaticas/modelo-educativo/filosofia-y-metodologia', ['titulo' => 'Filosofía y Metodología']);
    }

    public static function aprendizajeintegral(Router $router) {
        $router->render('estaticas/modelo-educativo/aprendizaje-integral', ['titulo' => 'Aprendizaje Integral']);
    }

    public static function idiomas(Router $router) {
        $router->render('estaticas/modelo-educativo/idiomas', ['titulo' => 'Idiomas']);
    }

    // ---- NIVELES ACADÉMICOS ----
    public static function niveles(Router $router) {
        $seo_titulo = 'Niveles Académicos | Colegio Bilbao';
        $seo_descripcion = 'Preescolar, Primaria, Secundaria y Preparatoria. Conoce la propuesta educativa del Colegio Bilbao para cada etapa.';
        $router->render('estaticas/niveles', compact('seo_titulo', 'seo_descripcion'));
    }

    public static function preescolar(Router $router) {
        $router->render('estaticas/niveles-academicos/preescolar', ['titulo' => 'Preescolar']);
    }

    public static function primaria(Router $router) {
        $router->render('estaticas/niveles-academicos/primaria', ['titulo' => 'Primaria']);
    }

    public static function secundaria(Router $router) {
        $router->render('estaticas/niveles-academicos/secundaria', ['titulo' => 'Secundaria']);
    }

    public static function preparatoria(Router $router) {
        $router->render('estaticas/niveles-academicos/preparatoria', ['titulo' => 'Preparatoria']);
    }

    // ---- VIDA ESCOLAR ----
    public static function afterschool(Router $router) {
        $router->render('estaticas/vida-escolar/afterschool-extracurriculares', ['titulo' => 'Afterschool y Extracurriculares']);
    }

    public static function cuidadobienestar(Router $router) {
        $router->render('estaticas/vida-escolar/cuidado-y-bienestar', ['titulo' => 'Cuidado y Bienestar']);
    }

    public static function eventostradicones(Router $router) {
        $router->render('estaticas/vida-escolar/eventos-y-tradiciones', ['titulo' => 'Eventos y Tradiciones']);
    }

    public static function futurouniversitario(Router $router) {
        $router->render('estaticas/vida-escolar/futuro-universitario-becas', ['titulo' => 'Futuro Universitario y Becas']);
    }

    public static function programadual(Router $router) {
        $router->render('estaticas/vida-escolar/programa-dual', ['titulo' => 'Programa Dual']);
    }

    public static function serviciofamilias(Router $router) {
        $router->render('estaticas/vida-escolar/servicios-para-familias', ['titulo' => 'Servicios para Familias']);
    }

    // ---- ADMISIONES ----
    public static function inicio(Router $router) {
        $router->render('estaticas/admisiones/inicio', ['titulo' => 'Admisiones']);
    }

    public static function proceso(Router $router) {
        $router->render('estaticas/admisiones/proceso', ['titulo' => 'Proceso de Admisión']);
    }

    public static function preguntasfrecuentes(Router $router) {
        $router->render('estaticas/admisiones/preguntas-frecuentes', ['titulo' => 'Preguntas Frecuentes']);
    }

    public static function convenios(Router $router) {
        $router->render('estaticas/admisiones/convenios', ['titulo' => 'Convenios']);
    }

    public static function convocatoriabecas(Router $router) {
        $router->render('estaticas/admisiones/convocatoria-becas', ['titulo' => 'Convocatoria de Becas']);
    }

    public static function contacto(Router $router) {
        $router->render('estaticas/admisiones/contacto', ['titulo' => 'Contacto Admisiones']);
    }

    // ---- COMUNIDAD ----
    public static function estudiantes(Router $router) {
        $router->render('estaticas/comunidad/estudiantes', ['titulo' => 'Estudiantes']);
    }

    public static function familias(Router $router) {
        $router->render('estaticas/comunidad/familias', ['titulo' => 'Familias']);
    }

    public static function exalumnos(Router $router) {
        $router->render('estaticas/comunidad/exalumnos', ['titulo' => 'Exalumnos']);
    }

    // ---- VOCES BILBAO ----
    public static function noticias(Router $router) {
        Noticia::publicarProgramadas();

        $todas      = Noticia::publicadas();
        $featured   = null;
        $listado    = [];

        foreach ($todas as $n) {
            if (!$featured && $n->destacada) {
                $featured = $n;
            } else {
                $listado[] = $n;
            }
        }
        if (!$featured && !empty($listado)) {
            $featured = array_shift($listado);
        }

        usort($listado, fn($a, $b) =>
            strtotime($b->fecha_publicacion ?? '0') <=> strtotime($a->fecha_publicacion ?? '0')
        );

        $extra_head = '<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>';
        $router->render('noticias/index', [
            'seo_titulo'      => 'Noticias · Colegio Bilbao',
            'seo_descripcion' => 'Mantente al día con todo lo que pasa en el Colegio Bilbao: logros académicos, eventos culturales, deportes y vida escolar.',
            'extra_head'      => $extra_head,
            'featured'        => $featured,
            'noticias'        => $listado,
            'categorias'      => Noticia::categorias(),
        ]);
    }

    public static function noticiaDetalle(Router $router) {
        $slug = $router->params['slug'] ?? '';
        if (!$slug) { header('Location: /noticias'); exit; }

        $noticia = Noticia::findBySlug($slug);
        if (!$noticia) { header('Location: /noticias'); exit; }

        $router->render('noticias/detalle', [
            'seo_titulo'      => htmlspecialchars($noticia->titulo),
            'seo_descripcion' => htmlspecialchars($noticia->extracto ?? ''),
            'seo_imagen'      => $noticia->portada ?? '',
            'noticia'         => $noticia,
            'relacionadas'    => Noticia::relacionadas((int)$noticia->id, $noticia->categoria_id ? (int)$noticia->categoria_id : null),
        ]);
    }

    public static function entrevistas(Router $router) {
        $router->render('estaticas/voces-bilbao/entrevistas', ['titulo' => 'Entrevistas']);
    }

    public static function articulos(Router $router) {
        $router->render('estaticas/voces-bilbao/articulos', ['titulo' => 'Artículos']);
    }

    public static function testimonios(Router $router) {
        $router->render('estaticas/voces-bilbao/testimonios', ['titulo' => 'Testimonios']);
    }

    // ---- CONTACTO ----
    public static function directorio(Router $router) {
        $router->render('estaticas/contacto/directorio', ['titulo' => 'Directorio']);
    }

    public static function culturatalento(Router $router) {
        $router->render('estaticas/contacto/cultura-y-talento', ['titulo' => 'Cultura y Talento']);
    }

    public static function proveedores(Router $router) {
        $router->render('estaticas/contacto/proveedores', ['titulo' => 'Proveedores']);
    }

    // ---- LEGAL / UTILIDAD ----
    public static function avisoprivacidad(Router $router) {
        $router->render('estaticas/aviso-privacidad/aviso-privacidad', ['titulo' => 'Aviso de Privacidad']);
    }

    public static function terminoscondiciones(Router $router) {
        $router->render('estaticas/terminos-y-condiciones/terminos-y-condiciones', ['titulo' => 'Términos y Condiciones']);
    }

    public static function mapadelsitio(Router $router) {
        $router->render('estaticas/mapa-del-sitio/mapa-del-sitio', ['titulo' => 'Mapa del Sitio']);
    }

    public static function en(Router $router) {
        $router->render('estaticas/en/en', ['titulo' => 'Colegio Bilbao — English']);
    }

    public static function feedbackTestimoniales(Router $router) {
        $enviado = false;
        $errores = [];
        $datos   = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $t = new Testimonial();
            $t->nombre     = trim(strip_tags($_POST['nombre']   ?? ''));
            $t->rol        = $_POST['rol']       ?? '';
            $t->comentario = trim(strip_tags($_POST['comentario'] ?? ''));
            $t->aprobado   = 0;

            $datos   = ['nombre' => $t->nombre, 'rol' => $t->rol, 'comentario' => $t->comentario];
            $errores = $t->validar();

            if (empty($errores)) {
                $t->created_at = date('Y-m-d H:i:s');
                $t->guardar();
                $enviado = true;
                $datos   = [];
            }
        }

        $extra_head = '<style>
.fb-page-body{background:#f4f8fd;}
.fb-hero{background:linear-gradient(150deg,#0b1f3d 0%,#163a70 60%,#1d5ab0 100%);padding:80px 24px 60px;text-align:center;color:#fff;}
.fb-hero__eyebrow{display:inline-block;font-size:.75rem;letter-spacing:.16em;text-transform:uppercase;font-weight:700;color:rgba(255,255,255,.72);margin-bottom:14px;}
.fb-hero__title{font-size:clamp(2rem,5vw,3.4rem);font-weight:900;line-height:.95;letter-spacing:-.03em;margin-bottom:16px;}
.fb-hero__sub{font-size:1rem;font-weight:300;color:rgba(255,255,255,.78);max-width:44ch;margin:0 auto;}
.fb-wrap{max-width:600px;margin:0 auto;padding:52px 24px 80px;width:100%;}
.fb-card{background:#fff;border-radius:20px;padding:clamp(24px,5vw,44px);box-shadow:0 12px 48px rgba(22,40,80,.10);}
.fb-card__title{font-size:1.2rem;font-weight:800;color:#16202e;margin-bottom:24px;}
.fb-field{margin-bottom:20px;}
.fb-label{display:block;font-size:.82rem;font-weight:700;color:#374c69;margin-bottom:6px;letter-spacing:.02em;}
.fb-input,.fb-select,.fb-textarea{width:100%;padding:12px 16px;border:1.5px solid #d1dce8;border-radius:12px;font-family:inherit;font-size:.95rem;color:#16202e;transition:border-color .2s,box-shadow .2s;background:#fff;}
.fb-input:focus,.fb-select:focus,.fb-textarea:focus{outline:none;border-color:#4285f4;box-shadow:0 0 0 3px rgba(66,133,244,.14);}
.fb-textarea{resize:vertical;min-height:120px;line-height:1.6;}
.fb-counter{font-size:.76rem;color:#7a8fa8;text-align:right;margin-top:4px;}
.fb-errores{background:#fff0f3;border:1px solid #fca5a5;border-radius:12px;padding:14px 18px;margin-bottom:20px;}
.fb-errores li{color:#b91c1c;font-size:.88rem;line-height:1.7;}
.fb-btn{display:block;width:100%;padding:15px;border-radius:14px;background:#163a70;color:#fff;font-family:inherit;font-size:1rem;font-weight:700;border:none;cursor:pointer;transition:background .2s,transform .15s;}
.fb-btn:hover{background:#1d5ab0;transform:translateY(-1px);}
.fb-success{text-align:center;padding:40px 20px;}
.fb-success__mascot{width:100px;margin-bottom:20px;display:block;margin-left:auto;margin-right:auto;}
.fb-success__title{font-size:1.5rem;font-weight:800;color:#163a70;margin-bottom:10px;}
.fb-success__msg{color:#4a5c6e;font-weight:400;line-height:1.6;max-width:38ch;margin:0 auto 28px;}
.fb-success__back{display:inline-flex;align-items:center;gap:8px;padding:12px 26px;border-radius:14px;background:#163a70;color:#fff;text-decoration:none;font-weight:700;font-size:.9rem;}
.fb-success__back:hover{background:#1d5ab0;}
</style>';

        $router->render('estaticas/feedback-testimoniales', [
            'seo_titulo'  => 'Deja tu testimonio · Colegio Bilbao',
            'seo_descripcion' => 'Comparte tu experiencia en el Colegio Bilbao. Tu testimonio inspira a otras familias.',
            'enviado'     => $enviado,
            'errores'     => $errores,
            'datos'       => $datos,
            'extra_head'  => $extra_head,
        ]);
    }

}
