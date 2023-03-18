<?php
namespace Clicalmani\Flesco\Collection;

class SPLCollection extends \ArrayObject
{
    public function offsetSet($index, $newval) : void
    {
        parent::offsetSet($index, $newval);
    }
}