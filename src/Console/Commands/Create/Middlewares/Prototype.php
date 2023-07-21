<?php
namespace App\Http\Middleware;

use Clicalmani\Flesco\Http\Middleware\Middleware;

class ClassName extends Middleware 
{
    function handler()
    {
        // Register a handler
    }

    function authorize($request) 
    {
        /**
         * Always authorize
         */
        return true;
    }
}