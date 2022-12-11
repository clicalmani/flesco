<?php
namespace Cliclamani\Flesco\Users;

abstract class UserFactory extends User 
{
	function __construct($username)
	{
		parent::__construct($username);
	}
	 
	abstract function create();
}
?>