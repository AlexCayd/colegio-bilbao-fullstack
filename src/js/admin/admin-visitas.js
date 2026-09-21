/* admin-visitas — gráfica de visitas al sitio público (views/blog/_visitas.php).

   Se activa por existencia de `[data-visitas]`: el bloque solo lo pinta el home y solo
   para un administrador, así que una guarda de `data-page` sobraría y encima escondería
   el módulo si mañana se reutiliza en otra pantalla.

   Las cuatro series vienen ya calculadas en la isla JSON `#visData`, así que cambiar de
   rango es instantáneo y no va al servidor. Datos de PHP a JS por isla, nunca
   interpolados dentro del script. */
(function () {
    var raiz = document.querySelector('[data-visitas]');
    if (!raiz) return;

    var isla = document.getElementById('visData');
    if (!isla) return;

    var series;
    try { series = JSON.parse(isla.textContent || '{}'); }
    catch (e) { return; }

    var elTotal = raiz.querySelector('[data-vis-total]');
    var elMedia = raiz.querySelector('[data-vis-media]');
    var canvas  = raiz.querySelector('#visChart');
    var aviso   = raiz.querySelector('[data-vis-sinchart]');
    var botones = Array.prototype.slice.call(raiz.querySelectorAll('[data-vis-rango]'));

    var chart = null;

    function miles(n) { return Number(n || 0).toLocaleString('es-MX'); }

    /** 'YYYY-MM-DD' → '4 sep'. El año no cabe ni hace falta en el eje. */
    function etiqueta(iso) {
        var m = ['ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic'];
        var p = String(iso).split('-');
        return parseInt(p[2], 10) + ' ' + (m[parseInt(p[1], 10) - 1] || '');
    }

    function pintar(rango) {
        var s = series[String(rango)];
        if (!s) return;

        if (elTotal) elTotal.textContent = miles(s.total);
        // La media va con coma decimal: es un panel en español y un "12.4" se lee como
        // doce mil cuatrocientos.
        if (elMedia) elMedia.textContent = String(s.media).replace('.', ',');

        if (!window.Chart || !canvas) {
            if (aviso) aviso.hidden = false;
            return;
        }

        var labels = (s.labels || []).map(etiqueta);
        var datos  = s.datos || [];

        if (chart) {
            chart.data.labels = labels;
            chart.data.datasets[0].data = datos;
            chart.update();
            return;
        }

        var ctx = canvas.getContext('2d');
        // Degradado bajo la línea: con muchos días el área da la forma de la tendencia
        // mejor que la línea sola.
        var grad = ctx.createLinearGradient(0, 0, 0, 220);
        grad.addColorStop(0, 'rgba(66, 133, 244, .28)');
        grad.addColorStop(1, 'rgba(66, 133, 244, 0)');

        chart = new window.Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Visitas',
                    data: datos,
                    borderColor: '#4285f4',
                    backgroundColor: grad,
                    borderWidth: 2,
                    fill: true,
                    tension: .32,
                    // Sin puntos salvo al pasar por encima: con 60 días la línea se
                    // convertía en un collar de bolitas y no se veía la tendencia.
                    pointRadius: 0,
                    pointHoverRadius: 5,
                    pointHoverBackgroundColor: '#4285f4',
                    pointHoverBorderColor: '#fff',
                    pointHoverBorderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#0B1F3D',
                        padding: 10,
                        displayColors: false,
                        callbacks: {
                            label: function (c) { return miles(c.parsed.y) + ' visitas'; }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: {
                            color: '#94a3b8', font: { size: 11 },
                            maxRotation: 0, autoSkipPadding: 18
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: '#eef2f7' },
                        border: { display: false },
                        // Sin decimales: media visita no existe.
                        ticks: { color: '#94a3b8', font: { size: 11 }, precision: 0 }
                    }
                }
            }
        });
    }

    botones.forEach(function (b) {
        b.addEventListener('click', function () {
            botones.forEach(function (o) {
                o.classList.remove('is-on');
                o.setAttribute('aria-pressed', 'false');
            });
            b.classList.add('is-on');
            b.setAttribute('aria-pressed', 'true');
            pintar(b.dataset.visRango);
        });
    });

    pintar(30);   // el mismo que nace marcado en el HTML
})();
