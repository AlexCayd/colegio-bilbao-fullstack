/* blog-suplencias-index
   Migrado desde el <script> embebido de views/blog/suplencias/index.php */
(function () {
    if (!document.body || document.body.dataset.page !== 'blog-suplencias-index') return;
    (function () {
        // Búsqueda en vivo sobre las filas cargadas. Marca con `is-filtered` en vez
        // de tocar el style, para que admin-table.js pueda excluirlas del paginado.
        const input = document.getElementById('suplSearch');
        const tabla = document.getElementById('suplTable');
        const rows  = Array.from(document.querySelectorAll('.supl-row'));
        const none  = document.getElementById('suplNoResults');
        if (input) {
            input.addEventListener('input', function () {
                const q = this.value.trim().toLowerCase();
                let visible = 0;
                rows.forEach(r => {
                    const match = !q || r.dataset.search.includes(q);
                    r.classList.toggle('is-filtered', !match);
                    if (match) visible++;
                });
                if (none) none.style.display = (rows.length && visible === 0) ? 'block' : 'none';
                if (window.AdminTable) window.AdminTable.refrescar(tabla);
            });
        }
        // Los filtros de fecha son .bilbao-date: el hidden emite `change` al elegir
        // día o al limpiar, y ahí se reenvía el formulario (antes era un onchange
        // inline en el <input type="date">).
        var formFiltros = document.getElementById('suplFilters');
        if (formFiltros) {
            formFiltros.querySelectorAll('[data-date-value]').forEach(function (h) {
                h.addEventListener('change', function () { formFiltros.submit(); });
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

    // El toast de Alex lo lleva admin-toast.js (#alexToast)
})();
