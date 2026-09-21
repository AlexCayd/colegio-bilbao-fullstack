<?php
namespace Model;

class Aula extends ActiveRecord {
    protected static $tabla      = 'aulas';
    /**
     * ⚠️ `activo` NO entra aquí a propósito: es una columna con DEFAULT 1 que solo
     * escribe cambiarActivo(). Ver la nota de database.sql.
     */
    protected static $columnasDB = ['id', 'nombre'];

    public $id;
    public $nombre;
    /** Baja lógica: 0 = el archivo ya no la menciona. Ver database.sql. */
    public $activo;

    /** Alias de conteo (no es columna: hay que declararlo o crearObjeto() lo descarta). */
    public $total_horarios;

    /**
     * ⚠️ Por defecto SOLO las activas: un aula dada de baja no debe poder recibir una
     * clase nueva ni salir en el selector del editor. El importador, que necesita
     * reconocer las inactivas para reactivarlas, pasa `true`.
     */
    public static function todas(bool $incluirInactivas = false): array {
        $filtro = $incluirInactivas ? '' : 'WHERE activo = 1 ';
        return static::consultarSQL("SELECT * FROM aulas {$filtro}ORDER BY nombre ASC");
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

    /**
     * Da de baja (o vuelve a dar de alta) estas aulas de golpe.
     *
     * Es lo que hace el importador con las que el archivo ya no cita, en lugar del
     * DELETE que hacía antes. La FK de `suplencia_horas` es ON DELETE SET NULL: borrar
     * un aula citada por una cobertura de marzo no daba error, le vaciaba el dato al
     * histórico en silencio. Apagarla no ofrece el aula para nada nuevo y no toca una
     * sola fila de lo ya ocurrido.
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
            self::setAlerta('error', 'El nombre del aula es obligatorio');
        } elseif (mb_strlen($this->nombre) > 80) {
            self::setAlerta('error', 'El nombre no puede pasar de 80 caracteres');
        } elseif (self::nombreRepetido($this->nombre, (int) $this->id)) {
            self::setAlerta('error', "Ya existe un aula llamada «{$this->nombre}»");
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
