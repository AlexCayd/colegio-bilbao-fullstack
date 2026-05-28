-- =====================================================
--  BLOG COLEGIO BILBAO — Seed completo
--  Admin: admin@bilbao.edu.mx / Tlalmimilolpan39%
--  Admin: alexander.oliva@bilbao.edu.mx / ColegioBilbao13
--
--  Paleta de colores para categorías (escala cromática):
--  #fc6722  #f5b400  #8ac926  #34a853  #46bdc6
--  #4285f4  #4267ac  #aa2296  #ea075a  #e51022
-- =====================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS articulo_tags;
DROP TABLE IF EXISTS articulos;
DROP TABLE IF EXISTS tags;
DROP TABLE IF EXISTS categorias;
DROP TABLE IF EXISTS noticias;
DROP TABLE IF EXISTS categorias_noticias;
DROP TABLE IF EXISTS usuarios;

SET FOREIGN_KEY_CHECKS = 1;

-- ── Tablas ────────────────────────────────────────────────────────────────────

CREATE TABLE usuarios (
    id            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    nombre        VARCHAR(120)  NOT NULL,
    email         VARCHAR(180)  NOT NULL,
    password      VARCHAR(255)  NOT NULL,
    rol           ENUM('administrador','editor') NOT NULL DEFAULT 'editor',
    avatar        VARCHAR(255)  NULL,
    ultimo_acceso DATETIME      NULL,
    creado_en     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE categorias (
    id          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    nombre      VARCHAR(60)   NOT NULL,
    slug        VARCHAR(80)   NOT NULL,
    descripcion VARCHAR(240)  NULL,
    color       CHAR(7)       NOT NULL DEFAULT '#4267ac',
    creado_en   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE tags (
    id     INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(60)  NOT NULL,
    slug   VARCHAR(80)  NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE articulos (
    id                INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    titulo            VARCHAR(255)  NOT NULL,
    slug              VARCHAR(280)  NOT NULL,
    extracto          VARCHAR(300)  NULL,
    contenido         LONGTEXT      NULL,
    imagen            VARCHAR(255)  NULL,
    estado            ENUM('borrador','publicado','programado') NOT NULL DEFAULT 'borrador',
    fecha_publicacion DATETIME      NULL,
    tiempo_lectura    TINYINT UNSIGNED NULL,
    categoria_id      INT UNSIGNED  NULL,
    autor_id          INT UNSIGNED  NULL,
    creado_en         TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_slug (slug),
    CONSTRAINT fk_articulo_categoria FOREIGN KEY (categoria_id) REFERENCES categorias (id) ON DELETE SET NULL,
    CONSTRAINT fk_articulo_autor     FOREIGN KEY (autor_id)     REFERENCES usuarios   (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE articulo_tags (
    articulo_id INT UNSIGNED NOT NULL,
    tag_id      INT UNSIGNED NOT NULL,
    PRIMARY KEY (articulo_id, tag_id),
    CONSTRAINT fk_at_articulo FOREIGN KEY (articulo_id) REFERENCES articulos (id) ON DELETE CASCADE,
    CONSTRAINT fk_at_tag      FOREIGN KEY (tag_id)      REFERENCES tags       (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE categorias_noticias (
    id          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    nombre      VARCHAR(60)   NOT NULL,
    slug        VARCHAR(80)   NOT NULL,
    color       CHAR(7)       NOT NULL DEFAULT '#4267ac',
    descripcion VARCHAR(240)  NULL,
    creado_en   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE noticias (
    id                INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    titulo            VARCHAR(255)  NOT NULL,
    slug              VARCHAR(280)  NOT NULL,
    extracto          VARCHAR(400)  NULL,
    contenido         LONGTEXT      NULL,
    portada           VARCHAR(255)  NULL,
    portada_alt       VARCHAR(255)  NULL,
    estado            ENUM('borrador','publicado','programado') NOT NULL DEFAULT 'borrador',
    destacada         TINYINT(1)    NOT NULL DEFAULT 0,
    fecha_publicacion DATETIME      NULL,
    tiempo_lectura    TINYINT UNSIGNED NULL,
    categoria_id      INT UNSIGNED  NULL,
    autor_id          INT UNSIGNED  NULL,
    creado_en         TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_slug (slug),
    CONSTRAINT fk_noticia_categoria FOREIGN KEY (categoria_id) REFERENCES categorias_noticias (id) ON DELETE SET NULL,
    CONSTRAINT fk_noticia_autor     FOREIGN KEY (autor_id)     REFERENCES usuarios             (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Usuarios ──────────────────────────────────────────────────────────────────

INSERT INTO usuarios (nombre, email, password, rol) VALUES
('Administrador Bilbao',  'admin@bilbao.edu.mx',             '$2y$12$nJQBtZftIX.10iSyqFSv6uKIw0BhTQsGeCOO1xSkL.Cu77TWZ1Kai', 'administrador'),
('Alexander Oliva',       'alexander.oliva@bilbao.edu.mx',   '$2y$12$0TtlP9O9HsElGRDauM2CaOLYYAo5Xquik7QqFO2HS6izU7NyVaOMq', 'administrador');

-- ── Categorías de artículos ───────────────────────────────────────────────────
-- Colores en escala cromática: naranja → amarillo → lima → verde → teal → azul → azul marino → morado → rosa → rojo

INSERT INTO categorias (nombre, slug, descripcion, color) VALUES
('Modelo Educativo',        'modelo-educativo',        'Filosofía, pedagogía y propuesta formativa del Colegio Bilbao.',            '#4267ac'),
('Vida Escolar',            'vida-escolar',            'Convivencia, rutinas y experiencias dentro del campus.',                    '#46bdc6'),
('Comunidad y Familias',    'comunidad-y-familias',    'Reflexiones para padres, familias y la comunidad educativa.',               '#f5b400'),
('Aprendizaje Integral',    'aprendizaje-integral',    'Estrategias, hábitos y ciencia detrás del aprendizaje efectivo.',           '#8ac926'),
('Arte y Cultura',          'arte-y-cultura',          'Expresión artística, cultura y creatividad en el entorno escolar.',         '#aa2296'),
('Tecnología e Innovación', 'tecnologia-e-innovacion', 'Educación digital, IA y herramientas tecnológicas en el aula.',             '#4285f4'),
('Deportes',                'deportes',                'Formación física, valores deportivos y logros en competencias.',            '#34a853'),
('Internacional',           'internacional',           'Intercambios, certificaciones globales y perspectiva mundial.',             '#e51022');

-- ── Tags ─────────────────────────────────────────────────────────────────────

INSERT INTO tags (nombre, slug) VALUES
('educación integral',      'educacion-integral'),
('aprendizaje',             'aprendizaje'),
('familia',                 'familia'),
('tecnología',              'tecnologia'),
('deportes',                'deportes'),
('arte',                    'arte'),
('bilingüismo',             'bilinguismo'),
('emociones',               'emociones'),
('liderazgo',               'liderazgo'),
('innovación',              'innovacion');

-- ── Artículos (12) ────────────────────────────────────────────────────────────

INSERT INTO articulos (titulo, slug, extracto, contenido, estado, fecha_publicacion, tiempo_lectura, categoria_id, autor_id) VALUES

(
  'Educación contemporánea: criterio y carácter',
  'educacion-contemporanea-criterio-y-caracter',
  'Una declaración inaugural sobre la escuela que este tiempo exige.',
  '<p>La escuela no puede limitarse a transmitir contenidos. Debe formar personas capaces de pensar con profundidad, actuar con integridad y relacionarse con empatía.</p><p>En el Colegio Bilbao creemos que el criterio —la capacidad de distinguir lo verdadero de lo falso, lo valioso de lo superficial— es la competencia más importante que podemos cultivar en un estudiante. El carácter, por su parte, es la arquitectura interior que sostiene las decisiones cuando nadie está mirando.</p><h2>Una escuela con propósito</h2><p>No educamos para el examen. Educamos para la vida. Esa distinción cambia todo: el tipo de preguntas que hacemos en clase, la manera en que gestionamos el error, la forma en que celebramos el progreso.</p>',
  'publicado', '2026-05-01 09:00:00', 4, 1, 2
),

(
  'Aprendizaje basado en proyectos: el método que transforma las aulas',
  'aprendizaje-basado-en-proyectos',
  'Cuando los alumnos aprenden haciendo, la motivación se multiplica y el conocimiento se ancla con fuerza.',
  '<p>El aprendizaje basado en proyectos —conocido internacionalmente como <em>Project-Based Learning</em> o PBL— no es una moda pedagógica. Es una respuesta concreta a una pregunta vigente: ¿cómo hacemos que lo que se aprende en el aula tenga sentido fuera de ella?</p><p>En lugar de exponer contenidos y esperar que los alumnos los memoricen, el PBL parte de un problema real, una pregunta auténtica o un desafío concreto. Los estudiantes investigan, colaboran, toman decisiones y producen algo tangible.</p><h2>Lo que dicen los datos</h2><p>Estudios del Buck Institute for Education muestran que los alumnos que aprenden mediante proyectos retienen hasta un 60% más de contenido a largo plazo y desarrollan habilidades de trabajo en equipo significativamente superiores a las de sus pares con educación tradicional.</p>',
  'publicado', '2026-04-28 09:00:00', 5, 1, 2
),

(
  'Por qué el bilingüismo cambia la manera de pensar',
  'bilinguismo-cambia-manera-de-pensar',
  'Hablar dos idiomas no es solo una habilidad comunicativa. Es una forma distinta de procesar, razonar y relacionarse con el mundo.',
  '<p>Durante décadas se creyó que enseñar dos idiomas a la vez confundía a los niños. La ciencia cognitiva ha desmentido ese mito con contundencia. Los niños bilingües no solo aprenden dos sistemas lingüísticos: desarrollan un cerebro con mayor flexibilidad, mejores mecanismos de atención y una capacidad más sofisticada para cambiar entre contextos.</p><p>El neurocientífico Álvaro Pascual-Leone, de la Universidad de Harvard, ha documentado cómo el cerebro bilingüe muestra una densidad diferente en la corteza prefrontal, zona responsable del control ejecutivo y la toma de decisiones.</p><h2>Bilingüismo en el aula</h2><p>En el Colegio Bilbao trabajamos desde preescolar con inmersión progresiva en inglés. No se trata de traducir. Se trata de pensar, preguntar y crear directamente en dos idiomas.</p>',
  'publicado', '2026-04-22 10:00:00', 5, 4, 2
),

(
  'El rol del docente en la era de la inteligencia artificial',
  'rol-docente-inteligencia-artificial',
  'Cuando las máquinas pueden enseñar contenidos, el maestro se convierte en lo más valioso de la ecuación educativa.',
  '<p>ChatGPT puede resumir la Revolución Francesa en treinta segundos. Khan Academy ofrece tutorías personalizadas en tiempo real. ¿Para qué sirve entonces un maestro en 2026?</p><p>La respuesta es paradójica: sirve más que nunca. Pero para algo diferente.</p><p>La inteligencia artificial puede transferir información con eficiencia incomparable. Lo que no puede hacer es mirar a un alumno a los ojos y detectar que algo está pasando. No puede crear el tipo de vínculo que convierte una clase de física en una experiencia formativa.</p><h2>La nueva identidad docente</h2><p>El maestro del siglo XXI no compite con la IA. La orquesta. Su valor no está en saber más que sus alumnos —cosa que ya no es posible garantizar—, sino en enseñarles a relacionarse críticamente con el conocimiento y a convertirlo en acción.</p>',
  'publicado', '2026-04-18 09:30:00', 4, 6, 2
),

(
  'Arte y creatividad como pilares del desarrollo infantil',
  'arte-creatividad-desarrollo-infantil',
  'La expresión artística no es un lujo curricular. Es una necesidad cognitiva y emocional de primer orden.',
  '<p>En los debates sobre educación del futuro, el arte suele aparecer como lo primero que se puede recortar cuando hay presión por resultados académicos. Esta es una de las decisiones más costosas —y menos visibles— que puede tomar un sistema educativo.</p><p>El arte desarrolla competencias que ninguna asignatura convencional cubre de forma integral: tolerancia a la ambigüedad, perseverancia ante el proceso, capacidad de comunicar ideas sin palabras, y disposición para revisar el propio trabajo.</p><h2>El Colegio Bilbao y las artes</h2><p>Nuestra semana de artes anuales es uno de los eventos más esperados del calendario escolar. No porque sea un espectáculo —aunque lo es—, sino porque es el momento en que cada alumno tiene que presentar algo que creó desde cero y explicar por qué lo hizo así.</p>',
  'publicado', '2026-04-14 11:00:00', 5, 5, 2
),

(
  'Deportes de equipo: más que ganar, aprender a perder',
  'deportes-equipo-aprender-perder',
  'Las canchas y los campos de juego son algunos de los mejores salones de clases que existen, si sabemos aprovecharlos.',
  '<p>Un alumno que pierde un partido y al día siguiente vuelve a entrenar está aprendiendo algo que ningún libro puede enseñar: la diferencia entre el fracaso y la derrota definitiva.</p><p>Los deportes de equipo son una escuela de vida comprimida. En noventa minutos ocurren situaciones que en la vida real se distribuirían en años: la frustración, la camaradería, la injusticia del árbitro, el error propio, el error del compañero, la remontada inesperada.</p><h2>El entrenador como educador</h2><p>En el Colegio Bilbao los entrenadores reciben formación pedagógica específica. No solo enseñan técnica deportiva. Forman personas.</p>',
  'publicado', '2026-04-10 10:00:00', 4, 7, 2
),

(
  'Cómo crear rutinas de estudio que realmente funcionen',
  'rutinas-de-estudio-que-funcionan',
  'La disciplina no se improvisa. Se diseña con pequeños hábitos que, juntos, producen resultados extraordinarios.',
  '<p>El error más común que cometen los estudiantes —y muchos adultos— al intentar mejorar su rendimiento es confiar en la motivación. La motivación es un estado emocional: aparece, desaparece y no avisa cuándo volverá.</p><p>Los hábitos, en cambio, son automáticos. No requieren motivación porque se han integrado al flujo del día. La diferencia entre el alumno que estudia de manera consistente y el que estudia solo antes del examen no es inteligencia ni voluntad. Es estructura.</p><h2>Los cuatro elementos de una rutina efectiva</h2><p>Tiempo fijo, espacio dedicado, objetivo claro para cada sesión y un sistema de revisión son los cuatro pilares sobre los que se construye cualquier rutina de estudio que funciona a largo plazo.</p>',
  'publicado', '2026-04-07 09:00:00', 5, 4, 2
),

(
  'Comunidad escolar: el secreto detrás del éxito académico',
  'comunidad-escolar-exito-academico',
  'Los colegios con mayor rendimiento no se distinguen solo por sus maestros o su currículo. Se distinguen por su comunidad.',
  '<p>Existe un factor predictor del éxito académico que rara vez aparece en los rankings educativos: el sentido de pertenencia. Los alumnos que sienten que son parte de algo más grande que ellos mismos —una comunidad que los conoce, los reta y los apoya— tienen un desempeño consistentemente superior al de sus pares en entornos impersonales.</p><p>En el Colegio Bilbao trabajamos activamente la cohesión de comunidad a través de proyectos transversales, espacios de participación familiar y rituales escolares que crean identidad compartida.</p>',
  'publicado', '2026-04-03 10:00:00', 4, 3, 2
),

(
  'Intercambios estudiantiles: crecer más allá del aula',
  'intercambios-estudiantiles-crecer',
  'Vivir tres semanas en otra cultura no cambia solo el idioma. Cambia la manera de ver el propio país, la propia familia y uno mismo.',
  '<p>El primer día en una familia anfitriona extranjera es uno de los momentos más formativos que puede vivir un adolescente. No por lo que aprende del otro país, sino por lo que aprende de sí mismo cuando se queda sin los apoyos habituales.</p><p>Los intercambios estudiantiles aceleran el desarrollo de competencias que los educadores denominamos «transversales»: resiliencia, adaptabilidad, comunicación intercultural, autoconocimiento. Son las mismas competencias que las empresas más innovadoras del mundo declaran como prioritarias en sus procesos de selección.</p>',
  'publicado', '2026-03-28 09:00:00', 4, 8, 2
),

(
  'La lectura en la era digital: hábitos que los padres pueden cultivar',
  'lectura-era-digital-habitos-padres',
  'Leer en papel sigue siendo insustituible. Pero el reto hoy no es competir con las pantallas sino usarlas a nuestro favor.',
  '<p>Los padres que se preguntan cómo fomentar la lectura en sus hijos en un mundo de notificaciones, reels y videojuegos tienen razón en preocuparse. La lectura profunda —la que requiere sostener la atención durante veinte minutos sin interrupciones— está en retroceso entre los jóvenes de todo el mundo.</p><p>Pero hay buenas noticias: las familias donde los adultos leen —no solo donde hay libros en el librero— tienen hijos que leen más. El hábito se transmite por contagio, no por imposición.</p><h2>Tres hábitos concretos</h2><p>Leer en voz alta incluso con adolescentes, tener una hora sin pantallas antes de dormir, y dejar que el niño elija sus propios libros aunque no sean los que tú escogerías: estas tres prácticas tienen evidencia empírica que las respalda.</p>',
  'publicado', '2026-03-24 10:00:00', 5, 3, 2
),

(
  'Vida escolar equilibrada: bienestar emocional y rendimiento académico',
  'vida-escolar-equilibrada-bienestar',
  'No es posible sostener el rendimiento académico sobre una base de estrés crónico. La escuela sana rinde más.',
  '<p>Durante años, el debate sobre calidad educativa se centró en resultados: exámenes, posiciones en rankings, porcentajes de aprobación. Hoy sabemos que esa perspectiva era incompleta. Una escuela que produce buenos resultados académicos a costa del bienestar de sus alumnos no es una buena escuela. Es una fábrica.</p><p>El bienestar emocional no es lo opuesto al rigor académico. Es su condición. Un alumno que se siente seguro, valorado y parte de una comunidad tiene más recursos cognitivos disponibles para aprender, porque no los está gastando en gestionar amenazas sociales o emocionales.</p>',
  'publicado', '2026-03-18 09:00:00', 5, 2, 2
),

(
  'Inteligencia emocional en el aula: lo que la educación tradicional suele olvidar',
  'inteligencia-emocional-en-el-aula',
  'Saber lo que se siente, nombrar las emociones con precisión y regularlas con inteligencia es tan importante como cualquier contenido curricular.',
  '<p>Daniel Goleman popularizó el término inteligencia emocional en 1995. Treinta años después, la mayoría de los sistemas educativos del mundo siguen evaluando casi exclusivamente la inteligencia académica. Esta brecha es uno de los grandes pendientes de la pedagogía contemporánea.</p><p>Los estudios sobre predicción del éxito en la vida adulta muestran que el cociente intelectual explica menos del 25% de la varianza en los resultados vitales de una persona. El resto lo explican factores relacionados con la inteligencia emocional: autocontrol, empatía, capacidad de diferir la gratificación, habilidades sociales.</p><h2>Cómo lo trabajamos</h2><p>En el Colegio Bilbao la educación socioemocional no es una asignatura separada. Está integrada al método VIDA, que permea todas las materias y espacios del colegio.</p>',
  'publicado', '2026-03-14 10:00:00', 4, 1, 2
);

-- ── Tags de artículos ─────────────────────────────────────────────────────────

INSERT INTO articulo_tags (articulo_id, tag_id) VALUES
(1,  1), (1,  8),
(2,  2), (2,  1),
(3,  7), (3,  2),
(4,  4), (4, 10),
(5,  6),
(6,  5),
(7,  2),
(8,  3),
(9,  9),
(10, 3),
(11, 8),
(12, 8), (12, 1);

-- ── Categorías de noticias ────────────────────────────────────────────────────

INSERT INTO categorias_noticias (nombre, slug, color, descripcion) VALUES
('Institucional', 'institucional', '#4267ac', 'Comunicados, logros y novedades del colegio como institución.'),
('Académico',     'academico',     '#4285f4', 'Proyectos, reconocimientos y actividades académicas.'),
('Cultural',      'cultural',      '#aa2296', 'Eventos artísticos, festivales y vida cultural del campus.'),
('Deportivo',     'deportivo',     '#34a853', 'Torneos, equipos y logros en el área deportiva.'),
('Comunidad',     'comunidad',     '#f5b400', 'Actividades que involucran a familias y la comunidad escolar.');

-- ── Noticias (12) ─────────────────────────────────────────────────────────────

INSERT INTO noticias (titulo, slug, extracto, contenido, portada, portada_alt, estado, destacada, fecha_publicacion, tiempo_lectura, categoria_id, autor_id) VALUES

(
  'Colegio Bilbao obtiene la certificación Cambridge para preparatoria',
  'certificacion-cambridge-preparatoria',
  'Tras un riguroso proceso de evaluación de 18 meses, el Colegio Bilbao es reconocido como institución preparatoria certificada por la Universidad de Cambridge, abriendo puertas en más de 160 países.',
  '<p>El <strong>Colegio Bilbao</strong> ha sido oficialmente reconocido como institución preparatoria certificada por la <strong>Universidad de Cambridge</strong>. Este reconocimiento, obtenido tras un proceso de evaluación de más de dieciocho meses, posiciona al colegio entre las pocas escuelas privadas de la zona poniente de la Ciudad de México en alcanzar esta distinción.</p><p>La certificación permite a los alumnos de preparatoria cursar materias bajo el currículo internacional Cambridge A-Level, cuyos resultados son reconocidos en más de 160 países y valorados positivamente en los procesos de admisión de las universidades más importantes del mundo.</p><h2>Un proceso exigente</h2><p>La evaluación Cambridge revisó aspectos como la formación docente, la infraestructura académica, la coherencia del currículo y los resultados de aprendizaje de los alumnos. El Colegio Bilbao cumplió todos los criterios con calificaciones superiores al promedio nacional.</p>',
  NULL, NULL, 'publicado', 1, '2026-05-15 09:00:00', 5, 1, 2
),

(
  'Feria de Ciencias 2026: los doce proyectos finalistas',
  'feria-ciencias-2026-proyectos-finalistas',
  'Los equipos presentaron soluciones en biotecnología, energías renovables y programación. Conoce los proyectos que se llevan el reconocimiento este ciclo escolar.',
  '<p>La edición 2026 de la Feria de Ciencias del Colegio Bilbao reunió a más de cincuenta equipos de todos los niveles. Después de una fase clasificatoria rigurosa, doce proyectos avanzaron a la gran final, evaluados por un jurado externo de especialistas en ciencia y tecnología.</p><p>Entre los proyectos destacados estuvo un sistema de purificación de agua de bajo costo desarrollado por alumnos de segundo de secundaria, y una aplicación de seguimiento de hábitos de estudio creada por un equipo de preparatoria con herramientas de inteligencia artificial.</p>',
  NULL, NULL, 'publicado', 0, '2026-05-10 10:00:00', 4, 2, 2
),

(
  'Equipo varonil Sub-17 clasifica a finales regionales de básquetbol',
  'sub-17-finales-regionales-basquetbol',
  'Con siete victorias consecutivas en la fase regular, el equipo de básquet del Colegio Bilbao avanza a la siguiente fase del torneo. Las finales se disputarán en junio.',
  '<p>El equipo varonil Sub-17 del Colegio Bilbao completó una fase regular impecable con siete victorias consecutivas, consolidándose como uno de los equipos más sólidos del torneo regional. El técnico destacó el trabajo colectivo y la disciplina como factores clave del éxito.</p><p>Las finales regionales se disputarán del 14 al 16 de junio en las instalaciones del Centro Deportivo Municipal. El equipo del Bilbao estará acompañado por una delegación de padres de familia y compañeros de clase.</p>',
  NULL, NULL, 'publicado', 0, '2026-05-08 12:00:00', 4, 4, 2
),

(
  'Inauguración del nuevo laboratorio de ciencias Bilbao',
  'inauguracion-laboratorio-ciencias-bilbao',
  'Equipado con tecnología de punta, el nuevo laboratorio permitirá a los alumnos de secundaria y preparatoria realizar experimentos de biología molecular, química analítica y física avanzada.',
  '<p>El Colegio Bilbao inauguró oficialmente su nuevo laboratorio de ciencias, una inversión de infraestructura diseñada para llevar la experiencia experimental de los alumnos al nivel de las mejores instituciones del país.</p><p>El laboratorio cuenta con 24 estaciones de trabajo individuales, campana de extracción de gases, microscopios de alta resolución, espectrofotómetro y kits de biología molecular. Fue diseñado en colaboración con la Facultad de Ciencias de la UNAM para garantizar que las prácticas estén alineadas con estándares universitarios.</p>',
  NULL, NULL, 'publicado', 0, '2026-05-05 10:00:00', 3, 1, 2
),

(
  'Semana de las Artes 2026: tres días de creatividad sin límites',
  'semana-artes-2026',
  'Más de 300 alumnos presentaron proyectos de teatro, música, danza, artes plásticas y diseño digital durante la edición anual de la Semana de las Artes del Colegio Bilbao.',
  '<p>La Semana de las Artes es uno de los eventos más esperados del calendario escolar del Colegio Bilbao. Durante tres días, el campus se transforma en un espacio de exhibición, presentación y celebración de la creatividad estudiantil.</p><p>Este año participaron más de trescientos alumnos de todos los niveles. Las actividades incluyeron una obra de teatro producida íntegramente por alumnos de preparatoria, una exposición de escultura en materiales reciclados y el primer showcase de música electrónica de la historia del colegio.</p>',
  NULL, NULL, 'publicado', 0, '2026-04-30 09:00:00', 3, 3, 2
),

(
  'Olimpiada regional de matemáticas: tres medallas para el Bilbao',
  'olimpiada-matematicas-tres-medallas',
  'Alumnos de primaria y secundaria obtuvieron dos medallas de plata y una de bronce en la fase regional de la Olimpiada Mexicana de Matemáticas.',
  '<p>El equipo de matemáticas del Colegio Bilbao regresó con tres reconocimientos de la fase regional de la Olimpiada Mexicana de Matemáticas (OMM) celebrada en Toluca. Dos medallas de plata para la categoría de secundaria y una medalla de bronce en la categoría de primaria avanzada.</p><p>Los tres alumnos galardonados —que compitieron contra representantes de más de cuarenta instituciones— llevaban más de un año preparándose en el club de matemáticas de alta competencia que el colegio abrió el ciclo escolar pasado.</p>',
  NULL, NULL, 'publicado', 0, '2026-04-25 11:00:00', 4, 2, 2
),

(
  'Nuevo programa de mentoría entre pares fortalece el aprendizaje colaborativo',
  'programa-mentoria-entre-pares',
  'Alumnos de preparatoria fungen como mentores académicos de alumnos de secundaria en un programa diseñado para beneficiar a ambas partes del proceso.',
  '<p>El Colegio Bilbao lanzó oficialmente su programa de mentoría entre pares, una iniciativa que conecta a alumnos de preparatoria con compañeros de secundaria que requieren apoyo en materias específicas.</p><p>A diferencia de las tutorías tradicionales, este programa está diseñado para que ambos participantes aprendan. Los mentores desarrollan habilidades de comunicación, liderazgo y organización; los mentorizados reciben apoyo personalizado de alguien que cursó recientemente los mismos contenidos.</p>',
  NULL, NULL, 'publicado', 0, '2026-04-20 10:00:00', 3, 1, 2
),

(
  'Torneo de ajedrez escolar: el Bilbao se corona campeón en categoría secundaria',
  'torneo-ajedrez-campeon-secundaria',
  'El equipo de ajedrez del Colegio Bilbao obtuvo el primer lugar en el torneo inter-escolar de la zona poniente, con una actuación invicta en las ocho rondas del torneo.',
  '<p>El equipo de ajedrez del Colegio Bilbao se coronó campeón del torneo inter-escolar de la zona poniente de la Ciudad de México. Los cinco integrantes del equipo completaron las ocho rondas del torneo sin ninguna derrota, con un marcador acumulado de 36 puntos sobre 40 posibles.</p><p>El entrenador del equipo destacó que la preparación fue especialmente intensa este ciclo, con sesiones tres veces por semana y análisis de partidas históricas de los grandes maestros internacionales.</p>',
  NULL, NULL, 'publicado', 0, '2026-04-15 09:00:00', 3, 4, 2
),

(
  'Festival de Primavera 2026: familias y alumnos celebran juntos',
  'festival-primavera-2026',
  'La edición de este año del Festival de Primavera del Colegio Bilbao reunió a más de 800 personas en una jornada de convivencia, actividades y presentaciones artísticas.',
  '<p>El Festival de Primavera del Colegio Bilbao es mucho más que un evento escolar. Es el momento del año en que la comunidad completa —alumnos, familias, docentes y personal— se reúne para celebrar lo que se ha construido juntos.</p><p>La edición 2026 contó con mercado de artesanías elaboradas por los alumnos, presentaciones de teatro y danza, un área de comida de distintas regiones de México preparada por familias voluntarias, y la tradicional carrera de relevos entre grupos.</p>',
  NULL, NULL, 'publicado', 0, '2026-04-12 11:00:00', 3, 5, 2
),

(
  'Aula de innovación tecnológica: el Colegio Bilbao da un paso al futuro',
  'aula-innovacion-tecnologica',
  'El nuevo espacio de innovación tecnológica cuenta con estaciones de robótica, impresión 3D, realidad aumentada y desarrollo de aplicaciones móviles.',
  '<p>El Colegio Bilbao inauguró su Aula de Innovación Tecnológica, un espacio diseñado específicamente para que los alumnos aprendan a crear tecnología, no solo a consumirla.</p><p>El aula cuenta con kits de robótica LEGO Education y Arduino, una impresora 3D de uso escolar, visores de realidad aumentada y estaciones de desarrollo de aplicaciones. Está disponible para todos los niveles a partir de cuarto de primaria.</p>',
  NULL, NULL, 'publicado', 0, '2026-04-08 10:00:00', 3, 1, 2
),

(
  'Delegación Bilbao en la Conferencia Nacional de Liderazgo Juvenil',
  'delegacion-bilbao-liderazgo-juvenil',
  'Cinco alumnos de preparatoria representaron al Colegio Bilbao en la Conferencia Nacional de Liderazgo Juvenil celebrada en Monterrey.',
  '<p>Cinco alumnos de preparatoria del Colegio Bilbao viajaron a Monterrey para participar en la Conferencia Nacional de Liderazgo Juvenil, un evento que reúne anualmente a representantes estudiantiles de las principales escuelas privadas del país.</p><p>La delegación presentó un proyecto titulado «Escuela sin muros», una propuesta para integrar el aprendizaje servicio en el currículo de secundaria y preparatoria. El proyecto fue reconocido con mención honorífica por su comité evaluador.</p>',
  NULL, NULL, 'publicado', 0, '2026-04-04 09:00:00', 4, 2, 2
),

(
  'Resultados del ciclo: 9 de cada 10 alumnos superan sus metas académicas',
  'resultados-ciclo-metas-academicas',
  'El reporte interno de desempeño académico muestra que el 91% de los alumnos del Colegio Bilbao alcanzó o superó sus objetivos de aprendizaje en el tercer bimestre.',
  '<p>El reporte de desempeño académico del tercer bimestre del ciclo escolar 2025-2026 muestra que el 91% de los alumnos del Colegio Bilbao alcanzó o superó los objetivos de aprendizaje establecidos al inicio del ciclo. El dato representa un incremento de cuatro puntos porcentuales respecto al mismo período del ciclo anterior.</p><p>La directora académica señaló que los resultados son consecuencia de un trabajo sostenido en tres frentes: fortalecimiento de la práctica docente, seguimiento individualizado del aprendizaje y mayor involucramiento de las familias en el proceso educativo.</p>',
  NULL, NULL, 'publicado', 0, '2026-04-01 10:00:00', 4, 1, 2
);
