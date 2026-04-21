<!DOCTYPE html>
<html lang="es-MX">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Aviso de Privacidad Integral del Colegio Bilbao. Conoce cómo tratamos y protegemos tus datos personales.">
    <meta name="robots" content="index, follow">
    
    <title>Aviso de Privacidad | Colegio Bilbao</title>

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
                <h1 class="legal-h1">Aviso de Privacidad</h1>
                <p class="legal-meta">Nuevo Colegio Bilbao, S.C. | Última actualización: 13 de enero de 2026</p>
            </div>

            <div class="legal-content">
                
                <h2>1) Identidad y domicilio del responsable</h2>
                <p>En cumplimiento de la legislación mexicana aplicable en materia de protección de datos personales, <strong>Nuevo Colegio Bilbao, S.C.</strong> (en lo sucesivo, el “Colegio Bilbao”) es el <strong>Responsable</strong> del tratamiento de los datos personales que recabe a través del sitio web bilbao.edu.mx, sus formularios, medios de contacto, herramientas digitales y canales vinculados.</p>
                <p><strong>Domicilio del Responsable:</strong> Tlalmimilolpan 39, San Mateo Tlaltenango, Cuajimalpa de Morelos, 05600 Ciudad de México, CDMX.</p>

                <h2>2) Departamento de datos personales (contacto y horario)</h2>
                <p>Para cualquier asunto relacionado con este Aviso de Privacidad, el tratamiento de datos personales o el ejercicio de derechos, podrás contactar al Departamento de Datos Personales del Colegio Bilbao:</p>
                <ul>
                    <li><strong>Correo electrónico:</strong> <a href="mailto:contacto@bilbao.edu.mx" class="legal-link">contacto@bilbao.edu.mx</a></li>
                    <li><strong>Horario de atención:</strong> lunes a viernes de 8:00 a 14:00 horas (hora del centro de México)</li>
                    <li><strong>Domicilio para oír y recibir solicitudes:</strong> el señalado en el punto 1)</li>
                </ul>
                <p>Para agilizar trámites, se recomienda enviar solicitudes por correo electrónico. El Colegio podrá solicitar información adicional para acreditar identidad o representación legal cuando corresponda.</p>

                <h2>3) Datos personales que recabamos</h2>
                <p>El Colegio Bilbao podrá recabar, usar y tratar los siguientes datos personales, según la forma en que interactúes con el sitio y/o con el Colegio:</p>
                
                <h3>A) Datos proporcionados directamente por ti (vía formularios, correo o contacto):</h3>
                <ul>
                    <li><strong>Identificación y contacto:</strong> nombre(s), apellidos, correo electrónico, teléfono, ciudad/estado de residencia, país, relación con el Colegio (madre/padre/tutor, aspirante, exalumno, visitante, proveedor, candidato laboral, etc.).</li>
                    <li><strong>Interés académico y de admisiones:</strong> nivel o grado de interés, escuela de procedencia, información necesaria para agendar recorridos/vivencias/entrevistas, preferencias de contacto y seguimiento, y datos relacionados con el proceso de admisión o vinculación.</li>
                    <li><strong>Facturación (cuando aplique):</strong> RFC, razón social, domicilio fiscal, correo de facturación, constancia de situación fiscal y datos necesarios para emitir comprobantes fiscales.</li>
                    <li><strong>Contenido que nos envías:</strong> información incluida en mensajes, archivos o documentos que tú decidas compartir.</li>
                </ul>

                <h3>B) Datos recabados automáticamente al navegar (tecnologías de rastreo):</h3>
                <p>Datos técnicos y de uso como: tipo de navegador, sistema operativo, dirección IP, identificadores de dispositivo, páginas visitadas, duración de navegación, interacción con el sitio (eventos), fuente de tráfico y métricas de rendimiento.</p>

                <h3>C) Datos personales sensibles (solo en casos específicos):</h3>
                <p>En ciertos escenarios vinculados al entorno escolar, podrían tratarse datos sensibles (por ejemplo: información de salud relevante, alergias, requerimientos de apoyo, discapacidad o necesidades específicas). Cuando el Colegio Bilbao requiera tratar datos personales sensibles, lo realizará bajo medidas reforzadas y, cuando corresponda, recabará <strong>consentimiento expreso y por escrito</strong> mediante firma autógrafa, firma electrónica o mecanismo de autenticación equivalente.</p>

                <h3>D) Datos de menores de edad:</h3>
                <p>Por la naturaleza escolar del Colegio, algunos tratamientos pueden involucrar datos de personas menores de edad, usualmente gestionados por madre, padre y/o tutor(a). En trámites que lo ameriten, el Colegio podrá solicitar documentos para acreditar representación legal.</p>

                <h2>4) Finalidades del tratamiento</h2>
                <p>El Colegio Bilbao tratará los datos personales para las siguientes finalidades:</p>

                <h3>4.1 Finalidades primarias (necesarias)</h3>
                <p>Son indispensables para atender tu solicitud o sostener la relación con el Colegio:</p>
                <ul>
                    <li>Atender solicitudes de información sobre el Colegio, niveles, admisiones, becas, visitas, eventos y servicios escolares.</li>
                    <li>Contactarte y dar seguimiento a través de los medios que proporciones (correo, teléfono u otros).</li>
                    <li>Gestionar y administrar procesos de admisión: agenda de recorridos, entrevistas, vivencias, seguimiento y comunicación de etapas.</li>
                    <li>Administración, control y soporte de comunicaciones operativas derivadas de tu solicitud.</li>
                    <li>Facturación cuando aplique.</li>
                    <li>Seguridad y prevención de abuso de los canales digitales (detección de spam, fraude, usos indebidos, protección del sitio y de sus usuarios).</li>
                    <li>Cumplimiento de obligaciones legales y atención a requerimientos de autoridad competente cuando proceda.</li>
                </ul>

                <h3>4.2 Finalidades secundarias (no necesarias)</h3>
                <p>No son indispensables para la relación principal; puedes oponerte o revocar tu consentimiento:</p>
                <ul>
                    <li>Envío de comunicaciones institucionales (boletines, invitaciones, avisos generales, campañas informativas).</li>
                    <li>Promoción y difusión institucional (contenidos de identidad escolar, logros, actividades y comunidad), cuando aplique.</li>
                    <li>Analítica, medición y mejora del sitio y de campañas (estadísticas, desempeño y optimización de experiencia).</li>
                    <li>Encuestas de satisfacción y estudios internos de mejora.</li>
                </ul>
                <p><strong>Cómo negar o retirar consentimiento para finalidades secundarias:</strong><br>
                Envía un correo a <a href="mailto:contacto@bilbao.edu.mx" class="legal-link">contacto@bilbao.edu.mx</a> con el asunto: “Negativa/Revocación – Finalidades Secundarias”, indicando tu nombre completo y qué finalidades no autorizas.</p>

                <h2>5) Consentimiento</h2>
                <p>Por regla general, el tratamiento de datos personales está sujeto al consentimiento de la persona titular, salvo excepciones legales aplicables.</p>
                <ul>
                    <li>El consentimiento puede ser tácito cuando se pone a disposición este Aviso y no se manifiesta oposición.</li>
                    <li>En los casos en que la ley lo exija, el Colegio solicitará consentimiento expreso.</li>
                    <li>Para datos personales sensibles, se solicitará consentimiento expreso y por escrito mediante los mecanismos correspondientes.</li>
                    <li>El consentimiento puede revocarse en cualquier momento, sin efectos retroactivos, conforme al procedimiento indicado en este aviso.</li>
                </ul>

                <h2>6) Transferencias de datos personales</h2>
                <p>El Colegio Bilbao podrá realizar transferencias nacionales o internacionales de datos personales únicamente cuando sea legalmente procedente, para cumplir las finalidades descritas y/o obligaciones aplicables.</p>

                <h3>6.1 Encargados (proveedores que tratan datos por cuenta del Colegio)</h3>
                <p>El Colegio puede compartir datos con proveedores que prestan servicios necesarios para la operación, por ejemplo: hospedaje del sitio, correo institucional, CRM, gestión de formularios, analítica, mensajería, seguridad informática y soporte técnico. Estos proveedores actuarán como encargados, bajo instrucciones del Colegio y con medidas de confidencialidad y seguridad.</p>

                <h3>6.2 Terceros (no encargados)</h3>
                <p>Cuando el Colegio transfiera datos a terceros distintos de encargados, lo hará conforme al marco aplicable y, cuando la ley lo requiera, recabará el consentimiento correspondiente.</p>

                <h3>6.3 Cláusula de aceptación/negativa de transferencias</h3>
                <p>Cuando una transferencia requiera consentimiento, el Colegio lo solicitará mediante mecanismos apropiados (por ejemplo, checkbox o documento). En otros casos legalmente permitidos, la transferencia podrá realizarse sin consentimiento conforme a las excepciones aplicables.</p>

                <h2>7) Opciones y medios para limitar el uso o divulgación</h2>
                <p>Puedes limitar el uso o divulgación de tus datos personales solicitando:</p>
                <ul>
                    <li>Tu registro en un listado de exclusión interno para comunicaciones no indispensables, y/o</li>
                    <li>La suspensión de comunicaciones secundarias.</li>
                </ul>
                <p>Para ello, envía un correo a <a href="mailto:contacto@bilbao.edu.mx" class="legal-link">contacto@bilbao.edu.mx</a> con asunto: “Limitación de uso/divulgación”.</p>

                <h2>8) Derechos ARCO (Acceso, Rectificación, Cancelación y Oposición)</h2>
                <p>Puedes ejercer tus derechos ARCO en cualquier momento.</p>

                <h3>8.1 ¿Cómo presentar una solicitud ARCO?</h3>
                <p>Envía tu solicitud al correo <a href="mailto:contacto@bilbao.edu.mx" class="legal-link">contacto@bilbao.edu.mx</a> (o preséntala en el domicilio del Responsable). Tu solicitud debe incluir:</p>
                <ul>
                    <li>Nombre de la persona titular y medio para comunicar respuesta.</li>
                    <li>Documentos que acrediten identidad, o representación legal (si aplica).</li>
                    <li>Descripción clara y precisa de los datos respecto de los que se busca ejercer el derecho.</li>
                    <li>Indicar el derecho ARCO que deseas ejercer (Acceso, Rectificación, Cancelación u Oposición).</li>
                    <li>Cualquier elemento que facilite la localización de los datos (fecha aproximada, formulario usado, correo/telefono registrado, etc.).</li>
                </ul>

                <h3>8.2 Plazos de respuesta</h3>
                <p>El Colegio comunicará la determinación adoptada dentro del plazo legal aplicable y, en caso de resultar procedente, hará efectiva la solicitud conforme a los plazos previstos por la normativa.</p>

                <h2>9) Revocación del consentimiento</h2>
                <p>Puedes revocar tu consentimiento en cualquier momento (sin efectos retroactivos).
                Para revocar, envía un correo a <a href="mailto:contacto@bilbao.edu.mx" class="legal-link">contacto@bilbao.edu.mx</a> con asunto: “Revocación de consentimiento”, indicando tu nombre completo, el tratamiento específico y el alcance de la revocación.
                La revocación puede implicar que el Colegio no pueda seguir prestando ciertos servicios si el tratamiento es necesario para la relación.</p>

                <h2>10) Cookies y tecnologías similares</h2>
                <p>El sitio bilbao.edu.mx puede utilizar cookies, web beacons y tecnologías similares para:</p>
                <ul>
                    <li>Funcionamiento y seguridad del sitio.</li>
                    <li>Preferencias de navegación.</li>
                    <li>Medición, analítica y mejora de experiencia.</li>
                    <li>En su caso, medición de campañas.</li>
                </ul>
                <p><strong>Cómo deshabilitarlas:</strong> puedes bloquear o eliminar cookies desde tu navegador. Considera que algunas funciones podrían verse afectadas.</p>

                <h2>11) Enlaces a sitios de terceros</h2>
                <p>El sitio puede contener enlaces a páginas o plataformas de terceros. El Colegio Bilbao no controla el tratamiento de datos personales que dichos terceros realicen. Te recomendamos leer sus avisos de privacidad.</p>

                <h2>12) Seguridad y brechas de seguridad</h2>
                <p>El Colegio Bilbao implementa medidas de seguridad administrativas, técnicas y físicas razonables para proteger los datos personales contra daño, pérdida, alteración, destrucción, uso, acceso o tratamiento no autorizado.</p>
                <p>Si ocurriera una vulneración de seguridad que afecte de forma significativa tus derechos patrimoniales o morales, el Colegio realizará las acciones de atención y comunicación conforme al marco aplicable.</p>

                <h2>13) Conservación, bloqueo y supresión</h2>
                <p>Los datos personales se conservarán únicamente por el tiempo necesario para cumplir las finalidades descritas y obligaciones aplicables. Cuando dejen de ser necesarios, serán suprimidos y, en su caso, bloqueados conforme a la normativa.</p>

                <h2>14) Cambios al aviso de privacidad</h2>
                <p>Este aviso puede modificarse por actualizaciones legales, políticas internas o cambios operativos. Cualquier cambio será publicado en bilbao.edu.mx en la sección correspondiente. Cuando sea legalmente exigible, se podrá notificar a través de los medios de contacto disponibles.</p>

                <h2>15) Autoridad competente y medios de inconformidad</h2>
                <p>Si consideras que tu derecho a la protección de datos personales ha sido vulnerado o existe inconformidad con la respuesta del Colegio, podrás acudir ante la autoridad competente en materia de protección de datos personales en México, conforme a la normativa vigente aplicable.</p>

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
                        <p>Conmutador: <a href="tel:+5558101346">55 5810 1346</a></p>
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