/* blog-usuarios-cumpleanos
   Migrado desde el <script> embebido de views/blog/usuarios/cumpleanos.php */
(function () {
    if (!document.body || document.body.dataset.page !== 'blog-usuarios-cumpleanos') return;
    (function () {
        const root = document.getElementById('cbCalendar');
        if (!root) return;
        const MESES = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
        let evts = [];
        try { evts = JSON.parse(root.dataset.events || '[]'); } catch (e) { evts = []; }

        const byMD = {};
        evts.forEach(e => { (byMD[e.md] = byMD[e.md] || []).push(e); });

        const grid  = root.querySelector('[data-cal-grid]');
        const label = root.querySelector('[data-cal-label]');
        const list  = document.querySelector('[data-cb-list]');
        const title = document.querySelector('[data-cb-title]');
        const today = new Date();
        let view    = new Date(today.getFullYear(), today.getMonth(), 1);

        function pad(n) { return String(n).padStart(2, '0'); }

        function render() {
            const y = view.getFullYear(), m = view.getMonth();
            label.textContent = MESES[m] + ' ' + y;
            let firstDow = new Date(y, m, 1).getDay();
            firstDow = (firstDow === 0) ? 6 : firstDow - 1;
            const days = new Date(y, m + 1, 0).getDate();
            grid.innerHTML = '';
            for (let i = 0; i < firstDow; i++) {
                const b = document.createElement('span');
                b.className = 'bilbao-cal__cell bilbao-cal__cell--empty';
                grid.appendChild(b);
            }
            for (let d = 1; d <= days; d++) {
                const md = pad(m + 1) + '-' + pad(d);
                const cell = document.createElement('button');
                cell.type = 'button';
                cell.className = 'bilbao-cal__cell';
                cell.textContent = d;
                if (y === today.getFullYear() && m === today.getMonth() && d === today.getDate()) cell.classList.add('is-today');
                if (byMD[md]) {
                    cell.classList.add('has-event');
                    cell.setAttribute('data-md', md);
                    const dots = document.createElement('span');
                    dots.className = 'bilbao-cal__dots';
                    byMD[md].slice(0, 3).forEach(() => {
                        const dot = document.createElement('i');
                        dot.className = 'bilbao-cal__dot bilbao-cal__dot--cumple';
                        dots.appendChild(dot);
                    });
                    cell.appendChild(dots);
                    cell.addEventListener('click', () => showMD(md, d, m));
                } else {
                    cell.disabled = true;
                    cell.classList.add('is-plain');
                }
                grid.appendChild(cell);
            }
        }

        function itemHTML(e) {
            const ava = e.avatar
                ? '<div class="cb-ava"><img src="' + e.avatar + '" alt=""></div>'
                : '<div class="cb-ava">' + e.inicial + '</div>';
            return '<div class="cb-item">' + ava + '<div><div class="cb-name">' + e.nombre + '</div></div></div>';
        }

        function showMD(md, d, m) {
            root.querySelectorAll('.bilbao-cal__cell.is-active').forEach(c => c.classList.remove('is-active'));
            const cell = root.querySelector('[data-md="' + md + '"]');
            if (cell) cell.classList.add('is-active');
            if (title) title.textContent = d + ' de ' + MESES[m];
            list.innerHTML = (byMD[md] || []).map(itemHTML).join('') || '<div class="cb-empty">Sin cumpleaños.</div>';
        }

        root.querySelector('[data-cal-prev]').addEventListener('click', () => { view.setMonth(view.getMonth() - 1); render(); });
        root.querySelector('[data-cal-next]').addEventListener('click', () => { view.setMonth(view.getMonth() + 1); render(); });
        render();
    })();
})();
