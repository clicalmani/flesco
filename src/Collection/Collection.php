<?php
namespace src\Collection;

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
}