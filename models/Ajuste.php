<?php
namespace Model;

/**
 * Ajustes del sitio: clave → valor.
 *
 * Es la tabla de los interruptores que **no pertenecen a ninguna entidad**. El
 * primero (y hoy el único) es si el calendario del ciclo se puede descargar en PDF
 * desde Comunidad › Familias: no es una propiedad de un evento concreto sino del
 * sitio, así que no cabía en `eventos` ni en `usuarios`.
 *
 * No hereda el CRUD de ActiveRecord y es deliberado: la PK es `clave`, no un `id`
 * autoincremental, así que `guardar()`, `actualizar()` y `eliminar()` de la base no
 * sirven aquí. Solo se extiende para heredar la conexión `self::$db`.
 *
 * ⚠️ **Una fila ausente NO es un error.** `bool()` y `texto()` caen al valor por
 * defecto que se les pase, así que el panel sigue funcionando sobre una BD que
 * todavía no tenga la tabla creada (una instalación anterior a este cambio).
 *
 * @package Model
 */
class Ajuste extends ActiveRecord {

    /** ¿Se ofrece el PDF del calendario del ciclo en la web pública? */
    public const CALENDARIO_PDF = 'calendario_publico_pdf';

    /**
     * Cache de proceso. Cada vista pública lee el mismo ajuste desde varios sitios
     * (el botón, el guard del endpoint), y no tiene sentido una consulta por lectura.
     *
     * @var array<string, ?string>
     */
    private static $cache = [];

    /**
     * Valor crudo de un ajuste, o `$default` si no hay fila.
     *
     * Devuelve `$default` también si la consulta falla, que es el caso de una BD sin
     * la tabla `ajustes`: un panel que revienta porque falta un interruptor es peor
     * que uno que asume el valor por defecto.
     */
    public static function texto(string $clave, ?string $default = null): ?string {
        if (array_key_exists($clave, self::$cache)) return self::$cache[$clave] ?? $default;
        $c   = self::$db->escape_string($clave);
        $val = null;
        try {
            // ⚠️ try/catch y NO `@`: desde PHP 8.1 mysqli reporta por EXCEPCIÓN, y el
            // arroba no silencia una excepción. Con `@` a secas, una base sin la tabla
            // `ajustes` —cualquier instalación anterior a este cambio— tumbaba con un
            // fatal la portada pública entera, que es justo lo que esto evita.
            $res = self::$db->query("SELECT valor FROM ajustes WHERE clave = '{$c}' LIMIT 1");
            if ($res && $fila = $res->fetch_assoc()) $val = $fila['valor'];
        } catch (\Throwable $e) {
            $val = null;
        }
        self::$cache[$clave] = $val;
        return $val ?? $default;
    }

    /** El mismo valor leído como interruptor. Solo `'1'` es verdadero. */
    public static function bool(string $clave, bool $default = false): bool {
        $v = self::texto($clave);
        return $v === null ? $default : $v === '1';
    }

    /**
     * Escribe (o crea) un ajuste. `INSERT … ON DUPLICATE KEY UPDATE` porque la
     * clave puede no existir todavía: el alta y la edición son la misma operación.
     */
    public static function guardarValor(string $clave, ?string $valor): bool {
        $c = self::$db->escape_string($clave);
        $v = $valor === null ? 'NULL' : "'" . self::$db->escape_string($valor) . "'";
        try {
            $ok = (bool)self::$db->query(
                "INSERT INTO ajustes (clave, valor) VALUES ('{$c}', {$v})
                 ON DUPLICATE KEY UPDATE valor = VALUES(valor)");
        } catch (\Throwable $e) {
            // Sobre una base sin la tabla. Se devuelve false y NO se cachea: quien
            // llama avisa de que no se guardó. Un fallo de escritura callado dejaría
            // el interruptor volviendo solo a su sitio sin explicar por qué.
            return false;
        }
        if ($ok) self::$cache[$clave] = $valor;
        return $ok;
    }

    /** Atajo para los interruptores. */
    public static function guardarBool(string $clave, bool $valor): bool {
        return self::guardarValor($clave, $valor ? '1' : '0');
    }
}
