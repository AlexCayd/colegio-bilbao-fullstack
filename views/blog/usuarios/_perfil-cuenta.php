<?php
/**
 * Sección editable de «Mi perfil», dentro de la ficha.
 *
 * Solo se incluye cuando `$esPropio` es true. Espera definidas: `$u` (\Model\UsuarioBlog),
 * `$alertas` y `$guardado`.
 *
 * ⚠️ El `<form>` envuelve SOLO esta sección, no la página. Debajo van el horario, las
 * suplencias y los intercambios, que son tablas de lectura: meterlas dentro del
 * formulario no rompería nada hoy, pero convierte cualquier `<button>` futuro de esas
 * tablas en un submit de este formulario.
 *
 * Por lo mismo el pie NO es `--sticky`: era correcto cuando el perfil era una página de
 * un solo formulario; ahora es una sección de cinco, y una barra fija flotando sobre la
 * rejilla de horario diría que guarda algo que no guarda.
 */
?>
<section class="admin-panel" id="ufi-cuenta">
    <div class="admin-panel__header">
        <h2 class="admin-panel__title"><i class="fa-solid fa-user-pen"></i> Mi cuenta</h2>
    </div>

    <form class="ufi-cuenta" id="form-perfil" action="/dashboard/perfil" method="POST"
          enctype="multipart/form-data" novalidate>

        <?php if ($guardado): ?>
        <p class="ufi-aviso ufi-aviso--ok">
            <i class="fa-solid fa-circle-check"></i> Perfil actualizado correctamente.
        </p>
        <?php endif; ?>

        <?php if (!empty($alertas['error'])): ?>
        <div class="ufi-aviso ufi-aviso--error">
            <i class="fa-solid fa-circle-exclamation"></i>
            <ul>
                <?php foreach ($alertas['error'] as $e): ?>
                <li><?= s($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <div class="ufi-cuenta__grid">

            <!-- Foto -->
            <div class="ufi-campo ufi-campo--wide">
                <label class="admin-form__label"><i class="fa-regular fa-image"></i> Foto de perfil</label>
                <div class="admin-avatar-upload">
                    <div class="admin-avatar-preview" id="avatar-preview">
                        <?php if (!empty($u->avatar)): ?>
                            <img src="<?= s($u->avatar) ?>" alt=""
                                 onerror="this.parentElement.textContent='<?= s(strtoupper(mb_substr($u->nombre ?? 'U', 0, 1))) ?>'">
                        <?php else: ?><?= s(strtoupper(mb_substr($u->nombre ?? 'U', 0, 1))) ?><?php endif; ?>
                    </div>
                    <div>
                        <label for="avatar" class="admin-avatar-btn">
                            <i class="fa-solid fa-camera"></i> Cambiar foto
                        </label>
                        <input type="file" id="avatar" name="avatar" accept="image/jpeg,image/png,image/webp" hidden>
                        <p class="ufi-campo__hint">JPG, PNG o WebP · máx. 2 MB</p>
                    </div>
                </div>
            </div>

            <div class="ufi-campo">
                <label class="admin-form__label" for="nombre"><i class="fa-regular fa-user"></i> Nombre completo</label>
                <input type="text" id="nombre" name="nombre" class="admin-form__input"
                       value="<?= s($u->nombre ?? '') ?>" placeholder="Tu nombre completo" required>
            </div>

            <div class="ufi-campo">
                <label class="admin-form__label" for="email"><i class="fa-regular fa-envelope"></i> Correo electrónico</label>
                <input type="email" id="email" name="email" class="admin-form__input"
                       value="<?= s($u->email ?? '') ?>" placeholder="tu@correo.com" required>
            </div>

            <!-- Contraseña -->
            <div class="ufi-campo ufi-campo--wide">
                <h3 class="ufi-sub ufi-sub--enform">Cambiar contraseña</h3>
                <p class="ufi-campo__hint ufi-campo__hint--pre">Déjalo en blanco si no quieres cambiarla.</p>
            </div>

            <div class="ufi-campo">
                <label class="admin-form__label" for="password"><i class="fa-solid fa-lock"></i> Nueva contraseña</label>
                <div class="ufi-campo__pw">
                    <input type="password" id="password" name="password" class="admin-form__input"
                           placeholder="Mínimo 8 caracteres, una mayúscula y un número"
                           autocomplete="new-password">
                    <button type="button" class="admin-form__eye-btn" id="togglePwPerfil" aria-label="Mostrar contraseña">
                        <i class="fa-regular fa-eye" id="eyePerfil"></i>
                    </button>
                </div>
                <?php /* La barra la revela blog-perfil.js al teclear. */ ?>
                <div id="sp-bar" class="ufi-fuerza" hidden>
                    <span class="sp-seg" id="sp1"></span>
                    <span class="sp-seg" id="sp2"></span>
                    <span class="sp-seg" id="sp3"></span>
                    <span class="sp-seg" id="sp4"></span>
                </div>
                <p id="sp-label" class="ufi-campo__hint" hidden></p>
            </div>

            <div class="ufi-campo" id="confirm-group" hidden>
                <label class="admin-form__label" for="password_confirm"><i class="fa-solid fa-lock-open"></i> Confirmar contraseña</label>
                <div class="ufi-campo__pw">
                    <input type="password" id="password_confirm" name="password_confirm" class="admin-form__input"
                           placeholder="Repite la contraseña" autocomplete="new-password">
                    <button type="button" class="admin-form__eye-btn" id="togglePwConfirm" aria-label="Mostrar contraseña">
                        <i class="fa-regular fa-eye" id="eyeConfirm"></i>
                    </button>
                </div>
                <p id="confirm-msg" class="ufi-campo__hint"></p>
            </div>

        </div>

        <div class="ufi-cuenta__pie">
            <button type="submit" class="admin-btn admin-btn--primary">
                <i class="fa-solid fa-floppy-disk"></i> Guardar cambios
            </button>
        </div>

    </form>
</section>
