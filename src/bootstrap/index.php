<?php
/**
 * |---------------------------------------------------------
 * |                     App Bootstrap
 * |---------------------------------------------------------
 * 
 * Set up app config
 */

use App\Providers\EventServiceProvider;

require_once 'config.php'; 

/**
 * |------------------------------------------------------------------
 * |                    Init Service Providers
 * |------------------------------------------------------------------
 * 
 */

\Clicalmani\Flesco\Providers\ServiceProvider::init(
    $kernel = require_once bootstrap_path( '/kernel.php' ),
    $http_kernel = require_once app_path('/Http/kernel.php')
);

/**
 * |------------------------------------------------------------------
 * |            ***** Handle Custom Helpers *****
 * |------------------------------------------------------------------
 * 
 * Custom helpers are user define functions to be accessible in any other module.
 * 
 * Customer helpers can be defined anywhere, not necessary in the app folder.
 * after initialisation, there are automatically added to the global container.
 * So that they can access any other class.
 */

\Clicalmani\Flesco\Providers\ServiceProvider::helpers();

/**
 * Install service providers
 */
\Clicalmani\Flesco\Providers\ServiceProvider::install();
