/* blog-swaps-crear
   Asistente por pasos del alta de swap.

   Hace dos cosas: cargar los datos de cada paso (rejilla del que cede, clases del
   compañero) y llevar el RECORRIDO — qué paso se ve, qué falta para pasar al siguiente,
   cómo va el progreso y qué dice Alex.

   Paso «la clase»: se marca en la REJILLA semanal (.supl-week, en modo select con
   `single`), no en un <select>. El desplegable obligaba a reconstruir la semana
   mentalmente —"Lunes · 08:00–08:50 · Matemáticas (1A)"— cuando lo que el profesor
   tiene en la cabeza es su horario; y la rejilla ya existía, alimentada por el mismo
   Horario::rejilla() del módulo Horarios. Con un paso por pantalla dispone del ancho
   entero, que es la razón de fondo de haber partido el formulario.

   Paso «el compañero»: buscador compartido (admin-picker.js) contra
   /dashboard/swaps/buscar. Escribe su hidden y emite 'change' A MANO, que es lo que
   dispara la recarga del paso siguiente.

   Paso «la clase a cambio»: depende de los dos anteriores (la fecha da la ventana, el
   compañero de quién son las clases). Se rellena por AJAX contra /dashboard/swaps/clases,
   que ya devuelve una entrada por (clase × día) dentro del margen — la ventana la decide
   el servidor, no este script.

   ⚠️ El POST no cambia: los cinco hidden siguen en el DOM aunque su panel esté oculto
   (`hidden` no desactiva un input) y solo se envía desde el último paso. */
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

    /** De quién se pinta la rejilla: uno mismo, o el profesor designado. */
    function quienCede() {
        return coordina ? (hidCede && hidCede.value) : form.dataset.uid;
    }

    /* ── Estado del recorrido ─────────────────────────────────────────────────
       Las dos clases elegidas se guardan aquí y las alimentan los callbacks que ya
       existían (el onChange de SuplWeek y el change de cada radio): no hay una segunda
       fuente de datos que pueda desincronizarse con los hidden. */
    var claseCede = null, claseRecibe = null;
    var actual = 0;

    var paneles  = Array.prototype.slice.call(form.querySelectorAll('.swp-panel'));
    var indices  = Array.prototype.slice.call(form.querySelectorAll('[data-swap-indice] .swp-wiz__paso'));
    var alexes   = Array.prototype.slice.call(form.querySelectorAll('[data-alex-paso]'));
    var elDice   = form.querySelector('[data-swap-dice]');
    var elActual = form.querySelector('[data-swap-actual]');
    var elRest   = form.querySelector('[data-swap-restante]');
    var elFill   = form.querySelector('[data-swap-fill]');
    var elHint   = form.querySelector('[data-swap-hint]');
    var btnAtras = form.querySelector('[data-swap-atras]');
    var btnSig   = form.querySelector('[data-swap-siguiente]');
    var btnEnv   = form.querySelector('[data-swap-enviar]');
    var resumen  = form.querySelector('[data-swap-resumen]');
    var resFalta = form.querySelector('[data-swap-resumen-falta]');
    var TOTAL    = paneles.length;

    // Las frases de Alex viajan en el HTML (las emite PHP junto a los pasos), no
    // duplicadas aquí: el catálogo de pasos es uno solo y vive en la vista.
    var DICE = paneles.map(function (p, i) {
        var img = alexes[i];
        return img ? img.getAttribute('data-dice') || '' : '';
    });

    /** Qué falta para poder salir del paso `i`. '' = está resuelto. */
    function faltaEn(i) {
        var clave = paneles[i].dataset.step;
        if (clave === 'cede')  return (hidCede && hidCede.value) ? '' : 'Elige al profesor que cede la clase.';
        if (clave === 'clase') return (hidOrig && hidOrig.value) ? '' : 'Marca en la rejilla la clase que se cede.';
        if (clave === 'quien') return (hidQuien && hidQuien.value) ? '' : 'Elige con qué compañero se cambia.';
        if (clave === 'cual')  return (hidDest && hidDest.value) ? '' : 'Elige la clase que se toma a cambio.';
        return '';
    }

    /** Pinta un lado del resumen. `c` null = todavía sin elegir. */
    function pintarLado(sel, c, extraMeta) {
        var lado = form.querySelector(sel);
        if (!lado) return;
        lado.querySelector('.swp-resumen__clase').textContent = c ? (c.materia || 'Esa clase') : '—';
        lado.querySelector('.swp-resumen__meta').textContent  = c
            ? [c.grupo, (c.inicio && c.fin) ? c.inicio + '–' + c.fin : '', extraMeta || '']
                .filter(Boolean).join(' · ')
            : '';
        lado.classList.toggle('is-set', !!c);
    }

    /**
     * Único punto que decide qué se ve: panel activo, índice lateral, barra, Alex,
     * botones y resumen. Se llama tras cualquier cambio de dato o de paso.
     */
    function sincronizar() {
        paneles.forEach(function (p, i) { p.hidden = i !== actual; });

        indices.forEach(function (li, i) {
            var hecho = faltaEn(i) === '';
            li.classList.toggle('is-actual', i === actual);
            // "Hecho" solo hacia atrás: marcar en verde un paso que aún no se ha
            // visitado (el de confirmar, que nunca pide nada) sería mentir sobre el
            // avance real.
            li.classList.toggle('is-hecho', hecho && i < actual);
            var val = li.querySelector('[data-paso-val]');
            if (val) val.textContent = resumenPaso(i);
        });

        alexes.forEach(function (img, i) { img.hidden = i !== actual; });
        if (elDice && DICE[actual]) elDice.textContent = DICE[actual];

        if (elActual) elActual.textContent = actual + 1;
        if (elFill)   elFill.style.width = Math.round(((actual + 1) / TOTAL) * 100) + '%';
        if (elRest) {
            var quedan = TOTAL - actual - 1;
            elRest.textContent = quedan === 0 ? 'Último paso' : (quedan === 1 ? 'Queda 1 paso' : 'Quedan ' + quedan + ' pasos');
        }

        var ultimo = actual === TOTAL - 1;
        if (btnAtras) btnAtras.hidden = actual === 0;
        if (btnSig)   btnSig.hidden = ultimo;
        if (btnEnv)   btnEnv.hidden = !ultimo;

        // El aviso se muestra ANTES de pulsar, no como castigo después.
        var falta = faltaEn(actual);
        if (elHint) {
            elHint.textContent = falta;
            elHint.hidden = falta === '';
        }

        pintarLado('[data-swap-resumen-cede]',   claseCede);
        pintarLado('[data-swap-resumen-recibe]', claseRecibe, claseRecibe && claseRecibe.fecha_txt);
        var completo = !!(claseCede && claseRecibe);
        if (resumen)  resumen.classList.toggle('swp-resumen--pendiente', !completo);
        if (resFalta) resFalta.hidden = completo;
    }

    /** Lo elegido en cada paso, para el índice lateral: el recorrido se lee de un vistazo. */
    function resumenPaso(i) {
        var clave = paneles[i].dataset.step;
        if (clave === 'cede')  return nombreDe(boxCede);
        if (clave === 'quien') return nombreDe(boxQuien);
        if (clave === 'clase') return claseCede ? (claseCede.materia || 'Clase elegida') : '';
        if (clave === 'cual')  return claseRecibe ? (claseRecibe.materia || 'Clase elegida') : '';
        return '';
    }
    function nombreDe(box) {
        if (!box) return '';
        var hid = box.querySelector('[data-picker-value]');
        var inp = box.querySelector('[data-picker-input]');
        return (hid && hid.value && inp) ? inp.value : '';
    }

    /** Avanza o retrocede. Hacia adelante exige el paso resuelto. */
    function ir(delta) {
        if (delta > 0) {
            var falta = faltaEn(actual);
            if (falta) {
                // Nunca se bloquea en silencio: se dice qué falta y se lleva la vista al
                // campo que lo resuelve.
                if (elHint) { elHint.hidden = false; elHint.textContent = falta; }
                if (elHint) {
                    elHint.classList.remove('is-avisando');
                    void elHint.offsetWidth;          // reinicia la animación
                    elHint.classList.add('is-avisando');
                }
                paneles[actual].scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                return;
            }
        }
        actual = Math.min(TOTAL - 1, Math.max(0, actual + delta));
        sincronizar();
        form.scrollIntoView({ block: 'start', behavior: 'smooth' });
    }

    /* ── La rejilla de quien cede la clase ── */
    function pintarSemana() {
        var uid = quienCede();
        if (!weekBox || !window.SuplWeek || !hidOrigen || !hidOrigen.value) return;
        // Sin profesor no hay nada que pintar; el paso anterior es el que lo pide.
        if (!uid) { weekBox.innerHTML = ''; return; }
        weekBox.innerHTML = '<p class="supl-week-empty">Cargando el horario…</p>';
        window.fetch('/dashboard/swaps/horario?profesor=' + encodeURIComponent(uid) +
                     '&fecha=' + encodeURIComponent(hidOrigen.value),
                     { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.error) { weekBox.innerHTML = '<p class="supl-week-empty">' + data.error + '</p>'; return; }
                window.SuplWeek.render(weekBox, data, {
                    mode: 'select',
                    single: true,          // un swap es de UNA clase
                    fechaLabel: window.SuplWeek.etiquetaFecha(hidOrigen.value),
                    onChange: function (sel) {
                        // La rejilla devuelve la celda entera; de ahí sale el id de la
                        // FILA de horarios, que es lo que referencia el swap (el
                        // periodo_id identificaría la hora, no la clase).
                        var c = sel.length ? sel[0].clase : null;
                        claseCede = c;
                        if (hidOrig) hidOrig.value = c && c.horario_id ? c.horario_id : '';
                        if (weekPie) {
                            weekPie.textContent = c
                                ? (coordina ? 'Se cede ' : 'Cederás ') + (c.materia || 'esa clase') +
                                  (c.grupo ? ' (' + c.grupo + ')' : '') + ' de ' + c.inicio + ' a ' + c.fin + '.'
                                : (coordina ? 'Marca la clase que se cede.' : 'Marca la clase que no vas a poder dar.');
                        }
                        sincronizar();
                    }
                });
                sincronizar();
            })
            .catch(function () {
                weekBox.innerHTML = '<p class="supl-week-empty">No se pudo cargar el horario. Inténtalo de nuevo.</p>';
            });
    }

    function limpiarOpciones(msg) {
        caja.innerHTML = msg ? '<p class="swp-opciones__vacio">' + msg + '</p>' : '';
        if (hidDest)  hidDest.value = '';
        if (hidFecha) hidFecha.value = '';
        claseRecibe = null;
        sincronizar();
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
                    claseRecibe = c;
                    sincronizar();
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
        // Sin mensaje: los pasos anteriores son los que lo piden, y repetirlo aquí lo
        // diría dos veces y peor.
        if (!prof || !desde) { limpiarOpciones(''); return; }
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
    if (hidQuien)  hidQuien.addEventListener('change', function () { sincronizar(); cargar(); });
    // Cambiar de profesor que cede invalida la clase ya marcada: es de otra persona.
    if (hidCede)   hidCede.addEventListener('change', function () {
        if (hidOrig) hidOrig.value = '';
        claseCede = null;
        sincronizar();
        pintarSemana();
    });

    if (btnSig)   btnSig.addEventListener('click', function () { ir(1); });
    if (btnAtras) btnAtras.addEventListener('click', function () { ir(-1); });

    // Volver a un paso ya recorrido desde el índice lateral. Solo hacia atrás: saltar
    // adelante se saltaría los requisitos que `ir()` comprueba.
    indices.forEach(function (li, i) {
        li.addEventListener('click', function () {
            if (i < actual) { actual = i; sincronizar(); }
        });
    });

    /* Guarda dura del envío. Se llega aquí con todo resuelto salvo que alguien fuerce el
       submit (Enter en un campo, por ejemplo), así que se comprueba paso a paso y se
       lleva al primero que falte en vez de mandar un POST que el servidor rebotaría
       perdiendo la pantalla entera. */
    form.addEventListener('submit', function (e) {
        for (var i = 0; i < TOTAL; i++) {
            if (faltaEn(i)) {
                e.preventDefault();
                actual = i;
                sincronizar();
                if (elHint) { elHint.classList.remove('is-avisando'); void elHint.offsetWidth; elHint.classList.add('is-avisando'); }
                form.scrollIntoView({ block: 'start', behavior: 'smooth' });
                return;
            }
        }
    });

    sincronizar();
    pintarSemana();
    cargar();
})();
