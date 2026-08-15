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

    // Ciclo de vida del justificante (se escriben por SQL, no por el ORM base)
    public $justificante_subido_en;
    public $justificante_resuelto_en;
    public $justificante_resuelto_por;
    public $justificante_resolucion;

    // Aliases traídos por JOIN / agregados
    public $ausente_nombre;
    public $ausente_avatar;
    public $creador_nombre;
    public $resolutor_nombre;
    public $total_horas;
    public $horas_validadas;
    public $horas_agendadas;
    public $dias_desde;

    public const ESTADOS = ['solicitada', 'agendada', 'en_curso', 'por_justificar', 'completada', 'cancelada'];

    /**
     * Tamaño máximo del justificante, en MB. Fuente única: lo lee el guard del
     * servidor (BlogController::subirJustificante) y también las vistas, que lo
     * emiten como `data-file-max` para admin-file.js.
     *
     * ⚠️ Superar los defaults de PHP (upload_max_filesize=2M, post_max_size=8M) o
     * el maxAllowedContentLength de IIS (30 MB) hace que el archivo llegue vacío.
     * Ver la nota de despliegue en CLAUDE.md.
     */
    public const MAX_JUSTIFICANTE_MB = 50;

    /**
     * ── Retención de justificantes ──
     *
     * Un justificante suele ser un parte médico y vive en una carpeta pública, así
     * que no se conserva indefinidamente. La cuenta arranca en `fecha` (el día de la
     * ausencia), no en la subida: lo que caduca es la necesidad de revisarlo.
     *
     *   0 – 7 días   descargable con normalidad
     *   8 – 29 días  pasa a la COLA: un directivo decide descargar o eliminar
     *   ≥ 30 días    se purga solo (purgarJustificantes(), disparado por el panel)
     */
    public const DIAS_DESCARGA = 7;
    public const DIAS_PURGA    = 30;

    public const JUSTIF_LABEL = [
        'sin_archivo' => 'Sin justificante',
        'vigente'     => 'Disponible',
        'en_cola'     => 'Requiere decisión',
        'resuelto'    => 'Resuelto',
    ];

    /** Días transcurridos desde la ausencia. Negativo si aún no ha ocurrido. */
    public function diasDesdeAusencia(): int {
        if (empty($this->fecha)) return 0;
        $hoy = new \DateTime('today');
        $f   = \DateTime::createFromFormat('!Y-m-d', $this->fecha);
        if (!$f) return 0;
        return (int)$f->diff($hoy)->format('%r%a');
    }

    /**
     * Estado del justificante: `sin_archivo` · `vigente` · `en_cola` · `resuelto`.
     * Se deriva de la fecha en vez de guardarse para que no haya un estado que
     * envejezca mal si nadie entra al panel durante una semana.
     */
    public function estadoJustificante(): string {
        if (!empty($this->justificante_resuelto_en)) return 'resuelto';
        if (empty($this->justificante))              return 'sin_archivo';
        return $this->diasDesdeAusencia() > self::DIAS_DESCARGA ? 'en_cola' : 'vigente';
    }

    /** Días que faltan para que el justificante entre en la cola. 0 si ya entró. */
    public function diasParaCola(): int {
        return max(0, self::DIAS_DESCARGA - $this->diasDesdeAusencia());
    }

    /** Días que faltan para el borrado automático. 0 si ya toca. */
    public function diasParaPurga(): int {
        return max(0, self::DIAS_PURGA - $this->diasDesdeAusencia());
    }

    /**
     * Justificantes que esperan una decisión: hay archivo, la ausencia fue hace más
     * de DIAS_DESCARGA días y nadie lo ha resuelto todavía. Los más antiguos primero
     * — son los que están a punto de purgarse.
     */
    public static function colaJustificantes(array $niveles = []): array {
        $d = (int)self::DIAS_DESCARGA;
        $n = self::sqlNivel($niveles, 'sup', true);
        return static::consultarSQL(
            self::selectBase() .
            " WHERE sup.justificante IS NOT NULL
                AND sup.justificante_resuelto_en IS NULL
                AND sup.fecha < (CURDATE() - INTERVAL {$d} DAY){$n}
              GROUP BY sup.id
              ORDER BY sup.fecha ASC");
    }

    /**
     * Cuántos esperan decisión. Alimenta el badge del subnav, y por eso acepta el
     * mismo alcance que colaJustificantes(): un badge que dice 7 sobre una lista de 2
     * es peor que no tener badge.
     */
    public static function contarColaJustificantes(array $niveles = []): int {
        $d = (int)self::DIAS_DESCARGA;
        $n = self::sqlNivel($niveles, 'sup', true);
        $r = self::$db->query(
            "SELECT COUNT(*) n FROM suplencias sup
              WHERE sup.justificante IS NOT NULL AND sup.justificante_resuelto_en IS NULL
                AND sup.fecha < (CURDATE() - INTERVAL {$d} DAY){$n}");
        return $r ? (int)$r->fetch_assoc()['n'] : 0;
    }

    /**
     * Marca el justificante como resuelto y devuelve la ruta del archivo para que el
     * llamador lo borre del disco (el modelo no toca el sistema de ficheros).
     *
     * @param string $resolucion descargado | eliminado | purgado
     * @return string|null ruta pública del archivo, o null si ya no había
     */
    public static function resolverJustificante(int $id, string $resolucion, int $usuarioId = 0): ?string {
        if (!\in_array($resolucion, ['descargado', 'eliminado', 'purgado'], true)) return null;
        $s = self::find($id);
        if (!$s || empty($s->justificante)) return null;
        $ruta = $s->justificante;

        $por = $usuarioId > 0 ? (int)$usuarioId : 'NULL';
        self::$db->query(
            "UPDATE suplencias
                SET justificante = NULL,
                    justificante_resuelto_en  = NOW(),
                    justificante_resuelto_por = {$por},
                    justificante_resolucion   = '" . self::$db->escape_string($resolucion) . "'
              WHERE id = " . (int)$id . " LIMIT 1");
        self::recalcularEstado((int)$id);
        return $ruta;
    }

    /**
     * Justificantes que ya superaron DIAS_PURGA y siguen en disco. Los devuelve para
     * que el llamador borre los archivos; el marcado en BD lo hace él mismo.
     *
     * No hay cron en este proyecto: lo dispara la carga del panel, igual que
     * BlogController::recordarCoberturasVencidas().
     *
     * @return array<int, array{id:int, ruta:string}>
     */
    public static function purgables(): array {
        $d = (int)self::DIAS_PURGA;
        $out = [];
        $r = self::$db->query(
            "SELECT id, justificante FROM suplencias
              WHERE justificante IS NOT NULL
                AND fecha < (CURDATE() - INTERVAL {$d} DAY)");
        if ($r) while ($row = $r->fetch_assoc()) {
            $out[] = ['id' => (int)$row['id'], 'ruta' => $row['justificante']];
        }
        return $out;
    }

    /** Sella la subida del justificante. Se llama al guardar el archivo. */
    public static function marcarSubida(int $id): void {
        self::$db->query(
            "UPDATE suplencias
                SET justificante_subido_en = NOW(),
                    justificante_resuelto_en = NULL,
                    justificante_resuelto_por = NULL,
                    justificante_resolucion = NULL
              WHERE id = " . (int)$id . " LIMIT 1");
    }
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

    // ── Alcance por nivel educativo ─────────────────────────────────────────────
    // Las direcciones por nivel solo ven lo suyo. El nivel NO es columna de
    // `suplencias`: cuelga de sus horas (suplencia_horas.periodo_id → periodos.nivel).
    //
    // ⚠️ `periodos.nivel` es el ÚNICO camino fiable. `suplencia_horas.grupo_id` y
    // `.materia_id` van NULL en las guardias de receso, así que filtrar por
    // `grupos.nivel` o `materias.nivel` perdería las guardias en silencio — y una
    // guardia se suple igual que una clase. `periodo_id` es NOT NULL con FK CASCADE.
    //
    // ⚠️ Hay DOS granos y confundirlos infla los conteos. Por eso son dos helpers y
    // no uno con un flag:
    //   · grano SUPLENCIA ("cuántas ausencias hubo en Primaria") → sqlNivel(), EXISTS
    //   · grano HORA      ("cuántas coberturas se hicieron ahí") → sqlNivelHora(), IN

    /**
     * Acota una consulta de grano SUPLENCIA. Devuelve '' o un fragmento que EMPIEZA
     * por ' AND ', así que el llamador escribe siempre `WHERE 1=1` y se ahorra el
     * condicional.
     *
     * Se resuelve con EXISTS y NO con un JOIN porque selectBase() y varios agregados
     * YA unen `suplencia_horas`: un segundo JOIN multiplicaría filas y el
     * `COUNT(sh.id) AS total_horas` contaría de más. El EXISTS no aporta ni una fila.
     *
     * @param string[] $niveles         [] o los cinco = sin filtro
     * @param string   $alias           alias de `suplencias` en la consulta
     * @param bool     $incluirSinNivel true deja pasar la suplencia que todavía no
     *        tiene horas. En la AGENDA y la COLA hay que ponerlo: una ausencia recién
     *        abierta no tiene nivel y se volvería invisible justo para quien debe
     *        agendarla. En las ESTADÍSTICAS va a false — sin horas no aporta a nadie.
     */
    public static function sqlNivel(array $niveles, string $alias = 'sup', bool $incluirSinNivel = false): string {
        $ok = array_values(array_intersect(Materia::NIVELES, $niveles));
        if (!$ok || count($ok) === count(Materia::NIVELES)) return '';
        $in = implode(',', array_map(fn($n) => "'" . self::$db->escape_string($n) . "'", $ok));

        $p = "EXISTS (SELECT 1 FROM suplencia_horas shn
                        JOIN periodos pn ON pn.id = shn.periodo_id
                       WHERE shn.suplencia_id = {$alias}.id AND pn.nivel IN ({$in}))";
        if ($incluirSinNivel) {
            $p = "({$p} OR NOT EXISTS (SELECT 1 FROM suplencia_horas shx
                                        WHERE shx.suplencia_id = {$alias}.id))";
        }
        return " AND {$p}";
    }

    /**
     * Acota una consulta de grano HORA (base `suplencia_horas`). Aquí la fila ya ES
     * una hora con su periodo, así que basta un IN sobre `periodo_id`: cero JOINs
     * nuevos y cero riesgo de multiplicar filas.
     *
     * Vive aquí y no en SuplenciaHora para que el vocabulario del filtro esté en un
     * solo archivo; SuplenciaHora lo llama como Suplencia::sqlNivelHora($n, 'sh').
     */
    public static function sqlNivelHora(array $niveles, string $alias = 'sh'): string {
        $ok = array_values(array_intersect(Materia::NIVELES, $niveles));
        if (!$ok || count($ok) === count(Materia::NIVELES)) return '';
        $in = implode(',', array_map(fn($n) => "'" . self::$db->escape_string($n) . "'", $ok));
        return " AND {$alias}.periodo_id IN (SELECT id FROM periodos WHERE nivel IN ({$in}))";
    }

    /** Niveles que toca una suplencia, por sus horas. [] si todavía no tiene ninguna. */
    public static function nivelesDeSuplencia(int $id): array {
        $out = [];
        $r = self::$db->query(
            "SELECT DISTINCT p.nivel
               FROM suplencia_horas sh JOIN periodos p ON p.id = sh.periodo_id
              WHERE sh.suplencia_id = " . (int) $id);
        if ($r) while ($row = $r->fetch_assoc()) $out[] = $row['nivel'];
        return array_values(array_intersect(Materia::NIVELES, $out));
    }

    private static function selectBase(): string {
        return "
            SELECT sup.*,
                   a.nombre AS ausente_nombre, a.avatar AS ausente_avatar,
                   c.nombre AS creador_nombre,
                   j.nombre AS resolutor_nombre,
                   DATEDIFF(CURDATE(), sup.fecha) AS dias_desde,
                   COUNT(sh.id) AS total_horas,
                   SUM(sh.estado_hora = 'validada') AS horas_validadas,
                   SUM(sh.estado_hora = 'agendada') AS horas_agendadas
            FROM suplencias sup
            LEFT JOIN usuarios a ON a.id = sup.profesor_ausente_id
            LEFT JOIN usuarios c ON c.id = sup.creado_por
            LEFT JOIN usuarios j ON j.id = sup.justificante_resuelto_por
            LEFT JOIN suplencia_horas sh ON sh.suplencia_id = sup.id
        ";
    }

    /** Listado con filtros: q, estado, origen, desde, hasta, ausente_id, suplente_id, niveles. */
    public static function listar(array $f = []): array {
        $where = [];
        // Alcance por nivel de las direcciones. Se deja pasar la ausencia todavía sin
        // horas: es exactamente la que hay que agendar, y esconderla sería el peor
        // momento para hacerlo.
        if (!empty($f['niveles'])) {
            $n = self::sqlNivel((array) $f['niveles'], 'sup', true);
            if ($n !== '') $where[] = substr($n, 5);   // sqlNivel() antepone ' AND '
        }
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
        // `mias`: OR entre ausente y suplente. Es lo que ve un profesor raso —
        // sus propias ausencias y las coberturas que hizo—, no un AND de los dos
        // filtros anteriores, que nunca devolvería nada.
        if (!empty($f['mias'])) {
            $mid = (int)$f['mias'];
            $where[] = "(sup.profesor_ausente_id = {$mid}"
                     . " OR sup.id IN (SELECT suplencia_id FROM suplencia_horas WHERE suplente_id = {$mid}))";
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

    /**
     * Conteos por estado para las tarjetas de encabezado (+ total).
     * Con `$usuarioId` se ciñe a las suplencias de esa persona (como ausente o
     * como suplente): a un profesor no le sirve —ni le corresponde— el total del
     * claustro sobre unas tarjetas que encabezan una lista ya filtrada.
     */
    public static function conteos(int $usuarioId = 0, array $niveles = []): array {
        $out = ['total' => 0];
        foreach (self::ESTADOS as $e) $out[$e] = 0;
        foreach (self::porEstado($usuarioId, $niveles) as $estado => $n) {
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

    // ── Agregados para el dashboard (admin) ─────────────────────────────────────
    /**
     * Conteo por estado. `conteos()` es lo mismo más el total, así que se deriva de aquí
     * en lugar de repetir el GROUP BY en dos consultas.
     */
    public static function porEstado(int $usuarioId = 0, array $niveles = []): array {
        $out = [];
        $where = '';
        if ($usuarioId > 0) {
            $uid = (int)$usuarioId;
            // ⚠️ Los paréntesis del OR son obligatorios. Sin ellos, el ` AND …` que
            // añade sqlNivel() se asociaría solo al segundo término (AND liga más
            // fuerte que OR) y las ausencias propias colarían niveles ajenos.
            $where = " AND (sup.profesor_ausente_id = {$uid}"
                   . " OR sup.id IN (SELECT suplencia_id FROM suplencia_horas WHERE suplente_id = {$uid}))";
        }
        $where .= self::sqlNivel($niveles, 'sup');
        $r = self::$db->query("SELECT sup.estado, COUNT(*) n FROM suplencias sup WHERE 1=1{$where} GROUP BY sup.estado");
        if ($r) while ($row = $r->fetch_assoc()) $out[$row['estado']] = (int)$row['n'];
        return $out;
    }
    public static function porOrigen(array $niveles = []): array {
        $out = ['anticipada' => 0, 'sin_aviso' => 0];
        $n = self::sqlNivel($niveles, 'sup');
        $r = self::$db->query("SELECT sup.origen, COUNT(*) n FROM suplencias sup WHERE 1=1{$n} GROUP BY sup.origen");
        if ($r) while ($row = $r->fetch_assoc()) $out[$row['origen']] = (int)$row['n'];
        return $out;
    }
    /** Ranking de quién más ha suplido (horas agendadas/validadas). */
    public static function topSuplentes(int $limite = 8, array $niveles = []): array {
        $limite = max(1, min(20, $limite));
        // Grano hora: "quién más ha suplido EN Primaria", no "quién de Primaria".
        $n = self::sqlNivelHora($niveles, 'sh');
        $r = self::$db->query("
            SELECT u.id, u.nombre, u.avatar,
                   COUNT(*) AS coberturas,
                   SUM(sh.estado_hora='validada') AS validadas
            FROM suplencia_horas sh
            JOIN usuarios u ON u.id = sh.suplente_id
            WHERE sh.suplente_id IS NOT NULL AND sh.estado_hora IN ('agendada','validada'){$n}
            GROUP BY u.id
            ORDER BY coberturas DESC, u.nombre ASC
            LIMIT {$limite}
        ");
        $out = [];
        if ($r) while ($row = $r->fetch_assoc()) $out[] = $row;
        return $out;
    }
    /** Ranking de quién más ha faltado. */
    public static function topAusentes(int $limite = 8, array $niveles = []): array {
        $limite = max(1, min(20, $limite));
        $n = self::sqlNivel($niveles, 's');   // ojo: aquí la tabla se llama `s`
        $r = self::$db->query("
            SELECT u.id, u.nombre, u.avatar, COUNT(*) AS faltas
            FROM suplencias s
            JOIN usuarios u ON u.id = s.profesor_ausente_id
            WHERE s.profesor_ausente_id IS NOT NULL{$n}
            GROUP BY u.id
            ORDER BY faltas DESC, u.nombre ASC
            LIMIT {$limite}
        ");
        $out = [];
        if ($r) while ($row = $r->fetch_assoc()) $out[] = $row;
        return $out;
    }
    /** Suplencias por mes (últimos 12) para la gráfica de tendencia. */
    public static function porMes(array $niveles = []): array {
        $n = self::sqlNivel($niveles, 'sup');
        $r = self::$db->query("
            SELECT DATE_FORMAT(sup.fecha, '%Y-%m') AS mes, COUNT(*) n
            FROM suplencias sup
            WHERE 1=1{$n}
            GROUP BY mes ORDER BY mes ASC
        ");
        $out = [];
        if ($r) while ($row = $r->fetch_assoc()) $out[] = ['mes' => $row['mes'], 'n' => (int)$row['n']];
        return $out;
    }
    /** Materias con más suplencias (desde suplencia_horas). */
    public static function porMateria(int $limite = 8, array $niveles = []): array {
        $limite = max(1, min(20, $limite));
        // ⚠️ El LEFT JOIN a `materias` invita a filtrar por `m.nivel`, y sería un
        // error: `materia_id` va NULL en las guardias y se perderían en silencio.
        // Siempre por sh.periodo_id.
        $n = self::sqlNivelHora($niveles, 'sh');
        $r = self::$db->query("
            SELECT COALESCE(m.nombre,'Sin materia') AS materia, COUNT(*) n
            FROM suplencia_horas sh
            LEFT JOIN materias m ON m.id = sh.materia_id
            WHERE 1=1{$n}
            GROUP BY materia ORDER BY n DESC LIMIT {$limite}
        ");
        $out = [];
        if ($r) while ($row = $r->fetch_assoc()) $out[] = ['materia' => $row['materia'], 'n' => (int)$row['n']];
        return $out;
    }
    /** Motivos más frecuentes (el formulario los toma de self::MOTIVOS). */
    public static function porMotivo(int $limite = 8, array $niveles = []): array {
        $limite = max(1, min(20, $limite));
        $n = self::sqlNivel($niveles, 'sup');
        $r = self::$db->query("
            SELECT COALESCE(NULLIF(TRIM(sup.motivo), ''), 'Sin especificar') AS motivo, COUNT(*) n
            FROM suplencias sup
            WHERE 1=1{$n}
            GROUP BY motivo ORDER BY n DESC, motivo ASC LIMIT {$limite}
        ");
        $out = [];
        if ($r) while ($row = $r->fetch_assoc()) $out[] = ['motivo' => $row['motivo'], 'n' => (int)$row['n']];
        return $out;
    }

    /** Resumen diario (conteo por fecha) para el calendario del dashboard. */
    /**
     * Conteo de suplencias por día, para los calendarios.
     *
     * @param int $usuarioId 0 = todas (prefectura/admin). Con un id, solo las de esa
     *        persona —como ausente o como suplente—, igual que el filtro `mias` de
     *        listar(). Sin esto, el calendario del listado delataría a un profesor
     *        cuántas ausencias tiene el resto del claustro, que es justo lo que el
     *        listado evita.
     */
    public static function resumenDiario(int $usuarioId = 0, array $niveles = []): array {
        $where = '';
        if ($usuarioId > 0) {
            $uid = (int)$usuarioId;
            // Mismos paréntesis obligatorios que en porEstado(): ver la nota de allí.
            $where = " AND (sup.profesor_ausente_id = $uid
                       OR EXISTS (SELECT 1 FROM suplencia_horas sh
                                  WHERE sh.suplencia_id = sup.id AND sh.suplente_id = $uid))";
        }
        // El calendario acompaña a un listado que sí muestra la ausencia sin horas:
        // si el punto del día desapareciera, la tabla y el calendario se contradirían.
        $where .= self::sqlNivel($niveles, 'sup', true);
        $r = self::$db->query("SELECT sup.fecha, COUNT(*) n FROM suplencias sup WHERE 1=1{$where} GROUP BY sup.fecha");
        $out = [];
        if ($r) while ($row = $r->fetch_assoc()) $out[] = ['fecha' => $row['fecha'], 'n' => (int)$row['n']];
        return $out;
    }

    /**
     * Detalle por día para el calendario interactivo del tablero: al pulsar una fecha
     * se listan las suplencias de ese día. Se limita a los últimos 18 meses para que
     * la isla JSON no crezca sin control.
     */
    public static function detalleDiario(array $niveles = []): array {
        // ⚠️ Con el MISMO array que resumenDiario(), o el calendario pintaría un
        // punto en un día cuyo detalle sale vacío al pulsarlo.
        $n = self::sqlNivel($niveles, 'sup', true);
        $r = self::$db->query("
            SELECT sup.fecha, sup.estado, sup.origen, sup.motivo,
                   a.nombre AS ausente,
                   COUNT(sh.id) AS horas,
                   SUM(sh.suplente_id IS NOT NULL) AS cubiertas
            FROM suplencias sup
            LEFT JOIN usuarios a ON a.id = sup.profesor_ausente_id
            LEFT JOIN suplencia_horas sh ON sh.suplencia_id = sup.id
            WHERE sup.fecha >= DATE_SUB(CURDATE(), INTERVAL 18 MONTH){$n}
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
