/* admin-usuario-permisos — reglas del formulario de usuarios (crear y editar).
   Componente compartido: no lleva guarda de página, se activa por la presencia de
   los campos. Antes esta lógica estaba duplicada palabra por palabra en
   blog-usuarios-crear.js y blog-usuarios-editar.js.

   Tres reglas:
   1. Los módulos y el sub-rol de redacción solo aplican al rol `usuario`.
   2. "No puede suplir" solo tiene sentido para el personal docente.
   3. Los tipos EXCLUYENTES (`prefecto`, `directivo`) no se combinan con nada, ni
      entre sí: son puestos de coordinación, no se acumulan con la docencia. Cuáles
      son NO se escribe aquí: el partial marca esas tarjetas con `data-excluyente`
      desde UsuarioBlog::TIPOS_EXCLUYENTES, así que la regla tiene una sola fuente.
      El servidor la vuelve a imponer en UsuarioBlog::normalizarTipoPersonal(); esto
      es la ayuda visual, no la validación. */
(function () {
    var tipos = document.querySelectorAll('input[name="tipo_personal[]"]');
    var grupo = document.getElementById('modulos-group');
    if (!tipos.length && !grupo) return;

    // ── 1. Módulos y sub-rol de redacción según el rol ──
    (function () {
        if (!grupo) return;
        var sub          = document.getElementById('redaccion-subrol');
        var rolRadios    = document.querySelectorAll('input[name="rol"]');
        var chkRedaccion = document.querySelector('.admin-mod-chip[data-modulo="redaccion"] input[type="checkbox"]');

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

        /* Cuáles son excluyentes lo dice el DOM (`data-excluyente` en la tarjeta), que
           lo pinta el partial desde UsuarioBlog::TIPOS_EXCLUYENTES. Antes esta lista
           estaba escrita a mano aquí y era una segunda fuente de verdad que había que
           acordarse de actualizar. */
        var EXCLUYENTES = [];
        var COMBINABLES = [];
        tipos.forEach(function (cb) {
            var card = cb.closest('.admin-tipo-card');
            (card && card.dataset.excluyente ? EXCLUYENTES : COMBINABLES).push(cb.value);
        });
        var profesor     = porValor['profesor'];
        var suplirToggle = document.getElementById('suplir-toggle');
        var nivelesGrupo = document.getElementById('niveles-group');

        /** Marca visualmente la casilla deshabilitada, no solo el input. */
        function bloquear(cb, on) {
            if (!cb) return;
            cb.disabled = on;
            if (on) cb.checked = false;
            var label = cb.closest('.admin-tipo-card');
            if (label) label.classList.toggle('is-disabled', on);
        }

        function syncExclusividad() {
            var marcado = EXCLUYENTES.filter(function (k) {
                return porValor[k] && porValor[k].checked;
            });
            var hayExcluyente = marcado.length > 0;

            // Un excluyente marcado → los combinables se apagan y se bloquean…
            COMBINABLES.forEach(function (k) { bloquear(porValor[k], hayExcluyente); });
            // …y el otro excluyente también (prefecto y directivo no se mezclan).
            EXCLUYENTES.forEach(function (k) {
                bloquear(porValor[k], hayExcluyente && marcado.indexOf(k) === -1);
            });
            // Al revés: con un combinable marcado, ningún excluyente está disponible.
            var otroMarcado = COMBINABLES.some(function (k) {
                return porValor[k] && porValor[k].checked;
            });
            if (!hayExcluyente) {
                EXCLUYENTES.forEach(function (k) { bloquear(porValor[k], otroMarcado); });
            }
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

        /* Los niveles sirven a DOS puestos, y significan cosas distintas en cada uno:
           un profesor IMPARTE esos niveles; una dirección los GESTIONA (y vacío = todo
           el colegio, que es lo contrario de "se deducen"). Por eso el campo se muestra
           para los dos y el rótulo se alterna.
           UsuarioBlog::normalizarNiveles() lo vuelve a imponer en el servidor; esto es
           la ayuda visual. */
        function syncNiveles() {
            if (!nivelesGrupo) return;
            var esProfesor  = !!(profesor && profesor.checked);
            var esDirectivo = !!(porValor['directivo'] && porValor['directivo'].checked);
            var aplica      = esProfesor || esDirectivo;

            nivelesGrupo.hidden = !aplica;

            // ⚠️ Solo se limpian los checks cuando el campo deja de aplicar del todo.
            // Alternar profesor↔directivo mantiene la selección: los dos usan la misma
            // columna y borrarla al cambiar de tipo perdería el dato sin avisar.
            if (!aplica) {
                nivelesGrupo.querySelectorAll('input[name="niveles[]"]').forEach(function (cb) {
                    cb.checked = false;
                });
            }

            // Rótulo y ayuda según el puesto, sin interpolar PHP en JS.
            var quien = esDirectivo ? 'directivo' : 'profesor';
            nivelesGrupo
                .querySelectorAll('[data-niveles-label], [data-niveles-hint]')
                .forEach(function (el) {
                    var v = el.getAttribute('data-niveles-label') || el.getAttribute('data-niveles-hint');
                    el.hidden = (v !== quien);
                });
        }

        /* ── Módulos sugeridos al marcar un tipo ──
           Dar de alta a alguien «como los demás» era un ejercicio de memoria sobre una
           lista de trece módulos. Marcar el puesto ahora los preselecciona.

           Tres decisiones:
           · La lista NO se escribe aquí. Viene en `data-modulos` de la tarjeta, que el
             partial pinta desde UsuarioBlog::MODULOS_SUGERIDOS — una sola fuente.
           · Se UNEN, nunca se desmarcan: es una sugerencia, y quitarle a alguien un
             módulo que el admin acababa de marcar a mano sería pelearse con él. Por lo
             mismo, desmarcar el tipo no retira nada.
           · El rol pasa a `usuario` porque los módulos solo aplican a ese rol (un
             administrador entra a todo y el bloque ni se muestra). ⚠️ Si ya es
             `administrador` no se toca: marcarle un puesto no debe degradarlo en
             silencio, y para él la sugerencia no significaría nada. */
        var aviso = document.getElementById('modulos-sugeridos-aviso');

        function aplicarSugeridos(cb) {
            var card = cb.closest('.admin-tipo-card');
            var recs = (card && card.dataset.modulos ? card.dataset.modulos : '')
                .split(',').filter(Boolean);
            if (!recs.length) return;

            var rol = document.querySelector('input[name="rol"]:checked');
            if (rol && rol.value === 'administrador') return;

            var radioUsuario = document.querySelector('input[name="rol"][value="usuario"]');
            if (radioUsuario && !radioUsuario.checked) {
                radioUsuario.checked = true;
                // El listener del bloque 1 es quien muestra los módulos y el sub-rol.
                radioUsuario.dispatchEvent(new Event('change', { bubbles: true }));
            }

            var puestos = [];
            recs.forEach(function (m) {
                var chk = document.querySelector('.admin-mod-chip[data-modulo="' + m + '"] input[type="checkbox"]');
                if (chk && !chk.checked) {
                    chk.checked = true;
                    chk.dispatchEvent(new Event('change', { bubbles: true }));
                    puestos.push(m);
                }
            });

            // Se anuncia lo que se acaba de marcar: cambiar casillas sin decirlo hace
            // que el admin no sepa si el formulario le está ayudando o estorbando.
            if (aviso && puestos.length) {
                aviso.textContent = 'Se marcaron ' + puestos.length +
                    (puestos.length === 1 ? ' módulo recomendado' : ' módulos recomendados') +
                    ' para este puesto. Puedes cambiarlos.';
                aviso.hidden = false;
                clearTimeout(aviso._t);
                aviso._t = setTimeout(function () { aviso.hidden = true; }, 6000);
            }
        }

        tipos.forEach(function (cb) {
            cb.addEventListener('change', function () {
                syncExclusividad(); syncSuplir(); syncNiveles();
                // Solo al MARCAR, y después de syncExclusividad(): si el tipo era
                // excluyente, ese ya apagó los demás y no se sugiere sobre un estado
                // que está a punto de cambiar.
                if (cb.checked) aplicarSugeridos(cb);
            });
        });
        syncExclusividad();
        syncSuplir();
        syncNiveles();
    })();
})();
