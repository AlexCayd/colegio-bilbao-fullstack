<?php $paginaVista = 'blog-aulas-index'; ?>
<?php
$total = count($aulas ?? []);

// Feedback post-acción (mismo patrón de query params + toast de Alex del resto del panel)
$toast = null;
if     (isset($_GET['success'])) $toast = ['t' => '¡Aula creada!',     'm' => 'Ya puedes usarla en los horarios.', 'i' => 'fa-door-open',    'c' => '#34a853'];
elseif (isset($_GET['edited']))  $toast = ['t' => '¡Aula actualizada!', 'm' => 'Los cambios se guardaron.',         'i' => 'fa-pen',          'c' => '#4267ac'];
elseif (isset($_GET['deleted'])) $toast = ['t' => 'Aula eliminada',     'm' => 'El catálogo está actualizado.',     'i' => 'fa-circle-check', 'c' => '#4267ac'];
?>
<div class="admin-layout">

    <?php include __DIR__ . '/../_sidebar.php'; ?>

    <div class="admin-main">
        <header class="admin-topbar">
            <div class="admin-topbar__left">
                <span class="admin-topbar__title"><i class="fa-solid fa-door-open"></i> Aulas</span>
            </div>
            <div class="admin-topbar__actions">
                <a href="/dashboard/aulas/crear" class="admin-topbar__new-btn">
                    <i class="fa-solid fa-plus"></i> Nueva aula
                </a>
                <?php include __DIR__ . '/../_topbar-avatar.php'; ?>
            </div>
        </header>

        <main class="admin-content">

            <?php if (isset($_GET['enuso'])): ?>
            <div class="admin-alerta admin-alerta--error">
                <i class="fa-solid fa-circle-exclamation"></i>
                No se puede eliminar: <strong><?= (int)$_GET['enuso'] ?></strong> clase(s) o cobertura(s)
                usan esa aula. Reasígnalas primero desde el horario.
            </div>
            <?php endif; ?>

            <div class="cat-intro">
                <img src="/build/assets/img/alex/alex-point.png" alt="Alex">
                <div>
                    <strong>Catálogo de espacios</strong>
                    <span>Las aulas alimentan el horario y las suplencias. Al renombrar una, el cambio
                          se refleja en todas las clases que la usan.</span>
                </div>
            </div>

            <div class="admin-panel">
                <div class="admin-panel__header">
                    <h2 class="admin-panel__title">
                        Todas las aulas
                        <span class="admin-panel__count"><?= $total ?></span>
                    </h2>
                    <a href="/dashboard/aulas/crear" class="admin-panel__action">+ Nueva</a>
                </div>

                <?php if (!$total): ?>
                <div class="admin-empty-state">
                    <img src="/build/assets/img/alex/alex-espera.png" alt="Alex" class="admin-empty-state__img">
                    <p class="admin-empty-state__text">Todavía no hay aulas en el catálogo.</p>
                    <a href="/dashboard/aulas/crear" class="admin-btn admin-btn--primary">
                        <i class="fa-solid fa-plus"></i> Crear la primera
                    </a>
                </div>
                <?php else: ?>
                <div class="admin-table-scroll">
                    <table class="admin-table" data-table data-table-per="12" data-table-noun="aulas">
                        <thead>
                            <tr>
                                <th data-sort="text">Aula</th>
                                <th data-sort="text">Descripción</th>
                                <th data-sort="num">Clases asignadas</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($aulas as $i => $a): $uso = (int)$a->total_horarios; ?>
                            <tr data-pager-item<?= $i >= 12 ? ' class="is-hidden"' : '' ?>>
                                <td data-val="<?= s($a->nombre) ?>">
                                    <div class="cat-name"><i class="fa-solid fa-door-open"></i> <?= s($a->nombre) ?></div>
                                </td>
                                <td class="cat-desc"><?= $a->descripcion ? s($a->descripcion) : '<span class="cat-nil">—</span>' ?></td>
                                <td data-val="<?= $uso ?>">
                                    <?php if ($uso): ?>
                                        <span class="admin-badge admin-badge--published"><?= $uso ?></span>
                                    <?php else: ?>
                                        <span class="admin-badge">Sin uso</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="admin-table__actions">
                                        <a href="/dashboard/aulas/editar?id=<?= (int)$a->id ?>" class="admin-act admin-act--edit" title="Editar aula">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>
                                        <?php /* Con clases asignadas el borrado rompería el horario: se ofrece deshabilitado */ ?>
                                        <?php if ($uso): ?>
                                        <button type="button" class="admin-act admin-act--del" disabled
                                                title="No se puede eliminar: <?= $uso ?> clase(s) la usan">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                        <?php else: ?>
                                        <button type="button" class="admin-act admin-act--del" title="Eliminar aula"
                                                onclick="catEliminar(<?= (int)$a->id ?>, '<?= s(addslashes($a->nombre)) ?>')">
                                            <i class="fa-solid fa-trash"></i>
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

<?php
$catModal = ['accion' => '/dashboard/aulas/eliminar', 'que' => 'el aula'];
include __DIR__ . '/../_catalogo-modal.php';
?>

<?php if ($toast): ?>
<div class="at-wrap" id="alexToast">
    <span class="at-stripe" style="background:<?= $toast['c'] ?>;"></span>
    <img src="/build/assets/img/alex/alex-mano.png" alt="Alex" class="at-alex">
    <div class="at-body">
        <p class="at-title"><i class="fa-solid <?= $toast['i'] ?>" style="color:<?= $toast['c'] ?>;"></i> <?= s($toast['t']) ?></p>
        <p class="at-msg"><?= s($toast['m']) ?></p>
    </div>
    <button type="button" class="at-close" onclick="cerrarAlexToast()"><i class="fa-solid fa-xmark"></i></button>
    <span class="at-bar" style="background:<?= $toast['c'] ?>;"></span>
</div>
<?php endif; ?>
