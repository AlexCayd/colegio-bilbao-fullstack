/* Hero flagship — animaciones GSAP */
(function () {

    const titleEl  = document.getElementById('dynamic-hero-title');
    const heroSect = document.querySelector('.hero-flagship');
    if (!titleEl || !heroSect) return;

    const phrases = [
        'La naturaleza es nuestro salón',
        'Aprende a ser quien eres',
        'Piensa libremente. Crea con libertad',
        'Pertenece. Explora. Construye.',
        'Raíces profundas. Horizonte abierto',
    ];
    let phraseIndex = 0;
    let rotating    = false;

    /* ── Utilidad: envolver cada carácter en un span, por palabra ── */
    function wrapChars(el) {
        const text  = el.textContent.trim();
        el.innerHTML = '';
        text.split(' ').forEach((word, wi, arr) => {
            const ww = document.createElement('span');
            ww.style.cssText = 'display:inline; white-space:nowrap;';
            [...word].forEach(char => {
                const s = document.createElement('span');
                s.className = 'hero-char';
                s.style.display = 'inline-block';
                s.textContent   = char;
                ww.appendChild(s);
            });
            el.appendChild(ww);
            if (wi < arr.length - 1) {
                const sp = document.createElement('span');
                sp.className = 'hero-char hero-char--sp';
                sp.textContent = ' ';
                el.appendChild(sp);
            }
        });
    }

    /* ── Cambio de frase con GSAP ── */
    function morphToPhrase(el, newText) {
        if (rotating) return;
        rotating = true;

        const outChars = [...el.querySelectorAll('.hero-char:not(.hero-char--sp)')];

        gsap.to(outChars, {
            y:       gsap.utils.distribute({ base: -28, amount: -28, from: 'random' }),
            opacity: 0,
            stagger: { each: 0.018, from: 'random' },
            duration: 0.35,
            ease:    'power2.in',
            onComplete() {
                el.innerHTML = '';
                newText.trim().split(' ').forEach((word, wi, arr) => {
                    const ww = document.createElement('span');
                    ww.style.cssText = 'display:inline; white-space:nowrap;';
                    [...word].forEach(char => {
                        const s = document.createElement('span');
                        s.className  = 'hero-char';
                        s.style.cssText = 'display:inline-block;opacity:0;transform:translateY(40px) rotate(4deg)';
                        s.textContent = char;
                        ww.appendChild(s);
                    });
                    el.appendChild(ww);
                    if (wi < arr.length - 1) {
                        const sp = document.createElement('span');
                        sp.className = 'hero-char hero-char--sp';
                        sp.textContent = ' ';
                        el.appendChild(sp);
                    }
                });

                gsap.to(el.querySelectorAll('.hero-char:not(.hero-char--sp)'), {
                    y:        0,
                    rotation: 0,
                    opacity:  1,
                    stagger:  { each: 0.026, from: 'start' },
                    duration: 0.62,
                    ease:     'back.out(1.6)',
                    onComplete() { rotating = false; },
                });
            },
        });
    }

    /* ── Ken Burns sobre el poster ── */
    function kenBurns() {
        const img = heroSect.querySelector('.hero-poster-img');
        if (!img) return;
        /* Min scale 1.05 para que el parallax de scroll no desborde la imagen */
        gsap.to(img, {
            scale:    1.05,
            xPercent: 1.8,
            duration: 16,
            ease:     'sine.inOut',
            repeat:   -1,
            yoyo:     true,
        });
    }

    /* ── Partículas sutiles (polvo/bokeh sobre la imagen) ── */
    function addParticles() {
        const colors = [
            'rgba(255,255,255,0.38)',
            'rgba(70,189,198,0.48)',
            'rgba(77,138,187,0.52)',
        ];
        const frag = document.createDocumentFragment();

        for (let i = 0; i < 16; i++) {
            const p = document.createElement('span');
            p.className = 'hero-particle';
            frag.appendChild(p);

            const size = gsap.utils.random(1.5, 4, 0.5);

            gsap.set(p, {
                width:    size,
                height:   size,
                x:        gsap.utils.random(0, window.innerWidth),
                y:        gsap.utils.random(0, window.innerHeight),
                opacity:  gsap.utils.random(0.04, 0.2),
                zIndex:   4,
                background: gsap.utils.random(colors),
            });

            gsap.to(p, {
                y:       `-=${gsap.utils.random(150, 310)}`,
                x:       `+=${gsap.utils.random(-60, 60)}`,
                opacity: 0,
                duration: gsap.utils.random(10, 22),
                repeat:  -1,
                delay:   gsap.utils.random(0, 10),
                ease:    'none',
                onRepeat() {
                    gsap.set(p, {
                        x:       gsap.utils.random(0, window.innerWidth),
                        y:       window.innerHeight * 1.05,
                        opacity: gsap.utils.random(0.04, 0.2),
                    });
                },
            });
        }

        heroSect.appendChild(frag);
    }

    /* ── Efecto magnético en botones CTA ── */
    function addMagnetic() {
        heroSect.querySelectorAll('.btn-primario, .btn-outline-hero').forEach(btn => {
            btn.addEventListener('mousemove', e => {
                const r = btn.getBoundingClientRect();
                const x = (e.clientX - r.left - r.width  / 2) * 0.22;
                const y = (e.clientY - r.top  - r.height / 2) * 0.22;
                gsap.to(btn, { x, y, duration: 0.28, ease: 'power2.out' });
            });
            btn.addEventListener('mouseleave', () => {
                gsap.to(btn, { x: 0, y: 0, duration: 0.55, ease: 'elastic.out(1, 0.45)' });
            });
        });
    }

    /* ── Animación de entrada ── */
    function heroEntrance() {
        const tl       = gsap.timeline({ defaults: { ease: 'power3.out' } });
        const scanLine = heroSect.querySelector('.hero-scan-line');
        const img      = heroSect.querySelector('.hero-poster-img');

        /* Imagen: desenfoque → nitidez + ligero zoom out */
        if (img) {
            tl.fromTo(img,
                { scale: 1.1, filter: 'blur(16px)' },
                { scale: 1.08, filter: 'blur(0px)', duration: 1.7, ease: 'power2.out' },
                0
            );
        }

        /* Línea de scan que barre de arriba abajo */
        if (scanLine) {
            tl.fromTo(scanLine,
                { y: 0, opacity: 1 },
                { y: window.innerHeight, opacity: 0, duration: 1.15, ease: 'expo.inOut' },
                0.15
            );
        }

        /* Caracteres del título */
        wrapChars(titleEl);
        const initChars = [...titleEl.querySelectorAll('.hero-char:not(.hero-char--sp)')];
        tl.from(initChars, {
            y:        68,
            opacity:  0,
            rotation: 10,
            stagger:  { each: 0.024, from: 'start' },
            duration: 0.72,
            ease:     'back.out(1.6)',
        }, 0.95);

        /* Subtítulo, CTAs, scroll dot */
        tl.from('.hero-sub',              { y: 28, opacity: 0, duration: 0.75 }, 1.72)
          .from('.hero-cta-wrapper',      { y: 20, opacity: 0, duration: 0.65 }, 1.98)
          .from('.hero-scroll-indicator', { opacity: 0, duration: 0.5 }, 2.4);

        /* Arrancar Ken Burns cuando termina la entrada de imagen */
        tl.call(() => { kenBurns(); }, null, 1.7);
    }

    /* ── Parallax en scroll ── */
    function addParallax() {
        gsap.registerPlugin(ScrollTrigger);

        const st = { trigger: heroSect, start: 'top top', end: '+=100%', scrub: true };

        /* La imagen se mueve hacia arriba más lento que el scroll (efecto profundidad) */
        gsap.to('.hero-poster-img', {
            y:    -50,
            ease: 'none',
            scrollTrigger: { ...st, scrub: 2.2 },
        });

        /* El contenido se desvanece y sube al salir */
        gsap.to('.hero-content', {
            y:       50,
            opacity: 0.35,
            ease:    'none',
            scrollTrigger: { trigger: heroSect, start: '28% top', end: '78% top', scrub: 1.2 },
        });
    }

    /* ── Init ── */
    if (typeof gsap !== 'undefined') {
        heroEntrance();
        addParticles();
        addMagnetic();
        addParallax();
        setInterval(() => {
            phraseIndex = (phraseIndex + 1) % phrases.length;
            morphToPhrase(titleEl, phrases[phraseIndex]);
        }, 7000);
    } else {
        /* Fallback sin GSAP */
        let fi = 0;
        setInterval(() => {
            titleEl.style.transition = 'opacity .4s ease';
            titleEl.style.opacity    = '0';
            setTimeout(() => {
                fi = (fi + 1) % phrases.length;
                titleEl.textContent  = phrases[fi];
                titleEl.style.opacity = '1';
            }, 400);
        }, 7000);
    }

})();
