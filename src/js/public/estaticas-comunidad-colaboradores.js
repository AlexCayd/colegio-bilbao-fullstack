/* estaticas-comunidad-colaboradores
   Migrado desde el <script> embebido de views/estaticas/comunidad/colaboradores.php */
(function () {
    if (!document.body || document.body.dataset.page !== 'estaticas-comunidad-colaboradores') return;
    (function () {
        if (typeof gsap === 'undefined') return;
        var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (reduce) return;
        gsap.from('[data-colab-reveal]', { y: 28, opacity: 0, duration: 0.9, ease: 'power3.out' });
    })();
})();
