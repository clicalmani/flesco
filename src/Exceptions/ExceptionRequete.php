<?php
namespace src\Exceptions;

class ExceptionRequete extends \Exception {
	function __construct($message){
		parent::__construct($message);
	}
}
?>