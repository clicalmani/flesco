<?php
namespace Clicalmani\Flesco\Http\Middlewares;

use Clicalmani\Container\Manager;
use Clicalmani\Flesco\Http\Requests\Request;
use Clicalmani\Flesco\Http\Response\Response;
use Clicalmani\Routing\Group;

/**
 * Class Middleware
 * 
 * @package Clicalmani\Flesco
 * @author @Clicalmani\Flesco
 */
abstract class Middleware 
{
    /**
     * Handler
     * 
     * @param \Clicalmani\Flesco\Http\Requests\Request $request Request object
     * @param \Clicalmani\Flesco\Http\Response\Response $response Response object
     * @param callable $next Next middleware function
     * @return int|false
     */
    protected abstract function handle(Request $request, Response $response, callable $next) : int|false;

    /**
     * Bootstrap
     * 
     * @return void
     */
    protected abstract function boot() : void;

    /**
     * Group routes
     * 
     * @return \Clicalmani\Routing\Group
     */
    public function group() : Group
    {
        return (new Group)->group(fn() => $this->boot());
    }

    /**
     * Inject middleware routes into the service container.
     * 
     * @param string $routes_file Without extension
     * @return void
     */
    protected function include(string $routes_file) : void
    {
        (new Manager)->inject(fn() => routes_path("$routes_file.php"));
    }
}
