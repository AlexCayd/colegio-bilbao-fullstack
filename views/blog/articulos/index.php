<?php $paginaVista = 'blog-articulos-index'; ?>
<div class="admin-layout">

    <?php include __DIR__ . '/../_sidebar.php'; ?>

    <div class="admin-main">
        <header class="admin-topbar">
            <div class="admin-topbar__left">
                <div class="admin-breadcrumb">
                    <a href="/dashboard">Dashboard</a>
                    <i class="fa-solid fa-chevron-right"></i>
                    <span>Artículos</span>
                </div>
                <span class="admin-topbar__title">Artículos</span>
            </div>
            <div class="admin-topbar__actions">
                <a href="/dashboard/articulos/crear" class="admin-topbar__new-btn">
                    <i class="fa-solid fa-plus"></i> Nuevo artículo
                </a>
                <?php include __DIR__ . '/../_topbar-avatar.php'; ?>
                <form action="/logout" method="POST" style="display:flex;align-items:center;">
                    <button type="submit" class="admin-logout-btn"><i class="fa-solid fa-right-from-bracket"></i> Salir</button>
                </form>
            </div>
        </header>

        <main class="admin-content">
            <?php
            $estado = $estado ?? '';
            $filtroMeta = [
                'publicado'  => ['label' => 'Publicados',  'badge' => 'admin-badge--published', 'icon' => 'fa-regular fa-newspaper'],
                'borrador'   => ['label' => 'Borradores',  'badge' => 'admin-badge--draft',     'icon' => 'fa-regular fa-file-lines'],
                'programado' => ['label' => 'Programados', 'badge' => 'admin-badge--scheduled', 'icon' => 'fa-regular fa-calendar-check'],
            ];
            $filtro      = $estado ? ($filtroMeta[$estado] ?? null) : null;
            $tituloPanel = $filtro ? $filtro['label'] : 'Todos los artículos';
            ?>
            <div class="admin-panel">
                <div class="admin-panel__header">
                    <h2 class="admin-panel__title" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                        <?php if ($filtro): ?>
                            <i class="<?= $filtro['icon'] ?>" style="color:#1B4F8A;"></i>
                        <?php endif; ?>
                        <?= htmlspecialchars($tituloPanel) ?>
                        <span class="admin-panel__count"><?= count($articulos ?? []) ?></span>
                        <?php if ($filtro): ?>
                            <a href="/dashboard/articulos"
                               style="font-size:11px;font-weight:600;color:#64748B;text-decoration:none;padding:3px 10px;background:#F1F5F9;border-radius:20px;display:inline-flex;align-items:center;gap:5px;border:1px solid #E2E8F0;transition:background .15s;"
                               onmouseover="this.style.background='#E2E8F0'" onmouseout="this.style.background='#F1F5F9'">
                                <i class="fa-solid fa-xmark"></i> Limpiar filtro
                            </a>
                        <?php endif; ?>
                    </h2>
                    <a href="/dashboard/articulos/crear" class="admin-panel__action">+ Nuevo</a>
                </div>

                <?php if (empty($articulos)): ?>
                <div class="admin-empty-state">
                    <img src="/build/assets/img/alex/alex-lee.png" alt="Alex" class="admin-empty-state__img">
                    <?php if ($filtro): ?>
                        <p class="admin-empty-state__text">No hay artículos con estado <strong><?= htmlspecialchars($filtro['label']) ?></strong>.</p>
                        <a href="/dashboard/articulos" class="admin-btn admin-btn--primary">
                            <i class="fa-solid fa-list"></i> Ver todos los artículos
                        </a>
                    <?php else: ?>
                        <p class="admin-empty-state__text">Aún no hay artículos publicados.</p>
                        <a href="/dashboard/articulos/crear" class="admin-btn admin-btn--primary">
                            <i class="fa-solid fa-plus"></i> Crear primer artículo
                        </a>
                    <?php endif; ?>
                </div>

                <?php else: ?>
                <div class="admin-table-scroll">
                    <table class="admin-table at-fixed" id="tablaArticulos">
                        <colgroup>
                            <col style="width:28%">
                            <col style="width:16%">
                            <col style="width:13%">
                            <col style="width:13%">
                            <col style="width:9%">
                            <col style="width:9%">
                            <col style="width:12%">
                        </colgroup>
                        <thead>
                            <tr>
                                <th class="sortable" data-col="0">Título <span class="sort-icon">↕</span></th>
                                <th class="sortable" data-col="1" style="width:120px;max-width:120px;">Categoría <span class="sort-icon">↕</span></th>
                                <th class="sortable" data-col="2">Autor <span class="sort-icon">↕</span></th>
                                <th class="sortable" data-col="3">Estado <span class="sort-icon">↕</span></th>
                                <th class="sortable" data-col="4">Vistas <span class="sort-icon">↕</span></th>
                                <th class="sortable" data-col="5">Fecha <span class="sort-icon">↕</span></th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($articulos as $art): ?>
                            <tr>
                                <td data-val="<?= s($art->titulo) ?>" class="at-cell-truncate">
                                    <div class="at-title-line"><?= s($art->titulo) ?></div>
                                </td>
                                <td data-val="<?= s($art->categoria_nombre ?? '') ?>" class="at-cell-truncate">
                                    <?php if ($art->categoria_nombre): ?>
                                    <div style="display:flex;align-items:center;gap:5px;">
                                        <span style="width:8px;height:8px;border-radius:2px;background:<?= s($art->categoria_color ?? '#4D8ABB') ?>;flex-shrink:0;display:inline-block;"></span>
                                        <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= s($art->categoria_nombre) ?></span>
                                    </div>
                                    <?php else: ?>
                                    <span style="color:#A0AEC0;font-size:.82rem;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td data-val="<?= s($art->autor_nombre ?? '') ?>" class="at-cell-truncate" style="font-size:.83rem;">
                                    <?= $art->autor_nombre ? s($art->autor_nombre) : '<span style="color:#A0AEC0;">—</span>' ?>
                                </td>
                                <td data-val="<?= s($art->estado ?? 'borrador') ?>" style="white-space:nowrap;">
                                    <?php if (!empty($art->envio_revision)): ?>
                                    <span class="admin-badge" style="background:#fef9c3;color:#854d0e;font-size:.72rem;display:inline-flex;align-items:center;gap:3px;"><i class="fa-solid fa-clock"></i> En revisión</span>
                                    <?php elseif (!empty($art->comentario_revision)): ?>
                                    <span class="admin-badge admin-badge--changes"><i class="fa-solid fa-comment-exclamation"></i> Requiere cambios</span>
                                    <?php else:
                                    $badgeMap = [
                                        'publicado'  => ['class' => 'admin-badge--published',  'label' => 'Publicado'],
                                        'borrador'   => ['class' => 'admin-badge--draft',       'label' => 'Borrador'],
                                        'programado' => ['class' => 'admin-badge--scheduled',   'label' => 'Programado'],
                                    ];
                                    $badge = $badgeMap[$art->estado ?? 'borrador'] ?? $badgeMap['borrador'];
                                    ?>
                                    <span class="admin-badge <?= $badge['class'] ?>"><?= $badge['label'] ?></span>
                                    <?php endif; ?>
                                </td>
                                <td data-val="<?= (int)($art->vistas ?? 0) ?>" style="white-space:nowrap;font-size:.8rem;color:#718096;">
                                    <i class="fa-regular fa-eye" style="opacity:.5;"></i> <?= (int)($art->vistas ?? 0) ?>
                                </td>
                                <td data-val="<?= s($art->creado_en ?? '') ?>" style="white-space:nowrap;font-size:.8rem;color:#718096;">
                                    <?= $art->creado_en ? date('d M Y', strtotime($art->creado_en)) : '—' ?>
                                </td>
                                <td style="white-space:nowrap;">
                                    <div class="admin-table__actions">
                                        <a href="/dashboard/articulos/editar?id=<?= (int)$art->id ?>" class="admin-table__btn" title="Editar">
                                            <i class="fa-regular fa-pen-to-square"></i>
                                        </a>
                                        <?php if (($art->estado ?? '') === 'borrador' && empty($art->envio_revision) && ($esEditor ?? false) && (int)($art->autor_id ?? 0) === (int)($usuarioId ?? 0)): ?>
                                        <form method="POST" action="/dashboard/articulos/enviar-revision" style="display:inline;" id="revForm<?= (int)$art->id ?>">
                                            <input type="hidden" name="id" value="<?= (int)$art->id ?>">
                                            <button type="button" class="admin-table__btn btn-enviar-revision-inline" data-form-id="revForm<?= (int)$art->id ?>" style="background:#f0f4ff;color:#4267ac;border:1px solid #c7d2fe;" title="Enviar para revisión">
                                                <i class="fa-solid fa-paper-plane"></i>
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                        <button
                                            type="button"
                                            class="admin-table__btn admin-table__btn--danger"
                                            onclick="confirmarEliminar(<?= (int)$art->id ?>, '<?= s(addslashes($art->titulo)) ?>')"
                                            title="Eliminar"
                                        >
                                            <i class="fa-regular fa-trash-can"></i>
                                        </button>
                                    </div>
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

<!-- MODAL ELIMINAR -->
<div id="deleteModal" class="ubm-backdrop" aria-modal="true" role="dialog" aria-labelledby="ubm-title">
    <div class="ubm-card">
        <div class="ubm-icon"><i class="fa-solid fa-newspaper"></i></div>
        <h2 class="ubm-title" id="ubm-title">¿Eliminar artículo?</h2>
        <p class="ubm-text">Estás a punto de eliminar <strong id="ubm-name"></strong>. Esta acción no se puede deshacer.</p>
        <div class="ubm-field">
            <label class="ubm-field__label" for="ubm-input">Escribe el título para confirmar</label>
            <div class="ubm-field__wrap">
                <input type="text" id="ubm-input" class="ubm-field__input" placeholder="Título del artículo" autocomplete="off" spellcheck="false">
                <i class="fa-solid fa-check ubm-field__check" id="ubm-check"></i>
            </div>
        </div>
        <form method="POST" action="/dashboard/articulos/eliminar" id="ubm-form">
            <input type="hidden" name="id" id="ubm-id">
            <div class="ubm-actions">
                <button type="button" class="admin-btn admin-btn--ghost" onclick="cerrarModalEliminar()">Cancelar</button>
                <button type="submit" class="admin-btn ubm-confirm" id="ubm-submit" disabled>
                    <i class="fa-solid fa-trash-can"></i> Sí, eliminar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL CONFIRMAR ENVÍO A REVISIÓN -->
<div id="revisionModal" class="revision-confirm-modal" role="dialog" aria-modal="true">
    <div class="revision-confirm-modal__card">
        <div class="revision-confirm-modal__icon">
            <i class="fa-solid fa-paper-plane"></i>
        </div>
        <p class="revision-confirm-modal__title">¿Enviar a revisión?</p>
        <p class="revision-confirm-modal__text">Una vez enviado, no podrás editar este artículo hasta que el administrador lo revise.</p>
        <div class="revision-confirm-modal__actions">
            <button type="button" class="admin-btn admin-btn--secondary" id="btnRevCancelar">Cancelar</button>
            <button type="button" class="admin-btn admin-btn--primary" id="btnRevConfirmar">
                <i class="fa-solid fa-paper-plane"></i> Enviar a revisión
            </button>
        </div>
    </div>
</div>



<?php
$atConfig = null;
if (isset($_GET['success'])) {
    $atConfig = [
        'title' => '¡Artículo creado!',
        'msg'   => 'El artículo fue guardado correctamente y ya está disponible.',
        'icon'  => 'fa-newspaper',
        'color' => '#4D8ABB',
        'bar'   => '#4D8ABB',
    ];
} elseif (isset($_GET['edited'])) {
    $atConfig = [
        'title' => '¡Cambios guardados!',
        'msg'   => 'El artículo fue actualizado correctamente.',
        'icon'  => 'fa-floppy-disk',
        'color' => '#319795',
        'bar'   => '#319795',
    ];
} elseif (isset($_GET['deleted'])) {
    $atConfig = [
        'title' => '¡Artículo eliminado!',
        'msg'   => 'El artículo fue removido. La lista está actualizada.',
        'icon'  => 'fa-circle-check',
        'color' => '#38a169',
        'bar'   => '#38a169',
    ];
}
?>
<?php if ($atConfig): ?>
<div id="alexToast" class="at-wrap" role="alert" aria-live="polite">
    <span class="at-stripe" style="background:<?= $atConfig['bar'] ?>;"></span>
    <img src="/build/assets/img/alex/alex-lee.png" alt="Alex" class="at-alex">
    <div class="at-body">
        <p class="at-title" style="color:<?= $atConfig['color'] ?>;">
            <i class="fa-solid <?= $atConfig['icon'] ?>"></i> <?= $atConfig['title'] ?>
        </p>
        <p class="at-msg"><?= $atConfig['msg'] ?></p>
    </div>
    <button class="at-close" onclick="cerrarAlexToast()" aria-label="Cerrar">
        <i class="fa-solid fa-xmark"></i>
    </button>
    <span class="at-bar" style="background:<?= $atConfig['bar'] ?>;"></span>
</div>


<?php endif; ?>
