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

DROP TABLE IF EXISTS swap_clases;
DROP TABLE IF EXISTS suplencia_horas;
DROP TABLE IF EXISTS suplencias;
DROP TABLE IF EXISTS horarios;
DROP TABLE IF EXISTS lugares_guardia;
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
    -- Combinable (ej. 'profesor,administrativo'). 'prefecto' y 'directivo' son
    -- EXCLUYENTES: no se mezclan con nada (lo impone normalizarTipoPersonal()).
    -- 'directivo' coordina igual que prefectura —abre suplencias, revisa y descarga
    -- justificantes— pero NO edita usuarios ni horarios: ahí solo mira.
    tipo_personal    SET('profesor','prefecto','administrativo','directivo') NULL,
    -- Niveles en los que imparte. Solo tiene sentido con tipo_personal 'profesor'.
    -- Es la fuente DECLARATIVA del nivel de un profesor: antes se deducía de sus
    -- clases, lo que fallaba en cuanto no tenía horario cargado. Acota el eje de su
    -- rejilla y prioriza a los candidatos del nivel en las suplencias.
    niveles          SET('Maternal','Kinder','Primaria','Secundaria','Bachillerato') NULL,
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
-- Jornada POR NIVEL: cada nivel entra, sale y descansa a su hora. es_receso=1 marca
-- descansos (no son clase).
--
-- ⚠️ Como un profesor puede dar clase en varios niveles y las jornadas se desfasan
-- entre sí, la disponibilidad NO se decide comparando periodo_id: dos periodos
-- distintos pueden pisarse en el reloj. Se decide por solapamiento de
-- hora_inicio/hora_fin (Periodo::solapan(), Horario::libreEn()).
CREATE TABLE periodos (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nivel       ENUM('Maternal','Kinder','Primaria','Secundaria','Bachillerato') NOT NULL,
    orden       TINYINT UNSIGNED NOT NULL,           -- posición dentro de la jornada de SU nivel
    etiqueta    VARCHAR(40)  NOT NULL,               -- ej. '1ª hora', 'Receso'
    hora_inicio TIME         NOT NULL,
    hora_fin    TIME         NOT NULL,
    es_receso   TINYINT(1)   NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    UNIQUE KEY uq_nivel_orden (nivel, orden)
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

-- Rejilla de horario: una fila = una clase (día × periodo) de UN profesor con
-- grupo/aula/materia. Un mismo bloque real puede ocupar varias filas.
--
-- ⚠️ NO HAY UNIQUE de profesor/aula/grupo, y es deliberado. El horario real del
-- colegio tiene tres situaciones que los hacían imposibles:
--
--   · Coteaching        una clase la imparten un titular y uno o dos acompañantes.
--                       Cada uno necesita SU fila para que la clase aparezca en su
--                       horario y las suplencias lo cuenten como ocupado
--                       → misma (dia, periodo, grupo), distinto profesor.
--   · Materia dividida  1ºA de Secundaria tiene Arte y Música a la misma hora y el
--                       alumnado se reparte; en la rejilla salen las dos opciones
--                       → misma (dia, periodo, grupo), distinta `division`.
--   · Clase conjunta    un grupo pequeño de Bachillerato se junta con otro (6ºA+6ºB
--                       en Ecología); el profesor y el aula son los mismos
--                       → misma (dia, periodo, profesor/aula), distinto grupo.
--
-- La validación de choques vive entera en la aplicación (Horario::choques()), que
-- compara por RELOJ y no por periodo_id —con jornada por nivel dos periodos
-- distintos se pisan— y sabe distinguir estos tres casos de un conflicto real.
-- Un trigger no serviría: daría a mysqli un error genérico y el importador CSV no
-- podría decir qué fila falló.
-- Lugares donde se hacen las guardias de receso (patio, comedor, pasillos…).
-- Catálogo editable desde el panel; la UI los ofrece como tabs al agendar.
CREATE TABLE lugares_guardia (
    id     INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(80)  NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_lugar_guardia (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE horarios (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    dia         ENUM('lunes','martes','miercoles','jueves','viernes') NOT NULL,
    periodo_id  INT UNSIGNED NOT NULL,
    profesor_id INT UNSIGNED NOT NULL,
    -- Una guardia de receso es una ocupación real del profesor, así que vive en
    -- esta misma tabla en vez de en una propia: así `ocupacionDiaDeVarios()`,
    -- `libreEn()`, `choques()` y el algoritmo de suplencias la tienen en cuenta sin
    -- ningún caso especial —no se le puede asignar una suplencia mientras vigila— y
    -- una guardia se puede suplir igual que una clase. En una guardia
    -- `grupo_id`/`materia_id` van NULL y manda `lugar_id`; su `periodo_id` apunta
    -- normalmente a un periodo con es_receso = 1.
    tipo        ENUM('clase','guardia') NOT NULL DEFAULT 'clase',
    grupo_id    INT UNSIGNED NULL,
    aula_id     INT UNSIGNED NULL,
    materia_id  INT UNSIGNED NULL,
    lugar_id    INT UNSIGNED NULL,       -- solo en tipo='guardia'
    -- Papel del profesor en la clase. El titular la imparte; el acompañante entra
    -- con él (apoyo del tutor de grupo en las especialidades). Ambos la tienen
    -- ocupada, así que ninguno de los dos puede recibir una suplencia a esa hora.
    rol_docente ENUM('titular','acompanante') NOT NULL DEFAULT 'titular',
    -- Opción dentro de una materia dividida: 0 = la clase es para todo el grupo,
    -- 1..n = una de las opciones simultáneas entre las que se reparte el alumnado.
    -- Dos filas del mismo grupo a la misma hora solo son legítimas si ambas
    -- llevan division > 0 y distinta.
    division    TINYINT UNSIGNED NOT NULL DEFAULT 0,
    -- Color del bloque, elegido a mano en el editor de horarios (módulo Usuarios).
    -- NULL = automático: la vista lo deriva del nombre de la materia
    -- (BlogController::colorMateria()), que es lo que hacía toda la rejilla antes de
    -- que existiera esta columna. Es además el único dato que el CSV no sabe expresar,
    -- así que el importador lo conserva en vez de reescribirlo.
    color       CHAR(7)      NULL,
    PRIMARY KEY (id),
    -- Único duplicado que sigue sin tener sentido: el mismo profesor dos veces en
    -- la misma casilla para el mismo grupo y la misma opción.
    UNIQUE KEY uq_clase (dia, periodo_id, profesor_id, grupo_id, division),
    KEY idx_prof_dia (profesor_id, dia),   -- lo usa Horario::ocupacionDiaDeVarios()
    KEY idx_grupo_dia (grupo_id, dia),
    CONSTRAINT fk_hor_periodo  FOREIGN KEY (periodo_id)  REFERENCES periodos (id) ON DELETE CASCADE,
    CONSTRAINT fk_hor_profesor FOREIGN KEY (profesor_id) REFERENCES usuarios (id) ON DELETE CASCADE,
    CONSTRAINT fk_hor_grupo    FOREIGN KEY (grupo_id)    REFERENCES grupos (id)   ON DELETE SET NULL,
    CONSTRAINT fk_hor_aula     FOREIGN KEY (aula_id)     REFERENCES aulas (id)    ON DELETE SET NULL,
    CONSTRAINT fk_hor_materia  FOREIGN KEY (materia_id)  REFERENCES materias (id) ON DELETE SET NULL,
    CONSTRAINT fk_hor_lugar    FOREIGN KEY (lugar_id)    REFERENCES lugares_guardia (id) ON DELETE SET NULL
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

-- ── Módulo de Eventos / Calendario ────────────────────────────────────────────
-- Un evento se dirige a UN público (`audiencia`) y puede acotarse a uno o varios
-- niveles educativos. Sustituye al antiguo flag `publico`, que solo distinguía
-- "interno" de "visible para las familias" y no sabía expresar un evento dirigido
-- al alumnado ni acotado a Secundaria.
CREATE TABLE eventos (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    fecha       DATE         NOT NULL,
    fecha_fin   DATE         NULL,
    tipo        ENUM('festivo','evento','junta','entrega','suspension') NOT NULL DEFAULT 'evento',
    titulo      VARCHAR(160) NOT NULL,
    descripcion TEXT         NULL,
    -- interno     → solo el panel (colaboradores)
    -- familias    → además, calendario de /comunidad/familias
    -- estudiantes → además, /comunidad/estudiantes
    audiencia   ENUM('interno','familias','estudiantes') NOT NULL DEFAULT 'interno',
    -- Niveles a los que va dirigido. NULL o vacío = todo el colegio.
    niveles     SET('Maternal','Kinder','Primaria','Secundaria','Bachillerato') NULL,
    creado_en   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_fecha (fecha),
    KEY idx_audiencia (audiencia)
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
    -- Nombre del archivo dentro de storage/justificantes/, NO una URL: el parte
    -- médico vive fuera de public/ y solo se sirve por
    -- /dashboard/suplencias/justificante, que comprueba permisos. Los registros
    -- heredados guardan la ruta pública antigua ('/build/assets/suplencias/…') y
    -- el modelo los sigue resolviendo, pero el shim de index.php ya los deniega.
    justificante        VARCHAR(255) NULL,               -- PDF o imagen de justificación
    -- ── Ciclo de vida del justificante ──
    -- Es un parte médico, así que no se conserva indefinidamente y solo lo abre
    -- DIRECCIÓN (prefectura coordina la ausencia pero no ve el documento).
    -- Contando desde `fecha` (el día de la ausencia):
    --   0-7 días   descargable con normalidad
    --   8-29 días  pasa a la COLA de pendientes: un directivo decide descargarlo
    --              o eliminarlo, y la decisión queda firmada aquí
    --   ≥30 días   se purga automáticamente (Suplencia::purgarJustificantes(),
    --              que dispara la carga del panel; no hay cron en este proyecto)
    -- Se guarda la resolución en vez de solo borrar la ruta para que el histórico
    -- distinga "nunca hubo justificante" de "lo hubo y se resolvió así".
    justificante_subido_en   DATETIME     NULL,
    justificante_resuelto_en DATETIME     NULL,
    justificante_resuelto_por INT UNSIGNED NULL,
    justificante_resolucion  ENUM('descargado','eliminado','purgado') NULL,
    origen              ENUM('anticipada','sin_aviso') NOT NULL DEFAULT 'anticipada',
    estado              ENUM('solicitada','agendada','en_curso','por_justificar','completada','cancelada') NOT NULL DEFAULT 'solicitada',
    creado_por          INT UNSIGNED NULL,
    creado_en           TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_fecha (fecha),
    KEY idx_estado (estado),
    KEY idx_justif_cola (justificante_resuelto_en, fecha),
    CONSTRAINT fk_supl_ausente FOREIGN KEY (profesor_ausente_id) REFERENCES usuarios (id) ON DELETE SET NULL,
    CONSTRAINT fk_supl_creador FOREIGN KEY (creado_por)          REFERENCES usuarios (id) ON DELETE SET NULL,
    CONSTRAINT fk_supl_justres FOREIGN KEY (justificante_resuelto_por) REFERENCES usuarios (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Cobertura por hora: cada periodo a cubrir puede tener su propio suplente y su validación.
CREATE TABLE suplencia_horas (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    suplencia_id INT UNSIGNED NOT NULL,
    periodo_id   INT UNSIGNED NOT NULL,
    grupo_id     INT UNSIGNED NULL,
    aula_id      INT UNSIGNED NULL,
    materia_id   INT UNSIGNED NULL,
    -- Una guardia de receso también se suple. En ese caso grupo/materia van NULL y
    -- manda `lugar_id`, igual que en horarios.
    tipo         ENUM('clase','guardia') NOT NULL DEFAULT 'clase',
    lugar_id     INT UNSIGNED NULL,
    suplente_id  INT UNSIGNED NULL,
    -- 'no_cubierta' = prefectura registró que el suplente asignado no se presentó.
    -- No se confunde con 'pendiente': aquella es una hora que aún nadie ha tomado;
    -- esta es una incidencia con responsable, y cuenta como tal en el tablero.
    estado_hora  ENUM('pendiente','agendada','validada','no_cubierta') NOT NULL DEFAULT 'pendiente',
    validado_en  DATETIME     NULL,
    -- Quién incumplió. Se guarda aparte de `suplente_id` porque al reasignar la hora
    -- a otro profesor `suplente_id` cambia, y el incumplimiento tiene que seguir
    -- imputado a quien no llegó (si no, desaparecería de las estadísticas).
    incumplio_id  INT UNSIGNED NULL,
    incumplido_en DATETIME    NULL,
    -- Marca anti-duplicado del aviso "confirma si cubriste": se emite al entrar
    -- al panel cuando la fecha ya pasó y la hora sigue 'agendada'.
    recordatorio_en DATETIME  NULL,
    -- ── ¿El ausente dejó trabajo para el grupo? ──
    -- Lo registra prefectura al coordinar la ausencia, y alimenta el tablero.
    -- Va en la HORA y no en la suplencia porque un profesor puede dejar material
    -- para su clase de 3º y no para la de 5º; así el dato se cruza con grupo,
    -- materia y nivel (vía periodo_id → periodos.nivel).
    -- NULL = todavía sin revisar · 1 = sí dejó · 0 = no dejó. Los NULL no cuentan
    -- en el porcentaje: "no revisado" no es "no dejó".
    dejo_trabajo  TINYINT(1)   NULL,
    trabajo_notas VARCHAR(255) NULL,
    trabajo_por   INT UNSIGNED NULL,
    trabajo_en    DATETIME     NULL,
    PRIMARY KEY (id),
    KEY idx_suplencia (suplencia_id),
    KEY idx_suplente (suplente_id),
    KEY idx_pendientes (suplente_id, estado_hora),
    KEY idx_trabajo (dejo_trabajo),
    CONSTRAINT fk_sh_suplencia FOREIGN KEY (suplencia_id) REFERENCES suplencias (id) ON DELETE CASCADE,
    CONSTRAINT fk_sh_periodo   FOREIGN KEY (periodo_id)   REFERENCES periodos (id)   ON DELETE CASCADE,
    CONSTRAINT fk_sh_grupo     FOREIGN KEY (grupo_id)     REFERENCES grupos (id)     ON DELETE SET NULL,
    CONSTRAINT fk_sh_aula      FOREIGN KEY (aula_id)      REFERENCES aulas (id)      ON DELETE SET NULL,
    CONSTRAINT fk_sh_materia   FOREIGN KEY (materia_id)   REFERENCES materias (id)   ON DELETE SET NULL,
    CONSTRAINT fk_sh_suplente  FOREIGN KEY (suplente_id)  REFERENCES usuarios (id)   ON DELETE SET NULL,
    CONSTRAINT fk_sh_incumplio FOREIGN KEY (incumplio_id) REFERENCES usuarios (id)   ON DELETE SET NULL,
    CONSTRAINT fk_sh_lugar     FOREIGN KEY (lugar_id)     REFERENCES lugares_guardia (id) ON DELETE SET NULL,
    CONSTRAINT fk_sh_trabajo   FOREIGN KEY (trabajo_por)  REFERENCES usuarios (id)   ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Módulo Swap de clases ─────────────────────────────────────────────────────
-- Intercambio PUNTUAL de dos clases entre dos profesores: no altera el horario
-- permanente, solo dice que el día X la clase de uno la da el otro y viceversa.
-- Por eso guarda pareja (horario_id, fecha) y no solo el horario_id.
--
-- Flujo: solicitante propone → destinatario acepta o rechaza → prefectura o
-- dirección valida. Los tres pasos notifican. El destinatario solo puede ser
-- consultado dentro de una ventana de 7 días desde la fecha que se quiere cubrir,
-- que es lo que limita la búsqueda de huecos en su horario.
--
-- ⚠️ 'aceptado' NO es efectivo: el intercambio solo vale una vez validado. Y
-- prefectura puede abrirlo ella misma, en cuyo caso nace 'validado' y se salta
-- los dos pasos (`creado_por` distinto de `solicitante_id` es lo que lo delata).
CREATE TABLE swap_clases (
    id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    solicitante_id    INT UNSIGNED NOT NULL,
    destinatario_id   INT UNSIGNED NOT NULL,
    -- Clase que el solicitante no puede dar, y el día concreto en que falta.
    horario_origen_id INT UNSIGNED NULL,
    fecha_origen      DATE         NOT NULL,
    -- Clase del destinatario que se ofrece a cambio, y su día.
    horario_destino_id INT UNSIGNED NULL,
    fecha_destino     DATE         NOT NULL,
    motivo            VARCHAR(255) NULL,
    estado            ENUM('pendiente','aceptado','rechazado','validado','denegado','cancelado')
                      NOT NULL DEFAULT 'pendiente',
    -- Las dos notas van SEPARADAS a propósito. Antes compartían columna y la
    -- validación pisaba la respuesta del profesor: el solicitante se quedaba sin
    -- saber por qué le habían dicho que no. Ambas son obligatorias al negar.
    respuesta_nota    VARCHAR(255) NULL,       -- por qué lo rechaza el DESTINATARIO
    validacion_nota   VARCHAR(255) NULL,       -- por qué lo deniega quien COORDINA
    respondido_en     DATETIME     NULL,
    validado_por      INT UNSIGNED NULL,
    validado_en       DATETIME     NULL,
    -- Quién abrió el intercambio. Distinto de `solicitante_id` = lo impuso
    -- prefectura o dirección, y por eso nació ya validado.
    creado_por        INT UNSIGNED NULL,
    creado_en         TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_swap_solicitante (solicitante_id, estado),
    KEY idx_swap_destinatario (destinatario_id, estado),
    KEY idx_swap_estado (estado, fecha_origen),
    CONSTRAINT fk_swap_sol   FOREIGN KEY (solicitante_id)     REFERENCES usuarios (id) ON DELETE CASCADE,
    CONSTRAINT fk_swap_dest  FOREIGN KEY (destinatario_id)    REFERENCES usuarios (id) ON DELETE CASCADE,
    CONSTRAINT fk_swap_horo  FOREIGN KEY (horario_origen_id)  REFERENCES horarios (id) ON DELETE SET NULL,
    CONSTRAINT fk_swap_hord  FOREIGN KEY (horario_destino_id) REFERENCES horarios (id) ON DELETE SET NULL,
    CONSTRAINT fk_swap_val   FOREIGN KEY (validado_por)       REFERENCES usuarios (id) ON DELETE SET NULL,
    CONSTRAINT fk_swap_crea  FOREIGN KEY (creado_por)         REFERENCES usuarios (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Reactivar comprobaciones de FK (al final: ver nota de la cabecera)
SET FOREIGN_KEY_CHECKS = 1;
