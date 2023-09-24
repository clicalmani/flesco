<?php
/**
 * |---------------------------------------------------------------------------
 * |              ***** TONKA built-in helper functions *****
 * |---------------------------------------------------------------------------
 * 
 * Built-in helper functions
 * 
 * 
 */
require_once dirname( __DIR__ ) . '/helpers.php';

/**
 * |----------------------------------------------------------------
 * |              ***** Class Auto Loader *****
 * |----------------------------------------------------------------
 * 
 * Simple SPL Autoloader
 * 
 * Classes defined in the App directory, will be automatically loaded.
 */
new Clicalmani\Container\SPL_Loader( root_path() );

/**
 * Error log
 */
ini_set('log_errors', 1);
ini_set('error_log', storage_path( '/errors/errors.log' ) );

/**
 * Load environment variable to $_ENV
 */
global $dotenv;
$dotenv = Dotenv\Dotenv::createImmutable( root_path() );
$dotenv->safeLoad();

/**
 * Route methods definition
 * 
 * Here we define all the supported methods.
 */
Clicalmani\Flesco\Routes\Route::$routines = [
    'get'     => [], 
    'post'    => [],
    'options' => [],
    'delete'  => [],
    'put'     => [],
    'patch'   => []
];
