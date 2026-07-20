/* blog-suplencias-_form
   Migrado desde el <script> embebido de views/blog/suplencias/_form.php */
/* Compartido: se activa por existencia de sus elementos */
(function () {
    (function () {
        const ENDPOINT = '/dashboard/suplencias/buscar-colaboradores?q=';

        document.querySelectorAll('[data-picker]').forEach(function (root) {
            const input   = root.querySelector('[data-picker-input]');
            const hidden  = root.querySelector('[data-picker-value]');
            const results = root.querySelector('[data-picker-results]');
            const clear   = root.querySelector('[data-picker-clear]');
            let timer = null, items = [], active = -1;

            function close() { results.classList.remove('is-open'); active = -1; }
            function render() {
                if (!items.length) { results.innerHTML = '<div class="picker__empty">Sin coincidencias</div>'; results.classList.add('is-open'); return; }
                results.innerHTML = items.map(function (u, i) {
                    const ava = u.avatar ? '<img src="' + u.avatar + '" alt="">' : (u.nombre || '?').charAt(0).toUpperCase();
                    return '<div class="picker__item' + (i === active ? ' is-active' : '') + '" data-i="' + i + '">' +
                        '<span class="picker__ava">' + ava + '</span>' +
                        '<span class="picker__name">' + u.nombre + '</span></div>';
                }).join('');
                results.classList.add('is-open');
                results.querySelectorAll('.picker__item').forEach(function (el) {
                    el.addEventListener('mousedown', function (e) { e.preventDefault(); choose(items[+el.dataset.i]); });
                });
            }
            function choose(u) { input.value = u.nombre; hidden.value = u.id; root.classList.add('has-value'); close(); }
            function fetchUsers(q) {
                fetch(ENDPOINT + encodeURIComponent(q)).then(r => r.json()).then(function (data) {
                    items = data || []; active = -1; render();
                }).catch(function () { items = []; render(); });
            }

            input.addEventListener('input', function () {
                hidden.value = '';                      // al reescribir se invalida la selección previa
                root.classList.toggle('has-value', this.value.trim() !== '');
                const q = this.value.trim();
                clearTimeout(timer);
                if (q.length < 2) { close(); return; }
                timer = setTimeout(function () { fetchUsers(q); }, 180);
            });
            input.addEventListener('keydown', function (e) {
                if (!results.classList.contains('is-open')) return;
                if (e.key === 'ArrowDown') { e.preventDefault(); active = Math.min(active + 1, items.length - 1); render(); }
                else if (e.key === 'ArrowUp') { e.preventDefault(); active = Math.max(active - 1, 0); render(); }
                else if (e.key === 'Enter') { if (active >= 0) { e.preventDefault(); choose(items[active]); } }
                else if (e.key === 'Escape') { close(); }
            });
            input.addEventListener('blur', function () { setTimeout(close, 150); });
            if (clear) clear.addEventListener('click', function () { input.value = ''; hidden.value = ''; root.classList.remove('has-value'); input.focus(); });
        });
    })();
})();
