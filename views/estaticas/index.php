<div class="landing-root" id="landing-root">

    <!-- BOSQUE 3D FIJO -->
    <canvas id="forest-canvas" class="lnd-forest-canvas" aria-hidden="true"></canvas>
    <div id="forest-veil" class="lnd-forest-veil" aria-hidden="true"></div>

    <!-- ================================================
         1. HERO
    ================================================ -->
    <section class="lnd-hero" id="inicio">
        <div class="lnd-hero__content">
            <p class="lnd-hero__eyebrow">
                <span data-i18n="home.hero.eyebrow">Colegio Bilbao · Preescolar · Primaria · Secundaria · Preparatoria</span>
            </p>
            <h1 class="lnd-hero__title" id="hero-title">
                <span class="lnd-hero__title-inner">
                    <span id="hero-a" data-i18n="home.hero.titleA">La naturaleza</span><br>
                    <span id="hero-b" data-i18n="home.hero.titleB">es nuestro </span><span class="lnd-hero__hi" id="hero-hi" data-i18n="home.hero.titleHighlight">salón</span>
                </span>
            </h1>
            <p class="lnd-hero__sub" data-i18n="home.hero.subtitle">30,000 m² de bosque convertidos en aula. Un modelo donde explorar y preguntar pesa más que memorizar.</p>
            <div class="lnd-hero__ctas">
                <a href="https://wa.me/525614612682?text=Hola,%20acabo%20de%20ver%20la%20página%20del%20colegio,%20me%20gustó%20y%20quiero%20conocerlos%20en%20una%20visita%20guiada."
                   class="btn-primario lnd-btn-hero"
                   data-magnetic
                   target="_blank" rel="noopener">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                        <path d="M12 0C5.373 0 0 5.373 0 12c0 2.096.537 4.068 1.482 5.792L0 24l6.375-1.456C8.067 23.48 10.004 24 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.818c-1.859 0-3.584-.509-5.058-1.393l-.362-.215-3.755.857.906-3.641-.236-.374C2.633 15.553 2.182 13.823 2.182 12 2.182 6.575 6.575 2.182 12 2.182S21.818 6.575 21.818 12 17.425 21.818 12 21.818z"/>
                    </svg>
                    <span data-i18n="home.hero.ctaVisit">Agenda tu visita</span>
                </a>
                <a href="#nuestra-oferta" class="lnd-btn-ghost" data-i18n="home.hero.ctaExplore">Conocer el colegio ↓</a>
            </div>
        </div>
        <div class="lnd-hero__scroll" aria-hidden="true">
            <span class="lnd-hero__scroll-dot"></span>
        </div>
    </section>


    <!-- ================================================
         2. STATS
    ================================================ -->
    <section class="lnd-stats">
        <div class="lnd-stats__grid">
            <div class="lnd-stat" data-reveal>
                <strong class="lnd-stat__num" data-count="38" data-plus="true">0</strong>
                <p class="lnd-stat__label" data-i18n="home.stats.experience.label">Años de experiencia</p>
            </div>
            <div class="lnd-stat" data-reveal style="transition-delay:.1s">
                <strong class="lnd-stat__num" data-count="30000" data-suffix="m²">0</strong>
                <p class="lnd-stat__label" data-i18n="home.stats.forest.label">de bosque como aula</p>
            </div>
            <div class="lnd-stat" data-reveal style="transition-delay:.2s">
                <strong class="lnd-stat__num" data-count="3">0</strong>
                <p class="lnd-stat__label" data-i18n="home.stats.languages.label">Idiomas integrados</p>
            </div>
            <div class="lnd-stat" data-reveal style="transition-delay:.3s">
                <strong class="lnd-stat__num" data-count="200" data-plus="true">0</strong>
                <p class="lnd-stat__label" data-i18n="home.stats.universities.label">Universidades aliadas</p>
            </div>
        </div>
    </section>


    <!-- ================================================
         3. NIVELES ACADÉMICOS
    ================================================ -->
    <section class="lnd-niveles" id="nuestra-oferta">
        <div class="lnd-niveles__header" data-reveal>
            <span class="lnd-niveles__eyebrow" data-i18n="home.niveles.eyebrow">Un proyecto educativo, objetivos claros por nivel</span>
            <h2 class="lnd-niveles__title" data-i18n="home.niveles.title">Nuestra oferta educativa</h2>
        </div>
        <div class="lnd-niveles__grid">

            <a href="/niveles-academicos/preescolar" class="lnd-level-card">
                <div class="lnd-level-card__bg">
                    <?= picture('/build/assets/img/niveles-academicos/preescolar/nino-burbujas.jpg', 'Preescolar') ?>
                </div>
                <div class="lnd-level-card__overlay"></div>
                <img class="lnd-level-card__alex"
                     src="/build/assets/img/alex/bby-alex-feliz.png" alt="" loading="lazy">
                <div class="lnd-level-card__content">
                    <span class="lnd-level-card__num">01</span>
                    <h3 class="lnd-level-card__title" data-i18n="home.niveles.preescolar.title">Preescolar</h3>
                    <p class="lnd-level-card__desc" data-i18n="home.niveles.preescolar.desc">Juego, exploración y seguridad emocional para sus primeros pasos.</p>
                    <span class="lnd-level-card__link" data-i18n="home.niveles.viewLevel">Ver nivel →</span>
                </div>
            </a>

            <a href="/niveles-academicos/primaria" class="lnd-level-card">
                <div class="lnd-level-card__bg">
                    <?= picture('/build/assets/img/niveles-academicos/primaria/alumna-escribiendo.jpg', 'Primaria') ?>
                </div>
                <div class="lnd-level-card__overlay"></div>
                <img class="lnd-level-card__alex"
                     src="/build/assets/img/alex/kid-alex-explora.png" alt="" loading="lazy">
                <div class="lnd-level-card__content">
                    <span class="lnd-level-card__num">02</span>
                    <h3 class="lnd-level-card__title" data-i18n="home.niveles.primaria.title">Primaria</h3>
                    <p class="lnd-level-card__desc" data-i18n="home.niveles.primaria.desc">Aprendizaje activo, curiosidad y fundamentos sólidos.</p>
                    <span class="lnd-level-card__link" data-i18n="home.niveles.viewLevel">Ver nivel →</span>
                </div>
            </a>

            <a href="/niveles-academicos/secundaria" class="lnd-level-card">
                <div class="lnd-level-card__bg">
                    <?= picture('/build/assets/img/niveles-academicos/secundaria/alumnas-argumentando.jpg', 'Secundaria') ?>
                </div>
                <div class="lnd-level-card__overlay"></div>
                <img class="lnd-level-card__alex"
                     src="/build/assets/img/alex/teen-alex-camina.png" alt="" loading="lazy">
                <div class="lnd-level-card__content">
                    <span class="lnd-level-card__num">03</span>
                    <h3 class="lnd-level-card__title" data-i18n="home.niveles.secundaria.title">Secundaria</h3>
                    <p class="lnd-level-card__desc" data-i18n="home.niveles.secundaria.desc">Pensamiento crítico, diálogo y acompañamiento en la adolescencia.</p>
                    <span class="lnd-level-card__link" data-i18n="home.niveles.viewLevel">Ver nivel →</span>
                </div>
            </a>

            <a href="/niveles-academicos/preparatoria" class="lnd-level-card">
                <div class="lnd-level-card__bg">
                    <?= picture('/build/assets/img/niveles-academicos/preparatoria/alumnos-felices.jpg', 'Preparatoria') ?>
                </div>
                <div class="lnd-level-card__overlay"></div>
                <img class="lnd-level-card__alex"
                     src="/build/assets/img/alex/alex-medita.png" alt="" loading="lazy">
                <div class="lnd-level-card__content">
                    <span class="lnd-level-card__num">04</span>
                    <h3 class="lnd-level-card__title" data-i18n="home.niveles.preparatoria.title">Preparatoria</h3>
                    <p class="lnd-level-card__desc" data-i18n="home.niveles.preparatoria.desc">Diseño de futuro, certificación dual y proyección universitaria.</p>
                    <span class="lnd-level-card__link" data-i18n="home.niveles.viewLevel">Ver nivel →</span>
                </div>
            </a>

        </div>
    </section>


    <!-- ================================================
         4. MODELO EDUCATIVO VIDA
    ================================================ -->
    <section class="lnd-vida" data-reveal>
        <img class="lnd-vida__alex"
             src="/build/assets/img/alex/alex-pinta.png" alt="" loading="lazy" aria-hidden="true">
        <span class="lnd-vida__eyebrow" data-i18n="home.vida.eyebrow">La guía de lo que se vive en aulas y comunidad</span>
        <h2 class="lnd-vida__title" data-i18n="home.vida.title">Modelo Educativo VIDA</h2>
        <p class="lnd-vida__desc" data-i18n="home.vida.subtitle">Cuatro verbos que ordenan cada proyecto, cada clase y cada relación dentro del bosque.</p>
        <div class="lnd-vida__grid">
            <div class="lnd-vida-card">
                <span class="lnd-vida-card__letter">V</span>
                <h3 class="lnd-vida-card__title" data-i18n="home.vida.vincula.title">Vincula</h3>
                <p class="lnd-vida-card__desc" data-i18n="home.vida.vincula.desc">Comunidad y lazos fuertes entre personas.</p>
            </div>
            <div class="lnd-vida-card">
                <span class="lnd-vida-card__letter">I</span>
                <h3 class="lnd-vida-card__title" data-i18n="home.vida.indaga.title">Indaga</h3>
                <p class="lnd-vida-card__desc" data-i18n="home.vida.indaga.desc">Preguntas que despiertan el pensamiento.</p>
            </div>
            <div class="lnd-vida-card">
                <span class="lnd-vida-card__letter">D</span>
                <h3 class="lnd-vida-card__title" data-i18n="home.vida.descubre.title">Descubre</h3>
                <p class="lnd-vida-card__desc" data-i18n="home.vida.descubre.desc">Aprender haciendo, con manos y mente.</p>
            </div>
            <div class="lnd-vida-card">
                <span class="lnd-vida-card__letter">A</span>
                <h3 class="lnd-vida-card__title" data-i18n="home.vida.aporta.title">Aporta</h3>
                <p class="lnd-vida-card__desc" data-i18n="home.vida.aporta.desc">Acción con sentido para el mundo.</p>
            </div>
        </div>
        <div class="lnd-vida__cta">
            <a href="/modelo-educativo/modelo-vida" class="btn-secundario" data-i18n="home.vida.cta">Conocer el Modelo VIDA</a>
        </div>
    </section>


    <!-- ================================================
         5. DESCUBRE EL COLEGIO
    ================================================ -->
    <section class="lnd-descubre">
        <div class="lnd-descubre__header" data-reveal>
            <span class="lnd-descubre__eyebrow" data-i18n="home.descubre.eyebrow">Explora a tu ritmo</span>
            <h2 class="lnd-descubre__title" data-i18n="home.descubre.title">Descubre el Colegio Bilbao</h2>
        </div>
        <div class="lnd-descubre__grid">

            <a href="/admisiones" class="lnd-ex-card">
                <div class="lnd-ex-card__clip">
                    <?= picture('/build/assets/img/admisiones/inicio/llegando-al-bilbao.jpg', 'Admisiones', 'lnd-ex-card__photo') ?>
                    <div class="lnd-ex-card__overlay"></div>
                    <div class="lnd-ex-card__tint"></div>
                </div>
                <img class="lnd-ex-card__alex"
                     src="/build/assets/img/alex/alex-point.png" alt="" loading="lazy">
                <div class="lnd-ex-card__body">
                    <span class="lnd-ex-card__num">01</span>
                    <div class="lnd-ex-card__bar"></div>
                    <h3 class="lnd-ex-card__title" data-i18n="home.descubre.admisiones.title">Admisiones</h3>
                    <div class="lnd-ex-card__desc" data-i18n="home.descubre.admisiones.desc">Sin examen de admisión. Visita guiada y acompañamiento desde el primer contacto.</div>
                    <span class="lnd-ex-card__go"><span aria-hidden="true">→</span></span>
                </div>
            </a>

            <a href="/conocenos/equipo-educativo" class="lnd-ex-card">
                <div class="lnd-ex-card__clip">
                    <?= picture('/build/assets/img/conocenos/equipo-educativo/maestro-escucha.jpg', 'Equipo educativo', 'lnd-ex-card__photo') ?>
                    <div class="lnd-ex-card__overlay"></div>
                    <div class="lnd-ex-card__tint"></div>
                </div>
                <img class="lnd-ex-card__alex"
                     src="/build/assets/img/alex/teen-alex-escucha.png" alt="" loading="lazy">
                <div class="lnd-ex-card__body">
                    <span class="lnd-ex-card__num">02</span>
                    <div class="lnd-ex-card__bar"></div>
                    <h3 class="lnd-ex-card__title" data-i18n="home.descubre.equipoEducativo.title">Equipo educativo</h3>
                    <div class="lnd-ex-card__desc" data-i18n="home.descubre.equipoEducativo.desc">Maestros que escuchan, preguntan y construyen junto con sus alumnos.</div>
                    <span class="lnd-ex-card__go"><span aria-hidden="true">→</span></span>
                </div>
            </a>

            <a href="/modelo-educativo/idiomas" class="lnd-ex-card">
                <div class="lnd-ex-card__clip">
                    <?= picture('/build/assets/img/modelo-educativo/idiomas/reading.jpg', 'Idiomas', 'lnd-ex-card__photo') ?>
                    <div class="lnd-ex-card__overlay"></div>
                    <div class="lnd-ex-card__tint"></div>
                </div>
                <img class="lnd-ex-card__alex"
                     src="/build/assets/img/alex/alex-lee.png" alt="" loading="lazy">
                <div class="lnd-ex-card__body">
                    <span class="lnd-ex-card__num">03</span>
                    <div class="lnd-ex-card__bar"></div>
                    <h3 class="lnd-ex-card__title" data-i18n="home.descubre.idiomas.title">Idiomas</h3>
                    <div class="lnd-ex-card__desc" data-i18n="home.descubre.idiomas.desc">Español, inglés y alemán integrados al currículo desde preescolar.</div>
                    <span class="lnd-ex-card__go"><span aria-hidden="true">→</span></span>
                </div>
            </a>

            <a href="/modelo-educativo/aprendizaje-integral" class="lnd-ex-card">
                <div class="lnd-ex-card__clip">
                    <?= picture('/build/assets/img/modelo-educativo/aprendizaje-integral/ciencia.jpg', 'Aprendizaje Integral', 'lnd-ex-card__photo') ?>
                    <div class="lnd-ex-card__overlay"></div>
                    <div class="lnd-ex-card__tint"></div>
                </div>
                <img class="lnd-ex-card__alex"
                     src="/build/assets/img/alex/alex-ciencia.png" alt="" loading="lazy">
                <div class="lnd-ex-card__body">
                    <span class="lnd-ex-card__num">04</span>
                    <div class="lnd-ex-card__bar"></div>
                    <h3 class="lnd-ex-card__title" data-i18n="home.descubre.aprendizajeIntegral.title">Aprendizaje Integral</h3>
                    <div class="lnd-ex-card__desc" data-i18n="home.descubre.aprendizajeIntegral.desc">Arte, ciencia, tecnología, deporte y humanidades en un proyecto.</div>
                    <span class="lnd-ex-card__go"><span aria-hidden="true">→</span></span>
                </div>
            </a>

            <a href="/vida-escolar/futuro-universitario-becas" class="lnd-ex-card">
                <div class="lnd-ex-card__clip">
                    <?= picture('/build/assets/img/niveles-academicos/preparatoria/alumnos-anotando.jpg', 'Futuro universitario', 'lnd-ex-card__photo') ?>
                    <div class="lnd-ex-card__overlay"></div>
                    <div class="lnd-ex-card__tint"></div>
                </div>
                <img class="lnd-ex-card__alex"
                     src="/build/assets/img/alex/teen-alex-debate.png" alt="" loading="lazy">
                <div class="lnd-ex-card__body">
                    <span class="lnd-ex-card__num">05</span>
                    <div class="lnd-ex-card__bar"></div>
                    <h3 class="lnd-ex-card__title" data-i18n="home.descubre.futuroUniversitario.title">Futuro universitario</h3>
                    <div class="lnd-ex-card__desc" data-i18n="home.descubre.futuroUniversitario.desc">+200 universidades aliadas en México y el mundo para egresados.</div>
                    <span class="lnd-ex-card__go"><span aria-hidden="true">→</span></span>
                </div>
            </a>

            <a href="/conocenos/instalaciones" class="lnd-ex-card">
                <div class="lnd-ex-card__clip">
                    <?= picture('/build/assets/img/conocenos/instalaciones/cancha-futbol.jpg', 'Instalaciones', 'lnd-ex-card__photo') ?>
                    <div class="lnd-ex-card__overlay"></div>
                    <div class="lnd-ex-card__tint"></div>
                </div>
                <img class="lnd-ex-card__alex"
                     src="/build/assets/img/alex/alex-recicla.png" alt="" loading="lazy">
                <div class="lnd-ex-card__body">
                    <span class="lnd-ex-card__num">06</span>
                    <div class="lnd-ex-card__bar"></div>
                    <h3 class="lnd-ex-card__title" data-i18n="home.descubre.instalaciones.title">Instalaciones</h3>
                    <div class="lnd-ex-card__desc" data-i18n="home.descubre.instalaciones.desc">30,000 m² para aprender en movimiento y en contacto con la naturaleza.</div>
                    <span class="lnd-ex-card__go"><span aria-hidden="true">→</span></span>
                </div>
            </a>

        </div>
    </section>


    <!-- ================================================
         6. VOCES / TESTIMONIALES (desde BD)
    ================================================ -->
    <section class="lnd-voces">
        <div class="lnd-voces__header" data-reveal>
            <span class="lnd-voces__eyebrow" data-i18n="home.voces.eyebrow">Lo que dicen las familias del Colegio Bilbao</span>
            <h2 class="lnd-voces__title" data-i18n="home.voces.title">Familias Bilbao</h2>
            <span class="lnd-voces__stars" aria-label="5 estrellas de 5" data-i18n-attr="aria-label:home.voces.starsAriaLabel">★★★★★</span>
        </div>

        <div class="lnd-voces__marquee-wrap">
            <div class="lnd-voces__track">
                <?php
                // Combinar testimoniales de BD + fallbacks si la BD está vacía
                $voces = !empty($testimoniales) ? $testimoniales : [];
                $fallbacks = [
                    ['nombre' => 'Claudia R.',     'rol' => 'Mamá',    'comentario' => 'Fui a la junta de inicio de año pensando que duraría veinte minutos. Me quedé hora y media platicando con los maestros. Eso ya me dijo bastante.'],
                    ['nombre' => 'Roberto C.',     'rol' => 'Papá',    'comentario' => 'La primera semana mi hijo me decía que la escuela era rara porque los dejaban escoger qué trabajar. Ahora si le pregunto si quiere cambiarse me ve como si estuviera loco.'],
                    ['nombre' => 'Familia Torres', 'rol' => 'Familia', 'comentario' => 'Llevábamos como seis meses buscando escuela. Vinimos aquí casi de casualidad. Cancelé las otras citas esa misma tarde.'],
                    ['nombre' => 'Verónica M.',    'rol' => 'Mamá',    'comentario' => 'Mi hija tiene 14 y venía de una secundaria muy tradicional. El cambio fue difícil los primeros meses. Ahora dice que fue lo mejor que hicimos.'],
                    ['nombre' => 'Andrés L.',      'rol' => 'Papá',    'comentario' => 'Un día llegué a recogerlo y se estaban tardando porque estaban terminando un experimento que ellos mismos habían propuesto. No me importó esperar.'],
                    ['nombre' => 'Sofía B.',       'rol' => 'Mamá',    'comentario' => 'Me pregunté mucho si era la escuela correcta porque no tienen examen de admisión. Luego entendí que eso era parte de lo que los hace distintos.'],
                ];
                if (empty($voces)) {
                    foreach ($fallbacks as $f) {
                        $obj = new stdClass();
                        $obj->nombre     = $f['nombre'];
                        $obj->rol        = $f['rol'];
                        $obj->comentario = $f['comentario'];
                        $voces[] = $obj;
                    }
                }
                // Duplicar para bucle infinito
                $voces_loop = array_merge($voces, $voces);
                foreach ($voces_loop as $v):
                ?>
                <div class="lnd-voice-card">
                    <p class="lnd-voice-card__quote">"<?= htmlspecialchars($v->comentario) ?>"</p>
                    <div class="lnd-voice-card__author">
                        <span class="lnd-voice-card__avatar" aria-hidden="true"></span>
                        <div>
                            <strong class="lnd-voice-card__name"><?= htmlspecialchars($v->nombre) ?></strong>
                            <br>
                            <span class="lnd-voice-card__role"><?= htmlspecialchars($v->rol) ?></span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="lnd-voces__cta-wrap">
            <a href="/feedback-testimoniales" class="lnd-btn-ghost" style="margin-top:28px; display:inline-flex;" data-i18n="home.voces.cta">
                Deja tu testimonio →
            </a>
        </div>
    </section>


    <!-- ================================================
         7. NOTICIAS (horizontal scroll, fondo oscuro)
    ================================================ -->
    <?php if (!empty($noticia_destacada) || !empty($noticias_recientes)): ?>
    <section class="lnd-news" id="momentos">
        <!-- Ticker -->
        <div class="lnd-news__ticker-wrap">
            <div class="lnd-news__ticker">
                <?php
                $ticker_noticias = [];
                if (!empty($noticia_destacada)) $ticker_noticias[] = $noticia_destacada;
                foreach ($noticias_recientes as $n) $ticker_noticias[] = $n;
                $ticker_loop = array_merge($ticker_noticias, $ticker_noticias);
                foreach ($ticker_loop as $tn):
                ?>
                <a href="/noticias/<?= s($tn->slug) ?>" class="lnd-news__tick-item">
                    <span></span><?= htmlspecialchars($tn->titulo) ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Stage / viewport -->
        <div class="lnd-news__stage" id="news-stage">
            <div class="lnd-news__header">
                <div class="lnd-news__header-left">
                    <img class="lnd-news__alex"
                         src="/build/assets/img/alex/teen-alex-debate.png"
                         alt="Alex reportero" loading="lazy" data-i18n-attr="alt:home.news.alexAlt">
                    <div>
                        <span class="lnd-news__tag">
                            <span data-i18n="home.news.tag">Noticias</span>
                        </span>
                        <h2 class="lnd-news__title-h2" data-i18n="home.news.title">Momentos Bilbao</h2>
                    </div>
                </div>
                <span class="lnd-news__hint" data-i18n="home.news.hint">Desliza →</span>
            </div>

            <div class="lnd-news__viewport" id="news-viewport">
                <div class="lnd-news__track" id="news-track">

                    <!-- Noticia destacada -->
                    <?php if (!empty($noticia_destacada)): ?>
                    <a href="/noticias/<?= s($noticia_destacada->slug) ?>"
                       class="lnd-news-card lnd-news-card--featured"
                       draggable="false">
                        <div class="lnd-news-card__img-wrap">
                            <?php if (!empty($noticia_destacada->portada)): ?>
                            <img src="<?= s($noticia_destacada->portada) ?>"
                                 alt="<?= s($noticia_destacada->portada_alt ?? $noticia_destacada->titulo) ?>"
                                 draggable="false" loading="eager">
                            <?php else: ?>
                            <img src="/build/assets/img/blog/blog-placeholder.png" alt="" draggable="false" loading="lazy">
                            <?php endif; ?>
                            <div class="lnd-news-card__overlay"></div>
                            <span class="lnd-news-card__cat"<?= !empty($noticia_destacada->categoria_color) ? ' style="background:' . s($noticia_destacada->categoria_color) . '"' : '' ?>>
                                ★ <?= !empty($noticia_destacada->categoria_nombre) ? s($noticia_destacada->categoria_nombre) : 'Destacada' ?>
                            </span>
                        </div>
                        <div class="lnd-news-card__body">
                            <h3 class="lnd-news-card__title"><?= s($noticia_destacada->titulo) ?></h3>
                            <?php if (!empty($noticia_destacada->extracto)): ?>
                            <p class="lnd-news-card__excerpt"><?= s(mb_substr($noticia_destacada->extracto, 0, 120)) ?>…</p>
                            <?php endif; ?>
                            <span class="lnd-news-card__read" data-i18n="home.news.readMore">Leer noticia →</span>
                        </div>
                    </a>
                    <?php endif; ?>

                    <!-- Noticias recientes -->
                    <?php foreach ($noticias_recientes as $n): ?>
                    <a href="/noticias/<?= s($n->slug) ?>"
                       class="lnd-news-card"
                       draggable="false">
                        <div class="lnd-news-card__img-wrap">
                            <?php if (!empty($n->portada)): ?>
                            <img src="<?= s($n->portada) ?>"
                                 alt="<?= s($n->portada_alt ?? $n->titulo) ?>"
                                 draggable="false" loading="lazy">
                            <?php else: ?>
                            <img src="/build/assets/img/blog/blog-placeholder.png" alt="" draggable="false" loading="lazy">
                            <?php endif; ?>
                            <div class="lnd-news-card__overlay"></div>
                            <?php if (!empty($n->categoria_nombre)): ?>
                            <span class="lnd-news-card__cat"<?= !empty($n->categoria_color) ? ' style="background:' . s($n->categoria_color) . '"' : '' ?>>
                                <?= s($n->categoria_nombre) ?>
                            </span>
                            <?php endif; ?>
                        </div>
                        <div class="lnd-news-card__body">
                            <h3 class="lnd-news-card__title"><?= s($n->titulo) ?></h3>
                            <span class="lnd-news-card__read" data-i18n="home.news.readMore">Leer noticia →</span>
                        </div>
                    </a>
                    <?php endforeach; ?>

                </div>
            </div>
        </div>

        <div class="lnd-news__cta">
            <a href="/noticias" data-i18n="home.news.viewAll">Ver todas las noticias →</a>
        </div>
    </section>
    <?php endif; ?>


    <!-- ================================================
         8. ARTÍCULOS (dos columnas con preview)
    ================================================ -->
    <?php if (!empty($articulos_recientes)): ?>
    <section class="lnd-articulos">
        <div class="lnd-articulos__inner">

            <div class="lnd-articulos__header" data-reveal>
                <div>
                    <span class="lnd-articulos__tag">
                        <span data-i18n="home.articulos.tag">Artículos · Lectura</span>
                    </span>
                    <h2 class="lnd-articulos__title" data-i18n="home.articulos.title">Perspectivas Bilbao</h2>
                </div>
                <p class="lnd-articulos__intro" data-i18n="home.articulos.intro">Ideas y reflexiones escritas desde adentro del colegio. Para leer con calma.</p>
            </div>

            <div class="lnd-articulos__grid">

                <!-- Preview sticky -->
                <div class="lnd-art-preview" id="art-preview">
                    <?php foreach ($articulos_recientes as $i => $art): ?>
                    <div class="lnd-art-preview__panel" data-art-panel
                         style="display:<?= $i === 0 ? 'flex' : 'none' ?>">
                        <div class="lnd-art-preview__img-wrap">
                            <img src="<?= $art->imagen ? s($art->imagen) : '/build/assets/img/blog/blog-placeholder.png' ?>"
                                 alt="<?= s($art->titulo) ?>"
                                 onerror="this.src='/build/assets/img/blog/blog-placeholder.png'">
                            <?php if ($art->categoria_nombre): ?>
                            <span class="lnd-art-preview__cat"
                                  style="background:<?= s($art->categoria_color ?: 'var(--bilbao)') ?>">
                                <?= s($art->categoria_nombre) ?>
                            </span>
                            <?php endif; ?>
                            <img class="lnd-art-preview__alex"
                                 src="/build/assets/img/alex/alex-lee.png" alt="" loading="lazy" aria-hidden="true">
                        </div>
                        <div class="lnd-art-preview__body">
                            <h3 class="lnd-art-preview__title"><?= s($art->titulo) ?></h3>
                            <?php if ($art->extracto): ?>
                            <p class="lnd-art-preview__excerpt"><?= s($art->extracto) ?></p>
                            <?php endif; ?>
                            <div class="lnd-art-preview__foot">
                                <span class="lnd-art-preview__author">
                                    <span class="lnd-art-preview__author-dot"></span>
                                    <?= s($art->autor_nombre ?: 'Equipo Bilbao') ?>
                                </span>
                                <?php if ($art->tiempo_lectura): ?>
                                <span class="lnd-art-preview__time"><?= (int)$art->tiempo_lectura ?> min</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Lista -->
                <div class="lnd-art-list">
                    <?php foreach ($articulos_recientes as $i => $art): ?>
                    <a href="/blog/<?= s($art->slug) ?>"
                       class="lnd-art-row"
                       data-art-row="<?= $i ?>">
                        <span class="lnd-art-row__num">0<?= $i + 1 ?></span>
                        <div>
                            <?php if ($art->categoria_nombre): ?>
                            <span class="lnd-art-row__cat"><?= s($art->categoria_nombre) ?></span>
                            <?php endif; ?>
                            <h4 class="lnd-art-row__title"><?= s($art->titulo) ?></h4>
                            <span class="lnd-art-row__meta">
                                <?= $art->tiempo_lectura ? (int)$art->tiempo_lectura . ' min · ' : '' ?><?= s($art->autor_nombre ?: 'Equipo Bilbao') ?>
                            </span>
                        </div>
                        <span class="lnd-art-row__arrow">→</span>
                    </a>
                    <?php endforeach; ?>
                </div>

            </div>

            <div class="lnd-articulos__cta">
                <a href="/blog" class="btn-primario" data-i18n="home.articulos.viewAll">Ver todos los artículos →</a>
            </div>
        </div>
    </section>
    <?php endif; ?>


    <!-- ================================================
         9. VISITA / MAPA
    ================================================ -->
    <section class="lnd-visita" id="visita">
        <div class="lnd-visita__grid" data-reveal>
            <div>
                <span class="lnd-visita__eyebrow" data-i18n="home.visita.eyebrow">Ven a conocernos</span>
                <h2 class="lnd-visita__title" data-i18n="home.visita.title">Visita el bosque</h2>
                <p class="lnd-visita__desc" data-i18n="home.visita.subtitle">Estamos en la zona poniente de la Ciudad de México, a minutos de Santa Fe. Agenda tu visita y recorre los 30,000 m² en persona.</p>
                <div class="lnd-visita__info">
                    <div class="lnd-visita__info-row">
                        <span class="lnd-visita__info-icon" aria-hidden="true">
                            <svg width="19" height="19" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5A2.5 2.5 0 1 1 12 6.5a2.5 2.5 0 0 1 0 5z"/></svg>
                        </span>
                        <div>
                            <p class="lnd-visita__info-label" data-i18n="home.visita.addressLabel">Dirección</p>
                            <p class="lnd-visita__info-val">Tlalmimilolpan 39, San Mateo Tlaltenango,<br>Cuajimalpa de Morelos, 05600 CDMX</p>
                        </div>
                    </div>
                    <div class="lnd-visita__info-row">
                        <span class="lnd-visita__info-icon" aria-hidden="true">
                            <svg width="19" height="19" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zm1 10.59 4 2.31-.75 1.3L11 13V7h2z"/></svg>
                        </span>
                        <div>
                            <p class="lnd-visita__info-label" data-i18n="home.visita.hoursLabel">Horario de visitas</p>
                            <p class="lnd-visita__info-val" data-i18n="home.visita.hoursValue">Lunes a viernes · 8:00 – 15:00 h</p>
                        </div>
                    </div>
                </div>
                <div class="lnd-visita__ctas">
                    <a href="https://www.google.com/maps/dir/?api=1&destination=Colegio%20Bilbao%2C%20Tlalmimilolpan%2039%2C%20San%20Mateo%20Tlaltenango%2C%20Cuajimalpa%20de%20Morelos%2C%2005600%20CDMX"
                       target="_blank" rel="noopener"
                       class="btn-primario"
                       data-magnetic>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5A2.5 2.5 0 1 1 12 6.5a2.5 2.5 0 0 1 0 5z"/></svg>
                        <span data-i18n="home.visita.directionsCta">Cómo llegar</span>
                    </a>
                    <a href="https://wa.me/525614612682?text=Hola,%20quiero%20agendar%20una%20visita%20guiada%20al%20Colegio%20Bilbao."
                       class="lnd-btn-ghost"
                       target="_blank" rel="noopener"
                       data-i18n="home.visita.scheduleCta">
                        Agendar visita
                    </a>
                </div>
            </div>

            <div>
                <a href="https://www.google.com/maps/search/Colegio+Bilbao+Tlalmimilolpan"
                   target="_blank" rel="noopener" class="lnd-visita__map-link"
                   aria-label="Ver Colegio Bilbao en Google Maps"
                   data-i18n-attr="aria-label:home.visita.mapAriaLabel">
                <div class="lnd-visita__map-wrap">
                    <svg viewBox="0 0 800 560" width="100%" style="display:block;" xmlns="http://www.w3.org/2000/svg">
                        <rect width="800" height="560" fill="#DCEBF7"/>
                        <path d="M40,50 Q160,20 260,80 Q270,170 170,190 Q60,180 40,110 Z" fill="#BBD8EE"/>
                        <path d="M610,30 Q730,55 775,150 Q765,220 660,205 Q585,150 610,30 Z" fill="#BBD8EE"/>
                        <path d="M120,365 Q215,355 255,415 Q245,478 160,478 Q85,448 120,365 Z" fill="#BBD8EE"/>
                        <path d="M470,250 Q575,250 610,305 Q598,372 520,366 Q448,336 470,250 Z" fill="#9CC8E6"/>
                        <path d="M-20,300 C160,360 300,300 420,382 C540,462 660,432 820,505" fill="none" stroke="#5FB3DC" stroke-width="15" stroke-linecap="round"/>
                        <g stroke="#C2D6EA" stroke-width="20" fill="none" stroke-linecap="round">
                            <path d="M-20,140 C140,150 220,90 320,120 C470,165 515,250 540,305"/>
                            <path d="M300,-20 C322,120 300,260 360,420 C392,505 380,560 380,560"/>
                            <path d="M60,-20 C92,150 160,300 250,560"/>
                            <path d="M540,305 C645,305 720,262 820,250"/>
                            <path d="M-20,470 C200,470 520,470 820,470"/>
                        </g>
                        <g stroke="#EDF3F9" stroke-width="5" fill="none" stroke-linecap="round" stroke-dasharray="2 14">
                            <path d="M-20,140 C140,150 220,90 320,120 C470,165 515,250 540,305"/>
                            <path d="M540,305 C645,305 720,262 820,250"/>
                            <path d="M60,-20 C92,150 160,300 250,560"/>
                        </g>
                        <g fill="#C9DCEE">
                            <rect x="360" y="150" width="36" height="28" rx="5"/><rect x="408" y="160" width="30" height="24" rx="5"/>
                            <rect x="150" y="120" width="34" height="26" rx="5"/><rect x="200" y="150" width="28" height="22" rx="5"/>
                            <rect x="430" y="330" width="40" height="30" rx="5"/><rect x="300" y="360" width="30" height="26" rx="5"/>
                            <rect x="640" y="330" width="34" height="26" rx="5"/><rect x="690" y="360" width="30" height="24" rx="5"/>
                            <rect x="120" y="240" width="30" height="24" rx="5"/><rect x="80" y="300" width="28" height="22" rx="5"/>
                            <rect x="560" y="160" width="32" height="24" rx="5"/><rect x="610" y="120" width="28" height="22" rx="5"/>
                        </g>
                        <text x="252" y="108" fill="#6B7C8E" font-size="14" font-family="Outfit,sans-serif" font-weight="700" transform="rotate(-13 252 108)">Tlalmimilolpan</text>
                        <text x="120" y="300" fill="#6B7C8E" font-size="13" font-family="Outfit,sans-serif" font-weight="600" transform="rotate(70 120 300)">Muitles</text>
                        <text x="44" y="210" fill="#6B7C8E" font-size="13" font-family="Outfit,sans-serif" font-weight="600" transform="rotate(64 44 210)">Cam. San Mateo</text>
                        <text x="470" y="432" fill="#5FB3DC" font-size="13" font-family="Outfit,sans-serif" font-weight="700" transform="rotate(22 470 432)">Río Azoyapan</text>
                    </svg>
                    <div class="lnd-visita__map-pin">
                        <span class="lnd-visita__pin-dot"></span>
                        <span class="lnd-visita__pin-label">Colegio Bilbao</span>
                    </div>
                    <img class="lnd-visita__map-alex"
                         src="/build/assets/img/alex/alex-point.png"
                         alt="Alex señalando el mapa" loading="lazy" data-i18n-attr="alt:home.visita.mapAlexAlt">
                </div>
                </a>
            </div>
        </div>
    </section>


    <!-- ================================================
         10. CTA FINAL
    ================================================ -->
    <section class="lnd-cta">
        <img class="lnd-cta__alex-left"
             src="/build/assets/img/alex/kid-alex-salta.png"
             alt="" loading="lazy" data-parallax="0.18" aria-hidden="true">
        <img class="lnd-cta__alex-right"
             src="/build/assets/img/alex/alex-volley.png"
             alt="" loading="lazy" data-parallax="0.26" aria-hidden="true">

        <div class="lnd-cta__content" data-reveal>
            <span class="lnd-cta__eyebrow" data-i18n="home.ctaFinal.eyebrow">¿Listo para conocernos?</span>
            <h2 class="lnd-cta__title">
                <span data-i18n="home.ctaFinal.titlePre">El futuro de tus hijos</span><br><span data-i18n="home.ctaFinal.titleMid">comienza </span><span data-i18n="home.ctaFinal.titleHighlight">aquí</span><span data-i18n="home.ctaFinal.titlePost">.</span>
            </h2>
            <a href="https://wa.me/525614612682?text=Hola,%20acabo%20de%20ver%20la%20página%20del%20colegio,%20me%20gustó%20y%20quiero%20conocerlos%20en%20una%20visita%20guiada."
               class="btn-primario"
               style="font-size:1.1rem; padding:20px 44px; border-radius:18px; box-shadow:0 18px 50px var(--halo); gap:11px;"
               data-magnetic
               target="_blank" rel="noopener">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                    <path d="M12 0C5.373 0 0 5.373 0 12c0 2.096.537 4.068 1.482 5.792L0 24l6.375-1.456C8.067 23.48 10.004 24 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.818c-1.859 0-3.584-.509-5.058-1.393l-.362-.215-3.755.857.906-3.641-.236-.374C2.633 15.553 2.182 13.823 2.182 12 2.182 6.575 6.575 2.182 12 2.182S21.818 6.575 21.818 12 17.425 21.818 12 21.818z"/>
                </svg>
                <span data-i18n="home.ctaFinal.cta">Agenda una visita hoy</span>
            </a>
        </div>
    </section>


    <!-- ================================================
         STICKY CTA (solo móvil) — se muestra al hacer scroll
    ================================================ -->
    <div class="lnd-sticky-cta" id="lnd-sticky-cta">
        <a href="https://wa.me/525614612682?text=Hola,%20quiero%20agendar%20una%20visita%20guiada%20al%20Colegio%20Bilbao."
           class="lnd-sticky-cta__btn"
           target="_blank" rel="noopener">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                <path d="M12 0C5.373 0 0 5.373 0 12c0 2.096.537 4.068 1.482 5.792L0 24l6.375-1.456C8.067 23.48 10.004 24 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.818c-1.859 0-3.584-.509-5.058-1.393l-.362-.215-3.755.857.906-3.641-.236-.374C2.633 15.553 2.182 13.823 2.182 12 2.182 6.575 6.575 2.182 12 2.182S21.818 6.575 21.818 12 17.425 21.818 12 21.818z"/>
            </svg>
            <span data-i18n="home.stickyCta.cta">Agenda una visita</span>
        </a>
    </div>

</div><!-- /.landing-root -->
