<?php
namespace Model;

class Materia extends ActiveRecord {
    protected static $tabla      = 'materias';
    protected static $columnasDB = ['id', 'nombre', 'nivel'];

    public $id;
    public $nombre;
    public $nivel;

    /** Niveles académicos en orden. Compartido con Grupo. */
    public const NIVELES = ['Maternal', 'Kinder', 'Primaria', 'Secundaria', 'Bachillerato'];

    /** Expresión SQL para ordenar por nivel académico (no alfabético). */
    public static function ordenNivel(string $col = 'nivel'): string {
        $lista = implode(',', array_map(fn($n) => "'" . $n . "'", self::NIVELES));
        return "FIELD({$col}, {$lista})";
    }

    public static function todas(): array {
        return static::consultarSQL("SELECT * FROM materias ORDER BY " . self::ordenNivel() . ", nombre ASC");
    }

    /** Agrupadas por nivel para pintar <optgroup>: ['Kinder' => Materia[], …]. */
    public static function porNivel(): array {
        $out = [];
        foreach (self::todas() as $m) {
            $out[$m->nivel][] = $m;
        }
        return $out;
    }
}
