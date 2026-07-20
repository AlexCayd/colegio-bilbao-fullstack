<?php
$_notifsCount = (!empty($_SESSION['blog_usuario']) && class_exists(\Model\Notificacion::class))
    ? \Model\Notificacion::noLeidasPorUsuario((int)$_SESSION['blog_usuario']['id'])
    : 0;

if (!function_exists('_nav_active')) {
    function _nav_active(string $path): string {
        $cp = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $cp = ($cp !== '/') ? rtrim($cp, '/') : '/';
        return $cp === $path ? ' active' : '';
    }
    function _nav_active_prefix(string $prefix): string {
        $cp = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $cp = ($cp !== '/') ? rtrim($cp, '/') : '/';
        return str_starts_with($cp, $prefix) ? ' active' : '';
    }
}

if (!function_exists('_blog_puede')) {
    // ¿El usuario en sesión tiene acceso al módulo? Admin = todos.
    function _blog_puede(string $modulo): bool {
        $u = $_SESSION['blog_usuario'] ?? null;
        if (!$u) return false;
        if (($u['rol'] ?? '') === 'administrador') return true;
        $lista = array_filter(array_map('trim', explode(',', (string)($u['modulos'] ?? ''))));
        return in_array($modulo, $lista, true);
    }
}

// Detectar el módulo activo a partir de la URL
$_cp = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$_cp = ($_cp !== '/') ? rtrim($_cp, '/') : '/';
if     (str_starts_with($_cp, '/dashboard/suplencias')) $_modActivo = 'suplencias';
elseif (str_starts_with($_cp, '/dashboard/usuarios'))   $_modActivo = 'usuarios';
elseif ($_cp === '/dashboard')                          $_modActivo = 'home';
else                                                    $_modActivo = 'redaccion';

$_esAdmin = ($_SESSION['blog_usuario']['rol'] ?? '') === 'administrador';

// Metadatos de módulos para el título contextual del sidebar
$_modInfo = [
    'home'       => ['label' => 'Módulos',    'icon' => 'fa-grip'],
    'redaccion'  => ['label' => 'Redacción',  'icon' => 'fa-pen-nib'],
    'suplencias' => ['label' => 'Suplencias', 'icon' => 'fa-user-clock'],
    'usuarios'   => ['label' => 'Usuarios',   'icon' => 'fa-users-gear'],
];

// ── Breadcrumb total (Inicio › Módulo › Subpágina) ──
$_segMap = [
    'articulos' => 'Artículos', 'categorias' => 'Categorías', 'noticias' => 'Noticias',
    'revisiones' => 'Revisiones', 'mis-revisiones' => 'Mis revisiones', 'testimoniales' => 'Testimoniales',
    'autores' => 'Autores', 'notificaciones' => 'Notificaciones', 'cumpleanos' => 'Cumpleaños',
    'crear' => 'Nuevo', 'editar' => 'Editar',
];
$_modUrl = ['redaccion' => '/dashboard/redaccion', 'suplencias' => '/dashboard/suplencias', 'usuarios' => '/dashboard/usuarios'];
$_path   = trim((string)preg_replace('#^/dashboard#', '', $_cp), '/');
$_segs   = $_path === '' ? [] : explode('/', $_path);

$_crumbs = [['label' => 'Inicio', 'url' => '/dashboard']];
if ($_path === 'perfil') {
    $_crumbs[] = ['label' => 'Mi perfil', 'url' => null];
} else {
    if ($_modActivo !== 'home' && isset($_modUrl[$_modActivo])) {
        $_crumbs[] = ['label' => $_modInfo[$_modActivo]['label'], 'url' => $_modUrl[$_modActivo]];
    }
    foreach ($_segs as $seg) {
        if ($seg === $_modActivo || $seg === 'redaccion') continue; // módulo ya representado
        if (!isset($_segMap[$seg])) continue;                       // ids / endpoints
        $lbl = $_segMap[$seg];
        if ($seg === 'crear' && ($_modActivo === 'suplencias' || in_array('noticias', $_segs, true))) $lbl = 'Nueva';
        $_crumbs[] = ['label' => $lbl, 'url' => null];
    }
}
?>
<div class="admin-sidebar-overlay" id="sidebarOverlay"></div>

<!-- Breadcrumb (el JS lo mueve al topbar) -->
<nav class="admin-crumbs" id="adminCrumbsSrc" aria-label="Ruta de navegación" hidden>
    <?php foreach ($_crumbs as $_i => $_cr): ?>
        <?php if ($_i > 0): ?><i class="fa-solid fa-chevron-right admin-crumbs__sep" aria-hidden="true"></i><?php endif; ?>
        <?php if (!empty($_cr['url'])): ?>
            <a href="<?= $_cr['url'] ?>" class="admin-crumbs__link"><?= htmlspecialchars($_cr['label']) ?></a>
        <?php else: ?>
            <span class="admin-crumbs__cur"><?= htmlspecialchars($_cr['label']) ?></span>
        <?php endif; ?>
    <?php endforeach; ?>
</nav>

<aside class="admin-sidebar" id="adminSidebar">
    <div class="admin-sidebar__top">
        <div class="admin-sidebar__top-row">
            <a href="/" class="admin-sidebar__brand">
                <img src="/build/assets/img/global/logo-bilbao-horizontal-azul.png" alt="Colegio Bilbao" class="admin-sidebar__logo">
            </a>
            <button class="admin-sidebar__collapse-btn" id="sidebarCollapseBtn" title="Colapsar menú" aria-label="Colapsar menú">
                <i class="fa-solid fa-chevron-left" id="sidebarCollapseIcon"></i>
            </button>
        </div>
        <span class="admin-sidebar__brand-label admin-sidebar__brand-label--text">Dashboard</span>
    </div>
    <nav class="admin-sidebar__nav" aria-label="Navegación de administración">

        <!-- Volver al home de módulos -->
        <div class="admin-nav__section">
            <a href="/dashboard" title="Inicio" class="admin-nav__home<?= _nav_active('/dashboard') ?>">
                <i class="fa-solid fa-house"></i>
                <span class="admin-nav__label">Inicio</span>
                <?php if ($_modActivo !== 'home'): ?><i class="fa-solid fa-chevron-right admin-nav__home-arrow"></i><?php endif; ?>
            </a>
        </div>

        <?php if ($_modActivo === 'redaccion' && _blog_puede('redaccion')): ?>
        <!-- ══════════ MÓDULO REDACCIÓN ══════════ -->
        <div class="admin-nav__section">
            <span class="admin-nav__section-label">Redacción</span>
            <a href="/dashboard/redaccion" title="Resumen" class="admin-nav__link<?= _nav_active('/dashboard/redaccion') ?>">
                <i class="fa-solid fa-gauge-high"></i>
                <span class="admin-nav__label">Resumen</span>
            </a>
        </div>
        <div class="admin-nav__section">
            <span class="admin-nav__section-label">Contenido</span>
            <a href="/dashboard/articulos" title="Artículos" class="admin-nav__link<?= _nav_active('/dashboard/articulos') ?>">
                <i class="fa-regular fa-newspaper"></i>
                <span class="admin-nav__label">Artículos</span>
            </a>
            <a href="/dashboard/articulos/crear" title="Nuevo artículo" class="admin-nav__link<?= _nav_active('/dashboard/articulos/crear') ?>">
                <i class="fa-solid fa-pen-to-square"></i>
                <span class="admin-nav__label">Nuevo artículo</span>
            </a>
            <a href="/dashboard/categorias" title="Categorías" class="admin-nav__link<?= _nav_active_prefix('/dashboard/categorias') ?>">
                <i class="fa-solid fa-tags"></i>
                <span class="admin-nav__label">Categorías</span>
            </a>
        </div>
        <div class="admin-nav__section">
            <span class="admin-nav__section-label">Noticias</span>
            <a href="/dashboard/noticias" title="Noticias" class="admin-nav__link<?= _nav_active('/dashboard/noticias') ?>">
                <i class="fa-regular fa-bell"></i>
                <span class="admin-nav__label">Noticias</span>
            </a>
            <a href="/dashboard/noticias/crear" title="Nueva noticia" class="admin-nav__link<?= _nav_active('/dashboard/noticias/crear') ?>">
                <i class="fa-solid fa-bullhorn"></i>
                <span class="admin-nav__label">Nueva noticia</span>
            </a>
            <a href="/dashboard/noticias/categorias" title="Categorías noticias" class="admin-nav__link<?= _nav_active_prefix('/dashboard/noticias/categorias') ?>">
                <i class="fa-solid fa-folder-tree"></i>
                <span class="admin-nav__label">Categorías noticias</span>
            </a>
        </div>
        <div class="admin-nav__section">
            <span class="admin-nav__section-label">Gestión</span>
            <?php if ($_esAdmin): ?>
            <a href="/dashboard/autores" title="Por autor" class="admin-nav__link<?= _nav_active('/dashboard/autores') ?>">
                <i class="fa-solid fa-users-between-lines"></i>
                <span class="admin-nav__label">Por autor</span>
            </a>
            <a href="/dashboard/revisiones" title="Revisiones" class="admin-nav__link<?= _nav_active('/dashboard/revisiones') ?>">
                <i class="fa-solid fa-clipboard-check"></i>
                <span class="admin-nav__label">Revisiones</span>
            </a>
            <a href="/dashboard/testimoniales" title="Testimoniales" class="admin-nav__link<?= _nav_active('/dashboard/testimoniales') ?>">
                <i class="fa-solid fa-comment-dots"></i>
                <span class="admin-nav__label">Testimoniales</span>
            </a>
            <?php else: ?>
            <a href="/dashboard/mis-revisiones" title="Mis revisiones" class="admin-nav__link<?= _nav_active('/dashboard/mis-revisiones') ?>">
                <i class="fa-solid fa-rotate-left"></i>
                <span class="admin-nav__label">Mis revisiones</span>
            </a>
            <a href="/dashboard/notificaciones" title="Notificaciones" class="admin-nav__link<?= _nav_active('/dashboard/notificaciones') ?>">
                <i class="fa-solid fa-bell"></i>
                <span class="admin-nav__label">
                    Notificaciones
                    <?php if ($_notifsCount > 0): ?>
                    <span class="admin-nav__badge"><?= (int)$_notifsCount ?></span>
                    <?php endif; ?>
                </span>
            </a>
            <?php endif; ?>
        </div>

        <?php elseif ($_modActivo === 'suplencias' && _blog_puede('suplencias')): ?>
        <!-- ══════════ MÓDULO SUPLENCIAS ══════════ -->
        <div class="admin-nav__section">
            <span class="admin-nav__section-label">Suplencias</span>
            <a href="/dashboard/suplencias" title="Panel de suplencias" class="admin-nav__link<?= _nav_active('/dashboard/suplencias') ?>">
                <i class="fa-solid fa-user-clock"></i>
                <span class="admin-nav__label">Panel de suplencias</span>
            </a>
        </div>

        <?php elseif ($_modActivo === 'usuarios' && _blog_puede('usuarios')): ?>
        <!-- ══════════ MÓDULO USUARIOS ══════════ -->
        <div class="admin-nav__section">
            <span class="admin-nav__section-label">Usuarios</span>
            <a href="/dashboard/usuarios" title="Todos los usuarios" class="admin-nav__link<?= _nav_active('/dashboard/usuarios') ?>">
                <i class="fa-solid fa-users"></i>
                <span class="admin-nav__label">Todos los usuarios</span>
            </a>
            <?php if ($_esAdmin): ?>
            <a href="/dashboard/usuarios/crear" title="Nuevo usuario" class="admin-nav__link<?= _nav_active('/dashboard/usuarios/crear') ?>">
                <i class="fa-solid fa-user-plus"></i>
                <span class="admin-nav__label">Nuevo usuario</span>
            </a>
            <?php endif; ?>
            <a href="/dashboard/usuarios/cumpleanos" title="Cumpleaños" class="admin-nav__link<?= _nav_active('/dashboard/usuarios/cumpleanos') ?>">
                <i class="fa-solid fa-cake-candles"></i>
                <span class="admin-nav__label">Cumpleaños</span>
            </a>
        </div>

        <?php else: ?>
        <!-- ══════════ HOME · LISTA DE MÓDULOS ══════════ -->
        <div class="admin-nav__section">
            <span class="admin-nav__section-label">Módulos</span>
            <?php if (_blog_puede('redaccion')): ?>
            <a href="/dashboard/redaccion" title="Redacción" class="admin-nav__link">
                <i class="fa-solid fa-pen-nib"></i>
                <span class="admin-nav__label">Redacción</span>
            </a>
            <?php endif; ?>
            <?php if (_blog_puede('suplencias')): ?>
            <a href="/dashboard/suplencias" title="Suplencias" class="admin-nav__link">
                <i class="fa-solid fa-user-clock"></i>
                <span class="admin-nav__label">Suplencias</span>
            </a>
            <?php endif; ?>
            <?php if (_blog_puede('usuarios')): ?>
            <a href="/dashboard/usuarios" title="Usuarios" class="admin-nav__link">
                <i class="fa-solid fa-users-gear"></i>
                <span class="admin-nav__label">Usuarios</span>
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </nav>
    <div class="admin-sidebar__alex admin-sidebar__alex--collapsible">
        <img src="/build/assets/img/alex/alex-toca.png" alt="Alex">
        <p>Tu espacio de trabajo<br>Colegio Bilbao</p>
    </div>
</aside>


