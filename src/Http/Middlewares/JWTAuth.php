<?php
namespace Clicalmani\Flesco\Http\Middlewares;

use Clicalmani\Flesco\Http\Requests\Request;
use Clicalmani\Flesco\Http\Response\Response;
use Clicalmani\Container\Manager;
use Clicalmani\Flesco\Auth\AuthServiceProvider;

/**
 * Class JWTAuth
 * 
 * @package Clicalmani\Flesco
 * @author @Clicalmani\Flesco
 */
abstract class JWTAuth extends AuthServiceProvider
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
    public function boot() : void
    {
        throw new \Exception(sprintf("%s::%s must been override; in %s at line %d", static::class, __METHOD__, __CLASS__, __LINE__));
    }
}
