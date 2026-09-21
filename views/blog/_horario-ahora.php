<?php
/**
 * «Ahora / Sigue» — la clase en curso y la siguiente, más la columna de hoy.
 *
 * Lo incluyen el home del panel (para uno mismo) y la ficha del colaborador (para otro),
 * así que NO asume que el sujeto sea quien mira: el texto sale de `$haTitulo`/`$haPropio`.
 *
 * ⚠️ La hora la lleva el CLIENTE (src/js/admin/admin-horario-ahora.js, tic de 30 s). El
 * servidor solo estampa su propia hora en `data-ahora` para corregir la deriva al cargar:
 * un reloj de portátil mal puesto marcaría la clase equivocada, y aquí eso significa que
 * un profesor cree que le toca otra cosa. Sin JS el bloque se queda en su estado base —
 * la columna del día completa, sin resaltado— que sigue siendo información correcta.
 *
 * @var array  $haBloques  [['ini','fin','etiqueta','materia','grupo','aula','color','receso'], …]
 * @var string $haTitulo   encabezado de la tarjeta
 * @var bool   $haPropio   true = es el horario de quien mira
 * @var string $haDia      'lunes'… o '' si hoy no hay jornada
 */
$haBloques = $haBloques ?? [];
$haTitulo  = $haTitulo  ?? 'Tu día de hoy';
$haPropio  = $haPropio  ?? true;
$haDia     = $haDia     ?? '';
$_haFinde  = $haDia === '';
?>
<section class="hoy" data-hoy data-ahora="<?= date('Y-m-d\TH:i:s') ?>">

    <header class="hoy__head">
        <h2 class="hoy__titulo"><i class="fa-regular fa-clock"></i> <?= s($haTitulo) ?></h2>
        <span class="hoy__reloj" data-hoy-reloj aria-live="off">--:--</span>
    </header>

    <?php if ($_haFinde): ?>
        <p class="hoy__vacio">
            <i class="fa-solid fa-mug-hot"></i>
            Hoy no hay jornada escolar.
        </p>
    <?php elseif (!$haBloques): ?>
        <p class="hoy__vacio">
            <i class="fa-regular fa-calendar-xmark"></i>
            <?= $haPropio ? 'No tienes clases' : 'Sin clases' ?> este <?= s($haDia) ?>.
        </p>
    <?php else: ?>

        <?php /* Los dos estados posibles conviven en el DOM y el JS enseña el que toca.
                 Pintarlos en servidor sería mentir a los treinta segundos. */ ?>
        <div class="hoy__foco" data-hoy-foco hidden>
            <div class="hoy__ahora" data-hoy-ahora hidden>
                <span class="hoy__etiqueta">Ahora</span>
                <p class="hoy__clase" data-hoy-ahora-clase></p>
                <p class="hoy__meta"  data-hoy-ahora-meta></p>
                <div class="hoy__barra" role="progressbar" aria-label="Avance de la clase"
                     aria-valuemin="0" aria-valuemax="100" data-hoy-barra>
                    <span data-hoy-barra-fill></span>
                </div>
                <p class="hoy__restan" data-hoy-restan></p>
            </div>

            <div class="hoy__sigue" data-hoy-sigue hidden>
                <span class="hoy__etiqueta hoy__etiqueta--sigue">Después</span>
                <p class="hoy__clase" data-hoy-sigue-clase></p>
                <p class="hoy__meta"  data-hoy-sigue-meta></p>
            </div>

            <p class="hoy__fin" data-hoy-fin hidden>
                <i class="fa-solid fa-flag-checkered"></i>
                <?= $haPropio ? 'Terminaste' : 'Jornada terminada' ?> por hoy.
            </p>
        </div>

        <ol class="hoy__lista">
            <?php foreach ($haBloques as $b): ?>
            <li class="hoy__fila<?= !empty($b['receso']) ? ' hoy__fila--receso' : '' ?><?= empty($b['materia']) && empty($b['receso']) ? ' hoy__fila--libre' : '' ?>"
                data-hoy-fila
                data-ini="<?= s(substr((string)$b['ini'], 0, 5)) ?>"
                data-fin="<?= s(substr((string)$b['fin'], 0, 5)) ?>"
                style="--c:<?= s($b['color'] ?? '#94a3b8') ?>;">
                <span class="hoy__hora"><?= s(substr((string)$b['ini'], 0, 5)) ?></span>
                <span class="hoy__cuerpo">
                    <?php if (!empty($b['receso'])): ?>
                        <span class="hoy__mat">Receso</span>
                    <?php elseif (!empty($b['materia'])): ?>
                        <span class="hoy__mat"><?= s($b['materia']) ?></span>
                        <span class="hoy__sec">
                            <?= s(trim(($b['grupo'] ?? '') . ($b['aula'] ? ' · ' . $b['aula'] : ''), ' ·')) ?>
                        </span>
                    <?php else: ?>
                        <span class="hoy__mat hoy__mat--libre">Libre</span>
                    <?php endif; ?>
                </span>
                <span class="hoy__fin-h"><?= s(substr((string)$b['fin'], 0, 5)) ?></span>
            </li>
            <?php endforeach; ?>
        </ol>

    <?php endif; ?>
</section>
