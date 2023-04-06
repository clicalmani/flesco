<?php
require_once dirname( __DIR__ ) . '/helpers.php';
require_once dirname( __DIR__ ) . '/auto.php';

ini_set('log_errors', 1);
ini_set('error_log', storage_path( '/errors/errors.log' ) );

global $dotenv;
$dotenv = Dotenv\Dotenv::createImmutable( root_path() );
$dotenv->safeLoad();

Clicalmani\Flesco\Routes\Route::$routines = [
    'get'     => [], 
    'post'    => [],
    'options' => [],
    'delete'  => [],
    'put'     => [],
    'patch'   => []
];