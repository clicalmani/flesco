<?php
namespace Clicalmani\Flesco\Security;

class CSRF
{
    /**
     * Generate CSRF token
     * 
     * @return string
     */
    public function getToken() : string
    {
        return bin2hex( Security::hash( time() ) );
    }
}
