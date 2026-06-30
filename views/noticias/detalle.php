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

$svg_link  = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>';
$svg_share = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><polyline points="16 6 12 2 8 6"/><line x1="12" y1="2" x2="12" y2="15"/></svg>';
?>
<div class="read-progress-bar" id="readProgress" role="progressbar" aria-hidden="true"></div>

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
                                <?= $svg_link ?> <span id="ndCopyTxt">Copiar enlace</span>
                            </button>
                            <button class="nd-share-btn" id="ndShareBtn" type="button">
                                <?= $svg_share ?> Compartir
                            </button>
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
                        <img src="/build/assets/img/alex/bby-alex-saluda.png" alt="">
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

        var copyBtn = document.getElementById('copyLinkHero');
        var copyTxt = document.getElementById('ndCopyTxt');
        if (copyBtn) {
            copyBtn.addEventListener('click', function () {
                var write = function () {
                    copyTxt.textContent = '¡Copiado!';
                    copyBtn.style.background = '#dcfce7';
                    copyBtn.style.color = '#166534';
                    setTimeout(function () {
                        copyTxt.textContent = 'Copiar enlace';
                        copyBtn.style.background = '';
                        copyBtn.style.color = '';
                    }, 2000);
                };
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(location.href).then(write).catch(write);
                } else {
                    var ta = document.createElement('textarea');
                    ta.value = location.href;
                    Object.assign(ta.style, { position: 'fixed', opacity: '0' });
                    document.body.appendChild(ta);
                    ta.select();
                    document.execCommand('copy');
                    document.body.removeChild(ta);
                    write();
                }
            });
        }

        var shareBtn = document.getElementById('ndShareBtn');
        if (shareBtn) {
            if (navigator.share) {
                shareBtn.addEventListener('click', function () {
                    navigator.share({ title: document.title, url: location.href });
                });
            } else {
                shareBtn.style.display = 'none';
            }
        }
    })();
    </script>
