<?php

namespace Model;

/**
 * Solicitud de restablecimiento de contraseña del panel.
 *
 * El panel **no manda correo**: `classes/Email.php` sirve al registro público del sitio y
 * arrastra remitente y textos de la plantilla original del proyecto. Así que "olvidé mi
 * contraseña" no genera un token por correo sino una SOLICITUD que un administrador
 * resuelve desde el panel, generando una contraseña temporal que comunica por su cuenta.
 *
 * ⚠️ `usuario_id` puede ser NULL y es deliberado: el formulario público responde **lo
 * mismo exista o no el correo**, para no convertirlo en un verificador de cuentas del
 * colegio. Una solicitud sin usuario es alguien que se equivocó de correo, y la cola la
 * muestra como tal para que el admin no pierda el tiempo buscándola.
 */
class SolicitudPassword extends ActiveRecord
{
    protected static $tabla = 'solicitudes_password';
    protected static $columnasDB = ['id', 'nombre', 'email', 'usuario_id', 'estado',
                                    'resuelto_por', 'resuelto_en', 'ip'];

    public $id;
    public $nombre;
    public $email;
    public $usuario_id;
    public $estado = 'pendiente';
    public $resuelto_por;
    public $resuelto_en;
    public $ip;
    public $creado_en;

    /** Aliases de JOIN. Sin declarar, crearObjeto() los descarta. */
    public $usuario_nombre;
    public $usuario_email;
    public $resolutor_nombre;

    public const ESTADOS = ['pendiente', 'resuelta', 'descartada'];

    public const ESTADO_LABEL = [
        'pendiente'  => 'Pendiente',
        'resuelta'   => 'Resuelta',
        'descartada' => 'Descartada',
    ];

    /** Cuántas solicitudes se admiten por correo y hora. Freno de abuso, no de seguridad. */
    public const MAX_POR_HORA = 3;

    public function validar(): array {
        static::$alertas = [];

        $this->nombre = trim((string)$this->nombre);
        $this->email  = trim((string)$this->email);

        if ($this->nombre === '') static::setAlerta('error', 'Escribe tu nombre completo');
        if ($this->email === '') {
            static::setAlerta('error', 'Escribe tu correo electrónico');
        } elseif (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            static::setAlerta('error', 'El correo electrónico no tiene un formato válido');
        }

        return static::$alertas;
    }

    /** Persistencia con NULL real en las FK y en la fecha de resolución. */
    public function guardar() {
        $db   = self::$db;
        $cols = ['nombre', 'email', 'usuario_id', 'estado', 'resuelto_por', 'resuelto_en', 'ip'];
        $sql  = [];
        foreach ($cols as $c) {
            $v = $this->$c;
            if ($v === null || $v === '' || (in_array($c, ['usuario_id', 'resuelto_por'], true) && (int)$v === 0)) {
                $sql[$c] = 'NULL';
            } else {
                $sql[$c] = "'" . $db->escape_string($v) . "'";
            }
        }
        if (!empty($this->id)) {
            $assign = [];
            foreach ($sql as $c => $v) $assign[] = "{$c} = {$v}";
            $ok = $db->query("UPDATE solicitudes_password SET " . implode(', ', $assign) . " WHERE id = " . (int)$this->id . " LIMIT 1");
            return ['resultado' => (bool)$ok, 'id' => (int)$this->id];
        }
        $ok = $db->query("INSERT INTO solicitudes_password (" . implode(', ', array_keys($sql)) . ") VALUES (" . implode(', ', array_values($sql)) . ")");
        $this->id = $db->insert_id;
        return ['resultado' => (bool)$ok, 'id' => (int)$this->id];
    }

    /**
     * ¿Este correo ha pedido ya demasiadas veces en la última hora?
     *
     * Es un freno al ruido —alguien pulsando el botón diez veces, o un script llenando la
     * cola—, no una medida de seguridad: el formulario no revela nada, así que no hay nada
     * que proteger más allá de la bandeja del administrador.
     */
    public static function demasiadasRecientes(string $email): bool {
        $e = self::$db->escape_string(trim($email));
        $r = self::$db->query(
            "SELECT COUNT(*) n FROM solicitudes_password
              WHERE email = '{$e}' AND creado_en >= DATE_SUB(NOW(), INTERVAL 1 HOUR)");
        return $r ? ((int)$r->fetch_assoc()['n'] >= self::MAX_POR_HORA) : false;
    }

    private static function selectBase(): string {
        return "SELECT sp.*,
                       u.nombre AS usuario_nombre, u.email AS usuario_email,
                       r.nombre AS resolutor_nombre
                  FROM solicitudes_password sp
             LEFT JOIN usuarios u ON u.id = sp.usuario_id
             LEFT JOIN usuarios r ON r.id = sp.resuelto_por";
    }

    /**
     * La cola. Las pendientes primero y, dentro de cada grupo, la más antigua arriba:
     * alguien que no puede entrar lleva esperando desde que la mandó.
     */
    public static function todas(): array {
        return static::consultarSQL(
            self::selectBase() .
            " ORDER BY sp.estado = 'pendiente' DESC,
                       CASE WHEN sp.estado = 'pendiente' THEN sp.creado_en END ASC,
                       sp.creado_en DESC");
    }

    public static function encontrar(int $id): ?self {
        $r = static::consultarSQL(self::selectBase() . ' WHERE sp.id = ' . (int)$id . ' LIMIT 1');
        return $r[0] ?? null;
    }

    public static function contarPendientes(): int {
        try {
            $r = self::$db->query("SELECT COUNT(*) n FROM solicitudes_password WHERE estado = 'pendiente'");
            return $r ? (int)$r->fetch_assoc()['n'] : 0;
        } catch (\Throwable $e) {
            return 0;   // base sin actualizar: el badge no puede tumbar el sidebar
        }
    }

    /** Cierra la solicitud, firmada: el histórico tiene que decir quién la atendió. */
    public static function resolver(int $id, int $adminId, string $estado = 'resuelta'): bool {
        $id = (int)$id; $adminId = (int)$adminId;
        if ($id <= 0 || !in_array($estado, ['resuelta', 'descartada'], true)) return false;
        return (bool)self::$db->query(
            "UPDATE solicitudes_password
                SET estado = '{$estado}', resuelto_por = {$adminId}, resuelto_en = NOW()
              WHERE id = {$id} LIMIT 1");
    }

    /**
     * Contraseña temporal legible: se comunica por teléfono o en persona, así que evita
     * los caracteres que se confunden al dictar (O/0, l/1/I) y va en bloques.
     * Cumple las tres reglas del panel: 8+, una mayúscula y un número.
     */
    public static function generarTemporal(): string {
        $may = 'ABCDEFGHJKMNPQRSTUVWXYZ';   // sin I ni L ni O
        $min = 'abcdefghijkmnpqrstuvwxyz';  // sin l ni o
        $num = '23456789';                  // sin 0 ni 1

        $out = $may[random_int(0, strlen($may) - 1)];
        for ($i = 0; $i < 5; $i++) $out .= $min[random_int(0, strlen($min) - 1)];
        $out .= '-';
        for ($i = 0; $i < 3; $i++) $out .= $num[random_int(0, strlen($num) - 1)];
        return $out;
    }
}
