/* admin-nivel-tabs — filtro por nivel académico en forma de tabs.
   Componente compartido: no lleva guarda de página, se activa por `[data-nivel-tabs]`.

   Filtra en cliente una tabla ya renderizada: marca las filas descartadas con
   `is-filtered` y llama a window.AdminTable.refrescar() para que la paginación
   cuente solo las visibles. Es el mismo patrón que usan los buscadores del panel,
   así que no hace falta recargar ni tocar el servidor.

   Markup:
     <div class="cat-tabs" data-nivel-tabs>
        <button class="cat-tab is-active" data-nivel="">Todos</button>
        <button class="cat-tab" data-nivel="Primaria">Primaria</button>
     </div>
     <table class="admin-table" data-table>
        <tr data-pager-item data-nivel="Primaria">…</tr> */
(function () {
    var barra = document.querySelector('[data-nivel-tabs]');
    if (!barra) return;

    var tabla = document.querySelector('.admin-table[data-table]');
    if (!tabla) return;

    var filas = Array.prototype.slice.call(tabla.querySelectorAll('tbody tr[data-nivel]'));

    function aplicar(nivel) {
        filas.forEach(function (tr) {
            var visible = !nivel || tr.dataset.nivel === nivel;
            tr.classList.toggle('is-filtered', !visible);
        });
        if (window.AdminTable && window.AdminTable.refrescar) window.AdminTable.refrescar(tabla);
    }

    barra.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-nivel]');
        if (!btn || btn.disabled) return;
        barra.querySelectorAll('[data-nivel]').forEach(function (b) {
            b.classList.toggle('is-active', b === btn);
        });
        aplicar(btn.dataset.nivel);
    });
})();
