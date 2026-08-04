/* blog-home
   Calendario combinado (cumpleaños + eventos) y paginación de cumpleaños.
   Migrado desde el <script> embebido de views/blog/home.php */
(function () {
    if (!document.body || document.body.dataset.page !== 'blog-home') return;
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
                    } else { cell.disabled = true; cell.classList.add('is-plain'); }
                    grid.appendChild(cell);
                }
                if (window.BilbaoCalAnim) window.BilbaoCalAnim.entrada(grid);
            }
            root.querySelector('[data-cal-prev]').addEventListener('click', () => { view.setMonth(view.getMonth() - 1); render(); });
            root.querySelector('[data-cal-next]').addEventListener('click', () => { view.setMonth(view.getMonth() + 1); render(); });
            render();
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
