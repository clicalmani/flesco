<?php
namespace Clicalmani\Flesco\Security\Validators;

use Clicalmani\Flesco\Security\InputValidator;

class RegEx extends InputValidator
{
    protected string $validator = 'regex';

    public function options() : array
    {
        return [
            'pattern' => [
                'required' => true,
                'type' => 'string'
            ]
        ];
    }

    public function validate(mixed &$value, ?array $options = []) : bool
    {
        $value = $this->parseString($value);
        $pattern = $options['pattern'];

        return !! preg_match("/^$pattern$/", $value);
    }
}
