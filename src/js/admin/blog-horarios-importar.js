/* blog-horarios-importar — la vista previa del CSV de horarios.

   Hace dos cosas: llevar el RECORRIDO del asistente (qué paso se ve, cuánto queda, qué
   falta para poder importar) y filtrar la tabla de filas.

   ── Por qué asistente ──
   La previa volcaba de golpe el bloque de impacto, las tarjetas de cifra, la tabla de
   catálogo, hasta doce listas de nombres y las 878 filas del archivo sin paginar. Todo
   competía por la misma pantalla, así que lo irreversible se leía al mismo peso que lo
   anecdótico. El patrón es el mismo del alta de swap (`blog-swaps-crear.js`).

   ⚠️ DIVERGENCIA respecto a aquel: aquí se puede saltar a CUALQUIER paso, también hacia
   adelante. Allí cada paso recoge un dato que el siguiente necesita; aquí todos son de
   lectura y el único requisito es la casilla del último — que además es `required`, así
   que el navegador lo frena aunque este archivo no cargue. Bloquear el avance obligaría
   a pulsar «Siguiente» cuatro veces para reimportar un archivo ya revisado. Lo que hace
   seguro ese salto vive en la vista: el paso «Confirmar» repite todas las cifras.

   ⚠️ Sin este archivo la pantalla SIGUE SIRVIENDO, y el reparto es de tres piezas:
     · el servidor NO emite `hidden` en los paneles ni en los botones de recorrido, así
       que sin JS se pintan los cinco seguidos y se llega a «Reemplazar»;
     · `html.js .hoi-wiz:not(.is-wiz)` los pliega antes del primer pintado —la clase la
       estampa el `<head>` síncrono de `layout-admin.php`—, así que no hay flash;
     · en cuanto este módulo monta añade `.is-wiz` y a partir de ahí manda `hidden`,
       como en el resto del panel.
   Y el freno de verdad no está aquí: la casilla es `required` y el guard vive en
   `importarHorarios()` (`$_POST['confirmo']`).

   ⚠️ Este módulo va concatenado con los demás en `admin.min.js`: una excepción aquí
   detiene el archivo entero y se lleva por delante los módulos posteriores. De ahí los
   guards de existencia en vez de dar nada por hecho. */
(function () {
    if (!document.body || document.body.dataset.page !== 'blog-horarios-importar') return;

    /* ── Filtros de la tabla de filas ────────────────────────────────────────────
       Tres criterios sobre las MISMAS filas: el texto que se busca, la pill activa y
       —hasta ahora la única— la casilla de problemas.

       ⚠️ Los tres se guardan en estado de módulo y los aplica UNA sola función. Si
       cada uno tocara `is-filtered` por su cuenta, el segundo desharía al primero: es
       exactamente el problema que ya tuvo la agenda de suplencias con su buscador y su
       calendario (`blog-suplencias-index.js`).

       ⚠️ Y se marca con `is-filtered`, NUNCA con `tr.hidden`. La tabla está paginada
       (`data-table-per`), y una fila escondida por su cuenta seguiría contando para el
       paginador: se verían páginas medio vacías y el «N de M» mentiría. Quien recuenta
       es `AdminTable.refrescar()`, que reparte `data-pager-item` según la clase. */
    (function filtros() {
        var tabla = document.querySelector('.hoi-table');
        if (!tabla) return;

        var filas  = Array.prototype.slice.call(tabla.querySelectorAll('.hoi-row'));
        var input  = document.querySelector('[data-hoi-buscar]');
        var limpiar = document.querySelector('[data-hoi-limpiar]');
        var pills  = Array.prototype.slice.call(document.querySelectorAll('[data-hoi-pill]'));
        var cuenta = document.querySelector('[data-hoi-cuenta]');
        var vacio  = document.querySelector('[data-hoi-empty]');
        if (!filas.length) return;

        var TOTAL = filas.length;
        var terminos = [];      // búsqueda, ya normalizada y troceada
        var pill = '';          // clave de la pill activa, o '' si ninguna

        /* Quita acentos para que «Fernandez» encuentre a «Fernández». El heno lo compone
           PHP en `data-buscar` y ya viene en minúsculas, igual que en el resto del panel.
           ⚠️ Con guarda de nulo: `normalize` revienta con undefined. */
        function normalizar(s) {
            return String(s == null ? '' : s).normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
        }

        /** ¿Pasa esta fila la pill activa? Las marcas las emite el servidor. */
        function pasaPill(tr) {
            if (!pill) return true;
            if (pill === 'problemas') return tr.dataset.estado !== 'ok';
            return tr.hasAttribute('data-' + pill);
        }

        function aplicarFiltros() {
            var visibles = 0;

            filas.forEach(function (tr) {
                var heno = normalizar(tr.dataset.buscar);
                var okTexto = !terminos.length || terminos.every(function (t) { return heno.indexOf(t) !== -1; });
                var match = okTexto && pasaPill(tr);
                tr.classList.toggle('is-filtered', !match);
                if (match) visibles++;
            });

            var filtrando = terminos.length > 0 || pill !== '';
            if (cuenta) {
                /* ⚠️ Primero el texto y DESPUÉS `hidden`, no al revés. Es un
                   `role="status"`: los lectores de pantalla anuncian lo que APARECE
                   dentro de una región viva, así que poblarla mientras está oculta y
                   destaparla después es lo que dispara el aviso; al revés —destapar
                   vacía y escribir en el mismo tic— hay AT que se pierden el primer
                   cambio, justo el que dice que la búsqueda ya no enseña las 878.
                   Y se oculta cuando no hay nada que decir (también si el filtro deja
                   las filas intactas), o quedaría un hueco vivo sin contenido. */
                var txt = (filtrando && visibles !== TOTAL)
                    ? visibles + ' de ' + TOTAL + ' filas'
                    : '';
                cuenta.textContent = txt;
                cuenta.hidden = txt === '';
            }
            // Filtrar hasta cero es un resultado legítimo; una tabla vacía sin
            // explicación se lee como que algo se rompió.
            if (vacio) vacio.hidden = visibles !== 0;
            if (limpiar) limpiar.hidden = !input || input.value === '';

            if (window.AdminTable) window.AdminTable.refrescar(tabla);
        }

        // Sin antirrebote, como el resto de buscadores del panel: no hay `fetch`
        // detrás, solo una pasada sobre un array ya en memoria.
        var leerTexto = function () {
            terminos = normalizar(input.value).split(/\s+/).filter(Boolean);
            aplicarFiltros();
        };
        if (input) {
            input.addEventListener('input', leerTexto);
            input.addEventListener('search', leerTexto);   // la «x» nativa de type="search"
        }
        if (limpiar && input) {
            limpiar.addEventListener('click', function () {
                input.value = '';
                terminos = [];
                aplicarFiltros();
                input.focus();
            });
        }

        // Toggle, no radio: volver a pulsar la pill activa quita el filtro. Una sola a
        // la vez porque componerlas («acompaña Y dividida») son cero filas casi siempre,
        // y un filtro que no devuelve nada se lee como roto.
        pills.forEach(function (b) {
            b.addEventListener('click', function () {
                pill = pill === b.dataset.hoiPill ? '' : b.dataset.hoiPill;
                pills.forEach(function (o) {
                    var on = o.dataset.hoiPill === pill;
                    o.classList.toggle('is-on', on);
                    o.setAttribute('aria-pressed', on ? 'true' : 'false');
                });
                aplicarFiltros();
            });
        });
    })();

    /* ── Asistente ───────────────────────────────────────────────────────────── */
    var form = document.querySelector('[data-hoi-wiz]');
    if (!form) return;                       // paso 1 (subir): no hay asistente

    var paneles = Array.prototype.slice.call(form.querySelectorAll('[data-hoi-panel]'));
    var pasos   = Array.prototype.slice.call(form.querySelectorAll('[data-hoi-indice] .hoi-wiz__paso'));
    var alexes  = Array.prototype.slice.call(form.querySelectorAll('[data-alex-paso]'));
    var TOTAL   = paneles.length;
    if (!TOTAL) return;

    var elDice   = form.querySelector('[data-hoi-dice]');
    var elActual = form.querySelector('[data-hoi-actual]');
    var elRest   = form.querySelector('[data-hoi-restante]');
    var elFill   = form.querySelector('[data-hoi-fill]');
    var elHint   = form.querySelector('[data-hoi-hint]');
    var btnAtras = form.querySelector('[data-hoi-atras]');
    var btnSig   = form.querySelector('[data-hoi-siguiente]');
    var btnEnv   = form.querySelector('[data-hoi-enviar]');
    var confirmo = form.querySelector('[data-hoi-confirmo]');

    // Las frases viajan en el HTML (las emite PHP junto al catálogo de pasos), no
    // duplicadas aquí: el catálogo es uno solo y vive en la vista.
    var DICE = alexes.map(function (img) { return img.dataset.dice || ''; });

    var actual = 0;
    var vistos = [0];

    /** Qué impide terminar. '' = nada. Solo el último paso pide algo. */
    function falta() {
        if (actual !== TOTAL - 1) return '';
        if (!confirmo) return '';            // archivo sin ninguna fila válida: ya lo dice el panel
        return confirmo.checked ? '' : 'Marca la casilla de confirmación para poder importar.';
    }

    /**
     * Único punto que decide qué se ve: panel activo, índice, barra, Alex, botones y
     * aviso. Se llama tras cualquier cambio de paso o de la casilla.
     */
    function sincronizar() {
        paneles.forEach(function (p, i) { p.hidden = i !== actual; });

        pasos.forEach(function (b, i) {
            var esActual = i === actual;
            b.classList.toggle('is-actual', esActual);
            // «Visto», no «hecho»: aquí no se resuelve nada, se revisa. Marcar en verde
            // un paso al que no se ha llegado mentiría sobre lo que se ha leído.
            b.classList.toggle('is-visto', !esActual && vistos.indexOf(i) !== -1);
            if (esActual) b.setAttribute('aria-current', 'step');
            else b.removeAttribute('aria-current');
        });

        alexes.forEach(function (img, i) { img.hidden = i !== actual; });
        if (elDice && DICE[actual]) elDice.textContent = DICE[actual];

        if (elActual) elActual.textContent = actual + 1;
        if (elFill)   elFill.style.width = Math.round(((actual + 1) / TOTAL) * 100) + '%';
        if (elRest) {
            var quedan = TOTAL - actual - 1;
            elRest.textContent = quedan === 0 ? 'Último paso'
                : (quedan === 1 ? 'Queda 1 paso' : 'Quedan ' + quedan + ' pasos');
        }

        var ultimo = actual === TOTAL - 1;
        if (btnAtras) btnAtras.hidden = actual === 0;
        if (btnSig)   btnSig.hidden = ultimo;
        if (btnEnv)   btnEnv.hidden = !ultimo;

        // El aviso se ve ANTES de pulsar, no como castigo después. Ningún botón sale
        // deshabilitado: uno muerto sin explicación no se distingue de uno roto.
        var f = falta();
        if (elHint) {
            elHint.textContent = f;
            elHint.hidden = f === '';
        }
    }

    /** Va a un paso concreto. Sin requisitos: todos son de lectura (ver la cabecera). */
    function irA(i) {
        actual = Math.min(TOTAL - 1, Math.max(0, i));
        if (vistos.indexOf(actual) === -1) vistos.push(actual);
        sincronizar();
        form.scrollIntoView({ block: 'start', behavior: 'smooth' });

        /* ⚠️ El foco tiene que MUDARSE, no quedarse donde estaba: el botón que acaba de
           pulsarse suele desaparecer en la misma llamada —el «Ver» de un hallazgo vive
           dentro del panel que se oculta, y «Siguiente»/«Atrás» se esconden al llegar al
           extremo—, así que se quedaba colgado en un elemento `hidden` y el navegador lo
           devolvía al `<body>`: con teclado se perdía el sitio, y un lector de pantalla
           no se enteraba de que había cambiado de paso.
           `preventScroll` para no pelearse con el desplazamiento suave de arriba, y el
           panel lleva `tabindex="-1"` desde la vista. */
        var panel = paneles[actual];
        if (panel && typeof panel.focus === 'function') {
            try { panel.focus({ preventScroll: true }); } catch (e) { panel.focus(); }
        }
    }

    pasos.forEach(function (b) {
        b.addEventListener('click', function () { irA(parseInt(b.dataset.indice, 10) || 0); });
    });

    /* Los hallazgos del último paso llevan al paso donde se ven. Nacen `hidden` y los
       enseña este módulo: sin JS los paneles se pintan seguidos, así que no hay ningún
       recorrido que saltar y el botón no tendría a dónde llevar. */
    Array.prototype.forEach.call(form.querySelectorAll('[data-hoi-ir]'), function (b) {
        b.hidden = false;
        b.addEventListener('click', function () { irA(parseInt(b.dataset.hoiIr, 10) || 0); });
    });
    if (btnSig)   btnSig.addEventListener('click', function () { irA(actual + 1); });
    if (btnAtras) btnAtras.addEventListener('click', function () { irA(actual - 1); });
    if (confirmo) confirmo.addEventListener('change', sincronizar);

    /* Si el navegador rechaza el envío por el `required` de la casilla —pasa cuando se
       llega al botón sin marcarla—, el foco cae en un control que puede estar en otro
       paso. Llevar la vista a donde está el freno evita el «no pasa nada» mudo. */
    form.addEventListener('invalid', function (e) {
        if (e.target === confirmo) irA(TOTAL - 1);
    }, true);

    /* Releva a la regla de arranque: a partir de aquí el plegado lo lleva `hidden`.
       Va ANTES de la primera `sincronizar()` para que no quede un fotograma con los
       dos criterios en desacuerdo. */
    form.classList.add('is-wiz');
    sincronizar();
})();
