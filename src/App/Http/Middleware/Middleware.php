<?php
namespace Clicalmani\Flesco\App\Http\Middleware;

class Middleware 
{
    protected function handler() {
        return routes_path( '/web.php' );
    }

    protected function authorize() {
        return true;
    }
}