<?php
namespace Model;

/**
 * Artículo del blog institucional (Voces Bilbao).
 *
 * Ciclo editorial de tres estados —`borrador`, `publicado` y `programado`— más un circuito
 * de revisión: quien tiene rol de redacción `editor` no publica directamente, marca
 * `envio_revision = 1` y un revisor aprueba o rechaza con un comentario.
 *
 * Al editar algo YA PUBLICADO, los cambios se guardan en `version_pendiente` y la versión
 * viva no cambia hasta que se aprueban: el sitio público nunca muestra un borrador a medias.
 *
 * Es la única entidad con relación muchos-a-muchos del esquema, vía la tabla puente
 * `articulo_tags`.
 *
 * ⚠️ Casi gemelo de Noticia, que repite esta estructura sobre `noticias` para la sección
 * de noticias institucionales. Al tocar la lógica de uno, revisar el otro.
 *
 * @package Model
 */
class Articulo extends ActiveRecord {

    protected static $tabla      = 'articulos';

    /** @var array<int, string> Sin `creado_en` ni `actualizado_en`: los gestiona la BD. */
    protected static $columnasDB = [
        'id', 'titulo', 'slug', 'extracto', 'contenido',
        'imagen', 'estado', 'envio_revision', 'comentario_revision', 'version_pendiente',
        'fecha_publicacion', 'tiempo_lectura', 'vistas', 'likes',
        'categoria_id', 'autor_id',
    ];

    /** @var int|string|null */
    public $id;
    /** @var string|null */
    public $titulo;
    /** @var string|null Identificador de la URL pública. Único en la tabla. */
    public $slug;
    /** @var string|null Resumen para listados y meta description. */
    public $extracto;
    /** @var string|null HTML del editor enriquecido. */
    public $contenido;
    /** @var string|null Ruta pública de la imagen destacada. */
    public $imagen;
    /** @var string 'borrador' | 'publicado' | 'programado'. */
    public $estado              = 'borrador';
    /** @var int 1 = esperando que un revisor lo apruebe. */
    public $envio_revision      = 0;
    /** @var string|null Motivo del rechazo, que ve el autor. */
    public $comentario_revision;
    /** @var string|null Borrador de un artículo YA publicado, pendiente de aprobación. */
    public $version_pendiente;
    /** @var string|null DATETIME. Obligatorio si el estado es 'programado'. */
    public $fecha_publicacion;
    /** @var int|string|null Minutos estimados de lectura. Obligatorio. */
    public $tiempo_lectura;
    /** @var int Contador; lo incrementa incrementarVistas(). */
    public $vistas              = 0;
    /** @var int Contador de «me gusta». */
    public $likes               = 0;
    /** @var int|string|null FK a `categorias`. Obligatoria. */
    public $categoria_id;
    /** @var int|string|null FK a `usuarios`. Obligatorio. */
    public $autor_id;
    /** @var string|null Solo lectura: DEFAULT CURRENT_TIMESTAMP. */
    public $creado_en;
    /** @var string|null Solo lectura: ON UPDATE CURRENT_TIMESTAMP. */
    public $actualizado_en;

    // Campos calculados por JOIN (no van a BD)
    // ⚠️ Sin estas declaraciones, ActiveRecord::crearObjeto() descartaría los alias
    //    en silencio y las vistas mostrarían campos vacíos sin ningún error.
    /** @var string|null Alias de c.nombre. */
    public $categoria_nombre;
    /** @var string|null Alias de c.color. */
    public $categoria_color;
    /** @var string|null Alias de c.slug. */
    public $categoria_slug;
    /** @var string|null Alias de u.nombre. */
    public $autor_nombre;
    /** @var string|null Alias de u.avatar. */
    public $autor_avatar;

    // ── Validación ────────────────────────────────────────────────────────────

    /**
     * Valida el artículo completo antes de guardarlo.
     *
     * Un estado desconocido NO produce error: se corrige a 'borrador' en silencio, que es
     * la opción segura ante un POST manipulado. 'programado' exige fecha no anterior a hoy.
     *
     * ⚠️ La unicidad del slug se comprueba aparte, con existeSlug().
     *
     * @return array<string, array<int, string>> Alertas; vacío = válido.
     */
    public function validar(): array {
        static::$alertas = [];

        $this->titulo = trim($this->titulo ?? '');
        $this->slug   = trim($this->slug   ?? '');

        if (!$this->titulo) {
            static::setAlerta('error', 'El título del artículo es obligatorio');
        }

        if (!$this->slug) {
            static::setAlerta('error', 'El slug es obligatorio');
        } elseif (!preg_match('/^[a-z0-9][a-z0-9-]*$/', $this->slug)) {
            static::setAlerta('error', 'El slug solo puede contener letras minúsculas, números y guiones');
        }

        if (!trim(strip_tags($this->contenido ?? ''))) {
            static::setAlerta('error', 'El contenido del artículo es obligatorio');
        }

        if (!\in_array($this->estado ?? '', ['borrador', 'publicado', 'programado'])) {
            $this->estado = 'borrador';
        }

        if ($this->estado === 'programado') {
            if (!trim($this->fecha_publicacion ?? '')) {
                static::setAlerta('error', 'Debes indicar la fecha y hora de publicación para programar el artículo');
            } elseif (date('Y-m-d', strtotime($this->fecha_publicacion)) < date('Y-m-d')) {
                static::setAlerta('error', 'La fecha de publicación no puede ser anterior al día de hoy');
            }
        }

        if (!(int) ($this->autor_id ?? 0)) {
            static::setAlerta('error', 'El autor es obligatorio');
        }

        if (!(int) ($this->categoria_id ?? 0)) {
            static::setAlerta('error', 'La categoría es obligatoria');
        }

        if (!(int) ($this->tiempo_lectura ?? 0)) {
            static::setAlerta('error', 'El tiempo de lectura es obligatorio');
        }

        return static::$alertas;
    }

    // ── Slug único ────────────────────────────────────────────────────────────

    /**
     * ¿Ya hay otro artículo con este slug? Al editar se excluye a sí mismo.
     *
     * @return bool true = el slug está tomado.
     */
    public function existeSlug(): bool {
        $slug      = self::$db->escape_string($this->slug);
        $excluirId = (int) ($this->id ?? 0);
        $query     = "SELECT id FROM " . static::$tabla
                   . " WHERE slug = '{$slug}'"
                   . ($excluirId ? " AND id != {$excluirId}" : '')
                   . " LIMIT 1";
        $resultado = self::$db->query($query);
        return $resultado->num_rows > 0;
    }

    // ── INSERT con soporte a NULL (override de ActiveRecord) ─────────────────

    /**
     * INSERT con soporte de NULL real (sobrescribe ActiveRecord::crear()).
     *
     * El ORM base envuelve TODOS los valores en comillas, así que un campo vacío acabaría
     * como cadena vacía: inaceptable en `fecha_publicacion` (DATETIME) y en las FKs
     * `categoria_id` / `autor_id`. Aquí toda cadena vacía o nula se escribe como NULL.
     *
     * ⚠️ Efecto colateral: NO se puede guardar deliberadamente una cadena vacía en una
     * columna de texto; se convierte en NULL.
     *
     * @return array{resultado: bool|\mysqli_result, id: int|string}
     */
    public function crear() {
        $atributos = $this->sanitizarAtributos();
        $columns   = implode(', ', array_keys($atributos));
        $values    = array_map(
            fn($v) => ($v === null || $v === '') ? 'NULL' : "'" . $v . "'",
            array_values($atributos)
        );
        $query     = "INSERT INTO " . static::$tabla
                   . " ({$columns}) VALUES (" . implode(', ', $values) . ")";
        $resultado = self::$db->query($query);
        return ['resultado' => $resultado, 'id' => self::$db->insert_id];
    }

    // ── UPDATE con soporte a NULL (override de ActiveRecord) ─────────────────

    /**
     * UPDATE con soporte de NULL real (sobrescribe ActiveRecord::actualizar()).
     *
     * Mismo motivo y misma salvedad que crear(). Reescribe todas las columnas de
     * $columnasDB, no solo las que cambiaron.
     *
     * @return bool|\mysqli_result
     */
    public function actualizar() {
        $atributos = $this->sanitizarAtributos();
        $valores   = [];
        foreach ($atributos as $key => $value) {
            $valores[] = ($value === null || $value === '')
                ? "{$key} = NULL"
                : "{$key} = '{$value}'";
        }
        $query = "UPDATE " . static::$tabla
               . " SET " . implode(', ', $valores)
               . " WHERE id = '" . self::$db->escape_string($this->id) . "'"
               . " LIMIT 1";
        return self::$db->query($query);
    }

    // ── Tags via pivot articulo_tags ──────────────────────────────────────────

    /**
     * Recibe un string con tags separadas por coma.
     * Crea las tags que no existan y llena articulo_tags.
     *
     * Reemplaza por completo las etiquetas del artículo: primero borra las de la tabla
     * puente y después inserta las del texto. Cada nombre se convierte a slug y se crea
     * en `tags` con INSERT IGNORE, de modo que dos artículos que escriban la misma
     * etiqueta comparten fila.
     *
     * ⚠️ El borrado inicial ocurre ANTES de comprobar si la cadena viene vacía: pasar ''
     * deja el artículo sin ninguna etiqueta. Es el comportamiento que espera el
     * formulario, pero conviene saberlo antes de reutilizar el método.
     *
     * ⚠️ Sin transacción: si falla a mitad, el artículo se queda con parte de sus
     * etiquetas.
     *
     * @param  string $tagsStr Nombres separados por coma. Vacío = quitar todas.
     * @return void
     */
    public function guardarTags(string $tagsStr): void {
        $id = (int) $this->id;
        if (!$id) return;

        self::$db->query("DELETE FROM articulo_tags WHERE articulo_id = {$id}");

        if (!trim($tagsStr)) return;

        $nombres = array_unique(
            array_filter(array_map('trim', explode(',', $tagsStr)))
        );

        foreach ($nombres as $nombre) {
            if ($nombre === '') continue;

            $slug   = self::slugify($nombre);
            $nEsc   = self::$db->escape_string($nombre);
            $sEsc   = self::$db->escape_string($slug);

            self::$db->query(
                "INSERT IGNORE INTO tags (nombre, slug) VALUES ('{$nEsc}', '{$sEsc}')"
            );

            $r = self::$db->query(
                "SELECT id FROM tags WHERE slug = '{$sEsc}' LIMIT 1"
            );
            if ($r && $r->num_rows > 0) {
                $row = $r->fetch_assoc();
                self::$db->query(
                    "INSERT IGNORE INTO articulo_tags (articulo_id, tag_id)"
                    . " VALUES ({$id}, {$row['id']})"
                );
            }
        }
    }

    /**
     * Devuelve los tags del artículo como string separado por coma.
     *
     * Formato pensado para rellenar el mismo campo de texto que consume guardarTags():
     * lo que sale de aquí se puede volver a pasar allí sin transformar.
     *
     * @return string Cadena vacía si no tiene etiquetas.
     */
    public function obtenerTags(): string {
        $id     = (int) $this->id;
        $result = self::$db->query(
            "SELECT t.nombre
             FROM   tags t
             JOIN   articulo_tags at ON at.tag_id = t.id
             WHERE  at.articulo_id = {$id}
             ORDER  BY t.nombre ASC"
        );
        if (!$result) return '';
        $tags = [];
        while ($row = $result->fetch_assoc()) {
            $tags[] = $row['nombre'];
        }
        return implode(', ', $tags);
    }

    // ── Queries ───────────────────────────────────────────────────────────────

    /**
     * Artículos con su categoría y su autor resueltos, opcionalmente filtrados.
     *
     * Es la consulta base del listado del panel y del blog público. Los LEFT JOIN evitan
     * que un artículo desaparezca por haberse borrado su categoría o su autor (ambas FK
     * son ON DELETE SET NULL).
     *
     * @param  string $estado  'publicado' | 'borrador' | 'programado'. Vacío o desconocido = todos.
     * @param  int    $autorId Filtrar por autor. 0 = todos.
     * @return array<int, self> Del más reciente al más antiguo, con los alias poblados.
     */
    public static function allConDetalles(string $estado = '', int $autorId = 0): array {
        $conditions = [];
        if ($estado && in_array($estado, ['publicado', 'borrador', 'programado'], true)) {
            $e            = self::$db->escape_string($estado);
            $conditions[] = "a.estado = '{$e}'";
        }
        if ($autorId > 0) {
            $conditions[] = "a.autor_id = {$autorId}";
        }
        $where = $conditions ? "WHERE " . implode(" AND ", $conditions) : "";
        $query = "
            SELECT a.*,
                   c.nombre AS categoria_nombre,
                   c.color  AS categoria_color,
                   c.slug   AS categoria_slug,
                   u.nombre AS autor_nombre,
                   u.avatar AS autor_avatar
            FROM   articulos a
            LEFT JOIN categorias c ON c.id = a.categoria_id
            LEFT JOIN usuarios   u ON u.id = a.autor_id
            {$where}
            ORDER BY a.id DESC
        ";
        return static::consultarSQL($query);
    }

    /**
     * Un artículo por su slug, con categoría y autor. Alimenta la ficha pública /blog/{slug}.
     *
     * ⚠️ NO filtra por estado: devuelve borradores y programados. Quien llama debe
     * comprobar `$articulo->estado === 'publicado'` antes de mostrarlo — es lo que hace
     * BlogController::verArticulo().
     *
     * @param  string $slug
     * @return self|null null si no existe.
     */
    public static function findConDetallesBySlug(string $slug): ?self {
        $slug  = self::$db->escape_string($slug);
        $query = "
            SELECT a.*,
                   c.nombre AS categoria_nombre,
                   c.color  AS categoria_color,
                   c.slug   AS categoria_slug,
                   u.nombre AS autor_nombre,
                   u.avatar AS autor_avatar
            FROM   articulos a
            LEFT JOIN categorias c ON c.id = a.categoria_id
            LEFT JOIN usuarios   u ON u.id = a.autor_id
            WHERE  a.slug = '{$slug}'
            LIMIT  1
        ";
        $resultado = static::consultarSQL($query);
        return $resultado[0] ?? null;
    }

    /**
     * Un artículo por id, con categoría y autor. Alimenta el formulario de edición.
     *
     * @param  int $id
     * @return self|null null si no existe.
     */
    public static function findConDetalles(int $id): ?self {
        $query = "
            SELECT a.*,
                   c.nombre AS categoria_nombre,
                   c.color  AS categoria_color,
                   c.slug   AS categoria_slug,
                   u.nombre AS autor_nombre,
                   u.avatar AS autor_avatar
            FROM   articulos a
            LEFT JOIN categorias c ON c.id = a.categoria_id
            LEFT JOIN usuarios   u ON u.id = a.autor_id
            WHERE  a.id = {$id}
            LIMIT  1
        ";
        $resultado = static::consultarSQL($query);
        return $resultado[0] ?? null;
    }

    // ── Dashboard queries ─────────────────────────────────────────────────────

    /**
     * Publica de golpe todo artículo programado cuya fecha ya llegó.
     *
     * ⚠️ ESTE ES EL «CRON» DEL PROYECTO, y no hay ningún otro: el disparador es la visita
     * a la portada o al blog. Si nadie entra al sitio, un artículo programado no se
     * publica solo. Es idempotente y basta una sentencia, así que ejecutarlo en cada
     * visita no pesa.
     *
     * @return void
     */
    public static function publicarProgramados(): void {
        self::$db->query(
            "UPDATE " . static::$tabla .
            " SET estado = 'publicado'" .
            " WHERE estado = 'programado' AND fecha_publicacion IS NOT NULL AND fecha_publicacion <= NOW()"
        );
    }

    /**
     * Cuántos artículos hay en un estado, para las cifras del tablero de Redacción.
     *
     * @param  string $estado 'publicado' | 'borrador' | 'programado'.
     * @return int
     */
    public static function contarPorEstado(string $estado): int {
        $e = self::$db->escape_string($estado);
        $r = self::$db->query("SELECT COUNT(*) AS total FROM " . static::$tabla . " WHERE estado = '{$e}'");
        return ($r) ? (int) $r->fetch_assoc()['total'] : 0;
    }

    /**
     * Los N artículos más recientes con categoría y autor, para el tablero.
     *
     * ⚠️ Sin filtro de estado: incluye borradores. Es lo correcto en el panel —muestra en
     * qué se está trabajando— pero no sirve para nada de cara al público.
     *
     * @param  int $limite Mínimo 1.
     * @return array<int, self>
     */
    public static function recentesConDetalles(int $limite = 6): array {
        $limite = max(1, (int) $limite);
        $query  = "
            SELECT a.*,
                   c.nombre AS categoria_nombre,
                   c.color  AS categoria_color,
                   c.slug   AS categoria_slug,
                   u.nombre AS autor_nombre,
                   u.avatar AS autor_avatar
            FROM   " . static::$tabla . " a
            LEFT JOIN categorias c ON c.id = a.categoria_id
            LEFT JOIN usuarios   u ON u.id = a.autor_id
            ORDER BY a.id DESC
            LIMIT  {$limite}
        ";
        return static::consultarSQL($query);
    }

    /**
     * Cola de revisión: artículos que un editor envió y esperan aprobación.
     *
     * Ordenados por última modificación descendente, así lo recién enviado queda arriba.
     *
     * @return array<int, self>
     */
    public static function conRevisionPendiente(): array {
        $query = "
            SELECT a.*, u.nombre AS autor_nombre, u.avatar AS autor_avatar,
                   c.nombre AS categoria_nombre, c.color AS categoria_color
            FROM articulos a
            LEFT JOIN usuarios u ON u.id = a.autor_id
            LEFT JOIN categorias c ON c.id = a.categoria_id
            WHERE a.envio_revision = 1
            ORDER BY a.actualizado_en DESC
        ";
        return static::consultarSQL($query);
    }

    /**
     * Artículos creados por mes, para la gráfica de evolución del tablero.
     *
     * Devuelve arrays y no objetos: el destino es una isla JSON que consume Chart.js.
     *
     * ⚠️ Un mes SIN artículos no aparece en el resultado (GROUP BY sobre las filas
     * existentes). Si la gráfica necesita el eje completo, hay que rellenar los huecos en
     * el cliente.
     *
     * @param  int $meses Ventana hacia atrás. Mínimo 1.
     * @return array<int, array{mes:string, total:string}> `mes` en formato 'Y-m', ascendente.
     */
    public static function articulosPorMes(int $meses = 6): array {
        $meses     = max(1, (int) $meses);
        $resultado = self::$db->query("
            SELECT DATE_FORMAT(creado_en, '%Y-%m') AS mes, COUNT(*) AS total
            FROM   " . static::$tabla . "
            WHERE  creado_en >= DATE_SUB(NOW(), INTERVAL {$meses} MONTH)
            GROUP  BY mes
            ORDER  BY mes ASC
        ");
        $data = [];
        if ($resultado) {
            while ($row = $resultado->fetch_assoc()) {
                $data[] = $row;
            }
        }
        return $data;
    }

    /**
     * Suma una visita al artículo. La llama la ficha pública en cada carga.
     *
     * ⚠️ Sin deduplicación: recargar la página cuenta como visita nueva. La cifra sirve
     * como indicador relativo, no como analítica.
     *
     * @param  int $id
     * @return void
     */
    public static function incrementarVistas(int $id): void {
        static::$db->query("UPDATE articulos SET vistas=vistas+1 WHERE id=" . $id);
    }

    /**
     * Devuelve hasta 3 artículos recomendados:
     *   'reciente'  → el publicado más recientemente (≠ actual)
     *   'categoria' → uno de la misma categoría al azar (≠ actual ni reciente)
     *   'aleatorio' → uno al azar de los restantes
     * Cada slot puede ser null si no hay artículos disponibles.
     *
     * Son tres consultas encadenadas y no una sola: cada paso añade su resultado a la
     * lista de exclusión, y así los tres huecos nunca repiten artículo. Solo entran
     * publicados.
     *
     * @param  int      $excluirId   Artículo que se está leyendo. 0 = no excluir ninguno.
     * @param  int|null $categoriaId Categoría del actual. null = se omite el hueco 'categoria'.
     * @return array{reciente: self|null, categoria: self|null, aleatorio: self|null}
     *         Las tres claves existen siempre; su valor puede ser null.
     */
    public static function recomendados(int $excluirId, ?int $categoriaId): array {
        $excluirId = max(0, $excluirId);
        $yaIds     = [$excluirId ?: 0];

        $cols = "
            a.*,
            c.nombre AS categoria_nombre,
            c.color  AS categoria_color,
            c.slug   AS categoria_slug,
            u.nombre AS autor_nombre,
            u.avatar AS autor_avatar
        ";
        $joins = "
            LEFT JOIN categorias c ON c.id = a.categoria_id
            LEFT JOIN usuarios   u ON u.id = a.autor_id
        ";

        // 1. Más reciente publicado
        $excStr   = implode(',', $yaIds);
        $reciente = static::consultarSQL("
            SELECT {$cols} FROM articulos a {$joins}
            WHERE  a.estado = 'publicado' AND a.id NOT IN ({$excStr})
            ORDER  BY a.fecha_publicacion DESC, a.id DESC LIMIT 1
        ")[0] ?? null;
        if ($reciente) $yaIds[] = (int) $reciente->id;

        // 2. Misma categoría (si la hay y existe otro artículo)
        $categoria = null;
        if ($categoriaId) {
            $catId  = (int) $categoriaId;
            $excStr = implode(',', $yaIds);
            $categoria = static::consultarSQL("
                SELECT {$cols} FROM articulos a {$joins}
                WHERE  a.estado = 'publicado'
                       AND a.categoria_id = {$catId}
                       AND a.id NOT IN ({$excStr})
                ORDER  BY RAND() LIMIT 1
            ")[0] ?? null;
            if ($categoria) $yaIds[] = (int) $categoria->id;
        }

        // 3. Aleatorio de los restantes
        $excStr   = implode(',', $yaIds);
        $aleatorio = static::consultarSQL("
            SELECT {$cols} FROM articulos a {$joins}
            WHERE  a.estado = 'publicado' AND a.id NOT IN ({$excStr})
            ORDER  BY RAND() LIMIT 1
        ")[0] ?? null;

        return [
            'reciente'  => $reciente,
            'categoria' => $categoria,
            'aleatorio' => $aleatorio,
        ];
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Convierte un texto libre en slug: sin acentos, minúsculas y con guiones.
     *
     * Solo se usa para las etiquetas; el slug del artículo lo genera
     * BlogController::generarSlug(), que hace lo mismo por su cuenta.
     *
     * ⚠️ Depende de iconv() con //TRANSLIT, cuyo resultado varía según la biblioteca de
     * caracteres del sistema: en algunas plataformas 'ñ' sale como 'n' y en otras se
     * descarta. Por eso `iconv` figura como extensión recomendada.
     *
     * @param  string $str Texto de origen.
     * @return string Slug, sin guiones al principio ni al final.
     */
    private static function slugify(string $str): string {
        $str = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $str);
        $str = strtolower($str);
        $str = preg_replace('/[^a-z0-9\s-]/', '', $str);
        $str = preg_replace('/[\s-]+/', '-', trim($str));
        return trim($str, '-');
    }
}
