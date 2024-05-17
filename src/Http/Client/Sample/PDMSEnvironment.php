<?php
namespace Clicalmani\Flesco\Http\Client\Sample;

use Clicalmani\Flesco\Http\Client\Environment;

/**
 * Class PDMSEnvironment
 * 
 * @package Clicalmani\Flesco
 * @author @Clicalmani\Flesco
 */
abstract class PDMSEnvironment implements Environment
{
    private $clientId;
    private $clientSecret;

    /**
     * Constructor
     * 
     * @param string $clientId
     * @param string $clientSecret
     */
    public function __construct(string $clientId, string $clientSecret)
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
    }

    /**
     * Get authorization string
     * 
     * @return string
     */
    public function authorizationString() : string
    {
        return base64_encode($this->clientId . ":" . $this->clientSecret);
    }
}

