/* estaticas-comunidad-colaboradores
   Migrado desde el <script> embebido de views/estaticas/comunidad/colaboradores.php */
(function () {
    if (!document.body || document.body.dataset.page !== 'estaticas-comunidad-colaboradores') return;

    /* Bosque WebGL, el mismo del login. Three.js lo inyecta EstaticasController en
       $extra_head y va sin `defer`, pero este bundle sí lo lleva, así que en teoría
       THREE ya existe; el sondeo es la misma red de seguridad que usan blog-login.js y
       blog-home.js — si el CDN tarda, se reintenta durante 8 s en vez de rendirse.

       `init()` devuelve null sin WebGL o con prefers-reduced-motion: el degradado de
       `.colab__stage` es el fondo real, así que la pantalla nunca se ve rota. */
    (function () {
        var canvas = document.getElementById('forest-canvas');
        if (!canvas) return;
        var intentos = 0;
        (function esperar() {
            if (window.THREE && window.BilbaoForest) {
                window.BilbaoForest.init(canvas, { scroll: false, dark: true });
                return;
            }
            if (++intentos < 100) setTimeout(esperar, 80);
        })();
    })();

    (function () {
        if (typeof gsap === 'undefined') return;
        var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (reduce) return;
        gsap.from('[data-colab-reveal]', { y: 28, opacity: 0, duration: 0.9, ease: 'power3.out' });
    })();
})();
