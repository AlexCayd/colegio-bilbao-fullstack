/* admin-table — ordenamiento por columna + paginación para las tablas de lectura del panel.
   Componente compartido: no lleva guarda de página, se activa por `[data-table]`.

   Sustituye al ordenamiento que estaba duplicado a mano en blog-articulos-index.js
   y blog-noticias-index.js, y lleva la paginación de admin-pager.js (que solo se
   usaba en listas de tarjetas) a las tablas.

   Markup esperado:
     <table class="admin-table" data-table data-table-per="10" data-table-noun="usuarios">
       <thead><tr>
         <th data-sort="text">Usuario</th>       ← ordenable
         <th data-sort="num">Artículos</th>
         <th data-sort="date">Último acceso</th>
         <th>Acciones</th>                        ← sin data-sort = no ordenable
       </tr></thead>
       <tbody>
         <tr data-pager-item class="is-hidden">   ← el servidor preoculta a partir de la fila `per`
           <td data-val="ana torres">Ana Torres</td>
           …
       </tbody>
     </table>

   `data-val` es opcional: sin él se ordena por el texto de la celda. Para fechas
   conviene ponerlo en Y-m-d, que ordena bien como cadena. Las celdas vacías ("—")
   caen siempre al final, se ordene ascendente o descendente.

   El paginador se genera solo si hay `data-table-per`; las vistas no lo escriben.

   Filtros de página (buscadores de suplencias, personal…): marcan las filas
   descartadas con la clase `is-filtered` y llaman a window.AdminTable.refrescar(tabla)
   para que la paginación cuente solo las visibles. */
(function () {
    var TABLAS = [];
    var VACIOS = ['', '—', '-', '–', 'n/a'];

    function texto(td) {
        if (!td) return '';
        var v = td.dataset && td.dataset.val !== undefined ? td.dataset.val : td.textContent;
        return String(v).trim();
    }

    function esVacio(v) { return VACIOS.indexOf(v.toLowerCase()) !== -1; }

    function aNumero(v) {
        var n = parseFloat(String(v).replace(/[^\d.,-]/g, '').replace(/\.(?=\d{3}\b)/g, '').replace(',', '.'));
        return isNaN(n) ? null : n;
    }

    /** Comparador por tipo. Devuelve <0, 0 o >0 ignorando la dirección. */
    function comparar(a, b, tipo) {
        var va = esVacio(a), vb = esVacio(b);
        if (va && vb) return 0;
        if (va) return 1;   // los vacíos siempre al final…
        if (vb) return -1;  // …en ambas direcciones (se compensa fuera)

        if (tipo === 'num') {
            var na = aNumero(a), nb = aNumero(b);
            if (na === null && nb === null) return 0;
            if (na === null) return 1;
            if (nb === null) return -1;
            return na - nb;
        }
        if (tipo === 'date') {
            // Y-m-d ordena bien como cadena; si no, se cae a Date.parse
            if (/^\d{4}-\d{2}-\d{2}/.test(a) && /^\d{4}-\d{2}-\d{2}/.test(b)) return a < b ? -1 : (a > b ? 1 : 0);
            var da = Date.parse(a), db = Date.parse(b);
            if (isNaN(da) && isNaN(db)) return 0;
            if (isNaN(da)) return 1;
            if (isNaN(db)) return -1;
            return da - db;
        }
        return a.localeCompare(b, 'es', { sensitivity: 'base', numeric: true });
    }

    function montar(tabla) {
        var thead = tabla.tHead;
        var tbody = tabla.tBodies[0];
        if (!thead || !tbody) return;

        var ths   = Array.prototype.slice.call(thead.querySelectorAll('th'));
        var per   = parseInt(tabla.dataset.tablePer || '0', 10);
        var noun  = tabla.dataset.tableNoun || 'registros';
        var pager = null;

        // ── Paginador (solo si la vista pide un tamaño de página) ──
        if (per > 0) {
            if (!tbody.id) tbody.id = 'tb-' + Math.random().toString(36).slice(2, 9);

            pager = document.createElement('div');
            pager.className = 'admin-pager';
            pager.setAttribute('data-pager', '');
            pager.dataset.pagerFor  = '#' + tbody.id;
            pager.dataset.pagerPer  = String(per);
            pager.dataset.pagerNoun = noun;
            pager.innerHTML =
                '<button type="button" class="admin-pager__btn" data-pager-prev aria-label="Anteriores">' +
                    '<i class="fa-solid fa-chevron-left"></i></button>' +
                '<span class="admin-pager__info" data-pager-info></span>' +
                '<button type="button" class="admin-pager__btn" data-pager-next aria-label="Siguientes">' +
                    '<i class="fa-solid fa-chevron-right"></i></button>';

            // Tras el contenedor de scroll si lo hay, para no meterlo dentro del overflow
            var ancla = tabla.closest('.admin-table-scroll') || tabla;
            ancla.parentNode.insertBefore(pager, ancla.nextSibling);

            if (window.AdminPager) window.AdminPager.init(pager.parentNode);
        }

        /** Reetiqueta como paginables solo las filas que el filtro dejó visibles. */
        function sincronizarFiltro() {
            Array.prototype.forEach.call(tbody.rows, function (tr) {
                if (tr.classList.contains('is-filtered')) tr.removeAttribute('data-pager-item');
                else tr.setAttribute('data-pager-item', '');
            });
        }

        function repaginar(aPrimera) {
            sincronizarFiltro();
            if (!pager || !window.AdminPager) return;
            if (aPrimera) window.AdminPager.reset(pager);
            else window.AdminPager.refrescar(pager);
        }

        function ordenar(th, idx) {
            var tipo = th.dataset.sort || 'text';
            var asc  = th.getAttribute('aria-sort') !== 'ascending';

            ths.forEach(function (o) {
                o.removeAttribute('aria-sort');
                o.classList.remove('is-asc', 'is-desc');
            });
            th.setAttribute('aria-sort', asc ? 'ascending' : 'descending');
            th.classList.add(asc ? 'is-asc' : 'is-desc');

            var filas = Array.prototype.slice.call(tbody.rows);
            filas.sort(function (fa, fb) {
                var a = texto(fa.cells[idx]), b = texto(fb.cells[idx]);
                // Los vacíos van al final siempre: no se invierten con la dirección
                if (esVacio(a) !== esVacio(b)) return esVacio(a) ? 1 : -1;
                var r = comparar(a, b, tipo);
                return asc ? r : -r;
            });
            filas.forEach(function (tr) { tbody.appendChild(tr); });

            repaginar(true);
        }

        ths.forEach(function (th, idx) {
            if (!th.dataset.sort) return;
            th.classList.add('is-sortable');
            th.tabIndex = 0;
            // Sin espacio literal antes del span: era un punto de salto de línea
            // válido y partía el encabezado en dos renglones. La separación la
            // pone el margin-left del propio .admin-table__sortico.
            th.insertAdjacentHTML('beforeend', '<span class="admin-table__sortico" aria-hidden="true"></span>');
            th.addEventListener('click', function () { ordenar(th, idx); });
            th.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); ordenar(th, idx); }
            });
        });

        tabla._tablaRepaginar = repaginar;
        TABLAS.push(tabla);
    }

    function init(raiz) {
        (raiz || document).querySelectorAll('[data-table]').forEach(function (t) {
            if (TABLAS.indexOf(t) === -1) montar(t);
        });
    }

    window.AdminTable = {
        init: init,
        /** Llamar tras filtrar en cliente: recuenta las filas visibles y vuelve a la página 1. */
        refrescar: function (tabla) { if (tabla && tabla._tablaRepaginar) tabla._tablaRepaginar(true); }
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { init(); });
    } else {
        init();
    }
})();
