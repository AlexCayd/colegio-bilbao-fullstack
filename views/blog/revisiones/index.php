<?php $paginaVista = 'blog-revisiones-index'; ?>
<?php
$aprobado        = isset($_GET['aprobado']);
$rechazado       = isset($_GET['rechazado']);
$totalPendientes = count($articulos) + count($noticias);
?>

<div class="admin-layout">

    <?php include __DIR__ . '/../_sidebar.php'; ?>

    <div class="admin-main">
        <header class="admin-topbar">
            <div class="admin-topbar__left">
                <div class="admin-breadcrumb">
                    <a href="/dashboard">Dashboard</a>
                    <i class="fa-solid fa-chevron-right"></i>
                    <span>Revisiones</span>
                </div>
                <span class="admin-topbar__title">
                    Revisiones pendientes
                    <?php if ($totalPendientes > 0): ?>
                    <span class="admin-badge" style="background:#f59e0b;color:#fff;font-size:.75rem;margin-left:8px;"><?= $totalPendientes ?></span>
                    <?php endif; ?>
                </span>
            </div>
            <div class="admin-topbar__actions">
                <?php include __DIR__ . '/../_topbar-avatar.php'; ?>
                <form action="/logout" method="POST" style="display:flex;align-items:center;">
                    <button type="submit" class="admin-logout-btn"><i class="fa-solid fa-right-from-bracket"></i> Salir</button>
                </form>
            </div>
        </header>

        <main class="admin-content">

        <?php if ($aprobado): ?>
        <div class="admin-alert admin-alert--success"><i class="fa-solid fa-circle-check"></i> Contenido aprobado y publicado.</div>
        <?php elseif ($rechazado): ?>
        <div class="admin-alert admin-alert--info"><i class="fa-solid fa-message"></i> Retroalimentación enviada al editor.</div>
        <?php endif; ?>

        <?php if ($totalPendientes === 0): ?>
        <div class="admin-panel" style="text-align:center;padding:64px 24px;">
            <i class="fa-solid fa-check-circle" style="font-size:2.5rem;color:#34a853;display:block;margin-bottom:16px;"></i>
            <p style="font-size:1rem;color:#64748B;">Sin revisiones pendientes. ¡Todo al día!</p>
        </div>
        <?php else: ?>

        <!-- ARTÍCULOS PENDIENTES -->
        <?php if (!empty($articulos)): ?>
        <div class="admin-panel admin-panel--articulos">
            <div class="admin-panel__header admin-panel__header--articulos">
                <h2 class="admin-panel__title"><i class="fa-regular fa-newspaper"></i> Artículos (<?= count($articulos) ?>)</h2>
            </div>
            <div class="revision-list">
                <?php foreach ($articulos as $art): ?>
                <div class="revision-item" id="rev-art-<?= (int)$art->id ?>">
                    <div class="revision-item__portada">
                        <?php $imgArt = !empty($art->imagen) ? s($art->imagen) : '/build/assets/img/blog/blog-placeholder.png'; ?>
                        <img src="<?= $imgArt ?>" alt="<?= s($art->titulo) ?>" loading="lazy">
                    </div>
                    <div class="revision-item__info">
                        <div class="revision-item__meta">
                            <?php if (!empty($art->autor_avatar)): ?>
                            <img src="<?= s($art->autor_avatar) ?>" class="revision-item__avatar" alt="<?= s($art->autor_nombre) ?>">
                            <?php endif; ?>
                            <span class="revision-item__autor"><?= s($art->autor_nombre ?? 'Sin autor') ?></span>
                            <span class="revision-item__fecha"><?= date('d/m/Y', strtotime($art->actualizado_en ?? $art->creado_en)) ?></span>
                        </div>
                        <div class="revision-item__titulo"><?= s($art->titulo) ?></div>
                        <?php if (!empty($art->extracto)): ?>
                        <div class="revision-item__extracto"><?= s($art->extracto) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="revision-item__cta">
                        <a href="/dashboard/articulos/editar?id=<?= (int)$art->id ?>"
                           class="revision-item__read">
                            <i class="fa-solid fa-pen-to-square"></i>
                            Revisar
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- NOTICIAS PENDIENTES -->
        <?php if (!empty($noticias)): ?>
        <div class="admin-panel admin-panel--noticias" style="margin-top:24px;">
            <div class="admin-panel__header admin-panel__header--noticias">
                <h2 class="admin-panel__title"><i class="fa-regular fa-bell"></i> Noticias (<?= count($noticias) ?>)</h2>
            </div>
            <div class="revision-list">
                <?php foreach ($noticias as $not): ?>
                <div class="revision-item" id="rev-not-<?= (int)$not->id ?>">
                    <div class="revision-item__portada">
                        <?php $imgNot = !empty($not->portada) ? s($not->portada) : '/build/assets/img/blog/blog-placeholder.png'; ?>
                        <img src="<?= $imgNot ?>" alt="<?= s($not->titulo) ?>" loading="lazy">
                    </div>
                    <div class="revision-item__info">
                        <div class="revision-item__meta">
                            <?php if (!empty($not->autor_avatar)): ?>
                            <img src="<?= s($not->autor_avatar) ?>" class="revision-item__avatar" alt="<?= s($not->autor_nombre) ?>">
                            <?php endif; ?>
                            <span class="revision-item__autor"><?= s($not->autor_nombre ?? 'Sin autor') ?></span>
                            <span class="revision-item__fecha"><?= date('d/m/Y', strtotime($not->actualizado_en ?? $not->creado_en)) ?></span>
                        </div>
                        <div class="revision-item__titulo"><?= s($not->titulo) ?></div>
                        <?php if (!empty($not->extracto)): ?>
                        <div class="revision-item__extracto"><?= s($not->extracto) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="revision-item__cta">
                        <a href="/dashboard/noticias/editar?id=<?= (int)$not->id ?>"
                           class="revision-item__read">
                            <i class="fa-solid fa-pen-to-square"></i>
                            Revisar
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php endif; ?>

        </main>
    </div><!-- /.admin-main -->
</div><!-- /.admin-layout -->

