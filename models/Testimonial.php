<?php
namespace Model;

/**
 * Testimonio de una familia sobre el colegio.
 *
 * Lo envían desde el formulario público /feedback-testimoniales y nace SIN aprobar: no
 * se publica en la portada hasta que un revisor lo apruebe desde Redacción ›
 * Testimoniales. Es el único contenido del sitio que escribe alguien de fuera, así que
 * la moderación previa no es opcional.
 *
 * ⚠️ Única tabla del esquema con una columna en inglés (`created_at`), incoherencia
 * heredada de la plantilla original.
 *
 * @package Model
 */
class Testimonial extends ActiveRecord {

    protected static $tabla      = 'testimoniales';

    /**
     * ⚠️ Sin 'id' a propósito: la clave primaria es AUTO_INCREMENT y atributos() la
     * excluye igualmente.
     *
     * @var array<int, string>
     */
    protected static $columnasDB = ['nombre', 'rol', 'comentario', 'aprobado', 'created_at'];

    /** @var int|string|null */
    public $id;
    /** @var string|null Nombre de quien firma el testimonio. */
    public $nombre;
    /** @var string|null Parentesco o vínculo. Debe ser uno de self::$roles. */
    public $rol;
    /** @var string|null Texto del testimonio. */
    public $comentario;
    /** @var int 0 = pendiente de moderación, 1 = publicado. */
    public $aprobado   = 0;
    /** @var string|null Fecha de envío (Y-m-d H:i:s). La fija el controlador, no la BD. */
    public $created_at;

    /**
     * Vínculos admitidos. Deben coincidir con el ENUM `rol` de la tabla.
     *
     * @var array<int, string>
     */
    public static $roles = ['Papá', 'Mamá', 'Exalumno', 'Exalumna', 'Familia'];

    /**
     * Testimonios ya moderados, listos para mostrarse en la portada.
     *
     * @return array<int, self> En orden de alta.
     */
    public static function aprobados(): array {
        return self::consultarSQL(
            "SELECT * FROM testimoniales WHERE aprobado = 1 ORDER BY id ASC"
        );
    }

    /**
     * Todos los testimonios para la pantalla de moderación.
     *
     * Los pendientes salen primero (`aprobado ASC`) y, dentro de cada grupo, los más
     * recientes: es la cola de trabajo del revisor.
     *
     * @return array<int, self>
     */
    public static function todos(): array {
        return self::consultarSQL(
            "SELECT * FROM testimoniales ORDER BY aprobado ASC, created_at DESC"
        );
    }

    /**
     * Valida el testimonio recibido del formulario público.
     *
     * ⚠️ ROMPE LA CONVENCIÓN del proyecto: devuelve una lista plana de cadenas en vez de
     * usar setAlerta() y el array agrupado por tipo de ActiveRecord. La vista
     * feedback-testimoniales lo recorre así, de modo que cambiarlo obliga a tocar
     * también EstaticasController::feedbackTestimoniales() y la plantilla.
     *
     * ⚠️ El máximo de 60 caracteres parece un error de dedo (¿600?): con un mínimo de 20,
     * la ventana útil es de 40 caracteres, menos que esta misma frase. Revisar con el
     * colegio antes de darle más difusión al formulario.
     *
     * @return array<int, string> Lista de errores; vacía = válido.
     */
    public function validar(): array {
        $errores = [];
        if (!trim($this->nombre ?? ''))
            $errores[] = 'El nombre es obligatorio';
        if (!$this->rol || !in_array($this->rol, self::$roles))
            $errores[] = 'Selecciona un rol válido';
        $len = mb_strlen(trim($this->comentario ?? ''));
        if ($len < 20)
            $errores[] = 'El comentario debe tener al menos 20 caracteres';
        if ($len > 60)
            $errores[] = 'El comentario no puede superar 60 caracteres';
        return $errores;
    }
}
