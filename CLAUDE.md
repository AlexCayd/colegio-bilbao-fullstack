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
`SuplenciaHora::sugerir()` (tiene clase / ya cubre esa hora / debe conservar una hora de descanso /
equidad) más `usuarios.puede_suplir`, que ya filtra `UsuarioBlog::candidatosSuplencia()`.

> ⚠️ La BD de desarrollo conserva una tabla `disponibilidad_suplencia` con FKs a `periodos` y
> `usuarios`. Es un **residuo del diseño anterior**: no está en `database.sql` ni la usa ningún
> modelo. No construir nada sobre ella.

**Solo los `profesor` cubren suplencias.** `candidatosSuplencia()` filtra por
`FIND_IN_SET('profesor', tipo_personal)` y nada más: prefectura y administrativos registran y
coordinan las ausencias, pero nunca aparecen como candidatos a suplente. Por eso los directorios
de Prefectura y Administrativos no muestran la columna «Puede suplir».

**Margen de equidad.** `SuplenciaHora::MARGEN_EQUIDAD` (= 3) es la holgura de la regla de equidad:
se admite a quien esté hasta 3 coberturas por encima del mínimo del claustro. Con 0 —el
comportamiento original— quedaba casi siempre un único candidato elegible frente a decenas de
bloqueados, y prefectura se quedaba sin opciones reales.

**Catálogos del módulo Horarios:** `periodos` (jornada), `aulas`, `grupos`, **`materias`**.
`grupos` y `materias` comparten el ENUM `nivel` (`Maternal|Kinder|Primaria|Secundaria|Bachillerato`);
`grupos.orden` fija la secuencia académica (Maternal → Bachillerato) porque ordenar por `nivel` sería
alfabético. `Materia::ordenNivel()` genera el `FIELD(...)` para ordenar por nivel en cualquier consulta.
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

> **Tipos de personal:** `prefecto` es **excluyente** (no se combina con nada);
> `profesor` + `administrativo` sí se combinan. Solo los `profesor` cubren suplencias.

### Paleta de colores para categorías

Usar **exactamente** estos 10 colores, en este orden cromático (naranja → rojo):

```
#fc6722  #f5b400  #8ac926  #34a853  #46bdc6
#4285f4  #4267ac  #aa2296  #ea075a  #e51022
```

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
| `.admin-switch-row` | — | Interruptor con título y ayuda (`no_puede_suplir`, `publico` de eventos) |
| `.admin-file` | `admin-file.js` (`[data-file]`) | Zona de subida con nombre de archivo y validación de tamaño |
| `.supl-week` | `admin-supl-week.js` (`window.SuplWeek`) | Rejilla semanal de horario, en modo `select` o `preview` |
| `.hor-select` | — | Select estilizado del módulo Horarios (soporta `<optgroup>`) |
| `.bilbao-cal` | por vista | Calendario reutilizable (cumpleaños, eventos, resumen diario) |
| `.bilbao-date` | `admin-datepicker.js` (`[data-datepicker]`) | Selector de fecha propio; sustituye a `<input type="date">`. **La semana empieza en domingo.** Escribe un hidden en `Y-m-d` y emite `change`. Con `data-habiles="1"` bloquea fines de semana. Lo pinta el partial `views/blog/_campo-fecha.php` |
| `.mh-pager` / `.cb-pager` / `.supl-pager` | `admin-pager.js` (`[data-pager]`) | Paginación en cliente de una lista ya renderizada (`[data-pager-item]`, `data-pager-per`). `window.AdminPager.reset()` la relista tras repintarla |
| `.admin-table` | `admin-table.js` (`[data-table]`) | Ordenamiento por columna (`<th data-sort="text\|num\|date">`) + paginación. Genera solo el paginador si hay `data-table-per` |
| `.admin-act` | — | Acciones de fila: `--edit` amarillo sólido + lápiz, `--del` rojo sólido + papelera, `--ghost` neutra |
| `.at-wrap` | `admin-toast.js` (`#alexToast`) | Aviso de Alex tras una acción, disparado por query params (`?success`, `?deleted`…) |
| `.admin-topbar__bell` | `blog-notificaciones-index.js` | Campana con badge de pendientes; vive en `_topbar-avatar.php`, así que sale en todo el panel |
| `.cat-modal` | `admin-catalogo.js` (`#catModal`) | Confirmación de borrado de los catálogos (aulas, grupos) |
| — | `admin-usuario-permisos.js` | Reglas del formulario de usuarios: rol→módulos, `no_puede_suplir` y exclusividad de `prefecto` |
| — | `admin-motivo.js` (`[data-motivo]`) | El select de motivo revela el campo de texto al elegir "Otro" |

> **Tablas de lectura:** todas usan `admin-table.js`. El servidor preoculta las filas que pasan de
> `data-table-per` con la clase `is-hidden` (evita el parpadeo inicial) y marca cada `<tr>` con
> `data-pager-item`. Los buscadores por página marcan las filas descartadas con `is-filtered` y
> llaman a `window.AdminTable.refrescar(tabla)` para que la paginación cuente solo las visibles.

> **Rejillas semanales:** `.supl-week` y `.hor-grid` usan `table-layout: fixed` + `width:20%` en las
> cinco columnas de día. Sin eso el ancho lo decide el nombre de materia más largo y los días salen
> desiguales. El texto "Libre" va en un `<span>` que llena la celda (`display:flex`), no suelto.

**Funciones usadas por `onclick=` inline** (p. ej. `cerrarModalEliminar`, `togglePassword`) deben
exponerse con `window.miFuncion = miFuncion;` dentro del módulo, o dejarán de existir al quedar
encapsuladas en el bundle.

> Única excepción: el guard anti-FOUC de i18n en el `<head>` de `views/layout.php` sigue inline
> porque debe ejecutarse antes de pintar.

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
| `/dashboard/suplencias/dashboard` | `BlogController::suplenciasDashboard` | Tablero de estadísticas — **administrador y superadmin** |
| `/dashboard/horarios/profesor\|aula\|grupo` | `BlogController::horariosVista` | **Horarios** en solo lectura (requiere módulo `horarios`) |
| `/dashboard/horarios/mi-horario` | `BlogController::miHorario` | El colaborador ve **su propio** horario, sin edición (solo `requireAuth`) |
| `/dashboard/horarios/importar` | `BlogController::importarHorarios` | Carga de horarios por **CSV** — solo superadmin |
| `/dashboard/usuarios*` | varios | **Usuarios** (requiere módulo `usuarios`) |
| `/dashboard/usuarios/cumpleanos` | `BlogController::cumpleanos` | Calendario de cumpleaños (módulo Usuarios) |
| `/dashboard/aulas*` | `BlogController::aulas`, `crearAula`, `editarAula`, `eliminarAula` | **Aulas** — CRUD del catálogo, solo superadmin |
| `/dashboard/grupos*` | `BlogController::grupos`, `crearGrupo`, `editarGrupo`, `eliminarGrupo` | **Grupos** — CRUD del catálogo, solo superadmin |
| `/dashboard/notificaciones*` | `notificaciones`, `marcarNotificacionLeida`, `marcarTodasLeidas`, `eliminarNotificacion`, `restaurarNotificacion`, `limpiarNotificaciones` | **Notificaciones** — transversal, solo `requireAuth` |

**Home de módulos y sidebar comparten catálogo.** `views/blog/_modulos.php` define las funciones
`blog_modulos_catalogo()` / `blog_modulos_categorias()` / `blog_modulos_visibles()`, y las usan
tanto `home.php` como `_sidebar.php`: **añadir un módulo se hace en un solo sitio**. Las tres
categorías van en este orden: **Personal y accesos** · **Operación académica** · **Contenido**.
El color **lo asigna la posición de render**, no el módulo: `mh-card--c1` es cyan y de ahí sigue el
curso cromático, así la primera tarjeta visible es siempre cyan tenga el usuario 2 módulos u 8.

**Catálogos Aulas y Grupos.** CRUD solo-superadmin sobre tablas que ya alimentaban el horario.
Antes de borrar se cuentan las dependencias (`Aula::usos()` / `Grupo::usos()` sobre `horarios` y
`suplencia_horas`) y, si las hay, el botón sale deshabilitado y el POST redirige con `?enuso=N`
en vez de dejar reventar la FK. En Grupos, `orden` fija la secuencia académica: ordenar por `nivel`
saldría alfabético.

**Módulo Horarios.** Es de **solo lectura** en todo el panel: el horario se carga por CSV desde
`/dashboard/horarios/importar` (superadmin). El importador va en dos pasos — subir → vista previa con
el estado de cada fila → confirmar — y guarda el payload validado en `$_SESSION['horarios_import']`.
El archivo **reemplaza el horario completo** de los profesores que aparecen en él
(`Horario::borrarDeProfesores()`) y no toca al resto. Formato:

```csv
profesor_email,dia,periodo,materia,grupo,aula
ana.torres@bilbao.edu.mx,lunes,1,Matemáticas,1A,A-101
```

`dia` ∈ `Horario::DIAS`; `periodo` casa con `periodos.etiqueta` o su `orden`; materia/grupo/aula se
resuelven por nombre contra los catálogos (comparación sin acentos, `BlogController::claveCatalogo()`).
`grupo` y `aula` admiten vacío. Lo que no exista en el catálogo se marca como **error** y esa fila se
omite; el choque de aula es solo un **aviso**.

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
El justificante de una ausencia `sin_aviso` vive en su propia tarjeta visible sobre los paneles.
Al quedar todas asignadas aparece el cierre "Finalizar y volver a suplencias". El home `/dashboard`
muestra un **calendario interactivo** (`.bilbao-cal`) que combina **cumpleaños + eventos**.

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
- `requireSuperadmin()` queda para los directorios de personal (Profesores/Prefectura/Administrativos)
  y para la importación de horarios por CSV.
- El **sidebar** (`views/blog/_sidebar.php`) es contextual: detecta el módulo activo por la URL y
  muestra solo la navegación de ese módulo; en el home lista los módulos permitidos agrupados por
  las mismas tres categorías (ver `_modulos.php`). Un botón **Inicio** (icono casa) vuelve al home.
  El **perfil**, **Ver sitio público** y la **campana de notificaciones** viven en el topbar
  (`_topbar-avatar.php` → sitio público · campana · avatar). Ese partial lo incluyen todas las
  vistas del panel, así que basta tocarlo una vez para que un elemento salga en todas.
- **Breadcrumb global:** `_sidebar.php` construye la ruta (`Inicio › Módulo › Subpágina`) desde la URL y
  el JS la mueve al `.admin-topbar__left` (ocultando el `.admin-topbar__title`). Vive en un solo lugar.
  La **última miga siempre se fuerza a `url = null`**, si no la raíz de un módulo se enlazaba a sí misma
  y el topbar se quedaba sin ningún texto destacado.
- **⚠️ Clases `db-stat`/`db-table`/`db-badge`/`db-card` son inline SOLO en `views/blog/dashboard.php`**
  (no están en el CSS compilado). No reutilizarlas en otras vistas del panel: cada vista lleva su
  partial en `src/scss/admin/` (ver `_admin-suplencias.scss` como referencia).
- **`puede_suplir` solo lo decide un admin.** El toggle "No puede suplir a otros profesores" vive
  **únicamente** en el formulario de usuarios (`views/blog/usuarios/_permisos-fields.php`, visible solo
  si el tipo `profesor` está marcado). Un profesor no puede auto-excluirse, y `resolverPuedeSuplir()`
  fuerza `1` cuando no hay tipo `profesor`.
- **`prefecto` es excluyente.** No se combina con `profesor` ni `administrativo`; `profesor` +
  `administrativo` sí es válido. Lo impone `UsuarioBlog::normalizarTipoPersonal()` —única puerta de
  entrada, así que también cubre un POST manipulado—; `admin-usuario-permisos.js` solo desactiva las
  casillas para que se vea venir.
- **Comparaciones de rol:** usar siempre `in_array($rol, ['administrador','superadmin'], true)`.
  Un `=== 'administrador'` a secas deja al superadmin fuera de su propia UI (era el bug de
  `views/blog/usuarios/index.php`, que le mostraba "Solo lectura" en cada fila; quedaba también en
  las dos vistas de categorías, que ocultaban el botón de eliminar al superadmin).
- **La lista de usuarios va en tres tablas** (superadmins · profesores · administrativos y
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
php -S localhost:3000          # servidor de desarrollo

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
