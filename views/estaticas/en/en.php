<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Colegio Bilbao: We are currently working on this section to provide you with a better experience.">
    <meta name="robots" content="noindex, follow"> <!-- IMPORTANT: noindex -->
    
    <title>Colegio Bilbao | Coming Soon</title>

    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-8XVWCDM02P"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', 'G-8XVWCDM02P');
    </script>

    <!-- FAVICON -->
    <link rel="shortcut icon" href="../../assets/img/global/favicon.png" type="image/png">
    <link rel="icon" href="../../assets/img/global/favicon.png" type="image/png">

    <!-- Montserrat Typography -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">

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
            --sp-xs: 8px;
            --sp-sm: 16px;
            --sp-md: 24px;
            --sp-lg: 32px;
            --sp-xl: 48px;
            --sp-xxl: 64px;
            --font-main: 'Montserrat', sans-serif;
            --max-width: 1280px;
            --header-height: 90px;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: var(--font-main);
            background-color: var(--bg-global);
            color: var(--col-texto);
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        a { text-decoration: none; color: inherit; transition: color 0.2s ease; }
        ul { list-style: none; }
        button { font-family: inherit; border: none; background: none; cursor: pointer; }

        /* --- HEADER STYLES (No Menu) --- */
        .header-bar {
            position: fixed; top: 0; left: 0; width: 100%; height: var(--header-height);
            background-color: rgba(249, 251, 254, 0.95); backdrop-filter: blur(10px);
            z-index: 1000; border-bottom: 1px solid rgba(77, 138, 187, 0.1);
            display: flex; align-items: center; justify-content: center; padding: 0 var(--sp-md);
        }
        .header-inner { width: 100%; max-width: var(--max-width); display: flex; justify-content: space-between; align-items: center; }
        .logo-link { display: flex; align-items: center; z-index: 1002; height: 100%; }
        .logo-img { height: 67px; width: auto; object-fit: contain; }
        .header-controls { display: flex; align-items: center; gap: var(--sp-md); z-index: 1002; }
        
        /* Language Switcher Styles */
        .lang-switch { font-size: 0.9rem; font-weight: 600; color: var(--col-herencia); }
        .lang-switch a { color: var(--col-herencia); text-decoration: none; }
        .lang-switch a:hover { color: var(--col-bilbao); }
        .lang-switch span.active { color: var(--col-bilbao); text-decoration: underline; text-underline-offset: 4px; }

        /* --- CONTENT STYLES --- */
        main {
            flex-grow: 1; 
            padding-top: var(--header-height);
            display: flex;
            align-items: center;
            justify-content: center;
            padding-bottom: var(--sp-xxl);
            min-height: 60vh;
        }

        .construction-container {
            text-align: center;
            max-width: 800px; 
            padding: var(--sp-md);
        }

        .construction-image {
            max-width: 100%;
            height: auto;
            max-height: 400px; 
            border-radius: 16px; 
            box-shadow: 0 12px 24px rgba(77, 138, 187, 0.15); 
            margin-bottom: var(--sp-lg);
            object-fit: cover;
        }

        h1 {
            font-size: 2.5rem;
            color: var(--col-bilbao);
            margin-bottom: var(--sp-md);
            letter-spacing: -1px;
        }

        p.lead {
            font-size: 1.1rem;
            color: var(--col-herencia);
            margin-bottom: var(--sp-lg);
            line-height: 1.6;
        }

        .btn-primary {
            display: inline-block;
            background-color: var(--col-bilbao);
            color: var(--col-blanco);
            padding: 14px 32px;
            border-radius: 50px; 
            font-weight: 600;
            font-size: 1rem;
            transition: background-color 0.3s ease, transform 0.2s ease;
            box-shadow: 0 4px 12px rgba(77, 138, 187, 0.2);
        }

        .btn-primary:hover {
            background-color: var(--col-herencia);
            transform: translateY(-2px);
        }

        /* --- FOOTER STYLES --- */
        footer { background-color: var(--bg-global); border-top: 1px solid var(--col-borde); padding-top: var(--sp-xl); font-size: 0.95rem; color: var(--col-herencia); margin-top: auto; }
        .footer-container { width: 100%; max-width: var(--max-width); margin: 0 auto; padding: 0 var(--sp-md); }
        
        .footer-header { display: flex; flex-direction: column; margin-bottom: var(--sp-lg); align-items: flex-start; }
        .footer-logo-link { display: inline-block; margin-bottom: var(--sp-sm); }
        .footer-logo-img { height: 77px; width: auto; object-fit: contain; display: block; }
        
        .footer-social-desktop { display: none; }
        .social-link { display: inline-flex; align-items: center; justify-content: center; color: var(--col-herencia); transition: color 0.2s ease, transform 0.2s ease; width: 36px; height: 36px; }
        .social-link:hover { color: var(--col-bilbao); transform: translateY(-2px); }
        .social-icon { width: 20px; height: 20px; fill: currentColor; }

        .footer-grid { display: grid; gap: var(--sp-lg); grid-template-columns: 1fr; }
        .footer-desc { margin-bottom: var(--sp-md); font-size: 0.9rem; line-height: 1.6; }
        .footer-contact p { margin-bottom: var(--sp-xs); }
        .footer-contact a { font-weight: 600; color: var(--col-bilbao); }
        .footer-social-mobile { margin-top: var(--sp-md); margin-bottom: var(--sp-lg); display: flex; gap: var(--sp-xs); align-items: center; flex-wrap: wrap; }
        
        .footer-legal { margin-top: var(--sp-xl); padding: var(--sp-md) 0; border-top: 1px solid var(--col-borde); font-size: 0.8rem; display: flex; flex-direction: column; gap: var(--sp-sm); text-align: center; opacity: 0.8; }
        .legal-links { display: flex; flex-wrap: wrap; justify-content: center; gap: var(--sp-sm); }

        /* Breakpoints */
        @media (min-width: 600px) {
            .footer-grid { grid-template-columns: 1fr 1fr; }
            .footer-identity { grid-column: span 2; }
        }
        @media (min-width: 1024px) {
            .footer-header { flex-direction: row; justify-content: space-between; align-items: center; }
            .footer-social-desktop { display: flex; align-items: center; gap: var(--sp-xs); }
            /* Simplified grid for this page since menus are not present */
            .footer-grid { grid-template-columns: 1fr; }
            .footer-social-mobile { display: none; }
            .footer-legal { flex-direction: row; justify-content: space-between; text-align: left; }
        }
    </style>
</head>
<body>

    <!-- HEADER -->
    <header class="header-bar">
        <div class="header-inner">
            <!-- Logo links to English Home (/en/) -->
            <a href="/en/" class="logo-link" aria-label="Go to Colegio Bilbao Home">
                <img src="/assets/img/global/logo-bilbao-horizontal-azul.png" alt="Colegio Bilbao" class="logo-img" width="216" height="67">
            </a>
            <div class="header-controls">
                <!-- Language Switcher: ES links to Spanish Home (/) -->
                <div class="lang-switch">
                    <a href="/" aria-label="Cambiar a Español">ES</a>
                    <span style="margin: 0 4px;">|</span>
                    <span class="active">EN</span>
                </div>
                <!-- NO MENU BUTTON HERE -->
            </div>
        </div>
    </header>

    <!-- MAIN CONTENT: COMING SOON -->
    <main id="main-content">
        <div class="construction-container">
            <img src="/assets/img/global/estudiantes-cartel-proximamente.jpg" 
                 alt="Colegio Bilbao students working in class" 
                 class="construction-image"
                 width="600" height="400">
            
            <h1>Coming Soon</h1>
            <p class="lead">We are building something extraordinary. This section will be available very soon with all the information you are looking for.</p>
            
            <!-- Return Button links to English Home -->
            <a href="/en/" class="btn-primary">Return to Home</a>
        </div>
    </main>

    <!-- FOOTER -->
    <footer>
        <div class="footer-container">
            
            <!-- Footer Header -->
            <div class="footer-header">
                <!-- Logo links to English Home -->
                <a href="/en/" class="footer-logo-link" aria-label="Go to Colegio Bilbao Home">
                    <img src="/assets/img/global/logo-bilbao-horizontal-azul.png" alt="Colegio Bilbao" class="footer-logo-img">
                </a>

                <!-- Socials Desktop -->
                <div class="footer-social-desktop">
                    <span style="margin-right: 8px;">Follow us</span>
                    <!-- Facebook -->
                    <a href="https://www.facebook.com/bilbaocolegio/" target="_blank" rel="noopener noreferrer" class="social-link" aria-label="Facebook">
                        <svg class="social-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M14 13.5h2.5l1-4H14v-2c0-1.03 0-2 2-2h1.5V1.74c-.75-.07-2.47-.24-4.75-.24-4.8 0-8 2.9-8 8v3h-5v4h5v11.5h7V13.5z"/></svg>
                    </a>
                    <!-- Instagram 1 -->
                    <a href="https://www.instagram.com/bilbaocolegio/" target="_blank" rel="noopener noreferrer" class="social-link" aria-label="Instagram">
                        <svg class="social-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 2.16c3.2 0 3.58.01 4.85.07 3.25.15 4.77 1.69 4.92 4.92.06 1.27.07 1.65.07 4.85 0 3.2-.01 3.58-.07 4.85-.15 3.23-1.66 4.77-4.92 4.92-1.27.06-1.64.07-4.85.07-3.2 0-3.58-.01-4.85-.07-3.23-.15-4.77-1.66-4.92-4.92-.06-1.27-.07-1.64-.07-4.85 0-3.2.01-3.58.07-4.85.15-3.23 1.66-4.77 4.92-4.92 1.27-.06 1.65-.07 4.85-.07M12 0C8.74 0 8.33.01 7.05.07 2.7.27.27 2.7.07 7.05.01 8.33 0 8.74 0 12s.01 3.67.07 4.95c.2 4.36 2.62 6.78 6.98 6.98 1.28.06 1.69.07 4.95.07s3.67-.01 4.95-.07c4.36-.2 6.78-2.62 6.98-6.98.06-1.28.07-1.69.07-4.95s-.01-3.67-.07-4.95C23.73 2.7 21.31.27 16.95.07 15.67.01 15.26 0 12 0zm0 5.84c-3.4 0-6.16 2.76-6.16 6.16 0 3.4 2.76 6.16 6.16 6.16 3.4 0 6.16-2.76 6.16-6.16 0-3.4-2.76-6.16-6.16-6.16zm0 10.16c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4zm6.4-10.8c0 .66-.54 1.2-1.2 1.2-.66 0-1.2-.54-1.2-1.2 0-.66.54-1.2 1.2-1.2.66 0 1.2.54 1.2 1.2z"/></svg>
                    </a>
                    <!-- Instagram 2 -->
                    <a href="https://www.instagram.com/bilbaomoments/" target="_blank" rel="noopener noreferrer" class="social-link" aria-label="Instagram Secundaria">
                        <svg class="social-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 2.16c3.2 0 3.58.01 4.85.07 3.25.15 4.77 1.69 4.92 4.92.06 1.27.07 1.65.07 4.85 0 3.2-.01 3.58-.07 4.85-.15 3.23-1.66 4.77-4.92 4.92-1.27.06-1.64.07-4.85.07-3.2 0-3.58-.01-4.85-.07-3.23-.15-4.77-1.66-4.92-4.92-.06-1.27-.07-1.64-.07-4.85 0-3.2.01-3.58.07-4.85.15-3.23 1.66-4.77 4.92-4.92 1.27-.06 1.65-.07 4.85-.07M12 0C8.74 0 8.33.01 7.05.07 2.7.27.27 2.7.07 7.05.01 8.33 0 8.74 0 12s.01 3.67.07 4.95c.2 4.36 2.62 6.78 6.98 6.98 1.28.06 1.69.07 4.95.07s3.67-.01 4.95-.07c4.36-.2 6.78-2.62 6.98-6.98.06-1.28.07-1.69.07-4.95s-.01-3.67-.07-4.95C23.73 2.7 21.31.27 16.95.07 15.67.01 15.26 0 12 0zm0 5.84c-3.4 0-6.16 2.76-6.16 6.16 0 3.4 2.76 6.16 6.16 6.16 3.4 0 6.16-2.76 6.16-6.16 0-3.4-2.76-6.16-6.16-6.16zm0 10.16c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4zm6.4-10.8c0 .66-.54 1.2-1.2 1.2-.66 0-1.2-.54-1.2-1.2 0-.66.54-1.2 1.2-1.2.66 0 1.2.54 1.2 1.2z"/></svg>
                    </a>
                    <!-- YouTube -->
                    <a href="https://www.youtube.com/@ColegioBilbaoOficial" target="_blank" rel="noopener noreferrer" class="social-link" aria-label="YouTube">
                        <svg class="social-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M19.615 3.184c-3.604-.246-11.631-.245-15.23 0-3.897.266-4.356 2.62-4.385 8.816.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0 3.897-.266 4.356-2.62 4.385-8.816-.029-6.185-.484-8.549-4.385-8.816zm-10.615 12.816v-8l8 3.993-8 4.007z"/></svg>
                    </a>
                    <!-- LinkedIn -->
                    <a href="https://mx.linkedin.com/company/colegio-bilbao" target="_blank" rel="noopener noreferrer" class="social-link" aria-label="LinkedIn">
                        <svg class="social-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/></svg>
                    </a>
                    <span style="margin: 0 16px;">|</span>
                    <!-- Language Switcher in Footer -->
                    <div class="lang-switch" style="display: inline-block;">
                        <a href="/" aria-label="Cambiar a Español">ES</a>
                        <span style="margin: 0 4px;">|</span>
                        <span class="active">EN</span>
                    </div>
                </div>
            </div>

            <!-- Footer Grid -->
            <div class="footer-grid">
                
                <!-- Identity Column -->
                <div class="footer-identity">
                    <p class="footer-desc">Private K-12 school in the western area of Mexico City.</p>
                    
                    <div class="footer-contact">
                        <p><strong>Address:</strong><br>
                        Tlalmimilolpan 39, San Mateo Tlaltenango,<br>
                        Cuajimalpa de Morelos, 05600 Mexico City, CDMX</p>
                        
                        <p style="margin-top:12px"><strong>Phones:</strong></p>
                        <p>Switchboard: <a href="tel:+5558101346">55 5810 1346</a></p>
                        <p>Admissions:<br> 
                           <a href="tel:+525549839745">+52 55 4983 9745</a><br>
                           <a href="tel:+525614612682">+52 56 1461 2682</a>
                        </p>
                        <p style="margin-top:12px"><a href="/en/contact/">View location and map →</a></p>
                    </div>

                    <!-- Social Mobile -->
                    <div class="footer-social-mobile">
                        <span>Follow us:</span>
                        <!-- Social Icons (Same as above) -->
                        <a href="https://www.facebook.com/bilbaocolegio/" class="social-link" aria-label="Facebook"><svg class="social-icon" viewBox="0 0 24 24"><path d="M14 13.5h2.5l1-4H14v-2c0-1.03 0-2 2-2h1.5V1.74c-.75-.07-2.47-.24-4.75-.24-4.8 0-8 2.9-8 8v3h-5v4h5v11.5h7V13.5z"/></svg></a>
                        <a href="https://www.instagram.com/bilbaocolegio/" class="social-link" aria-label="Instagram"><svg class="social-icon" viewBox="0 0 24 24"><path d="M12 2.16c3.2 0 3.58.01 4.85.07 3.25.15 4.77 1.69 4.92 4.92.06 1.27.07 1.65.07 4.85 0 3.2-.01 3.58-.07 4.85-.15 3.23-1.66 4.77-4.92 4.92-1.27.06-1.64.07-4.85.07-3.2 0-3.58-.01-4.85-.07-3.23-.15-4.77-1.66-4.92-4.92-.06-1.27-.07-1.64-.07-4.85 0-3.2.01-3.58.07-4.85.15-3.23 1.66-4.77 4.92-4.92 1.27-.06 1.65-.07 4.85-.07M12 0C8.74 0 8.33.01 7.05.07 2.7.27.27 2.7.07 7.05.01 8.33 0 8.74 0 12s.01 3.67.07 4.95c.2 4.36 2.62 6.78 6.98 6.98 1.28.06 1.69.07 4.95.07s3.67-.01 4.95-.07c4.36-.2 6.78-2.62 6.98-6.98.06-1.28.07-1.69.07-4.95s-.01-3.67-.07-4.95C23.73 2.7 21.31.27 16.95.07 15.67.01 15.26 0 12 0zm0 5.84c-3.4 0-6.16 2.76-6.16 6.16 0 3.4 2.76 6.16 6.16 6.16 3.4 0 6.16-2.76 6.16-6.16 0-3.4-2.76-6.16-6.16-6.16zm0 10.16c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4zm6.4-10.8c0 .66-.54 1.2-1.2 1.2-.66 0-1.2-.54-1.2-1.2 0-.66.54-1.2 1.2-1.2.66 0 1.2.54 1.2 1.2z"/></svg></a>
                        <a href="https://www.instagram.com/bilbaomoments/" class="social-link" aria-label="Instagram"><svg class="social-icon" viewBox="0 0 24 24"><path d="M12 2.16c3.2 0 3.58.01 4.85.07 3.25.15 4.77 1.69 4.92 4.92.06 1.27.07 1.65.07 4.85 0 3.2-.01 3.58-.07 4.85-.15 3.23-1.66 4.77-4.92 4.92-1.27.06-1.64.07-4.85.07-3.2 0-3.58-.01-4.85-.07-3.23-.15-4.77-1.66-4.92-4.92-.06-1.27-.07-1.64-.07-4.85 0-3.2.01-3.58.07-4.85.15-3.23 1.66-4.77 4.92-4.92 1.27-.06 1.65-.07 4.85-.07M12 0C8.74 0 8.33.01 7.05.07 2.7.27.27 2.7.07 7.05.01 8.33 0 8.74 0 12s.01 3.67.07 4.95c.2 4.36 2.62 6.78 6.98 6.98 1.28.06 1.69.07 4.95.07s3.67-.01 4.95-.07c4.36-.2 6.78-2.62 6.98-6.98.06-1.28.07-1.69.07-4.95s-.01-3.67-.07-4.95C23.73 2.7 21.31.27 16.95.07 15.67.01 15.26 0 12 0zm0 5.84c-3.4 0-6.16 2.76-6.16 6.16 0 3.4 2.76 6.16 6.16 6.16 3.4 0 6.16-2.76 6.16-6.16 0-3.4-2.76-6.16-6.16-6.16zm0 10.16c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4zm6.4-10.8c0 .66-.54 1.2-1.2 1.2-.66 0-1.2-.54-1.2-1.2 0-.66.54-1.2 1.2-1.2.66 0 1.2.54 1.2 1.2z"/></svg></a>
                        <a href="https://www.youtube.com/@ColegioBilbaoOficial" class="social-link" aria-label="YouTube"><svg class="social-icon" viewBox="0 0 24 24"><path d="M19.615 3.184c-3.604-.246-11.631-.245-15.23 0-3.897.266-4.356 2.62-4.385 8.816.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0 3.897-.266 4.356-2.62 4.385-8.816-.029-6.185-.484-8.549-4.385-8.816zm-10.615 12.816v-8l8 3.993-8 4.007z"/></svg></a>
                        <a href="https://mx.linkedin.com/company/colegio-bilbao" class="social-link" aria-label="LinkedIn"><svg class="social-icon" viewBox="0 0 24 24"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/></svg></a>
                    </div>
                </div>
            </div>

            <!-- Footer Legal -->
            <div class="footer-legal">
                <div class="legal-links">
                    <a href="/en/privacy-policy/">Privacy Policy</a> · 
                    <a href="/en/terms-and-conditions/">Terms and Conditions</a> · 
                    <a href="/en/sitemap/">Sitemap</a>
                </div>
                <div>
                    © 2025 Colegio Bilbao. All rights reserved.
                </div>
            </div>
        </div>
    </footer>

</body>
</html>