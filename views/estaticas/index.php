    <!-- MAIN CONTENT -->
    <main>
        <!-- 1. HERO -->
        <section class="hero-flagship">
            <!-- Poster SOLO móvil -->
            <img
                class="hero-poster"
                src="build/assets/img/home/poster1.jpg"
                alt=""
                loading="eager"
                decoding="async"
            />

            <!-- Video SOLO desktop -->
            <video
                class="hero-video-bg"
                autoplay
                muted
                loop
                playsinline
                preload="metadata"
                poster="build/assets/img/home/poster2.jpg"
            >
                <source src="build/assets/vid/home/bg_high.mp4" type="video/mp4" />
            </video>

            <div class="hero-overlay"></div>

            <div class="hero-content">
                <span class="hero-subtitle-tag">Este es tu lugar: <strong>Colegio Bilbao</strong></span>
                <h1 class="hero-title" id="dynamic-hero-title">Learning to become yourself</h1>
                <div class="hero-cta-wrapper">
                <a href="https://wa.me/525614612682?text=Hola,%20acabo%20de%20ver%20la%20página%20del%20colegio,%20me%20gustó%20y%20quiero%20conocerlos%20en%20una%20visita%20guiada."
                    class="btn-primario">Agenda tu visita</a>
                </div>
            </div>
        </section>        

        <!-- 2. BIENVENIDA -->
        <section class="welcome-section reveal">
            <h2 class="welcome-lead">
                Más que una escuela, somos una <strong>comunidad que se vive.</strong>
            </h2>
            <div style="width:60px; height:4px; background:var(--col-bilbao); margin:20px auto; border-radius:2px;"></div>
            <p style="max-width:800px; margin:0 auto; font-size:1.15rem; color:var(--col-herencia);">
                Bienvenidos al Colegio Bilbao. Somos una escuela privada K-12 en la zona poniente de CDMX. Formamos criterio, autonomía y sensibilidad humana, con acompañamiento cercano. Gracias por considerarnos para tu familia.
            </p>
        </section>

 <!-- 3. STATS  -->
        <section class="stats-banner"> <!-- Wrapper to center -->
            <div class="stats-grid"> <!-- The Card -->
                <div class="stat-item reveal">
                    <h3 data-target="38" data-plus="true">0</h3>
                    <p>Años de experiencia</p>
                </div>
                <div class="stat-item reveal" style="transition-delay:0.1s">
                    <h3 data-target="30000" data-suffix="m²">0</h3>
                    <p>de bosque como aula</p>
                </div>
                <div class="stat-item reveal" style="transition-delay:0.2s">
                    <h3 data-target="3">0</h3>
                    <p>Idiomas</p>
                </div>
                <div class="stat-item reveal" style="transition-delay:0.3s">
                    <h3 data-target="200" data-plus="true">0</h3>
                    <p>Universidades aliadas</p>
                </div>
            </div>
        </section>
        
        <!-- 4. NIVELES ACADÉMICOS -->
        <section class="levels-section reveal">
            <div class="section-header-center">
                <h2 style="font-size:2.5rem; color:var(--col-bilbao); margin-top:10px;">Nuestra oferta educativa</h2>
<span class="section-tag">Un mismo proyecto educativo, con objetivos claros por nivel</span>
            </div>
            
            <div class="levels-container">
                <!-- Preescolar -->
                <a href="niveles-academicos/preescolar" class="level-card">
                    <img src="build/assets/img/niveles-academicos/preescolar/nino-burbujas.jpg" alt="Preescolar" class="level-bg" loading="lazy">
                    <div class="level-content">
                        <span class="panel-num">01</span>
                        <h3 class="level-title">Preescolar</h3>
                        <div class="level-desc">Juego, exploración y seguridad emocional para sus primeros pasos.</div>
                        <span class="level-link">Ver nivel →</span>
                    </div>
                </a>
                
                <!-- Primaria -->
                <a href="niveles-academicos/primaria" class="level-card">
                    <img src="build/assets/img/niveles-academicos/primaria/alumna-escribiendo.jpg" alt="Primaria" class="level-bg" loading="lazy">
                    <div class="level-content">
                        <span class="panel-num">02</span>
                        <h3 class="level-title">Primaria</h3>
                        <div class="level-desc">Aprendizaje activo, curiosidad y fundamentos sólidos.</div>
                        <span class="level-link">Ver nivel →</span>
                    </div>
                </a>

                <!-- Secundaria -->
                <a href="niveles-academicos/secundaria" class="level-card">
                    <img src="build/assets/img/niveles-academicos/secundaria/alumnas-argumentando.jpg" alt="Secundaria" class="level-bg" loading="lazy">
                    <div class="level-content">
                        <span class="panel-num">03</span>
                        <h3 class="level-title">Secundaria</h3>
                        <div class="level-desc">Pensamiento crítico, diálogo y acompañamiento en la adolescencia.</div>
                        <span class="level-link">Ver nivel →</span>
                    </div>
                </a>

                <!-- Preparatoria -->
                <a href="niveles-academicos/preparatoria" class="level-card">
                    <img src="build/assets/img/niveles-academicos/preparatoria/alumnos-felices.jpg" alt="Preparatoria" class="level-bg" loading="lazy">
                    <div class="level-content">
                        <span class="panel-num">04</span>
                        <h3 class="level-title">Preparatoria</h3>
                        <div class="level-desc">Diseño de futuro, certificación dual y proyección universitaria.</div>
                        <span class="level-link">Ver nivel →</span>
                    </div>
                </a>
            </div>
        </section>

        <!-- 5. MODELO VIDA  -->
        <section class="vida-showcase reveal">
            <!-- Formas de fondo -->
            <div class="vida-blob blob-1"></div>
            <div class="vida-blob blob-2"></div>
            
            <div class="vida-container">
                <div style="margin-bottom: 40px;">
                    <h2 style="font-size:3rem; font-weight:800; color:white; margin-bottom:16px;">Modelo Educativo VIDA</h2>
                    <p style="font-size:1.2rem; color:rgba(255,255,255,0.9); font-weight:300;">Es la guía de lo que se vive en aulas, proyectos y nuestra comunidad.</p>
                </div>

                <div class="vida-grid-new">
                    <!-- Card V -->
                    <div class="vida-card-new">
                        <span class="vc-letter">V</span>
                        <h3 class="vc-title">Vincula</h3>
                        <p class="vc-desc">Comunidad y lazos fuertes.</p>
                    </div>
                    <!-- Card I -->
                    <div class="vida-card-new">
                        <span class="vc-letter">I</span>
                        <h3 class="vc-title">Indaga</h3>
                        <p class="vc-desc">Preguntas que despiertan.</p>
                    </div>
                    <!-- Card D -->
                    <div class="vida-card-new">
                        <span class="vc-letter">D</span>
                        <h3 class="vc-title">Descubre</h3>
                        <p class="vc-desc">Aprender haciendo.</p>
                    </div>
                    <!-- Card A -->
                    <div class="vida-card-new">
                        <span class="vc-letter">A</span>
                        <h3 class="vc-title">Aporta</h3>
                        <p class="vc-desc">Acción con sentido.</p>
                    </div>
                </div>

                <a href="modelo-educativo/modelo-vida" class="btn-secundario">Conocer Modelo</a>
            </div>
        </section>

 <!-- 6. Descubre el Colegio Bilbao -->
        <section class="carousel-section reveal">
            <div class="container" style="text-align:center; margin-bottom:3rem; max-width: 1400px; margin-left: auto; margin-right: auto; padding: 0 20px;">
                <h2 style="font-size:2.5rem; font-weight:800; color:var(--col-bilbao);">Descubre el Colegio Bilbao</h2>
<span class="section-tag">Elige el tema que más te interesa para conocer mejor la escuela.</span>
            </div>
            
            <div class="carousel-track-wrapper">
                <div class="carousel-track">
                    <!-- Slide 1 -->
                    <a href="/admisiones/inicio" class="carousel-slide">
                        <img src="build/assets/img/admisiones/inicio/llegando-al-bilbao.jpg" alt="Admisiones" loading="lazy"> 
                        <div class="carousel-overlay">
                            <span class="slide-title">Admisiones</span>
                            <span class="slide-cta">Ver Más →</span>
                        </div>
                    </a>
                    <!-- Slide 2 -->
                    <a href="/conocenos/equipo-educativo" class="carousel-slide">
                        <img src="build/assets/img/conocenos/quienes-somos/relacion-maestro-alumno.jpg" alt="Equipo educativo" loading="lazy">
                        <div class="carousel-overlay">
                            <span class="slide-title">Equipo educativo</span>
                            <span class="slide-cta">Ver Más →</span>
                        </div>
                    </a>
                    <!-- Slide 3 -->
                    <a href="/conocenos/instalaciones" class="carousel-slide">
                        <img src="build/assets/img/conocenos/instalaciones/edificio-preescolar.jpg" alt="Instalaciones" loading="lazy">
                        <div class="carousel-overlay">
                            <span class="slide-title">Instalaciones</span>
                            <span class="slide-cta">Ver Más →</span>
                        </div>
                    </a>
                    <!-- Slide 4 -->
                    <a href="/modelo-educativo/idiomas" class="carousel-slide">
                        <img src="build/assets/img/modelo-educativo/idiomas/kids.jpg" alt="Idiomas" loading="lazy">
                        <div class="carousel-overlay">
                            <span class="slide-title">Idiomas</span>
                            <span class="slide-cta">Ver Más →</span>
                        </div>
                    </a>
                    <!-- Slide 5 -->
                    <a href="/vida-escolar/futuro-universitario-becas" class="carousel-slide">
                        <img src="build/assets/img/niveles-academicos/preparatoria/alumnos-anotando.jpg" alt="Futuro universitario" loading="lazy">
                        <div class="carousel-overlay">
                            <span class="slide-title">Futuro universitario</span>
                            <span class="slide-cta">Ver Más →</span>
                        </div>
                    </a>
                     <!-- Slide 6 (Duplicate for loop) -->
                    <a href="/admisiones/inicio" class="carousel-slide">
                        <img src="build/assets/img/admisiones/inicio/vida.jpg" alt="Admisiones" loading="lazy">
                         <div class="carousel-overlay">
                            <span class="slide-title"> Admisiones </span>
                            <span class="slide-cta">Ver Más →</span>
                        </div>
                    </a>
                    <!-- Slide 7 (Duplicate for loop safety) -->
                     <a href="/conocenos/equipo-educativo" class="carousel-slide">
                        <img src="build/assets/img/conocenos/equipo-educativo/relacion-maestro-alumno.jpg" alt="Equipo educativo" loading="lazy">
                        <div class="carousel-overlay">
                            <span class="slide-title">Equipo educativo</span>
                            <span class="slide-cta">Ver Más →</span>
                        </div>
                    </a>
                     <!-- Slide 8 (Duplicate for loop safety) -->
                     <a href="/modelo-educativo/idiomas/" class="carousel-slide">
                        <img src="build/assets/img/modelo-educativo/idiomas/kids.jpg" alt="Idiomas" loading="lazy">
                        <div class="carousel-overlay">
                            <span class="slide-title">Instalaciones</span>
                            <span class="slide-cta">Ver Más →</span>
                        </div>
                    </a>
                     <!-- Slide 9 (Duplicate for loop safety) -->
                    <a href="/modelo-educativo/ciencias/" class="carousel-slide">
                        <img src="build/assets/img/modelo-educativo/aprendizaje-integral/ciencia.jpg" alt="Idiomas" loading="lazy">
                        <div class="carousel-overlay">
                            <span class="slide-title"> Idiomas </span>
                            <span class="slide-cta">Ver Más →</span>
                        </div>
                    </a>
                    <!-- Slide 10 (Duplicate for loop safety) -->
                    <a href="/vida-escolar/futuro-universitario-becas/" class="carousel-slide">
                        <img src="build/assets/img/niveles-academicos/preparatoria/alumnos-anotando.jpg" alt="Futuro universitario" loading="lazy">
                        <div class="carousel-overlay">
                            <span class="slide-title">Futuro universitario</span>
                            <span class="slide-cta">Ver Más →</span>
                        </div>
                    </a>
                </div>
            </div>
        </section>

        <!-- 7. COMENTARIOS -->
        <section class="voices-section">
            <div class="voices-header">
                <h2 class="voices-title">Voces Bilbao</h2>
                <span class="section-tag">Lo que dicen las familias del Colegio Bilbao.</span>
            </div>

            <!-- Carrusel Infinito Optimizado -->
            <div class="marquee-container" style="width:100%; overflow:hidden;">
                <div class="marquee-track" style="display:flex; gap:30px; width:max-content; animation:scrollMarquee 45s linear infinite; will-change: transform;">
                     <!-- Tarjetas simplificadas para reducir DOM -->
                    <div class="voice-card" style="width:350px; flex-shrink:0; background:white; border-radius:20px; padding:30px; border:1px solid #E0E6ED;">
                        <p style="font-size:1.1rem; color:var(--col-texto); font-style:italic; margin-bottom:20px;">"El mejor colegio para mis hijos. La calidad humana es incomparable."</p>
                        <div style="display:flex; align-items:center; gap:12px;">
                            <img src="/build/assets/img/inicio/icono-usuario.png" alt="Usuario" style="width:50px; height:50px; border-radius:50%; object-fit:cover;" loading="lazy">
                            <div><strong style="display:block; color:var(--col-bilbao); font-size:0.95rem;">Paulina C.</strong><span style="font-size:0.85rem; color:#999;">Mamá Primaria</span></div>
                        </div>
                    </div>
                     <div class="voice-card" style="width:350px; flex-shrink:0; background:white; border-radius:20px; padding:30px; border:1px solid #E0E6ED;">
                        <p style="font-size:1.1rem; color:var(--col-texto); font-style:italic; margin-bottom:20px;">“Se sienten vistos, no evaluados todo el tiempo.”</p>
                        <div style="display:flex; align-items:center; gap:12px;">
                            <img src="/build/assets/img/inicio/icono-usuario.png" alt="Usuario" style="width:50px; height:50px; border-radius:50%; object-fit:cover;" loading="lazy">                            
                            <div><strong style="display:block; color:var(--col-bilbao); font-size:0.95rem;"> Mario T.</strong><span style="font-size:0.85rem; color:#999;"> Papá Secundaria</span></div>
                        </div>
                    </div>
                     <div class="voice-card" style="width:350px; flex-shrink:0; background:white; border-radius:20px; padding:30px; border:1px solid #E0E6ED;">
                        <p style="font-size:1.1rem; color:var(--col-texto); font-style:italic; margin-bottom:20px;">“Mi hijo volvió a disfrutar aprender.”</p>
                        <div style="display:flex; align-items:center; gap:12px;">
                            <img src="/build/assets/img/inicio/icono-usuario.png" alt="Usuario" style="width:50px; height:50px; border-radius:50%; object-fit:cover;" loading="lazy">                            
                            <div><strong style="display:block; color:var(--col-bilbao); font-size:0.95rem;">Jorge M.</strong><span style="font-size:0.85rem; color:#999;">Papá Prepa</span></div>
                        </div>
                    </div>
                     <!-- Duplicados para loop visual -->
                     <div class="voice-card" style="width:350px; flex-shrink:0; background:white; border-radius:20px; padding:30px; border:1px solid #E0E6ED;">
                        <p style="font-size:1.1rem; color:var(--col-texto); font-style:italic; margin-bottom:20px;">"El mejor colegio para mis hijos. La calidad humana es incomparable."</p>
                        <div style="display:flex; align-items:center; gap:12px;">
                            <img src="/build/assets/img/inicio/icono-usuario.png" alt="Usuario" style="width:50px; height:50px; border-radius:50%; object-fit:cover;" loading="lazy">                            
                            <div><strong style="display:block; color:var(--col-bilbao); font-size:0.95rem;">Familia Sánchez</strong><span style="font-size:0.85rem; color:#999;">Primaria</span></div>
                        </div>
                    </div>
                     <div class="voice-card" style="width:350px; flex-shrink:0; background:white; border-radius:20px; padding:30px; border:1px solid #E0E6ED;" loading="lazy">
                        <p style="font-size:1.1rem; color:var(--col-texto); font-style:italic; margin-bottom:20px;">"La naturaleza sí se usa. No es fondo para fotos."</p>
                        <div style="display:flex; align-items:center; gap:12px;">
                            <img src="/build/assets/img/inicio/icono-usuario.png" alt="Usuario" style="width:50px; height:50px; border-radius:50%; object-fit:cover;" loading="lazy">                            
                            <div><strong style="display:block; color:var(--col-bilbao); font-size:0.95rem;">Anna G.</strong><span style="font-size:0.85rem; color:#999;">Mamá Preescolar</span></div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        
 <!-- 8. NOTICIAS -->
        <section class="news-section">
            <div class="container" style="max-width: 1200px; margin: 0 auto;">
                <div class="section-header-center reveal">
                    <h2 style="font-size:2.5rem; color:var(--col-bilbao); margin-top:10px; margin-bottom:1rem;">Momentos Bilbao</h2>
<span class="section-tag" style="text-transform:uppercase; letter-spacing:2px; font-weight:700; font-size:0.9rem; color:var(--col-espiritu);">Conoce lo que vivimos</span>
                </div>
                
                <div class="news-grid">
                    <a href="https://www.excelsior.com.mx/nacional/egresados-del-colegio-bilbao-con-acceso-a-mas-de-200-universidades-en-mexico-y-el-mundo" class="news-item featured reveal">
                        <img src="build/assets/img/inicio/nota-1.jpeg" alt="Noticia Principal" class="news-img" loading="lazy">
                        <div class="news-overlay">
                            <span class="news-date">Destacado</span>
                            <h3 class="news-title-card">EXCELSIOR: Egresados del Colegio Bilbao, con acceso a más de 200 universidades en México y el mundo</h3>
                        </div>
                    </a>
                    <a href="https://www.youtube.com/watch?v=trRy2F8KFcU" class="news-item reveal" style="transition-delay:0.1s">
                        <img src="build/assets/img/inicio/entrevista.png" alt="Noticia 2" class="news-img" loading="lazy">
                        <div class="news-overlay">
                            <span class="news-date">Destacado</span>
                            <h3 class="news-title-card">Entrevista: ¿Qué hace diferente a una escuela cuando realmente se convierte en comunidad?</h3>
                        </div>
                    </a>
                    <a href="https://www.youtube.com/watch?v=gDbAl_3y9Bc" class="news-item reveal" style="transition-delay:0.2s">
                        <img src="build/assets/img/inicio/entrevista-2.jpg" alt="Noticia 3" class="news-img" loading="lazy">
                        <div class="news-overlay">
                            <span class="news-date">Destacado</span>
                            <h3 class="news-title-card">Entrevista: ¿Qué se siente volver a casa… ahora como maestro?</h3>
                        </div>
                    </a>
                </div>
            </div>
        </section>

        <section class="blog-cta-strip reveal">
            <div class="blog-cta-inner">
                <div class="blog-cta-copy">
                    <h3>Explora Voces Bilbao</h3>
                    <p>
                        Descubre artículos, entrevistas, noticias y momentos que reflejan la vida, la filosofía y la comunidad del Colegio Bilbao.
                    </p>
                </div>
                <a href="/blog" class="btn-primario">Visitar el blog</a>
            </div>
        </section>

        <!-- 9. ALUMNI -->
        <section class="alumni-section reveal">
            <div class="alumni-content">
                <h3 class="alumni-title">Alumni Bilbao</h3>
                <p class="alumni-text">Vuelve a conectar, esta sigue siendo tu casa. Queremos reencontrarnos y crear nuevos espacios de conexión. Comunidad de exalumnos.</p>
                <a href="/comunidad/exalumnos" class="btn-secundario">Volver a conectar</a>
            </div>
        </section>

        <!-- 10. CTA -->
        <section class="final-cta">
            <h2 class="cta-big-h2">El futuro de tus hijos<br>comienza aquí.</h2>
            <a href="https://wa.me/525614612682?text=Hola,%20acabo%20de%20ver%20la%20página%20del%20colegio,%20me%20gustó%20y%20quiero%20conocerlos%20en%20una%20visita%20guiada." class="btn-primario">Agenda una visita hoy</a>
        </section>

    </main>
