<?php
namespace Model;

class Testimonial extends ActiveRecord {

    protected static $tabla      = 'testimoniales';
    protected static $columnasDB = ['nombre', 'rol', 'comentario', 'aprobado', 'created_at'];

    public $id;
    public $nombre;
    public $rol;
    public $comentario;
    public $aprobado   = 0;
    public $created_at;

    public static $roles = ['Papá', 'Mamá', 'Exalumno', 'Exalumna', 'Familia'];

    public static function aprobados(): array {
        return self::consultarSQL(
            "SELECT * FROM testimoniales WHERE aprobado = 1 ORDER BY id ASC"
        );
    }

    public static function todos(): array {
        return self::consultarSQL(
            "SELECT * FROM testimoniales ORDER BY aprobado ASC, created_at DESC"
        );
    }

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
