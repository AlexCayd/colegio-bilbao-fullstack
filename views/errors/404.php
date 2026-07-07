<?php $titulo = 'Página no encontrada'; ?>

<main>
<style>
    .error-404-wrap {
        min-height: calc(100vh - 80px);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 4rem var(--gutter-mobile, 24px);
        background: var(--bg-global, #F9FBFE);
    }

    .error-404-inner {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 3rem;
        align-items: center;
        max-width: 960px;
        width: 100%;
    }

    .error-alex-col {
        position: relative;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .error-bubble {
        background: white;
        border: 2px solid var(--col-borde, rgba(77,138,187,.15));
        border-radius: var(--radius-card, 24px);
        padding: 1.25rem 1.5rem;
        box-shadow: var(--shadow-float);
        max-width: 280px;
        text-align: center;
        position: relative;
        margin-bottom: 0;
    }

    .error-bubble p {
        margin: 0;
        font-size: 0.95rem;
        color: var(--col-herencia, #374C69);
        font-weight: 600;
        line-height: 1.5;
    }

    .error-bubble em {
        font-style: normal;
        color: var(--col-bilbao, #4D8ABB);
    }

    /* Punta del bocadillo */
    .error-bubble::after {
        content: '';
        position: absolute;
        bottom: -16px;
        left: 50%;
        transform: translateX(-50%);
        border: 8px solid transparent;
        border-top-color: white;
        filter: drop-shadow(0 3px 2px rgba(77,138,187,.1));
    }
    .error-bubble::before {
        content: '';
        position: absolute;
        bottom: -19px;
        left: 50%;
        transform: translateX(-50%);
        border: 9px solid transparent;
        border-top-color: var(--col-borde, rgba(77,138,187,.2));
    }

    .error-alex-img {
        width: 260px;
        max-width: 100%;
        margin-top: 1rem;
        filter: drop-shadow(0 16px 32px rgba(77,138,187,.18));
        animation: alexFloat 4s ease-in-out infinite;
    }

    @keyframes alexFloat {
        0%, 100% { transform: translateY(0); }
        50%       { transform: translateY(-10px); }
    }

    /* ---- Columna de texto ---- */
    .error-text-col {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .error-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: rgba(77,138,187,.1);
        color: var(--col-bilbao, #4D8ABB);
        font-size: 0.78rem;
        font-weight: 700;
        letter-spacing: 1.5px;
        text-transform: uppercase;
        padding: 6px 14px;
        border-radius: 100px;
        width: fit-content;
    }

    .error-headline {
        font-size: clamp(1.8rem, 4vw, 2.8rem);
        font-weight: 900;
        color: var(--col-herencia, #374C69);
        line-height: 1.15;
        margin: 0;
    }

    .error-headline span {
        color: var(--col-bilbao, #4D8ABB);
    }

    .error-desc {
        font-size: 1rem;
        color: var(--text-gray, #4A5568);
        line-height: 1.7;
        margin: 0;
        max-width: 420px;
    }

    .error-actions {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        margin-top: 0.5rem;
    }

    .error-divider {
        height: 1px;
        background: var(--col-borde, rgba(77,138,187,.15));
        margin: 0.25rem 0;
    }

    .error-explore-label {
        font-size: 0.8rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: var(--text-gray, #4A5568);
    }

    .error-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .error-chip {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 7px 14px;
        border-radius: 100px;
        background: white;
        border: 1.5px solid var(--col-borde, rgba(77,138,187,.2));
        color: var(--col-herencia, #374C69);
        font-size: 0.85rem;
        font-weight: 600;
        text-decoration: none;
        transition: all var(--transition-fast, .2s ease);
    }

    .error-chip:hover {
        border-color: var(--col-bilbao, #4D8ABB);
        color: var(--col-bilbao, #4D8ABB);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(77,138,187,.15);
    }

    /* ---- Responsive ---- */
    @media (max-width: 680px) {
        .error-404-inner {
            grid-template-columns: 1fr;
            text-align: center;
        }

        .error-alex-col {
            order: -1;
        }

        .error-alex-img {
            width: 200px;
        }

        .error-bubble {
            max-width: 100%;
        }

        .error-bubble::after,
        .error-bubble::before {
            display: none;
        }

        .error-badge,
        .error-chips,
        .error-actions {
            justify-content: center;
        }

        .error-desc {
            max-width: 100%;
        }
    }
</style>

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
