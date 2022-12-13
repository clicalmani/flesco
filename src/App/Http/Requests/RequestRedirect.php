<?php
namespace Clicalmani\Flesco\App\Http\Requests;

class RequestRedirect {
    function __construct()
    {

    }

    function back()
    {
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }

    function route( $route )
    {
        header('Location: ' . $route);
        exit;
    }
}