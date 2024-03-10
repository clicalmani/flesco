<?php
namespace Clicalmani\Flesco\Security\Validators;

use Clicalmani\Flesco\Security\InputValidator;

class Enum extends InputValidator
{
    protected string $argument = 'enum';

    public function options() : array
    {
        return [
            'list' => [
                'required' => true,
                'type' => 'array'
            ]
        ];
    }

    public function validate(mixed &$value, ?array $options = []) : bool
    {
        $value = $this->parseString($value);
        $list = $this->parseArray($options['list']);
        
        return !! in_array($value, $list);
    }
}
