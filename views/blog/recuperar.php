<?php $paginaVista = 'blog-login'; ?>
<?php
/**
 * Solicitud de restablecimiento de contraseña del panel.
 *
 * Comparte `$paginaVista` con el login a propósito: es la misma superficie —mismo bosque,
 * misma tarjeta, mismo lenguaje— y duplicar `_blog-login.scss` bajo otro id garantizaría
 * que las dos pantallas se desincronicen a la primera corrección.
 *
 * ⚠️ El acuse es SIEMPRE el mismo, exista o no el correo. Un mensaje distinto convertiría
 * esta pantalla en un verificador de qué direcciones pertenecen al claustro, y eso no
 * puede ofrecerlo una pantalla sin autenticar. El texto está escrito para que sea verdad
 * en los dos casos: dice qué pasa *si* la cuenta existe.
 *
 * @var array $alertas  @var bool $enviado
 */
$hayError = !empty($alertas['error']);
?>
<div class="admin-login">
    <canvas id="forest-canvas" class="admin-login__canvas" aria-hidden="true"></canvas>
    <div class="admin-login__veil" aria-hidden="true"></div>

    <div class="admin-login__card<?= $hayError ? ' has-shake' : '' ?>">

        <img src="/build/assets/img/global/logo-bilbao-horizontal-azul.png"
             alt="Colegio Bilbao" class="admin-login__logo">

        <?php if ($enviado): ?>

            <h1 class="admin-login__heading">Solicitud recibida</h1>
            <div class="admin-login-ok" role="status">
                <i class="fa-solid fa-circle-check"></i>
                <span>
                    Si ese correo corresponde a una cuenta del panel, un administrador te hará
                    llegar una contraseña temporal. Te avisará por el medio habitual.
                </span>
            </div>
            <p class="admin-form__back">
                <i class="fa-solid fa-arrow-left"></i>
                <a href="/login">Volver a iniciar sesión</a>
            </p>

        <?php else: ?>

            <h1 class="admin-login__heading">Recuperar contraseña</h1>

            <?php if ($hayError): ?>
                <div class="admin-login-alerta" role="alert">
                    <i class="fa-solid fa-circle-xmark"></i>
                    <span><?= s($alertas['error'][0]) ?></span>
                </div>
            <?php else: ?>
                <p class="admin-login__sub">
                    Déjanos tus datos y un administrador te generará una contraseña temporal.
                </p>
            <?php endif; ?>

            <form action="/recuperar" method="POST" novalidate data-recuperar>

                <div class="admin-form__group">
                    <label class="admin-form__label" for="nombre">
                        <i class="fa-regular fa-user"></i> Nombre completo
                    </label>
                    <div class="admin-form__input-wrapper">
                        <input type="text" id="nombre" name="nombre" class="admin-form__input"
                               value="<?= s($_POST['nombre'] ?? '') ?>"
                               placeholder="Como apareces en el panel" autocomplete="name" required>
                    </div>
                </div>

                <div class="admin-form__group">
                    <label class="admin-form__label" for="email">
                        <i class="fa-regular fa-envelope"></i> Correo electrónico
                    </label>
                    <div class="admin-form__input-wrapper">
                        <input type="email" id="email" name="email" class="admin-form__input"
                               value="<?= s($_POST['email'] ?? '') ?>"
                               placeholder="tu@bilbao.edu.mx" autocomplete="email" required>
                    </div>
                </div>

                <?php /* Segundo campo de correo: es el único dato con el que el
                         administrador puede casar la solicitud con una cuenta, y aquí no
                         hay forma de detectar una errata —no se manda correo, así que un
                         rebote nunca llega. La comparación se hace también en servidor
                         (recuperarPassword()): sin JS esto no valdría nada. */ ?>
                <div class="admin-form__group">
                    <label class="admin-form__label" for="email_confirm">
                        <i class="fa-solid fa-check-double"></i> Repite el correo
                    </label>
                    <div class="admin-form__input-wrapper">
                        <input type="email" id="email_confirm" name="email_confirm" class="admin-form__input"
                               placeholder="Escríbelo otra vez" autocomplete="off" required>
                    </div>
                    <p class="admin-form__msg" data-recuperar-msg hidden></p>
                </div>

                <button type="submit" class="admin-form__submit">
                    <i class="fa-solid fa-paper-plane"></i>
                    Enviar solicitud
                </button>

            </form>

            <p class="admin-form__back">
                <i class="fa-solid fa-arrow-left"></i>
                <a href="/login">Volver a iniciar sesión</a>
            </p>

        <?php endif; ?>

    </div>
</div>
