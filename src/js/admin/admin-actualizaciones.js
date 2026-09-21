/* admin-actualizaciones — la puerta bloqueante de novedades (views/blog/_actualizaciones-modal.php).

   Se activa por EXISTENCIA del modal, no por `data-page`: se interpone en cualquier
   pantalla del panel, así que una guarda de página lo apagaría en todas menos una.

   Lo único que hace es pasar de un anuncio al siguiente sin recargar cuando hay varios.
   Todo lo demás —el bloqueo, el acuse, el registro por persona— vive en el servidor: sin
   JS el modal sigue funcionando (los N anuncios se ven seguidos y cada uno tiene su
   formulario), solo que cada "Entendido" recarga la página.

   ⚠️ Deliberadamente NO cierra con Escape ni pulsando el fondo. Es la diferencia entre
   esto y una notificación: un cambio de funcionamiento que nadie ha leído acaba en
   tickets de soporte. */
(function () {
    var gate = document.getElementById('actGate');
    if (!gate) return;

    var items = Array.prototype.slice.call(gate.querySelectorAll('[data-act-item]'));
    if (items.length < 2) return;   // con uno solo el POST del botón basta

    var contador = gate.querySelector('[data-act-contador]');
    var actual   = 0;

    function pintar() {
        items.forEach(function (el, i) {
            if (i === actual) el.removeAttribute('data-act-oculto');
            else el.setAttribute('data-act-oculto', '');
        });
        if (contador) contador.textContent = (actual + 1) + ' de ' + items.length;
        // El foco viaja con el anuncio activo: sin esto, con teclado se seguiría
        // tabulando por los botones de los que ya no se ven.
        var btn = items[actual].querySelector('[data-act-ok]');
        if (btn) btn.focus();
    }

    items.forEach(function (item, i) {
        var form = item.querySelector('form');
        if (!form) return;

        form.addEventListener('submit', function (e) {
            // El último sí va al servidor: es el que devuelve al usuario a su pantalla.
            if (i === items.length - 1) return;

            e.preventDefault();

            /* Los intermedios se acusan en segundo plano y se avanza en el sitio. Si el
               fetch falla no se bloquea el avance: el anuncio se quedará pendiente y
               volverá a salir en la siguiente carga, que es el fallo correcto —mejor
               repetir un aviso que dejar a alguien encerrado en el modal. */
            var datos = new FormData(form);
            fetch(form.action, { method: 'POST', body: datos, keepalive: true })
                .catch(function () { /* ver comentario */ });

            actual = Math.min(actual + 1, items.length - 1);
            pintar();
        });
    });

    /* La trampa de foco: mientras la puerta esté abierta, el resto del panel no debe ser
       alcanzable ni con Tab ni con lector de pantalla. `inert` resuelve las dos cosas de
       un golpe, igual que hace el cajón del sidebar. */
    var layout = document.querySelector('.admin-layout');
    if (layout && 'inert' in HTMLElement.prototype) layout.inert = true;

    pintar();
})();
