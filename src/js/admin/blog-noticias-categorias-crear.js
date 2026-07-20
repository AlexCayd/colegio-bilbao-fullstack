/* blog-noticias-categorias-crear
   Migrado desde el <script> embebido de views/blog/noticias/categorias/crear.php */
(function () {
    if (!document.body || document.body.dataset.page !== 'blog-noticias-categorias-crear') return;
    (function () {
        function toSlug(str) {
            return str.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '')
                .replace(/[^a-z0-9\s-]/g, '').trim().replace(/\s+/g, '-').replace(/-+/g, '-');
        }
        function hexToRgb(hex) {
            return { r: parseInt(hex.slice(1,3),16), g: parseInt(hex.slice(3,5),16), b: parseInt(hex.slice(5,7),16) };
        }

        const nombreEl   = document.getElementById('nombre');
        const slugEl     = document.getElementById('slug');
        const slugDisp   = document.getElementById('slug-display');
        const badgeEl    = document.getElementById('badge-preview');
        const descEl     = document.getElementById('descripcion');
        const descCount  = document.getElementById('desc-count');
        const colorInput = document.getElementById('color-value');

        if (descEl && descCount) {
            descCount.textContent = descEl.value.length + ' / 240';
            descEl.addEventListener('input', function () { descCount.textContent = this.value.length + ' / 240'; });
        }

        if (nombreEl) {
            nombreEl.addEventListener('input', function () {
                if (slugEl && !slugEl.dataset.manual) {
                    const s = toSlug(this.value);
                    slugEl.value = s;
                    if (slugDisp) slugDisp.textContent = s || 'tu-categoria';
                }
                if (badgeEl) badgeEl.textContent = this.value.trim() || 'Vista previa';
            });
        }

        if (slugEl) {
            slugEl.addEventListener('input', function () {
                this.dataset.manual = '1';
                if (slugDisp) slugDisp.textContent = this.value || 'tu-categoria';
            });
        }

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

        document.getElementById('form-categoria')?.addEventListener('submit', function () {
            const main = document.querySelector('.admin-main');
            if (main) { main.style.transition = 'opacity .35s ease'; main.style.opacity = '0'; main.style.transform = 'translateY(10px)'; }
        });
    })();
})();
