<?php
namespace Clicalmani\Flesco\Http\Requests;

class RequestRedirect {
    function __construct()
    {

    }

    function route( $route )
    {
        header('Location: ' . $route);
        exit;
    }

    function back()
    {
        $this->route($_SERVER['HTTP_REFERER']);
    }

    function home()
    {
        $this->route('/');
    }

    function error($message = '')
    {
        $route($_SERVER['HTTP_REFERER'] . '?error=' . $message);
    }

    function success($message = '')
    {
        $this->route($_SERVER['HTTP_REFERER'] . '?success=' . $message);
    }
}
