# CLAUDE.md — `database/`

Reglas de los archivos SQL del proyecto. Leer antes de tocar cualquier `.sql`.

---

## Regla número uno: solo existen TRES archivos `.sql`

```
database/
├── database.sql                    # ESTRUCTURA  · solo CREATE/DROP TABLE, cero datos
├── credenciales.md                 # cuentas del seed y sus contraseñas
├── CLAUDE.md                       # este archivo
├── deploy/
│   └── deploy.sql                  # PRODUCCIÓN  · solo INSERT de registros reales
└── development/
    └── development.sql             # DESARROLLO  · solo INSERT, datos de prueba
```

**No se crean archivos SQL nuevos.** Ni migraciones, ni parches, ni
`actualizar-x.sql`, ni `fix-y.sql`, ni carpeta `migrations/`. Si el esquema cambia,
se edita `database.sql` y se ajustan los dos archivos de datos para que sigan
cargando. Para actualizar una base existente se **re-ejecutan** los archivos.

Si hace falta documentar un `ALTER` puntual para no perder datos, va **como
comentario en este archivo** (ver «Actualizar una BD existente»), nunca como un
cuarto `.sql`.

---

## Qué va en cada archivo

| Archivo | Contiene | No contiene |
|---------|----------|-------------|
| `database.sql` | `DROP TABLE` + `CREATE TABLE` de todas las tablas, con sus índices y FKs | Ningún `INSERT` |
| `deploy/deploy.sql` | Claustro real, catálogos académicos (`periodos`, `aulas`, `grupos`, `materias`), categorías, tags y artículos publicados | Horarios, suplencias, eventos, noticias ni testimoniales — todo eso es material de ejemplo |
| `development/development.sql` | Todo lo de deploy **más** cuentas de prueba, una semana de horarios, ~90 suplencias, noticias/testimoniales/eventos ficticios | — |

**Por qué `deploy.sql` no lleva horarios ni suplencias:** son datos operativos que
en producción genera el propio panel. Los horarios entran por CSV desde
`/dashboard/horarios/importar`; las suplencias las abren prefectura y el claustro.
Sembrarlos ensuciaría la base real.

**`development.sql` es un superconjunto de `deploy.sql`** en las tablas comunes:
los mismos ids de usuario, las mismas categorías, los mismos catálogos. Si cambias
un usuario real o un aula, cámbialo en **los dos** o se desincronizan.

---

## Orden de ejecución

Siempre la estructura primero y luego **un solo** archivo de datos:

```bash
# Desarrollo
mysql -u root -p colegiobilbao < database/database.sql
mysql -u root -p colegiobilbao < database/development/development.sql

# Producción
mysql -u root -p colegiobilbao < database/database.sql
mysql -u root -p colegiobilbao < database/deploy/deploy.sql
```

Nunca se cargan los dos archivos de datos seguidos: comparten ids y chocarían.

---

## Invariantes que hay que respetar al editar

**⚠️ `database.sql` reactiva las FK en la ÚLTIMA línea, no arriba.** El archivo abre
con `SET FOREIGN_KEY_CHECKS = 0` seguido de los `DROP TABLE`, y solo vuelve a
`= 1` al final. Si se reactivan antes de los `CREATE`, cualquier FK ajena que
apunte a estas tablas aborta el script a mitad, dejando las tablas ya borradas.
**No mover esa línea.**

**La BD `colegiobilbao` ya NO tiene tablas ajenas** (verificado el 2026-08-07: solo
están las del proyecto). Tampoco queda el residuo `disponibilidad_suplencia`. Aun
así, antes de correr nada destructivo conviene mirar qué hay:
`SELECT table_name FROM information_schema.tables WHERE table_schema='colegiobilbao';`
— y nunca añadir un `DROP DATABASE` ni un `DROP TABLE` genérico.

**Reglas de datos que los seeds deben cumplir** (si no, la app se comporta raro):
- `tipo_personal`: `prefecto` y `directivo` son **excluyentes** y tampoco se combinan
  entre sí. `profesor,administrativo` sí es válido. Lo impone
  `UsuarioBlog::normalizarTipoPersonal()` con `TIPOS_EXCLUYENTES`.
- Solo los `profesor` aparecen como candidatos a suplente.
- `usuarios.modulos`: el CSV debe salir de `UsuarioBlog::MODULOS_ASIGNABLES`. Cualquier clave
  que no esté ahí la descarta `normalizarModulos()` en el siguiente guardado. ⚠️ Añadir un
  módulo nuevo a esa constante **no** lo mete en las BD ya cargadas: hay que re-ejecutar el
  seed o marcarlo en el formulario de usuarios. El admin no se ve afectado (accede a todos).
- **Acompañantes de coteaching:** una fila con `rol_docente='acompanante'` necesita un titular
  en su mismo bloque (`dia`, `periodo_id`, `grupo_id`, `materia_id`, `division`). Una sin él es
  huérfana: no aparece en el editor —así que nadie puede borrarla— pero le sigue ocupando la
  hora a esa persona.
- **La jornada es POR NIVEL.** `periodos` tiene `nivel` y `UNIQUE (nivel, orden)`: cada
  nivel entra, sale y descansa a su hora. Un `periodo_id` pertenece siempre a un nivel, y
  `horarios.periodo_id` debe ser del **mismo nivel que su `grupo_id`**.
- **⚠️ `horarios` ya NO tiene UNIQUE de profesor, aula ni grupo**, y es deliberado: el
  horario real del colegio tiene tres situaciones que los hacían imposibles.
  Queda solo `uq_clase (dia, periodo_id, profesor_id, grupo_id, division)`.

  | Situación | Qué comparte | Qué cambia |
  |---|---|---|
  | **Coteaching** (`Dulce\Laura` en el PDF) | día, periodo, grupo | `profesor_id`, `rol_docente` |
  | **Materia dividida** (1ºA Sec: Arte *y* Música a la vez) | día, periodo, grupo | `division` (1..n) |
  | **Clase conjunta** (6ºA+6ºB juntos en Ecología) | día, periodo, profesor, aula | `grupo_id` |

  Al sembrar horarios hay que comprobar **solapamiento por reloj** en las tres
  dimensiones, exceptuando esos tres casos — es exactamente lo que hace
  `Horario::choques()` cuando se le pasa el contexto `materia_id` + `division`.
- **Las guardias de receso viven en `horarios`** con `tipo = 'guardia'`, `lugar_id`
  y `grupo_id`/`materia_id` a NULL, sobre un periodo con `es_receso = 1`. No es un
  atajo: así `ocupacionDiaDeVarios()`, `libreEn()` y el algoritmo de suplencias las
  cuentan como ocupación sin ningún caso especial, y una guardia se puede suplir
  igual que una clase. Una guardia NUNCA debe caer sobre una hora de clase, ni una
  clase sobre un receso.
- Cada profesor del seed debe conservar **más de `SuplenciaHora::DESCANSO_MIN` (40) minutos
  libres al día**, o las reglas de descanso de `sugerir()` lo dejan fuera de todas las
  sugerencias. La regla va en minutos, no en bloques: un bloque dura 45' en
  Maternal/Kinder y 50' en el resto, así que "una hora libre" no es comparable.
- El seed debe conservar **algún profesor que dé clase en dos niveles**. Sin eso, el caso
  que justifica todo el diseño (jornadas desfasadas) queda sin ejercitar. Hoy son 6.
- `usuarios.niveles` debe cuadrar con las clases de cada profesor: es lo que acota el eje
  de su rejilla. Si no cuadra el panel lo avisa, pero el seed no debería estrenarse con
  discrepancias. ⚠️ Lo llevan **`profesor` y `directivo`**, con significados distintos
  (imparte / gestiona — ver «Actualizar una BD existente»); en el resto va `NULL`.
- El orden académico (Maternal → Bachillerato) sale de `Materia::ordenNivel()`; ordenar
  por `nivel` a secas saldría alfabético.

---

## Actualizar una BD existente sin perder datos

Lo normal es re-ejecutar los archivos. Si en algún caso hace falta conservar los
datos, se aplica el `ALTER` a mano — **no se crea un archivo para ello**.

**Eliminación del rol `superadmin` (julio 2026).** El orden importa: primero el
`UPDATE`, después el `MODIFY`. Al revés, MySQL trunca los `superadmin` a cadena
vacía al reducir el ENUM y esas cuentas se quedan sin rol.

```sql
UPDATE usuarios SET rol = 'administrador' WHERE rol = 'superadmin';
ALTER TABLE usuarios
    MODIFY COLUMN rol ENUM('administrador','usuario') NOT NULL DEFAULT 'usuario';
```

**Catálogos simplificados (julio 2026).** `aulas.descripcion` no aportaba nada al
horario y `grupos.orden` había que mantenerlo a mano; el orden se deduce ya del
nivel + el nombre (ver `Grupo::todos()`).

```sql
ALTER TABLE aulas  DROP COLUMN descripcion;
ALTER TABLE grupos DROP COLUMN orden;
```

**Nivel declarado del profesor (agosto 2026).** `usuarios.niveles` pasa a ser la fuente
declarativa de en qué niveles imparte cada profesor; antes solo se deducía de sus clases.

```sql
ALTER TABLE usuarios
    ADD COLUMN niveles SET('Maternal','Kinder','Primaria','Secundaria','Bachillerato') NULL
        AFTER tipo_personal;

-- Poblarla desde el horario ya cargado. Solo profesores: el campo no aplica a
-- prefectura ni a administrativos, y UsuarioBlog::normalizarNiveles() lo fuerza a NULL.
UPDATE usuarios u
   JOIN (SELECT h.profesor_id pid,
                GROUP_CONCAT(DISTINCT p.nivel
                    ORDER BY FIELD(p.nivel,'Maternal','Kinder','Primaria','Secundaria','Bachillerato')) niv
           FROM horarios h JOIN periodos p ON p.id = h.periodo_id
          GROUP BY h.profesor_id) x ON x.pid = u.id
    SET u.niveles = x.niv
  WHERE FIND_IN_SET('profesor', u.tipo_personal);
```

Comprobaciones (las dos deben dar **0**):

```sql
-- (a) nadie declara un nivel que no imparte ni imparte uno que no declara
SELECT COUNT(*) FROM (SELECT u.id FROM usuarios u
   JOIN horarios h ON h.profesor_id = u.id JOIN periodos p ON p.id = h.periodo_id
  WHERE NOT FIND_IN_SET(p.nivel, u.niveles) GROUP BY u.id) x;
-- (b) ningún no-profesor tiene niveles
SELECT COUNT(*) FROM usuarios
 WHERE niveles IS NOT NULL AND NOT FIND_IN_SET('profesor', tipo_personal);
```

La (a) puede dar >0 sin ser un error: significa que ese profesor da clase en un nivel que
no consta en su ficha. El panel lo muestra igual, marcado, con un aviso sobre la rejilla.

**Color por bloque de horario (agosto 2026).** El editor de horarios del módulo Usuarios
permite elegir el color de cada clase. `NULL` = automático, derivado del nombre de la
materia por `BlogController::colorMateria()` — que es como se pintaba el 100% de la
rejilla antes de que la columna existiera, así que las filas ya cargadas no cambian.

```sql
ALTER TABLE horarios ADD COLUMN color CHAR(7) NULL AFTER materia_id;
```

> ⚠️ **El CSV de horarios no transporta el color** (sus 7 columnas no lo contemplan) y
> una importación reemplaza el horario completo de los profesores del archivo. Para que
> reimportar no borre en silencio los colores elegidos a mano,
> `BlogController::importarHorarios()` fotografía `(profesor_id, dia, periodo_id) → color`
> antes del `DELETE` y lo vuelca en las filas nuevas que caigan en la misma casilla.

**Jornada por nivel (agosto 2026).** Antes `periodos` era una sola jornada para todo el
colegio (8 bloques, 07:00–13:20). Ahora cada nivel tiene la suya. El orden importa:
primero la columna, luego las jornadas nuevas, y **al final** el remapeo de los datos ya
cargados.

```sql
-- ⚠️ La BD conserva el residuo `disponibilidad_suplencia` con FK a periodos: sin esto,
--    tocar periodos aborta.
SET FOREIGN_KEY_CHECKS = 0;

-- 1. Columna + índice. La jornada cargada hoy es única para todo el colegio: se la
--    ancla a 'Secundaria' (la que conserva sus horas) y los otros cuatro niveles se
--    crean en el paso 3.
ALTER TABLE periodos
    ADD COLUMN nivel ENUM('Maternal','Kinder','Primaria','Secundaria','Bachillerato')
        NOT NULL DEFAULT 'Secundaria' AFTER id,
    DROP INDEX uq_orden,
    ADD UNIQUE KEY uq_nivel_orden (nivel, orden);
ALTER TABLE periodos ALTER COLUMN nivel DROP DEFAULT;

-- 2. Índice que sirve Horario::ocupacionDiaDeVarios()
ALTER TABLE horarios DROP INDEX idx_prof, ADD KEY idx_prof_dia (profesor_id, dia);

-- 3. Jornadas de los otros cuatro niveles: copiar el bloque INSERT INTO `periodos`
--    de database/deploy/deploy.sql, saltándose las filas de Secundaria.

-- 4. Reapuntar lo ya cargado. Se remapea POR RELOJ, no por `orden`: un mismo `orden` ya
--    no significa la misma hora en dos niveles. El ORDER BY por minutos solapados es lo
--    que lo hace determinista cuando un bloque viejo de 50' pisa dos nuevos de 45'.
UPDATE horarios h
  JOIN periodos v ON v.id = h.periodo_id
  JOIN grupos   g ON g.id = h.grupo_id
SET h.periodo_id = (
      SELECT n.id FROM periodos n
       WHERE n.nivel = g.nivel AND n.es_receso = 0
         AND n.hora_inicio < v.hora_fin AND n.hora_fin > v.hora_inicio
       ORDER BY TIME_TO_SEC(LEAST(n.hora_fin, v.hora_fin))
              - TIME_TO_SEC(GREATEST(n.hora_inicio, v.hora_inicio)) DESC, n.orden ASC
       LIMIT 1)
WHERE v.nivel <> g.nivel
  AND EXISTS (SELECT 1 FROM periodos n WHERE n.nivel = g.nivel AND n.es_receso = 0
               AND n.hora_inicio < v.hora_fin AND n.hora_fin > v.hora_inicio);
-- …y lo mismo para suplencia_horas (el nivel sale de su grupo_id).

-- 5. Lo que ningún bloque del nivel llega a cubrir (una clase de Maternal a las 12:30).
DELETE h FROM horarios h JOIN periodos p ON p.id=h.periodo_id JOIN grupos g ON g.id=h.grupo_id
 WHERE p.nivel <> g.nivel;

-- 6. Solapes heredados: se conserva la fila de id menor. UNA pasada basta, porque toda
--    fila que choque con otra de id menor cae en esta misma sentencia.
DELETE b FROM horarios a
  JOIN horarios b  ON b.dia = a.dia AND b.id > a.id
                  AND (b.profesor_id = a.profesor_id
                       OR (b.aula_id  IS NOT NULL AND b.aula_id  = a.aula_id)
                       OR (b.grupo_id IS NOT NULL AND b.grupo_id = a.grupo_id))
  JOIN periodos pa ON pa.id = a.periodo_id
  JOIN periodos pb ON pb.id = b.periodo_id
 WHERE pa.hora_inicio < pb.hora_fin AND pb.hora_inicio < pa.hora_fin;

SET FOREIGN_KEY_CHECKS = 1;
```

Comprobaciones obligatorias después. **Las seis deben dar 0** — están escritas ya con
las excepciones de coteaching, materia dividida y clase conjunta, así que un resultado
distinto de cero es un choque real:

```sql
-- (a) ninguna clase apunta a un periodo de otro nivel
SELECT COUNT(*) FROM horarios h JOIN periodos p ON p.id=h.periodo_id
  JOIN grupos g ON g.id=h.grupo_id WHERE h.tipo='clase' AND p.nivel <> g.nivel;

-- (b) ninguna clase cae sobre un receso
SELECT COUNT(*) FROM horarios h JOIN periodos p ON p.id=h.periodo_id
 WHERE h.tipo='clase' AND p.es_receso=1;

-- (c) ninguna guardia cae fuera de un receso
SELECT COUNT(*) FROM horarios h JOIN periodos p ON p.id=h.periodo_id
 WHERE h.tipo='guardia' AND p.es_receso=0;

-- (d) choque real de profesor (misma materia + aula = la misma clase real: coteaching
--     o clase conjunta, y esas conviven)
SELECT COUNT(*) FROM horarios a
  JOIN horarios b  ON b.id>a.id AND b.dia=a.dia AND b.profesor_id=a.profesor_id
  JOIN periodos pa ON pa.id=a.periodo_id JOIN periodos pb ON pb.id=b.periodo_id
 WHERE pa.hora_inicio<pb.hora_fin AND pb.hora_inicio<pa.hora_fin
   AND NOT (a.materia_id<=>b.materia_id AND a.aula_id<=>b.aula_id AND a.tipo=b.tipo);

-- (e) choque real de grupo (dos opciones de una materia dividida sí conviven, y la
--     misma materia con otro docente es coteaching)
SELECT COUNT(*) FROM horarios a
  JOIN horarios b  ON b.id>a.id AND b.dia=a.dia AND b.grupo_id=a.grupo_id
  JOIN periodos pa ON pa.id=a.periodo_id JOIN periodos pb ON pb.id=b.periodo_id
 WHERE pa.hora_inicio<pb.hora_fin AND pb.hora_inicio<pa.hora_fin
   AND NOT (a.division>0 AND b.division>0 AND a.division<>b.division)
   AND NOT (a.materia_id<=>b.materia_id AND a.profesor_id<>b.profesor_id);

-- (f) ningún acompañante sin titular en su bloque (fila huérfana: no sale en el editor,
--     así que nadie puede borrarla, pero le ocupa la hora a esa persona)
SELECT COUNT(*) FROM horarios a WHERE a.rol_docente='acompanante' AND NOT EXISTS (
  SELECT 1 FROM horarios t WHERE t.rol_docente='titular' AND t.dia=a.dia
    AND t.periodo_id=a.periodo_id AND t.division=a.division
    AND t.grupo_id<=>a.grupo_id AND t.materia_id<=>a.materia_id);
```

**Trabajo dejado, notas de swap y direcciones por nivel (agosto 2026).** Tres cambios
independientes; ninguno destruye datos.

```sql
-- 1. ¿El profesor ausente dejó trabajo para el grupo? Lo marca prefectura, y de aquí
--    sale el porcentaje del tablero. Va en la HORA y no en la suplencia: puede haber
--    dejado material para 3º y no para 5º.
--    ⚠️ NULL ≠ 0. NULL es "todavía sin revisar" y queda FUERA del denominador del
--    porcentaje; 0 es "no dejó". Por eso la columna admite null y no lleva DEFAULT.
ALTER TABLE suplencia_horas
    ADD COLUMN dejo_trabajo  TINYINT(1)   NULL AFTER recordatorio_en,
    ADD COLUMN trabajo_notas VARCHAR(255) NULL AFTER dejo_trabajo,
    ADD COLUMN trabajo_por   INT UNSIGNED NULL AFTER trabajo_notas,
    ADD COLUMN trabajo_en    DATETIME     NULL AFTER trabajo_por,
    ADD KEY idx_trabajo (dejo_trabajo),
    ADD CONSTRAINT fk_sh_trabajo FOREIGN KEY (trabajo_por) REFERENCES usuarios (id) ON DELETE SET NULL;

-- 2. Swaps: la nota de la validación deja de pisar la respuesta del profesor.
--    Antes las dos compartían `respuesta_nota` y el solicitante se quedaba sin saber
--    quién había dicho qué. `creado_por` distinto de `solicitante_id` = lo impuso
--    prefectura, y por eso nació ya validado.
ALTER TABLE swap_clases
    ADD COLUMN validacion_nota VARCHAR(255) NULL AFTER respuesta_nota,
    ADD COLUMN creado_por      INT UNSIGNED NULL AFTER validado_en,
    ADD CONSTRAINT fk_swap_crea FOREIGN KEY (creado_por) REFERENCES usuarios (id) ON DELETE SET NULL;

-- Los swaps ya existentes los abrió su solicitante.
UPDATE swap_clases SET creado_por = solicitante_id WHERE creado_por IS NULL;

-- 3. Direcciones por nivel. Cero DDL: `usuarios.niveles` ya existe con el SET correcto.
--    Solo cambia QUIÉN puede llevarla (ver la nota del doble significado, abajo) y se
--    añaden las cinco cuentas. La 72 se queda SIN niveles = todo el colegio.
INSERT INTO usuarios (id, nombre, email, password, rol, rol_redaccion, tipo_personal,
                      niveles, puede_suplir, modulos, fecha_nacimiento, avatar,
                      ultimo_acceso, creado_en)
SELECT 73, 'Dirección de Maternal', 'direccion.maternal@bilbao.edu.mx', password,
       'usuario', NULL, 'directivo', 'Maternal', 1, modulos, NULL, '', NULL, NOW()
  FROM usuarios WHERE id = 72;
-- …y lo mismo con 74/Kinder, 75/Primaria, 76/Secundaria, 77/Bachillerato.
-- (Copiar `password` y `modulos` de la 72 mantiene las seis alineadas: lo que las
--  distingue es el alcance de datos, no los permisos de módulo.)
```

⚠️ **`usuarios.niveles` significa dos cosas** desde este cambio, y el invariante de más
arriba («solo los `profesor` lo llevan») ya no vale tal cual:

| `tipo_personal` | Qué significa `niveles` | Vacío significa |
|---|---|---|
| `profesor` | los niveles que **imparte** | se deducen de sus clases |
| `directivo` | los niveles que **gestiona** | **todo el colegio** |

No hay ambigüedad posible en una fila porque `directivo` es excluyente. Quien mantiene
separados los dos sentidos es `BlogController::nivelesAlcance()`, que comprueba el
**tipo** y no la columna — invertir eso abriría una vía de escalada por BD.
La comprobación (b) de arriba pasa a ser:

```sql
-- (b) ningún puesto que no sea profesor ni directivo tiene niveles → debe dar 0
SELECT COUNT(*) FROM usuarios
 WHERE niveles IS NOT NULL
   AND NOT FIND_IN_SET('profesor',  tipo_personal)
   AND NOT FIND_IN_SET('directivo', tipo_personal);
```

**Justificantes fuera de `public/` (agosto 2026).** Sin DDL, pero hay que **mover los
archivos**: `suplencias.justificante` pasa de guardar una URL pública
(`/build/assets/suplencias/x.pdf`) a guardar solo el nombre. El modelo sigue resolviendo
los registros heredados, así que la migración de datos es opcional; lo que **no** es
opcional es sacar los archivos de la carpeta pública.

```sh
mkdir -p storage/justificantes
mv public/build/assets/suplencias/* storage/justificantes/ 2>/dev/null
```

```sql
-- Opcional, para que los registros viejos guarden solo el nombre como los nuevos.
UPDATE suplencias
   SET justificante = SUBSTRING_INDEX(justificante, '/', -1)
 WHERE justificante LIKE '/build/%';
```

> El shim de `index.php` devuelve **404** en `/build/assets/suplencias/*`, así que
> cualquier archivo que quede ahí deja de ser descargable aunque no se mueva. La única
> puerta es `/dashboard/suplencias/justificante?id=N`, que comprueba permisos
> (dirección, o el propio ausente).
>
> ⚠️ En Apache ese portazo hay que **repetirlo en el `.htaccess`**, y por encima de la
> regla que atajа `/build/` hacia `public/build/`: al atajarla, el shim de `index.php`
> deja de correr para esas URLs y con él su 404.

**Justificantes y trabajo dejado en el seed (agosto 2026).** Dos huecos de datos que hacían
imposible probar sus flujos, ambos ya corregidos en `development.sql`:

- Las 25 filas con `justificante` apuntaban todas al literal
  `/build/assets/suplencias/ejemplo-justificante.pdf`, **que no existe en disco**:
  `rutaJustificante()` devolvía `null`, `infoJustificante()` también, y la cola entera salía
  como «Archivo no encontrado» con el botón de descarga dando 404. Ahora el ciclo lo cubren
  **siete** suplencias (ids 93-99) que apuntan a los `storage/justificantes/ejemplo-*.txt`
  —documentos ficticios en texto plano, versionados como excepción en `.gitignore`— en el
  **formato nuevo**: nombre suelto, no ruta pública. Tres `vigente`, tres `en_cola` y una
  `resuelto` (id 96: `justificante` a `NULL` + la firma `_resuelto_en`/`_por`/`_resolucion`),
  que era el estado que ninguna vista sabía pintar y que no había forma de ver sin provocarlo
  a mano.

  > ⚠️ **DOS REGLAS que no se pueden saltar al sembrar un justificante**, las dos aprendidas
  > por las malas:
  >
  > 1. **Solo fechas RELATIVAS.** `purgarJustificantes()` corre en **cada** carga de
  >    `/dashboard` y hace `unlink()` de todo lo que pase de `DIAS_PURGA` (30 días). Las
  >    suplencias de fecha fija del seed son de febrero a junio, o sea purgables desde el
  >    primer login: darles un justificante modela algo que no puede existir **y borra el
  >    archivo del repositorio**. Por eso ahí la columna va en `NULL`.
  > 2. **Un archivo por suplencia, nunca compartido.** `aprobarJustificante()` y
  >    `resolverJustificante()` borran el fichero. Si dos filas apuntan al mismo, aprobar una
  >    deja a la otra en `is-broken` y el archivo perdido del repo.
  >
  > Comprobación (las dos deben dar **0**):
  > ```sql
  > SELECT COUNT(*) FROM suplencias
  >  WHERE justificante IS NOT NULL AND fecha < CURDATE() - INTERVAL 30 DAY;
  > SELECT COUNT(*) FROM (SELECT justificante FROM suplencias
  >   WHERE justificante IS NOT NULL GROUP BY justificante HAVING COUNT(*) > 1) x;
  > ```
- `suplencia_horas` no incluía `dejo_trabajo` en su `INSERT`, así que las 172 horas quedaban a
  `NULL`: el tablero arrancaba con 0 %, el panel de «ausencias sin trabajo» vacío y la cola de
  prefectura con todo pendiente. Ahora la columna va en el `INSERT` con un reparto
  **determinista** (~55 % `1`, ~20 % `0`, ~25 % `NULL`) — nada de aleatorio: el seed tiene que
  dar el mismo resultado en cada carga.

> Los prefectos del seed pasaron a llevar también el módulo **`profesores`**, que es lo que
> `UsuarioBlog::MODULOS_SUGERIDOS['prefecto']` ya declaraba y el seed no reflejaba. Sin él no
> pueden abrir el directorio ni la ficha de un colaborador.
> ⚠️ `deploy.sql` **no tiene ninguna fila de prefectura** (57 usuarios: 48 profesores, 1
> profesor+administrativo, 6 directivos). No es efecto de este cambio, pero conviene saberlo
> antes de dar por hecho que el flujo de prefectura existe en producción.

Ejemplo, el cambio de `notificaciones` a avisos transversales (julio 2026):

```sql
ALTER TABLE notificaciones
    ADD COLUMN modulo VARCHAR(40) NOT NULL DEFAULT 'general' AFTER usuario_id,
    ADD COLUMN nivel  ENUM('info','exito','aviso','error') NOT NULL DEFAULT 'info' AFTER tipo,
    ADD COLUMN enlace VARCHAR(255) NULL AFTER referencia_tipo,
    MODIFY COLUMN referencia_tipo VARCHAR(40) NULL;

UPDATE notificaciones
SET modulo = 'redaccion',
    nivel  = IF(tipo LIKE '%rechaz%', 'aviso', 'exito'),
    enlace = CONCAT('/dashboard/', referencia_tipo, 's/editar?id=', referencia_id)
WHERE referencia_tipo IS NOT NULL;
```

---

## Comprobar que un cambio no rompe nada

Cargar los archivos en una base desechable antes de tocar la real:

```bash
mysql -u root -e "DROP DATABASE IF EXISTS cb_test; CREATE DATABASE cb_test CHARACTER SET utf8mb4;"
mysql -u root cb_test < database/database.sql
mysql -u root cb_test < database/development/development.sql   # o deploy.sql
mysql -u root cb_test -e "SELECT COUNT(*) FROM usuarios;"
mysql -u root -e "DROP DATABASE cb_test;"
```

Los dos archivos de datos deben cargar **sin un solo warning** sobre el
`database.sql` actual. Si uno falla, el esquema y el seed se han desincronizado.
