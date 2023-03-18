<?php
namespace Clicalmani\Flesco\Database;

class Delete extends DBQueryBuilder implements \IteratorAggregate {
	
	function __construct($params = array()) { 
		parent::__construct($params);
		
		$this->sql = 'DELETE ';
		
		if (isset($this->params['champs'])) {
			
			$this->sql .= $this->params['champs'];
		}

		// Clean aliases
		$tables = [];
		foreach ($this->params['tables'] as $table) {
			$a = explode(' ', $table);
			$tables[] = $this->db->getPrefix() . strtoupper($a[0]);
		}
		
		$this->sql .= ' FROM ' . join(',', $tables) . ' WHERE TRUE ';
		
		if (isset($this->params['where'])) {
			$this->sql .= 'AND ' . $this->params['where'];
		}
	}
	
	function query() { 
		
	    $result = $this->db->query($this->bindVars($this->sql));
    	
		$this->status     = $result ? true: false;
	    $this->error_code = $this->db->errno();
	    $this->error_msg  = $this->db->error();
	}
	
	function getIterator() : \Traversable {
		return new DBQueryIterator($this);
	}
	
	function error() { parent::error(); }
}
?>