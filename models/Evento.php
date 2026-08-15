<?php
namespace Model;

class Evento extends ActiveRecord {
    protected static $tabla      = 'eventos';
    protected static $columnasDB = ['id', 'fecha', 'fecha_fin', 'tipo', 'titulo', 'descripcion', 'audiencia', 'niveles'];

    public $id;
    public $fecha;
    public $fecha_fin;
    public $tipo;
    public $titulo;
    public $descripcion;
    /** interno | familias | estudiantes. Sustituye al antiguo flag `publico`. */
    public $audiencia;
    /** SET de niveles a los que va dirigido. NULL/vacío = todo el colegio. */
    public $niveles;
    public $creado_en;

    public const TIPOS = ['festivo', 'evento', 'junta', 'entrega', 'suspension'];
    public const TIPO_LABEL = [
        'festivo'    => 'Festivo',
        'evento'     => 'Evento',
        'junta'      => 'Junta',
        'entrega'    => 'Entrega',
        'suspension' => 'Suspensión',
    ];

    /**
     * A quién va dirigido. Es excluyente: un evento tiene un público, y de él depende
     * dónde se publica. `interno` no sale nunca del panel.
     */
    public const AUDIENCIAS = ['interno', 'familias', 'estudiantes'];
    public const AUDIENCIA_LABEL = [
        'interno'     => 'Interno',
        'familias'    => 'Familias',
        'estudiantes' => 'Estudiantes',
    ];
    public const AUDIENCIA_DESC = [
        'interno'     => 'Solo lo ven los colaboradores, dentro del panel.',
        'familias'    => 'Además aparece en el calendario de Comunidad › Familias.',
        'estudiantes' => 'Además aparece en Comunidad › Estudiantes.',
    ];
    public const AUDIENCIA_ICONO = [
        'interno'     => 'fa-lock',
        'familias'    => 'fa-people-roof',
        'estudiantes' => 'fa-graduation-cap',
    ];

    public function validar(): array {
        static::$alertas = [];
        $this->titulo = trim((string)($this->titulo ?? ''));
        if ($this->titulo === '') static::setAlerta('error', 'El título del evento es obligatorio');
        $this->fecha = trim((string)($this->fecha ?? ''));
        if ($this->fecha === '' || !\DateTime::createFromFormat('Y-m-d', $this->fecha)) {
            static::setAlerta('error', 'La fecha del evento no es válida');
        }
        if ($this->fecha_fin && $this->fecha && $this->fecha_fin < $this->fecha) {
            static::setAlerta('error', 'La fecha de fin no puede ser anterior a la de inicio');
        }
        if (!\in_array($this->tipo ?? '', self::TIPOS, true)) $this->tipo = 'evento';
        if (!\in_array($this->audiencia ?? '', self::AUDIENCIAS, true)) $this->audiencia = 'interno';
        $this->normalizarNiveles();
        return static::$alertas;
    }

    /**
     * Deja `niveles` como CSV en el orden canónico Maternal→Bachillerato, o NULL si
     * están todos o ninguno: "va dirigido a los cinco niveles" y "no se acotó" son la
     * misma cosa para quien lee el calendario, y guardarlo como NULL evita que la UI
     * pinte cinco chips redundantes en cada fila.
     */
    private function normalizarNiveles(): void {
        $raw   = $this->niveles;
        $lista = \is_array($raw) ? $raw : array_filter(array_map('trim', explode(',', (string)$raw)));
        $lista = array_values(array_intersect(Materia::NIVELES, $lista));
        $this->niveles = (!$lista || count($lista) === count(Materia::NIVELES)) ? null : implode(',', $lista);
    }

    /** Niveles como array, ya troceado. Vacío = todo el colegio. */
    public function nivelesLista(): array {
        return array_filter(array_map('trim', explode(',', (string)$this->niveles)));
    }

    /** Etiqueta legible del alcance, para listados y tooltips. */
    public function alcance(): string {
        $l = $this->nivelesLista();
        return $l ? implode(' · ', $l) : 'Todo el colegio';
    }

    /** Persistencia con NULL real para fecha_fin/descripcion/niveles. */
    public function guardar() {
        $db   = self::$db;
        $cols = ['fecha', 'fecha_fin', 'tipo', 'titulo', 'descripcion', 'audiencia', 'niveles'];
        $sql  = [];
        foreach ($cols as $c) {
            $v = $this->$c;
            if ($v === null || $v === '') {
                $sql[$c] = ($c === 'audiencia') ? "'interno'" : 'NULL';
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

    /**
     * Eventos de una audiencia concreta, para las páginas públicas.
     * `interno` nunca sale del panel, así que no se acepta aquí.
     */
    public static function porAudiencia(string $audiencia): array {
        if (!\in_array($audiencia, ['familias', 'estudiantes'], true)) return [];
        $a = self::$db->escape_string($audiencia);
        return static::consultarSQL("SELECT * FROM eventos WHERE audiencia = '{$a}' ORDER BY fecha ASC");
    }

    /**
     * Compat: el calendario de Familias.
     * @deprecated Usa porAudiencia('familias'); se conserva por los llamadores antiguos.
     */
    public static function publicos(): array {
        return self::porAudiencia('familias');
    }

    /** Los de un día concreto (incluye los de varios días que lo abarcan). */
    public static function delDia(string $fecha): array {
        $f = self::$db->escape_string($fecha);
        return static::consultarSQL(
            "SELECT * FROM eventos
              WHERE fecha = '{$f}' OR (fecha_fin IS NOT NULL AND fecha <= '{$f}' AND fecha_fin >= '{$f}')
           ORDER BY fecha ASC, id ASC");
    }
}
