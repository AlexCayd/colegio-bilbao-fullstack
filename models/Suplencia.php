<?php
namespace Model;

class Suplencia extends ActiveRecord {

    protected static $tabla      = 'suplencias';
    protected static $columnasDB = ['id', 'profesor_ausente_id', 'fecha', 'motivo', 'notas', 'justificante', 'origen', 'estado', 'creado_por'];

    public $id;
    public $profesor_ausente_id;
    public $fecha;
    public $motivo;
    public $notas;
    public $justificante;
    public $origen;
    public $estado;
    public $creado_por;
    public $creado_en;
    public $actualizado_en;

    // Aliases traídos por JOIN / agregados
    public $ausente_nombre;
    public $ausente_avatar;
    public $creador_nombre;
    public $total_horas;
    public $horas_validadas;
    public $horas_agendadas;

    public const ESTADOS = ['solicitada', 'agendada', 'en_curso', 'por_justificar', 'completada', 'cancelada'];
    public const ESTADO_LABEL = [
        'solicitada'     => 'Solicitada',
        'agendada'       => 'Agendada',
        'en_curso'       => 'En curso',
        'por_justificar' => 'Por justificar',
        'completada'     => 'Completada',
        'cancelada'      => 'Cancelada',
    ];

    /**
     * Motivos sugeridos. La columna sigue siendo VARCHAR libre: el formulario ofrece
     * este catálogo y, con "Otro", exige una descripción que se guarda tal cual.
     */
    public const MOTIVOS = [
        'Incapacidad médica',
        'Cita médica',
        'Asunto personal',
        'Comisión oficial',
        'Curso o capacitación',
        'Congreso académico',
        'Permiso administrativo',
        'Luto',
    ];

    /**
     * Consolida el motivo del POST: una opción del catálogo o el texto libre de "Otro".
     * Devuelve null si no se indicó nada.
     */
    public static function motivoDesdePost(array $post): ?string {
        $elegido = trim((string)($post['motivo'] ?? ''));
        if ($elegido === '') return null;
        if ($elegido !== 'Otro') return $elegido;
        $otro = trim((string)($post['motivo_otro'] ?? ''));
        return $otro !== '' ? $otro : 'Otro';
    }

    public function validar(): array {
        static::$alertas = [];
        $this->fecha = trim((string)($this->fecha ?? ''));
        if ($this->fecha === '' || !\DateTime::createFromFormat('Y-m-d', $this->fecha)) {
            static::setAlerta('error', 'La fecha de la ausencia no es válida');
        }
        if (empty($this->profesor_ausente_id)) {
            static::setAlerta('error', 'Selecciona al profesor ausente');
        }
        // "Otro" sin describir no dice nada a quien luego revise la suplencia
        if (trim((string)($this->motivo ?? '')) === 'Otro') {
            static::setAlerta('error', 'Describe el motivo cuando elijas "Otro"');
        }
        if (!\in_array($this->origen ?? '', ['anticipada', 'sin_aviso'], true)) {
            $this->origen = 'anticipada';
        }
        if (!\in_array($this->estado ?? '', self::ESTADOS, true)) {
            $this->estado = $this->origen === 'sin_aviso' ? 'por_justificar' : 'solicitada';
        }
        $this->profesor_ausente_id = !empty($this->profesor_ausente_id) ? (int)$this->profesor_ausente_id : null;
        return static::$alertas;
    }

    /** Persistencia con NULL real (FKs y justificante). */
    public function guardar() {
        $db   = self::$db;
        $cols = ['profesor_ausente_id', 'fecha', 'motivo', 'notas', 'justificante', 'origen', 'estado', 'creado_por'];
        $sql  = [];
        foreach ($cols as $c) {
            $v = $this->$c;
            if ($v === null || $v === '' || (in_array($c, ['profesor_ausente_id', 'creado_por'], true) && (int)$v === 0)) {
                $sql[$c] = 'NULL';
            } else {
                $sql[$c] = "'" . $db->escape_string($v) . "'";
            }
        }
        if (!empty($this->id)) {
            $assign = [];
            foreach ($sql as $c => $v) $assign[] = "{$c} = {$v}";
            $ok = $db->query("UPDATE suplencias SET " . implode(', ', $assign) . " WHERE id = " . (int)$this->id . " LIMIT 1");
            return ['resultado' => (bool)$ok, 'id' => (int)$this->id];
        }
        $ok = $db->query("INSERT INTO suplencias (" . implode(', ', array_keys($sql)) . ") VALUES (" . implode(', ', array_values($sql)) . ")");
        $this->id = $db->insert_id;
        return ['resultado' => (bool)$ok, 'id' => (int)$this->id];
    }

    private static function selectBase(): string {
        return "
            SELECT sup.*,
                   a.nombre AS ausente_nombre, a.avatar AS ausente_avatar,
                   c.nombre AS creador_nombre,
                   COUNT(sh.id) AS total_horas,
                   SUM(sh.estado_hora = 'validada') AS horas_validadas,
                   SUM(sh.estado_hora = 'agendada') AS horas_agendadas
            FROM suplencias sup
            LEFT JOIN usuarios a ON a.id = sup.profesor_ausente_id
            LEFT JOIN usuarios c ON c.id = sup.creado_por
            LEFT JOIN suplencia_horas sh ON sh.suplencia_id = sup.id
        ";
    }

    /** Listado con filtros: q, estado, origen, desde, hasta, ausente_id, suplente_id. */
    public static function listar(array $f = []): array {
        $where = [];
        if (!empty($f['q'])) {
            $q = self::$db->escape_string($f['q']);
            $where[] = "(a.nombre LIKE '%{$q}%' OR sup.motivo LIKE '%{$q}%')";
        }
        if (!empty($f['estado']) && \in_array($f['estado'], self::ESTADOS, true)) {
            $where[] = "sup.estado = '" . self::$db->escape_string($f['estado']) . "'";
        }
        if (!empty($f['origen']) && \in_array($f['origen'], ['anticipada', 'sin_aviso'], true)) {
            $where[] = "sup.origen = '" . self::$db->escape_string($f['origen']) . "'";
        }
        if (!empty($f['desde'])) $where[] = "sup.fecha >= '" . self::$db->escape_string($f['desde']) . "'";
        if (!empty($f['hasta'])) $where[] = "sup.fecha <= '" . self::$db->escape_string($f['hasta']) . "'";
        if (!empty($f['ausente_id'])) $where[] = "sup.profesor_ausente_id = " . (int)$f['ausente_id'];
        if (!empty($f['suplente_id'])) {
            $sid = (int)$f['suplente_id'];
            $where[] = "sup.id IN (SELECT suplencia_id FROM suplencia_horas WHERE suplente_id = {$sid})";
        }

        $sql = self::selectBase();
        if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
        $sql .= ' GROUP BY sup.id ORDER BY sup.fecha DESC, sup.id DESC';
        return static::consultarSQL($sql);
    }

    public static function encontrarConDetalle(int $id): ?self {
        $id = (int)$id;
        $sql = self::selectBase() . " WHERE sup.id = {$id} GROUP BY sup.id LIMIT 1";
        $r = static::consultarSQL($sql);
        return $r[0] ?? null;
    }

    /** Conteos por estado para las tarjetas de encabezado (+ total). */
    public static function conteos(): array {
        $out = ['total' => 0];
        foreach (self::ESTADOS as $e) $out[$e] = 0;
        foreach (self::porEstado() as $estado => $n) {
            $out[$estado] = $n;
            $out['total'] += $n;
        }
        return $out;
    }

    /**
     * Recalcula el estado de la suplencia según sus horas.
     * Todas validadas → completada (si sin_aviso requiere justificante). Alguna agendada → agendada/en_curso.
     */
    public static function recalcularEstado(int $suplenciaId): void {
        $s = self::find($suplenciaId);
        if (!$s || in_array($s->estado, ['cancelada'], true)) return;
        $db = self::$db;
        $id = (int)$suplenciaId;
        $tot = (int)($db->query("SELECT COUNT(*) n FROM suplencia_horas WHERE suplencia_id={$id}")->fetch_assoc()['n']);
        if ($tot === 0) return;
        $val = (int)($db->query("SELECT COUNT(*) n FROM suplencia_horas WHERE suplencia_id={$id} AND estado_hora='validada'")->fetch_assoc()['n']);
        $age = (int)($db->query("SELECT COUNT(*) n FROM suplencia_horas WHERE suplencia_id={$id} AND estado_hora='agendada'")->fetch_assoc()['n']);

        $tieneJustificante = !empty($s->justificante) || $s->origen === 'anticipada';

        if ($val === $tot) {
            // Todas cubiertas y validadas
            $nuevo = ($s->origen === 'sin_aviso' && empty($s->justificante)) ? 'por_justificar' : 'completada';
        } elseif ($age > 0 || $val > 0) {
            $nuevo = 'agendada';
        } else {
            $nuevo = ($s->origen === 'sin_aviso') ? 'por_justificar' : 'solicitada';
        }
        $db->query("UPDATE suplencias SET estado='" . $db->escape_string($nuevo) . "' WHERE id={$id} LIMIT 1");
    }

    // ── Agregados para el dashboard (superadmin) ────────────────────────────────
    /**
     * Conteo por estado. `conteos()` es lo mismo más el total, así que se deriva de aquí
     * en lugar de repetir el GROUP BY en dos consultas.
     */
    public static function porEstado(): array {
        $out = [];
        $r = self::$db->query("SELECT estado, COUNT(*) n FROM suplencias GROUP BY estado");
        if ($r) while ($row = $r->fetch_assoc()) $out[$row['estado']] = (int)$row['n'];
        return $out;
    }
    public static function porOrigen(): array {
        $out = ['anticipada' => 0, 'sin_aviso' => 0];
        $r = self::$db->query("SELECT origen, COUNT(*) n FROM suplencias GROUP BY origen");
        if ($r) while ($row = $r->fetch_assoc()) $out[$row['origen']] = (int)$row['n'];
        return $out;
    }
    /** Ranking de quién más ha suplido (horas agendadas/validadas). */
    public static function topSuplentes(int $limite = 8): array {
        $limite = max(1, min(20, $limite));
        $r = self::$db->query("
            SELECT u.id, u.nombre, u.avatar,
                   COUNT(*) AS coberturas,
                   SUM(sh.estado_hora='validada') AS validadas
            FROM suplencia_horas sh
            JOIN usuarios u ON u.id = sh.suplente_id
            WHERE sh.suplente_id IS NOT NULL AND sh.estado_hora IN ('agendada','validada')
            GROUP BY u.id
            ORDER BY coberturas DESC, u.nombre ASC
            LIMIT {$limite}
        ");
        $out = [];
        if ($r) while ($row = $r->fetch_assoc()) $out[] = $row;
        return $out;
    }
    /** Ranking de quién más ha faltado. */
    public static function topAusentes(int $limite = 8): array {
        $limite = max(1, min(20, $limite));
        $r = self::$db->query("
            SELECT u.id, u.nombre, u.avatar, COUNT(*) AS faltas
            FROM suplencias s
            JOIN usuarios u ON u.id = s.profesor_ausente_id
            WHERE s.profesor_ausente_id IS NOT NULL
            GROUP BY u.id
            ORDER BY faltas DESC, u.nombre ASC
            LIMIT {$limite}
        ");
        $out = [];
        if ($r) while ($row = $r->fetch_assoc()) $out[] = $row;
        return $out;
    }
    /** Suplencias por mes (últimos 12) para la gráfica de tendencia. */
    public static function porMes(): array {
        $r = self::$db->query("
            SELECT DATE_FORMAT(fecha, '%Y-%m') AS mes, COUNT(*) n
            FROM suplencias
            GROUP BY mes ORDER BY mes ASC
        ");
        $out = [];
        if ($r) while ($row = $r->fetch_assoc()) $out[] = ['mes' => $row['mes'], 'n' => (int)$row['n']];
        return $out;
    }
    /** Materias con más suplencias (desde suplencia_horas). */
    public static function porMateria(int $limite = 8): array {
        $limite = max(1, min(20, $limite));
        $r = self::$db->query("
            SELECT COALESCE(m.nombre,'Sin materia') AS materia, COUNT(*) n
            FROM suplencia_horas sh
            LEFT JOIN materias m ON m.id = sh.materia_id
            GROUP BY materia ORDER BY n DESC LIMIT {$limite}
        ");
        $out = [];
        if ($r) while ($row = $r->fetch_assoc()) $out[] = ['materia' => $row['materia'], 'n' => (int)$row['n']];
        return $out;
    }
    /** Motivos más frecuentes (el formulario los toma de self::MOTIVOS). */
    public static function porMotivo(int $limite = 8): array {
        $limite = max(1, min(20, $limite));
        $r = self::$db->query("
            SELECT COALESCE(NULLIF(TRIM(motivo), ''), 'Sin especificar') AS motivo, COUNT(*) n
            FROM suplencias
            GROUP BY motivo ORDER BY n DESC, motivo ASC LIMIT {$limite}
        ");
        $out = [];
        if ($r) while ($row = $r->fetch_assoc()) $out[] = ['motivo' => $row['motivo'], 'n' => (int)$row['n']];
        return $out;
    }

    /** Resumen diario (conteo por fecha) para el calendario del dashboard. */
    public static function resumenDiario(): array {
        $r = self::$db->query("SELECT fecha, COUNT(*) n FROM suplencias GROUP BY fecha");
        $out = [];
        if ($r) while ($row = $r->fetch_assoc()) $out[] = ['fecha' => $row['fecha'], 'n' => (int)$row['n']];
        return $out;
    }

    /**
     * Detalle por día para el calendario interactivo del tablero: al pulsar una fecha
     * se listan las suplencias de ese día. Se limita a los últimos 18 meses para que
     * la isla JSON no crezca sin control.
     */
    public static function detalleDiario(): array {
        $r = self::$db->query("
            SELECT sup.fecha, sup.estado, sup.origen, sup.motivo,
                   a.nombre AS ausente,
                   COUNT(sh.id) AS horas,
                   SUM(sh.suplente_id IS NOT NULL) AS cubiertas
            FROM suplencias sup
            LEFT JOIN usuarios a ON a.id = sup.profesor_ausente_id
            LEFT JOIN suplencia_horas sh ON sh.suplencia_id = sup.id
            WHERE sup.fecha >= DATE_SUB(CURDATE(), INTERVAL 18 MONTH)
            GROUP BY sup.id
            ORDER BY sup.fecha ASC, a.nombre ASC
        ");
        $out = [];
        if ($r) while ($row = $r->fetch_assoc()) {
            $out[$row['fecha']][] = [
                'ausente'   => $row['ausente'] ?: '—',
                'motivo'    => $row['motivo'] ?: 'Sin especificar',
                'estado'    => $row['estado'],
                'origen'    => $row['origen'],
                'horas'     => (int)$row['horas'],
                'cubiertas' => (int)$row['cubiertas'],
            ];
        }
        return $out;
    }
}
