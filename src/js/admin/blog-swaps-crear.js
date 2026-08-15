/* blog-swaps-crear
   Enlaza los tres pasos de la propuesta de intercambio.

   Paso 1: la clase propia se marca en la REJILLA semanal (.supl-week, en modo select
   con `single`), no en un <select>. El desplegable obligaba a reconstruir la semana
   mentalmente —"Lunes · 08:00–08:50 · Matemáticas (1A)"— cuando lo que el profesor
   tiene en la cabeza es su horario; y la rejilla ya existía, alimentada por el mismo
   Horario::rejilla() del módulo Horarios.

   Paso 2: el compañero, con el buscador compartido (admin-picker.js) apuntando a
   /dashboard/swaps/buscar. Escribe su hidden y emite 'change', que es lo que dispara
   la recarga del paso 3.

   Paso 3 (qué clase del otro profesor tomas) depende de los dos anteriores: hace falta
   la fecha para saber la ventana y el compañero para saber de quién son las clases. Por
   eso arranca bloqueado y se rellena por AJAX contra /dashboard/swaps/clases, que ya
   devuelve una entrada por (clase × día) dentro del margen — la ventana la decide el
   servidor, no este script. */
(function () {
    if (!document.body || document.body.dataset.page !== 'blog-swaps-crear') return;

    var form = document.querySelector('[data-swap-form]');
    if (!form) return;

    var weekBox   = form.querySelector('[data-swap-week]');
    var weekPie   = form.querySelector('[data-swap-week-pie]');
    var hidOrig   = form.querySelector('[data-swap-origen]');
    var caja      = form.querySelector('[data-swap-opciones]');
    var hidDest   = form.querySelector('[data-swap-destino]');
    var hidFecha  = form.querySelector('[data-swap-fecha]');
    // El datepicker propio escribe en un hidden y emite 'change'.
    var hidOrigen = form.querySelector('input[name="fecha_origen"]');

    // ⚠️ Cada picker se busca por SU marca, no por [data-picker-value] a secas: quien
    // coordina tiene dos en el formulario y el genérico agarraría el primero (el
    // profesor que cede) creyendo que es el compañero.
    var coordina  = form.dataset.coordina === '1';
    var boxQuien  = form.querySelector('[data-swap-destinatario]');
    var hidQuien  = boxQuien ? boxQuien.querySelector('[data-picker-value]') : null;
    var boxCede   = form.querySelector('[data-swap-solicitante]');
    var hidCede   = boxCede ? boxCede.querySelector('[data-picker-value]') : null;

    /** De quién se pinta la rejilla del paso 1: uno mismo, o el profesor designado. */
    function quienCede() {
        return coordina ? (hidCede && hidCede.value) : form.dataset.uid;
    }

    /* ── La rejilla de quien cede la clase ── */
    function pintarSemana() {
        var uid = quienCede();
        if (!weekBox || !window.SuplWeek || !hidOrigen || !hidOrigen.value) return;
        if (!uid) {
            weekBox.innerHTML = '<p class="supl-week-empty">Elige primero el profesor que cede la clase.</p>';
            return;
        }
        weekBox.innerHTML = '<p class="supl-week-empty">Cargando el horario…</p>';
        window.fetch('/dashboard/swaps/horario?profesor=' + encodeURIComponent(uid) +
                     '&fecha=' + encodeURIComponent(hidOrigen.value),
                     { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.error) { weekBox.innerHTML = '<p class="supl-week-empty">' + data.error + '</p>'; return; }
                window.SuplWeek.render(weekBox, data, {
                    mode: 'select',
                    single: true,          // un intercambio es de UNA clase
                    fechaLabel: window.SuplWeek.etiquetaFecha(hidOrigen.value),
                    onChange: function (sel) {
                        // La rejilla devuelve la celda entera; de ahí sale el id de la
                        // FILA de horarios, que es lo que referencia el intercambio (el
                        // periodo_id identificaría la hora, no la clase).
                        var c = sel.length ? sel[0].clase : null;
                        if (hidOrig) hidOrig.value = c && c.horario_id ? c.horario_id : '';
                        if (weekPie) {
                            weekPie.textContent = c
                                ? (coordina ? 'Se cede ' : 'Cederás ') + (c.materia || 'esa clase') +
                                  (c.grupo ? ' (' + c.grupo + ')' : '') + ' de ' + c.inicio + ' a ' + c.fin + '.'
                                : (coordina ? 'Marca la clase que se cede.' : 'Marca la clase que no vas a poder dar.');
                        }
                    }
                });
            })
            .catch(function () {
                weekBox.innerHTML = '<p class="supl-week-empty">No se pudo cargar tu horario. Inténtalo de nuevo.</p>';
            });
    }

    function limpiarOpciones(msg) {
        caja.innerHTML = '<p class="swp-opciones__vacio">' + msg + '</p>';
        if (hidDest)  hidDest.value = '';
        if (hidFecha) hidFecha.value = '';
    }

    function pintar(clases) {
        if (!clases.length) {
            limpiarOpciones('Ese profesor no tiene clases en los próximos días. Prueba con otro día o con otro compañero.');
            return;
        }
        caja.innerHTML = '';
        var porFecha = {};
        clases.forEach(function (c) { (porFecha[c.fecha] = porFecha[c.fecha] || []).push(c); });

        Object.keys(porFecha).sort().forEach(function (f) {
            var grupo = document.createElement('div');
            grupo.className = 'swp-dia';

            var tit = document.createElement('p');
            tit.className = 'swp-dia__titulo';
            tit.textContent = porFecha[f][0].fecha_txt;
            grupo.appendChild(tit);

            porFecha[f].forEach(function (c) {
                var lab = document.createElement('label');
                lab.className = 'swp-op';
                lab.style.setProperty('--c', (c.color && c.color.hex) || '#4267ac');

                var rad = document.createElement('input');
                rad.type = 'radio';
                rad.name = '_swap_op';
                rad.value = c.horario_id;
                rad.addEventListener('change', function () {
                    if (hidDest)  hidDest.value = c.horario_id;
                    if (hidFecha) hidFecha.value = c.fecha;
                });

                var caj = document.createElement('span');
                caj.className = 'swp-op__box';
                caj.innerHTML =
                    '<span class="swp-op__hora">' + c.inicio + '–' + c.fin + '</span>' +
                    '<span class="swp-op__mat"></span>' +
                    '<span class="swp-op__meta"></span>';
                caj.querySelector('.swp-op__mat').textContent = c.materia;
                caj.querySelector('.swp-op__meta').textContent =
                    [c.grupo, c.aula].filter(Boolean).join(' · ');

                lab.appendChild(rad);
                lab.appendChild(caj);
                grupo.appendChild(lab);
            });
            caja.appendChild(grupo);
        });
    }

    function cargar() {
        var prof  = hidQuien && hidQuien.value;
        var desde = hidOrigen && hidOrigen.value;
        if (!prof || !desde) {
            limpiarOpciones('Completa los pasos anteriores para ver sus clases disponibles.');
            return;
        }
        caja.innerHTML = '<p class="swp-opciones__vacio">Buscando sus clases…</p>';
        fetch('/dashboard/swaps/clases?profesor=' + encodeURIComponent(prof) +
              '&desde=' + encodeURIComponent(desde), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (d) { pintar(d.clases || []); })
            .catch(function () { limpiarOpciones('No se pudieron cargar sus clases. Inténtalo de nuevo.'); });
    }

    if (hidOrigen) hidOrigen.addEventListener('change', function () { pintarSemana(); cargar(); });
    // El picker escribe su hidden por propiedad y emite el 'change' a mano (ver
    // admin-picker.js): sin eso, elegir compañero no recargaría el paso siguiente.
    if (hidQuien)  hidQuien.addEventListener('change', cargar);
    // Cambiar de profesor que cede invalida la clase ya marcada: es de otra persona.
    if (hidCede)   hidCede.addEventListener('change', function () {
        if (hidOrig) hidOrig.value = '';
        pintarSemana();
    });

    // Sin clase elegida no hay intercambio, y el error del servidor llega tras perder
    // la pantalla entera: se corta antes.
    form.addEventListener('submit', function (e) {
        if (hidOrig && !hidOrig.value) {
            e.preventDefault();
            if (weekPie) weekPie.textContent = 'Marca en la rejilla la clase que se cede.';
            if (weekBox) weekBox.scrollIntoView({ block: 'center', behavior: 'smooth' });
        }
    });

    pintarSemana();
    cargar();
})();
