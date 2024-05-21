<?php
namespace Clicalmani\Flesco\Http\Requests;

use Clicalmani\Flesco\Routing\Route;

/**
 * Class RequestRedirect
 * 
 * @package Clicalmani\Flesco
 * @author @Clicalmani\Flesco
 */
class RequestRedirect 
{
    /**
     * Redirect URI
     * 
     * @param mixed $uri
     * @return never
     */
    public function route(mixed ...$uri) : never
    {
        header('Location: ' . Route::resolve(...$uri));
        exit;
    }

    /**
     * Redirect back
     * 
     * @return never
     */
    public function back() : never
    {
        $this->route($_SERVER['HTTP_REFERER']);
    }

    /**
     * Redirect home
     * 
     * @return never
     */
    public function home() : never
    {
        $this->route('/');
    }

    /**
     * Redirect error
     * 
     * @param ?string $error_message
     * @return never
     */
    public function error(?string $error_message = '') : never
    {
        $this->route($_SERVER['HTTP_REFERER'] . '?error=' . $error_message);
    }

    /**
     * Redirect success
     * 
     * @param ?string $success_message
     * @return never
     */
    public function success(?string $success_message = '') : never
    {
        $this->route($_SERVER['HTTP_REFERER'] . '?success=' . $success_message);
    }
}
