/* admin-horario-ahora — «Ahora / Sigue» sobre la columna del día (views/blog/_horario-ahora.php).

   Se activa por existencia de `[data-hoy]`, no por `data-page`: el mismo partial vive en
   el home y en la ficha del colaborador, y una guarda de página lo apagaría en una de las
   dos.

   ⚠️ El reloj del navegador no es de fiar —un portátil con la hora mal puesta marcaría la
   clase equivocada, y aquí eso significa que alguien cree que le toca otra cosa—, así que
   al cargar se mide la DERIVA contra la hora del servidor (`data-ahora`) y se corrige en
   cada tic. El servidor sella su hora en la zona del colegio, que es la que manda.

   Tic de 30 s: la barra de progreso de una clase de 50 minutos no necesita más, y así no
   se despierta la pestaña cada segundo. */
(function () {
    var raiz = document.querySelector('[data-hoy]');
    if (!raiz) return;

    var filas = Array.prototype.slice.call(raiz.querySelectorAll('[data-hoy-fila]'));
    var reloj = raiz.querySelector('[data-hoy-reloj]');

    /* Deriva entre el reloj del cliente y el del servidor, en milisegundos. Si el atributo
       falta o no parsea, se asume 0 y se sigue con la hora local: degradar a "puede que
       esté unos minutos desviado" es mejor que no pintar nada. */
    var desfase = 0;
    var sello = raiz.getAttribute('data-ahora');
    if (sello) {
        var t = new Date(sello.replace(' ', 'T')).getTime();
        if (!isNaN(t)) desfase = t - Date.now();
    }

    function ahora() { return new Date(Date.now() + desfase); }

    /** 'HH:MM' → minutos desde medianoche. */
    function min(hhmm) {
        var p = String(hhmm || '').split(':');
        return (parseInt(p[0], 10) || 0) * 60 + (parseInt(p[1], 10) || 0);
    }

    function dosCifras(n) { return (n < 10 ? '0' : '') + n; }

    // Refs del bloque de foco. Si no existe (día sin clases) solo se mueve el reloj.
    var foco   = raiz.querySelector('[data-hoy-foco]');
    var elAh   = raiz.querySelector('[data-hoy-ahora]');
    var elSg   = raiz.querySelector('[data-hoy-sigue]');
    var elFin  = raiz.querySelector('[data-hoy-fin]');
    var ahCla  = raiz.querySelector('[data-hoy-ahora-clase]');
    var ahMet  = raiz.querySelector('[data-hoy-ahora-meta]');
    var sgCla  = raiz.querySelector('[data-hoy-sigue-clase]');
    var sgMet  = raiz.querySelector('[data-hoy-sigue-meta]');
    var barra  = raiz.querySelector('[data-hoy-barra]');
    var fill   = raiz.querySelector('[data-hoy-barra-fill]');
    var restan = raiz.querySelector('[data-hoy-restan]');

    /** Lee de la fila lo que hay que enseñar arriba. */
    function datos(fila) {
        var mat = fila.querySelector('.hoy__mat');
        var sec = fila.querySelector('.hoy__sec');
        return {
            clase: mat ? mat.textContent.trim() : '',
            meta:  (sec ? sec.textContent.trim() + ' · ' : '') +
                   fila.dataset.ini + '–' + fila.dataset.fin,
            color: fila.style.getPropertyValue('--c') || '#94a3b8',
            // Un receso o una hora libre no son "su clase": no se anuncian como la
            // siguiente, porque lo que se quiere saber es cuándo hay que estar en un aula.
            lectiva: !fila.classList.contains('hoy__fila--receso') &&
                     !fila.classList.contains('hoy__fila--libre')
        };
    }

    function pintar() {
        var n = ahora();
        var m = n.getHours() * 60 + n.getMinutes();

        if (reloj) reloj.textContent = dosCifras(n.getHours()) + ':' + dosCifras(n.getMinutes());
        if (!filas.length || !foco) return;

        var enCurso = null, siguiente = null;

        filas.forEach(function (f) {
            var a = min(f.dataset.ini), b = min(f.dataset.fin);
            var activa = m >= a && m < b;
            f.classList.toggle('is-now', activa);
            // `is-pasada` atenúa lo que ya ocurrió: la columna se lee de arriba abajo y
            // sin esto hay que buscar a ojo dónde está uno.
            f.classList.toggle('is-pasada', m >= b);

            if (activa && !enCurso) enCurso = f;
            if (!siguiente && m < a && datos(f).lectiva) siguiente = f;
        });

        foco.hidden = false;

        // ── En curso ──
        if (enCurso && datos(enCurso).lectiva) {
            var d = datos(enCurso);
            var a = min(enCurso.dataset.ini), b = min(enCurso.dataset.fin);
            var pct = b > a ? Math.round((m - a) / (b - a) * 100) : 0;
            var quedan = Math.max(0, b - m);

            elAh.hidden = false;
            elAh.style.setProperty('--c', d.color);
            ahCla.textContent = d.clase;
            ahMet.textContent = d.meta;
            if (fill) fill.style.width = pct + '%';
            if (barra) barra.setAttribute('aria-valuenow', String(pct));
            restan.textContent = quedan === 0
                ? 'Termina ahora'
                : 'Quedan ' + quedan + (quedan === 1 ? ' minuto' : ' minutos');
        } else {
            elAh.hidden = true;
        }

        // ── Siguiente ──
        if (siguiente) {
            var s = datos(siguiente);
            var falta = min(siguiente.dataset.ini) - m;
            elSg.hidden = false;
            elSg.style.setProperty('--c', s.color);
            sgCla.textContent = s.clase;
            sgMet.textContent = s.meta + (falta > 0
                ? ' · en ' + (falta >= 60
                    ? Math.floor(falta / 60) + ' h ' + (falta % 60) + ' min'
                    : falta + ' min')
                : '');
        } else {
            elSg.hidden = true;
        }

        // Ni clase ahora ni ninguna por delante: se acabó la jornada.
        elFin.hidden = !(elAh.hidden && elSg.hidden);
    }

    pintar();
    setInterval(pintar, 30000);

    /* Al volver a la pestaña se repinta de inmediato: los temporizadores se estrangulan en
       segundo plano, así que tras un rato minimizado la tarjeta podía estar media hora
       desfasada justo en el momento de mirarla. */
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) pintar();
    });
})();
