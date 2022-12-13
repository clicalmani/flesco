<?php
namespace Clicalmani\Flesco\App\Exceptions;

class MiddlewareException extends \Exception {
	function __construct($message){
		parent::__construct($message);
	}
}
?>