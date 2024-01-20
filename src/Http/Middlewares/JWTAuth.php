<?php
namespace Clicalmani\Flesco\Http\Middlewares;

use Clicalmani\Flesco\Auth\JWT;
use Clicalmani\Flesco\Http\Requests\Request;
use Clicalmani\Flesco\Http\Response\Response;
use Clicalmani\Container\Manager;

/**
 * Class JWTAuth
 * 
 * @package Clicalmani\Flesco
 * @author @clicalmani
 */
abstract class JWTAuth extends JWT
{
    /**
     * Service container
     * 
     * @var \Clicalmani\Container\Manager
     */
    protected $container;

    public function __construct()
    {
        $this->container = new Manager;
        parent::__construct();
    }

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
}
