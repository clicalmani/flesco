<?php
namespace Clicalmani\Flesco\Database\Factory;

use Clicalmani\Flesco\Database\DBQueryBuilder;
use Clicalmani\Flesco\Database\Factory\DataTypes\DataType;
use Clicalmani\Flesco\Exceptions\DataTypeException;

class Create extends DBQueryBuilder implements \IteratorAggregate 
{
	private $dataType;

	function __construct($params = []) 
    { 
		parent::__construct($params);
		
		$this->sql .= 'CREATE TABLE IF NOT EXISTS ' . $this->db->getPrefix() . $this->params['table'];
		
		if (isset($this->params['definition'])) {
            $this->sql .= ' (' . join(',', $this->params['definition']) . ') ';
		}
		
		if (isset($this->params['engine'])) $this->sql .= 'ENGINE = ' . $this->params['engine'];

        if (isset($this->params['collate'])) $this->sql .= 'DEFAULT COLLATE = ' . $this->params['collate'];

        if (isset($this->params['charset'])) $this->sql .= 'DEFAULT CHARACTER SET = ' . $this->params['charset'];
	}

	function query() 
	{echo $this->bindVars($this->sql);
	    $result = $this->db->query($this->bindVars($this->sql));
    		
		$this->status     = $result ? true: false;
	    $this->error_code = $this->db->errno();
	    $this->error_msg  = $this->db->error();
		$this->insert_id  = $this->db->insertId($result);
	}
	
	function getIterator() : \Traversable
	{
		return new DBQueryIterator($this);
	}
	
	function error() { parent::error(); }
}
