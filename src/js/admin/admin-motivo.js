/* admin-motivo — el select de motivo revela un campo de texto al elegir "Otro".
   Componente compartido de Suplencias: se activa por [data-motivo], sin guarda de página
   (lo usan crear.php y solicitar.php). */
(function () {
    document.querySelectorAll('[data-motivo]').forEach(function (root) {
        var select = root.querySelector('[data-motivo-select]');
        var caja   = root.querySelector('[data-motivo-otro]');
        var input  = root.querySelector('[data-motivo-otro-input]');
        if (!select || !caja || !input) return;

        function sincronizar() {
            var otro = select.value === 'Otro';
            caja.hidden = !otro;
            // required solo cuando está visible: si no, el navegador bloquea el envío
            input.required = otro;
            if (!otro) input.value = '';
        }

        select.addEventListener('change', function () {
            sincronizar();
            if (select.value === 'Otro') input.focus();
        });
        sincronizar();
    });
})();
