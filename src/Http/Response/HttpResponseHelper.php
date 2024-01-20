<?php
namespace Clicalmani\Flesco\Http\Response;

/**
 * Class HttpResponseHelper
 * 
 * @package Clicalmani\Flesco
 * @author @clicalmani
 */
class HttpResponseHelper 
{
    use JsonResponse;
    
    /**
     * Send a status code
     * 
     * @param int $status_code
     * @return int|bool
     */
    public function statusCode(int $status_code) : int|bool
    {
        return http_response_code($status_code);
    }
}
