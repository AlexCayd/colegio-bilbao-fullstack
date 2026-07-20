<?php $paginaVista = 'blog-categorias-index'; ?>
<div class="admin-layout">

    <?php include __DIR__ . '/../_sidebar.php'; ?>

    <div class="admin-main">
        <header class="admin-topbar">
            <div class="admin-topbar__left">
                <div class="admin-breadcrumb">
                    <a href="/dashboard">Dashboard</a>
                    <i class="fa-solid fa-chevron-right"></i>
                    <span>Categorías</span>
                </div>
                <span class="admin-topbar__title">Categorías</span>
            </div>
            <div class="admin-topbar__actions">
                <a href="/dashboard/categorias/crear" class="admin-topbar__new-btn">
                    <i class="fa-solid fa-plus"></i> Nueva categoría
                </a>
                <?php include __DIR__ . '/../_topbar-avatar.php'; ?>
                <form action="/logout" method="POST" style="display:flex;align-items:center;">
                    <button type="submit" class="admin-logout-btn">
                        <i class="fa-solid fa-right-from-bracket"></i> Salir
                    </button>
                </form>
            </div>
        </header>

        <main class="admin-content">
            <div class="admin-panel">
                <div class="admin-panel__header">
                    <h2 class="admin-panel__title">
                        Todas las categorías
                        <span class="admin-panel__count"><?= count($categorias ?? []) ?></span>
                    </h2>
                    <a href="/dashboard/categorias/crear" class="admin-panel__action">+ Nueva</a>
                </div>

                <?php if (empty($categorias)): ?>
                <div class="admin-empty-state">
                    <img src="/build/assets/img/alex/alex-dice.png" alt="Alex" class="admin-empty-state__img">
                    <p class="admin-empty-state__text">Aún no hay categorías registradas.</p>
                    <a href="/dashboard/categorias/crear" class="admin-btn admin-btn--primary">
                        <i class="fa-solid fa-plus"></i> Crear primera categoría
                    </a>
                </div>

                <?php else: ?>
                <div style="overflow-x:auto;">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Slug</th>
                                <th>Artículos</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($categorias as $c): ?>
                            <tr>
                                <td>
                                    <div style="display:flex;align-items:center;gap:10px;">
                                        <span style="width:12px;height:12px;border-radius:3px;background:<?= s($c->color ?? '#4D8ABB') ?>;flex-shrink:0;display:inline-block;"></span>
                                        <div>
                                            <div class="admin-table__title"><?= s($c->nombre) ?></div>
                                            <?php if ($c->descripcion): ?>
                                            <div class="admin-table__meta" style="max-width:260px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= s($c->descripcion) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <code style="font-size:.8rem;background:#f3f4f6;padding:2px 6px;border-radius:4px;"><?= s($c->slug) ?></code>
                                </td>
                                <td style="font-weight:700;color:var(--col-herencia);"><?= (int)$c->total_articulos ?></td>
                                <td>
                                    <div class="admin-table__actions">
                                        <a href="/dashboard/categorias/editar?id=<?= (int)$c->id ?>" class="admin-table__btn">
                                            <i class="fa-regular fa-pen-to-square"></i> Editar
                                        </a>
                                        <?php if (($_SESSION['blog_usuario']['rol'] ?? '') === 'administrador'): ?>
                                        <button
                                            type="button"
                                            class="admin-table__btn admin-table__btn--danger"
                                            onclick="confirmarEliminar(<?= (int)$c->id ?>, '<?= s(addslashes($c->nombre)) ?>')"
                                            title="Eliminar categoría"
                                        >
                                            <i class="fa-regular fa-trash-can"></i>
                                        </button>
                                        <?php endif; ?>
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
        <div class="ubm-icon"><i class="fa-solid fa-tag"></i></div>
        <h2 class="ubm-title" id="ubm-title">¿Eliminar categoría?</h2>
        <p class="ubm-text">Estás a punto de eliminar <strong id="ubm-name"></strong>. Los artículos asociados quedarán sin clasificar. Esta acción no se puede deshacer.</p>
        <div class="ubm-field">
            <label class="ubm-field__label" for="ubm-input">Escribe el nombre para confirmar</label>
            <div class="ubm-field__wrap">
                <input type="text" id="ubm-input" class="ubm-field__input" placeholder="Nombre de la categoría" autocomplete="off" spellcheck="false">
                <i class="fa-solid fa-check ubm-field__check" id="ubm-check"></i>
            </div>
        </div>
        <form method="POST" action="/dashboard/categorias/eliminar" id="ubm-form">
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
    $atConfig = [
        'title' => '¡Categoría creada!',
        'msg'   => 'La nueva categoría ya está disponible para asignar a artículos.',
        'icon'  => 'fa-tag',
        'color' => '#4D8ABB',
        'bar'   => '#4D8ABB',
    ];
} elseif (isset($_GET['edited'])) {
    $atConfig = [
        'title' => '¡Cambios guardados!',
        'msg'   => 'La información de la categoría fue actualizada correctamente.',
        'icon'  => 'fa-floppy-disk',
        'color' => '#319795',
        'bar'   => '#319795',
    ];
} elseif (isset($_GET['deleted'])) {
    $atConfig = [
        'title' => '¡Categoría eliminada!',
        'msg'   => 'La categoría fue removida. La lista está actualizada.',
        'icon'  => 'fa-circle-check',
        'color' => '#38a169',
        'bar'   => '#38a169',
    ];
}
?>
<?php if ($atConfig): ?>
<div id="alexToast" class="at-wrap" role="alert" aria-live="polite">
    <span class="at-stripe" style="background:<?= $atConfig['bar'] ?>;"></span>
    <img src="/build/assets/img/alex/alex-dice.png" alt="Alex" class="at-alex">
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
