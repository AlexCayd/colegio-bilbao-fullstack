<?php

namespace Controllers;

use MVC\Router;
use Model\Articulo;
use Model\Noticia;

class EstaticasController {

    // ---- HOME ----
    public static function index(Router $router) {
        Articulo::publicarProgramados();
        Noticia::publicarProgramadas();

        $todos = Articulo::allConDetalles('publicado');
        $articulos_recientes = array_slice($todos, 0, 5);

        $noticia_destacada = Noticia::destacada();
        $excluirId = $noticia_destacada ? (int)$noticia_destacada->id : 0;
        $noticias_recientes = Noticia::recientes(3, $excluirId);

        $router->render('estaticas/index', [
            'titulo'              => 'Home',
            'articulos_recientes' => $articulos_recientes,
            'noticia_destacada'   => $noticia_destacada,
            'noticias_recientes'  => $noticias_recientes,
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

        $router->renderBlog('noticias/index', [
            'titulo'     => 'Noticias · Colegio Bilbao',
            'featured'   => $featured,
            'noticias'   => $listado,
            'categorias' => Noticia::categorias(),
        ]);
    }

    public static function noticiaDetalle(Router $router) {
        $slug = $router->params['slug'] ?? '';
        if (!$slug) { header('Location: /noticias'); exit; }

        $noticia = Noticia::findBySlug($slug);
        if (!$noticia) { header('Location: /noticias'); exit; }

        $router->renderBlog('noticias/detalle', [
            'titulo'      => htmlspecialchars($noticia->titulo) . ' · Colegio Bilbao',
            'noticia'     => $noticia,
            'relacionadas' => Noticia::relacionadas((int)$noticia->id, $noticia->categoria_id ? (int)$noticia->categoria_id : null),
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

}
