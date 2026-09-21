<?php
namespace Model;

class Evento extends ActiveRecord {
    protected static $tabla      = 'eventos';
    protected static $columnasDB = ['id', 'fecha', 'fecha_fin', 'tipo', 'icono', 'titulo', 'descripcion', 'audiencia', 'niveles'];

    public $id;
    public $fecha;
    public $fecha_fin;
    public $tipo;
    /** Icono Font Awesome elegido a mano. NULL = el del `tipo` (ver icono()). */
    public $icono;
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
     * Cómo se nombra cada tipo **de cara a las familias**. El panel dice «Junta» y
     * «Entrega» porque son los términos internos; en la web se leen mejor como
     * «Reunión» y «Académico», y «Suspensión» a secas asusta más de lo que informa.
     */
    public const TIPO_LABEL_PUBLICO = [
        'festivo'    => 'Festivo',
        'evento'     => 'Evento',
        'junta'      => 'Reunión',
        'entrega'    => 'Académico',
        'suspension' => 'Aviso',
    ];

    /**
     * Color de cada tipo. **Fuente única**: lo leen el listado del panel, el
     * calendario público y el PDF del ciclo — este último no puede consultar las
     * custom properties `--cal-*` del SCSS, así que necesita el hex en PHP.
     *
     * ⚠️ Deben coincidir con las `--cal-*` del `:root` de
     * `src/scss/estaticas/_comunidad-familias.scss`. Estuvieron desalineados: la
     * vista de Familias llevaba su propia tabla con otros cinco colores, así que
     * un mismo evento salía morado en la tarjeta de aviso y azul en el punto del
     * calendario de al lado.
     */
    public const TIPO_COLOR = [
        'festivo'    => '#e51022',
        'evento'     => '#4285f4',
        'junta'      => '#aa2296',
        'entrega'    => '#46bdc6',
        'suspension' => '#f5b400',
    ];

    /** Icono por defecto de cada tipo, cuando el evento no eligió uno propio. */
    public const TIPO_ICONO = [
        'festivo'    => 'fa-star',
        'evento'     => 'fa-palette',
        'junta'      => 'fa-people-group',
        'entrega'    => 'fa-file-lines',
        'suspension' => 'fa-calendar-xmark',
    ];

    /**
     * Catálogo de iconos elegibles, agrupado para el selector del panel.
     *
     * ⚠️ **Es una lista blanca, no una sugerencia.** El valor acaba como clase CSS
     * dentro del HTML público (`<i class="fa-solid {$icono}">`), así que
     * `normalizarIcono()` descarta cualquier cosa que no esté aquí: un POST
     * manipulado no puede inyectar atributos por esa vía.
     *
     * Son iconos **sólidos** de Font Awesome 6, que es lo que ya carga el sitio.
     *
     * @var array<string, array<string, string>>
     */
    public const ICONOS = [
        'Académico' => [
            'fa-graduation-cap'  => 'Graduación',
            'fa-file-lines'      => 'Boletas',
            'fa-clipboard-check' => 'Evaluación',
            'fa-book'            => 'Lectura',
            'fa-chalkboard-user' => 'Clase',
            'fa-microscope'      => 'Ciencia',
            'fa-award'           => 'Reconocimiento',
            'fa-pencil'          => 'Inscripciones',
        ],
        'Celebración' => [
            'fa-star'          => 'Festivo',
            'fa-cake-candles'  => 'Cumpleaños',
            'fa-gift'          => 'Regalos',
            'fa-masks-theater' => 'Teatro',
            'fa-music'         => 'Música',
            'fa-palette'       => 'Arte',
            'fa-camera'        => 'Foto',
            'fa-flag'          => 'Cívico',
        ],
        'Comunidad' => [
            'fa-people-group' => 'Junta',
            'fa-people-roof'  => 'Familias',
            'fa-handshake'    => 'Encuentro',
            'fa-bullhorn'     => 'Aviso',
            'fa-comments'     => 'Orientación',
            'fa-hand-holding-heart' => 'Servicio',
        ],
        'Deporte y salud' => [
            'fa-futbol'         => 'Deporte',
            'fa-person-running' => 'Activación',
            'fa-medal'          => 'Torneo',
            'fa-apple-whole'    => 'Salud',
            'fa-notes-medical'  => 'Médico',
        ],
        'Calendario' => [
            'fa-calendar-xmark'  => 'Sin clases',
            'fa-calendar-check'  => 'Fecha clave',
            'fa-umbrella-beach'  => 'Vacaciones',
            'fa-bus'             => 'Salida',
            'fa-tree'            => 'Campamento',
            'fa-snowflake'       => 'Invierno',
            'fa-sun'             => 'Verano',
            'fa-utensils'        => 'Convivencia',
        ],
    ];

    /**
     * Punto de código de cada icono del catálogo dentro de `fa-solid-900.ttf`.
     *
     * **Solo lo usa el PDF.** La web carga Font Awesome por CSS y le basta la clase;
     * Dompdf no: corre con `isRemoteEnabled = false` y no sabe de `::before`, así que
     * para imprimir un icono hay que escribir su carácter con la fuente FA aplicada.
     * Por eso el TTF está versionado en `src/fonts/` junto a los Outfit.
     *
     * Se guarda el hex **como cadena** y no el carácter: un archivo fuente lleno de
     * glifos del área de uso privado se ve como una fila de cajitas en cualquier
     * editor y nadie podría revisarlo. Lo convierte `glifo()`.
     *
     * ⚠️ **Tiene que cubrir todas las claves de ICONOS.** Los valores se extrajeron
     * del propio `all.min.css` de Font Awesome 6.1.2 —la versión que carga el sitio—,
     * no de memoria. Al añadir un icono al catálogo hay que añadir aquí su código, o
     * en el PDF saldrá sin icono (`glifo()` devuelve '' y la plantilla se lo salta:
     * degrada, no pinta una caja rota).
     *
     * @var array<string, string>
     */
    public const ICONO_GLIFO = [
        'fa-graduation-cap'            => 'f19d',
        'fa-file-lines'                => 'f15c',
        'fa-clipboard-check'           => 'f46c',
        'fa-book'                      => 'f02d',
        'fa-chalkboard-user'           => 'f51c',
        'fa-microscope'                => 'f610',
        'fa-award'                     => 'f559',
        'fa-pencil'                    => 'f303',
        'fa-star'                      => 'f005',
        'fa-cake-candles'              => 'f1fd',
        'fa-gift'                      => 'f06b',
        'fa-masks-theater'             => 'f630',
        'fa-music'                     => 'f001',
        'fa-palette'                   => 'f53f',
        'fa-camera'                    => 'f030',
        'fa-flag'                      => 'f024',
        'fa-people-group'              => 'e533',
        'fa-people-roof'               => 'e537',
        'fa-handshake'                 => 'f2b5',
        'fa-bullhorn'                  => 'f0a1',
        'fa-comments'                  => 'f086',
        'fa-hand-holding-heart'        => 'f4be',
        'fa-futbol'                    => 'f1e3',
        'fa-person-running'            => 'f70c',
        'fa-medal'                     => 'f5a2',
        'fa-apple-whole'               => 'f5d1',
        'fa-notes-medical'             => 'f481',
        'fa-calendar-xmark'            => 'f273',
        'fa-calendar-check'            => 'f274',
        'fa-umbrella-beach'            => 'f5ca',
        'fa-bus'                       => 'f207',
        'fa-tree'                      => 'f1bb',
        'fa-snowflake'                 => 'f2dc',
        'fa-sun'                       => 'f185',
        'fa-utensils'                  => 'f2e7',
    ];

    /**
     * El mes en que arranca el ciclo escolar (agosto). De aquí sale la ventana del
     * calendario descargable: agosto de un año → julio del siguiente.
     *
     * Va como constante y no como fecha fija porque el ciclo **se deduce de la fecha
     * de hoy** (ver ciclo()): un literal '2026-08-01' caducaría el próximo agosto y
     * dejaría el calendario público anclado a un curso que ya terminó.
     */
    public const CICLO_MES_INICIO = 8;

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
        $this->normalizarIcono();
        return static::$alertas;
    }

    /**
     * Deja `icono` en una clave de ICONOS, o NULL.
     *
     * ⚠️ No valida, **filtra**: un icono desconocido no es un error de formulario
     * sino un valor que no debe existir, y quedarse con el del tipo es siempre
     * correcto. El descarte es obligatorio porque el valor se emite como clase CSS
     * en la web pública; es la misma razón por la que aquí no vale un `trim()`.
     *
     * Guardar el icono que ya es el del tipo como NULL mantiene el vínculo: si
     * alguien cambia el tipo del evento después, el icono lo sigue.
     */
    private function normalizarIcono(): void {
        $i = trim((string)($this->icono ?? ''));
        $this->icono = ($i !== '' && isset(self::iconosPlanos()[$i]) && $i !== (self::TIPO_ICONO[$this->tipo] ?? ''))
            ? $i : null;
    }

    /**
     * El catálogo aplanado a `icono => etiqueta`, que es como se consulta para
     * validar y para rotular. Se calcula una vez por petición.
     *
     * @return array<string, string>
     */
    public static function iconosPlanos(): array {
        static $plano = null;
        if ($plano === null) {
            $plano = [];
            foreach (self::ICONOS as $grupo) $plano += $grupo;
        }
        return $plano;
    }

    /** El icono que se pinta: el elegido a mano, o el del tipo. Nunca vacío. */
    public function icono(): string {
        $i = (string)($this->icono ?? '');
        if ($i !== '' && isset(self::iconosPlanos()[$i])) return $i;
        return self::TIPO_ICONO[$this->tipo] ?? 'fa-calendar-day';
    }

    /** El color del tipo. Fuente única para panel, web y PDF (ver TIPO_COLOR). */
    public function color(): string {
        return self::TIPO_COLOR[$this->tipo] ?? '#4267ac';
    }

    /**
     * El icono como CARÁCTER, para imprimirlo con la fuente de Font Awesome.
     *
     * Solo lo necesita el PDF (ver ICONO_GLIFO). Devuelve '' si la clase no tiene
     * código registrado, y la plantilla entonces no pinta icono: degradar es mejor
     * que sacar la caja vacía del glifo ausente.
     *
     * @param string $clase clave de ICONOS, normalmente lo que devuelve icono()
     */
    public static function glifo(string $clase): string {
        $hex = self::ICONO_GLIFO[$clase] ?? null;
        return $hex === null ? '' : mb_chr(hexdec($hex), 'UTF-8');
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

    /** Persistencia con NULL real para fecha_fin/descripcion/niveles/icono. */
    public function guardar() {
        $db   = self::$db;
        $cols = ['fecha', 'fecha_fin', 'tipo', 'icono', 'titulo', 'descripcion', 'audiencia', 'niveles'];
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

    /**
     * La ventana del ciclo escolar que contiene una fecha: agosto → julio.
     *
     * **Se deduce de la fecha, no se configura.** Con CICLO_MES_INICIO = 8, el 19 de
     * septiembre de 2026 cae en el ciclo 2026-08-01 → 2027-07-31, y el 3 de marzo de
     * 2027 también. Un rango fijo en la base o en una constante habría que recordar
     * moverlo cada agosto, y el calendario público se quedaría enseñando el curso
     * anterior hasta que alguien se diera cuenta.
     *
     * @param  ?string $ref Fecha `Y-m-d` de referencia; por defecto, hoy.
     * @return array{ini:string, fin:string, anio_ini:int, anio_fin:int, etiqueta:string}
     */
    public static function ciclo(?string $ref = null): array {
        $ts  = $ref ? strtotime($ref) : time();
        if ($ts === false) $ts = time();
        $y   = (int)date('Y', $ts);
        $m   = (int)date('n', $ts);
        $ini = ($m >= self::CICLO_MES_INICIO) ? $y : $y - 1;
        $mes = ['', 'Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
        $mIni = self::CICLO_MES_INICIO;
        $mFin = $mIni === 1 ? 12 : $mIni - 1;
        return [
            'ini'      => sprintf('%04d-%02d-01', $ini, $mIni),
            'fin'      => date('Y-m-t', mktime(0, 0, 0, $mFin, 1, $ini + 1)),
            'anio_ini' => $ini,
            'anio_fin' => $ini + 1,
            'etiqueta' => $mes[$mIni] . ' ' . $ini . ' – ' . $mes[$mFin] . ' ' . ($ini + 1),
        ];
    }

    /**
     * Los doce meses del ciclo, en orden, como `['anio' => int, 'mes' => int]`.
     * Lo consumen la vista de ciclo completo y el PDF; que salga de aquí evita que
     * la pantalla y el papel puedan discrepar en qué meses son el curso.
     *
     * @return array<int, array{anio:int, mes:int}>
     */
    public static function mesesCiclo(?string $ref = null): array {
        $c = self::ciclo($ref);
        $out = [];
        for ($i = 0; $i < 12; $i++) {
            $ts = mktime(0, 0, 0, self::CICLO_MES_INICIO + $i, 1, $c['anio_ini']);
            $out[] = ['anio' => (int)date('Y', $ts), 'mes' => (int)date('n', $ts)];
        }
        return $out;
    }

    /**
     * Eventos de una audiencia que **tocan** un rango de fechas.
     *
     * Un evento de varios días entra si su tramo se solapa con la ventana, no solo
     * si empieza dentro: unas vacaciones del 20 de diciembre al 6 de enero
     * pertenecen a los dos meses y tienen que salir en ambos.
     */
    public static function porAudienciaEnRango(string $audiencia, string $ini, string $fin): array {
        if (!\in_array($audiencia, ['familias', 'estudiantes'], true)) return [];
        $a = self::$db->escape_string($audiencia);
        $i = self::$db->escape_string($ini);
        $f = self::$db->escape_string($fin);
        return static::consultarSQL(
            "SELECT * FROM eventos
              WHERE audiencia = '{$a}'
                AND fecha <= '{$f}'
                AND COALESCE(fecha_fin, fecha) >= '{$i}'
           ORDER BY fecha ASC, id ASC");
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
