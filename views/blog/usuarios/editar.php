<?php $paginaVista = 'blog-usuarios-editar'; ?>
<?php /** @var \Model\UsuarioBlog $usuario */ ?>
<div class="admin-layout">

    <!-- ===================== SIDEBAR ===================== -->
    <?php include __DIR__ . '/../_sidebar.php'; ?>

    <!-- ===================== MAIN ===================== -->
    <div class="admin-main">

        <header class="admin-topbar">
            <div class="admin-topbar__left">
                <div class="admin-breadcrumb">
                    <a href="/dashboard">Dashboard</a>
                    <i class="fa-solid fa-chevron-right"></i>
                    <a href="/dashboard/usuarios">Usuarios</a>
                    <i class="fa-solid fa-chevron-right"></i>
                    <span>Editar usuario</span>
                </div>
                <span class="admin-topbar__title">Editar usuario</span>
            </div>
            <div class="admin-topbar__actions">
                <a href="/dashboard/usuarios" class="admin-btn admin-btn--ghost">
                    <i class="fa-solid fa-xmark"></i>
                    Cancelar
                </a>
                <button type="submit" form="form-editar-usuario" class="admin-btn admin-btn--primary">
                    <i class="fa-solid fa-floppy-disk"></i>
                    Guardar cambios
                </button>
                <?php include __DIR__ . '/../_topbar-avatar.php'; ?>
            </div>
        </header>

        <main class="admin-content">

            <?php if (!empty($alertas['error'])): ?>
            <div class="admin-alerta admin-alerta--error">
                <i class="fa-solid fa-circle-exclamation"></i>
                <ul class="admin-alerta__list">
                    <?php foreach ($alertas['error'] as $error): ?>
                        <li><?= s($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <form id="form-editar-usuario" action="/dashboard/usuarios/editar" method="POST" enctype="multipart/form-data" novalidate>
                <input type="hidden" name="id" value="<?= (int)$usuario->id ?>">

                <div class="admin-form-grid">

                    <!-- ── COLUMNA PRINCIPAL ── -->
                    <div>

                        <div class="admin-panel" style="margin-bottom:20px;">
                            <div class="admin-panel__header">
                                <h2 class="admin-panel__title">Información personal</h2>
                            </div>
                            <div class="admin-form-section">

                                <div class="admin-form__group">
                                    <label class="admin-form__label">
                                        <i class="fa-regular fa-image"></i>
                                        Foto de perfil
                                    </label>
                                    <div class="admin-avatar-upload">
                                        <div class="admin-avatar-preview" id="avatar-preview">
                                        <?php if (!empty($usuario->avatar)): ?>
                                            <img src="<?= s($usuario->avatar) ?>" alt="" style="width:100%;height:100%;object-fit:cover;" onerror="this.parentElement.innerHTML='<?= s(strtoupper(mb_substr($usuario->nombre ?? 'U', 0, 1))) ?>'">
                                        <?php else: ?>
                                            <?= s(strtoupper(mb_substr($usuario->nombre ?? 'U', 0, 1))) ?>
                                        <?php endif; ?>
                                    </div>
                                        <div>
                                            <label for="avatar" class="admin-avatar-btn" style="cursor:pointer;">
                                                <i class="fa-solid fa-arrow-up-from-bracket"></i>
                                                Cambiar imagen
                                            </label>
                                            <input type="file" id="avatar" name="avatar" accept="image/jpeg,image/png,image/webp" style="display:none;">
                                            <span class="admin-form__hint" style="display:block;margin-top:6px;">PNG o JPG. Máximo 2 MB.</span>
                                            <span class="admin-form__hint" id="avatar-filename" style="display:block;margin-top:3px;color:var(--col-bilbao);"></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="admin-form-row">
                                    <div class="admin-form__group">
                                        <label class="admin-form__label" for="nombre">
                                            <i class="fa-regular fa-user"></i>
                                            Nombre completo
                                        </label>
                                        <div class="admin-form__input-wrapper">
                                            <input type="text" id="nombre" name="nombre" class="admin-form__input"
                                                placeholder="Ej. María González" value="<?= s($usuario->nombre ?? '') ?>"
                                                autocomplete="name" required>
                                        </div>
                                    </div>
                                    <div class="admin-form__group">
                                        <label class="admin-form__label" for="email">
                                            <i class="fa-regular fa-envelope"></i>
                                            Correo electrónico
                                        </label>
                                        <div class="admin-form__input-wrapper">
                                            <input type="email" id="email" name="email" class="admin-form__input"
                                                placeholder="usuario@colegiobilbao.edu.mx"
                                                value="<?= s($usuario->email ?? '') ?>"
                                                autocomplete="email" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="admin-form-row">
                                    <?php
                                    // Datepicker propio: nacer en fin de semana es lo normal, así
                                    // que sin `habiles`; y puede quedarse vacío, de ahí "Limpiar".
                                    $fechaName  = 'fecha_nacimiento';
                                    $fechaLabel = 'Fecha de nacimiento';
                                    $fechaValor = $usuario->fecha_nacimiento ?? '';
                                    $fechaMax   = date('Y-m-d');
                                    $fechaHabiles = false;
                                    $fechaLimpiar = true;
                                    $fechaPlaceholder = 'Sin fecha';
                                    include __DIR__ . '/../_campo-fecha.php';
                                    ?>
                                    <div class="admin-form__group"><!-- espaciador --></div>
                                </div>

                            </div>
                        </div>

                        <!-- CONTRASEÑA (colapsable) -->
                        <div class="admin-panel" style="margin-bottom:20px;">
                            <div class="admin-panel__header">
                                <h2 class="admin-panel__title">Contraseña</h2>
                                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:0.84rem;font-weight:600;color:var(--text-gray);">
                                    <input type="checkbox" id="toggle-password" onchange="togglePasswordSection()" style="accent-color:var(--col-bilbao);">
                                    Cambiar contraseña
                                </label>
                            </div>

                            <div id="password-section" style="display:none;">
                                <div class="admin-form-section">
                                    <div class="admin-form-row">
                                        <div class="admin-form__group">
                                            <label class="admin-form__label" for="password">
                                                <i class="fa-solid fa-key"></i>
                                                Nueva contraseña
                                            </label>
                                            <div class="admin-form__input-wrapper">
                                                <input type="password" id="password" name="password"
                                                    class="admin-form__input" placeholder="Mínimo 8 caracteres"
                                                    style="padding-right:48px;">
                                                <button type="button" class="admin-form__password-toggle"
                                                    onclick="togglePassword('password','icon-pwd')">
                                                    <i class="fa-regular fa-eye" id="icon-pwd"></i>
                                                </button>
                                            </div>
                                            <!-- Medidor de fortaleza -->
                                            <div class="password-strength" id="pwd-strength">
                                                <div class="password-strength__bar">
                                                    <span class="password-strength__fill" id="pwd-fill"></span>
                                                </div>
                                                <span class="password-strength__label" id="pwd-label"></span>
                                            </div>
                                            <span class="field-msg" id="msg-password"></span>
                                        </div>
                                        <div class="admin-form__group">
                                            <label class="admin-form__label" for="password_confirm">
                                                <i class="fa-solid fa-lock"></i>
                                                Confirmar nueva contraseña
                                            </label>
                                            <div class="admin-form__input-wrapper">
                                                <input type="password" id="password_confirm" name="password_confirm"
                                                    class="admin-form__input" placeholder="Repite la contraseña"
                                                    style="padding-right:48px;">
                                                <button type="button" class="admin-form__password-toggle"
                                                    onclick="togglePassword('password_confirm','icon-pwd2')">
                                                    <i class="fa-regular fa-eye" id="icon-pwd2"></i>
                                                </button>
                                            </div>
                                            <span class="field-msg" id="msg-password2"></span>
                                        </div>
                                    </div>
                                    <span class="admin-form__hint">
                                        <i class="fa-solid fa-shield-halved" style="color:var(--col-bilbao);margin-right:5px;"></i>
                                        Mínimo 8 caracteres, una mayúscula y un número.
                                    </span>
                                </div>
                            </div>

                            <div id="password-placeholder" style="padding:18px 24px;">
                                <p style="font-size:0.88rem;color:var(--text-gray);display:flex;align-items:center;gap:8px;">
                                    <i class="fa-solid fa-lock" style="color:#CBD5E0;"></i>
                                    La contraseña actual se mantiene. Activa la casilla para modificarla.
                                </p>
                            </div>
                        </div>

                        <!-- ROL (solo visible para administradores) -->
                        <?php if (($_SESSION['blog_usuario']['rol'] ?? '') === 'administrador'):
                            $modsSel   = array_filter(array_map('trim', explode(',', (string)($usuario->modulos ?? ''))));
                            $rolActual = $usuario->rol ?? 'usuario';
                            $rolRed    = $usuario->rol_redaccion ?? '';
                            $tiposSel  = array_filter(array_map('trim', explode(',', (string)($usuario->tipo_personal ?? ''))));
                            $puedeSupl = (int)($usuario->puede_suplir ?? 1) === 1;
                            include __DIR__ . '/_permisos-fields.php';
                        ?>
                            <div class="admin-form-footer">
                                <button type="submit" class="admin-btn admin-btn--primary">
                                    <i class="fa-solid fa-floppy-disk"></i>
                                    Guardar cambios
                                </button>
                                <a href="/dashboard/usuarios" class="admin-btn admin-btn--ghost">Cancelar</a>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php /* ZONA DE PELIGRO
                                 data-danger lo engancha src/js/admin/admin-danger.js, que añade la
                                 animación con GSAP. Todo el estado visual (rojo, sombra, hover) vive
                                 en CSS, así que sin GSAP o con prefers-reduced-motion sigue completa. */ ?>
                        <div class="admin-danger-zone" data-danger>
                            <div class="admin-danger-zone__header">
                                <i class="fa-solid fa-triangle-exclamation"></i>
                                <span class="admin-danger-zone__title">Zona de peligro</span>
                            </div>
                            <div class="admin-danger-zone__body">
                                <div>
                                    <p class="admin-danger-zone__label">Eliminar este usuario</p>
                                    <p class="admin-danger-zone__desc">
                                        Acción permanente. El usuario perderá acceso al panel y sus datos no se podrán recuperar.
                                    </p>
                                </div>
                                <button type="button" class="admin-btn admin-btn--danger admin-danger-zone__btn" data-danger-btn
                                    onclick="abrirModalEliminar(<?= (int)$usuario->id ?>, '<?= s(addslashes($usuario->nombre)) ?>')">
                                    <i class="fa-regular fa-trash-can" data-danger-ico></i>
                                    <span>Eliminar usuario</span>
                                </button>
                            </div>
                        </div>

                    </div>

                    <!-- ── COLUMNA LATERAL ── -->
                    <div>

                        <div class="admin-helper-card">
                            <img src="/build/assets/img/alex/alex-dice.png"
                                alt="Alex" class="admin-helper-card__alex">
                            <h3 class="admin-helper-card__title">Editar usuario</h3>
                            <p class="admin-helper-card__text">
                                Actualiza los datos con cuidado. Un correo incorrecto impedirá que pueda iniciar sesión.
                            </p>
                            <div class="admin-tips">
                                <div class="admin-tip">
                                    <i class="fa-solid fa-envelope"></i>
                                    <span>El correo es el identificador único para acceder al panel.</span>
                                </div>
                                <div class="admin-tip">
                                    <i class="fa-solid fa-key"></i>
                                    <span>Si no necesitas cambiar la contraseña, déjala sin activar.</span>
                                </div>
                                <div class="admin-tip">
                                    <i class="fa-solid fa-shield-halved"></i>
                                    <span>Puedes cambiar el rol en cualquier momento.</span>
                                </div>
                            </div>
                        </div>

                        <div class="admin-panel">
                            <div class="admin-panel__header">
                                <h2 class="admin-panel__title">Información de cuenta</h2>
                            </div>
                            <div style="padding:20px 24px;display:flex;flex-direction:column;gap:14px;">
                                <div>
                                    <span style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:var(--text-gray);">Fecha de registro</span>
                                    <p style="font-size:0.9rem;color:var(--col-herencia);font-weight:600;margin-top:3px;">
                                        <?= $usuario->creado_en ? s(date('d M Y', strtotime($usuario->creado_en))) : '—' ?>
                                    </p>
                                </div>
                                <div>
                                    <span style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:var(--text-gray);">Último acceso</span>
                                    <p style="font-size:0.9rem;color:var(--col-herencia);font-weight:600;margin-top:3px;">
                                        <?= $usuario->ultimo_acceso ? s(date('d M Y H:i', strtotime($usuario->ultimo_acceso))) : 'Nunca' ?>
                                    </p>
                                </div>
                                <div>
                                    <span style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:var(--text-gray);">Artículos</span>
                                    <p style="font-size:0.9rem;color:var(--col-herencia);font-weight:600;margin-top:3px;">
                                        <?= (int)$usuario->total_articulos ?> artículo<?= $usuario->total_articulos != 1 ? 's' : '' ?>
                                    </p>
                                </div>
                            </div>
                        </div>

                    </div>

                </div>

            </form>

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
        <form method="POST" action="/dashboard/usuarios/eliminar">
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

