<?php
namespace Clicalmani\Flesco\Collection;

class SPLCollection extends \ArrayObject
{
    public function offsetSet($index, $newval)
    {
        parent::offsetSet($index, $newval);
    }
}