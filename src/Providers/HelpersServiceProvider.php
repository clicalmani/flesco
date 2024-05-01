<?php
namespace Clicalmani\Flesco\Providers;

/**
 * RouteServiceProvider class
 * 
 * @package clicalmani/flesco 
 * @author @clicalmani
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