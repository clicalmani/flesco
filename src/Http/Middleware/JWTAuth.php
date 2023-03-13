<?php
namespace Clicalmani\Flesco\Http\Middleware;

use Clicalmani\Flesco\Auth\JWT;

abstract class JWTAuth extends JWT
{
    function __construct()
    {
        parent::__construct();
    }

    function handler() {
        return routes_path( '/api.php' );
    }

    function authorize($request) {
        return true;
    }
}