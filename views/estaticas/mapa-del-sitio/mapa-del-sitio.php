<!DOCTYPE html>
<html lang="es-MX">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Mapa del sitio del Colegio Bilbao. Encuentra rápidamente información sobre niveles educativos, admisiones, vida escolar y contacto.">
    <meta name="robots" content="index, follow">
    
    <title>Mapa del Sitio | Colegio Bilbao</title>

    <!-- FAVICON -->
    <link rel="shortcut icon" href="../../assets/img/global/favicon.png" type="image/png">
    <link rel="icon" href="../../assets/img/global/favicon.png" type="image/png">

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
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        /* --- 1. VARIABLES & RESET --- */
        :root {
            --bg-global: #FFFFFF;
            --bg-offwhite: #F8FAFC;
            --col-bilbao: #4D8ABB;
            --col-espiritu: #7DC6E5;
            --col-herencia: #374C69;
            --col-texto: #374C69;
            --col-blanco: #FFFFFF;
            --col-borde: #E0E6ED;
            
            --sp-xs: 8px; --sp-sm: 16px; --sp-md: 24px; --sp-lg: 48px; --sp-xl: 80px;
            --header-height: 90px;
            --radius-card: 16px;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; font-size: 16px; }
        
        body {
            font-family: 'Montserrat', sans-serif;
            background-color: var(--bg-global);
            color: var(--col-texto);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }

        a { text-decoration: none; color: inherit; transition: 0.3s; }
        ul { list-style: none; }
        button { font-family: inherit; border: none; background: none; cursor: pointer; }
        img { max-width: 100%; height: auto; display: block; }

        /* --- HEADER & MENU STYLES (COPIA EXACTA GLOBAL) --- */
        .header-bar { position: fixed; top: 0; left: 0; width: 100%; height: var(--header-height); background-color: rgba(255, 255, 255, 0.98); border-bottom: 1px solid rgba(77, 138, 187, 0.1); display: flex; align-items: center; justify-content: center; padding: 0 24px; z-index: 1000; }
        .header-inner { width: 100%; max-width: 1280px; display: flex; justify-content: space-between; align-items: center; }
        .logo-link { display: flex; align-items: center; z-index: 1002; height: 100%; }
        .logo-img { height: 67px; width: auto; object-fit: contain; }
        .header-controls { display: flex; align-items: center; gap: 24px; z-index: 1002; }
        .lang-switch { font-size: 0.9rem; font-weight: 600; color: var(--col-herencia); }
        .lang-switch span.active { color: var(--col-bilbao); text-decoration: underline; text-underline-offset: 4px; }
        .menu-trigger { display: flex; align-items: center; justify-content: center; padding: 8px; color: var(--col-bilbao); }
        .hamburger-icon { width: 28px; height: 20px; position: relative; display: flex; flex-direction: column; justify-content: space-between; }
        .hamburger-icon span { display: block; width: 100%; height: 2px; background-color: var(--col-bilbao); border-radius: 2px; transition: all 0.3s ease; }
        .menu-overlay { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background-color: var(--bg-global); z-index: 2000; display: flex; flex-direction: column; opacity: 0; visibility: hidden; transition: opacity 0.3s ease, visibility 0.3s ease; overflow-y: auto; }
        .menu-overlay[aria-hidden="false"] { opacity: 1; visibility: visible; }
        .overlay-header { flex-shrink: 0; height: var(--header-height); display: flex; align-items: center; justify-content: center; padding: 0 24px; border-bottom: 1px solid rgba(77, 138, 187, 0.1); }
        .close-btn { width: 44px; height: 44px; position: relative; color: var(--col-bilbao); }
        .close-btn::before, .close-btn::after { content: ''; position: absolute; top: 50%; left: 50%; width: 24px; height: 2px; background-color: currentColor; }
        .close-btn::before { transform: translate(-50%, -50%) rotate(45deg); }
        .close-btn::after { transform: translate(-50%, -50%) rotate(-45deg); }
        .overlay-content { flex-grow: 1; width: 100%; max-width: 800px; margin: 0 auto; padding: 32px 24px; }
        .nav-accordion-item { border-bottom: 1px solid var(--col-borde); }
        .nav-accordion-trigger { width: 100%; display: flex; justify-content: space-between; align-items: center; padding: 24px 0; font-size: 1.5rem; font-weight: 700; color: var(--col-herencia); text-align: left; transition: color 0.2s; }
        .nav-accordion-trigger:hover, .nav-accordion-trigger[aria-expanded="true"] { color: var(--col-bilbao); }
        .nav-accordion-trigger .chevron { transition: transform 0.3s ease; font-size: 1rem; }
        .nav-accordion-trigger[aria-expanded="true"] .chevron { transform: rotate(180deg); }
        .nav-submenu { display: none; padding-bottom: 24px; padding-left: 24px; border-left: 2px solid var(--col-espiritu); margin-left: 4px; }
        .nav-submenu li { margin-bottom: 16px; }
        .nav-submenu a { font-size: 1.1rem; color: var(--col-texto); font-weight: 500; }
        .nav-submenu a:hover { color: var(--col-bilbao); }
        .no-scroll { overflow: hidden; }

        /* --- SITEMAP SPECIFIC STYLES --- */
        main { padding-top: var(--header-height); min-height: 80vh; }

        .sitemap-header {
            background-color: var(--col-herencia);
            color: white;
            padding: 80px 20px 60px;
            text-align: center;
        }

        .sitemap-h1 {
            font-size: clamp(2rem, 5vw, 3rem);
            font-weight: 800;
            margin-bottom: 20px;
        }

        /* BUSCADOR */
        .search-wrapper {
            max-width: 600px;
            margin: 0 auto;
            position: relative;
        }
        .search-input {
            width: 100%;
            padding: 16px 24px 16px 50px;
            border-radius: 50px;
            border: none;
            font-family: inherit;
            font-size: 1rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            transition: all 0.3s;
        }
        .search-input:focus {
            outline: none;
            box-shadow: 0 10px 30px rgba(77, 138, 187, 0.4);
            transform: scale(1.02);
        }
        .search-icon {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            width: 20px;
            height: 20px;
        }

        /* GRID DEL SITEMAP (Vista por Defecto) */
        .sitemap-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: var(--sp-xl) 20px;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 40px;
        }

        .sitemap-group {
            background-color: white;
            padding: 24px;
            border-radius: var(--radius-card);
            border: 1px solid var(--col-borde);
            transition: transform 0.3s ease;
        }
        .sitemap-group:hover {
            transform: translateY(-5px);
            border-color: var(--col-espiritu);
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        }

        .group-title {
            font-size: 1.2rem;
            color: var(--col-bilbao);
            font-weight: 700;
            margin-bottom: 16px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--bg-offwhite);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sitemap-list {
            list-style: none;
            padding: 0;
        }

        .sitemap-item {
            margin-bottom: 10px;
        }

        .sitemap-link {
            color: var(--col-texto);
            font-weight: 500;
            display: block;
            padding: 4px 0;
            transition: color 0.2s, padding-left 0.2s;
        }

        .sitemap-link:hover {
            color: var(--col-bilbao);
            padding-left: 8px;
        }

        /* CONTENEDOR DE RESULTADOS DE BÚSQUEDA (Oculto por defecto) */
        .search-results-container {
            max-width: 800px;
            margin: 0 auto;
            padding: var(--sp-xl) 20px;
            display: none; /* Se activa con JS */
        }

        .result-card {
            background: white;
            padding: 20px;
            border-bottom: 1px solid var(--col-borde);
            transition: background 0.2s;
        }
        .result-card:hover {
            background: var(--bg-offwhite);
        }
        .result-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--col-bilbao);
            text-decoration: none;
            display: block;
            margin-bottom: 8px;
        }
        .result-snippet {
            font-size: 0.95rem;
            color: #666;
            line-height: 1.5;
        }
        .result-breadcrumb {
            font-size: 0.8rem;
            color: var(--col-espiritu);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 4px;
            display: block;
        }

        .no-results {
            text-align: center;
            padding: 40px;
            color: #888;
            font-size: 1.1rem;
        }

        /* FOOTER (COPIA EXACTA GLOBAL) */
        footer { background-color: #F9FBFE; border-top: 1px solid var(--col-borde); padding-top: 60px; font-size: 0.95rem; color: var(--col-herencia); }
        .footer-container { width: 100%; max-width: 1280px; margin: 0 auto; padding: 0 24px; }
        .footer-header { display: flex; flex-direction: column; margin-bottom: 40px; align-items: flex-start; }
        .footer-logo-link { display: inline-block; margin-bottom: 16px; }
        .footer-logo-img { height: 77px; width: auto; object-fit: contain; display: block; }
        .footer-social-desktop { display: none; }
        .social-link { display: inline-flex; align-items: center; justify-content: center; color: var(--col-herencia); margin-right: 12px; transition: 0.2s; }
        .social-link:hover { color: var(--col-bilbao); transform: translateY(-2px); }
        .social-icon { width: 20px; height: 20px; fill: currentColor; }
        .footer-grid { display: grid; gap: 40px; grid-template-columns: 1fr; }
        .footer-desc { margin-bottom: 24px; font-size: 0.9rem; line-height: 1.6; text-align: left; }
        .footer-contact p { margin-bottom: 8px; text-align: left; font-weight: 400; }
        .footer-contact a { font-weight: 600; color: var(--col-bilbao); }
        .footer-social-mobile { margin-top: 24px; margin-bottom: 32px; display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
        .footer-col-title { font-weight: 700; color: var(--col-bilbao); margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center; width: 100%; }
        .footer-links { display: none; margin-bottom: 24px; }
        .footer-links li { margin-bottom: 8px; }
        .footer-links a:hover { color: var(--col-espiritu); text-decoration: underline; }
        .footer-legal { margin-top: 48px; padding: 24px 0; border-top: 1px solid var(--col-borde); font-size: 0.8rem; display: flex; flex-direction: column; gap: 16px; text-align: center; opacity: 0.8; }
        .legal-links { display: flex; flex-wrap: wrap; justify-content: center; gap: 16px; }
        .visible { display: block !important; }
        .rotate { transform: rotate(180deg); }
        @media (min-width: 600px) { .footer-grid { grid-template-columns: 1fr 1fr; } .footer-identity { grid-column: span 2; } }
        @media (min-width: 1024px) { 
            .footer-header { flex-direction: row; justify-content: space-between; align-items: center; }
            .footer-social-desktop { display: flex; gap: 16px; align-items: center; }
            .footer-grid { grid-template-columns: 1.4fr 1fr 1fr 1fr; gap: 36px; }
            .footer-identity { grid-column: auto; }
            .footer-links { display: block !important; }
            .footer-col-title { pointer-events: none; cursor: default; }
            .footer-col-title .chevron { display: none; }
            .footer-social-mobile { display: none; }
            .footer-legal { flex-direction: row; justify-content: space-between; text-align: left; }
        }
    </style>
</head>
<body>

    <!-- HEADER -->
    <header class="header-bar">
        <div class="header-inner">
            <a href="/" class="logo-link"><img src="../assets/img/global/logo-bilbao-horizontal-azul.png" alt="Colegio Bilbao" class="logo-img"></a>
            <div class="header-controls">
                <div class="lang-switch"><span class="active">ES</span> | <a href="/en/">EN</a></div>
                <button class="menu-trigger"><div class="hamburger-icon"><span></span><span></span><span></span></div></button>
            </div>
        </div>
    </header>

    <!-- OVERLAY MENU -->
    <div id="menu-overlay" class="menu-overlay" role="dialog" aria-modal="true" aria-hidden="true" aria-label="Menú principal">
        <div class="overlay-header">
            <div class="header-inner">
                <a href="/" class="logo-link"><img src="assets/img/global/logo-bilbao-horizontal-azul.png" alt="Colegio Bilbao" class="logo-img"></a>
                <div class="header-controls">
                    <div class="lang-switch"><span class="active">ES</span> | <a href="/en/">EN</a></div>
                    <button id="close-menu-btn" class="close-btn" aria-label="Cerrar menú"></button>
                </div>
            </div>
        </div>
        <nav class="overlay-content">
            <ul id="primary-nav">
                <li class="nav-accordion-item"><button class="nav-accordion-trigger">Conócenos <span class="chevron">▼</span></button><ul class="nav-submenu"><li><a href="conocenos/quienes-somos/">Quiénes somos</a></li><li><a href="conocenos/equipo-educativo/">Equipo educativo</a></li><li><a href="conocenos/instalaciones/">Instalaciones</a></li><li><a href="conocenos/certificaciones-y-reconocimientos/">Certificaciones y reconocimientos</a></li></ul></li>
                <li class="nav-accordion-item"><button class="nav-accordion-trigger">Modelo educativo <span class="chevron">▼</span></button><ul class="nav-submenu"><li><a href="modelo-educativo/modelo-vida/">Modelo VIDA</a></li><li><a href="modelo-educativo/filosofia-y-metodologia/">Filosofía</a></li><li><a href="modelo-educativo/aprendizaje-integral/">Aprendizaje integral</a></li><li><a href="modelo-educativo/idiomas/">Idiomas</a></li></ul></li>
                <li class="nav-accordion-item"><button class="nav-accordion-trigger">Niveles académicos <span class="chevron">▼</span></button><ul class="nav-submenu"><li><a href="niveles-academicos/preescolar/">Preescolar</a></li><li><a href="niveles-academicos/primaria/">Primaria</a></li><li><a href="niveles-academicos/secundaria/">Secundaria</a></li><li><a href="niveles-academicos/preparatoria/">Preparatoria</a></li></ul></li>
                <li class="nav-accordion-item"><button class="nav-accordion-trigger">Vida escolar <span class="chevron">▼</span></button><ul class="nav-submenu"><li><a href="vida-escolar/afterschool-extracurriculares/">Afterschool</a></li><li><a href="vida-escolar/futuro-universitario-becas/">Futuro universitario</a></li><li><a href="vida-escolar/programa-dual/">Programa Dual</a></li><li><a href="vida-escolar/servicios-para-familias/">Servicios</a></li><li><a href="vida-escolar/cuidado-y-bienestar/">Cuidado y bienestar</a></li><li><a href="vida-escolar/eventos-y-tradiciones/">Eventos</a></li></ul></li>
                <li class="nav-accordion-item"><button class="nav-accordion-trigger">Admisiones <span class="chevron">▼</span></button><ul class="nav-submenu"><li><a href="admisiones/">Inicio</a></li><li><a href="admisiones/proceso/">Proceso</a></li><li><a href="admisiones/preguntas-frecuentes/">FAQ</a></li><li><a href="admisiones/convenios/">Convenios</a></li><li><a href="admisiones/convocatoria-becas/">Becas</a></li><li><a href="admisiones/contacto/">Contacto</a></li></ul></li>
                <li class="nav-accordion-item"><button class="nav-accordion-trigger">Comunidad <span class="chevron">▼</span></button><ul class="nav-submenu"><li><a href="comunidad/estudiantes/">Estudiantes</a></li><li><a href="comunidad/familias/">Familias</a></li><li><a href="comunidad/exalumnos/">Exalumnos</a></li></ul></li>
                <li class="nav-accordion-item"><button class="nav-accordion-trigger">Voces Bilbao <span class="chevron">▼</span></button><ul class="nav-submenu"><li><a href="voces-bilbao/noticias/">Noticias</a></li><li><a href="voces-bilbao/entrevistas/">Entrevistas</a></li><li><a href="voces-bilbao/articulos/">Artículos</a></li><li><a href="voces-bilbao/testimonios/">Testimonios</a></li></ul></li>
                <li class="nav-accordion-item"><button class="nav-accordion-trigger">Contacto <span class="chevron">▼</span></button><ul class="nav-submenu"><li><a href="contacto/">Contacto</a></li><li><a href="contacto/directorio/">Directorio</a></li><li><a href="contacto/cultura-y-talento/">Cultura y talento</a></li><li><a href="contacto/proveedores/">Proveedores</a></li></ul></li>
            </ul>
        </nav>
    </div>

    <!-- MAIN CONTENT -->
    <main>
        
        <!-- SITEMAP HEADER & SEARCH -->
        <section class="sitemap-header">
            <h1 class="sitemap-h1">Mapa del Sitio</h1>
            <div class="search-wrapper">
                <!-- SVG Lupa -->
                <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="text" id="sitemapSearch" class="search-input" placeholder="Buscar página, tema o palabra clave...">
            </div>
        </section>

        <!-- SITEMAP GRID (DEFAULT VIEW) -->
        <div class="sitemap-container" id="sitemapContainer">

            <!-- Inicio -->
            <div class="sitemap-group">
                <h2 class="group-title">General</h2>
                <ul class="sitemap-list">
                    <li class="sitemap-item"><a href="/" class="sitemap-link">Inicio</a></li>
                </ul>
            </div>

            <!-- Conócenos -->
            <div class="sitemap-group">
                <h2 class="group-title">Conócenos</h2>
                <ul class="sitemap-list">
                    <li class="sitemap-item"><a href="conocenos/quienes-somos/" class="sitemap-link">Quiénes somos</a></li>
                    <li class="sitemap-item"><a href="conocenos/equipo-educativo/" class="sitemap-link">Equipo educativo</a></li>
                    <li class="sitemap-item"><a href="conocenos/instalaciones/" class="sitemap-link">Instalaciones</a></li>
                    <li class="sitemap-item"><a href="conocenos/certificaciones-y-reconocimientos/" class="sitemap-link">Certificaciones</a></li>
                </ul>
            </div>

            <!-- Modelo Educativo -->
            <div class="sitemap-group">
                <h2 class="group-title">Modelo Educativo</h2>
                <ul class="sitemap-list">
                    <li class="sitemap-item"><a href="modelo-educativo/modelo-vida/" class="sitemap-link">Modelo VIDA</a></li>
                    <li class="sitemap-item"><a href="modelo-educativo/filosofia-y-metodologia/" class="sitemap-link">Filosofía</a></li>
                    <li class="sitemap-item"><a href="modelo-educativo/aprendizaje-integral/" class="sitemap-link">Aprendizaje integral</a></li>
                    <li class="sitemap-item"><a href="modelo-educativo/idiomas/" class="sitemap-link">Idiomas</a></li>
                </ul>
            </div>

            <!-- Niveles -->
            <div class="sitemap-group">
                <h2 class="group-title">Niveles Académicos</h2>
                <ul class="sitemap-list">
                    <li class="sitemap-item"><a href="niveles-academicos/preescolar/" class="sitemap-link">Preescolar</a></li>
                    <li class="sitemap-item"><a href="niveles-academicos/primaria/" class="sitemap-link">Primaria</a></li>
                    <li class="sitemap-item"><a href="niveles-academicos/secundaria/" class="sitemap-link">Secundaria</a></li>
                    <li class="sitemap-item"><a href="niveles-academicos/preparatoria/" class="sitemap-link">Preparatoria</a></li>
                </ul>
            </div>

            <!-- Vida Escolar -->
            <div class="sitemap-group">
                <h2 class="group-title">Vida Escolar</h2>
                <ul class="sitemap-list">
                    <li class="sitemap-item"><a href="vida-escolar/afterschool-extracurriculares/" class="sitemap-link">Afterschool</a></li>
                    <li class="sitemap-item"><a href="vida-escolar/futuro-universitario-becas/" class="sitemap-link">Futuro universitario</a></li>
                    <li class="sitemap-item"><a href="vida-escolar/programa-dual/" class="sitemap-link">Programa Dual</a></li>
                    <li class="sitemap-item"><a href="vida-escolar/servicios-para-familias/" class="sitemap-link">Servicios</a></li>
                    <li class="sitemap-item"><a href="vida-escolar/cuidado-y-bienestar/" class="sitemap-link">Cuidado y bienestar</a></li>
                    <li class="sitemap-item"><a href="vida-escolar/eventos-y-tradiciones/" class="sitemap-link">Eventos</a></li>
                </ul>
            </div>

            <!-- Admisiones -->
            <div class="sitemap-group">
                <h2 class="group-title">Admisiones</h2>
                <ul class="sitemap-list">
                    <li class="sitemap-item"><a href="admisiones/" class="sitemap-link">Inicio Admisiones</a></li>
                    <li class="sitemap-item"><a href="admisiones/proceso/" class="sitemap-link">Proceso de admisión</a></li>
                    <li class="sitemap-item"><a href="admisiones/preguntas-frecuentes/" class="sitemap-link">Preguntas frecuentes</a></li>
                    <li class="sitemap-item"><a href="admisiones/convenios/" class="sitemap-link">Convenios</a></li>
                    <li class="sitemap-item"><a href="admisiones/convocatoria-becas/" class="sitemap-link">Becas</a></li>
                    <li class="sitemap-item"><a href="admisiones/contacto/" class="sitemap-link">Contacto Admisiones</a></li>
                </ul>
            </div>

            <!-- Comunidad -->
            <div class="sitemap-group">
                <h2 class="group-title">Comunidad</h2>
                <ul class="sitemap-list">
                    <li class="sitemap-item"><a href="comunidad/estudiantes/" class="sitemap-link">Estudiantes</a></li>
                    <li class="sitemap-item"><a href="comunidad/familias/" class="sitemap-link">Familias</a></li>
                    <li class="sitemap-item"><a href="comunidad/exalumnos/" class="sitemap-link">Exalumnos</a></li>
                </ul>
            </div>

            <!-- Voces -->
            <div class="sitemap-group">
                <h2 class="group-title">Voces Bilbao</h2>
                <ul class="sitemap-list">
                    <li class="sitemap-item"><a href="voces-bilbao/noticias/" class="sitemap-link">Noticias</a></li>
                    <li class="sitemap-item"><a href="voces-bilbao/entrevistas/" class="sitemap-link">Entrevistas</a></li>
                    <li class="sitemap-item"><a href="voces-bilbao/articulos/" class="sitemap-link">Artículos</a></li>
                    <li class="sitemap-item"><a href="voces-bilbao/testimonios/" class="sitemap-link">Testimonios</a></li>
                </ul>
            </div>

            <!-- Contacto -->
            <div class="sitemap-group">
                <h2 class="group-title">Contacto</h2>
                <ul class="sitemap-list">
                    <li class="sitemap-item"><a href="contacto/" class="sitemap-link">Contacto General</a></li>
                    <li class="sitemap-item"><a href="contacto/directorio/" class="sitemap-link">Directorio</a></li>
                    <li class="sitemap-item"><a href="contacto/cultura-y-talento/" class="sitemap-link">Cultura y talento</a></li>
                    <li class="sitemap-item"><a href="contacto/proveedores/" class="sitemap-link">Proveedores</a></li>
                </ul>
            </div>

            <!-- Legales -->
            <div class="sitemap-group">
                <h2 class="group-title">Legales</h2>
                <ul class="sitemap-list">
                    <li class="sitemap-item"><a href="aviso-de-privacidad/" class="sitemap-link">Aviso de privacidad</a></li>
                    <li class="sitemap-item"><a href="terminos-y-condiciones/" class="sitemap-link">Términos y condiciones</a></li>
                </ul>
            </div>
        </div>

        <!-- SEARCH RESULTS CONTAINER (HIDDEN BY DEFAULT) -->
        <div id="searchResultsContainer" class="search-results-container">
            <!-- Results will be injected here via JS -->
            <div class="no-results" id="noResults" style="display:none;">No se encontraron resultados para tu búsqueda.</div>
        </div>

    </main>

    <!-- FOOTER -->
    <footer>
        <div class="footer-container">
            <div class="footer-header">
                <a href="/" class="footer-logo-link"><img src="assets/img/global/logo-bilbao-horizontal-azul.png" alt="Colegio Bilbao" class="footer-logo-img"></a>
                <div class="footer-social-desktop">
                    <span style="margin-right: 8px;">Síguenos</span>
                    <a href="https://www.facebook.com/bilbaocolegio/" target="_blank" rel="noopener noreferrer" class="social-link" aria-label="Facebook"><svg class="social-icon" viewBox="0 0 24 24"><path d="M14 13.5h2.5l1-4H14v-2c0-1.03 0-2 2-2h1.5V1.74c-.75-.07-2.47-.24-4.75-.24-4.8 0-8 2.9-8 8v3h-5v4h5v11.5h7V13.5z"/></svg></a>
                    <a href="https://www.instagram.com/bilbaocolegio/" target="_blank" rel="noopener noreferrer" class="social-link" aria-label="Instagram"><svg class="social-icon" viewBox="0 0 24 24"><path d="M12 2.16c3.2 0 3.58.01 4.85.07 3.25.15 4.77 1.69 4.92 4.92.06 1.27.07 1.65.07 4.85 0 3.2-.01 3.58-.07 4.85-.15 3.23-1.66 4.77-4.92 4.92-1.27.06-1.64.07-4.85.07-3.2 0-3.58-.01-4.85-.07-3.23-.15-4.77-1.66-4.92-4.92-.06-1.27-.07-1.64-.07-4.85 0-3.2.01-3.58.07-4.85.15-3.23 1.66-4.77 4.92-4.92 1.27-.06 1.65-.07 4.85-.07M12 0C8.74 0 8.33.01 7.05.07 2.7.27.27 2.7.07 7.05.01 8.33 0 8.74 0 12s.01 3.67.07 4.95c.2 4.36 2.62 6.78 6.98 6.98 1.28.06 1.69.07 4.95.07s3.67-.01 4.95-.07c4.36-.2 6.78-2.62 6.98-6.98.06-1.28.07-1.69.07-4.95s-.01-3.67-.07-4.95C23.73 2.7 21.31.27 16.95.07 15.67.01 15.26 0 12 0zm0 5.84c-3.4 0-6.16 2.76-6.16 6.16 0 3.4 2.76 6.16 6.16 6.16 3.4 0 6.16-2.76 6.16-6.16 0-3.4-2.76-6.16-6.16-6.16zm0 10.16c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4zm6.4-10.8c0 .66-.54 1.2-1.2 1.2-.66 0-1.2-.54-1.2-1.2 0-.66.54-1.2 1.2-1.2.66 0 1.2.54 1.2 1.2z"/></svg></a>
                    <a href="https://www.youtube.com/@ColegioBilbaoOficial" target="_blank" rel="noopener noreferrer" class="social-link" aria-label="YouTube"><svg class="social-icon" viewBox="0 0 24 24"><path d="M19.615 3.184c-3.604-.246-11.631-.245-15.23 0-3.897.266-4.356 2.62-4.385 8.816.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0 3.897-.266 4.356-2.62 4.385-8.816-.029-6.185-.484-8.549-4.385-8.816zm-10.615 12.816v-8l8 3.993-8 4.007z"/></svg></a>
                    <a href="https://mx.linkedin.com/company/colegio-bilbao" target="_blank" rel="noopener noreferrer" class="social-link" aria-label="LinkedIn"><svg class="social-icon" viewBox="0 0 24 24"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/></svg></a>
                    <span style="margin: 0 16px;">|</span>
                    <div class="lang-switch" style="display:inline-block"><span class="active">ES</span> | <a href="/en/">EN</a></div>
                </div>
            </div>
            
            <div class="footer-grid">
                <div class="footer-identity">
                    <p class="footer-desc">Escuela privada K-12 en la zona poniente de la Ciudad de México.</p>
                    <div class="footer-contact">
                        <p><strong>Dirección:</strong><br>Tlalmimilolpan 39, San Mateo Tlaltenango,<br>Cuajimalpa de Morelos, 05600 Ciudad de México, CDMX</p>
                        <p><strong>Teléfonos:</strong></p>
                        <p>Conmutador: 55 5810 1346</p>
                        <p>Admisiones:<br> <a href="tel:+525549839745">+52 55 4983 9745</a><br><a href="tel:+525614612682">+52 56 1461 2682</a></p>
                        <p style="margin-top:12px"><a href="/contacto/">Ver ubicación y mapa →</a></p>
                    </div>
                </div>
                <div class="footer-col">
                    <button class="footer-col-title">Conócenos <span class="chevron">▼</span></button>
                    <ul class="footer-links">
                        <li><a href="/conocenos/quienes-somos/">Quiénes somos</a></li>
                        <li><a href="/conocenos/equipo-educativo/">Equipo educativo</a></li>
                        <li><a href="/conocenos/instalaciones/">Instalaciones</a></li>
                        <li><a href="/conocenos/certificaciones-y-reconocimientos/">Certificaciones y reconocimientos</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <button class="footer-col-title">Niveles <span class="chevron">▼</span></button>
                    <ul class="footer-links">
                        <li><a href="/niveles-academicos/preescolar/">Preescolar</a></li>
                        <li><a href="/niveles-academicos/primaria/">Primaria</a></li>
                        <li><a href="/niveles-academicos/secundaria/">Secundaria</a></li>
                        <li><a href="/niveles-academicos/preparatoria/">Preparatoria</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <button class="footer-col-title">Comunidad <span class="chevron">▼</span></button>
                    <ul class="footer-links">
                        <li><a href="/admisiones/">Admisiones</a></li>
                        <li><a href="/comunidad/familias/">Familias</a></li>
                        <li><a href="/voces-bilbao/noticias/">Noticias</a></li>
                        <li><a href="/contacto/cultura-y-talento/">Bolsa de trabajo</a></li>
                    </ul>
                </div>
            </div>

             <div class="footer-legal">
                <div class="legal-links">
                    <a href="/aviso-de-privacidad/">Aviso de privacidad</a> · 
                    <a href="/terminos-y-condiciones/">Términos y condiciones</a> · 
                    <a href="/mapa-del-sitio/">Mapa del sitio</a>
                </div>
                <div>© 2025 Colegio Bilbao. Todos los derechos reservados.</div>
            </div>
        </div>
    </footer>

    <script>
        const menuTrigger = document.querySelector('.menu-trigger');
        const closeMenuBtn = document.getElementById('close-menu-btn');
        const menuOverlay = document.getElementById('menu-overlay');
        const body = document.body;
        const navAccordions = document.querySelectorAll('.nav-accordion-trigger');
        const footerToggles = document.querySelectorAll('.footer-col-title');

        function toggleMenu(show) {
            menuOverlay.setAttribute('aria-hidden', !show);
            menuTrigger.setAttribute('aria-expanded', show);
            show ? body.classList.add('no-scroll') : body.classList.remove('no-scroll');
            (show ? closeMenuBtn : menuTrigger).focus();
        }

        menuTrigger.addEventListener('click', () => toggleMenu(true));
        closeMenuBtn.addEventListener('click', () => toggleMenu(false));

        navAccordions.forEach(trigger => {
            trigger.addEventListener('click', () => {
                const submenu = trigger.nextElementSibling;
                if(submenu) {
                    const expanded = trigger.getAttribute('aria-expanded') === 'true';
                    trigger.setAttribute('aria-expanded', !expanded);
                    submenu.style.display = expanded ? 'none' : 'block';
                }
            });
        });

        footerToggles.forEach(toggle => {
            toggle.addEventListener('click', () => {
                if (window.innerWidth < 1024) {
                    const content = toggle.nextElementSibling;
                    const expanded = toggle.getAttribute('aria-expanded') === 'true';
                    toggle.setAttribute('aria-expanded', !expanded);
                    content.style.display = expanded ? 'none' : 'block';
                }
            });
        });

        window.addEventListener('resize', () => {
             if(window.innerWidth >= 1024) {
                 document.querySelectorAll('.footer-links').forEach(el => el.style.display = 'block');
                 document.querySelectorAll('.footer-col-title .chevron').forEach(el => el.style.transform = '');
             } else {
                 document.querySelectorAll('.footer-links').forEach(el => el.style.display = 'none');
             }
        });

        // --- SEARCH ENGINE LOGIC ---
        // Índice de contenidos del sitio (Simulado)
        const siteData = [
            { title: "Inicio", url: "/", content: "bienvenido colegio bilbao educación integral k-12 excelencia académica valores humanistas formar personas" },
            { title: "Quiénes Somos", url: "conocenos/quienes-somos/", content: "historia misión visión valores identidad filosofía educativa directivos liderazgo" },
            { title: "Equipo Educativo", url: "conocenos/equipo-educativo/", content: "maestros docentes profesores personal directivo coordinación acompañamiento psicopedagogía" },
            { title: "Instalaciones", url: "conocenos/instalaciones/", content: "campus bosque aulas laboratorios canchas deportivas espacios verdes seguridad" },
            { title: "Certificaciones", url: "conocenos/certificaciones-y-reconocimientos/", content: "validez sep cendes convenios universidades certificaciones calidad educativa" },
            { title: "Modelo VIDA", url: "modelo-educativo/modelo-vida/", content: "vincula indaga descubre aporta metodología modelo educativo valores pilares" },
            { title: "Preescolar", url: "niveles-academicos/preescolar/", content: "kinder preescolar niños pequeños highscope juego estimulación temprana" },
            { title: "Primaria", url: "niveles-academicos/primaria/", content: "educación primaria niños indagación proyectos bilingüe inglés español" },
            { title: "Secundaria", url: "niveles-academicos/secundaria/", content: "adolescentes secundaria debate pensamiento crítico ciencias humanidades" },
            { title: "Preparatoria", url: "niveles-academicos/preparatoria/", content: "bachillerato prepa universidad certificación dual orientación vocacional futuro" },
            { title: "Admisiones", url: "admisiones/", content: "proceso inscripción nuevo ingreso costos colegiaturas becas examen admisión" },
            { title: "Vida Escolar", url: "vida-escolar/eventos-y-tradiciones/", content: "eventos tradiciones día de muertos navidad kermés deportes cultura arte" },
            { title: "Afterschool", url: "vida-escolar/afterschool-extracurriculares/", content: "talleres extracurriculares deportes música arte horario extendido" },
             { title: "Contacto", url: "contacto/", content: "teléfono dirección mapa correo ubicación dudas informes" }
        ];

        const searchInput = document.getElementById('sitemapSearch');
        const sitemapContainer = document.getElementById('sitemapContainer');
        const resultsContainer = document.getElementById('searchResultsContainer');
        const noResults = document.getElementById('noResults');

        function performSearch(term) {
            // Limpiar resultados previos
            resultsContainer.innerHTML = '';
            
            if (term.length < 2) {
                sitemapContainer.style.display = 'grid';
                resultsContainer.style.display = 'none';
                noResults.style.display = 'none';
                return;
            }

            sitemapContainer.style.display = 'none';
            resultsContainer.style.display = 'block';

            const filtered = siteData.filter(page => 
                page.title.toLowerCase().includes(term) || 
                page.content.toLowerCase().includes(term)
            );

            if (filtered.length === 0) {
                noResults.style.display = 'block';
                resultsContainer.appendChild(noResults);
            } else {
                noResults.style.display = 'none';
                filtered.forEach(page => {
                    const card = document.createElement('div');
                    card.className = 'result-card';
                    card.innerHTML = `
                        <span class="result-breadcrumb">Página encontrada</span>
                        <a href="${page.url}" class="result-title">${page.title}</a>
                        <p class="result-snippet">Contiene información relacionada con: <strong>${term}</strong></p>
                    `;
                    resultsContainer.appendChild(card);
                });
            }
        }

        searchInput.addEventListener('input', (e) => {
            performSearch(e.target.value.toLowerCase());
        });

    </script>
</body>
</html>