<?php
namespace Model;

/**
 * Noticia institucional del colegio.
 *
 * Gemela de Articulo: mismos tres estados (`borrador`, `publicado`, `programado`), mismo
 * circuito de revisión editor → revisor y misma mecánica de `version_pendiente` para
 * editar algo ya publicado sin alterar la versión viva.
 *
 * Se diferencia en tres cosas:
 *   · La imagen se llama `portada` y lleva su propio texto alternativo (`portada_alt`).
 *   · Tiene `destacada`: una sola noticia ocupa la portada de /noticias a la vez, y de
 *     mantener esa exclusividad se encarga quitarDestacadaDeOtras().
 *   · No tiene etiquetas: la clasificación es solo por categoría.
 *
 * ⚠️ La duplicación con Articulo es real y conocida. Al tocar la lógica de uno, revisar
 * el otro.
 *
 * @package Model
 */
class Noticia extends ActiveRecord {

    protected static $tabla      = 'noticias';

    /** @var array<int, string> Sin `creado_en` ni `actualizado_en`: los gestiona la BD. */
    protected static $columnasDB = [
        'id', 'titulo', 'slug', 'extracto', 'contenido',
        'portada', 'portada_alt', 'estado', 'destacada',
        'envio_revision', 'comentario_revision', 'version_pendiente',
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
    /** @var string|null Ruta pública de la imagen de portada. */
    public $portada;
    /** @var string|null Texto alternativo de la portada (accesibilidad y SEO). */
    public $portada_alt;
    /** @var string 'borrador' | 'publicado' | 'programado'. */
    public $estado              = 'borrador';
    /** @var int 1 = ocupa la portada de /noticias. Solo una a la vez. */
    public $destacada           = 0;
    /** @var int 1 = esperando que un revisor la apruebe. */
    public $envio_revision      = 0;
    /** @var string|null Motivo del rechazo, que ve el autor. */
    public $comentario_revision;
    /** @var string|null Borrador de una noticia YA publicada, pendiente de aprobación. */
    public $version_pendiente;
    /** @var string|null DATETIME. Obligatorio si el estado es 'programado'. */
    public $fecha_publicacion;
    /** @var int|string|null Minutos estimados de lectura. */
    public $tiempo_lectura;
    /** @var int Contador de visitas. */
    public $vistas              = 0;
    /** @var int Contador de «me gusta». */
    public $likes               = 0;
    /** @var int|string|null FK a `categorias_noticias`. */
    public $categoria_id;
    /** @var int|string|null FK a `usuarios`. */
    public $autor_id;
    /** @var string|null Solo lectura: DEFAULT CURRENT_TIMESTAMP. */
    public $creado_en;
    /** @var string|null Solo lectura: ON UPDATE CURRENT_TIMESTAMP. */
    public $actualizado_en;

    // Campos calculados por JOIN
    // ⚠️ Sin estas declaraciones, ActiveRecord::crearObjeto() descartaría los alias en
    //    silencio y las vistas mostrarían campos vacíos sin ningún error.
    /** @var string|null Alias de c.nombre. */
    public $categoria_nombre;
    /** @var string|null Alias de c.slug. */
    public $categoria_slug;
    /** @var string|null Alias de c.color. */
    public $categoria_color;
    /** @var string|null Alias de u.nombre. */
    public $autor_nombre;
    /** @var string|null Alias de u.avatar. */
    public $autor_avatar;

    // ── Queries públicas ──────────────────────────────────────────────────────

    /**
     * Lista de columnas del SELECT, compartida por todas las consultas con JOIN.
     *
     * Centralizarla evita que una consulta se olvide un alias y devuelva objetos a medio
     * hidratar. Articulo, en cambio, la repite en cada método.
     *
     * @return string Fragmento SQL. Requiere los alias de tabla `n`, `c` y `u`.
     */
    private static function colsJoin(): string {
        return "
            n.*,
            c.nombre AS categoria_nombre,
            c.slug   AS categoria_slug,
            c.color  AS categoria_color,
            u.nombre AS autor_nombre,
            u.avatar AS autor_avatar
        ";
    }

    /**
     * JOINs de categoría y autor, compartidos por todas las consultas con detalle.
     *
     * LEFT y no INNER: una noticia no debe desaparecer del listado porque se borrase su
     * categoría o su autor (ambas FK son ON DELETE SET NULL).
     *
     * @return string Fragmento SQL que define los alias `c` y `u`.
     */
    private static function joins(): string {
        return "
            LEFT JOIN categorias_noticias c ON c.id = n.categoria_id
            LEFT JOIN usuarios            u ON u.id = n.autor_id
        ";
    }

    /**
     * La noticia marcada como destacada, para la portada de /noticias.
     *
     * @return self|null null si no hay ninguna destacada publicada. El listado cae
     *                   entonces a la primera de la lista, para que la portada nunca
     *                   quede vacía.
     */
    public static function destacada(): ?self {
        $result = static::consultarSQL("
            SELECT " . self::colsJoin() . "
            FROM   noticias n
            " . self::joins() . "
            WHERE  n.estado = 'publicado' AND n.destacada = 1
            ORDER  BY n.fecha_publicacion DESC, n.id DESC
            LIMIT  1
        ");
        return $result[0] ?? null;
    }

    /**
     * Las N noticias publicadas más recientes, para el bloque de la portada del sitio.
     *
     * @param  int $limite    Cuántas devolver.
     * @param  int $excluirId Noticia a omitir, normalmente la destacada. 0 = ninguna.
     * @return array<int, self>
     */
    public static function recientes(int $limite = 4, int $excluirId = 0): array {
        $excl = $excluirId ? "AND n.id != {$excluirId}" : '';
        return static::consultarSQL("
            SELECT " . self::colsJoin() . "
            FROM   noticias n
            " . self::joins() . "
            WHERE  n.estado = 'publicado' {$excl}
            ORDER  BY n.fecha_publicacion DESC, n.id DESC
            LIMIT  {$limite}
        ");
    }

    /**
     * Todas las noticias publicadas, para el listado público /noticias.
     *
     * @return array<int, self> De la más reciente a la más antigua.
     */
    public static function publicadas(): array {
        $query = "
            SELECT " . self::colsJoin() . "
            FROM   noticias n
            " . self::joins() . "
            WHERE  n.estado = 'publicado'
            ORDER  BY n.fecha_publicacion DESC, n.id DESC
        ";
        return static::consultarSQL($query);
    }

    /**
     * Una noticia publicada por su slug. Alimenta la ficha pública /noticias/{slug}.
     *
     * ⚠️ A diferencia de Articulo::findConDetallesBySlug(), esta SÍ filtra por
     * `estado = 'publicado'`: un borrador no es alcanzable desde fuera aunque se acierte
     * la URL. Por eso el controlador no necesita comprobar el estado después.
     *
     * @param  string $slug
     * @return self|null null si no existe o no está publicada.
     */
    public static function findBySlug(string $slug): ?self {
        $slug  = self::$db->escape_string($slug);
        $query = "
            SELECT " . self::colsJoin() . "
            FROM   noticias n
            " . self::joins() . "
            WHERE  n.slug = '{$slug}' AND n.estado = 'publicado'
            LIMIT  1
        ";
        $resultado = static::consultarSQL($query);
        return $resultado[0] ?? null;
    }

    /**
     * Noticias relacionadas para el pie de una ficha.
     *
     * Prioriza la misma categoría y, si no salen suficientes, COMPLETA con las más
     * recientes de cualquier otra. El bloque nunca queda a medias, que es lo que pasaba
     * con una categoría de una sola noticia.
     *
     * @param  int      $excluirId   Noticia que se está leyendo.
     * @param  int|null $categoriaId Su categoría. null = ir directo al relleno general.
     * @param  int      $limite      Cuántas devolver. Mínimo 1.
     * @return array<int, self> Puede traer menos de $limite si no hay bastantes publicadas.
     */
    public static function relacionadas(int $excluirId, ?int $categoriaId, int $limite = 2): array {
        $limite    = max(1, $limite);
        $catFilter = $categoriaId ? "AND n.categoria_id = {$categoriaId}" : '';
        $result    = static::consultarSQL("
            SELECT " . self::colsJoin() . "
            FROM   noticias n
            " . self::joins() . "
            WHERE  n.estado = 'publicado' AND n.id <> {$excluirId} {$catFilter}
            ORDER  BY n.fecha_publicacion DESC
            LIMIT  {$limite}
        ");

        // Completar con otras categorías si no hay suficientes
        if (count($result) < $limite) {
            $idsEx  = implode(',', array_merge([$excluirId], array_map(fn($n) => (int)$n->id, $result)));
            $falta  = $limite - count($result);
            $extra  = static::consultarSQL("
                SELECT " . self::colsJoin() . "
                FROM   noticias n
                " . self::joins() . "
                WHERE  n.estado = 'publicado' AND n.id NOT IN ({$idsEx})
                ORDER  BY n.fecha_publicacion DESC
                LIMIT  {$falta}
            ");
            $result = array_merge($result, $extra);
        }
        return $result;
    }

    /**
     * Categorías que tienen al menos una noticia publicada, para el filtro público.
     *
     * INNER JOIN a propósito: al visitante no se le ofrece un filtro que no devolvería
     * nada. Devuelve arrays y no objetos CategoriaNoticia porque la vista solo necesita
     * nombre, slug y color.
     *
     * @return array<int, array<string, string|null>> Filas tal cual las da la BD.
     */
    public static function categorias(): array {
        $result = self::$db->query("
            SELECT DISTINCT c.*
            FROM   categorias_noticias c
            INNER JOIN noticias n ON n.categoria_id = c.id AND n.estado = 'publicado'
            ORDER  BY c.nombre ASC
        ");
        $cats = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) { $cats[] = $row; }
        }
        return $cats;
    }

    /**
     * Publica de golpe toda noticia programada cuya fecha ya llegó.
     *
     * ⚠️ Igual que Articulo::publicarProgramados(): NO hay cron. El disparador es la
     * visita a la portada o al listado de noticias. Sin visitas, nada se publica solo.
     *
     * @return void
     */
    public static function publicarProgramadas(): void {
        self::$db->query(
            "UPDATE " . static::$tabla .
            " SET estado = 'publicado'" .
            " WHERE estado = 'programado' AND fecha_publicacion IS NOT NULL AND fecha_publicacion <= NOW()"
        );
    }

    // ── Validación ───────────────────────────────────────────────────────────

    /**
     * Valida la noticia antes de guardarla.
     *
     * ⚠️ MÁS LAXA que Articulo::validar(), y la diferencia es deliberada solo en parte:
     *   · Un estado desconocido aquí es ERROR; en Articulo se corrige a 'borrador'.
     *   · NO exige contenido, categoría, autor ni tiempo de lectura, que en Articulo sí
     *     son obligatorios.
     *   · Al programar exige fecha, pero NO comprueba que sea futura.
     * Si se quiere igualar el rigor de las dos secciones, este es el sitio.
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
            static::setAlerta('error', 'El título de la noticia es obligatorio');
        }
        if (!$this->slug) {
            static::setAlerta('error', 'El slug es obligatorio');
        } elseif (!preg_match('/^[a-z0-9][a-z0-9-]*$/', $this->slug)) {
            static::setAlerta('error', 'El slug solo puede contener letras minúsculas, números y guiones');
        }
        if (!\in_array($this->estado ?? '', ['borrador', 'publicado', 'programado'])) {
            static::setAlerta('error', 'Estado inválido');
        }
        if (($this->estado ?? '') === 'programado' && !trim($this->fecha_publicacion ?? '')) {
            static::setAlerta('error', 'Para programar indica una fecha de publicación');
        }

        return static::$alertas;
    }

    // ── Dashboard ─────────────────────────────────────────────────────────────

    /**
     * Cuántas noticias hay en un estado, para las cifras del tablero de Redacción.
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
     * Noticias con categoría y autor, opcionalmente filtradas. Consulta base del panel.
     *
     * @param  string $estado  'publicado' | 'borrador' | 'programado'. Vacío o desconocido = todas.
     * @param  int    $autorId Filtrar por autor. 0 = todos.
     * @return array<int, self> Por fecha de publicación descendente.
     */
    public static function allConDetalles(string $estado = '', int $autorId = 0): array {
        $conditions = [];
        if ($estado && in_array($estado, ['publicado', 'borrador', 'programado'], true)) {
            $e            = self::$db->escape_string($estado);
            $conditions[] = "n.estado = '{$e}'";
        }
        if ($autorId > 0) {
            $conditions[] = "n.autor_id = {$autorId}";
        }
        $where = $conditions ? "WHERE " . implode(" AND ", $conditions) : "";
        $query = "
            SELECT " . self::colsJoin() . "
            FROM   noticias n
            " . self::joins() . "
            {$where}
            ORDER BY n.fecha_publicacion DESC, n.id DESC
        ";
        return static::consultarSQL($query);
    }

    /**
     * Una noticia por id, con categoría y autor. Alimenta el formulario de edición.
     *
     * Sin filtro de estado, a diferencia de findBySlug(): en el panel hay que poder abrir
     * un borrador.
     *
     * @param  int $id
     * @return self|null null si no existe.
     */
    public static function findConDetalles(int $id): ?self {
        $query = "
            SELECT " . self::colsJoin() . "
            FROM   noticias n
            " . self::joins() . "
            WHERE  n.id = {$id}
            LIMIT  1
        ";
        $resultado = static::consultarSQL($query);
        return $resultado[0] ?? null;
    }

    /**
     * Las N noticias creadas más recientemente, para el tablero de Redacción.
     *
     * Ordena por `creado_en` y no por `fecha_publicacion`: en el panel interesa en qué se
     * ha trabajado últimamente, incluidos los borradores.
     *
     * @param  int $limit Mínimo 1.
     * @return array<int, self>
     */
    public static function recentesConDetalles(int $limit = 6): array {
        $limit = max(1, $limit);
        $query = "
            SELECT " . self::colsJoin() . "
            FROM   noticias n
            " . self::joins() . "
            ORDER  BY n.creado_en DESC, n.id DESC
            LIMIT  {$limit}
        ";
        return static::consultarSQL($query);
    }

    /**
     * Cola de revisión: noticias que un editor envió y esperan aprobación.
     *
     * ⚠️ Escribe sus JOIN a mano en vez de usar colsJoin()/joins(), así que NO trae
     * `categoria_slug`. Si la vista de revisiones llegara a necesitarlo, hay que
     * añadirlo aquí o unificar con los helpers.
     *
     * @return array<int, self> Por última modificación descendente.
     */
    public static function conRevisionPendiente(): array {
        $query = "
            SELECT n.*, u.nombre AS autor_nombre, u.avatar AS autor_avatar,
                   c.nombre AS categoria_nombre, c.color AS categoria_color
            FROM noticias n
            LEFT JOIN usuarios u ON u.id = n.autor_id
            LEFT JOIN categorias_noticias c ON c.id = n.categoria_id
            WHERE n.envio_revision = 1
            ORDER BY n.actualizado_en DESC
        ";
        return static::consultarSQL($query);
    }

    /**
     * Noticias creadas por mes, para la gráfica de evolución del tablero.
     *
     * ⚠️ Un mes sin noticias no aparece en el resultado: si la gráfica necesita el eje
     * completo, hay que rellenar los huecos en el cliente.
     * ⚠️ A diferencia de Articulo::articulosPorMes(), NO aplica `max(1, …)` a $meses. Se
     * interpola directo en el INTERVAL, así que solo debe recibir literales del código.
     *
     * @param  int $meses Ventana hacia atrás.
     * @return array<int, array{mes:string, total:string}> `mes` en formato 'Y-m', ascendente.
     */
    public static function noticiasPorMes(int $meses = 6): array {
        $resultado = self::$db->query("
            SELECT DATE_FORMAT(creado_en, '%Y-%m') AS mes, COUNT(*) AS total
            FROM   " . static::$tabla . "
            WHERE  creado_en >= DATE_SUB(NOW(), INTERVAL {$meses} MONTH)
            GROUP  BY mes
            ORDER  BY mes ASC
        ");
        $data = [];
        if ($resultado) {
            while ($row = $resultado->fetch_assoc()) { $data[] = $row; }
        }
        return $data;
    }

    // ── Crear / Actualizar (con soporte a NULL) ───────────────────────────────

    /**
     * INSERT con soporte de NULL real (sobrescribe ActiveRecord::crear()).
     *
     * El ORM base envuelve todos los valores en comillas y un campo vacío acabaría como
     * cadena vacía, inaceptable en `fecha_publicacion` (DATETIME) y en las FKs. Aquí toda
     * cadena vacía o nula se escribe como NULL.
     *
     * ⚠️ Efecto colateral: no se puede guardar deliberadamente una cadena vacía en una
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

    /**
     * UPDATE con soporte de NULL real (sobrescribe ActiveRecord::actualizar()).
     *
     * Mismo motivo y misma salvedad que crear().
     *
     * @return bool|\mysqli_result
     */
    public function actualizar() {
        $atributos = $this->sanitizarAtributos();
        $valores   = [];
        foreach ($atributos as $key => $value) {
            $valores[] = ($value === null || $value === '')
                ? "{$key} = NULL"
                : "{$key} = '" . $value . "'";
        }
        $query = "UPDATE " . static::$tabla
               . " SET " . implode(', ', $valores)
               . " WHERE id = '" . self::$db->escape_string($this->id) . "'"
               . " LIMIT 1";
        return self::$db->query($query);
    }

    /**
     * Desmarca la destacada de todas las noticias menos una.
     *
     * Es lo que mantiene la exclusividad de la portada: no hay restricción en la base de
     * datos que la garantice, así que hay que llamarla ANTES de guardar una noticia con
     * `destacada = 1`. Olvidarlo deja dos destacadas y destacada() elige una arbitraria.
     *
     * @param  int $excluirId Noticia que conserva la marca. 0 = quitarla a todas.
     * @return void
     */
    public static function quitarDestacadaDeOtras(int $excluirId = 0): void {
        $where = $excluirId ? " WHERE id != {$excluirId}" : '';
        self::$db->query("UPDATE " . static::$tabla . " SET destacada = 0{$where}");
    }

    /**
     * ¿Ya hay otra noticia con este slug? Al editar se excluye a sí misma.
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
}
