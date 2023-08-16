<?php
/**
 * |---------------------------------------------------------
 * |              ***** Bootstrap *****
 * |---------------------------------------------------------
 * 
 * Bootstraping 
 * 
 * Here we setup all the necessary configurations and providers.
 */

require_once 'config.php'; 
require_once bootstrap_path( '/providers.php' ); 

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

$custom_helpers = \Clicalmani\Flesco\Providers\ServiceProvider::$providers['helpers'];

if ( !empty($custom_helpers) ) {
    foreach ($custom_helpers as $helper) {
        $helper = realpath( root_path( '/' . $helper ) );
        if (file_exists($helper) AND is_readable($helper)) {
            include_once $helper;
        }
    }
}

/**
 * Provide route service
 */
 with( new \App\Providers\RouteServiceProvider )->boot();
