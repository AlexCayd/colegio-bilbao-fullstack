<?php

namespace Classes;

/**
 * El diccionario de nombres del claustro: cómo se llama cada persona en cada sitio.
 *
 * ── Por qué existe ────────────────────────────────────────────────────────────
 * El CSV de horarios trae el nombre de sala de maestros («Gaby», «Nancy G», «Fer
 * Uribe»); la base de datos guarda el del expediente («Gabriela Sánchez», «Nancy
 * González de la Rosa»). Son la misma persona y no se parecen lo bastante como para
 * que ninguna heurística los case sola sin equivocarse: «Fernanda» encaja igual de
 * bien con dos personas distintas y elegir mal le da a alguien el horario de otra.
 *
 * Sin este archivo, importar el horario real **duplicaba casi todo el claustro**:
 * cada nombre corto que no coincidía con el de la BD se daba de alta como cuenta
 * nueva. El diccionario es la tabla de equivalencias que el colegio mantiene a mano,
 * y con ella el importador **reconoce** en vez de crear.
 *
 * ── El archivo ────────────────────────────────────────────────────────────────
 * Se acepta `.csv`, `.xlsx` y `.xlsm` — el CSV es la salida directa de «Guardar como»
 * y sirve igual, así que el colegio puede seguir trabajando en Excel—. Vive en **dos
 * carpetas**, y las dos se miran:
 *
 *   · `diccionario/`         versionado. Es el suelo: lo que trae un clon limpio.
 *   · `storage/diccionario/` lo que se sube desde el panel, junto a las demás carpetas
 *                            escribibles del proyecto. No se escribe en la versionada:
 *                            daría conflictos con git y pediría permisos sobre código.
 *
 * De todos los candidatos **se usa el más reciente**, que es lo que significa dejar ahí
 * una versión nueva — y hace que una subida gane siempre, sin ninguna regla aparte.
 * Cuál se ha leído lo dice `estado()`, y la previa de la importación lo enseña: leer el
 * archivo equivocado en silencio sería peor que no leer ninguno.
 *
 * Cuatro columnas, reconocidas **por su cabecera** y no por su posición (añadir una
 * quinta no rompe nada):
 *
 *   | Versión corta | Versión larga       | Nombre real                  | Correo            |
 *   |---------------|---------------------|------------------------------|-------------------|
 *   | Gaby          | Gabriela Sánchez    | GABRIELA SANCHEZ MONTES DE OCA | gabriela.sanchez… |
 *
 * Las **tres** grafías son alias de la misma persona: el importador busca por las tres
 * porque no sabe cuál usará el archivo de horarios, y el correo es el identificador
 * fuerte cuando existe cuenta.
 *
 * ⚠️ **Nada aquí normaliza para comparar.** Esta clase devuelve el dato crudo; quien
 * construye el índice de búsqueda es `BlogController::indiceDiccionario()` con su
 * `claveCatalogo()`, que es la misma normalización que usa para el resto de catálogos.
 * Dos recetas de normalización en dos sitios se desincronizan, y la primera vez que
 * lo hicieran sería un profesor recibiendo el horario de otro.
 *
 * ⚠️ **Todo degrada.** Sin carpeta, sin archivo, sin extensión `zip` o con el Excel
 * corrupto, `entradas()` devuelve `[]` y `estado()['error']` explica por qué: el
 * importador sigue funcionando exactamente como antes del diccionario (casar por
 * nombre exacto y avisar de los parecidos). Un diccionario ilegible no puede impedir
 * cargar el horario del colegio.
 */
class Diccionario {

    /**
     * Dónde se busca, en este orden de preferencia **solo para desempatar**: quien
     * manda de verdad es la fecha de modificación. Las dos están fuera de `public/`,
     * que es lo que importa — son nombres y correos del claustro.
     */
    private const CARPETAS = [
        'subido' => __DIR__ . '/../storage/diccionario',
        'repo'   => __DIR__ . '/../diccionario',
    ];

    /** Dónde aterriza lo que se sube desde el panel. */
    public const DESTINO = __DIR__ . '/../storage/diccionario/claustro.csv';

    /** Lo que sabemos leer. El CSV es «Guardar como» desde el mismo Excel. */
    private const EXTENSIONES = ['xlsm', 'xlsx', 'csv'];

    /**
     * Cabecera → campo. Se busca por **fragmento** y en este orden, así que
     * «Correo Electronico», «correo» y «e-mail» caen los tres en `email`.
     * El orden importa: `nombre real` se comprueba antes que nada porque lleva
     * la palabra «nombre», que también aparece en otras cabeceras posibles.
     */
    private const CABECERAS = [
        'real'  => ['real', 'expediente', 'oficial'],
        'corta' => ['cort', 'alias', 'apodo'],
        'larga' => ['larg', 'complet'],
        'email' => ['correo', 'mail'],
    ];

    /** Resultado memorizado: el archivo se lee UNA vez por petición. */
    private static ?array $cache = null;

    /**
     * Las personas del diccionario, tal y como están escritas en el archivo.
     *
     * @return array<int, array{corta:string, larga:string, real:string, email:string, nombre:string}>
     *         `nombre` es la grafía con la que se daría de alta (ver `nombreDeAlta()`).
     */
    public static function entradas(): array {
        return self::leer()['entradas'];
    }

    /**
     * Qué archivo se leyó y qué salió mal, para poder decirlo en pantalla.
     *
     * `origen` es `subido` o `repo`: no es lo mismo estar leyendo lo que alguien cargó
     * la semana pasada que la copia que vino con el proyecto, y la pantalla lo dice.
     *
     * @return array{archivo:?string, origen:?string, fecha:?int, total:int, error:?string, otros:string[]}
     */
    public static function estado(): array {
        $r = self::leer();
        return [
            'archivo' => $r['archivo'],
            'origen'  => $r['origen'],
            'fecha'   => $r['fecha'],
            'total'   => count($r['entradas']),
            'error'   => $r['error'],
            'otros'   => $r['otros'],
        ];
    }

    /**
     * Olvida lo leído para que la próxima llamada vuelva al disco.
     *
     * Hace falta porque en **una misma petición** se guarda un diccionario nuevo y
     * acto seguido se lee el horario contra él: sin esto seguiría vigente el que se
     * cargó al principio de la petición y la previa hablaría del archivo anterior.
     */
    public static function olvidar(): void {
        self::$cache = null;
    }

    /**
     * ¿Sirve este archivo como diccionario? Devuelve `[entradas, error]`.
     *
     * Se llama **sobre el temporal de la subida**, antes de mover nada: un archivo que
     * no se entiende no puede pisar al que funciona. Que la comprobación sea el propio
     * lector —y no una validación paralela— es lo que garantiza que lo que se acepta
     * aquí es exactamente lo que se leerá después.
     *
     * ⚠️ La extensión va **aparte**: el archivo subido vive en un temporal de PHP
     * (`C:\…\phpA1B2.tmp`), así que deducirla de la ruta mandaría un CSV al lector de
     * Excel y el archivo bueno se rechazaría por ilegible.
     *
     * @param string $extension       la del nombre original, sin punto
     * @param bool   $exigirCabecera  `true` para lo que se sube: sin cabecera se rechaza
     * @return array{0:array, 1:?string}
     */
    public static function comprobar(string $ruta, string $extension, bool $exigirCabecera = false): array {
        $ext = strtolower(trim($extension, '. '));
        if (!in_array($ext, self::EXTENSIONES, true)) {
            return [[], 'Solo se acepta .csv, .xlsx o .xlsm.'];
        }
        try {
            $tabla = $ext === 'csv' ? self::filasDeCsv($ruta) : self::filasDeExcel($ruta);
        } catch (\Throwable $e) {
            return [[], 'No se pudo leer el archivo: ' . $e->getMessage()];
        }
        return self::interpretar($tabla, $exigirCabecera);
    }

    /**
     * Con qué nombre se crea la cuenta de alguien que todavía no la tiene.
     *
     * La **versión larga**, no el nombre real. El real es el del expediente y viene en
     * mayúsculas y sin acentos («ADRIAN ARMANDO ARCE PERALTA»): pasarlo a capitalizado
     * daría «Adrian», que es una falta de ortografía en el nombre de una persona y
     * además no es como la llama nadie. La larga es la grafía que el propio colegio
     * eligió como legible y es con la que el resto del claustro la reconoce en
     * horarios, suplencias e intercambios.
     */
    private static function nombreDeAlta(array $e): string {
        foreach (['larga', 'real', 'corta'] as $campo) {
            if ($e[$campo] !== '') {
                // El real va en mayúsculas; las otras dos ya vienen bien escritas.
                return $campo === 'real' ? mb_convert_case(mb_strtolower($e[$campo], 'UTF-8'), MB_CASE_TITLE, 'UTF-8') : $e[$campo];
            }
        }
        return '';
    }

    // ── Lectura ───────────────────────────────────────────────────────────────

    private static function leer(): array {
        if (self::$cache !== null) return self::$cache;

        $vacio = ['archivo' => null, 'origen' => null, 'fecha' => null,
                  'entradas' => [], 'error' => null, 'otros' => []];

        $archivos = self::candidatos();
        if (!$archivos) {
            return self::$cache = array_merge($vacio, [
                'error' => 'No hay ningún archivo de diccionario, ni subido desde el panel ni en la '
                         . 'carpeta «diccionario/» (se acepta .csv, .xlsx o .xlsm).',
            ]);
        }

        $elegido = array_shift($archivos);
        $nombre  = basename($elegido['ruta']);
        // Con la carpeta delante: el archivo subido y el del repositorio se llaman los
        // dos `claustro.csv`, así que una lista de nombres a secas diría «claustro.csv»
        // dos veces y no habría forma de saber cuál se está usando.
        $otros = array_map(
            fn(array $c) => ($c['origen'] === 'subido' ? 'storage/diccionario/' : 'diccionario/') . basename($c['ruta']),
            $archivos
        );

        [$entradas, $error] = self::comprobar($elegido['ruta'], (string)pathinfo($elegido['ruta'], PATHINFO_EXTENSION));
        if ($error !== null && !$entradas) {
            return self::$cache = array_merge($vacio, [
                'archivo' => $nombre,
                'origen'  => $elegido['origen'],
                'otros'   => $otros,
                'error'   => 'No se pudo usar «' . $nombre . '»: ' . $error,
            ]);
        }

        return self::$cache = [
            'archivo'  => $nombre,
            'origen'   => $elegido['origen'],
            'fecha'    => $elegido['fecha'] ?: null,
            'entradas' => $entradas,
            'error'    => $error,
            'otros'    => $otros,
        ];
    }

    /**
     * Los archivos legibles de **las dos carpetas**, del más reciente al más viejo.
     *
     * La fecha es el único criterio, y por eso un archivo recién subido gana sin que
     * haga falta ninguna regla de precedencia entre carpetas: acaba de escribirse.
     *
     * @return array<int, array{ruta:string, origen:string, fecha:int}>
     */
    private static function candidatos(): array {
        $out = [];
        foreach (self::CARPETAS as $origen => $carpeta) {
            if (!is_dir($carpeta)) continue;
            foreach ((array)scandir($carpeta) as $f) {
                if ($f === '.' || $f === '..') continue;
                // Excel deja `~$archivo.xlsx` mientras lo tiene abierto: no es el documento.
                if (str_starts_with($f, '~$') || str_starts_with($f, '.')) continue;
                if (!in_array(strtolower((string)pathinfo($f, PATHINFO_EXTENSION)), self::EXTENSIONES, true)) continue;
                $ruta = $carpeta . '/' . $f;
                if (!is_file($ruta)) continue;
                $out[] = ['ruta' => $ruta, 'origen' => $origen, 'fecha' => @filemtime($ruta) ?: 0];
            }
        }
        usort($out, fn(array $a, array $b) => $b['fecha'] <=> $a['fecha']);
        return $out;
    }

    /**
     * Filas de un `.xlsx`/`.xlsm`, que son un ZIP con el contenido en XML.
     *
     * Se lee a mano y no con una librería porque hace falta **una** hoja de **cuatro**
     * columnas de texto: traerse un lector de hojas de cálculo entero por eso sería
     * una dependencia nueva en Composer para leer un archivo de 40 filas.
     *
     * @return array<int, array<string,string>> filas, cada una `letra de columna => texto`
     */
    private static function filasDeExcel(string $ruta): array {
        if (!class_exists(\ZipArchive::class)) {
            throw new \RuntimeException('falta la extensión «zip» de PHP para abrir un Excel. Guarda el diccionario como .csv.');
        }
        $zip = new \ZipArchive();
        if ($zip->open($ruta) !== true) throw new \RuntimeException('el archivo no se abre como Excel.');

        try {
            // Las cadenas de texto no viven en la hoja sino en una tabla aparte, y la
            // celda guarda su índice. Una `<si>` puede venir partida en varios `<r>`
            // cuando el texto lleva formato mezclado: se concatenan.
            $compartidas = [];
            if ($xml = $zip->getFromName('xl/sharedStrings.xml')) {
                foreach (self::xml($xml)->si as $si) {
                    $t = (string)$si->t;
                    foreach ($si->r as $r) $t .= (string)$r->t;
                    $compartidas[] = $t;
                }
            }

            $hoja = self::xml($zip->getFromName(self::primeraHoja($zip)) ?: '');

            $filas = [];
            foreach ($hoja->sheetData->row as $row) {
                $celdas = [];
                foreach ($row->c as $c) {
                    $col = preg_replace('/\d+/', '', (string)$c['r']);   // «B7» → «B»
                    $tipo = (string)$c['t'];
                    $v = match ($tipo) {
                        's'         => $compartidas[(int)$c->v] ?? '',    // índice a la tabla
                        'inlineStr' => (string)$c->is->t,
                        default     => (string)$c->v,
                    };
                    if ($col !== '') $celdas[$col] = trim($v);
                }
                if ($celdas) $filas[] = $celdas;
            }
            return $filas;
        } finally {
            $zip->close();
        }
    }

    /**
     * Ruta interna de la primera hoja del libro.
     *
     * No se da por hecho `xl/worksheets/sheet1.xml`: al reordenar o renombrar hojas
     * en Excel, la primera del libro puede ser `sheet3.xml`, y entonces se leería la
     * hoja equivocada **sin ningún error**. Se sigue la cadena real (workbook → rels).
     */
    private static function primeraHoja(\ZipArchive $zip): string {
        $libro = $zip->getFromName('xl/workbook.xml');
        $rels  = $zip->getFromName('xl/_rels/workbook.xml.rels');
        if ($libro && $rels) {
            $wb = self::xml($libro);
            $id = (string)($wb->sheets->sheet[0]->attributes('r', true)['id'] ?? '');
            if ($id !== '') {
                foreach (self::xml($rels)->Relationship as $rel) {
                    if ((string)$rel['Id'] !== $id) continue;
                    $destino = ltrim((string)$rel['Target'], '/');
                    return str_starts_with($destino, 'xl/') ? $destino : 'xl/' . $destino;
                }
            }
        }
        return 'xl/worksheets/sheet1.xml';
    }

    /** SimpleXML con los errores como excepción, no como warning suelto. */
    private static function xml(string $s): \SimpleXMLElement {
        $previo = libxml_use_internal_errors(true);
        $x = simplexml_load_string($s);
        libxml_use_internal_errors($previo);
        if ($x === false) throw new \RuntimeException('el contenido interno no es XML válido.');
        return $x;
    }

    /**
     * Filas de un `.csv`. Mismo criterio de codificación que el importador de
     * horarios: si ya es UTF-8 válido se deja —reconvertir rompe lo que estaba bien—
     * y si no se traduce desde Windows-1252, que es como sale de Excel.
     *
     * @return array<int, array<string,string>> con letras de columna, como el Excel
     */
    private static function filasDeCsv(string $ruta): array {
        $s = @file_get_contents($ruta);
        if ($s === false) throw new \RuntimeException('no se pudo leer el archivo.');
        if (str_starts_with($s, "\xEF\xBB\xBF")) $s = substr($s, 3);
        if (!mb_check_encoding($s, 'UTF-8')) $s = (string)mb_convert_encoding($s, 'UTF-8', 'Windows-1252');

        // El separador lo decide la primera línea: Excel en español exporta con `;`.
        $primera = strtok($s, "\r\n") ?: '';
        $sep = substr_count($primera, ';') > substr_count($primera, ',') ? ';' : ',';

        $filas = [];
        foreach (preg_split('/\R/u', $s) as $linea) {
            if (trim($linea) === '') continue;
            $celdas = [];
            foreach (str_getcsv($linea, $sep, '"', '') as $i => $v) {
                $celdas[self::letra($i)] = trim((string)$v);
            }
            $filas[] = $celdas;
        }
        return $filas;
    }

    /** 0 → «A», 25 → «Z», 26 → «AA». */
    private static function letra(int $i): string {
        $s = '';
        for ($n = $i + 1; $n > 0; $n = intdiv($n - 1, 26)) $s = chr(65 + ($n - 1) % 26) . $s;
        return $s;
    }

    /**
     * De filas de celdas a personas: localiza la cabecera y lee lo que hay debajo.
     *
     * @param array<int, array<string,string>> $tabla
     * @return array{0: array, 1: ?string} entradas y, si algo impide leerlas, el motivo
     */
    private static function interpretar(array $tabla, bool $exigirCabecera = false): array {
        if (!$tabla) return [[], 'El diccionario está vacío.'];

        // ── Dónde está cada columna ──────────────────────────────────────────
        // Por cabecera y no por posición: así añadir una columna al Excel (un
        // teléfono, una nota) no desplaza el significado de las demás.
        $cols  = null;
        $desde = 0;
        foreach ($tabla as $i => $fila) {
            if ($i > 4) break;                       // la cabecera está arriba o no está
            $mapa = self::columnasDe($fila);
            if ($mapa !== null) { $cols = $mapa; $desde = $i + 1; break; }
        }
        if ($cols === null) {
            // ⚠️ El respaldo posicional NO vale para un archivo que se acaba de subir.
            // Sin cabecera, cualquier CSV de cuatro columnas —una lista de aulas, un
            // export de otra cosa— se leería como si fuera el claustro, y a la
            // siguiente importación el colegio entero saldría duplicado. Para el
            // archivo del repositorio sí se asume el orden documentado: es el que
            // mantiene el colegio y lo único que puede faltarle es el rótulo.
            if ($exigirCabecera) {
                return [[], 'No se reconocen las columnas. La primera fila tiene que nombrarlas: '
                          . 'versión corta, versión larga, nombre real y correo.'];
            }
            $cols = ['corta' => 'A', 'larga' => 'B', 'real' => 'C', 'email' => 'D'];
        }

        $entradas = [];
        $vistos   = [];
        foreach (array_slice($tabla, $desde) as $fila) {
            $e = [];
            foreach (['corta', 'larga', 'real', 'email'] as $campo) {
                $e[$campo] = isset($cols[$campo]) ? trim($fila[$cols[$campo]] ?? '') : '';
            }
            $e['email'] = mb_strtolower($e['email'], 'UTF-8');
            // Una persona sin ninguna grafía no es una persona: es una fila en blanco
            // de las 1000 que Excel arrastra debajo de los datos.
            if ($e['corta'] === '' && $e['larga'] === '' && $e['real'] === '') continue;

            $e['nombre'] = self::nombreDeAlta($e);

            // La misma persona repetida (dos filas idénticas) se queda con la primera:
            // fundirlas cambiaría cuál gana según el orden del archivo.
            $firma = mb_strtolower($e['corta'] . '|' . $e['email'], 'UTF-8');
            if (isset($vistos[$firma])) continue;
            $vistos[$firma] = true;

            $entradas[] = $e;
        }

        if (!$entradas) return [[], 'El diccionario no tiene ninguna fila con nombres.'];
        return [$entradas, null];
    }

    /**
     * ¿Es esta fila la cabecera? Devuelve `campo => letra de columna`, o `null`.
     * Exige al menos dos columnas reconocidas: con una sola, cualquier fila de datos
     * que llevara la palabra «correo» pasaría por cabecera.
     */
    private static function columnasDe(array $fila): ?array {
        $out = [];
        foreach ($fila as $col => $txt) {
            $t = self::plano($txt);
            if ($t === '') continue;
            foreach (self::CABECERAS as $campo => $pistas) {
                if (isset($out[$campo])) continue;
                foreach ($pistas as $p) {
                    if (str_contains($t, $p)) { $out[$campo] = $col; continue 3; }
                }
            }
        }
        return count($out) >= 2 ? $out : null;
    }

    /** Minúsculas y sin acentos, solo para reconocer cabeceras. */
    private static function plano(string $v): string {
        $v = mb_strtolower(trim($v), 'UTF-8');
        return strtr($v, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n']);
    }
}
