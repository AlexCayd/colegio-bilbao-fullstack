/* admin-usuario-permisos — reglas del formulario de usuarios (crear y editar).
   Componente compartido: no lleva guarda de página, se activa por la presencia de
   los campos. Antes esta lógica estaba duplicada palabra por palabra en
   blog-usuarios-crear.js y blog-usuarios-editar.js.

   Tres reglas:
   1. Los módulos y el sub-rol de redacción solo aplican al rol `usuario`.
   2. "No puede suplir" solo tiene sentido para el personal docente.
   3. `prefecto` es EXCLUYENTE: prefectura coordina las suplencias, no las cubre,
      así que no se combina con `profesor` ni `administrativo`. El servidor lo
      vuelve a imponer en UsuarioBlog::normalizarTipoPersonal(); esto es la ayuda
      visual, no la validación. */
(function () {
    var tipos = document.querySelectorAll('input[name="tipo_personal[]"]');
    var grupo = document.getElementById('modulos-group');
    if (!tipos.length && !grupo) return;

    // ── 1. Módulos y sub-rol de redacción según el rol ──
    (function () {
        if (!grupo) return;
        var sub          = document.getElementById('redaccion-subrol');
        var rolRadios    = document.querySelectorAll('input[name="rol"]');
        var chkRedaccion = document.querySelector('.admin-modulo-check[data-modulo="redaccion"] input[type="checkbox"]');

        function rolActual() {
            var r = document.querySelector('input[name="rol"]:checked');
            return r ? r.value : null;
        }
        function syncSub() {
            var ver = rolActual() === 'usuario' && chkRedaccion && chkRedaccion.checked;
            if (sub) sub.style.display = ver ? 'block' : 'none';
        }
        function syncModulos() {
            grupo.style.display = rolActual() === 'usuario' ? 'block' : 'none';
            syncSub();
        }
        rolRadios.forEach(function (r) { r.addEventListener('change', syncModulos); });
        if (chkRedaccion) chkRedaccion.addEventListener('change', syncSub);
        syncModulos();
    })();

    // ── 2 y 3. Tipo de personal ──
    (function () {
        if (!tipos.length) return;

        var porValor = {};
        tipos.forEach(function (cb) { porValor[cb.value] = cb; });

        var prefecto     = porValor['prefecto'];
        var profesor     = porValor['profesor'];
        var suplirToggle = document.getElementById('suplir-toggle');

        /** Marca visualmente la casilla deshabilitada, no solo el input. */
        function bloquear(cb, on) {
            if (!cb) return;
            cb.disabled = on;
            if (on) cb.checked = false;
            var label = cb.closest('.admin-modulo-check');
            if (label) label.classList.toggle('is-disabled', on);
        }

        function syncExclusividad() {
            var esPrefecto = prefecto && prefecto.checked;
            // Prefecto marcado → los otros dos se apagan y se bloquean
            ['profesor', 'administrativo'].forEach(function (k) { bloquear(porValor[k], esPrefecto); });
            // …y al revés: con cualquier otro marcado, prefecto deja de estar disponible
            var otroMarcado = ['profesor', 'administrativo'].some(function (k) {
                return porValor[k] && porValor[k].checked;
            });
            bloquear(prefecto, otroMarcado);
        }

        function syncSuplir() {
            if (!suplirToggle) return;
            var esProfesor = profesor && profesor.checked;
            suplirToggle.hidden = !esProfesor;
            // Si deja de ser profesor, no debe quedar excluido de las suplencias
            if (!esProfesor) {
                var cb = suplirToggle.querySelector('input[name="no_puede_suplir"]');
                if (cb) cb.checked = false;
            }
        }

        tipos.forEach(function (cb) {
            cb.addEventListener('change', function () { syncExclusividad(); syncSuplir(); });
        });
        syncExclusividad();
        syncSuplir();
    })();
})();
