/* blog-soporte
   Compone el mensaje de WhatsApp con lo que el usuario escribe.

   El texto se arma en cliente porque el problema lo escribe aquí mismo; del servidor
   solo llegan el nombre y el puesto ya resueltos (data-quien / data-puesto), para no
   tener que traducir el CSV de tipo_personal en JS. */
(function () {
    if (!document.body || document.body.dataset.page !== 'blog-soporte') return;

    var raiz = document.querySelector('[data-sop]');
    var area = document.getElementById('sopProblema');
    var btn  = document.querySelector('[data-sop-enviar]');
    if (!raiz || !area || !btn) return;

    var nota      = document.querySelector('[data-sop-nota]');
    var preview   = document.querySelector('[data-sop-preview]');
    var contador  = document.querySelector('[data-sop-contador]');
    var tel       = raiz.dataset.tel || '';
    var quien     = raiz.dataset.quien || 'un colaborador';
    var puesto    = raiz.dataset.puesto || 'Colaborador';

    function mensaje(problema) {
        return 'Hola, soy ' + quien + ', (' + puesto + ') del Colegio Bilbao. ' +
               'Tengo el siguiente problema en Intranet Bilbao: ' + problema;
    }

    function sync() {
        var txt = area.value.trim();
        if (contador) contador.textContent = String(area.value.length);

        var listo = txt.length > 0;
        btn.classList.toggle('is-off', !listo);
        btn.setAttribute('aria-disabled', listo ? 'false' : 'true');

        if (listo) {
            btn.href = 'https://wa.me/' + tel + '?text=' + encodeURIComponent(mensaje(txt));
            if (nota) nota.textContent = 'Se abrirá WhatsApp con el mensaje listo para enviar';
        } else {
            // Sin problema escrito el enlace no lleva a ningún sitio: el guard del
            // click lo bloquea, pero un href vivo invitaría a pulsarlo igualmente.
            btn.removeAttribute('href');
            if (nota) nota.textContent = 'Escribe tu problema para continuar';
        }
        if (preview) {
            preview.textContent = mensaje(txt || '[describe aquí tu problema]');
        }
    }

    btn.addEventListener('click', function (ev) {
        if (!area.value.trim()) {
            ev.preventDefault();
            area.focus();
            raiz.classList.add('is-falta');
            setTimeout(function () { raiz.classList.remove('is-falta'); }, 600);
        }
    });

    area.addEventListener('input', sync);
    sync();
})();
