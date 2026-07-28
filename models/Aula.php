<?php
namespace Model;

class Aula extends ActiveRecord {
    protected static $tabla      = 'aulas';
    protected static $columnasDB = ['id', 'nombre', 'descripcion'];

    public $id;
    public $nombre;
    public $descripcion;

    /** Alias de conteo (no es columna: hay que declararlo o crearObjeto() lo descarta). */
    public $total_horarios;

    public static function todas(): array {
        return static::consultarSQL("SELECT * FROM aulas ORDER BY nombre ASC");
    }

    /** Listado del panel con las clases que ocupan cada aula. */
    public static function todasConUso(): array {
        return static::consultarSQL("
            SELECT a.*, COUNT(h.id) AS total_horarios
            FROM aulas a
            LEFT JOIN horarios h ON h.aula_id = a.id
            GROUP BY a.id
            ORDER BY a.nombre ASC
        ");
    }

    /**
     * Cuántas filas dependen de este aula. Se consulta antes de borrar para dar un
     * mensaje claro en vez de dejar reventar la FK de horarios/suplencia_horas.
     */
    public static function usos(int $id): array {
        $id = (int) $id;
        $n = fn(string $sql) => (int) (self::$db->query($sql)?->fetch_assoc()['n'] ?? 0);
        return [
            'horarios'   => $n("SELECT COUNT(*) n FROM horarios WHERE aula_id = {$id}"),
            'suplencias' => $n("SELECT COUNT(*) n FROM suplencia_horas WHERE aula_id = {$id}"),
        ];
    }

    public function validar() {
        static::$alertas = [];

        $this->nombre      = trim((string) $this->nombre);
        $this->descripcion = trim((string) $this->descripcion) ?: null;

        if ($this->nombre === '') {
            self::setAlerta('error', 'El nombre del aula es obligatorio');
        } elseif (mb_strlen($this->nombre) > 80) {
            self::setAlerta('error', 'El nombre no puede pasar de 80 caracteres');
        } elseif (self::nombreRepetido($this->nombre, (int) $this->id)) {
            self::setAlerta('error', "Ya existe un aula llamada «{$this->nombre}»");
        }

        if ($this->descripcion !== null && mb_strlen($this->descripcion) > 160) {
            self::setAlerta('error', 'La descripción no puede pasar de 160 caracteres');
        }

        return static::$alertas;
    }

    /** La tabla tiene UNIQUE en `nombre`: se comprueba antes para poder explicarlo. */
    private static function nombreRepetido(string $nombre, int $excluirId = 0): bool {
        $n = self::$db->escape_string($nombre);
        $r = self::$db->query("SELECT id FROM aulas WHERE nombre = '{$n}' AND id <> {$excluirId} LIMIT 1");
        return $r && $r->num_rows > 0;
    }
}
