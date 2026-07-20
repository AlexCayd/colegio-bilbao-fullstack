/* blog-dashboard
   Migrado desde el <script> embebido de views/blog/dashboard.php */
(function () {
    if (!document.body || document.body.dataset.page !== 'blog-dashboard') return;
    (function () {
        const _el = document.getElementById('dashboardChartData');
        const DBD = _el ? JSON.parse(_el.textContent) : {};

        // ── Section tabs ─────────────────────────────────────────────────────
        const tabBtns  = document.querySelectorAll('.db-tab-btn');
        const panels   = document.querySelectorAll('.db-panel');
        const chartRefs = {};
        let noticiaChartsInited = false;

        tabBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                tabBtns.forEach(b => b.classList.remove('active'));
                panels.forEach(p => p.classList.remove('active'));
                btn.classList.add('active');
                const panel = document.getElementById('panel-' + btn.dataset.panel);
                if (panel) {
                    panel.classList.add('active');
                    if (btn.dataset.panel === 'noticias' && !noticiaChartsInited) {
                        noticiaChartsInited = true;
                        initNoticiaCharts();
                    }
                    Object.values(chartRefs).forEach(c => { try { c.resize(); } catch(e){} });
                }
            });
        });

        // ── Counter animation ─────────────────────────────────────────────────
        const animCounter = (el) => {
            const target = parseInt(el.dataset.counter, 10);
            if (!target) { el.textContent = '0'; return; }
            const steps = 40;
            let step = 0;
            const timer = setInterval(() => {
                step++;
                el.textContent = Math.round(target * (step / steps));
                if (step >= steps) { el.textContent = target; clearInterval(timer); }
            }, 16);
        };
        if ('IntersectionObserver' in window) {
            const obs = new IntersectionObserver(entries => {
                entries.forEach(e => { if (e.isIntersecting) { animCounter(e.target); obs.unobserve(e.target); } });
            }, { threshold: 0.3 });
            document.querySelectorAll('[data-counter]').forEach(el => obs.observe(el));
        } else {
            document.querySelectorAll('[data-counter]').forEach(animCounter);
        }

        // ── Line/área chart — actividad mensual ───────────────────────────────
        const ctxBar = document.getElementById('chartMeses');
        if (ctxBar) {
            const monthData   = DBD.monthData;
            const monthLabels = DBD.monthLabels;
            const maxVal      = Math.max(...monthData, 1);

            chartRefs.bar = new Chart(ctxBar, {
                type: 'line',
                data: {
                    labels: monthLabels,
                    datasets: [{
                        label: 'Artículos',
                        data: monthData,
                        borderColor: '#4267ac',
                        borderWidth: 2.5,
                        backgroundColor: 'rgba(66,103,172,0.07)',
                        fill: true,
                        tension: 0.42,
                        pointBackgroundColor: '#4267ac',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        pointHoverBackgroundColor: '#4267ac',
                        pointHoverBorderColor: '#fff',
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#0F172A',
                            titleFont: { size: 12, weight: '700' },
                            bodyFont:  { size: 12 },
                            padding: 10,
                            cornerRadius: 8,
                            displayColors: false,
                            callbacks: {
                                label: ctx => ctx.parsed.y + (ctx.parsed.y === 1 ? ' artículo' : ' artículos'),
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid:   { display: false },
                            border: { display: false },
                            ticks:  { color: '#94A3B8', font: { size: 11 } }
                        },
                        y: {
                            beginAtZero: true,
                            suggestedMax: maxVal + 1,
                            border: { display: false, dash: [4, 4] },
                            grid:   { color: 'rgba(148,163,184,0.12)' },
                            ticks: {
                                stepSize: 1,
                                color: '#94A3B8',
                                font: { size: 11 },
                                callback: v => Number.isInteger(v) ? v : ''
                            }
                        }
                    }
                }
            });
        }

        // ── Donut chart ───────────────────────────────────────────────────────
        const ctxDon = document.getElementById('chartCategorias');
        if (ctxDon) {
            chartRefs.don = new Chart(ctxDon, {
                type: 'doughnut',
                data: {
                    labels: DBD.catLabels,
                    datasets: [{
                        data: DBD.catData,
                        backgroundColor: DBD.catColors,
                        borderColor: '#fff',
                        borderWidth: 3,
                        hoverOffset: 8,
                    }]
                },
                options: {
                    responsive: false,
                    cutout: '68%',
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#0F172A',
                            titleFont: { size: 12, weight: '700' },
                            bodyFont:  { size: 12 },
                            padding: 10,
                            cornerRadius: 8,
                            callbacks: {
                                label: ctx => '  ' + ctx.parsed + (ctx.parsed === 1 ? ' artículo' : ' artículos'),
                            }
                        }
                    }
                }
            });
        }

        // ── Noticias charts — init lazy (panel starts hidden) ────────────────
        function initNoticiaCharts() {

            const ctxNBar = document.getElementById('chartNMeses');
            if (ctxNBar) {
                const nMonthData   = DBD.nMonthData;
                const nMonthLabels = DBD.nMonthLabels;
                const nMaxVal      = Math.max(...nMonthData, 1);

                chartRefs.nbar = new Chart(ctxNBar, {
                    type: 'line',
                    data: {
                        labels: nMonthLabels,
                        datasets: [{
                            label: 'Noticias',
                            data: nMonthData,
                            borderColor: '#374C69',
                            borderWidth: 2.5,
                            backgroundColor: 'rgba(55,76,105,0.07)',
                            fill: true,
                            tension: 0.42,
                            pointBackgroundColor: '#374C69',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        interaction: { mode: 'index', intersect: false },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: '#0F172A',
                                titleFont: { size: 12, weight: '700' },
                                bodyFont:  { size: 12 },
                                padding: 10,
                                cornerRadius: 8,
                                displayColors: false,
                                callbacks: {
                                    label: ctx => ctx.parsed.y + (ctx.parsed.y === 1 ? ' noticia' : ' noticias'),
                                }
                            }
                        },
                        scales: {
                            x: { grid: { display: false }, border: { display: false }, ticks: { color: '#94A3B8', font: { size: 11 } } },
                            y: {
                                beginAtZero: true,
                                suggestedMax: nMaxVal + 1,
                                border: { display: false, dash: [4, 4] },
                                grid: { color: 'rgba(148,163,184,0.12)' },
                                ticks: { stepSize: 1, color: '#94A3B8', font: { size: 11 }, callback: v => Number.isInteger(v) ? v : '' }
                            }
                        }
                    }
                });
            }

            const ctxNDon = document.getElementById('chartNCategorias');
            if (ctxNDon) {
                chartRefs.ndon = new Chart(ctxNDon, {
                    type: 'doughnut',
                    data: {
                        labels: DBD.nCatLabels,
                        datasets: [{
                            data: DBD.nCatData,
                            backgroundColor: DBD.nCatColors,
                            borderColor: '#fff',
                            borderWidth: 3,
                            hoverOffset: 8,
                        }]
                    },
                    options: {
                        responsive: false,
                        cutout: '68%',
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: '#0F172A',
                                titleFont: { size: 12, weight: '700' },
                                bodyFont:  { size: 12 },
                                padding: 10,
                                cornerRadius: 8,
                                callbacks: {
                                    label: ctx => '  ' + ctx.parsed + (ctx.parsed === 1 ? ' noticia' : ' noticias'),
                                }
                            }
                        }
                    }
                });
            }
        }

    })();
})();
