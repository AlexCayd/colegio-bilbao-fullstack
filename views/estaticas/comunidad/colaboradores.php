<?php $paginaVista = 'estaticas-comunidad-colaboradores'; ?>
<main id="main-content" class="colab">
    <section class="colab__stage">
        <?php /* El MISMO bosque del login (src/js/public/forest.js), no la nube de
                 partículas de _bg.php que había antes. Colaboradores es la puerta al
                 panel y su único CTA lleva a /login: compartir escena encadena las dos
                 pantallas en lugar de cambiar de lenguaje visual a mitad de camino.
                 El velo repite el patrón de `.admin-login__veil`.
                 ⚠️ El degradado oscuro de `.colab__stage` es el respaldo REAL:
                 `BilbaoForest.init()` devuelve null sin WebGL o con
                 `prefers-reduced-motion`, y entonces el canvas queda transparente. */ ?>
        <canvas id="forest-canvas" class="colab__canvas" aria-hidden="true"></canvas>
        <div class="colab__veil" aria-hidden="true"></div>

        <div class="colab__inner" data-colab-reveal>
            <h1 class="colab__title" data-i18n="comunidad-colaboradores.title">Intranet Bilbao</h1>
            <a href="/login" class="colab__cta">
                <i class="fa-solid fa-arrow-right-to-bracket"></i>
                <span data-i18n="comunidad-colaboradores.cta">Acceder al panel</span>
            </a>
        </div>
    </section>
</main>

