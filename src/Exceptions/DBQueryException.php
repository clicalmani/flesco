<?php
namespace Clicalmani\Flesco\Exceptions;

class DBQueryException extends \PDOException {
	function __construct($message){
		parent::__construct($message);
	}
}
