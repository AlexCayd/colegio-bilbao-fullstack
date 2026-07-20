<?php $paginaVista = 'blog-revisiones-mis-revisiones'; ?>
<?php
// Unified list grouped by status (feedback first, then pending review)
$enRevision  = [];
$conFeedback = [];

foreach ($articulos as $a) {
    if ((int)($a->envio_revision ?? 0) === 1) {
        $enRevision[]  = ['tipo' => 'articulo', 'item' => $a];
    } elseif (!empty($a->comentario_revision)) {
        $conFeedback[] = ['tipo' => 'articulo', 'item' => $a];
    }
}
foreach ($noticias as $n) {
    if ((int)($n->envio_revision ?? 0) === 1) {
        $enRevision[]  = ['tipo' => 'noticia', 'item' => $n];
    } elseif (!empty($n->comentario_revision)) {
        $conFeedback[] = ['tipo' => 'noticia', 'item' => $n];
    }
}

$totalItems = count($enRevision) + count($conFeedback);
?>
<div class="admin-layout">
    <?php include __DIR__ . '/../_sidebar.php'; ?>

    <div class="admin-main">
        <header class="admin-topbar">
            <div class="admin-topbar__left">
                <div class="admin-breadcrumb">
                    <a href="/dashboard">Dashboard</a>
                    <i class="fa-solid fa-chevron-right"></i>
                    <span>Mis revisiones</span>
                </div>
                <span class="admin-topbar__title">
                    <i class="fa-solid fa-rotate-left" style="margin-right:6px;color:var(--col-espiritu,#46bdc6);"></i>Mis revisiones
                    <?php if ($totalItems > 0): ?>
                    <span class="admin-badge" style="background:var(--col-bilbao,#1B4F8A);color:#fff;font-size:.72rem;margin-left:6px;"><?= $totalItems ?></span>
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

        <?php if (!empty($_GET['reenviado'])): ?>
        <div class="admin-alert admin-alert--success" style="margin-bottom:18px;">
            <i class="fa-solid fa-circle-check"></i> Tu contenido fue reenviado a revisión. El administrador lo revisará pronto.
        </div>
        <?php endif; ?>

        <?php if ($totalItems === 0): ?>
        <!-- EMPTY STATE -->
        <div class="mrv-empty">
            <img src="/build/assets/img/alex/alex-medita.png"
                 alt="Alex" class="mrv-empty__img">
            <div class="mrv-empty__body">
                <h2 class="mrv-empty__title">¡Todo al día!</h2>
                <p class="mrv-empty__sub">No tienes artículos ni noticias pendientes. Cuando envíes contenido al administrador o recibas retroalimentación, aparecerá aquí.</p>
                <div style="display:flex;gap:10px;flex-wrap:wrap;justify-content:center;margin-top:8px;">
                    <a href="/dashboard/articulos/crear" class="admin-btn admin-btn--primary">
                        <i class="fa-solid fa-plus"></i> Nuevo artículo
                    </a>
                    <a href="/dashboard/noticias/crear" class="admin-btn admin-btn--secondary">
                        <i class="fa-solid fa-plus"></i> Nueva noticia
                    </a>
                </div>
            </div>
        </div>

        <?php else: ?>

        <!-- STATS BAR -->
        <div class="mrv-stats">
            <?php if (count($conFeedback) > 0): ?>
            <div class="mrv-stat mrv-stat--feedback">
                <i class="fa-solid fa-comment-exclamation mrv-stat__icon"></i>
                <div>
                    <span class="mrv-stat__num"><?= count($conFeedback) ?></span>
                    <span class="mrv-stat__label">Requiere<?= count($conFeedback) > 1 ? 'n' : '' ?> cambios</span>
                </div>
            </div>
            <?php endif; ?>
            <?php if (count($enRevision) > 0): ?>
            <div class="mrv-stat mrv-stat--pending">
                <i class="fa-solid fa-hourglass-half mrv-stat__icon"></i>
                <div>
                    <span class="mrv-stat__num"><?= count($enRevision) ?></span>
                    <span class="mrv-stat__label">En revisión</span>
                </div>
            </div>
            <?php endif; ?>
            <p class="mrv-stats__hint">
                <?php if (count($conFeedback) > 0 && count($enRevision) > 0): ?>
                    Atiende el contenido con feedback y monitorea el que está en espera.
                <?php elseif (count($conFeedback) > 0): ?>
                    Edita el contenido con retroalimentación y reenvíalo para su aprobación.
                <?php else: ?>
                    El administrador revisará tu contenido pronto.
                <?php endif; ?>
            </p>
        </div>

        <!-- REQUIERE CAMBIOS -->
        <?php if (count($conFeedback) > 0): ?>
        <section class="mrv-section">
            <div class="mrv-section__header mrv-section__header--feedback">
                <i class="fa-solid fa-comment-exclamation"></i>
                Requiere cambios
                <span class="mrv-section__count"><?= count($conFeedback) ?></span>
            </div>

            <?php foreach ($conFeedback as $row):
                $item    = $row['item'];
                $tipo    = $row['tipo'];
                $editUrl = "/dashboard/{$tipo}s/editar?id=" . (int)$item->id;
                $portada = $tipo === 'articulo' ? ($item->imagen ?? '') : ($item->portada ?? '');
                $catNombre = $item->categoria_nombre ?? '';
                $catColor  = $item->categoria_color  ?? '#4267ac';
                $updatedAt = !empty($item->actualizado_en) ? date('d M Y, H:i', strtotime($item->actualizado_en)) : '';
            ?>
            <article class="mrv-card mrv-card--feedback">
                <div class="mrv-card__thumb">
                    <img src="<?= !empty($portada) ? htmlspecialchars($portada) : '/build/assets/img/blog/blog-placeholder.png' ?>"
                         alt="" loading="lazy">
                    <span class="mrv-tipo-tag"><?= $tipo === 'articulo' ? 'Artículo' : 'Noticia' ?></span>
                </div>
                <div class="mrv-card__body">
                    <div class="mrv-card__meta">
                        <?php if ($catNombre): ?>
                        <span class="mrv-cat" style="background:<?= htmlspecialchars($catColor) ?>;">
                            <?= htmlspecialchars($catNombre) ?>
                        </span>
                        <?php endif; ?>
                        <?php if ($updatedAt): ?>
                        <span class="mrv-card__date"><i class="fa-regular fa-clock"></i> <?= $updatedAt ?></span>
                        <?php endif; ?>
                    </div>
                    <h3 class="mrv-card__title"><?= htmlspecialchars($item->titulo) ?></h3>
                    <div class="mrv-feedback-inline">
                        <span class="mrv-feedback-inline__label">
                            <i class="fa-solid fa-comment-exclamation"></i> Retroalimentación del administrador
                        </span>
                        <p class="mrv-feedback-inline__text">
                            <?= nl2br(htmlspecialchars($item->comentario_revision)) ?>
                        </p>
                    </div>
                </div>
                <div class="mrv-card__action">
                    <a href="<?= $editUrl ?>" class="admin-btn admin-btn--primary mrv-action-btn">
                        <i class="fa-solid fa-pen-to-square"></i> Editar y reenviar
                    </a>
                    <p class="mrv-action-hint">Los cambios se envían al admin</p>
                </div>
            </article>
            <?php endforeach; ?>
        </section>
        <?php endif; ?>

        <!-- EN REVISIÓN -->
        <?php if (count($enRevision) > 0): ?>
        <section class="mrv-section" <?= count($conFeedback) > 0 ? 'style="margin-top:20px;"' : '' ?>>
            <div class="mrv-section__header mrv-section__header--pending">
                <i class="fa-solid fa-hourglass-half"></i>
                En revisión
                <span class="mrv-section__count"><?= count($enRevision) ?></span>
            </div>

            <?php foreach ($enRevision as $row):
                $item    = $row['item'];
                $tipo    = $row['tipo'];
                $portada = $tipo === 'articulo' ? ($item->imagen ?? '') : ($item->portada ?? '');
                $catNombre = $item->categoria_nombre ?? '';
                $catColor  = $item->categoria_color  ?? '#4267ac';
                $updatedAt = !empty($item->actualizado_en) ? date('d M Y, H:i', strtotime($item->actualizado_en)) : '';
            ?>
            <article class="mrv-card mrv-card--pending">
                <div class="mrv-card__thumb mrv-card__thumb--muted">
                    <img src="<?= !empty($portada) ? htmlspecialchars($portada) : '/build/assets/img/blog/blog-placeholder.png' ?>"
                         alt="" loading="lazy">
                    <span class="mrv-tipo-tag"><?= $tipo === 'articulo' ? 'Artículo' : 'Noticia' ?></span>
                </div>
                <div class="mrv-card__body">
                    <div class="mrv-card__meta">
                        <?php if ($catNombre): ?>
                        <span class="mrv-cat" style="background:<?= htmlspecialchars($catColor) ?>;">
                            <?= htmlspecialchars($catNombre) ?>
                        </span>
                        <?php endif; ?>
                        <?php if ($updatedAt): ?>
                        <span class="mrv-card__date"><i class="fa-regular fa-clock"></i> <?= $updatedAt ?></span>
                        <?php endif; ?>
                    </div>
                    <h3 class="mrv-card__title"><?= htmlspecialchars($item->titulo) ?></h3>
                    <p class="mrv-pending-note">
                        <i class="fa-solid fa-hourglass-half mrv-pulse"></i>
                        El administrador está revisando este contenido. Te avisaremos cuando haya una respuesta.
                    </p>
                </div>
                <div class="mrv-card__action mrv-card__action--locked">
                    <i class="fa-solid fa-lock" style="font-size:1.3rem;color:#CBD5E1;"></i>
                    <span class="mrv-locked-label">Bloqueado</span>
                </div>
            </article>
            <?php endforeach; ?>
        </section>
        <?php endif; ?>

        <?php endif; ?>
        </main>
    </div>
</div>

