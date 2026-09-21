<?php
namespace Model;

class Horario extends ActiveRecord {
    protected static $tabla      = 'horarios';
    protected static $columnasDB = ['id', 'dia', 'periodo_id', 'profesor_id', 'tipo', 'grupo_id',
                                    'aula_id', 'materia_id', 'lugar_id', 'rol_docente', 'division', 'color'];

    public $id;
    public $dia;
    public $periodo_id;
    public $profesor_id;
    /** 'clase' | 'guardia'. Una guardia de receso ocupa igual que una clase. */
    public $tipo;
    public $grupo_id;
    public $aula_id;
    public $materia_id;
    /** Lugar de la guardia. Solo en tipo='guardia'. */
    public $lugar_id;
    /** 'titular' | 'acompanante'. Ambos tienen la hora ocupada. */
    public $rol_docente;
    /** 0 = clase para todo el grupo; 1..n = opción de una materia dividida. */
    public $division;
    /** Color elegido a mano en el editor. NULL = automático por nombre de materia. */
    public $color;

    // Aliases traídos por JOIN
    public $profesor_nombre;
    public $grupo_nombre;
    public $grupo_nivel;
    public $aula_nombre;
    public $materia;        // alias de materias.nombre
    public $materia_nivel;
    public $lugar_nombre;
    public $periodo_etiqueta;
    public $periodo_orden;
    public $periodo_inicio;  // periodos.hora_inicio — lo que decide si dos clases chocan
    public $periodo_fin;
    public $periodo_nivel;
    public $periodo_receso;

    public const DIAS = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes'];
    public const DIAS_LABEL = ['lunes' => 'Lunes', 'martes' => 'Martes', 'miercoles' => 'Miércoles', 'jueves' => 'Jueves', 'viernes' => 'Viernes'];
    public const ROLES = ['titular', 'acompanante'];
    public const ROL_LABEL = ['titular' => 'Titular', 'acompanante' => 'Acompañante'];

    /** Persistencia con NULL real para grupo_id/aula_id/materia_id/lugar_id/color. */
    public function guardar() {
        $db   = self::$db;
        $cols = ['dia', 'periodo_id', 'profesor_id', 'tipo', 'grupo_id', 'aula_id',
                 'materia_id', 'lugar_id', 'rol_docente', 'division', 'color'];
        $nulos = ['grupo_id', 'aula_id', 'materia_id', 'lugar_id'];
        $sql  = [];
        foreach ($cols as $c) {
            $v = $this->$c;
            if ($c === 'tipo')        $v = $v === 'guardia' ? 'guardia' : 'clase';
            if ($c === 'rol_docente') $v = $v === 'acompanante' ? 'acompanante' : 'titular';
            if ($c === 'division')    $v = (int)$v;
            if ($v === null || $v === '' || (in_array($c, $nulos, true) && (int)$v === 0)) {
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
                   lg.nombre AS lugar_nombre,
                   p.etiqueta AS periodo_etiqueta, p.orden AS periodo_orden,
                   p.hora_inicio AS periodo_inicio, p.hora_fin AS periodo_fin,
                   p.nivel AS periodo_nivel, p.es_receso AS periodo_receso
            FROM horarios h
            LEFT JOIN usuarios u ON u.id = h.profesor_id
            LEFT JOIN grupos   g ON g.id = h.grupo_id
            LEFT JOIN aulas    a ON a.id = h.aula_id
            LEFT JOIN materias m ON m.id = h.materia_id
            LEFT JOIN periodos p ON p.id = h.periodo_id
            LEFT JOIN lugares_guardia lg ON lg.id = h.lugar_id
        ";
    }

    // Se ordena por reloj, no por `orden`: un mismo `orden` significa horas distintas
    // en cada nivel, así que ordenar por él mezclaría las jornadas.
    private const ORDEN = " ORDER BY p.hora_inicio ASC, p.orden ASC";

    public static function porProfesor(int $id): array {
        $id = (int)$id;
        return static::consultarSQL(self::selectBase() . " WHERE h.profesor_id = {$id}" . self::ORDEN);
    }
    public static function porAula(int $id): array {
        $id = (int)$id;
        return static::consultarSQL(self::selectBase() . " WHERE h.aula_id = {$id}" . self::ORDEN);
    }
    public static function porGrupo(int $id): array {
        $id = (int)$id;
        return static::consultarSQL(self::selectBase() . " WHERE h.grupo_id = {$id}" . self::ORDEN);
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

    /**
     * Vacía la rejilla entera.
     *
     * El CSV del colegio es el horario COMPLETO del plantel, no el de unos cuantos
     * profesores, así que importarlo es un reemplazo total: quien no venga en el
     * archivo se queda sin clases. Borrar solo a los del archivo dejaba vivas las
     * filas de un profesor que ya no imparte —invisibles en su propio listado, pero
     * ocupándole la hora frente a `sugerir()`— y ningún camino de la UI las
     * alcanzaba para quitarlas.
     *
     * ⚠️ `suplencia_horas` y `swap_clases` NO apuntan a `horarios.id`, así que este
     * borrado no arrastra historia: las suplencias guardan su propio
     * (periodo, grupo, aula, materia) y sobreviven al reemplazo.
     */
    public static function borrarTodo(): int {
        self::$db->query("DELETE FROM horarios");
        return self::$db->affected_rows;
    }

    /**
     * Colores elegidos a mano, indexados por casilla: `"profesorId|dia|periodoId" => hex`.
     *
     * Lo usa el importador CSV antes de borrar. El archivo no trae ninguna columna de
     * color, así que sin esta foto una reimportación revertía en silencio cada color
     * puesto desde el editor. Solo devuelve los no nulos: lo demás ya es automático y
     * no hay nada que conservar.
     *
     * Sin `$ids` devuelve los de TODA la tabla, que es lo que necesita el reemplazo
     * completo: los ids de profesor aún no se conocen cuando el archivo los crea.
     */
    public static function coloresDeProfesores(array $ids = []): array {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        $filtro = $ids ? " AND profesor_id IN (" . implode(',', $ids) . ")" : '';
        $r = self::$db->query(
            "SELECT profesor_id, dia, periodo_id, color FROM horarios
              WHERE color IS NOT NULL{$filtro}"
        );
        $out = [];
        if ($r) while ($f = $r->fetch_assoc()) {
            $out[$f['profesor_id'] . '|' . $f['dia'] . '|' . $f['periodo_id']] = $f['color'];
        }
        return $out;
    }

    /** ids de profesor que hoy tienen al menos una clase. Lo usa la previa del CSV. */
    public static function profesoresConHorario(): array {
        $r = self::$db->query("SELECT DISTINCT profesor_id FROM horarios");
        $out = [];
        if ($r) while ($f = $r->fetch_assoc()) $out[] = (int)$f['profesor_id'];
        return $out;
    }

    /** Niveles distintos en los que imparte esta lista de clases. */
    public static function nivelesDe(array $filas): array {
        $n = [];
        foreach ($filas as $h) if ($h->periodo_nivel) $n[$h->periodo_nivel] = true;
        return array_values(array_intersect(Materia::NIVELES, array_keys($n)));
    }

    // ── Disponibilidad ────────────────────────────────────────────────────────
    // Un profesor puede dar clase en varios niveles y las jornadas se desfasan entre
    // sí, así que "está libre" NO se puede decidir comparando periodo_id: hay que
    // mirar el reloj.

    /**
     * Rangos de reloj que ocupa un profesor un día concreto.
     * @return array<int, array{periodo_id:int,nivel:string,etiqueta:string,inicio:string,fin:string,materia:?string,grupo:?string}>
     */
    public static function ocupacionDia(int $profId, string $dia): array {
        return self::ocupacionDiaDeVarios([$profId], $dia)[(int)$profId] ?? [];
    }

    /**
     * Ocupación de VARIOS profesores en UNA sola consulta. Es lo que permite a
     * SuplenciaHora::sugerir() evaluar a todo el claustro sin lanzar una consulta por
     * candidato. La sirve el índice idx_prof_dia (profesor_id, dia).
     *
     * @return array<int, array> profesor_id => rangos ordenados por hora
     */
    public static function ocupacionDiaDeVarios(array $profIds, string $dia): array {
        $ids = array_values(array_unique(array_filter(array_map('intval', $profIds))));
        if (!$ids) return [];
        $dia = self::$db->escape_string($dia);

        // Incluye las guardias de receso: vigilar el patio ocupa igual que dar clase,
        // así que quien la tenga asignada no puede recibir una suplencia a esa hora.
        $sql = "SELECT h.profesor_id, h.periodo_id, h.tipo, p.nivel, p.etiqueta, p.hora_inicio, p.hora_fin,
                       m.nombre AS materia, g.nombre AS grupo, lg.nombre AS lugar
                  FROM horarios h
                  JOIN periodos p ON p.id = h.periodo_id
             LEFT JOIN materias m ON m.id = h.materia_id
             LEFT JOIN grupos   g ON g.id = h.grupo_id
             LEFT JOIN lugares_guardia lg ON lg.id = h.lugar_id
                 WHERE h.dia = '{$dia}' AND h.profesor_id IN (" . implode(',', $ids) . ")
              ORDER BY h.profesor_id, p.hora_inicio";

        $out = array_fill_keys($ids, []);
        $r = self::$db->query($sql);
        if ($r) while ($row = $r->fetch_assoc()) {
            $out[(int)$row['profesor_id']][] = [
                'periodo_id' => (int)$row['periodo_id'],
                'nivel'      => $row['nivel'],
                'etiqueta'   => $row['etiqueta'],
                'inicio'     => $row['hora_inicio'],
                'fin'        => $row['hora_fin'],
                'tipo'       => $row['tipo'],
                'materia'    => $row['tipo'] === 'guardia' ? 'Guardia' : $row['materia'],
                'grupo'      => $row['tipo'] === 'guardia' ? $row['lugar'] : $row['grupo'],
            ];
        }
        return $out;
    }

    /**
     * Ocupación REAL de una fecha concreta: el horario permanente más los intercambios
     * ya validados de ese día.
     *
     * ⚠️ Un swap `validado` cambia quién da una clase **sin tocar `horarios`** — esa es
     * justamente su razón de ser: es un cambio puntual, no un cambio de horario. Así que
     * quien lee `horarios` a secas ve a quien cede todavía ocupado y a quien cubre
     * todavía libre. Leyendo lo permanente, `sugerir()` daba por disponible a un
     * profesor que sí tenía clase ese día y le encima una cobertura.
     *
     * Por eso hay DOS lectores y no uno:
     *   - `ocupacionDiaDeVarios($ids, $dia)` → el horario permanente, por día de la
     *     semana. Es lo que pintan las rejillas, y debe seguir diciendo la verdad sobre
     *     la semana tipo.
     *   - `ocupacionEfectivaDia($ids, $dia, $fecha)` → quién da clase ESE día. Es lo que
     *     necesita cualquier decisión sobre una fecha (suplencias, hoy; el resto,
     *     cuando lo pida).
     *
     * Cuesta UNA consulta más, no una por candidato: los swaps del día se traen de golpe
     * y se reparten en memoria.
     *
     * @param  array  $profIds candidatos
     * @param  string $dia     día de la semana ('lunes'…), para el horario permanente
     * @param  string $fecha   fecha concreta 'Y-m-d', para los swaps
     * @return array<int, array> profesor_id => rangos ordenados por hora
     */
    public static function ocupacionEfectivaDia(array $profIds, string $dia, string $fecha): array {
        $ocupacion = self::ocupacionDiaDeVarios($profIds, $dia);
        if (!$ocupacion) return $ocupacion;

        $f = self::$db->escape_string($fecha);

        /* Los dos lados del intercambio en una sola consulta. Cada fila de swap_clases
           describe DOS movimientos —la clase que cede el solicitante y la que cede el
           destinatario— y cada uno tiene su propia fecha, que pueden no coincidir
           (Swap::DIAS_VENTANA permite hasta 7 días de separación). Por eso se pregunta
           por las dos y se filtra después cuál de los dos cae en `$fecha`. */
        $sql = "SELECT sw.fecha_origen, sw.fecha_destino,
                       sw.solicitante_id, sw.destinatario_id,
                       sw.horario_origen_id, sw.horario_destino_id
                  FROM swap_clases sw
                 WHERE sw.estado = 'validado'
                   AND (sw.fecha_origen = '{$f}' OR sw.fecha_destino = '{$f}')";

        $mueve = [];   // [ [horario_id, de, a], … ]
        $r = self::$db->query($sql);
        if ($r) while ($row = $r->fetch_assoc()) {
            // Lado origen: la clase del solicitante la da el destinatario.
            if ($row['fecha_origen'] === $fecha && $row['horario_origen_id']) {
                $mueve[] = [(int)$row['horario_origen_id'],
                            (int)$row['solicitante_id'], (int)$row['destinatario_id']];
            }
            // Lado destino: la del destinatario la da el solicitante.
            if ($row['fecha_destino'] === $fecha && $row['horario_destino_id']) {
                $mueve[] = [(int)$row['horario_destino_id'],
                            (int)$row['destinatario_id'], (int)$row['solicitante_id']];
            }
        }
        if (!$mueve) return $ocupacion;

        // Los rangos de las clases movidas. Se consultan aparte porque quien RECIBE la
        // clase puede no estar en $profIds —no ser candidato— y aun así hay que quitarle
        // la suya a quien la cede.
        $ids = array_values(array_unique(array_column($mueve, 0)));
        $rangos = [];
        $q = self::$db->query(
            "SELECT h.id, p.hora_inicio, p.hora_fin FROM horarios h
               JOIN periodos p ON p.id = h.periodo_id
              WHERE h.id IN (" . implode(',', $ids) . ")");
        if ($q) while ($row = $q->fetch_assoc()) {
            $rangos[(int)$row['id']] = [$row['hora_inicio'], $row['hora_fin']];
        }

        foreach ($mueve as [$horarioId, $de, $a]) {
            if (!isset($rangos[$horarioId])) continue;   // la clase se borró después
            [$ini, $fin] = $rangos[$horarioId];

            // Quitársela a quien la cede. Se localiza por reloj y no por periodo_id: la
            // fila puede tener acompañantes de coteaching en el mismo periodo, y el swap
            // mueve la clase entera.
            if (isset($ocupacion[$de])) {
                $ocupacion[$de] = array_values(array_filter(
                    $ocupacion[$de],
                    fn($o) => !($o['inicio'] === $ini && $o['fin'] === $fin)
                ));
            }
            // Y dársela a quien la cubre. Solo si es candidato: a los demás no se les
            // pregunta nada, y montarles la entrada sería trabajo tirado.
            if (isset($ocupacion[$a])) {
                $ocupacion[$a][] = [
                    'periodo_id' => 0,
                    'nivel'      => '',
                    'etiqueta'   => '',
                    'inicio'     => $ini,
                    'fin'        => $fin,
                    'tipo'       => 'clase',
                    'materia'    => 'Intercambio',
                    'grupo'      => null,
                ];
            }
        }

        // Se reordena porque los bloques añadidos van al final; bloquesLibres() y las
        // rejillas de previsualización cuentan con el orden cronológico.
        foreach ($ocupacion as &$lista) {
            usort($lista, fn($a, $b) => strcmp($a['inicio'], $b['inicio']));
        }
        unset($lista);

        return $ocupacion;
    }

    /**
     * Qué es cada uno de esos periodos en el horario de un profesor: clase o guardia de
     * receso, y en ese caso en qué lugar. Una sola consulta para todos los periodos.
     *
     * Lo usa BlogController::guardarHoras() al abrir una suplencia: las horas se marcan
     * sobre el horario del ausente, así que su naturaleza ya está aquí y no hace falta
     * —ni conviene— que el formulario la mande.
     *
     * @return array<int, array{tipo:string,lugar_id:?int}> periodo_id => …
     */
    public static function tipoDePeriodos(int $profId, string $dia, array $periodoIds): array {
        $ids = array_values(array_unique(array_filter(array_map('intval', $periodoIds))));
        if ($ids === [] || $profId <= 0) return [];

        $sql = "SELECT periodo_id, tipo, lugar_id FROM horarios
                 WHERE profesor_id = " . (int)$profId . "
                   AND dia = '" . self::$db->escape_string($dia) . "'
                   AND periodo_id IN (" . implode(',', $ids) . ")";
        $out = [];
        $r = self::$db->query($sql);
        if ($r) while ($row = $r->fetch_assoc()) {
            $out[(int)$row['periodo_id']] = [
                'tipo'     => $row['tipo'] ?: 'clase',
                'lugar_id' => $row['lugar_id'] !== null ? (int)$row['lugar_id'] : null,
            ];
        }
        return $out;
    }

    /** ¿El profesor tiene libre ese tramo del día? (solo mira sus clases, no suplencias) */
    public static function libreEn(int $profId, string $dia, string $inicio, string $fin): bool {
        foreach (self::ocupacionDia($profId, $dia) as $o) {
            if (Periodo::solapan($inicio, $fin, $o['inicio'], $o['fin'])) return false;
        }
        return true;
    }

    /**
     * Choques por solapamiento de reloj en las tres dimensiones. Es lo que la tabla ya
     * no puede garantizar: no hay UNIQUE de profesor/aula/grupo (los impiden el
     * coteaching, las materias divididas y las clases conjuntas) y, aunque los hubiera,
     * no verían que la 1ª hora de Primaria (07:45-08:35) pisa la 2ª de Kinder
     * (08:35-09:25) para un profesor que da en los dos niveles.
     *
     * `$quien` admite además `materia_id` y `division` como CONTEXTO de la clase que se
     * está colocando. Sin ellos el método es conservador —cualquier solapamiento cuenta
     * como choque, que es el comportamiento histórico—; con ellos sabe reconocer las
     * tres convivencias legítimas y no las reporta:
     *
     *   · coteaching      mismo grupo y misma materia, distinto profesor
     *   · materia dividida mismo grupo, ambas con division > 0 y distinta
     *   · clase conjunta  mismo profesor y misma aula y misma materia, distinto grupo
     *
     * @param array $quien ['profesor_id'=>int, 'aula_id'=>?int, 'grupo_id'=>?int,
     *                      'materia_id'=>?int, 'division'=>?int]
     * @param int|int[] $excluirId Filas que NO cuentan como choque. Acepta una lista
     *        porque un bloque de coteaching son N filas (titular + acompañantes): al
     *        editarlo hay que excluirlas TODAS, o cada una detecta a sus hermanas.
     * @return array<int, array{dimension:string,horario_id:int,rango:string,materia:?string,grupo:?string,profesor:?string}>
     */
    public static function choques(string $dia, string $inicio, string $fin, array $quien, $excluirId = 0): array {
        $dia    = self::$db->escape_string($dia);
        $solapa = Periodo::sqlSolapa('p', $inicio, $fin);

        $cond = [];
        if (!empty($quien['profesor_id'])) $cond[] = "(h.profesor_id = " . (int)$quien['profesor_id'] . ")";
        if (!empty($quien['aula_id']))     $cond[] = "(h.aula_id     = " . (int)$quien['aula_id']     . ")";
        if (!empty($quien['grupo_id']))    $cond[] = "(h.grupo_id    = " . (int)$quien['grupo_id']    . ")";
        if (!$cond) return [];

        $ids  = array_values(array_filter(array_map('intval', (array)$excluirId)));
        $excl = $ids ? " AND h.id NOT IN (" . implode(',', $ids) . ")" : '';
        $sql = "SELECT h.id, h.profesor_id, h.aula_id, h.grupo_id, h.periodo_id,
                       h.materia_id, h.division, h.tipo,
                       p.hora_inicio, p.hora_fin, m.nombre AS materia, g.nombre AS grupo, u.nombre AS profesor
                  FROM horarios h
                  JOIN periodos p ON p.id = h.periodo_id
             LEFT JOIN materias m ON m.id = h.materia_id
             LEFT JOIN grupos   g ON g.id = h.grupo_id
             LEFT JOIN usuarios u ON u.id = h.profesor_id
                 WHERE h.dia = '{$dia}' AND {$solapa} AND (" . implode(' OR ', $cond) . "){$excl}";

        // Contexto: solo se aplican las excepciones si el llamador lo aporta.
        $miMat = array_key_exists('materia_id', $quien) ? (int)$quien['materia_id'] : null;
        $miDiv = array_key_exists('division', $quien)   ? (int)$quien['division']   : null;
        $miProf  = (int)($quien['profesor_id'] ?? 0);
        $miAula  = (int)($quien['aula_id'] ?? 0);
        $miGrupo = (int)($quien['grupo_id'] ?? 0);

        $out = [];
        $r = self::$db->query($sql);
        if ($r) while ($row = $r->fetch_assoc()) {
            $mismaMat = $miMat !== null && (int)$row['materia_id'] === $miMat && $miMat > 0;
            $mismoGr  = $miGrupo && (int)$row['grupo_id'] === $miGrupo;
            $mismoPr  = $miProf  && (int)$row['profesor_id'] === $miProf;
            $mismaAu  = $miAula  && (int)$row['aula_id'] === $miAula;

            // Opciones distintas de una materia dividida: conviven por definición.
            if ($mismoGr && $miDiv !== null && $miDiv > 0 && (int)$row['division'] > 0
                && (int)$row['division'] !== $miDiv) continue;
            // Coteaching: la misma materia, en el mismo grupo, con otro docente.
            if ($mismoGr && $mismaMat && !$mismoPr) continue;
            // Clase conjunta a dos grupos: mismo profesor y aula, misma materia.
            if ($mismoPr && $mismaMat && $mismaAu && (int)$row['grupo_id'] !== $miGrupo) continue;

            $dim = 'profesor';
            if (!empty($quien['aula_id'])  && (int)$row['aula_id']  === (int)$quien['aula_id']
                && (int)$row['profesor_id'] !== (int)($quien['profesor_id'] ?? 0)) $dim = 'aula';
            if (!empty($quien['grupo_id']) && (int)$row['grupo_id'] === (int)$quien['grupo_id']
                && (int)$row['profesor_id'] !== (int)($quien['profesor_id'] ?? 0)) $dim = 'grupo';

            $out[] = [
                'dimension'   => $dim,
                'horario_id'  => (int)$row['id'],
                'profesor_id' => (int)$row['profesor_id'],
                // Distingue el choque de aula que la BD SÍ bloquea (mismo periodo, por
                // el UNIQUE uq_aula) del que solo se pisa en el reloj entre jornadas.
                'periodo_id'  => (int)$row['periodo_id'],
                'rango'       => substr($row['hora_inicio'], 0, 5) . '–' . substr($row['hora_fin'], 0, 5),
                'materia'     => $row['materia'],
                'grupo'       => $row['grupo'],
                'profesor'    => $row['profesor'],
            ];
        }
        return $out;
    }

    // ── Editor de horario (módulo Usuarios) ───────────────────────────────────

    /**
     * Colocación por PERIODO, no por tramo de reloj. Es el eje del editor.
     *
     * ⚠️ Deliberadamente NO usa rejilla(). Allí las filas son tramos —cortes del reloj
     * derivados de las jornadas o de los eventos— y un tramo puede no ser periodo de
     * nadie: el fragmento de 20' que queda al cruzarse Primaria y Secundaria no tiene
     * `periodo_id` que asignarle, así que una casilla clicable sobre ese eje no se
     * podría escribir. Aquí cada casilla es exactamente un `(dia, periodo_id)`, que es
     * lo que la tabla `horarios` guarda.
     *
     * Como el eje es de UN nivel, las clases del profesor en otros niveles no encajan en
     * ninguna casilla; se devuelven en `ajenas` de todas las casillas cuyo reloj pisen
     * —con `Periodo::solapan()`, el mismo predicado de choques()— para que se vean, se
     * pueda saltar a su nivel y, sobre todo, no se puedan pisar por accidente.
     *
     * @param  Horario[] $filas     TODAS las clases del profesor, de cualquier nivel
     * @param  Periodo[] $periodos  jornada del nivel mostrado, recesos incluidos
     * @return array<string, array<int, array{propia:?Horario, ajenas:Horario[]}>> [dia][periodo_id]
     */
    public static function rejillaPorPeriodo(array $filas, array $periodos): array {
        $porDia = [];
        foreach ($filas as $h) $porDia[$h->dia][] = $h;

        $out = [];
        foreach (self::DIAS as $dia) {
            $delDia = $porDia[$dia] ?? [];
            foreach ($periodos as $p) {
                $propia = null;
                $ajenas = [];
                foreach ($delDia as $h) {
                    if ((int)$h->periodo_id === (int)$p->id) {
                        // Un duplicado exacto lo impide el UNIQUE; si aun así hubiera
                        // dos, la segunda se trata como ajena y así se ve y se puede
                        // borrar, en vez de desaparecer.
                        if ($propia === null) { $propia = $h; continue; }
                        $ajenas[] = $h;
                        continue;
                    }
                    if ($h->periodo_inicio && Periodo::solapan($p->hora_inicio, $p->hora_fin, $h->periodo_inicio, $h->periodo_fin)) {
                        $ajenas[] = $h;
                    }
                }
                $out[$dia][(int)$p->id] = ['propia' => $propia, 'ajenas' => $ajenas];
            }
        }
        return $out;
    }

    /* ── Coteaching ──
       Una clase que dan dos o tres profesores a la vez son N filas en `horarios`, una
       por docente, con el mismo (dia, periodo_id, grupo_id, materia_id, division) y
       `rol_docente` distinguiendo al titular de los acompañantes. Que cada uno tenga su
       fila es lo que hace que su horario, su disponibilidad y sus suplencias funcionen
       sin ningún caso especial.

       La clave usada aquí es la que ESCRIBE el editor, y es un subconjunto estricto de
       la de agruparBloques() (que agrupa por hora de reloj e ignora el grupo, para poder
       fundir una clase conjunta a dos grupos): sobre filas creadas por el editor las dos
       coinciden, y ser más específico aquí evita confundir al profesor del otro grupo de
       una clase conjunta con un acompañante. */

    /** Filas de acompañante del bloque descrito. El titular NO se incluye. */
    public static function acompanantes(string $dia, int $periodoId, ?int $grupoId, ?int $materiaId, int $division = 0): array {
        $d  = self::$db->escape_string($dia);
        $gr = $grupoId   ? "h.grupo_id = "   . (int)$grupoId   : "h.grupo_id IS NULL";
        $ma = $materiaId ? "h.materia_id = " . (int)$materiaId : "h.materia_id IS NULL";
        return static::consultarSQL(
            self::selectBase() .
            " WHERE h.dia = '{$d}' AND h.periodo_id = " . (int)$periodoId .
            " AND {$gr} AND {$ma} AND h.division = " . (int)$division .
            " AND h.rol_docente = 'acompanante'" . self::ORDEN);
    }

    /**
     * Acompañantes de TODOS los bloques de estas filas, en UNA consulta.
     * Alimenta la rejilla del editor, que si no haría una consulta por casilla.
     *
     * @param  Horario[] $filas  clases del profesor que se está editando
     * @return array<string, array<int, array<int, array{id:int,nombre:string}>>> [dia][periodo_id]
     */
    public static function acompanantesDeBloques(array $filas): array {
        $ids = [];
        foreach ($filas as $h) {
            if (($h->tipo ?? 'clase') === 'guardia') continue;   // una guardia es de una persona
            $ids[(int)$h->periodo_id] = true;
        }
        if (!$ids) return [];

        // Se traen los acompañantes de esos periodos y se cruzan en PHP contra la fila
        // del profesor: montar el OR de N claves compuestas en SQL sería ilegible y no
        // ahorraría nada (el índice útil aquí es idx_prof_dia, que no aplica).
        $sql = "SELECT h.id, h.dia, h.periodo_id, h.grupo_id, h.materia_id, h.division,
                       h.profesor_id, u.nombre AS profesor_nombre
                  FROM horarios h
             LEFT JOIN usuarios u ON u.id = h.profesor_id
                 WHERE h.rol_docente = 'acompanante'
                   AND h.periodo_id IN (" . implode(',', array_keys($ids)) . ")";

        $porClave = [];
        $r = self::$db->query($sql);
        if ($r) while ($row = $r->fetch_assoc()) {
            $clave = $row['dia'] . '|' . (int)$row['periodo_id'] . '|' . (int)$row['grupo_id']
                   . '|' . (int)$row['materia_id'] . '|' . (int)$row['division'];
            $porClave[$clave][] = ['id' => (int)$row['profesor_id'], 'nombre' => (string)$row['profesor_nombre']];
        }

        $out = [];
        foreach ($filas as $h) {
            if (($h->tipo ?? 'clase') === 'guardia') continue;
            $clave = $h->dia . '|' . (int)$h->periodo_id . '|' . (int)$h->grupo_id
                   . '|' . (int)$h->materia_id . '|' . (int)$h->division;
            if (empty($porClave[$clave])) continue;
            // Sin el propio profesor: si él es el acompañante de otro, en su rejilla
            // sigue siendo "su" bloque y no un acompañante de sí mismo.
            $out[$h->dia][(int)$h->periodo_id] = array_values(array_filter(
                $porClave[$clave],
                fn($a) => $a['id'] !== (int)$h->profesor_id
            ));
        }
        return $out;
    }

    /**
     * Choques de un bloque, ya clasificados en lo que impide guardar y lo que solo
     * advierte. Envuelve choques(), que sigue siendo la única implementación del
     * solapamiento.
     *
     * Reparto y por qué:
     *  · profesor y grupo → ERROR. Son imposibles físicamente: la misma persona o los
     *    mismos alumnos no pueden estar en dos sitios.
     *  · aula en el MISMO periodo → ERROR. Es lo único de aulas que la BD sí bloquea
     *    (UNIQUE uq_aula), y dejarlo pasar devolvería un `Duplicate entry` ilegible.
     *  · aula en otro periodo → AVISO. Es logística, no imposibilidad: el patio o el
     *    salón de usos múltiples reciben legítimamente a dos grupos a la vez, y varias
     *    aulas del catálogo se usan como marcador de zona. Misma semántica que el
     *    importador CSV.
     *
     * @param array $quien ['profesor_id'=>int, 'aula_id'=>?int, 'grupo_id'=>?int]
     * @param int|int[] $excluirId ver choques(): una lista al editar un bloque de coteaching
     * @return array{errores: string[], avisos: string[]}
     */
    public static function conflictos(string $dia, Periodo $periodo, array $quien, $excluirId = 0): array {
        $errores = [];
        $avisos  = [];
        $donde   = (self::DIAS_LABEL[$dia] ?? ucfirst($dia)) . ' · ' . $periodo->etiqueta;

        foreach (self::choques($dia, $periodo->hora_inicio, $periodo->hora_fin, $quien, $excluirId) as $c) {
            $que = '«' . ($c['materia'] ?: 'una clase') . '»'
                 . ($c['grupo'] ? ' (' . $c['grupo'] . ')' : '')
                 . ' de ' . $c['rango'];

            if ($c['dimension'] === 'profesor') {
                $errores[] = "{$donde}: ya tiene {$que}.";
            } elseif ($c['dimension'] === 'grupo') {
                $errores[] = "{$donde}: el grupo ya tiene {$que}" . ($c['profesor'] ? ' con ' . $c['profesor'] : '') . '.';
            } elseif ((int)$c['periodo_id'] === (int)$periodo->id) {
                $errores[] = "{$donde}: el aula ya está ocupada en este mismo periodo por {$que}.";
            } else {
                $avisos[]  = "{$donde}: el aula ya está ocupada por {$que}. Puedes guardar igual.";
            }
        }
        return ['errores' => $errores, 'avisos' => $avisos];
    }

    // ── Rejilla semanal ───────────────────────────────────────────────────────

    /**
     * Coloca las clases sobre un eje de tiempo. ÚNICA implementación: la consumen la
     * vista `_grid.php` y el endpoint JSON que alimenta `.supl-week`, para que la
     * rejilla del panel y la de suplencias no puedan divergir.
     *
     * Las filas dejan de ser "periodos" y pasan a ser tramos de reloj, porque un
     * profesor de dos niveles tiene clases que no encajan en una sola jornada. Cada
     * clase ocupa tantos tramos como dure (`span` → `rowspan` en la tabla).
     *
     * Invariante que impide el desalineo: el eje se construye con TODOS los periodos de
     * los niveles del ámbito, y toda clase pertenece a uno de ellos, así que el
     * `hora_inicio` de cualquier clase es siempre un corte del eje.
     *
     * Con `comprimir` el eje se corta solo donde hay algo y los huecos consecutivos de un
     * mismo día se funden en una celda. Solo se activa cuando el eje mezcla jornadas: con
     * un solo nivel la jornada ES el marco de lectura ("libre en tu 3ª hora" significa
     * algo) y comprimir destruiría esa semántica a cambio de nada.
     *
     * @param Horario[] $filas  clases a colocar (deben venir de selectBase())
     * @param Periodo[] $eje    periodos del ámbito, recesos incluidos
     * @param array     $opts   comprimir(bool) · declarados(string[]) · extra(rangos)
     * @return array{tramos: array, rejilla: array<string, array>}
     */
    /**
     * Agrupa las filas de un día en BLOQUES visuales. Una fila de `horarios` es un
     * (clase × profesor); lo que se pinta en la rejilla es la clase entera.
     *
     * La clave de agrupación es (hora, materia, aula, division) — sin profesor y sin
     * grupo — porque eso es lo que hace que dos filas sean "la misma clase":
     *
     *   · coteaching     mismo grupo, dos docentes  → misma clave, se fusionan
     *   · clase conjunta 6ºA y 6ºB juntos en Ecología → misma clave, se fusionan
     *                    (la celda acaba diciendo los dos grupos)
     *   · división       Arte y Música a la misma hora → materia distinta y
     *                    division distinta: claves distintas, no se fusionan
     *
     * El titular queda como `principal` (es el que da el color y la materia); los
     * acompañantes van detrás en `docentes`.
     *
     * @param Horario[] $filas
     * @return BloqueHorario[] ordenados por hora
     */
    private static function agruparBloques(array $filas): array {
        $bloques = [];
        foreach ($filas as $h) {
            $clave = implode('|', [
                $h->periodo_inicio, $h->periodo_fin, $h->tipo ?? 'clase',
                (int)$h->materia_id, (int)$h->aula_id, (int)$h->lugar_id, (int)$h->division,
            ]);
            if (!isset($bloques[$clave])) $bloques[$clave] = new BloqueHorario($h);
            else                          $bloques[$clave]->agregar($h);
        }
        $out = array_values($bloques);
        usort($out, fn($a, $b) => [$a->inicio, $a->fin, $a->division] <=> [$b->inicio, $b->fin, $b->division]);
        return $out;
    }

    public static function rejilla(array $filas, array $eje, array $opts = []): array {
        $comprimir  = !empty($opts['comprimir']);
        $declarados = $opts['declarados'] ?? [];

        $tramos  = $comprimir
            ? Periodo::tramosComprimidos($eje, $filas, $opts['extra'] ?? [])
            : Periodo::tramos($eje);
        $recesos = array_values(array_filter($eje, fn($p) => (int)$p->es_receso === 1));

        // Cortes que la fusión de libres no puede tragarse (ver más abajo)
        $protegidos = [];
        foreach ($opts['extra'] ?? [] as $r) { $protegidos[$r[0]] = true; $protegidos[$r[1]] = true; }

        $porDia = [];
        foreach ($filas as $h) $porDia[$h->dia][] = $h;

        $rejilla = [];
        foreach (self::DIAS as $dia) {
            $clases = self::agruparBloques($porDia[$dia] ?? []);

            // El receso de un nivel solo aplica a quien esté en ese nivel ese día.
            //
            // ⚠️ Un día sin clases no dice en qué jornada está. Antes se caía a los
            // recesos de TODO el ámbito, y con un eje de varios niveles eso llenaba la
            // columna de tazas superpuestas (el receso de Maternal a las 09:30, el de
            // Kinder a las 10:00, el de Primaria a las 10:15…). Con un solo nivel no hay
            // ambigüedad y su receso sí se pinta; con varios, no pintar ninguno es más
            // honesto que pintarlos todos.
            $niveles = [];
            foreach ($clases as $h) $niveles[$h->principal->periodo_nivel] = true;
            if (!$niveles) {
                $nivEje = [];
                foreach ($eje as $p) $nivEje[$p->nivel] = true;
                if (count($nivEje) === 1) $niveles = $nivEje;
            }

            $celdas = [];
            $usada  = [];
            $i = 0;
            $n = count($tramos);

            while ($i < $n) {
                $t = $tramos[$i];

                // 1) ¿arranca aquí un bloque? (prioridad máxima: clase > receso > libre)
                $bloque = null;
                foreach ($clases as $k => $b) {
                    if (isset($usada[$k]) || $b->inicio !== $t['inicio']) continue;
                    $bloque = $b; $usada[$k] = true; break;
                }

                if ($bloque) {
                    // Lo que se pisa con este bloque puede ser una cosa u otra:
                    //   · otra OPCIÓN de una materia dividida (ambas con division > 0):
                    //     conviven, y se apilan dentro de la misma celda;
                    //   · cualquier otra cosa: dato sucio que la BD ya no puede impedir,
                    //     se adjunta para que la vista lo señale en vez de perderlo.
                    $opciones = [];
                    $conflicto = [];
                    foreach ($clases as $k => $b) {
                        if (isset($usada[$k])) continue;
                        if (!Periodo::solapan($bloque->inicio, $bloque->fin, $b->inicio, $b->fin)) continue;
                        $usada[$k] = true;
                        if ($bloque->division > 0 && $b->division > 0 && $b->division !== $bloque->division) {
                            $opciones[] = $b;
                        } else {
                            $conflicto[] = $b->principal;
                        }
                    }
                    $span = Periodo::spanTramos($tramos, $bloque->inicio, $bloque->fin);
                    $celdas[] = [
                        'tramo' => $i, 'span' => $span, 'tipo' => 'clase',
                        'inicio' => $bloque->inicio, 'fin' => $bloque->fin,
                        // `horario` sigue siendo la fila principal: las vistas que solo
                        // saben de una clase por celda siguen funcionando igual.
                        'horario' => $bloque->principal,
                        'bloque'  => $bloque,
                        'opciones' => $opciones,
                        'conflicto' => $conflicto,
                        // Da clase en un nivel que no tiene declarado: dato a corregir,
                        // no un choque. Se pinta igual, marcado.
                        'ajeno' => $declarados && !in_array($bloque->principal->periodo_nivel, $declarados, true),
                    ];
                    $i += $span;
                    continue;
                }

                // 2) ¿cae en el receso de alguno de sus niveles?
                $receso = null;
                foreach ($recesos as $p) {
                    if (!isset($niveles[$p->nivel])) continue;
                    if (Periodo::solapan($t['inicio'], $t['fin'], $p->hora_inicio, $p->hora_fin)) { $receso = $p; break; }
                }

                if ($receso) {
                    $ini  = max($receso->hora_inicio, $t['inicio']);   // pudo empezar bajo una clase
                    $span = Periodo::spanTramos($tramos, $ini, $receso->hora_fin);
                    $span = min($span, self::hastaProximaClase($tramos, $i, $clases, $usada));
                    $celdas[] = [
                        'tramo' => $i, 'span' => $span, 'tipo' => 'receso',
                        'inicio' => $ini, 'fin' => $tramos[$i + $span - 1]['fin'],
                        'nivel' => $receso->nivel, 'etiqueta' => $receso->etiqueta,
                    ];
                    $i += $span;
                    continue;
                }

                // 3) hora libre
                $celdas[] = [
                    'tramo' => $i, 'span' => 1, 'tipo' => 'libre',
                    'inicio' => $t['inicio'], 'fin' => $t['fin'],
                ];
                $i++;
            }

            /* ⚠️ Guarda dura. Si el hora_inicio de una clase no es corte del eje, el
               bucle de arriba no la coloca NUNCA y la suma de spans sigue cuadrando: la
               clase desaparecía en silencio y ningún test lo veía. Aquí se rescata
               colgándola de la celda que la solape, para que un dato imposible se vea en
               pantalla en vez de evaporarse. */
            foreach ($clases as $k => $b) {
                if (isset($usada[$k])) continue;
                foreach ($celdas as $idx => $c) {
                    if (!Periodo::solapan($b->inicio, $b->fin, $c['inicio'], $c['fin'])) continue;
                    if ($c['tipo'] !== 'clase') {
                        // Cae sobre un hueco/receso: se convierte en celda de clase.
                        $celdas[$idx]['tipo']      = 'clase';
                        $celdas[$idx]['horario']   = $b->principal;
                        $celdas[$idx]['bloque']    = $b;
                        $celdas[$idx]['opciones']  = [];
                        $celdas[$idx]['conflicto'] = [];
                        $celdas[$idx]['ajeno']     = true;
                    } else {
                        $celdas[$idx]['conflicto'][] = $b->principal;
                    }
                    $usada[$k] = true;
                    break;
                }
            }

            // Fusión de huecos consecutivos. Es segura por construcción: N celdas de
            // span 1 pasan a una de span N, así que la suma del día no cambia, el eje no
            // se toca y los otros días conservan sus filas por separado.
            //
            // Los bordes de `extra` (la hora que se quiere cubrir) NO se funden: se
            // inyectaron como corte precisamente para que el "Cubriría aquí" mida esa
            // hora, y fundirlos volvería a diluirla en un bloque libre de dos horas.
            if ($comprimir) {
                $fundidas = [];
                foreach ($celdas as $c) {
                    $prev = $fundidas ? array_key_last($fundidas) : null;
                    $puedeFundir = $prev !== null
                        && $fundidas[$prev]['tipo'] === 'libre' && $c['tipo'] === 'libre'
                        && !isset($protegidos[$c['inicio']]);
                    if ($puedeFundir) {
                        $fundidas[$prev]['span'] += $c['span'];
                        $fundidas[$prev]['fin']   = $c['fin'];
                        continue;
                    }
                    $fundidas[] = $c;
                }
                $celdas = $fundidas;
            }

            $rejilla[$dia] = $celdas;
        }

        /* Un tramo que ningún día usa (ni clase ni receso) se pinta como franja fina en
           vez de fila completa: es lo que de verdad da la sensación de rejilla compacta,
           y es puro CSS — no altera el rowspan de nadie.
           ⚠️ Solo se aplasta lo que NO tiene rótulo propio. `hueco` se pensó para los
           fragmentos que el eje comprimido genera al cruzarse dos jornadas —trozos de
           reloj que no son periodo de nadie—, no para una hora real de la jornada. Una
           «3ª hora» libre toda la semana es información, y aplastarla a 16px y al 50% de
           opacidad entre filas de 62px es justo lo que se veía descuadrado. */
        $usadoPorAlguien = array_fill(0, count($tramos), false);
        foreach ($rejilla as $celdas) {
            foreach ($celdas as $c) {
                if ($c['tipo'] === 'libre') continue;
                for ($j = $c['tramo']; $j < $c['tramo'] + $c['span']; $j++) $usadoPorAlguien[$j] = true;
            }
        }
        foreach ($tramos as $j => $t) {
            /* ⚠️ Un tramo `extra` NUNCA es hueco. Es la hora que se quiere cubrir, y se
               inyectó como corte precisamente para poder medirla: en el preview de una
               suplencia el buen candidato está LIBRE justo ahí, así que si además ningún
               otro día usa ese fragmento cumplía las dos condiciones de hueco y la celda
               «Cubriría aquí» se pintaba a 16px y al 50% de opacidad entre filas de 46 —
               el bloque del día seleccionado se veía más corto que el resto. */
            $tramos[$j]['hueco'] = !$usadoPorAlguien[$j]
                                && ($t['etiqueta'] ?? '') === ''
                                && !isset($protegidos[$t['inicio']]);
            if ($tramos[$j]['hueco']) $tramos[$j]['alto'] = Periodo::EJE_HUECO_MIN;
        }

        return ['tramos' => $tramos, 'rejilla' => $rejilla];
    }

    /**
     * Resuelve el ámbito de una rejilla: qué niveles la enmarcan, si hay discrepancia
     * con lo declarado y si toca comprimir. Centraliza el `nivelesDe($filas) ?: NIVELES`
     * que estaba copiado en los tres consumidores.
     *
     * @return array{niveles:string[], declarados:string[], discrepantes:string[], comprimir:bool}
     */
    public static function ambito(array $filas, array $declarados = []): array {
        $deClases = self::nivelesDe($filas);

        /* ⚠️ Sin clases NI niveles declarados no hay ámbito, y el resultado es `[]`, no
           los cinco niveles. Caer a `Materia::NIVELES` montaba el eje con las cinco
           jornadas a la vez: una rejilla de ~16 filas fragmentadas, con rótulos repetidos
           («1ª hora 07:00–07:30» y «1ª hora 07:30–07:50» son dos niveles distintos), sin
           fusión de libres —solo corre al comprimir— y con los recesos de los cinco
           niveles apilados. Con `[]` el eje sale vacío y la vista pinta su empty state,
           que es lo que de verdad pasa: ese profesor no tiene horario. */
        $niveles  = $declarados ?: $deClases;
        $discrepantes = $declarados ? array_values(array_diff($deClases, $declarados)) : [];

        // El `|| $discrepantes` no es cosmético: con el eje sin comprimir, una clase de
        // un nivel no declarado tiene un hora_inicio que no es corte y no se colocaría.
        // Comprimir mete sus cortes en el eje. Sin clases ni niveles declarados no se
        // comprime: el eje serían los recesos de los cinco niveles, una rejilla absurda.
        $comprimir = ($filas || $declarados) && (count($niveles) > 1 || $discrepantes !== []);

        return [
            'niveles'      => $niveles,
            'declarados'   => $declarados,
            'discrepantes' => $discrepantes,
            'comprimir'    => $comprimir,
        ];
    }

    /** Tramos disponibles desde $i antes de que arranque el siguiente bloque sin colocar. */
    private static function hastaProximaClase(array $tramos, int $i, array $clases, array $usada): int {
        $n = count($tramos);
        for ($j = $i + 1; $j < $n; $j++) {
            foreach ($clases as $k => $b) {
                if (!isset($usada[$k]) && $b->inicio === $tramos[$j]['inicio']) return $j - $i;
            }
        }
        return $n - $i;
    }
}

/**
 * Una clase tal y como se ve en la rejilla: la unidad visual, no la fila de BD.
 *
 * Existe porque `horarios` guarda un (clase × profesor) y lo que hay que pintar es la
 * clase entera — con su titular, sus acompañantes y, si es conjunta, los dos grupos
 * que la reciben. La construye Horario::agruparBloques().
 */
class BloqueHorario {
    /** @var Horario Fila que manda: el titular (o la primera, si no consta ninguno). */
    public $principal;
    /** @var Horario[] Todas las filas de la clase, con el titular primero. */
    public $docentes = [];
    public $inicio;
    public $fin;
    public $division;
    public $tipo;

    public function __construct(Horario $h) {
        $this->principal = $h;
        $this->docentes  = [$h];
        $this->inicio    = $h->periodo_inicio;
        $this->fin       = $h->periodo_fin;
        $this->division  = (int)$h->division;
        $this->tipo      = $h->tipo ?? 'clase';
    }

    public function agregar(Horario $h): void {
        $this->docentes[] = $h;
        // El titular manda aunque llegue el segundo: es quien da color y materia.
        if (($h->rol_docente ?? 'titular') === 'titular' && ($this->principal->rol_docente ?? 'titular') !== 'titular') {
            $this->principal = $h;
        }
        usort($this->docentes, fn($a, $b) => [($a->rol_docente ?? 'titular') !== 'titular', (string)$a->profesor_nombre]
                                        <=> [($b->rol_docente ?? 'titular') !== 'titular', (string)$b->profesor_nombre]);
    }

    /** Nombres de los docentes, titular primero. Vacío si la vista ya es de un profesor. */
    public function nombresDocentes(): array {
        $out = [];
        foreach ($this->docentes as $h) if ($h->profesor_nombre) $out[(int)$h->profesor_id] = $h->profesor_nombre;
        return array_values($out);
    }

    /** Grupos que reciben la clase. Más de uno = clase conjunta. */
    public function nombresGrupos(): array {
        $out = [];
        foreach ($this->docentes as $h) if ($h->grupo_nombre) $out[(int)$h->grupo_id] = $h->grupo_nombre;
        return array_values($out);
    }

    public function esGuardia(): bool { return $this->tipo === 'guardia'; }

    /** ¿Hay acompañantes además del titular? */
    public function tieneAcompanantes(): bool {
        return count($this->nombresDocentes()) > 1;
    }
}
