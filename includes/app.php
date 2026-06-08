<?php

use Dotenv\Dotenv;
use Model\ActiveRecord;
require __DIR__ . '/../vendor/autoload.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Añadir Dotenv
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();


require 'funciones.php';
require 'helpers.php';
require 'database.php';

// Conectarnos a la base de datos
ActiveRecord::setDB($db);