<?php
namespace Clicalmani\Flesco\Http\Client\Sample;

class AccessToken 
{
    public $token;
    public $tokenType;
    public $expiresIn;
    private $createDate;

    public function __construct($token, $tokenType, $expiresIn)
    {
        $this->token = $token;
        $this->tokenType = $tokenType;
        $this->expiresIn = $expiresIn;
        $this->createDate = time();
    }

    public function isExpired()
    {
        return time() >= $this->createDate + $this->expiresIn;
    }
}
