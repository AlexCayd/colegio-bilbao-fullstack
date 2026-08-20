<?php $paginaVista = 'blog-suplencias-dashboard'; ?>
<?php
/** @var array $conteos,$porEstado,$porOrigen,$topSuplentes,$topAusentes,$porMes,$porMateria,$porMotivo,$resumenDia,$detalleDia */
$total = (int)($conteos['total'] ?? 0);
$comp  = (int)($conteos['completada'] ?? 0);
$pct   = $total ? round($comp / $total * 100) : 0;

$dashData = [
    'porEstado'    => $porEstado,
    'porOrigen'    => $porOrigen,
    'porMes'       => $porMes,
    'porMateria'   => $porMateria,
    'porMotivo'    => $porMotivo,
    'topSuplentes' => array_map(fn($r) => ['nombre' => $r['nombre'], 'n' => (int)$r['coberturas'], 'val' => (int)$r['validadas']], $topSuplentes),
    'resumen'      => $resumenDia,
    'detalle'      => $detalleDia,
    // Sí / No / Sin revisar. Los tres, y el tercero aparte: es un hueco de captura,
    // no una respuesta, y meterlo en el porcentaje acusaría a alguien por él.
    'trabajo'      => $trabajo,
];

// Alcance por nivel de las direcciones. Con `$alcance` vacío el tablero es del
// colegio entero (admin o dirección general).
$alcance     = $alcance ?? [];
$nivelActivo = $nivelActivo ?? [];
?>
<div class="admin-layout">
    <?php include __DIR__ . '/../_sidebar.php'; ?>

    <div class="admin-main">
        <header class="admin-topbar">
            <div class="admin-topbar__left"><span class="admin-topbar__title">Tablero de suplencias</span></div>
            <div class="admin-topbar__actions">
                <?php include __DIR__ . '/../_topbar-avatar.php'; ?>
            </div>
        </header>

        <main class="admin-content">
            <?php if ($alcance): ?>
            <?php /* Un tablero filtrado que no lo dice miente: "Suplencias totales: 12"
                      se leería como el dato del colegio entero. */ ?>
            <div class="sd-alcance">
                <i class="fa-solid fa-layer-group"></i>
                <span>Estás viendo únicamente los datos de <strong><?= s(implode(' y ', $alcance)) ?></strong>.</span>
            </div>
            <?php endif; ?>

            <!-- KPIs -->
            <div class="sd-kpis">
                <div class="sd-kpi"><div class="sd-kpi__ico" style="background:#eaf2fb;color:#4285f4;"><i class="fa-solid fa-calendar-day"></i></div><div><div class="sd-kpi__val"><?= $total ?></div><div class="sd-kpi__lbl">Suplencias totales</div></div></div>
                <div class="sd-kpi"><div class="sd-kpi__ico" style="background:#e8f7ee;color:#34a853;"><i class="fa-solid fa-circle-check"></i></div><div><div class="sd-kpi__val"><?= $comp ?></div><div class="sd-kpi__lbl">Completadas</div></div></div>
                <div class="sd-kpi"><div class="sd-kpi__ico" style="background:#fdeef0;color:#e51022;"><i class="fa-solid fa-triangle-exclamation"></i></div><div><div class="sd-kpi__val"><?= (int)($conteos['por_justificar'] ?? 0) ?></div><div class="sd-kpi__lbl">Por justificar</div></div></div>
                <div class="sd-kpi"><div class="sd-kpi__ico" style="background:#fff5e0;color:#e0a800;"><i class="fa-solid fa-percent"></i></div><div><div class="sd-kpi__val"><?= $pct ?>%</div><div class="sd-kpi__lbl">Tasa de cobertura</div></div></div>
                <?php /* El porcentaje se calcula solo sobre las horas revisadas: las que
                          nadie ha mirado se cuentan aparte, en el subtítulo. */ ?>
                <div class="sd-kpi">
                    <div class="sd-kpi__ico" style="background:#f0f7e8;color:#8ac926;"><i class="fa-regular fa-clipboard"></i></div>
                    <div>
                        <div class="sd-kpi__val"><?= (int)$trabajo['pct'] ?>%</div>
                        <div class="sd-kpi__lbl">
                            Dejaron trabajo
                            <?php if ($trabajo['pendientes']): ?>
                            <small>· <?= (int)$trabajo['pendientes'] ?> sin revisar</small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="sd-grid">
                <div class="admin-panel sd-card">
                    <div class="admin-panel__header"><h2 class="admin-panel__title">Por estado</h2></div>
                    <div class="sd-chart"><canvas id="chartEstado"></canvas></div>
                </div>
                <div class="admin-panel sd-card">
                    <div class="admin-panel__header"><h2 class="admin-panel__title">Tendencia por mes</h2></div>
                    <div class="sd-chart"><canvas id="chartMes"></canvas></div>
                </div>
                <div class="admin-panel sd-card">
                    <div class="admin-panel__header"><h2 class="admin-panel__title">Quién más ha suplido</h2></div>
                    <div class="sd-chart"><canvas id="chartSuplentes"></canvas></div>
                </div>
                <div class="admin-panel sd-card">
                    <div class="admin-panel__header"><h2 class="admin-panel__title">Materias más cubiertas</h2></div>
                    <div class="sd-chart"><canvas id="chartMateria"></canvas></div>
                </div>
                <div class="admin-panel sd-card">
                    <div class="admin-panel__header"><h2 class="admin-panel__title">Motivos más frecuentes</h2></div>
                    <div class="sd-chart"><canvas id="chartMotivo"></canvas></div>
                </div>
                <div class="admin-panel sd-card">
                    <div class="admin-panel__header"><h2 class="admin-panel__title">Anticipadas vs. sin aviso</h2></div>
                    <div class="sd-chart"><canvas id="chartOrigen"></canvas></div>
                </div>
                <div class="admin-panel sd-card">
                    <div class="admin-panel__header"><h2 class="admin-panel__title">¿Dejaron trabajo para el grupo?</h2></div>
                    <div class="sd-chart"><canvas id="chartTrabajo"></canvas></div>
                </div>
            </div>

            <div class="sd-grid">
                <!-- Ranking ausentes -->
                <div class="admin-panel sd-card">
                    <div class="admin-panel__header"><h2 class="admin-panel__title">Quién más ha faltado</h2></div>
                    <div class="sd-card__body">
                        <?php if (empty($topAusentes)): ?><p class="sd-nil">Sin datos.</p><?php else: foreach ($topAusentes as $i => $a): ?>
                        <div class="sd-rank">
                            <span class="sd-rank__pos">#<?= $i + 1 ?></span>
                            <span class="supl-person__ava sd-rank__ava"><?php if (!empty($a['avatar'])): ?><img src="<?= s($a['avatar']) ?>" alt=""><?php else: ?><?= s(mb_strtoupper(mb_substr($a['nombre'], 0, 1))) ?><?php endif; ?></span>
                            <span class="sd-rank__name"><?= s($a['nombre']) ?></span>
                            <span class="sd-rank__val"><?= (int)$a['faltas'] ?> falta<?= (int)$a['faltas'] === 1 ? '' : 's' ?></span>
                        </div>
                        <?php endforeach; endif; ?>
                    </div>
                </div>

                <!-- Coberturas incumplidas -->
                <?php /* El suplente aceptó la hora y no se presentó. Va en su propio panel
                          y no mezclado con "quién más cubre": son cosas opuestas, y este es
                          el dato que justifica registrar el incumplimiento en vez de
                          limitarse a reabrir la hora. */ ?>
                <div class="admin-panel sd-card">
                    <div class="admin-panel__header">
                        <h2 class="admin-panel__title">Coberturas no cubiertas</h2>
                        <?php if (!empty($nIncumple)): ?>
                        <span class="sd-badge sd-badge--alerta"><?= (int)$nIncumple ?> en total</span>
                        <?php endif; ?>
                    </div>
                    <div class="sd-card__body">
                        <?php if (empty($incumple)): ?>
                            <p class="sd-nil">Nadie ha faltado a una cobertura asignada. </p>
                        <?php else: foreach ($incumple as $i => $a): ?>
                        <div class="sd-rank">
                            <span class="sd-rank__pos">#<?= $i + 1 ?></span>
                            <span class="supl-person__ava sd-rank__ava"><?php if (!empty($a['avatar'])): ?><img src="<?= s($a['avatar']) ?>" alt=""><?php else: ?><?= s(mb_strtoupper(mb_substr($a['nombre'], 0, 1))) ?><?php endif; ?></span>
                            <span class="sd-rank__name"><?= s($a['nombre']) ?></span>
                            <span class="sd-rank__val sd-rank__val--alerta"><?= (int)$a['n'] ?> vez<?= (int)$a['n'] === 1 ? '' : 'es' ?></span>
                        </div>
                        <?php endforeach; endif; ?>
                    </div>
                </div>

                <!-- Ausencias sin trabajo para el grupo -->
                <?php /* Se imputa al AUSENTE, no al suplente: el material lo deja quien
                          falta. Panel propio y no una columna del ranking de faltas: no
                          es lo mismo faltar mucho que dejar al grupo sin nada que hacer. */ ?>
                <div class="admin-panel sd-card">
                    <div class="admin-panel__header">
                        <h2 class="admin-panel__title">Ausencias sin trabajo para el grupo</h2>
                        <?php if (!empty($trabajo['sin'])): ?>
                        <span class="sd-badge sd-badge--alerta"><?= (int)$trabajo['sin'] ?> en total</span>
                        <?php endif; ?>
                    </div>
                    <div class="sd-card__body">
                        <?php if (empty($sinTrabajo)): ?>
                            <p class="sd-nil">
                                <?= $trabajo['revisadas'] ? 'Todas las ausencias revisadas dejaron trabajo.' : 'Todavía no se ha revisado ninguna hora.' ?>
                            </p>
                        <?php else: foreach ($sinTrabajo as $i => $a): ?>
                        <div class="sd-rank">
                            <span class="sd-rank__pos">#<?= $i + 1 ?></span>
                            <span class="supl-person__ava sd-rank__ava"><?php if (!empty($a['avatar'])): ?><img src="<?= s($a['avatar']) ?>" alt=""><?php else: ?><?= s(mb_strtoupper(mb_substr($a['nombre'], 0, 1))) ?><?php endif; ?></span>
                            <span class="sd-rank__name"><?= s($a['nombre']) ?></span>
                            <span class="sd-rank__val sd-rank__val--alerta"><?= (int)$a['n'] ?> hora<?= (int)$a['n'] === 1 ? '' : 's' ?></span>
                        </div>
                        <?php endforeach; endif; ?>
                    </div>
                </div>

                <!-- Calendario diario interactivo -->
                <div class="admin-panel sd-card">
                    <div class="admin-panel__header"><h2 class="admin-panel__title">Resumen diario</h2></div>
                    <div class="sd-card__body sd-cal-body">
                        <div class="bilbao-cal sd-cal" id="sdCal">
                            <div class="bilbao-cal__header">
                                <button type="button" class="bilbao-cal__nav" data-cal-prev aria-label="Mes anterior"><i class="fa-solid fa-chevron-left"></i></button>
                                <h3 class="bilbao-cal__month" data-cal-label>—</h3>
                                <button type="button" class="bilbao-cal__nav" data-cal-next aria-label="Mes siguiente"><i class="fa-solid fa-chevron-right"></i></button>
                            </div>
                            <div class="bilbao-cal__weekdays"><span>Dom</span><span>Lun</span><span>Mar</span><span>Mié</span><span>Jue</span><span>Vie</span><span>Sáb</span></div>
                            <div class="bilbao-cal__grid" data-cal-grid></div>
                        </div>

                        <?php /* Detalle del día seleccionado: lo rellena blog-suplencias-dashboard.js */ ?>
                        <div class="sd-day" data-cal-detalle>
                            <p class="sd-day__hint"><i class="fa-solid fa-hand-pointer"></i> Toca un día marcado para ver sus suplencias.</p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script type="application/json" id="suplDashData"><?= json_encode($dashData) ?></script>
