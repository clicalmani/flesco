<?php
namespace Clicalmani\Flesco\Providers;

/**
 * RouteServiceProvider class
 * 
 * @package Clicalmani\Flesco/flesco 
 * @author @Clicalmani\Flesco
 */
class HelpersServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
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
    }
}