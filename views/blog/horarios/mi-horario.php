<?php $paginaVista = 'blog-horarios-mi-horario'; ?>
<?php
/** @var \Model\UsuarioBlog $profesor  @var array $tramos  @var array $rejilla
 *  @var array $ocupadoPorDia  MINUTOS de clase por día  @var int $totalClases
 *  @var string[] $niveles  @var string[] $discrepantes */
$vista = 'profesor';

// En minutos, no en "horas": un bloque dura 45' en Maternal/Kinder y 50' en el resto,
// así que contar celdas no es comparable entre jornadas.
$minOcupados  = array_sum($ocupadoPorDia);
$horasEnteras = intdiv($minOcupados, 60);
$restoMin     = $minOcupados % 60;
$puedeSuplir  = (int)($profesor->puede_suplir ?? 1) === 1;
?>
<div class="admin-layout">
    <?php include __DIR__ . '/../_sidebar.php'; ?>

    <div class="admin-main">
        <header class="admin-topbar">
            <div class="admin-topbar__left">
                <span class="admin-topbar__title">Mi horario</span>
            </div>
            <div class="admin-topbar__actions">
                <?php if (!empty($tramos)): ?>
                <?php /* Descarga directa: el PDF lo arma el servidor con Dompdf, no el
                          navegador, así que sale igual en cualquier equipo. */ ?>
                <a href="/dashboard/horarios/mi-horario.pdf" class="admin-btn admin-btn--ghost">
                    <i class="fa-solid fa-file-arrow-down"></i> Descargar PDF
                </a>
                <?php endif; ?>
                <?php include __DIR__ . '/../_topbar-avatar.php'; ?>
            </div>
        </header>

        <main class="admin-content">

            <div class="mih-intro">
                <img src="/build/assets/img/alex/bby-alex-saluda.png" alt="Alex" class="mih-intro__alex">
                <div class="mih-intro__body">
                    <p class="mih-intro__text">
                        Este es tu horario tal como lo tiene cargado dirección. Es de <strong>solo lectura</strong>:
                        si algo no cuadra, avisa a prefectura.
                        <?php if ($puedeSuplir): ?>
                        Las horas en las que no tienes clase son las que el sistema puede proponer para cubrir una
                        suplencia, respetando siempre un descanso mínimo al día.
                        <?php else: ?>
                        Estás marcado como <strong>no disponible para suplir</strong>, así que no aparecerás entre
                        los candidatos a cubrir clases.
                        <?php endif; ?>
                    </p>
                </div>
                <?php /* Horas de CLASE, que es la carga que el profesor reconoce como suya.
                          "18:20" se leería como una hora del reloj: la unidad va dentro. */ ?>
                <div class="mih-intro__stat">
                    <span class="mih-intro__stat-n"><?= $horasEnteras ?><small>h<?= $restoMin ? ' ' . sprintf('%02d', $restoMin) : '' ?></small></span>
                    <span class="mih-intro__stat-l"><?= $totalClases ?> <?= $totalClases === 1 ? 'clase' : 'clases' ?> a la semana</span>
                </div>
            </div>

            <div class="admin-panel">
                <div class="admin-panel__header hor-head">
                    <h2 class="admin-panel__title"><i class="fa-regular fa-calendar-check"></i> <?= s($profesor->nombre) ?></h2>
                    <div class="hor-head__meta">
                        <?php if (count($niveles) > 1): ?>
                        <?php /* Da clase en varios niveles: la rejilla mezcla sus jornadas sobre un
                                  eje de reloj común, así que conviene decir cuáles son. */ ?>
                        <span class="hor-niveles" title="Su horario cruza estas jornadas">
                            <i class="fa-solid fa-layer-group"></i> <?= s(implode(' · ', $niveles)) ?>
                        </span>
                        <?php endif; ?>
                        <span class="mih-badge<?= $puedeSuplir ? '' : ' mih-badge--off' ?>">
                            <i class="fa-solid <?= $puedeSuplir ? 'fa-circle-check' : 'fa-ban' ?>"></i>
                            <?= $puedeSuplir ? 'Disponible para suplir' : 'Excluido de suplencias' ?>
                        </span>
                    </div>
                </div>

                <?php if (empty($tramos)): ?>
                <div class="admin-empty-state" style="padding:40px;">
                    <p class="admin-empty-state__text">
                        Todavía no tienes clases cargadas. En cuanto dirección suba tu horario aparecerá aquí.
                    </p>
                </div>
                <?php else: ?>
                <?php include __DIR__ . '/_grid.php'; ?>
                <?php endif; ?>
            </div>

        </main>
    </div>
</div>
