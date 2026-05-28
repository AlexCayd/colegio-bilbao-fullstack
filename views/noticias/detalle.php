<?php
/*
 * Vista: detalle de noticia
 * Variables recibidas del controller: $titulo, $noticia (Noticia), $relacionadas (Noticia[])
 */

if (!function_exists('noticia_fecha_larga')) {
    function noticia_fecha_larga(?string $fecha): string {
        if (empty($fecha) || $fecha === '0000-00-00' || $fecha === '0000-00-00 00:00:00') {
            return '';
        }
        $meses = ['','enero','febrero','marzo','abril','mayo','junio',
                  'julio','agosto','septiembre','octubre','noviembre','diciembre'];
        $ts = strtotime($fecha);
        if (!$ts || $ts < 0) return '';
        return date('j', $ts) . ' de ' . $meses[(int)date('n', $ts)] . ' de ' . date('Y', $ts);
    }
}

/* ── Variables calculadas ── */
$autor_siglas = 'CB';
if (!empty($noticia->autor_nombre)) {
    $partes = explode(' ', trim($noticia->autor_nombre));
    $autor_siglas = mb_strtoupper(
        mb_substr($partes[0], 0, 1) . mb_substr($partes[1] ?? '', 0, 1)
    );
}

$has_portada  = !empty($noticia->portada);
$fecha_mostrar = $noticia->fecha_publicacion ?: ($noticia->creado_en ?? '');
$wa_text     = urlencode('Leí esta noticia del Colegio Bilbao: '
    . ($_SERVER['HTTP_HOST'] ?? 'colegiobilbao.mx')
    . ($_SERVER['REQUEST_URI'] ?? ''));

$svg_link = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>';
$svg_wa   = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M11.999 0C5.373 0 0 5.373 0 12c0 2.117.553 4.103 1.523 5.833L.052 23.999l6.304-1.454A11.945 11.945 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.818a9.818 9.818 0 0 1-5.006-1.369l-.359-.214-3.721.859.936-3.612-.234-.371A9.818 9.818 0 1 1 12 21.818z"/></svg>';
?>
<!DOCTYPE html>
<html lang="es-MX">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($titulo ?? ($noticia->titulo . ' · Colegio Bilbao')) ?></title>
    <meta name="description" content="<?= htmlspecialchars($noticia->extracto ?? '') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;700;900&display=swap" rel="stylesheet">
    <link rel="shortcut icon" href="/build/assets/img/global/favicon.png" type="image/png">
    <link rel="stylesheet" href="/build/css/app.css">
    <meta name="view-transition" content="same-origin">
</head>
<body>

    <div class="read-progress-bar" id="readProgress" role="progressbar" aria-hidden="true"></div>

    <!-- HEADER -->
    <header class="header-bar">
        <div class="header-inner">
            <a href="/" class="logo-link">
                <img src="/build/assets/img/global/logo-bilbao-horizontal-azul.png" alt="Colegio Bilbao" class="logo-img" loading="lazy">
            </a>
            <div class="header-controls">
                <div class="lang-switch"><span class="active">ES</span> | <a href="/en/">EN</a></div>
                <button class="menu-trigger" aria-label="Abrir menú">
                    <div class="hamburger-icon"><span></span><span></span><span></span></div>
                </button>
            </div>
        </div>
    </header>

    <!-- OVERLAY MENU -->
    <div id="menu-overlay" class="menu-overlay" role="dialog" aria-modal="true" aria-hidden="true" aria-label="Menú principal">
        <div class="overlay-header">
            <div class="header-inner">
                <a href="/" class="logo-link"><img src="/build/assets/img/global/logo-bilbao-horizontal-azul.png" loading="lazy" alt="Colegio Bilbao" class="logo-img"></a>
                <div class="header-controls">
                    <div class="lang-switch"><span class="active">ES</span> | <a href="/en/">EN</a></div>
                    <button id="close-menu-btn" class="close-btn" aria-label="Cerrar menú"></button>
                </div>
            </div>
        </div>
        <nav class="overlay-content">
            <ul id="primary-nav">
                <li class="nav-accordion-item"><button class="nav-accordion-trigger">Conócenos <span class="chevron">▼</span></button><ul class="nav-submenu"><li><a href="/conocenos/quienes-somos/">Quiénes somos</a></li><li><a href="/conocenos/equipo-educativo/">Equipo educativo</a></li><li><a href="/conocenos/instalaciones/">Instalaciones</a></li><li><a href="/conocenos/certificaciones-y-reconocimientos/">Certificaciones y reconocimientos</a></li></ul></li>
                <li class="nav-accordion-item"><button class="nav-accordion-trigger">Modelo educativo <span class="chevron">▼</span></button><ul class="nav-submenu"><li><a href="/modelo-educativo/modelo-vida/">Modelo VIDA</a></li><li><a href="/modelo-educativo/filosofia-y-metodologia/">Filosofía</a></li><li><a href="/modelo-educativo/aprendizaje-integral/">Aprendizaje integral</a></li><li><a href="/modelo-educativo/idiomas/">Idiomas</a></li></ul></li>
                <li class="nav-accordion-item"><button class="nav-accordion-trigger">Niveles académicos <span class="chevron">▼</span></button><ul class="nav-submenu"><li><a href="/niveles-academicos/preescolar/">Preescolar</a></li><li><a href="/niveles-academicos/primaria/">Primaria</a></li><li><a href="/niveles-academicos/secundaria/">Secundaria</a></li><li><a href="/niveles-academicos/preparatoria/">Preparatoria</a></li></ul></li>
                <li class="nav-accordion-item"><button class="nav-accordion-trigger">Vida escolar <span class="chevron">▼</span></button><ul class="nav-submenu"><li><a href="/vida-escolar/afterschool-extracurriculares/">Afterschool</a></li><li><a href="/vida-escolar/futuro-universitario-becas/">Futuro universitario</a></li><li><a href="/vida-escolar/programa-dual/">Programa Dual</a></li><li><a href="/vida-escolar/servicios-para-familias/">Servicios</a></li><li><a href="/vida-escolar/cuidado-y-bienestar/">Cuidado y bienestar</a></li><li><a href="/vida-escolar/eventos-y-tradiciones/">Eventos</a></li></ul></li>
                <li class="nav-accordion-item"><button class="nav-accordion-trigger">Admisiones <span class="chevron">▼</span></button><ul class="nav-submenu"><li><a href="/admisiones/">Inicio</a></li><li><a href="/admisiones/proceso/">Proceso</a></li><li><a href="/admisiones/preguntas-frecuentes/">FAQ</a></li><li><a href="/admisiones/convenios/">Convenios</a></li><li><a href="/admisiones/convocatoria-becas/">Becas</a></li><li><a href="/admisiones/contacto/">Contacto</a></li></ul></li>
                <li class="nav-accordion-item"><button class="nav-accordion-trigger">Comunidad <span class="chevron">▼</span></button><ul class="nav-submenu"><li><a href="/comunidad/estudiantes/">Estudiantes</a></li><li><a href="/comunidad/familias/">Familias</a></li><li><a href="/comunidad/exalumnos/">Exalumnos</a></li></ul></li>
                <li class="nav-accordion-item"><button class="nav-accordion-trigger">Voces Bilbao <span class="chevron">▼</span></button><ul class="nav-submenu"><li><a href="/noticias/" class="active">Noticias</a></li><li><a href="/voces-bilbao/entrevistas/">Entrevistas</a></li><li><a href="/blog">Artículos</a></li><li><a href="/voces-bilbao/testimonios/">Testimonios</a></li></ul></li>
                <li class="nav-accordion-item"><button class="nav-accordion-trigger">Contacto <span class="chevron">▼</span></button><ul class="nav-submenu"><li><a href="/contacto/">Contacto</a></li><li><a href="/contacto/directorio/">Directorio</a></li><li><a href="/contacto/cultura-y-talento/">Cultura y talento</a></li><li><a href="/contacto/proveedores/">Proveedores</a></li></ul></li>
            </ul>
        </nav>
    </div>

    <main class="noticia-detalle">

        <!-- ══════════════════════════
             HERO — portada full-width
        ══════════════════════════ -->
        <section class="nd-hero-portada<?= $has_portada ? '' : ' nd-hero-portada--empty' ?>">
            <img src="<?= $has_portada ? htmlspecialchars($noticia->portada) : '/build/assets/img/blog/blog-placeholder.png' ?>"
                 alt="<?= $has_portada ? htmlspecialchars($noticia->portada_alt ?? $noticia->titulo) : '' ?>"
                 class="nd-hero-portada-img<?= $has_portada ? '' : ' nd-hero-portada-img--placeholder' ?>"
                 loading="eager"
                 style="view-transition-name: noticia-img-<?= htmlspecialchars($noticia->slug) ?>">
            <div class="nd-hero-overlay"></div>
            <div class="nd-hero-content">
                <div class="container">
                <div class="nd-hero-glass-card">
                    <div class="nd-hero-meta">
                        <a href="/noticias/?cat=<?= htmlspecialchars($noticia->categoria_slug ?? '') ?>"
                           class="cat-chip cat-<?= htmlspecialchars($noticia->categoria_slug ?? '') ?> cat-chip--white">
                            <?= htmlspecialchars($noticia->categoria_nombre ?? '') ?>
                        </a>
                        <?php if ($noticia->destacada): ?>
                        <span class="nueva-badge">Destacada</span>
                        <?php endif; ?>
                        <span class="nd-hero-date"><?= noticia_fecha_larga($fecha_mostrar) ?></span>
                    </div>

                    <h1 class="nd-hero-title"><?= htmlspecialchars($noticia->titulo) ?></h1>
                    <p class="nd-hero-lead"><?= htmlspecialchars($noticia->extracto ?? '') ?></p>

                    <div class="nd-hero-byline">
                        <div class="nd-author-wrap">
                            <div class="nd-author-avatar">
                                <?php if (!empty($noticia->autor_avatar)): ?>
                                <img src="<?= htmlspecialchars($noticia->autor_avatar) ?>"
                                     alt="<?= htmlspecialchars($noticia->autor_nombre ?? '') ?>">
                                <?php else: ?>
                                <?= htmlspecialchars($autor_siglas) ?>
                                <?php endif; ?>
                            </div>
                            <div class="nd-author-meta">
                                <strong><?= htmlspecialchars($noticia->autor_nombre ?? 'Colegio Bilbao') ?></strong>
                                <span>
                                    <?= noticia_fecha_larga($fecha_mostrar) ?>
                                    <?php if ($noticia->tiempo_lectura): ?>
                                    <span class="nd-sep">·</span>
                                    <?= (int)$noticia->tiempo_lectura ?> min de lectura
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                        <div class="nd-hero-share">
                            <button class="nd-share-btn" id="copyLinkHero" type="button">
                                <?= $svg_link ?> Copiar enlace
                            </button>
                            <a href="https://wa.me/?text=<?= $wa_text ?>" target="_blank" rel="noopener noreferrer"
                               class="nd-share-btn nd-share-btn--wa">
                                <?= $svg_wa ?> Compartir
                            </a>
                        </div>
                    </div>
                </div>
                </div>
            </div>
        </section>

        <!-- ══════════════════════════
             BREADCRUMB
        ══════════════════════════ -->
        <nav class="noticia-detalle-nav" aria-label="Regresar">
            <div class="container">
                <a href="/noticias/">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="15 18 9 12 15 6"/></svg>
                    Volver a noticias
                </a>
                <span class="nd-breadcrumb-divider" aria-hidden="true">/</span>
                <span class="nd-breadcrumb-current"><?= htmlspecialchars($noticia->categoria_nombre ?? '') ?></span>
            </div>
        </nav>

        <!-- ══════════════════════════════════════════════════════
             ARTICLE BODY
        ══════════════════════════════════════════════════════ -->
        <div id="articleBody">

            <!-- Alex te lo resume -->
            <?php if (!empty($noticia->extracto)): ?>
            <div class="alex-summary">
                <div class="alex-summary-inner">
                    <div class="alex-summary-mascot-wrap" aria-hidden="true">
                        <img src="/build/assets/img/niveles-academicos/preescolar/bby-alex-saluda.png" alt="">
                        <span>Alex</span>
                    </div>
                    <div class="alex-summary-bubble">
                        <span class="summary-label">En resumen</span>
                        <p><?= htmlspecialchars($noticia->extracto) ?></p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Contenido -->
            <?php
            $contenidoRaw = $noticia->contenido ?? '';
            // Drop cap requires the first child to be a <p>. If content starts with raw
            // text or a <div> (contenteditable default), wrap it so ::first-letter fires.
            if (!empty($contenidoRaw) && !preg_match('/^\s*<(p|h[1-6]|div|ul|ol|blockquote|figure)/i', $contenidoRaw)) {
                $contenidoRaw = '<p>' . $contenidoRaw . '</p>';
            }
            ?>
            <article class="noticia-detalle-body noticia-detalle-body--lead">
                <?= $contenidoRaw ?>
            </article>

        </div><!-- /#articleBody -->

        <!-- ══════════════════════════
             NOTICIAS RELACIONADAS
        ══════════════════════════ -->
        <?php if (!empty($relacionadas)): ?>
        <section class="noticia-relacionadas">
            <div class="container">
                <div class="noticias-section-head">
                    <h2 class="noticias-section-title">También en Bilbao</h2>
                    <p class="noticias-section-copy">Más historias de nuestra comunidad escolar.</p>
                </div>
                <div class="noticia-relacionadas-grid">
                    <?php
                    $meses_corto = ['','ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic'];
                    foreach ($relacionadas as $r):
                        $rslug     = htmlspecialchars($r->slug);
                        $rcat      = htmlspecialchars($r->categoria_slug ?? '');
                        $r_has_img = !empty($r->portada);
                        $r_ts      = !empty($r->fecha_publicacion) ? strtotime($r->fecha_publicacion) : null;
                    ?>
                    <article class="noticia-card">
                        <a href="/noticias/<?= $rslug ?>"
                           class="noticia-card-img"
                           tabindex="-1" aria-hidden="true">
                            <img src="<?= $r_has_img ? htmlspecialchars($r->portada) : '/build/assets/img/blog/blog-placeholder.png' ?>"
                                 alt="" loading="lazy">
                            <span class="cat-chip cat-<?= $rcat ?><?= $r_has_img ? ' cat-chip--glass' : '' ?>">
                                <?= htmlspecialchars($r->categoria_nombre ?? '') ?>
                            </span>
                        </a>
                        <div class="noticia-card-body">
                            <div class="noticia-card-meta">
                                <?php if ($r_ts): ?>
                                <time class="noticia-date" datetime="<?= htmlspecialchars($r->fecha_publicacion) ?>">
                                    <?= date('j', $r_ts) . ' ' . $meses_corto[(int)date('n', $r_ts)] ?>
                                </time>
                                <?php endif; ?>
                            </div>
                            <h3 class="noticia-card-title">
                                <a href="/noticias/<?= $rslug ?>"><?= htmlspecialchars($r->titulo) ?></a>
                            </h3>
                            <a href="/noticias/<?= $rslug ?>" class="noticia-read-more">
                                Leer noticia
                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                            </a>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- CTA -->
        <section class="noticias-cta" style="margin-top:64px;">
            <div class="container">
                <div class="noticias-cta-box">
                    <h3>¿Quieres ser parte de esta historia?</h3>
                    <p>Estas noticias son el reflejo de un proyecto educativo vivo. Visita el Colegio Bilbao y descubre cómo formamos personas para el mundo.</p>
                    <a href="https://wa.me/525614612682?text=Hola,%20quiero%20agendar%20una%20visita%20guiada%20al%20Colegio%20Bilbao."
                       class="btn-terciario" target="_blank" rel="noopener noreferrer">
                        Agenda tu visita
                    </a>
                </div>
            </div>
        </section>

    </main>

    <footer class="footer-simple">
        <p>© 2026 Colegio Bilbao. Todos los derechos reservados. · <a href="/aviso-privacidad/">Aviso de privacidad</a></p>
    </footer>

    <script src="/build/js/bundle.min.js" defer></script>
    <script>
    (function () {
        var bar    = document.getElementById('readProgress');
        var bodyEl = document.getElementById('articleBody');

        function onScroll() {
            if (!bar || !bodyEl) return;
            var st         = window.pageYOffset;
            var top        = bodyEl.getBoundingClientRect().top + st;
            var h          = bodyEl.offsetHeight;
            var wh         = window.innerHeight;
            var scrollable = h - wh;
            var p          = scrollable > 0 ? (st - top) / scrollable : (st >= top ? 1 : 0);
            bar.style.width = (Math.max(0, Math.min(1, p)) * 100).toFixed(1) + '%';
        }

        window.addEventListener('scroll', onScroll, { passive: true });
        onScroll();

        function initCopy(id) {
            var el = document.getElementById(id);
            if (!el) return;
            el.addEventListener('click', function () {
                var orig = el.innerHTML;
                el.innerHTML = '¡Copiado!';
                setTimeout(function () { el.innerHTML = orig; }, 2200);
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(window.location.href);
                } else {
                    var ta = document.createElement('textarea');
                    ta.value = window.location.href;
                    Object.assign(ta.style, { position: 'fixed', opacity: '0' });
                    document.body.appendChild(ta);
                    ta.select();
                    document.execCommand('copy');
                    document.body.removeChild(ta);
                }
            });
        }
        initCopy('copyLinkHero');
    })();
    </script>
</body>
</html>
