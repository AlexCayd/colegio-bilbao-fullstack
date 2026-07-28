<?php
/**
 * Acciones del topbar del panel: ver sitio público · campana · avatar.
 * Lo incluyen todas las vistas del panel, así que la campana llega a todas ellas
 * sin tocarlas una por una.
 */
$_tbNombre  = $_SESSION['blog_usuario']['nombre'] ?? 'Admin';
$_tbInicial = mb_strtoupper(mb_substr($_tbNombre, 0, 1, 'UTF-8'), 'UTF-8');
$_tbAvatar  = $_SESSION['blog_usuario']['avatar'] ?? '';

// El contador lo reusa el modal de Alex en layout-admin.php para no repetir la consulta.
if (!isset($GLOBALS['_notifsPendientes'])) {
    $GLOBALS['_notifsPendientes'] = (!empty($_SESSION['blog_usuario']) && class_exists(\Model\Notificacion::class))
        ? \Model\Notificacion::noLeidasPorUsuario((int)$_SESSION['blog_usuario']['id'])
        : 0;
}
$_tbNotifs = (int) $GLOBALS['_notifsPendientes'];
?>
<a href="/" class="admin-topbar__site" target="_blank" rel="noopener" title="Ver sitio público" aria-label="Ver sitio público">
    <i class="fa-solid fa-arrow-up-right-from-square"></i>
</a>
<a href="/dashboard/notificaciones" class="admin-topbar__bell<?= $_tbNotifs > 0 ? ' has-pend' : '' ?>"
   title="<?= $_tbNotifs > 0 ? $_tbNotifs . ' notificación' . ($_tbNotifs === 1 ? '' : 'es') . ' sin leer' : 'Notificaciones' ?>"
   aria-label="Notificaciones">
    <i class="fa-regular fa-bell"></i>
    <?php if ($_tbNotifs > 0): ?>
        <span class="admin-topbar__bell-badge" data-notif-badge><?= $_tbNotifs > 99 ? '99+' : $_tbNotifs ?></span>
    <?php endif; ?>
</a>
<a href="/dashboard/perfil" class="admin-topbar__avatar" title="<?= htmlspecialchars($_tbNombre) ?> · Mi perfil">
    <?php if ($_tbAvatar): ?>
        <img src="<?= htmlspecialchars($_tbAvatar) ?>" alt="" style="width:100%;height:100%;object-fit:cover;">
    <?php else: ?>
        <?= htmlspecialchars($_tbInicial) ?>
    <?php endif; ?>
</a>
