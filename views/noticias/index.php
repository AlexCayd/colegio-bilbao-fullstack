<?php $paginaVista = 'noticias-index'; ?>
<?php
/* ── helpers ─────────────────────────────────────────── */
function noticia_mes_corto(?string $fecha): string {
    if (empty($fecha) || $fecha === '0000-00-00') return '';
    $ts = strtotime($fecha);
    if (!$ts || $ts < 0) return '';
    $meses = ['', 'ene', 'feb', 'mar', 'abr', 'may', 'jun',
              'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    return $meses[(int)date('n', $ts)];
}

function noticia_fecha_larga(?string $fecha): string {
    if (empty($fecha) || $fecha === '0000-00-00' || $fecha === '0000-00-00 00:00:00') return '';
    $meses = ['', 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
              'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    $ts = strtotime($fecha);
    if (!$ts || $ts < 0) return '';
    return date('j', $ts) . ' de ' . $meses[(int)date('n', $ts)] . ' de ' . date('Y', $ts);
}

function noticia_hace_cuando(?string $fecha): string {
    if (empty($fecha) || $fecha === '0000-00-00') return '';
    $ts = strtotime($fecha);
    if (!$ts || $ts < 0) return '';
    $diff = (int)(new DateTime('today'))->diff(new DateTime($fecha))->days;
    if ($diff === 0)  return 'Hoy';
    if ($diff === 1)  return 'Ayer';
    if ($diff < 7)   return "Hace {$diff} días";
    if ($diff < 14)  return 'Hace 1 semana';
    if ($diff < 30)  return 'Hace ' . floor($diff / 7) . ' semanas';
    return noticia_mes_corto($fecha) . ' ' . date('Y', $ts);
}

/* ── ticker generado desde datos de BD ───────────────── */
$all_para_ticker = array_merge(
    $featured ? [$featured] : [],
    $noticias ?? []
);
$ticker_items = array_map(fn($n) => [
    'cat'   => $n->categoria_nombre ?? '',
    'texto' => $n->titulo,
    'slug'  => $n->slug ?? '',
], array_slice($all_para_ticker, 0, 8));
?>
    <!-- ── SUBNAV ───────────────────────────────────────────── -->
    <nav class="noticias-subnav" data-i18n-attr="aria-label:noticias.subnav.ariaLabel" aria-label="Voces Bilbao">
        <div class="container">
            <a href="/noticias" class="noticias-subnav-brand">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2Zm0 0a2 2 0 0 1-2-2v-9c0-1.1.9-2 2-2h2"/><path d="M18 14h-8"/><path d="M15 18h-5"/><path d="M10 6h8v4h-8z"/></svg>
                <span data-i18n="noticias.subnav.brand">Voces Bilbao</span>
                <span class="noticias-subnav-sep">/</span>
                <span class="noticias-subnav-section" data-i18n="noticias.subnav.brandSection">Noticias</span>
            </a>
            <div class="noticias-subnav-links">
                <a href="/noticias" class="noticias-subnav-link active">
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                    <span data-i18n="noticias.subnav.linkNoticias">Noticias</span>
                </a>
                <a href="/blog" class="noticias-subnav-link">
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                    <span data-i18n="noticias.subnav.linkArticulos">Artículos</span>
                </a>
                <a href="/admisiones/" class="noticias-subnav-link">
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                    <span data-i18n="noticias.subnav.linkAdmisiones">Admisiones</span>
                </a>
            </div>
            <div class="noticias-subnav-end">
                <span class="noticias-subnav-live" data-i18n="noticias.subnav.enVivo">En vivo</span>
            </div>
        </div>
    </nav>

    <main class="noticias-home">

        <!-- ── HERO ──────────────────────────────────────── -->
        <section class="noticias-hero">
            <canvas id="noticias-hero-bg" aria-hidden="true"></canvas>
            <div class="container">
                <div class="noticias-hero-body fade-up">
                    <div class="noticias-eyebrow">
                        <span class="eyebrow-live" data-i18n="noticias.hero.enVivo">En vivo</span>
                        <span data-i18n="noticias.hero.eyebrowText">Al día con nuestra comunidad</span>
                    </div>
                    <h1 class="noticias-hero-title">
                        <span data-i18n="noticias.hero.tituloLinea1">Noticias</span><br><span data-i18n="noticias.hero.tituloLinea2">Bilbao</span>
                    </h1>
                    <p class="noticias-hero-lead" data-i18n="noticias.hero.lead">
                        Todo lo que pasa en el Colegio Bilbao: logros académicos, eventos culturales, vida deportiva y los momentos que hacen única a nuestra comunidad escolar.
                    </p>
                    <div class="noticias-hero-actions">
                        <a href="#todas-las-noticias" class="btn-primario" data-i18n="noticias.hero.ctaVerNoticias">Ver noticias</a>
                        <a href="/contacto/" class="btn-secundario" data-i18n="noticias.hero.ctaConocer">Conocer el colegio</a>
                    </div>
                </div>
                <picture class="noticias-hero-mascot-wrap">
                    <source srcset="/build/assets/img/alex/alex-periodico.avif" type="image/avif">
                    <source srcset="/build/assets/img/alex/alex-periodico.webp" type="image/webp">
                    <img src="/build/assets/img/alex/alex-periodico.png"
                         class="noticias-hero-mascot"
                         data-i18n-attr="alt:noticias.hero.mascotAlt"
                         alt="Alex reportero de noticias"
                         aria-hidden="true">
                </picture>
            </div>
        </section>

        <!-- ── TICKER — ancho completo ────────────────────── -->
        <?php if (!empty($ticker_items)): ?>
        <div class="news-ticker-bar">
            <div class="news-ticker-inner">
                <div class="ticker-label">
                    <span class="ticker-dot"></span>
                    <span data-i18n="noticias.ticker.label">Últimas noticias</span>
                </div>
                <div class="ticker-track-wrap">
                    <div class="ticker-track">
                        <?php
                        $ticker_doubled = array_merge($ticker_items, $ticker_items);
                        foreach ($ticker_doubled as $t): ?>
                        <a class="ticker-item" href="/noticias/<?= htmlspecialchars($t['slug']) ?>">
                            <span class="ticker-cat"><?= htmlspecialchars($t['cat']) ?></span>
                            <span class="ticker-text"><?= htmlspecialchars($t['texto']) ?></span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- ── NOTICIA DESTACADA ──────────────────────────── -->
        <?php if ($featured): ?>
        <section class="noticias-featured">
            <div class="container">
                <div class="noticias-section-head fade-up">
                    <h2 class="noticias-section-title" data-i18n="noticias.destacada.titulo">Noticia destacada</h2>
                    <p class="noticias-section-copy" data-i18n="noticias.destacada.copy">El acontecimiento más relevante de nuestra comunidad en este momento.</p>
                </div>

                <?php
                $featured_nueva = !empty($featured->fecha_publicacion)
                    && strtotime($featured->fecha_publicacion) >= strtotime('-7 days');
                ?>
                <article class="featured-noticia fade-up">
                    <div class="featured-noticia-bg-decor"></div>
                    <img src="/build/assets/img/alex/alex-lee.png"
                         class="featured-noticia-mascot"
                         alt="" aria-hidden="true">
                    <div class="featured-noticia-content">
                        <div class="featured-noticia-meta">
                            <span class="cat-chip cat-chip--featured">
                                <i class="fa-solid fa-star" aria-hidden="true"></i>
                                <span data-i18n="noticias.destacada.badge">Destacada</span>
                            </span>
                            <?php if ($featured_nueva): ?>
                            <span class="nueva-badge" data-i18n="noticias.tarjeta.nuevoBadge">Nuevo</span>
                            <?php endif; ?>
                            <span class="featured-noticia-date"><?= noticia_fecha_larga($featured->fecha_publicacion ?? '') ?></span>
                        </div>
                        <h3 class="featured-noticia-title"><?= htmlspecialchars($featured->titulo) ?></h3>
                        <p class="featured-noticia-excerpt"><?= htmlspecialchars($featured->extracto ?? '') ?></p>
                        <a href="/noticias/<?= htmlspecialchars($featured->slug) ?>" class="featured-noticia-cta">
                            <span data-i18n="noticias.destacada.ctaLeerCompleta">Leer noticia completa</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                        </a>
                    </div>
                </article>
            </div>
        </section>
        <?php endif; ?>

        <!-- ── LISTADO ────────────────────────────────────── -->
        <section class="noticias-listado" id="todas-las-noticias">
            <div class="container">

                <div class="noticias-section-head fade-up">
                    <h2 class="noticias-section-title" data-i18n="noticias.listado.titulo">Todas las noticias</h2>
                    <p class="noticias-section-copy" data-i18n="noticias.listado.copy">Filtra por área o busca un tema para encontrar lo que te interesa.</p>
                </div>

                <!-- Búsqueda -->
                <div class="noticias-search-wrap fade-up">
                    <div class="noticias-search-input-wrap">
                        <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        <input type="search" id="noticiasSearch" class="noticias-search-input"
                               data-i18n-attr="placeholder:noticias.listado.buscarPlaceholder;aria-label:noticias.listado.buscarAriaLabel"
                               placeholder="Buscar noticias..." autocomplete="off" aria-label="Buscar noticias">
                    </div>
                </div>

                <!-- Filtros -->
                <div class="noticias-filters fade-up" role="group" data-i18n-attr="aria-label:noticias.listado.filtrosAriaLabel" aria-label="Filtrar por categoría">
                    <button class="noticias-filter-btn active" type="button" data-noticia-filter="all" data-i18n="noticias.listado.filtroTodas">Todas</button>
                    <?php foreach ($categorias as $cat): ?>
                    <button class="noticias-filter-btn" type="button" data-noticia-filter="<?= htmlspecialchars($cat['slug']) ?>">
                        <?= htmlspecialchars($cat['nombre']) ?>
                    </button>
                    <?php endforeach; ?>
                </div>

                <!-- Grid -->
                <div class="noticias-grid" id="noticiasGrid">
                    <?php foreach ($noticias as $n):
                        $has_img  = !empty($n->portada);
                        $cat_slug = htmlspecialchars($n->categoria_slug ?? '');
                        $slug     = htmlspecialchars($n->slug);
                        $is_nueva = !empty($n->fecha_publicacion)
                                    && strtotime($n->fecha_publicacion) >= strtotime('-7 days');
                        $partes   = $n->autor_nombre ? explode(' ', trim($n->autor_nombre)) : [];
                        $siglas   = mb_strtoupper(
                            mb_substr($partes[0] ?? 'B', 0, 1) .
                            mb_substr($partes[1] ?? '',  0, 1)
                        );
                    ?>
                    <article class="noticia-card fade-up"
                             data-category="<?= $cat_slug ?>"
                             data-title="<?= htmlspecialchars(mb_strtolower($n->titulo)) ?>"
                             data-excerpt="<?= htmlspecialchars(mb_strtolower($n->extracto ?? '')) ?>">

                        <a href="/noticias/<?= $slug ?>"
                           class="noticia-card-img"
                           tabindex="-1" aria-hidden="true">
                            <?= picture(
                                $has_img ? htmlspecialchars($n->portada) : '/build/assets/img/blog/blog-placeholder.png',
                                '',
                                '',
                                'lazy',
                                ['style' => 'view-transition-name: noticia-img-' . htmlspecialchars($slug)]
                            ) ?>
                            <span class="cat-chip cat-chip--glass cat-<?= $cat_slug ?>">
                                <?= htmlspecialchars($n->categoria_nombre ?? '') ?>
                            </span>
                            <?php if ($is_nueva): ?>
                            <span class="noticia-nueva-dot" data-i18n-attr="aria-label:noticias.tarjeta.nuevaAriaLabel" aria-label="Noticia nueva"></span>
                            <?php endif; ?>
                        </a>

                        <div class="noticia-card-body">
                            <div class="noticia-card-meta">
                                <time class="noticia-date" datetime="<?= htmlspecialchars($n->fecha_publicacion ?? '') ?>">
                                    <?= noticia_fecha_larga($n->fecha_publicacion ?? date('Y-m-d')) ?>
                                </time>
                                <span class="noticia-freshness"><?= noticia_hace_cuando($n->fecha_publicacion ?? date('Y-m-d')) ?></span>
                            </div>
                            <h3 class="noticia-card-title">
                                <a href="/noticias/<?= $slug ?>"><?= htmlspecialchars($n->titulo) ?></a>
                            </h3>
                            <p class="noticia-card-excerpt"><?= htmlspecialchars($n->extracto ?? '') ?></p>
                            <a href="/noticias/<?= $slug ?>" class="noticia-read-more">
                                <span data-i18n="noticias.tarjeta.leerNoticia">Leer noticia</span>
                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                            </a>
                            <?php if ($n->autor_nombre): ?>
                            <div class="noticia-card-author">
                                <div class="noticia-card-author-avatar">
                                    <?php if ($n->autor_avatar): ?>
                                    <img src="<?= htmlspecialchars($n->autor_avatar) ?>" alt="<?= htmlspecialchars($n->autor_nombre) ?>">
                                    <?php else: ?>
                                    <?= htmlspecialchars($siglas) ?>
                                    <?php endif; ?>
                                </div>
                                <span class="noticia-card-author-name"><?= htmlspecialchars($n->autor_nombre) ?></span>
                            </div>
                            <?php endif; ?>
                        </div>

                    </article>
                    <?php endforeach; ?>
                </div>

                <!-- Empty state -->
                <div class="noticias-empty" id="noticiasEmpty" role="status">
                    <img src="/build/assets/img/alex/bby-alex-piensa.png" alt="" aria-hidden="true">
                    <h3 data-i18n="noticias.vacio.titulo">Sin resultados</h3>
                    <p data-i18n="noticias.vacio.copy">Prueba con otro término o selecciona "Todas".</p>
                </div>

            </div>
        </section>

        <!-- ── CTA ────────────────────────────────────────── -->
        <section class="noticias-cta">
            <div class="container">
                <div class="noticias-cta-box fade-up">
                    <h3 data-i18n="noticias.cta.titulo">¿Quieres vivir estas noticias en persona?</h3>
                    <p data-i18n="noticias.cta.copy">
                        Cada evento, logro y celebración sucede dentro de un proyecto educativo pensado para que cada alumno crezca. Agenda una visita y descubre cómo se vive Bilbao.
                    </p>
                    <a href="https://wa.me/525614612682?text=Hola,%20quiero%20agendar%20una%20visita%20guiada%20al%20Colegio%20Bilbao."
                       class="btn-terciario" target="_blank" rel="noopener noreferrer" data-i18n="noticias.cta.boton">
                        Agenda tu visita
                    </a>
                </div>
            </div>
        </section>

    </main>


    <!-- ── Three.js hero background ── -->
