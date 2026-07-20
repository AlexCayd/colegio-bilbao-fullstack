/* blog-categorias-editar
   Migrado desde el <script> embebido de views/blog/categorias/editar.php */
(function () {
    if (!document.body || document.body.dataset.page !== 'blog-categorias-editar') return;
    (function () {
        function hexToRgb(hex) {
            return { r: parseInt(hex.slice(1,3),16), g: parseInt(hex.slice(3,5),16), b: parseInt(hex.slice(5,7),16) };
        }

        const slugEl    = document.getElementById('slug');
        const slugDisp  = document.getElementById('slug-display');
        const badgeEl   = document.getElementById('badge-preview');
        const descEl    = document.getElementById('descripcion');
        const descCount = document.getElementById('desc-count');
        const colorInput = document.getElementById('color-value');

        // Inicializar desc-count
        if (descEl && descCount) {
            descCount.textContent = descEl.value.length + ' / 240';
            descEl.addEventListener('input', function () {
                descCount.textContent = this.value.length + ' / 240';
            });
        }

        // Slug display
        if (slugEl) {
            slugEl.addEventListener('input', function () {
                if (slugDisp) slugDisp.textContent = this.value || 'tu-categoria';
            });
        }

        // Badge preview desde nombre
        document.getElementById('nombre')?.addEventListener('input', function () {
            if (badgeEl) badgeEl.textContent = this.value.trim() || 'Vista previa';
        });

        // Selector de color
        document.querySelectorAll('.admin-color-swatch').forEach(function (swatch) {
            swatch.addEventListener('click', function () {
                document.querySelectorAll('.admin-color-swatch').forEach(s => s.classList.remove('selected'));
                this.classList.add('selected');
                const color = this.dataset.color;
                if (colorInput) colorInput.value = color;
                if (badgeEl) {
                    const { r, g, b } = hexToRgb(color);
                    badgeEl.style.background = 'rgba(' + r + ',' + g + ',' + b + ',0.12)';
                    badgeEl.style.color = color;
                }
            });
        });

        // Fade-out al guardar
        document.getElementById('form-editar-categoria')?.addEventListener('submit', function () {
            const main = document.querySelector('.admin-main');
            if (main) {
                main.style.transition = 'opacity .35s ease, transform .35s ease';
                main.style.opacity    = '0';
                main.style.transform  = 'translateY(10px)';
            }
        });

        // Modal eliminar
        let _ubmName = '';
        const modal  = document.getElementById('deleteModal');
        const input  = document.getElementById('ubm-input');
        const submit = document.getElementById('ubm-submit');

        window.abrirModalEliminar = function (id, nombre) {
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

        document.getElementById('ubm-form').addEventListener('submit', function (e) {
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
})();
