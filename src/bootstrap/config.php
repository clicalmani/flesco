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

\Clicalmani\Flesco\Support\Helper::include();

/**
 * |----------------------------------------------------------------
 * |            ***** Container AutoLoader *****
 * |----------------------------------------------------------------
 * 
 * Classes defined in the app directory will be automatically injected.
 */
new Clicalmani\Container\SPL_Loader( root_path() );

/**
 * Error log
 */
\Clicalmani\Flesco\Support\Log::init();

/**
 * Load environment variables
 */
\Dotenv\Dotenv::create(
    \Clicalmani\Flesco\Support\Env::getRepository(), 
    root_path()
)->safeLoad();

/**
 * Route methods definition
 * 
 * Here we define all the supported methods.
 */
Clicalmani\Routes\Route::$routines = [
    'get'     => [], 
    'post'    => [],
    'options' => [],
    'delete'  => [],
    'put'     => [],
    'patch'   => []
];
