<?php
namespace Model;

/**
 * Categoría de artículos del blog (Voces Bilbao).
 *
 * Clasificación de un solo nivel: un artículo pertenece como mucho a una categoría, y al
 * borrarla los artículos sobreviven sin ella (FK con ON DELETE SET NULL).
 *
 * ⚠️ No confundir con CategoriaNoticia, que hace lo mismo para las noticias sobre otra
 * tabla. Son dos taxonomías separadas a propósito: el blog y las noticias son secciones
 * distintas del sitio.
 *
 * @package Model
 */
class Categoria extends ActiveRecord {

    protected static $tabla      = 'categorias';
    protected static $columnasDB = ['id', 'nombre', 'slug', 'descripcion', 'color'];

    /** @var int|string|null */
    public $id;
    /** @var string|null Nombre visible. */
    public $nombre;
    /** @var string|null Identificador para la URL. Único en la tabla. */
    public $slug;
    /** @var string|null Texto de apoyo, opcional. */
    public $descripcion;

    /**
     * Color de la etiqueta, en hexadecimal de 7 caracteres.
     *
     * ⚠️ Este valor por defecto NO pertenece a la paleta institucional de diez colores
     * (ver CLAUDE.md) y tampoco coincide con el DEFAULT de la columna, que es '#4267ac'.
     * Solo aplica a objetos nuevos creados en PHP sin pasar por el formulario, que sí
     * ofrece la paleta correcta.
     *
     * @var string
     */
    public $color           = '#4D8ABB';

    /** @var string|null Solo lectura: lo fija la BD con DEFAULT CURRENT_TIMESTAMP. */
    public $creado_en;

    /**
     * @var int Alias de COUNT(a.id) en allConArticulos() y findConArticulos().
     *          Declarado porque crearObjeto() descarta las columnas sin propiedad.
     */
    public $total_articulos = 0;

    /**
     * Valida nombre y slug.
     *
     * El slug se restringe a minúsculas, dígitos y guiones, empezando por carácter
     * alfanumérico: va directo a la URL pública.
     *
     * ⚠️ La unicidad del slug NO se comprueba aquí sino con existeSlug(), que el
     * controlador debe llamar aparte. Olvidarlo produce un error de índice único.
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
     * ¿Ya existe otra categoría con este slug?
     *
     * Al editar se excluye a sí misma, de modo que guardar sin cambiar el slug no da
     * falso positivo.
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
     * Todas las categorías con su número de artículos, para el listado del panel.
     *
     * LEFT JOIN: las categorías vacías también salen, con total 0. Es lo que hace falta
     * para poder borrarlas.
     *
     * @return array<int, self> Con $total_articulos poblado.
     */
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

    /**
     * Categorías que tienen al menos un artículo PUBLICADO, para el filtro del blog público.
     *
     * INNER JOIN, al revés que allConArticulos(): al visitante no se le ofrece un filtro
     * que no devolvería nada.
     *
     * @return array<int, self> Sin $total_articulos.
     */
    public static function allConArticulosPublicados(): array {
        $query = "
            SELECT DISTINCT c.*
            FROM   categorias c
            INNER JOIN articulos a ON a.categoria_id = c.id AND a.estado = 'publicado'
            ORDER BY c.nombre ASC
        ";
        return static::consultarSQL($query);
    }

    /**
     * Una categoría con su número de artículos.
     *
     * @param  int $id Id de la categoría.
     * @return self|null null si no existe.
     */
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

    /**
     * Número total de categorías, para las cifras del tablero de Redacción.
     *
     * @return int
     */
    public static function contarTotal(): int {
        $r = self::$db->query("SELECT COUNT(*) AS total FROM " . static::$tabla);
        return ($r) ? (int) $r->fetch_assoc()['total'] : 0;
    }

    /**
     * Reparto de artículos por categoría, para la gráfica del tablero de Redacción.
     *
     * Devuelve arrays y no objetos porque el destino es una isla JSON que consume
     * Chart.js: hidratar el modelo entero no aportaría nada. Se queda con las 8 primeras.
     *
     * @return array<int, array{nombre:string, color:string, total:string}>
     *         Ordenado de mayor a menor. `total` llega como cadena desde MySQLi.
     */
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
