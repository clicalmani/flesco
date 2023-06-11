<?php
namespace Clicalmani\Flesco\Exceptions;

class ModelException extends \Exception {
	function __construct($message = ''){
		parent::__construct($message);
	}
}
