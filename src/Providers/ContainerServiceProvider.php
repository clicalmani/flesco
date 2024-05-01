<?php
namespace Clicalmani\Flesco\Providers;

/**
 * RouteServiceProvider class
 * 
 * @package clicalmani/flesco 
 * @author @clicalmani
 */
class ContainerServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        /**
         * |----------------------------------------------------------------
         * |            ***** Container AutoLoader *****
         * |----------------------------------------------------------------
         * 
         * Classes defined in the app directory will be automatically injected.
         */
        new \Clicalmani\Container\SPL_Loader( root_path() );
    }
}