<!DOCTYPE html>
<html lang="es-MX">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Aprendizaje Integral en Colegio Bilbao: Cinco caminos para aprender con sentido (ciencia, humanidades, artes, tecnología y vida activa).">
    <meta name="robots" content="index, follow">
    
    <title>Aprendizaje Integral | Colegio Bilbao</title>

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
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <style>
        /* --- 1. VARIABLES & RESET --- */
        :root {
            --bg-global: #FFFFFF;
            --col-bilbao: #4D8ABB;
            --col-espiritu: #7DC6E5;
            --col-herencia: #374C69;
            --col-texto: #374C69;
            --col-blanco: #FFFFFF;
            --col-borde: #E0E6ED;
            
            --sp-sm: 16px; --sp-md: 24px; --sp-lg: 48px; --sp-xl: 80px; --sp-xxl: 120px;
            --font-main: 'Montserrat', sans-serif;
            --max-width: 1280px;
            --header-height: 90px;
            
            --radius-card: 24px;
            --shadow-float: 0 20px 50px rgba(0,0,0,0.1);
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
        p { text-align: justify; margin-bottom: 1rem; }
        .text-center { text-align: center !important; }

        /* --- HEADER & MENU STYLES (INTACTOS) --- */
        .header-bar { position: fixed; top: 0; left: 0; width: 100%; height: var(--header-height); background-color: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); z-index: 1000; border-bottom: 1px solid rgba(77, 138, 187, 0.1); display: flex; align-items: center; justify-content: center; padding: 0 var(--sp-md); }
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

        /* --- ESTILOS DE PÁGINA --- */
        main { padding-top: var(--header-height); }

        /* 1. HERO SPLIT (NUEVO) */
        .hero-section {
            position: relative;
            min-height: 85vh; 
            display: flex; align-items: center;
            background-color: var(--bg-global);
            overflow: hidden; padding-top: 40px; 
        }

        .hero-blob-bg {
            position: absolute; right: -15%; top: -20%; width: 70vw; height: 70vw;
            background: radial-gradient(circle, rgba(77, 138, 187, 0.08) 0%, rgba(255,255,255,0) 70%);
            border-radius: 50%; z-index: 0; pointer-events: none;
            animation: pulseBlob 10s infinite alternate;
        }
        @keyframes pulseBlob { 0% { transform: scale(1); } 100% { transform: scale(1.1); } }

        .hero-grid {
            display: grid; grid-template-columns: 1fr; gap: 3rem;
            width: 100%; max-width: var(--max-width); margin: 0 auto;
            padding: 0 var(--sp-md); position: relative; z-index: 2; align-items: center;
        }
        @media (min-width: 1024px) { .hero-grid { grid-template-columns: 0.9fr 1.1fr; gap: 5rem; } }

        /* Columna Texto Hero */
        .hero-text-col { position: relative; z-index: 3; text-align: left; }
        
        .hero-supertitle {
            font-size: clamp(3rem, 7vw, 5rem); font-weight: 900; line-height: 1; color: var(--col-bilbao);
            margin-bottom: 1.5rem; margin-left: -5px; letter-spacing: -0.04em;
            opacity: 0; animation: fadeUp 1s ease forwards 0.2s;
        }
        
        .hero-desc-p {
            font-size: 1.15rem; line-height: 1.6; color: var(--col-texto); max-width: 550px;
            margin-bottom: 0; opacity: 0; animation: fadeUp 1s ease forwards 0.6s;
            text-align: left;
        }

        /* Columna Imagen Hero */
        .hero-img-col {
            position: relative; height: 500px; display: flex; align-items: center; justify-content: center;
            opacity: 0; animation: scaleIn 1.2s cubic-bezier(0.2, 0.8, 0.2, 1) forwards 0.3s;
        }
        .hero-img-main {
            width: 100%; height: 100%; object-fit: cover; border-radius: 30px;
            box-shadow: 30px 40px 80px -10px rgba(0, 51, 102, 0.2);
            z-index: 2; transition: transform 0.6s ease; transform: rotate(2deg);
        }
        .hero-img-col:hover .hero-img-main { transform: rotate(0deg) scale(1.02); }
        
        .hero-floating-card {
            position: absolute; background: white; padding: 1.5rem 2rem;
            border-radius: 20px; box-shadow: var(--shadow-float);
            z-index: 3; bottom: 40px; left: -20px;
            border: 1px solid rgba(255,255,255,0.8);
            animation: floatCard 5s ease-in-out infinite;
            text-align: left;
        }
        .float-stat { font-size: 2rem; font-weight: 900; color: var(--col-bilbao); line-height: 1; margin-bottom: 5px; display: block; }
        .float-label { font-size: 0.9rem; font-weight: 700; color: var(--col-herencia); text-transform: uppercase; letter-spacing: 0.5px; }

        @keyframes floatCard { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }
        @keyframes fadeUp { to { opacity: 1; transform: translateY(0); } from { opacity: 0; transform: translateY(40px); } }
        @keyframes scaleIn { to { opacity: 1; transform: scale(1); } from { opacity: 0; transform: scale(0.95); } }

        /* 2. RESUMEN (ESTILO QUIÉNES SOMOS) */
        .intro-section { padding: var(--sp-xxl) var(--sp-md); max-width: 900px; margin: 0 auto; text-align: center; }
        
        .intro-highlight { 
            font-size: 1.5rem; font-weight: 600; color: var(--col-bilbao); 
            line-height: 1.4; margin-bottom: var(--sp-lg);
        }
        .intro-body { 
            font-size: 1.15rem; color: var(--col-herencia); line-height: 1.8; 
            max-width: 800px; margin: 0 auto;
        }
        
        .graphic-separator {
            width: 80px; height: 3px; background: linear-gradient(90deg, var(--col-bilbao), var(--col-espiritu));
            margin: var(--sp-md) auto; border-radius: 2px;
        }

        /* 3. STICKY SCROLL SECTION (MANTENIDO) */
        .sticky-container { position: relative; display: block; }

/* OCULTAR VISUALES MÓVILES EN ESCRITORIO */
        .mobile-visual { display: none; }

        @media (min-width: 1024px) {
            .sticky-container { display: flex; max-width: 1400px; margin: 0 auto; padding: var(--sp-xl) var(--sp-md); }
            .visual-panel { width: 50%; height: 80vh; position: sticky; top: 120px; display: flex; align-items: center; justify-content: center; border-radius: 30px; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,0.1); }
            .visual-image { position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; transition: opacity 0.6s ease-in-out, transform 0.6s ease; opacity: 0; }
            .visual-image.active { opacity: 1; transform: scale(1.05); }
            .visual-mascot { position: absolute; bottom: 20px; right: 20px; width: 150px; height: auto; z-index: 10; filter: drop-shadow(0 5px 15px rgba(0,0,0,0.2)); transition: opacity 0.4s ease; opacity: 0; transform: translateY(20px); }
            .visual-mascot.active { opacity: 1; transform: translateY(0); }
            .content-panel { width: 50%; padding-left: var(--sp-xl); }
            .content-section { min-height: 80vh; display: flex; flex-direction: column; justify-content: center; padding: var(--sp-lg) 0; opacity: 0.3; transition: opacity 0.5s; }
            .content-section.active { opacity: 1; }
        }

        @media (max-width: 1023px) {
            .sticky-container { display: block; padding: var(--sp-lg) var(--sp-md); }
            .visual-panel { display: none; }
            .content-section { margin-bottom: var(--sp-xl); background: white; border-radius: 24px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.05); }
            .mobile-visual { display: block; width: 100%; height: 250px; position: relative; }
            .mobile-visual img { width: 100%; height: 100%; object-fit: cover; }
            .mobile-mascot { position: absolute; bottom: -20px; right: 20px; width: 100px; }
            .text-wrapper { padding: var(--sp-lg); }
        }

        /* Estilos de Texto Pilares */
        .pillar-number { font-size: 5rem; font-weight: 900; color: rgba(77, 138, 187, 0.1); line-height: 1; margin-bottom: -20px; display: block; }
        .pillar-title { font-size: 2.2rem; color: var(--col-bilbao); font-weight: 700; margin-bottom: var(--sp-md); }
        .pillar-text { font-size: 1.1rem; color: var(--col-herencia); line-height: 1.7; }
        .pillar-list { margin-top: var(--sp-md); padding-left: 0; }
        .pillar-list li { list-style: none; position: relative; padding-left: 24px; margin-bottom: 8px; font-weight: 500; }
        .pillar-list li::before { content: '•'; color: var(--col-espiritu); font-size: 1.5rem; position: absolute; left: 0; top: -5px; }

        /* 4. FAQ */
        .faq-section { background-color: var(--bg-global); padding: var(--sp-xxl) var(--sp-md); }
        .faq-wrapper { max-width: 900px; margin: 0 auto; }
        .faq-title { text-align: center; font-size: 2rem; color: var(--col-bilbao); margin-bottom: var(--sp-xl); }
        .faq-item { border-bottom: 1px solid #E5E7EB; margin-bottom: 0; }
        .faq-question { width: 100%; text-align: left; padding: 24px 0; background: none; border: none; font-size: 1.15rem; font-weight: 600; color: var(--col-herencia); display: flex; justify-content: space-between; align-items: center; cursor: pointer; transition: color 0.2s; }
        .faq-question:hover { color: var(--col-bilbao); }
        .faq-icon-wrap { width: 32px; height: 32px; border-radius: 50%; background: #F3F4F6; display: flex; align-items: center; justify-content: center; transition: 0.3s; }
        .faq-question[aria-expanded="true"] .faq-icon-wrap { background: var(--col-bilbao); transform: rotate(180deg); }
        .faq-question[aria-expanded="true"] .faq-icon-wrap svg { fill: white; }
        .faq-answer { max-height: 0; overflow: hidden; transition: 0.4s ease; color: var(--col-texto); font-size: 1rem; }
        .faq-question[aria-expanded="true"] + .faq-answer { padding-bottom: 24px; }

        /* 5. CTA (Estilo Certificaciones - Pill) */
        .cta-section { padding: var(--sp-xxl) var(--sp-md); text-align: center; background: white; border-top: 1px solid var(--col-borde); }
        .cta-pill { max-width: 800px; margin: 0 auto; display: flex; flex-direction: column; align-items: center; }
        
        /* Mascota CTA */
        .mascot-img { width: 140px; margin-bottom: 24px; display: inline-block; }
        
        .cta-h2 { font-size: 2rem; color: var(--col-bilbao); margin-bottom: var(--sp-md); }
        .cta-p { text-align: center; font-size: 1.1rem; color: var(--col-texto); margin-bottom: var(--sp-lg); }
        .btn-primary { display: inline-block; background-color: var(--col-bilbao); color: white; padding: 16px 32px; border-radius: 50px; font-weight: 600; font-size: 1.1rem; transition: background 0.3s, transform 0.2s; box-shadow: 0 8px 20px rgba(77, 138, 187, 0.3); }
        .btn-primary:hover { background-color: var(--col-herencia); transform: translateY(-2px); }

        /* FOOTER (Copia Exacta) */
        footer { background-color: #F9FBFE; border-top: 1px solid var(--col-borde); padding-top: 60px; font-size: 0.95rem; color: var(--col-herencia); }
        .footer-container { width: 100%; max-width: 1280px; margin: 0 auto; padding: 0 24px; }
        .footer-header { display: flex; flex-direction: column; margin-bottom: 40px; align-items: flex-start; }
        .footer-logo-link { display: inline-block; margin-bottom: 16px; }
        .footer-logo-img { height: 77px; width: auto; object-fit: contain; display: block; }
        .footer-social-desktop { display: none; }
        .social-link { display: inline-flex; align-items: center; justify-content: center; color: var(--col-herencia); margin-right: 12px; transition: 0.2s; }
        .social-link:hover { color: var(--col-bilbao); }
        .social-icon { width: 20px; height: 20px; fill: currentColor; }
        .footer-grid { display: grid; gap: 40px; grid-template-columns: 1fr; }
        .footer-desc { margin-bottom: 24px; font-size: 0.9rem; line-height: 1.6; text-align: left; }
        .footer-contact p { margin-bottom: 8px; text-align: left; }
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
    <main>
        
        <!-- 1. HERO SPLIT (NUEVO) -->
        <section class="hero-section">
            <div class="hero-blob-bg"></div>
            <div class="hero-grid">
                <!-- Columna Texto -->
                <div class="hero-text-col">
                    <span class="hero-supertitle">APRENDIZAJE INTEGRAL</span>
                    <h1 class="hero-title">Cinco caminos para aprender con sentido</h1>
                    <p class="hero-desc-p">Ciencia, humanidades, artes, tecnología y vida activa. En el Colegio Bilbao el aprendizaje no se reduce a materias, se vive como experiencias reales.</p>
                </div>
                
                <!-- Columna Imagen -->
                <div class="hero-img-col">
                    <img src="../../assets/img/modelo-educativo/aprendizaje-integral/a-integral.jpg" alt="Aprendizaje Integral" class="hero-img-main">
                    
                    <!-- Floating Card -->
                    <div class="hero-floating-card">
                        <span class="float-stat">5</span>
                        <span class="float-label">Caminos del<br>Modelo VIDA</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- 2. RESUMEN (ESTILO QUIÉNES SOMOS) -->
        <section class="intro-section">
            <div class="intro-section">
                <p class="intro-highlight">En el Colegio Bilbao, el aprendizaje no se reduce a materias separadas. Se vive como experiencias que desarrollan criterio, curiosidad y habilidades reales.</p>
                <div class="graphic-separator"></div>
                <p class="intro-body">Por eso formamos desde preescolar hasta prepa con una mirada integral y humana. Lo que se trabaja en el colegio se nota en casa y se sostiene en comunidad.</p>
            </div>
        </section>

        <!-- 3. STICKY SCROLL SECTION (MANTENIDO) -->
        <div class="sticky-container">
            
            <!-- Panel Visual (Izquierda - Sticky) -->
            <div class="visual-panel">
                <!-- Imágenes de fondo que cambian -->
                <img src="../../assets/img/modelo-educativo/aprendizaje-integral/ciencia.jpg" class="visual-image active" id="img-ciencia" alt="Ciencia">
                <img src="../../assets/img/modelo-educativo/aprendizaje-integral/humanidades.jpg" class="visual-image" id="img-humanidades" alt="Humanidades">
                <img src="../../assets/img/modelo-educativo/aprendizaje-integral/arte.jpg" class="visual-image" id="img-artes" alt="Artes">
                <img src="../../assets/img/modelo-educativo/aprendizaje-integral/tech.jpg" class="visual-image" id="img-tecnologia" alt="Tecnología">
                <img src="../../assets/img/modelo-educativo/aprendizaje-integral/volley.jpg" class="visual-image" id="img-deportes" alt="Deportes">

                <!-- Mascotas que cambian -->
                <img src="../../assets/img/modelo-educativo/aprendizaje-integral/alex-ciencia.png" class="visual-mascot active" id="mascot-ciencia" alt="Alex Científico">
                <img src="../../assets/img/modelo-educativo/aprendizaje-integral/alex-lee.png" class="visual-mascot" id="mascot-humanidades" alt="Alex Humanidades">
                <img src="../../assets/img/modelo-educativo/aprendizaje-integral/alex-toca.png" class="visual-mascot" id="mascot-artes" alt="Alex Arte">
                <img src="../../assets/img/modelo-educativo/aprendizaje-integral/alex-tech.png" class="visual-mascot" id="mascot-tecnologia" alt="Alex Tech">
                <img src="../../assets/img/modelo-educativo/aprendizaje-integral/alex-volley.png" class="visual-mascot" id="mascot-deportes" alt="Alex Deportes">
            </div>

            <!-- Panel de Contenido (Derecha - Scroll) -->
            <div class="content-panel">
                
                <!-- 1. CIENCIA -->
                <div class="content-section active" data-target="ciencia">
                    <!-- Mobile Visuals -->
                    <div class="mobile-visual display-block lg:hidden">
                        <img src="../../assets/img/modelo-educativo/aprendizaje-integral/ciencia.jpg" alt="Ciencia">
                       
                    </div>
                    
                    <div class="text-wrapper">
                        <span class="pillar-number">01</span>
                        <h2 class="pillar-title">Cómo vivimos las ciencias</h2>
                        <p class="pillar-text">En el Bilbao, la ciencia nace de la curiosidad y se vuelve método. Desde pequeños observan, hacen preguntas y prueban ideas con acompañamiento. Se aprende con experimentación, registro y reflexión.</p>
                        <p class="pillar-text">El campus y sus espacios abiertos también se vuelven laboratorio vivo. Esto fortalece pensamiento crítico y confianza para resolver problemas reales.</p>
                    </div>
                </div>

                <!-- 2. HUMANIDADES -->
                <div class="content-section" data-target="humanidades">
                    <div class="mobile-visual display-block lg:hidden">
                        <img src="../../assets/img/modelo-educativo/aprendizaje-integral/humanidades.jpg" alt="Humanidades">
                         
                    </div>
                    
                    <div class="text-wrapper">
                        <span class="pillar-number">02</span>
                        <h2 class="pillar-title">Cómo trabajamos las humanidades</h2>
                        <p class="pillar-text">Las humanidades enseñan a pensar, dialogar y comprender contextos humanos. Promovemos lectura, expresión oral y escritura con intención.</p>
                        <ul class="pillar-list">
                            <li>Equipos de debate y argumentación.</li>
                            <li>Visitas formativas (Senado, Cámara de Diputados).</li>
                            <li>Entender que la voz importa y conlleva responsabilidad.</li>
                        </ul>
                    </div>
                </div>

                <!-- 3. ARTES -->
                <div class="content-section" data-target="artes">
                    <div class="mobile-visual display-block lg:hidden">
                        <img src="../../assets/img/modelo-educativo/aprendizaje-integral/arte.jpg" alt="Artes">
                         
                    </div>
                    
                    <div class="text-wrapper">
                        <span class="pillar-number">03</span>
                        <h2 class="pillar-title">Las artes como lenguaje</h2>
                        <p class="pillar-text">En el Bilbao, el arte no es adorno, es formación profunda. Se vive como herramienta para crear, colaborar y tolerar la frustración.</p>
                        <p class="pillar-text">La práctica artística desarrolla disciplina, sensibilidad y mirada crítica. También abre un espacio seguro para expresarse y ser visto por sus pares.</p>
                    </div>
                </div>

                <!-- 4. TECNOLOGÍA -->
                <div class="content-section" data-target="tecnologia">
                    <div class="mobile-visual display-block lg:hidden">
                        <img src="../../assets/img/modelo-educativo/aprendizaje-integral/tech.jpg" alt="Tecnología">
                         
                    </div>
                    
                    <div class="text-wrapper">
                        <span class="pillar-number">04</span>
                        <h2 class="pillar-title">Tecnología al servicio del aprendizaje</h2>
                        <p class="pillar-text">Usamos tecnología para potenciar el aprendizaje, no para reemplazar lo humano. Se integra como herramienta para investigar, crear y presentar proyectos.</p>
                        <p class="pillar-text">Buscamos pensamiento lógico, criterio digital y uso responsable. La meta es que construyan, no solo consuman, preparándose para el futuro con autonomía.</p>
                    </div>
                </div>

                <!-- 5. DEPORTES -->
                <div class="content-section" data-target="deportes">
                     <div class="mobile-visual display-block lg:hidden">
                        <img src="../../assets/img/modelo-educativo/aprendizaje-integral/volley.jpg" alt="Deportes">
                         
                    </div>
                    
                    <div class="text-wrapper">
                        <span class="pillar-number">05</span>
                        <h2 class="pillar-title">Deportes y vida activa</h2>
                        <p class="pillar-text">El deporte forma cuerpo, carácter y comunidad. Se promueven hábitos de movimiento, disciplina y trabajo en equipo.</p>
                        <p class="pillar-text">La vida activa se entiende como bienestar, convivencia y constancia. Así el alumnado aprende a cuidarse y a pertenecer a un grupo.</p>
                    </div>
                </div>

            </div>
        </div>

        <!-- 4. FAQ -->
        <section class="faq-section">
            <div class="faq-wrapper">
                <h2 class="faq-title">Preguntas frecuentes</h2>
                <div class="faq-item">
                    <button class="faq-question" aria-expanded="false">¿Cómo evitan que lo académico se sienta desconectado? <span class="faq-icon-wrap"><svg viewBox="0 0 24 24"><path d="M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6-6-6 1.41-1.41z"/></svg></span></button>
                    <div class="faq-answer"><p>Trabajamos experiencias y proyectos que cruzan distintas disciplinas con un propósito común. Eso ayuda a comprender mejor y recordar por más tiempo.</p></div>
                </div>
                <div class="faq-item">
                    <button class="faq-question" aria-expanded="false">¿Cómo apoyan a estudiantes con fortalezas distintas? <span class="faq-icon-wrap"><svg viewBox="0 0 24 24"><path d="M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6-6-6 1.41-1.41z"/></svg></span></button>
                    <div class="faq-answer"><p>Observamos su estilo de aprendizaje y ajustamos estrategias con seguimiento cercano. La meta es progreso real, no comparaciones injustas.</p></div>
                </div>
                <div class="faq-item">
                    <button class="faq-question" aria-expanded="false">¿Qué tan importante es el debate y la expresión oral? <span class="faq-icon-wrap"><svg viewBox="0 0 24 24"><path d="M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6-6-6 1.41-1.41z"/></svg></span></button>
                    <div class="faq-answer"><p>Es clave para pensamiento crítico, liderazgo y vida universitaria. Se practica con estructura y respeto, no con confrontación.</p></div>
                </div>
                <div class="faq-item">
                    <button class="faq-question" aria-expanded="false">¿La tecnología implica más pantalla? <span class="faq-icon-wrap"><svg viewBox="0 0 24 24"><path d="M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6-6-6 1.41-1.41z"/></svg></span></button>
                    <div class="faq-answer"><p>Buscamos creación con sentido y criterio digital, no solo tiempo de pantalla. La tecnología se usa como medio, no como fin.</p></div>
                </div>
            </div>
        </section>

        <!-- 5. CTA (Estilo Pill - Certificaciones) -->
        <section class="cta-section">
            <div class="cta-pill">
                <!-- Mascota Agregada -->
                <img src="../../assets/img/modelo-educativo/aprendizaje-integral/alex-point.png" style="width: 140px; height: auto; margin-bottom: 24px; display: inline-block;">
                <h2>Vívelo de cerca</h2>
                <p class="text-center">Si quieres ver cómo estas experiencias cambian la forma de aprender, lo mejor es vivirlo de cerca.</p>
                <a href="https://wa.me/525614612682?text=Hola,%20acabo%20de%20ver%20la%20página%20de%205%20aprendizajes%20bilbao,%20me%20gustó%20y%20quiero%20conocer%20el%20colegio%20en%20una%20visita%20guiada." class="btn-primary">Agenda una visita</a>
            </div>
        </section>

    </main>

    <!-- FOOTER -->
    <footer>
        <div class="footer-container">
            <div class="footer-header">
                <a href="/" class="footer-logo-link"><img src="../../assets/img/global/logo-bilbao-horizontal-azul.png" alt="Colegio Bilbao" class="footer-logo-img"></a>
                <div class="footer-social-desktop">
                    <span style="margin-right: 8px;">Síguenos</span>
                    <a href="https://www.facebook.com/bilbaocolegio/" target="_blank" rel="noopener noreferrer" class="social-link" aria-label="Facebook"><svg class="social-icon" viewBox="0 0 24 24"><path d="M14 13.5h2.5l1-4H14v-2c0-1.03 0-2 2-2h1.5V1.74c-.75-.07-2.47-.24-4.75-.24-4.8 0-8 2.9-8 8v3h-5v4h5v11.5h7V13.5z"/></svg></a>
                    <a href="https://www.instagram.com/bilbaocolegio/" target="_blank" rel="noopener noreferrer" class="social-link" aria-label="Instagram"><svg class="social-icon" viewBox="0 0 24 24"><path d="M12 2.16c3.2 0 3.58.01 4.85.07 3.25.15 4.77 1.69 4.92 4.92.06 1.27.07 1.65.07 4.85 0 3.2-.01 3.58-.07 4.85-.15 3.23-1.66 4.77-4.92 4.92-1.27.06-1.64.07-4.85.07-3.2 0-3.58-.01-4.85-.07-3.23-.15-4.77-1.66-4.92-4.92-.06-1.27-.07-1.64-.07-4.85 0-3.2.01-3.58.07-4.85.15-3.23 1.66-4.77 4.92-4.92 1.27-.06 1.65-.07 4.85-.07M12 0C8.74 0 8.33.01 7.05.07 2.7.27.27 2.7.07 7.05.01 8.33 0 8.74 0 12s.01 3.67.07 4.95c.2 4.36 2.62 6.78 6.98 6.98 1.28.06 1.69.07 4.95.07s3.67-.01 4.95-.07c4.36-.2 6.78-2.62 6.98-6.98.06-1.28.07-1.69.07-4.95s-.01-3.67-.07-4.95C23.73 2.7 21.31.27 16.95.07 15.67.01 15.26 0 12 0zm0 5.84c-3.4 0-6.16 2.76-6.16 6.16 0 3.4 2.76 6.16 6.16 6.16 3.4 0 6.16-2.76 6.16-6.16 0-3.4-2.76-6.16-6.16-6.16zm0 10.16c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4zm6.4-10.8c0 .66-.54 1.2-1.2 1.2-.66 0-1.2-.54-1.2-1.2 0-.66.54-1.2 1.2-1.2.66 0 1.2.54 1.2 1.2z"/></svg></a>
                        <a href="https://www.youtube.com/@ColegioBilbaoOficial" class="social-link"><svg class="social-icon" viewBox="0 0 24 24"><path d="M19.615 3.184c-3.604-.246-11.631-.245-15.23 0-3.897.266-4.356 2.62-4.385 8.816.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0 3.897-.266 4.356-2.62 4.385-8.816-.029-6.185-.484-8.549-4.385-8.816zm-10.615 12.816v-8l8 3.993-8 4.007z"/></svg></a>
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
        const faqQuestions = document.querySelectorAll('.faq-question');

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
                if(submenu) {
                    const expanded = trigger.getAttribute('aria-expanded') === 'true';
                    trigger.setAttribute('aria-expanded', !expanded);
                    submenu.style.display = expanded ? 'none' : 'block';
                }
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

        // FAQ Logic
        faqQuestions.forEach(question => {
            question.addEventListener('click', () => {
                const isExpanded = question.getAttribute('aria-expanded') === 'true';
                question.setAttribute('aria-expanded', !isExpanded);
                const answer = question.nextElementSibling;
                if (!isExpanded) {
                    answer.style.maxHeight = answer.scrollHeight + "px";
                } else {
                    answer.style.maxHeight = null;
                }
            });
        });
        
        // Sticky Scroll Logic (THE MAGIC)
        const sections = document.querySelectorAll('.content-section');
        const visualImages = document.querySelectorAll('.visual-image');
        const visualMascots = document.querySelectorAll('.visual-mascot');

        const observerOptions = {
            root: null,
            rootMargin: '-40% 0px -40% 0px',
            threshold: 0
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    // Activate Text
                    sections.forEach(s => s.classList.remove('active'));
                    entry.target.classList.add('active');

                    // Activate Visuals
                    const targetId = entry.target.getAttribute('data-target');
                    
                    visualImages.forEach(img => {
                        img.classList.remove('active');
                        if (img.id === 'img-' + targetId) img.classList.add('active');
                    });

                    visualMascots.forEach(mascot => {
                        mascot.classList.remove('active');
                        if (mascot.id === 'mascot-' + targetId) mascot.classList.add('active');
                    });
                }
            });
        }, observerOptions);

        sections.forEach(section => {
            observer.observe(section);
        });

        window.addEventListener('resize', () => {
             if(window.innerWidth >= 1024) {
                 document.querySelectorAll('.footer-links').forEach(el => el.style.display = 'block');
                 document.querySelectorAll('.footer-col-title .chevron').forEach(el => el.style.transform = '');
             } else {
                 document.querySelectorAll('.footer-links').forEach(el => el.style.display = 'none');
             }
        });
    </script>
</body>
</html>