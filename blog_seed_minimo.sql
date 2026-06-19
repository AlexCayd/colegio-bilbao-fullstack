-- =====================================================
--  BLOG COLEGIO BILBAO — Seed completo
--  Versión 2 — incluye columnas de flujo de revisión
--
--  Cuentas de administrador:
--    admin@bilbao.edu.mx           / Tlalmimilolpan39%
--    alexander.oliva@bilbao.edu.mx / ColegioBilbao13
--
--  Cuentas de editor (datos de ejemplo):
--    maria.gonzalez@bilbao.edu.mx  / EditorBilbao1
--    carlos.ramirez@bilbao.edu.mx  / EditorBilbao1
--
--  Paleta de colores para categorías (escala cromática):
--  #fc6722  #f5b400  #8ac926  #34a853  #46bdc6
--  #4285f4  #4267ac  #aa2296  #ea075a  #e51022
-- =====================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS notificaciones;
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
    id                    INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    titulo                VARCHAR(255)  NOT NULL,
    slug                  VARCHAR(280)  NOT NULL,
    extracto              VARCHAR(300)  NULL,
    contenido             LONGTEXT      NULL,
    imagen                VARCHAR(255)  NULL,
    estado                ENUM('borrador','publicado','programado') NOT NULL DEFAULT 'borrador',
    envio_revision        TINYINT(1)    NOT NULL DEFAULT 0,
    comentario_revision   TEXT          NULL,
    version_pendiente     LONGTEXT      NULL,
    fecha_publicacion     DATETIME      NULL,
    tiempo_lectura        TINYINT UNSIGNED NULL,
    vistas                INT           NOT NULL DEFAULT 0,
    likes                 INT           NOT NULL DEFAULT 0,
    categoria_id          INT UNSIGNED  NULL,
    autor_id              INT UNSIGNED  NULL,
    creado_en             TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en        TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
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
    id                    INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    titulo                VARCHAR(255)  NOT NULL,
    slug                  VARCHAR(280)  NOT NULL,
    extracto              VARCHAR(400)  NULL,
    contenido             LONGTEXT      NULL,
    portada               VARCHAR(255)  NULL,
    portada_alt           VARCHAR(255)  NULL,
    estado                ENUM('borrador','publicado','programado') NOT NULL DEFAULT 'borrador',
    envio_revision        TINYINT(1)    NOT NULL DEFAULT 0,
    comentario_revision   TEXT          NULL,
    version_pendiente     LONGTEXT      NULL,
    destacada             TINYINT(1)    NOT NULL DEFAULT 0,
    fecha_publicacion     DATETIME      NULL,
    tiempo_lectura        TINYINT UNSIGNED NULL,
    vistas                INT           NOT NULL DEFAULT 0,
    likes                 INT           NOT NULL DEFAULT 0,
    categoria_id          INT UNSIGNED  NULL,
    autor_id              INT UNSIGNED  NULL,
    creado_en             TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en        TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_slug (slug),
    CONSTRAINT fk_noticia_categoria FOREIGN KEY (categoria_id) REFERENCES categorias_noticias (id) ON DELETE SET NULL,
    CONSTRAINT fk_noticia_autor     FOREIGN KEY (autor_id)     REFERENCES usuarios             (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE notificaciones (
    id              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    usuario_id      INT UNSIGNED  NOT NULL,
    tipo            VARCHAR(60)   NOT NULL,
    referencia_id   INT UNSIGNED  NULL,
    referencia_tipo ENUM('articulo','noticia') NULL,
    mensaje         VARCHAR(255)  NOT NULL,
    leida           TINYINT(1)    NOT NULL DEFAULT 0,
    creado_en       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_usuario_leida (usuario_id, leida),
    CONSTRAINT fk_notif_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Usuarios ──────────────────────────────────────────────────────────────────
-- id 1 → admin Bilbao  (administrador) — password: Tlalmimilolpan39%
-- id 2 → Alexander     (administrador) — password: ColegioBilbao13
-- id 3 → María         (editor)        — password: EditorBilbao25  — artículo/noticia en revisión pendiente
-- id 4 → Carlos        (editor)        — password: EditorBilbao25  — artículo/noticia rechazados con feedback

INSERT INTO usuarios (nombre, email, password, rol) VALUES
('Administrador Bilbao',  'admin@bilbao.edu.mx',             '$2y$12$nJQBtZftIX.10iSyqFSv6uKIw0BhTQsGeCOO1xSkL.Cu77TWZ1Kai', 'administrador'),
('Alexander Oliva',       'alexander.oliva@bilbao.edu.mx',   '$2y$12$0TtlP9O9HsElGRDauM2CaOLYYAo5Xquik7QqFO2HS6izU7NyVaOMq', 'administrador'),
('María González',        'maria.gonzalez@bilbao.edu.mx',    '$2y$12$Y7OOx/TShLw3Ypjim4gWIOlNxZFewG.2dc8HJvYlKlSVsjMXiQ6bm',  'editor'),
('Carlos Ramírez',        'carlos.ramirez@bilbao.edu.mx',    '$2y$12$Y7OOx/TShLw3Ypjim4gWIOlNxZFewG.2dc8HJvYlKlSVsjMXiQ6bm',  'editor');

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

-- ── Categorías de noticias ────────────────────────────────────────────────────

INSERT INTO categorias_noticias (nombre, slug, color, descripcion) VALUES
('Institucional', 'institucional', '#4267ac', 'Comunicados, logros y novedades del colegio como institución.'),
('Académico',     'academico',     '#4285f4', 'Proyectos, reconocimientos y actividades académicas.'),
('Cultural',      'cultural',      '#aa2296', 'Eventos artísticos, festivales y vida cultural del campus.'),
('Deportivo',     'deportivo',     '#34a853', 'Torneos, equipos y logros en el área deportiva.'),
('Comunidad',     'comunidad',     '#f5b400', 'Actividades que involucran a familias y la comunidad escolar.');
