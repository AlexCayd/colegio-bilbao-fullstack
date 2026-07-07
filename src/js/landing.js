/* Landing page — forest canvas, hero rotation, counters, GSAP, interactions */
(function () {
    'use strict';

    if (!document.getElementById('forest-canvas')) return;

    /* ---- HERO PHRASE ROTATION ---- */
    const phrases = [
        { a: 'La naturaleza', b: 'es nuestro ', hi: 'salón' },
        { a: 'El bosque',     b: 'es nuestro ', hi: 'maestro' },
        { a: 'Aquí se aprende', b: 'a base de ', hi: 'asombro' },
        { a: 'Crecer',        b: 'también es ', hi: 'explorar' },
    ];
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
        if (three) recolorForest(e.detail === 'dark');
    });
    /* Aplicar paleta del bosque al cargar según tema guardado */
    var _initDark = document.documentElement.getAttribute('data-theme') === 'dark';
    setTimeout(function () { if (three) recolorForest(_initDark); }, 300);

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
            if (e.isIntersecting || e.boundingClientRect.top <= 0) {
                forestPaused = true;
                forestCanvasEl.style.opacity = '0';
            } else if (forestPaused) {
                forestPaused = false;
                forestCanvasEl.style.opacity = '';
                if (forestLoop) forestLoop();
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
            r.style.borderLeftColor = on ? 'var(--bilbao)' : 'transparent';
            r.style.background      = on ? 'var(--card)'   : 'transparent';
            r.style.paddingLeft     = on ? '22px'          : '14px';
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
    var three         = null;
    var scrollP       = 0;
    var mouse         = { x: 0, y: 0 };
    var tmouse        = { x: 0, y: 0 };
    var forestPaused  = false;
    var forestLoop     = null;

    function glowTex() {
        var c = document.createElement('canvas'); c.width = c.height = 64;
        var x = c.getContext('2d');
        var g = x.createRadialGradient(32, 32, 0, 32, 32, 32);
        g.addColorStop(0,    'rgba(255,255,255,1)');
        g.addColorStop(0.35, 'rgba(255,255,255,.55)');
        g.addColorStop(1,    'rgba(255,255,255,0)');
        x.fillStyle = g; x.fillRect(0, 0, 64, 64);
        return new THREE.CanvasTexture(c);
    }
    function treeTex(kind) {
        var c = document.createElement('canvas'); c.width = 160; c.height = 320;
        var x = c.getContext('2d'); x.fillStyle = '#fff'; x.fillRect(72, 180, 16, 140);
        if (kind === 'pine') {
            [[80,20,66],[80,90,80],[80,160,94]].forEach(function (a) {
                x.beginPath(); x.moveTo(a[0],a[1]); x.lineTo(a[0]-a[2],a[1]+120); x.lineTo(a[0]+a[2],a[1]+120); x.closePath(); x.fill();
            });
        } else {
            [[80,90,60],[48,140,42],[112,140,42],[80,150,58],[60,110,38],[100,110,38]].forEach(function (a) {
                x.beginPath(); x.arc(a[0],a[1],a[2],0,Math.PI*2); x.fill();
            });
        }
        return new THREE.CanvasTexture(c);
    }
    function rayTex() {
        var c = document.createElement('canvas'); c.width = 32; c.height = 256;
        var x = c.getContext('2d');
        var g = x.createLinearGradient(0,0,0,256);
        g.addColorStop(0,    'rgba(255,255,255,0)');
        g.addColorStop(0.18, 'rgba(255,255,255,.9)');
        g.addColorStop(0.55, 'rgba(255,255,255,.35)');
        g.addColorStop(1,    'rgba(255,255,255,0)');
        x.fillStyle = g; x.fillRect(0,0,32,256);
        return new THREE.CanvasTexture(c);
    }

    function getPal(dark) {
        return dark
            ? { fog:0x081320, tree:0x16314c, treeFar:0x0f2236, fly1:0x7DC6E5, fly2:0xFFFFFF, ray:0x7DC6E5, rayOp:0.10 }
            : { fog:0xDCEAF7, tree:0x6f9cc6, treeFar:0xa9c6e3, fly1:0x4D8ABB, fly2:0x7DC6E5, ray:0xFFFFFF, rayOp:0.16 };
    }

    function recolorForest(dark) {
        if (!three) return;
        var P = getPal(dark), T = three;
        T.scene.fog.color.set(P.fog);
        T.trees.forEach(function (o) { o.sp.material.color.set(o.far ? P.treeFar : P.tree); });
        T.rays.forEach(function (o) { o.pl.material.color.set(P.ray); o.base = P.rayOp; });
        var c1 = new THREE.Color(P.fly1), c2 = new THREE.Color(P.fly2), col = T.geo.attributes.color.array;
        for (var i = 0; i < T.N; i++) {
            var c = Math.random() < 0.62 ? c1 : c2;
            col[i*3] = c.r; col[i*3+1] = c.g; col[i*3+2] = c.b;
        }
        T.geo.attributes.color.needsUpdate = true;
    }

    function initForest() {
        try {
            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
            var canvas = document.getElementById('forest-canvas');
            var w = window.innerWidth, h = window.innerHeight;
            var scene    = new THREE.Scene();
            var camera   = new THREE.PerspectiveCamera(60, w / h, 0.1, 200);
            camera.position.set(0, 1, 12);
            var renderer = new THREE.WebGLRenderer({ canvas: canvas, alpha: true, antialias: true });
            renderer.setPixelRatio(Math.min(window.devicePixelRatio, 1.6));
            renderer.setSize(w, h);

            var dark = document.documentElement.getAttribute('data-theme') === 'dark';
            var P    = getPal(dark);
            scene.fog = new THREE.FogExp2(P.fog, 0.021);

            var glow = glowTex(), texPine = treeTex('pine'), texRound = treeTex('round'), rayT = rayTex();
            var treeGroup = new THREE.Group(); scene.add(treeGroup);
            var trees = [], groundY = -7;
            var bands = [
                { n:9,  zc:-10, zs:6,  sc:7,  op:.96, far:false },
                { n:13, zc:-26, zs:10, sc:13, op:.82, far:false },
                { n:16, zc:-46, zs:14, sc:22, op:.6,  far:true  }
            ];
            bands.forEach(function (b) {
                for (var i = 0; i < b.n; i++) {
                    var tex = Math.random() < 0.62 ? texPine : texRound;
                    var mat = new THREE.SpriteMaterial({ map: tex, color: b.far ? P.treeFar : P.tree, transparent: true, opacity: b.op, depthWrite: false, fog: true });
                    var sp  = new THREE.Sprite(mat);
                    var sw  = b.sc * (0.7 + Math.random() * 0.6), sh = sw * (1.7 + Math.random() * 0.5);
                    sp.scale.set(sw, sh, 1);
                    sp.position.set((Math.random() - 0.5) * b.sc * 7.5, groundY + sh / 2, b.zc + (Math.random() - 0.5) * b.zs * 1.8);
                    sp.material.rotation = (Math.random() - 0.5) * 0.05;
                    treeGroup.add(sp);
                    trees.push({ sp: sp, phase: Math.random() * 6.28, far: b.far });
                }
            });

            var N   = 720;
            var geo = new THREE.BufferGeometry();
            var pos = new Float32Array(N * 3), col = new Float32Array(N * 3), spd = new Float32Array(N), ph = new Float32Array(N);
            var c1  = new THREE.Color(P.fly1), c2 = new THREE.Color(P.fly2);
            for (var i = 0; i < N; i++) {
                pos[i*3]   = (Math.random() - 0.5) * 90;
                pos[i*3+1] = groundY + Math.random() * 26;
                pos[i*3+2] = -Math.random() * 55 + 5;
                var c = Math.random() < 0.62 ? c1 : c2;
                col[i*3] = c.r; col[i*3+1] = c.g; col[i*3+2] = c.b;
                spd[i] = 0.05 + Math.random() * 0.12;
                ph[i]  = Math.random() * 6.28;
            }
            geo.setAttribute('position', new THREE.BufferAttribute(pos, 3));
            geo.setAttribute('color',    new THREE.BufferAttribute(col, 3));
            var fmat  = new THREE.PointsMaterial({ size: 0.42, map: glow, vertexColors: true, transparent: true, opacity: .92, depthWrite: false, blending: THREE.AdditiveBlending });
            var flies = new THREE.Points(geo, fmat);
            scene.add(flies);

            var rays = [], rayGroup = new THREE.Group(); scene.add(rayGroup);
            for (var i = 0; i < 5; i++) {
                var m  = new THREE.MeshBasicMaterial({ map: rayT, color: P.ray, transparent: true, opacity: P.rayOp, depthWrite: false, blending: THREE.AdditiveBlending, side: THREE.DoubleSide, fog: false });
                var pl = new THREE.Mesh(new THREE.PlaneGeometry(7, 46), m);
                pl.position.set((Math.random() - 0.5) * 44, 9, -16 - Math.random() * 26);
                pl.rotation.z = (Math.random() - 0.5) * 0.5;
                rayGroup.add(pl);
                rays.push({ pl: pl, phase: Math.random() * 6.28, base: P.rayOp });
            }

            three = { scene: scene, camera: camera, renderer: renderer, treeGroup: treeGroup, trees: trees, flies: flies, geo: geo, pos: pos, spd: spd, ph: ph, N: N, groundY: groundY, rays: rays, baseZ: 12 };

            window.addEventListener('mousemove', function (e) {
                tmouse.x = (e.clientX / window.innerWidth  - 0.5);
                tmouse.y = (e.clientY / window.innerHeight - 0.5);
            });
            window.addEventListener('resize', function () {
                var W = window.innerWidth, H = window.innerHeight;
                camera.aspect = W / H; camera.updateProjectionMatrix(); renderer.setSize(W, H);
            });
            window.addEventListener('scroll', function () {
                var max = document.body.scrollHeight - window.innerHeight;
                scrollP = max > 0 ? Math.min(window.scrollY / max, 1) : 0;
            }, { passive: true });

            var clock = new THREE.Clock();
            forestLoop = function loop() {
                if (forestPaused) return;
                var t = clock.getElapsedTime(), T = three;
                for (var i = 0; i < T.N; i++) {
                    T.pos[i*3+1] += Math.sin(t * 0.7 + T.ph[i]) * 0.01 + T.spd[i] * 0.02;
                    T.pos[i*3]   += Math.cos(t * 0.5 + T.ph[i]) * 0.012;
                    if (T.pos[i*3+1] > T.groundY + 30) T.pos[i*3+1] = T.groundY + 1;
                }
                T.geo.attributes.position.needsUpdate = true;
                T.flies.material.size = 0.42 + Math.sin(t * 2) * 0.08;
                T.trees.forEach(function (o) { o.sp.material.rotation = Math.sin(t * 0.6 + o.phase) * 0.025; });
                T.rays.forEach(function (o)  { o.pl.material.opacity  = o.base * (0.5 + 0.5 * Math.abs(Math.sin(t * 0.4 + o.phase))); });
                mouse.x += (tmouse.x - mouse.x) * 0.05;
                mouse.y += (tmouse.y - mouse.y) * 0.05;
                var targetZ = T.baseZ - scrollP * 42;
                T.camera.position.z += (targetZ - T.camera.position.z) * 0.06;
                T.camera.position.x += (mouse.x * 4 - T.camera.position.x) * 0.05;
                T.camera.position.y += ((1 - mouse.y * 2.5) - T.camera.position.y) * 0.05;
                T.camera.lookAt(mouse.x * 2, 0.5, T.camera.position.z - 20);
                T.renderer.render(T.scene, T.camera);
                requestAnimationFrame(forestLoop);
            };
            forestLoop();
        } catch (err) {
            console.warn('Forest canvas init failed:', err);
        }
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
