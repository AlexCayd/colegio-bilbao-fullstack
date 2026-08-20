<?php $paginaVista = 'blog-suplencias-mis-coberturas'; ?>
<?php
/**
 * "Mis suplencias": las dos caras del ciclo para quien da clase — lo que ha cubierto y
 * lo que ha pedido— a lo largo del tiempo.
 *
 * La agenda de /dashboard/suplencias no es de aquí: expone motivos y justificantes de
 * todo el claustro y sirve para repartir ausencias, cosa que un profesor no hace.
 *
 * @var \Model\SuplenciaHora[] $pendientes   coberturas que reclaman confirmación
 * @var \Model\SuplenciaHora[] $coberturas   histórico completo, más reciente primero
 * @var array                  $pendIds      ids de $pendientes, para no repetir el CTA
 * @var \Model\Suplencia[]     $solicitadas  sus propias ausencias
 */
$mesesEs = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
$hoy     = date('Y-m-d');

$badgeEstado = [
    'solicitada'     => ['supl-badge--warn', 'Solicitada'],
    'agendada'       => ['supl-badge--info', 'Agendada'],
    'en_curso'       => ['supl-badge--info', 'En curso'],
    'por_justificar' => ['supl-badge--bad',  'Por justificar'],
    'completada'     => ['supl-badge--ok',   'Completada'],
    'cancelada'      => ['supl-badge--muted','Cancelada'],
];
// Estado de UNA hora cubierta. 'pendiente' aquí significa que la suplencia existe pero
// esa hora todavía no tiene suplente: no debería llegar a este listado, pero si un
// dato viejo lo hace, se rotula en vez de salir en blanco.
$badgeHora = [
    'pendiente' => ['supl-badge--muted', 'Sin asignar'],
    'agendada'  => ['supl-badge--info',  'Por confirmar'],
    'validada'  => ['supl-badge--ok',    'Confirmada'],
];

$totalCubiertas = count(array_filter($coberturas, fn($h) => $h->estado_hora === 'validada'));
?>
<div class="admin-layout">
    <?php include __DIR__ . '/../_sidebar.php'; ?>

    <div class="admin-main">
        <header class="admin-topbar">
            <div class="admin-topbar__left"><span class="admin-topbar__title">Mis suplencias</span></div>
            <div class="admin-topbar__actions">
                <?php include __DIR__ . '/../_topbar-avatar.php'; ?>
            </div>
        </header>

        <main class="admin-content">
            <?php /* El alta abre la pantalla y no el topbar. No cuelga de ninguno de los dos
                     paneles —no responde a una cobertura ni a una ausencia ya existente, crea
                     una nueva—, así que va arriba, que es donde se mira al entrar. */ ?>
            <div class="supl-barra">
                <a href="/dashboard/suplencias/solicitar" class="admin-new-btn">
                    <i class="fa-solid fa-hand"></i> Solicitar ausencia
                </a>
            </div>
            <?php if (isset($_GET['validado'])): ?>
            <div class="admin-alerta admin-alerta--exito" style="margin-bottom:16px;"><i class="fa-solid fa-circle-check"></i> ¡Gracias! Confirmaste la cobertura.</div>
            <?php endif; ?>
            <?php if (isset($_GET['solicitada'])): ?>
            <div class="admin-alerta admin-alerta--exito" style="margin-bottom:16px;"><i class="fa-solid fa-circle-check"></i> Tu ausencia quedó registrada. Prefectura la agendará.</div>
            <?php endif; ?>

            <div class="msu-stats">
                <div class="msu-stat">
                    <span class="msu-stat__n"><?= $totalCubiertas ?></span>
                    <span class="msu-stat__l">Clases cubiertas</span>
                </div>
                <div class="msu-stat<?= $pendientes ? ' msu-stat--warn' : '' ?>">
                    <span class="msu-stat__n"><?= count($pendientes) ?></span>
                    <span class="msu-stat__l">Por confirmar</span>
                </div>
                <div class="msu-stat">
                    <span class="msu-stat__n"><?= count($solicitadas) ?></span>
                    <span class="msu-stat__l">Ausencias solicitadas</span>
                </div>
            </div>

            <?php /* Lo que reclama acción, arriba y con su tarjeta completa. El histórico
                      de abajo también las contiene, pero enterradas entre las cerradas
                      nadie las confirmaría. */ ?>
            <?php if ($pendientes): ?>
            <div class="admin-panel">
                <div class="admin-panel__header">
                    <h2 class="admin-panel__title">
                        <i class="fa-solid fa-clipboard-check"></i> Coberturas por confirmar
                        <span class="admin-panel__count"><?= count($pendientes) ?></span>
                    </h2>
                </div>
                <div class="supl-cover-body">
                    <?php foreach ($pendientes as $h): ?>
                    <div class="supl-cover-card">
                        <div class="supl-cover-card__date">
                            <span class="supl-cover-card__d"><?= (int)date('d', strtotime($h->s_fecha)) ?></span>
                            <span class="supl-cover-card__mo"><?= $mesesEs[(int)date('n', strtotime($h->s_fecha)) - 1] ?></span>
                        </div>
                        <div class="supl-cover-card__info">
                            <span class="supl-cover-card__clase"><?= s($h->materia ?: 'Clase') ?><?php if ($h->grupo_nombre): ?> · <?= s($h->grupo_nombre) ?><?php endif; ?></span>
                            <span class="supl-cover-card__meta"><?= s($h->periodo_etiqueta) ?> · <?= substr($h->periodo_inicio, 0, 5) ?>–<?= substr($h->periodo_fin, 0, 5) ?> · Cubre a <?= s($h->ausente_nombre ?: '—') ?><?php if ($h->aula_nombre): ?> · <?= s($h->aula_nombre) ?><?php endif; ?></span>
                            <?php if (!empty($h->s_notas)): ?>
                            <span class="supl-cover-card__notas"><i class="fa-regular fa-note-sticky"></i> <?= s($h->s_notas) ?></span>
                            <?php endif; ?>
                        </div>
                        <?php /* Una cobertura futura todavía no se ha impartido: confirmarla
                                 por adelantado contradice lo que promete esta misma pantalla.
                                 El servidor lo impone igual en SuplenciaHora::validarHora(). */ ?>
                        <?php if ($h->s_fecha > $hoy): ?>
                        <div class="supl-cover-card__wait">
                            <button type="button" class="admin-btn admin-btn--ghost" disabled><i class="fa-regular fa-clock"></i> Aún no impartida</button>
                            <small>Podrás confirmarla el <?= s(fecha_larga($h->s_fecha)) ?>.</small>
                        </div>
                        <?php else: ?>
                        <form method="POST" action="/dashboard/suplencias/validar">
                            <input type="hidden" name="hora_id" value="<?= (int)$h->id ?>">
                            <button type="submit" class="admin-btn admin-btn--primary"><i class="fa-solid fa-check"></i> Confirmar</button>
                        </form>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php /* ── Histórico de coberturas ── */ ?>
            <div class="admin-panel">
                <div class="admin-panel__header">
                    <h2 class="admin-panel__title">
                        <i class="fa-solid fa-clock-rotate-left"></i> Clases que he cubierto
                        <span class="admin-panel__count"><?= count($coberturas) ?></span>
                    </h2>
                </div>

                <?php if (empty($coberturas)): ?>
                <div class="supl-cover-empty">
                    <img src="/build/assets/img/alex/bby-alex-feliz.png" alt="Alex">
                    <strong>Todavía no has cubierto ninguna clase</strong>
                    <p>
                        Cuando prefectura te asigne una cobertura aparecerá aquí, y podrás confirmarla
                        en cuanto la hayas impartido.
                    </p>
                </div>
                <?php else: ?>
                <div class="admin-table-scroll">
                    <table class="admin-table" data-table data-table-per="10" data-table-noun="coberturas">
                        <thead>
                            <tr>
                                <th data-sort="date">Fecha</th>
                                <th data-sort="text">Clase</th>
                                <th data-sort="text">Hora</th>
                                <th data-sort="text">Cubrí a</th>
                                <th data-sort="text">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($coberturas as $i => $h):
                                [$bc, $bl] = $badgeHora[$h->estado_hora] ?? ['supl-badge--muted', $h->estado_hora];
                                $esPend    = isset($pendIds[(int)$h->id]);
                            ?>
                            <tr data-pager-item<?= $i >= 10 ? ' class="is-hidden"' : '' ?>>
                                <td data-val="<?= s($h->s_fecha) ?>"><?= s(fecha_larga($h->s_fecha)) ?></td>
                                <td>
                                    <strong><?= s($h->materia ?: 'Clase') ?></strong>
                                    <?php if ($h->grupo_nombre): ?><br><small><?= s($h->grupo_nombre) ?><?php if ($h->aula_nombre): ?> · <?= s($h->aula_nombre) ?><?php endif; ?></small><?php endif; ?>
                                </td>
                                <td data-val="<?= s($h->periodo_inicio) ?>">
                                    <?= s($h->periodo_etiqueta) ?><br>
                                    <small><?= substr($h->periodo_inicio, 0, 5) ?>–<?= substr($h->periodo_fin, 0, 5) ?></small>
                                </td>
                                <td><?= s($h->ausente_nombre ?: '—') ?></td>
                                <td data-val="<?= s($bl) ?>">
                                    <span class="supl-badge <?= $bc ?>"><?= s($bl) ?></span>
                                    <?php /* El CTA solo baja aquí si la clase ya pasó y sigue sin
                                             confirmar; si no, la tarjeta de arriba ya lo ofrece. */ ?>
                                    <?php if ($esPend && $h->s_fecha <= $hoy): ?>
                                    <form method="POST" action="/dashboard/suplencias/validar" class="msu-inline-form">
                                        <input type="hidden" name="hora_id" value="<?= (int)$h->id ?>">
                                        <button type="submit" class="msu-mini-btn"><i class="fa-solid fa-check"></i> Confirmar</button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>

            <?php /* ── Histórico de ausencias propias ── */ ?>
            <div class="admin-panel">
                <div class="admin-panel__header">
                    <h2 class="admin-panel__title">
                        <i class="fa-solid fa-hand"></i> Mis ausencias
                        <span class="admin-panel__count"><?= count($solicitadas) ?></span>
                    </h2>
                    <a href="/dashboard/suplencias/solicitar" class="admin-btn admin-btn--ghost admin-btn--sm">
                        <i class="fa-solid fa-plus"></i> Solicitar
                    </a>
                </div>

                <?php if (empty($solicitadas)): ?>
                <div class="supl-cover-empty">
                    <img src="/build/assets/img/alex/bby-alex-saluda.png" alt="Alex">
                    <strong>No has solicitado ninguna ausencia</strong>
                    <p>
                        Si vas a faltar, avisa desde <strong>Solicitar</strong>: marca las clases que hay que
                        cubrir y prefectura se encarga de buscar suplente.
                    </p>
                </div>
                <?php else: ?>
                <div class="admin-table-scroll">
                    <table class="admin-table" data-table data-table-per="10" data-table-noun="ausencias">
                        <thead>
                            <tr>
                                <th data-sort="date">Fecha</th>
                                <th data-sort="text">Motivo</th>
                                <th data-sort="num">Horas cubiertas</th>
                                <th data-sort="text">Estado</th>
                                <th data-sort="text">Justificante</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($solicitadas as $i => $sup):
                                [$bc, $bl] = $badgeEstado[$sup->estado] ?? ['supl-badge--muted', $sup->estado];
                                $tot = (int)$sup->total_horas;
                                $val = (int)$sup->horas_validadas;
                                $pct = $tot ? round($val / $tot * 100) : 0;
                                $ej  = $sup->estadoJustificante();
                            ?>
                            <tr data-pager-item<?= $i >= 10 ? ' class="is-hidden"' : '' ?>>
                                <td data-label="Fecha" data-val="<?= s($sup->fecha) ?>"><?= s(fecha_larga($sup->fecha)) ?></td>
                                <td data-label="Motivo"><?= s($sup->motivo ?: '—') ?></td>
                                <td data-label="Horas cubiertas" data-val="<?= $pct ?>">
                                    <span class="supl-cover">
                                        <span class="supl-cover__bar"><span style="width:<?= $pct ?>%"></span></span>
                                        <span class="supl-cover__txt"><?= $val ?>/<?= $tot ?> confirmadas</span>
                                    </span>
                                </td>
                                <td data-label="Estado" data-val="<?= s($bl) ?>"><span class="supl-badge <?= $bc ?>"><?= s($bl) ?></span></td>
                                <?php /* ⚠️ Un profesor con la ausencia en `por_justificar` veía el
                                         badge rojo y NO tenía desde aquí ninguna vía para subir el
                                         archivo: las filas no eran clicables y su única pantalla de
                                         suplencias es esta. El enlace va a /agendar, que es donde
                                         vive el formulario de subida (y donde el guard ya le
                                         reconoce como el ausente). */ ?>
                                <td data-label="Justificante" data-val="<?= s($ej) ?>">
                                    <?php if ($ej === 'sin_archivo' && $sup->origen === 'sin_aviso'): ?>
                                        <a href="/dashboard/suplencias/agendar?id=<?= (int)$sup->id ?>" class="supl-jchip supl-jchip--miss supl-jchip--link">
                                            <i class="fa-solid fa-upload"></i> Subir justificante
                                        </a>
                                    <?php elseif ($ej === 'vigente' || $ej === 'en_cola'): ?>
                                        <a href="/dashboard/suplencias/agendar?id=<?= (int)$sup->id ?>" class="supl-jchip supl-jchip--ok supl-jchip--link">
                                            <i class="fa-solid fa-file-circle-check"></i> Entregado
                                        </a>
                                    <?php elseif ($ej === 'resuelto'): ?>
                                        <span class="supl-jchip supl-jchip--done"><i class="fa-solid fa-check"></i> Revisado</span>
                                    <?php else: ?>
                                        <span class="supl-cell-muted">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>
