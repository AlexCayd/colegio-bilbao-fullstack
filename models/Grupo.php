<?php
namespace Model;

class Grupo extends ActiveRecord {
    protected static $tabla      = 'grupos';
    protected static $columnasDB = ['id', 'nombre', 'nivel'];

    public $id;
    public $nombre;
    public $nivel;

    /** Alias de conteo (no es columna: hay que declararlo o crearObjeto() lo descarta). */
    public $total_horarios;

    /** Mismo ENUM que `materias.nivel`. */
    public const NIVELES = ['Maternal', 'Kinder', 'Primaria', 'Secundaria', 'Bachillerato'];

    /**
     * Todos en secuencia académica (Maternal → Bachillerato) y, dentro de cada
     * nivel, alfabéticamente. Antes había una columna `orden` que había que
     * mantener a mano; el orden se deduce ya del nivel + el nombre.
     */
    public static function todos(): array {
        return static::consultarSQL(
            "SELECT * FROM grupos ORDER BY " . Materia::ordenNivel() . ", nombre ASC"
        );
    }

    /** Agrupados por nivel para pintar <optgroup>: ['Primaria' => Grupo[], …]. */
    public static function porNivel(): array {
        $out = [];
        foreach (self::todos() as $g) {
            $out[$g->nivel][] = $g;
        }
        return $out;
    }

    /** Listado del panel con las clases que tiene asignadas cada grupo. */
    public static function todosConUso(): array {
        return static::consultarSQL("
            SELECT g.*, COUNT(h.id) AS total_horarios
            FROM grupos g
            LEFT JOIN horarios h ON h.grupo_id = g.id
            GROUP BY g.id
            ORDER BY " . Materia::ordenNivel('g.nivel') . ", g.nombre ASC
        ");
    }

    /** Dependencias que impiden borrar (ver Aula::usos()). */
    public static function usos(int $id): array {
        $id = (int) $id;
        $n = fn(string $sql) => (int) (self::$db->query($sql)?->fetch_assoc()['n'] ?? 0);
        return [
            'horarios'   => $n("SELECT COUNT(*) n FROM horarios WHERE grupo_id = {$id}"),
            'suplencias' => $n("SELECT COUNT(*) n FROM suplencia_horas WHERE grupo_id = {$id}"),
        ];
    }

    public function validar() {
        static::$alertas = [];

        $this->nombre = trim((string) $this->nombre);

        if ($this->nombre === '') {
            self::setAlerta('error', 'El nombre del grupo es obligatorio');
        } elseif (mb_strlen($this->nombre) > 80) {
            self::setAlerta('error', 'El nombre no puede pasar de 80 caracteres');
        } elseif (self::nombreRepetido($this->nombre, (int) $this->id)) {
            self::setAlerta('error', "Ya existe un grupo llamado «{$this->nombre}»");
        }

        if (!in_array($this->nivel, self::NIVELES, true)) {
            self::setAlerta('error', 'Elige un nivel académico válido');
        }

        return static::$alertas;
    }

    /** La tabla tiene UNIQUE en `nombre`: se comprueba antes para poder explicarlo. */
    private static function nombreRepetido(string $nombre, int $excluirId = 0): bool {
        $n = self::$db->escape_string($nombre);
        $r = self::$db->query("SELECT id FROM grupos WHERE nombre = '{$n}' AND id <> {$excluirId} LIMIT 1");
        return $r && $r->num_rows > 0;
    }
}
