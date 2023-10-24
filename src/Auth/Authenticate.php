<?php
namespace Clicalmani\Flesco\Auth;

use App\Models\User;

abstract class Authenticate implements \ArrayAccess 
{
	/**
	 * Authenticated user
	 * 
	 * @var \App\Models\User
	 */
	private $user;
	 
	/**
	 * Constructor
	 *
	 * @param mixed $user_id 
	 */
	public function __construct( protected $user_id )
	{ 
		$this->user = new User( $user_id );
	}
	
	/**
	 * @override
	 * 
	 * @param string $attribute
	 * @return mixed
	 */
	public function __get(string $attribute)
	{
		return $this->user->{$attribute};
	}
}
