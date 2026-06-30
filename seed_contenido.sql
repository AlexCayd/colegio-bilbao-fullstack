-- =====================================================
--  COLEGIO BILBAO — Contenido de ejemplo
--  Requiere que database.sql ya haya sido ejecutado.
--
--  Inserta: 3 artículos + 3 noticias publicados
--  Categorías de artículos usadas: 1, 4, 6
--  Categorías de noticias usadas:  1, 2, 4
--  Autor: id 2 (Alexander Oliva, administrador)
-- =====================================================

SET NAMES utf8mb4;

-- ── Artículos ─────────────────────────────────────────────────────────────────

INSERT INTO articulos
    (titulo, slug, extracto, contenido, estado, envio_revision, fecha_publicacion, tiempo_lectura, vistas, likes, categoria_id, autor_id)
VALUES

-- Artículo 1 — Modelo Educativo
(
    'El modelo VIDA: cómo formamos personas, no solo estudiantes',
    'el-modelo-vida-como-formamos-personas-no-solo-estudiantes',
    'En el Colegio Bilbao creemos que la educación va más allá de los libros. El modelo VIDA integra valores, inteligencia, disciplina y amor para formar seres humanos completos.',
    '<p>En el Colegio Bilbao creemos que cada niño y joven llega a nuestras aulas con un potencial único. Por eso, desde hace más de dos décadas hemos desarrollado el <strong>modelo VIDA</strong>: un enfoque educativo que coloca a la persona en el centro del aprendizaje.</p>

<h2>¿Qué significa VIDA?</h2>
<p>Las siglas representan los cuatro pilares que guían nuestra práctica docente diaria:</p>
<ul>
  <li><strong>Valores:</strong> construimos carácter a través de decisiones cotidianas.</li>
  <li><strong>Inteligencia:</strong> desarrollamos el pensamiento crítico y la curiosidad intelectual.</li>
  <li><strong>Disciplina:</strong> fomentamos hábitos que acompañarán a nuestros alumnos toda la vida.</li>
  <li><strong>Amor:</strong> creamos vínculos genuinos entre maestros, alumnos y familias.</li>
</ul>

<h2>Un aprendizaje situado en la realidad</h2>
<p>El modelo VIDA no es un manual de instrucciones; es una actitud frente a la educación. Nuestros maestros diseñan experiencias de aprendizaje que conectan los contenidos académicos con situaciones reales: proyectos comunitarios, retos de resolución de problemas y actividades en la naturaleza.</p>

<p>Cuando un alumno de quinto año diseña una campaña de reciclaje para su colonia, no solo aprende ciencias naturales: aprende a liderar, a comunicarse y a comprometerse con su entorno. Eso es educación integral.</p>

<h2>Resultados que se ven</h2>
<p>Los egresados del Colegio Bilbao ingresan a preparatorias y universidades de alto nivel con una ventaja clara: saben trabajar en equipo, manejan la frustración con madurez y tienen una identidad personal sólida. Eso no se logra memorizando tablas de multiplicar; se logra viviendo el modelo VIDA cada día.</p>',
    'publicado', 0, '2026-05-10 08:00:00', 4, 128, 14, 1, 2
),

-- Artículo 2 — Aprendizaje Integral
(
    'Neurociencia y aprendizaje: lo que la ciencia dice sobre cómo estudiar mejor',
    'neurociencia-y-aprendizaje-lo-que-la-ciencia-dice-sobre-como-estudiar-mejor',
    'Dormir bien, recuperar información activamente y espaciar la práctica no son trucos: son estrategias respaldadas por la neurociencia que nuestros maestros integran al aula.',
    '<p>Durante años la cultura escolar premió a quien pasaba más horas frente a los libros. Hoy la neurociencia nos dice algo diferente: lo que importa no es cuánto estudias, sino <em>cómo</em> estudias.</p>

<h2>El mito del repaso pasivo</h2>
<p>Releer apuntes o subrayar textos produce una sensación de familiaridad que confundimos con aprendizaje real. Sin embargo, cuando llega el examen, esa información no está disponible. La razón es sencilla: el cerebro solo consolida lo que <strong>recupera activamente</strong>.</p>

<p>El método de <em>retrieval practice</em> —hacerse preguntas, cerrar el libro y tratar de recordar— ha demostrado en cientos de estudios mejorar la retención a largo plazo hasta un 50 % en comparación con el repaso pasivo.</p>

<h2>El sueño como herramienta de estudio</h2>
<p>Durante el sueño profundo, el hipocampo transfiere los recuerdos del día a la corteza cerebral, donde se almacenan de forma permanente. Un alumno que estudia hasta medianoche y duerme cinco horas retiene menos que uno que estudió dos horas menos pero durmió ocho. En el Colegio Bilbao lo tomamos en serio: las cargas académicas están diseñadas para que los alumnos puedan descansar.</p>

<h2>Práctica espaciada en el aula</h2>
<p>Nuestros maestros de secundaria y bachillerato aplican la <em>spaced practice</em>: en lugar de ver un tema una semana y olvidarlo, retoman conceptos clave cada 7 y 21 días mediante ejercicios cortos de recuperación. Los resultados en evaluaciones estandarizadas hablan por sí solos.</p>

<p>Compartimos estas estrategias con las familias porque el aprendizaje no termina al salir del salón. Cuando padres e hijos hablan sobre lo que se vio en clase —aunque sea cinco minutos en la cena— están activando exactamente los mismos mecanismos que la neurociencia recomienda.</p>',
    'publicado', 0, '2026-05-24 09:30:00', 5, 95, 11, 4, 2
),

-- Artículo 3 — Tecnología e Innovación
(
    'Inteligencia artificial en el aula: aliada, no sustituta',
    'inteligencia-artificial-en-el-aula-aliada-no-sustituta',
    'La IA ya está en manos de nuestros alumnos. La pregunta no es si usarla, sino cómo enseñarles a usarla con criterio, ética y pensamiento crítico.',
    '<p>En el ciclo escolar 2025-2026, el Colegio Bilbao integró de manera formal el uso de herramientas de inteligencia artificial en los programas de secundaria y bachillerato. No como reemplazo del pensamiento, sino como amplificador de él.</p>

<h2>El problema de prohibir sin enseñar</h2>
<p>Prohibir la IA en las aulas es tan efectivo como prohibir el uso de calculadoras en los años noventa: los alumnos la usan de todas formas, solo que sin orientación. El resultado es copiado sin comprensión, trabajos genéricos y ningún desarrollo de criterio propio.</p>

<p>Nuestra apuesta es distinta: <strong>enseñamos a usar la IA bien</strong>. Eso implica formular preguntas precisas, evaluar las respuestas críticamente, identificar alucinaciones y entender las limitaciones de estos sistemas.</p>

<h2>Proyectos concretos</h2>
<p>En la materia de Español de tercer año de secundaria, los alumnos usan asistentes de IA para generar un primer borrador de un ensayo. Luego, en clase, analizan qué le falta, qué está mal y cómo mejorar el texto. El resultado final debe ser sustancialmente diferente —y mejor— que el borrador generado.</p>

<p>En Física de bachillerato, utilizan herramientas de simulación impulsadas por IA para modelar fenómenos que serían imposibles de replicar en el laboratorio escolar. Esto abre conversaciones sobre modelado científico, simplificaciones y la diferencia entre un modelo y la realidad.</p>

<h2>La habilidad del siglo XXI</h2>
<p>El mercado laboral al que se integrarán nuestros egresados en 2030 no pedirá que eviten la IA; pedirá que la dominen. Formarlos hoy con esa competencia —junto con el pensamiento crítico para cuestionarla— es uno de los compromisos más importantes del Colegio Bilbao.</p>',
    'publicado', 0, '2026-06-05 10:00:00', 5, 210, 27, 6, 2
);

-- ── Tags para los artículos insertados ───────────────────────────────────────
-- Asume que los artículos recién insertados tienen los IDs más altos.
-- Ajusta los IDs si la tabla ya tenía registros previos.

SET @art1 = (SELECT id FROM articulos WHERE slug = 'el-modelo-vida-como-formamos-personas-no-solo-estudiantes');
SET @art2 = (SELECT id FROM articulos WHERE slug = 'neurociencia-y-aprendizaje-lo-que-la-ciencia-dice-sobre-como-estudiar-mejor');
SET @art3 = (SELECT id FROM articulos WHERE slug = 'inteligencia-artificial-en-el-aula-aliada-no-sustituta');

INSERT INTO articulo_tags (articulo_id, tag_id)
SELECT @art1, id FROM tags WHERE slug IN ('educacion-integral', 'liderazgo', 'emociones')
UNION ALL
SELECT @art2, id FROM tags WHERE slug IN ('aprendizaje', 'educacion-integral')
UNION ALL
SELECT @art3, id FROM tags WHERE slug IN ('tecnologia', 'innovacion', 'aprendizaje');

-- ── Noticias ──────────────────────────────────────────────────────────────────

INSERT INTO noticias
    (titulo, slug, extracto, contenido, estado, envio_revision, destacada, fecha_publicacion, tiempo_lectura, vistas, likes, categoria_id, autor_id)
VALUES

-- Noticia 1 — Institucional
(
    'Colegio Bilbao recibe la certificación SEP de Escuela de Excelencia 2026',
    'colegio-bilbao-recibe-certificacion-sep-escuela-de-excelencia-2026',
    'Por tercer año consecutivo, el Colegio Bilbao fue reconocido por la Secretaría de Educación Pública como Escuela de Excelencia, destacando su modelo pedagógico y sus resultados académicos.',
    '<p>El pasado 18 de junio, durante la ceremonia regional celebrada en el Centro Cultural del Estado, el <strong>Colegio Bilbao</strong> recibió por tercer año consecutivo la certificación <em>Escuela de Excelencia</em> otorgada por la Secretaría de Educación Pública (SEP).</p>

<p>El reconocimiento evalúa cinco dimensiones: resultados de aprendizaje, gestión escolar, formación docente, participación de las familias y mejora continua. En esta edición, el Colegio Bilbao obtuvo el puntaje más alto de la zona en los rubros de formación docente y participación familiar.</p>

<blockquote>"Este reconocimiento no es nuestro; es de cada maestro que llega temprano, de cada padre que se involucra y de cada alumno que da lo mejor de sí mismo." — Director General</blockquote>

<p>El galardón incluye recursos adicionales para la adquisición de materiales educativos y el financiamiento parcial de capacitaciones internacionales para el equipo docente durante el ciclo 2026-2027.</p>',
    'publicado', 0, 1, '2026-06-19 07:00:00', 3, 340, 45, 1, 2
),

-- Noticia 2 — Académico
(
    'Alumnos de bachillerato ganan primer lugar en la Olimpiada Estatal de Matemáticas',
    'alumnos-bachillerato-ganan-primer-lugar-olimpiada-estatal-matematicas',
    'Tres estudiantes de tercero de bachillerato representaron al Colegio Bilbao y se llevaron el primer lugar por equipos en la Olimpiada Estatal de Matemáticas 2026, compitiendo contra 47 escuelas.',
    '<p>La delegación del Colegio Bilbao regresó con el trofeo de primer lugar de la <strong>Olimpiada Estatal de Matemáticas 2026</strong>, celebrada el 14 de junio en las instalaciones de la Universidad Autónoma del Estado.</p>

<p>El equipo, formado por Valentina Herrera, Diego Morales y Sofía Castillo —los tres de tercer año de bachillerato—, superó a 47 escuelas públicas y privadas en una competencia que incluyó pruebas de álgebra, geometría analítica, cálculo diferencial y razonamiento combinatorio.</p>

<h2>Seis meses de preparación</h2>
<p>La maestra Lucía Vargas, coordinadora del club de matemáticas, acompañó al equipo durante seis meses de sesiones semanales antes de la competencia. "Lo que más me sorprende de estos tres alumnos no es que sepan mucho, sino que saben pensar cuando no saben", comentó al recibir el reconocimiento.</p>

<p>Diego Morales, además, obtuvo la medalla de plata en la categoría individual, lo que lo acredita para representar al estado en la fase nacional en septiembre.</p>

<p>El Colegio Bilbao felicita a los alumnos, a sus familias y a la maestra Vargas por este logro que refleja el compromiso del colegio con la excelencia académica.</p>',
    'publicado', 0, 1, '2026-06-16 08:30:00', 4, 275, 38, 2, 2
),

-- Noticia 3 — Deportivo
(
    'Equipo de fútbol sub-15 campeón del Torneo Intercolegial de Primavera',
    'equipo-futbol-sub-15-campeon-torneo-intercolegial-primavera',
    'Tras cinco partidos invictos, el equipo de fútbol varonil sub-15 del Colegio Bilbao se coronó campeón del Torneo Intercolegial de Primavera 2026 con marcador de 2-0 en la gran final.',
    '<p>Con un marcador de <strong>2 a 0</strong> ante el Colegio San Patricio, el equipo de fútbol varonil sub-15 del Colegio Bilbao se proclamó campeón del <strong>Torneo Intercolegial de Primavera 2026</strong> el pasado sábado 21 de junio.</p>

<p>El equipo disputó cinco partidos a lo largo del torneo sin recibir un solo gol en contra, consolidándose como la defensa más sólida de la competencia con 14 goles anotados y cero recibidos.</p>

<h2>La final</h2>
<p>Los goles del partido fueron obra de Emilio Sánchez —capitán del equipo— al minuto 23, y de Rodrigo Peña al minuto 61, ambas asistidas por Mateo Ríos. El guardameta Andrés Villanueva fue nombrado mejor portero del torneo tras cuatro actuaciones de infarto.</p>

<p>El entrenador Pablo Montoya destacó el trabajo colectivo: "Ganamos porque somos un equipo de verdad. Ninguno juega solo, ninguno gana solo. Eso es lo que intentamos enseñar desde el primer día."</p>

<p>El trofeo y las medallas fueron recibidos en una pequeña ceremonia en la cancha principal del colegio. La directiva agradece a los padres de familia que acompañaron al equipo en cada partido.</p>',
    'publicado', 0, 0, '2026-06-22 09:00:00', 3, 189, 31, 4, 2
);
