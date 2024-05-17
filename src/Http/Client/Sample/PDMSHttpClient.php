<?php
namespace Clicalmani\Flesco\Http\Client\Sample;

use Clicalmani\Flesco\Http\Client\Core\UserAgent;
use Clicalmani\Flesco\Http\Client\HttpClient;

/**
 * Class PDMSHttpClient
 * 
 * @package Clicalmani\Flesco
 * @author @Clicalmani\Flesco
 */
class PDMSHttpClient extends HttpClient
{
    /**
     * Auth injector
     * 
     * @var AuthorizationInjector
     */
    public $authInjector;

    /**
     * Constructor
     * 
     * @param PDMSEnvironment $environment
     */
    public function __construct(PDMSEnvironment $environment)
    {
        parent::__construct($environment);
        $this->authInjector = new AuthorizationInjector($this, $environment);
        $this->addInjector($this->authInjector);
    }

    /**
     * Get user agent
     * 
     * @return string
     */
    public function userAgent() : string
    {
        return UserAgent::getValue();
    }
}

