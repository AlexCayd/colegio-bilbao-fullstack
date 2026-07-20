<?php $paginaVista = 'blog-index'; ?>
<?php
function fechaEs(?string $fecha): string {
    if (!$fecha) return '';
    $meses = ['', 'enero','febrero','marzo','abril','mayo','junio','julio','agosto',
                  'septiembre','octubre','noviembre','diciembre'];
    $ts = strtotime($fecha);
    if (!$ts) return '';
    return date('j', $ts) . ' de ' . $meses[(int)date('n', $ts)] . ' de ' . date('Y', $ts);
}

function inicialesAutor(?string $nombre): string {
    if (!$nombre) return 'CB';
    $partes = explode(' ', trim($nombre));
    $ini = '';
    foreach (array_slice($partes, 0, 2) as $p) {
        $ini .= mb_strtoupper(mb_substr($p, 0, 1));
    }
    return $ini ?: 'CB';
}
?>

    <!-- ── SUBNAV ───────────────────────────────────────────── -->
    <nav class="blog-subnav" aria-label="Voces Bilbao">
        <div class="container">
            <a href="/blog" class="blog-subnav-brand">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                Voces Bilbao
            </a>
            <div class="blog-subnav-links">
                <a href="/blog" class="blog-subnav-link active">
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2Zm0 0a2 2 0 0 1-2-2v-9c0-1.1.9-2 2-2h2"/><path d="M18 14h-8"/><path d="M15 18h-5"/><path d="M10 6h8v4h-8z"/></svg>
                    Artículos
                </a>
                <a href="/noticias" class="blog-subnav-link">
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                    Noticias
                </a>
                <a href="/admisiones/" class="blog-subnav-link">
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                    Admisiones
                </a>
            </div>
            <div class="blog-subnav-end">
                <?php if (!empty($articulos)): ?>
                <span class="blog-subnav-count"><?= count($articulos) ?> artículos</span>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <main class="blog-home">

        <section class="blog-home-hero">
            <canvas id="blog-hero-bg" style="position:absolute;top:0;left:0;width:100%;height:100%;pointer-events:none;z-index:0;" aria-hidden="true"></canvas>
            <div class="container" style="position:relative;z-index:1;">
                <div class="blog-home-hero-body fade-up">
                    <div class="blog-eyebrow">
                        <span class="blog-eyebrow-tag">Blog editorial</span>
                        Colegio Bilbao
                    </div>
                    <h1 class="blog-home-title">
                        Blog<br><em>Bilbao</em>
                    </h1>
                    <p class="blog-home-lead">
                        Artículos, reflexiones y perspectivas sobre educación, aprendizaje y la vida dentro del Colegio Bilbao.
                    </p>
                    <div class="blog-home-actions">
                        <a href="#todos-los-articulos" class="btn-primario">Ver artículos</a>
                        <a href="/contacto/" class="btn-secundario">Conocer el colegio</a>
                    </div>
                </div>
                <img src="/build/assets/img/alex/alex-lee.png"
                     class="blog-hero-mascot"
                     alt="Alex lee un libro"
                     aria-hidden="true">
            </div>

            <?php if (!empty($articulos)): ?>
            <div class="blog-topics-strip" aria-hidden="true">
                <div class="blog-topics-inner">
                    <div class="blog-topics-label">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2Zm0 0a2 2 0 0 1-2-2v-9c0-1.1.9-2 2-2h2"/><path d="M18 14h-8"/><path d="M15 18h-5"/><path d="M10 6h8v4h-8z"/></svg>
                        Artículos recientes
                    </div>
                    <div class="blog-topics-track-wrap">
                        <div class="blog-topics-track">
                            <?php
                            $tickerColors = ['#7dd3fc','#86efac','#fcd34d','#f9a8d4','#c4b5fd','#67e8f9','#fda4af'];
                            $tickerArticulos = array_merge($articulos, $articulos, $articulos);
                            foreach ($tickerArticulos as $ti => $ta):
                                $dotColor = !empty($ta->categoria_color) ? $ta->categoria_color : $tickerColors[$ti % count($tickerColors)];
                            ?>
                            <a href="/blog/<?= s($ta->slug) ?>" class="blog-topic-pill">
                                <span class="topic-dot" style="background:<?= s($dotColor) ?>;"></span>
                                <?= s($ta->titulo) ?>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="blog-topics-strip blog-topics-strip--static" aria-hidden="true">
                <div class="blog-topics-inner">
                    <div class="blog-topics-label">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                        Artículos recientes
                    </div>
                    <div class="blog-topics-track-wrap">
                        <div class="blog-topics-track">
                            <?php
                            $staticTopics = [
                                ['Aprendizaje integral','#7dd3fc'],['Vida escolar','#86efac'],
                                ['Comunidad','#fcd34d'],['Arte y cultura','#f9a8d4'],
                                ['Tecnología','#c4b5fd'],['Deportes','#67e8f9'],
                                ['Familia','#fda4af'],['Aprendizaje integral','#7dd3fc'],
                                ['Vida escolar','#86efac'],['Comunidad','#fcd34d'],
                                ['Arte y cultura','#f9a8d4'],['Tecnología','#c4b5fd'],
                            ];
                            foreach ($staticTopics as [$name, $color]):
                            ?>
                            <span class="blog-topic-pill">
                                <span class="topic-dot" style="background:<?= htmlspecialchars($color) ?>;"></span>
                                <?= htmlspecialchars($name) ?>
                            </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </section>

        <?php if (!empty($articulos)):
            $featuredSlugs = array_column(array_slice($articulos, 0, 3), 'slug');
        ?>
        <section class="blog-featured">
            <div class="container">
                <div class="blog-section-head fade-up">
                    <div>
                        <h2 class="blog-section-title">Artículos destacados</h2>
                    </div>
                    <p class="blog-section-copy">
                        Una selección editorial para conocer de cerca cómo se vive el aprendizaje dentro y fuera del aula.
                    </p>
                </div>

                <div class="featured-grid">
                    <?php $principal = $articulos[0]; ?>
                    <a href="/blog/<?= s($principal->slug) ?>" class="featured-main-card fade-up" style="view-transition-name: blog-hero-<?= s($principal->slug) ?>">
                        <?= picture($principal->imagen ? s($principal->imagen) : '/build/assets/img/blog/blog-placeholder.png', s($principal->titulo), '', 'lazy') ?>
                        <div class="featured-main-overlay">
                            <div class="featured-main-content">
                                <?php if ($principal->categoria_nombre): ?>
                                <span class="post-kicker"<?= !empty($principal->categoria_color) ? ' style="background:' . s($principal->categoria_color) . ';backdrop-filter:none;"' : '' ?>><?= s($principal->categoria_nombre) ?></span>
                                <?php endif; ?>
                                <h3 class="featured-main-title"><?= s($principal->titulo) ?></h3>
                                <?php if ($principal->extracto): ?>
                                <p class="featured-main-excerpt"><?= s($principal->extracto) ?></p>
                                <?php endif; ?>
                                <div class="post-meta">
                                    <?php if ($principal->autor_nombre): ?>
                                    <div class="post-author">
                                        <div class="post-author-avatar">
                                            <?php if (!empty($principal->autor_avatar)): ?>
                                            <img src="<?= s($principal->autor_avatar) ?>" alt="<?= s($principal->autor_nombre) ?>" loading="lazy">
                                            <?php else: ?>
                                            <span><?= inicialesAutor($principal->autor_nombre) ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <span><?= s($principal->autor_nombre) ?></span>
                                    </div>
                                    <span>·</span>
                                    <?php endif; ?>
                                    <span><?= fechaEs($principal->fecha_publicacion ?? $principal->creado_en) ?></span>
                                    <?php if ($principal->tiempo_lectura): ?>
                                    <span>·</span>
                                    <span><?= (int)$principal->tiempo_lectura ?> min</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </a>

                    <?php if (count($articulos) > 1): ?>
                    <div class="featured-side">
                        <?php foreach (array_slice($articulos, 1, 2) as $dest): ?>
                        <a href="/blog/<?= s($dest->slug) ?>" class="mini-post-card fade-up">
                            <?= picture($dest->imagen ? s($dest->imagen) : '/build/assets/img/blog/blog-placeholder.png', s($dest->titulo), '', 'lazy', ['style' => 'view-transition-name: blog-hero-' . s($dest->slug)]) ?>
                            <div class="mini-post-body">
                                <?php if ($dest->categoria_nombre): ?>
                                <span class="article-category article-category-home"<?= !empty($dest->categoria_color) ? ' style="background:' . s($dest->categoria_color) . ';border-color:' . s($dest->categoria_color) . ';color:#fff;"' : '' ?>><?= s($dest->categoria_nombre) ?></span>
                                <?php endif; ?>
                                <h3 class="mini-post-title"><?= s($dest->titulo) ?></h3>
                                <?php if ($dest->extracto): ?>
                                <p class="mini-post-excerpt"><?= s($dest->extracto) ?></p>
                                <?php endif; ?>
                                <?php if ($dest->autor_nombre): ?>
                                <div class="post-meta mini-post-meta">
                                    <div class="post-author">
                                        <div class="post-author-avatar">
                                            <?php if (!empty($dest->autor_avatar)): ?>
                                            <img src="<?= s($dest->autor_avatar) ?>" alt="<?= s($dest->autor_nombre) ?>" loading="lazy">
                                            <?php else: ?>
                                            <span><?= inicialesAutor($dest->autor_nombre) ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <span><?= s($dest->autor_nombre) ?></span>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <section class="blog-posts" id="todos-los-articulos">
            <div class="container">
                <div class="blog-section-head fade-up">
                    <div>
                        <h2 class="blog-section-title">Todos los artículos</h2>
                    </div>
                    <p class="blog-section-copy">
                        Explora todos los contenidos editoriales y filtra por categoría para encontrar lo que quieres leer.
                    </p>
                </div>

                <div class="blog-search-wrap fade-up">
                    <div class="blog-search-input-wrap">
                        <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        <input type="search" id="blogSearch" class="blog-search-input" placeholder="Buscar artículos..." autocomplete="off" aria-label="Buscar artículos">
                        <button type="button" class="blog-search-clear" id="blogSearchClear" aria-label="Limpiar búsqueda">
                            <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        </button>
                    </div>
                </div>

                <?php if (!empty($categorias)): ?>
                <div class="category-filters fade-up" id="categoryFilters">
                    <button class="filter-btn active" type="button" data-filter="all">Todos</button>
                    <?php foreach ($categorias as $cat): ?>
                    <button class="filter-btn" type="button" data-filter="<?= s($cat->slug) ?>"><?= s($cat->nombre) ?></button>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <?php if (!empty($articulos)): ?>
                <div class="posts-grid" id="postsGrid">
                    <?php foreach ($articulos as $a): ?>
                    <article class="post-card fade-up"
                             data-category="<?= s($a->categoria_slug ?? '') ?>"
                             data-title="<?= s($a->titulo) ?>"
                             data-excerpt="<?= s($a->extracto ?? '') ?>">
                        <a href="/blog/<?= s($a->slug) ?>"<?= !in_array($a->slug, $featuredSlugs) ? ' style="view-transition-name: blog-hero-' . s($a->slug) . '"' : '' ?>>
                            <?= picture($a->imagen ? s($a->imagen) : '/build/assets/img/blog/blog-placeholder.png', s($a->titulo), '', 'lazy') ?>
                        </a>
                        <div class="post-card-body">
                            <?php if ($a->categoria_nombre): ?>
                            <div class="post-card-categories">
                                <span class="article-category" data-filter-link="<?= s($a->categoria_slug ?? '') ?>"<?= !empty($a->categoria_color) ? ' style="background:' . s($a->categoria_color) . ';border-color:' . s($a->categoria_color) . ';color:#fff;"' : '' ?>>
                                    <?= s($a->categoria_nombre) ?>
                                </span>
                            </div>
                            <?php endif; ?>
                            <h3 class="post-card-title">
                                <a href="/blog/<?= s($a->slug) ?>"><?= s($a->titulo) ?></a>
                            </h3>
                            <?php if ($a->extracto): ?>
                            <p class="post-card-excerpt"><?= s($a->extracto) ?></p>
                            <?php endif; ?>
                            <div class="post-meta">
                                <?php if ($a->autor_nombre): ?>
                                <div class="post-author">
                                    <div class="post-author-avatar">
                                        <?php if (!empty($a->autor_avatar)): ?>
                                        <img src="<?= s($a->autor_avatar) ?>" alt="<?= s($a->autor_nombre) ?>" loading="lazy">
                                        <?php else: ?>
                                        <span><?= inicialesAutor($a->autor_nombre) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <span><?= s($a->autor_nombre) ?></span>
                                </div>
                                <span>·</span>
                                <?php endif; ?>
                                <span><?= fechaEs($a->fecha_publicacion ?? $a->creado_en) ?></span>
                                <?php if ($a->tiempo_lectura): ?>
                                <span>·</span>
                                <span><?= (int)$a->tiempo_lectura ?> min</span>
                                <?php endif; ?>
                            </div>
                            <a href="/blog/<?= s($a->slug) ?>" class="read-more">Leer artículo →</a>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>

                <div class="empty-state" id="emptyState" style="display:none;">
                    <h3>No hay artículos en esta categoría</h3>
                    <p>Prueba con otro filtro o vuelve a "Todos".</p>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <h3>Próximamente</h3>
                    <p>Estamos preparando contenido. ¡Vuelve pronto!</p>
                </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="blog-cta">
            <div class="container">
                <div class="blog-cta-box fade-up">
                    <h3>Conoce el colegio más allá de la pantalla</h3>
                    <p>
                        Si alguna de estas historias resuena contigo, agenda una visita guiada y descubre cómo se vive en Bilbao una educación que combina exigencia, sensibilidad y contexto real.
                    </p>
                    <a href="https://wa.me/525614612682?text=Hola,%20quiero%20agendar%20una%20visita%20guiada%20al%20Colegio%20Bilbao." class="btn-terciario">
                        Agenda tu visita
                    </a>
                </div>
            </div>
        </section>

    </main>


    <!-- ── Three.js hero background ── -->
