<?php
namespace Clicalmani\Flesco\Http\Middlewares;

use Clicalmani\Routes\Route;

abstract class Middleware 
{
    private $group;

    public function handler() {
        return routes_path( '/web.php' );
    }

    public function authorize($user) {
        return true;
    }

    public function group() : static
    {
        $routes = Route::all();

        Route::startGrouping(function() {
            // Add grouped routes
            require_once $this->handler();
        });

        $this->group = array_diff(Route::all(), $routes);

        return $this;
    }

    public function prefix(string $prefix) : void
    {
        Route::setPrefix($this->group, $prefix);
    }
}