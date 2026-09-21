<?php

namespace Classes;

/**
 * Plomería compartida de los PDF del sitio.
 *
 * Existen dos y no tienen nada que ver entre sí —el horario de un profesor (panel) y
 * el calendario del ciclo (web pública)—, pero el arranque de Dompdf sí es el mismo:
 * la tipografía Outfit desde disco, el `chroot` en la raíz, la caché de fuentes y el
 * `stream()` final. Estaba escrito una vez dentro de BlogController como métodos
 * privados; al aparecer el segundo PDF, la alternativa era copiarlo a
 * EstaticasController, que es justo la clase de duplicado que acaba divergiendo.
 *
 * Esta clase **no sabe qué se imprime**: recibe el HTML ya renderizado. Quién puede
 * verlo y con qué datos se decide en el controlador que llama, que es donde vive el
 * guard.
 *
 * @package Classes
 */
class Pdf {

    /** Pesos de Outfit que se versionan en src/fonts/. */
    private const PESOS = [400 => 'normal', 600 => 'normal', 700 => 'bold', 800 => 'bold'];

    /**
     * Font Awesome Solid, para los iconos de evento del calendario impreso.
     *
     * El sitio carga Font Awesome desde cdnjs, pero Dompdf corre con
     * `isRemoteEnabled = false` y tampoco entiende `::before`, así que la única forma
     * de imprimir un icono es escribir su carácter con esta familia aplicada — y para
     * eso el TTF tiene que estar en disco. Es la misma razón por la que están ahí los
     * Outfit. (Font Awesome Free: fuentes bajo SIL OFL 1.1.)
     */
    private const FA_TTF = 'fa-solid-900.ttf';

    /**
     * Declaraciones `@font-face` de Outfit, la tipografía del panel, para que lo
     * impreso se lea como parte del mismo producto.
     *
     * Se arma en PHP y no en el SCSS porque necesita rutas **absolutas del disco de
     * este servidor**, que un CSS compilado no puede conocer. Dompdf exige el TTF en
     * disco: la hoja de Google Fonts no le sirve.
     *
     * Si faltan los archivos devuelve '' y Dompdf cae a su fuente por defecto: el PDF
     * sale con otra tipografía, pero sale.
     */
    public static function fuenteCss(): string {
        $dir = realpath(__DIR__ . '/../src/fonts');
        if ($dir === false) return '';
        $css = '';
        foreach (array_keys(self::PESOS) as $peso) {
            $ttf = $dir . DIRECTORY_SEPARATOR . "Outfit-{$peso}.ttf";
            if (!is_file($ttf)) continue;
            $css .= "@font-face{font-family:'Outfit';font-style:normal;font-weight:{$peso};"
                  . "src:url('" . str_replace('\\', '/', $ttf) . "') format('truetype');}\n";
        }
        // Font Awesome es opcional igual que Outfit: si falta el archivo, el documento
        // sale sin iconos en vez de no salir.
        $fa = $dir . DIRECTORY_SEPARATOR . self::FA_TTF;
        if (is_file($fa)) {
            // ⚠️ Se registra con `font-weight: normal` aunque el archivo sea el «900»
            // de Font Awesome. Dompdf casa las `@font-face` por familia + peso + estilo
            // y NO cae a otro peso: declarándola como 900, una celda que hereda el peso
            // normal del body no encontraba la cara, Dompdf no incrustaba la fuente y
            // los iconos salían como cajas. Solo hay un archivo, así que no hay peso
            // que distinguir.
            $css .= "@font-face{font-family:'FontAwesome';font-style:normal;font-weight:normal;"
                  . "src:url('" . str_replace('\\', '/', $fa) . "') format('truetype');}\n";
        }
        return $css;
    }

    /** ¿Está el TTF de Font Awesome en disco? La plantilla decide si pinta iconos. */
    public static function hayIconos(): bool {
        $dir = realpath(__DIR__ . '/../src/fonts');
        return $dir !== false && is_file($dir . DIRECTORY_SEPARATOR . self::FA_TTF);
    }

    /**
     * Una hoja compilada de `public/build/css/`, precedida de las `@font-face`.
     * Dompdf no resuelve URLs del sitio, así que el CSS se inyecta en el HTML.
     */
    public static function hojaCss(string $nombre): string {
        $css = @file_get_contents(__DIR__ . '/../public/build/css/' . $nombre) ?: '';
        return self::fuenteCss() . $css;
    }

    /**
     * Un archivo local como data: URI. Dompdf con rutas relativas es frágil.
     *
     * ⚠️ Devuelve '' si falta la extensión **GD**: Dompdf la necesita para incrustar
     * un PNG y sin ella lanza una excepción que se lleva por delante el PDF entero.
     * Las plantillas tratan el logo como opcional, así que sin GD el documento sale
     * sin él en vez de no salir. (Habilitar `extension=gd` en php.ini lo devuelve;
     * `intervention/image` —avatares y optimización de subidas— también la necesita.)
     */
    public static function dataUri(string $ruta): string {
        if (!is_file($ruta) || !extension_loaded('gd')) return '';
        $ext  = strtolower(pathinfo($ruta, PATHINFO_EXTENSION));
        $mime = ['png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg'][$ext] ?? 'image/png';
        return 'data:' . $mime . ';base64,' . base64_encode((string)file_get_contents($ruta));
    }

    /** El logo institucional, listo para incrustar. '' si no se puede (ver dataUri()). */
    public static function logo(): string {
        return self::dataUri(__DIR__ . '/../public/build/assets/img/global/logo-bilbao-horizontal-azul.png');
    }

    /**
     * Renderiza el HTML y lo manda al navegador como descarga. No vuelve: termina
     * el proceso, igual que cualquier `stream()` de Dompdf.
     *
     * @param string $html         documento completo, con su CSS ya embebido
     * @param string $archivo      nombre del PDF descargado
     * @param string $orientacion  'landscape' | 'portrait'
     */
    public static function emitir(string $html, string $archivo, string $orientacion = 'landscape'): void {
        $opciones = new \Dompdf\Options();
        $opciones->set('isRemoteEnabled', false);   // las imágenes van embebidas; nada sale a la red
        $opciones->set('defaultFont', 'Outfit');
        // `chroot` es lo que permite a Dompdf leer src/fonts/ desde un @font-face.
        $opciones->set('fontDir', __DIR__ . '/../storage/fuentes-pdf');
        $opciones->set('fontCache', __DIR__ . '/../storage/fuentes-pdf');
        $opciones->set('chroot', [realpath(__DIR__ . '/..')]);
        if (!is_dir($opciones->get('fontDir'))) @mkdir($opciones->get('fontDir'), 0755, true);

        $dompdf = new \Dompdf\Dompdf($opciones);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', $orientacion);
        $dompdf->render();
        $dompdf->stream($archivo, ['Attachment' => true]);
        exit;
    }

    /**
     * Texto a slug para un nombre de archivo.
     *
     * ⚠️ `strtr($s, 'áé…', 'ae…')` NO vale: con dos cadenas opera **byte a byte**, y
     * en UTF-8 un acento ocupa dos, así que «Adrián» salía como `adriuen`. La forma
     * de array —la misma que usa `BlogController::claveCatalogo()`— sustituye
     * cadenas completas.
     */
    public static function slug(string $texto): string {
        $plano = strtr($texto, [
            'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n',
            'Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U','Ü'=>'U','Ñ'=>'N',
        ]);
        return trim(strtolower(preg_replace('/[^a-z0-9]+/i', '-', $plano)), '-');
    }
}
