/* admin-catalogo — modal de borrado de los catálogos (aulas, grupos).
   Componente compartido: no lleva guarda de página, se activa por la existencia
   de #catModal (views/blog/_catalogo-modal.php). */
(function () {
    var modal = document.getElementById('catModal');
    if (!modal) return;

    var campoId     = modal.querySelector('[data-cat-id]');
    var campoNombre = modal.querySelector('[data-cat-nombre]');

    function abrir(id, nombre) {
        if (campoId)     campoId.value = id;
        if (campoNombre) campoNombre.textContent = nombre;
        modal.hidden = false;
    }
    function cerrar() { modal.hidden = true; }

    modal.addEventListener('click', function (e) {
        if (e.target === modal || e.target.closest('[data-cat-cancel]')) cerrar();
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !modal.hidden) cerrar();
    });

    /* Lo llama el onclick inline de cada fila: hay que exponerlo o desaparece
       al quedar encapsulado en el bundle. */
    window.catEliminar = abrir;
})();
