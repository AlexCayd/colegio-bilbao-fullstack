/**
 * Animación de los calendarios .bilbao-cal (GSAP).
 *
 * Componente compartido: sin guarda de página. Los cuatro calendarios del proyecto
 * repintan su rejilla por su cuenta (home, cumpleaños, tablero de suplencias y la
 * pública de Familias), así que no puede engancharse a un evento: se expone
 * `window.BilbaoCalAnim.entrada(grid)` y cada render() la llama después de pintar.
 *
 * Es puro adorno. Sin GSAP o con prefers-reduced-motion el objeto sigue existiendo
 * pero no hace nada, así que los llamadores no necesitan comprobar nada.
 */
(function () {
    'use strict';

    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function activo() {
        return !!window.gsap && !reduce;
    }

    /** Entrada escalonada de las celdas: se usa al cambiar de mes. */
    function entrada(grid) {
        if (!activo() || !grid) return;
        const celdas = grid.querySelectorAll('.bilbao-cal__cell');
        if (!celdas.length) return;

        window.gsap.fromTo(celdas,
            { opacity: 0, y: 6 },
            {
                opacity: 1,
                y: 0,
                duration: .28,
                ease: 'power2.out',
                // Por filas, no una a una: 42 celdas en cascada se hacía eterno.
                stagger: { each: .012, from: 'start' },
                clearProps: 'opacity,transform'
            }
        );
    }

    /** Acuse al elegir un día. */
    function pop(celda) {
        if (!activo() || !celda) return;
        window.gsap.fromTo(celda,
            { scale: .86 },
            { scale: 1, duration: .42, ease: 'back.out(2.2)', clearProps: 'transform' }
        );
    }

    window.BilbaoCalAnim = { entrada: entrada, pop: pop, activo: activo };
})();
