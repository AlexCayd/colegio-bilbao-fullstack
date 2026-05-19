<?php
namespace Model;

class UsuarioBlog extends ActiveRecord {

    protected static $tabla      = 'usuarios';
    protected static $columnasDB = ['id', 'nombre', 'email', 'password', 'rol', 'avatar'];

    public $id;
    public $nombre;
    public $email;
    public $password;
    public $password2;       // confirmación — no va a BD
    public $rol;
    public $avatar;
    public $ultimo_acceso;   // read-only desde BD (DEFAULT CURRENT_TIMESTAMP)
    public $creado_en;       // read-only desde BD
    public $total_articulos = 0; // calculado por JOIN en allConArticulos()

    public function validar(): array {
        static::$alertas = [];

        $this->nombre = trim($this->nombre ?? '');
        $this->email  = trim($this->email  ?? '');

        // Nombre
        if (!$this->nombre) {
            static::setAlerta('error', 'El nombre completo es obligatorio');
        }

        // Email
        if (!trim($this->email ?? '')) {
            static::setAlerta('error', 'El correo electrónico es obligatorio');
        } elseif (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            static::setAlerta('error', 'El correo electrónico no tiene un formato válido');
        }

        // Password
        if (!trim($this->password ?? '')) {
            static::setAlerta('error', 'La contraseña es obligatoria');
        } elseif (strlen($this->password) < 8) {
            static::setAlerta('error', 'La contraseña debe tener mínimo 8 caracteres');
        } elseif (!preg_match('/[A-Z]/', $this->password)) {
            static::setAlerta('error', 'La contraseña debe incluir al menos una mayúscula');
        } elseif (!preg_match('/[0-9]/', $this->password)) {
            static::setAlerta('error', 'La contraseña debe incluir al menos un número');
        }

        // Confirmación
        if (($this->password ?? '') !== ($this->password2 ?? '')) {
            static::setAlerta('error', 'Las contraseñas no coinciden');
        }

        // Rol
        if (!\in_array($this->rol ?? '', ['administrador', 'editor'])) {
            static::setAlerta('error', 'Selecciona un rol válido');
        }

        return static::$alertas;
    }

    public function hashPassword(): void {
        $this->password = password_hash($this->password, PASSWORD_BCRYPT);
    }

    public function existeEmail(): bool {
        $email    = self::$db->escape_string($this->email);
        $excluirId = (int) ($this->id ?? 0);
        $query    = "SELECT id FROM " . static::$tabla
                  . " WHERE email = '{$email}'"
                  . ($excluirId ? " AND id != {$excluirId}" : '')
                  . " LIMIT 1";
        $resultado = self::$db->query($query);
        return $resultado->num_rows > 0;
    }

    /** Devuelve todos los usuarios con el conteo de artículos asociados. */
    public static function allConArticulos(): array {
        $query = "
            SELECT u.*, COUNT(a.id) AS total_articulos
            FROM   usuarios u
            LEFT JOIN articulos a ON a.autor_id = u.id
            GROUP BY u.id
            ORDER BY u.id ASC
        ";
        return static::consultarSQL($query);
    }

    /** Devuelve un usuario por id con su conteo de artículos. */
    public static function findConArticulos(int $id): ?self {
        $query = "
            SELECT u.*, COUNT(a.id) AS total_articulos
            FROM   usuarios u
            LEFT JOIN articulos a ON a.autor_id = u.id
            WHERE  u.id = {$id}
            GROUP BY u.id
            LIMIT 1
        ";
        $resultado = static::consultarSQL($query);
        return $resultado[0] ?? null;
    }

    public static function contarTotal(): int {
        $r = self::$db->query("SELECT COUNT(*) AS total FROM " . static::$tabla);
        return ($r) ? (int) $r->fetch_assoc()['total'] : 0;
    }

    public static function findByEmail(string $email): ?self {
        $emailSafe = self::$db->escape_string(trim($email));
        $query = "SELECT * FROM " . static::$tabla . " WHERE email = '{$emailSafe}' LIMIT 1";
        $resultado = static::consultarSQL($query);
        return $resultado[0] ?? null;
    }

    public static function registrarAcceso(int $id): void {
        self::$db->query("UPDATE " . static::$tabla . " SET ultimo_acceso = NOW() WHERE id = {$id} LIMIT 1");
    }

    /** Validación para edición: igual que validar() pero sin exigir contraseña. */
    public function validarEdicion(): array {
        static::$alertas = [];

        $this->nombre = trim($this->nombre ?? '');
        $this->email  = trim($this->email  ?? '');

        if (!$this->nombre) {
            static::setAlerta('error', 'El nombre completo es obligatorio');
        }

        if (!$this->email) {
            static::setAlerta('error', 'El correo electrónico es obligatorio');
        } elseif (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            static::setAlerta('error', 'El correo electrónico no tiene un formato válido');
        }

        if (!\in_array($this->rol ?? '', ['administrador', 'editor'])) {
            static::setAlerta('error', 'Selecciona un rol válido');
        }

        return static::$alertas;
    }
}
