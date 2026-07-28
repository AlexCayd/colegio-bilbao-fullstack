<?php
namespace Model;

class Periodo extends ActiveRecord {
    protected static $tabla      = 'periodos';
    protected static $columnasDB = ['id', 'orden', 'etiqueta', 'hora_inicio', 'hora_fin', 'es_receso'];

    public $id;
    public $orden;
    public $etiqueta;
    public $hora_inicio;
    public $hora_fin;
    public $es_receso;

    /** Todos los periodos ordenados por la jornada. */
    public static function todos(): array {
        return static::consultarSQL("SELECT * FROM periodos ORDER BY orden ASC");
    }

    /** Solo los periodos de clase (excluye recesos). */
    public static function clases(): array {
        return static::consultarSQL("SELECT * FROM periodos WHERE es_receso = 0 ORDER BY orden ASC");
    }
}
