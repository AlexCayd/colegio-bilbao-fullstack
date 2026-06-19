<?php
namespace Model;

class Notificacion extends ActiveRecord {

    protected static $tabla      = 'notificaciones';
    protected static $columnasDB = [
        'id', 'usuario_id', 'tipo', 'referencia_id', 'referencia_tipo', 'mensaje', 'leida',
    ];

    public $id;
    public $usuario_id;
    public $tipo;
    public $referencia_id;
    public $referencia_tipo;
    public $mensaje;
    public $leida     = 0;
    public $creado_en;

    // ── Métodos estáticos ─────────────────────────────────────────────────────

    public static function nueva(
        int $usuarioId,
        string $tipo,
        string $mensaje,
        ?int $referenciaId,
        ?string $referenciaTipo
    ): void {
        $uid  = (int) $usuarioId;
        $t    = self::$db->escape_string($tipo);
        $msg  = self::$db->escape_string($mensaje);
        $rid  = $referenciaId ? (int) $referenciaId : 'NULL';
        $rt   = $referenciaTipo ? "'" . self::$db->escape_string($referenciaTipo) . "'" : 'NULL';
        self::$db->query(
            "INSERT INTO notificaciones (usuario_id, tipo, referencia_id, referencia_tipo, mensaje)
             VALUES ({$uid}, '{$t}', {$rid}, {$rt}, '{$msg}')"
        );
    }

    public static function noLeidasPorUsuario(int $usuarioId): int {
        $uid    = (int) $usuarioId;
        $result = self::$db->query(
            "SELECT COUNT(*) AS total FROM notificaciones WHERE usuario_id = {$uid} AND leida = 0"
        );
        $row = $result->fetch_assoc();
        return (int) ($row['total'] ?? 0);
    }

    public static function porUsuario(int $usuarioId, int $limite = 20): array {
        $uid   = (int) $usuarioId;
        $lim   = max(1, (int) $limite);
        $query = "SELECT * FROM notificaciones WHERE usuario_id = {$uid} ORDER BY creado_en DESC LIMIT {$lim}";
        return static::consultarSQL($query);
    }

    public static function marcarLeida(int $id, int $usuarioId): void {
        $id  = (int) $id;
        $uid = (int) $usuarioId;
        self::$db->query(
            "UPDATE notificaciones SET leida = 1 WHERE id = {$id} AND usuario_id = {$uid}"
        );
    }

    public static function marcarTodasLeidas(int $usuarioId): void {
        $uid = (int) $usuarioId;
        self::$db->query(
            "UPDATE notificaciones SET leida = 1 WHERE usuario_id = {$uid} AND leida = 0"
        );
    }
}
