/* admin-datepicker — selector de fecha propio del panel (.bilbao-date).
   Componente compartido: no lleva guarda de página, se activa por `[data-datepicker]`.
   Sustituye a <input type="date"> para que la fecha se vea igual en todos los navegadores
   y para poder deshabilitar fines de semana (no hay clases que cubrir).

   La semana empieza en DOMINGO (la cabecera de días está en el markup, no aquí:
   ver views/blog/_campo-fecha.php).

   Markup esperado (lo genera views/blog/_campo-fecha.php):
     <div class="bilbao-date" data-datepicker data-min="2026-07-26" data-habiles="1">
        <input type="hidden" name="fecha" value="2026-07-27" data-date-value>
        <button type="button" class="bilbao-date__field" data-date-trigger>
            <i class="fa-regular fa-calendar"></i>
            <span data-date-label>—</span>
            <i class="fa-solid fa-chevron-down bilbao-date__caret"></i>
        </button>
        <div class="bilbao-date__pop" data-date-pop hidden>…
            <button data-date-hoy>Hoy</button>
            <button data-date-clear>Limpiar</button>   ← opcional, para filtros
        </div>
     </div>

   El hidden emite un evento `change` al elegir día o al limpiar, para que la vista
   reaccione (recargar el horario del profesor, reenviar el formulario de filtros…). */
(function () {
    var MESES = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
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

        function bloqueado(d) {
            if (soloHabiles && (d.getDay() === 0 || d.getDay() === 6)) return true;
            if (min && d < min) return true;
            if (max && d > max) return true;
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

        function pintarRejilla() {
            var y = vista.getFullYear(), m = vista.getMonth();
            pop.querySelector('[data-date-month]').textContent = MESES[m] + ' ' + y;

            var grid = pop.querySelector('[data-date-grid]');
            grid.innerHTML = '';

            // Semana que empieza en domingo: getDay() ya devuelve 0 para domingo,
            // así que el desfase de la primera fila es directo.
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

        function abrir() {
            cerrarTodos();
            vista = new Date((sel || hoy).getFullYear(), (sel || hoy).getMonth(), 1);
            pintarRejilla();
            pop.hidden = false;
            root.classList.add('is-open');
            trigger.setAttribute('aria-expanded', 'true');
            abiertos.push(root);
        }
        function cerrar() {
            pop.hidden = true;
            root.classList.remove('is-open');
            trigger.setAttribute('aria-expanded', 'false');
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

        pop.querySelector('[data-date-prev]').addEventListener('click', function () {
            vista.setMonth(vista.getMonth() - 1); pintarRejilla();
        });
        pop.querySelector('[data-date-next]').addEventListener('click', function () {
            vista.setMonth(vista.getMonth() + 1); pintarRejilla();
        });
        pop.querySelector('[data-date-grid]').addEventListener('click', function (e) {
            var btn = e.target.closest('[data-ymd]');
            if (btn) elegir(btn.dataset.ymd);
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
