/* estaticas-comunidad-_bg
   Migrado desde el <script> embebido de views/estaticas/comunidad/_bg.php */
/* Compartido: se activa por existencia de sus elementos */
(function () {
    (function () {
        var canvas = document.querySelector('[data-comunidad-bg]');
        if (!canvas) return;

        // Respeta accesibilidad y ausencia de WebGL
        var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (reduce || typeof THREE === 'undefined') { canvas.style.display = 'none'; return; }

        var host = canvas.parentElement;
        var colors = [];
        try { colors = JSON.parse(canvas.dataset.colors || '[]'); } catch (e) {}
        if (!colors.length) colors = ['#4d8abb', '#2e4b8a', '#46bdc6'];

        var renderer;
        try {
            renderer = new THREE.WebGLRenderer({ canvas: canvas, alpha: true, antialias: true });
        } catch (e) { canvas.style.display = 'none'; return; }
        renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));

        var scene  = new THREE.Scene();
        var camera = new THREE.PerspectiveCamera(60, 1, 0.1, 100);
        camera.position.z = 26;

        // Textura suave circular (glow)
        var tex = (function () {
            var c = document.createElement('canvas'); c.width = c.height = 64;
            var g = c.getContext('2d');
            var grd = g.createRadialGradient(32, 32, 0, 32, 32, 32);
            grd.addColorStop(0, 'rgba(255,255,255,1)');
            grd.addColorStop(0.4, 'rgba(255,255,255,0.6)');
            grd.addColorStop(1, 'rgba(255,255,255,0)');
            g.fillStyle = grd; g.fillRect(0, 0, 64, 64);
            return new THREE.CanvasTexture(c);
        })();

        // Nube de orbes flotantes
        var N = 90;
        var geo = new THREE.BufferGeometry();
        var pos = new Float32Array(N * 3);
        var col = new Float32Array(N * 3);
        var speeds = [];
        var C = new THREE.Color();
        for (var i = 0; i < N; i++) {
            pos[i * 3]     = (Math.random() - 0.5) * 60;
            pos[i * 3 + 1] = (Math.random() - 0.5) * 40;
            pos[i * 3 + 2] = (Math.random() - 0.5) * 30;
            C.set(colors[i % colors.length]);
            col[i * 3] = C.r; col[i * 3 + 1] = C.g; col[i * 3 + 2] = C.b;
            speeds.push(0.15 + Math.random() * 0.35);
        }
        geo.setAttribute('position', new THREE.BufferAttribute(pos, 3));
        geo.setAttribute('color', new THREE.BufferAttribute(col, 3));

        var mat = new THREE.PointsMaterial({
            size: 3.1, map: tex, vertexColors: true, transparent: true,
            opacity: 0.8, depthWrite: false, blending: THREE.AdditiveBlending
        });
        var points = new THREE.Points(geo, mat);
        scene.add(points);

        // Formas wireframe flotantes (más carácter en el hero) — opcional
        var shapes = [];
        var shapeDefs = (canvas.dataset.shapes === '0') ? [] : [
            { geo: new THREE.IcosahedronGeometry(5, 0),   pos: [-16, 6, -6],  col: colors[0] },
            { geo: new THREE.TorusGeometry(4, 1.1, 8, 20), pos: [17, -7, -4],  col: colors[1 % colors.length] },
            { geo: new THREE.OctahedronGeometry(3.4, 0),  pos: [10, 9, -8],   col: colors[2 % colors.length] }
        ];
        shapeDefs.forEach(function (d) {
            var m = new THREE.MeshBasicMaterial({ color: new THREE.Color(d.col), wireframe: true, transparent: true, opacity: 0.28 });
            var mesh = new THREE.Mesh(d.geo, m);
            mesh.position.set(d.pos[0], d.pos[1], d.pos[2]);
            mesh.rotation.set(Math.random(), Math.random(), 0);
            scene.add(mesh);
            shapes.push({ mesh: mesh, sx: 0.001 + Math.random() * 0.003, sy: 0.001 + Math.random() * 0.003 });
        });

        function resize() {
            var w = host.clientWidth, h = host.clientHeight;
            renderer.setSize(w, h, false);
            camera.aspect = w / h; camera.updateProjectionMatrix();
        }
        resize();
        window.addEventListener('resize', resize);
        window.addEventListener('load', resize);
        // El script corre durante el parseo (antes de que el hero tenga su altura final):
        // observar el host garantiza que el canvas tome el tamaño correcto.
        if (window.ResizeObserver) { new ResizeObserver(resize).observe(host); }

        // Parallax sutil con el mouse
        var mx = 0, my = 0, cx = 0, cy = 0;
        host.addEventListener('pointermove', function (e) {
            var r = host.getBoundingClientRect();
            mx = ((e.clientX - r.left) / r.width - 0.5) * 2;
            my = ((e.clientY - r.top) / r.height - 0.5) * 2;
        });
        host.addEventListener('pointerleave', function () { mx = 0; my = 0; });

        // Reacción al scroll: la nube gira/acerca según el desplazamiento del hero
        var scroll = 0;
        function onScroll() {
            var r = host.getBoundingClientRect();
            scroll = Math.max(0, Math.min(1, -r.top / (r.height || 1)));
        }
        window.addEventListener('scroll', onScroll, { passive: true });
        onScroll();

        var t = 0, raf;
        function animate() {
            t += 0.005;
            var p = geo.attributes.position.array;
            for (var i = 0; i < N; i++) {
                p[i * 3 + 1] += speeds[i] * 0.03;
                if (p[i * 3 + 1] > 22) p[i * 3 + 1] = -22;
            }
            geo.attributes.position.needsUpdate = true;
            points.rotation.y = Math.sin(t * 0.4) * 0.15 + scroll * 0.8;
            points.rotation.x = scroll * 0.4;
            shapes.forEach(function (s) { s.mesh.rotation.x += s.sx; s.mesh.rotation.y += s.sy; });
            // Cámara: sigue al mouse y se acerca ligeramente con el scroll
            cx += (mx * 3 - cx) * 0.05;
            cy += (-my * 2 - cy) * 0.05;
            camera.position.x = cx;
            camera.position.y = cy;
            camera.position.z = 26 - scroll * 8;
            camera.lookAt(scene.position);
            renderer.render(scene, camera);
            raf = requestAnimationFrame(animate);
        }
        animate();

        // Pausar fuera de viewport para ahorrar recursos
        if ('IntersectionObserver' in window) {
            new IntersectionObserver(function (es) {
                es.forEach(function (e) {
                    if (e.isIntersecting) { if (!raf) animate(); }
                    else { cancelAnimationFrame(raf); raf = null; }
                });
            }, { threshold: 0 }).observe(host);
        }
    })();
})();
