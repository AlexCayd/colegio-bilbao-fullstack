<?php
namespace Model;

/**
 * Categoría de noticias del colegio.
 *
 * Gemela de Categoria pero sobre `categorias_noticias`. Son dos taxonomías separadas a
 * propósito: el blog (Voces Bilbao) y las noticias institucionales son secciones
 * distintas del sitio y no comparten vocabulario.
 *
 * ⚠️ Esta clase y Categoria son casi idénticas salvo por la tabla y el nombre de la
 * relación. Si se toca la lógica de una, revisar la otra: divergen con facilidad.
 *
 * @package Model
 */
class CategoriaNoticia extends ActiveRecord {

    protected static $tabla      = 'categorias_noticias';
    protected static $columnasDB = ['id', 'nombre', 'slug', 'color', 'descripcion'];

    /** @var int|string|null */
    public $id;
    /** @var string|null Nombre visible. */
    public $nombre;
    /** @var string|null Identificador para la URL. Único en la tabla. */
    public $slug;

    /**
     * Color de la etiqueta, en hexadecimal de 7 caracteres.
     *
     * ⚠️ Igual que en Categoria, este valor por defecto no pertenece a la paleta
     * institucional ni coincide con el DEFAULT de la columna ('#4267ac'). Solo aplica a
     * objetos creados en PHP sin pasar por el formulario.
     *
     * @var string
     */
    public $color          = '#374C69';

    /** @var string|null Texto de apoyo, opcional. */
    public $descripcion;
    /** @var string|null Solo lectura: lo fija la BD con DEFAULT CURRENT_TIMESTAMP. */
    public $creado_en;

    /**
     * @var int Alias de COUNT(n.id) en allConNoticias() y findConNoticias().
     *          Declarado porque crearObjeto() descarta las columnas sin propiedad.
     */
    public $total_noticias = 0;

    /**
     * Valida nombre y slug. Mismas reglas que Categoria::validar().
     *
     * ⚠️ La unicidad del slug se comprueba aparte, con existeSlug().
     *
     * @return array<string, array<int, string>> Alertas; vacío = válido.
     */
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

    /**
     * ¿Ya existe otra categoría de noticias con este slug? Al editar se excluye a sí misma.
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

    /**
     * Todas las categorías con su número de noticias, para el listado del panel.
     *
     * LEFT JOIN: las vacías también salen, con total 0.
     *
     * @return array<int, self> Con $total_noticias poblado.
     */
    public static function allConNoticias(): array {
        $query = "
            SELECT c.*, COUNT(n.id) AS total_noticias
            FROM   categorias_noticias c
            LEFT JOIN noticias n ON n.categoria_id = c.id
            GROUP  BY c.id
            ORDER  BY c.nombre ASC
        ";
        return static::consultarSQL($query);
    }

    /**
     * Una categoría de noticias con su conteo.
     *
     * @param  int $id
     * @return self|null null si no existe.
     */
    public static function findConNoticias(int $id): ?self {
        $query = "
            SELECT c.*, COUNT(n.id) AS total_noticias
            FROM   categorias_noticias c
            LEFT JOIN noticias n ON n.categoria_id = c.id
            WHERE  c.id = {$id}
            GROUP  BY c.id
            LIMIT  1
        ";
        $resultado = static::consultarSQL($query);
        return $resultado[0] ?? null;
    }

    /**
     * Número total de categorías de noticias, para el tablero de Redacción.
     *
     * @return int
     */
    public static function contarTotal(): int {
        $r = self::$db->query("SELECT COUNT(*) AS total FROM " . static::$tabla);
        return ($r) ? (int) $r->fetch_assoc()['total'] : 0;
    }

    /**
     * Reparto de noticias por categoría, para la gráfica del tablero.
     *
     * Devuelve arrays y no objetos: el destino es una isla JSON que consume Chart.js.
     *
     * @return array<int, array{nombre:string, color:string, total:string}>
     *         Las 8 primeras, de mayor a menor.
     */
    public static function noticiasPorCategoria(): array {
        $resultado = self::$db->query("
            SELECT c.nombre, c.color, COUNT(n.id) AS total
            FROM   " . static::$tabla . " c
            LEFT JOIN noticias n ON n.categoria_id = c.id
            GROUP  BY c.id
            ORDER  BY total DESC, c.nombre ASC
            LIMIT  8
        ");
        $data = [];
        if ($resultado) {
            while ($row = $resultado->fetch_assoc()) { $data[] = $row; }
        }
        return $data;
    }
}
