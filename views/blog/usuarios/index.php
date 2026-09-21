<?php $paginaVista = 'blog-usuarios-index'; ?>
<?php
// Se requiere aquí y no se espera al sidebar: este bloque corre ANTES del include.
require_once __DIR__ . '/../_modulos.php';

$avatarColors = ['#4D8ABB', '#374C69', '#38A169', '#E67E22', '#9B59B6', '#319795'];

// Solo el admin gestiona usuarios; el resto entra en solo lectura.
$puedeGestionar = ($_SESSION['blog_usuario']['rol'] ?? '') === 'administrador';
// La ficha la abre quien coordina: expone motivos de ausencia y horarios ajenos.
$puedeFicha = blog_modulos_coordina();

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
//
// Orden de presentación FIJO y completo: no basta con el orden del CSV (los registros
// guardados antes del cambio conservan el anterior y solo se reescriben al volver a
// guardarlos), y si falta un tipo —a `directivo` le pasó— quien lo tenga se queda sin
// etiqueta y fuera de los tres grupos.
//
// Encabeza el puesto DOMINANTE, porque la celda pinta un solo chip y el color sale de
// `$tipos[0]`: `prefecto` y `directivo` son excluyentes, así que solo compiten
// `profesor` y `administrativo`, y ahí pesa más dar clase — es lo que decide si esa
// persona aparece en horarios, suplencias e intercambios. La pertenencia a cada grupo
// se comprueba con `in_array`, así que el orden no la toca.
$ORDEN_TIPOS = ['directivo', 'prefecto', 'profesor', 'administrativo'];
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
                <?php /* La acción va aquí y NO repetida en las tres cabeceras de panel: el
                         destino es el mismo, y tres botones idénticos en la misma pantalla
                         hacen dudar de si crean cosas distintas. Junto al buscador queda en
                         la barra de herramientas de la lista, que es donde se busca. */ ?>
                <?php if ($puedeGestionar): ?>
                <a href="/dashboard/usuarios/crear" class="admin-new-btn usr-toolbar__new">
                    <i class="fa-solid fa-plus"></i> Nuevo usuario
                </a>
                <?php endif; ?>
            </div>

            <?php foreach ($grupos as $g): ?>
            <div class="admin-panel usr-panel" data-usr-panel>
                <div class="admin-panel__header">
                    <h2 class="admin-panel__title">
                        <i class="fa-solid <?= $g['icon'] ?> usr-panel__icon"></i>
                        <?= s($g['titulo']) ?>
                        <span class="admin-panel__count" data-usr-count><?= count($g['lista']) ?></span>
                    </h2>
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
                                // Baja lógica: la cuenta existe y conserva todo su histórico, pero
                                // no entra al panel ni sale como candidata a suplir. La pone un admin
                                // desde la FICHA (aquí solo se muestra), o la importación de horarios
                                // con quien deja de aparecer en el archivo.
                                $off     = (int)($u->activo ?? 1) === 0;
                                $clasesTr = trim(($i >= 10 ? 'is-hidden ' : '') . ($off ? 'is-off' : ''));
                            ?>
                            <tr data-pager-item data-usr-row data-buscar="<?= s($buscable) ?>"<?= $clasesTr ? ' class="' . $clasesTr . '"' : '' ?>>
                                <?php /* `data-label` alimenta el `::before` de cada celda cuando la tabla
                                         se apila en tarjetas (≤900px). La primera no lo lleva: ahí el
                                         colaborador es la cabecera de la tarjeta, no un campo más. */ ?>
                                <td data-val="<?= s($u->nombre) ?>">
                                    <div class="usr-user">
                                        <div class="admin-topbar__avatar usr-ava" style="background:<?= s($color) ?>;">
                                            <?php if ($u->avatar): ?>
                                                <img src="<?= s($u->avatar) ?>" alt="" onerror="this.parentElement.textContent='<?= s($inicial) ?>'">
                                            <?php else: ?>
                                                <?= s($inicial) ?>
                                            <?php endif; ?>
                                        </div>
                                        <?php /* El nombre abre la ficha. No rompe el ordenamiento: admin-table.js
                                                 usa `td.dataset.val`, que esta celda ya emite. */ ?>
                                        <div class="usr-ident">
                                            <?php if ($puedeFicha): ?>
                                            <a class="admin-table__title usr-link" href="/dashboard/usuarios/detalle?id=<?= (int)$u->id ?>"><?= s($u->nombre) ?></a>
                                            <?php else: ?>
                                            <div class="admin-table__title"><?= s($u->nombre) ?></div>
                                            <?php endif; ?>
                                            <?php if ($off): ?>
                                            <span class="usr-baja" title="No puede entrar al panel ni suplir. Conserva todo su histórico.">Baja</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td data-label="Email" class="usr-mail"><?= s($u->email) ?></td>
                                <td data-label="Rol" data-val="<?= s(rolLabel($u->rol)) ?>">
                                    <span class="admin-badge <?= rolBadgeClass($u->rol) ?>"><?= s(rolLabel($u->rol)) ?></span>
                                </td>
                                <?php /* UN chip, no uno por tipo. Con dos tipos (profesor +
                                         administrativo) más la línea de niveles, la celda se iba a
                                         tres renglones de objetos apilados y costaba más leer «qué es
                                         esta persona» que con una sola etiqueta. Los puestos se
                                         concatenan dentro del mismo pill —$ORDEN_TIPOS ya los trae con
                                         el dominante delante— y el color sale de ese primero. */ ?>
                                <td data-label="Tipo" data-val="<?= s(implode(',', $tipos)) ?>">
                                    <?php /* Envoltorio obligatorio: apilada, la celda es un flex con la
                                             etiqueta en un `::before`, y dos hijos sueltos se repartirían
                                             el ancho en vez de quedarse juntos a la derecha. */ ?>
                                    <div class="usr-tipos">
                                    <?php if ($tipos): ?>
                                        <?php $tipoTexto = implode(' · ', array_map(
                                            fn($t) => \Model\UsuarioBlog::TIPO_LABEL[$t] ?? ucfirst($t), $tipos)); ?>
                                        <span class="usr-tipo usr-tipo--<?= s($tipos[0]) ?>"><?= s($tipoTexto) ?></span>
                                        <?php /* Los niveles cuelgan del tipo en vez de ocupar columna propia:
                                                  solo los tiene el profesorado y la tabla ya va con seis.
                                                  ⚠️ El rótulo depende del PUESTO: en un profesor son los que
                                                  imparte, en un directivo los que gestiona. Decir «imparte»
                                                  en una dirección hace pensar que da clase. */ ?>
                                        <?php if ($niveles): ?>
                                        <span class="usr-niveles" title="<?= in_array('directivo', $tipos, true) ? 'Niveles que gestiona' : 'Niveles que imparte' ?>"><?= s(implode(' · ', $niveles)) ?></span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="usr-nil">—</span>
                                    <?php endif; ?>
                                    </div>
                                </td>
                                <?php /* data-val en Y-m-d: "Hoy, 09:14" no ordena cronológicamente */ ?>
                                <td data-label="Último acceso" class="usr-acceso"
                                    data-val="<?= $u->ultimo_acceso ? s(date('Y-m-d H:i', strtotime($u->ultimo_acceso))) : '' ?>"><?= s(formatAcceso($u->ultimo_acceso)) ?></td>
                                <?php /* DOS acciones, no cinco. Editar, editar horario y la baja lógica
                                         viven en la ficha, que es la pantalla de esa persona y donde
                                         además se ve sobre qué se está actuando; repetirlas aquí llenaba
                                         la fila de colores que competían entre sí (ámbar, naranja
                                         profundo, un icono suelto sin fondo y rojo) y obligaba a
                                         distinguir dos calendarios casi iguales. Aquí quedan la de
                                         lectura —azul, la que casi siempre se quiere— y la destructiva,
                                         con el rojo de la paleta. */ ?>
                                <td data-label="Acciones">
                                    <?php if ($puedeGestionar || $puedeFicha): ?>
                                    <div class="admin-table__actions">
                                        <?php if ($puedeFicha): ?>
                                        <a href="/dashboard/usuarios/detalle?id=<?= (int)$u->id ?>" class="admin-act admin-act--ficha" title="Ver ficha">
                                            <i class="fa-solid fa-id-card"></i>
                                        </a>
                                        <?php endif; ?>
                                        <?php if ($puedeGestionar): ?>
                                        <button
                                            type="button"
                                            class="admin-act admin-act--del"
                                            onclick="confirmarEliminar(<?= (int)$u->id ?>, '<?= s(addslashes($u->nombre)) ?>')"
                                            title="Eliminar usuario"
                                        >
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                        <?php endif; ?>
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
} elseif (isset($_GET['inhabilitado'])) {
    $atConfig = [
        'title'  => 'Cuenta dada de baja',
        'msg'    => 'Ya no entra al panel ni sale como suplente. Conserva todo su histórico y puedes reactivarla cuando quieras.',
        'icon'   => 'fa-power-off',
        'color'  => '#f5b400',
        'bar'    => '#f5b400',
    ];
} elseif (isset($_GET['reactivado'])) {
    $atConfig = [
        'title'  => 'Cuenta reactivada',
        'msg'    => 'Vuelve a tener acceso al panel con la contraseña de siempre.',
        'icon'   => 'fa-rotate-left',
        'color'  => '#38a169',
        'bar'    => '#38a169',
    ];
} elseif (($_GET['nobaja'] ?? '') === 'propia') {
    $atConfig = [
        'title'  => 'No puedes darte de baja a ti mismo',
        'msg'    => 'Te dejaría fuera del panel sin nadie que lo revierta. Pídeselo a otro administrador.',
        'icon'   => 'fa-circle-exclamation',
        'color'  => '#e51022',
        'bar'    => '#e51022',
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
