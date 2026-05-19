<?php
namespace Model;

class Articulo extends ActiveRecord {

    protected static $tabla      = 'articulos';
    protected static $columnasDB = [
        'id', 'titulo', 'slug', 'extracto', 'contenido',
        'imagen', 'estado', 'fecha_publicacion',
        'categoria_id', 'autor_id', 'tiempo_lectura',
        // 'tags' va en la tabla pivot articulo_tags, no aquí
    ];

    public $id;
    public $titulo;
    public $slug;
    public $extracto;
    public $contenido;
    public $imagen;
    public $estado           = 'borrador';
    public $fecha_publicacion;
    public $categoria_id;
    public $autor_id;
    public $tiempo_lectura;
    public $creado_en;
    public $actualizado_en;

    // Campos calculados por JOIN (no van a BD)
    public $categoria_nombre;
    public $categoria_color;
    public $categoria_slug;
    public $autor_nombre;
    public $autor_avatar;

    // ── Validación ────────────────────────────────────────────────────────────

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

    public static function allConDetalles(string $estado = ''): array {
        $where = '';
        if ($estado && in_array($estado, ['publicado', 'borrador', 'programado'], true)) {
            $e     = self::$db->escape_string($estado);
            $where = "WHERE a.estado = '{$e}'";
        }
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

    public static function publicarProgramados(): void {
        self::$db->query(
            "UPDATE " . static::$tabla .
            " SET estado = 'publicado'" .
            " WHERE estado = 'programado' AND fecha_publicacion IS NOT NULL AND fecha_publicacion <= NOW()"
        );
    }

    public static function contarPorEstado(string $estado): int {
        $e = self::$db->escape_string($estado);
        $r = self::$db->query("SELECT COUNT(*) AS total FROM " . static::$tabla . " WHERE estado = '{$e}'");
        return ($r) ? (int) $r->fetch_assoc()['total'] : 0;
    }

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

    // ── Helpers ───────────────────────────────────────────────────────────────

    private static function slugify(string $str): string {
        $str = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $str);
        $str = strtolower($str);
        $str = preg_replace('/[^a-z0-9\s-]/', '', $str);
        $str = preg_replace('/[\s-]+/', '-', trim($str));
        return trim($str, '-');
    }
}
