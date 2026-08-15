/* Editor del horario de un profesor (módulo Usuarios, solo admin).

   UNA CASILLA = UN BLOQUE. Se pulsa una casilla y se abre el modal; no hay selección
   múltiple, arrastre, shift+clic ni atajo de fila. El alta por lote existía para
   cargar semanas enteras, que es justo lo que hace el importador CSV; aquí lo que se
   hace es corregir una clase suelta, y el lote solo añadía una capa de estado (barra
   sticky, preview del rectángulo) entre el clic y el formulario.

   Qué se puede pulsar:
   - `libre`  → modal en modo CLASE (grupo · materia · aula · acompañantes · color)
   - `receso` → modal en modo GUARDIA (lugar · color). Las guardias de receso viven en
                la misma tabla que las clases, así que ocupan igual y nadie recibe una
                suplencia mientras vigila el patio.
   - un bloque ya existente → modal en modo edición
   Las `ocupada` (clase de otro nivel que pisa esa hora en el reloj) no son botones, y
   el servidor las rechaza igual con Horario::choques(): UI y guard coinciden por
   construcción.

   El tipo NO se elige en el modal: lo decide la casilla. `guardarBloqueHorario()`
   rechaza una clase sobre un receso y una guardia fuera de él, así que un selector
   solo habría servido para provocar ese error. */
(function () {
    if (!document.body || document.body.dataset.page !== 'blog-usuarios-horario') return;

    var tabla = document.querySelector('[data-hed-grid]');
    var modal = document.getElementById('hedModal');
    if (!tabla || !modal) return;

    var elTitle  = modal.querySelector('[data-hed-title]');
    var elChips  = modal.querySelector('[data-hed-chips]');
    var elCeldas = modal.querySelector('[data-hed-celdas]');
    var elId     = modal.querySelector('[data-hed-id]');
    var elForzar = modal.querySelector('[data-hed-forzar]');
    var elGrupo  = modal.querySelector('[data-hed-grupo]');
    var elMat    = modal.querySelector('[data-hed-materia]');
    var elAula   = modal.querySelector('[data-hed-aula]');
    var elColor  = modal.querySelector('[data-hed-color]');
    var swatches = Array.prototype.slice.call(modal.querySelectorAll('[data-hed-colors] .hed-color'));
    var btnDel   = modal.querySelector('[data-hed-del]');
    var delForm  = document.getElementById('hedDelForm');
    var delId    = delForm && delForm.querySelector('[data-hed-del-id]');

    var celdas = Array.prototype.slice.call(tabla.querySelectorAll('[data-hed-cell]'));
    /* Los recesos entran aquí. Que no lo hicieran era lo único que mantenía las
       guardias inalcanzables: el backend, el modal y el SCSS ya estaban completos, pero
       la casilla no recibía ningún listener. */
    var clicables = celdas.filter(function (td) {
        return td.dataset.tipo === 'libre' || td.dataset.tipo === 'receso';
    });

    /** Casilla sobre la que está abierto el modal (para el preview de color). */
    var celdaActiva = null;
    /** Cuántos acompañantes tiene el bloque abierto: lo usa el aviso de borrado. */
    var acompDelBloque = 0;

    function etiqueta(td) {
        var th = td.parentNode.querySelector('.hed-grid__hour');
        var lb = th && th.querySelector('.hed-grid__hour-label');
        var dia = tabla.tHead.rows[0].cells[parseInt(td.dataset.col, 10) + 1];
        return (dia ? dia.textContent.trim() : '') + ' ' + (lb ? lb.textContent.trim() : '');
    }

    /* ── Modal ───────────────────────────────────────────────────────────── */

    function pintarColor(hex) {
        if (elColor) elColor.value = hex || '';
        swatches.forEach(function (s) { s.classList.toggle('is-on', (s.dataset.color || '') === (hex || '')); });
        // Preview en vivo sobre la casilla abierta: el color es lo único del formulario
        // que no se entiende hasta verlo puesto.
        if (celdaActiva) celdaActiva.style.setProperty('--sel-c', hex || '');
    }

    swatches.forEach(function (s) {
        s.addEventListener('click', function () { pintarColor(s.dataset.color || ''); });
    });

    function cerrar() {
        modal.hidden = true;
        if (elForzar) elForzar.value = '';
        if (celdaActiva) {
            celdaActiva.classList.remove('is-abierta');
            celdaActiva.style.removeProperty('--sel-c');
            celdaActiva = null;
        }
    }

    modal.addEventListener('click', function (e) {
        if (e.target === modal || e.target.closest('[data-hed-cancel]')) cerrar();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !modal.hidden) cerrar();
    });

    /* ── Clase o guardia ──
       No es una elección del usuario (ver cabecera): `setTipo` solo escribe el hidden,
       actualiza la insignia y enseña unos campos u otros — una guardia no tiene grupo,
       materia ni aula: tiene lugar. */
    var tipoVal   = modal.querySelector('[data-hed-tipo-val]');
    var tipoIco   = modal.querySelector('[data-hed-tipo-ico]');
    var tipoTxt   = modal.querySelector('[data-hed-tipo-txt]');
    var tipoSub   = modal.querySelector('[data-hed-tipo-sub]');
    var tipoBadge = modal.querySelector('[data-hed-tipo-badge]');
    var lugarWrap = modal.querySelector('[data-hed-lugares-wrap]');
    var lugarVal  = modal.querySelector('[data-hed-lugar-val]');
    var lugarBox  = modal.querySelector('[data-hed-lugares]');
    var modalCard = modal.querySelector('.hed-modal__card');

    function setTipo(tipo) {
        var esG = tipo === 'guardia';
        if (tipoVal) tipoVal.value = esG ? 'guardia' : 'clase';
        if (tipoBadge) tipoBadge.classList.toggle('is-guardia', esG);
        // Una guardia solo pide lugar y color: la tarjeta se estrecha a una columna
        // en vez de dejar media rejilla vacía.
        if (modalCard) modalCard.classList.toggle('is-guardia', esG);
        if (tipoIco) tipoIco.className = esG ? 'fa-solid fa-shield-halved' : 'fa-solid fa-chalkboard';
        if (tipoTxt) tipoTxt.textContent = esG ? 'Guardia' : 'Clase';
        if (tipoSub) tipoSub.textContent = esG ? 'vigilancia durante el receso' : 'en una hora de la jornada';
        if (lugarWrap) lugarWrap.hidden = !esG;
        modal.querySelectorAll('.hed-solo-clase').forEach(function (el) { el.hidden = esG; });
    }

    function setLugar(id) {
        if (lugarVal) lugarVal.value = id || '';
        if (!lugarBox) return;
        lugarBox.querySelectorAll('[data-lugar]').forEach(function (b) {
            b.classList.toggle('is-on', String(b.dataset.lugar) === String(id));
        });
    }

    if (lugarBox) {
        lugarBox.addEventListener('click', function (ev) {
            var b = ev.target.closest('[data-lugar]');
            if (b) setLugar(b.dataset.lugar);
        });
    }

    /* Alta de un lugar sin salir del modal: mandar a otra pantalla obligaría a volver
       a buscar la casilla. */
    (function () {
        var abrir  = modal.querySelector('[data-hed-lugar-nuevo]');
        var caja   = modal.querySelector('[data-hed-lugar-alta]');
        var input  = modal.querySelector('[data-hed-lugar-nombre]');
        var crear  = modal.querySelector('[data-hed-lugar-crear]');
        if (!abrir || !caja || !input || !crear) return;

        abrir.addEventListener('click', function () {
            caja.hidden = !caja.hidden;
            if (!caja.hidden) input.focus();
        });

        function alta() {
            var nombre = input.value.trim();
            if (!nombre) { input.focus(); return; }
            crear.disabled = true;
            var datos = new FormData();
            datos.append('nombre', nombre);
            fetch('/dashboard/usuarios/horario/lugar', {
                method: 'POST', body: datos,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    crear.disabled = false;
                    if (d.error) { input.setCustomValidity(d.error); input.reportValidity(); return; }
                    if (!lugarBox.querySelector('[data-lugar="' + d.id + '"]')) {
                        var b = document.createElement('button');
                        b.type = 'button';
                        b.className = 'hed-lugar';
                        b.dataset.lugar = d.id;
                        b.textContent = d.nombre;
                        lugarBox.insertBefore(b, abrir);
                    }
                    setLugar(d.id);
                    input.value = '';
                    caja.hidden = true;
                })
                .catch(function () { crear.disabled = false; });
        }

        crear.addEventListener('click', alta);
        input.addEventListener('input', function () { input.setCustomValidity(''); });
        input.addEventListener('keydown', function (ev) {
            // Enter dentro del modal enviaría el formulario del bloque entero.
            if (ev.key === 'Enter') { ev.preventDefault(); alta(); }
        });
    })();

    /* ── Acompañantes (coteaching) ──
       El picker compartido (admin-picker.js) hace el trabajo; aquí solo se le pide
       vaciar o precargar los chips al abrir el modal. */
    var pickerAcomp = modal.querySelector('[data-picker][data-picker-multi]');
    function setAcomp(lista) {
        if (!pickerAcomp || !window.AdminPicker) return;
        window.AdminPicker.set(pickerAcomp, lista || []);
    }

    /** Escribe la única casilla del bloque en el formulario. */
    function ponerCelda(td) {
        if (!elCeldas) return;
        elCeldas.innerHTML = '';
        var i = document.createElement('input');
        i.type = 'hidden';
        i.name = 'celdas[]';
        i.value = td.dataset.dia + ':' + td.dataset.periodo;
        elCeldas.appendChild(i);
    }

    function marcarAbierta(td) {
        celdaActiva = td || null;
        if (celdaActiva) celdaActiva.classList.add('is-abierta');
    }

    /** Abre en modo crear sobre la casilla pulsada. */
    function abrirNuevo(td) {
        if (!td) return;
        var esReceso = td.dataset.tipo === 'receso';

        if (elId) elId.value = '';
        if (btnDel) btnDel.hidden = true;
        if (elTitle) elTitle.textContent = esReceso ? 'Nueva guardia' : 'Nueva clase';
        if (elChips) elChips.textContent = etiqueta(td);
        ponerCelda(td);
        if (elGrupo) elGrupo.value = '0';
        if (elMat)   elMat.value   = '0';
        if (elAula)  elAula.value  = '0';
        setAcomp([]);
        acompDelBloque = 0;
        marcarAbierta(td);
        pintarColor('');
        setTipo(esReceso ? 'guardia' : 'clase');
        setLugar('');
        modal.hidden = false;
        // En una guardia el grupo está oculto: se enfoca el primer lugar disponible.
        var foco = esReceso
            ? (lugarBox && lugarBox.querySelector('[data-lugar]'))
            : elGrupo;
        if (foco) foco.focus();
    }

    /** Abre en modo edición sobre un bloque existente. */
    function abrirEdicion(btn) {
        var td = btn.closest('[data-hed-cell]');
        var esG = btn.dataset.tipoBloque === 'guardia';
        if (elId) elId.value = btn.dataset.id || '';
        if (btnDel) btnDel.hidden = false;
        if (elTitle) elTitle.textContent = esG ? 'Editar guardia' : 'Editar clase';
        if (elChips) elChips.textContent = btn.dataset.donde || '';
        if (td) ponerCelda(td);
        if (elGrupo) elGrupo.value = btn.dataset.grupo || '0';
        if (elMat)   elMat.value   = btn.dataset.materia || '0';
        if (elAula)  elAula.value  = btn.dataset.aula || '0';
        var acs = [];
        try { acs = JSON.parse(btn.dataset.acomp || '[]'); } catch (e) { acs = []; }
        setAcomp(acs);
        // Lo lee el aviso de borrado: los acompañantes se van con el bloque.
        acompDelBloque = acs.length;
        marcarAbierta(td);
        pintarColor(btn.dataset.color || '');
        setTipo(esG ? 'guardia' : 'clase');
        setLugar(btn.dataset.lugar && btn.dataset.lugar !== '0' ? btn.dataset.lugar : '');
        if (delId) delId.value = btn.dataset.id || '';
        modal.hidden = false;
    }

    /* ── Enganche de la rejilla ── */
    clicables.forEach(function (td) {
        var btn = td.querySelector('[data-hed-pick]');
        if (btn) btn.addEventListener('click', function () { abrirNuevo(td); });
    });

    tabla.querySelectorAll('[data-hed-edit]').forEach(function (b) {
        b.addEventListener('click', function () { abrirEdicion(b); });
    });

    if (btnDel && delForm) {
        btnDel.addEventListener('click', function () {
            // El aviso cuenta los acompañantes porque se van con el bloque: un bloque
            // sin titular sería una fila huérfana que nadie puede editar.
            var msg = acompDelBloque
                ? '¿Eliminar este bloque? También se quitará a ' + acompDelBloque
                    + ' acompañante' + (acompDelBloque === 1 ? '' : 's') + '.'
                : '¿Eliminar este bloque del horario?';
            if (window.confirm(msg)) delForm.submit();
        });
    }

    /* ── Reapertura tras un error o un aviso del servidor ─────────────────
       El servidor rebota con redirect (no hay endpoints JSON de escritura en el
       panel), así que el formulario se pierde: se reconstruye desde la isla JSON
       para no obligar a rellenarlo otra vez. */
    (function () {
        var isla = document.getElementById('hedReabrir');
        if (!isla) return;
        var d;
        try { d = JSON.parse(isla.textContent); } catch (e) { return; }

        var clave = String((d.celdas || [])[0] || '').split(':');
        var td = clicables.filter(function (x) {
            return x.dataset.dia === clave[0] && x.dataset.periodo === clave[1];
        })[0];

        if (d.id) {
            var btn = tabla.querySelector('[data-hed-edit][data-id="' + d.id + '"]');
            if (btn) abrirEdicion(btn);
        } else if (td) {
            td.classList.add('is-error');
            abrirNuevo(td);
        }

        // Después de abrir, porque abrirNuevo/abrirEdicion resetean los campos.
        setTipo(d.tipo === 'guardia' ? 'guardia' : 'clase');
        setLugar(d.lugar || '');
        if (elGrupo && d.grupo) elGrupo.value = d.grupo;
        if (elMat   && d.materia) elMat.value = d.materia;
        if (elAula  && d.aula) elAula.value = d.aula;
        setAcomp(d.acomp || []);
        pintarColor(d.color || '');

        // Solo se pide confirmar cuando lo único pendiente eran avisos de aula: el
        // reenvío los da por leídos y guarda.
        if (d.forzar && elForzar) {
            elForzar.value = '1';
            var save = modal.querySelector('[data-hed-save]');
            if (save) save.innerHTML = '<i class="fa-solid fa-check"></i> Guardar de todos modos';
        }
    })();
})();
