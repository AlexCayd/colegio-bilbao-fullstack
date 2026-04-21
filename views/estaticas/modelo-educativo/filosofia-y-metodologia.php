<!DOCTYPE html>
<html lang="es-MX">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Filosofía humanista y metodologías activas por nivel en el Colegio Bilbao. Educación con sentido desde preescolar hasta preparatoria.">
    <meta name="robots" content="index, follow">
    
    <title>Filosofía y Metodología | Colegio Bilbao</title>

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
        /* --- 1. VARIABLES & SISTEMA VISUAL --- */
        :root {
            /* Paleta Primaria */
            --col-bilbao: #4D8ABB;
            --col-spirit: #7DC6E5;
            --col-heritage: #374C69;
            --col-accent-pop: #F1C400;
            
            /* Mapeo de variables */
            --col-herencia: #374C69;
            --col-espiritu: #7DC6E5;
            --col-texto: #1A202C;
            --col-borde: rgba(77, 138, 187, 0.15);
            
            /* Fondo Global */
            --bg-global: #F9FBFE;
            --bg-dark: #0F172A;
            
            /* Neutros */
            --text-dark: #1A202C;
            --text-gray: #4A5568;
            --text-light: #FFFFFF;

            /* Espaciado y Dimensiones */
            --container-width: 1280px;
            --max-width: 1280px;
            --header-height: 80px;
            --radius-pill: 100px;
            --radius-card: 20px;
            --gutter-mobile: 24px;
            --gutter-desktop: 64px;

            /* Variables de espaciado Editorial */
            --sp-lg: 80px;
            --sp-xl: 120px;

            /* Sombras & Efectos */
            --shadow-sm: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            --shadow-md: 0 10px 15px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -2px rgba(0, 0, 0, 0.04);
            --shadow-float: 0 20px 40px -5px rgba(77, 138, 187, 0.15), 0 10px 15px -5px rgba(0, 0, 0, 0.05);

            /* Transiciones */
            --transition-std: 0.4s cubic-bezier(0.2, 0.8, 0.2, 1);
        }

        /* --- 2. RESET & BASE --- */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            font-family: 'Montserrat', sans-serif;
            background-color: var(--bg-global);
            color: var(--text-dark);
            line-height: 1.6;
            display: flex; flex-direction: column; min-height: 100vh;
            overflow-x: hidden;
        }
        body.no-scroll { overflow: hidden; }
        
        h1, h2, h3 { line-height: 1.1; letter-spacing: -0.02em; }
        p { letter-spacing: -0.01em; }

        .skip-link {
            position: absolute; top: -40px; left: 0; background: var(--col-bilbao); color: white; padding: 8px; z-index: 2000; transition: top 0.3s;
        }
        .skip-link:focus { top: 0; }
        a { text-decoration: none; color: inherit; transition: var(--transition-std); }
        ul { list-style: none; }
        button { font-family: inherit; border: none; background: none; cursor: pointer; }
        img { max-width: 100%; display: block; height: auto; }

        .container { width: 100%; max-width: var(--container-width); margin: 0 auto; padding: 0 var(--gutter-mobile); position: relative; z-index: 2; }
        @media (min-width: 1024px) { .container { padding: 0 var(--gutter-desktop); } }

        /* --- HEADER & MENU STYLES --- */
        .header-bar { position: fixed; top: 0; left: 0; width: 100%; height: var(--header-height); background-color: rgba(255, 255, 255, 0.95); backdrop-filter: blur(16px); z-index: 1000; border-bottom: 1px solid rgba(77, 138, 187, 0.1); display: flex; align-items: center; justify-content: center; padding: 0 var(--sp-md); transition: background-color 0.3s; }
        .header-inner { width: 100%; max-width: 1280px; display: flex; justify-content: space-between; align-items: center; }
        .logo-link { display: flex; align-items: center; z-index: 1002; height: 100%; }
        .logo-img { height: 67px; width: auto; object-fit: contain; }
        .header-controls { display: flex; align-items: center; gap: 24px; z-index: 1002; }
        .lang-switch { font-size: 0.9rem; font-weight: 600; color: var(--col-herencia); }
        .lang-switch span.active { color: var(--col-bilbao); text-decoration: underline; text-underline-offset: 4px; }
        .menu-trigger { display: flex; align-items: center; justify-content: center; padding: 8px; color: var(--col-bilbao); }
        .hamburger-icon { width: 28px; height: 20px; position: relative; display: flex; flex-direction: column; justify-content: space-between; }
        .hamburger-icon span { display: block; width: 100%; height: 2px; background-color: var(--col-bilbao); border-radius: 2px; transition: all 0.3s ease; }
        .menu-overlay { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background-color: var(--bg-global); z-index: 2000; display: flex; flex-direction: column; opacity: 0; visibility: hidden; transition: opacity 0.4s ease, visibility 0.4s ease; overflow-y: auto; }
        .menu-overlay[aria-hidden="false"] { opacity: 1; visibility: visible; }
        .overlay-header { flex-shrink: 0; height: var(--header-height); display: flex; align-items: center; justify-content: center; padding: 0 24px; border-bottom: 1px solid rgba(77, 138, 187, 0.1); }
        .close-btn { width: 44px; height: 44px; position: relative; color: var(--col-bilbao); }
        .close-btn::before, .close-btn::after { content: ''; position: absolute; top: 50%; left: 50%; width: 24px; height: 2px; background-color: currentColor; }
        .close-btn::before { transform: translate(-50%, -50%) rotate(45deg); }
        .close-btn::after { transform: translate(-50%, -50%) rotate(-45deg); }
        .overlay-content { flex-grow: 1; width: 100%; max-width: 800px; margin: 0 auto; padding: 48px 24px; }
        .nav-accordion-item { border-bottom: 1px solid var(--col-borde); }
        .nav-accordion-trigger { width: 100%; display: flex; justify-content: space-between; align-items: center; padding: 24px 0; font-size: 1.5rem; font-weight: 700; color: var(--col-herencia); text-align: left; transition: color 0.2s; }
        .nav-accordion-trigger:hover, .nav-accordion-trigger[aria-expanded="true"] { color: var(--col-bilbao); }
        .nav-accordion-trigger .chevron { transition: transform 0.3s ease; font-size: 1rem; }
        .nav-accordion-trigger[aria-expanded="true"] .chevron { transform: rotate(180deg); }
        .nav-submenu { display: none; padding-bottom: 24px; padding-left: 24px; border-left: 2px solid var(--col-espiritu); margin-left: 4px; }
        .nav-submenu li { margin-bottom: 8px; }
        .nav-submenu a { font-size: 1.1rem; color: var(--col-texto); font-weight: 500; }
        .nav-submenu a:hover { color: var(--col-bilbao); }

        /* --- FOOTER STYLES --- */
        footer { background-color: white; border-top: 1px solid var(--border-light); padding: 4rem 0 0 0; color: var(--text-dark); font-size: 0.95rem; position: relative; z-index: 2; }
        .footer-container { width: 100%; max-width: 1280px; margin: 0 auto; padding: 0 24px; }
        @media (min-width: 1024px) { .footer-container { padding: 0 64px; } }
        .footer-header { display: flex; flex-direction: column; align-items: flex-start; margin-bottom: 2rem; }
        @media (min-width: 1024px) { .footer-header { flex-direction: row; justify-content: space-between; align-items: center; margin-bottom: 3rem; } }
        .footer-logo-img { height: 50px; width: auto; display: block; }
        .footer-social-desktop { display: none; align-items: center; gap: 12px; color: var(--col-heritage); font-weight: 600; font-size: 0.9rem; }
        @media (min-width: 1024px) { .footer-social-desktop { display: flex; } }
        .social-link { display: flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 50%; background: rgba(77, 138, 187, 0.1); color: var(--col-bilbao); transition: var(--transition-std); }
        .social-link:hover { background: var(--col-bilbao); color: white; transform: translateY(-2px); }
        .social-icon { width: 20px; height: 20px; fill: currentColor; }
        .footer-grid { display: grid; grid-template-columns: 1fr; gap: 2rem; padding-bottom: 3rem; }
        @media (min-width: 600px) { .footer-grid { grid-template-columns: 1fr 1fr; } }
        @media (min-width: 1024px) { .footer-grid { grid-template-columns: 1.5fr 1fr 1fr 1fr; gap: 3rem; } }
        .footer-desc { color: var(--text-gray); margin-bottom: 1.5rem; max-width: 300px; }
        .footer-contact p { margin-bottom: 0.5rem; font-size: 0.9rem; }
        .footer-contact a { font-weight: 600; color: var(--col-heritage); }
        .footer-contact a:hover { text-decoration: underline; color: var(--col-bilbao); }
        .footer-social-mobile { display: flex; align-items: center; gap: 10px; margin-top: 2rem; font-weight: 600; color: var(--col-heritage); }
        @media (min-width: 1024px) { .footer-social-mobile { display: none; } }
        .footer-col-title { width: 100%; display: flex; justify-content: space-between; align-items: center; font-weight: 700; color: var(--col-bilbao); font-size: 1.1rem; margin-bottom: 1rem; text-align: left; }
        .footer-links li { margin-bottom: 0.8rem; }
        .footer-links a { color: var(--text-gray); }
        .footer-links a:hover { color: var(--col-bilbao); text-decoration: underline; }
        @media (max-width: 1023px) { .footer-links { display: none; padding-bottom: 1rem; } .footer-links.visible { display: block; } .chevron { transition: transform 0.3s; font-size: 0.8rem; } }
        @media (min-width: 1024px) { .chevron { display: none; } .footer-links { display: block !important; } .footer-col-title { cursor: default; margin-bottom: 1.5rem; } }
        .footer-legal { border-top: 1px solid rgba(0,0,0,0.05); padding: 2rem 0; background-color: #f4f6f9; font-size: 0.85rem; color: var(--text-gray); text-align: center; }
        .legal-links { margin-bottom: 1rem; }
        .legal-links a { margin: 0 5px; color: var(--text-gray); }
        .legal-links a:hover { color: var(--col-bilbao); text-decoration: underline; }
        @media (min-width: 1024px) { .footer-legal { display: flex; justify-content: space-between; padding: 2rem var(--gutter-desktop); text-align: left; } .legal-links { margin-bottom: 0; } }

        /* --- PAGE SPECIFIC STYLES --- */
        
        main { padding-top: var(--header-height); }

        /* 1. HERO SPLIT (MANTENIDO) */
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
            width: 100%; max-width: var(--container-width); margin: 0 auto;
            padding: 0 var(--gutter-mobile); position: relative; z-index: 2; align-items: center;
        }
        @media (min-width: 1024px) { .hero-grid { grid-template-columns: 0.9fr 1.1fr; gap: 5rem; } }

        /* Columna Texto Hero */
        .hero-text-col { position: relative; z-index: 3; }
        .hero-supertitle {
            font-size: clamp(3rem, 7vw, 5rem); font-weight: 900; line-height: 1; color: var(--col-bilbao);
            margin-bottom: 1.5rem; margin-left: -5px; letter-spacing: -0.04em;
            opacity: 0; animation: fadeUp 1s ease forwards 0.2s;
        }
        .hero-desc-p {
            font-size: 1.15rem; line-height: 1.6; color: var(--text-gray); max-width: 550px;
            margin-bottom: 0; opacity: 0; animation: fadeUp 1s ease forwards 0.6s;
        }

        /* Columna Imagen Hero */
        .hero-img-col {
            position: relative; height: 500px; display: flex; align-items: center; justify-content: center;
            opacity: 0; animation: scaleIn 1.2s cubic-bezier(0.2, 0.8, 0.2, 1) forwards 0.3s;
        }
        .hero-img-main {
            width: 100%; height: auto; border-radius: 30px;
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
        }
        .float-stat { font-size: 2rem; font-weight: 900; color: var(--col-bilbao); line-height: 1; margin-bottom: 5px; }
        .float-label { font-size: 0.9rem; font-weight: 700; color: var(--col-herencia); text-transform: uppercase; letter-spacing: 0.5px; }

        @keyframes floatCard { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }
        @keyframes fadeUp { to { opacity: 1; transform: translateY(0); } from { opacity: 0; transform: translateY(40px); } }
        @keyframes scaleIn { to { opacity: 1; transform: scale(1); } from { opacity: 0; transform: scale(0.95); } }

        /* 2. MANIFIESTO (MANTENIDO) */
        .manifesto-section { padding: var(--sp-xl) 0; background-color: white; position: relative; }
        .manifesto-container { 
            max-width: 900px; margin: 0 auto; text-align: left;
            display: grid; grid-template-columns: 1fr; gap: 3rem;
        }
        @media (min-width: 1024px) { 
            .manifesto-container { grid-template-columns: 0.4fr 1fr; align-items: start; }
            .manifesto-sticky { position: sticky; top: 120px; }
        }
        .manifesto-label { font-size: 0.9rem; font-weight: 700; color: var(--col-spirit); text-transform: uppercase; letter-spacing: 2px; margin-bottom: 1rem; display: block; }
        .manifesto-lead {
            font-size: clamp(2rem, 4vw, 3rem); font-weight: 800; color: var(--col-bilbao); line-height: 1.1; margin-bottom: 1rem;
        }
        .manifesto-text { font-size: 1.25rem; color: var(--text-gray); line-height: 1.8; font-weight: 300; }
        .manifesto-text p { margin-bottom: 1.5rem; }
        
        /* 3. FILOSOFÍA HUMANISTA (MANTENIDA) */
        .philosophy-section {
            background-color: var(--col-bilbao); color: white;
            padding: var(--sp-xl) 0; position: relative; overflow: hidden;
        }
        .philo-watermark {
            position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
            font-size: 15vw; font-weight: 900; color: rgba(255,255,255,0.05);
            line-height: 1; pointer-events: none; white-space: nowrap; z-index: 0;
        }
        .philo-grid {
            display: grid; grid-template-columns: 1fr; gap: 4rem;
            position: relative; z-index: 2; align-items: center;
        }
        @media (min-width: 1024px) { .philo-grid { grid-template-columns: 1fr 1fr; } }
        .philo-title { font-size: 3rem; font-weight: 800; margin-bottom: 1.5rem; color: white; }
        .philo-text { font-size: 1.2rem; opacity: 0.9; line-height: 1.6; }
        .philo-highlight {
            font-size: 1.5rem; font-weight: 700; color: var(--col-spirit);
            margin-top: 2rem; border-left: 4px solid var(--col-spirit); padding-left: 20px;
        }

        /* 4. METODOLOGÍAS (STICKY STACK RESTAURADO) */
        .methods-intro-title {
            padding: 80px 0 60px 0; text-align: center;
            background-color: #F9FBFE; /* Dark background to prepare for stack */
            color: white; margin-bottom: 0;
        }
        .methods-intro-title h2 { font-size: 2.5rem; font-weight: 800; margin-bottom: 0.5rem; color: var(--col-spirit); }
        .methods-intro-title p { font-size: 1.2rem; color: #4D8ABB; }

        .methods-wrapper { background-color: #0b1120; }
        
        .method-card-full {
            position: sticky; top: 0; height: 100vh; width: 100%;
            display: flex; align-items: center; justify-content: center;
            overflow: hidden; box-shadow: 0 -10px 40px rgba(0,0,0,0.2); 
        }

        /* Colors matching levels */
        .card-1 { background-color: #F9FBFE; color: var(--col-bilbao); z-index: 1; }
        .card-2 { background-color: #7DC6E5; color: white; z-index: 2; }
        .card-3 { background-color: #4D8ABB; color: white; z-index: 3; }
        .card-4 { background-color: #374C69; color: white; z-index: 4; }

        .method-inner {
            display: grid; grid-template-columns: 1fr; width: 100%; height: 100%;
            max-width: 1600px; margin: 0 auto;
        }
        @media (min-width: 1024px) { .method-inner { grid-template-columns: 1fr 1fr; } }

        .method-visual { position: relative; height: 50vh; overflow: hidden; }
        @media (min-width: 1024px) { .method-visual { height: 100vh; } }
        .method-visual img { width: 100%; height: 100%; object-fit: cover; animation: slowPan 20s infinite alternate; }
        @keyframes slowPan { from {transform: scale(1);} to {transform: scale(1.1);} }

        .method-info {
            padding: 40px; display: flex; flex-direction: column; justify-content: center; position: relative;
        }
        @media (min-width: 1024px) { .method-info { padding: 80px; } }

        .big-number { font-size: 8rem; font-weight: 900; opacity: 0.15; position: absolute; top: 20px; left: 20px; line-height: 1; }
        .method-level { text-transform: uppercase; letter-spacing: 4px; font-weight: 700; font-size: 1rem; margin-bottom: 1rem; opacity: 0.8; }
        .method-name { font-size: clamp(2.5rem, 6vw, 4.5rem); font-weight: 900; line-height: 0.9; margin-bottom: 2rem; letter-spacing: -0.03em; }
        .method-desc { font-size: 1.2rem; max-width: 500px; line-height: 1.6; font-weight: 400; }

        /* 5. AULA & RELACIÓN (DUAL GRID MEJORADO) */
        .ecosystem-section { padding: var(--sp-xl) 0; background: white; position: relative; }
        .ecosystem-grid { display: grid; gap: 2rem; grid-template-columns: 1fr; }
        @media (min-width: 1024px) { .ecosystem-grid { grid-template-columns: 1fr 1fr; gap: 4rem; } }

        .eco-card {
            background: rgba(255,255,255,0.7); backdrop-filter: blur(10px);
            border-radius: var(--radius-card); padding: 3.5rem; position: relative;
            overflow: hidden; border: 1px solid var(--col-borde); transition: transform 0.3s;
        }
        .eco-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-float); }

        /* Estilo diferenciado */
        .eco-card.aula { background: linear-gradient(135deg, #f0f9ff 0%, #ffffff 100%); }
        .eco-card.relacion { background: linear-gradient(135deg, #fffbeb 0%, #ffffff 100%); }

        .eco-icon { font-size: 3rem; margin-bottom: 1.5rem; display: block; }
        .eco-title { font-size: 2rem; font-weight: 800; color: var(--col-herencia); margin-bottom: 1rem; }
        .eco-desc { font-size: 1.1rem; color: var(--text-gray); margin-bottom: 2rem; }
        .eco-list { list-style: none; padding: 0; }
        .eco-list li {
            margin-bottom: 1rem; padding-left: 2rem; position: relative;
            font-size: 1.05rem; color: var(--text-dark);
        }
        .eco-list li::before {
            content: ''; position: absolute; left: 0; top: 10px;
            width: 8px; height: 8px; border-radius: 50%; background: var(--col-bilbao);
        }

        /* 6. FAQ & CTA (ESTILO VIDA HOMOLOGADO) */
        .faq-section { padding: var(--sp-xl) 0; background-color: var(--bg-global); }
        .faq-wrapper { max-width: 800px; margin: 0 auto 5rem auto; }
        
        details { margin-bottom: 1.5rem; background: white; border-radius: 12px; border: 1px solid rgba(0,0,0,0.05); box-shadow: 0 4px 12px rgba(0,0,0,0.02); transition: all 0.3s ease; }
        details:hover { box-shadow: 0 8px 16px rgba(0,0,0,0.05); }
        summary { padding: 1.5rem 2rem; font-weight: 600; font-size: 1.1rem; color: var(--col-bilbao); cursor: pointer; list-style: none; position: relative; padding-right: 4rem; }
        summary::-webkit-details-marker { display: none; }
        summary::after { content: '+'; position: absolute; right: 2rem; top: 50%; transform: translateY(-50%); font-size: 1.5rem; font-weight: 300; color: var(--col-spirit); transition: transform 0.3s; }
        details[open] summary::after { transform: translateY(-50%) rotate(45deg); }
        .faq-ans { padding: 0 2rem 2rem 2rem; color: var(--text-gray); line-height: 1.7; }

        .cta-final {
            background: white; max-width: 900px; margin: 0 auto;
            padding: 4rem 2rem; text-align: center; border-radius: var(--radius-card);
            box-shadow: 0 20px 60px rgba(0,0,0,0.08); border: 1px solid rgba(0,0,0,0.02);
        }
        .cta-heading { font-size: 2rem; font-weight: 800; color: var(--col-bilbao); margin-bottom: 1rem; }
        .btn-glow {
            display: inline-block; background-color: var(--col-bilbao); color: white;
            padding: 1.2rem 3rem; border-radius: var(--radius-pill); font-weight: 700;
            font-size: 1.1rem; margin-top: 2rem; transition: all 0.3s;
            box-shadow: 0 10px 20px rgba(77, 138, 187, 0.3);
        }
        .btn-glow:hover { transform: translateY(-3px); box-shadow: 0 15px 30px rgba(77, 138, 187, 0.4); background-color: var(--col-herencia); }

        .reveal { opacity: 0; transform: translateY(40px); transition: all 1s var(--ease-expo); }
        .reveal.active { opacity: 1; transform: translateY(0); }
        @keyframes fadeUp { to { opacity: 1; transform: translateY(0); } from { opacity: 0; transform: translateY(40px); } }
        @keyframes scaleIn { to { opacity: 1; transform: scale(1); } from { opacity: 0; transform: scale(0.95); } }

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
                <!-- Navigation Items -->
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
        
        <!-- 1. HERO SPLIT (MANTENIDO) -->
        <section class="hero-section">
            <div class="hero-blob-bg"></div>
            
            <div class="hero-grid">
                <!-- Texto -->
                <div class="hero-text-col">
                    <span class="hero-supertitle" style="display:block; margin-bottom:1rem;">Filosofía y Metodología</span>
                    <p class="hero-desc-p">Una educación con sentido: humanismo, metodologías por etapa y aprendizaje activo en el Colegio Bilbao.</p>
                </div>

                <!-- Imagen -->
                <div class="hero-img-col">
                    <!-- PLACEHOLDER IMAGEN HERO -->
                    <img src="../../assets/img/modelo-educativo/filosofia-y-metodologia/alumno-y-banca.jpg" alt="Filosofía Educativa Bilbao" class="hero-img-main">
                    
                    <!-- Floating Card -->
                    <div class="hero-floating-card">
                        <div class="float-stat">Humanismo</div>
                        <div class="float-label">El centro es la persona.</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 2. MANIFIESTO (MANTENIDO) -->
        <section class="manifesto-section reveal">
            <div class="container manifesto-container">
                <div class="manifesto-sticky">
                    <span class="manifesto-label">Cada familia entiende el “cómo” y ve el “por qué”</span>
                </div>
                <div class="manifesto-content">
                    <h2 class="manifesto-lead">En el Colegio Bilbao, educar es formar personas, no solo completar programas.</h2>
                    <div class="manifesto-text">
                        <p>Nuestra filosofía es humanista y se vive con estructura, calidez y exigencia saludable. Trabajamos con metodologías claras por nivel, unidas por el <strong>Modelo VIDA</strong>.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- 3. FILOSOFÍA HUMANISTA (MANTENIDA) -->
        <section class="philosophy-section reveal">
            <div class="philo-watermark">HUMANISMO</div>
            <div class="container philo-grid">
                <div>
                    <h2 class="philo-title">Nuestra filosofía humanista</h2>
                    <p class="philo-text">Primero está la persona, después el sistema.</p>
                    <div class="philo-highlight">Cuidamos el aprendizaje académico y el desarrollo emocional, social y ético de cada estudiante.</div>
                </div>
                <!-- Imagen -->
                <div style="height: 300px; background: rgba(255,255,255,0.1); border-radius: 24px; border: 1px solid rgba(255,255,255,0.2);">
                    <!-- PLACEHOLDER IMAGEN HUMANISMO -->
                    <img src="../../assets/img/modelo-educativo/filosofia-y-metodologia/calidez.jpg" alt="Filosofía Humanista" style="width:100%; height:100%; object-fit:cover; border-radius:24px; opacity:0.8;">
                </div>
            </div>
        </section>

        <!-- 4. METODOLOGÍAS (STICKY STACK) -->
        <section>
             <!-- TITULO DE ENTRADA (MANTENIDO) -->
             <div class="methods-intro-title reveal">
                <h2>Metodologías por nivel</h2>
                <p>Lo que se ve en el estudiante</p>
            </div>
            
            <div class="methods-wrapper">
                <!-- Card 1: Preescolar -->
                <section class="method-card-full card-1">
                    <div class="method-inner">
                        <div class="method-info">
                            <span class="big-number">01</span>
                            <span class="method-level">Preescolar</span>
                            <h2 class="method-name">HighScope</h2>
                            <p class="method-desc">Impulsa aprendizaje activo. La niña o el niño elige, explora, construye y explica lo que hizo. Se fortalece autonomía, lenguaje y seguridad para aprender con alegría.</p>
                        </div>
                        <div class="method-visual">
                            <!-- PLACEHOLDER -->
                            <img src="../../assets/img/modelo-educativo/filosofia-y-metodologia/highscope.jpg" alt="Preescolar">
                        </div>
                    </div>
                </section>

                <!-- Card 2: Primaria -->
                <section class="method-card-full card-2">
                    <div class="method-inner">
                        <div class="method-visual">
                            <!-- PLACEHOLDER -->
                            <img src="../../assets/img/modelo-educativo/filosofia-y-metodologia/constructivismo.jpg" alt="Primaria">
                        </div>
                        <div class="method-info">
                            <span class="big-number" style="color:rgba(255,255,255,0.7)">02</span>
                            <span class="method-level">Primaria</span>
                            <h2 class="method-name">Enseñanza Formativa</h2>
                            <p class="method-desc">Acompaña el proceso mientras aprenden. Tu hija o hijo recibe retroalimentación frecuente y entiende qué mejorar. Reduce frustración y construye hábitos sólidos.</p>
                        </div>
                    </div>
                </section>

                <!-- Card 3: Secundaria -->
                <section class="method-card-full card-3">
                    <div class="method-inner">
                        <div class="method-info">
                            <span class="big-number" style="color:rgba(255,255,255,0.7)">03</span>
                            <span class="method-level">Secundaria</span>
                            <h2 class="method-name">Constructivismo Social</h2>
                            <p class="method-desc">Diálogo y colaboración. El estudiante aprende a argumentar, escuchar y construir acuerdos con otros. La convivencia se vuelve aprendizaje.</p>
                        </div>
                        <div class="method-visual">
                            <!-- PLACEHOLDER -->
                            <img src="../../assets/img/modelo-educativo/filosofia-y-metodologia/construc-soc.jpg" alt="Secundaria">
                        </div>
                    </div>
                </section>

                <!-- Card 4: Prepa -->
                <section class="method-card-full card-4">
                    <div class="method-inner">
                        <div class="method-visual">
                            <!-- PLACEHOLDER -->
                            <img src="../../assets/img/modelo-educativo/filosofia-y-metodologia/construccionismo.jpg" alt="Preparatoria">
                        </div>
                        <div class="method-info">
                            <span class="big-number" style="color:rgba(255,255,255,0.7)">04</span>
                            <span class="method-level">Preparatoria</span>
                            <h2 class="method-name">Construccionismo</h2>
                            <p class="method-desc">Proyectos y productos visibles. El joven aprende haciendo, corrigiendo y mostrando resultados. Preparación real para la universidad.</p>
                        </div>
                    </div>
                </section>
            </div>
        </section>

        <!-- 5. AULA & RELACIÓN (INNOVACIÓN: ECOSYSTEM) -->
        <section class="ecosystem-section">
            <div class="container ecosystem-grid">
                
                <!-- Card Aula -->
                <div class="eco-card aula reveal">
                    <span class="eco-icon">📚</span>
                    <h3 class="eco-title">Cómo se trabaja en el aula</h3>
                    <p class="eco-desc">Las clases se diseñan para que el aprendizaje se vea, se practique y se comprenda.</p>
                    <ul class="eco-list">
                        <li>Rutinas claras y estructura.</li>
                        <li>Trabajo por proyectos.</li>
                        <li>Momentos de reflexión guiada.</li>
                        <li>Evaluación para orientar.</li>
                    </ul>
                </div>

                <!-- Card Relación -->
                <div class="eco-card relacion reveal">
                    <span class="eco-icon">🤝</span>
                    <h3 class="eco-title">Relación maestro–alumno</h3>
                    <p class="eco-desc">En el Bilbao, la cercanía no es permisividad, es acompañamiento con dirección.</p>
                    <ul class="eco-list">
                        <li>Docente conoce a cada estudiante.</li>
                        <li>Comunicación clara.</li>
                        <li>Límites consistentes.</li>
                        <li>Colegio presente.</li>
                    </ul>
                </div>

            </div>
        </section>

        <!-- 6. FAQ & CTA (ESTILO VIDA HOMOLOGADO) -->
        <section class="faq-section">
            <div class="container">
                
                <div class="faq-wrapper reveal">
                    <h2 style="text-align:center; margin-bottom:3rem; font-weight:800; color:var(--col-bilbao); font-size:2rem;">Preguntas frecuentes</h2>
                    
                    <details>
                        <summary>¿Cómo se diferencia el Bilbao de otras escuelas?</summary>
                        <div class="faq-ans">Por coherencia metodológica por etapa y acompañamiento humano constante. Aquí el aprendizaje se vive con propósito y con comunidad.</div>
                    </details>
                    <details>
                        <summary>¿Qué significa “aprendizaje vivencial” en la práctica?</summary>
                        <div class="faq-ans">Significa aprender con experiencias, proyectos y retos con sentido. El estudiante entiende para qué aprende, y eso cambia su motivación.</div>
                    </details>
                    <details>
                        <summary>¿Cómo mantienen coherencia entre niveles?</summary>
                        <div class="faq-ans">El hilo conductor es VIDA: Vincula, Indaga, Descubre y Aporta. La forma cambia por edad, pero el sentido se mantiene.</div>
                    </details>
                </div>

                <div class="cta-final reveal">
                    <h2 class="cta-heading">¿Buscas una educación con sentido?</h2>
                    <p style="margin-bottom:2rem; font-size:1.1rem; color:var(--text-gray);">Si buscas una escuela K-12 en CDMX donde la filosofía humanista se viva con método y resultados, hablemos.</p>
                    <a href="https://wa.me/525614612682?text=Hola,%20acabo%20de%20ver%20la%20página%20de%20la%20 filosofía%20bilbao,%20me%20gustó%20y%20quiero%20conocer%20el%20colegio%20en%20una%20visita%20guiada." class="btn-glow">Agenda una visita</a>
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

    <!-- JS -->
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

        // REVEAL ANIMATION
        document.addEventListener('DOMContentLoaded', () => {
            const reveals = document.querySelectorAll('.reveal');
            const revealOnScroll = () => {
                const windowHeight = window.innerHeight;
                const elementVisible = 100;
                reveals.forEach((reveal) => {
                    const elementTop = reveal.getBoundingClientRect().top;
                    if (elementTop < windowHeight - elementVisible) {
                        reveal.classList.add('active');
                    }
                });
            };
            window.addEventListener('scroll', revealOnScroll);
            revealOnScroll();
        });
    </script>
</body>
</html>