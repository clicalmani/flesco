<?php
namespace Clicalmani\Flesco\Http\Middlewares;

use Clicalmani\Flesco\Auth\JWT;
use Clicalmani\Flesco\Http\Requests\Request;
use Clicalmani\Flesco\Http\Response\Response;

abstract class JWTAuth extends JWT
{
    public function __construct()
    {
        parent::__construct();
    }

    protected abstract function handle(Request $request, Response $response, callable $next) : int|false;

    protected abstract function boot() : void;
}
