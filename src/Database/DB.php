<?php
namespace Clicalmani\Flesco\Database;

use Clicalmani\Flesco\Collection\Collection;
use PDO;
use \PDOStatement;

global $db_config;

$db_config = require config_path( '/database.php' );

abstract class DB 
{
	
	static private $instance;
	static private $pdo;
	static private $prefix;
	
	private $cons = [];
	
	function __construct() 
	{
		
	}
	
	public function getConnection($driver = '') 
	{ 
		if (empty($driver)) {
			return static::$pdo ? static::$pdo : null;
		} 

		/**
		 * Driver provided
		 */
	}
	
	public function getPrefix() { return static::$prefix; }
	
	static function getInstance() 
	{
	    if ( ! static::$instance ) {
			self::getPdo();
			return new DBQuery;
		}

		return static::$instance;
	}

	static function getPdo() {
		if ( isset(static::$pdo) ) return static::$pdo;

		global $db_config;

		try {
			$db_default = $db_config['connections'][$db_config['default']];

			static::$pdo = new PDO(
				$db_default['driver'] . ':host=' . $db_default['host'] . ':' . $db_default['port'] . ';dbname=' . $db_default['name'],
				$db_default['user'],
				$db_default['pswd'],
				[PDO::ATTR_PERSISTENT => true]
			);

			static::$pdo->query('SET NAMES ' . $db_default['charset']);
			static::$pdo->query('SELECT CONCAT("ALTER TABLE ", tbl.TABLE_SCHEMA, ".", tbl.TABLE_NAME, " CONVERT TO CHARACTER SET ' . $db_default['charset'] . ' COLLATION ' . $db_default['collation'] . ';") FROM information_schema.TABLES tbl WHERE tbl.TABLE_SCHEMA = "' . $db_default['name'] . '"');
			
			static::$prefix = $db_default['prefix'];

			return static::$pdo;
		} catch(\PDOException $e) {
			die('An error occurred while trying to connect to the database server, please contact your administrator for further informations.');
		}
	}
	
	public function query($sql) { return self::getPdo()->query($sql); } 
	
	public function fetch($statement, $flag = PDO::FETCH_BOTH) 
	{ 
	    
		if ($statement instanceof PDOStatement) return $statement->fetch($flag);
		
		return null;
	}
	
	public function getRow($statement, $flag = PDO::FETCH_NUM) 
	{
		
		if ($statement instanceof PDOStatement) return $statement->fetch($flag);
		
		return [];
	}
	
	public function numRows($statement) 
	{ 
	    
		if ($statement instanceof PDOStatement) return $statement->rowCount(); 
		
		return 0;
	}

	public function prepare($sql, $options = [])
	{
		return static::$pdo->prepare($sql, $options);
	}
	
	public function error() { return static::$pdo->errorInfo(); }
	
	public function errno() { return static::$pdo->errorCode(); }
	
	public function insertId() { return static::$pdo->lastInsertId(); }
	
	public function free($statement) 
	{ 
	    
		if ($statement instanceof PDOStatement) $statement = null; 
		
		return false;
	}
	
	public function beginTransaction() { return static::$pdo->beginTransaction(); }

	public function commit() { return static::$pdo->commit(); }

	public function rollback() { return static::$pdo->rollback(); }
	
	public function close() { return static::$pdo = null; }

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
}
?>