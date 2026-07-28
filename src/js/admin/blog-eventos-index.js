/* blog-eventos-index — modal de borrado + toast */
(function () {
    if (!document.body || document.body.dataset.page !== 'blog-eventos-index') return;

    var modal = document.getElementById('evDeleteModal');

    // Expuestas por los onclick= inline de la vista
    window.eventoEliminar = function (id, nombre) {
        document.getElementById('evDeleteId').value = id;
        document.getElementById('evDeleteName').textContent = nombre;
        if (modal) modal.classList.add('is-open');
    };
    window.cerrarModalEvento = function () {
        if (modal) modal.classList.remove('is-open');
    };

    // Cerrar con Escape o clic en el fondo
    if (modal) {
        modal.addEventListener('click', function (e) {
            if (e.target === modal) window.cerrarModalEvento();
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') window.cerrarModalEvento();
        });
    }

    // El toast de Alex lo lleva admin-toast.js (#alexToast)
})();
