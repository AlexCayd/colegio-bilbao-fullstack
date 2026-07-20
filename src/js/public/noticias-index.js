/* noticias-index
   Migrado desde el <script> embebido de views/noticias/index.php */
(function () {
    if (!document.body || document.body.dataset.page !== 'noticias-index') return;
        (function () {
            'use strict';
            var PERPAGE = 9;
            var currentPage  = 1;
            var activeFilter = 'all';
            var searchQuery  = '';

            var grid       = document.getElementById('noticiasGrid');
            var empty      = document.getElementById('noticiasEmpty');
            var search     = document.getElementById('noticiasSearch');
            var filterBtns = document.querySelectorAll('[data-noticia-filter]');
            if (!grid) return;

            var allCards = Array.from(grid.querySelectorAll('.noticia-card'));

            function getMatching() {
                return allCards.filter(function (card) {
                    var matchCat    = activeFilter === 'all' || card.dataset.category === activeFilter;
                    var matchSearch = !searchQuery ||
                        (card.dataset.title   || '').indexOf(searchQuery) !== -1 ||
                        (card.dataset.excerpt || '').indexOf(searchQuery) !== -1;
                    return matchCat && matchSearch;
                });
            }

            function render() {
                var matching   = getMatching();
                var total      = matching.length;
                var totalPages = Math.max(1, Math.ceil(total / PERPAGE));
                if (currentPage > totalPages) currentPage = totalPages;

                var start = (currentPage - 1) * PERPAGE;
                var end   = start + PERPAGE;

                allCards.forEach(function (card) {
                    var idx  = matching.indexOf(card);
                    var show = idx !== -1 && idx >= start && idx < end;
                    card.classList.toggle('is-hidden', !show);
                });

                if (empty) empty.classList.toggle('show', total === 0);
                renderPagination(totalPages, total);
            }

            function renderPagination(totalPages, total) {
                var nav = document.getElementById('noticiasPagination');
                if (!nav) {
                    nav = document.createElement('nav');
                    nav.id = 'noticiasPagination';
                    nav.className = 'blog-pagination';
                    nav.setAttribute('aria-label', 'Paginación de noticias');
                    var emptyEl = document.getElementById('noticiasEmpty');
                    grid.parentNode.insertBefore(nav, emptyEl || grid.nextSibling);
                }

                if (totalPages <= 1) { nav.innerHTML = ''; return; }

                var parts = [];
                parts.push('<span class="blog-page-info">' + total + ' noticia' + (total !== 1 ? 's' : '') + '</span>');

                if (currentPage > 1) {
                    parts.push('<button class="blog-page-btn" data-pg="' + (currentPage - 1) + '" aria-label="Página anterior">←</button>');
                }

                var from = Math.max(1, currentPage - 2);
                var to   = Math.min(totalPages, currentPage + 2);
                if (from > 1) parts.push('<button class="blog-page-btn" data-pg="1">1</button>');
                if (from > 2) parts.push('<span class="blog-page-info">…</span>');
                for (var i = from; i <= to; i++) {
                    parts.push('<button class="blog-page-btn' + (i === currentPage ? ' active' : '') + '" data-pg="' + i + '">' + i + '</button>');
                }
                if (to < totalPages - 1) parts.push('<span class="blog-page-info">…</span>');
                if (to < totalPages)     parts.push('<button class="blog-page-btn" data-pg="' + totalPages + '">' + totalPages + '</button>');

                if (currentPage < totalPages) {
                    parts.push('<button class="blog-page-btn" data-pg="' + (currentPage + 1) + '" aria-label="Página siguiente">→</button>');
                }

                nav.innerHTML = parts.join('');
                nav.querySelectorAll('[data-pg]').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        currentPage = parseInt(btn.dataset.pg, 10);
                        render();
                        grid.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    });
                });
            }

            filterBtns.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    filterBtns.forEach(function (b) { b.classList.remove('active'); });
                    btn.classList.add('active');
                    activeFilter = btn.dataset.noticiaFilter;
                    currentPage = 1;
                    render();
                });
            });

            if (search) {
                search.addEventListener('input', function () {
                    searchQuery = search.value.toLowerCase().trim();
                    currentPage = 1;
                    render();
                });
            }

            render();
        })();
    

        (function () {
            if (typeof THREE === 'undefined') return;
            const canvas = document.getElementById('noticias-hero-bg');
            const hero   = canvas.parentElement;

            const renderer = new THREE.WebGLRenderer({ canvas, alpha: true, antialias: false });
            renderer.setPixelRatio(Math.min(window.devicePixelRatio, 1.5));

            const scene  = new THREE.Scene();
            const camera = new THREE.OrthographicCamera(0, 0, 0, 0, -10, 10);

            const N = 70;
            const pts = [];
            const vel = [];

            function resize() {
                const w = hero.offsetWidth;
                const h = hero.offsetHeight;
                renderer.setSize(w, h);
                camera.left   = 0;
                camera.right  = w;
                camera.top    = h;
                camera.bottom = 0;
                camera.updateProjectionMatrix();

                if (pts.length === 0) {
                    for (let i = 0; i < N; i++) {
                        pts.push(Math.random() * w, Math.random() * h, 0);
                        const speed = 0.18 + Math.random() * 0.22;
                        const angle = Math.random() * Math.PI * 2;
                        vel.push(Math.cos(angle) * speed, Math.sin(angle) * speed);
                    }
                }
            }

            resize();

            // Particles
            const ptGeo  = new THREE.BufferGeometry();
            const posArr = new Float32Array(pts);
            ptGeo.setAttribute('position', new THREE.BufferAttribute(posArr, 3));
            const ptMat  = new THREE.PointsMaterial({ color: 0xffffff, size: 2.8, transparent: true, opacity: 0.55 });
            const points = new THREE.Points(ptGeo, ptMat);
            scene.add(points);

            // Connections
            const linGeo = new THREE.BufferGeometry();
            const linMat = new THREE.LineBasicMaterial({ color: 0x7dd3fc, transparent: true, opacity: 0.20 });
            const lines  = new THREE.LineSegments(linGeo, linMat);
            scene.add(lines);

            const CONN_DIST = 140;
            let mouseX = -9999, mouseY = -9999;

            function buildConnections() {
                const p   = ptGeo.attributes.position.array;
                const seg = [];
                for (let i = 0; i < N; i++) {
                    for (let j = i + 1; j < N; j++) {
                        const dx = p[i*3] - p[j*3];
                        const dy = p[i*3+1] - p[j*3+1];
                        if (dx*dx + dy*dy < CONN_DIST * CONN_DIST) {
                            seg.push(p[i*3], p[i*3+1], 0, p[j*3], p[j*3+1], 0);
                        }
                    }
                }
                linGeo.setAttribute('position', new THREE.BufferAttribute(new Float32Array(seg), 3));
            }

            function tick() {
                rafId = requestAnimationFrame(tick);
                const p = ptGeo.attributes.position.array;
                const w = hero.offsetWidth;
                const h = hero.offsetHeight;

                for (let i = 0; i < N; i++) {
                    p[i*3]   += vel[i*2];
                    p[i*3+1] += vel[i*2+1];

                    // Mouse attraction (subtle)
                    const dx = mouseX - p[i*3];
                    const dy = mouseY - p[i*3+1];
                    const d2 = dx*dx + dy*dy;
                    if (d2 < 22500) {
                        const f = 0.0003;
                        vel[i*2]   += dx * f;
                        vel[i*2+1] += dy * f;
                        // cap speed
                        const spd = Math.sqrt(vel[i*2]*vel[i*2] + vel[i*2+1]*vel[i*2+1]);
                        if (spd > 0.8) { vel[i*2] *= 0.8/spd; vel[i*2+1] *= 0.8/spd; }
                    }

                    if (p[i*3] < 0)  { p[i*3] = 0;  vel[i*2]   = Math.abs(vel[i*2]); }
                    if (p[i*3] > w)  { p[i*3] = w;  vel[i*2]   = -Math.abs(vel[i*2]); }
                    if (p[i*3+1] < 0){ p[i*3+1] = 0; vel[i*2+1] = Math.abs(vel[i*2+1]); }
                    if (p[i*3+1] > h){ p[i*3+1] = h; vel[i*2+1] = -Math.abs(vel[i*2+1]); }
                }

                ptGeo.attributes.position.needsUpdate = true;
                buildConnections();
                renderer.render(scene, camera);
            }

            let rafId;
            const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            if (prefersReduced) {
                buildConnections();
                renderer.render(scene, camera);
            } else {
                tick();
            }

            hero.addEventListener('mousemove', function (e) {
                const r = hero.getBoundingClientRect();
                mouseX = e.clientX - r.left;
                mouseY = hero.offsetHeight - (e.clientY - r.top);
            });
            hero.addEventListener('mouseleave', function () {
                mouseX = -9999; mouseY = -9999;
            });

            window.addEventListener('resize', function () {
                resize();
                ptGeo.attributes.position.needsUpdate = true;
                if (!rafId) buildConnections();
            }, { passive: true });
        })();
    
})();
