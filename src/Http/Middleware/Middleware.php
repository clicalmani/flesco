<?php
namespace Clicalmani\Flesco\Http\Middleware;

class Middleware 
{
    protected function handler() {
        return routes_path( '/web.php' );
    }

    protected function authorize() {
        return true;
    }
}