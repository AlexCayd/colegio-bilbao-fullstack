/* blog-personal-index — filtro en cliente de los directorios de personal
   (Profesores / Prefectura / Administrativos / Directivos comparten esta vista). */
(function () {
    if (!document.body || document.body.dataset.page !== 'blog-personal-index') return;

    var buscar = document.querySelector('[data-per-buscar]');
    var tabla  = document.getElementById('perTable');
    var filas  = Array.prototype.slice.call(document.querySelectorAll('[data-per-row]'));
    var cuenta = document.querySelector('[data-per-count]');
    var vacio  = document.querySelector('[data-per-empty]');
    if (!filas.length) return;

    // Mismo criterio que claveCatalogo() en el controller: minúsculas y sin acentos
    var ACENTOS = { 'á': 'a', 'é': 'e', 'í': 'i', 'ó': 'o', 'ú': 'u', 'ü': 'u', 'ñ': 'n' };
    function normalizar(s) {
        return String(s || '').toLowerCase().replace(/[áéíóúüñ]/g, function (c) { return ACENTOS[c]; });
    }

    // Se marca con `is-filtered` en vez de `hidden` para que admin-table.js pueda
    // dejar las descartadas fuera del paginado.
    function filtrar() {
        var q = normalizar(buscar ? buscar.value.trim() : '');
        var n = 0;

        filas.forEach(function (tr) {
            var coincide = !q || normalizar(tr.dataset.nombre).indexOf(q) !== -1;
            tr.classList.toggle('is-filtered', !coincide);
            if (coincide) n++;
        });

        if (cuenta) cuenta.textContent = n;
        if (vacio)  vacio.hidden = n > 0;
        if (window.AdminTable) window.AdminTable.refrescar(tabla);
    }

    if (buscar) buscar.addEventListener('input', filtrar);
})();
