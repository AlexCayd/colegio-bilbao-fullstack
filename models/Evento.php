<?php
namespace Model;

class Evento extends ActiveRecord {
    protected static $tabla      = 'eventos';
    protected static $columnasDB = ['id', 'fecha', 'fecha_fin', 'tipo', 'titulo', 'descripcion', 'publico'];

    public $id;
    public $fecha;
    public $fecha_fin;
    public $tipo;
    public $titulo;
    public $descripcion;
    public $publico;
    public $creado_en;

    public const TIPOS = ['festivo', 'evento', 'junta', 'entrega', 'suspension'];
    public const TIPO_LABEL = [
        'festivo'    => 'Festivo',
        'evento'     => 'Evento',
        'junta'      => 'Junta',
        'entrega'    => 'Entrega',
        'suspension' => 'Suspensión',
    ];

    public function validar(): array {
        static::$alertas = [];
        $this->titulo = trim((string)($this->titulo ?? ''));
        if ($this->titulo === '') static::setAlerta('error', 'El título del evento es obligatorio');
        $this->fecha = trim((string)($this->fecha ?? ''));
        if ($this->fecha === '' || !\DateTime::createFromFormat('Y-m-d', $this->fecha)) {
            static::setAlerta('error', 'La fecha del evento no es válida');
        }
        if (!\in_array($this->tipo ?? '', self::TIPOS, true)) $this->tipo = 'evento';
        $this->publico = !empty($this->publico) ? 1 : 0;
        return static::$alertas;
    }

    /** Persistencia con NULL real para fecha_fin/descripcion. */
    public function guardar() {
        $db   = self::$db;
        $cols = ['fecha', 'fecha_fin', 'tipo', 'titulo', 'descripcion', 'publico'];
        $sql  = [];
        foreach ($cols as $c) {
            $v = $this->$c;
            if ($v === null || $v === '') {
                $sql[$c] = ($c === 'publico') ? '0' : 'NULL';
            } else {
                $sql[$c] = "'" . $db->escape_string($v) . "'";
            }
        }
        if (!empty($this->id)) {
            $assign = [];
            foreach ($sql as $c => $v) $assign[] = "{$c} = {$v}";
            $ok = $db->query("UPDATE eventos SET " . implode(', ', $assign) . " WHERE id = " . (int)$this->id . " LIMIT 1");
            return ['resultado' => (bool)$ok, 'id' => (int)$this->id];
        }
        $ok = $db->query("INSERT INTO eventos (" . implode(', ', array_keys($sql)) . ") VALUES (" . implode(', ', array_values($sql)) . ")");
        $this->id = $db->insert_id;
        return ['resultado' => (bool)$ok, 'id' => (int)$this->id];
    }

    /** Todos, ordenados por fecha descendente (panel). */
    public static function todos(): array {
        return static::consultarSQL("SELECT * FROM eventos ORDER BY fecha DESC, id DESC");
    }

    /** Eventos públicos futuros/actuales para el calendario de Familias. */
    public static function publicos(): array {
        return static::consultarSQL("SELECT * FROM eventos WHERE publico = 1 ORDER BY fecha ASC");
    }
}
