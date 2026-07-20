<?php $paginaVista = 'blog-dashboard'; ?>
<?php
// ── Preparar datos para gráficas ──────────────────────────────────────────────

$esMonths    = [1=>'Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
$monthMap    = [];
$monthLabels = [];
// Anclar al día 1 del mes actual para evitar overflow en meses de 28/30 días
$now         = (new \DateTime())->modify('first day of this month');
for ($i = 5; $i >= 0; $i--) {
    $d              = clone $now;
    $d->modify("-{$i} month");
    $key            = $d->format('Y-m');
    $monthMap[$key] = 0;
    $monthLabels[]  = $esMonths[(int)$d->format('n')] . " '" . $d->format('y');
}
foreach ($porMes as $row) {
    if (isset($monthMap[$row['mes']])) {
        $monthMap[$row['mes']] = (int) $row['total'];
    }
}
$chartMonthData = array_values($monthMap);

$catLabels = [];
$catData   = [];
$catColors = [];
foreach ($porCat as $row) {
    $catLabels[] = $row['nombre'];
    $catData[]   = (int) $row['total'];
    $catColors[] = !empty($row['color']) ? $row['color'] : '#4D8ABB';
}

$jsMonthLabels = json_encode($monthLabels);
$jsMonthData   = json_encode($chartMonthData);
$jsCatLabels   = json_encode($catLabels);
$jsCatData     = json_encode($catData);
$jsCatColors   = json_encode($catColors);

// ── Noticias chart data ───────────────────────────────────────────────────────
$nMonthMap = [];
$nMonthLabels2 = [];
for ($i = 5; $i >= 0; $i--) {
    $d2 = clone $now; $d2->modify("-{$i} month");
    $key = $d2->format('Y-m');
    $nMonthMap[$key] = 0;
    $nMonthLabels2[] = $esMonths[(int)$d2->format('n')] . " '" . $d2->format('y');
}
foreach ($nPorMes as $row) {
    if (isset($nMonthMap[$row['mes']])) $nMonthMap[$row['mes']] = (int)$row['total'];
}
$nChartData = array_values($nMonthMap);
$nCatLabels = $nCatData = $nCatColors = [];
foreach ($nPorCat as $row) {
    $nCatLabels[] = $row['nombre'];
    $nCatData[]   = (int)$row['total'];
    $nCatColors[] = !empty($row['color']) ? $row['color'] : '#374C69';
}
$jsNMonthLabels = json_encode($nMonthLabels2);
$jsNMonthData   = json_encode($nChartData);
$jsNCatLabels   = json_encode($nCatLabels);
$jsNCatData     = json_encode($nCatData);
$jsNCatColors   = json_encode($nCatColors);

// Saludo dinámico
$hour = (int)(new \DateTime())->format('H');
if ($hour < 12)      $saludo = 'Buenos días';
elseif ($hour < 18)  $saludo = 'Buenas tardes';
else                 $saludo = 'Buenas noches';

// Nombre
$nombreCompleto = $_SESSION['blog_usuario']['nombre'] ?? 'Administrador';
$nombreAdmin    = explode(' ', trim($nombreCompleto))[0];
$inicialAdmin   = mb_strtoupper(mb_substr($nombreCompleto, 0, 1, 'UTF-8'), 'UTF-8');

// Tip de Alex — Artículos
if ($borradores > 0) {
    $alexTipArt = "Tienes <strong>{$borradores} borrador" . ($borradores !== 1 ? 'es' : '') . "</strong> sin publicar. Publicar con regularidad mejora el posicionamiento del blog.";
    $alexCtaArt = ['url' => '/dashboard/articulos?estado=borrador', 'label' => 'Ver borradores →'];
} elseif ($publicados === 0) {
    $alexTipArt = "El blog está vacío. <strong>Publica el primer artículo</strong> hoy y dale voz al Colegio Bilbao en línea.";
    $alexCtaArt = ['url' => '/dashboard/articulos/crear', 'label' => 'Crear primer artículo →'];
} elseif ($totalCats === 0) {
    $alexTipArt = "Crea <strong>categorías</strong> para organizar el blog. Los lectores encuentran más fácilmente lo que buscan.";
    $alexCtaArt = ['url' => '/dashboard/categorias/crear', 'label' => 'Crear categoría →'];
} else {
    $alexTipArt = "¡Todo va bien! Publicar al menos <strong>2 artículos por mes</strong> mantiene el blog activo y mejora el SEO del sitio.";
    $alexCtaArt = ['url' => '/dashboard/articulos/crear', 'label' => 'Nuevo artículo →'];
}

// Tip de Alex — Noticias
if ($nBor > 0) {
    $alexTipNot = "Tienes <strong>{$nBor} noticia" . ($nBor !== 1 ? 's' : '') . "</strong> en borrador. Las noticias tienen mayor impacto cuando se publican a tiempo.";
    $alexCtaNot = ['url' => '/dashboard/noticias?estado=borrador', 'label' => 'Ver borradores →'];
} elseif ($nPub === 0) {
    $alexTipNot = "Aún no hay noticias publicadas. <strong>Comparte lo que pasa</strong> en el colegio y mantén informada a la comunidad.";
    $alexCtaNot = ['url' => '/dashboard/noticias/crear', 'label' => 'Crear primera noticia →'];
} elseif ($nTotalCats === 0) {
    $alexTipNot = "Organiza las noticias con <strong>categorías</strong>: Institucional, Académico, Deportivo. Facilita la búsqueda a las familias.";
    $alexCtaNot = ['url' => '/dashboard/noticias/categorias/crear', 'label' => 'Crear categoría →'];
} else {
    $alexTipNot = "Las noticias frecuentes <strong>generan confianza</strong> en las familias. Publica eventos, logros y actividades del colegio.";
    $alexCtaNot = ['url' => '/dashboard/noticias/crear', 'label' => 'Nueva noticia →'];
}
?>

<div class="admin-layout">

    <!-- ══════════════════ SIDEBAR ══════════════════ -->
    <?php include __DIR__ . '/_sidebar.php'; ?>

    <!-- ══════════════════ MAIN ══════════════════ -->
    <div class="admin-main">

        <!-- TOPBAR -->
        <header class="admin-topbar">
            <div class="admin-topbar__left">
                <span class="admin-topbar__title">Panel de administración</span>
            </div>
            <div class="admin-topbar__actions">
                <a href="/dashboard/articulos/crear" class="admin-topbar__new-btn">
                    <i class="fa-solid fa-plus"></i> Nuevo artículo
                </a>
                <?php include __DIR__ . '/_topbar-avatar.php'; ?>
                <form action="/logout" method="POST" style="display:flex;align-items:center;">
                    <button type="submit" class="admin-logout-btn">
                        <i class="fa-solid fa-right-from-bracket"></i> Salir
                    </button>
                </form>
            </div>
        </header>

        <main class="admin-content">

            <!-- ── HERO ─────────────────────────────────── -->
            <div class="db-hero">
                <div class="db-hero__text">
                    <p class="db-hero__eyebrow">
                        <i class="fa-solid fa-wand-magic-sparkles"></i>
                        Bienvenido de vuelta
                    </p>
                    <h1 class="db-hero__title">
                        <?= htmlspecialchars($saludo) ?>, <?= htmlspecialchars($nombreAdmin) ?>
                    </h1>
                    <p class="db-hero__sub">
                        Gestiona artículos y noticias del Colegio Bilbao.<?php if ($borradores + $nBor > 0): ?>
                        Tienes <strong><?= $borradores + $nBor ?> borrador<?= ($borradores + $nBor) !== 1 ? 'es' : '' ?></strong> pendiente<?= ($borradores + $nBor) !== 1 ? 's' : '' ?> de publicar.<?php endif; ?>
                    </p>
                    <div style="display:flex;gap:10px;flex-wrap:wrap;">
                        <a href="/dashboard/articulos/crear" class="db-hero__cta">
                            <i class="fa-solid fa-pen-to-square"></i> Nuevo artículo
                        </a>
                        <a href="/dashboard/noticias/crear" class="db-hero__cta" style="background:rgba(255,255,255,0.12);color:#fff;box-shadow:none;">
                            <i class="fa-solid fa-bullhorn"></i> Nueva noticia
                        </a>
                    </div>
                </div>
                <img src="/build/assets/img/alex/alex-periodico.png"
                     alt="Alex" class="db-hero__alex">
            </div>

            <!-- ── SECTION TABS ──────────────────────────── -->
            <div class="db-tabs">
                <button class="db-tab-btn active" data-panel="articulos" type="button">
                    <i class="fa-regular fa-newspaper"></i> Artículos
                </button>
                <button class="db-tab-btn" data-panel="noticias" type="button">
                    <i class="fa-solid fa-bullhorn"></i> Noticias
                </button>
            </div>

            <!-- ══════════════ PANEL: ARTÍCULOS ══════════════ -->
            <div id="panel-articulos" class="db-panel active">

            <!-- ── STATS ──────────────────────────────────── -->
            <div class="db-stats">

                <a href="/dashboard/articulos?estado=publicado" class="db-stat db-stat--blue">
                    <div class="db-stat__icon"><i class="fa-regular fa-newspaper"></i></div>
                    <div class="db-stat__value" data-counter="<?= $publicados ?>">0</div>
                    <div class="db-stat__label">Publicados</div>
                </a>

                <a href="/dashboard/articulos?estado=borrador" class="db-stat db-stat--red">
                    <div class="db-stat__icon"><i class="fa-regular fa-file-lines"></i></div>
                    <div class="db-stat__value" data-counter="<?= $borradores ?>">0</div>
                    <div class="db-stat__label">Borradores</div>
                </a>

                <a href="/dashboard/articulos?estado=programado" class="db-stat db-stat--green">
                    <div class="db-stat__icon"><i class="fa-regular fa-calendar-check"></i></div>
                    <div class="db-stat__value" data-counter="<?= $programados ?>">0</div>
                    <div class="db-stat__label">Programados</div>
                </a>

                <a href="/dashboard/categorias" class="db-stat db-stat--yellow">
                    <div class="db-stat__icon"><i class="fa-solid fa-tags"></i></div>
                    <div class="db-stat__value" data-counter="<?= $totalCats ?>">0</div>
                    <div class="db-stat__label">Categorías</div>
                </a>

                <a href="/dashboard/usuarios" class="db-stat db-stat--purple">
                    <div class="db-stat__icon"><i class="fa-solid fa-users"></i></div>
                    <div class="db-stat__value" data-counter="<?= $totalUsers ?>">0</div>
                    <div class="db-stat__label">Usuarios</div>
                </a>

            </div>

            <!-- ── CHARTS ─────────────────────────────────── -->
            <div class="db-charts">

                <div class="db-card">
                    <div class="db-card__header">
                        <div>
                            <div class="db-card__title">
                                <i class="fa-solid fa-chart-column"></i> Actividad mensual
                            </div>
                            <div class="db-card__subtitle">Artículos creados — últimos 6 meses</div>
                        </div>
                    </div>
                    <div class="db-card__body">
                        <canvas id="chartMeses" height="210"></canvas>
                    </div>
                </div>

                <div class="db-card">
                    <div class="db-card__header">
                        <div>
                            <div class="db-card__title">
                                <i class="fa-solid fa-chart-pie"></i> Por categoría
                            </div>
                            <div class="db-card__subtitle">Distribución de artículos</div>
                        </div>
                    </div>
                    <div class="db-card__body">
                        <?php if (array_sum($catData) === 0): ?>
                            <div class="db-empty">
                                <i class="fa-solid fa-chart-pie"></i>
                                <p>Asigna categorías a tus artículos<br>para ver la distribución aquí.</p>
                            </div>
                        <?php else: ?>
                            <div style="display:flex;align-items:center;gap:20px;flex-wrap:wrap;">
                                <div style="flex-shrink:0;">
                                    <canvas id="chartCategorias" width="160" height="160"
                                            style="max-width:160px;max-height:160px;"></canvas>
                                </div>
                                <div class="db-legend" style="flex:1;min-width:100px;">
                                    <?php foreach ($porCat as $cat): ?>
                                    <div class="db-legend-item">
                                        <span class="db-legend-dot"
                                              style="background:<?= htmlspecialchars($cat['color'] ?? '#4D8ABB') ?>;"></span>
                                        <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:110px;">
                                            <?= htmlspecialchars($cat['nombre']) ?>
                                        </span>
                                        <span class="db-legend-val"><?= (int)$cat['total'] ?></span>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

            <!-- ── BOTTOM ─────────────────────────────────── -->
            <div class="db-bottom">

                <!-- Artículos recientes -->
                <div class="db-card">
                    <div class="db-card__header">
                        <div>
                            <div class="db-card__title">
                                <i class="fa-regular fa-clock"></i> Artículos recientes
                            </div>
                            <div class="db-card__subtitle"><?= count($recientes) ?> más recientes</div>
                        </div>
                        <a href="/dashboard/articulos" class="db-card__link">Ver todos →</a>
                    </div>
                    <div class="db-card__body" style="padding-top:10px;">
                        <?php if (empty($recientes)): ?>
                            <div class="db-empty">
                                <i class="fa-regular fa-newspaper"></i>
                                <p>Aún no hay artículos.<br>
                                   <a href="/dashboard/articulos/crear">Crea el primero</a>
                                </p>
                            </div>
                        <?php else: ?>
                            <div style="overflow-x:auto;">
                                <table class="db-table">
                                    <thead>
                                        <tr>
                                            <th>Título</th>
                                            <th>Categoría</th>
                                            <th>Estado</th>
                                            <th>Fecha</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($recientes as $art): ?>
                                        <tr>
                                            <td>
                                                <div class="db-art-title">
                                                    <?= htmlspecialchars($art->titulo ?? '—') ?>
                                                </div>
                                                <?php if (!empty($art->autor_nombre)): ?>
                                                <div class="db-art-meta">
                                                    por <?= htmlspecialchars($art->autor_nombre) ?>
                                                </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="db-cat-dot"
                                                      style="--dot-color:<?= htmlspecialchars($art->categoria_color ?? '#4D8ABB') ?>">
                                                    <?= htmlspecialchars($art->categoria_nombre ?? '—') ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($art->estado === 'publicado'): ?>
                                                    <span class="db-badge db-badge--pub">Publicado</span>
                                                <?php elseif ($art->estado === 'borrador'): ?>
                                                    <span class="db-badge db-badge--draft">Borrador</span>
                                                <?php else: ?>
                                                    <span class="db-badge db-badge--sched">Programado</span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="font-size:11px;color:#94A3B8;white-space:nowrap;">
                                                <?= !empty($art->creado_en) ? date('d/m/Y', strtotime($art->creado_en)) : '—' ?>
                                            </td>
                                            <td>
                                                <a href="/dashboard/articulos/editar?id=<?= (int)$art->id ?>"
                                                   class="db-edit-btn">
                                                    <i class="fa-regular fa-pen-to-square"></i> Editar
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Columna derecha -->
                <div class="db-right-col">

                    <!-- Acciones rápidas -->
                    <div class="db-card">
                        <div class="db-card__header">
                            <div class="db-card__title">
                                <i class="fa-solid fa-bolt"></i> Acciones rápidas
                            </div>
                        </div>
                        <div class="db-card__body" style="padding-top:10px;display:flex;flex-direction:column;gap:2px;">

                            <a href="/dashboard/articulos/crear" class="db-qa">
                                <div class="db-qa-icon" style="background:linear-gradient(135deg,#2e4b8a,#4267ac);color:#fff;">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </div>
                                <div>
                                    <div class="db-qa-label">Nuevo artículo</div>
                                    <div class="db-qa-desc">Redactar y publicar contenido</div>
                                </div>
                                <i class="fa-solid fa-chevron-right db-qa-arrow"></i>
                            </a>

                            <a href="/dashboard/noticias/crear" class="db-qa">
                                <div class="db-qa-icon" style="background:linear-gradient(135deg,#991b1b,#dc2626);color:#fff;">
                                    <i class="fa-solid fa-bullhorn"></i>
                                </div>
                                <div>
                                    <div class="db-qa-label">Nueva noticia</div>
                                    <div class="db-qa-desc">Publicar en Voces Bilbao</div>
                                </div>
                                <i class="fa-solid fa-chevron-right db-qa-arrow"></i>
                            </a>

                            <a href="/dashboard/categorias/crear" class="db-qa">
                                <div class="db-qa-icon" style="background:linear-gradient(135deg,#5c4200,#facc15);color:#fff;">
                                    <i class="fa-solid fa-tag"></i>
                                </div>
                                <div>
                                    <div class="db-qa-label">Nueva categoría</div>
                                    <div class="db-qa-desc">Organizar el contenido</div>
                                </div>
                                <i class="fa-solid fa-chevron-right db-qa-arrow"></i>
                            </a>

                            <?php if (($_SESSION['blog_usuario']['rol'] ?? '') === 'administrador'): ?>
                            <a href="/dashboard/usuarios/crear" class="db-qa">
                                <div class="db-qa-icon" style="background:linear-gradient(135deg,#4c1d95,#7c3aed);color:#fff;">
                                    <i class="fa-solid fa-user-plus"></i>
                                </div>
                                <div>
                                    <div class="db-qa-label">Agregar usuario</div>
                                    <div class="db-qa-desc">Dar acceso al panel</div>
                                </div>
                                <i class="fa-solid fa-chevron-right db-qa-arrow"></i>
                            </a>
                            <?php endif; ?>

                            <a href="/blog" class="db-qa" target="_blank">
                                <div class="db-qa-icon" style="background:linear-gradient(135deg,#14532d,#16a34a);color:#fff;">
                                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                </div>
                                <div>
                                    <div class="db-qa-label">Ver blog público</div>
                                    <div class="db-qa-desc">Revisar cómo se ve en el sitio</div>
                                </div>
                                <i class="fa-solid fa-chevron-right db-qa-arrow"></i>
                            </a>

                        </div>
                    </div>

                    <!-- Alex card — Artículos -->
                    <div class="db-alex-card">
                        <div class="db-alex-card__inner">
                            <div class="db-alex-card__eyebrow">
                                <i class="fa-solid fa-comment-dots"></i> Alex dice...
                            </div>
                            <div class="db-alex-card__bubble">
                                <p><?= $alexTipArt ?></p>
                            </div>
                            <a href="<?= $alexCtaArt['url'] ?>" class="db-alex-card__cta">
                                <?= $alexCtaArt['label'] ?>
                            </a>
                        </div>
                        <img src="/build/assets/img/alex/alex-lee.png"
                             alt="Alex" class="db-alex-card__img">
                    </div>

                </div>

            </div>

            </div><!-- /panel-articulos -->

            <!-- ══════════════ PANEL: NOTICIAS ══════════════ -->
            <div id="panel-noticias" class="db-panel">

            <!-- ── NOTICIAS STATS ──────────────────────────── -->
            <div class="db-stats db-stats--4col" style="margin-bottom:24px;">

                <a href="/dashboard/noticias?estado=publicado" class="db-stat db-stat--blue">
                    <div class="db-stat__icon"><i class="fa-solid fa-bullhorn"></i></div>
                    <div class="db-stat__value" data-counter="<?= $nPub ?>">0</div>
                    <div class="db-stat__label">Noticias publicadas</div>
                </a>

                <a href="/dashboard/noticias?estado=borrador" class="db-stat db-stat--red">
                    <div class="db-stat__icon"><i class="fa-regular fa-file-lines"></i></div>
                    <div class="db-stat__value" data-counter="<?= $nBor ?>">0</div>
                    <div class="db-stat__label">Borradores</div>
                </a>

                <a href="/dashboard/noticias?estado=programado" class="db-stat db-stat--green">
                    <div class="db-stat__icon"><i class="fa-regular fa-calendar-check"></i></div>
                    <div class="db-stat__value" data-counter="<?= $nProg ?>">0</div>
                    <div class="db-stat__label">Programadas</div>
                </a>

                <a href="/dashboard/noticias/categorias" class="db-stat db-stat--yellow">
                    <div class="db-stat__icon"><i class="fa-solid fa-folder-tree"></i></div>
                    <div class="db-stat__value" data-counter="<?= $nTotalCats ?>">0</div>
                    <div class="db-stat__label">Categorías</div>
                </a>

            </div>

            <!-- ── NOTICIAS CHARTS ─────────────────────────── -->
            <div class="db-charts" style="margin-bottom:24px;">

                <div class="db-card">
                    <div class="db-card__header">
                        <div>
                            <div class="db-card__title">
                                <i class="fa-solid fa-chart-column"></i> Actividad mensual
                            </div>
                            <div class="db-card__subtitle">Noticias creadas — últimos 6 meses</div>
                        </div>
                    </div>
                    <div class="db-card__body">
                        <canvas id="chartNMeses" height="210"></canvas>
                    </div>
                </div>

                <div class="db-card">
                    <div class="db-card__header">
                        <div>
                            <div class="db-card__title">
                                <i class="fa-solid fa-chart-pie"></i> Por categoría
                            </div>
                            <div class="db-card__subtitle">Distribución de noticias</div>
                        </div>
                    </div>
                    <div class="db-card__body">
                        <?php if (array_sum($nCatData) === 0): ?>
                            <div class="db-empty">
                                <i class="fa-solid fa-chart-pie"></i>
                                <p>Asigna categorías a tus noticias<br>para ver la distribución aquí.</p>
                            </div>
                        <?php else: ?>
                            <div style="display:flex;align-items:center;gap:20px;flex-wrap:wrap;">
                                <div style="flex-shrink:0;">
                                    <canvas id="chartNCategorias" width="160" height="160"
                                            style="max-width:160px;max-height:160px;"></canvas>
                                </div>
                                <div class="db-legend" style="flex:1;min-width:100px;">
                                    <?php foreach ($nPorCat as $ncat): ?>
                                    <div class="db-legend-item">
                                        <span class="db-legend-dot"
                                              style="background:<?= htmlspecialchars($ncat['color'] ?? '#374C69') ?>;"></span>
                                        <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:110px;">
                                            <?= htmlspecialchars($ncat['nombre']) ?>
                                        </span>
                                        <span class="db-legend-val"><?= (int)$ncat['total'] ?></span>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

            <!-- ── BOTTOM: tabla + columna derecha ────────── -->
            <div class="db-bottom">

                <!-- Noticias recientes -->
                <div class="db-card">
                    <div class="db-card__header">
                        <div>
                            <div class="db-card__title">
                                <i class="fa-regular fa-clock"></i> Noticias recientes
                            </div>
                            <div class="db-card__subtitle"><?= count($nRecientes) ?> más recientes</div>
                        </div>
                        <a href="/dashboard/noticias" class="db-card__link">Ver todas →</a>
                    </div>
                    <div class="db-card__body" style="padding-top:10px;">
                        <?php if (empty($nRecientes)): ?>
                            <div class="db-empty">
                                <i class="fa-solid fa-bullhorn"></i>
                                <p>Aún no hay noticias.<br>
                                   <a href="/dashboard/noticias/crear">Crea la primera</a>
                                </p>
                            </div>
                        <?php else: ?>
                            <div style="overflow-x:auto;">
                                <table class="db-table">
                                    <thead>
                                        <tr>
                                            <th>Título</th>
                                            <th>Categoría</th>
                                            <th>Estado</th>
                                            <th>Fecha</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($nRecientes as $noticia): ?>
                                        <tr>
                                            <td>
                                                <div class="db-art-title">
                                                    <?= htmlspecialchars($noticia->titulo ?? '—') ?>
                                                </div>
                                                <?php if (!empty($noticia->autor_nombre)): ?>
                                                <div class="db-art-meta">
                                                    por <?= htmlspecialchars($noticia->autor_nombre) ?>
                                                </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="db-cat-dot"
                                                      style="--dot-color:<?= htmlspecialchars($noticia->categoria_color ?? '#374C69') ?>">
                                                    <?= htmlspecialchars($noticia->categoria_nombre ?? '—') ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($noticia->estado === 'publicado'): ?>
                                                    <span class="db-badge db-badge--pub">Publicado</span>
                                                <?php elseif ($noticia->estado === 'borrador'): ?>
                                                    <span class="db-badge db-badge--draft">Borrador</span>
                                                <?php else: ?>
                                                    <span class="db-badge db-badge--sched">Programado</span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="font-size:11px;color:#94A3B8;white-space:nowrap;">
                                                <?= !empty($noticia->creado_en) ? date('d/m/Y', strtotime($noticia->creado_en)) : '—' ?>
                                            </td>
                                            <td>
                                                <a href="/dashboard/noticias/editar?id=<?= (int)$noticia->id ?>"
                                                   class="db-edit-btn">
                                                    <i class="fa-regular fa-pen-to-square"></i> Editar
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Columna derecha -->
                <div class="db-right-col">

                    <!-- Acciones rápidas -->
                    <div class="db-card">
                        <div class="db-card__header">
                            <div class="db-card__title">
                                <i class="fa-solid fa-bolt"></i> Acciones rápidas
                            </div>
                        </div>
                        <div class="db-card__body" style="padding-top:10px;display:flex;flex-direction:column;gap:2px;">
                            <a href="/dashboard/noticias/crear" class="db-qa">
                                <div class="db-qa-icon" style="background:linear-gradient(135deg,#2e4b8a,#4267ac);color:#fff;">
                                    <i class="fa-solid fa-bullhorn"></i>
                                </div>
                                <div><div class="db-qa-label">Nueva noticia</div><div class="db-qa-desc">Publicar en Voces Bilbao</div></div>
                                <i class="fa-solid fa-chevron-right db-qa-arrow"></i>
                            </a>
                            <a href="/dashboard/noticias" class="db-qa">
                                <div class="db-qa-icon" style="background:linear-gradient(135deg,#991b1b,#dc2626);color:#fff;">
                                    <i class="fa-regular fa-bell"></i>
                                </div>
                                <div><div class="db-qa-label">Ver noticias</div><div class="db-qa-desc">Gestionar todas las noticias</div></div>
                                <i class="fa-solid fa-chevron-right db-qa-arrow"></i>
                            </a>
                            <a href="/dashboard/noticias/categorias/crear" class="db-qa">
                                <div class="db-qa-icon" style="background:linear-gradient(135deg,#5c4200,#facc15);color:#fff;">
                                    <i class="fa-solid fa-folder-tree"></i>
                                </div>
                                <div><div class="db-qa-label">Nueva categoría</div><div class="db-qa-desc">Organizar noticias</div></div>
                                <i class="fa-solid fa-chevron-right db-qa-arrow"></i>
                            </a>
                            <a href="/noticias" class="db-qa" target="_blank">
                                <div class="db-qa-icon" style="background:linear-gradient(135deg,#14532d,#16a34a);color:#fff;">
                                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                </div>
                                <div><div class="db-qa-label">Ver noticias públicas</div><div class="db-qa-desc">Cómo se ven en el sitio</div></div>
                                <i class="fa-solid fa-chevron-right db-qa-arrow"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Alex card — Noticias -->
                    <div class="db-alex-card">
                        <div class="db-alex-card__inner">
                            <div class="db-alex-card__eyebrow">
                                <i class="fa-solid fa-comment-dots"></i> Alex dice...
                            </div>
                            <div class="db-alex-card__bubble">
                                <p><?= $alexTipNot ?></p>
                            </div>
                            <a href="<?= $alexCtaNot['url'] ?>" class="db-alex-card__cta">
                                <?= $alexCtaNot['label'] ?>
                            </a>
                        </div>
                        <img src="/build/assets/img/alex/alex-point.png"
                             alt="Alex" class="db-alex-card__img">
                    </div>

                </div>

            </div><!-- /db-bottom -->

            </div><!-- /panel-noticias -->

        </main>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<!-- Datos del servidor para las graficas (isla JSON, no es logica embebida) -->
<script type="application/json" id="dashboardChartData">
{
  "monthLabels": <?= $jsMonthLabels ?>,
  "monthData":   <?= $jsMonthData ?>,
  "catLabels":   <?= $jsCatLabels ?>,
  "catData":     <?= $jsCatData ?>,
  "catColors":   <?= $jsCatColors ?>,
  "nMonthLabels": <?= $jsNMonthLabels ?>,
  "nMonthData":   <?= $jsNMonthData ?>,
  "nCatLabels":   <?= $jsNCatLabels ?>,
  "nCatData":     <?= $jsNCatData ?>,
  "nCatColors":   <?= $jsNCatColors ?>
}
</script>
