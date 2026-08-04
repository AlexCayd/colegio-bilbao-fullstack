<?php $paginaVista = 'blog-eventos-crear'; ?>
<div class="admin-layout">
    <?php include __DIR__ . '/../_sidebar.php'; ?>
    <div class="admin-main">
        <header class="admin-topbar">
            <div class="admin-topbar__left"><span class="admin-topbar__title">Nuevo evento</span></div>
            <div class="admin-topbar__actions">
                <?php include __DIR__ . '/../_topbar-avatar.php'; ?>
            </div>
        </header>
        <main class="admin-content">
            <?php $action = '/dashboard/eventos/crear'; include __DIR__ . '/_form.php'; ?>
        </main>
    </div>
</div>
