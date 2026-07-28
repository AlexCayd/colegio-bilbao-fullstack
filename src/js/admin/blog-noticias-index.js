/* blog-noticias-index
   Migrado desde el <script> embebido de views/blog/noticias/index.php */
(function () {
    if (!document.body || document.body.dataset.page !== 'blog-noticias-index') return;
    (function () {
        let _ubmName = '';
        const modal  = document.getElementById('deleteModal');
        const input  = document.getElementById('ubm-input');
        const submit = document.getElementById('ubm-submit');

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

        modal.addEventListener('click', e => { if (e.target === modal) cerrarModalEliminar(); });
        document.addEventListener('keydown', e => { if (e.key === 'Escape') cerrarModalEliminar(); });
    })();

    // El ordenamiento y la paginación los lleva admin-table.js ([data-table])

    // El toast de Alex lo lleva admin-toast.js (#alexToast)

    // Revision confirm modal
    (function() {
        const modal   = document.getElementById('revisionModal');
        const cancel  = document.getElementById('revisionModalCancel');
        const confirm = document.getElementById('revisionModalConfirm');
        if (!modal) return;

        let pendingFormId = null;

        window.abrirRevisionModal = function(formId) {
            pendingFormId = formId;
            modal.classList.add('is-open');
        };

        cancel.addEventListener('click', function() {
            modal.classList.remove('is-open');
            pendingFormId = null;
        });

        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                modal.classList.remove('is-open');
                pendingFormId = null;
            }
        });

        confirm.addEventListener('click', function() {
            modal.classList.remove('is-open');
            if (pendingFormId) {
                const form = document.getElementById(pendingFormId);
                if (form) form.submit();
            }
            pendingFormId = null;
        });
    })();
})();
