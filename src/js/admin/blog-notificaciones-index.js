/* blog-notificaciones-index — bandeja de notificaciones.

   Marcar leída = BORRAR. El borrado se hace de inmediato y la red de seguridad es
   un "Deshacer" de 6 s que reinserta la fila con el payload que devolvió el DELETE.
   Se borra primero y se restaura después (en vez de diferir el DELETE) para que
   recargar o cerrar la pestaña a mitad de la cuenta atrás no deje basura en la BD. */
(function () {
    if (!document.body || document.body.dataset.page !== 'blog-notificaciones-index') return;

    var MS_DESHACER = 6000;

    var undo      = document.getElementById('ntUndo');
    var undoBtn   = undo && undo.querySelector('[data-nt-undo]');
    var lista     = document.getElementById('ntList');
    var modal     = document.getElementById('ntModal');
    var pendiente = null;   // { datos, fila }
    var reloj     = null;

    function post(url, datos) {
        var cuerpo = Object.keys(datos)
            .map(function (k) { return encodeURIComponent(k) + '=' + encodeURIComponent(datos[k]); })
            .join('&');
        return fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: cuerpo
        }).then(function (r) { return r.json(); });
    }

    /* ── Contadores de pendientes ──
       Hay DOS y los dos están siempre en el DOM: la campana del topbar y el acceso a
       «Avisos» dentro del cajón, que es el que se ve por debajo de 1024px. Quién se
       pinta lo decide el CSS, así que actualizar solo el primero —como se hacía—
       dejaba en móvil el número visible congelado mientras se movía el invisible. */
    function hostsBadge() {
        return [
            document.querySelector('.admin-topbar__bell'),
            document.querySelector('[data-notif-cta]')
        ].filter(Boolean);
    }

    /** Deja el contador de `host` en `n`; en 0 lo retira y apaga el estado. */
    function pintarBadge(host, n) {
        var badge = host.querySelector('[data-notif-badge]');
        if (n <= 0) {
            if (badge) badge.remove();
            host.classList.remove('has-pend');
            return;
        }
        if (!badge) {
            badge = document.createElement('span');
            badge.setAttribute('data-notif-badge', '');
            /* Cada superficie tiene su pastilla: la del topbar cuelga de la campana,
               la del cajón es la misma de los badges de subopción del sidebar. */
            badge.className = host.classList.contains('admin-topbar__bell')
                ? 'admin-topbar__bell-badge'
                : 'admin-nav__badge';
            host.appendChild(badge);
        }
        badge.textContent = n > 99 ? '99+' : n;
        host.classList.add('has-pend');
    }

    function moverBadge(delta) {
        hostsBadge().forEach(function (host) {
            var badge = host.querySelector('[data-notif-badge]');
            var n = badge ? parseInt(badge.textContent, 10) : 0;
            pintarBadge(host, (isNaN(n) ? 0 : n) + delta);
        });
    }

    function bajarBadge() { moverBadge(-1); }
    function subirBadge() { moverBadge(1); }

    function repaginar() {
        var pager = document.querySelector('[data-pager]');
        if (pager && window.AdminPager) window.AdminPager.refrescar(pager);
    }

    function ocultarUndo() {
        if (!undo) return;
        undo.classList.remove('is-visible');
        // El atributo hidden se repone al acabar la transición para reiniciar la barra
        setTimeout(function () { if (!undo.classList.contains('is-visible')) undo.hidden = true; }, 220);
    }

    /** Confirma el borrado: la fila desaparece del DOM y se cierra el aviso. */
    function consolidar() {
        if (pendiente && pendiente.fila && pendiente.fila.parentNode) {
            pendiente.fila.remove();
            repaginar();
        }
        pendiente = null;
        ocultarUndo();
    }

    function eliminar(btn) {
        var fila = btn.closest('[data-notif-id]');
        if (!fila) return;
        var id = fila.dataset.notifId;

        // Si ya había un borrado esperando, se da por bueno antes de encadenar otro
        if (pendiente) { clearTimeout(reloj); consolidar(); }

        post('/dashboard/notificaciones/eliminar', { id: id }).then(function (data) {
            if (!data || !data.ok) return;

            if (fila.classList.contains('is-unread')) bajarBadge();
            fila.classList.add('is-going');

            pendiente = { datos: data.notif || {}, fila: fila };

            if (undo) {
                undo.hidden = false;
                // Reflow para que la animación de la barra reinicie en cada borrado
                void undo.offsetWidth;
                undo.classList.add('is-visible');
            }
            clearTimeout(reloj);
            reloj = setTimeout(consolidar, MS_DESHACER);
        });
    }

    function deshacer() {
        if (!pendiente) return;
        clearTimeout(reloj);
        var d = pendiente.datos, fila = pendiente.fila;

        post('/dashboard/notificaciones/restaurar', {
            tipo:            d.tipo            || '',
            mensaje:         d.mensaje         || '',
            modulo:          d.modulo          || 'general',
            nivel:           d.nivel           || 'info',
            enlace:          d.enlace          || '',
            referencia_id:   d.referencia_id   || '',
            referencia_tipo: d.referencia_tipo || ''
        }).then(function (res) {
            if (!res || !res.ok) return;
            fila.classList.remove('is-going');
            if (fila.classList.contains('is-unread')) subirBadge();
        });

        pendiente = null;
        ocultarUndo();
    }

    document.addEventListener('click', function (e) {
        var del = e.target.closest('[data-nt-del]');
        if (del) { eliminar(del); return; }

        if (e.target.closest('[data-nt-undo]')) { deshacer(); return; }

        if (e.target.closest('[data-nt-clear]')) { if (modal) modal.hidden = false; return; }
        if (e.target.closest('[data-nt-cancel]')) { if (modal) modal.hidden = true; return; }
        if (modal && !modal.hidden && e.target === modal) modal.hidden = true;
    });

    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        if (modal && !modal.hidden) modal.hidden = true;
    });

    // Salir de la página con un borrado a medias no debe dejarlo colgando:
    // ya está persistido, solo se limpia el estado del cliente.
    window.addEventListener('pagehide', function () { clearTimeout(reloj); });

    if (undoBtn) undoBtn.addEventListener('click', deshacer);
})();
