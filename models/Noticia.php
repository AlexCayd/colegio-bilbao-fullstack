<?php
namespace Model;

class Noticia extends ActiveRecord {

    protected static $tabla      = 'noticias';
    protected static $columnasDB = [
        'id', 'titulo', 'slug', 'extracto', 'contenido',
        'portada', 'portada_alt', 'estado', 'destacada',
        'envio_revision', 'comentario_revision',
        'fecha_publicacion', 'tiempo_lectura', 'vistas', 'likes',
        'categoria_id', 'autor_id',
    ];

    public $id;
    public $titulo;
    public $slug;
    public $extracto;
    public $contenido;
    public $portada;
    public $portada_alt;
    public $estado              = 'borrador';
    public $destacada           = 0;
    public $envio_revision      = 0;
    public $comentario_revision;
    public $fecha_publicacion;
    public $tiempo_lectura;
    public $vistas              = 0;
    public $likes               = 0;
    public $categoria_id;
    public $autor_id;
    public $creado_en;
    public $actualizado_en;

    // Campos calculados por JOIN
    public $categoria_nombre;
    public $categoria_slug;
    public $categoria_color;
    public $autor_nombre;
    public $autor_avatar;

    // ── Queries públicas ──────────────────────────────────────────────────────

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

    private static function joins(): string {
        return "
            LEFT JOIN categorias_noticias c ON c.id = n.categoria_id
            LEFT JOIN usuarios            u ON u.id = n.autor_id
        ";
    }

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

    public static function publicarProgramadas(): void {
        self::$db->query(
            "UPDATE " . static::$tabla .
            " SET estado = 'publicado'" .
            " WHERE estado = 'programado' AND fecha_publicacion IS NOT NULL AND fecha_publicacion <= NOW()"
        );
    }

    // ── Validación ───────────────────────────────────────────────────────────

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

    public static function contarPorEstado(string $estado): int {
        $e = self::$db->escape_string($estado);
        $r = self::$db->query("SELECT COUNT(*) AS total FROM " . static::$tabla . " WHERE estado = '{$e}'");
        return ($r) ? (int) $r->fetch_assoc()['total'] : 0;
    }

    public static function allConDetalles(string $estado = ''): array {
        $where = '';
        if ($estado && in_array($estado, ['publicado', 'borrador', 'programado'], true)) {
            $e     = self::$db->escape_string($estado);
            $where = "WHERE n.estado = '{$e}'";
        }
        $query = "
            SELECT " . self::colsJoin() . "
            FROM   noticias n
            " . self::joins() . "
            {$where}
            ORDER BY n.fecha_publicacion DESC, n.id DESC
        ";
        return static::consultarSQL($query);
    }

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

    public static function quitarDestacadaDeOtras(int $excluirId = 0): void {
        $where = $excluirId ? " WHERE id != {$excluirId}" : '';
        self::$db->query("UPDATE " . static::$tabla . " SET destacada = 0{$where}");
    }

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
