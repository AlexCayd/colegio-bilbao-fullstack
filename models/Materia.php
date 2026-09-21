<?php
namespace Model;

class Materia extends ActiveRecord {
    protected static $tabla      = 'materias';
    /**
     * ⚠️ `activo` NO entra aquí a propósito: es una columna con DEFAULT 1 que solo
     * escribe cambiarActivo(). Ver la nota de database.sql.
     */
    protected static $columnasDB = ['id', 'nombre', 'nivel'];

    public $id;
    public $nombre;
    public $nivel;
    /** Baja lógica: 0 = el archivo ya no la menciona. Ver database.sql. */
    public $activo;

    /** Niveles académicos en orden. Compartido con Grupo. */
    public const NIVELES = ['Maternal', 'Kinder', 'Primaria', 'Secundaria', 'Bachillerato'];

    /**
     * Color de cada nivel, de la paleta institucional. **Fuente única**: lo pintan
     * las tabs de Grupos (listado y formulario) y los chips de nivel del formulario
     * de eventos, y estuvo copiado a mano en cada vista.
     *
     * Es un recorrido cromático por el orden académico (naranja → índigo), no cinco
     * colores sueltos: así el nivel se reconoce por el tono en cualquier pantalla.
     *
     * ⚠️ Son colores **de identificación**, no de tinta: tres de los cinco (ámbar,
     * lima y turquesa) no llegan a AA como texto, así que quien los use sobre texto
     * debe oscurecerlos igual que hace `--pal-tinta` en el resto del panel.
     */
    public const NIVEL_COLOR = [
        'Maternal'     => '#fc6722',
        'Kinder'       => '#f5b400',
        'Primaria'     => '#8ac926',
        'Secundaria'   => '#46bdc6',
        'Bachillerato' => '#4267ac',
    ];

    /** El color de un nivel, con gris de respaldo si llega uno desconocido. */
    public static function colorNivel(?string $nivel): string {
        return self::NIVEL_COLOR[$nivel] ?? '#94a3b8';
    }

    /** Expresión SQL para ordenar por nivel académico (no alfabético). */
    public static function ordenNivel(string $col = 'nivel'): string {
        $lista = implode(',', array_map(fn($n) => "'" . $n . "'", self::NIVELES));
        return "FIELD({$col}, {$lista})";
    }

    /**
     * ⚠️ Por defecto SOLO las activas. El importador, que necesita reconocer las
     * inactivas para reactivarlas cuando el archivo vuelve a nombrarlas, pasa `true`.
     */
    public static function todas(bool $incluirInactivas = false): array {
        $filtro = $incluirInactivas ? '' : 'WHERE activo = 1 ';
        return static::consultarSQL("SELECT * FROM materias {$filtro}ORDER BY " . self::ordenNivel() . ", nombre ASC");
    }

    /** Agrupadas por nivel para pintar <optgroup>: ['Kinder' => Materia[], …]. */
    public static function porNivel(bool $incluirInactivas = false): array {
        $out = [];
        foreach (self::todas($incluirInactivas) as $m) {
            $out[$m->nivel][] = $m;
        }
        return $out;
    }

    /**
     * Da de baja (o vuelve a dar de alta) estas materias. Ver Aula::cambiarActivo().
     *
     * ⚠️ Materias es el único de los tres catálogos SIN pantalla propia en el panel,
     * así que su único interruptor es el archivo: volver a mencionarla en un CSV
     * posterior la reactiva. Por eso importa que la previa las liste por su nombre.
     *
     * @return int filas realmente cambiadas.
     */
    public static function cambiarActivo(array $ids, bool $activo): int {
        return self::marcarActivo($ids, $activo);
    }
}
