/* admin-picker — buscador de personas con autocompletado.

   Componente compartido: no lleva guarda de página, se activa por la existencia de
   `[data-picker]`. Salió de blog-suplencias-_form.js, que lo tenía atado a un solo
   endpoint; ahora lo usan tres sitios (ausente/suplente en Suplencias, acompañantes
   en el editor de horario, y el compañero de un intercambio) y cada uno declara el
   suyo, porque los guards del servidor no son los mismos.

   Contrato del HTML:
     [data-picker]                  raíz
     [data-picker-input]            caja de texto
     [data-picker-results]          donde se pintan las coincidencias
     data-picker-endpoint="/…"      opcional; por defecto el de Suplencias
     data-picker-exclude="12"       opcional; id que el endpoint debe omitir

   Modo simple (por defecto):
     [data-picker-value]            hidden con el id elegido
     [data-picker-clear]            botón de limpiar (opcional)

   Modo múltiple (`data-picker-multi`):
     [data-picker-chips]            donde se pintan los elegidos
     data-picker-name="acompanantes"  → un hidden `acompanantes[]` por elegido
     data-picker-max="2"            opcional; tope de elegidos

   ⚠️ El hidden del modo simple se escribe por propiedad, así que emite `change` a
   mano: otras vistas (crear suplencia, crear swap) reaccionan a ese evento para
   recargar el horario del elegido. Asignar `.value` no dispara nada por sí solo. */
(function () {
    var ENDPOINT_DEFECTO = '/dashboard/suplencias/buscar-colaboradores';

    /* ── Recorte de los ancestros ──────────────────────────────────────────────
       `.picker__results` es `position:absolute`, así que un ancestro con
       `overflow: clip` lo recorta POR GEOMETRÍA y el `z-index: 40` no puede con eso.
       Dentro de `.swp-panel` (que es `overflow: clip`) el desplegable caía entero
       fuera del panel y parecía que el buscador no devolvía nada.

       Mismo patrón, y por el mismo motivo, que `admin-datepicker.js`: se levanta el
       recorte de TODOS los ancestros que recorten, no solo del más cercano.

       El contador por nodo es necesario porque dos pickers pueden compartir ancestro
       (ausente y suplente en Suplencias): sin él, el `blur` diferido del primero
       borraba la clase que el segundo acababa de poner. */
    var RECORTAN = '.admin-panel, .admin-form-section, .admin-form-row, .swp-panel';

    function marcar(n) {
        n._pickersAbiertos = (n._pickersAbiertos || 0) + 1;
        n.classList.add('has-picker-open');
    }

    function desmarcar(n) {
        n._pickersAbiertos = (n._pickersAbiertos || 1) - 1;
        if (n._pickersAbiertos <= 0) {
            n._pickersAbiertos = 0;
            n.classList.remove('has-picker-open');
        }
    }

    function esc(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function montar(root) {
        var input    = root.querySelector('[data-picker-input]');
        var results  = root.querySelector('[data-picker-results]');
        if (!input || !results) return;

        var multi    = root.hasAttribute('data-picker-multi');
        var hidden   = root.querySelector('[data-picker-value]');
        var clear    = root.querySelector('[data-picker-clear]');
        var chipsBox = root.querySelector('[data-picker-chips]');
        var campo    = root.dataset.pickerName || 'seleccion';
        var tope     = parseInt(root.dataset.pickerMax || '0', 10) || 0;
        var endpoint = root.dataset.pickerEndpoint || ENDPOINT_DEFECTO;
        var excluir  = root.dataset.pickerExclude || '';

        var timer = null, items = [], active = -1;
        var elegidos = [];   // solo en modo múltiple: [{id, nombre}]
        var abierto = false, recortadores = [];

        // `render()` corre en cada pulsación y en cada flecha, así que el desrecorte
        // va detrás de una guarda: si no, el contador por nodo subiría sin bajar.
        function abrirLista() {
            results.classList.add('is-open');
            if (abierto) return;
            abierto = true;
            for (var n = root.parentElement; n && n !== document.body; n = n.parentElement) {
                if (n.matches && n.matches(RECORTAN)) { marcar(n); recortadores.push(n); }
            }
        }

        function close() {
            results.classList.remove('is-open');
            active = -1;
            if (!abierto) return;
            abierto = false;
            recortadores.forEach(desmarcar);
            recortadores = [];
        }

        function render() {
            if (!items.length) {
                results.innerHTML = '<div class="picker__empty">Sin coincidencias</div>';
                abrirLista();
                return;
            }
            results.innerHTML = items.map(function (u, i) {
                var ava = u.avatar
                    ? '<img src="' + esc(u.avatar) + '" alt="">'
                    : esc((u.nombre || '?').charAt(0).toUpperCase());
                return '<div class="picker__item' + (i === active ? ' is-active' : '') + '" data-i="' + i + '">' +
                    '<span class="picker__ava">' + ava + '</span>' +
                    '<span class="picker__name">' + esc(u.nombre) + '</span></div>';
            }).join('');
            abrirLista();
            results.querySelectorAll('.picker__item').forEach(function (el) {
                el.addEventListener('mousedown', function (e) {
                    e.preventDefault();
                    choose(items[+el.dataset.i]);
                });
            });
        }

        function setValor(v) {
            if (!hidden || hidden.value === String(v)) return;
            hidden.value = v;
            hidden.dispatchEvent(new Event('change', { bubbles: true }));
        }

        /* ── Modo múltiple ── */
        function pintarChips() {
            if (!chipsBox) return;
            chipsBox.innerHTML = elegidos.map(function (u) {
                return '<span class="picker-chip" data-id="' + esc(u.id) + '">' +
                    '<input type="hidden" name="' + esc(campo) + '[]" value="' + esc(u.id) + '">' +
                    esc(u.nombre) +
                    '<button type="button" class="picker-chip__x" data-picker-quitar="' + esc(u.id) + '"' +
                    ' aria-label="Quitar a ' + esc(u.nombre) + '"><i class="fa-solid fa-xmark"></i></button>' +
                    '</span>';
            }).join('');
            // El tope se aplica ocultando la caja, no rechazando en silencio: si no,
            // se teclea un nombre, no pasa nada y parece que el buscador está roto.
            if (tope) {
                input.disabled = elegidos.length >= tope;
                input.placeholder = input.disabled
                    ? 'Máximo ' + tope + ' acompañantes'
                    : (input.dataset.ph || input.placeholder);
            }
        }

        function quitar(id) {
            elegidos = elegidos.filter(function (u) { return String(u.id) !== String(id); });
            pintarChips();
        }

        if (chipsBox) {
            chipsBox.addEventListener('click', function (e) {
                var b = e.target.closest('[data-picker-quitar]');
                if (b) quitar(b.dataset.pickerQuitar);
            });
        }

        function choose(u) {
            if (!u) return;
            if (multi) {
                if (tope && elegidos.length >= tope) return;
                if (!elegidos.some(function (x) { return String(x.id) === String(u.id); })) {
                    elegidos.push({ id: u.id, nombre: u.nombre });
                    pintarChips();
                }
                input.value = '';          // listo para el siguiente
            } else {
                input.value = u.nombre;
                setValor(u.id);
                root.classList.add('has-value');
            }
            close();
        }

        function buscar(q) {
            var url = endpoint + (endpoint.indexOf('?') === -1 ? '?' : '&') + 'q=' + encodeURIComponent(q);
            if (excluir) url += '&excluir=' + encodeURIComponent(excluir);
            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    items = Array.isArray(data) ? data : (data && data.resultados) || [];
                    // En múltiple, quien ya está elegido no vuelve a ofrecerse.
                    if (multi) {
                        items = items.filter(function (u) {
                            return !elegidos.some(function (x) { return String(x.id) === String(u.id); });
                        });
                    }
                    active = -1;
                    render();
                })
                .catch(function () { items = []; render(); });
        }

        input.addEventListener('input', function () {
            if (!multi) {
                setValor('');            // al reescribir se invalida la selección previa
                root.classList.toggle('has-value', this.value.trim() !== '');
            }
            var q = this.value.trim();
            clearTimeout(timer);
            if (q.length < 2) { close(); return; }
            timer = setTimeout(function () { buscar(q); }, 180);
        });

        input.addEventListener('keydown', function (e) {
            if (!results.classList.contains('is-open')) return;
            if (e.key === 'ArrowDown') { e.preventDefault(); active = Math.min(active + 1, items.length - 1); render(); }
            else if (e.key === 'ArrowUp') { e.preventDefault(); active = Math.max(active - 1, 0); render(); }
            else if (e.key === 'Enter') { if (active >= 0) { e.preventDefault(); choose(items[active]); } }
            else if (e.key === 'Escape') { close(); }
        });

        input.addEventListener('blur', function () { setTimeout(close, 150); });

        if (clear) {
            clear.addEventListener('click', function () {
                input.value = '';
                setValor('');
                root.classList.remove('has-value');
                input.focus();
            });
        }

        if (input.placeholder) input.dataset.ph = input.placeholder;

        // API por raíz, para que quien abre un modal pueda precargar o vaciar.
        root._picker = {
            set: function (lista) {
                if (multi) {
                    elegidos = (lista || []).map(function (u) { return { id: u.id, nombre: u.nombre }; });
                    input.value = '';
                    pintarChips();
                } else {
                    var u = (lista || [])[0];
                    input.value = u ? u.nombre : '';
                    setValor(u ? u.id : '');
                    root.classList.toggle('has-value', !!u);
                }
                close();
            }
        };
        if (multi) pintarChips();
    }

    document.querySelectorAll('[data-picker]').forEach(montar);

    window.AdminPicker = {
        /** Precarga (o vacía) un picker. `lista` = [{id, nombre}]. */
        set: function (root, lista) {
            if (root && root._picker) root._picker.set(lista);
        },
        /** Monta un picker añadido al DOM después de la carga. */
        montar: montar
    };
})();
