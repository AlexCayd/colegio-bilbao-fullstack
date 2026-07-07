<!DOCTYPE html>
<html lang="es-MX">
<head>
    <script>
    (function(){
        try {
            if (localStorage.getItem('bilbao_lang') === 'en') {
                document.documentElement.lang = 'en-US';
                document.documentElement.classList.add('i18n-pending');
                setTimeout(function () {
                    document.documentElement.classList.add('i18n-fallback');
                }, 2000);
            }
        } catch (e) {}
    })();
    </script>
    <noscript><style>html.i18n-pending body{visibility:visible!important}</style></noscript>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($seo_titulo) ? s($seo_titulo) . ' | Colegio Bilbao' : 'Colegio Bilbao - ' . s($titulo) ?></title>
    <meta name="description" content="<?= s($seo_descripcion ?? $descripcion ?? 'Colegio Bilbao es una institución educativa privada en México que forma personas con criterio, carácter y vocación de servicio a través del Modelo VIDA.') ?>">
    <link rel="canonical" href="https://<?= htmlspecialchars($_SERVER['HTTP_HOST'] . strtok($_SERVER['REQUEST_URI'], '?')) ?>">

    <!-- Open Graph -->
    <meta property="og:type"        content="website">
    <meta property="og:locale"      content="es_MX">
    <meta property="og:site_name"   content="Colegio Bilbao">
    <meta property="og:title"       content="<?= s($seo_titulo ?? $titulo) ?> | Colegio Bilbao">
    <meta property="og:description" content="<?= s($seo_descripcion ?? $descripcion ?? 'Colegio Bilbao — educación con criterio y carácter.') ?>">
    <meta property="og:url"         content="https://<?= htmlspecialchars($_SERVER['HTTP_HOST'] . strtok($_SERVER['REQUEST_URI'], '?')) ?>">
    <meta property="og:image"       content="https://<?= htmlspecialchars($_SERVER['HTTP_HOST']) ?><?= !empty($seo_imagen) ? s($seo_imagen) : '/build/assets/img/global/logo-bilbao-horizontal-azul.png' ?>">

    <!-- Twitter / X -->
    <meta name="twitter:card"        content="summary_large_image">
    <meta name="twitter:title"       content="<?= s($seo_titulo ?? $titulo) ?> | Colegio Bilbao">
    <meta name="twitter:description" content="<?= s($seo_descripcion ?? $descripcion ?? 'Colegio Bilbao — educación con criterio y carácter.') ?>">

    <!-- View Transitions -->
    <meta name="view-transition" content="same-origin">

    <link rel="shortcut icon" href="/build/assets/img/global/favicon.png" type="image/png">
    <link rel="icon" type="image/png" sizes="32x32" href="/build/assets/img/global/favicon.png">
    <link rel="apple-touch-icon" href="/build/assets/img/global/favicon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.8.0/dist/leaflet.css" integrity="sha512-hoalWLoI8r4UszCkZ5kL8vayOGVae1oxXe/2A4AO6J9+580uKHDO3JdHb7NzwwzK5xr/Fs0W40kiNHxM9vyTtQ==" crossorigin="" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.2/css/all.min.css" integrity="sha512-1sCRPdkRXhBV2PBLUdRb4tMg1w2YPf37qatUFeS7zlBy7jJI8Lf4VHwWfZZfpXtYSLy85pkm9GaYVYMfw5BC1A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="/build/css/app.css">
    <script src="https://unpkg.com/leaflet@1.8.0/dist/leaflet.js" integrity="sha512-BB3hKbKWOc9Ez/TAwyWxNXeoV9c1v6FIeYiBieIWkpLjauysF18NzgR1MBNBXf8/KABdlkX68nAhlwcDFLGPCQ==" crossorigin="" defer></script>
    <?php if (!empty($extra_head)) echo $extra_head; ?>
</head>
<body>
    <?php
        include_once __DIR__ .'/templates/header.php';
        echo $contenido;
        include_once __DIR__ .'/templates/footer.php';
    ?>
</body>
</html>