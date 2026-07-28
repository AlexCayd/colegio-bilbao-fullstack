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

**⚠️ La BD `colegiobilbao` se comparte con otra aplicación.** Conviven tablas
ajenas (`activos`, `deudas`, `libros`, `videojuegos`, `gym_dias`…). Los `DROP TABLE`
de `database.sql` solo listan las del proyecto, así que es seguro, pero:
- No añadir un `DROP DATABASE` ni un `DROP TABLE` genérico.
- Antes de correr nada destructivo, comprobar qué hay:
  `SELECT table_name FROM information_schema.tables WHERE table_schema='colegiobilbao';`

**Residuo conocido:** existe una tabla `disponibilidad_suplencia` con FKs a
`periodos` y `usuarios`. Es del diseño anterior, no está en `database.sql` y no la
usa ningún modelo. No construir nada sobre ella.

**Reglas de datos que los seeds deben cumplir** (si no, la app se comporta raro):
- `tipo_personal`: `prefecto` es **excluyente**, no se combina con `profesor` ni
  `administrativo`. `profesor,administrativo` sí es válido.
- Solo los `profesor` aparecen como candidatos a suplente.
- `horarios` tiene tres UNIQUE: `(dia, periodo_id, profesor_id)`,
  `(dia, periodo_id, aula_id)` y `(dia, periodo_id, grupo_id)`. Al añadir clases al
  seed hay que buscar huecos libres en las tres dimensiones, no inventar filas.
- Cada profesor del seed debe conservar **≥2 horas libres al día**, o las reglas de
  descanso de `SuplenciaHora::sugerir()` lo dejan fuera de todas las sugerencias.
- `grupos.orden` fija la secuencia académica (Maternal → Bachillerato); ordenar por
  `nivel` saldría alfabético.

---

## Actualizar una BD existente sin perder datos

Lo normal es re-ejecutar los archivos. Si en algún caso hace falta conservar los
datos, se aplica el `ALTER` a mano — **no se crea un archivo para ello**.

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
