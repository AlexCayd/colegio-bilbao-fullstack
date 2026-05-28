    <!-- MAIN CONTENT -->
    <main>

        <!-- ================================================
             1. HERO
        ================================================ -->
        <section class="hero-flagship">

            <!-- Poster solo móvil -->
            <img
                class="hero-poster"
                src="/build/assets/img/home/poster1.jpg"
                alt=""
                loading="eager"
                decoding="async"
            />

            <!-- Video solo desktop -->
            <video
                class="hero-video-bg"
                autoplay muted loop playsinline
                preload="metadata"
                poster="/build/assets/img/home/poster2.jpg"
            >
                <source src="/build/assets/vid/home/bg_high.mp4" type="video/mp4" />
            </video>

            <div class="hero-overlay"></div>

            <!-- Acento azul creativo en la base del hero -->
            <div class="hero-blue-bottom" aria-hidden="true"></div>
            <div class="hero-blue-stripe" aria-hidden="true"></div>

            <!-- Contenido principal -->
            <div class="hero-content">
                <h1 class="hero-title" id="dynamic-hero-title">
                    La naturaleza es nuestro salón
                </h1>
                <p class="hero-sub">
                    Aquí los estudiantes aprenden a pensar, a cuestionarse y a encontrar su propio camino.
                </p>
                <div class="hero-cta-wrapper">
                    <a
                        href="https://wa.me/525614612682?text=Hola,%20acabo%20de%20ver%20la%20página%20del%20colegio,%20me%20gustó%20y%20quiero%20conocerlos%20en%20una%20visita%20guiada."
                        class="btn-primario"
                        style="gap:8px"
                        target="_blank" rel="noopener"
                    >
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                            <path d="M12 0C5.373 0 0 5.373 0 12c0 2.096.537 4.068 1.482 5.792L0 24l6.375-1.456C8.067 23.48 10.004 24 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.818c-1.859 0-3.584-.509-5.058-1.393l-.362-.215-3.755.857.906-3.641-.236-.374C2.633 15.553 2.182 13.823 2.182 12 2.182 6.575 6.575 2.182 12 2.182S21.818 6.575 21.818 12 17.425 21.818 12 21.818z"/>
                        </svg>
                        Agenda tu visita
                    </a>
                    <a href="#nuestra-oferta" class="btn-outline-hero">
                        Conocer el colegio ↓
                    </a>
                </div>
            </div>

            <!-- Indicador de scroll -->
            <div class="hero-scroll-indicator" aria-hidden="true">
                <span class="scroll-dot"></span>
            </div>
        </section>


        <!-- ================================================
             2. STATS
        ================================================ -->
        <section class="stats-banner">
            <div class="stats-grid">
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


        <!-- ================================================
             3. NIVELES ACADÉMICOS
        ================================================ -->
        <section class="levels-section reveal" id="nuestra-oferta">
            <div class="section-header-center">
                <span class="section-tag">Un mismo proyecto educativo, con objetivos claros por nivel</span>
                <h2 class="section-h2">Nuestra oferta educativa</h2>
            </div>

            <div class="levels-container">

                <!-- Preescolar -->
                <a href="/niveles-academicos/preescolar" class="level-card">
                    <img src="/build/assets/img/niveles-academicos/preescolar/nino-burbujas.jpg"
                         alt="Preescolar" class="level-bg" loading="lazy">
                    <div class="level-alex-mascot">
                        <img src="/build/assets/img/niveles-academicos/preescolar/bby-alex-feliz.png"
                             alt="" loading="lazy">
                    </div>
                    <div class="level-content">
                        <span class="panel-num">01</span>
                        <h3 class="level-title">Preescolar</h3>
                        <div class="level-desc">Juego, exploración y seguridad emocional para sus primeros pasos.</div>
                        <span class="level-link">Ver nivel →</span>
                    </div>
                </a>

                <!-- Primaria -->
                <a href="/niveles-academicos/primaria" class="level-card">
                    <img src="/build/assets/img/niveles-academicos/primaria/alumna-escribiendo.jpg"
                         alt="Primaria" class="level-bg" loading="lazy">
                    <div class="level-alex-mascot">
                        <img src="/build/assets/img/niveles-academicos/primaria/kid-alex-explora.png"
                             alt="" loading="lazy">
                    </div>
                    <div class="level-content">
                        <span class="panel-num">02</span>
                        <h3 class="level-title">Primaria</h3>
                        <div class="level-desc">Aprendizaje activo, curiosidad y fundamentos sólidos.</div>
                        <span class="level-link">Ver nivel →</span>
                    </div>
                </a>

                <!-- Secundaria -->
                <a href="/niveles-academicos/secundaria" class="level-card">
                    <img src="/build/assets/img/niveles-academicos/secundaria/alumnas-argumentando.jpg"
                         alt="Secundaria" class="level-bg" loading="lazy">
                    <div class="level-alex-mascot">
                        <img src="/build/assets/img/niveles-academicos/secundaria/teen-alex-camina.png"
                             alt="" loading="lazy">
                    </div>
                    <div class="level-content">
                        <span class="panel-num">03</span>
                        <h3 class="level-title">Secundaria</h3>
                        <div class="level-desc">Pensamiento crítico, diálogo y acompañamiento en la adolescencia.</div>
                        <span class="level-link">Ver nivel →</span>
                    </div>
                </a>

                <!-- Preparatoria -->
                <a href="/niveles-academicos/preparatoria" class="level-card">
                    <img src="/build/assets/img/niveles-academicos/preparatoria/alumnos-felices.jpg"
                         alt="Preparatoria" class="level-bg" loading="lazy">
                    <div class="level-alex-mascot">
                        <img src="/build/assets/img/niveles-academicos/preparatoria/alex-medita.png"
                             alt="" loading="lazy">
                    </div>
                    <div class="level-content">
                        <span class="panel-num">04</span>
                        <h3 class="level-title">Preparatoria</h3>
                        <div class="level-desc">Diseño de futuro, certificación dual y proyección universitaria.</div>
                        <span class="level-link">Ver nivel →</span>
                    </div>
                </a>
            </div>
        </section>


        <!-- ================================================
             5. MODELO EDUCATIVO VIDA
        ================================================ -->
        <section class="vida-showcase reveal">
            <div class="vida-blob blob-1"></div>
            <div class="vida-blob blob-2"></div>

            <!-- Alex decorativo -->
            <div class="vida-alex-deco" aria-hidden="true">
                <img src="/build/assets/img/modelo-educativo/modelo-vida/alex-pinta.png"
                     alt="" loading="lazy">
            </div>

            <div class="vida-container">
                <div>
                    <h2 class="vida-h2">Modelo Educativo VIDA</h2>
                    <p class="vida-p">La guía de lo que se vive en aulas, proyectos y comunidad.</p>
                </div>

                <div class="vida-grid-new">
                    <div class="vida-card-new reveal" style="transition-delay:0s">
                        <span class="vc-letter">V</span>
                        <h3 class="vc-title">Vincula</h3>
                        <p class="vc-desc">Comunidad y lazos fuertes entre personas.</p>
                    </div>
                    <div class="vida-card-new reveal" style="transition-delay:0.1s">
                        <span class="vc-letter">I</span>
                        <h3 class="vc-title">Indaga</h3>
                        <p class="vc-desc">Preguntas que despiertan el pensamiento.</p>
                    </div>
                    <div class="vida-card-new reveal" style="transition-delay:0.2s">
                        <span class="vc-letter">D</span>
                        <h3 class="vc-title">Descubre</h3>
                        <p class="vc-desc">Aprender haciendo, con las manos y la mente.</p>
                    </div>
                    <div class="vida-card-new reveal" style="transition-delay:0.3s">
                        <span class="vc-letter">A</span>
                        <h3 class="vc-title">Aporta</h3>
                        <p class="vc-desc">Acción con sentido para el mundo.</p>
                    </div>
                </div>

                <a href="/modelo-educativo/modelo-vida" class="btn-secundario">
                    Conocer el Modelo VIDA
                </a>
            </div>
        </section>


        <!-- ================================================
             6. DESCUBRE EL COLEGIO (Grid interactivo)
        ================================================ -->
        <section class="explore-section reveal">
            <div class="explore-header">
                <h2>Descubre el Colegio Bilbao</h2>
                <p class="explore-subtitle">Elige el tema que más te interesa para conocer mejor la escuela.</p>
            </div>

            <div class="explore-grid">

                <!-- Card 1: Admisiones — tall izquierda -->
                <a href="/admisiones/inicio" class="explore-card">
                    <img src="/build/assets/img/admisiones/inicio/llegando-al-bilbao.jpg"
                         alt="Admisiones" loading="lazy">
                    <div class="explore-overlay">
                        <span class="explore-num">01</span>
                        <h3 class="explore-title">Admisiones</h3>
                        <p class="explore-desc">Sin examen de admisión. Visita guiada y acompañamiento personalizado desde el primer contacto.</p>
                        <span class="explore-cta">Conocer proceso →</span>
                    </div>
                </a>

                <!-- Card 2: Equipo educativo — row 1 col 2 -->
                <a href="/conocenos/equipo-educativo" class="explore-card">
                    <img src="/build/assets/img/conocenos/equipo-educativo/maestro-escucha.jpg"
                         alt="Equipo educativo" loading="lazy">
                    <div class="explore-overlay">
                        <span class="explore-num">02</span>
                        <h3 class="explore-title">Equipo educativo</h3>
                        <p class="explore-desc">Maestros que escuchan, preguntan y construyen junto con sus alumnos.</p>
                        <span class="explore-cta">Conocer al equipo →</span>
                    </div>
                </a>

                <!-- Card 3: Idiomas — row 1 col 3 -->
                <a href="/modelo-educativo/idiomas" class="explore-card">
                    <img src="/build/assets/img/modelo-educativo/idiomas/reading.jpg"
                         alt="Idiomas" loading="lazy">
                    <div class="explore-overlay">
                        <span class="explore-num">03</span>
                        <h3 class="explore-title">Idiomas</h3>
                        <p class="explore-desc">Español, inglés y alemán integrados al currículo desde preescolar.</p>
                        <span class="explore-cta">Ver programa →</span>
                    </div>
                </a>

                <!-- Card 4: Aprendizaje Integral — row 2 col 2 -->
                <a href="/modelo-educativo/aprendizaje-integral" class="explore-card">
                    <img src="/build/assets/img/modelo-educativo/aprendizaje-integral/ciencia.jpg"
                         alt="Aprendizaje Integral" loading="lazy">
                    <div class="explore-overlay">
                        <span class="explore-num">04</span>
                        <h3 class="explore-title">Aprendizaje Integral</h3>
                        <p class="explore-desc">Arte, ciencia, tecnología, deporte y humanidades en un solo proyecto educativo.</p>
                        <span class="explore-cta">Explorar →</span>
                    </div>
                </a>

                <!-- Card 5: Futuro universitario — row 2 col 3 -->
                <a href="/vida-escolar/futuro-universitario-becas" class="explore-card">
                    <img src="/build/assets/img/niveles-academicos/preparatoria/alumnos-anotando.jpg"
                         alt="Futuro universitario" loading="lazy">
                    <div class="explore-overlay">
                        <span class="explore-num">05</span>
                        <h3 class="explore-title">Futuro universitario</h3>
                        <p class="explore-desc">Más de 200 universidades aliadas en México y el mundo para nuestros egresados.</p>
                        <span class="explore-cta">Ver opciones →</span>
                    </div>
                </a>

                <!-- Card 6: Instalaciones — tall derecha -->
                <a href="/conocenos/instalaciones" class="explore-card">
                    <img src="/build/assets/img/conocenos/instalaciones/cancha-futbol.jpg"
                         alt="Instalaciones" loading="lazy">
                    <div class="explore-overlay">
                        <span class="explore-num">06</span>
                        <h3 class="explore-title">Instalaciones</h3>
                        <p class="explore-desc">30,000 m² de espacio diseñado para aprender en movimiento y en contacto con la naturaleza.</p>
                        <span class="explore-cta">Ver instalaciones →</span>
                    </div>
                </a>

            </div>
        </section>


        <!-- ================================================
             7. VOCES / TESTIMONIOS
        ================================================ -->
        <section class="voices-section">
            <div class="voices-header">
                <span class="section-tag">Lo que dicen las familias del Colegio Bilbao</span>
                <h2 class="voices-title">Familias Bilbao</h2>
                <span class="voices-stars" aria-label="5 estrellas de 5">★★★★★</span>
            </div>

            <div class="marquee-container">
                <div class="marquee-track">
                    <div class="voice-card">
                        <p class="voice-quote">"Fui a la junta de inicio de año pensando que duraría veinte minutos. Me quedé hora y media platicando con los maestros. Eso ya me dijo bastante."</p>
                        <div class="voice-author">
                            <img src="/build/assets/img/inicio/icono-usuario.png" alt="" class="author-avatar" loading="lazy">
                            <div class="author-info">
                                <strong>Claudia R.</strong>
                                <span>Mamá · Primaria</span>
                            </div>
                        </div>
                    </div>
                    <div class="voice-card">
                        <p class="voice-quote">"La primera semana mi hijo me decía que la escuela era rara porque los dejaban escoger qué trabajar. Ahora si le pregunto si quiere cambiarse me ve como si estuviera loco."</p>
                        <div class="voice-author">
                            <img src="/build/assets/img/inicio/icono-usuario.png" alt="" class="author-avatar" loading="lazy">
                            <div class="author-info">
                                <strong>Roberto C.</strong>
                                <span>Papá · Primaria</span>
                            </div>
                        </div>
                    </div>
                    <div class="voice-card">
                        <p class="voice-quote">"Llevábamos como seis meses buscando escuela. Vinimos aquí casi de casualidad. Cancelé las otras citas esa misma tarde."</p>
                        <div class="voice-author">
                            <img src="/build/assets/img/inicio/icono-usuario.png" alt="" class="author-avatar" loading="lazy">
                            <div class="author-info">
                                <strong>Familia Torres</strong>
                                <span>Dos hijos · Preescolar y Primaria</span>
                            </div>
                        </div>
                    </div>
                    <div class="voice-card">
                        <p class="voice-quote">"Mi hija tiene 14 y venía de una secundaria muy tradicional. El cambio fue difícil los primeros meses. Ahora dice que fue lo mejor que hicimos."</p>
                        <div class="voice-author">
                            <img src="/build/assets/img/inicio/icono-usuario.png" alt="" class="author-avatar" loading="lazy">
                            <div class="author-info">
                                <strong>Verónica M.</strong>
                                <span>Mamá · Preparatoria</span>
                            </div>
                        </div>
                    </div>
                    <div class="voice-card">
                        <p class="voice-quote">"Un día llegué a recogerlo y se estaban tardando porque estaban terminando un experimento que ellos mismos habían propuesto. No me importó esperar."</p>
                        <div class="voice-author">
                            <img src="/build/assets/img/inicio/icono-usuario.png" alt="" class="author-avatar" loading="lazy">
                            <div class="author-info">
                                <strong>Andrés L.</strong>
                                <span>Papá · Secundaria</span>
                            </div>
                        </div>
                    </div>
                    <div class="voice-card">
                        <p class="voice-quote">"Me pregunté mucho si era la escuela correcta porque no tienen examen de admisión. Luego entendí que eso era parte de lo que los hace distintos."</p>
                        <div class="voice-author">
                            <img src="/build/assets/img/inicio/icono-usuario.png" alt="" class="author-avatar" loading="lazy">
                            <div class="author-info">
                                <strong>Sofía B.</strong>
                                <span>Mamá · Preescolar</span>
                            </div>
                        </div>
                    </div>
                    <!-- Duplicados para loop infinito -->
                    <div class="voice-card">
                        <p class="voice-quote">"Fui a la junta de inicio de año pensando que duraría veinte minutos. Me quedé hora y media platicando con los maestros. Eso ya me dijo bastante."</p>
                        <div class="voice-author">
                            <img src="/build/assets/img/inicio/icono-usuario.png" alt="" class="author-avatar" loading="lazy">
                            <div class="author-info">
                                <strong>Claudia R.</strong>
                                <span>Mamá · Primaria</span>
                            </div>
                        </div>
                    </div>
                    <div class="voice-card">
                        <p class="voice-quote">"La primera semana mi hijo me decía que la escuela era rara porque los dejaban escoger qué trabajar. Ahora si le pregunto si quiere cambiarse me ve como si estuviera loco."</p>
                        <div class="voice-author">
                            <img src="/build/assets/img/inicio/icono-usuario.png" alt="" class="author-avatar" loading="lazy">
                            <div class="author-info">
                                <strong>Roberto C.</strong>
                                <span>Papá · Primaria</span>
                            </div>
                        </div>
                    </div>
                    <div class="voice-card">
                        <p class="voice-quote">"Llevábamos como seis meses buscando escuela. Vinimos aquí casi de casualidad. Cancelé las otras citas esa misma tarde."</p>
                        <div class="voice-author">
                            <img src="/build/assets/img/inicio/icono-usuario.png" alt="" class="author-avatar" loading="lazy">
                            <div class="author-info">
                                <strong>Familia Torres</strong>
                                <span>Dos hijos · Preescolar y Primaria</span>
                            </div>
                        </div>
                    </div>
                    <div class="voice-card">
                        <p class="voice-quote">"Mi hija tiene 14 y venía de una secundaria muy tradicional. El cambio fue difícil los primeros meses. Ahora dice que fue lo mejor que hicimos."</p>
                        <div class="voice-author">
                            <img src="/build/assets/img/inicio/icono-usuario.png" alt="" class="author-avatar" loading="lazy">
                            <div class="author-info">
                                <strong>Verónica M.</strong>
                                <span>Mamá · Preparatoria</span>
                            </div>
                        </div>
                    </div>
                    <div class="voice-card">
                        <p class="voice-quote">"Un día llegué a recogerlo y se estaban tardando porque estaban terminando un experimento que ellos mismos habían propuesto. No me importó esperar."</p>
                        <div class="voice-author">
                            <img src="/build/assets/img/inicio/icono-usuario.png" alt="" class="author-avatar" loading="lazy">
                            <div class="author-info">
                                <strong>Andrés L.</strong>
                                <span>Papá · Secundaria</span>
                            </div>
                        </div>
                    </div>
                    <div class="voice-card">
                        <p class="voice-quote">"Me pregunté mucho si era la escuela correcta porque no tienen examen de admisión. Luego entendí que eso era parte de lo que los hace distintos."</p>
                        <div class="voice-author">
                            <img src="/build/assets/img/inicio/icono-usuario.png" alt="" class="author-avatar" loading="lazy">
                            <div class="author-info">
                                <strong>Sofía B.</strong>
                                <span>Mamá · Preescolar</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>


        <!-- ================================================
             8. NOTICIAS (dinámicas desde la BD)
        ================================================ -->
        <?php if (!empty($noticia_destacada) || !empty($noticias_recientes)): ?>
        <section class="news-section">
            <div style="max-width:1200px; margin:0 auto;">

                <div class="content-section-header reveal">
                    <div class="content-section-label noticias-label">
                        <span class="label-dot"></span>
                        Noticias
                    </div>
                    <h2 class="content-section-title">Momentos Bilbao</h2>
                    <p class="content-section-sub">Lo último de nuestra comunidad escolar</p>
                    <div class="news-alex-wrap" aria-hidden="true">
                        <img src="/build/assets/img/conocenos/quienes-somos/alex-dice.png"
                             alt="" loading="lazy">
                    </div>
                </div>

                <div class="news-grid">
                    <?php if (!empty($noticia_destacada)): ?>
                    <a href="/noticias/<?= s($noticia_destacada->slug) ?>"
                       class="news-item featured reveal">
                        <?php if (!empty($noticia_destacada->portada)): ?>
                        <img src="<?= s($noticia_destacada->portada) ?>"
                             alt="<?= s($noticia_destacada->portada_alt ?? $noticia_destacada->titulo) ?>"
                             class="news-img" loading="eager">
                        <?php else: ?>
                        <img src="/build/assets/img/blog/blog-placeholder.png" alt="" class="news-img" loading="lazy">
                        <?php endif; ?>
                        <div class="news-overlay">
                            <?php if (!empty($noticia_destacada->categoria_nombre)): ?>
                            <span class="news-source-badge"<?= !empty($noticia_destacada->categoria_color) ? ' style="background:' . s($noticia_destacada->categoria_color) . ';"' : '' ?>><?= s($noticia_destacada->categoria_nombre) ?></span>
                            <?php endif; ?>
                            <h3 class="news-title-card"><?= s($noticia_destacada->titulo) ?></h3>
                            <span class="news-arrow">Leer noticia →</span>
                        </div>
                    </a>
                    <?php endif; ?>
                    <?php foreach (array_slice($noticias_recientes, 0, 2) as $i => $n): ?>
                    <a href="/noticias/<?= s($n->slug) ?>"
                       class="news-item reveal"
                       style="transition-delay:<?= ($i + 1) * 0.06 ?>s">
                        <?php if (!empty($n->portada)): ?>
                        <img src="<?= s($n->portada) ?>"
                             alt="<?= s($n->portada_alt ?? $n->titulo) ?>"
                             class="news-img" loading="lazy">
                        <?php else: ?>
                        <img src="/build/assets/img/blog/blog-placeholder.png" alt="" class="news-img" loading="lazy">
                        <?php endif; ?>
                        <div class="news-overlay">
                            <?php if (!empty($n->categoria_nombre)): ?>
                            <span class="news-source-badge"<?= !empty($n->categoria_color) ? ' style="background:' . s($n->categoria_color) . ';"' : '' ?>><?= s($n->categoria_nombre) ?></span>
                            <?php endif; ?>
                            <h3 class="news-title-card"><?= s($n->titulo) ?></h3>
                            <span class="news-arrow">Leer noticia →</span>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>

                <div class="articulos-cta reveal" style="margin-top:40px;">
                    <a href="/noticias" class="btn-primario">
                        Ver todas las noticias →
                    </a>
                </div>

            </div>
        </section>
        <?php endif; ?>


        <!-- ================================================
             9. ARTÍCULOS DEL BLOG (dinámicos desde la BD)
        ================================================ -->
        <?php if (!empty($articulos_recientes)): ?>
        <section class="articulos-section">
            <div class="articulos-inner">

                <div class="content-section-header reveal">
                    <div class="content-section-label articulos-label">
                        <span class="label-dot"></span>
                        Artículos
                    </div>
                    <h2 class="content-section-title">Perspectivas Bilbao</h2>
                    <p class="content-section-sub">Ideas, reflexiones y experiencias escritas desde adentro del colegio</p>
                    <!-- Alex al lado izquierdo del header -->
                    <div class="articulos-alex-wrap" aria-hidden="true">
                        <img src="/build/assets/img/modelo-educativo/aprendizaje-integral/alex-lee.png"
                             alt="" loading="lazy">
                    </div>
                </div>

                <div class="articulos-grid">
                    <?php foreach ($articulos_recientes as $i => $articulo): ?>
                    <a
                        href="/blog/<?= s($articulo->slug) ?>"
                        class="articulo-card<?= $i === 0 ? ' articulo-card--featured' : '' ?> reveal"
                        style="transition-delay:<?= $i * 0.1 ?>s"
                    >
                        <div class="articulo-img-wrap">
                            <img
                                src="<?= $articulo->imagen ? s($articulo->imagen) : '/build/assets/img/blog/blog-placeholder.png' ?>"
                                alt="<?= s($articulo->titulo) ?>"
                                class="articulo-img"
                                loading="lazy"
                                onerror="this.onerror=null;this.src='/build/assets/img/blog/blog-placeholder.png';"
                            >
                            <?php if ($articulo->categoria_nombre): ?>
                            <span
                                class="articulo-cat"
                                style="background:<?= s($articulo->categoria_color ?: '#4D8ABB') ?>"
                            >
                                <?= s($articulo->categoria_nombre) ?>
                            </span>
                            <?php endif; ?>
                        </div>
                        <div class="articulo-body">
                            <h3 class="articulo-title"><?= s($articulo->titulo) ?></h3>
                            <?php if ($articulo->extracto): ?>
                                <p class="articulo-excerpt"><?= s($articulo->extracto) ?></p>
                            <?php endif; ?>
                            <div class="articulo-meta">
                                <div class="articulo-author-info">
                                    <img
                                        class="articulo-author-avatar"
                                        src="<?= $articulo->autor_avatar ? s($articulo->autor_avatar) : '/build/assets/img/inicio/icono-usuario.png' ?>"
                                        alt=""
                                        loading="lazy"
                                        onerror="this.onerror=null;this.src='/build/assets/img/inicio/icono-usuario.png';"
                                    >
                                    <span class="articulo-author">
                                        <?= s($articulo->autor_nombre ?: 'Equipo Bilbao') ?>
                                    </span>
                                </div>
                                <?php if ($articulo->tiempo_lectura): ?>
                                    <span class="articulo-time">
                                        <?= (int) $articulo->tiempo_lectura ?> min
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="articulo-read-more">
                                Leer artículo
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>

                <div class="articulos-cta reveal">
                    <a href="/blog" class="btn-primario">Ver todos los artículos →</a>
                </div>

            </div>
        </section>
        <?php endif; ?>


        <!-- ================================================
             10. ALUMNI
        ================================================ -->
        <section class="alumni-section reveal">
            <div class="alumni-content">
                <div class="alumni-alex" aria-hidden="true">
                    <img src="/build/assets/img/niveles-academicos/preparatoria/alex-basket.png"
                         alt="" loading="lazy">
                </div>
                <h3 class="alumni-title">Alumni Bilbao</h3>
                <p class="alumni-text">
                    Vuelve a conectar — esta sigue siendo tu casa. Queremos reencontrarnos
                    y crear nuevos espacios de conexión para nuestra comunidad de exalumnos.
                </p>
                <a href="/comunidad/exalumnos" class="btn-secundario">Volver a conectar</a>
            </div>
        </section>


        <!-- ================================================
             12. CTA FINAL
        ================================================ -->
        <section class="final-cta">

            <!-- Alex derecha -->
            <div class="final-cta-alex" aria-hidden="true">
                <img src="/build/assets/img/admisiones/preguntas-frecuentes/alex-mano.png"
                     alt="" loading="lazy">
            </div>
            <!-- Alex izquierda -->
            <div class="final-cta-alex-left" aria-hidden="true">
                <img src="/build/assets/img/admisiones/proceso/fam-alex.png"
                     alt="" loading="lazy">
            </div>

            <span class="final-cta-tag">¿Listo para conocernos?</span>
            <h2 class="cta-big-h2">
                El futuro de tus hijos<br>comienza aquí.
            </h2>
            <a
                href="https://wa.me/525614612682?text=Hola,%20acabo%20de%20ver%20la%20página%20del%20colegio,%20me%20gustó%20y%20quiero%20conocerlos%20en%20una%20visita%20guiada."
                class="btn-primario"
                style="gap:8px; font-size:1.1rem; padding:18px 42px;"
                target="_blank" rel="noopener"
            >
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                    <path d="M12 0C5.373 0 0 5.373 0 12c0 2.096.537 4.068 1.482 5.792L0 24l6.375-1.456C8.067 23.48 10.004 24 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.818c-1.859 0-3.584-.509-5.058-1.393l-.362-.215-3.755.857.906-3.641-.236-.374C2.633 15.553 2.182 13.823 2.182 12 2.182 6.575 6.575 2.182 12 2.182S21.818 6.575 21.818 12 17.425 21.818 12 21.818z"/>
                </svg>
                Agenda una visita hoy
            </a>
        </section>

    </main>
