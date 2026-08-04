/* blog-usuarios-index
   Migrado desde el <script> embebido de views/blog/usuarios/index.php */
(function () {
    if (!document.body || document.body.dataset.page !== 'blog-usuarios-index') return;
    (function () {
        let _ubmName = '';
        const modal  = document.getElementById('deleteModal');
        const input  = document.getElementById('ubm-input');
        const submit = document.getElementById('ubm-submit');
        const form   = document.getElementById('ubm-form');
        // Sin permiso de borrado la vista no pinta el modal: no hay nada que enganchar
        if (!modal || !input || !submit || !form) return;

        window.confirmarEliminar = function (id, nombre) {
            _ubmName = nombre;
            document.getElementById('ubm-name').textContent = nombre;
            document.getElementById('ubm-id').value = id;
            input.value = '';
            input.classList.remove('is-valid');
            submit.disabled = true;
            modal.classList.add('is-open');
            document.body.style.overflow = 'hidden';
            setTimeout(() => input.focus(), 300);
        };

        window.cerrarModalEliminar = function () {
            modal.classList.remove('is-open');
            document.body.style.overflow = '';
            input.value = '';
            input.classList.remove('is-valid');
            submit.disabled = true;
        };

        input.addEventListener('input', function () {
            const match = this.value === _ubmName;
            this.classList.toggle('is-valid', match);
            submit.disabled = !match;
        });

        /* Fade-out antes de enviar el formulario de eliminación */
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            const main = document.querySelector('.admin-main');
            modal.classList.remove('is-open');
            document.body.style.overflow = '';
            if (main) {
                main.style.transition = 'opacity .35s ease, transform .35s ease';
                main.style.opacity    = '0';
                main.style.transform  = 'translateY(10px)';
            }
            setTimeout(() => this.submit(), 380);
        });

        modal.addEventListener('click', function (e) { if (e.target === this) cerrarModalEliminar(); });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') cerrarModalEliminar(); });
    })();

    /* ── Buscador ──
       Un solo input para las tres tablas (administradores · profesores ·
       administrativos y prefectura). Filtra con `is-filtered` en vez de `hidden`
       porque es lo que admin-table.js sabe recontar: las filas filtradas pierden
       `data-pager-item` y la paginación deja de contarlas. */
    (function () {
        const input = document.querySelector('[data-usr-buscar]');
        if (!input) return;

        const limpiar  = document.querySelector('[data-usr-limpiar]');
        const resumen  = document.querySelector('[data-usr-resumen]');
        const paneles  = Array.prototype.map.call(
            document.querySelectorAll('[data-usr-panel]'),
            function (panel) {
                return {
                    filas:  panel.querySelectorAll('[data-usr-row]'),
                    tabla:  panel.querySelector('[data-table]'),
                    cuenta: panel.querySelector('[data-usr-count]'),
                    vacio:  panel.querySelector('[data-usr-empty]')
                };
            }
        );
        if (!paneles.length) return;

        /** Quita acentos para que "Perez" encuentre a "Pérez". */
        function normalizar(s) {
            return s.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
        }

        function filtrar() {
            // Varios términos = todos deben aparecer, en cualquier orden y campo.
            const terminos = normalizar(input.value).split(/\s+/).filter(Boolean);
            const activo   = terminos.length > 0;
            let total = 0;

            paneles.forEach(function (p) {
                let visibles = 0;

                Array.prototype.forEach.call(p.filas, function (fila) {
                    const heno  = normalizar(fila.dataset.buscar || '');
                    const match = !activo || terminos.every(function (t) { return heno.indexOf(t) !== -1; });
                    fila.classList.toggle('is-filtered', !match);
                    if (match) visibles++;
                });

                total += visibles;
                if (p.cuenta) p.cuenta.textContent = visibles;
                if (p.vacio)  p.vacio.hidden = visibles > 0;
                if (p.tabla)  p.tabla.closest('.admin-table-scroll').hidden = visibles === 0;
                if (p.tabla && window.AdminTable) window.AdminTable.refrescar(p.tabla);
            });

            if (limpiar) limpiar.hidden = !activo;
            if (resumen) {
                resumen.hidden = !activo;
                resumen.textContent = total === 1
                    ? '1 persona coincide con la búsqueda.'
                    : total + ' personas coinciden con la búsqueda.';
            }
        }

        input.addEventListener('input', filtrar);
        input.addEventListener('search', filtrar);   // la "x" nativa de type="search"

        if (limpiar) {
            limpiar.addEventListener('click', function () {
                input.value = '';
                filtrar();
                input.focus();
            });
        }
    })();

    // El toast de Alex lo lleva admin-toast.js (#alexToast)
})();
