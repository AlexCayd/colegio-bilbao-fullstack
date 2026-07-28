<?php
namespace Model;

/**
 * Notificaciones del panel. Las genera cualquier módulo (no solo Redacción) y se
 * consumen desde la campana del topbar y /dashboard/notificaciones.
 *
 * Al marcarlas leídas se BORRAN: no se archiva nada. La red de seguridad es el
 * "Deshacer" del cliente, que llama a `restaurar()` con la fila que devolvió el
 * borrado (ver src/js/admin/blog-notificaciones-index.js).
 */
class Notificacion extends ActiveRecord {

    protected static $tabla      = 'notificaciones';
    protected static $columnasDB = [
        'id', 'usuario_id', 'modulo', 'tipo', 'nivel',
        'referencia_id', 'referencia_tipo', 'enlace', 'mensaje', 'leida',
    ];

    public $id;
    public $usuario_id;
    public $modulo    = 'general';
    public $tipo;
    public $nivel     = 'info';
    public $referencia_id;
    public $referencia_tipo;
    public $enlace;
    public $mensaje;
    public $leida     = 0;
    public $creado_en;

    /** Módulos que pueden emitir notificaciones (fija el icono en la UI). */
    public const MODULOS = ['general', 'redaccion', 'suplencias', 'horarios', 'eventos', 'usuarios'];
    public const NIVELES = ['info', 'exito', 'aviso', 'error'];

    // ── Escritura ─────────────────────────────────────────────────────────────

    /**
     * Crea una notificación. Los parámetros nuevos van al final y con valor por
     * defecto para no romper a los llamadores editoriales previos.
     */
    public static function nueva(
        int $usuarioId,
        string $tipo,
        string $mensaje,
        ?int $referenciaId   = null,
        ?string $referenciaTipo = null,
        string $modulo       = 'general',
        string $nivel        = 'info',
        ?string $enlace      = null
    ): void {
        if (!in_array($modulo, self::MODULOS, true)) $modulo = 'general';
        if (!in_array($nivel,  self::NIVELES, true)) $nivel  = 'info';

        $uid = (int) $usuarioId;
        $t   = self::$db->escape_string($tipo);
        $msg = self::$db->escape_string(mb_substr($mensaje, 0, 255));
        $mod = self::$db->escape_string($modulo);
        $niv = self::$db->escape_string($nivel);
        $rid = $referenciaId ? (int) $referenciaId : 'NULL';
        $rt  = $referenciaTipo ? "'" . self::$db->escape_string($referenciaTipo) . "'" : 'NULL';
        $enl = $enlace ? "'" . self::$db->escape_string(mb_substr($enlace, 0, 255)) . "'" : 'NULL';

        self::$db->query(
            "INSERT INTO notificaciones
                (usuario_id, modulo, tipo, nivel, referencia_id, referencia_tipo, enlace, mensaje)
             VALUES ({$uid}, '{$mod}', '{$t}', '{$niv}', {$rid}, {$rt}, {$enl}, '{$msg}')"
        );
    }

    /**
     * Reinserta una notificación borrada por error ("Deshacer").
     * El `usuario_id` lo impone el llamador desde la sesión, nunca el payload.
     */
    public static function restaurar(int $usuarioId, array $datos): void {
        self::nueva(
            $usuarioId,
            (string)($datos['tipo'] ?? 'restaurada'),
            (string)($datos['mensaje'] ?? ''),
            isset($datos['referencia_id']) && $datos['referencia_id'] !== '' ? (int)$datos['referencia_id'] : null,
            !empty($datos['referencia_tipo']) ? (string)$datos['referencia_tipo'] : null,
            (string)($datos['modulo'] ?? 'general'),
            (string)($datos['nivel']  ?? 'info'),
            !empty($datos['enlace']) ? (string)$datos['enlace'] : null
        );
    }

    // ── Lectura ───────────────────────────────────────────────────────────────

    public static function noLeidasPorUsuario(int $usuarioId): int {
        $uid    = (int) $usuarioId;
        $result = self::$db->query(
            "SELECT COUNT(*) AS total FROM notificaciones WHERE usuario_id = {$uid} AND leida = 0"
        );
        $row = $result ? $result->fetch_assoc() : null;
        return (int) ($row['total'] ?? 0);
    }

    public static function porUsuario(int $usuarioId, int $limite = 20): array {
        $uid = (int) $usuarioId;
        $lim = max(1, (int) $limite);
        return static::consultarSQL(
            "SELECT * FROM notificaciones WHERE usuario_id = {$uid} ORDER BY creado_en DESC LIMIT {$lim}"
        );
    }

    /** Solo las pendientes — alimenta el desplegable de la campana. */
    public static function noLeidas(int $usuarioId, int $limite = 6): array {
        $uid = (int) $usuarioId;
        $lim = max(1, (int) $limite);
        return static::consultarSQL(
            "SELECT * FROM notificaciones
             WHERE usuario_id = {$uid} AND leida = 0
             ORDER BY creado_en DESC LIMIT {$lim}"
        );
    }

    /** Una notificación concreta, siempre acotada a su dueño. */
    public static function delUsuario(int $id, int $usuarioId): ?self {
        $id  = (int) $id;
        $uid = (int) $usuarioId;
        $r   = static::consultarSQL(
            "SELECT * FROM notificaciones WHERE id = {$id} AND usuario_id = {$uid} LIMIT 1"
        );
        return $r[0] ?? null;
    }

    // ── Borrado ───────────────────────────────────────────────────────────────

    public static function marcarLeida(int $id, int $usuarioId): void {
        $id  = (int) $id;
        $uid = (int) $usuarioId;
        self::$db->query("UPDATE notificaciones SET leida = 1 WHERE id = {$id} AND usuario_id = {$uid}");
    }

    public static function marcarTodasLeidas(int $usuarioId): void {
        $uid = (int) $usuarioId;
        self::$db->query("UPDATE notificaciones SET leida = 1 WHERE usuario_id = {$uid} AND leida = 0");
    }

    /**
     * Borra una notificación propia. Devuelve true si existía.
     * No se llama `eliminar()` porque ActiveRecord ya define ese nombre como
     * método de instancia y PHP no permite redeclararlo como estático.
     */
    public static function borrarDeUsuario(int $id, int $usuarioId): bool {
        $id  = (int) $id;
        $uid = (int) $usuarioId;
        self::$db->query("DELETE FROM notificaciones WHERE id = {$id} AND usuario_id = {$uid} LIMIT 1");
        return self::$db->affected_rows > 0;
    }

    /** Vacía la bandeja del usuario. Devuelve cuántas borró. */
    public static function borrarTodasDeUsuario(int $usuarioId): int {
        $uid = (int) $usuarioId;
        self::$db->query("DELETE FROM notificaciones WHERE usuario_id = {$uid}");
        return max(0, self::$db->affected_rows);
    }
}
