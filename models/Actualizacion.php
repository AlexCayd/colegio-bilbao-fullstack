<?php

namespace Model;

/**
 * Anuncios de novedades del panel, publicados por el desarrollador.
 *
 * Lo que distingue a esto de una notificación es que **bloquea**: mientras un usuario
 * tenga anuncios publicados sin acusar, el panel no le deja pasar a ninguna pantalla. Una
 * notificación se puede ignorar para siempre; un cambio de funcionamiento que nadie ha
 * leído genera tickets de soporte.
 *
 * Por eso hay DOS tablas: el anuncio es uno (`actualizaciones`) pero el acuse es por
 * persona (`actualizacion_vistas`, con PK compuesta, así que el POST de "Entendido" es
 * idempotente y un doble clic no duplica nada).
 *
 * ⚠️ El borrador NO bloquea a nadie ni sale en el historial. El disparador es **publicar**,
 * que es lo que sella `publicada_en`.
 */
class Actualizacion extends ActiveRecord
{
    protected static $tabla = 'actualizaciones';
    // `publicada_en` queda fuera a propósito: lo sella publicar(), no un formulario.
    protected static $columnasDB = ['id', 'version', 'titulo', 'cuerpo', 'imagen', 'estado', 'creado_por'];

    public $id;
    public $version;
    public $titulo;
    public $cuerpo;
    public $imagen;
    public $estado = 'borrador';
    public $publicada_en;
    public $creado_por;
    public $creado_en;

    /** Aliases de JOIN/agregado. Sin declarar, crearObjeto() los descarta. */
    public $autor_nombre;
    public $vistas;

    public const ESTADOS = ['borrador', 'publicada'];

    public const ESTADO_LABEL = [
        'borrador'  => 'Borrador',
        'publicada' => 'Publicada',
    ];

    public function validar(): array {
        static::$alertas = [];

        $this->titulo = trim((string)$this->titulo);
        $this->cuerpo = trim((string)$this->cuerpo);
        $this->version = trim((string)$this->version);

        if ($this->titulo === '') static::setAlerta('error', 'El título es obligatorio');
        if (mb_strlen($this->titulo) > 160) static::setAlerta('error', 'El título no puede pasar de 160 caracteres');
        if ($this->cuerpo === '') static::setAlerta('error', 'El cuerpo del anuncio es obligatorio');
        if (mb_strlen($this->version) > 20) static::setAlerta('error', 'La versión no puede pasar de 20 caracteres');
        if (!in_array($this->estado, self::ESTADOS, true)) $this->estado = 'borrador';

        return static::$alertas;
    }

    /** Persistencia con NULL real en version/imagen/creado_por. */
    public function guardar() {
        $db   = self::$db;
        $cols = ['version', 'titulo', 'cuerpo', 'imagen', 'estado', 'creado_por'];
        $sql  = [];
        foreach ($cols as $c) {
            $v = $this->$c;
            if ($v === null || $v === '' || ($c === 'creado_por' && (int)$v === 0)) {
                $sql[$c] = 'NULL';
            } else {
                $sql[$c] = "'" . $db->escape_string($v) . "'";
            }
        }
        if (!empty($this->id)) {
            $assign = [];
            foreach ($sql as $c => $v) $assign[] = "{$c} = {$v}";
            $ok = $db->query("UPDATE actualizaciones SET " . implode(', ', $assign) . " WHERE id = " . (int)$this->id . " LIMIT 1");
            return ['resultado' => (bool)$ok, 'id' => (int)$this->id];
        }
        $ok = $db->query("INSERT INTO actualizaciones (" . implode(', ', array_keys($sql)) . ") VALUES (" . implode(', ', array_values($sql)) . ")");
        $this->id = $db->insert_id;
        return ['resultado' => (bool)$ok, 'id' => (int)$this->id];
    }

    /**
     * Publica el anuncio y sella la fecha. A partir de aquí bloquea a todo el que no lo
     * haya acusado.
     *
     * ⚠️ Solo sella `publicada_en` la PRIMERA vez. Reeditar y volver a publicar no vuelve
     * a molestar a quien ya lo leyó —su acuse sigue en pie— pero tampoco falsea la fecha
     * del anuncio, que es lo que ordena el historial.
     */
    public static function publicar(int $id): bool {
        $id = (int)$id;
        if ($id <= 0) return false;
        return (bool)self::$db->query(
            "UPDATE actualizaciones
                SET estado = 'publicada',
                    publicada_en = COALESCE(publicada_en, NOW())
              WHERE id = {$id} LIMIT 1");
    }

    /** Vuelve una publicada a borrador: deja de bloquear inmediatamente. */
    public static function despublicar(int $id): bool {
        $id = (int)$id;
        if ($id <= 0) return false;
        return (bool)self::$db->query(
            "UPDATE actualizaciones SET estado = 'borrador' WHERE id = {$id} LIMIT 1");
    }

    private static function selectBase(): string {
        return "SELECT a.*, u.nombre AS autor_nombre,
                       (SELECT COUNT(*) FROM actualizacion_vistas av WHERE av.actualizacion_id = a.id) AS vistas
                  FROM actualizaciones a
             LEFT JOIN usuarios u ON u.id = a.creado_por";
    }

    /**
     * Anuncios que este usuario todavía no ha acusado. Es LA consulta del bloqueo, así que
     * corre en cada carga de pantalla del panel: va por `estado` (indexado) y una
     * subconsulta sobre la PK de `actualizacion_vistas`.
     *
     * Orden ascendente: si hay varios pendientes se leen en el orden en que ocurrieron.
     */
    public static function pendientesDe(int $usuarioId): array {
        $uid = (int)$usuarioId;
        if ($uid <= 0) return [];
        try {
            return static::consultarSQL(
                self::selectBase() .
                " WHERE a.estado = 'publicada'
                    AND NOT EXISTS (SELECT 1 FROM actualizacion_vistas av
                                     WHERE av.actualizacion_id = a.id AND av.usuario_id = {$uid})
                  ORDER BY a.publicada_en ASC, a.id ASC");
        } catch (\Throwable $e) {
            // Base sin actualizar todavía: mejor no bloquear a nadie que reventar el panel.
            return [];
        }
    }

    /** Historial: lo publicado, lo más reciente primero. Lo ve cualquiera con sesión. */
    public static function publicadas(): array {
        return static::consultarSQL(
            self::selectBase() . " WHERE a.estado = 'publicada'
                                    ORDER BY a.publicada_en DESC, a.id DESC");
    }

    /** Todo, incluidos los borradores. Solo para la pantalla de administración. */
    public static function todas(): array {
        return static::consultarSQL(
            self::selectBase() . " ORDER BY a.estado = 'publicada' ASC, COALESCE(a.publicada_en, a.creado_en) DESC, a.id DESC");
    }

    public static function encontrar(int $id): ?self {
        $r = static::consultarSQL(self::selectBase() . ' WHERE a.id = ' . (int)$id . ' LIMIT 1');
        return $r[0] ?? null;
    }

    /**
     * Acusa recibo. `INSERT IGNORE` y no un `SELECT` previo: la PK compuesta ya garantiza
     * la unicidad, así que esto es idempotente por construcción y no hay ventana entre
     * comprobar e insertar.
     */
    public static function marcarVista(int $actualizacionId, int $usuarioId): bool {
        $a = (int)$actualizacionId; $u = (int)$usuarioId;
        if ($a <= 0 || $u <= 0) return false;
        return (bool)self::$db->query(
            "INSERT IGNORE INTO actualizacion_vistas (actualizacion_id, usuario_id) VALUES ({$a}, {$u})");
    }

    /** Cuántas personas han acusado, para la pantalla de administración. */
    public static function totalUsuarios(): int {
        $r = self::$db->query("SELECT COUNT(*) n FROM usuarios");
        return $r ? (int)$r->fetch_assoc()['n'] : 0;
    }
}
