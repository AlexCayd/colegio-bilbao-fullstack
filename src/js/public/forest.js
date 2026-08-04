/* forest — bosque Three.js reutilizable (partículas + árboles + rayos de luz).
   Compartido: no lleva guarda de página; lo instancia quien lo necesita.

   Nació dentro de landing.js, atado al id `forest-canvas` y sin exportar nada.
   Se extrajo al reutilizarlo en el login, que carga admin.min.js y no el bundle
   público. El comportamiento de la landing debe quedar idéntico.

   Uso:
       var bosque = window.BilbaoForest.init(canvas, { scroll: true });
       if (bosque) { bosque.recolor(true); bosque.pause(); bosque.resume(); }

   Devuelve null si no hay WebGL, si falta THREE o si el usuario pidió menos
   movimiento (`prefers-reduced-motion`): el llamador debe tener un fondo de
   respaldo en CSS, no depender de que esto pinte algo.

   Opciones:
       scroll  {bool}  la cámara se aleja al hacer scroll (landing). Por defecto false.
       dark    {bool}  arranca en paleta oscura. Por defecto lee data-theme del <html>. */
(function () {
    var PALETAS = {
        dark:  { fog:0x081320, tree:0x16314c, treeFar:0x0f2236, fly1:0x7DC6E5, fly2:0xFFFFFF, ray:0x7DC6E5, rayOp:0.10 },
        light: { fog:0xDCEAF7, tree:0x6f9cc6, treeFar:0xa9c6e3, fly1:0x4D8ABB, fly2:0x7DC6E5, ray:0xFFFFFF, rayOp:0.16 }
    };
    function getPal(dark) { return dark ? PALETAS.dark : PALETAS.light; }

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

    function init(canvas, opts) {
        opts = opts || {};
        if (!canvas || typeof THREE === 'undefined') return null;
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return null;

        try {
            var conScroll = opts.scroll === true;
            var scrollP   = 0;
            var mouse     = { x: 0, y: 0 };
            var tmouse    = { x: 0, y: 0 };
            var pausado   = false;

            var w = window.innerWidth, h = window.innerHeight;
            var scene    = new THREE.Scene();
            var camera   = new THREE.PerspectiveCamera(60, w / h, 0.1, 200);
            camera.position.set(0, 1, 12);
            var renderer = new THREE.WebGLRenderer({ canvas: canvas, alpha: true, antialias: true });
            renderer.setPixelRatio(Math.min(window.devicePixelRatio, 1.6));
            renderer.setSize(w, h);

            var dark = typeof opts.dark === 'boolean'
                ? opts.dark
                : document.documentElement.getAttribute('data-theme') === 'dark';
            var P = getPal(dark);
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
            for (var j = 0; j < 5; j++) {
                var m  = new THREE.MeshBasicMaterial({ map: rayT, color: P.ray, transparent: true, opacity: P.rayOp, depthWrite: false, blending: THREE.AdditiveBlending, side: THREE.DoubleSide, fog: false });
                var pl = new THREE.Mesh(new THREE.PlaneGeometry(7, 46), m);
                pl.position.set((Math.random() - 0.5) * 44, 9, -16 - Math.random() * 26);
                pl.rotation.z = (Math.random() - 0.5) * 0.5;
                rayGroup.add(pl);
                rays.push({ pl: pl, phase: Math.random() * 6.28, base: P.rayOp });
            }

            window.addEventListener('mousemove', function (e) {
                tmouse.x = (e.clientX / window.innerWidth  - 0.5);
                tmouse.y = (e.clientY / window.innerHeight - 0.5);
            });
            window.addEventListener('resize', function () {
                var W = window.innerWidth, H = window.innerHeight;
                camera.aspect = W / H; camera.updateProjectionMatrix(); renderer.setSize(W, H);
            });
            if (conScroll) {
                window.addEventListener('scroll', function () {
                    var max = document.body.scrollHeight - window.innerHeight;
                    scrollP = max > 0 ? Math.min(window.scrollY / max, 1) : 0;
                }, { passive: true });
            }

            var baseZ = 12;
            var clock = new THREE.Clock();
            function loop() {
                if (pausado) return;
                var t = clock.getElapsedTime();
                for (var k = 0; k < N; k++) {
                    pos[k*3+1] += Math.sin(t * 0.7 + ph[k]) * 0.01 + spd[k] * 0.02;
                    pos[k*3]   += Math.cos(t * 0.5 + ph[k]) * 0.012;
                    if (pos[k*3+1] > groundY + 30) pos[k*3+1] = groundY + 1;
                }
                geo.attributes.position.needsUpdate = true;
                flies.material.size = 0.42 + Math.sin(t * 2) * 0.08;
                trees.forEach(function (o) { o.sp.material.rotation = Math.sin(t * 0.6 + o.phase) * 0.025; });
                rays.forEach(function (o)  { o.pl.material.opacity  = o.base * (0.5 + 0.5 * Math.abs(Math.sin(t * 0.4 + o.phase))); });
                mouse.x += (tmouse.x - mouse.x) * 0.05;
                mouse.y += (tmouse.y - mouse.y) * 0.05;
                var targetZ = baseZ - scrollP * 42;
                camera.position.z += (targetZ - camera.position.z) * 0.06;
                camera.position.x += (mouse.x * 4 - camera.position.x) * 0.05;
                camera.position.y += ((1 - mouse.y * 2.5) - camera.position.y) * 0.05;
                camera.lookAt(mouse.x * 2, 0.5, camera.position.z - 20);
                renderer.render(scene, camera);
                requestAnimationFrame(loop);
            }
            loop();

            return {
                /** Repinta la escena con la paleta clara u oscura (lo llama el theme switcher). */
                recolor: function (esOscuro) {
                    var Q = getPal(esOscuro);
                    scene.fog.color.set(Q.fog);
                    trees.forEach(function (o) { o.sp.material.color.set(o.far ? Q.treeFar : Q.tree); });
                    rays.forEach(function (o) { o.pl.material.color.set(Q.ray); o.base = Q.rayOp; });
                    var d1 = new THREE.Color(Q.fly1), d2 = new THREE.Color(Q.fly2);
                    var arr = geo.attributes.color.array;
                    for (var i = 0; i < N; i++) {
                        var c = Math.random() < 0.62 ? d1 : d2;
                        arr[i*3] = c.r; arr[i*3+1] = c.g; arr[i*3+2] = c.b;
                    }
                    geo.attributes.color.needsUpdate = true;
                },
                /** Detiene el render (p. ej. cuando el hero sale de pantalla). */
                pause:  function () { pausado = true; },
                resume: function () { if (pausado) { pausado = false; loop(); } },
                estaPausado: function () { return pausado; }
            };
        } catch (err) {
            console.warn('Forest canvas init failed:', err);
            return null;
        }
    }

    window.BilbaoForest = { init: init };
})();
