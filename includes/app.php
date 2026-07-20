<?php

use Dotenv\Dotenv;
use Model\ActiveRecord;
require __DIR__ . '/../vendor/autoload.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Zona horaria del colegio (evita desfases de fecha por defecto UTC)
date_default_timezone_set('America/Mexico_City');

// Añadir Dotenv
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();


require 'funciones.php';
require 'helpers.php';
require 'database.php';

// Conectarnos a la base de datos
ActiveRecord::setDB($db);