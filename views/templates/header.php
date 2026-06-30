<!DOCTYPE html>
<html lang="es-MX">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Colegio Bilbao: Formación integral K-12 en CDMX. Un modelo humanista, bilingüe y con excelencia académica para el futuro de tus hijos.">
    <meta name="robots" content="index, follow">

    <title>Colegio Bilbao | Formando personas para la vida</title>

    <!-- FAVICON -->
    <link rel="shortcut icon" href="/build/assets/img/global/favicon.png" type="image/png">
    <link rel="icon" href="/build/assets/img/global/favicon.png" type="image/png">

    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-8XVWCDM02P"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', 'G-8XVWCDM02P');
    </script>

    <!-- Tipografía Montserrat -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <!-- GSAP — defer evita bloqueo de renderizado; preconnect adelanta la conexión -->
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <script defer src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script defer src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>
</head>
<body>

    <!-- HEADER -->
    <header class="header-bar">
        <div class="header-inner">
            <a href="/" class="logo-link"><img src="/build/assets/img/global/logo-bilbao-horizontal-azul.png" alt="Colegio Bilbao" class="logo-img" loading="lazy"></a>

            <nav class="header-nav" aria-label="Navegación principal">
                <a href="/conocenos/quienes-somos"  class="header-nav__link">Conócenos</a>
                <a href="/niveles"                  class="header-nav__link">Niveles</a>
                <a href="/modelo-educativo/modelo-vida" class="header-nav__link">Modelo educativo</a>
                <a href="/admisiones"               class="header-nav__link">Admisiones</a>
                <a href="/noticias"                 class="header-nav__link">Noticias</a>
                <a href="/blog"                     class="header-nav__link">Blog</a>
                <a href="/contacto"                 class="header-nav__link">Visítanos</a>
            </nav>

            <div class="header-controls">
                <div class="lang-switch"><span class="active">ES</span> | <a href="#" id="lang-en-btn">EN</a></div>
                <button id="theme-toggle" class="header-theme-btn" aria-label="Cambiar modo de color" title="Modo oscuro / claro">
                    <span id="theme-icon" class="theme-icon-wrap">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon-sun" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/></svg>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon-moon" style="display:none" aria-hidden="true"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
                    </span>
                </button>
                <a href="https://wa.me/525614612682?text=Hola,%20quiero%20agendar%20una%20visita%20al%20Colegio%20Bilbao."
                   class="header-agenda-btn" target="_blank" rel="noopener">Agenda</a>
                <button class="menu-trigger" aria-label="Abrir menú"><div class="hamburger-icon"><span></span><span></span><span></span></div></button>
            </div>
        </div>
    </header>

    <!-- OVERLAY MENU -->
    <div id="menu-overlay" class="menu-overlay" role="dialog" aria-modal="true" aria-hidden="true" aria-label="Menú principal">
        <div class="overlay-header">
            <div class="header-inner">
                <a href="/" class="logo-link"><img src="/build/assets/img/global/logo-bilbao-horizontal-azul.png" alt="Colegio Bilbao" class="logo-img" loading="lazy"></a>
                <div class="header-controls">
                    <div class="lang-switch"><span class="active">ES</span> | <a href="#" class="lang-en-overlay-btn">EN</a></div>
                    <button id="close-menu-btn" class="close-btn" aria-label="Cerrar menú"></button>
                </div>
            </div>
        </div>
        <nav class="overlay-content">
            <ul id="primary-nav">
                <li class="nav-accordion-item"><button class="nav-accordion-trigger">Conócenos <span class="chevron">▼</span></button><ul class="nav-submenu"><li><a href="/conocenos/quienes-somos">Quiénes somos</a></li><li><a href="/conocenos/equipo-educativo">Equipo educativo</a></li><li><a href="/conocenos/instalaciones">Instalaciones</a></li><li><a href="/conocenos/certificaciones-y-reconocimientos">Certificaciones y reconocimientos</a></li></ul></li>
                <li class="nav-accordion-item"><button class="nav-accordion-trigger">Modelo educativo <span class="chevron">▼</span></button><ul class="nav-submenu"><li><a href="/modelo-educativo/modelo-vida">Modelo VIDA</a></li><li><a href="/modelo-educativo/filosofia-y-metodologia">Filosofía</a></li><li><a href="/modelo-educativo/aprendizaje-integral">Aprendizaje integral</a></li><li><a href="/modelo-educativo/idiomas">Idiomas</a></li></ul></li>
                <li class="nav-accordion-item"><button class="nav-accordion-trigger">Niveles académicos <span class="chevron">▼</span></button><ul class="nav-submenu"><li><a href="/niveles-academicos/preescolar">Preescolar</a></li><li><a href="/niveles-academicos/primaria">Primaria</a></li><li><a href="/niveles-academicos/secundaria">Secundaria</a></li><li><a href="/niveles-academicos/preparatoria">Preparatoria</a></li></ul></li>
                <li class="nav-accordion-item"><button class="nav-accordion-trigger">Vida escolar <span class="chevron">▼</span></button><ul class="nav-submenu"><li><a href="/vida-escolar/afterschool-extracurriculares">Afterschool</a></li><li><a href="/vida-escolar/futuro-universitario-becas">Futuro universitario</a></li><li><a href="/vida-escolar/programa-dual">Programa Dual</a></li><li><a href="/vida-escolar/servicios-para-familias">Servicios</a></li><li><a href="/vida-escolar/cuidado-y-bienestar">Cuidado y bienestar</a></li><li><a href="/vida-escolar/eventos-y-tradiciones">Eventos</a></li></ul></li>
                <li class="nav-accordion-item"><button class="nav-accordion-trigger">Admisiones <span class="chevron">▼</span></button><ul class="nav-submenu"><li><a href="/admisiones">Inicio</a></li><li><a href="/admisiones/proceso">Proceso</a></li><li><a href="/admisiones/preguntas-frecuentes">FAQ</a></li><li><a href="/admisiones/convenios">Convenios</a></li><li><a href="/admisiones/convocatoria-becas">Becas</a></li><li><a href="/admisiones/contacto">Contacto</a></li></ul></li>
                <li class="nav-accordion-item"><button class="nav-accordion-trigger">Comunidad <span class="chevron">▼</span></button><ul class="nav-submenu"><li><a href="/comunidad/estudiantes">Estudiantes</a></li><li><a href="/comunidad/familias">Familias</a></li><li><a href="/comunidad/exalumnos">Exalumnos</a></li></ul></li>
                <li class="nav-accordion-item"><button class="nav-accordion-trigger">Voces Bilbao <span class="chevron">▼</span></button><ul class="nav-submenu"><li><a href="/voces-bilbao/noticias">Noticias</a></li><li><a href="/voces-bilbao/entrevistas">Entrevistas</a></li><li><a href="/voces-bilbao/articulos">Artículos</a></li><li><a href="/voces-bilbao/testimonios">Testimonios</a></li></ul></li>
                <li class="nav-accordion-item"><button class="nav-accordion-trigger">Contacto <span class="chevron">▼</span></button><ul class="nav-submenu"><li><a href="/contacto">Contacto</a></li><li><a href="/contacto/directorio">Directorio</a></li><li><a href="/contacto/cultura-y-talento">Cultura y talento</a></li><li><a href="/contacto/proveedores">Proveedores</a></li></ul></li>
            </ul>
        </nav>
    </div>
