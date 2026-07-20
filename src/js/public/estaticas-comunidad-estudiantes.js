/* estaticas-comunidad-estudiantes
   Migrado desde el <script> embebido de views/estaticas/comunidad/estudiantes.php */
(function () {
    if (!document.body || document.body.dataset.page !== 'estaticas-comunidad-estudiantes') return;
        if (window.instgrm && window.instgrm.Embeds) { window.instgrm.Embeds.process(); }

        // Animaciones GSAP (GSAP + ScrollTrigger cargados global en header.php)
        (function () {
            if (typeof gsap === 'undefined') return;
            var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            if (reduce) return;
            gsap.from('[data-est-reveal]', { y: 26, opacity: 0, duration: 0.8, ease: 'power3.out', stagger: 0.12 });
            if (gsap.registerPlugin && window.ScrollTrigger) {
                gsap.registerPlugin(ScrollTrigger);
                // Parallax creativo del hero: el texto y Alex se desplazan a distinto ritmo
                gsap.to('.comunidad-est__hero-text', {
                    scrollTrigger: { trigger: '.comunidad-est__hero', start: 'top top', end: 'bottom top', scrub: true },
                    y: 80, opacity: 0.2, ease: 'none'
                });
                gsap.to('.comunidad-est__hero-img', {
                    scrollTrigger: { trigger: '.comunidad-est__hero', start: 'top top', end: 'bottom top', scrub: true },
                    y: -60, rotate: 4, ease: 'none'
                });
                gsap.utils.toArray('[data-est-post]').forEach(function (el, i) {
                    gsap.from(el, {
                        scrollTrigger: { trigger: el, start: 'top 88%' },
                        y: 40, opacity: 0, duration: 0.7, ease: 'power3.out', delay: (i % 2) * 0.08
                    });
                });
            }
        })();
})();
