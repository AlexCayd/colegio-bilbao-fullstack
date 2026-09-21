/* estaticas-comunidad-familias
   Migrado desde el <script> embebido de views/estaticas/comunidad/familias.php */
(function () {
    if (!document.body || document.body.dataset.page !== 'estaticas-comunidad-familias') return;

    /* ── CALENDARIO ESCOLAR ────────────────────────────────────────────────────
     * Dos vistas sobre los MISMOS datos y el MISMO filtro:
     *
     *   · mes   — la rejilla navegable de siempre, con el detalle del día al lado.
     *   · ciclo — los doce meses del curso de un vistazo, cada uno con su lista.
     *
     * Todo ocurre en cliente: los eventos de la audiencia «familias» llegan
     * completos en la isla JSON del contenedor, así que filtrar por nivel o saltar
     * de vista no pide nada al servidor. El icono, el color y la etiqueta pública de
     * cada evento vienen ya resueltos desde PHP — replicar aquí la tabla de tipos era
     * la vía directa a que la web y el PDF pintaran distinto el mismo evento.
     */
    (function () {
        const seccion = document.querySelector('[data-fam-cal]');
        const root    = document.getElementById('famCalendar');
        if (!seccion || !root) return;

        const MESES = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
        const DOW   = ['D', 'L', 'M', 'M', 'J', 'V', 'S'];
        /* Tope de expansión de un evento de varios días. Un rango absurdo (o un
           `fecha_fin` mal tecleado) no debe poder colgar el bucle. */
        const MAX_DIAS = 400;

        function leer(el, prop, fallback) {
            try { return JSON.parse(el.dataset[prop] || ''); } catch (e) { return fallback; }
        }

        const events     = leer(root, 'events', []) || [];
        const mesesCiclo = leer(seccion, 'meses', []) || [];
        const ciclo      = leer(seccion, 'ciclo', {}) || {};

        const grid    = root.querySelector('[data-cal-grid]');
        const label   = root.querySelector('[data-cal-label]');
        const listEl  = seccion.querySelector('[data-cal-list]');
        const heading = seccion.querySelector('.fam__cal-detail-title');
        const resetBtn = seccion.querySelector('[data-cal-reset]');
        const descarga = seccion.querySelector('[data-cal-descarga]');
        const cicloGrid = seccion.querySelector('[data-ciclo-grid]');
        const cicloMeta = seccion.querySelector('[data-ciclo-meta]');
        const cicloVacio = seccion.querySelector('[data-ciclo-vacio]');

        /* El rótulo de «próximos» se lee del DOM y no se escribe aquí: así conserva la
           traducción que i18n haya aplicado al pintar la página. */
        const TXT_PROXIMOS = (heading && heading.textContent.trim()) || 'Próximos eventos';

        const today = new Date();
        let view    = new Date(today.getFullYear(), today.getMonth(), 1);
        let vista   = 'mes';
        const sel   = new Set();          // niveles activos; vacío = sin filtro

        function pad(n) { return String(n).padStart(2, '0'); }
        function key(y, m, d) { return y + '-' + pad(m + 1) + '-' + pad(d); }
        function iso(dt) { return key(dt.getFullYear(), dt.getMonth(), dt.getDate()); }

        // ── Filtro ────────────────────────────────────────────────────────────
        /* Un evento SIN niveles va dirigido a todo el colegio: pasa cualquier filtro.
           Es lo que la nota de la barra explica, porque si no un filtro de Kinder que
           sigue enseñando la junta general parece un filtro roto. */
        function coincide(ev) {
            if (!sel.size) return true;
            if (!ev.niveles || !ev.niveles.length) return true;
            return ev.niveles.some(n => sel.has(n));
        }
        function visibles() { return events.filter(coincide); }

        // ── Días que ocupa un evento ──────────────────────────────────────────
        /* Un evento de varios días existe en TODOS ellos, no solo en el primero: unas
           vacaciones del 20 de diciembre al 6 de enero tienen que verse en los dos
           meses. Antes solo se indexaba `fecha` y el resto del tramo desaparecía. */
        function dias(ev) {
            const out = [ev.fecha];
            if (!ev.fecha_fin || ev.fecha_fin <= ev.fecha) return out;
            const cursor = new Date(ev.fecha + 'T00:00:00');
            const fin    = new Date(ev.fecha_fin + 'T00:00:00');
            if (isNaN(cursor) || isNaN(fin)) return out;
            for (let i = 0; i < MAX_DIAS; i++) {
                cursor.setDate(cursor.getDate() + 1);
                if (cursor > fin) break;
                out.push(iso(cursor));
            }
            return out;
        }

        function indexar() {
            const map = {};
            visibles().forEach(ev => {
                dias(ev).forEach(k => { (map[k] = map[k] || []).push(ev); });
            });
            return map;
        }

        let byDate = indexar();

        // ── Vista MES ─────────────────────────────────────────────────────────
        function render() {
            const y = view.getFullYear(), m = view.getMonth();
            label.textContent = MESES[m] + ' ' + y;

            // Domingo = 0
            const firstDow = new Date(y, m, 1).getDay();
            const daysInMonth = new Date(y, m + 1, 0).getDate();

            grid.innerHTML = '';
            for (let i = 0; i < firstDow; i++) {
                const blank = document.createElement('span');
                blank.className = 'bilbao-cal__cell bilbao-cal__cell--empty';
                grid.appendChild(blank);
            }
            for (let d = 1; d <= daysInMonth; d++) {
                const k = key(y, m, d);
                const cell = document.createElement('button');
                cell.type = 'button';
                cell.className = 'bilbao-cal__cell';
                cell.textContent = d;

                const isToday = (y === today.getFullYear() && m === today.getMonth() && d === today.getDate());
                if (isToday) cell.classList.add('is-today');

                if (byDate[k]) {
                    cell.classList.add('has-event');
                    cell.setAttribute('data-date', k);
                    const dots = document.createElement('span');
                    dots.className = 'bilbao-cal__dots';
                    byDate[k].slice(0, 3).forEach(ev => {
                        const dot = document.createElement('i');
                        dot.className = 'bilbao-cal__dot bilbao-cal__dot--' + ev.tipo;
                        if (ev.color) dot.style.background = ev.color;
                        dots.appendChild(dot);
                    });
                    cell.appendChild(dots);
                    cell.addEventListener('click', () => showDay(k));
                } else {
                    cell.disabled = true;
                    cell.classList.add('is-plain');
                }
                grid.appendChild(cell);
            }
            if (window.BilbaoCalAnim) window.BilbaoCalAnim.entrada(grid);
        }

        function fmt(k) {
            const [yy, mm, dd] = k.split('-').map(Number);
            return dd + ' de ' + MESES[mm - 1];
        }

        /* Rango legible de un evento. Un evento de un día dice su día; uno de varios
           dice los dos extremos, que es el dato que la celda no puede contar. */
        function rango(ev) {
            if (!ev.fecha_fin || ev.fecha_fin <= ev.fecha) return fmt(ev.fecha);
            return fmt(ev.fecha) + ' – ' + fmt(ev.fecha_fin);
        }

        function chipsNivel(ev) {
            if (!ev.niveles || !ev.niveles.length) return '';
            return '<span class="fam__cal-item-nivs">'
                + ev.niveles.map(n => '<span class="fam-nivtag">' + esc(n) + '</span>').join('')
                + '</span>';
        }

        /* ⚠️ Escapa también las COMILLAS, no solo `&<>`.
           El truco de `div.textContent → innerHTML` sirve para texto pero deja pasar
           `"`, y aquí el título del evento se interpola también dentro de atributos
           (`title="…"`): una comilla en «Junta de 3º "A"» se saldría del atributo y
           lo siguiente ya sería marcado. Los títulos los escribe el panel, pero el
           panel no es un sitio de confianza para el HTML del sitio público. */
        function esc(t) {
            return String(t == null ? '' : t)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function renderList(items, title) {
            if (heading && title) heading.textContent = title;
            listEl.innerHTML = '';
            if (!items.length) {
                listEl.innerHTML = '<li class="fam__cal-empty">Sin eventos este día.</li>';
                return;
            }
            items.forEach(ev => {
                const li = document.createElement('li');
                li.className = 'fam__cal-item';
                li.style.setProperty('--ev-color', ev.color || '#4d8abb');
                li.innerHTML = '<span class="fam__cal-item-ico"><i class="fa-solid ' + esc(ev.icono || 'fa-calendar-day') + '"></i></span>'
                    + '<div><span class="fam__cal-item-date">' + esc(rango(ev)) + '</span>'
                    + '<span class="fam__cal-item-title">' + esc(ev.titulo) + '</span>'
                    + (ev.desc ? '<span class="fam__cal-item-desc">' + esc(ev.desc) + '</span>' : '')
                    + chipsNivel(ev) + '</div>';
                listEl.appendChild(li);
            });
        }

        function showDay(k) {
            root.querySelectorAll('.bilbao-cal__cell.is-active').forEach(c => c.classList.remove('is-active'));
            const cell = root.querySelector('[data-date="' + k + '"]');
            if (cell) cell.classList.add('is-active');
            renderList(byDate[k] || [], fmt(k));
            if (resetBtn) resetBtn.hidden = false;
        }

        function showUpcoming() {
            root.querySelectorAll('.bilbao-cal__cell.is-active').forEach(c => c.classList.remove('is-active'));
            renderList(upcoming(), TXT_PROXIMOS);
            if (resetBtn) resetBtn.hidden = true;
        }

        /* Lo que aún no ha terminado, no lo que aún no ha empezado: unas vacaciones que
           arrancaron ayer y acaban el viernes siguen siendo un próximo evento. */
        function upcoming() {
            const t = iso(today);
            return visibles()
                .filter(e => (e.fecha_fin || e.fecha) >= t)
                .sort((a, b) => a.fecha.localeCompare(b.fecha))
                .slice(0, 5);
        }

        // ── Vista CICLO ───────────────────────────────────────────────────────
        /* ¿El evento toca este mes? Se compara por solapamiento de tramos y no por
           `fecha`: un evento que empieza en diciembre y acaba en enero pertenece a los
           dos meses, y listarlo solo en el primero dejaría enero mintiendo. */
        function tocaMes(ev, y, m) {
            const ini = key(y, m, 1);
            const fin = key(y, m, new Date(y, m + 1, 0).getDate());
            return ev.fecha <= fin && (ev.fecha_fin || ev.fecha) >= ini;
        }

        function renderCiclo() {
            if (!cicloGrid) return;
            cicloGrid.innerHTML = '';
            const lista = visibles();

            /* El contador cuenta EVENTOS, no apariciones. Un evento de varios meses
               —las vacaciones de invierno— sale en las tarjetas de diciembre y de
               enero, así que sumar los `delMes` daba «13 eventos publicados» habiendo
               12. Se cuentan aquí los que tocan el ciclo, una sola vez cada uno. */
            const total = (ciclo.ini && ciclo.fin)
                ? lista.filter(ev => ev.fecha <= ciclo.fin && (ev.fecha_fin || ev.fecha) >= ciclo.ini).length
                : lista.length;

            mesesCiclo.forEach(function (info) {
                const y = info.anio, m = info.mes - 1;
                const delMes = lista.filter(ev => tocaMes(ev, y, m));

                const card = document.createElement('article');
                card.className = 'fam-cmes';

                let html = '<header class="fam-cmes__head">'
                    + '<h4 class="fam-cmes__titulo">' + MESES[m] + ' <span>' + y + '</span></h4>'
                    + (delMes.length ? '<span class="fam-cmes__count">' + delMes.length + '</span>' : '')
                    + '</header>'
                    + '<div class="fam-cmes__dow">' + DOW.map(d => '<span>' + d + '</span>').join('') + '</div>';

                // Mini-rejilla del mes
                const firstDow = new Date(y, m, 1).getDay();
                const total_d  = new Date(y, m + 1, 0).getDate();
                html += '<div class="fam-cmes__grid">';
                for (let i = 0; i < firstDow; i++) html += '<span class="fam-cmes__cell fam-cmes__cell--empty"></span>';
                for (let d = 1; d <= total_d; d++) {
                    const k  = key(y, m, d);
                    const ev = byDate[k];
                    const hoy = (y === today.getFullYear() && m === today.getMonth() && d === today.getDate());
                    if (ev) {
                        /* El color del día es el del primer evento; el resto se cuentan
                           con el punto extra. Pintar franjas de dos o tres colores en
                           una celda de 26px no se distingue, solo ensucia. */
                        html += '<button type="button" class="fam-cmes__cell has-event' + (hoy ? ' is-today' : '') + '"'
                              + ' data-dia="' + k + '" style="--ev-color:' + esc(ev[0].color || '#4d8abb') + '"'
                              + ' title="' + esc(ev.map(e => e.titulo).join(' · ')) + '">' + d
                              + (ev.length > 1 ? '<i class="fam-cmes__mas"></i>' : '') + '</button>';
                    } else {
                        html += '<span class="fam-cmes__cell' + (hoy ? ' is-today' : '') + '">' + d + '</span>';
                    }
                }
                html += '</div>';

                // Lista del mes. En una celda de 26px solo cabe un punto de color: el
                // evento hay que poder leerlo, y de eso se encarga esta lista.
                if (delMes.length) {
                    html += '<ul class="fam-cmes__list">';
                    delMes.forEach(function (ev) {
                        const d1 = new Date(ev.fecha + 'T00:00:00').getDate();
                        const multi = ev.fecha_fin && ev.fecha_fin > ev.fecha;
                        html += '<li class="fam-cmes__item" data-dias="' + dias(ev).join(' ') + '"'
                             + ' style="--ev-color:' + esc(ev.color || '#4d8abb') + '">'
                             + '<span class="fam-cmes__dia">' + (multi ? esc(diasCortos(ev, y, m)) : d1) + '</span>'
                             + '<span class="fam-cmes__ico"><i class="fa-solid ' + esc(ev.icono || 'fa-calendar-day') + '"></i></span>'
                             + '<span class="fam-cmes__txt">' + esc(ev.titulo)
                             + (ev.niveles && ev.niveles.length
                                 ? '<span class="fam-cmes__nivs">' + ev.niveles.map(n => esc(n)).join(' · ') + '</span>'
                                 : '')
                             + '</span></li>';
                    });
                    html += '</ul>';
                } else {
                    html += '<p class="fam-cmes__sin">Sin eventos</p>';
                }

                card.innerHTML = html;
                cicloGrid.appendChild(card);
            });

            if (cicloMeta) {
                cicloMeta.textContent = total === 1 ? '1 evento publicado' : total + ' eventos publicados';
            }
            if (cicloVacio) cicloVacio.hidden = total > 0;
        }

        /* «20–31» para el tramo del evento DENTRO de este mes. Un evento que cruza de
           diciembre a enero no puede rotularse «20–6» en ninguno de los dos. */
        function diasCortos(ev, y, m) {
            const primero = new Date(y, m, 1), ultimo = new Date(y, m + 1, 0);
            const a = new Date(ev.fecha + 'T00:00:00');
            const b = new Date((ev.fecha_fin || ev.fecha) + 'T00:00:00');
            const ini = a < primero ? primero : a;
            const fin = b > ultimo ? ultimo : b;
            return ini.getDate() === fin.getDate() ? String(ini.getDate())
                 : ini.getDate() + '–' + fin.getDate();
        }

        /* Pulsar un día de la mini-rejilla resalta sus eventos en la lista de ese mes.
           Es un realce, no una navegación: en esta vista todo está ya a la vista y
           abrir un panel aparte solo taparía el resto del curso. */
        if (cicloGrid) {
            cicloGrid.addEventListener('click', function (e) {
                const cell = e.target.closest('.fam-cmes__cell.has-event');
                if (!cell) return;
                const card = cell.closest('.fam-cmes');
                const on   = !cell.classList.contains('is-sel');
                cicloGrid.querySelectorAll('.is-sel').forEach(c => c.classList.remove('is-sel'));
                cicloGrid.querySelectorAll('.is-hit').forEach(c => c.classList.remove('is-hit'));
                if (!on) return;
                cell.classList.add('is-sel');
                const k = cell.dataset.dia;
                card.querySelectorAll('.fam-cmes__item').forEach(function (li) {
                    if ((li.dataset.dias || '').split(' ').indexOf(k) !== -1) li.classList.add('is-hit');
                });
            });
        }

        // ── Cambio de vista ───────────────────────────────────────────────────
        /* ⚠️ Los dos paneles se alternan con `hidden`, y los dos tienen `display`
           propio (`grid`), así que en el SCSS llevan obligatoriamente su `&[hidden]`:
           `[hidden]{display:none}` vive en base/_normalize.scss, misma especificidad y
           capa anterior, así que sin esa línea el `grid` gana y se verían las dos. */
        function setVista(v) {
            vista = v;
            seccion.querySelectorAll('[data-cal-panel]').forEach(function (p) {
                p.hidden = p.dataset.calPanel !== v;
            });
            seccion.querySelectorAll('[data-cal-vista]').forEach(function (b) {
                const on = b.dataset.calVista === v;
                b.classList.toggle('is-on', on);
                b.setAttribute('aria-pressed', on ? 'true' : 'false');
            });
            if (v === 'ciclo') renderCiclo();
        }

        seccion.querySelectorAll('[data-cal-vista]').forEach(function (b) {
            b.addEventListener('click', function () { setVista(b.dataset.calVista); });
        });

        // ── Chips de nivel ────────────────────────────────────────────────────
        /* «Todo el colegio» no es un nivel más sino el estado sin filtro: apaga a los
           demás en lugar de sumarse a ellos. Y marcar los cinco equivale a ninguno,
           igual que en el panel (Evento::normalizarNiveles()), así que se vuelve a ese
           estado en vez de dejar una selección que no filtra nada. */
        const chips = Array.prototype.slice.call(seccion.querySelectorAll('[data-nivel]'));
        const chipTodos = chips.filter(c => c.dataset.nivel === '')[0];
        const chipsNiv  = chips.filter(c => c.dataset.nivel !== '');

        function pintarChips() {
            chipsNiv.forEach(function (c) {
                const on = sel.has(c.dataset.nivel);
                c.classList.toggle('is-on', on);
                c.setAttribute('aria-pressed', on ? 'true' : 'false');
            });
            if (chipTodos) {
                chipTodos.classList.toggle('is-on', sel.size === 0);
                chipTodos.setAttribute('aria-pressed', sel.size === 0 ? 'true' : 'false');
            }
        }

        function sincronizarDescarga() {
            if (!descarga) return;
            const orden = chipsNiv.filter(c => sel.has(c.dataset.nivel)).map(c => c.dataset.nivel);
            descarga.href = '/comunidad/familias/calendario.pdf'
                + (orden.length ? '?niveles=' + encodeURIComponent(orden.join(',')) : '');
        }

        function aplicarFiltro() {
            byDate = indexar();
            pintarChips();
            sincronizarDescarga();
            render();
            showUpcoming();
            if (vista === 'ciclo') renderCiclo();
        }

        chips.forEach(function (c) {
            c.addEventListener('click', function () {
                const n = c.dataset.nivel;
                if (!n) sel.clear();
                else if (sel.has(n)) sel.delete(n);
                else sel.add(n);
                if (sel.size === chipsNiv.length) sel.clear();   // los cinco = ninguno
                aplicarFiltro();
            });
        });

        if (resetBtn) resetBtn.addEventListener('click', showUpcoming);
        root.querySelector('[data-cal-prev]').addEventListener('click', () => { view.setMonth(view.getMonth() - 1); render(); });
        root.querySelector('[data-cal-next]').addEventListener('click', () => { view.setMonth(view.getMonth() + 1); render(); });

        sincronizarDescarga();
        render();
        showUpcoming();
    })();

    (function () {
        if (typeof gsap === 'undefined') return;
        var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (reduce) return;
        gsap.from('[data-fam-reveal]', { y: 24, opacity: 0, duration: 0.8, ease: 'power3.out', stagger: 0.12 });
        if (gsap.registerPlugin && window.ScrollTrigger) {
            gsap.registerPlugin(ScrollTrigger);
            gsap.utils.toArray('.fam-aviso').forEach(function (el, i) {
                gsap.from(el, { scrollTrigger: { trigger: el, start: 'top 90%' }, y: 34, opacity: 0, duration: 0.6, ease: 'power3.out', delay: (i % 3) * 0.06 });
            });
            gsap.from('.fam__cal-wrap', { scrollTrigger: { trigger: '.fam__cal-wrap', start: 'top 85%' }, y: 40, opacity: 0, duration: 0.8, ease: 'power3.out' });
        }
    })();
})();
