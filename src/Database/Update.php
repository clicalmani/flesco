<?php
namespace Clicalmani\Flesco\Database;

use Clicalmani\Flesco\Database\DBQueryBuilder;

class Update extends DBQueryBuilder implements \IteratorAggregate {
	
	function __construct(
		protected $params = array(), 
		protected $options = []
	) 
	{ 
		parent::__construct($params, $options);
		
		$this->sql = 'UPDATE ' . (isset($this->params['low_priority']) ? 'LOW_PRIORITY ': '') . (isset($this->params['ignore']) ? 'IGNORE ': '') . collection()->exchange($this->params['tables'])->map(function($table) {
			$arr = preg_split('/\s/', $table, -1, PREG_SPLIT_NO_EMPTY);
			$table = $arr[0];

			if ($arr[0] !== $arr[sizeof($arr)-1]) $table .= ' ' . end($arr);

			return $this->db->getPrefix() . $table;
		})->join(',');

		if (isset($this->params['join'])) {

			$tables = [];
			
			foreach ($this->params['join'] as $joint) {
				
				$tables[] = $this->db->getPrefix() . $joint['table'];
			}

			$this->sql .= ', ' . join(',', $tables);
		}
		
		$this->sql .= ' SET ' . collection()->exchange($this->params['fields'])->map(function($field, $index) {
			return "`$field` = :$field";
		})->join(',');
		
		$this->sql .= ' WHERE TRUE ';
		
		if (isset($this->params['where'])) {
			
			$this->sql .= 'AND ' . $this->params['where'];
		}
	}
	
	function query() 
	{ 
		$statement = $this->db->prepare($this->sql, $this->params['options']);

		foreach ($this->params['values'] as $i => $value) {
			$statement->bindValue($this->params['fields'][$i], $value);
		}

		foreach ($this->options as $param => $value) {
			$statement->bindValue($param, $value);
		}

		$statement->execute();
    		
		$this->status     = $statement ? true: false;
	    $this->error_code = $this->db->errno();
	    $this->error_msg  = $this->db->error();

		$statement = null;
	}
	
	function getIterator() : \Traversable {
		return new DBQueryIterator($this);
	}
	
	function error() { parent::error(); }
}
?>