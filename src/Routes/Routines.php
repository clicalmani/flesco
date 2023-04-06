<?php
namespace Clicalmani\Flesco\Routes;

use Clicalmani\Flesco\Collection\Collection;

class Routines implements \ArrayAccess
{
    function __construct(private Collection $storage = new Collection) {}
    /**
	 * (non-PHPdoc)
	 * @see ArrayAccess::offsetExists()
	 */
	function offsetExists(mixed $routine) : bool
	{
        return $this->storage->filter(function($rt) use($routine) {
            return $rt == $routine;
        })->count();
    }

    /** (non-PHPdoc)
     * @see ArrayAccess::offsetGet()
     */
    function offsetGet(mixed $index) : mixed
    {
        return $this->storage->filter(function($routine, $key) use($index) {
            return $index == $key;
        })->first();
    }

    /**
	 * (non-PHPdoc)
	 * @see ArrayAccess::offsetSet()
	 */
	function offsetSet(mixed $old, mixed $new) : void
	{
        $this->storage->map(function($routine, $key) use($old, $new) {
            if ($routine == $old) {
                return $new;
            }

            return $routine;
        });
    }

    /**
	 * (non-PHPdoc)
	 * @see ArrayAccess::offsetUnset()
	 */
	function offsetUnset(mixed $routine) : void
	{
        $this->storage->filter(function($rt, $key) use($routine) {
            return $rt != $routine;
        })->first();
    }
}