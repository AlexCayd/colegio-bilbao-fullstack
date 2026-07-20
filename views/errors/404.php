<?php $paginaVista = 'errors-404'; ?>
<?php $titulo = 'Página no encontrada'; ?>

<main>

<section class="error-404-wrap">
    <div class="error-404-inner">

        <!-- Alex + bocadillo -->
        <div class="error-alex-col">
            <div class="error-bubble">
                <p><span data-i18n="errors.404.bubble.pre">¡Uy! Busqué por todas partes y </span><em data-i18n="errors.404.bubble.em">esta página no existe</em><span data-i18n="errors.404.bubble.post">. Puede que la URL esté mal escrita o la ruta haya cambiado.</span></p>
            </div>
            <img
                src="/build/assets/img/alex/alex-espera.png"
                alt="Alex, mascota del Colegio Bilbao"
                class="error-alex-img"
                loading="eager"
                decoding="async"
                data-i18n-attr="alt:errors.404.mascotAlt"
            >
        </div>

        <!-- Texto y acciones -->
        <div class="error-text-col">
            <span class="error-badge">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <span data-i18n="errors.404.badge">Error 404</span>
            </span>

            <h1 class="error-headline">
                <span data-i18n="errors.404.headlinePre">Esta página</span><br><span data-i18n="errors.404.headlineEm">no existe</span>
            </h1>

            <p class="error-desc" data-i18n="errors.404.desc">
                La ruta que buscas no fue encontrada en nuestro servidor. Puede que haya sido movida, renombrada o simplemente nunca existió.
            </p>

            <div class="error-actions">
                <a href="/" class="btn-primario" data-i18n="errors.404.cta.home">Ir al inicio</a>
                <a href="javascript:history.back()" class="btn-secundario" data-i18n="errors.404.cta.back">← Volver atrás</a>
            </div>

            <div class="error-divider"></div>

            <p class="error-explore-label" data-i18n="errors.404.exploreLabel">Explora el sitio</p>
            <div class="error-chips">
                <a href="/admisiones" class="error-chip">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 7H4a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/><path d="M16 3H8a2 2 0 0 0-2 2v2h12V5a2 2 0 0 0-2-2z"/></svg>
                    <span data-i18n="chrome.header.nav.admisiones">Admisiones</span>
                </a>
                <a href="/conocenos/quienes-somos" class="error-chip">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M20 21a8 8 0 1 0-16 0"/></svg>
                    <span data-i18n="chrome.header.nav.conocenos">Conócenos</span>
                </a>
                <a href="/blog" class="error-chip">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>
                    <span data-i18n="chrome.header.nav.blog">Blog</span>
                </a>
                <a href="/contacto" class="error-chip">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.6 3.4 2 2 0 0 1 3.6 1.22h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.81a16 16 0 0 0 6.29 6.29l.95-.95a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                    <span data-i18n="chrome.overlay.contacto.contacto">Contacto</span>
                </a>
                <a href="/mapa-del-sitio" class="error-chip">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="3 6 9 3 15 6 21 3 21 18 15 21 9 18 3 21"/><line x1="9" y1="3" x2="9" y2="18"/><line x1="15" y1="6" x2="15" y2="21"/></svg>
                    <span data-i18n="chrome.footer.legal.mapa">Mapa del sitio</span>
                </a>
            </div>
        </div>

    </div>
</section>
</main>
