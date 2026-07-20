/* blog-testimoniales-index
   Migrado desde el <script> embebido de views/blog/testimoniales/index.php */
(function () {
    if (!document.body || document.body.dataset.page !== 'blog-testimoniales-index') return;
    (function(){
        var modal = document.getElementById('confirm-modal');
        var msgEl = document.getElementById('cmodal-msg');
        var pendingForm = null;

        function showModal(form, msg) {
            pendingForm = form;
            msgEl.textContent = msg;
            modal.setAttribute('aria-hidden', 'false');
        }
        document.getElementById('cmodal-cancel').addEventListener('click', function(){
            modal.setAttribute('aria-hidden', 'true');
            pendingForm = null;
        });
        document.getElementById('cmodal-ok').addEventListener('click', function(){
            modal.setAttribute('aria-hidden', 'true');
            if (pendingForm) pendingForm.submit();
            pendingForm = null;
        });
        modal.addEventListener('click', function(e){
            if (e.target === modal) { modal.setAttribute('aria-hidden', 'true'); pendingForm = null; }
        });
        document.addEventListener('keydown', function(e){
            if (e.key === 'Escape' && modal.getAttribute('aria-hidden') === 'false') {
                modal.setAttribute('aria-hidden', 'true');
                pendingForm = null;
            }
        });

        document.querySelectorAll('form[data-confirm]').forEach(function(form){
            form.addEventListener('submit', function(e){
                e.preventDefault();
                showModal(form, form.dataset.confirm);
            });
        });
    })();
})();
