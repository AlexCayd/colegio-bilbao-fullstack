<?php
namespace Model;

class Grupo extends ActiveRecord {
    protected static $tabla      = 'grupos';
    /**
     * ⚠️ `activo` NO entra aquí a propósito. Es una columna con DEFAULT 1 que solo
     * escribe cambiarActivo(): el ORM base envuelve todo en comillas y no sabe escribir
     * un TINYINT nulo, y dejándola fuera ningún `sincronizar($_POST)` puede reactivar
     * un grupo desde el formulario.
     */
    protected static $columnasDB = ['id', 'nombre', 'nivel'];

    public $id;
    public $nombre;
    public $nivel;
    /** Baja lógica: 0 = el archivo ya no lo menciona. Ver database.sql. */
    public $activo;

    /** Alias de conteo (no es columna: hay que declararlo o crearObjeto() lo descarta). */
    public $total_horarios;

    /** Mismo ENUM que `materias.nivel`. */
    public const NIVELES = ['Maternal', 'Kinder', 'Primaria', 'Secundaria', 'Bachillerato'];

    /**
     * Todos en secuencia académica (Maternal → Bachillerato) y, dentro de cada
     * nivel, alfabéticamente. Antes había una columna `orden` que había que
     * mantener a mano; el orden se deduce ya del nivel + el nombre.
     *
     * ⚠️ Por defecto SOLO los activos, que es lo que necesita quien monta un selector:
     * un grupo dado de baja no debe poder recibir una clase nueva. El importador —que
     * tiene que reconocer lo inactivo para reactivarlo— pasa `true`.
     */
    public static function todos(bool $incluirInactivos = false): array {
        $filtro = $incluirInactivos ? '' : 'WHERE activo = 1 ';
        return static::consultarSQL(
            "SELECT * FROM grupos {$filtro}ORDER BY " . Materia::ordenNivel() . ", nombre ASC"
        );
    }

    /** Agrupados por nivel para pintar <optgroup>: ['Primaria' => Grupo[], …]. */
    public static function porNivel(bool $incluirInactivos = false): array {
        $out = [];
        foreach (self::todos($incluirInactivos) as $g) {
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

    /**
     * Da de baja (o vuelve a dar de alta) estos grupos. Ver Aula::cambiarActivo().
     *
     * @return int filas realmente cambiadas.
     */
    public static function cambiarActivo(array $ids, bool $activo): int {
        return self::marcarActivo($ids, $activo);
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
