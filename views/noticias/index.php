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
    <nav class="noticias-subnav" aria-label="Voces Bilbao">
        <div class="container">
            <a href="/noticias" class="noticias-subnav-brand">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2Zm0 0a2 2 0 0 1-2-2v-9c0-1.1.9-2 2-2h2"/><path d="M18 14h-8"/><path d="M15 18h-5"/><path d="M10 6h8v4h-8z"/></svg>
                Voces Bilbao
                <span class="noticias-subnav-sep">/</span>
                <span class="noticias-subnav-section">Noticias</span>
            </a>
            <div class="noticias-subnav-links">
                <a href="/noticias" class="noticias-subnav-link active">
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                    Noticias
                </a>
                <a href="/blog" class="noticias-subnav-link">
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                    Artículos
                </a>
                <a href="/admisiones/" class="noticias-subnav-link">
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                    Admisiones
                </a>
            </div>
            <div class="noticias-subnav-end">
                <span class="noticias-subnav-live">En vivo</span>
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
                        <span class="eyebrow-live">En vivo</span>
                        Al día con nuestra comunidad
                    </div>
                    <h1 class="noticias-hero-title">
                        Noticias<br>Bilbao
                    </h1>
                    <p class="noticias-hero-lead">
                        Todo lo que pasa en el Colegio Bilbao: logros académicos, eventos culturales, vida deportiva y los momentos que hacen única a nuestra comunidad escolar.
                    </p>
                    <div class="noticias-hero-actions">
                        <a href="#todas-las-noticias" class="btn-primario">Ver noticias</a>
                        <a href="/contacto/" class="btn-secundario">Conocer el colegio</a>
                    </div>
                </div>
                <picture class="noticias-hero-mascot-wrap">
                    <source srcset="/build/assets/img/alex/alex-periodico.avif" type="image/avif">
                    <source srcset="/build/assets/img/alex/alex-periodico.webp" type="image/webp">
                    <img src="/build/assets/img/alex/alex-periodico.png"
                         class="noticias-hero-mascot"
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
                    Últimas noticias
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
                    <h2 class="noticias-section-title">Noticia destacada</h2>
                    <p class="noticias-section-copy">El acontecimiento más relevante de nuestra comunidad en este momento.</p>
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
                            <span class="cat-chip cat-<?= htmlspecialchars($featured->categoria_slug ?? '') ?> cat-chip--white">
                                <?= htmlspecialchars($featured->categoria_nombre ?? '') ?>
                            </span>
                            <?php if ($featured_nueva): ?>
                            <span class="nueva-badge">Nuevo</span>
                            <?php endif; ?>
                            <span class="featured-noticia-date"><?= noticia_fecha_larga($featured->fecha_publicacion ?? '') ?></span>
                        </div>
                        <h3 class="featured-noticia-title"><?= htmlspecialchars($featured->titulo) ?></h3>
                        <p class="featured-noticia-excerpt"><?= htmlspecialchars($featured->extracto ?? '') ?></p>
                        <a href="/noticias/<?= htmlspecialchars($featured->slug) ?>" class="featured-noticia-cta">
                            Leer noticia completa
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
                    <h2 class="noticias-section-title">Todas las noticias</h2>
                    <p class="noticias-section-copy">Filtra por área o busca un tema para encontrar lo que te interesa.</p>
                </div>

                <!-- Búsqueda -->
                <div class="noticias-search-wrap fade-up">
                    <div class="noticias-search-input-wrap">
                        <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        <input type="search" id="noticiasSearch" class="noticias-search-input"
                               placeholder="Buscar noticias..." autocomplete="off" aria-label="Buscar noticias">
                    </div>
                </div>

                <!-- Filtros -->
                <div class="noticias-filters fade-up" role="group" aria-label="Filtrar por categoría">
                    <button class="noticias-filter-btn active" type="button" data-noticia-filter="all">Todas</button>
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
                            <span class="noticia-nueva-dot" aria-label="Noticia nueva"></span>
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
                                Leer noticia
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
                    <h3>Sin resultados</h3>
                    <p>Prueba con otro término o selecciona "Todas".</p>
                </div>

            </div>
        </section>

        <!-- ── CTA ────────────────────────────────────────── -->
        <section class="noticias-cta">
            <div class="container">
                <div class="noticias-cta-box fade-up">
                    <h3>¿Quieres vivir estas noticias en persona?</h3>
                    <p>
                        Cada evento, logro y celebración sucede dentro de un proyecto educativo pensado para que cada alumno crezca. Agenda una visita y descubre cómo se vive Bilbao.
                    </p>
                    <a href="https://wa.me/525614612682?text=Hola,%20quiero%20agendar%20una%20visita%20guiada%20al%20Colegio%20Bilbao."
                       class="btn-terciario" target="_blank" rel="noopener noreferrer">
                        Agenda tu visita
                    </a>
                </div>
            </div>
        </section>

    </main>

    <script>
    (function () {
        'use strict';
        var PERPAGE = 9;
        var currentPage  = 1;
        var activeFilter = 'all';
        var searchQuery  = '';

        var grid       = document.getElementById('noticiasGrid');
        var empty      = document.getElementById('noticiasEmpty');
        var search     = document.getElementById('noticiasSearch');
        var filterBtns = document.querySelectorAll('[data-noticia-filter]');
        if (!grid) return;

        var allCards = Array.from(grid.querySelectorAll('.noticia-card'));

        function getMatching() {
            return allCards.filter(function (card) {
                var matchCat    = activeFilter === 'all' || card.dataset.category === activeFilter;
                var matchSearch = !searchQuery ||
                    (card.dataset.title   || '').indexOf(searchQuery) !== -1 ||
                    (card.dataset.excerpt || '').indexOf(searchQuery) !== -1;
                return matchCat && matchSearch;
            });
        }

        function render() {
            var matching   = getMatching();
            var total      = matching.length;
            var totalPages = Math.max(1, Math.ceil(total / PERPAGE));
            if (currentPage > totalPages) currentPage = totalPages;

            var start = (currentPage - 1) * PERPAGE;
            var end   = start + PERPAGE;

            allCards.forEach(function (card) {
                var idx  = matching.indexOf(card);
                var show = idx !== -1 && idx >= start && idx < end;
                card.classList.toggle('is-hidden', !show);
            });

            if (empty) empty.classList.toggle('show', total === 0);
            renderPagination(totalPages, total);
        }

        function renderPagination(totalPages, total) {
            var nav = document.getElementById('noticiasPagination');
            if (!nav) {
                nav = document.createElement('nav');
                nav.id = 'noticiasPagination';
                nav.className = 'blog-pagination';
                nav.setAttribute('aria-label', 'Paginación de noticias');
                var emptyEl = document.getElementById('noticiasEmpty');
                grid.parentNode.insertBefore(nav, emptyEl || grid.nextSibling);
            }

            if (totalPages <= 1) { nav.innerHTML = ''; return; }

            var parts = [];
            parts.push('<span class="blog-page-info">' + total + ' noticia' + (total !== 1 ? 's' : '') + '</span>');

            if (currentPage > 1) {
                parts.push('<button class="blog-page-btn" data-pg="' + (currentPage - 1) + '" aria-label="Página anterior">←</button>');
            }

            var from = Math.max(1, currentPage - 2);
            var to   = Math.min(totalPages, currentPage + 2);
            if (from > 1) parts.push('<button class="blog-page-btn" data-pg="1">1</button>');
            if (from > 2) parts.push('<span class="blog-page-info">…</span>');
            for (var i = from; i <= to; i++) {
                parts.push('<button class="blog-page-btn' + (i === currentPage ? ' active' : '') + '" data-pg="' + i + '">' + i + '</button>');
            }
            if (to < totalPages - 1) parts.push('<span class="blog-page-info">…</span>');
            if (to < totalPages)     parts.push('<button class="blog-page-btn" data-pg="' + totalPages + '">' + totalPages + '</button>');

            if (currentPage < totalPages) {
                parts.push('<button class="blog-page-btn" data-pg="' + (currentPage + 1) + '" aria-label="Página siguiente">→</button>');
            }

            nav.innerHTML = parts.join('');
            nav.querySelectorAll('[data-pg]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    currentPage = parseInt(btn.dataset.pg, 10);
                    render();
                    grid.scrollIntoView({ behavior: 'smooth', block: 'start' });
                });
            });
        }

        filterBtns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                filterBtns.forEach(function (b) { b.classList.remove('active'); });
                btn.classList.add('active');
                activeFilter = btn.dataset.noticiaFilter;
                currentPage = 1;
                render();
            });
        });

        if (search) {
            search.addEventListener('input', function () {
                searchQuery = search.value.toLowerCase().trim();
                currentPage = 1;
                render();
            });
        }

        render();
    })();
    </script>

    <!-- ── Three.js hero background ── -->
    <script>
    (function () {
        if (typeof THREE === 'undefined') return;
        const canvas = document.getElementById('noticias-hero-bg');
        const hero   = canvas.parentElement;

        const renderer = new THREE.WebGLRenderer({ canvas, alpha: true, antialias: false });
        renderer.setPixelRatio(Math.min(window.devicePixelRatio, 1.5));

        const scene  = new THREE.Scene();
        const camera = new THREE.OrthographicCamera(0, 0, 0, 0, -10, 10);

        const N = 70;
        const pts = [];
        const vel = [];

        function resize() {
            const w = hero.offsetWidth;
            const h = hero.offsetHeight;
            renderer.setSize(w, h);
            camera.left   = 0;
            camera.right  = w;
            camera.top    = h;
            camera.bottom = 0;
            camera.updateProjectionMatrix();

            if (pts.length === 0) {
                for (let i = 0; i < N; i++) {
                    pts.push(Math.random() * w, Math.random() * h, 0);
                    const speed = 0.18 + Math.random() * 0.22;
                    const angle = Math.random() * Math.PI * 2;
                    vel.push(Math.cos(angle) * speed, Math.sin(angle) * speed);
                }
            }
        }

        resize();

        // Particles
        const ptGeo  = new THREE.BufferGeometry();
        const posArr = new Float32Array(pts);
        ptGeo.setAttribute('position', new THREE.BufferAttribute(posArr, 3));
        const ptMat  = new THREE.PointsMaterial({ color: 0xffffff, size: 2.8, transparent: true, opacity: 0.55 });
        const points = new THREE.Points(ptGeo, ptMat);
        scene.add(points);

        // Connections
        const linGeo = new THREE.BufferGeometry();
        const linMat = new THREE.LineBasicMaterial({ color: 0x7dd3fc, transparent: true, opacity: 0.20 });
        const lines  = new THREE.LineSegments(linGeo, linMat);
        scene.add(lines);

        const CONN_DIST = 140;
        let mouseX = -9999, mouseY = -9999;

        function buildConnections() {
            const p   = ptGeo.attributes.position.array;
            const seg = [];
            for (let i = 0; i < N; i++) {
                for (let j = i + 1; j < N; j++) {
                    const dx = p[i*3] - p[j*3];
                    const dy = p[i*3+1] - p[j*3+1];
                    if (dx*dx + dy*dy < CONN_DIST * CONN_DIST) {
                        seg.push(p[i*3], p[i*3+1], 0, p[j*3], p[j*3+1], 0);
                    }
                }
            }
            linGeo.setAttribute('position', new THREE.BufferAttribute(new Float32Array(seg), 3));
        }

        function tick() {
            rafId = requestAnimationFrame(tick);
            const p = ptGeo.attributes.position.array;
            const w = hero.offsetWidth;
            const h = hero.offsetHeight;

            for (let i = 0; i < N; i++) {
                p[i*3]   += vel[i*2];
                p[i*3+1] += vel[i*2+1];

                // Mouse attraction (subtle)
                const dx = mouseX - p[i*3];
                const dy = mouseY - p[i*3+1];
                const d2 = dx*dx + dy*dy;
                if (d2 < 22500) {
                    const f = 0.0003;
                    vel[i*2]   += dx * f;
                    vel[i*2+1] += dy * f;
                    // cap speed
                    const spd = Math.sqrt(vel[i*2]*vel[i*2] + vel[i*2+1]*vel[i*2+1]);
                    if (spd > 0.8) { vel[i*2] *= 0.8/spd; vel[i*2+1] *= 0.8/spd; }
                }

                if (p[i*3] < 0)  { p[i*3] = 0;  vel[i*2]   = Math.abs(vel[i*2]); }
                if (p[i*3] > w)  { p[i*3] = w;  vel[i*2]   = -Math.abs(vel[i*2]); }
                if (p[i*3+1] < 0){ p[i*3+1] = 0; vel[i*2+1] = Math.abs(vel[i*2+1]); }
                if (p[i*3+1] > h){ p[i*3+1] = h; vel[i*2+1] = -Math.abs(vel[i*2+1]); }
            }

            ptGeo.attributes.position.needsUpdate = true;
            buildConnections();
            renderer.render(scene, camera);
        }

        let rafId;
        const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        if (prefersReduced) {
            buildConnections();
            renderer.render(scene, camera);
        } else {
            tick();
        }

        hero.addEventListener('mousemove', function (e) {
            const r = hero.getBoundingClientRect();
            mouseX = e.clientX - r.left;
            mouseY = hero.offsetHeight - (e.clientY - r.top);
        });
        hero.addEventListener('mouseleave', function () {
            mouseX = -9999; mouseY = -9999;
        });

        window.addEventListener('resize', function () {
            resize();
            ptGeo.attributes.position.needsUpdate = true;
            if (!rafId) buildConnections();
        }, { passive: true });
    })();
    </script>
