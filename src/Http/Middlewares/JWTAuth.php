<?php
namespace Clicalmani\Flesco\Http\Middlewares;

use Clicalmani\Flesco\Auth\JWT;
use Clicalmani\Flesco\Http\Requests\Request;

abstract class JWTAuth extends JWT
{
    function __construct()
    {
        parent::__construct();
    }

    function handler() {
        return routes_path( '/api.php' );
    }

    function authorize(Request $request) {
        return true;
    }
}