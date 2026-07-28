/* admin-file — zonas de subida de archivos (.admin-file)
   Módulo compartido: sin guarda de página, se activa por la existencia de [data-file]. */
(function () {
    var zonas = document.querySelectorAll('[data-file]');
    if (!zonas.length) return;

    var MB = 1024 * 1024;

    function humano(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < MB) return Math.round(bytes / 1024) + ' KB';
        return (bytes / MB).toFixed(1).replace('.0', '') + ' MB';
    }

    zonas.forEach(function (zona) {
        var input = zona.querySelector('input[type="file"]');
        var titulo = zona.querySelector('[data-file-title]');
        var hint   = zona.querySelector('[data-file-hint]');
        if (!input || !titulo) return;

        var tituloBase = titulo.textContent;
        var hintBase   = hint ? hint.textContent : '';
        var maxMB      = parseFloat(zona.dataset.fileMax || '0');  // 0 = sin límite

        function reset() {
            zona.classList.remove('is-filled', 'is-error');
            titulo.textContent = tituloBase;
            if (hint) hint.textContent = hintBase;
        }

        input.addEventListener('change', function () {
            var file = input.files && input.files[0];
            if (!file) { reset(); return; }

            if (maxMB > 0 && file.size > maxMB * MB) {
                zona.classList.remove('is-filled');
                zona.classList.add('is-error');
                titulo.textContent = 'El archivo supera ' + maxMB + ' MB';
                if (hint) hint.textContent = file.name + ' · ' + humano(file.size);
                input.value = '';
                return;
            }

            zona.classList.remove('is-error');
            zona.classList.add('is-filled');
            titulo.textContent = file.name;
            if (hint) hint.textContent = humano(file.size) + ' · listo para subir';
        });
    });
})();
