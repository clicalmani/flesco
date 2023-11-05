<?php
namespace Clicalmani\Flesco\Http\Middlewares;

use Clicalmani\Container\Manager;
use Clicalmani\Flesco\Http\Requests\Request;
use Clicalmani\Flesco\Http\Response\Response;
use Clicalmani\Routes\Route;
use Clicalmani\Routes\RouteGroup;

abstract class Middleware 
{
    protected abstract function handle(Request $request, Response $response, callable $next) : int|false;

    protected abstract function boot() : void;

    public function group() : RouteGroup
    {
        return (new RouteGroup)->group(fn() => $this->boot());
        // $routes = Route::all();

        // Route::startGrouping(function() {
        //     // Add grouped routes
        //     require_once $this->handler();
        // });

        // $this->group = array_diff(Route::all(), $routes);

        // return $this;
    }

    protected function include(string $routes_file)
    {
        (new Manager)->inject(fn() => routes_path("$routes_file.php"));
    }
}