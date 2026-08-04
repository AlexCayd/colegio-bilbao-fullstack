/* blog-suplencias-index
   Migrado desde el <script> embebido de views/blog/suplencias/index.php */
(function () {
    if (!document.body || document.body.dataset.page !== 'blog-suplencias-index') return;
    (function () {
        // Búsqueda en vivo sobre las filas cargadas. Marca con `is-filtered` en vez
        // de tocar el style, para que admin-table.js pueda excluirlas del paginado.
        const input = document.getElementById('suplSearch');
        const tabla = document.getElementById('suplTable');
        const rows  = Array.from(document.querySelectorAll('.supl-row'));
        const none  = document.getElementById('suplNoResults');

        // Dos filtros conviven sobre las mismas filas —el buscador y el día elegido
        // en el calendario— y ambos usan `is-filtered`. Se aplican juntos desde un
        // único sitio; si cada uno tocara la clase por su cuenta, el segundo
        // desharía al primero.
        let filtroTexto = '';
        let filtroDia   = '';

        function aplicarFiltros() {
            let visible = 0;
            rows.forEach(r => {
                const okTexto = !filtroTexto || (r.dataset.search || '').includes(filtroTexto);
                const okDia   = !filtroDia   || r.dataset.fecha === filtroDia;
                const match   = okTexto && okDia;
                r.classList.toggle('is-filtered', !match);
                if (match) visible++;
            });
            if (none) none.style.display = (rows.length && visible === 0) ? 'block' : 'none';
            if (window.AdminTable) window.AdminTable.refrescar(tabla);
            return visible;
        }

        if (input) {
            input.addEventListener('input', function () {
                filtroTexto = this.value.trim().toLowerCase();
                aplicarFiltros();
            });
        }
        // Los filtros de fecha son .bilbao-date: el hidden emite `change` al elegir
        // día o al limpiar, y ahí se reenvía el formulario (antes era un onchange
        // inline en el <input type="date">).
        var formFiltros = document.getElementById('suplFilters');
        if (formFiltros) {
            formFiltros.querySelectorAll('[data-date-value]').forEach(function (h) {
                h.addEventListener('change', function () { formFiltros.submit(); });
            });
        }

        /* ── Calendario ──
           Sustituye a los botones que había en el topbar. Diferencia clave con los
           otros .bilbao-cal del proyecto: aquí NINGÚN día se deshabilita, porque el
           calendario no solo consulta —también es el punto de entrada para abrir una
           ausencia futura, y esos días todavía no tienen nada. */
        (function () {
            const cal = document.getElementById('suplCal');
            if (!cal) return;

            const MESES = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
            const DIAS  = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];

            let dias = [];
            try { dias = JSON.parse(cal.dataset.dias || '[]'); } catch (e) { dias = []; }
            const porFecha = {};
            dias.forEach(d => { porFecha[d.fecha] = d.n; });

            const grid   = cal.querySelector('[data-cal-grid]');
            const label  = cal.querySelector('[data-cal-label]');
            const vacio  = document.querySelector('[data-cal-empty]');
            const panel  = document.querySelector('[data-cal-panel]');
            const elFecha  = document.querySelector('[data-cal-fecha]');
            const elConteo = document.querySelector('[data-cal-conteo]');
            const btnReset = document.querySelector('[data-cal-reset]');
            const aCrear     = document.querySelector('[data-cal-crear]');
            const aSolicitar = document.querySelector('[data-cal-solicitar]');

            const hoyStr = cal.dataset.hoy || '';
            const hoy    = new Date();
            let vista    = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
            let elegido  = '';

            const pad = n => String(n).padStart(2, '0');

            function pintar() {
                const y = vista.getFullYear(), m = vista.getMonth();
                label.textContent = MESES[m] + ' ' + y;

                const primero = new Date(y, m, 1).getDay();   // domingo = 0
                const total   = new Date(y, m + 1, 0).getDate();
                grid.innerHTML = '';

                for (let i = 0; i < primero; i++) {
                    const b = document.createElement('span');
                    b.className = 'bilbao-cal__cell bilbao-cal__cell--empty';
                    grid.appendChild(b);
                }
                for (let d = 1; d <= total; d++) {
                    const ymd  = y + '-' + pad(m + 1) + '-' + pad(d);
                    const n    = porFecha[ymd] || 0;
                    const cell = document.createElement('button');
                    cell.type = 'button';
                    cell.className = 'bilbao-cal__cell';
                    cell.textContent = d;
                    cell.dataset.ymd = ymd;

                    if (ymd === hoyStr)  cell.classList.add('is-today');
                    if (ymd === elegido) cell.classList.add('is-active');
                    if (n) {
                        cell.classList.add('has-event');
                        cell.title = n + (n === 1 ? ' suplencia' : ' suplencias');
                        const badge = document.createElement('span');
                        badge.className = 'bilbao-cal__badge';
                        badge.textContent = n;
                        cell.appendChild(badge);
                    }
                    grid.appendChild(cell);
                }
                if (window.BilbaoCalAnim) window.BilbaoCalAnim.entrada(grid);
            }

            function elegir(ymd) {
                elegido = ymd;
                grid.querySelectorAll('.bilbao-cal__cell.is-active').forEach(c => c.classList.remove('is-active'));
                const cell = grid.querySelector('[data-ymd="' + ymd + '"]');
                if (cell) {
                    cell.classList.add('is-active');
                    if (window.BilbaoCalAnim) window.BilbaoCalAnim.pop(cell);
                }

                filtroDia = ymd;
                const visibles = aplicarFiltros();

                const p = ymd.split('-').map(Number);
                const f = new Date(p[0], p[1] - 1, p[2]);
                if (elFecha)  elFecha.textContent = DIAS[f.getDay()] + ', ' + p[2] + ' de ' + MESES[p[1] - 1].toLowerCase();
                if (elConteo) elConteo.textContent = visibles === 0
                    ? 'Sin suplencias este día.'
                    : visibles + (visibles === 1 ? ' suplencia' : ' suplencias') + ' en la lista.';

                // Las acciones llegan con la fecha puesta: el destino ya no obliga a
                // volver a elegirla en el datepicker.
                if (aCrear)     aCrear.href     = '/dashboard/suplencias/crear?fecha=' + ymd;
                if (aSolicitar) aSolicitar.href = '/dashboard/suplencias/solicitar?fecha=' + ymd;

                if (vacio) vacio.hidden = true;
                if (panel) panel.hidden = false;
                if (btnReset) btnReset.hidden = false;
            }

            function limpiar() {
                elegido = '';
                filtroDia = '';
                aplicarFiltros();
                grid.querySelectorAll('.bilbao-cal__cell.is-active').forEach(c => c.classList.remove('is-active'));
                if (vacio) vacio.hidden = false;
                if (panel) panel.hidden = true;
            }

            grid.addEventListener('click', function (e) {
                const cell = e.target.closest('[data-ymd]');
                if (cell) elegir(cell.dataset.ymd);
            });
            if (btnReset) btnReset.addEventListener('click', limpiar);
            cal.querySelector('[data-cal-prev]').addEventListener('click', () => { vista.setMonth(vista.getMonth() - 1); pintar(); });
            cal.querySelector('[data-cal-next]').addEventListener('click', () => { vista.setMonth(vista.getMonth() + 1); pintar(); });

            pintar();
        })();

        window.suplEliminar = function (id, nombre) {
            document.getElementById('suplDeleteId').value = id;
            document.getElementById('suplDeleteName').textContent = nombre;
            document.getElementById('suplDeleteModal').style.display = 'flex';
        };

        // ── Select personalizado (estado) ──
        document.querySelectorAll('[data-select]').forEach(function (sel) {
            const btn   = sel.querySelector('[data-select-btn]');
            const menu  = sel.querySelector('[data-select-menu]');
            const value = sel.querySelector('[data-select-value]');
            const label = sel.querySelector('[data-select-label]');
            const form  = document.getElementById('suplFilters');
            btn.addEventListener('click', function (e) { e.stopPropagation(); sel.classList.toggle('is-open'); });
            menu.querySelectorAll('[data-value]').forEach(function (opt) {
                opt.addEventListener('click', function () {
                    value.value = opt.dataset.value;
                    label.textContent = opt.textContent;
                    sel.classList.remove('is-open');
                    if (form) form.submit();
                });
            });
            document.addEventListener('click', function (e) { if (!sel.contains(e.target)) sel.classList.remove('is-open'); });
        });
    })();

    // El toast de Alex lo lleva admin-toast.js (#alexToast)
})();
