<?php
namespace Clicalmani\Flesco\Security\Validators;

use Clicalmani\Flesco\Security\InputValidator;

class Email extends InputValidator
{
    protected $argument = 'email';
    
    public function validate(mixed &$email, ?array $options = []) : bool
    {
        return !! filter_var($this->parseString($email), FILTER_VALIDATE_EMAIL);
    }
}
