-- =====================================================
--  BLOG COLEGIO BILBAO — Seed mínimo de verificación
--  Admin: admin@bilbao.edu.mx / Tlalmimilolpan39%
-- =====================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS articulo_tags;
DROP TABLE IF EXISTS articulos;
DROP TABLE IF EXISTS tags;
DROP TABLE IF EXISTS categorias;
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
    color       CHAR(7)       NOT NULL DEFAULT '#4D8ABB',
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

-- ── Datos mínimos ─────────────────────────────────────────────────────────────

INSERT INTO usuarios (nombre, email, password, rol) VALUES
('Administrador Bilbao', 'admin@bilbao.edu.mx', '$2y$12$nJQBtZftIX.10iSyqFSv6uKIw0BhTQsGeCOO1xSkL.Cu77TWZ1Kai', 'administrador');

INSERT INTO categorias (nombre, slug, descripcion, color) VALUES
('Modelo educativo', 'modelo-educativo', 'Filosofía y propuesta pedagógica del Colegio Bilbao.', '#374C69');

INSERT INTO tags (nombre, slug) VALUES
('educación integral', 'educacion-integral');

INSERT INTO articulos
    (titulo, slug, extracto, contenido, estado, fecha_publicacion, tiempo_lectura, categoria_id, autor_id)
VALUES (
    'Educación contemporánea: criterio y carácter',
    'educacion-contemporanea-criterio-y-caracter',
    'Una declaración inaugural sobre la escuela que este tiempo exige.',
    '<p>La escuela no puede limitarse a transmitir contenidos. Debe formar personas capaces de pensar con profundidad, actuar con integridad y relacionarse con empatía.</p>',
    'publicado',
    NOW(),
    3,
    1,
    1
);

INSERT INTO articulo_tags (articulo_id, tag_id) VALUES (1, 1);
