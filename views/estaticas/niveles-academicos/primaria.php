<!DOCTYPE html>
<html lang="es-MX">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Primaria del Colegio Bilbao: Un espacio donde tu hija o hijo entiende lo que aprende, desarrolla autonomía y quiere volver cada día.">
    <meta name="robots" content="index, follow">
    
    <title>Primaria | Colegio Bilbao</title>

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
            --col-bilbao: #4D8ABB;   /* Azul Principal */
            --col-espiritu: #7DC6E5; /* Azul Claro */
            --col-herencia: #374C69; /* Azul Oscuro/Texto */
            --col-texto: #374C69;
            --col-blanco: #FFFFFF;
            --col-borde: #E0E6ED;
            
            /* ESPACIADO REDUCIDO (Igual a Preescolar) */
            --sp-xs: 8px; --sp-sm: 16px; --sp-md: 24px; --sp-lg: 32px; --sp-xl: 48px; --sp-xxl: 64px;
            --font-main: 'Montserrat', sans-serif;
            --max-width: 1280px;
            --header-height: 90px;
            
            /* Variables Diseño Orgánico */
            --radius-soft: 30px;
            --radius-pill: 50px;
            --bg-soft-blue: #F0F6FC;
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

        /* --- HEADER & MENU STYLES (NO MODIFICAR - Consistency) --- */
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

        /* --- PAGE STYLES: PRIMARIA --- */
        main { padding-top: var(--header-height); }

        /* 1. HERO SUAVE */
        .preschool-hero {
            position: relative;
            min-height: 65vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: var(--col-blanco);
            overflow: hidden;
        }

        .hero-bg {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            /* Imagen: Alumnos Abrazados (Horizontal) */
            background-image: url('../../assets/img/niveles-academicos/primaria/alumnos-abrazados.jpg');
            background-size: cover;
            background-position: center;
            z-index: 1;
        }

        .hero-overlay {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            /* Filtro suave */
            background: linear-gradient(rgba(125, 198, 229, 0.2), rgba(77, 138, 187, 0.4)); 
            z-index: 2;
        }

        .hero-content {
            position: relative;
            z-index: 3;
            max-width: 900px;
            padding: var(--sp-md);
            animation: floatUp 1.2s ease-out;
            text-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }

        @keyframes floatUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

        .hero-title {
            font-size: clamp(3rem, 8vw, 5rem);
            font-weight: 700;
            line-height: 1.1;
            margin-bottom: var(--sp-sm);
            text-shadow: 0 4px 15px rgba(0,0,0,0.3);
        }

        .hero-subtitle {
            font-size: clamp(1.2rem, 3vw, 1.8rem);
            font-weight: 500;
            text-shadow: 0 2px 8px rgba(0,0,0,0.3);
        }

        /* 2. MINI MENÚ (Simplificado) */
        .sticky-nav-container {
            position: -webkit-sticky; position: sticky; top: var(--header-height); z-index: 900;
            padding: 10px 0; background-color: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(0,0,0,0.05); display: flex; justify-content: center;
        }
        .sticky-nav { display: flex; gap: 8px; flex-wrap: wrap; justify-content: center; padding: 0 var(--sp-md); }
        .sticky-link {
            font-size: 0.85rem; font-weight: 600; color: var(--col-herencia); padding: 6px 14px;
            border-radius: 20px; background-color: #F0F4F8; transition: all 0.2s ease; white-space: nowrap;
        }
        .sticky-link:hover { background-color: var(--col-espiritu); color: white; transform: translateY(-2px); }

        /* 3. RESUMEN CON MASCOTA (Style Certificaciones) */
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

        /* MASCOTA MÁS GRANDE (200px) */
        .intro-icon-container { width: 200px; height: auto; flex-shrink: 0; margin-bottom: var(--sp-md); }
        .intro-icon-img { width: 100%; height: auto; }
        
        .intro-text-content { text-align: center; } 

        .intro-lead { font-size: 1.4rem; color: var(--col-bilbao); font-weight: 600; margin-bottom: var(--sp-md); line-height: 1.5; }
        .intro-text { font-size: 1.15rem; color: var(--col-herencia); line-height: 1.8; font-weight: 300; }

        @media (min-width: 768px) {
            .intro-section { flex-direction: row; align-items: flex-start; text-align: left; }
            .intro-text-content { text-align: justify; }
            .intro-icon-container { margin-right: var(--sp-xl); margin-bottom: 0; border-right: 2px solid #E0E6ED; padding-right: var(--sp-xl); min-height: 120px; display: flex; align-items: center; width: 200px; }
        }

        /* 4. BLOQUES DE CONTENIDO (ORGÁNICOS) */
        .section-container { max-width: 1280px; margin: 0 auto; padding: var(--sp-xxl) var(--sp-md); }
        
        .organic-block {
            display: grid;
            gap: var(--sp-xl);
            align-items: center;
            margin-bottom: var(--sp-xxl);
        }
        
        /* Imagen con borde orgánico suave */
        .organic-img-wrapper {
            position: relative;
            border-radius: var(--radius-soft);
            overflow: hidden;
            box-shadow: 0 15px 40px rgba(77, 138, 187, 0.15);
            height: 400px;
        }
        
        .organic-img-wrapper img {
            width: 100%; height: 100%; object-fit: cover; transition: transform 0.6s ease;
        }
        .organic-img-wrapper:hover img { transform: scale(1.05); }

        /* Ajustes para imagen vertical */
        .img-vertical { height: 500px !important; max-width: 400px; margin: 0 auto; }

        .section-title { font-size: 2.2rem; color: var(--col-bilbao); margin-bottom: var(--sp-md); font-weight: 700; line-height: 1.2; }
        .section-text { font-size: 1.1rem; color: var(--col-texto); line-height: 1.7; }
        .section-text p { margin-bottom: var(--sp-sm); }
        
        .mascot-float-small { width: 80px; margin-bottom: 16px; display: block; }
        .mascot-float-center { width: 100px; margin: 0 auto 16px; display: block; }

        @media (min-width: 900px) {
            .organic-block { grid-template-columns: 1fr 1fr; }
            .organic-block.reverse .organic-text { order: 1; }
            .organic-block.reverse .organic-img-wrapper { order: 2; }
            .organic-img-wrapper { height: 500px; }
            .img-vertical { height: 600px !important; }
        }

        /* 5. PROYECTOS (GRID DE TARJETAS) */
        .learning-section {
            background-color: #F0F6FC; /* Azul muy pálido */
            border-radius: var(--radius-soft);
            padding: var(--sp-xl);
            margin: var(--sp-xxl) auto;
            max-width: 1280px;
        }

        .learning-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: var(--sp-md);
            margin-top: var(--sp-lg);
        }

        .learning-card {
            background: white;
            padding: var(--sp-lg);
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 4px 10px rgba(0,0,0,0.03);
            transition: transform 0.3s;
        }
        .learning-card:hover { transform: translateY(-5px); }

        .learning-icon {
            font-size: 2rem; color: var(--col-espiritu); margin-bottom: var(--sp-sm); display: block;
        }
        .learning-title { font-weight: 700; color: var(--col-herencia); margin-bottom: 4px; }
        
        /* 6. VÍNCULOS Y TRANSICIÓN (Bloque Especial) */
        .transition-wrapper {
            position: relative;
            background-color: white;
            border: 2px solid #E0E6ED;
            border-radius: var(--radius-soft);
            padding: var(--sp-xl);
            margin-bottom: var(--sp-xxl);
        }

        .mascot-thinking {
            position: absolute;
            top: -50px;
            right: 40px;
            width: 110px;
            height: auto;
            z-index: 5;
        }
        @media (max-width: 768px) { .mascot-thinking { position: relative; top: auto; right: auto; margin: 0 auto var(--sp-md); display: block; } }

        /* 7. FAQ (Mismo estilo) */
        .faq-wrapper { max-width: 900px; margin: 0 auto; }
        .faq-title { text-align: center; font-size: 2rem; color: var(--col-bilbao); margin-bottom: var(--sp-xl); }
        .faq-item { border-bottom: 1px solid #E5E5E5; }
        .faq-question { width: 100%; text-align: left; padding: 20px 0; background: none; border: none; font-size: 1.15rem; font-weight: 600; color: var(--col-herencia); display: flex; justify-content: space-between; align-items: center; cursor: pointer; transition: color 0.2s; }
        .faq-question:hover { color: var(--col-bilbao); }
        .plus-icon { font-size: 1.5rem; color: var(--col-espiritu); transition: 0.3s; }
        .faq-question[aria-expanded="true"] .plus-icon { transform: rotate(45deg); color: var(--col-bilbao); }
        .faq-answer { max-height: 0; overflow: hidden; transition: max-height 0.4s ease; color: var(--col-texto); font-size: 1rem; }
        .faq-question[aria-expanded="true"] + .faq-answer { padding-bottom: 24px; }

        /* 8. CTA FINAL (Mascota Feliz) */
        .cta-section { padding: var(--sp-xxl) var(--sp-md); background-color: var(--bg-global); text-align: center; border-top: 1px solid var(--col-borde); }
        .cta-container { max-width: 800px; margin: 0 auto; display: flex; flex-direction: column; align-items: center; }
        .mascot-happy { width: 140px; margin-bottom: var(--sp-md); transition: 0.3s; }
        .mascot-happy:hover { transform: scale(1.1) translateY(-10px); }
        
        .cta-h2 { font-size: 2rem; color: var(--col-bilbao); margin-bottom: var(--sp-sm); }
        .cta-p { font-size: 1.1rem; margin-bottom: var(--sp-lg); max-width: 600px; }
        
        .btn-primary { display: inline-block; background-color: var(--col-bilbao); color: white; padding: 16px 32px; border-radius: 50px; font-weight: 600; font-size: 1.1rem; transition: 0.3s; box-shadow: 0 8px 20px rgba(77,138,187,0.3); }
        .btn-primary:hover { background-color: var(--col-espiritu); transform: translateY(-2px); }

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
        
        <!-- 1. HERO SUAVE Y JUGUETÓN -->
        <section class="preschool-hero">
            <div class="hero-bg"></div>
            <div class="hero-overlay"></div>
            <div class="hero-content">
                <h1 class="hero-title">Primaria</h1>
                <p class="hero-subtitle">La primaria donde tu hija o hijo entiende lo que aprende y quiere volver cada día.</p>
            </div>
        </section>

        <!-- 2. MINI MENÚ -->
        <div class="sticky-nav-container">
            <nav class="sticky-nav">
                <a href="#cuestiona" class="sticky-link">Preguntar</a>
                <a href="#persona" class="sticky-link">Persona</a>
                <a href="#proyectos" class="sticky-link">Proyectos</a>
                <a href="#idiomas" class="sticky-link">Idiomas</a>
                <a href="#faq" class="sticky-link">Preguntas</a>
            </nav>
        </div>

        <!-- 3. RESUMEN CON MASCOTA (Style Certificaciones - Mascota Grande) -->
        <section id="resumen" class="intro-section">
            <div class="intro-icon-container">
                <!-- Mascota Kid Alex Salta como ícono -->
                <img src="../../assets/img/niveles-academicos/primaria/kid-alex-salta.png" alt="Kid Alex Salta" class="intro-icon-img">
            </div>
            <div class="intro-text-content">
                <p class="intro-lead">En la primaria del Colegio Bilbao, aprender no significa solo sacar buenas calificaciones.</p>
                <p class="intro-text">Significa entender, cuestionar, convivir y hacerse responsable de lo que se piensa y se hace. Las niñas y los niños aprenden en aulas vivas, rodeadas de naturaleza, proyectos y experiencias significativas. Queremos que construyan bases académicas sólidas y, al mismo tiempo, desarrollen criterio, autonomía y sensibilidad humana.</p>
            </div>
        </section>

        <!-- 4. CUESTIONA (Orgánico) -->
        <section id="cuestiona" class="section-container">
            <div class="organic-block">
                <div class="organic-text">
                    <img src="../../assets/img/niveles-academicos/primaria/kid-alex-cuestiona.png" alt="Alex Cuestiona" class="mascot-float-small">
                    <h2 class="section-title">Una primaria donde tus hijas e hijos se atreven a preguntar más</h2>
                    <div class="section-text">
                        <p>En la primaria del Colegio Bilbao las preguntas no se castigan, se celebran. Sabemos que preguntar es una señal de curiosidad y de pensamiento vivo.</p>
                        <p>Las clases se diseñan para que las niñas y los niños no solo escuchen, también exploren, duden y contrasten ideas. El profesorado abre espacios de diálogo donde pueden decir “no entendí” sin miedo ni vergüenza.</p>
                        <p>El error se entiende como parte natural del aprendizaje. Queremos que salgan de clase con más ganas de entender el mundo, no con menos.</p>
                    </div>
                </div>
                <div class="organic-img-wrapper">
                    <img src="../../assets/img/niveles-academicos/primaria/clase-sexto.jpg" alt="Clase de sexto grado">
                </div>
            </div>
        </section>

        <!-- 5. PERSONA (Orgánico con Icono Esculpe) -->
        <section id="persona" class="section-container">
             <div class="organic-block"> <!-- Orden normal: Texto Izq, Imagen Der -->
                <div class="organic-text">
                    <div style="text-align: center; margin-bottom: 20px;">
                        <img src="../../assets/img/niveles-academicos/primaria/kid-alex-esculpe.png" alt="Alex Esculpe" class="mascot-float-center">
                    </div>
                    <h2 class="section-title">Una primaria donde tu hija o hijo no es solo un número</h2>
                    <div class="section-text">
                        <p>En la primaria del Colegio Bilbao miramos primero a la persona y después a la calificación. Nos interesa cómo aprende, qué siente y qué necesita tu hija o hijo en cada etapa.</p>
                        <p>Cuidamos grupos cercanos para conocer su historia, su manera de pensar y su forma de convivir. El profesorado da seguimiento real, más allá de una boleta.</p>
                        <p>Recibimos familias que buscan una escuela donde sus hijas e hijos sean vistos y escuchados de verdad. Aquí las decisiones se toman pensando en personas concretas.</p>
                    </div>
                </div>
                <div class="organic-img-wrapper img-vertical">
                    <img src="../../assets/img/niveles-academicos/primaria/alumna-feliz.jpg" alt="Alumna feliz en clase">
                </div>
            </div>
        </section>

        <!-- 6. PROYECTOS (Grid de Tarjetas) -->
        <section id="proyectos" class="learning-section">
            <div class="organic-block">
                <div class="organic-img-wrapper">
                     <img src="../../assets/img/niveles-academicos/primaria/alumna-escribiendo.jpg" alt="Alumna tomando notas">
                </div>
                <div class="organic-text">
                    <h2 class="section-title">Proyectos que unen ciencias, humanidades, artes y tecnología</h2>
                    <div class="section-text">
                        <p>En primaria, muchas unidades se trabajan como proyectos que cruzan varias áreas. Un experimento de ciencias puede terminar en un mural, una maqueta y una presentación oral.</p>
                        <p>Buscamos que vean conexiones entre números, lectura, historia, arte y tecnología. Así desarrollan comprensión profunda y no sienten las materias como compartimentos separados.</p>
                    </div>
                    
                    <div class="learning-grid">
                        <div class="learning-card">
                            <span class="learning-icon">🔬</span>
                            <div class="learning-title">Ciencias</div>
                        </div>
                        <div class="learning-card">
                            <span class="learning-icon">📚</span>
                            <div class="learning-title">Humanidades</div>
                        </div>
                        <div class="learning-card">
                            <span class="learning-icon">🎨</span>
                            <div class="learning-title">Artes</div>
                        </div>
                         <div class="learning-card">
                            <span class="learning-icon">💻</span>
                            <div class="learning-title">Tecnología</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 7. IDIOMAS Y MUNDO (Bloque Especial) -->
        <section id="idiomas" class="section-container">
            <div class="transition-wrapper">
                <img src="../../assets/img/niveles-academicos/primaria/kid-alex-explora.png" alt="Kid Alex Explora" class="mascot-thinking">
                
                <h2 class="section-title text-center" style="margin-bottom: 30px;">Idiomas y mundo global</h2>
                
                <div class="organic-block">
                     <div class="organic-img-wrapper" style="height: 300px;">
                        <img src="../../assets/img/niveles-academicos/primaria/alumnas-abrazadas.jpg" alt="Amigas felices">
                    </div>
                    <div class="organic-text">
                        <p>En primaria, el inglés se vive más allá del libro y los ejercicios aislados. Se utilizan materiales de National Geographic Learning que conectan con culturas, ciencia y realidades actuales.</p>
                        <p>El objetivo es que comprendan, se expresen y pierdan el miedo a usar el idioma. Desde pequeños, se familiarizan con un mundo global, diverso y en constante cambio.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- 8. FAQ -->
        <section id="faq" class="section-container">
            <div class="faq-wrapper">
                <h2 class="faq-title" style="text-align:center; margin-bottom:30px; color:var(--col-bilbao);">Preguntas frecuentes sobre nuestra primaria</h2>
                <div class="faq-item">
                    <button class="faq-question" aria-expanded="false">¿Cómo manejan las tareas para que no saturen las tardes? <span class="plus-icon">+</span></button>
                    <div class="faq-answer"><p>En la primaria del Colegio Bilbao no dejamos tareas para casa de forma habitual. El trabajo se realiza y se acompaña dentro del horario escolar, con tiempo para preguntas y práctica. En casa priorizamos descanso, juego, convivencia familiar y tiempo para ser niñas y niños.</p></div>
                </div>
                <div class="faq-item">
                    <button class="faq-question" aria-expanded="false">¿Qué pasa si mi hijo tiene dificultades en alguna materia? <span class="plus-icon">+</span></button>
                    <div class="faq-answer"><p>El equipo docente detecta dificultades, las registra y junto con la familia se acuerdan apoyos y estrategias concretas para acompañar mejor su proceso.</p></div>
                </div>
                 <div class="faq-item">
                    <button class="faq-question" aria-expanded="false">¿Aceptan estudiantes que vienen de otras escuelas en grados intermedios? <span class="plus-icon">+</span></button>
                    <div class="faq-answer"><p>Sí, recibimos estudiantes de otras escuelas cuando hay lugares disponibles en el grado correspondiente. Acompañamos su integración académica y social con especial cuidado durante los primeros meses.</p></div>
                </div>
                <div class="faq-item">
                    <button class="faq-question" aria-expanded="false">¿Cómo es un día típico en la primaria? <span class="plus-icon">+</span></button>
                    <div class="faq-answer"><p>Combina clases en aula, trabajo por proyectos, momentos al aire libre y espacios para expresión artística y deportiva. Buscamos un ritmo equilibrado, con estructura clara, pero también tiempos de juego, convivencia y respiro emocional.</p></div>
                </div>
                 <div class="faq-item">
                    <button class="faq-question" aria-expanded="false">¿Qué beneficios tiene para primaria estar en un campus con bosque? <span class="plus-icon">+</span></button>
                    <div class="faq-answer"><p>El entorno natural ayuda a regular emociones, concentrarse mejor y liberar energía de manera saludable. Además, permite proyectos de ciencias, arte y reflexión que difícilmente ocurren en espacios completamente cerrados.</p></div>
                </div>
            </div>
        </section>

        <!-- 9. CTA FINAL (KID ALEX SALTA) -->
        <section class="cta-section">
            <div class="cta-container">
                <img src="../../assets/img/niveles-academicos/primaria/kid-alex-salta.png" alt="Kid Alex Salta" class="mascot-happy">
                <div class="cta-content">
                    <h2 class="cta-h2">Ven a conocernos</h2>
                    <p class="cta-p">Si esta forma de entender la primaria resuena contigo, lo mejor es verla en vivo.</p>
                    <a href="https://wa.me/525614612682?text=Hola,%20acabo%20de%20ver%20la%20página%20de%20primaria,%20me%20gustó%20y%20quiero%20conocer%20el%20colegio%20en%20una%20visita%20guiada." target="_blank" class="btn-primary">
                        Agenda una visita a primaria
                    </a>
                </div>
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

        // FAQ Logic
        faqQuestions.forEach(question => {
            question.addEventListener('click', () => {
                const isExpanded = question.getAttribute('aria-expanded') === 'true';
                question.setAttribute('aria-expanded', !expanded);
                const answer = question.nextElementSibling;
                if (!isExpanded) {
                    answer.style.maxHeight = answer.scrollHeight + "px";
                } else {
                    answer.style.maxHeight = null;
                }
            });
        });
        
        window.addEventListener('resize', () => {
             if(window.innerWidth >= 1024) {
                 document.querySelectorAll('.footer-links').forEach(el => el.style.display = 'block');
             } else {
                 document.querySelectorAll('.footer-links').forEach(el => el.style.display = 'none');
             }
        });
    </script>
</body>
</html>