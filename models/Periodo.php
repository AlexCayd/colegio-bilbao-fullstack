<?php
namespace Model;

/**
 * Bloque de la jornada. La jornada es POR NIVEL: cada nivel entra, sale y descansa a
 * su hora, así que `orden` solo tiene sentido dentro de su propio nivel — la 3ª hora
 * de Primaria y la 3ª de Secundaria son periodos distintos que además se pisan en el
 * reloj.
 *
 * ⚠️ Corolario para todo el módulo: dos clases NO chocan por tener el mismo
 * `periodo_id`, chocan por solaparse en el tiempo. Este modelo es el único sitio
 * donde se define qué significa "se pisan" (`solapan()` / `sqlSolapa()`).
 */
class Periodo extends ActiveRecord {
    protected static $tabla      = 'periodos';
    protected static $columnasDB = ['id', 'nivel', 'orden', 'etiqueta', 'hora_inicio', 'hora_fin', 'es_receso'];

    public $id;
    public $nivel;
    public $orden;
    public $etiqueta;
    public $hora_inicio;
    public $hora_fin;
    public $es_receso;

    /** Caché por petición de porNivel(): sugerir() la consulta una vez por candidato. */
    private static $cacheNivel = null;

    /** Orden canónico: Maternal → Bachillerato y, dentro del nivel, la jornada. */
    private static function orden(): string {
        return Materia::ordenNivel('nivel') . ", orden ASC";
    }

    /** Todos los periodos. Con $nivel, solo los de esa jornada. */
    public static function todos(?string $nivel = null): array {
        $filtro = $nivel ? " WHERE nivel = '" . self::$db->escape_string($nivel) . "'" : '';
        return static::consultarSQL("SELECT * FROM periodos{$filtro} ORDER BY " . self::orden());
    }

    /** Solo los periodos de clase (excluye recesos). */
    public static function clases(?string $nivel = null): array {
        $filtro = $nivel ? " AND nivel = '" . self::$db->escape_string($nivel) . "'" : '';
        return static::consultarSQL("SELECT * FROM periodos WHERE es_receso = 0{$filtro} ORDER BY " . self::orden());
    }

    /** ['Primaria' => Periodo[], …] en UNA consulta, cacheado por petición. */
    public static function porNivel(bool $soloClases = false): array {
        if (self::$cacheNivel === null) {
            self::$cacheNivel = [];
            foreach (self::todos() as $p) self::$cacheNivel[$p->nivel][] = $p;
        }
        if (!$soloClases) return self::$cacheNivel;

        $out = [];
        foreach (self::$cacheNivel as $niv => $lista) {
            $out[$niv] = array_values(array_filter($lista, fn($p) => (int)$p->es_receso === 0));
        }
        return $out;
    }

    /**
     * Periodos de un conjunto de niveles: el "ámbito" de una rejilla. Un profesor que
     * da clase en Primaria y Secundaria necesita el eje de tiempo de ambos, nunca el
     * de los cinco niveles (serían el doble de filas para nada).
     */
    public static function deNiveles(array $niveles, bool $soloClases = false): array {
        $porNivel = self::porNivel($soloClases);
        $out = [];
        foreach ($niveles as $n) foreach ($porNivel[$n] ?? [] as $p) $out[] = $p;
        usort($out, fn($a, $b) => [$a->hora_inicio, $a->hora_fin] <=> [$b->hora_inicio, $b->hora_fin]);
        return $out;
    }

    /**
     * Solapamiento ESTRICTO: una clase que acaba a las 08:40 y otra que empieza a las
     * 08:40 NO se pisan (no se exige margen de traslado entre niveles).
     * Las horas 'HH:MM:SS' comparan lexicográficamente igual que cronológicamente.
     */
    public static function solapan(string $aIni, string $aFin, string $bIni, string $bFin): bool {
        return $aIni < $bFin && $bIni < $aFin;
    }

    /** El mismo predicado como fragmento SQL, para no reescribirlo en cada consulta. */
    public static function sqlSolapa(string $alias, string $inicio, string $fin): string {
        $i = self::$db->escape_string($inicio);
        $f = self::$db->escape_string($fin);
        return "({$alias}.hora_inicio < '{$f}' AND '{$i}' < {$alias}.hora_fin)";
    }

    /** Duración en minutos de un rango de la jornada. */
    public static function minutos(string $inicio, string $fin): int {
        return (int)max(0, (strtotime("1970-01-01 {$fin}") - strtotime("1970-01-01 {$inicio}")) / 60);
    }

    /**
     * Eje de tiempo: convierte un conjunto de periodos (de uno o varios niveles) en los
     * tramos reales del reloj. Un tramo es el intervalo entre dos cortes consecutivos de
     * la unión de todos los hora_inicio/hora_fin; se descartan los que ningún periodo
     * cubre (huecos entre jornadas).
     *
     * Con un solo nivel devuelve exactamente su jornada, tramo por periodo: la rejilla
     * queda idéntica a la de antes de que la jornada fuera por nivel.
     *
     * @param  Periodo[] $periodos
     * @return array<int, array{inicio:string,fin:string,minutos:int,etiqueta:string}>
     *         `etiqueta` es la común a todos los periodos que cubren el tramo ('' si discrepan).
     */
    public static function tramos(array $periodos): array {
        if (!$periodos) return [];

        $cortes = [];
        foreach ($periodos as $p) {
            $cortes[$p->hora_inicio] = true;
            $cortes[$p->hora_fin]    = true;
        }
        $cortes = array_keys($cortes);
        sort($cortes);

        $out = [];
        for ($i = 0; $i < count($cortes) - 1; $i++) {
            $ini = $cortes[$i];
            $fin = $cortes[$i + 1];

            $etiquetas = [];
            foreach ($periodos as $p) {
                if (self::solapan($ini, $fin, $p->hora_inicio, $p->hora_fin)) $etiquetas[$p->etiqueta] = true;
            }
            if (!$etiquetas) continue;   // hueco entre jornadas: no es tramo de nadie

            $min = self::minutos($ini, $fin);
            [, $nivel] = self::etiquetaExacta($periodos, $ini, $fin);
            $out[] = [
                'inicio'   => $ini,
                'fin'      => $fin,
                'minutos'  => $min,
                'etiqueta' => count($etiquetas) === 1 ? array_key_first($etiquetas) : '',
                'nivel'    => $nivel,
                // `alto` es lo que alimenta el --min de la fila; en el eje sin comprimir
                // coincide con la duración, pero se emite igual para que las vistas lean
                // siempre la misma clave vengan del eje que vengan.
                'alto'     => min($min, self::EJE_TOPE_MIN),
            ];
        }
        return $out;
    }

    /**
     * Alto máximo (en "minutos equivalentes") de un tramo largo del eje comprimido.
     *
     * ⚠️ Desde que las rejillas web dan a TODAS las filas el mismo alto (`--hor-fila`
     * en el SCSS), ni esta constante ni `EJE_HUECO_MIN` las gobiernan: `alto` sigue
     * viajando en el JSON del endpoint —contrato del que cuelgan tres vistas— pero
     * ninguna rejilla lo lee ya. El PDF nunca lo leyó.
     */
    public const EJE_TOPE_MIN = 60;
    /** Alto de un tramo que ningún día usa. Ver la nota de EJE_TOPE_MIN: ya no se usa. */
    public const EJE_HUECO_MIN = 16;

    /**
     * Eje COMPRIMIDO: se corta solo donde hay algo.
     *
     * A diferencia de tramos(), que parte de la jornada y por eso genera un corte en
     * cada frontera de cada nivel (16 filas para dos jornadas desfasadas, la mayoría
     * vacías), éste parte de los EVENTOS: las clases de la semana y los recesos que se
     * van a pintar. Lo que ningún día usa no llega a ser fila.
     *
     * ⚠️ No descarta ningún tramo, así que el eje resultante es CONTIGUO
     * (`tramos[i]['fin'] === tramos[i+1]['inicio']`). Es imprescindible: spanTramos()
     * cuenta por contención sobre todo el array, y con un eje agujereado sumaría tramos
     * no adyacentes y el rowspan se comería celdas de abajo.
     *
     * @param Periodo[] $eje    periodos del ámbito (dan los bordes de jornada y los recesos)
     * @param Horario[] $filas  TODAS las clases de la semana, de cualquier nivel — también
     *                          las de niveles no declarados, para que no se pierdan
     * @param array     $extra  rangos ['HH:MM:SS','HH:MM:SS'] que además deben ser corte
     *                          (la hora a cubrir en el preview de suplencias)
     * @return array<int, array{inicio:string,fin:string,minutos:int,etiqueta:string,alto:int}>
     */
    public static function tramosComprimidos(array $eje, array $filas, array $extra = []): array {
        $cortes = [];
        $ini0 = null;
        $fin0 = null;
        $marcar = function (?string $i, ?string $f) use (&$cortes, &$ini0, &$fin0) {
            if ($i === null || $f === null) return;
            $cortes[$i] = true;
            $cortes[$f] = true;
            $ini0 = ($ini0 === null) ? $i : min($ini0, $i);
            $fin0 = ($fin0 === null) ? $f : max($fin0, $f);
        };

        // Bordes de la jornada del ámbito: sin ellos, un profesor sin clases se queda
        // con eje vacío y la rejilla diría "no hay jornada configurada".
        foreach ($eje as $p) {
            $ini0 = ($ini0 === null) ? $p->hora_inicio : min($ini0, $p->hora_inicio);
            $fin0 = ($fin0 === null) ? $p->hora_fin    : max($fin0, $p->hora_fin);
            // Los recesos se pintan como celda: sus dos bordes tienen que ser corte.
            if ((int)$p->es_receso === 1) { $cortes[$p->hora_inicio] = true; $cortes[$p->hora_fin] = true; }
        }
        // Toda clase de la semana. Es el invariante del rowspan: el hora_inicio de una
        // clase SIEMPRE tiene que ser un corte, o rejilla() no llega a colocarla.
        foreach ($filas as $h) $marcar($h->periodo_inicio, $h->periodo_fin);
        foreach ($extra as $r) $marcar($r[0] ?? null, $r[1] ?? null);

        if ($ini0 === null || $fin0 === null) return [];
        $cortes[$ini0] = true;
        $cortes[$fin0] = true;

        $cortes = array_keys(array_filter($cortes, fn($_, $c) => $c >= $ini0 && $c <= $fin0, ARRAY_FILTER_USE_BOTH));
        sort($cortes);

        $out = [];
        for ($i = 0; $i < count($cortes) - 1; $i++) {
            $ini = $cortes[$i];
            $fin = $cortes[$i + 1];
            $min = self::minutos($ini, $fin);
            [$etiqueta, $nivel] = self::etiquetaExacta($eje, $ini, $fin);
            $out[] = [
                'inicio'   => $ini,
                'fin'      => $fin,
                'minutos'  => $min,
                'etiqueta' => $etiqueta,
                'nivel'    => $nivel,
                'alto'     => min($min, self::EJE_TOPE_MIN),
            ];
        }
        return $out;
    }

    /**
     * Etiqueta de un tramo SOLO si coincide exactamente con un periodo. Un fragmento de
     * 20 min de la 3ª hora de Primaria no puede rotularse "3ª hora": mentiría sobre
     * cuánto dura y sobre dónde empieza.
     *
     * Devuelve también el nivel, que es lo que distingue dos tramos con el mismo
     * rótulo: en un eje mixto salen dos filas «Receso» seguidas (la de Secundaria a
     * las 09:30 y la de Primaria a las 10:00) y sin el nivel parecen un error.
     *
     * @return array{0:string,1:string}  [etiqueta, nivel]; '' en ambos si no es exacto
     */
    private static function etiquetaExacta(array $periodos, string $ini, string $fin): array {
        $e = [];
        foreach ($periodos as $p) {
            if ($p->hora_inicio === $ini && $p->hora_fin === $fin) $e[$p->etiqueta][$p->nivel] = true;
        }
        if (count($e) !== 1) return ['', ''];
        $etiqueta = (string)array_key_first($e);
        $niveles  = array_keys($e[$etiqueta]);
        return [$etiqueta, count($niveles) === 1 ? (string)$niveles[0] : ''];
    }

    /** Índice del tramo que arranca exactamente en $hora, o null. */
    public static function indiceTramo(array $tramos, string $hora): ?int {
        foreach ($tramos as $i => $t) if ($t['inicio'] === $hora) return $i;
        return null;
    }

    /** Cuántos tramos abarca el rango [$inicio, $fin). Mínimo 1. */
    public static function spanTramos(array $tramos, string $inicio, string $fin): int {
        $n = 0;
        foreach ($tramos as $t) if ($t['inicio'] >= $inicio && $t['fin'] <= $fin) $n++;
        return max(1, $n);
    }
}
