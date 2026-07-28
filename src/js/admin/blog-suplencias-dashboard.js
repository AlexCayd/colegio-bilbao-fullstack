/* blog-suplencias-dashboard — gráficas (Chart.js) + calendario interactivo de resumen diario */
(function () {
    if (!document.body || document.body.dataset.page !== 'blog-suplencias-dashboard') return;

    var island = document.getElementById('suplDashData');
    if (!island) return;
    var data = {};
    try { data = JSON.parse(island.textContent || '{}'); } catch (e) { return; }

    var BRAND = ['#4285f4', '#46bdc6', '#8ac926', '#f5b400', '#fc6722', '#aa2296', '#4267ac', '#34a853', '#ea075a', '#e51022'];
    var ESTADO_LBL = { solicitada: 'Solicitada', agendada: 'Agendada', en_curso: 'En curso', por_justificar: 'Por justificar', completada: 'Completada', cancelada: 'Cancelada' };
    var ESTADO_COL = { solicitada: '#f5b400', agendada: '#4285f4', en_curso: '#46bdc6', por_justificar: '#e51022', completada: '#34a853', cancelada: '#94a3b8' };
    var MESES = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
    var MESES_AB = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];

    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }
    /** '2026-03' → 'Mar 2026' */
    function etiquetaMes(ym) {
        var p = /^(\d{4})-(\d{2})$/.exec(String(ym || ''));
        return p ? MESES_AB[+p[2] - 1] + ' ' + p[1] : ym;
    }

    // ── Gráficas ──
    // responsive + maintainAspectRatio:false → cada canvas llena su .sd-chart,
    // que es lo que mantiene todas las tarjetas a la misma altura.
    function newChart(id, cfg) {
        var el = document.getElementById(id);
        if (!el) return;
        cfg.options = cfg.options || {};
        cfg.options.responsive = true;
        cfg.options.maintainAspectRatio = false;
        new Chart(el, cfg);
    }

    if (typeof Chart !== 'undefined') {
        Chart.defaults.font.family = "'Outfit', sans-serif";
        Chart.defaults.color = '#64748b';

        var est = data.porEstado || {};
        var estKeys = Object.keys(est);
        newChart('chartEstado', {
            type: 'doughnut',
            data: { labels: estKeys.map(function (k) { return ESTADO_LBL[k] || k; }),
                    datasets: [{ data: estKeys.map(function (k) { return est[k]; }), backgroundColor: estKeys.map(function (k) { return ESTADO_COL[k] || '#cbd5e1'; }), borderWidth: 0 }] },
            options: { plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, usePointStyle: true, pointStyle: 'circle' } } }, cutout: '62%' }
        });

        var pm = data.porMes || [];
        newChart('chartMes', {
            type: 'bar',
            data: { labels: pm.map(function (r) { return etiquetaMes(r.mes); }), datasets: [{ label: 'Suplencias', data: pm.map(function (r) { return r.n; }), backgroundColor: '#4285f4', borderRadius: 6, maxBarThickness: 46 }] },
            options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } }, x: { grid: { display: false } } } }
        });

        var ts = data.topSuplentes || [];
        newChart('chartSuplentes', {
            type: 'bar',
            data: { labels: ts.map(function (r) { return r.nombre; }), datasets: [{ label: 'Coberturas', data: ts.map(function (r) { return r.n; }), backgroundColor: '#34a853', borderRadius: 6, maxBarThickness: 22 }] },
            options: { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, ticks: { precision: 0 } }, y: { grid: { display: false } } } }
        });

        var pmat = data.porMateria || [];
        newChart('chartMateria', {
            type: 'bar',
            data: { labels: pmat.map(function (r) { return r.materia; }), datasets: [{ label: 'Horas', data: pmat.map(function (r) { return r.n; }), backgroundColor: pmat.map(function (_, i) { return BRAND[i % BRAND.length]; }), borderRadius: 6, maxBarThickness: 22 }] },
            options: { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, ticks: { precision: 0 } }, y: { grid: { display: false } } } }
        });

        var pmot = data.porMotivo || [];
        newChart('chartMotivo', {
            type: 'bar',
            data: { labels: pmot.map(function (r) { return r.motivo; }), datasets: [{ label: 'Ausencias', data: pmot.map(function (r) { return r.n; }), backgroundColor: pmot.map(function (_, i) { return BRAND[(i + 3) % BRAND.length]; }), borderRadius: 6, maxBarThickness: 22 }] },
            options: { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, ticks: { precision: 0 } }, y: { grid: { display: false } } } }
        });

        var org = data.porOrigen || {};
        newChart('chartOrigen', {
            type: 'doughnut',
            data: { labels: ['Anticipadas', 'Sin aviso'],
                    datasets: [{ data: [org.anticipada || 0, org.sin_aviso || 0], backgroundColor: ['#4285f4', '#fc6722'], borderWidth: 0 }] },
            options: { plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, usePointStyle: true, pointStyle: 'circle' } } }, cutout: '62%' }
        });
    }

    // ── Calendario de resumen diario (interactivo) ──
    (function () {
        var root = document.getElementById('sdCal');
        if (!root) return;

        var counts  = {};
        (data.resumen || []).forEach(function (r) { counts[r.fecha] = r.n; });
        var detalle = data.detalle || {};

        var grid    = root.querySelector('[data-cal-grid]');
        var label   = root.querySelector('[data-cal-label]');
        var panel   = document.querySelector('[data-cal-detalle]');
        var fechas  = Object.keys(counts).sort();

        // Arrancar en el mes más reciente CON datos: con `new Date()` la rejilla
        // solía abrirse vacía si el periodo cargado no incluye el mes actual.
        var cur = new Date();
        cur.setDate(1);
        if (fechas.length) {
            var ultima = fechas[fechas.length - 1].split('-');
            cur = new Date(+ultima[0], +ultima[1] - 1, 1);
        }

        function pad(n) { return (n < 10 ? '0' : '') + n; }

        function pintarDetalle(ymd) {
            if (!panel) return;
            var lista = detalle[ymd] || [];
            var p = ymd.split('-');
            var titulo = (+p[2]) + ' de ' + MESES[+p[1] - 1].toLowerCase() + ' de ' + p[0];

            if (!lista.length) {
                panel.innerHTML = '<p class="sd-day__hint">Sin suplencias el ' + esc(titulo) + '.</p>';
                return;
            }
            panel.innerHTML =
                '<div class="sd-day__head"><strong>' + esc(titulo) + '</strong>' +
                '<span>' + lista.length + (lista.length === 1 ? ' suplencia' : ' suplencias') + '</span></div>' +
                lista.map(function (s) {
                    var col = ESTADO_COL[s.estado] || '#94a3b8';
                    return '<div class="sd-day__item">' +
                        '<span class="sd-day__dot" style="background:' + col + ';"></span>' +
                        '<span class="sd-day__body">' +
                            '<span class="sd-day__name">' + esc(s.ausente) + '</span>' +
                            '<span class="sd-day__meta">' + esc(s.motivo) +
                                ' · ' + s.cubiertas + '/' + s.horas + ' h cubiertas' +
                                (s.origen === 'sin_aviso' ? ' · <em>sin aviso</em>' : '') +
                            '</span>' +
                        '</span>' +
                        '<span class="sd-day__estado" style="color:' + col + ';">' + esc(ESTADO_LBL[s.estado] || s.estado) + '</span>' +
                    '</div>';
                }).join('');
        }

        function draw() {
            var y = cur.getFullYear(), m = cur.getMonth();
            label.textContent = MESES[m] + ' ' + y;
            var offset = (new Date(y, m, 1).getDay() + 6) % 7;   // lunes = 0
            var days   = new Date(y, m + 1, 0).getDate();
            var html   = '';

            for (var i = 0; i < offset; i++) html += '<span class="bilbao-cal__cell bilbao-cal__cell--empty"></span>';
            for (var d = 1; d <= days; d++) {
                var key = y + '-' + pad(m + 1) + '-' + pad(d);
                var n   = counts[key] || 0;
                if (n) {
                    html += '<button type="button" class="bilbao-cal__cell has-event" data-ymd="' + key + '"' +
                            ' title="' + n + (n === 1 ? ' suplencia' : ' suplencias') + '">' + d +
                            '<span class="bilbao-cal__badge">' + n + '</span></button>';
                } else {
                    html += '<span class="bilbao-cal__cell is-plain">' + d + '</span>';
                }
            }
            grid.innerHTML = html;
        }

        grid.addEventListener('click', function (e) {
            var cell = e.target.closest('[data-ymd]');
            if (!cell) return;
            grid.querySelectorAll('.is-active').forEach(function (c) { c.classList.remove('is-active'); });
            cell.classList.add('is-active');
            pintarDetalle(cell.dataset.ymd);
        });

        var prev = root.querySelector('[data-cal-prev]'), next = root.querySelector('[data-cal-next]');
        if (prev) prev.addEventListener('click', function () { cur.setMonth(cur.getMonth() - 1); draw(); });
        if (next) next.addEventListener('click', function () { cur.setMonth(cur.getMonth() + 1); draw(); });
        draw();
    })();
})();
