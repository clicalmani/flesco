<?php
namespace Flesco\Auth;

use Flesco\Database\DB;

class Auth implements \ArrayAccess {
	/**
	 * Holds the database connection resource.
	 * @var Database connection resource.
	 */
	protected $db;
	protected $username;
	 
	/**
	 * Constructor
	 *
	 * @param Database resource
	 */
	function __construct(){ $this->db = DB::getInstance(); }
	 
	/**
	 * (non-PHPdoc)
	 * @see ArrayAccess::offsetExists()
	 */
	function offsetExists($username){ 
		return DB::raw('SELECT userExists("' . $username . '", NULL)')->count();
	}
	 
	/**
	 * (non-PHPdoc)
	 * @see ArrayAccess::offsetGet()
	 */
	function offsetGet($username){ 
		$collection = DB::raw('SELECT userExists("' . $username . '", NULL)');

		if ($collection->count()) {
			return $collection->first();
		}

		return null;
	}
	 
	/**
	 * (non-PHPdoc)
	 * @see ArrayAccess::offsetSet()
	 */
	function offsetSet($username, $new_status){
		DB::raw('SELECT setUserStatus("' . $username . '", "' . $new_status . '")');
	}
	 
	/**
	 * (non-PHPdoc)
	 * @see ArrayAccess::offsetUnset()
	 */
	function offsetUnset($username){
		DB::raw('SELECT dropUser("' . $username . '")');
	}
}
?>