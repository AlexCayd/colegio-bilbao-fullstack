# 1. Arquitectura del sistema

> Público: desarrolladores e ingenieros de sistemas que mantengan el proyecto.
> Complemento indispensable: [`CLAUDE.md`](../CLAUDE.md) en la raíz, que documenta **por qué**
> cada decisión es como es. Este archivo documenta **cómo** funciona.

---

## 1.1 Visión general

El proyecto es **una sola aplicación PHP** que sirve dos productos distintos bajo el mismo
dominio:

| Producto | Rutas | Público | Autenticación |
|---|---|---|---|
| **Sitio institucional** | `/`, `/conocenos/*`, `/admisiones/*`, `/noticias`, `/blog`… | Familias, aspirantes, público general | Ninguna (más un registro público opcional en `/cuenta/*`) |
| **Intranet del colegio** | `/dashboard/*` | Personal del colegio | Sesión `$_SESSION['blog_usuario']` |

No hay framework: el routing, el ORM y el sistema de plantillas son código propio. Composer se
usa solo para el autoloader PSR-4 y tres librerías de apoyo (ver
[dependencias](03-dependencias.md)).

```
Navegador
    │
    ▼
.htaccess (Apache)  ó  dev-server.php (php -S)    ← reparto de estáticos vs. dinámico
    │
    ▼
index.php ─── ¿la URI empieza por /build/? ──► sirve el archivo desde public/build/ y termina
    │  no
    ▼
includes/app.php      sesión · zona horaria · Dotenv · funciones · conexión MySQLi
    │
    ▼
Router::comprobarRutas()   busca la URI en la tabla de rutas
    │
    ▼
Controller::metodo($router)    guards de permiso → consulta modelos → prepara datos
    │
    ▼
$router->render() / renderAdmin() / renderBlog()
    │
    ▼
views/<vista>.php  →  $contenido  →  views/layout*.php  →  HTML
```

---

## 1.2 Front controller: `index.php`

`index.php` hace **dos cosas** antes de delegar en el router.

### a) Shim de assets `/build/*`

Los assets compilados viven en `public/build/`, pero las vistas los piden en `/build/…`. Una
función anónima autoejecutada al principio del archivo intercepta esas URIs y sirve el archivo
directamente:

```php
(function() {
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    if (!preg_match('#^/build/(.+)$#', $uri, $m)) return;
    // …resolver, validar y volcar el archivo
})();
```

Tres detalles que no se pueden quitar:

1. **Normalización antes de tocar el disco.** Se resuelve con `realpath()` y se comprueba que el
   resultado siga dentro de `public/build/`. Sin eso, `/build/../../includes/.env` filtraría los
   secretos.
2. **Tabla de MIME types explícita.** Sin `Content-Type` correcto el navegador rechaza el CSS.
3. **Soporte de *range requests*** para `.mp4` y `.webm` (respuesta `206 Partial Content`). Los
   navegadores lo exigen para reproducir vídeo HTML5; sin él, el vídeo del hero no arranca.

### b) Tabla de rutas

El resto del archivo es la declaración de todas las rutas de la aplicación (≈250 líneas), y
la última línea dispara el despacho:

```php
$router->comprobarRutas();
```

---

## 1.3 Enrutamiento (`Router.php`)

Router propio de ~100 líneas. Tres estructuras internas:

| Propiedad | Contenido |
|---|---|
| `$getRoutes` | Mapa `'/ruta/exacta' => callable` para GET |
| `$postRoutes` | Mapa `'/ruta/exacta' => callable` para POST |
| `$getPatterns` | Lista de rutas con parámetros, **solo GET** |
| `$params` | Parámetros capturados de la URI, disponibles en el controlador |

### Declaración

```php
$router->get('/dashboard/suplencias', [BlogController::class, 'suplencias']);
$router->post('/dashboard/suplencias/crear', [BlogController::class, 'crearSuplencia']);
$router->pattern('/blog/{slug}', [BlogController::class, 'verArticulo']);
```

Un `{param}` se traduce a un grupo con nombre (`(?P<slug>[^/]+)`) y el controlador lo recoge en
`$router->params['slug']`.

### Resolución

1. Se extrae la ruta de `REQUEST_URI` con `parse_url(…, PHP_URL_PATH)` — la query string nunca
   participa en el emparejado.
2. Se normaliza la barra final (`/dashboard/` ≡ `/dashboard`), excepto en la raíz.
3. **GET:** primero coincidencia exacta; si no la hay, se recorren los patrones en orden de
   declaración y gana el primero que case.
4. **POST:** solo coincidencia exacta.
5. Sin coincidencia: `404` y `views/errors/404.php`.

> ⚠️ **Los patrones son exclusivos de GET.** `pattern()` solo alimenta `$getPatterns`, así que
> una ruta POST con parámetro en la URI nunca hará match. Por eso todos los POST del panel
> reciben el id en el cuerpo del formulario (`/dashboard/articulos/eliminar` + `id` en el POST) y
> no en la ruta.

> ⚠️ **El orden de declaración importa en los patrones**, no en las rutas exactas (aquellas son
> un mapa por clave, y una ruta declarada dos veces pisa a la anterior en silencio).

### Un mismo método para GET y POST

El patrón habitual en el panel es registrar la misma función en ambos verbos y ramificar dentro
según `$_SERVER['REQUEST_METHOD']`: GET pinta el formulario, POST lo procesa y redirige.

```php
$router->get('/dashboard/usuarios/crear',  [BlogController::class, 'crearUsuario']);
$router->post('/dashboard/usuarios/crear', [BlogController::class, 'crearUsuario']);
```

---

## 1.4 Los tres renderizadores

`Router` expone tres métodos de render. Los tres reciben la vista y un array de datos, y
convierten cada clave del array en una variable local de la plantilla (`extract` manual mediante
variables variables). La diferencia está en el layout que envuelve el resultado:

| Método | Layout | Uso |
|---|---|---|
| `render($vista, $datos)` | `views/layout.php` | Sitio público (header, footer, i18n, SEO) |
| `renderAdmin($vista, $datos)` | `views/layout-admin.php` | Panel de administración |
| `renderBlog($vista, $datos)` | *ninguno* | La vista se incluye tal cual, sin envolver |

Los dos primeros capturan la vista en un buffer (`ob_start()` → `ob_get_clean()`), dejan el
resultado en `$contenido` y luego incluyen el layout, que lo imprime en su hueco.

> `renderBlog()` **no usa buffer**: incluye la vista directamente. Es para páginas que emiten su
> propio documento HTML completo.

### Variables especiales que leen los layouts

| Variable | Efecto |
|---|---|
| `$titulo` | Título de la página / cabecera del panel |
| `$seo_titulo`, `$seo_descripcion`, `$seo_imagen` | Sobrescriben los meta y Open Graph por defecto (solo `layout.php`) |
| `$extra_head` | HTML crudo inyectado en el `<head>` — se usa para cargar Three.js solo donde hace falta |
| `$paginaVista` | Identificador de la vista; se emite como `<body data-page="…">` y es la base del aislamiento de CSS y JS (ver §1.7) |

---

## 1.5 Controladores

Tres clases, todas con **métodos estáticos** que reciben el `Router` como único argumento:

| Controlador | Responsabilidad | Tamaño |
|---|---|---|
| `EstaticasController` | Páginas institucionales, comunidad, noticias públicas, formulario de testimoniales | ~350 líneas, 41 métodos |
| `AuthController` | Registro, login, recuperación de contraseña del **sitio público** (`/cuenta/*`) | ~245 líneas |
| `BlogController` | Todo el panel `/dashboard/*` más el blog público | ~4.200 líneas, 146 métodos |

Un método de controlador típico hace exactamente esto, y en este orden:

```php
public static function grupos(Router $router) {
    self::requireModulo('grupos');                 // 1. guard de permiso

    $router->renderAdmin('blog/grupos/index', [    // 2. consulta a modelos
        'titulo' => 'Grupos',                      // 3. render con los datos
        'grupos' => Grupo::todosConUso(),
    ]);
}
```

Y un método de escritura:

```php
public static function crearGrupo(Router $router) {
    self::requireEscritura('grupos');              // 1. guard (incluye solo-lectura)

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $grupo = new Grupo($_POST);
        $errores = $grupo->validar();              // 2. validación en el modelo
        if (empty($errores)) {
            $grupo->guardar();
            header('Location: /dashboard/grupos?success=1');   // 3. POST → Redirect → GET
            exit;
        }
    }
    $router->renderAdmin(/* … repinta con los errores … */);
}
```

> **Patrón POST → Redirect → GET en todo el panel.** Tras una escritura correcta siempre hay un
> `header('Location: …?success=1')` con un query param, que la vista destino convierte en el
> aviso emergente (`views/blog/_toast.php`). **No hay ni un solo endpoint JSON de escritura**:
> todos los `fetch` del panel son de lectura, salvo los de notificaciones.

### Sistema de permisos

Todos los guards viven en `BlogController` como métodos privados estáticos. Hay dos ejes
independientes que se combinan:

**Eje 1 — rol** (`usuarios.rol`), decide el *alcance*:

| Guard | Regla |
|---|---|
| `requireAuth()` | Hay sesión; si no, redirige a `/` |
| `esAdmin()` | `rol === 'administrador'` |
| `requireAdmin()` | Operaciones destructivas y tableros analíticos |
| `puede($modulo)` | Admin siempre; usuario solo si el módulo está en su CSV `modulos`, o si es transversal (`soporte`) |
| `requireModulo($modulo)` | Puerta de entrada de cada módulo. Rebota a `/dashboard?sinacceso=<modulo>` |
| `modulosDisponibles()` | Lista de módulos del usuario, para el home y el sidebar |

**Eje 2 — tipo de personal** (`usuarios.tipo_personal`), decide *de quién son los datos*:

| Guard | Regla | Para qué |
|---|---|---|
| `puedeCoordinar()` | Admin, `prefecto` o `directivo` | Ver datos de terceros: horarios ajenos, motivos de ausencia, justificantes |
| `imparte()` | Incluye `profesor` | Quién puede faltar a una clase y quién puede cubrirla |
| `soloLectura()` | `directivo` que no es admin | Dirección coordina pero no edita la configuración |
| `requireEscritura($mod)` | `requireModulo` + no ser solo-lectura | Bloquea el POST; la pantalla sí se ve |
| `requireImparte()` | Módulo suplencias + `imparte()` | Pantallas personales (solicitar, mis coberturas) |
| `requireAgendar()` | Módulo suplencias + `puedeCoordinar()` | Endpoints que exponen al claustro entero |

> **Por qué dos ejes.** El rol dice *cuánto* puede tocar alguien; el tipo de personal dice *qué
> papel juega en la operación académica*. Un administrador de sistemas tiene rol `administrador`
> pero no imparte nada: no debe aparecer como candidato a suplente ni tener «mis coberturas».
> Un prefecto coordina las ausencias del claustro pero no tiene ausencias propias.

Las vistas espejan estas reglas con las funciones de `views/blog/_modulos.php`
(`blog_modulos_coordina()`, `blog_modulos_imparte()`, `blog_modulos_solo_lectura()`), que sirven
para **ocultar** controles. El guard real siempre está en el controlador: ocultar un botón no es
una medida de seguridad.

---

## 1.6 Modelos y ORM

### `ActiveRecord`

Clase base de ~190 líneas que implementa el patrón Active Record. Cada modelo declara su tabla y
las columnas que el ORM debe persistir:

```php
class Grupo extends ActiveRecord {
    protected static $tabla      = 'grupos';
    protected static $columnasDB = ['id', 'nombre', 'nivel'];

    public $id;
    public $nombre;
    public $nivel;
}
```

Métodos heredados: `all()`, `find($id)`, `where($col, $val)`, `get($limite)`, `guardar()`
(inserta o actualiza según haya `id`), `crear()`, `actualizar()`, `eliminar()`, `sincronizar()`,
`validar()`, `consultarSQL($query)` y el sistema de alertas
(`setAlerta()` / `getAlertas()`).

### Cuatro limitaciones del ORM que hay que conocer

Son la causa de casi todo el código «raro» de los modelos. Conocerlas evita reintroducir bugs
ya resueltos.

**1. Solo se copian propiedades declaradas.** `crearObjeto()` recorre la fila devuelta y asigna
únicamente las claves que existan como propiedad pública del modelo:

```php
if (property_exists($objeto, $key)) $objeto->$key = $value;
```

> Cualquier **alias de SQL** (`MONTH(fecha) AS mes`, `u.nombre AS ausente_nombre`, `COUNT(*) AS
> total_articulos`) debe declararse como `public $mes;` en el modelo o **se pierde en silencio**,
> sin error ni aviso.

**2. No sabe escribir `NULL`.** `crear()` y `actualizar()` envuelven todos los valores en comillas
simples, así que un `null` se guarda como cadena vacía `''`. En una columna `DATE` o en una FK eso
es un error o un valor falso. Los modelos que necesitan `NULL` real **sobrescriben `guardar()`** o
persisten ese campo con un `UPDATE` aparte:

| Modelo | Solución |
|---|---|
| `Suplencia`, `SuplenciaHora`, `Swap`, `Horario`, `Evento` | `guardar()` sobrescrito con construcción manual del SQL |
| `UsuarioBlog` | `guardarFechaNacimiento()` y `guardarAtributos()`, llamados **después** de `guardar()` |

**3. Consultas por concatenación, no por sentencias preparadas.** La protección es
`mysqli::escape_string()` sobre los valores y *cast* a `(int)` sobre los identificadores. Es
efectiva mientras se aplique **siempre**:

```php
// Correcto
$safe = self::$db->escape_string($q);
$id   = (int) $id;
$query = "SELECT * FROM usuarios WHERE nombre LIKE '%{$safe}%' AND id = {$id}";
```

> **Regla obligatoria para código nuevo:** ningún valor procedente de `$_GET`, `$_POST`, `$_FILES`
> o de la sesión entra en una cadena SQL sin pasar por `escape_string()` (strings) o `(int)`
> (numéricos). Un identificador que se use como nombre de columna o dirección de orden debe
> validarse contra una **lista blanca**, porque `escape_string()` no protege ahí.

**4. `sincronizar()` ignora los nulos.** Al hidratar un objeto desde `$_POST`, un valor `null` no
sobrescribe la propiedad existente. Es lo que permite editar un registro con un formulario
parcial, pero también significa que **no se puede vaciar un campo** pasándole `null`: hay que
pasar cadena vacía y normalizarla en el modelo.

### Validación

Cada modelo implementa `validar(): array`. Acumula mensajes con `setAlerta('error', …)` y
devuelve el array de alertas; vacío = válido. Varios modelos ofrecen variantes según el contexto
(`UsuarioBlog::validar()` para el alta, `validarEdicion()` sin exigir contraseña,
`validarPerfil()` para el autoservicio).

Las **normalizaciones también viven en el modelo**, no en el controlador, y se ejecutan dentro de
`validar()`. Es deliberado: así un POST manipulado queda cubierto por el mismo camino que el
formulario. Ejemplos en `UsuarioBlog`: `normalizarModulos()` filtra contra la lista blanca
`MODULOS_ASIGNABLES`, `normalizarTipoPersonal()` aplica la exclusividad de `prefecto`/`directivo`
y `normalizarNiveles()` fuerza `NULL` a quien no sea profesor.

### Inventario de modelos

| Modelo | Tabla | Papel |
|---|---|---|
| `ActiveRecord` | — | Clase base del ORM |
| `UsuarioBlog` | `usuarios` | Personal del colegio: roles, módulos, tipo de personal, niveles |
| `Usuario` | `usuarios` | Registro público de familias (**heredado**, flujo `/cuenta/*`) |
| `Periodo` | `periodos` | Jornada por nivel; aritmética de solapamiento y ejes de rejilla |
| `Materia`, `Grupo`, `Aula`, `LugarGuardia` | homónimas | Catálogos académicos |
| `Horario` | `horarios` | Rejilla semanal, detección de choques, coteaching |
| `Suplencia` | `suplencias` | Ausencia de un profesor en una fecha; ciclo del justificante |
| `SuplenciaHora` | `suplencia_horas` | Cobertura hora a hora + **algoritmo de sugerencia** |
| `Swap` | `swap_clases` | Intercambio puntual de clases |
| `Evento` | `eventos` | Calendario institucional, audiencia y niveles |
| `Notificacion` | `notificaciones` | Avisos transversales del panel |
| `Articulo`, `Categoria`, `Noticia`, `CategoriaNoticia`, `Testimonial` | homónimas | Módulo de Redacción |

---

## 1.7 Vistas y aislamiento por página

Las vistas son plantillas PHP planas: **sin lógica de negocio, sin `<style>` y sin `<script>`
embebidos**. Todo el CSS y el JS vive en `src/` y se compila con Gulp.

### El mecanismo `data-page`

Cada vista declara su identificador en la primera línea:

```php
<?php $paginaVista = 'blog-usuarios-index'; ?>
```

El layout lo emite como `<body data-page="blog-usuarios-index">`, y con eso:

- **CSS** — el partial `src/scss/admin/_blog-usuarios-index.scss` envuelve todas sus reglas en
  `body[data-page="blog-usuarios-index"] { … }`. Varias vistas reutilizan nombres de clase
  (`.wysiwyg-editor`, `.at-wrap`) con valores distintos; sin el envoltorio se pisarían. Los
  `@keyframes` y `:root` quedan fuera del scope.
- **JS** — cada módulo abre con una guarda:
  ```js
  if (!document.body || document.body.dataset.page !== 'blog-usuarios-index') return;
  ```

Los módulos de **partials compartidos** (`_sidebar`, `_form`, `layout-admin`, `header`) no llevan
guarda de página: se activan por la existencia de sus elementos en el DOM, porque se usan en
muchas vistas.

### Checklist para una vista nueva

1. `<?php $paginaVista = '<nombre>'; ?>` en la primera línea.
2. `src/scss/<admin|publico>/_<nombre>.scss`, envuelto en `body[data-page="<nombre>"]`.
3. Registrar el partial en el `_index.scss` de esa carpeta.
4. `src/js/<admin|public>/<nombre>.js` con su guarda.
5. Datos de PHP a JS **nunca por interpolación**: usar `data-*` en el elemento o una isla JSON
   (`<script type="application/json" id="…">`) leída con `JSON.parse`.

### Funciones expuestas globalmente

Una función que se invoque desde un `onclick=` inline debe exponerse con
`window.miFuncion = miFuncion;`, o dejará de existir al quedar encapsulada por el bundle.

---

## 1.8 Assets y build

```
src/scss/                              src/js/
├── base/       tokens, mixins         ├── public/ → public/build/js/bundle.min.js
├── estaticas/  páginas públicas       └── admin/  → public/build/js/admin.min.js
├── publico/    1 partial por vista
└── admin/      1 partial por vista
```

| Comando | Efecto |
|---|---|
| `npm run dev` | Watch de SCSS, JS, imágenes y carpetas de subida |
| `npx gulp css` | Solo CSS (con sourcemaps) |
| `npx gulp js` | Ambos bundles |
| `npx gulp jsAdmin` | Solo el bundle del panel |
| `npx gulp optimizar` | Optimiza los uploads y genera `.webp` |
| `npx gulp build` | Compilación de producción: CSS minificado, ambos bundles, imágenes |

> **`public/build/` no se edita a mano jamás**: Gulp lo sobrescribe.

Dos módulos JS viven en `src/js/public/` pero se compilan en **los dos bundles** (declarado en
`gulpfile.js`): `forest.js` (el bosque Three.js, porque el login del panel lo usa) y `cal-anim.js`
(la animación del calendario, porque `.bilbao-cal` existe en Comunidad y en el panel).

---

## 1.9 Servidor y reparto de peticiones

La misma condición —«si el archivo existe, sírvelo; si no, al front controller»— está
implementada dos veces, una por entorno:

| Entorno | Archivo | Mecanismo |
|---|---|---|
| Producción (Apache/Hostinger) | `.htaccess` | `mod_rewrite` con condiciones `!-f` / `!-d`, más denegación explícita de las carpetas de código |
| Desarrollo (`php -S`) | `dev-server.php` | Router file que replica la misma condición en PHP |

Ambos **vetan explícitamente** las mismas carpetas sensibles (`includes/`, `vendor/`, `database/`,
`models/`, `controllers/`, `views/`, `classes/`, `src/`, `node_modules/`, `storage/`) y cualquier
archivo que empiece por punto. **La lista está duplicada a propósito en los dos archivos: si se
toca una, hay que tocar la otra.**

> ⚠️ Hasta agosto de 2026 producción era **IIS con `web.config`**, y ahí la denegación la daba la
> configuración del sitio, no el archivo de reglas. Al pasar a Apache hubo que escribirla: sin
> ella, `GET /includes/.env` se sirve **como texto plano**, porque el catch-all `!-f` no lo captura
> justamente por ser un archivo real.

En producción `/build/*` lo sirve **Apache directamente** (`RewriteRule ^build/(.*)$
public/build/$1`), no el shim PHP de `index.php`, que queda como respaldo. Esa regla va
obligatoriamente **después** del 404 a `build/assets/suplencias/`: al atajar `/build/`, el shim deja
de correr y con él su portazo a los justificantes heredados.

> ⚠️ **No renombrar `dev-server.php` a `router.php`.** En Windows el sistema de archivos es
> insensible a mayúsculas y chocaría con `Router.php`.

Arranque local, siempre con el router file:

```bash
php -S localhost:3000 dev-server.php
```

---

## 1.10 Tareas periódicas sin cron

El proyecto **no tiene cron ni tareas programadas**. Tres procesos de mantenimiento cuelgan de la
carga de una página, con marcas en base de datos que evitan repetirlos:

| Proceso | Disparador | Anti-duplicado |
|---|---|---|
| `recordarCoberturasVencidas()` — avisa al suplente cuya cobertura venció sin confirmar | `BlogController::home()`, es decir, entrar al panel | `suplencia_horas.recordatorio_en` |
| `purgarJustificantes()` — borra los justificantes de ≥30 días | `BlogController::home()` | `suplencias.justificante_resolucion = 'purgado'` |
| `Articulo::publicarProgramados()` / `Noticia::publicarProgramadas()` | Cada visita al blog o a noticias públicas | El propio cambio de `estado` |

> **Consecuencia operativa:** si nadie entra al panel durante una semana, los recordatorios y la
> purga se acumulan y se ejecutan de golpe en la siguiente visita. Es aceptable porque las tres
> operaciones son idempotentes y están indexadas, pero conviene saberlo al depurar «¿por qué no
> llegó el aviso?».

---

## 1.11 Convenciones transversales

| Ámbito | Convención |
|---|---|
| PHP | `snake_case` para variables y métodos de dominio; `PascalCase` para clases; `camelCase` en los métodos de controlador y modelo ya existentes |
| CSS / HTML | BEM: `bloque__elemento--modificador` |
| SQL | Nombres de tabla y columna en español y minúsculas; FKs con prefijo `fk_`, índices únicos con `uq_`, índices normales con `idx_` |
| Namespaces | `MVC\` (raíz) · `Controllers\` · `Model\` · `Classes\` |
| Sesiones | Panel en `$_SESSION['blog_usuario']`; sitio público en `$_SESSION['usuario']` |
| Escapado de salida | Función `s()` de `includes/funciones.php` (`htmlspecialchars` con `ENT_QUOTES`) en **toda** interpolación de datos en las vistas |
| Fechas en la UI | `fecha_larga()` de `includes/funciones.php` — «martes 4 de agosto», no `04/08/2026` |
| i18n | Atributos `data-i18n` en el chrome; el diccionario está en `src/js/i18n.js` |

---

## 1.12 Deuda técnica conocida

Inventario honesto de lo que un mantenedor debe saber antes de tocar el código:

| Asunto | Impacto | Nota |
|---|---|---|
| SQL por concatenación en lugar de sentencias preparadas | Riesgo de inyección si alguien olvida escapar | Migrar a `prepare()`/`bind_param()` en `ActiveRecord` es la mejora estructural de mayor valor |
| `BlogController` con 4.200 líneas y 146 métodos | Difícil de navegar y de probar | Candidato natural a dividirse por módulo (`SuplenciasController`, `HorariosController`…) |
| Sin *suite* de pruebas automatizadas | Toda regresión se detecta en manual | Los invariantes ya están escritos como consultas SQL en [`database/CLAUDE.md`](../database/CLAUDE.md) — son la base para los primeros tests |
| Modelos `Usuario`, `Articulo`, `Noticia`, `Categoria` heredados de la plantilla original | Estilo distinto al del código nuevo | `Usuario` (registro público) apenas se usa; conviene decidir si el flujo `/cuenta/*` sigue vivo |
| `diagnostico.php` e `informacion.php` en la raíz | Exponen configuración del servidor | **Eliminar antes de producción** |
| Librerías de cliente por CDN (GSAP, Three.js, Chart.js, Font Awesome) | Dependencia de red externa | Sin *subresource integrity*; considerar alojarlas |
