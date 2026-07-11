    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-8XVWCDM02P"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', 'G-8XVWCDM02P');
    </script>

    <!-- GSAP — defer evita bloqueo de renderizado; preconnect adelanta la conexión -->
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <script defer src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script defer src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>

    <!-- HEADER -->
    <header class="header-bar">
        <div class="header-inner">
            <a href="/" class="logo-link"><img src="/build/assets/img/global/logo-bilbao-horizontal-azul.png" alt="Colegio Bilbao" class="logo-img" loading="lazy"></a>

            <nav class="header-nav" data-i18n-attr="aria-label:chrome.header.nav.ariaLabel" aria-label="Navegación principal">

                <div class="header-nav__item">
                    <a href="/conocenos/quienes-somos" class="header-nav__link header-nav__link--has-menu" data-i18n="chrome.header.nav.conocenos" aria-haspopup="true">Conócenos <span class="header-nav__caret" aria-hidden="true"></span></a>
                    <div class="header-nav__dropdown">
                        <a href="/conocenos/quienes-somos" class="header-nav__sublink" data-i18n="chrome.overlay.conocenos.quienesSomos">Quiénes somos</a>
                        <a href="/conocenos/equipo-educativo" class="header-nav__sublink" data-i18n="chrome.overlay.conocenos.equipoEducativo">Equipo educativo</a>
                        <a href="/conocenos/instalaciones" class="header-nav__sublink" data-i18n="chrome.overlay.conocenos.instalaciones">Instalaciones</a>
                        <a href="/conocenos/certificaciones-y-reconocimientos" class="header-nav__sublink" data-i18n="chrome.overlay.conocenos.certificaciones">Certificaciones y reconocimientos</a>
                    </div>
                </div>

                <div class="header-nav__item">
                    <a href="/niveles" class="header-nav__link header-nav__link--has-menu" data-i18n="chrome.header.nav.niveles" aria-haspopup="true">Niveles <span class="header-nav__caret" aria-hidden="true"></span></a>
                    <div class="header-nav__dropdown">
                        <a href="/niveles-academicos/preescolar" class="header-nav__sublink" data-i18n="chrome.overlay.niveles.preescolar">Preescolar</a>
                        <a href="/niveles-academicos/primaria" class="header-nav__sublink" data-i18n="chrome.overlay.niveles.primaria">Primaria</a>
                        <a href="/niveles-academicos/secundaria" class="header-nav__sublink" data-i18n="chrome.overlay.niveles.secundaria">Secundaria</a>
                        <a href="/niveles-academicos/preparatoria" class="header-nav__sublink" data-i18n="chrome.overlay.niveles.preparatoria">Preparatoria</a>
                    </div>
                </div>

                <div class="header-nav__item">
                    <a href="/modelo-educativo/modelo-vida" class="header-nav__link header-nav__link--has-menu" data-i18n="chrome.header.nav.modeloEducativo" aria-haspopup="true">Modelo educativo <span class="header-nav__caret" aria-hidden="true"></span></a>
                    <div class="header-nav__dropdown">
                        <a href="/modelo-educativo/modelo-vida" class="header-nav__sublink" data-i18n="chrome.overlay.modeloEducativo.modeloVida">Modelo VIDA</a>
                        <a href="/modelo-educativo/filosofia-y-metodologia" class="header-nav__sublink" data-i18n="chrome.overlay.modeloEducativo.filosofia">Filosofía</a>
                        <a href="/modelo-educativo/aprendizaje-integral" class="header-nav__sublink" data-i18n="chrome.overlay.modeloEducativo.aprendizajeIntegral">Aprendizaje integral</a>
                        <a href="/modelo-educativo/idiomas" class="header-nav__sublink" data-i18n="chrome.overlay.modeloEducativo.idiomas">Idiomas</a>
                    </div>
                </div>

                <div class="header-nav__item">
                    <a href="/admisiones" class="header-nav__link header-nav__link--has-menu" data-i18n="chrome.header.nav.admisiones" aria-haspopup="true">Admisiones <span class="header-nav__caret" aria-hidden="true"></span></a>
                    <div class="header-nav__dropdown">
                        <a href="/admisiones" class="header-nav__sublink" data-i18n="chrome.overlay.admisiones.inicio">Inicio</a>
                        <a href="/admisiones/proceso" class="header-nav__sublink" data-i18n="chrome.overlay.admisiones.proceso">Proceso</a>
                        <a href="/admisiones/preguntas-frecuentes" class="header-nav__sublink" data-i18n="chrome.overlay.admisiones.faq">FAQ</a>
                        <a href="/admisiones/convenios" class="header-nav__sublink" data-i18n="chrome.overlay.admisiones.convenios">Convenios</a>
                        <a href="/admisiones/convocatoria-becas" class="header-nav__sublink" data-i18n="chrome.overlay.admisiones.becas">Becas</a>
                        <a href="/admisiones/contacto" class="header-nav__sublink" data-i18n="chrome.overlay.admisiones.contacto">Contacto</a>
                    </div>
                </div>

                <a href="/noticias" class="header-nav__link" data-i18n="chrome.header.nav.noticias">Noticias</a>
                <a href="/blog"     class="header-nav__link" data-i18n="chrome.header.nav.blog">Blog</a>

                <div class="header-nav__item">
                    <a href="/contacto" class="header-nav__link header-nav__link--has-menu" data-i18n="chrome.header.nav.visitanos" aria-haspopup="true">Visítanos <span class="header-nav__caret" aria-hidden="true"></span></a>
                    <div class="header-nav__dropdown">
                        <a href="/contacto" class="header-nav__sublink" data-i18n="chrome.overlay.contacto.contacto">Contacto</a>
                        <a href="/contacto/directorio" class="header-nav__sublink" data-i18n="chrome.overlay.contacto.directorio">Directorio</a>
                        <a href="/contacto/cultura-y-talento" class="header-nav__sublink" data-i18n="chrome.overlay.contacto.culturaTalento">Cultura y talento</a>
                        <a href="/contacto/proveedores" class="header-nav__sublink" data-i18n="chrome.overlay.contacto.proveedores">Proveedores</a>
                    </div>
                </div>

            </nav>

            <div class="header-controls">
                <div class="lang-switch">
                    <a href="#" class="lang-switch__btn" data-lang="es">ES</a> |
                    <a href="#" class="lang-switch__btn" data-lang="en">EN</a>
                </div>
                <button id="theme-toggle" class="header-theme-btn" data-i18n-attr="aria-label:chrome.header.themeToggleLabel;title:chrome.header.themeToggleTitle" aria-label="Cambiar modo de color" title="Modo oscuro / claro">
                    <span id="theme-icon" class="theme-icon-wrap">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon-sun" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/></svg>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon-moon" style="display:none" aria-hidden="true"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
                    </span>
                </button>
                <a href="https://wa.me/525614612682?text=Hola,%20quiero%20agendar%20una%20visita%20al%20Colegio%20Bilbao."
                   class="header-agenda-btn" target="_blank" rel="noopener" data-i18n="chrome.header.agendaBtn">Agenda</a>
                <button class="menu-trigger" data-i18n-attr="aria-label:chrome.header.menuTrigger.open" aria-label="Abrir menú"><div class="hamburger-icon"><span></span><span></span><span></span></div></button>
            </div>
        </div>
    </header>

    <!-- OVERLAY MENU -->
    <div id="menu-overlay" class="menu-overlay" role="dialog" aria-modal="true" aria-hidden="true" data-i18n-attr="aria-label:chrome.overlay.ariaLabel" aria-label="Menú principal">
        <div class="overlay-header">
            <div class="header-inner">
                <a href="/" class="logo-link"><img src="/build/assets/img/global/logo-bilbao-horizontal-azul.png" alt="Colegio Bilbao" class="logo-img" loading="lazy"></a>
                <div class="header-controls">
                    <div class="lang-switch">
                        <a href="#" class="lang-switch__btn" data-lang="es">ES</a> |
                        <a href="#" class="lang-switch__btn" data-lang="en">EN</a>
                    </div>
                    <button id="close-menu-btn" class="close-btn" data-i18n-attr="aria-label:chrome.overlay.close" aria-label="Cerrar menú"></button>
                </div>
            </div>
        </div>
        <nav class="overlay-content">
            <ul id="primary-nav">
                <li class="nav-accordion-item"><button class="nav-accordion-trigger"><span data-i18n="chrome.overlay.accordion.conocenos">Conócenos</span> <span class="chevron">▼</span></button><ul class="nav-submenu"><li><a href="/conocenos/quienes-somos" data-i18n="chrome.overlay.conocenos.quienesSomos">Quiénes somos</a></li><li><a href="/conocenos/equipo-educativo" data-i18n="chrome.overlay.conocenos.equipoEducativo">Equipo educativo</a></li><li><a href="/conocenos/instalaciones" data-i18n="chrome.overlay.conocenos.instalaciones">Instalaciones</a></li><li><a href="/conocenos/certificaciones-y-reconocimientos" data-i18n="chrome.overlay.conocenos.certificaciones">Certificaciones y reconocimientos</a></li></ul></li>
                <li class="nav-accordion-item"><button class="nav-accordion-trigger"><span data-i18n="chrome.overlay.accordion.modeloEducativo">Modelo educativo</span> <span class="chevron">▼</span></button><ul class="nav-submenu"><li><a href="/modelo-educativo/modelo-vida" data-i18n="chrome.overlay.modeloEducativo.modeloVida">Modelo VIDA</a></li><li><a href="/modelo-educativo/filosofia-y-metodologia" data-i18n="chrome.overlay.modeloEducativo.filosofia">Filosofía</a></li><li><a href="/modelo-educativo/aprendizaje-integral" data-i18n="chrome.overlay.modeloEducativo.aprendizajeIntegral">Aprendizaje integral</a></li><li><a href="/modelo-educativo/idiomas" data-i18n="chrome.overlay.modeloEducativo.idiomas">Idiomas</a></li></ul></li>
                <li class="nav-accordion-item"><button class="nav-accordion-trigger"><span data-i18n="chrome.overlay.accordion.nivelesAcademicos">Niveles académicos</span> <span class="chevron">▼</span></button><ul class="nav-submenu"><li><a href="/niveles-academicos/preescolar" data-i18n="chrome.overlay.niveles.preescolar">Preescolar</a></li><li><a href="/niveles-academicos/primaria" data-i18n="chrome.overlay.niveles.primaria">Primaria</a></li><li><a href="/niveles-academicos/secundaria" data-i18n="chrome.overlay.niveles.secundaria">Secundaria</a></li><li><a href="/niveles-academicos/preparatoria" data-i18n="chrome.overlay.niveles.preparatoria">Preparatoria</a></li></ul></li>
                <li class="nav-accordion-item"><button class="nav-accordion-trigger"><span data-i18n="chrome.overlay.accordion.vidaEscolar">Vida escolar</span> <span class="chevron">▼</span></button><ul class="nav-submenu"><li><a href="/vida-escolar/afterschool-extracurriculares" data-i18n="chrome.overlay.vidaEscolar.afterschool">Afterschool</a></li><li><a href="/vida-escolar/futuro-universitario-becas" data-i18n="chrome.overlay.vidaEscolar.futuroUniversitario">Futuro universitario</a></li><li><a href="/vida-escolar/programa-dual" data-i18n="chrome.overlay.vidaEscolar.programaDual">Programa Dual</a></li><li><a href="/vida-escolar/servicios-para-familias" data-i18n="chrome.overlay.vidaEscolar.servicios">Servicios</a></li><li><a href="/vida-escolar/cuidado-y-bienestar" data-i18n="chrome.overlay.vidaEscolar.cuidadoBienestar">Cuidado y bienestar</a></li><li><a href="/vida-escolar/eventos-y-tradiciones" data-i18n="chrome.overlay.vidaEscolar.eventos">Eventos</a></li></ul></li>
                <li class="nav-accordion-item"><button class="nav-accordion-trigger"><span data-i18n="chrome.overlay.accordion.admisiones">Admisiones</span> <span class="chevron">▼</span></button><ul class="nav-submenu"><li><a href="/admisiones" data-i18n="chrome.overlay.admisiones.inicio">Inicio</a></li><li><a href="/admisiones/proceso" data-i18n="chrome.overlay.admisiones.proceso">Proceso</a></li><li><a href="/admisiones/preguntas-frecuentes" data-i18n="chrome.overlay.admisiones.faq">FAQ</a></li><li><a href="/admisiones/convenios" data-i18n="chrome.overlay.admisiones.convenios">Convenios</a></li><li><a href="/admisiones/convocatoria-becas" data-i18n="chrome.overlay.admisiones.becas">Becas</a></li><li><a href="/admisiones/contacto" data-i18n="chrome.overlay.admisiones.contacto">Contacto</a></li></ul></li>
                <li class="nav-accordion-item"><button class="nav-accordion-trigger"><span data-i18n="chrome.overlay.accordion.comunidad">Comunidad</span> <span class="chevron">▼</span></button><ul class="nav-submenu"><li><a href="/comunidad/estudiantes" data-i18n="chrome.overlay.comunidad.estudiantes">Estudiantes</a></li><li><a href="/comunidad/familias" data-i18n="chrome.overlay.comunidad.familias">Familias</a></li><li><a href="/comunidad/exalumnos" data-i18n="chrome.overlay.comunidad.exalumnos">Exalumnos</a></li></ul></li>
                <li class="nav-accordion-item"><button class="nav-accordion-trigger"><span data-i18n="chrome.overlay.accordion.vocesBilbao">Voces Bilbao</span> <span class="chevron">▼</span></button><ul class="nav-submenu"><li><a href="/noticias" data-i18n="chrome.overlay.voces.noticias">Noticias</a></li><li><a href="/blog" data-i18n="chrome.overlay.voces.articulos">Artículos</a></li><li><a href="/feedback-testimoniales" data-i18n="chrome.overlay.voces.testimonios">Testimonios</a></li></ul></li>
                <li class="nav-accordion-item"><button class="nav-accordion-trigger"><span data-i18n="chrome.overlay.accordion.contacto">Contacto</span> <span class="chevron">▼</span></button><ul class="nav-submenu"><li><a href="/contacto" data-i18n="chrome.overlay.contacto.contacto">Contacto</a></li><li><a href="/contacto/directorio" data-i18n="chrome.overlay.contacto.directorio">Directorio</a></li><li><a href="/contacto/cultura-y-talento" data-i18n="chrome.overlay.contacto.culturaTalento">Cultura y talento</a></li><li><a href="/contacto/proveedores" data-i18n="chrome.overlay.contacto.proveedores">Proveedores</a></li></ul></li>
            </ul>
        </nav>
    </div>
