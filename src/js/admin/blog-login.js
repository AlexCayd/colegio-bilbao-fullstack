/* blog-login — toggle de contraseña + fondo del bosque.
   Migrado desde el <script> embebido de views/blog/login.php */
(function () {
    if (!document.body || document.body.dataset.page !== 'blog-login') return;

    /* Mismo bosque de la landing (src/js/public/forest.js, incluido también en el
       bundle admin). Se fija la paleta oscura: el panel no tiene selector de tema,
       y sobre el bosque oscuro el cristal del formulario se lee mucho mejor.
       Three.js llega por CDN, así que se espera a que exista; si no carga o no hay
       WebGL, init() devuelve null y queda el gradiente de respaldo del SCSS. */
    (function () {
        var canvas = document.getElementById('forest-canvas');
        if (!canvas) return;

        var intentos = 0;
        (function esperarThree() {
            if (window.THREE && window.BilbaoForest) {
                window.BilbaoForest.init(canvas, { scroll: false, dark: true });
                return;
            }
            if (++intentos > 100) return;   // ~8 s y nos rendimos
            setTimeout(esperarThree, 80);
        })();
    })();

    /* Reacción al teclear: el haz bajo el campo pulsa con cada pulsación. El estado
       de foco lo lleva el CSS solo; esto solo añade el latido, que necesita saber
       cuándo paras de escribir. Se reinicia la animación forzando un reflow, si no
       una segunda tecla dentro de los 450 ms no la relanzaría. */
    (function () {
        var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (reduce) return;

        document.querySelectorAll('.admin-login__card .admin-form__input').forEach(function (input) {
            var wrap = input.closest('.admin-form__input-wrapper');
            if (!wrap) return;
            var t;

            input.addEventListener('input', function () {
                wrap.classList.remove('is-typing');
                void wrap.offsetWidth;             // reinicia la animación
                wrap.classList.add('is-typing');

                clearTimeout(t);
                t = setTimeout(function () { wrap.classList.remove('is-typing'); }, 450);
            });
        });
    })();

    document.getElementById('togglePassword')?.addEventListener('click',function(){
        const inp=document.getElementById('password');
        const ico=document.getElementById('eyeIcon');
        const show=inp.type==='password';
        inp.type=show?'text':'password';
        ico.className=show?'fa-regular fa-eye-slash':'fa-regular fa-eye';
        this.setAttribute('aria-label',show?'Ocultar contraseña':'Mostrar contraseña');
    });
})();
