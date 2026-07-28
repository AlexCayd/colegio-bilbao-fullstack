/* admin-toast — aviso de Alex tras una acción (crear, editar, eliminar…).
   Componente compartido: no lleva guarda de página, se activa por #alexToast.

   Cada vista pinta el markup a partir de los query params (?success, ?deleted…)
   y este módulo se encarga de mostrarlo, cerrarlo y auto-ocultarlo. Antes esta
   misma lógica estaba copiada en siete módulos de página.

   Expone window.cerrarAlexToast porque el botón de cierre usa onclick inline. */
(function () {
    var MS_VISIBLE = 5600;

    var toast = document.getElementById('alexToast');
    if (!toast) return;

    var timer = null;

    function cerrar() {
        clearTimeout(timer);
        toast.style.top = '-160px';
        toast.style.opacity = '0';
        setTimeout(function () { if (toast.parentNode) toast.remove(); }, 400);
    }

    // El doble salto deja que el navegador pinte la posición inicial antes de animar
    requestAnimationFrame(function () {
        setTimeout(function () { toast.classList.add('is-visible'); }, 80);
    });
    timer = setTimeout(cerrar, MS_VISIBLE);

    window.cerrarAlexToast = cerrar;
})();
