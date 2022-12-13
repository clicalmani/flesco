<?php
namespace Clicalmani\Flesco\Security;

class CSRF
{
    private $oauth;
    private $length;

    function __construct($length = 41)
    {
        $this->length = $length;
        $this->oauth = new \OAuthProvider;echo '<pre>'; print_r($this->oauth); echo '</pre>';
    }

    function getToken()
    {
        return bin2hex( $this->oauth->generateToken($this->length) );
    }
}
