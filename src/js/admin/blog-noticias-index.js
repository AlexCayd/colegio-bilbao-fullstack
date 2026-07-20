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

    (function () {
        const table = document.getElementById('tablaNoticias');
        if (!table) return;
        let sortCol = -1, sortAsc = true;

        table.querySelectorAll('th.sortable').forEach(th => {
            th.addEventListener('click', function () {
                const col = parseInt(this.dataset.col);
                if (sortCol === col) { sortAsc = !sortAsc; }
                else { sortCol = col; sortAsc = true; }

                table.querySelectorAll('th.sortable').forEach(h => h.classList.remove('sort-asc','sort-desc'));
                this.classList.add(sortAsc ? 'sort-asc' : 'sort-desc');

                const tbody = table.querySelector('tbody');
                const rows  = Array.from(tbody.querySelectorAll('tr'));
                rows.sort((a, b) => {
                    const va = a.querySelectorAll('td')[col]?.dataset.val ?? '';
                    const vb = b.querySelectorAll('td')[col]?.dataset.val ?? '';
                    const na = parseFloat(va), nb = parseFloat(vb);
                    const cmp = (!isNaN(na) && !isNaN(nb)) ? na - nb : va.localeCompare(vb, 'es', {sensitivity:'base'});
                    return sortAsc ? cmp : -cmp;
                });
                rows.forEach(r => tbody.appendChild(r));
            });
        });
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
