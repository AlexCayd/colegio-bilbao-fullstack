-- =====================================================
--  BLOG COLEGIO BILBAO — Estructura de base de datos
--  Solo tablas (sin datos). Los INSERT viven en seed_contenido.sql.
--
--  Uso:
--    mysql -u root -p colegiobilbao < database.sql
--    mysql -u root -p colegiobilbao < seed_contenido.sql
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
DROP TABLE IF EXISTS testimoniales;
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
-- ── Testimoniales ─────────────────────────────────────────────────────────────
CREATE TABLE testimoniales (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  nombre     VARCHAR(100) NOT NULL,
  rol        ENUM('Papá','Mamá','Exalumno','Exalumna','Familia') NOT NULL,
  comentario TEXT NOT NULL,
  aprobado   TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
