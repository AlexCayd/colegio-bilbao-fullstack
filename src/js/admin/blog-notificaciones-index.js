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

    /** Resta uno a la campana del topbar; al llegar a 0 la deja apagada. */
    function bajarBadge() {
        var badge = document.querySelector('[data-notif-badge]');
        if (!badge) return;
        var n = parseInt(badge.textContent, 10) - 1;
        if (isNaN(n) || n <= 0) {
            var campana = badge.closest('.admin-topbar__bell');
            badge.remove();
            if (campana) campana.classList.remove('has-pend');
        } else {
            badge.textContent = n;
        }
    }

    function subirBadge() {
        var campana = document.querySelector('.admin-topbar__bell');
        if (!campana) return;
        var badge = campana.querySelector('[data-notif-badge]');
        if (badge) {
            badge.textContent = (parseInt(badge.textContent, 10) || 0) + 1;
        } else {
            badge = document.createElement('span');
            badge.className = 'admin-topbar__bell-badge';
            badge.setAttribute('data-notif-badge', '');
            badge.textContent = '1';
            campana.appendChild(badge);
        }
        campana.classList.add('has-pend');
    }

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
