/* blog-index
   Migrado desde el <script> embebido de views/blog/index.php */
(function () {
    if (!document.body || document.body.dataset.page !== 'blog-index') return;
        (function () {
            'use strict';
            var PERPAGE = 9;
            var currentPage = 1;
            var activeFilter = 'all';
            var searchQuery = '';

            var grid       = document.getElementById('postsGrid');
            var emptyState = document.getElementById('emptyState');
            var searchInput = document.getElementById('blogSearch');
            var clearBtn   = document.getElementById('blogSearchClear');
            var filterBtns = document.querySelectorAll('.filter-btn');

            if (!grid) return;
            var allCards = Array.from(grid.querySelectorAll('.post-card'));

            /* ── Keyboard shortcut: / focuses search ─────────── */
            document.addEventListener('keydown', function (e) {
                if (e.key === '/' && document.activeElement !== searchInput && !e.ctrlKey && !e.metaKey) {
                    e.preventDefault();
                    if (searchInput) searchInput.focus();
                }
                if (e.key === 'Escape' && document.activeElement === searchInput) {
                    searchInput.value = '';
                    searchQuery = '';
                    currentPage = 1;
                    if (clearBtn) clearBtn.classList.remove('visible');
                    render();
                    searchInput.blur();
                }
            });

            /* ── Filter + search logic ────────────────────────── */
            function getMatching() {
                return allCards.filter(function (card) {
                    var matchFilter = activeFilter === 'all' || card.dataset.category === activeFilter;
                    var q = searchQuery;
                    var matchSearch = !q ||
                        (card.dataset.title   || '').toLowerCase().indexOf(q) !== -1 ||
                        (card.dataset.excerpt || '').toLowerCase().indexOf(q) !== -1;
                    return matchFilter && matchSearch;
                });
            }

            /* ── Render ───────────────────────────────────────── */
            function render() {
                var matching   = getMatching();
                var total      = matching.length;
                var totalPages = Math.max(1, Math.ceil(total / PERPAGE));
                if (currentPage > totalPages) currentPage = totalPages;

                var start = (currentPage - 1) * PERPAGE;
                var end   = start + PERPAGE;

                allCards.forEach(function (card) {
                    var idx = matching.indexOf(card);
                    var show = idx !== -1 && idx >= start && idx < end;
                    card.classList.toggle('is-hidden', !show);
                });

                if (emptyState) emptyState.classList.toggle('show', total === 0);
                renderPagination(totalPages, total);
            }

            /* ── Pagination UI ────────────────────────────────── */
            function renderPagination(totalPages, total) {
                var nav = document.getElementById('blogPagination');
                if (!nav) {
                    nav = document.createElement('nav');
                    nav.id = 'blogPagination';
                    nav.className = 'blog-pagination';
                    nav.setAttribute('aria-label', 'Paginación de artículos');
                    grid.parentNode.insertBefore(nav, grid.nextSibling);
                }

                if (totalPages <= 1) { nav.innerHTML = ''; return; }

                var parts = [];
                parts.push('<span class="blog-page-info">' + total + ' artículo' + (total !== 1 ? 's' : '') + '</span>');

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

            /* ── Event listeners ──────────────────────────────── */
            filterBtns.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    filterBtns.forEach(function (b) { b.classList.remove('active'); });
                    btn.classList.add('active');
                    activeFilter = btn.dataset.filter;
                    currentPage = 1;
                    render();
                });
            });

            if (searchInput) {
                searchInput.addEventListener('input', function () {
                    searchQuery = searchInput.value.toLowerCase().trim();
                    if (clearBtn) clearBtn.classList.toggle('visible', searchQuery.length > 0);
                    currentPage = 1;
                    render();
                });
            }

            if (clearBtn) {
                clearBtn.addEventListener('click', function () {
                    if (searchInput) { searchInput.value = ''; searchInput.focus(); }
                    searchQuery = '';
                    clearBtn.classList.remove('visible');
                    currentPage = 1;
                    render();
                });
            }

            render();
        })();
    

        (function () {
            if (typeof THREE === 'undefined') return;
            const canvas = document.getElementById('blog-hero-bg');
            if (!canvas) return;
            const hero = canvas.parentElement;

            const renderer = new THREE.WebGLRenderer({ canvas, alpha: true, antialias: false });
            renderer.setPixelRatio(Math.min(window.devicePixelRatio, 1.5));

            const scene  = new THREE.Scene();
            const camera = new THREE.OrthographicCamera(0, 0, 0, 0, -10, 10);

            const N = 60;
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
                        const speed = 0.15 + Math.random() * 0.2;
                        const angle = Math.random() * Math.PI * 2;
                        vel.push(Math.cos(angle) * speed, Math.sin(angle) * speed);
                    }
                }
            }

            resize();

            const ptGeo  = new THREE.BufferGeometry();
            const posArr = new Float32Array(pts);
            ptGeo.setAttribute('position', new THREE.BufferAttribute(posArr, 3));
            const ptMat  = new THREE.PointsMaterial({ color: 0x4285f4, size: 2.4, transparent: true, opacity: 0.45 });
            const points = new THREE.Points(ptGeo, ptMat);
            scene.add(points);

            const linGeo = new THREE.BufferGeometry();
            const linMat = new THREE.LineBasicMaterial({ color: 0x4267ac, transparent: true, opacity: 0.14 });
            const lines  = new THREE.LineSegments(linGeo, linMat);
            scene.add(lines);

            const CONN_DIST = 130;
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

            let rafId;

            function tick() {
                rafId = requestAnimationFrame(tick);
                const p = ptGeo.attributes.position.array;
                const w = hero.offsetWidth;
                const h = hero.offsetHeight;

                for (let i = 0; i < N; i++) {
                    p[i*3]   += vel[i*2];
                    p[i*3+1] += vel[i*2+1];

                    const dx = mouseX - p[i*3];
                    const dy = mouseY - p[i*3+1];
                    const d2 = dx*dx + dy*dy;
                    if (d2 < 22500) {
                        const f = 0.0003;
                        vel[i*2]   += dx * f;
                        vel[i*2+1] += dy * f;
                        const spd = Math.sqrt(vel[i*2]*vel[i*2] + vel[i*2+1]*vel[i*2+1]);
                        if (spd > 0.7) { vel[i*2] *= 0.7/spd; vel[i*2+1] *= 0.7/spd; }
                    }

                    if (p[i*3] < 0)   { p[i*3] = 0;   vel[i*2]   =  Math.abs(vel[i*2]);   }
                    if (p[i*3] > w)   { p[i*3] = w;   vel[i*2]   = -Math.abs(vel[i*2]);   }
                    if (p[i*3+1] < 0) { p[i*3+1] = 0; vel[i*2+1] =  Math.abs(vel[i*2+1]); }
                    if (p[i*3+1] > h) { p[i*3+1] = h; vel[i*2+1] = -Math.abs(vel[i*2+1]); }
                }

                ptGeo.attributes.position.needsUpdate = true;
                buildConnections();
                renderer.render(scene, camera);
            }

            const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            if (prefersReduced) {
                buildConnections();
                renderer.render(scene, camera);
            } else {
                tick();
            }

            hero.addEventListener('mousemove', function(e) {
                const r = hero.getBoundingClientRect();
                mouseX = e.clientX - r.left;
                mouseY = hero.offsetHeight - (e.clientY - r.top);
            });
            hero.addEventListener('mouseleave', function() { mouseX = -9999; mouseY = -9999; });

            window.addEventListener('resize', function() {
                resize();
                ptGeo.attributes.position.needsUpdate = true;
                if (!rafId) buildConnections();
            }, { passive: true });

            document.addEventListener('bilbao:theme', function(e) {
                const isDark = e.detail === 'dark';
                ptMat.color.set(isDark ? 0x7dd3fc : 0x4285f4);
                linMat.color.set(isDark ? 0x93c5fd : 0x4267ac);
            });
        })();
    
})();
