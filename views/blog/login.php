<?php $paginaVista = 'blog-login'; ?>
<?php $hayError = !empty($alertas['error']); ?>

<?php /* Mismo bosque Three.js de la landing (src/js/public/forest.js, en ambos
         bundles). El velo garantiza el contraste del texto y hace de fondo
         cuando no hay WebGL o el usuario pidió menos movimiento: el canvas
         simplemente se queda transparente. */ ?>
<div class="admin-login">
    <canvas id="forest-canvas" class="admin-login__canvas" aria-hidden="true"></canvas>
    <div class="admin-login__veil" aria-hidden="true"></div>

    <div class="admin-login__card<?php echo $hayError ? ' has-shake' : ''; ?>">

        <?php /* No hay variante blanca del logo: se blanquea por CSS
                 (`brightness(0) invert(1)`), que con una marca de un solo color
                 da exactamente el mismo resultado que tener el archivo. */ ?>
        <img
            src="/build/assets/img/global/logo-bilbao-horizontal-azul.png"
            alt="Colegio Bilbao"
            class="admin-login__logo"
        >

        <h1 class="admin-login__heading">Bienvenido de vuelta</h1>

        <?php if ($hayError): ?>
            <div class="admin-login-alerta" role="alert">
                <i class="fa-solid fa-circle-xmark"></i>
                <span><?php echo htmlspecialchars($alertas['error'][0]); ?></span>
                <button type="button" aria-label="Cerrar" onclick="this.closest('.admin-login-alerta').remove()">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        <?php else: ?>
            <p class="admin-login__sub">
                Ingresa tus credenciales para entrar al panel del Colegio Bilbao.
            </p>
        <?php endif; ?>

        <form action="/login" method="POST" novalidate>

            <div class="admin-form__group">
                <label class="admin-form__label" for="email">
                    <i class="fa-regular fa-envelope"></i>
                    Correo electrónico
                </label>
                <div class="admin-form__input-wrapper<?php echo ($hayError && in_array(($errorCampo ?? null), [null, 'email'])) ? ' is-error' : ''; ?>">
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="admin-form__input"
                        value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                        placeholder="admin@bilbao.edu.mx"
                        autocomplete="email"
                        required
                    >
                </div>
            </div>

            <div class="admin-form__group">
                <label class="admin-form__label" for="password">
                    <i class="fa-solid fa-key"></i>
                    Contraseña
                </label>
                <div class="admin-form__input-wrapper has-eye<?php echo ($hayError && in_array(($errorCampo ?? null), [null, 'password'])) ? ' is-error' : ''; ?>">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="admin-form__input"
                        placeholder="••••••••••••"
                        autocomplete="current-password"
                        required
                    >
                    <button type="button" class="admin-form__eye-btn" id="togglePassword" aria-label="Mostrar contraseña">
                        <i class="fa-regular fa-eye" id="eyeIcon"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="admin-form__submit">
                <i class="fa-solid fa-arrow-right-to-bracket"></i>
                Iniciar sesión
            </button>

        </form>

        <p class="admin-form__back">
            <i class="fa-solid fa-arrow-left"></i>
            <a href="/">Volver al sitio público</a>
        </p>

    </div>
</div>
