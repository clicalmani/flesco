<?php
namespace Clicalmani\Flesco\Collection;

class Collection extends SPLCollection 
{
    function add($val)
    {
        $this->append($val);
    }

    function get($index = null)
    {
        if ( isset( $index ) AND isset( $this[$index] ) ) {
            return $this[$index];
        }

        return $this;
    }

    function first()
    {
        return $this->get(0);
    }

    function last()
    {
        return $this[$this->count() - 1];
    }

    function map($closure)
    {
        foreach ($this as $key => $value) {
            $this[$key] = $closure($value, $key);
        }

        return $this;
    }

    function filter($closure)
    {
        $new = [];
        foreach ($this as $key => $value)
        {
            if ($closure($value, $key)) {
                $new[] = $value;
            }
        }

        $this->exchange($new);

        return $this;
    }

    function merge()
    {
        $this->exchange(
            array_merge([], (array) $this)
        );

        return $this;
    }

    function isEmpty()
    {
        return $this->count() === 0;
    }

    function exists($key)
    {
        return isset($this[$key]);
    }

    function copy()
    {
        return $this->getArrayCopy();
    }

    function exchange($array)
    {
        $this->exchangeArray($array);
        return $this;
    }

    function sort($callback)
    {
        $this->uasort($callback);
        return $this;
    }
    
    function toArray()
    {
        return (array) $this;
    }

    function toObject()
    {
        $this->setFlags(parent::ARRAY_AS_PROPS);
        return $this;
    }
}