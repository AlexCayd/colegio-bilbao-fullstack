<?php $paginaVista = 'estaticas-comunidad-estudiantes'; ?>
<main id="main-content" class="comunidad-est">

    <!-- ── HERO ─────────────────────────────────── -->
    <section class="comunidad-est__hero">
        <?php $bg_scene = 'bosque'; $bg_colores = ['#4D8ABB', '#7DC6E5', '#46bdc6', '#374C69', '#F1C400']; include __DIR__ . '/_bg.php'; ?>
        <div class="comunidad-est__hero-inner">
            <div class="comunidad-est__hero-text" data-est-reveal>
                <span class="comunidad-est__eyebrow"><i class="fa-solid fa-graduation-cap"></i> <span data-i18n="comunidad-estudiantes.eyebrow">Comunidad estudiantil</span></span>
                <h1 class="comunidad-est__title" data-i18n="comunidad-estudiantes.title">La vida de nuestros estudiantes</h1>
                <p class="comunidad-est__lead" data-i18n="comunidad-estudiantes.lead">
                    Proyectos, deportes, arte y momentos que hacen del Colegio Bilbao un lugar
                    para crecer. Así se vive el día a día en la voz de quienes lo protagonizan.
                </p>
                <div class="comunidad-est__hero-cta">
                    <a href="https://www.instagram.com/bilbaocolegio/" target="_blank" rel="noopener" class="comunidad-est__btn comunidad-est__btn--primary">
                        <i class="fa-brands fa-instagram"></i> @bilbaocolegio
                    </a>
                    <a href="https://www.instagram.com/bilbaomoments/" target="_blank" rel="noopener" class="comunidad-est__btn comunidad-est__btn--ghost">
                        <i class="fa-brands fa-instagram"></i> @bilbaomoments
                    </a>
                </div>
            </div>
            <img src="/build/assets/img/alex/alex-toca.png" alt="Alex" data-i18n-attr="alt:comunidad-estudiantes.alexAlt" class="comunidad-est__hero-img" loading="lazy" data-est-reveal>
        </div>
    </section>

    <!-- ── FEED DE INSTAGRAM ────────────────────── -->
    <section class="comunidad-est__feed">
        <div class="comunidad-est__feed-head" data-est-reveal>
            <h2 class="comunidad-est__feed-title"><i class="fa-brands fa-instagram"></i> <span data-i18n="comunidad-estudiantes.feedTitle">Lo más reciente</span></h2>
            <p class="comunidad-est__feed-sub" data-i18n="comunidad-estudiantes.feedSub">Publicaciones destacadas de nuestras redes.</p>
        </div>

        <div class="comunidad-est__grid">
            <?php
            // Posts destacados de Instagram. TODO: volver dinámico desde el panel.
            $ig_posts = [
                'https://www.instagram.com/p/DaBKTzyGLV4/?img_index=1',
                'https://www.instagram.com/p/DZLmcZgj_6L/?img_index=1',
                'https://www.instagram.com/p/DZFxJMrjiBr/?img_index=1',
                'https://www.instagram.com/p/DYYDH85gX6g/?img_index=1',
            ];
            foreach ($ig_posts as $url): ?>
            <div class="comunidad-est__post" data-est-post>
                <blockquote class="instagram-media"
                    data-instgrm-permalink="<?= htmlspecialchars($url) ?>"
                    data-instgrm-version="14"
                    style="background:#FFF; border:0; border-radius:16px; box-shadow:none; margin:0; max-width:600px; min-width:280px; width:100%;">
                    <a href="<?= htmlspecialchars($url) ?>" target="_blank" rel="noopener" data-i18n="comunidad-estudiantes.verPost">Ver esta publicación en Instagram</a>
                </blockquote>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

</main>

<!-- Script oficial de embeds de Instagram -->
<script async src="//www.instagram.com/embed.js"></script>
