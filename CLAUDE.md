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

| Email | Contraseña | Rol |
|-------|-----------|-----|
| `admin@bilbao.edu.mx` | `Tlalmimilolpan39%` | administrador |
| `alexander.oliva@bilbao.edu.mx` | `ColegioBilbao13` | administrador |
| `maria.gonzalez@bilbao.edu.mx` | `EditorBilbao25` | editor (artículo/noticia en revisión) |
| `carlos.ramirez@bilbao.edu.mx` | `EditorBilbao25` | editor (artículo/noticia rechazado con feedback) |

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

### Imágenes subidas por PHP

Los uploads de blog, noticias y avatares van a `public/build/assets/{blog,noticias,usuarios}/`. Para optimizar y generar versiones WebP:

```bash
npx gulp optimizar   # optimiza todas las imágenes subidas + genera .webp
npx gulp build       # CSS minificado + JS + imágenes de src/ para producción
```

El watcher `npm run dev` también observa los directorios de uploads y los optimiza al detectar nuevos archivos.

---

## Convenciones

- **CSS/HTML:** metodología BEM (`bloque__elemento--modificador`)
- **PHP:** snake_case para variables y métodos; PascalCase para clases
- **Vistas:** archivos `.php` simples; datos pasados como array desde el controller
- **Validaciones:** definidas en cada Model como método `validar()`; errores en `$this->errores`
- **Sesiones:** autenticación del blog en `$_SESSION['blog']`; sitio público en `$_SESSION['usuario']`

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

## Rutas del panel (protección)

Todas las rutas bajo `/dashboard/*` y `/blog/articulos*`, `/blog/categorias*`, `/blog/usuarios*` requieren sesión activa. La guard `requireAuth()` en `BlogController` redirige a `/` si `$_SESSION['blog_usuario']` está vacío. `requireAdmin()` es para operaciones destructivas; los editores pueden crear/editar pero no eliminar usuarios ni categorías.

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
