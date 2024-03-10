<?php
namespace Clicalmani\Flesco\Security\Validators;

use Clicalmani\Flesco\Security\InputValidator;

class StringArray extends InputValidator
{
    protected string $validator = 'string[]';

    public function validate(mixed &$value, ?array $options = []) : bool
    {
        $value = explode(',', $this->parseString( $value ));

        foreach ($value as $entry) {
            if ( ! is_string($entry) ) return false;
        }

        return true;
    }
}
