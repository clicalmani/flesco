<?php
namespace Clicalmani\Flesco\Providers;

use Clicalmani\Container\Manager;

/**
 * ServiceProvider class
 * 
 * @package clicalmani/flesco 
 * @author @clicalmani
 */
abstract class ServiceProvider
{
    /**
     * Service container
     * 
     * @var \Clicalmani\Container\Manager
     */
    protected $container;

    /**
     * Event listeners
     * 
     * @var array
     */
    protected $listen = [];
    
    public function __construct()
    {
        $this->container = new Manager;
    }

    /**
     * Service kernel
     * 
     * @var array
     */
    protected static $kernel;

    /**
     * Http middlewares
     * 
     * @var array
     */
    protected static $http_kernel;

    /**
     * (non-PHPDoc)
     * @override
     */
    protected abstract function boot() : void;

    /**
     * (non-PHPDoc)
     * @override
     */
    public function register() : void { /** TODO: Override */}

    /**
     * Bootstrap providers
     * 
     * @param array $kernel
     * @param array $http_kernel
     * @return void
     */
    public static function init(array $kernel, array $http_kernel) : void
    {
        static::$kernel     = $kernel;
        static::$http_kernel = $http_kernel;
    }

    /**
     * Make custom helper functions available
     * 
     * @return void
     */
    public static function helpers() : void
    {
        foreach (self::customHelpers() as $helper) {
            with( new $helper )->boot();
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
        return @ static::$http_kernel[$gateway][$name];
    }

    /**
     * Get specific event listeners
     * 
     * @param string $event
     * @return array
     */
    public static function getEventListeners(string $event) : array
    {
        return @ self::$listen[$event] ?? [];
    }

    /**
     * Add event listener
     * 
     * @param string $event
     * @param string $listener
     * @return void
     */
    public static function listenEvent(string $event, string $listener) : void
    {
        @ self::$listen[$event][] = $listener;
    }
}
