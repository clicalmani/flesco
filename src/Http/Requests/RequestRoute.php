<?php
namespace Clicalmani\Flesco\Http\Requests;

use Clicalmani\Routes\Route;

/**
 * Class RequestRoute
 * 
 * @package Clicalmani\Flesco
 * @author @Clicalmani\Flesco
 */
class RequestRoute 
{
    /**
     * Get current route
     * 
     * @return string Current route
     */
    public function current() : string
    {
        return current_route();
    }

    /**
     * Verify if route has been named to the specified name.
     * 
     * @param string $name
     * @return bool
     */
    public function named(string $name) : bool
    {
        return !!(Route::currentRouteSignature() == Route::findByName($name)); 
    }
}
