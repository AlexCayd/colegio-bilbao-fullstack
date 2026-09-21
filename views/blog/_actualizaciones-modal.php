<?php
/**
 * Modal BLOQUEANTE de novedades. Lo incluye views/layout-admin.php cuando quedan
 * anuncios publicados sin acusar, así que se interpone en cualquier pantalla del panel.
 *
 * Es deliberadamente inescapable: sin ✕, sin cierre por `Escape`, sin cierre al pulsar el
 * fondo. La única salida es "Entendido", que hace POST y deja constancia por persona. Una
 * notificación normal se puede ignorar para siempre; un cambio de funcionamiento que nadie
 * ha leído acaba en tickets de soporte, y esa es justo la diferencia que este componente
 * existe para marcar.
 *
 * El avance entre varios anuncios lo lleva el JS (src/js/admin/admin-actualizaciones.js),
 * pero **sin JS sigue siendo usable**: los artículos se pintan todos, y cada uno trae su
 * propio formulario de acuse. Se acusa de uno en uno y la página recarga — más torpe, pero
 * nadie se queda encerrado si el bundle no carga.
 *
 * @var \Model\Actualizacion[] $_actPend
 */
$_n = count($_actPend);
// A dónde volver tras acusar. El modal aparece encima de CUALQUIER pantalla, así que
// mandar siempre al home obligaría a rehacer la navegación. El servidor revalida que
// sea una ruta interna del panel (verActualizacion()): esto es comodidad, no confianza.
$_volver = $_SERVER['REQUEST_URI'] ?? '/dashboard';
?>
<div class="act-gate" id="actGate" role="dialog" aria-modal="true" aria-labelledby="actGateTitle">
    <div class="act-gate__card">

        <header class="act-gate__head">
            <img src="/build/assets/img/alex/alex-tech.png" alt="" class="act-gate__alex">
            <div>
                <p class="act-gate__eyebrow"><i class="fa-solid fa-wand-magic-sparkles"></i> Novedades del panel</p>
                <h2 class="act-gate__title" id="actGateTitle">
                    <?= $_n === 1 ? 'Hay una novedad' : 'Hay ' . $_n . ' novedades' ?>
                </h2>
            </div>
            <?php if ($_n > 1): ?>
            <span class="act-gate__contador" data-act-contador>1 de <?= $_n ?></span>
            <?php endif; ?>
        </header>

        <div class="act-gate__body">
            <?php foreach (array_values($_actPend) as $_i => $_a): ?>
            <?php /* Todos en el DOM; el JS deja visible solo el activo. `hidden` en los
                     demás para que sin JS se vean los N seguidos en vez de ninguno. */ ?>
            <article class="act-gate__item" data-act-item data-act-idx="<?= $_i ?>"
                     <?= $_i > 0 ? 'data-act-oculto' : '' ?>>
                <div class="act-gate__meta">
                    <?php if ($_a->version): ?>
                    <span class="act-gate__ver"><?= s($_a->version) ?></span>
                    <?php endif; ?>
                    <?php if ($_a->publicada_en): ?>
                    <span class="act-gate__fecha"><?= s(fecha_larga(substr((string)$_a->publicada_en, 0, 10))) ?></span>
                    <?php endif; ?>
                </div>

                <h3 class="act-gate__h"><?= s($_a->titulo) ?></h3>

                <?php if ($_a->imagen): ?>
                <img src="<?= s($_a->imagen) ?>" alt="" class="act-gate__img" loading="lazy">
                <?php endif; ?>

                <?php /* nl2br sobre texto ya escapado: el cuerpo lo escribe el
                         desarrollador, pero pasa por la misma puerta que todo lo demás —
                         nadie se acuerda de que esto era «de confianza» dentro de un año. */ ?>
                <div class="act-gate__texto"><?= nl2br(s($_a->cuerpo)) ?></div>

                <form method="POST" action="/dashboard/actualizaciones/visto" class="act-gate__form">
                    <input type="hidden" name="id" value="<?= (int)$_a->id ?>">
                    <input type="hidden" name="volver" value="<?= s($_volver) ?>">
                    <button type="submit" class="admin-btn admin-btn--primary act-gate__ok" data-act-ok>
                        <i class="fa-solid fa-check"></i>
                        <?= $_i === $_n - 1 ? 'Entendido' : 'Entendido, siguiente' ?>
                    </button>
                </form>
            </article>
            <?php endforeach; ?>
        </div>

        <footer class="act-gate__pie">
            <i class="fa-solid fa-circle-info"></i>
            Podrás releerlas cuando quieras en <strong>Actualizaciones</strong>.
        </footer>
    </div>
</div>
