/* blog-suplencias-solicitar — el profesor marca en su horario las clases a cubrir */
(function () {
    if (!document.body || document.body.dataset.page !== 'blog-suplencias-solicitar') return;

    var main = document.querySelector('.admin-content[data-profesor]');
    var form = document.getElementById('form-solicitar');
    if (!main || !form || !window.SuplWeek) return;

    var profesorId = main.dataset.profesor;
    var fechaEl  = form.querySelector('[data-fecha]');
    var weekEl   = form.querySelector('[data-week]');
    var inputsEl = form.querySelector('[data-week-inputs]');
    var countEl  = form.querySelector('[data-picked-count]');

    var seleccion = [];   // periodo_id marcados, se conservan al cambiar de fecha

    function pintarInputs(horas) {
        seleccion = horas.map(function (h) { return h.periodo_id; });
        inputsEl.innerHTML = horas.map(function (h) {
            var c = h.clase || {};
            return '<input type="hidden" name="periodo_id[]" value="' + h.periodo_id + '">' +
                   '<input type="hidden" name="grupo_id[]" value="'   + (c.grupo_id   || '') + '">' +
                   '<input type="hidden" name="aula_id[]" value="'    + (c.aula_id    || '') + '">' +
                   '<input type="hidden" name="materia_id[]" value="' + (c.materia_id || '') + '">';
        }).join('');
        if (countEl) {
            countEl.textContent = horas.length
                ? horas.length + (horas.length === 1 ? ' hora marcada' : ' horas marcadas')
                : '';
        }
    }

    function cargar() {
        var fecha = fechaEl.value;
        if (!fecha) return;
        weekEl.innerHTML = '<p class="supl-week-empty"><i class="fa-solid fa-spinner fa-spin"></i> Cargando tu horario…</p>';
        window.SuplWeek.fetch(profesorId, fecha)
            .then(function (data) {
                window.SuplWeek.render(weekEl, data, {
                    mode: 'select',
                    selected: seleccion,
                    fechaLabel: window.SuplWeek.etiquetaFecha(fecha),
                    legend: '[data-week-legend]',   // bajo el tip de Alex, no en la rejilla
                    onChange: pintarInputs
                });
            })
            .catch(function () {
                weekEl.innerHTML = '<p class="supl-week-empty">No se pudo cargar el horario. Recarga la página.</p>';
            });
    }

    // Sin horas marcadas no hay nada que cubrir
    form.addEventListener('submit', function (e) {
        if (!inputsEl.querySelector('input[name="periodo_id[]"]')) {
            e.preventDefault();
            weekEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
            if (countEl) {
                countEl.textContent = 'Marca al menos una clase';
                countEl.classList.add('is-error');
            }
        }
    });

    fechaEl.addEventListener('change', function () {
        if (countEl) countEl.classList.remove('is-error');
        cargar();
    });
    cargar();
})();
