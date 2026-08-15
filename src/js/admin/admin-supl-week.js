/* admin-supl-week — rejilla semanal de horario reutilizable
   La usan solicitar/crear (elegir las horas a cubrir) y agendar (preview del suplente).
   Módulo compartido: no lleva guarda de página, expone window.SuplWeek. */
(function () {
    var DIAS    = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes'];
    var DIAS_LG = { lunes: 'Lun', martes: 'Mar', miercoles: 'Mié', jueves: 'Jue', viernes: 'Vie' };
    var DIAS_LARGO = { lunes: 'Lunes', martes: 'Martes', miercoles: 'Miércoles', jueves: 'Jueves', viernes: 'Viernes' };
    var NIVEL_CORTO = { Maternal: 'Mat', Kinder: 'Kín', Primaria: 'Prim', Secundaria: 'Sec', Bachillerato: 'Bach' };

    /* El color de cada materia lo calcula PHP y viaja en el JSON (celda.color). Antes se
       replicaba aquí el crc32() y la paleta de _grid.php: dos fuentes de verdad que se
       desincronizaban en cuanto alguien tocaba una. */
    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }
    function subtitulo(clase) {
        return [clase.grupo, clase.aula].filter(Boolean).join(' · ');
    }

    /**
     * Pinta la rejilla dentro de `cont`.
     *
     * Las filas son TRAMOS de reloj, no periodos: la jornada es distinta en cada nivel y
     * un profesor puede dar clase en varios, así que sus clases no caben en una sola
     * jornada. El eje y la colocación (con su `span` → rowspan) los calcula el servidor
     * en Horario::rejilla(), la misma función que pinta el módulo Horarios.
     *
     * @param {HTMLElement} cont  contenedor (se vacía)
     * @param {Object} data       respuesta de /dashboard/suplencias/horario
     * @param {Object} opts
     *   mode      'select' | 'preview'   (por defecto 'preview')
     *   dia       día activo; si se omite se usa data.dia
     *   targetIni 'HH:MM' de inicio de la hora a cubrir (modo preview)
     *   targetFin 'HH:MM' de fin. Se marca toda celda que SOLAPE ese rango — comparar
     *             periodo_id era justo el error de fondo: dos periodos distintos de
     *             niveles distintos pueden ser la misma hora del reloj.
     *   selected  array de periodo_id preseleccionados (modo select)
     *   single    en modo select, comportamiento de radio: elegir una apaga la anterior.
     *             Lo usa crear un intercambio, que cambia UNA clase, no varias horas.
     *   onChange  callback(arrayDeSeleccionados) en modo select
     */
    /** Elemento donde pintar la leyenda, si el llamador pidió sacarla de la rejilla. */
    function resolverLegend(opts) {
        if (!opts.legend) return null;
        return typeof opts.legend === 'string' ? document.querySelector(opts.legend) : opts.legend;
    }

    /** ¿La leyenda va dentro de la tarjeta de Alex? Entonces se pinta como texto. */
    function destinoEsTarjeta(opts) {
        var el = resolverLegend(opts);
        return !!(el && el.closest('.admin-helper-card'));
    }

    /** Solapamiento estricto de 'HH:MM' (comparan lexicográfico = cronológico). */
    function solapan(aIni, aFin, bIni, bFin) {
        return aIni < bFin && bIni < aFin;
    }

    function render(cont, data, opts) {
        opts = opts || {};
        var mode    = opts.mode || 'preview';
        var dia     = opts.dia || data.dia;
        var tramos  = data.tramos || [];
        var rejilla = data.rejilla || {};

        if (!dia) {
            cont.innerHTML = '<p class="supl-week-empty">La fecha elegida cae en fin de semana: no hay clases que cubrir.</p>';
            if (opts.onChange) opts.onChange([]);
            return;
        }
        if (!tramos.length) {
            cont.innerHTML = '<p class="supl-week-empty">No hay jornada configurada.</p>';
            return;
        }

        var sel = {};
        (opts.selected || []).forEach(function (p) { sel[p] = true; });

        // Celdas por el tramo en que arrancan: los tramos que faltan los cubre el
        // rowspan de la celda de arriba y ahí no se emite <td>.
        var porTramo = {};
        DIAS.forEach(function (d) {
            porTramo[d] = {};
            (rejilla[d] || []).forEach(function (c) { porTramo[d][c.tramo] = c; });
        });

        var tIni = opts.targetIni || null;
        var tFin = opts.targetFin || null;

        // Con dos jornadas mezcladas hay rótulos que se repiten ("Receso" de Secundaria
        // y el de Primaria, en filas seguidas): el nivel es lo que los distingue.
        var mixto = (data.niveles || []).length > 1;

        var html = '<div class="supl-week-wrap"><table class="supl-week"><thead><tr><th class="supl-week__corner"></th>';
        DIAS.forEach(function (d) {
            html += '<th class="supl-week__dayhead' + (d === dia ? ' is-active-day' : '') + '">' + DIAS_LG[d] + '</th>';
        });
        html += '</tr></thead><tbody>';

        tramos.forEach(function (t, i) {
            /* Altura proporcional a `alto` (los minutos con tope), no a los minutos
               crudos: en el eje comprimido un tramo puede durar dos horas. Un tramo que
               ningún día usa baja a una franja. El rowspan lo sigue cuadrando el
               navegador. */
            var alto = t.alto || t.minutos || 50;

            /* Tres formas de rotular la fila. Un fragmento (los 20' que quedan al
               cruzarse dos jornadas) NO tiene nombre propio: solo su hora de inicio.
               Pintar `etiqueta || inicio` arriba y `inicio` abajo daba el rótulo
               duplicado "08:20 / 08:20" de la rejilla mixta. */
            var rotulo = t.etiqueta || '';
            var nivTr  = mixto && t.nivel ? (NIVEL_CORTO[t.nivel] || t.nivel) : '';
            var clase  = t.hueco ? ' class="supl-week__gap"' : (rotulo ? '' : ' class="supl-week__frag"');

            html += '<tr style="--min:' + alto + '"' + clase + '><th class="supl-week__hour">'
                  + (rotulo
                        ? '<span>' + esc(rotulo) + '</span><small>' + esc(t.inicio) + (nivTr ? ' · ' + esc(nivTr) : '') + '</small>'
                        : '<small>' + esc(t.inicio) + '</small>')
                  + '</th>';

            DIAS.forEach(function (d) {
                var c = porTramo[d][i];
                if (!c) return;                       // lo cubre un rowspan de más arriba

                var activo = (d === dia);
                var dim    = activo ? '' : ' is-dim';
                var span   = c.span > 1 ? ' rowspan="' + c.span + '"' : '';
                // En preview se marca por SOLAPAMIENTO con la hora a cubrir, no por id
                var esTarget = (mode === 'preview' && activo && tIni && solapan(tIni, tFin, c.inicio, c.fin));

                if (c.tipo === 'receso') {
                    // El receso ya no bloquea: puede ser el de su nivel mientras en otro
                    // nivel hay clase. Se marca en ámbar y prefectura decide.
                    if (esTarget) {
                        html += '<td class="supl-week__target is-break"' + span + '>'
                              + '<span class="supl-week__target-flag">Es su receso</span>'
                              + '<div class="supl-week__target-in"><i class="fa-solid fa-mug-hot"></i></div></td>';
                        return;
                    }
                    html += '<td class="supl-week__receso' + dim + '"' + span
                          + ' title="Receso de ' + esc(c.nivel) + '"><i class="fa-solid fa-mug-hot"></i></td>';
                    return;
                }

                if (c.tipo === 'clase') {
                    var col      = (c.color && c.color.hex) || '#94a3b8';
                    var oscuro   = !!(c.color && c.color.oscuro);
                    var tinta    = oscuro ? '#1f2937' : '#fff';
                    var tintaSec = oscuro ? 'rgba(31,41,55,.72)' : 'rgba(255,255,255,.8)';
                    var sec      = subtitulo(c);
                    var pick     = (mode === 'select' && activo);

                    html += '<td class="' + (esTarget ? 'supl-week__target is-busy' : '') + dim + '"' + span + '>';
                    if (esTarget) html += '<span class="supl-week__target-flag">Tiene clase</span>';
                    html += '<' + (pick ? 'button type="button"' : 'div') + ' class="supl-week__class'
                          + (c.ajeno ? ' is-ajeno' : '')
                          + (pick ? ' supl-week__class--pick' + (sel[c.periodo_id] ? ' is-on' : '') : '') + '"'
                          + ' style="background:' + col + ';"'
                          + (pick ? ' data-pick="' + c.periodo_id + '"' : '')
                          + ' title="' + esc(c.materia || 'Clase') + (sec ? ' · ' + esc(sec) : '')
                          + ' · ' + esc(c.inicio) + '–' + esc(c.fin) + (c.nivel ? ' · ' + esc(c.nivel) : '') + '">'
                          + '<span class="supl-week__class-mat" style="color:' + tinta + ';">' + esc(c.materia || 'Clase') + '</span>'
                          + '<span class="supl-week__class-sec" style="color:' + tintaSec + ';">' + esc(sec) + '</span>'
                          + '</' + (pick ? 'button' : 'div') + '></td>';
                    return;
                }

                // Hora libre. Una hora libre siempre es candidata a cubrir: si el sistema
                // la descarta (descanso, equidad…) lo dice el motivo del candidato, no la rejilla.
                if (esTarget) {
                    html += '<td class="supl-week__target is-free"' + span + '>'
                          + '<span class="supl-week__target-flag">Cubriría aquí</span>'
                          + '<div class="supl-week__target-in"><span>Libre</span></div></td>';
                    return;
                }
                html += '<td class="supl-week__free' + dim + '"' + span + '><span>Libre</span></td>';
            });
            html += '</tr>';
        });
        html += '</tbody></table></div>';

        /* Leyenda del modo select. `opts.legend` la saca de la rejilla y la pinta
           donde diga el llamador — en crear/solicitar, bajo el tip de Alex de la
           columna derecha. Sin esa opción se queda dentro de la rejilla, como
           antes, y el modo preview de agendar no la usa en absoluto.
           La instrucción genérica ("toca las clases que hay que cubrir") ya la da
           el tip de Alex: aquí solo va lo que aporta, que es el día concreto. */
        var legendHtml = '';
        if (mode === 'select') {
            // opts.fechaLabel = "martes 14 de julio"; sin ella se cae al nombre del día
            var cuando = opts.fechaLabel || DIAS_LARGO[dia];
            if (destinoEsTarjeta(opts)) {
                // Dentro de la tarjeta de Alex: texto corrido, no una caja dentro de
                // otra caja. Solo aporta el día concreto; el flujo ya lo cuenta la tarjeta.
                legendHtml = '<i class="fa-solid fa-hand-pointer"></i> Toca las clases del <b>'
                           + esc(cuando) + '</b>. Las de otros días están atenuadas.';
            } else {
                legendHtml = '<div class="supl-week-legend">'
                      + '<img src="/build/assets/img/alex/bby-alex-saluda.png" alt="Alex">'
                      + '<div class="supl-week-legend__txt">'
                      +   '<strong>Toca las clases del ' + esc(cuando) + '</strong>'
                      +   '<span>Las de otros días están atenuadas y no se pueden seleccionar.</span>'
                      + '</div>'
                      + '</div>';
            }
        }

        var destinoLeyenda = resolverLegend(opts);

        cont.innerHTML = destinoLeyenda ? html : html + legendHtml;
        if (destinoLeyenda) destinoLeyenda.innerHTML = legendHtml;

        if (mode !== 'select') return;

        // Clases del día activo indexadas por periodo_id, para devolver sus ids al
        // formulario. El periodo_id sigue identificando la hora sin ambigüedad: son
        // clases del propio ausente, siempre de sus propios niveles.
        var clasesDelDia = {};
        (rejilla[dia] || []).forEach(function (c) {
            if (c.tipo === 'clase') clasesDelDia[c.periodo_id] = c;
        });

        // Toggle de clases del día activo
        function emitir() {
            var out = [];
            cont.querySelectorAll('.supl-week__class--pick.is-on').forEach(function (b) {
                var pid = b.dataset.pick;
                out.push({ periodo_id: pid, clase: clasesDelDia[pid] || {} });
            });
            if (opts.onChange) opts.onChange(out);
        }
        cont.querySelectorAll('[data-pick]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                // `single` = radio: se apaga lo demás antes de encender esta. Se sigue
                // permitiendo desmarcar, para poder dejar el formulario sin elección.
                if (opts.single && !btn.classList.contains('is-on')) {
                    cont.querySelectorAll('[data-pick].is-on').forEach(function (o) {
                        o.classList.remove('is-on');
                    });
                }
                btn.classList.toggle('is-on');
                emitir();
            });
        });
        emitir();
    }

    /**
     * Descarga el horario de un profesor para una fecha.
     * `ini`/`fin` (opcionales) = la hora que se quiere cubrir. El servidor la inyecta
     * como corte del eje: sin eso, si es de un nivel que el candidato no imparte, no
     * sería frontera suya y el "Cubriría aquí" caería sobre un bloque libre entero.
     */
    function fetchHorario(profesorId, fecha, ini, fin) {
        var url = '/dashboard/suplencias/horario?profesor=' + encodeURIComponent(profesorId)
                + '&fecha=' + encodeURIComponent(fecha);
        if (ini && fin) url += '&ini=' + encodeURIComponent(ini) + '&fin=' + encodeURIComponent(fin);
        return fetch(url).then(function (r) { return r.json(); });
    }

    /** '2026-07-14' → 'martes 14 de julio'. Para el aviso de la rejilla en modo select. */
    var MESES = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
    var SEMANA = ['domingo','lunes','martes','miércoles','jueves','viernes','sábado'];
    function etiquetaFecha(ymd) {
        var p = /^(\d{4})-(\d{2})-(\d{2})$/.exec(String(ymd || ''));
        if (!p) return '';
        var d = new Date(+p[1], +p[2] - 1, +p[3]);
        return SEMANA[d.getDay()] + ' ' + d.getDate() + ' de ' + MESES[d.getMonth()];
    }

    window.SuplWeek = {
        render: render,
        fetch: fetchHorario,
        etiquetaFecha: etiquetaFecha,
        DIAS: DIAS,
        DIAS_LARGO: DIAS_LARGO
    };
})();
