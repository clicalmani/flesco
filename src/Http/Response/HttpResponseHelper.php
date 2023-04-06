<?php
namespace Clicalmani\Flesco\Http\Response;

class HttpResponseHelper 
{
    use JsonResponse;
    
    function statusCode($status_code)
    {
        return http_response_code($status_code);
    }
}