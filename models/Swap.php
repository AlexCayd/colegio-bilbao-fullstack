<?php
namespace Model;

/**
 * Swap: intercambio PUNTUAL de clases entre dos profesores.
 *
 * ⚠️ En la UI se llama **«Intercambio»**: la clase, la tabla `swap_clases`, las rutas
 * `/dashboard/swaps`, la clave de módulo `swaps` y los tipos de notificación `swap_*`
 * conservan el nombre viejo a propósito. Renombrarlos sería una migración de datos y
 * de permisos por un cambio de rótulo. Al tocar texto que se lee, decir «intercambio».
 *
 * No es una suplencia: nadie falta y nadie cubre a nadie. Es un trato — «da tú mi
 * clase del martes y yo doy la tuya del jueves» — que no altera el horario
 * permanente, solo dice qué pasa esos dos días concretos. Por eso cada lado guarda
 * la pareja (horario_id, fecha) y no solo el horario.
 *
 * Ciclo: pendiente → aceptado/rechazado → validado/denegado.
 * Los tres pasos notifican; el último lo da prefectura o dirección.
 */
class Swap extends ActiveRecord {

    protected static $tabla      = 'swap_clases';
    protected static $columnasDB = ['id', 'solicitante_id', 'destinatario_id',
        'horario_origen_id', 'fecha_origen', 'horario_destino_id', 'fecha_destino',
        'motivo', 'estado', 'respuesta_nota', 'validacion_nota', 'respondido_en',
        'validado_por', 'validado_en', 'creado_por'];

    public $id;
    public $solicitante_id;
    public $destinatario_id;
    public $horario_origen_id;
    public $fecha_origen;
    public $horario_destino_id;
    public $fecha_destino;
    public $motivo;
    public $estado;
    /** Por qué lo rechaza el DESTINATARIO. Obligatoria al rechazar. */
    public $respuesta_nota;
    /**
     * Por qué lo deniega quien COORDINA. Obligatoria al denegar.
     * Va aparte de `respuesta_nota` porque antes compartían columna y la validación
     * pisaba la respuesta del profesor: el solicitante se quedaba sin saber por qué.
     */
    public $validacion_nota;
    public $respondido_en;
    public $validado_por;
    public $validado_en;
    /** Quién lo abrió. Distinto de `solicitante_id` = lo impuso prefectura o dirección. */
    public $creado_por;
    public $creado_en;

    // Aliases por JOIN
    public $solicitante_nombre;
    public $solicitante_avatar;
    public $destinatario_nombre;
    public $destinatario_avatar;
    public $validador_nombre;
    public $origen_materia;
    public $origen_grupo;
    public $origen_aula;
    public $origen_ini;
    public $origen_fin;
    public $origen_etiqueta;
    public $destino_materia;
    public $destino_grupo;
    public $destino_aula;
    public $destino_ini;
    public $destino_fin;
    public $destino_etiqueta;
    public $origen_nivel;
    public $destino_nivel;
    public $creador_nombre;

    /**
     * Ventana de búsqueda: desde el día que se falta, cuántos días hacia delante se
     * pueden ofrecer clases del otro profesor. Un swap a tres semanas vista
     * deja de ser un swap y se convierte en un cambio de horario.
     */
    public const DIAS_VENTANA = 7;

    public const ESTADOS = ['pendiente', 'aceptado', 'rechazado', 'validado', 'denegado', 'cancelado'];
    public const ESTADO_LABEL = [
        'pendiente' => 'Esperando respuesta',
        'aceptado'  => 'Aceptado, falta validar',
        'rechazado' => 'Rechazado',
        'validado'  => 'Validado',
        'denegado'  => 'Denegado',
        'cancelado' => 'Cancelado',
    ];
    /** Color del estado, alineado con la paleta institucional. */
    public const ESTADO_COLOR = [
        'pendiente' => 'warn', 'aceptado' => 'info', 'rechazado' => 'bad',
        'validado'  => 'ok',   'denegado' => 'bad',  'cancelado' => 'nil',
    ];

    public function validar(): array {
        static::$alertas = [];

        if (!$this->solicitante_id)  static::setAlerta('error', 'Falta quién solicita el intercambio');
        if (!$this->destinatario_id) static::setAlerta('error', 'Elige con qué profesor quieres cambiar la clase');
        if ((int)$this->solicitante_id === (int)$this->destinatario_id) {
            static::setAlerta('error', 'No puedes cambiar una clase contigo mismo');
        }
        if (!$this->horario_origen_id)  static::setAlerta('error', 'Elige la clase que no puedes dar');
        if (!$this->horario_destino_id) static::setAlerta('error', 'Elige la clase que darás a cambio');

        foreach (['fecha_origen' => 'la fecha de tu clase', 'fecha_destino' => 'la fecha de la clase que tomas'] as $c => $qué) {
            $v = trim((string)($this->$c ?? ''));
            if ($v === '' || !\DateTime::createFromFormat('!Y-m-d', $v)) {
                static::setAlerta('error', "No se entiende {$qué}");
            }
            $this->$c = $v;
        }

        // La ventana se mide desde la fecha que se falta: es lo que convierte esto en
        // una excepción puntual y no en un cambio de horario encubierto.
        if (empty(static::$alertas['error'])) {
            $ini = new \DateTime($this->fecha_origen);
            $fin = new \DateTime($this->fecha_destino);
            $dif = (int)$ini->diff($fin)->format('%r%a');
            if ($dif < 0) {
                static::setAlerta('error', 'La clase que tomas a cambio no puede ser anterior al día que faltas');
            } elseif ($dif > self::DIAS_VENTANA) {
                static::setAlerta('error', 'El intercambio debe caer dentro de los '
                    . self::DIAS_VENTANA . ' días siguientes al día que faltas');
            }
        }

        if (!\in_array($this->estado ?? '', self::ESTADOS, true)) $this->estado = 'pendiente';
        $this->motivo = mb_substr(trim((string)($this->motivo ?? '')), 0, 255) ?: null;

        return static::$alertas;
    }

    /** Persistencia con NULL real en las FK y las marcas de tiempo opcionales. */
    public function guardar() {
        $db   = self::$db;
        $cols = ['solicitante_id', 'destinatario_id', 'horario_origen_id', 'fecha_origen',
                 'horario_destino_id', 'fecha_destino', 'motivo', 'estado',
                 'respuesta_nota', 'validacion_nota', 'respondido_en',
                 'validado_por', 'validado_en', 'creado_por'];
        $sql = [];
        foreach ($cols as $c) {
            $v = $this->$c;
            $esFk = str_ends_with($c, '_id') || $c === 'validado_por' || $c === 'creado_por';
            if ($v === null || $v === '' || ($esFk && (int)$v === 0)) $sql[$c] = 'NULL';
            else $sql[$c] = "'" . $db->escape_string($v) . "'";
        }
        if (!empty($this->id)) {
            $set = [];
            foreach ($sql as $c => $v) $set[] = "{$c} = {$v}";
            $ok = $db->query("UPDATE swap_clases SET " . implode(', ', $set) . " WHERE id = " . (int)$this->id . " LIMIT 1");
            return ['resultado' => (bool)$ok, 'id' => (int)$this->id];
        }
        $ok = $db->query("INSERT INTO swap_clases (" . implode(', ', array_keys($sql)) . ")
                          VALUES (" . implode(', ', array_values($sql)) . ")");
        $this->id = $db->insert_id;
        return ['resultado' => (bool)$ok, 'id' => (int)$this->id];
    }

    private static function selectBase(): string {
        return "
            SELECT sw.*,
                   s.nombre AS solicitante_nombre,  s.avatar AS solicitante_avatar,
                   d.nombre AS destinatario_nombre, d.avatar AS destinatario_avatar,
                   v.nombre AS validador_nombre,
                   mo.nombre AS origen_materia,  go.nombre AS origen_grupo,  ao.nombre AS origen_aula,
                   po.hora_inicio AS origen_ini, po.hora_fin AS origen_fin,  po.etiqueta AS origen_etiqueta,
                   po.nivel AS origen_nivel,
                   md.nombre AS destino_materia, gd.nombre AS destino_grupo, ad.nombre AS destino_aula,
                   pd.hora_inicio AS destino_ini, pd.hora_fin AS destino_fin, pd.etiqueta AS destino_etiqueta,
                   pd.nivel AS destino_nivel,
                   cr.nombre AS creador_nombre
            FROM swap_clases sw
            LEFT JOIN usuarios s ON s.id = sw.solicitante_id
            LEFT JOIN usuarios d ON d.id = sw.destinatario_id
            LEFT JOIN usuarios v ON v.id = sw.validado_por
            LEFT JOIN usuarios cr ON cr.id = sw.creado_por
            LEFT JOIN horarios ho ON ho.id = sw.horario_origen_id
            LEFT JOIN periodos po ON po.id = ho.periodo_id
            LEFT JOIN materias mo ON mo.id = ho.materia_id
            LEFT JOIN grupos   go ON go.id = ho.grupo_id
            LEFT JOIN aulas    ao ON ao.id = ho.aula_id
            LEFT JOIN horarios hd ON hd.id = sw.horario_destino_id
            LEFT JOIN periodos pd ON pd.id = hd.periodo_id
            LEFT JOIN materias md ON md.id = hd.materia_id
            LEFT JOIN grupos   gd ON gd.id = hd.grupo_id
            LEFT JOIN aulas    ad ON ad.id = hd.aula_id
        ";
    }

    public static function encontrar(int $id): ?self {
        $r = static::consultarSQL(self::selectBase() . " WHERE sw.id = " . (int)$id . " LIMIT 1");
        return $r[0] ?? null;
    }

    /**
     * Swaps en los que participa un profesor (como solicitante o destinatario).
     * Los pendientes de su respuesta salen primero: son los que reclaman acción.
     */
    public static function deProfesor(int $uid): array {
        $uid = (int)$uid;
        return static::consultarSQL(
            self::selectBase() .
            " WHERE sw.solicitante_id = {$uid} OR sw.destinatario_id = {$uid}
              ORDER BY (sw.estado = 'pendiente' AND sw.destinatario_id = {$uid}) DESC,
                       sw.fecha_origen DESC, sw.id DESC");
    }

    /**
     * Todos, para quien coordina. `$soloPorValidar` deja solo los ya aceptados.
     *
     * `$excluirUid` saca de la lista los swaps de quien mira: un admin o un
     * prefecto que además imparte los tiene ya en "Mis swaps", y salían dos
     * veces en la misma pantalla.
     */
    public static function todos(bool $soloPorValidar = false, int $excluirUid = 0, array $niveles = []): array {
        $cond = [];
        if ($soloPorValidar) $cond[] = "sw.estado = 'aceptado'";
        if ($excluirUid > 0) {
            $u = (int)$excluirUid;
            $cond[] = "sw.solicitante_id <> {$u} AND sw.destinatario_id <> {$u}";
        }
        $n = self::sqlNivel($niveles);
        if ($n !== '') $cond[] = $n;
        $w = $cond ? ' WHERE ' . implode(' AND ', $cond) : '';
        return static::consultarSQL(
            self::selectBase() . $w .
            " ORDER BY FIELD(sw.estado,'aceptado','pendiente','validado','rechazado','denegado','cancelado'),
                       sw.fecha_origen DESC, sw.id DESC");
    }

    /**
     * Condición de nivel para las direcciones. Criterio **OR**, nunca AND: un
     * swap cruza dos clases y puede cruzar dos niveles, así que a la dirección
     * de Primaria le compete si cualquiera de los dos lados es de Primaria.
     *
     * El OR tiene CUATRO términos, no dos: los niveles de las dos clases y los niveles
     * declarados de los dos profesores. La segmentación de una dirección sigue a **su
     * personal**, no solo al calendario, y aquí eso además tapa un agujero: los dos
     * `horario_*_id` son `ON DELETE SET NULL`, así que un intercambio cuya clase se borró
     * se queda con `po.nivel`/`pd.nivel` en NULL y desaparecía para TODAS las direcciones
     * —incluida la que debía validarlo— sin que nadie pudiera notarlo.
     *
     * selectBase() ya une `po`/`pd` y también `s`/`d` (los dos profesores), así que no
     * añade ni un JOIN — pero por eso mismo solo vale sobre consultas que arranquen de
     * selectBase().
     */
    private static function sqlNivel(array $niveles): string {
        $ok = array_values(array_intersect(Materia::NIVELES, $niveles));
        if (!$ok || count($ok) === count(Materia::NIVELES)) return '';
        $in = implode(',', array_map(fn($x) => "'" . self::$db->escape_string($x) . "'", $ok));

        // `niveles` es un SET: se consulta con FIND_IN_SET, no con IN.
        $porPersona = [];
        foreach (['s', 'd'] as $alias) {
            foreach ($ok as $n) {
                $porPersona[] = "FIND_IN_SET('" . self::$db->escape_string($n) . "', {$alias}.niveles)";
            }
        }
        return "(po.nivel IN ({$in}) OR pd.nivel IN ({$in}) OR " . implode(' OR ', $porPersona) . ")";
    }

    /**
     * Cuántos esperan validación. Alimenta el badge del subnav, así que acepta el
     * mismo alcance que todos(): un badge que no cuadra con la lista es ruido.
     */
    public static function contarPorValidar(array $niveles = []): int {
        $n = self::sqlNivel($niveles);
        // Con filtro hace falta el contexto de periodos que da selectBase(); sin él,
        // la consulta directa sobre swap_clases es más barata.
        if ($n === '') {
            $r = self::$db->query("SELECT COUNT(*) n FROM swap_clases WHERE estado = 'aceptado'");
            return $r ? (int)$r->fetch_assoc()['n'] : 0;
        }
        return count(static::consultarSQL(
            self::selectBase() . " WHERE sw.estado = 'aceptado' AND {$n}"));
    }

    /** Niveles que toca un swap (uno o dos). Alimenta el aviso a dirección. */
    public function niveles(): array {
        return array_values(array_intersect(
            Materia::NIVELES,
            array_filter([$this->origen_nivel, $this->destino_nivel])));
    }

    /** Cuántos esperan la respuesta de este profesor. */
    public static function contarPendientesDe(int $uid): int {
        $r = self::$db->query(
            "SELECT COUNT(*) n FROM swap_clases
              WHERE destinatario_id = " . (int)$uid . " AND estado = 'pendiente'");
        return $r ? (int)$r->fetch_assoc()['n'] : 0;
    }

    /** ¿Este swap ya cerró? Los cerrados no admiten más cambios de estado. */
    public function cerrado(): bool {
        return \in_array($this->estado, ['rechazado', 'validado', 'denegado', 'cancelado'], true);
    }
}
