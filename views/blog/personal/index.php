<?php $paginaVista = 'blog-personal-index'; ?>
<?php
/** @var \Model\UsuarioBlog[] $usuarios  @var string $tipo  @var string $slug  @var string $titulo */
$avatarColors = ['#4D8ABB', '#374C69', '#38A169', '#E67E22', '#9B59B6', '#319795'];

$iconos = ['profesores' => 'fa-chalkboard-user', 'prefectura' => 'fa-user-shield',
           'administrativos' => 'fa-user-tie', 'directivos' => 'fa-user-gear'];
$icono  = $iconos[$slug] ?? 'fa-users';

$rolSesion = $_SESSION['blog_usuario']['rol'] ?? '';
$puedeEditar = $rolSesion === 'administrador';

function tipoChips(?string $tipos): string {
    // Los rótulos salen de la constante del modelo: la copia local que había aquí se
    // quedó sin `directivo` y ese tipo salía como un `ucfirst()` de casualidad.
    $labels = \Model\UsuarioBlog::TIPO_LABEL;
    $out = [];
    foreach (array_filter(array_map('trim', explode(',', (string)$tipos))) as $t) {
        $out[] = '<span class="admin-badge">' . htmlspecialchars($labels[$t] ?? ucfirst($t)) . '</span>';
    }
    return $out ? implode(' ', $out) : '<span style="color:#94A3B8;">—</span>';
}
function esDocente(?string $tipos): bool {
    return in_array('profesor', array_filter(array_map('trim', explode(',', (string)$tipos))), true);
}
/** Niveles que imparte, con el color de cada nivel. Vacío = se deducen de sus clases. */
function nivelChips(?string $niveles): string {
    $color = ['Maternal' => '#fc6722', 'Kinder' => '#f5b400', 'Primaria' => '#8ac926',
              'Secundaria' => '#46bdc6', 'Bachillerato' => '#4267ac'];
    $out = [];
    foreach (array_filter(array_map('trim', explode(',', (string)$niveles))) as $n) {
        $out[] = '<span class="per-nivel" style="--c:' . ($color[$n] ?? '#94a3b8') . ';">'
               . htmlspecialchars($n) . '</span>';
    }
    return $out ? implode(' ', $out)
                : '<span class="per-nivel per-nivel--auto" title="Sin declarar: se deducen de sus clases">Automático</span>';
}

// Solo los profesores cubren suplencias: en Prefectura y Administrativos la
// columna y el contador de "puede suplir" no significan nada, así que no se pintan.
$esDirectorioDocente = $slug === 'profesores';

$total    = count($usuarios ?? []);
$nSuplen  = 0;
$nDocente = 0;
foreach (($usuarios ?? []) as $u) {
    if (esDocente($u->tipo_personal)) {
        $nDocente++;
        if ((int)($u->puede_suplir ?? 1) === 1) $nSuplen++;
    }
}
?>

<div class="admin-layout">

    <?php include __DIR__ . '/../_sidebar.php'; ?>

    <div class="admin-main">
        <header class="admin-topbar">
            <div class="admin-topbar__left">
                <span class="admin-topbar__title"><?= s($titulo) ?></span>
            </div>
            <div class="admin-topbar__actions">
                <?php if ($puedeEditar): ?>
                <a href="/dashboard/usuarios/crear" class="admin-topbar__new-btn">
                    <i class="fa-solid fa-plus"></i> Nuevo usuario
                </a>
                <?php endif; ?>
                <?php include __DIR__ . '/../_topbar-avatar.php'; ?>
            </div>
        </header>

        <main class="admin-content">

            <?php /* Las tarjetas de resumen solo tienen sentido en Profesores: en Prefectura
                     y Administrativos la única cifra posible era el total de registros, que no
                     dice nada que la tabla no diga ya (y dejaba dos huecos en el grid de 3). */ ?>
            <?php if ($esDirectorioDocente): ?>
            <div class="per-stats">
                <div class="per-stat">
                    <span class="per-stat__n"><?= $total ?></span>
                    <span class="per-stat__l">en el directorio</span>
                </div>
                <div class="per-stat per-stat--ok">
                    <span class="per-stat__n"><?= $nSuplen ?></span>
                    <span class="per-stat__l">pueden suplir</span>
                </div>
                <div class="per-stat">
                    <span class="per-stat__n"><?= $nDocente ?></span>
                    <span class="per-stat__l">con horario docente</span>
                </div>
            </div>
            <?php endif; ?>

            <div class="admin-panel">
                <div class="admin-panel__header per-head">
                    <h2 class="admin-panel__title">
                        <i class="fa-solid <?= s($icono) ?>"></i> <?= s($titulo) ?>
                        <?php if ($esDirectorioDocente): ?>
                        <span class="admin-panel__count" data-per-count><?= $total ?></span>
                        <?php endif; ?>
                    </h2>
                    <?php if ($total): ?>
                    <?php /* El filtro "Solo quienes pueden suplir" se retiró: ordenar por la
                             columna "Puede suplir" hace lo mismo sin ocupar la barra. */ ?>
                    <div class="per-toolbar">
                        <div class="per-search">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="search" class="per-search__input" data-per-buscar placeholder="Buscar por nombre o correo…" aria-label="Buscar colaborador">
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if (empty($usuarios)): ?>
                <div class="admin-empty-state">
                    <img src="/build/assets/img/alex/alex-point.png" alt="Alex" class="admin-empty-state__img">
                    <p class="admin-empty-state__text">Aún no hay personal con este tipo. Edita un usuario y asígnale el tipo <strong><?= s($tipo) ?></strong>.</p>
                    <a href="/dashboard/usuarios" class="admin-btn admin-btn--primary"><i class="fa-solid fa-users"></i> Ir a Usuarios</a>
                </div>
                <?php else: ?>
                <div class="per-table-wrap">
                    <table class="admin-table per-table" id="perTable" data-table data-table-per="12" data-table-noun="colaboradores">
                        <thead>
                            <tr>
                                <th class="per-col-name" data-sort="text">Colaborador</th>
                                <th class="per-col-mail" data-sort="text">Email</th>
                                <th class="per-col-tipo" data-sort="text">Tipo de personal</th>
                                <?php if ($esDirectorioDocente): ?>
                                <th class="per-col-niv" data-sort="text">Niveles</th>
                                <th class="per-col-supl" data-sort="text">Puede suplir</th>
                                <?php endif; ?>
                                <th class="per-col-act">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($usuarios as $i => $u): ?>
                            <?php
                                $color   = $avatarColors[$u->id % count($avatarColors)];
                                $inicial = strtoupper(mb_substr($u->nombre, 0, 1));
                                $suple   = (int)($u->puede_suplir ?? 1) === 1;
                            ?>
                            <tr data-per-row data-pager-item<?= $i >= 12 ? ' class="is-hidden"' : '' ?>
                                data-nombre="<?= s(mb_strtolower($u->nombre . ' ' . $u->email . ' ' . (string)$u->niveles)) ?>">
                                <td data-val="<?= s($u->nombre) ?>">
                                    <div class="per-user">
                                        <div class="admin-topbar__avatar per-user__ava" style="background:<?= s($color) ?>;">
                                            <?php if ($u->avatar): ?>
                                                <img src="<?= s($u->avatar) ?>" alt="" onerror="this.parentElement.textContent='<?= s($inicial) ?>'">
                                            <?php else: ?><?= s($inicial) ?><?php endif; ?>
                                        </div>
                                        <div class="admin-table__title"><?= s($u->nombre) ?></div>
                                    </div>
                                </td>
                                <td class="per-mail"><?= s($u->email) ?></td>
                                <td data-val="<?= s((string)$u->tipo_personal) ?>"><?= tipoChips($u->tipo_personal) ?></td>
                                <?php if ($esDirectorioDocente): ?>
                                <td data-val="<?= s((string)$u->niveles) ?>"><?= nivelChips($u->niveles) ?></td>
                                <td data-val="<?= $suple ? 'Sí' : 'No' ?>">
                                    <?php if ($suple): ?>
                                        <span class="admin-badge admin-badge--published">Sí</span>
                                    <?php else: ?>
                                        <span class="admin-badge">No</span>
                                    <?php endif; ?>
                                </td>
                                <?php endif; ?>
                                <td>
                                    <div class="admin-table__actions">
                                        <?php if ($puedeEditar): ?>
                                        <a href="/dashboard/usuarios/editar?id=<?= (int)$u->id ?>" class="admin-act admin-act--edit" title="Editar"><i class="fa-solid fa-pen"></i></a>
                                        <?php endif; ?>
                                        <?php if (esDocente($u->tipo_personal)): ?>
                                        <a href="/dashboard/horarios/profesor?id=<?= (int)$u->id ?>" class="admin-act admin-act--horario" title="Ver horario"><i class="fa-regular fa-calendar"></i></a>
                                        <?php /* Editar es otra acción, no la misma: la de arriba abre el
                                                 módulo Horarios (solo lectura), ésta el editor. */ ?>
                                        <?php if ($puedeEditar): ?>
                                        <a href="/dashboard/usuarios/horario?id=<?= (int)$u->id ?>" class="admin-act admin-act--horario-edit" title="Editar horario"><i class="fa-regular fa-calendar-plus"></i></a>
                                        <?php endif; ?>
                                        <?php endif; ?>
                                        <?php if (!$puedeEditar && !esDocente($u->tipo_personal)): ?>
                                        <span class="per-nil">Solo lectura</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <p class="per-empty" data-per-empty hidden>Ningún colaborador coincide con la búsqueda.</p>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>
