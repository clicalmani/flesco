<?php

/**
 * |------------------------------------------------------------------
 * |                    Init Service Providers
 * |------------------------------------------------------------------
 * 
 */

$root_path = dirname( __DIR__, 5);

\Clicalmani\Flesco\Providers\ServiceProvider::init(
    $app = require_once $root_path . '/config/app.php',
    $kernel = require_once $root_path . '/bootstrap/kernel.php',
    $http_kernel = require_once $root_path . '/app/Http/kernel.php'
);
