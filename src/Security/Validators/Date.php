<?php
namespace Clicalmani\Flesco\Security\Validators;

use Clicalmani\Flesco\Security\InputValidator;

class Date extends InputValidator
{
    protected string $argument = 'date';

    public function options() : array
    {
        return [
            'format' => [
                'required' => true,
                'type' => 'string',
                'validator' => fn(string $value) => preg_match('/^[a-z-\/]+$/', $value)
            ]
        ];
    }

    public function validate(mixed &$date, ?array $options = [] ) : bool
    {
        $date = $this->parseString($date);
        $format = $this->parseString($options['format']);

        $bindings = [
			'Y' => '[0-9]{4}',
			'm' => '[0-9]{2}',
			'd' => '[0-9]{2}'
		];

		foreach ($bindings as $k => $v) {
			$format = str_replace($k, $v, $format);
		}
		
		return !! @ preg_match('/^' . trim($format) . '$/i', $date);
    }
}
