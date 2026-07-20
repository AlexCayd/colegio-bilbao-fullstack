/* blog-noticias-categorias-index
   Migrado desde el <script> embebido de views/blog/noticias/categorias/index.php */
(function () {
    if (!document.body || document.body.dataset.page !== 'blog-noticias-categorias-index') return;
    (function () {
        let _ubmName = '';
        const modal  = document.getElementById('deleteModal');
        const input  = document.getElementById('ubm-input');
        const submit = document.getElementById('ubm-submit');

        window.confirmarEliminar = function (id, nombre) {
            _ubmName = nombre;
            document.getElementById('ubm-name').textContent = nombre;
            document.getElementById('ubm-id').value = id;
            input.value = ''; input.classList.remove('is-valid');
            submit.disabled = true;
            modal.classList.add('is-open');
            document.body.style.overflow = 'hidden';
            setTimeout(() => input.focus(), 300);
        };
        window.cerrarModalEliminar = function () {
            modal.classList.remove('is-open');
            document.body.style.overflow = '';
            input.value = ''; input.classList.remove('is-valid');
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
            if (main) { main.style.transition = 'opacity .35s ease'; main.style.opacity = '0'; main.style.transform = 'translateY(10px)'; }
            setTimeout(() => this.submit(), 380);
        });
        modal.addEventListener('click', e => { if (e.target === modal) cerrarModalEliminar(); });
        document.addEventListener('keydown', e => { if (e.key === 'Escape') cerrarModalEliminar(); });
    })();

    (function () {
        const toast = document.getElementById('alexToast');
        if (!toast) return;
        requestAnimationFrame(() => setTimeout(() => toast.classList.add('is-visible'), 80));
        let timer = setTimeout(cerrarAlexToast, 5600);
        function cerrarAlexToast() {
            clearTimeout(timer);
            toast.style.top = '-160px';
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 400);
        }
        window.cerrarAlexToast = cerrarAlexToast;
    })();
})();
