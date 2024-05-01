<?php
namespace Clicalmani\Flesco\Providers;

/**
 * RouteServiceProvider class
 * 
 * @package clicalmani/flesco 
 * @author @clicalmani
 */
class EnvServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        /**
         * Load environment variables
         */
        \Dotenv\Dotenv::create(
            \Clicalmani\Flesco\Support\Env::getRepository(), 
            dirname( __DIR__, 5)
        )->safeLoad();
    }
}