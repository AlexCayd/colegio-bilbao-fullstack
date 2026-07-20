/* blog-notificaciones-index
   Migrado desde el <script> embebido de views/blog/notificaciones/index.php */
(function () {
    if (!document.body || document.body.dataset.page !== 'blog-notificaciones-index') return;
    function marcarLeida(btn) {
        const id  = btn.dataset.id;
        const row = document.getElementById('notif-' + id);

        fetch('/dashboard/notificaciones/leer', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: 'id=' + encodeURIComponent(id)
        })
        .then(r => r.json())
        .then(data => {
            if (data.ok && row) {
                row.classList.remove('notif-row--unread');
                btn.remove();

                // Update unread count in badge
                const badge = document.querySelector('.admin-nav__badge[style*="static"]');
                if (badge) {
                    const count = parseInt(badge.textContent, 10) - 1;
                    if (count <= 0) badge.remove();
                    else badge.textContent = count;
                }
                // Sidebar badge
                const sideBadge = document.querySelectorAll('.admin-nav__badge');
                sideBadge.forEach(b => {
                    const c = parseInt(b.textContent, 10) - 1;
                    if (c <= 0) b.remove();
                    else b.textContent = c;
                });
            }
        });
    }

    /* Exponer para los atributos inline (onclick/onchange) */
    window.marcarLeida = marcarLeida;
})();
