/* admin-supl-week — rejilla semanal de horario reutilizable
   La usan solicitar/crear (elegir las horas a cubrir) y agendar (preview del suplente).
   Módulo compartido: no lleva guarda de página, expone window.SuplWeek. */
(function () {
    var DIAS    = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes'];
    var DIAS_LG = { lunes: 'Lun', martes: 'Mar', miercoles: 'Mié', jueves: 'Jue', viernes: 'Vie' };
    var DIAS_LARGO = { lunes: 'Lunes', martes: 'Martes', miercoles: 'Miércoles', jueves: 'Jueves', viernes: 'Viernes' };

    // Paleta institucional: color estable por materia (mismo criterio que el módulo Horarios)
    var COLORES = ['#4285f4', '#46bdc6', '#8ac926', '#f5b400', '#fc6722', '#aa2296', '#4267ac', '#34a853', '#ea075a', '#e51022'];
    var CLAROS  = { '#f5b400': 1, '#8ac926': 1, '#46bdc6': 1 };

    /* CRC-32 sobre los bytes UTF-8: replica crc32() de PHP para que una materia
       tenga el mismo color aquí y en views/blog/horarios/index.php. */
    var CRC_TABLA = (function () {
        var t = new Int32Array(256), c, n, k;
        for (n = 0; n < 256; n++) {
            c = n;
            for (k = 0; k < 8; k++) c = (c & 1) ? (0xEDB88320 ^ (c >>> 1)) : (c >>> 1);
            t[n] = c;
        }
        return t;
    })();
    function crc32(str) {
        var bytes = new TextEncoder().encode(str), c = -1, i;
        for (i = 0; i < bytes.length; i++) c = (c >>> 8) ^ CRC_TABLA[(c ^ bytes[i]) & 0xFF];
        return (c ^ -1) >>> 0;
    }
    function colorMateria(nombre) {
        if (!nombre) return '#94a3b8';
        return COLORES[crc32(nombre) % COLORES.length];
    }
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
     * @param {HTMLElement} cont  contenedor (se vacía)
     * @param {Object} data       respuesta de /dashboard/suplencias/horario
     * @param {Object} opts
     *   mode      'select' | 'preview'   (por defecto 'preview')
     *   dia       día activo; si se omite se usa data.dia
     *   target    periodo_id de la hora de cobertura (modo preview)
     *   selected  array de periodo_id preseleccionados (modo select)
     *   onChange  callback(arrayDeSeleccionados) en modo select
     */
    function render(cont, data, opts) {
        opts = opts || {};
        var mode = opts.mode || 'preview';
        var dia  = opts.dia || data.dia;
        var semana = data.semana || {};
        var periodos = data.periodos || [];

        if (!dia) {
            cont.innerHTML = '<p class="supl-week-empty">La fecha elegida cae en fin de semana: no hay clases que cubrir.</p>';
            if (opts.onChange) opts.onChange([]);
            return;
        }
        if (!periodos.length) {
            cont.innerHTML = '<p class="supl-week-empty">No hay jornada configurada.</p>';
            return;
        }

        var sel = {};
        (opts.selected || []).forEach(function (p) { sel[p] = true; });

        var html = '<div class="supl-week-wrap"><table class="supl-week"><thead><tr><th class="supl-week__corner"></th>';
        DIAS.forEach(function (d) {
            html += '<th class="supl-week__dayhead' + (d === dia ? ' is-active-day' : '') + '">' + DIAS_LG[d] + '</th>';
        });
        html += '</tr></thead><tbody>';

        periodos.forEach(function (p) {
            html += '<tr><th class="supl-week__hour"><span>' + esc(p.etiqueta) + '</span><small>' + esc(p.inicio) + '</small></th>';
            DIAS.forEach(function (d) {
                var activo = (d === dia);
                var dim    = activo ? '' : ' is-dim';
                var clase  = (semana[d] || {})[p.id];

                if (p.es_receso) {
                    html += '<td class="supl-week__receso' + dim + '"><i class="fa-solid fa-mug-hot"></i></td>';
                    return;
                }
                if (clase) {
                    var col       = colorMateria(clase.materia);
                    var tinta     = CLAROS[col] ? '#1f2937' : '#fff';
                    var tintaSec  = CLAROS[col] ? 'rgba(31,41,55,.72)' : 'rgba(255,255,255,.8)';
                    var sec       = subtitulo(clase);
                    var esTarget  = (mode === 'preview' && activo && String(p.id) === String(opts.target));
                    var pick      = (mode === 'select' && activo);

                    html += '<td class="' + (esTarget ? 'supl-week__target is-busy' : '') + dim + '">';
                    if (esTarget) html += '<span class="supl-week__target-flag">Tiene clase</span>';
                    html += '<' + (pick ? 'button type="button"' : 'div') + ' class="supl-week__class'
                          + (pick ? ' supl-week__class--pick' + (sel[p.id] ? ' is-on' : '') : '') + '"'
                          + ' style="background:' + col + ';"'
                          + (pick ? ' data-pick="' + p.id + '"' : '')
                          + ' title="' + esc(clase.materia || 'Clase') + (sec ? ' · ' + esc(sec) : '') + '">'
                          + '<span class="supl-week__class-mat" style="color:' + tinta + ';">' + esc(clase.materia || 'Clase') + '</span>'
                          + '<span class="supl-week__class-sec" style="color:' + tintaSec + ';">' + esc(sec) + '</span>'
                          + '</' + (pick ? 'button' : 'div') + '></td>';
                    return;
                }
                // Hora libre. Una hora libre siempre es candidata a cubrir: si el sistema
                // la descarta (descanso, equidad…) lo dice el motivo del candidato, no la rejilla.
                if (mode === 'preview' && activo && String(p.id) === String(opts.target)) {
                    html += '<td class="supl-week__target is-free">'
                          + '<span class="supl-week__target-flag">Cubriría aquí</span>'
                          + '<div class="supl-week__target-in"><span>Libre</span></div></td>';
                    return;
                }
                html += '<td class="supl-week__free' + dim + '"><span>Libre</span></td>';
            });
            html += '</tr>';
        });
        html += '</tbody></table></div>';

        if (mode === 'select') {
            // opts.fechaLabel = "martes 14 de julio"; sin ella se cae al nombre del día
            var cuando = opts.fechaLabel || DIAS_LARGO[dia];
            html += '<div class="supl-week-legend">'
                  + '<img src="/build/assets/img/alex/bby-alex-saluda.png" alt="Alex">'
                  + '<div class="supl-week-legend__txt">'
                  +   '<strong>Marca las clases que hay que cubrir</strong>'
                  +   '<span>Toca las del <b>' + esc(cuando) + '</b>. Las de otros días están atenuadas '
                  +     'y no se pueden seleccionar.</span>'
                  + '</div>'
                  + '</div>';
        }
        cont.innerHTML = html;

        if (mode !== 'select') return;

        // Toggle de clases del día activo
        function emitir() {
            var out = [];
            cont.querySelectorAll('.supl-week__class--pick.is-on').forEach(function (b) {
                var pid = b.dataset.pick;
                out.push({ periodo_id: pid, clase: (semana[dia] || {})[pid] || {} });
            });
            if (opts.onChange) opts.onChange(out);
        }
        cont.querySelectorAll('[data-pick]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                btn.classList.toggle('is-on');
                emitir();
            });
        });
        emitir();
    }

    /** Descarga el horario de un profesor para una fecha. */
    function fetchHorario(profesorId, fecha) {
        return fetch('/dashboard/suplencias/horario?profesor=' + encodeURIComponent(profesorId) + '&fecha=' + encodeURIComponent(fecha))
            .then(function (r) { return r.json(); });
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
