<?php $paginaVista = 'blog-autores-index'; ?>
<?php
$rolActual = $_SESSION['blog_usuario']['rol'] ?? '';
?>

<div class="admin-layout">

    <?php include __DIR__ . '/../_sidebar.php'; ?>

    <div class="admin-main">
        <header class="admin-topbar">
            <div class="admin-topbar__left">
                <div class="admin-breadcrumb">
                    <a href="/dashboard">Dashboard</a>
                    <i class="fa-solid fa-chevron-right"></i>
                    <span>Por autor</span>
                </div>
                <span class="admin-topbar__title">Contenido por autor</span>
            </div>
            <div class="admin-topbar__actions">
                <?php include __DIR__ . '/../_topbar-avatar.php'; ?>
                <form action="/logout" method="POST" style="display:flex;align-items:center;">
                    <button type="submit" class="admin-logout-btn"><i class="fa-solid fa-right-from-bracket"></i> Salir</button>
                </form>
            </div>
        </header>

        <main class="admin-content">

        <!-- BUSCADOR -->
        <div class="admin-panel" style="margin-bottom:20px;">
            <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;padding:20px 24px 16px;">
                <input type="text" id="buscarAutor" class="admin-input" placeholder="Buscar autor…"
                       style="max-width:280px;font-size:.9rem;" autocomplete="off">
                <span class="admin-badge" style="background:var(--col-bilbao);color:#fff;font-size:.8rem;padding:4px 12px;border-radius:99px;">
                    <?= count($autores) ?> autores
                </span>
            </div>
        </div>

        <!-- TARJETAS DE AUTORES -->
        <div class="admin-panel">
            <?php if (empty($autores)): ?>
            <div style="padding:60px 24px;text-align:center;color:#94A3B8;">
                <i class="fa-solid fa-users" style="font-size:2.5rem;margin-bottom:16px;display:block;opacity:.4;"></i>
                <p>No hay usuarios registrados.</p>
            </div>
            <?php else: ?>
            <div class="autores-grid" id="autoresGrid">
                <?php foreach ($autores as $autor):
                    $totalArts = (int)($autor->art_publicados ?? 0) + (int)($autor->art_borradores ?? 0) + (int)($autor->art_programados ?? 0);
                    $totalNots = (int)($autor->not_publicadas ?? 0) + (int)($autor->not_borradores ?? 0) + (int)($autor->not_programadas ?? 0);
                    $isAdmin   = $autor->rol === 'administrador';
                    $partes    = explode(' ', trim($autor->nombre ?? ''));
                    $iniciales = mb_strtoupper(mb_substr($partes[0] ?? '', 0, 1) . mb_substr($partes[1] ?? '', 0, 1));
                    $avatarBg  = '#374C69';
                ?>
                <div class="autor-card" data-nombre="<?= strtolower(s($autor->nombre)) ?>">
                    <div class="autor-card__header">
                        <div class="autor-card__avatar">
                            <div class="autor-card__initials" style="background:<?= $avatarBg ?>;"><?= s($iniciales) ?></div>
                            <?php if (!empty($autor->avatar)): ?>
                            <img src="<?= s($autor->avatar) ?>" alt="<?= s($autor->nombre) ?>">
                            <?php endif; ?>
                        </div>
                        <div class="autor-card__info">
                            <div class="autor-card__name">
                                <?= s($autor->nombre) ?>
                                <span class="admin-badge autor-card__rol <?= $isAdmin ? 'autor-card__rol--admin' : 'autor-card__rol--editor' ?>">
                                    <i class="fa-solid <?= $isAdmin ? 'fa-crown' : 'fa-pen' ?>"></i>
                                    <?= $isAdmin ? 'Admin' : 'Editor' ?>
                                </span>
                            </div>
                            <div class="autor-card__email"><?= s($autor->email) ?></div>
                        </div>
                        <a href="/dashboard/usuarios/editar?id=<?= (int)$autor->id ?>" class="autor-card__edit" title="Editar usuario">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </a>
                    </div>

                    <div class="autor-card__stats">
                        <div class="autor-stat">
                            <span class="autor-stat__num" style="color:var(--col-herencia);"><?= (int)($autor->art_publicados ?? 0) + (int)($autor->not_publicadas ?? 0) ?></span>
                            <span class="autor-stat__label">Publicados</span>
                        </div>
                        <div class="autor-stat">
                            <span class="autor-stat__num" style="color:var(--col-bilbao);"><?= (int)($autor->art_borradores ?? 0) + (int)($autor->not_borradores ?? 0) ?></span>
                            <span class="autor-stat__label">Borradores</span>
                        </div>
                        <div class="autor-stat">
                            <span class="autor-stat__num" style="color:#46bdc6;"><?= (int)($autor->art_programados ?? 0) + (int)($autor->not_programadas ?? 0) ?></span>
                            <span class="autor-stat__label">Programados</span>
                        </div>
                        <div class="autor-stat">
                            <span class="autor-stat__num" style="color:#34a853;"><?= $totalArts + $totalNots ?></span>
                            <span class="autor-stat__label">Total</span>
                        </div>
                    </div>

                    <?php if ($totalArts + $totalNots > 0): ?>
                    <button class="autor-card__toggle" data-autor-id="<?= (int)$autor->id ?>" aria-expanded="false">
                        <i class="fa-solid fa-chevron-down"></i>
                        Ver contenido (<?= $totalArts + $totalNots ?>)
                    </button>
                    <div class="autor-contenido" id="contenido-<?= (int)$autor->id ?>" hidden>
                        <?php if ($detalleAutorId === (int)$autor->id && !empty($contenidoAutor)): ?>
                            <?php foreach ($contenidoAutor as $item):
                                $tipo     = $item['tipo'];
                                $editUrl  = $tipo === 'articulo'
                                    ? '/dashboard/articulos/editar?id=' . (int)$item['id']
                                    : '/dashboard/noticias/editar?id='   . (int)$item['id'];
                                $estado   = htmlspecialchars($item['estado']);
                            ?>
                            <a href="<?= $editUrl ?>" class="autor-item">
                                <span class="autor-item__tipo autor-item__tipo--<?= $tipo ?>">
                                    <i class="fa-solid <?= $tipo === 'articulo' ? 'fa-newspaper' : 'fa-bell' ?>"></i>
                                </span>
                                <span class="autor-item__titulo"><?= htmlspecialchars($item['titulo']) ?></span>
                                <span class="autor-item__estado autor-item__estado--<?= $estado ?>"><?= $estado ?></span>
                                <?php if (!empty($item['envio_revision'])): ?>
                                <span class="autor-item__revision" title="Pendiente de revisión"><i class="fa-solid fa-clock"></i></span>
                                <?php endif; ?>
                                <span style="font-size:.72rem;color:#CBD5E1;flex-shrink:0;">
                                    <i class="fa-regular fa-eye"></i> <?= (int)($item['vistas'] ?? 0) ?>
                                </span>
                            </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                        <div class="autor-contenido__loading"><i class="fa-solid fa-spinner fa-spin"></i> Cargando…</div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        </main>
    </div><!-- /.admin-main -->
</div><!-- /.admin-layout -->


