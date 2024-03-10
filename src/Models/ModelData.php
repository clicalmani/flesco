<?php
namespace Clicalmani\Flesco\Models;

class ModelData
{
    /**
     * Update writing mode
     * 
     * @var int
     */
    const WRITING_MODE_UPDATE = 1;

    /**
     * Insert writing mode
     * 
     * @var int
     */
    const WRITING_MODE_INSERT = 2;

    /**
     * Data attribute
     * 
     * @var string
     */
    private $attribute;

    /**
     * Data value
     * 
     * @var mixed
     */
    private $value;

    /**
     * Data writing mode
     * 
     * @var int
     */
    private $writing_mode;

    public function __construct(string $attribute, mixed $value = null, ?int $writing_mode = 2)
    {
        $this->attribute = $attribute;
        $this->value = $value;
        $this->writing_mode = $writing_mode;
    }

    /**
     * Verify if data is null.
     * 
     * @return bool
     */
    public function isNull() : bool
    {
        return is_null($this->value);
    }

    public function __set(mixed $name, mixed $value)
    {
        switch ($name) {
            case 'attribute': $this->attribute = $value; break;
            case 'value': $this->value = $value; break;
            case 'writing_mode': $this->writing_mode = $value; break;
        }
    }

    public function __get(mixed $name)
    {
        switch ($name) {
            case 'attribute': return $this->attribute;
            case 'value': return $this->value;
            case 'writing_mode': return $this->writing_mode;
        }
    }
}
