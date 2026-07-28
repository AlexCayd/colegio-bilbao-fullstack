/* estaticas-comunidad-_bg
   Fondo Three.js reutilizable del hero de Comunidad.
   Escena 'bosque' (escuela en el bosque, colores institucionales) o 'orbes' (nube clásica).
   Compartido: se activa por existencia de [data-comunidad-bg]; sin guarda de página. */
(function () {
    var canvas = document.querySelector('[data-comunidad-bg]');
    if (!canvas) return;

    // Accesibilidad y ausencia de WebGL → sin animación
    var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (reduce || typeof THREE === 'undefined') { canvas.style.display = 'none'; return; }

    var host = canvas.parentElement;
    var colors = [];
    try { colors = JSON.parse(canvas.dataset.colors || '[]'); } catch (e) {}
    if (!colors.length) colors = ['#4D8ABB', '#7DC6E5', '#46bdc6', '#374C69', '#F1C400'];
    var scene3 = (canvas.dataset.scene || 'bosque');

    var renderer;
    try {
        renderer = new THREE.WebGLRenderer({ canvas: canvas, alpha: true, antialias: true });
    } catch (e) { canvas.style.display = 'none'; return; }
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));

    var scene  = new THREE.Scene();
    var camera = new THREE.PerspectiveCamera(60, 1, 0.1, 200);
    camera.position.set(0, 4, 30);

    var C = new THREE.Color();

    // ── Textura circular suave (glow) para las partículas ──
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

    var animateFns = [];   // callbacks por frame(t, scroll)

    if (scene3 === 'bosque') {
        buildForest();
    } else {
        buildOrbs();
    }

    // Partículas de polen / luciérnagas (ambas escenas)
    (function buildPollen() {
        var N = scene3 === 'bosque' ? 70 : 90;
        var geo = new THREE.BufferGeometry();
        var pos = new Float32Array(N * 3);
        var col = new Float32Array(N * 3);
        var speeds = [];
        for (var i = 0; i < N; i++) {
            pos[i * 3]     = (Math.random() - 0.5) * 70;
            pos[i * 3 + 1] = (Math.random() - 0.5) * 42;
            pos[i * 3 + 2] = (Math.random() - 0.5) * 34;
            C.set(colors[i % colors.length]);
            col[i * 3] = C.r; col[i * 3 + 1] = C.g; col[i * 3 + 2] = C.b;
            speeds.push(0.15 + Math.random() * 0.4);
        }
        geo.setAttribute('position', new THREE.BufferAttribute(pos, 3));
        geo.setAttribute('color', new THREE.BufferAttribute(col, 3));
        var mat = new THREE.PointsMaterial({
            size: scene3 === 'bosque' ? 2.2 : 3.1, map: tex, vertexColors: true,
            transparent: true, opacity: 0.85, depthWrite: false, blending: THREE.AdditiveBlending
        });
        var points = new THREE.Points(geo, mat);
        scene.add(points);
        animateFns.push(function (t, scroll) {
            var p = geo.attributes.position.array;
            for (var i = 0; i < N; i++) {
                p[i * 3 + 1] += speeds[i] * 0.03;
                if (p[i * 3 + 1] > 22) p[i * 3 + 1] = -22;
            }
            geo.attributes.position.needsUpdate = true;
            points.rotation.y = Math.sin(t * 0.4) * 0.1 + scroll * 0.5;
        });
    })();

    // ── ESCENA BOSQUE ──────────────────────────────────────────────
    function buildForest() {
        scene.fog = new THREE.FogExp2(0x0f2036, 0.021);

        // Luces
        scene.add(new THREE.AmbientLight(0xbcd6ef, 0.75));
        var sun = new THREE.DirectionalLight(0xffffff, 0.9);
        sun.position.set(-8, 16, 10);
        scene.add(sun);
        // Destello cálido (amarillo institucional) como "rayo de sol" entre los árboles
        var glow = new THREE.PointLight(0xF1C400, 1.1, 90);
        glow.position.set(10, 10, -6);
        scene.add(glow);

        // Suelo del claro
        var groundMat = new THREE.MeshLambertMaterial({ color: 0x24506f, transparent: true, opacity: 0.55 });
        var ground = new THREE.Mesh(new THREE.CircleGeometry(60, 40), groundMat);
        ground.rotation.x = -Math.PI / 2;
        ground.position.y = -9;
        scene.add(ground);

        // Paleta de follaje: variantes teal/azul del branding (bosque estilizado)
        var foliage = ['#2f7d8f', '#46bdc6', '#3a6ea5', '#4D8ABB', '#2e6f7d', '#5aa0b8'];
        var trunkColor = new THREE.Color('#374C69');

        // Filas de coníferas low-poly, con parallax por profundidad
        var rows = [
            { z: -26, count: 9,  scale: 2.4, y: -9, opacity: 0.55, speed: 0.10 },
            { z: -14, count: 7,  scale: 1.9, y: -9, opacity: 0.78, speed: 0.22 },
            { z: -2,  count: 5,  scale: 1.5, y: -9, opacity: 1.0,  speed: 0.40 }
        ];
        var layers = [];
        rows.forEach(function (row, ri) {
            var group = new THREE.Group();
            var spread = 70;
            for (var i = 0; i < row.count; i++) {
                var tree = makeTree(foliage, trunkColor, row.opacity);
                var s = row.scale * (0.75 + Math.random() * 0.5);
                tree.scale.setScalar(s);
                tree.position.set(
                    (i / (row.count - 1) - 0.5) * spread + (Math.random() - 0.5) * 6,
                    row.y,
                    row.z + (Math.random() - 0.5) * 4
                );
                tree.userData.sway = 0.02 + Math.random() * 0.03;
                tree.userData.phase = Math.random() * Math.PI * 2;
                group.add(tree);
            }
            scene.add(group);
            layers.push({ group: group, depth: (ri + 1) });
        });

        animateFns.push(function (t, scroll, mx) {
            layers.forEach(function (l) {
                // Parallax horizontal por capa según el mouse
                l.group.position.x = mx * l.depth * 0.9;
                l.group.children.forEach(function (tree) {
                    // Balanceo suave de las copas
                    tree.rotation.z = Math.sin(t * 1.2 + tree.userData.phase) * tree.userData.sway;
                });
            });
            // El claro se "abre" al hacer scroll (la cámara avanza)
            camera.position.z = 30 - scroll * 12;
            camera.position.y = 4 + scroll * 2;
        });
    }

    // Un árbol conífera low-poly = tronco + conos de follaje apilados
    function makeTree(foliagePalette, trunkColor, opacity) {
        var g = new THREE.Group();
        var trunkMat = new THREE.MeshLambertMaterial({ color: trunkColor, transparent: true, opacity: opacity });
        var trunk = new THREE.Mesh(new THREE.CylinderGeometry(0.18, 0.28, 1.6, 6), trunkMat);
        trunk.position.y = 0.8;
        g.add(trunk);

        var col = new THREE.Color(foliagePalette[Math.floor(Math.random() * foliagePalette.length)]);
        var tiers = 3;
        for (var k = 0; k < tiers; k++) {
            var mat = new THREE.MeshLambertMaterial({ color: col, flatShading: true, transparent: true, opacity: opacity });
            var r = 1.5 - k * 0.35;
            var h = 1.6;
            var cone = new THREE.Mesh(new THREE.ConeGeometry(r, h, 7), mat);
            cone.position.y = 1.7 + k * 1.05;
            g.add(cone);
        }
        return g;
    }

    // ── ESCENA ORBES (clásica) ─────────────────────────────────────
    function buildOrbs() {
        camera.position.set(0, 0, 26);
        var shapeDefs = (canvas.dataset.shapes === '0') ? [] : [
            { geo: new THREE.IcosahedronGeometry(5, 0),    pos: [-16, 6, -6], col: colors[0] },
            { geo: new THREE.TorusGeometry(4, 1.1, 8, 20), pos: [17, -7, -4], col: colors[1 % colors.length] },
            { geo: new THREE.OctahedronGeometry(3.4, 0),   pos: [10, 9, -8],  col: colors[2 % colors.length] }
        ];
        var shapes = [];
        shapeDefs.forEach(function (d) {
            var m = new THREE.MeshBasicMaterial({ color: new THREE.Color(d.col), wireframe: true, transparent: true, opacity: 0.28 });
            var mesh = new THREE.Mesh(d.geo, m);
            mesh.position.set(d.pos[0], d.pos[1], d.pos[2]);
            mesh.rotation.set(Math.random(), Math.random(), 0);
            scene.add(mesh);
            shapes.push({ mesh: mesh, sx: 0.001 + Math.random() * 0.003, sy: 0.001 + Math.random() * 0.003 });
        });
        animateFns.push(function (t, scroll) {
            shapes.forEach(function (s) { s.mesh.rotation.x += s.sx; s.mesh.rotation.y += s.sy; });
            camera.position.z = 26 - scroll * 8;
        });
    }

    // ── Resize ──
    function resize() {
        var w = host.clientWidth, h = host.clientHeight;
        renderer.setSize(w, h, false);
        camera.aspect = w / (h || 1); camera.updateProjectionMatrix();
    }
    resize();
    window.addEventListener('resize', resize);
    window.addEventListener('load', resize);
    if (window.ResizeObserver) { new ResizeObserver(resize).observe(host); }

    // ── Interacción: mouse + scroll ──
    var mx = 0, my = 0, cx = 0, cy = 0;
    host.addEventListener('pointermove', function (e) {
        var r = host.getBoundingClientRect();
        mx = ((e.clientX - r.left) / r.width - 0.5) * 2;
        my = ((e.clientY - r.top) / r.height - 0.5) * 2;
    });
    host.addEventListener('pointerleave', function () { mx = 0; my = 0; });

    var scroll = 0;
    function onScroll() {
        var r = host.getBoundingClientRect();
        scroll = Math.max(0, Math.min(1, -r.top / (r.height || 1)));
    }
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();

    var baseZ = camera.position.z, baseY = camera.position.y;
    var t = 0, raf;
    function animate() {
        t += 0.005;
        cx += (mx - cx) * 0.05;
        cy += (-my - cy) * 0.05;
        for (var i = 0; i < animateFns.length; i++) animateFns[i](t, scroll, cx);
        // Ligero parallax de cámara con el mouse (encima de lo que fije cada escena)
        camera.position.x = cx * 2.2;
        camera.lookAt(0, scene3 === 'bosque' ? -1 : 0, 0);
        renderer.render(scene, camera);
        raf = requestAnimationFrame(animate);
    }
    animate();

    // Pausar fuera de viewport
    if ('IntersectionObserver' in window) {
        new IntersectionObserver(function (es) {
            es.forEach(function (e) {
                if (e.isIntersecting) { if (!raf) animate(); }
                else { cancelAnimationFrame(raf); raf = null; }
            });
        }, { threshold: 0 }).observe(host);
    }
})();
