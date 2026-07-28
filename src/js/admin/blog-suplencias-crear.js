/* blog-suplencias-crear — horario del profesor ausente: se marcan las clases a cubrir */
(function () {
    if (!document.body || document.body.dataset.page !== 'blog-suplencias-crear') return;

    var form = document.getElementById('form-crear-supl');
    if (!form || !window.SuplWeek) return;

    var fechaEl   = form.querySelector('[data-fecha]');
    var pickerVal = form.querySelector('[data-picker-value]');
    var weekEl    = form.querySelector('[data-week]');
    var inputsEl  = form.querySelector('[data-week-inputs]');
    var countEl   = form.querySelector('[data-picked-count]');
    if (!fechaEl || !pickerVal || !weekEl) return;

    var seleccion = [];

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
            countEl.classList.remove('is-error');
            countEl.textContent = horas.length
                ? horas.length + (horas.length === 1 ? ' hora marcada' : ' horas marcadas')
                : '';
        }
    }

    function cargar() {
        var prof = pickerVal.value, fecha = fechaEl.value;
        if (!prof) {
            weekEl.innerHTML = '<p class="supl-week-empty">Elige al profesor ausente para ver su horario.</p>';
            inputsEl.innerHTML = '';
            seleccion = [];
            if (countEl) countEl.textContent = '';
            return;
        }
        if (!fecha) return;
        weekEl.innerHTML = '<p class="supl-week-empty"><i class="fa-solid fa-spinner fa-spin"></i> Cargando horario…</p>';
        window.SuplWeek.fetch(prof, fecha)
            .then(function (data) {
                window.SuplWeek.render(weekEl, data, {
                    mode: 'select',
                    selected: seleccion,
                    fechaLabel: window.SuplWeek.etiquetaFecha(fecha),
                    onChange: pintarInputs
                });
            })
            .catch(function () {
                weekEl.innerHTML = '<p class="supl-week-empty">No se pudo cargar el horario.</p>';
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

    // El picker de colaboradores y el datepicker emiten 'change' en su hidden
    pickerVal.addEventListener('change', function () { seleccion = []; cargar(); });
    fechaEl.addEventListener('change', cargar);
    cargar();
})();
