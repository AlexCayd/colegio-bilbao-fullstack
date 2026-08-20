# Colegio Bilbao — Sitio institucional + Intranet

Sitio web público del **Colegio Bilbao** e intranet de colaboradores (suplencias, horarios,
intercambios de clase, eventos y redacción). PHP 8 con MVC propio, MySQL, SCSS compilado con
Gulp, desplegado sobre Apache en Hostinger (Linux).

---

## Documentación

La documentación completa vive en [`docs/`](docs/):

| Documento | Para quién |
|---|---|
| [Arquitectura del sistema](docs/01-arquitectura.md) | Desarrollo — routing, MVC, ciclo de petición, assets |
| [Estándares de código](docs/02-estandares-codigo.md) | Desarrollo — PHPDoc, convenciones, phpDocumentor |
| [Dependencias](docs/03-dependencias.md) | Desarrollo / sistemas — versiones exactas |
| [Base de datos](docs/04-base-de-datos.md) | Desarrollo / DBA — diagrama ER y diccionario de datos |
| [Despliegue y configuración](docs/05-despliegue.md) | Sistemas — requisitos, `.env`, entornos |
| [Manual de usuario](docs/06-manual-usuario.md) | Personal del colegio — roles, flujos y FAQ |

Además, [`CLAUDE.md`](CLAUDE.md) recoge las decisiones de diseño y sus porqués (por qué la
jornada es por nivel, por qué `horarios` perdió sus UNIQUE, etc.), y
[`database/CLAUDE.md`](database/CLAUDE.md) las reglas de los archivos SQL.

---

## Stack tecnológico

| Capa | Tecnología |
|------|-----------|
| Backend | PHP 8.1+, MVC propio, PSR-4 vía Composer |
| Base de datos | MySQL 8 / MariaDB 10.6+ (MySQLi, `utf8mb4`) |
| Frontend CSS | SCSS con metodología BEM, compilado con Gulp |
| Frontend JS | Módulos vanilla en dos bundles (público / panel) |
| Librerías de cliente | GSAP 3.12, Three.js r128, Chart.js 4.4, Font Awesome 6.1 (por CDN) |
| Build | Gulp 4, Sass, Terser, imagemin, WebP y AVIF |
| Email | PHPMailer 6 sobre SMTP |
| Servidor | Apache + PHP-FPM (`.htaccess`, `.user.ini`) — hosting compartido Hostinger |

---

## Puesta en marcha local

### 1. Dependencias

```bash
composer install
npm install
```

### 2. Configuración

```bash
cp includes/.env.example includes/.env
```

Editar `includes/.env` con las credenciales de la base de datos. Detalle de cada variable en
[docs/05-despliegue.md](docs/05-despliegue.md#variables-de-entorno).

### 3. Base de datos

```bash
mysql -u root -p -e "CREATE DATABASE colegiobilbao CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p colegiobilbao < database/database.sql                  # estructura
mysql -u root -p colegiobilbao < database/development/development.sql   # datos de prueba
```

Siempre `database.sql` primero y después **un solo** archivo de datos: `development.sql` en local,
`deploy.sql` en producción. Las cuentas del seed están en
[`database/credenciales.md`](database/credenciales.md).

### 4. Compilar assets y levantar el servidor

```bash
npm run dev                              # watch de SCSS, JS e imágenes
php -S localhost:3000 dev-server.php     # ⚠️ el router file es obligatorio
```

> **El `dev-server.php` no es opcional.** Los assets de `/build/*` no existen físicamente en la
> raíz: los sirve un shim dentro de `index.php`. Sin el router file, `php -S` devuelve 404 en
> todos ellos y el sitio se pinta sin CSS ni JS.

Abrir [http://localhost:3000](http://localhost:3000). El panel está en `/login`.

---

## Estructura del proyecto

```
colegio-bilbao/
├── index.php                  Front controller: shim de /build/* + tabla de rutas
├── Router.php                 Router propio (GET/POST, patrones {param}, 3 layouts)
├── dev-server.php             Router file del servidor embebido de PHP
├── .htaccess                  Reparto de peticiones en Apache + denegación de carpetas
├── .user.ini                  Límites de PHP en hosting compartido (subidas de 50 MB)
├── controllers/               EstaticasController · AuthController · BlogController
├── models/                    ActiveRecord + 18 modelos de dominio
├── classes/Email.php          Envoltorio de PHPMailer
├── includes/                  app.php (bootstrap) · database.php · funciones · helpers
├── views/                     Plantillas PHP (layout, layout-admin, estaticas/, blog/…)
├── src/scss/ · src/js/        Fuentes de estilos y scripts
├── public/build/              Salida compilada — NO editar a mano
├── database/                  database.sql + development/ + deploy/
├── docs/                      Documentación del proyecto
└── horarios_grupos/           PDFs de horario originales (sin trackear en git)
```

---

## Comandos frecuentes

```bash
# Desarrollo
php -S localhost:3000 dev-server.php   # servidor local
npm run dev                            # watch de assets

# Compilación
npx gulp css        # solo CSS
npx gulp js         # ambos bundles JS
npx gulp optimizar  # optimiza uploads y genera WebP
npx gulp build      # compilación de producción

# Documentación de la API PHP
vendor/bin/phpdoc   # genera docs/api/ a partir de los bloques PHPDoc
```

---

## Antes de desplegar a producción

1. `composer install --no-dev` y `npx gulp build`.
2. Cargar `database/database.sql` + `database/deploy/deploy.sql` (**nunca** `development.sql`).
3. Configurar `includes/.env` con credenciales reales.
4. Subir `.htaccess` y `.user.ini` (son dotfiles: muchos clientes FTP los ocultan por defecto).
   Sin el primero **ninguna ruta funciona salvo la raíz** y las carpetas de código quedan
   servibles; sin el segundo los justificantes de más de 2 MB llegan vacíos.
5. Dar permiso de escritura a `public/build/assets/{blog,noticias,usuarios}/`,
   `storage/justificantes/` y `storage/fuentes-pdf/`.
6. Subir también `src/fonts/` — Dompdf lee los TTF de ahí para el PDF de «Mi horario».
7. **Eliminar `diagnostico.php` e `informacion.php`** — exponen configuración del servidor.
8. Cambiar las contraseñas del seed.

Procedimiento completo en [docs/05-despliegue.md](docs/05-despliegue.md).
