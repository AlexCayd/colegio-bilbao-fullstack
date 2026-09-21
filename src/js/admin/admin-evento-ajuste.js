/* admin-evento-ajuste — el interruptor del calendario público se envía solo.
 *
 * Se activa por existencia de `[data-evento-ajuste]`, sin guarda `data-page`.
 *
 * Un formulario de un solo interruptor con su botón «Guardar» al lado obliga a dos
 * gestos para una decisión binaria, y deja la pantalla en un estado ambiguo entre
 * ellos: el interruptor ya se ve encendido pero todavía no lo está. Aquí el cambio
 * ES el envío, y la recarga con su toast confirma lo que quedó guardado.
 *
 * El botón sigue en el HTML para quien llegue sin JS —el POST es el mismo— y se
 * oculta aquí. `.admin-btn` declara su propio `&[hidden]`, así que `hidden = true`
 * lo esconde de verdad: sin esa regla el `inline-flex` ganaría por cascada.
 */
(function () {
    var form = document.querySelector('[data-evento-ajuste]');
    if (!form) return;

    var check = form.querySelector('input[type="checkbox"]');
    var save  = form.querySelector('.ev-ajuste__save');
    if (!check) return;

    if (save) save.hidden = true;
    check.addEventListener('change', function () {
        form.submit();
    });
})();
