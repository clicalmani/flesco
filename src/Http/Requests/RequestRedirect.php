<?php
namespace Clicalmani\Flesco\Http\Requests;

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

    function home()
    {
        header('Location: /');
        exit;
    }
}