<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - <?php echo htmlspecialchars($titulo); ?> | Colegio Bilbao</title>
    <link rel="shortcut icon" href="/build/assets/img/global/favicon.png" type="image/png">
    <link rel="icon" type="image/png" sizes="32x32" href="/build/assets/img/global/favicon.png">
    <link rel="apple-touch-icon" href="/build/assets/img/global/favicon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.2/css/all.min.css" integrity="sha512-1sCRPdkRXhBV2PBLUdRb4tMg1w2YPf37qatUFeS7zlBy7jJI8Lf4VHwWfZZfpXtYSLy85pkm9GaYVYMfw5BC1A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="/build/css/app.css">
    <?php /* Guard anti-salto del sidebar.
             El bundle va con `defer` al final del <body>, así que el estado plegado
             (localStorage) se leía DESPUÉS del primer pintado: el navegador dibujaba
             el contenido con el margen de 260px y luego JS lo corregía a 72px, con
             los 0.28s de transition a la vista en cada carga de página.
             Este script es síncrono y va antes del primer pintado, así que decide el
             estado inicial; blog-_sidebar.js sigue mandando al alternar.
             Es la misma excepción justificada que el guard de i18n en views/layout.php:
             cualquier cosa que deba correr antes de pintar no puede ir en el bundle. */ ?>
    <script>
    (function () {
        var d = document.documentElement;
        d.classList.add('no-transition');
        /* ⚠️ Marca de "hay JavaScript", y no es cosmética.
           Por debajo de 1024px el sidebar es un cajón: vive en translateX(-100%) y el
           botón que lo abre lo INYECTA blog-_sidebar.js — no existe en el HTML de
           ninguna vista. Si el bundle no carga (red, bloqueador, error de sintaxis en
           otro módulo del mismo archivo concatenado), el resultado en un móvil era un
           panel sin ninguna navegación: sidebar fuera de pantalla y cero forma de
           traerlo. Las reglas del cajón cuelgan ahora de `html.js`, así que sin JS el
           sidebar se queda en el flujo — más feo, pero utilizable. */
        d.classList.add('js');
        try {
            if (window.innerWidth > 1024 && localStorage.getItem('bilbao_sidebar_collapsed') === '1') {
                d.classList.add('sidebar-boot-collapsed');
            }
        } catch (e) { /* modo privado sin localStorage: se queda expandido */ }
        // Dos frames: el primero aún es el del pintado inicial
        requestAnimationFrame(function () {
            requestAnimationFrame(function () { d.classList.remove('no-transition'); });
        });
    })();
    </script>
    <!-- GSAP — mismo CDN y versión que el sitio público (views/templates/header.php).
         Lo usa src/js/admin/admin-danger.js; todo lo que dependa de él debe
         comprobar `window.gsap` y degradar sin animación. -->
    <script defer src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <?php if (!empty($extra_head)) echo $extra_head; ?>
</head>
<body class="admin-body"<?= !empty($paginaVista) ? ' data-page="' . htmlspecialchars($paginaVista) . '"' : '' ?>>
    <?php echo $contenido; ?>

<?php
// Modal de Alex: avisa de la notificación pendiente más reciente, sea del módulo
// que sea. Antes solo lo veía el rol `usuario`; ahora también el admin,
// que igualmente reciben avisos de suplencias y horarios.
// El contador ya lo calculó _topbar-avatar.php, así que solo se consulta si hay algo.
$_alexNotif = null;
if (!empty($_SESSION['blog_usuario'])
    && !empty($GLOBALS['_notifsPendientes'])
    && class_exists(\Model\Notificacion::class)) {
    $_pend = \Model\Notificacion::noLeidas((int)$_SESSION['blog_usuario']['id'], 1);
    $_alexNotif = $_pend[0] ?? null;
}
?>
<?php if ($_alexNotif): ?>
<?php
// El título depende del nivel, no del texto del tipo: así vale para cualquier módulo.
$_titulos = [
    'exito' => ['&#127881; ¡Listo!',        'alex-mano'],
    'aviso' => ['&#9888;&#65039; Atención',  'alex-point'],
    'error' => ['&#128721; Requiere acción', 'alex-espera'],
    'info'  => ['&#128276; Novedad',         'alex-tech'],
];
[$_alexTitulo, $_alexImg] = $_titulos[$_alexNotif->nivel ?? 'info'] ?? $_titulos['info'];
$_enlace = !empty($_alexNotif->enlace) ? $_alexNotif->enlace : null;
?>
<div id="alexModal" class="alex-modal" data-notif-id="<?= (int)$_alexNotif->id ?>">
    <div class="alex-modal__card">
        <img src="/build/assets/img/alex/<?= $_alexImg ?>.png"
             alt="Alex, mascota del Colegio Bilbao" class="alex-modal__img">
        <div class="alex-modal__body">
            <p class="alex-modal__tipo"><?= $_alexTitulo ?></p>
            <p class="alex-modal__msg"><?= htmlspecialchars($_alexNotif->mensaje) ?></p>
            <div class="alex-modal__actions">
                <?php /* data-alex-cta: el JS lo engancha para marcar la notificación como
                         leída ANTES de navegar. Sin eso el modal reaparecía en el destino
                         con el mismo botón, en bucle. */ ?>
                <?php if ($_enlace): ?>
                <a href="<?= htmlspecialchars($_enlace) ?>" class="admin-btn admin-btn--primary alex-modal__cta" data-alex-cta>
                    Ver detalle
                </a>
                <?php endif; ?>
                <button type="button" class="admin-btn admin-btn--ghost" id="alexModalClose">
                    Entendido
                </button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
<script defer src="/build/js/admin.min.js"></script>
</body>
</html>
