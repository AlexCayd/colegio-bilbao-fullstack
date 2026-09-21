<?php

namespace Model;

/**
 * Contador propio de visitas al sitio público.
 *
 * Existe porque ni Clarity ni Google Analytics —los dos que ya están instalados— exponen
 * una serie histórica que se pueda consultar desde PHP: sirven para mirar en su panel, no
 * para pintar una gráfica dentro del nuestro. Esta tabla es nuestra y no depende de
 * credenciales de terceros.
 *
 * ⚠️ Es un AGREGADO, no un registro de eventos: una fila por (día, ruta, visitante) con un
 * contador, y no una fila por carga de página. Un sitio institucional no necesita el
 * detalle y así la tabla no crece sin control.
 *
 * ⚠️ No guarda NADA identificable. `visitante_hash` = sha1(IP + user-agent + sal del día),
 * y como la sal cambia cada día el hash no permite seguir a nadie de una jornada a la
 * siguiente: solo sirve para no contar diez veces al mismo visitante en la misma página el
 * mismo día.
 */
class Visita extends ActiveRecord
{
    protected static $tabla = 'visitas';
    protected static $columnasDB = ['id', 'fecha', 'ruta', 'visitante_hash', 'golpes'];

    public $id;
    public $fecha;
    public $ruta;
    public $visitante_hash;
    public $golpes;

    /** Alias de los agregados. Sin declarar, crearObjeto() los descarta. */
    public $dia;
    public $total;

    /** Rangos que ofrece la gráfica del panel. `0` = todo el histórico. */
    public const RANGOS = [7 => '7 días', 30 => '30 días', 60 => '60 días', 0 => 'Todo'];

    /**
     * Registra una visita. Idempotente dentro del mismo día: la segunda carga de la misma
     * página por el mismo visitante incrementa el contador en vez de crear otra fila, que
     * es lo que hace el UNIQUE (fecha, ruta, visitante_hash).
     *
     * Nunca lanza: una analítica rota no puede tumbar el sitio público. Si la tabla no
     * existe todavía (base sin actualizar), simplemente no cuenta.
     */
    public static function registrar(string $ruta): void {
        try {
            $ruta = self::normalizarRuta($ruta);
            if ($ruta === '') return;

            $db = self::$db;
            $r  = $db->escape_string($ruta);
            $h  = $db->escape_string(self::huella());

            $db->query("INSERT INTO visitas (fecha, ruta, visitante_hash, golpes)
                        VALUES (CURDATE(), '{$r}', '{$h}', 1)
                        ON DUPLICATE KEY UPDATE golpes = golpes + 1");
        } catch (\Throwable $e) {
            // Silencio deliberado: ver docblock.
        }
    }

    /**
     * Ruta normalizada y acotada a 190 caracteres (lo que cabe en el UNIQUE con utf8mb4).
     * Se queda solo con el path: la query string multiplicaría las filas por cada
     * combinación de parámetros de campaña sin decir nada nuevo sobre qué se visitó.
     */
    private static function normalizarRuta(string $ruta): string {
        $path = parse_url($ruta, PHP_URL_PATH) ?: '/';
        $path = '/' . trim($path, '/');
        return mb_substr($path, 0, 190);
    }

    /**
     * Huella anónima y diaria del visitante.
     *
     * La sal incluye la fecha, así que el mismo visitante produce un hash distinto cada
     * día: sirve para deduplicar dentro de la jornada y no para construir un historial de
     * navegación de nadie.
     */
    private static function huella(): string {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
        // Detrás de un proxy la cabecera puede traer una lista: la primera es el cliente.
        if (str_contains($ip, ',')) $ip = trim(explode(',', $ip)[0]);
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        return sha1($ip . '|' . $ua . '|' . date('Y-m-d'));
    }

    /**
     * Serie diaria para la gráfica, con los días sin visitas rellenos a 0.
     *
     * El relleno es imprescindible: sin él, Chart.js une el 3 con el 7 en una recta y un
     * fin de semana sin tráfico se lee como si hubiera habido visitas decrecientes.
     *
     * @param  int $dias 0 = todo el histórico
     * @return array{labels: string[], datos: int[], total: int, media: float}
     */
    public static function serie(int $dias = 30): array {
        $dias = (int)$dias;
        $where = $dias > 0
            ? "WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL " . ($dias - 1) . " DAY)"
            : '';

        $bruto = [];
        $r = self::$db->query(
            "SELECT fecha, SUM(golpes) total FROM visitas {$where} GROUP BY fecha ORDER BY fecha ASC");
        if ($r) while ($row = $r->fetch_assoc()) $bruto[$row['fecha']] = (int)$row['total'];

        /* ⚠️ A MEDIANOCHE, no "hace N días" a secas. `new DateTimeImmutable('-6 days')`
           arrastra la hora actual, así que el bucle comparaba 14/09 08:20 contra
           20/09 00:00 y se cortaba un día antes: la serie salía con 6 fechas en vez de 7
           y —lo peor— **sin el día de hoy**, que es justo el que se está mirando.

           Con "todo el histórico" el eje arranca en la primera visita registrada y no en
           una fecha fija: si no, una base recién estrenada pintaría años de ceros. */
        if ($dias > 0) {
            $desde = (new \DateTimeImmutable('today'))->modify('-' . ($dias - 1) . ' days');
        } elseif ($bruto) {
            $desde = new \DateTimeImmutable(array_key_first($bruto) . ' 00:00:00');
        } else {
            return ['labels' => [], 'datos' => [], 'total' => 0, 'media' => 0.0];
        }

        $hasta  = new \DateTimeImmutable('today');
        $labels = [];
        $datos  = [];
        for ($d = $desde; $d <= $hasta; $d = $d->modify('+1 day')) {
            $k = $d->format('Y-m-d');
            $labels[] = $k;
            $datos[]  = $bruto[$k] ?? 0;
        }

        $total = array_sum($datos);
        return [
            'labels' => $labels,
            'datos'  => $datos,
            'total'  => $total,
            'media'  => $datos ? round($total / count($datos), 1) : 0.0,
        ];
    }

    /** Las rutas más visitadas del periodo, para acompañar la gráfica. */
    public static function topRutas(int $dias = 30, int $limite = 6): array {
        $limite = max(1, min(20, (int)$limite));
        $dias   = (int)$dias;
        $where  = $dias > 0
            ? "WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL " . ($dias - 1) . " DAY)"
            : '';

        $out = [];
        $r = self::$db->query(
            "SELECT ruta, SUM(golpes) total FROM visitas {$where}
              GROUP BY ruta ORDER BY total DESC LIMIT {$limite}");
        if ($r) while ($row = $r->fetch_assoc()) {
            $out[] = ['ruta' => $row['ruta'], 'total' => (int)$row['total']];
        }
        return $out;
    }

    /**
     * Las cuatro series de golpe, para que la gráfica cambie de rango sin ir al servidor.
     * Son cuatro consultas baratas sobre una tabla indexada por fecha, y evitan montar un
     * endpoint JSON solo para esto.
     */
    public static function seriesTodas(): array {
        $out = [];
        foreach (array_keys(self::RANGOS) as $d) $out[(string)$d] = self::serie($d);
        return $out;
    }
}
