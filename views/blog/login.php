<?php $paginaVista = 'blog-login'; ?>
<?php $hayError = !empty($alertas['error']); ?>

<div class="admin-login">

    <!-- PANEL IZQUIERDO: Branding + Alex -->
    <div class="admin-login__brand">
        <div class="admin-login__brand-content">

            <img
                src="/build/assets/img/global/logo-bilbao-horizontal-azul.png"
                alt="Colegio Bilbao"
                class="admin-login__brand-logo"
            >

            <img
                src="/build/assets/img/alex/alex-tech.png"
                alt="Alex, mascota del Colegio Bilbao"
                class="admin-login__brand-alex"
            >

            <h1 class="admin-login__brand-title">
                Panel de<br>Administración
            </h1>
            <p class="admin-login__brand-sub">
                Gestiona los contenidos del blog del Colegio Bilbao desde un solo lugar.
            </p>

        </div>
    </div>

    <!-- PANEL DERECHO: Formulario -->
    <div class="admin-login__form-side">
        <div class="admin-login__card<?php echo $hayError ? ' has-shake' : ''; ?>">

            <!-- Logo visible solo en móvil -->
            <div class="admin-login__logo-mobile">
                <img
                    src="/build/assets/img/global/logo-bilbao-horizontal-azul.png"
                    alt="Colegio Bilbao"
                >
            </div>

            <span class="admin-login__eyebrow">
                <i class="fa-solid fa-lock"></i>
                Acceso restringido
            </span>

            <h2 class="admin-login__heading">Bienvenido<br>de vuelta</h2>

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
                    Ingresa tus credenciales para acceder al panel de administración del blog.
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
                    <div class="admin-form__input-wrapper<?php echo ($hayError && in_array(($errorCampo ?? null), [null, 'password'])) ? ' is-error' : ''; ?>" style="position:relative;">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="admin-form__input"
                            placeholder="••••••••••••"
                            autocomplete="current-password"
                            style="padding-right:44px;"
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

            <div class="admin-form__divider">o</div>

            <p class="admin-form__back">
                <i class="fa-solid fa-arrow-left" style="font-size:0.75rem;"></i>
                <a href="/">Volver al sitio público</a>
            </p>

        </div>
    </div>

</div>

