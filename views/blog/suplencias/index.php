<?php $paginaVista = 'blog-suplencias-index'; ?>
<?php
/** @var \Model\Suplencia[] $suplencias  @var array $conteos  @var array $filtros  @var bool $puedeAgendar */
$mesesEs = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
$diasEs  = ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'];
$badgeEstado = [
    'solicitada'     => ['supl-badge--warn', 'Solicitada'],
    'agendada'       => ['supl-badge--info', 'Agendada'],
    'en_curso'       => ['supl-badge--info', 'En curso'],
    'por_justificar' => ['supl-badge--bad',  'Por justificar'],
    'completada'     => ['supl-badge--ok',   'Completada'],
    'cancelada'      => ['supl-badge--muted','Cancelada'],
];
function _avatarChip($nombre, $avatar) {
    $ini = $nombre ? mb_strtoupper(mb_substr($nombre, 0, 1)) : '—';
    $inner = $avatar ? '<img src="' . htmlspecialchars($avatar) . '" alt="">' : htmlspecialchars($ini);
    $sinAsignar = $nombre ? '' : ' supl-person--empty';
    return '<span class="supl-person' . $sinAsignar . '"><span class="supl-person__ava">' . $inner . '</span>'
         . '<span class="supl-person__name">' . htmlspecialchars($nombre ?: 'Sin asignar') . '</span></span>';
}
$pendientes = (int)($conteos['solicitada'] ?? 0) + (int)($conteos['agendada'] ?? 0) + (int)($conteos['por_justificar'] ?? 0);
?>

<div class="admin-layout">
    <?php include __DIR__ . '/../_sidebar.php'; ?>

    <div class="admin-main">
        <header class="admin-topbar">
            <div class="admin-topbar__left">
                <?php /* Para un profesor la lista solo trae las suyas: llamarla "Agenda"
                         (de todo el claustro) sería engañoso. */ ?>
                <span class="admin-topbar__title"><?= s($titulo) ?></span>
            </div>
            <?php /* Sin botones de acción: abrir y solicitar se hacen desde el calendario,
                     que además dice para qué día. El tablero vive en el sidebar (admin). */ ?>
            <div class="admin-topbar__actions">
                <?php include __DIR__ . '/../_topbar-avatar.php'; ?>
            </div>
        </header>

        <main class="admin-content">
            <?php
            $suplToast = null;
            if (isset($_GET['success'])) $suplToast = ['title' => '¡Suplencia registrada!', 'msg' => 'Se creó correctamente.', 'icon' => 'fa-user-clock', 'color' => '#4D8ABB'];
            elseif (isset($_GET['deleted'])) $suplToast = ['title' => '¡Suplencia eliminada!', 'msg' => 'La lista está actualizada.', 'icon' => 'fa-circle-check', 'color' => '#4267ac'];
            ?>

            <div class="supl-stats">
                <div class="supl-stat supl-stat--total">
                    <div class="supl-stat__ico"><i class="fa-solid fa-calendar-day"></i></div>
                    <div><div class="supl-stat__val"><?= (int)$conteos['total'] ?></div><div class="supl-stat__lbl">Total</div></div>
                </div>
                <div class="supl-stat supl-stat--ok">
                    <div class="supl-stat__ico"><i class="fa-solid fa-circle-check"></i></div>
                    <div><div class="supl-stat__val"><?= (int)($conteos['completada'] ?? 0) ?></div><div class="supl-stat__lbl">Completadas</div></div>
                </div>
                <div class="supl-stat supl-stat--warn">
                    <div class="supl-stat__ico"><i class="fa-solid fa-hourglass-half"></i></div>
                    <div><div class="supl-stat__val"><?= $pendientes ?></div><div class="supl-stat__lbl">En proceso</div></div>
                </div>
                <div class="supl-stat supl-stat--bad">
                    <div class="supl-stat__ico"><i class="fa-solid fa-triangle-exclamation"></i></div>
                    <div><div class="supl-stat__val"><?= (int)($conteos['por_justificar'] ?? 0) ?></div><div class="supl-stat__lbl">Por justificar</div></div>
                </div>
            </div>

            <?php /* ── Calendario ──
                     Sustituye a los botones que había en el topbar: aquí la acción llega
                     con la fecha puesta, en vez de obligar a elegirla después. Cualquier
                     día es pulsable (no solo los que ya tienen ausencias), porque también
                     sirve para abrir una futura. El badge cuenta las suplencias del día.
                     Los datos van en isla JSON, no interpolados en el JS. */ ?>
            <div class="supl-cal-wrap">
                <div class="bilbao-cal supl-cal" id="suplCal"
                     data-dias='<?= htmlspecialchars(json_encode($resumenDias ?? [], JSON_UNESCAPED_UNICODE), ENT_QUOTES) ?>'
                     data-hoy="<?= date('Y-m-d') ?>">
                    <div class="bilbao-cal__header">
                        <button type="button" class="bilbao-cal__nav" data-cal-prev aria-label="Mes anterior"><i class="fa-solid fa-chevron-left"></i></button>
                        <span class="bilbao-cal__month" data-cal-label></span>
                        <button type="button" class="bilbao-cal__nav" data-cal-next aria-label="Mes siguiente"><i class="fa-solid fa-chevron-right"></i></button>
                    </div>
                    <div class="bilbao-cal__weekdays"><span>Dom</span><span>Lun</span><span>Mar</span><span>Mié</span><span>Jue</span><span>Vie</span><span>Sáb</span></div>
                    <div class="bilbao-cal__grid" data-cal-grid></div>
                </div>

                <?php /* Panel de acciones del día elegido. Las opciones dependen del rol:
                         abrir una ausencia ajena es de prefectura; solicitar, de cualquiera. */ ?>
                <div class="supl-cal-side" data-cal-side>
                    <div class="supl-cal-side__empty" data-cal-empty>
                        <i class="fa-regular fa-hand-pointer"></i>
                        <p>Elige un día del calendario para ver sus suplencias o abrir una nueva.</p>
                    </div>

                    <div class="supl-cal-side__panel" data-cal-panel hidden>
                        <p class="supl-cal-side__fecha" data-cal-fecha></p>
                        <p class="supl-cal-side__conteo" data-cal-conteo></p>

                        <div class="supl-cal-side__acts">
                            <?php if ($puedeAgendar): ?>
                            <a class="admin-btn admin-btn--primary" data-cal-crear href="/dashboard/suplencias/crear">
                                <i class="fa-solid fa-plus"></i> Abrir suplencia
                            </a>
                            <?php endif; ?>
                            <a class="admin-btn admin-btn--ghost" data-cal-solicitar href="/dashboard/suplencias/solicitar">
                                <i class="fa-solid fa-hand"></i> Solicitar
                            </a>
                        </div>

                        <button type="button" class="supl-cal-side__reset" data-cal-reset hidden>
                            <i class="fa-solid fa-xmark"></i> Ver todas las fechas
                        </button>
                    </div>
                </div>
            </div>

            <?php
            $estadoOpts = ['' => 'Todos los estados', 'solicitada' => 'Solicitadas', 'agendada' => 'Agendadas', 'por_justificar' => 'Por justificar', 'completada' => 'Completadas', 'cancelada' => 'Canceladas'];
            $estadoSel  = $filtros['estado'] ?? '';
            ?>
            <form method="GET" action="/dashboard/suplencias" class="supl-toolbar" id="suplFilters">
                <div class="supl-search">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="suplSearch" name="q" value="<?= htmlspecialchars($filtros['q'] ?? '') ?>" placeholder="Busca por profesor ausente o motivo…" autocomplete="off">
                </div>
                <div class="supl-select" data-select>
                    <input type="hidden" name="estado" value="<?= htmlspecialchars($estadoSel) ?>" data-select-value>
                    <button type="button" class="supl-select__btn" data-select-btn>
                        <span data-select-label><?= htmlspecialchars($estadoOpts[$estadoSel] ?? 'Todos los estados') ?></span>
                        <i class="fa-solid fa-chevron-down"></i>
                    </button>
                    <div class="supl-select__menu" data-select-menu>
                        <?php foreach ($estadoOpts as $val => $lbl): ?>
                        <button type="button" class="supl-select__opt<?= $estadoSel === $val ? ' is-active' : '' ?>" data-value="<?= $val ?>"><?= htmlspecialchars($lbl) ?></button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php /* Datepicker propio en vez de <input type="date">: mismo aspecto en todos
                         los navegadores. Aquí `$fechaHabiles = false` a propósito: son los
                         extremos de un RANGO de consulta, no la fecha de una clase, así que
                         acotar "del sábado al sábado" es perfectamente legítimo. En crear y
                         solicitar sí siguen bloqueados los fines de semana.
                         El `change` del hidden reenvía el formulario (blog-suplencias-index.js). */ ?>
                <div class="supl-fecha-filtro">
                    <?php
                    $fechaName = 'desde'; $fechaLabel = ''; $fechaHabiles = false;
                    $fechaValor = $filtros['desde'] ?? ''; $fechaPlaceholder = 'Desde';
                    include __DIR__ . '/../_campo-fecha.php';

                    $fechaName = 'hasta'; $fechaLabel = ''; $fechaHabiles = false;
                    $fechaValor = $filtros['hasta'] ?? ''; $fechaPlaceholder = 'Hasta';
                    include __DIR__ . '/../_campo-fecha.php';
                    ?>
                </div>
                <?php if (!empty($filtros['q']) || !empty($filtros['estado']) || !empty($filtros['desde']) || !empty($filtros['hasta'])): ?>
                <a href="/dashboard/suplencias" class="supl-filter supl-filter--clear">Limpiar</a>
                <?php endif; ?>
            </form>

            <div class="supl-panel">
                <?php if (empty($suplencias)): ?>
                    <div class="supl-empty">
                        <img src="/build/assets/img/alex/alex-espera.png" alt="Alex">
                        <p>No hay suplencias que coincidan.<br><a href="/dashboard/suplencias/solicitar" style="color:#4267ac;font-weight:600;">Solicita la primera →</a></p>
                    </div>
                <?php else: ?>
                <div style="overflow-x:auto;">
                    <table class="supl-table" id="suplTable" data-table data-table-per="12" data-table-noun="suplencias">
                        <thead>
                            <tr>
                                <th data-sort="date">Fecha</th>
                                <th data-sort="text">Profesor ausente</th>
                                <th data-sort="text">Origen</th>
                                <th data-sort="num">Cobertura</th>
                                <th data-sort="text">Estado</th>
                                <th class="supl-col-act">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($suplencias as $i => $s):
                            $ts = strtotime($s->fecha);
                            $hay = strtolower(($s->ausente_nombre ?? '') . ' ' . ($s->motivo ?? ''));
                            [$bc, $bl] = $badgeEstado[$s->estado] ?? ['supl-badge--warn', ucfirst($s->estado)];
                            $tot = (int)$s->total_horas; $val = (int)$s->horas_validadas;
                        ?>
                            <?php /* data-fecha: lo usa el calendario para filtrar la tabla al día pulsado */ ?>
                            <tr class="supl-row<?= $i >= 12 ? ' is-hidden' : '' ?>" data-pager-item
                                data-fecha="<?= htmlspecialchars($s->fecha) ?>"
                                data-search="<?= htmlspecialchars($hay) ?>">
                                <td data-val="<?= htmlspecialchars($s->fecha) ?>">
                                    <div class="supl-date">
                                        <span class="supl-date__tile">
                                            <span class="supl-date__d"><?= (int)date('d', $ts) ?></span>
                                            <span class="supl-date__mo"><?= $mesesEs[(int)date('n', $ts) - 1] ?></span>
                                        </span>
                                        <span class="supl-date__dow"><?= $diasEs[(int)date('w', $ts)] ?></span>
                                    </div>
                                </td>
                                <td data-val="<?= htmlspecialchars($s->ausente_nombre ?? '') ?>">
                                    <?= _avatarChip($s->ausente_nombre, $s->ausente_avatar) ?>
                                    <?php if ($s->motivo): ?><div class="supl-cell-muted" style="margin-top:2px;"><?= htmlspecialchars($s->motivo) ?></div><?php endif; ?>
                                </td>
                                <td data-val="<?= $s->origen === 'sin_aviso' ? 'Sin aviso' : 'Anticipada' ?>">
                                    <?php if ($s->origen === 'sin_aviso'): ?>
                                        <span class="supl-badge supl-badge--bad">Sin aviso</span>
                                    <?php else: ?>
                                        <span class="supl-badge supl-badge--info">Anticipada</span>
                                    <?php endif; ?>
                                </td>
                                <?php /* Ordena por % cubierto, que es lo que importa, no por horas absolutas */ ?>
                                <td data-val="<?= $tot ? round($val / $tot * 100) : 0 ?>">
                                    <span class="supl-cover">
                                        <span class="supl-cover__bar"><span style="width:<?= $tot ? round($val / $tot * 100) : 0 ?>%;"></span></span>
                                        <span class="supl-cover__txt"><?= $val ?>/<?= $tot ?> hrs</span>
                                    </span>
                                </td>
                                <td data-val="<?= htmlspecialchars($bl) ?>"><span class="supl-badge <?= $bc ?>"><?= $bl ?></span></td>
                                <td class="supl-col-act">
                                    <div class="supl-actions">
                                        <a href="/dashboard/suplencias/agendar?id=<?= (int)$s->id ?>" class="admin-act admin-act--edit" title="Ver / agendar"><i class="fa-solid fa-user-gear"></i></a>
                                        <?php if ($puedeAgendar): ?>
                                        <button type="button" class="admin-act admin-act--del" title="Eliminar"
                                                onclick="suplEliminar(<?= (int)$s->id ?>, '<?= htmlspecialchars(addslashes($s->ausente_nombre ?: 'esta suplencia')) ?>')">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div class="supl-noresults" id="suplNoResults">Ningún resultado para tu búsqueda en esta página.</div>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>

<div id="suplDeleteModal" style="display:none;position:fixed;inset:0;background:rgba(11,31,61,.55);z-index:2000;align-items:center;justify-content:center;padding:20px;">
    <div style="background:#fff;border-radius:18px;max-width:400px;width:100%;padding:28px;text-align:center;box-shadow:0 24px 60px rgba(0,0,0,.3);">
        <div style="width:56px;height:56px;border-radius:50%;background:#fde8e8;color:#b42318;display:grid;place-items:center;font-size:22px;margin:0 auto 16px;"><i class="fa-solid fa-triangle-exclamation"></i></div>
        <h3 style="font-size:18px;font-weight:800;color:#0B1F3D;margin-bottom:8px;">Eliminar suplencia</h3>
        <p style="font-size:13.5px;color:#64748b;line-height:1.6;margin-bottom:22px;">¿Eliminar la suplencia de <strong id="suplDeleteName"></strong>? Esta acción no se puede deshacer.</p>
        <form action="/dashboard/suplencias/eliminar" method="POST" style="display:flex;gap:10px;justify-content:center;">
            <input type="hidden" name="id" id="suplDeleteId">
            <button type="button" class="admin-btn admin-btn--ghost" onclick="document.getElementById('suplDeleteModal').style.display='none'">Cancelar</button>
            <button type="submit" class="admin-btn" style="background:#dc2626;color:#fff;">Sí, eliminar</button>
        </form>
    </div>
</div>

<?php if ($suplToast): ?>
<div id="alexToast" class="at-wrap" role="alert" aria-live="polite">
    <span class="at-stripe" style="background:<?= $suplToast['color'] ?>;"></span>
    <img src="/build/assets/img/alex/alex-mano.png" alt="Alex" class="at-alex">
    <div class="at-body">
        <p class="at-title" style="color:<?= $suplToast['color'] ?>;"><i class="fa-solid <?= $suplToast['icon'] ?>"></i> <?= $suplToast['title'] ?></p>
        <p class="at-msg"><?= $suplToast['msg'] ?></p>
    </div>
    <button class="at-close" onclick="cerrarAlexToast()" aria-label="Cerrar"><i class="fa-solid fa-xmark"></i></button>
    <span class="at-bar" style="background:<?= $suplToast['color'] ?>;"></span>
</div>
<?php endif; ?>
