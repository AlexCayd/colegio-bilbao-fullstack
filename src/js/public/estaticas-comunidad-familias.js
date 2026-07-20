/* estaticas-comunidad-familias
   Migrado desde el <script> embebido de views/estaticas/comunidad/familias.php */
(function () {
    if (!document.body || document.body.dataset.page !== 'estaticas-comunidad-familias') return;
    (function () {
        const root = document.getElementById('famCalendar');
        if (!root) return;

        const MESES = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
        let events = [];
        try { events = JSON.parse(root.dataset.events || '[]'); } catch (e) { events = []; }

        const byDate = {};
        events.forEach(ev => { (byDate[ev.fecha] = byDate[ev.fecha] || []).push(ev); });

        const grid    = root.querySelector('[data-cal-grid]');
        const label   = root.querySelector('[data-cal-label]');
        const listEl  = document.querySelector('[data-cal-list]');
        const today   = new Date();
        let view      = new Date(today.getFullYear(), today.getMonth(), 1);

        function pad(n) { return String(n).padStart(2, '0'); }
        function key(y, m, d) { return y + '-' + pad(m + 1) + '-' + pad(d); }

        function render() {
            const y = view.getFullYear(), m = view.getMonth();
            label.textContent = MESES[m] + ' ' + y;

            // Lunes = 0
            let firstDow = new Date(y, m, 1).getDay();
            firstDow = (firstDow === 0) ? 6 : firstDow - 1;
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
        }

        function fmt(k) {
            const [yy, mm, dd] = k.split('-').map(Number);
            return dd + ' de ' + MESES[mm - 1];
        }

        function renderList(items, title) {
            const heading = document.querySelector('.fam__cal-detail-title');
            if (heading && title) heading.textContent = title;
            listEl.innerHTML = '';
            if (!items.length) {
                listEl.innerHTML = '<li class="fam__cal-empty">Sin eventos este día.</li>';
                return;
            }
            items.forEach(ev => {
                const li = document.createElement('li');
                li.className = 'fam__cal-item';
                li.innerHTML = '<i class="fam__cal-item-dot bilbao-cal__dot--' + ev.tipo + '"></i>'
                    + '<div><span class="fam__cal-item-date">' + fmt(ev.fecha) + '</span>'
                    + '<span class="fam__cal-item-title">' + ev.titulo + '</span></div>';
                listEl.appendChild(li);
            });
        }

        const resetBtn = document.querySelector('[data-cal-reset]');

        function showDay(k) {
            root.querySelectorAll('.bilbao-cal__cell.is-active').forEach(c => c.classList.remove('is-active'));
            const cell = root.querySelector('[data-date="' + k + '"]');
            if (cell) cell.classList.add('is-active');
            renderList(byDate[k] || [], fmt(k));
            if (resetBtn) resetBtn.hidden = false;
        }

        function showUpcoming() {
            root.querySelectorAll('.bilbao-cal__cell.is-active').forEach(c => c.classList.remove('is-active'));
            renderList(upcoming(), 'Próximos eventos');
            if (resetBtn) resetBtn.hidden = true;
        }

        function upcoming() {
            const t = today.getFullYear() + '-' + pad(today.getMonth() + 1) + '-' + pad(today.getDate());
            return events.filter(e => e.fecha >= t).sort((a, b) => a.fecha.localeCompare(b.fecha)).slice(0, 5);
        }

        if (resetBtn) resetBtn.addEventListener('click', showUpcoming);
        root.querySelector('[data-cal-prev]').addEventListener('click', () => { view.setMonth(view.getMonth() - 1); render(); });
        root.querySelector('[data-cal-next]').addEventListener('click', () => { view.setMonth(view.getMonth() + 1); render(); });

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
