/* layout-admin
   Migrado desde el <script> embebido de views/layout-admin.php */
/* Compartido: se activa por existencia de sus elementos */
(function () {
    (function() {
        const modal = document.getElementById('alexModal');
        const close = document.getElementById('alexModalClose');
        if (!modal || !close) return;

        setTimeout(() => modal.classList.add('is-open'), 300);

        function dismiss() {
            modal.classList.remove('is-open');
            setTimeout(() => modal.remove(), 350);
            fetch('/dashboard/notificaciones/leer', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                body: 'id=' + encodeURIComponent(modal.dataset.notifId)
            });
        }

        close.addEventListener('click', dismiss);
        modal.addEventListener('click', function(e) { if (e.target === modal) dismiss(); });
    })();
})();
