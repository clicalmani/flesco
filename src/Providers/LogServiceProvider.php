<?php
namespace Clicalmani\Flesco\Providers;

/**
 * LogServiceProvider class
 * 
 * @package clicalmani/flesco 
 * @author @clicalmani
 */
class LogServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        /**
         * Error log
         */
        \Clicalmani\Flesco\Support\Log::init( root_path() );
    }
}