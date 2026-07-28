<?php
/**
 * Campo de fecha del panel — usa el datepicker propio (.bilbao-date,
 * src/js/admin/admin-datepicker.js) en vez del <input type="date"> nativo, para
 * que se vea igual en todos los navegadores y poder bloquear fines de semana.
 *
 * Variables opcionales antes del include (todas se resetean al final, así que se
 * puede incluir varias veces en el mismo formulario, p. ej. un rango desde/hasta):
 *
 *   $fechaName    name del hidden           (por defecto 'fecha')
 *   $fechaLabel   etiqueta; '' = sin ella   (por defecto 'Fecha')
 *   $fechaValor   Y-m-d; '' = sin fecha     (por defecto hoy)
 *   $fechaMin     Y-m-d o null
 *   $fechaMax     Y-m-d o null
 *   $fechaHabiles bool: bloquear sáb/dom    (por defecto true)
 *   $fechaLimpiar bool: botón "Limpiar"     (por defecto true si el valor va vacío)
 *   $fechaPlaceholder texto sin fecha       (por defecto 'Elige una fecha')
 */
$fechaName    = $fechaName    ?? 'fecha';
$fechaLabel   = $fechaLabel   ?? 'Fecha';
$fechaValor   = $fechaValor   ?? date('Y-m-d');
$fechaMin     = $fechaMin     ?? null;
$fechaMax     = $fechaMax     ?? null;
$fechaHabiles = $fechaHabiles ?? true;
$fechaPlaceholder = $fechaPlaceholder ?? 'Elige una fecha';
// Si el campo puede ir vacío (filtros), hace falta poder volver a vaciarlo
$fechaLimpiar = $fechaLimpiar ?? ($fechaValor === '');

$_mesesEs = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
$_diasEs  = ['domingo','lunes','martes','miércoles','jueves','viernes','sábado'];

// Etiqueta pre-renderizada en el servidor: evita el parpadeo antes de que corra el JS
if ($fechaValor !== '') {
    $_ts        = strtotime($fechaValor);
    $_etiqueta  = ucfirst($_diasEs[(int)date('w', $_ts)]) . ', ' . (int)date('j', $_ts)
                . ' de ' . $_mesesEs[(int)date('n', $_ts) - 1] . ' de ' . date('Y', $_ts);
} else {
    $_etiqueta = $fechaPlaceholder;
}
?>
<div class="admin-form__group">
    <?php if ($fechaLabel !== ''): ?>
    <label class="admin-form__label"><i class="fa-regular fa-calendar"></i> <?= s($fechaLabel) ?></label>
    <?php endif; ?>

    <?php /* data-habiles="1": los fines de semana quedan bloqueados, no hay clases que cubrir */ ?>
    <div class="bilbao-date<?= $fechaValor === '' ? '' : ' has-value' ?>" data-datepicker<?=
        $fechaHabiles ? ' data-habiles="1"' : '' ?><?=
        $fechaMin ? ' data-min="' . s($fechaMin) . '"' : '' ?><?=
        $fechaMax ? ' data-max="' . s($fechaMax) . '"' : '' ?>>

        <?php /* Sin `required`: un hidden no es enfocable y el navegador bloquearía el envío
                 sin poder señalar el error. */ ?>
        <input type="hidden" name="<?= s($fechaName) ?>" value="<?= s($fechaValor) ?>" data-date-value data-fecha>

        <button type="button" class="bilbao-date__field" data-date-trigger aria-haspopup="dialog" aria-expanded="false">
            <i class="fa-regular fa-calendar"></i>
            <span data-date-label><?= s($_etiqueta) ?></span>
            <i class="fa-solid fa-chevron-down bilbao-date__caret"></i>
        </button>

        <div class="bilbao-date__pop" data-date-pop hidden role="dialog" aria-label="Elegir fecha">
            <div class="bilbao-date__head">
                <button type="button" class="bilbao-date__nav" data-date-prev aria-label="Mes anterior"><i class="fa-solid fa-chevron-left"></i></button>
                <span class="bilbao-date__month" data-date-month>—</span>
                <button type="button" class="bilbao-date__nav" data-date-next aria-label="Mes siguiente"><i class="fa-solid fa-chevron-right"></i></button>
            </div>
            <?php /* La semana empieza en domingo (ver admin-datepicker.js) */ ?>
            <div class="bilbao-date__weekdays">
                <span>Dom</span><span>Lun</span><span>Mar</span><span>Mié</span><span>Jue</span><span>Vie</span><span>Sáb</span>
            </div>
            <div class="bilbao-date__grid" data-date-grid></div>
            <div class="bilbao-date__foot">
                <button type="button" class="bilbao-date__hoy" data-date-hoy>Hoy</button>
                <?php if ($fechaLimpiar): ?>
                <button type="button" class="bilbao-date__clear" data-date-clear>Limpiar</button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php
// Reset: si no, una segunda inclusión en el mismo formulario heredaría estos valores
unset($fechaName, $fechaLabel, $fechaValor, $fechaMin, $fechaMax,
      $fechaHabiles, $fechaLimpiar, $fechaPlaceholder,
      $_mesesEs, $_diasEs, $_ts, $_etiqueta);
