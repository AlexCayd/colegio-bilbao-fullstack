/* blog-autores-index
   Migrado desde el <script> embebido de views/blog/autores/index.php */
(function () {
    if (!document.body || document.body.dataset.page !== 'blog-autores-index') return;
    document.getElementById('buscarAutor').addEventListener('input', function () {
        const q = this.value.toLowerCase().trim();
        document.querySelectorAll('.autor-card').forEach(card => {
            card.hidden = q && !card.dataset.nombre.includes(q);
        });
    });

    document.querySelectorAll('.autor-card__toggle').forEach(btn => {
        btn.addEventListener('click', async function () {
            const autorId  = this.dataset.autorId;
            const expanded = this.getAttribute('aria-expanded') === 'true';
            const panel    = document.getElementById('contenido-' + autorId);

            if (expanded) {
                this.setAttribute('aria-expanded', 'false');
                panel.hidden = true;
                return;
            }

            this.setAttribute('aria-expanded', 'true');
            panel.hidden = false;

            if (panel.dataset.loaded) return;
            panel.dataset.loaded = '1';

            try {
                const res  = await fetch('/dashboard/autores?autor=' + autorId, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                const html = await res.text();
                const tmp  = document.createElement('div');
                tmp.innerHTML = html;
                const items = tmp.querySelector('#contenido-' + autorId);
                if (items) panel.innerHTML = items.innerHTML;
                else panel.innerHTML = '<p style="color:#94A3B8;font-size:.82rem;padding:8px 0;">Sin contenido.</p>';
            } catch (e) {
                panel.innerHTML = '<p style="color:#ef4444;font-size:.82rem;padding:8px 0;">Error al cargar.</p>';
            }
        });
    });
})();
