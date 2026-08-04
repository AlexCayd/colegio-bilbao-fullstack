<?php
/**
 * Acciones del topbar del panel: ver sitio público · campana · avatar · salir.
 * Lo incluyen todas las vistas del panel, así que basta tocarlo aquí para que un
 * elemento salga en todas.
 *
 * El logout vive aquí desde que se detectó que las vistas ya migradas a este
 * partial (notificaciones, aulas, grupos) se habían quedado sin ningún control
 * para cerrar sesión: antes cada vista repetía su propio <form action="/logout">.
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
<?php /* "Ver sitio público" vive ahora en el sidebar, como última opción de la
         navegación: es una salida del panel, no una acción de la página actual. */ ?>
<a href="/dashboard/notificaciones" class="admin-topbar__bell<?= $_tbNotifs > 0 ? ' has-pend' : '' ?>"
   <?php /* El plural pierde la tilde: "notificación" + "es" daba "notificaciónes". */ ?>
   title="<?= $_tbNotifs > 0 ? $_tbNotifs . ($_tbNotifs === 1 ? ' notificación' : ' notificaciones') . ' sin leer' : 'Notificaciones' ?>"
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
<?php /* POST, no enlace: cerrar sesión cambia estado del servidor */ ?>
<form action="/logout" method="POST" class="admin-topbar__logout-form">
    <button type="submit" class="admin-topbar__logout" title="Cerrar sesión" aria-label="Cerrar sesión">
        <i class="fa-solid fa-right-from-bracket"></i>
    </button>
</form>
