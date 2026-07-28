<?php
namespace Model;

class SuplenciaHora extends ActiveRecord {

    protected static $tabla      = 'suplencia_horas';
    protected static $columnasDB = ['id', 'suplencia_id', 'periodo_id', 'grupo_id', 'aula_id', 'materia_id', 'suplente_id', 'estado_hora', 'validado_en'];

    public $id;
    public $suplencia_id;
    public $periodo_id;
    public $grupo_id;
    public $aula_id;
    public $materia_id;
    public $suplente_id;
    public $estado_hora;
    public $validado_en;

    // Aliases por JOIN
    public $periodo_etiqueta;
    public $periodo_orden;
    public $periodo_inicio;
    public $periodo_fin;
    public $grupo_nombre;
    public $aula_nombre;
    public $materia;        // alias de materias.nombre
    public $materia_nivel;
    public $suplente_nombre;
    public $suplente_avatar;
    public $s_fecha;
    public $s_motivo;
    public $s_notas;
    public $ausente_nombre;

    private const DOW_DIA = [1 => 'lunes', 2 => 'martes', 3 => 'miercoles', 4 => 'jueves', 5 => 'viernes', 6 => null, 7 => null];

    /**
     * Holgura de la regla de equidad en sugerir(): cuántas coberturas por encima
     * del mínimo del claustro se toleran antes de bloquear a un candidato.
     * Con 0 (el comportamiento anterior) casi siempre quedaba un único elegible.
     */
    public const MARGEN_EQUIDAD = 3;

    /** Persistencia con NULL real. */
    public function guardar() {
        $db   = self::$db;
        $cols = ['suplencia_id', 'periodo_id', 'grupo_id', 'aula_id', 'materia_id', 'suplente_id', 'estado_hora', 'validado_en'];
        $sql  = [];
        foreach ($cols as $c) {
            $v = $this->$c;
            if ($v === null || $v === '' || (in_array($c, ['grupo_id', 'aula_id', 'materia_id', 'suplente_id'], true) && (int)$v === 0)) {
                $sql[$c] = 'NULL';
            } else {
                $sql[$c] = "'" . $db->escape_string($v) . "'";
            }
        }
        if (!empty($this->id)) {
            $assign = [];
            foreach ($sql as $c => $v) $assign[] = "{$c} = {$v}";
            $ok = $db->query("UPDATE suplencia_horas SET " . implode(', ', $assign) . " WHERE id = " . (int)$this->id . " LIMIT 1");
            return ['resultado' => (bool)$ok, 'id' => (int)$this->id];
        }
        $ok = $db->query("INSERT INTO suplencia_horas (" . implode(', ', array_keys($sql)) . ") VALUES (" . implode(', ', array_values($sql)) . ")");
        $this->id = $db->insert_id;
        return ['resultado' => (bool)$ok, 'id' => (int)$this->id];
    }

    /** Horas de una suplencia con nombres de grupo/aula/suplente/periodo. */
    public static function deSuplencia(int $suplenciaId): array {
        $id = (int)$suplenciaId;
        $sql = "
            SELECT sh.*,
                   p.etiqueta AS periodo_etiqueta, p.orden AS periodo_orden,
                   p.hora_inicio AS periodo_inicio, p.hora_fin AS periodo_fin,
                   g.nombre AS grupo_nombre, a.nombre AS aula_nombre,
                   m.nombre AS materia, m.nivel AS materia_nivel,
                   s.nombre AS suplente_nombre, s.avatar AS suplente_avatar
            FROM suplencia_horas sh
            LEFT JOIN periodos p ON p.id = sh.periodo_id
            LEFT JOIN grupos   g ON g.id = sh.grupo_id
            LEFT JOIN aulas    a ON a.id = sh.aula_id
            LEFT JOIN materias m ON m.id = sh.materia_id
            LEFT JOIN usuarios s ON s.id = sh.suplente_id
            WHERE sh.suplencia_id = {$id}
            ORDER BY p.orden ASC
        ";
        return static::consultarSQL($sql);
    }

    /** Una hora con sus nombres resueltos (para redactar avisos legibles). */
    public static function detalle(int $horaId): ?self {
        $id  = (int)$horaId;
        $sql = "
            SELECT sh.*,
                   p.etiqueta AS periodo_etiqueta,
                   g.nombre AS grupo_nombre, a.nombre AS aula_nombre,
                   m.nombre AS materia
            FROM suplencia_horas sh
            LEFT JOIN periodos p ON p.id = sh.periodo_id
            LEFT JOIN grupos   g ON g.id = sh.grupo_id
            LEFT JOIN aulas    a ON a.id = sh.aula_id
            LEFT JOIN materias m ON m.id = sh.materia_id
            WHERE sh.id = {$id} LIMIT 1
        ";
        $r = static::consultarSQL($sql);
        return $r[0] ?? null;
    }

    /** Coberturas pendientes de validar por un suplente (para "Mis coberturas"). */
    public static function porValidarDeSuplente(int $suplenteId): array {
        $id = (int)$suplenteId;
        $sql = "
            SELECT sh.*, sup.fecha AS s_fecha, sup.motivo AS s_motivo, sup.notas AS s_notas,
                   p.etiqueta AS periodo_etiqueta, p.orden AS periodo_orden,
                   p.hora_inicio AS periodo_inicio, p.hora_fin AS periodo_fin,
                   g.nombre AS grupo_nombre, a.nombre AS aula_nombre,
                   m.nombre AS materia, m.nivel AS materia_nivel,
                   au.nombre AS ausente_nombre
            FROM suplencia_horas sh
            JOIN suplencias sup ON sup.id = sh.suplencia_id
            LEFT JOIN periodos p ON p.id = sh.periodo_id
            LEFT JOIN grupos   g ON g.id = sh.grupo_id
            LEFT JOIN aulas    a ON a.id = sh.aula_id
            LEFT JOIN materias m ON m.id = sh.materia_id
            LEFT JOIN usuarios au ON au.id = sup.profesor_ausente_id
            WHERE sh.suplente_id = {$id} AND sh.estado_hora = 'agendada'
            ORDER BY sup.fecha ASC, p.orden ASC
        ";
        return static::consultarSQL($sql);
    }

    /** Asigna un suplente a una hora (pasa a 'agendada'). */
    public static function asignar(int $horaId, int $suplenteId): bool {
        $horaId = (int)$horaId; $suplenteId = (int)$suplenteId;
        $ok = self::$db->query("UPDATE suplencia_horas SET suplente_id={$suplenteId}, estado_hora='agendada', validado_en=NULL WHERE id={$horaId} LIMIT 1");
        return (bool)$ok;
    }

    /** Quita el suplente de una hora (vuelve a 'pendiente'). */
    public static function desasignar(int $horaId): bool {
        $horaId = (int)$horaId;
        return (bool)self::$db->query("UPDATE suplencia_horas SET suplente_id=NULL, estado_hora='pendiente', validado_en=NULL WHERE id={$horaId} LIMIT 1");
    }

    /** El suplente valida que cubrió la hora. Solo si él es el suplente asignado. */
    public static function validarHora(int $horaId, int $suplenteId): bool {
        $horaId = (int)$horaId; $suplenteId = (int)$suplenteId;
        $ok = self::$db->query("UPDATE suplencia_horas SET estado_hora='validada', validado_en=NOW() WHERE id={$horaId} AND suplente_id={$suplenteId} AND estado_hora='agendada' LIMIT 1");
        return $ok && self::$db->affected_rows > 0;
    }

    /**
     * Sugiere y clasifica candidatos para cubrir una hora concreta.
     * La disponibilidad no se declara a mano: se deduce de las horas libres del horario.
     * Reglas: excluir ausente / con clase / ya asignado ese periodo; respetar la hora de
     * descanso; equidad (solo el mínimo de coberturas); administrativos al final.
     * `puede_suplir = 0` ya lo filtra UsuarioBlog::candidatosSuplencia().
     *
     * @return array Lista ordenada con: id, nombre, avatar, elegible(bool), motivo, horas_libres, coberturas, es_administrativo
     */
    public static function sugerir(string $fecha, int $periodoId, int $ausenteId = 0): array {
        $periodoId = (int)$periodoId;
        $ausenteId = (int)$ausenteId;
        $dow = (int)date('N', strtotime($fecha));
        $dia = self::DOW_DIA[$dow] ?? null;
        if ($dia === null) return []; // fin de semana: sin clases

        $fechaSafe = self::$db->escape_string($fecha);
        $db = self::$db;

        // Coberturas totales por suplente (equidad)
        $coberturas = [];
        $r = $db->query("SELECT suplente_id, COUNT(*) n FROM suplencia_horas WHERE suplente_id IS NOT NULL AND estado_hora IN ('agendada','validada') GROUP BY suplente_id");
        if ($r) while ($row = $r->fetch_assoc()) $coberturas[(int)$row['suplente_id']] = (int)$row['n'];

        // Ya asignados como suplente ese día (para la regla de descanso) y ese periodo (ocupado)
        $asignadosDia = [];
        $r = $db->query("SELECT sh.suplente_id, COUNT(*) n FROM suplencia_horas sh JOIN suplencias s ON s.id=sh.suplencia_id WHERE s.fecha='{$fechaSafe}' AND sh.suplente_id IS NOT NULL GROUP BY sh.suplente_id");
        if ($r) while ($row = $r->fetch_assoc()) $asignadosDia[(int)$row['suplente_id']] = (int)$row['n'];

        $ocupadoPeriodo = [];
        $r = $db->query("SELECT DISTINCT sh.suplente_id FROM suplencia_horas sh JOIN suplencias s ON s.id=sh.suplencia_id WHERE s.fecha='{$fechaSafe}' AND sh.periodo_id={$periodoId} AND sh.suplente_id IS NOT NULL");
        if ($r) while ($row = $r->fetch_assoc()) $ocupadoPeriodo[(int)$row['suplente_id']] = true;

        $candidatos = UsuarioBlog::candidatosSuplencia();
        $lista = [];
        foreach ($candidatos as $c) {
            $cid = (int)$c['id'];
            if ($cid === $ausenteId) continue;

            $libres  = Horario::horasLibres($cid, $dia);              // periodos de clase libres
            $nLibres = count($libres);
            $asigDia = $asignadosDia[$cid] ?? 0;
            $cob     = $coberturas[$cid] ?? 0;

            $motivo = null;
            if (!in_array($periodoId, $libres, true)) {
                $motivo = 'Tiene clase a esa hora';
            } elseif (!empty($ocupadoPeriodo[$cid])) {
                $motivo = 'Ya cubre otra suplencia a esa hora';
            } elseif ($nLibres <= 1) {
                $motivo = 'Solo tiene una hora libre (descanso)';
            } elseif (($nLibres - $asigDia) <= 1) {
                $motivo = 'Debe conservar una hora de descanso';
            }

            $lista[] = [
                'id'           => $cid,
                'nombre'       => $c['nombre'],
                'avatar'       => $c['avatar'] ?? '',
                'elegible'     => $motivo === null,
                'motivo'       => $motivo,
                'horas_libres' => $nLibres,
                'coberturas'   => $cob,
            ];
        }

        // Equidad: se permite a los que estén dentro de MARGEN_EQUIDAD coberturas
        // del mínimo. Exigir el mínimo exacto dejaba casi siempre un solo candidato
        // elegible frente a decenas de bloqueados, y prefectura se quedaba sin opciones.
        $minCob = null;
        foreach ($lista as $it) {
            if ($it['elegible']) $minCob = ($minCob === null) ? $it['coberturas'] : min($minCob, $it['coberturas']);
        }
        if ($minCob !== null) {
            $tope = $minCob + self::MARGEN_EQUIDAD;
            foreach ($lista as &$it) {
                if ($it['elegible'] && $it['coberturas'] > $tope) {
                    $it['elegible'] = false;
                    $it['motivo']   = 'Bloqueado por equidad (ya tiene bastantes más coberturas)';
                }
            }
            unset($it);
        }

        // Orden: elegibles primero; luego menos coberturas (el más justo arriba);
        // luego más horas libres; y a igualdad, por nombre.
        usort($lista, function ($a, $b) {
            if ($a['elegible'] !== $b['elegible']) return $a['elegible'] ? -1 : 1;
            if ($a['coberturas'] !== $b['coberturas']) return $a['coberturas'] <=> $b['coberturas'];
            if ($a['horas_libres'] !== $b['horas_libres']) return $b['horas_libres'] <=> $a['horas_libres'];
            return strcmp($a['nombre'], $b['nombre']);
        });

        return $lista;
    }
}
