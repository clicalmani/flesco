<?php
namespace Clicalmani\Flesco\Database;

use Clicalmani\Flesco\Collection\Collection;
use \mysqli_result;

require $_SERVER['DOCUMENT_ROOT'] . '/database/config.php';

class DB 
{
	
	static private $instance;
	
	private $con;
	
	private $prefix;
	
	const MYSQL_CONNECT_ERROR = "Une erreur est survenue lors de l'établissement d'une connexion au serveur des bases de données, veuillez réessayer plus tard.";
	
	function __construct() 
	{
		global $db_config;

		$this->con = mysqli_connect(
		    $db_config->db_host, 
		    $db_config->db_user, 
		    $db_config->db_pswd, 
		    $db_config->db_name
		) or die(self::MYSQL_CONNECT_ERROR);
		
		mysqli_set_charset($this->con, 'UTF8');
		mysqli_query($this->con, 'SET character_set_results=utf8');
		
		$this->prefix = $db_config->table_prefix;
	}
	
	public function getConnection() { return $this->con? $this->con: false; }
	
	public function getPrefix() { return $this->prefix; }
	
	static function getInstance() 
	{
		 
	    if (!isset(self::$instance)) {
			return self::$instance = new \Clicalmani\Flesco\Database\DB();
		}
		
		return self::$instance;
	}
	
	public function query($sql) { return mysqli_query($this->con, $sql); } 
	
	public function fetch($result, $flag = MYSQLI_BOTH) 
	{ 
	    
		if ($result instanceof mysqli_result) return mysqli_fetch_array($result, $flag);
		
		return [];
	}
	
	public function getRow($result) 
	{
		
		if ($result instanceof mysqli_result) return mysqli_fetch_row($result);
		
		return array();
	}
	
	public function numRows($result) 
	{ 
	    
		if ($result instanceof mysqli_result) return mysqli_num_rows($result); 
		
		return 0;
	}
	
	public function execSQL($sql) 
	{ 
		
		$succes = mysqli_multi_query($this->con, $sql);
		
		if ($succes) {
			
			do {
				
				$result = mysqli_store_result($this->con);
				if ($result instanceof mysqli_result) mysqli_free_result($result);
				
			} while (mysqli_next_result($this->con));
		}
		
		return $succes;
	}
	
	public function error() { return mysqli_error($this->con); }
	
	public function errno() { return mysqli_errno($this->con); }
	
	public function insertId() { return mysqli_insert_id($this->con); }
	
	public function free($result) 
	{ 
	    
		if ($result instanceof mysqli_result) return mysqli_free_result($result); 
		
		return false;
	}
	
	public function escape($str) { return mysqli_real_escape_string($this->con, $str); }
	
	public function close() { return mysqli_close($this->con); }

	static function table($tables)
	{
		$builder = new DBQuery;
		$builder->set('query', DB_QUERY_SELECT);
		
		if ( is_string( $tables ) ) {
			$builder->set('tables', [$tables]);
		} elseif ( is_array($tables) ) {
			$builder->set('tables', $tables);
		}
		
		return $builder;
	}

	public static function select($sql)
	{
		$result = $this->query($sql);

		if (false != $result) {

			$collection = new Collection;

			while ($row = $this->fetch($result)) {
				$collection->add($row);
			}

			return $collection;
		}

		return null;
	}

	public static function raw($sql)
	{
		$result = $this->query($sql);

		if (false != $result) {

			$collection = new Collection;

			while ($row = $this->fetch($result)) {
				$collection->add($row);
			}

			return $collection;
		}

		return null;
	}
}
?>