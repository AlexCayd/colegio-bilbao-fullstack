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

    // El toast de Alex lo lleva admin-toast.js (#alexToast)
})();
