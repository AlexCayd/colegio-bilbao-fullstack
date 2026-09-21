<?php
namespace Model;

class SuplenciaHora extends ActiveRecord {

    protected static $tabla      = 'suplencia_horas';
    protected static $columnasDB = ['id', 'suplencia_id', 'periodo_id', 'tipo', 'grupo_id', 'aula_id', 'materia_id', 'lugar_id', 'suplente_id', 'estado_hora', 'validado_en'];

    public $id;
    public $suplencia_id;
    public $periodo_id;
    /** 'clase' | 'guardia'. Una guardia de receso también se cubre. */
    public $tipo;
    public $grupo_id;
    public $aula_id;
    public $materia_id;
    public $lugar_id;
    public $suplente_id;
    public $estado_hora;
    public $validado_en;
    /** Quién no se presentó. Se conserva aunque la hora se reasigne a otro. */
    public $incumplio_id;
    public $incumplido_en;
    /**
     * ¿El ausente dejó trabajo para el grupo? NULL = sin revisar · 1 = sí · 0 = no.
     * Se persiste con marcarTrabajo(), no por el ORM: NULL y 0 significan cosas
     * distintas y el ActiveRecord base no sabe escribir NULL real.
     */
    public $dejo_trabajo;
    public $trabajo_notas;
    public $trabajo_por;
    public $trabajo_en;

    // Aliases por JOIN
    public $periodo_etiqueta;
    public $periodo_orden;
    public $periodo_inicio;
    public $periodo_fin;
    public $periodo_nivel;
    public $grupo_nombre;
    public $aula_nombre;
    public $materia;        // alias de materias.nombre
    public $materia_nivel;
    public $suplente_nombre;
    public $suplente_avatar;
    public $incumplio_nombre;
    public $trabajo_por_nombre;
    public $lugar_nombre;
    public $s_fecha;
    public $s_motivo;
    public $s_notas;
    public $s_estado;       // alias de suplencias.estado (histórico del suplente)
    public $s_origen;       // alias de suplencias.origen (cola de "¿dejó trabajo?")
    public $ausente_nombre;
    public $ausente_avatar;

    private const DOW_DIA = [1 => 'lunes', 2 => 'martes', 3 => 'miercoles', 4 => 'jueves', 5 => 'viernes', 6 => null, 7 => null];

    /**
     * Holgura de la regla de equidad en sugerir(): cuántas coberturas por encima
     * del mínimo del claustro se toleran antes de bloquear a un candidato.
     * Con 0 (el comportamiento anterior) casi siempre quedaba un único elegible.
     */
    public const MARGEN_EQUIDAD = 3;

    /**
     * Minutos libres que un suplente debe conservar tras aceptar una cobertura.
     * Va en minutos y no en "una hora libre" porque con jornada por nivel un bloque
     * dura 45' en Maternal/Kinder y 50' en el resto: contar bloques no es comparable.
     * 40 queda por debajo del bloque más corto del colegio, así que para un profesor de
     * un solo nivel se comporta igual que la regla anterior.
     */
    public const DESCANSO_MIN = 40;

    /**
     * Persistencia con NULL real.
     *
     * ⚠️ `tipo` y `lugar_id` van en la lista aunque los formularios no los manden: los
     * declara $columnasDB desde el principio y esta función los ignoraba, así que TODA
     * hora nacía como `tipo='clase'` y `lugar_nombre` salía siempre vacío. Eso rompía
     * dos cosas: pendientesTrabajo() filtra `tipo <> 'guardia'` y colaba las guardias en
     * la cola de "¿dejó trabajo?" —en el patio no hay trabajo que dejar—, y una guardia
     * suplida no decía dónde. Los rellena guardarHoras() desde el horario del ausente.
     */
    public function guardar() {
        $db   = self::$db;
        $cols = ['suplencia_id', 'periodo_id', 'grupo_id', 'aula_id', 'materia_id',
                 'tipo', 'lugar_id', 'suplente_id', 'estado_hora', 'validado_en'];
        // La columna es NOT NULL con DEFAULT 'clase': dejarla vacía escribiría '' y el
        // ENUM lo guardaría como cadena vacía, que no casa con ninguna de las dos ramas.
        if (empty($this->tipo)) $this->tipo = 'clase';
        $sql  = [];
        foreach ($cols as $c) {
            $v = $this->$c;
            if ($v === null || $v === '' || (in_array($c, ['grupo_id', 'aula_id', 'materia_id', 'lugar_id', 'suplente_id'], true) && (int)$v === 0)) {
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
                   lg.nombre AS lugar_nombre,
                   s.nombre AS suplente_nombre, s.avatar AS suplente_avatar,
                   inc.nombre AS incumplio_nombre,
                   tr.nombre AS trabajo_por_nombre
            FROM suplencia_horas sh
            LEFT JOIN periodos p ON p.id = sh.periodo_id
            LEFT JOIN grupos   g ON g.id = sh.grupo_id
            LEFT JOIN aulas    a ON a.id = sh.aula_id
            LEFT JOIN materias m ON m.id = sh.materia_id
            LEFT JOIN lugares_guardia lg ON lg.id = sh.lugar_id
            LEFT JOIN usuarios s   ON s.id   = sh.suplente_id
            LEFT JOIN usuarios inc ON inc.id = sh.incumplio_id
            LEFT JOIN usuarios tr  ON tr.id  = sh.trabajo_por
            WHERE sh.suplencia_id = {$id}
            ORDER BY p.hora_inicio ASC, p.orden ASC
        ";
        return static::consultarSQL($sql);
    }

    /** Una hora con sus nombres resueltos (para redactar avisos legibles). */
    public static function detalle(int $horaId): ?self {
        $id  = (int)$horaId;
        $sql = "
            SELECT sh.*,
                   p.etiqueta AS periodo_etiqueta, p.nivel AS periodo_nivel,
                   p.hora_inicio AS periodo_inicio, p.hora_fin AS periodo_fin,
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
            ORDER BY sup.fecha ASC, p.hora_inicio ASC
        ";
        return static::consultarSQL($sql);
    }

    /**
     * TODAS las coberturas de un suplente, sea cual sea su estado, de la más reciente a
     * la más antigua. Es el histórico: `porValidarDeSuplente()` filtra `agendada`, así
     * que una vez confirmada una cobertura desaparecía de la vista y el profesor no
     * tenía dónde consultar lo que ya había hecho.
     *
     * @param int $limite 0 = sin límite
     */
    public static function historicoDeSuplente(int $suplenteId, int $limite = 0): array {
        $id  = (int)$suplenteId;
        $lim = $limite > 0 ? ' LIMIT ' . (int)$limite : '';
        $sql = "
            SELECT sh.*, sup.fecha AS s_fecha, sup.motivo AS s_motivo, sup.notas AS s_notas,
                   sup.estado AS s_estado,
                   p.etiqueta AS periodo_etiqueta, p.orden AS periodo_orden, p.nivel AS periodo_nivel,
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
            WHERE sh.suplente_id = {$id}
            ORDER BY sup.fecha DESC, p.hora_inicio ASC{$lim}
        ";
        return static::consultarSQL($sql);
    }

    /**
     * Histórico de coberturas de TODO el plantel: quién faltó y quién le cubrió.
     *
     * Lo consulta cualquiera con el módulo de suplencias, también un profesor raso, así
     * que selecciona columnas en LISTA BLANCA en vez de `sup.*`: `motivo`, `notas` y
     * `justificante` son datos de terceros (un parte médico, por ejemplo) y solo los ve
     * quien coordina, desde la agenda.
     *
     * Solo horas ya asignadas (`suplente_id IS NOT NULL`): una hora que nadie ha tomado
     * todavía es trabajo pendiente de prefectura, no historia.
     *
     * @param int $limite 0 = sin límite
     */
    public static function historialPlantel(int $limite = 0): array {
        $lim = $limite > 0 ? ' LIMIT ' . (int)$limite : '';
        $sql = "
            SELECT sh.id, sh.estado_hora,
                   sup.fecha AS s_fecha,
                   au.nombre AS ausente_nombre,
                   s.nombre  AS suplente_nombre
            FROM suplencia_horas sh
            JOIN suplencias sup ON sup.id = sh.suplencia_id
            LEFT JOIN usuarios au ON au.id = sup.profesor_ausente_id
            LEFT JOIN usuarios s  ON s.id  = sh.suplente_id
            WHERE sh.suplente_id IS NOT NULL
            ORDER BY sup.fecha DESC, sh.id DESC{$lim}
        ";
        return static::consultarSQL($sql);
    }

    /**
     * Coberturas ya vencidas (fecha pasada) sin confirmar y sin recordatorio previo.
     * Alimenta el aviso que se emite al entrar al panel: si nadie confirma, la hora
     * se quedaba 'agendada' para siempre y la suplencia nunca llegaba a 'completada'.
     */
    public static function vencidasSinRecordatorio(int $suplenteId): array {
        $id = (int)$suplenteId;
        $sql = "
            SELECT sh.id, sh.suplencia_id, sup.fecha AS s_fecha,
                   p.etiqueta AS periodo_etiqueta,
                   g.nombre AS grupo_nombre, m.nombre AS materia
            FROM suplencia_horas sh
            JOIN suplencias sup ON sup.id = sh.suplencia_id
            LEFT JOIN periodos p ON p.id = sh.periodo_id
            LEFT JOIN grupos   g ON g.id = sh.grupo_id
            LEFT JOIN materias m ON m.id = sh.materia_id
            WHERE sh.suplente_id = {$id}
              AND sh.estado_hora = 'agendada'
              AND sh.recordatorio_en IS NULL
              AND sup.fecha < CURDATE()
            ORDER BY sup.fecha ASC
        ";
        return static::consultarSQL($sql);
    }

    /** Marca que ya se avisó de esta hora, para no repetir el recordatorio en cada carga. */
    public static function marcarRecordatorio(int $horaId): void {
        $horaId = (int)$horaId;
        self::$db->query("UPDATE suplencia_horas SET recordatorio_en = NOW() WHERE id = {$horaId} LIMIT 1");
    }

    /** Horas de una suplencia que ya vencieron sin confirmar (para que las cierre prefectura). */
    public static function vencidasDeSuplencia(int $suplenciaId): array {
        $id = (int)$suplenciaId;
        $sql = "
            SELECT sh.id
            FROM suplencia_horas sh
            JOIN suplencias sup ON sup.id = sh.suplencia_id
            WHERE sh.suplencia_id = {$id}
              AND sh.estado_hora = 'agendada'
              AND sup.fecha < CURDATE()
        ";
        return array_map(fn($r) => (int)$r->id, static::consultarSQL($sql));
    }

    /**
     * ¿Esa hora pertenece a esa suplencia? Guard de pertenencia para todo POST que
     * reciba `hora_id` y `id` por separado: el guard de alcance valida la suplencia, no
     * la hora, y sin esto se puede tocar la hora de otra mandando el par cruzado.
     */
    public static function esDeSuplencia(int $horaId, int $suplenciaId): bool {
        $horaId = (int)$horaId; $suplenciaId = (int)$suplenciaId;
        if ($horaId <= 0 || $suplenciaId <= 0) return false;
        $r = self::$db->query(
            "SELECT 1 FROM suplencia_horas WHERE id = {$horaId} AND suplencia_id = {$suplenciaId} LIMIT 1");
        return (bool)($r && $r->num_rows);
    }

    /**
     * ¿Hay alguna razón para NO asignarle esta hora a este suplente?
     * Devuelve `null` si se puede, o el texto del impedimento.
     *
     * ⚠️ Es el guard de SERVIDOR de la asignación, y existe porque no había ninguno: la
     * regla «no tiene clase a esa hora» vivía solo en blog-suplencias-agendar.js, que se
     * limita a no pintar el botón de confirmar. Cualquier POST que no viniera de ese
     * botón —una pestaña vieja, el botón atrás, un reenvío de formulario, dos
     * coordinadores a la vez— escribía sin que nadie mirase.
     *
     * No reimplementa las reglas: pregunta a sugerir(), que es la única fuente de verdad.
     * Cuesta lo mismo que pintar la lista de candidatos, y se paga una vez por
     * asignación.
     *
     * Comprueba además que la hora pertenezca a la suplencia del POST cuando se le pasa
     * `$suplenciaId`. Es la misma precaución que marcarTrabajo(): requireAlcance() valida
     * la SUPLENCIA, no la hora, así que sin esto un coordinador con alcance sobre A podía
     * tocar una hora de B mandando `id=A&hora_id=<hora de B>`.
     */
    public static function motivoBloqueo(int $horaId, int $suplenteId, int $suplenciaId = 0): ?string {
        $horaId = (int)$horaId; $suplenteId = (int)$suplenteId; $suplenciaId = (int)$suplenciaId;
        if ($suplenteId <= 0) return 'No se indicó a quién asignar la cobertura.';

        $sql = "SELECT sh.periodo_id, sh.suplencia_id, sup.fecha, sup.estado,
                       sup.profesor_ausente_id
                  FROM suplencia_horas sh
                  JOIN suplencias sup ON sup.id = sh.suplencia_id
                 WHERE sh.id = {$horaId} LIMIT 1";
        $r = self::$db->query($sql);
        $h = $r ? $r->fetch_assoc() : null;
        if (!$h) return 'Esa hora ya no existe.';

        if ($suplenciaId > 0 && (int)$h['suplencia_id'] !== $suplenciaId) {
            return 'Esa hora no pertenece a esta suplencia.';
        }
        if ($h['estado'] === 'cancelada') {
            return 'La suplencia está cancelada: ya no hay nada que cubrir.';
        }

        foreach (self::sugerir($h['fecha'], (int)$h['periodo_id'], (int)$h['profesor_ausente_id']) as $c) {
            if ((int)$c['id'] !== $suplenteId) continue;
            return $c['elegible'] ? null : ($c['motivo'] ?: 'No cumple las reglas de suplencia.');
        }

        // No está en la lista: o no es profesor, o tiene puede_suplir = 0, o es el propio
        // ausente. sugerir() ya los descartó antes de evaluarlos, así que no hay motivo
        // que copiar y hay que decirlo en genérico.
        return 'Esa persona no puede cubrir suplencias.';
    }

    /**
     * Asigna un suplente a una hora (pasa a 'agendada').
     *
     * ⚠️ NO valida: escribe. Quien llame tiene que haber pasado antes por
     * motivoBloqueo(), que es donde vive la regla. Se mantienen separados para que el
     * llamador pueda DECIR por qué no se pudo en vez de tragarse un `false` mudo.
     */
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

    /**
     * El suplente valida que cubrió la hora. Solo si él es el suplente asignado
     * y **la clase ya ocurrió**: sin el `sup.fecha <= CURDATE()` se podía confirmar
     * una cobertura de dentro de tres semanas, que es justo lo contrario de lo que
     * promete la UI ("podrás confirmarla en cuanto la hayas impartido").
     */
    public static function validarHora(int $horaId, int $suplenteId): bool {
        $horaId = (int)$horaId; $suplenteId = (int)$suplenteId;
        $ok = self::$db->query("
            UPDATE suplencia_horas sh
            JOIN suplencias sup ON sup.id = sh.suplencia_id
            SET sh.estado_hora = 'validada', sh.validado_en = NOW()
            WHERE sh.id = {$horaId}
              AND sh.suplente_id = {$suplenteId}
              AND sh.estado_hora = 'agendada'
              AND sup.fecha <= CURDATE()
        ");
        return $ok && self::$db->affected_rows > 0;
    }

    /**
     * Cierre por prefectura de una cobertura vencida que el suplente no confirmó.
     * `$cubrio = true` la da por impartida; `false` la devuelve a 'pendiente' para
     * que se reasigne. Sin esto, un suplente que nunca responde dejaba la suplencia
     * bloqueada indefinidamente.
     */
    public static function resolverPorPrefectura(int $horaId, bool $cubrio): bool {
        $horaId = (int)$horaId;
        // "No cubrió" ya no devuelve la hora a `pendiente` a secas: eso borraba al
        // suplente y con él la única prueba de que alguien faltó a su cobertura, así
        // que el incumplimiento no llegaba a las estadísticas. Ahora queda registrado
        // en `incumplio_id` y el estado es `no_cubierta`, que es una incidencia
        // cerrada y no una hora que nadie ha tomado todavía.
        $set = $cubrio
            ? "sh.estado_hora = 'validada', sh.validado_en = NOW()"
            : "sh.estado_hora = 'no_cubierta', sh.incumplio_id = sh.suplente_id,
               sh.incumplido_en = NOW(), sh.suplente_id = NULL, sh.validado_en = NULL";
        $ok = self::$db->query("
            UPDATE suplencia_horas sh
            JOIN suplencias sup ON sup.id = sh.suplencia_id
            SET {$set}
            WHERE sh.id = {$horaId}
              AND sh.estado_hora = 'agendada'
              AND sup.fecha <= CURDATE()
        ");
        return $ok && self::$db->affected_rows > 0;
    }

    /**
     * Devuelve una hora marcada `no_cubierta` al circuito para reasignarla.
     * `incumplio_id` NO se borra: la incidencia sigue imputada a quien faltó aunque
     * la hora acabe cubriéndola otro.
     */
    public static function reabrirHora(int $horaId): bool {
        $ok = self::$db->query(
            "UPDATE suplencia_horas
                SET estado_hora = 'pendiente', suplente_id = NULL, validado_en = NULL
              WHERE id = " . (int)$horaId . " AND estado_hora = 'no_cubierta' LIMIT 1");
        return $ok && self::$db->affected_rows > 0;
    }

    /**
     * Ranking de incumplimientos por profesor, para el tablero.
     * @return array<int, array{id:int,nombre:string,avatar:?string,n:int}>
     */
    public static function topIncumplimientos(int $limite = 8, array $niveles = []): array {
        $out = [];
        $n = Suplencia::sqlNivelHora($niveles, 'sh');
        $r = self::$db->query(
            "SELECT u.id, u.nombre, u.avatar, COUNT(*) n
               FROM suplencia_horas sh
               JOIN usuarios u ON u.id = sh.incumplio_id
              WHERE sh.incumplio_id IS NOT NULL{$n}
           GROUP BY u.id, u.nombre, u.avatar
           ORDER BY n DESC, u.nombre ASC
              LIMIT " . (int)$limite);
        if ($r) while ($row = $r->fetch_assoc()) {
            $out[] = ['id' => (int)$row['id'], 'nombre' => $row['nombre'],
                      'avatar' => $row['avatar'], 'n' => (int)$row['n']];
        }
        return $out;
    }

    /** Total de coberturas incumplidas (tarjeta del tablero). */
    public static function contarIncumplimientos(array $niveles = []): int {
        $n = Suplencia::sqlNivelHora($niveles, 'sh');
        $r = self::$db->query("SELECT COUNT(*) n FROM suplencia_horas sh WHERE sh.incumplio_id IS NOT NULL{$n}");
        return $r ? (int)$r->fetch_assoc()['n'] : 0;
    }

    // ── ¿El profesor ausente dejó trabajo para el grupo? ─────────────────────────
    // Lo registra prefectura al coordinar la ausencia. Va por HORA y no por
    // suplencia: un profesor puede dejar material para su clase de 3º y no para la
    // de 5º, y así el dato se cruza con grupo, materia y nivel.

    /**
     * Marca si el ausente dejó trabajo para esa hora.
     *
     * Persistencia manual porque el ORM base envuelve todo en comillas y no sabe
     * escribir NULL real — y aquí NULL significa algo distinto de 0: "todavía sin
     * revisar" no es "no dejó".
     *
     * @param bool|null $dejo null devuelve la hora a "sin revisar"
     */
    public static function marcarTrabajo(int $horaId, ?bool $dejo, ?string $notas, int $uid): bool {
        $db     = self::$db;
        $horaId = (int) $horaId;
        $uid    = (int) $uid;
        if ($horaId <= 0) return false;

        if ($dejo === null) {
            $ok = $db->query("UPDATE suplencia_horas
                                 SET dejo_trabajo = NULL, trabajo_notas = NULL,
                                     trabajo_por = NULL, trabajo_en = NULL
                               WHERE id = {$horaId} LIMIT 1");
            return (bool) $ok;
        }

        $notas = trim((string) $notas);
        $sqlNotas = $notas === '' ? 'NULL' : "'" . $db->escape_string(mb_substr($notas, 0, 255)) . "'";
        $ok = $db->query("UPDATE suplencia_horas
                             SET dejo_trabajo = " . ($dejo ? 1 : 0) . ",
                                 trabajo_notas = {$sqlNotas},
                                 trabajo_por = " . ($uid > 0 ? $uid : 'NULL') . ",
                                 trabajo_en = NOW()
                           WHERE id = {$horaId} LIMIT 1");
        return (bool) $ok;
    }

    /**
     * Reparto de "dejó trabajo" para el tablero.
     *
     * Las horas sin revisar (NULL) se cuentan aparte y NO entran en el porcentaje:
     * meterlas en el denominador convertiría "prefectura aún no ha mirado" en
     * "el profesor no dejó nada", que es acusar a alguien por un hueco de captura.
     *
     * @return array{revisadas:int, con:int, sin:int, pendientes:int, pct:int}
     */
    public static function estadisticaTrabajo(array $niveles = []): array {
        $n = Suplencia::sqlNivelHora($niveles, 'sh');
        $r = self::$db->query("
            SELECT SUM(sh.dejo_trabajo = 1) con,
                   SUM(sh.dejo_trabajo = 0) sin_t,
                   SUM(sh.dejo_trabajo IS NULL) pend
              FROM suplencia_horas sh
             WHERE 1=1{$n}");
        $row = $r ? $r->fetch_assoc() : null;
        $con  = (int) ($row['con']   ?? 0);
        $sinT = (int) ($row['sin_t'] ?? 0);
        $rev  = $con + $sinT;
        return [
            'revisadas'  => $rev,
            'con'        => $con,
            'sin'        => $sinT,
            'pendientes' => (int) ($row['pend'] ?? 0),
            'pct'        => $rev > 0 ? (int) round($con * 100 / $rev) : 0,
        ];
    }

    /**
     * Horas cuyo "¿dejó trabajo?" sigue sin revisar, para la cola de prefectura.
     *
     * Tres condiciones, y las tres importan:
     *
     * - `dejo_trabajo IS NULL` — el estado "sin revisar". `0` ya es una respuesta.
     * - `tipo <> 'guardia'` — en el patio no hay trabajo que dejar, así que una guardia
     *   no es una pregunta pendiente: sería ruido permanente en la cola.
     * - `sup.fecha <= CURDATE()` — antes de que la clase ocurra la pregunta no tiene
     *   respuesta posible, y una ausencia agendada con tres semanas de antelación
     *   inflaría la cola con trabajo que todavía no existe.
     *
     * Se ordena de la más antigua a la más reciente: lo que lleva más tiempo sin revisar
     * es lo que peor se recuerda, y es lo primero que hay que cerrar.
     *
     * Trae `sup.notas AS s_notas` —las indicaciones que el ausente escribió al avisar—
     * porque son justo el dato con el que se responde la pregunta de la cola, y sin
     * ellas había que abrir cada suplencia por separado para poder marcar con criterio.
     *
     * @param string[] $niveles Alcance de una dirección de nivel. [] = sin filtro.
     * @param int      $limite  0 = sin límite
     */
    public static function pendientesTrabajo(array $niveles = [], int $limite = 0): array {
        $n   = Suplencia::sqlNivelHora($niveles, 'sh');
        $lim = $limite > 0 ? ' LIMIT ' . (int)$limite : '';
        $sql = "
            SELECT sh.*,
                   sup.fecha AS s_fecha, sup.motivo AS s_motivo, sup.origen AS s_origen,
                   sup.notas AS s_notas,
                   p.etiqueta AS periodo_etiqueta, p.nivel AS periodo_nivel,
                   p.hora_inicio AS periodo_inicio, p.hora_fin AS periodo_fin,
                   g.nombre AS grupo_nombre, a.nombre AS aula_nombre,
                   m.nombre AS materia,
                   au.nombre AS ausente_nombre, au.avatar AS ausente_avatar,
                   s.nombre AS suplente_nombre
              FROM suplencia_horas sh
              JOIN suplencias sup ON sup.id = sh.suplencia_id
              LEFT JOIN periodos p ON p.id = sh.periodo_id
              LEFT JOIN grupos   g ON g.id = sh.grupo_id
              LEFT JOIN aulas    a ON a.id = sh.aula_id
              LEFT JOIN materias m ON m.id = sh.materia_id
              LEFT JOIN usuarios au ON au.id = sup.profesor_ausente_id
              LEFT JOIN usuarios s  ON s.id  = sh.suplente_id
             WHERE sh.dejo_trabajo IS NULL
               AND sh.tipo <> 'guardia'
               AND sup.fecha <= CURDATE(){$n}
             ORDER BY sup.fecha ASC, p.hora_inicio ASC{$lim}
        ";
        return static::consultarSQL($sql);
    }

    /** Cuántas horas esperan revisión. Alimenta el badge del subnav. */
    public static function contarPendientesTrabajo(array $niveles = []): int {
        $n = Suplencia::sqlNivelHora($niveles, 'sh');
        $r = self::$db->query("
            SELECT COUNT(*) c
              FROM suplencia_horas sh
              JOIN suplencias sup ON sup.id = sh.suplencia_id
             WHERE sh.dejo_trabajo IS NULL
               AND sh.tipo <> 'guardia'
               AND sup.fecha <= CURDATE(){$n}");
        return $r ? (int)($r->fetch_assoc()['c'] ?? 0) : 0;
    }

    /**
     * Ranking de profesores que más veces faltaron SIN dejar trabajo.
     * Se imputa al ausente (`sup.profesor_ausente_id`), no al suplente: el material
     * lo deja quien falta.
     *
     * @return array<int, array{id:int,nombre:string,avatar:?string,n:int}>
     */
    public static function topSinTrabajo(int $limite = 8, array $niveles = []): array {
        $out = [];
        $n = Suplencia::sqlNivelHora($niveles, 'sh');
        $r = self::$db->query(
            "SELECT u.id, u.nombre, u.avatar, COUNT(*) n
               FROM suplencia_horas sh
               JOIN suplencias sup ON sup.id = sh.suplencia_id
               JOIN usuarios u ON u.id = sup.profesor_ausente_id
              WHERE sh.dejo_trabajo = 0{$n}
           GROUP BY u.id, u.nombre, u.avatar
           ORDER BY n DESC, u.nombre ASC
              LIMIT " . (int) $limite);
        if ($r) while ($row = $r->fetch_assoc()) {
            $out[] = ['id' => (int)$row['id'], 'nombre' => $row['nombre'],
                      'avatar' => $row['avatar'], 'n' => (int)$row['n']];
        }
        return $out;
    }

    /**
     * Sugiere y clasifica candidatos para cubrir una hora concreta.
     *
     * La disponibilidad no se declara a mano: se deduce del horario. Y como la jornada
     * es POR NIVEL, "estar libre" NO se decide comparando periodo_id — la 3ª hora de
     * Primaria y la 3ª de Secundaria son periodos distintos que se pisan en el reloj.
     * Todo se resuelve por solapamiento de hora_inicio/hora_fin.
     *
     * Reglas: excluir al ausente / a quien ya faltó ese día / con clase solapada / con
     * otra suplencia solapada; conservar DESCANSO_MIN minutos libres; equidad
     * (MARGEN_EQUIDAD sobre el mínimo).
     * `puede_suplir = 0` ya lo filtra UsuarioBlog::candidatosSuplencia().
     *
     * ⚠️ La ocupación sale de Horario::ocupacionEfectivaDia() y NO de
     * ocupacionDiaDeVarios(): los intercambios validados cambian quién da clase ese día
     * concreto sin tocar `horarios`, así que leyendo solo lo permanente se daba por
     * libre a quien había aceptado cubrir la clase de otro.
     *
     * Cuesta 8 consultas fijas, no 8+N: la ocupación de todo el claustro se resuelve de
     * una vez, y los swaps del día también.
     *
     * @return array Lista ordenada con: id, nombre, avatar, elegible(bool), motivo,
     *               aviso, aviso_tipo, horas_libres, coberturas
     */
    public static function sugerir(string $fecha, int $periodoId, int $ausenteId = 0): array {
        $periodoId = (int)$periodoId;
        $ausenteId = (int)$ausenteId;
        $dow = (int)date('N', strtotime($fecha));
        $dia = self::DOW_DIA[$dow] ?? null;
        if ($dia === null) return []; // fin de semana: sin clases

        $hora = Periodo::find($periodoId);
        if (!$hora) return [];
        $hIni = $hora->hora_inicio;
        $hFin = $hora->hora_fin;
        $duracion = Periodo::minutos($hIni, $hFin);

        $fechaSafe = self::$db->escape_string($fecha);
        $db = self::$db;

        // Coberturas totales por suplente (equidad)
        $coberturas = [];
        $r = $db->query("SELECT suplente_id, COUNT(*) n FROM suplencia_horas WHERE suplente_id IS NOT NULL AND estado_hora IN ('agendada','validada') GROUP BY suplente_id");
        if ($r) while ($row = $r->fetch_assoc()) $coberturas[(int)$row['suplente_id']] = (int)$row['n'];

        // Suplencias ya asignadas ese día, CON SUS RANGOS: hacen falta tanto para
        // detectar el choque con la hora a cubrir como para descontar minutos ocupados.
        $asignadasDia = [];
        $r = $db->query("
            SELECT sh.suplente_id, p.hora_inicio, p.hora_fin
              FROM suplencia_horas sh
              JOIN suplencias s ON s.id = sh.suplencia_id
              JOIN periodos   p ON p.id = sh.periodo_id
             WHERE s.fecha = '{$fechaSafe}' AND sh.suplente_id IS NOT NULL");
        if ($r) while ($row = $r->fetch_assoc()) {
            $asignadasDia[(int)$row['suplente_id']][] = ['inicio' => $row['hora_inicio'], 'fin' => $row['hora_fin']];
        }

        /* Quien ya avisó de que falta ese día NO puede cubrir a nadie, y se bloquea el
           DÍA ENTERO: si alguien no viene el lunes, no viene a ninguna hora del lunes,
           aunque la hora a cubrir caiga fuera de las que él declaró ausentes. Antes solo
           se excluía al ausente de ESTA suplencia (el `$ausenteId` de abajo), así que un
           profesor con otra ausencia registrada el mismo día seguía saliendo sugerido.
           `cancelada` no cuenta: esa ausencia ya no existe y vuelve a estar disponible. */
        $ausentesDia = [];
        $r = $db->query("
            SELECT DISTINCT profesor_ausente_id
              FROM suplencias
             WHERE fecha = '{$fechaSafe}'
               AND estado <> 'cancelada'
               AND profesor_ausente_id IS NOT NULL");
        if ($r) while ($row = $r->fetch_assoc()) $ausentesDia[(int)$row['profesor_ausente_id']] = true;

        $candidatos = UsuarioBlog::candidatosSuplencia();
        // Efectiva y no permanente: los intercambios validados mueven clases ese día
        // concreto sin tocar `horarios`. Ver Horario::ocupacionEfectivaDia().
        $ocupacion  = Horario::ocupacionEfectivaDia(array_column($candidatos, 'id'), $dia, $fecha);

        $lista = [];
        foreach ($candidatos as $c) {
            $cid = (int)$c['id'];
            if ($cid === $ausenteId) continue;

            $clases  = $ocupacion[$cid] ?? [];
            $cubre   = $asignadasDia[$cid] ?? [];
            $cob     = $coberturas[$cid] ?? 0;

            // Ámbito del profesor: sus niveles DECLARADOS. Si no ha declarado ninguno se
            // deducen de sus clases de ese día, y si tampoco da clase, el de la hora a
            // cubrir (es la jornada a la que tendría que venir). Lo declarado manda para
            // no tener dos fuentes de verdad sobre en qué niveles está un profesor.
            $declarados = array_values(array_intersect(
                Materia::NIVELES,
                array_filter(array_map('trim', explode(',', (string)($c['niveles'] ?? ''))))
            ));
            $niveles = [];
            foreach ($declarados as $n) $niveles[$n] = true;
            if (!$niveles) foreach ($clases as $o) $niveles[$o['nivel']] = true;
            if (!$niveles) $niveles[$hora->nivel] = true;

            /* Afinidad con el nivel de la clase a cubrir. Decide la PRIORIDAD, no la
               elegibilidad: filtrar en duro dejaría a prefectura sin candidatos en los
               niveles pequeños, el mismo problema que resolvió MARGEN_EQUIDAD.
               Tres grados, porque "no sabemos" no es lo mismo que "sabemos que no":
                 2 = consta que imparte ese nivel
                 1 = sin datos (ni declarados ni clases ese día) → no se le penaliza
                 0 = consta que imparte otros niveles, no ese                       */
            $evidencia = $declarados ?: array_column($clases, 'nivel');
            $afinidad  = !$evidencia ? 1 : (in_array($hora->nivel, $evidencia, true) ? 2 : 0);

            $jornada = self::minutosJornada(array_keys($niveles));
            $ocupado = self::minutosOcupados($clases) + self::minutosOcupados($cubre);
            $libres  = max(0, $jornada - $ocupado);

            $motivo = null;
            // Va PRIMERO: es la explicación más útil de las que concurren. A quien falta
            // ese día le sobra con saber eso; que además tenga clase a esa hora (la que
            // precisamente no va a dar) no le dice nada a quien reparte.
            if (isset($ausentesDia[$cid])) {
                $motivo = 'Tiene una ausencia registrada ese día';
            } elseif (self::chocaCon($clases, $hIni, $hFin)) {
                // Una guardia de receso ocupa igual que una clase, pero decir "tiene
                // clase" cuando está vigilando el patio confunde a quien reparte.
                $enGuardia = false;
                foreach ($clases as $o) {
                    if (($o['tipo'] ?? 'clase') === 'guardia'
                        && Periodo::solapan($hIni, $hFin, $o['inicio'], $o['fin'])) { $enGuardia = true; break; }
                }
                $motivo = $enGuardia ? 'Tiene guardia a esa hora' : 'Tiene clase a esa hora';
            } elseif (self::chocaCon($cubre, $hIni, $hFin)) {
                $motivo = 'Ya cubre otra suplencia a esa hora';
            } elseif ($libres <= self::DESCANSO_MIN) {
                $motivo = 'Casi no tiene horas libres ese día';
            } elseif (($libres - $duracion) < self::DESCANSO_MIN) {
                $motivo = 'Debe conservar una hora de descanso';
            }

            /* Salvedades que NO bloquean: prefectura decide con ellas a la vista. Pueden
               concurrir varias, por eso es una lista y no un campo suelto.

               El receso propio ya NO genera aviso. Saltaba en cuanto la hora a cubrir
               caía en el receso de alguno de sus niveles, que con jornada por nivel es
               lo habitual —el receso de uno es hora de clase de otro—, así que aparecía
               en casi todos los candidatos y dejó de significar nada. El dato sigue a la
               vista donde importa: la rejilla de previsualización pinta ese tramo en
               ámbar dentro del horario real del candidato. */
            $avisos = [];
            // Solo se avisa cuando CONSTA que imparte otros niveles (afinidad 0). Si no
            // hay datos no se le cuelga un aviso que no podemos sostener.
            if ($motivo === null && $afinidad === 0) {
                $avisos[] = ['tipo' => 'nivel', 'texto' => 'No imparte en ' . $hora->nivel];
            }

            $lista[] = [
                'id'            => $cid,
                'nombre'        => $c['nombre'],
                'avatar'        => $c['avatar'] ?? '',
                'elegible'      => $motivo === null,
                'motivo'        => $motivo,
                'avisos'        => $avisos,
                'afinidad'      => $afinidad,
                'niveles'       => $declarados,
                // Dato de presentación, no decide nada: bloques libres de su nivel
                // dominante. Los minutos son lo único comparable entre jornadas.
                'horas_libres'  => self::bloquesLibres($clases, $cubre, $niveles),
                'coberturas'    => $cob,
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

        /* Orden: elegibles primero; dentro de ellos, por afinidad de nivel (imparte ese
           nivel > sin datos > imparte otros); luego los que no arrastran otras
           salvedades; luego menos coberturas (el más justo arriba); luego más horas
           libres; y a igualdad, por nombre.
           La afinidad va por delante de la equidad a propósito: para cubrir una clase de
           Primaria, un profesor de Primaria pasa antes aunque acumule alguna cobertura
           más. */
        usort($lista, function ($a, $b) {
            if ($a['elegible'] !== $b['elegible']) return $a['elegible'] ? -1 : 1;
            if ($a['afinidad'] !== $b['afinidad']) return $b['afinidad'] <=> $a['afinidad'];
            $avA = count($a['avisos']); $avB = count($b['avisos']);
            if ($avA !== $avB) return $avA <=> $avB;
            if ($a['coberturas'] !== $b['coberturas']) return $a['coberturas'] <=> $b['coberturas'];
            if ($a['horas_libres'] !== $b['horas_libres']) return $b['horas_libres'] <=> $a['horas_libres'];
            return strcmp($a['nombre'], $b['nombre']);
        });

        return $lista;
    }

    // ── Apoyo de sugerir() ────────────────────────────────────────────────────

    /** ¿Alguno de esos rangos se pisa con [$ini, $fin)? */
    private static function chocaCon(array $rangos, string $ini, string $fin): bool {
        foreach ($rangos as $r) if (Periodo::solapan($ini, $fin, $r['inicio'], $r['fin'])) return true;
        return false;
    }

    /** Minutos cubiertos por una lista de rangos, sin contar dos veces los solapes. */
    private static function minutosOcupados(array $rangos): int {
        if (!$rangos) return 0;
        usort($rangos, fn($a, $b) => $a['inicio'] <=> $b['inicio']);
        $total = 0; $ini = null; $fin = null;
        foreach ($rangos as $r) {
            if ($ini === null)            { $ini = $r['inicio']; $fin = $r['fin']; continue; }
            if ($r['inicio'] < $fin)      { $fin = max($fin, $r['fin']); continue; }
            $total += Periodo::minutos($ini, $fin);
            $ini = $r['inicio']; $fin = $r['fin'];
        }
        return $total + Periodo::minutos($ini, $fin);
    }

    /**
     * Minutos de clase de la UNIÓN de las jornadas de esos niveles. Unión y no suma:
     * dos niveles se solapan en el reloj y contarlos dos veces inflaría el descanso.
     */
    private static function minutosJornada(array $niveles): int {
        $rangos = [];
        foreach (Periodo::deNiveles($niveles, true) as $p) {
            $rangos[] = ['inicio' => $p->hora_inicio, 'fin' => $p->hora_fin];
        }
        return self::minutosOcupados($rangos);
    }

    /**
     * Bloques libres en la jornada del nivel dominante del profesor. Es el número que
     * daría un humano ("le quedan 3 horas libres"); no participa en ninguna decisión,
     * porque un bloque de Maternal y uno de Bachillerato no duran lo mismo.
     */
    private static function bloquesLibres(array $clases, array $cubre, array $niveles): int {
        $dominante = null; $max = -1;
        $conteo = [];
        foreach ($clases as $o) $conteo[$o['nivel']] = ($conteo[$o['nivel']] ?? 0) + 1;
        foreach (array_keys($niveles) as $niv) {
            $n = $conteo[$niv] ?? 0;
            if ($n > $max) { $max = $n; $dominante = $niv; }
        }
        // porNivel() está cacheado: Periodo::clases($nivel) aquí sería una consulta
        // por candidato, justo el N+1 que esta reescritura vino a quitar.
        $ocupados = array_merge($clases, $cubre);
        $libres = 0;
        foreach (Periodo::porNivel(true)[$dominante] ?? [] as $p) {
            if (!self::chocaCon($ocupados, $p->hora_inicio, $p->hora_fin)) $libres++;
        }
        return $libres;
    }
}
