# CLAUDE.md — Colegio Bilbao Fullstack

Guía de contexto para Claude Code. Leer antes de tocar cualquier archivo.

---

## Qué es este proyecto

Sitio web institucional del **Colegio Bilbao** (colegio privado, México) + panel de administración de blog. PHP 8 MVC custom (sin Laravel/Symfony), MySQL, SCSS compilado con Gulp, desplegado en IIS.

---

## Arquitectura

### Entry point y routing

- `index.php` — dispatcher principal; sirve también assets estáticos de `/build/` hacia `public/build/`
- `Router.php` — router custom; soporta `get()`, `post()`, patrones con `{param}`
- `includes/app.php` — bootstrap: inicia sesión, carga Dotenv, conecta BD
- `dev-server.php` — **router file obligatorio** para `php -S` (ver abajo)

**⚠️ En local hay que arrancar con `php -S localhost:3000 dev-server.php`.** Sin el router file,
el servidor embebido devuelve 404 en todo `/build/*`: esos assets no existen físicamente (los sirve
un shim dentro de `index.php`), y el servidor solo cae al front controller cuando la URI *no* parece
un archivo — `/build/css/app.css` tiene extensión, así que nunca llega. Resultado: el sitio se pinta
sin CSS ni JS. `dev-server.php` replica la condición `IsFile` de `web.config`, veta las carpetas
sensibles (`includes/`, `vendor/`, `database/`, dotfiles) y manda todo lo demás a `index.php`.

> No renombrarlo a `router.php`: en Windows el FS es case-insensitive y chocaría con `Router.php`.
> En producción (IIS) no se usa: el reparto lo hace `web.config`.

> Estuvo mucho tiempo sin existir en el repo (un clon limpio abortaba con *Failed opening
> required*). Ya está creado.

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

**Componentes compartidos del panel** (sin scope de página; se activan por existencia de sus elementos):

| Clase | Módulo JS | Para qué |
|-------|-----------|----------|
| `.admin-switch-row` | — | Interruptor con título y ayuda (`no_puede_suplir`) |
| `.admin-file` | `admin-file.js` (`[data-file]`) | Zona de subida con nombre de archivo y validación de tamaño |
| `.supl-week` | `admin-supl-week.js` (`window.SuplWeek`) | Rejilla semanal de horario, en modo `select` o `preview`. Las filas son **tramos de reloj**, no periodos (ver abajo). En `preview` recibe `targetIni`/`targetFin` y marca toda celda que **solape** ese rango. `single: true` la vuelve de selección única (la usa crear un intercambio) |
| `.hed-grid` | `blog-usuarios-horario.js` | Rejilla **editable** del horario de un profesor. A diferencia de las otras dos, sus filas **son periodos** de un solo nivel: cada casilla es un `(dia, periodo_id)` escribible. **Un clic = un bloque**: abre `.hed-modal` en modo Clase (casilla libre) o Guardia (receso) |
| `.picker` | `admin-picker.js` (`[data-picker]`) | Buscador de personas con autocompletado. `data-picker-endpoint` elige la fuente (por defecto la de Suplencias) porque cada consumidor tiene sus propios guards; `data-picker-multi` + `data-picker-name` lo vuelve de selección múltiple con chips (`.picker-chip`) y un hidden `<campo>[]` por elegido. Lo usan el ausente/suplente de Suplencias, los acompañantes de coteaching y el compañero de un intercambio. ⚠️ Emite `change` **a mano** en su hidden: escribirlo por propiedad no dispara eventos, y de ese `change` cuelgan las recargas encadenadas |
| `.hor-select` | — | Select estilizado del módulo Horarios (soporta `<optgroup>`) |
| `.bilbao-cal` | por vista + `cal-anim.js` | Calendario reutilizable (cumpleaños, eventos, resumen diario, agenda de suplencias). La animación de entrada la pone `window.BilbaoCalAnim.entrada(grid)` con **GSAP**: cada vista repinta su rejilla por su cuenta, así que debe llamarla al final de su `render()`. Vive en `src/js/public/` porque `.bilbao-cal` también existe en Comunidad, y va en **los dos bundles** (ver `gulpfile.js`). Sin GSAP o con `prefers-reduced-motion` no hace nada |
| `.admin-nav__mod` | `admin-sidebar-nav.js` (`[data-nav-toggle]`) | Acordeón de módulo del sidebar. Abierto = módulo activo; plegado, pulsarlo expande el sidebar |
| `.bilbao-date` | `admin-datepicker.js` (`[data-datepicker]`) | Selector de fecha propio; sustituye a `<input type="date">`. **La semana empieza en domingo** (igual que `.bilbao-cal`). Tres vistas encadenadas **días → meses → años**: la cabecera sube de nivel, elegir baja. Escribe un hidden en `Y-m-d` y emite `change`. `data-habiles="1"` (default) bloquea fines de semana — **suplencias lo desactiva**: cualquier día es elegible. Se voltea solo (`.is-flipped`) si se saldría del viewport, y levanta el `overflow: clip` de **todos** los ancestros que recorten (`.admin-panel`, `.admin-form-section`, `.admin-form-row`). Lo pinta el partial `views/blog/_campo-fecha.php` |
| `.mh-pager` / `.cb-pager` / `.supl-pager` | `admin-pager.js` (`[data-pager]`) | Paginación en cliente de una lista ya renderizada (`[data-pager-item]`, `data-pager-per`). `window.AdminPager.reset()` la relista tras repintarla |
| `.admin-table` | `admin-table.js` (`[data-table]`) | Ordenamiento por columna (`<th data-sort="text\|num\|date">`) + paginación. Genera solo el paginador si hay `data-table-per` |
| `.admin-act` | — | Acciones de fila: `--edit` ámbar de la paleta (`--pal-ambar`, `#f5b400`) con lápiz en **tinta oscura** (`--pal-tinta`; el blanco sobre ese amarillo no pasa AA), `--del` rojo `--pal-rojo` + papelera, `--horario` naranja `--pal-naranja` + calendario, `--ghost` neutra |
| `.admin-danger-zone` | `admin-danger.js` (`[data-danger]`) | Zona de acciones irreversibles. El botón usa `.admin-btn--danger` (rojo sólido). La animación la pone **GSAP** (CDN en `layout-admin.php`); el módulo sale sin hacer nada si no hay `window.gsap` o si el usuario pidió `prefers-reduced-motion`, y el estado visual completo vive en CSS |
| `.admin-topbar__logout` | — | Cerrar sesión: círculo rojo de 40px con `POST /logout`. Vive en `_topbar-avatar.php`, así que sale en **todo** el panel. Sustituye a la antigua `.admin-logout-btn`, que cada vista repetía en su propio `<form>` (y que las vistas ya migradas al partial habían perdido) |
| `.at-wrap` | `admin-toast.js` (`#alexToast`) | Aviso de Alex tras una acción, disparado por query params (`?success`, `?deleted`…) y retirado solo a los 5,6 s. El markup vive en el partial **`views/blog/_toast.php`** (recibe `$toast = ['title','msg','icon','color']`); lo nuevo entra por ahí. Sigue copiado a mano en diez vistas antiguas, que se migran cuando se toque cada una |
| `.admin-topbar__bell` | `blog-notificaciones-index.js` | Campana con badge de pendientes; vive en `_topbar-avatar.php`, así que sale en todo el panel. Icono **blanco sobre el azul institucional** (`--pal-indigo`): estuvo en ámbar con tinta oscura porque el blanco sobre `#f5b400` no llega a AA |
| `.cat-modal` | `admin-catalogo.js` (`#catModal`) | Confirmación de borrado de los catálogos (aulas, grupos) |
| `.admin-tipo-card` / `.admin-mod-chip` | `admin-usuario-permisos.js` | Los dos lenguajes visuales del formulario de usuarios: **tarjeta de identidad** (tipo de personal, con el color del tipo) vs **chip de permiso** (módulo, con casilla cuadrada a la vista). Antes ambos eran la misma `.admin-modulo-check` y sus nombres son homónimos —módulo «Profesores» vs tipo «Profesor»—, así que no se distinguía qué se estaba respondiendo. El JS aplica rol→módulos, `no_puede_suplir`, niveles y la exclusividad, que lee de `data-excluyente` en lugar de repetir la lista |
| `.swp-step` | — | Paso del alta de intercambio: número en columna fija y guía vertical que encadena los pasos. Es un recorrido con dependencias (el 3 necesita los dos anteriores) y como `.admin-form-section` apiladas no se veía |
| — | `admin-motivo.js` (`[data-motivo]`) | El select de motivo revela el campo de texto al elegir "Otro" |
| `.cat-tabs` | `admin-nivel-tabs.js` (`[data-nivel-tabs]`) | Tabs de nivel académico a ancho completo. Filtran una tabla ya renderizada (`is-filtered` + `AdminTable.refrescar()`) o hacen de radios en un formulario |
| — | `forest.js` (`window.BilbaoForest.init(canvas, opts)`) | Bosque Three.js reutilizable (landing y login). Vive en `src/js/public/` pero **va en los dos bundles** (ver `gulpfile.js`), porque el login carga `admin.min.js`. Devuelve `null` sin WebGL o con `prefers-reduced-motion`: el llamador necesita fondo de respaldo en CSS |

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
> ⚠️ **`rowspan` + `border-spacing`.** El alto de fila va en proporción a **`alto`**
> (`<tr style="--min:50">` + `height: calc(var(--min) * 1.24px)`), y el contenido se ancla al
> `<td>` con `position:absolute; inset:0` — en una tabla `height` es un mínimo y un
> `height:100%` del hijo no resuelve bien bajo `rowspan`. Así la suma de filas y de los
> `border-spacing` intermedios la hace el navegador y **no queda aritmética en el CSS**.
> Por lo mismo, `.hor-cell__mat` necesita `nowrap`/`ellipsis`: una materia de nombre largo
> estiraría la fila y descuadraría las cinco columnas.
>
> **`alto` ≠ `minutos`.** `alto` viene capado a `Periodo::EJE_TOPE_MIN` (un tramo comprimido
> puede durar dos horas y la fila se iría a 174px) y baja a `EJE_HUECO_MIN` en los tramos
> marcados `hueco` — los que ningún día usa, que se pintan como franja separadora. `minutos`
> se conserva como dato. Alimentar `--min` de `minutos` revienta la altura.
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
| `/dashboard/horarios/importar` | `BlogController::importarHorarios` | Carga de horarios por **CSV** — módulo `horarios` + **admin** (es destructivo) |
| `/dashboard/usuarios*` | varios | **Usuarios** (requiere módulo `usuarios`) |
| `/dashboard/usuarios/cumpleanos` | `BlogController::cumpleanos` | Calendario de cumpleaños (módulo Usuarios) |
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
| `/dashboard/swaps*` | `swaps`, `crearSwap`, `clasesSwapJson`, `horarioSwapJson`, `buscarProfesoresSwap`, `responderSwap`, `validarSwap`, `cancelarSwap` | **Intercambios** (módulo `swaps`) |
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

**Módulo Swap de clases.** Intercambio PUNTUAL entre dos profesores: no altera el horario
permanente, solo dice qué pasa esos dos días concretos, y por eso cada lado guarda la
pareja `(horario_id, fecha)`. Flujo `pendiente → aceptado/rechazado → validado/denegado`,
con notificación en los tres pasos; el último lo da prefectura o dirección.

> **`aceptado` NO es efectivo.** Que las dos partes se pongan de acuerdo no basta: hasta
> la validación el intercambio no vale, y la tarjeta lo dice con todas las letras
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

**Prefectura también abre intercambios, y los suyos nacen `validado`.** No es una petición
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
allá deja de ser un intercambio y es un cambio de horario. `crearSwap()` comprueba además
que **cada clase sea de quien dice ser**, o un POST manipulado podría regalar la clase de
un tercero.

Los tres pasos de `/dashboard/swaps/crear` reusan componentes del panel en vez de desplegables:

1. **La clase propia se marca en la rejilla semanal** (`.supl-week` en modo `select` + `single`),
   no en un `<select>`. Un desplegable con «Lunes · 08:00–08:50 · Matemáticas (1A)» obliga a
   reconstruir la semana en la cabeza, y el profesor ya la tiene delante. La alimenta
   `/dashboard/swaps/horario`, puerta aparte de la de Suplencias **solo por el guard**: aquella
   pide `requireModulo('suplencias')` y un profesor con solo `swaps` recibía un 403. El cuerpo lo
   comparten en `rejillaProfesorJson()`, así que las dos rejillas no pueden divergir.
   ⚠️ El JSON de la celda expone **`horario_id`** (la fila), no solo `periodo_id`: un intercambio
   referencia una clase concreta, no «la 3ª hora del lunes».
2. **El compañero, con `.picker`** contra `/dashboard/swaps/buscar` (`UsuarioBlog::buscarProfesores()`,
   solo docentes y nunca uno mismo). Antes era un `<select>` con el claustro entero.
3. Sus clases dentro de la ventana, igual que antes (`/dashboard/swaps/clases`).

Quien coordina ve «Todo el claustro»; quien no, solo lo suyo. `Swap::todos()` acepta un
`$excluirUid` para que a un admin o prefecto que **además imparte** no le salgan sus propios
intercambios dos veces en la misma pantalla. Los cuatro POST redirigen con su query param y el
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
  hacer `calc()` sobre el alto de página). En pantalla las filas van en proporción a su
  duración; en papel eso dejaba la fila con clase estirada al contenido y la vacía
  aplastada, que se lee como un fallo de maquetación. Por lo mismo la celda no repite la
  hora —ya está en la cabecera de la fila— salvo cuando la clase abarca varios tramos.
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

La marca sigue el patrón tipográfico del hero del landing (`src/scss/estaticas/_home.scss`): eyebrow
en versalitas con `letter-spacing` amplio + pieza pesada (900) con `background-clip:text` y un halo
`text-shadow` del color de fondo para que se lea sobre los árboles. ⚠️ El degradado de «Bilbao» es
el **azul institucional** (`#1f5a94 → #2f7cb8`, el mismo de `.lnd-hero__hi`), no el naranja que
tuvo antes: el naranja es un color de la paleta de categorías, el azul es la marca.

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

> ⚠️ **50 MB supera los defaults de PHP e IIS.** Hay que subir `upload_max_filesize` y
> `post_max_size` en `php.ini` (defaults 2M/8M) y `maxAllowedContentLength` en `web.config`
> (default 30 MB). Sin eso el archivo llega vacío; `subirJustificante()` detecta
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
  - Cierran la lista dos transversales: **Notificaciones** (con badge) y **Ver sitio público**, que
    bajó del topbar por ser una salida del panel y no una acción de la página.
  - ⚠️ La lista de módulos del admin sale de `UsuarioBlog::MODULOS_ASIGNABLES`, igual que
    `BlogController::modulosDisponibles()`. Estuvo escrita a mano con cinco claves y dejaba fuera
    aulas, grupos y los tres directorios: el sidebar mostraba menos módulos que el home.
- En el topbar quedan **campana** (icono blanco sobre `--pal-indigo`) y **avatar**,
  más el **logout** rojo. Los pinta `_topbar-avatar.php`, que incluyen todas las vistas del panel,
  así que basta tocarlo una vez para que un elemento salga en todas.
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
