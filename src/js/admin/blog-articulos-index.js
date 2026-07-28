/* blog-articulos-index
   Migrado desde el <script> embebido de views/blog/articulos/index.php */
(function () {
    if (!document.body || document.body.dataset.page !== 'blog-articulos-index') return;
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

    /* ── Modal confirmar envío a revisión (inline) ── */
    (function () {
        const revModal      = document.getElementById('revisionModal');
        const btnRevCancel  = document.getElementById('btnRevCancelar');
        const btnRevConfirm = document.getElementById('btnRevConfirmar');
        let pendingFormId   = null;

        document.querySelectorAll('.btn-enviar-revision-inline').forEach(function (btn) {
            btn.addEventListener('click', function () {
                pendingFormId = this.dataset.formId;
                revModal && revModal.classList.add('is-open');
            });
        });

        btnRevCancel  && btnRevCancel.addEventListener('click',  function () { revModal.classList.remove('is-open'); });
        btnRevConfirm && btnRevConfirm.addEventListener('click', function () {
            if (pendingFormId) {
                const f = document.getElementById(pendingFormId);
                if (f) f.submit();
            }
        });
        revModal && revModal.addEventListener('click', function (e) {
            if (e.target === revModal) revModal.classList.remove('is-open');
        });
    })();
})();
