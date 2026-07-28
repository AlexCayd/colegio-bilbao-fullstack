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
            var intro = gsap.from('[data-est-reveal]', { y: 26, opacity: 0, duration: 0.8, ease: 'power3.out', stagger: 0.12 });
            if (gsap.registerPlugin && window.ScrollTrigger) {
                gsap.registerPlugin(ScrollTrigger);

                // Parallax del hero: el texto y Alex se desplazan a distinto ritmo.
                // Se usa fromTo con immediateRender:false porque estos elementos también
                // llevan [data-est-reveal]: con un .to() el scrub tomaría como estado
                // inicial el de la animación de entrada (opacity 0) y, al volver arriba,
                // el texto se quedaría invisible.
                // Config nueva por tween: GSAP muta el objeto al instanciar el ScrollTrigger
                function scrubHero() {
                    return { trigger: '.comunidad-est__hero', start: 'top top', end: 'bottom top', scrub: true, invalidateOnRefresh: true };
                }

                gsap.fromTo('.comunidad-est__hero-text',
                    { y: 0, opacity: 1 },
                    { y: 80, opacity: 0.35, ease: 'none', immediateRender: false, scrollTrigger: scrubHero() }
                );
                gsap.fromTo('.comunidad-est__hero-img',
                    { y: 0, rotate: 0 },
                    { y: -60, rotate: 4, ease: 'none', immediateRender: false, scrollTrigger: scrubHero() }
                );

                // Los embeds de Instagram cambian de alto al cargar: recalcula los triggers
                intro.eventCallback('onComplete', function () { ScrollTrigger.refresh(); });
                window.addEventListener('load', function () { ScrollTrigger.refresh(); });
                gsap.utils.toArray('[data-est-post]').forEach(function (el, i) {
                    gsap.from(el, {
                        scrollTrigger: { trigger: el, start: 'top 88%' },
                        y: 40, opacity: 0, duration: 0.7, ease: 'power3.out', delay: (i % 2) * 0.08
                    });
                });
            }
        })();
})();
