<?php
namespace Clicalmani\Flesco\Database;

use Clicalmani\Flesco\Collection\Collection;
use PDO;
use \PDOStatement;

global $db_config;

$db_config = require config_path( '/database.php' );

class DB 
{
	
	static private $instance;
	
	private $cons = [];
	private $pdo;
	
	private $prefix;
	
	function __construct() 
	{
		global $db_config;

		try {
			$db_default = $db_config['connections'][$db_config['default']];

			$this->pdo = new PDO(
				$db_default['driver'] . ':host=' . $db_default['host'] . ':' . $db_default['port'] . ';dbname=' . $db_default['name'],
				$db_default['user'],
				$db_default['pswd'],
				[PDO::ATTR_PERSISTENT => true]
			);

			$this->pdo->query('SET NAMES ' . $db_default['charset']);
			$this->pdo->query('SELECT CONCAT("ALTER TABLE ", tbl.TABLE_SCHEMA, ".", tbl.TABLE_NAME, " CONVERT TO CHARACTER SET ' . $db_default['charset'] . ' COLLATION ' . $db_default['collation'] . ';") FROM information_schema.TABLES tbl WHERE tbl.TABLE_SCHEMA = "' . $db_default['name'] . '"');
			
			$this->prefix = $db_default['prefix'];
		} catch(\PDOException $e) {
			die('An error occurred while trying to connect to the database server, please contact your administrator for further informations.');
		}
	}
	
	public function getConnection($driver = '') 
	{ 
		if (empty($driver)) {
			return $this->pdo? $this->pdo: null;
		} 

		/**
		 * Driver provided
		 */
	}
	
	public function getPrefix() { return $this->prefix; }
	
	static function getInstance() 
	{
		 
	    if (!isset(self::$instance)) {
			return self::$instance = new \Clicalmani\Flesco\Database\DB();
		}
		
		return self::$instance;
	}

	static function getPdo() { return $this->pdo; }
	
	public function query($sql) { return $this->pdo->query($sql); } 
	
	public function fetch($statement, $flag = PDO::FETCH_BOTH) 
	{ 
	    
		if ($statement instanceof PDOStatement) return $statement->fetch($flag);
		
		return null;
	}
	
	public function getRow($statement) 
	{
		
		if ($statement instanceof PDOStatement) return $statement->fetch(PDO::FETCH_NUM);
		
		return array();
	}
	
	public function numRows($statement) 
	{ 
	    
		if ($statement instanceof PDOStatement) return $statement->rowCount(); 
		
		return 0;
	}

	public function prepare($sql)
	{
		return $this->pdo->prepare($sql);
	}
	
	public function error() { return $this->pdo->errorInfo(); }
	
	public function errno() { return $this->pdo->errorCode(); }
	
	public function insertId() { return $this->pdo->lastInsertId(); }
	
	public function free($statement) 
	{ 
	    
		if ($statement instanceof PDOStatement) $statement = null; 
		
		return false;
	}
	
	public function beginTransaction() { return $this->pdo->beginTransaction(); }

	public function commit() { return $this->pdo->commit(); }

	public function rollback() { return $this->pdo->rollback(); }
	
	public function close() { return $this->pdo = null; }

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

	public static function select($sql, $parameters = [])
	{
		$collection = new Collection;

		if (empty($parameters)) {
			$result = $this->query($sql);

			if (false != $result) {

				while ($row = $this->fetch($result)) {
					$collection->add($row);
				}

				return $collection;
			}
		} else {
			$stmt = $this->prepare($sql);
			$success = $stmt->execute($parameters);

			if ($success) {
				while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
					$collection->add($row);
				}
			}
		}

		return $collection;
	}

	public static function raw($sql)
	{
		return $this->query($sql);
	}
}
?>