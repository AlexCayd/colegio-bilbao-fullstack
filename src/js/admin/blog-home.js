/* blog-home
   Calendario combinado (cumpleaños + eventos) y paginación de cumpleaños.
   Migrado desde el <script> embebido de views/blog/home.php */
(function () {
    if (!document.body || document.body.dataset.page !== 'blog-home') return;

    /* ── Bosque del hero ──
       El mismo componente que el login. Devuelve null sin WebGL o con
       prefers-reduced-motion; el degradado del contenedor queda entonces a la vista,
       así que aquí no hay nada que deshacer. Three.js lo inyecta BlogController::home()
       en $extra_head y puede llegar después que este bundle (va con defer), de ahí el
       sondeo corto en vez de un init directo. */
    (function () {
        const canvas = document.getElementById('mhForest');
        if (!canvas) return;
        let intentos = 0;
        (function esperar() {
            if (window.THREE && window.BilbaoForest) {
                // Paleta CLARA, la misma del landing: el panel es una interfaz de día y
                // el bosque nocturno del login pesaba demasiado como fondo permanente.
                window.BilbaoForest.init(canvas, { scroll: false, dark: false });
                return;
            }
            if (++intentos < 100) setTimeout(esperar, 80);
        })();
    })();

    (function () {
        // ── Calendario ──
        const root = document.getElementById('mhCal');
        if (root) {
            const MESES = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
            let marks = []; try { marks = JSON.parse(root.dataset.events || '[]'); } catch (e) {}

            // Dos índices: los cumpleaños se repiten cada año (MM-DD),
            // los eventos caen en una fecha concreta (YYYY-MM-DD).
            const byMD = {}, byYMD = {};
            marks.forEach(m => {
                if (m.md)       (byMD[m.md]   = byMD[m.md]   || []).push(m);
                else if (m.ymd) (byYMD[m.ymd] = byYMD[m.ymd] || []).push(m);
            });

            const grid = root.querySelector('[data-cal-grid]'), label = root.querySelector('[data-cal-label]');
            const today = new Date(); let view = new Date(today.getFullYear(), today.getMonth(), 1);
            const pad = n => String(n).padStart(2, '0');

            function render() {
                const y = view.getFullYear(), m = view.getMonth();
                label.textContent = MESES[m] + ' ' + y;
                const fd = new Date(y, m, 1).getDay();   // domingo = 0
                const days = new Date(y, m + 1, 0).getDate();
                grid.innerHTML = '';
                for (let i = 0; i < fd; i++) { const b = document.createElement('span'); b.className = 'bilbao-cal__cell bilbao-cal__cell--empty'; grid.appendChild(b); }
                for (let d = 1; d <= days; d++) {
                    const md  = pad(m + 1) + '-' + pad(d);
                    const ymd = y + '-' + md;
                    const hits = (byMD[md] || []).concat(byYMD[ymd] || []);
                    const cell = document.createElement('button'); cell.type = 'button'; cell.className = 'bilbao-cal__cell'; cell.textContent = d;
                    if (y === today.getFullYear() && m === today.getMonth() && d === today.getDate()) cell.classList.add('is-today');
                    if (hits.length) {
                        cell.classList.add('has-event');
                        const titulo = hits.map(e => e.nombre).join(', ');
                        cell.title = titulo;
                        cell.setAttribute('aria-label', d + ': ' + titulo);
                        const dots = document.createElement('span'); dots.className = 'bilbao-cal__dots';
                        hits.slice(0, 3).forEach(e => {
                            const i = document.createElement('i');
                            i.className = 'bilbao-cal__dot bilbao-cal__dot--' + (e.tipo || 'evento');
                            dots.appendChild(i);
                        });
                        cell.appendChild(dots);
                        // Al pulsar el día se abre el detalle. Antes las celdas eran
                        // botones sin acción: se veían los puntos pero no se podía
                        // saber qué había ese día sin ir a Eventos.
                        cell.addEventListener('click', () => abrirDia(y, m, d, hits));
                    } else { cell.disabled = true; cell.classList.add('is-plain'); }
                    grid.appendChild(cell);
                }
                if (window.BilbaoCalAnim) window.BilbaoCalAnim.entrada(grid);
            }
            root.querySelector('[data-cal-prev]').addEventListener('click', () => { view.setMonth(view.getMonth() - 1); render(); });
            root.querySelector('[data-cal-next]').addEventListener('click', () => { view.setMonth(view.getMonth() + 1); render(); });
            render();

            // ── Modal de detalle del día ──
            // `mostrarDia` se asigna solo si el modal existe; `abrirDia` (que es lo que
            // llaman las celdas) queda como no-op si no está, para que un clic nunca
            // reviente aunque la vista se renderice sin el modal.
            let mostrarDia = null;
            const modal = document.getElementById('mhDiaModal');
            if (modal) {
                const elTitulo = modal.querySelector('[data-dia-titulo]');
                const elLista  = modal.querySelector('[data-dia-lista]');
                let ultimoFoco = null;

                const DIAS_L = ['domingo','lunes','martes','miércoles','jueves','viernes','sábado'];
                const TIPO_L = {
                    cumple:'Cumpleaños', festivo:'Festivo', evento:'Evento',
                    junta:'Junta', entrega:'Entrega', suspension:'Suspensión'
                };

                mostrarDia = function (y, m, d, hits) {
                    ultimoFoco = document.activeElement;
                    const f = new Date(y, m, d);
                    elTitulo.textContent = DIAS_L[f.getDay()] + ', ' + d + ' de ' + MESES[m].toLowerCase() + ' de ' + y;

                    elLista.innerHTML = '';
                    hits.forEach(e => {
                        const tipo = e.tipo || 'evento';
                        const li = document.createElement('li');
                        li.className = 'mh-dia__item mh-dia__item--' + tipo;

                        const ico = document.createElement('span');
                        ico.className = 'mh-dia__ico';
                        ico.innerHTML = '<i class="fa-solid ' +
                            (tipo === 'cumple' ? 'fa-cake-candles' : 'fa-calendar-day') + '"></i>';

                        const txt = document.createElement('div');
                        txt.className = 'mh-dia__txt';
                        const nom = document.createElement('span');
                        nom.className = 'mh-dia__nom';
                        nom.textContent = e.nombre || '';
                        const et = document.createElement('span');
                        et.className = 'mh-dia__tipo';
                        et.textContent = TIPO_L[tipo] || tipo;
                        txt.appendChild(nom); txt.appendChild(et);
                        if (e.desc) {
                            const de = document.createElement('span');
                            de.className = 'mh-dia__desc';
                            de.textContent = e.desc;
                            txt.appendChild(de);
                        }
                        li.appendChild(ico); li.appendChild(txt);
                        elLista.appendChild(li);
                    });

                    modal.classList.add('is-open');
                    modal.setAttribute('aria-hidden', 'false');
                    document.body.style.overflow = 'hidden';
                    const cerrar = modal.querySelector('[data-dia-cerrar]');
                    if (cerrar) cerrar.focus();
                };

                function cerrarDia() {
                    modal.classList.remove('is-open');
                    modal.setAttribute('aria-hidden', 'true');
                    document.body.style.overflow = '';
                    if (ultimoFoco && ultimoFoco.focus) ultimoFoco.focus();
                }

                modal.addEventListener('click', (ev) => {
                    if (ev.target === modal || ev.target.closest('[data-dia-cerrar]')) cerrarDia();
                });
                document.addEventListener('keydown', (ev) => {
                    if (ev.key === 'Escape' && modal.classList.contains('is-open')) cerrarDia();
                });
            }

            function abrirDia(y, m, d, hits) {
                if (mostrarDia) mostrarDia(y, m, d, hits);
            }
        }

        /* ── Cuántos cumpleaños caben en la columna ──
           La monta admin-pager.js a partir de [data-pager]; aquí solo se ajusta
           `data-pager-per` al alto REAL disponible. La altura de esta columna la
           fija el calendario de al lado (celdas cuadradas, depende del ancho), así
           que no se puede acertar con un número fijo: con 10 sobraba hueco y la
           lista quedaba desparramada. */
        (function () {
            const lista = document.getElementById('mhBdayList');
            const pager = document.getElementById('mhPager');
            if (!lista || !pager || !window.AdminPager) return;

            const cuerpo = lista.parentElement;   // .mh-panel__body

            function ajustar() {
                const item = lista.querySelector('[data-pager-item]');
                if (!item) return;

                // Se mide sobre un elemento visible; si la página actual los oculta
                // todos, no hay nada fiable que medir y se deja como está.
                const alto = item.getBoundingClientRect().height;
                if (!alto) return;

                const gap  = parseFloat(getComputedStyle(lista).rowGap) || 0;
                // El alto disponible es el del cuerpo menos su padding vertical.
                const cs   = getComputedStyle(cuerpo);
                const util = cuerpo.clientHeight - parseFloat(cs.paddingTop) - parseFloat(cs.paddingBottom);
                if (util <= 0) return;

                const caben = Math.max(3, Math.floor((util + gap) / (alto + gap)));
                if (String(caben) === pager.dataset.pagerPer) return;   // sin cambios

                pager.dataset.pagerPer = String(caben);
                window.AdminPager.reset(pager);
            }

            ajustar();

            let t;
            window.addEventListener('resize', function () {
                clearTimeout(t);
                t = setTimeout(ajustar, 150);
            });
        })();
    })();
})();
