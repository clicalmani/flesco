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
        if ( isset( $index ) ) {
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
            $this[$key] = $closure($value);
        }

        return $this;
    }

    function filter($closure)
    {
        foreach ($this as $key => $value)
        {
            if (false == $closure($value)) {
                unset($this[$key]);
            }
        }

        return $this;
    }
}