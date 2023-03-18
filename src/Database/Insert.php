<?php
namespace Clicalmani\Flesco\Database;

class Insert extends DBQueryBuilder implements \IteratorAggregate {
	
	function __construct($params = []) { 
		parent::__construct($params);
		
		$this->sql .= 'INSERT ' . (isset($params['ignore']) AND $params['ignore'] == true ? 'IGNORE': '') . ' INTO ' . $this->db->getPrefix() . strtoupper($this->params['table']);
		
		if (isset($this->params['fields'])) {
			
			$this->sql .= ' (';
			
			for ($i=0; $i<(sizeof($this->params['fields'])-1); $i++) {
				
				$this->sql .= '`' . $this->params['fields'][$i] . '`, ';
			}
			
			$this->sql .= '`' . $this->params['fields'][sizeof($this->params['fields'])-1] . '`) ';
		}
		
		$this->sql .= ' VALUES ';
		
		for ($i=0; $i<(sizeof($this->params['values'])-1); $i++) {
			
			$this->sql .= '(';
			
			for ($j=0; $j<(sizeof($this->params['values'][$i])-1); $j++) {
				
				if (isset($this->params['values'][$i][$j])) {
					$this->sql .= $this->sanitizeValue($this->params['values'][$i][$j]) . ', ';
				} else {
					$this->sql .= 'NULL, ';
				}
			}
			
			if (isset($this->params['values'][$i][sizeof($this->params['values'][$i])-1])) {
				
				$this->sql .= $this->sanitizeValue($this->params['values'][$i][sizeof($this->params['values'][$i])-1]) . '), ';
			} else {
				
				$this->sql .= 'NULL), ';
			}
		}
		
		$this->sql .= '(';
			
		for ($j=0; $j<(sizeof($this->params['values'][sizeof($this->params['values'])-1])-1); $j++) {
			
			if (isset($this->params['values'][sizeof($this->params['values'])-1][$j])) {
				
				$this->sql .= $this->sanitizeValue($this->params['values'][sizeof($this->params['values'])-1][$j]) . ', ';
			} else {
				
				$this->sql .= 'NULL, ';
			}
		}
		
		if (isset($this->params['values'][sizeof($this->params['values'])-1][sizeof($this->params['values'][sizeof($this->params['values'])-1])-1])) {
			
			$this->sql .= $this->sanitizeValue($this->params['values'][sizeof($this->params['values'])-1][sizeof($this->params['values'][sizeof($this->params['values'])-1])-1]) . '); ';
		} else {
			
			$this->sql .= 'NULL); ';
		}
	}
	
	function query() 
	{ 
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
?>