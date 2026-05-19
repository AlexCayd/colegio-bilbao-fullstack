<?php
namespace Model;

class Categoria extends ActiveRecord {

    protected static $tabla      = 'categorias';
    protected static $columnasDB = ['id', 'nombre', 'slug', 'descripcion', 'color'];

    public $id;
    public $nombre;
    public $slug;
    public $descripcion;
    public $color           = '#4D8ABB';
    public $creado_en;
    public $total_articulos = 0;

    public function validar(): array {
        static::$alertas = [];

        $this->nombre = trim($this->nombre ?? '');
        $this->slug   = trim($this->slug   ?? '');

        if (!$this->nombre) {
            static::setAlerta('error', 'El nombre de la categoría es obligatorio');
        }

        if (!$this->slug) {
            static::setAlerta('error', 'El slug es obligatorio');
        } elseif (!preg_match('/^[a-z0-9][a-z0-9-]*$/', $this->slug)) {
            static::setAlerta('error', 'El slug solo puede contener letras minúsculas, números y guiones');
        }

        return static::$alertas;
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

    public static function allConArticulos(): array {
        $query = "
            SELECT c.*, COUNT(a.id) AS total_articulos
            FROM   categorias c
            LEFT JOIN articulos a ON a.categoria_id = c.id
            GROUP BY c.id
            ORDER BY c.nombre ASC
        ";
        return static::consultarSQL($query);
    }

    public static function findConArticulos(int $id): ?self {
        $query = "
            SELECT c.*, COUNT(a.id) AS total_articulos
            FROM   categorias c
            LEFT JOIN articulos a ON a.categoria_id = c.id
            WHERE  c.id = {$id}
            GROUP BY c.id
            LIMIT 1
        ";
        $resultado = static::consultarSQL($query);
        return $resultado[0] ?? null;
    }

    public static function contarTotal(): int {
        $r = self::$db->query("SELECT COUNT(*) AS total FROM " . static::$tabla);
        return ($r) ? (int) $r->fetch_assoc()['total'] : 0;
    }

    public static function articulosPorCategoria(): array {
        $resultado = self::$db->query("
            SELECT c.nombre, c.color, COUNT(a.id) AS total
            FROM   " . static::$tabla . " c
            LEFT JOIN articulos a ON a.categoria_id = c.id
            GROUP  BY c.id
            ORDER  BY total DESC, c.nombre ASC
            LIMIT  8
        ");
        $data = [];
        if ($resultado) {
            while ($row = $resultado->fetch_assoc()) {
                $data[] = $row;
            }
        }
        return $data;
    }
}
