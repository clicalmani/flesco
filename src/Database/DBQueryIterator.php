<?php
namespace Clicalmani\Flesco\Database;

class DBQueryIterator implements \Iterator 
{
  
	private $obj;
	
	function __construct($obj)
	{
		$this->obj = $obj;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Iterator::rewind()
	 */
	function rewind()
	{
		$this->obj->setKey(0);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Iterator::key()
	 */
	function key()
	{ 
		return $this->obj->key();
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Iterator::current()
	 */
	function current(){ return $this->obj->getRow(); }
	
	/**
	 * (non-PHPdoc)
	 * @see Iterator::next()
	 */
	function next()
	{
		$this->obj->setKey($this->obj->key()+1);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Iterator::valid()
	 */
	function valid()
	{
		return $this->obj->key() < $this->obj->numRows();
	}
}
?>