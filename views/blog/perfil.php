<?php $paginaVista = 'blog-perfil'; ?>
<?php /** @var \Model\UsuarioBlog $usuario */ ?>
<div class="admin-layout">

    <?php include __DIR__ . '/_sidebar.php'; ?>

    <div class="admin-main">

        <header class="admin-topbar">
            <div class="admin-topbar__left">
                <div class="admin-breadcrumb">
                    <a href="/dashboard">Dashboard</a>
                    <i class="fa-solid fa-chevron-right"></i>
                    <span>Mi perfil</span>
                </div>
                <span class="admin-topbar__title">Mi perfil</span>
            </div>
            <div class="admin-topbar__actions">
                <button type="submit" form="form-perfil" class="admin-btn admin-btn--primary">
                    <i class="fa-solid fa-floppy-disk"></i>
                    Guardar cambios
                </button>
                <form action="/logout" method="POST" style="display:flex;align-items:center;">
                    <button type="submit" class="admin-logout-btn">
                        <i class="fa-solid fa-right-from-bracket"></i>
                        Salir
                    </button>
                </form>
            </div>
        </header>

        <main class="admin-content">

            <?php if ($guardado): ?>
            <div class="admin-alerta admin-alerta--success" style="display:flex;align-items:center;gap:10px;background:#eef8d9;border:1px solid #b6e06a;color:#3a6008;border-radius:12px;padding:14px 18px;margin-bottom:22px;">
                <i class="fa-solid fa-circle-check"></i>
                <span>Perfil actualizado correctamente.</span>
            </div>
            <?php endif; ?>

            <?php if (!empty($alertas['error'])): ?>
            <div class="admin-alerta admin-alerta--error">
                <i class="fa-solid fa-circle-exclamation"></i>
                <ul class="admin-alerta__list">
                    <?php foreach ($alertas['error'] as $e): ?>
                        <li><?= s($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <form id="form-perfil" action="/dashboard/perfil" method="POST" enctype="multipart/form-data" novalidate>

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
                                                <img src="<?= s($usuario->avatar) ?>" alt=""
                                                     style="width:100%;height:100%;object-fit:cover;"
                                                     onerror="this.parentElement.innerHTML='<?= s(strtoupper(mb_substr($usuario->nombre ?? 'U', 0, 1))) ?>'">
                                            <?php else: ?>
                                                <?= s(strtoupper(mb_substr($usuario->nombre ?? 'U', 0, 1))) ?>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <label for="avatar" class="admin-avatar-btn" style="cursor:pointer;">
                                                <i class="fa-solid fa-camera"></i>
                                                Cambiar foto
                                            </label>
                                            <input type="file" id="avatar" name="avatar"
                                                   accept="image/jpeg,image/png,image/webp"
                                                   style="display:none;">
                                            <p style="font-size:12px;color:#94A3B8;margin-top:6px;">
                                                JPG, PNG o WebP · máx. 2 MB
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="admin-form__group">
                                    <label class="admin-form__label" for="nombre">
                                        <i class="fa-regular fa-user"></i>
                                        Nombre completo
                                    </label>
                                    <input
                                        type="text"
                                        id="nombre"
                                        name="nombre"
                                        class="admin-form__input"
                                        value="<?= s($usuario->nombre ?? '') ?>"
                                        placeholder="Tu nombre completo"
                                        required
                                    >
                                </div>

                                <div class="admin-form__group">
                                    <label class="admin-form__label" for="email">
                                        <i class="fa-regular fa-envelope"></i>
                                        Correo electrónico
                                    </label>
                                    <input
                                        type="email"
                                        id="email"
                                        name="email"
                                        class="admin-form__input"
                                        value="<?= s($usuario->email ?? '') ?>"
                                        placeholder="tu@correo.com"
                                        required
                                    >
                                </div>

                            </div>
                        </div>

                        <div class="admin-panel">
                            <div class="admin-panel__header">
                                <h2 class="admin-panel__title">Cambiar contraseña</h2>
                            </div>
                            <div class="admin-form-section">

                                <p style="font-size:13px;color:#64748B;margin-bottom:16px;">
                                    Deja en blanco si no deseas cambiar tu contraseña.
                                </p>

                                <div class="admin-form__group">
                                    <label class="admin-form__label" for="password">
                                        <i class="fa-solid fa-lock"></i>
                                        Nueva contraseña
                                    </label>
                                    <div style="position:relative;">
                                        <input
                                            type="password"
                                            id="password"
                                            name="password"
                                            class="admin-form__input"
                                            placeholder="Mínimo 8 caracteres, una mayúscula y un número"
                                            autocomplete="new-password"
                                            style="padding-right:44px;"
                                        >
                                        <button type="button" class="admin-form__eye-btn" id="togglePwPerfil" aria-label="Mostrar contraseña">
                                            <i class="fa-regular fa-eye" id="eyePerfil"></i>
                                        </button>
                                    </div>
                                    <!-- Barra de fortaleza -->
                                    <div id="sp-bar" style="display:none;margin-top:8px;display:flex;gap:3px;">
                                        <span class="sp-seg" id="sp1"></span>
                                        <span class="sp-seg" id="sp2"></span>
                                        <span class="sp-seg" id="sp3"></span>
                                        <span class="sp-seg" id="sp4"></span>
                                    </div>
                                    <p id="sp-label" style="font-size:.73rem;color:#94A3B8;margin-top:4px;min-height:1em;"></p>
                                </div>

                                <!-- Confirmar contraseña -->
                                <div class="admin-form__group" id="confirm-group" style="display:none;">
                                    <label class="admin-form__label" for="password_confirm">
                                        <i class="fa-solid fa-lock-open"></i>
                                        Confirmar contraseña
                                    </label>
                                    <div style="position:relative;">
                                        <input
                                            type="password"
                                            id="password_confirm"
                                            name="password_confirm"
                                            class="admin-form__input"
                                            placeholder="Repite la contraseña"
                                            autocomplete="new-password"
                                            style="padding-right:44px;"
                                        >
                                        <button type="button" class="admin-form__eye-btn" id="togglePwConfirm" aria-label="Mostrar contraseña">
                                            <i class="fa-regular fa-eye" id="eyeConfirm"></i>
                                        </button>
                                    </div>
                                    <p id="confirm-msg" style="font-size:.75rem;margin-top:4px;min-height:1em;"></p>
                                </div>

                            </div>
                        </div>

                    </div>

                    <!-- ── COLUMNA LATERAL ── -->
                    <div>

                        <div class="admin-panel">
                            <div class="admin-panel__header">
                                <h2 class="admin-panel__title">Cuenta</h2>
                            </div>
                            <div class="admin-form-section">

                                <div style="display:flex;flex-direction:column;gap:14px;">

                                    <div style="display:flex;align-items:center;gap:12px;">
                                        <div style="width:54px;height:54px;border-radius:50%;background:linear-gradient(135deg,#4D8ABB,#4267ac);display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.4rem;font-weight:800;flex-shrink:0;overflow:hidden;" id="perfil-avatar-big">
                                            <?php if (!empty($usuario->avatar)): ?>
                                                <img src="<?= s($usuario->avatar) ?>" alt="" style="width:100%;height:100%;object-fit:cover;">
                                            <?php else: ?>
                                                <?= s(strtoupper(mb_substr($usuario->nombre ?? 'U', 0, 1))) ?>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <div style="font-weight:700;color:#0F172A;font-size:14px;"><?= s($usuario->nombre ?? '') ?></div>
                                            <div style="font-size:12px;color:#64748B;"><?= s($usuario->email ?? '') ?></div>
                                        </div>
                                    </div>

                                    <div style="border-top:1px solid #F1F5F9;padding-top:14px;">
                                        <div style="font-size:11px;color:#94A3B8;text-transform:uppercase;letter-spacing:1px;font-weight:700;margin-bottom:8px;">Rol</div>
                                        <span style="display:inline-flex;align-items:center;gap:6px;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:700;
                                            <?= ($usuario->rol === 'administrador') ? 'background:#e8eef9;color:#4267ac;' : 'background:#fff5cc;color:#7a5c00;' ?>">
                                            <i class="fa-solid <?= ($usuario->rol === 'administrador') ? 'fa-shield-halved' : 'fa-pen-nib' ?>"></i>
                                            <?= s(ucfirst($usuario->rol ?? '')) ?>
                                        </span>
                                    </div>

                                </div>

                            </div>
                        </div>

                    </div>

                </div><!-- /admin-form-grid -->

            </form>

        </main>
    </div>

</div>

