<?php

namespace Controllers;

use MVC\Router;
use Model\Articulo;
use Model\Noticia;
use Model\Testimonial;
use Model\Evento;
use Model\Materia;
use Model\Ajuste;
use Classes\Pdf;

/**
 * Páginas públicas del sitio institucional.
 *
 * Agrupa todo lo que ve un visitante sin iniciar sesión: portada, Conócenos, Modelo
 * Educativo, Niveles Académicos, Vida Escolar, Admisiones, Comunidad, Noticias, Contacto
 * y las páginas legales. El blog público vive en BlogController, no aquí.
 *
 * La mayoría de los métodos son de una línea: renderizan una vista sin datos, porque el
 * contenido está escrito en la propia plantilla. Los que sí consultan modelos son
 * index(), noticias(), noticiaDetalle(), estudiantes(), familias() y
 * feedbackTestimoniales(); el resto responde al patrón:
 *
 *     $router->render('estaticas/<seccion>/<pagina>', ['titulo' => '<Título>']);
 *
 * Para añadir una página estática hacen falta tres cosas: la ruta en index.php, un
 * método aquí y la vista en views/estaticas/.
 *
 * ── SEO ──
 * Las páginas nuevas pasan $seo_titulo y $seo_descripcion; las antiguas solo $titulo y
 * heredan los valores por defecto del layout. No es un error, es migración pendiente.
 *
 * @package Controllers
 */
class EstaticasController {

    // ---- HOME ----
    /**
     * Portada del sitio.
     *
     * Antes de pintar nada publica el contenido programado cuya fecha ya llegó: no hay
     * cron en el proyecto, así que el disparador es la propia visita.
     *
     * Carga Three.js (bosque del hero) y GSAP con ScrollTrigger vía $extra_head, porque
     * el layout público no los incluye por defecto.
     *
     * @param  Router $router
     * @return void
     */
    public static function index(Router $router) {
        Articulo::publicarProgramados();
        Noticia::publicarProgramadas();

        $todos = Articulo::allConDetalles('publicado');
        $articulos_recientes = array_slice($todos, 0, 5);

        $noticia_destacada = Noticia::destacada();
        $excluirId = $noticia_destacada ? (int)$noticia_destacada->id : 0;
        $noticias_recientes = Noticia::recientes(4, $excluirId);

        $testimoniales = Testimonial::aprobados();

        $extra_head = three_js_tag()
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
    /** Conócenos › Quiénes Somos. @param Router $router @return void */
    public static function quienessomos(Router $router) {
        $router->render('estaticas/conocenos/quienes-somos', ['titulo' => 'Quiénes Somos']);
    }

    /** Conócenos › Equipo Educativo. @param Router $router @return void */
    public static function equipoeducativo(Router $router) {
        $router->render('estaticas/conocenos/equipo-educativo', ['titulo' => 'Equipo Educativo']);
    }

    /** Conócenos › Instalaciones. @param Router $router @return void */
    public static function instalaciones(Router $router) {
        $router->render('estaticas/conocenos/instalaciones', ['titulo' => 'Instalaciones']);
    }

    /** Conócenos › Certificaciones y Reconocimientos. @param Router $router @return void */
    public static function certificaciones(Router $router) {
        $router->render('estaticas/conocenos/certificaciones-y-reconocimientos', ['titulo' => 'Certificaciones y Reconocimientos']);
    }

    // ---- MODELO EDUCATIVO ----
    /** Modelo Educativo › Modelo VIDA. @param Router $router @return void */
    public static function modelovida(Router $router) {
        $router->render('estaticas/modelo-educativo/modelo-vida', ['titulo' => 'Modelo Educativo VIDA']);
    }

    /** Modelo Educativo › Filosofía y Metodología. @param Router $router @return void */
    public static function filosofiametodologia(Router $router) {
        $router->render('estaticas/modelo-educativo/filosofia-y-metodologia', ['titulo' => 'Filosofía y Metodología']);
    }

    /** Modelo Educativo › Aprendizaje Integral. @param Router $router @return void */
    public static function aprendizajeintegral(Router $router) {
        $router->render('estaticas/modelo-educativo/aprendizaje-integral', ['titulo' => 'Aprendizaje Integral']);
    }

    /** Modelo Educativo › Idiomas. @param Router $router @return void */
    public static function idiomas(Router $router) {
        $router->render('estaticas/modelo-educativo/idiomas', ['titulo' => 'Idiomas']);
    }

    // ---- NIVELES ACADÉMICOS ----
    /**
     * Índice de Niveles Académicos.
     *
     * Única página estática que declara SEO propio en vez de solo $titulo, y la que marca
     * la pauta para las nuevas.
     *
     * @param  Router $router
     * @return void
     */
    public static function niveles(Router $router) {
        $seo_titulo = 'Niveles Académicos | Colegio Bilbao';
        $seo_descripcion = 'Preescolar, Primaria, Secundaria y Preparatoria. Conoce la propuesta educativa del Colegio Bilbao para cada etapa.';
        $router->render('estaticas/niveles', compact('seo_titulo', 'seo_descripcion'));
    }

    /** Niveles › Preescolar (Maternal y Kinder). @param Router $router @return void */
    public static function preescolar(Router $router) {
        $router->render('estaticas/niveles-academicos/preescolar', ['titulo' => 'Preescolar']);
    }

    /** Niveles › Primaria. @param Router $router @return void */
    public static function primaria(Router $router) {
        $router->render('estaticas/niveles-academicos/primaria', ['titulo' => 'Primaria']);
    }

    /** Niveles › Secundaria. @param Router $router @return void */
    public static function secundaria(Router $router) {
        $router->render('estaticas/niveles-academicos/secundaria', ['titulo' => 'Secundaria']);
    }

    /** Niveles › Preparatoria (Bachillerato). @param Router $router @return void */
    public static function preparatoria(Router $router) {
        $router->render('estaticas/niveles-academicos/preparatoria', ['titulo' => 'Preparatoria']);
    }

    // ---- VIDA ESCOLAR ----
    /** Vida Escolar › Afterschool y Extracurriculares. @param Router $router @return void */
    public static function afterschool(Router $router) {
        $router->render('estaticas/vida-escolar/afterschool-extracurriculares', ['titulo' => 'Afterschool y Extracurriculares']);
    }

    /** Vida Escolar › Cuidado y Bienestar. @param Router $router @return void */
    public static function cuidadobienestar(Router $router) {
        $router->render('estaticas/vida-escolar/cuidado-y-bienestar', ['titulo' => 'Cuidado y Bienestar']);
    }

    /** Vida Escolar › Eventos y Tradiciones. @param Router $router @return void */
    public static function eventostradicones(Router $router) {
        $router->render('estaticas/vida-escolar/eventos-y-tradiciones', ['titulo' => 'Eventos y Tradiciones']);
    }

    /** Vida Escolar › Futuro Universitario y Becas. @param Router $router @return void */
    public static function futurouniversitario(Router $router) {
        $router->render('estaticas/vida-escolar/futuro-universitario-becas', ['titulo' => 'Futuro Universitario y Becas']);
    }

    /** Vida Escolar › Programa Dual. @param Router $router @return void */
    public static function programadual(Router $router) {
        $router->render('estaticas/vida-escolar/programa-dual', ['titulo' => 'Programa Dual']);
    }

    /** Vida Escolar › Servicios para Familias. @param Router $router @return void */
    public static function serviciofamilias(Router $router) {
        $router->render('estaticas/vida-escolar/servicios-para-familias', ['titulo' => 'Servicios para Familias']);
    }

    // ---- ADMISIONES ----
    /**
     * Portada de Admisiones (ruta /admisiones).
     *
     * ⚠️ El nombre genérico `inicio()` es histórico y no dice a qué sección pertenece.
     *
     * @param  Router $router
     * @return void
     */
    public static function inicio(Router $router) {
        $router->render('estaticas/admisiones/inicio', ['titulo' => 'Admisiones']);
    }

    /** Admisiones › Proceso de Admisión. @param Router $router @return void */
    public static function proceso(Router $router) {
        $router->render('estaticas/admisiones/proceso', ['titulo' => 'Proceso de Admisión']);
    }

    /** Admisiones › Preguntas Frecuentes. @param Router $router @return void */
    public static function preguntasfrecuentes(Router $router) {
        $router->render('estaticas/admisiones/preguntas-frecuentes', ['titulo' => 'Preguntas Frecuentes']);
    }

    /** Admisiones › Convenios. @param Router $router @return void */
    public static function convenios(Router $router) {
        $router->render('estaticas/admisiones/convenios', ['titulo' => 'Convenios']);
    }

    /** Admisiones › Convocatoria de Becas. @param Router $router @return void */
    public static function convocatoriabecas(Router $router) {
        $router->render('estaticas/admisiones/convocatoria-becas', ['titulo' => 'Convocatoria de Becas']);
    }

    /**
     * Contacto de Admisiones.
     *
     * ⚠️ index.php la registra en DOS rutas: /admisiones/contacto y /contacto. La segunda
     * hace que el «Contacto» del menú principal sirva la página de Admisiones.
     *
     * @param  Router $router
     * @return void
     */
    public static function contacto(Router $router) {
        $router->render('estaticas/admisiones/contacto', ['titulo' => 'Contacto Admisiones']);
    }

    // ---- COMUNIDAD ----
    /** Three.js para los fondos animados de Comunidad (GSAP ya es global en header.php). */
    private static function comunidadThree(): string {
        return three_js_tag();
    }

    /**
     * Eventos de una audiencia, en el formato plano que consumen las vistas públicas.
     * El nivel viaja para poder mostrarlo y filtrar en cliente.
     *
     * Aplana los objetos Evento a arrays porque el calendario de cliente los recibe como
     * isla JSON: no tiene sentido exponerle el modelo entero.
     *
     * @param  string $audiencia 'familias' o 'estudiantes'. Los 'interno' nunca salen del panel.
     * @return array<int, array{fecha:string, fecha_fin:?string, tipo:string, titulo:string,
     *                          desc:string, niveles:array<int,string>, alcance:string,
     *                          icono:string, color:string, etiqueta:string}>
     */
    private static function eventosPublicos(string $audiencia): array {
        return self::aplanarEventos(Evento::porAudiencia($audiencia));
    }

    /**
     * Objetos Evento → arrays planos para el cliente y para el PDF.
     *
     * `icono`, `color` y `etiqueta` se resuelven **aquí** y no en el JS: el icono por
     * defecto depende del tipo y el color tiene que coincidir con el del panel y el
     * del papel. Replicar esas tres tablas en el cliente era la vía directa a que la
     * web y el PDF pintaran el mismo evento de distinto color.
     *
     * @param  \Model\Evento[] $eventos
     * @return array<int, array<string, mixed>>
     */
    private static function aplanarEventos(array $eventos): array {
        $out = [];
        foreach ($eventos as $ev) {
            $out[] = [
                'fecha'    => $ev->fecha,
                'fecha_fin'=> $ev->fecha_fin,
                'tipo'     => $ev->tipo,
                'titulo'   => $ev->titulo,
                'desc'     => $ev->descripcion ?? '',
                'niveles'  => $ev->nivelesLista(),
                'alcance'  => $ev->alcance(),
                'icono'    => $ev->icono(),
                'color'    => $ev->color(),
                'etiqueta' => Evento::TIPO_LABEL_PUBLICO[$ev->tipo] ?? $ev->tipo,
            ];
        }
        return $out;
    }

    /**
     * Comunidad › Estudiantes. Embeds oficiales de Instagram + calendario de su audiencia.
     *
     * @param  Router $router
     * @return void
     */
    public static function estudiantes(Router $router) {
        $router->render('estaticas/comunidad/estudiantes', [
            'titulo'          => 'Estudiantes',
            'seo_titulo'      => 'Comunidad estudiantil',
            'seo_descripcion' => 'Proyectos, deportes, arte y la vida diaria de los estudiantes del Colegio Bilbao.',
            'extra_head'      => self::comunidadThree(),
            'eventosCal'      => self::eventosPublicos('estudiantes'),
        ]);
    }

    /**
     * Comunidad › Familias. Avisos y calendario escolar interactivo.
     *
     * El calendario se alimenta de los eventos reales con audiencia 'familias', que se
     * crean en el módulo Eventos del panel.
     *
     * Se le pasan **todos** los eventos de la audiencia, no solo los del ciclo: el
     * navegador de meses no tiene tope y un evento de julio de 2028 debe aparecer si
     * alguien llega hasta ahí. El ciclo solo acota la vista de «curso completo» y el
     * PDF, que sí son una ventana concreta.
     *
     * @param  Router $router
     * @return void
     */
    public static function familias(Router $router) {
        // Eventos reales para el calendario interactivo (reemplaza el array hardcodeado)
        $eventos = self::eventosPublicos('familias');
        $router->render('estaticas/comunidad/familias', [
            'titulo'          => 'Familias',
            'seo_titulo'      => 'Familias Bilbao',
            'seo_descripcion' => 'Avisos y calendario escolar del Colegio Bilbao para nuestras familias.',
            'extra_head'      => self::comunidadThree(),
            'eventosCal'      => $eventos,
            'ciclo'           => Evento::ciclo(),
            'mesesCiclo'      => Evento::mesesCiclo(),
            // El botón de descarga solo existe si el módulo Eventos lo habilitó.
            'calendarioPdf'   => Ajuste::bool(Ajuste::CALENDARIO_PDF, false),
        ]);
    }

    /**
     * El calendario del ciclo en PDF, desde la web pública.
     *
     * ⚠️ **El interruptor es el guard, no una decoración del botón.** Con el ajuste
     * apagado esta ruta redirige: si solo escondiera el enlace, la URL seguiría
     * sirviendo el documento a cualquiera que la conociese, y el sentido del
     * interruptor es justamente decidir si el colegio publica ya su calendario.
     *
     * `?niveles=` acota igual que los chips de la página, para que lo que se descarga
     * sea lo que se está mirando. Se filtra contra `Materia::NIVELES`: es un valor de
     * la query que acaba en el documento.
     *
     * @param  Router $router
     * @return void
     */
    public static function calendarioFamiliasPdf(Router $router) {
        if (!Ajuste::bool(Ajuste::CALENDARIO_PDF, false)) {
            header('Location: /comunidad/familias');
            exit;
        }

        $ciclo = Evento::ciclo();
        // `?niveles[]=x` llegaría como array y `(string)` lo convertiría en el literal
        // "Array" con un warning; se descarta lo que no sea cadena antes de trocear.
        $crudo   = $_GET['niveles'] ?? '';
        $niveles = array_values(array_intersect(
            Materia::NIVELES,
            array_filter(array_map('trim', explode(',', is_string($crudo) ? $crudo : '')))
        ));

        $eventos = self::aplanarEventos(
            Evento::porAudienciaEnRango('familias', $ciclo['ini'], $ciclo['fin'])
        );
        // Un evento sin niveles es de todo el colegio: entra siempre, se filtre lo que
        // se filtre. Los cinco marcados equivalen a ninguno (Evento::normalizarNiveles()).
        if ($niveles && count($niveles) < count(Materia::NIVELES)) {
            $eventos = array_values(array_filter($eventos, static function (array $e) use ($niveles) {
                return !$e['niveles'] || array_intersect($e['niveles'], $niveles);
            }));
        } else {
            $niveles = [];
        }

        ob_start();
        $pdfCss     = Pdf::hojaCss('calendario-pdf.css');
        $logoData   = Pdf::logo();
        $mesesCiclo = Evento::mesesCiclo();
        require __DIR__ . '/../views/estaticas/comunidad/calendario-pdf.php';
        $html = ob_get_clean();

        $sufijo = $niveles ? '-' . Pdf::slug(implode('-', $niveles)) : '';
        Pdf::emitir($html, 'calendario-' . $ciclo['anio_ini'] . '-' . $ciclo['anio_fin'] . $sufijo . '.pdf', 'landscape');
    }

    /**
     * Comunidad › Colaboradores: pantalla única a 100vh que enlaza a /login.
     *
     * Es la puerta de entrada pública a la intranet.
     *
     * @param  Router $router
     * @return void
     */
    public static function colaboradores(Router $router) {
        $router->render('estaticas/comunidad/colaboradores', [
            'titulo'          => 'Colaboradores',
            'seo_titulo'      => 'Portal de Colaboradores',
            'seo_descripcion' => 'Acceso al portal interno para el personal y colaboradores del Colegio Bilbao.',
            'extra_head'      => self::comunidadThree(),
        ]);
    }

    // ---- VOCES BILBAO ----
    /**
     * Listado público de noticias.
     *
     * Elige la noticia de portada con esta prioridad: la marcada como `destacada` y, si no
     * hay ninguna, la primera del listado — así la portada nunca queda vacía. El resto se
     * ordena por fecha de publicación descendente.
     *
     * @param  Router $router
     * @return void
     */
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

        $extra_head = three_js_tag();
        $router->render('noticias/index', [
            'seo_titulo'      => 'Noticias · Colegio Bilbao',
            'seo_descripcion' => 'Mantente al día con todo lo que pasa en el Colegio Bilbao: logros académicos, eventos culturales, deportes y vida escolar.',
            'extra_head'      => $extra_head,
            'featured'        => $featured,
            'noticias'        => $listado,
            'categorias'      => Noticia::categorias(),
        ]);
    }

    /**
     * Ficha pública de una noticia, resuelta por slug.
     *
     * Es el único método del controlador que recibe un parámetro de la URI: lo alimenta
     * la ruta con patrón `/noticias/{slug}` de index.php. Sin slug o sin coincidencia,
     * redirige al listado en vez de mostrar un 404.
     *
     * @param  Router $router Espera $router->params['slug'].
     * @return void
     */
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

    /**
     * Voces Bilbao › Entrevistas.
     *
     * ⚠️ SIN RUTA. index.php no registra ninguna URI hacia este método desde que la
     * sección /voces-bilbao/* se reorganizó en /noticias y /blog. Es código muerto:
     * o se le devuelve la ruta, o se elimina junto con su vista.
     *
     * @param  Router $router
     * @return void
     */
    public static function entrevistas(Router $router) {
        $router->render('estaticas/voces-bilbao/entrevistas', ['titulo' => 'Entrevistas']);
    }

    /**
     * Voces Bilbao › Artículos.
     *
     * ⚠️ SIN RUTA, igual que entrevistas(). El listado de artículos vivo es
     * BlogController::blogPublico() en /blog.
     *
     * @param  Router $router
     * @return void
     */
    public static function articulos(Router $router) {
        $router->render('estaticas/voces-bilbao/articulos', ['titulo' => 'Artículos']);
    }

    /**
     * Voces Bilbao › Testimonios.
     *
     * ⚠️ SIN RUTA, igual que entrevistas(). Los testimoniales aprobados se muestran hoy
     * en la portada.
     *
     * @param  Router $router
     * @return void
     */
    public static function testimonios(Router $router) {
        $router->render('estaticas/voces-bilbao/testimonios', ['titulo' => 'Testimonios']);
    }

    // ---- CONTACTO ----
    /** Contacto › Directorio de áreas y extensiones. @param Router $router @return void */
    public static function directorio(Router $router) {
        $router->render('estaticas/contacto/directorio', ['titulo' => 'Directorio']);
    }

    /** Contacto › Cultura y Talento (bolsa de trabajo). @param Router $router @return void */
    public static function culturatalento(Router $router) {
        $router->render('estaticas/contacto/cultura-y-talento', ['titulo' => 'Cultura y Talento']);
    }

    /** Contacto › Proveedores. @param Router $router @return void */
    public static function proveedores(Router $router) {
        $router->render('estaticas/contacto/proveedores', ['titulo' => 'Proveedores']);
    }

    // ---- LEGAL / UTILIDAD ----
    /** Aviso de Privacidad. @param Router $router @return void */
    public static function avisoprivacidad(Router $router) {
        $router->render('estaticas/aviso-privacidad/aviso-privacidad', ['titulo' => 'Aviso de Privacidad']);
    }

    /** Términos y Condiciones. @param Router $router @return void */
    public static function terminoscondiciones(Router $router) {
        $router->render('estaticas/terminos-y-condiciones/terminos-y-condiciones', ['titulo' => 'Términos y Condiciones']);
    }

    /** Mapa del Sitio. @param Router $router @return void */
    public static function mapadelsitio(Router $router) {
        $router->render('estaticas/mapa-del-sitio/mapa-del-sitio', ['titulo' => 'Mapa del Sitio']);
    }

    /**
     * Formulario público para que una familia deje su testimonio (GET pinta, POST guarda).
     *
     * El testimonio nace con `aprobado = 0` y NO se publica hasta que un revisor lo apruebe
     * desde Redacción › Testimoniales. La entrada se limpia con strip_tags() antes de
     * validarla.
     *
     * Tras un envío correcto se vacía $datos para que el formulario no repinte lo enviado;
     * si hay errores, $datos los conserva para no obligar a reescribirlo todo.
     *
     * ⚠️ Este método incorpora un bloque <style> en $extra_head, en contra de la regla del
     * proyecto («nada de CSS embebido»). Es la última vista sin migrar a
     * src/scss/publico/: al tocarla, mover esos estilos a su partial con el envoltorio
     * body[data-page="..."].
     *
     * @param  Router $router
     * @return void
     */
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
