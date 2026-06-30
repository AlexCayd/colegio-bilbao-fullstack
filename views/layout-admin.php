<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin — <?php echo htmlspecialchars($titulo); ?> | Colegio Bilbao</title>
    <link rel="shortcut icon" href="/build/assets/img/global/favicon.png" type="image/png">
    <link rel="icon" type="image/png" sizes="32x32" href="/build/assets/img/global/favicon.png">
    <link rel="apple-touch-icon" href="/build/assets/img/global/favicon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.2/css/all.min.css" integrity="sha512-1sCRPdkRXhBV2PBLUdRb4tMg1w2YPf37qatUFeS7zlBy7jJI8Lf4VHwWfZZfpXtYSLy85pkm9GaYVYMfw5BC1A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="/build/css/app.css">
</head>
<body class="admin-body">
    <?php echo $contenido; ?>

<?php
// Alex notification modal — shown once per unread notification
$_alexNotif = null;
if (!empty($_SESSION['blog_usuario']) && ($_SESSION['blog_usuario']['rol'] ?? '') === 'editor') {
    if (class_exists(\Model\Notificacion::class)) {
        $uid = (int)$_SESSION['blog_usuario']['id'];
        $recientes = \Model\Notificacion::porUsuario($uid, 5);
        foreach ($recientes as $_n) {
            if (!(int)$_n->leida) { $_alexNotif = $_n; break; }
        }
    }
}
?>
<?php if ($_alexNotif): ?>
<?php
$_esAprobado = str_contains($_alexNotif->tipo ?? '', 'aprobado');
$_enlace = null;
if (!empty($_alexNotif->referencia_id) && !empty($_alexNotif->referencia_tipo)) {
    $_enlace = '/dashboard/' . htmlspecialchars($_alexNotif->referencia_tipo) . 's/editar?id=' . (int)$_alexNotif->referencia_id;
}
?>
<div id="alexModal" class="alex-modal" data-notif-id="<?= (int)$_alexNotif->id ?>">
    <div class="alex-modal__card">
        <img src="/build/assets/img/alex/alex-tech.png"
             alt="Alex, mascota del Colegio Bilbao" class="alex-modal__img">
        <div class="alex-modal__body">
            <p class="alex-modal__tipo">
                <?= $_esAprobado ? '&#127881; ¡Publicado!' : '&#128221; Tienes feedback' ?>
            </p>
            <p class="alex-modal__msg"><?= htmlspecialchars($_alexNotif->mensaje) ?></p>
            <div class="alex-modal__actions">
                <?php if ($_enlace): ?>
                <a href="<?= $_enlace ?>" class="admin-btn admin-btn--primary alex-modal__cta">
                    <?= $_esAprobado ? 'Ver publicación' : 'Ver feedback' ?>
                </a>
                <?php endif; ?>
                <button type="button" class="admin-btn admin-btn--ghost" id="alexModalClose">
                    Entendido
                </button>
            </div>
        </div>
    </div>
</div>
<script>
(function() {
    const modal = document.getElementById('alexModal');
    const close = document.getElementById('alexModalClose');
    if (!modal || !close) return;

    setTimeout(() => modal.classList.add('is-open'), 300);

    function dismiss() {
        modal.classList.remove('is-open');
        setTimeout(() => modal.remove(), 350);
        fetch('/dashboard/notificaciones/leer', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
            body: 'id=' + encodeURIComponent(modal.dataset.notifId)
        });
    }

    close.addEventListener('click', dismiss);
    modal.addEventListener('click', function(e) { if (e.target === modal) dismiss(); });
})();
</script>
<?php endif; ?>
</body>
</html>
