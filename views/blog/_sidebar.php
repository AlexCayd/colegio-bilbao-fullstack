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

// Catálogo, subnavegación y helper de coordinación: fuente única con el home.
require_once __DIR__ . '/_modulos.php';

if (!function_exists('_blog_puede')) {
    // ¿El usuario en sesión tiene acceso al módulo? Admin = todos.
    function _blog_puede(string $modulo): bool {
        $u = $_SESSION['blog_usuario'] ?? null;
        if (!$u) return false;
        if (($u['rol'] ?? '') === 'administrador') return true;
        $lista = array_filter(array_map('trim', explode(',', (string)($u['modulos'] ?? ''))));
        return in_array($modulo, $lista, true);
    }
    // ¿Puede validar contenido editorial (revisiones + testimoniales)?
    function _blog_puede_revisar(): bool {
        $u = $_SESSION['blog_usuario'] ?? null;
        if (!$u) return false;
        if (($u['rol'] ?? '') === 'administrador') return true;
        return ($u['rol_redaccion'] ?? '') === 'revisor' && _blog_puede('redaccion');
    }
    /** ¿Coordina la operación académica? Espeja BlogController::puedeCoordinar().
     *  La regla vive en _modulos.php, que también la usa para elegir el texto y el
     *  destino de las tarjetas de módulo: una sola definición. */
    function _blog_coordina(): bool {
        return blog_modulos_coordina();
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
elseif (str_starts_with($_cp, '/dashboard/directivos'))      $_modActivo = 'directivos';
elseif (str_starts_with($_cp, '/dashboard/notificaciones'))  $_modActivo = 'notificaciones';
elseif ($_cp === '/dashboard')                               $_modActivo = 'home';
// Redacción se declara por sus rutas, no por descarte. Con un `else` cayendo aquí,
// /dashboard/notificaciones y /dashboard/perfil se pintaban como si fueran de
// Redacción, con una miga que un profesor ni siquiera puede abrir.
elseif (preg_match('#^/dashboard/(redaccion|articulos|categorias|noticias|revisiones|testimoniales|autores)#', $_cp)) {
    $_modActivo = 'redaccion';
}
else                                                         $_modActivo = 'home';

$_esAdmin   = ($_SESSION['blog_usuario']['rol'] ?? '') === 'administrador';
$_coordina  = _blog_coordina();
$_puedeRev  = _blog_puede_revisar();

// Sin argumento, el catálogo se adapta al rol de quien mira: un profesor raso ve
// "Mi horario" en vez de la vista general de horarios.
$_catMods = blog_modulos_catalogo();

// Módulos del usuario. Para el admin son TODOS los asignables: antes esta lista
// estaba escrita a mano con cinco claves y dejaba fuera aulas, grupos y los tres
// directorios de personal, así que el sidebar mostraba menos módulos que el home.
// Ahora ambos parten del mismo sitio (igual que BlogController::modulosDisponibles()).
$_misMods = $_esAdmin
    ? \Model\UsuarioBlog::MODULOS_ASIGNABLES
    : array_values(array_filter(array_map('trim', explode(',', (string)($_SESSION['blog_usuario']['modulos'] ?? '')))));
// Los transversales (Soporte + los cuatro directorios) no se asignan: los tiene todo el
// mundo. Se unen aquí igual que en BlogController::modulosDisponibles(), leyendo la
// MISMA constante — antes 'soporte' estaba empujado a mano y era una segunda lista.
$_misMods = array_values(array_unique(array_merge($_misMods, \Model\UsuarioBlog::MODULOS_TRANSVERSALES)));
$_grupos  = blog_modulos_visibles($_misMods);

// Contador de la campana. Se calcula aquí porque el sidebar se incluye ANTES que
// _topbar-avatar.php, que es quien lo memoizaba: sin esto el badge del sidebar
// salía siempre vacío. La misma variable la reusan el topbar y el modal de Alex,
// así que la consulta sigue siendo una sola por petición.
if (!isset($GLOBALS['_notifsPendientes'])) {
    $GLOBALS['_notifsPendientes'] = (!empty($_SESSION['blog_usuario']) && class_exists(\Model\Notificacion::class))
        ? \Model\Notificacion::noLeidasPorUsuario((int)$_SESSION['blog_usuario']['id'])
        : 0;
}

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
    'directivos'      => ['label' => 'Directivos',     'icon' => 'fa-user-gear'],
    // Transversal: no es un módulo asignable, pero sí un destino con su propia miga.
    'notificaciones'  => ['label' => 'Notificaciones', 'icon' => 'fa-bell'],
];

// ── Breadcrumb total (Inicio › Módulo › Subpágina) ──
$_segMap = [
    'articulos' => 'Artículos', 'categorias' => 'Categorías', 'noticias' => 'Noticias',
    'revisiones' => 'Revisiones', 'mis-revisiones' => 'Mis revisiones', 'testimoniales' => 'Testimoniales',
    'autores' => 'Autores', 'notificaciones' => 'Notificaciones', 'cumpleanos' => 'Cumpleaños',
    'profesor' => 'Por profesor', 'aula' => 'Por aula', 'grupo' => 'Por grupo',
    'mi-horario' => 'Mi horario', 'importar' => 'Importar CSV',
    'dashboard' => 'Tablero', 'solicitar' => 'Solicitar', 'validar' => 'Validar',
    'agendar' => 'Agendar', 'mis-coberturas' => 'Mis suplencias',
    'historial' => 'Histórico del plantel', 'justificantes' => 'Justificantes',
    'horario' => 'Horario',
    'profesores' => 'Profesores', 'prefectura' => 'Prefectura', 'administrativos' => 'Administrativos',
    'directivos' => 'Directivos',
    'aulas' => 'Aulas', 'grupos' => 'Grupos',
    'crear' => 'Nuevo', 'editar' => 'Editar',
];
// La raíz de Suplencias y la de Horarios dependen de quién mira (quien no coordina no
// puede abrir la agenda del claustro ni los horarios ajenos), así que salen del catálogo
// en vez de estar escritas a mano: si no, la miga del módulo enlazaba a un redirect.
$_modUrl = [
    'redaccion' => '/dashboard/redaccion',
    'suplencias' => $_catMods['suplencias']['url'], 'horarios' => $_catMods['horarios']['url'],
    'eventos' => '/dashboard/eventos', 'usuarios' => '/dashboard/usuarios',
    'aulas' => '/dashboard/aulas', 'grupos' => '/dashboard/grupos',
    'profesores' => '/dashboard/profesores', 'prefectura' => '/dashboard/prefectura', 'administrativos' => '/dashboard/administrativos',
    'directivos' => '/dashboard/directivos',
    'notificaciones' => '/dashboard/notificaciones',
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


        <?php /* ══════════ MÓDULOS ══════════
                 El sidebar es PERMANENTE: siempre están todos los módulos del usuario,
                 agrupados por las mismas categorías del home. El módulo activo se abre
                 en acordeón y marca su subpágina, así que en todo momento se ve dónde
                 estás y sigues teniendo el resto del panel a un clic.

                 Antes era contextual —ocho ramas `elseif ($_modActivo === ...)`, solo
                 se pintaba la del módulo activo— y desde Suplencias había que pasar por
                 Inicio para llegar a cualquier otra cosa. Las subopciones viven ahora en
                 blog_modulos_subnav() (views/blog/_modulos.php). */ ?>
        <?php foreach ($_grupos as $_g): ?>
        <div class="admin-nav__section">
            <span class="admin-nav__section-label"><?= htmlspecialchars($_g['label']) ?></span>

            <?php foreach ($_g['claves'] as $_k):
                $_m      = $_catMods[$_k];
                $_sub    = blog_modulos_subnav($_k);
                $_actual = ($_modActivo === $_k);

                // Un acordeón cuya única opción es el propio módulo no aporta nada:
                // le pasa a Horarios con un profesor raso, que solo ve "Mi horario"
                // y el módulo ya apunta ahí. Se degrada a enlace directo.
                if (count($_sub) === 1 && $_sub[0]['url'] === $_m['url']) $_sub = [];
            ?>

                <?php if (!$_sub): ?>
                <?php /* Sin subopciones (directorios de personal, o el caso de arriba): enlace directo */ ?>
                <a href="<?= $_m['url'] ?>" title="<?= htmlspecialchars($_m['nombre']) ?>"
                   class="admin-nav__link<?= $_actual ? ' is-current' : '' ?>">
                    <i class="fa-solid <?= $_m['icon'] ?>"></i>
                    <span class="admin-nav__label"><?= htmlspecialchars($_m['nombre']) ?></span>
                </a>

                <?php else: ?>
                <div class="admin-nav__mod<?= $_actual ? ' is-current' : '' ?>" data-nav-mod>
                    <?php /* El acordeón del módulo activo arranca abierto; el resto, cerrados.
                             Sin persistencia: cada navegación reafirma dónde estás. */ ?>
                    <button type="button" class="admin-nav__link admin-nav__toggle"
                            data-nav-toggle aria-expanded="<?= $_actual ? 'true' : 'false' ?>"
                            title="<?= htmlspecialchars($_m['nombre']) ?>">
                        <i class="fa-solid <?= $_m['icon'] ?>"></i>
                        <span class="admin-nav__label"><?= htmlspecialchars($_m['nombre']) ?></span>
                        <i class="fa-solid fa-chevron-down admin-nav__caret" aria-hidden="true"></i>
                    </button>

                    <div class="admin-nav__sub" data-nav-sub<?= $_actual ? '' : ' hidden' ?>>
                        <?php foreach ($_sub as $_s): ?>
                        <a href="<?= $_s['url'] ?>" title="<?= htmlspecialchars($_s['label']) ?>"
                           class="admin-nav__sublink<?= !empty($_s['prefijo']) ? _nav_active_prefix($_s['url']) : _nav_active($_s['url']) ?>">
                            <i class="<?= str_starts_with($_s['icon'], 'fa-regular') ? '' : 'fa-solid ' ?><?= $_s['icon'] ?>"></i>
                            <span class="admin-nav__label"><?= htmlspecialchars($_s['label']) ?></span>
                            <?php /* Contador de lo que espera acción. Sin él, dos colas del módulo
                                     —justificantes y trabajo por revisar— solo se descubrían
                                     entrando a mirar, y `contarColaJustificantes()` llevaba desde su
                                     creación con un docblock que prometía este badge y ningún
                                     llamador. Se pinta solo con valor: un 0 permanente es ruido. */ ?>
                            <?php if (!empty($_s['badge'])): ?>
                            <span class="admin-nav__badge"><?= (int)$_s['badge'] > 99 ? '99+' : (int)$_s['badge'] ?></span>
                            <?php endif; ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>

        <?php /* "Ver sitio público" cierra la lista porque es la salida, no una sección.

                 ⚠️ Notificaciones NO está aquí, y es deliberado: la bandeja ya tiene su
                 acceso permanente en la CAMPANA del topbar, con el mismo badge de
                 pendientes y en todas las pantallas del panel. Tenerla en los dos sitios
                 duplicaba el contador y metía en la lista de módulos algo que no lo es.
                 El módulo activo sigue detectándose (`$_modActivo === 'notificaciones'`)
                 para que el breadcrumb diga «Inicio › Notificaciones». */ ?>
        <div class="admin-nav__section admin-nav__section--fin">
            <a href="/" target="_blank" rel="noopener" title="Ver sitio público" class="admin-nav__link admin-nav__link--salida">
                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                <span class="admin-nav__label">Ver sitio público</span>
            </a>
        </div>
    </nav>
    <div class="admin-sidebar__alex admin-sidebar__alex--collapsible">
        <img src="/build/assets/img/alex/alex-toca.png" alt="Alex">
        <p>Tu espacio de trabajo<br>Colegio Bilbao</p>
    </div>

    <?php /* ══════════ CUENTA · SOLO MÓVIL (≤1024px) ══════════
             Por debajo del ancho en que el sidebar pasa a cajón, el topbar se queda
             solo con la miga y el hamburger: cuatro círculos de 34px más el disparador
             del cajón no caben en 56px de alto sin dejar la ruta en dos palabras, y la
             miga es lo único que dice en qué pantalla estás. Campana, avatar y logout
             bajan aquí.

             ⚠️ Va AL PIE del cajón y anclado con `position:sticky`, no arriba. El cajón
             se abre en cada navegación móvil y esto son ~96px que empujarían la lista
             de módulos —lo frecuente— hacia abajo por algo que se usa una vez al día;
             además arriba es la peor zona del pulgar en un panel a altura completa y
             abajo la mejor, así que estaba justo del revés. Anclado se ve igual sin
             desplazar, que era lo único que ganaba estando arriba.

             Va en el sidebar y NO en `_topbar-avatar.php` porque son dos superficies
             distintas: aquel pinta el cluster del topbar y este el cajón. El dato es
             el mismo y por eso el contador se cachea en `$GLOBALS['_notifsPendientes']`
             —la misma clave que leen `_topbar-avatar.php` y el modal de Alex de
             `layout-admin.php`—, así que la consulta se hace UNA vez y da igual cuál de
             los tres se incluya primero.

             ⚠️ Se oculta con `display:none` fuera de móvil, así que sale duplicado en
             el HTML de escritorio. Es a propósito: el breakpoint es de CSS y montarlo
             en JS dejaría el cajón sin cuenta mientras el bundle no cargue, que es
             justo el escenario contra el que existe `html.js`. */ ?>
    <?php if (!empty($_SESSION['blog_usuario'])):
        $_sbNombre  = $_SESSION['blog_usuario']['nombre'] ?? 'Usuario';
        $_sbInicial = mb_strtoupper(mb_substr($_sbNombre, 0, 1, 'UTF-8'), 'UTF-8');
        $_sbAvatar  = $_SESSION['blog_usuario']['avatar'] ?? '';
        if (!isset($GLOBALS['_notifsPendientes'])) {
            $GLOBALS['_notifsPendientes'] = class_exists(\Model\Notificacion::class)
                ? \Model\Notificacion::noLeidasPorUsuario((int)$_SESSION['blog_usuario']['id'])
                : 0;
        }
        $_sbNotifs = (int) $GLOBALS['_notifsPendientes'];
    ?>
    <div class="admin-sidebar__cuenta">
        <a href="/dashboard/perfil" class="admin-sidebar__yo<?= _nav_active('/dashboard/perfil') ?>">
            <span class="admin-sidebar__yo-ava">
                <?php if ($_sbAvatar): ?>
                    <img src="<?= htmlspecialchars($_sbAvatar) ?>" alt="">
                <?php else: ?><?= htmlspecialchars($_sbInicial) ?><?php endif; ?>
            </span>
            <span class="admin-sidebar__yo-txt">
                <span class="admin-sidebar__yo-nombre"><?= htmlspecialchars($_sbNombre) ?></span>
                <span class="admin-sidebar__yo-sub">Ver mi perfil</span>
            </span>
        </a>
        <div class="admin-sidebar__cuenta-acts">
            <?php /* `data-notif-cta`: blog-notificaciones-index.js mueve los DOS
                     contadores —este y el de la campana— al marcar como leída. Sin la
                     marca actualizaba solo el del topbar, que en móvil está oculto, y
                     el número visible se quedaba congelado. */ ?>
            <?php /* El `title` da la unidad: sin él, el nombre accesible del enlace sale
                     como «Avisos 3» —el badge aporta su texto— y el número queda sin
                     decir de qué. Mismo patrón que la campana del topbar. */ ?>
            <a href="/dashboard/notificaciones" data-notif-cta
               title="<?= $_sbNotifs > 0 ? $_sbNotifs . ($_sbNotifs === 1 ? ' notificación' : ' notificaciones') . ' sin leer' : 'Notificaciones' ?>"
               class="admin-sidebar__cta<?= _nav_active('/dashboard/notificaciones') ?><?= $_sbNotifs > 0 ? ' has-pend' : '' ?>">
                <i class="fa-regular fa-bell"></i>
                <span>Notificaciones</span>
                <?php if ($_sbNotifs > 0): ?>
                <span class="admin-nav__badge" data-notif-badge><?= $_sbNotifs > 99 ? '99+' : $_sbNotifs ?></span>
                <?php endif; ?>
            </a>
            <?php /* POST, no enlace: cerrar sesión cambia estado del servidor.
                     La etiqueta se oculta VISUALMENTE con clip-path (ver el SCSS) y no se
                     borra del HTML: sin ella el <button> se quedaría sin nombre accesible.
                     El glifo es el mismo del logout del topbar en escritorio, así que no
                     hay nada nuevo que adivinar. */ ?>
            <form action="/logout" method="POST">
                <button type="submit" class="admin-sidebar__cta admin-sidebar__cta--salir"
                        title="Cerrar sesión">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    <span>Salir</span>
                </button>
            </form>
        </div>
    </div>
    <?php endif; ?>
</aside>


