<?php
namespace Clicalmani\Flesco\App\Http\Requests;

abstract class HttpRequest {
    abstract public static function render();

    function getHeaders()
    {
        return getallheaders();
    }

    function getHeader($header_name)
    {
        foreach ($this->getHeaders() as $name => $header) {
            if ($name == $header_name) return $header;
        }
    }
}