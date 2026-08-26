/* admin-datepicker — selector de fecha propio del panel (.bilbao-date).
   Componente compartido: no lleva guarda de página, se activa por `[data-datepicker]`.
   Sustituye a <input type="date"> para que la fecha se vea igual en todos los navegadores
   y para poder deshabilitar fines de semana (no hay clases que cubrir).

   La semana empieza en LUNES, igual que el calendario del home (blog-home.js).
   La cabecera de días está en el markup, no aquí: ver views/blog/_campo-fecha.php.

   Tres vistas encadenadas — días → meses → años — porque solo con ‹ › de mes en mes
   una fecha de nacimiento de 1990 costaba cientos de clics. Pulsar la cabecera sube
   de nivel; elegir baja. Tres clics para cualquier fecha.

   Markup esperado (lo genera views/blog/_campo-fecha.php):
     <div class="bilbao-date" data-datepicker data-min="2026-07-26" data-habiles="1">
        <input type="hidden" name="fecha" value="2026-07-27" data-date-value>
        <button type="button" class="bilbao-date__field" data-date-trigger>…</button>
        <div class="bilbao-date__pop" data-date-pop hidden>…
            <button data-date-month data-date-salto>…</button>
            <div data-date-panel="dias">…<div data-date-grid></div></div>
            <div data-date-panel="meses"></div>
            <div data-date-panel="anios"></div>
            <button data-date-hoy>Hoy</button>
            <button data-date-clear>Limpiar</button>   ← opcional, para filtros
        </div>
     </div>

   El hidden emite un evento `change` al elegir día o al limpiar, para que la vista
   reaccione (recargar el horario del profesor, reenviar el formulario de filtros…). */
(function () {
    var MESES = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
    var MESES_CORTO = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
    var DIAS  = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
    var abiertos = [];

    function pad(n) { return String(n).padStart(2, '0'); }
    function aYmd(d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); }
    function deYmd(s) {
        var p = /^(\d{4})-(\d{2})-(\d{2})$/.exec(String(s || ''));
        if (!p) return null;
        return new Date(+p[1], +p[2] - 1, +p[3]);
    }
    /** "Martes, 14 de julio de 2026" */
    function etiquetaLarga(d) {
        return DIAS[d.getDay()] + ', ' + d.getDate() + ' de ' + MESES[d.getMonth()].toLowerCase() + ' de ' + d.getFullYear();
    }

    function montar(root) {
        var hidden  = root.querySelector('[data-date-value]');
        var trigger = root.querySelector('[data-date-trigger]');
        var label   = root.querySelector('[data-date-label]');
        var pop     = root.querySelector('[data-date-pop]');
        if (!hidden || !trigger || !label || !pop) return;

        // data-habiles="1" → solo lunes a viernes seleccionables
        var soloHabiles = root.dataset.habiles === '1';
        var min = deYmd(root.dataset.min);
        var max = deYmd(root.dataset.max);

        var hoy   = new Date(); hoy.setHours(0, 0, 0, 0);
        var sel   = deYmd(hidden.value);
        var vista = new Date((sel || hoy).getFullYear(), (sel || hoy).getMonth(), 1);
        var modo  = 'dias';           // dias | meses | anios
        var baseAnios = 0;            // primer año del bloque de 12 en la vista de años

        var cabecera = pop.querySelector('[data-date-month]');
        var panels = {
            dias:  pop.querySelector('[data-date-panel="dias"]'),
            meses: pop.querySelector('[data-date-panel="meses"]'),
            anios: pop.querySelector('[data-date-panel="anios"]')
        };

        function bloqueado(d) {
            if (soloHabiles && (d.getDay() === 0 || d.getDay() === 6)) return true;
            if (min && d < min) return true;
            if (max && d > max) return true;
            return false;
        }

        /** ¿Queda algún día seleccionable en este mes? Para atenuar meses vacíos. */
        function mesBloqueado(y, m) {
            if (min && new Date(y, m + 1, 0) < min) return true;
            if (max && new Date(y, m, 1) > max) return true;
            return false;
        }
        function anioBloqueado(y) {
            if (min && new Date(y, 11, 31) < min) return true;
            if (max && new Date(y, 0, 1) > max) return true;
            return false;
        }

        function pintarEtiqueta() {
            if (sel) {
                label.textContent = etiquetaLarga(sel);
                root.classList.add('has-value');
            } else {
                label.textContent = 'Elige una fecha';
                root.classList.remove('has-value');
            }
        }

        function pintarDias() {
            var y = vista.getFullYear(), m = vista.getMonth();
            var grid = panels.dias.querySelector('[data-date-grid]');
            grid.innerHTML = '';

            // Semana que empieza en DOMINGO, igual que los calendarios .bilbao-cal:
            // getDay() ya da 0 para domingo, así que el offset es directo.
            var primero = new Date(y, m, 1).getDay();
            var dias = new Date(y, m + 1, 0).getDate();

            for (var i = 0; i < primero; i++) {
                var hueco = document.createElement('span');
                hueco.className = 'bilbao-date__cell bilbao-date__cell--empty';
                grid.appendChild(hueco);
            }
            for (var d = 1; d <= dias; d++) {
                var fecha = new Date(y, m, d);
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'bilbao-date__cell';
                btn.textContent = d;

                if (fecha.getTime() === hoy.getTime()) btn.classList.add('is-today');
                if (sel && aYmd(fecha) === aYmd(sel))  btn.classList.add('is-selected');

                if (bloqueado(fecha)) {
                    btn.disabled = true;
                    btn.classList.add('is-off');
                    btn.title = (soloHabiles && (fecha.getDay() === 0 || fecha.getDay() === 6))
                        ? 'Fin de semana: no hay clases'
                        : 'Fuera del rango permitido';
                } else {
                    btn.dataset.ymd = aYmd(fecha);
                }
                grid.appendChild(btn);
            }
        }

        function pintarMeses() {
            var y = vista.getFullYear();
            panels.meses.innerHTML = '';
            for (var m = 0; m < 12; m++) {
                var b = document.createElement('button');
                b.type = 'button';
                b.className = 'bilbao-date__opt';
                b.textContent = MESES_CORTO[m];
                b.dataset.mes = m;
                if (m === vista.getMonth()) b.classList.add('is-selected');
                if (sel && sel.getFullYear() === y && sel.getMonth() === m) b.classList.add('is-selected');
                if (mesBloqueado(y, m)) { b.disabled = true; b.classList.add('is-off'); }
                panels.meses.appendChild(b);
            }
        }

        function pintarAnios() {
            panels.anios.innerHTML = '';
            for (var i = 0; i < 12; i++) {
                var y = baseAnios + i;
                var b = document.createElement('button');
                b.type = 'button';
                b.className = 'bilbao-date__opt';
                b.textContent = y;
                b.dataset.anio = y;
                if (y === vista.getFullYear()) b.classList.add('is-selected');
                if (anioBloqueado(y)) { b.disabled = true; b.classList.add('is-off'); }
                panels.anios.appendChild(b);
            }
        }

        function pintar() {
            panels.dias.hidden  = modo !== 'dias';
            panels.meses.hidden = modo !== 'meses';
            panels.anios.hidden = modo !== 'anios';

            if (modo === 'dias') {
                cabecera.textContent = MESES[vista.getMonth()] + ' ' + vista.getFullYear();
                pintarDias();
            } else if (modo === 'meses') {
                cabecera.textContent = vista.getFullYear();
                pintarMeses();
            } else {
                cabecera.textContent = baseAnios + ' – ' + (baseAnios + 11);
                pintarAnios();
            }
        }

        /** ‹ / › mueven un mes, un año o un bloque de 12 según la vista activa. */
        function mover(paso) {
            if (modo === 'dias')       vista.setMonth(vista.getMonth() + paso);
            else if (modo === 'meses') vista.setFullYear(vista.getFullYear() + paso);
            else                       baseAnios += paso * 12;
            pintar();
        }

        /**
         * Evita que el popover se salga por la derecha. El contenedor de un filtro
         * puede medir 172px y el popover 306px, y `max-width: calc(100vw - 40px)`
         * se calcula contra el viewport, no contra el contenedor: en la toolbar de
         * suplencias el campo "Hasta" desbordaba el panel.
         */
        function reposicionar() {
            root.classList.remove('is-flipped');
            var r = pop.getBoundingClientRect();
            var margen = 12;
            if (r.right > window.innerWidth - margen) root.classList.add('is-flipped');
        }

        function abrir() {
            cerrarTodos();
            vista = new Date((sel || hoy).getFullYear(), (sel || hoy).getMonth(), 1);
            modo = 'dias';
            pintar();
            pop.hidden = false;
            root.classList.add('is-open');
            trigger.setAttribute('aria-expanded', 'true');
            // .admin-panel usa `overflow: clip`, que recorta el popover por
            // geometría (el z-index no ayuda). La clase la lee el SCSS para
            // levantar el recorte solo mientras hay un datepicker abierto.
            //
            // Hay que subir por TODOS los ancestros, no solo el más cercano:
            // `closest()` devolvía `.admin-form-section` en los formularios de
            // usuarios y el `.admin-panel` que de verdad recorta nunca se
            // liberaba, así que el calendario no se veía.
            root._contenedores = [];
            for (var n = root.parentElement; n; n = n.parentElement) {
                if (n.matches('.admin-panel, .admin-form-section, .admin-form-row, .swp-panel')) {
                    n.classList.add('has-datepicker-open');
                    root._contenedores.push(n);
                }
            }
            reposicionar();
            abiertos.push(root);
        }
        function cerrar() {
            pop.hidden = true;
            root.classList.remove('is-open', 'is-flipped');
            trigger.setAttribute('aria-expanded', 'false');
            if (root._contenedores) {
                root._contenedores.forEach(function (n) { n.classList.remove('has-datepicker-open'); });
                root._contenedores = null;
            }
            var i = abiertos.indexOf(root);
            if (i !== -1) abiertos.splice(i, 1);
        }

        function elegir(ymd) {
            sel = deYmd(ymd);
            hidden.value = ymd;
            pintarEtiqueta();
            cerrar();
            hidden.dispatchEvent(new Event('change', { bubbles: true }));
        }

        /** Deja el campo sin fecha. Lo necesitan los filtros, que pueden ir vacíos. */
        function limpiar() {
            sel = null;
            hidden.value = '';
            pintarEtiqueta();
            cerrar();
            hidden.dispatchEvent(new Event('change', { bubbles: true }));
        }

        trigger.addEventListener('click', function (e) {
            e.stopPropagation();
            if (pop.hidden) abrir(); else cerrar();
        });
        pop.addEventListener('click', function (e) { e.stopPropagation(); });

        pop.querySelector('[data-date-prev]').addEventListener('click', function () { mover(-1); });
        pop.querySelector('[data-date-next]').addEventListener('click', function () { mover(1); });

        // Cabecera: días → meses → años. Desde años ya no sube más.
        cabecera.addEventListener('click', function () {
            if (modo === 'dias') { modo = 'meses'; }
            else if (modo === 'meses') { modo = 'anios'; baseAnios = vista.getFullYear() - (vista.getFullYear() % 12); }
            pintar();
        });

        panels.dias.querySelector('[data-date-grid]').addEventListener('click', function (e) {
            var btn = e.target.closest('[data-ymd]');
            if (btn) elegir(btn.dataset.ymd);
        });
        panels.meses.addEventListener('click', function (e) {
            var b = e.target.closest('[data-mes]');
            if (!b || b.disabled) return;
            vista.setMonth(+b.dataset.mes);
            modo = 'dias';
            pintar();
        });
        panels.anios.addEventListener('click', function (e) {
            var b = e.target.closest('[data-anio]');
            if (!b || b.disabled) return;
            vista.setFullYear(+b.dataset.anio);
            modo = 'meses';
            pintar();
        });

        var hoyBtn = pop.querySelector('[data-date-hoy]');
        if (hoyBtn) hoyBtn.addEventListener('click', function () {
            if (!bloqueado(hoy)) elegir(aYmd(hoy));
        });
        var limpiarBtn = pop.querySelector('[data-date-clear]');
        if (limpiarBtn) limpiarBtn.addEventListener('click', limpiar);

        root._cerrarDate = cerrar;
        pintarEtiqueta();
    }

    function cerrarTodos() {
        abiertos.slice().forEach(function (r) { if (r._cerrarDate) r._cerrarDate(); });
    }

    document.addEventListener('click', cerrarTodos);
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') cerrarTodos(); });

    function init(raiz) {
        (raiz || document).querySelectorAll('[data-datepicker]').forEach(function (r) {
            if (!r._cerrarDate) montar(r);
        });
    }

    window.AdminDatePicker = { init: init };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { init(); });
    } else {
        init();
    }
})();
