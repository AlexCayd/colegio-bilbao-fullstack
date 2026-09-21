<?php
namespace Model;

/**
 * Clase base del ORM: patrón Active Record sobre MySQLi.
 *
 * Cada modelo declara su tabla ($tabla) y las columnas que este ORM debe persistir
 * ($columnasDB), y hereda el CRUD completo. No hay mapeo de relaciones ni cargas
 * perezosas: las consultas con JOIN se escriben a mano en cada modelo y se hidratan
 * con consultarSQL().
 *
 * ── CUATRO LIMITACIONES QUE HAY QUE CONOCER ──────────────────────────────────
 * Son la causa de casi todo el código "raro" de los modelos concretos.
 *
 * 1. SOLO SE COPIAN PROPIEDADES DECLARADAS. crearObjeto() descarta cualquier
 *    columna que no exista como propiedad pública. Todo alias de SQL
 *    (MONTH(x) AS mes, u.nombre AS ausente_nombre, COUNT(*) AS total) debe
 *    declararse en el modelo o SE PIERDE EN SILENCIO, sin error ni aviso.
 *
 * 2. NO SABE ESCRIBIR NULL. crear() y actualizar() envuelven todos los valores en
 *    comillas simples, así que un null acaba como cadena vacía — error en un DATE
 *    y valor falso en una FK. Los modelos que necesitan NULL real sobrescriben
 *    guardar() (Suplencia, SuplenciaHora, Swap, Horario, Evento) o persisten ese
 *    campo con un UPDATE aparte (UsuarioBlog::guardarFechaNacimiento()).
 *
 * 3. CONSULTAS POR CONCATENACIÓN, no sentencias preparadas. La protección es
 *    escape_string() sobre los valores y cast a (int) sobre los identificadores, y
 *    es efectiva solo mientras se aplique SIEMPRE. Ningún dato de $_GET, $_POST,
 *    $_FILES o la sesión debe entrar en una cadena SQL sin pasar por ahí; un
 *    identificador usado como nombre de columna o dirección de orden necesita
 *    además lista blanca, porque escape_string() no protege en esa posición.
 *
 * 4. sincronizar() IGNORA LOS NULOS. Es lo que permite editar con un formulario
 *    parcial, pero también significa que no se puede vaciar un campo pasándole
 *    null: hay que pasar cadena vacía y normalizarla en el modelo.
 *
 * @property int|string|null $id Clave primaria. NO se declara aquí sino en cada modelo
 *           hijo, pero guardar(), actualizar() y eliminar() la usan: se anota para que el
 *           análisis estático no la dé por indefinida. Un modelo sin `public $id` rompe
 *           esos tres métodos.
 *
 * @package Model
 */
class ActiveRecord {

    /**
     * Conexión MySQLi compartida por todos los modelos.
     * La inyecta includes/app.php al arrancar, vía setDB().
     *
     * @var \mysqli
     */
    protected static $db;

    /**
     * Nombre de la tabla que respalda el modelo. Lo sobrescribe cada hijo.
     *
     * ⚠️ Se interpola directamente en el SQL: es un literal del código, nunca un
     * valor de entrada.
     *
     * @var string
     */
    protected static $tabla = '';

    /**
     * Columnas que este ORM escribe en INSERT y UPDATE.
     *
     * Es una lista blanca: una columna ausente de aquí no se persiste aunque el
     * objeto tenga la propiedad. Se usa para dejar fuera los campos que necesitan
     * NULL real y los que son de solo lectura (creado_en, ultimo_acceso).
     *
     * @var array<int, string>
     */
    protected static $columnasDB = [];

    /**
     * Mensajes de validación acumulados, agrupados por tipo ('error', 'exito'…).
     *
     * @var array<string, array<int, string>>
     */
    protected static $alertas = [];

    /**
     * Inyecta la conexión a la base de datos. La llama includes/app.php al arrancar.
     *
     * @param  \mysqli $database Conexión ya abierta y con charset utf8mb4.
     * @return void
     */
    public static function setDB($database) {
        self::$db = $database;
    }

    /**
     * Devuelve la conexión, para las operaciones que el ORM no cubre.
     *
     * La usan las escrituras con transacción (la importación de horarios) y las
     * consultas que no encajan en el patrón Active Record.
     *
     * @return \mysqli
     */
    public static function getDB(): \mysqli {
        return static::$db;
    }

    /**
     * Enciende o apaga la baja lógica (`activo`) de varias filas de golpe.
     *
     * Solo la heredan los cuatro modelos que tienen esa columna —UsuarioBlog, Aula,
     * Grupo y Materia—, y cada uno la expone como `cambiarActivo()`. Es `protected`
     * justamente por eso: llamarla sobre un modelo sin la columna sería un error de
     * SQL, y así no se puede.
     *
     * Va por UPDATE directo y no por guardar() porque `activo` está deliberadamente
     * FUERA de $columnasDB (ver la nota de cada modelo): es una columna con DEFAULT,
     * que ningún formulario debe poder escribir de rebote con sincronizar($_POST).
     *
     * @param  array<int, mixed> $ids    Ids a cambiar; los no numéricos se descartan.
     * @param  bool              $activo true = alta, false = baja lógica.
     * @return int Filas realmente modificadas (las que ya estaban así no cuentan).
     */
    protected static function marcarActivo(array $ids, bool $activo): int {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (!$ids) return 0;
        $v = $activo ? 1 : 0;
        static::$db->query(
            "UPDATE " . static::$tabla . " SET activo = {$v} WHERE id IN (" . implode(',', $ids) . ")"
        );
        return static::$db->affected_rows;
    }

    /**
     * Añade un mensaje de validación.
     *
     * @param  string $tipo    Categoría: 'error' o 'exito'.
     * @param  string $mensaje Texto ya redactado para el usuario final.
     * @return void
     */
    public static function setAlerta($tipo, $mensaje) {
        static::$alertas[$tipo][] = $mensaje;
    }

    /**
     * Devuelve las alertas acumuladas.
     *
     * @return array<string, array<int, string>> Vacío = sin problemas.
     */
    public static function getAlertas() {
        return static::$alertas;
    }

    /**
     * Validación por defecto: no comprueba nada.
     *
     * Cada modelo la sobrescribe. La convención es limpiar $alertas al principio y
     * devolver el array resultante; vacío significa válido.
     *
     * @return array<string, array<int, string>>
     */
    public function validar() {
        static::$alertas = [];
        return static::$alertas;
    }

    /**
     * Ejecuta un SELECT y devuelve los resultados hidratados como objetos del modelo.
     *
     * Es la puerta de entrada de todas las consultas a medida de los modelos. Ante un
     * error de SQL devuelve un array vacío en vez de lanzar: quien llama debe tratar
     * "sin resultados" y "la consulta falló" como el mismo caso.
     *
     * ⚠️ Todo alias del SELECT necesita su propiedad pública en el modelo, o
     * crearObjeto() lo descarta (ver limitación 1 en la cabecera de la clase).
     *
     * @param  string $query SQL completo. Los valores ya deben venir escapados.
     * @return array<int, static> Lista de objetos; vacía si no hay filas o si falló.
     */
    public static function consultarSQL($query) {
        $resultado = self::$db->query($query);

        if (!$resultado || !($resultado instanceof \mysqli_result)) {
            return [];
        }

        $array = [];
        while($registro = $resultado->fetch_assoc()) {
            $array[] = static::crearObjeto($registro);
        }

        $resultado->free();

        return $array;
    }

    /**
     * Convierte una fila de la base de datos en un objeto del modelo.
     *
     * ⚠️ Copia ÚNICAMENTE las claves que existan como propiedad de la clase. Una
     * columna o alias sin propiedad declarada se descarta sin ningún aviso — es el
     * fallo silencioso más habitual al añadir un JOIN o una función agregada a una
     * consulta.
     *
     * @param  array<string, string|null> $registro Fila tal cual la devuelve fetch_assoc().
     * @return static
     */
    protected static function crearObjeto($registro) {
        $objeto = new static;

        foreach($registro as $key => $value ) {
            if(property_exists( $objeto, $key  )) {
                $objeto->$key = $value;
            }
        }
        return $objeto;
    }

    /**
     * Recopila los valores de las columnas persistibles, excluyendo la clave primaria.
     *
     * @return array<string, mixed> Mapa columna => valor.
     */
    public function atributos() {
        $atributos = [];
        foreach(static::$columnasDB as $columna) {
            if($columna === 'id') continue;
            $atributos[$columna] = $this->$columna;
        }
        return $atributos;
    }

    /**
     * Escapa los atributos para interpolarlos en el SQL.
     *
     * ⚠️ escape_string() convierte null en cadena vacía. Es el origen de la
     * limitación 2: un modelo que necesite NULL real no puede pasar por aquí.
     *
     * @return array<string, string> Mapa columna => valor escapado.
     */
    public function sanitizarAtributos() {
        $atributos = $this->atributos();
        $sanitizado = [];
        foreach($atributos as $key => $value ) {
            $sanitizado[$key] = self::$db->escape_string($value);
        }
        return $sanitizado;
    }

    /**
     * Vuelca un array asociativo (normalmente $_POST) sobre las propiedades del objeto.
     *
     * Solo asigna claves que existan como propiedad y cuyo valor NO sea null, y aplica
     * trim() a las cadenas.
     *
     * ⚠️ Como los nulos se ignoran, esto NO sirve para vaciar un campo: hay que pasar
     * cadena vacía y normalizarla en validar().
     *
     * @param  array<string, mixed> $args Datos de entrada.
     * @return void
     */
    public function sincronizar($args=[]) {
        foreach($args as $key => $value) {
          if(property_exists($this, $key) && !is_null($value)) {
            $this->$key = is_string($value) ? trim($value) : $value;
          }
        }
    }

    /**
     * Guarda el objeto: inserta si no tiene id, actualiza si lo tiene.
     *
     * @return array{resultado: bool|\mysqli_result, id: int|string}|bool
     *         Al insertar, array con el resultado y el id nuevo; al actualizar, el
     *         booleano de mysqli::query(). La forma asimétrica es histórica: quien
     *         llame debe saber en qué caso está.
     */
    public function guardar() {
        $resultado = '';
        if(!is_null($this->id)) {
            // actualizar
            $resultado = $this->actualizar();
        } else {
            // Creando un nuevo registro
            $resultado = $this->crear();
        }
        return $resultado;
    }

    /**
     * Todos los registros de la tabla, del más reciente al más antiguo.
     *
     * Sin paginación: sobre tablas grandes conviene una consulta a medida en el modelo.
     *
     * @return array<int, static>
     */
    public static function all() {
        $query = "SELECT * FROM " . static::$tabla . " ORDER BY id DESC";
        $resultado = self::consultarSQL($query);
        return $resultado;
    }

    /**
     * Busca un registro por su clave primaria.
     *
     * @param  int|string $id Se castea a entero, así que es seguro con entrada del usuario.
     * @return static|null null si no existe.
     */
    public static function find($id) {
        $query = "SELECT * FROM " . static::$tabla . " WHERE id = " . (int)$id;
        $resultado = self::consultarSQL($query);
        return array_shift($resultado);
    }

    /**
     * Devuelve el registro MÁS RECIENTE de los N primeros.
     *
     * ⚠️ El nombre engaña: pese al LIMIT, array_shift() se queda con un único objeto,
     * no con la lista. Para obtener varios hay que usar una consulta propia.
     *
     * @param  int $limite Tamaño del LIMIT.
     * @return static|null
     */
    public static function get($limite) {
        $query = "SELECT * FROM " . static::$tabla . " ORDER BY id DESC LIMIT " . (int)$limite;
        $resultado = self::consultarSQL($query);
        return array_shift($resultado);
    }

    /**
     * Devuelve el PRIMER registro cuya columna coincida con el valor dado.
     *
     * ⚠️ El nombre sugiere una lista, pero devuelve un solo objeto.
     *
     * @param  string $columna Nombre de la columna. Debe ser un literal del código:
     *                         escape_string() no protege en esa posición del SQL.
     * @param  mixed  $valor   Se escapa antes de interpolarse.
     * @return static|null
     */
    public static function where($columna, $valor) {
        $query = "SELECT * FROM " . static::$tabla . " WHERE " . self::$db->escape_string($columna) . " = '" . self::$db->escape_string($valor) . "'";
        $resultado = self::consultarSQL($query);
        return array_shift($resultado);
    }

    /**
     * Inserta el objeto como registro nuevo.
     *
     * @return array{resultado: bool|\mysqli_result, id: int|string} 'resultado' en false = falló.
     */
    public function crear() {
        // Sanitizar los datos
        $atributos = $this->sanitizarAtributos();

        // Insertar en la base de datos
        $query = " INSERT INTO " . static::$tabla . " ( ";
        $query .= join(', ', array_keys($atributos));
        $query .= " ) VALUES ('";
        $query .= join("', '", array_values($atributos));
        $query .= "') ";

        // debuguear($query); // Descomentar si no te funciona algo

        // Resultado de la consulta
        $resultado = self::$db->query($query);
        return [
           'resultado' =>  $resultado,
           'id' => self::$db->insert_id
        ];
    }

    /**
     * Actualiza el registro existente. Escribe TODAS las columnas de $columnasDB,
     * no solo las que cambiaron.
     *
     * @return bool|\mysqli_result false si falló.
     */
    public function actualizar() {
        // Sanitizar los datos
        $atributos = $this->sanitizarAtributos();

        // Iterar para ir agregando cada campo de la BD
        $valores = [];
        foreach($atributos as $key => $value) {
            $valores[] = "{$key}='{$value}'";
        }

        // Consulta SQL
        $query = "UPDATE " . static::$tabla ." SET ";
        $query .=  join(', ', $valores );
        $query .= " WHERE id = '" . self::$db->escape_string($this->id) . "' ";
        $query .= " LIMIT 1 ";

        // Actualizar BD
        $resultado = self::$db->query($query);
        return $resultado;
    }

    /**
     * Elimina el registro por su id.
     *
     * ⚠️ El borrado se propaga según las FKs del esquema: ON DELETE CASCADE en los
     * hijos que no existen sin el padre, ON DELETE SET NULL en los que sobreviven con
     * un dato menos. Ver docs/04-base-de-datos.md antes de borrar en cascada.
     *
     * @return bool|\mysqli_result false si falló.
     */
    public function eliminar() {
        $query = "DELETE FROM "  . static::$tabla . " WHERE id = " . self::$db->escape_string($this->id) . " LIMIT 1";
        $resultado = self::$db->query($query);
        return $resultado;
    }
}
