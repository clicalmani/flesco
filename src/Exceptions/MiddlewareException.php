<?php
namespace Clicalmani\Flesco\Exceptions;

class MiddlewareException extends \Exception {
	function __construct($message){
		parent::__construct($message);
	}
}
?>