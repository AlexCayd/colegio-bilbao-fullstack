<?php $paginaVista = 'blog-suplencias-index'; ?>
<?php
$mesesEs = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
$diasEs  = ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'];
$badgeEstado = [
    'confirmada' => ['supl-badge--ok',   'Confirmada'],
    'pendiente'  => ['supl-badge--warn', 'Pendiente'],
    'cancelada'  => ['supl-badge--bad',  'Cancelada'],
];
function _avatarChip($nombre, $avatar) {
    $ini = $nombre ? mb_strtoupper(mb_substr($nombre, 0, 1)) : '—';
    $inner = $avatar
        ? '<img src="' . htmlspecialchars($avatar) . '" alt="">'
        : htmlspecialchars($ini);
    $sinAsignar = $nombre ? '' : ' supl-person--empty';
    return '<span class="supl-person' . $sinAsignar . '"><span class="supl-person__ava">' . $inner . '</span>'
         . '<span class="supl-person__name">' . htmlspecialchars($nombre ?: 'Sin asignar') . '</span></span>';
}
?>

<div class="admin-layout">

    <?php include __DIR__ . '/../_sidebar.php'; ?>

    <div class="admin-main">

        <header class="admin-topbar">
            <div class="admin-topbar__left">
                <span class="admin-topbar__title">Suplencias</span>
            </div>
            <div class="admin-topbar__actions">
                <a href="/dashboard/suplencias/crear" class="admin-topbar__new-btn">
                    <i class="fa-solid fa-plus"></i> Nueva suplencia
                </a>
                <?php include __DIR__ . '/../_topbar-avatar.php'; ?>
                <form action="/logout" method="POST" style="display:flex;align-items:center;">
                    <button type="submit" class="admin-logout-btn"><i class="fa-solid fa-right-from-bracket"></i> Salir</button>
                </form>
            </div>
        </header>

        <main class="admin-content">

            <?php
            $suplToast = null;
            if (isset($_GET['success'])) $suplToast = ['title' => '¡Suplencia registrada!', 'msg' => 'La suplencia fue guardada correctamente.', 'icon' => 'fa-user-clock', 'color' => '#4D8ABB'];
            elseif (isset($_GET['edited'])) $suplToast = ['title' => '¡Cambios guardados!', 'msg' => 'La suplencia fue actualizada correctamente.', 'icon' => 'fa-floppy-disk', 'color' => '#319795'];
            elseif (isset($_GET['deleted'])) $suplToast = ['title' => '¡Suplencia eliminada!', 'msg' => 'La suplencia fue removida. La lista está actualizada.', 'icon' => 'fa-circle-check', 'color' => '#4267ac'];
            ?>

            <!-- Stats -->
            <div class="supl-stats">
                <div class="supl-stat supl-stat--total">
                    <div class="supl-stat__ico"><i class="fa-solid fa-calendar-day"></i></div>
                    <div><div class="supl-stat__val"><?= (int)$conteos['total'] ?></div><div class="supl-stat__lbl">Total</div></div>
                </div>
                <div class="supl-stat supl-stat--ok">
                    <div class="supl-stat__ico"><i class="fa-solid fa-circle-check"></i></div>
                    <div><div class="supl-stat__val"><?= (int)$conteos['confirmada'] ?></div><div class="supl-stat__lbl">Confirmadas</div></div>
                </div>
                <div class="supl-stat supl-stat--warn">
                    <div class="supl-stat__ico"><i class="fa-solid fa-hourglass-half"></i></div>
                    <div><div class="supl-stat__val"><?= (int)$conteos['pendiente'] ?></div><div class="supl-stat__lbl">Pendientes</div></div>
                </div>
                <div class="supl-stat supl-stat--bad">
                    <div class="supl-stat__ico"><i class="fa-solid fa-ban"></i></div>
                    <div><div class="supl-stat__val"><?= (int)$conteos['cancelada'] ?></div><div class="supl-stat__lbl">Canceladas</div></div>
                </div>
            </div>

            <!-- Toolbar de búsqueda / filtros -->
            <?php
            $estadoOpts = ['' => 'Todos los estados', 'pendiente' => 'Pendientes', 'confirmada' => 'Confirmadas', 'cancelada' => 'Canceladas'];
            $estadoSel  = $filtros['estado'] ?? '';
            ?>
            <form method="GET" action="/dashboard/suplencias" class="supl-toolbar" id="suplFilters">
                <div class="supl-search">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="suplSearch" name="q" value="<?= htmlspecialchars($filtros['q'] ?? '') ?>"
                           placeholder="Busca por ausente, suplente, grupo o materia…" autocomplete="off">
                </div>

                <!-- Select personalizado (estado) -->
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

                <input type="date" name="desde" value="<?= htmlspecialchars($filtros['desde'] ?? '') ?>" class="supl-filter" title="Desde" onchange="document.getElementById('suplFilters').submit()">
                <input type="date" name="hasta" value="<?= htmlspecialchars($filtros['hasta'] ?? '') ?>" class="supl-filter" title="Hasta" onchange="document.getElementById('suplFilters').submit()">
                <?php if (!empty($filtros['q']) || !empty($filtros['estado']) || !empty($filtros['desde']) || !empty($filtros['hasta'])): ?>
                <a href="/dashboard/suplencias" class="supl-filter supl-filter--clear">Limpiar</a>
                <?php endif; ?>
            </form>

            <!-- Tabla -->
            <div class="supl-panel">
                <?php if (empty($suplencias)): ?>
                    <div class="supl-empty">
                        <img src="/build/assets/img/alex/alex-espera.png" alt="Alex">
                        <p>No hay suplencias que coincidan.<br><a href="/dashboard/suplencias/crear" style="color:#4267ac;font-weight:600;">Registra la primera →</a></p>
                    </div>
                <?php else: ?>
                <div style="overflow-x:auto;">
                    <table class="supl-table" id="suplTable">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Docente ausente</th>
                                <th>Suplente</th>
                                <th>Grupo</th>
                                <th>Materia</th>
                                <th>Estado</th>
                                <th class="supl-col-act"></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($suplencias as $s):
                            $ts = strtotime($s->fecha);
                            $hay = strtolower(($s->ausente_nombre ?? '') . ' ' . ($s->suplente_nombre ?? '') . ' ' . ($s->grupo ?? '') . ' ' . ($s->materia ?? ''));
                            [$bc, $bl] = $badgeEstado[$s->estado] ?? ['supl-badge--warn', ucfirst($s->estado)];
                        ?>
                            <tr class="supl-row" data-search="<?= htmlspecialchars($hay) ?>">
                                <td>
                                    <div class="supl-date">
                                        <span class="supl-date__tile">
                                            <span class="supl-date__d"><?= (int)date('d', $ts) ?></span>
                                            <span class="supl-date__mo"><?= $mesesEs[(int)date('n', $ts) - 1] ?></span>
                                        </span>
                                        <span class="supl-date__dow"><?= $diasEs[(int)date('w', $ts)] ?></span>
                                    </div>
                                </td>
                                <td><?= _avatarChip($s->ausente_nombre, $s->ausente_avatar) ?></td>
                                <td><?= _avatarChip($s->suplente_nombre, $s->suplente_avatar) ?></td>
                                <td><span class="supl-cell-strong"><?= htmlspecialchars($s->grupo ?: '—') ?></span></td>
                                <td><span class="supl-cell-muted"><?= htmlspecialchars($s->materia ?: '—') ?></span></td>
                                <td><span class="supl-badge <?= $bc ?>"><?= $bl ?></span></td>
                                <td class="supl-col-act">
                                    <div class="supl-actions">
                                        <a href="/dashboard/suplencias/editar?id=<?= (int)$s->id ?>" class="supl-icon-btn supl-icon-btn--edit" title="Editar"><i class="fa-regular fa-pen-to-square"></i></a>
                                        <button type="button" class="supl-icon-btn supl-icon-btn--danger" title="Eliminar"
                                                onclick="suplEliminar(<?= (int)$s->id ?>, '<?= htmlspecialchars(addslashes($s->ausente_nombre ?: 'esta suplencia')) ?>')">
                                            <i class="fa-regular fa-trash-can"></i>
                                        </button>
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

<!-- Modal eliminar -->
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
