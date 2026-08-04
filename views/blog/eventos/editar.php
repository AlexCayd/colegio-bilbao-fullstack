<?php $paginaVista = 'blog-eventos-editar'; ?>
<div class="admin-layout">
    <?php include __DIR__ . '/../_sidebar.php'; ?>
    <div class="admin-main">
        <header class="admin-topbar">
            <div class="admin-topbar__left"><span class="admin-topbar__title">Editar evento</span></div>
            <div class="admin-topbar__actions">
                <?php include __DIR__ . '/../_topbar-avatar.php'; ?>
            </div>
        </header>
        <main class="admin-content">
            <?php $action = '/dashboard/eventos/editar'; include __DIR__ . '/_form.php'; ?>
        </main>
    </div>
</div>
