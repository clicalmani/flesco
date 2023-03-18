<?php
namespace Clicalmani\Flesco\Database;

use Clicalmani\Flesco\Database\DBQueryBuilder;

class Update extends DBQueryBuilder implements \IteratorAggregate {
	
	function __construct($params = array()) { 
		parent::__construct($params);
		
		$this->sql = 'UPDATE ';
		
		for ($i=0; $i<(sizeof($this->params['tables'])-1); $i++) {
			
			$arr = preg_split('/\s/', $this->params['tables'][$i], -1, PREG_SPLIT_NO_EMPTY);
			
			$this->sql .= $this->db->getPrefix() . strtoupper($arr[0]);
			
			if ($arr[0] !== $arr[sizeof($arr)-1]) $this->sql .= ' ' . $arr[sizeof($arr)-1];
			
			$this->sql .= ', ';
		}
		
		$arr = preg_split('/\s/', $this->params['tables'][sizeof($this->params['tables'])-1], -1, PREG_SPLIT_NO_EMPTY);
			
		$this->sql .= $this->db->getPrefix() . strtoupper($arr[0]);
		
		if ($arr[0] !== $arr[sizeof($arr)-1]) $this->sql .= ' ' . $arr[sizeof($arr)-1];
		
		$this->sql .= ' SET ';
		
		if (isset($this->params['fields'])) {
			
			for ($i=0; $i<(sizeof($this->params['fields'])-1); $i++) {
				
				$this->sql .= $this->params['fields'][$i] . ' = ';
				
				if (isset($this->params['values'][$i])) {
					
					$this->sql .= $this->sanitizeValue($this->params['values'][$i]) . ', ';
				} else {
					
					$this->sql .= 'NULL, ';
				}
			}
			
			$this->sql .= $this->params['fields'][sizeof($this->params['fields'])-1] . ' = ';
			
			if (isset($this->params['values'][sizeof($this->params['fields'])-1])) {
					
				$this->sql .= $this->sanitizeValue($this->params['values'][sizeof($this->params['fields'])-1]) . ' ';
			} else {
				
				$this->sql .= 'NULL ';
			}
		}
		
		$this->sql .= 'WHERE TRUE ';
		
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