# 3. Dependencias

> Versiones verificadas contra `composer.lock` y `package.json` el **11 de agosto de 2026**.
> `composer.json` declara rangos; la columna «instalada» es la versión exacta bloqueada, que es
> la que se despliega.

---

## 3.1 Runtime

| Componente | Versión requerida | Notas |
|---|---|---|
| **PHP** | 8.1 mínimo · 8.5 en el entorno de desarrollo actual | El código usa `str_starts_with()`, `match`, tipos de unión y propiedades tipadas |
| **MySQL** | 8.0+ | O **MariaDB** 10.6+ |
| **Composer** | 2+ | Solo para el autoloader y tres librerías |
| **Node.js** | 16+ | Solo en desarrollo: compila los assets. **No** hace falta en el servidor de producción |
| **IIS** | Con el módulo **URL Rewrite** | Producción. En desarrollo basta el servidor embebido de PHP |

### Extensiones de PHP

| Extensión | Obligatoria | Para qué |
|---|---|---|
| `mysqli` | **Sí** | Única capa de acceso a datos. Sin ella la aplicación no arranca |
| `mbstring` | **Sí** | Manejo de UTF-8; requerida además por `vlucas/phpdotenv` |
| `json` | **Sí** | Todos los endpoints del panel y las islas de datos hacia JS |
| `pcre` | **Sí** | Enrutamiento por patrones, validaciones, *slugs* |
| `session` | **Sí** | Autenticación del panel y del sitio público |
| `fileinfo` | **Sí** | Validación de tipo real en las subidas (justificantes, imágenes) |
| `openssl` | Sí, si se usa correo | SMTP con TLS en PHPMailer |
| `gd` | Recomendada | Requerida por `intervention/image`; sin ella no se procesan imágenes |
| `iconv` | Recomendada | Normalización de acentos al resolver catálogos del CSV de horarios |

> **PDO no se usa.** El proyecto accede a MySQL exclusivamente por MySQLi. No hace falta
> instalar `pdo_mysql`.

Comprobación rápida en el servidor:

```bash
php -m | grep -E '^(mysqli|mbstring|json|fileinfo|openssl|gd|iconv)$'
```

`diagnostico.php` hace la misma comprobación por navegador — recordar **eliminarlo** después.

---

## 3.2 Dependencias PHP (Composer)

### Directas

| Paquete | Declarada | Instalada | Uso real en el proyecto |
|---|---|---|---|
| `phpmailer/phpmailer` | `^6.5` | **6.12.0** | `classes/Email.php`. Solo el flujo público de cuentas: confirmación de registro y restablecimiento de contraseña. **El panel no envía correo** |
| `vlucas/phpdotenv` | `^5.4` | **5.6.3** | `includes/app.php`, en modo `safeLoad()`. Carga `includes/.env` |
| `intervention/image` | `^2.7` | **2.7.2** | Procesamiento de imágenes subidas (avatares, portadas) |

### Transitivas

Instaladas automáticamente; no se invocan desde el código propio.

| Paquete | Versión | Viene de |
|---|---|---|
| `graham-campbell/result-type` | v1.1.4 | phpdotenv |
| `phpoption/phpoption` | 1.9.5 | phpdotenv |
| `symfony/polyfill-ctype` | v1.37.0 | phpdotenv |
| `symfony/polyfill-mbstring` | v1.38.2 | phpdotenv |
| `symfony/polyfill-php80` | v1.37.0 | phpdotenv |
| `guzzlehttp/psr7` | 2.11.0 | intervention/image |
| `psr/http-message` | 2.0 | guzzle |
| `psr/http-factory` | 1.1.0 | guzzle |
| `ralouphie/getallheaders` | 3.0.3 | guzzle |
| `symfony/deprecation-contracts` | v3.7.0 | guzzle |

### Autoload PSR-4

Definido en `composer.json`; no hay paquetes propios publicados:

```json
{
  "MVC\\":         "./",
  "Controllers\\": "./controllers",
  "Model\\":       "./models",
  "Classes\\":     "./classes"
}
```

### Instalación

```bash
composer install               # desarrollo
composer install --no-dev      # producción (hoy es equivalente: no hay require-dev)
```

> **Nota sobre `composer.json`.** Los campos `name`, `description` y `authors` conservan los
> valores de la plantilla original (`juandelatorre/devwebcamp`) sobre la que se construyó el
> proyecto. No afecta al funcionamiento —el paquete nunca se publica— pero conviene corregirlo
> para no confundir a quien llegue nuevo.

---

## 3.3 Dependencias Node (build)

Todas son `devDependencies`: **el servidor de producción no necesita Node**. Se compila en local
o en CI y se sube `public/build/` ya generado.

| Paquete | Versión | Función |
|---|---|---|
| `gulp` | ^4.0.2 | Orquestador de tareas |
| `sass` | ^1.41.1 | Compilador Dart Sass |
| `gulp-sass` | ^5.0.0 | Puente Gulp ↔ Sass |
| `gulp-postcss` | ^9.0.0 | Cadena PostCSS |
| `gulp-autoprefixer` | ^8.0.0 | Prefijos de proveedor |
| `autoprefixer` | *(transitiva)* | Motor de prefijos |
| `cssnano` | ^5.1.10 | Minificación de CSS en producción |
| `gulp-sourcemaps` | ^3.0.0 | Mapas de fuente en desarrollo |
| `gulp-terser` | ^2.1.0 | Minificación de JS |
| `terser` | ^5.14.2 | Motor de minificación |
| `gulp-concat` | ^2.6.1 | Concatenación de los módulos en cada bundle |
| `gulp-rename` | ^2.0.0 | Renombrado de salidas |
| `gulp-imagemin` | ^7.1.0 | Optimización de imágenes |
| `gulp-webp` | ^4.0.1 | Generación de `.webp` |
| `gulp-avif` | ^1.1.1 | Generación de `.avif` |
| `gulp-cache` | ^1.1.3 | Caché de imágenes ya optimizadas |
| `gulp-clean` | ^0.4.0 | Limpieza de directorios de salida |
| `gulp-plumber` | ^1.2.1 | Evita que un error de Sass mate el watcher |
| `gulp-notify` | ^4.0.0 | Notificaciones de escritorio al compilar |

### Tareas expuestas

```bash
npm run dev            # = gulp dev  → watch de SCSS, JS, imágenes y uploads
npx gulp css           # CSS de desarrollo (con sourcemaps)
npx gulp cssProd       # CSS minificado
npx gulp js            # ambos bundles
npx gulp jsAdmin       # solo el bundle del panel
npx gulp imagenes      # optimizar imágenes de src/
npx gulp versionWebp   # generar .webp
npx gulp versionAvif   # generar .avif
npx gulp optimizar     # optimizar las subidas de usuarios + WebP
npx gulp build         # compilación completa de producción
```

> `package.json` conserva `name` y `description` de la plantilla original
> (`devwebcamp-php-mvc-mysql`), igual que `composer.json`.

---

## 3.4 Librerías de cliente (CDN)

**No se instalan con npm ni se empaquetan**: se cargan con etiquetas `<script>` / `<link>` desde
CDN. Esto crea una dependencia de red externa que conviene tener presente.

| Librería | Versión | CDN | Dónde se carga | Para qué |
|---|---|---|---|---|
| **Font Awesome** | 6.1.2 | cdnjs | Layouts (público y panel) | Iconografía de toda la interfaz |
| **GSAP** | 3.12.5 | cdnjs | `views/templates/header.php` y `layout-admin.php` | Animaciones: zona de peligro, calendarios, Comunidad |
| **GSAP ScrollTrigger** | 3.12.5 | cdnjs | Páginas públicas | Animaciones al hacer scroll |
| **Three.js** | r128 | cdnjs | Bajo demanda con `three_js_tag()` | Bosque WebGL del login y del home; partículas de Comunidad |
| **Chart.js** | 4.4.4 | jsDelivr | Tableros de Redacción y Suplencias | Gráficas de estadísticas |

### Degradación

Todos los usos están escritos para **fallar sin romper la página**:

- `BilbaoForest.init()` devuelve `null` sin WebGL o con `prefers-reduced-motion`; el degradado CSS
  del contenedor es el fondo real, así que el hero nunca se ve roto.
- `admin-danger.js` y `cal-anim.js` salen sin hacer nada si no existe `window.gsap`; el estado
  visual completo vive en CSS.
- Sin Chart.js las tarjetas de cifras siguen legibles: solo faltan las gráficas.

### Consideraciones

`three_js_tag()` en `includes/funciones.php` centraliza la URL de Three.js — antes estaba repetida
en cuatro sitios y actualizar la versión obligaba a acordarse de todos. Las demás URLs siguen
escritas a mano en los layouts.

> **Sin *subresource integrity*.** Ninguna etiqueta lleva atributo `integrity`, así que un CDN
> comprometido podría inyectar código. Para un entorno escolar el riesgo es bajo, pero es la
> mejora obvia: añadir `integrity` + `crossorigin`, o alojar las librerías en `public/build/`
> y eliminar la dependencia externa.

---

## 3.5 Actualizar dependencias

```bash
# PHP — ver qué hay desactualizado
composer outdated --direct

# PHP — actualizar dentro de los rangos declarados
composer update

# Node
npm outdated
npm update
```

Tras cualquier actualización de Node hay que **recompilar y verificar los assets**, porque el
resultado se versiona en `public/build/`:

```bash
npx gulp build
```

### Puntos de fricción conocidos

| Actualización | Riesgo |
|---|---|
| `gulp-imagemin` a 8+ | Pasó a ESM puro; el `gulpfile.js` actual usa CommonJS y no cargaría |
| `intervention/image` a 3.x | API incompatible con la 2.x |
| Three.js más allá de r128 | Cambios de API en materiales y geometrías que afectan a `forest.js` |
| Chart.js dentro de la 4.x | Sin problemas conocidos |
| PHP 8.5 → futuras | Vigilar los avisos de obsolescencia de MySQLi |
