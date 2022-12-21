<?php
namespace Clicalmani\Flesco\Auth;

use App\Models\User;

abstract class UserAuth implements \ArrayAccess {
	
	private $user_id;
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

	function can($access) 
	{
		$arr = explode(':', $access);

		if ( count( $arr ) < 2 ) {
			return false;
		}
		
		$action = $arr[0];
		$access_label = $arr[1];

		$user = new \App\Models\User( $this->user_id );

		return $user->group()->permissions()->filter(function($permission) use($access_label, $action) {
			return $permission->access()
					->where('label = "' . $access_label . '" AND can_' . $action . ' = true')
					->get()
					->count();
		})->count() > 0;
	}

	function __get($attribute)
	{
		return $this->user->{$attribute};
	}
}
?>