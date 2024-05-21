<?php
namespace Clicalmani\Flesco\Providers;

use Clicalmani\Flesco\Support\Env;

/**
 * EnvServiceProvider class
 * 
 * @package Clicalmani\Flesco/flesco 
 * @author @Clicalmani\Flesco
 */
class EnvServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Env::enablePutenv();

        /**
         * Load environment variables
         */
        \Dotenv\Dotenv::create(
            Env::getRepository(), 
            dirname( __DIR__, 5)
        )->safeLoad();
    }
}