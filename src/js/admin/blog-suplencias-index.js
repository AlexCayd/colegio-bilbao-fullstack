/* blog-suplencias-index
   Migrado desde el <script> embebido de views/blog/suplencias/index.php */
(function () {
    if (!document.body || document.body.dataset.page !== 'blog-suplencias-index') return;
    (function () {
        // Búsqueda inteligente en vivo sobre las filas cargadas
        const input = document.getElementById('suplSearch');
        const rows  = Array.from(document.querySelectorAll('.supl-row'));
        const none  = document.getElementById('suplNoResults');
        if (input) {
            input.addEventListener('input', function () {
                const q = this.value.trim().toLowerCase();
                let visible = 0;
                rows.forEach(r => {
                    const match = !q || r.dataset.search.includes(q);
                    r.style.display = match ? '' : 'none';
                    if (match) visible++;
                });
                if (none) none.style.display = (rows.length && visible === 0) ? 'block' : 'none';
            });
        }
        window.suplEliminar = function (id, nombre) {
            document.getElementById('suplDeleteId').value = id;
            document.getElementById('suplDeleteName').textContent = nombre;
            document.getElementById('suplDeleteModal').style.display = 'flex';
        };

        // ── Select personalizado (estado) ──
        document.querySelectorAll('[data-select]').forEach(function (sel) {
            const btn   = sel.querySelector('[data-select-btn]');
            const menu  = sel.querySelector('[data-select-menu]');
            const value = sel.querySelector('[data-select-value]');
            const label = sel.querySelector('[data-select-label]');
            const form  = document.getElementById('suplFilters');
            btn.addEventListener('click', function (e) { e.stopPropagation(); sel.classList.toggle('is-open'); });
            menu.querySelectorAll('[data-value]').forEach(function (opt) {
                opt.addEventListener('click', function () {
                    value.value = opt.dataset.value;
                    label.textContent = opt.textContent;
                    sel.classList.remove('is-open');
                    if (form) form.submit();
                });
            });
            document.addEventListener('click', function (e) { if (!sel.contains(e.target)) sel.classList.remove('is-open'); });
        });
    })();

    (function () {
        const toast = document.getElementById('alexToast');
        if (!toast) return;
        requestAnimationFrame(() => setTimeout(() => toast.classList.add('is-visible'), 80));
        let timer = setTimeout(cerrarAlexToast, 5600);
        function cerrarAlexToast() { clearTimeout(timer); toast.style.top = '-160px'; toast.style.opacity = '0'; setTimeout(() => toast.remove(), 400); }
        window.cerrarAlexToast = cerrarAlexToast;
    })();
})();
