<?php $paginaVista = 'blog-noticias-index'; ?>
<div class="admin-layout">

    <?php include __DIR__ . '/../_sidebar.php'; ?>

    <div class="admin-main">
        <header class="admin-topbar">
            <div class="admin-topbar__left">
                <div class="admin-breadcrumb">
                    <a href="/dashboard">Dashboard</a>
                    <i class="fa-solid fa-chevron-right"></i>
                    <span>Noticias</span>
                </div>
                <span class="admin-topbar__title">Noticias</span>
            </div>
            <div class="admin-topbar__actions">
                <a href="/dashboard/noticias/crear" class="admin-topbar__new-btn">
                    <i class="fa-solid fa-plus"></i> Nueva noticia
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
                'publicado'  => ['label' => 'Publicadas',   'badge' => 'admin-badge--published', 'icon' => 'fa-regular fa-newspaper'],
                'borrador'   => ['label' => 'Borradores',   'badge' => 'admin-badge--draft',     'icon' => 'fa-regular fa-file-lines'],
                'programado' => ['label' => 'Programadas',  'badge' => 'admin-badge--scheduled', 'icon' => 'fa-regular fa-calendar-check'],
            ];
            $filtro      = $estado ? ($filtroMeta[$estado] ?? null) : null;
            $tituloPanel = $filtro ? $filtro['label'] : 'Todas las noticias';
            ?>
            <div class="admin-panel">
                <div class="admin-panel__header">
                    <h2 class="admin-panel__title" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                        <?php if ($filtro): ?>
                            <i class="<?= $filtro['icon'] ?>" style="color:#1B4F8A;"></i>
                        <?php endif; ?>
                        <?= htmlspecialchars($tituloPanel) ?>
                        <span class="admin-panel__count"><?= count($noticias ?? []) ?></span>
                        <?php if ($filtro): ?>
                            <a href="/dashboard/noticias"
                               style="font-size:11px;font-weight:600;color:#64748B;text-decoration:none;padding:3px 10px;background:#F1F5F9;border-radius:20px;display:inline-flex;align-items:center;gap:5px;border:1px solid #E2E8F0;transition:background .15s;"
                               onmouseover="this.style.background='#E2E8F0'" onmouseout="this.style.background='#F1F5F9'">
                                <i class="fa-solid fa-xmark"></i> Limpiar filtro
                            </a>
                        <?php endif; ?>
                    </h2>
                    <a href="/dashboard/noticias/crear" class="admin-panel__action">+ Nueva</a>
                </div>

                <?php if (empty($noticias)): ?>
                <div class="admin-empty-state">
                    <img src="/build/assets/img/alex/alex-periodico.png" alt="Alex" class="admin-empty-state__img">
                    <?php if ($filtro): ?>
                        <p class="admin-empty-state__text">No hay noticias con estado <strong><?= htmlspecialchars($filtro['label']) ?></strong>.</p>
                        <a href="/dashboard/noticias" class="admin-btn admin-btn--primary">
                            <i class="fa-solid fa-list"></i> Ver todas las noticias
                        </a>
                    <?php else: ?>
                        <p class="admin-empty-state__text">Aún no hay noticias publicadas.</p>
                        <a href="/dashboard/noticias/crear" class="admin-btn admin-btn--primary">
                            <i class="fa-solid fa-plus"></i> Crear primera noticia
                        </a>
                    <?php endif; ?>
                </div>

                <?php else: ?>
                <div class="admin-table-scroll">
                    <table class="admin-table at-fixed" id="tablaNoticias">
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
                        <?php foreach ($noticias as $n): ?>
                            <tr>
                                <td data-val="<?= s($n->titulo) ?>" class="at-cell-truncate">
                                    <div class="at-title-line">
                                        <?php if ((int)($n->destacada ?? 0) === 1): ?>
                                        <i class="fa-solid fa-star" style="color:#f5b400;font-size:.72rem;vertical-align:middle;margin-right:4px;" title="Destacada"></i>
                                        <?php endif; ?>
                                        <?= s($n->titulo) ?>
                                    </div>
                                </td>
                                <td data-val="<?= s($n->categoria_nombre ?? '') ?>" class="at-cell-truncate">
                                    <?php if ($n->categoria_nombre): ?>
                                    <div style="display:flex;align-items:center;gap:5px;">
                                        <span style="width:8px;height:8px;border-radius:2px;background:<?= s($n->categoria_color ?? '#374C69') ?>;flex-shrink:0;display:inline-block;"></span>
                                        <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= s($n->categoria_nombre) ?></span>
                                    </div>
                                    <?php else: ?>
                                    <span style="color:#A0AEC0;font-size:.82rem;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td data-val="<?= s($n->autor_nombre ?? '') ?>" class="at-cell-truncate" style="font-size:.83rem;">
                                    <?= $n->autor_nombre ? s($n->autor_nombre) : '<span style="color:#A0AEC0;">—</span>' ?>
                                </td>
                                <td data-val="<?= s($n->estado ?? 'borrador') ?>" style="white-space:nowrap;">
                                    <?php if (!empty($n->envio_revision)): ?>
                                    <span class="admin-badge" style="background:#fef9c3;color:#854d0e;font-size:.72rem;display:inline-flex;align-items:center;gap:3px;"><i class="fa-solid fa-clock"></i> En revisión</span>
                                    <?php elseif (!empty($n->comentario_revision)): ?>
                                    <span class="admin-badge admin-badge--changes"><i class="fa-solid fa-comment-exclamation"></i> Requiere cambios</span>
                                    <?php else:
                                    $badgeMap = [
                                        'publicado'  => ['class' => 'admin-badge--published', 'label' => 'Publicado'],
                                        'borrador'   => ['class' => 'admin-badge--draft',     'label' => 'Borrador'],
                                        'programado' => ['class' => 'admin-badge--scheduled', 'label' => 'Programado'],
                                    ];
                                    $badge = $badgeMap[$n->estado ?? 'borrador'] ?? $badgeMap['borrador'];
                                    ?>
                                    <span class="admin-badge <?= $badge['class'] ?>"><?= $badge['label'] ?></span>
                                    <?php endif; ?>
                                </td>
                                <td data-val="<?= (int)($n->vistas ?? 0) ?>" style="white-space:nowrap;font-size:.8rem;color:#718096;">
                                    <i class="fa-regular fa-eye" style="opacity:.5;"></i> <?= (int)($n->vistas ?? 0) ?>
                                </td>
                                <td data-val="<?= s($n->creado_en ?? '') ?>" style="white-space:nowrap;font-size:.8rem;color:#718096;">
                                    <?= $n->creado_en ? date('d M Y', strtotime($n->creado_en)) : '—' ?>
                                </td>
                                <td style="white-space:nowrap;">
                                    <div class="admin-table__actions">
                                        <a href="/dashboard/noticias/editar?id=<?= (int)$n->id ?>" class="admin-table__btn" title="Editar">
                                            <i class="fa-regular fa-pen-to-square"></i>
                                        </a>
                                        <?php if (($n->estado ?? '') === 'borrador' && empty($n->envio_revision) && ($_SESSION['blog_usuario']['rol'] ?? '') === 'editor' && (int)($n->autor_id ?? 0) === (int)($usuarioId ?? 0)): ?>
                                        <form method="POST" action="/dashboard/noticias/enviar-revision" style="display:inline;" id="revNotForm<?= (int)$n->id ?>">
                                            <input type="hidden" name="id" value="<?= (int)$n->id ?>">
                                            <button type="button" class="admin-table__btn" style="background:#f0f4ff;color:#4267ac;border:1px solid #c7d2fe;" title="Enviar para revisión"
                                                data-form-id="revNotForm<?= (int)$n->id ?>" onclick="abrirRevisionModal(this.dataset.formId)">
                                                <i class="fa-solid fa-paper-plane"></i>
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                        <button
                                            type="button"
                                            class="admin-table__btn admin-table__btn--danger"
                                            onclick="confirmarEliminar(<?= (int)$n->id ?>, '<?= s(addslashes($n->titulo)) ?>')"
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

<!-- MODAL REVISIÓN -->
<div id="revisionModal" class="revision-confirm-modal" role="dialog" aria-modal="true" aria-label="Confirmar envío a revisión">
    <div class="revision-confirm-modal__card">
        <div class="revision-confirm-modal__icon"><i class="fa-solid fa-paper-plane"></i></div>
        <p class="revision-confirm-modal__title">¿Enviar a revisión?</p>
        <p class="revision-confirm-modal__text">Una vez enviada, no podrás editar esta noticia hasta que el administrador la revise.</p>
        <div class="revision-confirm-modal__actions">
            <button type="button" class="admin-btn admin-btn--ghost" id="revisionModalCancel">Cancelar</button>
            <button type="button" class="admin-btn admin-btn--primary" id="revisionModalConfirm">
                <i class="fa-solid fa-paper-plane"></i> Enviar a revisión
            </button>
        </div>
    </div>
</div>

<!-- MODAL ELIMINAR -->
<div id="deleteModal" class="ubm-backdrop" aria-modal="true" role="dialog" aria-labelledby="ubm-title">
    <div class="ubm-card">
        <div class="ubm-icon"><i class="fa-solid fa-bullhorn"></i></div>
        <h2 class="ubm-title" id="ubm-title">¿Eliminar noticia?</h2>
        <p class="ubm-text">Estás a punto de eliminar <strong id="ubm-name"></strong>. Esta acción no se puede deshacer.</p>
        <div class="ubm-field">
            <label class="ubm-field__label" for="ubm-input">Escribe el título para confirmar</label>
            <div class="ubm-field__wrap">
                <input type="text" id="ubm-input" class="ubm-field__input" placeholder="Título de la noticia" autocomplete="off" spellcheck="false">
                <i class="fa-solid fa-check ubm-field__check" id="ubm-check"></i>
            </div>
        </div>
        <form method="POST" action="/dashboard/noticias/eliminar" id="ubm-form">
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



<?php
$atConfig = null;
if (isset($_GET['success'])) {
    $atConfig = ['title' => '¡Noticia creada!',   'msg' => 'La noticia fue guardada correctamente.', 'icon' => 'fa-bullhorn',     'color' => '#1a8a96', 'bar' => '#1a8a96'];
} elseif (isset($_GET['edited'])) {
    $atConfig = ['title' => '¡Cambios guardados!', 'msg' => 'La noticia fue actualizada correctamente.', 'icon' => 'fa-floppy-disk', 'color' => '#319795', 'bar' => '#319795'];
} elseif (isset($_GET['deleted'])) {
    $atConfig = ['title' => '¡Noticia eliminada!', 'msg' => 'La noticia fue removida correctamente.',    'icon' => 'fa-circle-check', 'color' => '#38a169', 'bar' => '#38a169'];
}
?>
<?php if ($atConfig): ?>
<div id="alexToast" class="at-wrap" role="alert" aria-live="polite">
    <span class="at-stripe" style="background:<?= $atConfig['bar'] ?>;"></span>
    <img src="/build/assets/img/alex/alex-periodico.png" alt="Alex" class="at-alex">
    <div class="at-body">
        <p class="at-title" style="color:<?= $atConfig['color'] ?>;"><i class="fa-solid <?= $atConfig['icon'] ?>"></i> <?= $atConfig['title'] ?></p>
        <p class="at-msg"><?= $atConfig['msg'] ?></p>
    </div>
    <button class="at-close" onclick="cerrarAlexToast()" aria-label="Cerrar"><i class="fa-solid fa-xmark"></i></button>
    <span class="at-bar" style="background:<?= $atConfig['bar'] ?>;"></span>
</div>
<?php endif; ?>
