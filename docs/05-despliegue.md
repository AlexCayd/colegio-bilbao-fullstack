# 5. Guía de despliegue y configuración

> Público: quien tenga que levantar el proyecto desde cero, en local o en el servidor.

---

## 5.1 Requisitos del servidor

### Producción

| Componente | Versión | Nota |
|---|---|---|
| **Windows Server + IIS** | IIS 8.5+ | Con el módulo **URL Rewrite** instalado. Sin él, ninguna ruta funciona salvo la raíz |
| **PHP** | 8.1 mínimo (8.2–8.4 recomendado) | Como FastCGI. El entorno de desarrollo actual usa 8.5 |
| **MySQL / MariaDB** | MySQL 8.0+ o MariaDB 10.6+ | |
| **Composer** | 2+ | Solo para instalar; no hace falta que quede en el servidor |
| **Node.js** | **No se necesita en producción** | Los assets se compilan antes de subir |

### Extensiones de PHP

Obligatorias: `mysqli`, `mbstring`, `json`, `pcre`, `session`, `fileinfo`.
Recomendadas: `openssl` (SMTP con TLS), `gd` (procesado de imágenes), `iconv` (acentos al importar
el CSV de horarios).

> **PDO no se usa.** No hace falta `pdo_mysql`.

```bash
php -m | grep -E '^(mysqli|mbstring|json|fileinfo|openssl|gd|iconv)$'
```

### Ajustes de `php.ini` — obligatorios

Los justificantes de ausencia admiten hasta **50 MB** (`Suplencia::MAX_JUSTIFICANTE_MB`), muy por
encima de los valores por defecto de PHP:

```ini
upload_max_filesize = 52M     ; por defecto 2M
post_max_size       = 56M     ; por defecto 8M — debe superar a upload_max_filesize
max_execution_time  = 120     ; la importación de CSV puede tardar
memory_limit        = 256M
date.timezone       = America/Mexico_City
```

> Sin estos valores el archivo llega vacío. `subirJustificante()` detecta el caso
> (`UPLOAD_ERR_INI_SIZE`) y lo explica en pantalla en vez de fallar en silencio, pero la subida no
> se completa.

Y en producción, además:

```ini
display_errors  = Off        ; ⚠️ nunca On de cara al público
log_errors      = On
error_log       = C:\inetpub\logs\php\error.log
```

### Ajuste de `web.config` — obligatorio

IIS limita las peticiones a 30 MB por defecto, con lo que los justificantes grandes se rechazan
**antes** de llegar a PHP:

```xml
<system.webServer>
  <security>
    <requestFiltering>
      <requestLimits maxAllowedContentLength="58720256" /> <!-- 56 MB -->
    </requestFiltering>
  </security>
</system.webServer>
```

### Permisos de escritura

La cuenta del grupo de aplicaciones de IIS (`IIS_IUSRS`) necesita **escritura** en:

```
public/build/assets/blog/
public/build/assets/noticias/
public/build/assets/usuarios/
public/build/assets/suplencias/
```

El resto del árbol puede ser de solo lectura.

---

## 5.2 Variables de entorno

Archivo: `includes/.env`. Plantilla: [`includes/.env.example`](../includes/.env.example).

Las carga `includes/app.php` con `vlucas/phpdotenv` en modo **`safeLoad()`**: si el archivo no
existe, la aplicación **no revienta** — arranca con los valores por defecto de
`includes/database.php` (`localhost`, usuario y contraseña vacíos) y falla más tarde al conectar,
con un mensaje poco claro. Conviene verificarlo tras cada despliegue.

| Variable | Obligatoria | Quién la lee | Descripción |
|---|---|---|---|
| `DB_HOST` | **Sí** | `includes/database.php` | Servidor MySQL. Por defecto `localhost` |
| `DB_PORT` | No | `includes/database.php` | Por defecto `3306` |
| `DB_NAME` | **Sí** | `includes/database.php` | Nombre de la base |
| `DB_USER` | **Sí** | `includes/database.php` | |
| `DB_PASS` | **Sí** | `includes/database.php` | |
| `HOST` | Sí, si se usa correo | `classes/Email.php` | URL base con la que se construyen los enlaces de confirmación y restablecimiento. **Sin barra final** |
| `EMAIL_HOST` | Solo con registro público | `classes/Email.php` | Servidor SMTP |
| `EMAIL_PORT` | Solo con registro público | `classes/Email.php` | |
| `EMAIL_USER` | Solo con registro público | `classes/Email.php` | |
| `EMAIL_PASS` | Solo con registro público | `classes/Email.php` | |

> **El panel de colaboradores no envía ni un correo.** Las contraseñas las asigna un administrador
> desde Usuarios. Las cuatro variables `EMAIL_*` afectan **solo** al flujo público
> `/registro` · `/olvide` · `/reestablecer`. Si el sitio no ofrece registro público, pueden quedar
> vacías sin romper nada.

Ejemplo de producción:

```dotenv
DB_HOST=localhost
DB_PORT=3306
DB_NAME=colegiobilbao
DB_USER=bilbao_app
DB_PASS=<contraseña generada, no reutilizada>

HOST=https://www.colegiobilbao.edu.mx

EMAIL_HOST=smtp.tuproveedor.com
EMAIL_PORT=587
EMAIL_USER=no-reply@colegiobilbao.edu.mx
EMAIL_PASS=<contraseña de aplicación>
```

> **Usuario de base de datos dedicado.** No usar `root`. Basta con:
> ```sql
> CREATE USER 'bilbao_app'@'localhost' IDENTIFIED BY '<contraseña>';
> GRANT SELECT, INSERT, UPDATE, DELETE ON colegiobilbao.* TO 'bilbao_app'@'localhost';
> ```
> `CREATE` / `DROP` / `ALTER` solo hacen falta al cargar los archivos SQL, y eso se hace a mano con
> una cuenta administrativa.

### ⚠️ Secretos versionados — acción pendiente

**`includes/.env` e `includes/.env_deploy` están trackeados en git.** El `.gitignore` original solo
excluía `node_modules/` y `vendor`, así que las credenciales reales de base de datos y SMTP se
commitearon y **siguen en el historial**, accesibles para cualquiera que tenga el repositorio.

El `.gitignore` ya está corregido, pero eso **solo evita commits futuros**. La remediación completa
tiene tres pasos y hay que hacerlos en orden:

```bash
# 1. Sacarlos del índice conservando los archivos en disco
git rm --cached includes/.env includes/.env_deploy
git commit -m "Dejar de versionar los archivos .env"
```

```
2. ROTAR las credenciales expuestas. Es el paso importante y no es opcional:
   · cambiar la contraseña del usuario MySQL de producción
   · cambiar la contraseña de la cuenta SMTP
   Mientras no se roten, el historial de git sigue siendo una copia válida de ellas.
```

```
3. (Opcional) Purgar el historial con git filter-repo o BFG Repo-Cleaner.
   Reescribe los hashes de todos los commits: hay que coordinarlo con todo el
   equipo. Con el paso 2 hecho, el riesgo residual es bajo, así que suele
   bastar con los dos primeros pasos.
```

---

## 5.3 Entorno de desarrollo local

### Instalación completa

```bash
# 1. Código y dependencias
git clone <repo-url> colegio-bilbao
cd colegio-bilbao
composer install
npm install

# 2. Configuración
cp includes/.env.example includes/.env
#    editar includes/.env con las credenciales locales

# 3. Base de datos
mysql -u root -p -e "CREATE DATABASE colegiobilbao CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p colegiobilbao < database/database.sql
mysql -u root -p colegiobilbao < database/development/development.sql

# 4. Assets
npm run dev            # deja el watcher corriendo en esta terminal

# 5. Servidor (en otra terminal)
php -S localhost:3000 dev-server.php
```

Abrir [http://localhost:3000](http://localhost:3000); el panel está en `/login`. Las cuentas del
seed y sus contraseñas están en [`database/credenciales.md`](../database/credenciales.md).

### ⚠️ El router file no es opcional

```bash
php -S localhost:3000 dev-server.php     # ✅
php -S localhost:3000                    # ❌ el sitio se pinta sin CSS ni JS
```

Los assets de `/build/*` **no existen físicamente** en la raíz: los sirve un shim dentro de
`index.php`. El servidor embebido solo cae al front controller cuando la URI *no* parece un
archivo, y `/build/css/app.css` tiene extensión, así que nunca llega. `dev-server.php` replica la
condición `IsFile` de `web.config`, veta las carpetas sensibles y manda todo lo demás a
`index.php`.

> No renombrarlo a `router.php`: en Windows el sistema de archivos es insensible a mayúsculas y
> chocaría con `Router.php`.

### Verificación

| Comprobación | Qué debe pasar |
|---|---|
| `http://localhost:3000` | La portada carga **con estilos** |
| `http://localhost:3000/build/css/app.css` | Devuelve CSS, no un 404 |
| `http://localhost:3000/login` | Formulario de acceso con el fondo del bosque animado |
| Iniciar sesión con una cuenta del seed | Llega al home de módulos |
| `http://localhost:3000/diagnostico.php` | Todos los indicadores en verde |

---

## 5.4 Diferencias entre entornos

| Aspecto | Desarrollo | Producción |
|---|---|---|
| Servidor web | `php -S` + `dev-server.php` | IIS + `web.config` |
| Reparto de estáticos | `dev-server.php` (PHP) | Reglas de URL Rewrite (IIS) |
| Datos | `development.sql` — claustro real **más** cuentas de prueba, una semana de horarios, ~90 suplencias y contenido ficticio | `deploy.sql` — solo claustro real, catálogos y contenido publicado |
| Horarios | Precargados en el seed | Se cargan por **CSV** desde el panel |
| Suplencias, eventos, noticias | Ficticios en el seed | Los genera el propio panel |
| CSS | `gulp css` — expandido, con sourcemaps | `gulp cssProd` — minificado |
| JS | Bundles concatenados y minificados (igual en ambos) | Ídem |
| `display_errors` | `On` | **`Off`** |
| Node.js | Necesario (watcher) | No se instala |
| `diagnostico.php`, `informacion.php` | Útiles | **Eliminados** |
| Contraseñas | Las del seed | Rotadas en el primer arranque |

> **Nunca cargar `development.sql` en producción.** Crearía cuentas de prueba con contraseñas
> conocidas y públicas.

---

## 5.5 Despliegue a producción

### Lista de comprobación previa

- [ ] `composer install --no-dev`
- [ ] `npx gulp build` — CSS minificado y ambos bundles
- [ ] `includes/.env` de producción preparado (**no** el de desarrollo)
- [ ] `php.ini` con los límites de subida ajustados
- [ ] `web.config` con `maxAllowedContentLength`
- [ ] `display_errors = Off`
- [ ] `diagnostico.php` e `informacion.php` **eliminados**
- [ ] Contraseñas del seed rotadas
- [ ] Respaldo de la base de datos actual, si es una actualización

### Procedimiento — primer despliegue

1. **Publicar el código** en la raíz del sitio IIS. Subir todo **excepto** `node_modules/`, `src/`,
   `horarios_grupos/`, `.git/` y `database/development/`.
2. **Comprobar el módulo URL Rewrite** de IIS. Sin él solo funciona la raíz.
3. **Crear la base de datos y el usuario dedicado** (ver §5.2).
4. **Cargar el esquema y los datos:**
   ```bash
   mysql -u root -p colegiobilbao < database/database.sql
   mysql -u root -p colegiobilbao < database/deploy/deploy.sql
   ```
5. **Configurar `includes/.env`** con las credenciales reales.
6. **Dar permisos de escritura** a `IIS_IUSRS` sobre las cuatro carpetas de subidas.
7. **Verificar con `/diagnostico.php`** y después **eliminarlo**, junto con `informacion.php`.
8. **Entrar al panel y cambiar las contraseñas** de todas las cuentas del seed.
9. **Cargar los horarios** desde `/dashboard/horarios/importar` (CSV de 7 columnas — formato en el
   [manual de usuario](06-manual-usuario.md#54-importar-horarios-por-csv)).

### Procedimiento — actualización

```bash
# 1. Respaldo (base de datos Y archivos subidos)
mysqldump -u root -p --single-transaction colegiobilbao > backup-$(date +%F).sql
#    copiar además public/build/assets/{blog,noticias,usuarios,suplencias}/

# 2. Compilar en local
composer install --no-dev
npx gulp build

# 3. Subir, SIN pisar:
#    · includes/.env
#    · public/build/assets/{blog,noticias,usuarios,suplencias}/   ← subidas de usuarios

# 4. Si el esquema cambió, aplicar el ALTER documentado en database/CLAUDE.md
#    (nunca re-ejecutar database.sql sobre una base con datos reales: los borra)

# 5. Verificar
```

> ⚠️ **`database.sql` empieza con `DROP TABLE`.** Re-ejecutarlo sobre producción **destruye todos
> los datos**. Para actualizar una base con datos reales se aplica a mano el `ALTER` puntual, que
> se documenta como comentario en [`database/CLAUDE.md`](../database/CLAUDE.md).

### Verificación posterior

| Comprobación | Resultado esperado |
|---|---|
| Portada pública | Carga con estilos e imágenes |
| `/login` | Acceso correcto con una cuenta real |
| `/dashboard` | Home de módulos, calendario y cumpleaños |
| `/dashboard/horarios/profesor` | La rejilla pinta las clases |
| Subir un justificante de ~40 MB | Se completa sin error |
| `/build/css/app.css` | Sirve el CSS |
| Una página inexistente | 404 propio del sitio, no el de IIS |

---

## 5.6 Resolución de problemas

| Síntoma | Causa probable | Solución |
|---|---|---|
| El sitio se ve **sin estilos** en local | Se arrancó `php -S` sin `dev-server.php` | Arrancar con el router file |
| El sitio se ve sin estilos **en producción** | Falta el módulo URL Rewrite de IIS, o `public/build/` no se subió | Instalar el módulo; verificar `/build/css/app.css` |
| «Error: No se pudo conectar a MySQL» | `includes/.env` ausente o con credenciales incorrectas | Revisar el archivo; comprobar que el usuario tiene permisos |
| *Failed opening required* al arrancar | Falta `vendor/` | `composer install` |
| Todas las rutas dan 404 salvo la raíz | URL Rewrite ausente o `web.config` no aplicado | Instalar el módulo; verificar que el `web.config` está en la raíz del sitio |
| El justificante «se sube» pero llega vacío | `upload_max_filesize` / `post_max_size` / `maxAllowedContentLength` por debajo de 50 MB | Ajustar los tres valores (§5.1) |
| Fechas desplazadas un día | Zona horaria del servidor | `date.timezone = America/Mexico_City`. `includes/app.php` ya la fija en runtime |
| Acentos como `Ã¡` | Colación de la base o de la conexión | Base en `utf8mb4`; `includes/database.php` ya hace `set_charset('utf8mb4')` |
| Un cambio de SCSS no se ve | El watcher no corre, o se editó `public/build/` | `npm run dev`; editar siempre en `src/` |
| Una función `onclick=` da «is not defined» | No se expuso en `window` | `window.miFuncion = miFuncion;` dentro del módulo |
| El importador de CSV falla con `Duplicate entry` | Un grupo ya ocupado por un profesor que **no** viene en el archivo | El importador valida esto y lo explica; si aparece el error crudo, revisar la fila señalada |
| No llegan los recordatorios de cobertura | Nadie ha entrado al panel | Es el comportamiento esperado: no hay cron, el disparador es la carga de `/dashboard` |

### Herramientas de diagnóstico

`diagnostico.php` comprueba assets compilados, reescritura de `/build/`, extensiones de PHP,
presencia de `vendor/` y de `includes/.env`, y la conexión a la base.
`informacion.php` es un `phpinfo()` extendido.

> **Los dos deben eliminarse antes de producción.** Exponen rutas del sistema de archivos,
> extensiones instaladas y detalles de configuración del servidor.
