/* admin-recuperar — confirmación de correo en la solicitud de restablecimiento.

   Se activa por existencia de `[data-recuperar]` y no por `data-page`: la vista comparte
   `blog-login` con el propio login (misma superficie, mismo SCSS), así que una guarda de
   página se dispararía también ahí.

   Es AYUDA, no validación: la comparación de verdad está en el servidor
   (BlogController::recuperarPassword()). Sin JS el formulario sigue siendo correcto —
   simplemente el aviso de "no coinciden" llega tras enviar en vez de al escribir. */
(function () {
    var form = document.querySelector('[data-recuperar]');
    if (!form) return;

    var email   = form.querySelector('#email');
    var confirm = form.querySelector('#email_confirm');
    var msg     = form.querySelector('[data-recuperar-msg]');
    if (!email || !confirm || !msg) return;

    function iguales() {
        // Insensible a mayúsculas, igual que el servidor: nadie escribe su correo con la
        // misma capitalización dos veces seguidas y rechazarlo por eso sería absurdo.
        return email.value.trim().toLowerCase() === confirm.value.trim().toLowerCase();
    }

    function revisar() {
        // Con el segundo campo vacío todavía no hay nada que decir: avisar mientras se
        // escribe la primera letra es ruido, no ayuda.
        if (confirm.value.trim() === '') {
            msg.hidden = true;
            confirm.setCustomValidity('');
            return;
        }

        if (iguales()) {
            msg.hidden = false;
            msg.classList.add('is-ok');
            msg.innerHTML = '<i class="fa-solid fa-check"></i> Los correos coinciden.';
            confirm.setCustomValidity('');
        } else {
            msg.hidden = false;
            msg.classList.remove('is-ok');
            msg.innerHTML = '<i class="fa-solid fa-circle-exclamation"></i> Los dos correos no coinciden.';
            confirm.setCustomValidity('Los dos correos no coinciden');
        }
    }

    email.addEventListener('input', revisar);
    confirm.addEventListener('input', revisar);
})();
