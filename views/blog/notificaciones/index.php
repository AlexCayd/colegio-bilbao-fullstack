<?php $paginaVista = 'blog-notificaciones-index'; ?>
<?php
$noLeidas = array_filter($notifs, fn($n) => !(int)$n->leida);
$totalNoLeidas = count($noLeidas);
?>
<div class="admin-layout">

    <?php include __DIR__ . '/../_sidebar.php'; ?>

    <div class="admin-main">
        <header class="admin-topbar">
            <div class="admin-topbar__left">
                <div class="admin-breadcrumb">
                    <a href="/dashboard">Dashboard</a>
                    <i class="fa-solid fa-chevron-right"></i>
                    <span>Notificaciones</span>
                </div>
                <span class="admin-topbar__title">
                    <i class="fa-solid fa-bell" style="margin-right:6px;"></i>Notificaciones
                    <?php if ($totalNoLeidas > 0): ?>
                    <span class="admin-nav__badge" style="position:static;margin-left:8px;"><?= $totalNoLeidas ?></span>
                    <?php endif; ?>
                </span>
            </div>
            <?php include __DIR__ . '/../_topbar-avatar.php'; ?>
        </header>

        <div class="admin-content">
            <?php if (!empty($_GET['marcadas'])): ?>
            <div class="admin-alert admin-alert--success" style="margin-bottom:16px;">
                <i class="fa-solid fa-check-circle"></i> Todas las notificaciones marcadas como leídas.
            </div>
            <?php endif; ?>

            <div class="admin-card" style="padding:0;overflow:hidden;">
                <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid #e9edf4;">
                    <p style="font-size:.9rem;color:#374C69;font-weight:600;margin:0;">
                        <?= count($notifs) ?> notificacion<?= count($notifs) !== 1 ? 'es' : '' ?>
                        <?php if ($totalNoLeidas > 0): ?>
                        · <span style="color:#e51022;"><?= $totalNoLeidas ?> sin leer</span>
                        <?php endif; ?>
                    </p>
                    <?php if ($totalNoLeidas > 0): ?>
                    <form method="POST" action="/dashboard/notificaciones/leer-todas" style="margin:0;">
                        <button type="submit" class="admin-btn admin-btn--ghost" style="font-size:.8rem;padding:6px 12px;">
                            <i class="fa-solid fa-check-double"></i> Marcar todas como leídas
                        </button>
                    </form>
                    <?php endif; ?>
                </div>

                <?php if (empty($notifs)): ?>
                <div style="padding:48px 20px;text-align:center;color:#A0AEC0;">
                    <i class="fa-regular fa-bell-slash" style="font-size:2.5rem;margin-bottom:12px;display:block;"></i>
                    <p style="font-size:.95rem;">No tienes notificaciones todavía.</p>
                </div>
                <?php else: ?>
                <ul class="notif-list" style="margin:0;padding:0;list-style:none;">
                    <?php foreach ($notifs as $notif): ?>
                    <?php
                    $esLeida = (int)$notif->leida;
                    $esAprobado = str_contains($notif->tipo ?? '', 'aprobado');
                    $iconClass = $esAprobado ? 'notif-row__icon--aprobado' : 'notif-row__icon--rechazado';
                    $iconFA    = $esAprobado ? 'fa-circle-check' : 'fa-circle-exclamation';
                    $enlace = null;
                    if (!empty($notif->referencia_id) && !empty($notif->referencia_tipo)) {
                        $enlace = $notif->referencia_tipo === 'articulo'
                            ? '/dashboard/articulos/editar?id=' . (int)$notif->referencia_id
                            : '/dashboard/noticias/editar?id=' . (int)$notif->referencia_id;
                    }
                    ?>
                    <li class="notif-row<?= !$esLeida ? ' notif-row--unread' : '' ?>" id="notif-<?= (int)$notif->id ?>">
                        <span class="notif-row__icon <?= $iconClass ?>">
                            <i class="fa-solid <?= $iconFA ?>"></i>
                        </span>
                        <div class="notif-row__body">
                            <p class="notif-row__msg"><?= s($notif->mensaje) ?></p>
                            <p class="notif-row__meta">
                                <?= $notif->creado_en ? date('d M Y, H:i', strtotime($notif->creado_en)) : '' ?>
                                <?php if ($enlace): ?>
                                · <a href="<?= $enlace ?>" style="color:#4267ac;text-decoration:none;">Ver <?= $notif->referencia_tipo ?></a>
                                <?php endif; ?>
                            </p>
                        </div>
                        <?php if (!$esLeida): ?>
                        <button type="button" class="notif-mark-read" title="Marcar como leída"
                                data-id="<?= (int)$notif->id ?>" onclick="marcarLeida(this)">
                            <i class="fa-regular fa-circle-check"></i>
                        </button>
                        <?php endif; ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

