<?php
namespace src\Database;

class Delete extends DBQueryBuilder implements \IteratorAggregate {
	
	function __construct($params = array()) { 
		parent::__construct($params);
		
		$this->sql = 'DELETE ';
		
		if (isset($this->params['champs'])) {
			
			$this->sql .= $this->params['champs'];
		}
		
		$this->sql .= ' FROM ';
		
		for ($i=0; $i<(sizeof($this->params['tables'])-1); $i++) {
			
			$arr = preg_split('/\s/', $this->params['tables'][$i], -1, PREG_SPLIT_NO_EMPTY);
			
			$this->sql .= $this->db->prefix() . strtoupper($arr[0]);
			
			if ($arr[0] !== $arr[sizeof($arr)-1]) $this->sql .= ' ' . $arr[sizeof($arr)-1];
			
			$this->sql .= ', ';
		}
		
		$arr = preg_split('/\s/', $this->params['tables'][sizeof($this->params['tables'])-1], -1, PREG_SPLIT_NO_EMPTY);
			
		$this->sql .= $this->db->prefix() . strtoupper($arr[0]);
		
		if ($arr[0] !== $arr[sizeof($arr)-1]) $this->sql .= ' ' . $arr[sizeof($arr)-1];
		
		$this->sql .= ' WHERE TRUE ';
		
		if (isset($this->params['where'])) {
			
			$this->sql .= 'AND ' . $this->params['where'];
		}
	}
	
	function query() { 
		
	    $result = $this->db->query($this->bindVars($this->sql));
    	
		$this->status     = $result;
	    $this->error_code = $this->db->errno($this->db->getConnection());
	    $this->error_msg  = $this->db->error($this->db->getConnection());
	}
	
	function getIterator() {
		return new DBQueryIterator($this);
	}
	
	function error() { parent::error(); }
}
?>