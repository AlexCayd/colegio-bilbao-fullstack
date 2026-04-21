<?php

namespace Controllers;

use Classes\Email;
use MVC\Router;

class EstaticasController {
    public static function index(Router $router) {
        $router->render('estaticas/index', [
            'titulo' => 'Home'
        ]);
    }

    public static function contacto(Router $router) {
        $router->render('estaticas/admisiones/contacto', [
            'titulo' => 'Contacto'
        ]);
    }

    public static function convenios(Router $router) {
        $router->render('estaticas/admisiones/convenios', [
            'titulo' => 'Convenios'
        ]);
    }

    public static function convocatoriabecas(Router $router) {
        $router->render('estaticas/admisiones/convocatoria-becas', [
            'titulo' => 'Convocatoria becas'
        ]);
    }

    public static function inicio(Router $router) {
        $router->render('estaticas/admisiones/inicio', [
            'titulo' => 'Admisiones'
        ]);
    }

    public static function preguntasfrecuentes(Router $router) {
        $router->render('estaticas/admisiones/preguntas-frecuentes', [
            'titulo' => 'Preguntas Frecuentes'
        ]);
    }
}