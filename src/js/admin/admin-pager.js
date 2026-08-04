/* admin-pager — paginación en cliente de una lista ya renderizada.
   Componente compartido: no lleva guarda de página, se activa por `[data-pager]`.

   Markup esperado:
     <div class="…" data-pager-list>          ← contenedor (o cualquier ancestro común)
        <div data-pager-item>…</div> × N
     </div>
     <div class="mh-pager" data-pager data-pager-for="…" data-pager-per="5" data-pager-noun="colaboradores">
        <span data-pager-info></span>
        <button data-pager-prev></button><button data-pager-next></button>
     </div>

   Las páginas se ocultan con la clase `is-hidden`. Expone window.AdminPager.refrescar(pager)
   para relistar los ítems cuando la lista se repinta desde otro módulo. */
(function () {
    var PAGERS = [];

    function montar(pager) {
        var sel   = pager.dataset.pagerFor;
        var lista = sel ? document.querySelector(sel) : pager.previousElementSibling;
        if (!lista) return;

        var noun  = pager.dataset.pagerNoun || 'elementos';
        var info  = pager.querySelector('[data-pager-info]');
        var prev  = pager.querySelector('[data-pager-prev]');
        var next  = pager.querySelector('[data-pager-next]');
        var page  = 0;
        var items = [];

        function pintar() {
            // `per` se relee en cada pintado, no se captura al montar: hay listas que
            // lo ajustan al alto real de su contenedor (los cumpleaños del home) y
            // vuelven a llamar a reset() tras cambiarlo.
            var per   = parseInt(pager.dataset.pagerPer || '5', 10) || 5;
            var pages = Math.max(1, Math.ceil(items.length / per));
            if (page > pages - 1) page = pages - 1;
            if (page < 0) page = 0;

            items.forEach(function (el, i) {
                el.classList.toggle('is-hidden', i < page * per || i >= (page + 1) * per);
            });
            if (info) info.textContent = (page + 1) + ' / ' + pages + ' · ' + items.length + ' ' + noun;
            if (prev) prev.disabled = page === 0;
            if (next) next.disabled = page >= pages - 1;
            // Con una sola página el paginador estorba
            pager.hidden = items.length <= per;
        }

        function releer() {
            items = Array.prototype.slice.call(lista.querySelectorAll('[data-pager-item]'));
            pintar();
        }

        if (prev) prev.addEventListener('click', function () { if (page > 0) { page--; pintar(); } });
        if (next) next.addEventListener('click', function () { page++; pintar(); });

        pager._pagerReleer = releer;
        pager._pagerReset  = function () { page = 0; releer(); };
        releer();
        PAGERS.push(pager);
    }

    function init(raiz) {
        (raiz || document).querySelectorAll('[data-pager]').forEach(function (p) {
            if (PAGERS.indexOf(p) === -1) montar(p);
        });
    }

    window.AdminPager = {
        init: init,
        /** Vuelve a leer los ítems (tras repintar la lista) y salta a la primera página. */
        reset: function (pager) { if (pager && pager._pagerReset) pager._pagerReset(); },
        refrescar: function (pager) { if (pager && pager._pagerReleer) pager._pagerReleer(); }
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { init(); });
    } else {
        init();
    }
})();
