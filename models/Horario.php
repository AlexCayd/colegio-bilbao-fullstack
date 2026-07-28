<?php
namespace Model;

class Horario extends ActiveRecord {
    protected static $tabla      = 'horarios';
    protected static $columnasDB = ['id', 'dia', 'periodo_id', 'profesor_id', 'grupo_id', 'aula_id', 'materia_id'];

    public $id;
    public $dia;
    public $periodo_id;
    public $profesor_id;
    public $grupo_id;
    public $aula_id;
    public $materia_id;

    // Aliases traídos por JOIN
    public $profesor_nombre;
    public $grupo_nombre;
    public $grupo_nivel;
    public $aula_nombre;
    public $materia;        // alias de materias.nombre
    public $materia_nivel;
    public $periodo_etiqueta;
    public $periodo_orden;

    public const DIAS = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes'];
    public const DIAS_LABEL = ['lunes' => 'Lunes', 'martes' => 'Martes', 'miercoles' => 'Miércoles', 'jueves' => 'Jueves', 'viernes' => 'Viernes'];

    /** Persistencia con NULL real para grupo_id/aula_id/materia_id. */
    public function guardar() {
        $db   = self::$db;
        $cols = ['dia', 'periodo_id', 'profesor_id', 'grupo_id', 'aula_id', 'materia_id'];
        $sql  = [];
        foreach ($cols as $c) {
            $v = $this->$c;
            if ($v === null || $v === '' || (in_array($c, ['grupo_id', 'aula_id', 'materia_id'], true) && (int)$v === 0)) {
                $sql[$c] = 'NULL';
            } else {
                $sql[$c] = "'" . $db->escape_string($v) . "'";
            }
        }
        if (!empty($this->id)) {
            $assign = [];
            foreach ($sql as $c => $v) $assign[] = "{$c} = {$v}";
            $ok = $db->query("UPDATE horarios SET " . implode(', ', $assign) . " WHERE id = " . (int)$this->id . " LIMIT 1");
            return ['resultado' => (bool)$ok, 'id' => (int)$this->id];
        }
        $ok = $db->query("INSERT INTO horarios (" . implode(', ', array_keys($sql)) . ") VALUES (" . implode(', ', array_values($sql)) . ")");
        $this->id = $db->insert_id;
        return ['resultado' => (bool)$ok, 'id' => (int)$this->id];
    }

    private static function selectBase(): string {
        return "
            SELECT h.*,
                   u.nombre AS profesor_nombre,
                   g.nombre AS grupo_nombre, g.nivel AS grupo_nivel,
                   a.nombre AS aula_nombre,
                   m.nombre AS materia, m.nivel AS materia_nivel,
                   p.etiqueta AS periodo_etiqueta, p.orden AS periodo_orden
            FROM horarios h
            LEFT JOIN usuarios u ON u.id = h.profesor_id
            LEFT JOIN grupos   g ON g.id = h.grupo_id
            LEFT JOIN aulas    a ON a.id = h.aula_id
            LEFT JOIN materias m ON m.id = h.materia_id
            LEFT JOIN periodos p ON p.id = h.periodo_id
        ";
    }

    public static function porProfesor(int $id): array {
        $id = (int)$id;
        return static::consultarSQL(self::selectBase() . " WHERE h.profesor_id = {$id} ORDER BY p.orden ASC");
    }
    public static function porAula(int $id): array {
        $id = (int)$id;
        return static::consultarSQL(self::selectBase() . " WHERE h.aula_id = {$id} ORDER BY p.orden ASC");
    }
    public static function porGrupo(int $id): array {
        $id = (int)$id;
        return static::consultarSQL(self::selectBase() . " WHERE h.grupo_id = {$id} ORDER BY p.orden ASC");
    }

    /**
     * Borra el horario completo de los profesores indicados.
     * Lo usa la importación CSV: el archivo reemplaza el horario de quien aparezca en él,
     * y no toca al resto del claustro.
     */
    public static function borrarDeProfesores(array $ids): int {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (!$ids) return 0;
        self::$db->query("DELETE FROM horarios WHERE profesor_id IN (" . implode(',', $ids) . ")");
        return self::$db->affected_rows;
    }

    /** Indexa una lista de horarios en una matriz [dia][periodo_id] => Horario. */
    public static function comoMatriz(array $filas): array {
        $m = [];
        foreach ($filas as $h) {
            $m[$h->dia][(int)$h->periodo_id] = $h;
        }
        return $m;
    }

    /** ¿El profesor tiene clase ese día y periodo? */
    public static function ocupado(int $profId, string $dia, int $periodoId): bool {
        $profId = (int)$profId; $periodoId = (int)$periodoId;
        $dia = self::$db->escape_string($dia);
        $r = self::$db->query("SELECT id FROM horarios WHERE profesor_id={$profId} AND dia='{$dia}' AND periodo_id={$periodoId} LIMIT 1");
        return $r && $r->num_rows > 0;
    }

    /**
     * Periodos de CLASE en que el profesor está libre ese día (ids de periodo).
     * = periodos con es_receso=0 que no aparecen en su horario.
     */
    public static function horasLibres(int $profId, string $dia): array {
        $profId = (int)$profId;
        $dia = self::$db->escape_string($dia);
        $sql = "SELECT p.id FROM periodos p
                WHERE p.es_receso = 0
                  AND p.id NOT IN (SELECT periodo_id FROM horarios WHERE profesor_id={$profId} AND dia='{$dia}')
                ORDER BY p.orden ASC";
        $r = self::$db->query($sql);
        $out = [];
        if ($r) while ($row = $r->fetch_assoc()) $out[] = (int)$row['id'];
        return $out;
    }
}
