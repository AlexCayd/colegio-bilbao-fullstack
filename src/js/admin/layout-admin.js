/* layout-admin
   Migrado desde el <script> embebido de views/layout-admin.php */
/* Compartido: se activa por existencia de sus elementos */
(function () {
    (function() {
        const modal = document.getElementById('alexModal');
        const close = document.getElementById('alexModalClose');
        if (!modal || !close) return;

        setTimeout(() => modal.classList.add('is-open'), 300);

        /* Marca la notificación como leída. `keepalive` porque el CTA "Ver detalle"
           navega de inmediato: sin él el navegador cancela el fetch al descargar la
           página y el aviso seguiría pendiente. */
        function marcarLeida() {
            return fetch('/dashboard/notificaciones/leer', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                body: 'id=' + encodeURIComponent(modal.dataset.notifId),
                keepalive: true
            });
        }

        function dismiss() {
            modal.classList.remove('is-open');
            setTimeout(() => modal.remove(), 350);
            marcarLeida();
        }

        close.addEventListener('click', dismiss);
        modal.addEventListener('click', function(e) { if (e.target === modal) dismiss(); });

        /* El CTA es un <a> DENTRO de la tarjeta, así que nunca disparaba dismiss()
           (que solo escuchaba el botón "Entendido" y el clic en el backdrop). La
           notificación seguía sin leer y el mismo modal reaparecía en la página de
           destino, una y otra vez: el bucle de "Ver detalle". */
        const cta = modal.querySelector('[data-alex-cta]');
        if (cta) cta.addEventListener('click', marcarLeida);
    })();
})();
