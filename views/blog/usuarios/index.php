<?php $paginaVista = 'blog-usuarios-index'; ?>
<?php
$avatarColors = ['#4D8ABB', '#374C69', '#38A169', '#E67E22', '#9B59B6', '#319795'];

// Solo el admin gestiona usuarios; el resto entra en solo lectura.
$puedeGestionar = ($_SESSION['blog_usuario']['rol'] ?? '') === 'administrador';

function formatAcceso(?string $fecha): string {
    if (!$fecha) return 'Nunca';
    $ts   = strtotime($fecha);
    $hoy  = strtotime('today');
    $ayer = strtotime('yesterday');
    if ($ts >= $hoy)  return 'Hoy, '   . date('H:i', $ts);
    if ($ts >= $ayer) return 'Ayer, '  . date('H:i', $ts);
    return date('d M Y', $ts);
}

function rolBadgeClass(string $rol): string {
    return $rol === 'administrador' ? 'admin-badge--published' : '';
}

/** Etiqueta de rol para la UI: `administrador` se muestra como "Admin". */
function rolLabel(string $rol): string {
    return $rol === 'administrador' ? 'Admin' : 'Usuario';
}

// Reparto en tres tablas. Un usuario con varios tipos aparece en todas las que le
// correspondan (p. ej. profesor + administrativo sale en dos), así que no son
// grupos excluyentes: se filtra la misma lista tres veces.
// Orden de presentación fijo (administrativo · profesor · prefecto · directivo), el
// mismo de UsuarioBlog::normalizarTipoPersonal(). No basta con el orden del CSV: los
// registros guardados antes del cambio conservan el anterior y solo se reescriben al
// volver a guardarlos. `directivo` faltaba aquí, así que un directivo se quedaba sin
// etiqueta de tipo y fuera de los tres grupos de la lista.
$ORDEN_TIPOS = ['administrativo', 'profesor', 'prefecto', 'directivo'];
$tiposDe = function ($u) use ($ORDEN_TIPOS) {
    $lista = array_filter(array_map('trim', explode(',', (string)($u->tipo_personal ?? ''))));
    return array_values(array_intersect($ORDEN_TIPOS, $lista));
};

$grupos = [
    [
        'clave'  => 'administradores',
        'titulo' => 'Administradores',
        'sub'    => 'Acceso a todos los módulos del panel.',
        'icon'   => 'fa-user-shield',
        'lista'  => array_values(array_filter($usuarios ?? [],
            fn($u) => $u->rol === 'administrador')),
    ],
    [
        'clave'  => 'profesores',
        'titulo' => 'Profesores',
        'sub'    => 'Personal docente: imparte clase y cubre suplencias.',
        'icon'   => 'fa-chalkboard-user',
        'lista'  => array_values(array_filter($usuarios ?? [],
            fn($u) => in_array('profesor', $tiposDe($u), true))),
    ],
    [
        'clave'  => 'administrativos',
        'titulo' => 'Administrativos, prefectura y dirección',
        'sub'    => 'Coordinan y registran; no cubren suplencias.',
        'icon'   => 'fa-user-tie',
        'lista'  => array_values(array_filter($usuarios ?? [],
            fn($u) => (bool)array_intersect(['administrativo', 'prefecto', 'directivo'], $tiposDe($u)))),
    ],
];
?>

<div class="admin-layout">

    <!-- ===================== SIDEBAR ===================== -->
    <?php include __DIR__ . '/../_sidebar.php'; ?>

    <!-- ===================== MAIN ===================== -->
    <div class="admin-main">
        <header class="admin-topbar">
            <div class="admin-topbar__left">
                <span class="admin-topbar__title">Usuarios</span>
            </div>
            <div class="admin-topbar__actions">
                <?php if ($puedeGestionar): ?>
                <a href="/dashboard/usuarios/crear" class="admin-topbar__new-btn">
                    <i class="fa-solid fa-plus"></i> Nuevo usuario
                </a>
                <?php endif; ?>
                <?php include __DIR__ . '/../_topbar-avatar.php'; ?>
            </div>
        </header>

        <main class="admin-content">

            <?php /* Dirección revisa pero no edita: el guard real es requireEscritura(). */
            if (blog_modulos_solo_lectura()) { $soloLecturaQue = 'el directorio de colaboradores'; include __DIR__ . '/../_solo-lectura.php'; } ?>


            <?php /* Los toasts de created / edited / deleted se renderizan al final de la página */ ?>

            <?php if (empty($usuarios)): ?>
            <div class="admin-panel">
                <div class="admin-empty-state">
                    <img src="/build/assets/img/alex/alex-volley.png" alt="Alex" class="admin-empty-state__img">
                    <p class="admin-empty-state__text">Aún no hay usuarios registrados.</p>
                    <a href="/dashboard/usuarios/crear" class="admin-btn admin-btn--primary">
                        <i class="fa-solid fa-user-plus"></i> Crear primer usuario
                    </a>
                </div>
            </div>
            <?php else: ?>

            <?php /* Buscador único para las tres tablas: filtra por nombre, correo, rol,
                     tipo de personal y módulos, sin acentos y con varios términos a la vez
                     ("juan prof"). Marca las filas descartadas con is-filtered y llama a
                     AdminTable.refrescar() para que la paginación recuente solo las visibles. */ ?>
            <div class="usr-toolbar">
                <div class="usr-search">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="search" class="usr-search__input" data-usr-buscar
                           placeholder="Buscar por nombre, correo, rol o tipo…"
                           aria-label="Buscar colaborador" autocomplete="off">
                    <button type="button" class="usr-search__clear" data-usr-limpiar hidden aria-label="Limpiar búsqueda">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
                <p class="usr-search__resumen" data-usr-resumen hidden></p>
            </div>

            <?php foreach ($grupos as $g): ?>
            <div class="admin-panel usr-panel" data-usr-panel>
                <div class="admin-panel__header">
                    <h2 class="admin-panel__title">
                        <i class="fa-solid <?= $g['icon'] ?> usr-panel__icon"></i>
                        <?= s($g['titulo']) ?>
                        <span class="admin-panel__count" data-usr-count><?= count($g['lista']) ?></span>
                    </h2>
                    <?php if ($puedeGestionar): ?>
                    <a href="/dashboard/usuarios/crear" class="admin-panel__action">+ Nuevo</a>
                    <?php endif; ?>
                </div>
                <p class="usr-panel__sub"><?= s($g['sub']) ?></p>

                <?php if (!$g['lista']): ?>
                <p class="usr-panel__vacio">Nadie en este grupo por ahora.</p>
                <?php else: ?>
                <p class="usr-panel__vacio" data-usr-empty hidden>Nadie coincide con la búsqueda en este grupo.</p>
                <div class="admin-table-scroll">
                    <table class="admin-table" data-table data-table-per="10" data-table-noun="personas">
                        <thead>
                            <tr>
                                <th data-sort="text">Usuario</th>
                                <th data-sort="text">Email</th>
                                <th data-sort="text">Rol</th>
                                <th data-sort="text">Tipo</th>
                                <th data-sort="date">Último acceso</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($g['lista'] as $i => $u): ?>
                            <?php
                                $color   = $avatarColors[$u->id % count($avatarColors)];
                                $inicial = strtoupper(mb_substr($u->nombre, 0, 1));
                                $tipos   = $tiposDe($u);
                                // Todo lo buscable de la fila en un solo atributo; el JS normaliza
                                // acentos antes de comparar, aquí basta con bajar a minúsculas.
                                $buscable = mb_strtolower(implode(' ', array_filter([
                                    $u->nombre,
                                    $u->email,
                                    rolLabel($u->rol),
                                    implode(' ', $tipos),
                                    str_replace(',', ' ', (string)($u->modulos ?? '')),
                                    str_replace(',', ' ', (string)($u->niveles ?? '')),
                                ])), 'UTF-8');
                                $niveles = array_filter(array_map('trim', explode(',', (string)($u->niveles ?? ''))));
                            ?>
                            <tr data-pager-item data-usr-row data-buscar="<?= s($buscable) ?>"<?= $i >= 10 ? ' class="is-hidden"' : '' ?>>
                                <td data-val="<?= s($u->nombre) ?>">
                                    <div style="display:flex;align-items:center;gap:.75rem;">
                                        <div class="admin-topbar__avatar" style="width:38px;height:38px;font-size:.875rem;flex-shrink:0;background:<?= s($color) ?>;">
                                            <?php if ($u->avatar): ?>
                                                <img src="<?= s($u->avatar) ?>" alt="" style="width:100%;height:100%;object-fit:cover;" onerror="this.parentElement.textContent='<?= s($inicial) ?>'">
                                            <?php else: ?>
                                                <?= s($inicial) ?>
                                            <?php endif; ?>
                                        </div>
                                        <div class="admin-table__title"><?= s($u->nombre) ?></div>
                                    </div>
                                </td>
                                <td style="color:var(--text-gray);font-size:.875rem;"><?= s($u->email) ?></td>
                                <td data-val="<?= s(rolLabel($u->rol)) ?>">
                                    <span class="admin-badge <?= rolBadgeClass($u->rol) ?>"><?= s(rolLabel($u->rol)) ?></span>
                                </td>
                                <td data-val="<?= s(implode(',', $tipos)) ?>">
                                    <?php if ($tipos): ?>
                                        <?php foreach ($tipos as $t): ?>
                                        <span class="usr-tipo usr-tipo--<?= s($t) ?>"><?= s(\Model\UsuarioBlog::TIPO_LABEL[$t] ?? ucfirst($t)) ?></span>
                                        <?php endforeach; ?>
                                        <?php /* Los niveles cuelgan del tipo en vez de ocupar columna propia:
                                                  solo los tiene el profesorado y la tabla ya va con seis. */ ?>
                                        <?php if ($niveles): ?>
                                        <span class="usr-niveles" title="Niveles que imparte"><?= s(implode(' · ', $niveles)) ?></span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="usr-nil">—</span>
                                    <?php endif; ?>
                                </td>
                                <?php /* data-val en Y-m-d: "Hoy, 09:14" no ordena cronológicamente */ ?>
                                <td data-val="<?= $u->ultimo_acceso ? s(date('Y-m-d H:i', strtotime($u->ultimo_acceso))) : '' ?>"
                                    style="font-size:.85rem;color:var(--text-gray);"><?= s(formatAcceso($u->ultimo_acceso)) ?></td>
                                <td>
                                    <?php if ($puedeGestionar): ?>
                                    <div class="admin-table__actions">
                                        <a href="/dashboard/usuarios/editar?id=<?= (int)$u->id ?>" class="admin-act admin-act--edit" title="Editar usuario">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>
                                        <?php /* Solo el profesorado tiene horario que editar */ ?>
                                        <?php if (in_array('profesor', $tipos, true)): ?>
                                        <a href="/dashboard/usuarios/horario?id=<?= (int)$u->id ?>" class="admin-act admin-act--horario-edit" title="Editar horario">
                                            <i class="fa-regular fa-calendar-plus"></i>
                                        </a>
                                        <?php endif; ?>
                                        <button
                                            type="button"
                                            class="admin-act admin-act--del"
                                            onclick="confirmarEliminar(<?= (int)$u->id ?>, '<?= s(addslashes($u->nombre)) ?>')"
                                            title="Eliminar usuario"
                                        >
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </div>
                                    <?php else: ?>
                                    <span class="usr-nil">Solo lectura</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>

            <?php endif; ?>

        </main>
    </div>

</div>

<!-- MODAL ELIMINAR -->
<!-- MODAL ELIMINAR -->
<div id="deleteModal" class="ubm-backdrop" aria-modal="true" role="dialog" aria-labelledby="ubm-title">
    <div class="ubm-card">
        <div class="ubm-icon"><i class="fa-solid fa-user-xmark"></i></div>
        <h2 class="ubm-title" id="ubm-title">¿Eliminar usuario?</h2>
        <p class="ubm-text">Estás a punto de eliminar a <strong id="ubm-name"></strong>. Esta acción es permanente y no se puede deshacer.</p>
        <div class="ubm-field">
            <label class="ubm-field__label" for="ubm-input">Escribe el nombre completo para confirmar</label>
            <div class="ubm-field__wrap">
                <input type="text" id="ubm-input" class="ubm-field__input" placeholder="Nombre completo" autocomplete="off" spellcheck="false">
                <i class="fa-solid fa-check ubm-field__check" id="ubm-check"></i>
            </div>
        </div>
        <form method="POST" action="/dashboard/usuarios/eliminar" id="ubm-form">
            <input type="hidden" name="id" id="ubm-id">
            <div class="ubm-actions">
                <button type="button" class="admin-btn admin-btn--ghost" onclick="cerrarModalEliminar()">Cancelar</button>
                <button type="submit" class="admin-btn ubm-confirm" id="ubm-submit" disabled>
                    <i class="fa-solid fa-trash-can"></i> Sí, eliminar
                </button>
            </div>
        </form>
    </div>
</div>



<?php
/* ── Configuración de toasts ── */
$atConfig = null;
if ($success ?? false) {
    $atConfig = [
        'title'  => '¡Usuario creado!',
        'msg'    => 'El nuevo usuario ya tiene acceso al panel del blog.',
        'icon'   => 'fa-user-plus',
        'color'  => '#4D8ABB',
        'bar'    => '#4D8ABB',
    ];
} elseif (isset($_GET['edited'])) {
    $atConfig = [
        'title'  => '¡Cambios guardados!',
        'msg'    => 'La información del usuario fue actualizada correctamente.',
        'icon'   => 'fa-floppy-disk',
        'color'  => '#319795',
        'bar'    => '#319795',
    ];
} elseif (isset($_GET['deleted'])) {
    $atConfig = [
        'title'  => '¡Usuario eliminado!',
        'msg'    => 'El usuario fue removido del sistema. La lista está actualizada.',
        'icon'   => 'fa-circle-check',
        'color'  => '#38a169',
        'bar'    => '#38a169',
    ];
}
?>
<?php if ($atConfig): ?>
<div id="alexToast" class="at-wrap" role="alert" aria-live="polite">
    <span class="at-stripe" style="background:<?= $atConfig['bar'] ?>;"></span>
    <img src="/build/assets/img/alex/alex-volley.png" alt="Alex" class="at-alex">
    <div class="at-body">
        <p class="at-title" style="color:<?= $atConfig['color'] ?>;">
            <i class="fa-solid <?= $atConfig['icon'] ?>"></i> <?= $atConfig['title'] ?>
        </p>
        <p class="at-msg"><?= $atConfig['msg'] ?></p>
    </div>
    <button class="at-close" onclick="cerrarAlexToast()" aria-label="Cerrar">
        <i class="fa-solid fa-xmark"></i>
    </button>
    <span class="at-bar" style="background:<?= $atConfig['bar'] ?>;"></span>
</div>


<?php endif; ?>
