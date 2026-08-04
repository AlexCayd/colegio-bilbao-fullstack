-- ══════════════════════════════════════════════════════════════════════════════
-- database.sql — ESTRUCTURA. Crea todas las tablas; no inserta ni un registro.
--
-- Es el único archivo que define el esquema: si cambia una columna, cambia aquí
-- y después se ajustan development.sql y deploy.sql para que sigan cargando.
-- Ejecutar siempre PRIMERO, y luego el archivo de datos del entorno.
-- Ver database/CLAUDE.md para las reglas de estos tres archivos.
-- ══════════════════════════════════════════════════════════════════════════════
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS suplencia_horas;
DROP TABLE IF EXISTS suplencias;
DROP TABLE IF EXISTS horarios;
DROP TABLE IF EXISTS periodos;
DROP TABLE IF EXISTS aulas;
DROP TABLE IF EXISTS grupos;
DROP TABLE IF EXISTS materias;
DROP TABLE IF EXISTS eventos;
DROP TABLE IF EXISTS notificaciones;
DROP TABLE IF EXISTS articulo_tags;
DROP TABLE IF EXISTS articulos;
DROP TABLE IF EXISTS tags;
DROP TABLE IF EXISTS categorias;
DROP TABLE IF EXISTS noticias;
DROP TABLE IF EXISTS categorias_noticias;
DROP TABLE IF EXISTS testimoniales;
DROP TABLE IF EXISTS usuarios;

-- Las comprobaciones de FK se reactivan al FINAL del archivo, no aquí:
-- si la base de datos comparte espacio con otras tablas, una FK ajena hacia
-- alguna de estas tablas abortaría los CREATE de más abajo.

CREATE TABLE usuarios (
    id               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    nombre           VARCHAR(120)  NOT NULL,
    email            VARCHAR(180)  NOT NULL,
    password         VARCHAR(255)  NOT NULL,
    rol              ENUM('administrador','usuario') NOT NULL DEFAULT 'usuario',
    rol_redaccion    ENUM('revisor','editor') NULL,  -- solo aplica si tiene el módulo 'redaccion'
    tipo_personal    SET('profesor','prefecto','administrativo') NULL, -- combinable (ej. 'profesor,administrativo')
    puede_suplir     TINYINT(1)    NOT NULL DEFAULT 1, -- 0 = "No puede suplir a otros profesores"
    modulos          VARCHAR(255)  NULL,          -- CSV de módulos para rol 'usuario' (ej. 'redaccion,suplencias')
    fecha_nacimiento DATE          NULL,          -- para el calendario interno de cumpleaños
    avatar           VARCHAR(255)  NULL,
    ultimo_acceso    DATETIME      NULL,
    creado_en        TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Módulo Horarios ───────────────────────────────────────────────────────────
-- Jornada: bloques de hora (periodos). es_receso=1 marca descansos (no son clase).
CREATE TABLE periodos (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    orden       TINYINT UNSIGNED NOT NULL,           -- posición en la jornada
    etiqueta    VARCHAR(40)  NOT NULL,               -- ej. '1ª hora', 'Receso'
    hora_inicio TIME         NOT NULL,
    hora_fin    TIME         NOT NULL,
    es_receso   TINYINT(1)   NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    UNIQUE KEY uq_orden (orden)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE aulas (
    id     INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(80)  NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_aula (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Grupos de alumnos. El orden de listado se deduce del nivel (Maternal →
-- Bachillerato, via Materia::ordenNivel()) y, dentro de él, del nombre.
CREATE TABLE grupos (
    id     INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(80)  NOT NULL,                     -- ej. '3A Primaria'
    nivel  ENUM('Maternal','Kinder','Primaria','Secundaria','Bachillerato') NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_grupo (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Catálogo de materias/asignaturas. El nivel NO va en el nombre: 'Arte' + nivel 'Kinder'.
CREATE TABLE materias (
    id     INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(120) NOT NULL,
    nivel  ENUM('Maternal','Kinder','Primaria','Secundaria','Bachillerato') NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_materia (nombre, nivel)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Rejilla de horario: una fila = una clase (día × periodo) de un profesor con grupo/aula/materia.
CREATE TABLE horarios (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    dia         ENUM('lunes','martes','miercoles','jueves','viernes') NOT NULL,
    periodo_id  INT UNSIGNED NOT NULL,
    profesor_id INT UNSIGNED NOT NULL,
    grupo_id    INT UNSIGNED NULL,
    aula_id     INT UNSIGNED NULL,
    materia_id  INT UNSIGNED NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_prof (dia, periodo_id, profesor_id),
    UNIQUE KEY uq_aula (dia, periodo_id, aula_id),
    UNIQUE KEY uq_grupo (dia, periodo_id, grupo_id),
    KEY idx_prof (profesor_id),
    CONSTRAINT fk_hor_periodo  FOREIGN KEY (periodo_id)  REFERENCES periodos (id) ON DELETE CASCADE,
    CONSTRAINT fk_hor_profesor FOREIGN KEY (profesor_id) REFERENCES usuarios (id) ON DELETE CASCADE,
    CONSTRAINT fk_hor_grupo    FOREIGN KEY (grupo_id)    REFERENCES grupos (id)   ON DELETE SET NULL,
    CONSTRAINT fk_hor_aula     FOREIGN KEY (aula_id)     REFERENCES aulas (id)    ON DELETE SET NULL,
    CONSTRAINT fk_hor_materia  FOREIGN KEY (materia_id)  REFERENCES materias (id) ON DELETE SET NULL
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

-- Notificaciones de cualquier módulo del panel, no solo de Redacción.
-- `referencia_tipo` era un ENUM('articulo','noticia'): se abrió a VARCHAR porque
-- suplencias/horarios/eventos también notifican. El destino se guarda en `enlace`
-- en vez de deducirse del tipo (suplencias abre en /agendar, no en /editar, y
-- horarios no tiene URL por fila).
CREATE TABLE notificaciones (
    id              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    usuario_id      INT UNSIGNED  NOT NULL,
    modulo          VARCHAR(40)   NOT NULL DEFAULT 'general', -- redaccion|suplencias|horarios|eventos|usuarios
    tipo            VARCHAR(60)   NOT NULL,
    nivel           ENUM('info','exito','aviso','error') NOT NULL DEFAULT 'info',
    referencia_id   INT UNSIGNED  NULL,
    referencia_tipo VARCHAR(40)   NULL,
    enlace          VARCHAR(255)  NULL,          -- destino del "Ver" (ruta absoluta del panel)
    mensaje         VARCHAR(255)  NOT NULL,
    leida           TINYINT(1)    NOT NULL DEFAULT 0,
    creado_en       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_usuario_leida (usuario_id, leida),
    CONSTRAINT fk_notif_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE testimoniales (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  nombre     VARCHAR(100) NOT NULL,
  rol        ENUM('Papá','Mamá','Exalumno','Exalumna','Familia') NOT NULL,
  comentario TEXT NOT NULL,
  aprobado   TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Módulo de Eventos / Calendario (avisos + calendario público de Familias) ──
CREATE TABLE eventos (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    fecha       DATE         NOT NULL,
    fecha_fin   DATE         NULL,
    tipo        ENUM('festivo','evento','junta','entrega','suspension') NOT NULL DEFAULT 'evento',
    titulo      VARCHAR(160) NOT NULL,
    descripcion TEXT         NULL,
    publico     TINYINT(1)   NOT NULL DEFAULT 1,   -- 1 = visible en el calendario de Familias
    creado_en   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_fecha (fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Módulo de Suplencias (panel intranet) ─────────────────────────────────────
-- Una suplencia = la ausencia de un profesor en una fecha. Sus horas viven en suplencia_horas.
-- Flujo anticipada: solicitada → agendada → completada. Flujo sin aviso: por_justificar → ... → completada.
CREATE TABLE suplencias (
    id                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    profesor_ausente_id INT UNSIGNED NULL,
    fecha               DATE         NOT NULL,
    motivo              VARCHAR(160) NULL,
    notas               TEXT         NULL,
    justificante        VARCHAR(255) NULL,               -- PDF o imagen de justificación
    origen              ENUM('anticipada','sin_aviso') NOT NULL DEFAULT 'anticipada',
    estado              ENUM('solicitada','agendada','en_curso','por_justificar','completada','cancelada') NOT NULL DEFAULT 'solicitada',
    creado_por          INT UNSIGNED NULL,
    creado_en           TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_fecha (fecha),
    KEY idx_estado (estado),
    CONSTRAINT fk_supl_ausente FOREIGN KEY (profesor_ausente_id) REFERENCES usuarios (id) ON DELETE SET NULL,
    CONSTRAINT fk_supl_creador FOREIGN KEY (creado_por)          REFERENCES usuarios (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Cobertura por hora: cada periodo a cubrir puede tener su propio suplente y su validación.
CREATE TABLE suplencia_horas (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    suplencia_id INT UNSIGNED NOT NULL,
    periodo_id   INT UNSIGNED NOT NULL,
    grupo_id     INT UNSIGNED NULL,
    aula_id      INT UNSIGNED NULL,
    materia_id   INT UNSIGNED NULL,
    suplente_id  INT UNSIGNED NULL,
    estado_hora  ENUM('pendiente','agendada','validada') NOT NULL DEFAULT 'pendiente',
    validado_en  DATETIME     NULL,
    -- Marca anti-duplicado del aviso "confirma si cubriste": se emite al entrar
    -- al panel cuando la fecha ya pasó y la hora sigue 'agendada'.
    recordatorio_en DATETIME  NULL,
    PRIMARY KEY (id),
    KEY idx_suplencia (suplencia_id),
    KEY idx_suplente (suplente_id),
    KEY idx_pendientes (suplente_id, estado_hora),
    CONSTRAINT fk_sh_suplencia FOREIGN KEY (suplencia_id) REFERENCES suplencias (id) ON DELETE CASCADE,
    CONSTRAINT fk_sh_periodo   FOREIGN KEY (periodo_id)   REFERENCES periodos (id)   ON DELETE CASCADE,
    CONSTRAINT fk_sh_grupo     FOREIGN KEY (grupo_id)     REFERENCES grupos (id)     ON DELETE SET NULL,
    CONSTRAINT fk_sh_aula      FOREIGN KEY (aula_id)      REFERENCES aulas (id)      ON DELETE SET NULL,
    CONSTRAINT fk_sh_materia   FOREIGN KEY (materia_id)   REFERENCES materias (id)   ON DELETE SET NULL,
    CONSTRAINT fk_sh_suplente  FOREIGN KEY (suplente_id)  REFERENCES usuarios (id)   ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Reactivar comprobaciones de FK (al final: ver nota de la cabecera)
SET FOREIGN_KEY_CHECKS = 1;
