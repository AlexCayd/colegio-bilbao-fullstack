# CLAUDE.md — Colegio Bilbao Fullstack

Guía de contexto para Claude Code. Leer antes de tocar cualquier archivo.

---

## Qué es este proyecto

Sitio web institucional del **Colegio Bilbao** (colegio privado, México) + panel de administración de blog. PHP 8 MVC custom (sin Laravel/Symfony), MySQL, SCSS compilado con Gulp, desplegado en **Hostinger (Apache + PHP-FPM, Linux)**.

---

## Arquitectura

### Entry point y routing

- `index.php` — dispatcher principal; sirve también assets estáticos de `/build/` hacia `public/build/`
- `Router.php` — router custom; soporta `get()`, `post()`, patrones con `{param}`
- `includes/app.php` — bootstrap: inicia sesión, carga Dotenv, conecta BD
- `dev-server.php` — **router file obligatorio** para `php -S` (ver abajo)
- `.htaccess` — reparto en producción (Apache); `.user.ini` — límites de PHP

**⚠️ En local hay que arrancar con `php -S localhost:3000 dev-server.php`.** Sin el router file,
el servidor embebido devuelve 404 en todo `/build/*`: esos assets no existen físicamente (los sirve
un shim dentro de `index.php`), y el servidor solo cae al front controller cuando la URI *no* parece
un archivo — `/build/css/app.css` tiene extensión, así que nunca llega. Resultado: el sitio se pinta
sin CSS ni JS. `dev-server.php` replica la condición «archivo real» del `.htaccess`, veta las carpetas
sensibles (`includes/`, `vendor/`, `database/`, dotfiles) y manda todo lo demás a `index.php`.

> No renombrarlo a `router.php`: en Windows el FS es case-insensitive y chocaría con `Router.php`.
> En producción no se usa: el reparto lo hace `.htaccess`.

> Estuvo mucho tiempo sin existir en el repo (un clon limpio abortaba con *Failed opening
> required*). Ya está creado.

### ⚠️ Producción es Apache sobre Linux, no IIS

El proyecto **se desplegó en IIS** hasta agosto de 2026 y varias decisiones vienen de ahí. Lo que
cambió al pasar a Hostinger:

| Antes (IIS) | Ahora (Apache) |
|---|---|
| `web.config` (URL Rewrite) | **`.htaccess`** — `web.config` se eliminó |
| `maxAllowedContentLength` | **`.user.ini`** (`upload_max_filesize`, `post_max_size`, …) |
| Permisos a `IIS_IUSRS` | `chmod`/propietario del usuario FTP |
| FS case-insensitive | **FS case-sensitive** |

**El `.htaccess` NO es una traducción literal del `web.config`.** IIS bloqueaba las carpetas de
código por configuración del servidor; Apache no. Sin las reglas de denegación, un
`GET /includes/.env` o `/database/credenciales.md` se sirve **como texto plano**, porque el
catch-all `!-f` no los captura justamente por ser archivos reales. La lista de carpetas vetadas
está duplicada a propósito en `.htaccess` y `dev-server.php:22` — **si se toca una, tocar la otra**.

⚠️ **El orden de las reglas del `.htaccess` importa.** El atajo `^build/(.*)$ → public/build/$1`
hace que el shim PHP de `index.php` **deje de correr** para esas URLs, y con él su portazo 404 a
`assets/suplencias/` (justificantes heredados, § *Justificantes*). Por eso ese 404 se repite en el
`.htaccess` **antes** del atajo. Mover una regla por encima de la otra reabre los partes médicos.

⚠️ **Linux distingue mayúsculas.** Dos rutas de imagen (`Alex-espera.png`, `Alex-dice.png`) daban
404 solo en producción. Verificado que las 143 referencias a `/build/assets/` resuelven; al añadir
una imagen, respetar el nombre exacto del archivo.

**Necesitan permiso de escritura**, y no solo las carpetas de subidas: `storage/justificantes/`
(partes médicos) y `storage/fuentes-pdf/` (caché `.ufm` que genera Dompdf solo). Sin la segunda,
«Mi horario» en PDF falla.

### MVC custom

```
Controllers/  → clases estáticas; reciben $router, llaman a $router->render()
Models/       → heredan de ActiveRecord; mapeo ORM con MySQLi
views/        → templates PHP; no lógica de negocio
```

**No hay framework externo de routing ni ORM de terceros.** Todo es código propio.

### Layouts

| Método | Layout usado |
|--------|-------------|
| `$router->render('vista', $datos)` | `views/layout.php` (sitio público) |
| `$router->renderAdmin('vista', $datos)` | `views/layout-admin.php` |
| `$router->renderBlog('vista', $datos)` | HTML mínimo para blog público/auth |

### Namespaces (PSR-4)

```
MVC\         → raíz del proyecto (Router, etc.)
Controllers\ → ./controllers/
Model\       → ./models/
Classes\     → ./classes/
```

---

## Base de datos

Conexión MySQLi en `includes/database.php`, instancia global via `ActiveRecord::$db`.

**Tablas del blog:** `articulos`, `categorias`, `tags`, `articulo_tags`, `usuarios` (UsuarioBlog)  
**Tablas del sitio:** `usuarios` (Usuario — registro/auth público)

**`usuarios.niveles`** (`SET` de los cinco niveles) sirve a **dos puestos**, y significa una cosa
distinta en cada uno. `UsuarioBlog::normalizarNiveles()` lo fuerza a `NULL` en cualquier otro caso,
así que un POST manipulado no se lo cuela a un prefecto:

| `tipo_personal` | Qué declara | Vacío significa |
|---|---|---|
| `profesor` | los niveles que **imparte** | se infieren de sus clases |
| `directivo` | los niveles que **gestiona** | **todo el colegio** |

En un profesor es la fuente **declarativa** de su nivel —antes se deducía siempre de sus clases, lo
que fallaba en cuanto no tenía horario cargado (de ahí los `?: Materia::NIVELES` de respaldo)— y
sirve para acotar el eje de su rejilla y priorizar candidatos en las suplencias. En un directivo es
el **alcance de sus datos** (§ *Direcciones por nivel*).

> ⚠️ Los dos sentidos **no pueden coexistir en una fila**: `directivo` es excluyente y
> `normalizarTipoPersonal()` —que corre justo antes— ya colapsó el SET. Quien los mantiene separados
> en el código es `BlogController::nivelesAlcance()`, que comprueba el **tipo** y no la columna:
> invertir eso dejaría que un `UPDATE` directo sobre un profesor le diera alcance de gestión.
> Marcar los cinco niveles se normaliza a `NULL` — para quien lee el alcance son la misma cosa.

**Tabla `usuarios` (panel) — columnas clave:** `rol ENUM('administrador','usuario')`,
`modulos VARCHAR(255)` (CSV de módulos para rol `usuario`, ej. `redaccion,suplencias`; NULL para admin),
`fecha_nacimiento DATE` (calendario de cumpleaños). La `fecha_nacimiento` **no** está en `$columnasDB`
del modelo: se persiste con `UsuarioBlog::guardarFechaNacimiento()` para poder guardar `NULL` real
(el ActiveRecord base envuelve todos los valores en comillas y no soporta NULL). `UsuarioBlog::buscar()`
alimenta el autocompletado de colaboradores.

**Tabla `suplencias`:** `fecha`, `ausente_id`/`suplente_id` (FK a `usuarios`, ON DELETE SET NULL),
`grupo`, `materia`, `motivo`, `notas`, `estado ENUM('pendiente','confirmada','cancelada')`. El modelo
`Suplencia` **sobrescribe `guardar()`** con soporte de NULL real (suplente sin asignar, FKs nulas).
`motivo` sigue siendo VARCHAR libre, pero el formulario ofrece el catálogo `Suplencia::MOTIVOS` + "Otro";
`Suplencia::motivoDesdePost()` consolida ambos campos y `validar()` rechaza "Otro" sin describir.

**No existe tabla de disponibilidad.** Un profesor no declara en qué horas acepta suplir: la
disponibilidad **se deduce de las horas libres de su horario**. El descarte lo hacen las reglas de
`SuplenciaHora::sugerir()` (clase solapada / ya cubre una hora solapada / debe conservar
`DESCANSO_MIN` minutos libres / equidad) más `usuarios.puede_suplir`, que ya filtra
`UsuarioBlog::candidatosSuplencia()`. `sugerir()` cuesta **6 consultas fijas**, no una por
candidato: la ocupación de todo el claustro sale de `Horario::ocupacionDiaDeVarios()`.

**Descanso en minutos, no en bloques.** `SuplenciaHora::DESCANSO_MIN` (= 40) son los minutos
libres que un suplente debe conservar tras aceptar la cobertura. Va en minutos porque un
bloque dura 45' en Maternal/Kinder y 50' en el resto: "una hora libre" no es comparable
entre jornadas. Los minutos de jornada se calculan sobre la **unión** de los niveles en que
el profesor da clase ese día (unión y no suma: dos niveles se solapan). El campo
`horas_libres` del JSON sigue existiendo, pero es **solo presentación** — bloques libres de
su nivel dominante— y no decide nada.

**Las salvedades avisan, no bloquean.** `sugerir()` devuelve `avisos: [{tipo, texto}]` —una
lista, porque pueden concurrir— y la UI pinta un chip ámbar por cada una sin quitar al
candidato de los elegibles. Hoy solo hay **un** tipo:

- `nivel`: consta que **no** imparte el nivel de esa clase.

> ⚠️ Hubo un segundo tipo, `receso` («la hora a cubrir cae en su receso»), y se **retiró**:
> con jornada por nivel el receso de uno es hora de clase de otro, así que saltaba en casi
> todos los candidatos y dejó de significar nada. El dato sigue donde importa —la rejilla de
> previsualización pinta ese tramo en ámbar (`is-break`) dentro del horario real del
> candidato—, pero ya no hay chip en la fila ni banner sobre el botón de confirmar.
> Con él se fue `SuplenciaHora::recesosPorNivel()`, que no usaba nadie más.

**Prioridad por nivel, no filtro.** `afinidad` tiene tres grados —**2** consta que imparte ese
nivel · **1** sin datos · **0** consta que imparte otros— y ordena a los elegibles **por delante
de la equidad**: para cubrir Primaria, un profesor de Primaria pasa antes aunque acumule alguna
cobertura más. No se filtra en duro porque dejaría a prefectura sin candidatos en los niveles
pequeños, el mismo problema que resolvió `MARGEN_EQUIDAD`. El grado 1 existe para no penalizar a
quien todavía no tiene ni niveles declarados ni horario: «no sabemos» no es «sabemos que no».

> ⚠️ La BD de desarrollo conserva una tabla `disponibilidad_suplencia` con FKs a `periodos` y
> `usuarios`. Es un **residuo del diseño anterior**: no está en `database.sql` ni la usa ningún
> modelo. No construir nada sobre ella.

**Solo los `profesor` cubren suplencias.** `candidatosSuplencia()` filtra por
`FIND_IN_SET('profesor', tipo_personal)` y nada más: prefectura y administrativos registran y
coordinan las ausencias, pero nunca aparecen como candidatos a suplente. Por eso los directorios
de Prefectura, Administrativos y Directivos no muestran la columna «Puede suplir».

**Un directorio por tipo de personal.** `profesores` · `prefectura` · `administrativos` ·
`directivos`, cada uno su propio módulo asignable, los cuatro sobre la misma vista
(`views/blog/personal/index.php`, vía `renderDirectorio()`). Añadir uno nuevo no necesita vista, JS
ni SCSS: entra en `UsuarioBlog::MODULOS_ASIGNABLES`, una ruta, un método de una línea, el catálogo y
la categoría de `_modulos.php`, y los cuatro mapas de `_sidebar.php`.
⚠️ El color y el número de las tarjetas del home los asigna la **posición de render**, así que meter
una clave en una categoría corre la paleta de las siguientes. Es esperado.

**Margen de equidad.** `SuplenciaHora::MARGEN_EQUIDAD` (= 3) es la holgura de la regla de equidad:
se admite a quien esté hasta 3 coberturas por encima del mínimo del claustro. Con 0 —el
comportamiento original— quedaba casi siempre un único candidato elegible frente a decenas de
bloqueados, y prefectura se quedaba sin opciones reales.

**⚠️ La jornada es POR NIVEL, y la disponibilidad se calcula por RELOJ.** `periodos` tiene
`nivel` + `UNIQUE (nivel, orden)`: cada nivel entra, sale y descansa a su hora, y hay
profesores que dan clase en varios. Por eso **dos clases no chocan por compartir
`periodo_id`, chocan por solaparse en el tiempo**: la 3ª hora de Primaria y la 3ª de
Secundaria son ids distintos que se pisan en el reloj. Quien decide qué significa «se
pisan» es `Periodo::solapan()` (estricto: fin 08:40 + inicio 08:40 **no** se pisan, no hay
margen de traslado), y de ahí cuelgan `Horario::libreEn()`, `Horario::choques()` y
`SuplenciaHora::sugerir()`. Los tres `UNIQUE` de `horarios` siguen ahí pero **solo
bloquean el duplicado exacto**; el resto es validación de aplicación.

**⚠️ Los horarios son REALES y salen de `horarios_grupos/`** (21 PDFs de Peñalara, uno por
grupo, sin trackear en git). De ahí salen `periodos`, `materias`, `aulas`, `grupos` y las
831 filas de `horarios`. Para re-parsearlos hay que leerlos **por coordenadas** con
`pdfjs-dist` y usar las **líneas horizontales de la rejilla** (`page.getOperatorList()`)
como fronteras de fila: Peñalara centra el texto de cada celda dentro de su rectángulo y
hay hasta 35pt de diferencia dentro de una misma fila, así que ni `pdftotext -layout` ni
la posición vertical del texto permiten deducir a qué fila pertenece cada celda. Cada
celda se ancla en su **materia** (siempre lleva prefijo `P`/`S`/`B`/`K` de nivel) y los
profesores cuelgan de ella por proximidad.

**Tres situaciones del horario real** que el esquema no soportaba, y por las que
`horarios` **perdió sus tres UNIQUE** (queda solo `uq_clase`):

| Situación | Ejemplo | Columna |
|---|---|---|
| **Coteaching** | `Dulce\Laura`: titular + hasta 2 acompañantes | `rol_docente` |
| **Materia dividida** | 1ºA Sec tiene Arte *y* Música a la vez, el grupo se reparte | `division` |
| **Clase conjunta** | 6ºA+6ºB de Bachillerato juntos en Ecología | (mismo bloque, distinto `grupo_id`) |

Cada profesor conserva **su propia fila**, así que su horario y las suplencias funcionan
sin cambios. Quien pinta la clase entera es `Horario::agruparBloques()`, que devuelve
`BloqueHorario` (titular + acompañantes + grupos); `rejilla()` coloca bloques, no filas.
`Horario::choques()` reconoce las tres convivencias **solo si se le pasa el contexto**
`materia_id` + `division`; sin él es conservador y las reporta como choque.

**Guardias de receso.** Viven en `horarios` con `tipo = 'guardia'` y `lugar_id` (FK a
`lugares_guardia`), sobre un periodo con `es_receso = 1`. No es un atajo: así
`ocupacionDiaDeVarios()`, `libreEn()` y `SuplenciaHora::sugerir()` las cuentan como
ocupación sin ningún caso especial —a nadie se le asigna una suplencia mientras vigila el
patio, y el motivo dice «Tiene guardia a esa hora»— y una guardia se puede **suplir**
igual que una clase (`suplencia_horas.tipo` + `lugar_id`). Se agendan desde el editor de
horario: **pulsar una casilla de receso abre el modal en modo «Guardia»**, con los lugares
como chips y alta en línea (`/dashboard/usuarios/horario/lugar`).

> ⚠️ Ese camino estuvo **muerto** hasta agosto de 2026, y este archivo lo daba por hecho.
> Todo existía —columnas, FK, validación cruzada receso↔tipo, modal, SCSS— menos una línea:
> `blog-usuarios-horario.js` filtraba las casillas clicables por `dataset.tipo === 'libre'`,
> así que un receso nunca recibía listeners y la pestaña «Guardia» era inalcanzable. Si
> vuelve a aparecer un «está implementado pero no se puede usar», mirar primero ese filtro.

> ⚠️ **Y el segundo tropiezo del mismo camino: `[hidden]` no oculta lo que tenga `display`
> declarado más abajo en la cascada.** `setTipo()` sí ponía `el.hidden = true` sobre los
> `.hed-solo-clase`, pero `[hidden] { display:none }` vive en `base/_normalize.scss` y
> `.hed-field { display:flex }` en `admin/_admin-horario-editor.scss` — misma especificidad
> (0,1,0) y capa posterior (`app.scss` importa `base` antes que `admin`), así que ganaba el
> `flex`. Resultado: la modal de guardia pedía grupo, materia, aula y acompañantes —campos que
> `guardarBloqueHorario()` ignora por completo cuando `tipo='guardia'`— y la de clase ofrecía
> lugares. Lo cierra un `&[hidden] { display:none }` dentro de `.hed-field`.
> **Todo componente con `display` propio necesita su propio `&[hidden]`.** El mismo fallo
> tenía la modal de justificantes: `.cat-modal` es `display:grid` y su única regla de
> ocultación es `&[hidden]`, pero la vista abría el overlay sin el atributo y el JS alternaba
> una clase `.is-open` que no existe en ningún SCSS — nacía abierta y no se podía cerrar.

**Catálogos del módulo Horarios:** `periodos` (jornada), `aulas`, `grupos`, **`materias`**,
`lugares_guardia`.
`grupos` y `materias` comparten el ENUM `nivel` (`Maternal|Kinder|Primaria|Secundaria|Bachillerato`);
El orden de listado de `grupos` **no se configura**: sale de `Materia::ordenNivel()` (el `FIELD(...)`
que ordena Maternal → Bachillerato) y, dentro de cada nivel, del nombre. `aulas` solo tiene `nombre`.
La materia **no** es texto libre: `horarios.materia_id` y `suplencia_horas.materia_id` son FK a `materias`,
y los `selectBase()` de `Horario`/`SuplenciaHora` la traen con el alias `materia` (más `materia_nivel`),
así que las vistas siguen leyendo `$h->materia`.

**ORM · cuidado con NULL y aliases:** `ActiveRecord::crearObjeto()` solo copia columnas que sean
**propiedades declaradas** — cualquier alias de SQL (`MONTH() AS mes`, JOINs `ausente_nombre`, etc.) debe
declararse como `public $prop` en el modelo o se pierde. Y como el ORM base envuelve todo en comillas, las
columnas que necesiten `NULL` real (DATE, FKs) requieren persistencia manual (ver overrides arriba).

**⚠️ `database.sql` reactiva las FK al final, no arriba.** El archivo abre con
`SET FOREIGN_KEY_CHECKS = 0` + los `DROP TABLE`, y solo vuelve a poner `= 1` en la última línea. Si se
reactivan antes de los `CREATE`, cualquier FK ajena que apunte a estas tablas (p. ej. si la BD comparte
espacio con otra aplicación) aborta el script **a mitad**, dejando las tablas ya borradas. No mover esa línea.

**SQL:** todo el esquema y los datos viven en **3 archivos** (`database/database.sql` = estructura,
`database/development/development.sql` = datos de prueba, `database/deploy/deploy.sql` = producción). No
hay carpeta de migraciones: para actualizar una BD existente se re-ejecutan estos archivos.

### Estructura de archivos SQL (`database/`)

**Reglas completas en [`database/CLAUDE.md`](database/CLAUDE.md)** — leerlo antes de tocar un `.sql`.

```
database/
├── database.sql                    # ESTRUCTURA: todas las tablas, cero datos
├── credenciales.md                 # todas las cuentas del seed y sus contraseñas
├── CLAUDE.md                       # reglas de estos archivos
├── deploy/
│   └── deploy.sql                  # PRODUCCIÓN: registros reales (solo INSERT)
└── development/
    └── development.sql             # DESARROLLO: datos de prueba (solo INSERT)
```

**Solo existen esos tres `.sql`.** No hay migraciones ni parches: si el esquema cambia se edita
`database.sql` y se ajustan los dos archivos de datos. Ejecutar siempre `database.sql` primero y
luego **uno solo** de los archivos de datos, según el entorno.

`deploy.sql` lleva el claustro real, los catálogos y el contenido publicado; **no** lleva horarios,
suplencias, eventos, noticias ni testimoniales, porque en producción los genera el propio panel
(los horarios entran por CSV). `development.sql` es ese mismo contenido más los datos de prueba.

### Cuentas del seed

**La lista completa vive en [`database/credenciales.md`](database/credenciales.md)** — no duplicarla
aquí. Para probar el panel con distintos permisos existen `admin@`, `profesor1@`, `profesor2@` y
`prefecto@bilbao.edu.mx`, todas con la contraseña `Tlalmimilolpan39%`; el claustro real usa
`EditorBilbao25`.

> **Roles:** solo existen `administrador` (**Admin** en la UI, acceso a todos los módulos) y
> `usuario` (**Usuario**, acceso solo a los módulos listados en la columna `modulos`). El antiguo rol
> `editor` fue renombrado a `usuario`; conserva el mismo flujo de revisión editorial en Redacción.

> **Tipos de personal:** `prefecto` y `directivo` son **excluyentes** (no se combinan con
> nada, ni entre sí); `profesor` + `administrativo` sí se combinan. Solo los `profesor`
> cubren suplencias.

> **Rol Directivo** (`tipo_personal = 'directivo'`). Coordina igual que prefectura —abre y
> agenda suplencias— pero su acceso a la **configuración es de solo lectura**: ve el
> claustro, los horarios y los catálogos, y no puede escribirlos. Lo imponen
> `soloLectura()` y `requireEscritura($modulo)` en el controlador; las vistas lo anuncian
> con `views/blog/_solo-lectura.php` (el partial es la explicación, no el guard).
> Usuarios y horarios ya estaban cubiertos por `requireAdmin()`; aulas y grupos pasaron a
> `requireEscritura()`.
> A cambio, **dos cosas son suyas y solo suyas**: el tablero de suplencias y los
> justificantes. Seis cuentas en el seed (ver *Direcciones por nivel*).

### Paleta de colores para categorías

Usar **exactamente** estos 10 colores, en este orden cromático (naranja → rojo):

```
#fc6722  #f5b400  #8ac926  #34a853  #46bdc6
#4285f4  #4267ac  #aa2296  #ea075a  #e51022
```

Están declarados como custom properties en el `:root` de `src/scss/estaticas/_variables.scss`
(`--pal-naranja` … `--pal-rojo`, más `--pal-tinta` para el texto oscuro sobre los claros).
**Usar los tokens en el código nuevo**, no el hex a mano. Los usos antiguos siguen hardcodeados en
varios partials: se migran cuando se toque cada uno.

Los selectores de color en `views/blog/categorias/crear.php`, `editar.php`, `views/blog/noticias/categorias/crear.php` y `editar.php` ya están configurados con esta paleta. No usar otros colores para categorías.

---

## Assets

- Fuentes: `src/scss/` y `src/js/`
- Salida compilada: `public/build/css/`, `public/build/js/`, `public/build/assets/`
- **No editar** nada dentro de `public/build/` directamente; se sobreescribe con Gulp
- Comando de watch: `npm run dev`

### ⚠️ Nada de `<style>` ni `<script>` embebidos en las vistas

Todo el CSS y JS vive en `src/`. Las vistas solo llevan HTML/PHP. Estructura:

```
src/scss/
├── base/         tokens, mixins, tipografía
├── estaticas/    componentes y páginas públicas
├── publico/      estilos migrados de vistas públicas (1 partial por vista)
└── admin/        estilos migrados del panel (1 partial por vista)

src/js/
├── public/  → public/build/js/bundle.min.js   (lo carga views/templates/footer.php)
└── admin/   → public/build/js/admin.min.js    (lo carga views/layout-admin.php)
```

**Cómo se aísla cada página.** Cada vista declara su identificador en la primera línea:

```php
<?php $paginaVista = 'blog-usuarios-index'; ?>
```

Los layouts lo emiten como `<body data-page="blog-usuarios-index">`. Con eso:

- **CSS:** el partial correspondiente se envuelve en `body[data-page="..."] { … }`, así los estilos de una
  vista no se filtran a otras (varias vistas reutilizan nombres como `.wysiwyg-editor` o `.at-wrap` con
  valores distintos). Los `@keyframes` y `:root` quedan fuera del scope.
- **JS:** cada módulo arranca con la guarda
  `if (!document.body || document.body.dataset.page !== '...') return;`
  Los módulos de partials compartidos (`_sidebar`, `_form`, `_bg`, `layout-admin`, `header`) **no** llevan
  guarda de página: se activan por existencia de sus elementos, porque se usan en varias vistas.

**Reglas al crear una vista nueva:** añade `$paginaVista`, crea `src/scss/<admin|publico>/_<nombre>.scss`
(envuelto en `body[data-page="<nombre>"]`), regístralo en el `_index.scss` de esa carpeta, y pon el JS en
`src/js/<admin|public>/<nombre>.js` con su guarda.

**Datos de PHP hacia JS:** nunca interpolar PHP dentro de JS. Usar `data-*` en el elemento
(`data-events`, `data-titulo-ref`) o una isla JSON
(`<script type="application/json" id="dashboardChartData">` en `dashboard.php`) y leerla con `JSON.parse`.

### Responsive — el móvil es el dispositivo de profesores y prefectura

**Escala de breakpoints en `src/scss/base/_mixins.scss`:** `hasta-xl 1200 · hasta-lg 1024 ·
hasta-md 900 · hasta-sm 720 · hasta-xs 520 · hasta-xxs 400`. Se añadió **al lado** de los
cuatro mixins `min-width` que ya había (`telefono`/`tablet`/`desktop`/`xl_desktop`), que
tienen **cero usos**: reescribirlos cambiaría su semántica, y migrar los ~25 breakpoints
ad-hoc de `_blog-admin.scss` (3857 líneas) es riesgo de regresión sin contrapartida.
**Regla: mixin en lo nuevo, ad-hoc intacto en lo viejo.**

⚠️ **`hasta-lg` (1024px) no es negociable**: es donde el sidebar pasa a cajón, y ese umbral
está además en `layout-admin.php` y en `blog-_sidebar.js`. Cambiarlo en un solo sitio
descuadra el layout.

**⚠️ Sin JS el panel móvil era inoperable, y las reglas del cajón cuelgan de `html.js`.**
Por debajo de 1024px `.admin-sidebar` vive en `translateX(-100%)` y el botón que lo abre lo
**inyecta `blog-_sidebar.js`** — no está en el HTML de ninguna vista. Si el bundle no cargaba,
el resultado era un panel sin navegación ninguna. El script síncrono del `<head>` de
`layout-admin.php` (la misma excepción ya justificada del anti-salto) estampa `html.js`, y sin
él el sidebar se queda en el flujo: más feo, pero utilizable. El cajón es además un diálogo de
verdad: `aria-expanded`/`aria-controls`, `Escape` que cierra y devuelve el foco, e **`inert`
sobre `.admin-main`** — que resuelve trampa de foco y lectores de pantalla de un golpe, sin
recorrer focusables a mano.

⚠️ **`blog-_sidebar.js` empieza con `if (!sidebar || !overlay) return;`** y no es cosmético:
los módulos de `src/js/admin/` se concatenan en **un solo** `admin.min.js`, así que una
excepción aquí detiene el archivo y se lleva por delante todos los módulos posteriores.

**Rejilla semanal: `.supl-week` cambia de forma, `.hor-grid` no.** Son decisiones opuestas a
propósito:

| | Móvil (≤640 / ≤720) | Por qué |
|---|---|---|
| `.supl-week` | **vista por día** en vertical | En modo `select` solo el día activo recibe listeners; los otros cuatro salen con `is-dim`. El ancho que forzaba el scroll era **decorado no accionable** |
| `.hor-grid` | scroll-x, estrechado a 560px | Es **solo lectura** —el dato completo lo abre el modal de la casilla, que en táctil es la única vía— y ocultar columnas rompe la geometría de los `rowspan` bajo `table-layout:fixed` |

La vista por día la pinta **el JS** (`renderDia()` en `admin-supl-week.js`), no el servidor:
el JSON del endpoint no cambia, el contrato de selección es idéntico (mismos `[data-pick]`,
misma `.is-on`, `emitir()`/`single`/`onChange` intactos) y las **tres** vistas consumidoras lo
reciben gratis. Al cruzar el breakpoint se repinta, rehidratando con lo marcado **en ese
momento** —leído del DOM, no de `opts.selected`, que es la selección inicial—: si no, girar el
móvil a medio formulario borraba las horas ya elegidas.

**⚠️ Apilar una `.admin-table` en tarjetas exige repetir la guarda de paginación.**
`tbody tr.is-hidden, tbody tr.is-filtered { display:none }` vive en
`estaticas/_blog-admin.scss`, y `app.scss` importa `estaticas` **antes** que `admin/`: misma
especificidad, capa posterior. Un `tbody tr { display:block }` escrito en `admin/` la gana por
cascada y **mata la paginación en silencio**. Es el mismo fallo que el de `[hidden]`. Todo
bloque de apilado lleva obligatoriamente sus dos líneas y `.admin-table-scroll{overflow-x:visible}`.

Solo se apilan **`suplencias/historial`** y **`mis-coberturas`** (flujos de profesor, columnas
cortas); el resto se queda con scroll horizontal, que es cero regresión. Al apilar, `<thead>`
se oculta con `clip-path: inset(50%)` y **no** con `display:none`, para no perderlo en
lectores de pantalla; se pierde el afordance de ordenar y se asume, porque el servidor ya
devuelve por fecha descendente. Y como `.admin-table` hereda `font-size:.88rem`, los bloques
móviles suben el tamaño explícitamente: rebasear el root afectaría también al sitio público.

**⚠️ 1rem = 16px, NO 10px, y esto estuvo documentado al revés.** Hay **dos** declaraciones de
`html { font-size }` en conflicto: `base/_globales.scss:5` pone `62.5%` y `estaticas/_base.scss:7`
pone `16px`; `app.scss` importa `base` **antes** que `estaticas`, misma especificidad, así que
**gana 16px** y el `62.5%` es letra muerta (comprobable en `public/build/css/app.css`). Dos
partials se escribieron creyendo lo contrario y salían **1,6× más grandes** —la ficha del
colaborador y la cola de trabajo por revisar—; ya están reescalados. **Todo `rem` nuevo del panel
va contra 16px**: el rango real del resto de `admin/` es `0.7rem–1.05rem`.

**Hojas inferiores a ≤520:** `.bilbao-date` y los resultados de `.picker` pasan de popover
anclado al campo a `position:fixed` pegada al borde inferior — un desplegable de 268px en la
mitad baja de la pantalla queda medio fuera y el flip solo corrige el eje horizontal. Al ser
`fixed` dejan de recortarlos los ancestros con `overflow`, que es lo que resolvía a mano
`has-datepicker-open`. Van a `z-index: 210`, **por encima del cajón del sidebar (200)**.

`.bilbao-cal` se adapta **en `estaticas/_comunidad-familias.scss`**, que es donde vive: la
comparten Comunidad › Familias y cuatro pantallas del panel, así que arreglarlo ahí las cubre
todas y evita que las dos superficies se desincronicen.

**Componentes compartidos del panel** (sin scope de página; se activan por existencia de sus elementos):

| Clase | Módulo JS | Para qué |
|-------|-----------|----------|
| `.admin-switch-row` | — | Interruptor con título y ayuda (`no_puede_suplir`) |
| `.admin-file` | `admin-file.js` (`[data-file]`) | Zona de subida con nombre de archivo y validación de tamaño |
| `.supl-week` | `admin-supl-week.js` (`window.SuplWeek`) | Rejilla semanal de horario, en modo `select` o `preview`. Las filas son **tramos de reloj**, no periodos (ver abajo). En `preview` recibe `targetIni`/`targetFin` y marca toda celda que **solape** ese rango. `single: true` la vuelve de selección única (la usa crear un swap) |
| `.hed-grid` | `blog-usuarios-horario.js` | Rejilla **editable** del horario de un profesor. A diferencia de las otras dos, sus filas **son periodos** de un solo nivel: cada casilla es un `(dia, periodo_id)` escribible. **Un clic = un bloque**: abre `.hed-modal` en modo Clase (casilla libre) o Guardia (receso) |
| `.picker` | `admin-picker.js` (`[data-picker]`) | Buscador de personas con autocompletado. `data-picker-endpoint` elige la fuente (por defecto la de Suplencias) porque cada consumidor tiene sus propios guards; `data-picker-multi` + `data-picker-name` lo vuelve de selección múltiple con chips (`.picker-chip`) y un hidden `<campo>[]` por elegido. Lo usan el ausente/suplente de Suplencias, los acompañantes de coteaching y el compañero de un swap. ⚠️ Emite `change` **a mano** en su hidden: escribirlo por propiedad no dispara eventos, y de ese `change` cuelgan las recargas encadenadas |
| `.hor-select` | — | Select estilizado del módulo Horarios (soporta `<optgroup>`) |
| `.bilbao-cal` | por vista + `cal-anim.js` | Calendario reutilizable (cumpleaños, eventos, resumen diario, agenda de suplencias). La animación de entrada la pone `window.BilbaoCalAnim.entrada(grid)` con **GSAP**: cada vista repinta su rejilla por su cuenta, así que debe llamarla al final de su `render()`. Vive en `src/js/public/` porque `.bilbao-cal` también existe en Comunidad, y va en **los dos bundles** (ver `gulpfile.js`). Sin GSAP o con `prefers-reduced-motion` no hace nada |
| `.admin-nav__mod` | `admin-sidebar-nav.js` (`[data-nav-toggle]`) | Acordeón de módulo del sidebar. Abierto = módulo activo; plegado, pulsarlo expande el sidebar |
| `.bilbao-date` | `admin-datepicker.js` (`[data-datepicker]`) | Selector de fecha propio; sustituye a `<input type="date">`. **La semana empieza en domingo** (igual que `.bilbao-cal`). Tres vistas encadenadas **días → meses → años**: la cabecera sube de nivel, elegir baja. Escribe un hidden en `Y-m-d` y emite `change`. `data-habiles="1"` (default) bloquea fines de semana — **suplencias lo desactiva**: cualquier día es elegible. Se voltea solo (`.is-flipped`) si se saldría del viewport, y levanta el `overflow: clip` de **todos** los ancestros que recorten (`.admin-panel`, `.admin-form-section`, `.admin-form-row`). Lo pinta el partial `views/blog/_campo-fecha.php`. ⚠️ **Sin `text-transform: capitalize`**: el JS ya compone «Jueves, 20 de agosto de 2026» (mes en minúscula a propósito) y el capitalize lo rompía palabra a palabra — «20 De Agosto De 2026» |
| `.mh-pager` / `.cb-pager` / `.supl-pager` | `admin-pager.js` (`[data-pager]`) | Paginación en cliente de una lista ya renderizada (`[data-pager-item]`, `data-pager-per`). `window.AdminPager.reset()` la relista tras repintarla |
| `.admin-table` | `admin-table.js` (`[data-table]`) | Ordenamiento por columna (`<th data-sort="text\|num\|date">`) + paginación. Genera solo el paginador si hay `data-table-per` |
| `.admin-act` | — | Acciones de fila: `--edit` ámbar de la paleta (`--pal-ambar`, `#f5b400`) con lápiz en **tinta oscura** (`--pal-tinta`; el blanco sobre ese amarillo no pasa AA), `--del` rojo `--pal-rojo` + papelera, `--horario` naranja `--pal-naranja` + calendario, `--ghost` neutra |
| `.admin-danger-zone` | `admin-danger.js` (`[data-danger]`) | Zona de acciones irreversibles. El botón usa `.admin-btn--danger` (rojo sólido). La animación la pone **GSAP** (CDN en `layout-admin.php`); el módulo sale sin hacer nada si no hay `window.gsap` o si el usuario pidió `prefers-reduced-motion`, y el estado visual completo vive en CSS |
| `.admin-topbar__logout` | — | Cerrar sesión: círculo rojo de 40px con `POST /logout`. Vive en `_topbar-avatar.php`, así que sale en **todo** el panel. Sustituye a la antigua `.admin-logout-btn`, que cada vista repetía en su propio `<form>` (y que las vistas ya migradas al partial habían perdido) |
| `.at-wrap` | `admin-toast.js` (`#alexToast`) | Aviso de Alex tras una acción, disparado por query params (`?success`, `?deleted`…) y retirado solo a los 5,6 s. El markup vive en el partial **`views/blog/_toast.php`** (recibe `$toast = ['title','msg','icon','color']`); lo nuevo entra por ahí. Sigue copiado a mano en diez vistas antiguas, que se migran cuando se toque cada una |
| `.admin-topbar__bell` | `blog-notificaciones-index.js` | Campana con badge de pendientes; vive en `_topbar-avatar.php`, así que sale en todo el panel. Icono **blanco sobre el azul institucional** (`--pal-indigo`): estuvo en ámbar con tinta oscura porque el blanco sobre `#f5b400` no llega a AA |
| `.cat-modal` | `admin-catalogo.js` (`#catModal`) | Confirmación de borrado de los catálogos (aulas, grupos) |
| `.admin-tipo-card` / `.admin-mod-chip` | `admin-usuario-permisos.js` | Los dos lenguajes visuales del formulario de usuarios: **tarjeta de identidad** (tipo de personal, con el color del tipo) vs **chip de permiso** (módulo, con casilla cuadrada a la vista y **solo el nombre** — la descripción vive en el `title`, porque eran trece líneas de texto entre el admin y las casillas que venía a marcar). Antes ambos eran la misma `.admin-modulo-check` y sus nombres son homónimos —módulo «Profesores» vs tipo «Profesor»—, así que no se distinguía qué se estaba respondiendo. El JS aplica rol→módulos, `no_puede_suplir`, niveles y la exclusividad, que lee de `data-excluyente` en lugar de repetir la lista |
| `.swp-wiz` | `blog-swaps-crear.js` | Asistente del alta de swap: **una pregunta por pantalla** a ancho completo. Dos columnas — acompañamiento sticky (Alex + índice de pasos con lo ya elegido) y el paso activo. `sincronizar()` decide todo lo visible; `.swp-wiz__hint` dice qué falta antes de pulsar. ⚠️ `.swp-panel`, `.swp-wiz__hint`, el `<img>` de Alex y `.admin-btn` se alternan con `hidden` y **todos** llevan su `&[hidden]` |
| — | `admin-motivo.js` (`[data-motivo]`) | El select de motivo revela el campo de texto al elegir "Otro" |
| `.cat-tabs` | `admin-nivel-tabs.js` (`[data-nivel-tabs]`) | Tabs de nivel académico a ancho completo. Filtran una tabla ya renderizada (`is-filtered` + `AdminTable.refrescar()`) o hacen de radios en un formulario |
| — | `forest.js` (`window.BilbaoForest.init(canvas, opts)`) | Bosque Three.js reutilizable: landing, login, hero del panel y **Comunidad › Colaboradores**. Vive en `src/js/public/` pero **va en los dos bundles** (ver `gulpfile.js`), porque el login carga `admin.min.js`. Devuelve `null` sin WebGL o con `prefers-reduced-motion`: el llamador necesita fondo de respaldo en CSS. ⚠️ Se dimensiona con **`canvas.clientWidth/clientHeight` + `ResizeObserver`**, no con `window.innerWidth` — ver abajo |

> **Tablas de lectura:** todas usan `admin-table.js`. El servidor preoculta las filas que pasan de
> `data-table-per` con la clase `is-hidden` (evita el parpadeo inicial) y marca cada `<tr>` con
> `data-pager-item`. Los buscadores por página marcan las filas descartadas con `is-filtered` y
> llaman a `window.AdminTable.refrescar(tabla)` para que la paginación cuente solo las visibles.

> **Rejillas semanales:** `.supl-week` y `.hor-grid` usan `table-layout: fixed` con reparto
> exacto del ancho restante (`calc((100% - 74px) / 5)`) en las cinco columnas de día. Sin eso el
> ancho lo decide el nombre de materia más largo y los días salen desiguales. El texto "Libre" va
> en un `<span>` que llena la celda, no suelto.
>
> ⚠️ **El contenido de la celda va `position:absolute; inset:0`, y ningún modificador puede
> redeclarar su `position`.** `.supl-week__class--pick` lo hacía (`relative`, misma especificidad
> y más abajo en el archivo), así que en modo `select` —y solo ahí— los bloques del día activo
> volvían al flujo, se encogían a su contenido y no coincidían con los de los demás días.
> Por lo mismo, marcar una celda se hace con `box-shadow: inset …` y **nunca con `border`**: en
> `table-layout: fixed` el borde se dibuja dentro del ancho de columna y le roba 4px al hijo.
>
> **Las filas son tramos de reloj, no periodos.** Con jornada por nivel, un profesor de dos
> niveles tiene clases que no encajan en una sola jornada. Cada clase ocupa tantos tramos
> como dure (`span` → `rowspan`). **Lo calcula el servidor**, en `Horario::rejilla()`: la
> vista `_grid.php` y el JSON que alimenta `.supl-week` consumen la misma función, así que
> no pueden divergir.
>
> **Hay DOS ejes, y `Horario::ambito()` decide cuál.**
> - **Sin comprimir** (`Periodo::tramos()`): el eje son todos los cortes de las jornadas del
>   ámbito. Es el camino de **un solo nivel** —la mayoría del claustro y todas las vistas por
>   grupo—: el eje coincide con su jornada, todos los `span` valen 1, cada fila conserva su
>   etiqueta («libre en tu 3ª hora» significa algo) y la rejilla se ve exactamente como
>   siempre.
> - **Comprimido** (`Periodo::tramosComprimidos()`): parte de los EVENTOS —las clases de la
>   semana y los recesos que se pintan— en vez de la jornada, así que lo que ningún día usa
>   no llega a ser fila; además los huecos consecutivos de un mismo día se funden. Se activa
>   solo cuando el eje **mezcla niveles**, que es donde no hay jornada común que perder.
>   Mide: un profesor de dos niveles baja de 16-17 filas a 13.
>
> ⚠️ El eje comprimido **no descarta ningún tramo**, así que es contiguo. Es obligatorio:
> `spanTramos()` cuenta por contención sobre todo el array, y con un eje agujereado sumaría
> tramos no adyacentes y el `rowspan` se comería celdas de abajo.
>
> **Fusionar celdas libres es seguro; borrar cortes del eje no.** Lo primero convierte N
> celdas de span 1 en una de span N: la suma del día no cambia y los otros días conservan
> sus filas. Lo segundo tocaría las cinco columnas a la vez. Con cortes derivados de
> eventos, el corte superfluo no llega a existir y no hace falta borrarlo nunca.
>
> ⚠️ **TODAS las filas miden lo mismo**, y el alto sale de **`--hor-fila` (62px)** en el
> `:root` de `estaticas/_variables.scss` — más `--hor-fila-compacta` (46px) para
> `.supl-week`. Antes iba en proporción a `alto` (los minutos del tramo, vía un
> `<tr style="--min:50">`), y como el eje comprimido produce tramos de 50, 30 y 20
> minutos, un profesor de dos niveles veía casillas de tres alturas: se leía como un
> fallo de maquetación, no como información. 62px es justo lo que medía el tramo de 50
> min, el dominante, así que **las vistas por grupo y por aula quedaron pixel-idénticas**.
>
> El precedente estaba en el propio producto: **`views/blog/horarios/pdf.php` reparte el
> alto útil de la página entre todos los tramos por igual desde siempre y nunca ha leído
> `alto`**. La versión impresa ya había renunciado a la proporcionalidad.
>
> **`--min` ya no se emite** —ni en `_grid.php` ni en `admin-supl-week.js`—: un atributo
> que ningún estilo lee es una mentira que la próxima persona intentará usar. `alto`,
> `EJE_TOPE_MIN` y `EJE_HUECO_MIN` siguen en PHP porque `alto` viaja en el JSON del
> endpoint, que es contrato de tres vistas, pero **ninguna rejilla los consume**.
>
> ⚠️ Los **`rowspan` no se ven afectados**: `Periodo::spanTramos()` cuenta por contención
> de reloj y no lee `alto`. La suma de N filas más los N-1 `border-spacing` la sigue
> haciendo el navegador. El contenido se ancla al `<td>` con `position:absolute; inset:0`
> —en una tabla `height` es un mínimo y un `height:100%` del hijo no resuelve bajo
> `rowspan`—, así que **`.hor-cell` no aporta altura y la fila no puede estirarse**: por
> eso `.hor-cell__mat` necesita `nowrap`/`ellipsis` y `justify-content: safe center`.
> El **hueco** conserva su tratamiento cosmético (atenuado, hora en tono menor) pero ya
> no mide distinto: «ningún día usa este tramo» es información, la altura no lo era.
>
> ⚠️ **Con 3+ opciones de materia dividida no hay altura que alcance.** Tres franjas con
> materia + subtítulo cuestan ~64px y la fila mide 62.
> `_grid.php` emite `hor-cell--split-3` a partir de tres, y esa clase **oculta
> `.hor-cell__sec`**: el dato completo de cada opción ya viaja en la isla JSON
> `data-hor-cell` y lo pinta el modal de la casilla.
>
> ⚠️ **Solo se marca `hueco` un tramo SIN etiqueta propia.** `hueco` se pensó para los fragmentos
> que el eje comprimido genera al cruzarse dos jornadas —trozos de reloj que no son periodo de
> nadie—, no para una hora real de la jornada. Sin ese matiz, un profesor de un solo nivel con la
> «3ª hora» libre toda la semana veía esa fila aplastada a 16px y al 50% de opacidad entre filas de
> 62px: parecía un fallo de render, y «libre toda la semana» es información, no ruido.
>
> ⚠️ **Sin clases NI niveles declarados no hay ámbito**: `Horario::ambito()` devuelve `niveles = []`
> y la vista pinta su empty state. Caer a `Materia::NIVELES` montaba el eje con las cinco jornadas a
> la vez —~16 filas fragmentadas, rótulos repetidos («1ª hora» de dos niveles), sin fusión de libres
> (solo corre al comprimir) y con los recesos de los cinco niveles apilados—. Por lo mismo, el
> fallback de recesos de un día sin clases **solo aplica si el eje es de un único nivel**: con varios
> no se sabe qué jornada rige ese día, y no pintar ninguno es más honesto que pintarlos todos.
>
> **Preview de suplencias:** el endpoint acepta `&ini=&fin=` con la hora que se quiere
> cubrir y la inyecta como corte del eje (`extra`), porque el eje se construye con las clases
> **del candidato**: si la hora es de un nivel que él no imparte no sería frontera suya y el
> «Cubriría aquí» caería sobre un bloque libre de dos horas. Esos cortes están además
> **protegidos de la fusión** de libres, que si no se los tragaría otra vez.
>
> El **receso ya no es una fila entera** sino estado de celda: el mismo tramo puede ser
> receso de Primaria y clase de Bachillerato. Prioridad **clase > receso > libre**. Y si dos
> clases se pisan (dato que la BD ya no puede impedir entre niveles), `rejilla()` adjunta la
> perdida en `conflicto[]` y la celda pinta un badge rojo en vez de tragársela.
>
> **El color de la materia lo calcula PHP** (`BlogController::colorMateria()`) y viaja en el
> JSON. Antes `admin-supl-week.js` replicaba a mano el `crc32()` y la paleta de `_grid.php`:
> dos fuentes de verdad que se desincronizaban en cuanto alguien tocaba una.
>
> ⚠️ **Una clase cuyo `hora_inicio` no sea corte del eje NO se coloca**, y la suma de spans
> sigue cuadrando: desaparecía en silencio y ningún test lo veía (pasaba en `/horarios/grupo`
> con una clase importada con `nivel` explícito distinto al del grupo). Lo cierran dos cosas:
> `ambito()` fuerza la compresión ante discrepancia —lo que mete sus cortes en el eje— y
> `rejilla()` tiene una **guarda dura** que cuelga del `conflicto[]` cualquier clase que
> quedara sin colocar. El test cubre cuatro asertos, y el decisivo es «toda clase aparece
> exactamente una vez»; los otros tres (Σspan == nº de tramos, eje contiguo, y celda que
> cuadra con sus tramos) no lo detectan.

**Funciones usadas por `onclick=` inline** (p. ej. `cerrarModalEliminar`, `togglePassword`) deben
exponerse con `window.miFuncion = miFuncion;` dentro del módulo, o dejarán de existir al quedar
encapsuladas en el bundle.

> Únicas excepciones, ambas por el mismo motivo (deben correr **antes del primer pintado**, y el
> bundle va con `defer` al final del `<body>`):
> - el guard anti-FOUC de i18n en el `<head>` de `views/layout.php`;
> - el **guard anti-salto del sidebar** en el `<head>` de `views/layout-admin.php`: lee
>   `bilbao_sidebar_collapsed` de `localStorage` y estampa `sidebar-boot-collapsed` +
>   `no-transition` en `<html>`. Sin él la página se pintaba con el sidebar expandido y luego se
>   encogía animándose (0.28s) en **cada** navegación. `blog-_sidebar.js` sigue mandando al alternar;
>   solo ha dejado de decidir el estado inicial.

### Imágenes subidas por PHP

Los uploads de blog, noticias y avatares van a `public/build/assets/{blog,noticias,usuarios}/`. Para optimizar y generar versiones WebP:

```bash
npx gulp optimizar   # optimiza todas las imágenes subidas + genera .webp
npx gulp build       # CSS minificado + ambos bundles JS + imágenes de src/ para producción
npx gulp css         # solo CSS
npx gulp js          # ambos bundles (público + admin)
npx gulp jsAdmin     # solo el bundle del panel
```

El watcher `npm run dev` también observa los directorios de uploads y los optimiza al detectar nuevos archivos.

---

## Convenciones

- **CSS/HTML:** metodología BEM (`bloque__elemento--modificador`)
- **PHP:** snake_case para variables y métodos; PascalCase para clases
- **Vistas:** archivos `.php` simples; datos pasados como array desde el controller
- **Validaciones:** definidas en cada Model como método `validar()`; errores en `$this->errores`
- **Sesiones:** autenticación del panel en `$_SESSION['blog_usuario']` (`['id','nombre','rol','avatar','modulos']`); sitio público en `$_SESSION['usuario']`
- **i18n (ES/EN):** el texto del chrome (header/footer) usa atributos `data-i18n` / `data-i18n-attr`. Toda etiqueta nueva necesita su clave en el diccionario `DICT.en` de `src/js/i18n.js`, o el modo EN cae a español y emite `[i18n] missing key` en consola. El contenido de páginas nuevas puede ir sin `data-i18n` (no genera warning). Recompilar tras editar (`npx gulp js`).

---

## Flujo de trabajo habitual

1. Cambios de estilo → editar `src/scss/` → Gulp recompila automáticamente
2. Cambios de JS → editar `src/js/` → Gulp recompila automáticamente
3. Nueva página estática → agregar ruta en `index.php` + método en `EstaticasController` + vista en `views/estaticas/`
4. Nueva funcionalidad de blog → `BlogController.php` + vistas en `views/blog/`

---

## Archivos a NO tocar

| Archivo/Directorio | Razón |
|--------------------|-------|
| `public/build/` | Generado por Gulp; se sobreescribe |
| `vendor/` | Gestionado por Composer |
| `node_modules/` | Gestionado por npm |
| `includes/.env` | Secretos; nunca se commitea |
| `storage/justificantes/` | Partes médicos subidos por el claustro. Fuera de `public/` a propósito (ver *Justificantes*); solo se versiona su `.gitkeep` |

---

## Dependencias PHP (Composer)

`phpmailer/phpmailer` · `vlucas/phpdotenv` · `intervention/image` · **`dompdf/dompdf`**
(PDF de «Mi horario»).

> ⚠️ **La extensión `gd` de PHP hace falta** para dos cosas: incrustar el logo en el PDF
> y todo `intervention/image` (avatares y optimización de subidas). Si viene comentada en
> `php.ini`, descomentar `extension=gd`. El PDF **degrada sin ella** —sale sin logo en vez
> de reventar—, pero las imágenes no.

---

## Variables de entorno

Cargadas desde `includes/.env` via `vlucas/phpdotenv`. Ver `includes/.env.example` para la lista completa.

Variables críticas: `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME`, `HOST`, `EMAIL_HOST`, `EMAIL_USER`, `EMAIL_PASS`.

---

## Archivos utilitarios (eliminar antes de producción)

- `diagnostico.php` — verificador de extensiones y assets
- `informacion.php` — phpinfo() extendido

---

## SEO

`views/layout.php` soporta estas variables opcionales por página para sobrescribir los defaults:

| Variable | Uso |
|----------|-----|
| `$seo_titulo` | `<title>` y OG title |
| `$seo_descripcion` | `<meta description>` y OG description |
| `$seo_imagen` | OG image (ruta relativa, ej. `/build/assets/img/...`) |

Las vistas públicas de noticias y blog tienen sus propios `<head>` con OG tags completos.

## Panel de administración — módulos

El panel está organizado en **módulos**. Tras iniciar sesión se llega a un **home de módulos**:

| Ruta | Método | Módulo / rol |
|------|--------|--------------|
| `/dashboard` | `BlogController::home` | Home: tarjetas de módulos permitidos (agrupadas) + calendario y cumpleaños |
| `/dashboard/redaccion` | `BlogController::redaccion` | **Redacción** (dashboard analítico; requiere módulo `redaccion`) |
| `/dashboard/articulos*`, `/dashboard/categorias*`, `/dashboard/noticias*`, `/dashboard/revisiones`, `/dashboard/testimoniales`, `/dashboard/autores`, `/dashboard/notificaciones` | varios | Pertenecen a **Redacción** |
| `/dashboard/suplencias*` | `BlogController::suplencias`, `crearSuplencia`, `editarSuplencia`, `eliminarSuplencia` | **Suplencias** — CRUD completo (requiere módulo `suplencias`) |
| `/dashboard/suplencias/buscar-colaboradores` | `BlogController::buscarColaboradores` | Endpoint JSON de autocompletado (ausente/suplente desde `usuarios`) |
| `/dashboard/suplencias/sugerir` | `BlogController::sugerirSuplentes` | Endpoint JSON: candidatos a cubrir una hora (algoritmo de equidad) |
| `/dashboard/suplencias/horario` | `BlogController::horarioProfesorJson` | Endpoint JSON: horario semanal + horas libres de un profesor en una fecha |
| `/dashboard/suplencias/historial` | `BlogController::historialSuplencias` | Histórico del claustro, resumido a fecha · quién faltó · quién cubrió. Lo ve **todo el módulo**, también un profesor: no lleva motivos ni justificantes |
| `/dashboard/suplencias/dashboard` | `BlogController::suplenciasDashboard` | Tablero de estadísticas — solo **administrador** |
| `/dashboard/horarios/profesor\|aula\|grupo` | `BlogController::horariosVista` | **Horarios** en solo lectura (requiere módulo `horarios`) |
| `/dashboard/horarios/mi-horario` | `BlogController::miHorario` | El colaborador ve **su propio** horario, sin edición (solo `requireAuth`). La cifra de la cabecera son sus **horas de clase**, no las libres: las libres eran un residuo del cálculo de suplencias y anunciarlas ahí sobraba |
| `/dashboard/horarios/mi-horario.pdf` | `BlogController::miHorarioPdf` | El mismo horario en PDF (Dompdf), para llevarlo en papel |
| `/dashboard/usuarios/horario.pdf` | `BlogController::horarioUsuarioPdf` | El horario de **otro** colaborador en PDF, desde su ficha. Mismo guard que la ficha (`requireFichaColaborador()`) |
| `/dashboard/horarios/pdf` | `BlogController::horariosPdf` | La semana que se está viendo, en PDF (`?vista=profesor\|aula\|grupo&id=N`). Mismo guard que las tres vistas (`requireHorariosVista()`) |
| `/dashboard/horarios/importar` | `BlogController::importarHorarios` | Carga de horarios por **CSV** — módulo `horarios` + **admin** (es destructivo) |
| `/dashboard/usuarios*` | varios | **Usuarios** (requiere módulo `usuarios`) |
| `/dashboard/usuarios/cumpleanos` | `BlogController::cumpleanos` | Calendario de cumpleaños (módulo Usuarios) |
| `/dashboard/usuarios/detalle` | `BlogController::detalleUsuario` | **Ficha de un colaborador** (solo lectura): horario, ausencias, coberturas y swaps. La abre quien **coordina** + módulo `usuarios` **o** el directorio de su tipo |
| `/dashboard/usuarios/horario` | `BlogController::horarioEditor`, `guardarBloqueHorario`, `eliminarBloqueHorario` | **Editor del horario de un profesor** — módulo `usuarios` + **admin** (es el único punto que escribe horario) |
| `/dashboard/usuarios/horario/profesores` | `buscarProfesoresHorario` | Endpoint JSON: docentes para el campo de acompañantes (coteaching) |
| `/dashboard/profesores` · `/prefectura` · `/administrativos` · `/directivos` | `BlogController::profesores`, `prefectura`, `administrativos`, `directivos` | **Directorios de personal**, uno por `tipo_personal`. Cada uno es su propio módulo asignable y los cuatro comparten `views/blog/personal/index.php` vía `renderDirectorio()` |
| `/dashboard/aulas*` | `BlogController::aulas`, `crearAula`, `editarAula`, `eliminarAula` | **Aulas** — CRUD del catálogo (módulo `aulas`) |
| `/dashboard/grupos*` | `BlogController::grupos`, `crearGrupo`, `editarGrupo`, `eliminarGrupo` | **Grupos** — CRUD del catálogo (módulo `grupos`) |
| `/dashboard/notificaciones*` | `notificaciones`, `marcarNotificacionLeida`, `marcarTodasLeidas`, `eliminarNotificacion`, `restaurarNotificacion`, `limpiarNotificaciones` | **Notificaciones** — transversal, solo `requireAuth` |
| `/dashboard/suplencias/justificantes` | `justificantes`, `resolverJustificante` | Cola de justificantes vencidos — **solo dirección** |
| `/dashboard/suplencias/justificante` | `descargarJustificante` | Descarga del archivo. **Única puerta**: vive fuera de `public/` |
| `/dashboard/suplencias/trabajo` | `marcarTrabajo` | Prefectura registra si el ausente dejó trabajo para el grupo |
| `/dashboard/suplencias/reabrir-hora` | `reabrirHora` | Devuelve al circuito una hora `no_cubierta` |
| `/dashboard/usuarios/horario/lugar` | `crearLugarGuardia` | Alta en línea de un lugar de guardia (JSON, admin) |
| `/dashboard/swaps*` | `swaps`, `crearSwap`, `clasesSwapJson`, `horarioSwapJson`, `buscarProfesoresSwap`, `responderSwap`, `validarSwap`, `cancelarSwap` | **Swaps** (módulo `swaps`) |
| `/dashboard/soporte` | `soporte` | **Soporte técnico** — transversal, lo tiene todo el mundo |

**Módulo Soporte técnico.** No se asigna: está en `BlogController::MODULOS_TRANSVERSALES`,
así que `puede('soporte')` siempre da `true` y `_sidebar.php` lo añade a mano a
`$_misMods`. Es la vía para pedir ayuda cuando el panel falla, y condicionarla a un
permiso dejaría sin ella justo a quien no puede arreglarlo por su cuenta. El CTA compone
el mensaje de WhatsApp en cliente (`SOPORTE_WHATSAPP`, formato internacional) con el
nombre y el tipo de personal que llegan ya resueltos en `data-*`. El Q&A vive en
`views/blog/soporte/_faq.php` —añadir una pregunta se hace ahí y en ningún otro sitio— y
se **filtra a los módulos del usuario**: un profesor no necesita leer cómo se importa un
CSV de horarios.

**Módulo Swaps.** Intercambio PUNTUAL de clases entre dos profesores: no altera el horario
permanente, solo dice qué pasa esos dos días concretos, y por eso cada lado guarda la
pareja `(horario_id, fecha)`. Flujo `pendiente → aceptado/rechazado → validado/denegado`,
con notificación en los tres pasos; el último lo da prefectura o dirección.

> **`aceptado` NO es efectivo.** Que las dos partes se pongan de acuerdo no basta: hasta
> la validación el swap no vale, y la tarjeta lo dice con todas las letras
> (`.swp-card__pendiente`) para que nadie deje de ir a su clase confiando en él. Al pasar
> a `aceptado` se avisa a dirección: antes solo lo delataba el badge del subnav y un swap
> podía quedarse ahí para siempre.

**Decir que no exige decir por qué**, en los dos rechazos. Sin motivo, `responderSwap()` y
`validarSwap()` rebotan con `?faltamotivo=1` y no cambian el estado; el `required` del
formulario es la ayuda, el guard es el servidor. Y el motivo **viaja dentro de la
notificación**: antes había que abrir el listado para enterarse.

⚠️ **Las dos notas viven en columnas SEPARADAS.** `respuesta_nota` es del destinatario y
`validacion_nota` de quien coordina. Compartían columna, y la validación pisaba la
explicación del profesor: el solicitante se quedaba sin saber quién había dicho qué.

**Prefectura también abre swaps, y los suyos nacen `validado`.** No es una petición
sino una reasignación: designa a los **dos** profesores (paso 0 con `.picker`) y ambos
reciben un aviso `swap_impuesto` de que su clase cambió, no una pregunta. `creado_por`
distinto de `solicitante_id` es lo que lo delata en la tarjeta. La comprobación de que
cada clase es de quien dice ser **se mantiene**: que lo abra prefectura no exime, o un
POST manipulado regalaría la clase de un tercero.
⚠️ `blog-swaps-crear.js` busca cada picker por **su** marca (`[data-swap-solicitante]` /
`[data-swap-destinatario]`) y no por `[data-picker-value]` a secas: con dos en el
formulario, el selector genérico agarraba el primero creyendo que era el compañero.
`Swap::DIAS_VENTANA` (**7**) limita cuánto margen hay: las clases que se pueden pedir a
cambio son las del otro profesor dentro de los 7 días siguientes al que se falta — más
allá deja de ser un swap y es un cambio de horario. `crearSwap()` comprueba además
que **cada clase sea de quien dice ser**, o un POST manipulado podría regalar la clase de
un tercero.

**El alta es un ASISTENTE por pasos, una pregunta por pantalla** (`.swp-wiz`), no un
formulario con las cuatro apiladas. Apiladas rompían dos cosas a la vez: el último paso
caía fuera del viewport —había que hacer scroll para descubrir que existía— y la rejilla
semanal, que es cinco días por la jornada entera, se pintaba en una columna de 940px.
Ahora ocupa **el ancho completo**, que es lo que necesita.

- **El catálogo de pasos son DATOS** (`$PASOS` al principio de la vista): de ahí salen a la
  vez el índice lateral, la barra de progreso y la frase de Alex, así que no pueden
  desincronizarse. La frase viaja en `data-dice` de cada `<img>`, no copiada en el JS.
- **Alex acompaña el recorrido**: cambia de postura y de frase en cada paso
  (`.swp-wiz__alex`). No decora — dice lo que el título no puede sin alargarse.
- **El progreso es permanente**: índice lateral con lo elegido en cada paso + barra
  «Paso N de M». En un formulario troceado, no ver cuánto queda es peor que verlo todo.
- **`sincronizar()` es el único punto que decide qué se ve** (panel, índice, barra, Alex,
  botones y resumen). El paso «confirmar» cierra con el swap entero en una línea.
- El índice deja **volver atrás**, nunca saltar adelante: eso se saltaría los requisitos.
- ⚠️ **Ningún botón sale deshabilitado.** `.swp-wiz__hint` dice qué falta *antes* de
  pulsar; uno muerto sin explicación es indistinguible de uno roto.
- ⚠️ **Cuatro componentes con `display` propio se alternan con `hidden`** y por tanto
  llevan su `&[hidden]`: `.swp-panel`, `.swp-wiz__hint`, el `<img>` de Alex y —esta
  faltaba en todo el panel— **`.admin-btn`**, que es `inline-flex`: sin ella se verían a
  la vez «Siguiente» y «Enviar propuesta».
- ⚠️ El POST **no cambió**: los cinco hidden siguen en el DOM aunque su panel esté oculto
  (`hidden` no desactiva un input) y solo se envía desde el último paso.

Los pasos reusan componentes del panel en vez de desplegables:

1. **La clase propia se marca en la rejilla semanal** (`.supl-week` en modo `select` + `single`),
   no en un `<select>`. Un desplegable con «Lunes · 08:00–08:50 · Matemáticas (1A)» obliga a
   reconstruir la semana en la cabeza, y el profesor ya la tiene delante. La alimenta
   `/dashboard/swaps/horario`, puerta aparte de la de Suplencias **solo por el guard**: aquella
   pide `requireModulo('suplencias')` y un profesor con solo `swaps` recibía un 403. El cuerpo lo
   comparten en `rejillaProfesorJson()`, así que las dos rejillas no pueden divergir.
   ⚠️ El JSON de la celda expone **`horario_id`** (la fila), no solo `periodo_id`: un swap
   referencia una clase concreta, no «la 3ª hora del lunes».
2. **El compañero, con `.picker`** contra `/dashboard/swaps/buscar` (`UsuarioBlog::buscarProfesores()`,
   solo docentes y nunca uno mismo). Antes era un `<select>` con el claustro entero.
3. Sus clases dentro de la ventana, igual que antes (`/dashboard/swaps/clases`).

Quien coordina ve «Todo el claustro»; quien no, solo lo suyo. `Swap::todos()` acepta un
`$excluirUid` para que a un admin o prefecto que **además imparte** no le salgan sus propios
swaps dos veces en la misma pantalla. Los cuatro POST redirigen con su query param y el
listado los pinta con el toast de Alex (`views/blog/_toast.php`); antes se emitían y nadie los leía.

**Home de módulos y sidebar comparten catálogo.** `views/blog/_modulos.php` define las funciones
`blog_modulos_catalogo()` / `blog_modulos_categorias()` / `blog_modulos_visibles()` /
`blog_modulos_coordina()`, y las usan tanto `home.php` como `_sidebar.php`: **añadir un módulo se
hace en un solo sitio**.

> **El catálogo es sensible al rol.** `blog_modulos_catalogo()` sin argumento se adapta a quien mira
> (vía `blog_modulos_coordina()`, la fuente canónica de la regla admin-o-prefecto en vistas, que
> `_sidebar.php` reexporta como `_blog_coordina()`). Un profesor raso ve **«Mi horario» →
> `/dashboard/horarios/mi-horario`**, porque la vista general no puede abrirla, y Suplencias le habla
> de *sus* ausencias y coberturas. Hay que pasar **`blog_modulos_catalogo(true)`** cuando el catálogo
> describa los módulos de **otra** persona y no los del usuario en sesión — es el caso de
> `views/blog/usuarios/_permisos-fields.php`. Las tres
categorías van en este orden: **Personal y accesos** · **Operación académica** · **Contenido**.
El color **lo asigna la posición de render**, no el módulo: `mh-card--c1` es cyan y de ahí sigue el
curso cromático, así la primera tarjeta visible es siempre cyan tenga el usuario 2 módulos u 8.

**Catálogos Aulas y Grupos.** CRUD sobre tablas que ya alimentaban el horario; cada uno es su
propio módulo asignable. Ambos listados llevan la acción **Ver horario** (`--horario`, naranja) que
abre `/dashboard/horarios/aula|grupo?id=N`: es lo que hace falta para saber cuándo un espacio está
libre. Grupos filtra por **tabs de nivel** a ancho completo (`admin-nivel-tabs.js`, mismo patrón
`is-filtered` + `AdminTable.refrescar()` de los buscadores), y su formulario elige el nivel con esas
mismas tabs.
Antes de borrar se cuentan las dependencias (`Aula::usos()` / `Grupo::usos()` sobre `horarios` y
`suplencia_horas`) y, si las hay, el botón sale deshabilitado y el POST redirige con `?enuso=N`
en vez de dejar reventar la FK. En Grupos, `orden` fija la secuencia académica: ordenar por `nivel`
saldría alfabético.

**Detalle de casilla.** La celda de `.hor-grid` recorta con `text-overflow: ellipsis`:
materia, grupo, aula y docentes comparten una línea y lo que no cabe desaparece, así que
la casilla **miente por omisión**. Antes el dato entero vivía solo en el atributo `title`,
que en táctil no existe. Ahora cada celda es un `<button>` con la ficha completa en un
`data-hor-cell` (isla JSON) y la abre `admin-horario-celda.js` en `.hcd-modal`
(`views/blog/horarios/_celda-modal.php`). No hace falta ninguna consulta ni endpoint
nuevo: todo estaba ya en `$h`/`$b` dentro del `foreach` de `_grid.php`.

> El JS **no** lleva guarda `data-page`: se activa por la existencia del modal, así que
> sirve a las tres vistas que incluyen `_grid.php` sin repetir la lista en dos sitios. Y
> el partial lleva guarda anti-duplicado (`$GLOBALS`), porque el editor incluye `_grid.php`
> además de su propia rejilla y dos ids iguales romperían el `getElementById`.
> El SCSS de `.hcd-modal` va **fuera** del scope `body[data-page=...]`, como `.hor-select`.

**Las tres vistas del módulo también se llevan en papel**, con
`/dashboard/horarios/pdf?vista=&id=`. Reusan **la misma plantilla** que «Mi horario» sin
ninguna rama: de su sujeto solo necesita el `nombre`, y eso lo tienen igual un profesor,
un aula y un grupo (`$subtitulo` distingue de cuál se trata en el encabezado, porque
«3A Secundaria» a secas no dice si es el horario del grupo o el del aula homónima).
Pantalla y PDF salen de **`datosHorarioVista()`**, y el guard de las dos es
**`requireHorariosVista()`** (módulo `horarios` + coordinar), extraído por lo mismo que
`requireFichaColaborador()`: dos puertas al mismo cuarto no pueden pedir cosas distintas.
El botón solo aparece si hay tramos — un PDF de una semana vacía no es nada que imprimir.

⚠️ **El slug del nombre de archivo NO se hace con `strtr($s, 'áé…', 'ae…')`.** Con dos
cadenas `strtr` opera **byte a byte** y en UTF-8 un acento ocupa dos, así que «Adrián»
salía como `adriuen`. Va con la forma de array, la misma que `claveCatalogo()`.

**⚠️ El PDF de OTRO colaborador va por su propia ruta**, `/dashboard/usuarios/horario.pdf?id=N`
(`horarioUsuarioPdf()`), y no por un `?id=` opcional sobre `mi-horario.pdf`: aquella tiene
guard `requireAuth()` a secas porque el horario que sirve es el de quien pide, y un mismo
endpoint con dos niveles de autorización decididos dentro de un `if` es la forma que
alguien «simplifica» seis meses después — con una fuga de horarios ajenos como resultado.
La frontera ya existía en el panel: `/horarios/mi-horario` es lo propio,
`/usuarios/horario?id=` es lo de otro.
Las tres piezas están extraídas para que no puedan divergir: **`emitirHorarioPdf($datos)`**
(render + descarga, no decide qué ni quién) y **`requireFichaColaborador($id)`** (el guard
de tres pasos), que usan **tanto la ficha como el PDF**. El botón de la ficha va **fuera**
del `if ($puedeEditar)`: eso es permiso de escritura, y una dirección en solo lectura
también necesita llevarse el horario en papel.

**PDF de «Mi horario».** `GET /dashboard/horarios/mi-horario.pdf` → `miHorarioPdf()`, mismo
guard que la vista web (`requireAuth`). Se genera en servidor con **Dompdf**.

- **Reutiliza los datos, no el markup.** `datosHorarioProfesor()` es la fuente única que
  consumen la vista y el PDF, así que no pueden pintar semanas distintas. Pero la
  plantilla es propia (`views/blog/horarios/pdf.php`): la celda de `_grid.php` es
  `position:absolute; inset:0` dentro de un `<td>` relativo, y **Dompdf no implementa
  posicionamiento absoluto en tabla** — saldría todo apilado en una esquina.
- **El CSS sigue en `src/`**: `src/scss/horario-pdf.scss` (sin guion bajo, o dart-sass lo
  trata como partial y no lo emite) → `public/build/css/horario-pdf.css`, que el
  controlador inyecta con `file_get_contents()`. Una hoja enlazada no le llegaría a Dompdf.
- **Tipografía Outfit, la misma del panel**, para que el horario impreso se lea como parte
  del producto. Los TTF se versionan en `src/fonts/` porque Dompdf exige el archivo **en
  disco** —la hoja de Google Fonts no le sirve— y las `@font-face` las arma
  `cssFuentePdf()` en PHP: necesitan rutas absolutas que un CSS compilado no puede
  conocer, y por eso no están en el SCSS. `storage/fuentes-pdf/` es la caché `.ufm` que
  Dompdf genera sola (ignorada en git). Si faltan los TTF cae a la fuente por defecto.
- **Llena el folio, con la retícula regular**: A4 apaisado y **todas las filas del mismo
  alto**, repartido **en PHP** sobre el alto útil de página (el CSS de Dompdf no sabe
  hacer `calc()` sobre el alto de página). Fue el primer sitio en renunciar a la altura
  proporcional a la duración, que dejaba la fila con clase estirada y la vacía aplastada;
  la pantalla siguió el mismo camino después (ver `--hor-fila`). Por lo mismo la celda no
  repite la hora —ya está en la cabecera de la fila— salvo cuando la clase abarca varios
  tramos.
- ⚠️ **`colorMateria()['oscuro']` significa color CLARO** (ámbar, lima y cyan), que son
  los tres sobre los que el blanco no llega a AA: ahí va tinta oscura (`.hp-cell--claro`)
  y en el resto texto blanco. Es la misma regla de la rejilla web; invertirla deja media
  paleta ilegible en impresión.
- ⚠️ **El logo necesita la extensión GD.** Dompdf la exige para incrustar un PNG y sin
  ella lanzaba una excepción que se llevaba el PDF entero. `dataUri()` comprueba
  `extension_loaded('gd')` y devuelve `''`; la plantilla cae entonces a una marca
  tipográfica, así que el PDF **sale igual, sin logo**. Habilitar `extension=gd` en
  `php.ini` lo devuelve — y `intervention/image` (avatares y optimización de subidas) la
  necesita también.

**Módulo Horarios.** Es de **solo lectura**: consulta la semana ya consolidada. Se escribe desde
otros dos sitios, ambos de admin — el **editor por bloques** (`/dashboard/usuarios/horario`, ver
abajo) y el **importador CSV**. El importador va en dos pasos — subir → vista previa con
el estado de cada fila → confirmar — y guarda el payload validado en `$_SESSION['horarios_import']`.
El archivo **reemplaza el horario completo** de los profesores que aparecen en él
(`Horario::borrarDeProfesores()`) y no toca al resto. Formato (**7 columnas**):

```csv
profesor_email,dia,nivel,periodo,materia,grupo,aula
ana.torres@bilbao.edu.mx,lunes,,1,Matemáticas,1A Primaria,A-101
```

`dia` ∈ `Horario::DIAS`. **`nivel` va en la cabecera pero normalmente vacío en el contenido:
se deduce del `grupo`.** Solo hace falta escribirlo en una clase sin grupo, y si viene y
discrepa del grupo es error — es lo que decide en qué jornada cae «3ª hora». Con el nivel
resuelto, `periodo` casa con `periodos.etiqueta` **de ese nivel** o su `orden`, y `materia`
se resuelve por `(nivel, nombre)`, no solo por nombre: `materias` tiene `UNIQUE (nombre, nivel)`
y antes «Arte» de Kinder y de Primaria se pisaban en silencio. Grupo/aula se resuelven por
nombre (comparación sin acentos, `BlogController::claveCatalogo()`) y admiten vacío.

Los choques se validan **por hora de reloj**, no por `periodo_id`: profesor solapado y grupo
solapado son **error**, aula solapada es **aviso**. Se comprueba tanto dentro del archivo como
contra el horario ya cargado de los profesores que **no** vienen en él — sin eso, un grupo
ocupado por un tercero reventaba la transacción con un `Duplicate entry 'lunes-41-5'`
ilegible. El aula se valida la última: si va antes, su error tapa el de nivel/periodo/materia,
que es el que hay que corregir primero.

**Ficha del colaborador** (`/dashboard/usuarios/detalle?id=N`). Reúne en una pantalla lo que
el panel ya sabía de una persona y estaba repartido entre tres módulos: identidad y permisos,
su horario semanal, sus ausencias, las horas que ha cubierto y sus swaps. Antes lo más
parecido a una ficha era el **formulario de edición**, que solo abre un admin y que no dice
nada de lo que esa persona hace.

- **Guard en tres pasos:** sesión → `puedeCoordinar()` (expone motivos de ausencia y horarios
  ajenos: la misma frontera que separa la agenda del histórico del plantel) → módulo
  `usuarios` **o** el directorio del tipo de la persona mirada (`DIRECTORIO_DE_TIPO`). Ese
  mapeo vive en el **controlador** y no en un modelo: es política de acceso del panel, como
  `nivelesAlcance()`.
- **Cero consultas nuevas.** Reutiliza `datosHorarioProfesor()` —la misma fuente que «Mi
  horario» y su PDF, así que las tres no pueden pintar semanas distintas— más los métodos que
  ya sabían ceñirse a UNA persona: `Suplencia::listar(['ausente_id'=>N])`, `conteos($id)`,
  `SuplenciaHora::historicoDeSuplente()`, `Swap::deProfesor()`. Las estadísticas se calculan
  en PHP sobre esos arrays; `topIncumplimientos()` **no** sirve aquí (es ranking del plantel).
- **Secciones apiladas + anclas, no pestañas.** Cuáles existen depende del `tipo_personal`:
  un administrativo no tiene horario, ni suplencias, ni swaps, y ve un empty state que
  lo dice. Unas pestañas cuyo número cambia según a quién mires obligan a aprender la
  excepción; además así funcionan Ctrl-F, la impresión y el enlace directo, sin JS.
- ⚠️ **El nivel se rotula según el puesto**: en un `profesor` son los que *imparte*, en un
  `directivo` los que *gestiona* (y vacío = **todo el colegio**). Ver *Direcciones por nivel*.
- ⚠️ Incluye `views/blog/horarios/_grid.php`, así que `blog-usuarios-detalle` **tiene que
  estar en la lista de `body[data-page]` de `_admin-horarios.scss`** o la rejilla sale como
  tabla desnuda. Es el paso que se olvida al añadir una vista que use ese partial.
- Los swaps van en una `.admin-table` compacta y **no** en `.swp-card`: esa clase está
  encerrada en `body[data-page="blog-swaps-index"]` y su partial es una superficie de decisión
  que aquí saldría sin ninguna acción.
- Se entra desde los dos listados (`usuarios` y los cuatro directorios) con `.admin-act--ficha`
  —primera del grupo, azul: es la acción de lectura— y con el nombre de la fila convertido en
  enlace. Envolverlo en un `<a>` no rompe el ordenamiento: `admin-table.js` lee `td.dataset.val`
  antes que el `textContent`, y ambas vistas ya lo emiten.

**Editor de horario** (`/dashboard/usuarios/horario?id=N`, módulo `usuarios` + **admin**).
Vive en Usuarios, no en Horarios, porque es el punto que **escribe**. Corrige una clase suelta sin
regenerar el CSV entero.

> **UNA CASILLA = UN BLOQUE.** Se pulsa una casilla y se abre el modal. No hay selección
> múltiple, arrastre, `Shift`+clic ni atajo de fila: el alta por lote existía para cargar
> semanas enteras, que es exactamente lo que hace el importador CSV, y aquí solo metía una
> capa de estado (barra sticky, preview del rectángulo) entre el clic y el formulario. El
> coste asumido es que «la misma materia los cinco días» son cinco modales.
>
> **Qué abre cada casilla:** `libre` → modo **Clase** (grupo · materia · aula · acompañantes ·
> color) · `receso` → modo **Guardia** (lugar · color) · un bloque existente → edición.
> `ocupada` no es un botón.
>
> **El tipo NO se elige.** Lo decide la casilla, y `guardarBloqueHorario()` rechaza una clase
> sobre un receso y una guardia fuera de él. Por eso `.hed-tipo-badge` es una **insignia
> informativa**: el segmented control que había antes solo podía servir para provocar ese
> error del servidor.
>
> **La modal es horizontal y su ancho depende del modo.** `.hed-form` es una rejilla de dos
> columnas (`.hed-tipo-badge`, los lugares y el campo de profesores —`.hed-field--wide`— van a
> ancho completo); en vertical el formulario de clase se salía de pantalla y había que hacer
> scroll dentro de la modal para llegar a Guardar. En modo Guardia solo quedan lugar y color,
> así que `setTipo()` marca `.hed-modal__card.is-guardia` y la tarjeta baja a una columna y a
> 560px en vez de dejar media rejilla vacía.

> **Se edita UN nivel por pestaña, y por eso no usa `Horario::rejilla()`.** Una casilla tiene que ser
> exactamente un `(dia, periodo_id)` —que es lo que la tabla guarda— y ninguno de los dos ejes de
> `rejilla()` lo es: el comprimido parte de eventos y un fragmento de 20' al cruzarse dos jornadas no
> es periodo de nadie; el sin comprimir sobre varios niveles parte un periodo de 50' en dos tramos.
> Solo la jornada de un nivel cumple `casilla ≡ periodo`. La colocación la hace
> **`Horario::rejillaPorPeriodo()`**, y todos los `span` valen 1: cero aritmética de `rowspan`.
>
> **Las pestañas son los CINCO niveles, no los del ámbito.** Si salieran del ámbito, un admin no
> podría darle a un profesor de Primaria su primera clase de Secundaria. Como todo periodo pertenece
> a uno de los cinco, con cinco pestañas ninguna clase queda ineditable. Son **enlaces**
> (`?id=N&nivel=X`), no JS: cada rejilla necesita su overlay calculado en servidor.
>
> **Las clases de otro nivel se ven y bloquean la casilla.** Toda clase del profesor cuyo reloj pise
> la hora mostrada se pinta como `.hed-cell--ocupada` (rayada, no seleccionable) con un enlace a su
> pestaña. Un bloque de 50' de Primaria ocupa **las dos** casillas de Kinder que pisa. El descarte
> usa `Periodo::solapan()`, el mismo predicado de `choques()`, así que UI y guard coinciden por
> construcción. Debajo va la **semana consolidada** (el partial `views/blog/horarios/_grid.php` sin
> tocar): el editor muestra un nivel, esa rejilla muestra la verdad.
>
> **Coteaching desde el modal.** Una clase que dan dos o tres profesores a la vez son **N filas
> en `horarios`**, una por docente, con el mismo `(dia, periodo_id, grupo_id, materia_id, division)`
> y `rol_docente` distinguiendo titular de acompañante. Que cada uno tenga su fila es lo que hace
> que su horario, su disponibilidad y sus suplencias funcionen sin ningún caso especial. El titular
> es el profesor del `?id=N` y no se elige; los acompañantes (máx. `BlogController::MAX_ACOMPANANTES`
> = 2) se buscan con `admin-picker.js` en modo múltiple contra
> `/dashboard/usuarios/horario/profesores`. Solo en modo Clase: una guardia es de una persona.
>
> - `Horario::acompanantes()` es la clave que **escribe** el editor, y es un subconjunto estricto
>   de la de `agruparBloques()` (que agrupa por reloj e ignora el grupo, para fundir una clase
>   conjunta a dos grupos). Ser más específico aquí evita confundir al profesor del otro grupo de
>   una clase conjunta con un acompañante.
> - `Horario::acompanantesDeBloques()` los trae para toda la rejilla en **una** consulta.
> - ⚠️ Al **editar**, hay que excluir del chequeo de choques las filas hermanas o el bloque se
>   autodetecta como conflicto. Para eso `choques()`/`conflictos()` aceptan `int|array` en
>   `$excluirId`.
> - Cada acompañante se valida **solo** en su dimensión de persona («¿está libre a esa hora?»);
>   pasarle también aula y grupo repetiría el error del titular una vez por acompañante.
> - Al editar se **reconcilian** (borrar los que salen, insertar los nuevos, actualizar los que
>   siguen) en vez de reescribir todo: reescribir cambiaría sus ids, y con ellos las referencias
>   de swaps y suplencias a esa clase.
> - **Borrar el bloque se lleva a los acompañantes.** Una fila de acompañante sin titular no
>   aparece en ninguna rejilla del editor, así que nadie podría volver a borrarla, pero le sigue
>   ocupando la hora a esa persona.
>
> **Guardado atómico.** Si falla una fila no se escribe ninguna. Con coteaching esto pasa de
> precaución a requisito: escribir la mitad dejaría una clase con dos docentes de los que solo uno
> la tiene en su horario. Es POST + redirect + toast, **no fetch** — en el panel no hay ni un
> endpoint JSON de escritura, y tras guardar hay que recalcular en servidor el overlay de los cinco
> niveles y la vista consolidada. Se emite **una sola** notificación por persona y petición.
>
> **Validaciones** (`Horario::conflictos()`, que envuelve `choques()`): profesor solapado y grupo
> solapado son **error** (son físicamente imposibles); receso y bloque en el nivel equivocado también.
> El **aula** es **error solo si es el mismo `periodo_id`** —lo bloquearía el `UNIQUE uq_aula` con un
> `Duplicate entry` ilegible— y **aviso forzable** si solo se pisa en el reloj entre jornadas: el
> patio o el salón de usos múltiples reciben legítimamente a dos grupos. `usuarios.niveles` **no
> filtra ni bloquea nunca**: se avisa tras guardar y se marca la pestaña, nada más.
>
> Datos sucios preexistentes (clase sobre un receso, grupo de otro nivel) se pintan marcados
> `.is-invalida` y **se pueden borrar**: ocultarlos los volvería inarreglables desde la UI.

**`horarios.color`** es el color del bloque elegido a mano ahí. `NULL` = automático, derivado del
nombre de la materia. Lo calcula **`BlogController::colorMateria($materia, $color)`**, fuente única
que consumen el JSON de suplencias, `.hor-grid` y el editor — antes había tres copias del `crc32()`
y la paleta. ⚠️ **El CSV no transporta el color** y una importación reemplaza semanas enteras, así
que `importarHorarios()` fotografía `(profesor, dia, periodo) → color` con
`Horario::coloresDeProfesores()` antes del borrado y lo vuelca en las filas nuevas.

**Módulo Suplencias.** Dos flujos según quién abre la ausencia:

- **Profesor** → `/dashboard/suplencias/solicitar`: el ausente se lee de la sesión y el origen queda
  fijo en `anticipada` (no puede registrar "sin aviso").
- **Prefectura/admin** → `/dashboard/suplencias/crear`: elige al ausente con el picker y sí puede
  marcar "sin aviso".

En ambos, las horas a cubrir **no** se escriben a mano ni existe alta manual: se marcan tocando las
clases del día en una **rejilla semanal** (`.supl-week`, la pinta `src/js/admin/admin-supl-week.js`
desde el endpoint `/dashboard/suplencias/horario`) que inyecta los `hidden`
`periodo_id[]`/`grupo_id[]`/`aula_id[]`/`materia_id[]`. El mismo componente se reutiliza en **modo
preview** en `agendar` para ver el horario de un candidato con la hora de la cobertura resaltada.
La fecha usa el datepicker propio (`.bilbao-date`) y el submit aparece **también al pie del
formulario**, no solo en el topbar.

`agendar.php` es un layout de dos paneles: a la izquierda las horas (seleccionables, con la hora como
dato dominante), a la derecha los candidatos + preview + confirmación. El preview del horario se abre
como **acordeón bajo el candidato pulsado**, y los candidatos bloqueados por las reglas **no** ofrecen
asignación (no hay "asignar de todos modos": el sistema decide). Ambas listas se paginan de 5 en 5.
Sobre los paneles van dos tarjetas propias: el **motivo completo** (antes era un `<small>` recortado
con ellipsis dentro de la cabecera, justo el dato con el que prefectura decide) y el **justificante**.
Este último se muestra **siempre que exista archivo, sea cual sea el origen**: `crear` y `solicitar`
permiten adjuntarlo también en ausencias `anticipada`, y ahí quedaba invisible e indescargable. La
tarjeta solo *reclama* un archivo que falta cuando el origen es `sin_aviso`, que es cuando es
obligatorio.
**Estado de las horas por color, no por banner.** En `agendar`, cada `.supl-hora-card` dice su estado:
**ámbar** sin asignar · **azul** con suplente · **verde** validada. La selección se marca solo con un
anillo, porque el relleno ya significa otra cosa. Se retiró el bloque `.supl-done`
(«Falta 1 hora por asignar» / «Todas las horas ya tienen suplente»), que repetía el `X/Y` de la barra
de progreso; el cierre del flujo es ahora el botón **«Finalizar»** (verde, `.admin-btn--ok`) en el que
se convierte «Volver» cuando no queda ninguna hora pendiente. El motivo vive en la cabecera
(`.supl-fact--motivo`, sin el recorte a 190px del resto de facts), no en una tarjeta aparte.

**La agenda abre por calendario.** `/dashboard/suplencias` no tiene botones de acción en el topbar:
sobre la tabla hay un `.bilbao-cal` que cuenta las suplencias por día (`Suplencia::resumenDiario()`).
Pulsar un día filtra la tabla a esa fecha y ofrece **Abrir suplencia** con `?fecha=YYYY-MM-DD`, que
`crear` recoge vía `BlogController::fechaDeQuery()` (valida formato y fecha real, o cae al valor por
defecto). A diferencia del resto de `.bilbao-cal`, **ningún día se deshabilita**: también sirve para
abrir una ausencia futura. `resumenDiario($usuarioId)` acepta el mismo filtro que `conteos()`.

> **Fines de semana:** bloqueados en `crear` y `solicitar` (no hay clases que cubrir), **libres** en
> los filtros `desde`/`hasta` del listado, que son un rango de consulta y no la fecha de una clase.

**Módulo Eventos: audiencia + niveles.** El antiguo flag `publico` (que solo distinguía «interno» de
«visible para las familias») se sustituyó por dos campos:

- **`audiencia`** — `interno` · `familias` · `estudiantes`. Es **excluyente**: un evento tiene un
  público, y de él depende dónde se publica. `interno` no sale nunca del panel; los otros dos van
  además a Comunidad › Familias y Comunidad › Estudiantes respectivamente
  (`Evento::porAudiencia()`, consumida por `EstaticasController::eventosPublicos()`).
- **`niveles`** — SET opcional. Vacío = todo el colegio, y `Evento::normalizarNiveles()` guarda
  `NULL` también cuando están los cinco: para quien lee el calendario son la misma cosa, y así la
  UI no pinta cinco chips redundantes en cada fila.

Todos los eventos, sea cual sea su audiencia, siguen apareciendo en el calendario del panel.
`Evento::publicos()` se conserva como alias `@deprecated` de `porAudiencia('familias')`.

El home `/dashboard` muestra un **calendario interactivo** (`.bilbao-cal`) que combina **cumpleaños +
eventos**, más una lista de **próximos eventos** y el panel de cumpleaños — todo visible para
cualquier usuario, no solo para quien tenga el módulo `usuarios`. Cuántos cumpleaños entran por
página **no es un número fijo**: `blog-home.js` mide el alto real de la columna (que la fija el
calendario de al lado) y ajusta `data-pager-per`; por eso `admin-pager.js` relee `per` en cada
pintado en vez de capturarlo al montar.

**Pulsar un día del calendario abre su ficha** (`#mhDiaModal`): antes las celdas eran botones sin
acción — se veían los puntos de color pero no había forma de saber qué eran sin ir a Eventos.

**El hero del home es la portada, no un tablero.** `.mh-hero` lleva el **bosque WebGL** del login
(`BilbaoForest.init(canvas, {dark:false})`, paleta CLARA como la del landing) y el saludo sobre una
tarjeta de vidrio. Three.js lo inyecta `home()` en `$extra_head`, igual que `login()`: el layout del
panel no lo carga por defecto. El degradado del contenedor **es el respaldo real** —`init()` devuelve
`null` sin WebGL o con `prefers-reduced-motion`— así que el hero nunca se ve roto.

**Dentro del contenedor solo van la marca y el saludo:** `INTRANET BILBAO` + `Hola, {nombre}`.
Cayeron, por este orden, la fila `.mh-estado` (cumpleaños, notificaciones, coberturas, ausencias,
eventos), y después el **tile de fecha** y el subtítulo con el contador de módulos: la fecha la da
el calendario de abajo y los módulos los dicen sus propias tarjetas. El **cumpleaños del día** no se
perdió —es el único de todos esos datos que no aparece en ningún otro sitio del panel— sino que bajó
a su propia tira `.mh-cumple-hoy`, pegada al calendario porque es su mismo tema.

**«Intranet Bilbao» es UN título, no un eyebrow con un logotipo al lado.** El degradado ya vivía
en el contenedor y recorría las dos palabras, pero `.mh-hero__brand` iba a 15px, peso 800, en
versalitas y con `letter-spacing:.24em` frente a los 24px/900 de `.mh-hero__colegio`: con esa
diferencia de tamaño y tracking se leían como dos piezas distintas aunque compartieran tinta.
Ahora **las dos declaraciones son la misma** —mismo tamaño, peso y tracking, sin versalitas— y
lo único que queda es el degradado. Cambiar una de las dos vuelve a partir la marca.

⚠️ El lockup **no crece más allá del tamaño que tenía «Bilbao»**: el saludo de debajo llega a
29px y la marca acompaña, no compite.
⚠️ El degradado es el **azul institucional** (`#1f5a94 → #2f7cb8`, el mismo de `.lnd-hero__hi`),
no el naranja que tuvo antes: el naranja es un color de la paleta de categorías, el azul es la
marca.
⚠️ `background-clip:text` **no convive con `text-shadow`** (el halo se pintaría por detrás del
recorte y se vería la caja): el contraste sobre los árboles lo da el vidrio de `.mh-hero__texto`.

**Alex es la mitad del saludo, no un adorno de esquina.** A 118px se leía como un icono; ahora
es `clamp(130px, 14vw, 190px)` y el `min-height` del hero subió a 320px para que quepa. En
móvil ya **no se oculta**: se queda en fila con el texto (en columna empujaría las tarjetas de
módulo fuera de la primera pantalla) y solo desaparece por debajo de 400px.

**Quién ve qué: dos públicos que no se solapan.** La regla se ata a **`tipo_personal`, no al rol** —
un admin de sistemas no imparte nada y `prefecto` es tipo excluyente, así que coordina las ausencias
del claustro pero no tiene ausencias propias. La deciden `puedeCoordinar()` e `imparte()` en el
controlador, espejadas en las vistas por `blog_modulos_coordina()` y `blog_modulos_imparte()`
(`views/blog/_modulos.php`), que es de donde sale el subnav:

| Opción | URL | Quién |
|---|---|---|
| **Agenda** | `/dashboard/suplencias` | coordina |
| **Suplencias** (histórico propio) | `/dashboard/suplencias/mis-coberturas` | imparte |
| **Solicitar** | `/dashboard/suplencias/solicitar` | imparte |
| **Histórico del plantel** | `/dashboard/suplencias/historial` | todo el módulo |
| **Justificantes** | `/dashboard/suplencias/justificantes` | coordina |
| **Tablero** | `/dashboard/suplencias/dashboard` | admin |

**El histórico del plantel es la excepción a «la agenda es de quien coordina».** Un profesor
sí puede ver quién cubrió a quién en todo el colegio, porque esa pregunta no es privada; lo
que no puede ver son los motivos y los justificantes. Por eso es una vista aparte y no un
filtro de la agenda: `SuplenciaHora::historialPlantel()` selecciona columnas en **lista
blanca** —fecha, ausente, suplente— en vez de `sup.*`, que es lo que arrastra `motivo`,
`notas` y `justificante`. Solo lista horas con `suplente_id IS NOT NULL`: una hora que nadie
ha tomado todavía es trabajo pendiente de prefectura, no historia. El guard es
`requireModulo('suplencias')` a secas, sin distinguir impartir de coordinar.

`suplencias()` **redirige** a quien no coordina: la agenda es la herramienta de reparto —calendario,
buscador, «Abrir suplencia»— y expone motivos y justificantes de terceros. Antes se le servía
filtrada por `mias`, duplicando el histórico con la mitad de la información. Un profesor tiene en su
lugar **"Mis suplencias"**: sus coberturas (todas, vía `SuplenciaHora::historicoDeSuplente()`, no
solo las `agendada` — confirmar una la hacía desaparecer) y sus ausencias solicitadas
(`Suplencia::listar(['ausente_id' => N])`), con las que reclaman acción destacadas arriba.
`requireImparte()` rebota **al home**, no a la agenda: quien no imparte ni coordina se quedaría
rebotando entre las dos.

Los endpoints JSON `sugerir`, `buscar-colaboradores` y `horario` exigen coordinar; el último admite
además el `profesor` de la propia sesión, que es lo que necesita `/solicitar`.

**Cierre del ciclo (post-fecha).** `SuplenciaHora::validarHora()` exige `sup.fecha <= CURDATE()`:
antes se podía confirmar "sí la cubrí" semanas antes de la clase, justo lo contrario de lo que
promete la UI. Si la fecha pasa y nadie confirma:

1. Al entrar al panel, `BlogController::recordarCoberturasVencidas()` avisa al suplente
   (`suplencia_horas.recordatorio_en` evita repetir el aviso). No hay cron: el disparador es la
   carga de `/dashboard`, con la consulta indexada por `(suplente_id, estado_hora)`.
2. Si aun así no responde, prefectura cierra con **«Sí se cubrió» / «No se cubrió»** en `/agendar`
   (`SuplenciaHora::resolverPorPrefectura()`). "No" devuelve la hora a `pendiente` y notifica a los
   dos implicados.

### Direcciones por nivel

Hay **seis** cuentas `directivo`: la general (`direccion@`, id 72, **sin** `niveles`) y una por
nivel (ids 73-77). Todas con los mismos módulos — lo que las distingue no son los permisos sino el
**alcance de los datos**.

**La regla completa vive en `BlogController::nivelesAlcance()`**, y devuelve `[]` (= *sin filtro*)
en tres casos: es admin, **no** es directivo, o declaró los cinco niveles. Su espejo para las vistas
es `blog_modulos_niveles()` en `_modulos.php`. `niveles` viaja en la sesión (CSV crudo, como
`modulos`) porque las vistas lo necesitan y no consultan la BD; se guarda **el dato, no la
decisión**, para que cambiar la regla no deje sesiones vivas con una versión vieja congelada.

> ⚠️ **Vacío es *fail-open*.** Una dirección sin niveles ve el colegio entero. Es lo que hace falta
> para la dirección general, pero significa que crear una dirección de nivel y olvidar marcárselos
> **no la deja sin acceso: se lo da todo**. Por eso el formulario lo dice con todas las letras.

**`?nivel=` estrecha, nunca amplía.** `nivelesVista()` acepta el parámetro para que un admin filtre
el tablero, pero a una dirección de Primaria `?nivel=Secundaria` le devuelve su propio alcance: es un
no-op, no una escalada.

**El nivel de una suplencia sale de `periodos.nivel`, vía `suplencia_horas.periodo_id`.** Es el
único camino fiable: `grupo_id` y `materia_id` van `NULL` en las guardias de receso, así que filtrar
por `grupos.nivel` las perdería en silencio —y una guardia se suple igual que una clase—. `periodo_id`
es `NOT NULL` con FK `CASCADE`.

**Hay DOS granos, y confundirlos infla los conteos.** Por eso son dos helpers en `Suplencia` y no
uno con un flag:

| Grano | Helper | Predicado | Pregunta que responde |
|---|---|---|---|
| **suplencia** | `sqlNivel()` | `EXISTS (…)` | «cuántas *ausencias* hubo en Primaria» |
| **hora** | `sqlNivelHora()` | `periodo_id IN (…)` | «cuántas *coberturas* se hicieron ahí» |

`EXISTS` y **no** un `JOIN`: `selectBase()` y varios agregados ya unen `suplencia_horas`, y un
segundo JOIN multiplicaría filas disparando el `COUNT(sh.id) AS total_horas`.

> ⚠️ **`porEstado()` y `resumenDiario()` construían `WHERE a OR b` sin paréntesis.** Concatenarles
> un ` AND …` cambiaba la semántica en silencio (`AND` liga más que `OR`) y el filtro se aplicaba a
> medias. Están parentizados; no desparentizarlos.

**`$incluirSinNivel` es deliberado.** Una suplencia recién abierta todavía no tiene horas, así que
no tiene nivel. En la **agenda** y la **cola** se deja pasar (si no, desaparecería justo de quien
debe agendarla); en las **estadísticas** se excluye (no aporta a ningún nivel). El guard de objeto
`requireAlcance()` es *fail-open* por lo mismo.

**Filtrar el listado no es cerrar la puerta.** Todo lo que llega por `?id=` lleva además
`requireAlcance()`: `/agendar`, la descarga del justificante, `resolver`, `aprobar`, `reabrir-hora`,
`validar-prefectura` y `marcar-trabajo`.

| Pantalla | ¿Filtra? |
|---|---|
| Tablero, agenda + calendario, cola de justificantes, listado de swaps | **Sí** |
| **Histórico del plantel** | **No** — es de todo el claustro por diseño y lo ve hasta un profesor raso |
| Endpoint `sugerir` | **No filtra candidatos** (rompería «prioridad por nivel, no filtro»); sí lleva guard de objeto |
| Horarios, directorios, catálogos, eventos, calendario del home | **No** — son catálogo, no gestión |

**El tablero se abrió a dirección** (`esAdmin() || esDirectivo()`, ya no `requireAdmin()`), acotado
por `nivelesVista()`. **Prefectura sigue fuera**: meterla por `puedeCoordinar()` la metería *sin
acotar* —`normalizarNiveles()` fuerza `NULL` en prefecto—, o sea una ampliación de permisos
disfrazada de cambio de alcance. Cuando el alcance no está vacío la vista lo anuncia con
`.sd-alcance`: un tablero filtrado que no lo dice se lee como el dato del colegio entero.

**Los eventos validados llegan a dirección.** `Notificacion::nueva()` escribe una fila por usuario y
no sabe de grupos, así que el reparto lo hace `UsuarioBlog::direccionesDeNiveles($niveles)` —la de
esos niveles **más** la general— y `BlogController::avisarDireccion()`, que deduplica con
`array_flip` y saca a quien ejecuta la acción. Se dispara al **validar una cobertura**
(`validarCobertura`, `validarPrefectura`) y en los **swaps** (`aceptado`, `validado`, y el alta
directa de prefectura).

> ⚠️ Un `SET` de MySQL tiene **tres** estados: `NULL`, `''` y con valor. `FIND_IN_SET(x, '')` da 0 y
> `'' IS NULL` es falso, así que sin el término `= ''` la dirección general se quedaría sin un solo
> aviso.

### Justificantes — competencia de DIRECCIÓN

**Prefectura coordina la ausencia; dirección revisa el documento.** Un prefecto ve el chip de
«recibido» o «falta» —lo necesita para saber si la ausencia está soportada— y **nada más**: ni el
nombre del archivo, ni la descarga, ni la cola, ni la resolución. Lo decide
`BlogController::puedeVerJustificante()` (= admin o directivo, **no** `puedeCoordinar()`), espejado
en las vistas por `blog_modulos_ve_justificantes()`. `$justifInfo` solo se calcula con permiso, o el
nombre y el peso se filtrarían al HTML aunque el botón no estuviera.

**⚠️ Las vistas pintan los CUATRO estados de `estadoJustificante()`, no un booleano.**
`sin_archivo · vigente · en_cola · resuelto` estaban modelados desde el principio —junto con
`JUSTIF_LABEL`, `diasParaCola()`, `diasParaPurga()` y `resolutor_nombre`— y **ninguna vista
los usaba**. `agendar.php` miraba `!empty($justificante)`, y como aprobar un parte pone esa
columna a `NULL`, al profesor al que se le acababa de aprobar el justificante la tarjeta le
decía en **rojo** «Falta el justificante» y le reofrecía el formulario de subida; en una
ausencia `anticipada` la tarjeta desaparecía sin dejar rastro de que hubo documento.
`.supl-justif` tiene ahora cuatro variantes (`is-pending`/`is-ok`/`is-done`/`is-broken`).

Tres cosas más que guard y UI no decían igual:

- **`is-broken`**: con permiso pero sin archivo en disco, `infoJustificante()` devuelve `null`.
  Ofrecer «Descargar» ahí prometía un 404 y el modal confirmaba el borrado de algo que no
  podía nombrar. `justificantes.php` ya lo trataba bien; `agendar.php` no.
- **El propio ausente puede descargar el suyo** (`descargarJustificante():849` lo autoriza),
  pero la vista solo se lo ofrecía a dirección.
- **Chips en los listados**: la agenda no decía nada del documento —había que abrir las
  suplencias una a una— y en «Mis ausencias» un profesor veía el badge `por_justificar` y
  **no tenía desde ahí ninguna vía para subir el archivo**. Ahora el chip es el enlace.

`justificarSuplencia()` tenía solo `requireAuth()`: le faltaban `requireModulo('suplencias')`
y `requireAlcance()` (este último solo si quien sube **no** es el ausente — un profesor sube
el suyo sin que su nivel entre en juego).

**Se acepta `txt`** además de PDF e imagen: no todo justificante es un escaneo, y un permiso
administrativo o una constancia interna llega muchas veces como nota de texto. Es también el
formato de los **ejemplos del seed** (`storage/justificantes/ejemplo-*.txt`, versionados como
excepción en `.gitignore`), que antes apuntaban a un PDF inexistente y hacían que toda la cola
saliera como «Archivo no encontrado». ⚠️ `mime_content_type()` no es estable con texto plano
—devuelve `text/html` o `application/x-empty` según el contenido—, así que para `.txt` se
acepta cualquier `text/*`: la extensión ya está en lista blanca y el archivo vive fuera de
`public/`.

**⚠️ El archivo vive FUERA de `public/`, en `storage/justificantes/`.** Estuvo en
`public/build/assets/suplencias/`, que sirve el shim de `index.php` — y ese shim corre **antes** de
`includes/app.php`, o sea sin sesión y sin permisos: cualquiera con la URL se descargaba el parte
médico. Ahora:

- `suplencias.justificante` guarda **el nombre del archivo**, no una URL. Los registros heredados
  llevan la ruta pública antigua y `rutaJustificante()` sigue resolviendo las dos.
- La única puerta es `GET /dashboard/suplencias/justificante?id=N`, que comprueba permisos y sirve
  con `Cache-Control: private, no-store`.
- El shim devuelve **404** en `assets/suplencias/`, lo que cierra de golpe lo que quedara en disco.

**Retención 7/30 días.** Es un parte médico, así que no se conserva indefinidamente. La cuenta
arranca en `fecha` (el día de la ausencia), no en la subida: lo que caduca es la necesidad de
revisarlo.

| Días desde la ausencia | Estado | Quién actúa |
|---|---|---|
| 0 – 7 (`DIAS_DESCARGA`) | `vigente` — se descarga desde la suplencia | **dirección** (o el propio ausente) |
| 8 – 29 | `en_cola` — espera decisión en `/dashboard/suplencias/justificantes` | **dirección**: descargar o eliminar |
| ≥ 30 (`DIAS_PURGA`) | `purgado` — se borra solo | nadie |

**El plazo de la cola es un semáforo de cuatro tramos**, no un booleano. Sobre los 30 días
de `DIAS_PURGA` (y como la cola empieza a los 7, `quedan ∈ [0,23]`): **0-3 rojo · 4-7
naranja · 8-14 ámbar · 15+ gris**. El corte del naranja son los `DIAS_DESCARGA`: le queda
tanto plazo como el que ya esperó para entrar. El gris de arriba **no es un olvido** — si
todo estuviera teñido, el color dejaría de señalar nada. Siempre tinte suave + tinta
oscura, nunca fondo sólido: el blanco sobre el ámbar `#f5b400` no llega a AA.
⚠️ El `data-val` del **`<td>`** sigue siendo `$quedan`: el `data-sort="num"` lee el
`data-val` de la celda y no el texto del `<span>`, así que moverlo rompe el orden en
silencio.

`Suplencia::estadoJustificante()` **deriva** el estado de la fecha en vez de guardarlo, para
que no envejezca mal si nadie entra al panel en una semana. La resolución sí se guarda y va
**firmada** (`justificante_resuelto_por` / `_en` / `_resolucion`): el histórico tiene que
distinguir «nunca hubo justificante» de «lo hubo y se resolvió así». La purga la dispara
`BlogController::purgarJustificantes()` desde `home()` — no hay cron, mismo patrón que
`recordarCoberturasVencidas()`.

El límite de tamaño sigue siendo `Suplencia::MAX_JUSTIFICANTE_MB` (**50 MB**), fuente única
que leen el guard del servidor y el `data-file-max` de las vistas.

**¿El ausente dejó trabajo para el grupo?** Lo marca prefectura en `/agendar`, hora a hora
(`.supl-trabajo`), y alimenta el tablero. Va en `suplencia_horas` y **no** en `suplencias`
porque un profesor puede dejar material para su clase de 3º y no para la de 5º; así el
dato se cruza con grupo, materia y **nivel**. No aparece sobre las guardias: en el patio no
hay trabajo que dejar.

**Lo escribe SOLO prefectura** (`BlogController::puedeMarcarTrabajo()` = admin ∪ `prefecto`,
espejado en las vistas por `blog_modulos_marca_trabajo()`). Es la simétrica del justificante:
el parte médico es un documento que **dirección valora**, el trabajo es un hecho de campo que
**prefectura constata** en el aula el día de la ausencia. El guard era `puedeAgendar()`, que
incluye a `directivo`, y como el dato alimenta el ranking de «ausencias sin trabajo» eso
permitía imputarle algo a un profesor desde el despacho. Dirección lo sigue **viendo** —la
vista pinta el estado y, si está sin revisar, un «Lo registra prefectura» en vez de los
botones.

> ⚠️ `marcarTrabajo()` comprueba además que la **hora pertenezca a la suplencia del POST**.
> `requireAlcance()` valida la suplencia, no la hora, así que sin eso un coordinador con
> alcance sobre A podía marcar una hora de B mandando `id=A&hora_id=<hora de B>`.

**Cada fila lleva las indicaciones del ausente.** `pendientesTrabajo()` trae
`sup.notas AS s_notas` —lo que el profesor escribió al avisar de su ausencia—, que es
justo el dato con el que se responde la pregunta de la pantalla; sin él había que abrir
cada suplencia por separado para poder marcar con criterio. La nota es de la
**suplencia**, no de la hora, así que se repite en todas las horas de la misma ausencia:
es correcto —se marca fila a fila— y no se deduplica porque dos ausencias del mismo día
se intercalan por hora y las filas no quedan contiguas. El caso vacío también se dice
(`.tpe-nota--sin`): «no escribió nada» y «no se cargó el dato» son la misma pantalla en
blanco, y esa duda es la que impide marcar con confianza.

> `suplencia_horas.trabajo_notas` sigue **sin usarse**. `marcarTrabajo()` sabe
> escribirlo y el POST ya lee `notas`, pero esta pantalla es un barrido de dos clics: un
> campo de texto por fila convertiría cada `<li>` en un formulario con foco y scroll, y
> mataría el flujo que justifica la vista. Su sitio es `agendar.php`.

**Cola de pendientes: `/dashboard/suplencias/trabajo-pendiente`** (`trabajoPendiente()`,
mismo guard). El dato no tenía dónde rellenarse: había que recordar en qué suplencia estaba
cada hora y abrirlas de una en una desde la agenda, y el único indicio de cuántas faltaban
era una cifra del KPI del tablero **que no enlazaba a ningún sitio** y que prefectura ni
siquiera ve. La alimenta `SuplenciaHora::pendientesTrabajo()`, con tres filtros que importan:
`dejo_trabajo IS NULL`, `tipo <> 'guardia'` y **`sup.fecha <= CURDATE()`** — antes de que la
clase ocurra la pregunta no tiene respuesta posible, y una ausencia agendada con tres semanas
de antelación inflaría la cola con trabajo que todavía no existe. El subnav lleva su badge
(`contarPendientesTrabajo()`), y el POST acepta `volver=cola` para no saltar a `/agendar` a
mitad del repaso.

> ⚠️ **`NULL` ≠ `0`.** `NULL` es «todavía sin revisar» y queda **fuera del denominador**
> del porcentaje; `0` es «no dejó». Meter los NULL en el cálculo convertiría un hueco de
> captura de prefectura en una acusación al profesor. Por eso hay un tercer estado en la
> gráfica (gris) y un botón para volver a «sin revisar»: marcarlo por error no debe quedar
> registrado como un hecho. Se persiste con `SuplenciaHora::marcarTrabajo()` —el ORM base
> no sabe escribir `NULL` real—, no por `guardar()`.

**«No se cubrió» se registra, no se borra.** Antes, marcar que el suplente no se presentó
devolvía la hora a `pendiente` y **borraba al suplente**, con él la única prueba de que
alguien había faltado: el incumplimiento no llegaba a ninguna estadística. Ahora la hora
pasa a `estado_hora = 'no_cubierta'` y el responsable queda en `incumplio_id`, que
**sobrevive a la reasignación**. Reabrir la hora es un paso aparte y deliberado
(`/dashboard/suplencias/reabrir-hora`): si volviera sola a la bolsa, la incidencia se
confundiría con una hora que nadie ha tomado todavía. El tablero lo muestra en
«Coberturas no cubiertas» (`SuplenciaHora::topIncumplimientos()`), y se notifica a los dos
implicados —al suplente con nivel `error`, al ausente con `aviso`. El modal de confirmación **nombra el archivo**
(`BlogController::infoJustificante()` da nombre, peso y tipo leyéndolos del disco; devuelve `null` si
el registro apunta a algo que ya no existe), pone la **descarga como acción dominante** —es lo que
hay que hacer antes— y el botón rojo nace `disabled`: lo suelta una casilla de confirmación. Antes
pedía un borrado irreversible hablando en abstracto de «el justificante».

> ⚠️ **50 MB supera los defaults de PHP** (2M/8M). Los valores viven en **`.user.ini`** de la raíz
> (`upload_max_filesize=52M`, `post_max_size=56M`), porque en hosting compartido no hay acceso al
> `php.ini` global. `post_max_size` va por encima a propósito: el POST lleva el archivo *más* los
> campos del formulario. Sin eso el archivo llega vacío; `subirJustificante()` detecta
> `UPLOAD_ERR_INI_SIZE` y lo explica en vez de fallar en silencio.

### Notificaciones (transversales)

Ya no son cosa de Redacción: **cualquier módulo** puede avisar a un usuario. La campana vive en el
topbar y por tanto en todo el panel.

```php
Notificacion::nueva($usuarioId, $tipo, $mensaje, $refId, $refTipo, $modulo, $nivel, $enlace);
```

- `modulo` (`redaccion|suplencias|horarios|eventos|usuarios|general`) fija el icono de la fila;
  `nivel` (`info|exito|aviso|error`) fija el color y el título del modal de Alex.
- **`enlace` guarda la URL de destino.** Antes se deducía como `/dashboard/{tipo}s/editar?id=N`,
  regla que ya no vale: suplencias abre en `/agendar` y horarios no tiene URL por fila.
- Los cuatro parámetros nuevos van al final y con valor por defecto, así que los llamadores
  antiguos siguen compilando.

**Marcar como leída = BORRAR.** No se archiva nada. La red de seguridad es un «Deshacer» de 6 s:
el DELETE se ejecuta de inmediato y la respuesta devuelve la fila completa, que el cliente
reinserta vía `/dashboard/notificaciones/restaurar` si el usuario se arrepiente. Se borra primero
y se restaura después —en vez de diferir el DELETE— para que recargar o cerrar la pestaña a mitad
de la cuenta atrás no deje basura. «Vaciar bandeja» pide confirmación en modal.

**La bandeja es su propio módulo activo.** `_sidebar.php` detecta `notificaciones` por URL y el
breadcrumb dice **Inicio › Notificaciones**. Antes el `else` final de esa cadena asignaba
`'redaccion'`, así que la bandeja (y `/dashboard/perfil`) se pintaban con el menú completo de
Redacción y una miga «Redacción» que un profesor ni siquiera puede abrir. Ahora Redacción se declara
por sus rutas y el default es `home`.

**Filas.** Dos acciones explícitas —**«Marcar como completada»** (verde, sólida: es la que cierra el
aviso) y **«Ver detalle»** (outline de contraste alto)— más separadores temporales
*Hoy / Esta semana / Anteriores*, que viajan dentro de su primera fila para no dejar encabezados
huérfanos al paginar. Las notificaciones de `suplencias` pintan una tira **L·M·X·J·V** con el día
resaltado: la fecha llega como alias `ref_fecha` de `Notificacion::porUsuario()`, nunca parseando el
mensaje.

**⚠️ El CTA del modal de Alex debe marcar como leída antes de navegar** (`data-alex-cta` +
`fetch` con `keepalive`). Sin eso se producía el bucle de "Ver detalle": el `<a>` está dentro de la
tarjeta, así que no disparaba el `dismiss()` enganchado al botón "Entendido" y al backdrop, la
notificación seguía pendiente y el mismo modal reaparecía en la página de destino indefinidamente.

**Rebote por permisos.** `requireModulo()` redirige con `?sinacceso=<modulo>` y el home lo explica
con un toast de Alex. Antes era un `Location: /dashboard` mudo y parecía que el enlace estaba roto.

⚠️ Si actualizas una BD existente en vez de recargar el seed, el `ALTER` que añade `modulo`,
`nivel` y `enlace` conservando los datos está documentado en
[`database/CLAUDE.md`](database/CLAUDE.md).

### Protección y permisos

- `requireAuth()` redirige a `/` si `$_SESSION['blog_usuario']` está vacío. La sesión guarda
  `['id','nombre','rol','avatar','modulos']`.
- `puede(string $modulo)` → `true` si el rol es `administrador`, o si el módulo está en el CSV `modulos`.
- `requireModulo(string $modulo)` → guard de entrada a cada módulo; redirige a `/dashboard` si no tiene acceso.
- `requireAdmin()` sigue reservado a operaciones sensibles/destructivas (crear/eliminar usuarios,
  eliminar categorías, aprobar/rechazar contenido) y al **tablero de estadísticas de suplencias**
  (`requireModulo('suplencias')` + `requireAdmin()`). Los usuarios con módulo `redaccion` conservan el
  flujo borrador → revisión → aprobación (antes rol `editor`; en el código la variable `$esEditor`
  significa "no es admin").
- `requireSuperadmin()` **ya no existe**: el rol `superadmin` se eliminó y sus cuentas pasaron a
  `administrador`. Los directorios de personal y los catálogos Aulas/Grupos son ahora módulos
  asignables normales; la importación de horarios por CSV pide `requireModulo('horarios')` +
  `requireAdmin()` porque reemplaza el horario completo de los profesores del archivo.
- `puedeCoordinar()` (= admin **o** `prefecto` **o** `directivo`, vía
  `UsuarioBlog::TIPOS_COORDINAN`) marca quién puede ver datos de terceros: horarios
  ajenos, motivos de ausencia y justificantes. `puedeAgendar()` es un alias suyo.
- El **sidebar** (`views/blog/_sidebar.php`) es **permanente**, no contextual: siempre lista todos
  los módulos del usuario agrupados por las tres categorías de `_modulos.php`, y el módulo activo
  se despliega en **acordeón** con sus subopciones. Antes solo se pintaba la navegación del módulo
  activo, así que desde Suplencias había que pasar por Inicio para llegar a cualquier otra cosa.
  - Las subopciones son **datos**, no markup: `blog_modulos_subnav(string $clave)` en `_modulos.php`
    devuelve `['label','icon','url','prefijo','ver']` por módulo, y `ver` recoge los guards
    (`$esAdmin` para «Nuevo usuario», «Importar CSV» y «Tablero»; revisor para revisiones y
    testimoniales; `blog_modulos_coordina()` para las vistas generales de Horarios). Añadir una
    subopción se hace en un solo sitio.
  - Un módulo **sin** subopciones (los cuatro directorios de personal) se pinta como enlace directo;
    también si su única opción apunta a la propia URL del módulo (Horarios visto por un profesor).
  - Estado: `is-current` en el módulo (barra de acento + acordeón abierto) y `active` en el sublink
    que casa con la URL. Solo un acordeón abierto a la vez, sin persistencia: cada navegación
    reafirma dónde estás. Lo alterna `src/js/admin/admin-sidebar-nav.js` (`aria-expanded` + `hidden`).
  - **Plegado (72px)** no hay sitio para submenús: pulsar un módulo con subopciones expande el
    sidebar y abre su acordeón, escribiendo `bilbao_sidebar_collapsed = '0'`.
  - Cierra la lista **Ver sitio público**, que bajó del topbar por ser una salida del panel y
    no una acción de la página.
  - ⚠️ **Notificaciones NO está en el sidebar**, y es deliberado: la bandeja ya tiene su acceso
    permanente en la **campana del topbar**, con el mismo badge de pendientes y en todas las
    pantallas. Tenerla en los dos sitios duplicaba el contador y metía en la lista de módulos
    algo que no lo es. La detección del módulo activo sí se conserva
    (`$_modActivo === 'notificaciones'`), para que el breadcrumb siga diciendo
    «Inicio › Notificaciones».
  - ⚠️ La lista de módulos del admin sale de `UsuarioBlog::MODULOS_ASIGNABLES`, igual que
    `BlogController::modulosDisponibles()`. Estuvo escrita a mano con cinco claves y dejaba fuera
    aulas, grupos y los tres directorios: el sidebar mostraba menos módulos que el home.
- **⚠️ El topbar NO lleva acciones de página.** En él quedan **campana** (icono blanco sobre
  `--pal-indigo`), **avatar** y el **logout** rojo, y nada más: los tres son del panel entero y
  los pinta `_topbar-avatar.php`, que incluyen todas las vistas, así que basta tocarlo una vez
  para que un elemento salga en todas.
  24 vistas colgaban ahí su botón principal —cada una repitiendo a mano su `<header>`, porque
  `layout-admin.php` no tiene slots— y era el peor sitio: la esquina menos escaneable, pegada a
  elementos que no tienen nada que ver con la pantalla. **Cuatro destinos, según qué ES el botón:**

  | Qué es | Dónde va | Clase |
  |---|---|---|
  | Alta / acción de un listado | Cabecera del panel al que pertenece | **`.admin-new-btn`** (era `.admin-topbar__new-btn`; el nombre BEM dejó de ser cierto al salir del topbar) |
  | Varias herramientas juntas (buscador + botón) | `.admin-panel__tools` dentro de esa cabecera | ⚠️ es `display:flex` → lleva su propio `&[hidden]` |
  | Guardar / Cancelar | Pie del formulario, `.admin-form-footer` | `--sticky` en los largos: el topbar era sticky y perder eso sí habría sido regresión. Su `z-index` va **por debajo** de los 210 de `.bilbao-date`/`.picker` en móvil |
  | Ver la entidad fuera del panel | `.admin-view-bar`, sobre el contenido | — |
  | Volver / ir a otra sección | **se retira**: es orientación, no acción, y el breadcrumb global ya la da | — |

  La mayoría resultaron ser **duplicados**: los listados ya tenían un `.admin-panel__action`
  («+ Nuevo», un enlace azul de texto que no se leía como botón) y diez formularios ya tenían su
  barra al pie. Solo `perfil.php` estrenó pie de verdad.
  ⚠️ Comprobación de que no se cuela otro: `grep -rn 'admin-topbar__actions' views/ | grep -v topbar-avatar`
  no debe devolver ningún `<a>` ni `<button>`.
- **Breadcrumb global:** `_sidebar.php` construye la ruta (`Inicio › Módulo › Subpágina`) desde la URL y
  el JS la mueve al `.admin-topbar__left` (ocultando el `.admin-topbar__title`). Vive en un solo lugar.
  La **última miga siempre se fuerza a `url = null`**, si no la raíz de un módulo se enlazaba a sí misma
  y el topbar se quedaba sin ningún texto destacado.
- **⚠️ Clases `db-stat`/`db-table`/`db-badge`/`db-card` son inline SOLO en `views/blog/dashboard.php`**
  (no están en el CSS compilado). No reutilizarlas en otras vistas del panel: cada vista lleva su
  partial en `src/scss/admin/` (ver `_admin-suplencias.scss` como referencia).
- **Marcar un tipo de personal preselecciona sus módulos.** `UsuarioBlog::MODULOS_SUGERIDOS`
  viaja como `data-modulos` en cada `.admin-tipo-card`, así que el JS no tiene una segunda
  copia de la lista. Tres reglas: los módulos se **unen, nunca se desmarcan** (es una
  sugerencia, y quitarle al admin algo que acababa de marcar sería pelearse con él);
  desmarcar el tipo no retira nada; y el rol pasa a `usuario` **salvo que ya sea
  `administrador`**, porque marcarle un puesto no debe degradarlo en silencio y para un
  admin los módulos ni se muestran. Se anuncia con `.admin-sugerido`: cambiar casillas sin
  decirlo deja al admin sin saber si el formulario ayuda o estorba.
- **`puede_suplir` solo lo decide un admin.** El toggle "No puede suplir a otros profesores" vive
  **únicamente** en el formulario de usuarios (`views/blog/usuarios/_permisos-fields.php`, visible solo
  si el tipo `profesor` está marcado). Un profesor no puede auto-excluirse, y `resolverPuedeSuplir()`
  fuerza `1` cuando no hay tipo `profesor`.
- **`prefecto` es excluyente.** No se combina con `profesor` ni `administrativo`; `profesor` +
  `administrativo` sí es válido. Lo impone `UsuarioBlog::normalizarTipoPersonal()` —única puerta de
  entrada, así que también cubre un POST manipulado—; `admin-usuario-permisos.js` solo desactiva las
  casillas para que se vea venir.
- **Comparaciones de rol:** `=== 'administrador'`, sin más. Solo hay dos roles.
- **La lista de usuarios va en tres tablas** (administradores · profesores · administrativos y
  prefectura). No son grupos excluyentes: quien tenga varios tipos aparece en todas las que le
  correspondan, y el reparto se hace en la vista sobre una única consulta.

### Comunidad — visual (Three.js + GSAP)

Las páginas de Comunidad usan un fondo Three.js reutilizable (`views/estaticas/comunidad/_bg.php`,
partículas en colores institucionales, solo en el hero) inyectando `three.min.js` vía `$extra_head` desde
el `EstaticasController`, y animaciones GSAP (ya global en `header.php`). Todo respeta
`prefers-reduced-motion` y degrada sin WebGL. **Colaboradores** es una pantalla única `100vh` con CTA a
`/login`. El componente calendario `.bilbao-cal` se comparte entre Familias (eventos) y el panel (cumpleaños).

### Comunidad (público)

`views/estaticas/comunidad/`: **Estudiantes** (embeds oficiales de Instagram), **Familias** (avisos de
prueba + calendario interactivo infantil con mascotas Alex), **Colaboradores** (landing de acceso al
panel, enlaza a `/login`). *Exalumnos fue eliminado.* El calendario usa el componente reutilizable
`.bilbao-cal` (definido en `_comunidad-familias.scss`, disponible también en el panel para cumpleaños).

**Colaboradores usa `forest.js`, el MISMO bosque del login**, no la nube de partículas de
`_bg.php` que tuvo antes (`$bg_scene='orbes'`). Es la puerta al panel y su único CTA lleva a
`/login`: compartir escena encadena las dos pantallas en vez de cambiar de lenguaje visual a
mitad de camino. El degradado oscuro de `.colab__stage` es el **respaldo real** —`init()`
devuelve `null` sin WebGL o con `prefers-reduced-motion` y el canvas queda transparente.

> ⚠️ **`forest.js` se dimensiona al CANVAS, no a la ventana.** Usaba
> `window.innerWidth/innerHeight`, lo cual da igual en la landing y el login —ahí el canvas es
> `position:fixed; inset:0`, o sea el viewport exacto— pero rompe en cuanto vive dentro de un
> contenedor: el hero del panel (~320px de alto) se rasterizaba a pantalla completa y la escena
> salía estirada, con un buffer varias veces mayor del necesario. Ahora mide con
> `clientWidth/clientHeight` y escucha un `ResizeObserver`, porque el `resize` de `window` no
> se dispara cuando lo que cambia es el contenedor (plegar el sidebar, por ejemplo). En
> `fixed; inset:0` los dos valores coinciden, así que **no hay regresión** en landing ni login.
> `renderer.setSize(w, h, false)` — el `false` evita que Three escriba `width`/`height` inline
> y entre en bucle con el observer.

## Comandos frecuentes

```bash
# Backend
composer install               # instalar deps PHP
php -S localhost:3000 dev-server.php   # servidor de desarrollo (el router file es obligatorio)

# Frontend
npm install                    # instalar deps Node
npm run dev                    # watch SCSS + JS + imágenes + uploads

# Imágenes
npx gulp optimizar             # optimiza uploads y genera .webp
npx gulp build                 # compilación de producción (CSS minificado)

# Base de datos
mysql -u root -p colegiobilbao < database/database.sql                    # estructura
mysql -u root -p colegiobilbao < database/development/development.sql     # datos de prueba
# En producción, en vez del seed:
# mysql -u root -p colegiobilbao < database/deploy/deploy.sql
```
