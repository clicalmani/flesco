<?php
namespace Clicalmani\Flesco\Http\Middleware;

abstract class Middleware 
{
    function handler() {
        return routes_path( '/web.php' );
    }

    function authorize($user) {
        return true;
    }
}