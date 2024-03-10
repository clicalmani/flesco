<?php
namespace Clicalmani\Flesco\Security;

trait InputParser
{
    /**
     * Parse int
     * 
     * @param mixed $value
     * @return int Parsed value
     */
    public function parseInt(mixed $value) : int
    {
        return tap($value, fn($value) => settype($value, 'integer'));
    }

    /**
     * Parse float
     * 
     * @param mixed $value
     * @return float Parsed value
     */
    public function parseFloat(mixed $value) : float
    {
        return tap($value, fn($value) => settype($value, 'double'));
    }

    /**
     * Parse boolean
     * 
     * @param mixed $value
     * @return bool Parsed value
     */
    public function parseBoolean(mixed $value) : bool
    {
        return tap($value, fn($value) => settype($value, 'boolean'));
    }

    /**
     * Parse string
     * 
     * @param mixed $value
     * @return string
     */
    public function parseString(mixed $value) : string
    {
        return tap($value, fn($value) => settype($value, 'string'));
    }

    /**
     * Parse array
     * 
     * @param mixed $value
     * @return array
     */
    public function parseArray(mixed $value) : array
    {
        return tap($value, fn($value) => settype($value, 'array'));
    }

    /**
     * Parse object
     * 
     * @param mixed $value
     * @return \stdClass
     */
    public function parseObject(mixed $value) : \stdClass
    {
        return tap($value, fn($value) => settype($value, 'object'));
    }
}
