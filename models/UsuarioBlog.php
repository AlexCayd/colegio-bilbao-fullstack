<?php
namespace Model;

class UsuarioBlog extends ActiveRecord {

    protected static $tabla      = 'usuarios';
    // fecha_nacimiento, rol_redaccion, tipo_personal y niveles se persisten aparte
    // (soporte de NULL real: el ORM base envuelve todo en comillas y escribiría '').
    protected static $columnasDB = ['id', 'nombre', 'email', 'password', 'rol', 'puede_suplir', 'modulos', 'avatar'];

    public $id;
    public $nombre;
    public $email;
    public $password;
    public $password2;       // confirmación — no va a BD
    public $rol;
    public $rol_redaccion;   // 'revisor'|'editor'|null — solo con módulo redaccion
    public $tipo_personal;   // SET: 'profesor','prefecto','administrativo' (CSV, combinable)
    // SET (CSV). En un 'profesor' = niveles que IMPARTE; en un 'directivo' = niveles
    // que GESTIONA (vacío = todo el colegio). En el resto de puestos va NULL.
    public $niveles;
    public $puede_suplir = 1;// 0 = no puede suplir a otros profesores
    public $modulos;         // CSV de módulos para rol 'usuario' (admin = todos)
    public $fecha_nacimiento;// DATE — para el calendario de cumpleaños
    public $avatar;
    public $ultimo_acceso;   // read-only desde BD (DEFAULT CURRENT_TIMESTAMP)
    public $creado_en;       // read-only desde BD
    public $total_articulos  = 0;

    // Aliases calculados por SQL (MONTH()/DAY()) en las consultas de cumpleaños
    public $mes;
    public $dia;

    // Conteos calculados por JOIN en conArticulosYNoticias()
    public $art_publicados   = 0;
    public $art_borradores   = 0;
    public $art_programados  = 0;
    public $not_publicadas   = 0;
    public $not_borradores   = 0;
    public $not_programadas  = 0;

    public function validar(): array {
        static::$alertas = [];

        $this->validarIdentidad();

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

        $this->validarRolYPermisos();

        return static::$alertas;
    }

    /** Nombre y correo: común a alta y edición. */
    private function validarIdentidad(): void {
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
    }

    /**
     * Rol, módulos, sub-rol editorial, tipo de personal y fecha de nacimiento.
     * Común a alta y edición: antes estaba duplicado en validar() y validarEdicion(),
     * y las dos copias ya habían empezado a divergir.
     */
    private function validarRolYPermisos(): void {
        if (!\in_array($this->rol ?? '', self::ROLES, true)) {
            static::setAlerta('error', 'Selecciona un rol válido');
        }

        // Módulos (obligatorios si es 'usuario')
        $this->normalizarModulos();
        if (($this->rol ?? '') === 'usuario' && empty($this->modulos)) {
            static::setAlerta('error', 'Selecciona al menos un módulo para el usuario');
        }

        // Rol de redacción (revisor/editor) obligatorio si tiene el módulo redaccion
        $this->validarRolRedaccion();

        // Tipo de personal (SET combinable) y, si es docente, sus niveles
        $this->normalizarTipoPersonal();
        $this->normalizarNiveles();

        // Fecha de nacimiento (opcional, pero si viene debe ser válida)
        $this->validarFechaNacimiento();
    }

    /** Roles del panel. `administrador` accede a todo; `usuario` solo a su CSV `modulos`. */
    public const ROLES = ['administrador', 'usuario'];

    /** Lista blanca de módulos asignables a un rol 'usuario'. */
    public const MODULOS_ASIGNABLES = [
        'usuarios', 'profesores', 'prefectura', 'administrativos', 'directivos',
        'eventos', 'horarios', 'aulas', 'grupos', 'suplencias', 'swaps',
        'redaccion', 'soporte',
    ];

    /**
     * Módulos que se preseleccionan al marcar un tipo de personal.
     *
     * Es una **sugerencia**, no una regla: el formulario los marca para no partir de
     * cero y el admin quita o añade lo que quiera. Salen de lo que el claustro real ya
     * tiene asignado en el seed, así que dar de alta a alguien «como los demás» deja
     * de ser un ejercicio de memoria.
     *
     * `soporte` no aparece en ninguna: es transversal (`MODULOS_TRANSVERSALES`) y lo
     * tiene todo el mundo, así que marcarlo sería ruido.
     */
    public const MODULOS_SUGERIDOS = [
        'profesor'       => ['suplencias', 'horarios', 'swaps'],
        'administrativo' => ['eventos'],
        'prefecto'       => ['suplencias', 'horarios', 'swaps', 'profesores'],
        // Dirección gobierna: ve todo el panel, acotado por `niveles` a su nivel.
        'directivo'      => ['suplencias', 'horarios', 'swaps', 'usuarios', 'profesores',
                             'prefectura', 'administrativos', 'directivos', 'aulas',
                             'grupos', 'eventos'],
    ];

    /** Tipos de personal que NO se combinan con ningún otro, en orden de prioridad. */
    public const TIPOS_EXCLUYENTES = ['directivo', 'prefecto'];

    /** Puestos de coordinación: ven datos de terceros (horarios, motivos, justificantes). */
    public const TIPOS_COORDINAN = ['prefecto', 'directivo'];

    public const TIPO_LABEL = [
        'profesor'       => 'Profesor',
        'prefecto'       => 'Prefecto',
        'administrativo' => 'Administrativo',
        'directivo'      => 'Directivo',
    ];

    /** Normaliza $modulos: el admin no guarda módulos; usuario guarda CSV limpio. */
    private function normalizarModulos(): void {
        if (($this->rol ?? '') === 'administrador') {
            $this->modulos = null;
            return;
        }
        $raw = $this->modulos;
        if (\is_array($raw)) {
            $lista = $raw;
        } else {
            $lista = array_filter(array_map('trim', explode(',', (string) $raw)));
        }
        $lista = array_values(array_intersect(self::MODULOS_ASIGNABLES, $lista));
        $this->modulos = $lista ? implode(',', $lista) : null;
    }

    /** El rol de redacción solo aplica si el usuario tiene el módulo 'redaccion'. */
    private function validarRolRedaccion(): void {
        // El admin actúa siempre como revisor implícito; no requiere el campo.
        if (($this->rol ?? '') === 'administrador') {
            $this->rol_redaccion = null;
            return;
        }
        $tieneRedaccion = \in_array('redaccion', array_filter(array_map('trim', explode(',', (string) $this->modulos))), true);
        if (!$tieneRedaccion) { $this->rol_redaccion = null; return; }
        if (!\in_array($this->rol_redaccion ?? '', ['revisor', 'editor'], true)) {
            static::setAlerta('error', 'Elige si el usuario será revisor o editor en Redacción');
            $this->rol_redaccion = null;
        }
    }

    /**
     * Normaliza tipo_personal (SET) contra la lista permitida; vacío = null.
     *
     * `prefecto` es EXCLUYENTE: prefectura coordina las suplencias, no las cubre,
     * así que combinarlo con `profesor` deja al mismo usuario a ambos lados del
     * flujo. Si viene marcado, gana él y se descarta el resto. `profesor` +
     * `administrativo` sí es una combinación válida.
     *
     * Es la única puerta de entrada (la llaman validar() y validarEdicion()), así
     * que aquí queda cubierto tanto el formulario como cualquier POST manipulado.
     */
    private function normalizarTipoPersonal(): void {
        // El orden manda: array_intersect conserva el del primer array, así que
        // este literal decide en qué orden se guarda el CSV y, por tanto, cómo
        // salen los chips en los listados. Va alineado con el orden del
        // formulario (views/blog/usuarios/_permisos-fields.php).
        $permitidos = ['administrativo', 'profesor', 'prefecto', 'directivo'];
        $raw = $this->tipo_personal;
        if (\is_array($raw)) {
            $lista = $raw;
        } else {
            $lista = array_filter(array_map('trim', explode(',', (string) $raw)));
        }
        $lista = array_values(array_intersect($permitidos, $lista));
        // `prefecto` y `directivo` son excluyentes: son puestos de coordinación, no se
        // acumulan con la docencia ni entre sí. Si vienen ambos gana `directivo`, que
        // es el de mayor alcance.
        foreach (self::TIPOS_EXCLUYENTES as $t) {
            if (in_array($t, $lista, true)) { $lista = [$t]; break; }
        }
        $this->tipo_personal = $lista ? implode(',', $lista) : null;
        // puede_suplir normalizado a 0/1
        $this->puede_suplir = !empty($this->puede_suplir) ? 1 : 0;
    }

    /**
     * Normaliza `niveles` (SET) contra Materia::NIVELES; vacío = null.
     *
     * ⚠️ La columna sirve a DOS puestos y significa una cosa distinta en cada uno:
     *   profesor  → "IMPARTE estos niveles". Acota el eje de su rejilla y prioriza
     *               a los candidatos en las suplencias.
     *   directivo → "GESTIONA estos niveles". Acota sus tableros, su agenda y su
     *               bandeja de justificantes. Vacío = todo el colegio.
     *
     * Los dos significados NO pueden coexistir en una fila: `directivo` es
     * EXCLUYENTE y normalizarTipoPersonal() —que corre JUSTO ANTES, y ese orden es
     * precondición de este método— ya ha colapsado el SET a ['directivo']. En
     * prefectura y administrativos se sigue forzando a null, igual que
     * `puede_suplir` se fuerza a 1, así que un POST manipulado tampoco se los cuela.
     *
     * El orden lo fija Materia::NIVELES (Maternal→Bachillerato), que es el vocabulario
     * canónico: array_intersect conserva el orden del primer array.
     */
    private function normalizarNiveles(): void {
        if (!$this->esDocente() && !$this->esDirectivo()) { $this->niveles = null; return; }

        $raw = $this->niveles;
        if (\is_array($raw)) {
            $lista = $raw;
        } else {
            $lista = array_filter(array_map('trim', explode(',', (string) $raw)));
        }
        $lista = array_values(array_intersect(Materia::NIVELES, $lista));
        // Los cinco marcados es lo mismo que ninguno: para quien lee el alcance son
        // la misma cosa, y así la UI no pinta cinco chips redundantes. Mismo criterio
        // que Evento::normalizarNiveles().
        if (count($lista) === count(Materia::NIVELES)) $lista = [];
        $this->niveles = $lista ? implode(',', $lista) : null;
    }

    /** ¿El tipo de personal incluye 'profesor'? (tipo_personal ya normalizado o crudo) */
    private function esDocente(): bool {
        return $this->tieneTipo('profesor');
    }

    /** ¿El tipo de personal incluye 'directivo'? */
    private function esDirectivo(): bool {
        return $this->tieneTipo('directivo');
    }

    private function tieneTipo(string $tipo): bool {
        $raw = $this->tipo_personal;
        $lista = \is_array($raw) ? $raw : array_filter(array_map('trim', explode(',', (string) $raw)));
        return in_array($tipo, $lista, true);
    }

    /** Niveles declarados de un usuario, en orden canónico. [] si no ha declarado ninguno. */
    public static function nivelesDe(int $id): array {
        $id = (int) $id;
        if ($id <= 0) return [];
        $r = self::$db->query("SELECT niveles FROM " . static::$tabla . " WHERE id = {$id} LIMIT 1");
        $row = $r ? $r->fetch_assoc() : null;
        $lista = array_filter(array_map('trim', explode(',', (string) ($row['niveles'] ?? ''))));
        return array_values(array_intersect(Materia::NIVELES, $lista));
    }

    /**
     * Direcciones a las que compete un conjunto de niveles: las que tienen alguno de
     * ellos declarado MÁS las que no declaran ninguno (dirección general, cuyo
     * alcance es el colegio entero).
     *
     * Es el fan-out de avisos. `Notificacion::nueva()` escribe UNA fila por usuario y
     * no sabe de grupos, así que el reparto se decide aquí y en UNA sola consulta.
     *
     * No reutiliza porTipo(): ese arrastra un LEFT JOIN a `articulos` con un
     * COUNT(a.id) que existe para las tarjetas de los directorios y aquí solo cuesta
     * un GROUP BY inútil. Devuelve ids y no objetos porque hidratar seis ActiveRecord
     * para leerles el id es trabajo tirado.
     *
     * @param string[] $niveles [] o los cinco = todas las direcciones
     * @return int[] ids en orden estable
     */
    public static function direccionesDeNiveles(array $niveles): array {
        $cond = '';
        $ok   = array_values(array_intersect(Materia::NIVELES, $niveles));
        if ($ok && count($ok) < count(Materia::NIVELES)) {
            $orNivel = implode(' OR ', array_map(
                fn($n) => "FIND_IN_SET('" . self::$db->escape_string($n) . "', u.niveles)", $ok));
            // ⚠️ Un SET tiene TRES estados: NULL, '' y con valor. FIND_IN_SET(x, '')
            // devuelve 0 y '' IS NULL es false, así que sin el término `= ''` una
            // dirección general guardada con el SET vacío se quedaría sin un solo aviso.
            $cond = " AND (u.niveles IS NULL OR u.niveles = '' OR {$orNivel})";
        }

        $out = [];
        $r = self::$db->query(
            "SELECT u.id FROM " . static::$tabla . " u
              WHERE FIND_IN_SET('directivo', u.tipo_personal){$cond}
              ORDER BY u.id ASC");
        if ($r) while ($row = $r->fetch_assoc()) $out[] = (int) $row['id'];
        return $out;
    }

    /** Valida el formato de fecha_nacimiento (Y-m-d) si se proporcionó. */
    private function validarFechaNacimiento(): void {
        $f = trim((string) ($this->fecha_nacimiento ?? ''));
        if ($f === '') { $this->fecha_nacimiento = null; return; }
        $d = \DateTime::createFromFormat('Y-m-d', $f);
        if (!$d || $d->format('Y-m-d') !== $f) {
            static::setAlerta('error', 'La fecha de nacimiento no es válida');
        }
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

    /**
     * Todos los usuarios del panel, en orden de alta.
     * (Antes traía además un COUNT de artículos por un LEFT JOIN a `articulos`;
     * la lista de usuarios ya no muestra esa columna, así que el JOIN sobraba.
     * El conteo por usuario sigue disponible en findConArticulos(), que lo usa
     * la ficha de perfil.)
     */
    public static function todos(): array {
        return static::consultarSQL("SELECT * FROM usuarios ORDER BY id ASC");
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

    /** Búsqueda de colaboradores por nombre/email para autocompletado (módulo Suplencias). */
    public static function buscar(string $q, int $limite = 8): array {
        $q = trim($q);
        if ($q === '') return [];
        $safe   = self::$db->escape_string($q);
        $limite = max(1, min(20, $limite));
        $query = "SELECT id, nombre, email, avatar
                  FROM " . static::$tabla . "
                  WHERE nombre LIKE '%{$safe}%' OR email LIKE '%{$safe}%'
                  ORDER BY nombre ASC
                  LIMIT {$limite}";
        $r = self::$db->query($query);
        $out = [];
        if ($r) {
            while ($row = $r->fetch_assoc()) {
                $out[] = [
                    'id'     => (int)$row['id'],
                    'nombre' => $row['nombre'],
                    'email'  => $row['email'],
                    'avatar' => $row['avatar'] ?? '',
                ];
            }
        }
        return $out;
    }

    /**
     * Igual que buscar(), pero solo personal DOCENTE y sin uno mismo.
     *
     * `buscar()` devuelve cualquier usuario, incluido quien busca: sirve para el picker
     * de Suplencias, donde el ausente puede ser cualquiera. Aquí no vale — un
     * acompañante de coteaching y la contraparte de un intercambio tienen que dar clase,
     * y ofrecerte a ti mismo solo produce un error del servidor.
     */
    public static function buscarProfesores(string $q, int $excluir = 0, int $limite = 8): array {
        $q = trim($q);
        if ($q === '') return [];
        $safe   = self::$db->escape_string($q);
        $limite = max(1, min(20, $limite));
        $excl   = $excluir > 0 ? " AND id <> " . (int)$excluir : '';
        $query = "SELECT id, nombre, email, avatar
                  FROM " . static::$tabla . "
                  WHERE FIND_IN_SET('profesor', tipo_personal)
                    AND (nombre LIKE '%{$safe}%' OR email LIKE '%{$safe}%')
                    {$excl}
                  ORDER BY nombre ASC
                  LIMIT {$limite}";
        $r = self::$db->query($query);
        $out = [];
        if ($r) {
            while ($row = $r->fetch_assoc()) {
                $out[] = [
                    'id'     => (int)$row['id'],
                    'nombre' => $row['nombre'],
                    'email'  => $row['email'],
                    'avatar' => $row['avatar'] ?? '',
                ];
            }
        }
        return $out;
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

    /** Devuelve todos los usuarios con sus conteos de artículos y noticias por estado. */
    public static function conArticulosYNoticias(): array {
        $query = "
            SELECT u.id, u.nombre, u.email, u.rol, u.avatar,
                   COUNT(DISTINCT CASE WHEN a.estado='publicado' THEN a.id END) AS art_publicados,
                   COUNT(DISTINCT CASE WHEN a.estado='borrador'  THEN a.id END) AS art_borradores,
                   COUNT(DISTINCT CASE WHEN a.estado='programado' THEN a.id END) AS art_programados,
                   COUNT(DISTINCT CASE WHEN n.estado='publicado' THEN n.id END) AS not_publicadas,
                   COUNT(DISTINCT CASE WHEN n.estado='borrador'  THEN n.id END) AS not_borradores,
                   COUNT(DISTINCT CASE WHEN n.estado='programado' THEN n.id END) AS not_programadas
            FROM usuarios u
            LEFT JOIN articulos a ON a.autor_id = u.id
            LEFT JOIN noticias  n ON n.autor_id = u.id
            GROUP BY u.id
            ORDER BY (COUNT(DISTINCT a.id) + COUNT(DISTINCT n.id)) DESC, u.nombre ASC
        ";
        return static::consultarSQL($query);
    }

    /** Devuelve artículos y noticias de un autor específico. */
    public static function contenidoDeAutor(int $autorId): array {
        $id = (int) $autorId;
        $arts = static::$db->query("
            SELECT a.id, a.titulo, a.slug, a.estado, a.envio_revision,
                   a.creado_en, a.vistas, a.likes,
                   c.nombre AS categoria_nombre, c.color AS categoria_color,
                   'articulo' AS tipo
            FROM articulos a
            LEFT JOIN categorias c ON c.id = a.categoria_id
            WHERE a.autor_id = {$id}
            ORDER BY a.creado_en DESC
        ");
        $nots = static::$db->query("
            SELECT n.id, n.titulo, n.slug, n.estado, n.envio_revision,
                   n.creado_en, n.vistas, n.likes,
                   c.nombre AS categoria_nombre, c.color AS categoria_color,
                   'noticia' AS tipo
            FROM noticias n
            LEFT JOIN categorias_noticias c ON c.id = n.categoria_id
            WHERE n.autor_id = {$id}
            ORDER BY n.creado_en DESC
        ");
        $rows = [];
        if ($arts) while ($r = $arts->fetch_assoc()) $rows[] = $r;
        if ($nots) while ($r = $nots->fetch_assoc()) $rows[] = $r;
        usort($rows, fn($a, $b) => strcmp($b['creado_en'], $a['creado_en']));
        return $rows;
    }

    /** Validación para edición: igual que validar() pero sin exigir contraseña. */
    public function validarPerfil(): array {
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

        return static::$alertas;
    }

    public function validarEdicion(): array {
        static::$alertas = [];

        $this->validarIdentidad();
        $this->validarRolYPermisos();

        return static::$alertas;
    }

    /**
     * Persiste fecha_nacimiento con soporte de NULL real (el ORM base no lo permite).
     * Llamar tras guardar() con el id ya disponible.
     */
    public static function guardarFechaNacimiento(int $id, ?string $fecha): void {
        $id = (int) $id;
        if ($id <= 0) return;
        $fecha = trim((string) $fecha);
        if ($fecha !== '' && \DateTime::createFromFormat('Y-m-d', $fecha)) {
            $f = self::$db->escape_string($fecha);
            self::$db->query("UPDATE " . static::$tabla . " SET fecha_nacimiento = '{$f}' WHERE id = {$id} LIMIT 1");
        } else {
            self::$db->query("UPDATE " . static::$tabla . " SET fecha_nacimiento = NULL WHERE id = {$id} LIMIT 1");
        }
    }

    /**
     * Persiste rol_redaccion, tipo_personal y niveles con soporte de NULL real
     * (el ORM base no lo permite). Llamar tras guardar() con el id ya disponible.
     */
    public static function guardarAtributos(int $id, ?string $rolRedaccion, ?string $tipoPersonal, ?string $niveles = null): void {
        $id = (int) $id;
        if ($id <= 0) return;
        $sql = fn(?string $v) => ($v !== null && $v !== '') ? "'" . self::$db->escape_string($v) . "'" : 'NULL';
        self::$db->query("UPDATE " . static::$tabla . " SET"
            . " rol_redaccion = " . $sql($rolRedaccion)
            . ", tipo_personal = " . $sql($tipoPersonal)
            . ", niveles = "       . $sql($niveles)
            . " WHERE id = {$id} LIMIT 1");
    }

    /**
     * Candidatos a suplente: SOLO profesores que sí pueden suplir.
     *
     * Antes entraban también los administrativos ("última prioridad"), pero cubrir
     * una clase exige estar frente a grupo: prefectura y administrativos registran
     * y coordinan las ausencias, no las cubren.
     */
    public static function candidatosSuplencia(): array {
        $r = self::$db->query("
            SELECT id, nombre, avatar, tipo_personal, niveles, puede_suplir
            FROM usuarios
            WHERE puede_suplir = 1
              AND FIND_IN_SET('profesor', tipo_personal)
            ORDER BY nombre ASC
        ");
        $out = [];
        if ($r) while ($row = $r->fetch_assoc()) $out[] = $row;
        return $out;
    }

    /**
     * Índice ligero id/nombre/email de todo el claustro, para resolver el CSV de horarios.
     * No se filtra por tipo_personal: el archivo puede traer a quien todavía no lo tenga puesto.
     */
    public static function todosParaImportar(): array {
        return static::consultarSQL("SELECT id, nombre, email FROM usuarios ORDER BY nombre ASC");
    }

    /** Usuarios cuyo tipo_personal incluye el tipo indicado (para directorios y suplencias). */
    public static function porTipo(string $tipo): array {
        $safe = self::$db->escape_string($tipo);
        $query = "SELECT u.*, COUNT(a.id) AS total_articulos
                  FROM usuarios u
                  LEFT JOIN articulos a ON a.autor_id = u.id
                  WHERE FIND_IN_SET('{$safe}', u.tipo_personal)
                  GROUP BY u.id
                  ORDER BY u.nombre ASC";
        return static::consultarSQL($query);
    }

    /** ¿Este usuario puede acceder al módulo indicado? El admin accede a todos. */
    public function puedeModulo(string $modulo): bool {
        if (($this->rol ?? '') === 'administrador') return true;
        $lista = array_filter(array_map('trim', explode(',', (string) $this->modulos)));
        return \in_array($modulo, $lista, true);
    }

    /**
     * Próximos cumpleaños de colaboradores (ignora el año).
     * Devuelve filas con nombre, avatar, fecha_nacimiento, mes, dia y edad que cumplirá.
     */
    public static function proximosCumpleanos(int $limite = 8): array {
        $query = "
            SELECT id, nombre, email, avatar, rol, fecha_nacimiento,
                   MONTH(fecha_nacimiento) AS mes,
                   DAY(fecha_nacimiento)   AS dia
            FROM " . static::$tabla . "
            WHERE fecha_nacimiento IS NOT NULL
            ORDER BY
                (DAYOFYEAR(fecha_nacimiento) - DAYOFYEAR(CURDATE()) + 366) % 366 ASC
            LIMIT {$limite}
        ";
        return static::consultarSQL($query);
    }

    /** Todos los colaboradores con fecha de nacimiento (para el calendario). */
    public static function conCumpleanos(): array {
        $query = "
            SELECT id, nombre, avatar, rol, fecha_nacimiento,
                   MONTH(fecha_nacimiento) AS mes,
                   DAY(fecha_nacimiento)   AS dia
            FROM " . static::$tabla . "
            WHERE fecha_nacimiento IS NOT NULL
            ORDER BY mes ASC, dia ASC
        ";
        return static::consultarSQL($query);
    }
}
