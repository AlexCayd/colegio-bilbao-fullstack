<?php
namespace Model;

class Suplencia extends ActiveRecord {

    protected static $tabla      = 'suplencias';
    protected static $columnasDB = ['id', 'fecha', 'ausente_id', 'suplente_id', 'grupo', 'materia', 'motivo', 'notas', 'estado'];

    public $id;
    public $fecha;
    public $ausente_id;
    public $suplente_id;
    public $grupo;
    public $materia;
    public $motivo;
    public $notas;
    public $estado;
    public $creado_en;
    public $actualizado_en;

    // Aliases traídos por JOIN a usuarios
    public $ausente_nombre;
    public $ausente_avatar;
    public $suplente_nombre;
    public $suplente_avatar;

    public function validar(): array {
        static::$alertas = [];

        $this->fecha = trim((string)($this->fecha ?? ''));
        if ($this->fecha === '' || !\DateTime::createFromFormat('Y-m-d', $this->fecha)) {
            static::setAlerta('error', 'La fecha de la suplencia no es válida');
        }
        if (empty($this->ausente_id)) {
            static::setAlerta('error', 'Selecciona al docente ausente');
        }
        if (!empty($this->ausente_id) && !empty($this->suplente_id) && (int)$this->ausente_id === (int)$this->suplente_id) {
            static::setAlerta('error', 'El suplente no puede ser el mismo docente ausente');
        }
        if (!\in_array($this->estado ?? '', ['pendiente', 'confirmada', 'cancelada'], true)) {
            $this->estado = 'pendiente';
        }
        // Normalizar FKs vacías a null
        $this->ausente_id  = !empty($this->ausente_id)  ? (int)$this->ausente_id  : null;
        $this->suplente_id = !empty($this->suplente_id) ? (int)$this->suplente_id : null;

        return static::$alertas;
    }

    /**
     * Persistencia con soporte de NULL real: el ORM base envuelve todo en comillas y no
     * permite NULL (necesario para suplente_id sin asignar y FKs ON DELETE SET NULL).
     */
    public function guardar() {
        $db   = self::$db;
        $cols = ['fecha', 'ausente_id', 'suplente_id', 'grupo', 'materia', 'motivo', 'notas', 'estado'];
        $sql  = [];
        foreach ($cols as $c) {
            $v = $this->$c;
            if ($v === null || $v === '' || (in_array($c, ['ausente_id', 'suplente_id'], true) && (int)$v === 0)) {
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

    /**
     * Listado con nombres de ausente/suplente. Filtros: q (busca en ambos nombres),
     * estado, desde, hasta.
     */
    public static function listar(array $f = []): array {
        $where = [];
        if (!empty($f['q'])) {
            $q = self::$db->escape_string($f['q']);
            $where[] = "(a.nombre LIKE '%{$q}%' OR s.nombre LIKE '%{$q}%' OR sup.grupo LIKE '%{$q}%' OR sup.materia LIKE '%{$q}%')";
        }
        if (!empty($f['estado']) && \in_array($f['estado'], ['pendiente', 'confirmada', 'cancelada'], true)) {
            $where[] = "sup.estado = '" . self::$db->escape_string($f['estado']) . "'";
        }
        if (!empty($f['desde'])) {
            $where[] = "sup.fecha >= '" . self::$db->escape_string($f['desde']) . "'";
        }
        if (!empty($f['hasta'])) {
            $where[] = "sup.fecha <= '" . self::$db->escape_string($f['hasta']) . "'";
        }
        $sql = "
            SELECT sup.*,
                   a.nombre AS ausente_nombre,  a.avatar AS ausente_avatar,
                   s.nombre AS suplente_nombre, s.avatar AS suplente_avatar
            FROM suplencias sup
            LEFT JOIN usuarios a ON a.id = sup.ausente_id
            LEFT JOIN usuarios s ON s.id = sup.suplente_id
        ";
        if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
        $sql .= ' ORDER BY sup.fecha DESC, sup.id DESC';
        return static::consultarSQL($sql);
    }

    public static function encontrarConNombres(int $id): ?self {
        $id = (int)$id;
        $sql = "
            SELECT sup.*,
                   a.nombre AS ausente_nombre,  a.avatar AS ausente_avatar,
                   s.nombre AS suplente_nombre, s.avatar AS suplente_avatar
            FROM suplencias sup
            LEFT JOIN usuarios a ON a.id = sup.ausente_id
            LEFT JOIN usuarios s ON s.id = sup.suplente_id
            WHERE sup.id = {$id}
            LIMIT 1
        ";
        $r = static::consultarSQL($sql);
        return $r[0] ?? null;
    }

    /** Conteos por estado para las tarjetas del encabezado. */
    public static function conteos(): array {
        $out = ['total' => 0, 'pendiente' => 0, 'confirmada' => 0, 'cancelada' => 0];
        $r = self::$db->query("SELECT estado, COUNT(*) AS n FROM suplencias GROUP BY estado");
        if ($r) {
            while ($row = $r->fetch_assoc()) {
                $out[$row['estado']] = (int)$row['n'];
                $out['total'] += (int)$row['n'];
            }
        }
        return $out;
    }
}
