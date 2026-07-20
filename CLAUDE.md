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

**ORM · cuidado con NULL y aliases:** `ActiveRecord::crearObjeto()` solo copia columnas que sean
**propiedades declaradas** — cualquier alias de SQL (`MONTH() AS mes`, JOINs `ausente_nombre`, etc.) debe
declararse como `public $prop` en el modelo o se pierde. Y como el ORM base envuelve todo en comillas, las
columnas que necesiten `NULL` real (DATE, FKs) requieren persistencia manual (ver overrides arriba).

**SQL:** todo el esquema y los datos viven en **3 archivos** (`database/database.sql` = estructura,
`database/development/development.sql` = datos de prueba, `database/deploy/deploy.sql` = producción). No
hay carpeta de migraciones: para actualizar una BD existente se re-ejecutan estos archivos.

### Estructura de archivos SQL (`database/`)

```
database/
├── database.sql                    # creación de todas las tablas (sin datos)
├── deploy/
│   └── deploy.sql                  # registros reales de producción (solo INSERT)
└── development/
    └── development.sql             # registros de prueba para desarrollo
```

Ejecutar siempre `database.sql` primero, luego el archivo de datos según el entorno
(`deploy/deploy.sql` para producción, `development/development.sql` para desarrollo local).

### Cuentas del seed

| Email | Contraseña | Rol | Módulos |
|-------|-----------|-----|---------|
| `admin@bilbao.edu.mx` | `Tlalmimilolpan39%` | administrador | todos (implícito) |
| `alexander.oliva@bilbao.edu.mx` | `ColegioBilbao13` | administrador | todos (implícito) |
| `maria.gonzalez@bilbao.edu.mx` | `EditorBilbao25` | usuario | `redaccion` (artículo/noticia en revisión) |
| `carlos.ramirez@bilbao.edu.mx` | `EditorBilbao25` | usuario | `redaccion` (artículo/noticia rechazado con feedback) |

> **Roles:** solo existen `administrador` (**Admin** en la UI, acceso a todos los módulos) y
> `usuario` (**Usuario**, acceso solo a los módulos listados en la columna `modulos`). El antiguo rol
> `editor` fue renombrado a `usuario`; conserva el mismo flujo de revisión editorial en Redacción.

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
| `/dashboard` | `BlogController::home` | Home: tarjetas de módulos permitidos + widget "Próximos cumpleaños" |
| `/dashboard/redaccion` | `BlogController::redaccion` | **Redacción** (dashboard analítico; requiere módulo `redaccion`) |
| `/dashboard/articulos*`, `/dashboard/categorias*`, `/dashboard/noticias*`, `/dashboard/revisiones`, `/dashboard/testimoniales`, `/dashboard/autores`, `/dashboard/notificaciones` | varios | Pertenecen a **Redacción** |
| `/dashboard/suplencias*` | `BlogController::suplencias`, `crearSuplencia`, `editarSuplencia`, `eliminarSuplencia` | **Suplencias** — CRUD completo (requiere módulo `suplencias`) |
| `/dashboard/suplencias/buscar-colaboradores` | `BlogController::buscarColaboradores` | Endpoint JSON de autocompletado (ausente/suplente desde `usuarios`) |
| `/dashboard/usuarios*` | varios | **Usuarios** (requiere módulo `usuarios`) |
| `/dashboard/usuarios/cumpleanos` | `BlogController::cumpleanos` | Calendario de cumpleaños (módulo Usuarios) |

**Módulo Suplencias:** modelo `Suplencia` (`listar($filtros)` con JOIN a usuarios, `conteos()`), vistas en
`views/blog/suplencias/` (`index` con búsqueda inteligente en vivo + filtros; `_form.php` compartido por
`crear`/`editar` con **pickers autocompletados** que consultan el endpoint JSON). El home `/dashboard`
muestra un **calendario interactivo** (`.bilbao-cal`) junto a los próximos cumpleaños.

### Protección y permisos

- `requireAuth()` redirige a `/` si `$_SESSION['blog_usuario']` está vacío. La sesión guarda
  `['id','nombre','rol','avatar','modulos']`.
- `puede(string $modulo)` → `true` si el rol es `administrador`, o si el módulo está en el CSV `modulos`.
- `requireModulo(string $modulo)` → guard de entrada a cada módulo; redirige a `/dashboard` si no tiene acceso.
- `requireAdmin()` sigue reservado a operaciones sensibles/destructivas (crear/eliminar usuarios,
  eliminar categorías, aprobar/rechazar contenido). Los usuarios con módulo `redaccion` conservan el
  flujo borrador → revisión → aprobación (antes rol `editor`; en el código la variable `$esEditor`
  significa "no es admin").
- El **sidebar** (`views/blog/_sidebar.php`) es contextual: detecta el módulo activo por la URL y
  muestra solo la navegación de ese módulo + los módulos permitidos en el home. Un botón **Inicio**
  (icono casa) vuelve al home de módulos. El **perfil** y **Ver sitio público** ya no están en el
  sidebar: viven en el topbar (`_topbar-avatar.php` → avatar = perfil, botón flecha = sitio público).
- **Breadcrumb global:** `_sidebar.php` construye la ruta (`Inicio › Módulo › Subpágina`) desde la URL y
  el JS la mueve al `.admin-topbar__left` (ocultando el `.admin-topbar__title`). Vive en un solo lugar.
- **⚠️ Clases `db-stat`/`db-table`/`db-badge`/`db-card` son inline SOLO en `views/blog/dashboard.php`**
  (no están en el CSS compilado). No reutilizarlas en otras vistas del panel: cada vista define sus
  estilos con un `<style>` propio (ver `views/blog/suplencias/index.php` como referencia).

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
