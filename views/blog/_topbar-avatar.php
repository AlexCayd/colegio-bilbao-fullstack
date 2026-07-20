<?php
$_tbNombre  = $_SESSION['blog_usuario']['nombre'] ?? 'Admin';
$_tbInicial = mb_strtoupper(mb_substr($_tbNombre, 0, 1, 'UTF-8'), 'UTF-8');
$_tbAvatar  = $_SESSION['blog_usuario']['avatar'] ?? '';
?>
<a href="/" class="admin-topbar__site" target="_blank" rel="noopener" title="Ver sitio público" aria-label="Ver sitio público">
    <i class="fa-solid fa-arrow-up-right-from-square"></i>
</a>
<a href="/dashboard/perfil" class="admin-topbar__avatar" title="<?= htmlspecialchars($_tbNombre) ?> · Mi perfil">
    <?php if ($_tbAvatar): ?>
        <img src="<?= htmlspecialchars($_tbAvatar) ?>" alt="" style="width:100%;height:100%;object-fit:cover;">
    <?php else: ?>
        <?= htmlspecialchars($_tbInicial) ?>
    <?php endif; ?>
</a>
