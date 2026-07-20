<?php $paginaVista = 'blog-usuarios-crear'; ?>
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
                    <span>Nuevo usuario</span>
                </div>
                <span class="admin-topbar__title">Nuevo usuario</span>
            </div>
            <div class="admin-topbar__actions">
                <a href="/dashboard/usuarios" class="admin-btn admin-btn--ghost">
                    <i class="fa-solid fa-xmark"></i> Cancelar
                </a>
                <button type="submit" form="form-usuario" class="admin-btn admin-btn--primary">
                    <i class="fa-solid fa-floppy-disk"></i> Guardar usuario
                </button>
                <?php include __DIR__ . '/../_topbar-avatar.php'; ?>
                <form action="/logout" method="POST" style="display:flex;align-items:center;">
                    <button type="submit" class="admin-logout-btn">
                        <i class="fa-solid fa-right-from-bracket"></i> Salir
                    </button>
                </form>
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

            <form
                id="form-usuario"
                action="/dashboard/usuarios/crear"
                method="POST"
                enctype="multipart/form-data"
                novalidate
            >
                <div class="admin-form-grid">

                    <!-- ── COLUMNA PRINCIPAL ── -->
                    <div>

                        <!-- DATOS PERSONALES -->
                        <div class="admin-panel" style="margin-bottom:20px;">
                            <div class="admin-panel__header">
                                <h2 class="admin-panel__title">Información personal</h2>
                            </div>
                            <div class="admin-form-section">

                                <!-- AVATAR -->
                                <div class="admin-form__group">
                                    <label class="admin-form__label">
                                        <i class="fa-regular fa-image"></i> Foto de perfil
                                    </label>
                                    <div class="admin-avatar-upload">
                                        <div class="admin-avatar-preview" id="avatar-preview">
                                            <?= s(strtoupper(substr($usuario->nombre ?? 'U', 0, 1))) ?>
                                        </div>
                                        <div>
                                            <label for="avatar" class="admin-avatar-btn" style="cursor:pointer;">
                                                <i class="fa-solid fa-arrow-up-from-bracket"></i> Subir imagen
                                            </label>
                                            <input type="file" id="avatar" name="avatar" accept="image/jpeg,image/png,image/webp" style="display:none;">
                                            <span class="admin-form__hint" style="display:block;margin-top:6px;">PNG, JPG o WebP · Máx. 2 MB</span>
                                            <span class="admin-form__hint" id="avatar-filename" style="display:block;margin-top:3px;color:var(--col-bilbao);"></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="admin-form-row">

                                    <!-- NOMBRE -->
                                    <div class="admin-form__group">
                                        <label class="admin-form__label" for="nombre">
                                            <i class="fa-regular fa-user"></i> Nombre completo
                                        </label>
                                        <div class="admin-form__input-wrapper">
                                            <input
                                                type="text"
                                                id="nombre"
                                                name="nombre"
                                                class="admin-form__input"
                                                placeholder="Ej. María González"
                                                value="<?= s($usuario->nombre ?? '') ?>"
                                                autocomplete="name"
                                                required
                                            >
                                            <span class="field-icon field-icon--valid"><i class="fa-solid fa-check"></i></span>
                                            <span class="field-icon field-icon--invalid"><i class="fa-solid fa-xmark"></i></span>
                                        </div>
                                        <span class="field-msg" id="msg-nombre"></span>
                                    </div>

                                    <!-- EMAIL -->
                                    <div class="admin-form__group">
                                        <label class="admin-form__label" for="email">
                                            <i class="fa-regular fa-envelope"></i> Correo electrónico
                                        </label>
                                        <div class="admin-form__input-wrapper">
                                            <input
                                                type="email"
                                                id="email"
                                                name="email"
                                                class="admin-form__input"
                                                placeholder="usuario@colegiobilbao.edu.mx"
                                                value="<?= s($usuario->email ?? '') ?>"
                                                autocomplete="email"
                                                required
                                            >
                                            <span class="field-icon field-icon--valid"><i class="fa-solid fa-check"></i></span>
                                            <span class="field-icon field-icon--invalid"><i class="fa-solid fa-xmark"></i></span>
                                        </div>
                                        <span class="field-msg" id="msg-email"></span>
                                    </div>

                                </div>

                                <div class="admin-form-row">
                                    <!-- FECHA DE NACIMIENTO -->
                                    <div class="admin-form__group">
                                        <label class="admin-form__label" for="fecha_nacimiento">
                                            <i class="fa-regular fa-calendar"></i> Fecha de nacimiento
                                        </label>
                                        <div class="admin-form__input-wrapper">
                                            <input
                                                type="date"
                                                id="fecha_nacimiento"
                                                name="fecha_nacimiento"
                                                class="admin-form__input"
                                                value="<?= s($usuario->fecha_nacimiento ?? '') ?>"
                                                max="<?= date('Y-m-d') ?>"
                                            >
                                        </div>
                                        <span class="admin-form__hint">Se usa para el calendario de cumpleaños del equipo.</span>
                                    </div>
                                    <div class="admin-form__group"><!-- espaciador --></div>
                                </div>
                            </div>
                        </div>

                        <!-- ACCESO Y SEGURIDAD -->
                        <div class="admin-panel" style="margin-bottom:20px;">
                            <div class="admin-panel__header">
                                <h2 class="admin-panel__title">Acceso y seguridad</h2>
                            </div>
                            <div class="admin-form-section">

                                <div class="admin-form-row">

                                    <!-- CONTRASEÑA -->
                                    <div class="admin-form__group">
                                        <label class="admin-form__label" for="password">
                                            <i class="fa-solid fa-key"></i> Contraseña
                                        </label>
                                        <div class="admin-form__input-wrapper">
                                            <input
                                                type="password"
                                                id="password"
                                                name="password"
                                                class="admin-form__input"
                                                placeholder="Mínimo 8 caracteres"
                                                autocomplete="new-password"
                                                required
                                                style="padding-right:48px;"
                                            >
                                            <button type="button" class="admin-form__password-toggle" onclick="togglePassword('password','icon-pwd')">
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

                                    <!-- CONFIRMAR -->
                                    <div class="admin-form__group">
                                        <label class="admin-form__label" for="password2">
                                            <i class="fa-solid fa-lock"></i> Confirmar contraseña
                                        </label>
                                        <div class="admin-form__input-wrapper">
                                            <input
                                                type="password"
                                                id="password2"
                                                name="password2"
                                                class="admin-form__input"
                                                placeholder="Repite la contraseña"
                                                autocomplete="new-password"
                                                required
                                                style="padding-right:48px;"
                                            >
                                            <button type="button" class="admin-form__password-toggle" onclick="togglePassword('password2','icon-pwd2')">
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

                        <!-- ROL -->
                        <?php
                        $modsSel = array_filter(array_map('trim', explode(',', (string)($usuario->modulos ?? ''))));
                        $rolActual = $usuario->rol ?? 'usuario';
                        $MODS = [
                            'redaccion'  => ['nombre' => 'Redacción',  'desc' => 'Blog, noticias y contenido.',        'icon' => 'fa-pen-nib'],
                            'suplencias' => ['nombre' => 'Suplencias', 'desc' => 'Gestión de suplencias docentes.',      'icon' => 'fa-user-clock'],
                            'usuarios'   => ['nombre' => 'Usuarios',   'desc' => 'Colaboradores y cumpleaños.',          'icon' => 'fa-users-gear'],
                        ];
                        ?>
                        <div class="admin-panel">
                            <div class="admin-panel__header">
                                <h2 class="admin-panel__title">Rol y permisos</h2>
                            </div>
                            <div class="admin-form-section">
                                <p style="font-size:.88rem;color:var(--text-gray);line-height:1.65;margin-bottom:20px;">
                                    El <strong>Administrador</strong> accede a todos los módulos. El <strong>Usuario</strong> solo a los módulos que marques.
                                </p>
                                <div class="admin-role-cards" style="grid-template-columns:repeat(2,1fr);">

                                    <div class="admin-role-card">
                                        <input type="radio" id="rol-admin" name="rol" value="administrador"
                                            <?= $rolActual === 'administrador' ? 'checked' : '' ?>>
                                        <label for="rol-admin">
                                            <div class="admin-role-card__icon"><i class="fa-solid fa-crown"></i></div>
                                            <div class="admin-role-card__name">Administrador</div>
                                            <div class="admin-role-card__desc">Acceso total a todos los módulos del panel.</div>
                                        </label>
                                    </div>

                                    <div class="admin-role-card">
                                        <input type="radio" id="rol-usuario" name="rol" value="usuario"
                                            <?= $rolActual !== 'administrador' ? 'checked' : '' ?>>
                                        <label for="rol-usuario">
                                            <div class="admin-role-card__icon"><i class="fa-solid fa-user-gear"></i></div>
                                            <div class="admin-role-card__name">Usuario</div>
                                            <div class="admin-role-card__desc">Acceso solo a los módulos seleccionados.</div>
                                        </label>
                                    </div>

                                </div>

                                <!-- MÓDULOS (solo para rol Usuario) -->
                                <div class="admin-modulos" id="modulos-group" style="margin-top:22px;">
                                    <label class="admin-form__label" style="margin-bottom:12px;">
                                        <i class="fa-solid fa-grip"></i> Módulos con acceso
                                    </label>
                                    <div class="admin-modulos__grid">
                                        <?php foreach ($MODS as $key => $m): ?>
                                        <label class="admin-modulo-check">
                                            <input type="checkbox" name="modulos[]" value="<?= $key ?>"
                                                <?= in_array($key, $modsSel, true) ? 'checked' : '' ?>>
                                            <span class="admin-modulo-check__box">
                                                <span class="admin-modulo-check__icon"><i class="fa-solid <?= $m['icon'] ?>"></i></span>
                                                <span class="admin-modulo-check__text">
                                                    <span class="admin-modulo-check__name"><?= $m['nombre'] ?></span>
                                                    <span class="admin-modulo-check__desc"><?= $m['desc'] ?></span>
                                                </span>
                                                <span class="admin-modulo-check__tick"><i class="fa-solid fa-check"></i></span>
                                            </span>
                                        </label>
                                        <?php endforeach; ?>
                                    </div>
                                    <span class="admin-form__hint" style="margin-top:10px;display:block;">Selecciona al menos un módulo para el rol Usuario.</span>
                                </div>
                            </div>
                            <div class="admin-form-footer">
                                <button type="submit" class="admin-btn admin-btn--primary">
                                    <i class="fa-solid fa-floppy-disk"></i> Guardar usuario
                                </button>
                                <a href="/dashboard/usuarios" class="admin-btn admin-btn--ghost">Cancelar</a>
                            </div>
                        </div>

                    </div>
                    <!-- /columna principal -->

                    <!-- ── COLUMNA LATERAL ── -->
                    <div>

                        <div class="admin-helper-card">
                            <img src="/build/assets/img/alex/alex-mano.png" alt="Alex" class="admin-helper-card__alex">
                            <h3 class="admin-helper-card__title">¡Hola! Soy Alex</h3>
                            <p class="admin-helper-card__text">
                                Te ayudo a crear un nuevo acceso al panel del blog del Colegio Bilbao.
                            </p>
                            <div class="admin-tips">
                                <div class="admin-tip">
                                    <i class="fa-solid fa-crown"></i>
                                    <span><strong>Administrador</strong> tiene acceso a todos los módulos del panel.</span>
                                </div>
                                <div class="admin-tip">
                                    <i class="fa-solid fa-user-gear"></i>
                                    <span><strong>Usuario</strong> accede solo a los módulos que le asignes.</span>
                                </div>
                            </div>
                        </div>

                        <div class="admin-panel">
                            <div class="admin-panel__header">
                                <h2 class="admin-panel__title">
                                    <i class="fa-solid fa-circle-info" style="color:var(--col-bilbao);font-size:.85rem;margin-right:4px;"></i>
                                    Aviso importante
                                </h2>
                            </div>
                            <div style="padding:20px 24px;">
                                <p style="font-size:.85rem;color:var(--text-gray);line-height:1.7;margin-bottom:12px;">
                                    Comparte la contraseña de forma segura. Se recomienda que el usuario la cambie en su primer acceso.
                                </p>
                                <p style="font-size:.85rem;color:var(--text-gray);line-height:1.7;">
                                    El correo es el identificador único de inicio de sesión y no puede repetirse.
                                </p>
                            </div>
                        </div>

                    </div>

                </div>
            </form>

        </main>
    </div>

</div>

