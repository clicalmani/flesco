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
		
		$this->sql .= ' FROM ';
		
		for ($i=0; $i<(sizeof($this->params['tables'])-1); $i++) {
			
			$arr = preg_split('/\s/', $this->params['tables'][$i], -1, PREG_SPLIT_NO_EMPTY);
			
			$this->sql .= $this->db->getPrefix() . strtoupper($arr[0]);
			
			if ($arr[0] !== $arr[sizeof($arr)-1]) $this->sql .= ' ' . $arr[sizeof($arr)-1];
			
			$this->sql .= ', ';
		}
		
		$arr = preg_split('/\s/', $this->params['tables'][sizeof($this->params['tables'])-1], -1, PREG_SPLIT_NO_EMPTY);
			
		$this->sql .= $this->db->getPrefix() . strtoupper($arr[0]);
		
		if ($arr[0] !== $arr[sizeof($arr)-1]) $this->sql .= ' ' . $arr[sizeof($arr)-1];
		
		$this->sql .= ' ';
		
		if (isset($this->params['joint'])) {
			$this->sql .= $this->params['joint'];
		}
		
		if (isset($this->params['join'])) {
			
			foreach ($this->params['join'] as $arr) {
				
				$types = [
					'left'=>'LEFT JOIN', 
					'right'=>'RIGHT JOIN', 
					'inner'=>'INNER JOIN'
				];
				
				$this->sql .= $types[strtolower($arr['type'])] . ' ';
				
				if (isset($arr['table'])) {
					
					$arr['table'] = preg_split('/\s/', $arr['table'], -1, PREG_SPLIT_NO_EMPTY);
				
					$this->sql .= $this->db->getPrefix() . strtoupper($arr['table'][0]) . ' ' . $arr['table'][sizeof($arr['table'])-1] . ' ' . $arr['criteria'] . ' ';
				} elseif (isset($arr['sub_query'])) {
					
					$this->sql .= '(' . $arr['sub_query'] . ') ';
					
					if (isset($arr['alias'])) $this->sql .= $arr['alias'] . ' ';
					
					$this->sql .= $arr['criteria'] . ' ';
				}
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
		
		if (isset($this->params['calc']) AND $this->params['calc'] === true) $this->sql .= ' LIMIT ' . $this->params['offset'] . ', ' . $this->params['limit'];
		else $this->sql .= '';
	}
	
	function query() { 
		
	    $result = $this->db->query($this->bindVars($this->sql));
    	
		$this->status     = $result ? true: false;
	    $this->error_code = $this->db->errno();
	    $this->error_msg  = $this->db->error();
		$this->num_rows   = $this->db->numRows($result);
		
		// if (isset($this->params['calc']) AND $this->params['calc'] == true) {
		// 	$GLOBALS['pagination'] = paginer($this->db->getConnection(), $this->range, $this->limit, $this->params['query_str']);
		// }
		
		$count = 0;
	    while ($row = $this->db->fetch($result, \PDO::FETCH_ASSOC)) {
	    	$this->result[] = $row;
			$count++;
		}
	}
	
	function getIterator() {
		return new DBQueryIterator($this);
	}
	
	function error() { parent::error(); }
}
?>