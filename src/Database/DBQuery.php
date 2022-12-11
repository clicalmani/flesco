<?php
namespace src\Database;

use src\Collection\Collection;

define('DB_QUERY_SELECT', 0);
define('DB_QUERY_INSERT', 1);
define('DB_QUERY_DELETE', 2);
define('DB_QUERY_UPDATE', 3);

class DBQuery 
{ 
	
	private $type;
	
	function __construct($query = null, $params = [])
	{ 
		$this->params = isset($params)? $params: [];
		
		$this->query = $query;
	}
	
	function set($param, $value) 
	{ 
		$this->params[$param] = $value; 
	}
	
	function exec()
	{ 
		
		$this->query = isset($this->params['query'])? $this->params['query']: $this->query;
		
		switch ($this->query){
			
			case DB_QUERY_SELECT:
				$obj = new Select($this->params);
				$obj->query();
				return $obj;
			
			case DB_QUERY_INSERT:
				$obj = new Insert($this->params);
				$obj->query();
				return $obj;
				
			case DB_QUERY_DELETE:
				$obj = new Delete($this->params);
				$obj->query();
				return $obj;
				
			case DB_QUERY_UPDATE:
				$obj = new Update($this->params);
				$obj->query();
				return $obj;
		}
	}

	function select($fields) 
	{
		$this->params['fields'] = $fields;
		return $this;
	}

	function where($criteria)
	{
		$this->params['where'] = $criteria;
		return $this;
	}

	function orderBy($order_by) {
		$this->params['order_by'] = $order_by;
		return $this;
	}

	function get()
	{
		$result = $this->exec();
		$collection = new Collection;
		
		foreach ($result as $row) {
			$collection->add($row);
		}

		return $collection;
	}
}
?>