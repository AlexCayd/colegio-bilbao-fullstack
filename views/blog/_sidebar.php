<?php
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
    // ¿El usuario en sesión tiene acceso al módulo? Super/admin = todos.
    function _blog_puede(string $modulo): bool {
        $u = $_SESSION['blog_usuario'] ?? null;
        if (!$u) return false;
        if (in_array($u['rol'] ?? '', ['administrador', 'superadmin'], true)) return true;
        $lista = array_filter(array_map('trim', explode(',', (string)($u['modulos'] ?? ''))));
        return in_array($modulo, $lista, true);
    }
    // ¿Puede validar contenido editorial (revisiones + testimoniales)?
    function _blog_puede_revisar(): bool {
        $u = $_SESSION['blog_usuario'] ?? null;
        if (!$u) return false;
        if (in_array($u['rol'] ?? '', ['administrador', 'superadmin'], true)) return true;
        return ($u['rol_redaccion'] ?? '') === 'revisor' && _blog_puede('redaccion');
    }
}

// Detectar el módulo activo a partir de la URL
$_cp = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$_cp = ($_cp !== '/') ? rtrim($_cp, '/') : '/';
if     (str_starts_with($_cp, '/dashboard/suplencias'))      $_modActivo = 'suplencias';
elseif (str_starts_with($_cp, '/dashboard/horarios'))        $_modActivo = 'horarios';
elseif (str_starts_with($_cp, '/dashboard/eventos'))         $_modActivo = 'eventos';
elseif (str_starts_with($_cp, '/dashboard/aulas'))           $_modActivo = 'aulas';
elseif (str_starts_with($_cp, '/dashboard/grupos'))          $_modActivo = 'grupos';
elseif (str_starts_with($_cp, '/dashboard/usuarios'))        $_modActivo = 'usuarios';
elseif (str_starts_with($_cp, '/dashboard/profesores'))      $_modActivo = 'profesores';
elseif (str_starts_with($_cp, '/dashboard/prefectura'))      $_modActivo = 'prefectura';
elseif (str_starts_with($_cp, '/dashboard/administrativos')) $_modActivo = 'administrativos';
elseif ($_cp === '/dashboard')                               $_modActivo = 'home';
else                                                         $_modActivo = 'redaccion';

$_esAdmin   = in_array($_SESSION['blog_usuario']['rol'] ?? '', ['administrador', 'superadmin'], true);
$_esSuper   = ($_SESSION['blog_usuario']['rol'] ?? '') === 'superadmin';
$_puedeRev  = _blog_puede_revisar();

// Catálogo y categorías: la misma fuente que usa el home de módulos.
require_once __DIR__ . '/_modulos.php';
$_catMods = blog_modulos_catalogo();
$_misMods = array_filter(array_map('trim', explode(',', (string)($_SESSION['blog_usuario']['modulos'] ?? ''))));
if ($_esAdmin) $_misMods = ['redaccion', 'suplencias', 'horarios', 'eventos', 'usuarios'];
$_grupos  = blog_modulos_visibles(blog_modulos_disponibles($_misMods, $_esSuper));

// Metadatos de módulos para el título contextual del sidebar
$_modInfo = [
    'home'            => ['label' => 'Módulos',        'icon' => 'fa-grip'],
    'redaccion'       => ['label' => 'Redacción',      'icon' => 'fa-pen-nib'],
    'suplencias'      => ['label' => 'Suplencias',     'icon' => 'fa-user-clock'],
    'horarios'        => ['label' => 'Horarios',       'icon' => 'fa-table-cells'],
    'eventos'         => ['label' => 'Eventos',        'icon' => 'fa-calendar-day'],
    'aulas'           => ['label' => 'Aulas',          'icon' => 'fa-door-open'],
    'grupos'          => ['label' => 'Grupos',         'icon' => 'fa-layer-group'],
    'usuarios'        => ['label' => 'Usuarios',       'icon' => 'fa-users-gear'],
    'profesores'      => ['label' => 'Profesores',     'icon' => 'fa-chalkboard-user'],
    'prefectura'      => ['label' => 'Prefectura',     'icon' => 'fa-user-shield'],
    'administrativos' => ['label' => 'Administrativos','icon' => 'fa-user-tie'],
];

// ── Breadcrumb total (Inicio › Módulo › Subpágina) ──
$_segMap = [
    'articulos' => 'Artículos', 'categorias' => 'Categorías', 'noticias' => 'Noticias',
    'revisiones' => 'Revisiones', 'mis-revisiones' => 'Mis revisiones', 'testimoniales' => 'Testimoniales',
    'autores' => 'Autores', 'notificaciones' => 'Notificaciones', 'cumpleanos' => 'Cumpleaños',
    'profesor' => 'Por profesor', 'aula' => 'Por aula', 'grupo' => 'Por grupo',
    'mi-horario' => 'Mi horario', 'importar' => 'Importar CSV',
    'dashboard' => 'Tablero', 'solicitar' => 'Solicitar', 'validar' => 'Validar',
    'agendar' => 'Agendar', 'mis-coberturas' => 'Mis coberturas',
    'profesores' => 'Profesores', 'prefectura' => 'Prefectura', 'administrativos' => 'Administrativos',
    'aulas' => 'Aulas', 'grupos' => 'Grupos',
    'crear' => 'Nuevo', 'editar' => 'Editar',
];
$_modUrl = [
    'redaccion' => '/dashboard/redaccion', 'suplencias' => '/dashboard/suplencias',
    'horarios' => '/dashboard/horarios', 'eventos' => '/dashboard/eventos', 'usuarios' => '/dashboard/usuarios',
    'aulas' => '/dashboard/aulas', 'grupos' => '/dashboard/grupos',
    'profesores' => '/dashboard/profesores', 'prefectura' => '/dashboard/prefectura', 'administrativos' => '/dashboard/administrativos',
];
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
// La última miga es la página actual: siempre en negrita, nunca un enlace a sí misma
// (si no, la raíz de un módulo se quedaba sin ningún texto destacado en el topbar).
$_crumbs[array_key_last($_crumbs)]['url'] = null;
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
            <?php endif; ?>
            <?php if ($_puedeRev): ?>
            <a href="/dashboard/revisiones" title="Revisiones" class="admin-nav__link<?= _nav_active('/dashboard/revisiones') ?>">
                <i class="fa-solid fa-clipboard-check"></i>
                <span class="admin-nav__label">Revisiones</span>
            </a>
            <a href="/dashboard/testimoniales" title="Testimoniales" class="admin-nav__link<?= _nav_active('/dashboard/testimoniales') ?>">
                <i class="fa-solid fa-comment-dots"></i>
                <span class="admin-nav__label">Testimoniales</span>
            </a>
            <?php endif; ?>
            <?php if (!$_esAdmin): ?>
            <a href="/dashboard/mis-revisiones" title="Mis revisiones" class="admin-nav__link<?= _nav_active('/dashboard/mis-revisiones') ?>">
                <i class="fa-solid fa-rotate-left"></i>
                <span class="admin-nav__label">Mis revisiones</span>
            </a>
            <?php endif; ?>
            <?php /* Notificaciones ya no vive aquí: es transversal y está en la campana del topbar. */ ?>
        </div>

        <?php elseif ($_modActivo === 'suplencias' && _blog_puede('suplencias')): ?>
        <!-- ══════════ MÓDULO SUPLENCIAS ══════════ -->
        <div class="admin-nav__section">
            <span class="admin-nav__section-label">Suplencias</span>
            <a href="/dashboard/suplencias" title="Agenda de suplencias" class="admin-nav__link<?= _nav_active('/dashboard/suplencias') ?>">
                <i class="fa-solid fa-user-clock"></i>
                <span class="admin-nav__label">Agenda</span>
            </a>
            <a href="/dashboard/suplencias/solicitar" title="Solicitar suplencia" class="admin-nav__link<?= _nav_active('/dashboard/suplencias/solicitar') ?>">
                <i class="fa-solid fa-hand"></i>
                <span class="admin-nav__label">Solicitar</span>
            </a>
            <a href="/dashboard/suplencias/mis-coberturas" title="Mis coberturas" class="admin-nav__link<?= _nav_active('/dashboard/suplencias/mis-coberturas') ?>">
                <i class="fa-solid fa-clipboard-check"></i>
                <span class="admin-nav__label">Mis coberturas</span>
            </a>
            <?php if ($_esAdmin): ?>
            <a href="/dashboard/suplencias/dashboard" title="Tablero de estadísticas" class="admin-nav__link<?= _nav_active('/dashboard/suplencias/dashboard') ?>">
                <i class="fa-solid fa-chart-line"></i>
                <span class="admin-nav__label">Tablero</span>
            </a>
            <?php endif; ?>
        </div>

        <?php elseif ($_modActivo === 'horarios' && _blog_puede('horarios')): ?>
        <!-- ══════════ MÓDULO HORARIOS ══════════ -->
        <div class="admin-nav__section">
            <span class="admin-nav__section-label">Horarios</span>
            <a href="/dashboard/horarios/profesor" title="Por profesor" class="admin-nav__link<?= _nav_active_prefix('/dashboard/horarios/profesor') ?>">
                <i class="fa-solid fa-chalkboard-user"></i>
                <span class="admin-nav__label">Por profesor</span>
            </a>
            <a href="/dashboard/horarios/aula" title="Por aula" class="admin-nav__link<?= _nav_active_prefix('/dashboard/horarios/aula') ?>">
                <i class="fa-solid fa-door-open"></i>
                <span class="admin-nav__label">Por aula</span>
            </a>
            <a href="/dashboard/horarios/grupo" title="Por grupo" class="admin-nav__link<?= _nav_active_prefix('/dashboard/horarios/grupo') ?>">
                <i class="fa-solid fa-users-rectangle"></i>
                <span class="admin-nav__label">Por grupo</span>
            </a>
            <a href="/dashboard/horarios/mi-horario" title="Mi horario" class="admin-nav__link<?= _nav_active('/dashboard/horarios/mi-horario') ?>">
                <i class="fa-regular fa-calendar-check"></i>
                <span class="admin-nav__label">Mi horario</span>
            </a>
        </div>
        <?php /* La carga del horario es destructiva (reemplaza el del profesor entero):
                 va en su propia sección de superadmin, no mezclada con la consulta. */ ?>
        <?php if ($_esSuper): ?>
        <div class="admin-nav__section">
            <span class="admin-nav__section-label">Administración</span>
            <a href="/dashboard/horarios/importar" title="Importar horarios (CSV)" class="admin-nav__link<?= _nav_active('/dashboard/horarios/importar') ?>">
                <i class="fa-solid fa-file-csv"></i>
                <span class="admin-nav__label">Importar CSV</span>
            </a>
            <a href="/dashboard/aulas" title="Aulas" class="admin-nav__link<?= _nav_active_prefix('/dashboard/aulas') ?>">
                <i class="fa-solid fa-door-open"></i>
                <span class="admin-nav__label">Aulas</span>
            </a>
            <a href="/dashboard/grupos" title="Grupos" class="admin-nav__link<?= _nav_active_prefix('/dashboard/grupos') ?>">
                <i class="fa-solid fa-layer-group"></i>
                <span class="admin-nav__label">Grupos</span>
            </a>
        </div>
        <?php endif; ?>

        <?php elseif ($_modActivo === 'eventos' && _blog_puede('eventos')): ?>
        <!-- ══════════ MÓDULO EVENTOS ══════════ -->
        <div class="admin-nav__section">
            <span class="admin-nav__section-label">Eventos</span>
            <a href="/dashboard/eventos" title="Calendario" class="admin-nav__link<?= _nav_active('/dashboard/eventos') ?>">
                <i class="fa-solid fa-calendar-day"></i>
                <span class="admin-nav__label">Calendario</span>
            </a>
            <a href="/dashboard/eventos/crear" title="Nuevo evento" class="admin-nav__link<?= _nav_active('/dashboard/eventos/crear') ?>">
                <i class="fa-solid fa-calendar-plus"></i>
                <span class="admin-nav__label">Nuevo evento</span>
            </a>
        </div>

        <?php elseif (in_array($_modActivo, ['aulas','grupos'], true) && $_esSuper): ?>
        <!-- ══════════ CATÁLOGOS ACADÉMICOS (superadmin) ══════════ -->
        <div class="admin-nav__section">
            <span class="admin-nav__section-label">Catálogos</span>
            <a href="/dashboard/aulas" title="Aulas" class="admin-nav__link<?= _nav_active('/dashboard/aulas') ?>">
                <i class="fa-solid fa-door-open"></i>
                <span class="admin-nav__label">Aulas</span>
            </a>
            <a href="/dashboard/aulas/crear" title="Nueva aula" class="admin-nav__link<?= _nav_active('/dashboard/aulas/crear') ?>">
                <i class="fa-solid fa-plus"></i>
                <span class="admin-nav__label">Nueva aula</span>
            </a>
            <a href="/dashboard/grupos" title="Grupos" class="admin-nav__link<?= _nav_active('/dashboard/grupos') ?>">
                <i class="fa-solid fa-layer-group"></i>
                <span class="admin-nav__label">Grupos</span>
            </a>
            <a href="/dashboard/grupos/crear" title="Nuevo grupo" class="admin-nav__link<?= _nav_active('/dashboard/grupos/crear') ?>">
                <i class="fa-solid fa-plus"></i>
                <span class="admin-nav__label">Nuevo grupo</span>
            </a>
        </div>
        <div class="admin-nav__section">
            <span class="admin-nav__section-label">Horarios</span>
            <a href="/dashboard/horarios/aula" title="Horario por aula" class="admin-nav__link">
                <i class="fa-solid fa-table-cells"></i>
                <span class="admin-nav__label">Ver horarios</span>
            </a>
            <a href="/dashboard/horarios/importar" title="Importar horarios (CSV)" class="admin-nav__link<?= _nav_active('/dashboard/horarios/importar') ?>">
                <i class="fa-solid fa-file-csv"></i>
                <span class="admin-nav__label">Importar CSV</span>
            </a>
        </div>

        <?php elseif (in_array($_modActivo, ['profesores','prefectura','administrativos'], true) && $_esSuper): ?>
        <!-- ══════════ DIRECTORIOS DE PERSONAL (superadmin) ══════════ -->
        <div class="admin-nav__section">
            <span class="admin-nav__section-label">Personal</span>
            <a href="/dashboard/profesores" title="Profesores" class="admin-nav__link<?= _nav_active('/dashboard/profesores') ?>">
                <i class="fa-solid fa-chalkboard-user"></i>
                <span class="admin-nav__label">Profesores</span>
            </a>
            <a href="/dashboard/prefectura" title="Prefectura" class="admin-nav__link<?= _nav_active('/dashboard/prefectura') ?>">
                <i class="fa-solid fa-user-shield"></i>
                <span class="admin-nav__label">Prefectura</span>
            </a>
            <a href="/dashboard/administrativos" title="Administrativos" class="admin-nav__link<?= _nav_active('/dashboard/administrativos') ?>">
                <i class="fa-solid fa-user-tie"></i>
                <span class="admin-nav__label">Administrativos</span>
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
        <?php /* Mismas categorías y mismo orden que el home (views/blog/_modulos.php). */ ?>
        <?php foreach ($_grupos as $_g): ?>
        <div class="admin-nav__section">
            <span class="admin-nav__section-label"><?= htmlspecialchars($_g['label']) ?></span>
            <?php foreach ($_g['claves'] as $_k): $_m = $_catMods[$_k]; ?>
            <a href="<?= $_m['url'] ?>" title="<?= htmlspecialchars($_m['nombre']) ?>" class="admin-nav__link">
                <i class="fa-solid <?= $_m['icon'] ?>"></i>
                <span class="admin-nav__label"><?= htmlspecialchars($_m['nombre']) ?></span>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </nav>
    <div class="admin-sidebar__alex admin-sidebar__alex--collapsible">
        <img src="/build/assets/img/alex/alex-toca.png" alt="Alex">
        <p>Tu espacio de trabajo<br>Colegio Bilbao</p>
    </div>
</aside>


