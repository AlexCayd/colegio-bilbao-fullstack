/* Landing page — forest canvas, hero rotation, counters, GSAP, interactions */
(function () {
    'use strict';

    if (!document.getElementById('forest-canvas')) return;

    /* ---- HERO PHRASE ROTATION (bilingüe ES/EN) ---- */
    var PHRASES = {
        es: [
            { a: 'La naturaleza',   b: 'es nuestro ', hi: 'salón' },
            { a: 'El bosque',       b: 'es nuestro ', hi: 'maestro' },
            { a: 'Aquí se aprende', b: 'a base de ',  hi: 'asombro' },
            { a: 'Crecer',          b: 'también es ', hi: 'explorar' },
        ],
        en: [
            { a: 'Nature is',    b: 'our ',      hi: 'classroom' },
            { a: 'The forest is', b: 'our ',     hi: 'teacher' },
            { a: 'Here we learn', b: 'through ', hi: 'wonder' },
            { a: 'To grow',      b: 'is to ',    hi: 'explore' },
        ],
    };
    var lang = 'es';
    try { lang = localStorage.getItem('bilbao_lang') || 'es'; } catch (e) {}
    var phrases   = PHRASES[lang] || PHRASES.es;
    var phraseIdx = 0;
    var elA   = document.getElementById('hero-a');
    var elB   = document.getElementById('hero-b');
    var elHi  = document.getElementById('hero-hi');
    var title = document.getElementById('hero-title');

    function setPhrase(i, instant) {
        var p = phrases[i];
        if (!p || !elA) return;
        if (instant) {
            elA.textContent  = p.a;
            elB.textContent  = p.b;
            elHi.textContent = p.hi;
            if (title) { title.style.opacity = '1'; title.style.transform = 'none'; }
        } else {
            if (title) { title.style.opacity = '0'; title.style.transform = 'translateY(14px)'; }
            setTimeout(function () {
                elA.textContent  = p.a;
                elB.textContent  = p.b;
                elHi.textContent = p.hi;
                if (title) { title.style.opacity = '1'; title.style.transform = 'none'; }
            }, 450);
        }
    }
    setPhrase(0, true);
    setInterval(function () {
        phraseIdx = (phraseIdx + 1) % phrases.length;
        setPhrase(phraseIdx);
    }, 3800);

    /* Cambio de idioma en caliente: i18n.js emite 'bilbao:lang' al alternar ES/EN */
    document.addEventListener('bilbao:lang', function (e) {
        var next = (e && e.detail) || 'es';
        phrases = PHRASES[next] || PHRASES.es;
        setPhrase(phraseIdx, true);   // re-render inmediato de la frase actual en el nuevo idioma
    });

    /* ---- CARRUSEL DEL POSTER DEL HERO (imágenes + badge rotativos + lightbox) ---- */
    (function initHeroCarousel() {
        var fig    = document.querySelector('[data-hero-carousel]');
        if (!fig) return;
        var slides = [].slice.call(fig.querySelectorAll('.lnd-hero__slide'));
        var dots   = [].slice.call(fig.querySelectorAll('[data-hero-dots] span'));
        var badgeV = fig.querySelector('[data-hero-badge-value]');
        var badgeL = fig.querySelector('[data-hero-badge-label]');
        var prev   = fig.querySelector('[data-hero-prev]');
        var next   = fig.querySelector('[data-hero-next]');
        var zoom   = fig.querySelector('[data-hero-zoom]');
        if (!slides.length) return;

        /* Badge por imagen, bilingüe (mismo patrón que las frases del título) */
        var BADGES = {
            es: [
                { v: '30,000 m²',   l: 'de bosque como salón' },
                { v: 'Atención 1 a 1',   l: 'seguimiento académico' },
                { v: 'Aprender jugando', l: 'recreo y exploración' },
                { v: 'Deporte',          l: 'cuerpo y mente sanos' }
            ],
            en: [
                { v: '30,000 m²',    l: 'of forest as a classroom' },
                { v: '1-to-1 support',    l: 'academic follow-up' },
                { v: 'Learning by play',  l: 'recess & exploration' },
                { v: 'Sports',            l: 'healthy body & mind' }
            ]
        };
        var cLang = 'es';
        try { cLang = localStorage.getItem('bilbao_lang') || 'es'; } catch (e) {}
        var badges = BADGES[cLang] || BADGES.es;

        var idx = 0;
        var timer = null;
        var DELAY = 5000;

        function paintBadge() {
            var b = badges[idx];
            if (b && badgeV) badgeV.innerHTML = b.v;
            if (b && badgeL) badgeL.textContent = b.l;
        }

        var prevTimer = null;
        function go(n) {
            var target = (n + slides.length) % slides.length;
            if (target === idx) return;
            var outgoing = slides[idx];
            idx = target;
            /* La imagen saliente queda opaca DEBAJO (.is-prev) mientras la
               entrante se funde encima → crossfade sin parpadeo. */
            slides.forEach(function (s) { s.classList.remove('is-prev'); });
            if (outgoing) outgoing.classList.add('is-prev');
            slides.forEach(function (s, i) { s.classList.toggle('is-active', i === idx); });
            dots.forEach(function (d, i) { d.classList.toggle('is-active', i === idx); });
            paintBadge();
            if (boxImg && box && box.classList.contains('is-open')) syncLightbox();
            if (prevTimer) clearTimeout(prevTimer);
            prevTimer = setTimeout(function () {
                if (outgoing) outgoing.classList.remove('is-prev');
            }, 1200);
        }
        function nextSlide() { go(idx + 1); }
        function prevSlide() { go(idx - 1); }

        function start() { stop(); timer = setInterval(nextSlide, DELAY); }
        function stop()  { if (timer) { clearInterval(timer); timer = null; } }
        function restart() { start(); }

        if (next) next.addEventListener('click', function () { nextSlide(); restart(); });
        if (prev) prev.addEventListener('click', function () { prevSlide(); restart(); });
        dots.forEach(function (d, i) { d.addEventListener('click', function () { go(i); restart(); }); });

        /* Pausar auto-avance al interactuar */
        fig.addEventListener('mouseenter', stop);
        fig.addEventListener('mouseleave', start);
        fig.addEventListener('focusin', stop);
        fig.addEventListener('focusout', start);

        /* Idioma en caliente */
        document.addEventListener('bilbao:lang', function (e) {
            var nx = (e && e.detail) || 'es';
            badges = BADGES[nx] || BADGES.es;
            paintBadge();
        });

        /* ---- Lightbox (ampliar + navegar entre imágenes) ---- */
        var isEn = (document.documentElement.lang || 'es').indexOf('en') === 0;
        var box, boxImg, closeBtn, lbPrev, lbNext, lastFocus;
        function activeImg() {
            var s = slides[idx];
            return s && s.querySelector('img');
        }
        function syncLightbox() {
            var im = activeImg();
            if (im && boxImg) { boxImg.src = im.currentSrc || im.src; boxImg.alt = im.alt || ''; }
        }
        function mkBtn(cls, label, svg) {
            var b = document.createElement('button');
            b.type = 'button';
            b.className = cls;
            b.setAttribute('aria-label', label);
            b.innerHTML = svg;
            return b;
        }
        function build() {
            box = document.createElement('div');
            box.className = 'lnd-lightbox';
            box.setAttribute('role', 'dialog');
            box.setAttribute('aria-modal', 'true');
            closeBtn = mkBtn('lnd-lightbox__close', isEn ? 'Close' : 'Cerrar', '');
            var chev = 'stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" fill="none"';
            lbPrev = mkBtn('lnd-lightbox__nav lnd-lightbox__nav--prev', isEn ? 'Previous image' : 'Imagen anterior', '<svg viewBox="0 0 24 24" ' + chev + '><polyline points="15 18 9 12 15 6"/></svg>');
            lbNext = mkBtn('lnd-lightbox__nav lnd-lightbox__nav--next', isEn ? 'Next image' : 'Imagen siguiente', '<svg viewBox="0 0 24 24" ' + chev + '><polyline points="9 18 15 12 9 6"/></svg>');
            boxImg = document.createElement('img');
            boxImg.className = 'lnd-lightbox__img';
            box.appendChild(closeBtn);
            box.appendChild(lbPrev);
            box.appendChild(lbNext);
            box.appendChild(boxImg);
            document.body.appendChild(box);
            box.addEventListener('click', function (ev) { if (ev.target === box) closeLb(); });
            closeBtn.addEventListener('click', closeLb);
            lbPrev.addEventListener('click', function () { prevSlide(); restart(); });
            lbNext.addEventListener('click', function () { nextSlide(); restart(); });
        }
        function onKey(ev) {
            if (ev.key === 'Escape') closeLb();
            else if (ev.key === 'ArrowLeft')  { prevSlide(); restart(); }
            else if (ev.key === 'ArrowRight') { nextSlide(); restart(); }
        }
        function openLb() {
            var im = activeImg();
            if (!im) return;
            if (!box) build();
            syncLightbox();
            lastFocus = document.activeElement;
            document.body.classList.add('no-scroll');
            box.classList.add('is-open');
            closeBtn.focus();
            document.addEventListener('keydown', onKey);
        }
        function closeLb() {
            if (!box) return;
            box.classList.remove('is-open');
            document.body.classList.remove('no-scroll');
            document.removeEventListener('keydown', onKey);
            if (lastFocus && lastFocus.focus) lastFocus.focus();
        }
        if (zoom) zoom.addEventListener('click', openLb);

        paintBadge();   // pinta el badge del slide inicial en el idioma actual
        start();
    })();

    /* ---- STICKY CTA MÓVIL (mostrar al pasar el hero, ocultar cerca del pie) ---- */
    var stickyCta = document.getElementById('lnd-sticky-cta');
    var heroEl    = document.querySelector('.lnd-hero');
    if (stickyCta && heroEl) {
        var stickyOn  = false;
        var stickyRaf = 0;
        function evalStickyCta() {
            stickyRaf = 0;
            var y      = window.scrollY || window.pageYOffset || 0;
            var heroH  = heroEl.offsetHeight || window.innerHeight;
            var docH   = document.documentElement.scrollHeight;
            var atBottom = (y + window.innerHeight) >= (docH - 220);
            var show   = y > heroH * 0.55 && !atBottom;
            if (show !== stickyOn) {
                stickyOn = show;
                stickyCta.classList.toggle('is-visible', show);
            }
        }
        function onStickyScroll() {
            if (!stickyRaf) stickyRaf = requestAnimationFrame(evalStickyCta);
        }
        window.addEventListener('scroll', onStickyScroll, { passive: true });
        window.addEventListener('resize', onStickyScroll, { passive: true });
        evalStickyCta();
    }

    /* ---- DARK MODE (forest recoloring — toggle handled by theme.js) ---- */
    document.addEventListener('bilbao:theme', function (e) {
        if (bosque) bosque.recolor(e.detail === 'dark');
    });
    /* Aplicar paleta del bosque al cargar según tema guardado */
    var _initDark = document.documentElement.getAttribute('data-theme') === 'dark';
    setTimeout(function () { if (bosque) bosque.recolor(_initDark); }, 300);

    /* ---- STAT COUNTERS ---- */
    var cio = new IntersectionObserver(function (entries) {
        entries.forEach(function (e) {
            if (!e.isIntersecting) return;
            var el     = e.target;
            var target = +el.dataset.count;
            var suffix = el.dataset.suffix || '';
            var plus   = el.dataset.plus ? '+' : '';
            var dur    = 1700;
            var t0     = performance.now();
            function fmt(n) { return n >= 10000 ? n.toLocaleString('es-MX') : String(n); }
            function tick(t) {
                var p    = Math.min((t - t0) / dur, 1);
                var ease = 1 - Math.pow(1 - p, 3);
                el.textContent = fmt(Math.round(target * ease)) + suffix + plus;
                if (p < 1) requestAnimationFrame(tick);
            }
            requestAnimationFrame(tick);
            cio.unobserve(el);
        });
    }, { threshold: 0.5 });
    document.querySelectorAll('.lnd-stat__num').forEach(function (el) { cio.observe(el); });

    /* ---- CORTE DEL BOSQUE AL LLEGAR A "DESCUBRE" (rendimiento) ---- */
    var descubreSection = document.querySelector('.lnd-descubre');
    var forestCanvasEl  = document.getElementById('forest-canvas');
    if (descubreSection && forestCanvasEl) {
        var fio = new IntersectionObserver(function (entries) {
            var e = entries[0];
            if (!bosque) return;
            if (e.isIntersecting || e.boundingClientRect.top <= 0) {
                bosque.pause();
                forestCanvasEl.style.opacity = '0';
            } else if (bosque.estaPausado()) {
                forestCanvasEl.style.opacity = '';
                bosque.resume();
            }
        }, { threshold: 0, rootMargin: '0px 0px -45% 0px' });
        fio.observe(descubreSection);
    }

    /* ---- REVEALS (IntersectionObserver) ---- */
    var rEls = document.querySelectorAll('[data-reveal]');
    rEls.forEach(function (el) {
        el.style.opacity    = '0';
        el.style.transform  = 'translateY(42px)';
        el.style.transition = 'opacity .9s cubic-bezier(.2,.8,.2,1), transform .9s cubic-bezier(.2,.8,.2,1)';
    });
    var rIO = new IntersectionObserver(function (entries) {
        entries.forEach(function (e) {
            if (e.isIntersecting) {
                e.target.style.opacity   = '1';
                e.target.style.transform = 'none';
                rIO.unobserve(e.target);
            }
        });
    }, { threshold: 0.08 });
    rEls.forEach(function (el) { rIO.observe(el); });

    /* ---- MAGNETIC BUTTONS ---- */
    document.querySelectorAll('[data-magnetic]').forEach(function (btn) {
        if (btn.dataset.magDone) return;
        btn.dataset.magDone = '1';
        btn.addEventListener('mousemove', function (e) {
            var r = btn.getBoundingClientRect();
            var x = (e.clientX - r.left - r.width  / 2) * 0.3;
            var y = (e.clientY - r.top  - r.height / 2) * 0.45;
            btn.style.transform = 'translate(' + x + 'px, ' + y + 'px)';
        });
        btn.addEventListener('mouseleave', function () {
            btn.style.transform  = '';
            btn.style.transition = 'transform .4s cubic-bezier(.2,.8,.2,1)';
        });
        btn.addEventListener('mouseenter', function () {
            btn.style.transition = 'transform .1s';
        });
    });

    /* ---- ARTICLE PREVIEW ---- */
    var artRows    = document.querySelectorAll('[data-art-row]');
    var artPreview = document.getElementById('art-preview');
    function activateArtRow(idx) {
        artRows.forEach(function (r, j) {
            var on  = j === idx;
            r.style.borderLeftColor = on ? 'var(--espiritu)'      : 'transparent';
            r.style.background      = on ? 'rgba(255,255,255,.06)' : 'transparent';
            r.style.paddingLeft     = on ? '22px'                 : '14px';
            var arr = r.querySelector('.lnd-art-row__arrow');
            if (arr) {
                arr.style.opacity   = on ? '1'    : '0';
                arr.style.transform = on ? 'none' : 'translateX(-6px)';
            }
        });
        if (artPreview) {
            artPreview.querySelectorAll('[data-art-panel]').forEach(function (p, j) {
                p.style.display = j === idx ? 'flex' : 'none';
            });
        }
    }
    if (artRows.length) {
        artRows.forEach(function (r, i) {
            r.addEventListener('mouseenter', function () { activateArtRow(i); });
            r.addEventListener('focus',      function () { activateArtRow(i); });
        });
        activateArtRow(0);
    }

    /* ---- DRAG SCROLL (news) ---- */
    var newsVp = document.getElementById('news-viewport');
    if (newsVp) {
        var down = false, sx = 0, sl = 0, moved = false;
        newsVp.addEventListener('pointerdown', function (e) {
            down = true; moved = false; sx = e.clientX; sl = newsVp.scrollLeft;
            newsVp.style.cursor = 'grabbing';
        });
        window.addEventListener('pointermove', function (e) {
            if (!down) return;
            var d = e.clientX - sx;
            if (Math.abs(d) > 4) moved = true;
            newsVp.scrollLeft = sl - d;
        });
        window.addEventListener('pointerup', function () { down = false; newsVp.style.cursor = 'grab'; });
        newsVp.addEventListener('click', function (e) { if (moved) e.preventDefault(); });
    }

    /* ---- THREE.JS FOREST ---- */
    /* La escena vive en src/js/public/forest.js: se extrajo de aquí para poder
       reutilizarla en el login, que carga otro bundle. Aquí solo queda el
       cableado propio de la landing (tema y pausa al salir del hero). */
    var bosque = null;

    function initForest() {
        var canvas = document.getElementById('forest-canvas');
        bosque = window.BilbaoForest
            ? window.BilbaoForest.init(canvas, { scroll: true })
            : null;
    }

    /* ---- GSAP ---- */
    function initGsap() {
        try {
            gsap.registerPlugin(ScrollTrigger);

            gsap.to('#hero-title', { yPercent: 14, ease: 'none',
                scrollTrigger: { trigger: '.lnd-hero', start: 'top top', end: 'bottom top', scrub: true } });

            gsap.utils.toArray('.lnd-level-card').forEach(function (el, i) {
                gsap.from(el, { x: i % 2 ? 70 : -70, y: 40, opacity: 0, duration: .9, ease: 'power3.out',
                    scrollTrigger: { trigger: el, start: 'top 86%' } });
            });

            gsap.utils.toArray('[data-parallax]').forEach(function (el) {
                var sp = parseFloat(el.dataset.parallax) || 0.15;
                gsap.to(el, { yPercent: -sp * 100, ease: 'none',
                    scrollTrigger: { trigger: el, start: 'top bottom', end: 'bottom top', scrub: true } });
            });

            var stage    = document.getElementById('news-stage');
            var viewport = document.getElementById('news-viewport');
            var track    = document.getElementById('news-track');
            if (stage && viewport && track && window.innerWidth >= 820) {
                viewport.style.overflow = 'hidden';
                var amount = function () { return Math.max(0, track.scrollWidth - viewport.clientWidth + 48); };
                if (amount() > 20) {
                    gsap.to(track, { x: function () { return -amount(); }, ease: 'none',
                        scrollTrigger: { trigger: stage, start: 'top top', end: function () { return '+=' + amount(); },
                            scrub: 0.6, pin: true, anticipatePin: 1, invalidateOnRefresh: true } });
                }
            }

            window.addEventListener('load', function () { ScrollTrigger.refresh(); });
            setTimeout(function () { ScrollTrigger.refresh(); }, 1300);
        } catch (e) {
            console.warn('GSAP init failed:', e);
        }
    }

    /* ---- POLL FOR LIBS ---- */
    var t1 = 0;
    (function pollThree() {
        if (window.THREE) { initForest(); return; }
        if (t1++ < 100) setTimeout(pollThree, 80);
    })();

    var t2 = 0;
    (function pollGsap() {
        if (window.gsap && window.ScrollTrigger) { initGsap(); return; }
        if (t2++ < 100) setTimeout(pollGsap, 80);
    })();

})();
