/* blog-home
   Migrado desde el <script> embebido de views/blog/home.php */
(function () {
    if (!document.body || document.body.dataset.page !== 'blog-home') return;
    (function () {
        // ── Calendario ──
        const root = document.getElementById('mhCal');
        if (root) {
            const MESES = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
            let evts = []; try { evts = JSON.parse(root.dataset.events || '[]'); } catch (e) {}
            const byMD = {}; evts.forEach(e => { (byMD[e.md] = byMD[e.md] || []).push(e); });
            const grid = root.querySelector('[data-cal-grid]'), label = root.querySelector('[data-cal-label]');
            const today = new Date(); let view = new Date(today.getFullYear(), today.getMonth(), 1);
            const pad = n => String(n).padStart(2, '0');
            function render() {
                const y = view.getFullYear(), m = view.getMonth();
                label.textContent = MESES[m] + ' ' + y;
                let fd = new Date(y, m, 1).getDay(); fd = (fd === 0) ? 6 : fd - 1;
                const days = new Date(y, m + 1, 0).getDate();
                grid.innerHTML = '';
                for (let i = 0; i < fd; i++) { const b = document.createElement('span'); b.className = 'bilbao-cal__cell bilbao-cal__cell--empty'; grid.appendChild(b); }
                for (let d = 1; d <= days; d++) {
                    const md = pad(m + 1) + '-' + pad(d);
                    const cell = document.createElement('button'); cell.type = 'button'; cell.className = 'bilbao-cal__cell'; cell.textContent = d;
                    if (y === today.getFullYear() && m === today.getMonth() && d === today.getDate()) cell.classList.add('is-today');
                    if (byMD[md]) {
                        cell.classList.add('has-event'); cell.title = byMD[md].map(e => e.nombre).join(', ');
                        const dots = document.createElement('span'); dots.className = 'bilbao-cal__dots';
                        byMD[md].slice(0, 3).forEach(() => { const i = document.createElement('i'); i.className = 'bilbao-cal__dot bilbao-cal__dot--cumple'; dots.appendChild(i); });
                        cell.appendChild(dots);
                    } else { cell.disabled = true; cell.classList.add('is-plain'); }
                    grid.appendChild(cell);
                }
            }
            root.querySelector('[data-cal-prev]').addEventListener('click', () => { view.setMonth(view.getMonth() - 1); render(); });
            root.querySelector('[data-cal-next]').addEventListener('click', () => { view.setMonth(view.getMonth() + 1); render(); });
            render();
        }

        // ── Paginación de cumpleaños ──
        const list = document.getElementById('mhBdayList');
        const pager = document.getElementById('mhPager');
        if (list && pager) {
            const items = Array.from(list.querySelectorAll('[data-bday]'));
            const per = 5, pages = Math.ceil(items.length / per);
            let page = 0;
            const info = pager.querySelector('[data-pager-info]');
            const prev = pager.querySelector('[data-pager-prev]');
            const next = pager.querySelector('[data-pager-next]');
            function show() {
                items.forEach((el, i) => el.classList.toggle('is-hidden', i < page * per || i >= (page + 1) * per));
                info.textContent = (page + 1) + ' / ' + pages + ' · ' + items.length + ' colaboradores';
                prev.disabled = page === 0; next.disabled = page >= pages - 1;
            }
            prev.addEventListener('click', () => { if (page > 0) { page--; show(); } });
            next.addEventListener('click', () => { if (page < pages - 1) { page++; show(); } });
            show();
        }
    })();
})();
