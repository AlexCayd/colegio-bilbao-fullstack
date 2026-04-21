<!DOCTYPE html>
<html lang="es-MX">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Conoce al equipo educativo del Colegio Bilbao: una comunidad profesional comprometida con el aprendizaje y la experiencia humana de cada estudiante.">
    <meta name="robots" content="index, follow">
    
    <title>Equipo Educativo | Colegio Bilbao</title>

    <!-- FAVICON (Compatibilidad reforzada) -->
    <!-- Asegúrate de que el archivo 'favicon.png' exista en assets/img/global/ -->
    <link rel="icon" href="/assets/img/global/favicon.png" type="image/png">
    <link rel="shortcut icon" href="/assets/img/global/favicon.png" type="image/png">


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
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        /* --- 1. VARIABLES & RESET --- */
        :root {
            --bg-global: #F9FBFE;
            --col-bilbao: #4D8ABB;
            --col-espiritu: #7DC6E5;
            --col-herencia: #374C69;
            --col-texto: #374C69;
            --col-blanco: #FFFFFF;
            --col-borde: #E0E6ED;
            
            --sp-xs: 8px; --sp-sm: 16px; --sp-md: 24px; --sp-lg: 32px; --sp-xl: 48px; --sp-xxl: 64px;
            --font-main: 'Montserrat', sans-serif;
            --max-width: 1280px;
            --header-height: 90px;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        
        body {
            font-family: var(--font-main);
            background-color: var(--bg-global);
            color: var(--col-texto);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }

        a { text-decoration: none; color: inherit; transition: color 0.2s ease; }
        ul { list-style: none; }
        button { font-family: inherit; border: none; background: none; cursor: pointer; }
        img { max-width: 100%; height: auto; display: block; }
        p { text-align: justify; }
        .text-center { text-align: center !important; }
        .text-highlight { color: var(--col-espiritu); font-weight: 700; }

        /* --- HEADER & MENU STYLES --- */
        .header-bar { position: fixed; top: 0; left: 0; width: 100%; height: var(--header-height); background-color: rgba(249, 251, 254, 0.95); backdrop-filter: blur(10px); z-index: 1000; border-bottom: 1px solid rgba(77, 138, 187, 0.1); display: flex; align-items: center; justify-content: center; padding: 0 var(--sp-md); }
        .header-inner { width: 100%; max-width: var(--max-width); display: flex; justify-content: space-between; align-items: center; }
        .logo-link { display: flex; align-items: center; z-index: 1002; height: 100%; }
        .logo-img { height: 67px; width: auto; object-fit: contain; }
        .header-controls { display: flex; align-items: center; gap: var(--sp-md); z-index: 1002; }
        .lang-switch { font-size: 0.9rem; font-weight: 600; color: var(--col-herencia); }
        .lang-switch span.active { color: var(--col-bilbao); text-decoration: underline; text-underline-offset: 4px; }
        .menu-trigger { display: flex; align-items: center; justify-content: center; padding: 8px; color: var(--col-bilbao); }
        .hamburger-icon { width: 28px; height: 20px; position: relative; display: flex; flex-direction: column; justify-content: space-between; }
        .hamburger-icon span { display: block; width: 100%; height: 2px; background-color: var(--col-bilbao); border-radius: 2px; transition: all 0.3s ease; }
        
        .menu-overlay { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background-color: var(--bg-global); z-index: 2000; display: flex; flex-direction: column; opacity: 0; visibility: hidden; transition: opacity 0.3s ease, visibility 0.3s ease; overflow-y: auto; }
        .menu-overlay[aria-hidden="false"] { opacity: 1; visibility: visible; }
        .overlay-header { flex-shrink: 0; height: var(--header-height); display: flex; align-items: center; justify-content: center; padding: 0 var(--sp-md); border-bottom: 1px solid rgba(77, 138, 187, 0.1); }
        .close-btn { width: 44px; height: 44px; position: relative; color: var(--col-bilbao); }
        .close-btn::before, .close-btn::after { content: ''; position: absolute; top: 50%; left: 50%; width: 24px; height: 2px; background-color: currentColor; }
        .close-btn::before { transform: translate(-50%, -50%) rotate(45deg); }
        .close-btn::after { transform: translate(-50%, -50%) rotate(-45deg); }
        .overlay-content { flex-grow: 1; width: 100%; max-width: 800px; margin: 0 auto; padding: var(--sp-lg) var(--sp-md); }
        .nav-accordion-item { border-bottom: 1px solid var(--col-borde); }
        .nav-accordion-trigger { width: 100%; display: flex; justify-content: space-between; align-items: center; padding: var(--sp-md) 0; font-size: 1.5rem; font-weight: 700; color: var(--col-herencia); text-align: left; transition: color 0.2s; }
        .nav-accordion-trigger:hover, .nav-accordion-trigger[aria-expanded="true"] { color: var(--col-bilbao); }
        .nav-accordion-trigger .chevron { transition: transform 0.3s ease; font-size: 1rem; }
        .nav-accordion-trigger[aria-expanded="true"] .chevron { transform: rotate(180deg); }
        .nav-submenu { display: none; padding-bottom: var(--sp-md); padding-left: var(--sp-md); border-left: 2px solid var(--col-espiritu); margin-left: 4px; }
        .nav-submenu li { margin-bottom: var(--sp-sm); }
        .nav-submenu a { font-size: 1.1rem; color: var(--col-texto); font-weight: 500; }
        .nav-submenu a:hover { color: var(--col-bilbao); }
        .no-scroll { overflow: hidden; }

        /* --- PAGE STYLES --- */
        main { padding-top: var(--header-height); }

        /* HERO */
        .page-hero {
            position: relative;
            min-height: 50vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: var(--col-blanco);
            background-color: var(--col-herencia);
            /* Ruta Relativa: sube 2 niveles */
            background-image: 
                linear-gradient(rgba(55, 76, 105, 0.2), rgba(55, 76, 105, 0.1)), 
                url('../../assets/img/conocenos/equipo-educativo/instalaciones-tunel.jpg');
            background-size: cover;
            background-position: center;
            padding: var(--sp-xxl) var(--sp-md);
        }

        .hero-content {
            max-width: 900px;
            z-index: 2;
            text-shadow: 0 4px 15px rgba(0,0,0,0.7);
        }

        .hero-content h1 {
            font-size: clamp(2.5rem, 5vw, 4rem);
            font-weight: 700;
            margin-bottom: var(--sp-md);
            line-height: 1.1;
            text-align: center; 
            color: var(--col-blanco);
        }

        .hero-content p {
            font-size: clamp(1.1rem, 2vw, 1.5rem);
            font-weight: 500;
            opacity: 1;
            text-align: center;
            color: var(--col-blanco);
        }

        /* MINI MENÚ */
        .page-nav-wrapper {
            background-color: var(--col-blanco);
            border-bottom: 1px solid var(--col-borde);
            position: sticky; top: var(--header-height); z-index: 900;
            padding: 10px 0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }
        .page-nav {
            max-width: var(--max-width); margin: 0 auto; display: flex; justify-content: center; flex-wrap: wrap; gap: var(--sp-sm); padding: 0 var(--sp-md);
        }
        .page-nav-link {
            font-size: 0.85rem; font-weight: 600; color: var(--col-herencia); padding: 6px 14px; border-radius: 20px; background-color: #F0F4F8; transition: all 0.2s ease; white-space: nowrap;
        }
        .page-nav-link:hover {
            background-color: var(--col-espiritu); color: var(--col-blanco); transform: translateY(-2px);
        }

        /* CONTENEDOR GENERAL */
        .section-container { max-width: var(--max-width); margin: 0 auto; padding: var(--sp-xxl) var(--sp-md); scroll-margin-top: 140px; }
        
        /* RESUMEN (FULL WIDTH) */
        .summary-full-width {
            width: 100%;
            background: radial-gradient(circle at top right, #8da4c3 0%, var(--col-herencia) 80%);
            color: var(--col-blanco);
            padding: 150px var(--sp-md);
            text-align: center;
            position: relative;
            overflow: hidden;
            margin-top: 0; 
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .summary-content-wrapper {
            max-width: 900px;
            width: 100%;
            position: relative;
            z-index: 2;
        }

        .summary-lead {
            font-size: clamp(1.4rem, 3vw, 1.8rem); 
            font-weight: 300;
            line-height: 1.5;
            margin-bottom: var(--sp-lg);
            color: var(--col-blanco);
            text-align: center;
        }

        .summary-body {
            font-size: clamp(1rem, 1.5vw, 1.15rem);
            font-weight: 700;
            line-height: 1.8;
            opacity: 1;
            color: var(--col-blanco);
            max-width: 800px;
            margin: 0 auto;
            text-align: center;
        }

        /* HEADER DE DIRECTIVOS */
        .directors-header-clean {
            text-align: center;
            max-width: 800px;
            margin: 0 auto var(--sp-xl) auto;
        }

        .dh-title-clean {
            font-size: 2.5rem;
            color: var(--col-bilbao);
            font-weight: 700;
            margin-bottom: var(--sp-sm);
            position: relative;
            display: inline-block;
        }
        
        .dh-separator {
            width: 80px;
            height: 4px;
            background-color: var(--col-espiritu);
            border-radius: 2px;
            margin: 0 auto var(--sp-md) auto;
        }

        .dh-intro-text p {
            font-size: 1.15rem;
            color: var(--col-herencia);
            line-height: 1.7;
            text-align: center;
            margin-bottom: var(--sp-sm);
            font-weight: 400;
        }

        /* GRID DE DIRECTIVOS */
        .directors-grid {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 40px;
            margin-top: var(--sp-lg);
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
        }

        .director-card {
            background: transparent;
            text-align: center !important; 
            padding: var(--sp-md);
            transition: transform 0.3s ease;
            flex: 0 1 300px; 
            margin: 0; 
        }

        /* Orden Móvil */
        .director-card.general { order: -1; }
        
        @media (min-width: 900px) {
            .director-card.general { order: 0; }
        }

        .director-card p { text-align: center !important; }

        .director-img-wrapper {
            width: 170px;
            height: 170px;
            margin: 0 auto var(--sp-md);
            padding: 4px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--col-bilbao), var(--col-espiritu), var(--col-herencia));
            transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .director-img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--col-blanco); 
            background-color: var(--col-blanco);
        }

        .director-name {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--col-bilbao);
            margin-bottom: 4px;
        }

        .director-details {
            opacity: 0;
            max-height: 0;
            overflow: hidden;
            transform: translateY(10px);
            transition: all 0.4s ease;
        }

        .director-role {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--col-espiritu);
            margin-bottom: var(--sp-sm);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 8px;
        }

        .director-desc {
            font-size: 0.95rem;
            color: var(--col-texto);
            line-height: 1.5;
        }

        .director-card:hover .director-img-wrapper { transform: scale(1.08); }
        .director-card:hover .director-details { opacity: 1; max-height: 200px; transform: translateY(0); }


        /* SECCIONES ALTERNADAS (DOCENTES) */
        .content-block { margin-bottom: var(--sp-xxl); }
        
        #docentes {
            background: linear-gradient(135deg, var(--col-bilbao) 0%, var(--col-espiritu) 100%);
            color: var(--col-blanco);
            border-radius: 24px;
            padding: var(--sp-xl) var(--sp-md);
            box-shadow: 0 15px 35px rgba(77, 138, 187, 0.25);
        }

        #docentes .section-title { 
            color: var(--col-blanco);
            text-align: center;
            display: block; 
            width: 100%;
            font-size: 2.5rem; 
            margin-bottom: var(--sp-xl);
        }
        #docentes .section-title::after { display: none; }
        #docentes .content-text p { color: rgba(255, 255, 255, 0.95); text-align: justify; font-weight: 400; }

        .content-grid { display: flex; flex-direction: column; gap: var(--sp-xl); align-items: center; }
        .content-img { order: 1; width: 100%; }
        .content-text { order: 2; width: 100%; }
        .content-img img { border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.15); width: 100%; }
        .content-text p { margin-bottom: var(--sp-md); font-size: 1.05rem; font-weight: 400; }

        @media (min-width: 900px) {
            #docentes { padding: var(--sp-xxl) var(--sp-xl); } 
            .content-grid { display: grid; grid-template-columns: 1fr 1fr; align-items: center; }
            .content-img, .content-text { order: unset; width: auto; }
            .content-grid.reverse .content-img { order: 2; }
            .content-grid.reverse .content-text { order: 1; }
        }

        /* FAQ (Apple Style) */
        #preguntas .section-title { text-align: center; display: block; width: 100%; }
        #preguntas .section-title::after { margin: 8px auto 0 auto; }

        .faq-list { max-width: 800px; margin: 0 auto; }
        .faq-item { border-bottom: 1px solid #E5E5E5; }
        .faq-item:last-child { border-bottom: none; }

        .faq-question {
            width: 100%; text-align: left; padding: 24px 0;
            background: none; border: none; font-size: 1.2rem; font-weight: 500;
            color: var(--col-herencia); display: flex; justify-content: space-between; align-items: center;
            cursor: pointer; transition: color 0.2s ease;
        }
        .faq-question:hover { color: var(--col-bilbao); }

        .faq-icon-wrapper {
            width: 32px; height: 32px; border-radius: 50%; background-color: #F0F4F8;
            display: flex; align-items: center; justify-content: center;
            transition: all 0.3s ease; flex-shrink: 0; margin-left: 16px;
        }
        .faq-question:hover .faq-icon-wrapper { background-color: var(--col-bilbao); }
        .faq-icon-wrapper svg { width: 14px; height: 14px; fill: var(--col-herencia); transition: transform 0.3s ease, fill 0.3s ease; }
        .faq-question:hover .faq-icon-wrapper svg { fill: white; }
        .faq-question[aria-expanded="true"] .faq-icon-wrapper { background-color: var(--col-bilbao); transform: rotate(180deg); }
        .faq-question[aria-expanded="true"] .faq-icon-wrapper svg { fill: white; }

        .faq-answer {
            max-height: 0; overflow: hidden; transition: max-height 0.4s cubic-bezier(0.25, 1, 0.5, 1);
            color: var(--col-texto); font-size: 1.05rem; line-height: 1.6; opacity: 0; font-weight: 400;
        }
        .faq-question[aria-expanded="true"] + .faq-answer { opacity: 1; padding-bottom: 24px; }

        /* CTA */
        .cta-section { padding: var(--sp-xxl) var(--sp-md); background-color: white; border-top: 1px solid var(--col-borde); }
        .cta-container { 
            max-width: 900px; margin: 0 auto; 
            display: flex; flex-direction: row; 
            align-items: center; gap: var(--sp-md); text-align: left;
        }
        .mascot-img { 
            width: 100px; height: auto; flex-shrink: 0; transition: transform 0.3s ease; 
        }
        .mascot-img:hover { transform: scale(1.05) rotate(3deg); }
        
        .cta-content { flex-grow: 1; }
        .cta-content h2 { font-size: 1.5rem; color: var(--col-bilbao); margin-bottom: var(--sp-xs); }
        .cta-content p { text-align: left; font-size: 1rem; color: var(--col-texto); margin-bottom: var(--sp-md); font-weight: 400; }

        @media (min-width: 768px) {
            .cta-container { gap: var(--sp-lg); }
            .mascot-img { width: 200px; }
            .cta-content h2 { font-size: 2rem; margin-bottom: var(--sp-md); }
            .cta-content p { font-size: 1.1rem; }
        }
        .btn-primary { display: inline-block; background-color: var(--col-bilbao); color: white; padding: 14px 28px; border-radius: 50px; font-weight: 600; font-size: 1rem; transition: background 0.3s, transform 0.2s; box-shadow: 0 8px 20px rgba(77, 138, 187, 0.3); }
        .btn-primary:hover { background-color: var(--col-espiritu); transform: translateY(-2px); }

        /* FOOTER */
        footer { background-color: var(--bg-global); border-top: 1px solid var(--col-borde); padding-top: var(--sp-xl); font-size: 0.95rem; color: var(--col-herencia); }
        .footer-container { width: 100%; max-width: var(--max-width); margin: 0 auto; padding: 0 var(--sp-md); }
        .footer-header { display: flex; flex-direction: column; margin-bottom: var(--sp-lg); align-items: flex-start; }
        .footer-logo-link { display: inline-block; margin-bottom: var(--sp-sm); }
        .footer-logo-img { height: 77px; width: auto; object-fit: contain; display: block; }
        .footer-social-desktop { display: none; }
        .social-link { display: inline-flex; align-items: center; justify-content: center; color: var(--col-herencia); transition: color 0.2s ease, transform 0.2s ease; width: 36px; height: 36px; }
        .social-link:hover { color: var(--col-bilbao); transform: translateY(-2px); }
        .social-icon { width: 20px; height: 20px; fill: currentColor; }
        .footer-grid { display: grid; gap: var(--sp-lg); grid-template-columns: 1fr; }
        .footer-desc { margin-bottom: var(--sp-md); font-size: 0.9rem; line-height: 1.6; text-align: left; font-weight: 400; }
        .footer-contact p { margin-bottom: var(--sp-xs); text-align: left; font-weight: 400; }
        .footer-contact a { font-weight: 600; color: var(--col-bilbao); }
        .footer-social-mobile { margin-top: var(--sp-md); margin-bottom: var(--sp-lg); display: flex; gap: var(--sp-xs); align-items: center; flex-wrap: wrap; }
        .footer-col-title { font-weight: 700; color: var(--col-bilbao); margin-bottom: var(--sp-sm); display: flex; justify-content: space-between; align-items: center; width: 100%; }
        .footer-links { display: none; margin-bottom: var(--sp-md); }
        .footer-links li { margin-bottom: var(--sp-xs); }
        .footer-links a:hover { color: var(--col-espiritu); text-decoration: underline; }
        .footer-legal { margin-top: var(--sp-xl); padding: var(--sp-md) 0; border-top: 1px solid var(--col-borde); font-size: 0.8rem; display: flex; flex-direction: column; gap: var(--sp-sm); text-align: center; opacity: 0.8; }
        .legal-links { display: flex; flex-wrap: wrap; justify-content: center; gap: var(--sp-sm); }
        .visible { display: block !important; }
        .rotate { transform: rotate(180deg); }
        @media (min-width: 600px) { .footer-grid { grid-template-columns: 1fr 1fr; } .footer-identity { grid-column: span 2; } }
        @media (min-width: 1024px) { 
            .footer-header { flex-direction: row; justify-content: space-between; align-items: center; }
            .footer-social-desktop { display: flex; align-items: center; gap: var(--sp-xs); }
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

    <!-- Skip Link -->
    <a href="#main-content" class="skip-link">Saltar al contenido principal</a>

    <!-- HEADER -->
    <header class="header-bar">
        <div class="header-inner">
            <a href="/" class="logo-link" aria-label="Ir al inicio de Colegio Bilbao">
                <img src="../../assets/img/global/logo-bilbao-horizontal-azul.png" alt="Colegio Bilbao" class="logo-img" width="216" height="67">
            </a>
            <div class="header-controls">
                <div class="lang-switch"><span class="active">ES</span> | <a href="/en/">EN</a></div>
                <button class="menu-trigger" aria-controls="menu-overlay" aria-expanded="false" aria-label="Abrir menú">
                    <div class="hamburger-icon" aria-hidden="true"><span></span><span></span><span></span></div>
                </button>
            </div>
        </div>
    </header>

    <!-- OVERLAY MENU -->
    <div id="menu-overlay" class="menu-overlay" role="dialog" aria-modal="true" aria-hidden="true" aria-label="Menú principal">
        <div class="overlay-header">
            <div class="header-inner">
                <a href="/" class="logo-link" tabindex="-1">
                     <img src="../../assets/img/global/logo-bilbao-horizontal-azul.png" alt="Colegio Bilbao" class="logo-img" width="216" height="67">
                </a>
                <div class="header-controls">
                    <div class="lang-switch"><span class="active">ES</span> | <a href="/en/">EN</a></div>
                    <button id="close-menu-btn" class="close-btn" aria-label="Cerrar menú"></button>
                </div>
            </div>
        </div>
        <nav class="overlay-content">
            <ul id="primary-nav">
                <li class="nav-accordion-item"><button class="nav-accordion-trigger" aria-expanded="false">Conócenos <span class="chevron">▼</span></button>
                    <ul class="nav-submenu">
                        <li><a href="/conocenos/quienes-somos/">Quiénes somos</a></li>
                        <li><a href="/conocenos/equipo-educativo/">Equipo educativo</a></li>
                        <li><a href="/conocenos/instalaciones/">Instalaciones</a></li>
                        <li><a href="/conocenos/certificaciones-y-reconocimientos/">Certificaciones</a></li>
                    </ul>
                </li>
                <li class="nav-accordion-item"><button class="nav-accordion-trigger" aria-expanded="false">Modelo educativo <span class="chevron">▼</span></button>
                    <ul class="nav-submenu">
                        <li><a href="/modelo-educativo/modelo-vida/">Modelo VIDA</a></li>
                        <li><a href="/modelo-educativo/filosofia-y-metodologia/">Filosofía</a></li>
                        <li><a href="/modelo-educativo/aprendizaje-integral/">Aprendizaje integral</a></li>
                        <li><a href="/modelo-educativo/idiomas/">Idiomas</a></li>
                    </ul>
                </li>
                <li class="nav-accordion-item"><button class="nav-accordion-trigger" aria-expanded="false">Niveles académicos <span class="chevron">▼</span></button>
                    <ul class="nav-submenu">
                        <li><a href="/niveles-academicos/preescolar/">Preescolar</a></li>
                        <li><a href="/niveles-academicos/primaria/">Primaria</a></li>
                        <li><a href="/niveles-academicos/secundaria/">Secundaria</a></li>
                        <li><a href="/niveles-academicos/preparatoria/">Preparatoria</a></li>
                    </ul>
                </li>
                <li class="nav-accordion-item"><button class="nav-accordion-trigger" aria-expanded="false">Vida escolar <span class="chevron">▼</span></button>
                    <ul class="nav-submenu">
                        <li><a href="/vida-escolar/afterschool-extracurriculares/">Afterschool</a></li>
                        <li><a href="/vida-escolar/futuro-universitario-becas/">Futuro universitario</a></li>
                        <li><a href="/vida-escolar/programa-dual/">Programa Dual</a></li>
                        <li><a href="/vida-escolar/servicios-para-familias/">Servicios</a></li>
                        <li><a href="/vida-escolar/cuidado-y-bienestar/">Cuidado y bienestar</a></li>
                        <li><a href="/vida-escolar/eventos-y-tradiciones/">Eventos</a></li>
                    </ul>
                </li>
                <li class="nav-accordion-item"><button class="nav-accordion-trigger" aria-expanded="false">Admisiones <span class="chevron">▼</span></button>
                    <ul class="nav-submenu">
                        <li><a href="/admisiones/">Inicio</a></li>
                        <li><a href="/admisiones/proceso/">Proceso</a></li>
                        <li><a href="/admisiones/preguntas-frecuentes/">FAQ</a></li>
                        <li><a href="/admisiones/convenios/">Convenios</a></li>
                        <li><a href="/admisiones/convocatoria-becas/">Becas</a></li>
                        <li><a href="/admisiones/contacto/">Contacto</a></li>
                    </ul>
                </li>
                <li class="nav-accordion-item"><button class="nav-accordion-trigger" aria-expanded="false">Comunidad <span class="chevron">▼</span></button>
                    <ul class="nav-submenu">
                        <li><a href="/comunidad/estudiantes/">Estudiantes</a></li>
                        <li><a href="/comunidad/familias/">Familias</a></li>
                        <li><a href="/comunidad/exalumnos/">Exalumnos</a></li>
                    </ul>
                </li>
                <li class="nav-accordion-item"><button class="nav-accordion-trigger" aria-expanded="false">Voces Bilbao <span class="chevron">▼</span></button>
                    <ul class="nav-submenu">
                        <li><a href="/voces-bilbao/noticias/">Noticias</a></li>
                        <li><a href="/voces-bilbao/entrevistas/">Entrevistas</a></li>
                        <li><a href="/voces-bilbao/articulos/">Artículos</a></li>
                        <li><a href="/voces-bilbao/testimonios/">Testimonios</a></li>
                    </ul>
                </li>
                <li class="nav-accordion-item"><button class="nav-accordion-trigger" aria-expanded="false">Contacto <span class="chevron">▼</span></button>
                    <ul class="nav-submenu">
                        <li><a href="/contacto/">Contacto</a></li>
                        <li><a href="/contacto/directorio/">Directorio</a></li>
                        <li><a href="/contacto/cultura-y-talento/">Cultura y talento</a></li>
                        <li><a href="/contacto/proveedores/">Proveedores</a></li>
                    </ul>
                </li>
            </ul>
        </nav>
    </div>

    <!-- MAIN CONTENT -->
    <main id="main-content">
        
        <!-- HERO SECTION -->
        <section class="page-hero">
            <div class="hero-content">
                <h1>Equipo educativo</h1>
                <p>Un equipo educativo que cuida tanto el aprendizaje como la experiencia de cada estudiante.</p>
            </div>
        </section>

        <!-- MINI MENÚ DE PÁGINA -->
        <div class="page-nav-wrapper">
            <nav class="page-nav" aria-label="Secciones de la página">
                <a href="#resumen" class="page-nav-link">Resumen</a>
                <a href="#direcciones" class="page-nav-link">Directivos</a>
                <a href="#docentes" class="page-nav-link">Docentes</a>
                <a href="#formacion" class="page-nav-link">Formación</a>
                <a href="#preguntas" class="page-nav-link">Preguntas</a>
            </nav>
        </div>

        <!-- RESUMEN BREVE -->
        <section id="resumen" class="summary-full-width">
            <div class="summary-content-wrapper">
                <p class="summary-lead">
                    En el Colegio Bilbao, el equipo educativo es mucho más que una lista de nombres.
                    Es una comunidad profesional que escucha, acompaña y toma decisiones pensando en las personas.
                </p>
                <p class="summary-body">
                    <strong>Directivos, coordinaciones, docentes y personal de apoyo comparten una misma visión educativa y un compromiso profundo con la comunidad.</strong>
                </p>
            </div>
        </section>

        <!-- DIRECCIONES Y LIDERAZGO -->
        <section id="direcciones" class="section-container">
            
            <div class="directors-header-clean">
                <h2 class="dh-title-clean">Direcciones y liderazgo institucional</h2>
                <div class="dh-separator"></div>
                <div class="dh-intro-text">
                    <p>Las direcciones del Colegio Bilbao sostienen la visión del proyecto y cuidan que se traduzca en decisiones concretas. Su liderazgo combina claridad académica, acompañamiento humano y responsabilidad frente a las familias y la comunidad. <strong>Estas son las personas que actualmente encabezan cada dirección del colegio.</strong></p>
                </div>
            </div>

            <!-- GRID DE DIRECTIVOS -->
            <div class="directors-grid">
                
                <!-- 1. Nieves (Preescolar) -->
                <div class="director-card">
                    <div class="director-img-wrapper">
                        <img src="../../assets/img/conocenos/equipo-educativo/nieves.png" alt="Nieves Mandujano" class="director-img">
                    </div>
                    <h3 class="director-name">Nieves Mandujano</h3>
                    <div class="director-details">
                        <p class="director-role">Directora de Preescolar</p>
                        <p class="director-desc">Cuida los primeros vínculos con la escuela y el desarrollo emocional y social temprano.</p>
                    </div>
                </div>

                <!-- 2. Sasha (General) -->
                <div class="director-card general">
                    <div class="director-img-wrapper">
                        <img src="../../assets/img/conocenos/equipo-educativo/sasha.png" alt="Sasha Klainer" class="director-img">
                    </div>
                    <h3 class="director-name">Sasha Klainer</h3>
                    <div class="director-details">
                        <p class="director-role">Director General</p>
                        <p class="director-desc">Acompaña la visión integral del colegio y asegura coherencia entre identidad, decisiones académicas y vida comunitaria.</p>
                    </div>
                </div>

                <!-- 3. Georgina (Primaria) -->
                <div class="director-card">
                    <div class="director-img-wrapper">
                        <img src="../../assets/img/conocenos/equipo-educativo/gina.png" alt="Georgina Lopez" class="director-img">
                    </div>
                    <h3 class="director-name">Georgina Lopez</h3>
                    <div class="director-details">
                        <p class="director-role">Directora de Primaria</p>
                        <p class="director-desc">Construye bases sólidas en lectura, escritura, pensamiento lógico y hábitos de estudio saludables.</p>
                    </div>
                </div>

                <!-- 4. Pablo (Secundaria) -->
                <div class="director-card">
                    <div class="director-img-wrapper">
                        <img src="../../assets/img/conocenos/equipo-educativo/pablo.png" alt="Pablo Medina" class="director-img">
                    </div>
                    <h3 class="director-name">Pablo Medina</h3>
                    <div class="director-details">
                        <p class="director-role">Director de Secundaria</p>
                        <p class="director-desc">Acompaña la adolescencia, fortalece el pensamiento crítico y orienta procesos de convivencia responsable.</p>
                    </div>
                </div>

                <!-- 5. Eduardo (Preparatoria) -->
                <div class="director-card">
                    <div class="director-img-wrapper">
                        <img src="../../assets/img/conocenos/equipo-educativo/lalo.png" alt="Eduardo Maldonado" class="director-img">
                    </div>
                    <h3 class="director-name">Eduardo Maldonado</h3>
                    <div class="director-details">
                        <p class="director-role">Director de Preparatoria</p>
                        <p class="director-desc">Prepara a las y los estudiantes para la vida universitaria y decisiones de futuro.</p>
                    </div>
                </div>

            </div>
        </section>

        <!-- DOCENTES CON ENFOQUE HUMANO -->
        <section id="docentes" class="section-container">
            <div id="docentes-block">
                <h2 class="section-title">Docentes con enfoque humano</h2>
                <div class="content-grid">
                    <div class="content-img">
                        <img src="../../assets/img/conocenos/equipo-educativo/relacion-maestro-alumno.jpg" alt="Docente acompañando a estudiante">
                    </div>
                    <div class="content-text">
                        <p>Las maestras y maestros del Bilbao son el rostro visible del proyecto educativo.</p>
                        <p>Están frente a grupo, pero también a un lado, acompañando emociones, dudas y descubrimientos.</p>
                        <p>En clase se trabaja con metodologías activas, proyectos y experiencias significativas. El objetivo es que el aprendizaje tenga sentido para quienes lo viven cada día.</p>
                        <p>El profesorado conoce a sus estudiantes por nombre, historia y forma particular de aprender. Esa cercanía permite ajustar estrategias y acompañar con mayor precisión.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- FORMACIÓN CONTINUA -->
        <section id="formacion" class="section-container content-block">
            <h2 class="section-title">Formación continua y trabajo colaborativo</h2>
            <div class="content-grid reverse">
                <div class="content-img">
                    <img src="../../assets/img/conocenos/equipo-educativo/maestro-escucha.jpg" alt="Equipo docente en formación">
                </div>
                <div class="content-text">
                    <p>El equipo educativo se forma de manera constante para responder a los retos de cada generación.</p>
                    <p>Participa en cursos, espacios de actualización y reflexiones internas sobre la práctica docente.</p>
                    <p>Las reuniones de trabajo no solo revisan pendientes, también permiten analizar casos y compartir estrategias. Nos interesa que nadie se sienta educando en soledad dentro del aula.</p>
                </div>
            </div>
        </section>

        <!-- PREGUNTAS FRECUENTES -->
        <section id="preguntas" class="section-container">
            <h2 class="section-title">Preguntas frecuentes</h2>
            <div class="faq-list">
                <div class="faq-item">
                    <button class="faq-question" aria-expanded="false">
                        ¿Qué perfil profesional buscan en el equipo docente?
                        <span class="faq-icon-wrapper">
                            <svg viewBox="0 0 24 24"><path d="M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6-6-6 1.41-1.41z"/></svg>
                        </span>
                    </button>
                    <div class="faq-answer">
                        <p>Buscamos docentes con formación sólida, experiencia en aula y una clara vocación por acompañar personas, no solo impartir contenidos.</p>
                    </div>
                </div>
                <div class="faq-item">
                    <button class="faq-question" aria-expanded="false">
                        ¿Cómo cuidan el bienestar del equipo educativo?
                        <span class="faq-icon-wrapper">
                            <svg viewBox="0 0 24 24"><path d="M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6-6-6 1.41-1.41z"/></svg>
                        </span>
                    </button>
                    <div class="faq-answer">
                        <p>Fomentamos espacios de diálogo, acompañamiento entre pares, formación en autocuidado y una cultura donde pedir apoyo sea bienvenido.</p>
                    </div>
                </div>
                <div class="faq-item">
                    <button class="faq-question" aria-expanded="false">
                        ¿Las familias tienen espacios formales para dialogar?
                        <span class="faq-icon-wrapper">
                            <svg viewBox="0 0 24 24"><path d="M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6-6-6 1.41-1.41z"/></svg>
                        </span>
                    </button>
                    <div class="faq-answer">
                        <p>Sí, se organizan reuniones, entrevistas y espacios de retroalimentación programados durante el ciclo escolar. Buscamos que estos encuentros sean claros, respetuosos y centrados siempre en el bienestar del estudiante.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA FINAL -->
        <section class="cta-section">
            <div class="cta-container">
                <!-- Mascota actualizada -->
                <img src="../../assets/img/conocenos/equipo-educativo/alex-bby-show.png" alt="Mascota Alex del Colegio Bilbao" class="mascot-img">
                <div class="cta-content">
                    <h2>¿Te gustaría conocer más?</h2>
                    <p>Si quieres conocer más sobre cómo trabajamos en las aulas, puedes dar el siguiente paso. Conoce nuestro modelo educativo y visualiza cómo se traduce en la experiencia diaria de tus hijas e hijos.</p>
                    <a href="/modelo-educativo/modelo-vida/" class="btn-primary">Conoce nuestro modelo educativo</a>
                </div>
            </div>
        </section>

    </main>

    <!-- FOOTER -->
    <footer>
        <div class="footer-container">
            <div class="footer-header">
                <a href="/" class="footer-logo-link" aria-label="Ir al inicio de Colegio Bilbao">
                    <img src="../../assets/img/global/logo-bilbao-horizontal-azul.png" alt="Colegio Bilbao" class="footer-logo-img">
                </a>
                <div class="footer-social-desktop">
                    <span style="margin-right: 8px;">Síguenos</span>
                    <a href="https://www.facebook.com/bilbaocolegio/" target="_blank" rel="noopener noreferrer" class="social-link" aria-label="Facebook"><svg class="social-icon" viewBox="0 0 24 24"><path d="M14 13.5h2.5l1-4H14v-2c0-1.03 0-2 2-2h1.5V1.74c-.75-.07-2.47-.24-4.75-.24-4.8 0-8 2.9-8 8v3h-5v4h5v11.5h7V13.5z"/></svg></a>
                    <a href="https://www.instagram.com/bilbaocolegio/" target="_blank" rel="noopener noreferrer" class="social-link" aria-label="Instagram"><svg class="social-icon" viewBox="0 0 24 24"><path d="M12 2.16c3.2 0 3.58.01 4.85.07 3.25.15 4.77 1.69 4.92 4.92.06 1.27.07 1.65.07 4.85 0 3.2-.01 3.58-.07 4.85-.15 3.23-1.66 4.77-4.92 4.92-1.27.06-1.64.07-4.85.07-3.2 0-3.58-.01-4.85-.07-3.23-.15-4.77-1.66-4.92-4.92-.06-1.27-.07-1.64-.07-4.85 0-3.2.01-3.58.07-4.85.15-3.23 1.66-4.77 4.92-4.92 1.27-.06 1.65-.07 4.85-.07M12 0C8.74 0 8.33.01 7.05.07 2.7.27.27 2.7.07 7.05.01 8.33 0 8.74 0 12s.01 3.67.07 4.95c.2 4.36 2.62 6.78 6.98 6.98 1.28.06 1.69.07 4.95.07s3.67-.01 4.95-.07c4.36-.2 6.78-2.62 6.98-6.98.06-1.28.07-1.69.07-4.95s-.01-3.67-.07-4.95C23.73 2.7 21.31.27 16.95.07 15.67.01 15.26 0 12 0zm0 5.84c-3.4 0-6.16 2.76-6.16 6.16 0 3.4 2.76 6.16 6.16 6.16 3.4 0 6.16-2.76 6.16-6.16 0-3.4-2.76-6.16-6.16-6.16zm0 10.16c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4zm6.4-10.8c0 .66-.54 1.2-1.2 1.2-.66 0-1.2-.54-1.2-1.2 0-.66.54-1.2 1.2-1.2.66 0 1.2.54 1.2 1.2z"/></svg></a>
                    <a href="https://www.instagram.com/bilbaomoments/" target="_blank" rel="noopener noreferrer" class="social-link" aria-label="Instagram Secundaria"><svg class="social-icon" viewBox="0 0 24 24"><path d="M12 2.16c3.2 0 3.58.01 4.85.07 3.25.15 4.77 1.69 4.92 4.92.06 1.27.07 1.65.07 4.85 0 3.2-.01 3.58-.07 4.85-.15 3.23-1.66 4.77-4.92 4.92-1.27.06-1.64.07-4.85.07-3.2 0-3.58-.01-4.85-.07-3.23-.15-4.77-1.66-4.92-4.92-.06-1.27-.07-1.64-.07-4.85 0-3.2.01-3.58.07-4.85.15-3.23 1.66-4.77 4.92-4.92 1.27-.06 1.65-.07 4.85-.07M12 0C8.74 0 8.33.01 7.05.07 2.7.27.27 2.7.07 7.05.01 8.33 0 8.74 0 12s.01 3.67.07 4.95c.2 4.36 2.62 6.78 6.98 6.98 1.28.06 1.69.07 4.95.07s3.67-.01 4.95-.07c4.36-.2 6.78-2.62 6.98-6.98.06-1.28.07-1.69.07-4.95s-.01-3.67-.07-4.95C23.73 2.7 21.31.27 16.95.07 15.67.01 15.26 0 12 0zm0 5.84c-3.4 0-6.16 2.76-6.16 6.16 0 3.4 2.76 6.16 6.16 6.16 3.4 0 6.16-2.76 6.16-6.16 0-3.4-2.76-6.16-6.16-6.16zm0 10.16c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4zm6.4-10.8c0 .66-.54 1.2-1.2 1.2-.66 0-1.2-.54-1.2-1.2 0-.66.54-1.2 1.2-1.2.66 0 1.2.54 1.2 1.2z"/></svg></a>
                    <a href="https://www.youtube.com/@ColegioBilbaoOficial" target="_blank" rel="noopener noreferrer" class="social-link" aria-label="YouTube"><svg class="social-icon" viewBox="0 0 24 24"><path d="M19.615 3.184c-3.604-.246-11.631-.245-15.23 0-3.897.266-4.356 2.62-4.385 8.816.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0 3.897-.266 4.356-2.62 4.385-8.816-.029-6.185-.484-8.549-4.385-8.816zm-10.615 12.816v-8l8 3.993-8 4.007z"/></svg></a>
                    <a href="https://mx.linkedin.com/company/colegio-bilbao" target="_blank" rel="noopener noreferrer" class="social-link" aria-label="LinkedIn"><svg class="social-icon" viewBox="0 0 24 24"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/></svg></a>
                    <span style="margin: 0 16px;">|</span>
                    <div class="lang-switch" style="display: inline-block;"><span class="active">ES</span> | <a href="/en/">EN</a></div>
                </div>
            </div>
            
            <div class="footer-grid">
                <div class="footer-identity">
                    <p class="footer-desc">Escuela privada K-12 en la zona poniente de la Ciudad de México.</p>
                    <div class="footer-contact">
                        <p><strong>Dirección:</strong><br>Tlalmimilolpan 39, San Mateo Tlaltenango,<br>Cuajimalpa de Morelos, 05600 Ciudad de México, CDMX</p>
                        <p style="margin-top:12px"><strong>Teléfonos:</strong></p>
                        <p>Conmutador: <a href="tel:+5558101346">55 5810 1346</a></p>
                        <p>Admisiones:<br> <a href="tel:+525549839745">+52 55 4983 9745</a><br><a href="tel:+525614612682">+52 56 1461 2682</a></p>
                        <p style="margin-top:12px"><a href="/contacto/">Ver ubicación y mapa →</a></p>
                    </div>
                    <div class="footer-social-mobile">
                        <span>Síguenos:</span>
                        <!-- Mobile Socials Same as Desktop -->
                        <a href="https://www.facebook.com/bilbaocolegio/" class="social-link"><svg class="social-icon" viewBox="0 0 24 24"><path d="M14 13.5h2.5l1-4H14v-2c0-1.03 0-2 2-2h1.5V1.74c-.75-.07-2.47-.24-4.75-.24-4.8 0-8 2.9-8 8v3h-5v4h5v11.5h7V13.5z"/></svg></a>
                        <a href="https://www.instagram.com/bilbaocolegio/" class="social-link"><svg class="social-icon" viewBox="0 0 24 24"><path d="M12 2.16c3.2 0 3.58.01 4.85.07 3.25.15 4.77 1.69 4.92 4.92.06 1.27.07 1.64.07 4.85 0 3.2-.01 3.58-.07 4.85-.15 3.23-1.66 4.77-4.92 4.92-1.27.06-1.64.07-4.85.07-3.2 0-3.58-.01-4.85-.07-3.23-.15-4.77-1.66-4.92-4.92-.06-1.27-.07-1.64-.07-4.85 0-3.2.01-3.58.07-4.85.15-3.23 1.66-4.77 4.92-4.92 1.27-.06 1.65-.07 4.85-.07M12 0C8.74 0 8.33.01 7.05.07 2.7.27.27 2.7.07 7.05.01 8.33 0 8.74 0 12s.01 3.67.07 4.95c.2 4.36 2.62 6.78 6.98 6.98 1.28.06 1.69.07 4.95.07s3.67-.01 4.95-.07c4.36-.2 6.78-2.62 6.98-6.98.06-1.28.07-1.69.07-4.95s-.01-3.67-.07-4.95C23.73 2.7 21.31.27 16.95.07 15.67.01 15.26 0 12 0zm0 5.84c-3.4 0-6.16 2.76-6.16 6.16 0 3.4 2.76 6.16 6.16 6.16 3.4 0 6.16-2.76 6.16-6.16 0-3.4-2.76-6.16-6.16-6.16zm0 10.16c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4zm6.4-10.8c0 .66-.54 1.2-1.2 1.2-.66 0-1.2-.54-1.2-1.2 0-.66.54-1.2 1.2-1.2.66 0 1.2.54 1.2 1.2z"/></svg></a>
                        <a href="https://www.youtube.com/@ColegioBilbaoOficial" class="social-link"><svg class="social-icon" viewBox="0 0 24 24"><path d="M19.615 3.184c-3.604-.246-11.631-.245-15.23 0-3.897.266-4.356 2.62-4.385 8.816.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0 3.897-.266 4.356-2.62 4.385-8.816-.029-6.185-.484-8.549-4.385-8.816zm-10.615 12.816v-8l8 3.993-8 4.007z"/></svg></a>
                        <a href="https://mx.linkedin.com/company/colegio-bilbao" class="social-link"><svg class="social-icon" viewBox="0 0 24 24"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/></svg></a>
                    </div>
                </div>
                <div class="footer-col">
                    <button class="footer-col-title" aria-expanded="false">Conócenos <span class="chevron">▼</span></button>
                    <ul class="footer-links">
                        <li><a href="/conocenos/quienes-somos/">Quiénes somos</a></li>
                        <li><a href="/conocenos/equipo-educativo/">Equipo educativo</a></li>
                        <li><a href="/conocenos/instalaciones/">Instalaciones</a></li>
                        <li><a href="/modelo-educativo/modelo-vida/">Modelo VIDA</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <button class="footer-col-title" aria-expanded="false">Niveles <span class="chevron">▼</span></button>
                    <ul class="footer-links">
                        <li><a href="/niveles-academicos/preescolar/">Preescolar</a></li>
                        <li><a href="/niveles-academicos/primaria/">Primaria</a></li>
                        <li><a href="/niveles-academicos/secundaria/">Secundaria</a></li>
                        <li><a href="/niveles-academicos/preparatoria/">Preparatoria</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <button class="footer-col-title" aria-expanded="false">Comunidad <span class="chevron">▼</span></button>
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
        document.addEventListener('keydown', (e) => { if(e.key === 'Escape') toggleMenu(false); });

        navAccordions.forEach(trigger => {
            trigger.addEventListener('click', () => {
                const submenu = trigger.nextElementSibling;
                if(!submenu) return;
                const isExpanded = trigger.getAttribute('aria-expanded') === 'true';
                trigger.setAttribute('aria-expanded', !isExpanded);
                submenu.style.display = isExpanded ? 'none' : 'block';
            });
        });

        footerToggles.forEach(toggle => {
            toggle.addEventListener('click', () => {
                if (window.innerWidth >= 1024) return;
                const isExpanded = toggle.getAttribute('aria-expanded') === 'true';
                const content = toggle.nextElementSibling;
                const chevron = toggle.querySelector('.chevron');
                toggle.setAttribute('aria-expanded', !isExpanded);
                if (!isExpanded) { content.classList.add('visible'); chevron.style.transform = 'rotate(180deg)'; }
                else { content.classList.remove('visible'); chevron.style.transform = 'rotate(0deg)'; }
            });
        });

        window.addEventListener('resize', () => {
            if (window.innerWidth >= 1024) {
                document.querySelectorAll('.footer-links').forEach(el => el.classList.remove('visible'));
                document.querySelectorAll('.footer-col-title .chevron').forEach(el => el.style.transform = '');
            }
        });
    </script>

<style>
    /* Solo aplica en pantallas menores a 1024px (Móviles y Tablets) */
    @media (max-width: 1024px) {
        
        /* Efecto de Escala (Zoom In) */
        .photo-card.mobile-active img,
        .level-card.mobile-active .level-bg,
        .bento-card.mobile-active .bento-img,
        .news-item.mobile-active .news-img,
        .carousel-slide.mobile-active img,
        .organic-img-wrapper.mobile-active img,
        .explore-card.mobile-active .explore-bg,
        .manifesto-img.mobile-active img { 
            transform: scale(1.1); 
        }
        
        /* Efecto de Brillo/Filtro */
        .level-card.mobile-active .level-bg { filter: brightness(0.6); }

        /* Efecto de Aparición de Texto/Overlay */
        .photo-card.mobile-active .card-caption,
        .level-card.mobile-active .level-desc,
        .level-card.mobile-active .level-link,
        .carousel-slide.mobile-active .carousel-overlay { 
            opacity: 1; transform: translateY(0); 
            max-height: 200px; /* Para descripciones que se expanden */
        }
        
        /* Ajuste de título rotado en Home */
        .level-card.mobile-active .level-title { transform: rotate(0deg); } 

        /* Efecto de Elevación (Y-axis) */
        .bento-card.mobile-active,
        .news-item.mobile-active,
        .social-explore-card.mobile-active,
        .comp-card.mobile-active,
        .step-item.mobile-active,
        .explore-card.mobile-active {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1); 
        }

        /* Efecto Específico Mascotas (Rotación divertida) */
        .mascot-img.mobile-active, 
        .mascot-intro.mobile-active,
        .mascot-happy.mobile-active,
        .mascot-cta.mobile-active { 
            transform: scale(1.1) rotate(5deg); 
        }

        /* Bordes activos */
        .social-explore-card.mobile-active,
        .step-item.mobile-active { 
            border-color: var(--col-espiritu); 
        }
    }
</style>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Solo ejecutar lógica en móviles/tablets
        if (window.innerWidth <= 1024) {
            
            // LISTA MAESTRA DE CLASES A ANIMAR
            // Aquí están incluidas las clases del Home, Admisiones y Niveles
            const selectors = [
                '.photo-card', '.level-card', '.bento-card', '.news-item', 
                '.carousel-slide', '.mascot-img', '.mascot-intro', '.mascot-happy', '.mascot-cta',
                '.organic-img-wrapper', '.social-explore-card', '.comp-card', 
                '.step-item', '.explore-card', '.manifesto-img'
            ];

            const hoverElements = document.querySelectorAll(selectors.join(', '));

            const observerOptions = {
                root: null,
                // El margen negativo hace que el efecto se active justo en el centro de la pantalla
                // y se desactive si el elemento sale mucho.
                rootMargin: '-15% 0px -15% 0px', 
                threshold: 0.2
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('mobile-active');
                    } else {
                        // Quitar el comentario de abajo si quieres que el efecto se repita cada vez que haces scroll
                        // entry.target.classList.remove('mobile-active');
                    }
                });
            }, observerOptions);

            hoverElements.forEach(el => observer.observe(el));
        }
    });
</script>

</body>
</html>