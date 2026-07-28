<?php $paginaVista = 'blog-notificaciones-index'; ?>
<?php
$totalNoLeidas = count(array_filter($notifs, fn($n) => !(int)$n->leida));
$total         = count($notifs);
$porPagina     = 8;

// Icono por módulo emisor y color por nivel: así se distingue de un vistazo de
// dónde viene cada aviso sin tener que leerlo entero.
$NOTIF_MOD = [
    'redaccion'  => ['icon' => 'fa-pen-nib',      'label' => 'Redacción'],
    'suplencias' => ['icon' => 'fa-user-clock',   'label' => 'Suplencias'],
    'horarios'   => ['icon' => 'fa-table-cells',  'label' => 'Horarios'],
    'eventos'    => ['icon' => 'fa-calendar-day', 'label' => 'Eventos'],
    'usuarios'   => ['icon' => 'fa-users-gear',   'label' => 'Usuarios'],
    'general'    => ['icon' => 'fa-bell',         'label' => 'General'],
];
?>
<div class="admin-layout">

    <?php include __DIR__ . '/../_sidebar.php'; ?>

    <div class="admin-main">
        <header class="admin-topbar">
            <div class="admin-topbar__left">
                <span class="admin-topbar__title">
                    <i class="fa-solid fa-bell"></i> Notificaciones
                </span>
            </div>
            <div class="admin-topbar__actions">
                <?php include __DIR__ . '/../_topbar-avatar.php'; ?>
            </div>
        </header>

        <div class="admin-content">
            <?php if (isset($_GET['limpiadas'])): ?>
            <div class="admin-alerta admin-alerta--success">
                <i class="fa-solid fa-circle-check"></i>
                Bandeja vaciada: se eliminaron <?= (int)$_GET['limpiadas'] ?> notificaciones.
            </div>
            <?php endif; ?>

            <div class="admin-panel nt-panel">
                <div class="nt-head">
                    <div class="nt-head__info">
                        <strong><?= $total ?> notificación<?= $total !== 1 ? 'es' : '' ?></strong>
                        <?php if ($totalNoLeidas > 0): ?>
                        <span class="nt-head__pend"><?= $totalNoLeidas ?> sin leer</span>
                        <?php endif; ?>
                    </div>
                    <?php if ($total > 0): ?>
                    <div class="nt-head__acts">
                        <?php if ($totalNoLeidas > 0): ?>
                        <form method="POST" action="/dashboard/notificaciones/leer-todas">
                            <button type="submit" class="admin-btn admin-btn--ghost admin-btn--sm">
                                <i class="fa-solid fa-check-double"></i> Marcar todas leídas
                            </button>
                        </form>
                        <?php endif; ?>
                        <button type="button" class="admin-btn admin-btn--ghost admin-btn--sm nt-clear" data-nt-clear>
                            <i class="fa-regular fa-trash-can"></i> Vaciar bandeja
                        </button>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if (!$notifs): ?>
                <div class="nt-empty">
                    <img src="/build/assets/img/alex/bby-alex-feliz.png" alt="">
                    <p><strong>Todo al día</strong></p>
                    <p>No tienes notificaciones pendientes.</p>
                </div>
                <?php else: ?>
                <ul class="nt-list" id="ntList">
                    <?php foreach ($notifs as $i => $notif): ?>
                    <?php
                    $esLeida = (int)$notif->leida;
                    $mod     = $NOTIF_MOD[$notif->modulo ?? 'general'] ?? $NOTIF_MOD['general'];
                    $nivel   = in_array($notif->nivel ?? 'info', ['info','exito','aviso','error'], true) ? $notif->nivel : 'info';
                    ?>
                    <li class="nt-row nt-row--<?= $nivel ?><?= $esLeida ? '' : ' is-unread' ?><?= $i >= $porPagina ? ' is-hidden' : '' ?>"
                        id="notif-<?= (int)$notif->id ?>" data-pager-item data-notif-id="<?= (int)$notif->id ?>">

                        <span class="nt-row__icon"><i class="fa-solid <?= $mod['icon'] ?>"></i></span>

                        <div class="nt-row__body">
                            <p class="nt-row__msg"><?= s($notif->mensaje) ?></p>
                            <p class="nt-row__meta">
                                <span class="nt-row__mod"><?= s($mod['label']) ?></span>
                                <?= $notif->creado_en ? '· ' . date('d M Y, H:i', strtotime($notif->creado_en)) : '' ?>
                                <?php if (!empty($notif->enlace)): ?>
                                · <a href="<?= s($notif->enlace) ?>" class="nt-row__link">Ver detalle <i class="fa-solid fa-arrow-right"></i></a>
                                <?php endif; ?>
                            </p>
                        </div>

                        <?php /* El payload viaja en data-* para poder restaurar la fila si se borra por error. */ ?>
                        <button type="button" class="nt-row__del" title="Marcar leída y eliminar"
                                data-nt-del
                                data-tipo="<?= s((string)$notif->tipo) ?>"
                                data-mensaje="<?= s((string)$notif->mensaje) ?>"
                                data-modulo="<?= s((string)($notif->modulo ?? 'general')) ?>"
                                data-nivel="<?= s($nivel) ?>"
                                data-enlace="<?= s((string)($notif->enlace ?? '')) ?>"
                                data-referencia-id="<?= (int)($notif->referencia_id ?? 0) ?>"
                                data-referencia-tipo="<?= s((string)($notif->referencia_tipo ?? '')) ?>">
                            <i class="fa-regular fa-circle-check"></i>
                        </button>
                    </li>
                    <?php endforeach; ?>
                </ul>

                <div class="nt-pager" data-pager data-pager-for="#ntList"
                     data-pager-per="<?= $porPagina ?>" data-pager-noun="notificaciones">
                    <button type="button" class="nt-pager__btn" data-pager-prev aria-label="Anteriores">
                        <i class="fa-solid fa-chevron-left"></i>
                    </button>
                    <span class="nt-pager__info" data-pager-info></span>
                    <button type="button" class="nt-pager__btn" data-pager-next aria-label="Siguientes">
                        <i class="fa-solid fa-chevron-right"></i>
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php /* Deshacer: la notificación ya se borró, esto la reinserta. */ ?>
<div class="nt-undo" id="ntUndo" hidden>
    <img src="/build/assets/img/alex/alex-mano.png" alt="Alex">
    <div class="nt-undo__body">
        <strong>Notificación eliminada</strong>
        <small>Se quitó de tu bandeja.</small>
    </div>
    <button type="button" class="nt-undo__btn" data-nt-undo>Deshacer</button>
    <span class="nt-undo__bar"></span>
</div>

<?php /* Vaciar bandeja es irreversible: pasa por confirmación explícita. */ ?>
<div class="nt-modal" id="ntModal" hidden>
    <div class="nt-modal__card">
        <span class="nt-modal__icon"><i class="fa-solid fa-triangle-exclamation"></i></span>
        <h3>¿Vaciar la bandeja?</h3>
        <p>Se eliminarán <strong><?= $total ?></strong> notificación<?= $total !== 1 ? 'es' : '' ?>.
           Esta acción no se puede deshacer.</p>
        <div class="nt-modal__acts">
            <button type="button" class="admin-btn admin-btn--ghost" data-nt-cancel>Cancelar</button>
            <form method="POST" action="/dashboard/notificaciones/limpiar">
                <button type="submit" class="admin-btn nt-modal__danger">
                    <i class="fa-regular fa-trash-can"></i> Sí, vaciar
                </button>
            </form>
        </div>
    </div>
</div>
