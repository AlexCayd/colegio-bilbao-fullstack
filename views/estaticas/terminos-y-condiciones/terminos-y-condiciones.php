<!DOCTYPE html>
<html lang="es-MX">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Términos y Condiciones de Uso del sitio web del Colegio Bilbao. Información legal sobre el uso del sitio.">
    <meta name="robots" content="noindex, follow">
    
    <title>Términos y Condiciones | Colegio Bilbao</title>

    <!-- FAVICON -->
    <link rel="shortcut icon" href="../assets/img/global/favicon.png" type="image/png">
    <link rel="icon" href="../assets/img/global/favicon.png" type="image/png">

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
            --col-bilbao: #4D8ABB;
            --col-espiritu: #7DC6E5;
            --col-herencia: #374C69;
            --col-texto: #374C69;
            --col-blanco: #FFFFFF;
            --col-borde: #E0E6ED;
            
            --sp-xs: 8px; --sp-sm: 16px; --sp-md: 24px; --sp-lg: 48px; --sp-xl: 80px; --sp-xxl: 120px;
            --font-main: 'Montserrat', sans-serif;
            --max-width: 1280px;
            --header-height: 90px;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; font-size: 16px; }
        
        body {
            font-family: var(--font-main);
            background-color: var(--bg-global);
            color: var(--col-texto);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }

        a { text-decoration: none; color: inherit; transition: 0.3s; }
        ul { list-style: none; }
        button { font-family: inherit; border: none; background: none; cursor: pointer; }
        img { max-width: 100%; height: auto; display: block; }
        p { text-align: justify; margin-bottom: 1rem; }
        strong { font-weight: 700; color: var(--col-herencia); }

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

        /* --- CONTENIDO LEGAL --- */
        main { padding-top: var(--header-height); }

        .legal-wrapper {
            max-width: 900px;
            margin: 0 auto;
            padding: 60px 24px 120px;
        }

        .legal-header {
            margin-bottom: 60px;
            text-align: left;
        }

        .legal-h1 {
            font-size: clamp(2rem, 5vw, 3rem);
            color: var(--col-bilbao);
            font-weight: 800;
            margin-bottom: 16px;
            line-height: 1.2;
        }

        .legal-meta {
            font-size: 0.9rem;
            color: #888;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .legal-content {
            font-size: 1rem;
            color: var(--col-texto);
        }

        .legal-content h2 {
            font-size: 1.5rem;
            color: var(--col-herencia);
            font-weight: 700;
            margin-top: 40px;
            margin-bottom: 20px;
            border-bottom: 2px solid var(--col-borde);
            padding-bottom: 10px;
        }

        .legal-content h3 {
            font-size: 1.2rem;
            color: var(--col-bilbao);
            font-weight: 600;
            margin-top: 30px;
            margin-bottom: 15px;
        }

        .legal-content ul {
            list-style: disc;
            padding-left: 24px;
            margin-bottom: 20px;
        }

        .legal-content li {
            margin-bottom: 10px;
        }

        .legal-content p {
            margin-bottom: 20px;
            line-height: 1.7;
        }
        
        .legal-link {
            color: var(--col-bilbao);
            text-decoration: underline;
            font-weight: 600;
        }
        .legal-link:hover { color: var(--col-espiritu); }
        
        .quote-block {
            background-color: #F8FAFC;
            padding: 24px;
            border-left: 4px solid var(--col-bilbao);
            margin: 40px 0;
            font-style: italic;
            font-weight: 500;
            color: var(--col-herencia);
            text-align: center;
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
                <a href="/" class="logo-link"><img src="../assets/img/global/logo-bilbao-horizontal-azul.png" alt="Colegio Bilbao" class="logo-img"></a>
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
        <section class="legal-wrapper">
            <div class="legal-header">
                <h1 class="legal-h1">Términos y Condiciones de Uso</h1>
                <p class="legal-meta">Nuevo Colegio Bilbao, S.C. | Última actualización: 13 de enero de 2026</p>
            </div>

            <div class="legal-content">
                <p>
                    <strong>Sitio web:</strong> bilbao.edu.mx<br>
                    <strong>Responsable del sitio:</strong> Nuevo Colegio Bilbao, S.C. (“Colegio Bilbao”)<br>
                    <strong>Domicilio:</strong> Tlalmimilolpan 39, San Mateo Tlaltenango, Cuajimalpa de Morelos, 05600 Ciudad de México, CDMX<br>
                    <strong>Correo de contacto:</strong> <a href="mailto:contacto@bilbao.edu.mx" class="legal-link">contacto@bilbao.edu.mx</a><br>
                    <strong>Horario de atención:</strong> lunes a viernes de 8:00 a 14:00 horas (hora del centro de México)
                </p>

                <h2>1) Aceptación de los Términos</h2>
                <p>Al acceder, navegar o utilizar este sitio web (el “Sitio”), la persona usuaria acepta estos Términos y Condiciones (los “Términos”). Si no está de acuerdo, deberá abstenerse de usar el Sitio.</p>
                <p>El Colegio Bilbao podrá actualizar estos Términos en cualquier momento. La versión vigente será la publicada en el Sitio. El uso continuado del Sitio implica la aceptación de las modificaciones.</p>

                <h2>2) Objeto del Sitio</h2>
                <p>El Sitio tiene fines principalmente informativos y de comunicación institucional, incluyendo, de forma enunciativa: información del Colegio, niveles, modelo educativo, procesos de admisión, contacto, eventos y publicaciones.</p>
                <p>El Sitio puede incluir formularios, vínculos a plataformas externas y/o canales de mensajería para facilitar la comunicación con el Colegio.</p>

                <h2>3) Reglas de uso y conducta</h2>
                <p>La persona usuaria se obliga a utilizar el Sitio de forma lícita, respetuosa y segura, y se abstendrá de:</p>
                <ul>
                    <li>Usar el Sitio con fines fraudulentos, ilegales o no autorizados.</li>
                    <li>Intentar acceder sin autorización a sistemas, servidores, bases de datos o cuentas.</li>
                    <li>Interferir o afectar la disponibilidad del Sitio (incluyendo ataques, escaneo masivo, scraping no autorizado, malware o cualquier técnica similar).</li>
                    <li>Transmitir contenido que sea difamatorio, discriminatorio, violento, amenazante, engañoso, invasivo de privacidad o que infrinja derechos de terceros.</li>
                    <li>Suplantar la identidad de otra persona o proporcionar información falsa en formularios o comunicaciones.</li>
                </ul>
                <p>El Colegio Bilbao podrá restringir, suspender o bloquear el acceso al Sitio cuando detecte un uso que contravenga estos Términos o ponga en riesgo la seguridad del Sitio o de terceros.</p>

                <h2>4) Información, promociones, cuotas y exactitud</h2>
                <p>El Colegio Bilbao procura que la información publicada sea clara y actualizada. Sin embargo, el Sitio puede contener errores tipográficos, desactualizaciones o variaciones por cambios operativos (por ejemplo, ciclos escolares, disponibilidad, eventos, horarios o actividades).</p>
                <p>Cuando el Sitio publique información sobre cuotas, colegiaturas, promociones, condiciones, restricciones o plazos, el Colegio Bilbao procurará que dichas comunicaciones sean claras. En caso de que se requiera confirmación formal, las condiciones aplicables serán las comunicadas por los canales oficiales del Colegio y/o las contenidas en los formatos institucionales correspondientes (cotizaciones, contratos, reglamentos, comunicados y/o documentos del ciclo escolar aplicable), sin perjuicio de los derechos que reconozca la legislación vigente a las personas usuarias/consumidoras.</p>

                <h2>5) Contacto y comunicaciones electrónicas</h2>
                <p>La persona usuaria reconoce que ciertos trámites o comunicaciones pueden realizarse por medios electrónicos (por ejemplo: formularios, correo, mensajería). El Colegio Bilbao podrá responder por los mismos medios de contacto proporcionados.</p>
                <p>Para solicitudes formales relacionadas con el Sitio, estos Términos o temas institucionales, el canal oficial de contacto es: <a href="mailto:contacto@bilbao.edu.mx" class="legal-link">contacto@bilbao.edu.mx</a>.</p>

                <h2>6) Formularios y veracidad de la información</h2>
                <p>Al enviar información mediante formularios o canales vinculados al Sitio, la persona usuaria declara que los datos proporcionados son veraces y que cuenta con facultades para proporcionarlos (por ejemplo, en caso de actuar como madre, padre o tutor(a)).</p>
                <p>El Colegio Bilbao podrá solicitar información adicional para validar identidad o representación cuando resulte necesario por seguridad, prevención de fraude o cumplimiento normativo.</p>

                <h2>7) Protección de datos personales</h2>
                <p>El tratamiento de datos personales recabados a través del Sitio se rige por el <a href="../aviso-de-privacidad/" class="legal-link">Aviso de Privacidad</a> del Colegio Bilbao, disponible en el Sitio (sección “Aviso de Privacidad”). El uso del Sitio implica haber puesto a disposición dicha información.</p>

                <h2>8) Propiedad intelectual y uso del contenido</h2>
                <p>Todos los contenidos del Sitio, incluyendo, de forma enunciativa: textos, fotografías, videos, documentos, logotipos, marcas, ilustraciones, iconografía, diseño, estructura, código y materiales descargables (el “Contenido”), son propiedad del Colegio Bilbao o se usan con autorización/licencia correspondiente, y están protegidos por la normativa aplicable.</p>
                <p>Queda prohibido: copiar, reproducir, modificar, distribuir, publicar, transmitir, explotar comercialmente o crear obras derivadas del Contenido, total o parcialmente, sin autorización previa y por escrito del Colegio Bilbao, salvo cuando la ley lo permita expresamente.</p>
                <p><strong>Uso permitido:</strong> visualización y uso personal/no comercial del Contenido para fines informativos, siempre respetando derechos de autor y marcas.</p>

                <h2>9) Testimonios, comentarios y contenido enviado por la persona usuaria</h2>
                <p>Si el Sitio habilita campos para envío de mensajes, comentarios, materiales o archivos (“Contenido de Usuario”), la persona usuaria:</p>
                <ul>
                    <li>Garantiza que tiene derechos para enviar dicho contenido y que no infringe derechos de terceros.</li>
                    <li>Reconoce que el Colegio Bilbao puede revisar, moderar, ocultar o eliminar Contenido de Usuario que contravenga estos Términos o que resulte riesgoso, ilegal o inapropiado.</li>
                    <li>Autoriza al Colegio Bilbao a usar el Contenido de Usuario estrictamente para fines de atención, seguimiento o gestión de la solicitud, salvo que se pacte o autorice expresamente un uso adicional (por ejemplo, difusión institucional).</li>
                </ul>

                <h2>10) Enlaces a terceros y plataformas externas</h2>
                <p>El Sitio puede incluir enlaces a sitios, plataformas o servicios de terceros (por ejemplo, mapas, mensajería, herramientas de analítica, plataformas educativas o formularios). Dichos terceros son responsables de sus propios términos, políticas y funcionamiento.</p>
                <p>El Colegio Bilbao no controla ni garantiza la disponibilidad, seguridad o exactitud de servicios de terceros. El acceso a estos enlaces se realiza bajo responsabilidad de la persona usuaria.</p>

                <h2>11) Disponibilidad del Sitio y seguridad</h2>
                <p>El Colegio Bilbao busca mantener el Sitio disponible y seguro, pero no garantiza operación ininterrumpida, libre de errores o sin interrupciones por causas técnicas, mantenimiento, fallas de terceros, fuerza mayor o eventos fuera de control razonable.</p>
                <p>La persona usuaria es responsable de contar con medidas básicas de seguridad (por ejemplo, antivirus, navegador actualizado) para reducir riesgos al navegar en internet.</p>

                <h2>12) Exclusión de garantías</h2>
                <p>El Sitio y su Contenido se ponen a disposición “tal cual”, con fines principalmente informativos. En la medida permitida por la ley, el Colegio Bilbao no otorga garantías implícitas sobre la idoneidad del Sitio para fines específicos de la persona usuaria.</p>
                <p>Nada de lo previsto en estos Términos busca limitar derechos irrenunciables establecidos por la legislación aplicable.</p>

                <h2>13) Limitación de responsabilidad</h2>
                <p>En la medida permitida por la ley, el Colegio Bilbao no será responsable por daños indirectos, incidentales, especiales o consecuenciales derivados del uso o imposibilidad de uso del Sitio, incluyendo, de forma enunciativa: pérdida de datos, interrupciones, fallas de terceros, virus u otros elementos dañinos introducidos por terceros.</p>
                <p>La responsabilidad del Colegio Bilbao, cuando legalmente proceda, se limitará al alcance previsto por la normativa aplicable y, en su caso, a las medidas correctivas razonables dentro de su control.</p>

                <h2>14) Indemnidad</h2>
                <p>La persona usuaria acepta sacar en paz y a salvo al Colegio Bilbao frente a reclamaciones de terceros derivadas de: (i) uso indebido del Sitio; (ii) violación a estos Términos; (iii) infracción de derechos de terceros; o (iv) Contenido de Usuario enviado por la persona usuaria, sin perjuicio de lo que determine la autoridad competente.</p>

                <h2>15) Terminación o suspensión</h2>
                <p>El Colegio Bilbao podrá suspender o terminar el acceso al Sitio (total o parcial) cuando detecte: (i) incumplimiento de estos Términos; (ii) riesgos de seguridad; (iii) requerimiento de autoridad; o (iv) uso que afecte al Sitio o a terceros.</p>

                <h2>16) Legislación aplicable y jurisdicción</h2>
                <p>Estos Términos se interpretarán conforme a las leyes aplicables en los Estados Unidos Mexicanos. Para cualquier controversia relacionada con el Sitio o estos Términos, las partes se someten a la jurisdicción de los tribunales competentes en la Ciudad de México, renunciando a cualquier otro fuero que pudiera corresponderles por razón de domicilio presente o futuro, salvo disposición legal en contrario.</p>

                <h2>17) Contacto</h2>
                <p>Cualquier duda sobre estos Términos puede enviarse a: <a href="mailto:contacto@bilbao.edu.mx" class="legal-link">contacto@bilbao.edu.mx</a></p>
                
                <div class="quote-block">
                    “Al usar este sitio aceptas nuestros Términos y Condiciones y el Aviso de Privacidad.”
                </div>

            </div>
        </section>
    </main>

    <!-- FOOTER -->
    <footer>
        <div class="footer-container">
            <div class="footer-header">
                <a href="/" class="footer-logo-link"><img src="../assets/img/global/logo-bilbao-horizontal-azul.png" alt="Colegio Bilbao" class="footer-logo-img"></a>
                <div class="footer-social-desktop">
                    <span style="margin-right: 8px;">Síguenos</span>
                    <a href="https://www.facebook.com/bilbaocolegio/" target="_blank" rel="noopener noreferrer" class="social-link" aria-label="Facebook"><svg class="social-icon" viewBox="0 0 24 24"><path d="M14 13.5h2.5l1-4H14v-2c0-1.03 0-2 2-2h1.5V1.74c-.75-.07-2.47-.24-4.75-.24-4.8 0-8 2.9-8 8v3h-5v4h5v11.5h7V13.5z"/></svg></a>
                    <a href="https://www.instagram.com/bilbaocolegio/" target="_blank" rel="noopener noreferrer" class="social-link" aria-label="Instagram"><svg class="social-icon" viewBox="0 0 24 24"><path d="M12 2.16c3.2 0 3.58.01 4.85.07 3.25.15 4.77 1.69 4.92 4.92.06 1.27.07 1.65.07 4.85 0 3.2-.01 3.58-.07 4.85-.15 3.23-1.66 4.77-4.92 4.92-1.27.06-1.64.07-4.85.07-3.2 0-3.58-.01-4.85-.07-3.23-.15-4.77-1.66-4.92-4.92-.06-1.27-.07-1.64-.07-4.85 0-3.2.01-3.58.07-4.85.15-3.23 1.66-4.77 4.92-4.92 1.27-.06 1.65-.07 4.85-.07M12 0C8.74 0 8.33.01 7.05.07 2.7.27.27 2.7.07 7.05.01 8.33 0 8.74 0 12s.01 3.67.07 4.95c.2 4.36 2.62 6.78 6.98 6.98 1.28.06 1.69.07 4.95.07s3.67-.01 4.95-.07c4.36-.2 6.78-2.62 6.98-6.98.06-1.28.07-1.69.07-4.95s-.01-3.67-.07-4.95C23.73 2.7 21.31.27 16.95.07 15.67.01 15.26 0 12 0zm0 5.84c-3.4 0-6.16 2.76-6.16 6.16 0 3.4 2.76 6.16 6.16 6.16 3.4 0 6.16-2.76 6.16-6.16 0-3.4-2.76-6.16-6.16-6.16zm0 10.16c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4zm6.4-10.8c0 .66-.54 1.2-1.2 1.2-.66 0-1.2-.54-1.2-1.2 0-.66.54-1.2 1.2-1.2.66 0 1.2.54 1.2 1.2z"/></svg></a>
                        <a href="https://www.instagram.com/bilbaomoments/" target="_blank" rel="noopener noreferrer" class="social-link" aria-label="Instagram Secundaria"><svg class="social-icon" viewBox="0 0 24 24"><path d="M12 2.16c3.2 0 3.58.01 4.85.07 3.25.15 4.77 1.69 4.92 4.92.06 1.27.07 1.65.07 4.85 0 3.2-.01 3.58-.07 4.85-.15 3.23-1.66 4.77-4.92 4.92-1.27.06-1.64.07-4.85.07-3.2 0-3.58-.01-4.85-.07-3.23-.15-4.77-1.66-4.92-4.92-.06-1.27-.07-1.64-.07-4.85 0-3.2.01-3.58.07-4.85.15-3.23 1.66-4.77 4.92-4.92 1.27-.06 1.65-.07 4.85-.07M12 0C8.74 0 8.33.01 7.05.07 2.7.27.27 2.7.07 7.05.01 8.33 0 8.74 0 12s.01 3.67.07 4.95c.2 4.36 2.62 6.78 6.98 6.98 1.28.06 1.69.07 4.95.07s3.67-.01 4.95-.07c4.36-.2 6.78-2.62 6.98-6.98.06-1.28.07-1.69.07-4.95s-.01-3.67-.07-4.95C23.73 2.7 21.31.27 16.95.07 15.67.01 15.26 0 12 0zm0 5.84c-3.4 0-6.16 2.76-6.16 6.16 0 3.4 2.76 6.16 6.16 6.16 3.4 0 6.16-2.76 6.16-6.16 0-3.4-2.76-6.16-6.16-6.16zm0 10.16c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4zm6.4-10.8c0 .66-.54 1.2-1.2 1.2-.66 0-1.2-.54-1.2-1.2 0-.66.54-1.2 1.2-1.2.66 0 1.2.54 1.2 1.2z"/></svg></a>
                        <a href="https://www.youtube.com/@ColegioBilbaoOficial" class="social-link"><svg class="social-icon" viewBox="0 0 24 24"><path d="M19.615 3.184c-3.604-.246-11.631-.245-15.23 0-3.897.266-4.356 2.62-4.385 8.816.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0 3.897-.266 4.356-2.62 4.385-8.816-.029-6.185-.484-8.549-4.385-8.816zm-10.615 12.816v-8l8 3.993-8 4.007z"/></svg></a>
                        <a href="https://mx.linkedin.com/company/colegio-bilbao" class="social-link"><svg class="social-icon" viewBox="0 0 24 24"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/></svg></a>
                    </div>
                </div>
                <div class="footer-col">
                    <button class="footer-col-title" aria-expanded="false">Conócenos <span class="chevron">▼</span></button>
                    <ul class="footer-links">
                        <li><a href="../conocenos/quienes-somos/">Quiénes somos</a></li>
                        <li><a href="../conocenos/equipo-educativo/">Equipo educativo</a></li>
                        <li><a href="../conocenos/instalaciones/">Instalaciones</a></li>
                        <li><a href="../conocenos/certificaciones-y-reconocimientos/">Certificaciones y reconocimientos</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <button class="footer-col-title">Niveles <span class="chevron">▼</span></button>
                    <ul class="footer-links">
                        <li><a href="../niveles-academicos/preescolar/">Preescolar</a></li>
                        <li><a href="../niveles-academicos/primaria/">Primaria</a></li>
                        <li><a href="../niveles-academicos/secundaria/">Secundaria</a></li>
                        <li><a href="../niveles-academicos/preparatoria/">Preparatoria</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <button class="footer-col-title">Comunidad <span class="chevron">▼</span></button>
                    <ul class="footer-links">
                        <li><a href="../admisiones/">Admisiones</a></li>
                        <li><a href="../comunidad/familias/">Familias</a></li>
                        <li><a href="../voces-bilbao/noticias/">Noticias</a></li>
                        <li><a href="../contacto/cultura-y-talento/">Bolsa de trabajo</a></li>
                    </ul>
                </div>
            </div>

             <div class="footer-legal">
                <div class="legal-links">
                    <a href="../aviso-de-privacidad/">Aviso de privacidad</a> · 
                    <a href="../terminos-y-condiciones/">Términos y condiciones</a> · 
                    <a href="../mapa-del-sitio/">Mapa del sitio</a>
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
    </script>
</body>
</html>