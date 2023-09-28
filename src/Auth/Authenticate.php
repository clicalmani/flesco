<?php
namespace Clicalmani\Flesco\Auth;

use App\Models\User;

abstract class Authenticate implements \ArrayAccess {
	
	protected $user_id;
	private $user;
	 
	/**
	 * Constructor
	 *
	 * @param [integer] $user_id 
	 */
	function __construct( $user_id )
	{ 
		$this->user_id = $user_id;
		$this->user = new User( $user_id );
	}
	
	function __get($attribute)
	{
		return $this->user->{$attribute};
	}
}
?>