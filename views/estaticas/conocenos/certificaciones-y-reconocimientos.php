<!DOCTYPE html>
<html lang="es-MX">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Conoce las acreditaciones, certificaciones y reconocimientos del Colegio Bilbao: validez SEP, CENDES y convenios universitarios.">
    <meta name="robots" content="index, follow">
    
    <title>Certificaciones y Reconocimientos | Colegio Bilbao</title>

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
            
            --sp-xs: 8px; --sp-sm: 16px; --sp-md: 24px; --sp-lg: 32px; --sp-xl: 48px; --sp-xxl: 96px;
            --font-main: 'Montserrat', sans-serif;
            --max-width: 1280px;
            --header-height: 90px;
            
            --bg-secondary: #F5F5F7; 
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        
        body {
            font-family: var(--font-main);
            background-color: var(--col-blanco);
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
        .text-link { color: var(--col-bilbao); font-weight: 600; border-bottom: 2px solid var(--col-espiritu); transition: all 0.2s; }
        .text-link:hover { color: var(--col-herencia); border-bottom-color: var(--col-bilbao); }

        /* --- HEADER & MENU STYLES (Global) --- */
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

        /* 1. HERO INMERSIVO (RESTAURADO CON IMAGEN) */
        .page-hero {
            position: relative;
            height: 60vh;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: var(--col-blanco);
            background-color: var(--col-herencia);
            /* IMAGEN DE FONDO RESTAURADA */
            background-image: url('../../assets/img/conocenos/certificaciones-y-reconocimientos/edificio-bachillerato.jpg');
            background-size: cover;
            background-position: center;
        }

        .hero-overlay {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            /* Filtro oscuro para legibilidad */
            background: linear-gradient(rgba(55, 76, 105, 0.4), rgba(55, 76, 105, 0.5));
            z-index: 1;
        }

        .hero-content {
            position: relative;
            z-index: 2;
            max-width: 900px;
            padding: var(--sp-md);
            animation: fadeInMethod 1.2s ease-out;
            text-shadow: 0 4px 15px rgba(0,0,0,0.5);
        }

        @keyframes fadeInMethod {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .hero-title {
            font-size: clamp(2.5rem, 6vw, 4rem);
            font-weight: 700;
            line-height: 1.1;
            margin-bottom: var(--sp-sm);
            color: var(--col-blanco);
        }

        .hero-subtitle {
            font-size: clamp(1.2rem, 3vw, 1.8rem);
            font-weight: 400;
            color: var(--col-blanco);
            text-align: center; 
        }

        /* 2. MINI MENÚ */
        .sticky-nav-container {
            position: -webkit-sticky;
            position: sticky;
            top: var(--header-height);
            z-index: 900;
            padding: 10px 0;
            background-color: rgba(249, 251, 254, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(0,0,0,0.05);
            display: flex;
            justify-content: center;
            box-shadow: 0 4px 10px rgba(0,0,0,0.03);
        }

        .sticky-nav {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: center;
            padding: 0 var(--sp-md);
        }

        .sticky-link {
            font-size: 0.85rem; font-weight: 600; color: var(--col-herencia); padding: 6px 14px; border-radius: 20px; background-color: #E8EEF4; transition: all 0.2s ease; white-space: nowrap;
        }

        .sticky-link:hover {
            background-color: var(--col-espiritu); color: var(--col-blanco); transform: translateY(-2px);
        }

        /* 3. RESUMEN */
        .intro-section {
            max-width: 1000px;
            margin: 0 auto;
            padding: var(--sp-xxl) var(--sp-md) var(--sp-lg);
            display: flex;
            flex-direction: column;
            gap: var(--sp-lg);
            align-items: center;
            text-align: center;
        }

        .intro-icon-container { width: 80px; height: auto; flex-shrink: 0; margin-bottom: var(--sp-md); }
        .intro-icon-img { width: 100%; height: auto; }
        
        .intro-text-content { text-align: center; }

        .intro-lead { font-size: 1.4rem; color: var(--col-bilbao); font-weight: 600; margin-bottom: var(--sp-md); line-height: 1.5; }
        .intro-text { font-size: 1.15rem; color: var(--col-herencia); line-height: 1.8; font-weight: 300; }

        @media (min-width: 768px) {
            .intro-section { flex-direction: row; align-items: flex-start; text-align: left; }
            .intro-text-content { text-align: justify; }
            .intro-icon-container { margin-right: var(--sp-xl); margin-bottom: 0; border-right: 1px solid #E0E6ED; padding-right: var(--sp-xl); min-height: 120px; display: flex; align-items: center; width: 120px; }
        }

        /* CONTENEDOR GENERAL */
        .section-container { max-width: 1400px; margin: 0 auto; padding: var(--sp-xxl) var(--sp-md); scroll-margin-top: 140px; }
        
        /* Contenedor RESTRINGIDO para evitar desborde (Cambio #3) */
        .container-constrained {
            max-width: 1000px; /* Margen aumentado a los lados */
            margin: 0 auto;
            padding: 0 var(--sp-md);
        }

        /* 4. VALIDEZ SEP */
        .sep-card {
            background-color: white;
            border-radius: 16px;
            padding: var(--sp-xl) var(--sp-lg);
            text-align: center;
            box-shadow: 0 15px 40px rgba(77, 138, 187, 0.15);
            border-top: 6px solid var(--col-bilbao);
            position: relative;
            overflow: hidden;
            margin: var(--sp-xl) auto;
            max-width: 900px;
        }
        
        .sep-card::before {
            content: '';
            position: absolute; top: 0; right: 0; width: 150px; height: 150px;
            background: radial-gradient(circle, rgba(125, 198, 229, 0.1) 0%, transparent 70%);
            pointer-events: none;
        }

        .sep-title {
            font-size: 2.2rem;
            color: var(--col-bilbao);
            font-weight: 700;
            margin-bottom: var(--sp-md);
        }
        
        .sep-text {
            font-size: 1.15rem;
            color: var(--col-herencia);
            line-height: 1.7;
        }

        /* 5. RECONOCIMIENTOS (Feature Split - Con Margen Controlado) */
        .feature-block {
            display: grid; gap: var(--sp-xl); align-items: center; margin-bottom: var(--sp-xxl);
        }
        .feature-image { 
            border-radius: 24px; overflow: hidden; box-shadow: 0 20px 50px rgba(0,0,0,0.15); height: 450px; position: relative;
        }
        .feature-image img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s; }
        .feature-image:hover img { transform: scale(1.03); }
        
        .feature-text { padding: var(--sp-md); }
        .feature-label { text-transform: uppercase; letter-spacing: 2px; color: var(--col-espiritu); font-weight: 700; font-size: 0.85rem; margin-bottom: var(--sp-sm); display: block; }
        .feature-title { font-size: 2.5rem; color: var(--col-bilbao); font-weight: 700; margin-bottom: var(--sp-md); line-height: 1.2; }
        .feature-desc p { margin-bottom: var(--sp-md); font-size: 1.1rem; color: var(--col-texto); }

        @media (min-width: 900px) {
            .feature-block { grid-template-columns: 1fr 1fr; }
            .feature-image { height: 500px; }
        }

        /* 6. CENDES (Destacado) */
        .highlight-section { 
            background-color: var(--bg-secondary);
            border-radius: 24px;
            padding: var(--sp-xl);
            margin: var(--sp-xxl) auto;
            max-width: 1000px;
        }
        
        .cendes-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: var(--sp-xl);
            text-align: center;
        }
        .cendes-logo-area {
            background: white;
            padding: var(--sp-lg);
            border-radius: 50%;
            width: 160px; height: 160px;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            border: 4px solid var(--col-bilbao);
        }
        .cendes-logo-area img { width: 90px; height: auto; } 

        @media (min-width: 900px) {
            .cendes-container { flex-direction: row; text-align: left; }
            .cendes-logo-area { margin-right: var(--sp-xl); flex-shrink: 0; }
        }

        /* 7. EVALUACIÓN Y MEJORA CONTINUA */
        /* Reutiliza .feature-block y .container-constrained */


        /* 8. FAQ (EMBELLECIDO - Estilo Apple Cards) */
        .faq-section { background: var(--bg-global); padding: var(--sp-xxl) 0; border-top: 1px solid var(--col-borde); }
        .faq-wrapper { max-width: 900px; margin: 0 auto; padding: 0 var(--sp-md); }
        .faq-title { text-align: center; font-size: 2rem; color: var(--col-bilbao); margin-bottom: var(--sp-xl); }
        
        .faq-item { 
            background: white; 
            border-radius: 12px; 
            margin-bottom: 16px; 
            border: 1px solid #E0E6ED; /* Borde sutil */
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
            transition: all 0.3s ease;
            overflow: hidden;
        }
        .faq-item:hover { 
            box-shadow: 0 10px 20px rgba(0,0,0,0.05); 
            border-color: var(--col-espiritu);
        }

        .faq-question { 
            width: 100%; text-align: left; padding: 20px 24px; 
            background: none; border: none; font-size: 1.15rem; font-weight: 600; 
            color: var(--col-herencia); display: flex; justify-content: space-between; align-items: center; 
            cursor: pointer; transition: color 0.2s ease; 
        }
        .faq-question:hover { color: var(--col-bilbao); }

        .faq-icon-wrapper { 
            width: 32px; height: 32px; border-radius: 50%; background-color: #F0F4F8; 
            display: flex; align-items: center; justify-content: center; 
            transition: all 0.3s ease; flex-shrink: 0; margin-left: 16px; 
        }
        
        .faq-question[aria-expanded="true"] { background-color: #F9FBFE; }
        .faq-question[aria-expanded="true"] .faq-icon-wrapper { background-color: var(--col-bilbao); transform: rotate(180deg); }
        
        .faq-icon-wrapper svg { width: 14px; height: 14px; fill: var(--col-herencia); transition: transform 0.3s ease, fill 0.3s ease; }
        .faq-question:hover .faq-icon-wrapper svg { fill: var(--col-bilbao); }
        .faq-question[aria-expanded="true"] .faq-icon-wrapper svg { fill: white; }

        .faq-answer { 
            max-height: 0; overflow: hidden; transition: max-height 0.4s cubic-bezier(0.25, 1, 0.5, 1); 
            color: var(--col-texto); font-size: 1.05rem; line-height: 1.6; opacity: 0; 
        }
        .faq-question[aria-expanded="true"] + .faq-answer { opacity: 1; }
        .faq-answer-inner {
            padding: 0 24px 24px 24px; /* Padding interno para contenido */
        }


        /* 9. CTA (Pill Style) */
        .cta-section { text-align: center; padding: var(--sp-xxl) var(--sp-md); background-color: white; border-top: 1px solid var(--col-borde); }
        .cta-pill { max-width: 800px; margin: 0 auto; display: flex; flex-direction: column; align-items: center; }
        
        .cta-mascot {
            width: 140px; height: auto; margin-bottom: 24px; display: inline-block;
        }
        
        .cta-pill h2 { font-size: 2rem; color: var(--col-bilbao); margin-bottom: var(--sp-md); }
        .cta-pill p { text-align: center; font-size: 1.1rem; color: var(--col-texto); margin-bottom: var(--sp-lg); }
        .btn-primary { display: inline-block; background-color: var(--col-bilbao); color: white; padding: 16px 32px; border-radius: 50px; font-weight: 600; font-size: 1.1rem; transition: background 0.3s, transform 0.2s; box-shadow: 0 8px 20px rgba(77, 138, 187, 0.3); }
        .btn-primary:hover { background-color: var(--col-herencia); transform: translateY(-2px); }

        /* FOOTER (Copia Global) */
        footer { background-color: var(--bg-global); border-top: 1px solid var(--col-borde); padding-top: var(--sp-xl); font-size: 0.95rem; color: var(--col-herencia); }
        .footer-container { width: 100%; max-width: 1280px; margin: 0 auto; padding: 0 var(--sp-md); }
        .footer-header { display: flex; flex-direction: column; margin-bottom: var(--sp-lg); align-items: flex-start; }
        .footer-logo-link { display: inline-block; margin-bottom: var(--sp-sm); }
        .footer-logo-img { height: 77px; width: auto; object-fit: contain; display: block; }
        .footer-social-desktop { display: none; }
        .social-link { display: inline-flex; align-items: center; justify-content: center; color: var(--col-herencia); transition: color 0.2s ease, transform 0.2s ease; width: 36px; height: 36px; }
        .social-link:hover { color: var(--col-bilbao); transform: translateY(-2px); }
        .social-icon { width: 20px; height: 20px; fill: currentColor; }
        .footer-grid { display: grid; gap: var(--sp-lg); grid-template-columns: 1fr; }
        .footer-desc { margin-bottom: var(--sp-md); font-size: 0.9rem; line-height: 1.6; text-align: left; }
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

    <!-- Skip Link -->
    <a href="#main-content" class="skip-link">Saltar al contenido principal</a>

    <!-- HEADER -->
    <header class="header-bar">
        <div class="header-inner">
            <a href="/" class="logo-link"><img src="../../assets/img/global/logo-bilbao-horizontal-azul.png" alt="Colegio Bilbao" class="logo-img"></a>
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
                <a href="/" class="logo-link"><img src="../../assets/img/global/logo-bilbao-horizontal-azul.png" alt="Colegio Bilbao" class="logo-img"></a>
                <div class="header-controls">
                    <div class="lang-switch"><span class="active">ES</span> | <a href="/en/">EN</a></div>
                    <button id="close-menu-btn" class="close-btn" aria-label="Cerrar menú"></button>
                </div>
            </div>
        </div>
        <nav class="overlay-content">
            <ul id="primary-nav">
                <li class="nav-accordion-item"><button class="nav-accordion-trigger">Conócenos <span class="chevron">▼</span></button><ul class="nav-submenu"><li><a href="/conocenos/quienes-somos/">Quiénes somos</a></li><li><a href="/conocenos/equipo-educativo/">Equipo educativo</a></li><li><a href="/conocenos/instalaciones/">Instalaciones</a></li><li><a href="/conocenos/certificaciones-y-reconocimientos/">Certificaciones y reconocimientos</a></li></ul></li>
                <li class="nav-accordion-item"><button class="nav-accordion-trigger">Modelo educativo <span class="chevron">▼</span></button><ul class="nav-submenu"><li><a href="/modelo-educativo/modelo-vida/">Modelo VIDA</a></li><li><a href="/modelo-educativo/filosofia-y-metodologia/">Filosofía</a></li><li><a href="/modelo-educativo/aprendizaje-integral/">Aprendizaje integral</a></li><li><a href="/modelo-educativo/idiomas/">Idiomas</a></li></ul></li>
                <li class="nav-accordion-item"><button class="nav-accordion-trigger">Niveles académicos <span class="chevron">▼</span></button><ul class="nav-submenu"><li><a href="/niveles-academicos/preescolar/">Preescolar</a></li><li><a href="/niveles-academicos/primaria/">Primaria</a></li><li><a href="/niveles-academicos/secundaria/">Secundaria</a></li><li><a href="/niveles-academicos/preparatoria/">Preparatoria</a></li></ul></li>
                <li class="nav-accordion-item"><button class="nav-accordion-trigger">Vida escolar <span class="chevron">▼</span></button><ul class="nav-submenu"><li><a href="/vida-escolar/afterschool-extracurriculares/">Afterschool</a></li><li><a href="/vida-escolar/futuro-universitario-becas/">Futuro universitario</a></li><li><a href="/vida-escolar/programa-dual/">Programa Dual</a></li><li><a href="/vida-escolar/servicios-para-familias/">Servicios</a></li><li><a href="/vida-escolar/cuidado-y-bienestar/">Cuidado y bienestar</a></li><li><a href="/vida-escolar/eventos-y-tradiciones/">Eventos</a></li></ul></li>
                <li class="nav-accordion-item"><button class="nav-accordion-trigger">Admisiones <span class="chevron">▼</span></button><ul class="nav-submenu"><li><a href="/admisiones/">Inicio</a></li><li><a href="/admisiones/proceso/">Proceso</a></li><li><a href="/admisiones/preguntas-frecuentes/">FAQ</a></li><li><a href="/admisiones/convenios/">Convenios</a></li><li><a href="/admisiones/convocatoria-becas/">Becas</a></li><li><a href="/admisiones/contacto/">Contacto</a></li></ul></li>
                <li class="nav-accordion-item"><button class="nav-accordion-trigger">Comunidad <span class="chevron">▼</span></button><ul class="nav-submenu"><li><a href="/comunidad/estudiantes/">Estudiantes</a></li><li><a href="/comunidad/familias/">Familias</a></li><li><a href="/comunidad/exalumnos/">Exalumnos</a></li></ul></li>
                <li class="nav-accordion-item"><button class="nav-accordion-trigger">Voces Bilbao <span class="chevron">▼</span></button><ul class="nav-submenu"><li><a href="/voces-bilbao/noticias/">Noticias</a></li><li><a href="/voces-bilbao/entrevistas/">Entrevistas</a></li><li><a href="/voces-bilbao/articulos/">Artículos</a></li><li><a href="/voces-bilbao/testimonios/">Testimonios</a></li></ul></li>
                <li class="nav-accordion-item"><button class="nav-accordion-trigger">Contacto <span class="chevron">▼</span></button><ul class="nav-submenu"><li><a href="/contacto/">Contacto</a></li><li><a href="/contacto/directorio/">Directorio</a></li><li><a href="/contacto/cultura-y-talento/">Cultura y talento</a></li><li><a href="/contacto/proveedores/">Proveedores</a></li></ul></li>
            </ul>
        </nav>
    </div>

    <!-- MAIN CONTENT -->
    <main id="main-content">
        
        <!-- 1. HERO INMERSIVO (Imagen Restaurada) -->
        <section class="page-hero">
            <div class="hero-overlay"></div>
            <div class="hero-content">
                <h1 class="hero-title">Acreditaciones, certificaciones y reconocimientos</h1>
                <p class="hero-subtitle">Acreditaciones que reflejan una manera de educar seria, humana y comprometida con la niñez y la juventud.</p>
            </div>
        </section>

        <!-- 2. MINI MENÚ FLOTANTE -->
        <div class="sticky-nav-container">
            <nav class="sticky-nav">
                <a href="#sep" class="sticky-link">Validez SEP</a>
                <a href="#universidades" class="sticky-link">Universidades</a>
                <a href="#cendes" class="sticky-link">CENDES</a>
                <a href="#evaluacion" class="sticky-link">Evaluación</a>
                <a href="#faq" class="sticky-link">Preguntas</a>
            </nav>
        </div>

        <!-- 3. RESUMEN (Icon Split) -->
        <section id="resumen" class="intro-section">
            <div class="intro-icon-container">
                <img src="../../assets/img/conocenos/certificaciones-y-reconocimientos/icono-diploma.png" alt="Reconocimientos" class="intro-icon-img">
            </div>
            <div class="intro-text-content">
                <p class="intro-lead">Sabemos que elegir escuela implica confiar en quienes acompañarán muchos años a tus hijas e hijos.</p>
                <p class="intro-text">Por eso nuestro proyecto educativo está respaldado por validez oficial, certificaciones especializadas y reconocimientos.</p>
            </div>
        </section>

        <!-- 4. VALIDEZ SEP -->
        <section id="sep" class="section-container">
            <div class="sep-card">
                <h2 class="sep-title">Validez oficial ante la SEP</h2>
                <div class="container-narrow">
                    <p class="sep-text">El Colegio Bilbao está incorporado a la Secretaría de Educación Pública en todos sus niveles educativos. Cumplimos con los planes y programas oficiales, integrando además nuestro propio enfoque formativo y humanista.</p>
                    <p class="sep-text" style="margin-top:16px;">Para las familias, esta incorporación representa seguridad, formalidad y continuidad en el camino escolar de sus hijas e hijos.</p>
                </div>
            </div>
        </section>

        <!-- 5. RECONOCIMIENTOS (ANCHO CONTENIDO) -->
        <section id="universidades" class="gallery-section" style="background-color: var(--bg-secondary);">
             <div class="gallery-container container-constrained"> <!-- Ancho limitado -->
                 <div class="feature-block">
                    <div class="feature-text">
                        <h2 class="feature-title">Reconocimiento académico y becas de universidades líderes</h2>
                        <div class="feature-desc">
                            <p>Las universidades más importantes de México reconocen el nivel académico de nuestras y nuestros egresados. Este reconocimiento se expresa en la cantidad y calidad de becas que ofrecen a estudiantes provenientes del Bilbao.</p>
                            <p>Para las familias, estas oportunidades confirman que la inversión educativa se traduce en opciones reales de futuro.</p>
                            <br>
                            <a href="/vida-escolar/futuro-universitario-becas/" class="text-link">Ver convenios y becas →</a>
                        </div>
                    </div>
                    <div class="feature-image">
                        <img src="../../assets/img/conocenos/certificaciones-y-reconocimientos/clase-bach.jpg" alt="Estudiantes de Bachillerato">
                    </div>
                 </div>
             </div>
        </section>

        <!-- 6. CENDES (Destacado) -->
        <section id="cendes" class="gallery-section highlight-section">
            <div class="gallery-container">
                <div class="cendes-container">
                    <div class="cendes-logo-area">
                         <img src="../../assets/img/conocenos/certificaciones-y-reconocimientos/icono-liston.png" alt="CENDES">
                    </div>
                    <div class="feature-text">
                        <h2 class="feature-title" style="margin-bottom: 24px;">Compromiso activo contra el maltrato, acoso y abuso infantil</h2>
                        <div class="feature-desc">
                            <p>Estamos certificados por <a href="https://www.cendes.com.mx/nosotros/" target="_blank" class="text-link">Fundación CENDES</a> en la prevención del maltrato, el acoso y el abuso infantil.</p>
                            <p>Esta certificación implica protocolos claros, capacitación continua y una postura firme frente a cualquier forma de violencia. Trabajamos para que el colegio sea un espacio seguro, protegido y respetuoso de la dignidad de cada persona.</p>
                            <p>Las familias pueden confiar en que existe una base ética y profesional para actuar ante situaciones delicadas.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 7. EVALUACIÓN Y MEJORA CONTINUA (ANCHO CONTENIDO) -->
        <section id="evaluacion" class="gallery-section">
            <div class="gallery-container container-constrained"> <!-- Ancho limitado -->
                <div class="feature-block reverse">
                    <div class="feature-image">
                        <img src="../../assets/img/conocenos/certificaciones-y-reconocimientos/sasha-y-mau.jpg" alt="Equipo directivo en evaluación">
                    </div>
                    <div class="feature-text">
                        <h2 class="feature-title">Evaluación y mejora continua</h2>
                        <div class="feature-desc">
                            <p>Más allá de las acreditaciones, creemos en revisar nuestro trabajo de manera constante y honesta. Realizamos procesos internos de evaluación que permiten ajustar prácticas, fortalecer aciertos y atender áreas de mejora.</p>
                            <p>Las certificaciones y reconocimientos se entienden como puntos de partida, no como metas finales. Nos recuerdan el compromiso de seguir respondiendo con seriedad a la confianza de la comunidad Bilbao.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 8. FAQ (EMBELLECIDO) -->
        <section id="faq" class="faq-section">
            <div class="faq-wrapper">
                <h2 class="faq-title">Preguntas frecuentes sobre nuestras acreditaciones</h2>
                <div class="faq-item">
                    <button class="faq-question" aria-expanded="false">¿Cómo benefician estas acreditaciones la experiencia diaria? <span class="faq-icon-wrapper"><svg viewBox="0 0 24 24"><path d="M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6-6-6 1.41-1.41z"/></svg></span></button>
                    <div class="faq-answer"><div class="faq-answer-inner"><p>Las acreditaciones exigen procesos claros, seguros y actualizados en el aula y en los patios. Eso se traduce en clases mejor planeadas, ambientes cuidados y decisiones sustentadas, no improvisadas.</p></div></div>
                </div>
                <div class="faq-item">
                    <button class="faq-question" aria-expanded="false">¿De qué manera el reconocimiento de universidades ayuda al futuro? <span class="faq-icon-wrapper"><svg viewBox="0 0 24 24"><path d="M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6-6-6 1.41-1.41z"/></svg></span></button>
                    <div class="faq-answer"><div class="faq-answer-inner"><p>Las universidades que nos reconocen ofrecen becas y facilidades de ingreso a nuestro alumnado destacado. Eso abre opciones reales de estudio superior y reduce la carga económica para la familia.</p></div></div>
                </div>
            </div>
        </section>

        <!-- 9. CTA (Pill Style con Mascota) -->
        <section class="cta-section">
            <div class="cta-pill">
                <!-- Mascota Agregada -->
                <img src="../../assets/img/conocenos/certificaciones-y-reconocimientos/Alex-espera.png" alt="Mascota Alex" style="width: 140px; height: auto; margin-bottom: 24px; display: inline-block;">
                <h2>Conoce nuestro modelo educativo</h2>
                <p class="text-center">Conoce nuestro modelo educativo y descubre cómo cuidamos la formación y el futuro de tus hijas e hijos.</p>
                <a href="/modelo-educativo/modelo-vida/" class="btn-primary">Conoce nuestro modelo educativo</a>
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
</body>
</html>