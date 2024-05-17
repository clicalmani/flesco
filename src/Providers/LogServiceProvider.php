<?php
namespace Clicalmani\Flesco\Providers;

/**
 * LogServiceProvider class
 * 
 * @package Clicalmani\Flesco/flesco 
 * @author @Clicalmani\Flesco
 */
class LogServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        /**
         * Error log
         */
        \Clicalmani\Flesco\Support\Facades\Log::init( root_path() );
    }
}