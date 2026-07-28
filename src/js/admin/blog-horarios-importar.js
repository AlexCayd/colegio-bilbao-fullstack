/* blog-horarios-importar — filtro de la vista previa del CSV de horarios */
(function () {
    if (!document.body || document.body.dataset.page !== 'blog-horarios-importar') return;

    var check = document.querySelector('[data-solo-problemas]');
    if (!check) return;

    var filas = Array.prototype.slice.call(document.querySelectorAll('.hoi-row'));

    check.addEventListener('change', function () {
        var solo = check.checked;
        filas.forEach(function (tr) {
            var problema = tr.dataset.estado !== 'ok';
            tr.hidden = solo && !problema;
        });
    });
})();
