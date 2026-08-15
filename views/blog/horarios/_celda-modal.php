<?php
/**
 * Detalle de una casilla del horario.
 *
 * Existe porque la celda de `.hor-grid` recorta con `text-overflow: ellipsis`: la
 * materia, el grupo, el aula y los docentes comparten una línea, y lo que no cabe
 * desaparecía sin avisar. El dato completo vivía solo en el atributo `title`, que en
 * táctil no existe.
 *
 * Lo rellena `src/js/admin/admin-horario-celda.js` desde el `data-hor-cell` de la
 * casilla pulsada. Se oculta con `[hidden]`, como el resto de modales del panel
 * (.cat-modal, .hed-modal, .nt-modal).
 *
 * ⚠️ Guarda anti-duplicado: `_grid.php` puede incluirse más de una vez en la misma
 * página (el editor pinta su rejilla y además la semana consolidada), y dos elementos
 * con el mismo id romperían el getElementById.
 */
if (!empty($GLOBALS['_horCeldaModalPuesto'])) return;
$GLOBALS['_horCeldaModalPuesto'] = true;
?>
<div class="hcd-modal" id="horCeldaModal" hidden aria-hidden="true">
    <div class="hcd-modal__card" role="dialog" aria-modal="true" aria-labelledby="hcdTitulo">
        <header class="hcd-modal__head">
            <span class="hcd-modal__eyebrow" data-hcd-eyebrow></span>
            <h3 class="hcd-modal__title" id="hcdTitulo">—</h3>
            <button type="button" class="hcd-modal__x" data-hcd-cerrar aria-label="Cerrar">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </header>

        <dl class="hcd-modal__lista" data-hcd-lista></dl>

        <footer class="hcd-modal__foot">
            <button type="button" class="admin-btn admin-btn--primary" data-hcd-cerrar>Entendido</button>
        </footer>
    </div>
</div>
