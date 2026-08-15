# 2. Estándares de código

> Público: cualquiera que escriba código en este repositorio.

---

## 2.1 Principio general

El código de este proyecto documenta **por qué**, no **qué**. Un comentario que repite lo que la
línea siguiente ya dice es ruido; uno que explica una decisión no evidente ahorra que el próximo
mantenedor la deshaga sin saberlo.

```php
// ❌ Ruido: la firma ya lo dice
/** Devuelve los grupos. */
public static function todos(): array { … }

// ✅ Aporta lo que el código no puede decir
/**
 * Todos los grupos, en orden académico.
 *
 * El orden NO sale de una columna: se deriva de Materia::ordenNivel() (el FIELD()
 * que ordena Maternal → Bachillerato) y, dentro de cada nivel, del nombre. Ordenar
 * por `nivel` a secas saldría alfabético, que no es el orden de un colegio.
 */
public static function todos(): array { … }
```

Buena parte del código nuevo ya sigue esta pauta. Lo que falta —y lo que añade este estándar— es
la parte **estructurada**: las etiquetas `@param`, `@return` y `@throws` que permiten generar
documentación automática y que un IDE ofrezca autocompletado y detecte errores de tipo.

---

## 2.2 PHPDoc: formato obligatorio

### Anatomía de un bloque

```php
/**
 * Resumen en una línea, terminado en punto.
 *
 * Párrafo opcional con el contexto: por qué existe, qué decisión encapsula, qué
 * pasa si se usa mal. Aquí es donde va lo valioso.
 *
 * @param  string      $fecha     Fecha de la ausencia en formato Y-m-d.
 * @param  int         $periodoId Periodo a cubrir. Pertenece siempre a un nivel.
 * @param  int         $ausenteId Se excluye de los candidatos. 0 = no excluir a nadie.
 * @return array<int, array{id:int, nombre:string, elegible:bool, motivo:?string}>
 *         Lista ordenada: elegibles primero.
 * @throws \RuntimeException Si la conexión no está inicializada.
 */
public static function sugerir(string $fecha, int $periodoId, int $ausenteId = 0): array
```

### Reglas

| Regla | Detalle |
|---|---|
| **Idioma** | Español, igual que el resto del código y de la base de datos |
| **Resumen** | Una sola línea, imperativo o descriptivo, terminada en punto |
| **Línea en blanco** | Obligatoria entre resumen, descripción y bloque de etiquetas |
| **`@param`** | Uno por parámetro, en el mismo orden que la firma. Se omite si el tipo nativo ya lo dice todo **y** el nombre es autoexplicativo |
| **`@return`** | Obligatorio siempre que el tipo devuelto sea `array`, `mixed` o un tipo compuesto. Se puede omitir en `: void` y en tipos escalares evidentes |
| **`@throws`** | Obligatorio si el método puede lanzar |
| **`@deprecated`** | Con el motivo y el reemplazo: `@deprecated Usar porAudiencia('familias')` |
| **Alineación** | Los nombres de parámetro y las descripciones se alinean en columna |

### Tipos de array: usar notación estructurada

El valor añadido real de PHPDoc en este proyecto está aquí. `@return array` no dice nada; los
modelos devuelven arrays con formas muy concretas y **esa forma es un contrato**:

```php
/** @return array<int, array{label:string, icon:string, url:string, ver:bool}> */

/** @return array{niveles:array<int,string>, comprimir:bool} */

/** @return array<string, array<int, array{inicio:string, fin:string, nivel:string}>>
 *          Ocupación indexada por id de profesor. */
```

phpDocumentor, PhpStorm, VS Code (Intelephense) y PHPStan entienden esta notación.

### Propiedades y constantes

```php
/** @var string|null CSV de módulos para el rol 'usuario'. NULL en administradores. */
public $modulos;

/** Minutos libres que un suplente debe conservar tras aceptar una cobertura. */
public const DESCANSO_MIN = 40;
```

> Recordatorio del ORM: **toda propiedad pública que reciba un alias de SQL debe estar
> declarada**, o `ActiveRecord::crearObjeto()` la descarta en silencio. El bloque `@var` es el
> lugar donde se anota de qué consulta viene:
> ```php
> /** @var int|null Alias de MONTH(fecha_nacimiento) en proximosCumpleanos(). */
> public $mes;
> ```

### Bloque de clase

Toda clase lleva un bloque que explica su papel en el dominio:

```php
/**
 * Cobertura de una hora concreta de una ausencia.
 *
 * Una `Suplencia` es «X faltó el día D»; una `SuplenciaHora` es «y esa clase de las
 * 10:00 la cubre Z». Aquí vive además el algoritmo de sugerencia de suplentes, que es
 * el corazón del módulo.
 *
 * @package Model
 */
class SuplenciaHora extends ActiveRecord
```

---

## 2.3 Estado actual y prioridad de cobertura

Medido sobre el árbol actual:

| Archivo | Métodos | Bloques | Estado |
|---|---:|---:|---|
| `models/Horario.php` | 26 | 27 | ✅ Documentado |
| `models/Suplencia.php` | 26 | 26 | ✅ Documentado |
| `models/SuplenciaHora.php` | 21 | 25 | ✅ Documentado |
| `models/Periodo.php` | 13 | 17 | ✅ Documentado |
| `models/UsuarioBlog.php` | 31 | 28 | ✅ Documentado |
| `models/Notificacion.php`, `Swap.php`, `Evento.php`, `Grupo.php`, `Aula.php`, `Materia.php`, `LugarGuardia.php` | — | — | ✅ Documentados |
| `Router.php` | 7 | 7 | ✅ Documentado *(añadido con este estándar)* |
| `models/ActiveRecord.php` | 18 | 18 | ✅ Documentado *(añadido)* |
| `controllers/AuthController.php` | 7 | 7 | ✅ Documentado *(añadido)* |
| `controllers/EstaticasController.php` | 43 | 43 | ✅ Documentado *(añadido)* |
| `models/Articulo.php`, `Noticia.php`, `Categoria.php`, `CategoriaNoticia.php`, `Usuario.php`, `Testimonial.php` | — | — | ✅ Documentados *(añadido)* |
| `controllers/BlogController.php` | 146 | 79 | 🟡 Parcial — la prosa está, faltan `@param`/`@return` |

**Prioridad para completar `BlogController`:** los métodos que devuelven o consumen estructuras
complejas (`rejillaProfesorJson`, `parsearCsvHorarios`, `celdasDelPost`, `colorMateria`,
`infoJustificante`, `guardarHoras`) antes que los CRUD lineales, cuya firma
`(Router $router): void` no aporta información.

---

## 2.4 Generar la documentación de la API

La configuración vive en [`phpdoc.dist.xml`](../phpdoc.dist.xml) en la raíz del repositorio.

### Instalación

phpDocumentor **no** está en `composer.json` a propósito: es una herramienta de desarrollo, no
una dependencia de la aplicación, y su árbol de dependencias es grande. Se usa como PHAR:

```bash
# Descargar una vez
curl -L https://phpdoc.org/phpDocumentor.phar -o phpDocumentor.phar

# Generar
php phpDocumentor.phar
```

O con Docker, sin instalar nada:

```bash
docker run --rm -v "$PWD:/data" phpdoc/phpdoc:3
```

La salida se escribe en `docs/api/`, que está excluida del control de versiones: es un artefacto
generado, se regenera cuando haga falta.

### Qué se documenta y qué no

`phpdoc.dist.xml` incluye `controllers/`, `models/`, `classes/`, `includes/` y `Router.php`, y
excluye `vendor/`, `node_modules/`, `public/`, `views/`, `src/`, `database/` y los dos archivos
utilitarios de la raíz.

---

## 2.5 Convenciones de nomenclatura

| Elemento | Convención | Ejemplo |
|---|---|---|
| Clase | `PascalCase`, singular | `SuplenciaHora`, `UsuarioBlog` |
| Método | `camelCase`, verbo primero | `guardarBloqueHorario()`, `porValidarDeSuplente()` |
| Método de consulta que devuelve lista | Sustantivo plural o `porX` | `todos()`, `porTipo()`, `deSuplencia()` |
| Método de consulta que devuelve uno | `find*` / `encontrar*` | `findByEmail()`, `encontrarConDetalle()` |
| Guard de permiso | `require*` (corta la ejecución) o `puede*` (devuelve `bool`) | `requireModulo()`, `puedeCoordinar()` |
| Constante | `SCREAMING_SNAKE_CASE` | `DESCANSO_MIN`, `MODULOS_ASIGNABLES` |
| Constante de etiquetas para la UI | Sufijo `_LABEL` | `ESTADO_LABEL`, `TIPO_LABEL` |
| Propiedad | `snake_case` si mapea una columna; `camelCase` si es calculada | `$fecha_nacimiento`, `$total_articulos` |
| Variable local | `snake_case` o `camelCase` según el archivo — **mantener el estilo del archivo** | |
| Tabla y columna SQL | `snake_case`, español, plural en tablas | `suplencia_horas`, `profesor_ausente_id` |
| Clase CSS | BEM | `.supl-hora-card--pendiente` |
| Archivo SCSS de vista | `_<data-page>.scss` | `_blog-usuarios-index.scss` |
| Módulo JS de vista | `<data-page>.js` | `blog-usuarios-index.js` |

---

## 2.6 Reglas de seguridad no negociables

Estas cinco reglas se verifican en revisión de código. Ninguna es opcional.

**1. Escapar toda salida.** Cualquier dato que llegue a una vista pasa por `s()`:

```php
<h2><?= s($usuario->nombre) ?></h2>
<a href="/dashboard/usuarios/editar?id=<?= (int)$usuario->id ?>">Editar</a>
```

**2. Escapar toda entrada que vaya a SQL.** `escape_string()` para cadenas, *cast* a `(int)` para
números, **lista blanca** para nombres de columna y direcciones de orden.

**3. Guard antes que nada en un método de controlador.** La primera línea, siempre. Ocultar un
botón en la vista no es un control de acceso: es cortesía visual.

**4. Validar y normalizar en el modelo, no en el controlador.** Es lo que hace que un POST
manipulado recorra el mismo camino que el formulario. `UsuarioBlog::normalizarTipoPersonal()` es
el ejemplo canónico: impide colarle `prefecto` + `profesor` aunque el JS del formulario se
salte por completo.

**5. Comprobar la propiedad del recurso, no solo el permiso.** Tener el módulo `swaps` no da
derecho a responder al intercambio de otra persona. `Swap::crearSwap()` verifica además que
**cada clase sea de quien dice ser**; `rejillaProfesorJson()` niega el horario ajeno a quien no
coordina.

---

## 2.7 Reglas de las vistas

| Prohibido | Alternativa |
|---|---|
| `<style>` o `<script>` embebidos | `src/scss/…` y `src/js/…` con el aislamiento `data-page` |
| Interpolar PHP dentro de JavaScript | Atributos `data-*` o isla `<script type="application/json">` |
| Consultas a modelos desde la vista | Pasar los datos desde el controlador |
| Editar `public/build/` | Editar la fuente en `src/` y recompilar |
| Colores hexadecimales a mano en código nuevo | Tokens `--pal-*` de `src/scss/estaticas/_variables.scss` |

Dos excepciones documentadas al «nada de scripts en las vistas», ambas porque deben ejecutarse
**antes del primer pintado** y el bundle va con `defer` al final del `<body>`:

- el guard anti-FOUC de i18n en el `<head>` de `views/layout.php`;
- el guard anti-salto del sidebar en el `<head>` de `views/layout-admin.php`.

---

## 2.8 Reglas de la base de datos

Detalle completo en [`database/CLAUDE.md`](../database/CLAUDE.md). Resumen:

- **Solo existen tres archivos `.sql`.** No se crean migraciones ni parches. Si el esquema cambia,
  se edita `database.sql` y se ajustan los dos archivos de datos.
- Un `ALTER` puntual para conservar datos se documenta **como comentario** en
  `database/CLAUDE.md`, nunca como un cuarto archivo.
- `database.sql` reactiva `FOREIGN_KEY_CHECKS` en la **última** línea. No mover esa línea.
- Antes de tocar la base real, cargar los archivos en una base desechable y comprobar que no
  emiten ni un *warning*.

---

## 2.9 Checklist de revisión

Antes de dar por terminado un cambio:

- [ ] Los métodos nuevos llevan bloque PHPDoc con `@param`/`@return` cuando aportan.
- [ ] La salida está escapada con `s()`; la entrada a SQL, con `escape_string()` o `(int)`.
- [ ] El método de controlador empieza por su guard.
- [ ] La validación y la normalización viven en el modelo.
- [ ] Si la consulta usa alias, existe la propiedad pública en el modelo.
- [ ] Si el campo admite `NULL`, la persistencia lo soporta (no basta con `guardar()`).
- [ ] La vista nueva tiene `$paginaVista`, su partial SCSS registrado y su JS con guarda.
- [ ] Se recompilaron los assets (`npx gulp css` / `npx gulp js`).
- [ ] Si cambió el esquema, se actualizaron `database.sql` **y** los dos archivos de datos.
- [ ] Si cambió una decisión de diseño, se actualizó `CLAUDE.md`.
