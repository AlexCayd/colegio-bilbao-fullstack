/* blog-horarios-index — selector de entidad (profesor/aula/grupo) */
(function () {
    if (!document.body || document.body.dataset.page !== 'blog-horarios-index') return;
    var sel = document.querySelector('[data-hor-select]');
    if (!sel) return;
    sel.addEventListener('change', function () {
        var url = sel.dataset.url || location.pathname;
        location.href = url + '?id=' + encodeURIComponent(sel.value);
    });
})();
