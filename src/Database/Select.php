<?php
namespace Clicalmani\Flesco\Database;

class Select extends DBQueryBuilder implements \IteratorAggregate 
{
	
	function __construct($params = array()) 
	{ 
		parent::__construct($params);
		
		$this->sql = 'SELECT ';
		
		if (isset($this->params['distinct']) AND $this->params['distinct'] === false) $this->sql = 'SELECT ';
		else $this->sql = 'SELECT DISTINCT ';
		
		if (isset($this->params['calc']) AND $this->params['calc']) $this->sql .= 'SQL_CALC_FOUND_ROWS ';
		else $this->sql .= '';
		
		if (isset($this->params['fields'])) {
			$this->sql .= $this->params['fields'];
		} else {
			$this->sql .= '*';
		}
		
		$this->sql .= ' FROM ' . join(',', $this->sanitizeTables($this->params['tables'])) . ' ';
		
		if (isset($this->params['sub_query'])) {
			$sub_query = trim($this->params['sub_query']);
			$alias = substr($sub_query, strrpos($sub_query, ' '));

			$this->sql .= $this->addJoint(['sub_query' => $sub_query, 'alias' => trim($alias)]) . ' ';
		}
		
		if (isset($this->params['join'])) {
			
			foreach ($this->params['join'] as $joint) {
				
				$this->sql .= $this->addJoint($joint) . ' ';
			}
		}
		
		$this->sql .= ' WHERE TRUE ';
		
		if (isset($this->params['where'])) {
			$this->sql .= ' AND ' . $this->params['where'];
		}

		if (isset($this->params['group_by'])) {
				
			$this->sql .=' GROUP BY ' . $this->params['group_by'];
			
			if (isset($this->params['having'])) {
		
				$this->sql .= ' HAVING ' . $this->params['having'];
			}
		}
		
		if (isset($this->params['order_by'])) {
			
			$this->sql .= ' ORDER BY ' . $this->params['order_by'];
		}
		
		if ( isset($this->params['limit']) ) $this->sql .= ' LIMIT ' . $this->params['offset'] . ', ' . $this->params['limit'];
	}
	
	function query() { 
		
	    $result = $this->db->query($this->bindVars($this->sql));
    	
		$this->status     = $result ? true: false;
	    $this->error_code = $this->db->errno();
	    $this->error_msg  = $this->db->error();
		$this->num_rows   = $this->db->numRows($result);
		
		$count = 0;
	    while ($row = $this->db->fetch($result, \PDO::FETCH_ASSOC)) {
	    	$this->result[] = $row;
			$count++;
		}
	}
	
	function getIterator() : \Traversable {
		return new DBQueryIterator($this);
	}
	
	function error() { parent::error(); }
}
?>