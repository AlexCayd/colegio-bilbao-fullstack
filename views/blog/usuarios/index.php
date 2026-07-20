<?php $paginaVista = 'blog-usuarios-index'; ?>
<?php
$avatarColors = ['#4D8ABB', '#374C69', '#38A169', '#E67E22', '#9B59B6', '#319795'];

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
    return match($rol) {
        'administrador' => 'admin-badge--published',
        'editor'        => 'admin-badge--scheduled',
        default         => '',
    };
}
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
                <?php if (($_SESSION['blog_usuario']['rol'] ?? '') === 'administrador'): ?>
                <a href="/dashboard/usuarios/crear" class="admin-topbar__new-btn">
                    <i class="fa-solid fa-plus"></i> Nuevo usuario
                </a>
                <?php endif; ?>
                <?php include __DIR__ . '/../_topbar-avatar.php'; ?>
                <form action="/logout" method="POST" style="display:flex;align-items:center;">
                    <button type="submit" class="admin-logout-btn">
                        <i class="fa-solid fa-right-from-bracket"></i> Salir
                    </button>
                </form>
            </div>
        </header>

        <main class="admin-content">

            <?php /* Los toasts de created / edited / deleted se renderizan al final de la página */ ?>

            <div class="admin-panel">
                <div class="admin-panel__header">
                    <h2 class="admin-panel__title">
                        Todos los usuarios
                        <span class="admin-panel__count"><?= count($usuarios ?? []) ?></span>
                    </h2>
                    <?php if (($_SESSION['blog_usuario']['rol'] ?? '') === 'administrador'): ?>
                    <a href="/dashboard/usuarios/crear" class="admin-panel__action">+ Nuevo</a>
                    <?php endif; ?>
                </div>

                <?php if (empty($usuarios)): ?>
                <div class="admin-empty-state">
                    <img src="/build/assets/img/alex/alex-volley.png" alt="Alex" class="admin-empty-state__img">
                    <p class="admin-empty-state__text">Aún no hay usuarios registrados.</p>
                    <a href="/dashboard/usuarios/crear" class="admin-btn admin-btn--primary">
                        <i class="fa-solid fa-user-plus"></i> Crear primer usuario
                    </a>
                </div>

                <?php else: ?>
                <div style="overflow-x:auto;">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Usuario</th>
                                <th>Email</th>
                                <th>Rol</th>
                                <th>Artículos</th>
                                <th>Último acceso</th>
                                <th>Registro</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($usuarios as $u): ?>
                            <?php
                                $color   = $avatarColors[$u->id % count($avatarColors)];
                                $inicial = strtoupper(mb_substr($u->nombre, 0, 1));
                            ?>
                            <tr>
                                <td>
                                    <div style="display:flex;align-items:center;gap:.75rem;">
                                        <div class="admin-topbar__avatar" style="width:38px;height:38px;font-size:.875rem;flex-shrink:0;background:<?= s($color) ?>;">
                                            <?php if ($u->avatar): ?>
                                                <img src="<?= s($u->avatar) ?>" alt="" style="width:100%;height:100%;object-fit:cover;" onerror="this.parentElement.textContent='<?= s($inicial) ?>'">
                                            <?php else: ?>
                                                <?= s($inicial) ?>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <div class="admin-table__title"><?= s($u->nombre) ?></div>
                                            <div class="admin-table__meta"><?= s(ucfirst($u->rol)) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td style="color:var(--text-gray);font-size:.875rem;"><?= s($u->email) ?></td>
                                <td>
                                    <span class="admin-badge <?= rolBadgeClass($u->rol) ?>">
                                        <?= s(ucfirst($u->rol)) ?>
                                    </span>
                                </td>
                                <td style="font-weight:700;color:var(--col-herencia);"><?= (int)$u->total_articulos ?></td>
                                <td style="font-size:.85rem;color:var(--text-gray);"><?= s(formatAcceso($u->ultimo_acceso)) ?></td>
                                <td style="font-size:.82rem;color:var(--text-gray);">
                                    <?= $u->creado_en ? s(date('d M Y', strtotime($u->creado_en))) : '—' ?>
                                </td>
                                <td>
                                    <?php if (($_SESSION['blog_usuario']['rol'] ?? '') === 'administrador'): ?>
                                    <div class="admin-table__actions">
                                        <a href="/dashboard/usuarios/editar?id=<?= (int)$u->id ?>" class="admin-table__btn">
                                            <i class="fa-regular fa-pen-to-square"></i> Editar
                                        </a>
                                        <button
                                            type="button"
                                            class="admin-table__btn admin-table__btn--danger"
                                            onclick="confirmarEliminar(<?= (int)$u->id ?>, '<?= s(addslashes($u->nombre)) ?>')"
                                            title="Eliminar usuario"
                                        >
                                            <i class="fa-regular fa-trash-can"></i>
                                        </button>
                                    </div>
                                    <?php else: ?>
                                    <span style="font-size:.8rem;color:#94A3B8;">Solo lectura</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>

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
