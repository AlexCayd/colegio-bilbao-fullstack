<?php $paginaVista = 'blog-articulo'; ?>
<?php
function fechaEsArt(?string $fecha): string {
    if (!$fecha) return '';
    $meses = ['', 'enero','febrero','marzo','abril','mayo','junio','julio','agosto',
                  'septiembre','octubre','noviembre','diciembre'];
    $ts = strtotime($fecha);
    if (!$ts) return '';
    return date('j', $ts) . ' de ' . $meses[(int)date('n', $ts)] . ' de ' . date('Y', $ts);
}

function inicialesArt(?string $nombre): string {
    if (!$nombre) return 'CB';
    $partes = explode(' ', trim($nombre));
    $ini = '';
    foreach (array_slice($partes, 0, 2) as $p) {
        $ini .= mb_strtoupper(mb_substr($p, 0, 1));
    }
    return $ini ?: 'CB';
}

$fechaMostrar = fechaEsArt(
    !empty($articulo->fecha_publicacion) ? $articulo->fecha_publicacion : ($articulo->creado_en ?? null)
);

$tagsArr = $tags
    ? array_filter(array_map('trim', explode(',', $tags)))
    : [];

$svg_link_art  = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>';
$svg_share_art = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><polyline points="16 6 12 2 8 6"/><line x1="12" y1="2" x2="12" y2="15"/></svg>';
?>

<div class="read-progress-bar" id="readProgress" role="progressbar" aria-hidden="true"></div>

    <main class="article-main">

        <!-- PORTADA CON PARALLAX -->
        <section class="article-hero" style="view-transition-name: blog-hero-<?= s($articulo->slug) ?>">
            <img
                id="parallax-img"
                class="hero-img-parallax"
                src="<?= $articulo->imagen ? s($articulo->imagen) : '/build/assets/img/blog/blog-placeholder.png' ?>"
                alt="<?= s($articulo->titulo) ?>"
                loading="eager"
                onerror="this.src='/build/assets/img/blog/blog-placeholder.png'"
            >
            <div class="hero-gradient"></div>

            <div class="hero-meta">
                <?php if ($articulo->categoria_nombre): ?>
                <span class="article-category article-category-template"
                    <?php if ($articulo->categoria_color): ?>style="border-color:<?= s($articulo->categoria_color) ?>;"<?php endif; ?>>
                    <?= s($articulo->categoria_nombre) ?>
                </span>
                <?php endif; ?>

                <h1 class="article-title"><?= s($articulo->titulo) ?></h1>

                <?php if ($articulo->extracto): ?>
                <p style="color:rgba(255,255,255,0.82); font-size:1.08rem; line-height:1.65; margin:-8px 0 20px; font-weight:400;">
                    <?= s($articulo->extracto) ?>
                </p>
                <?php endif; ?>

                <div class="article-byline">
                    <?php if ($articulo->autor_avatar): ?>
                    <img src="<?= s($articulo->autor_avatar) ?>" alt="<?= s($articulo->autor_nombre ?? 'Autor') ?>" class="author-avatar-small">
                    <?php else: ?>
                    <div class="author-avatar-small"><?= inicialesArt($articulo->autor_nombre) ?></div>
                    <?php endif; ?>

                    <div class="byline-text">
                        <?php if ($articulo->autor_nombre): ?>
                        Por <strong><?= s($articulo->autor_nombre) ?></strong>
                        <?php else: ?>
                        <strong>Colegio Bilbao</strong>
                        <?php endif; ?>
                        <?php if ($fechaMostrar): ?>
                        <span class="byline-dot"> · </span><?= $fechaMostrar ?>
                        <?php endif; ?>
                        <?php if ($articulo->tiempo_lectura): ?>
                        <span class="byline-dot"> · </span><?= (int)$articulo->tiempo_lectura ?> min de lectura
                        <?php endif; ?>
                    </div>

                    <div class="art-share">
                        <button class="nd-share-btn" id="artCopyLink" type="button">
                            <?= $svg_link_art ?> <span id="artCopyTxt">Copiar enlace</span>
                        </button>
                        <button class="nd-share-btn" id="artShareBtn" type="button">
                            <?= $svg_share_art ?> Compartir
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <!-- BREADCRUMB -->
        <nav class="breadcrumb" aria-label="Ruta de navegación">
            <a href="/">Inicio</a>
            <span>›</span>
            <a href="/blog">Artículos</a>
            <span>›</span>
            <span style="color: var(--col-herencia);"><?= s($articulo->titulo) ?></span>
        </nav>

        <!-- CUERPO DEL ARTÍCULO -->
        <article class="article-body-wrapper">

            <?php if ($articulo->extracto): ?>
            <p class="article-lead fade-up"><?= s($articulo->extracto) ?></p>
            <?php endif; ?>

            <div class="article-body fade-up">
                <?= $articulo->contenido ?>
            </div>

            <!-- DIVIDER -->
            <div class="article-divider fade-up"></div>

            <?php if (!empty($tagsArr)): ?>
            <!-- TAGS -->
            <div class="article-tags fade-up">
                <?php foreach ($tagsArr as $tag): ?>
                <span class="tag"><?= s($tag) ?></span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- CTA BLOCK -->
            <div class="article-cta-block fade-up">
                <h3>¿Quieres vivir esto de cerca?</h3>
                <p>Agenda una visita guiada al Colegio Bilbao y conoce los espacios donde sucede la educación que marca diferencia.</p>
                <a href="https://wa.me/525614612682?text=Hola,%20quiero%20agendar%20una%20visita%20guiada%20al%20Colegio%20Bilbao." class="btn-terciario">Agenda tu visita →</a>
            </div>

        </article>
    </main>

    <?php
    $slotsRec = [
        'reciente'  => 'Lo más reciente',
        'categoria' => 'En la misma categoría',
        'aleatorio' => 'También puede interesarte',
    ];
    $hayRec = !empty(array_filter($recomendados ?? []));
    ?>
    <?php if ($hayRec): ?>
    <section class="article-recomendados">
        <div class="recomendados-inner">
            <h2 class="recomendados-titulo">Más para leer</h2>
            <div class="recomendados-grid">
                <?php foreach ($slotsRec as $key => $etiqueta):
                    $rec = $recomendados[$key] ?? null;
                    if (!$rec) continue;
                    $recFecha = !empty($rec->fecha_publicacion) ? $rec->fecha_publicacion : ($rec->creado_en ?? null);
                ?>
                <a href="/blog/<?= s($rec->slug) ?>" class="rec-card">
                    <div class="rec-card-img" style="view-transition-name: blog-hero-<?= s($rec->slug) ?>">
                        <img
                            src="<?= $rec->imagen ? s($rec->imagen) : '/build/assets/img/blog/blog-placeholder.png' ?>"
                            alt="<?= s($rec->titulo) ?>"
                            loading="lazy"
                            onerror="this.src='/build/assets/img/blog/blog-placeholder.png'"
                        >
                        <span class="rec-label"><?= $etiqueta ?></span>
                    </div>
                    <div class="rec-card-body">
                        <?php if ($rec->categoria_nombre): ?>
                        <span class="rec-cat"
                            <?php if ($rec->categoria_color): ?>style="color:<?= s($rec->categoria_color) ?>;border-color:<?= s($rec->categoria_color) ?>;"<?php endif; ?>>
                            <?= s($rec->categoria_nombre) ?>
                        </span>
                        <?php endif; ?>
                        <h3 class="rec-title"><?= s($rec->titulo) ?></h3>
                        <?php if ($rec->extracto): ?>
                        <p class="rec-excerpt"><?= s($rec->extracto) ?></p>
                        <?php endif; ?>
                        <span class="rec-read-more">Leer artículo →</span>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- FOOTER -->
    <footer class="footer-simple">
        <p>© 2026 Colegio Bilbao. Todos los derechos reservados. · <a href="/aviso-privacidad/">Aviso de privacidad</a></p>
    </footer>

    <script src="/build/js/bundle.min.js" defer></script>

