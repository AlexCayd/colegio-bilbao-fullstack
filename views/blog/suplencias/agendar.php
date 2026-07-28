<?php $paginaVista = 'blog-suplencias-agendar'; ?>
<?php
/** @var \Model\Suplencia $suplencia  @var \Model\SuplenciaHora[] $horas  @var bool $puedeAgendar */
$estadoLabel = \Model\Suplencia::ESTADO_LABEL;
$ausenteId = (int)$suplencia->profesor_ausente_id;
$fecha = $suplencia->fecha;
$estadoCol = [
    'solicitada' => '#f5b400', 'agendada' => '#4285f4', 'en_curso' => '#4285f4',
    'por_justificar' => '#e51022', 'completada' => '#34a853', 'cancelada' => '#94a3b8',
];

$total      = count($horas);
$cubiertas  = 0;
$pendientes = 0;
foreach ($horas as $h) {
    if ($h->estado_hora === 'pendiente') $pendientes++;
    else $cubiertas++;
}
$pct = $total ? round($cubiertas / $total * 100) : 0;
$todoCubierto = $total > 0 && $pendientes === 0;
$activa = $suplencia->estado !== 'cancelada';

$diasEs = ['domingo','lunes','martes','miércoles','jueves','viernes','sábado'];
?>
<div class="admin-layout">
    <?php include __DIR__ . '/../_sidebar.php'; ?>

    <div class="admin-main">
        <header class="admin-topbar">
            <div class="admin-topbar__left"><span class="admin-topbar__title">Agendar suplencia</span></div>
            <div class="admin-topbar__actions">
                <a href="/dashboard/suplencias" class="admin-btn admin-btn--ghost"><i class="fa-solid fa-arrow-left"></i> Volver</a>
                <?php include __DIR__ . '/../_topbar-avatar.php'; ?>
                <form action="/logout" method="POST" style="display:flex;align-items:center;">
                    <button type="submit" class="admin-logout-btn"><i class="fa-solid fa-right-from-bracket"></i> Salir</button>
                </form>
            </div>
        </header>

        <main class="admin-content" data-fecha="<?= s($fecha) ?>" data-ausente="<?= $ausenteId ?>" data-puede-agendar="<?= $puedeAgendar ? '1' : '0' ?>">

            <?php /* Cabecera en tres zonas (identidad · datos · progreso) centradas
                     verticalmente: antes el bloque derecho quedaba descolgado arriba
                     y sobraba espacio en medio. */ ?>
            <div class="supl-detail-head">
                <div class="supl-detail-head__main">
                    <span class="supl-person__ava supl-detail-head__ava">
                        <?php if ($suplencia->ausente_avatar): ?><img src="<?= s($suplencia->ausente_avatar) ?>" alt=""><?php else: ?><?= s(mb_strtoupper(mb_substr($suplencia->ausente_nombre ?: 'U', 0, 1))) ?><?php endif; ?>
                    </span>
                    <div class="supl-detail-head__id">
                        <h2 class="supl-detail-head__name"><?= s($suplencia->ausente_nombre ?: 'Profesor') ?></h2>
                        <p class="supl-detail-head__meta">Profesor ausente</p>
                    </div>
                </div>

                <div class="supl-detail-head__facts">
                    <span class="supl-fact">
                        <i class="fa-regular fa-calendar"></i>
                        <b><?= s(ucfirst($diasEs[(int)date('w', strtotime($fecha))] ?? '')) ?> <?= (int)date('j', strtotime($fecha)) ?></b>
                        <small><?= date('d/m/Y', strtotime($fecha)) ?></small>
                    </span>
                    <span class="supl-fact">
                        <i class="fa-solid <?= $suplencia->origen === 'sin_aviso' ? 'fa-bolt' : 'fa-hand' ?>"></i>
                        <b><?= $suplencia->origen === 'sin_aviso' ? 'Sin aviso' : 'Anticipada' ?></b>
                        <small><?= $suplencia->motivo ? s($suplencia->motivo) : 'Sin motivo indicado' ?></small>
                    </span>
                </div>

                <div class="supl-detail-head__right">
                    <?php if ($total): ?>
                    <div class="supl-cover supl-detail-head__cover">
                        <div class="supl-cover__bar"><span style="width:<?= $pct ?>%;"></span></div>
                        <span class="supl-cover__txt"><strong><?= $cubiertas ?></strong> de <?= $total ?> horas asignadas</span>
                    </div>
                    <?php endif; ?>
                    <span class="supl-badge" style="background:<?= $estadoCol[$suplencia->estado] ?? '#94a3b8' ?>;color:#fff;"><?= $estadoLabel[$suplencia->estado] ?? $suplencia->estado ?></span>
                </div>
            </div>

            <?php if ($suplencia->notas): ?>
            <div class="supl-notas">
                <i class="fa-regular fa-note-sticky"></i>
                <div><strong>Notas para el suplente:</strong> <?= s($suplencia->notas) ?></div>
            </div>
            <?php endif; ?>

            <?php /* Justificante: tarjeta propia y visible, solo en ausencias sin aviso */ ?>
            <?php if ($suplencia->origen === 'sin_aviso'): ?>
            <div class="supl-justif <?= !empty($suplencia->justificante) ? 'is-ok' : 'is-pending' ?>">
                <div class="supl-justif__msg">
                    <i class="fa-solid <?= !empty($suplencia->justificante) ? 'fa-file-circle-check' : 'fa-file-circle-exclamation' ?>"></i>
                    <div>
                        <strong><?= !empty($suplencia->justificante) ? 'Justificante recibido' : 'Falta el justificante' ?></strong>
                        <?php if (!empty($suplencia->justificante)): ?>
                            <span>La ausencia queda documentada. <a href="<?= s($suplencia->justificante) ?>" target="_blank" rel="noopener">Ver archivo</a></span>
                        <?php else: ?>
                            <span>La suplencia no puede completarse hasta que <?= s($suplencia->ausente_nombre ?: 'el profesor ausente') ?> suba su comprobante.</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if (empty($suplencia->justificante)): ?>
                <form method="POST" action="/dashboard/suplencias/justificar" enctype="multipart/form-data" class="supl-justif__form">
                    <input type="hidden" name="id" value="<?= (int)$suplencia->id ?>">
                    <label class="admin-file" data-file data-file-max="4">
                        <input type="file" name="justificante" accept="application/pdf,image/jpeg,image/png,image/webp" required>
                        <span class="admin-file__ico"><i class="fa-solid fa-paperclip"></i></span>
                        <span class="admin-file__text">
                            <span class="admin-file__title" data-file-title>Elige el justificante</span>
                            <span class="admin-file__hint" data-file-hint>PDF o imagen · máx. 4 MB</span>
                        </span>
                    </label>
                    <button type="submit" class="admin-btn admin-btn--primary"><i class="fa-solid fa-upload"></i> Subir</button>
                </form>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Cierre del flujo -->
            <?php if ($total && $activa): ?>
            <div class="supl-done<?= $todoCubierto ? ' is-ok' : ' is-pending' ?>">
                <?php if ($todoCubierto): ?>
                    <img src="/build/assets/img/alex/alex-mano.png" alt="Alex" class="supl-done__alex">
                    <div class="supl-done__text">
                        <strong>Suplencia lista: las <?= $total ?> hora<?= $total === 1 ? '' : 's' ?> tiene<?= $total === 1 ? '' : 'n' ?> suplente</strong>
                        <small>Ya no queda nada por asignar. Cada suplente confirmará su cobertura
                               desde «Mis coberturas» y la suplencia pasará a completada.</small>
                    </div>
                    <a href="/dashboard/suplencias" class="admin-btn supl-done__cta">
                        <i class="fa-solid fa-check"></i> Finalizar y volver a suplencias
                    </a>
                <?php else: ?>
                    <span class="supl-done__ico"><i class="fa-solid fa-circle-exclamation"></i></span>
                    <div class="supl-done__text">
                        <strong>Falta<?= $pendientes === 1 ? '' : 'n' ?> <?= $pendientes ?> hora<?= $pendientes === 1 ? '' : 's' ?> por asignar</strong>
                        <small>Elige una hora de la lista y asígnale un suplente.</small>
                    </div>
                    <span class="supl-done__cuenta"><?= $cubiertas ?>/<?= $total ?></span>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <div class="supl-agenda">
                <!-- ══ Izquierda: horas a cubrir ══ -->
                <div class="admin-panel supl-agenda__col">
                    <div class="admin-panel__header"><h2 class="admin-panel__title">Horas a cubrir <span class="admin-panel__count"><?= $total ?></span></h2></div>
                    <div class="supl-agenda__body">
                        <?php if (!$total): ?>
                            <p class="supl-week-empty">No hay horas registradas todavía.</p>
                        <?php else: foreach ($horas as $h): ?>
                            <div class="supl-hora-card<?= $h->estado_hora === 'pendiente' ? ' is-open' : '' ?>"
                                 data-hora-card
                                 data-hora="<?= (int)$h->id ?>"
                                 data-periodo="<?= (int)$h->periodo_id ?>"
                                 data-etiqueta="<?= s($h->periodo_etiqueta) ?>"
                                 data-rango="<?= substr($h->periodo_inicio, 0, 5) ?>–<?= substr($h->periodo_fin, 0, 5) ?>"
                                 data-clase="<?= s(trim(($h->materia ?: 'Clase') . ($h->grupo_nombre ? ' · ' . $h->grupo_nombre : '') . ($h->aula_nombre ? ' · ' . $h->aula_nombre : ''))) ?>"
                                 <?= $puedeAgendar && $h->estado_hora === 'pendiente' ? 'role="button" tabindex="0"' : '' ?>>

                                <!-- La hora es el dato dominante de la tarjeta -->
                                <span class="supl-hora-card__tile">
                                    <strong><?= s($h->periodo_etiqueta) ?></strong>
                                    <small><?= substr($h->periodo_inicio, 0, 5) ?>–<?= substr($h->periodo_fin, 0, 5) ?></small>
                                </span>

                                <div class="supl-hora-card__info">
                                    <span class="supl-hora-card__clase"><?= s($h->materia ?: 'Clase') ?></span>
                                    <span class="supl-hora-card__meta">
                                        <?php if ($h->grupo_nombre): ?><i class="fa-solid fa-users-rectangle"></i> <?= s($h->grupo_nombre) ?><?php endif; ?>
                                        <?php if ($h->aula_nombre): ?><i class="fa-solid fa-door-open"></i> <?= s($h->aula_nombre) ?><?php endif; ?>
                                    </span>
                                </div>

                                <div class="supl-hora-card__cover">
                                    <?php if ($h->estado_hora === 'validada'): ?>
                                        <span class="supl-hora-card__suplente"><i class="fa-solid fa-circle-check" style="color:#34a853;"></i> <?= s($h->suplente_nombre) ?> <em>(validada)</em></span>
                                    <?php elseif ($h->estado_hora === 'agendada'): ?>
                                        <span class="supl-hora-card__suplente"><i class="fa-solid fa-user-check" style="color:#4285f4;"></i> <?= s($h->suplente_nombre) ?> <em>(por validar)</em></span>
                                        <?php if ($puedeAgendar): ?>
                                        <form method="POST" action="/dashboard/suplencias/agendar" style="display:inline;">
                                            <input type="hidden" name="id" value="<?= (int)$suplencia->id ?>">
                                            <input type="hidden" name="_accion" value="desasignar">
                                            <input type="hidden" name="hora_id" value="<?= (int)$h->id ?>">
                                            <button type="submit" class="supl-icon-btn supl-icon-btn--danger" title="Quitar suplente"><i class="fa-solid fa-user-xmark"></i></button>
                                        </form>
                                        <?php endif; ?>
                                    <?php elseif ($puedeAgendar): ?>
                                        <span class="supl-hora-card__pending"><i class="fa-solid fa-hourglass-half"></i> Sin asignar</span>
                                        <span class="supl-hora-card__go">Elegir suplente <i class="fa-solid fa-arrow-right"></i></span>
                                    <?php else: ?>
                                        <span class="supl-hora-card__pending">Sin asignar</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; endif; ?>
                    </div>
                </div>

                <!-- ══ Derecha: asignación ══ -->
                <div class="admin-panel supl-agenda__col supl-assign" data-assign>
                    <div class="supl-assign__head">
                        <span class="supl-hora-card__tile supl-assign__tile" data-assign-tile>
                            <strong>—</strong><small></small>
                        </span>
                        <div>
                            <div class="supl-assign__title" data-assign-title>Elige una hora</div>
                            <div class="supl-assign__sub" data-assign-sub>Selecciona a la izquierda la hora que quieres cubrir.</div>
                        </div>
                    </div>
                    <div class="supl-assign__body" data-assign-body>
                        <p class="supl-week-empty"><i class="fa-solid fa-hand-pointer"></i> Ninguna hora seleccionada.</p>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>

<!-- Formulario oculto para asignar (lo dispara el JS) -->
<form method="POST" action="/dashboard/suplencias/agendar" id="supl-asignar-form" style="display:none;">
    <input type="hidden" name="id" value="<?= (int)$suplencia->id ?>">
    <input type="hidden" name="_accion" value="asignar">
    <input type="hidden" name="hora_id" id="supl-asignar-hora">
    <input type="hidden" name="suplente_id" id="supl-asignar-suplente">
</form>
