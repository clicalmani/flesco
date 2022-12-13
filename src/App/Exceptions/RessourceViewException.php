<?php
namespace Clicalmani\Flesco\App\Exceptions;

class RessourceViewException extends \Exception
{
    function __construct($message){
		parent::__construct($message);
	}
}