/**
 * Zona de peligro — micro-interacción con GSAP.
 *
 * Componente compartido: NO lleva guarda de página, se activa por existencia de
 * `[data-danger]` (mismo criterio que admin-file, admin-toast o admin-pager).
 *
 * Todo el estado visual —fondo rojo, sombra, hover— vive en el CSS. Aquí solo se
 * añade movimiento, así que si GSAP no carga (CDN caído) o el usuario pidió menos
 * animación, la zona sigue siendo perfectamente usable: solo pierde el gesto.
 */
(function () {
    'use strict';

    const zonas = document.querySelectorAll('[data-danger]');
    if (!zonas.length) return;

    // Sin GSAP o con prefers-reduced-motion no se hace nada: el CSS ya basta.
    if (!window.gsap) return;
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    const gsap = window.gsap;

    zonas.forEach(function (zona) {
        const btn = zona.querySelector('[data-danger-btn]');
        if (!btn) return;

        const ico    = btn.querySelector('[data-danger-ico]');
        const header = zona.querySelector('.admin-danger-zone__header');

        // Una sola timeline pausada por zona: reproducirla hacia delante al
        // entrar y hacia atrás al salir evita que hovers rápidos dejen la
        // tarjeta a medio animar.
        const tl = gsap.timeline({ paused: true });

        tl.to(zona, {
            borderColor: '#e51022',
            boxShadow: '0 14px 34px -18px rgba(229, 16, 34, .75)',
            duration: .28,
            ease: 'power2.out'
        }, 0);

        if (header) {
            tl.to(header, { backgroundColor: '#ffe9e9', duration: .28, ease: 'power2.out' }, 0);
        }

        if (ico) {
            // La papelera "se sacude": es el gesto que anuncia el borrado.
            tl.to(ico, {
                keyframes: [
                    { rotation: -14, y: -2, duration: .1 },
                    { rotation: 11,  y: 0,  duration: .1 },
                    { rotation: -6,           duration: .09 },
                    { rotation: 0,            duration: .09 }
                ],
                ease: 'power1.inOut'
            }, .04);
        }

        btn.addEventListener('mouseenter', function () { tl.play(); });
        btn.addEventListener('focus',      function () { tl.play(); });
        btn.addEventListener('mouseleave', function () { tl.reverse(); });
        btn.addEventListener('blur',       function () { tl.reverse(); });

        // Al confirmar, un último acuse antes de que el modal tome el relevo.
        btn.addEventListener('click', function () {
            gsap.fromTo(zona, { x: -5 }, {
                x: 0, duration: .5, ease: 'elastic.out(1, .35)', clearProps: 'x'
            });
        });
    });
})();
