<?php
namespace Model;

/**
 * Lugar donde se hace una guardia de receso (patio, comedor, pasillos…).
 *
 * Catálogo pequeño y de crecimiento lento, así que no tiene módulo propio: se
 * administra desde el propio editor de horario, en el momento en que hace falta —
 * obligar a salir a otra pantalla para dar de alta «Pasillo de Kinder» y volver
 * sería el camino largo para un dato de dos palabras.
 */
class LugarGuardia extends ActiveRecord {

    protected static $tabla      = 'lugares_guardia';
    protected static $columnasDB = ['id', 'nombre'];

    public $id;
    public $nombre;

    public function validar(): array {
        static::$alertas = [];
        $this->nombre = trim(preg_replace('/\s+/', ' ', (string)($this->nombre ?? '')));
        if ($this->nombre === '')            static::setAlerta('error', 'El lugar necesita un nombre');
        if (mb_strlen($this->nombre) > 80)   static::setAlerta('error', 'El nombre es demasiado largo');
        return static::$alertas;
    }

    public static function todos(): array {
        return static::consultarSQL("SELECT * FROM lugares_guardia ORDER BY nombre ASC");
    }

    /** Busca por nombre normalizado, para no duplicar «Patio» y «patio ». */
    public static function porNombre(string $nombre): ?self {
        $n = self::$db->escape_string(trim(preg_replace('/\s+/', ' ', $nombre)));
        $r = static::consultarSQL("SELECT * FROM lugares_guardia WHERE nombre = '{$n}' LIMIT 1");
        return $r[0] ?? null;
    }

    /** Cuántas guardias lo usan. Evita borrar un lugar con horario colgando. */
    public static function usos(int $id): int {
        $id = (int)$id;
        $r = self::$db->query(
            "SELECT (SELECT COUNT(*) FROM horarios WHERE lugar_id = {$id})
                  + (SELECT COUNT(*) FROM suplencia_horas WHERE lugar_id = {$id}) AS n");
        return $r ? (int)$r->fetch_assoc()['n'] : 0;
    }
}
