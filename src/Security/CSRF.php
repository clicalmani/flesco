<?php
namespace Clicalmani\Flesco\Security;

class CSRF
{
    function getToken()
    {
        return bin2hex( Security::hash( time() ) );
    }
}
