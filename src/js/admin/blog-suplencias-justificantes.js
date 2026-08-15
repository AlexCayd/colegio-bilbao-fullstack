/* blog-suplencias-justificantes
   Cola de justificantes vencidos: descargar (y cerrar) o eliminar.

   La descarga NO resuelve por sí sola en el servidor —el navegador podría cancelarla—
   así que se dispara la descarga, se deja un margen para que arranque y solo entonces
   se envía la resolución. Eliminar sí pide confirmación explícita: borra un archivo
   que puede ser un parte médico y no hay vuelta atrás. */
(function () {
    if (!document.body || document.body.dataset.page !== 'blog-suplencias-justificantes') return;

    var form   = document.getElementById('jusForm');
    var campoId = document.getElementById('jusFormId');
    var campoAc = document.getElementById('jusFormAccion');
    var modal  = document.getElementById('jusModal');
    if (!form) return;

    function enviar(id, accion) {
        campoId.value = String(id);
        campoAc.value = accion;
        form.submit();
    }

    // ── Descargar ──
    document.querySelectorAll('[data-jus-descargar]').forEach(function (a) {
        a.addEventListener('click', function () {
            var id = a.getAttribute('data-jus-descargar');
            // El <a download> sigue su curso; el POST va detrás con un respiro para
            // que la descarga haya empezado antes de que el archivo desaparezca.
            setTimeout(function () { enviar(id, 'descargado'); }, 1200);
        });
    });

    // ── Eliminar (con confirmación) ──
    var pendiente = null;
    document.querySelectorAll('[data-jus-eliminar]').forEach(function (b) {
        b.addEventListener('click', function () {
            pendiente = b.getAttribute('data-jus-eliminar');
            if (!modal) { enviar(pendiente, 'eliminado'); return; }
            var quien  = modal.querySelector('[data-jus-quien]');
            var cuando = modal.querySelector('[data-jus-cuando]');
            if (quien)  quien.textContent  = b.getAttribute('data-nombre') || '—';
            if (cuando) cuando.textContent = b.getAttribute('data-fecha') || '';
            // `hidden` es lo que .cat-modal mira para ocultarse; una clase propia no
            // tiene CSS detrás y dejaba la modal abierta desde la carga.
            modal.hidden = false;
        });
    });

    function cerrar() {
        pendiente = null;
        if (!modal) return;
        modal.hidden = true;
    }

    if (modal) {
        modal.addEventListener('click', function (ev) {
            if (ev.target === modal || ev.target.closest('[data-jus-cancelar]')) cerrar();
            if (ev.target.closest('[data-jus-confirmar]') && pendiente) enviar(pendiente, 'eliminado');
        });
        document.addEventListener('keydown', function (ev) {
            if (ev.key === 'Escape' && !modal.hidden) cerrar();
        });
    }
})();
