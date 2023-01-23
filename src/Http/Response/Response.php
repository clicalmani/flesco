<?php
namespace Clicalmani\Flesco\Http\Response;

class Response extends HttpResponse
{
    function sendStatus($code)
    {
        return http_response_code($code);
    }
}