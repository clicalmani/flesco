<?php
namespace Clicalmani\Flesco\Http\Response;

class Response extends HttpResponse
{
    function sendStatus($code)
    {
        return http_response_code($code);
    }

    function notFound()
    {
        return http_response_code(404);
    }

    function unauthorized()
    {
        return http_response_code(401);
    }

    function forbiden()
    {
        return http_response_code(403);
    }
}