<?php $paginaVista = 'blog-eventos-index'; ?>
<?php
/** @var \Model\Evento[] $eventos */
$tipoLabel = \Model\Evento::TIPO_LABEL;
$tipoColor = ['festivo' => '#e51022', 'evento' => '#4285f4', 'junta' => '#aa2296', 'entrega' => '#46bdc6', 'suspension' => '#f5b400'];
$mesesEs = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
?>
<div class="admin-layout">
    <?php include __DIR__ . '/../_sidebar.php'; ?>

    <div class="admin-main">
        <header class="admin-topbar">
            <div class="admin-topbar__left"><span class="admin-topbar__title">Eventos</span></div>
            <div class="admin-topbar__actions">
                <a href="/dashboard/eventos/crear" class="admin-topbar__new-btn"><i class="fa-solid fa-plus"></i> Nuevo evento</a>
                <?php include __DIR__ . '/../_topbar-avatar.php'; ?>
            </div>
        </header>

        <main class="admin-content">
            <?php
            $toast = null;
            if (isset($_GET['success'])) $toast = ['¡Evento creado!', '#4285f4', 'fa-calendar-plus'];
            elseif (isset($_GET['edited'])) $toast = ['¡Cambios guardados!', '#319795', 'fa-floppy-disk'];
            elseif (isset($_GET['deleted'])) $toast = ['¡Evento eliminado!', '#4267ac', 'fa-circle-check'];
            ?>
            <div class="admin-panel">
                <div class="admin-panel__header">
                    <h2 class="admin-panel__title">Calendario institucional <span class="admin-panel__count"><?= count($eventos ?? []) ?></span></h2>
                    <a href="/dashboard/eventos/crear" class="admin-panel__action">+ Nuevo</a>
                </div>

                <?php if (empty($eventos)): ?>
                <div class="admin-empty-state" style="padding:40px;">
                    <img src="/build/assets/img/alex/alex-point.png" alt="Alex" class="admin-empty-state__img">
                    <p class="admin-empty-state__text">Aún no hay eventos. Crea el primero para que aparezca en el calendario de Familias.</p>
                    <a href="/dashboard/eventos/crear" class="admin-btn admin-btn--primary"><i class="fa-solid fa-calendar-plus"></i> Nuevo evento</a>
                </div>
                <?php else: ?>
                <div class="ev-scroll">
                    <table class="admin-table" data-table data-table-per="10" data-table-noun="eventos">
                        <thead><tr>
                            <th data-sort="date">Fecha</th>
                            <th data-sort="text">Evento</th>
                            <th data-sort="text">Tipo</th>
                            <th data-sort="text">Visibilidad</th>
                            <th class="ev-col-act">Acciones</th>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($eventos as $i => $e): $ts = strtotime($e->fecha); $col = $tipoColor[$e->tipo] ?? '#94a3b8'; ?>
                            <tr data-pager-item<?= $i >= 10 ? ' class="is-hidden"' : '' ?>>
                                <td data-val="<?= s($e->fecha) ?>">
                                    <div class="ev-datecell" style="--c:<?= $col ?>;">
                                        <span class="ev-date">
                                            <strong class="ev-date__d"><?= (int)date('d', $ts) ?></strong>
                                            <span class="ev-date__mo"><?= $mesesEs[(int)date('n', $ts) - 1] ?></span>
                                        </span>
                                        <?php if ($e->fecha_fin && $e->fecha_fin !== $e->fecha): ?><span class="ev-range">→ <?= date('d M', strtotime($e->fecha_fin)) ?></span><?php endif; ?>
                                    </div>
                                </td>
                                <td data-val="<?= s($e->titulo) ?>">
                                    <div class="admin-table__title"><?= s($e->titulo) ?></div>
                                    <?php if ($e->descripcion): ?><div class="admin-table__meta"><?= s($e->descripcion) ?></div><?php endif; ?>
                                </td>
                                <td data-val="<?= s($tipoLabel[$e->tipo] ?? $e->tipo) ?>"><span class="ev-badge" style="--c:<?= $col ?>;"><?= s($tipoLabel[$e->tipo] ?? $e->tipo) ?></span></td>
                                <td data-val="<?= (int)$e->publico === 1 ? 'Familias' : 'Interno' ?>">
                                    <?php if ((int)$e->publico === 1): ?>
                                        <span class="ev-badge ev-badge--publico"><i class="fa-solid fa-eye"></i> Familias</span>
                                    <?php else: ?>
                                        <span class="ev-badge ev-badge--interno"><i class="fa-solid fa-eye-slash"></i> Interno</span>
                                    <?php endif; ?>
                                </td>
                                <td class="ev-col-act">
                                    <div class="ev-actions">
                                        <a href="/dashboard/eventos/editar?id=<?= (int)$e->id ?>" class="admin-act admin-act--edit" title="Editar evento" aria-label="Editar evento"><i class="fa-solid fa-pen"></i></a>
                                        <button type="button" class="admin-act admin-act--del" title="Eliminar evento" aria-label="Eliminar evento" onclick="eventoEliminar(<?= (int)$e->id ?>, '<?= s(addslashes($e->titulo)) ?>')"><i class="fa-solid fa-trash"></i></button>
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

<div id="evDeleteModal" class="ev-modal">
    <div class="ev-modal__box">
        <div class="ev-modal__ico"><i class="fa-solid fa-triangle-exclamation"></i></div>
        <h3 class="ev-modal__title">Eliminar evento</h3>
        <p class="ev-modal__text">¿Eliminar <strong id="evDeleteName"></strong>? Esta acción no se puede deshacer.</p>
        <form action="/dashboard/eventos/eliminar" method="POST" class="ev-modal__actions">
            <input type="hidden" name="id" id="evDeleteId">
            <button type="button" class="admin-btn admin-btn--ghost" onclick="cerrarModalEvento()">Cancelar</button>
            <button type="submit" class="admin-btn ev-btn-danger">Sí, eliminar</button>
        </form>
    </div>
</div>

<?php if ($toast): ?>
<div id="alexToast" class="at-wrap" role="alert" aria-live="polite">
    <span class="at-stripe" style="background:<?= $toast[1] ?>;"></span>
    <img src="/build/assets/img/alex/alex-mano.png" alt="Alex" class="at-alex">
    <div class="at-body"><p class="at-title" style="color:<?= $toast[1] ?>;"><i class="fa-solid <?= $toast[2] ?>"></i> <?= $toast[0] ?></p></div>
    <button class="at-close" onclick="cerrarAlexToast()" aria-label="Cerrar"><i class="fa-solid fa-xmark"></i></button>
    <span class="at-bar" style="background:<?= $toast[1] ?>;"></span>
</div>
<?php endif; ?>
