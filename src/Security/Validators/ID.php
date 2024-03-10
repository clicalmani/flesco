<?php
namespace Clicalmani\Flesco\Security\Validators;

use Clicalmani\Flesco\Security\InputValidator;

class ID extends InputValidator
{
    protected string $validator = 'id';

    public function options() : array
    {
        return [
            'model' => [
                'required' => true,
                'type' => 'string',
                'function' => fn(string $model) => collection(explode('_', $model))->map(fn(string $part) => ucfirst($part))->join('')
            ],
            'primary' => [
                'required' => true,
                'type' => 'string',
                'function' => function(string $primary) {
                    if ( strpos($primary, ',') ) $primary = explode(',', $primary);
                    return $primary;
                }
            ]
        ];
    }

    public function validate(mixed &$value, ?array $options = []) : bool
    {
        $model = "\\App\\Models\\" . $options['model'];
        $primaryKey = $options['primary'];

        if ( class_exists($model) ) {
            if ( is_array($primaryKey) ) $value = explode(',', $value);
            return !!$model::find($value);
        }

        return false;
    }
}
