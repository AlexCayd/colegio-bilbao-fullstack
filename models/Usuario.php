<?php

namespace Model;

/**
 * Cuenta del REGISTRO PÚBLICO del sitio (familias y visitantes).
 *
 * ⚠️ NO ES EL MODELO DEL PANEL. La intranet de colaboradores usa UsuarioBlog, que mapea
 * la MISMA tabla `usuarios` pero otras columnas: rol, tipo_personal, niveles, modulos,
 * puede_suplir… Este modelo solo conoce las de la plantilla original (apellido,
 * confirmado, token, admin), varias de las cuales **ya no existen en el esquema actual**.
 *
 * ── CÓDIGO HEREDADO, PRÁCTICAMENTE SIN USO ───────────────────────────────────
 * Viene de la plantilla DevWebcamp y lo consume únicamente AuthController, el flujo
 * público /cuenta/login · /registro · /olvide · /reestablecer · /confirmar-cuenta, que el
 * colegio no ofrece hoy.
 *
 * Antes de reactivarlo hay que resolver, como mínimo:
 *   · `apellido`, `confirmado`, `token` y `admin` NO están en database.sql: el INSERT
 *     fallaría contra el esquema actual.
 *   · Dos modelos escribiendo la misma tabla con reglas distintas es un riesgo real —
 *     este exige 6 caracteres de contraseña y UsuarioBlog exige 8 con mayúscula y dígito.
 *   · El token de confirmación se genera con uniqid(), que es predecible por basarse en
 *     la hora. Para un token de seguridad corresponde random_bytes()/bin2hex().
 *   · Los métodos de validación devuelven self::$alertas SIN limpiarlo antes, así que los
 *     mensajes se acumulan entre llamadas de la misma petición.
 *
 * La decisión pendiente es si el registro público sigue vivo. Si no, este modelo y
 * AuthController pueden eliminarse enteros.
 *
 * @package Model
 */
class Usuario extends ActiveRecord {
    protected static $tabla = 'usuarios';

    /**
     * ⚠️ Referencia columnas que el esquema actual NO tiene (`apellido`, `confirmado`,
     * `token`, `admin`). Ver la nota de la clase.
     *
     * @var array<int, string>
     */
    protected static $columnasDB = ['id', 'nombre', 'apellido', 'email', 'password', 'confirmado', 'token', 'admin'];

    /** @var int|string|null */
    public $id;
    /** @var string Nombre de pila. */
    public $nombre;
    /** @var string ⚠️ Sin columna en el esquema actual. */
    public $apellido;
    /** @var string Correo: identificador de acceso. */
    public $email;
    /** @var string Hash bcrypt tras hashPassword(); en claro mientras se valida. */
    public $password;
    /** @var string Confirmación del formulario. NO se persiste: hay que unset() antes de guardar. */
    public $password2;
    /** @var int|string 1 = correo verificado. Sin ello login() rechaza el acceso. ⚠️ Sin columna hoy. */
    public $confirmado;
    /** @var string Token de un solo uso para confirmar la cuenta o restablecer. ⚠️ Sin columna hoy. */
    public $token;
    /** @var string|int Marca de administrador del sitio público. ⚠️ Sin columna hoy. */
    public $admin;

    /** @var string Contraseña vigente, en el cambio desde el perfil. No se persiste. */
    public $password_actual;
    /** @var string Contraseña nueva, en el cambio desde el perfil. No se persiste. */
    public $password_nuevo;


    /**
     * Hidrata el objeto desde un array (normalmente $_POST), con valores por defecto.
     *
     * ⚠️ Es el ÚNICO modelo del proyecto con constructor. Los demás se rellenan con
     * sincronizar(), que es la puerta que ofrece ActiveRecord. `new Horario([...])`
     * devuelve un objeto vacío justamente porque ese constructor no existe.
     *
     * @param array<string, mixed> $args
     */
    public function __construct($args = [])
    {
        $this->id = $args['id'] ?? null;
        $this->nombre = $args['nombre'] ?? '';
        $this->apellido = $args['apellido'] ?? '';
        $this->email = $args['email'] ?? '';
        $this->password = $args['password'] ?? '';
        $this->password2 = $args['password2'] ?? '';
        $this->confirmado = $args['confirmado'] ?? 0;
        $this->token = $args['token'] ?? '';
        $this->admin = $args['admin'] ?? '';
    }

    /**
     * Valida el formulario de inicio de sesión: que haya correo con formato y contraseña.
     *
     * No comprueba credenciales; de eso se encarga AuthController::login().
     *
     * @return array<string, array<int, string>> Alertas acumuladas.
     */
    public function validarLogin() {
        if(!$this->email) {
            self::$alertas['error'][] = 'El Email del Usuario es Obligatorio';
        }
        if(!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            self::$alertas['error'][] = 'Email no válido';
        }
        if(!$this->password) {
            self::$alertas['error'][] = 'El Password no puede ir vacio';
        }
        return self::$alertas;

    }

    /**
     * Valida el alta de una cuenta pública: nombre, apellido, correo y contraseña doble.
     *
     * ⚠️ Exige 6 caracteres sin más requisitos, frente a los 8 con mayúscula y dígito de
     * UsuarioBlog::validar(). Dos políticas de contraseña sobre la misma tabla.
     *
     * @return array<string, array<int, string>> Alertas acumuladas.
     */
    public function validar_cuenta() {
        if(!$this->nombre) {
            self::$alertas['error'][] = 'El Nombre es Obligatorio';
        }
        if(!$this->apellido) {
            self::$alertas['error'][] = 'El Apellido es Obligatorio';
        }
        if(!$this->email) {
            self::$alertas['error'][] = 'El Email es Obligatorio';
        }
        if(!$this->password) {
            self::$alertas['error'][] = 'El Password no puede ir vacio';
        }
        if(strlen($this->password) < 6) {
            self::$alertas['error'][] = 'El password debe contener al menos 6 caracteres';
        }
        if($this->password !== $this->password2) {
            self::$alertas['error'][] = 'Los password son diferentes';
        }
        return self::$alertas;
    }

    /**
     * Valida solo el correo. Lo usa el paso 1 de la recuperación de contraseña.
     *
     * @return array<string, array<int, string>> Alertas acumuladas.
     */
    public function validarEmail() {
        if(!$this->email) {
            self::$alertas['error'][] = 'El Email es Obligatorio';
        }
        if(!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            self::$alertas['error'][] = 'Email no válido';
        }
        return self::$alertas;
    }

    /**
     * Valida solo la contraseña. Lo usa el paso 2 de la recuperación.
     *
     * @return array<string, array<int, string>> Alertas acumuladas.
     */
    public function validarPassword() {
        if(!$this->password) {
            self::$alertas['error'][] = 'El Password no puede ir vacio';
        }
        if(strlen($this->password) < 6) {
            self::$alertas['error'][] = 'El password debe contener al menos 6 caracteres';
        }
        return self::$alertas;
    }

    /**
     * Valida el cambio de contraseña desde el perfil (actual + nueva).
     *
     * Comprueba solo el formato; que la actual sea correcta lo verifica
     * comprobar_password().
     *
     * @return array<string, array<int, string>> Alertas acumuladas.
     */
    public function nuevo_password() : array {
        if(!$this->password_actual) {
            self::$alertas['error'][] = 'El Password Actual no puede ir vacio';
        }
        if(!$this->password_nuevo) {
            self::$alertas['error'][] = 'El Password Nuevo no puede ir vacio';
        }
        if(strlen($this->password_nuevo) < 6) {
            self::$alertas['error'][] = 'El Password debe contener al menos 6 caracteres';
        }
        return self::$alertas;
    }

    /**
     * Contrasta $password_actual contra el hash almacenado en $password.
     *
     * @return bool true si coincide.
     */
    public function comprobar_password() : bool {
        return password_verify($this->password_actual, $this->password );
    }

    /**
     * Reemplaza $password por su hash bcrypt. Llamar SIEMPRE antes de guardar.
     *
     * @return void
     */
    public function hashPassword() : void {
        $this->password = password_hash($this->password, PASSWORD_BCRYPT);
    }

    /**
     * Genera el token de confirmación o de restablecimiento.
     *
     * ⚠️ uniqid() se basa en la hora del sistema y es PREDECIBLE: no es apto para un
     * token que da acceso a una cuenta. Si el registro público se reactiva, sustituirlo
     * por bin2hex(random_bytes(32)).
     *
     * @return void
     */
    public function crearToken() : void {
        $this->token = uniqid();
    }
}
