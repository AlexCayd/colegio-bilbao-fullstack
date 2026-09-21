/* admin-evento-icono — selector de icono del formulario de eventos.
 *
 * Sin guarda `data-page`: vive en el partial `views/blog/eventos/_form.php`, que
 * comparten crear y editar. Se activa por la existencia de `[data-evento-icono]`,
 * igual que el resto de componentes compartidos del panel.
 *
 * Lo único que hace es mantener honesta la opción «Automático»: como se guarda
 * NULL, el icono real del evento será el del tipo, así que la casilla tiene que
 * enseñar el del tipo que esté elegido en ese momento. Antes de esto mostraba el
 * del tipo con el que se abrió el formulario y mentía en cuanto se cambiaba.
 */
(function () {
    var root = document.querySelector('[data-evento-icono]');
    if (!root) return;

    var form = root.closest('form');
    // ⚠️ El tipo son RADIOS, no un <select>: hay cinco elementos con el mismo
    // `name` y el valor lo tiene el que esté marcado, así que no sirve quedarse
    // con el primero ni escuchar un solo nodo.
    var tipos = form ? form.querySelectorAll('[name="tipo"]') : [];
    var auto  = root.querySelector('[data-ico-auto]');
    if (!tipos.length || !auto) return;

    function tipoActual() {
        for (var i = 0; i < tipos.length; i++) {
            if (tipos[i].checked) return tipos[i].value;
        }
        return '';
    }

    // Los dos mapas llegan como isla de datos en el contenedor: nunca se interpola
    // PHP dentro del JS, y así el catálogo sigue viviendo solo en Evento.
    var iconos = {}, colores = {};
    try { iconos  = JSON.parse(root.dataset.tipoIconos  || '{}'); } catch (e) { iconos  = {}; }
    try { colores = JSON.parse(root.dataset.tipoColores || '{}'); } catch (e) { colores = {}; }

    function sincronizar() {
        var v     = tipoActual();
        var clase = iconos[v];
        if (clase) {
            // Se conserva `fa-solid` y se cambia solo la clase del glifo.
            auto.className = 'fa-solid ' + clase;
            auto.setAttribute('data-ico-auto', '');
        }
        root.style.setProperty('--ev-ico-auto', colores[v] || '#4267ac');
    }

    for (var i = 0; i < tipos.length; i++) {
        tipos[i].addEventListener('change', sincronizar);
    }
    sincronizar();
})();
