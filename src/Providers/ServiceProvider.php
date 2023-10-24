<?php
namespace Clicalmani\Flesco\Providers;

/**
 * ServiceProvider class
 * 
 * @package clicalmani/flesco 
 * @author @clicalmani
 */
abstract class ServiceProvider
{
    /**
     * Service kernel
     * 
     * @var array
     */
    protected static $kernel;

    /**
     * @override
     */
    abstract function boot() : void;

    /**
     * @override
     */
    abstract function handler() : void;

    /**
     * Bootstrap providers
     * 
     * @param array $kernel
     * @return void
     */
    public static function init(array $kernel)
    {
        static::$kernel = $kernel;
    }

    /**
     * Make custom helper functions available
     * 
     * @return void
     */
    public static function helpers() : void
    {
        foreach (self::customHelpers() as $helper) {

            with( new $helper )->handler();

            // $helper = realpath( root_path( '/' . $helper ) );

            // if (file_exists($helper) AND is_readable($helper)) {
            //     include_once $helper;
            // }
        }
    }

    /**
     * Retrieve provided custom helpers
     * 
     * @return array
     */
    public static function customHelpers() : array 
    {
        if ( $custom_helpers = static::$kernel['helpers'] ) return $custom_helpers;

        return [];
    }

    /**
     * Get a provided middleware
     * 
     * @param string $gateway
     * @param string $name Middleware name
     * @return mixed
     */
    public static function getProvidedMiddleware(string $gateway, $name) : mixed
    {
        return @ static::$kernel['middlewares'][$gateway][$name];
    }
}
