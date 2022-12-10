<?php
namespace Clicalmani\Flesco\Users;

abstract class User extends Auth 
{
	
	protected $username;
	
	function __construct($username)
	{
		parent::__construct();
		$this->username = $username;
	}
	
	function isOnline()
	{
		
		$collection = DB::raw('SELECT userIsOnline("' . $this->username . '")');

		if ($collection->count()) {
			return ( $collection->first()[0] == true );
		}

		return false;
	}
	 
	function accessLevel()
	{
		
		$collection = DB::raw('SELECT accessLevel("' . $this->username . '")');

		if ( $collection->count() ) {
			return $collection->first()[0];
		}

		return null;
	}
	 
	function changeAccessLevel($new_level)
	{
		
		DB::raw('SELECT changeAccessLevel("' . $this->username . '", "' . $new_level . '")');
	}
	
	function exists($pswd = null) 
	{
		
		$query = 'SELECT userExists("' . $this->username . '", ';
		
		if (isset($pswd)) {
			$query .= '"' . $pswd . '")'; 
		} else {
			$query .= 'NULL)';
		}
		
		$collection = DB::raw($sql);

		if ( $collection->count() ) {
			return $collection->first()[0];
		}

		return false;
	}
	
	function __toString()
	{ 
		$collection = DB::raw('SELECT * FROM ' . $this->db->getPrefix() . 'user WHERE username = "' . $this->username . '"');

		if ( $collection->count() ) {
			return json_encode( $collection->first() );
		}

		return '';
	}
}
?>